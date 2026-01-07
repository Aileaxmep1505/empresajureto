<?php
// app/Services/MailboxService.php

namespace App\Services;

use Webklex\IMAP\Facades\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MailboxService
{
    /** @var \Webklex\IMAP\Client|null */
    protected $client = null;

    /** Cache corto para counts/mapas */
    protected int $shortTtl = 25;

    /** Cache de carpetas (más largo) */
    protected int $foldersTtl = 600;

    /** Ventanas */
    protected int $windowDefaultMonths = 12;   // 👈 antes 6, ahora más seguro (no te “come” correos)
    protected int $windowTrashSpamMonths = 2;

    /** Cuenta IMAP */
    protected string $account = 'account_default';

    public function __construct()
    {
        $this->account = env('IMAP_ACCOUNT', 'account_default');
    }

    /* =========================================================
     |                    CONEXION / CLIENTE
     * =======================================================*/

    public function connect(): self
    {
        if ($this->client) return $this;

        $this->client = Client::account($this->account);

        try {
            $this->client->connect();
        } catch (\Throwable $e) {
            usleep(150 * 1000);
            $this->client->connect();
        }

        return $this;
    }

    protected function tz(): string
    {
        return config('app.timezone', env('APP_TIMEZONE', 'America/Mexico_City'));
    }

    protected function cacheKey(string $base): string
    {
        $uid = optional(auth()->user())->getAuthIdentifier() ?? 'guest';
        return "mailbox:{$uid}:{$base}";
    }

    /* =========================================================
     |                       FOLDERS
     * =======================================================*/

    public function folderAliases(): array
    {
        // Permite override por ENV (lo primero que intenta)
        $env = [
            'INBOX'   => env('IMAP_FOLDER_INBOX'),
            'SENT'    => env('IMAP_FOLDER_SENT'),
            'DRAFTS'  => env('IMAP_FOLDER_DRAFTS'),
            'ARCHIVE' => env('IMAP_FOLDER_ARCHIVE'),
            'OUTBOX'  => env('IMAP_FOLDER_OUTBOX'),
            'SPAM'    => env('IMAP_FOLDER_SPAM'),
            'TRASH'   => env('IMAP_FOLDER_TRASH'),
        ];

        $aliases = [
            'INBOX'    => ['INBOX','Inbox','Bandeja de entrada'],
            'DRAFTS'   => ['Drafts','Borradores','INBOX.Drafts','INBOX/Drafts','[Gmail]/Drafts'],
            'SENT'     => ['Sent','Sent Mail','Enviados','INBOX.Sent','INBOX/Sent','INBOX/Sent Items','Sent Items','[Gmail]/Sent Mail'],
            'ARCHIVE'  => ['Archive','All Mail','Archivados','Archivo','INBOX.Archive','INBOX/Archive','[Gmail]/All Mail'],
            'OUTBOX'   => ['Outbox','Bandeja de salida','INBOX.Outbox','INBOX/Outbox'],
            'SPAM'     => ['Spam','Junk','Junk Email','Correo no deseado','INBOX.Junk','INBOX/Spam','[Gmail]/Spam'],
            'TRASH'    => ['Trash','Deleted Items','Papelera','INBOX.Trash','INBOX/Trash','[Gmail]/Trash'],
            'PRIORITY' => [],
            'ALL'      => [],
        ];

        // Prepend env overrides si existen
        foreach ($env as $k => $v) {
            if ($v) array_unshift($aliases[$k], $v);
        }

        return $aliases;
    }

    public function allFolders()
    {
        $this->connect();
        $cacheKey = $this->cacheKey('imap.folders.all');

        return Cache::remember($cacheKey, $this->foldersTtl, function () {
            return collect($this->client->getFolders(true));
        });
    }

    /**
     * Resuelve carpeta lógica → Folder real.
     * Soporta comparar por name/path/full_name (según versión de Webklex).
     */
    public function resolveFolder(string $logical)
    {
        $this->connect();
        $logical = strtoupper(trim($logical ?: 'INBOX'));

        if ($logical === 'PRIORITY') return $this->resolveFolder('INBOX');
        if ($logical === 'ALL') return null;

        $aliases = $this->folderAliases()[$logical] ?? [$logical];
        $all = $this->allFolders();

        $candidates = function ($f) {
            $vals = [];
            foreach (['name','path','full_name','fullName'] as $p) {
                try {
                    if (isset($f->$p) && is_string($f->$p) && $f->$p !== '') $vals[] = $f->$p;
                } catch (\Throwable $e) {}
            }
            try {
                if (method_exists($f, 'path') && is_string($f->path())) $vals[] = $f->path();
            } catch (\Throwable $e) {}
            return array_values(array_unique(array_filter($vals)));
        };

        $norm = fn($s)=>Str::of((string)$s)->lower()->replace(['/','.','\\',' '],'')->toString();

        // 1) exacto CI en cualquiera de los campos
        $found = $all->first(function($f) use ($aliases, $candidates){
            $vals = $candidates($f);
            foreach ($aliases as $a) {
                foreach ($vals as $v) {
                    if (strcasecmp($v, $a) === 0) return true;
                }
            }
            return false;
        });
        if ($found) return $found;

        // 2) normalizado
        $found = $all->first(function($f) use ($aliases, $candidates, $norm){
            $vals = $candidates($f);
            foreach ($aliases as $a) {
                $na = $norm($a);
                foreach ($vals as $v) {
                    if ($norm($v) === $na) return true;
                }
            }
            return false;
        });
        if ($found) return $found;

        // 3) heurística
        $keywords = [
            'SENT'    => ['sent','enviado','enviados','sentitems'],
            'DRAFTS'  => ['draft','borrador'],
            'ARCHIVE' => ['archive','allmail','archivo'],
            'OUTBOX'  => ['outbox','salida'],
            'SPAM'    => ['spam','junk','nodejado'],
            'TRASH'   => ['trash','deleted','papelera'],
        ];

        if (isset($keywords[$logical])) {
            $found = $all->first(function($f) use ($keywords, $logical, $candidates){
                $vals = $candidates($f);
                $joined = Str::lower(implode(' | ', $vals));
                foreach ($keywords[$logical] as $kw) {
                    if (Str::contains($joined, Str::lower($kw))) return true;
                }
                return false;
            });
            if ($found) return $found;
        }

        // fallback INBOX duro
        if ($logical === 'INBOX') {
            try { return $this->client->getFolder('INBOX'); } catch (\Throwable $e) {}
        }

        return null;
    }

    protected function windowMonths(string $logical): int
    {
        $logical = strtoupper($logical);
        if (in_array($logical, ['TRASH','SPAM'], true)) return (int) env('MAILBOX_WINDOW_TRASH_SPAM', $this->windowTrashSpamMonths);
        return (int) env('MAILBOX_WINDOW_DEFAULT', $this->windowDefaultMonths);
    }

    /* =========================================================
     |                     DECODERS / FECHAS
     * =======================================================*/

    public function decodeHeader(?string $v): string
    {
        if (!$v) return '';

        if (function_exists('iconv_mime_decode')) {
            $d = @iconv_mime_decode($v, 0, 'UTF-8');
            if ($d !== false) return $d;
        }

        if (function_exists('mb_decode_mimeheader')) {
            $d = @mb_decode_mimeheader($v);
            if (is_string($d) && $d !== '') return $d;
        }

        if (preg_match_all('/=\?([^?]+)\?(Q|B)\?([^?]+)\?=/i', $v, $mm, PREG_SET_ORDER)) {
            foreach ($mm as $p) {
                [$full,$cs,$mode,$data] = $p;
                $data = strtoupper($mode)==='B'
                    ? base64_decode($data)
                    : quoted_printable_decode(str_replace('_',' ',$data));
                $v = str_replace($full, $data, $v);
            }
        }

        return $v;
    }

    protected function parseMessageDate($m): ?Carbon
    {
        try {
            if (method_exists($m, 'getDate')) {
                $d = $m->getDate();
                if ($d instanceof Carbon) return $d;
                if ($d instanceof \DateTimeInterface) return Carbon::instance($d);
                if (is_string($d) && trim($d) !== '') return Carbon::parse($d);
            }
        } catch (\Throwable $e) {}

        try {
            $h = optional($m->get('date'))->first();
            if ($h) {
                $val = method_exists($h,'getValue') ? (string)$h->getValue() : (string)$h;
                if (trim($val) !== '') return Carbon::parse($val);
            }
        } catch (\Throwable $e) {}

        try {
            if (method_exists($m, 'getInternalDate')) {
                $id = $m->getInternalDate();
                if ($id instanceof \DateTimeInterface) return Carbon::instance($id);
                if (is_string($id) && trim($id) !== '') return Carbon::parse($id);
            }
        } catch (\Throwable $e) {}

        return null;
    }

    public function formatMessageWhen($m): string
    {
        $dt = $this->parseMessageDate($m);
        if (!$dt) return '';
        $dt = $dt->copy()->setTimezone($this->tz())->locale('es');
        return $dt->translatedFormat('d \\de F \\de Y, H:i');
    }

    public function messageFrom($m): string
    {
        $fromObj = optional($m->getFrom())->first();
        $raw = $fromObj?->personal ?: $fromObj?->mail ?: '(desconocido)';
        return $this->decodeHeader((string)$raw);
    }

    public function messageTo($m): string
    {
        $toObj = optional($m->getTo())->first();
        return (string)($toObj?->mail ?: '');
    }

    public function messageCc($m): string
    {
        $ccObj = optional($m->getCc())->first();
        return (string)($ccObj?->mail ?: '');
    }

    /* =========================================================
     |                       NORMALIZE
     * =======================================================*/

    public function normalize($m, string $folderName): array
    {
        $folderName = strtoupper($folderName ?: 'INBOX');

        $fromObj  = optional($m->getFrom())->first();
        $rawFrom  = $fromObj?->personal ?: $fromObj?->mail ?: '(desconocido)';
        $from     = $this->decodeHeader((string)$rawFrom);

        $subject  = $this->decodeHeader((string)($m->getSubject() ?: '(sin asunto)'));

        $hasAtt   = (bool) $m->hasAttachments();
        $seen     = (bool) $m->hasFlag('Seen');
        $flagged  = (bool) $m->hasFlag('Flagged');

        $dt = $this->parseMessageDate($m);
        $dateTxt = ''; $dateFull=''; $dateIso=''; $dateTs=null;

        if ($dt) {
            $dt = $dt->copy()->setTimezone($this->tz())->locale('es');
            $dateTxt  = $dt->isoFormat('DD MMM HH:mm');               // 12 dic 09:12
            $dateFull = $dt->translatedFormat('d \\de F \\de Y, H:i'); // 12 de diciembre de 2025, 09:12
            $dateIso  = $dt->toIso8601String();
            $dateTs   = $dt->timestamp;
        }

        $bodySample = $m->hasHTMLBody() ? strip_tags((string)$m->getHTMLBody()) : (string)($m->getTextBody() ?? '');
        $bodySample = html_entity_decode($bodySample, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $bodySample = trim(preg_replace('/\s+/u', ' ', $bodySample));
        $snippet    = Str::limit($bodySample, 140);

        $kind = ($folderName === 'SENT') ? 'Enviado' : 'Recibido';

        return [
            'uid'      => (int) $m->getUid(),
            'from'     => $from,
            'subject'  => $subject,
            'snippet'  => $snippet,
            'dateTxt'  => $dateTxt,
            'dateIso'  => $dateIso,
            'dateFull' => $dateFull,
            'dateTs'   => $dateTs,
            'kind'     => $kind,
            'hasAtt'   => $hasAtt,
            'seen'     => $seen,
            'flagged'  => $flagged,
            'priority' => $flagged ? 1 : 0,
            'showUrl'  => route('mail.show', [$folderName, $m->getUid()]).'?partial=1',
            'flagUrl'  => route('mail.toggleFlag', [$folderName, $m->getUid()]),
            'readUrl'  => route('mail.markRead',  [$folderName, $m->getUid()]),
            'folder'   => $folderName,
        ];
    }

    /* =========================================================
     |                       LISTADOS
     * =======================================================*/

    public function listFromFolder(string $logical, array $opts = [])
    {
        $this->connect();

        $logical   = strtoupper($logical ?: 'INBOX');
        $limit     = (int)($opts['limit'] ?? 80);
        $afterUid  = (int)($opts['after_uid'] ?? 0);
        $onlyAtt   = (bool)($opts['only_with_attachments'] ?? false);
        $priority  = (bool)($opts['priority_only'] ?? false);
        $unseen    = (bool)($opts['unseen_only'] ?? false);
        $q         = Str::lower((string)($opts['q'] ?? ''));

        if ($logical === 'PRIORITY') {
            $logical = 'INBOX';
            $priority = true;
        }

        $box = $this->resolveFolder($logical);
        if (!$box) return collect();

        // Ventana amplia (para que no te aparezcan “solo 2”)
        $months = max(1, (int)($opts['months'] ?? $this->windowMonths($logical)));

        $query = $box->query()
            ->since(now()->subMonths($months))
            ->setFetchOrder('desc')
            ->limit(max(10, $limit));

        if ($unseen && method_exists($query, 'unseen')) {
            $query->unseen();
        }

        // Ejecutar y filtrar (compatibilidad entre versiones)
        $coll = $query->get();

        if ($afterUid > 0) {
            $coll = $coll->filter(fn($m)=>(int)$m->getUid() > $afterUid);
        }

        $rows = $coll->map(fn($m)=>$this->normalize($m, $logical));

        if ($priority) {
            $rows = $rows->filter(fn($r)=>!empty($r['priority']))->values();
        }
        if ($onlyAtt) {
            $rows = $rows->filter(fn($r)=>!empty($r['hasAtt']))->values();
        }
        if ($q !== '') {
            $rows = $rows->filter(function ($r) use ($q) {
                return Str::contains(Str::lower($r['from']), $q)
                    || Str::contains(Str::lower($r['subject']), $q)
                    || Str::contains(Str::lower($r['snippet']), $q);
            })->values();
        }

        // Orden correcto si faltan timestamps
        $rows = $rows->sortByDesc(fn($r)=>$r['dateTs'] ?? 0)->values();

        return $rows->take($limit)->values();
    }

    public function listUnified(array $opts = [])
    {
        $buckets = $opts['buckets'] ?? ['INBOX','SENT','ARCHIVE'];
        $limit   = (int)($opts['limit'] ?? 120);

        $merged = collect();
        $per = max(20, (int) ceil($limit / max(1, count($buckets))));

        foreach ($buckets as $b) {
            $merged = $merged->concat(
                $this->listFromFolder($b, array_merge($opts, ['limit'=>$per]))
            );
        }

        return $merged->sortByDesc(fn($r)=>$r['dateTs'] ?? 0)->values()->take($limit);
    }

    /**
     * API principal
     */
    public function apiList(string $logical, array $opts = []): array
    {
        $logical = strtoupper($logical ?: 'INBOX');
        $limit   = (int)($opts['limit'] ?? 80);
        $after   = (int)($opts['after_uid'] ?? 0);
        $q       = (string)($opts['q'] ?? '');

        if ($logical === 'ALL') {
            $items = $this->listUnified($opts);
        } elseif ($logical === 'PRIORITY') {
            $items = $this->listFromFolder('INBOX', array_merge($opts, ['priority_only'=>true]));
        } else {
            // Si estás en INBOX y haces búsqueda, busca global (opcional)
            if ($logical === 'INBOX' && Str::of($q)->trim()->isNotEmpty()) {
                $items = $this->listUnified($opts);
            } else {
                $items = $this->listFromFolder($logical, $opts);
            }
        }

        $maxUid = (int)($items->max('uid') ?? $after);

        return [
            'ok'      => true,
            'folder'  => $logical,
            'count'   => (int)$items->count(),
            'max_uid' => $maxUid,
            'items'   => $items->values(),
        ];
    }

    /* =========================================================
     |                       COUNTS
     * =======================================================*/

    public function counts(): array
    {
        $this->connect();

        $cacheKey = $this->cacheKey('imap.counts');
        $keys = ['INBOX','PRIORITY','DRAFTS','SENT','ARCHIVE','OUTBOX','SPAM','TRASH'];

        return Cache::remember($cacheKey, $this->shortTtl, function () use ($keys) {
            $out = [];
            foreach ($keys as $k) {
                try {
                    if ($k === 'PRIORITY') {
                        $rows = $this->listFromFolder('INBOX', ['limit'=>200, 'months'=>6]);
                        $out[$k] = (int)$rows->where('priority', 1)->count();
                        continue;
                    }

                    $box = $this->resolveFolder($k);
                    if (!$box) { $out[$k]=0; continue; }

                    // Unseen en INBOX (más útil)
                    if ($k === 'INBOX') {
                        try {
                            $out[$k] = (int)$box->messages()->unseen()->count();
                            continue;
                        } catch (\Throwable $e) {}
                    }

                    $st = $box->examine();
                    $out[$k] = (int)($st->messages ?? 0);
                } catch (\Throwable $e) {
                    $out[$k] = 0;
                }
            }
            return $out;
        });
    }

    /* =========================================================
     |                    GET MESSAGE / ACCIONES
     * =======================================================*/

    public function getMessage(string $folder, string $uid)
    {
        $this->connect();
        $box = $this->resolveFolder($folder);
        if (!$box) return null;

        return $box->query()->uid($uid)->get()->first();
    }

    public function markRead(string $folder, string $uid): bool
    {
        $msg = $this->getMessage($folder, $uid);
        if (!$msg) return false;
        $msg->setFlag('Seen');
        return true;
    }

    public function toggleFlag(string $folder, string $uid): bool
    {
        $msg = $this->getMessage($folder, $uid);
        if (!$msg) return false;

        if ($msg->hasFlag('Flagged')) $msg->unsetFlag('Flagged');
        else $msg->setFlag('Flagged');

        return true;
    }

    public function move(string $srcFolder, string $uid, string $destFolder): bool
    {
        $this->connect();
        $src  = $this->resolveFolder($srcFolder);
        $dest = $this->resolveFolder($destFolder);
        if (!$src || !$dest) return false;

        $msg = $src->query()->uid($uid)->get()->first();
        if (!$msg) return false;

        $msg->move($dest);
        return true;
    }

    public function delete(string $folder, string $uid): array
    {
        $this->connect();
        $box = $this->resolveFolder($folder);
        if (!$box) return ['ok'=>false];

        $msg = $box->query()->uid($uid)->get()->first();
        if (!$msg) return ['ok'=>false];

        $isTrash = strtoupper($folder) === 'TRASH';

        if (!$isTrash) {
            $trash = $this->resolveFolder('TRASH');
            if ($trash) {
                $msg->move($trash);
                return ['ok'=>true,'moved'=>'TRASH'];
            }

            $msg->delete();
            try { $box->expunge(); } catch (\Throwable $e) {}
            return ['ok'=>true,'deleted'=>true];
        }

        $msg->delete();
        try { $box->expunge(); } catch (\Throwable $e) {}
        return ['ok'=>true,'purged'=>true];
    }

    public function downloadAttachment(string $folder, string $uid, string $part): ?array
    {
        $msg = $this->getMessage($folder, $uid);
        if (!$msg) return null;

        $att = null;
        try {
            $att = collect($msg->getAttachments() ?? [])
                ->first(function($a) use ($part) {
                    $pn = null;
                    try { $pn = (string)$a->getPartNumber(); } catch (\Throwable $e) {}
                    return $pn === (string)$part;
                });
        } catch (\Throwable $e) {}

        if (!$att) return null;

        $name = 'adjunto-'.Str::random(6);
        try { $name = $att->getName() ?: $name; } catch (\Throwable $e) {}

        $mime = 'application/octet-stream';
        try { $mime = $att->getMimeType() ?: $mime; } catch (\Throwable $e) {}

        $content = '';
        try { $content = $att->getContent(); } catch (\Throwable $e) {}

        return ['name'=>$name,'mime'=>$mime,'content'=>$content];
    }

    /* =========================================================
     |            APPEND A ENVIADOS (SMTP -> IMAP)
     * =======================================================*/

    public function appendToSentIfPossible(string $rawMime): bool
    {
        $this->connect();

        $sent = $this->resolveFolder('SENT');
        if (!$sent) return false;

        // Webklex cambia según versión: intentamos varias formas sin romper.
        try {
            if (method_exists($sent, 'appendMessage')) {
                // Algunas versiones: appendMessage($raw, $flags = [], $date = null)
                $sent->appendMessage($rawMime, ['Seen']);
                return true;
            }
        } catch (\Throwable $e) {}

        try {
            if (method_exists($this->client, 'appendMessage')) {
                // Algunas versiones: appendMessage($folderPath, $raw, $flags = [], $date = null)
                $path = $sent->path ?? $sent->name ?? 'Sent';
                $this->client->appendMessage($path, $rawMime, ['Seen']);
                return true;
            }
        } catch (\Throwable $e) {}

        return false;
    }

    /* =========================================================
     |               PREFILL (reply/forward) + THREAD
     * =======================================================*/

    public function buildPrefill(string $mode, string $folder, string $uid): array
    {
        $mode = strtolower($mode);
        $msg = $this->getMessage($folder, $uid);
        if (!$msg) return ['to'=>'','subject'=>'','body'=>''];

        $subject = $this->decodeHeader((string)($msg->getSubject() ?: '(sin asunto)'));
        $fromMail = optional($msg->getFrom())->first()?->mail ?? '';
        $replyTo  = optional($msg->getReplyTo())->first()?->mail ?? $fromMail;

        if ($mode === 'reply') {
            $sub = Str::startsWith(Str::lower($subject), 're:') ? $subject : 'Re: '.$subject;
            return [
                'to'      => (string)$replyTo,
                'subject' => $sub,
                'body'    => "",
            ];
        }

        if ($mode === 'forward') {
            $sub = Str::startsWith(Str::lower($subject), 'fwd:') ? $subject : 'Fwd: '.$subject;
            return [
                'to'      => '',
                'subject' => $sub,
                'body'    => "",
            ];
        }

        return ['to'=>'','subject'=>$subject,'body'=>''];
    }

    public function threadHeaders(string $folder, string $uid): array
    {
        $msg = $this->getMessage($folder, $uid);
        if (!$msg) return ['in_reply_to'=>'','references'=>''];

        $inReplyTo = '';
        $refs = '';

        try { $inReplyTo = (string)($msg->getMessageId() ?: ''); } catch (\Throwable $e) {}

        try {
            $refsHeader = optional($msg->get('References'))->first();
            $refsValue  = $refsHeader && method_exists($refsHeader,'getValue') ? (string)$refsHeader->getValue() : '';
            $refs = trim($refsValue.' '.$inReplyTo);
        } catch (\Throwable $e) {}

        return ['in_reply_to'=>$inReplyTo,'references'=>$refs];
    }

    /* =========================================================
     |                         HEALTH
     * =======================================================*/

    public function health(): bool
    {
        try {
            $this->connect();
            $inbox = $this->resolveFolder('INBOX');
            return (bool) $inbox;
        } catch (\Throwable $e) {
            return false;
        }
    }
}

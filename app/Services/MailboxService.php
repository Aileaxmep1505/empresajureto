<?php

namespace App\Services;

use Webklex\IMAP\Facades\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MailboxService
{
    protected $client = null;

    protected int $shortTtl   = 25;   // counts
    protected int $foldersTtl = 600;  // folders

    protected int $windowDefaultMonths   = 18;
    protected int $windowTrashSpamMonths = 2;

    protected string $account;

    public function __construct()
    {
        $this->account = env('IMAP_ACCOUNT', 'account_default');
    }

    /* =========================
     |  Core
     ========================= */

    public function connect(): self
    {
        if ($this->client) return $this;

        $this->client = Client::account($this->account);

        try {
            $this->client->connect();
        } catch (\Throwable $e) {
            \Log::error('ERROR IMAP AL CONECTAR', [
                'mensaje' => $e->getMessage(),
                'clase' => get_class($e),
            ]);

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

    /* =========================
     |  Folder resolution
     ========================= */

    public function folderAliases(): array
    {
        $aliases = [
            'INBOX'   => ['INBOX','Inbox','Bandeja de entrada'],
            'DRAFTS'  => ['Drafts','Borradores','INBOX.Drafts','INBOX/Drafts','[Gmail]/Drafts'],
            'SENT'    => ['Sent','Sent Mail','Enviados','INBOX.Sent','INBOX/Sent','Sent Items','INBOX/Sent Items','[Gmail]/Sent Mail'],
            'ARCHIVE' => ['Archive','All Mail','Archivados','Archivo','INBOX.Archive','INBOX/Archive','[Gmail]/All Mail'],
            'OUTBOX'  => ['Outbox','Bandeja de salida','INBOX.Outbox','INBOX/Outbox'],
            'SPAM'    => ['Spam','Junk','Junk Email','Correo no deseado','INBOX.Junk','INBOX/Spam','[Gmail]/Spam'],
            'TRASH'   => ['Trash','Deleted Items','Papelera','INBOX.Trash','INBOX/Trash','[Gmail]/Trash'],
            'PRIORITY'=> [],
            'ALL'     => [],
        ];

        // Overrides por .env (si existen, los intentamos primero)
        $envMap = [
            'INBOX'   => env('IMAP_FOLDER_INBOX'),
            'SENT'    => env('IMAP_FOLDER_SENT'),
            'DRAFTS'  => env('IMAP_FOLDER_DRAFTS'),
            'ARCHIVE' => env('IMAP_FOLDER_ARCHIVE'),
            'OUTBOX'  => env('IMAP_FOLDER_OUTBOX'),
            'SPAM'    => env('IMAP_FOLDER_SPAM'),
            'TRASH'   => env('IMAP_FOLDER_TRASH'),
        ];
        foreach ($envMap as $k => $v) {
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

    protected function folderCandidates($f): array
    {
        $vals = [];

        foreach (['name','path','full_name','fullName'] as $prop) {
            try {
                if (isset($f->$prop) && is_string($f->$prop) && $f->$prop !== '') $vals[] = $f->$prop;
            } catch (\Throwable $e) {}
        }

        try {
            if (method_exists($f, 'path')) {
                $p = $f->path();
                if (is_string($p) && $p !== '') $vals[] = $p;
            }
        } catch (\Throwable $e) {}

        return array_values(array_unique(array_filter($vals)));
    }

    public function resolveFolder(string $logical)
    {
        $this->connect();
        $logical = strtoupper(trim($logical ?: 'INBOX'));

        if ($logical === 'PRIORITY') return $this->resolveFolder('INBOX');
        if ($logical === 'ALL') return null;

        $aliases = $this->folderAliases()[$logical] ?? [$logical];
        $all = $this->allFolders();

        $norm = fn($s)=>Str::of((string)$s)->lower()->replace(['/','.','\\',' '],'')->toString();

        // 1) exacto CI
        $found = $all->first(function($f) use ($aliases){
            $vals = $this->folderCandidates($f);
            foreach ($aliases as $a) {
                foreach ($vals as $v) {
                    if (strcasecmp($v, $a) === 0) return true;
                }
            }
            return false;
        });
        if ($found) return $found;

        // 2) normalizado
        $found = $all->first(function($f) use ($aliases, $norm){
            $vals = $this->folderCandidates($f);
            foreach ($aliases as $a) {
                $na = $norm($a);
                foreach ($vals as $v) {
                    if ($norm($v) === $na) return true;
                }
            }
            return false;
        });
        if ($found) return $found;

        // 3) heurÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­stica suave
        $keywords = [
            'SENT'    => ['sent','enviado','enviados','sentitems'],
            'DRAFTS'  => ['draft','borrador'],
            'ARCHIVE' => ['archive','allmail','archivo'],
            'SPAM'    => ['spam','junk','nodejado'],
            'TRASH'   => ['trash','deleted','papelera'],
        ];

        if (isset($keywords[$logical])) {
            $found = $all->first(function($f) use ($keywords, $logical){
                $joined = Str::lower(implode(' | ', $this->folderCandidates($f)));
                foreach ($keywords[$logical] as $kw) {
                    if (Str::contains($joined, Str::lower($kw))) return true;
                }
                return false;
            });
            if ($found) return $found;
        }

        if ($logical === 'INBOX') {
            try { return $this->client->getFolder('INBOX'); } catch (\Throwable $e) {}
        }

        return null;
    }

    protected function folderPath($folder): string
    {
        foreach (['path','full_name','name'] as $p) {
            try {
                if (isset($folder->$p) && is_string($folder->$p) && $folder->$p !== '') return $folder->$p;
            } catch (\Throwable $e) {}
        }
        try {
            if (method_exists($folder, 'path')) {
                $x = $folder->path();
                if (is_string($x) && $x !== '') return $x;
            }
        } catch (\Throwable $e) {}
        return 'INBOX';
    }

    protected function windowMonths(string $logical): int
    {
        $logical = strtoupper($logical);
        if (in_array($logical, ['TRASH','SPAM'], true)) {
            return (int) env('MAILBOX_WINDOW_TRASH_SPAM', $this->windowTrashSpamMonths);
        }
        return (int) env('MAILBOX_WINDOW_DEFAULT', $this->windowDefaultMonths);
    }

    /* =========================
     |  Query helpers (FIX SEARCH vacÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­o)
     ========================= */

    protected function applyAllCriteria($query): void
    {
        // ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã¢â‚¬Â¦ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¦ Esta funciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n evita: "UID SEARCH: Missing search parameters"
        try {
            if (method_exists($query, 'all')) { $query->all(); return; }
        } catch (\Throwable $e) {}

        try {
            if (method_exists($query, 'whereAll')) { $query->whereAll(); return; }
        } catch (\Throwable $e) {}

        // Si no existe, no hacemos nada; pero normalmente sÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­ existe en Webklex.
    }

    /* =========================
     |  Header decode + Date
     ========================= */

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

    public function formatWhen($m): string
    {
        $dt = $this->parseMessageDate($m);
        if (!$dt) return '';
        return $dt->copy()->setTimezone($this->tz())->locale('es')
            ->translatedFormat('d \\de F \\de Y, H:i');
    }

    /* =========================
     |  Normalize
     ========================= */

    public function normalize($m, string $folderKey): array
    {
        $folderKey = strtoupper($folderKey ?: 'INBOX');

        $fromObj = optional($m->getFrom())->first();
        $rawFrom = $fromObj?->personal ?: $fromObj?->mail ?: '(desconocido)';
        $from    = $this->decodeHeader((string)$rawFrom);

        $subject = $this->decodeHeader((string)($m->getSubject() ?: '(sin asunto)'));

        $hasAtt  = (bool)$m->hasAttachments();
        $seen    = (bool)$m->hasFlag('Seen');
        $flagged = (bool)$m->hasFlag('Flagged');

        $dt = $this->parseMessageDate($m);
        $dateTxt=''; $dateFull=''; $dateIso=''; $dateTs=null;

        if ($dt) {
            $dt = $dt->copy()->setTimezone($this->tz())->locale('es');
            $dateTxt  = $dt->isoFormat('DD MMM HH:mm');
            $dateFull = $dt->translatedFormat('d \\de F \\de Y, H:i');
            $dateIso  = $dt->toIso8601String();
            $dateTs   = $dt->timestamp;
        }

        /*
         * No descargar el cuerpo al listar mensajes.
         * El contenido completo se obtiene al abrir el correo.
         */
        $snippet = '';

        return [
            'uid'      => (int)$m->getUid(),
            'from'     => $from,
            'subject'  => $subject,
            'snippet'  => $snippet,
            'dateTxt'  => $dateTxt,
            'dateIso'  => $dateIso,
            'dateFull' => $dateFull,
            'dateTs'   => $dateTs,
            'hasAtt'   => $hasAtt,
            'seen'     => $seen,
            'flagged'  => $flagged,
            'priority' => $flagged ? 1 : 0,
            'kind'     => ($folderKey === 'SENT') ? 'Enviado' : 'Recibido',
            'folder'   => $folderKey,
            'showUrl'  => route('mail.show', [$folderKey, $m->getUid()]).'?partial=1',
            'flagUrl'  => route('mail.toggleFlag', [$folderKey, $m->getUid()]),
            'readUrl'  => route('mail.markRead',  [$folderKey, $m->getUid()]),
        ];
    }

    /* =========================
     |  List messages (FIX SEARCH vacÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­o)
     ========================= */

    public function listFromFolder(string $folderKey, array $opts = [])
    {
        $this->connect();

        $folderKey = strtoupper($folderKey ?: 'INBOX');
        $limit     = min(20, max(10, (int)($opts['limit'] ?? 20)));
        $afterUid  = (int)($opts['after_uid'] ?? 0);
        $onlyAtt   = (bool)($opts['only_with_attachments'] ?? false);
        $priority  = (bool)($opts['priority_only'] ?? false);
        $unseen    = (bool)($opts['unseen_only'] ?? false);
        $q         = Str::lower((string)($opts['q'] ?? ''));

        if ($folderKey === 'PRIORITY') {
            $folderKey = 'INBOX';
            $priority = true;
        }

        $box = $this->resolveFolder($folderKey);

        if (!$box) {
            return collect();
        }

        $query = $box->query();

        if (method_exists($query, 'setFetchBody')) {
            $query->setFetchBody(false);
        }

        if (method_exists($query, 'setFetchAttachment')) {
            $query->setFetchAttachment(false);
        }

        if ($priority && method_exists($query, 'flagged')) {
            $query->flagged();
        } else {
            $this->applyAllCriteria($query);
        }

        if (in_array($folderKey, ['SPAM', 'TRASH'], true)) {
            $months = max(1, (int)($opts['months'] ?? $this->windowMonths($folderKey)));

            if (method_exists($query, 'since')) {
                $query->since(now()->subMonths($months));
            }
        }

        if ($unseen && method_exists($query, 'unseen')) {
            $query->unseen();
        }

        if (method_exists($query, 'setFetchOrder')) {
            $query->setFetchOrder('desc');
        }

        $messages = $query->limit($limit)->get();

        if ($afterUid > 0) {
            $messages = $messages->filter(
                fn($m) => (int)$m->getUid() > $afterUid
            );
        }

        $rows = $messages->map(
            fn($m) => $this->normalize($m, $folderKey)
        );

        if ($priority) {
            $rows = $rows
                ->filter(fn($r) => !empty($r['priority']))
                ->values();
        }

        if ($onlyAtt) {
            $rows = $rows
                ->filter(fn($r) => !empty($r['hasAtt']))
                ->values();
        }

        if ($q !== '') {
            $rows = $rows->filter(function ($r) use ($q) {
                return Str::contains(Str::lower($r['from']), $q)
                    || Str::contains(Str::lower($r['subject']), $q)
                    || Str::contains(Str::lower($r['snippet']), $q);
            })->values();
        }

        return $rows
            ->sortByDesc(fn($r) => $r['dateTs'] ?? 0)
            ->values()
            ->take($limit);
    }

    public function apiList(string $folderKey, array $opts = []): array
    {
        $folderKey = strtoupper($folderKey ?: 'INBOX');

        $limit = min(
            20,
            max(10, (int)($opts['limit'] ?? 20))
        );

        $after = (int)($opts['after_uid'] ?? 0);

        /*
         * Las consultas con after_uid deben ser actuales porque buscan
         * mensajes nuevos. Los listados normales pueden usar caché breve.
         */
        $usarCache = $after === 0;

        $cacheKey = 'mail:list:' . md5(
            $folderKey . '|' . json_encode(
                $opts,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            )
        );

        $cargar = function () use ($folderKey, $opts, $limit, $after) {
            if ($folderKey === 'ALL') {
                $items = collect()
                    ->concat($this->listFromFolder('INBOX', $opts))
                    ->concat($this->listFromFolder('SENT', $opts))
                    ->concat($this->listFromFolder('ARCHIVE', $opts))
                    ->sortByDesc(fn($r) => $r['dateTs'] ?? 0)
                    ->values()
                    ->take($limit);
            } else {
                $items = $this->listFromFolder($folderKey, $opts);
            }

            $maxUid = (int)($items->max('uid') ?? $after);

            return [
                'ok'      => true,
                'folder'  => $folderKey,
                'count'   => (int)$items->count(),
                'max_uid' => $maxUid,
                'items'   => $items->values(),
            ];
        };

        if (!$usarCache) {
            return $cargar();
        }

        return \Illuminate\Support\Facades\Cache::remember(
            $cacheKey,
            now()->addSeconds(45),
            $cargar
        );
    }

    public function getMessage(string $folderKey, string $uid)
    {
        $this->connect();
        $folderKey = strtoupper($folderKey);

        $box = $this->resolveFolder($folderKey);
        if (!$box) return null;

        // ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã¢â‚¬Â¦ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¦ uid() ya pone criterio, no falla
        $query = $box->query()->uid($uid);

        // El cuerpo sÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­ se necesita para la previsualizaciÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³n.
        if (method_exists($query, 'setFetchBody')) {
            $query->setFetchBody(true);
        }

        // Los archivos adjuntos se descargan ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âºnicamente cuando se solicitan.
        if (method_exists($query, 'setFetchAttachment')) {
            $query->setFetchAttachment(false);
        }

        return $query->get()->first();
    }

    public function toggleFlag(string $folderKey, string $uid): bool
    {
        $m = $this->getMessage($folderKey, $uid);
        if (!$m) return false;

        if ($m->hasFlag('Flagged')) $m->unsetFlag('Flagged');
        else $m->setFlag('Flagged');

        return true;
    }

    public function markRead(string $folderKey, string $uid): bool
    {
        $m = $this->getMessage($folderKey, $uid);
        if (!$m) return false;
        $m->setFlag('Seen');
        return true;
    }

    public function move(string $src, string $uid, string $dest): bool
    {
        $this->connect();
        $srcF  = $this->resolveFolder($src);
        $destF = $this->resolveFolder($dest);
        if (!$srcF || !$destF) return false;

        $m = $srcF->query()->uid($uid)->get()->first();
        if (!$m) return false;

        $m->move($destF);
        return true;
    }

    public function delete(string $folderKey, string $uid): array
    {
        $this->connect();
        $folderKey = strtoupper($folderKey);

        $box = $this->resolveFolder($folderKey);
        if (!$box) return ['ok'=>false];

        $m = $box->query()->uid($uid)->get()->first();
        if (!$m) return ['ok'=>false];

        $isTrash = $folderKey === 'TRASH';

        if (!$isTrash) {
            $trash = $this->resolveFolder('TRASH');
            if ($trash) {
                $m->move($trash);
                return ['ok'=>true,'moved'=>'TRASH'];
            }
            $m->delete();
            try { $box->expunge(); } catch (\Throwable $e) {}
            return ['ok'=>true,'deleted'=>true];
        }

        $m->delete();
        try { $box->expunge(); } catch (\Throwable $e) {}
        return ['ok'=>true,'purged'=>true];
    }

    public function downloadAttachment(string $folderKey, string $uid, string $part): ?array
    {
        $m = $this->getMessage($folderKey, $uid);
        if (!$m) return null;

        $att = null;
        try {
            $att = collect($m->getAttachments() ?? [])->first(function($a) use ($part){
                try { return (string)$a->getPartNumber() === (string)$part; } catch (\Throwable $e) { return false; }
            });
        } catch (\Throwable $e) {}

        if (!$att) return null;

        $name = 'adjunto-'.Str::random(6);
        $mime = 'application/octet-stream';
        $content = '';

        try { $name = $att->getName() ?: $name; } catch (\Throwable $e) {}
        try { $mime = $att->getMimeType() ?: $mime; } catch (\Throwable $e) {}
        try { $content = $att->getContent(); } catch (\Throwable $e) {}

        return ['name'=>$name,'mime'=>$mime,'content'=>$content];
    }

    /* =========================
     |  APPEND a Enviados
     ========================= */

    public function appendToSentIfPossible(string $rawMime): bool
    {
        $this->connect();
        $sent = $this->resolveFolder('SENT');
        if (!$sent) return false;

        $sentPath = $this->folderPath($sent);

        try {
            if (method_exists($this->client, 'appendMessage')) {
                $this->client->appendMessage($sentPath, $rawMime, ['Seen']);
                return true;
            }
        } catch (\Throwable $e) {}

        try {
            if (method_exists($sent, 'appendMessage')) {
                $sent->appendMessage($rawMime, ['Seen']);
                return true;
            }
        } catch (\Throwable $e) {}

        return false;
    }

    /* =========================
     |  Counts / Health
     ========================= */

    public function counts(): array
    {
        $cacheKey = $this->cacheKey('imap.counts');
        $keys = ['INBOX','PRIORITY','DRAFTS','SENT','ARCHIVE','OUTBOX','SPAM','TRASH'];

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($keys) {
            $this->connect();

            $out = [];

            foreach ($keys as $k) {
                try {
                    /*
                     * El conteo PRIORITY anterior descargaba hasta 200 mensajes
                     * completos y provocaba esperas de mÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡s de dos minutos.
                     */
                    if ($k === 'PRIORITY') {
                        $out[$k] = 0;
                        continue;
                    }

                    $box = $this->resolveFolder($k);

                    if (!$box) {
                        $out[$k] = 0;
                        continue;
                    }

                    if ($k === 'INBOX') {
                        try {
                            $out[$k] = (int) $box->messages()->unseen()->count();
                            continue;
                        } catch (\Throwable $e) {
                            $out[$k] = 0;
                            continue;
                        }
                    }

                    $status = $box->examine();
                    $out[$k] = (int) ($status->messages ?? 0);
                } catch (\Throwable $e) {
                    $out[$k] = 0;
                }
            }

            return $out;
        });
    }
    public function health(): bool
    {
        try {
            $this->connect();
            return (bool)$this->resolveFolder('INBOX');
        } catch (\Throwable $e) {
            return false;
        }
    }
}

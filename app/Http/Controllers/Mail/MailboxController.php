<?php

namespace App\Http\Controllers\Mail;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail as MailFacade;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Webklex\IMAP\Facades\Client;
use App\Mail\SimpleMail;

class MailboxController extends Controller
{
    /* =========================================================
     |               CONFIG Y CONSTANTES “ENTERPRISE”
     * =======================================================*/

    /** Ventana de meses para fetch por carpeta (reduce costo en SPAM/TRASH) */
    protected const WINDOW_DEFAULT_MONTHS     = 6;
    protected const WINDOW_TRASH_SPAM_MONTHS  = 1;

    /** Límite de mensajes por petición */
    protected const LIMIT_MIN = 10;
    protected const LIMIT_MAX = 200;

    /** Cache de estructura de carpetas (segundos) */
    protected const FOLDERS_CACHE_TTL = 600; // 10 min

    /** Timeouts para long-poll (segundos) */
    protected const WAIT_TIMEOUT_MAX = 25;
    protected const WAIT_TICK_MAX    = 5;

    /* =========================================================
     |                    HELPERS DE BAJO NIVEL
     * =======================================================*/

    /** Conexión IMAP (defensiva) */
    protected function imap() {
        $client = Client::account('account_default');
        try { $client->connect(); }
        catch (\Throwable $e) {
            usleep(120 * 1000);
            $client->connect();
        }
        return $client;
    }

    /** Cachea lista plana de carpetas (incluye subcarpetas) */
    protected function getFoldersCached($client) {
        $cacheKey = 'imap.folders.all';
        return Cache::remember($cacheKey, self::FOLDERS_CACHE_TTL, function () use ($client) {
            return collect($client->getFolders(true));
        });
    }

    /** Alias lógicos por carpeta */
    protected function folderAliases(): array {
        return [
            'INBOX'    => ['INBOX','Inbox','Bandeja de entrada'],
            'DRAFTS'   => ['Drafts','Borradores','INBOX.Drafts','INBOX/Drafts','[Gmail]/Drafts'],
            'SENT'     => ['Sent','Sent Mail','Enviados','INBOX.Sent','INBOX/Sent','INBOX/Sent Items','Sent Items','[Gmail]/Sent Mail'],
            'ARCHIVE'  => ['Archive','All Mail','Archivados','Archivo','INBOX.Archive','INBOX/Archive','[Gmail]/All Mail'],
            'OUTBOX'   => ['Outbox','Bandeja de salida','INBOX.Outbox','INBOX/Outbox'],
            'SPAM'     => ['Spam','Junk','Correo no deseado','INBOX.Junk','INBOX/Spam','[Gmail]/Spam'],
            'TRASH'    => ['Trash','Deleted Items','Papelera','INBOX.Trash','INBOX/Trash','[Gmail]/Trash'],
            // Virtuales:
            'PRIORITY' => [],
            'ALL'      => [],
        ];
    }

    /** Resuelve una carpeta lógica a la real en el servidor, usando cache y heurística */
    protected function resolveFolder($client, string $logical) {
        $logical = strtoupper($logical);
        if ($logical === 'PRIORITY') return $this->resolveFolder($client, 'INBOX');
        if ($logical === 'ALL')      return null; // unificado, no es una sola carpeta

        $aliases = $this->folderAliases()[$logical] ?? [$logical];
        $all     = $this->getFoldersCached($client);

        // 1) exacto
        $found = $all->first(function($f) use ($aliases){
            foreach ($aliases as $a) if (strcasecmp($f->name, $a) === 0) return true;
            return false;
        });
        if ($found) return $found;

        // 2) normalizando separadores
        $norm = fn($s)=>str_ireplace(['/','.'],'',$s);
        $found = $all->first(function($f) use ($aliases,$norm){
            foreach ($aliases as $a) if (strcasecmp($norm($f->name), $norm($a))===0) return true;
            return false;
        });
        if ($found) return $found;

        // 3) heurística
        $keywords = [
            'SENT'    => ['sent','enviado','enviados','sent items'],
            'DRAFTS'  => ['draft','borrador','drafts'],
            'ARCHIVE' => ['archive','all mail','archivo','archivados'],
            'OUTBOX'  => ['outbox','salida'],
            'SPAM'    => ['spam','junk','no deseado'],
            'TRASH'   => ['trash','deleted','papelera'],
        ];
        if (isset($keywords[$logical])) {
            $found = $all->first(function($f) use ($keywords,$logical){
                $n = strtolower($f->name);
                foreach ($keywords[$logical] as $kw) if (str_contains($n,$kw)) return true;
                return false;
            });
            if ($found) return $found;
        }

        // fallback
        if ($logical === 'INBOX') return $client->getFolder('INBOX');
        return null;
    }

    /** Decodifica encabezados RFC-2047 (seguro) */
    protected function decodeHeader(?string $v): string {
        if (!$v) return '';
        if (function_exists('iconv_mime_decode')) {
            $d = @iconv_mime_decode($v, 0, 'UTF-8');
            if ($d !== false) return $d;
        }
        if (function_exists('mb_decode_mimeheader')) {
            $d = @mb_decode_mimeheader($v);
            if (is_string($d) && $d !== '') return $d;
        }
        if (preg_match_all('/=\?([^?]+)\?(Q|B)\?([^?]+)\?=/i', $v, $m, PREG_SET_ORDER)) {
            foreach ($m as $p) {
                [$full,$cs,$mode,$data] = $p;
                $data = strtoupper($mode)==='B' ? base64_decode($data) : quoted_printable_decode(str_replace('_',' ',$data));
                $v = str_replace($full, $data, $v);
            }
        }
        return $v;
    }

    /** Normaliza un mensaje a un shape liviano para UI/API */
    protected function normalizeMessage($m, string $folderName): array {
        $fromObj  = optional($m->getFrom())->first();
        $rawFrom  = $fromObj?->personal ?: $fromObj?->mail ?: '(desconocido)';
        $from     = $this->decodeHeader($rawFrom);
        $subject  = $this->decodeHeader($m->getSubject() ?: '(sin asunto)');
        $hasAtt   = $m->hasAttachments();
        $seen     = $m->hasFlag('Seen');
        $flagged  = $m->hasFlag('Flagged');

        $dateHeader = optional($m->get('date'))->first();
        $dateVal    = method_exists($dateHeader,'getValue') ? (string)$dateHeader->getValue() : null;

        $dateTxt=''; $dateTs=null; $dateIso=null; $dateFull=null;
        try {
            if ($dateVal) {
                $dt = Carbon::parse($dateVal)->locale('es');
                $dateTxt  = $dt->isoFormat('DD MMM HH:mm');
                $dateIso  = $dt->toIso8601String();
                $dateFull = $dt->translatedFormat('d \\de M Y, H:i:s');
                $dateTs   = $dt->timestamp;
            }
        } catch (\Throwable $e) {}

        $bodySample = $m->hasHTMLBody() ? strip_tags($m->getHTMLBody()) : $m->getTextBody();
        $snippet    = Str::limit(trim(preg_replace('/\s+/',' ', $bodySample ?? '')), 140);

        $kind = (strtoupper($folderName)==='SENT') ? 'Enviado' : 'Recibido';

        return [
            'uid'      => (int)$m->getUid(),
            'from'     => $from,
            'subject'  => $subject,
            'snippet'  => $snippet,
            'dateTxt'  => $dateTxt,
            'dateIso'  => $dateIso,
            'dateFull' => $dateFull,
            'kind'     => $kind,
            'dateTs'   => $dateTs,
            'hasAtt'   => $hasAtt,
            'seen'     => $seen,
            'flagged'  => $flagged,
            'priority' => $flagged ? 1 : 0,
            'showUrl'  => route('mail.show', [$folderName, $m->getUid()]) . '?partial=1',
            'flagUrl'  => route('mail.toggleFlag',[$folderName, $m->getUid()]),
            'readUrl'  => route('mail.markRead',  [$folderName, $m->getUid()]),
            'folder'   => strtoupper($folderName),
        ];
    }

    /** Ventana por carpeta */
    protected function windowMonths(string $logical): int {
        $logical = strtoupper($logical);
        return in_array($logical, ['TRASH','SPAM'], true)
            ? (int) env('MAILBOX_WINDOW_TRASH_SPAM', self::WINDOW_TRASH_SPAM_MONTHS)
            : (int) env('MAILBOX_WINDOW_DEFAULT', self::WINDOW_DEFAULT_MONTHS);
    }

    /** Descarga lista de mensajes (1 carpeta) */
    protected function fetchMessages($folder, int $limit = 80, ?string $logical = 'INBOX') {
        $logical = strtoupper($logical ?? 'INBOX');
        $months  = $this->windowMonths($logical);

        return $folder->query()
            ->since(now()->subMonths(max(1, $months)))
            ->setFetchOrder('desc')
            ->limit($limit)
            ->get();
    }

    /** Render JSON con headers de cache defensivos */
    protected function jsonOk($payload = [], ?string $etagSeed = null) {
        $etag = $etagSeed ? '"'.sha1($etagSeed).'"' : null;
        $resp = response()->json($payload, 200);
        if ($etag) $resp->header('ETag', $etag);
        $resp->header('Last-Modified', gmdate('D, d M Y H:i:s').' GMT');
        $resp->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        $resp->header('Pragma', 'no-cache');
        return $resp;
    }

    protected function jsonErr(string $msg, int $code = 400) {
        return response()->json(['ok'=>false,'error'=>$msg], $code);
    }

    /* =========================================================
     |                 LISTADOS UNIFICADOS / FILTROS
     * =======================================================*/

    protected function listFromFolder($client, string $logical, array $opts = []) {
        $logical   = strtoupper($logical);
        $limit     = (int)($opts['limit'] ?? 80);
        $afterUid  = (int)($opts['after_uid'] ?? 0);
        $onlyAtt   = (bool)($opts['only_with_attachments'] ?? false);
        $prioOnly  = (bool)($opts['priority_only'] ?? false);
        $q         = Str::lower((string)($opts['q'] ?? ''));

        $box = $this->resolveFolder($client, $logical);
        if (!$box) return collect();

        $coll = $this->fetchMessages($box, $limit, $logical);
        if ($afterUid) $coll = $coll->filter(fn($m)=>(int)$m->getUid() > $afterUid);

        $rows = $coll->map(fn($m)=>$this->normalizeMessage($m, $logical));

        if ($prioOnly || $logical==='PRIORITY') {
            $rows = $rows->where('priority', 1)->values();
        }
        if ($onlyAtt) {
            $rows = $rows->where('hasAtt', true)->values();
        }
        if ($q !== '') {
            $rows = $rows->filter(function ($r) use ($q) {
                return Str::contains(Str::lower($r['from']), $q)
                    || Str::contains(Str::lower($r['subject']), $q)
                    || Str::contains(Str::lower($r['snippet']), $q);
            })->values();
        }
        return $rows->take($limit)->values();
    }

    protected function listUnified($client, array $opts = []) {
        $buckets = $opts['buckets'] ?? ['INBOX','SENT','ARCHIVE'];
        $limit   = (int)($opts['limit'] ?? 120);

        $merged = collect();
        $slice  = max(1, intval($limit / max(1, count($buckets))));
        foreach ($buckets as $b) {
            $merged = $merged->concat($this->listFromFolder($client, $b, $opts)->take($slice));
        }
        return $merged->sortByDesc('dateTs')->values()->take($limit);
    }

    /* =========================================================
     |                       VISTAS BÁSICAS
     * =======================================================*/

    public function index(Request $r) {
        $client   = $this->imap();
        $folders  = $this->getFoldersCached($client);
        $inbox    = $this->resolveFolder($client, env('IMAP_DEFAULT_FOLDER','INBOX')) ?: $client->getFolder('INBOX');
        $messages = $this->fetchMessages($inbox, 80, 'INBOX');

        return view('mail.index', [
            'folders'  => $folders,
            'messages' => $messages,
            'counts'   => $this->safeCounts($client),
            'current'  => 'INBOX',
        ]);
    }

    public function folder(Request $r, string $name) {
        $client   = $this->imap();
        $box      = $this->resolveFolder($client, $name) ?: $this->resolveFolder($client, 'INBOX');
        $folders  = $this->getFoldersCached($client);
        $messages = $this->fetchMessages($box, 80, strtoupper($name));

        return view('mail.index', [
            'folders'  => $folders,
            'messages' => $messages,
            'counts'   => $this->safeCounts($client),
            'current'  => strtoupper($name),
        ]);
    }

    public function show(Request $r, string $folder, string $uid) {
        $client = $this->imap();
        $box    = $this->resolveFolder($client, $folder);
        abort_unless($box, 404);

        $msg = $box->query()->uid($uid)->get()->first();
        abort_unless($msg, 404);

        $attachments = $msg->getAttachments();
        return view('mail.show', compact('msg','folder','uid','attachments'));
    }

    public function downloadAttachment(Request $r, string $folder, string $uid, string $part) {
        $client = $this->imap();
        $box    = $this->resolveFolder($client, $folder);
        abort_unless($box, 404);

        $msg = $box->query()->uid($uid)->get()->first();
        abort_unless($msg, 404);

        $att = collect($msg->getAttachments())->first(fn($a)=>(string)$a->getPartNumber()===(string)$part);
        abort_unless($att, 404);

        $filename = $att->getName() ?: ('adjunto-'.Str::random(6));
        return response($att->getContent(), 200, [
            'Content-Type'        => $att->getMimeType() ?: 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"'
        ]);
    }

    /* =========================================================
     |                          ACCIONES
     * =======================================================*/

    public function compose(){ return view('mail.compose'); }

    public function send(Request $r)
    {
        $data = $r->validate([
            'to'      => ['required','string'],
            'subject' => ['required','string','max:200'],
            'body'    => ['required','string'],
            'cc'      => ['nullable','string'],
            'bcc'     => ['nullable','string'],
            'files.*' => ['nullable','file','max:15360'], // 15MB por archivo
        ]);

        $to   = SimpleMail::splitEmails($data['to']);
        $cc   = isset($data['cc'])  ? SimpleMail::splitEmails($data['cc'])  : [];
        $bcc  = isset($data['bcc']) ? SimpleMail::splitEmails($data['bcc']) : [];
        $files = $r->file('files', []); // array de UploadedFile

        // ✅ Usa SIEMPRE el facade importado como MailFacade
        MailFacade::send(new SimpleMail($to, $data['subject'], $data['body'], $cc, $bcc, $files));

        return back()->with('ok', 'Correo enviado correctamente.');
    }

    public function reply(Request $r, string $folder, string $uid) {
        $data = $r->validate(['body'=>['required','string']]);

        $client = $this->imap();
        $box    = $this->resolveFolder($client, $folder);
        abort_unless($box, 404);

        $msg = $box->query()->uid($uid)->get()->first();
        abort_unless($msg, 404);

        $subject = 'Re: '.($msg->getSubject() ?: '(sin asunto)');
        $to = optional($msg->getReplyTo())->first()?->mail
           ?? optional($msg->getFrom())->first()?->mail;

        $refsHeader = optional($msg->get('References'))->first();
        $refsValue  = method_exists($refsHeader,'getValue') ? (string)$refsHeader->getValue() : '';
        $refs       = trim($refsValue.' '.$msg->getMessageId());

        $mailable = new SimpleMail([$to], $subject, $data['body']);
        // In-Reply-To / References
        $mailable->withSymfonyMessage(function ($symfonyEmail) use ($msg, $refs) {
            if (method_exists($symfonyEmail, 'getHeaders')) {
                $headers = $symfonyEmail->getHeaders();
                $headers->addTextHeader('In-Reply-To', $msg->getMessageId());
                if ($refs) $headers->addTextHeader('References', $refs);
            }
        });

        MailFacade::send($mailable);
        return back()->with('ok','Respuesta enviada ✔️');
    }

    public function forward(Request $r, string $folder, string $uid) {
        $data = $r->validate(['to'=>['required','string','max:500'], 'note'=>['nullable','string']]);

        $client = $this->imap();
        $box    = $this->resolveFolder($client, $folder);
        abort_unless($box, 404);

        $msg = $box->query()->uid($uid)->get()->first();
        abort_unless($msg, 404);

        $subject = 'Fwd: '.($msg->getSubject() ?: '(sin asunto)');

        // Reenviar como texto plano (seguro); si quieres HTML real, te ajusto la vista.
        $origText = $msg->hasHTMLBody() ? strip_tags($msg->getHTMLBody()) : ($msg->getTextBody() ?? '');
        $body = ($data['note'] ? "Nota: ".$data['note']."\n\n" : '')
              . $origText;

        $to = SimpleMail::splitEmails($data['to']);
        $mailable = new SimpleMail($to, $subject, $body);

        // Adjuntar archivos originales
        foreach ($msg->getAttachments() as $att) {
            $mailable->attachData($att->getContent(), $att->getName() ?: 'adjunto', [
                'mime' => $att->getMimeType() ?: 'application/octet-stream'
            ]);
        }

        MailFacade::send($mailable);
        return back()->with('ok','Reenviado ✔️');
    }

    public function toggleFlag(Request $r, string $folder, string $uid) {
        $client = $this->imap();
        $box    = $this->resolveFolder($client, $folder);
        abort_unless($box, 404);

        $msg = $box->query()->uid($uid)->get()->first();
        abort_unless($msg, 404);

        if ($msg->hasFlag('Flagged')) $msg->unsetFlag('Flagged');
        else                           $msg->setFlag('Flagged');

        return response()->noContent();
    }

    public function markRead(Request $r, string $folder, string $uid) {
        $client = $this->imap();
        $box    = $this->resolveFolder($client, $folder);
        abort_unless($box, 404);
        $msg = $box->query()->uid($uid)->get()->first();
        abort_unless($msg, 404);
        $msg->setFlag('Seen');
        return response()->noContent();
    }

    public function move(Request $r, string $folder, string $uid) {
        $data = $r->validate(['dest'=>['required','string']]);

        $client = $this->imap();
        $src    = $this->resolveFolder($client, $folder);
        $dest   = $this->resolveFolder($client, $data['dest']);

        abort_unless($src && $dest, 404);

        $msg = $src->query()->uid($uid)->get()->first();
        abort_unless($msg, 404);

        $msg->move($dest);
        return $this->jsonOk(['ok'=>true], "move:$folder:$uid:{$data['dest']}");
    }

    public function delete(Request $r, string $folder, string $uid) {
        $client = $this->imap();
        $box    = $this->resolveFolder($client, $folder);
        abort_unless($box, 404);

        $msg = $box->query()->uid($uid)->get()->first();
        abort_unless($msg, 404);

        $isTrash = strtoupper($folder) === 'TRASH';

        if (!$isTrash) {
            $trash = $this->resolveFolder($client, 'TRASH');
            if ($trash) {
                $msg->move($trash);
                return $this->jsonOk(['ok'=>true,'moved'=>'TRASH'], "delete-to-trash:$folder:$uid");
            } else {
                $msg->delete();
                try { $box->expunge(); } catch (\Throwable $e) {}
                return $this->jsonOk(['ok'=>true,'deleted'=>true], "delete-expunge:$folder:$uid");
            }
        } else {
            $msg->delete();
            try { $box->expunge(); } catch (\Throwable $e) {}
            return $this->jsonOk(['ok'=>true,'purged'=>true], "purge:$folder:$uid");
        }
    }

    /* =========================================================
     |                       APIS “RÁPIDAS”
     * =======================================================*/

    public function apiMessages(Request $r) {
        $folderName = strtoupper($r->string('folder', env('IMAP_DEFAULT_FOLDER','INBOX'))->toString());
        $limit      = max(self::LIMIT_MIN, min(self::LIMIT_MAX, (int)$r->integer('limit', 80)));
        $afterUid   = (int)$r->integer('after_uid', 0);
        $q          = $r->string('q')->toString();
        $onlyAtt    = $r->boolean('only_with_attachments', false);
        $prioOnly   = $r->boolean('priority_only', false);

        $client = $this->imap();

        $useUnified = ($folderName === 'ALL') || ($folderName === 'INBOX' && Str::of($q)->trim()->isNotEmpty());

        if ($useUnified) {
            $items = $this->listUnified($client, [
                'limit'                 => $limit,
                'after_uid'             => $afterUid,
                'q'                     => $q,
                'only_with_attachments' => $onlyAtt,
                'priority_only'         => false,
            ]);
        } elseif ($folderName === 'PRIORITY') {
            $items = $this->listFromFolder($client, 'INBOX', [
                'limit'                 => $limit,
                'after_uid'             => $afterUid,
                'q'                     => $q,
                'only_with_attachments' => $onlyAtt,
                'priority_only'         => true,
            ]);
        } else {
            $items = $this->listFromFolder($client, $folderName, [
                'limit'                 => $limit,
                'after_uid'             => $afterUid,
                'q'                     => $q,
                'only_with_attachments' => $onlyAtt,
                'priority_only'         => $prioOnly,
            ]);
        }

        $maxUid = $items->max('uid') ?? $afterUid;

        return $this->jsonOk([
            'folder'   => $folderName,
            'count'    => $items->count(),
            'max_uid'  => (int)$maxUid,
            'items'    => $items->values(),
        ], "apiMessages:$folderName:$maxUid:{$items->count()}:".md5($q.':'.($onlyAtt?'1':'0').':'.($prioOnly?'1':'0')));
    }

    public function apiWait(Request $r) {
        $folder = strtoupper($r->string('folder', 'INBOX')->toString());
        $after  = (int)$r->integer('after_uid', 0);
        $timeout= max(5, min(self::WAIT_TIMEOUT_MAX, (int)$r->integer('timeout', 25)));
        $tick   = max(1, min(self::WAIT_TICK_MAX,  (int)$r->integer('tick', 3)));
        $limit  = max(self::LIMIT_MIN, min(self::LIMIT_MAX,(int)$r->integer('limit', 120)));
        $q      = $r->string('q')->toString();
        $onlyAtt= $r->boolean('only_with_attachments', false);
        $prio   = $r->boolean('priority_only', false);

        $client = $this->imap();

        $start = microtime(true);
        $items = collect();
        $maxUid= $after;

        do {
            $useUnified = ($folder === 'ALL') || ($folder === 'INBOX' && Str::of($q)->trim()->isNotEmpty());
            if ($useUnified) {
                $rows = $this->listUnified($client, [
                    'limit'                 => $limit,
                    'after_uid'             => $after,
                    'q'                     => $q,
                    'only_with_attachments' => $onlyAtt,
                    'priority_only'         => false,
                ]);
            } elseif ($folder === 'PRIORITY') {
                $rows = $this->listFromFolder($client, 'INBOX', [
                    'limit'                 => $limit,
                    'after_uid'             => $after,
                    'q'                     => $q,
                    'only_with_attachments' => $onlyAtt,
                    'priority_only'         => true,
                ]);
            } else {
                $rows = $this->listFromFolder($client, $folder, [
                    'limit'                 => $limit,
                    'after_uid'             => $after,
                    'q'                     => $q,
                    'only_with_attachments' => $onlyAtt,
                    'priority_only'         => $prio,
                ]);
            }

            if ($rows->count()) {
                $items  = $rows;
                $maxUid = max($after, (int)$rows->max('uid'));
                break;
            }

            usleep($tick * 300000); // 0.3s * tick
        } while ((microtime(true) - $start) < $timeout);

        return $this->jsonOk([
            'folder'  => $folder,
            'max_uid' => (int)$maxUid,
            'items'   => $items->values(),
        ], "apiWait:$folder:$maxUid:{$items->count()}");
    }

    public function apiMessagesWait(Request $r) {
        return $this->apiWait($r);
    }

    public function apiCounts(Request $r) {
        $client = $this->imap();
        return $this->jsonOk($this->safeCounts($client), 'apiCounts');
    }

    protected function safeCounts($client): array {
        $keys = ['INBOX','PRIORITY','DRAFTS','SENT','ARCHIVE','OUTBOX','SPAM','TRASH'];
        $out  = [];

        foreach ($keys as $k) {
            try {
                if ($k === 'PRIORITY') {
                    $box    = $this->resolveFolder($client,'INBOX') ?: $client->getFolder('INBOX');
                    $recent = $this->fetchMessages($box, 150, 'INBOX');
                    $out[$k] = (int)$recent->filter(fn($m)=>$m->hasFlag('Flagged'))->count();
                    continue;
                }
                $box = $this->resolveFolder($client, $k);
                if (!$box) { $out[$k]=0; continue; }
                $st = $box->examine();
                $out[$k] = (int)($st->messages ?? 0);
            } catch (\Throwable $e) {
                $out[$k] = 0;
            }
        }
        return $out;
    }

    /* =========================================================
     |                         HEALTHCHECK
     * =======================================================*/

    public function health(Request $r) {
        try {
            $client = $this->imap();
            $box = $this->resolveFolder($client, 'INBOX') ?: $client->getFolder('INBOX');
            $ok  = (bool)$box;
            return $this->jsonOk(['ok'=>$ok, 'ts'=>now()->toIso8601String()], 'health:ok');
        } catch (\Throwable $e) {
            return $this->jsonErr('imap_unreachable', 503);
        }
    }
}

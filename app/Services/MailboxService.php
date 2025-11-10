<?php

namespace App\Services;

use Webklex\IMAP\Facades\Client;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class MailboxService
{
    /** @var \Webklex\IMAP\Client */
    protected $client;

    /** Cache corto (segundos) para conteos y mapas de carpetas */
    protected int $shortTtl = 25;

    /** Mapa de aliases lógicos → nombres posibles en el servidor */
    public function folderAliases(): array {
        return [
            'INBOX'    => ['INBOX','Inbox','Bandeja de entrada'],
            'DRAFTS'   => ['Drafts','Borradores','INBOX.Drafts','INBOX/Drafts','[Gmail]/Drafts'],
            'SENT'     => ['Sent','Sent Mail','Enviados','INBOX.Sent','INBOX/Sent','INBOX/Sent Items','Sent Items','[Gmail]/Sent Mail'],
            'ARCHIVE'  => ['Archive','All Mail','Archivados','Archivo','INBOX.Archive','INBOX/Archive','[Gmail]/All Mail'],
            'OUTBOX'   => ['Outbox','Bandeja de salida','INBOX.Outbox','INBOX/Outbox'],
            'SPAM'     => ['Spam','Junk','Correo no deseado','INBOX.Junk','INBOX/Spam','[Gmail]/Spam'],
            'TRASH'    => ['Trash','Deleted Items','Papelera','INBOX.Trash','INBOX/Trash','[Gmail]/Trash'],
            // Virtuales:
            'PRIORITY' => [], // flagged de INBOX
            'ALL'      => [], // unificado: INBOX + SENT + ARCHIVE (por defecto)
        ];
    }

    /** Conecta (1 vez por request) */
    public function connect(): self {
        if (!$this->client) {
            $this->client = Client::account('account_default');
            $this->client->connect();
        }
        return $this;
    }

    /** Devuelve todas las carpetas (cacheadas corto en memoria+Cache) */
    public function allFolders() {
        $this->connect();
        $cacheKey = $this->cacheKey('imap.folders');
        return Cache::remember($cacheKey, $this->shortTtl, function () {
            return collect($this->client->getFolders(true));
        });
    }

    /** Resuelve una carpeta lógica a la real del servidor (robusto) */
    public function resolveFolder(string $logical) {
        $this->connect();
        $logical = strtoupper($logical);
        // Virtuales
        if ($logical === 'PRIORITY') return $this->resolveFolder('INBOX');
        if ($logical === 'ALL')      return null; // no es una sola carpeta

        $aliases = $this->folderAliases()[$logical] ?? [$logical];
        $all = $this->allFolders();

        // 1) exacto (case-insensitive)
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

        // fallback INBOX
        if ($logical === 'INBOX') return $this->client->getFolder('INBOX');
        return null;
    }

    /** Ventana (meses) por carpeta para acelerar */
    protected function windowMonths(string $logical): int {
        $logical = strtoupper($logical);
        return in_array($logical, ['TRASH','SPAM'], true) ? 1 : 6;
    }

    /** Normaliza mensaje a “item” liviano para UI */
    public function normalize($m, string $folderName): array {
        $decode = function (?string $v): string {
            if (!$v) return '';
            if (function_exists('iconv_mime_decode')) { $d=@iconv_mime_decode($v,0,'UTF-8'); if($d!==false) return $d; }
            if (function_exists('mb_decode_mimeheader')) { $d=@mb_decode_mimeheader($v); if(is_string($d)&&$d!=='') return $d; }
            if (preg_match_all('/=\?([^?]+)\?(Q|B)\?([^?]+)\?=/i', $v, $mm, PREG_SET_ORDER)) {
                foreach ($mm as $p) {
                    [$full,$cs,$mode,$data] = $p;
                    $data = strtoupper($mode)==='B' ? base64_decode($data) : quoted_printable_decode(str_replace('_',' ',$data));
                    $v = str_replace($full, $data, $v);
                }
            }
            return $v;
        };

        $fromObj  = optional($m->getFrom())->first();
        $rawFrom  = $fromObj?->personal ?: $fromObj?->mail ?: '(desconocido)';
        $from     = $decode($rawFrom);
        $subject  = $decode($m->getSubject() ?: '(sin asunto)');
        $hasAtt   = $m->hasAttachments();
        $seen     = $m->hasFlag('Seen');
        $flagged  = $m->hasFlag('Flagged');

        $dateHeader = optional($m->get('date'))->first();
        $dateVal    = method_exists($dateHeader,'getValue') ? (string)$dateHeader->getValue() : null;
        $dateTxt    = ''; $dateTs=null;
        try{
            if($dateVal){
                $dt = Carbon::parse($dateVal)->locale('es');
                $dateTxt = $dt->isoFormat('DD MMM HH:mm');
                $dateTs  = $dt->timestamp;
            }
        }catch(\Throwable $e){}

        $bodySample = $m->hasHTMLBody() ? strip_tags($m->getHTMLBody()) : $m->getTextBody();
        $snippet = Str::limit(trim(preg_replace('/\s+/',' ', $bodySample ?? '')), 140);

        return [
            'uid'      => (int)$m->getUid(),
            'from'     => $from,
            'subject'  => $subject,
            'snippet'  => $snippet,
            'dateTxt'  => $dateTxt,
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

    /** Lee mensajes de UNA carpeta (ventana de tiempo + limit + afterUID) */
    public function listFromFolder(string $logical, array $opts = []): \Illuminate\Support\Collection {
        $this->connect();
        $logical   = strtoupper($logical);
        $limit     = (int)($opts['limit'] ?? 80);
        $afterUid  = (int)($opts['after_uid'] ?? 0);
        $onlyAtt   = (bool)($opts['only_with_attachments'] ?? false);
        $priority  = (bool)($opts['priority_only'] ?? false);
        $q         = Str::lower((string)($opts['q'] ?? ''));

        $box = $this->resolveFolder($logical);
        if (!$box) return collect();

        // Ventana acotada
        $months = $this->windowMonths($logical);
        $query = $box->query()
            ->since(now()->subMonths($months))
            ->setFetchOrder('desc')
            ->limit($limit);

        // Ejecutar y post-filtrar rápido en memoria
        $coll = $query->get();

        if ($afterUid) {
            $coll = $coll->filter(fn($m)=>(int)$m->getUid() > $afterUid);
        }

        // Normalizar
        $rows = $coll->map(fn($m)=>$this->normalize($m, $logical));

        // Virtual PRIORITY si piden priority_only
        if ($priority || $logical==='PRIORITY') {
            $rows = $rows->filter(fn($r)=>!empty($r['priority']))->values();
        }

        // Adjuntos
        if ($onlyAtt) {
            $rows = $rows->filter(fn($r)=>!empty($r['hasAtt']))->values();
        }

        // Búsqueda simple (from, subject, snippet)
        if ($q !== '') {
            $rows = $rows->filter(function ($r) use ($q) {
                return Str::contains(Str::lower($r['from']), $q)
                    || Str::contains(Str::lower($r['subject']), $q)
                    || Str::contains(Str::lower($r['snippet']), $q);
            })->values();
        }

        return $rows->take($limit)->values();
    }

    /** Lista UNIFICADA (ALL): INBOX + SENT + ARCHIVE por defecto */
    public function listUnified(array $opts = []): \Illuminate\Support\Collection {
        $buckets = $opts['buckets'] ?? ['INBOX','SENT','ARCHIVE'];
        $limit   = (int)($opts['limit'] ?? 120);

        $merged = collect();
        foreach ($buckets as $b) {
            $merged = $merged->concat($this->listFromFolder($b, $opts)->take(intval($limit / max(1,count($buckets)))));
        }

        // Ordenar por timestamp desc y cortar
        return $merged->sortByDesc('dateTs')->values()->take($limit);
    }

    /**
     * API principal para listar:
     * - logical 'ALL' → unificado (global search por defecto)
     * - logical 'PRIORITY' → solo flagged
     * - INBOX con q != '' → usa unificado para simular “buscar en todo”
     */
    public function apiList(string $logical, array $opts = []): array {
        $logical = strtoupper($logical);
        $limit   = (int)($opts['limit'] ?? 80);
        $q       = (string)($opts['q'] ?? '');

        if ($logical === 'ALL') {
            $items = $this->listUnified($opts);
        } elseif ($logical === 'PRIORITY') {
            $opts['priority_only'] = true;
            $items = $this->listFromFolder('INBOX', $opts);
        } else {
            // Si estamos en INBOX y hay búsqueda -> buscar global
            if ($logical === 'INBOX' && Str::of($q)->trim()->isNotEmpty()) {
                $items = $this->listUnified($opts);
            } else {
                $items = $this->listFromFolder($logical, $opts);
            }
        }

        $maxUid = $items->max('uid') ?? (int)($opts['after_uid'] ?? 0);

        return [
            'folder'  => $logical,
            'count'   => $items->count(),
            'max_uid' => (int)$maxUid,
            'items'   => $items->take($limit)->values(),
        ];
    }

    /** Conteos rápidos por carpeta (cacheados) + PRIORITY (flagged recientes) */
    public function counts(): array {
        $this->connect();
        $keys = ['INBOX','PRIORITY','DRAFTS','SENT','ARCHIVE','OUTBOX','SPAM','TRASH'];
        $cacheKey = $this->cacheKey('imap.counts');

        return Cache::remember($cacheKey, $this->shortTtl, function () use ($keys) {
            $out = [];
            foreach ($keys as $k) {
                try {
                    if ($k === 'PRIORITY') {
                        $rows = $this->listFromFolder('INBOX', ['limit'=>150]);
                        $out[$k] = (int)$rows->where('priority',1)->count();
                        continue;
                    }
                    $box = $this->resolveFolder($k);
                    if (!$box) { $out[$k]=0; continue; }
                    $st = $box->examine();
                    $out[$k] = (int)($st->messages ?? 0);
                } catch (\Throwable $e) {
                    $out[$k] = 0;
                }
            }
            return $out;
        });
    }

    /** Operaciones sobre un mensaje */
    public function getMessage(string $folder, string $uid) {
        $this->connect();
        $box = $this->resolveFolder($folder);
        if (!$box) return null;
        return $box->query()->uid($uid)->get()->first();
    }

    public function markRead(string $folder, string $uid): bool {
        $msg = $this->getMessage($folder,$uid);
        if (!$msg) return false;
        $msg->setFlag('Seen'); return true;
    }

    public function toggleFlag(string $folder, string $uid): bool {
        $msg = $this->getMessage($folder,$uid);
        if (!$msg) return false;
        if ($msg->hasFlag('Flagged')) $msg->unsetFlag('Flagged');
        else                           $msg->setFlag('Flagged');
        return true;
    }

    public function move(string $srcFolder, string $uid, string $destFolder): bool {
        $this->connect();
        $src  = $this->resolveFolder($srcFolder);
        $dest = $this->resolveFolder($destFolder);
        if (!$src || !$dest) return false;
        $msg = $src->query()->uid($uid)->get()->first();
        if (!$msg) return false;
        $msg->move($dest); return true;
    }

    /** delete: si no es TRASH → mover a TRASH; si ya es TRASH → borrar y expunge */
    public function delete(string $folder, string $uid): array {
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
            } else {
                $msg->delete();
                try { $box->expunge(); } catch (\Throwable $e) {}
                return ['ok'=>true,'deleted'=>true];
            }
        } else {
            $msg->delete();
            try { $box->expunge(); } catch (\Throwable $e) {}
            return ['ok'=>true,'purged'=>true];
        }
    }

    /** Utilidad para claves de cache por usuario (si hay auth) para evitar colisiones multi-cuenta */
    protected function cacheKey(string $base): string {
        $uid = optional(auth()->user())->getAuthIdentifier() ?? 'guest';
        return "mailbox:{$uid}:{$base}";
    }
}

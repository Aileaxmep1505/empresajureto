<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Webklex\IMAP\Facades\Client;
use App\Models\User;
use App\Notifications\NewMailNotification;

class PollMailboxNotifications extends Command
{
    protected $signature = 'mailbox:poll-notifications
                            {--folder=INBOX : Carpeta IMAP}
                            {--limit=25 : Cuantos revisar}
                            {--sinceDays=7 : Ventana de días}
                            {--users=* : IDs de usuarios a notificar (si vacío, notifica a todos)}';

    protected $description = 'Detecta correos nuevos por IMAP y crea notificaciones (database) para la campanita';

    protected function decodeHeader(?string $v): string
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
        if (preg_match_all('/=\?([^?]+)\?(Q|B)\?([^?]+)\?=/i', $v, $m, PREG_SET_ORDER)) {
            foreach ($m as $p) {
                [$full,$cs,$mode,$data] = $p;
                $data = strtoupper($mode)==='B' ? base64_decode($data) : quoted_printable_decode(str_replace('_',' ',$data));
                $v = str_replace($full, $data, $v);
            }
        }
        return $v;
    }

    public function handle(): int
    {
        $folder = strtoupper((string)$this->option('folder'));
        $limit  = max(5, min(200, (int)$this->option('limit')));
        $sinceDays = max(1, min(60, (int)$this->option('sinceDays')));

        // Usuarios a notificar
        $userIds = $this->option('users') ?: [];
        $users = empty($userIds)
            ? User::query()->get()
            : User::query()->whereIn('id', $userIds)->get();

        if ($users->isEmpty()) {
            $this->warn('No hay usuarios para notificar.');
            return self::SUCCESS;
        }

        // Conectar IMAP
        $client = Client::account('account_default');
        try { $client->connect(); }
        catch (\Throwable $e) {
            usleep(120 * 1000);
            $client->connect();
        }

        // Carpeta
        try {
            $box = $client->getFolder($folder);
        } catch (\Throwable $e) {
            $this->error("No se pudo abrir la carpeta IMAP: {$folder}");
            return self::FAILURE;
        }

        // Estado global por carpeta (para evitar spam)
        $lastUidKey = "mailbox:last_uid:{$folder}";
        $lastUid = (int) Cache::get($lastUidKey, 0);

        // Traer últimos mensajes
        $msgs = $box->query()
            ->since(now()->subDays($sinceDays))
            ->setFetchOrder('desc')
            ->limit($limit)
            ->get();

        if (!$msgs || $msgs->count() === 0) {
            $this->line('Sin mensajes.');
            return self::SUCCESS;
        }

        // UIDs ordenados asc
        $rows = $msgs->map(function($m) use ($folder){
            $fromObj = optional($m->getFrom())->first();
            $rawFrom = $fromObj?->personal ?: $fromObj?->mail ?: '(desconocido)';
            $from    = $this->decodeHeader($rawFrom);
            $subject = $this->decodeHeader($m->getSubject() ?: '(sin asunto)');

            $bodySample = $m->hasHTMLBody() ? strip_tags($m->getHTMLBody()) : $m->getTextBody();
            $snippet = Str::limit(trim(preg_replace('/\s+/',' ', $bodySample ?? '')), 120);

            $dateHeader = optional($m->get('date'))->first();
            $dateVal    = method_exists($dateHeader,'getValue') ? (string)$dateHeader->getValue() : null;
            $dateTxt    = null;
            try { if ($dateVal) $dateTxt = Carbon::parse($dateVal)->locale('es')->isoFormat('DD MMM HH:mm'); } catch (\Throwable $e) {}

            return [
                'uid'     => (int) $m->getUid(),
                'from'    => $from,
                'subject' => $subject,
                'snippet' => $snippet,
                'dateTxt' => $dateTxt,
            ];
        })->sortBy('uid')->values();

        $maxUidFound = (int) ($rows->max('uid') ?? 0);

        // Primer corrida: fija last uid y NO notifica (para no inundar)
        if ($lastUid <= 0) {
            Cache::forever($lastUidKey, $maxUidFound);
            $this->info("Inicializado last_uid={$maxUidFound} para {$folder} (sin notificar).");
            return self::SUCCESS;
        }

        $new = $rows->filter(fn($r) => $r['uid'] > $lastUid)->values();
        if ($new->isEmpty()) {
            $this->line("Sin nuevos (last_uid={$lastUid}).");
            return self::SUCCESS;
        }

        $sentCount = 0;

        foreach ($new as $r) {
            // anti-duplicado por UID (7 días)
            $dupKey = "mailbox:notif:{$folder}:{$r['uid']}";
            if (!Cache::add($dupKey, 1, now()->addDays(7))) {
                continue;
            }

            foreach ($users as $u) {
                $u->notify(new NewMailNotification(
                    folder: $folder,
                    uid: $r['uid'],
                    from: $r['from'],
                    subject: $r['subject'],
                    snippet: $r['snippet'],
                    dateTxt: $r['dateTxt'],
                ));
                $sentCount++;
            }
        }

        // actualizar last uid
        Cache::forever($lastUidKey, max($lastUid, $maxUidFound));

        $this->info("Notificaciones creadas: {$sentCount}. last_uid => ".Cache::get($lastUidKey));
        return self::SUCCESS;
    }
}

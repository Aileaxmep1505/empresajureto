<?php
// app/Http/Controllers/Mail/MailboxController.php

namespace App\Http\Controllers\Mail;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail as MailFacade;
use Illuminate\Support\Str;
use App\Mail\SimpleMail;
use App\Services\MailboxService;

class MailboxController extends Controller
{
    /* =========================================================
     |               LIMITES / AJUSTES “ENTERPRISE”
     * =======================================================*/
    protected const LIMIT_MIN = 10;
    protected const LIMIT_MAX = 200;

    protected const WAIT_TIMEOUT_MAX = 25;
    protected const WAIT_TICK_MAX    = 5;

    public function __construct(protected MailboxService $mailbox) {}

    /* =========================================================
     |                       VISTAS
     * =======================================================*/

    public function index(Request $r)
    {
        $folder = strtoupper($r->get('folder', env('IMAP_DEFAULT_FOLDER', 'INBOX')));

        // Lista inicial (para que la vista cargue algo aun si el API tarda)
        $list = $this->mailbox->apiList($folder, [
            'limit'                 => 80,
            'after_uid'             => 0,
            'q'                     => '',
            'only_with_attachments' => false,
            'priority_only'         => false,
            'unseen_only'           => false,
        ]);

        return view('mail.index', [
            'messages' => $list['raw_messages'] ?? [], // opcional (si tu blade usa mensajes IMAP). Si no, no pasa nada.
            'counts'   => $this->mailbox->counts(),
            'current'  => $folder,
        ]);
    }

    public function folder(Request $r, string $name)
    {
        return redirect()->route('mail.index', ['folder' => strtoupper($name)]);
    }

    /**
     * Compose mejorado:
     * - Soporta ?mode=reply|forward&folder=INBOX&uid=123 para precargar
     */
    public function compose(Request $r)
    {
        $mode   = strtolower((string)$r->get('mode', 'new')); // new|reply|forward
        $folder = strtoupper((string)$r->get('folder', 'INBOX'));
        $uid    = (string)$r->get('uid', '');

        $prefill = [
            'mode'    => $mode,
            'folder'  => $folder,
            'uid'     => $uid,
            'to'      => '',
            'subject' => '',
            'body'    => '',
            'cc'      => '',
            'bcc'     => '',
        ];

        if (in_array($mode, ['reply','forward'], true) && $uid !== '') {
            $prefill = array_merge($prefill, $this->mailbox->buildPrefill($mode, $folder, $uid));
        }

        return view('mail.compose', compact('prefill'));
    }

    /* =========================================================
     |                          ENVIO
     * =======================================================*/

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

        $to    = SimpleMail::splitEmails($data['to']);
        $cc    = isset($data['cc'])  ? SimpleMail::splitEmails($data['cc'])  : [];
        $bcc   = isset($data['bcc']) ? SimpleMail::splitEmails($data['bcc']) : [];
        $files = $r->file('files', []);

        $rawSent = null;

        $mailable = new SimpleMail($to, $data['subject'], $data['body'], $cc, $bcc, $files);

        // Capturar MIME para poder APPEND a Enviados (si el servidor no lo hace solo)
        $mailable->withSymfonyMessage(function ($symfonyEmail) use (&$rawSent) {
            if (is_object($symfonyEmail) && method_exists($symfonyEmail, 'toString')) {
                $rawSent = $symfonyEmail->toString();
            }
        });

        MailFacade::send($mailable);

        // Intentar guardar copia en Enviados (IMAP APPEND)
        if ($rawSent) {
            $this->mailbox->appendToSentIfPossible($rawSent);
        }

        return back()->with('ok', 'Correo enviado correctamente.');
    }

    /* =========================================================
     |                   RESPONDER / REENVIAR
     * =======================================================*/

    public function reply(Request $r, string $folder, string $uid)
    {
        $data = $r->validate(['body'=>['required','string']]);

        $prefill = $this->mailbox->buildPrefill('reply', strtoupper($folder), (string)$uid);

        $to = SimpleMail::splitEmails($prefill['to'] ?: '');
        if (!$to) return back()->with('err','No se pudo determinar destinatario para responder.');

        $rawSent = null;
        $mailable = new SimpleMail($to, $prefill['subject'] ?: '(sin asunto)', $data['body']);

        // Headers threading (In-Reply-To / References)
        $thread = $this->mailbox->threadHeaders(strtoupper($folder), (string)$uid);
        $mailable->withSymfonyMessage(function ($symfonyEmail) use (&$rawSent, $thread) {
            if (is_object($symfonyEmail) && method_exists($symfonyEmail, 'getHeaders')) {
                $headers = $symfonyEmail->getHeaders();
                if (!empty($thread['in_reply_to'])) $headers->addTextHeader('In-Reply-To', $thread['in_reply_to']);
                if (!empty($thread['references']))  $headers->addTextHeader('References',  $thread['references']);
            }
            if (is_object($symfonyEmail) && method_exists($symfonyEmail, 'toString')) {
                $rawSent = $symfonyEmail->toString();
            }
        });

        MailFacade::send($mailable);

        if ($rawSent) {
            $this->mailbox->appendToSentIfPossible($rawSent);
        }

        return back()->with('ok','Respuesta enviada ✔️');
    }

    public function forward(Request $r, string $folder, string $uid)
    {
        $data = $r->validate([
            'to'   => ['required','string','max:500'],
            'note' => ['nullable','string'],
        ]);

        $msg = $this->mailbox->getMessage(strtoupper($folder), (string)$uid);
        if (!$msg) abort(404);

        $subject = 'Fwd: ' . ($this->mailbox->decodeHeader((string)($msg->getSubject() ?: '(sin asunto)')));

        $origText = $msg->hasHTMLBody()
            ? strip_tags((string)$msg->getHTMLBody())
            : (string)($msg->getTextBody() ?? '');

        $origText = html_entity_decode($origText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $origText = preg_replace('/\s+/u', ' ', trim($origText));

        $body = ($data['note'] ? "Nota: ".$data['note']."\n\n" : '')
              . $origText;

        $to = SimpleMail::splitEmails($data['to']);

        $rawSent = null;
        $mailable = new SimpleMail($to, $subject, $body);

        // Adjuntar archivos originales
        try {
            foreach ($msg->getAttachments() as $att) {
                $mailable->attachData(
                    $att->getContent(),
                    $att->getName() ?: ('adjunto-'.Str::random(6)),
                    ['mime' => $att->getMimeType() ?: 'application/octet-stream']
                );
            }
        } catch (\Throwable $e) {}

        $mailable->withSymfonyMessage(function ($symfonyEmail) use (&$rawSent) {
            if (is_object($symfonyEmail) && method_exists($symfonyEmail, 'toString')) {
                $rawSent = $symfonyEmail->toString();
            }
        });

        MailFacade::send($mailable);

        if ($rawSent) {
            $this->mailbox->appendToSentIfPossible($rawSent);
        }

        return back()->with('ok','Reenviado ✔️');
    }

    /* =========================================================
     |                          ACCIONES
     * =======================================================*/

    public function toggleFlag(Request $r, string $folder, string $uid)
    {
        $ok = $this->mailbox->toggleFlag(strtoupper($folder), (string)$uid);
        return $ok ? response()->noContent() : response()->json(['ok'=>false], 500);
    }

    public function markRead(Request $r, string $folder, string $uid)
    {
        $ok = $this->mailbox->markRead(strtoupper($folder), (string)$uid);
        return $ok ? response()->noContent() : response()->json(['ok'=>false], 500);
    }

    public function move(Request $r, string $folder, string $uid)
    {
        $data = $r->validate(['dest'=>['required','string']]);
        $ok = $this->mailbox->move(strtoupper($folder), (string)$uid, strtoupper((string)$data['dest']));
        return response()->json(['ok'=>$ok]);
    }

    public function delete(Request $r, string $folder, string $uid)
    {
        return response()->json($this->mailbox->delete(strtoupper($folder), (string)$uid));
    }

    /* =========================================================
     |                          SHOW + ADJUNTOS
     * =======================================================*/

    public function show(Request $r, string $folder, string $uid)
    {
        $folder = strtoupper($folder);
        $msg = $this->mailbox->getMessage($folder, (string)$uid);
        abort_unless($msg, 404);

        // Siempre formatear "when" acá para evitar strings raros
        $when = $this->mailbox->formatMessageWhen($msg);

        if ($r->get('partial') == '1') {
            $from = $this->mailbox->messageFrom($msg);
            $to   = $this->mailbox->messageTo($msg);
            $cc   = $this->mailbox->messageCc($msg);
            $subject = $this->mailbox->decodeHeader((string)($msg->getSubject() ?: '(sin asunto)'));

            $html = $msg->hasHTMLBody() ? (string)$msg->getHTMLBody() : null;
            $text = (string)($msg->getTextBody() ?? '');

            $attachments = [];
            try { $attachments = $msg->getAttachments() ?? []; } catch (\Throwable $e) {}

            return response()->view('mail.partials.show', compact(
                'folder','uid','from','to','cc','subject','when','html','text','attachments'
            ));
        }

        $attachments = [];
        try { $attachments = $msg->getAttachments() ?? []; } catch (\Throwable $e) {}

        return view('mail.show', compact('msg','folder','uid','attachments'));
    }

    public function downloadAttachment(Request $r, string $folder, string $uid, string $part)
    {
        $out = $this->mailbox->downloadAttachment(strtoupper($folder), (string)$uid, (string)$part);
        abort_unless($out, 404);

        return response($out['content'], 200, [
            'Content-Type'        => $out['mime'],
            'Content-Disposition' => 'attachment; filename="'.$out['name'].'"',
        ]);
    }

    /* =========================================================
     |                          APIS
     * =======================================================*/

    protected function clampLimit(int $n): int
    {
        return max(self::LIMIT_MIN, min(self::LIMIT_MAX, $n));
    }

    public function apiMessages(Request $r)
    {
        $folder = strtoupper((string)$r->get('folder', env('IMAP_DEFAULT_FOLDER','INBOX')));
        $limit  = $this->clampLimit((int)$r->get('limit', 80));

        $payload = $this->mailbox->apiList($folder, [
            'limit'                 => $limit,
            'after_uid'             => (int)$r->get('after_uid', 0),
            'q'                     => (string)$r->get('q', ''),
            'only_with_attachments' => (bool)$r->boolean('only_with_attachments', false),
            'priority_only'         => (bool)$r->boolean('priority_only', false),
            'unseen_only'           => (bool)$r->boolean('unseen_only', false),
        ]);

        return response()->json($payload);
    }

    public function apiWait(Request $r)
    {
        $folder  = strtoupper((string)$r->get('folder', env('IMAP_DEFAULT_FOLDER','INBOX')));
        $after   = (int)$r->get('after_uid', 0);

        $timeout = max(5, min(self::WAIT_TIMEOUT_MAX, (int)$r->get('timeout', 25)));
        $tick    = max(1, min(self::WAIT_TICK_MAX,  (int)$r->get('tick', 3)));
        $limit   = $this->clampLimit((int)$r->get('limit', 120));

        $q       = (string)$r->get('q', '');
        $onlyAtt = (bool)$r->boolean('only_with_attachments', false);
        $prio    = (bool)$r->boolean('priority_only', false);

        $start = microtime(true);
        $items = [];

        do {
            $resp = $this->mailbox->apiList($folder, [
                'limit'                 => $limit,
                'after_uid'             => $after,
                'q'                     => $q,
                'only_with_attachments' => $onlyAtt,
                'priority_only'         => $prio,
            ]);

            if (!empty($resp['items'])) {
                return response()->json($resp);
            }

            usleep($tick * 250000); // 0.25s * tick
        } while ((microtime(true) - $start) < $timeout);

        return response()->json([
            'ok'      => true,
            'folder'  => $folder,
            'count'   => 0,
            'max_uid' => $after,
            'items'   => [],
        ]);
    }

    public function apiCounts(Request $r)
    {
        return response()->json($this->mailbox->counts());
    }

    public function health(Request $r)
    {
        return response()->json(['ok'=>$this->mailbox->health(), 'ts'=>now()->toIso8601String()]);
    }
}

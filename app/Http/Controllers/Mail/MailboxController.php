<?php

namespace App\Http\Controllers\Mail;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail as MailFacade;
use Illuminate\Support\Str;
use App\Mail\SimpleMail;
use App\Services\MailboxService;

class MailboxController extends Controller
{
    protected const LIMIT_MIN = 10;
    protected const LIMIT_MAX = 200;

    protected const WAIT_TIMEOUT_MAX = 25;
    protected const WAIT_TICK_MAX    = 5;

    public function __construct(protected MailboxService $mailbox) {}

    public function index(Request $r)
    {
        $folder = strtoupper((string)$r->get('folder', env('IMAP_DEFAULT_FOLDER','INBOX')));

        // Solo counts + la vista ya se llena con API (tu blade actual)
        return view('mail.index', [
            'counts'  => $this->mailbox->counts(),
            'current' => $folder,
            'messages'=> [], // tu blade puede ignorarlo porque recarga por API
        ]);
    }

    public function folder(Request $r, string $name)
    {
        return redirect()->route('mail.index', ['folder' => strtoupper($name)]);
    }

    /* =========================
     |  SHOW (partial que tu JS necesita)
     ========================= */

    public function show(Request $r, string $folder, string $uid)
    {
        $folder = strtoupper($folder);
        $msg = $this->mailbox->getMessage($folder, (string)$uid);
        abort_unless($msg, 404);

        if ($r->get('partial') == '1') {
            $from    = $this->mailbox->decodeHeader(optional($msg->getFrom())->first()?->personal ?: optional($msg->getFrom())->first()?->mail ?: '(desconocido)');
            $to      = (string)(optional($msg->getTo())->first()?->mail ?? '');
            $cc      = (string)(optional($msg->getCc())->first()?->mail ?? '');
            $subject = $this->mailbox->decodeHeader((string)($msg->getSubject() ?: '(sin asunto)'));
            $when    = $this->mailbox->formatWhen($msg);

            $html = $msg->hasHTMLBody() ? (string)$msg->getHTMLBody() : null;
            $text = (string)($msg->getTextBody() ?? '');

            $attachments = [];
            try { $attachments = $msg->getAttachments() ?? []; } catch (\Throwable $e) {}

            // 👇 ESTE ES EL PARCIAL QUE TU JS ESPERA (#mx-payload)
            return response()->view('mail.partials.show', compact(
                'folder','uid','from','to','cc','subject','when','html','text','attachments'
            ));
        }

        // Si abres sin partial, puedes dejarlo como una vista normal
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

    /* =========================
     |  Envío + APPEND a Enviados
     ========================= */

    public function compose()
    {
        return view('mail.compose');
    }

    public function send(Request $r)
    {
        $data = $r->validate([
            'to'      => ['required','string'],
            'subject' => ['required','string','max:200'],
            'body'    => ['required','string'],
            'cc'      => ['nullable','string'],
            'bcc'     => ['nullable','string'],
            'files.*' => ['nullable','file','max:15360'],
        ]);

        $to    = SimpleMail::splitEmails($data['to']);
        $cc    = isset($data['cc'])  ? SimpleMail::splitEmails($data['cc'])  : [];
        $bcc   = isset($data['bcc']) ? SimpleMail::splitEmails($data['bcc']) : [];
        $files = $r->file('files', []);

        $rawSent = null;

        $mailable = new SimpleMail($to, $data['subject'], $data['body'], $cc, $bcc, $files);

        $mailable->withSymfonyMessage(function ($symfonyEmail) use (&$rawSent) {
            if (is_object($symfonyEmail) && method_exists($symfonyEmail, 'toString')) {
                $rawSent = $symfonyEmail->toString();
            }
        });

        MailFacade::send($mailable);

        if ($rawSent) {
            $this->mailbox->appendToSentIfPossible($rawSent);
        }

        return back()->with('ok', 'Correo enviado correctamente.');
    }

    public function reply(Request $r, string $folder, string $uid)
    {
        $data = $r->validate(['body'=>['required','string']]);

        $msg = $this->mailbox->getMessage(strtoupper($folder), (string)$uid);
        abort_unless($msg, 404);

        $to = optional($msg->getReplyTo())->first()?->mail
           ?? optional($msg->getFrom())->first()?->mail;

        $subject = 'Re: ' . $this->mailbox->decodeHeader((string)($msg->getSubject() ?: '(sin asunto)'));

        $rawSent = null;
        $mailable = new SimpleMail([(string)$to], $subject, $data['body']);

        $mailable->withSymfonyMessage(function ($symfonyEmail) use (&$rawSent, $msg) {
            // Thread headers
            try {
                if (method_exists($symfonyEmail, 'getHeaders')) {
                    $headers = $symfonyEmail->getHeaders();
                    $mid = (string)($msg->getMessageId() ?: '');
                    if ($mid) $headers->addTextHeader('In-Reply-To', $mid);

                    $refsHeader = optional($msg->get('References'))->first();
                    $refsVal = $refsHeader && method_exists($refsHeader,'getValue') ? (string)$refsHeader->getValue() : '';
                    $refs = trim($refsVal.' '.$mid);
                    if ($refs) $headers->addTextHeader('References', $refs);
                }
            } catch (\Throwable $e) {}

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
        abort_unless($msg, 404);

        $subject = 'Fwd: ' . $this->mailbox->decodeHeader((string)($msg->getSubject() ?: '(sin asunto)'));

        $origText = $msg->hasHTMLBody()
            ? strip_tags((string)$msg->getHTMLBody())
            : (string)($msg->getTextBody() ?? '');

        $body = ($data['note'] ? "Nota: ".$data['note']."\n\n" : '')
              . trim(preg_replace('/\s+/u', ' ', html_entity_decode($origText, ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        $to = SimpleMail::splitEmails($data['to']);

        $rawSent = null;
        $mailable = new SimpleMail($to, $subject, $body);

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

    /* =========================
     |  Acciones rápidas
     ========================= */

    public function toggleFlag(Request $r, string $folder, string $uid)
    {
        return $this->mailbox->toggleFlag(strtoupper($folder), (string)$uid)
            ? response()->noContent()
            : response()->json(['ok'=>false], 500);
    }

    public function markRead(Request $r, string $folder, string $uid)
    {
        return $this->mailbox->markRead(strtoupper($folder), (string)$uid)
            ? response()->noContent()
            : response()->json(['ok'=>false], 500);
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

    /* =========================
     |  APIs
     ========================= */

    protected function clampLimit(int $n): int
    {
        return max(self::LIMIT_MIN, min(self::LIMIT_MAX, $n));
    }

    public function apiMessages(Request $r)
    {
        $folder = strtoupper((string)$r->get('folder', env('IMAP_DEFAULT_FOLDER','INBOX')));
        $limit  = $this->clampLimit((int)$r->get('limit', 80));

        return response()->json(
            $this->mailbox->apiList($folder, [
                'limit'                 => $limit,
                'after_uid'             => (int)$r->get('after_uid', 0),
                'q'                     => (string)$r->get('q', ''),
                'only_with_attachments' => (bool)$r->boolean('only_with_attachments', false),
                'priority_only'         => (bool)$r->boolean('priority_only', false),
                'unseen_only'           => (bool)$r->boolean('unseen_only', false),
            ])
        );
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

        do {
            $resp = $this->mailbox->apiList($folder, [
                'limit'                 => $limit,
                'after_uid'             => $after,
                'q'                     => $q,
                'only_with_attachments' => $onlyAtt,
                'priority_only'         => $prio,
            ]);

            if (!empty($resp['items'])) return response()->json($resp);

            usleep($tick * 250000);
        } while ((microtime(true) - $start) < $timeout);

        return response()->json([
            'ok'=>true,'folder'=>$folder,'count'=>0,'max_uid'=>$after,'items'=>[]
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

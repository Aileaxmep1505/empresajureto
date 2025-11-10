<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SimpleMail extends Mailable /* implements ShouldQueue */ // opcional: colócala si quieres encolar
{
    use Queueable, SerializesModels;

    /** NO sobrescribas $to/$cc/$bcc del padre. Usa nombres distintos. */
    protected array $xTo = [];
    protected array $xCc = [];
    protected array $xBcc = [];
    protected string $xSubject;
    protected string $xBody;

    /** @var UploadedFile[] */
    protected array $xFiles = [];

    /**
     * @param string|array $to  lista o string coma-separado
     * @param string       $subject
     * @param string       $body  (texto plano; en la vista se imprime con nl2br)
     * @param array        $cc
     * @param array        $bcc
     * @param UploadedFile[] $files
     */
    public function __construct(
        string|array $to,
        string $subject,
        string $body,
        array $cc = [],
        array $bcc = [],
        array $files = []
    ) {
        $this->xTo = is_array($to) ? $to : self::splitEmails($to);
        $this->xCc = $cc;
        $this->xBcc = $bcc;
        $this->xSubject = $subject;
        $this->xBody = $body;
        $this->xFiles = $files;
    }

    /** ---- Laravel 10/11+ */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->xSubject,
            to: collect($this->xTo)->filter()->map(fn($e) => new Address(trim($e)))->all(),
            cc: collect($this->xCc)->filter()->map(fn($e) => new Address(trim($e)))->all(),
            bcc: collect($this->xBcc)->filter()->map(fn($e) => new Address(trim($e)))->all(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.simple', // crea resources/views/mail/simple.blade.php (abajo)
            with: ['body' => $this->xBody],
        );
    }

    /** Adjuntos (Laravel 10/11+) */
    public function attachments(): array
    {
        return collect($this->xFiles)
            ->filter(fn($f) => $f instanceof UploadedFile && $f->isValid())
            ->map(fn(UploadedFile $f) =>
                Attachment::fromPath($f->getRealPath())
                    ->as($f->getClientOriginalName())
                    ->withMime($f->getClientMimeType() ?: 'application/octet-stream')
            )->all();
    }

    /** ---- Respaldo para proyectos antiguos (Laravel 8/9): se ignora si usas envelope/content */
    public function build()
    {
        $mail = $this->subject($this->xSubject)
            ->view('mail.simple', ['body' => $this->xBody]);

        if (!empty($this->xTo))  { $mail->to($this->xTo); }
        if (!empty($this->xCc))  { $mail->cc($this->xCc); }
        if (!empty($this->xBcc)) { $mail->bcc($this->xBcc); }

        foreach ($this->xFiles as $f) {
            if ($f instanceof UploadedFile && $f->isValid()) {
                $mail->attach($f->getRealPath(), [
                    'as'   => $f->getClientOriginalName(),
                    'mime' => $f->getClientMimeType() ?: 'application/octet-stream',
                ]);
            }
        }

        return $mail;
    }

    /** Utilidad: separar "a@a.com, b@b.com" en array */
    public static function splitEmails(string $s): array
    {
        return collect(explode(',', $s))
            ->map(fn($e) => trim($e))
            ->filter()
            ->values()
            ->all();
    }
}

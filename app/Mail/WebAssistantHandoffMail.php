<?php
namespace App\Mail;
use App\Models\WebAssistantConversation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
class WebAssistantHandoffMail extends Mailable
{
 use Queueable, SerializesModels;
 public function __construct(
 public WebAssistantConversation $conversation
 ) {
 }
 public function build()
 {
 return $this
 ->subject('Un cliente solicita atención de un asesor')
 ->view('emails.web-assistant-handoff');
 }
}
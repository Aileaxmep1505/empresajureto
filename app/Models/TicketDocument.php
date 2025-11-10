<?php
// app/Models/TicketDocument.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TicketDocument extends Model {
  protected $fillable = ['ticket_id','uploaded_by','stage_id','category','name','path','external_url','version','meta'];
  protected $casts = ['version'=>'integer','meta'=>'array'];
  public function ticket(){ return $this->belongsTo(Ticket::class); }
  public function stage(){ return $this->belongsTo(TicketStage::class,'stage_id'); }
  public function uploader(){ return $this->belongsTo(User::class,'uploaded_by'); }
}

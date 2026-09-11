<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SettingDocument extends Model {
    use \App\Traits\LogsModelActivity;
 protected $fillable=['user_id','section','document_key','type','name','description','path','original_name','mime_type','size_bytes','version','expires_at','validation_status']; protected $casts=['expires_at'=>'date']; }

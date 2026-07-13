<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SettingCertification extends Model { protected $fillable=['user_id','name','issuer','folio','issued_at','expires_at','support_path','support_original_name']; protected $casts=['issued_at'=>'date','expires_at'=>'date']; }

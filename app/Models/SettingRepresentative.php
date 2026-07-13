<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SettingRepresentative extends Model { protected $fillable=['user_id','name','position','identification_path','identification_original_name','power_path','power_original_name']; }

<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class UserSettingProfile extends Model {
    use \App\Traits\LogsModelActivity;
 protected $fillable=['user_id','first_name','last_name','whatsapp','two_factor_enabled']; protected $casts=['two_factor_enabled'=>'boolean']; }

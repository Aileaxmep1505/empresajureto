<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Organization extends Model { protected $fillable=['user_id','country','organization_type','tax_id','legal_name','trade_name','institutional_email','institutional_phone','website','legal_country','legal_state','postal_code','city','legal_address']; }

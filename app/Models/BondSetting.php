<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class BondSetting extends Model { protected $fillable=['user_id','financial_statements_audited','has_solidary_debtor','solidary_business_name','solidary_tax_id','solidary_representative','solidary_phone','has_real_estate_guarantee','property_type','property_value','property_address']; protected $casts=['financial_statements_audited'=>'boolean','has_solidary_debtor'=>'boolean','has_real_estate_guarantee'=>'boolean','property_value'=>'decimal:2']; }

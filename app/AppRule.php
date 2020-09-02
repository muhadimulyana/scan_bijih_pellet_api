<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AppRule extends Model
{
    protected $table = 'android_data.app_rule';
    //protected $primaryKey = ['user', 'package_name', 'page_name', 'rule_name'];
    public $timestamps = false;
    protected $fillable = ['user', 'package_name', 'page_name', 'rule_name', 'created_at'];
}

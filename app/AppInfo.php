<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AppInfo extends Model
{
    protected $table = 'android_data.app_info';
    public $timestamps = false;
    protected $fillable = ['package_name', 'app_name', 'version',' link', 'message'];
}

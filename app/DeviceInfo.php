<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DeviceInfo extends Model
{
    protected $table = 'android_data.device_info';
    public $timestamps = false;
    protected $fillable = ['device_id', 'app_name', 'user', 'app_id', 'device_model', 'active', 'created_date'];
}

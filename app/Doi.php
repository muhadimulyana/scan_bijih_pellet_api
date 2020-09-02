<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Doi extends Model
{
    protected $table = 'erasystem_2012.doi_pellet';
    public $timestamps = false; 
    protected $fillable = ['ID_DO', 'ID_SO', 'ID_JADWAL', 'TANGGAL_BUAT', 'TANGGAL', 'NOPOL', 'STATUS', 'USERNAME', 'TOTAL', 'KETERANGAN,' , 'LAST_UPDATE'];
}

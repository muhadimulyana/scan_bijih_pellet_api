<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Bst extends Model
{
    protected $table = 'erasystem_2012.bst_pellet';
    public $timestamps = false; 
    protected $fillable = ['NO_BST', 'TANGGAL_BUAT', 'TANGGAL', 'PT_ID', 'PT_NAMA', 'GUDANG', 'DARI_DEPT_ID', 'DARI_DEPT_NAMA', 'KE_DEPT_ID', 'KE_DEPT_NAMA', 'DARI_DEPT_AREA', 'KE_DEPT_AREA', 'USERNAME', 'STATUS', 'TOTAL', 'KETERANGAN,' , 'LAST_UPDATE', 'NO_WO'];
}

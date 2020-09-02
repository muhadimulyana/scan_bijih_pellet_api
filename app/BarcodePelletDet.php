<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BarcodePelletDet extends Model
{
    protected $table = 'erasystem_2012.barcode_pellet_det';
    public $timestamps = false; //di false karena aotomatis laravel mengisi kolom created at
    protected $fillable = ['TANGGAL', 'BARCODE', 'PT_ID', 'PT_NAMA', 'GUDANG', 'DEPT_ID', 'DEPT_NAMA', 'NOTRANS', 'USERNAME', 'STATUS']; //berguna untuk mendaftarkan atribut (nama kolom) yang bisa kita isi ketika melakukan insert atau update ke database. 
}

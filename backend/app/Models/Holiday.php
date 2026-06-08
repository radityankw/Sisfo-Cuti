<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $table = 'holidays';
    protected $primaryKey = 'tgl';
    public $incrementing = false;
    protected $keyType = 'date';

    protected $fillable = [
        'tgl',
        'deskripsi',
    ];

    protected $casts = [
        'tgl' => 'date',
    ];
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    protected $table = 'leaves';
    
    protected $fillable = [
        'namaCuti',
        'kategori',
        'maxHari',
    ];

    public function requests()
    {
        return $this->hasMany(Request::class, 'leave_id');
    }
}
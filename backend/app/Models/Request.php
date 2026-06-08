<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    protected $table = 'requests';
    
    protected $fillable = [
        'user_nik',
        'leave_id',
        'tglRequest',
        'tglMulai',
        'tglSelesai',
        'alasan',
        'lampiran',
        'status',
        'manager_nik',
        'tglApproval',
        'catatanManager',
    ];

    protected $casts = [
        'tglRequest' => 'datetime',
        'tglMulai' => 'datetime',
        'tglSelesai' => 'datetime',
        'tglApproval' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_nik', 'nik');
    }

    public function leave()
    {
        return $this->belongsTo(Leave::class, 'leave_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'manager_nik', 'nik');
    }
}
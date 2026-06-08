<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens; 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'nik';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'nik', 'nama', 'tglMasuk', 'departemen', 'role', 'password', 'manager_nik'
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'tglMasuk' => 'datetime',
        'password' => 'hashed',
    ];

    public function getAuthIdentifierName()
    {
        return 'nik';
    }

    // Relasi ke Request Cuti
    public function requests()
    {
        return $this->hasMany(Request::class, 'user_nik', 'nik');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_nik', 'nik');
    }

    public function bawahan()
    {
        return $this->hasMany(User::class, 'manager_nik', 'nik');
    }
}
<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table         = 'users';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'pegawai_id',
        'nama',
        'email',
        'password',
        'must_change_password',
        'role',
        'is_active',
        'last_login'
    ];
    protected $useTimestamps = true;
    protected $hiddenFields  = ['password'];
}
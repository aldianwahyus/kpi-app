<?php
namespace App\Models;

use CodeIgniter\Model;

class DirektoratModel extends Model
{
    protected $table         = 'direktorat';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'kode', 'nama', 'singkatan',
        'deskripsi', 'is_active',
    ];
    protected $useTimestamps = true;

    public function getActive(): array
    {
        return $this->where('is_active', 1)
                    ->orderBy('nama', 'ASC')
                    ->findAll();
    }

    public function getDropdown(): array
    {
        $rows = $this->getActive();
        $result = [];
        foreach ($rows as $row) {
            $result[$row['id']] = $row['nama'];
        }
        return $result;
    }
}
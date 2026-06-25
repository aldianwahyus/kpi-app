<?php
namespace App\Models;

use CodeIgniter\Model;

class PeriodeModel extends Model
{
    protected $table         = 'periode';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'nama', 'kode', 'tgl_mulai',
        'tgl_selesai', 'status',
    ];
    protected $useTimestamps = true;

    // Ambil periode yang sedang aktif
    public function getAktif(): ?array
    {
        return $this->where('status', 'aktif')->first();
    }

    // Cek apakah sudah ada periode aktif lain
    public function hasAktif(?int $excludeId = null): bool
    {
        $builder = $this->where('status', 'aktif');
        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }
        return $builder->countAllResults() > 0;
    }

    // Ambil semua periode urut terbaru
    public function getAllOrdered(): array
    {
        return $this->orderBy('tgl_mulai', 'DESC')->findAll();
    }

    // Dropdown periode untuk filter
    public function getDropdown(): array
    {
        $rows = $this->getAllOrdered();
        $result = [];
        foreach ($rows as $row) {
            $result[$row['id']] = $row['nama'] . ' (' . ucfirst($row['status']) . ')';
        }
        return $result;
    }
}
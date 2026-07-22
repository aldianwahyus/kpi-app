<?php
namespace App\Models;

use CodeIgniter\Model;

class DivisiModel extends Model
{
    protected $table         = 'divisi';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'kode', 'nama', 'deskripsi', 'direktorat_id',
        'kepala_divisi', 'is_active',
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

    // Ambil divisi beserta nama direktorat
    public function getAllWithDirektorat(): array
    {
        return $this->db->table('divisi d')
            ->select('d.*, dir.nama as nama_direktorat,
                    dir.kode as kode_direktorat,
                    dir.id as direktorat_id_rel')
            ->join('direktorat dir', 'dir.id = d.direktorat_id', 'left')
            ->where('d.is_active', 1)
            ->orderBy('dir.nama', 'ASC')
            ->orderBy('d.nama', 'ASC')
            ->get()->getResultArray();
    }

    // Ambil dikelompokkan per direktorat
    public function getGroupedByDirektorat(): array
    {
        $rows = $this->getAllWithDirektorat();
        $grouped = [];
        foreach ($rows as $row) {
            $key = $row['nama_direktorat'] ?? 'Tanpa Direktorat';
            $grouped[$key][] = $row;
        }
        return $grouped;
    }

    // Dropdown dengan nama direktorat
    public function getDropdownWithDirektorat(): array
    {
        $rows = $this->getAllWithDirektorat();
        $result = [];
        foreach ($rows as $row) {
            $dir = $row['nama_direktorat'] ?? 'Tanpa Direktorat';
            $result[$dir][$row['id']] = $row['nama'];
        }
        return $result;
    }
}
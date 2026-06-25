<?php
namespace App\Models;

use CodeIgniter\Model;

class PegawaiModel extends Model
{
    protected $table         = 'pegawai';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'nip', 'nama', 'jabatan', 'unit',
        'divisi_id', 'golongan', 'tgl_masuk',
        'atasan_id', 'is_active',
    ];
    protected $useTimestamps = true;

    // Ambil semua pegawai beserta nama divisi
    public function getAllWithDivisi(): array
    {
        return $this->db->table('pegawai p')
            ->select('p.*, d.nama as nama_divisi, d.kode as kode_divisi,
                      a.nama as nama_atasan')
            ->join('divisi d', 'd.id = p.divisi_id', 'left')
            ->join('pegawai a', 'a.id = p.atasan_id', 'left')
            ->orderBy('d.nama', 'ASC')
            ->orderBy('p.nama', 'ASC')
            ->get()->getResultArray();
    }

    // Ambil satu pegawai beserta divisi
    public function getWithDivisi(int $id): ?array
    {
        return $this->db->table('pegawai p')
            ->select('p.*, d.nama as nama_divisi')
            ->join('divisi d', 'd.id = p.divisi_id', 'left')
            ->where('p.id', $id)
            ->get()->getRowArray();
    }

    // Dropdown pegawai untuk pilihan atasan
    public function getDropdown(?int $excludeId = null): array
    {
        $builder = $this->where('is_active', 1)
                        ->orderBy('nama', 'ASC');
        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }
        $rows = $builder->findAll();
        $result = [];
        foreach ($rows as $row) {
            $result[$row['id']] = $row['nama'];
        }
        return $result;
    }

    // Cek NIP sudah ada
    public function isNipExists(string $nip, ?int $excludeId = null): bool
    {
        $builder = $this->where('nip', $nip);
        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }
        return $builder->countAllResults() > 0;
    }

    // Hitung total pegawai aktif per divisi
    public function countByDivisi(): array
    {
        return $this->db->table('pegawai p')
            ->select('d.nama as divisi, COUNT(p.id) as total')
            ->join('divisi d', 'd.id = p.divisi_id', 'left')
            ->where('p.is_active', 1)
            ->groupBy('p.divisi_id')
            ->get()->getResultArray();
    }
}
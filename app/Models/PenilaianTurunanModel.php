<?php
namespace App\Models;

use CodeIgniter\Model;

class PenilaianTurunanModel extends Model
{
    protected $table         = 'penilaian_turunan';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'penilaian_id', 'kpi_pegawai_turunan_id',
        'realisasi', 'skor', 'nilai_kontribusi', 'catatan',
    ];
    protected $useTimestamps = true;

    // Ambil seluruh realisasi Turunan untuk satu baris penilaian Induk
    public function getByPenilaian(int $penilaianId): array
    {
        return $this->db->table('penilaian_turunan pt')
            ->select('pt.*, kpt.nama_turunan, kpt.target, kpt.bobot, kpt.urutan')
            ->join('kpi_pegawai_turunan kpt', 'kpt.id = pt.kpi_pegawai_turunan_id')
            ->where('pt.penilaian_id', $penilaianId)
            ->orderBy('kpt.urutan', 'ASC')
            ->get()->getResultArray();
    }

    // Ambil sebagai array dengan kpi_pegawai_turunan_id sebagai key,
    // memudahkan pengisian ulang nilai pada form (mirip getIndexedByKpi
    // pada PenilaianModel).
    public function getIndexedByTurunan(int $penilaianId): array
    {
        $rows = $this->where('penilaian_id', $penilaianId)->findAll();
        $result = [];
        foreach ($rows as $row) {
            $result[$row['kpi_pegawai_turunan_id']] = $row;
        }
        return $result;
    }

    // Simpan atau update realisasi satu baris Turunan (upsert)
    public function upsert(int $penilaianId, int $turunanId, array $data): void
    {
        $existing = $this->where('penilaian_id', $penilaianId)
                         ->where('kpi_pegawai_turunan_id', $turunanId)
                         ->first();
        if ($existing) {
            $this->update($existing['id'], $data);
        } else {
            $this->insert(array_merge($data, [
                'penilaian_id'           => $penilaianId,
                'kpi_pegawai_turunan_id' => $turunanId,
            ]));
        }
    }

    // Total Realisasi seluruh Turunan untuk satu baris penilaian Induk —
    // inilah nilai yang dipakai sebagai Realisasi Induk (Realisasi Induk
    // = SUM seluruh Realisasi Turunan), sesuai mekanisme yang ditetapkan.
    public function getTotalRealisasi(int $penilaianId): float
    {
        $result = $this->where('penilaian_id', $penilaianId)
                       ->selectSum('realisasi')
                       ->get()->getRowArray();
        return round((float)($result['realisasi'] ?? 0), 4);
    }
}
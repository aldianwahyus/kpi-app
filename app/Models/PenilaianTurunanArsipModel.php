<?php

namespace App\Models;

use CodeIgniter\Model;

class PenilaianTurunanArsipModel extends Model
{
    protected $table         = 'penilaian_turunan_arsip';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'penilaian_arsip_id', 'nama_turunan', 'satuan',
        'polarity', 'perubahan_polarity', 'sifat_khusus',
        'toleransi_skor4', 'toleransi_skor3', 'toleransi_skor2',
        'bobot', 'target', 'realisasi', 'realisasi_harian',
        'skor', 'nilai_kontribusi', 'catatan', 'urutan',
    ];
    protected $useTimestamps = false;

    public function getByPenilaianArsip(int $penilaianArsipId): array
    {
        return $this->where('penilaian_arsip_id', $penilaianArsipId)
                    ->orderBy('urutan', 'ASC')
                    ->orderBy('id', 'ASC')
                    ->findAll();
    }

    // Ambil seluruh Turunan untuk banyak baris penilaian_arsip sekaligus
    // (dikelompokkan per penilaian_arsip_id) — dipakai saat export bulk
    // supaya tidak query satu-per-satu per baris Induk.
    public function getGroupedByPenilaianArsipIds(array $penilaianArsipIds): array
    {
        if (empty($penilaianArsipIds)) return [];

        $rows = $this->whereIn('penilaian_arsip_id', $penilaianArsipIds)
                    ->orderBy('urutan', 'ASC')
                    ->orderBy('id', 'ASC')
                    ->findAll();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['penilaian_arsip_id']][] = $row;
        }
        return $grouped;
    }
}

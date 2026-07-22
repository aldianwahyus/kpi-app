<?php

namespace App\Models;

use CodeIgniter\Model;

class PenilaianArsipModel extends Model
{
    protected $table         = 'penilaian_arsip';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'periode_id', 'periode_nama', 'periode_kode', 'penilaian_id',
        'pegawai_id', 'pegawai_nama', 'pegawai_nip', 'pegawai_jabatan',
        'divisi_id', 'divisi_nama', 'direktorat_nama',
        'kpi_id', 'kpi_kode', 'kpi_nama', 'kpi_satuan', 'kpi_perspektif',
        'polarity', 'perubahan_polarity', 'sifat_khusus',
        'toleransi_skor4', 'toleransi_skor3', 'toleransi_skor2',
        'bobot', 'target', 'realisasi', 'realisasi_harian',
        'skor', 'nilai_kontribusi', 'catatan',
        'status', 'submitted_at', 'approved_by_nama', 'approved_at', 'reject_note',
        'input_by_nama', 'arsip_dibuat_oleh',
    ];
    protected $useTimestamps = false; // 'created_at' diisi manual sebagai waktu arsip dibuat

    public function hasArsip(int $periodeId): bool
    {
        return $this->where('periode_id', $periodeId)->countAllResults() > 0;
    }

    public function hapusByPeriode(int $periodeId): void
    {
        $this->where('periode_id', $periodeId)->delete();
    }

    // Rekap ranking (Nilai Akhir + Grade) per pegawai untuk satu periode
    // yang sudah diarsipkan — dihitung dari data BEKU (bukan live join),
    // memakai pola SUM yang sama dengan PenilaianModel::getRekapKombinasi()
    // supaya hasilnya konsisten dengan bagaimana Nilai Akhir selalu dihitung
    // di seluruh aplikasi (SUM nilai_kontribusi, skala 1.00-4.00).
    public function getRekapPeriode(int $periodeId, ?int $divisiId = null): array
    {
        $builder = $this->db->table('penilaian_arsip pa')
            ->select('pa.pegawai_id, pa.pegawai_nama as nama, pa.pegawai_jabatan as jabatan,
                      pa.divisi_id, pa.divisi_nama as divisi, pa.direktorat_nama as direktorat,
                      SUM(pa.nilai_kontribusi) as nilai_akhir, COUNT(pa.id) as jumlah_kpi')
            ->where('pa.periode_id', $periodeId);

        if ($divisiId !== null) {
            $builder->where('pa.divisi_id', $divisiId);
        }

        return $builder->groupBy('pa.pegawai_id')
                       ->orderBy('nilai_akhir', 'DESC')
                       ->get()->getResultArray();
    }

    // Detail seluruh baris KPI (Induk) untuk satu pegawai pada satu
    // periode yang sudah diarsipkan, dikelompokkan per perspektif —
    // dipakai untuk halaman detail & export.
    public function getDetailPegawai(int $periodeId, int $pegawaiId): array
    {
        return $this->where('periode_id', $periodeId)
                    ->where('pegawai_id', $pegawaiId)
                    ->orderBy('kpi_perspektif', 'ASC')
                    ->orderBy('id', 'ASC')
                    ->findAll();
    }

    // Seluruh baris KPI untuk seluruh pegawai pada satu periode — dipakai
    // untuk export bulk (Excel/PDF) supaya tidak query per-pegawai berulang.
    public function getAllDetailPeriode(int $periodeId): array
    {
        return $this->where('periode_id', $periodeId)
                    ->orderBy('pegawai_nama', 'ASC')
                    ->orderBy('kpi_perspektif', 'ASC')
                    ->orderBy('id', 'ASC')
                    ->findAll();
    }
}

<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Master Target — Target KPI Induk per bulan, per tahun (12 baris/tahun).
 * Target Periode Triwulan/Semester/Tahunan dihitung sebagai rata-rata
 * bulan-bulan terkait, bukan diinput ulang manual per Periode.
 */
class KpiPegawaiTargetBulananModel extends Model
{
    protected $table         = 'kpi_pegawai_target_bulanan';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['kpi_pegawai_id', 'tahun', 'bulan', 'target'];
    protected $useTimestamps = true;

    public function getByRefTahunBulan(int $kpiPegawaiId, int $tahun, int $bulan): ?array
    {
        return $this->where('kpi_pegawai_id', $kpiPegawaiId)
                    ->where('tahun', $tahun)
                    ->where('bulan', $bulan)
                    ->first();
    }

    public function upsert(int $kpiPegawaiId, int $tahun, int $bulan, ?float $target): void
    {
        $existing = $this->getByRefTahunBulan($kpiPegawaiId, $tahun, $bulan);
        if ($existing) {
            $this->update($existing['id'], ['target' => $target]);
        } else {
            $this->insert([
                'kpi_pegawai_id' => $kpiPegawaiId,
                'tahun'          => $tahun,
                'bulan'          => $bulan,
                'target'         => $target,
            ]);
        }
    }

    /**
     * Ambil seluruh baris Target Bulanan untuk sekumpulan kpi_pegawai_id,
     * pada daftar tahun tertentu — diindeks per kpi_pegawai_id lalu per
     * "tahun-bulan", dipakai resolver KpiPegawaiModel::getByPegawaiUntukPeriode()
     * untuk menghitung Target efektif suatu Periode.
     */
    public function getIndexedByRefAndTahunList(array $kpiPegawaiIds, array $tahunList): array
    {
        if (empty($kpiPegawaiIds) || empty($tahunList)) {
            return [];
        }

        $rows = $this->whereIn('kpi_pegawai_id', $kpiPegawaiIds)
                     ->whereIn('tahun', $tahunList)
                     ->findAll();

        $indexed = [];
        foreach ($rows as $r) {
            $indexed[$r['kpi_pegawai_id']][$r['tahun'] . '-' . $r['bulan']] = $r['target'];
        }
        return $indexed;
    }

    /** Ambil 12 baris (bulan 1-12) satu KPI untuk satu tahun, diindeks per bulan. */
    public function getTahunPenuh(int $kpiPegawaiId, int $tahun): array
    {
        $rows = $this->where('kpi_pegawai_id', $kpiPegawaiId)
                     ->where('tahun', $tahun)
                     ->findAll();
        $indexed = [];
        foreach ($rows as $r) {
            $indexed[(int)$r['bulan']] = $r['target'];
        }
        return $indexed;
    }
}

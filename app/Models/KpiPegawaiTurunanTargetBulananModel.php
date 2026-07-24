<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Sama seperti KpiPegawaiTargetBulananModel, untuk Parameter Turunan.
 */
class KpiPegawaiTurunanTargetBulananModel extends Model
{
    protected $table         = 'kpi_pegawai_turunan_target_bulanan';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['kpi_pegawai_turunan_id', 'tahun', 'bulan', 'target'];
    protected $useTimestamps = true;

    public function getByRefTahunBulan(int $turunanId, int $tahun, int $bulan): ?array
    {
        return $this->where('kpi_pegawai_turunan_id', $turunanId)
                    ->where('tahun', $tahun)
                    ->where('bulan', $bulan)
                    ->first();
    }

    public function upsert(int $turunanId, int $tahun, int $bulan, ?float $target): void
    {
        $existing = $this->getByRefTahunBulan($turunanId, $tahun, $bulan);
        if ($existing) {
            $this->update($existing['id'], ['target' => $target]);
        } else {
            $this->insert([
                'kpi_pegawai_turunan_id' => $turunanId,
                'tahun'                  => $tahun,
                'bulan'                  => $bulan,
                'target'                 => $target,
            ]);
        }
    }

    public function getIndexedByRefAndTahunList(array $turunanIds, array $tahunList): array
    {
        if (empty($turunanIds) || empty($tahunList)) {
            return [];
        }

        $rows = $this->whereIn('kpi_pegawai_turunan_id', $turunanIds)
                     ->whereIn('tahun', $tahunList)
                     ->findAll();

        $indexed = [];
        foreach ($rows as $r) {
            $indexed[$r['kpi_pegawai_turunan_id']][$r['tahun'] . '-' . $r['bulan']] = $r['target'];
        }
        return $indexed;
    }

    public function getTahunPenuh(int $turunanId, int $tahun): array
    {
        $rows = $this->where('kpi_pegawai_turunan_id', $turunanId)
                     ->where('tahun', $tahun)
                     ->findAll();
        $indexed = [];
        foreach ($rows as $r) {
            $indexed[(int)$r['bulan']] = $r['target'];
        }
        return $indexed;
    }
}

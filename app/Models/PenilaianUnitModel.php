<?php
namespace App\Models;

use CodeIgniter\Model;

class PenilaianUnitModel extends Model
{
    protected $table         = 'penilaian_unit';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'divisi_id', 'kpi_unit_id', 'periode_id',
        'target', 'realisasi', 'capaian',
        'catatan', 'input_by',
        'verified_by', 'verified_at',
    ];
    protected $useTimestamps = true;

    // Ambil penilaian unit per divisi per periode
    public function getByDivisiPeriode(int $divisiId, int $periodeId): array
    {
        return $this->where('divisi_id', $divisiId)
                    ->where('periode_id', $periodeId)
                    ->findAll();
    }

    // Ambil sebagai array dengan kpi_unit_id sebagai key
    public function getIndexedByKpi(int $divisiId, int $periodeId): array
    {
        $rows = $this->getByDivisiPeriode($divisiId, $periodeId);
        $result = [];
        foreach ($rows as $row) {
            $result[$row['kpi_unit_id']] = $row;
        }
        return $result;
    }

    // Upsert — tanpa nilai_kontribusi
    public function upsert(int $divisiId, int $kpiUnitId, int $periodeId, array $data): void
    {
        $existing = $this->where('divisi_id', $divisiId)
                         ->where('kpi_unit_id', $kpiUnitId)
                         ->where('periode_id', $periodeId)
                         ->first();
        if ($existing) {
            $this->update($existing['id'], $data);
        } else {
            $this->insert(array_merge($data, [
                'divisi_id'   => $divisiId,
                'kpi_unit_id' => $kpiUnitId,
                'periode_id'  => $periodeId,
            ]));
        }
    }

    // Rata-rata capaian KPI Unit satu divisi (0-100)
    public function getRataCapaian(int $divisiId, int $periodeId): float
    {
        $result = $this->db->table('penilaian_unit')
            ->selectAvg('capaian')
            ->where('divisi_id', $divisiId)
            ->where('periode_id', $periodeId)
            ->get()->getRowArray();

        return round((float)($result['capaian'] ?? 0) * 100, 2);
    }

    // Rekap semua divisi satu periode
    public function getRekapPeriode(int $periodeId): array
    {
        return $this->db->table('penilaian_unit pu')
            ->select('pu.divisi_id,
                      d.nama as nama_divisi,
                      d.kode as kode_divisi,
                      dir.nama as nama_direktorat,
                      AVG(pu.capaian) * 100 as rata_capaian,
                      COUNT(pu.id) as jumlah_kpi_diisi')
            ->join('divisi d', 'd.id = pu.divisi_id')
            ->join('direktorat dir', 'dir.id = d.direktorat_id', 'left')
            ->where('pu.periode_id', $periodeId)
            ->groupBy('pu.divisi_id')
            ->orderBy('rata_capaian', 'DESC')
            ->get()->getResultArray();
    }

    // Detail penilaian unit dengan nama KPI
    public function getDetailWithKpi(int $divisiId, int $periodeId): array
    {
        return $this->db->table('penilaian_unit pu')
            ->select('pu.*, k.nama_kpi, k.kode, k.satuan,
                      k.polarity, k.perspektif')
            ->join('kpi_unit k', 'k.id = pu.kpi_unit_id')
            ->where('pu.divisi_id', $divisiId)
            ->where('pu.periode_id', $periodeId)
            ->orderBy('k.perspektif', 'ASC')
            ->get()->getResultArray();
    }
}
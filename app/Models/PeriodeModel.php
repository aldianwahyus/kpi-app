<?php
namespace App\Models;

use CodeIgniter\Model;

class PeriodeModel extends Model
{
    protected $table         = 'periode';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'nama', 'kode', 'jenis', 'tgl_mulai',
        'tgl_selesai', 'status',
    ];
    protected $useTimestamps = true;

    public const JUMLAH_BULAN_PER_JENIS = [
        'bulanan'  => 1,
        'triwulan' => 3,
        'semester' => 6,
        'tahunan'  => 12,
    ];

    /**
     * Daftar (tahun, bulan) yang dicakup satu Periode, dihitung dari
     * tgl_mulai s.d. tgl_selesai (inklusif, per bulan kalender) — dipakai
     * resolver Master Target (KpiPegawaiModel::getByPegawaiUntukPeriode())
     * untuk menentukan bulan mana yang di-rata-rata sesuai Jenis Periode.
     */
    public function getBulanTahunList(array $periode): array
    {
        $cursor   = new \DateTime(date('Y-m-01', strtotime($periode['tgl_mulai'])));
        $akhir    = new \DateTime(date('Y-m-01', strtotime($periode['tgl_selesai'])));
        $list     = [];

        while ($cursor <= $akhir) {
            $list[] = ['tahun' => (int)$cursor->format('Y'), 'bulan' => (int)$cursor->format('n')];
            $cursor->modify('+1 month');
        }

        return $list;
    }

    /**
     * Validasi bahwa rentang tgl_mulai/tgl_selesai cocok dengan jumlah
     * bulan yang diharapkan untuk Jenis Periode yang dipilih (Bulanan=1,
     * Triwulan=3, Semester=6, Tahunan=12) — mencegah kesalahan input
     * tanggal yang akan membuat perhitungan rata-rata Target keliru.
     */
    public function jumlahBulanSesuaiJenis(array $periode): bool
    {
        $jumlahBulan = count($this->getBulanTahunList($periode));
        $ekspektasi  = self::JUMLAH_BULAN_PER_JENIS[$periode['jenis']] ?? null;
        return $ekspektasi !== null && $jumlahBulan === $ekspektasi;
    }

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
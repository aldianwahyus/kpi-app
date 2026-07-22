<?php

use App\Models\PenilaianModel;
use Tests\Support\KpiTestCase;

/**
 * @internal
 */
final class PenilaianWorkflowFeatureTest extends KpiTestCase
{
    private function setupPegawaiDenganKpi(string $polarity = 'max'): array
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Satu');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'TK-F1', $polarity, 100);
        $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);
        $this->makePeriodeAktif();

        return ['divisiId' => $divisiId, 'pegawaiId' => $pegawaiId, 'kpiUnitId' => $kpiUnitId];
    }

    public function testStoreTanpaPeriodeAktifDitolak(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Satu');
        // Sengaja TIDAK membuat periode aktif.

        $result = $this->withSession($this->sessionFor('drafter', 1, $pegawaiId))
            ->post("penilaian/store/{$pegawaiId}", [
                'realisasi' => [1 => 100],
            ]);

        $result->assertRedirect();
        $this->assertSame(0, (new PenilaianModel())->where('pegawai_id', $pegawaiId)->countAllResults());
    }

    public function testStoreMenyimpanRealisasiValidUntukPolarityMax(): void
    {
        $ctx = $this->setupPegawaiDenganKpi('max');

        $this->withSession($this->sessionFor('drafter', 1, $ctx['pegawaiId']))
            ->post("penilaian/store/{$ctx['pegawaiId']}", [
                'realisasi' => [$ctx['kpiUnitId'] => 120], // melebihi target 100
            ]);

        $row = (new PenilaianModel())
            ->where('pegawai_id', $ctx['pegawaiId'])
            ->where('kpi_id', $ctx['kpiUnitId'])
            ->first();

        $this->assertNotNull($row);
        $this->assertSame('draft', $row['status']);
        // realisasi 120 vs target 100 -> pencapaian 120% (>110%) -> band skor 4
        $this->assertEqualsWithDelta(4.0, (float) $row['skor'], 0.01);
    }

    // ── Regresi: realisasi = 0 untuk KPI polaritas 'min' adalah capaian
    // TERBAIK (skor maksimum), bukan dianggap "belum diisi".
    public function testStoreRealisasiNolUntukPolarityMinMendapatSkorMaksimum(): void
    {
        $ctx = $this->setupPegawaiDenganKpi('min');

        $this->withSession($this->sessionFor('drafter', 1, $ctx['pegawaiId']))
            ->post("penilaian/store/{$ctx['pegawaiId']}", [
                'realisasi' => [$ctx['kpiUnitId'] => 0],
            ]);

        $row = (new PenilaianModel())
            ->where('pegawai_id', $ctx['pegawaiId'])
            ->where('kpi_id', $ctx['kpiUnitId'])
            ->first();

        $this->assertNotNull($row, 'KPI dengan realisasi 0 (min-polarity) harus tetap tersimpan, bukan dilewati.');
        // realisasi 0 (min-polarity) -> band skor terbaik (4)
        $this->assertEqualsWithDelta(4.0, (float) $row['skor'], 0.01);
    }

    // ── Regresi: field realisasi yang benar-benar dikosongkan tetap
    // dilewati (tidak tersimpan) — beda dari kasus realisasi = 0 di atas.
    public function testStoreRealisasiKosongTidakTersimpan(): void
    {
        $ctx = $this->setupPegawaiDenganKpi('min');

        $this->withSession($this->sessionFor('drafter', 1, $ctx['pegawaiId']))
            ->post("penilaian/store/{$ctx['pegawaiId']}", [
                'realisasi' => [$ctx['kpiUnitId'] => ''],
            ]);

        $row = (new PenilaianModel())
            ->where('pegawai_id', $ctx['pegawaiId'])
            ->where('kpi_id', $ctx['kpiUnitId'])
            ->first();

        $this->assertNull($row, 'Field realisasi kosong seharusnya dilewati, bukan tersimpan sebagai 0.');
    }

    public function testSubmitMengubahStatusMenjadiSubmitted(): void
    {
        $ctx = $this->setupPegawaiDenganKpi('max');

        $this->withSession($this->sessionFor('drafter', 1, $ctx['pegawaiId']))
            ->post("penilaian/store/{$ctx['pegawaiId']}", ['realisasi' => [$ctx['kpiUnitId'] => 100]]);

        $result = $this->withSession($this->sessionFor('drafter', 1, $ctx['pegawaiId']))
            ->post("penilaian/submit/{$ctx['pegawaiId']}");

        $result->assertRedirect();
        $row = (new PenilaianModel())->where('pegawai_id', $ctx['pegawaiId'])->first();
        $this->assertSame('submitted', $row['status']);
    }

    public function testDrafterTidakBisaMengApprove(): void
    {
        $ctx = $this->setupPegawaiDenganKpi('max');

        $this->withSession($this->sessionFor('drafter', 1, $ctx['pegawaiId']))
            ->post("penilaian/store/{$ctx['pegawaiId']}", ['realisasi' => [$ctx['kpiUnitId'] => 100]]);
        $this->withSession($this->sessionFor('drafter', 1, $ctx['pegawaiId']))
            ->post("penilaian/submit/{$ctx['pegawaiId']}");

        $result = $this->withSession($this->sessionFor('drafter', 1, $ctx['pegawaiId']))
            ->post("penilaian/approve/{$ctx['pegawaiId']}");

        $result->assertRedirect();
        $row = (new PenilaianModel())->where('pegawai_id', $ctx['pegawaiId'])->first();
        $this->assertSame('submitted', $row['status'], 'Status tidak boleh berubah karena drafter tidak berwenang approve.');
    }

    public function testApproverBisaMengApproveSetelahSubmit(): void
    {
        $ctx = $this->setupPegawaiDenganKpi('max');

        $this->withSession($this->sessionFor('drafter', 1, $ctx['pegawaiId']))
            ->post("penilaian/store/{$ctx['pegawaiId']}", ['realisasi' => [$ctx['kpiUnitId'] => 100]]);
        $this->withSession($this->sessionFor('drafter', 1, $ctx['pegawaiId']))
            ->post("penilaian/submit/{$ctx['pegawaiId']}");

        $result = $this->withSession($this->sessionFor('approver', 2, $ctx['pegawaiId']))
            ->post("penilaian/approve/{$ctx['pegawaiId']}");

        $result->assertRedirect();
        $row = (new PenilaianModel())->where('pegawai_id', $ctx['pegawaiId'])->first();
        $this->assertSame('approved', $row['status']);
    }

    public function testRejectTanpaCatatanDitolak(): void
    {
        $ctx = $this->setupPegawaiDenganKpi('max');

        $this->withSession($this->sessionFor('drafter', 1, $ctx['pegawaiId']))
            ->post("penilaian/store/{$ctx['pegawaiId']}", ['realisasi' => [$ctx['kpiUnitId'] => 100]]);
        $this->withSession($this->sessionFor('drafter', 1, $ctx['pegawaiId']))
            ->post("penilaian/submit/{$ctx['pegawaiId']}");

        $result = $this->withSession($this->sessionFor('approver', 2, $ctx['pegawaiId']))
            ->post("penilaian/reject/{$ctx['pegawaiId']}", ['reject_note' => '']);

        $result->assertRedirect();
        $row = (new PenilaianModel())->where('pegawai_id', $ctx['pegawaiId'])->first();
        $this->assertSame('submitted', $row['status'], 'Status tidak boleh berubah tanpa catatan penolakan.');
    }

    public function testRejectDenganCatatanMengubahStatusMenjadiRejected(): void
    {
        $ctx = $this->setupPegawaiDenganKpi('max');

        $this->withSession($this->sessionFor('drafter', 1, $ctx['pegawaiId']))
            ->post("penilaian/store/{$ctx['pegawaiId']}", ['realisasi' => [$ctx['kpiUnitId'] => 100]]);
        $this->withSession($this->sessionFor('drafter', 1, $ctx['pegawaiId']))
            ->post("penilaian/submit/{$ctx['pegawaiId']}");

        $result = $this->withSession($this->sessionFor('approver', 2, $ctx['pegawaiId']))
            ->post("penilaian/reject/{$ctx['pegawaiId']}", ['reject_note' => 'Data belum lengkap.']);

        $result->assertRedirect();
        $row = (new PenilaianModel())->where('pegawai_id', $ctx['pegawaiId'])->first();
        $this->assertSame('rejected', $row['status']);
        $this->assertSame('Data belum lengkap.', $row['reject_note']);
    }

    // ── Kolom "Pencapaian" (live preview via ajaxHitung) ──

    public function testAjaxHitungMengembalikanPencapaianUntukPolarityMax(): void
    {
        $ctx = $this->setupPegawaiDenganKpi('max');

        $result = $this->withSession($this->sessionFor('drafter', 1, $ctx['pegawaiId']))
            ->post('penilaian/ajaxHitung', [
                'pegawai_id' => $ctx['pegawaiId'],
                'kpi_id'     => $ctx['kpiUnitId'],
                'realisasi'  => 97,
            ]);

        $json = json_decode($result->getJSON(), true);
        $this->assertTrue($json['valid']);
        $this->assertEqualsWithDelta(97.0, $json['pencapaian'], 0.01);
        $this->assertFalse($json['pencapaian_tak_terhingga']);
        $this->assertEqualsWithDelta(2.0, $json['skor'], 0.01);
        $this->assertEqualsWithDelta(2.0, $json['nilai'], 0.01); // Nilai = Skor
    }

    // ── Regresi: realisasi = 0 pada KPI 'min' -> Pencapaian tak terhingga.
    // json_encode() akan gagal/rusak jika field ini dikirim sebagai INF
    // mentah, jadi controller WAJIB mengirim null + flag terpisah.
    public function testAjaxHitungPencapaianTakTerhinggaUntukPolarityMinRealisasiNol(): void
    {
        $ctx = $this->setupPegawaiDenganKpi('min');

        $result = $this->withSession($this->sessionFor('drafter', 1, $ctx['pegawaiId']))
            ->post('penilaian/ajaxHitung', [
                'pegawai_id' => $ctx['pegawaiId'],
                'kpi_id'     => $ctx['kpiUnitId'],
                'realisasi'  => 0,
            ]);

        $json = json_decode($result->getJSON(), true);
        $this->assertTrue($json['valid']);
        $this->assertNull($json['pencapaian']);
        $this->assertTrue($json['pencapaian_tak_terhingga']);
        $this->assertEqualsWithDelta(4.0, $json['skor'], 0.01);
    }

    public function testAjaxHitungTurunanMengembalikanPencapaianDanTotal(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Turunan');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'TK-F2', 'max', 100);
        $kpId         = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);
        $this->makePeriodeAktif();

        $turunanId = (new \App\Models\KpiPegawaiTurunanModel())->insert([
            'kpi_pegawai_id' => $kpId,
            'nama_turunan'   => 'Sub Parameter 1',
            'bobot'          => 1.0000,
            'target'         => 50,
            'polarity'       => 'max',
            'is_active'      => 1,
        ]);

        $result = $this->withSession($this->sessionFor('drafter', 1, $pegawaiId))
            ->post('penilaian/ajaxHitungTurunan', [
                'turunan_id' => $turunanId,
                'pegawai_id' => $pegawaiId,
                'realisasi'  => 55, // 55/50 = 110% -> band 3
            ]);

        $json = json_decode($result->getJSON(), true);
        $this->assertTrue($json['valid']);
        $this->assertEqualsWithDelta(110.0, $json['pencapaian'], 0.01);
        $this->assertFalse($json['pencapaian_tak_terhingga']);
        $this->assertEqualsWithDelta(3.0, $json['skor'], 0.01);
        $this->assertEqualsWithDelta(3.0, $json['nilai'], 0.01);
        $this->assertEqualsWithDelta(3.0, $json['kontribusi_t'], 0.01); // bobot turunan 100%
    }

    // ── Regresi: bobot KPI belum 100% -> penginputan penilaian diblokir ──

    public function testFormDitolakJikaBobotKpiBelum100Persen(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Bobot Kurang');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'TK-F3', 'max', 100);
        $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 0.6000, 100); // bobot cuma 60%
        $this->makePeriodeAktif();

        $result = $this->withSession($this->sessionFor('drafter', 1, $pegawaiId))
            ->get("penilaian/form/{$pegawaiId}");

        $result->assertRedirectTo(base_url('penilaian'));
        $this->assertStringContainsString(
            'belum mencapai 100%',
            session('error') ?? ''
        );
        $this->assertStringContainsString('Pegawai Bobot Kurang', session('error') ?? '');
    }

    public function testStoreDitolakJikaBobotKpiBelum100Persen(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Bobot Kurang');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'TK-F4', 'max', 100);
        $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 0.5000, 100); // bobot cuma 50%
        $this->makePeriodeAktif();

        $result = $this->withSession($this->sessionFor('drafter', 1, $pegawaiId))
            ->post("penilaian/store/{$pegawaiId}", [
                'realisasi' => [$kpiUnitId => 100],
            ]);

        $result->assertRedirect();
        $this->assertSame(
            0,
            (new PenilaianModel())->where('pegawai_id', $pegawaiId)->countAllResults(),
            'Penilaian tidak boleh tersimpan selama bobot KPI belum 100%.'
        );
    }

    public function testFormBisaDiaksesJikaBobotKpiTepat100Persen(): void
    {
        $ctx = $this->setupPegawaiDenganKpi('max'); // bobot 1.0000 dari helper

        $result = $this->withSession($this->sessionFor('drafter', 1, $ctx['pegawaiId']))
            ->get("penilaian/form/{$ctx['pegawaiId']}");

        $result->assertOK();
    }

    // ── Polarity 'precise' (Precise is Better) — end-to-end ──

    public function testStorePreciseMenyimpanSkorSesuaiToleransi(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Precise');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'TK-P1', 'precise', 100, [
            'toleransi_skor4' => 2.5, 'toleransi_skor3' => 7.5, 'toleransi_skor2' => 12.5,
        ]);
        $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);
        $this->makePeriodeAktif();

        $this->withSession($this->sessionFor('drafter', 1, $pegawaiId))
            ->post("penilaian/store/{$pegawaiId}", ['realisasi' => [$kpiUnitId => 101]]); // deviasi 1% -> Skor 4

        $row = (new PenilaianModel())->where('pegawai_id', $pegawaiId)->where('kpi_id', $kpiUnitId)->first();
        $this->assertNotNull($row);
        $this->assertEqualsWithDelta(4.0, (float)$row['skor'], 0.01);
    }

    public function testAjaxHitungPreciseMengembalikanSkorSesuaiDeviasi(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Precise Ajax');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'TK-P2', 'precise', 100, [
            'toleransi_skor4' => 2.5, 'toleransi_skor3' => 7.5, 'toleransi_skor2' => 12.5,
        ]);
        $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);
        $this->makePeriodeAktif();

        $result = $this->withSession($this->sessionFor('drafter', 1, $pegawaiId))
            ->post('penilaian/ajaxHitung', [
                'pegawai_id' => $pegawaiId, 'kpi_id' => $kpiUnitId, 'realisasi' => 95, // deviasi 5% -> Skor 3
            ]);

        $json = json_decode($result->getJSON(), true);
        $this->assertTrue($json['valid']);
        $this->assertEqualsWithDelta(3.0, $json['skor'], 0.01);
    }

    // ── Polarity 'special' (Special Scoring) — end-to-end ──

    public function testStoreSpecialMaximizeAdaMenyimpanSkorEmpat(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Special Max');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'TK-S1', 'special', 100, ['sifat_khusus' => 'maximize']);
        $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);
        $this->makePeriodeAktif();

        $this->withSession($this->sessionFor('drafter', 1, $pegawaiId))
            ->post("penilaian/store/{$pegawaiId}", ['realisasi' => [$kpiUnitId => 1]]); // Ada

        $row = (new PenilaianModel())->where('pegawai_id', $pegawaiId)->where('kpi_id', $kpiUnitId)->first();
        $this->assertNotNull($row);
        $this->assertEqualsWithDelta(4.0, (float)$row['skor'], 0.01);
    }

    public function testStoreSpecialMinimizeTidakAdaMenyimpanSkorEmpat(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Special Min');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'TK-S2', 'special', 100, ['sifat_khusus' => 'minimize']);
        $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);
        $this->makePeriodeAktif();

        $this->withSession($this->sessionFor('drafter', 1, $pegawaiId))
            ->post("penilaian/store/{$pegawaiId}", ['realisasi' => [$kpiUnitId => 0]]); // Tidak Ada, sengaja diisi 0

        $row = (new PenilaianModel())->where('pegawai_id', $pegawaiId)->where('kpi_id', $kpiUnitId)->first();
        $this->assertNotNull($row, 'Realisasi 0 (Tidak Ada) untuk special-polarity harus tetap tersimpan, bukan dilewati.');
        $this->assertEqualsWithDelta(4.0, (float)$row['skor'], 0.01);
    }

    // ── Polarity 'tertimbang' (Scoring Tertimbang) — end-to-end ──

    public function testStoreTertimbangMenyimpanSkorGabunganDuaIndikator(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Tertimbang');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'TK-T1', 'tertimbang', 100);
        $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);
        $this->makePeriodeAktif();

        $this->withSession($this->sessionFor('drafter', 1, $pegawaiId))
            ->post("penilaian/store/{$pegawaiId}", [
                'realisasi'        => [$kpiUnitId => 120], // Skor Dasar 4
                'realisasi_harian' => [$kpiUnitId => 96],  // Pengkali 1.00
            ]);

        $row = (new PenilaianModel())->where('pegawai_id', $pegawaiId)->where('kpi_id', $kpiUnitId)->first();
        $this->assertNotNull($row);
        $this->assertEqualsWithDelta(4.0, (float)$row['skor'], 0.01);
        $this->assertEqualsWithDelta(96.0, (float)$row['realisasi_harian'], 0.01);
    }

    public function testStoreTertimbangTidakTersimpanJikaHanyaSatuIndikatorTerisi(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Tertimbang Parsial');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'TK-T2', 'tertimbang', 100);
        $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);
        $this->makePeriodeAktif();

        $this->withSession($this->sessionFor('drafter', 1, $pegawaiId))
            ->post("penilaian/store/{$pegawaiId}", [
                'realisasi' => [$kpiUnitId => 120],
                // realisasi_harian sengaja tidak dikirim sama sekali
            ]);

        $row = (new PenilaianModel())->where('pegawai_id', $pegawaiId)->where('kpi_id', $kpiUnitId)->first();
        $this->assertNull($row, 'Tertimbang harus all-or-nothing: satu indikator kosong berarti belum lengkap.');
    }

    public function testAjaxHitungTertimbangMengembalikanSkorGabungan(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Tertimbang Ajax');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'TK-T3', 'tertimbang', 100);
        $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);
        $this->makePeriodeAktif();

        $result = $this->withSession($this->sessionFor('drafter', 1, $pegawaiId))
            ->post('penilaian/ajaxHitung', [
                'pegawai_id' => $pegawaiId, 'kpi_id' => $kpiUnitId,
                'realisasi' => 120, 'realisasi_harian' => 90, // Skor Indikator 4 x Pengkali 0.95 = 3.8
            ]);

        $json = json_decode($result->getJSON(), true);
        $this->assertTrue($json['valid']);
        $this->assertEqualsWithDelta(3.8, $json['skor'], 0.01);
    }

    // ── Regresi: Contoh 2 persis dari spesifikasi 3-tahap Scoring Tertimbang
    // (Target=100, Realisasi=105 -> Skor 3; Rata-rata Harian=92% -> Pengkali
    // 95%; Skor Akhir = 3 x 0,95 = 2,85). Rata-rata Harian adalah PERSENTASE
    // langsung, bukan rasio realisasi/target — tidak ada "Target Harian".
    public function testStoreTertimbangSesuaiContoh2Spesifikasi(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Tertimbang Contoh2');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'TK-T4', 'tertimbang', 100);
        $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);
        $this->makePeriodeAktif();

        $this->withSession($this->sessionFor('drafter', 1, $pegawaiId))
            ->post("penilaian/store/{$pegawaiId}", [
                'realisasi'        => [$kpiUnitId => 105],
                'realisasi_harian' => [$kpiUnitId => 92],
            ]);

        $row = (new PenilaianModel())->where('pegawai_id', $pegawaiId)->where('kpi_id', $kpiUnitId)->first();
        $this->assertNotNull($row);
        $this->assertEqualsWithDelta(2.85, (float)$row['skor'], 0.01);
    }

    // ── Regresi: Skor Induk (Cara B, agregasi dari Turunan) TIDAK boleh
    // di-floor ke 1 — Turunan ber-polarity 'tertimbang' bisa sah
    // menghasilkan Skor_T di bawah 1 (Skor Indikator 1 x Pengkali 0,85 =
    // 0,85), dan rata-rata tertimbangnya pun harus mencerminkan itu apa
    // adanya, bukan dipaksa naik ke 1.0.
    public function testSkorIndukTidakDiFloorKeSatuUntukTurunanTertimbangRendah(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Turunan Tertimbang Rendah');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'TK-T5', 'max', 100);
        $kpId         = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);
        $this->makePeriodeAktif();

        $turunanId = (new \App\Models\KpiPegawaiTurunanModel())->insert([
            'kpi_pegawai_id' => $kpId,
            'nama_turunan'   => 'Sub Tertimbang Rendah',
            'bobot'          => 1.0000,
            'target'         => 100,
            'polarity'       => 'tertimbang',
            'is_active'      => 1,
        ]);

        $this->withSession($this->sessionFor('drafter', 1, $pegawaiId))
            ->post("penilaian/store/{$pegawaiId}", [
                'realisasi_turunan'        => [$kpId => [$turunanId => 50]],  // 50% -> Skor Indikator 1
                'realisasi_turunan_harian' => [$kpId => [$turunanId => 80]],  // <85% -> Pengkali 0.85
            ]);

        $row = (new PenilaianModel())->where('pegawai_id', $pegawaiId)->where('kpi_id', $kpiUnitId)->first();
        $this->assertNotNull($row);
        // Skor_T = 1 x 0.85 = 0.85; satu-satunya Turunan berbobot penuh ->
        // Skor Induk = 0.85 juga. Sebelum diperbaiki, ini akan diam-diam
        // di-floor menjadi 1.0.
        $this->assertEqualsWithDelta(0.85, (float)$row['skor'], 0.01,
            'Skor Induk tidak boleh di-floor ke 1 — harus mencerminkan agregasi Turunan tertimbang apa adanya.');
    }
}

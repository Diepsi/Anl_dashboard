<?php

namespace Tests\Feature;

use App\Models\Shipment;
use App\Services\ShipmentSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Keputusan pemilik data: semua baris sheet disimpan, dan nomor resi yang muncul
 * lebih dari satu kali ditandai dengan is_duplicate_no_resi alih-alih digabung
 * atau dibuang. Test di sini mengunci bahwa tidak ada lagi baris yang hilang saat
 * resi kembar, dan bahwa penandaannya berjalan benar.
 */
class ShipmentDuplicateMarkingTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://docs.google.test/dup';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.google_sheets.csv_url' => self::URL]);

        // Fingerprint dipasang dulu supaya keputusan replace=false benar-benar
        // ditaati dan tidak tersapu auto-replace akibat cache antar-test.
        Cache::put(
            ShipmentSyncService::SOURCE_FINGERPRINT_CACHE_KEY,
            hash('sha256', self::URL)
        );
    }

    private function csv(array $rows): string
    {
        $lines = ['No Resi,NAMA SEKOLAH,Status Akhir'];

        foreach ($rows as $row) {
            $lines[] = implode(',', $row);
        }

        return implode("\n", $lines)."\n";
    }

    private function resi(int $n): string
    {
        return str_pad((string) $n, 15, '0', STR_PAD_LEFT);
    }

    private function fakeCsv(string $body): void
    {
        Http::fake(['*' => Http::response($body)]);
    }

    public function test_rows_sharing_a_resi_are_all_stored_and_marked(): void
    {
        $a = $this->resi(111);
        $b = $this->resi(222);
        $c = $this->resi(333);

        $this->fakeCsv($this->csv([
            [$a, 'SD 1', 'Completed'],
            [$a, 'SD 1b', 'On Process'],
            [$b, 'SD 2', 'Completed'],
            [$b, 'SD 2b', 'Completed'],
            [$c, 'SD 3', 'Completed'],
        ]));

        $result = app(ShipmentSyncService::class)->sync(replace: true);

        $this->assertSame(5, $result, 'Lima baris masukan menghasilkan lima baris database.');
        $this->assertSame(5, Shipment::count());

        $byId = Shipment::query()
            ->select('no_resi', 'is_duplicate_no_resi')
            ->orderBy('id')
            ->get();

        // Semua baris ada (nilai ke-1 dan ke-3 kolom resi), dan setiap baris yang
        // berbagi nomor ditandai; yang unik tidak.
        $this->assertSame(
            [$a, $a, $b, $b, $c],
            $byId->pluck('no_resi')->all()
        );
        $this->assertSame(
            [true, true, true, true, false],
            $byId->pluck('is_duplicate_no_resi')->map(fn ($v) => (bool) $v)->all()
        );
        $this->assertSame(4, Shipment::query()->where('is_duplicate_no_resi', true)->count());
    }

    public function test_stats_reflect_that_every_row_was_imported(): void
    {
        $a = $this->resi(111);

        $this->fakeCsv($this->csv([
            [$a, 'SD 1', 'Completed'],
            [$a, 'SD 1b', 'Completed'],
            ['', 'SD 2', 'Completed'],
            ['#N/A', 'SD 3', 'Completed'],
        ]));

        $result = app(ShipmentSyncService::class)->sync(replace: true);

        $this->assertSame(4, $result);
        $this->assertSame(4, Shipment::count());

        $stats = app(ShipmentSyncService::class)->sourceStats();

        $this->assertSame(4, $stats['raw_rows']);
        $this->assertSame(1, $stats['skipped_empty']);
        $this->assertSame(1, $stats['skipped_malformed']);
        $this->assertSame(2, $stats['valid_rows']);
        $this->assertSame(4, $stats['imported_rows']);
        $this->assertSame(1, $stats['dup_extra']);
        $this->assertSame(1, $stats['unique_rows']);
        $this->assertSame(2, $stats['duplicates'][$a]);
    }

    public function test_null_receipt_rows_do_not_accumulate_on_update_syncs(): void
    {
        $a = $this->resi(1);
        $b = $this->resi(2);

        Shipment::create(['no_resi' => $a, 'nama_sekolah' => 'SD lama']);
        Shipment::create(['no_resi' => null, 'nama_sekolah' => 'SD noresi 1']);
        Shipment::create(['no_resi' => null, 'nama_sekolah' => 'SD noresi 2']);

        $this->fakeCsv($this->csv([
            [$a, 'SD 1 baru', 'Completed'],
            [$b, 'SD 2', 'Completed'],
            ['#N/A', 'SD tanpa resi', 'Completed'],
        ]));

        app(ShipmentSyncService::class)->sync(replace: false);

        // Baris ber-NULL lama dihapus dan yang satu dari CSV di-import: totalnya
        // harus tetap 2 (a + b) + 1 (NULL), bukan menumpuk.
        $this->assertSame(3, Shipment::count());
        $this->assertSame(1, Shipment::query()->whereNull('no_resi')->count());
        $this->assertSame(
            'SD 1 baru',
            Shipment::query()->where('no_resi', $a)->first()->nama_sekolah
        );
    }

    public function test_a_previously_merged_scientific_batch_comes_back_marked(): void
    {
        // Nilai ilmiah mengandung koma, jadi selnya harus dikutip agar CSV tetap
        // utuh — persis seperti yang dilakukan Google Sheets di ekspor aslinya.
        $this->fakeCsv($this->csv([
            ['"1,01E+15"', 'SD 1', 'Completed'],
            ['"1,01E+15"', 'SD 2', 'Completed'],
            ['"1,01E+15"', 'SD 3', 'Completed'],
        ]));

        $result = app(ShipmentSyncService::class)->sync(replace: true);

        $this->assertSame(3, $result, 'Batch 1,01E+15 dulu dikerutkan jadi satu; sekarang tiga baris masuk.');
        $this->assertSame(3, Shipment::count());
        $this->assertSame(
            [true, true, true],
            Shipment::query()
                ->where('no_resi', '1010000000000000')
                ->orderBy('id')
                ->pluck('is_duplicate_no_resi')
                ->map(fn ($v) => (bool) $v)
                ->all()
        );
    }

    public function test_a_smaller_update_keeps_old_rows_and_refreshes_overlapping_ones(): void
    {
        $a = $this->resi(1);
        $b = $this->resi(2);
        $c = $this->resi(3);
        $d = $this->resi(4);

        Shipment::create(['no_resi' => $c, 'nama_sekolah' => 'SD lama']);
        Shipment::create(['no_resi' => $d, 'nama_sekolah' => 'SD lama 4']);

        $this->fakeCsv($this->csv([
            [$a, 'SD 1', 'Completed'],
            [$a, 'SD 1b', 'Completed'],
            [$b, 'SD 2', 'Completed'],
            [$c, 'SD 3 baru', 'Completed'],
        ]));

        $result = app(ShipmentSyncService::class)->sync(replace: false);

        $this->assertSame(4, $result);
        $this->assertSame(5, Shipment::count(), 'Baris lama yang tidak lagi di CSV tetap ada.');
        $this->assertSame(2, Shipment::query()->where('is_duplicate_no_resi', true)->count());
        $this->assertSame(
            'SD 3 baru',
            Shipment::query()->where('no_resi', $c)->first()->nama_sekolah,
            'Resi yang ikut di CSV disegarkan, bukan digandakan.'
        );
        $this->assertSame(
            'SD lama 4',
            Shipment::query()->where('no_resi', $d)->first()->nama_sekolah
        );
    }
}

<?php

namespace Tests\Feature;

use App\Models\Shipment;
use App\Services\ShipmentSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

/**
 * `replace` mengosongkan tabel shipments sebelum meng-import apa pun. Sheet Google
 * yang terhapus, terfilter, atau salah pilih tab hanya mengembalikan baris header,
 * dan itulah sumber kegagalan yang paling mungkin terjadi pada pipeline ini karena
 * `AGENTS.md` memperlakukan pergantian sheet sebagai langkah rutin.
 *
 * Tanpa pre-flight, rangkaian sebelumnya berjalan tanpa error: header lolos
 * validasi, tabel dihapus, loop data langsung EOF, dan sync melaporkan "Selesai: 0
 * baris" dengan `last_synced_at` yang diperbarui ke waktu sekarang. Dashboard
 * yang tampil setelahnya terlihat seperti data baru saja masuk, padahal tabelnya
 * kosong. Test di sini mengunci bahwa tabel tidak boleh tersentuh dalam kondisi
 * itu, dan bahwa sync yang sehat tetap berjalan.
 */
class ShipmentSyncGuardTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://docs.google.test/sheet';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.google_sheets.csv_url' => self::URL]);
    }

    /** Build a CSV with the same header shape the real source uses. */
    private function csv(array $rows): string
    {
        $lines = ['No Resi,NAMA SEKOLAH,Status Akhir'];

        foreach ($rows as $row) {
            $lines[] = implode(',', $row);
        }

        return implode("\n", $lines)."\n";
    }

    /** Receipt numbers are only accepted as 13-18 digits, so build valid ones. */
    private function resi(int $n): string
    {
        return str_pad((string) $n, 15, '0', STR_PAD_LEFT);
    }

    private function seedShipments(int $count): void
    {
        foreach (range(1, $count) as $i) {
            Shipment::create([
                'no_resi' => $this->resi($i),
                'nama_sekolah' => 'SD '.$i,
                'nama_funder' => 'ANL',
            ]);
        }
    }

    private function fakeCsv(string $body): void
    {
        Http::fake(['*' => Http::response($body)]);
    }

    public function test_header_only_csv_never_deletes_existing_data(): void
    {
        $this->seedShipments(3);
        $this->fakeCsv($this->csv([]));

        try {
            app(ShipmentSyncService::class)->sync(replace: true);
            $this->fail('Sync sheet kosong seharusnya ditolak.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('0 baris data valid', $e->getMessage());
        }

        $this->assertSame(3, Shipment::count(), 'Tabel tidak boleh tersentuh saat CSV tidak punya data.');
    }

    public function test_csv_without_a_header_is_rejected(): void
    {
        $this->seedShipments(2);
        $this->fakeCsv('');

        $this->expectException(RuntimeException::class);

        try {
            app(ShipmentSyncService::class)->sync(replace: true);
        } finally {
            $this->assertSame(2, Shipment::count());
        }
    }

    public function test_rows_without_a_usable_receipt_are_stored_with_null_resi(): void
    {
        // Keputusan pemilik data: semuanya masuk. Baris yang resinya tak bisa
        // dipulihkan disimpan dengan no_resi NULL (tampil "—"), bukan ditolak.
        // Yang tetap dijaga: sheet hanya-berheader masih ditolak (lihat di atas).
        $this->fakeCsv($this->csv([
            ['#N/A', 'SD 1', 'Completed'],
            ['1,23E+5', 'SD 2', 'Completed'],
            ['123', 'SD 3', 'Completed'],
        ]));

        $count = app(ShipmentSyncService::class)->sync(replace: true);

        $this->assertSame(3, $count, 'Semua baris berdata masuk, sekalipun resinya rusak.');
        $this->assertSame(3, Shipment::count());
        $this->assertSame(
            [null, null, null],
            Shipment::query()->orderBy('id')->pluck('no_resi')->all(),
            'Resi yang tak bisa dipulihkan disimpan sebagai NULL.'
        );
        $this->assertSame(0, Shipment::query()->where('is_duplicate_no_resi', true)->count());
    }

    public function test_a_shrunken_sheet_is_rejected_before_the_table_is_cleared(): void
    {
        $this->seedShipments(10);
        $this->fakeCsv($this->csv([[$this->resi(99), 'SD 1', 'Completed']]));

        try {
            app(ShipmentSyncService::class)->sync(replace: true);
            $this->fail('Sheet yang menyusut drastis seharusnya ditolak.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('turun drastis', $e->getMessage());
        }

        $this->assertSame(10, Shipment::count());
    }

    public function test_a_failed_sync_leaves_the_last_synced_timestamp_alone(): void
    {
        $this->seedShipments(2);
        $this->fakeCsv($this->csv([]));

        try {
            app(ShipmentSyncService::class)->sync(replace: true);
        } catch (RuntimeException) {
            // diabaikan; yang diperiksa adalah efek sampingnya
        }

        $this->assertNull(
            app(ShipmentSyncService::class)->lastSyncedAt(),
            'Sync yang gagal tidak boleh menandai data baru saja tersinkron.'
        );
    }

    public function test_force_allows_a_deliberately_smaller_sheet(): void
    {
        $this->seedShipments(10);
        $this->fakeCsv($this->csv([[$this->resi(99), 'SD 1', 'Completed']]));

        $count = app(ShipmentSyncService::class)->sync(replace: true, force: true);

        $this->assertSame(1, $count);
        $this->assertSame(1, Shipment::count());
    }

    public function test_a_healthy_sheet_is_still_replaced_normally(): void
    {
        $this->seedShipments(4);

        $rows = [];
        foreach (range(1, 6) as $i) {
            $rows[] = [$this->resi(100 + $i), 'SD '.$i, 'Completed'];
        }
        $this->fakeCsv($this->csv($rows));

        $count = app(ShipmentSyncService::class)->sync(replace: true);

        $this->assertSame(6, $count, 'Sync yang sehat tidak boleh diblokir oleh guard.');
        $this->assertSame(6, Shipment::count());
        $this->assertSame(
            [$this->resi(101), $this->resi(102), $this->resi(103), $this->resi(104), $this->resi(105), $this->resi(106)],
            Shipment::query()->orderBy('no_resi')->pluck('no_resi')->all(),
            'replace harus mengganti isi lama, bukan menyusulkannya.'
        );
        $this->assertNotNull(app(ShipmentSyncService::class)->lastSyncedAt());
    }
}

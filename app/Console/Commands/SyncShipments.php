<?php

namespace App\Console\Commands;

use App\Services\ShipmentSyncService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('shipments:sync {--replace : Kosongkan tabel sebelum import} {--force : Izinkan sync meski baris valid nol atau menyusut drastis}')]
#[Description('Sinkronkan data pengiriman dari Google Sheets (CSV publik)')]
class SyncShipments extends Command
{
    public function handle(ShipmentSyncService $service): int
    {
        $this->info('Menyinkronkan data pengiriman dari Google Sheets...');

        try {
            $count = $service->sync(
                (bool) $this->option('replace'),
                (bool) $this->option('force'),
            );

            $lastSync = $service->lastSyncedAt();

            $this->info("Selesai: {$count} baris di-import.");
            $this->warn('Terakhir sync: '.($lastSync?->format('d M Y H:i:s') ?? '-'));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Sync gagal: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}

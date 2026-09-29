<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pemilik data memutuskan: semua baris sheet (31.709) harus masuk ke database,
 * dan nomor resi yang muncul lebih dari satu kali ditandai, bukan digabung.
 *
 * Dulu `no_resi` bersifat unik sehingga sync menggabungkan baris kembar (609 baris
 * di antaranya berakhir dari batch "1,01E+15" yang tidak bisa dibedakan string-nya)
 * dan membuang baris tanpa resi. Migration ini mengizinkan no_resi nul dan kembar,
 * lalu menambah tanda `is_duplicate_no_resi` agar pelanggan tidak terkecoh bahwa
 * semua nomor resi yang sama hanyalah satu baris.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            foreach (['shipments_no_resi_unique', 'shipments_no_resi_index'] as $index) {
                try {
                    Schema::table('shipments', fn (Blueprint $t) => $t->dropIndex($index));
                } catch (RuntimeException) {
                    // Indeks tidak wajib ada di semua driver (mis. SQLite hanya
                    // membuat satunya); lanjutkan selama kolomnya bisa berubah.
                }
            }
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->string('no_resi')->nullable()->change();
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->boolean('is_duplicate_no_resi')->default(false)->after('no_resi');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('is_duplicate_no_resi');
        });

        // Semantik lama tidak bisa menyimpan baris tanpa resi atau nomor kembar.
        // Rollback sengaja menghapus yang tidak mampu dipresentasikan skema lama.
        $keptIds = DB::table('shipments')
            ->selectRaw('MIN(id) as id')
            ->whereNotNull('no_resi')
            ->groupBy('no_resi')
            ->pluck('id');

        foreach (array_chunk($keptIds->all(), 1000) as $slice) {
            DB::table('shipments')
                ->whereNull('no_resi')
                ->orWhereNotIn('id', $slice)
                ->delete();
        }

        Schema::table('shipments', function (Blueprint $table) {
            $table->string('no_resi')->nullable(false)->change();
            $table->unique('no_resi');
        });
    }
};

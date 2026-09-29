<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dua perubahan yang berakar dari satu sebab: kolom `SLA` di sumber tidak pernah
 * satu arti. BOMA mengisinya dengan ambang hari ("17"), sedangkan 31 vendor lain
 * mengisinya dengan tanggal batas ("25/08/2025"). Keduanya sah, tapi kode lama hanya
 * menerima angka 1..365, sehingga 28.320 baris non-BOMA kehilangan ambangnya dan ikut
 * hilang dari seluruh perhitungan SLA.
 *
 * Kolom baru:
 *  - `sla_due_date` menyatukan kedua bentuk ambang menjadi satu tanggal batas:
 *    baris yang `SLA`-nya bertanggal memakai tanggal itu apa adanya, sedangkan
 *    ambang hari dijangkarkan ke `Tgl HO dari SarTrans`. Satu kolom ini yang
 *    membuat verdict bisa dihitung ulang oleh dasbor dan diekspor.
 *  - `aging` diberi nama `sla_threshold_days` karena isinya ambang hari, bukan
 *    umur kiriman. Nama lamalah yang membuat kolom ini dibaca sebagai "umur" selama
 *    ini dan muncul di dasbor berlabel "Aging (hari)".
 *
 * Kolom yang dilonggarkan nullable: nilainya dulu dipaksa isi saat kosong
 * (harga 0, status_instalasi "BELUM", nama_funder "Panthera", status_akhir
 * "On Process"). Nilai karangan itu tidak bisa dibedakan dari data asli, dan
 * membuat rata-rata harga meleset belasan kali. Sekarang sel kosong di sumber
 * disimpan NULL, dan UI yang menampilkannya sebagai "—".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->renameColumn('aging', 'sla_threshold_days');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->date('sla_due_date')->nullable()->after('sla')->index();
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->string('nama_funder')->nullable()->change();
            $table->integer('sla_threshold_days')->nullable()->change();
            $table->string('status_akhir')->nullable()->change();
            $table->string('status_instalasi', 20)->nullable()->change();
            $table->decimal('harga_per_shipment', 15, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex(['sla_due_date']);
            $table->dropColumn('sla_due_date');
            $table->renameColumn('sla_threshold_days', 'aging');
        });

        // Skema lama tidak bisa menyatakan "tidak diketahui", jadi kembalikan dulu nilai
        // kosong ke default lamanya. Tanpa ini, menegakkan NOT NULL gagal di tengah
        // rollback karena ada ribuan baris NULL. Efek sampingnya jelas: rollback ini
        // menulis ulang nilai karangan yang sengaja dihapus pada langkah up.
        DB::table('shipments')->whereNull('nama_funder')->update(['nama_funder' => 'Panthera']);
        DB::table('shipments')->whereNull('aging')->update(['aging' => 0]);
        DB::table('shipments')->whereNull('status_akhir')->update(['status_akhir' => 'On Process']);
        DB::table('shipments')->whereNull('status_instalasi')->update(['status_instalasi' => 'BELUM']);
        DB::table('shipments')->whereNull('harga_per_shipment')->update(['harga_per_shipment' => 0]);

        Schema::table('shipments', function (Blueprint $table) {
            $table->string('nama_funder')->default('Panthera')->nullable(false)->change();
            $table->integer('aging')->default(0)->nullable(false)->change();
            $table->string('status_akhir')->default('On Process')->nullable(false)->change();
            $table->string('status_instalasi', 20)->default('BELUM')->nullable(false)->change();
            $table->decimal('harga_per_shipment', 15, 2)->default(0)->nullable(false)->change();
        });
    }
};

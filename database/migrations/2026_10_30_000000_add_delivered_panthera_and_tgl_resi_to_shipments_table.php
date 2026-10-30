<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->date('tgl_resi')->nullable()->after('tanggal_manifest')->index();
            $table->date('tgl_delivered_panthera')->nullable()->after('completed_date')->index();
        });

        // Data lama sudah punya tanggal manifest dan completed date. Keduanya menjadi
        // sumber awal untuk kolom baru agar dashboard tidak kosong sampai sync berikutnya.
        DB::table('shipments')->update([
            'tgl_resi' => DB::raw('tanggal_manifest'),
            'tgl_delivered_panthera' => DB::raw('completed_date'),
        ]);
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex(['tgl_resi']);
            $table->dropIndex(['tgl_delivered_panthera']);
            $table->dropColumn(['tgl_resi', 'tgl_delivered_panthera']);
        });
    }
};

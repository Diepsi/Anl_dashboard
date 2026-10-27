<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sumber data baru memakai nilai `Status Instalasi` yang lebih panjang dari
 * 20 karakter (mis. "belum update pada TMS" = 21), sehingga sync gagal di
 * tengah transaksi. Kolom dilonggarkan agar seluruh nilai sumber bisa masuk
 * tanpa dipotong.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('status_instalasi', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('shipments')
            ->whereRaw('LENGTH(status_instalasi) > 20')
            ->update(['status_instalasi' => DB::raw('SUBSTR(status_instalasi, 1, 20)')]);

        Schema::table('shipments', function (Blueprint $table) {
            $table->string('status_instalasi', 20)->nullable()->change();
        });
    }
};

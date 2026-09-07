<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('no_resi')->unique()->index();
            $table->string('nomor_redock')->nullable();
            $table->string('delivery_order')->nullable();
            $table->string('nama_sekolah')->index();
            $table->string('nama_penerima')->nullable();
            $table->string('provinsi')->nullable()->index();
            $table->string('daerah')->nullable();
            $table->string('kota_kabupaten')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('vendor_mm')->nullable();
            $table->string('kode_funder')->nullable();
            $table->string('nama_funder')->index();
            $table->string('vendor_lm')->nullable()->index();
            $table->date('tanggal_manifest')->nullable()->index();
            $table->date('completed_date')->nullable()->index();
            $table->date('tgl_sampai_kota_tujuan')->nullable();
            $table->integer('aging')->default(0);
            $table->string('status_akhir')->default('On Process')->index();
            $table->string('status_instalasi', 20)->default('BELUM');
            $table->decimal('harga_per_shipment', 15, 2)->default(0);
            $table->string('status_invoice')->nullable();
            $table->timestamps();

            $table->index(['nama_funder', 'tanggal_manifest']);
            $table->index(['nama_funder', 'status_akhir']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};

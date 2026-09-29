<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            // Serah-terima dari SarTrans (first mile). Dipisah dari tanggal_manifest
            // karena keduanya adalah kejadian berbeda: yang satu titik mulai
            // last-mile, yang satu titik mulai first-mile.
            $table->date('tgl_ho_sartrans')->nullable()->after('tanggal_manifest');

            $table->integer('koli')->nullable()->after('tanggal_manifest');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->index('tgl_ho_sartrans');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex(['tgl_ho_sartrans']);
            $table->dropColumn(['tgl_ho_sartrans', 'koli']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->date('bast_tgl_balik')->nullable()->after('sla_result');
            $table->date('bast_tgl_ke_finance')->nullable()->after('bast_tgl_balik');
            $table->string('bast_keterangan')->nullable()->after('bast_tgl_ke_finance');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn(['bast_tgl_balik', 'bast_tgl_ke_finance', 'bast_keterangan']);
        });
    }
};
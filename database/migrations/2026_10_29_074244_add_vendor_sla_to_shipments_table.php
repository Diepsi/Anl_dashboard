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
        Schema::table('shipments', function (Blueprint $table) {
            $table->integer('sla_vendor_days')->nullable()->after('sla');
            $table->string('sla_vendor_result')->nullable()->after('sla_result')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex(['sla_vendor_result']);
            $table->dropColumn(['sla_vendor_days', 'sla_vendor_result']);
        });
    }
};

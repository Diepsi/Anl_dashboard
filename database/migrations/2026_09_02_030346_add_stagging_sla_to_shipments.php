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
            $table->string('stagging')->nullable()->after('status_instalasi');
            $table->integer('sla')->nullable()->after('stagging');
            $table->string('sla_result')->nullable()->after('sla');

            $table->index(['stagging'], 'shipments_stagging_index');
            $table->index(['sla_result'], 'shipments_sla_result_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex(['stagging']);
            $table->dropIndex(['sla_result']);
            $table->dropColumn(['stagging', 'sla', 'sla_result']);
        });
    }
};

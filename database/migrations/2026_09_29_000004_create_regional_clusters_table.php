<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regional_clusters', function (Blueprint $table) {
            $table->id();
            $table->string('kota_kabupaten')->unique();
            $table->string('provinsi')->nullable();
            $table->unsignedTinyInteger('cluster_id');
            $table->string('cluster_label');
            $table->decimal('avg_aging', 8, 2)->nullable();
            $table->decimal('out_sla_rate', 5, 2)->nullable();
            $table->unsignedInteger('total_shipment');
            $table->unsignedInteger('verifiable_count')->default(0);
            $table->decimal('risk_index', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regional_clusters');
    }
};

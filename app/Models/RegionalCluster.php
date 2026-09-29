<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegionalCluster extends Model
{
    protected $fillable = [
        'kota_kabupaten',
        'provinsi',
        'cluster_id',
        'cluster_label',
        'avg_aging',
        'out_sla_rate',
        'total_shipment',
        'verifiable_count',
        'risk_index',
    ];

    protected function casts(): array
    {
        return [
            'cluster_id' => 'integer',
            'avg_aging' => 'decimal:2',
            'out_sla_rate' => 'decimal:2',
            'total_shipment' => 'integer',
            'verifiable_count' => 'integer',
            'risk_index' => 'decimal:2',
        ];
    }
}

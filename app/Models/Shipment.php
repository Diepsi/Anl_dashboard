<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Shipment extends Model
{
    protected $fillable = [
        'no_resi',
        'nomor_redock',
        'delivery_order',
        'nama_sekolah',
        'nama_penerima',
        'provinsi',
        'daerah',
        'kota_kabupaten',
        'kecamatan',
        'vendor_mm',
        'kode_funder',
        'nama_funder',
        'vendor_lm',
        'tanggal_manifest',
        'completed_date',
        'tgl_sampai_kota_tujuan',
        'aging',
        'status_akhir',
        'status_instalasi',
        'harga_per_shipment',
        'status_invoice',
        'stagging',
        'sla',
        'sla_result',
        'bast_tgl_balik',
        'bast_tgl_ke_finance',
        'bast_keterangan',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tanggal_manifest' => 'date',
            'completed_date' => 'date',
            'tgl_sampai_kota_tujuan' => 'date',
            'aging' => 'integer',
            'harga_per_shipment' => 'decimal:2',
            'sla' => 'integer',
            'bast_tgl_balik' => 'date',
            'bast_tgl_ke_finance' => 'date',
        ];
    }

    public function scopeInRange($query, int $days)
    {
        if ($days <= 0) {
            return $query;
        }

        // Range dihitung mundur dari tanggal manifest TERAKHIR di data (bukan hari ini),
        // agar dashboard tetap menampilkan data aktif meski sumber GSheet belum diperbarui.
        $latest = $query->clone()->selectRaw('MAX(tanggal_manifest) as latest')->value('latest');

        if (! $latest) {
            return $query;
        }

        $anchor = ($latest instanceof \DateTimeInterface) ? $latest : Carbon::parse($latest);

        return $query->where('tanggal_manifest', '>=', $anchor->copy()->subDays($days - 1)->startOfDay());
    }
}

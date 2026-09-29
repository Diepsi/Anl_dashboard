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
        'tgl_ho_sartrans',
        'koli',
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
            'tgl_ho_sartrans' => 'date',
            'koli' => 'integer',
            'completed_date' => 'date',
            'tgl_sampai_kota_tujuan' => 'date',
            'aging' => 'integer',
            'harga_per_shipment' => 'decimal:2',
            'sla' => 'integer',
            'bast_tgl_balik' => 'date',
            'bast_tgl_ke_finance' => 'date',
        ];
    }

    /**
     * Exclude rows whose tanggal_manifest is later than completed_date.
     * Such rows are logically impossible and corrupt any date-based aggregate.
     */
    public function scopeWithConsistentDates($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('tanggal_manifest')
                ->orWhereNull('completed_date')
                ->orWhereColumn('tanggal_manifest', '<=', 'completed_date');
        });
    }

    public function scopeInRange($query, int $days)
    {
        if ($days <= 0) {
            return $query;
        }

        // Range dihitung mundur dari tanggal acuan TERAKHIR yang trustworthy (bukan hari ini),
        // agar dashboard tetap menampilkan data aktif meski sumber GSheet belum diperbarui.
        // completed_date dipakai sebagai jangkar karena kolom itu terisi hampir penuh,
        // sedangkan MAX(tanggal_manifest) bisa didominasi baris korup (tanggal manifest
        // yang lebih baru dari tanggal selesai).
        $latest = $query->clone()->max('completed_date');

        if (! $latest) {
            return $query;
        }

        $anchor = ($latest instanceof \DateTimeInterface) ? $latest : Carbon::parse($latest);

        // Baris yang belum selesai (completed_date kosong) selalu ikut dihitung: tugas panel ini
        // justru memantau kiriman yang masih berjalan, jadi menyingkirkannya akan membuat
        // "Perlu Perhatian" kosong tepat saat ada masalah.
        return $query->where(function ($q) use ($anchor, $days) {
            $q->whereNull('completed_date')
                ->orWhere('completed_date', '>=', $anchor->copy()->subDays($days - 1)->startOfDay());
        });
    }
}

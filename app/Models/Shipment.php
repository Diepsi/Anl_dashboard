<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Shipment extends Model
{
    protected $fillable = [
        'no_resi',
        'is_duplicate_no_resi',
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
        'sla_threshold_days',
        'status_akhir',
        'status_instalasi',
        'harga_per_shipment',
        'status_invoice',
        'stagging',
        'sla',
        'sla_due_date',
        'sla_result',
        'bast_tgl_balik',
        'bast_tgl_ke_finance',
        'bast_keterangan',
    ];

    protected $guarded = ['id'];

    protected $appends = ['sla_verdict'];

    protected function casts(): array
    {
        return [
            'tanggal_manifest' => 'date',
            'tgl_ho_sartrans' => 'date',
            'koli' => 'integer',
            'completed_date' => 'date',
            'tgl_sampai_kota_tujuan' => 'date',
            'sla_threshold_days' => 'integer',
            'harga_per_shipment' => 'decimal:2',
            'sla' => 'integer',
            'sla_due_date' => 'date',
            'bast_tgl_balik' => 'date',
            'bast_tgl_ke_finance' => 'date',
            'is_duplicate_no_resi' => 'boolean',
        ];
    }

    /**
     * Verdict SLA dihitung ulang dari `completed_date` terhadap `sla_due_date`,
     * bukan diambil dari teks verdict di sheet.
     *
     * Kolom `SLA` di sumber berisi dua bentuk: BOMA mengisi ambang hari, 31 vendor
     * lain mengisi tanggal batas. `sla_due_date` sudah menyatukan keduanya saat
     * sync, jadi satu perbandingan ini berlaku untuk semua vendor. Mengambil
     * verdict apa adanya dari sheet membuat dashboard ikut 31 baris BOMA yang
     * sumbernya salah hitung.
     *
     * NULL berarti belum bisa diverifikasi, dan itu harus dibedakan dari "Out SLA":
     * satu baris yang tidak bisa dinilai tidak boleh ikut dihitung sebagai salah. */
    public function getSlaVerdictAttribute(): ?string
    {
        if ($this->sla_due_date === null || $this->completed_date === null) {
            return null;
        }

        return $this->completed_date->startOfDay()->lte($this->sla_due_date->startOfDay())
            ? 'Meet SLA'
            : 'Out SLA';
    }

    /** Baris yang punya ambang dan tanggal selesai, jadi verdict-nya bisa dihitung. */
    public function scopeSlaVerifiable($query)
    {
        return $query->whereNotNull('sla_due_date')->whereNotNull('completed_date');
    }

    public function scopeOutSla($query)
    {
        return $query->slaVerifiable()->whereColumn('completed_date', '>', 'sla_due_date');
    }

    public function scopeMeetSla($query)
    {
        return $query->slaVerifiable()->whereColumn('completed_date', '<=', 'sla_due_date');
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

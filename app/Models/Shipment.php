<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Shipment extends Model
{
    public const STATUS_COMPLETED = ['completed', 'pod', 'terkirim'];

    public const STATUS_ON_DELIVERY = ['on delivery', 'in transit', 'sedang dikirim'];

    public const STATUS_UNDELIVERED = ['undelivered', 'gagal kirim', 'gagal', 'retur'];

    public const STATUS_HOLD = ['hold', 'pending', 'ditahan'];

    public const STATUS_ACTIVE = [
        'on process', 'on process delivery', 'dikirim',
        'on delivery', 'in transit', 'sedang dikirim',
        'undelivered', 'gagal kirim', 'gagal', 'retur',
        'hold', 'pending', 'ditahan',
    ];

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
        'tgl_resi',
        'tgl_ho_sartrans',
        'koli',
        'completed_date',
        'tgl_delivered_panthera',
        'tgl_sampai_kota_tujuan',
        'sla_threshold_days',
        'status_akhir',
        'status_instalasi',
        'harga_per_shipment',
        'status_invoice',
        'stagging',
        'sla',
        'sla_vendor_days',
        'sla_due_date',
        'sla_result',
        'sla_vendor_result',
        'bast_tgl_balik',
        'bast_tgl_ke_finance',
        'bast_keterangan',
    ];

    protected $guarded = ['id'];

    protected $appends = [
        'sla_verdict',
        'sla_vendor_verdict',
        'sla_end_to_end',
        'sla_vendor',
        'sla_end_to_end_days',
        'sla_vendor_actual_days',
        'sla_vendor_due_date',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_manifest' => 'date',
            'tgl_resi' => 'date',
            'tgl_ho_sartrans' => 'date',
            'koli' => 'integer',
            'completed_date' => 'date',
            'tgl_delivered_panthera' => 'date',
            'tgl_sampai_kota_tujuan' => 'date',
            'sla_threshold_days' => 'integer',
            'harga_per_shipment' => 'decimal:2',
            'sla' => 'integer',
            'sla_vendor_days' => 'integer',
            'sla_due_date' => 'date',
            'bast_tgl_balik' => 'date',
            'bast_tgl_ke_finance' => 'date',
            'is_duplicate_no_resi' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // `completed_date` adalah nama lama. Selama masih ditulis oleh pemanggil lama,
        // samakan nilainya dengan kolom acuan tunggal agar tidak ada dua versi tanggal selesai.
        static::saving(function (self $shipment): void {
            if ($shipment->isDirty('tgl_delivered_panthera')) {
                $shipment->completed_date = $shipment->tgl_delivered_panthera;
            } elseif ($shipment->isDirty('completed_date')) {
                $shipment->tgl_delivered_panthera = $shipment->completed_date;
            }
        });
    }

    /**
     * Awal hitungan End-to-End: tanggal resi, lalu tanggal staging/manifest, lalu
     * handover SarTrans sebagai fallback bila kedua tanggal awal tidak tersedia.
     */
    public static function endToEndStartExpression(): string
    {
        return 'COALESCE(tgl_resi, tanggal_manifest, tgl_ho_sartrans)';
    }

    /**
     * Selisih hari yang portabel untuk MySQL (produksi) dan SQLite (test).
     */
    public static function dateDiffExpression(string $end, string $start): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => 'CAST(julianday('.$end.') - julianday('.$start.') AS INTEGER)',
            'pgsql' => 'CAST('.$end.' AS DATE) - CAST('.$start.' AS DATE)',
            default => 'DATEDIFF('.$end.', '.$start.')',
        };
    }

    public function getSlaEndToEndDaysAttribute(): ?int
    {
        $start = $this->tgl_resi ?? $this->tanggal_manifest ?? $this->tgl_ho_sartrans;

        if ($start === null || $this->tgl_delivered_panthera === null) {
            return null;
        }

        $start = $start->copy()->startOfDay();
        $delivered = $this->tgl_delivered_panthera->copy()->startOfDay();

        if ($start->gt($delivered)) {
            return null;
        }

        return (int) $start->diffInDays($delivered);
    }

    public function getSlaVendorActualDaysAttribute(): ?int
    {
        if ($this->tgl_ho_sartrans === null || $this->tgl_delivered_panthera === null) {
            return null;
        }

        $start = $this->tgl_ho_sartrans->copy()->startOfDay();
        $delivered = $this->tgl_delivered_panthera->copy()->startOfDay();

        if ($start->gt($delivered)) {
            return null;
        }

        return (int) $start->diffInDays($delivered);
    }

    public function getSlaVendorDueDateAttribute(): ?Carbon
    {
        if ($this->tgl_ho_sartrans === null || $this->sla_vendor_days === null) {
            return null;
        }

        return $this->tgl_ho_sartrans->copy()->addDays($this->sla_vendor_days);
    }

    /**
     * Verdict SLA End-to-End dihitung ulang dari `tgl_delivered_panthera`
     * terhadap `sla_due_date`, bukan diambil dari teks verdict di sheet.
     *
     * Kolom `SLA` di sumber berisi dua bentuk: BOMA mengisi ambang hari, 31 vendor
     * lain mengisi tanggal batas. `sla_due_date` sudah menyatukan keduanya saat
     * sync, jadi satu perbandingan ini berlaku untuk semua vendor.
     *
     * NULL berarti belum bisa diverifikasi, dan itu harus dibedakan dari "Out SLA":
     * satu baris yang tidak bisa dinilai tidak boleh ikut dihitung sebagai salah.
     */
    public function getSlaVerdictAttribute(): ?string
    {
        if ($this->sla_due_date === null || $this->tgl_delivered_panthera === null) {
            return null;
        }

        return $this->tgl_delivered_panthera->startOfDay()->lte($this->sla_due_date->startOfDay())
            ? 'Meet SLA'
            : 'Out SLA';
    }

    public function getSlaEndToEndAttribute(): ?string
    {
        return $this->sla_verdict;
    }

    /**
     * Verdict Vendor/SARTrans dihitung dari `tgl_ho_sartrans` sampai
     * `tgl_delivered_panthera`. Label `RESULT FOR VENDOR` hanya dipakai sebagai
     * cadangan bila ambang hari vendor tidak tersedia.
     */
    public function getSlaVendorVerdictAttribute(): ?string
    {
        $actualDays = $this->sla_vendor_actual_days;

        if ($actualDays !== null && $this->sla_vendor_days !== null) {
            return $actualDays <= $this->sla_vendor_days ? 'Meet SLA' : 'Out SLA';
        }

        return match (strtoupper(trim((string) $this->sla_vendor_result))) {
            'MEET SLA' => 'Meet SLA',
            'OVER SLA', 'OUT SLA' => 'Out SLA',
            default => null,
        };
    }

    public function getSlaVendorAttribute(): ?string
    {
        return $this->sla_vendor_verdict;
    }

    /** Baris dengan No Resi yang benar-benar berasal dari kolom resi. */
    public function scopeHasValidResi($query)
    {
        return $query->whereNotNull('no_resi')->where('no_resi', '!=', '');
    }

    public static function normalizeStatus(?string $status): ?string
    {
        $normalized = strtolower(trim((string) $status));

        return $normalized === '' ? null : $normalized;
    }

    public function scopeStatusIn($query, array $statuses)
    {
        return $query->whereIn(DB::raw('LOWER(TRIM(status_akhir))'), $statuses);
    }

    public function scopeStatusCompleted($query)
    {
        $known = array_merge(
            self::STATUS_COMPLETED,
            self::STATUS_ON_DELIVERY,
            self::STATUS_UNDELIVERED,
            self::STATUS_HOLD,
            self::STATUS_ACTIVE,
        );

        // Status adalah penentu utama karena sheet sering sudah mengisi
        // `Completed date` pada baris yang statusnya masih On Delivery/Hold.
        // Tanggal tetap dipakai sebagai fallback untuk baris tanpa status atau
        // status yang tidak dikenal.
        return $query->where(function ($q) use ($known) {
            $q->statusIn(self::STATUS_COMPLETED)
                ->orWhere(function ($q2) use ($known) {
                    $q2->whereNotNull('tgl_delivered_panthera')
                        ->where(function ($q3) use ($known) {
                            $q3->whereNull('status_akhir')
                                ->orWhereRaw('TRIM(status_akhir) = ?', [''])
                                ->orWhereNotIn(DB::raw('LOWER(TRIM(status_akhir))'), $known);
                        });
                });
        });
    }

    public function scopeStatusOnDelivery($query)
    {
        return $query->statusIn(self::STATUS_ON_DELIVERY);
    }

    public function scopeStatusUndelivered($query)
    {
        return $query->statusIn(self::STATUS_UNDELIVERED);
    }

    public function scopeStatusHold($query)
    {
        return $query->statusIn(self::STATUS_HOLD);
    }

    public function scopeStatusRetur($query)
    {
        return $query->statusIn(['retur']);
    }

    public function scopeStatusActive($query)
    {
        return $query->statusIn(self::STATUS_ACTIVE);
    }

    public function scopeStatusNotCompleted($query)
    {
        return $query->whereNot(fn ($q) => $q->statusCompleted());
    }

    /** Hanya baris yang sudah punya tanggal delivered Panthera yang boleh masuk metrik SLA. */
    public function scopeDeliveredPanthera($query)
    {
        return $query->whereNotNull('tgl_delivered_panthera');
    }

    /** Baris yang punya ambang dan tanggal delivered, jadi verdict-nya bisa dihitung. */
    public function scopeSlaVerifiable($query)
    {
        return $query->deliveredPanthera()->whereNotNull('sla_due_date');
    }

    public function scopeSlaVendorVerifiable($query)
    {
        return $query->deliveredPanthera()
            ->whereNotNull('tgl_ho_sartrans')
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereNotNull('sla_vendor_days')
                        ->whereColumn('tgl_ho_sartrans', '<=', 'tgl_delivered_panthera');
                })->orWhereRaw("UPPER(TRIM(COALESCE(sla_vendor_result, ''))) IN ('MEET SLA', 'OVER SLA', 'OUT SLA')");
            });
    }

    public function scopeOutSlaVendor($query)
    {
        $diff = self::dateDiffExpression('tgl_delivered_panthera', 'tgl_ho_sartrans');
        $daysUsable = 'sla_vendor_days IS NOT NULL AND tgl_ho_sartrans <= tgl_delivered_panthera';
        $fallback = '(sla_vendor_days IS NULL OR tgl_ho_sartrans > tgl_delivered_panthera) '
            ."AND UPPER(TRIM(COALESCE(sla_vendor_result, ''))) IN ('OVER SLA', 'OUT SLA')";

        return $query->slaVendorVerifiable()
            ->where(function ($q) use ($diff, $daysUsable, $fallback) {
                $q->where(function ($q2) use ($diff, $daysUsable) {
                    $q2->whereRaw($daysUsable)->whereRaw($diff.' > sla_vendor_days');
                })->orWhereRaw($fallback);
            });
    }

    public function scopeMeetSlaVendor($query)
    {
        $diff = self::dateDiffExpression('tgl_delivered_panthera', 'tgl_ho_sartrans');
        $daysUsable = 'sla_vendor_days IS NOT NULL AND tgl_ho_sartrans <= tgl_delivered_panthera';
        $fallback = '(sla_vendor_days IS NULL OR tgl_ho_sartrans > tgl_delivered_panthera) '
            ."AND UPPER(TRIM(COALESCE(sla_vendor_result, ''))) = 'MEET SLA'";

        return $query->slaVendorVerifiable()
            ->where(function ($q) use ($diff, $daysUsable, $fallback) {
                $q->where(function ($q2) use ($diff, $daysUsable) {
                    $q2->whereRaw($daysUsable)->whereRaw($diff.' <= sla_vendor_days');
                })->orWhereRaw($fallback);
            });
    }

    public function scopeOutSla($query)
    {
        return $query->slaVerifiable()->whereColumn('tgl_delivered_panthera', '>', 'sla_due_date');
    }

    public function scopeMeetSla($query)
    {
        return $query->slaVerifiable()->whereColumn('tgl_delivered_panthera', '<=', 'sla_due_date');
    }

    /**
     * Exclude rows whose tanggal_manifest is later than tgl_delivered_panthera.
     * Such rows are logically impossible and corrupt any date-based aggregate.
     */
    public function scopeWithConsistentDates($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('tanggal_manifest')
                ->orWhereNull('tgl_delivered_panthera')
                ->orWhereColumn('tanggal_manifest', '<=', 'tgl_delivered_panthera');
        });
    }

    public function scopeInRange($query, int $days)
    {
        if ($days <= 0) {
            return $query;
        }

        // Range dihitung mundur dari tanggal acuan TERAKHIR yang trustworthy (bukan hari ini),
        // agar dashboard tetap menampilkan data aktif meski sumber GSheet belum diperbarui.
        // tgl_delivered_panthera dipakai sebagai jangkar karena kolom itu adalah acuan
        // penyelesaian tunggal, sedangkan MAX(tanggal_manifest) bisa didominasi baris korup.
        $latest = $query->clone()->max('tgl_delivered_panthera');

        if (! $latest) {
            return $query;
        }

        $anchor = ($latest instanceof \DateTimeInterface) ? $latest : Carbon::parse($latest);

        // Baris yang belum selesai (tgl_delivered_panthera kosong) selalu ikut dihitung:
        // tugas panel ini justru memantau kiriman yang masih berjalan.
        return $query->where(function ($q) use ($anchor, $days) {
            $q->whereNull('tgl_delivered_panthera')
                ->orWhere('tgl_delivered_panthera', '>=', $anchor->copy()->subDays($days - 1)->startOfDay());
        });
    }
}

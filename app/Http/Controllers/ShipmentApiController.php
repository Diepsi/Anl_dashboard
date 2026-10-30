<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use Illuminate\Http\Request;

class ShipmentApiController extends Controller
{
    public function staging(Request $request, string $stagging)
    {
        $shipments = Shipment::query()
            ->when(
                $request->query('include_invalid') !== '1',
                fn ($q) => $q->hasValidResi()
            )
            ->inRange($this->rangeDays($request))
            ->where('stagging', $stagging)
            ->orderByDesc('tanggal_manifest')
            ->orderByDesc('id')
            ->limit(50)
            ->get(['id', 'no_resi', 'is_duplicate_no_resi', 'nama_sekolah', 'provinsi', 'kota_kabupaten', 'tanggal_manifest', 'tgl_resi', 'tgl_ho_sartrans', 'status_akhir', 'sla', 'sla_vendor_days', 'sla_due_date', 'tgl_delivered_panthera', 'sla_result', 'sla_vendor_result']);

        return response()->json($shipments);
    }

    public function bottleneck(Request $request)
    {
        $provinsi = $request->query('provinsi');

        if (! $provinsi) {
            return response()->json(['message' => 'Parameter provinsi wajib diisi.'], 422);
        }

        // Default ke mode SLA Panthera ('customer'). Mode 'vendor' hanya aktif jika dikirim eksplisit di query.
        $requestedSlaMode = $request->query('sla_mode');
        $slaMode = $requestedSlaMode === 'vendor' ? 'vendor' : 'customer';

        if ($slaMode === 'vendor') {
            $hasVendorSla = Shipment::query()
                ->hasValidResi()
                ->inRange($this->rangeDays($request))
                ->where('provinsi', $provinsi)
                ->slaVendorVerifiable()
                ->exists();

            if (! $hasVendorSla) {
                $slaMode = 'customer';
            }
        }

        $shipments = Shipment::query()
            ->when(
                $request->query('include_invalid') !== '1',
                fn ($q) => $q->hasValidResi()
            )
            ->inRange($this->rangeDays($request))
            ->when(
                $slaMode === 'vendor',
                fn ($q) => $q->outSlaVendor(),
                fn ($q) => $q->outSla()
            )
            ->where('provinsi', $provinsi)
            ->orderByDesc('tanggal_manifest')
            ->orderByDesc('id')
            ->limit(50)
            ->get(['id', 'no_resi', 'is_duplicate_no_resi', 'nama_sekolah', 'provinsi', 'kota_kabupaten', 'kecamatan', 'tanggal_manifest', 'tgl_resi', 'tgl_ho_sartrans', 'tgl_delivered_panthera', 'sla', 'sla_vendor_days', 'sla_due_date', 'status_akhir', 'sla_vendor_result']);

        return response()->json($shipments);
    }

    /**
     * Whitelist range agar parameter tak dikenal tidak diam-diam mengubah
     * definisi panel. Nilainya harus sama dengan yang diterima DashboardController
     * supaya isi modal selalu cocok dengan angka di panel pemanggilnya.
     */
    protected function rangeDays(Request $request): int
    {
        $range = (int) $request->query('range', 0);

        return in_array($range, [0, 30, 90], true) ? $range : 0;
    }

    public function detail(Request $request)
    {
        $noResi = (string) $request->query('no_resi', '');

        if ($noResi === '') {
            return response()->json(['message' => 'Parameter no_resi wajib diisi.'], 422);
        }

        $shipment = Shipment::query()
            ->where('no_resi', $noResi)
            ->first();

        if (! $shipment) {
            return response()->json(['message' => 'Resi tidak ditemukan.'], 404);
        }

        return response()->json($shipment);
    }
}

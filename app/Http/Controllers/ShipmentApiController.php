<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use Illuminate\Http\Request;

class ShipmentApiController extends Controller
{
    public function staging(Request $request, string $stagging)
    {
        $shipments = Shipment::query()
            ->inRange($this->rangeDays($request))
            ->where('stagging', $stagging)
            ->orderByDesc('tanggal_manifest')
            ->orderByDesc('id')
            ->limit(50)
            ->get(['no_resi', 'is_duplicate_no_resi', 'nama_sekolah', 'provinsi', 'kota_kabupaten', 'tanggal_manifest', 'status_akhir', 'sla', 'sla_due_date', 'completed_date', 'sla_result']);

        return response()->json($shipments);
    }

    public function bottleneck(Request $request)
    {
        $provinsi = $request->query('provinsi');

        if (! $provinsi) {
            return response()->json(['message' => 'Parameter provinsi wajib diisi.'], 422);
        }

        // scopeOutSla() menghitung ulang verdict dari completed_date vs
        // sla_due_date, persis seperti panel pemanggilnya, dan hanya menghitung
        // baris yang benar-benar punya ambang serta tanggal selesai.
        $shipments = Shipment::query()
            ->inRange($this->rangeDays($request))
            ->outSla()
            ->where('provinsi', $provinsi)
            ->orderByDesc('tanggal_manifest')
            ->orderByDesc('id')
            ->limit(50)
            ->get(['no_resi', 'is_duplicate_no_resi', 'nama_sekolah', 'provinsi', 'kota_kabupaten', 'kecamatan', 'tanggal_manifest', 'completed_date', 'sla', 'sla_due_date', 'status_akhir']);

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

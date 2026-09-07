<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use Illuminate\Http\Request;

class ShipmentApiController extends Controller
{
    public function staging(Request $request, string $stagging)
    {
        $shipments = Shipment::query()
            ->where('stagging', $stagging)
            ->orderByDesc('tanggal_manifest')
            ->orderByDesc('id')
            ->limit(50)
            ->get(['no_resi', 'nama_sekolah', 'provinsi', 'kota_kabupaten', 'tanggal_manifest', 'status_akhir', 'sla_result']);

        return response()->json($shipments);
    }

    public function bottleneck(Request $request)
    {
        $provinsi = $request->query('provinsi');

        if (! $provinsi) {
            return response()->json([], 422);
        }

        $shipments = Shipment::query()
            ->where('sla_result', 'Out SLA')
            ->where('provinsi', $provinsi)
            ->orderByDesc('aging')
            ->orderByDesc('tanggal_manifest')
            ->limit(50)
            ->get(['no_resi', 'nama_sekolah', 'kota_kabupaten', 'kecamatan', 'tanggal_manifest', 'aging', 'sla_result', 'status_akhir']);

        return response()->json($shipments);
    }

    public function detail(Request $request, string $noResi)
    {
        $shipment = Shipment::query()
            ->where('no_resi', $noResi)
            ->first();

        if (! $shipment) {
            return response()->json(['message' => 'Resi tidak ditemukan.'], 404);
        }

        return response()->json($shipment);
    }
}
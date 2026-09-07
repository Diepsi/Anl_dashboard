<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Services\ShipmentSyncService;
use Illuminate\Http\Request;

class ShipmentsController extends Controller
{
    public function index(Request $request, ShipmentSyncService $service)
    {
        $status = $request->query('status');
        $provinsi = $request->query('provinsi');
        $stagging = $request->query('stagging');
        $sla = $request->query('sla');
        $search = trim((string) $request->query('search'));

        $query = Shipment::query();

        if ($status) {
            $query->where('status_akhir', $status);
        }

        if ($provinsi) {
            $query->where('provinsi', $provinsi);
        }

        if ($stagging) {
            $query->where('stagging', $stagging);
        }

        if ($sla === 'out') {
            $query->where('sla_result', 'Out SLA');
        } elseif ($sla === 'meet') {
            $query->where('sla_result', 'Meet SLA');
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('no_resi', 'like', "%{$search}%")
                    ->orWhere('nama_sekolah', 'like', "%{$search}%")
                    ->orWhere('nama_penerima', 'like', "%{$search}%");
            });
        }

        $shipments = $query->orderByDesc('tanggal_manifest')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $statuses = Shipment::query()
            ->select('status_akhir')
            ->distinct()
            ->whereNotNull('status_akhir')
            ->orderBy('status_akhir')
            ->pluck('status_akhir');
        $provinces = Shipment::query()
            ->select('provinsi')
            ->distinct()
            ->whereNotNull('provinsi')
            ->orderBy('provinsi')
            ->pluck('provinsi');
        $staggingList = Shipment::query()
            ->select('stagging')
            ->distinct()
            ->whereNotNull('stagging')
            ->orderBy('stagging')
            ->pluck('stagging');

        return view('shipments', compact(
            'status',
            'provinsi',
            'stagging',
            'sla',
            'search',
            'shipments',
            'statuses',
            'provinces',
            'staggingList',
        ));
    }
}
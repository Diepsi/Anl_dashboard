<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Services\ShipmentSyncService;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ShipmentsController extends Controller
{
    public function index(Request $request, ShipmentSyncService $service)
    {
        $status = $request->input('status');
        $provinsi = $request->input('provinsi');
        $stagging = $request->input('stagging');
        $sla = $request->input('sla');
        $search = trim((string) $request->input('search'));

        $shipments = $this->filteredQuery($request)
            ->orderByDesc('tanggal_manifest')
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

    public function export(Request $request)
    {
        $query = $this->filteredQuery($request)
            ->orderByDesc('tanggal_manifest')
            ->orderByDesc('id');

        $columns = [
            'no_resi' => 'No Resi',
            'is_duplicate_no_resi' => 'Duplikat Resi',
            'nomor_redock' => 'Nomor Redock',
            'delivery_order' => 'Delivery Order',
            'nama_sekolah' => 'Nama Sekolah',
            'nama_penerima' => 'Nama Penerima',
            'provinsi' => 'Provinsi',
            'daerah' => 'Daerah',
            'kota_kabupaten' => 'Kota / Kabupaten',
            'kecamatan' => 'Kecamatan',
            'vendor_mm' => 'Vendor MM',
            'kode_funder' => 'Kode Funder',
            'nama_funder' => 'Nama Funder',
            'vendor_lm' => 'Vendor LM',
            'tanggal_manifest' => 'Tanggal Manifest',
            'tgl_ho_sartrans' => 'Tgl HO dari SarTrans',
            'completed_date' => 'Tanggal Selesai',
            'tgl_sampai_kota_tujuan' => 'Tgl Sampai Kota Tujuan',
            'koli' => 'KOLI',
            'sla' => 'SLA (hari)',
            'sla_threshold_days' => 'Ambang SLA (hari)',
            'sla_due_date' => 'Batas SLA',
            'sla_verdict' => 'Status SLA',
            'sla_result' => 'Verdict Sheet',
            'status_akhir' => 'Status Akhir',
            'status_instalasi' => 'Status Instalasi',
            'harga_per_shipment' => 'Harga / Shipment',
            'status_invoice' => 'Status Invoice',
            'stagging' => 'Stagging',
            'bast_tgl_balik' => 'BAST Tgl Balik',
            'bast_tgl_ke_finance' => 'BAST Tgl ke Finance',
            'bast_keterangan' => 'BAST Keterangan',
        ];

        $dateFields = [
            'tanggal_manifest',
            'tgl_ho_sartrans',
            'completed_date',
            'tgl_sampai_kota_tujuan',
            'sla_due_date',
            'bast_tgl_balik',
            'bast_tgl_ke_finance',
        ];

        $numericFields = ['koli', 'sla', 'sla_threshold_days', 'harga_per_shipment'];

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Pengiriman');

        $maxLengths = [];
        foreach (array_values($columns) as $i => $label) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1).'1', $label);
            $maxLengths[$i] = mb_strlen($label);
        }

        $rowIndex = 2;
        $verifiableInExport = 0;

        $query->chunk(1000, function ($shipments) use ($sheet, $columns, $dateFields, &$rowIndex, &$maxLengths, &$verifiableInExport) {
            foreach ($shipments as $ship) {
                if ($ship->sla_verdict !== null) {
                    $verifiableInExport++;
                }

                foreach (array_keys($columns) as $i => $field) {
                    $value = $ship->{$field};
                    $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($i + 1).$rowIndex);

                    if (in_array($field, $dateFields, true) && $value instanceof \DateTimeInterface) {
                        $cell->setValue($value);
                        $length = mb_strlen($value->format('Y-m-d'));
                    } elseif ($field === 'sla_verdict' && $value === null) {
                        // Baris tanpa ambang atau tanpa tanggal selesai tidak boleh
                        // muncul kosong di file yang diambil: kosongnya tidak bisa
                        // dibedakan dari "tidak ada data" begitu difilter di Excel.
                        $cell->setValue('Tidak terverifikasi');
                        $length = mb_strlen('Tidak terverifikasi');
                    } elseif ($field === 'is_duplicate_no_resi') {
                        // Kolom boolean dirender eksplisit: "Ya" lebih jelas daripada
                        // TRUE/FALSE saat difilter di Excel.
                        $cell->setValue($value ? 'Ya' : 'Tidak');
                        $length = mb_strlen('Ya');
                    } elseif ($value === null || $value === '') {
                        $cell->setValue(null);
                        $length = 0;
                    } elseif ($field === 'harga_per_shipment') {
                        $cell->setValue((float) $value);
                        $length = mb_strlen((string) $value);
                    } else {
                        $cell->setValue($value);
                        $length = mb_strlen((string) $value);
                    }

                    $maxLengths[$i] = max($maxLengths[$i], $length);
                }

                $rowIndex++;
            }
        });

        // Baris 1 adalah header, jadi jumlah baris data satu kurang dari baris terakhir.
        $lastDataRow = $rowIndex - 1;
        $dataRowCount = $lastDataRow - 1;
        $totalColumns = count($columns);
        $lastColumnLetter = Coordinate::stringFromColumnIndex($totalColumns);

        foreach ($maxLengths as $i => $len) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i + 1))->setWidth(min($len + 3, 50));
        }

        $headerRange = 'A1:'.$lastColumnLetter.'1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setARGB(Color::COLOR_WHITE);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF0891B2');
        $sheet->getStyle($headerRange)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(22);

        $sheet->freezePane('A2');

        if ($lastDataRow >= 1) {
            $tableRange = 'A1:'.$lastColumnLetter.$lastDataRow;
            $sheet->getStyle($tableRange)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()->setARGB('FFCBD5E1');

            if ($lastDataRow > 1) {
                for ($r = 2; $r <= $lastDataRow; $r++) {
                    if ($r % 2 === 0) {
                        $sheet->getStyle('A'.$r.':'.$lastColumnLetter.$r)->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('FFF8FAFC');
                    }
                }

                foreach ($numericFields as $field) {
                    $col = Coordinate::stringFromColumnIndex(array_search($field, array_keys($columns), true) + 1);
                    $sheet->getStyle($col.'2:'.$col.$lastDataRow)
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                $hargaCol = Coordinate::stringFromColumnIndex(array_search('harga_per_shipment', array_keys($columns), true) + 1);
                $sheet->getStyle($hargaCol.'2:'.$hargaCol.$lastDataRow)
                    ->getNumberFormat()->setFormatCode('#,##0.00');

                foreach ($dateFields as $field) {
                    $col = Coordinate::stringFromColumnIndex(array_search($field, array_keys($columns), true) + 1);
                    $sheet->getStyle($col.'2:'.$col.$lastDataRow)
                        ->getNumberFormat()->setFormatCode('YYYY-MM-DD');
                }

                $sheet->setAutoFilter($tableRange);
            }
        }

        $metaRow = $lastDataRow + 2;
        $filterParts = [];

        if ($status = $request->input('status')) {
            $filterParts[] = 'status: '.$status;
        }
        if ($provinsi = $request->input('provinsi')) {
            $filterParts[] = 'provinsi: '.$provinsi;
        }
        if ($stagging = $request->input('stagging')) {
            $filterParts[] = 'stagging: '.$stagging;
        }
        if ($request->input('sla') === 'out') {
            $filterParts[] = 'SLA: Out SLA';
        } elseif ($request->input('sla') === 'meet') {
            $filterParts[] = 'SLA: Meet SLA';
        }
        if ($search = trim((string) $request->input('search'))) {
            $filterParts[] = 'cari: "'.$search.'"';
        }

        $meta = 'Dibuat '.now()->format('d M Y H:i').' | '.number_format($dataRowCount, 0, ',', '.').' baris';
        if ($filterParts) {
            $meta .= ' | '.implode(' | ', $filterParts);
        }

        $sheet->setCellValue('A'.$metaRow, $meta);
        $sheet->mergeCells('A'.$metaRow.':'.$lastColumnLetter.$metaRow);
        $sheet->getStyle('A'.$metaRow)->getFont()->setItalic(true)->setSize(10)->getColor()->setARGB('FF64748B');

        // Tanpa catatan ini, file yang keluar dari sistem kehilangan semua jejak
        // tingkat keyakinan: kolom "Verdict Sheet" dan "Status SLA" terlihat setara
        // padahal yang kedua dihitung ulang dari ambang yang nyata.
        $sheet->setCellValue('A'.($metaRow + 1), sprintf(
            '"Status SLA" = verdict yang dihitung ulang dari Completed date vs Batas SLA. '
            .'"Verdict Sheet" = teks apa adanya dari sumber. '
            .'Kolom kosong berarti sumber tidak mengisinya, bukan nol. '
            .'Tercakup %.1f%% dari baris di file ini (%d dari %d baris terverifikasi).',
            $dataRowCount > 0 ? 100 * $verifiableInExport / $dataRowCount : 0,
            $verifiableInExport,
            $dataRowCount
        ));
        $sheet->mergeCells('A'.($metaRow + 1).':'.$lastColumnLetter.($metaRow + 1));
        $sheet->getStyle('A'.($metaRow + 1))->getFont()->setItalic(true)->setSize(9)->getColor()->setARGB('FF94A3B8');

        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $temp = tempnam(sys_get_temp_dir(), 'anl_export_');
        $writer->save($temp);

        $filename = 'pengiriman_'.now()->format('Ymd_His').'.xlsx';

        return response()->download($temp, $filename)->deleteFileAfterSend(true);
    }

    private function filteredQuery(Request $request)
    {
        $query = Shipment::query();

        if ($status = $request->input('status')) {
            $query->where('status_akhir', $status);
        }

        if ($provinsi = $request->input('provinsi')) {
            $query->where('provinsi', $provinsi);
        }

        if ($stagging = $request->input('stagging')) {
            $query->where('stagging', $stagging);
        }

        $sla = $request->input('sla');

        if ($sla === 'out') {
            $query->outSla();
        } elseif ($sla === 'meet') {
            $query->meetSla();
        }

        $search = trim((string) $request->input('search'));
        if ($search !== '') {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);

            $query->where(function ($q) use ($escaped) {
                $q->where('no_resi', 'like', "%{$escaped}%")
                    ->orWhere('nama_sekolah', 'like', "%{$escaped}%")
                    ->orWhere('nama_penerima', 'like', "%{$escaped}%");
            });
        }

        return $query;
    }
}

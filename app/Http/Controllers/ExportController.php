<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Response;

class ExportController extends Controller
{
    public function exportToExcel(Request $request)
    {
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $service_unit_name = $request->input('service_unit_name');

        $data = DB::connection('sqlsrv')->table('vRegistration as r')
            ->distinct()
            ->select(
                'r.ServiceUnitName',
                'r.RegistrationNo',
                'r.MedicalNo',
                'r.PatientName',
                'r.CustomerType',
                'r.ChargeClassName',
                'r.ParamedicCode',
                'r.ParamedicName',
                DB::raw("CASE 
                            WHEN cv.PlanDischargeTime IS NULL
                            THEN CAST(cv.PlanDischargeDate AS VARCHAR) + ' ' + CAST(cv.PlanDischargeTime AS VARCHAR)
                            ELSE CAST(cv.PlanDischargeDate AS DATETIME) + CAST(cv.PlanDischargeTime AS TIME)
                        END AS RencanaPulang"),
                DB::raw("(SELECT MAX(ProposedDate) 
                            FROM PatientChargesHD
                            WHERE VisitID=cv.VisitID 
                                AND GCTransactionStatus<>'X121^999' 
                                AND GCTransactionStatus NOT IN ('X121^001','X121^002','X121^003')
                                AND HealthcareServiceUnitID IN (82,83,99,138,140)
                                AND ProposedDate IS NOT NULL) AS Jangdik"),
                DB::raw("(SELECT MAX(ProposedDate) 
                            FROM PatientChargesHD
                            WHERE VisitID=cv.VisitID 
                                AND GCTransactionStatus<>'X121^999' 
                                AND GCTransactionStatus NOT IN ('X121^001','X121^002','X121^003')
                                AND HealthcareServiceUnitID NOT IN (82,83,99,138,140,101,137)
                                AND ProposedDate IS NOT NULL) AS Keperawatan"),
                DB::raw("(SELECT MAX(ProposedDate) 
                            FROM PatientChargesHD
                            WHERE VisitID=cv.VisitID 
                                AND GCTransactionStatus<>'X121^999' 
                                AND GCTransactionStatus NOT IN ('X121^001','X121^002','X121^003')
                                AND HealthcareServiceUnitID IN (101,137)
                                AND ProposedDate IS NOT NULL) AS Farmasi"),
                DB::raw("(SELECT MAX(PrintedDate) 
                            FROM ReportPrintLog 
                            WHERE ReportID=7012 
                                AND ReportParameter = CONCAT('RegistrationID = ',r.RegistrationID)) AS SelesaiBilling"),
                DB::raw("CAST(cv.DischargeDate AS DATETIME) + CAST(cv.DischargeTime AS TIME) AS DischargeDateTime"),
                'cv.RoomDischargeDateTime',
                DB::raw("CONCAT(DATEDIFF(SECOND, 
                            CASE 
                                WHEN cv.PlanDischargeTime IS NULL
                                THEN CAST(cv.PlanDischargeDate AS VARCHAR) + ' ' + CAST(cv.PlanDischargeTime AS VARCHAR)
                                ELSE CAST(cv.PlanDischargeDate AS DATETIME) + CAST(cv.PlanDischargeTime AS TIME)
                            END, cv.ActualDischargeDateTime) / 3600, ':',
                            FORMAT((DATEDIFF(SECOND, 
                                CASE 
                                    WHEN cv.PlanDischargeTime IS NULL
                                    THEN CAST(cv.PlanDischargeDate AS VARCHAR) + ' ' + CAST(cv.PlanDischargeTime AS VARCHAR)
                                    ELSE CAST(cv.PlanDischargeDate AS DATETIME) + CAST(cv.PlanDischargeTime AS TIME)
                                END, cv.ActualDischargeDateTime) % 3600) / 60, '00')) AS rpul_roomclose")
            )
            ->leftJoin('vPatient as p', 'p.MRN', '=', 'r.MRN')
            ->leftJoin('PatientNotes as pn', 'pn.MRN', '=', 'r.MRN')
            ->leftJoin('ConsultVisit as cv', 'cv.VisitID', '=', 'r.VisitID')
            ->leftJoin('StandardCode as sc', 'sc.StandardCodeID', '=', 'cv.GCPlanDischargeNotesType')
            ->leftJoin('PatientVisitNote as pvn', function ($join) {
                $join->on('pvn.VisitID', '=', 'cv.VisitID')
                    ->whereIn('pvn.GCNoteType', ['X312^001', 'X312^002', 'X312^003', 'X312^004', 'X312^005', 'X312^006']);
            })
            ->whereBetween('r.DischargeDate', [$start_date, $end_date])
            ->whereNotNull('cv.PlanDischargeDate')
            ->where('r.GCRegistrationStatus', '<>', 'X020^006');

        // Tambahkan filter berdasarkan ruang perawatan jika diisi
        if ($service_unit_name) {
            $data->where('r.ServiceUnitName', $service_unit_name);
        }

        $data = $data->orderBy('r.PatientName')->get();

        // Membuat spreadsheet baru
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Menambahkan header kolom
        $sheet->setCellValue('A1', 'PASIEN');
        $sheet->setCellValue('B1', 'RUANG PERAWATAN');
        $sheet->setCellValue('C1', 'RENCANA PULANG');
        $sheet->setCellValue('D1', 'JANGDIK');
        $sheet->setCellValue('E1', 'KEPERAWATAN');
        $sheet->setCellValue('F1', 'FARMASI');
        $sheet->setCellValue('G1', 'SELESAI BILLING');
        $sheet->setCellValue('H1', 'WAKTU PASIEN PULANG');
        $sheet->setCellValue('I1', 'RENCANA PULANG - PASIEN PULANG (hh:mm)');

        // Menambahkan data ke dalam file excel
        $row = 2; // Mulai dari baris kedua
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $item->PatientName);
            $sheet->setCellValue('B' . $row, $item->ServiceUnitName);
            $sheet->setCellValue('C' . $row, $item->RencanaPulang);
            $sheet->setCellValue('D' . $row, $item->Jangdik);
            $sheet->setCellValue('E' . $row, $item->Keperawatan);
            $sheet->setCellValue('F' . $row, $item->Farmasi);
            $sheet->setCellValue('G' . $row, $item->SelesaiBilling);
            $sheet->setCellValue('H' . $row, $item->DischargeDateTime);
            $sheet->setCellValue('I' . $row, $item->rpul_roomclose);
            $row++;
        }

        // Menyimpan file Excel
        $writer = new Xlsx($spreadsheet);
        $formatted_start_date = date('Y-m-d', strtotime($start_date));
        $formatted_end_date = date('Y-m-d', strtotime($end_date));
        $fileName = 'Rekap_Kepulangan_Pasien_' . $formatted_start_date . ' to ' . $formatted_end_date . '.xlsx';

        // Mengirim file ke browser
        return response()->stream(
            function () use ($writer) {
                $writer->save('php://output');
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment;filename="' . $fileName . '"',
                'Cache-Control' => 'max-age=0',
            ]
        );
    }
}

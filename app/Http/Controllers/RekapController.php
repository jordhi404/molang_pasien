<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\ip_mappings;

class RekapController extends Controller
{
    // public function showDashboardRekap(Request $request) {
    //     $data_type = $request->input('data_type');
    //     $start_date = $request->input('start_date');
    //     $end_date = $request->input('end_date');

    //     $query = null;

    //     // Menampilkan data berdasarkan pilihan.
    //     if ($data_type == 'pasien') {
    //         $query = DB::table('patient_transitions')
    //                 ->select('MedicalNo', 'PatientName', 'ServiceUnitName', 'CustomerType', 'ChargeClassName', 'RencanaPulang', 'Keperawatan', 'Farmasi', 'Kasir', 'SelesaiBilling')
    //                 ->whereBetween('RencanaPulang', [$start_date, $end_date])
    //                 ->paginate(10);
    //     } else {
    //         $query = DB::table('bed_cleaning_records')
    //                 ->select('BedCode', 'ServiceUnitName', 'BedUnoccupiedInReality', 'ExpectedDoneCleaning', 'DoneCleaningInReality', 'CleaningDuration')
    //                 ->whereBetween('BedUnoccupiedInReality', [$start_date, $end_date])
    //                 ->paginate(10);
    //     }

    //     return view('Rekap.rekap', ['data' => $query, 'data_type' => $data_type]);
    // }

    public function showRekap(Request $request) {
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $service_unit_name = $request->input('service_unit_name');
        $ipAddress = $request->ip();
        $unit = ip_mappings::on('pgsql')->where('ip_address', $ipAddress)->value('unit');

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

        $data = $data->orderBy('r.PatientName')->paginate(10);

        // Perhitungan presentase berdasarkan rpul_roomclose
        foreach ($data as $patient) {
            // Konversi rpul_roomclose dari format "hh:mm" ke menit
            $timeParts = explode(':', $patient->rpul_roomclose);
            $timeInMinutes = (int)$timeParts[0] * 60 + (int)$timeParts[1]; // mengonversi ke menit
            
            $standardTimeInMinutes = 60; // 1 jam standar

            // Menghitung keterlambatan dalam menit
            $delayMinutes = max(0, $timeInMinutes - $standardTimeInMinutes);
            
            // Menghitung presentase
            $percentage = 100 - floor($delayMinutes / 5) * 2;
            $percentage = max(0, $percentage);

            // Menambahkan data presentase ke setiap pasien
            $patient->performancePercentage = $percentage;
        }

        $available_units = DB::connection('pgsql')
                        ->table('service_units')
                        ->where('unit_service_name', '!=', 'TEKNOLOGI INFORMASI')
                        ->pluck('unit_service_name');
        
        $CustomerTypeColors = DB::connection('pgsql')->table('customer_type_colors')->get();

        Log::info('IP client: ' . $ipAddress);
        Log::info('unit IP address: ' . $unit);
                        
        return view('Rekap.rkpData', compact('data', 'available_units', 'CustomerTypeColors'));
    }
}

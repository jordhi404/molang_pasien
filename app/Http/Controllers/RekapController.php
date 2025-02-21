<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use carbon\carbon;
use App\Models\ip_mappings;
use Illuminate\Support\Carbon as SupportCarbon;

class RekapController extends Controller
{
    public function showRekap(Request $request) {
        $start_date = $request->input('start_date', now()->subDay());
        $end_date = $request->input('end_date', now()->subDay());
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
                DB::raw("FORMAT(cv.PlanDischargeDate, 'dd/MM/yyyy') AS PlanDischargeDate"),
                'cv.PlanDischargeTime',
                'cv.VisitID',
                DB::raw("(SELECT FORMAT(MAX(phd.CreatedDate), 'dd/MM/yyyy HH:mm') 
                            FROM PatientVisitNote phd
                            WHERE cv.VisitID = phd.VisitID 
                                AND phd.IsDeleted = 0
                                AND phd.GCPatientNoteType IN ('X011^011', 'X011^004')) AS DokterVisit"),
                DB::raw("CASE 
                            WHEN cv.PlanDischargeTime IS NULL 
                                THEN CAST(cv.PlanDischargeDate AS VARCHAR) + ' ' + CAST(cv.PlanDischargeTime AS VARCHAR) 
                                ELSE CAST(cv.PlanDischargeDate AS DATETIME) + CAST(cv.PlanDischargeTime AS TIME) 
                            END AS RencanaPulang"),
                DB::raw("(SELECT FORMAT(MAX(ProposedDate), 'dd/MM/yyyy HH:mm')
                            FROM PatientChargesHD
                            WHERE VisitID=cv.VisitID 
                                AND ProposedDate >= cv.PlanDischargeDate
                                AND GCTransactionStatus<>'X121^999' 
                                AND GCTransactionStatus NOT IN ('X121^001','X121^002','X121^003')
                                AND HealthcareServiceUnitID IN (82,83,99,138,140)
                                AND ProposedDate IS NOT NULL) AS Jangdik"),
                DB::raw("(SELECT FORMAT(MAX(ProposedDate), 'dd/MM/yyyy HH:mm')
                            FROM PatientChargesHD
                            WHERE VisitID=cv.VisitID 
                                AND GCTransactionStatus<>'X121^999'
                                AND ProposedDate >= cv.PlanDischargeDate 
                                AND GCTransactionStatus NOT IN ('X121^001','X121^002','X121^003')
                                AND HealthcareServiceUnitID NOT IN (82,83,99,138,140,101,137)
                                AND ProposedDate IS NOT NULL) AS Keperawatan"),
                DB::raw("(SELECT FORMAT(MAX(ProposedDate), 'dd/MM/yyyy HH:mm')
                            FROM PatientChargesHD
                            WHERE VisitID=cv.VisitID 
                                AND ProposedDate >= cv.PlanDischargeDate
                                AND GCTransactionStatus<>'X121^999' 
                                AND GCTransactionStatus NOT IN ('X121^001','X121^002','X121^003')
                                AND HealthcareServiceUnitID IN (101,137)
                                AND ProposedDate IS NOT NULL) AS Farmasi"),
                DB::raw("(SELECT FORMAT(MAX(CreatedDate), 'dd/MM/yyyy HH:mm') 
                            FROM PatientBill 
                            WHERE RegistrationID = cv.RegistrationID 
                                AND GCTransactionStatus <> 'X121^999' 
                                AND CreatedDate IS NOT NULL) AS Billing"),
                DB::raw("(SELECT FORMAT(MAX(CreatedDate), 'dd/MM/yyyy HH:mm') 
                            FROM PatientPaymentHd 
                            WHERE RegistrationID = cv.RegistrationID 
                                AND GCTransactionStatus <> 'X121^999' 
                                AND CreatedDate IS NOT NULL) AS Bayar"),
                DB::raw("(SELECT TOP 1 TotalPatientBillAmount 
                            FROM PatientPaymentHd 
                            WHERE RegistrationID = cv.RegistrationID 
                                AND GCTransactionStatus <> 'X121^999' 
                                AND PaymentDate IS NOT NULL 
                                ORDER BY PaymentID DESC) AS Excess"),
                DB::raw("(SELECT FORMAT(MAX(PrintedDate), 'dd/MM/yyyy HH:mm') 
                            FROM ReportPrintLog 
                            WHERE ReportID = 7012 
                                AND ReportParameter = CONCAT('RegistrationID = ', r.RegistrationID)) AS BolehPulang"),
                DB::raw("FORMAT((CAST(cv.DischargeDate AS DATETIME) + CAST(cv.DischargeTime AS TIME)), 'dd/MM/yyyy HH:mm') AS DischargeDateTime"),
                DB::raw("FORMAT(cv.RoomDischargeDateTime, 'dd/MM/yyyy HH:mm') AS RoomDischargeDateTime"),
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
            
            // Menghitung presentase
            $percentage = max(0, ($timeInMinutes / $standardTimeInMinutes) * 100);

            // Menambahkan data presentase ke setiap pasien
            $patient->performancePercentage = round($percentage);

            $standardTimes = DB::connection('pgsql')
                ->table('standard_times')
                ->whereIn('keterangan', ['Tunggu Jangdik', 'Tunggu Keperawatan', 'Tunggu Farmasi', 'Tunggu Kasir'])
                ->get()->keyBy('keterangan');

            // Ambil waktu standar dari data yang diambil dari tabel standard_times
            $jangdikStandardTime = isset($standardTimes['Tunggu Jangdik']) ? $standardTimes['Tunggu Jangdik']->standard_time : 0;
            $keperawatanStandardTime = isset($standardTimes['Tunggu Keperawatan']) ? $standardTimes['Tunggu Keperawatan']->standard_time : 0;
            $farmasiStandardTime = isset($standardTimes['Tunggu Farmasi']) ? $standardTimes['Tunggu Farmasi']->standard_time : 0;
            $billingStandardTime = isset($standardTimes['Tunggu Kasir']) ? $standardTimes['Tunggu Kasir']->standard_time : 0;

            // Menghitung durasi Jangdik.
            if ($patient->Jangdik && $patient->RencanaPulang) {
                $jangdikTime = Carbon::createFromFormat('d/m/Y H:i', $patient->Jangdik);
                $rencanaPulangTime = Carbon::parse($patient->RencanaPulang);
                $JangdikDuration = max(0, $rencanaPulangTime->diffInMinutes($jangdikTime)); // durasi dalam menit
                if ($JangdikDuration !== null) {
                    // Menghitung jam dan menit
                    $hours = floor($JangdikDuration / 60); // Jam
                    $minutes = $JangdikDuration % 60; // Menit
                    
                    // Format durasi menjadi "X jam Y menit"
                    $patient->JangdikDurationFormatted = "{$hours} jam {$minutes} menit";
                    // Bandingkan dengan waktu standar
                    $patient->JangdikColor = $JangdikDuration > $jangdikStandardTime ? 'red' : 'green';
                } else {
                    $patient->JangdikDurationFormatted = null;
                }
            }
    
            // Menghitung durasi Keperawatan
            if ($patient->Keperawatan && $patient->RencanaPulang) {
                $keperawatanTime = Carbon::createFromFormat('d/m/Y H:i', $patient->Keperawatan);
                $rencanaPulangTime = Carbon::parse($patient->RencanaPulang);
                $KeperawatanDuration = max(0, $rencanaPulangTime->diffInMinutes($keperawatanTime)); // durasi dalam menit
                if ($KeperawatanDuration !== null) {
                    $hours = floor($KeperawatanDuration / 60); // Jam
                    $minutes = $KeperawatanDuration % 60; // Menit
                    $patient->KeperawatanDurationFormatted = "{$hours} jam {$minutes} menit";
                    // Bandingkan dengan waktu standar
                    $patient->KeperawatanColor = $KeperawatanDuration > $keperawatanStandardTime ? 'red' : 'green';
                } else {
                    $patient->KeperawatanDurationFormatted = null;
                }
            }
    
            // Menghitung durasi Farmasi
            if ($patient->Farmasi && $patient->RencanaPulang) {
                $farmasiTime = Carbon::createFromFormat('d/m/Y H:i', $patient->Farmasi);
                $rencanaPulangTime = Carbon::parse($patient->RencanaPulang);
                $FarmasiDuration = max(0, $rencanaPulangTime->diffInMinutes($farmasiTime)); // durasi dalam menit
                if ($FarmasiDuration !== null) {
                    $hours = floor($FarmasiDuration / 60); // Jam
                    $minutes = $FarmasiDuration % 60; // Menit
                    $patient->FarmasiDurationFormatted = "{$hours} jam {$minutes} menit";
                    // Bandingkan dengan waktu standar
                    $patient->FarmasiColor = $FarmasiDuration > $farmasiStandardTime ? 'red' : 'green';
                } else {
                    $patient->FarmasiDurationFormatted = null;
                }
            }

            //Menghitung durasi Billing
            if ($patient->Billing) {
                $billingTime = Carbon::createFromFormat('d/m/Y H:i', $patient->Billing);
                if(!$patient->Keperawatan) {
                    $rpl = Carbon::parse($patient->RencanaPulang);
                    $billingDuration = max(0, $rpl->diffInMinutes($billingTime));
                } else {
                    $keperawatanTime = Carbon::createFromFormat('d/m/Y H:i', $patient->Keperawatan);
                    $billingDuration = max(0, $keperawatanTime->diffInMinutes($billingTime));
                }
                if ($billingDuration !== null) {
                    $hours = floor($billingDuration / 60); // Jam
                    $minutes = $billingDuration % 60; // Menit
                    $patient->BillingDurationFormatted = "{$hours} jam {$minutes} menit";
                    // Bandingkan dengan waktu standar
                    $patient->BillingColor = $billingDuration > $billingStandardTime ? 'red' : 'green';
                } else {
                    $patient->BillingDurationFormatted = null;
                }
            }
            
            //Menghitung durasi cetak SIP
            if ($patient->BolehPulang) {
                $cetakTime = Carbon::createFromFormat('d/m/Y H:i', $patient->BolehPulang);
                if($patient->Billing){
                    $billingTime = Carbon::createFromFormat('d/m/Y H:i', $patient->Billing);
                    $cetakDuration = max(0, $billingTime->diffInMinutes($cetakTime)); // durasi dalam menit
                } else {
                    $rpl = Carbon::parse($patient->RencanaPulang);
                    $cetakDuration = max(0, $rpl->diffInMinutes($cetakTime)); // durasi dalam menit
                }
                if ($cetakDuration !== null) {
                    $hours = floor($cetakDuration / 60); // Jam
                    $minutes = $cetakDuration % 60; // Menit
                    $patient->cetakDurationFormatted = "{$hours} jam {$minutes} menit";
                } else {
                    $patient->cetakDurationFormatted = null;
                }
            } else {
                $patient->cetakDurationFormatted = 'Data bayar tidak ada';
            }
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

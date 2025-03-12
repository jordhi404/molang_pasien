<?php

namespace App\Models;

use Exception;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class Patient extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';
    protected $table = 'Patient';
    protected $primaryKey = 'MRN';

    public function bed()
    {
        return $this->hasOne(Bed::class, 'MRN', 'MRN');
    }

    /* FUNCTION UNTUK MENGAMBIL DATA PASIEN. */
    public static function getPatientData()
    {
        $max = now()->subMinutes(2);

        // Hapus lock lama jika ada (karena crash atau error)
        DB::connection('pgsql')->table('process_lock')
            ->where('process_name', 'data_update')
            ->where('locked_at', '<=', $max)
            ->delete();

        $lockExists = DB::connection('pgsql')->table('process_lock')
            ->where('process_name', 'data_update')
            ->where('locked_at', '>', $max)
            ->exists();

        Log::info('Cek apakah sudah ada lock: ' . json_encode($lockExists));

        if ($lockExists) {
            // Jika ada yang melakukan proses, hentikan eksekusi
            Log::info('Proses data update sedang berlangsung....');
            return response()->json([
                'status' => 'locked',
                'message' => 'Data dalam proses update.'
            ]);
        }

        // Tandai proses sedang berlangsung.
        DB::connection('pgsql')->table('process_lock')->insert([
            'process_name' => 'data_update',
            'locked_at' => now(),
            'ip_address' => request()->ip(), // IP user yang melakukan lock
        ]);
        Log::info('Lock dibuat pada: ' . now());

        /* MEMULAI PROSES PEMBAHARUAN DATA */
        try {
            // Mulai transaksi.
            DB::connection('pgsql')->beginTransaction();

                // Ambil data dari sqlsrv.
                $patients_data = DB::connection('sqlsrv')
                -> select("
                WITH Dashboard_CTE AS (
                    SELECT DISTINCT 
                        a.RegistrationNo,
                        r.ServiceUnitName,
                        a.BedCode,
                        a.MedicalNo,
                        a.PatientName,
                        r.CustomerType,    
                        RencanaPulang = CAST(cv.PlanDischargeDate AS VARCHAR) + ' ' + CAST(cv.PlanDischargeTime AS VARCHAR),
                        CatRencanaPulang = cv.PlanDischargeNotes,                      
                        TungguJangdik = 
                            (SELECT TOP 1 TransactionNo 
                            FROM PatientChargesHD
                            WHERE VisitID=cv.VisitID 
                            AND GCTransactionStatus<>'X121^999' 
                            AND GCTransactionStatus IN ('X121^001')
                            AND HealthcareServiceUnitID IN (82,83,99,138,140)
                            ORDER BY TestOrderID ASC),
                        Keperawatan =
                            (SELECT TOP 1 TransactionNo 
                            FROM PatientChargesHD
                            WHERE VisitID=cv.VisitID 
                            AND GCTransactionStatus<>'X121^999' 
                            AND GCTransactionStatus IN ('X121^001')
                            AND HealthcareServiceUnitID NOT IN (82,83,99,138,140,101,137)
                            ORDER BY TestOrderID ASC),
                        TungguFarmasi = 
                            (SELECT TOP 1 TransactionNo 
                            FROM PatientChargesHD
                            WHERE VisitID=cv.VisitID 
                            AND GCTransactionStatus<>'X121^999' 
                            AND GCTransactionStatus IN ('X121^001')
                            AND HealthcareServiceUnitID IN (101,137)
                            ORDER BY TestOrderID ASC),
                        RegistrationStatus = 
                            (SELECT TOP 1 IsLockDownNEW
                            FROM RegistrationStatusLog 
                            WHERE RegistrationID = a.RegistrationID 
                            ORDER BY ID DESC),
                        OutStanding =
                            (SELECT COUNT(DISTINCT GCTransactionStatus) 
                            FROM PatientChargesHD 
                            WHERE VisitID=cv.VisitID 
                            AND GCTransactionStatus IN ('X121^001')),
                        Billing =
                            (SELECT MAX(CreatedDate) 
                                FROM PatientBill 
                                WHERE RegistrationID = cv.RegistrationID 
                                    AND GCTransactionStatus <> 'X121^999' 
                                    AND CreatedDate IS NOT NULL),
                        Bayar =
                            (SELECT MAX(CreatedDate) 
                                FROM PatientPaymentHd 
                                WHERE RegistrationID = cv.RegistrationID 
                                    AND GCTransactionStatus <> 'X121^999' 
                                    AND CreatedDate IS NOT NULL),
                        BolehPulang = 
                            (SELECT MAX(PrintedDate) 
                                FROM ReportPrintLog 
                                WHERE ReportID = 7012 
                                    AND ReportParameter = CONCAT('RegistrationID = ', r.RegistrationID))
                    FROM vBed a
                    LEFT JOIN vPatient p ON p.MRN = a.MRN
                    LEFT JOIN PatientNotes pn ON pn.MRN = a.MRN
                    LEFT JOIN vRegistration r ON r.RegistrationID = a.RegistrationID
                    LEFT JOIN ConsultVisit cv ON cv.VisitID = r.VisitID
                    LEFT JOIN StandardCode sc ON sc.StandardCodeID = cv.GCPlanDischargeNotesType
                    LEFT JOIN PatientVisitNote pvn ON pvn.VisitID = cv.VisitID 
                        AND pvn.GCNoteType IN ('X312^001', 'X312^002', 'X312^003', 'X312^004', 'X312^005', 'X312^006')
                    WHERE a.IsDeleted = 0 
                    AND a.RegistrationID IS NOT NULL
                    AND cv.PlanDischargeDate IS NOT NULL
                    AND r.GCRegistrationStatus <> 'X020^006'
                )
                SELECT 
                    ServiceUnitName,
                    BedCode,
                    MedicalNo,
                    PatientName,
                    CustomerType,
                    RencanaPulang,
                    CatRencanaPulang,
                    Keperawatan,
                    TungguJangdik,
                    TungguFarmasi,
                    CASE
                        WHEN Keperawatan IS NOT NULL AND TungguJangdik IS NULL AND TungguFarmasi IS NOT NULL THEN 'Tunggu Keperawatan'
                        WHEN Keperawatan IS NOT NULL AND TungguJangdik IS NULL AND TungguFarmasi IS NULL THEN 'Tunggu Keperawatan'
                        WHEN TungguJangdik IS NOT NULL AND Keperawatan IS NOT NULL AND TungguFarmasi IS NOT NULL THEN 'Tunggu Jangdik'
                        WHEN TungguFarmasi IS NOT NULL AND Keperawatan IS NULL AND TungguJangdik IS NULL THEN 'Tunggu Farmasi'
                        WHEN RegistrationStatus = 0 AND OutStanding > 0 AND Billing IS NULL AND Bayar IS NULL THEN 'Billing'
                        WHEN RegistrationStatus = 1 AND OutStanding = 0 AND Billing IS NULL AND Bayar IS NULL THEN 'Billing'
                        WHEN RegistrationStatus = 1 AND OutStanding = 0 AND Billing IS NOT NULL AND Bayar IS NULL THEN 'Billing'
                        WHEN RegistrationStatus = 1 AND OutStanding = 0 AND Billing IS NOT NULL AND Bayar IS NOT NULL THEN 'Bayar/Piutang'
                    END AS Keterangan,
                    Billing,
                    Bayar,
                    BolehPulang
                FROM Dashboard_CTE
            ");

            $data_batch = [];
            $valid_time = now()->subSeconds(120); // Usia data 120 detik.

            // Simpan ke tabel temp_data_ajax di pgsql.
            foreach ($patients_data as $data) {
                // Untuk mencegah duplikasi data.
                $exists = DB::connection('pgsql')->table('temp_data_ajax')
                        ->whereExists(function ($query) use ($data, $valid_time) {
                            $query->select(DB::raw(1))
                                ->from('temp_data_ajax')
                                ->where('ServiceUnitName', $data->ServiceUnitName)
                                ->where('MedicalNo', $data->MedicalNo)
                                ->where('BedCode', $data->BedCode)
                                ->where('updated_at', '>=', $valid_time); // Cek apakah masih valid.
                        })->exists();

                        Log::info('Valid time: ' . $valid_time);

                if(!$exists) {
                    $data_batch[] = [
                        'ServiceUnitName' => $data->ServiceUnitName,
                        'BedCode' => $data->BedCode,
                        'MedicalNo' => $data->MedicalNo,
                        'PatientName' => $data->PatientName,
                        'CustomerType' => $data->CustomerType,
                        'RencanaPulang' => $data->RencanaPulang,
                        'CatRencanaPulang' => $data->CatRencanaPulang,
                        'Keperawatan' => $data->Keperawatan,
                        'TungguJangdik' => $data->TungguJangdik,
                        'TungguFarmasi' => $data->TungguFarmasi,
                        'Keterangan' => $data->Keterangan,
                        'Billing' => $data->Billing,
                        'Bayar' => $data->Bayar,
                        'BolehPulang' => $data->BolehPulang,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'update_by' => request()->ip(),
                    ];
                }
            }

            if(!empty($data_batch)) {
                DB::connection('pgsql')->table('temp_data_ajax')->insert($data_batch);
            }

            DB::connection('pgsql')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql')->rollBack();
            Log::error('Error dalam proses data: ' . $e->getMessage());
        } finally {
            $updatedData = DB::connection('pgsql')->table('temp_data_ajax')->get();

            DB::connection('pgsql')->table('process_lock')->where('process_name', 'data_update')->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'update selesai',
                'data' => $updatedData
            ]);
        }
    }
}
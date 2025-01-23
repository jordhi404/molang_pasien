<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
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

    /* CONNECTION KE DATABASE SQLSRV UNTUK MENGAMBIL DATA PASIEN. */
    public static function getPatientData()
    {
        $cacheKey = 'patientRanapLocal';

        return Cache::remember($cacheKey, 90, function() {     // Usia cache 90 detik (1,5 menit).
            return DB::connection('sqlsrv')
                -> select("
                WITH Dashboard_CTE AS (
                    SELECT DISTINCT 
                        a.RegistrationNo,
                        r.ServiceUnitName,
                        a.BedCode,
                        a.MedicalNo,
                        a.PatientName,
                        r.CustomerType,
                        r.ChargeClassName,
                        RencanaPulang = 
                            CASE 
                                WHEN cv.PlanDischargeTime IS NULL
                                    THEN CAST(cv.PlanDischargeDate AS VARCHAR) + ' ' + CAST(cv.PlanDischargeTime AS VARCHAR)
                                ELSE CAST(cv.PlanDischargeDate AS VARCHAR) + ' ' + CAST(cv.PlanDischargeTime AS VARCHAR)
                            END,
                        CatRencanaPulang = cv.PlanDischargeNotes,
                        JangdikEndTime = 
                            (SELECT FORMAT(MAX(ProposedDate), 'dd/MM/yyyy HH:mm')
                                FROM PatientChargesHD
                                WHERE VisitID=cv.VisitID 
                                    AND ProposedDate >= cv.PlanDischargeDate
                                    AND GCTransactionStatus<>'X121^999' 
                                    AND GCTransactionStatus NOT IN ('X121^001','X121^002','X121^003')
                                    AND HealthcareServiceUnitID IN (82,83,99,138,140)
                                    AND ProposedDate IS NOT NULL),
                        KeperawatanEndTime = 
                            (SELECT FORMAT(MAX(ProposedDate), 'dd/MM/yyyy HH:mm')
                                FROM PatientChargesHD
                                WHERE VisitID=cv.VisitID 
                                    AND GCTransactionStatus<>'X121^999'
                                    AND ProposedDate >= cv.PlanDischargeDate 
                                    AND GCTransactionStatus NOT IN ('X121^001','X121^002','X121^003')
                                    AND HealthcareServiceUnitID NOT IN (82,83,99,138,140,101,137)
                                    AND ProposedDate IS NOT NULL),
                        FarmasiEndTime = 
                            (SELECT FORMAT(MAX(ProposedDate), 'dd/MM/yyyy HH:mm')
                                FROM PatientChargesHD
                                WHERE VisitID=cv.VisitID 
                                    AND ProposedDate >= cv.PlanDischargeDate
                                    AND GCTransactionStatus<>'X121^999' 
                                    AND GCTransactionStatus NOT IN ('X121^001','X121^002','X121^003')
                                    AND HealthcareServiceUnitID IN (101,137)
                                    AND ProposedDate IS NOT NULL),
                        TungguJangdik = 
                            (SELECT TOP 1 TransactionNo 
                            FROM PatientChargesHD
                            WHERE VisitID=cv.VisitID 
                            AND GCTransactionStatus<>'X121^999' 
                            AND GCTransactionStatus IN ('X121^001','X121^002','X121^003')
                            AND HealthcareServiceUnitID IN (82,83,99,138,140)
                            ORDER BY TestOrderID ASC),
                        Keperawatan =
                            (SELECT TOP 1 TransactionNo 
                            FROM PatientChargesHD
                            WHERE VisitID=cv.VisitID 
                            AND GCTransactionStatus<>'X121^999' 
                            AND GCTransactionStatus IN ('X121^001','X121^002','X121^003')
                            AND HealthcareServiceUnitID NOT IN (82,83,99,138,140,101,137)
                            ORDER BY TestOrderID ASC),
                        TungguFarmasi = 
                            (SELECT TOP 1 TransactionNo 
                            FROM PatientChargesHD
                            WHERE VisitID=cv.VisitID 
                            AND GCTransactionStatus<>'X121^999' 
                            AND GCTransactionStatus IN ('X121^001','X121^002','X121^003')
                            AND HealthcareServiceUnitID IN (101,137)
                            ORDER BY TestOrderID ASC),
                        RegistrationStatus = 
                            (SELECT TOP 1 IsLockDownNEW
                            FROM RegistrationStatusLog 
                            WHERE RegistrationID = a.RegistrationID 
                            ORDER BY ID DESC),
                        OutStanding =
                            (SELECT DISTINCT COUNT(GCTransactionStatus) 
                            FROM PatientChargesHD 
                            WHERE VisitID=cv.VisitID 
                            AND GCTransactionStatus IN ('X121^001','X121^002','X121^003')),
                        Keterangan =
                        CASE 
                            WHEN sc.StandardCodeName = '' OR sc.StandardCodeName IS NULL
                                THEN ''
                            ELSE sc.StandardCodeName
                        END,
                        Billing =
                            (SELECT FORMAT(MAX(CreatedDate), 'dd/MM/yyyy HH:mm') 
                                FROM PatientBill 
                                WHERE RegistrationID = cv.RegistrationID 
                                    AND GCTransactionStatus <> 'X121^999' 
                                    AND CreatedDate IS NOT NULL),
                        Bayar =
                            (SELECT FORMAT(MAX(CreatedDate), 'dd/MM/yyyy HH:mm') 
                                FROM PatientPaymentHd 
                                WHERE RegistrationID = cv.RegistrationID 
                                    AND GCTransactionStatus <> 'X121^999' 
                                    AND CreatedDate IS NOT NULL),
                        Excess = 
                            (SELECT TOP 1 TotalPatientBillAmount 
                                FROM PatientPaymentHd 
                                WHERE RegistrationID = cv.RegistrationID 
                                    AND GCTransactionStatus <> 'X121^999' 
                                    AND PaymentDate IS NOT NULL 
                                    ORDER BY PaymentID DESC),
                        BolehPulang = 
                            (SELECT FORMAT(MAX(PrintedDate), 'dd/MM/yyyy HH:mm') 
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
                    AND r.GCRegistrationStatus <> 'X020^006' -- Pendaftaran Tidak DiBatalkan
                )
                SELECT 
                    RegistrationNo,
                    ServiceUnitName,
                    BedCode,
                    MedicalNo,
                    PatientName,
                    CustomerType,
                    ChargeClassName,
                    RencanaPulang,
                    CatRencanaPulang,
                    JangdikEndTime,
                    KeperawatanEndTime,
                    FarmasiEndTime,
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
                    Excess,
                    BolehPulang
                FROM Dashboard_CTE
            ");
        });
    }
}

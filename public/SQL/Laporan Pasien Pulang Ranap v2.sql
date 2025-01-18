DECLARE @FromDate DATE, @ToDate DATE
SET @FromDate = '2025-01-04'
SET @ToDate	  = '2025-01-04';

SELECT DISTINCT 
	r.ServiceUnitName,
	r.RegistrationNo,
	--r.PlanDischargeDate,
	--r.PlanDischargeTime,
	--cv.DischargePlanUpdatedDate,
	--r.BedCode,
	r.MedicalNo,
	r.PatientName,
	r.CustomerType,
	r.ChargeClassName,
	r.ParamedicCode,
	r.ParamedicName,
	--cv.VisitID,
	RencanaPulang = 
			CASE 
				WHEN cv.PlanDischargeTime IS NULL
					THEN CAST(cv.PlanDischargeDate AS VARCHAR) + ' ' + CAST(cv.PlanDischargeTime AS VARCHAR)
				ELSE CAST(cv.PlanDischargeDate AS DATETIME) + CAST(cv.PlanDischargeTime AS TIME)
			END,
		Jangdik = -- Pilih yang masih statusnya open, received and in progress
		(SELECT MAX(ProposedDate) AS ProposedDate 
		FROM PatientChargesHD
			WHERE VisitID=cv.VisitID 
				and GCTransactionStatus<>'X121^999' 
				and GCTransactionStatus NOT in ('X121^001','X121^002','X121^003')
				and HealthcareServiceUnitID in (82,83,99,138,140)
				AND ProposedDate IS NOT NULL),
		Keperawatan =
		(SELECT MAX(ProposedDate) AS ProposedDate 
		FROM PatientChargesHD
			WHERE VisitID=cv.VisitID 
			and GCTransactionStatus<>'X121^999' 
			and GCTransactionStatus NOT in ('X121^001','X121^002','X121^003')
			and HealthcareServiceUnitID not in (82,83,99,138,140,101,137)
			AND ProposedDate IS NOT NULL),
			--ORDER BY ProposedDate DESC),
		Farmasi = -- Pilih yang masih statusnya open, received and in progress
		(SELECT MAX(ProposedDate) AS ProposedDate 
		FROM PatientChargesHD
			WHERE VisitID=cv.VisitID 
			and GCTransactionStatus<>'X121^999' 
			and GCTransactionStatus NOT in ('X121^001','X121^002','X121^003')
			and HealthcareServiceUnitID in (101,137)
			AND ProposedDate IS NOT NULL),
			--ORDER BY ProposedDate DESC),
		SelesaiBilling = (SELECT MAX(PrintedDate) AS PrintedDate 
			FROM ReportPrintLog 
			WHERE ReportID=7012 
				and ReportParameter = CONCAT('RegistrationID = ',r.RegistrationID)),
			--ORDER BY PrintedDate DESC),
	DischargeDateTime = CAST(cv.DischargeDate AS DATETIME) + CAST(cv.DischargeTime AS TIME),
    --cv.ActualDischargeDateTime,
	cv.RoomDischargeDateTime,
	-- Dari Pasien Rencana Pulang sd Room Close
	rpul_roomclose = CONCAT(DATEDIFF(SECOND,CASE 
				WHEN cv.PlanDischargeTime IS NULL
					THEN CAST(cv.PlanDischargeDate AS VARCHAR) + ' ' + CAST(cv.PlanDischargeTime AS VARCHAR)
				ELSE CAST(cv.PlanDischargeDate AS DATETIME) + CAST(cv.PlanDischargeTime AS TIME)
			END,cv.ActualDischargeDateTime)/ 3600, ':',
			FORMAT((DATEDIFF(SECOND,CASE 
				WHEN cv.PlanDischargeTime IS NULL
					THEN CAST(cv.PlanDischargeDate AS VARCHAR) + ' ' + CAST(cv.PlanDischargeTime AS VARCHAR)
				ELSE CAST(cv.PlanDischargeDate AS DATETIME) + CAST(cv.PlanDischargeTime AS TIME)
			END,cv.ActualDischargeDateTime)% 3600) / 60, '00'))
	--cv.PlanDischargeNotes
FROM vRegistration r
LEFT JOIN vPatient			p	ON p.MRN=r.MRN
LEFT JOIN PatientNotes		pn	ON pn.MRN=r.MRN
LEFT JOIN ConsultVisit		cv	ON cv.VisitID=r.VisitID
LEFT JOIN StandardCode		sc	ON sc.StandardCodeID=cv.GCPlanDischargeNotesType
LEFT JOIN PatientVisitNote	pvn	ON pvn.VisitID=cv.VisitID and pvn.GCNoteType in ('X312^001','X312^002','X312^003','X312^004','X312^005','X312^006')
WHERE r.DischargeDate BETWEEN @FromDate AND @ToDate 
AND  cv.PlanDischargeDate  IS NOT NULL
AND r.GCRegistrationStatus<>'X020^006' -- Pendaftaran Tidak DiBatalkan
--and r.MedicalNo='01-42-62-15'
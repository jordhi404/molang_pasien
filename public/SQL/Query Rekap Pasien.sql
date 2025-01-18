WITH patient_transitions AS (
    SELECT 
        pt."MedicalNo",
        pt."PatientName",
        pt."ServiceUnitName",
        pt."RencanaPulang",
        pt."Keperawatan",
        pt."Farmasi",
        pt."Kasir",
        pt."SelesaiBilling"
    FROM 
        patient_transitions pt
    WHERE 
        pt."RencanaPulang" BETWEEN '2025-01-07' AND '2025-01-13' -- Tanggal bisa diganti-ganti
)

SELECT DISTINCT
    pt."MedicalNo",
    pt."PatientName",
    pt."ServiceUnitName",
    pt."RencanaPulang",
    pt."Keperawatan",
    pt."Farmasi",
    pt."Kasir",
    pt."SelesaiBilling"
FROM 
    patient_transitions pt
ORDER BY 
    pt."RencanaPulang";
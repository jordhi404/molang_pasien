WITH bed_cleaning_records AS (
    SELECT 
        bcr."BedCode",
        bcr."ServiceUnitName",
        bcr."LastUnoccupiedDate",
        bcr."BedUnoccupiedInReality",
        bcr."ExpectedDoneCleaning",
        bcr."DoneCleaningInReality",
        (bcr."DoneCleaningInReality" - bcr."BedUnoccupiedInReality") AS CleaningDuration
    FROM 
        bed_cleaning_records bcr
    WHERE 
        bcr."LastUnoccupiedDate" BETWEEN '2025-01-07' AND '2025-01-13' -- Tanggal bisa diganti-ganti
)
SELECT DISTINCT
    bcr."BedCode",
    bcr."ServiceUnitName",
    bcr."LastUnoccupiedDate",
    bcr."BedUnoccupiedInReality",
    bcr."ExpectedDoneCleaning",
    bcr."DoneCleaningInReality",
    bcr.CleaningDuration
FROM 
    bed_cleaning_records bcr
ORDER BY 
    bcr."LastUnoccupiedDate";
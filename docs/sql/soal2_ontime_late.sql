-- SOAL 2 : Jumlah tasklist ONTIME & LATE beserta persentase (MySQL 8+ / PostgreSQL)

-- 2a. Per karyawan
SELECT
    karyawan,
    COUNT(*) AS total_tasklist,
    SUM(CASE WHEN aktual <= deadline THEN 1 ELSE 0 END) AS total_ontime,
    SUM(CASE WHEN aktual >  deadline THEN 1 ELSE 0 END) AS total_late,
    ROUND(100.0 * SUM(CASE WHEN aktual <= deadline THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0), 2) AS persentase_ontime,
    ROUND(100.0 * SUM(CASE WHEN aktual >  deadline THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0), 2) AS persentase_late
FROM table_kpi_marketing
GROUP BY karyawan
ORDER BY karyawan;

-- 2b. Keseluruhan
SELECT
    COUNT(*) AS total_tasklist,
    SUM(CASE WHEN aktual <= deadline THEN 1 ELSE 0 END) AS total_ontime,
    SUM(CASE WHEN aktual >  deadline THEN 1 ELSE 0 END) AS total_late,
    ROUND(100.0 * SUM(CASE WHEN aktual <= deadline THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0), 2) AS persentase_ontime,
    ROUND(100.0 * SUM(CASE WHEN aktual >  deadline THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0), 2) AS persentase_late
FROM table_kpi_marketing;

-- SOAL 1 : Perhitungan KPI per karyawan (MySQL 8+ / PostgreSQL)
-- Sumber : table_kpi_marketing (di project Laravel tersedia sebagai VIEW)
WITH employee_kpi AS (
    SELECT
        karyawan,
        COUNT(CASE WHEN kpi = 'Sales'  THEN 1 END) AS sales_actual,
        COUNT(CASE WHEN kpi = 'Report' THEN 1 END) AS report_actual,
        SUM(CASE WHEN kpi = 'Sales'  AND aktual > deadline THEN 1 ELSE 0 END) AS sales_late,
        SUM(CASE WHEN kpi = 'Report' AND aktual > deadline THEN 1 ELSE 0 END) AS report_late
    FROM table_kpi_marketing
    GROUP BY karyawan
)
SELECT
    karyawan AS nama,
    2 AS sales_target,
    sales_actual,
    ROUND(sales_actual * 1.0 / 2, 4)                         AS sales_pencapaian,
    0.50                                                     AS sales_weight,
    ROUND(sales_late * 0.07, 4)                              AS sales_late_deduction,
    ROUND((sales_actual * 1.0 / 2 * 0.50) - (sales_late * 0.07), 4) AS sales_final_score,
    2 AS report_target,
    report_actual,
    ROUND(report_actual * 1.0 / 2, 4)                        AS report_pencapaian,
    ROUND(report_actual * 1.0 / 2 * 0.50, 4)                 AS report_actual_weight,
    ROUND(report_late * 0.05, 4)                             AS report_late_deduction,
    ROUND((report_actual * 1.0 / 2 * 0.50) - (report_late * 0.05), 4) AS report_final_score,
    ROUND(
        (sales_actual * 1.0 / 2 * 0.50) - (sales_late * 0.07)
      + (report_actual * 1.0 / 2 * 0.50) - (report_late * 0.05), 4
    ) AS total_kpi
FROM employee_kpi
ORDER BY karyawan;

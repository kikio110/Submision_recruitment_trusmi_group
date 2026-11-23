-- SOAL 5 : Normalisasi table_kpi_marketing (MySQL / PostgreSQL)
--
-- Masalah pada tabel asli:
--   1. karyawan & kpi berupa teks yang diulang di setiap baris (redundansi, rawan salah ketik
--      "Budi" vs "budi", update anomaly bila nama berubah).
--   2. Aturan KPI (target 2, bobot 50%, penalti late 0.07 / 0.05) tidak tersimpan di database,
--      sehingga hard-coded di query / aplikasi.
--   3. Tidak ada primary key / foreign key / index pada karyawan & kpi.
--
-- Hasil normalisasi (3NF): employees, kpi_categories, tasklists.

CREATE TABLE employees (
    id         INT PRIMARY KEY AUTO_INCREMENT,          -- PostgreSQL: SERIAL PRIMARY KEY
    name       VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE kpi_categories (
    id           INT PRIMARY KEY AUTO_INCREMENT,
    name         VARCHAR(100) NOT NULL UNIQUE,          -- Sales / Report
    target       SMALLINT      NOT NULL,                -- target KPI
    weight       DECIMAL(5,4)  NOT NULL,                -- bobot KPI
    late_penalty DECIMAL(5,4)  NOT NULL                 -- pengurangan per tasklist late
);

CREATE TABLE tasklists (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    name            VARCHAR(100) NOT NULL,
    employee_id     INT NOT NULL,
    kpi_category_id INT NOT NULL,
    deadline        DATE NULL,
    actual_date     DATE NULL,
    CONSTRAINT fk_task_employee FOREIGN KEY (employee_id)     REFERENCES employees(id),
    CONSTRAINT fk_task_kpi      FOREIGN KEY (kpi_category_id) REFERENCES kpi_categories(id)
);
CREATE INDEX idx_tasklists_employee ON tasklists(employee_id);
CREATE INDEX idx_tasklists_kpi      ON tasklists(kpi_category_id);

INSERT INTO kpi_categories (id, name, target, weight, late_penalty) VALUES
(1, 'Sales', 2, 0.5000, 0.0700),
(2, 'Report', 2, 0.5000, 0.0500);

INSERT INTO employees (id, name) VALUES (1,'Budi'),(2,'Adi'),(3,'Rara'),(4,'Doni');

INSERT INTO tasklists (id, name, employee_id, kpi_category_id, deadline, actual_date) VALUES
(1,'Tasklist 1',1,1,'2022-01-10','2022-01-09'),(2,'Tasklist 2',1,1,'2022-01-10','2022-01-08'),
(3,'Tasklist 3',1,2,'2022-01-10','2022-01-07'),(4,'Tasklist 4',1,2,'2022-01-10','2022-01-12'),
(5,'Tasklist 5',2,1,'2022-01-10','2022-01-09'),(6,'Tasklist 6',2,1,'2022-01-10','2022-01-12'),
(7,'Tasklist 7',2,2,'2022-01-10','2022-01-07'),(8,'Tasklist 8',2,2,'2022-01-10','2022-01-07'),
(9,'Tasklist 9',3,1,'2022-01-10','2022-01-12'),(10,'Tasklist 10',3,1,'2022-01-10','2022-01-09'),
(11,'Tasklist 11',3,2,'2022-01-10','2022-01-12'),
(12,'Tasklist 12',4,2,'2022-01-10','2022-01-09'),(13,'Tasklist 13',4,1,'2022-01-10','2022-01-12');

-- View kompatibilitas: query soal 1 & 2 tetap jalan tanpa diubah
CREATE VIEW table_kpi_marketing AS
SELECT t.id, t.name AS tasklist, c.name AS kpi, e.name AS karyawan,
       t.deadline AS deadline, t.actual_date AS aktual
FROM tasklists t
JOIN kpi_categories c ON c.id = t.kpi_category_id
JOIN employees e      ON e.id = t.employee_id;

-- Import students to database
-- Username format: Ad + Ata_adi_bas_herfi(uppercase) + Qrup
-- Password: same as username

-- First, get group IDs
SET @group_125 = (SELECT id_student_group FROM student_group WHERE group_number = '125');
SET @group_425 = (SELECT id_student_group FROM student_group WHERE group_number = '425');
SET @group_425a = (SELECT id_student_group FROM student_group WHERE group_number = '425-a');
SET @group_425b = (SELECT id_student_group FROM student_group WHERE group_number = '425-b');
SET @group_425c = (SELECT id_student_group FROM student_group WHERE group_number = '425-c');
SET @group_425d = (SELECT id_student_group FROM student_group WHERE group_number = '425-d');
SET @group_425e = (SELECT id_student_group FROM student_group WHERE group_number = '425-e');
SET @group_425f = (SELECT id_student_group FROM student_group WHERE group_number = '425-f');
SET @group_525 = (SELECT id_student_group FROM student_group WHERE group_number = '525');
SET @group_725 = (SELECT id_student_group FROM student_group WHERE group_number = '725');
SET @group_725a = (SELECT id_student_group FROM student_group WHERE group_number = '725-a');
SET @group_725b = (SELECT id_student_group FROM student_group WHERE group_number = '725-b');
SET @group_725c = (SELECT id_student_group FROM student_group WHERE group_number = '725-c');
SET @group_725d = (SELECT id_student_group FROM student_group WHERE group_number = '725-d');
SET @group_725e = (SELECT id_student_group FROM student_group WHERE group_number = '725-e');
SET @group_725f = (SELECT id_student_group FROM student_group WHERE group_number = '725-f');
SET @group_925 = (SELECT id_student_group FROM student_group WHERE group_number = '925');
SET @group_1025 = (SELECT id_student_group FROM student_group WHERE group_number = '1025');
SET @group_1025a = (SELECT id_student_group FROM student_group WHERE group_number = '1025-a');
SET @group_1025b = (SELECT id_student_group FROM student_group WHERE group_number = '1025-b');
SET @group_1025c = (SELECT id_student_group FROM student_group WHERE group_number = '1025-c');
SET @group_2125 = (SELECT id_student_group FROM student_group WHERE group_number = '2125');
SET @group_2425 = (SELECT id_student_group FROM student_group WHERE group_number = '2425');
SET @group_2725 = (SELECT id_student_group FROM student_group WHERE group_number = '2725');
SET @group_124 = (SELECT id_student_group FROM student_group WHERE group_number = '124');
SET @group_224 = (SELECT id_student_group FROM student_group WHERE group_number = '224');
SET @group_224a = (SELECT id_student_group FROM student_group WHERE group_number = '224-a');
SET @group_224i = (SELECT id_student_group FROM student_group WHERE group_number = '224-i');
SET @group_424 = (SELECT id_student_group FROM student_group WHERE group_number = '424');
SET @group_424a = (SELECT id_student_group FROM student_group WHERE group_number = '424-a');
SET @group_424b = (SELECT id_student_group FROM student_group WHERE group_number = '424-b');
SET @group_424c = (SELECT id_student_group FROM student_group WHERE group_number = '424-c');
SET @group_424d = (SELECT id_student_group FROM student_group WHERE group_number = '424-d');
SET @group_424e = (SELECT id_student_group FROM student_group WHERE group_number = '424-e');
SET @group_524 = (SELECT id_student_group FROM student_group WHERE group_number = '524');
SET @group_724 = (SELECT id_student_group FROM student_group WHERE group_number = '724');
SET @group_724a = (SELECT id_student_group FROM student_group WHERE group_number = '724-a');
SET @group_724b = (SELECT id_student_group FROM student_group WHERE group_number = '724-b');
SET @group_724c = (SELECT id_student_group FROM student_group WHERE group_number = '724-c');
SET @group_724d = (SELECT id_student_group FROM student_group WHERE group_number = '724-d');
SET @group_724e = (SELECT id_student_group FROM student_group WHERE group_number = '724-e');
SET @group_924 = (SELECT id_student_group FROM student_group WHERE group_number = '924');
SET @group_1024 = (SELECT id_student_group FROM student_group WHERE group_number = '1024');
SET @group_1024a = (SELECT id_student_group FROM student_group WHERE group_number = '1024-a');
SET @group_1024b = (SELECT id_student_group FROM student_group WHERE group_number = '1024-b');

-- Qrup 125
INSERT INTO users (f_name, username, password, status, group_id) VALUES
('AĞASİYEVA NƏNƏXANIM ATİF QIZI', 'NƏNƏXANIMA125', '$2y$10$IQh4bFDJ2SzKsZ8M0h9tUenbhFNMw4q3TZqOtMM2jyktzvucXbtSK', 'student', @group_125),
('ASLANOVA SARAXANIM VALEH QIZI', 'SARAXANIMV125', '$2y$10$IQh4bFDJ2SzKsZ8M0h9tUenbhFNMw4q3TZqOtMM2jyktzvucXbtSK', 'student', @group_125),
('ATAMALİYEV RÜSTƏM QALİB', 'RÜSTƏMQ125', '$2y$10$IQh4bFDJ2SzKsZ8M0h9tUenbhFNMw4q3TZqOtMM2jyktzvucXbtSK', 'student', @group_125),
('BEHBUDLU NURLAN AZƏR OĞLU', 'NURLANA125', '$2y$10$IQh4bFDJ2SzKsZ8M0h9tUenbhFNMw4q3TZqOtMM2jyktzvucXbtSK', 'student', @group_125),
('DADAŞOVA MAİSƏ TALEH QIZI', 'MAİSƏT125', '$2y$10$IQh4bFDJ2SzKsZ8M0h9tUenbhFNMw4q3TZqOtMM2jyktzvucXbtSK', 'student', @group_125),
('ƏHMƏDZADƏ PƏRİŞAN TAHİR QIZI', 'PƏRİŞANT125', '$2y$10$IQh4bFDJ2SzKsZ8M0h9tUenbhFNMw4q3TZqOtMM2jyktzvucXbtSK', 'student', @group_125),
('ƏLİYEVA FİDAN HƏBİB QIZI', 'FİDANH125', '$2y$10$IQh4bFDJ2SzKsZ8M0h9tUenbhFNMw4q3TZqOtMM2jyktzvucXbtSK', 'student', @group_125),
('ƏLİYEVA XƏYALƏ QƏHRƏMAN QIZI', 'XƏYALƏQ125', '$2y$10$IQh4bFDJ2SzKsZ8M0h9tUenbhFNMw4q3TZqOtMM2jyktzvucXbtSK', 'student', @group_125),
('HACALIYEVA ƏSƏDLİ AYGÜL NATİQ QIZI', 'AYGÜLN125', '$2y$10$IQh4bFDJ2SzKsZ8M0h9tUenbhFNMw4q3TZqOtMM2jyktzvucXbtSK', 'student', @group_125),
('HAQVERDİYEV SEHAN TEYMUR OĞLU', 'SEHANT125', '$2y$10$IQh4bFDJ2SzKsZ8M0h9tUenbhFNMw4q3TZqOtMM2jyktzvucXbtSK', 'student', @group_125),
('XURŞİDOVA ARZU ƏBÜLFƏT QIZI', 'ARZUƏ125', '$2y$10$IQh4bFDJ2SzKsZ8M0h9tUenbhFNMw4q3TZqOtMM2jyktzvucXbtSK', 'student', @group_125),
('QAFAROVA SONA CEYHUN QIZI', 'SONAC125', '$2y$10$IQh4bFDJ2SzKsZ8M0h9tUenbhFNMw4q3TZqOtMM2jyktzvucXbtSK', 'student', @group_125),
('QASIMBƏYLİ GÜLAY XƏZRİ QIZI', 'GÜLAYX125', '$2y$10$IQh4bFDJ2SzKsZ8M0h9tUenbhFNMw4q3TZqOtMM2jyktzvucXbtSK', 'student', @group_125),
('QULİYEV HACI EHTİRAM OĞLU', 'HACIE125', '$2y$10$IQh4bFDJ2SzKsZ8M0h9tUenbhFNMw4q3TZqOtMM2jyktzvucXbtSK', 'student', @group_125),
('MÜSEYİBOVA FATİMƏ FAMİL QIZI', 'FATİMƏF125', '$2y$10$IQh4bFDJ2SzKsZ8M0h9tUenbhFNMw4q3TZqOtMM2jyktzvucXbtSK', 'student', @group_125),
('PADAROV ƏSİLDAR İLQAR OĞLU', 'ƏSİLDARİ125', '$2y$10$IQh4bFDJ2SzKsZ8M0h9tUenbhFNMw4q3TZqOtMM2jyktzvucXbtSK', 'student', @group_125),
('ŞƏFƏQQƏTOVA NURAY NATİQ QIZI', 'NURAYN125', '$2y$10$IQh4bFDJ2SzKsZ8M0h9tUenbhFNMw4q3TZqOtMM2jyktzvucXbtSK', 'student', @group_125),
('VERDİYEV ÖMƏR ƏLİ OĞLU', 'ÖMƏRƏ125', '$2y$10$IQh4bFDJ2SzKsZ8M0h9tUenbhFNMw4q3TZqOtMM2jyktzvucXbtSK', 'student', @group_125);

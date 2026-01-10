-- ===================================================================
-- BAL UYĞUNSUZLUQLARINI DÜZƏLTMƏK ÜÇÜN SQL SORĞULARI
-- ===================================================================

USE edu_system;

-- ===================================================================
-- 1. PROBLEMLƏRI TAP - Düzgün cavab verib amma tam bal almayan
-- ===================================================================

SELECT
    a.id_answer,
    a.exam_id,
    u.f_name AS telebe_adi,
    qr.id_question_text AS sual_no,
    LEFT(qr.question_text, 80) AS sual_metni,

    -- Cavablar
    a.user_answer AS telebe_cavabi,
    a.correct_var AS duzgun_cavab,
    a.is_correct AS duzgundur,

    -- Bal məlumatı
    a.score_earned AS hazirki_bal,
    qr.question_score AS olmali_bal,
    (qr.question_score - a.score_earned) AS ferq

FROM answers a
INNER JOIN users u ON a.user_id = u.id_users
INNER JOIN question_read qr ON a.id_questions = qr.id_question_text

WHERE
    a.exam_id = 318  -- İmtahan ID buraya yazın
    AND a.is_correct = 1  -- Düzgün cavab verib
    AND a.score_earned < qr.question_score  -- Amma tam bal almayıb

ORDER BY u.f_name, qr.id_question_text;


-- ===================================================================
-- 2. NEÇƏ PROBLEM VAR? (Sayını tap)
-- ===================================================================

SELECT
    COUNT(*) AS problem_sayi,
    SUM(qr.question_score - a.score_earned) AS itmiş_bal_cəmi

FROM answers a
INNER JOIN question_read qr ON a.id_questions = qr.id_question_text

WHERE
    a.exam_id = 318
    AND a.is_correct = 1
    AND a.score_earned < qr.question_score;


-- ===================================================================
-- 3. DÜZƏLDİŞ - AVTOMATIK UPDATE (DİQQƏTLİ!)
-- ===================================================================
-- QEYD: Bu sorğu veritabanını dəyişdirəcək!
-- Əvvəlcə yuxarıdakı SELECT ilə yoxlayın, sonra işə salın!

UPDATE answers a
INNER JOIN question_read qr ON a.id_questions = qr.id_question_text
SET a.score_earned = qr.question_score
WHERE
    a.exam_id = 318  -- İmtahan ID
    AND a.is_correct = 1  -- Düzgün cavab
    AND a.score_earned < qr.question_score;  -- Tam bal almayıb


-- ===================================================================
-- 4. YOXLAMA - Düzəlişdən sonra yenidən yoxla
-- ===================================================================

SELECT
    'Düzəlişdən SONRA' AS status,
    COUNT(*) AS qalan_problem_sayi

FROM answers a
INNER JOIN question_read qr ON a.id_questions = qr.id_question_text

WHERE
    a.exam_id = 318
    AND a.is_correct = 1
    AND a.score_earned < qr.question_score;

-- Nəticə: 0 olmalıdır


-- ===================================================================
-- 5. BÜTÜN İMTAHANLAR ÜÇÜN PROBLEMLƏRI TAP
-- ===================================================================

SELECT
    a.exam_id,
    e.date_exam,
    s.subjectname,
    COUNT(*) AS problem_sayi,
    SUM(qr.question_score - a.score_earned) AS itmiş_bal

FROM answers a
INNER JOIN question_read qr ON a.id_questions = qr.id_question_text
INNER JOIN exams e ON a.exam_id = e.id_exam
INNER JOIN subjects s ON e.id_subject = s.id_subject

WHERE
    a.is_correct = 1
    AND a.score_earned < qr.question_score

GROUP BY a.exam_id, e.date_exam, s.subjectname
ORDER BY a.exam_id DESC;


-- ===================================================================
-- 6. BÜTÜN İMTAHANLARI DÜZƏLT (ÇOXLU DİQQƏT!)
-- ===================================================================
-- Bu sorğu BÜTÜN imtahanlardakı uyğunsuzluqları düzəldəcək!

/*
UPDATE answers a
INNER JOIN question_read qr ON a.id_questions = qr.id_question_text
SET a.score_earned = qr.question_score
WHERE
    a.is_correct = 1
    AND a.score_earned < qr.question_score;
*/


-- ===================================================================
-- 7. KONKRET TƏLƏBƏ ÜÇÜN PROBLEMLƏRI TAP
-- ===================================================================

SELECT
    a.exam_id,
    u.f_name AS telebe_adi,
    qr.id_question_text AS sual_no,
    qr.question_text AS sual,
    a.score_earned AS hazirki_bal,
    qr.question_score AS olmali_bal

FROM answers a
INNER JOIN users u ON a.user_id = u.id_users
INNER JOIN question_read qr ON a.id_questions = qr.id_question_text

WHERE
    u.f_name LIKE '%ad soyad%'  -- Tələbənin adını yazın
    AND a.is_correct = 1
    AND a.score_earned < qr.question_score

ORDER BY a.exam_id, qr.id_question_text;


-- ===================================================================
-- 8. KONKRET SUAL ÜÇÜN PROBLEMLƏRI TAP
-- ===================================================================

SELECT
    u.f_name AS telebe_adi,
    a.user_answer,
    a.correct_var,
    a.score_earned AS hazirki_bal,
    qr.question_score AS olmali_bal

FROM answers a
INNER JOIN users u ON a.user_id = u.id_users
INNER JOIN question_read qr ON a.id_questions = qr.id_question_text

WHERE
    qr.id_question_text = 743  -- Sual ID-si
    AND a.exam_id = 318
    AND a.is_correct = 1
    AND a.score_earned < qr.question_score;


-- ===================================================================
-- 9. BACKUP - Düzəlişdən əvvəl nəzarət cədvəli yarat
-- ===================================================================

CREATE TABLE IF NOT EXISTS answers_backup_before_fix AS
SELECT * FROM answers
WHERE
    is_correct = 1
    AND score_earned < (
        SELECT question_score
        FROM question_read
        WHERE id_question_text = answers.id_questions
    );

-- Bu cədvəl düzəlişdən əvvəl saxlanacaq


-- ===================================================================
-- 10. SCORES CƏDVƏLİNİ YENİLƏ (VACIB!)
-- ===================================================================

-- QEYD: Bu sorğu MÜTLƏQ işə salınmalıdır!
-- Student dashboard scores cədvəlindən oxuyur, ona görə yeniləmək lazımdır!

UPDATE scores sc
INNER JOIN (
    SELECT
        a.exam_id,
        a.user_id,
        SUM(a.score_earned) AS yeni_toplam
    FROM answers a
    WHERE a.exam_id = 318
    GROUP BY a.exam_id, a.user_id
) AS calc ON sc.exam_id = calc.exam_id AND sc.user_id = calc.user_id
SET sc.score = calc.yeni_toplam
WHERE sc.exam_id = 318;

-- Yoxlama - toplam ballar düzgündürmü?
SELECT
    u.f_name AS telebe,
    sc.score AS scores_cedveli,
    (SELECT SUM(a.score_earned) FROM answers a WHERE a.exam_id = 318 AND a.user_id = u.id_users) AS answers_toplami,
    (sc.score - (SELECT SUM(a.score_earned) FROM answers a WHERE a.exam_id = 318 AND a.user_id = u.id_users)) AS ferq
FROM scores sc
INNER JOIN users u ON sc.user_id = u.id_users
WHERE sc.exam_id = 318;
-- Fərq 0 olmalıdır


-- ===================================================================
-- İSTİFADƏ QAYDASI:
-- ===================================================================
-- 1. Əvvəlcə sorğu #1 ilə problemləri görün
-- 2. Sorğu #2 ilə sayını yoxlayın
-- 3. (Opsional) Sorğu #9 ilə backup yaradın
-- 4. Sorğu #3 ilə düzəldin (answers cədvəli)
-- 5. Sorğu #4 ilə yoxlayın
-- 6. ⚠️ VACIB: Sorğu #10 ilə scores-u yeniləyin (tələbə dashboard-da görünsün!)
-- ===================================================================

-- QEYD: Sorğu #10 MÜTLƏQ işə salınmalıdır!
-- Əks halda student/dashboard.php səhifəsində dəyişiklik görünməyəcək!
-- ===================================================================

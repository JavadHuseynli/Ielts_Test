# Bal Düzəltmə Sistemi - İstifadə Təlimatı

## Problem Nədir?

Bəzi tələbələr sualları **düzgün cavablandırıb** (`is_correct = 1`) amma **tam bal almayıb**.

**Nümunə:**
- Sual balı: **2.00**
- Tələbə cavabı: **a**
- Düzgün cavab: **a**
- Status: **✓ Düzgün**
- Alınan bal: **1.00** ❌ (Olmalıdır: 2.00 ✓)

---

## Həll: 3 Addımda Düzəldiş

### ADDIM 1: Problemləri Yoxla

Brauzerdə aç:
```
http://localhost/imtahan ingilis/check_score_problems.php
```

Bu səhifə göstərəcək:
- ✅ Neçə imtahanda problem var
- ✅ Neçə cavabda uyğunsuzluq var
- ✅ Neçə bal itmiş
- ✅ Nümunə problemlər

---

### ADDIM 2: Konkret İmtahanı Yoxla

Brauzerdə aç (İmtahan ID dəyişdirin):
```
http://localhost/imtahan ingilis/fix_scores.php?exam_id=318
```

Bu səhifə göstərəcək:
- Tələbə adları
- Sual nömrələri
- Hazırki bal vs olmalı bal
- Fərq (itmiş bal)

---

### ADDIM 3: Düzəlt (Avtomatik)

#### A) Bir İmtahanı Düzəlt

1. `fix_scores.php?exam_id=318` açın
2. Problemləri yoxlayın
3. **"HAMISI DÜZƏLSİN"** düyməsinə basın
4. Təsdiq edin
5. Yoxlayın ki, problem sayı 0 oldu

#### B) BÜTÜN İmtahanları Düzəlt (DİQQƏT!)

MySQL Workbench-də işə salın:

```sql
USE edu_system;

-- ƏVVƏL BACKUP YARAT
CREATE TABLE IF NOT EXISTS answers_backup_before_fix AS
SELECT * FROM answers
WHERE
    is_correct = 1
    AND score_earned < (
        SELECT question_score
        FROM question_read
        WHERE id_question_text = answers.id_questions
    );

-- İNDİ DÜZƏLT
UPDATE answers a
INNER JOIN question_read qr ON a.id_questions = qr.id_question_text
SET a.score_earned = qr.question_score
WHERE
    a.is_correct = 1
    AND a.score_earned < qr.question_score;

-- YOXLA
SELECT COUNT(*) AS qalan_problem
FROM answers a
INNER JOIN question_read qr ON a.id_questions = qr.id_question_text
WHERE
    a.is_correct = 1
    AND a.score_earned < qr.question_score;
-- Nəticə 0 olmalıdır
```

---

## Fayllar və Funksiyaları

| Fayl | Nə Edir |
|------|---------|
| `check_score_problems.php` | Bütün problemli imtahanları siyahıya alır |
| `fix_scores.php` | Konkret imtahanın problemlərini göstərir və düzəldir |
| `fix_scores.sql` | MySQL Workbench üçün hazır SQL sorğuları |
| `test_simple.php` | Düzəlişdən sonra nəticələrə baxmaq üçün |

---

## Təhlükəsizlik

✅ **Transaction istifadə olunur** - Xəta baş versə geri qaytarılır
✅ **Backup yaradılır** - Əvvəlki məlumatlar saxlanır
✅ **Prepared statements** - SQL injection mümkün deyil
✅ **Yalnız düzgün cavablar** - `is_correct = 1` şərtilə

---

## Addım-addım İşə Salma

### 1️⃣ Problemləri Gör
```
http://localhost/imtahan ingilis/check_score_problems.php
```
→ Əgər problem varsa, növbəti addıma keç

### 2️⃣ İlk İmtahanı Düzəlt (Test)
```
http://localhost/imtahan ingilis/fix_scores.php?exam_id=318
```
→ "HAMISI DÜZƏLSİN" düyməsinə bas
→ Yoxla ki, düzəldi

### 3️⃣ Nəticəni Yoxla
```
http://localhost/imtahan ingilis/test_simple.php?exam_id=318
```
→ İndi ballar düzgün olmalıdır

### 4️⃣ Bütün İmtahanları Düzəlt
`check_score_problems.php` səhifəsindəki hər imtahan üçün:
- "Düzəlt" düyməsinə bas
VƏ YA
- `fix_scores.sql` faylındakı sorğuları işə sal

---

## Nəticə Yoxlaması

Düzəlişdən sonra yoxla:

```sql
SELECT
    COUNT(*) AS problem_sayi
FROM answers a
INNER JOIN question_read qr ON a.id_questions = qr.id_question_text
WHERE
    a.is_correct = 1
    AND a.score_earned < qr.question_score;
```

**Gözlənilən nəticə:** `0` (Heç bir problem qalmamalıdır)

---

## Suallar / Problemlər

Əgər bir şey işləməsə:

1. `test_debug.php?exam_id=318` - Veritabanı bağlantısını yoxla
2. `fix_scores.php?exam_id=318` - Problemləri gör
3. MySQL error log-a bax

---

**Yaradılma tarixi:** 2026-01-06
**Məqsəd:** Düzgün cavab verib tam bal almayan tələbələrin ballarını avtomatik düzəltmək

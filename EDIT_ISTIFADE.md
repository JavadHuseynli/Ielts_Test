# EDIT Funksiyası - İstifadə Təlimatı

## YENILIKLƏR ✨

İndi **hər cavabın balını əl ilə dəyişdirə bilərsiniz**!

---

## EDIT Düyməsi Harada Var?

### 1️⃣ **fix_scores.php** səhifəsində
- Problemli cavabların siyahısında
- Hər sətr üçün ayrıca EDIT düyməsi

### 2️⃣ **test_simple.php** səhifəsində
- Bütün tələbələrin cavablarında
- Hər sual üçün EDIT düyməsi (sarı rəng)

---

## Necə İstifadə Edək?

### ADDIM 1: Səhifəni Aç
```
http://localhost/imtahan ingilis/test_simple.php?exam_id=318
```
və ya
```
http://localhost/imtahan ingilis/fix_scores.php?exam_id=318
```

### ADDIM 2: EDIT Düyməsinə Klik Et
- Hər sual üçün EDIT düyməsi var
- Sarı rəngdə göstərilir

### ADDIM 3: Modalda Balı Dəyişdir
Modal açılanda görünəcək:
- ✅ Tələbənin adı
- ✅ Sual nömrəsi
- ✅ Maksimum bal
- ✅ Hazırki bal
- ✏️ Yeni bal daxil etmək sahəsi

### ADDIM 4: Yeni Balı Yaz
- Rəqəm daxil edin (məsələn: 2.00)
- Enter düyməsinə basın VƏ YA
- **"YADDA SAXLA"** düyməsinə klik edin

### ADDIM 5: Təsdiq
- ✅ "Bal uğurla yeniləndi!" mesajı görünəcək
- Səhifə avtomatik yenilənəcək (1 saniyə sonra)
- Yeni bal görünəcək

---

## XÜSUSİYYƏTLƏR

✅ **Avtomatik Yoxlama:**
- Bal mənfi ola bilməz
- Bal maksimumdan çox ola bilməz
- Yanlış dəyər daxil etsəniz xəbərdarlıq verir

✅ **Klaviatura Dəstəyi:**
- Enter düyməsi ilə yadda saxlaya bilərsiniz

✅ **Real-time Yeniləmə:**
- Dəyişiklik dərhal görünür
- Səhifə avtomatik yenilənir

✅ **Təhlükəsiz:**
- AJAX ilə serverə göndərir
- PDO Prepared Statements istifadə edir
- Validasiya həm frontend, həm backend-də

---

## NÜMUNƏ İSTİFADƏ

### Ssenariu:
Tələbə düzgün cavab verib amma **1.00 bal** alıb, olmalıdır **2.00 bal**.

**Həll:**
1. `test_simple.php?exam_id=318` açın
2. Həmin tələbənin sətrində EDIT düyməsinə basın
3. Modal açılanda yeni bal yazın: **2.00**
4. YADDA SAXLA düyməsinə basın
5. ✅ Bal dərhal 2.00-a dəyişəcək!

---

## TEXNİKİ DETALLAR

### Fayllar:
| Fayl | Funksiya |
|------|---------|
| `fix_scores.php` | Problemli cavabları göstərir + EDIT |
| `test_simple.php` | Bütün nəticələri göstərir + EDIT |
| `edit_score_handler.php` | AJAX sorğularını işləyir (backend) |

### İstifadə Edilən Texnologiyalar:
- 🔹 Bootstrap 5 Modal
- 🔹 jQuery AJAX
- 🔹 PDO Prepared Statements
- 🔹 JSON Response
- 🔹 Font Awesome Icons

### Veritabanı Dəyişikliyi:
```sql
UPDATE answers
SET score_earned = :new_score
WHERE id_answer = :answer_id
```

---

## ÜSTÜNLÜKLƏR

✅ **Sürətli:** Səhifəni yeniləməyə ehtiyac yoxdur (AJAX)
✅ **Asan:** 3 klikdə bal dəyişir
✅ **Təhlükəsiz:** Validasiya və prepared statements
✅ **Vizual:** Modal popup ilə rahat istifadə
✅ **Çap Dostu:** EDIT düyməsi çap zamanı görünməz (no-print class)

---

## TEZLIKLE VERILƏN SUALLAR

**S: Bütün cavabları eyni anda dəyişdirə bilərəmmi?**
C: Hə! `fix_scores.php` səhifəsindəki **"HAMISI DÜZƏLSİN"** düyməsindən istifadə edin.

**S: Balı silə bilərəmmi?**
C: Xeyr, amma 0 (sıfır) edə bilərsiniz.

**S: Maksimumdan çox bal verə bilərəmmi?**
C: Xeyr, sistem icazə verməz. Xəbərdarlıq mesajı göstərəcək.

**S: Dəyişiklik dərhal görünürmi?**
C: Bəli! AJAX ilə dərhal yenilənir.

---

## İSTİFADƏ MİSALLARI

### Tək-tək Düzəldiş:
```
1. test_simple.php açın
2. Tələbənin sətrində EDIT basın
3. Yeni bal yazın: 1.5
4. YADDA SAXLA
→ Dərhal dəyişir!
```

### Toplu Düzəldiş:
```
1. fix_scores.php açın
2. Problemli cavabları görün
3. "HAMISI DÜZƏLSİN" düyməsinə basın
→ Hamısı avtomatik düzəlir!
```

---

**Hazırladı:** Claude Code
**Tarix:** 2026-01-06
**Versiya:** 2.0 (EDIT funksiyası ilə)

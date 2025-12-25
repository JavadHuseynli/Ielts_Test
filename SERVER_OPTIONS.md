# 🚀 Server Seçimləri - IIS-siz

IIS-i qaldırdığın üçün 3 əsas variant var:

---

## 📊 Sürətli Müqayisə

| Xüsusiyyət | XAMPP | PHP Built-in | Apache Manual |
|-----------|-------|--------------|---------------|
| **Quraşdırma vaxtı** | 10 dəq | 5 dəq | 30 dəq |
| **Asanlıq** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐ |
| **MySQL daxildir** | ✅ | ❌ | ❌ |
| **phpMyAdmin** | ✅ | ❌ | ❌ |
| **GUI İdarəetmə** | ✅ | ❌ | ❌ |
| **Production** | ❌ | ❌ | ✅ |
| **Development** | ✅ | ✅ | ✅ |
| **Network Access** | ✅ | ✅ | ✅ |
| **Performans** | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |

---

## 🎯 Hansını Seçməli?

### ✅ XAMPP - ƏN ASAN (Tövsiyə edilir)

**Seç əgər**:
- ✅ Asan quraşdırma istəyirsən
- ✅ MySQL və phpMyAdmin lazımdır
- ✅ GUI idarəetmə panel istəyirsən
- ✅ Development/Test üçündür

**Əlaqəli fayllar**:
- `INSTALL_XAMPP_GUIDE.bat` - Quraşdırma təlimatı
- `SETUP_WITHOUT_IIS.md` - Ətraflı təlimat

**İstifadə**:
```
URL: http://localhost/ielts_test/
phpMyAdmin: http://localhost/phpmyadmin/
```

---

### ⚡ PHP Built-in Server - ƏN SÜRƏTLƏ

**Seç əgər**:
- ✅ Sürətli başlamaq istəyirsən
- ✅ Yalnız PHP kifayətdir (MySQL ayrı)
- ✅ Development/Test üçündür
- ✅ Sadə və yüngül server lazımdır

**Əlaqəli fayllar**:
- `START_PHP_SERVER.bat` - Server işə sal
- `php.ini` - PHP konfiqurasiyası

**İstifadə**:
```
1. START_PHP_SERVER.bat faylını işə sal (2 klik)
2. URL: http://172.18.250.21:7777/
```

---

### 🔧 Apache Manual - PROFESSIONAL

**Seç əgər**:
- ✅ Production server qurasan
- ✅ Tam nəzarət istəyirsən
- ✅ Advanced konfiqurasiya lazımdır
- ✅ SSL/HTTPS lazımdır

**Əlaqəli fayllar**:
- `.htaccess` - Apache konfiqurasiyası
- `SETUP_WITHOUT_IIS.md` - Quraşdırma təlimatı

**İstifadə**:
```
URL: http://localhost/
```

---

## 🚀 Tez Başlamaq Təlimatları

### Variant 1: XAMPP (10 dəqiqə)

```
1. INSTALL_XAMPP_GUIDE.bat faylını işə sal
2. Təlimatlara əməl et
3. XAMPP Control Panel-də Apache+MySQL işə sal
4. http://localhost/ielts_test/ - Test et
```

### Variant 2: PHP Built-in (2 dəqiqə)

```
1. START_PHP_SERVER.bat faylını işə sal (2 klik)
2. http://172.18.250.21:7777/ - Test et
3. MySQL-i ayrıca quraşdır (XAMPP-dən və ya ayrı)
```

### Variant 3: Apache Manual (30 dəqiqə)

```
1. SETUP_WITHOUT_IIS.md faylını oxu
2. "Variant 3: Apache" bölməsinə keç
3. Addım-addım quraşdır
4. http://localhost/ - Test et
```

---

## 📁 Lazım Olan Fayllar

Serverdən asılı olmayaraq bu fayllar lazımdır:

```
✅ php.ini                  - PHP konfiqurasiyası (25MB upload)
✅ .htaccess                - Apache konfiqurasiyası
✅ check_config.php         - Server yoxlama skripti
✅ includes/db.php          - Database konfiqurasiyası
```

---

## ⚙️ Database Konfiqurasiyası

### XAMPP üçün:
```php
// includes/db.php
private $host = "localhost";
private $db_name = "ielts_test";
private $username = "root";
private $password = "";  // Boşdur!
```

### Digərləri üçün:
```php
// includes/db.php
private $host = "localhost";
private $db_name = "ielts_test";
private $username = "your_username";
private $password = "your_password";
```

---

## 🎯 Tövsiyəm:

### İndi (Development):
```
XAMPP istifadə et
↓
Asan quraşdırma
↓
MySQL + phpMyAdmin daxildir
↓
http://localhost/ielts_test/
```

### Sonra (Production):
```
Apache Manual + Linux Server
↓
Real hosting
↓
SSL certificate
↓
https://yourdomain.com/
```

---

## ❓ Suallar

**S: Hansı variant ən yaxşıdır?**
A: Development üçün XAMPP, Production üçün Apache Manual

**S: MySQL-siz işləyə bilər?**
A: Xeyr, MySQL mütləq lazımdır. XAMPP-də var, digərlərində ayrıca quraşdır.

**S: IIS-ə geri qayıda bilərəm?**
A: Bəli, web.config faylı var, istədiyiniz vaxt.

**S: Network-dən girə bilərlər?**
A: Bəli, hər üç variantda (firewall-da port açmalısan)

---

## 📞 Növbəti Addım

Hansı variantı seçdin? Mənə yaz:
- "XAMPP" - Asan və sürətli (tövsiyə)
- "PHP" - Yüngül və sadə
- "Apache" - Professional

Seçimindən sonra addım-addım kömək edəcəm! 🚀

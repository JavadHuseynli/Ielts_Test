# IIS-siz Server Quraşdırma

IIS-dən imtina etdiyiniz üçün 3 variant təklif edirəm:

---

## ✅ Variant 1: XAMPP (Ən Asan - Tövsiyə edilir)

### Quraşdırma:

1. **XAMPP yüklə**:
   - https://www.apachefriends.org/download.html
   - Windows üçün son versiyanı yüklə

2. **Quraşdır**:
   - `C:\xampp\` qovluğuna quraşdır
   - Apache və MySQL seç

3. **Layihəni köçür**:
   ```
   Qovluğu köçür:
   FROM: C:\inetpub\wwwroot\ingilis imtahan\
   TO:   C:\xampp\htdocs\ielts_test\
   ```

4. **PHP konfiqurasiyasını düzəlt**:
   - `C:\xampp\php\php.ini` faylını aç
   - Tap və dəyişdir:
     ```ini
     upload_max_filesize = 25M
     post_max_size = 30M
     max_execution_time = 300
     memory_limit = 256M
     ```

5. **Apache-i işə sal**:
   - XAMPP Control Panel aç
   - Apache-də "Start" düyməsinə bas
   - MySQL-də "Start" düyməsinə bas

6. **Test et**:
   ```
   http://localhost/ielts_test/
   ```

---

## ✅ Variant 2: PHP Built-in Server (Development üçün)

### Quraşdırma:

1. **PHP yüklə** (əgər yoxdursa):
   - https://windows.php.net/download/
   - "VS16 x64 Thread Safe" versiyanı yüklə
   - `C:\php\` qovluğuna çıxart

2. **PHP-ni PATH-ə əlavə et**:
   - Windows Search → "Environment Variables"
   - System Variables → Path → Edit
   - Əlavə et: `C:\php`
   - OK

3. **php.ini yarad**:
   - `C:\php\php.ini-development` → `C:\php\php.ini`
   - Aç və düzəlt:
     ```ini
     extension_dir = "C:/php/ext"
     extension=mysqli
     extension=pdo_mysql
     extension=mbstring
     extension=openssl

     upload_max_filesize = 25M
     post_max_size = 30M
     max_execution_time = 300
     memory_limit = 256M
     ```

4. **Server işə sal**:
   - CMD-ni aç (Administrator kimi)
   - CD to project:
     ```cmd
     cd "C:\inetpub\wwwroot\ingilis imtahan"
     ```
   - Server işə sal:
     ```cmd
     php -S 0.0.0.0:7777
     ```

5. **Test et**:
   ```
   http://172.18.250.21:7777/
   http://localhost:7777/
   ```

---

## ✅ Variant 3: Apache (Manual)

### Quraşdırma:

1. **Apache yüklə**:
   - https://www.apachelounge.com/download/
   - "httpd-2.4.xx-win64-VSxx.zip" yüklə
   - `C:\Apache24\` qovluğuna çıxart

2. **httpd.conf düzəlt**:
   ```
   C:\Apache24\conf\httpd.conf
   ```

   Tap və dəyişdir:
   ```apache
   # Line ~60
   ServerRoot "C:/Apache24"

   # Line ~227
   DocumentRoot "C:/inetpub/wwwroot/ingilis imtahan"
   <Directory "C:/inetpub/wwwroot/ingilis imtahan">
       Options Indexes FollowSymLinks
       AllowOverride All
       Require all granted
   </Directory>

   # Line ~181
   LoadModule rewrite_module modules/mod_rewrite.so
   LoadModule php_module "C:/php/php8apache2_4.dll"
   AddHandler application/x-httpd-php .php
   PHPIniDir "C:/php"

   # Line ~250
   DirectoryIndex index.php index.html
   ```

3. **Service yarad**:
   ```cmd
   cd C:\Apache24\bin
   httpd.exe -k install
   ```

4. **Apache işə sal**:
   ```cmd
   net start Apache2.4
   ```

5. **Test et**:
   ```
   http://localhost/
   ```

---

## 🚀 Hansı Variantı Seçməli?

| Variant | Asan | Sürətli | Production üçün |
|---------|------|---------|-----------------|
| **XAMPP** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ❌ |
| **PHP Built-in** | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ❌ |
| **Apache Manual** | ⭐⭐ | ⭐⭐⭐ | ✅ |

### Tövsiyə:
- **Development/Test üçün**: XAMPP və ya PHP Built-in
- **Production üçün**: Apache Manual və ya geri IIS-ə qayıt

---

## 📋 Növbəti Addımlar:

1. **Variant seç** (XAMPP tövsiyə edilir)
2. **Quraşdır** (yuxarıdakı təlimatlara görə)
3. **Test et** (check_config.php)
4. **Database import et** (MySQL-ə)

---

## ⚙️ Database Configuration:

### XAMPP üçün:
```php
// includes/db.php
private $host = "localhost";
private $db_name = "ielts_test";
private $username = "root";
private $password = "";  // XAMPP-də parol boşdur
```

### PHP Built-in üçün:
MySQL ayrıca işə salmalısan:
```cmd
# MySQL yüklə və ya XAMPP-dən MySQL işə sal
```

---

Hansı variantı seçirsən? Mənə yaz, kömək edim! 🚀

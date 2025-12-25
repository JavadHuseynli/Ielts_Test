# Server Configuration Files

Bu layihə üçün 2 server konfiqurasiya faylı yaradılmışdır:

## 1. web.config (IIS Server - Windows)

**İstifadə olunur**: Windows Server + IIS (Internet Information Services)

### Əsas Parametrlər:
- ✅ `upload_max_filesize`: 25MB
- ✅ `post_max_size`: 30MB
- ✅ `max_execution_time`: 300 saniyə (5 dəqiqə)
- ✅ `memory_limit`: 256MB
- ✅ `maxAllowedContentLength`: 31,457,280 bytes (~30MB)

### Funksiyalar:
- PHP FastCGI konfiqurasiyası
- Fayl yükləmə limitləri
- Security headers (X-Frame-Options, X-XSS-Protection)
- MIME types (mp3, mp4, wav, ogg)
- Gzip compression
- URL rewrite rules
- Error handling
- Request filtering

### Quraşdırma:
1. Faylı layihənin root qovluğuna qoyun
2. IIS Manager açın
3. Site seçin və "Restart" edin
4. PHP-nin düzgün quraşdırıldığından əmin olun

---

## 2. .htaccess (Apache Server - Linux/Unix)

**İstifadə olunur**: Linux/Unix Server + Apache

### Əsas Parametrlər:
- ✅ `upload_max_filesize`: 25MB
- ✅ `post_max_size`: 30MB
- ✅ `max_execution_time`: 300 saniyə
- ✅ `memory_limit`: 256MB

### Funksiyalar:
- PHP konfiqurasiyası
- Security headers
- MIME types
- Gzip compression
- Cache control
- File access protection
- URL rewriting
- Directory protection

### Quraşdırma:
1. Faylı layihənin root qovluğuna qoyun
2. Apache konfiqurasiyasında `AllowOverride All` olduğundan əmin olun
3. Apache-ni restart edin:
   ```bash
   sudo service apache2 restart
   # və ya
   sudo systemctl restart httpd
   ```

---

## 3. php.ini və .user.ini

**Artıq yaradılmışdır**: `/php.ini` və `/admin/.user.ini`

### İstifadə:
- Development server üçün: `php.ini`
- Shared hosting üçün: `.user.ini` (hər qovluqda)

---

## Hansı Faylı İstifadə Etməli?

| Server Tipi | Fayl | Qeyd |
|-------------|------|------|
| **Windows + IIS** | `web.config` | IIS Manager-də PHP FastCGI aktivləşdirin |
| **Linux + Apache** | `.htaccess` | `mod_rewrite` və `mod_headers` aktivləşdirin |
| **Development (PHP built-in)** | `php.ini` | `php -c php.ini -S localhost:8000` |
| **Shared Hosting** | `.user.ini` | Hər qovluqda ayrı konfiqurasiya |

---

## Test Etmək

### 1. PHP konfiqurasiyasını yoxlayın:
```bash
php -i | grep -E "upload_max_filesize|post_max_size"
```

### 2. Web vasitəsilə yoxlayın:
Bir test faylı yaradın: `phpinfo.php`
```php
<?php
phpinfo();
?>
```

Brauzerə açın: `http://yourdomain.com/phpinfo.php`

Axtarın:
- upload_max_filesize: 25M
- post_max_size: 30M
- max_execution_time: 300

⚠️ **DİQQƏT**: Test etdikdən sonra `phpinfo.php` faylını silin (təhlükəsizlik üçün)!

---

## Problemlərin Həlli

### Problem 1: Fayl yüklənmir (2MB limitdən böyük)
**Həll**:
1. PHP konfiqurasiyasını yoxlayın
2. Server konfiqurasiyasını yoxlayın
3. Server-i restart edin

### Problem 2: web.config işləmir (IIS)
**Həll**:
1. IIS Manager-də "URL Rewrite" modulunu quraşdırın
2. PHP FastCGI-ni aktivləşdirin
3. Application Pool-u restart edin

### Problem 3: .htaccess işləmir (Apache)
**Həll**:
1. Apache konfiqurasiyasında `AllowOverride All` edin:
   ```apache
   <Directory /var/www/html>
       AllowOverride All
   </Directory>
   ```
2. `mod_rewrite` modulunu aktivləşdirin:
   ```bash
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

### Problem 4: Təhlükəsizlik xətası
**Həll**:
- `includes/` qovluğuna birbaşa giriş qadağandır (düzgündür!)
- `.env` faylına giriş qadağandır (düzgündür!)
- Yalnız public fayllar əlçatan olmalıdır

---

## Əlavə Qeydlər

### Production Environment üçün:
1. ✅ `display_errors = Off` (PHP)
2. ✅ `log_errors = On` (PHP)
3. ✅ HTTPS aktivləşdirin (SSL certificate)
4. ✅ Error log fayllarını yoxlayın
5. ✅ Database backup quraşdırın
6. ✅ Firewall konfiqurasiyası

### Development Environment üçün:
1. ✅ `display_errors = On` (xətaları göstərir)
2. ✅ Debug mode aktiv
3. ✅ Local server istifadə edin

---

## Dəstək

Əgər problem olarsa:
1. Server error log fayllarını yoxlayın
2. PHP error log-larını yoxlayın
3. Brauzer console-da xətaları yoxlayın
4. Network tab-da request-ləri yoxlayın

---

**Son yenilənmə**: 2025-12-25
**Versiya**: 1.0

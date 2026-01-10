╔════════════════════════════════════════════════════════════════╗
║                                                                ║
║          IELTS TEST SYSTEM - IIS-SIZ QURASDIRMA               ║
║                                                                ║
╚════════════════════════════════════════════════════════════════╝

IIS-i qaldirdığiniz üçün 3 sadə variant var:

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📦 VARIANT 1: XAMPP (Tövsiyə edilir - ƏN ASAN)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

   ✅ Bir paketde hər şey (Apache + MySQL + PHP + phpMyAdmin)
   ✅ 10 dəqiqədə quraşdırma
   ✅ GUI idarəetmə paneli
   ✅ Development/Test üçün ideal

   NECƏ QURASDIRMALIYAM?
   ────────────────────────────────────────────────────────────
   1. INSTALL_XAMPP_GUIDE.bat faylını işə sal (2 klik)
   2. Ekrandakı təlimatlara əməl et
   3. XAMPP Control Panel-də Apache və MySQL işə sal
   4. Layihəni C:\xampp\htdocs\ielts_test\ köçür
   5. http://localhost/ielts_test/ - Test et

   ƏTRAFLISI:
   ────────────────────────────────────────────────────────────
   → SETUP_WITHOUT_IIS.md faylını oxu (Variant 1)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

⚡ VARIANT 2: PHP BUILT-IN SERVER (ƏN SÜRƏTLƏ)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

   ✅ Heç nə quraşdırmağa ehtiyac yoxdur
   ✅ 2 dəqiqədə işə salma
   ✅ Yüngül və sürətli
   ✅ Development/Test üçün ideal

   NECƏ İŞƏ SALMALIYAM?
   ────────────────────────────────────────────────────────────
   1. START_PHP_SERVER.bat faylını işə sal (2 klik)
   2. http://172.18.250.21:7777/ - Test et

   QEYD: MySQL ayrıca quraşdırmalısan (XAMPP-dən götür)

   ƏTRAFLISI:
   ────────────────────────────────────────────────────────────
   → SETUP_WITHOUT_IIS.md faylını oxu (Variant 2)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🔧 VARIANT 3: APACHE MANUAL (PROFESSIONAL)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

   ✅ Tam nəzarət
   ✅ Production üçün əladır
   ✅ Advanced konfiqurasiya
   ✅ SSL/HTTPS dəstəyi

   NECƏ QURASDIRMALIYAM?
   ────────────────────────────────────────────────────────────
   1. SETUP_WITHOUT_IIS.md faylını aç
   2. "Variant 3: Apache" bölməsinə keç
   3. Addım-addım quraşdır (30 dəq)
   4. http://localhost/ - Test et

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📊 MÜQAYISƏ
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

┌─────────────────┬──────────┬────────────┬──────────────┐
│ Xüsusiyyət      │ XAMPP    │ PHP Built  │ Apache Man   │
├─────────────────┼──────────┼────────────┼──────────────┤
│ Asanlıq         │ ⭐⭐⭐⭐⭐ │ ⭐⭐⭐⭐    │ ⭐⭐          │
│ Sürət           │ 10 dəq   │ 2 dəq      │ 30 dəq       │
│ MySQL daxil     │ ✅       │ ❌         │ ❌           │
│ GUI             │ ✅       │ ❌         │ ❌           │
│ Production      │ ❌       │ ❌         │ ✅           │
└─────────────────┴──────────┴────────────┴──────────────┘

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🎯 TÖVSİYƏ
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

   Development/Test üçün:  → XAMPP (Variant 1)
   Sürətli test üçün:      → PHP Built-in (Variant 2)
   Production üçün:        → Apache Manual (Variant 3)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📁 FAYILLARIN YERLƏŞMƏSI
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📖 Təlimatlar:
   ├── README_FIRST.txt            ← Bu fayl (İLK OXUMALISAN!)
   ├── SERVER_OPTIONS.md            ← Ətraflı müqayisə
   ├── SETUP_WITHOUT_IIS.md         ← Tam quraşdırma təlimatı
   └── INSTALL_XAMPP_GUIDE.bat      ← XAMPP quraşdırma

⚙️ Konfiqurasiya:
   ├── php.ini                      ← PHP settings (25MB upload)
   ├── .htaccess                    ← Apache config
   └── web.config                   ← IIS config (istifadə etmirsən)

🚀 İşə Salma:
   └── START_PHP_SERVER.bat         ← PHP server işə sal

✅ Test:
   └── check_config.php             ← Server konfiqurasiyasını yoxla

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🚀 TEZ BAŞLA
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

   ASAN YOL (XAMPP):
   1. INSTALL_XAMPP_GUIDE.bat faylını aç
   2. Təlimatlara əməl et
   3. http://localhost/ielts_test/

   SUPER SÜRƏTLƏ (PHP Built-in):
   1. START_PHP_SERVER.bat faylını aç
   2. http://172.18.250.21:7777/

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

❓ SUALLAR?
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

   Q: Hansını seçməliyəm?
   A: XAMPP - ən asan və tam paket

   Q: MySQL-siz işləyər?
   A: Xeyr, MySQL lazımdır (XAMPP-də var)

   Q: Network-dən daxil ola bilərlərmi?
   A: Bəli, firewall-da portu açmalısan

   Q: IIS-ə qayıda bilərəm?
   A: Bəli, istədiyiniz vaxt

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

💡 İPUCU
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

   İlk dəfə quraşdırırsan?    → XAMPP seç
   Test etmək istəyirsən?     → PHP Built-in seç
   Production server?          → Apache Manual seç

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Uğurlar! 🎉

Kömək lazımdırsa:
→ SERVER_OPTIONS.md faylını oxu (Ətraflı məlumat)
→ SETUP_WITHOUT_IIS.md faylını oxu (Tam təlimat)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

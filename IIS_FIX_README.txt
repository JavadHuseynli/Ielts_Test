╔════════════════════════════════════════════════════════════════╗
║                                                                ║
║              IIS PERMISSIONS XƏTASI - HƏLL YOLU               ║
║                                                                ║
╚════════════════════════════════════════════════════════════════╝

Xəta:
  Error Code: 0x80070005
  Cannot read configuration file due to insufficient permissions

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

⚡ SÜRƏTLƏ HƏLL (1 dəqiqə)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. FIX_IIS_NOW.bat faylını tap

2. Sağ klik → "Run as administrator" seç

3. Gözlə (30 saniyə)

4. Test et:
   http://172.18.250.21:7777/

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

NƏ EDİR BU SKRİPT?
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✅ web.config faylını backup edir
✅ Sadə web.config yaradır (problem olmaz)
✅ IIS_IUSRS permissions əlavə edir
✅ IUSR permissions əlavə edir
✅ uploads qovluğu yaradır
✅ Permissions düzəldir
✅ IIS restart edir

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🔧 ƏLLƏ HƏLL (Əgər skript işləməsə)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Addım 1: PowerShell aç (Administrator kimi)
────────────────────────────────────────────────────────────────

Windows Search → PowerShell → Sağ klik → Run as administrator


Addım 2: Kopyala və yapışdır
────────────────────────────────────────────────────────────────

$path = "C:\inetpub\wwwroot\ingilis imtahan"
icacls $path /grant "IIS_IUSRS:(OI)(CI)(RX)" /T
icacls $path /grant "IUSR:(OI)(CI)(RX)" /T
icacls "$path\web.config" /grant "IIS_IUSRS:R"
icacls "$path\web.config" /grant "IUSR:R"
iisreset /restart


Addım 3: Test et
────────────────────────────────────────────────────────────────

http://172.18.250.21:7777/

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📁 YARADILAN FAYLLAR
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🚀 Avtomatik həll:
   ├── FIX_IIS_NOW.bat          ← İŞƏ SAL BUNU! (Tövsiyə)
   └── FIX_IIS_EASY.ps1          ← PowerShell skript

📖 Əllə təlimatlar:
   ├── IIS_FIX_README.txt        ← Bu fayl
   ├── IIS_MANUAL_FIX.md         ← Ətraflı təlimat
   └── FIX_PERMISSIONS.md        ← Köhnə təlimat

⚙️ Konfiqurasiya:
   ├── web.config                ← Əsas konfiqurasiya
   ├── web.config.simple         ← Sadə versiya
   └── php.ini                   ← PHP settings

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

❓ TƏLƏSƏN PROBLEMLƏR
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Problem: Skript "Access Denied" deyir
Həll: Administrator kimi işə sal
      Sağ klik → "Run as administrator"

Problem: Yenə eyni xəta
Həll: 1. IIS Manager aç
      2. Site seç → Basic Settings → Test Settings
      3. Hər ikisi yaşıl olmalıdır
      4. Deyilsə, Application Pool-u yoxla

Problem: 404 Not Found
Həll: 1. IIS Manager-də site "Started" olmalıdır
      2. Physical path düzgündür?
      3. index.php faylı var?

Problem: PHP işləmir
Həll: 1. PHP quraşdırılıb?
      2. IIS-də PHP Handler var?
      3. FastCGI modul var?

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🎯 SON ÇARƏ
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Əgər heç nə işləməsə:

1. Kompüteri restart et
   (Bəzən Windows-un restart lazım olur)

2. web.config-i sil
   Rename: web.config → web.config.old
   Copy: web.config.simple → web.config

3. IIS-i tam reinstall et
   - Uninstall IIS
   - Restart
   - Install IIS
   - PHP quraşdır

4. Port dəyişdir
   IIS Manager → Site → Bindings → Port: 8080

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✅ UĞURLU NƏTICƏ
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Website açılanda görməlisiniz:

┌────────────────────────────────────────────────────────┐
│                                                        │
│  ✅ IELTS Test System açılır                          │
│  ✅ Login səhifəsi görsənir                           │
│  ✅ PHP işləyir                                       │
│  ✅ Database bağlantısı var                           │
│                                                        │
└────────────────────────────────────────────────────────┘

Test konfiqurasiya:
http://172.18.250.21:7777/check_config.php

Görməlisiniz:
  ✅ upload_max_filesize: 25M
  ✅ post_max_size: 30M
  ✅ file_uploads: On

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📞 NÖVBƏTI ADDIMLAR
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. Permissions düzəlt (FIX_IIS_NOW.bat)

2. Website test et (http://172.18.250.21:7777/)

3. Konfiqurasiya yoxla (check_config.php)

4. Login test et (admin panel)

5. File upload test et (mp3 yüklə)

6. Exam test et (exam_v2.php)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

İNDİ NƏ ETMƏLƏ?
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

→ FIX_IIS_NOW.bat faylını işə sal (Administrator kimi)
→ Gözlə 30 saniyə
→ Test et: http://172.18.250.21:7777/

Uğurlar! 🚀

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

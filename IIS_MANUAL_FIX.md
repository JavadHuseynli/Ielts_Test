# IIS Manual Fix - Addım-Addım Təlimat

## Problem:
```
Error Code: 0x80070005
Cannot read configuration file due to insufficient permissions
```

---

## 🚀 SÜRƏTLƏ HƏLL (Tövsiyə edilir)

### Avtomatik Skript:

1. **FIX_IIS_NOW.bat** faylını tap
2. Sağ klik → **"Run as administrator"**
3. Gözlə (30 saniyə)
4. Test et: `http://172.18.250.21:7777/`

---

## 🔧 ƏLLƏ HƏLL (Əgər skript işləməsə)

### Addım 1: Sadə web.config Yarat

1. Windows Explorer-də qovluğa get:
   ```
   C:\inetpub\wwwroot\ingilis imtahan\
   ```

2. **web.config** faylını tap

3. Sağ klik → **Rename** → `web.config.backup`

4. Yeni fayl yarat: **web.config**

5. Aşağıdakı kodu kopyala və yapışdır:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <system.webServer>
        <defaultDocument>
            <files>
                <add value="index.php" />
                <add value="index.html" />
            </files>
        </defaultDocument>

        <staticContent>
            <mimeMap fileExtension=".mp3" mimeType="audio/mpeg" />
            <mimeMap fileExtension=".wav" mimeType="audio/wav" />
            <mimeMap fileExtension=".ogg" mimeType="audio/ogg" />
        </staticContent>

        <security>
            <requestFiltering>
                <requestLimits maxAllowedContentLength="31457280" />
            </requestFiltering>
        </security>

        <directoryBrowse enabled="false" />
    </system.webServer>
</configuration>
```

6. Yadda saxla (Save)

---

### Addım 2: Permissions Düzəlt

#### Variant A: PowerShell ilə (Sürətli)

1. **PowerShell**-i aç (Administrator kimi)

2. Kopyala və yapışdır:

```powershell
# Qovluq yolu
$path = "C:\inetpub\wwwroot\ingilis imtahan"

# Permissions əlavə et
icacls $path /grant "IIS_IUSRS:(OI)(CI)(RX)" /T
icacls $path /grant "IUSR:(OI)(CI)(RX)" /T

# web.config permissions
icacls "$path\web.config" /grant "IIS_IUSRS:R"
icacls "$path\web.config" /grant "IUSR:R"

# uploads folder
New-Item -ItemType Directory -Path "$path\uploads" -Force
icacls "$path\uploads" /grant "IIS_IUSRS:(OI)(CI)(M)"
icacls "$path\uploads" /grant "IUSR:(OI)(CI)(M)"

# IIS restart
iisreset /restart
```

#### Variant B: GUI ilə (Əllə)

1. **Qovluğa sağ klik** → **Properties**

2. **Security** tab → **Edit**

3. **Add** düyməsi

4. Yaz: `IIS_IUSRS` → **Check Names** → **OK**

5. Permissions:
   - ✅ Read & Execute
   - ✅ List folder contents
   - ✅ Read

6. **Add** yenidən bas

7. Yaz: `IUSR` → **Check Names** → **OK**

8. Eyni permissions:
   - ✅ Read & Execute
   - ✅ List folder contents
   - ✅ Read

9. **Apply** → **OK**

10. **web.config** faylı üçün təkrarla

---

### Addım 3: Application Pool Yoxla

1. **IIS Manager** aç

2. Sol tərəfdə **Application Pools** tap

3. Site-ınızın pool-unu tap (adətən **DefaultAppPool**)

4. Sağ klik → **Advanced Settings**

5. **Identity** → **ApplicationPoolIdentity** olmalıdır

6. **OK**

7. Sağ klik → **Recycle**

---

### Addım 4: Site Settings

1. **IIS Manager**-də sol tərəfdə site-ınızı tap

2. Sağ klik → **Manage Website** → **Advanced Settings**

3. **Physical Path** yoxla:
   ```
   C:\inetpub\wwwroot\ingilis imtahan
   ```

4. **OK**

5. Sağ klik → **Manage Website** → **Restart**

---

### Addım 5: Test Settings

1. Site-ı seç

2. Sağ tərəfdə **Basic Settings** klik

3. **Test Settings** düyməsi

4. Hər ikisi **yaşıl** olmalıdır:
   - ✅ Authentication
   - ✅ Authorization

5. **Close**

---

### Addım 6: IIS Restart

PowerShell-də (Administrator):
```powershell
iisreset /restart
```

Və ya:
```powershell
iisreset /stop
Start-Sleep -Seconds 5
iisreset /start
```

---

### Addım 7: Test Et

Brauzerə get:
```
http://172.18.250.21:7777/
```

---

## 🔍 YENƏ İŞLƏMƏSƏ

### Yoxlama Siyahısı:

- [ ] web.config sadə versiyası istifadə olunur
- [ ] IIS_IUSRS permissions var
- [ ] IUSR permissions var
- [ ] Application Pool Identity düzgündür
- [ ] IIS restart olunub
- [ ] Firewall-da port 7777 açıqdır
- [ ] Site status "Started"dır

### Son Çarə:

1. **Kompüteri restart et**
   - Bəzən Windows restart lazım olur

2. **Port dəyişdir**:
   - IIS Manager → Site → Bindings
   - Port: 7777 → 8080
   - Test: `http://172.18.250.21:8080/`

3. **Yeni Site yarat**:
   - IIS Manager → Add Website
   - Yeni ad ver
   - Physical path eyni qalsın
   - Port: 8080

---

## 📞 Debug Məlumatları

### Event Viewer Yoxla:

1. Windows Search → **Event Viewer**
2. **Windows Logs** → **Application**
3. Son xətaları yoxla
4. IIS ilə əlaqəli error-ları tap

### IIS Logs:

```
C:\inetpub\logs\LogFiles\
```

Son fayla bax, error-ları tap.

---

## ✅ Uğurlu Nəticə

Əgər hər şey işləsə, görməlisiniz:
- ✅ Website açılır
- ✅ PHP işləyir
- ✅ File upload işləyir (25MB-a qədər)

Test et:
```
http://172.18.250.21:7777/check_config.php
```

---

## 📝 Qeydlər

- **web.config.backup** faylını saxla (əgər problem olarsa)
- Hər dəyişiklikdən sonra IIS restart et
- PHP konfiqurasiyası üçün `php.ini` düzəlt
- Production üçün SSL certificate quraşdır

---

Uğurlar! 🚀

# IIS Permissions Xətasını Həll Et

## Xəta:
```
Error Code: 0x80070005
Config Error: Cannot read configuration file due to insufficient permissions
Config File: C:\inetpub\wwwroot\ingilis imtahan\web.config
```

## Həll Addımları:

### Addım 1: Qovluq Permissions
1. Windows Explorer-də `C:\inetpub\wwwroot\ingilis imtahan\` qovluğuna gedin
2. Qovluğa sağ klik → **Properties**
3. **Security** tab-a keçin
4. **Edit** düyməsinə basın
5. **Add** düyməsinə basın
6. Aşağıdakı istifadəçiləri əlavə edin:
   - `IIS_IUSRS`
   - `IUSR`
   - `IIS AppPool\DefaultAppPool` (və ya sizin app pool adınız)

7. Hər biri üçün **Read & Execute**, **List folder contents**, **Read** seçin
8. **Apply** və **OK**

### Addım 2: web.config Permissions
1. `web.config` faylına sağ klik → **Properties**
2. **Security** tab → **Edit**
3. Əmin olun ki, bu istifadəçilər var:
   - `IIS_IUSRS` - Read icazəsi
   - `IUSR` - Read icazəsi
   - `Administrators` - Full Control
   - `SYSTEM` - Full Control

4. **Apply** və **OK**

### Addım 3: PowerShell ilə (Administrator kimi)
```powershell
# CD to website directory
cd "C:\inetpub\wwwroot\ingilis imtahan\"

# Give permissions to IIS users
icacls . /grant "IIS_IUSRS:(OI)(CI)(RX)"
icacls . /grant "IUSR:(OI)(CI)(RX)"
icacls web.config /grant "IIS_IUSRS:R"
icacls web.config /grant "IUSR:R"

# Restart IIS
iisreset
```

### Addım 4: IIS Manager
1. IIS Manager açın
2. Sol tərəfdə site-ı seçin (ingilis imtahan)
3. **Basic Settings** → **Connect as...**
4. **Specific user** seçin və ya **Application user (pass-through authentication)** seçin
5. Test edin: **Test Settings**
6. Hər ikisi yaşıl olmalıdır ✅

### Addım 5: Application Pool Identity
1. IIS Manager → **Application Pools**
2. Site-ınızın pool-unu seçin (adətən DefaultAppPool)
3. Sağ klik → **Advanced Settings**
4. **Identity** → **ApplicationPoolIdentity** olmalıdır
5. OK
6. Application Pool-u **Restart** edin

---

## Əgər yenə işləməsə:

### Variant 1: Network Service istifadə et
Application Pool Identity-ni dəyişdirin:
- **LocalSystem** (ən güclü, amma az təhlükəsiz)
- **NetworkService** (tövsiyə edilir)

### Variant 2: Sadələşdirilmiş web.config istifadə et
Əgər hələ də problem varsa, sadə web.config istifadə edin (FIX faylına bax)

---

## Test Et:
```
http://172.18.250.21:7777/
```

Əgər işləsə, `check_config.php` açın:
```
http://172.18.250.21:7777/check_config.php
```

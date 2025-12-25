@echo off
:: ============================================
:: XAMPP Installation Guide
:: ============================================

title XAMPP Installation Guide

color 0A
echo ========================================
echo XAMPP QURASDIRMA TELIMAT
echo ========================================
echo.

echo ADDIM 1: XAMPP yukle
echo ----------------------------------------
echo 1. Brauzeri ac: https://www.apachefriends.org/download.html
echo 2. "XAMPP for Windows" yukle (en son versiaya)
echo 3. Faylin yuklendigini gozle...
echo.
pause
echo.

echo ADDIM 2: XAMPP qurashdir
echo ----------------------------------------
echo 1. Yuklenmish faylin uzerine klik et
echo 2. "Next" duymesine bas
echo 3. Komponenler sec:
echo    [x] Apache
echo    [x] MySQL
echo    [x] PHP
echo    [x] phpMyAdmin
echo 4. Qurashdir: C:\xampp\
echo 5. Qurasdirmanin bitdigini gozle...
echo.
pause
echo.

echo ADDIM 3: Layiheni kocur
echo ----------------------------------------
echo 1. Bu qovlugu kocur:
echo    FROM: %CD%
echo    TO:   C:\xampp\htdocs\ielts_test\
echo.
echo 2. Ve ya Windows Explorer-de:
echo    - %CD%
echo    - Butun faylari kocur C:\xampp\htdocs\ielts_test\ qovluguna
echo.
pause
echo.

echo ADDIM 4: PHP konfiqurasiyanı duzelt
echo ----------------------------------------
echo 1. Ac: C:\xampp\php\php.ini
echo 2. Tap ve deyishdir (Ctrl+F):
echo.
echo    upload_max_filesize = 25M
echo    post_max_size = 30M
echo    max_execution_time = 300
echo    memory_limit = 256M
echo.
echo 3. Yadda saxla (Save)
echo.
pause
echo.

echo ADDIM 5: XAMPP Control Panel ac
echo ----------------------------------------
echo 1. Ac: C:\xampp\xampp-control.exe
echo 2. Apache - "Start" duymesine bas
echo 3. MySQL - "Start" duymesine bas
echo 4. Her ikisi yasil olmalidir!
echo.
pause
echo.

echo ADDIM 6: Database import et
echo ----------------------------------------
echo 1. Brauzerda ac: http://localhost/phpmyadmin/
echo 2. "New" - database yarad: ielts_test
echo 3. "Import" tab
echo 4. SQL faylini sec
echo 5. "Go" duymesine bas
echo.
pause
echo.

echo ADDIM 7: Database konfiqurasiyanı yoxla
echo ----------------------------------------
echo 1. Ac: C:\xampp\htdocs\ielts_test\includes\db.php
echo 2. Yoxla:
echo    $host = "localhost";
echo    $db_name = "ielts_test";
echo    $username = "root";
echo    $password = "";  // XAMPP-de parol boshdur
echo.
pause
echo.

echo ========================================
echo TAMAM! INDI TEST ET:
echo ========================================
echo.
echo Brauzerda ac:
echo   http://localhost/ielts_test/
echo.
echo Konfiqurasiyanı yoxla:
echo   http://localhost/ielts_test/check_config.php
echo.
echo ========================================
echo.
echo Ugurlar! :)
echo.
pause

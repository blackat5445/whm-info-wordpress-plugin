@echo off
SETLOCAL EnableDelayedExpansion
cls

REM ##########################################################################
REM # Author & Info Splash Screen
REM ##########################################################################

echo.
echo  Author:    Kasra Falahati (Kasra.eu)
echo  Sponsor:   Agenzia magma (agenziamagma.it)
echo  =========================================================
echo.
echo  WP Plugin Packager
echo.
echo  This script will:
echo  1. Read your plugin's name and version from the main PHP file.
echo  2. Create a 'dist' folder (if it doesn't exist).
echo  3. Copy all plugin files directly into the ZIP (no root folder).
echo  4. Exclude dev files based on your configuration.
echo  5. Create a versioned .zip file (e.g., 'plugin-name.1.2.0.zip').
echo.
echo  Starting in 5 seconds...
echo.

timeout /t 5 > NUL

REM ##########################################################################
REM # Check for Configuration File
REM ##########################################################################

SET "CONFIG_FILE=packager.config"

if not exist "%CONFIG_FILE%" (
    goto FIRST_TIME_SETUP
) else (
    REM Check if INCLUDE_VERSION exists, if not add it
    powershell -Command "$config = Get-Content '%CONFIG_FILE%'; $hasVersion = $config | Where-Object { $_ -like 'INCLUDE_VERSION=*' }; if (-not $hasVersion) { Add-Content '%CONFIG_FILE%' 'INCLUDE_VERSION=true' }"
    goto MAIN_MENU
)

REM ##########################################################################
REM # First Time Setup
REM ##########################################################################

:FIRST_TIME_SETUP
cls
echo.
echo =========================================================
echo FIRST TIME SETUP
echo =========================================================
echo.

REM Ask for main plugin file
:ASK_MAIN_FILE
echo Enter the name of your main plugin file:
echo Example: plugin.php, whm-info.php
echo.
set /p "PLUGIN_MAIN_FILE=Main Plugin File: "

if not exist "%PLUGIN_MAIN_FILE%" (
    echo.
    echo ERROR: File "%PLUGIN_MAIN_FILE%" not found!
    echo Please try again.
    echo.
    goto ASK_MAIN_FILE
)

echo.
echo SUCCESS: Main plugin file set to "%PLUGIN_MAIN_FILE%"
echo.

REM Initialize config file with main plugin file
echo PLUGIN_MAIN_FILE=%PLUGIN_MAIN_FILE%> "%CONFIG_FILE%"
echo EXCLUDE_LIST=.git,.svn,node_modules,dist,Build.bat,packager.config,.gitignore>> "%CONFIG_FILE%"
echo INCLUDE_VERSION=true>> "%CONFIG_FILE%"

REM Ask for additional exclusions
:ASK_EXCLUSIONS
echo.
echo =========================================================
echo FILE EXCLUSION SETUP
echo =========================================================
echo.
echo Current exclusions: .git, .svn, node_modules, dist, Build.bat, packager.config, .gitignore
echo.
echo Do you want to add files/folders to exclude? (Y/N)
set /p "ADD_EXCLUSIONS=Your choice: "

if /i "%ADD_EXCLUSIONS%"=="Y" (
    goto ADD_EXCLUSION_FILE
) else (
    echo.
    echo Configuration saved to %CONFIG_FILE%
    timeout /t 2 > NUL
    goto MAIN_MENU
)

:ADD_EXCLUSION_FILE
echo.
echo Enter the file or folder name to exclude (e.g., .gitignore, tests):
set /p "EXCLUDE_FILE=File/Folder: "

if exist "%EXCLUDE_FILE%" (
    echo.
    echo SUCCESS: File/Folder "%EXCLUDE_FILE%" found and added to exclusions.
    
    REM Append to config file
    powershell -Command "$config = Get-Content '%CONFIG_FILE%'; $excludeLine = $config | Where-Object { $_ -like 'EXCLUDE_LIST=*' }; $newExclude = $excludeLine + ',%EXCLUDE_FILE%'; $config = $config -replace [regex]::Escape($excludeLine), $newExclude; $config | Set-Content '%CONFIG_FILE%'"
    
) else (
    echo.
    echo ERROR: File/Folder "%EXCLUDE_FILE%" not found! Try again.
    goto ADD_EXCLUSION_FILE
)

echo.
echo Do you want to add more exclusions? (Y/N)
set /p "MORE_EXCLUSIONS=Your choice: "

if /i "%MORE_EXCLUSIONS%"=="Y" (
    goto ADD_EXCLUSION_FILE
) else (
    echo.
    echo Configuration saved!
    timeout /t 2 > NUL
    goto MAIN_MENU
)

REM ##########################################################################
REM # Main Menu
REM ##########################################################################

:MAIN_MENU
cls

REM Load configuration to display current settings
SET "INCLUDE_VERSION=true"
for /f "tokens=1,* delims==" %%a in (%CONFIG_FILE%) do (
    if "%%a"=="INCLUDE_VERSION" set "INCLUDE_VERSION=%%b"
)

echo.
echo =========================================================
echo WP PLUGIN PACKAGER - MAIN MENU
echo =========================================================
echo.
echo 1. Bundle Plugin
echo 2. Settings (Reconfigure)
if /i "%INCLUDE_VERSION%"=="true" (
    echo 3. Version in filename: [ON]  ^(Toggle OFF^)
) else (
    echo 3. Version in filename: [OFF] ^(Toggle ON^)
)
echo 4. Exit
echo.
set /p "MENU_CHOICE=Select an option (1-4): "

if "%MENU_CHOICE%"=="1" goto BUNDLE_PLUGIN
if "%MENU_CHOICE%"=="2" goto SETTINGS_MENU
if "%MENU_CHOICE%"=="3" goto TOGGLE_VERSION
if "%MENU_CHOICE%"=="4" goto END_SCRIPT
echo Invalid choice. Please try again.
timeout /t 2 > NUL
goto MAIN_MENU

REM ##########################################################################
REM # Toggle Version Option
REM ##########################################################################

:TOGGLE_VERSION
REM Load current setting
SET "INCLUDE_VERSION=true"
for /f "tokens=1,* delims==" %%a in (%CONFIG_FILE%) do (
    if "%%a"=="INCLUDE_VERSION" set "INCLUDE_VERSION=%%b"
)

REM Toggle the value
if /i "%INCLUDE_VERSION%"=="true" (
    SET "NEW_VERSION_SETTING=false"
    echo.
    echo Version numbering in filename has been turned OFF.
    echo ZIP files will be named: plugin-name.zip
) else (
    SET "NEW_VERSION_SETTING=true"
    echo.
    echo Version numbering in filename has been turned ON.
    echo ZIP files will be named: plugin-name.1.2.0.zip
)

REM Update config file - Check if line exists first
powershell -Command "$config = Get-Content '%CONFIG_FILE%'; $versionLine = $config | Where-Object { $_ -like 'INCLUDE_VERSION=*' }; if ($versionLine) { $newVersionLine = 'INCLUDE_VERSION=%NEW_VERSION_SETTING%'; $config = $config -replace [regex]::Escape($versionLine), $newVersionLine; $config | Set-Content '%CONFIG_FILE%' } else { Add-Content '%CONFIG_FILE%' 'INCLUDE_VERSION=%NEW_VERSION_SETTING%' }"

echo.
timeout /t 3 > NUL
goto MAIN_MENU

REM ##########################################################################
REM # Settings Menu
REM ##########################################################################

:SETTINGS_MENU
cls
echo.
echo =========================================================
echo SETTINGS - RECONFIGURATION
echo =========================================================
echo.
echo This will reset all your configuration settings.
echo.
echo Press any key to continue or close the window to cancel...
pause > NUL

REM Delete existing config
if exist "%CONFIG_FILE%" del "%CONFIG_FILE%"

goto FIRST_TIME_SETUP

REM ##########################################################################
REM # Bundle Plugin
REM ##########################################################################

:BUNDLE_PLUGIN
cls

REM Load configuration
SET "INCLUDE_VERSION=true"
for /f "tokens=1,* delims==" %%a in (%CONFIG_FILE%) do (
    if "%%a"=="PLUGIN_MAIN_FILE" set "PLUGIN_MAIN_FILE=%%b"
    if "%%a"=="EXCLUDE_LIST" set "EXCLUDE_LIST=%%b"
    if "%%a"=="INCLUDE_VERSION" set "INCLUDE_VERSION=%%b"
)

SET "DIST_FOLDER=dist"

REM Check for the main plugin file
if not exist "%PLUGIN_MAIN_FILE%" (
    echo.
    echo ERROR: Main plugin file "%PLUGIN_MAIN_FILE%" not found.
    echo Please reconfigure settings.
    pause
    goto MAIN_MENU
)

echo.
echo =========================================================
echo WP Plugin Packaging Started...
echo =========================================================

REM --- PowerShell Script to Package Plugin (No Root Folder) ---
powershell -Command "$mainFile = '%PLUGIN_MAIN_FILE%'; $distFolder = '%DIST_FOLDER%'; $excludeListStr = '%EXCLUDE_LIST%'; $includeVersion = '%INCLUDE_VERSION%'; $content = Get-Content -Path $mainFile; $nameLine = $content | Select-String -Pattern 'Plugin Name:' | Select-Object -First 1; if (-not $nameLine) { Write-Error 'ERROR: Plugin Name not found in header.'; exit 1 }; $pluginName = $nameLine.ToString().Split(':')[-1].Trim(); $versionLine = $content | Select-String -Pattern 'Version:' | Select-Object -First 1; if (-not $versionLine) { Write-Error 'ERROR: Version not found in header.'; exit 1 }; $pluginVersion = $versionLine.ToString().Split(':')[-1].Trim(); $pluginSlug = $pluginName.ToLower().Replace(' ', '-'); $tempFolder = (Get-Item .).FullName + '\_temp_package'; if ($includeVersion -eq 'true') { $zipFileName = $pluginSlug + '.' + $pluginVersion + '.zip'; } else { $zipFileName = $pluginSlug + '.zip'; }; $zipPath = Join-Path $distFolder $zipFileName; Write-Host 'Plugin Name: ' -NoNewLine; Write-Host $pluginName -ForegroundColor Cyan; Write-Host 'Plugin Slug: ' -NoNewLine; Write-Host $pluginSlug -ForegroundColor Cyan; Write-Host 'Version: ' -NoNewLine; Write-Host $pluginVersion -ForegroundColor Cyan; Write-Host 'ZIP Filename: ' -NoNewLine; Write-Host $zipFileName -ForegroundColor Yellow; Write-Host 'ZIP Target:  ' -NoNewLine; Write-Host $zipPath -ForegroundColor Cyan; Write-Host '---'; if (-not (Test-Path $distFolder)) { New-Item -ItemType Directory -Force -Path $distFolder | Out-Null; Write-Host 'Created distribution folder: '$distFolder; }; if (Test-Path $tempFolder) { Remove-Item $tempFolder -Recurse -Force; }; New-Item -ItemType Directory -Force -Path $tempFolder | Out-Null; $excludeList = $excludeListStr.Split(',') | ForEach-Object { $_.Trim() }; $excludeList += '_temp_package'; Get-ChildItem -Path '.' -Force | ForEach-Object { $name = $_.Name; if ($excludeList -notcontains $name) { Copy-Item -Path $_.FullName -Destination $tempFolder -Recurse -Force; Write-Host ' + Copied: ' -NoNewLine; Write-Host $name -ForegroundColor Green; } else { Write-Host ' - Skipped: ' -NoNewLine; Write-Host $name -ForegroundColor Gray; } }; Write-Host '---'; Start-Sleep -Seconds 1; try { $filesToZip = Get-ChildItem -Path $tempFolder -Recurse -Force; Compress-Archive -Path (Join-Path $tempFolder '*') -DestinationPath $zipPath -Force; Write-Host 'SUCCESS: Created ' $zipFileName ' in the ' $distFolder ' folder.' -ForegroundColor Green; Remove-Item $tempFolder -Recurse -Force; Write-Host 'Cleanup complete.' -ForegroundColor Gray; } catch { $errMsg = 'ZIP FAILED: A file is likely locked by another program (like VS Code or Local). Close other programs and try again. Error details: ' + $_; Write-Error $errMsg; exit 1; }"
echo.

REM --- Check for PowerShell Errors ---
IF %ERRORLEVEL% NEQ 0 (
    echo =========================================================
    echo !! ERROR: Packaging failed. See PowerShell errors above. !!
    echo.
    echo    This is often caused by a "file in use" error.
    echo    Please CLOSE any code editors (VS Code) or local
    echo    server apps that might be using the plugin files.
    echo =========================================================
) ELSE (
    echo =========================================================
    echo Finished Packaging Successfully.
    echo =========================================================
)

echo.
echo Press any key to return to main menu...
pause > NUL
goto MAIN_MENU

REM ##########################################################################
REM # End Script
REM ##########################################################################

:END_SCRIPT
echo.
echo Goodbye!
timeout /t 2 > NUL
ENDLOCAL
exit /b 0
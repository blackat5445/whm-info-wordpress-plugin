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
echo  3. Copy all plugin files into a temporary folder.
echo  4. Exclude dev files like .git, node_modules, and this .bat file.
echo  5. Create a versioned .zip file (e.g., 'plugin-name.1.2.0.zip').
echo.
echo  Starting in 5 seconds...
echo.

timeout /t 5 > NUL

REM ##########################################################################
REM # WordPress Plugin Zipping and Packaging Script
REM ##########################################################################

REM --- Configuration ---
SET "PLUGIN_MAIN_FILE=whm-info.php"
SET "DIST_FOLDER=dist"
SET "BAT_FILE_NAME=Compiler.bat"

REM Check for the main plugin file
if not exist "%PLUGIN_MAIN_FILE%" (
    echo.
    echo ERROR: Main plugin file "%PLUGIN_MAIN_FILE%" not found.
    echo Please make sure the batch file is in the root of your plugin directory.
    pause
    exit /b 1
)

echo.
echo =========================================================
echo WP Plugin Packaging Started...
echo =========================================================

REM --- 1. Use PowerShell to Handle Complex Logic and Zipping ---
REM NOTE: The ^| in the 'catch' block escapes the pipe for cmd.exe,
REM allowing PowerShell to receive it correctly.

powershell -Command "$mainFile = '%PLUGIN_MAIN_FILE%'; $distFolder = '%DIST_FOLDER%'; $batFile = '%BAT_FILE_NAME%'; $content = Get-Content -Path $mainFile; $nameLine = $content | Select-String -Pattern 'Plugin Name:' | Select-Object -First 1; if (-not $nameLine) { Write-Error 'ERROR: Plugin Name not found in header.'; exit 1 }; $pluginName = $nameLine.ToString().Split(':')[-1].Trim(); $versionLine = $content | Select-String -Pattern 'Version:' | Select-Object -First 1; if (-not $versionLine) { Write-Error 'ERROR: Version not found in header.'; exit 1 }; $pluginVersion = $versionLine.ToString().Split(':')[-1].Trim(); $pluginSlug = $pluginName.ToLower().Replace(' ', '-'); $stagingFolder = (Get-Item .).FullName + '\' + $pluginSlug; $zipFileName = $pluginSlug + '.' + $pluginVersion + '.zip'; $zipPath = Join-Path $distFolder $zipFileName; Write-Host 'Plugin Name: ' -NoNewLine; Write-Host $pluginName -ForegroundColor Cyan; Write-Host 'Plugin Slug: ' -NoNewLine; Write-Host $pluginSlug -ForegroundColor Cyan; Write-Host 'Version: ' -NoNewLine; Write-Host $pluginVersion -ForegroundColor Cyan; Write-Host 'Staging Dir: ' -NoNewLine; Write-Host $stagingFolder -ForegroundColor Cyan; Write-Host 'ZIP Target:  ' -NoNewLine; Write-Host $zipPath -ForegroundColor Cyan; Write-Host '---'; if (-not (Test-Path $distFolder)) { New-Item -ItemType Directory -Force -Path $distFolder | Out-Null; Write-Host 'Created distribution folder: '$distFolder; }; if (Test-Path $stagingFolder) { Remove-Item $stagingFolder -Recurse -Force; }; New-Item -ItemType Directory -Force -Path $stagingFolder | Out-Null; $excludeList = @('.git', '.svn', 'node_modules', $distFolder, $batFile, $pluginSlug); Get-ChildItem -Path '.' -Force | ForEach-Object { $name = $_.Name; if ($excludeList -notcontains $name) { Copy-Item -Path $_.FullName -Destination $stagingFolder -Recurse -Force; Write-Host ' + Copied: ' -NoNewLine; Write-Host $name -ForegroundColor Green; } else { Write-Host ' - Skipped: ' -NoNewLine; Write-Host $name -ForegroundColor Gray; } }; Write-Host '---'; Start-Sleep -Seconds 1; try { Compress-Archive -Path $stagingFolder -DestinationPath $zipPath -Force; Write-Host 'SUCCESS: Created ' $zipFileName ' in the ' $distFolder ' folder.' -ForegroundColor Green; Remove-Item $stagingFolder -Recurse -Force; Write-Host 'Cleanup complete.' -ForegroundColor Gray; } catch { $errMsg = 'ZIP FAILED: A file is likely locked by another program (like VS Code or Local). Close other programs and try again. Error details: ' + $_; Write-Error $errMsg; exit 1; }"
echo.

REM --- 2. Check for PowerShell Errors ---
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
echo Press any key to close...
pause
ENDLOCAL
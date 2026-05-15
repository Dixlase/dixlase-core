@echo off
rem
rem This file is part of Dixlase.
rem
rem Copyright (C) 2026 exc-D inc.
rem https://exc-d.com
rem
rem Dixlase is dual-licensed. You may use this file under either:
rem
rem   (a) the GNU Affero General Public License version 3 or later, as
rem       published by the Free Software Foundation, together with the
rem       Dixlase Plugin and Theme Exception (see LICENSE-EXCEPTIONS for
rem       full exception terms); or
rem
rem   (b) a commercial license agreement obtained from exc-D inc.
rem       (see LICENSE.commercial, or contact info@dixlase.org).
rem
rem Unless you have entered into a commercial license agreement, this
rem file is governed by the AGPL terms above.
rem
rem ====================================================================
rem convert-comments.bat — Windows wrapper that delegates to the core
rem dls:comment:build Artisan command running inside the Dixlase PHP
rem container.
rem
rem Usage:
rem   convert-comments.bat ja
rem   convert-comments.bat ja --reverse
rem   convert-comments.bat ja --dry-run
rem   convert-comments.bat --list
rem
rem Override the container name with DIXLASE_PHP_CONTAINER if your
rem environment differs (default: dixlase-dev-app).
rem ====================================================================

setlocal enabledelayedexpansion

if "%DIXLASE_PHP_CONTAINER%"=="" set DIXLASE_PHP_CONTAINER=dixlase-dev-app
if "%DIXLASE_PHP_APP_DIR%"=="" set DIXLASE_PHP_APP_DIR=/var/www/html

if "%~1"=="" goto :usage
if "%~1"=="-h" goto :usage
if "%~1"=="--help" goto :usage
if "%~1"=="--list" goto :list_locales

rem Build the artisan args, defaulting --include-plugins and --include-themes ON.
set "LOCALE="
set "ARTISAN_ARGS=dls:comment:build --in-place --include-plugins --include-themes"

:parse_loop
if "%~1"=="" goto :run
if "%~1"=="--reverse" (
    set "ARTISAN_ARGS=%ARTISAN_ARGS% --reverse"
    shift
    goto :parse_loop
)
if "%~1"=="--dry-run" (
    set "ARTISAN_ARGS=%ARTISAN_ARGS% --dry-run"
    shift
    goto :parse_loop
)
if "%~1"=="--no-plugins" (
    set "ARTISAN_ARGS=!ARTISAN_ARGS:--include-plugins=!"
    shift
    goto :parse_loop
)
if "%~1"=="--no-themes" (
    set "ARTISAN_ARGS=!ARTISAN_ARGS:--include-themes=!"
    shift
    goto :parse_loop
)
echo %~1| findstr /b "--path=" >nul
if not errorlevel 1 (
    set "ARTISAN_ARGS=%ARTISAN_ARGS% %~1"
    shift
    goto :parse_loop
)
if "%LOCALE%"=="" (
    set "LOCALE=%~1"
    shift
    goto :parse_loop
)
echo Unexpected argument: %~1
goto :usage

:run
if "%LOCALE%"=="" goto :usage
set "ARTISAN_ARGS=%ARTISAN_ARGS% --locale=%LOCALE%"

docker ps --format "{{.Names}}" 2>nul | findstr /x "%DIXLASE_PHP_CONTAINER%" >nul
if errorlevel 1 (
    echo Error: container "%DIXLASE_PHP_CONTAINER%" is not running.
    echo Start it with "docker compose up -d", or override the name:
    echo   set DIXLASE_PHP_CONTAINER=^<name^> ^&^& convert-comments.bat %LOCALE%
    exit /b 1
)

docker exec %DIXLASE_PHP_CONTAINER% php %DIXLASE_PHP_APP_DIR%/artisan %ARTISAN_ARGS%
exit /b %errorlevel%

:list_locales
echo Available locales:
for /d %%d in ("%~dp0resources\comment-translations\*") do (
    set "name=%%~nxd"
    if not "!name:~0,1!"=="_" echo   !name!
)
exit /b 0

:usage
echo Usage: convert-comments.bat ^<locale^> [options]
echo        convert-comments.bat --list
echo.
echo Options:
echo   --reverse        Revert from ^<locale^> back to English
echo   --dry-run        Preview substitutions without writing
echo   --no-plugins     Do not walk plugins
echo   --no-themes      Do not walk themes
echo   --path=^<rel^>     Restrict scan to a sub-path (default: app)
echo   --list           Print available locales
echo.
echo Environment:
echo   DIXLASE_PHP_CONTAINER  Override container name (default: dixlase-dev-app)
echo   DIXLASE_PHP_APP_DIR    Override app dir in container (default: /var/www/html)
exit /b 1

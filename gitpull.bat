@echo off
echo Pulling latest changes...

REM Store the current HEAD before pull
for /f "delims=" %%h in ('git rev-parse HEAD') do set OLD_HEAD=%%h

REM Execute git pull and capture output
git pull > pull_output.tmp 2>&1

REM Check if pull_output contains "Already up to date"
findstr /C:"Already up to date" pull_output.tmp >nul
if %errorlevel% equ 0 (
    type pull_output.tmp
    del pull_output.tmp
    goto :eof
)

REM Show pull output
type pull_output.tmp
del pull_output.tmp

REM Get the new HEAD after pull
for /f "delims=" %%h in ('git rev-parse HEAD') do set NEW_HEAD=%%h

REM Only show file comments if HEAD changed (meaning files were actually pulled)
if "%OLD_HEAD%" neq "%NEW_HEAD%" (
    echo.
    echo Files changed with their commit messages:
    echo ========================================
    
    REM Get the list of changed files from the pull
    for /f "delims=" %%f in ('git diff --name-only %OLD_HEAD% %NEW_HEAD% 2^>nul') do (
        echo %%f
        REM Get all commit messages for this file since last pull
        for /f "delims=" %%c in ('git log %OLD_HEAD%..%NEW_HEAD% --pretty^=format:"    %%s" -- "%%f" 2^>nul') do (
            echo     %%c
        )
        echo.
    )
)
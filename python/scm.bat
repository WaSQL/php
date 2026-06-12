@echo off
where python3 >nul 2>nul
if %errorlevel%==0 (
    python3 "%~dp0scm.py" %*
) else (
    python "%~dp0scm.py" %*
)

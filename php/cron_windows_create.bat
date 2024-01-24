@echo off
echo Computername: %ComputerName%
set /p "user=Run As User: "
set /p "pass=Password: "
@echo on
schtasks /create /sc minute /mo 1 /rl HIGHEST /F /tn "\WaSQL\WaSQL Scheduler" /tr "\"C:\webserver\bin\php82\php.exe\" \"c:\wasql\php\cron_scheduler.php\"" /ru %user% /rp %pass%
schtasks /create /sc minute /mo 1 /rl HIGHEST /F /tn "\WaSQL\WaSQL Worker 1" /tr "\"C:\webserver\bin\php82\php.exe\" \"c:\wasql\php\cron_worker.php\"" /ru %user% /rp %pass%
schtasks /create /sc minute /mo 1 /rl HIGHEST /F /tn "\WaSQL\WaSQL Worker 2" /tr "\"C:\webserver\bin\php82\php.exe\" \"c:\wasql\php\cron_worker.php\"" /ru %user% /rp %pass%
schtasks /create /sc minute /mo 1 /rl HIGHEST /F /tn "\WaSQL\WaSQL Worker 3" /tr "\"C:\webserver\bin\php82\php.exe\" \"c:\wasql\php\cron_worker.php\"" /ru %user% /rp %pass%
schtasks /create /sc minute /mo 1 /rl HIGHEST /F /tn "\WaSQL\WaSQL Worker 4" /tr "\"C:\webserver\bin\php82\php.exe\" \"c:\wasql\php\cron_worker.php\"" /ru %user% /rp %pass%
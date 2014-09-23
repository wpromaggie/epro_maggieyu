:: db_file_ops.php by default looks in tmp directory in cli
:: could also put the local file in a place of your choosing when
:: calling scp and then pass that path to the local call to db_file_ops

@echo off


:: set this to path to your putty id file
set id_file=C:\path\to\putty_keyfile.ppk
set db=%1
set table=%2
set where_condition=%3

if not defined db goto bad_args
if not defined table goto bad_args
goto gtg

:bad_args
echo Usage: import_remote_table.bat db table
goto:eof

:gtg

set sql_file=%db%.%table%.mysql

echo on
plink -i "%id_file%" dbcmthfy@e2.wpromote.com php cli/e2/db_file_ops.php export %db% %table% "" "%where_condition%"
@if ERRORLEVEL 1 goto:eof

scp -i %id_file% dbcmthfy@e2.wpromote.com:cli/e2/tmp/%sql_file% tmp/
@if ERRORLEVEL 1 goto:eof

php db_file_ops.php import %db% %table%
@if ERRORLEVEL 1 goto:eof

@echo import successfully completed

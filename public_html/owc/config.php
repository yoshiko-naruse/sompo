<?php
/***********************************************************
* ＜OPTIMA Web Compiler＞
*  　ユーザー定義情報
***********************************************************/

// PHPパーサのパスを絶対パスで記述してください。
// 例1. $php_path = '/usr/local/bin/php'; // Linuxなどの場合
// 例2. $php_path = 'C:\PHP\cli\php.exe'; // Windowsの場合

//$php_path = '/usr/bin/php';
//$php_path = 'C:\PHP\php.exe'; // Windowsの場合

mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
ob_start('mb_output_handler');


?>
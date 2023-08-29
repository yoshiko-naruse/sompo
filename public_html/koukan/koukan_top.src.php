<?php
/*
 * 交換メニュー画面
 * koukan_top.src.php
 *
 * create 2007/03/19 H.Osugi
 *
 *
 */

// 各種モジュールの取得
include_once('../../include/define.php');			// 定数定義
require_once('../../include/dbConnect.php');		// DB接続モジュール
require_once('../../include/msSqlControl.php');		// DB操作モジュール
require_once('../../include/checkLogin.php');		// ログイン判定モジュール
require_once('../../include/castHtmlEntity.php');	// HTMLエンティティモジュール
require_once('../../include/redirectPost.php');		// リダイレクトポストモジュール

$isMenuExchange = true;
$isLevelAdmin = false;

// 交換時のAppliReasonをセット
$APPLI_REASON_EXCHANGE_SIZE        = APPLI_REASON_EXCHANGE_SIZE;         // 交換（サイズ交換）
$APPLI_REASON_EXCHANGE_INFERIORITY = APPLI_REASON_EXCHANGE_INFERIORITY;  // 交換（不良品交換）
$APPLI_REASON_EXCHANGE_LOSS        = APPLI_REASON_EXCHANGE_LOSS;         // 交換（紛失交換）
$APPLI_REASON_EXCHANGE_BREAK       = APPLI_REASON_EXCHANGE_BREAK;        // 交換（汚損・破損交換）
$APPLI_REASON_EXCHANGE_CHANGEGRADE = APPLI_REASON_EXCHANGE_CHANGEGRADE;  // 交換（役職変更交換）
$APPLI_REASON_EXCHANGE_MATERNITY   = APPLI_REASON_EXCHANGE_MATERNITY;    // 交換（マタニティ交換）
$APPLI_REASON_EXCHANGE_REPAIR      = APPLI_REASON_EXCHANGE_REPAIR;       // 交換（修理交換）

if ($_SESSION['USERLVL'] == USER_AUTH_LEVEL_ADMIN) {
	$isLevelAdmin = true;
}

$searchCompCd   = '';
$searchCompName = '';
$searchCompId   = '';
if ($isLevelAdmin == true) {
	$searchCompCd   = castHtmlEntity($_POST['searchCompCd']);
	$searchCompName = castHtmlEntity($_POST['searchCompName']);
	$searchCompId   = castHtmlEntity($_POST['searchCompId']);
}

?>
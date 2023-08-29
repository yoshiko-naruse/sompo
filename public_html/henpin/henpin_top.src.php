<?php
/*
 * 返却メニュー画面
 * henpin_top.src.php
 *
 * create 2007/03/22 H.Osugi
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

$isMenuReturn = true;

$APPLI_REASON_RETURN_RETIRE        = APPLI_REASON_RETURN_RETIRE;         // 返却（退職・異動返却）
$APPLI_REASON_RETURN_OTHER         = APPLI_REASON_RETURN_OTHER;          // 返却（その他返却）
$APPLI_REASON_EXCHANGE_SIZE_RETURN = APPLI_REASON_EXCHANGE_SIZE_RETURN;  // 返却（サイズ交換キャンセル）

$searchCompCd   = '';
$searchCompName = '';
$searchCompId   = '';
if ($isLevelAdmin == true) {
	$searchCompCd   = castHtmlEntity($_POST['searchCompCd']);
	$searchCompName = castHtmlEntity($_POST['searchCompName']);
	$searchCompId   = castHtmlEntity($_POST['searchCompId']);
}

?>
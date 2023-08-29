<?php
/*
 * 発注メニュー画面
 * hachu_top.src.php
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

$isMenuOrder = true;

$APPLI_REASON_ORDER_BASE            = APPLI_REASON_ORDER_BASE;				// 発注（基本パターン）
$APPLI_REASON_ORDER_GRADEUP         = APPLI_REASON_ORDER_GRADEUP;			// 発注（グレードアップタイ）
$APPLI_REASON_ORDER_FRESHMAN        = APPLI_REASON_ORDER_FRESHMAN;			// 発注（新入社員※新品優先）

$searchCompCd   = '';
$searchCompName = '';
$searchCompId   = '';
if ($isLevelAdmin == true) {
	$searchCompCd   = castHtmlEntity($_POST['searchCompCd']);
	$searchCompName = castHtmlEntity($_POST['searchCompName']);
	$searchCompId   = castHtmlEntity($_POST['searchCompId']);
}

// 施設権限ユーザーの場合、ラヴィーレ系ユーザーかどうかの判定
$isJitakuLikeUser = false;
$isHoteruLikeUser = false;
if ($isLevelNormal == true) {
	if ($_SESSION['COMPKIND'] == '1') {
		$isJitakuLikeUser = true;	// そんぽの家系
	}
	if ($_SESSION['COMPKIND'] == '2') {
		$isHoteruLikeUser = true;	// ラヴィーレ系
	}
}

?>
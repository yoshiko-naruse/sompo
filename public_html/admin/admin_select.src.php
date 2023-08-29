<?php
/*
 * 着用者機能選択画面
 * admin_sentaku.src.php
 *
 * create 2007/05/10 H.Osugi
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

// 初期設定
$isMenuAdmin = true;	// 着用者状況のメニューをアクティブに

$isItcAdmin = false;	// ＩＴＣ管理者フラグ

$isJafAdmin = false;	// ＪＡＦ管理者フラグ

//// 管理権限の場合は店舗IDが取得できなければエラーに
//if ($isLevelAdmin == false) {
//
//	$returnUrl             = HOME_URL . 'top.php';
//	
//	// TOP画面に強制遷移
//	redirectPost($returnUrl, $hiddens);
//
//} 

// 伊藤忠権限判定
if ($isLevelAdmin && $_SESSION['COMPID'] == '1') {
		$isItcAdmin         = true;
}

//// ＩＴＣ管理者フラグチェック
//if (trim($_SESSION['ADMINLVL']) == '1') {
//	$isItcAdmin = true;
//}
?>

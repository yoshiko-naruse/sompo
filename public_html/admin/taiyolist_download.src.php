<?php
/*
 * 定期発注用申請明細出力画面
 * teikioutput_select.src.php
 *
 * create 2013/09/06 T.Uno
 *
 */

// 各種モジュールの取得
include_once('../../include/define.php');			// 定数定義
require_once('../../include/dbConnect.php');		// DB接続モジュール
require_once('../../include/msSqlControl.php');		// DB操作モジュール
require_once('../../include/checkLogin.php');		// ログイン判定モジュール
require_once('../../include/castHtmlEntity.php');	// HTMLエンティティモジュール
require_once('../../include/redirectPost.php');		// リダイレクトポストモジュール
require_once('../../include/setPaging.php');		// ページング情報セッティングモジュール
//require_once('../../error_message/errorMessage.php');		// エラーメッセージ

// 初期設定
$isMenuKanri = true;	// 管理機能のメニューをアクティブに

// 管理権限でなければトップに
if ($isLevelAdmin == false) {

	$returnUrl = HOME_URL . 'top.php';
	
	// TOP画面に強制遷移
	redirectPost($returnUrl, $hiddens);

}

// 変数の初期化 ここから ******************************************************
//$appliDayFrom = '';
//$appliDayTo   = '';

// 変数の初期化 ここまで ******************************************************

// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST);

$inputDay = trim($post['inputDay']);		// 集計日

// 表示する値の成型 ここまで +++++++++++++++++++++++++++++++++++++++++++++++++++

?>

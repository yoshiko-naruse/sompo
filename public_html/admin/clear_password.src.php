<?php
/*
 * トップ画面
 * top.src.php
 *
 * create 2007/03/13 H.Osugi
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
require_once('../../include/castHidden.php');		//
require_once('./clear_password.val.php');			// エラー判定モジュール
// 変数の初期化 ここから ******************************************************
$isMenuAdmin = true;		// 管理機能のメニューをアクティブに

$stocks       = array();
$isClearComp = false;	// 初期化完了フラグ
// 変数の初期化 ここまで ******************************************************

// 管理者権限で無ければトップに強制遷移
if ($isLevelAdmin == false) {
	$returnUrl             = HOME_URL . 'top.php';
	// TOP画面に強制遷移
	redirectPost($returnUrl, $hiddens);
} 

// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST); 

// 初期パスワードをセット
$initPass = SYSTEM_DEFAULT_PASSWORD;
if($post['update_flg'] != ""){
	validatePostData($dbConnect,$post);
 	$isSuccess = clearPassword($dbConnect,$post);

	if ($isSuccess == false) {

		$hiddens['errorName'] = 'clearPassword';
		$hiddens['returnUrl'] = 'clear_password.php';
		$hiddens['errorId'][] = '901';
		$errorUrl             = HOME_URL . 'error.php';

		// エラー画面に強制遷移
		redirectPost($errorUrl, $hiddens);

	}else{
		$isClearComp = true;
	}
}

// パスワード変更
function clearPassword($dbConnect,$post){

	$sql  = " UPDATE M_User SET  ";
	$sql .= " PassWd = '" .SYSTEM_DEFAULT_PASSWORD ."',";
	$sql .= " PassWdUpdDay = GETDATE(),";
	$sql .= " UpdDay = GETDATE(),";
	$sql .= " UpdUser = '". db_Escape(trim($_SESSION['NAMECODE']))."' ";
	$sql .= " WHERE ";
	$sql .= " NameCd = '".db_Escape(trim($post['usercode']))."' ";
	$sql .= " AND Del = " .DELETE_OFF;	

	$isSuccess = db_Execute($dbConnect, $sql);

	// 実行結果が失敗の場合
	if ($isSuccess == false) {
		return false;
	}
	return true;	
}
?>
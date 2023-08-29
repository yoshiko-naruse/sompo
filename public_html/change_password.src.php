<?php
/*
 * パスワード変更画面
 * change_password.src.php
 *
 * create 2007/04/04 H.Osugi
 *
 *
 */

// 各種モジュールの取得
include_once('../include/define.php');				// 定数定義
require_once('../include/dbConnect.php');			// DB接続モジュール
require_once('../include/msSqlControl.php');		// DB操作モジュール
require_once('../include/checkLogin.php');			// ログイン判定モジュール
require_once('../include/castHtmlEntity.php');	// HTMLエンティティモジュール

// 変数の初期化 ここから ******************************************************
$nowPassWord  = '';					// 現在のパスワード
$newPassWord1 = '';					// 新しいパスワード
$newPassWord2 = '';					// 新しいパスワード（確認用）

$defaultFlg = false;				// ログイン画面からの遷移フラグ 09/04/07 uesugi
// パスワード変更初期値 09/04/10 uesugi
$isInitPass = false;
$isTimeLimit = false;
// 変数の初期化 ここまで ******************************************************

// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST); 

// 表示する値の成型 ここから +++++++++++++++++++++++++++++++++++++++++++++++++++

// 現在のパスワード
$nowPassword  = trim($post['nowPassword']);

// 新しいパスワード
$newPassword1 = trim($post['newPassword1']);

// 新しいパスワード（確認用）
$newPassword2 = trim($post['newPassword2']);
// 表示する値の成型 ここまで +++++++++++++++++++++++++++++++++++++++++++++++++++

// 初期パスワードの変更時は戻るボタンを表示しない
//if ($_SESSION['PASSWORD'] == SYSTEM_DEFAULT_PASSWORD) {
//    $defaultFlg = true;    
//}
// ログイン画面からの遷移の場合
// 09/04/08 uesugi
if(isset($_SESSION['FROM_LOGIN']) && $_SESSION['FROM_LOGIN'] == "1"){
	$defaultFlg = true;
	// 初期パスワードの場合
	if($_SESSION['PASSWORD'] == SYSTEM_DEFAULT_PASSWORD) {
		$isInitPass = true;
		$isTimeLimit = false;
	}else{
		$isInitPass = false;
		$isTimeLimit = true;
	}
}

?>
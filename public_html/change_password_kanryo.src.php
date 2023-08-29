<?php
/*
 * パスワード変更完了画面
 * change_password_kanryo.src.php
 *
 * create 2007/04/04 H.Osugi
 * update 2007/04/19 H.Osugi メール送信機能追加
 *
 */

// 各種モジュールの取得
include_once('../include/define.php');				// 定数定義
require_once('../include/dbConnect.php');			// DB接続モジュール
require_once('../include/msSqlControl.php');		// DB操作モジュール
require_once('../include/checkLogin.php');			// ログイン判定モジュール
require_once('../include/redirectPost.php');		// リダイレクトポストモジュール
require_once('../include/checkData.php');			// 対象文字列検証モジュール
require_once('../include/castHidden.php');			// hidden値成型モジュール
require_once('../include/castHtmlEntity.php');		// HTMLエンティティモジュール
require_once('../include/createPasswordMail.php');	// 発注申請メール生成モジュール
require_once('../include/sendTextMail.php');		// テキストメール送信モジュール
require_once('./change_password.val.php');			// エラー判定モジュール

// 変数の初期化 ここから ******************************************************
$nowPassWord  = '';					// 現在のパスワード
$newPassWord1 = '';					// 新しいパスワード
$newPassWord2 = '';					// 新しいパスワード（確認用）
// 変数の初期化 ここまで ******************************************************

// エラー判定
validatePostData($dbConnect, $_POST);

// パスワードを変更する
$isSuccess = changeUserPassword($dbConnect, $_POST);

if ($isSuccess == false) {

	$hiddens['errorName'] = 'changePassword';
	$hiddens['returnUrl'] = 'change_password.php';
	$hiddens['errorId'][] = '901';
	$errorUrl             = HOME_URL . 'error.php';

	// エラー画面に強制遷移
	redirectPost($errorUrl, $hiddens);

}

// メールを送信する
$isSuccess = sendMailHenkou($dbConnect);

// ログイン画面に遷移
$postUrl = HOME_URL . "login.php?changePass=1";
redirectPost($postUrl, '');

/*
 * ユーザパスワードを変更する
 * 引数  ：$dbConnect    => コネクションハンドラ
 *       ：$post         => POST値
 * 戻り値：true：変更成功 / false：変更失敗
 *
 * create 2007/04/04 H.Osugi
 *
 */
function changeUserPassword($dbConnect, $post) {

	// パスワードの変更
	$sql  = "";
	$sql .= " UPDATE";
	$sql .= 	" M_User";
	$sql .= " SET";
	$sql .= 	" PassWd = '" . db_Escape(trim($post['newPassword1'])) . "',";
	$sql .= 	" PassWdUpdDay = GETDATE(),";
	$sql .= 	" UpdDay = GETDATE(),";
	$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
	$sql .= " WHERE";
	$sql .= 	" UserID = '" . db_Escape($_SESSION['USERID']) ."'";

	$isSuccess = db_Execute($dbConnect, $sql);
	
	// 実行結果が失敗の場合
	if ($isSuccess == false) {
		return false;
	}

	// パスワード
	$_SESSION['PASSWORD'] = trim($post['newPassword1']);
	 setcookie("pass", md5(trim($post['newPassword1'])));

	return true;

}


/*
 * パスワード変更通知メールを送信する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$post      => POST値
 *       ：$orderId   => OrderID
 * 戻り値：true：変更成功 / false：変更失敗
 *
 * create 2007/04/19 H.Osugi
 *
 */
function sendMailHenkou() {

	$filePath = '../mail_template/';

	// パスワード通知メールの件名と本文を取得
	$isSuccess = passwordHenkouMail($filePath, $subject, $message);
	
	if ($isSuccess == false) {
		return false;
	}

	$toAddr     = PASSWORD_HENKOU_MAIL;
	$bccAddr    = MAIL_GROUP_0;
	$fromAddr   = FROM_MAIL;
	$returnAddr = RETURN_MAIL;

	// メールを送信
	$isSuccess = sendTextMail($toAddr, $subject, $message, $fromAddr, '', $bccAddr, 'UTF-8', $rturnAddr);

	if ($isSuccess == false) {
		return false;
	}

	return true;

}

?>
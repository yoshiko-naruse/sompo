<?php
/*
 * パスワード変更通知メール生成モジュール
 * createPasswordMail.php
 *
 * create 2007/04/19 H.Osugi
 *
 */

/*
 * パスワード変更通知メールの件名と本文を作成する
 *
 * 引数  ：$post       => POST値
 *       ：$filePath   => メールテンプレートのファイルパス
 *       ：$subject    => 件名
 *       ：$message    => 本文
 *
 * 戻り値：true：メールテンプレート取得成功/false：取得失敗
 *
 * create 2007/04/19 H.Osugi
 *
 */
function passwordHenkouMail($filePath, &$subject, &$message) {

	// 初期化
	$subject = '';
	$message = '';

	// 件名
	$subject = MAIL_SUBJECT_HEADER . "パスワード変更通知";

	// 現在日時
	$date = date("Y年n月j日 H時i分");

	// 本文
	$message = file_get_contents($filePath . 'passwordHenkou.txt');

	// 本文の置換
	$message = mb_ereg_replace('###HEAD###', MAIL_BODY_HEADER, $message);
	$message = mb_ereg_replace('###DATE###', $date, $message);

	$message = mb_ereg_replace('###COMPCODE###', trim($_SESSION['COMPCD']), $message);
	$message = mb_ereg_replace('###COMPNAME###', trim($_SESSION['COMPNAME']), $message);
	$message = mb_ereg_replace('###STAFFCODE###', trim($_SESSION['NAMECODE']), $message);

	return true;

}


?>
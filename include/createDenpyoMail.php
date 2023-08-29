<?php
/*
 * 着払い伝票依頼メール生成モジュール
 * createDenpyoMail.php
 *
 * create 2007/04/09 H.Osugi
 *
 */

/*
 * 着払い伝票依頼メールの件名と本文を作成する
 *
 * 引数  ：$post       => POST値
 *       ：$filePath   => メールテンプレートのファイルパス
 *       ：$subject    => 件名
 *       ：$message    => 本文
 *
 * 戻り値：true：メールテンプレート取得成功/false：取得失敗
 *
 * create 2007/04/09 H.Osugi
 *
 */
function denpyoMail($post, $voucherNum, $filePath, &$subject, &$message) {

	// 初期化
	$subject = '';
	$message = '';

	// 件名
	$subject = MAIL_SUBJECT_HEADER . "着払い伝票発注";

	// 現在日時
	$date = date("Y年n月j日 H時i分");

	// 申請日
	$today = date("Y/m/d");

    // 伝票種別
    $syubetsuAry = unserialize(DENPYO_SYUBETSU_ARY_JP);
    $denpyo_syubetsu = $syubetsuAry[$post['denpyo_syubetsu']];

	// 本文
	$message = file_get_contents($filePath . 'tyakubaraiDenpyo.txt');

	// 本文の置換
	$message = mb_ereg_replace('###HEAD###', MAIL_BODY_HEADER, $message);
	$message = mb_ereg_replace('###DATE###', $date, $message);

	$message = mb_ereg_replace('###APPLIDAY###', $today, $message);
	$message = mb_ereg_replace('###COMPCODE###', trim($_SESSION['COMPCD']), $message);
	$message = mb_ereg_replace('###COMPNAME###', trim($_SESSION['COMPNAME']), $message);
	$message = mb_ereg_replace('###SHIPNAME###', trim($post['shipName']), $message);
	$message = mb_ereg_replace('###STAFFNAME###', trim($post['staffName']), $message);
	$message = mb_ereg_replace('###ZIP1###', trim($post['zip1']), $message);
	$message = mb_ereg_replace('###ZIP2###', trim($post['zip2']), $message);
	$message = mb_ereg_replace('###ADDRESS###', trim($post['address']), $message);
	$message = mb_ereg_replace('###TEL###', trim($post['tel']), $message);
	$message = mb_ereg_replace('###NOTE###', trim($post['memo']), $message);
    $message = mb_ereg_replace('###DENPYOSYUBETSU###', $denpyo_syubetsu, $message);
	$message = mb_ereg_replace('###VOUCHERNUM###', $voucherNum, $message);

	return true;

}


?>
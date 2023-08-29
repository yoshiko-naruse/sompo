<?php
/*
 * 店舗移動通知メール生成モジュール
 * createMoveMail.php
 *
 * create 2008/06/27 W.Takasaki
 *
 */

/*
 * 店舗移動通知メールの件名と本文を作成する
 *
 * 引数  ：  $dbConnect   => DB接続オブジェクト
 *       ：$filePath   => メールテンプレートのファイルパス
 *       ：$staffInfo  => スタッフ情報
 *       ：$compInfo   => 店舗情報
 *       ：$subject    => 件名
 *       ：$message    => 本文
 *
 * 戻り値：true：メールテンプレート取得成功/false：取得失敗
 *
 * create 2007/04/19 H.Osugi
 *
 */
function moveCompMail($dbConnect,$filePath, $compInfo, $staffInfo, &$subject, &$message) {

	// 初期化
	$subject = '';
	$message = '';

	// 件名
	$subject = MAIL_SUBJECT_HEADER . "所属店舗変更通知";

	// 現在日時
	$date = date("Y年n月j日 H時i分");

	// 本文
	$message = file_get_contents($filePath . 'moveComp.txt');

	// 本文の置換
	$message = mb_ereg_replace('###HEAD###', MAIL_BODY_HEADER, $message);
	$message = mb_ereg_replace('###DATE###', $date, $message);

	$message = mb_ereg_replace('###OLDCOMPCD###', trim($compInfo['old']['CompCd']), $message);
	$message = mb_ereg_replace('###OLDCOMPNAME###', trim($compInfo['old']['CompName']), $message);
	$message = mb_ereg_replace('###OLDSTAFFCODE###', trim($staffInfo['old']['StaffCode']), $message);
    $message = mb_ereg_replace('###OLDPERSONNAME###', trim($staffInfo['old']['PersonName']), $message);

    $message = mb_ereg_replace('###NEWCOMPCD###', trim($compInfo['new']['CompCd']), $message);
    $message = mb_ereg_replace('###NEWCOMPNAME###', trim($compInfo['new']['CompName']), $message);
    $message = mb_ereg_replace('###NEWSTAFFCODE###', trim($staffInfo['new']['StaffCode']), $message);
    $message = mb_ereg_replace('###NEWPERSONNAME###', trim($staffInfo['new']['PersonName']), $message);

	return true;

}


?>
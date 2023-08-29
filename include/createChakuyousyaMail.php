<?php
/*
 * 着用者変更通知メール生成モジュール
 * createChakuyousyaMail.php
 *
 * create 2007/05/11 H.Osugi
 *
 */

/*
 * 着用者変更通知メールの件名と本文を作成する
 *
 * 引数  ：$post       => POST値
 *       ：$filePath   => メールテンプレートのファイルパス
 *       ：$subject    => 件名
 *       ：$message    => 本文
 *
 * 戻り値：true：メールテンプレート取得成功/false：取得失敗
 *
 * create 2007/05/11 H.Osugi
 *
 */
function chakuyousyaHenkouMail($filePath, $post, &$subject, &$message) {

	// 初期化
	$subject = '';
	$message = '';

	// 件名
	$subject = MAIL_SUBJECT_HEADER . "着用者変更通知";

	// 現在日時
	$date = date("Y年n月j日 H時i分");

	// 本文
	$message = file_get_contents($filePath . 'chakuyousyaHenkou.txt');

	// 本文の置換
	$message = mb_ereg_replace('###HEAD###', MAIL_BODY_HEADER, $message);
	$message = mb_ereg_replace('###DATE###', $date, $message);

	$message = mb_ereg_replace('###COMPCODE###', trim($post['compCd']), $message);
	$message = mb_ereg_replace('###COMPNAME###', trim($post['compName']), $message);
	$message = mb_ereg_replace('###STAFFCODE###', trim($post['staffCode']), $message);

	$message = mb_ereg_replace('###NEWCOMPCODE###', trim($post['searchCompCd']), $message);
	$message = mb_ereg_replace('###NEWCOMPNAME###', trim($post['searchCompName']), $message);
	$message = mb_ereg_replace('###NEWSTAFFCODE###', trim($post['searchStaffCode']), $message);

	return true;

}

/*
 * 店舗移動アラートメールの件名と本文を作成する
 *
 * 引数  ：$post       => POST値
 *       ：$stockouts  => 在庫切れ商品情報
 *       ：$filePath   => メールテンプレートのファイルパス
 *       ：$subject    => 件名
 *       ：$message    => 本文
 *
 * 戻り値：true：メールテンプレート取得成功/false：取得失敗
 *
 * create 2007/06/27 H.Osugi
 *
 */
function chakuyousyaHenkouStockOutMail($filePath, $post, $stockouts, &$subject, &$message) {

	// 初期化
	$subject = '';
	$message = '';

	// 件名
	$subject = MAIL_SUBJECT_HEADER . " 店舗移動アラート";

	$stockOutTemplateHeader = file_get_contents($filePath . 'stockOutTemplateHeader.txt');
	$stockOutTemplate = file_get_contents($filePath . 'stockOutTemplate.txt');

	$orders = '';
	$appliNo = '';
	$countStockout = count($stockouts);
	for ($i=0; $i<$countStockout; $i++) {

		if ($appliNo != $stockouts[$i]['AppliNo']) {

			$orders .= mb_ereg_replace('###APPLINO###', $stockouts[$i]['AppliNo'], $stockOutTemplateHeader);
			$orders .= "\n";

			$appliNo = $stockouts[$i]['AppliNo'];

		}

		$itemData = '';
		$itemData = mb_ereg_replace('###ITEMNO###', $stockouts[$i]['ItemNo'], $stockOutTemplate);
		$itemData = mb_ereg_replace('###ITEMNAME###', $stockouts[$i]['ItemName'], $itemData);
		$itemData = mb_ereg_replace('###SIZE###', $stockouts[$i]['Size'], $itemData);

		$orders .= $itemData;
		$orders .= "\n\n";

	}

	// 現在日時
	$date = date("Y年n月j日 H時i分");

	// 本文
	$message = file_get_contents($filePath . 'chakuyousyaHenkouStockOut.txt');

	// 本文の置換
	$message = mb_ereg_replace('###HEAD###', MAIL_BODY_HEADER, $message);
	$message = mb_ereg_replace('###DATE###', $date, $message);

	$message = mb_ereg_replace('###COMPCODE###', trim($post['compCd']), $message);
	$message = mb_ereg_replace('###COMPNAME###', trim($post['compName']), $message);
	$message = mb_ereg_replace('###STAFFCODE###', trim($post['staffCode']), $message);

	$message = mb_ereg_replace('###NEWCOMPCODE###', trim($post['searchCompCd']), $message);
	$message = mb_ereg_replace('###NEWCOMPNAME###', trim($post['searchCompName']), $message);
	$message = mb_ereg_replace('###NEWSTAFFCODE###', trim($post['searchStaffCode']), $message);
	$message = mb_ereg_replace('###ORDER###', $orders, $message);

	return true;

}
?>
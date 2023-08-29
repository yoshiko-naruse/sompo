<?php
/*
 * 返却申請メール生成モジュール
 * createHenpinMail.php
 *
 * create 2007/04/25 H.Osugi
 *
 */

/*
 * 返却申請メールの件名と本文を作成する
 *
 * 引数  ：$dbConnect  => コネクションハンドラ
 *       ：$orderId    => OrderID
 *       ：$filePath   => メールテンプレートのファイルパス
 *       ：$subject    => 件名
 *       ：$message    => 本文
 *
 * 戻り値：true：メールテンプレート取得成功/false：取得失敗
 *
 * create 2007/04/25 H.Osugi
 *
 */
function henpinShinseiMail($dbConnect, $orderId, $filePath, &$subject, &$message) {

	// 初期化
	$subject = '';
	$message = '';

	// T_Orderの情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" AppliNo,";
	$sql .= 	" AppliReason,";
	$sql .= 	" AppliDay,";
	$sql .= 	" AppliCompCd,";
	$sql .= 	" AppliCompName,";
	$sql .= 	" StaffCode,";
    $sql .=     " PersonName,";
	$sql .= 	" Note,";
	$sql .= 	" TokNote";
	$sql .= " FROM";
	$sql .= 	" T_Order";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;
	
	$orderData = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($orderData) <= 0) {
	 	return false;
	}

	// 件名
	$subject = MAIL_SUBJECT_HEADER . "(" . $orderData[0]['AppliNo'] . ")";
	switch ($orderData[0]['AppliReason']) {
		case APPLI_REASON_RETURN_RETIRE:
			$subject .= "退職・異動返却申請";
			break;

		case APPLI_REASON_RETURN_OTHER:
			$subject .= "その他返却申請";
			break;

		default:
			break;
	}

	// T_Order_Detailsの情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" ItemName,";
	$sql .= 	" Size,";
	$sql .= 	" BarCd,";
	$sql .= 	" Status,";
	$sql .= 	" DamageCheck";
	$sql .= " FROM";
	$sql .= 	" T_Order_Details";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;
	$sql .= " ORDER BY";
	$sql .= 	" OrderDetID ASC";
	
	$orderDetailData = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($orderDetailData) <= 0) {
	 	return false;
	}

	$items = '';
	$countOrderDetail = count($orderDetailData);
	for ($i=0; $i<$countOrderDetail; $i++) {

		// 返却未申請ならば次へ
		if ($orderDetailData[$i]['Status'] == STATUS_RETURN_NOT_APPLY) {
			continue;
		}

		switch ($orderDetailData[$i]['Status']) {

			case STATUS_NOT_RETURN_ADMIT:		// 未返却（承認済）
				$items .= "　○返却：　". $orderDetailData[$i]['ItemName'] . "（" . $orderDetailData[$i]['Size'] . "）";
				break;

			case STATUS_LOSS_ADMIT:				// 紛失（承認済）
				$items .= "　●紛失：　". $orderDetailData[$i]['ItemName'] . "（" . $orderDetailData[$i]['Size'] . "）";
				break;
			default:
				break;
		}

		if (isset($orderDetailData[$i]['BarCd']) && $orderDetailData[$i]['BarCd'] != '') {
			$items .= " " . $orderDetailData[$i]['BarCd'];
		}

		// 汚損・破損の場合
		if (isset($orderDetailData[$i]['DamageCheck']) && $orderDetailData[$i]['DamageCheck'] == 1) {
			$items .= " ▼汚損・破損";
		}

		if ($i < $countOrderDetail - 1) {
			$items .= "\n";
		}
	}

	// 現在日時
	$date = date("Y年n月j日 H時i分");

	// 申請日
	$appliDay = date("Y/m/d", strtotime($orderData[0]['AppliDay']));

	// 本文
	$message = file_get_contents($filePath . 'henpinShinsei.txt');

	// 本文の置換
	$message = mb_ereg_replace('###HEAD###', MAIL_BODY_HEADER, $message);
	$message = mb_ereg_replace('###DATE###', $date, $message);

	// 理由
	switch ($orderData[0]['AppliReason']) {
		case APPLI_REASON_RETURN_RETIRE:
			$message = mb_ereg_replace('###REASON###', "退職・異動", $message);
			break;

		case APPLI_REASON_RETURN_OTHER:
			$message = mb_ereg_replace('###REASON###', "その他", $message);
			break;

		default:
			break;
	}

	$message = mb_ereg_replace('###APPLIDAY###', $appliDay, $message);
	$message = mb_ereg_replace('###APPLINO###', $orderData[0]['AppliNo'], $message);
	$message = mb_ereg_replace('###COMPCODE###', $orderData[0]['AppliCompCd'], $message);
	$message = mb_ereg_replace('###COMPNAME###', $orderData[0]['AppliCompName'], $message);
	$message = mb_ereg_replace('###STAFFCODE###', $orderData[0]['StaffCode'], $message);
    $message = mb_ereg_replace('###PERSONNAME###', $orderData[0]['PersonName'], $message);
	$message = mb_ereg_replace('###NOTE###', $orderData[0]['Note'], $message);
	$message = mb_ereg_replace('###ITEM###', $items, $message);

	return true;

}

/*
 * 返却キャンセルメールの件名と本文を作成する
 *
 * 引数  ：$dbConnect  => コネクションハンドラ
 *       ：$orderId    => OrderID
 *       ：$filePath   => メールテンプレートのファイルパス
 *       ：$subject    => 件名
 *       ：$message    => 本文
 *
 * 戻り値：true：メールテンプレート取得成功/false：取得失敗
 *
 * create 2007/04/25 H.Osugi
 *
 */
function henpinCancelMail($dbConnect, $orderId, $filePath, &$subject, &$message) {

	// 初期化
	$subject = '';
	$message = '';

	// T_Orderの情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" AppliNo,";
	$sql .= 	" AppliReason,";
	$sql .= 	" AppliCompCd,";
	$sql .= 	" AppliCompName,";
	$sql .= 	" StaffCode,";
    $sql .=     " PersonName,";
	$sql .= 	" Note";
	$sql .= " FROM";
	$sql .= 	" T_Order";
	$sql .= " WHERE";
	$sql .= 	" OrderId = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_ON;
	
	$orderData = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($orderData) <= 0) {
	 	return false;
	}

	// 件名
	$subject = MAIL_SUBJECT_HEADER . "(" . $orderData[0]['AppliNo'] . ")";
	switch ($orderData[0]['AppliReason']) {
		case APPLI_REASON_RETURN_RETIRE:
			$subject .= "退職・異動返却キャンセル";
			break;

		case APPLI_REASON_RETURN_OTHER:
			$subject .= "その他返却キャンセル";
			break;

		default:
			break;
	}

	// 現在日時
	$date = date("Y年n月j日 H時i分");

	// 申請日
	$appliDay = date("Y/m/d", strtotime($orderData[0]['AppliDay']));

	// 本文
	$message = file_get_contents($filePath . 'henpinCancel.txt');

	// 本文の置換
	$message = mb_ereg_replace('###HEAD###', MAIL_BODY_HEADER, $message);
	$message = mb_ereg_replace('###DATE###', $date, $message);

	// 理由
	switch ($orderData[0]['AppliReason']) {
		case APPLI_REASON_RETURN_RETIRE:
			$message = mb_ereg_replace('###REASON###', "退職・異動", $message);
			break;

		case APPLI_REASON_RETURN_OTHER:
			$message = mb_ereg_replace('###REASON###', "その他", $message);
			break;

		default:
			break;
	}

	$message = mb_ereg_replace('###APPLIDAY###', $appliDay, $message);
	$message = mb_ereg_replace('###APPLINO###', $orderData[0]['AppliNo'], $message);
	$message = mb_ereg_replace('###COMPCODE###', $orderData[0]['AppliCompCd'], $message);
	$message = mb_ereg_replace('###COMPNAME###', $orderData[0]['AppliCompName'], $message);
	$message = mb_ereg_replace('###STAFFCODE###', $orderData[0]['StaffCode'], $message);
    $message = mb_ereg_replace('###PERSONNAME###', $orderData[0]['PersonName'], $message);
	$message = mb_ereg_replace('###NOTE###', $orderData[0]['Note'], $message);

	return true;

}

/*
 * 退職・異動アラートメールの件名と本文を作成する
 *
 * 引数  ：$dbConnect  => コネクションハンドラ
 *       ：$orderId    => OrderID
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
function henpinStockOutMail($dbConnect, $orderId, $stockouts, $filePath, &$subject, &$message) {

	// 初期化
	$subject = '';
	$message = '';

	// T_Orderの情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" AppliDay,";
	$sql .= 	" AppliCompCd,";
	$sql .= 	" AppliCompName,";
	$sql .= 	" StaffCode,";
    $sql .=     " PersonName";
	$sql .= " FROM";
	$sql .= 	" T_Order";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;
	
	$orderData = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($orderData) <= 0) {
	 	return false;
	}

	// 件名
	$subject = MAIL_SUBJECT_HEADER . " 退職・異動アラート";

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

	// 申請日
	$appliDay = date("Y/m/d", strtotime($orderData[0]['AppliDay']));

	// 本文
	$message = file_get_contents($filePath . 'henpinStockOut.txt');

	// 本文の置換
	$message = mb_ereg_replace('###HEAD###', MAIL_BODY_HEADER, $message);
	$message = mb_ereg_replace('###DATE###', $date, $message);

	$message = mb_ereg_replace('###APPLIDAY###', $appliDay, $message);
	$message = mb_ereg_replace('###COMPCODE###', $orderData[0]['AppliCompCd'], $message);
	$message = mb_ereg_replace('###COMPNAME###', $orderData[0]['AppliCompName'], $message);
	$message = mb_ereg_replace('###STAFFCODE###', $orderData[0]['StaffCode'], $message);
    $message = mb_ereg_replace('###PERSONNAME###', $orderData[0]['PersonName'], $message);
	$message = mb_ereg_replace('###ORDER###', $orders, $message);

	return true;

}

?>
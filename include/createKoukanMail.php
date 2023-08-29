<?php
/*
 * 交換申請メール生成モジュール
 * createKoukanMail.php
 *
 * create 2007/04/25 H.Osugi
 *
 */

/*
 * 交換申請メールの件名と本文を作成する
 *
 * 引数  ：$dbConnect  => コネクションハンドラ
 *       ：$orderId    => OrderID
 *       ：$tolFlg     => 特寸フラグ 1なら特寸
 *       ：$filePath   => メールテンプレートのファイルパス
 *       ：$subject    => 件名
 *       ：$message    => 本文
 *
 * 戻り値：true：メールテンプレート取得成功/false：取得失敗
 *
 * create 2007/04/25 H.Osugi
 *
 */
function koukanShinseiMail($dbConnect, $orderId, $returnOrderId, $tokFlg, $filePath, &$subject, &$message) {

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
		case APPLI_REASON_EXCHANGE_SIZE:
			$subject .= "サイズ交換申請";
			break;

		case APPLI_REASON_EXCHANGE_INFERIORITY:
			$subject .= "不良品交換申請";
			break;

		case APPLI_REASON_EXCHANGE_LOSS:
			$subject .= "紛失交換申請";
			break;

		case APPLI_REASON_EXCHANGE_BREAK:
			$subject .= "汚損・破損交換申請";
			break;

        case APPLI_REASON_EXCHANGE_CHANGEGRADE:
            $subject .= "役職変更交換申請";
            break;

        case APPLI_REASON_EXCHANGE_MATERNITY:
            $subject .= "マタニティ交換申請";
            break;

		case APPLI_REASON_EXCHANGE_REPAIR:
			$subject .= "修理交換申請";
			break;

		default:
			break;
	}
	if ($tokFlg == 1) {
		$subject .= " （特注）";
	}

	// T_Order_Detailsの情報を取得する（返却）
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
	$sql .= 	" OrderID = '" . db_Escape($returnOrderId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;
	$sql .= " ORDER BY";
	$sql .= 	" OrderDetID ASC";
	
	$returnDetailData = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($returnDetailData) <= 0) {
	 	return false;
	}

	$returns = '';
	$countReturnDetail = count($returnDetailData);
	for ($i=0; $i<$countReturnDetail; $i++) {

		switch ($returnDetailData[$i]['Status']) {
			case STATUS_NOT_RETURN:				// 未返却（承認待）
			case STATUS_NOT_RETURN_ADMIT:		// 未返却（承認済）
				$returns .= "　○返却：　". $returnDetailData[$i]['ItemName'] . "（" . $returnDetailData[$i]['Size'] . "）";
				break;

			case STATUS_LOSS:					// 紛失（承認待）
			case STATUS_LOSS_ADMIT:				// 紛失（承認済）
				$returns .= "　●紛失：　". $returnDetailData[$i]['ItemName'] . "（" . $returnDetailData[$i]['Size'] . "）";
				break;
			default:
				break;
		}

		if (isset($returnDetailData[$i]['BarCd']) && $returnDetailData[$i]['BarCd'] != '') {
			$returns .= " " . $returnDetailData[$i]['BarCd'];
		}

		// 汚損・破損の場合
		if (isset($returnDetailData[$i]['DamageCheck']) && $returnDetailData[$i]['DamageCheck'] == 1) {
			$returns .= " ▼汚損・破損";
		}

		if ($i < $countReturnDetail - 1) {
			$returns .= "\n";
		}

	}

	// T_Order_Detailsの情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" ItemName,";
	$sql .= 	" Size,";
	$sql .= 	" BarCd";
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

	$orders = '';
	$countOrderDetail = count($orderDetailData);
	for ($i=0; $i<$countOrderDetail; $i++) {

		$orders .= "　○発注：　". $orderDetailData[$i]['ItemName'] . "（" . $orderDetailData[$i]['Size'] . "）";

		if (isset($orderDetailData[$i]['BarCd']) && $orderDetailData[$i]['BarCd'] != '') {
			$orders .= " " . $orderDetailData[$i]['BarCd'];
		}

		if ($i < $countOrderDetail - 1) {
			$orders .= "\n";
		}
	}

	$tokusun = '';
	if ($tokFlg == 1) {

		// T_Tokの情報を取得する
		$sql  = "";
		$sql .= " SELECT";
		$sql .= 	" Height,";
		$sql .= 	" Weight,";
		$sql .= 	" Bust,";
		$sql .= 	" Waist,";
		$sql .= 	" Hips,";
		$sql .= 	" Shoulder,";
		$sql .= 	" Sleeve,";
		$sql .= 	" Length,";
        $sql .=     " Kitake,";
        $sql .=     " Yukitake,";
        $sql .=     " Inseam";
		$sql .= " FROM";
		$sql .= 	" T_Tok";
		$sql .= " WHERE";
		$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
		$sql .= " AND";
		$sql .= 	" Del = " . DELETE_OFF;
		
		$tokData = db_Read($dbConnect, $sql);
	
		// 検索結果が0件の場合
		if (count($tokData) <= 0) {
		 	return false;
		}

		// 特寸部分のテンプレート
		$tokusun = file_get_contents($filePath . 'tokusunTemplate.txt');

		// 特寸部分のテンプレートの置換
		$tokusun = mb_ereg_replace('###HEIGHT###', $tokData[0]['Height'], $tokusun);
		$tokusun = mb_ereg_replace('###WEIGHT###', $tokData[0]['Weight'], $tokusun);
		$tokusun = mb_ereg_replace('###BUST###', $tokData[0]['Bust'], $tokusun);
		$tokusun = mb_ereg_replace('###WAIST###', $tokData[0]['Waist'], $tokusun);
		$tokusun = mb_ereg_replace('###HIPS###', $tokData[0]['Hips'], $tokusun);
		$tokusun = mb_ereg_replace('###SHOULDER###', $tokData[0]['Shoulder'], $tokusun);
		$tokusun = mb_ereg_replace('###SLEEVE###', $tokData[0]['Sleeve'], $tokusun);
		$tokusun = mb_ereg_replace('###SETALE###', $tokData[0]['Length'], $tokusun);
        $tokusun = mb_ereg_replace('###KITAKE###', $tokData[0]['Kitake'], $tokusun);
        $tokusun = mb_ereg_replace('###YUKITAKE###', $tokData[0]['Yukitake'], $tokusun);
        $tokusun = mb_ereg_replace('###INSEAM###', $tokData[0]['Inseam'], $tokusun);
		$tokusun = mb_ereg_replace('###TOKNOTE###', $orderData[0]['TokNote'], $tokusun);

	}

	// 現在日時
	$date = date("Y年n月j日 H時i分");

	// 申請日
	$appliDay = date("Y/m/d", strtotime($orderData[0]['AppliDay']));

	// 本文
	$message = file_get_contents($filePath . 'koukanShinsei.txt');

	// 本文の置換
	$message = mb_ereg_replace('###HEAD###', MAIL_BODY_HEADER, $message);
	$message = mb_ereg_replace('###DATE###', $date, $message);

	if ($tokFlg == 1) {
		$message = mb_ereg_replace('###TOK###', "(特注)", $message);
	}
	else {
		$message = mb_ereg_replace('###TOK###', "", $message);
	}

	// 理由
	switch ($orderData[0]['AppliReason']) {
		case APPLI_REASON_EXCHANGE_SIZE:
			$reason = "サイズ交換";
			break;

		case APPLI_REASON_EXCHANGE_INFERIORITY:
			$reason = "不良品交換";
			break;

		case APPLI_REASON_EXCHANGE_LOSS:
			$reason = "紛失交換";
			break;

		case APPLI_REASON_EXCHANGE_BREAK:
			$reason = "汚損・破損交換";
			break;

        case APPLI_REASON_EXCHANGE_CHANGEGRADE:
            $reason = "役職変更交換申請";
            break;

        case APPLI_REASON_EXCHANGE_MATERNITY:
            $reason = "マタニティ交換申請";
            break;

        case APPLI_REASON_EXCHANGE_REPAIR:
            $reason = "修理交換申請";
            break;

		default:
			break;
	}

    $message = mb_ereg_replace('###REASON###', $reason, $message);
	$message = mb_ereg_replace('###APPLIDAY###', $appliDay, $message);
	$message = mb_ereg_replace('###APPLINO###', $orderData[0]['AppliNo'], $message);
	$message = mb_ereg_replace('###COMPCODE###', $orderData[0]['AppliCompCd'], $message);
	$message = mb_ereg_replace('###COMPNAME###', $orderData[0]['AppliCompName'], $message);
	$message = mb_ereg_replace('###STAFFCODE###', $orderData[0]['StaffCode'], $message);
    $message = mb_ereg_replace('###PERSONNAME###', $orderData[0]['PersonName'], $message);
	$message = mb_ereg_replace('###NOTE###', $orderData[0]['Note'], $message);
	$message = mb_ereg_replace('###RETURNITEM###', $returns, $message);
	$message = mb_ereg_replace('###ORDERITEM###', $orders, $message);
	$message = mb_ereg_replace('###TOKUSUN###', $tokusun, $message);

	return true;

}

/*
 * 交換キャンセルメールの件名と本文を作成する
 *
 * 引数  ：$dbConnect  => コネクションハンドラ
 *       ：$orderId    => OrderID
 *       ：$filePath   => メールテンプレートのファイルパス
 *       ：$subject    => 件名
 *       ：$message    => 本文
 *       ：$tolFlg     => 特寸フラグ 1なら特寸
 *
 * 戻り値：true：メールテンプレート取得成功/false：取得失敗
 *
 * create 2007/04/25 H.Osugi
 *
 */
function koukanCancelMail($dbConnect, $orderId, $filePath, &$subject, &$message, &$tokFlg) {

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
	$sql .= 	" Note,";
	$sql .= 	" Tok";
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

	$tokFlg = $orderData[0]['Tok'];

	// 件名
	$subject = MAIL_SUBJECT_HEADER . "(" . $orderData[0]['AppliNo'] . ")";
	switch ($orderData[0]['AppliReason']) {
		case APPLI_REASON_EXCHANGE_SIZE:
			$subject .= "サイズ交換キャンセル";
			break;

		case APPLI_REASON_EXCHANGE_INFERIORITY:
			$subject .= "不良品交換キャンセル";
			break;

		case APPLI_REASON_EXCHANGE_LOSS:
			$subject .= "紛失交換キャンセル";
			break;

		case APPLI_REASON_EXCHANGE_BREAK:
			$subject .= "汚損・破損交換キャンセル";
			break;

        case APPLI_REASON_EXCHANGE_CHANGEGRADE:
            $subject .= "役職変更交換キャンセル";
            break;

        case APPLI_REASON_EXCHANGE_MATERNITY:
            $subject .= "マタニティ交換キャンセル";
            break;

		case APPLI_REASON_EXCHANGE_REPAIR:
			$subject .= "修理交換キャンセル";
			break;

		default:
			break;
	}
	if ($tokFlg == 1) {
		$subject .= " （特注）";
	}


	// 現在日時
	$date = date("Y年n月j日 H時i分");

	// 申請日
	$appliDay = date("Y/m/d", strtotime($orderData[0]['AppliDay']));

	// 本文
	$message = file_get_contents($filePath . 'koukanCancel.txt');

	// 本文の置換
	$message = mb_ereg_replace('###HEAD###', MAIL_BODY_HEADER, $message);
	$message = mb_ereg_replace('###DATE###', $date, $message);

	if ($tokFlg == 1) {
		$message = mb_ereg_replace('###TOK###', "(特注)", $message);
	}
	else {
		$message = mb_ereg_replace('###TOK###', "", $message);
	}

	// 理由
	switch ($orderData[0]['AppliReason']) {
		case APPLI_REASON_EXCHANGE_SIZE:
			$reason = "サイズ交換";
			break;

		case APPLI_REASON_EXCHANGE_INFERIORITY:
			$reason = "不良品交換";
			break;

		case APPLI_REASON_EXCHANGE_LOSS:
			$reason = "紛失交換";
			break;

		case APPLI_REASON_EXCHANGE_BREAK:
			$reason = "汚損・破損交換";
			break;

        case APPLI_REASON_EXCHANGE_CHANGEGRADE:
            $reason = "役職変更交換";
            break;

        case APPLI_REASON_EXCHANGE_MATERNITY:
            $reason = "マタニティ交換";
            break;

        case APPLI_REASON_EXCHANGE_REPAIR:
            $reason = "修理交換申請";
            break;

		default:
			break;
	}

    $message = mb_ereg_replace('###REASON###', $reason, $message);
	$message = mb_ereg_replace('###APPLIDAY###', $appliDay, $message);
	$message = mb_ereg_replace('###APPLINO###', $orderData[0]['AppliNo'], $message);
	$message = mb_ereg_replace('###COMPCODE###', $orderData[0]['AppliCompCd'], $message);
	$message = mb_ereg_replace('###COMPNAME###', $orderData[0]['AppliCompName'], $message);
	$message = mb_ereg_replace('###STAFFCODE###', $orderData[0]['StaffCode'], $message);
    $message = mb_ereg_replace('###PERSONNAME###', $orderData[0]['PersonName'], $message);
	$message = mb_ereg_replace('###NOTE###', $orderData[0]['Note'], $message);

	return true;

}

?>
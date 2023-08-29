<?php
/*
 * 発注申請メール生成モジュール
 * createHachuMail.php
 *
 * create 2007/03/30 H.Osugi
 *
 */

/*
 * 発注申請メールの件名と本文を作成する
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
 * create 2007/03/30 H.Osugi
 *
 */
function hachuShinseiMail($dbConnect, $orderId, $tokFlg, $filePath, &$subject, &$message) {

	// 初期化
	$subject = '';
	$message = '';

	// T_Orderの情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" AppliNo,";
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
	$subject = MAIL_SUBJECT_HEADER . "(" . $orderData[0]['AppliNo'] . ")発注申請";
	if ($tokFlg == 1) {
		$subject .= " （特注）";
	}

	// T_Order_Detailsの情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" ItemName,";
	$sql .= 	" Size";
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
		$items .= "　○発注：　". $orderDetailData[$i]['ItemName'] . "（" . $orderDetailData[$i]['Size'] . "）";
		if ($i < $countOrderDetail - 1) {
			$items .= "\n";
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
	$message = file_get_contents($filePath . 'hachuShinsei.txt');

	// 本文の置換
	$message = mb_ereg_replace('###HEAD###', MAIL_BODY_HEADER, $message);
	$message = mb_ereg_replace('###DATE###', $date, $message);

	if ($tokFlg == 1) {
		$message = mb_ereg_replace('###TOK###', "(特注)", $message);
	}
	else {
		$message = mb_ereg_replace('###TOK###', "", $message);
	}

	$message = mb_ereg_replace('###APPLIDAY###', $appliDay, $message);
	$message = mb_ereg_replace('###APPLINO###', $orderData[0]['AppliNo'], $message);
	$message = mb_ereg_replace('###COMPCODE###', $orderData[0]['AppliCompCd'], $message);
	$message = mb_ereg_replace('###COMPNAME###', $orderData[0]['AppliCompName'], $message);
	$message = mb_ereg_replace('###STAFFCODE###', $orderData[0]['StaffCode'], $message);
    $message = mb_ereg_replace('###PERSONNAME###', $orderData[0]['PersonName'], $message);
	$message = mb_ereg_replace('###NOTE###', $orderData[0]['Note'], $message);
	$message = mb_ereg_replace('###ITEM###', $items, $message);
	$message = mb_ereg_replace('###TOKUSUN###', $tokusun, $message);

	return true;

}

/*
 * 発注訂正メールの件名と本文を作成する
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
 * create 2007/04/02 H.Osugi
 *
 */
function hachuTeiseiMail($dbConnect, $orderId, $tokFlg, $motoTokFlg, $filePath, &$subject, &$message) {

	// 初期化
	$subject = '';
	$message = '';

	// T_Orderの情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" AppliNo,";
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
	$subject = MAIL_SUBJECT_HEADER . "(" . $orderData[0]['AppliNo'] . ")発注訂正";
	if ($tokFlg == 1 || $motoTokFlg == 1) {
		$subject .= " （特注）";
	}

	// T_Order_Detailsの情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" ItemName,";
	$sql .= 	" Size";
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
		$items .= "  ○発注：  ". $orderDetailData[$i]['ItemName'] . "（" . $orderDetailData[$i]['Size'] . "）";
		if ($i < $countOrderDetail - 1) {
			$items .= "\n";
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
	$message = file_get_contents($filePath . 'hachuTeisei.txt');

	// 本文の置換
	$message = mb_ereg_replace('###HEAD###', MAIL_BODY_HEADER, $message);
	$message = mb_ereg_replace('###DATE###', $date, $message);

	if ($tokFlg == 1 || $motoTokFlg == 1) {
		$message = mb_ereg_replace('###TOK###', "(特注)", $message);
	}
	else {
		$message = mb_ereg_replace('###TOK###', "", $message);
	}

	$message = mb_ereg_replace('###APPLIDAY###', $appliDay, $message);
	$message = mb_ereg_replace('###APPLINO###', $orderData[0]['AppliNo'], $message);
	$message = mb_ereg_replace('###COMPCODE###', $orderData[0]['AppliCompCd'], $message);
	$message = mb_ereg_replace('###COMPNAME###', $orderData[0]['AppliCompName'], $message);
	$message = mb_ereg_replace('###STAFFCODE###', $orderData[0]['StaffCode'], $message);
    $message = mb_ereg_replace('###PERSONNAME###', $orderData[0]['PersonName'], $message);
	$message = mb_ereg_replace('###NOTE###', $orderData[0]['Note'], $message);
	$message = mb_ereg_replace('###ITEM###', $items, $message);
	$message = mb_ereg_replace('###TOKUSUN###', $tokusun, $message);

	return true;

}

/*
 * 発注キャンセルメールの件名と本文を作成する
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
 * create 2007/04/02 H.Osugi
 *
 */
function hachuCancelMail($dbConnect, $orderId, $filePath, &$subject, &$message, &$tokFlg) {

	// 初期化
	$subject = '';
	$message = '';

	// T_Orderの情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" AppliNo,";
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
	$subject = MAIL_SUBJECT_HEADER . "(" . $orderData[0]['AppliNo'] . ")発注キャンセル";
	if ($tokFlg == 1) {
		$subject .= " （特注）";
	}

	// 現在日時
	$date = date("Y年n月j日 H時i分");

	// 申請日
	$appliDay = date("Y/m/d", strtotime($orderData[0]['AppliDay']));

	// 本文
	$message = file_get_contents($filePath . 'hachuCancel.txt');

	// 本文の置換
	$message = mb_ereg_replace('###HEAD###', MAIL_BODY_HEADER, $message);
	$message = mb_ereg_replace('###DATE###', $date, $message);

	if ($tokFlg == 1) {
		$message = mb_ereg_replace('###TOK###', "(特注)", $message);
	}
	else {
		$message = mb_ereg_replace('###TOK###', "", $message);
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

?>
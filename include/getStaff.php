<?php
/*
 * スタッフ情報取得モジュール
 * getStaff.php
 *
 * create 2007/03/19 H.Osugi
 *
 */

/*
 * スタッフコードとStaffIDを一覧取得する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$compId    => 店舗ID
 *       ：$mode      => 取得モード 1:サイズ交換 2:汚損・破損交換 3:紛失交換 4:不良品交換 / 11:退職・異動返却 12:その他返却
 *       ：$isEntity  => 0：エンティティしない / 1：エンティティする
 * 戻り値：サイズ情報（array）
 *
 * create 2007/03/19 H.Osugi
 *
 */
function getStaffAll($dbConnect, $compId, $mode, $isEntity = 0) {

	// 初期化
	$result = array();

	// スタッフコードの一覧を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" DISTINCT";
	$sql .= 	" ts.StaffID,";
	$sql .= 	" ts.StaffCode";
	$sql .= " FROM";
	$sql .= 	" T_Staff ts";
	$sql .= " INNER JOIN";
	$sql .= 	" T_Staff_Details tsd";
	$sql .= " ON";
	$sql .= 	" ts.StaffID = tsd.StaffID";
	$sql .= " AND";
	$sql .= 	" tsd.Del = " . DELETE_OFF;
	$sql .= " INNER JOIN";
	$sql .= 	" T_Order_Details tod";
	$sql .= " ON";
	$sql .= 	" tsd.OrderDetID = tod.OrderDetID";
	$sql .= " AND";
	$sql .= 	" tod.Del = " . DELETE_OFF;

	// サイズ交換の場合はSizeが１つの商品ははぶく
	if ($mode == 1) {
		$sql .= " INNER JOIN";
		$sql .= 	" M_Item mi";
		$sql .= " ON";
		$sql .= 	" tod.ItemID = mi.ItemID";
		$sql .= " AND";
		$sql .= 	" mi.Del = " . DELETE_OFF;
	}

	$sql .= " WHERE";
	$sql .= 	" ts.CompID = '" . db_Escape($compId) . "'";
	$sql .= " AND";
	$sql .= 	" tsd.Status IN ( " . STATUS_SHIP . ", ". STATUS_DELIVERY .")";		// ステータスが出荷済(15),納品済(16)

	// サイズ交換の場合はSizeが１つの商品ははぶく
	if ($mode == 1) {
		$sql .= " AND";
		$sql .= 	" mi.SizeID <> 3";
	}

	////$sql .= " AND";
	////$sql .= 	" ts.AllReturnFlag = 0";
	$sql .= " AND";
	$sql .= 	" ts.Del = " . DELETE_OFF;
	$sql .= " ORDER BY";
	$sql .= 	" ts.StaffCode ASC";

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	// エンティティ処理を行わない場合
	if ($isEntity == 0) {
		return $result;
	}

	// 取得した値をHTMLエンティティ
	$resultCount = count($result);
	for ($i=0; $i<$resultCount; $i++) {
		$result[$i]['StaffCode'] = htmlspecialchars($result[$i]['StaffCode'], HTMLENTITY_QUOTE_STYLE, HTMLENTITY_ENCODE);
		$result[$i]['StaffID'] = $result[$i]['StaffID'];
	}
	
	return  $result;
	
}

/*
 * スタッフコードとStaffIDを取得する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$compId    => 店舗ID
 *       ：$staffCode => スタッフコード
 *       ：$mode      => 取得モード 1:サイズ交換 2:汚損・破損交換 3:紛失交換 4:不良品交換 / 11:退職・異動返却 12:その他返却
 *       ：$isEntity  => 0：エンティティしない / 1：エンティティする
 * 戻り値：サイズ情報（array）
 *
 * create 2007/04/10 H.Osugi
 *
 */
function getStaff($dbConnect, $compId, $staffCode, $mode, $isEntity = 0) {

	// 初期化
	$result = array();

	// スタッフコードの一覧を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" DISTINCT";
	$sql .= 	" ts.StaffID,";
	$sql .= 	" ts.StaffCode";
	$sql .= " FROM";
	$sql .= 	" T_Staff ts";
	$sql .= " INNER JOIN";
	$sql .= 	" T_Staff_Details tsd";
	$sql .= " ON";
	$sql .= 	" ts.StaffID = tsd.StaffID";
	$sql .= " AND";
	$sql .= 	" tsd.Del = " . DELETE_OFF;
	$sql .= " INNER JOIN";
	$sql .= 	" T_Order_Details tod";
	$sql .= " ON";
	$sql .= 	" tsd.OrderDetID = tod.OrderDetID";
	$sql .= " AND";
	$sql .= 	" tod.Del = " . DELETE_OFF;

	// サイズ交換の場合はSizeが１つの商品ははぶく
	if ($mode == 1) {
		$sql .= " INNER JOIN";
		$sql .= 	" M_Item mi";
		$sql .= " ON";
		$sql .= 	" tod.ItemID = mi.ItemID";
		$sql .= " AND";
		$sql .= 	" mi.Del = " . DELETE_OFF;
	}

	$sql .= " WHERE";
	$sql .= 	" ts.CompID = '" . db_Escape($compId) . "'";
    if (isSetValue($staffCode)) {
    	$sql .= " AND";
    	$sql .= 	" ts.StaffCode = '" . db_Escape($staffCode) . "'";
    }

	switch ($mode) {
		case 11:			// 退職・異動返却の場合のみ返却未申請のユニフォームを持っているスタッフを表示する
			$sql .= " AND";
			$sql .= 	" tsd.Status IN ( " . STATUS_SHIP . ", ". STATUS_DELIVERY ." , " . STATUS_RETURN_NOT_APPLY . ")";		// ステータスが出荷済(15),納品済(16),返却未申請（25）
			break;
		default:
			$sql .= " AND";
			$sql .= 	" tsd.Status IN ( " . STATUS_SHIP . ", ". STATUS_DELIVERY .")";		// ステータスが出荷済(15),納品済(16)
			break;
	}

	// サイズ交換の場合はSizeが１つの商品ははぶく
	if ($mode == 1) {
		$sql .= " AND";
		$sql .= 	" mi.SizeID <> 3";
	}

	////$sql .= " AND";
	////$sql .= 	" ts.AllReturnFlag = 0";
	$sql .= " AND";
	$sql .= 	" ts.Del = " . DELETE_OFF;
	$sql .= " ORDER BY";
	$sql .= 	" ts.StaffCode ASC";

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	// エンティティ処理を行わない場合
	if ($isEntity == 0) {
		return $result;
	}

	// 取得した値をHTMLエンティティ
	$resultCount = count($result);
	for ($i=0; $i<$resultCount; $i++) {
		$result[$i]['StaffCode'] = htmlspecialchars($result[$i]['StaffCode'], HTMLENTITY_QUOTE_STYLE, HTMLENTITY_ENCODE);
		$result[$i]['StaffID'] = $result[$i]['StaffID'];
	}
	
	return  $result;
	
}


/*
 * スタッフコードを取得する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$staffId    => StaffID
 *       ：$isEntity  => 0：エンティティしない / 1：エンティティする
 * 戻り値：スタッフコード
 *
 * create 2007/04/06 H.Osugi
 *
 */
function getStaffCode($dbConnect, $staffId, $isEntity = 0) {

	// 初期化
	$result = array();

	// スタッフコードを取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" StaffCode";
	$sql .= " FROM";
	$sql .= 	" T_Staff";
	$sql .= " WHERE";
	$sql .= 	" StaffID = '" . db_Escape($staffId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (!isset($result[0]['StaffCode']) || count($result) <= 0) {
	 	return false;
	}

	$staffCode = $result[0]['StaffCode'];

	// エンティティ処理を行わない場合
	if ($isEntity == 0) {
		return $staffCode;
	}

	// 取得した値をHTMLエンティティ
	$staffCode = htmlspecialchars($staffCode, HTMLENTITY_QUOTE_STYLE, HTMLENTITY_ENCODE);
	
	return  $staffCode;
	
}

/*
 * スタッフ名を取得する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$staffId    => StaffID
 *       ：$isEntity  => 0：エンティティしない / 1：エンティティする
 * 戻り値：スタッフコード
 *
 * create 2007/04/06 H.Osugi
 *
 */
function getStaffName($dbConnect, $staffId, $isEntity = 0) {

    // 初期化
    $result = array();

    // スタッフコードを取得する
    $sql  = "";
    $sql .= " SELECT";
    $sql .=     " MS.PersonName";
    $sql .= " FROM";
    $sql .=     " T_Staff TS";
    $sql .=     " INNER JOIN";
    $sql .=     " M_Staff MS";
    $sql .=     " ON";
    $sql .=     " MS.StaffSeqID = TS.StaffID";
    $sql .= " WHERE";
    $sql .=     " TS.StaffID = '" . db_Escape($staffId) . "'";
    $sql .= " AND";
    $sql .=     " TS.Del = " . DELETE_OFF;
    $sql .= " AND";
    $sql .=     " MS.Del = " . DELETE_OFF;

    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合
    if (!isset($result[0]['PersonName']) || count($result) <= 0) {
        return false;
    }

    $personName = $result[0]['PersonName'];

    // エンティティ処理を行わない場合
    if ($isEntity == 0) {
        return $personName;
    }

    // 取得した値をHTMLエンティティ
    $$personName = htmlspecialchars($personName, HTMLENTITY_QUOTE_STYLE, HTMLENTITY_ENCODE);
    
    return  $personName;
    
}

?>
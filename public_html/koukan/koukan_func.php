<?php
/*
 * 交換機能で使用する共通関数
 * koukan_func.php
 *
 * create 2008/04/22 W.Takasaki
 *
 *
 */

/*
 * 交換可能商品一覧情報を取得する
 * 引数  ：$dbConnect    => コネクションハンドラ
 *       ：$staffId      => StaffID
 *       ：$appliReason => 交換理由 
 *       ：$compId       => 店舗ID
 *       ：$post         => POST値
 * 戻り値：$result       => 交換可能商品一覧情報
 *
 * create 2007/03/19 H.Osugi
 *
 */
function getStaffOrder($dbConnect, $staffId, $appliReason, $post) {

    // 初期化
    $result = array();

    // 商品の一覧を取得する
    $sql  = "";
    $sql .= " SELECT";
    $sql .=     " tod.OrderDetID,";
    $sql .=     " mi.ItemID,";
    $sql .=     " mi.ItemName,";
    $sql .=     " tod.BarCd,";
    $sql .=     " tod.Size,";
    $sql .=     " mi.SizeID";
    $sql .= " FROM";
    $sql .=     " T_Staff_Details tsd";
    $sql .= " INNER JOIN";
    $sql .=     " T_Staff ts";
    $sql .= " ON";
    $sql .=     " tsd.StaffID = ts.StaffID";
    $sql .= " AND";
    $sql .=     " ts.Del = " . DELETE_OFF;
    $sql .= " INNER JOIN";
    $sql .=     " T_Order_Details tod";
    $sql .= " ON";
    $sql .=     " tsd.OrderDetID = tod.OrderDetID";
    $sql .= " AND";
    $sql .=     " tod.Del = " . DELETE_OFF;
    // 初回サイズ交換の場合は、出荷から一定期間内であれば交換可能とする。
    if ($appliReason == APPLI_REASON_EXCHANGE_FIRST) {
        // 出荷日から起算して10日以上経過している商品は表示しない
// 一時的に日数制限を解除 by T.Uno at 2023/04/27
//        $sql .= " AND";
//        $sql .=     " CONVERT(char, tod.ShipDay, 111) >= '" . date("Y/m/d", strtotime(EXCHANGE_TERM)) . "'";
    }

    $sql .= " INNER JOIN";
    $sql .=     " M_Item mi";
    $sql .= " ON";
    $sql .=     " tod.ItemID = mi.ItemID";
    $sql .= " AND";
    $sql .=     " mi.Del = " . DELETE_OFF;
    $sql .= " INNER JOIN";
    $sql .=     " T_Order tor";
    $sql .= " ON";
    $sql .=     " tod.OrderID = tor.OrderID";
    $sql .= " AND";
    $sql .=     " tor.Del = " . DELETE_OFF;
    $sql .= " WHERE";
    $sql .=     " ts.StaffID = '" . db_Escape($staffId) . "'";
    $sql .= " AND";
    $sql .=     " tsd.ReturnFlag = 0";
    $sql .= " AND";
    $sql .=     " tsd.ReturnDetID IS NULL";
    $sql .= " AND";
    $sql .=     " tsd.Status IN ( " . STATUS_SHIP . ", ". STATUS_DELIVERY .")";     // ステータスが出荷済(15),納品済(16)
    $sql .= " AND";
    $sql .=     " tsd.Del = " . DELETE_OFF;
    $sql .= " ORDER BY";
    $sql .=     " mi.ItemID ASC";
    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合
    if (count($result) <= 0) {
        $result = array();
        return $result;
    }

    // 取得した値をHTMLエンティティ
    $resultCount = count($result);
    $returnAry = array();

	$j = 0;
    for ($i=0; $i<$resultCount; $i++) {

        // サイズ展開を取得
        $sizeData = array();
        $sizeData = getSize($dbConnect, $result[$i]['SizeID'], 1);

        // サイズ交換の場合はSizeが１つの商品ははぶく
        if ($appliReason == APPLI_REASON_EXCHANGE_FIRST || $appliReason == APPLI_REASON_EXCHANGE_SIZE) {
            if (count($sizeData) <= 1) {
                continue;
            }
        }

        $returnAry[$j]['OrderDetID'] = $result[$i]['OrderDetID'];
        $returnAry[$j]['ItemName']   = castHtmlEntity($result[$i]['ItemName']);
        $returnAry[$j]['BarCd']      = castHtmlEntity($result[$i]['BarCd']);
        $returnAry[$j]['Size']       = castHtmlEntity($result[$i]['Size']);
        $returnAry[$j]['SizeID']     = $result[$i]['SizeID'];
        // バーコードが空かどうか判定
        $returnAry[$j]['isEmptyBarCd'] = false;
        if ($returnAry[$j]['BarCd'] == '') {
            $returnAry[$j]['isEmptyBarCd'] = true;
        }

        // 選択チェックボックスが選択されているか判定
        $returnAry[$j]['checked'] = false;
        if (is_array($post['orderDetIds'])) {
            if (in_array($returnAry[$j]['OrderDetID'], $post['orderDetIds'])) {
                $returnAry[$j]['checked'] = true;
            }
        }

        // サイズのリストボックス情報を生成
        $returnAry[$j]['isSelect'] = false;
        if (isset($sizeData)) {

            // サイズが1件のものはリストボックスにはせずに文字列表示
            if (count($sizeData) == 1) {
                $returnAry[$j]['sizes'] = castHtmlEntity($sizeData['Size1']);
            } else {

                // 初期化
                $returnAry[$j]['sizes'] = array();
                $returnAry[$j]['isSelect'] = true;
                $selectedSize = '';

                if (isset($post['size'][$result[$i]['OrderDetID']])) {
                    $selectedSize = trim($post['size'][$result[$i]['OrderDetID']]);
                } elseif ($appliReason != APPLI_REASON_EXCHANGE_FIRST && $appliReason != APPLI_REASON_EXCHANGE_SIZE) {
                    // 初回サイズ交換、サイズ交換以外は現在のサイズを初期表示する
                    foreach($sizeData as $key => $value) {
                        if ($returnAry[$j]['Size'] == $value) {
                            $selectedSize = $key;
                            break;
                        }
                    }
                }

                // リストボックス用に値を成型    
                $returnAry[$j]['sizes'] = castListboxSize($sizeData, $selectedSize);
            }
        }

        // 選択チェックボックスが選択されているか判定
        $returnAry[$j]['isUnused'] = false;
        if (isset($post['itemUnused'][$result[$i]['OrderDetID']]) && trim($post['itemUnused'][$result[$i]['OrderDetID']]) == '1') {
            $returnAry[$j]['isUnused'] = true;
        }

		$j++;
    }

    return  $returnAry;

}

/*
 * 交換未出荷カウント
 * 交換後商品（サイズ含めて）が未出荷商品があれば、そのアイテムはサイズ交換不可とする
 
 * 引数  ：$dbConnect    => コネクションハンドラ
 *       ：$staffId      => StaffID
 *       ：$appliReason => 交換理由 
 *       ：$compId       => 店舗ID
 *       ：$post         => POST値
 * 戻り値：$result       => 交換可能商品一覧情報
 *
 * create 2007/03/19 H.Osugi
 *
 */
function getSizeKoukanUnshipped($dbConnect, $staffId, $appliReason, $itemID) {

    // 初期化
    $result = array();

	// 着用状況の一覧を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" tod.ItemID,";
	$sql .= 	" tod.Status,";
	$sql .= 	" count(tod.OrderDetID) AS ItemCount";
	$sql .= " FROM";
	$sql .= 	" T_Order_Details tod";
	$sql .= " INNER JOIN";
	$sql .= 	" (";
	$sql .= 		" (";
	$sql .= 			" SELECT";
	$sql .= 				" DISTINCT";
	$sql .= 				" tsd_uni1.OrderDetID,";
	$sql .= 				" tsd_uni1.StaffID,";
	$sql .= 				" tsd_uni1.Status";
	$sql .= 			" FROM";
	$sql .= 				" T_Order_Details tod_uni1";
	$sql .= 			" INNER JOIN";
	$sql .= 				" T_Staff_Details tsd_uni1";
	$sql .= 			" ON";
	$sql .= 				" tod_uni1.OrderDetID = tsd_uni1.OrderDetID";
	$sql .= 			" AND";
	$sql .= 				" tsd_uni1.ReturnDetID is NULL";
	$sql .= 			" AND";
	$sql .= 				" tsd_uni1.Del = " . DELETE_OFF;
	$sql .= 			" WHERE";
	$sql .= 				" tod_uni1.Del = " . DELETE_OFF;

	$sql .= 			" AND";
	$sql .= 				" tsd_uni1.Status IN (";
	$sql .= 					"'" . STATUS_APPLI . "',";            // 申請済（承認待）
	$sql .= 					"'" . STATUS_APPLI_ADMIT . "',";      // 申請済（承認済）
	$sql .= 					"'" . STATUS_STOCKOUT . "',";         // 在庫切
	$sql .= 					"'" . STATUS_ORDER . "',";            // 受注済
	$sql .= 					"'" . STATUS_SHIP . "',";             // 出荷済
	$sql .= 					"'" . STATUS_DELIVERY . "'";          // 納品済
	$sql .= 				" )";

	$sql .= 		" )";
	$sql .= 	" UNION ALL";
	$sql .= 		" (";
	$sql .= 			" SELECT";
	$sql .= 				" DISTINCT";
	$sql .= 				" tod_uni2.OrderDetID,";
	$sql .= 				" tsd_uni2.StaffID,";
	$sql .= 				" tsd_uni2.Status";
	$sql .= 			" FROM";
	$sql .= 				" T_Order_Details tod_uni2";
	$sql .= 			" INNER JOIN";
	$sql .= 				" T_Staff_Details tsd_uni2";
	$sql .= 			" ON";
	$sql .= 				" tod_uni2.OrderDetID = tsd_uni2.ReturnDetID";
	$sql .= 			" AND";
	$sql .= 				" tsd_uni2.Del = " . DELETE_OFF;
	$sql .= 			" WHERE";
	$sql .= 				" tod_uni2.Del = " . DELETE_OFF;

	$sql .= 			" AND";
	$sql .= 				" tsd_uni2.Status IN (";
	$sql .= 					"'" . STATUS_APPLI . "',";
	$sql .= 					"'" . STATUS_APPLI_ADMIT . "',";
	$sql .= 					"'" . STATUS_STOCKOUT . "',";
	$sql .= 					"'" . STATUS_ORDER . "',";
	$sql .= 					"'" . STATUS_SHIP . "',";
	$sql .= 					"'" . STATUS_DELIVERY . "'";
	$sql .= 				" )";

	$sql .= 		" )";
	$sql .= 	" ) tsd";
	$sql .= " ON";
	$sql .= 	" tod.OrderDetID = tsd.OrderDetID";

	if ($isLevelAgency == true) {
		$sql .= 	" INNER JOIN";
		$sql .= 		" T_Staff ts_age";
		$sql .= 	" ON";
		$sql .= 		" tsd.StaffID = ts_age.StaffID";
		$sql .= 	" AND";
		$sql .= 		" ts_age.Del = " . DELETE_OFF;
		$sql .= 	" INNER JOIN";
		$sql .= 		" M_Comp mc";
		$sql .= 	" ON";
		$sql .= 		" mc.AgencyID = '" . db_Escape($_SESSION['COMPID']) . "'";
		$sql .= 	" AND";
		$sql .= 		" ts_age.CompID = mc.CompID";
		$sql .= 	" AND";
		$sql .= 		" mc.Del = " . DELETE_OFF;
	}

	$sql .= " INNER JOIN";
	$sql .= 	" (";
	$sql .= 		" SELECT";
	$sql .= 			" *";
	$sql .= 		" FROM";
	$sql .= 			" T_Order tor2";
	$sql .= 		" WHERE";
	$sql .= 				" tor2.StaffCode = (";
	$sql .= 				" SELECT";
	$sql .= 						" TOP 1";
	$sql .= 						" StaffCode";
	$sql .= 					" FROM";
	$sql .= 						" (";
	$sql .= 							" SELECT";
	$sql .= 								" DISTINCT";
	$sql .= 								" TOP " . ($offset + 1);
	$sql .= 								" ts.StaffCode";
	$sql .= 							" FROM";
	$sql .= 								" T_Staff ts";
	$sql .= 							" INNER JOIN";
	$sql .= 								" T_Order tor3";
	$sql .= 							" ON";
	$sql .= 								" ts.StaffCode = tor3.StaffCode";
	$sql .= 							" AND";
	$sql .= 								" ts.CompID = tor3.CompID";
	$sql .= 							" AND";
	$sql .= 								" tor3.Del = " . DELETE_OFF;

	$sql .= 							" INNER JOIN";
	$sql .= 								" M_Comp mc";
	$sql .= 							" ON";
	$sql .= 								" tor3.CompID = mc.CompID";
	$sql .= 							" AND";
	$sql .= 								" mc.Del = " . DELETE_OFF;

	if ($corpCode != '') {
		$sql .= " AND";
		$sql .= 	" mc.CorpCd = '" . db_Escape($corpCode) . "'";
	}

	if ($compId != '') {
		$sql .= 							" AND";
		$sql .= 								" tor3.CompID = " . db_Escape($compId);
	}

	$sql .= 							" INNER JOIN";
	$sql .= 								" T_Order_Details tod2";
	$sql .= 							" ON";
	$sql .= 								" tor3.OrderID = tod2.OrderID";
	$sql .=		 						" AND";
	$sql .= 								" tod2.Del = " . DELETE_OFF;
	$sql .= 							" INNER JOIN";
	$sql .= 								" (";
	$sql .= 									" (";
	$sql .= 										" SELECT";
	$sql .= 											" DISTINCT";
	$sql .= 											" tod_uni3.OrderID";
	$sql .= 										" FROM";
	$sql .= 											" T_Order_Details tod_uni3";
	$sql .= 										" INNER JOIN";
	$sql .= 											" T_Staff_Details tsd_uni3";
	$sql .= 										" ON";
	$sql .= 											" tod_uni3.OrderDetID = tsd_uni3.OrderDetID";
	$sql .= 										" AND";
	$sql .= 											" tsd_uni3.ReturnDetID is NULL";
	$sql .= 										" AND";
	$sql .= 											" tsd_uni3.Del = " . DELETE_OFF;
	$sql .= 										" WHERE";
	$sql .= 											" tod_uni3.Del = " . DELETE_OFF;

	$sql .= 									" AND";
	$sql .= 										" tsd_uni3.Status IN (";
	$sql .= 											"'" . STATUS_APPLI . "',";
	$sql .= 											"'" . STATUS_APPLI_ADMIT . "',";
	$sql .= 											"'" . STATUS_STOCKOUT . "',";
	$sql .= 											"'" . STATUS_ORDER . "',";
	$sql .= 											"'" . STATUS_SHIP . "',";
	$sql .= 											"'" . STATUS_DELIVERY . "'";
	$sql .= 										" )";

	$sql .= 									" )";
	$sql .= 								" UNION ALL";
	$sql .= 									" (";
	$sql .= 										" SELECT";
	$sql .= 											" DISTINCT";
	$sql .= 											" tod_uni4.OrderID";
	$sql .= 										" FROM";
	$sql .= 											" T_Order_Details tod_uni4";
	$sql .= 										" INNER JOIN";
	$sql .= 											" T_Staff_Details tsd_uni4";
	$sql .= 										" ON";
	$sql .= 											" tod_uni4.OrderDetID = tsd_uni4.ReturnDetID";
	$sql .= 										" AND";
	$sql .= 											" tsd_uni4.Del = " . DELETE_OFF;
	$sql .= 										" WHERE";
	$sql .= 											" tod_uni4.Del = " . DELETE_OFF;

	$sql .= 									" AND";
	$sql .= 										" tsd_uni4.Status IN (";
	$sql .= 											"'" . STATUS_APPLI . "',";
	$sql .= 											"'" . STATUS_APPLI_ADMIT . "',";
	$sql .= 											"'" . STATUS_STOCKOUT . "',";
	$sql .= 											"'" . STATUS_ORDER . "',";
	$sql .= 											"'" . STATUS_SHIP . "',";
	$sql .= 											"'" . STATUS_DELIVERY . "'";
	$sql .= 										" )";

	$sql .= 									" )";
	$sql .= 								" ) tsd2";
	$sql .= 							" ON";
	$sql .= 								" tor3.OrderID = tsd2.OrderID";

	$sql .= 							" WHERE";

	if ($compId != '') {
		$sql .= 								" ts.CompID = " . db_Escape($compId);
		$sql .= 							" AND";
	}

	////$sql .= 								" ts.AllReturnFlag = 0";
	////$sql .= 							" AND";
	$sql .= 								" ts.Del = " . DELETE_OFF;

											// スタッフコードの指定があった場合
	$sql .= 								" AND";
	$sql .= 									" ts.StaffID = '" . db_Escape($staffId) . "'";

	$sql .= 					" ORDER BY";
	$sql .= 						" ts.StaffCode ASC";
	$sql .= 									" ) tor4";

	$sql .= 					" ORDER BY";
	$sql .= 						" tor4.StaffCode DESC";
	$sql .= 				" )";

	$sql .= 			" ) tor";
	$sql .= 	" ON";
	$sql .= 		" tod.OrderID = tor.OrderID";

	if ($compId != '') {
		$sql .= 	" AND";
		$sql .= 		" tor.CompID = " . db_Escape($compId);
	}

	$sql .= 	" WHERE";
	$sql .= 		" tod.Del= " . DELETE_OFF;

	$sql .= 	" AND";
	$sql .= 		" tod.ItemID= '" . db_Escape($itemID) . "'";

	$sql .= 	" GROUP BY";
	$sql .= 		" tod.ItemID,";
	$sql .= 		" tod.Status";

	$sql .= 	" ORDER BY";
	$sql .= 		" tod.ItemID ASC,";
	$sql .= 		" tod.Status ASC";
//var_dump($sql);die;
	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}
	
	return $result;
}

?>

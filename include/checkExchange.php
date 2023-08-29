<?php
/*
 * 交換できるユニフォームか判定
 * checkExchange.php
 *
 * create 2007/03/20 H.Osugi
 *
 */

/*
 * 交換できないユニフォームが存在しないかを判定する
 * 引数  ：$dbConnect   => コネクションハンドラ
 *       ：$orderDetIds => 検証したいOrderDetID(array)
 *       ：$returnUrl  => 戻り先URL
 * 戻り値：なし
 */
function checkExchange($dbConnect, $orderDetIds, $returnUrl, $hiddenHtml) {

	// 選択されたorderDetID
	$orderDetId = '';
	if(is_array($orderDetIds)) {
		$orderDetId = implode(', ', $orderDetIds);
	}

//var_dump("orderDetId:" . $orderDetId);die;


	// 交換できないユニフォームが存在しないかを判定する
    if ($orderDetId != '') {
    	$sql  = "";
    	$sql .= " SELECT";
    	$sql .= 	" count(*) as count_staffdet";
    	$sql .= " FROM";
    	$sql .= 	" T_Staff_Details";
    	$sql .= " WHERE";
    	$sql .= 	" OrderDetID IN (" . db_Escape($orderDetId) . ")";
    	$sql .= " AND";
    	$sql .= 	" Status <> " . STATUS_SHIP;			// 出荷済
    	$sql .= " AND";
    	$sql .= 	" Status <> " . STATUS_DELIVERY;		// 納品済
    	$sql .= " AND";
    	$sql .= 	" Del = " . DELETE_OFF;

    	$result = db_Read($dbConnect, $sql);
    }
	
	// 交換できないユニフォームが存在する場合
	if ($orderDetId == '' || !isset($result[0]['count_staffdet']) || $result[0]['count_staffdet'] > 0) {

		$hiddenHtml = castHtmlEntity($hiddenHtml);

		// エラー画面で必要な値のセット
		$hiddens = array();
		$hiddens['errorName'] = 'koukanShinsei';
		$hiddens['menuName']  = 'isMenuExchange';
		$hiddens['returnUrl'] = $returnUrl;
		$hiddens['errorId'][] = '904';

		if (is_array($hiddenHtml)) {
			$hiddens = array_merge($hiddens, $hiddenHtml);
		}

		redirectPost(HOME_URL . 'error.php', $hiddens);

	}
}

?>
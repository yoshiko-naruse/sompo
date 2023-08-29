<?php
/*
 * 返却できるユニフォームか判定
 * checkReturn.php
 *
 * create 2007/03/22 H.Osugi
 *
 */

/*
 * 返却できないユニフォームが存在しないかを判定する
 * 引数  ：$dbConnect   => コネクションハンドラ
 *       ：$orderDetIds => 検証したいOrderDetID(array)
 *       ：$returnUrl  => 戻り先URL
 * 戻り値：なし
 */
function checkReturn($dbConnect, $orderDetIds, $returnUrl) {

	// 選択されたorderDetID
	$orderDetId = '';
	if(is_array($orderDetIds)) {
		foreach ($orderDetIds as $key => $value) {
			if (!(int)$value) {
				// エラー画面で必要な値のセット
				$hiddens = array();
				$hiddens['errorName'] = 'henpinShinsei';
				$hiddens['menuName']  = 'isMenuReturn';
				$hiddens['returnUrl'] = $returnUrl;
				$hiddens['errorId'][] = '903';
		
				redirectPost(HOME_URL . 'error.php', $hiddens);
			}
		}
		$orderDetId = implode(', ', $orderDetIds);
	}

	// 返却できないユニフォームが存在しないかを判定する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" count(*) as count_staffdet";
	$sql .= " FROM";
	$sql .= 	" T_Staff_Details";
	$sql .= " WHERE";
	$sql .= 	" OrderDetID IN (" . db_Escape($orderDetId) . ")";
	$sql .= " AND";
	$sql .= 	" Status <> " . STATUS_SHIP;				// 出荷済
	$sql .= " AND";
	$sql .= 	" Status <> " . STATUS_DELIVERY;			// 納品済
	$sql .= " AND";
	$sql .= 	" Status <> " . STATUS_RETURN_NOT_APPLY;	// 返却未申請
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;

	$result = db_Read($dbConnect, $sql);

	// 返却できないユニフォームが存在する場合
	if (!isset($result[0]['count_staffdet']) || $result[0]['count_staffdet'] > 0) {

		// エラー画面で必要な値のセット
		$hiddens = array();
		$hiddens['errorName'] = 'henpinShinsei';
		$hiddens['menuName']  = 'isMenuReturn';
		$hiddens['returnUrl'] = $returnUrl;
		$hiddens['errorId'][] = '903';

		redirectPost(HOME_URL . 'error.php', $hiddens);

	}
}

?>
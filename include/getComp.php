<?php
/*
 * 店舗情報取得モジュール
 * getComp.php
 *
 * create 2007/03/13 H.Osugi
 *
 */

/*
 * 店舗情報を取得する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$compId    => 店舗ID
 *       ：$isEntity => 0：エンティティしない / エンティティする
 * 戻り値：店舗情報（array）
 *
 * create 2007/03/13 H.Osugi
 *
 */
function getComp($dbConnect, $compId, $isEntity = 0) {

	// 初期化
	$result = array();

	//compCdが空なら処理を終了
	if ($compId == '' && $compId !== 0) {
		return $result;
	}

	// 該当の店舗情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" CompID,";
	$sql .= 	" CompCd,";
	$sql .= 	" CompName,";
	$sql .= 	" Zip,";
	$sql .= 	" Adrr,";
	$sql .= 	" Tel";
	$sql .= " FROM";
	$sql .= 	" M_Comp";
	$sql .= " WHERE";
	$sql .= 	" CompID = '" . db_Escape($compId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;
	
	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	// エンティティ処理を行わない場合
	if ($isEntity == 0) {
		return $result[0];
	}
	
	$result[0]['CompName'] = htmlspecialchars($result[0]['CompName'], HTMLENTITY_QUOTE_STYLE, HTMLENTITY_ENCODE);
	$result[0]['Zip']      = htmlspecialchars($result[0]['Zip'], HTMLENTITY_QUOTE_STYLE, HTMLENTITY_ENCODE);
	$result[0]['Adrr']     = htmlspecialchars($result[0]['Adrr'], HTMLENTITY_QUOTE_STYLE, HTMLENTITY_ENCODE);
	$result[0]['Tel']      = htmlspecialchars($result[0]['Tel'], HTMLENTITY_QUOTE_STYLE, HTMLENTITY_ENCODE);

	return $result[0];

}

?>
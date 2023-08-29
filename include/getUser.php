<?php
/*
 * ユーザー情報取得モジュール
 * getUser.php
 *
 * create 2007/03/13 H.Osugi
 *
 */

/*
 * ユーザー名を取得する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$nameCd    => ユーザID
 *       ：$isEntity => 0：エンティティしない / エンティティする
 * 戻り値：ユーザー名
 *
 * create 2007/03/13 H.Osugi
 *
 */
function getUserName($dbConnect, $nameCd, $isEntity = 0) {

	// 初期化
	$result = array();

	// nameCdが空なら処理を終了
	if ($nameCd == '' && $nameCd !== 0) {
		return false;
	}

	// 該当のユーザー名を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" Name";
	$sql .= " FROM";
	$sql .= 	" M_User";
	$sql .= " WHERE";
	$sql .= 	" NameCd = '" . db_Escape($nameCd) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;
	
	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0 || !isset($result[0]['Name'])) {
	 	return false;
	}

	// エンティティ処理を行わない場合
	if ($isEntity == 0) {
		return $result[0]['Name'];
	}
	
	$result[0]['Name'] = htmlspecialchars($result[0]['Name'], HTMLENTITY_QUOTE_STYLE, HTMLENTITY_ENCODE);

	return $result[0]['Name'];
	
}

?>
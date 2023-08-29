<?php
/*
 * 申請番号生成モジュール
 * createRequestNo.php
 *
 * create 2007/03/15 H.Osugi
 *
 */

/*
 * 申請番号を生成する
 * 引数  ：$dbConnect   => コネクションハンドラ
 *       ：$compCd      => 店舗コード
 *       ：$requestCode => 申請/返却コードフラグ 1：申請 / 2：返却 / 3：交換
 * 戻り値：$requestNo   => 申請番号 / 生成失敗時は false
 *
 * create 2007/03/15 H.Osugi
 *
 */
function createRequestNo($dbConnect, $compId, $requestCode) {

	// 初期化
	$isError      = false;
	$requestNo    = '';

	//compIdが空の場合
	if ($compId == '' && $compId !== 0) {
		return false;
	}

	// トランザクション開始
	db_Transaction_Begin($dbConnect);

	// 該当の店舗情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" CompCode,";
	$sql .= 	" Cycle,";
	$sql .= 	" GETDATE() as now";
	$sql .= " FROM";
	$sql .= 	" M_Comp";
	$sql .= " WHERE";
	$sql .= 	" CompId = '" . db_Escape($compId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;
	
	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0 || !isset($result[0]['CompCode']) || !isset($result[0]['Cycle']) || !isset($result[0]['now'])) {
		// ロールバック
		db_Transaction_Rollback($dbConnect);
		return false;
	}

	// 申請番号の1文字目を生成
	switch ($requestCode) {
		case '1':		// 申請
			$requestNo .= 'A';
			break;		// 返却
		case '2':
			$requestNo .= 'R';
			break;
		case '3':		// 交換（交換の場合は頭文字（A or R）を付けずに生成）
			break;
		default:
			$isError = true;
			break;
	}

	if ($isError == true) {
		// ロールバック
		db_Transaction_Rollback($dbConnect);
		return false;
	}

	// 店舗毎のコードを付加
	$requestNo .= $result[0]['CompCode'];

	// 該当の店舗情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" CompCode,";
	$sql .= 	" Cycle,";
	$sql .= 	" UpdDay,";
	$sql .= 	" GETDATE() as now";
	$sql .= " FROM";
	$sql .= 	" M_Comp";
	$sql .= " WHERE";
	$sql .= 	" CompId = '" . db_Escape($compId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;
	
	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0 || !isset($result[0]['CompCode'])
		 || !isset($result[0]['Cycle']) || !isset($result[0]['UpdDay'])
		 || !isset($result[0]['now']))
	{
		// ロールバック
		db_Transaction_Rollback($dbConnect);
		return false;
	}
	
	// DBの現在日時を基にコードを付加
	$date = date('ymd', strtotime($result[0]['now']));
	$requestNo .= $date;

	// 更新日付が現在日付よりも古い場合採番の基準値を0に戻す
	if (date('Ymd', strtotime($result[0]['UpdDay'])) < date('Ymd', strtotime($result[0]['now']))) {

		// DBの採番の基準値を変更する
		$sql  = "";
		$sql .= " UPDATE";
		$sql .= 	" M_Comp";
		$sql .= " SET";
		$sql .= 	" Cycle   = 0,";
		$sql .= 	" UpdDay  = GETDATE(),";
		$sql .= 	" UpdUser = '" . db_Escape($_SESSION['NAMECODE']) . "'";
		$sql .= " WHERE";
		$sql .= 	" CompId = '" . db_Escape($compId) . "'";
		$sql .= " AND";
		$sql .= 	" Del = " . DELETE_OFF;
		
		$isSuccess = db_Execute($dbConnect, $sql);
	
		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			// ロールバック
			db_Transaction_Rollback($dbConnect);
			return false;
		}
		
		$result[0]['Cycle'] = 0;
		
	}

	$cycleNo = $result[0]['Cycle'] + 1;

	// 採番に問題がある場合
	if ($cycleNo > 9999 || $cycleNo <= 0) {
		// ロールバック
		db_Transaction_Rollback($dbConnect);
		return false;
	}

	$requestNo .= sprintf('%04d', $cycleNo);

	// DBの採番の基準値を変更する
	$sql  = "";
	$sql .= " UPDATE";
	$sql .= 	" M_Comp";
	$sql .= " SET";
	$sql .= 	" Cycle   = Cycle + 1,";
	$sql .= 	" UpdDay  = GETDATE(),";
	$sql .= 	" UpdUser = '" . db_Escape($_SESSION['NAMECODE']) . "'";
	$sql .= " WHERE";
	$sql .= 	" CompId = '" . db_Escape($compId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;
	
	$isSuccess = db_Execute($dbConnect, $sql);

	// 実行結果が失敗の場合
	if ($isSuccess == false) {
		// ロールバック
		db_Transaction_Rollback($dbConnect);
		return false;
	}

	// コミット
	db_Transaction_Commit($dbConnect);
	return $requestNo;
	
}

?>
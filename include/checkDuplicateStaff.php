<?php
/*
 * 申請発注重複判定
 * checkDuplicateStaff.php
 *
 * create 2016/11/30 H.Osugi
 *
 */

/*
 * 申請発注重複判定モジュール（同じスタッフコードですでに発注していないか判定）
 * 引数  ：$dbConnect  => コネクションハンドラ
 *       ：$staffID  => スタッフID
 *       ：$returnUrl  => 戻り先URL
 *       ：$hiddenHtml => 遷移時に送信したいPOST値(array)
 * 戻り値：なし
 */
function checkDuplicateStaffID($dbConnect, $staffID, $returnUrl, $hiddenHtml = '', $appliReason = '') {

	// 指定スタッフIDが貸与中か確認する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" COUNT(*) as count_order";
	$sql .= " FROM";
	$sql .= 	" T_Staff_Details tsd";
	$sql .= " INNER JOIN";
	$sql .= 	" T_Staff tsf";
	$sql .= " ON tsd.StaffID = tsf.StaffID AND tsf.Del = " . DELETE_OFF;
	$sql .= " INNER JOIN";
	$sql .= 	" T_Order_Details tod";
	$sql .= " ON tsd.OrderDetID = tod.OrderDetID AND tod.Del = " . DELETE_OFF . " AND tod.Status <> " . STATUS_CANCEL . " AND tod.Status <> " . STATUS_APPLI_DENY;
	$sql .= " INNER JOIN";
	$sql .= 	" T_Order tor";
	$sql .= " ON tod.OrderID = tor.OrderID AND tor.Del = " . DELETE_OFF . " AND tor.Status <> " . STATUS_CANCEL . " AND tod.Status <> " . STATUS_APPLI_DENY;
	if ($appliReason == APPLI_REASON_ORDER_GRADEUP) {	// グレードアップタイ
		$sql .= " AND";
		$sql .= 	" tor.AppliReason = '" . APPLI_REASON_ORDER_GRADEUP . "'";
	} else {											// 基本パターン または 新入社員
		$sql .= " AND";
		$sql .= 	" (tor.AppliReason = '" .APPLI_REASON_ORDER_BASE . "' OR tor.AppliReason = '" . APPLI_REASON_ORDER_FRESHMAN . "')" ;
	}
	$sql .= " WHERE";
	$sql .= 	" tsd.StaffID = '" . db_Escape($staffID) . "'";
	$sql .= " AND";
	$sql .= 	" tsd.ReturnDetID IS NULL";
	$sql .= " AND";
	$sql .= 	" tsd.Del = " . DELETE_OFF;
	$sql .= " AND";
	$sql .= 	" tsd.Status <> " . STATUS_CANCEL;
	$result = db_Read($dbConnect, $sql);

	// 該当の情報がすでに存在する場合
	if (isset($result[0]['count_order']) && $result[0]['count_order'] > 0) {

		// エラー画面で必要な値のセット
		$hiddens = array();
		$hiddens['errorName'] = 'checkStaffID';
		$hiddens['menuName']  = 'isMenuOrder';
		$hiddens['returnUrl'] = $returnUrl;
		$hiddens['errorId'][] = '901';

		if (is_array($hiddenHtml)) {
			$hiddens = array_merge($hiddens, $hiddenHtml);
		}

		redirectPost(HOME_URL . 'error.php', $hiddens);

	}
}

/*
 * スタッフコード重複登録判定モジュール
 * 引数  ：$dbConnect  => コネクションハンドラ
 *       ：$corpCd     => 会社コード
 *       ：$compId     => 部署ID
 *       ：$staffCode  => スタッフコード
 *       ：$staffKbn   => 新規更新区分(1:新規 2:更新)
 *       ：$requestNo  => 申請番号
 * 戻り値：True: 重複なし、False: 重複あり
 */
function checkDuplicateCorpStaff($dbConnect, $staffCode, $staffKbn, $requestNo = '') {

	// 同一会社内で異なる部署に既に登録されていないかを確認する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" tsf.StaffID";
	$sql .= " FROM";
	$sql .= 	" T_Staff tsf";

	$sql .= " INNER JOIN";
	$sql .= 	" M_Comp mcp";
	$sql .= " ON";
	$sql .= 	" tsf.CompID = mcp.CompID";
	$sql .= " AND";
	$sql .= 	" mcp.Del = ".DELETE_OFF;

	$sql .= " INNER JOIN";
	$sql .= 	" T_Order tor";
	$sql .= " ON";
	$sql .= 	" tsf.StaffID = tor.StaffID";
	$sql .= " AND";
	$sql .= 	" tor.Del = ".DELETE_OFF;
	$sql .= " AND";
	$sql .= 	" tor.Status <> ".STATUS_CANCEL;

	$sql .= " INNER JOIN";
	$sql .= 	" T_Order_Details tod";
	$sql .= " ON";
	$sql .= 	" tor.OrderID = tod.OrderID";
	$sql .= " AND";
	$sql .= 	" tod.Del = ".DELETE_OFF;
	$sql .= " AND";
	$sql .= 	" tod.Status <> ".STATUS_CANCEL;

	$sql .= " WHERE";
	$sql .= 	" tsf.StaffCode = '" . db_Escape($staffCode) . "'";
	// 新規時は１件でもあればＮＧ
	// 更新時は他部署にある場合はＮＧ
	if ($staffKbn == '2') {
		$sql .= " AND";
		$sql .= 	" tsf.CompID <> '" . db_Escape($compId) . "'";
	}
	// 申請変更画面からのチェックの場合、同一申請番号以外をチェック対象とする
	if ($requestNo != '') {
		$sql .= " AND";
		$sql .= 	" tor.AppliNo <> '" . db_Escape($requestNo) . "'";
	}
//	$sql .= " AND";
//	$sql .= 	" mcp.CorpCd = '" . db_Escape($corpCd) . "'";
	$sql .= " AND";
	$sql .= 	" tsf.Del = " . DELETE_OFF;

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		return true;		// 重複なし
	} else {
		// 重複があっても全返却済の場合は更新貸与を許可する（再雇用）
		if ($staffKbn == '2') {
			// 指定スタッフコードで現在も保持アイテムがある場合はＮＧ
			if (checkStaffKeepItem($dbConnect, $staffCode)) {
				return true;		// 保持アイテムなし
			} else {
				return false;		// 保持アイテムあり
			}
		} else {
			return false;		// 重複あり
		}
	}

}

/*
 * スタッフコード保持アイテムチェック
 * 引数  ：$dbConnect  => コネクションハンドラ
 *       ：$staffCode  => スタッフコード
 * 戻り値：True: 保持アイテムなし、False: 保持アイテムあり
 */
function checkStaffKeepItem($dbConnect, $staffCode) {

	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" tsd.StaffDetID";
	$sql .= " FROM";
	$sql .= 	" T_Staff tsf";

	$sql .= " INNER JOIN";
	$sql .= 	" T_Staff_Details tsd";
	$sql .= " ON";
	$sql .= 	" tsf.StaffID = tsd.StaffID";
	$sql .= " AND";
	$sql .= 	" tsd.Status < ".STATUS_RETURN;		// 返却済未満
	$sql .= " AND";
	$sql .= 	" tsd.Del = ".DELETE_OFF;

	$sql .= " WHERE";
    $sql .=     " tsf.StaffCode = '" . db_Escape($staffCode) . "'";
	$sql .= " AND";
	$sql .= 	" tsf.Del = ".DELETE_OFF;

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		return true;		// 保持アイテムなし
	} else {
		return false;		// 保持アイテムあり
	}

}

?>
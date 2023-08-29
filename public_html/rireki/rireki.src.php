<?php
/*
 * 申請履歴画面
 * rireki.src.php
 *
 * create 2007/03/26 H.Osugi
 *
 *
 */

// 各種モジュールの取得
include_once('../../include/define.php');			// 定数定義
require_once('../../include/dbConnect.php');		// DB接続モジュール
include_once('../../include/msSqlControl.php');		// DB操作モジュール
require_once('../../include/checkLogin.php');		// ログイン判定モジュール
require_once('../../include/castHtmlEntity.php');	// HTMLエンティティモジュール
require_once('../../include/redirectPost.php');		// リダイレクトポストモジュール
require_once('../../include/setPaging.php');		// ページング情報セッティングモジュール

// 初期設定
$isMenuHistory = true;	// 申請履歴のメニューをアクティブに

// 変数の初期化 ここから ******************************************************
$searchCompCd       = '';					// 店舗コード
$searchCompName     = '';					// 店舗名
$searchCompId       = '';					// 店舗ID
$searchAppliNo      = '';					// 申請番号
$searchAppliDayFrom = '';					// 申請日
$searchAppliDaryTo  = '';					// 申請日
$searchShipDayFrom  = '';					// 出荷日
$searchShipDaryTo   = '';					// 出荷日
$searchStaffCode    = '';					// スタッフコード
$searchBarCode      = '';					// バーコード
$searchStatus       = array();				// ステータス

$isSelectedAdmin    = false;				// 管理者権限で検索を行ったかどうか

// 状態の表示文字列
$DISPLAY_STATUS_APPLI       = $DISPLAY_STATUS[1];		// 承認待
$DISPLAY_STATUS_APPLI_ADMIT = $DISPLAY_STATUS[3];		// 申請済
$DISPLAY_STATUS_ORDER       = $DISPLAY_STATUS[14];		// 受注済
$DISPLAY_STATUS_SHIP        = $DISPLAY_STATUS[15];		// 出荷済
$DISPLAY_STATUS_DELIVERY    = $DISPLAY_STATUS[16];		// 納品済
$DISPLAY_STATUS_STOCKOUT    = $DISPLAY_STATUS[13];		// 在庫切
$DISPLAY_STATUS_NOT_RETURN  = $DISPLAY_STATUS[20];		// 未返却
$DISPLAY_STATUS_RETURN      = $DISPLAY_STATUS[22];		// 返却済
$DISPLAY_STATUS_LOSS        = $DISPLAY_STATUS[34];		// 紛失

$compCd    = castHtmlEntity($_SESSION['COMPCD']);	// 店舗番号
$compName  = castHtmlEntity($_SESSION['COMPNAME']);	// 店舗名

if ($isLevelAgency == true) {
	$isLevelAdmin = true;
}
// 変数の初期化 ここまで ******************************************************

// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST); 

$nowPage = 1;
if ($post['nowPage'] != '') {
	$nowPage = trim($post['nowPage']);
}

$isSelectedAdmin = trim($post['isSelectedAdmin']);

// 検索時は1ページ目に戻す
if ($post['searchFlg'] == 1) {
	$nowPage = 1;
	$isSelectedAdmin = true;
}

if ($isLevelAdmin == false || $isSelectedAdmin == true) {

	$isSelectedAdmin = true;

	// 管理者権限の場合は検索条件を指定しているか判定
//	if ($isLevelAdmin == true) {
//
//		// 条件が指定されているか判定
//		$hasCondition = checkCondition($post);
//	
//		if ($hasCondition == false) {
//
//			$hiddens['errorName'] = 'rireki';
//			$hiddens['menuName']  = 'isMenuHistory';
//			$hiddens['returnUrl'] = 'rireki/rireki.php';
//			$hiddens['errorId'][] = '902';
//			$errorUrl             = HOME_URL . 'error.php';
//
//			redirectPost($errorUrl, $hiddens);
//
//		}
//	}

	// 表示する注文履歴一覧を取得
	//$orders = castHtmlEntity(getOrder($dbConnect, $_POST, $nowPage, $DISPLAY_STATUS, $allCount));
	$orders = getOrder($dbConnect, $_POST, $nowPage, $DISPLAY_STATUS, $allCount, $isLevelItc, $isLevelHonbu);

	// ページングのセッティング
	$paging = setPaging($nowPage, PAGE_PER_DISPLAY_HISTORY, $allCount);

	// 注文履歴が０件の場合
	if (count($orders) <= 0) {

		// 条件が指定されているか判定
		$hasCondition = checkCondition($post);

		$hiddens['errorName'] = 'rireki';
		$hiddens['menuName']  = 'isMenuHistory';

		if ($hasCondition == true) {
			$hiddens['returnUrl'] = 'rireki/rireki.php';
		}
		else {
			$hiddens['returnUrl'] = 'top.php';
		}

		$hiddens['errorId'][] = '901';
		$errorUrl             = HOME_URL . 'error.php';

		redirectPost($errorUrl, $hiddens);

	}

}
$orders = castHtmlEntity($orders);
// 表示する値の成型 ここから +++++++++++++++++++++++++++++++++++++++++++++++++++

// 店舗コード
$searchCompCd       = trim($post['searchCompCd']);

// 店舗名
$searchCompName     = trim($post['searchCompName']);

// 店舗ID
$searchCompId       = trim($post['searchCompId']);

// 申請番号
$searchAppliNo      = trim($post['searchAppliNo']);

// 申請日
$searchAppliDayFrom = trim($post['searchAppliDayFrom']);
$searchAppliDayTo   = trim($post['searchAppliDayTo']);

// 出荷日
$searchShipDayFrom = trim($post['searchShipDayFrom']);
$searchShipDayTo   = trim($post['searchShipDayTo']);

// スタッフコード
$searchStaffCode    = trim($post['searchStaffCode']);

// 単品番号
$searchBarCode      = trim($post['searchBarCode']);

// 状態
for ($i=1; $i<=9; $i++) {

	${'isSelectedStatus' . $i} = false;
	if (isset($post['searchStatus']) && is_array($post['searchStatus']) && in_array($i, $post['searchStatus'])) {
		${'isSelectedStatus' . $i} = true;
	}

}
// 表示する値の成型 ここまで +++++++++++++++++++++++++++++++++++++++++++++++++++

/*
 * 注文履歴一覧情報を取得する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$post           => POST値
 *       ：$nowPage        => 現在のページ数
 *       ：$DISPLAY_STATUS => 状態
 *       ：$allCount       => 全件数
 * 戻り値：$result         => 注文履歴一覧情報
 *
 * create 2007/03/26 H.Osugi
 *
 */
function getOrder($dbConnect, $post, $nowPage, $DISPLAY_STATUS ,&$allCount, $isLevelItc, $isLevelHonbu) {

	global $isLevelAdmin;	// 管理者権限の有無
	global $isLevelAgency;	// 一次代理店

	// 初期化
	$compId       = '';
	$appliNo      = '';
	$appliDayFrom = '';
	$appliDayTo   = '';
	$shipDayFrom  = '';
	$shipDayTo    = '';
	$staffCode    = '';
	$barCode      = '';
	$status       = '';
	$limit        = '';
	$offset       = '';
	$corpCode     = '';
    $honbuCd      = '';
    $shibuCd      = '';

	// 取得したい件数
	$limit = PAGE_PER_DISPLAY_HISTORY;		// 1ページあたりの表示件数;

	// 取得したいデータの開始位置
	$offset = ($nowPage - 1) * PAGE_PER_DISPLAY_HISTORY;

	// 店舗ID
	$compId = $_SESSION['COMPID'];
	if ($isLevelAdmin == true) {
		$compId = $post['searchCompId'];

		// 店舗IDに不正な値が入っていた場合
		if ($compId != '' && !ctype_digit($compId)) {
			$result = array();
			return $result;
		}

	}

	if ($isLevelAdmin == true) {

        if (!$isLevelItc) {

            if ($isLevelHonbu) {
                // 本部権限
                if (isset($_SESSION['HONBUCD'])) {
                    $honbuCd = $_SESSION['HONBUCD'];
                } else {
                    $honbuCd = '';
                }

            } else {
                // 支部権限
                if (isset($_SESSION['SHIBUCD']) && isset($_SESSION['HONBUCD'])) {
                    $honbuCd = $_SESSION['HONBUCD'];
                    $shibuCd = $_SESSION['SHIBUCD'];
                } else {
                    $honbuCd = '';
                    $shibuCd = '';
                }
            }
        }
    }

	// 申請番号
	if ($isLevelAdmin == true) {
		$appliNo = $post['searchAppliNo'];
	}

	// 申請日
	if ($isLevelAdmin == true) {

		$appliDayFrom = $post['searchAppliDayFrom'];
		$appliDayTo   = $post['searchAppliDayTo'];

		// YY/MM/DD以外の文字列であれば処理を終了する
		if ($appliDayFrom != '' && !ereg('^[0-9]{4}/[0-9]{2}/[0-9]{2}$', $appliDayFrom)) {
			return $result;
		}
	
		// YY/MM/DD以外の文字列であれば処理を終了する
		if ($appliDayTo != '' && !ereg('^[0-9]{4}/[0-9]{2}/[0-9]{2}$', $appliDayTo)) {
			return $result;
		}

	}

	// 出荷日
	if ($isLevelAdmin == true) {

		$shipDayFrom = $post['searchShipDayFrom'];
		$shipDayTo   = $post['searchShipDayTo'];

		// YY/MM/DD以外の文字列であれば処理を終了する
		if ($shipDayFrom != '' && !ereg('^[0-9]{4}/[0-9]{2}/[0-9]{2}$', $shipDayFrom)) {
			return $result;
		}
	
		// YY/MM/DD以外の文字列であれば処理を終了する
		if ($shipDayTo != '' && !ereg('^[0-9]{4}/[0-9]{2}/[0-9]{2}$', $shipDayTo)) {
			return $result;
		}

	}

	// スタッフコード
	$staffCode = $post['searchStaffCode'];

	// 単品番号
	$barCode = $post['searchBarCode'];

	// 状態
	$countStatus = 0;
	if (isset($post['searchStatus']) && is_array($post['searchStatus'])) {
		$countStatus = count($post['searchStatus']);
	}
	for ($i=0; $i<$countStatus; $i++) {
		switch ($post['searchStatus'][$i]) {
			case '1':
				$status .= 	" " . STATUS_APPLI;					// 申請済（承認待ち）
				break;
			case '2':
				$status .= 	" " . STATUS_APPLI_ADMIT;			// 申請済（承認済）
				break;
			case '3':
				$status .= 	" " . STATUS_ORDER;					// 受注済
				break;
			case '4':
				$status .= 	" " . STATUS_SHIP;					// 出荷済
				break;
			case '5':
				$status .= 	" " . STATUS_DELIVERY;				// 納品済
				break;
			case '6':
				$status .= 	" " . STATUS_STOCKOUT;				// 在庫切れ
				break;
			case '7':
				$status .= 	" " . STATUS_NOT_RETURN;			// 未返却（承認待ち）
				$status .= 	" ," . STATUS_NOT_RETURN_ADMIT;		// 未返却（承認済）
				$status .= 	" ," . STATUS_NOT_RETURN_ORDER;		// 未返却（受注済）
				break;
			case '8':
				$status .= 	" " . STATUS_RETURN;				// 返却済
				break;
			case '9':
				$status .= 	" " . STATUS_LOSS;					// 紛失（承認待ち）
				$status .= 	" ," . STATUS_LOSS_ADMIT;			// 紛失（承認済）
				$status .= 	" ," . STATUS_LOSS_ORDER;			// 紛失（受注済）
				break;
			default:
				break;
		}

		if ($i != $countStatus -1) {
			$status .= " ,";
		}

	}


	// 注文履歴の件数を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" COUNT(DISTINCT tor.OrderID) as count_order";
	$sql .= " FROM";
	$sql .= 	" T_Order tor";

	if ($barCode != '' || $status != '') {
		$sql .= " INNER JOIN";
		$sql .= 	" T_Order_Details tod";
		$sql .= " ON";
		$sql .= 	" tor.OrderID = tod.OrderID";
		$sql .= " AND";
		$sql .= 	" tod.Del = " . DELETE_OFF;
	}

	$sql .= " INNER JOIN";
	$sql .= 	" M_Comp mc";
	$sql .= " ON";
	$sql .= 	" tor.CompID = mc.CompID";
	$sql .= " AND";
	$sql .= 	" mc.Del = " . DELETE_OFF;
	$sql .= " AND";
    $sql .=     " mc.ShopFlag <> 0";        // ShopFlagが0以外の店舗を表示する

	$sql .= " WHERE";

	if ($compId != '') {
		$sql .= 	" tor.CompID = " . db_Escape($compId);
		$sql .= " AND";
	}

	$sql .= 	" tor.Del = " . DELETE_OFF;

	// 申請番号を前方一致
	if ($appliNo != '') {
		$sql .= " AND";
		$sql .= 	" tor.AppliNo LIKE ('" . db_Like_Escape($appliNo) . "%')";
	}

	if ($appliDayFrom != '') {
		$sql .= " AND";
		$sql .= 	" CONVERT(char, tor.AppliDay, 111) >= '" . db_Escape($appliDayFrom) . "'";
	}

	if ($appliDayTo != '') {
		$sql .= " AND";
		$sql .= 	" CONVERT(char, tor.AppliDay, 111) <= '" . db_Escape($appliDayTo) . "'";
	}

	if ($shipDayFrom != '') {
		$sql .= " AND";
		$sql .= 	" CONVERT(char, tor.ShipDay, 111) >= '" . db_Escape($shipDayFrom) . "'";
	}

	if ($shipDayTo != '') {
		$sql .= " AND";
		$sql .= 	" CONVERT(char, tor.ShipDay, 111) <= '" . db_Escape($shipDayTo) . "'";
	}

	if ($staffCode != '') {
		$sql .= " AND";
		$sql .= 	" tor.StaffCode = '" . db_Escape($staffCode) . "'";
	}

	if ($barCode != '') {
		$sql .= " AND";
		//$sql .= 	" tod.BarCd = '" . db_Escape($barCode) . "'";
		$sql .= 	" tod.BarCd LIKE '" . db_Like_Escape($barCode) . "%'";
	}

	if ($honbuCd != '') {
		$sql .= " AND";
		$sql .= 	" mc.HonbuCd = '" . db_Escape($honbuCd) . "'";
	}

	if ($shibuCd != '') {
		$sql .= " AND";
		$sql .= 	" mc.ShibuCd = '" . db_Escape($shibuCd) . "'";
	}

	if ($status != '') {
		$sql .= " AND";
		$sql .= 	" tod.Status IN (";
		$sql .= 			$status;
		$sql .= 	" )";
	}

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	$allCount = 0;
	if (!isset($result[0]['count_order']) || $result[0]['count_order'] <= 0) {
		$result = array();
	 	return $result;
	}

	// 全件数
	$allCount = $result[0]['count_order'];

	$top = $offset + $limit;
	if ($top > $allCount) {
		$limit = $limit - ($top - $allCount);
		$top   = $allCount;
	}

	// 注文履歴の一覧を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" tor.OrderID,";
	$sql .= 	" tor.AppliDay,";
	$sql .= 	" tor.AppliNo,";
	$sql .= 	" tor.AppliCompCd,";
	$sql .= 	" tor.AppliCompName,";
	$sql .= 	" tor.StaffCode,";
    $sql .=     " tor.PersonName,";
	$sql .= 	" tor.AppliMode,";
	$sql .= 	" tor.AppliSeason,";
	$sql .= 	" tor.AppliReason,";
	$sql .= 	" tor.Status,";
	$sql .= 	" tor.ShipDay,";
	$sql .= 	" tor.ReturnDay";
	$sql .= " FROM";
	$sql .= 	" (";
	$sql .= 		" SELECT";
	$sql .= 			" TOP " . $limit;
    $sql .=             " tor2.OrderID,";
    $sql .=             " tor2.AppliDay,";
    $sql .=             " tor2.AppliNo,";
    $sql .=             " mc2.CompCd as AppliCompCd,";
    $sql .=             " mc2.CompName as AppliCompName,";
    $sql .=             " tor2.StaffCode,";
    $sql .=             " tor2.PersonName,";
    $sql .=             " tor2.AppliMode,";
    $sql .=             " tor2.AppliSeason,";
    $sql .=             " tor2.AppliReason,";
    $sql .=             " tor2.Status,";
    $sql .=             " tor2.ShipDay,";
    $sql .=             " tor2.ReturnDay";
	$sql .= 		" FROM";
	$sql .= 			" T_Order tor2";
    $sql .=             " INNER JOIN";
    $sql .=             " M_Staff ms2";
    $sql .=             " ON";
    $sql .=               " tor2.StaffID = ms2.StaffSeqID";
    $sql .=             " AND";
    $sql .=               " ms2.Del = " . DELETE_OFF;
	$sql .=             " INNER JOIN";
    $sql .=             " M_Comp mc2";
    $sql .=             " ON";
    $sql .=               " ms2.CompID = mc2.CompID";
    $sql .=             " AND";
    $sql .=               " mc2.Del = " . DELETE_OFF;
	$sql .= 		" WHERE";
	$sql .= 			" tor2.OrderID IN (";
	$sql .= 						" SELECT";
	$sql .= 							" OrderID";
	$sql .= 						" FROM";
	$sql .= 							" (";
	$sql .= 								" SELECT";
	$sql .= 									" DISTINCT";
	$sql .= 									" TOP " . ($top);
	$sql .= 									" tor3.OrderID,";
	$sql .= 									" tor3.AppliDay";
	$sql .= 								" FROM";
	$sql .= 									" T_Order tor3";

	if ($barCode != '' || $status != '') {
		$sql .= 							" INNER JOIN";
		$sql .= 								" T_Order_Details tod";
		$sql .= 							" ON";
		$sql .= 								" tor3.OrderID = tod.OrderID";
		$sql .= 							" AND";
		$sql .= 								" tod.Del = " . DELETE_OFF;
	}

	$sql .= 								" INNER JOIN";
	$sql .= 									" M_Comp mc";
	$sql .= 								" ON";
	$sql .= 									" tor3.CompID = mc.CompID";
	$sql .= 								" AND";
	$sql .= 									" mc.Del = " . DELETE_OFF;
	$sql .= 								" AND";
    $sql .=     								" mc.ShopFlag <> 0";        // ShopFlagが0以外の店舗を表示する

	$sql .= 								" WHERE";

	if ($compId != '') {
		$sql .= 									" tor3.CompID = " . db_Escape($compId);
		$sql .= 								" AND";
	}

	$sql .= 									" tor3.Del = " . DELETE_OFF;

	// 申請番号を前方一致
	if ($appliNo != '') {
		$sql .= 							" AND";
		$sql .= 								" tor3.AppliNo LIKE ('" . db_Like_Escape($appliNo) . "%')";
	}

	if ($appliDayFrom != '') {
		$sql .= 							" AND";
		$sql .= 								" CONVERT(char, tor3.AppliDay, 111) >= '" . db_Escape($appliDayFrom) . "'";
	}

	if ($appliDayTo != '') {
		$sql .= 							" AND";
		$sql .= 								" CONVERT(char, tor3.AppliDay, 111) <= '" . db_Escape($appliDayTo) . "'";
	}

	if ($shipDayFrom != '') {
		$sql .= 							" AND";
		$sql .= 								" CONVERT(char, tor3.ShipDay, 111) >= '" . db_Escape($shipDayFrom) . "'";
	}

	if ($shipDayTo != '') {
		$sql .= 							" AND";
		$sql .= 								" CONVERT(char, tor3.ShipDay, 111) <= '" . db_Escape($shipDayTo) . "'";
	}

	if ($staffCode != '') {
		$sql .= 							" AND";
		$sql .= 								" tor3.StaffCode = '" . db_Escape($staffCode) . "'";
	}
	
	if ($barCode != '') {
		$sql .= 							" AND";
		//$sql .= 								" tod.BarCd = '" . db_Escape($barCode) . "'";
		$sql .= 								" tod.BarCd LIKE '" . db_Like_Escape($barCode) . "%'";
	}

	if ($honbuCd != '') {
		$sql .= 							" AND";
		$sql .= 								" mc.HonbuCd = '" . db_Escape($honbuCd) . "'";
	}

	if ($shibuCd != '') {
		$sql .= 							" AND";
		$sql .= 								" mc.ShibuCd = '" . db_Escape($shibuCd) . "'";
	}

	if ($status != '') {
		$sql .= 							" AND";
		$sql .= 								" tod.Status IN(";
		$sql .= 										$status;
		$sql .= 								" )";
	}

	$sql .= 								" ORDER BY";
	$sql .= 									" tor3.AppliDay DESC,";
	$sql .= 									" tor3.OrderID DESC";
	$sql .= 							" ) tor4";
	$sql .= 						" )";
	$sql .= 				" ORDER BY";
	$sql .= 					" tor2.AppliDay ASC,";
	$sql .= 					" tor2.OrderID ASC";

	$sql .= 	" ) tor";

	$sql .= 	" ORDER BY";
	$sql .= 		" tor.AppliDay DESC,";
	$sql .= 		" tor.OrderID DESC";
//var_dump($sql);
	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	// 取得した値をHTMLエンティティ
	$resultCount  = count($result);
    $tempStoreAry = array();
    $tempIdxAry   = array();
	for ($i=0; $i<$resultCount; $i++) {
		$result[$i]['requestDay'] = strtotime($result[$i]['AppliDay']);
		$result[$i]['requestNo']  = $result[$i]['AppliNo'];
		$result[$i]['orderId']    = $result[$i]['OrderID'];
		$result[$i]['CompCd']     = castHtmlEntity($result[$i]['AppliCompCd']);
		$result[$i]['CompName']   = castHtmlEntity($result[$i]['AppliCompName']);
		$result[$i]['staffCode']  = castHtmlEntity($result[$i]['StaffCode']);
        $result[$i]['personName']  = castHtmlEntity($result[$i]['PersonName']);

		// 申請番号の遷移先決定
		$result[$i]['isAppli'] = false;
		if (ereg('^A.*$', $result[$i]['AppliNo'])) {
			$result[$i]['isAppli'] = true;
		}

		// 出荷日
		$result[$i]['isEmptyShipDay'] = true;
		if (isset($result[$i]['ShipDay']) && $result[$i]['ShipDay'] != '') {
			$result[$i]['ShipDay']   = strtotime($result[$i]['ShipDay']);
			$result[$i]['isEmptyShipDay'] = false;
		}

		// 返却日
		$result[$i]['isEmptyReturnDay'] = true;
		if (isset($result[$i]['ReturnDay']) && $result[$i]['ReturnDay'] != '') {
			$result[$i]['ReturnDay']  = strtotime($result[$i]['ReturnDay']);
			$result[$i]['isEmptyReturnDay'] = false;
		}

		// 区分
		$result[$i]['divisionOrder']    = false;
		$result[$i]['divisionExchange'] = false;
		$result[$i]['divisionReturn']   = false;
		switch ($result[$i]['AppliMode']) {
			case APPLI_MODE_ORDER:						// 発注
				$result[$i]['divisionOrder']    = true;
				break;
			case APPLI_MODE_EXCHANGE:					// 交換
				$result[$i]['divisionExchange'] = true;
				break;
			case APPLI_MODE_RETURN:						// 返却
				$result[$i]['divisionReturn']   = true;
				break;
			default:
				break;
		}

		// 状態
		$result[$i]['statusName']  = castHtmlEntity($DISPLAY_STATUS[$result[$i]['Status']]);

		// 状態の文字列の色
		$result[$i]['statusIsBlue']  = false;
		$result[$i]['statusIsRed']   = false;
		$result[$i]['statusIsTeal']  = false;
		$result[$i]['statusIsGreen'] = false;
		$result[$i]['statusIsGray']  = false;
		$result[$i]['statusIsPink']  = false;
		$result[$i]['statusIsBlack'] = false;
		switch ($result[$i]['Status']) {
			case STATUS_APPLI:							// 申請済（承認待ち）
			case STATUS_STOCKOUT:						// 在庫切れ
				$result[$i]['statusIsGray']  = true;
				break;
			case STATUS_APPLI_DENY:						// 申請済（否認）
			case STATUS_NOT_RETURN_DENY:				// 未返却 （否認）
			case STATUS_LOSS_DENY:						// 紛失（否認）
				$result[$i]['statusIsPink']  = true;
				break;
			case STATUS_APPLI_ADMIT:					// 申請済（承認済）
				$result[$i]['statusIsGreen'] = true;
				break;
			case STATUS_ORDER:							// 受注済
				$result[$i]['statusIsBlue']  = true;
				break;
			case STATUS_NOT_RETURN:						// 未返却（承認待ち）
			case STATUS_NOT_RETURN_ADMIT:				// 未返却（承認済）
			case STATUS_NOT_RETURN_ORDER:				// 未返却（受注済）
				$result[$i]['statusIsRed']   = true;
				break;
			case STATUS_LOSS:							// 紛失（承認待ち）
			case STATUS_LOSS_ADMIT:						// 紛失（承認済）
			case STATUS_LOSS_ORDER:						// 紛失（受注済）
				$result[$i]['statusIsTeal']  = true;
				break;
			default:
				$result[$i]['statusIsBlack'] = true;
				break;
		}

		// 訂正
		$result[$i]['reasonIsPersonal']  = false;
		$result[$i]['reviseIsCancel']   = false;
		$result[$i]['reviseIsEmpty']    = true;
		switch ($result[$i]['AppliMode']) {
			case APPLI_MODE_ORDER:									// 発注
				switch ($result[$i]['Status']) {
					case STATUS_APPLI:								// 承認待
                    case STATUS_APPLI_ADMIT:                        // 申請済（承認済）
						$result[$i]['reviseIsEmpty']    = false;
                        $result[$i]['reasonIsPersonal']  = true;
                        $result[$i]['reviseIsCancel']   = false;
						break;

                    case STATUS_ORDER:                              // 受注済
                        $result[$i]['reviseIsEmpty']    = true;
                        $result[$i]['reasonIsPersonal']  = false;
                        $result[$i]['reviseIsCancel']   = false;
                        break;

                    case STATUS_SHIP;                               // 出荷済
                    case STATUS_DELIVERY;                           // 納品済
                        $result[$i]['reviseIsEmpty']    = true;
                        $result[$i]['reasonIsPersonal']  = false;
                        $result[$i]['reviseIsCancel']   = false;
					default:
						break;
				}
				break;
			case APPLI_MODE_EXCHANGE:								// 交換 
				switch ($result[$i]['Status']) {
					case STATUS_APPLI:								// 承認待
                    case STATUS_APPLI_ADMIT:                        // 申請済（承認済）
                        $result[$i]['reviseIsEmpty']    = false;
                        $result[$i]['reasonIsPersonal'] = false;
                        $result[$i]['reviseIsCancel']   = true;
                        break;

                    case STATUS_ORDER:                              // 受注済
                        $result[$i]['reviseIsEmpty']    = true;
                        $result[$i]['reasonIsPersonal']  = false;
                        $result[$i]['reviseIsCancel']   = false;
                        break;

					case STATUS_NOT_RETURN:							// 承認待
					case STATUS_NOT_RETURN_ADMIT:					// 未返却（承認済）
                        $result[$i]['reviseIsEmpty']    = false;
                        $result[$i]['reasonIsPersonal'] = false;
						$result[$i]['reviseIsCancel']   = true;
						break;

                    case STATUS_RETURN:                             // 返却済
                        $result[$i]['reviseIsEmpty']    = true;
                        $result[$i]['reasonIsPersonal']  = false;
                        $result[$i]['reviseIsCancel']   = false;
                        break;

					case STATUS_LOSS:								// 承認待
					case STATUS_LOSS_ADMIT:							// 紛失（承認済）
                        $result[$i]['reviseIsEmpty']    = false;
                        $result[$i]['reasonIsPersonal'] = false;
						$result[$i]['reviseIsCancel']   = true;
						break;

                    case STATUS_LOSS_ORDER:                         // 紛失
                        $result[$i]['reviseIsEmpty']    = true;
                        $result[$i]['reasonIsPersonal']  = false;
                        $result[$i]['reviseIsCancel']   = false;
                        break;

					default:
						break;
				}
				break;
			case APPLI_MODE_RETURN:									// 返却
				if ($result[$i]['AppliReason'] != APPLI_REASON_EXCHANGE_SIZE_RETURN) {	// サイズ交換キャンセル返却以外
					switch ($result[$i]['Status']) {
						case STATUS_NOT_RETURN:							// 未返却（承認待ち）
						case STATUS_NOT_RETURN_ADMIT:					// 未返却（承認済）
						case STATUS_NOT_RETURN_ORDER:					// 未返却（受注済）

							// Modified by Y.Furukawa at 17/07/29 退職返却申請後に発注申請があるかどうか（=ある場合はｷｬﾝｾﾙ不可（リンク無））
							if (getOrderCnt($dbConnect, $result[$i]['AppliDay'], $result[$i]['staffCode']) > 0) {
								$result[$i]['reviseIsCancel']   = false;
								$result[$i]['reviseIsEmpty']    = true;
							} else {
								$result[$i]['reviseIsCancel']   = true;
								$result[$i]['reviseIsEmpty']    = false;
							}
							//$result[$i]['reviseIsCancel']   = true;
							//$result[$i]['reviseIsEmpty']    = false;
							break;
						default:
							break;
					}
				}
				break;
			default:
				break;
		} 

        // 交換のORDERの場合は「返却」「発注」のどちらかが「受注済」に達した時点で両方をキャンセル不可にする
        if ($result[$i]['divisionExchange']) {
            $innerAppliNo = substr($result[$i]['AppliNo'], 1);
            $overWrite = false;
            $copy      = false;
            if ($result[$i]['isAppli']) {       // 発注申請の場合
                switch ($result[$i]['Status']) {
                    case STATUS_STOCKOUT:           // 在庫切れ
                    case STATUS_ORDER:              // 受注済
                    case STATUS_SHIP:               // 出荷済
                    case STATUS_DELIVERY:           // 納品済
                        $copy = true;
                        break;
                    case STATUS_APPLI_ADMIT:        // 申請済（承認済）
                        $overWrite = true;
                        break;
                    default:
                        break;
                }
            } else {    // 返却申請の場合
                switch ($result[$i]['Status']) {
                    case STATUS_NOT_RETURN_ADMIT:   // 未返却（承認済）
                        $overWrite = true;
                        break;
                    case STATUS_NOT_RETURN_ORDER:   // 未返却（受注済）
                    case STATUS_RETURN:             // 返却済
                        $copy = true;
                        break;
                    default:
                        break;
                }
            }
 
            if ($overWrite) {
                if (isset($tempIdxAry[$innerAppliNo]) && $tempIdxAry[$innerAppliNo] != '') {
                    $result[$i]['reviseIsEmpty']    = $result[$tempIdxAry[$innerAppliNo]]['reviseIsEmpty'];
                    $result[$i]['reasonIsPersonal'] = $result[$tempIdxAry[$innerAppliNo]]['reasonIsPersonal'];
                    $result[$i]['reviseIsCancel']   = $result[$tempIdxAry[$innerAppliNo]]['reviseIsCancel'];
                } else {
                    $tempIdxAry[$innerAppliNo] = (string)$i;
                }
            }
            if ($copy) {
                if (isset($tempIdxAry[$innerAppliNo]) && $tempIdxAry[$innerAppliNo] != '') {
                    $result[$tempIdxAry[$innerAppliNo]]['reviseIsEmpty']    = $result[$i]['reviseIsEmpty'];
                    $result[$tempIdxAry[$innerAppliNo]]['reasonIsPersonal'] = $result[$i]['reasonIsPersonal'];
                    $result[$tempIdxAry[$innerAppliNo]]['reviseIsCancel']   = $result[$i]['reviseIsCancel'];
                } else {
                    $tempIdxAry[$innerAppliNo] = (string)$i;
                }
            } 
 
        }        

	}

	return  $result;

}

/*
 * 返却申請後に発注申請がされているかどうか
 * 引数  ：$dbConnect      => コネクションハンドラ
 * 　　  ：$requestDay     => 発注ID
 * 　　  ：$staffCode      => 発注ID
 * 戻り値：返却済件数
 *
 * create 2017/07/29 Y.Furukawa
 *
 */
function getOrderCnt($dbConnect, $requestDay, $staffCode) {

	// 対象スタッフの退職返却申請後の発注申請の件数を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" COUNT(DISTINCT OrderID) as count_order";
	$sql .= " FROM";
	$sql .= 	" T_Order";
	$sql .= " WHERE";
	$sql .= 	" AppliDay >= '" . db_Escape($requestDay) . "'";
	$sql .= " AND";
	$sql .= 	" AppliMode = '" . db_Escape(APPLI_MODE_ORDER) . "'";
	$sql .= " AND";
	$sql .= 	" StaffCode = '" . db_Escape($staffCode) . "'";
	$sql .= " AND";
	$sql .= "   (Status = " . STATUS_APPLI . " OR Status = " . STATUS_APPLI_ADMIT . " OR Status = " . STATUS_STOCKOUT . " OR Status = " . STATUS_ORDER . " OR Status = " . STATUS_SHIP . " OR Status = " . STATUS_DELIVERY . ")";	// 承認待、申請済、在庫切、受注済、出荷済、納品済
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
	 	return 0;
	}

	return (int)$result[0]['count_order'];
}

/*
 * 検索条件を指定しているか判定
 * 引数  ：$post           => POST値
 * 戻り値：true：条件を指定している / false：条件を指定していない
 *
 * create 2007/04/06 H.Osugi
 *
 */
function checkCondition($post) {

	global $isLevelAdmin;	// 管理者権限の有無

	// 店舗IDの指定があった場合
	if ($isLevelAdmin == true) {
		if (isset($post['searchCompId']) && $post['searchCompId'] != '') {
			return true;
		}
	}

	// 申請番号の指定があった場合
	if ($isLevelAdmin == true) {
		if (isset($post['searchAppliNo']) && $post['searchAppliNo'] != '') {
			return true;
		}
	}

	// 申請日の指定があった場合
	if ($isLevelAdmin == true) {
		if (isset($post['searchAppliDayFrom']) && $post['searchAppliDayFrom'] != '') {
			return true;
		}
		if (isset($post['searchAppliDayTo']) && $post['searchAppliDayTo'] != '') {
			return true;
		}
	}

	// 出荷日の指定があった場合
	if ($isLevelAdmin == true) {
		if (isset($post['searchShipDayFrom']) && $post['searchShipDayFrom'] != '') {
			return true;
		}
		if (isset($post['searchShipDayTo']) && $post['searchShipDayTo'] != '') {
			return true;
		}
	}

	// スタッフコードの指定があった場合
	if (isset($post['searchStaffCode']) && $post['searchStaffCode'] != '') {
		return true;
	}

	// 単品番号の指定があった場合
	if (isset($post['searchBarCode']) && $post['searchBarCode'] != '') {
		return true;
	}

	// 状態の指定があった場合
	if (isset($post['searchStatus']) && count($post['searchStatus']) > 0) {
		return true;
	}

	return false;

}

?>
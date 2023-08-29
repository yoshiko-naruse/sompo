<?php
/*
 * 着用状況画面
 * chakuyou.src.php
 *
 * create 2007/03/29 H.Osugi
 *
 *
 */

// 各種モジュールの取得
include_once('../../include/define.php');			// 定数定義
require_once('../../include/dbConnect.php');		// DB接続モジュール
require_once('../../include/msSqlControl.php');		// DB操作モジュール
require_once('../../include/checkLogin.php');		// ログイン判定モジュール
require_once('../../include/castHtmlEntity.php');	// HTMLエンティティモジュール
require_once('../../include/redirectPost.php');		// リダイレクトポストモジュール
require_once('../../include/commonFunc.php');       // 共通関数モジュール
require_once('../../include/setPaging.php');		// ページング情報セッティングモジュール

// 初期設定
$isMenuCondition = true;	// 着用状況のメニューをアクティブに

// 変数の初期化 ここから ******************************************************
$searchCompCd    = '';					// 店舗コード
$searchCompName  = '';					// 店舗名
$searchCompId    = '';					// 店舗ID
$searchStaffCode = '';					// スタッフコード
$searchBarCode   = '';					// バーコード
$searchStatus    = array();				// ステータス

$isSelectedAdmin = false;				// 管理者権限で検索を行ったかどうか

// 状態の表示文字列
$DISPLAY_STATUS_APPLI       = $DISPLAY_STATUS[1];		// 承認待
$DISPLAY_STATUS_APPLI_ADMIT = $DISPLAY_STATUS[3];		// 承認済
$DISPLAY_STATUS_ORDER       = $DISPLAY_STATUS[14];		// 受注済
$DISPLAY_STATUS_SHIP        = $DISPLAY_STATUS[15];		// 出荷済
$DISPLAY_STATUS_DELIVERY    = $DISPLAY_STATUS[16];		// 納品済
$DISPLAY_STATUS_STOCKOUT    = $DISPLAY_STATUS[13];		// 在庫切
$DISPLAY_STATUS_NOT_RETURN  = $DISPLAY_STATUS[20];		// 未返却

$compCd    = castHtmlEntity($_SESSION['COMPCD']);	// 店舗番号
$compName  = castHtmlEntity($_SESSION['COMPNAME']);	// 店舗名

if ($isLevelAgency == true) {
	$isLevelAdmin = true;
}
// 変数の初期化 ここまで ******************************************************

// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST); 

$nowPage = 1;
if ($post['nowPage'] != '' && (int)$post['nowPage']) {
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
//			$hiddens['errorName'] = 'chakuyou';
//			$hiddens['menuName']  = 'isMenuCondition';
//			$hiddens['returnUrl'] = 'chakuyou/chakuyou.php';
//			$hiddens['errorId'][] = '902';
//			$errorUrl             = HOME_URL . 'error.php';
//
//			redirectPost($errorUrl, $hiddens);
//
//		}
//	}

	// 表示する着用状況一覧を取得
	$items = getOrderDetail($dbConnect, $post, $nowPage, $DISPLAY_STATUS, $allCount, $isLevelItc, $isLevelHonbu);

	// ページングのセッティング
	$pagingStaff = setPaging($nowPage, 1, $allCount);

	// スタッフが０件の場合
	if ($allCount <= 0) {

		// 条件が指定されているか判定
		$hasCondition = checkCondition($post);

		$hiddens['errorName'] = 'chakuyou';
		$hiddens['menuName']  = 'isMenuCondition';

		if ($hasCondition == true) {
			$hiddens['returnUrl'] = 'chakuyou/chakuyou.php';
		}
		else {
			$hiddens['returnUrl'] = 'top.php';
		}

		$hiddens['errorId'][] = '901';
		$errorUrl             = HOME_URL . 'error.php';

		redirectPost($errorUrl, $hiddens);

    } else {
        // ヘッダー部分に表示する着用者情報を取得する
        if (is_array($items) && count($items) != 0) {
            $headerData = getHeaderData($dbConnect, $items[0]['StaffID']);
        }   
	}
}

// 表示する値の成型 ここから +++++++++++++++++++++++++++++++++++++++++++++++++++

// 店舗コード
$searchCompCd    = trim($post['searchCompCd']);

// 店舗名
$searchCompName  = trim($post['searchCompName']);

// 店舗ID
$searchCompId    = trim($post['searchCompId']);

// スタッフコード
$searchStaffCode = trim($post['searchStaffCode']);

// 単品番号
$searchBarCode   = trim($post['searchBarCode']);

// 状態
for ($i=1; $i<=7; $i++) {

	${'isSelectedStatus' . $i} = false;
	if (isset($post['searchStatus']) && is_array($post['searchStatus']) && in_array($i, $post['searchStatus'])) {
		${'isSelectedStatus' . $i} = true;
	}

}
// 表示する値の成型 ここまで +++++++++++++++++++++++++++++++++++++++++++++++++++

/*
 * 着用状況一覧情報を取得する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$post           => POST値
 *       ：$nowPage        => 現在のページ数
 *       ：$DISPLAY_STATUS => 状態
 *       ：$allCount       => 全件数
 * 戻り値：$result         => 注文履歴一覧情報
 *
 * create 2007/03/29 H.Osugi
 *
 */
function getOrderDetail($dbConnect, $post, $nowPage, $DISPLAY_STATUS ,&$allCount, $isLevelItc, $isLevelHonbu) {

	global $isLevelAdmin;	// 管理者権限の有無
	global $isLevelAgency;	// 一次代理店

	// 初期化
	$compId    = '';
	$staffCode = '';
	$barCode   = '';
	$status    = '';
	$offset    = '';

	// 取得したいデータの開始位置
	$offset = ($nowPage - 1);

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
				$status .= 	" " . STATUS_APPLI;				// 承認待
				break;
			case '2':
				$status .= 	" " . STATUS_APPLI_ADMIT;		// 申請済（承認済）
				break;
			case '3':
				$status .= 	" " . STATUS_ORDER;				// 受注済
				break;
			case '4':
				$status .= 	" " . STATUS_SHIP;				// 出荷済
				break;
			case '5':
				$status .= 	" " . STATUS_DELIVERY;			// 納品済
				break;
			case '6':
				$status .= 	" " . STATUS_STOCKOUT;			// 在庫切れ
				break;
			case '7':
				$status .= 	" " . STATUS_NOT_RETURN_ADMIT;	// 未返却（承認済）
				$status .= ",";
				$status .= 	" " . STATUS_NOT_RETURN_ORDER;	// 未返却（受注済）
				break;
			default:
				break;
		}

		if ($i != $countStatus -1) {
			$status .= ",";
		}

	}

	// 状態が何も選択されていない場合
	if ($countStatus <= 0) {

		$status  = 	" " . STATUS_APPLI;				// 申請済（承認待ち）
		$status .= ",";
		$status .= 	" " . STATUS_APPLI_ADMIT;		// 申請済（承認済）
		$status .= ",";
		$status .= 	" " . STATUS_STOCKOUT;			// 在庫切れ
		$status .= ",";
		$status .= 	" " . STATUS_ORDER;				// 受注済
		$status .= ",";
		$status .= 	" " . STATUS_SHIP;				// 出荷済
		$status .= ",";
		$status .= 	" " . STATUS_DELIVERY;			// 納品済
		$status .= ",";
		$status .= 	" " . STATUS_NOT_RETURN;		// 未返却（承認待ち）
		$status .= ",";
		$status .= 	" " . STATUS_NOT_RETURN_ADMIT;	// 未返却（承認済）
		$status .= ",";
		$status .= 	" " . STATUS_NOT_RETURN_ORDER;	// 未返却（受注済）
		$status .= ",";
		$status .= 	" " . STATUS_RETURN_NOT_APPLY;	// 返却未申請
		$status .= ",";
		$status .= 	" " . STATUS_LOSS;				// 紛失（承認待ち）

	}

	// 着用状況の件数を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" COUNT(DISTINCT ts.StaffCode) as count_staff";
	$sql .= " FROM";
	$sql .= 	" T_Staff ts";
	$sql .= " INNER JOIN";
	$sql .= 	" T_Order tor";
	$sql .= " ON";
	$sql .= 	" ts.StaffCode = tor.StaffCode";
	$sql .= " AND";
	$sql .= 	" ts.CompID = tor.CompID";
	$sql .= " AND";
	$sql .= 	" tor.Del = " . DELETE_OFF;

	if ($compId != '') {
		$sql .= " AND";
		$sql .= 	" tor.CompID = " . db_Escape($compId);
	}

	$sql .= " INNER JOIN";
	$sql .= 	" M_Comp mc";
	$sql .= " ON";
	$sql .= 	" tor.CompID = mc.CompID";
	$sql .= " AND";
	$sql .= 	" mc.Del = " . DELETE_OFF;
	$sql .= " AND";
    $sql .=     " mc.ShopFlag <> 0";        // ShopFlagが0以外の店舗を表示する

	if ($corpCode != '') {
		$sql .= " AND";
		$sql .= 	" mc.CorpCd = '" . db_Escape($corpCode) . "'";
	}

	if ($honbuCd != '') {
		$sql .= " AND";
		$sql .= 	" mc.HonbuCd = '" . db_Escape($honbuCd) . "'";
	}

	if ($shibuCd != '') {
		$sql .= " AND";
		$sql .= 	" mc.ShibuCd = '" . db_Escape($shibuCd) . "'";
	}

	$sql .= " INNER JOIN";
	$sql .= 	" (";
	$sql .= 		" (";
	$sql .= 			" SELECT";
	$sql .= 				" DISTINCT";
	$sql .= 				" tod_uni1.OrderID";
	$sql .= 			" FROM";
	$sql .= 				" T_Order_Details tod_uni1";
	$sql .= 			" INNER JOIN";
	$sql .= 				" T_Staff_Details tsd_uni1";
	$sql .= 			" ON";
	$sql .= 				" tod_uni1.OrderDetID = tsd_uni1.OrderDetID";
	$sql .= 			" AND";
	$sql .= 				" tsd_uni1.ReturnDetID is NULL";
	$sql .= 			" AND";
	$sql .= 				" tod_uni1.Del = " . DELETE_OFF;
	$sql .= 			" AND";
	$sql .= 				" tsd_uni1.Del = " . DELETE_OFF;

	// 単品番号の指定があった場合
	if ($barCode != '') {
		$sql .= 		" AND";
		//$sql .= 			" tod_uni1.BarCd = '" . db_Escape($barCode) . "'";
		$sql .= 			" tod_uni1.BarCd LIKE '" . db_Like_Escape($barCode) . "%'";
	}

	// 状態の指定があった場合
	if ($status != '') {
		$sql .= 		" AND";
		$sql .= 			" tsd_uni1.Status IN (";
		$sql .= 				$status;
		$sql .= 			" )";
	}

	$sql .= 		" )";
	$sql .= 	" UNION ALL";
	$sql .= 		" (";
	$sql .= 			" SELECT";
	$sql .= 				" DISTINCT";
	$sql .= 				" tod_uni2.OrderID";
	$sql .= 			" FROM";
	$sql .= 				" T_Order_Details tod_uni2";
	$sql .= 			" INNER JOIN";
	$sql .= 				" T_Staff_Details tsd_uni2";
	$sql .= 			" ON";
	$sql .= 				" tod_uni2.OrderDetID = tsd_uni2.ReturnDetID";
	$sql .= 			" AND";
	$sql .= 				" tod_uni2.Del = " . DELETE_OFF;
	$sql .= 			" AND";
	$sql .= 				" tsd_uni2.Del = " . DELETE_OFF;

	// 単品番号の指定があった場合
	if ($barCode != '') {
		$sql .= 		" AND";
		//$sql .= 			" tod_uni2.BarCd = '" . db_Escape($barCode) . "'";
		$sql .= 			" tod_uni2.BarCd LIKE '" . db_Like_Escape($barCode) . "%'";
	}

	// 状態の指定があった場合
	if ($status != '') {
		$sql .= 		" AND";
		$sql .= 			" tsd_uni2.Status IN (";
		$sql .= 				$status;
		$sql .= 			" )";
	}

	$sql .= 		" )";
	$sql .= 	" ) tsd";
	$sql .= " ON";
	$sql .= 	" tor.OrderID = tsd.OrderID";
	$sql .= " WHERE";
	////$sql .= 	" ts.AllReturnFlag = 0";
	////$sql .= " AND";
	$sql .= 	" ts.Del = " . DELETE_OFF;

	// スタッフコードの指定があった場合
	if ($staffCode != '') {
		$sql .= " AND";
		$sql .= 	" ts.StaffCode = '" . db_Escape($staffCode) . "'";
	}

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	$allCount = 0;
	if (!isset($result[0]['count_staff']) || $result[0]['count_staff'] <= 0) {
		$result = array();
	 	return $result;
	}

	// 全件数
	$allCount = $result[0]['count_staff'];

	// 着用状況の一覧を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" tor.StaffCode,";
	$sql .= 	" tod.AppliDay,";
	$sql .= 	" tod.ItemID,";
	$sql .= 	" tod.ItemName,";
	$sql .= 	" tod.Size,";
	$sql .= 	" tod.BarCd,";
	$sql .= 	" tsd.StaffID,";
	$sql .= 	" tsd.Status";
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

	// 状態の指定があった場合
	if ($status != '') {
		$sql .= 		" AND";
		$sql .= 			" tsd_uni1.Status IN (";
		$sql .= 				$status;
		$sql .= 			" )";
	}

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

	// 状態の指定があった場合
	if ($status != '') {
		$sql .= 		" AND";
		$sql .= 			" tsd_uni2.Status IN (";
		$sql .= 				$status;
		$sql .= 			" )";
	}

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
	$sql .= 							" AND";

    if ($_SESSION['ADMINLVL'] == 0) {   // ティファニー
        if ($_SESSION['SHOPFLAG'] == EXCEPTIONALSHOP_EXCEPTIONAL) {
            $sql .=     " mc.ShopFlag = 2";         // ShopFlagが2の店舗のみを表示する
        } else {
            $sql .=     " mc.ShopFlag = 1";         // ShopFlagが1の店舗のみを表示する
        }
    } else {                            // ＩＴＣ管理者
        $sql .=     " mc.ShopFlag <> 0";        // ShopFlagが0以外の店舗を表示する
    }

	if ($corpCode != '') {
		$sql .= " AND";
		$sql .= 	" mc.CorpCd = '" . db_Escape($corpCode) . "'";
	}

	if ($honbuCd != '') {
		$sql .= " AND";
		$sql .= 	" mc.HonbuCd = '" . db_Escape($honbuCd) . "'";
	}

	if ($shibuCd != '') {
		$sql .= " AND";
		$sql .= 	" mc.ShibuCd = '" . db_Escape($shibuCd) . "'";
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

	// 状態の指定があった場合
	if ($status != '') {
		$sql .= 									" AND";
		$sql .= 										" tsd_uni3.Status IN (";
		$sql .= 											$status;
		$sql .= 										" )";
	}

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

	// 状態の指定があった場合
	if ($status != '') {
		$sql .= 									" AND";
		$sql .= 										" tsd_uni4.Status IN (";
		$sql .= 											$status;
		$sql .= 										" )";
	}

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
	if ($staffCode != '') {
		$sql .= 					" AND";
		$sql .= 						" ts.StaffCode = '" . db_Escape($staffCode) . "'";
	}

	// 単品番号の指定があった場合
	if ($barCode != '') {
		$sql .= 					" AND";
		//$sql .= 						" tod2.BarCd = '" . db_Escape($barCode) . "'";
		$sql .= 						" tod2.BarCd LIKE '" . db_Like_Escape($barCode) . "%'";
	}

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

	// 単品番号の指定があった場合
	if ($barCode != '') {
		$sql .= 	" AND";
		//$sql .= 		" tod.BarCd = '" . db_Escape($barCode) . "'";
		$sql .= 		" tod.BarCd LIKE '" . db_Like_Escape($barCode) . "%'";
	}

	$sql .= 	" ORDER BY";
	$sql .= 		" tod.ItemID ASC,";
	$sql .= 		" tsd.Status ASC";

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	// 取得した値をHTMLエンティティ
	$resultCount = count($result);
	for ($i=0; $i<$resultCount; $i++) {

		$result[$i]['requestDay'] = strtotime($result[$i]['AppliDay']);
		$result[$i]['StaffCode']  = castHtmlEntity($result[$i]['StaffCode']);
        $result[$i]['StaffID']  = castHtmlEntity($result[$i]['StaffID']);
		$result[$i]['ItemName']   = castHtmlEntity($result[$i]['ItemName']);
		$result[$i]['Size']       = castHtmlEntity($result[$i]['Size']);
		$result[$i]['BarCd']      = castHtmlEntity($result[$i]['BarCd']);

		$result[$i]['isEmptyBarCd'] = true;
		if ($result[$i]['BarCd'] != '') {
			$result[$i]['isEmptyBarCd'] = false;
		}

		// 状態
		$result[$i]['status']  = castHtmlEntity($DISPLAY_STATUS[$result[$i]['Status']]);

		// 状態の文字列の色
		$result[$i]['statusIsBlue']  = false;
		$result[$i]['statusIsRed']   = false;
		$result[$i]['statusIsTeal']  = false;
		$result[$i]['statusIsGreen'] = false;
		$result[$i]['statusIsGray']  = false;
		$result[$i]['statusIsPink']  = false;
		$result[$i]['statusIsBlack'] = false;
		switch ($result[$i]['Status']) {
			case STATUS_APPLI:					// 申請済（承認待ち）
			case STATUS_STOCKOUT:				// 在庫切れ
				$result[$i]['statusIsGray']  = true;
				break;
			case STATUS_APPLI_DENY:				// 申請済（否認）
			case STATUS_NOT_RETURN_DENY:		// 未返却 （否認）
			case STATUS_LOSS_DENY:				// 紛失（否認）
				$result[$i]['statusIsPink']  = true;
				break;
			case STATUS_APPLI_ADMIT:			// 申請済（承認済）
				$result[$i]['statusIsGreen'] = true;
				break;
			case STATUS_ORDER:					// 受注済
				$result[$i]['statusIsBlue']  = true;
				break;
			case STATUS_NOT_RETURN:				// 未返却（承認待ち）
			case STATUS_NOT_RETURN_ADMIT:		// 未返却（承認済）
			case STATUS_NOT_RETURN_ORDER:		// 未返却（受注済）
				$result[$i]['statusIsRed']   = true;
				break;
			case STATUS_LOSS:					// 紛失（承認待ち）
			case STATUS_LOSS_ADMIT:				// 紛失（承認済）
			case STATUS_LOSS_ORDER:				// 紛失（受注済）
				$result[$i]['statusIsTeal']  = true;
				break;
			default:
				$result[$i]['statusIsBlack'] = true;
				break;
		}

		// 背景の文字列の色
		$result[$i]['bgcolorIsNone']   = false;
		$result[$i]['bgcolorIsRed']    = false;
		$result[$i]['bgcolorIsYellow'] = false;
		switch ($result[$i]['Status']) {
			case STATUS_NOT_RETURN:				// 未返却（承認待ち）
			case STATUS_NOT_RETURN_ADMIT:		// 未返却（承認済）
			case STATUS_NOT_RETURN_ORDER:		// 未返却（受注済）
				$result[$i]['bgcolorIsRed']  = true;
				break;
			case STATUS_APPLI:					// 申請済（承認待ち）
			case STATUS_APPLI_ADMIT:			// 申請済（承認済）
			case STATUS_STOCKOUT:				// 在庫切れ
				$result[$i]['bgcolorIsYellow']  = true;
				break;
			default:
				$result[$i]['bgcolorIsNone'] = true;
				break;
		}

	}

	return  $result;

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
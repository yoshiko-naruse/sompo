<?php
/*
 * 承認処理画面
 * syounin.src.php
 *
 * create 2007/04/23 H.Osugi
 * update 2007/04/27 H.Osugi 返却を検索対象から外す
 *
 */

// 各種モジュールの取得
include_once('../../include/define.php');			// 定数定義
require_once('../../include/dbConnect.php');		// DB接続モジュール
require_once('../../include/msSqlControl.php');		// DB操作モジュール
require_once('../../include/checkLogin.php');		// ログイン判定モジュール
require_once('../../include/castHtmlEntity.php');	// HTMLエンティティモジュール
require_once('../../include/redirectPost.php');		// リダイレクトポストモジュール
require_once('../../include/setPaging.php');		// ページング情報セッティングモジュール

// 承認権限のないユーザが閲覧しようとするとTOPに強制遷移
if ($isLevelAdmin  == false && $isLevelAcceptation  == false) {

	$hiddens = array();
	redirectPost(HOME_URL . 'top.php', $hiddens);

}

// 初期設定
$isMenuAcceptation = true;	// 承認処理のメニューをアクティブに

// 変数の初期化 ここから ******************************************************
$searchCompCd       = '';					// 店舗コード
$searchCompName     = '';					// 店舗名
$searchCompId       = '';					// 店舗ID
$searchStaffCode    = '';					// スタッフコード
$searchPersonCode   = '';					// スタッフ氏名
$searchBarCode      = '';					// バーコード
$searchAppliDayFrom = '';					// 申請日
$searchAppliDayTo   = '';					// 申請日
$searchStatus       = array();				// ステータス
$orders             = array();

$isSearched          = false;				// 検索を行ったかどうか

// 状態の表示文字列
$DISPLAY_STATUS_APPLI            = $DISPLAY_STATUS_ACCEPTATION[1];		// 承認待
$DISPLAY_STATUS_APPLI_ADMIT      = $DISPLAY_STATUS_ACCEPTATION[3];		// 承認済
$DISPLAY_STATUS_APPLI_DENY       = $DISPLAY_STATUS_ACCEPTATION[2];		// 否認
$DISPLAY_STATUS_NOT_RETURN       = $DISPLAY_STATUS_ACCEPTATION[18];		// 返却承認待
$DISPLAY_STATUS_NOT_RETURN_ADMIT = $DISPLAY_STATUS_ACCEPTATION[20];		// 返却承認済
$DISPLAY_STATUS_NOT_RETURN_DENY  = $DISPLAY_STATUS_ACCEPTATION[19];		// 返却否認

$compCd    = '';	// 店舗番号
$compName  = '';	// 店舗名
// 変数の初期化 ここまで ******************************************************

// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST); 

if ((isset($post['searchFlg']) && $post['searchFlg'] == 1)
    || (isset($post['errorFlg']) && $post['errorFlg'] == 1)
	|| (isset($post['confFlg']) && $post['confFlg'] == 1)) {

	// 条件が指定されているか判定
	$hasCondition = checkCondition($post);
		
	if ($hasCondition == false) {
	
		$hiddens['errorName'] = 'syounin';
		$hiddens['menuName']  = 'isMenuAcceptation';
		$hiddens['returnUrl'] = 'syounin/syounin.php';
		$hiddens['errorId'][] = '902';
		$errorUrl             = HOME_URL . 'error.php';
	
		redirectPost($errorUrl, $hiddens);
	
	}

	// 表示する承認情報一覧を取得
	$orders = getOrder($dbConnect, $post, $DISPLAY_STATUS_ACCEPTATION, $isLevelItc, $isLevelHonbu);

	// 承認情報が０件の場合
	if (count($orders) <= 0) {
	
	//	$hiddens['errorName'] = 'syounin';
	//	$hiddens['menuName']  = 'isMenuAcceptation';
	//	$hiddens['returnUrl'] = 'syounin/syounin.php';
	//	$hiddens['errorId'][] = '901';
	//	$errorUrl             = HOME_URL . 'error.php';
	
	//	redirectPost($errorUrl, $hiddens);

		$isSearched = false;

	}

	$isSearched = true;

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

// スタッフ氏名
$searchPersonName = trim($post['searchPersonName']);

// 申請日
$searchAppliDayFrom = trim($post['searchAppliDayFrom']);
$searchAppliDayTo   = trim($post['searchAppliDayTo']);

// 状態
for ($i=1; $i<=6; $i++) {

	${'isSelectedStatus' . $i} = false;
	if (isset($post['searchStatus']) && is_array($post['searchStatus']) && in_array($i, $post['searchStatus'])) {
		${'isSelectedStatus' . $i} = true;
	}

}

// 表示する値の成型 ここまで +++++++++++++++++++++++++++++++++++++++++++++++++++

/*
 * 注文履歴一覧情報を取得する
 * 引数  ：$dbConnect                  => コネクションハンドラ
 *       ：$post                       => POST値
 *       ：$DISPLAY_STATUS_ACCEPTATION => 状態
 * 戻り値：$result         => 注文履歴一覧情報
 *
 * create 2007/04/23 H.Osugi
 *
 */
function getOrder($dbConnect, $post, $DISPLAY_STATUS_ACCEPTATION, $isLevelItc, $isLevelHonbu) {

	global $isLevelAdmin;	// 管理者権限の有無
	global $isLevelAgency;	// 一次代理店

	// 初期化
	$compId     = '';
	$staffCode  = '';
	$personName = '';
	$barCode    = '';
	$status     = '';
    $honbuCd = '';
    $shibuCd = '';

	// 店舗ID
	$compId = $post['searchCompId'];

	// 店舗IDに不正な値が入っていた場合
	if ($comId != '' && !ctype_digit($compId)) {
		$result = array();
		return $result;
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

	// スタッフ氏名
	$personName = $post['searchPersonName'];

	// 申請日
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
				$status .= 	" " . STATUS_APPLI_ADMIT;		// 承認済
				break;
			case '3':
				$status .= 	" " . STATUS_APPLI_DENY;		// 否認
				break;
			case '4':
				$status .= 	" " . STATUS_NOT_RETURN;		// 返却承認待
				$status .= 	" ," . STATUS_LOSS;				// 紛失承認待
				break;
			case '5':
				$status .= 	" " . STATUS_NOT_RETURN_ADMIT;	// 返却承認済
				$status .= 	" ," . STATUS_LOSS_ADMIT;		// 紛失承認済
				break;
			case '6':
				$status .= 	" " . STATUS_NOT_RETURN_DENY;	// 返却否認
				$status .= 	" ," . STATUS_LOSS_DENY;		// 紛失否認
				break;
			default:
				break;
		}

		if ($i != $countStatus -1) {
			$status .= " ,";
		}

	}

	if ($status == '') {
		$status  .= " " . STATUS_APPLI;
		$status  .= " ," . STATUS_APPLI_ADMIT;
		$status  .= " ," . STATUS_APPLI_DENY;

// 返却を検索対象から外す　2007/04/27
//		$status  .= " ," . STATUS_NOT_RETURN;
//		$status .= 	" ," . STATUS_LOSS;
//		$status  .= " ," . STATUS_NOT_RETURN_ADMIT;
//		$status .= 	" ," . STATUS_LOSS_ADMIT;
//		$status  .= " ," . STATUS_NOT_RETURN_DENY;
//		$status .= 	" ," . STATUS_LOSS_DENY;

	}

	// 注文履歴の一覧を取得する
	$sql  = "";
	$sql .= " (";
	$sql .= 	" SELECT";
	$sql .= 		" DISTINCT";
	$sql .= 		" tor.OrderID,";
	$sql .= 		" NULL as ReturnOrderID,";
	$sql .= 		" tor.AppliDay as AppliDay,";
	$sql .= 		" tor.AppliNo,";
	$sql .= 		" NULL as ReturnAppliNo,";
	$sql .= 		" tor.AppliCompCd,";
	$sql .= 		" tor.AppliCompName,";
	$sql .= 		" tor.StaffCode,";
	$sql .= 		" tor.PersonName,";
	$sql .= 		" tor.AppliMode,";
	$sql .= 		" tor.AppliSeason,";
	$sql .= 		" tor.Status,";
	$sql .= 		" tor.AgreeReason,";
	$sql .= 		" tor.AgreeDay";
	$sql .= 	" FROM";
	$sql .= 		" T_Order tor";
	$sql .= 	" INNER JOIN";
	$sql .= 		" T_Order_Details tod";
	$sql .= 	" ON";
	$sql .= 		" tor.OrderID = tod.OrderID";
	$sql .= 	" AND";
	$sql .= 		" tod.Del = " . DELETE_OFF;

	if ($status != '') {
		$sql .= 	" AND";
		$sql .= 		" tor.Status IN (";
		$sql .= 			$status;
		$sql .= 		" )";
	}

	$sql .= 	" INNER JOIN";
	$sql .= 		" M_Comp mco";
	$sql .= 	" ON";
	$sql .= 		" tor.CompID = mco.CompID";
	$sql .= 	" AND";
	$sql .= 		" mco.Del = " . DELETE_OFF;

	$sql .= 	" WHERE";
	$sql .= 		" tor.Del = " . DELETE_OFF;
	$sql .= 	" AND";
	$sql .= 		" tor.AppliMode = " . APPLI_MODE_ORDER;		// 発注

	if ($appliDayFrom != '') {
		$sql .= 	" AND";
		$sql .= 		" CONVERT(char, tor.AppliDay, 111) >= '" . db_Escape($appliDayFrom) . "'";
	}

	if ($appliDayTo != '') {
		$sql .= 	" AND";
		$sql .= 		" CONVERT(char, tor.AppliDay, 111) <= '" . db_Escape($appliDayTo) . "'";
	}

	if ($compId != '') {
		$sql .= 	" AND";
		$sql .= 		" tor.CompID = " . db_Escape($compId);
	}

	if ($staffCode != '') {
		$sql .= 	" AND";
		$sql .= 		" tor.StaffCode = '" . db_Escape($staffCode) . "'";
	}

	if ($personName != '') {
		$sql .= 	" AND";
		$sql .= 		" tor.PersonName LIKE ('%" . db_Escape($personName) . "%')";
	}

	//if ($_SESSION['ADMINLVL'] != '1') {
	//	$sql .= 	" AND";
	//	$sql .= 		" mco.CorpCd = '" . db_Escape($_SESSION['CORPCD']) . "'";
	//	if ($isLevelAgency == true) {
	//		$sql .= 	" AND";
	//		$sql .= 		" mco.AgencyID = '" . db_Escape($_SESSION['COMPID']) . "'";
	//	}
	//}
	if ($honbuCd != '') {
		$sql .= " AND";
		$sql .= 	" mco.HonbuCd = '" . db_Escape($honbuCd) . "'";
	}

	if ($shibuCd != '') {
		$sql .= " AND";
		$sql .= 	" mco.ShibuCd = '" . db_Escape($shibuCd) . "'";
	}

	$sql .= " )";

	if ($isLevelItc) {
		$sql .= " UNION ALL";

		$sql .= " (";
		$sql .= 	" SELECT";
		$sql .= 		" DISTINCT";
		$sql .= 		" tor2.OrderID,";
		$sql .= 		" tor3.OrderID as ReturnOrderID,";
		$sql .= 		" tor2.AppliDay as AppliDay,";
		$sql .= 		" tor2.AppliNo,";
		$sql .= 		" tor3.AppliNo as ReturnAppliNo,";
		$sql .= 		" tor2.AppliCompCd,";
		$sql .= 		" tor2.AppliCompName,";
		$sql .= 		" tor2.StaffCode,";
		$sql .= 		" tor2.PersonName,";
		$sql .= 		" tor2.AppliMode,";
		$sql .= 		" tor2.AppliSeason,";
		$sql .= 		" tor2.Status,";
		$sql .= 		" tor2.AgreeReason,";
		$sql .= 		" tor2.AgreeDay";
		$sql .= 	" FROM";
		$sql .= 		" T_Order tor2";
		$sql .= 	" INNER JOIN";
		$sql .= 		" T_Order_Details tod2";
		$sql .= 	" ON";
		$sql .= 		" tor2.OrderID = tod2.OrderID";
		$sql .= 	" AND";
		$sql .= 		" tod2.Del = " . DELETE_OFF;

		if ($status != '') {
			$sql .= 	" AND";
			$sql .= 		" tor2.Status IN (";
			$sql .= 			$status;
			$sql .= 		" )";
		}

		$sql .= 	" INNER JOIN";
		$sql .= 		" M_Comp mco2";
		$sql .= 	" ON";
		$sql .= 		" tor2.CompID = mco2.CompID";
		$sql .= 	" AND";
		$sql .= 		" mco2.Del = " . DELETE_OFF;

		$sql .= 	" INNER JOIN";
		$sql .= 		" T_Order tor3";
		$sql .= 	" ON";
		$sql .= 		" SUBSTRING(tor2.AppliNo, 2, 12) = SUBSTRING(tor3.AppliNo, 2, 12)";
		$sql .= 	" AND";
		$sql .= 		" SUBSTRING(tor3.AppliNo, 1, 1) = 'R'";		// 返却申請情報の取得
		$sql .= 	" AND";
		$sql .= 		" tor3.Del = " . DELETE_OFF;

		$sql .= 	" WHERE";
		$sql .= 		" tor2.Del = " . DELETE_OFF;
		$sql .= 	" AND";
		$sql .= 		" SUBSTRING(tor2.AppliNo, 1, 1) = 'A'";			// 発注申請情報の取得
		$sql .= 	" AND";
		$sql .= 		" tor2.AppliMode = " . APPLI_MODE_EXCHANGE;		// 交換

		if ($appliDayFrom != '') {
			$sql .= 	" AND";
			$sql .= 		" CONVERT(char, tor2.AppliDay, 111) >= '" . db_Escape($appliDayFrom) . "'";
		}

		if ($appliDayTo != '') {
			$sql .= 	" AND";
			$sql .= 		" CONVERT(char, tor2.AppliDay, 111) <= '" . db_Escape($appliDayTo) . "'";
		}

		if ($compId != '') {
			$sql .= 	" AND";
			$sql .= 		" tor2.CompID = " . db_Escape($compId);
		}

		if ($staffCode != '') {
			$sql .= 	" AND";
			$sql .= 		" tor2.StaffCode = '" . db_Escape($staffCode) . "'";
		}

		if ($personName != '') {
			$sql .= 	" AND";
			$sql .= 		" tor2.PersonName LIKE ('%" . db_Escape($personName) . "%')";
		}

		//if ($_SESSION['ADMINLVL'] != '1') {
		//	$sql .= 	" AND";
		//	$sql .= 		" mco2.CorpCd = '" . db_Escape($_SESSION['CORPCD']) . "'";
		//	if ($isLevelAgency == true) {
		//		$sql .= 	" AND";
		//		$sql .= 		" mco2.AgencyID = '" . db_Escape($_SESSION['COMPID']) . "'";
		//	}
		//}

		if ($honbuCd != '') {
			$sql .= 	" AND";
			$sql .= 		" mco2.HonbuCd = '" . db_Escape($honbuCd) . "'";
		}

		if ($shibuCd != '') {
			$sql .= 	" AND";
			$sql .= 		" mco2.ShibuCd = '" . db_Escape($shibuCd) . "'";
		}

		$sql .= " )";
	}

	$sql .= 	" ORDER BY";
	$sql .= 		" AppliDay DESC";

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	// 取得した値をHTMLエンティティ
	$resultCount = count($result);
	for ($i=0; $i<$resultCount; $i++) {

		$result[$i]['requestDay']      = strtotime($result[$i]['AppliDay']);
		$result[$i]['requestNo']       = $result[$i]['AppliNo'];
		$result[$i]['returnRequestNo'] = $result[$i]['ReturnAppliNo'];
		$result[$i]['orderId']         = $result[$i]['OrderID'];
		$result[$i]['returnOrderId']   = $result[$i]['ReturnOrderID'];
		$result[$i]['CompCd']          = castHtmlEntity($result[$i]['AppliCompCd']);
		$result[$i]['CompName']        = castHtmlEntity($result[$i]['AppliCompName']);
		$result[$i]['staffCode']       = castHtmlEntity($result[$i]['StaffCode']);
		$result[$i]['personName']       = castHtmlEntity($result[$i]['PersonName']);
		$result[$i]['reason']          = castHtmlEntity($result[$i]['AgreeReason']);


		// 申請番号の遷移先決定
		$result[$i]['isAppli'] = false;
		if (ereg('^A.*$', $result[$i]['AppliNo'])) {
			$result[$i]['isAppli'] = true;
		}

		// 承認日
		$result[$i]['isEmptyAgreeDay'] = true;
		if (isset($result[$i]['AgreeDay']) && $result[$i]['AgreeDay'] != '') {
			$result[$i]['AgreeDay']   = strtotime($result[$i]['AgreeDay']);
			$result[$i]['isEmptyAgreeDay'] = false;
		}

		// 区分
		$result[$i]['divisionOrder']    = false;
		$result[$i]['divisionExchange'] = false;
		$result[$i]['divisionReturn']   = false;
		switch ($result[$i]['AppliMode']) {
			case APPLI_MODE_ORDER:							// 発注
				$result[$i]['divisionOrder']    = true;
				break;
			case APPLI_MODE_EXCHANGE:						// 交換
				$result[$i]['divisionExchange'] = true;
				break;
			case APPLI_MODE_RETURN:							// 返却
				$result[$i]['divisionReturn']   = true;
				break;
			default:
				break;
		}

		// 状態
		$result[$i]['status']  = castHtmlEntity($DISPLAY_STATUS_ACCEPTATION[$result[$i]['Status']]);

		// 状態の文字列の色
		$result[$i]['statusIsRed']   = false;
		$result[$i]['statusIsGreen'] = false;
		$result[$i]['statusIsGray']  = false;
		$result[$i]['statusIsTeal']  = false;
		$result[$i]['statusIsPink']  = false;
		$result[$i]['statusIsBlack'] = false;
		switch ($result[$i]['Status']) {
			case STATUS_APPLI:							// 承認待
				$result[$i]['statusIsGray']  = true;
				break;
			case STATUS_APPLI_DENY:						// 否認
			case STATUS_NOT_RETURN_DENY:				// 返却否認
			case STATUS_LOSS_DENY:						// 紛失否認
				$result[$i]['statusIsPink']  = true;
				break;
			case STATUS_APPLI_ADMIT:					// 承認済
				$result[$i]['statusIsGreen'] = true;
				break;
			case STATUS_NOT_RETURN:						// 返却承認待
			case STATUS_NOT_RETURN_ADMIT:				// 返却承認済
				$result[$i]['statusIsRed']   = true;
				break;
			case STATUS_LOSS:							// 紛失承認待
			case STATUS_LOSS_ADMIT:						// 紛失承認済
				$result[$i]['statusIsTeal']  = true;
				break;
			default:
				$result[$i]['statusIsBlack'] = true;
				break;
		}

		// 承認待ちか承認済みか
		$result[$i]['isAgree'] = false;
		switch ($result[$i]['Status']) {
			case STATUS_APPLI_DENY:						// 否認
			case STATUS_APPLI_ADMIT:					// 承認済
			case STATUS_NOT_RETURN_DENY:				// 返却否認
			case STATUS_NOT_RETURN_ADMIT:				// 返却承認済
			case STATUS_LOSS_DENY:						// 紛失否認
			case STATUS_LOSS_ADMIT:						// 紛失承認済
				$result[$i]['isAgree'] = true;
				break;
			default:
				break;
		}


		// 承認済みか否認済みか
		$result[$i]['acceptationIsYes'] = false;
		$result[$i]['acceptationIsNo']  = false;
		switch ($result[$i]['Status']) {
			case STATUS_APPLI_ADMIT:					// 承認済
			case STATUS_NOT_RETURN_ADMIT:				// 返却承認済
			case STATUS_LOSS_ADMIT:						// 紛失承認済
				$result[$i]['acceptationIsYes'] = true;
				break;
			case STATUS_APPLI_DENY:						// 否認
			case STATUS_NOT_RETURN_DENY:				// 返却否認
			case STATUS_LOSS_DENY:						// 紛失否認
				$result[$i]['acceptationIsNo'] = true;
				break;
			default:
				break;
		}

		switch ($result[$i]['Status']) {
			case STATUS_APPLI:							// 承認待
				$result[$i]['StatusYes'] = STATUS_APPLI_ADMIT;			// 承認済
				$result[$i]['StatusNo']  = STATUS_APPLI_DENY;			// 否認
				break;
			case STATUS_NOT_RETURN:						// 返却承認待
				$result[$i]['StatusYes'] = STATUS_NOT_RETURN_ADMIT;		// 返却承認済
				$result[$i]['StatusNo']  = STATUS_NOT_RETURN_DENY;		// 返却否認
				break;
			case STATUS_LOSS:							// 紛失承認待
				$result[$i]['StatusYes'] = STATUS_LOSS_ADMIT;			// 紛失承認済
				$result[$i]['StatusNo']  = STATUS_LOSS_DENY;			// 紛失否認
				break;
			default:
				break;
		}

		// 検索ボタンを押した時は入力された情報は引き継がない
		if (!isset($post['searchFlg']) || $post['searchFlg'] != '1') {
			if (isset($post['acceptationY'][$result[$i]['orderId']]) && $post['acceptationY'][$result[$i]['orderId']] != '') {
				$result[$i]['acceptationIsYes'] = true;
			}
	
			if (isset($post['acceptationN'][$result[$i]['orderId']]) && $post['acceptationN'][$result[$i]['orderId']] != '') {
				$result[$i]['acceptationIsNo'] = true;
			}
	
			if (isset($post['reason'][$result[$i]['orderId']]) && $post['reason'][$result[$i]['orderId']] != '') {
				$result[$i]['reason'] = trim($post['reason'][$result[$i]['orderId']]);
			}
		}

	}

	return  $result;

}

/*
 * 検索条件を指定しているか判定
 * 引数  ：$post           => POST値
 * 戻り値：true：条件を指定している / false：条件を指定していない
 *
 * create 2007/04/23 H.Osugi
 *
 */
function checkCondition($post) {

	// 店舗IDの指定があった場合
	if (isset($post['searchCompId']) && $post['searchCompId'] != '') {
		return true;
	}

	// スタッフコードの指定があった場合
	if (isset($post['searchStaffCode']) && $post['searchStaffCode'] != '') {
		return true;
	}

	// スタッフ氏名の指定があった場合
	if (isset($post['searchPersonName']) && $post['searchPersonName'] != '') {
		return true;
	}

	// 申請日の指定があった場合
	if (isset($post['searchAppliDayFrom']) && $post['searchAppliDayFrom'] != '') {
		return true;
	}
	if (isset($post['searchAppliDayTo']) && $post['searchAppliDayTo'] != '') {
		return true;
	}

	// 状態の指定があった場合
	if (isset($post['searchStatus']) && count($post['searchStatus']) > 0) {
		return true;
	}

	return false;

}

?>
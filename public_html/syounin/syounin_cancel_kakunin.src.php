<?php
/*
 * 承認キャンセル確認画面
 * syounin_chancel_kakunin.src.php
 *
 * create 2007/04/24 H.Osugi
 *
 *
 */

// 各種モジュールの取得
include_once('../../include/define.php');				// 定数定義
require_once('../../include/dbConnect.php');			// DB接続モジュール
require_once('../../include/msSqlControl.php');			// DB操作モジュール
require_once('../../include/checkLogin.php');			// ログイン判定モジュール
require_once('../../include/redirectPost.php');			// リダイレクトポストモジュール
require_once('../../include/castHidden.php');			// hidden値成型モジュール
require_once('../../include/castHtmlEntity.php');		// HTMLエンティティモジュール

// 承認権限のないユーザが閲覧しようとするとTOPに強制遷移
if ($isLevelAdmin  == false && $isLevelAcceptation  == false) {

	$hiddens = array();
	redirectPost(HOME_URL . 'top.php', $hiddens);

}

// 初期設定
$isMenuAcceptation = true;	// 承認処理のメニューをアクティブに

// 変数の初期化 ここから *******************************************************
$requestNo     = '';				// 申請番号
$requestDay    = '';				// 申請日
$compName      = '';				// 店舗名 
$compCd        = '';				// 店舗コード 
$staffCode     = '';				// スタッフコード
$personName    = '';				// スタッフ氏名
$staffTypeName = '';				// 社員区分
$keihiName     = '';				// 経費負担
$zip1          = '';				// 郵便番号（前半3桁）
$zip2          = '';				// 郵便番号（後半4桁）
$address       = '';				// 住所
$shipName      = '';				// 出荷先名
$staffName     = '';				// ご担当者
$tel           = '';				// 電話番号
$memo          = '';				// メモ

$selectedReason1  = false;			// サイズ交換
$selectedReason2  = false;			// 汚損・破損交換
$selectedReason3  = false;			// 紛失交換
$selectedReason11 = false;			// 退店返却
$selectedReason12 = false;			// その他返却


$isEmptyMemo = true;				// メモが空かどうかを判定するフラグ

$haveTok     = false;				// 特寸があるかどうかを判定するフラグ

$isReturn    = false;				// 返却か交換かを判定するフラグ
$isOrder     = false;				// 発注かどうかを判定するフラグ
// 変数の初期化 ここまで ******************************************************

// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST);

// 表示する値の成型 ここから +++++++++++++++++++++++++++++++++++++++++++++++++++
// OrderID
$orderId = trim($post['orderId']);
if (isset($post['returnOrderId'])) {
	$returnOrderId = trim($post['returnOrderId']);
}

// 承認か否認かを取得
$isAgree = getIsAgree($dbConnect, $orderId);

// 申請情報の取得
$orderData = getOrderData($dbConnect, $orderId);

// 申請情報をHTMLエンティティ
$orderData = castHtmlEntity($orderData); 

// 申請番号
$requestNo = $orderData['AppliNo'];

// 返却の場合
if ($orderData['AppliMode'] == APPLI_MODE_RETURN) {

	// 表示する商品詳細情報取得
	$items = getStaffOrderDetailReturns($dbConnect, $orderId);

	// 返却かどうかを判定するフラグ
	$isReturn   = true;

}

// 交換の場合
if ($orderData['AppliMode'] == APPLI_MODE_EXCHANGE) {

	// 表示する商品詳細情報取得
	$items = getStaffOrderDetailExchanges($dbConnect, $orderId, $returnOrderId);

}

// 発注の場合
if ($orderData['AppliMode'] == APPLI_MODE_ORDER) {

	// 表示する商品詳細情報取得
	$items = getStaffOrderDetailPersonal($dbConnect, $orderId);

	// 発注かどうかを判定するフラグ
	$isOrder   = true;

}

//var_dump($items);
//die;

// 表示する情報が取得できなければエラー
if (count($items) <= 0) {

	$hiddens['errorName'] = 'syouninCancel';
	$hiddens['menuName']  = 'isMenuAcceptation';
	$hiddens['returnUrl'] = 'syounin/syounin.php';
	$hiddens['errorId'][] = '902';
	$errorUrl             = HOME_URL . 'error.php';

	redirectPost($errorUrl, $hiddens);

}

// 特寸があるかどうか
if ($orderData['Tok'] == '1') {

	$tokData = getTokData($dbConnect, $orderId);

	// 特寸情報をHTMLエンティティ
	$tokData = castHtmlEntity($tokData); 

	// 特寸情報が存在するかの判定フラグ
	$haveTok = true;

	// 身長
	$high     = $tokData['Height'];

	// 体重
	$weight   = $tokData['Weight'];

	// バスト
	$bust     = $tokData['Bust'];

	// ウエスト
	$waist    = $tokData['Waist'];

	// ヒップ
	$hips     = $tokData['Hips'];

	// 肩幅
	$shoulder = $tokData['Shoulder'];

	// 袖丈
	$sleeve   = $tokData['Sleeve'];

    // 着丈
    $kitake   = $tokData['Kitake'];

    // 裄丈
    $yukitake = $tokData['Yukitake'];

    // 股下
    $inseam   = $tokData['Inseam'];

	// スカート丈
	$length   = $tokData['Length'];

	// 特寸備考
	$tokMemo  = $orderData['TokNote'];


}

// 申請日
$isEmptyRequestDay = false;
$requestDay = '';
if ($orderData['AppliDay'] != '') {
	$requestDay = strtotime($orderData['AppliDay']);
}
else {
	$isEmptyRequestDay = true;
}



// 店舗名
$compName = $orderData['AppliCompName'];

// 店舗コード
$compCd = $orderData['AppliCompCd'];

// スタッフコード
$staffCode = $orderData['StaffCode'];

// スタッフ氏名
$personName = $orderData['PersonName'];

// 社員区分
$staffTypeName = $orderData['StaffType'];

// 経費負担
$keihiName = $DISPLAY_KEIHI_MODE[$orderData['Keihi']];

// 発注・交換の場合
if ($orderData['AppliMode'] == APPLI_MODE_EXCHANGE || $orderData['AppliMode'] == APPLI_MODE_ORDER) {

	// 郵便番号
	list($zip1, $zip2) = explode('-', $orderData['Zip']);
	
	// 住所
	$address = $orderData['Adrr'];
	
	// 出荷先名
	$shipName = $orderData['ShipName'];
	
	// ご担当者
	$staffName = $orderData['TantoName'];
	
	// 電話番号
	$tel = $orderData['Tel'];

}

// メモ
$memo = trim($orderData['Note']);

if ($memo != '' || $memo === 0) {
	$isEmptyMemo = false;
}

// 返却理由
switch ($orderData['AppliReason']) {

	// 返却理由（退店返却）
	case APPLI_REASON_RETURN_RETIRE:
		$selectedReason11 = true;
		break;

	// 返却理由（その他返却）
	case APPLI_REASON_RETURN_OTHER:
		$selectedReason12 = true;
		break;

	// 交換理由（サイズ交換）
	case APPLI_REASON_EXCHANGE_SIZE:
		$selectedReason1 = true;
		break;

	// 交換理由（汚損・破損交換）
	case APPLI_REASON_EXCHANGE_BREAK:
		$selectedReason2 = true;
		break;

	// 交換理由（紛失交換）
	case APPLI_REASON_EXCHANGE_LOSS:
		$selectedReason3 = true;
		break;

	// 交換理由（不良品交換）
	case APPLI_REASON_EXCHANGE_INFERIORITY:
		$selectedReason4 = true;
		break;

	// 交換理由（修理交換）
	case APPLI_REASON_EXCHANGE_REPAIR:
		$selectedReason7 = true;
		break;

	default:
		break;

}

$countSearchStatus = count($post['searchStatus']);
for ($i=0; $i<$countSearchStatus; $i++) {
	$post['searchStatus[' . $i . ']'] = $post['searchStatus'][$i];
}
$countOrderIds = count($post['orderIds']);
for ($i=0; $i<$countOrderIds; $i++) {

	if (isset($post['reason'][ $post['orderIds'][$i]]) && $post['reason'][ $post['orderIds'][$i]] != '') {
		$post['reason[' .  $post['orderIds'][$i] . ']'] = $post['reason'][ $post['orderIds'][$i]];
	}
	if (isset($post['acceptationY'][ $post['orderIds'][$i]])) {
		$post['acceptationY[' .  $post['orderIds'][$i] . ']'] = $post['acceptationY'][ $post['orderIds'][$i]];
	}
	if (isset($post['acceptationN'][ $post['orderIds'][$i]])) {
		$post['acceptationN[' .  $post['orderIds'][$i] . ']'] = $post['acceptationN'][ $post['orderIds'][$i]];
	}
}

$notArrowKeys = array('searchStatus', 'reason', 'orderIds', 'acceptationY', 'acceptationN', 'returnOrderIds');
$hiddenHtml = castHidden($post, $notArrowKeys);
// 表示する値の成型 ここまで ++++++++++++++++++++++++++++++++++++++++++++++++++

/*
 * 表示する商品一覧情報（交換）を取得する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$orderId        => 申請番号
 *       ：$returnOrderId  => 申請番号
 * 戻り値：$result         => 表示する商品一覧情報
 *
 * create 2007/04/24 H.Osugi
 *
 */
function getStaffOrderDetailExchanges($dbConnect, $orderId, $returnOrderId) {

	// 表示する商品の一覧を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" DISTINCT";
	$sql .= 	" tod.OrderDetID,";
	$sql .= 	" tod.ItemName,";
	$sql .= 	" tod.BarCd,";
	$sql .= 	" tod.Size,";
	$sql .= 	" tod.Status,";
	$sql .= 	" tod2.Size as selectedSize";
	$sql .= " FROM";
	$sql .= 	" T_Order tor";
	$sql .= " INNER JOIN";
	$sql .= 	" T_Order_Details tod";
	$sql .= " ON";
	$sql .= 	" tor.OrderID = tod.OrderID";
	$sql .= " AND";
	$sql .= 	" tod.OrderID = '" . db_Escape($returnOrderId) . "'";
	$sql .= " AND";
	$sql .= 	" tod.Del = " . DELETE_OFF;
	$sql .= " INNER JOIN";
	$sql .= 	" T_Order_Details tod2";
	$sql .= " ON";
	$sql .= 	" tod2.OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" SUBSTRING(tod.AppliNo, 2, 12) = SUBSTRING(tod2.AppliNo, 2, 12)";
	$sql .= " AND";
	$sql .= 	" tod.ItemID = tod2.ItemID";
	$sql .= " AND";
	$sql .= 	" tod2.Del = " . DELETE_OFF;
	$sql .= " INNER JOIN";
	$sql .= 	" M_Item mi";
	$sql .= " ON";
	$sql .= 	" tod.ItemID = mi.ItemID";
	$sql .= " AND";
	$sql .= 	" mi.Del = " . DELETE_OFF;
	$sql .= " WHERE";
	$sql .= 	" tor.Del = " . DELETE_OFF;
	$sql .= " ORDER BY";
	$sql .= 	" tod.OrderDetID ASC";

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	// 取得した値をHTMLエンティティ
	$resultCount = count($result);
	for ($i=0; $i<$resultCount; $i++) {
		$result[$i]['ItemName']     = castHtmlEntity($result[$i]['ItemName']);
		$result[$i]['BarCd']        = castHtmlEntity($result[$i]['BarCd']);
		$result[$i]['Size']         = castHtmlEntity($result[$i]['Size']);
		$result[$i]['selectedSize'] = castHtmlEntity($result[$i]['selectedSize']);

		// バーコードが空かどうか判定
		$result[$i]['isEmptyBarCd'] = false;
		if ($result[$i]['BarCd'] == '') {
			$result[$i]['isEmptyBarCd'] = true;
		}

	}

	return  $result;

}

/*
 * 表示する商品一覧情報（返却）を取得する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$orderId        => 申請番号
 * 戻り値：$result         => 表示する商品一覧情報
 *
 * create 2007/04/24 H.Osugi
 *
 */
function getStaffOrderDetailReturns($dbConnect, $orderId) {

	// 表示する商品の一覧を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" tod.ItemName,";
	$sql .= 	" tod.BarCd,";
	$sql .= 	" tod.Size,";
	$sql .= 	" tod.Status,";
	$sql .= 	" tod.DamageCheck";
	$sql .= " FROM";
	$sql .= 	" T_Order tor";
	$sql .= " INNER JOIN";
	$sql .= 	" T_Order_Details tod";
	$sql .= " ON";
	$sql .= 	" tor.OrderID = tod.OrderID";
	$sql .= " AND";
	$sql .= 	" tod.Del = " . DELETE_OFF;
	$sql .= " AND";
	$sql .= 	" tod.OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " INNER JOIN";
	$sql .= 	" M_Item mi";
	$sql .= " ON";
	$sql .= 	" tod.ItemID = mi.ItemID";
	$sql .= " AND";
	$sql .= 	" mi.Del = " . DELETE_OFF;
	$sql .= " WHERE";
	$sql .= 	" tod.Status <> " . STATUS_RETURN_NOT_APPLY;	// 返却未申請（25）ははぶく
	$sql .= " AND";
	$sql .= 	" tor.Del = " . DELETE_OFF;
	$sql .= " ORDER BY";
	$sql .= 	" tod.OrderDetID ASC";

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	// 取得した値をHTMLエンティティ
	$resultCount = count($result);
	for ($i=0; $i<$resultCount; $i++) {
		$result[$i]['ItemName']   = castHtmlEntity($result[$i]['ItemName']);
		$result[$i]['BarCd']      = castHtmlEntity($result[$i]['BarCd']);
		$result[$i]['Size']       = castHtmlEntity($result[$i]['Size']);

		// バーコードが空かどうか判定
		$result[$i]['isEmptyBarCd'] = false;
		if ($result[$i]['BarCd'] == '') {
			$result[$i]['isEmptyBarCd'] = true;
		}

		// 返却・紛失のどちらか判定
		switch ($result[$i]['Status']) {

			case STATUS_NOT_RETURN:			// 返却承認待の場合
			case STATUS_NOT_RETURN_ADMIT:	// 未返却の場合
			case STATUS_NOT_RETURN_ORDER:	// 未返却の場合
			case STATUS_NOT_RETURN_DENY:	// 返却否認の場合
				$result[$i]['isCheckedReturn'] = true;
				break;
			case STATUS_LOSS:				// 紛失承認待の場合
			case STATUS_LOSS_ADMIT:			// 紛失の場合
			case STATUS_LOSS_ORDER:			// 紛失の場合
			case STATUS_LOSS_DENY:			// 紛失否認の場合
				$result[$i]['isCheckedReturn'] = false;
				break;
			default:
				break;
			
		}

		// 汚損・破損が選択されたか判定
		$result[$i]['isCheckedBroken'] = false;
		if (isset($result[$i]['DamageCheck']) && $result[$i]['DamageCheck'] == 1) {
			$result[$i]['isCheckedBroken'] = true;
		}

	}

	return  $result;

}

/*
 * 申請情報を取得する
 * 引数  ：$dbConnect    => コネクションハンドラ
 *       ：$orderId      => OrderID
 * 戻り値：$result       => 申請情報
 *
 * create 2007/04/24 H.Osugi
 *
 */
function getOrderData($dbConnect, $orderId) {

	// 初期化
	$requestDay = '';

	// 表示する申請情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" AppliNo,";
	$sql .= 	" AppliDay,";
	$sql .= 	" AppliCompCd,";
	$sql .= 	" AppliCompName,";
	$sql .= 	" AppliSeason,";
	$sql .= 	" StaffCode,";
	$sql .= 	" PersonName,";
	$sql .= 	" AppliMode,";
	$sql .= 	" AppliReason,";
	$sql .= 	" Zip,";
	$sql .= 	" Adrr,";
	$sql .= 	" Tel,";
	$sql .= 	" ShipName,";
	$sql .= 	" TantoName,";
	$sql .= 	" Tok,";
	$sql .= 	" Note,";
	$sql .= 	" TokNote";
	$sql .= " FROM";
	$sql .= 	" T_Order";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;

	$result = db_Read($dbConnect, $sql);

	// 情報が取得できなかった場合
	if (!is_array($result) || count($result) <= 0) {
	 	return false;
	}

	return $result[0];

}

/*
 * OrderIDを取得する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$requestNo => 申請番号
 * 戻り値：$orderId   => OrderID
 *
 * create 2007/04/24 H.Osugi
 *
 */
function getOrderId($dbConnect, $requestNo) {

	// OrderIDを取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" OrderID";
	$sql .= " FROM";
	$sql .= 	" T_Order";
	$sql .= " WHERE";
	$sql .= 	" AppliNo = '" . db_Escape($requestNo) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;

	$orderDatas = db_Read($dbConnect, $sql);

	// 実行結果が失敗の場合
	if (!isset($orderDatas[0]['OrderID']) || $orderDatas[0]['OrderID'] == '') {
		return false;
	}

	return $orderDatas[0]['OrderID'];

}

/*
 * 申請情報を取得する
 * 引数  ：$dbConnect    => コネクションハンドラ
 *       ：$orderId      => OrderID
 * 戻り値：$result       => 申請情報
 *
 * create 2007/04/24 H.Osugi
 *
 */
function getTokData($dbConnect, $orderId) {

	// 表示する申請情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" Height,";
	$sql .= 	" Weight,";
	$sql .= 	" Bust,";
	$sql .= 	" Waist,";
	$sql .= 	" Hips,";
	$sql .= 	" Shoulder,";
	$sql .= 	" Sleeve,";
	$sql .= 	" Length,";
    $sql .=     " Kitake,";
    $sql .=     " Yukitake,";
    $sql .=     " Inseam";
	$sql .= " FROM";
	$sql .= 	" T_Tok";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;

	$result = db_Read($dbConnect, $sql);

	// 情報が取得できなかった場合
	if (!is_array($result) || count($result) <= 0) {
	 	return false;
	}

	return $result[0];

}

/*
 * 表示する商品一覧情報（個別申請）を取得する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$orderId        => 申請番号
 * 戻り値：$result         => 表示する商品一覧情報
 *
 * create 2007/05/23 H.Osugi
 *
 */
function getStaffOrderDetailPersonal($dbConnect, $orderId) {

	// 変更する発注申請詳細情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" COUNT(tod.ItemNo) as itemNumber,";
	$sql .= 	" mi.ItemID,";
	$sql .= 	" mi.ItemName,";
	$sql .= 	" tod.Size,";
	$sql .= 	" mi.SizeID";
	$sql .= " FROM";
	$sql .= 	" T_Order_Details tod";
	$sql .= " INNER JOIN";
	$sql .= 	" M_Item mi";
	$sql .= " ON";
	$sql .= 	" tod.ItemID = mi.ItemID";
	$sql .= " AND";
	$sql .= 	" mi.Del = " . DELETE_OFF;
	$sql .= " WHERE";
	$sql .= 	" tod.OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" tod.Del = " . DELETE_OFF;
	$sql .= " GROUP BY";
	$sql .= 	" mi.ItemID,";
	$sql .= 	" mi.ItemName,";
	$sql .= 	" tod.Size,";
	$sql .= 	" mi.SizeID";
	$sql .= " ORDER BY";
	$sql .= 	" mi.ItemID ASC";

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	// 取得した値をHTMLエンティティ
	$resultCount = count($result);
	for ($i=0; $i<$resultCount; $i++) {
		$result[$i]['ItemName']     = castHtmlEntity($result[$i]['ItemName']);
		$result[$i]['itemNumber']   = $result[$i]['itemNumber'];
		$result[$i]['selectedSize'] = castHtmlEntity($result[$i]['Size']);

	}

	return  $result;

}

/*
 * 承認か否認かを取得する
 * 引数  ：$dbConnect    => コネクションハンドラ
 *       ：$orderId      => OrderID
 * 戻り値：$result       => 申請情報
 *
 * create 2007/04/24 H.Osugi
 *
 */
function getIsAgree($dbConnect, $orderId) {

	// 状態を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" Status";
	$sql .= " FROM";
	$sql .= 	" T_Order";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;

	$result = db_Read($dbConnect, $sql);

	$isAgree = '';
	switch ($result[0]['Status']) {
		case STATUS_APPLI_ADMIT:					// 承認済
		case STATUS_NOT_RETURN_ADMIT:				// 返却承認済
		case STATUS_LOSS_ADMIT:						// 紛失承認済
			$isAgree = true;
			break;
		case STATUS_APPLI_DENY:						// 否認
		case STATUS_NOT_RETURN_DENY:				// 返却否認
		case STATUS_LOSS_DENY:						// 紛失否認
			$isAgree = false;
			break;
		default:
			break;
	}

	return $isAgree;

}

?>
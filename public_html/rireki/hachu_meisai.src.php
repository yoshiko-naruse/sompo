<?php
/*
 * 返却明細画面
 * henpin_meisai.src.php
 *
 * create 2007/03/26 H.Osugi
 *
 *
 */

// 各種モジュールの取得
include_once('../../include/define.php');				// 定数定義
require_once('../../include/dbConnect.php');			// DB接続モジュール
require_once('../../include/msSqlControl.php');			// DB操作モジュール
require_once('../../include/checkLogin.php');			// ログイン判定モジュール
require_once('../../include/checkData.php');			// 対象文字列検証モジュール
require_once('../../include/redirectPost.php');			// リダイレクトポストモジュール
require_once('../../include/castHidden.php');			// hidden値成型モジュール
require_once('../../include/castHtmlEntity.php');		// HTMLエンティティモジュール
require_once('../../include/castOrderDetId.php');		// 選択したOrderDetIDを成型するモジュール
require_once('../../include/checkReturn.php');			// 返却可能か判定モジュール

// 初期設定
$isMenuHistory = true;	// 申請履歴のメニューをアクティブに

// 変数の初期化 ここから *******************************************************
$requestNo  = '';					// 申請番号
$requestDay = '';					// 申請日
$compName   = '';					// 店舗名 
$compCd     = '';					// 店舗コード 
$staffCode  = '';					// スタッフコード
$zip1       = '';					// 郵便番号（前半3桁）
$zip2       = '';					// 郵便番号（後半4桁）
$address    = '';					// 住所
$shipName   = '';					// 出荷先名
$staffName  = '';					// ご担当者
$tel        = '';					// 電話番号
$yoteiDay   = '';					// 出荷指定日
$memo       = '';					// メモ
$rentalStartDay = '';				// レンタル開始日

$haveTok  = false;					// 特寸から遷移してきたか判定フラグ

$high     = '';						// 身長
$weight   = '';						// 体重
$bust     = '';						// バスト
$waist    = '';						// ウエスト
$hips     = '';						// ヒップ
$shoulder = '';						// 肩幅
$sleeve   = '';						// 袖丈
$length   = '';						// スカート丈
$kitake   = '';                     // 着丈
$yukitake = '';                     // 裄丈
$inseam   = '';                     // 股下
$tokMemo  = '';						// 特寸備考

$isEmptyMemo = true;				// メモが空かどうかを判定するフラグ
$isEmptyRentalStartDay = true;		// レンタル開始日が空かどうかを判定するフラグ
$isSizeNoDisp = false;				// サイズ非表示フラグ

$isAppliMode = false;
// 変数の初期化 ここまで ******************************************************

// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST);

// 表示する値の成型 ここから +++++++++++++++++++++++++++++++++++++++++++++++++++

// OrderID
$orderId = trim($post['orderId']);

// 表示する商品詳細情報取得
$orders = getStaffOrderDetails($dbConnect, $orderId, $DISPLAY_STATUS);

$GoukeiKingaku = 0;
for ($i=0; $i<sizeof($orders); $i++) {
	$GoukeiKingaku += $orders[$i]['Price'];
	$orders[$i]['Price'] = number_format($orders[$i]['Price']);
}
$GoukeiKingaku = number_format($GoukeiKingaku);

// 表示する情報が取得できなければエラー
if (count($orders) <= 0) {

	$hiddens['errorName'] = 'hachuMeisai';
	$hiddens['menuName']  = 'isMenuHistory';
	$hiddens['returnUrl'] = 'rireki/rireki.php';
	$hiddens['errorId'][] = '901';
	$errorUrl             = HOME_URL . 'error.php';

	redirectPost($errorUrl, $hiddens);

}

// 一般ユーザーの場合はサイズを＊表示
//if ($_SESSION['USERLVL'] == USER_AUTH_LEVEL_GENERAL) {
//	$orderCount = count($orders);
//	for ($i=0; $i<$orderCount; $i++) {
//		$orders[$i]['Size'] = '****';
//	}
//	$isSizeNoDisp = true;				// サイズ非表示フラグON
//}

// 申請情報の取得
$orderData = getOrderData($dbConnect, $orderId);

// 申請情報をHTMLエンティティ
$orderData = castHtmlEntity($orderData); 

// 申請番号
$requestNo = $orderData['AppliNo'];

// 申請日
$isEmptyRequestDay = false;
$requestDay = '';
if ($orderData['AppliDay'] != '') {
	$requestDay = strtotime($orderData['AppliDay']);
}
else {
	$isEmptyRequestDay = true;
}

// 新品/中古区分
$new_Item = false;
$newOldKbn = trim($orderData['NewOldKbn']);
if($newOldKbn == 1){
	$new_Item = true;
}

// 店舗名
$compName = $orderData['AppliCompName'];

// 店舗コード
$compCd = $orderData['AppliCompCd'];

if (isset($orderData['AppliMode']) && $orderData['AppliMode'] == '1') {
	$isAppliMode = true;
}

// スタッフコード
$staffCode = $orderData['StaffCode'];

// 着用者氏名
$personName = $orderData['PersonName'];

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

// 出荷予定日
$yoteiDay = $orderData['YoteiDay'];

// レンタル開始日
$rentalStartDay = $orderData['RentalStartDay'];

if ($rentalStartDay != '' || $rentalStartDay === 0) {
	$isEmptyRentalStartDay = false;
}

// 貸与パターン名
$appliPattern = getStaffPattern($dbConnect, $orderData['AppliPattern']);

// メモ
$memo = trim($orderData['Note']);

if ($memo != '' || $memo === 0) {
	$isEmptyMemo = false;
}

// 返却理由
switch ($orderData['AppliReason']) {

	// 返却理由（退職・異動返却）
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

// 特寸情報があった場合
if ($orderData['Tok'] == 1) {

	// 特寸情報の取得
	$tokData = getTokData($dbConnect, $orderId);

	// 特寸情報をHTMLエンティティ
	$tokData = castHtmlEntity($tokData); 

	// 特寸情報が存在するかの判定フラグ
	$haveTok  = true;

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

	// スカート丈
	$length   = $tokData['Length'];

    // 着丈
    $kitake   = $tokData['Kitake'];

    // 裄丈
    $yukitake   = $tokData['Yukitake'];

    // 股下
    $inseam   = $tokData['Inseam'];

	// 特寸備考
	$tokMemo  = $orderData['TokNote'];

}

// 表示する値の成型 ここまで ++++++++++++++++++++++++++++++++++++++++++++++++++

/*
 * 表示する商品一覧情報を取得する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$orderId        => OrderID
 *       ：$DISPLAY_STATUS => 状態
 * 戻り値：$result         => 表示する商品一覧情報
 *
 * create 2007/03/26 H.Osugi
 *
 */
function getStaffOrderDetails($dbConnect, $orderId, $DISPLAY_STATUS) {

	// OrderIDが空の場合
	if ($orderId == '') {
		$result = array();
	 	return $result;
	}

	// 表示する商品の一覧を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" mi.ItemID,";
	$sql .= 	" tod.ItemName,";
	$sql .= 	" mi.Price,";
	$sql .= 	" tod.BarCd,";
	$sql .= 	" tod.IcTagCd,";
	$sql .= 	" tod.Size,";
	$sql .= 	" tod.Status,";
	$sql .= 	" tod.TakuhaiNo";
	$sql .= " FROM";
	$sql .= 	" T_Order tor";
	$sql .= " INNER JOIN";
	$sql .= 	" T_Order_Details tod";
	$sql .= " ON";
	$sql .= 	" tor.OrderID = tod.OrderID";
	$sql .= " AND";
	$sql .= 	" tod.Del = " . DELETE_OFF;
	$sql .= " INNER JOIN";
	$sql .= 	" M_Item mi";
	$sql .= " ON";
	$sql .= 	" tod.ItemID = mi.ItemID";
	$sql .= " AND";
	$sql .= 	" mi.Del = " . DELETE_OFF;
	$sql .= " WHERE";
	$sql .= 	" tor.OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" tor.Del = " . DELETE_OFF;
	$sql .= " ORDER BY";
	$sql .= 	" tod.AppliLNo ASC,";
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
		$result[$i]['ItemName']   = castHtmlEntity($result[$i]['ItemName']);
		$result[$i]['BarCd']      = castHtmlEntity($result[$i]['BarCd']);
		$result[$i]['IcTagCd']    = castHtmlEntity($result[$i]['IcTagCd']);
		$result[$i]['Size']       = castHtmlEntity($result[$i]['Size']);
		$result[$i]['Price']      = castHtmlEntity($result[$i]['Price']);

		$result[$i]['num'] = ($i + 1);

		// バーコードが空かどうか判定
		$result[$i]['isEmptyBarCd'] = false;
		if ($result[$i]['BarCd'] == '') {
			$result[$i]['isEmptyBarCd'] = true;
		}

		// ICタグコードが空かどうか判定
		$result[$i]['isEmptyIcTagCd'] = false;
		if ($result[$i]['IcTagCd'] == '') {
			$result[$i]['isEmptyIcTagCd'] = true;
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
		$result[$i]['TakuhaiNo']      = castHtmlEntity($result[$i]['TakuhaiNo']);

	}

	return  $result;

}


/*
 * 申請情報を取得する
 * 引数  ：$dbConnect    => コネクションハンドラ
 *       ：$orderId      => OrderID
 * 戻り値：$result       => 申請情報
 *
 * create 2007/03/26 H.Osugi
 *
 */
function getOrderData($dbConnect, $orderId) {

	// 表示する申請情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" AppliNo,";
	$sql .= 	" AppliDay,";
	$sql .= 	" AppliCompCd,";
	$sql .= 	" AppliCompName,";
	$sql .= 	" AppliMode,";
	$sql .= 	" StaffCode,";
    $sql .=     " PersonName,";
	$sql .= 	" AppliReason,";
	$sql .= 	" AppliPattern,";
	$sql .= 	" Zip,";
	$sql .= 	" Adrr,";
	$sql .= 	" Tel,";
	$sql .= 	" ShipName,";
	$sql .= 	" TantoName,";
	$sql .= 	" Note,";
	$sql .= 	" TantoName,";
	$sql .= 	" CONVERT(varchar,RentalStartDay,111) AS RentalStartDay,";
	$sql .= 	" CONVERT(varchar,YoteiDay,111) AS YoteiDay,";
	$sql .= 	" NewOldKbn,";
	$sql .= 	" Tok,";
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
 * 申請情報を取得する
 * 引数  ：$dbConnect    => コネクションハンドラ
 *       ：$orderId      => OrderID
 * 戻り値：$result       => 申請情報
 *
 * create 2007/03/26 H.Osugi
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

// 対象スタッフの所属している貸与パターン選択コンボボックス作成
function getStaffPattern($dbConnect, $patternID) {

	// 初期化
	$result = array();

	$sql = " SELECT";
	$sql .= 	" PatternName";
	$sql .= " FROM";
	$sql .= 	" M_Pattern";
	$sql .= " WHERE";
	$sql .= 	" PatternID = '" . db_Escape($patternID) . "'";
	$sql .= " AND";
	$sql .= 	" Del = '" . DELETE_OFF . "'";
	$sql .= " GROUP BY";
	$sql .= 	" PatternName";

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	return $result[0]['PatternName'];
}

?>
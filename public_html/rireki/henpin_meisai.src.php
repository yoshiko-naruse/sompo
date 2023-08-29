<?php
/*
 * 返却明細画面
 * henpin_meisai.src.php
 *
 * create 2007/03/23 H.Osugi
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

// 初期設定
$isMenuHistory = true;			// 履歴のメニューをアクティブに

// 変数の初期化 ここから *******************************************************
$requestNo  = '';					// 申請番号
$requestDay = '';					// 申請日
$compName   = '';					// 店舗名
$compCd     = '';					// 店舗コード
$staffCode  = '';					// スタッフコード
$memo       = '';					// メモ
$rentalEndDay = '';					// レンタル終了日

$isEmptyMemo = true;				// メモが空かどうかを判定するフラグ
$isEmptyRentalEndDay = true;		// レンタル終了日が空かどうかを判定するフラグ

$selectedReason1  = false;			// 返却理由（サイズ交換）
$selectedReason2  = false;			// 返却理由（汚損・破損交換）
$selectedReason3  = false;			// 返却理由（紛失交換）
$selectedReason4  = false;			// 返却理由（不良品交換）
$selectedReason5  = false;			// 返却理由（初回サイズ交換）
$selectedReason11 = false;			// 返却理由（退職・異動返却）
$selectedReason12 = false;			// 返却理由（その他返却）

$isLoss = false;					// 紛失交換かどうかの判定フラグ

$isReturn = false;					// 返却かどうかの判定フラグ

$hasDsp    = false;					// 紛失届/汚損・破損届のリンク表示判定フラグ
$hasBroken = false;					// 汚損・破損届のリンク表示判定フラグ
$hasLoss   = false;					// 紛失届のリンク表示判定フラグ
$isSizeNoDisp = false;				// サイズ非表示フラグ
// 変数の初期化 ここまで ******************************************************

// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST);

// 表示する値の成型 ここから +++++++++++++++++++++++++++++++++++++++++++++++++++

// 交換から遷移してきた場合
if (isset($post['koukanShinseiFlg']) && $post['koukanShinseiFlg'] == 1) {
	$isMenuHistory  = false;
	$isMenuExchange = true;			// 交換のメニューをアクティブに
	$isMenuReturn   = false;
}

// 返却から遷移してきた場合
if (isset($post['henpinShinseiFlg']) && $post['henpinShinseiFlg'] == 1) {
	$isMenuHistory  = false;
	$isMenuExchange = false;
	$isMenuReturn   = true;			// 返却のメニューをアクティブに
}

// OrderID
$orderId = trim($post['orderId']);

// 表示する商品詳細情報取得
$returns = getStaffOrderDetails($dbConnect, $orderId, $DISPLAY_STATUS, $isReturn, $hasBroken, $hasLoss);

// 一般ユーザーの場合はサイズを＊表示
//if (isset($post['rirekiFlg']) && $post['rirekiFlg'] == '1') {
//	if ($_SESSION['USERLVL'] == USER_AUTH_LEVEL_GENERAL) {
//		$returnCount = count($returns);
//		for ($i=0; $i<$returnCount; $i++) {
//			$returns[$i]['Size'] = '****';
//		}
//		$isSizeNoDisp = true;				// サイズ非表示フラグON
//	}
//}

if ($isMenuExchange == true || $isMenuReturn == true || $isMenuAcceptation == true) {
	$hasBroken = false;
	$hasLoss   = false;	
}

if ($hasBroken == true || $hasLoss == true) {
	$hasDsp = true;
}

// 表示する情報が取得できなければエラー
if (count($returns) <= 0) {

	$hiddens['errorName'] = 'henpinMeisai';

	$hiddens['menuName']  = 'isMenuHistory';
	if ($isMenuExchange == true) {
		$hiddens['menuName']  = 'isMenuExchange';
	}
	elseif ($isMenuReturn == true) {
		$hiddens['menuName']  = 'isMenuReturn';
	}

	$hiddens['returnUrl'] = 'rireki/rireki.php';
	$hiddens['errorId'][] = '901';
	$errorUrl             = HOME_URL . 'error.php';

	redirectPost($errorUrl, $hiddens);

}

// 申請情報の取得
$orderData = getOrderData($dbConnect, $orderId);

// POST値をHTMLエンティティ
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

// 店舗名
$compName = $orderData['AppliCompName'];

// 店舗コード
$compCd = $orderData['AppliCompCd'];

// スタッフコード
$staffCode = $orderData['StaffCode'];

// 着用者名
$personName = $orderData['PersonName'];

// レンタル終了日
$rentalEndDay = $orderData['RentalEndDay'];

if ($rentalEndDay != '' || $rentalEndDay === 0) {
	$isEmptyRentalEndDay = false;
}

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
		$isLoss = true;
		$selectedReason3 = true;
		break;

	// 交換理由（不良品交換）
	case APPLI_REASON_EXCHANGE_INFERIORITY:
		$selectedReason4 = true;
		break;

	// 交換理由（初回サイズ交換）
	case APPLI_REASON_EXCHANGE_FIRST:
		$selectedReason5 = true;
		break;

	default:
		break;

}

// 表示する値の成型 ここまで ++++++++++++++++++++++++++++++++++++++++++++++++++

/*
 * 表示する商品一覧情報を取得する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$orderId        => OrderID
 *       ：$DISPLAY_STATUS => 状態(array)
 *       ：$isReturn       => 返却かどうかのフラグ
 *       ：$hasBroken      => 汚損・破損届のリンク表示判定フラグ
 *       ：$hasLoss        => 紛失届のリンク表示判定フラグ
 * 戻り値：$result         => 表示する商品一覧情報
 *
 * create 2007/03/23 H.Osugi
 *
 */
function getStaffOrderDetails($dbConnect, $orderId, $DISPLAY_STATUS, &$isReturn, &$hasBroken, &$hasLoss) {

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
	$sql .= 	" tor.AppliMode,";
	$sql .= 	" tod.BarCd,";
	$sql .= 	" tod.IcTagCd,";
	$sql .= 	" tod.Size,";
	$sql .= 	" tod.Status,";
	$sql .= 	" tod.DamageCheck,";
	$sql .= 	" tod.UnusedCheck";
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

	// AppliModeが返却の場合
	if (isset($result[0]['AppliMode']) && $result[0]['AppliMode'] == APPLI_MODE_RETURN) {
		$isReturn = true;
	}

	// 取得した値をHTMLエンティティ
	$resultCount = count($result);
	for ($i=0; $i<$resultCount; $i++) {
		$result[$i]['ItemName']   = castHtmlEntity($result[$i]['ItemName']);
		$result[$i]['BarCd']      = castHtmlEntity($result[$i]['BarCd']);
		$result[$i]['IcTagCd']    = castHtmlEntity($result[$i]['IcTagCd']);
		$result[$i]['Size']       = castHtmlEntity($result[$i]['Size']);

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

		// 汚損・破損だった場合
		if ($hasBroken == false && $result[$i]['DamageCheck'] == 1) {
			$hasBroken = true;
		}

		if ($hasLoss == false) {
			switch ($result[$i]['Status']) {
				case STATUS_LOSS:				// 紛失（承認待ち）
				case STATUS_LOSS_ADMIT:			// 紛失（承認済）
				case STATUS_LOSS_ORDER:			// 紛失（受注済）
					$hasLoss = true;
					break;
	
				default:
					break;
			}
		}

		// 状態の文字列の色
		$result[$i]['statusIsBlue']  = false;
		$result[$i]['statusIsRed']   = false;
		$result[$i]['statusIsTeal']  = false;
		$result[$i]['statusIsGreen'] = false;
		$result[$i]['statusIsGray']  = false;
		$result[$i]['statusIsPink']  = false;
		$result[$i]['statusIsBlack'] = false;
		switch ($result[$i]['Status']) {
			case STATUS_APPLI:				// 申請済（承認待ち）
			case STATUS_STOCKOUT:			// 在庫切れ
				$result[$i]['statusIsGray']  = true;
				break;
			case STATUS_APPLI_DENY: 		// 申請済（否認）
			case STATUS_NOT_RETURN_DENY:	// 未返却 （否認）
			case STATUS_LOSS_DENY:			// 紛失（否認）
				$result[$i]['statusIsPink']  = true;
				break;
			case STATUS_APPLI_ADMIT:		// 申請済（承認済）
				$result[$i]['statusIsGreen'] = true;
				break;
			case STATUS_ORDER:				// 受注済
				$result[$i]['statusIsBlue']  = true;
				break;
			case STATUS_NOT_RETURN:			// 未返却（承認待ち）
			case STATUS_NOT_RETURN_ADMIT:	// 未返却（承認済）
			case STATUS_NOT_RETURN_ORDER:	// 未返却（受注済）
				$result[$i]['statusIsRed']   = true;
				break;
			case STATUS_LOSS:				// 紛失（承認待ち）
			case STATUS_LOSS_ADMIT:			// 紛失（承認済）
			case STATUS_LOSS_ORDER:			// 紛失（受注済）
				$result[$i]['statusIsTeal']  = true;
				break;
			default:
				$result[$i]['statusIsBlack'] = true;
				break;
		}

		// 汚損・破損
		$result[$i]['isBroken'] = false;
		if (isset($result[$i]['DamageCheck']) && $result[$i]['DamageCheck'] == 1) {
			$result[$i]['isBroken'] = true;
		}

		// 未着用
		$result[$i]['isUnused'] = false;
		if (isset($result[$i]['UnusedCheck']) && $result[$i]['UnusedCheck'] == 1) {
			$result[$i]['isUnused'] = true;
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
 * create 2007/03/23 H.Osugi
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
    $sql .=     " PersonName,";
	$sql .= 	" StaffCode,";
	$sql .= 	" AppliReason,";
	$sql .= 	" CONVERT(varchar,RentalEndDay,111) AS RentalEndDay,";
	$sql .= 	" Note";
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

?>
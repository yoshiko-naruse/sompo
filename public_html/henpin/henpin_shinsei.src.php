<?php
/*
 * 返却申請入力画面
 * henpin_shinsei.src.php
 *
 * create 2007/03/22 H.Osugi
 *
 *
 */

// 各種モジュールの取得
include_once('../../include/define.php');			// 定数定義
require_once('../../include/dbConnect.php');		// DB接続モジュール
require_once('../../include/msSqlControl.php');		// DB操作モジュール
require_once('../../include/checkLogin.php');		// ログイン判定モジュール
require_once('../../include/castHtmlEntity.php');	// HTMLエンティティモジュール
require_once('../../include/createRequestNo.php');	// 申請番号生成モジュール
require_once('../../include/getStaff.php');			// スタッフ情報取得モジュール
require_once('../../include/redirectPost.php');		// リダイレクトポストモジュール
require_once('../../include/commonFunc.php');       // 共通関数モジュール

// 初期設定
$isMenuReturn = true;	// 返却のメニューをアクティブに

// 変数の初期化 ここから ******************************************************
$searchCompCd = '';
$searchCompName = '';
$searchCompId = '';
$searchStaffCd = '';
$searchPersonName = '';

$requestNo = '';					// 申請番号
$staffCode = '';					// スタッフコード
$memo      = '';					// メモ
$rentalEndDay = '';					// レンタル終了日

$selectedReason1 = false;			// 返却理由（退職・異動返却）
$selectedReason2 = false;			// 返却理由（その他返却）

// 変数の初期化 ここまで ******************************************************

// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST); 

// スタッフIDが取得できなければエラーに
if (!isset($post['staffId']) || $post['staffId'] == '') {
    // TOP画面に強制遷移
	$returnUrl             = HOME_URL . 'top.php';
	redirectPost($returnUrl, $hiddens);
}

$staffId = trim($post['staffId']);		// StaffID

if (isset($post['searchFlg']) && $post['searchFlg'] != '') {
    $searchFlg = $post['searchFlg'];
} else {
    $searchFlg = '';
}

if (isset($post['nowPage']) && $post['nowPage'] != '') {
    $nowPage = $post['nowPage'];
} else {
    $nowPage = '';
}

if (isset($post['appliReason']) && $post['appliReason'] != '') {
    $appliReason = $post['appliReason'];
} else {
    $appliReason = '';
}

if (isset($post['searchStaffCode']) && $post['searchStaffCode'] != '') {
    $searchStaffCode = $post['searchStaffCode'];
} else {
    $searchStaffCode = '';
}

if (isset($post['searchPersonName']) && $post['searchPersonName'] != '') {
    $searchPersonName = $post['searchPersonName'];
} else {
    $searchPersonName = '';
}

if (isset($post['searchHonbuId']) && $post['searchHonbuId'] != '') {
    $searchHonbuId = $post['searchHonbuId'];
} else {
    $searchHonbuId = '';
}

if (isset($post['searchShitenId']) && $post['searchShitenId'] != '') {
    $searchShitenId = $post['searchShitenId'];
} else {
    $searchShitenId = '';
}

if (isset($post['searchEigyousyoId']) && $post['searchEigyousyoId'] != '') {
    $searchEigyousyoId = $post['searchEigyousyoId'];
} else {
    $searchEigyousyoId = '';
}


// 画面表示用データ取得
$headerData = getHeaderData($dbConnect, $staffId);

// 店舗ID
$compId     = $headerData['CompID'];

// 店舗コード
$compCd     = $headerData['CompCd'];

// 店舗名
$compName   = $headerData['CompName'];

// スタッフコード
$staffCode  = $headerData['StaffCode'];

// 着用者名
$personName = $headerData['PersonName'];

// 返却可能は商品一覧を表示
$items = getStaffOrder($dbConnect, $staffId, $compId, $post);

// 返却可能商品が０件の場合
if (count($items) <= 0) {

	$hiddens['errorName'] = 'henpinShinsei';
	$hiddens['menuName']  = 'isMenuReturn';
	$hiddens['returnUrl'] = 'select_staff.php';
	$hiddens['errorId'][] = '901';
	$errorUrl             = HOME_URL . 'error.php';

	$hiddens['appliReason'] = trim($post['appliReason']);

	redirectPost($errorUrl, $hiddens);

}

// 表示する値の成型 ここから +++++++++++++++++++++++++++++++++++++++++++++++++++
// 初期表示の場合
if (!isset($post['henpinShinseiFlg']) || $post['henpinShinseiFlg'] != '1') {

	// 申請番号を生成
	$requestNo = createRequestNo($dbConnect, $compId, 2);

	// 申請番号の生成に失敗した場合はエラー
	if ($requestNo == false) {
		// エラー処理を行う
	}

}
// POST情報を引き継ぐ場合
else {

	// スタッフコード
	$staffCode = trim($post['staffCode']);

	// 申請番号を生成
	$requestNo = trim($post['requestNo']);

	// レンタル終了日
	$rentalEndDay = trim($post['rentalEndDay']);

	// メモ
	$memo = trim($post['memo']);

}

if ($isLevelAdmin == true) {
	$searchCompCd        = $post['searchCompCd'];		// 店舗番号
	$searchCompName      = $post['searchCompName'];		// 店舗名
	$searchCompId        = $post['searchCompId'];		// 店舗名
}
$searchStaffCd    = trim($post['searchStaffCd']);		// スタッフコード
$searchPersonName = trim($post['searchPersonName']);	// スタッフ氏名

$appliReason = trim($post['appliReason']);		// 返却理由

// 返却理由
switch (trim($appliReason)) {

	// 返却理由（退職・異動返却）
	case APPLI_REASON_RETURN_RETIRE:
		$selectedReason1 = true;
		break;

	// 返却理由（退職・異動返却）
	case APPLI_REASON_RETURN_OTHER:
		$selectedReason2 = true;
		break;

	default:
		break;
}

// 表示する値の成型 ここまで +++++++++++++++++++++++++++++++++++++++++++++++++++

/*
 * 返却可能商品一覧情報を取得する
 * 引数  ：$dbConnect    => コネクションハンドラ
 *       ：$staffId      => StaffID
 *       ：$compId       => 店舗ID
 *       ：$post         => POST値
 * 戻り値：$result       => 返却可能商品一覧情報
 *
 * create 2007/03/22 H.Osugi
 *
 */
function getStaffOrder($dbConnect, $staffId, $compId, $post) {

	// 返却可能商品の一覧を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" tod.OrderDetID,";
	$sql .= 	" mi.ItemID,";
	$sql .= 	" mi.ItemName,";
	$sql .= 	" tod.BarCd,";
	$sql .= 	" tod.Size,";
	$sql .= 	" mi.SizeID";
	$sql .= " FROM";
	$sql .= 	" T_Staff_Details tsd";
	$sql .= " INNER JOIN";
	$sql .= 	" T_Staff ts";
	$sql .= " ON";
	$sql .= 	" tsd.StaffID = ts.StaffID";
	$sql .= " AND";
	$sql .= 	" ts.Del = " . DELETE_OFF;
	$sql .= " INNER JOIN";
	$sql .= 	" T_Order_Details tod";
	$sql .= " ON";
	$sql .= 	" tsd.OrderDetID = tod.OrderDetID";
	$sql .= " AND";
	$sql .= 	" tod.Del = " . DELETE_OFF;
	$sql .= " INNER JOIN";
	$sql .= 	" M_Item mi";
	$sql .= " ON";
	$sql .= 	" tod.ItemID = mi.ItemID";
	$sql .= " AND";
	$sql .= 	" mi.Del = " . DELETE_OFF;
	$sql .= " INNER JOIN";
	$sql .= 	" T_Order tor";
	$sql .= " ON";
	$sql .= 	" tod.OrderID = tor.OrderID";
	$sql .= " AND";
	$sql .= 	" tor.Del = " . DELETE_OFF;
	$sql .= " WHERE";
	$sql .= 	" ts.StaffID = '" . db_Escape($staffId) . "'";
	$sql .= " AND";
	$sql .= 	" ts.CompID = '" . db_Escape($compId) . "'";
	$sql .= " AND";
	$sql .= 	" tsd.ReturnFlag = 0";
//	$sql .= " AND";
//	$sql .= 	" tsd.ReturnDetID IS NULL";
	$sql .= " AND";
	$sql .= 	" tsd.Status IN ( " . STATUS_SHIP . ", ". STATUS_DELIVERY  . ")";		// ステータスが出荷済(15),納品済(16)
	$sql .= " AND";
	$sql .= 	" tsd.Del = " . DELETE_OFF;
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

		$result[$i]['OrderDetID'] = $result[$i]['OrderDetID'];
		$result[$i]['ItemName']   = castHtmlEntity($result[$i]['ItemName']);
		$result[$i]['BarCd']      = castHtmlEntity($result[$i]['BarCd']);
		$result[$i]['Size']       = castHtmlEntity($result[$i]['Size']);

		$result[$i]['Num']        = $i;

		// バーコードが空かどうか判定
		$result[$i]['isEmptyBarCd'] = false;
		if ($result[$i]['BarCd'] == '') {
			$result[$i]['isEmptyBarCd'] = true;
		}

		// ラジオボックスが選択されているか判定
		$result[$i]['isReturnChecked'] = false;
		$result[$i]['isLostChecked']   = false;

		// POST値が送信されてきた場合
		if (is_array($post['orderDetIds'])) {
			// 返却がチェックされている場合
			if (isset($post['returnChk'][$result[$i]['OrderDetID']]) && $post['returnChk'][$result[$i]['OrderDetID']] == 1) {
				$result[$i]['isReturnChecked'] = true;
			}
			// 紛失がチェックされている場合
			elseif (isset($post['lostChk'][$result[$i]['OrderDetID']]) && $post['lostChk'][$result[$i]['OrderDetID']] == 1) {
				$result[$i]['isLostChecked'] = true;
			}

			// 汚損・破損がチェックされている場合
			if (isset($post['brokenChk'][$result[$i]['OrderDetID']]) && $post['brokenChk'][$result[$i]['OrderDetID']] == 1) {
				$result[$i]['isBrokenChecked'] = true;
			}

		}
		// 退職・異動返却の場合、初期設定は全ての商品で返却をチェックする
		else {
			if ($post['appliReason'] == APPLI_REASON_RETURN_RETIRE) {
				$result[$i]['isReturnChecked'] = true;
			}
		}
	}

	return  $result;

}

?>
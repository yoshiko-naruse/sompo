<?php
/*
 * 返却確認画面
 * henpin_shinsei_kakunin.src.php
 *
 * create 2007/03/22 H.Osugi
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
require_once('../../include/checkDuplicateAppli.php');	// 申請番号重複判定モジュール
require_once('../../include/castHidden.php');			// hidden値成型モジュール
require_once('../../include/castHtmlEntity.php');		// HTMLエンティティモジュール
require_once('../../include/castOrderDetId.php');		// 選択したOrderDetIDを成型するモジュール
require_once('../../include/checkReturn.php');			// 返却可能か判定モジュール
require_once('../../include/commonFunc.php');           // 共通関数モジュール
require_once('./henpin_shinsei.val.php');				// エラー判定モジュール

// 初期設定
$isMenuReturn = true;			// 返却のメニューをアクティブに

// 変数の初期化 ここから *******************************************************
$requestNo = '';					// 申請番号
$staffCode = '';					// スタッフコード
$memo      = '';					// メモ
$rentalEndDay  = '';              	// レンタル終了日

$isEmptyMemo = true;				// メモが空かどうかを判定するフラグ
$isEmptyRentalEndDay = true;		// レンタル終了日が空かどうかを判定するフラグ

$selectedReason1 = false;			// 返却理由（退職・異動返却）
$selectedReason2 = false;			// 返却理由（その他返却）

// 変数の初期化 ここまで ******************************************************

// スタッフIDが取得できなければエラーに
if (!isset($_POST['staffId']) || $_POST['staffId'] == '' || !(int)$_POST['staffId']) {
    // TOP画面に強制遷移
	$returnUrl             = HOME_URL . 'top.php';
	redirectPost($returnUrl, $hiddens);
}

// 申請番号がすでに登録されていないか判定
checkDuplicateAppliNo($dbConnect, $_POST['requestNo'], 'henpin/henpin_top.php', 3);

// エラー判定
validatePostData($_POST);

// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST); 


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


// 返却・紛失のどちらかが選択されたorderDetIDを取得する
$orderDetIds = castOrderDetId($post);

// 返却できないユニフォームが存在しないかを判定する
if ($post['appliReason'] == APPLI_REASON_RETURN_RETIRE) {
	checkReturn($dbConnect, $post['orderDetIds'], 'henpin/henpin_top.php');
}
else {
	checkReturn($dbConnect, $orderDetIds, 'henpin/henpin_top.php');
}
// 表示する商品詳細情報取得
$items = getStaffOrderSelect($dbConnect, $post, $orderDetIds);

// 表示する値の成型 ここから +++++++++++++++++++++++++++++++++++++++++++++++++++

// 画面表示用データ取得
$headerData = getHeaderData($dbConnect, $post['staffId']);

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

// 申請番号
$requestNo = trim($post['requestNo']);

// レンタル終了日
$rentalEndDay = trim($post['rentalEndDay']);

if ($rentalEndDay != '' || $rentalEndDay === 0) {
	$isEmptyRentalEndDay = false;
}

// メモ
$memo = trim($post['memo']);

if ($memo != '' || $memo === 0) {
	$isEmptyMemo = false;
}

$appliReason = trim($post['appliReason']);	// 返却理由

// 返却理由
switch (trim($post['appliReason'])) {

	// 返却理由（退職・異動返却）
	case APPLI_REASON_RETURN_RETIRE:
		$selectedReason1 = true;
		break;

	// 返却理由（その他返却）
	case APPLI_REASON_RETURN_OTHER:
		$selectedReason2 = true;
		break;

	default:
		break;

}

// hidden値の成型
$countOrderDetIds = count($post['orderDetIds']);
// 退職・異動返却の場合
if ($post['appliReason'] == APPLI_REASON_RETURN_RETIRE) {
	for ($i=0; $i<$countOrderDetIds; $i++) {
		$post['orderDetIds[' . $i . ']'] = $post['orderDetIds'][$i];
		if (isset($post['returnChk'][$post['orderDetIds'][$i]]) && $post['returnChk'][$post['orderDetIds'][$i]] == 1) {
			$post['returnChk[' . $post['orderDetIds'][$i] . ']'] = trim($post['returnChk'][$post['orderDetIds'][$i]]);
		}
		elseif (isset($post['lostChk'][$post['orderDetIds'][$i]]) && $post['lostChk'][$post['orderDetIds'][$i]] == 1) {
			$post['lostChk[' . $post['orderDetIds'][$i] . ']'] = trim($post['lostChk'][$post['orderDetIds'][$i]]);
		}
		if (isset($post['brokenChk'][$post['orderDetIds'][$i]]) && $post['brokenChk'][$post['orderDetIds'][$i]] == 1) {
			$post['brokenChk[' . $post['orderDetIds'][$i] . ']'] = trim($post['brokenChk'][$post['orderDetIds'][$i]]);
		}
	}
}
// その他返却の場合
elseif ($post['appliReason'] == APPLI_REASON_RETURN_OTHER) {
	$j = 0;
	for ($i=0; $i<$countOrderDetIds; $i++) {
		if (isset($post['returnChk'][$post['orderDetIds'][$i]]) && $post['returnChk'][$post['orderDetIds'][$i]] == 1) {
			$post['orderDetIds[' . $j . ']'] = $post['orderDetIds'][$i];
			$post['returnChk[' . $post['orderDetIds'][$i] . ']'] = trim($post['returnChk'][$post['orderDetIds'][$i]]);
			$j++;
		}
		elseif (isset($post['lostChk'][$post['orderDetIds'][$i]]) && $post['lostChk'][$post['orderDetIds'][$i]] == 1) {
			$post['orderDetIds[' . $j . ']'] = $post['orderDetIds'][$i];
			$post['lostChk[' . $post['orderDetIds'][$i] . ']'] = trim($post['lostChk'][$post['orderDetIds'][$i]]);
			$j++;
		}
		if (isset($post['brokenChk'][$post['orderDetIds'][$i]]) && $post['brokenChk'][$post['orderDetIds'][$i]] == 1) {
			$post['brokenChk[' . $post['orderDetIds'][$i] . ']'] = trim($post['brokenChk'][$post['orderDetIds'][$i]]);
		}
	}
}

$notArrowKeys = array('orderDetIds', 'returnChk', 'lostChk', 'brokenChk');
$hiddenHtml = castHidden($post, $notArrowKeys);
// 表示する値の成型 ここまで ++++++++++++++++++++++++++++++++++++++++++++++++++

/*
 * 選択された商品一覧情報を取得する
 * 引数  ：$dbConnect    => コネクションハンドラ
 *       ：$post         => POST値
 *       ：$orderDetIds  => 選択されたorderDetID(array)
 * 戻り値：$result       => 選択された商品一覧情報
 *
 * create 2007/03/22 H.Osugi
 *
 */
function getStaffOrderSelect($dbConnect, $post, $orderDetIds) {

	// 初期化
	$result = array();

	if (!is_array($orderDetIds) || count($orderDetIds) <= 0) {
		return $result;
	}

	$orderDetId = '';
	if(is_array($orderDetIds)) {
		$orderDetId = implode(', ', $orderDetIds);
	}

	// 選択された商品の一覧を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" tod.OrderDetID,";
	$sql .= 	" mi.ItemID,";
	$sql .= 	" mi.ItemName,";
	$sql .= 	" tod.BarCd,";
	$sql .= 	" tod.Size";
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
	$sql .= 	" tod.OrderDetID IN (" . db_Escape($orderDetId) . ")";
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

		// バーコードが空かどうか判定
		$result[$i]['isEmptyBarCd'] = false;
		if ($result[$i]['BarCd'] == '') {
			$result[$i]['isEmptyBarCd'] = true;
		}

		// 返却・紛失のどちらが選択されたか判定
		$result[$i]['isCheckedReturn'] = true;
		if (isset($post['lostChk'][$result[$i]['OrderDetID']]) && $post['lostChk'][$result[$i]['OrderDetID']] == 1) {
			$result[$i]['isCheckedReturn'] = false;
		}

		// 汚損・破損が選択されたか判定
		$result[$i]['isCheckedBroken'] = false;
		if (isset($post['brokenChk'][$result[$i]['OrderDetID']]) && $post['brokenChk'][$result[$i]['OrderDetID']] == 1) {
			$result[$i]['isCheckedBroken'] = true;
		}

	}

	return  $result;

}

?>
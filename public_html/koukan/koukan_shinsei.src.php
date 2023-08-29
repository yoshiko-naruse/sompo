<?php
/*
 * 交換申請入力画面
 * koukan_shinsei.src.php
 *
 * create 2007/03/19 H.Osugi
 *
 *
 */

// 各種モジュールの取得
include_once('../../include/define.php');			// 定数定義
require_once('../../include/dbConnect.php');		// DB接続モジュール
require_once('../../include/msSqlControl.php');		// DB操作モジュール
require_once('../../include/checkLogin.php');		// ログイン判定モジュール
require_once('../../include/castHtmlEntity.php');	// HTMLエンティティモジュール
require_once('../../include/getComp.php');			// 店舗情報取得モジュール
require_once('../../include/getUser.php');			// ユーザ情報取得モジュール
require_once('../../include/getSize.php');			// サイズ情報取得モジュール
require_once('../../include/getStaff.php');			// スタッフ情報取得モジュール
require_once('../../include/createRequestNo.php');	// 申請番号生成モジュール
require_once('../../include/redirectPost.php');		// リダイレクトポストモジュール
require_once('../../include/commonFunc.php');       // 共通関数モジュール
require_once('./koukan_func.php');                  // 交換機能共通関数モジュール

// 初期設定
$isMenuExchange = true;	// 交換のメニューをアクティブに

// 変数の初期化 ここから ******************************************************
$searchCompCd = '';
$searchCompName = '';
$searchCompId = '';
$searchStaffCd = '';
$searchPersonName = '';

$requestNo = '';					// 申請番号
$staffCode = '';					// スタッフコード
$personName = '';                    // スタッフコード
$zip1      = '';					// 郵便番号（前半3桁）
$zip2      = '';					// 郵便番号（後半4桁）
$address   = '';					// 住所
$shipName  = '';					// 出荷先名
$staffName = '';					// ご担当者
$tel       = '';					// 電話番号
$memo      = '';					// メモ

$displayRequestNo = '';				// 申請番号（表示用）

$selectedSize = array();			// 選択されたサイズ

$selectedReason1 = false;			// 交換理由（サイズ交換）
$selectedReason2 = false;			// 交換理由（汚損・破損交換）
$selectedReason3 = false;			// 交換理由（紛失交換）
$selectedReason4 = false;			// 交換理由（不良品交換）
$selectedReason5 = false;           // 交換理由（初回サイズ交換）

$exchangeGuideMessage = '';			// 初回サイズ交換時に表示するメッセージ文字列

$isMotoTok      = false;            // 交換訂正する時に元の発注で特寸が選択されていたか

// 変数の初期化 ここまで ******************************************************

// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST); 

// 検索パラメーターの設定
//$searchStaffCode = $post['searchStaffCode'];


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


// スタッフIDが取得できなければエラーに
if (!isset($post['rirekiFlg']) || !$post['rirekiFlg']) {
    if (!isSetValue($post['staffId'])) {
    
    	// TOP画面に強制遷移
//var_dump("aaaa");die;
        $returnUrl = HOME_URL . 'top.php';
    	redirectPost($returnUrl, $hiddens);
    }
} else {    // 変更時は申請IDがなければエラー
    if (!isSetValue($post['orderId'])) {
//var_dump("bbbb");die;

        redirectTop();
    }

    $isMenuExchange   = false; // 交換のメニューをオフ
    $isMenuHistory = true;  // 申請履歴のメニューをアクティブに
    $haveRirekiFlg = true;  // 交換申請か交換変更かを判定するフラグ
}

$staffId = trim($post['staffId']);		// StaffID

$appliReason = trim($post['appliReason']);  // 交換理由

// 交換理由
switch (trim($appliReason)) {

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

    // 交換理由（初回サイズ交換）
    case APPLI_REASON_EXCHANGE_FIRST:
        $selectedReason5 = true;
// 一時的に日数制限を解除 by T.Uno at 2023/04/27
//        $exchangeGuideMessage = '※出荷から' . EXCHANGE_TERM_DAY . '日以内の商品のみ表示されます。';
        $exchangeGuideMessage = '';
        break;

    default:
        break;
}

// 画面上部に表示するデータを取得
$headerData = getHeaderData($dbConnect, $staffId);

// 店舗コードを取得
$compCd = '';
if (isSetValue($headerData['CompCd'])) {
    $compCd = $headerData['CompCd'];
}

// 店舗名を取得
$compName = '';
if (isSetValue($headerData['CompName'])) {
    $compName = $headerData['CompName'];
}

// スタッフコードを取得
$staffCode = '';
if (isSetValue($headerData['StaffCode'])) {
    $staffCode = $headerData['StaffCode'];
}

// 着用者氏名
$personName = '';
if (isSetValue($headerData['PersonName'])) {
    $personName = $headerData['PersonName'];
}


// 履歴からの遷移の場合は、申請内容を取得
if (isset($post['rirekiFlg']) && $post['rirekiFlg'] == COMMON_FLAG_ON) {     
    if (!isset($post['hachuShinseiFlg']) || !$post['hachuShinseiFlg']) {    
        $post = getOrderedContents($dbConnect, $post['orderId'], $post);
    }
}   

// 交換可能な商品一覧を表示
$items = getStaffOrder($dbConnect, $staffId, trim($post['appliReason']), $post);

// 交換可能商品が０件の場合
if (count($items) <= 0) {

	$hiddens['errorName']       = 'koukanShinsei';
	$hiddens['menuName']        = 'isMenuExchange';
	$hiddens['returnUrl']       = 'koukan/select_staff.php';
	$hiddens['errorId'][]       = '901';
	$errorUrl                   = HOME_URL . 'error.php';

	$hiddens['appliReason']    = trim($post['appliReason']);
	$hiddens['searchStaffCode'] = $post['searchStaffCode'];
	$hiddens['searchFlg']       = '1';

	if ($isLevelAdmin == true) {
		$hiddens['searchCompCd']   = trim($post['searchCompCd']);		// 店舗番号
		$hiddens['searchCompName'] = trim($post['searchCompName']);		// 店舗名
		$hiddens['searchCompId']   = trim($post['searchCompId']);		// 店舗名
	}

	redirectPost($errorUrl, $hiddens);
}


// 表示する値の成型 ここから +++++++++++++++++++++++++++++++++++++++++++++++++++
// 初期表示の場合
if (!isset($post['koukanShinseiFlg']) || $post['koukanShinseiFlg'] != '1') {

	// 申請番号を生成
	$requestNo = createRequestNo($dbConnect, $headerData['CompID'], 3);
	$displayRequestNo = 'A' . trim($requestNo);		// 頭文字に'A'をつける

	// 申請番号の生成に失敗した場合はエラー
	if ($requestNo == false) {
		// エラー処理を行う
	}

	// 郵便番号
	if (isset($headerData['Zip'])) {
		list($zip1, $zip2) = explode('-', $headerData['Zip']);
	}

	// 住所
	if (isset($headerData['Adrr'])) {
		$address = $headerData['Adrr'];
	}

	// 出荷先名
	if (isset($headerData['ShipName'])) {
		$shipName = $headerData['ShipName'];
	}

	// 電話番号
	if (isset($headerData['Tel'])) {
		$tel = $headerData['Tel'];
	}

	// ご担当者名を取得（HTMLエンティティ済）
    if (isset($headerData['TantoName'])) {
        $staffName = $headerData['TantoName'];
    } else {
        $staffName  = DEFAULT_STAFF_NAME;
	}

}
// POST情報を引き継ぐ場合
else {

	// 申請番号を生成
	$requestNo = trim($post['requestNo']);
	$displayRequestNo = 'A' . trim($post['requestNo']);		// 頭文字に'A'をつける

	// スタッフコード
	$staffCode = trim($headerData['StaffCode']);

    // 着用者氏名
    $personName = trim($headerData['PersonName']);

	// 郵便番号
	$zip1 = trim($post['zip1']);
	$zip2 = trim($post['zip2']);

	// 住所
	$address = trim($post['address']);

	// 出荷先名
	$shipName = trim($post['shipName']);

	// ご担当者
	$staffName  = trim($post['staffName']);

	// 電話番号
	$tel = trim($post['tel']);

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

// 表示する値の成型 ここまで +++++++++++++++++++++++++++++++++++++++++++++++++++
/*
 * 交換可能商品一覧情報を取得する
 * 引数  ：$dbConnect    => コネクションハンドラ
 *       ：$orderId    => 注文ID
 *       ：$post       => POST値
 * 戻り値：$result       => 交換可能商品一覧情報
 *
 * create 2007/03/19 H.Osugi
 *
 */
function getOrderedContents($dbConnect, $orderId, $post) {

    $result = array();

    // orderＩdからAppliNoを取得する
    $sql  = " SELECT";
    $sql .=     " AppliNo";
    $sql .= " FROM";
    $sql .=     " T_Order";
    $sql .= " WHERE";
    $sql .=     " OrderID = '".$orderId."'";

    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合
    if (count($result) <= 0) {
        $result = array();
        return $result;
    }

    $returnAppliNo = "R".substr($result[0]['AppliNo'], 1);
    $orderAppliNo  = "A".substr($result[0]['AppliNo'], 1);

    // 初期化
    $result = array();

    // 商品の一覧を取得する
    $sql  = "";
    $sql .= " SELECT";
    $sql .=     " todr.MotoOrderDetID,";
    $sql .=     " mi.ItemID,";
    $sql .=     " tod.Size,";
    $sql .=     " mi.SizeID";
    $sql .= " FROM";
    $sql .=     " T_Staff_Details tsd";
    $sql .= " INNER JOIN";
    $sql .=     " T_Order_Details tod";
    $sql .= " ON";
    $sql .=     " tsd.OrderDetID = tod.OrderDetID";
    $sql .= " AND";
    $sql .=     " tod.Del = " . DELETE_OFF;
    $sql .= " INNER JOIN";
    $sql .=     " T_Order_Details todr";
    $sql .= " ON";
    $sql .=     " todr.OrderDetID = tod.OrderDetID";
    $sql .= " AND";
    $sql .=     " todr.Del = " . DELETE_OFF;
    $sql .= " INNER JOIN";
    $sql .=     " M_Item mi";
    $sql .= " ON";
    $sql .=     " tod.ItemID = mi.ItemID";
    $sql .= " AND";
    $sql .=     " mi.Del = " . DELETE_OFF;
    $sql .= " INNER JOIN";
    $sql .=     " T_Order tor";
    $sql .= " ON";
    $sql .=     " tod.OrderID = tor.OrderID";
    $sql .= " AND";
    $sql .=     " tor.Del = " . DELETE_OFF;
    $sql .= " WHERE";
    $sql .=     " tor.AppliNo = '" . db_Escape($orderId) . "'";
    $sql .= " AND";
    $sql .=     " tsd.ReturnFlag = 0";
    $sql .= " AND";
    $sql .=     " tsd.ReturnDetID IS NULL";
    $sql .= " AND";
    $sql .=     " tsd.Del = " . DELETE_OFF;
    $sql .= " ORDER BY";
    $sql .=     " mi.ItemID ASC";

    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合
    if (count($result) <= 0) {
        $result = array();
        return $result;
    }

    // 取得した値をHTMLエンティティ
    foreach ($result as $key => $val) {
        $post['orderDetIds'][] = $val['MotoOrderDetID'];                

        // サイズ展開を取得
        $sizeData = array();
        $sizeData = array_flip(getSize($dbConnect, $val['SizeID'], 1));

        $post['size'][$val['MotoOrderDetID']] = $sizeData[$val['Size']];
    }

    return  $post;

}

?>
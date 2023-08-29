<?php
/*
 * 交換確認画面
 * koukan_shinsei_kakunin.src.php
 *
 * create 2007/03/20 H.Osugi
 * update 2007/04/03 H.Osugi 特寸処理追加
 *
 */

// 各種モジュールの取得
include_once('../../include/define.php');				// 定数定義
require_once('../../include/dbConnect.php');			// DB接続モジュール
require_once('../../include/msSqlControl.php');			// DB操作モジュール
require_once('../../include/checkLogin.php');			// ログイン判定モジュール
require_once('../../include/getSize.php');				// サイズ情報取得モジュール
require_once('../../include/checkData.php');			// 対象文字列検証モジュール
require_once('../../include/redirectPost.php');			// リダイレクトポストモジュール
require_once('../../include/checkDuplicateAppli.php');	// 申請番号重複判定モジュール
require_once('../../include/castHidden.php');			// hidden値成型モジュール
require_once('../../include/castHtmlEntity.php');		// HTMLエンティティモジュール
require_once('../../include/checkExchange.php');		// 交換可能か判定モジュール
require_once('../../include/commonFunc.php');           // 共通関数モジュール
require_once('./koukan_func.php');                      // 交換機能共通関数モジュール
require_once('./koukan_shinsei.val.php');				// エラー判定モジュール
require_once('./koukan_tokusun.val.php');				// 特寸のエラー判定モジュール

// 初期設定
$isMenuExchange = true;			// 交換のメニューをアクティブに

// 変数の初期化 ここから *******************************************************
$requestNo = '';					// 申請番号
$staffCode = '';					// スタッフコード
$zip1      = '';					// 郵便番号（前半3桁）
$zip2      = '';					// 郵便番号（後半4桁）
$address   = '';					// 住所
$shipName  = '';					// 出荷先名
$staffName = '';					// ご担当者
$tel       = '';					// 電話番号
$memo      = '';					// メモ

$displayRequestNo = '';				// 申請番号（表示用）

$isEmptyMemo = true;				// メモが空かどうかを判定するフラグ

$selectedSize = array();			// 選択されたサイズ

$selectedReason1 = false;			// 交換理由（サイズ交換）
$selectedReason2 = false;			// 交換理由（汚損・破損交換）
$selectedReason3 = false;			// 交換理由（紛失交換）
$selectedReason4 = false;			// 交換理由（不良品交換）
$selectedReason5 = false;           // 交換理由（初回サイズ交換）

$dispTwoPane     = false;           // 画面構成を２段にするか

$haveTok  = false;					// 特寸から遷移してきたか判定フラグ

$high     = '';						// 身長
$weight   = '';						// 体重
$bust     = '';						// バスト
$waist    = '';						// ウエスト
$hips     = '';						// ヒップ
$shoulder = '';						// 肩幅
$sleeve   = '';						// 袖丈
$length   = '';						// 首周り
$kitake   = '';                     // 着丈
$yukitake = '';                     // 裄丈
$inseam   = '';                     // 股下
$tokMemo  = '';						// 特寸備考

$returnUrl = '';					// 戻り先URL

// 変数の初期化 ここまで ******************************************************

// スタッフIDが取得できなければエラーに
if (!isset($_POST['staffId']) || $_POST['staffId'] == '') {
	// TOP画面に強制遷移
    $returnUrl = HOME_URL . 'top.php';
	redirectPost($returnUrl, $hiddens);
}

// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST); 

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
        break;

    default:
        break;
}


//}

// 申請番号がすでに登録されていないか判定
checkDuplicateAppliNo($dbConnect, $_POST['requestNo'], 'koukan/koukan_top.php', 2);

// エラー判定
validatePostData($dbConnect, $_POST);

// 交換できないユニフォームが存在しないかを判定する
checkExchange($dbConnect, $_POST['orderDetIds'], 'koukan/koukan_shinsei.php', $_POST);

$items = getStaffOrderSelect($dbConnect, $post);


if (isset($post['tokFlg']) && $post['tokFlg'] == 1) {
	// 特寸エラー判定
	validateTokData($_POST);
}
else {
	// 特寸判定
	checkTok($dbConnect, $post, $items);
}

// 表示する値の成型 ここから +++++++++++++++++++++++++++++++++++++++++++++++++++

// 画面上部に表示するデータを取得
$headerData = getHeaderData($dbConnect, $post['staffId']);

// 申請番号
$requestNo = trim($post['requestNo']);
$displayRequestNo = 'A' . trim($requestNo);		// 頭文字に'A'をつける

// 店舗コード
$compCd = trim($headerData['CompCd']);
// 店舗ID
$compId = trim($headerData['CompID']);
// 店舗名
$compName = trim($headerData['CompName']);

// 着用者名
$personName = trim($headerData['PersonName']);

// スタッフコード
$staffCode = trim($headerData['StaffCode']);

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

if ($memo != '' || $memo === 0) {
	$isEmptyMemo = false;
}

// 戻り先URL
$returnUrl = './koukan_shinsei.php';

// 特寸から遷移してきた場合
if (isset($post['tokFlg']) && $post['tokFlg'] == 1) {

	$haveTok = true;

	// 身長
	$high     = trim($post['high']);

	// 体重
	$weight   = trim($post['weight']);

	// バスト
	$bust     = trim($post['bust']);

	// ウエスト
	$waist    = trim($post['waist']);

	// ヒップ
	$hips     = trim($post['hips']);

	// 肩幅
	$shoulder = trim($post['shoulder']);

	// 袖丈
	$sleeve   = trim($post['sleeve']);

	// 首周り
	$length   = trim($post['length']);

    // 着丈
    $kitake   = trim($post['kitake']);

    // 裄丈
    $yukitake = trim($post['yukitake']);

    // 股下
    $inseam   = trim($post['inseam']);

	// 特寸備考
	$tokMemo  = trim($post['tokMemo']);

	// 戻り先URL
	$returnUrl = './koukan_tokusun.php';
	
}

if ($isLevelAdmin == true) {
	$searchCompCd    = $post['searchCompCd'];		// 店舗番号
	$searchCompName  = $post['searchCompName'];	// 店舗名
}

// hidden値の成型

$countOrderDetIds = count($post['orderDetIds']);

for ($i=0; $i<$countOrderDetIds; $i++) {
    $post['orderDetIds[' . $i . ']'] = $post['orderDetIds'][$i];
    if (count(getSize($dbConnect, $post['sizeType'][$post['orderDetIds'][$i]], 1)) > 1) {  // フリーかどうか判定
        $post['size[' . $post['orderDetIds'][$i] . ']'] = trim($post['size'][$post['orderDetIds'][$i]]);
    }
    $post['sizeType[' . $post['orderDetIds'][$i] . ']'] = trim($post['sizeType'][$post['orderDetIds'][$i]]);
    $post['itemUnused[' . $post['orderDetIds'][$i] . ']'] = trim($post['itemUnused'][$post['orderDetIds'][$i]]);
}
$notArrowKeys = array('orderDetIds' , 'size', 'sizeType', 'itemUnused');

$hiddenHtml = castHidden($post, $notArrowKeys);
// 表示する値の成型 ここまで ++++++++++++++++++++++++++++++++++++++++++++++++++

/*
 * 選択された商品一覧情報を取得する
 * 引数  ：$dbConnect    => コネクションハンドラ
 *       ：$post         => POST値
 *       ：$sizeData1    => サイズ情報1
 *       ：$sizeData2    => サイズ情報2
 *       ：$sizeData3    => サイズ情報3
 * 戻り値：$result       => 選択された商品一覧情報
 *
 * create 2007/03/20 H.Osugi
 *
 */
function getStaffOrderSelect($dbConnect, $post) {

	$orderDetIds = '';
	if(is_array($post['orderDetIds'])) {
		$orderDetIds = implode(', ', $post['orderDetIds']);
	}

	// 商品情報を取得する
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
	$sql .= 	" tod.OrderDetID IN (" . db_Escape($orderDetIds) . ")";
	$sql .= " AND";
	$sql .= 	" tsd.Del = " . DELETE_OFF;
  	$sql .= " ORDER BY";
	$sql .= 	" mi.ItemID ASC";

//var_dump($sql);

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
		$result[$i]['SizeID']     = $result[$i]['SizeID'];

		// バーコードが空かどうか判定
		$result[$i]['isEmptyBarCd'] = false;
		if ($result[$i]['BarCd'] == '') {
			$result[$i]['isEmptyBarCd'] = true;
		}

		// サイズの表示情報を成型
        $sizeAry = array();
        $sizeAry = getSize($dbConnect, $result[$i]['SizeID'], 1);
		if (is_array($sizeAry) && isSetValue($sizeAry)) {
    		$result[$i]['selectedSize'] = castHtmlEntity($sizeAry[$post['size'][$result[$i]['OrderDetID']]]);
		}

        // 選択チェックボックスが選択されているか判定
        $result[$i]['isUnused'] = false;
        if (isset($post['itemUnused'][$result[$i]['OrderDetID']]) && trim($post['itemUnused'][$result[$i]['OrderDetID']]) == '1') {
            $result[$i]['isUnused'] = true;
        }
	}

	return  $result;

}

/*
 * 特寸が選択されているか判定
 * 引数  ：$post        => POST値
 *     ：$dispTwoPane => true:マタニティか役職変更
 * 戻り値：なし
 *
 * create 2007/04/03 H.Osugi
 *
 */
function checkTok($dbConnect, $post, $items) {

	// ==============================================================
	// 対象の申請に特注アイテムが含まれているか確認
	// ==============================================================
	$isTokFlg = false;
	for ($i=0; $i<count($items); $i++) {
		if(isset($items[$i]['selectedSize']) && (strpos($items[$i]['selectedSize'], "特") !== false) && $items[$i]['selectedSize'] != '') {
		 	$isTokFlg = true;
		}
	}

	if ($isTokFlg == true) {

    	$tokUrl = './koukan_tokusun.php';

    	$post['koukanShinseiFlg'] = 1;

		$hiddenHtml = castHiddenError($post);

		// 特注入力画面に遷移
		redirectPost($tokUrl, $hiddenHtml);
	}
}

?>
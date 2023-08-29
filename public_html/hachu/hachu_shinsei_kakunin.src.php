<?php
/*
 * 発注申請確認画面
 * hachu_shinsei_kakunin.src.php
 *
 * create 2007/03/16 H.Osugi
 * update 2007/03/27 H.Osugi 発注変更処理追加
 * update 2007/03/30 H.Osugi 特寸処理追加
 *
 */

// 各種モジュールの取得
include_once('../../include/define.php');               // 定数定義
require_once('../../include/dbConnect.php');            // DB接続モジュール
require_once('../../include/msSqlControl.php');         // DB操作モジュール
require_once('../../include/checkLogin.php');           // ログイン判定モジュール
require_once('../../include/getSize.php');              // サイズ情報取得モジュール
require_once('../../include/checkData.php');            // 対象文字列検証モジュール
require_once('../../include/redirectPost.php');         // リダイレクトポストモジュール
require_once('../../include/checkDuplicateAppli.php');  // 申請番号重複判定モジュール
require_once('../../include/checkDuplicateStaff.php');  // スタッフコード重複判定モジュール
require_once('../../include/castHidden.php');           // hidden値成型モジュール
require_once('../../include/castHtmlEntity.php');       // HTMLエンティティモジュール
require_once('../../include/commonFunc.php');           // 共通関数モジュール
require_once('./hachu_shinsei.val.php');                // エラー判定モジュール
require_once('./hachu_tokusun.val.php');                // 特寸のエラー判定モジュール

// 初期設定
$isMenuOrder = true;                // 発注のメニューをアクティブに

// 変数の初期化 ここから *******************************************************
$requestNo = '';                    // 申請番号
$staffCode = '';                    // スタッフコード
$zip1      = '';                    // 郵便番号（前半3桁）
$zip2      = '';                    // 郵便番号（後半4桁）
$address   = '';                    // 住所
$shipName  = '';                    // 出荷先名
$staffName = '';                    // ご担当者
$tel       = '';                    // 電話番号
$yoteiDay  = '';                    // 出荷指定日
$memo      = '';                    // メモ

$isEmptyMemo  = true;               // メモが空かどうかを判定するフラグ

$haveTok      = false;              // 特寸から遷移してきたか判定フラグ

$high     = '';                     // 身長
$weight   = '';                     // 体重
$bust     = '';                     // バスト
$waist    = '';                     // ウエスト
$hips     = '';                     // ヒップ
$shoulder = '';                     // 肩幅
$sleeve   = '';                     // 袖丈
$length   = '';                     // スカート丈
$kitake   = '';                     // 着丈
$yukitake = '';                     // 裄丈
$inseam   = '';                     // 股下
$tokMemo  = '';                     // 特寸備考

$returnUrl = '';                    // 戻り先URL

// 変数の初期化 ここまで ******************************************************

// スタッフIDが取得できなければエラーに
if ((!isset($_POST['rirekiFlg']) || $_POST['rirekiFlg'] != 1) && 
//    (!isSetValue($_POST['staffId']) || !isSetValue($_POST['appliReason']))) {
    (!isSetValue($_POST['staffId']) || !isSetValue($_POST['appliReason']) || !isSetValue($_POST['searchPatternId']))) {
    // TOP画面に強制遷移
    redirectTop();
}

// 申請番号がすでに登録されていないか判定(変更時)
if (!isset($_POST['rirekiFlg']) || $_POST['rirekiFlg'] != 1) {
    checkDuplicateAppliNo($dbConnect, $_POST['requestNo'], 'hachu/hachu_shinsei.php', 1);
}

// エラー判定
validatePostData($dbConnect, $_POST);

// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST); 

//furukawa

// 表示する値の成型 ここから +++++++++++++++++++++++++++++++++++++++++++++++++++

// AppliReasonをセット
$appliReason = '';
if (isSetValue($post['appliReason'])) {
    $appliReason = $post['appliReason'];
}

if ($appliReason == APPLI_REASON_ORDER_PERSONAL) {  // 個別発注
    $isSyokai = false;  // 画面表示分岐用
} else {                                                    // 初回発注
    $isSyokai = true;  // 画面表示分岐用
}

$searchFukusyuID = '';
if (isSetValue($post['searchFukusyuID'])) {
    $searchFukusyuID = $post['searchFukusyuID'];
}
$searchGenderKbn = '';
if (isSetValue($post['searchGenderKbn'])) {
    $searchGenderKbn = $post['searchGenderKbn'];
}

if(isSetValue($post['searchPatternId'])) {
    $searchPatternId = $post['searchPatternId'];
    $searchPatternName = getStaffPattern($dbConnect, $searchPatternId);
}

// スタッフIDからヘッダー情報を取得
$headerInfo = getHeaderData($dbConnect, $post['staffId']);

// 新品/中古区分
$new_Item = false;
$newOldKbn = trim($post['newOldKbn']);
if($newOldKbn == 1){
	$new_Item = true;
}

// スタッフID
$staffId = '';
if (isSetValue($headerInfo['StaffSeqID'])) {
    $staffId = $headerInfo['StaffSeqID'];
} 

// スタッフコード
$staffCode = '';
if (isSetValue($headerInfo['StaffCode'])) {
    $staffCode = $headerInfo['StaffCode'];
} 

// 着用者名コード
$personName = '';
if (isSetValue($headerInfo['PersonName'])) {
    $personName = $headerInfo['PersonName'];
} 

// 店舗ID
$compId = '';
if (isSetValue($headerInfo['CompID'])) {
    $compId = $headerInfo['CompID'];
} 

// 店舗コード
$compCd = '';
if (isSetValue($headerInfo['CompCd'])) {
    $compCd = $headerInfo['CompCd'];
} 

// 店名
$compName = '';
if (isSetValue($headerInfo['CompName'])) {
    $compName = $headerInfo['CompName'];
} 

// 申請番号
$requestNo = trim($post['requestNo']);

// 郵便番号
$zip1 = trim($post['zip1']);
$zip2 = trim($post['zip2']);

// 住所
$address = trim($post['address']);

// 出荷先名
$shipName = trim($post['shipName']);

// ご担当者
$staffName = trim($post['staffName']);

// 電話番号
$tel = trim($post['tel']);

// 出荷指定日
$yoteiDay = trim($post['yoteiDay']);

// メモ
$memo = trim($post['memo']);

if ($memo != '' || $memo === 0) {
    $isEmptyMemo = false;
}

// 戻り先URL
$returnUrl = './hachu_shinsei.php';

if (isset($post['rirekiFlg']) && $post['rirekiFlg'] == 1) {
    $isMenuOrder   = false;
    $isMenuHistory = true;	// 申請履歴のメニューをアクティブに
    $haveRirekiFlg = true;	// 発注申請か発注変更かを判定するフラグ
}

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

    // スカート丈
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
    $returnUrl = './hachu_tokusun.php';

}

// 表示するアイテム情報を取得
$displayData = getDispItemKakunin($dbConnect, $post);

if (isset($_POST['tokFlg']) && $_POST['tokFlg'] == 1) {
    // 特寸エラー判定
    validateTokData($_POST);
} else {
    // 特寸判定
    checkTok($dbConnect, $displayData, $post);
}

$GoukeiKingaku = 0;
for ($i=0; $i<sizeof($displayData); $i++) {
	$GoukeiKingaku += $displayData[$i]['dispPrice'];
	$displayData[$i]['dispPrice'] = number_format($displayData[$i]['dispPrice']);
}
$GoukeiKingaku = number_format($GoukeiKingaku);

// hidden値の成型
$countItemIds = count($post['itemIds']);
for ($i=0; $i<$countItemIds; $i++) {
    $post['itemIds[' . $i . ']'] = $post['itemIds'][$i];
    $post['itemNumber[' . $post['itemIds'][$i] . ']'] = trim($post['itemNumber'][$post['itemIds'][$i]]);
    $post['groupId[' . $post['itemIds'][$i] . ']'] = trim($post['groupId'][$post['itemIds'][$i]]);
    if (isset($post['limitNum'][$post['itemIds'][$i]])) { 
        $post['limitNum[' . $post['itemIds'][$i] . ']'] = trim($post['limitNum'][$post['itemIds'][$i]]);
    }
}
$notArrowKeys = array('itemIds' , 'size', 'sizeType', 'itemNumber', 'groupId', 'limitNum');
$hiddenHtml = castHidden($post, $notArrowKeys);


    
// 表示する値の成型 ここまで ++++++++++++++++++++++++++++++++++++++++++++++++++

/*
 * 特寸が選択されているか判定
 * 引数  ：$post       => POST値
 *       ：$countSize1 => サイズ1の件数
 *       ：$countSize2 => サイズ2の件数
 * 戻り値：なし
 *
 * create 2007/03/30 H.Osugi
 *
 */
//function checkTok($dbConnect, $post) {
function checkTok($dbConnect, $items, $post) {

//    // サイズの配列を取得
//    $sizeAry = getValidateSizeData2($dbConnect, $post);
//
//    if (is_array($sizeAry)) {
//        foreach ($sizeAry as $key => $val){
//            if (isSetValue($post[$val['name']])) {
//                // 個数が０以上であれば、フラグ設定する
//                $itemId = substr($val['name'],4);
//                if (($post['itemNumber'][$itemId] != '') && $post['itemNumber'][$itemId] > 0) { 
//        
////var_dump($post);
//
////    			if (strpos($staffData[$i]['item'][$k]['size'], "特") !== false)
//                    $maxKey = count($val['validAry']);
//                    // 特寸が選択されていた場合
////                    if ( ($post[$val['name']] == $val['validAry'][$maxKey-1]) && ($maxKey > 1) ) {
//                    if ( ($post[$val['name']] == $val['validAry'][$maxKey-1])) {
////var_dump("aada:;" . $maxKey);die;
//
//                        $post['hachuShinseiFlg'] = true;
//                        
//                        $tokUrl = './hachu_tokusun.php';
//            
//                        $hiddenHtml = castHiddenError($post);
//            
//                        // 特寸入力画面に遷移
//                        redirectPost($tokUrl, $hiddenHtml);
//                    }
//                }
//            }
//        }
//    }]

	// ==============================================================
	// 対象の申請に特注アイテムが含まれているか確認
	// ==============================================================
	$isTokFlg = false;
	for ($i=0; $i<count($items); $i++) {
		if(isset($items[$i]['sizeData']) && (strpos($items[$i]['sizeData'], "特") !== false) && $items[$i]['sizeData'] != '') {
		 	$isTokFlg = true;
		}
	}

	if ($isTokFlg == true) {
		$post['hachuShinseiFlg'] = true;

		$tokUrl = './hachu_tokusun.php';

		$hiddenHtml = castHiddenError($post);

		// 特注入力画面に遷移
		redirectPost($tokUrl, $hiddenHtml);
	}


}

/*
 * 機能  ：エラー判定用のサイズデータ取得
 * 引数  ：$dbConnect  => コネクションハンドラ
 *       ：$post       => POSTデータ
 * 戻り値：なし
 *
 * create 2008/04/15 W.Takasaki
 *
 */
function getValidateSizeData2($dbConnect, $post)
{
    // 初期化
    $returnAry = array();

    // サイズの項目名を取得
    $sql = "";
    $sql .= " SELECT";
    $sql .=     " I.ItemID";
    $sql .=    " ,I.SizeID";
    $sql .= " FROM";
    $sql .=    " M_Item I";
    $sql .= " WHERE";
    $sql .=     " I.Del = " . DELETE_OFF;

    $result = db_Read($dbConnect, $sql);

    // 検索結果が配列ではない場合
    if (!is_array($result) || count($result) <= 0) {
        return false;
    }

    foreach ($result as $key => $val) {    
        // 初期化
        $returnAry[$key]['validAry'] = array();

        $returnAry[$key]['name'] = 'size'.$val['ItemID'];

        $returnAry[$key]['validAry'] = array_keys(getSize($dbConnect, $val['SizeID'], 0));
    }

    return $returnAry;
}


/*
 * 表示するアイテム情報を取得する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$post      => POST値
 * 戻り値：$result    => 表示する商品一覧情報
 *
 * create 2008/04/15 W.Takasaki
 *
 */
function getDispItemKakunin($dbConnect, $post)
{
    $itemIds = '';
    if(is_array($post['itemIds'])) {
        $itemIds = implode(', ', $post['itemIds']);
    }

    $returnData = array();
    
    // 商品情報を取得する
    $sql  = "";
    $sql .= " SELECT";
    $sql .=     " mi.ItemID,";
    $sql .=     " mi.ItemNo,";
    $sql .=     " mi.ItemName,";
    $sql .=     " mi.Price,";
    $sql .=     " mi.SizeID";
    $sql .= " FROM";
    $sql .=     " M_Item mi";
    $sql .= " WHERE";
    $sql .=     " mi.ItemID IN (" . db_Escape($itemIds) . ")";
    $sql .= " AND";
    $sql .=     " mi.Del = " . DELETE_OFF;
    $sql .= " ORDER BY";
    //$sql .=     " mi.ItemID ASC";
    $sql .=     " mi.DispFlg ASC,  mi.ItemID ASC";

    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合
    if (count($result) <= 0) {
        $result = array();
        return $result;
    }

    // 取得した値をHTMLエンティティ
    $rowCnt = 0;
    foreach ($result as $key => $val) {    
        if (isset($post['itemNumber'][$val['ItemID']]) && $post['itemNumber'][$val['ItemID']] != '' && $post['itemNumber'][$val['ItemID']] > 0) {
            $returnData[$rowCnt]['itemId'] = $val['ItemID'];
            $returnData[$rowCnt]['dispName'] = $val['ItemName'];
            $returnData[$rowCnt]['count'] = $rowCnt+1;
    
            $returnData[$rowCnt]['dispNum'] = $post['itemNumber'][$val['ItemID']];
            $returnData[$rowCnt]['dispPrice'] = $val['Price'] * $returnData[$rowCnt]['dispNum'];
    
            // アイテムごとのサイズを取得
            if (isset($val['SizeID']) && $val['SizeID'] != '' && $returnData[$rowCnt]['dispNum'] > 0) {
                $sizeAry = getSize($dbConnect, $val['SizeID'], 1);
                $returnData[$rowCnt]['sizeData'] = $sizeAry[$post['size'.$val['ItemID']]];
            }
    
            $rowCnt++;
        }
    }

    return  $returnData;
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
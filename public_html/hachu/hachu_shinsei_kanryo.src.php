<?php
/*
 * 発注申請発注結果画面
 * hachu_shinsei_kanryo.src.php
 *
 * create 2007/03/16 H.Osugi
 * update 2007/03/26 H.Osugi    発注変更処理を追加
 * update 2007/03/30 H.Osugi    特寸処理を追加
 * update 2008/04/16 W.Takasaki 初回発注と個別発注を統合
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
require_once('../../include/createHachuMail.php');      // 発注申請メール生成モジュール
require_once('../../include/sendTextMail.php');         // テキストメール送信モジュール
require_once('../../include/commonFunc.php');           // 共通関数モジュール
require_once('./hachu_shinsei.val.php');                // エラー判定モジュール

//var_dump( $_POST);die;

// 初期設定
$isMenuOrder = true;    // 発注のメニューをアクティブに

// 変数の初期化 ここから ******************************************************
$requestNo = '';                    // 申請番号
$staffCode = '';					// スタッフコード
$zip1      = '';					// 郵便番号（前半3桁）
$zip2      = '';					// 郵便番号（後半4桁）
$address   = '';					// 住所
$shipName  = '';					// 出荷先名
$staffName = '';					// ご担当者
$tel       = '';					// 電話番号
$yoteiDay  = '';					// 出荷指定日
$memo      = '';					// メモ

$isEmptyMemo  = true;				// メモが空かどうかを判定するフラグ

$dispRentalStartDay = false;        // レンタル終了日表示設定

$haveTok      = false;				// 特寸から遷移してきたか判定フラグ

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

// 変数の初期化 ここまで ******************************************************

// 発注訂正の場合
if (!isset($_POST['rirekiFlg']) || $_POST['rirekiFlg'] != 1) {

    // スタッフIDが取得できなければエラーに
    if (!isSetValue($_POST['staffId']) || !isSetValue($_POST['appliReason']) || !(int)$_POST['staffId'] || !(int)$_POST['appliReason'] || !(int)$_POST['searchPatternId']) {
        // TOP画面に強制遷移
        redirectTop();
    }

    // 申請番号がすでに登録されていないか判定
    checkDuplicateAppliNo($dbConnect, $_POST['requestNo'], 'hachu/hachu_shinsei.php', 1);
}

// レンタル終了日表示設定
//if ($isLevelAdmin) {
//    $dispRentalStartDay = true;
//}

// エラー判定
validatePostData($dbConnect, $_POST);

if ($_POST['appliReason'] == APPLI_REASON_ORDER_PERSONAL) {  // 個別発注
    $isSyokai = false;  // 画面表示分岐用
    $dispRentalStartDay = false;
} else {                                                    // 初回発注
    $isSyokai = true;  // 画面表示分岐用
    $dispRentalStartDay = true;
}

// スタッフ(着用者）情報を取得
$staffData = getHeaderData($dbConnect, $_POST['staffId']);

// トランザクション開始
db_Transaction_Begin($dbConnect);

$haveRirekiFlg = false;
if (isset($_POST['rirekiFlg']) && $_POST['rirekiFlg'] == 1) {   // 発注訂正

	// 発注訂正情報をDB更新
	$isSuccess = updateOrder($dbConnect, $_POST, $staffData, $dispRentalStartDay);
	
	$orderId = trim($_POST['orderId']);

	$isMenuOrder   = false;
	$isMenuHistory = true;	// 申請履歴のメニューをアクティブに
	$haveRirekiFlg = true;

} else {    // 新規発注
	// 発注申請情報をDBに登録
	$isSuccess = createOrder($dbConnect, $_POST, $staffData, $orderId, $dispRentalStartDay);
}

// 登録が失敗した場合はエラー画面へ遷移
if (!$isSuccess) {

	// ロールバック
	db_Transaction_Rollback($dbConnect);
//die('out');
	$hiddens['errorName'] = 'hachuShinsei';
	$hiddens['menuName']  = 'isMenuOrder';
	$hiddens['returnUrl'] = 'hachu/hachu_top.php';
	$hiddens['errorId'][] = '901';
	$errorUrl             = HOME_URL . 'error.php';

	// エラー画面に強制遷移
	redirectPost($errorUrl, $hiddens);

}

// コミット
db_Transaction_Commit($dbConnect);

// 発注申請メール送信
//$isSuccess = sendMailShinsei($dbConnect, $_POST, $staffData, $orderId);
$isSuccess = sendMailShinsei($dbConnect, $_POST, $orderId);

// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST); 

// 表示する値の成型 ここから +++++++++++++++++++++++++++++++++++++++++++++++++++

if(isSetValue($post['searchPatternId'])) {
    $searchPatternId = $post['searchPatternId'];
    $searchPatternName = getStaffPattern($dbConnect, $searchPatternId);
}

// スタッフID
$staffId = '';
if (isSetValue($post['staffId'])) {
    $staffId = $post['staffId'];
} 

// 新品/中古区分
$new_Item = false;
$newOldKbn = trim($post['newOldKbn']);
if($newOldKbn == 1){
	$new_Item = true;
}

// スタッフコード
$staffCode = '';
if (isSetValue($staffData['StaffCode'])) {
    $staffCode = $staffData['StaffCode'];
} 

// 着用者名コード
$personName = '';
if (isSetValue($staffData['PersonName'])) {
    $personName = $staffData['PersonName'];
} 

// 店舗ID
$compId = '';
if (isSetValue($staffData['CompID'])) {
    $compId = $staffData['CompID'];
} 

// 店舗コード
$compCd = '';
if (isSetValue($staffData['CompCd'])) {
    $compCd = $staffData['CompCd'];
} 

// 店名
$compName = '';
if (isSetValue($staffData['CompName'])) {
    $compName = $staffData['CompName'];
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
$staffName  = trim($post['staffName']);

// 電話番号
$tel = trim($post['tel']);

// 出荷指定日
$yoteiDay = trim($post['yoteiDay']);

// メモ
$memo = trim($post['memo']);

if ($memo != '' || $memo === 0) {
    $isEmptyMemo = false;
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
    $yukitake   = trim($post['yukitake']);

    // 股下
    $inseam   = trim($post['inseam']);

	// 特寸備考
	$tokMemo  = trim($post['tokMemo']);

}

// 表示するアイテム情報を取得
$displayData = getDispItem($dbConnect, $post);

$GoukeiKingaku = 0;
for ($i=0; $i<sizeof($displayData); $i++) {
	$GoukeiKingaku += $displayData[$i]['dispPrice'];
	$displayData[$i]['dispPrice'] = number_format($displayData[$i]['dispPrice']);
}
$GoukeiKingaku = number_format($GoukeiKingaku);

// 表示する値の成型 ここまで ++++++++++++++++++++++++++++++++++++++++++++++++++

/*
 * 発注申請情報を登録する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$post      => POST値
 *       ：$staffData => 着用者情報
 *       ：$orderId   => OrderID
 *       ：$dispRentalStartDay => レンタル開始日表示設定
 * 戻り値：true：登録成功 / false：登録失敗
 *
 * create 2007/03/16 H.Osugi
 * update 2008/04/16 W.Takasaki
 *
 */
function createOrder($dbConnect, $post, $staffData, &$orderId, $dispRentalStartDay) {

	// 郵便番号を成型する
	$zip = trim($post['zip1']) . '-' . trim($post['zip2']);

	// T_Orderに登録する
	$sql  = "";
	$sql .= " INSERT INTO";
	$sql .= 	" T_Order";
	$sql .= 		" (";
	$sql .= 		" AppliDay,";
	$sql .= 		" AppliNo,";
	$sql .= 		" AppliUserID,";
	$sql .= 		" AppliCompCd,";
	$sql .= 		" AppliCompName,";
	$sql .= 		" AppliMode,";
	$sql .= 		" AppliReason,";
	$sql .= 		" AppliPattern,";
	$sql .= 		" CompID,";
    $sql .=         " StaffID,";
	$sql .= 		" StaffCode,";
	$sql .= 		" PersonName,";
	$sql .= 		" Zip,";
	$sql .= 		" Adrr,";
	$sql .= 		" Tel,";
	$sql .= 		" ShipName,";
	$sql .= 		" TantoName,";
	$sql .= 		" Note,";
	$sql .= 		" Status,";
	$sql .= 		" Tok,";
	$sql .= 		" TokNote,";
	$sql .= 		" YoteiDay,";
	$sql .= 		" NewOldKbn,";
	$sql .= 		" Del,";
	$sql .= 		" RegistDay,";
	$sql .= 		" RegistUser";
	$sql .= 		" )";
	$sql .= " VALUES";
	$sql .= 		" (";
	$sql .= 		" GETDATE(),";												// AppliDay
	$sql .= 		" '" . db_Escape(trim($post['requestNo'])) ."',";			// AppliNo
	$sql .= 		" '" . db_Escape(trim($_SESSION['USERID'])) . "',";			// AppliUserID
	$sql .= 		" '" . db_Escape(trim($staffData['CompCd'])) . "',";		// AppliCompCd
	$sql .= 		" '" . db_Escape(trim($staffData['CompName'])) . "',";		// AppliCompName
	$sql .= 		" " . APPLI_MODE_ORDER . ",";								// AppliMode (発注:1)
	$sql .= 		" '" . db_Escape(trim($post['appliReason'])) . "',";		// AppliReason
	$sql .= 		" '" . db_Escape(trim($post['searchPatternId'])) . "',";	// AppliPattern
	$sql .= 		" '" . db_Escape(trim($staffData['CompID'])) . "',";		// CompID
    $sql .=         " '" . db_Escape(trim($post['staffId'])) . "',";			// StaffID
	$sql .= 		" '" . db_Escape(trim($staffData['StaffCode'])) . "',";		// StaffCode
    $sql .=         " '" . db_Escape(trim($staffData['PersonName'])) . "',";	// PersonName
	$sql .= 		" '" . db_Escape(trim($zip)) . "',";						// Zip
	$sql .= 		" '" . db_Escape(trim($post['address'])) . "',";			// Adrr
	$sql .= 		" '" . db_Escape(trim($post['tel'])) . "',";				// Tel
	$sql .= 		" '" . db_Escape(trim($post['shipName'])) . "',";			// ShipName
	$sql .= 		" '" . db_Escape(trim($post['staffName'])) . "',";			// TantoName
	$sql .= 		" '" . db_Escape(trim($post['memo'])) . "',";				// Note
	$sql .= 		" " . STATUS_APPLI . ",";									// Status (承認待:1)
	// 特寸フラグが有効な場合
	if (isset($post['tokFlg']) && $post['tokFlg'] == 1) {
		$sql .= 		" 1,";													// Tok
		$sql .= 		" '" . db_Escape(trim($post['tokMemo'])) . "',";		// TokNote
	} else {
		$sql .= 		" 0,";													// Tok
		$sql .= 		" NULL,";												// TokNote
	}
	// 出荷予定日が入力されている場合
	if (isset($post['yoteiDay']) && $post['yoteiDay'] != '') {
		$sql .=			" '" . db_Escape(trim($post['yoteiDay'])) . "',";		// YoteiDay
	} else {
		$sql .= 		" NULL,";												// YoteiDay
	}
	$sql .= 		" '" . db_Escape(trim($post['newOldKbn'])) . "',";			// NewOldKbn
	$sql .= 		" " . DELETE_OFF . ",";										// Del
	$sql .= 		" GETDATE(),";												// RegistDay
	$sql .= 		" '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";		// RegistUser
	$sql .= 		" )";
//var_dump($sql);

	$isSuccess = db_Execute($dbConnect, $sql);
//var_dump("isSuccess" . $isSuccess);die;
	// 実行結果が失敗の場合
	if ($isSuccess == false) {
		return false;
	}

	// 直近のシーケンスIDを取得
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" SCOPE_IDENTITY() as scope_identity";

	$result = db_Read($dbConnect, $sql);

	// 実行結果が失敗の場合
	if ($result == false || !isset($result[0]['scope_identity']) || $result[0]['scope_identity'] == '') {
		return false;
	}

	$orderId = $result[0]['scope_identity'];

	// 特寸情報を登録
	if (isset($post['tokFlg']) && $post['tokFlg'] == 1) {

		// T_Tokに登録する
		$sql  = "";
		$sql .= " INSERT INTO";
		$sql .= 	" T_Tok";
		$sql .= 		" (";
		$sql .= 		" OrderID,";
		$sql .= 		" Height,";
		$sql .= 		" Weight,";
		$sql .= 		" Bust,";
		$sql .= 		" Waist,";
		$sql .= 		" Hips,";
		$sql .= 		" Shoulder,";
		$sql .= 		" Sleeve,";
		$sql .= 		" Length,";
        $sql .=         " Kitake,";
        $sql .=         " YuKitake,";
        $sql .=         " Inseam,";
		$sql .= 		" Del,";
		$sql .= 		" RegistDay,";
		$sql .= 		" RegistUser";
		$sql .= 		" )";
		$sql .= " VALUES";
		$sql .= 		" (";
		$sql .= 		" '" . db_Escape(trim($orderId)) . "',";
		$sql .= 		" '" . db_Escape(trim($post['high'])) . "',";
		$sql .= 		" '" . db_Escape(trim($post['weight'])) . "',";
		$sql .= 		" '" . db_Escape(trim($post['bust'])) . "',";
		$sql .= 		" '" . db_Escape(trim($post['waist'])) . "',";
		$sql .= 		" '" . db_Escape(trim($post['hips'])) . "',";
		$sql .= 		" '" . db_Escape(trim($post['shoulder'])) . "',";
		$sql .= 		" '" . db_Escape(trim($post['sleeve'])) . "',";
		$sql .= 		" '" . db_Escape(trim($post['length'])) . "',";
        $sql .=         " '" . db_Escape(trim($post['kitake'])) . "',";
        $sql .=         " '" . db_Escape(trim($post['yukitake'])) . "',";
        $sql .=         " '" . db_Escape(trim($post['inseam'])) . "',";
		$sql .= 		" " . DELETE_OFF . ",";
		$sql .= 		" GETDATE(),";
		$sql .= 		" '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= 		" )";
		
		$isSuccess = db_Execute($dbConnect, $sql);
	
		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}
	}

	// T_Staffの情報を取得
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" COUNT(StaffID) as countID";
	$sql .= " FROM";
	$sql .= 	" T_Staff";
	$sql .= " WHERE";
    $sql .=     " StaffID = '" . db_Escape(trim($post['staffId'])) . "'";
    $sql .= " AND";
	$sql .= 	" StaffCode = '" . db_Escape(trim($staffData['StaffCode'])) . "'";
	$sql .= " AND";

	$sql .= 	" CompID = '" . db_Escape(trim($staffData['CompID'])) . "'";

	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;

	$result = db_Read($dbConnect, $sql);

	$count = 0;
	if (isset($result[0]['countID'])) {
		$count = $result[0]['countID'];
	}

	// スタッフがまだ登録されていない場合
	if ($count == 0) {

		// T_Staffに登録する
		$sql  = "";
		$sql .= " INSERT INTO";
		$sql .= 	" T_Staff";
		$sql .= 		" (";
        $sql .=         " StaffID,";
		$sql .= 		" CompID,";
		$sql .= 		" StaffCode,";
		// パターンＩＤを記録 Y.Furukawa 2017/05/02
		$sql .= 		" PatternID,";
		$sql .= 		" WithdrawalFlag,";
		$sql .= 		" AllReturnFlag,";
		$sql .= 		" Del,";
		$sql .= 		" RegistDay,";
		$sql .= 		" RegistUser";
		$sql .= 		" )";
		$sql .= " VALUES";
		$sql .= 		" (";
        $sql .=         " '" . db_Escape(trim($post['staffId'])) . "',";

		$sql .= 		" '" . db_Escape(trim($staffData['CompID'])) . "',";

		$sql .= 		" '" . db_Escape(trim($staffData['StaffCode'])) . "',";

		// パターンＩＤを記録 Y.Furukawa 2017/05/02
		$sql .= 		" '" . db_Escape(trim($post['searchPatternId'])) . "',";

		$sql .= 		" 0,";	// WithdrawalFlagの初期値は0
		$sql .= 		" 0,";	// AllReturnFlagの初期値は0
		$sql .= 		" " . DELETE_OFF . ",";
		$sql .= 		" GETDATE(),";
		$sql .= 		" '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= 		" )";
//var_dump($sql);
		$isSuccess = db_Execute($dbConnect, $sql);
//var_dump($isSuccess);die;
		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}

	} else {
		// （スタッフが存在する場合）パターンＩＤを記録 Y.Furukawa 2017/05/02
	    $sql  = "";
	    $sql .= " UPDATE";
	    $sql .=     " T_Staff";
	    $sql .= " SET";
	    $sql .=     " PatternID = '" . db_Escape(trim($post['searchPatternId'])) . "',";
	    $sql .=     " UpdDay = GETDATE(),";
	    $sql .=     " UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= " WHERE";
	    $sql .=     " StaffID = '" . db_Escape(trim($post['staffId'])) . "'";
	    $sql .= " AND";
		$sql .= 	" StaffCode = '" . db_Escape(trim($staffData['StaffCode'])) . "'";
		$sql .= " AND";
		$sql .= 	" CompID = '" . db_Escape(trim($staffData['CompID'])) . "'";
		$sql .= " AND";
		$sql .= 	" Del = " . DELETE_OFF;

		$isSuccess = db_Execute($dbConnect, $sql);

		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}
	}

	// T_Order_Detailsに登録するための情報をセットここから +++++++++++++++++++++++++++++++

    $orderDetails = array();

    $itemIds = '';
    if(is_array($post['itemIds'])) {
        $itemIds = implode(', ', $post['itemIds']);
    }

    // 発注申請の一覧を取得する
    $sql  = "";
    $sql .= " SELECT";
    $sql .=     " mi.ItemID,";
    $sql .=     " mi.ItemNo,";
    $sql .=     " mi.ItemName,";
    $sql .=     " mi.SizeID";
    $sql .= " FROM";
    $sql .=     " M_Item mi";
    $sql .= " WHERE";
    $sql .=     " mi.Del = " . DELETE_OFF;
    $sql .= " AND";
    $sql .=     " mi.ItemID IN (" . db_Escape($itemIds) . ")";
    $sql .= " ORDER BY";
    $sql .=     " mi.ItemID ASC";

    $result = db_Read($dbConnect, $sql);
    // 検索結果が0件の場合
    if (count($result) <= 0) {
        $result = array();
        return $result;
    }

    // 取得した値をHTMLエンティティ
    $rowCnt = 0;
    foreach ($result as $key => $val) {
        if ($post['itemNumber'][$val['ItemID']] != '' && $post['itemNumber'][$val['ItemID']] > 0) {    
            $orderDetails[$rowCnt]['itemId'] = $val['ItemID'];
            $orderDetails[$rowCnt]['itemNo'] = $val['ItemNo'];
            $orderDetails[$rowCnt]['itemName'] = $val['ItemName'];
            $orderDetails[$rowCnt]['num'] = $post['itemNumber'][$val['ItemID']];
    
            // アイテムごとのサイズを取得
            $sizeAry = getSize($dbConnect, $val['SizeID'], 1);
            $orderDetails[$rowCnt]['size'] = $sizeAry[$post['size'.$val['ItemID']]];

            $rowCnt++;
        }
    }
    
	// T_Order_Detailsに登録するための情報をセット ここまで ++++++++++++++++++++++++++++++

	// T_Order_Detailsの登録
	$countOrderDetail = count($orderDetails);
    $line = 1;  // 行番号
	for ($i=0; $i<$countOrderDetail; $i++) {

		// 初期化
		$orderDetailId = '';

		// ストック情報を取得
		$sql  = "";
		$sql .= " SELECT";
		$sql .= 	" StockCD";
		$sql .= " FROM";
		$sql .= 	" M_StockCtrl";
		$sql .= " WHERE";
		$sql .= 	" ItemNo = '" . db_Escape($orderDetails[$i]['itemNo']) . "'";
		$sql .= " AND";
		$sql .= 	" Size = '" . db_Escape($orderDetails[$i]['size']) . "'";
		$sql .= " AND";
		$sql .= 	" Del = " . DELETE_OFF;

		$stockDatas = db_Read($dbConnect, $sql);

		// 実行結果が失敗の場合
		if ($stockDatas == false || count($stockDatas) <= 0) {
			return false;
		}
        $stockCD = $stockDatas[0]['StockCD'];

        // 各アイテムの数量分繰り返す
        for ($t = 1;$t<=$orderDetails[$i]['num'];$t++) {
    		// T_Order_Detailsの登録
    		$sql  = "";
    		$sql .= " INSERT INTO";
    		$sql .= 	" T_Order_Details";
    		$sql .= 		" (";
    		$sql .= 		" OrderID,";
    		$sql .= 		" AppliNo,";
    		$sql .= 		" AppliLNo,";
    		$sql .= 		" ItemID,";
    		$sql .= 		" ItemNo,";
    		$sql .= 		" ItemName,";
    		$sql .= 		" Size,";
    		$sql .= 		" StockCd,";
    		$sql .= 		" Status,";
    		$sql .= 		" AppliDay,";
    		$sql .= 		" Del,";
    		$sql .= 		" RegistDay,";
    		$sql .= 		" RegistUser";
    		$sql .= 		" )";
    		$sql .= " VALUES";
    		$sql .= 		" (";
    		$sql .= 		" '" . db_Escape($orderId) ."',";
    		$sql .= 		" '" . db_Escape(trim($post['requestNo'])) ."',";
    		$sql .= 		" '" . db_Escape($line) ."',";
    		$sql .= 		" '" . db_Escape(trim($orderDetails[$i]['itemId'])) ."',";
    		$sql .= 		" '" . db_Escape(trim($orderDetails[$i]['itemNo'])) ."',";
    		$sql .= 		" '" . db_Escape(trim($orderDetails[$i]['itemName'])) ."',";
    		$sql .= 		" '" . db_Escape(trim($orderDetails[$i]['size'])) ."',";
    		$sql .= 		" '" . db_Escape(trim($stockCD)) ."',";
    		$sql .= 		" " . STATUS_APPLI . ",";		// 申請済（承認待ち）は1
//            $sql .=         " " . STATUS_APPLI_ADMIT . ",";       // 承認機能がないため承認済をsセット
    		$sql .= 		" GETDATE(),";
    		$sql .= 		" " . DELETE_OFF . ",";
    		$sql .= 		" GETDATE(),";
    		$sql .= 		" '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
    		$sql .= 		" );";
//var_dump($sql);
    		$isSuccess = db_Execute($dbConnect, $sql);
//var_dump($isSuccess);die;
    		// 実行結果が失敗の場合
    		if ($isSuccess == false) {
    			return false;
    		}
    	
    		// 直近のシーケンスIDを取得
    		$sql  = "";
    		$sql .= " SELECT";
    		$sql .= 	" SCOPE_IDENTITY() as scope_identity;";
    		
    		$result = db_Read($dbConnect, $sql);
    	
    		// 実行結果が失敗の場合
    		if ($result == false || !isset($result[0]['scope_identity']) || $result[0]['scope_identity'] == '') {
    			return false;
    		}
    	
    		$orderDetailId = $result[0]['scope_identity'];
    
    		// T_Staff_Detailsの登録
    		$sql  = "";
    		$sql .= " INSERT INTO";
    		$sql .= 	" T_Staff_Details";
    		$sql .= 		" (";
    		$sql .= 		" StaffID,";
    		$sql .= 		" OrderDetID,";
    		$sql .= 		" Status,";
    		$sql .= 		" ReturnFlag,";
    		$sql .= 		" Del,";
    		$sql .= 		" RegistDay,";
    		$sql .= 		" RegistUser";
    		$sql .= 		" )";
    		$sql .= " VALUES";
    		$sql .= 		" (";
    		$sql .= 		" '" . db_Escape($post['staffId']) ."',";
    		$sql .= 		" '" . db_Escape($orderDetailId) ."',";
			$sql .=         " " . STATUS_APPLI . ",";       // 申請済（承認待ち）は1
//            $sql .=         " " . STATUS_APPLI_ADMIT . ",";       // 承認機能がないため承認済をsセット
    		$sql .= 		" 0,";							// ReturnFlagの初期値は0
    		$sql .= 		" " . DELETE_OFF . ",";
    		$sql .= 		" GETDATE(),";
    		$sql .= 		" '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
    		$sql .= 		" );";
    
    		$isSuccess = db_Execute($dbConnect, $sql);
    	
    		// 実行結果が失敗の場合
    		if ($isSuccess == false) {
    			return false;
    		}
    
            $line++;  // 行番号
        }
	}

	return true;

}

/*
 * 発注申請情報を変更する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$post      => POST値
 *       ：$staffData => 着用者情報
 *       ：$dispRentalStartDay => レンタル開始日表示設定
 * 戻り値：true：変更成功 / false：変更失敗
 *
 * create 2007/03/26 H.Osugi
 *
 */
function updateOrder($dbConnect, $post, $staffData, $dispRentalStartDay) {

	$orderId = trim($post['orderId']);

	// 郵便番号を成型する
	$zip = trim($post['zip1']) . '-' . trim($post['zip2']);

	// T_Orderを変更する
	$sql  = "";
	$sql .= " UPDATE";
	$sql .= 	" T_Order";
	$sql .= " SET";
	$sql .= 	" AppliDay = GETDATE(),";
	$sql .= 	" AppliUserID = '" . db_Escape(trim($_SESSION['USERID'])) . "',";
	$sql .= 	" AppliCompCd = '" . db_Escape(trim($staffData['CompCd'])) . "',";
	$sql .= 	" AppliCompName = '" . db_Escape(trim($staffData['CompName'])) . "',";
	$sql .= 	" AppliMode = " . APPLI_MODE_ORDER . ",";								// 発注:1
	$sql .= 	" AppliReason = " . $post['appliReason'] . ",";
	$sql .= 	" AppliPattern = '" . db_Escape(trim($post['searchPatternId'])) . "',";
	$sql .= 	" CompID = '" . db_Escape(trim($staffData['CompID'])) . "',";
	$sql .= 	" StaffCode = '" . db_Escape(trim($staffData['StaffCode'])) . "',";
	$sql .= 	" Zip = '" . db_Escape(trim($zip)) . "',";
	$sql .= 	" Adrr = '" . db_Escape(trim($post['address'])) . "',";
	$sql .= 	" Tel = '" . db_Escape(trim($post['tel'])) . "',";
	$sql .= 	" ShipName = '" . db_Escape(trim($post['shipName'])) . "',";
	$sql .= 	" TantoName = '" . db_Escape(trim($post['staffName'])) . "',";
	$sql .= 	" Note = '" . db_Escape(trim($post['memo'])) . "',";
	if (isset($post['yoteiDay']) && $post['yoteiDay'] != '') {
		$sql .=		" YoteiDay = '" . db_Escape(trim($post['yoteiDay'])) . "',";
	} else {
		$sql .= 	" YoteiDay = NULL,";
	}
	$sql .= 	" NewOldKbn = '" . db_Escape(trim($post['newOldKbn'])) . "',";
	$sql .= 	" Status = " . STATUS_APPLI . ",";										// 承認待:1

	// 特寸フラグが有効な場合
	if (isset($post['tokFlg']) && $post['tokFlg'] == 1) {
		$sql .= 	" Tok = 1,";
		$sql .= 	" TokNote = '" . db_Escape(trim($post['tokMemo'])) . "',";
	}
	else {
		$sql .= 	" Tok = 0,";
		$sql .= 	" TokNote = NULL,";
	}

	$sql .= 	" UpdDay = GETDATE(),";
	$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape(trim($orderId)) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;

	$isSuccess = db_Execute($dbConnect, $sql);

	// 実行結果が失敗の場合
	if ($isSuccess == false) {
		return false;
	}

	// 特寸情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" TokID";
	$sql .= " FROM";
	$sql .= 	" T_Tok";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;

	$result = db_Read($dbConnect, $sql);

	$tokId = '';
	if (isset($result[0]['TokID'])) {
		$tokId = $result[0]['TokID'];
	}

	// 特寸情報を登録
	if ($tokId == '' && isset($post['tokFlg']) && $post['tokFlg'] == 1) {

		// T_Tokに登録する
		$sql  = "";
		$sql .= " INSERT INTO";
		$sql .= 	" T_Tok";
		$sql .= 		" (";
		$sql .= 		" OrderID,";
		$sql .= 		" Height,";
		$sql .= 		" Weight,";
		$sql .= 		" Bust,";
		$sql .= 		" Waist,";
		$sql .= 		" Hips,";
		$sql .= 		" Shoulder,";
		$sql .= 		" Sleeve,";
		$sql .= 		" Length,";
        $sql .=         " Kitake,";
        $sql .=         " YuKitake,";
        $sql .=         " Inseam,";
		$sql .= 		" Del,";
		$sql .= 		" RegistDay,";
		$sql .= 		" RegistUser";
		$sql .= 		" )";
		$sql .= " VALUES";
		$sql .= 		" (";
		$sql .= 		" '" . db_Escape(trim($orderId)) . "',";
		$sql .= 		" '" . db_Escape(trim($post['high'])) . "',";
		$sql .= 		" '" . db_Escape(trim($post['weight'])) . "',";
		$sql .= 		" '" . db_Escape(trim($post['bust'])) . "',";
		$sql .= 		" '" . db_Escape(trim($post['waist'])) . "',";
		$sql .= 		" '" . db_Escape(trim($post['hips'])) . "',";
		$sql .= 		" '" . db_Escape(trim($post['shoulder'])) . "',";
		$sql .= 		" '" . db_Escape(trim($post['sleeve'])) . "',";
		$sql .= 		" '" . db_Escape(trim($post['length'])) . "',";
        $sql .=         " '" . db_Escape(trim($post['kitake'])) . "',";
        $sql .=         " '" . db_Escape(trim($post['yukitake'])) . "',";
        $sql .=         " '" . db_Escape(trim($post['inseam'])) . "',";
		$sql .= 		" " . DELETE_OFF . ",";
		$sql .= 		" GETDATE(),";
		$sql .= 		" '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= 		" )";
		
		$isSuccess = db_Execute($dbConnect, $sql);
	
		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}

	}

	// 特寸情報を変更
	elseif ($tokId != '' && isset($post['tokFlg']) && $post['tokFlg'] == 1) {

		// T_Tokを変更する
		$sql  = "";
		$sql .= " UPDATE";
		$sql .= 	" T_Tok";
		$sql .= " SET";
		$sql .= 	" Height = '" . db_Escape(trim($post['high'])) . "',";
		$sql .= 	" Weight = '" . db_Escape(trim($post['weight'])) . "',";
		$sql .= 	" Bust = '" . db_Escape(trim($post['bust'])) . "',";
		$sql .= 	" Waist = '" . db_Escape(trim($post['waist'])) . "',";
		$sql .= 	" Hips = '" . db_Escape(trim($post['hips'])) . "',";
		$sql .= 	" Shoulder = '" . db_Escape(trim($post['shoulder'])) . "',";
		$sql .= 	" Sleeve = '" . db_Escape(trim($post['sleeve'])) . "',";
		$sql .= 	" Length = '" . db_Escape(trim($post['length'])) . "',";
        $sql .=     " Kitake = '" . db_Escape(trim($post['kitake'])) . "',";
        $sql .=     " Yukitake = '" . db_Escape(trim($post['yukitake'])) . "',";
        $sql .=     " Inseam = '" . db_Escape(trim($post['inseam'])) . "',";
		$sql .= 	" UpdDay = GETDATE(),";
		$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= " WHERE";
		$sql .= 	" TokID = '" . db_Escape(trim($tokId)) . "'";
		$sql .= " AND";
		$sql .= 	" Del = " . DELETE_OFF;
	
		$isSuccess = db_Execute($dbConnect, $sql);

		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}

	}

	// 特寸情報を論理削除
	elseif ($tokId != '' && (!isset($post['tokFlg']) || $post['tokFlg'] != 1)) {

		// T_Tokを論理削除する
		$sql  = "";
		$sql .= " UPDATE";
		$sql .= 	" T_Tok";
		$sql .= " SET";
		$sql .= 	" Del = " . DELETE_ON . ",";
		$sql .= 	" UpdDay = GETDATE(),";
		$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= " WHERE";
		$sql .= 	" TokID = '" . db_Escape(trim($tokId)) . "'";
	
		$isSuccess = db_Execute($dbConnect, $sql);

		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}

	}

	// T_Staffの情報を取得
    $sql  = "";
    $sql .= " SELECT";
    $sql .=     " COUNT(StaffID) as countID";
    $sql .= " FROM";
    $sql .=     " T_Staff";
    $sql .= " WHERE";
    $sql .=     " StaffID = '" . db_Escape(trim($post['staffId'])) . "'";
    $sql .= " AND";
    $sql .=     " StaffCode = '" . db_Escape(trim($staffData['StaffCode'])) . "'";
    $sql .= " AND";
    $sql .=     " CompID = '" . db_Escape(trim($staffData['CompID'])) . "'";
    $sql .= " AND";
    $sql .=     " Del = " . DELETE_OFF;

	$result = db_Read($dbConnect, $sql);

    $count = 0;
    if (isset($result[0]['countID'])) {
        $count = $result[0]['countID'];
    }

    // スタッフがまだ登録されていない場合
    $newStaffFlg = false;
    if ($count == 0) {

		// T_Staffに登録する
        $sql  = "";
        $sql .= " INSERT INTO";
        $sql .=     " T_Staff";
        $sql .=         " (";
        $sql .=         " StaffID,";
        $sql .=         " CompID,";
        $sql .=         " StaffCode,";
  		// パターンＩＤを記録 Y.Furukawa 2017/05/02
        $sql .=         " PatternID,";
        $sql .=         " WithdrawalFlag,";
        $sql .=         " AllReturnFlag,";
        $sql .=         " Del,";
        $sql .=         " RegistDay,";
        $sql .=         " RegistUser";
        $sql .=         " )";
        $sql .= " VALUES";
        $sql .=         " (";
        $sql .=         " '" . db_Escape(trim($post['staffId'])) . "',";
        $sql .=         " '" . db_Escape(trim($staffData['CompID'])) . "',";
        $sql .=         " '" . db_Escape(trim($staffData['StaffCode'])) . "',";
  		// パターンＩＤを記録 Y.Furukawa 2017/05/02
   		$sql .= 		" '" . db_Escape(trim($post['searchPatternId'])) . "',";
        $sql .=         " 0,";  // WithdrawalFlagの初期値は0
        $sql .=         " 0,";  // AllReturnFlagの初期値は0
        $sql .=         " " . DELETE_OFF . ",";
        $sql .=         " GETDATE(),";
        $sql .=         " '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
        $sql .=         " )";

		$isSuccess = db_Execute($dbConnect, $sql);
	
		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}

		$staffId = $post['staffId'];

		$newStaffFlg = true;  

	}

    // T_Staff_Detailsの論理削除
    $sql  = "";
    $sql .= " UPDATE";
    $sql .=     " T_Staff_Details";
    $sql .= " SET";
    $sql .=     " UpdDay = GETDATE(),";
    $sql .=     " UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "',";
    $sql .=     " Del = " . DELETE_ON;
    $sql .= " WHERE";
    $sql .=     " OrderDetID IN (";
    $sql .=         " SELECT";
    $sql .=             " OrderDetID";
    $sql .=         " FROM";
    $sql .=             " T_Order_Details";
    $sql .=         " WHERE";
    $sql .=             " AppliNo = '". db_Escape(trim($post['requestNo'])) ."'";
    $sql .=         " AND";
    $sql .=             " Del = " . DELETE_OFF;
    $sql .=     " )";
    $sql .= " AND";
    $sql .=     " Del = " . DELETE_OFF;
    
    $isSuccess = db_Execute($dbConnect, $sql);

    // 実行結果が失敗の場合
    if ($isSuccess == false) {
        return false;
    }

    // T_Order_Detailsの論理削除
    $sql  = "";
    $sql .= " UPDATE";
    $sql .=     " T_Order_Details";
    $sql .= " SET";
    $sql .=     " UpdDay = GETDATE(),";
    $sql .=     " UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "',";
    $sql .=     " Del = " . DELETE_ON;
    $sql .= " WHERE";
    $sql .=     " AppliNo = '". db_Escape(trim($post['requestNo'])) ."'";
    $sql .= " AND";
    $sql .=     " Del = " . DELETE_OFF;
    
    $isSuccess = db_Execute($dbConnect, $sql);

    // 実行結果が失敗の場合
    if ($isSuccess == false) {
        return false;
    }

	// T_Order_Detailsに登録するための情報をセットここから +++++++++++++++++++++++++++++++
    $orderDetails = array();

    $itemIds = '';
    if(is_array($post['itemIds'])) {
        $itemIds = implode(', ', $post['itemIds']);
    }

    // 発注申請の一覧を取得する
    $sql  = "";
    $sql .= " SELECT";
    $sql .=     " mi.ItemID,";
    $sql .=     " mi.ItemNo,";
    $sql .=     " mi.ItemName,";
    $sql .=     " mi.SizeID";
    $sql .= " FROM";
    $sql .=     " M_Item mi";
    $sql .= " WHERE";
    $sql .=     " mi.Del = " . DELETE_OFF;
    $sql .= " AND";
    $sql .=     " mi.ItemID IN (" . db_Escape($itemIds) . ")";
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
        $orderDetails[$key]['itemId'] = $val['ItemID'];
        $orderDetails[$key]['itemNo'] = $val['ItemNo'];
        $orderDetails[$key]['itemName'] = $val['ItemName'];
        $orderDetails[$key]['num'] = $post['itemNumber'][$val['ItemID']];

        // アイテムごとのサイズを取得
        $sizeAry = getSize($dbConnect, $val['SizeID'], 1);
        $orderDetails[$key]['size'] = $sizeAry[$post['size'.$val['ItemID']]];
    }

	// T_Order_Detailsに登録するための情報をセット ここまで ++++++++++++++++++++++++++++++

	// T_Order_Detailsの登録
    $line = 1;  // 行番号
	$countOrderDetail = count($orderDetails);
	for ($i=0; $i<$countOrderDetail; $i++) {

		// ストック情報を取得
		$sql  = "";
		$sql .= " SELECT";
		$sql .= 	" StockCD";
		$sql .= " FROM";
		$sql .= 	" M_StockCtrl";
		$sql .= " WHERE";
		$sql .= 	" ItemNo = '" . db_Escape($orderDetails[$i]['itemNo']) . "'";
		$sql .= " AND";
		$sql .= 	" Size = '" . db_Escape($orderDetails[$i]['size']) . "'";
		$sql .= " AND";
		$sql .= 	" Del = " . DELETE_OFF;

		$stockDatas = db_Read($dbConnect, $sql);

		// 実行結果が失敗の場合
		if ($stockDatas == false || count($stockDatas) <= 0) {
			return false;
		}

        // 各アイテムの数量分繰り返す
        for ($t = 1;$t<=$orderDetails[$i]['num'];$t++) {
            // T_Order_Detailsの登録
            $sql  = "";
            $sql .= " INSERT INTO";
            $sql .=     " T_Order_Details";
            $sql .=         " (";
            $sql .=         " OrderID,";
            $sql .=         " AppliNo,";
            $sql .=         " AppliLNo,";
            $sql .=         " ItemID,";
            $sql .=         " ItemNo,";
            $sql .=         " ItemName,";
            $sql .=         " Size,";
            $sql .=         " StockCd,";
            $sql .=         " Status,";
            $sql .=         " AppliDay,";
            $sql .=         " Del,";
            $sql .=         " RegistDay,";
            $sql .=         " RegistUser";
            $sql .=         " )";
            $sql .= " VALUES";
            $sql .=         " (";
            $sql .=         " '" . db_Escape($orderId) ."',";
            $sql .=         " '" . db_Escape(trim($post['requestNo'])) ."',";
            $sql .=         " '" . db_Escape($line) ."',";
            $sql .=         " '" . db_Escape(trim($orderDetails[$i]['itemId'])) ."',";
            $sql .=         " '" . db_Escape(trim($orderDetails[$i]['itemNo'])) ."',";
            $sql .=         " '" . db_Escape(trim($orderDetails[$i]['itemName'])) ."',";
            $sql .=         " '" . db_Escape(trim($orderDetails[$i]['size'])) ."',";
            $sql .=         " '" . db_Escape(trim($stockDatas[0]['StockCD'])) ."',";
			$sql .=         " " . STATUS_APPLI . ",";       // 申請済（承認待ち）は1
//            $sql .=         " " . STATUS_APPLI_ADMIT . ",";       // 承認機能がないため承認済をsセット
            $sql .=         " GETDATE(),";
            $sql .=         " " . DELETE_OFF . ",";
            $sql .=         " GETDATE(),";
            $sql .=         " '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
            $sql .=         " );";
    
            $isSuccess = db_Execute($dbConnect, $sql);
        
            // 実行結果が失敗の場合
            if ($isSuccess == false) {
                return false;
            }

            // 直近のシーケンスIDを取得
            $sql  = "";
            $sql .= " SELECT";
            $sql .=     " SCOPE_IDENTITY() as scope_identity;";
            
            $result = db_Read($dbConnect, $sql);
        
            // 実行結果が失敗の場合
            if ($result == false || !isset($result[0]['scope_identity']) || $result[0]['scope_identity'] == '') {
                return false;
            }
        
            $orderDetailId = $result[0]['scope_identity'];
    
            // T_Staff_Detailsの登録
            $sql  = "";
            $sql .= " INSERT INTO";
            $sql .=     " T_Staff_Details";
            $sql .=         " (";
            $sql .=         " StaffID,";
            $sql .=         " OrderDetID,";
            $sql .=         " Status,";
            $sql .=         " ReturnFlag,";
            $sql .=         " Del,";
            $sql .=         " RegistDay,";
            $sql .=         " RegistUser";
            $sql .=         " )";
            $sql .= " VALUES";
            $sql .=         " (";
            $sql .=         " '" . db_Escape($post['staffId']) ."',";
            $sql .=         " '" . db_Escape($orderDetailId) ."',";
			$sql .=         " " . STATUS_APPLI . ",";       // 申請済（承認待ち）は1
//            $sql .=         " " . STATUS_APPLI_ADMIT . ",";       // 承認機能がないため承認済をsセット
            $sql .=         " 0,";                          // ReturnFlagの初期値は0
            $sql .=         " " . DELETE_OFF . ",";
            $sql .=         " GETDATE(),";
            $sql .=         " '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
            $sql .=         " );";
    
            $isSuccess = db_Execute($dbConnect, $sql);
        
            // 実行結果が失敗の場合
            if ($isSuccess == false) {
                return false;
            }

            $line++;    // 行番号更新
        }
	}

	return true;
}

/*
 * 発注申請（訂正）メールを送信する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$post      => POST値
 *       ：$orderId   => OrderID
 * 戻り値：true：変更成功 / false：変更失敗
 *
 * create 2007/03/30 H.Osugi
 *
 */
function sendMailShinsei($dbConnect, $post, $orderId) {

	$tokFlg = 0;
	if (isset($post['tokFlg']) && trim($post['tokFlg']) != '') {
		$tokFlg = trim($post['tokFlg']);
	}

	$filePath = '../../mail_template/';

	// 発注訂正時
	$motoTokFlg = 0;
	if (isset($post['rirekiFlg']) && $post['rirekiFlg'] == 1) {

		if (isset($post['motoTokFlg']) && trim($post['motoTokFlg']) == '1') {
			$motoTokFlg = trim($post['motoTokFlg']);
		}

		// 訂正メールの件名と本文を取得
		$isSuccess = hachuTeiseiMail($dbConnect, $orderId, $tokFlg, $motoTokFlg, $filePath, $subject, $message);
	
	}
	// 発注申請時
	else {
	
		// 申請メールの件名と本文を取得
		$isSuccess = hachuShinseiMail($dbConnect, $orderId, $tokFlg, $filePath, $subject, $message);
	
	}

	if ($isSuccess == false) {
		return false;
	}

	$toAddr = MAIL_GROUP_1;

	// 特寸情報があった場合は特寸のメールグループにもメール送信
	if ($tokFlg == 1 || $motoTokFlg == 1) {

		if($toAddr != '') {
			$toAddr .= ',';
		}
		$toAddr .= MAIL_GROUP_4;

	}

	$bccAddr = MAIL_GROUP_0;
	$fromAddr = FROM_MAIL;
	$returnAddr = RETURN_MAIL;

	// メールを送信
	$isSuccess = sendTextMail($toAddr, $subject, $message, $fromAddr, '', $bccAddr, 'UTF-8', $returnAddr);

	if ($isSuccess == false) {
		return false;
	}

	return true;

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
function getDispItem($dbConnect, $post)
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
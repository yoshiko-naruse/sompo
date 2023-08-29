<?php
/*
 * 交換完了画面
 * koukan_shinsei_kanryo.src.php
 *
 * create 2007/03/20 H.Osugi
 *
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
require_once('../../include/createKoukanMail.php');		// 交換申請メール生成モジュール
require_once('../../include/sendTextMail.php');			// テキストメール送信モジュール
require_once('../../include/commonFunc.php');           // 共通関数モジュール
require_once('./koukan_func.php');                      // 交換機能共通関数モジュール
require_once('./koukan_shinsei.val.php');				// エラー判定モジュール

// 初期設定
$isMenuExchange = true;			// 交換のメニューをアクティブに

// 変数の初期化 ここから *******************************************************
$orderId   = '';					// OrderID
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

$haveTok  = false;					// 特寸から遷移してきたか判定フラグ

$isLoss   = false;					// 紛失申請かどうかの判定フラグ

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

// スタッフIDが取得できなければエラーに
if (!isset($_POST['staffId']) || $_POST['staffId'] == '') {
	// TOP画面に強制遷移
    $returnUrl             = HOME_URL . 'top.php';
	redirectPost($returnUrl, $hiddens);
}

// 店舗等のデータを取得
$headerData = getHeaderData($dbConnect, $_POST['staffId']);

$compCd    = $headerData['CompCd'];   // 店舗番号
$compName  = $headerData['CompName']; // 店舗名

// 申請番号がすでに登録されていないか判定
checkDuplicateAppliNo($dbConnect, $_POST['requestNo'], 'koukan/koukan_top.php', 2);

// エラー判定
validatePostData($dbConnect, $_POST);

// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST); 

if ($post['appliReason'] == APPLI_REASON_EXCHANGE_CHANGEGRADE || $post['appliReason'] == APPLI_REASON_EXCHANGE_MATERNITY) {     // 役職変更交換かマタニティ交換のみ
    // 交換可能な商品一覧を表示
    $returns = getStaffOrder($dbConnect, $post['staffId'], trim($post['appliReason']), $post);
    
    // 交換可能商品が０件の場合
    if (count($returns) <= 0) {
    
        $hiddens['errorName']       = 'koukanShinsei';
        $hiddens['menuName']        = 'isMenuExchange';
        $hiddens['returnUrl']       = 'koukan/koukan_sentaku.php';
        $hiddens['errorId'][]       = '901';
        $errorUrl                   = HOME_URL . 'error.php';
    
        $hiddens['appliReason']    = trim($post['appliReason']);
        $hiddens['searchStaffCode'] = $post['searchStaffCode'];
        $hiddens['searchFlg']       = '1';
    
        if ($isLevelAdmin == true) {
            $hiddens['searchCompCd']   = trim($post['searchCompCd']);       // 店舗番号
            $hiddens['searchCompName'] = trim($post['searchCompName']);     // 店舗名
            $hiddens['searchCompId']   = trim($post['searchCompId']);       // 店舗名
        }
    
        redirectPost($errorUrl, $hiddens);
    
    }

    // 表示する発注申請情報取得
    if ($_POST['appliReason'] == APPLI_REASON_EXCHANGE_CHANGEGRADE) {
        $orders = getDefineOrder($dbConnect, $post['appliReason'], $post, count($returns));
    } else {
        $orders = getDefineOrder($dbConnect, $post['appliReason'], $post);
    }

} else {
    // 交換できないユニフォームが存在しないかを判定する
    checkExchange($dbConnect, $_POST['orderDetIds'], 'koukan/koukan_top.php', $_POST);

    // 表示する返却申請情報取得
    $returns = getReturnSelect($dbConnect, $post);

    // 表示する発注申請情報取得
    $orders = getOrderSelect($dbConnect, $post);

}

// トランザクション開始
db_Transaction_Begin($dbConnect);

// 返却申請処理
$isSuccessReturn = createReturn($dbConnect, $post, $headerData, $orderId, $isLevelAdmin);

// 返却処理失敗時
if ($isSuccessReturn == false) {

	// ロールバック
	db_Transaction_Rollback($dbConnect);

	$hiddens['errorName'] = 'koukanShinsei';
	$hiddens['menuName']  = 'isMenuExchange';
	$hiddens['returnUrl'] = 'koukan/koukan_top.php';
	$hiddens['errorId'][] = '902';
	$errorUrl             = HOME_URL . 'error.php';

	// エラー画面に強制遷移
	redirectPost($errorUrl, $hiddens);

}

// 発注申請処理
$isSuccessOrder = createOrder($dbConnect, $post, $headerData, count($returns), $newOrderId, $isLevelAdmin);

// 発注処理失敗時
if ($isSuccessOrder == false) {

	// ロールバック
	db_Transaction_Rollback($dbConnect);
	$hiddens['errorName'] = 'koukanShinsei';
	$hiddens['menuName']  = 'isMenuExchange';
	$hiddens['returnUrl'] = 'koukan/koukan_top.php';
	$hiddens['errorId'][] = '903';
	$errorUrl             = HOME_URL . 'error.php';

	// エラー画面に強制遷移
	redirectPost($errorUrl, $hiddens);

}

// コミット
db_Transaction_Commit($dbConnect);

// 交換申請メール送信
$isSuccess = sendMailShinsei($dbConnect, $_POST, $newOrderId, $orderId);

// 表示する値の成型 ここから +++++++++++++++++++++++++++++++++++++++++++++++++++

// 申請番号
$requestNo = trim($post['requestNo']);
$displayRequestNo = 'A' . trim($requestNo);		// 頭文字に'A'をつける

// スタッフコード
$staffCode = trim($headerData['StaffCode']);

// 着用者名
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

if ($memo != '' || $memo === 0) {
	$isEmptyMemo = false;
}

$appliReason = trim($post['appliReason']);	// 交換理由

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

if ($isLevelAdmin == true) {
	$searchCompCd    = castHtmlEntity($post['searchCompCd']);	// 店舗番号
	$searchCompName  = castHtmlEntity($post['searchCompName']);	// 店舗名
}

// hidden値の成型
$notArrowKeys = array('orderDetIds' , 'size', 'sizeType', 'itemUnused', 'itemId');
$hiddenHtml = castHidden($post, $notArrowKeys);
// 表示する値の成型 ここまで ++++++++++++++++++++++++++++++++++++++++++++++++++

/*
 * 返却申請一覧情報を取得する
 * 引数  ：$dbConnect    => コネクションハンドラ
 *       ：$post         => POST値
 * 戻り値：$result       => 返却申請された商品一覧情報
 *
 * create 2007/03/20 H.Osugi
 *
 */
function getReturnSelect($dbConnect, $post) {

	$orderDetIds = '';
	if(is_array($post['orderDetIds'])) {
		foreach ($post['orderDetIds'] as $key => $value) {
			if (!(int)$value) {	// int以外の値が入っていた場合はエラー
				return false;
			} 
		}
		$orderDetIds = implode(', ', $post['orderDetIds']);
	}

	// 返却申請の一覧を取得する
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
	$sql .= 	" tod.OrderDetID IN (" . db_Escape($orderDetIds) . ")";
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
		$result[$i]['SizeID']     = $result[$i]['SizeID'];

		// バーコードが空かどうか判定
		$result[$i]['isEmptyBarCd'] = false;
		if ($result[$i]['BarCd'] == '') {
			$result[$i]['isEmptyBarCd'] = true;
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
 * 発注申請一覧情報を取得する
 * 引数  ：$dbConnect    => コネクションハンドラ
 *       ：$post         => POST値
 * 戻り値：$result       => 発注申請された一覧情報
 *
 * create 2007/03/20 H.Osugi
 *
 */
function getOrderSelect($dbConnect, $post) {

	$orderDetIds = '';
	if(is_array($post['orderDetIds'])) {
		foreach ($post['orderDetIds'] as $key => $value) {
			if (!(int)$value) {	// int以外の値が入っていた場合はエラー
				return false;
			} 
		}
		$orderDetIds = implode(', ', $post['orderDetIds']);
	}
//var_dump($post);die;
	// 発注申請の一覧を取得する
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
	$sql .= 	" tod.OrderDetID IN (" . db_Escape($orderDetIds) . ")";
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
//var_dump($sql);die;
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

		// サイズ交換とサイズ交換以外で分岐分け Y.Furukawa 2017/04/11
        //if ($post['appliReason'] == APPLI_REASON_EXCHANGE_SIZE) { 
		//// サイズの表示情報を成型
        	$sizeData = getSize($dbConnect, $post['sizeType'][$result[$i]['OrderDetID']], 1);
    		$result[$i]['selectedSize'] = $sizeData[$post['size'][$result[$i]['OrderDetID']]];

		//} else {
		//	$result[$i]['selectedSize'] = castHtmlEntity($result[$i]['Size']);
		//}

	}

	return  $result;
}

/*
 * 定義された交換商品一覧情報を取得する
 * 引数  ：$dbConnect    => コネクションハンドラ
 *       ：$appliReason => 交換理由 
 *       ：$post         => POST値
 *       ：$num         => 交換数
 * 戻り値：$result       => 交換商品一覧情報
 *
 * create 2008/04/22 W.Takasaki
 *
 */
function getDefineOrder($dbConnect, $appliReason, $post, $num = -1) {

    // 初期化
    $result = array();

    // 商品の一覧を取得する
    if ($appliReason == APPLI_REASON_EXCHANGE_CHANGEGRADE) {
        $sql = " SELECT";
        $sql .=     " I.ItemID";
        $sql .=    " ,I.ItemNo";
        $sql .=    " ,I.SizeID";
        $sql .=    " ,I.ItemName";
        $sql .= " FROM";
        $sql .=    " M_Item I";
        $sql .= " WHERE";
        $sql .=     " I.Del = " . DELETE_OFF;
        $sql .= " AND";
        $sql .=     " I.ItemNo = '" . ORDER_ITEM_JACKET_OFFICER . "'";
    } else {
        $sql .= " SELECT";
        $sql .=     " I.ItemID";
        $sql .=    " ,ISelect.SizeID";
        $sql .=    " ,ISelect.ItemSelectName as ItemName";
        $sql .=    " ,ISelect.ItemSelectNum";
        $sql .= " FROM";
        $sql .=    " M_Item I";
        $sql .=    " INNER JOIN";
        $sql .=    " M_ItemSelect ISelect";
        $sql .=    " ON";
        $sql .=    " I.ItemID = ISelect.ItemID";
        $sql .= " WHERE";
        $sql .=     " ISelect.AppliReason = " . $appliReason;
        $sql .= " AND";
        $sql .=     " I.Del = " . DELETE_OFF;
        $sql .= " AND";
        $sql .=     " ISelect.Del = " . DELETE_OFF;
    }

    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合
    if (count($result) <= 0) {
        $result = array();
        return $result;
    }

    // 取得した値をHTMLエンティティ
    $resultCount = count($result);
    $returnAry = array();
    $dispAry = array();
    for ($i=0; $i<$resultCount; $i++) {

        // サイズ展開を取得
        $sizeData = array();
        $sizeData = getSize($dbConnect, $result[$i]['SizeID'], 1);

        $returnAry[$i]['ItemName']   = castHtmlEntity($result[$i]['ItemName']);
        if ($post['appliReason'] == APPLI_REASON_EXCHANGE_CHANGEGRADE) {
            if ($num > 0) {
                $returnAry[$i]['num'] = $num;
            } else {
                return false;    
            }
        } else {
            $returnAry[$i]['num'] = $result[$i]['ItemSelectNum'];
        }

        // サイズの表示値を取得
        if (isset($sizeData)) {

            // 初期化
            $returnAry[$i]['selectedSize'] = '';
            if (isset($post['size'][$result[$i]['ItemID']])) {
                $returnAry[$i]['selectedSize'] = $sizeData[trim($post['size'][$result[$i]['ItemID']])];
            }
        }

        for ($t=1;$t<=$returnAry[$i]['num'];$t++) {
            $dispAry[] = $returnAry[$i];
        }

    }

    return  $dispAry;

}
/*
 * 返却申請を登録する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$post         => POST値
 *       ：$headerData   => 店舗コード、ID等
 *       ：$orderId      => OrderID
 * 戻り値：true：登録成功 / false：登録失敗
 *
 * create 2007/03/20 H.Osugi
 *
 */
function createReturn($dbConnect, $post, $headerData, &$orderId, $isLevelAdmin) {

	// 郵便番号を成型する
	$zip = trim($post['zip1']) . '-' . trim($post['zip2']);

	// 返却申請は頭文字に'R'を付加する
	$requestNo = 'R' . trim($post['requestNo']);

	// 選択されたorderDetID
	$orderDetIds = '';
	if(is_array($post['orderDetIds'])) {
		foreach($post['orderDetIds'] as $key => $value) {
			if (!(int)$value) {
				return false;
			}
		}
		$orderDetIds = implode(', ', $post['orderDetIds']);
	}

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
    //// 修理交換申請の場合のみWaitFlagを登録
    //if ($post['appliReason'] == APPLI_REASON_EXCHANGE_REPAIR) {
    //    $sql .=     " WaitFlag,";
    //}
	$sql .= 		" Del,";
	$sql .= 		" RegistDay,";
	$sql .= 		" RegistUser";
	$sql .= 		" )";
	$sql .= " VALUES";
	$sql .= 		" (";
	$sql .= 		" GETDATE(),";
	$sql .= 		" '" . db_Escape(trim($requestNo)) . "',";
	$sql .= 		" '" . db_Escape(trim($_SESSION['USERID'])) . "',";

	$sql .= 		" '" . db_Escape(trim($headerData['CompCd'])) . "',";
	$sql .= 		" '" . db_Escape(trim($headerData['CompName'])) . "',";

	$sql .= 		" " . APPLI_MODE_EXCHANGE . ",";		// 交換は2

	$sql .= 		" " . $post['appliReason'] . ",";

	$sql .= 		" '" . db_Escape(trim($headerData['CompID'])) . "',";

    $sql .=         " '" . db_Escape(trim($headerData['StaffSeqID'])) . "',";
	$sql .= 		" '" . db_Escape(trim($headerData['StaffCode'])) . "',";
    $sql .=         " '" . db_Escape(trim($headerData['PersonName'])) . "',";
	$sql .= 		" '" . db_Escape(trim($zip)) . "',";
	$sql .= 		" '" . db_Escape(trim($post['address'])) . "',";
	$sql .= 		" '" . db_Escape(trim($post['tel'])) . "',";
	$sql .= 		" '" . db_Escape(trim($post['shipName'])) . "',";
	$sql .= 		" '" . db_Escape(trim($post['staffName'])) . "',";
	$sql .= 		" '" . db_Escape(trim($post['memo'])) . "',";

	if (trim($post['appliReason']) == APPLI_REASON_EXCHANGE_LOSS) {
		$sql .= 		" " . STATUS_LOSS . ",";				// 紛失（承認）は32
	}
//	// サイズ交換・不良品交換の場合は、承認済とする 2023/01/24 T.Uno
//	else if (trim($post['appliReason']) == APPLI_REASON_EXCHANGE_SIZE || trim($post['appliReason']) == APPLI_REASON_EXCHANGE_INFERIORITY ) {
//		$sql .= 		" " . STATUS_NOT_RETURN_ADMIT . ",";	// （承認済）は20
//	}
	else {
		$sql .= 		" " . STATUS_NOT_RETURN . ",";			// 返却（承認待）は18
	}

	$sql .= 		" 0,";

    //// 修理交換申請の場合のみWaitFlagを登録
    //if ($post['appliReason'] == APPLI_REASON_EXCHANGE_REPAIR) {
    //    //if ($isLevelAdmin) {    // 代理交換の場合はFlgオフ
    //    //    $sql .=     " ".COMMON_FLAG_OFF.",";
    //    //} else {    // システム定義値を登録
    //    $sql .=     " ".ORDER_WAIT_FLAG.",";
    //    //}
    //}

	$sql .= 		" " . DELETE_OFF . ",";
	$sql .= 		" GETDATE(),";
	$sql .= 		" '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
	$sql .= 		" )";
	
	$isSuccess = db_Execute($dbConnect, $sql);

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

	$staffId = trim($post['staffId']);

	$orderDetails = array();

    // T_Order_Detailsの情報を取得
    $sql  = "";
    $sql .= " SELECT";
    $sql .=     " tod.OrderDetID,";
    $sql .=     " tod.Size,";
    $sql .=     " tod.BarCd,";
    $sql .=     " tod.IcTagCd,";
    $sql .=     " mi.ItemID,";
    $sql .=     " mi.ItemNo,";
    $sql .=     " mi.ItemName,";
    $sql .=     " msc.StockCD";
    $sql .= " FROM";
    $sql .=     " T_Order_Details tod";
    $sql .= " INNER JOIN";
    $sql .=     " T_Order torder";
    $sql .= " ON";
    $sql .=     " tod.OrderID = torder.OrderID";
    $sql .= " INNER JOIN";
    $sql .=     " M_Item mi";
    $sql .= " ON";
    $sql .=     " tod.ItemID = mi.ItemID";
    $sql .= " AND";
    $sql .=     " mi.Del = " . DELETE_OFF;
    $sql .= " INNER JOIN";
    $sql .=     " M_StockCtrl msc";
    $sql .= " ON";
    $sql .=     " mi.ItemNo = msc.ItemNo";
    $sql .= " AND";
    $sql .=     " tod.Size = msc.Size";
    $sql .= " AND";
    $sql .=     " msc.Del = " . DELETE_OFF;
    $sql .= " WHERE";
    if (trim($post['appliReason']) == APPLI_REASON_EXCHANGE_CHANGEGRADE || trim($post['appliReason']) == APPLI_REASON_EXCHANGE_MATERNITY) {
        $sql .=     " torder.StaffID = '" . db_Escape($post['staffId']) . "'";
        $sql .= " AND";
        $sql .=     " tod.Status IN ( " . STATUS_SHIP . ", ". STATUS_DELIVERY .")";     // ステータスが出荷済(15),納品済(16)
        $sql .= " AND";
        $sql .=     " tod.OrderDetID NOT IN ( SELECT MotoOrderDetID FROM T_Order_Details WHERE DEL = ".DELETE_OFF." )";     // ステータスが出荷済(15),納品済(16)
        if (trim($post['appliReason']) == APPLI_REASON_EXCHANGE_CHANGEGRADE) {
            $sql .= " AND";
            $sql .=     " mi.ItemNo = '" . ORDER_ITEM_JACKET_COMMON . "'";
        }
    } else {
        $sql .=     " tod.OrderDetId IN (" . db_Escape($orderDetIds) . ")";
    }
    $sql .= " AND";
    $sql .=     " tod.Del = " . DELETE_OFF;
    $sql .= " ORDER BY";
    $sql .=     " mi.ItemID ASC";

    $orderDetails = db_Read($dbConnect, $sql);
    
	// T_Order_Detailsの登録
	$countOrderDetail = count($orderDetails);
	for ($i=0; $i<$countOrderDetail; $i++) {

		// 初期化
		$orderDetailId = '';

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
		$sql .= 		" BarCd,";
		$sql .= 		" IcTagCd,";
		$sql .= 		" Status,";
		$sql .= 		" DamageCheck,";
		$sql .= 		" UnusedCheck,";
		$sql .= 		" AppliDay,";
		$sql .= 		" MotoOrderDetID,";
		$sql .= 		" Del,";
		$sql .= 		" RegistDay,";
		$sql .= 		" RegistUser";
		$sql .= 		" )";
		$sql .= " VALUES";
		$sql .= 		" (";
		$sql .= 		" '" . db_Escape($orderId) ."',";
		$sql .= 		" '" . db_Escape($requestNo) ."',";
		$sql .= 		" '" . db_Escape($i + 1) ."',";
		$sql .= 		" '" . db_Escape(trim($orderDetails[$i]['ItemID'])) ."',";
		$sql .= 		" '" . db_Escape(trim($orderDetails[$i]['ItemNo'])) ."',";
		$sql .= 		" '" . db_Escape(trim($orderDetails[$i]['ItemName'])) ."',";
		$sql .= 		" '" . db_Escape(trim($orderDetails[$i]['Size'])) ."',";
		$sql .= 		" '" . db_Escape(trim($orderDetails[$i]['StockCD'])) ."',";

		// BarCd
		if (trim($orderDetails[$i]['BarCd'])  != '') {
			$sql .= 		" '" . db_Escape(trim($orderDetails[$i]['BarCd'])) ."',";
		}
		else {
			$sql .= 		" NULL,";
		}

		// IcTagCd
		if (trim($orderDetails[$i]['IcTagCd'])  != '') {
			$sql .= 		" '" . db_Escape(trim($orderDetails[$i]['IcTagCd'])) ."',";
		}
		else {
			$sql .= 		" NULL,";
		}
	
		if (trim($post['appliReason']) == APPLI_REASON_EXCHANGE_LOSS) {
			$sql .= 		" " . STATUS_LOSS . ",";				// 紛失（承認済）は32
 		}
//		// サイズ交換・不良品交換の場合は、承認済とする 2023/01/24 T.Uno
//		else if (trim($post['appliReason']) == APPLI_REASON_EXCHANGE_SIZE || trim($post['appliReason']) == APPLI_REASON_EXCHANGE_INFERIORITY ) {
//			$sql .= 		" " . STATUS_NOT_RETURN_ADMIT . ",";	// 紛失（承認済）は20
//		}
		else {
			$sql .= 		" " . STATUS_NOT_RETURN . ",";			// 返却（承認済）は18
		}

		// DamageCheck
		if (trim($post['appliReason']) == APPLI_REASON_EXCHANGE_BREAK) {
			$sql .= 		" 1,";
		}
		else {
			$sql .= 		" 0,";
		}

		// UnusedCheck
		if (trim($post['appliReason']) == APPLI_REASON_EXCHANGE_FIRST) {
			if (isset($post['itemUnused'][$orderDetails[$i]['OrderDetID']]) && trim($post['itemUnused'][$orderDetails[$i]['OrderDetID']]) == '1') {
				$sql .= 		" 1,";
			}
			else {
				$sql .= 		" 0,";
			}
		}
		else {
			$sql .= 		" 0,";
		}

		$sql .= 		" GETDATE(),";
		$sql .= 		" '" . db_Escape(trim($orderDetails[$i]['OrderDetID'])) ."',";
		$sql .= 		" " . DELETE_OFF . ",";
		$sql .= 		" GETDATE(),";
		$sql .= 		" '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= 		" );";

		$isSuccess = db_Execute($dbConnect, $sql);
	
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
		$sql .= " UPDATE";
		$sql .= 	" T_Staff_Details";
		$sql .= " SET";
		$sql .= 	" ReturnDetID = '" . db_Escape($orderDetailId) ."',";

		if (trim($post['appliReason']) == APPLI_REASON_EXCHANGE_LOSS) {
			$sql .= 	" Status = " . STATUS_LOSS . ",";				// 紛失（承認済）は32
 		}
//		// サイズ交換・不良品交換の場合は、承認済とする 2023/01/24 T.Uno
//		else if (trim($post['appliReason']) == APPLI_REASON_EXCHANGE_SIZE || trim($post['appliReason']) == APPLI_REASON_EXCHANGE_INFERIORITY ) {
//			$sql .= 	" Status = " . STATUS_NOT_RETURN_ADMIT . ",";	// 返却（承認済）は20
//		}
		else {
			$sql .= 	" Status = " . STATUS_NOT_RETURN . ",";			// 返却（承認待）は18
		}

		$sql .= 	" UpdDay = GETDATE(),";
		$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= " WHERE";
		$sql .= 	" OrderDetID = '" . db_Escape($orderDetails[$i]['OrderDetID']) . "'";
		$sql .= " AND";
		$sql .= 	" StaffID = '" . db_Escape($staffId) . "'";

		$isSuccess = db_Execute($dbConnect, $sql);
	
		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}

	}

	return true;

}

/*
 * 発注申請を登録する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$post         => POST値
 *       ：$headerData   => 店舗コード、店舗ID等
 *       ：$num          => 交換数
 * 戻り値：true：登録成功 / false：登録失敗
 *
 * create 2007/03/20 H.Osugi
 *
 */
function createOrder($dbConnect, $post, $headerData, $num, &$orderId, $isLevelAdmin) {

	// 郵便番号を成型する
	$zip = trim($post['zip1']) . '-' . trim($post['zip2']);

	// 発注申請は頭文字に'A'を付加する
	$requestNo = 'A' . trim($post['requestNo']);

	// 選択されたorderDetID
	$orderDetIds = '';
	if(is_array($post['orderDetIds'])) {
		foreach($post['orderDetIds'] as $key => $value) {
			if (!(int)$value) {
				return false;
			}
		}
		$orderDetIds = implode(', ', $post['orderDetIds']);
	}

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
    //// 修理交換の場合のみWaitFlagを登録
    //if ($post['appliReason'] == APPLI_REASON_EXCHANGE_REPAIR) {
    //    $sql .=     " WaitFlag,";
    //}
	$sql .= 		" Del,";
	$sql .= 		" RegistDay,";
	$sql .= 		" RegistUser";
	$sql .= 		" )";
	$sql .= " VALUES";
	$sql .= 		" (";
	$sql .= 		" GETDATE(),";
	$sql .= 		" '" . db_Escape(trim($requestNo)) ."',";
	$sql .= 		" '" . db_Escape(trim($_SESSION['USERID'])) . "',";

	$sql .= 		" '" . db_Escape(trim($headerData['CompCd'])) . "',";
	$sql .= 		" '" . db_Escape(trim($headerData['CompName'])) . "',";

	$sql .= 		" " . APPLI_MODE_EXCHANGE . ",";		// 交換は2

	$sql .= 		" " . $post['appliReason'] . ",";

	$sql .= 		" '" . db_Escape(trim($headerData['CompID'])) . "',";
    $sql .=         " '" . db_Escape(trim($headerData['StaffSeqID'])) . "',";
	$sql .= 		" '" . db_Escape(trim($headerData['StaffCode'])) . "',";
    $sql .=         " '" . db_Escape(trim($headerData['PersonName'])) . "',";
	$sql .= 		" '" . db_Escape(trim($zip)) . "',";
	$sql .= 		" '" . db_Escape(trim($post['address'])) . "',";
	$sql .= 		" '" . db_Escape(trim($post['tel'])) . "',";
	$sql .= 		" '" . db_Escape(trim($post['shipName'])) . "',";
	$sql .= 		" '" . db_Escape(trim($post['staffName'])) . "',";
	$sql .= 		" '" . db_Escape(trim($post['memo'])) . "',";

//	// サイズ交換・不良品交換の場合は、承認済とする 2023/01/24 T.Uno
//	if (trim($post['appliReason']) == APPLI_REASON_EXCHANGE_SIZE || trim($post['appliReason']) == APPLI_REASON_EXCHANGE_INFERIORITY ) {
//		$sql .= 		" " . STATUS_APPLI_ADMIT . ",";	// 申請済（承認済）は3
//	} else {
		$sql .= 		" " . STATUS_APPLI . ",";		// 未承認：1
//	}
    // 特寸フラグが有効な場合
    if (isset($post['tokFlg']) && trim($post['tokFlg']) == COMMON_FLAG_ON) {
        $sql .=         " 1,";
        $sql .=         " '" . db_Escape(trim($post['tokMemo'])) . "',";
    } else {
        $sql .=         " 0,";
        $sql .=         " NULL,";
    }
    //// 修理交換の場合のみWaitFlagを登録
    //if ($post['appliReason'] == APPLI_REASON_EXCHANGE_REPAIR) {
    //    $sql .=     " ".ORDER_WAIT_FLAG.",";
    //}
	$sql .= 		" " . DELETE_OFF . ",";
	$sql .= 		" GETDATE(),";
	$sql .= 		" '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
	$sql .= 		" )";
	
	$isSuccess = db_Execute($dbConnect, $sql);

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
	if (isset($post['tokFlg']) && trim($post['tokFlg']) == 1) {

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
        $sql .=         " Yukitake,";
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

	$staffId = trim($post['staffId']);

    // 「役職変更による交換」と「マタニティとの交換」
    $orderDetails = array();
    if (trim($post['appliReason']) == APPLI_REASON_EXCHANGE_CHANGEGRADE || trim($post['appliReason']) == APPLI_REASON_EXCHANGE_MATERNITY) {
    
        // 商品の一覧を取得する
        if ($post['appliReason'] == APPLI_REASON_EXCHANGE_CHANGEGRADE) {
            $sql = " SELECT";
            $sql .=     " I.ItemID";
            $sql .=    " ,I.ItemNo";
            $sql .=    " ,I.SizeID";
            $sql .=    " ,I.ItemName";
            $sql .= " FROM";
            $sql .=    " M_Item I";
            $sql .= " WHERE";
            $sql .=     " I.Del = " . DELETE_OFF;
            $sql .= " AND";
            $sql .=     " I.ItemNo = '" . ORDER_ITEM_JACKET_OFFICER . "'";
        } else {
            $sql = " SELECT";
            $sql .=     " I.ItemID";
            $sql .=    " ,I.ItemNo";
            $sql .=    " ,ISelect.SizeID";
            $sql .=    " ,ISelect.ItemSelectName as ItemName";
            $sql .=    " ,ISelect.ItemSelectNum";
            $sql .= " FROM";
            $sql .=    " M_Item I";
            $sql .=    " INNER JOIN";
            $sql .=    " M_ItemSelect ISelect";
            $sql .=    " ON";
            $sql .=    " I.ItemID = ISelect.ItemID";
            $sql .= " WHERE";
            $sql .=     " ISelect.AppliReason = " . $post['appliReason'];
            $sql .= " AND";
            $sql .=     " I.Del = " . DELETE_OFF;
            $sql .= " AND";
            $sql .=     " ISelect.Del = " . DELETE_OFF;
        }
    
    	$result = db_Read($dbConnect, $sql);

        // T_Order_Detailsの登録
        $countResult = count($result);
        // アイテム数とデータ数をそろえる
        for ($i=0; $i<$countResult; $i++) {
            if ($post['appliReason'] == APPLI_REASON_EXCHANGE_CHANGEGRADE) {
                if ($num > 0) {
                    $result[$i]['ItemSelectNum'] = $num;
                } else {
                    return false;    
                }
            }

            if ($result[$i]['ItemSelectNum'] > 0) {
                for ($t=1;$t<=$result[$i]['ItemSelectNum'];$t++) {                
                    $orderDetails[] = $result[$i]; 
                }
            }
        }
    } else {
    
        // T_Order_Detailsの情報を取得
        $sql  = "";
        $sql .= " SELECT";
        $sql .=     " tod.OrderDetID,";
        $sql .=     " mi.ItemID,";
        $sql .=     " mi.ItemNo,";
        $sql .=     " mi.ItemName,";
        $sql .=     " tod.BarCd,";
        $sql .=     " tod.Size,";
        $sql .=     " mi.SizeID";

        $sql .= " FROM";
        $sql .=     " T_Order_Details tod";
        $sql .= " INNER JOIN";
        $sql .=     " M_Item mi";
        $sql .= " ON";
        $sql .=     " tod.ItemID = mi.ItemID";
        $sql .= " AND";
        $sql .=     " mi.Del = " . DELETE_OFF;
        $sql .= " WHERE";
        $sql .=     " tod.OrderDetId IN (" . db_Escape($orderDetIds) . ")";
        $sql .= " AND";
        $sql .=     " tod.Del = " . DELETE_OFF;
        $sql .= " ORDER BY";
        $sql .=     " mi.ItemID ASC";
    
        $orderDetails = db_Read($dbConnect, $sql);
    }

	// T_Order_Detailsの登録
	$countOrderDetail = count($orderDetails);
	for ($i=0; $i<$countOrderDetail; $i++) {

        //// 「役職変更による交換」と「マタニティとの交換」
        //if (trim($post['appliReason']) == APPLI_REASON_EXCHANGE_CHANGEGRADE || trim($post['appliReason']) == APPLI_REASON_EXCHANGE_MATERNITY) {
        //    // サイズ展開を取得
        //    $sizeData = getSizeByItem($dbConnect, $orderDetails[$i]['ItemID'], 1);
        //    $selectedSize = '';
        //    $selectedSize = $sizeData[$post['size'][$orderDetails[$i]['ItemID']]];

        //// サイズ交換とサイズ交換以外で分岐分け Y.Furukawa 2017/04/11
        //} if (trim($post['appliReason']) == APPLI_REASON_EXCHANGE_SIZE) {
            // サイズ展開を取得
            $sizeData = getSize($dbConnect, $post['sizeType'][$orderDetails[$i]['OrderDetID']], 1);
            $selectedSize = '';
            $selectedSize = $sizeData[$post['size'][$orderDetails[$i]['OrderDetID']]];

        //} else {
        //    // サイズ展開を取得
        //    $selectedSize = '';
        //    $selectedSize = $orderDetails[$i]['Size'];
        //}

		// ストック情報を取得
		$sql  = "";
		$sql .= " SELECT";
		$sql .= 	" StockCD";
		$sql .= " FROM";
		$sql .= 	" M_StockCtrl";
		$sql .= " WHERE";
		$sql .= 	" ItemNo = '" . db_Escape($orderDetails[$i]['ItemNo']) . "'";
		$sql .= " AND";
		$sql .= 	" Size = '" . db_Escape($selectedSize) . "'";
		$sql .= " AND";
		$sql .= 	" Del = " . DELETE_OFF;

		$stockDatas = db_Read($dbConnect, $sql);

		// 実行結果が失敗の場合
		if ($stockDatas == false || count($stockDatas) <= 0) {
			return false;
		}

		// 初期化
		$orderDetailId = '';

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
    	// 修理交換の場合のみWaitFlagを登録
    	if ($post['appliReason'] == APPLI_REASON_EXCHANGE_REPAIR) {
    	    $sql .=     " WaitFlag,";
    	}
		$sql .= 		" Del,";
		$sql .= 		" RegistDay,";
		$sql .= 		" RegistUser";
		$sql .= 		" )";
		$sql .= " VALUES";
		$sql .= 		" (";
		$sql .= 		" '" . db_Escape($orderId) . "',";
		$sql .= 		" '" . db_Escape($requestNo) . "',";
		$sql .= 		" '" . db_Escape($i + 1) . "',";
		$sql .= 		" '" . db_Escape(trim($orderDetails[$i]['ItemID'])) . "',";
		$sql .= 		" '" . db_Escape(trim($orderDetails[$i]['ItemNo'])) . "',";
		$sql .= 		" '" . db_Escape(trim($orderDetails[$i]['ItemName'])) . "',";
		$sql .= 		" '" . db_Escape(trim($selectedSize)) . "',";
		$sql .= 		" '" . db_Escape(trim($stockDatas[0]['StockCD'])) . "',";

//		// サイズ交換・不良品交換の場合は、承認済とする 2023/01/24 T.Uno
//		if (trim($post['appliReason']) == APPLI_REASON_EXCHANGE_SIZE || trim($post['appliReason']) == APPLI_REASON_EXCHANGE_INFERIORITY ) {
//			$sql .= 		" " . STATUS_APPLI_ADMIT . ",";	// 申請済（承認済）は3
//		} else {
			$sql .= 		" " . STATUS_APPLI . ",";		// 未承認：1
//		}

		$sql .= 		" GETDATE(),";
        // 修理交換の場合のみWaitFlagを登録
        if ($post['appliReason'] == APPLI_REASON_EXCHANGE_REPAIR) {
            $sql .=     " ".ORDER_WAIT_FLAG.",";
        }
		$sql .= 		" " . DELETE_OFF . ",";
		$sql .= 		" GETDATE(),";
		$sql .= 		" '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= 		" );";

		$isSuccess = db_Execute($dbConnect, $sql);

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
		$sql .= 		" '" . db_Escape($staffId) . "',";
		$sql .= 		" '" . db_Escape($orderDetailId) . "',";

//		// サイズ交換・不良品交換の場合は、承認済とする 2023/01/24 T.Uno
//		if (trim($post['appliReason']) == APPLI_REASON_EXCHANGE_SIZE || trim($post['appliReason']) == APPLI_REASON_EXCHANGE_INFERIORITY ) {
//			$sql .= 		" " . STATUS_APPLI_ADMIT . ",";	// 申請済（承認済）は3
//		} else {
			$sql .= 		" " . STATUS_APPLI . ",";		// 未承認：1
//		}

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

	}

	return true;

}

/*
 * 交換申請メールを送信する
 * 引数  ：$dbConnect  => コネクションハンドラ
 *       ：$post       => POST値
 *       ：$newOrderId => 発注のOrderID
 *       ：$orderId    => 返却のOrderID
 * 戻り値：true：変更成功 / false：変更失敗
 *
 * create 2007/04/25 H.Osugi
 *
 */
function sendMailShinsei($dbConnect, $post, $newOrderId, $orderId) {

	$tokFlg = 0;
	if (isset($post['tokFlg']) && trim($post['tokFlg']) == '1') {
		$tokFlg = trim($post['tokFlg']);
	}
	
	$filePath = '../../mail_template/';

	// 申請メールの件名と本文を取得
	$isSuccess = koukanShinseiMail($dbConnect, $newOrderId, $orderId, $tokFlg, $filePath, $subject, $message);

	if ($isSuccess == false) {
		return false;
	}

	$toAddr = MAIL_GROUP_2;

	// 特寸情報があった場合は特寸のメールグループにもメール送信
	if ($tokFlg == 1) {

		if($toAddr != '') {
			$toAddr .= ',';
		}
		$toAddr .= MAIL_GROUP_4;

	}

	$bccAddr = MAIL_GROUP_0;
	$fromAddr = FROM_MAIL;
	$returnAddr = RETURN_MAIL;

	// メールを送信
	$isSuccess = sendTextMail($toAddr, $subject, $message, $fromAddr, '', $bccAddr, 'UTF-8', $rturnAddr);

	if ($isSuccess == false) {
		return false;
	}

	return true;
}


?>
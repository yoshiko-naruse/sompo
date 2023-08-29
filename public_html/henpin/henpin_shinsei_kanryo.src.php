<?php
/*
 * 返却完了画面
 * henpin_shinsei_kanryo.src.php
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
require_once('../../include/createHenpinMail.php');		// 返却申請メール生成モジュール
require_once('../../include/sendTextMail.php');			// テキストメール送信モジュール
require_once('../../include/commonFunc.php');           // 共通関数モジュール
require_once('./henpin_shinsei.val.php');				// エラー判定モジュール

// 初期設定
$isMenuReturn = true;			// 返却のメニューをアクティブに

// 変数の初期化 ここから *******************************************************
$orderId   = '';					// OrderID
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
if (!isset($_POST['staffId']) || $_POST['staffId'] == '') {
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

// 返却できないユニフォームが存在しないかを判定する
checkReturn($dbConnect, $post['orderDetIds'], 'henpin/henpin_top.php');

// 退職・異動申請の場合
if ($post['appliReason'] == APPLI_REASON_RETURN_RETIRE) { 
	// 在庫切れ商品が無いか判定
	$stockOutData = getStockOut($dbConnect, $post);
}

// 返却がひとつも選択されていなかったら紛失申請
$isAllLoss = false;
if (count($post['returnChk']) <= 0) {
	$isAllLoss = true;
}

// 返却が選択されているか
$hasReturn = false;
if (count($post['returnChk']) > 0) {
	$hasReturn = true;
}

// 紛失が選択されているか
$hasLoss = false;
if (count($post['lostChk']) > 0) {
	$hasLoss = true;
}

// 汚損・破損が選択されているか
$hasBroken = false;
if (count($post['brokenChk']) > 0) {
	$hasBroken = true;
}

// 画面表示用データ取得
$headerData = getHeaderData($dbConnect, $post['staffId']);

// トランザクション開始
db_Transaction_Begin($dbConnect);

// 返却申請処理
$isSuccessReturn = createReturn($dbConnect, $post, $isAllLoss, $headerData, $orderId);

// 返却処理失敗時
if ($isSuccessReturn == false) {

	// ロールバック
	db_Transaction_Rollback($dbConnect);

	$hiddens['errorName'] = 'henpinShinsei';
	$hiddens['menuName']  = 'isMenuReturn';
	$hiddens['returnUrl'] = 'henpin/henpin_top.php';
	$hiddens['errorId'][] = '902';
	$errorUrl             = HOME_URL . 'error.php';

	// エラー画面に強制遷移
	redirectPost($errorUrl, $hiddens);

}

// コミット
db_Transaction_Commit($dbConnect);

// 返却申請メール送信
$isSuccess = sendMailShinsei($dbConnect, $orderId);

// 退職・異動申請の場合
if ($post['appliReason'] == APPLI_REASON_RETURN_RETIRE) {

	if (isset($stockOutData) && count($stockOutData) > 0) {

		// 退職・異動アラートメール送信
		$isSuccess = sendMailStockOut($dbConnect, $orderId, $stockOutData);

	}
}

// 返却・紛失のどちらかが選択されたorderDetIDを取得する
$orderDetIds = castOrderDetId($post);

// 表示する返却申請情報取得
$returns = getReturnSelect($dbConnect, $post, $orderDetIds);

// 表示する値の成型 ここから +++++++++++++++++++++++++++++++++++++++++++++++++++
// 申請番号
$requestNo = trim($post['requestNo']);

// 店舗コード
$compCd     = trim($headerData['CompCd']);

// 店舗名
$compName   = trim($headerData['CompName']);

// スタッフコード
$staffCode  = trim($headerData['StaffCode']);

// 着用者名
$personName = trim($headerData['PersonName']);

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
switch (trim($appliReason)) {

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
$notArrowKeys = array('orderDetIds', 'returnChk', 'lostChk', 'brokenChk');
$hiddenHtml = castHidden($post, $notArrowKeys);
// 表示する値の成型 ここまで ++++++++++++++++++++++++++++++++++++++++++++++++++

/*
 * 返却申請一覧情報を取得する
 * 引数  ：$dbConnect    => コネクションハンドラ
 *       ：$post         => POST値
 * 戻り値：$result       => 返却申請された商品一覧情報
 *
 * create 2007/03/22 H.Osugi
 *
 */
function getReturnSelect($dbConnect, $post, $orderDetIds) {

	// 初期化
	$result = array();

	if (!is_array($orderDetIds) || count($orderDetIds) <= 0) {
		return $result;
	}

	$orderDetId = '';
	if(is_array($orderDetIds)) {
		foreach ($orderDetIds as $key => $value) {
			if (!(int)$value) {
				return $result;
			}
		}
		$orderDetId = implode(', ', $orderDetIds);
	}

	// 返却申請一覧の一覧を取得する
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

/*
 * 返却申請を登録する
 * 引数  ：$dbConnect    => コネクションハンドラ
 *       ：$post         => POST値
 *       ：$isAllLoss    => 返却申請か紛失申請の判定
 *       ：$headerData   => 店舗コード、スタッフコード等
 *       ：$orderId      => OrderID
 * 戻り値：true：登録成功 / false：登録失敗
 *
 * create 2007/03/22 H.Osugi
 *
 */
function createReturn($dbConnect, $post, $isAllLoss, $headerData, &$orderId) {

	global $isLevelAdmin;

	// 選択されたorderDetID
	$orderDetIds = '';
	if(is_array($post['orderDetIds'])) {
		foreach ($post['orderDetIds'] as $key => $value) {
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
	$sql .= 		" AppliSeason,";
	$sql .= 		" AppliReason,";
	$sql .= 		" CompID,";
	$sql .= 		" StaffID,";
	$sql .= 		" StaffCode,";
	$sql .= 		" PersonName,";
	$sql .= 		" Note,";
	$sql .= 		" Status,";
	$sql .= 		" Tok,";
	$sql .= 		" RentalEndDay,";
	$sql .= 		" Del,";
	$sql .= 		" RegistDay,";
	$sql .= 		" RegistUser";
	$sql .= 		" )";
	$sql .= " VALUES";
	$sql .= 		" (";
	$sql .= 		" GETDATE(),";
	$sql .= 		" '" . db_Escape(trim($post['requestNo'])) . "',";
	$sql .= 		" '" . db_Escape(trim($_SESSION['USERID'])) . "',";

	$sql .= 		" '" . db_Escape(trim($headerData['CompCd'])) . "',";
	$sql .= 		" '" . db_Escape(trim($headerData['CompName'])) . "',";

	$sql .= 		" " . APPLI_MODE_RETURN . ",";		// 返却は3
	$sql .= 		" '',";		// 返却時の季節は0

	$sql .= 		" " . trim($post['appliReason']) . ",";

	$sql .= 		" '" . db_Escape(trim($headerData['CompID'])) . "',";

	$sql .= 		" " . trim($post['staffId']) . ",";
	$sql .= 		" '" . db_Escape(trim($headerData['StaffCode'])) . "',";
    $sql .=         " '" . db_Escape(trim($headerData['PersonName'])) . "',";
	$sql .= 		" '" . db_Escape(trim($post['memo'])) . "',";

	if ($isAllLoss == false) {
		$sql .= 		" " . STATUS_NOT_RETURN_ADMIT . ",";		// 未返却（承認済）は20
	}
	else {
		$sql .= 		" " . STATUS_LOSS_ADMIT . ",";		// 紛失（承認済）は34
	}
	$sql .= 		" 0,";
	if (trim($post['appliReason']) == APPLI_REASON_RETURN_RETIRE) {
		$sql .= 		" '" . db_Escape(trim($post['rentalEndDay'])) . "',";
	} else {
		$sql .= 	" NULL,";
	}
	$sql .= 		" " . DELETE_OFF . ",";				// DELの初期は0
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

	// 退職・異動返却の場合はT_StaffのWithdrawalFlagを1に
	if (trim($post['appliReason']) == APPLI_REASON_RETURN_RETIRE) {
	
		// T_Staffの変更
		$sql  = "";
		$sql .= " UPDATE";
		$sql .= 	" T_Staff";
		$sql .= " SET";
		$sql .= 	" WithdrawalFlag = 1,";
		$sql .= 	" WithdrawalDay = GETDATE(),";
		$sql .= 	" UpdDay = GETDATE(),";
		$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= " WHERE";
		$sql .= 	" StaffID = '" . db_Escape($staffId) . "'";
		$sql .= " AND";
		$sql .= 	" CompID = '" . db_Escape(trim($headerData['CompID'])) . "'";

		$isSuccess = db_Execute($dbConnect, $sql);
	
		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}

	}

	$orderDetails = array();

	// 退職・異動返却の場合のみ返却未申請の情報を論理削除する
	if (trim($post['appliReason']) == APPLI_REASON_RETURN_RETIRE) {

		// T_Order_Detailsの変更
		$sql  = "";
		$sql .= " UPDATE";
		$sql .= 	" T_Order_Details";
		$sql .= " SET";
		$sql .= 	" Del = " . DELETE_ON . ",";
		$sql .= 	" UpdDay = GETDATE(),";
		$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= " WHERE";
		$sql .= 	" OrderID IN (";

		$sql .= 		" SELECT";
		$sql .= 			" OrderID";
		$sql .= 		" FROM";
		$sql .= 			" T_Order";
		$sql .= 		" WHERE";
		$sql .= 			" StaffCode = '" . db_Escape($headerData['StaffCode']) . "'";
		$sql .= 		" AND";
		$sql .= 			" CompID = '" . db_Escape(trim($headerData['CompID'])) . "'";
		$sql .= 		" AND";
		$sql .= 			" Del = " . DELETE_OFF;

		$sql .= 	" )";
		$sql .= " AND";
		$sql .= 	" Status = " .  STATUS_RETURN_NOT_APPLY;		// 返却未申請

		$isSuccess = db_Execute($dbConnect, $sql);
	
		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}

	}

	// T_Order_Detailsの情報を取得
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" tod.OrderDetID,";
	$sql .= 	" tod.Size,";
	$sql .= 	" tod.BarCd,";
	$sql .= 	" tod.IcTagCd,";
	$sql .= 	" mi.ItemID,";
	$sql .= 	" mi.ItemNo,";
	$sql .= 	" mi.ItemName,";
	$sql .= 	" msc.StockCD";
	$sql .= " FROM";
	$sql .= 	" T_Order_Details tod";
	$sql .= " INNER JOIN";
	$sql .= 	" M_Item mi";
	$sql .= " ON";
	$sql .= 	" tod.ItemID = mi.ItemID";
	$sql .= " AND";
	$sql .= 	" mi.Del = " . DELETE_OFF;
	$sql .= " INNER JOIN";
	$sql .= 	" M_StockCtrl msc";
	$sql .= " ON";
	$sql .= 	" mi.ItemNo = msc.ItemNo";
	$sql .= " AND";
	$sql .= 	" tod.Size = msc.Size";
	$sql .= " AND";
	$sql .= 	" msc.Del = " . DELETE_OFF;
	$sql .= " WHERE";
	$sql .= 	" tod.OrderDetId IN (" . db_Escape($orderDetIds) . ")";
	$sql .= " AND";
	$sql .= 	" tod.Del = " . DELETE_OFF;
	$sql .= " ORDER BY";
	$sql .= 	" mi.ItemID ASC";

	$orderDetails = db_Read($dbConnect, $sql);

	// T_Order_Detailsの登録
	$countOrderDetail = count($orderDetails);
	for ($i=0; $i<$countOrderDetail; $i++) {

		// 返却・紛失・返却未申請の判定
		if (isset($post['returnChk'][$orderDetails[$i]['OrderDetID']]) && $post['returnChk'][$orderDetails[$i]['OrderDetID']] == '1') {
			$notReturnFlag = 1;		// 返却
		}
		elseif (isset($post['lostChk'][$orderDetails[$i]['OrderDetID']]) && $post['lostChk'][$orderDetails[$i]['OrderDetID']] == '1') {
			$notReturnFlag = 2;		// 紛失
		}


		// 汚損・破損の判定
		$isBroken = false;
		if (isset($post['brokenChk'][$orderDetails[$i]['OrderDetID']]) && $post['brokenChk'][$orderDetails[$i]['OrderDetID']] == '1') {
			$isBroken = true;
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
		$sql .= 		" BarCd,";
		$sql .= 		" IcTagCd,";
		$sql .= 		" Status,";
		$sql .= 		" DamageCheck,";
		$sql .= 		" AppliDay,";
		$sql .= 		" MotoOrderDetID,";
		$sql .= 		" Del,";
		$sql .= 		" RegistDay,";
		$sql .= 		" RegistUser";
		$sql .= 		" )";
		$sql .= " VALUES";
		$sql .= 		" (";
		$sql .= 		" '" . db_Escape($orderId) ."',";
		$sql .= 		" '" . db_Escape($post['requestNo']) ."',";
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

		// Status
		switch ($notReturnFlag) {
			case '1':
				$sql .= 		" " . STATUS_NOT_RETURN_ADMIT . ",";	// 未返却（承認済）は20
				break;

			case '2':
				$sql .= 		" " . STATUS_LOSS_ADMIT . ",";			// 紛失（承認済）は34
				break;

			default:
				break;
		}

		// DamageCheck
		if ($isBroken  == true) {
			$sql .= 		" 1,";
		}
		else {
			$sql .= 		" 0,";
		}


		$sql .= 		" GETDATE(),";
		$sql .= 		" '" . db_Escape(trim($orderDetails[$i]['OrderDetID'])) ."',";
		$sql .= 		" " . DELETE_OFF . ","; 	// DELの初期は0
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

		// T_Staff_Detailsの変更
		$sql  = "";
		$sql .= " UPDATE";
		$sql .= 	" T_Staff_Details";
		$sql .= " SET";
		$sql .= 	" ReturnDetID = '" . db_Escape($orderDetailId) ."',";

		// status
		switch ($notReturnFlag) {
			case '1':
				$sql .= 	" Status = " . STATUS_NOT_RETURN_ADMIT . ",";		// 未返却（承認済）は20
				break;

			case '2':
				$sql .= 	" Status = " . STATUS_LOSS_ADMIT . ",";				// 紛失（承認済）は34
				break;

			default:
				break;
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
 * 返却申請メールを送信する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$orderId   => OrderID
 * 戻り値：true：変更成功 / false：変更失敗
 *
 * create 2007/04/25 H.Osugi
 *
 */
function sendMailShinsei($dbConnect, $orderId) {

	$filePath = '../../mail_template/';

	// 申請メールの件名と本文を取得
	$isSuccess = henpinShinseiMail($dbConnect, $orderId, $filePath, $subject, $message);

	if ($isSuccess == false) {
		return false;
	}

	$toAddr = MAIL_GROUP_3;
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
 * 退職・異動アラートメールを送信する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$orderId   => OrderID
 *       ：$stockouts => 在庫切れ情報
 * 戻り値：true：変更成功 / false：変更失敗
 *
 * create 2007/06/27 H.Osugi
 *
 */
function sendMailStockOut($dbConnect, $orderId, $stockouts) {

	$filePath = '../../mail_template/';

	// 申請メールの件名と本文を取得
	$isSuccess = henpinStockOutMail($dbConnect, $orderId, $stockouts, $filePath, $subject, $message);

	if ($isSuccess == false) {
		return false;
	}

	$toAddr = MAIL_GROUP_5;
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
 * 返却申請一覧情報を取得する
 * 引数  ：$dbConnect    => コネクションハンドラ
 *       ：$post         => POST値
 * 戻り値：$result       => 在庫切れ商品一覧情報
 *
 * create 2007/06/27 H.Osugi
 *
 */
function getStockOut($dbConnect, $post) {

	$stockouts = array();

	$staffId = '';
	if (isset($post['staffId']) && trim($post['staffId']) != '') {
		$staffId = trim($post['staffId']);
	}
	else {
		return $stockouts;
	}

		$sql  = "";
		$sql .= " SELECT";
		$sql .= 	" tod.AppliNo,";
		$sql .= 	" tod.ItemNo,";
		$sql .= 	" tod.ItemName,";
		$sql .= 	" tod.Size";
		$sql .= " FROM";
		$sql .= 	" T_Staff_Details tsd";
		$sql .= " INNER JOIN";
		$sql .= 	" T_Order_Details tod";
		$sql .= " ON";
		$sql .= 	" tod.OrderDetID = tsd.OrderDetID";
		$sql .= " AND";
		$sql .= 	" tod.Del = " . DELETE_OFF;
		$sql .= " WHERE";
		$sql .= 	" tsd.StaffID = '" . db_Escape($staffId) . "'";
		$sql .= " AND";
		$sql .= 	" tsd.Status IN ('" . db_Escape(STATUS_STOCKOUT) . "', '" . db_Escape(STATUS_ORDER) . "')";
		$sql .= " AND";
		$sql .= 	" tsd.ReturnDetID IS NULL";
		$sql .= " AND";
		$sql .= 	" tsd.Del = " . DELETE_OFF;
		$sql .= " ORDER BY";
		$sql .= 	" tod.AppliNo ASC";

		$stockouts = db_Read($dbConnect, $sql);

		if (!isset($stockouts) || count($stockouts) <= 0) {
			$stockouts = array();
			return $stockouts;
		}

		return $stockouts;

}

?>
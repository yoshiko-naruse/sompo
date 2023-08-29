<?php
/*
 * 承認キャンセル完了画面
 * syounin_cancel_kanryo.src.php
 *
 * create 2007/04/24 H.Osugi
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

// 承認権限のないユーザが閲覧しようとするとTOPに強制遷移
if ($isLevelAdmin  == false && $isLevelAcceptation  == false) {

	$hiddens = array();
	redirectPost(HOME_URL . 'top.php', $hiddens);

}

// 変数の初期化 ここから *******************************************************
$orderId    = '';					// OrderID
$post       = $_POST;				// POST値
// 変数の初期化 ここまで ******************************************************

// 必要な値が取得できなければエラー画面へ遷移
if (!isset($_POST['orderId']) || trim($_POST['orderId']) == '') {

	$hiddens['errorName'] = 'syouninCancel';
	$hiddens['menuName']  = 'isMenuAcceptation';
	$hiddens['returnUrl'] = 'syounin/syounin.php';
	$hiddens['errorId'][] = '901';
	$errorUrl             = HOME_URL . 'error.php';

	// エラー画面に強制遷移
	redirectPost($errorUrl, $hiddens);

}

$cancelMode = trim($_POST['cancelMode']);
$orderId    = trim($_POST['orderId']);
if (isset($_POST['returnOrderId'])) {
	$returnOrderId = trim($_POST['returnOrderId']);
}

// トランザクション開始
db_Transaction_Begin($dbConnect);

// 状態の取得
$status = getOrderStatus($dbConnect, $orderId);

$isSuccess = true;
switch($status) {

	case STATUS_APPLI_DENY:					// 申請済（否認）
	case STATUS_APPLI_ADMIT:				// 申請済（承認済）
		$beforeStatus = STATUS_APPLI;		// 申請済（承認待ち）に戻す
		break;
		
	case STATUS_NOT_RETURN_DENY:			// 未返却（否認）
	case STATUS_NOT_RETURN_ADMIT:			// 未返却（承認済）
		$beforeStatus = STATUS_NOT_RETURN;	// 未返却（承認待ち）に戻す
		break;

	case STATUS_LOSS_DENY:					// 紛失（否認）
	case STATUS_LOSS_ADMIT:					// 紛失（承認済）
		$beforeStatus = STATUS_LOSS;		// 紛失（承認待ち）に戻す
		break;

	default:
		$isSuccess = false;
		break;

}

if ($isSuccess == true) {

	// 承認待ちに戻す
	$isSuccess = returnOrderStatus($dbConnect, $orderId, $beforeStatus);

	if (isset($returnOrderId) && $returnOrderId != '') {

		$returnStatus = getOrderStatus($dbConnect, $returnOrderId);
	
		switch($returnStatus) {
		
			case STATUS_APPLI_DENY:					// 申請済（否認）
			case STATUS_APPLI_ADMIT:				// 申請済（承認済）
				$beforeStatus = STATUS_APPLI;		// 申請済（承認待ち）に戻す
				break;
				
			case STATUS_NOT_RETURN_DENY:			// 未返却（否認）
			case STATUS_NOT_RETURN_ADMIT:			// 未返却（承認済）
				$beforeStatus = STATUS_NOT_RETURN;	// 未返却（承認待ち）に戻す
				break;
		
			case STATUS_LOSS_DENY:					// 紛失（否認）
			case STATUS_LOSS_ADMIT:					// 紛失（承認済）
				$beforeStatus = STATUS_LOSS;		// 紛失（承認待ち）に戻す
				break;
		
			default:
				$isSuccess = false;
				break;
		
		}

		if ($isSuccess == true) {
			// 承認待ちに戻す
			$isSuccess = returnOrderStatus($dbConnect, $returnOrderId, $beforeStatus);
		}
	
	}


}

// 登録が失敗した場合はエラー画面へ遷移
if ($isSuccess == false) {

	// ロールバック
	db_Transaction_Rollback($dbConnect);

	$hiddens['errorName'] = 'syouninCancel';
	$hiddens['menuName']  = 'isMenuAcceptation';
	$hiddens['returnUrl'] = 'syounin/syounin.php';
	$hiddens['errorId'][] = '901';
	$errorUrl             = HOME_URL . 'error.php';

	// エラー画面に強制遷移
	redirectPost($errorUrl, $hiddens);
}

// コミット
db_Transaction_Commit($dbConnect);

$countSearchStatus = count($post['searchStatus']);
for ($i=0; $i<$countSearchStatus; $i++) {
	$post['searchStatus[' . $i . ']'] = $post['searchStatus'][$i];
}

// POST値をHTMLエンティティ
$post = castHtmlEntity($post); 

$notArrowKeys = array('searchStatus');

$hiddenHtml = array();
if (is_array($post) && count($post) > 0) {
	$hiddenHtml = castHiddenError($post, $notArrowKeys);
}
 
$nextUrl = HOME_URL . 'syounin/syounin.php';

// 承認処理画面に強制遷移
redirectPost($nextUrl, $hiddenHtml);

/*
 * Statusを取得する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$orderId        => OrderID
 * 戻り値：状態
 *
 * create 2007/04/24 H.Osugi
 *
 */
function getOrderStatus($dbConnect, $orderId) {

	// Statusを取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" Status";
	$sql .= " FROM";
	$sql .= 	" T_Order";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;

	$orderDatas = db_Read($dbConnect, $sql);

	return $orderDatas[0]['Status'];

}

/*
 * T_Order T_Order_Detailsを論理削除する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$orderId        => OrderID
 * 戻り値：$isSuccess      => ture：キャンセル成功 / false：キャンセル失敗
 *
 * create 2007/04/24 H.Osugi
 *
 */
function returnOrderStatus($dbConnect, $orderId, $status) {

	// Statusを取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" AppliMode";
	$sql .= " FROM";
	$sql .= 	" T_Order";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;

	$orderDatas = db_Read($dbConnect, $sql);

	$appliMode = $orderDatas[0]['AppliMode'];

	// T_Orderを変更する
	$sql  = "";
	$sql .= " UPDATE";
	$sql .= 	" T_Order";
	$sql .= " SET";
	$sql .= 	" Status = " . db_Escape($status) . ",";
	$sql .= 	" AgreeReason = NULL,";
	$sql .= 	" AgreeUserID = NULL,";
	$sql .= 	" AgreeNameCd = NULL,";
	$sql .= 	" AgreeName = NULL,";
	$sql .= 	" AgreeDay = NULL,";
	$sql .= 	" UpdDay = GETDATE(),";
	$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;

	$isSuccess = db_Execute($dbConnect, $sql);

	// 実行結果が失敗の場合
	if ($isSuccess == false) {
		return false;
	}

	// 返却の場合のみ処理が異なる
	if ($appliMode == APPLI_MODE_RETURN) {

		// T_Staff_Detailsを変更する
		$sql  = "";
		$sql .= " UPDATE";
		$sql .= 	" T_Staff_Details";
		$sql .= " SET";
		$sql .= 	" Status = " . STATUS_NOT_RETURN . ",";		// 未返却（承認待ち）
		$sql .= 	" UpdDay = GETDATE(),";
		$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= " WHERE";
	
		$sql .= 	" ReturnDetID IN (";
		$sql .= 		" SELECT";
		$sql .= 			" OrderDetID";
		$sql .= 		" FROM";
		$sql .= 			" T_Order_Details";
		$sql .= 		" WHERE";
		$sql .= 			" OrderID = '" . db_Escape($orderId) . "'";
		$sql .= 		" AND";
		$sql .= 			" Del = " . DELETE_OFF;
		$sql .= 	" )";
		$sql .= " AND";
		$sql .= 	" Status IN (" . STATUS_NOT_RETURN_DENY . " ," . STATUS_NOT_RETURN_ADMIT . ")";			// 未返却（承認済）,未返却（否認）
		$sql .= " AND";
		$sql .= 	" Del = " . DELETE_OFF;

		$isSuccess = db_Execute($dbConnect, $sql);

		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}

		// T_Staff_Detailsを変更する
		$sql  = "";
		$sql .= " UPDATE";
		$sql .= 	" T_Staff_Details";
		$sql .= " SET";
		$sql .= 	" Status = " . STATUS_LOSS . ",";		// 紛失（承認待ち）
		$sql .= 	" UpdDay = GETDATE(),";
		$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= " WHERE";
	
		$sql .= 	" ReturnDetID IN (";
		$sql .= 		" SELECT";
		$sql .= 			" OrderDetID";
		$sql .= 		" FROM";
		$sql .= 			" T_Order_Details";
		$sql .= 		" WHERE";
		$sql .= 			" OrderID = '" . db_Escape($orderId) . "'";
		$sql .= 		" AND";
		$sql .= 			" Del = " . DELETE_OFF;
		$sql .= 	" )";
		$sql .= " AND";
		$sql .= 	" Status IN (" . STATUS_LOSS_DENY . " ," . STATUS_LOSS_ADMIT . ")";			// 紛失（承認済）,紛失（否認）
		$sql .= " AND";
		$sql .= 	" Del = " . DELETE_OFF;

		$isSuccess = db_Execute($dbConnect, $sql);
	
		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}

		// T_Order_Detailsを変更する
		$sql  = "";
		$sql .= " UPDATE";
		$sql .= 	" T_Order_Details";
		$sql .= " SET";
		$sql .= 	" Status = " . STATUS_NOT_RETURN . ",";		// 未返却（承認待ち）
		$sql .= 	" UpdDay = GETDATE(),";
		$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= " WHERE";
		$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
		$sql .= " AND";
		$sql .= 	" Status IN (" . STATUS_NOT_RETURN_DENY . " ," . STATUS_NOT_RETURN_ADMIT . ")";			// 未返却（承認済）,未返却（否認）
		$sql .= " AND";
		$sql .= 	" Del = " . DELETE_OFF;
	
		$isSuccess = db_Execute($dbConnect, $sql);
	
		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}

		// T_Order_Detailsを変更する
		$sql  = "";
		$sql .= " UPDATE";
		$sql .= 	" T_Order_Details";
		$sql .= " SET";
		$sql .= 	" Status = " . STATUS_LOSS . ",";		// 紛失（承認待ち）
		$sql .= 	" UpdDay = GETDATE(),";
		$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= " WHERE";
		$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
		$sql .= " AND";
		$sql .= 	" Status IN (" . STATUS_LOSS_DENY . " ," . STATUS_LOSS_ADMIT . ")";			// 紛失（承認済）,紛失（否認）
		$sql .= " AND";
		$sql .= 	" Del = " . DELETE_OFF;
	
		$isSuccess = db_Execute($dbConnect, $sql);
	
		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}

	}

	// 返却以外の処理
	else {

		// T_Staff_Detailsを変更する
		$sql  = "";
		$sql .= " UPDATE";
		$sql .= 	" T_Staff_Details";
		$sql .= " SET";
		$sql .= 	" Status = " . db_Escape($status) . ",";
		$sql .= 	" UpdDay = GETDATE(),";
		$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= " WHERE";
	
		if ($status == STATUS_APPLI) {
			$sql .= 	" OrderDetID IN (";
		}
		else {
			$sql .= 	" ReturnDetID IN (";
		}
	
		$sql .= 		" SELECT";
		$sql .= 			" OrderDetID";
		$sql .= 		" FROM";
		$sql .= 			" T_Order_Details";
		$sql .= 		" WHERE";
		$sql .= 			" OrderID = '" . db_Escape($orderId) . "'";
		$sql .= 		" AND";
		$sql .= 			" Del = " . DELETE_OFF;
		$sql .= 	" )";
		$sql .= " AND";
		$sql .= 	" Del = " . DELETE_OFF;
	
		$isSuccess = db_Execute($dbConnect, $sql);
	
		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}
	
		// T_Order_Detailsを変更する
		$sql  = "";
		$sql .= " UPDATE";
		$sql .= 	" T_Order_Details";
		$sql .= " SET";
		$sql .= 	" Status = " . db_Escape($status) . ",";
		$sql .= 	" UpdDay = GETDATE(),";
		$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= " WHERE";
		$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
		$sql .= " AND";
		$sql .= 	" Del = " . DELETE_OFF;
	
		$isSuccess = db_Execute($dbConnect, $sql);
	
		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}

	}

	return true;

}

?>
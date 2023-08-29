<?php
/*
 * キャンセル完了画面
 * cancel_kanryo.src.php
 *
 * create 2007/03/28 H.Osugi
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
require_once('../../include/checkReturn.php');			// 返却可能か判定モジュール
require_once('../../include/createHachuMail.php');		// 発注申請メール生成モジュール
require_once('../../include/createKoukanMail.php');		// 交換申請メール生成モジュール
require_once('../../include/createHenpinMail.php');		// 返却申請メール生成モジュール
require_once('../../include/sendTextMail.php');			// テキストメール送信モジュール

// 変数の初期化 ここから *******************************************************
$cancelMode = '';					// キャンセルモード
$orderId    = '';					// OrderID
$post       = $_POST;				// POST値
// 変数の初期化 ここまで ******************************************************

// 必要な値が取得できなければエラー画面へ遷移
if (!isset($_POST['cancelMode']) || trim($_POST['cancelMode']) == ''
	 || !isset($_POST['orderId']) || trim($_POST['orderId']) == '') {

	$hiddens['errorName'] = 'cancel';
	$hiddens['menuName']  = 'isMenuHistory';
	$hiddens['returnUrl'] = 'top.php';
	$hiddens['errorId'][] = '901';
	$errorUrl             = HOME_URL . 'error.php';

	// エラー画面に強制遷移
	redirectPost($errorUrl, $hiddens);

}

$cancelMode = trim($_POST['cancelMode']);
$orderId    = trim($_POST['orderId']);
if (isset($_POST['orderReturnId'])) {
	$orderReturnId = trim($_POST['orderReturnId']);
}

// トランザクション開始
db_Transaction_Begin($dbConnect);

switch($cancelMode) {

	case '1':		// 発注の場合
		$isSuccess = cancelOrder($dbConnect, $orderId);
		break;
		
	case '2':		// 交換の場合
		$isSuccess = cancelExchange($dbConnect, $orderId, $orderReturnId);
		break;

	case '3':		// 返却の場合
		$isSuccess = cancelReturn($dbConnect, $orderId);
		break;

	default:
		$isSuccess = false;
		break;

}

// 登録が失敗した場合はエラー画面へ遷移
if ($isSuccess == false) {

	// ロールバック
	db_Transaction_Rollback($dbConnect);

	$hiddens['errorName'] = 'cancel';
	$hiddens['menuName']  = '';
	$hiddens['returnUrl'] = 'top.php';
	$hiddens['errorId'][] = '901';
	$errorUrl             = HOME_URL . 'error.php';

	// エラー画面に強制遷移
	redirectPost($errorUrl, $hiddens);
}


// コミット
db_Transaction_Commit($dbConnect);

// キャンセルメールを送信する
switch($cancelMode) {

	case '1':		// 発注の場合
		$isSuccess = sendMailOrder($dbConnect, $orderId);
		break;
		
	case '2':		// 交換の場合
		$isSuccess = sendMailExchange($dbConnect, $orderId);
		break;

	case '3':		// 返却の場合
		$isSuccess = sendMailReturn($dbConnect, $orderId);
		break;

	default:
		$isSuccess = false;
		break;

}

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
 
$nextUrl = HOME_URL . 'rireki/rireki.php';

// 申請履歴画面に強制遷移
redirectPost($nextUrl, $hiddenHtml);

/*
 * キャンセル処理（発注の場合）
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$orderId        => OrderID
 * 戻り値：$isSuccess      => ture：キャンセル成功 / false：キャンセル失敗
 *
 * create 2007/03/28 H.Osugi
 *
 */
function cancelOrder($dbConnect, $orderId) {

	// T_Staff_Details の該当情報を論理削除する
	$isSuccess = cancelTStaffDetail($dbConnect, $orderId);

	if ($isSuccess == false) {
		return $isSuccess;
	}

	// T_Order T_Order_Details の該当情報を論理削除する
	$isSuccess = cancelTOrder($dbConnect, $orderId);

	return  $isSuccess;

}

/*
 * キャンセル処理（交換の場合）
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$orderId        => 発注のOrderID
 *       ：$orderReturnId  => 返却のOrderID
 * 戻り値：$isSuccess      => ture：キャンセル成功 / false：キャンセル失敗
 *
 * create 2007/03/28 H.Osugi
 *
 */
function cancelExchange($dbConnect, $orderId, $orderReturnId) {

	// T_Staff_Details の該当情報を論理削除する
	$isSuccess = cancelTStaffDetail($dbConnect, $orderId);

	if ($isSuccess == false) {
		return $isSuccess;
	}

	// T_Staff_Details の該当情報を返却前の情報に戻す
	$isSuccess = returnTStaffDetail($dbConnect, $orderReturnId);

	if ($isSuccess == false) {
		return $isSuccess;
	}

	// T_Order T_Order_Details の該当情報を論理削除する
	$isSuccess = cancelTOrder($dbConnect, $orderId);

	if ($isSuccess == false) {
		return $isSuccess;
	}

	// T_Order T_Order_Details の該当情報を論理削除する
	$isSuccess = cancelTOrder($dbConnect, $orderReturnId);

	return  $isSuccess;

}

/*
 * キャンセル処理（返却の場合）
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$orderId        => OrderID
 * 戻り値：$isSuccess      => ture：キャンセル成功 / false：キャンセル失敗
 *
 * create 2007/03/28 H.Osugi
 *
 */
function cancelReturn($dbConnect, $orderId) {

	// Statusを取得
	$status = getOrderStatus($dbConnect, $orderId) ;

	if ($status == '') {
		return false;
	}

	// T_Staff_Details の該当情報を返却前の情報に戻す
	$isSuccess = returnTStaffDetail($dbConnect, $orderId);

	if ($isSuccess == false) {
		return $isSuccess;
	}

	if ($status == STATUS_NOT_RETURN_ORDER) {		// 未返却（受注済）の場合はキャンセルに
		// T_Order T_Order_Details の該当情報をキャンセルに変更
		$isSuccess = returnTOrderCancel($dbConnect, $orderId);
	}
	else {
		// T_Order T_Order_Details の該当情報を論理削除する
		$isSuccess = cancelTOrder($dbConnect, $orderId);
	}

	return  $isSuccess;

}


/*
 * Statusを取得する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$orderId        => OrderID
 * 戻り値：$isSuccess      => ture：キャンセル成功 / false：キャンセル失敗
 *
 * create 2007/04/16 H.Osugi
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
 * create 2007/03/28 H.Osugi
 *
 */
function cancelTOrder($dbConnect, $orderId) {

	// TokIDを取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" ttk.TokID";
	$sql .= " FROM";
	$sql .= 	" T_Tok ttk";
	$sql .= " INNER JOIN";
	$sql .= 	" T_Order tor";
	$sql .= " ON";
	$sql .= 	" ttk.OrderID = tor.OrderID";
	$sql .= " AND";
	$sql .= 	" tor.Del = " . DELETE_OFF;
	$sql .= " WHERE";
	$sql .= 	" tor.OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" ttk.Del = " . DELETE_OFF;

	$orderDatas = db_Read($dbConnect, $sql);

	// TokIDが取得できた場合はT_Tokを論理削除
	if (isset($orderDatas[0]['TokID']) && $orderDatas[0]['TokID'] != '') {

		$tokID = $orderDatas[0]['TokID'];

		// T_Tokを論理削除する
		$sql  = "";
		$sql .= " UPDATE";
		$sql .= 	" T_Tok";
		$sql .= " SET";
		$sql .= 	" Del = " . DELETE_ON . ",";
		$sql .= 	" UpdDay = GETDATE(),";
		$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= " WHERE";
		$sql .= 	" TokID = '" . db_Escape($tokID) . "'";
	
		$isSuccess = db_Execute($dbConnect, $sql);
	
		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}

	}

	// T_Orderを論理削除する
	$sql  = "";
	$sql .= " UPDATE";
	$sql .= 	" T_Order";
	$sql .= " SET";
	$sql .= 	" Del = " . DELETE_ON . ",";
	$sql .= 	" UpdDay = GETDATE(),";
	$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";

	$isSuccess = db_Execute($dbConnect, $sql);

	// 実行結果が失敗の場合
	if ($isSuccess == false) {
		return false;
	}

	// T_Order_Detailsを論理削除する
	$sql  = "";
	$sql .= " UPDATE";
	$sql .= 	" T_Order_Details";
	$sql .= " SET";
	$sql .= 	" Del = " . DELETE_ON . ",";
	$sql .= 	" UpdDay = GETDATE(),";
	$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";

	$isSuccess = db_Execute($dbConnect, $sql);

	// 実行結果が失敗の場合
	if ($isSuccess == false) {
		return false;
	}

	return true;

}

/*
 * T_Order T_Order_Detailsをキャンセルにする
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$orderId        => OrderID
 * 戻り値：$isSuccess      => ture：キャンセル成功 / false：キャンセル失敗
 *
 * create 2007/04/16 H.Osugi
 *
 */
function returnTOrderCancel($dbConnect, $orderId) {

	// T_Orderをキャンセルする
	$sql  = "";
	$sql .= " UPDATE";
	$sql .= 	" T_Order";
	$sql .= " SET";
	$sql .= 	" Status = " . STATUS_CANCEL . ",";		// Statusをキャンセルに変更
	$sql .= 	" UpdDay = GETDATE(),";
	$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";

	$isSuccess = db_Execute($dbConnect, $sql);

	// 実行結果が失敗の場合
	if ($isSuccess == false) {
		return false;
	}

	// T_Order_Detailsを論理削除する
	$sql  = "";
	$sql .= " UPDATE";
	$sql .= 	" T_Order_Details";
	$sql .= " SET";
	$sql .= 	" Status = " . STATUS_CANCEL . ",";		// Statusをキャンセルに変更
	$sql .= 	" UpdDay = GETDATE(),";
	$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";

	$isSuccess = db_Execute($dbConnect, $sql);

	// 実行結果が失敗の場合
	if ($isSuccess == false) {
		return false;
	}

	return true;

}

/*
 * T_Staff_Detailsを論理削除する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$orderId        => OrderID
 * 戻り値：$isSuccess      => ture：キャンセル成功 / false：キャンセル失敗
 *
 * create 2007/03/28 H.Osugi
 *
 */
function cancelTStaffDetail($dbConnect, $orderId) {

	// T_Staff_Detailsを論理削除する
	$sql  = "";
	$sql .= " UPDATE";
	$sql .= 	" T_Staff_Details";
	$sql .= " SET";
	$sql .= 	" Del = " . DELETE_ON . ",";
	$sql .= 	" UpdDay = GETDATE(),";
	$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
	$sql .= " WHERE";
	$sql .= 	" OrderDetID IN (";
	$sql .= 		" SELECT";
	$sql .= 		" 	OrderDetID";
	$sql .= 		" FROM";
	$sql .= 			" T_Order_Details";
	$sql .= 		" WHERE";
	$sql .= 			" OrderID = '" . db_Escape($orderId) . "'";
	$sql .= 		" AND";
	$sql .= 			" Del = " . DELETE_OFF. ")";

	$isSuccess = db_Execute($dbConnect, $sql);

	// 実行結果が失敗の場合
	if ($isSuccess == false) {
		return false;
	}

	return true;

}

/*
 * T_Staff_Detailsを納品済みに戻す
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$orderId        => OrderID
 * 戻り値：$isSuccess      => ture：キャンセル成功 / false：キャンセル失敗
 *
 * create 2007/03/28 H.Osugi
 *
 */
function returnTStaffDetail($dbConnect, $orderId) {

	// Statusを取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" tod1.OrderDetID,";
	$sql .= 	" tod2.Status";
	$sql .= " FROM";
	$sql .= 	" T_Order_Details tod1";
	$sql .= " INNER JOIN";
	$sql .= 	" T_Order_Details tod2";
	$sql .= " ON";
	$sql .= 	" tod1.MotoOrderDetID = tod2.OrderDetID";
	$sql .= " AND";
	$sql .= 	" tod2.Del = " . DELETE_OFF;
	$sql .= " WHERE";
	$sql .= 	" tod1.OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" tod1.Del = " . DELETE_OFF;

	$result = db_Read($dbConnect, $sql);

	$countStatus = count($result);
	for ($i=0; $i<$countStatus; $i++) {

		// T_Staff_Detailsのstatusを納品済にする
		$sql  = "";
		$sql .= " UPDATE";
		$sql .= 	" T_Staff_Details";
		$sql .= " SET";
		$sql .= 	" ReturnDetID = NULL,";					// 返却時のOrderDetIDをNULLに変更
		$sql .= 	" Status = '" . db_Escape($result[$i]['Status']) . "',";
		$sql .= 	" UpdDay = GETDATE(),";
		$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= " WHERE";
		$sql .= 	" ReturnDetID = '" . db_Escape($result[$i]['OrderDetID']) . "'";
	
		$isSuccess = db_Execute($dbConnect, $sql);
	
		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}
	}

	return true;

}

/*
 * 発注キャンセルメールを送信する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$orderId   => OrderID
 * 戻り値：true：変更成功 / false：変更失敗
 *
 * create 2007/04/02 H.Osugi
 *
 */
function sendMailOrder($dbConnect, $orderId) {

	$filePath = '../../mail_template/';

	// キャンセルメールの件名と本文を取得
	$isSuccess = hachuCancelMail($dbConnect, $orderId, $filePath, $subject, $message, $tokFlg);

	if ($isSuccess == false) {
		return false;
	}

	$toAddr = MAIL_GROUP_1;

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

/*
 * 交換キャンセルメールを送信する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$orderId   => OrderID
 * 戻り値：true：変更成功 / false：変更失敗
 *
 * create 2007/04/25 H.Osugi
 *
 */
function sendMailExchange($dbConnect, $orderId) {

	$filePath = '../../mail_template/';

	// キャンセルメールの件名と本文を取得
	$isSuccess = koukanCancelMail($dbConnect, $orderId, $filePath, $subject, $message, $tokFlg);

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

/*
 * 返却キャンセルメールを送信する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$post      => POST値
 *       ：$orderId   => OrderID
 * 戻り値：true：変更成功 / false：変更失敗
 *
 * create 2007/04/25 H.Osugi
 *
 */
function sendMailReturn($dbConnect, $orderId) {

	$filePath = '../../mail_template/';

	// キャンセルメールの件名と本文を取得
	$isSuccess = henpinCancelMail($dbConnect, $orderId, $filePath, $subject, $message);

	if ($isSuccess == false) {
		return false;
	}

	$toAddr = MAIL_GROUP_3;
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
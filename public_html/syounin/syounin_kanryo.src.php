<?php
/*
 * 承認完了画面
 * syounin_kanryo.src.php
 *
 * create 2007/04/23 H.Osugi
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
require_once('../../include/getUser.php');				// ユーザ情報取得モジュール
require_once('./syounin.val.php');						// エラー判定モジュール

// 承認権限のないユーザが閲覧しようとするとTOPに強制遷移
if ($isLevelAdmin  == false && $isLevelAcceptation  == false) {

	$hiddens = array();
	redirectPost(HOME_URL . 'top.php', $hiddens);

}

// 変数の初期化 ここから *******************************************************
$post       = $_POST;				// POST値
// 変数の初期化 ここまで ******************************************************

// エラー判定
validatePostData($_POST);

// トランザクション開始
db_Transaction_Begin($dbConnect);

// 承認変更処理
$isSuccess = acceptationOrder($dbConnect, $post);

// 登録が失敗した場合はエラー画面へ遷移
if ($isSuccess == false) {

	// ロールバック
	db_Transaction_Rollback($dbConnect);

	$hiddens['errorName'] = 'syounin';
	$hiddens['menuName']  = 'isMenuAcceptation';
	$hiddens['returnUrl'] = 'syounin/syounin.php';
	$hiddens['errorId'][] = '903';
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

$post['searchFlg'] = '1';

// POST値をHTMLエンティティ
$post = castHtmlEntity($post); 

$notArrowKeys = array('searchStatus', 'acceptationY', 'acceptationN', 'reason', 'orderIds', 'returnOrderIds');

$hiddenHtml = array();
if (is_array($post) && count($post) > 0) {
	$hiddenHtml = castHiddenError($post, $notArrowKeys);
}

$nextUrl = HOME_URL . 'syounin/syounin.php';

// 承認画面に強制遷移
redirectPost($nextUrl, $hiddenHtml);

/*
 * 承認処理
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$post           => POST値
 * 戻り値：$isSuccess      => ture：承認成功 / false：承認失敗
 *
 * create 2007/04/23 H.Osugi
 *
 */
function acceptationOrder($dbConnect, $post) {

	$userName = getUserName($dbConnect, $_SESSION['NAMECODE'], 0);

	$countOrderId = count($post['orderIds']);
	for ($i=0; $i<$countOrderId; $i++) {

		// orderId
		$orderId = trim($post['orderIds'][$i]);

		$status = '';
		if (isset($post['acceptationY'][$orderId]) && trim($post['acceptationY'][$orderId]) != '') {
			$status = trim($post['acceptationY'][$orderId]);
		}
		if (isset($post['acceptationN'][$orderId]) && trim($post['acceptationN'][$orderId]) != '') {
			$status = trim($post['acceptationN'][$orderId]);
		}

		$reason = '';
		if (isset($post['reason'][$orderId]) && trim($post['reason'][$orderId]) != '') {
			$reason = trim($post['reason'][$orderId]);
		}

		$returnOrderId = '';
		if (isset($post['returnOrderIds'][$orderId]) && trim($post['returnOrderIds'][$orderId]) != '') {
			$returnOrderId = trim($post['returnOrderIds'][$orderId]);
		}

		// 規定の値で無かった場合は承認処理を行わない
		switch ($status) {
			case STATUS_APPLI_ADMIT:
			case STATUS_APPLI_DENY:
			case STATUS_NOT_RETURN_ADMIT:
			case STATUS_NOT_RETURN_DENY:
			case STATUS_LOSS_ADMIT:
			case STATUS_LOSS_DENY:
				break;
			default:
				$status = '';
				break;
		}
	
		if ($status == '') {
			continue;
		}

		$isSuccess = updateOrder($dbConnect, $orderId, $userName, $status, $reason);

		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}

		if ($returnOrderId != '') {

			$status = getOrderStatus ($dbConnect, $returnOrderId);

			$nextStatus = '';
			if (isset($post['acceptationY'][$orderId]) && trim($post['acceptationY'][$orderId]) != '') {

				$acceptation = true;

				switch ($status) {
					case STATUS_APPLI:
						$nextStatus = STATUS_APPLI_ADMIT;
						break;
					case STATUS_NOT_RETURN:
						$nextStatus = STATUS_NOT_RETURN_ADMIT;
						break;
					case STATUS_LOSS:
						$nextStatus = STATUS_LOSS_ADMIT;
						break;
					default:
						break;
				}

			}
			if (isset($post['acceptationN'][$orderId]) && trim($post['acceptationN'][$orderId]) != '') {

				$acceptation = false;

				switch ($status) {
					case STATUS_APPLI:
						$nextStatus = STATUS_APPLI_DENY;
						break;
					case STATUS_NOT_RETURN:
						$nextStatus = STATUS_NOT_RETURN_DENY;
						break;
					case STATUS_LOSS:
						$nextStatus = STATUS_LOSS_DENY;
						break;
					default:
						break;
				}

			}

			if ($nextStatus == '') {
				continue;
			}

			$isSuccess = updateOrder($dbConnect, $returnOrderId, $userName, $nextStatus, $reason, $acceptation);
	
			// 実行結果が失敗の場合
			if ($isSuccess == false) {
				return false;
			}
		}

	}


	return true;

}

/*
 * 承認処理
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$post           => POST値
 * 戻り値：$isSuccess      => ture：承認成功 / false：承認失敗
 *
 * create 2007/04/23 H.Osugi
 *
 */
function updateOrder($dbConnect, $orderId, $userName ,$status, $reason, $acceptation = true) {

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
	$sql .= 	" AgreeReason = '" . db_Escape($reason) . "',";
	$sql .= 	" AgreeUserID = '" . db_Escape($_SESSION['USERID']) . "',";
	$sql .= 	" AgreeNameCd = '" . db_Escape($_SESSION['NAMECODE']) . "',";
	$sql .= 	" AgreeName = '" . db_Escape($userName) . "',";
	$sql .= 	" AgreeDay = GETDATE(),";
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

		switch ($status) {
			case STATUS_NOT_RETURN_ADMIT:
			case STATUS_LOSS_ADMIT:
				$returnStatus = STATUS_NOT_RETURN_ADMIT;
				$lossStatus   = STATUS_LOSS_ADMIT;
				break;
			case STATUS_NOT_RETURN_DENY:
			case STATUS_LOSS_DENY:
				$returnStatus = STATUS_NOT_RETURN_DENY;
				$lossStatus   = STATUS_LOSS_DENY;
				break;
			default:
				break;
		}

		// T_Staff_Detailsを変更する
		$sql  = "";
		$sql .= " UPDATE";
		$sql .= 	" T_Staff_Details";
		$sql .= " SET";
		$sql .= 	" Status = " . db_Escape($returnStatus) . ",";
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
		$sql .= 	" Status = " . STATUS_NOT_RETURN;			// 未返却（承認待ち）
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
		$sql .= 	" Status = " . db_Escape($lossStatus) . ",";
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
		$sql .= 	" Status = " . STATUS_LOSS;			// 紛失（承認待ち）
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
		$sql .= 	" Status = " . db_Escape($returnStatus) . ",";
		$sql .= 	" UpdDay = GETDATE(),";
		$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= " WHERE";
		$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
		$sql .= " AND";
		$sql .= 	" Status = " . STATUS_NOT_RETURN;			// 未返却（承認待ち）
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
		$sql .= 	" Status = " . db_Escape($lossStatus) . ",";
		$sql .= 	" UpdDay = GETDATE(),";
		$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= " WHERE";
		$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
		$sql .= " AND";
		$sql .= 	" Status = " . STATUS_LOSS;			// 紛失（承認待ち）
		$sql .= " AND";
		$sql .= 	" Del = " . DELETE_OFF;
	
		$isSuccess = db_Execute($dbConnect, $sql);
	
		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}

	}

	// 交換で否認の場合
	elseif ($appliMode == APPLI_MODE_EXCHANGE && $acceptation == false) {

		if ($status == STATUS_APPLI_ADMIT || $status == STATUS_APPLI_DENY) {

			// T_Staff_Detailsを変更する
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

		}
		else {

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

				// T_Staff_Detailsを変更する
				$sql  = "";
				$sql .= " UPDATE";
				$sql .= 	" T_Staff_Details";
				$sql .= " SET";
				$sql .= 	" ReturnDetID = NULL,";
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
	
		if ($status == STATUS_APPLI_ADMIT || $status == STATUS_APPLI_DENY) {
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

?>
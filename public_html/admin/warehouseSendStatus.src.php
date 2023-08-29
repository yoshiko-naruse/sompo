<?php

/*
 * 特寸一覧画面
 * tokusunSendStatus.src.php
 *
 * create 2009/01/26 W.Takasaki
 *
 *
 */

// 各種モジュールの取得
include_once('../../include/define.php');               // 定数定義
include_once('../../include/dbConnect.php');            // DB接続モジュール
include_once('../../include/msSqlControl.php');         // DB操作モジュール
include_once('../../include/checkLogin.php');           // ログイン判定モジュール
include_once('../../include/castHtmlEntity.php');       // HTMLエンティティモジュール
include_once('../../include/redirectPost.php');         // リダイレクトポストモジュール
include_once('../../include/setPaging.php');            // ページング情報セッティングモジュール
include_once('../../error_message/errorMessage.php');   // エラーメッセージ

// 変数の初期化 ここから ******************************************************
$searchAppliNo      = '';                   // 申請番号
$searchStaffCode    = '';                   // 社員コード
$searchStaffName    = '';                   // 社員名
$searchCompCd    	= '';                   // 所属コード
$searchCompName    	= '';                   // 店舗名
$isSearched         = false;                // 検索フラグ
$isRegist         	= false;                // 登録フラグ

$isMenuAdmin = true;	// 着用者状況のメニューをアクティブに

// 変数の初期化 ここまで ******************************************************

// 管理権限でなければトップに
if ($isLevelAdmin == false) {

	$returnUrl = HOME_URL . 'top.php';
	
	// TOP画面に強制遷移
	redirectPost($returnUrl, $hiddens);

} 

$post = castHtmlEntity($_POST);

// 登録ボタン判定
if(isset($post['regist']) && $post['regist'] == 1 ){

	if ((isset($post['ready']) && is_array($post['ready'])) || (isset($post['notReady']) && is_array($post['notReady']))) {
		registReadyFlg($dbConnect, $_POST);
	}

	$isRegist = true;
}


// 検索ボタン判定
if(isset($post['searchFlg']) && $post['searchFlg'] == 1 ){

    $nowPage = 1;
    if ($post['nowPage'] != '') {
        $nowPage = trim($post['nowPage']);
    }

    if ($post['initializePage'] == 1) {
        $nowPage = 1;
    }
    
    // 表示する注文履歴一覧を取得
    $orders = getOrder($dbConnect, $_POST, $nowPage, $DISPLAY_STATUS, $allCount);
    
    // ページングのセッティング
    $paging = setPaging($nowPage, PAGE_PER_DISPLAY_HISTORY, $allCount);
    
    $isSearched = true;

    $searchAppliNo      = $post['searchAppliNo'];                   // 申請番号
    $searchStaffCode    = $post['searchStaffCode'];                 // 社員コード

	$searchStaffName    = $post['searchStaffName'];					// 社員名
	$searchCompCd   	= $post['searchCompCd'];					// 所属コード
	$searchCompName   	= $post['searchCompName'];					// 店舗名
}





function getOrder($dbConnect, $post, $nowPage, $DISPLAY_STATUS ,&$allCount) {

    // 初期化
    $appliNo      	= '';
    $staffCode    	= '';
    $staffName    	= '';
    $compCd    		= '';
    $compName    	= '';
    $limit        	= '';
    $offset       	= '';

    // 取得したい件数
    $limit = PAGE_PER_DISPLAY_HISTORY;      // 1ページあたりの表示件数;

    // 取得したいデータの開始位置
    $offset = ($nowPage - 1) * PAGE_PER_DISPLAY_HISTORY;

    // 申請番号
    $appliNo = $post['searchAppliNo'];

    // 社員コード
    $staffCode = $post['searchStaffCode'];

    // 社員名
    $staffName = $post['searchStaffName'];

    // 所属コード
    $compCd = $post['searchCompCd'];

    // 店舗名
    $compName = $post['searchCompName'];

    // 発注申請の件数を取得する
    $sql  = "";
    $sql .= " SELECT";
    $sql .=     " COUNT(DISTINCT tod.OrderDetID) as count_order";
    $sql .= " FROM";
    $sql .=     " T_Order_Details tod";

    $sql .= " INNER JOIN";
    $sql .=     " T_Order tor";
    $sql .= " ON";
    $sql .=     " tor.OrderID = tod.OrderID";
    $sql .= " AND";
    $sql .=     " tor.Del = " . DELETE_OFF;

    $sql .= " WHERE";

    $sql .=     " tod.Del = " . DELETE_OFF;

    // 発注申請のみ
    $sql .= " AND";
    $sql .=     " (tod.Status = " . STATUS_APPLI_ADMIT . " OR tod.Status = " . STATUS_STOCKOUT . ")";

	//// 特寸のみ
    //$sql .= " AND";
    //$sql .=     " (tod.Size like '%" . TOKUSUN_SIZE_NAME . "%')";

	// 修理申請のみ
    $sql .= " AND";
    $sql .=     " tor.AppliReason = '" . APPLI_REASON_EXCHANGE_REPAIR . "'";

    // 申請番号を前方一致
    if ($appliNo != '') {
        $sql .= " AND";
        $sql .=     " tor.AppliNo LIKE ('" . db_Like_Escape($appliNo) . "%')";
    }

	// 社員コード
    if ($staffCode != '') {
        $sql .= " AND";
        $sql .=     " tor.StaffCode = '" . db_Escape($staffCode) . "'";
    }

	// 社員名
    if ($staffName != '') {
        $sql .= " AND";
        $sql .=     " tor.PersonName LIKE '%" . db_Escape($staffName) . "%'";
    }

	// 所属コード
    if ($compCd != '') {
        $sql .= " AND";
        $sql .=     " tor.AppliCompCd = '" . db_Escape($compCd) . "'";
    }

	// 店舗名
    if ($compName != '') {
        $sql .= " AND";
        $sql .=     " tor.AppliCompName LIKE '%" . db_Escape($compName) . "%'";
    }

//var_dump($sql);die;
    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合
    $allCount = 0;
    if (!isset($result[0]['count_order']) || $result[0]['count_order'] <= 0) {
        $result = array();
        return $result;
    }

    // 全件数
    $allCount = $result[0]['count_order'];

    $top = $offset + $limit;
    if ($top > $allCount) {
        $limit = $limit - ($top - $allCount);
		if ($limit < 0) {
			$result = array();
			return $result;
		}
        $top   = $allCount;
    }

    // 注文履歴の一覧を取得する
    $sql  = "";
    $sql .= " SELECT";
    $sql .=     " tod.OrderDetID,";
    $sql .=     " tor.OrderID,";
    $sql .=     " tor.AppliNo,";
    $sql .=     " tor.AppliDay,";
    $sql .=     " tor.AppliNo,";
    $sql .=     " tor.AppliCompCd,";
    $sql .=     " tor.AppliCompName,";
    $sql .=     " tor.StaffCode,";
    $sql .=     " tor.PersonName,";
    $sql .=     " tor.AppliMode,";
    $sql .=     " tor.AppliReason,";
    $sql .=     " tod.Status,";
    $sql .=     " tor.ShipDay,";
    $sql .=     " tod.ItemNo,";
    $sql .=     " tod.ItemName,";
    $sql .=     " tod.Size,";
    $sql .=     " tod.TokOrderFlg,";
    $sql .=     " ISNULL(tod.WaitFlag, 0) AS WaitFlag";
    $sql .= " FROM";
    $sql .=     " (";
    $sql .=         " SELECT";
    $sql .=             " TOP " . $limit;
    $sql .=             " *";
    $sql .=         " FROM";
    $sql .=             " T_Order_Details tod2";
    $sql .=         " WHERE";
    $sql .=             " tod2.OrderDetID IN (";
    $sql .=                         " SELECT";
    $sql .=                             " OrderDetID";
    $sql .=                         " FROM";
    $sql .=                             " (";
    $sql .=                                 " SELECT";
    $sql .=                                     " DISTINCT";
    $sql .=                                     " TOP " . ($top);
    $sql .=                                     " tod3.OrderID,";
    $sql .=                                     " tod3.OrderDetID,";
    $sql .=                                     " tor3.AppliDay";
    $sql .=                                 " FROM";
    $sql .=                                     " T_Order_Details tod3";

    $sql .=                                 " INNER JOIN";
    $sql .=                                     " T_Order tor3";
    $sql .=                                 " ON";
    $sql .=                                     " tor3.OrderID = tod3.OrderID";
    $sql .=                                 " AND";
    $sql .=                                     " tor3.Del = " . DELETE_OFF;

    $sql .=                                 " WHERE";
    $sql .=                                     " tod3.Del = " . DELETE_OFF;

	// 確認済もしくは在庫切
    $sql .= 								" AND";
    $sql .=     								" (tod3.Status = " . STATUS_APPLI_ADMIT . " OR tod3.Status = " . STATUS_STOCKOUT . ")";

	//// 特寸のみ
    //$sql .= 								" AND";
    //$sql .=     								" (tod3.Size like '%" . TOKUSUN_SIZE_NAME . "%')";

	// 修理申請のみ
    $sql .= 								" AND";
    $sql .= 								    " tor3.AppliReason = '" . APPLI_REASON_EXCHANGE_REPAIR . "'";
    
    // 申請番号を前方一致
    if ($appliNo != '') {
        $sql .=                             " AND";
        $sql .=                                 " tor3.AppliNo LIKE ('" . db_Like_Escape($appliNo) . "%')";
    }

	// 社員コード
    if ($staffCode != '') {
        $sql .= 							" AND";
        $sql .= 								" tor3.StaffCode = '" . db_Escape($staffCode) . "'";
    }
	// 社員名
    if ($staffName != '') {
        $sql .= 							" AND";
        $sql .= 								" tor3.PersonName LIKE '%" . db_Escape($staffName) . "%'";
    }

	// 所属コード
    if ($compCd != '') {
        $sql .= 							" AND";
        $sql .= 								" tor3.AppliCompCd = '" . db_Escape($compCd) . "'";
    }
	// 店舗名
    if ($compName != '') {
        $sql .= 							" AND";
        $sql .= 								" tor3.AppliCompName LIKE '%" . db_Escape($compName) . "%'";
    }

    $sql .=                                 " ORDER BY";
    //$sql .=                                     " tor3.AppliDay DESC,";
    $sql .=                                     " tod3.OrderID DESC,";
    $sql .=                                     " tod3.OrderDetID DESC";
    $sql .=                             " ) tor4";
    $sql .=                         " )";
    $sql .=                 " ORDER BY";
    //$sql .=                     " tod2.RegistDay ASC,";
    $sql .=                     " tod2.OrderID ASC,";
    $sql .=                     " tod2.OrderDetID ASC";

    $sql .=     " ) tod";
    $sql .=     " INNER JOIN";
    $sql .=     " T_Order tor";
    $sql .=     " ON";
    $sql .=     	" tod.OrderID = tor.OrderID";
    $sql .=     " AND";
    $sql .=     	" tor.Del = " . DELETE_OFF;
    $sql .=     " INNER JOIN";
    $sql .=     " T_Staff tst";
    $sql .=     " ON";
    $sql .=     	" tor.StaffCode = tst.StaffCode";
    $sql .=     " AND";
    $sql .=     	" tst.Del = " . DELETE_OFF;

    $sql .=     " ORDER BY";
    $sql .=         " tor.AppliDay DESC,";
    $sql .=         " tor.OrderID DESC,";
    $sql .=         " tod.AppliLNo,";
    $sql .=         " tod.ItemNo";

//var_dump($sql);
    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合
    if (count($result) <= 0) {
        $result = array();
        return $result;
    }

    // 取得した値をHTMLエンティティ
    $resultCount  = count($result);
    $tempStoreAry = array();
    $tempIdxAry   = array();
    for ($i=0; $i<$resultCount; $i++) {
        $result[$i]['requestDay'] = strtotime($result[$i]['AppliDay']);
        $result[$i]['requestNo']  = $result[$i]['AppliNo'];
        $result[$i]['orderDetId']    = $result[$i]['OrderDetID'];
        $result[$i]['orderId']    = $result[$i]['OrderID'];
        $result[$i]['CompCd']     = castHtmlEntity($result[$i]['AppliCompCd']);
        $result[$i]['CompName']   = castHtmlEntity($result[$i]['AppliCompName']);
        $result[$i]['staffCode']  = castHtmlEntity($result[$i]['StaffCode']);
        $result[$i]['staffName']  = castHtmlEntity($result[$i]['PersonName']);
        $result[$i]['itemNo']  = castHtmlEntity($result[$i]['ItemNo']);
        $result[$i]['itemName']  = castHtmlEntity($result[$i]['ItemName']);
        $result[$i]['sizeOrder']  = castHtmlEntity($result[$i]['Size']);

        // 申請番号の遷移先決定
        $result[$i]['isAppli'] = false;
        if (ereg('^A.*$', $result[$i]['AppliNo'])) {
            $result[$i]['isAppli'] = true;
        }

        // 出荷日
        $result[$i]['isEmptyShipDay'] = true;
        if (isset($result[$i]['ShipDay']) && $result[$i]['ShipDay'] != '') {
            $result[$i]['ShipDay']   = strtotime($result[$i]['ShipDay']);
            $result[$i]['isEmptyShipDay'] = false;
        }

        // 返却日
        $result[$i]['isEmptyReturnDay'] = true;
        if (isset($result[$i]['ReturnDay']) && $result[$i]['ReturnDay'] != '') {
            $result[$i]['ReturnDay']  = strtotime($result[$i]['ReturnDay']);
            $result[$i]['isEmptyReturnDay'] = false;
        }

        // 区分
        $result[$i]['divisionOrder']    = false;
        $result[$i]['divisionExchange'] = false;
        $result[$i]['divisionReturn']   = false;
        switch ($result[$i]['AppliMode']) {
            case APPLI_MODE_ORDER:                      // 発注
			case APPLI_MODE_PROXY_ORDER:				// 代理発注
                $result[$i]['divisionOrder']    = true;
                break;
            case APPLI_MODE_EXCHANGE:                   // 交換
			case APPLI_MODE_PROXY_EXCHANGE:				// 代理交換
                $result[$i]['divisionExchange'] = true;
                break;
            case APPLI_MODE_RETURN:                     // 返却
			case APPLI_MODE_PROXY_RETURN:				// 代理返却
                $result[$i]['divisionReturn']   = true;
                break;
            default:
                break;
        }

        // 状態
        $result[$i]['status']  = castHtmlEntity($DISPLAY_STATUS[$result[$i]['Status']]);

		// チェックボックスの有無
		$result[$i]['dispBox'] = true;

        // 状態の文字列の色
        $result[$i]['statusIsBlue']  = false;
        $result[$i]['statusIsRed']   = false;
        $result[$i]['statusIsTeal']  = false;
        $result[$i]['statusIsGreen'] = false;
        $result[$i]['statusIsGray']  = false;
        $result[$i]['statusIsPink']  = false;
        $result[$i]['statusIsBlack'] = false;
        switch ($result[$i]['Status']) {
            case STATUS_APPLI:                          // 申請済（承認待ち）
            case STATUS_STOCKOUT:                       // 在庫切れ
                $result[$i]['statusIsGray']  = true;
                break;
            case STATUS_APPLI_DENY:                     // 申請済（否認）
                $result[$i]['statusIsPink']  = true;
                break;
            case STATUS_APPLI_ADMIT:                    // 申請済（承認済）
                $result[$i]['statusIsGreen'] = true;
                break;
            case STATUS_ORDER:                          // 受注済
				$result[$i]['dispBox'] = false;
                $result[$i]['statusIsBlue']  = true;
                break;
            case STATUS_KEEP:
                $result[$i]['statusIsBlack'] = true;
				$result[$i]['dispBox'] = true;
				break;
            default:
                $result[$i]['statusIsBlack'] = true;
				$result[$i]['dispBox'] = false;
                break;
        }

		// 修理アイテム引き当て準備フラグ
		$result[$i]['isReady'] = false;
		if (trim($result[$i]['WaitFlag']) == 0) {
			$result[$i]['isReady'] = true;
		}
    }
    return  $result;

}


function registReadyFlg($dbConnect, $post) {
	
	//　発送準備ができたデータ
	if (isset($post['ready']) && is_array($post['ready']) && $post['ready']) {
		$idAry = array();
		foreach ($post['ready'] as $key => $value) {
			if (trim($value) != '') {
				$idAry[] = $value;
			}
		}

		if (!empty($idAry)) {
			$updateId = implode(', ', $idAry);

			$sql  = " UPDATE";
			$sql .= 	" T_Order_Details";
			$sql .= " SET";
			$sql .= 	" WaitFlag = NULL";
			$sql .= " WHERE";
			$sql .= 	" OrderDetID IN (" . $updateId . ")";
			$sql .= " AND";
			$sql .= 	" Del = " . DELETE_OFF;

			$isSuccess = db_Execute($dbConnect, $sql);

			// 実行結果が失敗の場合
			if ($isSuccess == false) {
				return false;
			}
		}
	}

	//　発送準備ができていないデータ
	if (isset($post['notReady']) && is_array($post['notReady']) && $post['notReady']) {
		$idAry = array();
		foreach ($post['notReady'] as $key => $value) {
			if (trim($value) != '') {
				$idAry[] = $value;
			}
		}

		if (!empty($idAry)) {
			$not_updateId = implode(', ', $idAry);

			$sql  = " UPDATE";
			$sql .= 	" T_Order_Details";
			$sql .= " SET";
			$sql .= 	" WaitFlag = " . ORDER_WAIT_FLAG;
			$sql .= " WHERE";
			$sql .= 	" OrderDetID IN (" . $not_updateId . ")";
			$sql .= " AND";
			$sql .= 	" Del = " . DELETE_OFF;

			$isSuccess = db_Execute($dbConnect, $sql);

			// 実行結果が失敗の場合
			if ($isSuccess == false) {
				return false;
			}
		}
	}

	return true;
}

?>
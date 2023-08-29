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

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
  <head>
    <META http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <script src="//code.jquery.com/jquery-1.12.4.min.js"></script>
    <link href="/main.css" rel="stylesheet" type="text/css">
    <title>制服管理システム</title>
    <script language="JavaScript">
    <!--
    function change_data(oid,as,ccd,sid,no,url) {

      document.pagingForm.orderId.value=oid; 
      document.pagingForm.seasonMode.value=as; 
      document.pagingForm.syokukai.value=ccd;
      document.pagingForm.uid.value=sid;
      document.pagingForm.AppliNo.value=no;
      document.pagingForm.action=url; 
      document.pagingForm.submit();
      return false;

    }

    function checkReady(detailId) {
		var id 			= 'ready_' + detailId;
		var hiddenId 	= 'notReady_' + detailId;
		var checkForm 	= document.getElementById(id);
		var hiddenForm 	= document.getElementById(hiddenId);
		if (checkForm.checked) {
			hiddenForm.value = '';
		} else {
			hiddenForm.value = detailId;
		}

    }
    // -->
    </script>
  </head>
<?php if(!$isLevelAdmin) { ?>
<?php if(!$isLevelSyonin) { ?>
  <body onLoad="document.pagingForm.searchAppliNo.focus()">
<?php } ?>
<?php if($isLevelSyonin) { ?>
  <body onLoad="document.pagingForm.searchStaffCode.focus()">
<?php } ?>
<?php } ?>
<?php if($isLevelAdmin) { ?>
  <body onLoad="document.pagingForm.searchStaffCode.focus()">
<?php } ?>
    <div id="main">
      <div align="center">
      <div id="inner_main">
<?php if(!$isLogin) { ?>
        <table border="0" cellpadding="0" cellspacing="0" class="tb_login">
          <tr>
            <td colspan="7"><a href="<?php isset($homeUrl) ? print($homeUrl) : print('&#123;homeUrl&#125;'); ?>login.php"><img src="/img/logo.gif" alt="logo" width="163" height="42" border="0"></a><img src="/img/logo_02.gif" width="569" height="42"></td>
          </tr>
<?php } ?>
<?php if($isLogin) { ?>
       <form method="post" name="grobalMenuForm">
        <table border="0" cellpadding="0" cellspacing="0">
          <tr>
<?php if($isLevelAdmin) { ?>
            <td colspan="8">
<?php } ?>
<?php if($isLevelNormal) { ?>
            <td colspan="8">
<?php } ?>
              <a href="<?php isset($homeUrl) ? print($homeUrl) : print('&#123;homeUrl&#125;'); ?>top.php"><img src="/img/logo.gif" alt="logo" width="163" height="42" border="0"></a><img src="/img/logo_02.gif" width="569" height="42">
            </td>
          </tr>
<?php } ?>
<?php if($isLogin) { ?>
          <tr>
    </script>

    <input type="hidden" name="appliReason">

            

<?php if($isLevelAdmin) { ?>
<?php if(!$isLevelHonbu) { ?>
<?php if(!$isLevelSyonin) { ?>
<?php if($isMenuOrder) { ?>
            <td><a href="/hachu/hachu_top.php"><img src="/img/bt_06-2.gif" alt="代理発注"  border="0"></a></td>
<?php } ?>
<?php if(!$isMenuOrder) { ?>
            <td><a href="/hachu/hachu_top.php"><img src="/img/bt_06.gif" alt="代理発注"  border="0"></a></td>
<?php } ?>
<?php if($isMenuExchange) { ?>
            <td><a href="/koukan/koukan_top.php"><img src="/img/bt_07-2.gif" alt="代理交換"  border="0"></a></td>
<?php } ?>
<?php if(!$isMenuExchange) { ?>
            <td><a href="/koukan/koukan_top.php"><img src="/img/bt_07.gif" alt="代理交換"  border="0"></a></td>
<?php } ?>
<?php if($isMenuReturn) { ?>
            <td><a href="/henpin/henpin_top.php"><img src="/img/bt_08-2.gif" alt="代理返却"  border="0"></a></td>
<?php } ?>
<?php if(!$isMenuReturn) { ?>
            <td><a href="/henpin/henpin_top.php"><img src="/img/bt_08.gif" alt="代理返却"  border="0"></a></td>
<?php } ?>
<?php if($isMenuCondition) { ?>
            <td><a href="/chakuyou/chakuyou.php"><img src="/img/bt_03-2.gif" alt="貸与内容"  border="0"></a></td>
<?php } ?>
<?php if(!$isMenuCondition) { ?>
            <td><a href="/chakuyou/chakuyou.php"><img src="/img/bt_03.gif" alt="貸与内容"  border="0"></a></td>
<?php } ?>
<?php if($isMenuHistory) { ?>
            <td><a href="/rireki/rireki.php"><img src="/img/bt_04-2.gif" alt="申請履歴"  border="0"></a></td>
<?php } ?>
<?php if(!$isMenuHistory) { ?>
            <td><a href="/rireki/rireki.php"><img src="/img/bt_04.gif" alt="申請履歴"  border="0"></a></td>
<?php } ?>
<?php if($isMenuAcceptation) { ?>
            <td><a href="/syounin/syounin.php"><img src="/img/bt_09-2.gif" alt="承認" border="0"></td>
<?php } ?>
<?php if(!$isMenuAcceptation) { ?>
            <td><a href="/syounin/syounin.php"><img src="/img/bt_09.gif" alt="承認" border="0"></td>
<?php } ?>
<?php if($isMenuAdmin) { ?>
            <td><a href="/admin/admin_select.php"><img src="/img/bt_10-2.gif" alt="管理機能"  border="0"></td>
<?php } ?>
<?php if(!$isMenuAdmin) { ?>
            <td><a href="/admin/admin_select.php"><img src="/img/bt_10.gif" alt="管理機能"  border="0"></td>
<?php } ?>
<?php if($isMenuManual) { ?>
            <td><a href="<?php isset($manualUrl) ? print($manualUrl) : print('&#123;manualUrl&#125;'); ?>"><img src="/img/bt_05-2.gif" alt="マニュアル" border="0"></td>
<?php } ?>
<?php if(!$isMenuManual) { ?>
            <td><a href="<?php isset($manualUrl) ? print($manualUrl) : print('&#123;manualUrl&#125;'); ?>"><img src="/img/bt_05.gif" alt="マニュアル" border="0"></td>
<?php } ?>
<?php } ?>

<?php if($isLevelSyonin) { ?>
<?php if($isMenuOrder) { ?>
            <td><a href="/hachu/hachu_top.php"><img src="/img/bt_06-2.gif" alt="代理発注"  border="0"></a></td>
<?php } ?>
<?php if(!$isMenuOrder) { ?>
            <td><a href="/hachu/hachu_top.php"><img src="/img/bt_06.gif" alt="代理発注"  border="0"></a></td>
<?php } ?>
<?php if($isMenuExchange) { ?>
            <td><a href="/koukan/koukan_top.php"><img src="/img/bt_07-2.gif" alt="代理交換"  border="0"></a></td>
<?php } ?>
<?php if(!$isMenuExchange) { ?>
            <td><a href="/koukan/koukan_top.php"><img src="/img/bt_07.gif" alt="代理交換"  border="0"></a></td>
<?php } ?>
<?php if($isMenuReturn) { ?>
            <td><a href="/henpin/henpin_top.php"><img src="/img/bt_08-2.gif" alt="代理返却"  border="0"></a></td>
<?php } ?>
<?php if(!$isMenuReturn) { ?>
            <td><a href="/henpin/henpin_top.php"><img src="/img/bt_08.gif" alt="代理返却"  border="0"></a></td>
<?php } ?>
<?php if($isMenuCondition) { ?>
            <td><a href="/chakuyou/chakuyou.php"><img src="/img/bt_03-2.gif" alt="貸与内容"  border="0"></a></td>
<?php } ?>
<?php if(!$isMenuCondition) { ?>
            <td><a href="/chakuyou/chakuyou.php"><img src="/img/bt_03.gif" alt="貸与内容"  border="0"></a></td>
<?php } ?>
<?php if($isMenuHistory) { ?>
            <td><a href="/rireki/rireki.php"><img src="/img/bt_04-2.gif" alt="申請履歴"  border="0"></a></td>
<?php } ?>
<?php if(!$isMenuHistory) { ?>
            <td><a href="/rireki/rireki.php"><img src="/img/bt_04.gif" alt="申請履歴"  border="0"></a></td>
<?php } ?>
            <td><img src="/img/bt_00.gif" border="0"></td>
<?php if($isMenuAdmin) { ?>
            <td><a href="/admin/admin_select.php"><img src="/img/bt_10-2.gif" alt="管理機能"  border="0"></td>
<?php } ?>
<?php if(!$isMenuAdmin) { ?>
            <td><a href="/admin/admin_select.php"><img src="/img/bt_10.gif" alt="管理機能"  border="0"></td>
<?php } ?>
<?php if($isMenuManual) { ?>
            <td><a href="<?php isset($manualUrl) ? print($manualUrl) : print('&#123;manualUrl&#125;'); ?>"><img src="/img/bt_05-2.gif" alt="マニュアル" border="0"></td>
<?php } ?>
<?php if(!$isMenuManual) { ?>
            <td><a href="<?php isset($manualUrl) ? print($manualUrl) : print('&#123;manualUrl&#125;'); ?>"><img src="/img/bt_05.gif" alt="マニュアル" border="0"></td>
<?php } ?>
<?php } ?>
<?php } ?>
<?php if($isLevelHonbu) { ?>
<?php if($isMenuCondition) { ?>
            <td><a href="/chakuyou/chakuyou.php"><img src="/img/bt_03-2.gif" alt="貸与内容"  border="0"></a></td>
<?php } ?>
<?php if(!$isMenuCondition) { ?>
            <td><a href="/chakuyou/chakuyou.php"><img src="/img/bt_03.gif" alt="貸与内容"  border="0"></a></td>
<?php } ?>
<?php if($isMenuHistory) { ?>
            <td><a href="/rireki/rireki.php"><img src="/img/bt_04-2.gif" alt="申請履歴"  border="0"></a></td>
<?php } ?>
<?php if(!$isMenuHistory) { ?>
            <td><a href="/rireki/rireki.php"><img src="/img/bt_04.gif" alt="申請履歴"  border="0"></a></td>
<?php } ?>
            <td><img src="/img/bt_00.gif" border="0"></td>
            <td><img src="/img/bt_00.gif" border="0"></td>
            <td><img src="/img/bt_00.gif" border="0"></td>
            <td><img src="/img/bt_00.gif" border="0"></td>
<?php if($isMenuAdmin) { ?>
            <td><a href="/admin/admin_select.php"><img src="/img/bt_10-2.gif" alt="管理機能"  border="0"></td>
<?php } ?>
<?php if(!$isMenuAdmin) { ?>
            <td><a href="/admin/admin_select.php"><img src="/img/bt_10.gif" alt="管理機能"  border="0"></td>
<?php } ?>
<?php if($isMenuManual) { ?>
            <td><a href="<?php isset($manualUrl) ? print($manualUrl) : print('&#123;manualUrl&#125;'); ?>"><img src="/img/bt_05-2.gif" alt="マニュアル" border="0"></td>
<?php } ?>
<?php if(!$isMenuManual) { ?>
            <td><a href="<?php isset($manualUrl) ? print($manualUrl) : print('&#123;manualUrl&#125;'); ?>"><img src="/img/bt_05.gif" alt="マニュアル" border="0"></td>
<?php } ?>
<?php } ?>
 
<?php } ?>
            

<?php if($isLevelNormal) { ?>
<?php if($isMenuOrder) { ?>
            <td><a href="/hachu/hachu_top.php"><img src="/img/bt_01-2.gif" alt="発注"  border="0"></a></td>
<?php } ?>
<?php if(!$isMenuOrder) { ?>
            <td><a href="/hachu/hachu_top.php"><img src="/img/bt_01.gif" alt="発注"  border="0"></a></td>
<?php } ?>
<?php if($isMenuExchange) { ?>
            <td><a href="/koukan/koukan_top.php"><img src="/img/bt_12-2.gif" alt="交換"  border="0"></a></td>
<?php } ?>
<?php if(!$isMenuExchange) { ?>
            <td><a href="/koukan/koukan_top.php"><img src="/img/bt_12.gif" alt="交換"  border="0"></a></td>
<?php } ?>
<?php if($isMenuReturn) { ?>
            <td><a href="/henpin/henpin_top.php"><img src="/img/bt_02-2.gif" alt="返却"  border="0"></a></td>
<?php } ?>
<?php if(!$isMenuReturn) { ?>
            <td><a href="/henpin/henpin_top.php"><img src="/img/bt_02.gif" alt="返却"  border="0"></a></td>
<?php } ?>
<?php if($isMenuCondition) { ?>
            <td><a href="/chakuyou/chakuyou.php"><img src="/img/bt_03-2.gif" alt="貸与内容"  border="0"></a></td>
<?php } ?>
<?php if(!$isMenuCondition) { ?>
            <td><a href="/chakuyou/chakuyou.php"><img src="/img/bt_03.gif" alt="貸与内容"  border="0"></a></td>
<?php } ?>
<?php if($isMenuHistory) { ?>
            <td><a href="/rireki/rireki.php"><img src="/img/bt_04-2.gif" alt="申請履歴"  border="0"></a></td>
<?php } ?>
<?php if(!$isMenuHistory) { ?>
            <td><a href="/rireki/rireki.php"><img src="/img/bt_04.gif" alt="申請履歴"  border="0"></a></td>
<?php } ?>
            <td><img src="/img/bt_00.gif" border="0"></td>
<?php if($isMenuAdmin) { ?>
            <td><a href="/admin/admin_select.php"><img src="/img/bt_10-2.gif" alt="管理機能"  border="0"></td>
<?php } ?>
<?php if(!$isMenuAdmin) { ?>
            <td><a href="/admin/admin_select.php"><img src="/img/bt_10.gif" alt="管理機能"  border="0"></td>
<?php } ?>

<?php if($isMenuManual) { ?>
            <td><a href="<?php isset($manualUrl) ? print($manualUrl) : print('&#123;manualUrl&#125;'); ?>"><img src="/img/bt_05-2.gif" alt="マニュアル" border="0"></a></td>
<?php } ?>
<?php if(!$isMenuManual) { ?>
            <td><a href="<?php isset($manualUrl) ? print($manualUrl) : print('&#123;manualUrl&#125;'); ?>"><img src="/img/bt_05.gif" alt="マニュアル" border="0"></a></td>
<?php } ?>
    <input type="hidden" name="appliReason">

    <script language="JavaScript">
    <!--
    function MoveNext(source, appliReason) {
      document.grobalMenuForm.appliReason.value = '1';
      document.grobalMenuForm.action = source; 
      document.grobalMenuForm.submit();
     
      return false;

    }
    // -->
    </script>

<?php } ?>
          </tr>
          <tr>
<?php if($isLevelAdmin) { ?>
            <td colspan="8" class="headimg" height="20px" align="right">
<?php } ?>
<?php if($isLevelNormal) { ?>
            <td colspan="8" class="headimg" height="20px" align="right">
<?php } ?>
             <font size="2"><?php isset($userCd) ? print($userCd) : print('&#123;userCd&#125;'); ?>:<?php isset($userNm) ? print($userNm) : print('&#123;userNm&#125;'); ?></font>&nbsp;&nbsp;<a href="<?php isset($homeUrl) ? print($homeUrl) : print('&#123;homeUrl&#125;'); ?>login.php"><img src="/img/logout.gif" alt="ログアウト" width="82" height="21" border="0"></a>
            </td>
          </tr>
<?php } ?>
        </table>
       </form>
        <form method="post" action="./warehouseSendStatus.php" name="pagingForm">
          <div id="contents">
            <h1>修理申請一覧</h1>
            <table border="0" width="700" cellpadding="0" cellspacing="0" class="tb_1">
              <tr>
                <td width="90"  class="line"><font class="fbold">申請番号</font></td>
                <td class="line" colspan="3"><input name="searchAppliNo" type="text" value="<?php isset($searchAppliNo) ? print($searchAppliNo) : print('&#123;searchAppliNo&#125;'); ?>" size="20"></td>
                 <td width="180" align="center" valign="middle" rowspan="3" class="line">
                  <input type="button" value="     検索     " onclick="document.pagingForm.initializePage.value='1'; document.pagingForm.searchFlg.value='1'; document.pagingForm.submit(); return false;">
                </td>
              </tr>
              <tr>
                <td class="line">
                   <font class="fbold">職員コード</font>
                </td>
                <td width="140" class="line">
                  <input name="searchStaffCode" type="text" value="<?php isset($searchStaffCode) ? print($searchStaffCode) : print('&#123;searchStaffCode&#125;'); ?>" size="20">
                </td>
                <td width="90" class="line"><font class="fbold">氏名</font></td>
                <td width="140" class="line">
                  <input name="searchStaffName" type="text" value="<?php isset($searchStaffName) ? print($searchStaffName) : print('&#123;searchStaffName&#125;'); ?>" size="20">
                </td>
              </tr>
              <tr>
                <td class="line">
                   <font class="fbold">施設コード</font>
                </td>
                <td width="140" class="line">
                  <input name="searchCompCd" type="text" value="<?php isset($searchCompCd) ? print($searchCompCd) : print('&#123;searchCompCd&#125;'); ?>" size="20">
                </td>
                <td width="90" class="line"><font class="fbold">施設名</font></td>
                <td width="140" class="line">
                  <input name="searchCompName" type="text" value="<?php isset($searchCompName) ? print($searchCompName) : print('&#123;searchCompName&#125;'); ?>" size="20">
                </td>
              </tr>
            </table>
<?php if($isSearched) { ?>
            <h3>◆申請一覧</h3>
<?php if($orders) { ?>
            <table width="720" border="0" class="tb_1" cellpadding="0" cellspacing="3">
<?php if($isRegist) { ?>
              <tr>
                <td colspan="10" align="center"><font color="red">登録されました</font></td>
              </tr>
<?php } ?>
              <tr>
                <th width="60">申請日</th>
                <th width="90">申請番号</th>
                <th width="130">施設名</th>
                <th width="50">職員CD</th>
                <th width="80">氏名</th>
                <th width="90">ｱｲﾃﾑ名</th>
                <th width="60">ｻｲｽﾞ</th>
                <th width="40">区分</th>
                <th width="60" nowrap="nowrap">状態</th>
                <th width="60">発送準備</th>
              </tr>
<?php for ($i1_orders=0; $i1_orders<count($orders); $i1_orders++) { ?>
              <tr height="20">
                <td class="line2" align="center"><?php isset($orders[$i1_orders]['requestDay']) ? print(date("y/m/d", $orders[$i1_orders]['requestDay'])) : print('&#123;dateFormat(orders.requestDay, "y/m/d")&#125;'); ?></td>
                <td class="line2" align="center">
<?php if(!$orders[$i1_orders]['isAppli']) { ?>
                  <a href="#" onclick="document.pagingForm.orderId.value='<?php isset($orders[$i1_orders]['orderId']) ? print($orders[$i1_orders]['orderId']) : print('&#123;orders.orderId&#125;'); ?>'; document.pagingForm.action='../rireki/henpin_meisai.php'; document.pagingForm.submit(); return false;"><?php isset($orders[$i1_orders]['requestNo']) ? print($orders[$i1_orders]['requestNo']) : print('&#123;orders.requestNo&#125;'); ?></a>
<?php } ?>
<?php if($orders[$i1_orders]['isAppli']) { ?>
                  
                  <a href="#" onclick="document.pagingForm.orderId.value='<?php isset($orders[$i1_orders]['orderId']) ? print($orders[$i1_orders]['orderId']) : print('&#123;orders.orderId&#125;'); ?>'; document.pagingForm.action='../rireki/hachu_meisai.php'; document.pagingForm.submit(); return false;"><?php isset($orders[$i1_orders]['requestNo']) ? print($orders[$i1_orders]['requestNo']) : print('&#123;orders.requestNo&#125;'); ?></a>
                  
<?php } ?>
                </td>
                  
                <td class="line2" align="left"><?php isset($orders[$i1_orders]['CompCd']) ? print($orders[$i1_orders]['CompCd']) : print('&#123;orders.CompCd&#125;'); ?>:<?php isset($orders[$i1_orders]['CompName']) ? print($orders[$i1_orders]['CompName']) : print('&#123;orders.CompName&#125;'); ?></td>
                <td class="line2" align="center"><?php isset($orders[$i1_orders]['staffCode']) ? print($orders[$i1_orders]['staffCode']) : print('&#123;orders.staffCode&#125;'); ?></td>
                <td class="line2" align="left"><?php isset($orders[$i1_orders]['staffName']) ? print($orders[$i1_orders]['staffName']) : print('&#123;orders.staffName&#125;'); ?></td>
                <td class="line2" align="left"><?php isset($orders[$i1_orders]['itemName']) ? print($orders[$i1_orders]['itemName']) : print('&#123;orders.itemName&#125;'); ?></td>
                <td class="line2" align="center"><?php isset($orders[$i1_orders]['sizeOrder']) ? print($orders[$i1_orders]['sizeOrder']) : print('&#123;orders.sizeOrder&#125;'); ?></td>
                <td class="line2" align="center">
<?php if($orders[$i1_orders]['divisionOrder']) { ?>
                  発注
<?php } ?>
<?php if($orders[$i1_orders]['divisionExchange']) { ?>
                  
                  交換
                  
<?php } ?>
<?php if($orders[$i1_orders]['divisionReturn']) { ?>
                  
                  返却
                  
<?php } ?>
                </td>
                <td class="line2" align="center" >
<?php if($orders[$i1_orders]['statusIsBlue']) { ?>
                  
                  <font color="blue"><?php isset($orders[$i1_orders]['status']) ? print($orders[$i1_orders]['status']) : print('&#123;orders.status&#125;'); ?></font>
                  
<?php } ?>
<?php if($orders[$i1_orders]['statusIsRed']) { ?>
                  
                  <font color="red"><?php isset($orders[$i1_orders]['status']) ? print($orders[$i1_orders]['status']) : print('&#123;orders.status&#125;'); ?></font>
                  
<?php } ?>
<?php if($orders[$i1_orders]['statusIsTeal']) { ?>
                  
                  <font color="Teal"><?php isset($orders[$i1_orders]['status']) ? print($orders[$i1_orders]['status']) : print('&#123;orders.status&#125;'); ?></font>
                  
<?php } ?>
<?php if($orders[$i1_orders]['statusIsGreen']) { ?>
                  
                  <font color="green"><?php isset($orders[$i1_orders]['status']) ? print($orders[$i1_orders]['status']) : print('&#123;orders.status&#125;'); ?></font>
                  
<?php } ?>
<?php if($orders[$i1_orders]['statusIsGray']) { ?>
                  
                  <font color="gray"><?php isset($orders[$i1_orders]['status']) ? print($orders[$i1_orders]['status']) : print('&#123;orders.status&#125;'); ?></font>
                  
<?php } ?>
<?php if($orders[$i1_orders]['statusIsPink']) { ?>
                  
                  <font color="fuchsia"><?php isset($orders[$i1_orders]['status']) ? print($orders[$i1_orders]['status']) : print('&#123;orders.status&#125;'); ?></font>
                  
<?php } ?>
<?php if($orders[$i1_orders]['statusIsBlack']) { ?>
                  <?php isset($orders[$i1_orders]['status']) ? print($orders[$i1_orders]['status']) : print('&#123;orders.status&#125;'); ?>
<?php } ?>
                </td>

                <td class="line2" align="center">
<?php if($orders[$i1_orders]['dispBox']) { ?>
<?php if($orders[$i1_orders]['isReady']) { ?>
                    <input type="checkBox" name="ready[]" id="ready_<?php isset($orders[$i1_orders]['orderDetId']) ? print($orders[$i1_orders]['orderDetId']) : print('&#123;orders.orderDetId&#125;'); ?>" value="<?php isset($orders[$i1_orders]['orderDetId']) ? print($orders[$i1_orders]['orderDetId']) : print('&#123;orders.orderDetId&#125;'); ?>" checked onClick="checkReady(<?php isset($orders[$i1_orders]['orderDetId']) ? print($orders[$i1_orders]['orderDetId']) : print('&#123;orders.orderDetId&#125;'); ?>);">
                    <input type="hidden"   name="notReady[]" id="notReady_<?php isset($orders[$i1_orders]['orderDetId']) ? print($orders[$i1_orders]['orderDetId']) : print('&#123;orders.orderDetId&#125;'); ?>" value="">
<?php } ?>
<?php if(!$orders[$i1_orders]['isReady']) { ?>
                    <input type="checkBox" name="ready[]" id="ready_<?php isset($orders[$i1_orders]['orderDetId']) ? print($orders[$i1_orders]['orderDetId']) : print('&#123;orders.orderDetId&#125;'); ?>" value="<?php isset($orders[$i1_orders]['orderDetId']) ? print($orders[$i1_orders]['orderDetId']) : print('&#123;orders.orderDetId&#125;'); ?>" onClick="checkReady(<?php isset($orders[$i1_orders]['orderDetId']) ? print($orders[$i1_orders]['orderDetId']) : print('&#123;orders.orderDetId&#125;'); ?>);">
                    <input type="hidden"   name="notReady[]" id="notReady_<?php isset($orders[$i1_orders]['orderDetId']) ? print($orders[$i1_orders]['orderDetId']) : print('&#123;orders.orderDetId&#125;'); ?>" value="<?php isset($orders[$i1_orders]['orderDetId']) ? print($orders[$i1_orders]['orderDetId']) : print('&#123;orders.orderDetId&#125;'); ?>">
<?php } ?>
<?php } ?>
<?php if(!$orders[$i1_orders]['dispBox']) { ?>
                  &nbsp;
<?php } ?>
                </td>
              </tr>
<?php } ?>

            </table>
            <table border="0" width="700" cellpadding="0" cellspacing="0" class="tb_1">
              <tr>
                <td width="700" align="right">
                  <input name="regist_btn" type="button" value="発送準備登録" onclick="document.pagingForm.action='#'; document.pagingForm.regist.value=1; document.pagingForm.submit(); return false;">
                </td>
              </tr>
            </table>
            

            
<?php if($paging['isPaging']) { ?>
            <br>
            <div class="tb_1">
              <table border="0" width="120" cellpadding="0" cellspacing="0" class="tb_1">
                <tr>
                  <td width="60" align="left">
<?php if($paging['isPrev']) { ?>
                    <input name="prev_btn" type="button" value="&lt;&lt;" onclick="document.pagingForm.action='#'; document.pagingForm.nowPage.value='<?php isset($paging['prev']) ? print($paging['prev']) : print('&#123;paging.prev&#125;'); ?>'; document.pagingForm.submit(); return false;">
<?php } ?>
                  </td>
                  <td width="60" align="right">
<?php if($paging['isNext']) { ?>
                    <input name="next_btn" type="button" value="&gt;&gt;" onclick="document.pagingForm.action='#'; document.pagingForm.nowPage.value='<?php isset($paging['next']) ? print($paging['next']) : print('&#123;paging.next&#125;'); ?>'; document.pagingForm.submit(); return false;">
<?php } ?>
                  </td>
                </tr>
              </table>
            </div>
            <input type="hidden" name="nowPage" value="<?php isset($paging['nowPage']) ? print($paging['nowPage']) : print('&#123;paging.nowPage&#125;'); ?>">
<?php } ?>



<?php } ?>
<?php if(!$orders) { ?>
            <table width="730" border="0" class="tb_1" cellpadding="0" cellspacing="3">
              <tr>
               <td colspan="9" align="center"><font color="red"><b>該当する申請データが登録されていません。</b></font></td>
              </tr>
             </table>
<?php } ?>
<?php } ?>
            
          </div>
          <input type="hidden" name="encodeHint" value="京">
          <input type="hidden" name="orderId">
          <input type="hidden" name="regist">
          <input type="hidden" name="initializePage">
          <input type="hidden" name="searchFlg" value="<?php isset($isSearched) ? print($isSearched) : print('&#123;isSearched&#125;'); ?>">
        </form>
        <br><br><br>
        

      </div>
    </div>
    </div>
  </body>
</html>
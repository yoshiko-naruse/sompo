<?php

/*
 * 申請履歴画面
 * rireki.src.php
 *
 * create 2007/03/26 H.Osugi
 *
 *
 */

// 各種モジュールの取得
include_once('../include/define.php');               // 定数定義
include_once('../include/dbConnect.php');            // DB接続モジュール
include_once('../include/msSqlControl.php');         // DB操作モジュール
include_once('../include/checkLogin.php');           // ログイン判定モジュール
include_once('../include/castHtmlEntity.php');       // HTMLエンティティモジュール
include_once('../include/redirectPost.php');         // リダイレクトポストモジュール
include_once('../include/setPaging.php');            // ページング情報セッティングモジュール
include_once('../error_message/errorMessage.php');   // エラーメッセージ

// 変数の初期化 ここから ******************************************************
$searchAppliNo      = '';                   // 申請番号
$searchStaffCode    = '';                   // スタッフコード
$isSearched         = false;                // 検索フラグ

// 変数の初期化 ここまで ******************************************************

$post = $_POST;

if(isset($post['searchFlg']) && $post['searchFlg'] == 1 ){

    $nowPage = 1;
    if ($post['nowPage'] != '') {
        $nowPage = trim($post['nowPage']);
    }

    if ($post['initializePage'] == 1) {
        $nowPage = 1;
    }
    
    // 表示する注文履歴一覧を取得
    $orders = getOrder($dbConnect, $post, $nowPage, $DISPLAY_STATUS, $allCount);
    
    // ページングのセッティング
    $paging = setPaging($nowPage, PAGE_PER_DISPLAY_HISTORY, $allCount);
    
    $isSearched = true;

    $searchAppliNo      = $post['searchAppliNo'];                   // 申請番号
    $searchStaffCode    = $post['searchStaffCode'];                   // スタッフコード
    
}





function getOrder($dbConnect, $post, $nowPage, $DISPLAY_STATUS ,&$allCount) {

    // 初期化
    $appliNo      = '';
    $staffCode    = '';
    $limit        = '';
    $offset       = '';

    // 取得したい件数
    $limit = PAGE_PER_DISPLAY_HISTORY;      // 1ページあたりの表示件数;

    // 取得したいデータの開始位置
    $offset = ($nowPage - 1) * PAGE_PER_DISPLAY_HISTORY;

    // 申請番号
    $appliNo = $post['searchAppliNo'];

    // スタッフコード
    $staffCode = $post['searchStaffCode'];

    // 注文履歴の件数を取得する
    $sql  = "";
    $sql .= " SELECT";
    $sql .=     " COUNT(DISTINCT tor.OrderID) as count_order";
    $sql .= " FROM";
    $sql .=     " T_Order tor";

    $sql .= " INNER JOIN";
    $sql .=     " M_Comp mc";
    $sql .= " ON";
    $sql .=     " tor.CompID = mc.CompID";
    $sql .= " AND";
    $sql .=     " mc.Del = " . DELETE_OFF;

    $sql .= " WHERE";

    $sql .=     " tor.Del = " . DELETE_OFF;

    // 申請番号を前方一致
    if ($appliNo != '') {
        $sql .= " AND";
        $sql .=     " tor.AppliNo LIKE ('" . db_Like_Escape($appliNo) . "%')";
    }

    if ($staffCode != '') {
        $sql .= " AND";
        $sql .=     " tor.StaffCode = '" . db_Escape($staffCode) . "'";
    }
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
        $top   = $allCount;
    }

    // 注文履歴の一覧を取得する
    $sql  = "";
    $sql .= " SELECT";
    $sql .=     " tor.OrderID,";
    $sql .=     " tor.AppliDay,";
    $sql .=     " tor.AppliNo,";
    $sql .=     " tor.AppliCompCd,";
    $sql .=     " tor.AppliCompName,";
    $sql .=     " tor.StaffCode,";
    $sql .=     " tor.PersonName,";
    $sql .=     " tor.AppliMode,";
    $sql .=     " tor.AppliSeason,";
    $sql .=     " tor.AppliReason,";
    $sql .=     " tor.Status,";
    $sql .=     " tor.ShipDay,";
    $sql .=     " tor.ReturnDay";
    $sql .= " FROM";
    $sql .=     " (";
    $sql .=         " SELECT";
    $sql .=             " TOP " . $limit;
    $sql .=             " *";
    $sql .=         " FROM";
    $sql .=             " T_Order tor2";
    $sql .=         " WHERE";
    $sql .=             " tor2.OrderID IN (";
    $sql .=                         " SELECT";
    $sql .=                             " OrderID";
    $sql .=                         " FROM";
    $sql .=                             " (";
    $sql .=                                 " SELECT";
    $sql .=                                     " DISTINCT";
    $sql .=                                     " TOP " . ($top);
    $sql .=                                     " tor3.OrderID,";
    $sql .=                                     " tor3.AppliDay";
    $sql .=                                 " FROM";
    $sql .=                                     " T_Order tor3";

    $sql .=                                 " INNER JOIN";
    $sql .=                                     " M_Comp mc";
    $sql .=                                 " ON";
    $sql .=                                     " tor3.CompID = mc.CompID";
    $sql .=                                 " AND";
    $sql .=                                     " mc.Del = " . DELETE_OFF;

    $sql .=                                 " WHERE";

    $sql .=                                     " tor3.Del = " . DELETE_OFF;

    // 申請番号を前方一致
    if ($appliNo != '') {
        $sql .=                             " AND";
        $sql .=                                 " tor3.AppliNo LIKE ('" . db_Like_Escape($appliNo) . "%')";
    }

    $sql .=                                 " ORDER BY";
    $sql .=                                     " tor3.AppliDay DESC,";
    $sql .=                                     " tor3.OrderID DESC";
    $sql .=                             " ) tor4";
    $sql .=                         " )";
    $sql .=                 " ORDER BY";
    $sql .=                     " tor2.AppliDay ASC,";
    $sql .=                     " tor2.OrderID ASC";

    $sql .=     " ) tor";

    $sql .=     " ORDER BY";
    $sql .=         " tor.AppliDay DESC,";
    $sql .=         " tor.OrderID DESC";

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
        $result[$i]['orderId']    = $result[$i]['OrderID'];
        $result[$i]['CompCd']     = castHtmlEntity($result[$i]['AppliCompCd']);
        $result[$i]['CompName']   = castHtmlEntity($result[$i]['AppliCompName']);
        $result[$i]['staffCode']  = castHtmlEntity($result[$i]['StaffCode']);
        $result[$i]['personName']  = castHtmlEntity($result[$i]['PersonName']);

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
                $result[$i]['divisionOrder']    = true;
                break;
            case APPLI_MODE_EXCHANGE:                   // 交換
                $result[$i]['divisionExchange'] = true;
                break;
            case APPLI_MODE_RETURN:                     // 返却
                $result[$i]['divisionReturn']   = true;
                break;
            default:
                break;
        }

        // 状態
        $result[$i]['status']  = castHtmlEntity($DISPLAY_STATUS[$result[$i]['Status']]);

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
            case STATUS_NOT_RETURN_DENY:                // 未返却 （否認）
            case STATUS_LOSS_DENY:                      // 紛失（否認）
                $result[$i]['statusIsPink']  = true;
                break;
            case STATUS_APPLI_ADMIT:                    // 申請済（承認済）
                $result[$i]['statusIsGreen'] = true;
                break;
            case STATUS_ORDER:                          // 受注済
                $result[$i]['statusIsBlue']  = true;
                break;
            case STATUS_NOT_RETURN:                     // 未返却（承認待ち）
            case STATUS_NOT_RETURN_ADMIT:               // 未返却（承認済）
            case STATUS_NOT_RETURN_ORDER:               // 未返却（受注済）
                $result[$i]['statusIsRed']   = true;
                break;
            case STATUS_LOSS:                           // 紛失（承認待ち）
            case STATUS_LOSS_ADMIT:                     // 紛失（承認済）
            case STATUS_LOSS_ORDER:                     // 紛失（受注済）
                $result[$i]['statusIsTeal']  = true;
                break;
            default:
                $result[$i]['statusIsBlack'] = true;
                break;
        }
    }

    return  $result;

}

/*
 * 検索条件を指定しているか判定
 * 引数  ：$no           => 申請NO
 * 戻り値：詳細に1件でも受注済みがあるとTRUE/FALSE
 *
 * create 2008/01/09 DF
 *
 */
function _check_detaildata($dbConnect,$no){

    $sql  = " SELECT";
    $sql .= " tod.Status";
    $sql .= " FROM  T_Order_Details as tod ";
    $sql .= " WHERE ";
    $sql .= " tod.AppliNo = '". db_Escape(trim($no))."'";
    $sql .= " AND tod.Del = " . DELETE_OFF;
    $sql .= " AND tod.GroupID = ".MATTER_CODE;
    $sql .= " GROUP BY";
    $sql .= " tod.Status";
 
    $result = db_Read($dbConnect, $sql);
    
    // 検索結果が0件の場合
    if (count($result) <= 0) {
        return false;
    }
    
    $max = count($result);
    for($i=0;$i<$max;$i++){
        if(trim($result[$i]['Status']) == STATUS_ORDER){
            return true;
        }
    }

    return false;
}

?><!DOCTYPE html>
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
        <form method="post" action="./editData.php" name="pagingForm">
          <div id="contents">
            <h1>申請データ変更</h1>
            <table border="0" width="700" cellpadding="0" cellspacing="0" class="tb_1">
              <tr>
                <td>
                   <span class="fbold">従業員番号</span>
                </td>
                <td width="140">
                  <span class="fbold"><input name="searchStaffCode" type="text" value="<?php isset($searchStaffCode) ? print($searchStaffCode) : print('&#123;searchStaffCode&#125;'); ?>" size="20"></span>
                </td>
                <td width="90" ><span class="fbold">申請番号</span></td>
                <td width="140" ><input name="searchAppliNo" type="text" value="<?php isset($searchAppliNo) ? print($searchAppliNo) : print('&#123;searchAppliNo&#125;'); ?>" size="20"></td>
                 <td width="180" align="center">
                  <input type="button" value="     検索     " onclick="document.pagingForm.initializePage.value='1'; document.pagingForm.searchFlg.value='1'; document.pagingForm.submit(); return false;">
                </td>
              </tr>
            </table>
<?php if($isSearched) { ?>
            <h3>◆申請一覧</h3>
<?php if($orders) { ?>
            <table width="730" border="0" class="tb_1" cellpadding="0" cellspacing="3">
              <tr>
                <th width="60">申請日</th>
                <th width="90">申請番号</th>
                <th width="200">事業所名</th>
                <th width="100">職員名</th>
                <th width="40">区分</th>
                <th width="60">出荷日</th>
                <th width="60">返品日</th>
                <th width="60" nowrap="nowrap">状態</th>
                <th width="40">&nbsp;</th>
              </tr>
<?php for ($i1_orders=0; $i1_orders<count($orders); $i1_orders++) { ?>
              <tr height="20">
                <td class="line2" align="center"><?php isset($orders[$i1_orders]['requestDay']) ? print(date("y/m/d", $orders[$i1_orders]['requestDay'])) : print('&#123;dateFormat(orders.requestDay, "y/m/d")&#125;'); ?></td>
                <td class="line2" align="center">
<?php if(!$orders[$i1_orders]['isAppli']) { ?>
                  <a href="#" onclick="document.pagingForm.orderId.value='<?php isset($orders[$i1_orders]['orderId']) ? print($orders[$i1_orders]['orderId']) : print('&#123;orders.orderId&#125;'); ?>'; document.pagingForm.action='./rireki/henpin_meisai.php'; document.pagingForm.submit(); return false;"><?php isset($orders[$i1_orders]['requestNo']) ? print($orders[$i1_orders]['requestNo']) : print('&#123;orders.requestNo&#125;'); ?></a></td>
<?php } ?>
<?php if($orders[$i1_orders]['isAppli']) { ?>
                  
                  <a href="#" onclick="document.pagingForm.orderId.value='<?php isset($orders[$i1_orders]['orderId']) ? print($orders[$i1_orders]['orderId']) : print('&#123;orders.orderId&#125;'); ?>'; document.pagingForm.action='./rireki/hachu_meisai.php'; document.pagingForm.submit(); return false;"><?php isset($orders[$i1_orders]['requestNo']) ? print($orders[$i1_orders]['requestNo']) : print('&#123;orders.requestNo&#125;'); ?></a></td>
                  
<?php } ?>
                </td>
                  
                <td class="line2" align="left"><?php isset($orders[$i1_orders]['CompName']) ? print($orders[$i1_orders]['CompName']) : print('&#123;orders.CompName&#125;'); ?></td>
                <td class="line2" align="left"><?php isset($orders[$i1_orders]['personName']) ? print($orders[$i1_orders]['personName']) : print('&#123;orders.personName&#125;'); ?></td>
                <td class="line2" align="center">
<?php if($orders[$i1_orders]['divisionOrder']) { ?>
                  発注
<?php } ?>
<?php if($orders[$i1_orders]['divisionExchange']) { ?>
                  
                  交換
                  
<?php } ?>
<?php if($orders[$i1_orders]['divisionReturn']) { ?>
                  
                  返品
                  
<?php } ?>
                </td>
                <td class="line2" align="center">
<?php if($orders[$i1_orders]['isEmptyShipDay']) { ?>
                  
                  &nbsp;
                  
<?php } ?>
<?php if(!$orders[$i1_orders]['isEmptyShipDay']) { ?>
                  <?php isset($orders[$i1_orders]['ShipDay']) ? print(date("y/m/d", $orders[$i1_orders]['ShipDay'])) : print('&#123;dateFormat(orders.ShipDay, "y/m/d")&#125;'); ?>
<?php } ?>
                </td>
                <td class="line2" align="center">
<?php if($orders[$i1_orders]['isEmptyReturnDay']) { ?>
                  
                  &nbsp;
                  
<?php } ?>
<?php if(!$orders[$i1_orders]['isEmptyReturnDay']) { ?>
                  <?php isset($orders[$i1_orders]['ReturnDay']) ? print(date("y/m/d", $orders[$i1_orders]['ReturnDay'])) : print('&#123;dateFormat(orders.ReturnDay, "y/m/d")&#125;'); ?>
<?php } ?>
                </td>
                <td class="line2" align="center" >
<?php if($orders[$i1_orders]['statusIsBlue']) { ?>
                  
                  <span style="color:blue"><?php isset($orders[$i1_orders]['status']) ? print($orders[$i1_orders]['status']) : print('&#123;orders.status&#125;'); ?></span>
                  
<?php } ?>
<?php if($orders[$i1_orders]['statusIsRed']) { ?>
                  
                  <span style="color:red"><?php isset($orders[$i1_orders]['status']) ? print($orders[$i1_orders]['status']) : print('&#123;orders.status&#125;'); ?></span>
                  
<?php } ?>
<?php if($orders[$i1_orders]['statusIsTeal']) { ?>
                  
                  <span style="color:Teal"><?php isset($orders[$i1_orders]['status']) ? print($orders[$i1_orders]['status']) : print('&#123;orders.status&#125;'); ?></span>
                  
<?php } ?>
<?php if($orders[$i1_orders]['statusIsGreen']) { ?>
                  
                  <span style="color:green"><?php isset($orders[$i1_orders]['status']) ? print($orders[$i1_orders]['status']) : print('&#123;orders.status&#125;'); ?></span>
                  
<?php } ?>
<?php if($orders[$i1_orders]['statusIsGray']) { ?>
                  
                  <span style="color:gray"><?php isset($orders[$i1_orders]['status']) ? print($orders[$i1_orders]['status']) : print('&#123;orders.status&#125;'); ?></span>
                  
<?php } ?>
<?php if($orders[$i1_orders]['statusIsPink']) { ?>
                  
                  <span style="color:fuchsia"><?php isset($orders[$i1_orders]['status']) ? print($orders[$i1_orders]['status']) : print('&#123;orders.status&#125;'); ?></span>
                  
<?php } ?>
<?php if($orders[$i1_orders]['statusIsBlack']) { ?>
                  <?php isset($orders[$i1_orders]['status']) ? print($orders[$i1_orders]['status']) : print('&#123;orders.status&#125;'); ?>
<?php } ?>
                </td>

                <td class="line2" align="center">
                  <input type="button" value="詳細" onclick="document.pagingForm.action='./editDataDetail.php'; document.pagingForm.orderId.value='<?php isset($orders[$i1_orders]['orderId']) ? print($orders[$i1_orders]['orderId']) : print('&#123;orders.orderId&#125;'); ?>'; document.pagingForm.submit(); return false;">
                </td>
              </tr>
<?php } ?>

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
               <td colspan="9" align="center"><span style="color:red"><b>該当する申請データが登録されていません。</b></span></td>
              </tr>
             </table>
<?php } ?>
<?php } ?>
            
          </div>
          <input type="hidden" name="encodeHint" value="京">
          <input type="hidden" name="orderId">
          <input type="hidden" name="initializePage">
          <input type="hidden" name="searchFlg" value="<?php isset($isSearched) ? print($isSearched) : print('&#123;isSearched&#125;'); ?>">
        </form>
        <br><br><br>
        

      </div>
    </div>
  </body>
</html>
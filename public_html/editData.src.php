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

?>
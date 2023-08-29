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
include_once('../../include/define.php');           // 定数定義
require_once('../../include/dbConnect.php');        // DB接続モジュール
require_once('../../include/msSqlControl.php');     // DB操作モジュール

// 必須パラメータのチェック
if (!isset($_GET['id'])) {
        
}

// 「申請済」の注文情報を検索 
$result = checkUpdateOrder($dbConnect, $_GET['id']);

print $result;
exit;

// 「申請済」の注文情報を検索
function checkUpdateOrder($dbConnect, $staffId) {
    $sql .= "";
    $sql .= " SELECT";
    $sql .=     " COUNT(OrderID) as cnt";
    $sql .= " FROM";
    $sql .=     " T_Order";
    $sql .= " WHERE";
    $sql .=     " Status = ".STATUS_APPLI_ADMIT;
    $sql .= " AND";
    $sql .=     " StaffID = ".$staffId;
    $sql .= " AND";
    $sql .=     " Del = ".DELETE_OFF;

    $result = db_Read($dbConnect, $sql);

    if($result[0]['cnt'] == 0){
        return false;
    }else{
        return true;
    }
    
}









?>

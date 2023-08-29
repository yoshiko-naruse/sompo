<?php
/*
 * UserMaster_jinjiidou_Cron.php
 *
 * create 2007/12/28
 *
 *
 */
$rootDir = dirname(dirname(dirname(__FILE__)));
include_once($rootDir . '/include/define.php');             // 定数定義
include_once($rootDir . '/include/dbConnect.php');          // DB接続モジュール
include_once($rootDir . '/include/msSqlControl.php');       // DB操作モジュール
require_once($rootDir . '/include/createMoveMail.php');     // 店舗移動通知メール作成モジュール
require_once($rootDir . '/include/sendTextMail.php');       // テキストメール送信モジュール

set_time_limit(0);

$upfliePath = BATCH_EXCEL_UPLOAD_DIR;

    // ユーザー抽出
    $alluser = _getallUserdata($dbConnect);
    
    $flg = 1;
    switch($flg){
        case 1:
            // 本日
            $targetdate = date("y/m/d");
            break;
        case 2:
            // 前日
            $targetdate = date("y/m/d",strtotime("-1 day"));
            break;
        case 3:
            // 次日
            $targetdate = date("y/m/d",strtotime("+1 day"));
            break;
    }
    
    $max = count($alluser);

    for ($i=0;$i<$max;$i++) {
        //　対象となる日付より　当日以降のデータを処理する。
        if(trim($alluser[$i]['HatureiDay']) != "" && trim($alluser[$i]['HatureiDay']) <= $targetdate){
            // トランザクション開始
            db_Transaction_Begin($dbConnect);

            // 店舗変更があったかチェック
            $isMoveComp = _checkMoveComp($dbConnect,$alluser[$i]['StaffSeqID'],$alluser[$i]['NextCompID']);
            if ($isMoveComp) {
                // 店舗変更通知メール用に変更前のユーザー情報を取得
                $oldStaffInfo = getUserMaster($dbConnect, $alluser[$i]['StaffSeqID']);                
            } 

            // 人事異動反映
            $isSuccess = _Update_user($dbConnect,$alluser[$i]);
            if (!$isSuccess) {
                db_Transaction_Rollback($dbConnect);
            } else {
                // 申請済発注情報の発送先を変更
                $isSuccess = _Update_T_Order($dbConnect,$alluser[$i]);
                if (!$isSuccess) {
                    db_Transaction_Rollback($dbConnect);
                } else {
                    // コミット
                    db_Transaction_Commit($dbConnect);

                    // 店舗が変更されていたらメール送信
                    if ($isMoveComp) {
                        sendMoveInfo($dbConnect,$alluser[$i], $oldStaffInfo);
                    }
                }
            }

        }
    }
# d("all green");
# db_Transaction_Rollback($dbConnect);

// ------------------------------



// 人事異動データを反映する
/*
 * ユーザー更新処理
 * 引数  ：$dbConnect => コネクション
 * 引数  ：$data      => ユーザーデータ
 * create 2007/11/05 DF
 *
 */
function _Update_user($dbConnect,$data){

    // M_user更新
    $compData = getCompMaster($dbConnect,trim($data['NextCompID']));

    // T_Staffに登録があれば更新
    $sql  = " SELECT";
    $sql .=     " COUNT (*) AS count_data";
    $sql .= " FROM";
    $sql .=     " T_Staff";
    $sql .= " WHERE";
    $sql .=     " StaffID = '" . db_Escape(trim($data['StaffSeqID']))."'";
    $sql .= " AND";
    $sql .=     " Del = ".DELETE_OFF;

    $result = db_Read($dbConnect, $sql);

    if ($result[0]['count_data'] != 0) {

        $sql  = " UPDATE T_Staff SET  ";
        $sql .= "  CompID        = '".db_Escape(trim($compData[0]['CompID']))."'";
	    if (isset($data['NextNameCd']) && $data['NextNameCd'] != '') {
	        $sql .= " ,StaffCode     = '".db_Escape(trim($data['NextNameCd']))."'";
		}

        $sql .= " ,UpdDay        = GETDATE()";
        $sql .= " ,UpdUser       = '".db_Escape(trim($data['UpdUser']))."' ";
        $sql .= " WHERE ";
        $sql .=     " StaffID = '" . db_Escape(trim($data['StaffSeqID']))."'";
        $sql .= " AND";
        $sql .=     " Del = ".DELETE_OFF;

        $isSuccess = db_Execute($dbConnect, $sql);

        // 実行結果が失敗の場合
        if ($isSuccess == false) {

            return false;
        }

    }

    $sql  = " UPDATE M_Staff SET  ";
    $sql .= "  CompID        = '".db_Escape(trim($compData[0]['CompID']))."'";
    $sql .= " ,CompCd        = '".db_Escape(trim($compData[0]['CompCd']))."'";
    if (isset($data['NextNameCd']) && $data['NextNameCd'] != '') {
	    $sql .= " ,StaffCode     = '".db_Escape(trim($data['NextNameCd']))."'";
    }
    $sql .= " ,HatureiDay    = NULL";
    $sql .= " ,NextNameCd    = NULL";
    $sql .= " ,NextCompID    = NULL";

    $sql .= " ,UpdDay        = GETDATE()";
    $sql .= " ,UpdUser       = '".db_Escape(trim($data['UpdUser']))."' ";
    $sql .= " WHERE ";
    $sql .= " Del = ".DELETE_OFF;
    $sql .= " AND StaffSeqID = '" . db_Escape(trim($data['StaffSeqID']))."'";

    $isSuccess = db_Execute($dbConnect, $sql);

    // 実行結果が失敗の場合
    if ($isSuccess == false) {
        return false;
    }

    return true;
}

// 更新処理
function _Update_T_Order($dbConnect,$data){

    $compData = getCompMaster($dbConnect,trim($data['NextCompID']));

    // T_Orderの情報を更新
    $sql  = " UPDATE T_Order SET  ";
    $sql .= "  CompID                 = '".db_Escape(trim($compData[0]['CompID']))."'";
    if (isset($data['NextNameCd']) && $data['NextNameCd'] != '') {
	    $sql .= " ,StaffCode              = '".db_Escape(trim($data['NextNameCd']))."'";
	}
    $sql .= " ,PersonName             = '".db_Escape(trim($data['PersonName']))."'";

    $sql .= " ,UpdDay                 = GETDATE()";
    $sql .= " ,UpdUser                = '".db_Escape(trim($data['UpdUser']))."' ";
    $sql .= " WHERE ";
    $sql .=     " StaffID             = '" . db_Escape(trim($data['StaffSeqID']))."'";
    $sql .= " AND";
    $sql .=     " Del                 = ".DELETE_OFF;

    $isSuccess = db_Execute($dbConnect, $sql);

    // 実行結果が失敗の場合
    if ($isSuccess == false) {
//var_dump("aaaaaa");die;
        return false;
    }

    // まだ倉庫に送信していないデータがあれば更新
    $sql  = " SELECT";
    $sql .=     " COUNT (*) AS count_data";
    $sql .= " FROM";
    $sql .=     " T_Order";
    $sql .= " WHERE";
    $sql .=     " StaffID = '" . db_Escape(trim($data['StaffSeqID']))."'";
    $sql .= " AND";
    $sql .=     " Status IN (".STATUS_APPLI.",".STATUS_APPLI_ADMIT.",".STATUS_NOT_RETURN.",".STATUS_NOT_RETURN_ADMIT.",".STATUS_LOSS.",".STATUS_LOSS_ADMIT.")";
    $sql .= " AND";
    $sql .=     " Del = ".DELETE_OFF;

    $result = db_Read($dbConnect, $sql);

    if ($result[0]['count_data'] != 0) {

        $shipData = getShipData($dbConnect,$compData[0]['CompID']);

        $sql  = " UPDATE T_Order SET  ";
        $sql .= "  AppliCompCd            = '".db_Escape(trim($shipData['CompCd']))."'";
        $sql .= " ,AppliCompName          = '".db_Escape(trim($shipData['CompName']))."'";
        $sql .= " ,Zip                    = '".db_Escape(trim($shipData['Zip']))."'";
        $sql .= " ,Adrr                   = '".db_Escape(trim($shipData['Adrr']))."'";
        $sql .= " ,Tel                    = '".db_Escape(trim($shipData['Tel']))."'";
        $sql .= " ,ShipName               = '".db_Escape(trim($shipData['ShipName']))."'";
        $sql .= " ,TantoName              = '".db_Escape(trim($shipData['TantoName']))."'";

        $sql .= " ,UpdDay                 = GETDATE()";
        $sql .= " ,UpdUser                = '".db_Escape(trim($data['UpdUser']))."' ";
        $sql .= " WHERE ";
        $sql .=     " StaffID             = '" . db_Escape(trim($data['StaffSeqID']))."'";
        $sql .= " AND";
        $sql .=     " Status IN (".STATUS_APPLI.",".STATUS_APPLI_ADMIT.",".STATUS_NOT_RETURN.",".STATUS_NOT_RETURN_ADMIT.",".STATUS_LOSS.",".STATUS_LOSS_ADMIT.")";
        $sql .= " AND";
        $sql .=     " Del                 = ".DELETE_OFF;

        $isSuccess = db_Execute($dbConnect, $sql);

        // 実行結果が失敗の場合
        if ($isSuccess == false) {
//var_dump("bbbbb");die;
            return false;
        }

    }

    return true;
}

// M_Compのデータ取得
function getCompMaster($dbConnect,$compId="") {

    $sql  = "";
    $sql .= " SELECT";
    $sql .=     " CompID";
    $sql .=     ",CompCd";
    $sql .=     ",CompName";
    $sql .= " FROM";
    $sql .=     " M_Comp";
    $sql .= " WHERE";
    $sql .=     " Del = ". DELETE_OFF;
    if($compId != ""){
        $sql .= " AND";
        $sql .=     " CompID  = '" . db_Escape($compId) . "'";
    }
    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合
    if (count($result) <= 0) {
        $result = array();
        return $result;
    }

    return $result;
}
// 人事異動したユーザーのオーダー情報を取得する。
function _seach_orderdata($dbConnect,$data){

    $sql  = " SELECT  ";
    $sql .= " AppliNo ";
    $sql .= " FROM ";
    $sql .= " T_Order ";
    $sql .= " WHERE ";
    $sql .= " Del = ".DELETE_OFF;
    $sql .= " AND StaffCode = '" . db_Escape(trim($data['StaffCode']))."'";
    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合 エラー
    if (count($result) <= 0) {
        return false;
    }
    
    return $result;
}

/*
 * ユーザーデータ取得
 * 引数  ：$dbConnect   => コネクション
 * create 2007/11/05 DF
 *
 */
function _getallUserdata($dbConnect){

    $sql  = "";
    $sql .= " SELECT";
    $sql .=     "  ms.StaffSeqID";
    $sql .=     " ,ms.CompCd";
    $sql .=     " ,ms.StaffCode";
    $sql .=     " ,ms.PersonName";
    $sql .=     " , CONVERT(char, ms.HatureiDay, 11) as HatureiDay";
    $sql .=     " ,ms.NextNameCd";
    $sql .=     " ,ms.NextCompID";
    $sql .=     " ,mc.CompID";
    $sql .=     " ,mc.CompName";
    $sql .=     " ,ms.UpdUser";
    
    $sql .= " FROM";
    $sql .=     " M_Staff AS ms";
    $sql .= " INNER JOIN M_Comp AS mc ON ms.CompID = mc.CompID ";
    $sql .= " WHERE";
    $sql .=     " ms.Del = " .DELETE_OFF;
    $sql .= " AND";
    $sql .=     " mc.Del = " .DELETE_OFF;

    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合 エラー
    if (count($result) <= 0) {
        $result = array();
        return $result;
    }

    return $result;
}

// 発送先情報を取得
function getShipData ($dbConnect,$compId) {

    $sql  = "";
    $sql .= " SELECT";
    $sql .=     " CompID";
    $sql .=     ",CompCd";
    $sql .=     ",CompName";
    $sql .=     ",Zip";
    $sql .=     ",Adrr";
    $sql .=     ",Tel";
    $sql .=     ",ShipName";
    $sql .=     ",TantoName";
    $sql .= " FROM";
    $sql .=     " M_Comp";
    $sql .= " WHERE";
    $sql .=     " Del = ".DELETE_OFF;
    if($compId != ""){
        $sql .= " AND";
        $sql .=     " CompID  = '" . db_Escape($compId) . "'";
    }
    $sql .= " ORDER BY CompCd";
    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合
    if (count($result) <= 0) {
        $result = array();
        return $result;
    }

    return $result[0];
    
}

// 店舗移動チェック
function _checkMoveComp($dbConnect,$staffId,$nextCompId) {
    
    $sql  = " SELECT";
    $sql .=     " CompID";
    $sql .= " FROM";
    $sql .=     " M_Staff";
    $sql .= " WHERE";
    $sql .=     " StaffSeqID = '" . db_Escape($staffId) . "'";
    $sql .= " AND";
    $sql .=     " Del = ".DELETE_OFF;

    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合
    if (count($result) <= 0) {
        return false;
    }

    $oldCompId = $result[0]['CompID'];

    // 登録店舗と画面入力値を比較
    if ($oldCompId == $nextCompId) {
        return false;
    }

//var_dump($oldCompId);
//var_dump($nextCompId);
//die;

    return true;
}

/*
 * ユーザーマスタ情報を取得する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$userId         => 検索ユーザーID
 * 戻り値：$result         => ユーザーマスタ情報
 *
 * create 2007/03/26 H.Osugi
 *
 */
function getUserMaster($dbConnect, $StaffSeqID) {

    $sql  = "";
    $sql .= " SELECT";
    $sql .=     "  ms.StaffSeqID";
    $sql .=     " ,ms.CompID";
    $sql .=     " ,ms.CompCd";
    $sql .=     " ,ms.StaffCode";
    $sql .=     " ,ms.PersonName";
    $sql .=     " , CONVERT(char, ms.HatureiDay, 11) as HatureiDay";    
    $sql .=     " ,ms.NextNameCd";
    $sql .=     " ,ms.NextCompID";
    $sql .=     " ,mc.CompID";
    $sql .=     " ,mc.CompName";

    $sql .= " FROM";
    $sql .=     " M_Staff AS ms";
    $sql .= " INNER JOIN M_Comp AS mc ON ms.CompCd = mc.CompCd";
    $sql .= " WHERE";
    $sql .=     " ms.Del = ".DELETE_OFF;
    $sql .= " AND";
    $sql .=     " mc.Del = ".DELETE_OFF;
    $sql .= " AND";
    $sql .=     " ms.StaffSeqID = '" . db_Escape($StaffSeqID) . "'";

    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合
    if (count($result) <= 0) {
        return false;
    }

    return $result[0];
}

function d($data){
    print "<pre>";
    print_r($data);
    print "</pre>";
}

// 店舗移動メールを送信する
function sendMoveInfo($dbConnect,$userInfo,$oldStaffInfo) {

    // 新旧の店舗情報を取得
    $result = getCompMaster($dbConnect,trim($userInfo['NextCompID']));
    $compInfo['new'] = $result[0];

    $result = getCompMaster($dbConnect,trim($oldStaffInfo['CompID']));
    $compInfo['old'] = $result[0];

    // 現在のスタッフ情報を取得
    $staffInfo['new'] = getUserMaster($dbConnect, $userInfo['StaffSeqID']); 
    $staffInfo['old'] = $oldStaffInfo; 

    
    $filePath = '../../mail_template/';

    $isSuccess = moveCompMail($dbConnect,$filePath, $compInfo, $staffInfo, $subject, $message);

    if ($isSuccess == false) {
        return false;
    }
    $toAddr = MAIL_GROUP_6;

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

?>
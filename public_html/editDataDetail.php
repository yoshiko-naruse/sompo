<?php
/*
 * 申請データ編集詳細画面
 * editDataDetail.src.php
 *
 * create 2008/08/09 W.Takasaki
 *
 *
 */

// 各種モジュールの取得
include_once('../include/define.php');           // 定数定義
include_once('../include/dbConnect.php');        // DB接続モジュール
include_once('../include/msSqlControl.php');     // DB操作モジュール
include_once('../include/checkLogin.php');       // ログイン判定モジュール
include_once('../include/castHtmlEntity.php');   // HTMLエンティティモジュール
include_once('../include/redirectPost.php');     // リダイレクトポストモジュール
include_once('../error_message/errorMessage.php');   // エラーメッセージ

// データ編集用ステータス配列
$DISPLAY_DATAEDIT_STATUS = array(
                        STATUS_APPLI            => '承認待',       // 1
                        STATUS_APPLI_DENY       => '否認',        // 2
//                      STATUS_APPLI_ADMIT      => '承認済',       // 3
                        STATUS_APPLI_ADMIT      => '申請済',       // 3 承認の概念がないため、名称変更
                        STATUS_STOCKOUT         => '在庫切',       // 13
                        STATUS_ORDER            => '受注済',       // 14
                        STATUS_SHIP             => '出荷済',       // 15
                        STATUS_DELIVERY         => '納品済',       // 16

                        STATUS_NOT_RETURN       => '返品承認待',     // 18
                        STATUS_NOT_RETURN_DENY  => '返品否認',      // 19
                        STATUS_NOT_RETURN_ADMIT => '未返却',       // 20
                        STATUS_NOT_RETURN_ORDER => '未返却',       // 21
                        STATUS_RETURN           => '返却済',       // 22
                        STATUS_RETURN_NOT_APPLY => '返品未申請',     // 25

                        STATUS_CANCEL           => 'キャンセル',     // 30
                        STATUS_DISPOSAL         => '廃棄',          // 31

                        STATUS_LOSS             => '紛失承認待',     // 32
                        STATUS_LOSS_DENY        => '紛失否認',      // 33
                        STATUS_LOSS_ADMIT       => '紛失 承認済',            // 34
                        STATUS_LOSS_ORDER       => '紛失済'             // 35
);

// エラーメッセージ
define('ERR_MSG_EDITDATA_DATE_CONFRICT'      , $editData['001']);
define('ERR_MSG_EDITDATA_NOTDATE_SHIPDAY'        , $editData['002']);
define('ERR_MSG_EDITDATA_NOTDATE_RETURNDAY'      , $editData['003']);
define('ERR_MSG_EDITDATA_SQL_FAILED'             , $editData['004']);
define('ERR_MSG_EDITDATA_STATUS_INVALID'         , $editData['005']);

// 初期設定
$isMenu_hacyu = false;  // 発注のメニューをアクティブに
$isMenu_henpin = false; // 返品のメニューをアクティブに
$isMenu_koukan = false; // 交換のメニューをアクティブに

// 変数の初期化 ここから ******************************************************
$requestNo = '';                    // 申請番号
$_SESSION['returnflg'] = "";
$_SESSION[SESSION_NAMES_ERR] = "";

$syokubasyounin = "";
$hifukusyounin = "";

$searchAppliNo = '';
$searchStaffCode = '';

$isError = false;       // エラーフラグ

$isTokusunFlag = false; // 特注フラグ

$selectedMode1 = false;
$selectedMode2 = false;

$selectedReason1 = false;
$selectedReason2 = false;
$selectedReason3 = false;
$selectedReason4 = false;
$selectedReason5 = false;
$selectedReason6 = false;
$selectedReason7 = false;
$selectedReason8 = false;
$selectedReason9 = false;

// 変数の初期化 ここまで ******************************************************
// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST); 

// データ登録フラグがたっていたらデータ更新
if ($post['submitKey']) {
    // データチェック
    $errMsg = _checkData($post, $DISPLAY_DATAEDIT_STATUS);
    if ($errMsg != '') {
        // エラーメッセージをセット
        $isError = true;
        
    } else {
        // トランザクション開始
        db_Transaction_Begin($dbConnect);
        
        // orderIDからdetail情報を取得し、削除チェックを確認
        $detailData = _order_detail($dbConnect,$post['orderId']);
        if (!$detailData) {
            // ロールバック
            db_Transaction_Rollback($dbConnect);
            // エラー処理
            $_SESSION[SESSION_NAMES_ERR][]['errs'] = ERR_MSG_EDITDATA_SQL_FAILED;
            _err_disp('./editData.php');
        }
        $deleteHeader = true;
        $deleteList   = array();
        for($i=0;$i<count($detailData);$i++) {
            if (isset($post['delete'][$detailData[$i]['OrderDetID']]) && $post['delete'][$detailData[$i]['OrderDetID']] == 1) {
                $deleteList[] = $detailData[$i]['OrderDetID'];
            } else {
                $deleteHeader = false;
            }
        }

        // 全てのdetailデータを削除する場合はヘッダーを削除
        if ($deleteHeader) {
            // T_Orderを削除
            if (!deleteT_Order($dbConnect,$post['orderId'])) {
                // ロールバック
                db_Transaction_Rollback($dbConnect);
                // エラー処理
                $_SESSION[SESSION_NAMES_ERR][]['errs'] = ERR_MSG_EDITDATA_SQL_FAILED;
                _err_disp('./editData.php');
            }
            // コミット
            db_Transaction_Commit($dbConnect);
            //　申請情報検索画面に遷移
            $hiddens['searchAppliNo']   = trim($post['searchAppliNo']);
            $hiddens['searchStaffCode'] = trim($post['searchStaffCode']);
            $hiddens['searchFlg']       = '1';
            $action                    = './editData.php';
            redirectPost($action, $hiddens);
            exit;
        } else {
            for($i=0;$i<count($detailData);$i++) {
                                    
                // T_OrderDetailsを更新
                if (!updateDetail($dbConnect,$detailData[$i]['OrderDetID'], $post)) {
                    // ロールバック
                    db_Transaction_Rollback($dbConnect);
                    // エラー処理
                    $_SESSION[SESSION_NAMES_ERR][]['errs'] = ERR_MSG_EDITDATA_SQL_FAILED;
                    _err_disp('./editData.php');
                }
            }
            // T_OrderDetailsデータの削除があった場合は行番号を更新
            if (count($deleteList) > 0) {
                if (!updateDetailLineNo($dbConnect,$detailData, $deleteList)) {
                    // ロールバック
                    db_Transaction_Rollback($dbConnect);
                    // エラー処理
                    $_SESSION[SESSION_NAMES_ERR][]['errs'] = ERR_MSG_EDITDATA_SQL_FAILED;
                    _err_disp('./editData.php');
                }
            }
            
            // T_Orderのステータス,発注日、返品日,緊急フラグを更新
            if (!updateHeader($dbConnect,$post)) {
                // ロールバック
                db_Transaction_Rollback($dbConnect);
                // エラー処理
                $_SESSION[SESSION_NAMES_ERR][]['errs'] = ERR_MSG_EDITDATA_SQL_FAILED;
                _err_disp('./editData.php');
            }
        }
        // コミット
        db_Transaction_Commit($dbConnect);
    }
}


// オーダー情報取得
$order = castHtmlEntity(getOrderData($dbConnect,$post['orderId']));

// 表示のONOFF
switch(substr(trim($order['AppliNo']),0,1)){
    case 'A':   // 発注
        $itemdata= _hacyu_data($dbConnect,$post, $DISPLAY_DATAEDIT_STATUS);
        $isMenu_hacyu = true;
        break;
    case 'R':   // 返品
        $itemdata = _henpin_data($dbConnect,$post, $DISPLAY_DATAEDIT_STATUS);
    // 返却理由
    switch (trim($order['AppliReason'])) {
    
        // 返却理由（退職・異動返却）
        case APPLI_REASON_RETURN_RETIRE:
            $selectedReason1 = true;
            $selectedMode1 = true;
            break;
    
        // 返却理由（その他返却）
        case APPLI_REASON_RETURN_OTHER:
            $selectedReason2 = true;
            $selectedMode1 = true;
            break;
    
        // 返却理由（サイズ交換キャンセル返却）
        case APPLI_REASON_EXCHANGE_SIZE_RETURN:
            $selectedReason3 = true;
            $selectedMode1 = true;
            break;
    
            // 交換理由（サイズ交換）
        case APPLI_REASON_EXCHANGE_SIZE:
            $selectedReason4 = true;
            $selectedMode2 = true;
            break;
    
        // 交換理由（汚損・破損交換）
        case APPLI_REASON_EXCHANGE_BREAK:
            $selectedReason5 = true;
            $selectedMode2 = true;
            break;
    
        // 交換理由（紛失交換）
        case APPLI_REASON_EXCHANGE_LOSS:
            $selectedReason6 = true;
            $selectedMode2 = true;
            break;
    
        // 交換理由（不良品交換）
        case APPLI_REASON_EXCHANGE_INFERIORITY:
            $selectedReason7 = true;
            $selectedMode2 = true;
            break;
    
        // 交換理由（役職変更交換）
        case APPLI_REASON_EXCHANGE_CHANGEGRADE:
            $selectedReason8 = true;
            $selectedMode2 = true;
    
        // 交換理由（マタニティとの交換）
        case APPLI_REASON_EXCHANGE_MATERNITY:
            $selectedReason9 = true;
            $selectedMode2 = true;
            break;
    
        default:
            break;
    }
                
        // 紛失物があるかどうかチェック
        $lostflg = false;
        $max = count($itemdata);
        for($i=0;$i<$max;$i++){
            if($itemdata[$i]['flg']){
                $lostflg = true;
            }
        }
        
        $isMenu_henpin = true;
        break;
}

$orderId = $post['orderId'];


// 申請検索画面のパラメータをセット
$searchAppliNo   = $post['searchAppliNo'];
$searchStaffCode = $post['searchStaffCode'];


// 表示する値の成型 ここまで +++++++++++++++++++++++++++++++++++++++++++++++++++


// 発注データ表示
function _hacyu_data($dbConnect,$post, $DISPLAY_DATAEDIT_STATUS){
    // オーダー明細情報取得
    global $DISPLAY_STATUS;
    global $DISPLAY_STATUS_ACCEPTATION;

    // セレクトボックス用 ステータス配列作成
    $status = array();
    
    
    $itemdata = _order_detail($dbConnect,$post['orderId'], $post);
    $max = count($itemdata);
    for($i=0;$i<$max;$i++){
        if($itemdata[$i]['IcTagCd'] == ""){
            $itemdata[$i]['IcTagCd'] = "&nbsp;";
        }
        $itemdata[$i]['isEmptyShipDay'] = false;
        if($itemdata[$i]['ShipDay'] == ""){
            $itemdata[$i]['isEmptyShipDay'] = true;
        }
        $itemdata[$i]['isEmptyReturnDay'] = false;
        if($itemdata[$i]['ReturnDay'] == ""){
            $itemdata[$i]['isEmptyReturnDay'] = true;
        }
        $itemdata[$i]['lineNo'] = $i+1;
        
        $statusAry = array();
        foreach ($DISPLAY_DATAEDIT_STATUS as $key => $value) {
            if ($key <= STATUS_DELIVERY || $key == STATUS_CANCEL || $key == STATUS_DISPOSAL) {    
                $statusAry[$key] = $value; 
            }
        }
        
        $itemdata[$i]['statusBox'] = _make_selectboxdata($statusAry,"status[".$itemdata[$i]['OrderDetID']."]",$itemdata[$i]['Status']);
    
    }
    
    return $itemdata;
}
// 返品データ表示
function _henpin_data($dbConnect,$post, $DISPLAY_DATAEDIT_STATUS){
    
    global $DISPLAY_STATUS;
    global $DISPLAY_HENPINMEISAI_STATUS;

    $itemdata = _order_detail($dbConnect,$post['orderId'], $post);
    $max = count($itemdata);
    for($i=0;$i<$max;$i++){
        if($itemdata[$i]['IcTagCd'] == ""){
            $itemdata[$i]['IcTagCd'] = "&nbsp;";
        }
        $itemdata[$i]['isEmptyShipDay'] = false;
        if($itemdata[$i]['ShipDay'] == ""){
            $itemdata[$i]['isEmptyShipDay'] = true;
        }
        
        $itemdata[$i]['isEmptyReturnDay'] = false;
        if($itemdata[$i]['ReturnDay'] == ""){
            $itemdata[$i]['isEmptyReturnDay'] = true;
        }
        
        $itemdata[$i]['lineNo'] = $i+1;

        // 紛失物フラグ
        $itemdata[$i]['flg'] = $rtn['flg'];
        
        $statusAry = array();
        foreach ($DISPLAY_DATAEDIT_STATUS as $key => $value) {

            if (!$itemdata[$i]['flg'] && $key >= STATUS_NOT_RETURN && $key <= STATUS_LOSS_ORDER) {    
                $statusAry[$key] = $value; 
            }
        }

        $itemdata[$i]['statusBox'] = _make_selectboxdata($statusAry,"status[".$itemdata[$i]['OrderDetID']."]",$itemdata[$i]['Status']);

    }
    return $itemdata;
}

// 入力データをチェックする
function _checkData($post, $DISPLAY_DATAEDIT_STATUS) {

    $errMsg = array();
    foreach ($post['shipDay'] as $key => $value) {
        // 出荷日の日付チェック
       if (trim($value) != '' && !_chk_is_date(trim($value))) {
            $errMsg[] = ERR_MSG_EDITDATA_NOTDATE_SHIPDAY;
            break;
       }
    }

    foreach ($post['returnDay'] as $key => $value) {
        // 返品日の日付チェック
        if (trim($value) != '' && !_chk_is_date(trim($value))) {
            $errMsg[] = ERR_MSG_EDITDATA_NOTDATE_RETURNDAY;
            break;
        }
    }

    // 出荷日と返品日の矛盾チェック
    foreach ($post['shipDay'] as $key => $value) {
        if (trim($value) != '' && trim($post['returnDay'][$key]) != '' && _chk_is_date(trim($value)) && _chk_is_date(trim($post['returnDay'][$key]))) {
            if (trim($value) > trim($post['returnDay'][$key])) {
                $errMsg[] = ERR_MSG_EDITDATA_DATE_CONFRICT;
                break;
            }
        }
    }    
    
    // ステータスのチェック
    foreach ($post['status'] as $key => $value) {
        if (trim($value) == '' || !array_key_exists(trim($value), $DISPLAY_DATAEDIT_STATUS)) {
                $errMsg[] = ERR_MSG_EDITDATA_STATUS_INVALID;
                break;
        }
        
    }
    
    if (count($errMsg) > 0) {
       return implode('<br>', $errMsg);    
    } else {
        return '';
    }
}

/*
 * 申請情報を取得する
 * 引数  ：$dbConnect    => コネクションハンドラ
 *       ：$orderId      => OrderID
 * 戻り値：$result       => 申請情報
 *
 * create 2007/03/26 H.Osugi
 *
 */
function getOrderData($dbConnect, $orderId) {

    // 表示する申請情報を取得する
    $sql  = "";
    $sql .= " SELECT";
    $sql .=     " torder.OrderID,";
    $sql .=     " torder.AppliNo,";
    $sql .=     " CONVERT(varchar,torder.AppliDay,111) as AppliDay,";
    $sql .=     " mc.CompCd,";
    $sql .=     " mc.CompName,";
    $sql .=     " torder.StaffCode,";
    $sql .=     " torder.PersonName,";
    $sql .=     " torder.AppliReason,";
    $sql .=     " torder.Zip,";
    $sql .=     " torder.Adrr,";
    $sql .=     " torder.Tel,";
    $sql .=     " torder.ShipName,";
    $sql .=     " torder.TantoName,";
    $sql .=     " torder.Note,";
    $sql .=     " torder.Tok,";
    $sql .=     " torder.TokNote,";
    $sql .=     " YoteiDay = CASE";
    $sql .=     " WHEN";
    $sql .=         " torder.YoteiDay = NULL";
    $sql .=             " THEN";
    $sql .=                 " NULL";
    $sql .=             " ELSE";
    $sql .=             " CONVERT(varchar,torder.YoteiDay,11)";
    $sql .=         " END";
    $sql .= " FROM";
    $sql .=     " T_Order torder";
    $sql .=     " INNER JOIN";
    $sql .=     " M_Staff ms";
    $sql .=     " ON";
    $sql .=         " torder.StaffID = ms.StaffSeqID";
    $sql .=     " INNER JOIN";
    $sql .=     " M_Comp mc";
    $sql .=     " ON";
    $sql .=         " ms.CompID = mc.CompID";
    $sql .= " WHERE";
    $sql .=     " torder.OrderID = '" . db_Escape($orderId) . "'";
    $sql .= " AND";
    $sql .=     " torder.Del = " . DELETE_OFF;

    $result = db_Read($dbConnect, $sql);

    // 情報が取得できなかった場合
    if (!is_array($result) || count($result) <= 0) {
        return false;
    }

    list($result[0]['zip1'], $result[0]['zip2']) = explode('-', $result[0]['Zip']);
    
    return $result[0];

}
// オーダー詳細発注
function _order_detail($dbConnect,$oid, $post=null){

    $sql  = " SELECT";
    $sql .= " tod.OrderDetID";
    $sql .= " ,tod.OrderID";
    $sql .= " ,tod.ItemID";
    $sql .= " ,tod.ItemNo";
    $sql .= " ,tod.ItemName";
    $sql .= " ,tod.BarCd";
    $sql .= " ,tod.IcTagCd";
    $sql .= " ,tod.Size";
    $sql .= " ,tod.Status";
    $sql .= " ,CONVERT(char, tod.ShipDay, 111) as ShipDay";
    $sql .= " ,CONVERT(char, tod.ReturnDay, 111) as ReturnDay";
    $sql .= " FROM  T_Order_Details as tod INNER JOIN M_Item as mi ON tod.ItemID = mi.ItemID";
    $sql .= " WHERE ";
    $sql .= " tod.OrderID = '". db_Escape(trim($oid))."'";
    $sql .= " AND tod.Del = " . DELETE_OFF;
    $sql .= " ORDER BY tod.ItemID ASC";

    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合
    if (count($result) <= 0) {
        $_SESSION[SESSION_NAMES_ERR][]['errs'] = ERR_MSG_NO_UNIFORM;
        _err_disp();
        return $result;
    }
    $max = count($result);
    for($i=0;$i<$max;$i++){
        $result[$i]['linNo'] = $i+1;

        if (isset($post['shipDay'][$result[$i]['OrderDetID']])) {
            $result[$i]['ShipDay'] = $post['shipDay'][$result[$i]['OrderDetID']];
        }
    
        if (isset($post['returnDay'][$result[$i]['OrderDetID']])) {
            $result[$i]['ReturnDay'] = $post['returnDay'][$result[$i]['OrderDetID']];
        }

        if (isset($post['status'][$result[$i]['OrderDetID']])) {
            $result[$i]['Status'] = $post['status'][$result[$i]['OrderDetID']];
        }
        
    }

    return $result;
}

// T_Orderを削除する
function deleteT_Order($dbConnect,$orderId) {
    $sql  = "";
    $sql .= " UPDATE";
    $sql .=     " T_Order";
    $sql .= " SET";
    $sql .=     " Del = ".DELETE_ON;
    $sql .= " WHERE";
    $sql .=     " OrderID = '". db_Escape(trim($orderId))."'";

    $isSuccess = db_Execute($dbConnect, $sql);

    // 実行結果が失敗の場合
    if ($isSuccess == false) {
        return false;
    }
    return true;
    
}

// T_OrderDetailsを更新する
function updateDetail($dbConnect,$OrderDetID, $post) {

    $delete = '';
    if (isset($post['delete'][$OrderDetID]) && trim($post['delete'][$OrderDetID]) == '1') {
        $delete .= " ,Del = ".DELETE_ON;
    }
    if (isset($post['shipDay'][$OrderDetID]) && trim($post['shipDay'][$OrderDetID]) != '') {
        $shipDay = " ,ShipDay = convert(datetime,'".db_Escape(trim($post['shipDay'][$OrderDetID]))."',111)";    
    } else {
        $shipDay = " ,ShipDay = NULL";    
    }

    if (isset($post['returnDay'][$OrderDetID]) && trim($post['returnDay'][$OrderDetID]) != '') {
        $returnDay = " ,ReturnDay = convert(datetime,'".db_Escape(trim($post['returnDay'][$OrderDetID]))."',111)";    
    } else {
        $returnDay = " ,ReturnDay = NULL";    
    }

    if (!isset($post['status'][$OrderDetID]) || $post['status'][$OrderDetID] == '') {
        return false;    
    }

    $sql  = " UPDATE";
    $sql .=     " T_Order_Details";
    $sql .= " SET";
    $sql .=     "  Status = ".$post['status'][$OrderDetID];
    $sql .=     " ,UpdDay = GETDATE()";
    $sql .=     " ,UpdUser = '".db_Escape(trim($_SESSION['LOGINNAME']))."'";
    $sql .= $delete;
    $sql .= $shipDay;
    $sql .= $returnDay;
    $sql .= " WHERE";
    $sql .=     " OrderDetID = '".db_Escape(trim($OrderDetID))."'";
        
    $isSuccess = db_Execute($dbConnect, $sql);

    // 実行結果が失敗の場合
    if ($isSuccess == false) {
        return false;
    }
    return true;
}

// detailデータの行番号を更新する
function updateDetailLineNo($dbConnect,$detailData, $deleteList){

    // 行番号を設定
    $line=0;
    for ($i=0;$i<count($detailData);$i++) {
        if (!in_array($detailData[$i]['OrderDetID'], $deleteList)) { 
            $line++;
            $sql  = " UPDATE";
            $sql .=     " T_Order_Details";
            $sql .= " SET";
            $sql .=     "  AppliLNo = ".$line;
            $sql .=     " ,UpdDay = GETDATE()";
            $sql .=     " ,UpdUser = '".db_Escape(trim($_SESSION['LOGINNAME']))."'";
            $sql .= " WHERE";
            $sql .=     " OrderDetID = '".db_Escape(trim($detailData[$i]['OrderDetID']))."'";
    
            $isSuccess = db_Execute($dbConnect, $sql);
        
            // 実行結果が失敗の場合
            if ($isSuccess == false) {
                return false;
            }
        }
    }
    return true;    
}

// T_Orderのステータスを更新する
function updateHeader($dbConnect, $post) {
    
    //　T_Orderのステータスを取得
    $sql  = " SELECT"; 
    $sql .=     "  MIN(Status) as minStatus"; 
    $sql .=     " ,MAX(ShipDay) as maxShipDay"; 
    $sql .=     " ,MAX(ReturnDay) as maxReturnDay"; 
    $sql .= " FROM"; 
    $sql .=     " T_Order_Details"; 
    $sql .= " WHERE"; 
    $sql .=     " OrderID = '".db_Escape(trim($post['orderId']))."'";
    $sql .= " AND"; 
    $sql .=     " Del = ".DELETE_OFF;
    
    $detailData = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合
    if (count($detailData) <= 0) {
        return false;
    }

    $updSql  = " UPDATE";     
    $updSql .=     " T_Order";     
    $updSql .= " SET";
    $updSql .=     " Status = ".db_Escape(trim($detailData[0]['minStatus']));     
    if (!is_null($detailData[0]['maxShipDay']) && $detailData[0]['maxShipDay'] != '') {
        $updSql .=     " ,ShipDay = convert(datetime,'".db_Escape(trim($detailData[0]['maxShipDay']))."',111)";     
    } else {
        $updSql .=     " ,ShipDay = NULL";     
    }
    if (!is_null($detailData[0]['maxReturnDay']) && $detailData[0]['maxReturnDay'] != '') {
        $updSql .=     " ,ReturnDay = convert(datetime,'".db_Escape(trim($detailData[0]['maxReturnDay']))."',111)";     
    } else {
        $updSql .=     " ,ReturnDay = NULL";     
    }
    $updSql .= " WHERE";     
    $updSql .=     " OrderID = '".db_Escape(trim($post['orderId']))."'";
    $updSql .= " AND"; 
    $updSql .=     " Del = ".DELETE_OFF;

    $isSuccess = db_Execute($dbConnect, $updSql);

    // 実行結果が失敗の場合
    if ($isSuccess == false) {
        return false;
    }
    return true;
}
/*
 * アイテムのセレクトボックスを作成する
 * 引数  ：$items      => selectbox情報
 * 引数  ：$name       => セレクトボックス名
 * 引数  ：$valuefild  => valueとなるフィールド名
 * 引数  ：$dispfild   => 表示したいフィールド名
 * 引数  ：$select     => 選択状態にしたいvalue
 * 戻り値：$selectbox  => セレクトボックス
 *
 * create 2007/11/26 DF
 *
 */
function _make_selectboxdata($items,$name,$select="", $prefix=false){

    // selectボックス作成
    $selectbox ="";
    $selectbox .="<select name=\"$name\" id=\"select\">\n";
    if ($prefix) {
        $selectbox .="   <option value=\"\"></option>\n";
    }
    $max = count($items);
    foreach ($items as $key => $value) {
        
        if((int)$select === (int)$key){
            // 選択項目があるとき
            $selectbox .="  <option value=\"".$key."\" selected>".$value."</option>  \n";
        }else{
            // 選択項目がない時
            $selectbox .="  <option value=\"".$key."\">".$value."</option>  \n";
        }
    }
    $selectbox .="</select>";
    
    return $selectbox;
}

// 日付判定
function _chk_is_date($pValue, $pSplit = "/")
{
    if ( substr_count($pValue, $pSplit) <> 2 ) {
        return FALSE;
    }
    
    list($year, $month, $day) = explode($pSplit, $pValue);
    if ( ereg('^[0-9]{4}', $year) && _chk_is_number($month) && _chk_is_number($day) ) {
        $rtn =  ( checkdate($month, $day, $year) ) ? TRUE : FALSE;
    } else {
        $rtn = FALSE;
    }
    return $rtn;
}
// 数値判定
function _chk_is_number($pValue)
{
    if ( !ereg('^[0-9]+$', $pValue) ) {
        $rtn = FALSE;
    } else {
        $rtn = TRUE;
    }
    return $rtn;
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
    function dataSubmit() {
        document.getElementById('submitKey').value = '1';
        document.editForm.submit();    

        return false;
    }

    function backSubmit() {
        document.editForm.action='./editData.php';    
        document.editForm.submit();    

        return false;
    }

	function checkAll() {
	
		for (i = 0; i < document.editForm.elements.length; i++) {
	    	if (document.editForm.elements[i].name.substring(0,6) == 'delete') {
		       document.editForm.elements[i].checked = true;
		    }
		}
	}

    function getCheck() {
        if (document.getElementById('allDelete').checked) {
            checkAll();
        }    
    }

    function untick() {
        var allCheck = true;

        for (i = 0; i < document.editForm.elements.length; i++) {
            if (document.editForm.elements[i].name.substring(0,6) == 'delete') {
               if (document.editForm.elements[i].checked == false) {
                   allCheck = false;
               }
            }
        }

        if (allCheck) {
            document.getElementById('allDelete').checked = true;
        } else {
            document.getElementById('allDelete').checked = false;
        }
    }
    // -->
    </script>
  </head>
  <body>
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
      <form method="post" name="editForm" action="./editDataDetail.php">
        <div id="contents">
          <h1>申請データ変更詳細</h1>
          <table border="0" width="700" cellpadding="0" cellspacing="0" class="tb_1">
            <tr>
              <td width="100" class="line"><span class="fbold">申請番号</span></td>
              <td class="line"><?php isset($order['AppliNo']) ? print($order['AppliNo']) : print('&#123;order.AppliNo&#125;'); ?></td>
              <td width="60" class="line"><span class="fbold">申請日</span></td>
              <td class="line">
<?php if(!$isEmptyRequestDay) { ?>
                <?php isset($order['AppliDay']) ? print($order['AppliDay']) : print('&#123;order.AppliDay&#125;'); ?>
<?php } ?>
<?php if($isEmptyRequestDay) { ?>
                
                &nbsp;
                
<?php } ?>
              </td>
            </tr>
            <tr>
              <td class="line"><span class="fbold">基地名</span></td>
              <td colspan="3" class="line"><?php isset($order['CompCd']) ? print($order['CompCd']) : print('&#123;order.CompCd&#125;'); ?>：<?php isset($order['CompName']) ? print($order['CompName']) : print('&#123;order.CompName&#125;'); ?></td>
            </tr>
            <tr>
              <td class="line"><span class="fbold">職員コード</span></td>
              <td  class="line"><?php isset($order['StaffCode']) ? print($order['StaffCode']) : print('&#123;order.StaffCode&#125;'); ?></td>
              <td width="80" class="line">
                <span class="fbold">着用者氏名</span>
              </td>
              <td width="400" class="line"><?php isset($order['PersonName']) ? print($order['PersonName']) : print('&#123;order.PersonName&#125;'); ?></td>
            </tr>
<?php if(!$isMenu_hacyu) { ?>
<?php } ?>
<?php if($isMenu_hacyu) { ?>
            <tr>
              <td class="line"><span class="fbold">出荷先</span></td>
              <td  class="line" colspan="3">〒<?php isset($order['zip1']) ? print($order['zip1']) : print('&#123;order.zip1&#125;'); ?>-<?php isset($order['zip2']) ? print($order['zip2']) : print('&#123;order.zip2&#125;'); ?></td>
            </tr>
          </table>
          <table border="0" width="700" cellpadding="0" cellspacing="0" class="tb_1">
            <tr>
              <td width="100" class="line">&nbsp;</td>
              <td width="100" class="line"><span class="fbold">住所</span></td>
              <td width="482" colspan="5" class="line"><?php isset($order['Adrr']) ? print($order['Adrr']) : print('&#123;order.Adrr&#125;'); ?></td>
            </tr>
            <tr>
              <td width="100" class="line">&nbsp;</td>
              <td width="100" class="line"><span class="fbold">出荷先名</span></td>
              <td width="482" colspan="5" class="line"><?php isset($order['ShipName']) ? print($order['ShipName']) : print('&#123;order.ShipName&#125;'); ?></td>
            </tr>
            <tr>
              <td width="100" class="line">&nbsp;</td>
              <td width="100" class="line"><span class="fbold">ご担当者</span></td>
              <td width="482" colspan="5" class="line"><?php isset($order['TantoName']) ? print($order['TantoName']) : print('&#123;order.TantoName&#125;'); ?></td>
            </tr>
            <tr>
              <td width="100" class="line">&nbsp;</td>
              <td width="100" class="line"><span class="fbold">電話番号</span></td>
              <td width="482" colspan="5" class="line"><?php isset($order['Tel']) ? print($order['Tel']) : print('&#123;order.Tel&#125;'); ?></td>
            </tr>
<?php } ?>
<?php if($isMenu_henpin) { ?>
            <tr>
              <td width="80" class="line"><span class="fbold">
<?php if($selectedMode1) { ?>
                  返却
<?php } ?>
<?php if($selectedMode2) { ?>
                  
                  交換
                  
<?php } ?>
              </span></td>
              <td colspan="6" class="line">
<?php if($selectedReason1) { ?>
                  退職・異動返却
<?php } ?>
<?php if($selectedReason2) { ?>
                  
                  その他返却
                  
<?php } ?>
<?php if($selectedReason3) { ?>
                  
                  サイズ交換(無償)キャンセル
                  
<?php } ?>
<?php if($selectedReason4) { ?>
                  
                  サイズ交換(無償)
                  
<?php } ?>
<?php if($selectedReason5) { ?>
                  
                  汚損・破損交換(有償)
                  
<?php } ?>
<?php if($selectedReason6) { ?>
                  
                  紛失交換(有償)
                  
<?php } ?>
<?php if($selectedReason7) { ?>
                  
                  不良品交換(無償)
                  
<?php } ?>
<?php if($selectedReason8) { ?>
                  
                  役職変更による交換
                  
<?php } ?>
<?php if($selectedReason9) { ?>
                  
                  マタニティとの交換
                  
<?php } ?>
              </td>
            </tr>
<?php } ?>
            <tr>
              <td width="100" class="line"><span class="fbold">メモ</span></td>
              <td colspan="6" class="line">
                <?php isset($order['Note']) ? print($order['Note']) : print('&#123;order.Note&#125;'); ?>&nbsp;
              </td>
            </tr>
<?php if($dispYoteiDay) { ?>
                    <tr>
                      <td width="100" class="line"><span class="fbold">出荷日</span></td>
                      <td colspan="6" class="line"><?php isset($yoteiDay) ? print($yoteiDay) : print('&#123;yoteiDay&#125;'); ?>&nbsp;</td>
                    </tr>
<?php } ?>
          </table>
          <h3>◆発注or返品明細</h3>
          <table width="760" border="0" class="tb_1" cellpadding="0" cellspacing="3">
            <tr>
              <th align="center" width="50">No</th>
              <th align="center" width="200">アイテム名</th>
              <th align="center" width="80">サイズ</th>
              <th align="center" width="120">単品番号</th>
              <th align="center" width="80">出荷日</th>
              <th align="center" width="80">返品日</th>
              <th align="center" width="100">状態</th>
              <th align="center" width="50">削除</th>
            </tr>

<?php for ($i1_itemdata=0; $i1_itemdata<count($itemdata); $i1_itemdata++) { ?>
            <tr height="20" valign="middle">
              <td class="line2" align="center"><?php isset($itemdata[$i1_itemdata]['linNo']) ? print($itemdata[$i1_itemdata]['linNo']) : print('&#123;itemdata.linNo&#125;'); ?></td>
              <td class="line2"><?php isset($itemdata[$i1_itemdata]['ItemName']) ? print($itemdata[$i1_itemdata]['ItemName']) : print('&#123;itemdata.ItemName&#125;'); ?></td>
              <td class="line2" align="center"><?php isset($itemdata[$i1_itemdata]['Size']) ? print($itemdata[$i1_itemdata]['Size']) : print('&#123;itemdata.Size&#125;'); ?></td>
              <td class="line2" align="center">
<?php if($itemdata[$i1_itemdata]['IcTagCd']) { ?>
              <?php isset($itemdata[$i1_itemdata]['IcTagCd']) ? print($itemdata[$i1_itemdata]['IcTagCd']) : print('&#123;itemdata.IcTagCd&#125;'); ?>
<?php } ?>
<?php if(!$itemdata[$i1_itemdata]['IcTagCd']) { ?>
              &nbsp;
<?php } ?>
              </td>
<?php if(!$itemdata[$i1_itemdata]['isEmptyShipDay']) { ?>
              <td class="line2" align="center"><input type="text" size="12" name="shipDay[<?php isset($itemdata[$i1_itemdata]['OrderDetID']) ? print($itemdata[$i1_itemdata]['OrderDetID']) : print('&#123;itemdata.OrderDetID&#125;'); ?>]" value="<?php isset($itemdata[$i1_itemdata]['ShipDay']) ? print($itemdata[$i1_itemdata]['ShipDay']) : print('&#123;itemdata.ShipDay&#125;'); ?>"></td>
<?php } ?>
<?php if($itemdata[$i1_itemdata]['isEmptyShipDay']) { ?>
              <td class="line2" align="center"><input type="text" size="12" name="shipDay[<?php isset($itemdata[$i1_itemdata]['OrderDetID']) ? print($itemdata[$i1_itemdata]['OrderDetID']) : print('&#123;itemdata.OrderDetID&#125;'); ?>]" value=""></td>
<?php } ?>
<?php if(!$itemdata[$i1_itemdata]['isEmptyReturnDay']) { ?>
              <td class="line2" align="center"><input type="text" size="12" name="returnDay[<?php isset($itemdata[$i1_itemdata]['OrderDetID']) ? print($itemdata[$i1_itemdata]['OrderDetID']) : print('&#123;itemdata.OrderDetID&#125;'); ?>]" value="<?php isset($itemdata[$i1_itemdata]['ReturnDay']) ? print($itemdata[$i1_itemdata]['ReturnDay']) : print('&#123;itemdata.ReturnDay&#125;'); ?>"></td>
<?php } ?>
<?php if($itemdata[$i1_itemdata]['isEmptyReturnDay']) { ?>
              <td class="line2" align="center"><input type="text" size="12" name="returnDay[<?php isset($itemdata[$i1_itemdata]['OrderDetID']) ? print($itemdata[$i1_itemdata]['OrderDetID']) : print('&#123;itemdata.OrderDetID&#125;'); ?>]" value=""></td>
<?php } ?>
              <td class="line2" align="center">
                <?php isset($itemdata[$i1_itemdata]['statusBox']) ? print($itemdata[$i1_itemdata]['statusBox']) : print('&#123;itemdata.statusBox&#125;'); ?>
              </td>
              <td class="line2" align="center"><input type="checkbox" name="delete[<?php isset($itemdata[$i1_itemdata]['OrderDetID']) ? print($itemdata[$i1_itemdata]['OrderDetID']) : print('&#123;itemdata.OrderDetID&#125;'); ?>]" onClick="untick();" value="1"></td>
            </tr>
<?php } ?>

            <tr>
              <td colspan="8" align="right">
               <input type="checkbox" value="1" id="allDelete" onClick="getCheck();"><label for="allDelete">この申請を全て削除</label>　
              </td>
            </tr>
        </table>
<?php if($isError) { ?>
          <table width="760" border="0" class="tb_1" cellpadding="0" cellspacing="3">
            <tr>
             <td colspan="8" align="center"><span style="color:red"><b><?php isset($errMsg) ? print($errMsg) : print('&#123;errMsg&#125;'); ?></b></span></td>
            </tr>
　　　　　        </table>
<?php } ?>



        <br>
      </div>
      <br>
      <div class="bot" align="center">
        <a href="javascript:backSubmit();"><img src="../img/modoru.gif" alt="戻る" border="0"></a>&nbsp;&nbsp;<a href="javascript:dataSubmit();"><img src="../img/toroku.gif" alt="登録" border="0"></a>
      </div>
      <input type="hidden" name="searchFlg" value="1">
      <input type="hidden" name="searchStaffCode" value="<?php isset($searchStaffCode) ? print($searchStaffCode) : print('&#123;searchStaffCode&#125;'); ?>">
      <input type="hidden" name="searchAppliNo" value="<?php isset($searchAppliNo) ? print($searchAppliNo) : print('&#123;searchAppliNo&#125;'); ?>">
      <input type="hidden" name="orderId" value="<?php isset($order['OrderID']) ? print($order['OrderID']) : print('&#123;order.OrderID&#125;'); ?>">
      <input type="hidden" name="submitKey" id="submitKey">
      </form>

      </div>
    </div>
  </body>
</html>

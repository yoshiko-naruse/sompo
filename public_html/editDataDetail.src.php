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

?>
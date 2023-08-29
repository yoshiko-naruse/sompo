<?php
/*
 * スタッフ選択画面
 * select_staff.src.php
 *
 * create 2008/04/25 W.takasaki
 *
 *
 */

// 各種モジュールの取得
include_once('../../include/define.php');			// 定数定義
require_once('../../include/dbConnect.php');		// DB接続モジュール
require_once('../../include/msSqlControl.php');	// DB操作モジュール
require_once('../../include/checkLogin.php');		// ログイン判定モジュール
require_once('../../include/castHtmlEntity.php');	// HTMLエンティティモジュール
require_once('../../include/redirectPost.php');	// リダイレクトポストモジュール
require_once('../../include/commonFunc.php');      // 共通関数モジュール

// 初期設定
$isMenuReturn = true; 		// 返却のメニューをアクティブに

// 変数の初期化 ここから ******************************************************
$staff = array();           // スタッフコード一覧

$searchStaffCode = '';      // スタッフコード

$selectedReason1 = false;   // 返却理由（退職・異動返却）
$selectedReason2 = false;   // 返却理由（その他返却）
$selectedReason3 = false;   // 返却理由（サイズ交換キャンセル）

$isSearched      = false;   // 検索したかどうかを判定するフラグ

$searchCompCd   = '';       // 管理者権限で代行入力する時の店舗コード
$searchCompName = '';       // 管理者権限で代行入力する時の店舗名
$searchCompId   = '';       // 管理者権限で代行入力する時の店舗ID

$searchStaffCd     = '';	// 検索スタッフコード
$searchPersonName  = '';	// 検索スタッフ氏名

$errFlg = false;
$errMsg = '';
// 変数の初期化 ここまで ******************************************************

$post = castHtmlEntity($_POST);

// 取引区分をセット
if (isset($_POST['appliReason'])) {

    $appliReason = trim($post['appliReason']); 

    $title = '';
    if ($isLevelAdmin) {
        $title .= '代行入力　';
    }
    $title .= '職員選択　';

    switch($appliReason) {

        case APPLI_REASON_RETURN_RETIRE:                // 返却：退職・異動
            $isMenuReturn = true;                       // 返却のメニューをアクティブに
            $nextUrl = './henpin_shinsei.php';
            $title .= '(退職・異動返却)';
            break;
 
        case APPLI_REASON_RETURN_OTHER:                 // 返却：その他
            $isMenuReturn = true;                       // 返却のメニューをアクティブに
            $nextUrl = './henpin_shinsei.php';
            $title .= '(その他返却)';
            break;

        case APPLI_REASON_EXCHANGE_SIZE_RETURN:         // 返却：サイズ交換キャンセル
            $isMenuReturn = true;                       // 返却のメニューをアクティブに
            $nextUrl = './henpin_shinsei_change_cancel.php';
            $title .= '(サイズ交換キャンセル)';
            break;
            
        default:
            // TOP画面に強制遷移
            redirectTop();
            break;
    }

} else {    // 発注区分がなければ、TOP画面に強制遷移

    // TOP画面に強制遷移
    redirectTop();
}

if ($isLevelAdmin == true) {
    $searchCompCd   = trim($post['searchCompCd']);       // 店舗番号
    $searchCompName = trim($post['searchCompName']);     // 店舗名
} else {
    $searchCompCd   = trim($_SESSION['COMPCD']);       // 店舗番号
    $searchCompName = trim($_SESSION['COMPNAME']);     // 店舗名
}

// スタッフコード
$searchStaffCd = trim($post['searchStaffCd']);

// スタッフ名
$searchPersonName = trim($post['searchPersonName']);


// スタッフコード一覧の取得
if (isset($post['searchFlg']) && trim($post['searchFlg']) == '1') {
    if ($isLevelAdmin == true) {
        $searchCompId = trim($post['searchCompId']);
    } else {
        $searchCompId = $_SESSION['COMPID'];
    }

    if (!$errFlg) {
        $staff = getReturnStaff($dbConnect, (int)$searchCompId, $post['searchStaffCd'], $post['searchPersonName'], $appliReason, $isLevelAdmin, $isLevelItc);
    
        $isSearched = true;
    
        // 該当のスタッフが1件も存在しなかった場合（エラーメッセージの表示）
        if (count($staff) <= 0) {
        
            $errFlg = true;
            $errMsg = '該当する職員がいません。';
        
        }
    }
}

/*
 * スタッフコードとStaffIDを取得する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$compId    => 店舗ID
 *       ：$staffCode => スタッフコード
 *       ：$personName => スタッフ名
 *       ：$appliReason      => 申請理由
 *       ：$isEntity  => 0：エンティティしない / 1：エンティティする
 * 戻り値：サイズ情報（array）
 *
 * create 2007/04/10 H.Osugi
 *
 */
function getReturnStaff($dbConnect, $compId, $staffCode, $personName, $appliReason, $isLevelAdmin, $isLevelItc) {

    // 初期化
    $result = array();
	$corpCode  = '';

    // パラメータをセット
    if ($isLevelAdmin) {
	   	if (!$isLevelItc) {
    	    // 支店CD
    	    if (isset($_SESSION['CORPCD'])) {
    	        $corpCode = $_SESSION['CORPCD'];
    	    } else {
    	        $corpCode = '';
    	    }
		}
    }

    // 店員の一覧を取得する
    $sql =         " SELECT";
    $sql .=             " mco2.StaffSeqID,";
    $sql .=             " mcp2.CompID,";
    $sql .=             " mcp2.CompCd,";
    $sql .=             " mcp2.CompName,";
    $sql .=             " mco2.StaffCode,";
    $sql .=             " mco2.PersonName";
    $sql .=         " FROM";
    $sql .=             " M_Staff mco2";
    $sql .=             " INNER JOIN M_Comp mcp2 ON mco2.CompID=mcp2.CompID";
    $sql .=         " WHERE";
    $sql .=             " mco2.StaffSeqID IN (";
    $sql .=                         " SELECT";
    $sql .=                         " StaffSeqID";
    $sql .=                         " FROM";
    $sql .=                             " (";
    $sql .=                                 " SELECT";
    $sql .=                                     " DISTINCT";
    $sql .=                                     " mco3.StaffSeqID,";
    $sql .=                                     " mcp3.CompCd";
    $sql .=                                 " FROM";
    $sql .=                                     " M_Staff mco3";
    $sql .=                                     " INNER JOIN";
    $sql .=                                     " M_Comp mcp3";
    $sql .=                                     " ON";
    $sql .=                                         " mco3.CompID=mcp3.CompID";
    if ($appliReason == APPLI_REASON_EXCHANGE_SIZE_RETURN) {
        $sql .=                                 " INNER JOIN";
        $sql .=                                     " (SELECT";
        $sql .=                                         " SUBSTRING(A.AppliNo, 2, 12) AS RefNo,";
        $sql .=                                         " A.StaffID,";
        $sql .=                                         " B.AppliLNo";
        $sql .=                                     " FROM T_Order A INNER JOIN T_Order_Details B ON A.OrderID = B.OrderID";
        $sql .=                                     " WHERE B.Status = ".STATUS_NOT_RETURN_ORDER;
        $sql .=                                     " AND A.AppliMode = ".APPLI_MODE_EXCHANGE;
        $sql .=                                     " AND A.AppliReason = ".APPLI_REASON_EXCHANGE_SIZE;
        $sql .=                                     " AND A.Del = ".DELETE_OFF;
        $sql .=                                     " AND B.Del = ".DELETE_OFF;
        $sql .=                                     " ) AS ReturnItem";
        $sql .=                                 " ON ReturnItem.StaffID = mco3.StaffSeqID";
        $sql .=                                 " INNER JOIN";
        $sql .=                                     " (SELECT";
        $sql .=                                         " SUBSTRING(A.AppliNo, 2, 12) AS RefNo,";
        $sql .=                                         " A.StaffID,";
        $sql .=                                         " B.AppliLNo";
        $sql .=                                     " FROM T_Order A INNER JOIN T_Order_Details B ON A.OrderID = B.OrderID";
        $sql .=                                     	 " INNER JOIN T_Staff_Details C ON B.OrderDetID = C.OrderDetID";
        $sql .=                                     " WHERE (B.Status = ".STATUS_SHIP." OR B.Status = ".STATUS_DELIVERY.")";
        $sql .=                                     " AND A.AppliMode = ".APPLI_MODE_EXCHANGE;
        $sql .=                                     " AND A.AppliReason = ".APPLI_REASON_EXCHANGE_SIZE;
        $sql .=                                     " AND C.ReturnDetID IS NULL";
        $sql .=                                     " AND A.Del = ".DELETE_OFF;
        $sql .=                                     " AND B.Del = ".DELETE_OFF;
        $sql .=                                     " ) AS OrderItem";
        $sql .=                                 " ON ReturnItem.RefNo = OrderItem.RefNo";
        $sql .=                                 " AND ReturnItem.AppliLNo = OrderItem.AppliLNo";
        $sql .=                                 " AND ReturnItem.StaffID = OrderItem.StaffID";
        $sql .=                             " WHERE";
    } else {
    
        $sql .=                                 " INNER JOIN";
        $sql .=                                 " T_Staff ts";
        $sql .=                                 " ON";
        $sql .=                                     " mco3.StaffSeqID = ts.StaffID";
        $sql .=                                 " INNER JOIN";
        $sql .=                                 " T_Staff_Details tsd";
        $sql .=                                 " ON";
        $sql .=                                     " mco3.StaffSeqID = tsd.StaffID";
        $sql .=                                 " AND";
        $sql .=                                     " mco3.Del = " . DELETE_OFF;
        $sql .=                                 " INNER JOIN";
        $sql .=                                 " T_Order_Details tod";
        $sql .=                                 " ON";
        $sql .=                                     " tsd.OrderDetID = tod.OrderDetID";
        $sql .=                                 " AND";
        $sql .=                                     " tod.Del = " . DELETE_OFF;
    
        $sql .=                             " WHERE";
        $sql .=                                 " tsd.Status IN ( " . STATUS_SHIP . ", ". STATUS_DELIVERY .")";     // ステータスが出荷済(15),納品済(16)
        $sql .=                             " AND";
        ////$sql .=                                 " ts.AllReturnFlag = ".COMMON_FLAG_OFF;
        ////$sql .=                             " AND";
    }
    $sql .=                                     " mco3.Del = " . DELETE_OFF;
    $sql .=                                 " AND";
    $sql .=                                     " mcp3.Del = " . DELETE_OFF;
    $sql .=                                 " AND";

    if ($_SESSION['ADMINLVL'] == 0) {   // ティファニー
        if ($_SESSION['SHOPFLAG'] == EXCEPTIONALSHOP_EXCEPTIONAL) {
            $sql .=     " mcp3.ShopFlag = 2";         // ShopFlagが2の店舗のみを表示する
        } else {
            $sql .=     " mcp3.ShopFlag = 1";         // ShopFlagが1の店舗のみを表示する
        }
    } else {                            // ＩＴＣ管理者
        $sql .=     " mcp3.ShopFlag <> 0";        // ShopFlagが0以外の店舗を表示する
    }

    if ($compId != '') {
        $sql .=                             " AND";
        $sql .=                                 " mcp3.CompID = " . db_Like_Escape($compId) . "";
    }

	if ($corpCode != '') {
		$sql .= 							" AND";
		$sql .= 								" mcp3.CorpCd = '" . db_Like_Escape($corpCode) . "'";
	}

    if ($staffCode != '') {
        $sql .=                             " AND";
        $sql .=                                 " mco3.StaffCode LIKE '%" . db_Like_Escape($staffCode) . "%'";
    }

    if ($personName != '') {
        $sql .=                             " AND";
        $sql .=                                 " mco3.PersonName LIKE '%" . db_Like_Escape($personName) . "%'";
    }

    $sql .=                             " ) mco4";
    $sql .=                         " )";

    $sql .=                 " ORDER BY";
    $sql .=                     " mcp2.CompCd DESC,";
    $sql .=                     " mco2.StaffSeqID DESC";


    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合
    if (count($result) <= 0) {
        $result = array();
        return $result;
    }

    // 取得した値をHTMLエンティティ
    $resultCount = count($result);
    for ($i=0; $i<$resultCount; $i++) {
        $result[$i]['StaffCode'] = htmlspecialchars($result[$i]['StaffCode'], HTMLENTITY_QUOTE_STYLE, HTMLENTITY_ENCODE);
        $result[$i]['StaffID']   = htmlspecialchars($result[$i]['StaffSeqID'], HTMLENTITY_QUOTE_STYLE, HTMLENTITY_ENCODE);
        $result[$i]['CompID'] = htmlspecialchars($result[$i]['CompID'], HTMLENTITY_QUOTE_STYLE, HTMLENTITY_ENCODE);
        $result[$i]['CompCd']   = htmlspecialchars($result[$i]['CompCd'], HTMLENTITY_QUOTE_STYLE, HTMLENTITY_ENCODE);
        $result[$i]['CompName'] = htmlspecialchars($result[$i]['CompName'], HTMLENTITY_QUOTE_STYLE, HTMLENTITY_ENCODE);
        $result[$i]['PersonName']   = htmlspecialchars($result[$i]['PersonName'], HTMLENTITY_QUOTE_STYLE, HTMLENTITY_ENCODE);

    }
    
    return  $result;
    
}

?>
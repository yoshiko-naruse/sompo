<?php
/*
 * 職員選択画面
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
$isMenuExchange = true; // 交換のメニューをアクティブに

// 変数の初期化 ここから ******************************************************
$staff = array();           // 職員コード一覧

$searchStaffCode = '';      // 職員コード

$selectedReason1 = false;   // 交換理由（サイズ交換）
$selectedReason2 = false;   // 交換理由（汚損・破損交換）
$selectedReason3 = false;   // 交換理由（紛失交換）
$selectedReason4 = false;   // 交換理由（不良品交換）

$isSearched      = false;   // 検索したかどうかを判定するフラグ

$searchCompCd   = '';       // 管理者権限で代行入力する時の店舗コード
$searchCompName = '';       // 管理者権限で代行入力する時の店舗名
$searchCompId   = '';       // 管理者権限で代行入力する時の店舗ID

$errFlg = false;
$errMsg = '';
// 変数の初期化 ここまで ******************************************************

$post = castHtmlEntity($_POST);

// 取引区分をセット
if (isset($_POST['appliReason']) && (int)$_POST['appliReason']) {

    $appliReason = trim($_POST['appliReason']); 

    $title = '';
    if ($isLevelAdmin) {
        $title .= '代行入力　';
    }
    $title .= '職員選択　';

    switch($appliReason) {

        case APPLI_REASON_EXCHANGE_SIZE:                // 交換：サイズ
            $title .= '(サイズ交換)';
            break;

        case APPLI_REASON_EXCHANGE_INFERIORITY:         // 交換：不良品
            $title .= '(不良品交換)';
            break;
 
        case APPLI_REASON_EXCHANGE_LOSS:                // 交換：紛失
            $title .= '(紛失交換)';
            break;
 
        case APPLI_REASON_EXCHANGE_BREAK:               // 交換：汚損・破損
            $title .= '(汚損・破損交換)';
            break;
 
        case APPLI_REASON_EXCHANGE_CHANGEGRADE:         // 交換：役職
            $title .= '(役職変更による交換)';
            break;
 
        case APPLI_REASON_EXCHANGE_MATERNITY:           // 交換：マタニティ
            $title .= '(マタニティとの交換)';
            break;
 
        case APPLI_REASON_EXCHANGE_REPAIR:              // 交換：修理交換
            $title .= '(修理交換)';
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

// 職員コード
$searchStaffCd = trim($post['searchStaffCd']);

// 職員名
$searchPersonName = trim($post['searchPersonName']);


// 職員コード一覧の取得
if (isset($post['searchFlg']) && trim($post['searchFlg']) == '1') {
    if ($isLevelAdmin == true) {
        $searchCompId = trim($post['searchCompId']);
    } else {
        $searchCompId = $_SESSION['COMPID'];
    }

    if (!$errFlg) {
        $staff = getExchangeStaff($dbConnect, (int)$searchCompId, $post['searchStaffCd'], $post['searchPersonName'], $appliReason, $isLevelAdmin, $isLevelItc);
    
        $isSearched = true;
    
        // 該当の職員が1件も存在しなかった場合（エラーメッセージの表示）
        if (count($staff) <= 0) {
        
            $errFlg = true;
            $errMsg = '該当する職員がいません。';
        
        }
    }
}

/*
 * 職員コードとStaffIDを取得する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$compId    => 店舗ID
 *       ：$staffCode => 職員コード
 *       ：$personName => 職員名
 *       ：$appliReason      => 申請理由
 *       ：$isEntity  => 0：エンティティしない / 1：エンティティする
 * 戻り値：サイズ情報（array）
 *
 * create 2007/04/10 H.Osugi
 *
 */
function getExchangeStaff($dbConnect, $compId, $staffCode, $personName, $appliReason, $isLevelAdmin, $isLevelItc) {

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

    if ($appliReason == APPLI_REASON_EXCHANGE_SIZE) {   // サイズ交換の場合はワンサイズ展開のサイズIDを取得

        $sizeAry = array();

        $sql = "";
        $sql .= " SELECT";
        $sql .=     " SizeID";
        $sql .= " FROM";
        $sql .=     " M_Size";
        $sql .= " WHERE";
        $sql .=     " Del = " . DELETE_OFF;
        $sql .= " AND";
        $sql .=     " Size2 IS NULL ";

        $result = db_Read($dbConnect, $sql);

        if (is_array($result) && count($result) != 0) {
            foreach ($result as $key => $val) {
                $sizeAry[] = $val['SizeID'];
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

    // サイズ交換の場合はSizeが１つの商品ははぶく
    if ($appliReason == APPLI_REASON_EXCHANGE_SIZE && count($sizeAry) != 0) {
        $sql .=                                 " INNER JOIN";
        $sql .=                                 " M_Item mi";
        $sql .=                                 " ON";
        $sql .=                                     " tod.ItemID = mi.ItemID";
        $sql .=                                 " AND";
        $sql .=                                     " mi.Del = " . DELETE_OFF;
    }
    $sql .=                                 " WHERE";
    $sql .=                                     " mco3.Del = " . DELETE_OFF;
    $sql .=                                 " AND mcp3.Del = " . DELETE_OFF;
    $sql .=                             " AND";
    $sql .=                                 " tsd.Status IN ( " . STATUS_SHIP . ", ". STATUS_DELIVERY .")";     // ステータスが出荷済(15),納品済(16)
    ////$sql .=                             " AND";
    ////$sql .=                                 " ts.AllReturnFlag = ".COMMON_FLAG_OFF;

    // サイズ交換の場合はSizeが１つの商品ははぶく
    if ($appliReason == APPLI_REASON_EXCHANGE_SIZE && count($sizeAry) != 0) {
        $sql .= " AND";
        $sql .=     " mi.SizeID NOT IN (".implode(', ', $sizeAry).")";
    }

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
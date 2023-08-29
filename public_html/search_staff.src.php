<?php
/*
 * 店舗検索画面
 * search_comp.src.php
 *
 * create 2008/01/12
 *
 *
 */

// 各種モジュールの取得
include_once('../include/define.php');			// 定数定義
require_once('../include/dbConnect.php');		// DB接続モジュール
require_once('../include/msSqlControl.php');		// DB操作モジュール
require_once('../include/checkLogin.php');		// ログイン判定モジュール
require_once('../include/castHtmlEntity.php');	// HTMLエンティティモジュール
require_once('../include/redirectPost.php');		// リダイレクトポストモジュール
require_once('../include/setPaging.php');		// ページング情報セッティングモジュール

// 変数の初期化 ここから ******************************************************
$appliReason = '';                     // 申請理由
$staffs      = array();                // 検索結果
$searchCompCode = '';				   // 店舗コード
$searchCompName = '';                  // 店舗名
$searchHonbuCode = '';				   // 本部コード
$searchHonbuName = '';                 // 本部名
$searchShibuCode = '';				   // 支部コード
$searchShibuName = '';                 // 支部名
$searchStaffCode = '';                 // 社員番号
$searchPersonName = '';                // 氏名
$compData    = array();                // 会社情報

$isSearched = false;				// 検索を行ったかどうか
$isEmpty    = false;                // 検索結果が0かどうか
// 変数の初期化 ここまで ******************************************************

// GET・POST値をHTMLエンティティ
$post = castHtmlEntity($_POST); 
$get = castHtmlEntity($_GET); 

//var_dump("isLevelItc:" . $isLevelItc);

// 表示する値の成型 ここから +++++++++++++++++++++++++++++++++++++++++++++++++++

// 社員NO
if (isset($post['searchStaffCode'])) {
    $searchStaffCode = trim($post['searchStaffCode']);
}

// 社員名
if (isset($post['searchPersonName'])) {
    $searchPersonName = trim($post['searchPersonName']);
}

if ($isLevelAdmin) {
    //// 店舗コード
    //if (isset($post['searchCompCode'])) {
    //    $searchCompCode = trim($post['searchCompCode']);
    //}

    // 本部コード
    if (isset($post['searchHonbuId'])) {
        $searchHonbuId = trim($post['searchHonbuId']);
    }

    // 支部コード
    if (isset($post['searchShitenId'])) {
        $searchShitenId = trim($post['searchShitenId']);
    }

    // 基地局コード
    if (isset($post['searchEigyousyoId'])) {
        $searchEigyousyoId = trim($post['searchEigyousyoId']);
    }

	$compData = getCompName($dbConnect, $post, $isLevelAdmin, $isLevelItc);

	$searchHonbuName  = $compData[0]['HonbuName'];
	$searchShitenName = $compData[0]['ShibuName'];

	if (isset($compData[0]['CompCd']) && $compData[0]['CompCd'] != '') {
		$searchCompCode   = $compData[0]['CompCd'];

	} else {
		$searchCompCode   = '';

	}

	if (isset($compData[0]['CompName']) && $compData[0]['CompName'] != '') {
		$searchCompName   = $compData[0]['CompName'];

	} else {
		$searchCompName   = '';

	}

} else {    // 一般ユーザーの場合はログイン情報から取得

    // 店舗コード
    if (isset($_SESSION['COMPCD'])) {
        $searchCompCode    = trim($_SESSION['COMPCD']);
    }

    // 店舗名
    if (isset($_SESSION['COMPNAME'])) {
        $searchCompName    = trim($_SESSION['COMPNAME']);
    }

}

//var_dump($compData);

// 表示する値の成型 ここまで +++++++++++++++++++++++++++++++++++++++++++++++++++

// 発注区分
if (isset($get['appliReason'])) {
    $appliReason = $get['appliReason'];

} else {    // 発注区分がなければ、TOP画面に強制遷移

    // TOP画面に強制遷移
    $returnUrl = HOME_URL . 'top.php';
    $hiddens = array();
    redirectPost($returnUrl, $hiddens);
}

$nowPage = 1;
if ($post['nowPage'] != '') {
	$nowPage = trim($post['nowPage']);
}

$isSearched = trim($post['isSearched']);

// 検索時は1ページ目に戻す
if ($post['searchFlg'] == 1) {
	$nowPage = 1;
	$isSearched = true;
}

if ($isSearched == true) {

	// 表示する社員一覧を取得
	$staffs = getStaffData($dbConnect, $post, $nowPage, $allCount, $appliReason, $isLevelAdmin, $isLevelItc);

	// ページングのセッティング
	$paging = setPaging($nowPage, PAGE_PER_DISPLAY_SEARCH_STAFF, $allCount);

	// 社員が０件の場合
	if ($allCount <= 0) {
        $isEmpty    = true;
		$isSearched = false;
	}

}

/*
 * 社員一覧情報を取得する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$post           => POST値
 *       ：$nowPage        => 現在のページ数
 *       ：$allCount       => 全件数
 *       ：$appliReason    => 発注区分
 * 戻り値：$result         => 社員一覧情報
 *
 * create 2007/04/12 H.Osugi
 *
 */
function getStaffData($dbConnect, $post, $nowPage, &$allCount, $appliReason, $isLevelAdmin, $isLevelItc) {

    // 取引理由配列
    // 交換
    $koukanReasonAry = array(APPLI_REASON_EXCHANGE_SIZE, 
                            APPLI_REASON_EXCHANGE_INFERIORITY, 
                            APPLI_REASON_EXCHANGE_LOSS, 
                            APPLI_REASON_EXCHANGE_BREAK, 
                            APPLI_REASON_EXCHANGE_REPAIR, 
                            APPLI_REASON_EXCHANGE_CHANGEGRADE, 
                            APPLI_REASON_EXCHANGE_MATERNITY
                        );

    // 返却
    $henpinReasonAry = array(APPLI_REASON_RETURN_RETIRE, 
                            APPLI_REASON_RETURN_OTHER 
                        );

    // 初期化
    $compName  = '';
    $offset    = '';
	$corpCode  = '';

    // 取得したい件数
    $limit = PAGE_PER_DISPLAY_SEARCH_STAFF;     // 1ページあたりの表示件数;

    // 取得したいデータの開始位置
    $offset = ($nowPage - 1) * PAGE_PER_DISPLAY_SEARCH_STAFF;

    // パラメータをセット
    if ($isLevelAdmin) {

        // 本部コード
        if (isset($post['searchHonbuId'])) {
            $honbuCode = trim($post['searchHonbuId']);
        } else {
            $honbuCode = '';
        }

        // 支部コード
        if (isset($post['searchShitenId'])) {
            $shibuCode = trim($post['searchShitenId']);
        } else {
            $shibuCode = '';
        }

        // 基地局コード
        if (isset($post['searchEigyousyoId'])) {
            $compID = trim($post['searchEigyousyoId']);
        } else {
            $compID = '';
        }

        // 基地局コード
        if (isset($post['searchCompCode'])) {
            $compCode = trim($post['searchCompCode']);
        } else {
            $compCode = '';
        }

        //// 店舗CD
        //if (isset($post['searchCompCode'])) {
        //    $compCode = $post['searchCompCode'];
        //} else {
        //    $compCode = '';
        //}
        //// 店舗名
        //if (isset($post['searchCompName'])) {
        //    $compName = $post['searchCompName'];
        //} else {
        //    $compName = '';
        //}

        // 本部権限、支部権限なら、スコープを絞っておく
//    	if (!$isLevelItc) {
//    	    // 支店CD
//    	    if (isset($_SESSION['CORPCD'])) {
//    	        $corpCode = $_SESSION['CORPCD'];
//    	    } else {
//    	        $corpCode = '';
//    	    }
//		}


    } else {
        // 店舗CD
        if (isset($_SESSION['COMPCD'])) {
            $compCode = $_SESSION['COMPCD'];
        } else {
            $compCode = '';
        }

        // 店舗名
        if (isset($_SESSION['COMPNAME'])) {
            $compName = $_SESSION['COMPNAME'];
        } else {
            $compName = '';
        }
    }

    // 社員CD
    if (isset($post['searchStaffCode'])) {
        $staffCode = $post['searchStaffCode'];
    } else {
        $staffCode = '';
    }

    // 社員名
    if (isset($post['searchPersonName'])) {
        $personName = $post['searchPersonName'];
    } else {
        $personName = '';
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

    // 社員の件数を取得する
    $sql  = "";
    $sql .= " SELECT";
    $sql .=     " COUNT(DISTINCT S.StaffSeqID) as count_staff";
    $sql .= " FROM";
    $sql .=     " M_Staff S";
    $sql .=     " INNER JOIN";
    $sql .=     " M_Comp C";
    $sql .=     " ON";
    $sql .=         " S.CompID = C.CompID";
    if (in_array($appliReason, $koukanReasonAry) || in_array($appliReason, $henpinReasonAry)) {
        $sql .= " INNER JOIN";
        $sql .=     " T_Staff ts";
        $sql .= " ON";
        $sql .=     " S.StaffSeqID = ts.StaffID";
        $sql .= " INNER JOIN";
        $sql .=     " T_Staff_Details tsd";
        $sql .= " ON";
        $sql .=     " S.StaffSeqID = tsd.StaffID";
        $sql .= " AND";
        $sql .=     " S.Del = " . DELETE_OFF;
        $sql .= " INNER JOIN";
        $sql .=     " T_Order_Details tod";
        $sql .= " ON";
        $sql .=     " tsd.OrderDetID = tod.OrderDetID";
        $sql .= " AND";
        $sql .=     " tod.Del = " . DELETE_OFF;

    }

    // サイズ交換の場合はSizeが１つの商品ははぶく
    if ($appliReason == APPLI_REASON_EXCHANGE_SIZE && count($sizeAry) != 0) {
        $sql .= " INNER JOIN";
        $sql .=     " M_Item mi";
        $sql .= " ON";
        $sql .=     " tod.ItemID = mi.ItemID";
        $sql .= " AND";
        $sql .=     " mi.Del = " . DELETE_OFF;
    }
    $sql .= " WHERE";
    $sql .=     " S.Del = " . DELETE_OFF . " AND C.Del = " . DELETE_OFF;
    $sql .= " AND";

    if ($_SESSION['ADMINLVL'] == 0) {   // ティファニー
        if ($_SESSION['SHOPFLAG'] == EXCEPTIONALSHOP_EXCEPTIONAL) {
            $sql .=     " C.ShopFlag = 2";         // ShopFlagが2の店舗のみを表示する
        } else {
            $sql .=     " C.ShopFlag = 1";         // ShopFlagが1の店舗のみを表示する
        }
    } else {                            // ＩＴＣ管理者
        $sql .=     " C.ShopFlag <> 0";        // ShopFlagが0以外の店舗を表示する
    }

    if (in_array($appliReason, $koukanReasonAry) || in_array($appliReason, $henpinReasonAry)) {
            $sql .= " AND";
            $sql .=     " tsd.Status IN ( " . STATUS_SHIP . ", ". STATUS_DELIVERY .")";     // ステータスが出荷済(15),納品済(16)
            ////$sql .= " AND";
            ////$sql .=     " ts.AllReturnFlag = ".COMMON_FLAG_OFF;
    }

    // サイズ交換の場合はSizeが１つの商品ははぶく
    if ($appliReason == APPLI_REASON_EXCHANGE_SIZE && count($sizeAry) != 0) {
        $sql .= " AND";
        $sql .=     " mi.SizeID NOT IN (".implode(', ', $sizeAry).")";
    }

    if ($honbuCode != '') {
        $sql .= " AND";
        $sql .=     " C.HonbuCd = '" . db_Escape($honbuCode) . "'";
    }

    if ($shibuCode != '') {
        $sql .= " AND";
        $sql .=     " C.ShibuCd = '" . db_Escape($shibuCode) . "'";
    }

    if ($compID != '') {
        $sql .= " AND";
        $sql .=     " C.CompID = '" . db_Escape($CompID) . "'";
    }

    if ($compCode != '') {
        $sql .= " AND";
        $sql .=     " C.CompCd = '" . db_Escape($compCode) . "'";
    }

    if ($compName != '') {
        $sql .= " AND";
        $sql .=     " C.CompName LIKE '%" . db_Like_Escape($compName) . "%'";
    }

	if ($corpCode != '') {
		$sql .= " AND";
		$sql .= 	" C.CorpCd = '" . db_Like_Escape($corpCode) . "'";
	}

    if ($staffCode != '') {
        $sql .= " AND";
        $sql .=     " S.StaffCode LIKE '%" . db_Like_Escape($staffCode) . "%'";
    }

    if ($personName != '') {
        $sql .= " AND";
        $sql .=     " S.PersonName LIKE '%" . db_Like_Escape($personName) . "%'";
    }

    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合
    $allCount = 0;
    if (!isset($result[0]['count_staff']) || $result[0]['count_staff'] <= 0) {
        $result = array();
        return $result;
    }

    // 全件数
    $allCount = $result[0]['count_staff'];

    $top = $offset + $limit;
    if ($top > $allCount) {
        $limit = $limit - ($top - $allCount);
        $top   = $allCount;
    }

    // 店員の一覧を取得する
    $sql  = "";
    $sql .= " SELECT";
    $sql .=     " StaffSeqID,";
    $sql .=     " CompID,";
    $sql .=     " CompCd,";
    $sql .=     " CompName,";
    $sql .=     " StaffCode,";
    $sql .=     " PersonName,";
    $sql .=     " FukusyuID,";
    $sql .=     " GenderKbn";
    $sql .= " FROM";
    $sql .=     " (";
    $sql .=         " SELECT";
    $sql .=             " TOP " . $limit;
    $sql .=             " mco2.StaffSeqID,";
    $sql .=             " mcp2.CompID,";
    $sql .=             " mcp2.CompCd,";
    $sql .=             " mcp2.CompName,";
    $sql .=             " mcp2.CompKind,";
    $sql .=             " mco2.StaffCode,";
    $sql .=             " mco2.PersonName,";
    $sql .=             " mco2.FukusyuID,";
    $sql .=             " mco2.GenderKbn";
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
    $sql .=                                     " TOP " . ($top);
    $sql .=                                     " mco3.StaffSeqID,";
    $sql .=                                     " mcp3.CompCd";
    $sql .=                                 " FROM";
    $sql .=                                     " M_Staff mco3";
    $sql .=                                     " INNER JOIN";
    $sql .=                                     " M_Comp mcp3";
    $sql .=                                     " ON";
    $sql .=                                         " mco3.CompID=mcp3.CompID";
    if (in_array($appliReason, $koukanReasonAry) || in_array($appliReason, $henpinReasonAry)) {
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

    }
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

    if (in_array($appliReason, $koukanReasonAry) || in_array($appliReason, $henpinReasonAry)) {
        $sql .=                             " AND";
        $sql .=                                 " tsd.Status IN ( " . STATUS_SHIP . ", ". STATUS_DELIVERY .")";     // ステータスが出荷済(15),納品済(16)
        ////$sql .=                             " AND";
        ////$sql .=                                 " ts.AllReturnFlag = ".COMMON_FLAG_OFF;
    }

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

    if ($honbuCode != '') {
        $sql .=                             " AND";
        $sql .=                                 " mcp3.HonbuCd = '" . db_Escape($honbuCode) . "'";
    }

    if ($shibuCode != '') {
        $sql .=                             " AND";
        $sql .=                                 " mcp3.ShibuCd = '" . db_Escape($shibuCode) . "'";
    }

    if ($compID != '') {
        $sql .=                             " AND";
        $sql .=                                 " mcp3.CompID = '" . db_Escape($compID) . "'";
    }

    if ($compCode != '') {
        $sql .=                             " AND";
        $sql .=                                 " mcp3.CompCd LIKE '%" . db_Like_Escape($compCode) . "%'";
    }

    if ($compName != '') {
        $sql .=                             " AND";
        $sql .=                                 " mcp3.CompName LIKE '%" . db_Like_Escape($compName) . "%'";
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

    $sql .=                                 " ORDER BY";
    $sql .=                                     " mcp3.CompCd ASC,";
    $sql .=                                 " mco3.StaffSeqID ASC";
    $sql .=                             " ) mco4";
    $sql .=                         " )";

    $sql .=                 " ORDER BY";
    $sql .=                     " mcp2.CompCd DESC,";
    $sql .=                     " mco2.StaffSeqID DESC";

    $sql .=     " ) mco";

    $sql .=     " ORDER BY";
    $sql .=         " mco.CompCd ASC,";
    $sql .=         " mco.StaffSeqID ASC";

//var_dump($sql);die;

    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合
    if (count($result) <= 0) {
        $result = array();
        return $result;
    }

    // 取得した値をHTMLエンティティ
    $resultCount = count($result);
    for ($i=0; $i<$resultCount; $i++) {

        $result[$i]['StaffID']   = castHtmlEntity($result[$i]['StaffSeqID']);
        $result[$i]['StaffCode'] = castHtmlEntity($result[$i]['StaffCode']);
        $result[$i]['StaffName'] = castHtmlEntity($result[$i]['PersonName']);
        $result[$i]['FukusyuID'] = castHtmlEntity($result[$i]['FukusyuID']);
        $result[$i]['GenderKbn'] = castHtmlEntity($result[$i]['GenderKbn']);

    }

    return  $result;

}

function getCompName($dbConnect, $post, $isLevelAdmin, $isLevelItc) {

//var_dump($post);

//var_dump($isLevelAdmin);
//var_dump($post);
    // パラメータをセット
    if ($isLevelAdmin) {

        // 本部コード
        if (isset($post['searchHonbuId'])) {
            $honbuCode = trim($post['searchHonbuId']);
        } else {
            $honbuCode = '';
        }

        // 支部コード
        if (isset($post['searchShitenId'])) {
            $shibuCode = trim($post['searchShitenId']);
        } else {
            $shibuCode = '';
        }

        // 基地局コード
        if (isset($post['searchEigyousyoId'])) {
            $compID = trim($post['searchEigyousyoId']);
        } else {
            $compID = '';
        }

        // 本部権限、支部権限なら、スコープを絞っておく
//    	if (!$isLevelItc) {
//    	    // 支店CD
//    	    if (isset($_SESSION['CORPCD'])) {
//    	        $corpCode = $_SESSION['CORPCD'];
//    	    } else {
//    	        $corpCode = '';
//    	    }
//		}


    } else {
        // 店舗CD
        if (isset($_SESSION['COMPCD'])) {
            $compCode = $_SESSION['COMPCD'];
        } else {
            $compCode = '';
        }

        // 店舗名
        if (isset($_SESSION['COMPNAME'])) {
            $compName = $_SESSION['COMPNAME'];
        } else {
            $compName = '';
        }
    }

	// 初期化
	$result = array();


//var_dump("shibuCode:" . $shibuCode);
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" HonbuCd";
	$sql .= 	",HonbuName";
	$sql .= 	",ShibuCd";
	$sql .= 	",ShibuName";

    if ($compID != '') {
		$sql .= 	",CompCd";
		$sql .= 	",CompName";
	}
	$sql .= " FROM";
	$sql .= 	" M_Comp";
	$sql .= " WHERE";
	$sql .= 	" Del = " . DELETE_OFF;

    if ($honbuCode != '') {
        $sql .= " AND";
        $sql .=     " HonbuCd = '" . db_Escape($honbuCode) . "'";
    }

    if ($shibuCode != '') {
        $sql .= " AND";
        $sql .=     " ShibuCd = '" . db_Escape($shibuCode) . "'";
    }

    if ($compID != '') {
        $sql .= " AND";
        $sql .=     " CompID = '" . db_Escape($compID) . "'";
    }
//var_dump($sql);

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	return $result;
}

?>
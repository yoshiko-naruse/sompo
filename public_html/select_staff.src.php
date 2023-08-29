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
include_once('../include/define.php');			// 定数定義
require_once('../include/dbConnect.php');		// DB接続モジュール
require_once('../include/msSqlControl.php');	// DB操作モジュール
require_once('../include/checkLogin.php');		// ログイン判定モジュール
require_once('../include/castHtmlEntity.php');	// HTMLエンティティモジュール
require_once('../include/redirectPost.php');	// リダイレクトポストモジュール
require_once('../include/setPaging.php');		// ページング情報セッティングモジュール
require_once('../include/commonFunc.php');      // 共通関数モジュール

// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST);

$title = '';
$title .= '職員選択　';

$isSizeChange = false;

// 取引区分をセット
if (isset($post['appliReason'])) {

    $appliReason = trim($post['appliReason']); 

    $isExchangeFirst = false;		// 初回サイズ交換フラグON
    $exchangeGuideMessage = '';		// 初回サイズ交換時に表示するメッセージ文字列

    $title = '';
    if ($isLevelAdmin) {
        $title .= '代行入力　';
    }
    $title .= '職員選択　';

    switch($appliReason) {

        case APPLI_REASON_ORDER_BASE:                   // 発注（そんぽの家系／ラヴィーレ系）
            $isMenuOrder = true;                        // 発注のメニューをアクティブに
            $nextUrl = './hachu/hachu_shinsei.php';
            if ($isLevelNormal == true) {
                switch($_SESSION['COMPKIND']) {
                    case '1':		// そんぽの家系
                        $title .= '(発注申請・そんぽの家系)';
                        break;
                    case '2':		// ラヴィーレ系
                        $title .= '(発注申請・ラヴィーレ系)';
                        break;
                    default:
                        $title .= '(発注申請・そんぽの家系／ラヴィーレ系)';
                        break;
                }
            } else {
                $title .= '(発注申請・そんぽの家系／ラヴィーレ系)';
            }
            break;

        case APPLI_REASON_ORDER_GRADEUP:                // 発注（グレードアップタイ）
            $isMenuOrder = true;                        // 発注のメニューをアクティブに
            $nextUrl = './hachu/hachu_shinsei.php';
            $title .= '(発注申請・グレードアップタイ)';
            break;

        case APPLI_REASON_ORDER_FRESHMAN:               // 発注（新入社員）
            $isMenuOrder = true;                        // 発注のメニューをアクティブに
            $nextUrl = './hachu/hachu_shinsei.php';
            $title .= '(発注申請・新入社員)';
            break;

        case APPLI_REASON_ORDER_PERSONAL:               // 発注（個別発注申請）
            $isMenuOrder = true;                        // 発注のメニューをアクティブに
            $nextUrl = './hachu/hachu_shinsei.php';
            $title .= '(発注申請・個別発注申請)';
            break;

        case APPLI_REASON_EXCHANGE_FIRST:               // 交換：初回サイズ
            $isMenuExchange = true;                     // 交換のメニューをアクティブに
            $nextUrl = './koukan/koukan_shinsei.php';
            $title .= '(初回サイズ交換)';
            $isExchangeFirst = true;                    // 初回サイズ交換フラグON
// 一時的に日数制限を解除 by T.Uno at 2023/04/27
//            $exchangeGuideMessage = '出荷から' . EXCHANGE_TERM_DAY . '日以内の商品を保持している職員のみ表示されます。';
            $exchangeGuideMessage = '';
            $isSizeChange = true;                       // サイズ交換フラグON
            break;

        case APPLI_REASON_EXCHANGE_SIZE:                // 交換：サイズ
            $isMenuExchange = true;                     // 交換のメニューをアクティブに
            $nextUrl = './koukan/koukan_shinsei.php';
            $title .= '(サイズ交換)';
            $isSizeChange = true;                       // サイズ交換フラグON
            break;

//        case APPLI_REASON_EXCHANGE_INFERIORITY:         // 交換：不良品
//            $isMenuExchange = true;                     // 交換のメニューをアクティブに
//            $nextUrl = './koukan/koukan_shinsei.php';
//            $title .= '(不良品交換)';
//            break;

        case APPLI_REASON_EXCHANGE_LOSS:                // 交換：紛失
            $isMenuExchange = true;                     // 交換のメニューをアクティブに
            $nextUrl = './koukan/koukan_shinsei.php';
            $title .= '(紛失交換)';
            break;

        case APPLI_REASON_EXCHANGE_BREAK:               // 交換：汚損・破損
            $isMenuExchange = true;                     // 交換のメニューをアクティブに
            $nextUrl = './koukan/koukan_shinsei.php';
            $title .= '(汚損・破損交換)';
            break;

        case APPLI_REASON_RETURN_RETIRE:                // 返却：退職・異動
            $isMenuReturn = true;                       // 返却のメニューをアクティブに
            $nextUrl = './henpin/henpin_shinsei.php';
            $title .= '(退職・異動返却)';
            break;
 
        case APPLI_REASON_RETURN_OTHER:                 // 返却：その他
            $isMenuReturn = true;                       // 返却のメニューをアクティブに
            $nextUrl = './henpin/henpin_shinsei.php';
            $title .= '(その他返却)';
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

// ------- ここから ---------------------
$honbuID = '';
$honbuName = '';
$shitenID = '';
$shitenName = '';
$eigyousyoID = '';
$eigyousyoName = '';
$isLevelShibu = false;

// 本部検索時は支部検索値クリア
if (isset($post['searchSelectflg']) && $post['searchSelectflg'] == '1') {
	$post['isSearched']='';
	$post['searchShitenId']='';
	$post['searchEigyousyoId']='';
	$post['searchStaffCode']='';
	$post['searchPersonName']='';
}

// 支部検索時は支部検索値クリア
if (isset($post['searchSelectflg']) && $post['searchSelectflg'] == '2') {
	$post['isSearched']='';
	$post['searchEigyousyoId']='';
	$post['searchStaffCode']='';
	$post['searchPersonName']='';
}

// 基地検索時は基地検索値クリア
if (isset($post['searchSelectflg']) && $post['searchSelectflg'] == '3') {
	$post['isSearched']='';
	$post['searchStaffCode']='';
	$post['searchPersonName']='';
}

// 職員検索時は検索値クリア
if (isset($post['searchFlg'])) {
	$searchFlg=$post['searchFlg'];
}

//// 支部検索時は支部検索値クリア
//if (isset($post['searchSelectflg']) && $post['searchSelectflg'] == '2') {
//	$post['searchShitenId']='';
//}

//var_dump('searchHonbuId:' . $post['searchHonbuId']);
//var_dump('searchShitenId:' . $post['searchShitenId']);
//var_dump('searchEigyousyoId:' . $post['searchEigyousyoId']);

if ($isLevelAdmin == true) {

	if ($isLevelItc == true) { 
		$honbuID = trim($post['searchHonbuId']);

		// 支店リスト取得
		$honbu = castListbox_Honbu(getHonbuName($dbConnect), $honbuID);

	} else { 
		$honbuID   = trim($_SESSION['HONBUCD']);
		$honbuName = trim($_SESSION['HONBUNAME']);
	}

} else {
	$honbuID   = trim($_SESSION['HONBUCD']);
	$honbuName = trim($_SESSION['HONBUNAME']);

}

if ($isLevelAdmin == true) {

	if ($isLevelItc == true || $isLevelHonbu == true) { 

		$shitenID = trim($post['searchShitenId']);

		// 支店リスト取得
		$shiten = castListbox_Shibu(getShitenName($dbConnect, $honbuID), $shitenID);

	} else {
		$shitenID     = trim($_SESSION['SHIBUCD']);
		$shitenName   = trim($_SESSION['SHIBUNAME']);
		$isLevelShibu = true;

	}
} else {
	$shitenID     = trim($_SESSION['SHIBUCD']);
	$shitenName   = trim($_SESSION['SHIBUNAME']);
	$isLevelShibu = true;

}

$eigyousyoID = trim($post['searchEigyousyoId']);

// グレードアップタイを選択されている場合はラヴィーレ系のみを選択するようコード設定
if ($appliReason == APPLI_REASON_ORDER_GRADEUP) {
	$compKind = '2';	// ラヴィーレ系
} else {
	$compKind = '';
}

// 営業所リスト取得
$eigyousyo = castListbox(getEigyousyoName($dbConnect, $honbuID, $shitenID, $compKind), $eigyousyoID);

// 一般権限の場合は店舗CD,店舗名セット
if ($isLevelNormal) {

    $compCd = '';
    if (isset($_SESSION['COMPCD'])) {
        $compCd = $_SESSION['COMPCD'];
    }

    $eigyousyoID = '';
    if (isset($_SESSION['COMPID'])) {
        $eigyousyoID = $_SESSION['COMPID'];
    }

    $compName = '';
    if (isset($_SESSION['COMPNAME'])) {
        $compName = $_SESSION['COMPNAME'];
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

$nowPage = 1;

$isSearched = trim($post['isSearched']);

// 検索時は1ページ目に戻す
if ($post['searchFlg'] == 1) {
	$nowPage = 1;
	$isSearched = true;
}

if ($post['nowPage'] != '') {
	$nowPage = trim($post['nowPage']);
}

if ($isSearched == true) {

	// 表示する社員一覧を取得
	$staffs = getStaffData($dbConnect, $post, $nowPage, $allCount, $appliReason, $isLevelAdmin, $isLevelItc, $isLevelHonbu);

	// ページングのセッティング
	$paging = setPaging($nowPage, PAGE_PER_DISPLAY_SEARCH_STAFF, $allCount);

	// 社員が０件の場合
	if ($allCount <= 0) {
        $isEmpty    = true;
		$isSearched = false;
	}

}

function getHonbuName($dbConnect) {

	// 初期化
	$result = array();

	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" HonbuCd,";
	$sql .= 	" HonbuName";
	$sql .= " FROM";
	$sql .= 	" M_Comp";
	$sql .= " WHERE";
	$sql .= 	" Del = " . DELETE_OFF;
	$sql .= " AND";
	$sql .= 	" ShopFlag = 1";	// 対象データ

	$sql .= " GROUP BY";
	$sql .= 	" HonbuCd,";
	$sql .= 	" HonbuName";
	$sql .= " ORDER BY";
	$sql .= 	" HonbuCd,";
	$sql .= 	" HonbuName";

//	$sql .= " AND";
//	$sql .= 	" ShopKbn = 0";		// 支店

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	return $result;
}

function getShitenName($dbConnect, $honbuID='') {

	// 初期化
	$result = array();

	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" ShibuCd,";
	$sql .= 	" ShibuName";
	$sql .= " FROM";
	$sql .= 	" M_Comp";
	$sql .= " WHERE";
	$sql .= 	" Del = " . DELETE_OFF;
	if (isset($honbuID) &&  $honbuID != '') {
		$sql .= " AND";
		$sql .= 	" HonbuCd = " . $honbuID;
	}
	$sql .= " AND";
	$sql .= 	" ShopFlag = 1";	// 対象データ
	$sql .= " GROUP BY";
	$sql .= 	" ShibuCd,";
	$sql .= 	" ShibuName";
	$sql .= " ORDER BY";
	$sql .= 	" ShibuCd,";
	$sql .= 	" ShibuName";
	//$sql .= " AND";
	//$sql .= 	" ShopKbn = 0";		// 支店
//var_dump($sql);
	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	return $result;
}

function getEigyousyoName($dbConnect, $honbuID='', $shitenID='', $compKind='') {

	// 初期化
	$result = array();

	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" CompID,";
	$sql .= 	" CompName";
	$sql .= " FROM";
	$sql .= 	" M_Comp";
	$sql .= " WHERE";
	$sql .= 	" Del = " . DELETE_OFF;
	if (isset($honbuID) &&  $honbuID != '') {
		$sql .= " AND";
		$sql .= 	" HonbuCd = " . $honbuID;
	}
	if (isset($shitenID) &&  $shitenID != '') {
		$sql .= " AND";
		$sql .= 	" ShibuCd = " . $shitenID;
	}
	if (isset($compKind) &&  $compKind != '') {
		$sql .= " AND";
		$sql .= 	" CompKind = " . $compKind;
	}
	$sql .= " AND";
	$sql .= 	" ShopFlag = 1";	// 対象データ
//	$sql .= " AND";
//	$sql .= 	" ShopKbn = 1";		// 営業所
//	$sql .= " AND";
//	$sql .= 	" AgencyID = '" . db_Escape($compID) . "'";		// 支店

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	return $result;
}

function castListbox($compData, $selectedValue = '') {

	// 初期化
	$selectDatas = array();

	// $sizeDatasが配列でなければ終了
	if (!is_array($compData)) {
		return  $selectDatas;
	}

	// $compDataにデータが1件もなければ終了
	if (count($compData) <= 0) {
		return  $selectDatas;
	}

	// リストボックスを生成するための値に成型
	$listCount = count($compData);
	for ($i=0; $i<$listCount; $i++) {

		$selectDatas[$i]['selected'] = false;
		if (trim($compData[$i]['CompID']) == $selectedValue) {
			$selectDatas[$i]['selected'] = true;
		}

		$selectDatas[$i]['value'] = trim($compData[$i]['CompID']);

		// HTMLエンティティ処理
		$selectDatas[$i]['display'] = htmlspecialchars($compData[$i]['CompName'], HTMLENTITY_QUOTE_STYLE, HTMLENTITY_ENCODE);

	}

	return $selectDatas;

}

function castListbox_Honbu($honbuData, $selectedValue = '') {

	// 初期化
	$selectDatas = array();

	// $sizeDatasが配列でなければ終了
	if (!is_array($honbuData)) {
		return  $selectDatas;
	}

	// $compDataにデータが1件もなければ終了
	if (count($honbuData) <= 0) {
		return  $selectDatas;
	}

	// リストボックスを生成するための値に成型

	$listCount = count($honbuData);
	for ($i=0; $i<$listCount; $i++) {

		$selectDatas[$i]['selected'] = false;
		if (trim($honbuData[$i]['HonbuCd']) == $selectedValue) {
			$selectDatas[$i]['selected'] = true;

		}
//var_dump($honbuData);

		$selectDatas[$i]['value'] = trim($honbuData[$i]['HonbuCd']);

		// HTMLエンティティ処理
		$selectDatas[$i]['display'] = htmlspecialchars($honbuData[$i]['HonbuName'], HTMLENTITY_QUOTE_STYLE, HTMLENTITY_ENCODE);

	}

	return $selectDatas;

}

function castListbox_Shibu($shibuData, $selectedValue = '') {

	// 初期化
	$selectDatas = array();

	// $sizeDatasが配列でなければ終了
	if (!is_array($shibuData)) {
		return  $selectDatas;
	}

	// $compDataにshibuDataが1件もなければ終了
	if (count($shibuData) <= 0) {
		return  $selectDatas;
	}

	// リストボックスを生成するための値に成型

	$listCount = count($shibuData);
	for ($i=0; $i<$listCount; $i++) {

		$selectDatas[$i]['selected'] = false;
		if (trim($shibuData[$i]['ShibuCd']) == $selectedValue) {
			$selectDatas[$i]['selected'] = true;

		}
//var_dump($shibuData);

		$selectDatas[$i]['value'] = trim($shibuData[$i]['ShibuCd']);

		// HTMLエンティティ処理
		$selectDatas[$i]['display'] = htmlspecialchars($shibuData[$i]['ShibuName'], HTMLENTITY_QUOTE_STYLE, HTMLENTITY_ENCODE);

	}

	return $selectDatas;

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
function getStaffData($dbConnect, $post, $nowPage, &$allCount, $appliReason, $isLevelAdmin, $isLevelItc, $isLevelHonbu) {

    // 取引理由配列
    // 交換
    $koukanReasonAry = array(APPLI_REASON_EXCHANGE_FIRST, 
                            APPLI_REASON_EXCHANGE_SIZE, 
                            APPLI_REASON_EXCHANGE_INFERIORITY, 
                            APPLI_REASON_EXCHANGE_LOSS, 
                            APPLI_REASON_EXCHANGE_BREAK
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

        if (!$isLevelItc) {

            if ($isLevelHonbu) {
                // 本部権限
                if (isset($_SESSION['HONBUCD'])) {
                    $honbuCode = $_SESSION['HONBUCD'];
                } else {
                    $honbuCode = '';
                }

            } else {
                // 支部権限
                if (isset($_SESSION['SHIBUCD']) && isset($_SESSION['HONBUCD'])) {
                    $honbuCode = $_SESSION['HONBUCD'];
                    $shibuCode = $_SESSION['SHIBUCD'];
                } else {
                    $honbuCode = '';
                    $shibuCode = '';
                }
            }
        }

    } else {
        // 本部CD
        if (isset($_SESSION['HONBUCD'])) {
            $honbuCode = $_SESSION['HONBUCD'];
        } else {
            $honbuCode = '';
        }

        // 支部CD
        if (isset($_SESSION['SHIBUCD'])) {
            $shibuCode = $_SESSION['SHIBUCD'];
        } else {
            $shibuCode = '';
        }

        // 基地CD
        if (isset($_SESSION['COMPCD'])) {
            $compCode = $_SESSION['COMPCD'];
        } else {
            $compCode = '';
        }

        //// 店舗名
        //if (isset($_SESSION['COMPNAME'])) {
        //    $compName = $_SESSION['COMPNAME'];
        //} else {
        //    $compName = '';
        //}
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

    if ($appliReason == APPLI_REASON_EXCHANGE_FIRST || $appliReason == APPLI_REASON_EXCHANGE_SIZE) {   // サイズ交換の場合はワンサイズ展開のサイズIDを取得
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
    if ($appliReason == APPLI_REASON_ORDER_GRADEUP) {
        $sql .= " AND";
        $sql .=     " C.CompKind = 2";
    }
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
        // 初回サイズ交換の場合は、出荷から一定期間内であれば交換可能とする。
        if ($appliReason == APPLI_REASON_EXCHANGE_FIRST) {
            // 出荷日から起算して10日以上経過している商品は表示しない
// 一時的に日数制限を解除 by T.Uno at 2023/04/27
//            $sql .= " AND";
//            $sql .=     " CONVERT(char, tod.ShipDay, 111) >= '" . date("Y/m/d", strtotime(EXCHANGE_TERM)) . "'";
        }
    }

    // サイズ交換の場合はSizeが１つの商品ははぶく
    if (($appliReason == APPLI_REASON_EXCHANGE_FIRST || $appliReason == APPLI_REASON_EXCHANGE_SIZE) && count($sizeAry) != 0) {
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
    if (($appliReason == APPLI_REASON_EXCHANGE_FIRST || $appliReason == APPLI_REASON_EXCHANGE_SIZE) && count($sizeAry) != 0) {
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
        $sql .=     " C.CompID = '" . db_Escape($compID) . "'";
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
    $sql .=     " HonbuCd,";
    $sql .=     " HonbuName,";
    $sql .=     " ShibuCd,";
    $sql .=     " ShibuName,";
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
    $sql .=             " mcp2.HonbuCd,";
    $sql .=             " mcp2.HonbuName,";
    $sql .=             " mcp2.ShibuCd,";
    $sql .=             " mcp2.ShibuName,";
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
    if ($appliReason == APPLI_REASON_ORDER_GRADEUP) {
        $sql .=                                 " AND";
        $sql .=                                     " mcp3.CompKind = 2";
    }
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
        // 初回サイズ交換の場合は、出荷から一定期間内であれば交換可能とする。
        if ($appliReason == APPLI_REASON_EXCHANGE_FIRST) {
            // 出荷日から起算して10日以上経過している商品は表示しない
// 一時的に日数制限を解除 by T.Uno at 2023/04/27
//            $sql .=                                 " AND";
//            $sql .=                                     " CONVERT(char, tod.ShipDay, 111) >= '" . date("Y/m/d", strtotime(EXCHANGE_TERM)) . "'";
        }
    }
    // サイズ交換の場合はSizeが１つの商品ははぶく
    if (($appliReason == APPLI_REASON_EXCHANGE_FIRST || $appliReason == APPLI_REASON_EXCHANGE_SIZE) && count($sizeAry) != 0) {
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
    if (($appliReason == APPLI_REASON_EXCHANGE_FIRST || $appliReason == APPLI_REASON_EXCHANGE_SIZE) && count($sizeAry) != 0) {
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

//var_dump($sql);

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

?>
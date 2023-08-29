<?php
/*
 * 店舗検索画面
 * search_comp.src.php
 *
 * create 2007/04/12 H.Osugi
 *
 *
 */

// 各種モジュールの取得
include_once('../../include/define.php');			// 定数定義
require_once('../../include/dbConnect.php');		// DB接続モジュール
require_once('../../include/msSqlControl.php');		// DB操作モジュール
require_once('../../include/checkLogin.php');		// ログイン判定モジュール
require_once('../../include/castHtmlEntity.php');	// HTMLエンティティモジュール
require_once('../../include/redirectPost.php');		// リダイレクトポストモジュール
require_once('../../include/setPaging.php');		// ページング情報セッティングモジュール

// 変数の初期化 ここから ******************************************************
$comps      = array();
$searchComp = '';					// 基地名
$searchCompCode = '';				// 基地コード

$isSearched = false;				// 検索を行ったかどうか
// 変数の初期化 ここまで ******************************************************

// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST); 

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

	// 表示する店舗一覧を取得
	$comps = getCompData($dbConnect, $post, $nowPage, $allCount, $isLevelItc, $isLevelHonbu);
	
	// ページングのセッティング
	$paging = setPaging($nowPage, PAGE_PER_DISPLAY_SEARCH_COMP, $allCount, $isLevelItc);
	
	// 店舗が０件の場合
	if ($allCount <= 0) {
		$isSearched = false;
	}

}

// 表示する値の成型 ここから +++++++++++++++++++++++++++++++++++++++++++++++++++

if (isset($post['searchCompCode']) && $post['searchCompCode'] != '') {
    // 基地コード
    $searchCompCode = trim($post['searchCompCode']);
}

if (isset($post['searchComp']) && $post['searchComp'] != '') {
    // 基地名
    $searchComp     = trim($post['searchComp']);
}

// 表示する値の成型 ここまで +++++++++++++++++++++++++++++++++++++++++++++++++++

/*
 * 店舗一覧情報を取得する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$post           => POST値
 *       ：$nowPage        => 現在のページ数
 *       ：$allCount       => 全件数
 * 戻り値：$result         => 店舗一覧情報
 *
 * create 2007/04/12 H.Osugi
 *
 */
function getCompData($dbConnect, $post, $nowPage, &$allCount, $isLevelItc, $isLevelHonbu) {

	global $isLevelAgency;

	// 初期化
	$compName  = '';
	$offset    = '';
	$corpCode  = '';
	$honbuCd   = '';
	$shibuCd   = '';

	// 取得したい件数
	$limit = PAGE_PER_DISPLAY_SEARCH_COMP;		// 1ページあたりの表示件数;

	// 取得したいデータの開始位置
	$offset = ($nowPage - 1) * PAGE_PER_DISPLAY_SEARCH_COMP;

	// 基地名
	$compName = $post['searchComp'];

	// 基地コード
	$compCode = $post['searchCompCode'];

// 発注時に出荷先選択で全国から選択できるようにコメント化 2017/06/08 Y.Furukawa
//    if (!$isLevelItc) {
//
//        if ($isLevelHonbu) {
//            // 支部権限
//            if (isset($_SESSION['HONBUCD'])) {
//                $honbuCd = $_SESSION['HONBUCD'];
//            } else {
//                $honbuCd = '';
//            }
//
//        } else {
//            // 支部権限
//            if (isset($_SESSION['SHIBUCD']) && isset($_SESSION['HONBUCD'])) {
//                $honbuCd = $_SESSION['HONBUCD'];
//                $shibuCd = $_SESSION['SHIBUCD'];
//            } else {
//                $honbuCd = '';
//                $shibuCd = '';
//            }
//        }
//    }

	// 店舗の件数を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" COUNT(CompID) as count_comp";
	$sql .= " FROM";
	$sql .= 	" M_Comp";
	$sql .= " WHERE";
    $sql .=     " ShopFlag <> 0";

	if ($compCode != '') {
		$sql .= " AND";
		$sql .= 	" CompCd = '" . db_Like_Escape($compCode) . "'";
	}

	if ($compName != '') {
		$sql .= " AND";
		$sql .= 	" CompName LIKE '%" . db_Like_Escape($compName) . "%'";
	}

	if ($honbuCd != '') {
		$sql .= " AND";
		$sql .= 	" HonbuCd = '" . db_Like_Escape($honbuCd) . "'";
	}

	if ($shibuCd != '') {
		$sql .= " AND";
		$sql .= 	" ShibuCd = '" . db_Like_Escape($shibuCd) . "'";
	}

	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	$allCount = 0;
	if (!isset($result[0]['count_comp']) || $result[0]['count_comp'] <= 0) {
		$result = array();
	 	return $result;
	}

	// 全件数
	$allCount = $result[0]['count_comp'];

	$top = $offset + $limit;
	if ($top > $allCount) {
		$limit = $limit - ($top - $allCount);
		$top   = $allCount;
	}

	// 店舗の一覧を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" CompID,";
	$sql .= 	" CompCd,";
	$sql .= 	" CompName,";
	$sql .= 	" Zip,";
	$sql .= 	" Adrr,";
	$sql .= 	" Tel,";
	$sql .= 	" ShipName,";
	$sql .= 	" TantoName";
	$sql .= " FROM";
	$sql .= 	" (";
	$sql .= 		" SELECT";
	$sql .= 			" TOP " . $limit;
	$sql .= 			" *";
	$sql .= 		" FROM";
	$sql .= 			" M_Comp mco2";
	$sql .= 		" WHERE";
	$sql .= 			" mco2.CompID IN (";
	$sql .= 						" SELECT";
	$sql .= 							" CompID";
	$sql .= 						" FROM";
	$sql .= 							" (";
	$sql .= 								" SELECT";
	$sql .= 									" DISTINCT";
	$sql .= 									" TOP " . ($top);
	$sql .= 									" mco3.CompID,";
	$sql .= 									" mco3.CompCd";
	$sql .= 								" FROM";
	$sql .= 									" M_Comp mco3";
	$sql .= 								" WHERE";
    $sql .= 									" mco3.ShopFlag <> 0";

	if ($isLevelAgency == true) {
		$sql .= 							" AND";
		$sql .= 								" mco3.AgencyID = '" . db_Escape($_SESSION['COMPID']) . "'";
	}

	if ($compCode != '') {
		$sql .= 								" AND";
		$sql .= 									" mco3.CompCd = '" . db_Like_Escape($compCode) . "'";
	}

	if ($compName != '') {
		$sql .= 								" AND";
		$sql .= 									" mco3.CompName LIKE '%" . db_Like_Escape($compName) . "%'";
	}

	if ($corpCode != '') {
		$sql .= 								" AND";
		$sql .= 									" mco3.CorpCd = '" . db_Like_Escape($corpCode) . "'";
	}

	if ($honbuCd != '') {
		$sql .= 								" AND";
		$sql .= 									" mco3.HonbuCd = '" . db_Like_Escape($honbuCd) . "'";
	}

	if ($shibuCd								 != '') {
		$sql .= 								" AND";
		$sql .= 									" mco3.ShibuCd = '" . db_Like_Escape($shibuCd) . "'";
	}

	$sql .= 								" AND";
	$sql .= 									" mco3.Del = " . DELETE_OFF;
	$sql .= 								" ORDER BY";
	$sql .= 									" mco3.CompCd ASC,";
	$sql .= 									" mco3.CompID ASC";
	$sql .= 							" ) mco4";
	$sql .= 						" )";
	$sql .=					" ORDER BY";
	$sql .=						" mco2.CompCd DESC,";
	$sql .=						" mco2.CompID DESC";

	$sql .= 	" ) mco";

	$sql .= 	" ORDER BY";
	$sql .= 		" mco.CompCd ASC,";
	$sql .= 		" mco.CompID ASC";

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	// 取得した値をHTMLエンティティ
	$resultCount = count($result);
	for ($i=0; $i<$resultCount; $i++) {

		$result[$i]['CompID']    = $result[$i]['CompID'];
		$result[$i]['CompCd']    = castHtmlEntity($result[$i]['CompCd']);
		$result[$i]['CompName']  = castHtmlEntity($result[$i]['CompName']);
		$result[$i]['Zip']       = castHtmlEntity($result[$i]['Zip']);
        list($result[$i]['Zip1'], $result[$i]['Zip2']) = explode('-', $result[$i]['Zip']);
		$result[$i]['Adrr']      = castHtmlEntity($result[$i]['Adrr']);
		$result[$i]['Tel']       = castHtmlEntity($result[$i]['Tel']);
		$result[$i]['ShipName']  = castHtmlEntity($result[$i]['ShipName']);
		$result[$i]['TantoName'] = castHtmlEntity($result[$i]['TantoName']);

	}

	return  $result;

}

?>
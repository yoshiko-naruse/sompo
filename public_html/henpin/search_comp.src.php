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

// 管理権限のないユーザが閲覧しようとするとTOPに強制遷移
if ($isLevelAdmin == false) {

	$hiddens = array();
	redirectPost(HOME_URL . 'top.php', $hiddens);

}
 
// 変数の初期化 ここから ******************************************************
$comps      = array();
$searchComp = '';					// 店舗名

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
	$comps = getCompData($dbConnect, $post, $nowPage, $allCount);
	
	// ページングのセッティング
	$paging = setPaging($nowPage, PAGE_PER_DISPLAY_SEARCH_COMP, $allCount, $isLevelItc);
	
	// 店舗が０件の場合
	if ($allCount <= 0) {
		$isSearched = false;
	}

}

// 表示する値の成型 ここから +++++++++++++++++++++++++++++++++++++++++++++++++++

// 店舗コード
$searchComp    = trim($post['searchComp']);

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
function getCompData($dbConnect, $post, $nowPage, &$allCount, $isLevelItc) {

	global $isLevelAgency;

	// 初期化
	$compName  = '';
	$offset    = '';
	$corpCode  = '';

	// 取得したい件数
	$limit = PAGE_PER_DISPLAY_SEARCH_COMP;		// 1ページあたりの表示件数;

	// 取得したいデータの開始位置
	$offset = ($nowPage - 1) * PAGE_PER_DISPLAY_SEARCH_COMP;

	// 店舗名
	$compName = $post['searchComp'];

    if (!$isLevelItc) {
        // 支店CD
        if (isset($_SESSION['CORPCD'])) {
            $corpCode = $_SESSION['CORPCD'];
        } else {
            $corpCode = '';
        }
	}

	// 店舗の件数を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" COUNT(CompID) as count_comp";
	$sql .= " FROM";
	$sql .= 	" M_Comp";
	$sql .= " WHERE";

    if ($_SESSION['ADMINLVL'] == 0) {   // ティファニー
        if ($_SESSION['SHOPFLAG'] == EXCEPTIONALSHOP_EXCEPTIONAL) {
            $sql .=     " ShopFlag = 2";         // ShopFlagが2の店舗のみを表示する
        } else {
            $sql .=     " ShopFlag = 1";         // ShopFlagが1の店舗のみを表示する
        }
    } else {                            // ＩＴＣ管理者
        $sql .=     " ShopFlag <> 0";        // ShopFlagが0以外の店舗を表示する
    }

	if ($compCode != '') {
		$sql .= " AND";
		$sql .= 	" CompCd = '" . db_Like_Escape($compCode) . "'";
	}

	if ($compName != '') {
		$sql .= " AND";
		$sql .= 	" CompName LIKE '%" . db_Like_Escape($compName) . "%'";
	}

	if ($corpCode != '') {
		$sql .= " AND";
		$sql .= 	" CorpCd = '" . db_Like_Escape($corpCode) . "'";
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
	$sql .= 	" CompName";
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

    if ($_SESSION['ADMINLVL'] == 0) {   // ティファニー
        if ($_SESSION['SHOPFLAG'] == EXCEPTIONALSHOP_EXCEPTIONAL) {
            $sql .=     " mco3.ShopFlag = 2";         // ShopFlagが2の店舗のみを表示する
        } else {
            $sql .=     " mco3.ShopFlag = 1";         // ShopFlagが1の店舗のみを表示する
        }
    } else {                            // ＩＴＣ管理者
        $sql .=     " mco3.ShopFlag <> 0";        // ShopFlagが0以外の店舗を表示する
    }

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

		$result[$i]['CompID']   = $result[$i]['CompID'];
		$result[$i]['CompCd']   = castHtmlEntity($result[$i]['CompCd']);
		$result[$i]['CompName'] = castHtmlEntity($result[$i]['CompName']);

	}

	return  $result;

}

?>
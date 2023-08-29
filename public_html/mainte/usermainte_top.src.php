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
include_once('../../include/define.php');			// 定数定義
include_once('../../include/dbConnect.php');		// DB接続モジュール
include_once('../../include/msSqlControl.php');		// DB操作モジュール
include_once('../../include/checkLogin.php');		// ログイン判定モジュール
include_once('../../include/castHtmlEntity.php');	// HTMLエンティティモジュール
include_once('../../include/redirectPost.php');		// リダイレクトポストモジュール
include_once('../../include/setPaging.php');		// ページング情報セッティングモジュール
include_once('../../include/commonFunc.php');       // 共通関数モジュール

//admin以外はTOPへ遷移
//08/11/20 uesugi
//if (!$isLevelAdmin){
//    redirectTop();
//}
// 初期設定
$isMenuAdmin = true;	// 管理機能のメニューをアクティブに

// 変数の初期化 ここから ******************************************************
$searchCompCd       = '';					// 店舗コード
$searchCompName     = '';					// 店舗名
$searchCompId       = '';					// 店舗ID
$searchStaffCode    = '';					// スタッフコード
$searchPersonName   = '';					// スタッフ氏名

$compCd             = '';
$compName           = '';

$isSelectedAdmin    = false;				// 検索を行ったかどうか

// 変数の初期化 ここまで ******************************************************
$compCd   = $_SESSION['COMPCD'];
$compName = $_SESSION['COMPNAME'];


// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST); 

if($post['nowPage'] == "" || $post['nowPage'] == 0){
	$nowpage = 1;
}else{
	$nowpage = $post['nowPage'];
}

$isSelectedAdmin = trim($post['isSelectedAdmin']);

// 検索時は1ページ目に戻す
if ($post['searchFlg'] == 1) {
	$nowpage = 1;
	$isSelectedAdmin = true;
}

if ($isSelectedAdmin == true) {

	$isSelectedAdmin = true;

	// 表示するユーザー一覧を取得
	$users = getUserMaster($dbConnect, $post, $nowPage, $allCount);

	// ページコントロール
	$page = _getComPageSet($nowpage,PAGE_PER_DISPLAY_USERMASTER,count($users),5);
	// 表示データ作成
	for($i = $page['rcd_start'] - 1,$j=0;$i<$page['rcd_end'];$i++,$j++){
		$list[$j] = $users[$i];
	}
	$users = $list;

	// ユーザーが０件の場合
	if (count($users) <= 0) {

		// 条件が指定されているか判定
		$hasCondition = checkCondition($post);

		$hiddens['errorName'] = 'userMainte';
		$hiddens['menuName']  = 'isMenuAdmin';

		$hiddens['returnUrl'] = 'mainte/usermainte_top.php';

		$hiddens['errorId'][] = '001';
		$errorUrl             = HOME_URL . 'error.php';

	 	redirectPost($errorUrl, $hiddens);

	}

}

// 表示する値の成型 ここから +++++++++++++++++++++++++++++++++++++++++++++++++++

// 店舗コード
$searchCompCd       = trim($post['searchCompCd']);

// 店舗名
$searchCompName     = trim($post['searchCompName']);

// 店舗ID
$searchCompId       = trim($post['searchCompId']);

// スタッフコード
$searchStaffCode    = trim($post['searchStaffCode']);

// スタッフ氏名
$searchPersonName    = trim($post['searchPersonName']);

// 表示する値の成型 ここまで +++++++++++++++++++++++++++++++++++++++++++++++++++

/*
 * 注文履歴一覧情報を取得する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$post           => POST値
 *       ：$nowPage        => 現在のページ数
 *       ：$allCount       => 全件数
 * 戻り値：$result         => 注文履歴一覧情報
 *
 * create 2007/03/26 H.Osugi
 *
 */
function getUserMaster($dbConnect, $post, $nowPage, &$allCount) {

	global $isLevelAdmin;	// 管理者権限の有無
	global $isLevelAgency;	// 一次代理店
	global $isLevelItc;	    // max権限の有無
	global $isLevelHonbu;	// 本部権限の有無
	global $isLevelNormal;	// 基地（一般）権限の有無

	// 初期化
	$compId       = '';
	$staffCode    = '';
	$personName   = '';
	$limit        = '';
	$offset       = '';
    $honbuCd      = '';
    $shibuCd      = '';

	// 取得したい件数
	$limit = PAGE_PER_DISPLAY_USERMASTER;		// 1ページあたりの表示件数;

	// 取得したいデータの開始位置
	$offset = ($nowPage - 1) * PAGE_PER_DISPLAY_USERMASTER;

	// 店舗ID
	$compId = trim($post['searchCompId']);

	if ($isLevelNormal) {
		$compCd = $_SESSION['COMPCD'];
	} else {
		$compCd = trim($post['searchCompCd']);
	}

	// スタッフコード
	$staffCode = trim($post['searchStaffCode']);

	// スタッフ氏名
	$personName = trim($post['searchPersonName']);

//	if ($isLevelAdmin == true) {

        if (!$isLevelItc) {

            if ($isLevelHonbu) {
                // 本部権限
                if (isset($_SESSION['HONBUCD'])) {
                    $honbuCd = $_SESSION['HONBUCD'];
                } else {
                    $honbuCd = '';
                }

            } else {
                // 支部権限
                if (isset($_SESSION['SHIBUCD']) && isset($_SESSION['HONBUCD'])) {
                    $honbuCd = $_SESSION['HONBUCD'];
                    $shibuCd = $_SESSION['SHIBUCD'];
                } else {
                    $honbuCd = '';
                    $shibuCd = '';
                }
            }
        }
//    }

	// ユーザーマスタの件数を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" COUNT(DISTINCT ms.StaffSeqID) as count_user";
	$sql .= " FROM";
	$sql .= 	" M_Staff AS ms";
	$sql .= " INNER JOIN M_Comp AS mc ON ms.CompCd = mc.CompCd";
	$sql .= " WHERE";
	$sql .= 	" ms.Del = 0";
	$sql .= " AND";
	$sql .= 	" mc.Del = 0";
	$sql .= " AND";
    $sql .=     " mc.ShopFlag <> 0";        // ShopFlagが0以外の店舗を表示する

	if ($compCd != '') {
		$sql .= " AND";
		$sql .= 	" ms.CompCd = '" . db_Escape($compCd) . "'";
	}
	if ($honbuCd != '') {
		$sql .= " AND";
		$sql .= 	" mc.HonbuCd = '" . db_Escape($honbuCd) . "'";
	}
	if ($shibuCd != '') {
		$sql .= " AND";
		$sql .= 	" mc.ShibuCd = '" . db_Escape($shibuCd) . "'";
	}
	if ($staffCode != '') {
		$sql .= " AND";
		$sql .= 	" ms.StaffCode = '" . db_Escape($staffCode) . "'";
	}
	if ($personName != '') {
		$sql .= " AND";
		$sql .= 	" ms.PersonName LIKE '%" . db_Escape($personName) . "%'";
	}

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	$allCount = 0;
	if (!isset($result[0]['count_user']) || $result[0]['count_user'] <= 0) {
		$result = array();
	 	return $result;
	}

	// 全件数
	$allCount = $result[0]['count_user'];

	$top = $offset + $limit;
	if ($top > $allCount) {
		$limit = $limit - ($top - $allCount);
		$top   = $allCount;
	}

	// M_Staffの一覧を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	"  ms.StaffSeqID";
	$sql .= 	" ,ms.CompCd";
	$sql .= 	" ,ms.StaffCode";
	$sql .= 	" ,ms.PersonName";
	$sql .= 	" ,ms.HatureiDay";
	$sql .= 	" ,ms.NextNameCd";
	$sql .=		" ,ms.NextCompID";
	$sql .=		" ,mc.CompID";
	$sql .=		" ,mc.CompName";	
	$sql .= " FROM";
	$sql .= 	" M_Staff AS ms";
	$sql .= " INNER JOIN M_Comp AS mc ON ms.CompCd = mc.CompCd";
	$sql .= " WHERE";
	$sql .= 	" ms.Del = 0";
	$sql .= " AND";
	$sql .= 	" mc.Del = 0";


	$sql .= " AND";
    if ($_SESSION['ADMINLVL'] == 0) {   // ティファニー
        if ($_SESSION['SHOPFLAG'] == EXCEPTIONALSHOP_EXCEPTIONAL) {
            $sql .=     " mc.ShopFlag = 2";         // ShopFlagが2の店舗のみを表示する
        } else {
            $sql .=     " mc.ShopFlag = 1";         // ShopFlagが1の店舗のみを表示する
        }
    } else {                            // ＩＴＣ管理者
        $sql .=     " mc.ShopFlag <> 0";        // ShopFlagが0以外の店舗を表示する
    }

	if ($compCd != '') {

		$sql .= " AND";
		$sql .= 	" ms.CompCd = '" . db_Escape($compCd) . "'";
		//$sql .= 	" (ms.CompCd = '" . db_Escape($compCd) . "'";
		//$sql .= 	" AND ms.CorpCd = '001')";
		
	}
	if ($honbuCd != '') {
		$sql .= " AND";
		$sql .= 	" mc.HonbuCd = '" . db_Escape($honbuCd) . "'";
	}
	if ($shibuCd != '') {
		$sql .= " AND";
		$sql .= 	" mc.ShibuCd = '" . db_Escape($shibuCd) . "'";
	}
	if ($staffCode != '') {
		$sql .= " AND";
		$sql .= 	" ms.StaffCode = '" . db_Escape($staffCode) . "'";
	}
	if ($personName != '') {
		$sql .= " AND";
		$sql .= 	" ms.PersonName LIKE '%" . db_Escape($personName) . "%'";
	}

	$sql .= " ORDER BY mc.CompCd, ms.StaffCode";

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	// 取得した値をHTMLエンティティ
	$resultCount = count($result);
	for ($i=0; $i<$resultCount; $i++) {

		$result[$i]['UserID']   = $result[$i]['UserID'];
		$result[$i]['NameCd']   = castHtmlEntity($result[$i]['NameCd']);
		$result[$i]['Name']     = castHtmlEntity($result[$i]['Name']);
		$result[$i]['CompID']   = $result[$i]['CompID'];
		$result[$i]['CompCd']   = castHtmlEntity($result[$i]['CompCd']);
		$result[$i]['CompName'] = castHtmlEntity($result[$i]['CompName']);

	}

	return $result;

}

/*
 * 検索条件を指定しているか判定
 * 引数  ：$post           => POST値
 * 戻り値：true：条件を指定している / false：条件を指定していない
 *
 * create 2007/04/06 H.Osugi
 *
 */
function checkCondition($post) {

	global $isLevelAdmin;	// 管理者権限の有無

	// 店舗IDの指定があった場合
	if (isset($post['searchCompId']) && $post['searchCompId'] != '') {
		return true;
	}

	// スタッフコードの指定があった場合
	if (isset($post['searchStaffCode']) && $post['searchStaffCode'] != '') {
		return true;
	}

	// スタッフ氏名の指定があった場合
	if (isset($post['searchPersonName']) && $post['searchPersonName'] != '') {
		return true;
	}

	return false;

}
/**
* --------------------------------------------------
*  処理内容         ページ数表示制御
*  引数             
*  @param 　　　　　$nowpage   現在のページ
*  @param 　　　　　$pagerow 　データの表示件数
*  @param 　　　　　$allcount　最大件数
*  @param 　　　　　$view_cnt  ページの表示件数
*  戻り値
*  @return          ページ制御に必要な連想配列
*  備考             
*  作成日           2007/11/06
*  更新
* --------------------------------------------------
**/
function _getComPageSet($nowpage="", $pagerow, $allcount, $view_cnt) {
    
    if($nowpage == ""){
        $nowpage = 1;
    }
    $ret['maxpage'] = ceil($allcount / $pagerow);
    $ret['page_row'] = $pagerow;
    $ret['page'] = $nowpage;
    if ($ret['page'] > $ret['maxpage']){
        $ret['page'] = 1;
    }
    $ret['offset'] = $pagerow * ($ret['page'] - 1);
    $ret['back'] = ($ret['page'] != 1) ? ($ret['page'] - 1) : 0;
    $ret['next'] = (($ret['page'] != $ret['maxpage']) && $ret['maxpage']) ? ($ret['page'] + 1) : 0;
    $ret['rcd_start'] = $ret['offset'] + 1;
    $ret['rcd_end'] = $ret['page'] * $pagerow;
    if ($ret['rcd_end'] > $allcount) $ret['rcd_end'] = $allcount;
    $ret['allcount'] = $allcount;
    $ret['view_cnt'] = $view_cnt;
    if ($ret['maxpage'] > $view_cnt) {
        $chk = $view_cnt - $ret['page'] - floor($view_cnt / 2);
        $ret['page_list_from'] = $chk * -1 + 1;
        $ret['page_list_to'] = $view_cnt - $chk;
        if ($ret['page_list_to'] > $ret['maxpage']) {
            $ret['page_list_to'] = $ret['maxpage'];
            $ret['page_list_from'] = $ret['page_list_to'] - $view_cnt + 1;
        } else if ($ret['page_list_from'] <= 0) {
            $ret['page_list_to'] = $view_cnt;
            $ret['page_list_from'] = 1;
        }
    } else {
        $ret['page_list_from'] = 1;
        $ret['page_list_to'] = $ret['maxpage'];
    }
    if($ret['page'] == ""){
        $ret['page'] = 1;
    }
	if($ret['maxpage'] == $ret['page'] || $ret['maxpage'] == 0){
		$ret['next_view'] = false;
	}else{
		$ret['next_view'] = true;
	}
	if($ret['page'] == 1){
		$ret['back_view'] = false;
	}else{
		$ret['back_view'] = true;
	}
    return $ret;
}
?>
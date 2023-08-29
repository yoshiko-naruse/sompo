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
?><!DOCTYPE html>
<html>
  <head>
    <META http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <script src="//code.jquery.com/jquery-1.12.4.min.js"></script>
    <link href="/main.css" rel="stylesheet" type="text/css">
    <title>制服管理システム</title>
	
	<script language="JavaScript">
    <!--
    function is_submit(url,sid,mode) {

	  document.pagingForm.StaffSeqID.value=sid; 
	  document.pagingForm.Mode.value=mode; 
	  document.pagingForm.action=url; 
	  document.pagingForm.submit();
      return false;

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
        

        <form method="post" action="#" name="pagingForm">
          <div id="contents">
            <h1>職員マスタメンテナンス</h1>

            <table border="0" width="700" cellpadding="0" cellspacing="0" class="tb_1">
              <tr height="60">
                <td width="700" align="center">
				  <input type="button" value="  新規追加  " onclick="is_submit('./usermainte.php','','ins');">
                </td>
              </tr>
            </table>

        	<h3>「修正・変更」の場合は下記項目から対象職員を検索して下さい。</h3>
            <table border="0" width="700" cellpadding="0" cellspacing="0" class="tb_1">
              <tr height="40">
                <td width="80" class="line"><span class="fbold">所属施設</span></td>

<?php if(!$isLevelAdmin) { ?>
                <td colspan="2" class="line"><?php isset($compCd) ? print($compCd) : print('&#123;compCd&#125;'); ?>：<?php isset($compName) ? print($compName) : print('&#123;compName&#125;'); ?></td>
                <td class="line" class="line"></td>
<?php } ?>
<?php if($isLevelAdmin) { ?>
                <td colspan="3" class="line">
                  <input type="text" name="searchCompCd" value="<?php isset($searchCompCd) ? print($searchCompCd) : print('&#123;searchCompCd&#125;'); ?>" style="width:60px;" readonly="readonly"><input type="text" name="searchCompName" value="<?php isset($searchCompName) ? print($searchCompName) : print('&#123;searchCompName&#125;'); ?>" style="width:310px;" readonly="readonly">
                  <input type="hidden" name="searchCompId" value="<?php isset($searchCompId) ? print($searchCompId) : print('&#123;searchCompId&#125;'); ?>">
                  <input name="shop_btn" type="button" value="施設検索" onclick="window.open('../search_comp.php', 'searchComp', 'width=500, height=400, menubar=no, toolbar=no, scrollbars=yes'); return false;">
                </td>
<?php } ?>

                <td></td>
              </tr>
              <tr height="40">
                <td width="80" class="line"><span class="fbold">職員コード</span></td>
                <td width="160"class="line"><input name="searchStaffCode" type="text" value="<?php isset($searchStaffCode) ? print($searchStaffCode) : print('&#123;searchStaffCode&#125;'); ?>" style="width:100px;" maxlength="12"></td>
                <td width="80" align="center" class="line"><span class="fbold">氏名</span></td>
                <td width="260" class="line"><input name="searchPersonName" type="text" value="<?php isset($searchPersonName) ? print($searchPersonName) : print('&#123;searchPersonName&#125;'); ?>" style="width:200px;"></td>
                <td class="line" align="center">
                  <input type="button" value="     検索     " onclick="document.pagingForm.searchFlg.value='1'; document.pagingForm.submit(); return false;">
                </td>
              </tr>
            </table>
			<br>

<?php if($users) { ?>
            <table border="0" width="550" cellpadding="0" cellspacing="0" class="tb_1" bgcolor="#000000">
			  <tr>
				<td>
				  <table border="0" cellpadding="0" cellspacing="1" class="tb_1">
					<tr bgcolor="#FFFFFF">
					  <th width="200">施設名</th>
					  <th width="120">職員コード</th>
					  <th width="150">氏名</th>
					  <th width="100">設定</th>
					</tr>
<?php for ($i1_users=0; $i1_users<count($users); $i1_users++) { ?>
					<tr height="30" bgcolor="#FFFFFF">
					  <td align="left">&nbsp;&nbsp;<?php isset($users[$i1_users]['CompName']) ? print($users[$i1_users]['CompName']) : print('&#123;users.CompName&#125;'); ?></td>
					  <td align="left">&nbsp;&nbsp;<?php isset($users[$i1_users]['StaffCode']) ? print($users[$i1_users]['StaffCode']) : print('&#123;users.StaffCode&#125;'); ?></td>
					  <td align="left">&nbsp;&nbsp;<?php isset($users[$i1_users]['PersonName']) ? print($users[$i1_users]['PersonName']) : print('&#123;users.PersonName&#125;'); ?></td>
					  <td align="center">
						<input type="button" value="設定" onclick="is_submit('./usermainte.php','<?php isset($users[$i1_users]['StaffSeqID']) ? print($users[$i1_users]['StaffSeqID']) : print('&#123;users.StaffSeqID&#125;'); ?>','upd');">
					  </td>
					</tr>
<?php } ?>
				  </table>
				</td>
			  </tr>
			</table>
<?php } ?>
          </div>
		  
		   <div class="tb_4">
          <table border="0" width="700" cellpadding="0" cellspacing="0" class="tb_1">
			<tr><td colspan="3">&nbsp;</td></tr>
            <tr>
              <td width="50" align="left">
<?php if($page['back_view']) { ?>
                <input name="prev_btn" type="button" value="&lt;&lt" onclick=" document.pagingForm.nowPage.value = <?php isset($page['back']) ? print($page['back']) : print('&#123;page.back&#125;'); ?>; document.pagingForm.submit(); return false;">
<?php } ?>
              </td>
              <td width="50" align="right">
<?php if($page['next_view']) { ?>
                <input name="prev_btn" type="button" value="&gt;&gt" onclick=" document.pagingForm.nowPage.value = <?php isset($page['next']) ? print($page['next']) : print('&#123;page.next&#125;'); ?>; document.pagingForm.submit(); return false;">
<?php } ?>
              </td>
              <td width="600"></td>
            </tr>
          </table>
        </div>
		
		  <input type="hidden" name="StaffSeqID">
		  <input type="hidden" name="Mode">
		  <input type="hidden" name="nowPage" value="<?php isset($page['page']) ? print($page['page']) : print('&#123;page.page&#125;'); ?>">
		  
          <input type="hidden" name="mainteMode">
          <input type="hidden" name="userId">
          <input type="hidden" name="searchFlg">
		  <input type="hidden" name="isSelectedAdmin" value="1">
          <input type="hidden" name="encodeHint" value="京">
		  
        </form>
        <br><br><br>
        

      </div>
    </div>
  </body>
</html>

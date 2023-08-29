<?php
/*
 * 特寸入力画面
 * hachu_tokusun.src.php
 *
 * create 2007/03/30 H.Osugi
 *
 */

// 各種モジュールの取得
include_once('../../include/define.php');				// 定数定義
require_once('../../include/dbConnect.php');			// DB接続モジュール
require_once('../../include/msSqlControl.php');			// DB操作モジュール
require_once('../../include/checkLogin.php');			// ログイン判定モジュール
require_once('../../include/getSize.php');				// サイズ情報取得モジュール
require_once('../../include/checkData.php');			// 対象文字列検証モジュール
require_once('../../include/redirectPost.php');			// リダイレクトポストモジュール
require_once('../../include/castHidden.php');			// hidden値成型モジュール
require_once('../../include/castHtmlEntity.php');		// HTMLエンティティモジュール
require_once('../../include/commonFunc.php');           // 共通関数モジュール

// 初期設定
$isMenuOrder = true;	// 発注のメニューをアクティブに

// 変数の初期化 ここから *******************************************************
$high     = '';					// 身長
$weight   = '';					// 体重
$bust     = '';					// バスト
$waist    = '';					// ウエスト
$hips     = '';					// ヒップ
$shoulder = '';					// 肩幅
$sleeve   = '';					// 袖丈
$length   = '';					// スカート丈
$kitake   = '';                 // 着丈
$yukitake = '';                 // 裄丈
$inseam   = '';                 // 股下
$tokMemo  = '';					// 特寸備考

$nextUrl   = '';				// 遷移先URL
$returnUrl = '';				// 戻り先URL
// 変数の初期化 ここまで ******************************************************

$post = $_POST;

// 遷移先URLと戻り先URLの設定
switch($post['hachuShinseiFlg']) {
	case true:
		$nextUrl   = './hachu_shinsei_kakunin.php';
		$returnUrl = './hachu_shinsei.php';
		break;

	default:
		$hidden = array();
		redirectPost('./hachu_top.php', $hidden);
		break;
}

// 申請履歴から遷移してきた場合（発注変更）
if (isset($post['rirekiFlg']) && $post['rirekiFlg'] == 1
	&& (!isset($post['tokFlg']) || $post['tokFlg'] != 1)) {

	$post = getTokData($dbConnect, $post);

}

// POST値をHTMLエンティティ
$post = castHtmlEntity($post); 

// 表示する値の成型 ここから +++++++++++++++++++++++++++++++++++++++++++++++++++
// 身長
$high = trim($post['high']);

// 体重
$weight = trim($post['weight']);

// バスト
$bust = trim($post['bust']);

// ウエスト
$waist = trim($post['waist']);

// ヒップ
$hips = trim($post['hips']);

// 肩幅
$shoulder = trim($post['shoulder']);

// 袖丈
$sleeve = trim($post['sleeve']);

// スカート丈
$length = trim($post['length']);

// 着丈
$kitake = trim($post['kitake']);

// 裄丈
$yukitake = trim($post['yukitake']);

// 股下
$inseam = trim($post['inseam']);

// 特寸備考
$tokMemo = trim($post['tokMemo']);

if (isset($post['rirekiFlg']) && $post['rirekiFlg'] == 1) {

	$isMenuOrder   = false;
	$isMenuHistory = true;	// 申請履歴のメニューをアクティブに
	$haveRirekiFlg = true;	// 発注申請か発注変更かを判定するフラグ

}

// hidden値の成型
// 状態
$countSearchStatus = count($post['searchStatus']);
for ($i=0; $i<$countSearchStatus; $i++) {
	$post['searchStatus[' . $i . ']'] = $post['searchStatus'][$i];
}
$countItemIds = count($post['itemIds']);
for ($i=0; $i<$countItemIds; $i++) {
	$post['itemIds[' . $i . ']'] = $post['itemIds'][$i];
	if ($post['sizeType'][$post['itemIds'][$i]] != 3) {
		$post['size[' . $post['itemIds'][$i] . ']'] = trim($post['size'][$post['itemIds'][$i]]);
	}
	$post['sizeType[' . $post['itemIds'][$i] . ']'] = trim($post['sizeType'][$post['itemIds'][$i]]);
	$post['itemNumber[' . $post['itemIds'][$i] . ']'] = trim($post['itemNumber'][$post['itemIds'][$i]]);

    $post['groupId[' . $post['itemIds'][$i] . ']'] = trim($post['groupId'][$post['itemIds'][$i]]);
    if (isset($post['limitNum'][$post['itemIds'][$i]])) { 
        $post['limitNum[' . $post['itemIds'][$i] . ']'] = trim($post['limitNum'][$post['itemIds'][$i]]);
    }
}
$notAllows = array('high', 'weight', 'bust', 'waist', 'hips', 'shoulder', 'sleeve', 'kitake', 'yukitake', 'inseam', 'length', 'tokMemo', 'tokFlg', 'errorId', 'searchStatus', 'itemIds', 'size', 'sizeType', 'itemNumber', 'groupId', 'limitNum');
$hiddenHtml = castHidden($post, $notAllows);
// 表示する値の成型 ここまで ++++++++++++++++++++++++++++++++++++++++++++++++++

/*
 * 変更する特寸情報を取得する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$post      => POST値
 * 戻り値：$result    => 変更する特寸情報
 *
 * create 2007/03/30 H.Osugi
 *
 */
function getTokData($dbConnect, $post) {

	// 初期化
	$returnDatas = $post;

	// 申請番号
	$requestNo = trim($post['requestNo']);

	// 変更する発注申請情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" tt.Height,";
	$sql .= 	" tt.Weight,";
	$sql .= 	" tt.Bust,";
	$sql .= 	" tt.Waist,";
	$sql .= 	" tt.Hips,";
	$sql .= 	" tt.Shoulder,";
	$sql .= 	" tt.Sleeve,";
	$sql .= 	" tt.Length,";
    $sql .=     " tt.Kitake,";
    $sql .=     " tt.Yukitake,";
    $sql .=     " tt.Inseam,";
	$sql .= 	" tor.TokNote";
	$sql .= " FROM";
	$sql .= 	" T_Tok tt";
	$sql .= " INNER JOIN";
	$sql .= 	" T_Order tor";
	$sql .= " ON";
	$sql .= 	" tt.OrderID = tor.OrderID";
	$sql .= " AND";
	$sql .= 	" tor.Del = " . DELETE_OFF;
	$sql .= " WHERE";
	$sql .= 	" tor.AppliNo = '" . db_Escape($requestNo) . "'";
	$sql .= " AND";
	$sql .= 	" tt.Del = " . DELETE_OFF;

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
	 	return $returnDatas;
	}

	$returnDatas['high']     = $result[0]['Height'];		// 身長
	$returnDatas['weight']   = $result[0]['Weight'];		// 体重
	$returnDatas['bust']     = $result[0]['Bust'];			// バスト
	$returnDatas['waist']    = $result[0]['Waist'];			// ウエスト
	$returnDatas['hips']     = $result[0]['Hips'];			// ヒップ
	$returnDatas['shoulder'] = $result[0]['Shoulder'];		// 肩幅
	$returnDatas['sleeve']   = $result[0]['Sleeve'];		// 袖丈
	$returnDatas['length']   = $result[0]['Length'];		// スカート丈
    $returnDatas['kitake']   = $result[0]['Kitake'];        // 着丈
    $returnDatas['yukitake']   = $result[0]['Yukitake'];      // 裄丈
    $returnDatas['inseam']   = $result[0]['Inseam'];        // 股下
	$returnDatas['tokMemo']  = $result[0]['TokNote'];		// 特寸備考

 	return $returnDatas;

}

?>
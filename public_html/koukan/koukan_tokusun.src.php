<?php
/*
 * 特寸入力画面
 * koukan_tokusun.src.php
 *
 * create 2007/04/03 H.Osugi
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

// 初期設定
$isMenuExchange = true;	// 交換のメニューをアクティブに

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

//var_dump($_POST);

$post = $_POST;

// 遷移先URLと戻り先URLの設定
switch($post['koukanShinseiFlg']) {
	case '1':		// 交換の場合
		break;
	default:
		$hidden = array();
		redirectPost('./koukan_top.php', $hidden);
		break;
}

// POST値をHTMLエンティティ
$post = castHtmlEntity($post); 

// 表示する値の成型 ここから +++++++++++++++++++++++++++++++++++++++++++++++++++
// 身長
$high = trim($post['high']);
unset($post['high']);
// 体重
$weight = trim($post['weight']);
unset($post['weight']);

// バスト
$bust = trim($post['bust']);
unset($post['bust']);

// ウエスト
$waist = trim($post['waist']);
unset($post['waist']);

// ヒップ
$hips = trim($post['hips']);
unset($post['hips']);

// 肩幅
$shoulder = trim($post['shoulder']);
unset($post['shoulder']);

// 袖丈
$sleeve = trim($post['sleeve']);
unset($post['sleeve']);

// スカート丈
$length = trim($post['length']);
unset($post['length']);

// 着丈
$kitake = trim($post['kitake']);
unset($post['kitake']);

// 裄丈
$yukitake = trim($post['yukitake']);
unset($post['yukitake']);

// 股下
$inseam = trim($post['inseam']);
unset($post['inseam']);

// 特寸備考
$tokMemo = trim($post['tokMemo']);
unset($post['tokMemo']);

// hidden値の成型
if ($post['appliReason'] == APPLI_REASON_EXCHANGE_CHANGEGRADE || $post['appliReason'] == APPLI_REASON_EXCHANGE_MATERNITY) { 
    $countItemIds = count($post['itemId']);

    for ($i=0; $i<$countItemIds; $i++) {
        $post['itemId[' . $i . ']'] = $post['itemId'][$i];
        $post['size[' . $post['itemId'][$i] . ']'] = trim($post['size'][$post['itemId'][$i]]);
    }

    $notArrowKeys = array('itemId' , 'size');
} else {
    $countOrderDetIds = count($post['orderDetIds']);
    for ($i=0; $i<$countOrderDetIds; $i++) {
        $post['orderDetIds[' . $i . ']'] = $post['orderDetIds'][$i];
        if (count(getSize($dbConnect, $post['sizeType'][$post['orderDetIds'][$i]], 1)) > 1) {  // フリーかどうか判定
            $post['size[' . $post['orderDetIds'][$i] . ']'] = trim($post['size'][$post['orderDetIds'][$i]]);
        }
        $post['sizeType[' . $post['orderDetIds'][$i] . ']'] = trim($post['sizeType'][$post['orderDetIds'][$i]]);
//var_dump( $post['sizeType[' . $post['orderDetIds'][$i] . ']']);
    }
    $notArrowKeys = array('orderDetIds' , 'size', 'sizeType', 'errorId');
}

$hiddenHtml = castHidden($post, $notArrowKeys);
// 表示する値の成型 ここまで ++++++++++++++++++++++++++++++++++++++++++++++++++

?>
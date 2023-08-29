<?php
/*
 * エラー表示画面
 * error.src.php
 *
 * create 2007/03/14 H.Osugi
 *
 *
 */

// 各種モジュールの取得
include_once('../include/define.php');				// 定数定義
require_once('../include/dbConnect.php');			// DB接続モジュール
require_once('../include/msSqlControl.php');		// DB操作モジュール
require_once('../include/checkLogin.php');			// ログイン判定モジュール
require_once('../include/castHidden.php');			// hidden値成型モジュール
require_once('../include/castHtmlEntity.php');		// HTMLエンティティモジュール
require_once('../error_message/errorMessage.php');	// エラーメッセージ
require_once('../include/redirectPost.php');		// リダイレクトポストモジュール

$errors = array();
$returnUrl = HOME_URL;
if (count($_POST) > 0) {

	$countError = count($_POST['errorId']);

	for ($i=0; $i<$countError; $i++) {
		// エラーメッセージのset
		$errors[$i]['errorMessage'] = ${trim($_POST['errorName'])}[$_POST['errorId'][$i]];
	}

	// メニューをアクティブに切替
	if (isset($_POST['menuName']) && $_POST['menuName'] != '') {
		${$_POST['menuName']} = true;
	}

	$returnUrl = HOME_URL . trim($_POST['returnUrl']);

	$notAllows = array();
	$notAllows[] = 'errorId';
	$notAllows[] = 'errorName';
	$notAllows[] = 'menuName';
	$notAllows[] = 'returnUrl';

	// POST値をHTMLエンティティ
	$post = castHtmlEntity($_POST); 

	// 2次元配列を成型
	if (is_array($post) && count($post) > 0) {
		foreach ($post as $key => $value) {
			if (is_array($value)) {
				foreach ($value as $key2 => $value2) {
					$post[$key . '[' . castHtmlEntity($key2) . ']'] = $value2;
					if (!in_array($key, $notAllows)) {
						$notAllows[] = $key;
					}
				}
			}
		}
	}
	
	$hiddenHtml = castHidden($post, $notAllows);

}

// エラーが無い場合はTOPへ遷移
if (count($errors) <= 0) {
	header('Location: ' . HOME_URL . 'top.php');
}

?>
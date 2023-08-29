<?php
/*
 * 発注メニュー画面
 * hachu_top.src.php
 *
 * create 2007/03/14 H.Osugi
 *
 *
 */

// 各種モジュールの取得
include_once('../../include/define.php');			// 定数定義
require_once('../../include/dbConnect.php');		// DB接続モジュール
require_once('../../include/msSqlControl.php');		// DB操作モジュール
require_once('../../include/checkLogin.php');		// ログイン判定モジュール
require_once('../../include/redirectPost.php');		// リダイレクトポストモジュール
require_once('../../include/castHtmlEntity.php');	// HTMLエンティティモジュール
require_once('../../include/commonFunc.php');           // 共通関数モジュール


// メニューの発注ボタンON
$isMenuOrder = true;

//var_dump($_POST);die;

$searchStaffId = $_POST['staffId'];

?>
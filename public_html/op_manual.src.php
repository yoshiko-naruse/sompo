<?php
/*
 * マニュアルダウンロード画面
 * op_manual.src.php
 *
 * create 2007/06/01 T.Uno
 *
 *
 */

// 各種モジュールの取得
include_once('../include/define.php');			// 定数定義
require_once('../include/dbConnect.php');		// DB接続モジュール
require_once('../include/msSqlControl.php');	// DB操作モジュール
require_once('../include/checkLogin.php');		// ログイン判定モジュール
require_once('../include/redirectPost.php');	// リダイレクトポストモジュール

// 初期設定
$isMenuManual = true;	// マニュアルのメニューをアクティブに

?>
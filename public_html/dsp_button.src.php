<?php
/*
 * 紛失 / 破損・汚損届表示画面（印刷ボタン）
 * dsp_button.src.php
 *
 * create 2007/04/26 H.Osugi
 *
 *
 */

// 各種モジュールの取得
include_once('../include/define.php');			// 定数定義
require_once('../include/dbConnect.php');		// DB接続モジュール
require_once('../include/msSqlControl.php');	// DB操作モジュール
require_once('../include/checkLogin.php');		// ログイン判定モジュール
require_once('../include/redirectPost.php');	// リダイレクトポストモジュール

?>
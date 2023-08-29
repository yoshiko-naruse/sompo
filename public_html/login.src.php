<?php
/*
 * ログイン画面
 * login.src.php
 *
 * create 2007/03/13 H.Osugi
 *
 *
 */

// 各種モジュールの取得
include_once('../include/define.php');				// 定数定義
require_once('../include/dbConnect.php');			// DB接続モジュール
require_once('../include/msSqlControl.php');		// DB操作モジュール
require_once('../include/checkData.php');			// 対象文字列検証ジュール
require_once('../include/redirectPost.php');		// リダイレクトポストモジュール
require_once('../error_message/indexError.php');	// エラーメッセージ
require_once('./login.val.php');					// エラー判定モジュール

// 初期値の設定
$isLogin = false;		// 未ログイン状態
$homeUrl = HOME_URL;	// サイトトップのURL

// SESSIONの開始
session_start();
session_cache_limiter('private');

// SESSION情報を持っていたらSESSIONを開放
if (isset($_SESSION['NAMECODE']) || isset($_SESSION['PASSWORD'])) {

	// SESSION変数をすべて解除
	$_SESSION = array();

	// クライアントのCOOKIEの値も削除
	setcookie("PHPSESSID", '', time() - 3600, '/');
	setcookie("userId", '', time() - 3600, '/');
	setcookie("pass", '', time() - 3600, '/');

	// SESSIONの破棄
	session_destroy();

}

// ログイン判定を行う
if (isset($_POST['loginFlg'])) {

	// エラー判定処理（エラーが無ければSESSIONにログイン情報を保存する）
	$isError = validatePostData($dbConnect, $_POST);

	// エラーが無かった場合はTOP画面へ遷移
	if ($isError == false) {
		// 初期パスワードの場合は、パスワード変更画面へ遷移
		// 09/04/08 uesugi
		if($_SESSION['PASSWORD'] == SYSTEM_DEFAULT_PASSWORD){
			$_SESSION['FROM_LOGIN'] = "1";
			redirectPost("./change_password.php", "");
		}
		// パスワード更新日をチェック
		// 09/04/06 uesugi
		if(!checkChangePassDay($dbConnect)){
			$_SESSION['FROM_LOGIN'] = "1";
			redirectPost("./change_password.php", "");
		}

		header('Location: ' . './top.php');
		exit;
	}

	// エラーメッセージ
	$errorMessage = $indexErrors['001'];

}

if (isset($_GET['changePass']) && $_GET['changePass'] == COMMON_FLAG_ON) {
    // 画面にメッセージを表示
    $isError = true;
    $errorMessage = $indexErrors['002'];

}
/*
 * パスワード更新日をチェック
 * 引数  ：更新日以上の日数経過 false 更新日以内 true
 * 戻り値：なし
 */
function checkChangePassDay($dbConnect) {
	// 期限が設定されていない場合は無期限
	$exp_days = CHANGE_PASS_EXPDAY;
	if(!isset($exp_days) || $exp_days =="" || !is_numeric($exp_days)){
		return true;
	}else{
		// 現在の日付より、期限開始日を求める
		$exp_startday = mktime (0, 0, 0, date("m"), date("d"),  date("y")) - 86400 * $exp_days;
		$exp_startday = date('y/m/d', $exp_startday);

		// 期限開始日より前に更新されているかをチェック
		$sql  = "";
		$sql .= " SELECT";
		$sql .= 	" count(*) As OrderCount";
		$sql .= " FROM";
		$sql .= 	" M_User";
		$sql .= " WHERE";
		$sql .= 	" convert(binary(21), rtrim(NameCd)) = convert(binary(21), '" . db_Escape($_SESSION['NAMECODE']) . "')";
		$sql .= " AND";
		$sql .=	" (PassWdUpdDay IS Null OR CONVERT(char, PassWdUpdDay, 11) < '" . db_Escape($exp_startday) . "')";
		$sql .= " AND";
		$sql .= 	" Del = " . DELETE_OFF;
		$result = db_Read($dbConnect, $sql);
//var_dump($sql);die;
		if (!isset($result[0]['OrderCount']) || $result[0]['OrderCount'] >= 1) {
			return false;
		}else{
			return true;
		}
	}
}
?>
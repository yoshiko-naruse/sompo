<?php
/*
 * 履歴CSV出力
 * rireki_csv_dl.src.php
 *
 * create 2007/05/09 H.Osugi
 *
 *
 */

// 出力する際の文字コードを設定
mb_internal_encoding('SJIS');
mb_http_output('SJIS');
ob_start('mb_output_handler');

// 各種モジュールの取得
include_once('../../include/define.php');			// 定数定義
require_once('../../include/dbConnect.php');		// DB接続モジュール
require_once('../../include/msSqlControl.php');		// DB操作モジュール
require_once('../../include/checkLogin.php');		// ログイン判定モジュール
require_once('../../include/redirectPost.php');		// リダイレクトポストモジュール

// 制限時間の解除
set_time_limit(0);

// 出力するユーザー一覧を取得
$outputDatas = getUserData($dbConnect);

// ヘッダの生成
header('Cache-Control: public');
header('Pragma: public');
header('Content-Disposition: attachment; filename=' . mb_convert_encoding(USER_RTN_CSV_FILE_NAME . '.csv', 'SJIS', 'auto'));
header('Content-type: text/comma-separated-values');

// 項目名
$header  = 'ログインID,';
$header .= 'パスワード,';
$header .= 'ユーザー権限,';
$header .= '管理者権限,';
$header .= '氏名,';
$header .= '店舗コード,';
$header .= '店舗名,';
$header .= '最終更新日' . "\n";

print(mb_convert_encoding($header, 'SJIS', 'auto'));

$countDatas = count($outputDatas);
for ($i=0; $i<$countDatas; $i++) {

	print('="' . $outputDatas[$i]['NameCd'] . '",');					// ログインID
	print('="' . $outputDatas[$i]['PassWd'] . '",');					// パスワード
	print('="' . $outputDatas[$i]['UserLvl'] . '",');				// ユーザーレベル
	print('="' . $outputDatas[$i]['AdminLvl'] . '",');				// 管理者レベル
	print($outputDatas[$i]['Name'] . ',');							// 氏名
    print('="' . $outputDatas[$i]['CompCd'] . '",');                      // 店舗名
	print($outputDatas[$i]['CompName'] . ',');		                // 店舗名
	print($outputDatas[$i]['UpdDay'] . "\n");		                // 最終更新日

}

/*
 * 着用状況一覧情報を取得する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$post           => POST値
 * 戻り値：$result         => 着用状況一覧情報
 *
 * create 2007/05/09 H.Osugi
 *
 */
function getUserData($dbConnect) {

	// 初期化
	$compId    = '';
	$appliNo      = '';
	$appliDayFrom = '';
	$appliDayTo   = '';
	$shipDayFrom  = '';
	$shipDayTo    = '';
	$staffCode = '';
	$barCode   = '';
	$status    = '';
	$offset    = '';

	// 履歴の一覧を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" mus.NameCd,";
	$sql .= 	" mus.PassWd,";
	$sql .= 	" mus.UserLvl,";
	$sql .= 	" mus.AdminLvl,";
	$sql .= 	" mus.Name,";
    $sql .=     " mco.CompCd,";
	$sql .= 	" mco.CompName,";
	$sql .= 	" mus.UpdDay";
	$sql .= " FROM";
	$sql .= 	" M_user mus";
	$sql .= " INNER JOIN";
	$sql .= 	" M_Comp mco";
	$sql .= " ON";
	$sql .= 	" mus.CompID = mco.CompID";
	$sql .= " AND";
	$sql .= 	" mus.Del= " . DELETE_OFF;
    $sql .= " AND";
    $sql .=     " mco.Del= " . DELETE_OFF;

	$result = db_Read_Csv($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	return  $result;

}


?>
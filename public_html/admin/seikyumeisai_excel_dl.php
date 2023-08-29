<?php
/*
 * エクセル出力
 * common_excel_dl.src.php
 *
 * create 2013/04/18 T.Uno
 *
 *
 */

//mb_internal_encoding('UTF-8');
//mb_http_output('UTF-8');

// 各種モジュールの取得
include_once('../../include/define.php');			// 定数定義
/* ../../include/dbConnect.php start */

/*
 * DB接続モジュール
 * dbConnect.php
 *
 * create 2007/03/12 H.Osugi
 *
 */

//接続情報を指定
$connectionInfo = array("UID"=>DB_USER_NAME,
                        "PWD"=>DB_PASSWORD,
                        "Database"=>DB_DATABASE_NAME,
                        "CharacterSet"=>DB_CHARSET);

// DB接続
$dbConnect = sqlsrv_connect(DB_SERVER_NAME, $connectionInfo);

if ($dbConnect == false) {

	// エラー画面で必要な値のセット
	$hiddens = array();
	$hiddens['errorName'] = 'connectFailed';
	$hiddens['returnUrl'] = 'index.html';
	$hiddens['errorId'][] = '001';

	// エラー画面へ遷移
	redirectPost(HOME_URL . 'db_error.php', $hiddens);

	exit;
}


/* ../../include/dbConnect.php end */


/* ../../include/msSqlControl.php start */

/*
 * DB操作モジュール（MS SQL）
 * msSqlControl.php
 *
 * create 2007/03/12 H.Osugi
 * update 2007/03/15 H.Osugi	トランザクション処理モジュールの追加
 *
 */

/*
 * DB読み込み
 *
 * 引数  ：$dbConnect => DBコネクション
 *       ：$sql       => 実行するSQL
 * 戻り値：$fetches   => SQLの実行結果(array)
 *
 * create 2007/03/12 H.Osugi
 *
 */
function db_Read($dbConnect, $sql) {

	// 初期化
	$fetches = array();

	// SQLの実行
	$result = sqlsrv_query($dbConnect, $sql);

	if ($result == false) {
		return $fetches;
	}

	$i = 0;
	while($rows = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
		$fetches[$i] = $rows;
		if (count($fetches[$i]) > 0) {
			foreach ($fetches[$i] as $key => $value) {
				if (!is_object($value)) {
					$fetches[$i][$key] = $value;
				} else {
					$fetches[$i][$key] = $value->format('Y-m-d H:i:s');;
				}
			}
		}
		$i++;
	}

	return $fetches;

}

/*
 * DB読み込み（CSV出力用） 
 *
 * 引数  ：$dbConnect => DBコネクション
 *       ：$sql       => 実行するSQL
 * 戻り値：$fetches   => SQLの実行結果(array)
 *
 * create 2007/05/08 H.Osugi
 *
 */
function db_Read_Csv($dbConnect, $sql) {

	// 初期化
	$fetches = array();

	// SQLの実行
	$result = sqlsrv_query($dbConnect, $sql);

	if ($result == false) {
		return $fetches;
	}

	$i = 0;
	while($rows = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
		$fetches[$i] = $rows;
		if (count($fetches[$i]) > 0) {
			foreach ($fetches[$i] as $key => $value) {
				if (!is_object($value)) {
					$fetches[$i][$key] = mb_convert_encoding($value, 'sjis-win', 'UTF-8');
				} else {
					$fetches[$i][$key] = $value->format('Y-m-d H:i:s');;
				}
			}
		}
		$i++;
	}

	return $fetches;

}

/*
 * DB 書き込み
 *
 * 引数  ：$dbConnect => DBコネクション
 *       ：$sql       => 実行するSQL
 * 戻り値：true： 成功 / false：失敗
 *
 * create 2007/03/12 H.Osugi
 *
 */
function db_Execute($dbConnect, $sql) {

	// SQLの実行
	$result = sqlsrv_query($dbConnect, $sql);

	if ($result == false) {
		return false;
	}

	return true;

}

/*
 * SQLエスケープ
 *
 * 引数  ：$string => 対象文字列
 * 戻り値：$escapeString => エスケープ後の文字列
 *
 * create 2007/03/12 H.Osugi
 *
 */
function db_Escape($string) {

    // SQLの実行
    $escapeString = '';
    if ($string != '')  {
        $escapeString = mb_ereg_replace("'","''",  $string);
    }
    return $escapeString;

}

/*
 * SQLワイルドカードエスケープ
 *
 * 引数  ：$string => 対象文字列
 * 戻り値：$escapeString => エスケープ後の文字列
 *
 * create 2007/04/11 H.Osugi
 *
 */
function db_Like_Escape($string) {

	// SQLの実行
	$escapeString = mb_ereg_replace("'","''",  $string);
	$escapeString = mb_ereg_replace("%","[%]",  $escapeString);
	$escapeString = mb_ereg_replace("_","[_]",  $escapeString);

	return $escapeString;

}

/*
 * トランザクション処理（開始）
 *
 * 引数  ：$dbConnect => コネクションハンドラ
 * 戻り値：なし
 *
 * create 2007/03/15 H.Osugi
 *
 */
function db_Transaction_Begin($dbConnect) {

	// トランザクション開始
	sqlsrv_begin_transaction($dbConnect);

}

/*
 * トランザクション処理（ロールバック）
 *
 * 引数  ：$dbConnect => コネクションハンドラ
 * 戻り値：なし
 *
 * create 2007/03/15 H.Osugi
 *
 */
function db_Transaction_Rollback($dbConnect) {

	// ロールバック
	sqlsrv_rollback($dbConnect);

}

/*
 * トランザクション処理（コミット）
 *
 * 引数  ：$dbConnect => コネクションハンドラ
 * 戻り値：なし
 *
 * create 2007/03/15 H.Osugi
 *
 */
function db_Transaction_Commit($dbConnect) {

	// コミット
	sqlsrv_commit($dbConnect);

}

/*
 * DBのクローズ
 *
 * 引数  ：$dbConnect => コネクションハンドラ
 * 戻り値：なし
 *
 * create 2007/03/15 H.Osugi
 *
 */
function db_Close($dbConnect) {

	sqlsrv_close($dbConnect);

}


/* ../../include/msSqlControl.php end */


/* ../../include/checkLogin.php start */

/*
 * ログイン判定モジュール
 * checkLogin.php
 *
 * create 2007/03/12 H.Osugi
 *
 */

// SESSION開始
session_cache_limiter('none');
session_start();

//$old_sessionid = session_id();

session_regenerate_id();

//$new_sessionid = session_id();

//echo "古いセッション: $old_sessionid<br />";
//echo "新しいセッション: $new_sessionid<br />";

// ユーザコードが無ければログイン画面に遷移
if (!isset($_SESSION['NAMECODE']) || $_SESSION['NAMECODE'] == '') {
	moveOverLogin();
}

// パスワードが無ければログイン画面に遷移
if (!isset($_SESSION['PASSWORD']) || $_SESSION['PASSWORD'] == '') {
	moveOverLogin();
}

// ユーザコードが無ければログイン画面に遷移
if (!isset($_COOKIE['userId']) || $_COOKIE['userId'] == '') {
	moveOverLogin();
}

// ユーザコードが無ければログイン画面に遷移
if (!isset($_COOKIE['pass']) || $_COOKIE['pass'] == '') {
	moveOverLogin();
}

// SESSION情報とCOOKIE情報が異なればログイン画面に遷移
if ($_SESSION['NAMECODE'] != $_COOKIE['userId'] || md5($_SESSION['PASSWORD']) != $_COOKIE['pass']) {
	moveOverLogin();
}

// ログイン時に保持しているユーザ情報とDBの情報を比較
$sql  = "";
$sql .= " SELECT";
$sql .= 	" count(*) as user_count";
$sql .= " FROM";
$sql .= 	" M_User";
$sql .= " WHERE";
$sql .= 	" convert(binary(21), rtrim(NameCd)) = convert(binary(21), '" . db_Escape($_SESSION['NAMECODE']) . "')";
$sql .= " AND";
$sql .= 	" convert(binary(21), rtrim(PassWd)) = convert(binary(21), '" . db_Escape($_SESSION['PASSWORD']) . "')";
$sql .= " AND";
$sql .= 	" Del = " . DELETE_OFF;

$result = db_Read($dbConnect, $sql);

// 該当するユーザが存在しない場合はログイン画面へ遷移
if (!isset($result[0]['user_count']) || $result[0]['user_count'] <= 0) {
	moveOverLogin();
}

// メニュー表示部分で必要な変数の初期化
$isLogin         = true;		// ログイン状況

// メニューボタンの色判定
$isMenuOrder       = false;		// 発注
$isMenuExchange    = false;		// 交換
$isMenuReturn      = false;		// 返却
$isMenuCondition   = false;		// 着用状況
$isMenuHistory     = false;		// 申請履歴
$isMenuVoucher     = false;		// 着払い伝票依頼
$isMenuIdou        = false;		// 異動
$isMenuQandA       = false;		// Ｑ＆Ａ
$isMenuManual      = false;		// マニュアル
$isMenuAcceptation = false;		// 承認
$isMenuAdmin       = false;		// 着用者情報
$isMenuStock       = false;		// 在庫照会
$isMenuCleaning    = false;     // 洗濯

$isPermitStock     = false;		// 在庫照会閲覧権限 4

// ユーザー権限
//$isLevelAgency      = false;		// 一次代理店 5
$isLevelAdmin       = false;		// 権限（管理権限）3
$isLevelNormal      = false;		// 権限（通常権限）1

$isLevelItc         = false;		// 権限（管理権限）2
$isLevelHonbu       = false;		// 権限（本部権限）1
$isLevelSyonin      = false;		// 権限（承認権限）0

// 特殊店舗フラグ
$isExceptionalShop  = false;        // 特殊店舗

$homeUrl         = HOME_URL;			// サイトトップのURL
$manualUrl       = MANUAL_URL;			// マニュアルのURL
$sizeUrl         = SIZE_URL;			// サイズ表のURL

$userCd          = db_Escape($_SESSION['NAMECODE']);
$userNm          = db_Escape($_SESSION['USERNAME']);

// 権限判定
switch ($_SESSION['USERLVL']) {
//	case '5':		// 一次代理店
//		$isLevelAgency      = true;
//		break;
//	case '4':		// 管理者権限 + 在庫照会権限
//		$isPermitStock      = true;
//		$isLevelAdmin       = true;
//		break;
	case USER_AUTH_LEVEL_ADMIN:		// 管理者権限 + 在庫照会権限 3
		$isPermitStock      = true;	
		$isLevelAdmin       = true;
		break;
	default:		// 通常権限
		$isLevelNormal      = true;
		break;
}

// 管理者権限判定
if ($isLevelAdmin && $_SESSION['ADMINLVL'] == USER_AUTH_LEVEL_ITC) {
		$isLevelItc         = true;
}

// 本部権限判定
if ($isLevelAdmin && $_SESSION['ADMINLVL'] == USER_AUTH_LEVEL_HONBU) {
		$isLevelHonbu       = true;
}

// 本部権限判定
if ($isLevelAdmin && $_SESSION['ADMINLVL'] == USER_AUTH_LEVEL_SYONIN) {
		$isLevelSyonin      = true;
}

// 特殊店舗判定
switch ($_SESSION['SHOPFLAG']) {
    case EXCEPTIONALSHOP_EXCEPTIONAL:   // 特殊店舗
        $isExceptionalShop  = true;
        break;        

    case EXCEPTIONALSHOP_GENERAL:       // 通常店舗
    default:
        $isExceptionalShop  = false;
        break;        
}


/*
 * ログイン画面に遷移する
 * 引数  ：なし
 * 戻り値：なし
 */
function moveOverLogin() {
	header('Location: ' . HOME_URL . 'login.php');
	exit;
}


/* ../../include/checkLogin.php end */


/* ../../include/redirectPost.php start */

/*
 * リダイレクト時にhiddenで値を受け渡す
 * redirectPost.php
 *
 * create 2007/03/14 H.Osugi
 *
 */

/*
 * リダイレクト時にhiddenで値を受け渡す
 * 引数  ：$action     => 遷移先のパス
 *       ：$hiddens    => リダイレクト時に送信したい値(array)
 *       ：$formName   => フォーム名
 * 戻り値：なし
 *
 * create 2007/03/14 H.Osugi
 *
 */
function redirectPost($action, $hiddens, $formName = 'redirectForm') {

	// hiddenの生成（2次元配列まで対応）
	$hiddensHtml = '';
	//$hiddens = castHtmlEntity($hiddens);
	if (is_array($hiddens) && count($hiddens) > 0) {
		foreach ($hiddens as $key => $value) {
			if (is_array($value)) {
				foreach ($value as $key2 => $value2) {
					$hiddensHtml .= '<input type="hidden" name="' . $key . '[' . $key2 . ']" value="' . $value2 . '">' . "\n";
				}
			}
			else {
				$hiddensHtml .= '<input type="hidden" name="' . $key . '" value="' . $value . '">' . "\n";
			}
		}
	}

	$html  = '';
	$html .= '<html>' . "\n";
	$html .= '<head>' . "\n";
	$html .= '<META http-equiv="Content-Type" content="text/html; charset=UTF-8">' . "\n";
	$html .= '</head>' . "\n";
	$html .= '<body>' . "\n";
	$html .= '<form action="' . $action . '" name="' . $formName . '" method="post">' . "\n";
	$html .= $hiddensHtml;
	$html .= '</form>' . "\n";
	$html .= '<script language="javascript">document.' . $formName . '.submit();</script>' . "\n";
	$html .= '</body>' . "\n";
	$html .= '</html>' . "\n";

	echo $html;
	exit;

}


/* ../../include/redirectPost.php end */


include_once('../../include/castHidden.php');       // hidden値成型モジュール
/* ../../include/castHtmlEntity.php start */

/*
 * HTMLエンティティモジュール
 * castHtmlEntity.php
 *
 * create 2007/03/16 H.Osugi
 *
 */

/*
 * 与えられた値全てにHTMLエンティティ処理を行う
 * 引数  ：$strings         => HTMLエンティティを行いたい文字列（配列でも処理可能）
 * 戻り値：$entitiedStrings => HTMLエンティティ後の文字列（もしくは配列）
 *
 * create 2007/03/16 H.Osugi
 *
 */
function castHtmlEntity($strings) {

	if (is_array($strings)) {
		$entitiedStrings = array_map('castHtmlEntity', $strings);
	}
 	else {
		$entitiedStrings = htmlspecialchars($strings, HTMLENTITY_QUOTE_STYLE, HTMLENTITY_ENCODE);
	}

	return $entitiedStrings;

}


/* ../../include/castHtmlEntity.php end */


//// REQUIRE_ONCE('../../error_message/errorMessage.php');		// エラーメッセージ

include_once('../../include/myExcel/PHPExcel.php');
include_once('../../include/myExcel/PHPExcel/Writer/Excel5.php');
include_once('../../include/myExcel/PHPExcel/Writer/Excel2007.php');
include_once('../../include/myExcel/PHPExcel/IOFactory.php');

// 制限時間の解除
set_time_limit(0);

// 管理権限でなければトップに
if ($isLevelAdmin == false) {

	$returnUrl = HOME_URL . 'top.php';
	
	// TOP画面に強制遷移
	redirectPost($returnUrl, $hiddens);

}

// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST); 

$inputFromDay = $post['inputFromDay'];	      // 集計開始日
$inputToDay   = $post['inputToDay'];	      // 集計終了日

// 検索条件チェック
// 集計開始日、集計終了日（必須）
if (trim($inputFromDay) == '' || trim($inputToDay) == '') {
    $hiddens['errorName'] = 'seikyuMeisai';
    $hiddens['menuName']  = 'isMenuKanri';
    $hiddens['returnUrl'] = 'admin/seikyumeisai_download.php';
    $hiddens['errorId'][] = '002';
    $errorUrl             = HOME_URL . 'error.php';

    $hiddenHtml = castHiddenError($post);
    $hiddens = array_merge($hiddens, $hiddenHtml);

    // エラー画面に強制遷移
    redirectPost($errorUrl, $hiddens);
}

// YYYY/MM/DD以外の文字列であればエラー
if ( ($inputFromDay != '' && !ereg('^[0-9]{4}/[0-9]{2}/[0-9]{2}$', $inputFromDay))
 || ($inputToDay != '' && !ereg('^[0-9]{4}/[0-9]{2}/[0-9]{2}$', $inputToDay)) ) {
    $hiddens['errorName'] = 'seikyuMeisai';
    $hiddens['menuName']  = 'isMenuKanri';
    $hiddens['returnUrl'] = 'admin/seikyumeisai_download.php';
    $hiddens['errorId'][] = '003';
    $errorUrl             = HOME_URL . 'error.php';

    $hiddenHtml = castHiddenError($post);
    $hiddens = array_merge($hiddens, $hiddenHtml);

    // エラー画面に強制遷移
    redirectPost($errorUrl, $hiddens);
}
//$time_start = microtime(true);    // 出力時間計

// 出力する申請ヘッダ一覧を取得
$outputDatas = getSeikyuData($dbConnect, $_POST);
if (!$outputDatas) {
    $hiddens['errorName'] = 'seikyuMeisai';
    $hiddens['menuName']  = 'isMenuKanri';
    $hiddens['returnUrl'] = 'admin/seikyumeisai_download.php';
    $hiddens['errorId'][] = '005';
    $errorUrl             = HOME_URL . 'error.php';

    $hiddenHtml = castHiddenError($post);
    $hiddens = array_merge($hiddens, $hiddenHtml);

    // エラー画面に強制遷移
    redirectPost($errorUrl, $hiddens);
}

$xlsReader = PHPExcel_IOFactory::createReader('Excel2007');

// エクセルファイルをオープン
$xlsObject = $xlsReader->load("./temp/template_seikyumeisai.xlsx");

//var_dump("aaaaa2");die;
//アクティブなシートを変数に格納
$xlsObject->setActiveSheetIndex(0);
$worksheet = $xlsObject->getActiveSheet();

$lineoffset = 3;

//$honbuWk     = '';     // ブレイク処理用
//$startColumn = '';     // 本部単位での開始カラム ⇒本部ごとの貸与者種類合計にて使用
//$endColumn   = '';     // 本部単位での終了カラム ⇒本部ごとの貸与者種類合計にて使用

$outputCnt = count($outputDatas);

$fromDate = new DateTime($inputFromDay);
$toDate   = new DateTime($inputToDay);

// 請求資料タイトル
$title = "";
$title = "SOMPOケアフーズ様ユニフォーム請求明細（" . $fromDate->format('Y.m.d') . "～" . $toDate->format('Y.m.d') . "）";

// 請求資料シート名
$sheettitle = "請求明細";

// 出力ファイル名
$outFileName = "";
$outFileName = "請求明細【" . $fromDate->format('Y.m.d') . "～" . $toDate->format('Y.m.d') . "】";
$outFileName = mb_convert_encoding($outFileName, 'sjis-win', 'auto');

// タイトル作成（日付付き）
$worksheet->setCellValueExplicit("A1", mb_convert_encoding($title, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
$worksheet->setTitle(mb_convert_encoding($sheettitle, 'UTF-8', 'auto'));

for ($i = 0; $i < $outputCnt; $i++) {

	// 申請日
	$worksheet->setCellValueExplicit("A".($lineoffset), mb_convert_encoding($outputDatas[$i]['AppliDay'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
	// 申請番号
	$worksheet->setCellValueExplicit("B".($lineoffset), mb_convert_encoding($outputDatas[$i]['AppliNo'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
	// 施設コード
	$worksheet->setCellValueExplicit("C".($lineoffset), mb_convert_encoding($outputDatas[$i]['CompCd'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
	// 施設名
	$worksheet->setCellValueExplicit("D".($lineoffset), mb_convert_encoding($outputDatas[$i]['CompName'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
	// 職員コード
	$worksheet->setCellValueExplicit("E".($lineoffset), mb_convert_encoding($outputDatas[$i]['StaffCode'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
	// 職員氏名
	$worksheet->setCellValueExplicit("F".($lineoffset), mb_convert_encoding($outputDatas[$i]['PersonName'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);

	// 区分
	switch ($outputDatas[$i]['AppliMode']) {
		case APPLI_MODE_ORDER:						// 発注
			$appliMode = mb_convert_encoding('発注', 'UTF-8', 'auto');
			break;
		case APPLI_MODE_EXCHANGE:					// 交換
			$appliMode = mb_convert_encoding('交換', 'UTF-8', 'auto');
			break;
		default:
			$appliMode = mb_convert_encoding('', 'UTF-8', 'auto');
			break;
	}
	$worksheet->setCellValueExplicit("G".($lineoffset), $appliMode, PHPExcel_Cell_DataType::TYPE_STRING);

	// 区分詳細
	switch ($outputDatas[$i]['AppliMode']) {
		case APPLI_REASON_ORDER_BASE:				// 発注（そんぽの家系／ラヴィーレ系）
			$appliReason = mb_convert_encoding('そんぽの家系／ラヴィーレ系', 'UTF-8', 'auto');
			break;
		case APPLI_REASON_ORDER_GRADEUP:			// 発注（グレードアップタイ）
			$appliReason = mb_convert_encoding('グレードアップタイ', 'UTF-8', 'auto');
			break;
		case APPLI_REASON_ORDER_FRESHMAN:			// 発注（新入社員）
			$appliReason = mb_convert_encoding('新入社員', 'UTF-8', 'auto');
			break;
		case APPLI_REASON_ORDER_PERSONAL:			// 発注（個別発注申請）
			$appliReason = mb_convert_encoding('個別発注申請', 'UTF-8', 'auto');
			break;
		case APPLI_REASON_EXCHANGE_FIRST:			// 交換（初回サイズ交換）
			$appliReason = mb_convert_encoding('初回サイズ交換', 'UTF-8', 'auto');
			break;
		case APPLI_REASON_EXCHANGE_SIZE:			// 交換（サイズ交換）
			$appliReason = mb_convert_encoding('サイズ交換', 'UTF-8', 'auto');
			break;
		case APPLI_REASON_EXCHANGE_INFERIORITY:		// 交換（不良失品交換）
			$appliReason = mb_convert_encoding('不良失品交換', 'UTF-8', 'auto');
			break;
		case APPLI_REASON_EXCHANGE_LOSS:			// 交換（紛交換）
			$appliReason = mb_convert_encoding('紛交換', 'UTF-8', 'auto');
			break;
		case APPLI_REASON_EXCHANGE_BREAK:			// 交換（汚損・破損交換）
			$appliReason = mb_convert_encoding('汚損・破損交換', 'UTF-8', 'auto');
			break;
		default:
			$appliReason = mb_convert_encoding('', 'UTF-8', 'auto');
			break;
	}
	$worksheet->setCellValueExplicit("H".($lineoffset), $appliReason, PHPExcel_Cell_DataType::TYPE_STRING);

	// 出荷日
	$worksheet->setCellValueExplicit("I".($lineoffset), mb_convert_encoding($outputDatas[$i]['ShipDay'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
	// アイテム名
	$worksheet->setCellValueExplicit("J".($lineoffset), mb_convert_encoding($outputDatas[$i]['ItemName'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
	// サイズ
	$worksheet->setCellValueExplicit("K".($lineoffset), mb_convert_encoding($outputDatas[$i]['Size'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);

	// リユース区分
	switch ($outputDatas[$i]['NewOldOrderKbn']) {
		case 0:				// 新品
			$newOldKbn = mb_convert_encoding('新品', 'UTF-8', 'auto');
			$price = $outputDatas[$i]['Price'];		// 新品単価
			break;
		case 1:				// リユース品
			$newOldKbn = mb_convert_encoding('リユース品', 'UTF-8', 'auto');
			$price = $outputDatas[$i]['OldPrice'];	// リユース品単価
			break;
	}
	$worksheet->setCellValueExplicit("L".($lineoffset), $newOldKbn, PHPExcel_Cell_DataType::TYPE_STRING);

	// 単価
	$worksheet->setCellValueExplicit("M".($lineoffset), mb_convert_encoding($price, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
	$worksheet->getStyle("H".($lineoffset))->getNumberFormat()->setFormatCode('#,##0');
	// 単品番号
	$worksheet->setCellValueExplicit("N".($lineoffset), mb_convert_encoding($outputDatas[$i]['BarCd'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);

	$lineoffset = $lineoffset + 1;

	// 改ページ
//	$worksheet->setBreak("A".($lineoffset - 1), PHPExcel_Worksheet::BREAK_ROW);
}

// 書式設定
setRowFormat(0, 13, $lineoffset-1, '', 'thin', '', '', '', $worksheet);

// 最終合計行以降を削除とする。
//$lineoffset = $lineoffset+1;

//$worksheet->removeRow($lineoffset, 10000);   // 最下位行指定

//印刷範囲
$worksheet->getPageSetup()->setPrintArea("A1:" . "N".($lineoffset-1) );

$xlsWriter = PHPExcel_IOFactory::createWriter($xlsObject, 'Excel5');

//die;
header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Content-Type: application/force-download");
header("Content-Type: application/octet-stream");
header("Content-Type: application/download");
header("Content-Disposition: attachment;filename=" . $outFileName . ".xls");
header("Content-Transfer-Encoding: binary");
$xlsWriter->save('php://output');

/*
 * セル行の書式設定
 * 引数  ：$max         => 列数max値
 *       ：$column      => 対象列数
 *       ：$lineoffset  => 対象行数
 *       ：$top         => 上の線
 *       ：$bottom      => 下の線
 *       ：$left        => 左の線
 *       ：$right       => 右の線
 *       ：$color       => セル色
 * medium ⇒太字
 * thin   ⇒細線
 * double ⇒2重線
 */
function setRowFormat($min='', $max='', $lineoffset, $top='', $bottom='', $left='', $right='', $color='', $worksheet) {

	for ($column=$min; $column<=$max ;$column++) {

		// セル（上）に罫線を引く 
		if (isset($top) && $top != '') {
			$worksheet->getStyleByColumnAndRow($column, $lineoffset)->
			    getBorders()->getTop()->setBorderStyle($top);
		}
		// セル（下）に罫線を引く 
		if (isset($bottom) && $bottom != '') {
			$worksheet->getStyleByColumnAndRow($column, $lineoffset)->
			    getBorders()->getBottom()->setBorderStyle($bottom);
		}
		// セル（左）に罫線を引く
		if (isset($left) && $left != '') {
			$worksheet->getStyleByColumnAndRow($column, $lineoffset)->
			    getBorders()->getLeft()->setBorderStyle($left);
		}
		// セル（右）に罫線を引く
		if (isset($right) && $right != '') {
			$worksheet->getStyleByColumnAndRow($column, $lineoffset)->
			    getBorders()->getRight()->setBorderStyle($right);
		}
		// セルを（任意の）色で塗りつぶす
		if (isset($color) && $color != '') {
			$worksheet->getStyleByColumnAndRow($column, $lineoffset)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB($color);
		}
	}
}

/*
 * 貸与簿一覧情報を取得する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$post           => POST値
 * 戻り値：$result         => 注文履歴一覧情報
 *
 * create 2013/04/17 T.Uno
 *
 */
function getSeikyuData($dbConnect, $post) {
	global $isLevelAgency;

	$result = array();
	$details = array();

	$sql .= " SELECT";
	$sql .= 	" CONVERT(VARCHAR, tor.AppliDay, 111) AS AppliDay,";
	$sql .= 	" tor.AppliNo,";
	$sql .= 	" mcp.CompCd,";
	$sql .= 	" mcp.CompName,";
	$sql .= 	" tor.StaffCode,";
	$sql .= 	" tor.PersonName,";
	$sql .= 	" tor.AppliMode,";
	$sql .= 	" tor.AppliReason,";
	$sql .= 	" CONVERT(VARCHAR, tod.ShipDay, 111) AS ShipDay,";
	$sql .= 	" mit.ItemName,";
	$sql .= 	" tod.Size,";
	$sql .= 	" tod.NewOldOrderKbn,";
	$sql .= 	" tod.Size,";
	$sql .= 	" mit.Price,";
	$sql .= 	" mit.OldPrice,";
	$sql .= 	" tod.BarCd";

	$sql .= " FROM T_Order_Details tod";
	$sql .= " INNER JOIN T_Order tor";
	$sql .= 	" ON  tod.OrderID = tor.OrderID";
	$sql .= 	" AND tor.Del = " . DELETE_OFF;
	$sql .= " INNER JOIN M_Comp mcp";
	$sql .= 	" ON  tor.CompID = mcp.CompID";
	$sql .= 	" AND mcp.Del = " . DELETE_OFF;
	$sql .= " INNER JOIN M_Item mit";
	$sql .= 	" ON  tod.ItemID = mit.ItemID";
	$sql .= 	" AND mit.Del = " . DELETE_OFF;

	$sql .= " WHERE";
	$sql .= 	" tod.Del = " . DELETE_OFF;
	$sql .= " AND";
	// 【出荷日】
	$sql .= 	" (";
	$sql .= 		" CONVERT(char, tod.ShipDay, 111) >= '" . db_Escape($post['inputFromDay']) . "'";
	$sql .= 		" AND";
	$sql .= 		" CONVERT(char, tod.ShipDay, 111) <= '" . db_Escape($post['inputToDay']) . "'";
	$sql .= 	" )";
	$sql .= " AND";
	// 【ステータス】
	$sql .= 	" tod.Status IN (";
	$sql .= 			STATUS_SHIP . ",";		// 出荷済
	$sql .= 			STATUS_DELIVERY;		// 納品済
	$sql .= 	" )";
	$sql .= " ORDER BY";
	$sql .= 	" tor.AppliNo,";
	$sql .= 	" tod.AppliLNo";

//var_dump($sql);die;
	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		return false;
	}

	$resultCnt = count($result);

	return $result;
}

////////////////////////////////////////
// PHPEXCEL 行コピー
////////////////////////////////////////
function copyRows(
	$xlsObject,
	$srcRow,	// 複製元行番号
	$dstRow,	// 複製先行番号
	$height, 	// 複製行数
	$width		// 複製カラム数
) {
	$sheet = $xlsObject->getActiveSheet();

	for($row=0; $row<$height; $row++) {
		// セルの書式と値の複製
		for ($col=0; $col<$width; $col++) {
			$cell = $sheet->getCellByColumnAndRow($col, $srcRow+$row);
			$style = $sheet->getStyleByColumnAndRow($col, $srcRow+$row);
			
			$dstCell = PHPExcel_Cell::stringFromColumnIndex($col).(string)($dstRow+$row);
			$sheet->setCellValue($dstCell, $cell->getValue());
			$sheet->duplicateStyle($style, $dstCell);
		}
		
		// 行の高さ複製。
		$h = $sheet->getRowDimension($srcRow+$row)->getRowHeight();
		$sheet->getRowDimension($dstRow+$row)->setRowHeight($h);
	}

	// セル結合の複製
	// - $mergeCell="AB12:AC15" 複製範囲の物だけ行を加算して復元。 
	// - $merge="AB16:AC19"
	foreach ($sheet->getMergeCells() as $mergeCell) {
		$mc = explode(":", $mergeCell);
		$col_s = preg_replace("/[0-9]*/" , "",$mc[0]);
		$col_e = preg_replace("/[0-9]*/" , "",$mc[1]);
		$row_s = ((int)preg_replace("/[A-Z]*/" , "",$mc[0])) - $srcRow;
		$row_e = ((int)preg_replace("/[A-Z]*/" , "",$mc[1])) - $srcRow;

		// 複製先の行範囲なら。
		if (0 <= $row_s && $row_s < $height) {
			$merge = $col_s.(string)($dstRow+$row_s).":".$col_e.(string)($dstRow+$row_e);
			$sheet->mergeCells($merge);
		}
	}
}

?>

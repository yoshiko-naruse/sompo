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

$inputDay = $post['inputDay'];	      // 集計日

// 検索条件チェック
// 集計日（必須）
if (trim($inputDay) == '') {
    $hiddens['errorName'] = 'taiyoList';
    $hiddens['menuName']  = 'isMenuKanri';
    $hiddens['returnUrl'] = 'admin/taiyolist_download.php';
    $hiddens['errorId'][] = '002';
    $errorUrl             = HOME_URL . 'error.php';

    $hiddenHtml = castHiddenError($post);
    $hiddens = array_merge($hiddens, $hiddenHtml);

    // エラー画面に強制遷移
    redirectPost($errorUrl, $hiddens);
}

// YY/MM/DD以外の文字列であればエラー
if ($inputDay != '' && !ereg('^[0-9]{4}/[0-9]{2}/[0-9]{2}$', $inputDay)) {
    $hiddens['errorName'] = 'taiyoList';
    $hiddens['menuName']  = 'isMenuKanri';
    $hiddens['returnUrl'] = 'admin/taiyolist_download.php';
    $hiddens['errorId'][] = '003';
    $errorUrl             = HOME_URL . 'error.php';

    $hiddenHtml = castHiddenError($post);
    $hiddens = array_merge($hiddens, $hiddenHtml);

    // エラー画面に強制遷移
    redirectPost($errorUrl, $hiddens);
}

// 出力する申請ヘッダ一覧を取得
$outputDatas = getTaiyoData($dbConnect, $_POST);
if (!$outputDatas) {
    $hiddens['errorName'] = 'taiyoList';
    $hiddens['menuName']  = 'isMenuKanri';
    $hiddens['returnUrl'] = 'admin/taiyolist_download.php';
    $hiddens['errorId'][] = '005';
    $errorUrl             = HOME_URL . 'error.php';

    $hiddenHtml = castHiddenError($post);
    $hiddens = array_merge($hiddens, $hiddenHtml);

    // エラー画面に強制遷移
    redirectPost($errorUrl, $hiddens);
}

// ここから、エクセル出力
$xlsReader = PHPExcel_IOFactory::createReader('Excel2007');

// エクセルファイルをオープン
$xlsObject = $xlsReader->load("./temp/template_taiyo.xlsx");

//アクティブなシートを変数に格納
$xlsObject->setActiveSheetIndex(0);
$worksheet = $xlsObject->getActiveSheet();

$lineoffset = 5;

$honbuWk     = '';     // ブレイク処理用
$honbuNameWk = '';     // 合計行本部名退避用

$corpNameWk  = '';     // JAF or JAFメディアワークス

$honbuSum1   = '';      // パターン①：エクセル数式格納（本部合計）
$honbuSum2   = '';      // パターン②：エクセル数式格納（本部合計）
$honbuSum3   = '';      // パターン③：エクセル数式格納（本部合計）
$honbuSum4   = '';      // パターン④：エクセル数式格納（本部合計）
$honbuSum5   = '';      // パターン⑤：エクセル数式格納（本部合計）
$honbuSum6   = '';      // パターン⑥：エクセル数式格納（本部合計）

$jafSum1     = '';      // パターン①：エクセル数式格納（会社合計）
$jafSum2     = '';      // パターン②：エクセル数式格納（会社合計）
$jafSum3     = '';      // パターン③：エクセル数式格納（会社合計）
$jafSum4     = '';      // パターン④：エクセル数式格納（会社合計）
$jafSum5     = '';      // パターン⑤：エクセル数式格納（会社合計）
$jafSum6     = '';      // パターン⑥：エクセル数式格納（会社合計）

$totalLine   = '';

// メディアワークス以外のときは「false」
$mediaWorksFlg = false;

$startColumn = '';     // 本部単位での開始カラム ⇒本部ごとの貸与者種類合計にて使用
$endColumn   = '';     // 本部単位での終了カラム ⇒本部ごとの貸与者種類合計にて使用

$date = new DateTime($inputDay);

// 貸与一覧資料タイトル
$title = "";
$title = "貸与一覧　（集計日：" . $date->format('Y.m.d') . "）";

// 貸与一覧シート名
$sheettitle = "";
$sheettitle = $date->format('md') . "現在";

// 出力ファイル名
$outFileName = "";
$outFileName = "貸与一覧表【" . $date->format('Y.m.d') . "】";
$outFileName = mb_convert_encoding($outFileName, 'sjis-win', 'auto');

// タイトル作成（日付付き）
$worksheet->setCellValueExplicit("B2", mb_convert_encoding($title, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
$worksheet->setTitle(mb_convert_encoding($sheettitle, 'UTF-8', 'auto'));

$outputCnt = count($outputDatas);
for ($i = 0; $i < $outputCnt; $i++) {

	// 2件目以降で、前行の本部コードが現在の行の本部コードと一致している場合、
	// 前行までの本部合計行を作成する。
	if ($honbuWk == $outputDatas[$i]['HonbuCd']) {
		// 本部名
		$worksheet->setCellValueExplicit("B".($lineoffset), '', PHPExcel_Cell_DataType::TYPE_STRING);
		$endColumn   = $lineoffset;

		// 合計
		$worksheet->setCellValue("J".($lineoffset) ,"=SUM(D" . $lineoffset  . ":I" . $lineoffset . ")");

	// 本部コードの変わり目
	} else {
		// 1行目の処理（※何も処理せず、書き出す）
		if ($honbuWk == '') {
			// 本部名
			$worksheet->setCellValueExplicit("B".($lineoffset), mb_convert_encoding($outputDatas[$i]['HonbuName'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
			$startColumn = $lineoffset;
			$endColumn   = $lineoffset;

			// 合計
			$worksheet->setCellValue("J".($lineoffset) ,"=SUM(D" . $lineoffset  . ":I" . $lineoffset . ")");

		// 2行目以降で本部コードの変わり目の処理　⇒本部合計行を作成し、合計行 + 1行の位置から次の本部の処理を行う。
		} else {

			// 本部合計行の出力
			$worksheet->setCellValueExplicit("B".($lineoffset), mb_convert_encoding($honbuNameWk . "計", 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);

			$worksheet->setCellValue("D".($lineoffset) ,"=SUM(D" . $startColumn . ":D" . $endColumn . ")");
			$worksheet->setCellValue("E".($lineoffset) ,"=SUM(E" . $startColumn . ":E" . $endColumn . ")");
			$worksheet->setCellValue("F".($lineoffset) ,"=SUM(F" . $startColumn . ":F" . $endColumn . ")");
			$worksheet->setCellValue("G".($lineoffset) ,"=SUM(G" . $startColumn . ":G" . $endColumn . ")");
			$worksheet->setCellValue("H".($lineoffset) ,"=SUM(H" . $startColumn . ":H" . $endColumn . ")");
			$worksheet->setCellValue("I".($lineoffset) ,"=SUM(I" . $startColumn . ":I" . $endColumn . ")");

			// 合計
			$worksheet->setCellValue("J".($lineoffset) ,"=SUM(D" . $lineoffset  . ":I" . $lineoffset . ")");

			// 本部毎の総合計数式サマリー
			$honbuSum1 = $honbuSum1 . "+ D".($lineoffset);
			$honbuSum2 = $honbuSum2 . "+ E".($lineoffset);
			$honbuSum3 = $honbuSum3 . "+ F".($lineoffset);
			$honbuSum4 = $honbuSum4 . "+ G".($lineoffset);
			$honbuSum5 = $honbuSum5 . "+ H".($lineoffset);
			$honbuSum6 = $honbuSum6 . "+ I".($lineoffset);

			// 本部合計行書式
			setRowFormat(9, $column, $lineoffset, 'thin', 'double', '', '', 'ebf1de', $worksheet);

			$lineoffset = $lineoffset + 1;

			// 現在の読み込み行でJMWが存在 且つ JMW初回読み込み
			if (isset($outputDatas[$i]['HonbuCd']) && $outputDatas[$i]['HonbuCd'] == JAF_MEDIAWORKS && $mediaWorksFlg == false ) {

				// JAF本部⇒JAFメディアワークス切り替わり位置：JAFの合計行
				setRowFormat(9, $column, $lineoffset, 'thick', 'thick', '', '', 'fff2cc', $worksheet);

				// 最終行の本部総合計行の出力 Start
				$worksheet->setCellValueExplicit("B".($lineoffset), mb_convert_encoding($corpNameWk . "計", 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);

				// 貸与パターン①
				$worksheet->setCellValue("D".($lineoffset) ,"=" . $honbuSum1);
				// 貸与パターン②
				$worksheet->setCellValue("E".($lineoffset) ,"=" . $honbuSum2);
				// 貸与パターン③
				$worksheet->setCellValue("F".($lineoffset) ,"=" . $honbuSum3);
				// 貸与パターン④
				$worksheet->setCellValue("G".($lineoffset) ,"=" . $honbuSum4);
				// 貸与パターン⑤
				$worksheet->setCellValue("H".($lineoffset) ,"=" . $honbuSum5);
				// 貸与パターン⑥
				$worksheet->setCellValue("I".($lineoffset) ,"=" . $honbuSum6);

				// JAF行合計数式退避
				$jafSum1 = $honbuSum1;
				$jafSum2 = $honbuSum2;
				$jafSum3 = $honbuSum3;
				$jafSum4 = $honbuSum4;
				$jafSum5 = $honbuSum5;
				$jafSum6 = $honbuSum6;

				// 合計
				$worksheet->setCellValue("J".($lineoffset) ,"=SUM(D" . $lineoffset  . ":I" . $lineoffset . ")");

				// ＪＡＦメディアワークス用
				$honbuSum1   = '';
				$honbuSum2   = '';
				$honbuSum3   = '';
				$honbuSum4   = '';
				$honbuSum5   = '';
				$honbuSum6   = '';
				
				$mediaWorksFlg = true;

				$lineoffset = $lineoffset + 1;
			}

			// 合計
			$worksheet->setCellValue("J".($lineoffset) ,"=SUM(D" . $lineoffset  . ":I" . $lineoffset . ")");

			// 本部名
			$worksheet->setCellValueExplicit("B".($lineoffset), mb_convert_encoding($outputDatas[$i]['HonbuName'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
			$startColumn = $lineoffset;
			$endColumn   = $lineoffset;

		}
	}

	// 共通処理：書き出し
	// 本部名
//	$worksheet->setCellValueExplicit("B".($lineoffset), mb_convert_encoding($outputDatas[$i]['HonbuName'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
	// 支部名
	$worksheet->setCellValueExplicit("C".($lineoffset), mb_convert_encoding($outputDatas[$i]['ShibuName'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
	// 貸与パターン①
	$worksheet->setCellValueExplicit("D".($lineoffset), mb_convert_encoding($outputDatas[$i]['ptn1'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
	// 貸与パターン②
	$worksheet->setCellValueExplicit("E".($lineoffset), mb_convert_encoding($outputDatas[$i]['ptn2'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
	// 貸与パターン③
	$worksheet->setCellValueExplicit("F".($lineoffset), mb_convert_encoding($outputDatas[$i]['ptn3'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
	// 貸与パターン④
	$worksheet->setCellValueExplicit("G".($lineoffset), mb_convert_encoding($outputDatas[$i]['ptn4'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
	// 貸与パターン⑤
	$worksheet->setCellValueExplicit("H".($lineoffset), mb_convert_encoding($outputDatas[$i]['ptn5'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
	// 貸与パターン⑥
	$worksheet->setCellValueExplicit("I".($lineoffset), mb_convert_encoding($outputDatas[$i]['ptn6'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);

	$lineoffset = $lineoffset + 1;

	// 現在の行の本部コード、本部名を退避（※次行で前行のコードの比較に使用する。）
	$honbuWk     = $outputDatas[$i]['HonbuCd'];
	$honbuNameWk = $outputDatas[$i]['HonbuName'];
	
	// 総合計行の会社名称を退避
	if ($outputDatas[$i]['HonbuCd'] == JAF_MEDIAWORKS) {
		$corpNameWk = "JAFメディアワークス";
	} else {
		$corpNameWk = "JAF";
	}
	// 改ページ
//	$worksheet->setBreak("A".($lineoffset - 1), PHPExcel_Worksheet::BREAK_ROW);
}

// 最終行の本部合計行の出力 Start
$worksheet->setCellValueExplicit("B".($lineoffset), mb_convert_encoding($honbuNameWk . "計", 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);

$worksheet->setCellValue("D".($lineoffset) ,"=SUM(D" . $startColumn . ":D" . $endColumn . ")");
$worksheet->setCellValue("E".($lineoffset) ,"=SUM(E" . $startColumn . ":E" . $endColumn . ")");
$worksheet->setCellValue("F".($lineoffset) ,"=SUM(F" . $startColumn . ":F" . $endColumn . ")");
$worksheet->setCellValue("G".($lineoffset) ,"=SUM(G" . $startColumn . ":G" . $endColumn . ")");
$worksheet->setCellValue("H".($lineoffset) ,"=SUM(H" . $startColumn . ":H" . $endColumn . ")");
$worksheet->setCellValue("I".($lineoffset) ,"=SUM(I" . $startColumn . ":I" . $endColumn . ")");

$honbuSum1 = $honbuSum1 . "+ D".($lineoffset);
$honbuSum2 = $honbuSum2 . "+ E".($lineoffset);
$honbuSum3 = $honbuSum3 . "+ F".($lineoffset);
$honbuSum4 = $honbuSum4 . "+ G".($lineoffset);
$honbuSum5 = $honbuSum5 . "+ H".($lineoffset);
$honbuSum6 = $honbuSum6 . "+ I".($lineoffset);

// JAFのみだった場合の本部合計行（JAFメディアワークスを含まない）
if ($mediaWorksFlg == false) { 
	setRowFormat(9, $column, $lineoffset, 'thin', 'thick', '', '', 'ebf1de', $worksheet);

} else {
	setRowFormat(9, $column, $lineoffset, 'double', 'thick', '', '', 'fff2cc', $worksheet);
}

// 合計
$worksheet->setCellValue("J".($lineoffset) ,"=SUM(D" . $lineoffset  . ":I" . $lineoffset . ")");

//-----------------------------------------------------

// JAFのみだった場合、JAF合計行（JAFメディアワークスを含まない）
if ($mediaWorksFlg == false) { 

	$lineoffset = $lineoffset + 1;

	setRowFormat(9, $column, $lineoffset, 'thick', 'thick', '', '', 'fff2cc', $worksheet);

		
	// 最終行の本部総合計行の出力 Start
	$worksheet->setCellValueExplicit("B".($lineoffset), mb_convert_encoding($corpNameWk . "計", 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);

	// 貸与パターン①
	$worksheet->setCellValue("D".($lineoffset) ,"=" . $honbuSum1);
	// 貸与パターン②
	$worksheet->setCellValue("E".($lineoffset) ,"=" . $honbuSum2);
	// 貸与パターン③
	$worksheet->setCellValue("F".($lineoffset) ,"=" . $honbuSum3);
	// 貸与パターン④
	$worksheet->setCellValue("G".($lineoffset) ,"=" . $honbuSum4);
	// 貸与パターン⑤
	$worksheet->setCellValue("H".($lineoffset) ,"=" . $honbuSum5);
	// 貸与パターン⑥
	$worksheet->setCellValue("I".($lineoffset) ,"=" . $honbuSum6);

	// 合計
	$worksheet->setCellValue("J".($lineoffset) ,"=SUM(D" . $lineoffset  . ":I" . $lineoffset . ")");
}
//------------------------------------------------------------
// 最終行の本部合計行の出力 End

// 総合計行出力
$lineoffset = $lineoffset + 1;

// 最終行の本部総合計行の出力 Start
$worksheet->setCellValueExplicit("B".($lineoffset), mb_convert_encoding("総合計", 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);

$worksheet->setCellValue("D".($lineoffset) ,"=" . $jafSum1 . $honbuSum1);
$worksheet->setCellValue("E".($lineoffset) ,"=" . $jafSum2 . $honbuSum2);
$worksheet->setCellValue("F".($lineoffset) ,"=" . $jafSum3 . $honbuSum3);
$worksheet->setCellValue("G".($lineoffset) ,"=" . $jafSum4 . $honbuSum4);
$worksheet->setCellValue("H".($lineoffset) ,"=" . $jafSum5 . $honbuSum5);
$worksheet->setCellValue("I".($lineoffset) ,"=" . $jafSum6 . $honbuSum6);

$totalLine = $lineoffset;

// 合計
$worksheet->setCellValue("J".($lineoffset) ,"=SUM(D" . $lineoffset  . ":I" . $lineoffset . ")");

setRowFormat(9, $column, $lineoffset, 'thick', 'thick', '', '', 'ffe699', $worksheet);

// 最終合計行以降を削除とする。
$lineoffset = $lineoffset + 1;

$worksheet->removeRow($lineoffset, 400);

// （総合計行 + 2）行目から、総ステータスを記録
$lineoffset = $lineoffset + 1;

// 出力する請求サマリー情報
$outputRentalInfo = getRentalPrice($dbConnect);

$outputRentalCnt = count($outputRentalInfo);

for ($i = 0; $i < $outputRentalCnt; $i++) {

    switch ($outputRentalInfo[$i]['PatternID']) {
        case 1:
		case 2:
		case 3:
		case 4:
            $ptn1Price    = $outputRentalInfo[$i]['Price'];
            $ptn1PriceTax = $outputRentalInfo[$i]['PriceTax'];
            break;
		case 6:				// 
            $ptn2Price    = $outputRentalInfo[$i]['Price'];
            $ptn2PriceTax = $outputRentalInfo[$i]['PriceTax'];
            break;
		case 7:				// 
            $ptn3Price    = $outputRentalInfo[$i]['Price'];
            $ptn3PriceTax = $outputRentalInfo[$i]['PriceTax'];
            break;
		case 9:				// 
            $ptn4Price    = $outputRentalInfo[$i]['Price'];
            $ptn4PriceTax = $outputRentalInfo[$i]['PriceTax'];
            break;
		case 8:				// 
            $ptn5Price    = $outputRentalInfo[$i]['Price'];
            $ptn5PriceTax = $outputRentalInfo[$i]['PriceTax'];
            break;
		case 10:				// 
            $ptn6Price    = $outputRentalInfo[$i]['Price'];
            $ptn6PriceTax = $outputRentalInfo[$i]['PriceTax'];
            break;
        default:
            break;
    }
   
}

// レンタル料/月（税別）-------------------------------
$worksheet->setCellValueExplicit("B".($lineoffset), mb_convert_encoding("レンタル料/月（税別）", 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
// 貸与パターン①
$worksheet->setCellValueExplicit("D".($lineoffset), mb_convert_encoding($ptn1Price, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("D".($lineoffset))->getNumberFormat()->setFormatCode('"\"#,##0');
// 貸与パターン②
$worksheet->setCellValueExplicit("E".($lineoffset), mb_convert_encoding($ptn2Price, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("E".($lineoffset))->getNumberFormat()->setFormatCode('"\"#,##0');
// 貸与パターン③
$worksheet->setCellValueExplicit("F".($lineoffset), mb_convert_encoding($ptn3Price, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("F".($lineoffset))->getNumberFormat()->setFormatCode('"\"#,##0');
// 貸与パターン④
$worksheet->setCellValueExplicit("G".($lineoffset), mb_convert_encoding($ptn4Price, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("G".($lineoffset))->getNumberFormat()->setFormatCode('"\"#,##0');
// 貸与パターン⑤
$worksheet->setCellValueExplicit("H".($lineoffset), mb_convert_encoding($ptn5Price, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("H".($lineoffset))->getNumberFormat()->setFormatCode('"\"#,##0');
// 貸与パターン⑥
$worksheet->setCellValueExplicit("I".($lineoffset), mb_convert_encoding($ptn6Price, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("I".($lineoffset))->getNumberFormat()->setFormatCode('"\"#,##0');

// 書式設定
setRowFormat(8, $column, $lineoffset, 'thin', 'thin', 'thin', 'thin', '', $worksheet);

// セルを結合する
$worksheet->mergeCells("B".($lineoffset) . ":" . "C".($lineoffset) );

// 消費税 -------------------------------
$worksheet->setCellValueExplicit("B".($lineoffset+1), mb_convert_encoding("消費税", 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
// 貸与パターン①
$worksheet->setCellValueExplicit("D".($lineoffset+1), mb_convert_encoding($ptn1PriceTax - $ptn1Price, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("D".($lineoffset+1))->getNumberFormat()->setFormatCode('"\"#,##0');
// 貸与パターン②
$worksheet->setCellValueExplicit("E".($lineoffset+1), mb_convert_encoding($ptn2PriceTax - $ptn2Price, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("E".($lineoffset+1))->getNumberFormat()->setFormatCode('"\"#,##0');
// 貸与パターン③
$worksheet->setCellValueExplicit("F".($lineoffset+1), mb_convert_encoding($ptn3PriceTax - $ptn3Price, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("F".($lineoffset+1))->getNumberFormat()->setFormatCode('"\"#,##0');
// 貸与パターン④
$worksheet->setCellValueExplicit("G".($lineoffset+1), mb_convert_encoding($ptn4PriceTax - $ptn4Price, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("G".($lineoffset+1))->getNumberFormat()->setFormatCode('"\"#,##0');
// 貸与パターン⑤
$worksheet->setCellValueExplicit("H".($lineoffset+1), mb_convert_encoding($ptn5PriceTax - $ptn5Price, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("H".($lineoffset+1))->getNumberFormat()->setFormatCode('"\"#,##0');
// 貸与パターン⑥
$worksheet->setCellValueExplicit("I".($lineoffset+1), mb_convert_encoding($ptn6PriceTax - $ptn6Price, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("I".($lineoffset+1))->getNumberFormat()->setFormatCode('"\"#,##0');

// 書式設定
setRowFormat(8, $column, $lineoffset+1, 'thin', 'thin', 'thin', 'thin', '', $worksheet);

// セルを結合する
$worksheet->mergeCells("B".($lineoffset+1) . ":" . "C".($lineoffset+1) );

// レンタル料/月（税込）-------------------------------
$worksheet->setCellValueExplicit("B".($lineoffset+2), mb_convert_encoding("レンタル料/月（税込）", 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
// 貸与パターン①
$worksheet->setCellValueExplicit("D".($lineoffset+2), mb_convert_encoding($ptn1PriceTax, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("D".($lineoffset+2))->getNumberFormat()->setFormatCode('"\"#,##0');
// 貸与パターン②
$worksheet->setCellValueExplicit("E".($lineoffset+2), mb_convert_encoding($ptn2PriceTax, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("E".($lineoffset+2))->getNumberFormat()->setFormatCode('"\"#,##0');
// 貸与パターン③
$worksheet->setCellValueExplicit("F".($lineoffset+2), mb_convert_encoding($ptn3PriceTax, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("F".($lineoffset+2))->getNumberFormat()->setFormatCode('"\"#,##0');
// 貸与パターン④
$worksheet->setCellValueExplicit("G".($lineoffset+2), mb_convert_encoding($ptn4PriceTax, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("G".($lineoffset+2))->getNumberFormat()->setFormatCode('"\"#,##0');
// 貸与パターン⑤
$worksheet->setCellValueExplicit("H".($lineoffset+2), mb_convert_encoding($ptn5PriceTax, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("H".($lineoffset+2))->getNumberFormat()->setFormatCode('"\"#,##0');
// 貸与パターン⑥
$worksheet->setCellValueExplicit("I".($lineoffset+2), mb_convert_encoding($ptn6PriceTax, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("I".($lineoffset+2))->getNumberFormat()->setFormatCode('"\"#,##0');

// 書式設定
setRowFormat(8, $column, $lineoffset+2, 'thin', 'thin', 'thin', 'thin', '', $worksheet);

// セルを結合する
$worksheet->mergeCells("B".($lineoffset+2) . ":" . "C".($lineoffset+2) );

// 種類別合計（税別）-------------------------------
$worksheet->setCellValueExplicit("B".($lineoffset+3), mb_convert_encoding("種類別合計（税別）", 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
//
$worksheet->setCellValue("D".($lineoffset+3) ,"=D".($totalLine) . "*" . "D".($lineoffset));
$worksheet->setCellValue("E".($lineoffset+3) ,"=E".($totalLine) . "*" . "E".($lineoffset));
$worksheet->setCellValue("F".($lineoffset+3) ,"=F".($totalLine) . "*" . "F".($lineoffset));
$worksheet->setCellValue("G".($lineoffset+3) ,"=G".($totalLine) . "*" . "G".($lineoffset));
$worksheet->setCellValue("H".($lineoffset+3) ,"=H".($totalLine) . "*" . "H".($lineoffset));
$worksheet->setCellValue("I".($lineoffset+3) ,"=I".($totalLine) . "*" . "I".($lineoffset));

$worksheet->getStyle("D".($lineoffset+3))->getNumberFormat()->setFormatCode('"\"#,##0');
$worksheet->getStyle("E".($lineoffset+3))->getNumberFormat()->setFormatCode('"\"#,##0');
$worksheet->getStyle("F".($lineoffset+3))->getNumberFormat()->setFormatCode('"\"#,##0');
$worksheet->getStyle("G".($lineoffset+3))->getNumberFormat()->setFormatCode('"\"#,##0');
$worksheet->getStyle("H".($lineoffset+3))->getNumberFormat()->setFormatCode('"\"#,##0');
$worksheet->getStyle("I".($lineoffset+3))->getNumberFormat()->setFormatCode('"\"#,##0');//

// 書式設定
setRowFormat(8, $column, $lineoffset+3, 'thin', 'thin', 'thin', 'thin', '', $worksheet);

// セルを結合する
$worksheet->mergeCells("B".($lineoffset+3) . ":" . "C".($lineoffset+3) );

// 種類別合計（①×②）（税込）-------------------------------
$worksheet->setCellValueExplicit("B".($lineoffset+4), mb_convert_encoding("種類別合計（税込）", 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
//
$worksheet->setCellValue("D".($lineoffset+4) ,"=D".($totalLine) . "*" . "D".($lineoffset+2));
$worksheet->setCellValue("E".($lineoffset+4) ,"=E".($totalLine) . "*" . "E".($lineoffset+2));
$worksheet->setCellValue("F".($lineoffset+4) ,"=F".($totalLine) . "*" . "F".($lineoffset+2));
$worksheet->setCellValue("G".($lineoffset+4) ,"=G".($totalLine) . "*" . "G".($lineoffset+2));
$worksheet->setCellValue("H".($lineoffset+4) ,"=H".($totalLine) . "*" . "H".($lineoffset+2));
$worksheet->setCellValue("I".($lineoffset+4) ,"=I".($totalLine) . "*" . "I".($lineoffset+2));

$worksheet->getStyle("D".($lineoffset+4))->getNumberFormat()->setFormatCode('"\"#,##0');
$worksheet->getStyle("E".($lineoffset+4))->getNumberFormat()->setFormatCode('"\"#,##0');
$worksheet->getStyle("F".($lineoffset+4))->getNumberFormat()->setFormatCode('"\"#,##0');
$worksheet->getStyle("G".($lineoffset+4))->getNumberFormat()->setFormatCode('"\"#,##0');
$worksheet->getStyle("H".($lineoffset+4))->getNumberFormat()->setFormatCode('"\"#,##0');
$worksheet->getStyle("I".($lineoffset+4))->getNumberFormat()->setFormatCode('"\"#,##0');//

// 書式設定
setRowFormat(8, $column, $lineoffset+4, 'thin', 'thin', 'thin', 'thin', '', $worksheet);

// セルを結合する
$worksheet->mergeCells("B".($lineoffset+4) . ":" . "C".($lineoffset+4) );

// 合計 -------------------------------
$worksheet->setCellValueExplicit("B".($lineoffset+5), mb_convert_encoding("合計", 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
//
// 合計
$worksheet->setCellValue("D".($lineoffset+5) ,"=SUM(D" . ($lineoffset+4)  . ":I" . ($lineoffset+4) . ")");

$worksheet->getStyle("D".($lineoffset+5))->getNumberFormat()->setFormatCode('"\"#,##0');//

// 書式設定
setRowFormat(8, $column, $lineoffset+5, 'thick', 'thick', 'thick', 'thick', '', $worksheet);

// セルを結合する
$worksheet->mergeCells("B".($lineoffset+5) . ":" . "C".($lineoffset+5) );
// 合計行のセル結合
$worksheet->mergeCells("D".($lineoffset+5) . ":" . "I".($lineoffset+5) );

/* 幅と高さを1ページに収める */
//$worksheet->getPageSetup()->setFitToWidth( 1 )->setFitToHeight( 1 );

//印刷範囲
$worksheet->getPageSetup()->setPrintArea("A1:" . "J".($lineoffset+5) );

$xlsWriter = PHPExcel_IOFactory::createWriter($xlsObject, 'Excel5');

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
function setRowFormat($max='', $column, $lineoffset, $top='', $bottom='', $left='', $right='', $color='', $worksheet) {

	for ($column=1; $column<=$max ;$column++) {

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
function getTaiyoData($dbConnect, $post) {

	global $isLevelAgency;

	$result = array();
	$details = array();

	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" piv.HonbuCd,";
	$sql .= 	" piv.HonbuName,";
	$sql .= 	" piv.ShibuCd,";
	$sql .= 	" piv.ShibuName,";
	$sql .= 	" sum(case piv.AppliPattern when 1 then cnt when 2 then cnt when 3 then cnt when 4 then cnt else null end) as ptn1,";
	$sql .= 	" sum(case piv.AppliPattern when 6 then cnt else null end) as ptn2,";
	$sql .= 	" sum(case piv.AppliPattern when 7 then cnt else null end) as ptn3,";
	$sql .= 	" sum(case piv.AppliPattern when 9 then cnt else null end) as ptn4,";
	$sql .= 	" sum(case piv.AppliPattern when 8 then cnt else null end) as ptn5,";
	$sql .= 	" sum(case piv.AppliPattern when 10 then cnt else null end) as ptn6";
	$sql .= " FROM";
	$sql .= 	" (";
	$sql .= 	" SELECT";
	$sql .= 		" ship.HonbuCd,";
	$sql .= 		" ship.HonbuName,";
	$sql .= 		" ship.ShibuCd,";
	$sql .= 		" ship.ShibuName,";
	$sql .= 		" ship.AppliPattern,";
	$sql .= 		" PatternName,";
	$sql .= 		" count(ship.StaffID) AS cnt";
	$sql .= 	" FROM";
	$sql .= 	" (";
	$sql .= 		" SELECT";
	$sql .= 			" mc.HonbuCd,";
	$sql .= 			" mc.HonbuName,";
	$sql .= 			" mc.ShibuCd,";
	$sql .= 			" mc.ShibuName,";
	$sql .= 			" tor.StaffID,";
	$sql .= 			" tor.StaffCode,";
	$sql .= 			" ROW_NUMBER() OVER(PARTITION BY tor.StaffID ORDER BY tor.OrderID) AS OrderCnt,";
	$sql .= 			" tor.OrderID,";
	$sql .= 			" tor.AppliMode,";
	$sql .= 			" tor.AppliPattern,";
	$sql .= 			" tor.Status,";
	$sql .= 			" CONVERT(varchar, tor.RentalStartDay, 111) AS RentalStartDay";
	$sql .= 		" FROM";
	$sql .= 			" jaf.T_Order tor";

	$sql .= 			" INNER JOIN";
	$sql .= 				" M_Comp mc";
	$sql .= 			" ON  tor.CompID = mc.CompID";
	$sql .= 			" AND mc.Del = " . DELETE_OFF;
	$sql .= 			" AND mc.ShopFlag <> 0";

	$sql .= 		" WHERE";
	$sql .= 			" tor.AppliMode = " . APPLI_MODE_ORDER;
	// Y.Furukawa 2017/08/24 キャンセル・否認は除外
	$sql .= 		" AND tor.Status NOT IN (" . STATUS_CANCEL . "," . STATUS_APPLI_DENY .")";
	$sql .= 		" AND tor.Del = " . DELETE_OFF;
	$sql .= 		" AND RentalStartDay <= '" . db_Escape($post['inputDay']) . "'";
	$sql .= 	" ) ship";
	$sql .= 	" LEFT JOIN";
	$sql .= 	" (";
	$sql .= 		" SELECT";
	$sql .= 			" tor.StaffID,";
	$sql .= 			" tor.StaffCode,";
	$sql .= 			" ROW_NUMBER() OVER(PARTITION BY tor.StaffID ORDER BY tor.OrderID) AS OrderCnt,";
	$sql .= 			" tor.OrderID,";
	$sql .= 			" tor.AppliMode,";
	$sql .= 			" tor.Status,";
	$sql .= 			" CONVERT(varchar, tor.RentalEndDay, 111) AS RentalEndDay";
	$sql .= 		" FROM";
	$sql .= 			" jaf.T_Order tor";
	$sql .= 		" WHERE";
	$sql .= 			" tor.AppliMode = " . APPLI_MODE_RETURN;
	// Y.Furukawa 2017/08/24 キャンセル・否認は除外
	$sql .= 		" AND tor.Status NOT IN (" . STATUS_CANCEL . "," . STATUS_APPLI_DENY .")";
	$sql .= 		" AND tor.Del = " . DELETE_OFF;
	$sql .= 	" ) ret";
	$sql .= 	" ON  ship.StaffID = ret.StaffID";
	$sql .= 	" AND ship.OrderCnt = ret.OrderCnt";
	$sql .= 	" INNER JOIN";
	$sql .= 	" (";
	$sql .= 		" SELECT";
	$sql .= 			" DISTINCT PatternID,";
	$sql .= 			" PatternName,";
	$sql .= 			" Price,";
	$sql .= 			" PriceTax,";
	$sql .= 			" sum(ItemSelectNum) AS PatternCount";
	$sql .= 		" FROM";
	$sql .= 			" M_ItemSelect";
	$sql .= 		" WHERE";
	$sql .= 			" Del = " . DELETE_OFF;
	$sql .= 		" GROUP BY";
	$sql .= 			" PatternID,";
	$sql .= 			" PatternName,";
	$sql .= 			" Price,";
	$sql .= 			" PriceTax";
	$sql .= 	" ) mis";
	$sql .= 	" ON  mis.PatternID = ship.AppliPattern";
	$sql .= " WHERE";
	$sql .= 	" ret.RentalEndDay >=  '" . db_Escape($post['inputDay']) . "'";
	$sql .= " OR  ret.RentalEndDay is null";

	$sql .= " GROUP BY";
	$sql .= 	" ship.HonbuCd,";
	$sql .= 	" ship.HonbuName,";
	$sql .= 	" ship.ShibuCd,";
	$sql .= 	" ship.ShibuName,";
	$sql .= 	" ship.AppliPattern,";
	$sql .= 	" PatternName";

	$sql .= 	" ) piv";

	$sql .= " GROUP BY";
	$sql .= 	" piv.HonbuCd,";
	$sql .= 	" piv.HonbuName,";
	$sql .= 	" piv.ShibuCd,";
	$sql .= 	" piv.ShibuName";

	$sql .= " ORDER BY";
	$sql .= 	" piv.HonbuCd,";
	$sql .= 	" piv.HonbuName,";
	$sql .= 	" piv.ShibuCd,";
	$sql .= 	" piv.ShibuName";

//var_dump($sql);die;
	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		return false;
	}

	$resultCnt = count($result);

	// 各申請の明細一覧を取得する
	for ($i = 0; $i < $resultCnt; $i++) {

	}

	return $result;
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
function getRentalPrice($dbConnect) {

	global $isLevelAgency;

	$result = array();
	$details = array();

	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" DISTINCT";
	$sql .= 	" PatternID,";
	$sql .= 	" PatternName,";
	$sql .= 	" Price,";
	$sql .= 	" PriceTax";
	$sql .= " FROM";
	$sql .= 	" M_ItemSelect";
	$sql .= " WHERE";
	$sql .= 	" Del = " . DELETE_OFF;
	$sql .= " GROUP BY";
	$sql .= 	" PatternID,";
	$sql .= 	" PatternName,";
	$sql .= 	" Price,";
	$sql .= 	" PriceTax";

//var_dump($sql);die;
	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		return false;
	}

	$resultCnt = count($result);

	// 各申請の明細一覧を取得する
	for ($i = 0; $i < $resultCnt; $i++) {

	}

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

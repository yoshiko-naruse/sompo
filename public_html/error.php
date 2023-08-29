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
/* ../include/dbConnect.php start */

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


/* ../include/dbConnect.php end */


/* ../include/msSqlControl.php start */

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


/* ../include/msSqlControl.php end */


/* ../include/checkLogin.php start */

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


/* ../include/checkLogin.php end */


/* ../include/castHidden.php start */

/*
 * hidden値成型モジュール
 * castHidden.php
 *
 * create 2007/03/15 H.Osugi
 *
 */

/*
 * hidden値を成型する
 * 引数  ：$post       => hiddenを生成したい値(array) 
 *       ：$notAllows  => hiddenに組み込みたくない値(array)
 * 戻り値：$hiddenHtml => hiddenタグ生成に必要な値(array)
 *
 * create 2007/03/15 H.Osugi
 *
 */
function castHidden($post, $notAllows = '') {

	// 初期化
	$hiddenHtml = array();

	//POST値が存在しない場合
	if (!isset($post) || count($post) <= 0 || !is_array($post)) {
		return $hiddenHtml;
	}

	// 値の成型
	$i = 0;
	foreach ($post as $key => $value) {

		if (is_array($notAllows) && in_array($key, $notAllows)) {
			continue;
		}

		$hiddenHtml[$i]['name']  = $key;
		$hiddenHtml[$i]['value'] = $value;
		$i++;

	}

	return $hiddenHtml;

}

/*
 * エラー画面に送信するためのhidden値を成型する
 * 引数  ：$post       => コネクションハンドラ
 *       ：$notAllows  => hiddenに組み込みたくない値(array)
 * 戻り値：$hiddenHtml => hiddenタグ生成に必要な値(array)
 *
 * create 2007/03/15 H.Osugi
 *
 */
function castHiddenError($post, $notAllows = '') {

	// 初期化
	$hiddenHtml = array();

	//POST値が存在しない場合
	if (!isset($post) || count($post) <= 0) {
		$hiddenHtml = array();
		return $hiddenHtml;
	}

	// 値の成型
	$i = 0;
	foreach ($post as $key => $value) {

		if (is_array($notAllows) && in_array($key, $notAllows)) {
			continue;
		}
		$hiddenHtml[$key] = $value;
	}

	return $hiddenHtml;

}


/* ../include/castHidden.php end */


/* ../include/castHtmlEntity.php start */

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


/* ../include/castHtmlEntity.php end */


/* ../error_message/errorMessage.php start */

/*
 * エラーメッセージ一覧
 * index_error.php
 *
 * create 2007/03/14 H.Osugi
 *
 *
 */

// DB接続失敗時エラー(include/dbConnect.php)
$connectFailed = array();

$connectFailed['001'] = 'DB接続できませんでした。' . "\n" . 'システム管理者にお問い合わせください。';

// staffCode重複チェック(include/checkDuplicateAppli.php)
$duplicateAppli = array();
$duplicateAppli['001'] = '入力された職員コードはすでに申請が行われています。';

// 発注申請(hachu/hachu_shinsei.php)
$hachuShinsei = array();

$hachuShinsei['001'] = '職員コードが入力されていません。';
$hachuShinsei['002'] = '職員コードは半角で入力してください。';
$hachuShinsei['003'] = '職員コードは半角英数字12文字で入力してください。';
$hachuShinsei['004'] = '職員コードは' . COMMON_STAFF_CODE . 'で始まる半角英数字12文字で入力してください。';

$hachuShinsei['011'] = '郵便番号が入力されていません。';
$hachuShinsei['012'] = '郵便番号は半角数値の[3桁]-[4桁]で入力してください。';

$hachuShinsei['021'] = '住所が入力されていません。';
$hachuShinsei['022'] = '住所は全角120文字以内で入力してください。';

$hachuShinsei['031'] = '出荷先名が入力されていません。';
$hachuShinsei['032'] = '出荷先名は全角60文字以内で入力してください。';

$hachuShinsei['041'] = 'ご担当者が入力されていません。';
$hachuShinsei['042'] = 'ご担当者は全角20文字以内で入力してください。';

$hachuShinsei['051'] = '電話番号が入力されていません。';
$hachuShinsei['052'] = '電話番号は半角数値で入力してください。';
$hachuShinsei['053'] = '電話番号は半角数値15文字以内で入力してください。';

$hachuShinsei['061'] = 'メモは全角64文字以内で入力してください。';

$hachuShinsei['071'] = 'アイテムを選択してください。';

$hachuShinsei['081'] = 'サイズが選択されていないアイテムがあります。';

$hachuShinsei['091'] = '数量が入力されていないアイテムがあります。';

$hachuShinsei['092'] = 'スカートとパンツは合計で２着になるように入力してください。';

$hachuShinsei['093'] = '数量は半角数値で入力してください。';

$hachuShinsei['094'] = 'チェックしたアイテムの数量は1以上を入力してください。';

$hachuShinsei['095'] = 'アイテムの数量を入力してください。';

$hachuShinsei['111'] = '出荷指定日が正しい日付ではありません。';
$hachuShinsei['112'] = '出荷指定日に発注入力当日と過去日付は指定できません。';
$hachuShinsei['113'] = '出荷指定日に土曜日と日曜日は指定できません。';

$hachuShinsei['200'] = '貸与パターンを選択してください。';

$hachuShinsei['901'] = '発注申請に失敗しました。' . "\n" . 'システム管理者にお問い合わせください。';


// 交換職員選択（koukan/koukan_sentaku.php）
$koukanSentaku = array();

$koukanSentaku['901'] = '該当の職員は見つかりませんでした。';
$koukanSentaku['902'] = '交換理由が選択されていません。';

// 交換 (koukan/koukan_shinsei.php)
$koukanShinsei = array();

$koukanShinsei['001'] = '郵便番号が入力されていません。';
$koukanShinsei['002'] = '郵便番号は半角数値の[3桁]-[4桁]で入力してください。';

$koukanShinsei['011'] = '住所が入力されていません。';
$koukanShinsei['012'] = '住所は全角120文字以内で入力してください。';

$koukanShinsei['021'] = '出荷先名が入力されていません。';
$koukanShinsei['022'] = '出荷先名は全角60文字以内で入力してください。';

$koukanShinsei['031'] = 'ご担当者が入力されていません。';
$koukanShinsei['032'] = 'ご担当者は全角20文字以内で入力してください。';

$koukanShinsei['041'] = '電話番号が入力されていません。';
$koukanShinsei['042'] = '電話番号は半角数値で入力してください。';
$koukanShinsei['043'] = '電話番号は半角数値15文字以内で入力してください。';

$koukanShinsei['051'] = 'メモは全角64文字以内で入力してください。';
$koukanShinsei['052'] = 'メモを入力してください。';

$koukanShinsei['061'] = '交換するユニフォームが選択されていません。';

$koukanShinsei['071'] = 'サイズが選択されていないアイテムがあります。';

$koukanShinsei['081'] = 'サイズ交換の場合は同じサイズの交換はできません。';
$koukanShinsei['082'] = '同一アイテムは全て同じサイズをご指定ください。';
$koukanShinsei['083'] = '選択した商品に未出荷の商品が存在するため、サイズ交換できません。';

$koukanShinsei['901'] = '交換できるユニフォームはありません。';
$koukanShinsei['902'] = '交換するユニフォームの返却申請が失敗しました。' . "\n" . 'システム管理者にお問い合わせください。';
$koukanShinsei['903'] = '交換するユニフォームの発注申請が失敗しました。' . "\n" . 'システム管理者にお問い合わせください。';
$koukanShinsei['904'] = '選択されたユニフォームは現在交換できません。';
$koukanShinsei['905'] = '先に営業所を検索してください。';

// 返却職員選択（henpin/henpin_sentaku.php）
$henpinSentaku = array();

$henpinSentaku['901'] = '該当の職員は見つかりませんでした。';
$henpinSentaku['902'] = '返却理由が選択されていません。';

// 返却 (henpin/henpin_shinsei.php)
$henpinShinsei = array();

$henpinShinsei['001'] = 'メモを入力してください。';
$henpinShinsei['002'] = 'メモは全角64文字以内で入力してください。';
$henpinShinsei['003'] = '未選択のユニフォームがあります。必ず「返却」「紛失」のどちらかをチェックして下さい。';
$henpinShinsei['004'] = '返却するユニフォームが選択されていません。';

$henpinShinsei['100'] = 'レンタル終了日を入力してください。';
$henpinShinsei['101'] = 'レンタル終了日に存在しない日付が入力されています。';
$henpinShinsei['102'] = 'レンタル終了日は本日以降の日付を入力してください。';

$henpinShinsei['901'] = '返却できるユニフォームはありません。';
$henpinShinsei['902'] = 'ユニフォームの返却申請が失敗しました。' . "\n" . 'システム管理者にお問い合わせください。';
$henpinShinsei['903'] = '選択されたユニフォームは現在返却できません。';

// 申請履歴（rireki/rireki.php）
$rireki = array();

$rireki['901'] = '該当する申請履歴はありませんでした。';
$rireki['902'] = '検索条件を指定してください。';

// キャンセル（rireki/cancel.php）
$cancel = array();

$cancel['901'] = 'キャンセルに失敗しました。' . "\n" . 'システム管理者にお問い合わせください。';
$cancel['902'] = 'キャンセルする情報が取得できませんでした。';

// 返却明細（rireki/henpin_meisai.php）
$henpinMeisai = array();

$henpinMeisai['901'] = '返却明細を表示するための情報が取得できませんでした。';

// 発注明細（rireki/hachu_meisai.php）
$hachuMeisai = array();

$hachuMeisai['901'] = '発注明細を表示するための情報が取得できませんでした。';


// 着用状況（chakuyou/chakuyou.php）
$chakuyou = array();

$chakuyou['901'] = '該当する貸与データはありませんでした。';
$chakuyou['902'] = '検索条件を指定してください。';

// 発注（特寸）（hachu/hachu_tokusun.php）
$hachuTokusun = array();

$hachuTokusun['001'] = '身長が入力されていません。';
$hachuTokusun['002'] = '身長は小数点を含めて半角数値の8桁以内で入力してください。';

$hachuTokusun['011'] = '体重が入力されていません。';
$hachuTokusun['012'] = '体重は小数点を含めて半角数値の8桁以内で入力してください。';

$hachuTokusun['021'] = 'バストが入力されていません。';
$hachuTokusun['022'] = 'バストは小数点を含めて半角数値の8桁以内で入力してください。';

$hachuTokusun['031'] = 'ウエストが入力されていません。';
$hachuTokusun['032'] = 'ウエストは小数点を含めて半角数値の8桁以内で入力してください。';

$hachuTokusun['041'] = 'ヒップが入力されていません。';
$hachuTokusun['042'] = 'ヒップは小数点を含めて半角数値の8桁以内で入力してください。';

$hachuTokusun['051'] = '肩幅が入力されていません。';
$hachuTokusun['052'] = '肩幅は小数点を含めて半角数値の8桁以内で入力してください。';

$hachuTokusun['061'] = '袖丈が入力されていません。';
$hachuTokusun['062'] = '袖丈は小数点を含めて半角数値の8桁以内で入力してください。';

$hachuTokusun['071'] = '首周りが入力されていません。';
$hachuTokusun['072'] = '首周りは小数点を含めて半角数値の8桁以内で入力してください。';

$hachuTokusun['091'] = '着丈が入力されていません。';
$hachuTokusun['092'] = '着丈は小数点を含めて半角数値の8桁以内で入力してください。';

$hachuTokusun['101'] = '裄丈が入力されていません。';
$hachuTokusun['102'] = '裄丈は小数点を含めて半角数値の8桁以内で入力してください。';

$hachuTokusun['111'] = '股下が入力されていません。';
$hachuTokusun['112'] = '股下は小数点を含めて半角数値の8桁以内で入力してください。';

$hachuTokusun['081'] = '特注備考が入力されていません。';
$hachuTokusun['082'] = '特注備考は全角64文字以内で入力してください。';

$hachuTokusun['121'] = 'ヌード寸法または特注備考のどちらかを必ず入力してください。';

// 交換（特寸）（koukan/koukan_tokusun.php）
$koukanTokusun = array();

$koukanTokusun['001'] = '身長が入力されていません。';
$koukanTokusun['002'] = '身長は小数点を含めて半角数値の8桁以内で入力してください。';

$koukanTokusun['011'] = '体重が入力されていません。';
$koukanTokusun['012'] = '体重は小数点を含めて半角数値の8桁以内で入力してください。';

$koukanTokusun['021'] = 'バストが入力されていません。';
$koukanTokusun['022'] = 'バストは小数点を含めて半角数値の8桁以内で入力してください。';

$koukanTokusun['031'] = 'ウエストが入力されていません。';
$koukanTokusun['032'] = 'ウエストは小数点を含めて半角数値の8桁以内で入力してください。';

$koukanTokusun['041'] = 'ヒップが入力されていません。';
$koukanTokusun['042'] = 'ヒップは小数点を含めて半角数値の8桁以内で入力してください。';

$koukanTokusun['051'] = '肩幅が入力されていません。';
$koukanTokusun['052'] = '肩幅は小数点を含めて半角数値の8桁以内で入力してください。';

$koukanTokusun['061'] = '袖丈が入力されていません。';
$koukanTokusun['062'] = '袖丈は小数点を含めて半角数値の8桁以内で入力してください。';

$koukanTokusun['071'] = '首周りが入力されていません。';
$koukanTokusun['072'] = '首周りは小数点を含めて半角数値の8桁以内で入力してください。';

$koukanTokusun['091'] = '着丈が入力されていません。';
$koukanTokusun['092'] = '着丈は小数点を含めて半角数値の8桁以内で入力してください。';

$koukanTokusun['101'] = '裄丈が入力されていません。';
$koukanTokusun['102'] = '裄丈は小数点を含めて半角数値の8桁以内で入力してください。';

$koukanTokusun['111'] = '股下が入力されていません。';
$koukanTokusun['112'] = '股下は小数点を含めて半角数値の8桁以内で入力してください。';

$koukanTokusun['081'] = '特注備考が入力されていません。';
$koukanTokusun['082'] = '特注備考は全角64文字以内で入力してください。';

$koukanTokusun['121'] = 'ヌード寸法または特注備考のどちらかを必ず入力してください。';

// パスワード変更 （change_password.php）
$changePassword = array();

$changePassword['001'] = '現在のパスワードを入力してください。';

$changePassword['011'] = '現在のパスワードが間違っています。ご確認ください。';

$changePassword['021'] = '新しいパスワードを入力してください。';
$changePassword['022'] = '新しいパスワードが新しいパスワード（確認）と一致しません。';
// パスワード変更エラーメッセージ 追加 09/04/08 uesugi
//$changePassword['023'] = '新しいパスワードは半角で6文字～12文字で入力してください。';
$changePassword['023'] = '新しいパスワードは半角英数字で8文字～12文字で入力してください。';
//$changePassword['024'] = '新しいパスワードは初期設定とは異なるものを指定してください。';
$changePassword['024'] = '新しいパスワードは数字、英字の両方を一文字以上含めて入力してください。';
$changePassword['025'] = '新しいパスワードに初期パスワードと異なるパスワードを入力してください。';
$changePassword['026'] = '新しいパスワードに現在のパスワードと異なるパスワードを入力してください。';
$changePassword['027'] = '新しいパスワードにユーザーID文字列を含めずに入力してください。';
$changePassword['901'] = 'パスワードの変更に失敗しました。' . "\n" . 'システム管理者にお問い合わせください。';

// 申請番号重複チェック （include/checkDuplicateAppli.php）
$checkRequestNo = array();

$checkRequestNo['901'] = 'この申請番号ではすでに申請されています。';


// 職員ID重複チェック （include/checkDuplicateStaff.php）
$checkStaffID = array();

$checkStaffID['901'] = 'この職員は現在貸与中です。';


// 営業所検索 （search_comp.php）
$searchComp = array();

$searchComp['901'] = '条件に該当する営業所はありませんでした。';

// 承認処理（syounin/syounin.php）
$syounin = array();

$syounin['001'] = '承認/否認したい申請を選択してください。';
$syounin['011'] = '理由は全角30文字以内で入力してください。';

$syounin['901'] = '該当する申請情報はありませんでした。';
$syounin['902'] = '検索条件を指定してください。';
$syounin['903'] = '承認/否認の処理に失敗しました。' . "\n" . 'システム管理者にお問い合わせください。';

// 承認キャンセル処理（syounin/syounin_cancel.php）
$syouninCancel = array();

$syouninCancel['901'] = 'キャンセルに失敗しました。' . "\n" . 'システム管理者にお問い合わせください。';
$syouninCancel['902'] = 'キャンセルする情報が取得できませんでした。';


// 着用者一覧（admin/chakuyousya_ichiran.php）
$chakuyousyaIchiran = array();
$chakuyousyaIchiran['901'] = '抽出対象となるデータが存在しませんでした。';
$chakuyousyaIchiran['902'] = '検索条件を指定してください。';
$chakuyousyaIchiran['903'] = '日付を指定してください。';
$chakuyousyaIchiran['904'] = 'シーズンを選択してください。';

// 発注一括申請 (mainte/orderupresult.php)
$orderResult['001'] = '削除処理に失敗しました。';
$orderResult['002'] = 'アップロードデータが存在しません。';

// 職員一括申請 (mainte/staffupresult.php)
$staffResult['001'] = '削除処理に失敗しました。';
$staffResult['002'] = 'アップロードデータが存在しません。';

// 職員マスタメンテ (mainte/usermainte_top.php)
$userMainte['001'] = '該当する職員は存在しませんでした。';

$userMainte['011']  = '職員コードが入力されていません。';
$userMainte['012']  = '氏名が入力されていません。';
$userMainte['013']  = '営業所が選択されていません。';
$userMainte['014']  = '人事異動先情報、職員コードが正しくありません。';
$userMainte['015']  = '人事異動先情報、営業所が入力されていません。';
$userMainte['016']  = '入力された職員コードは既に存在します。';
$userMainte['017']  = '更新実施日の日付が正しくありません。';
$userMainte['018']  = '現在貸与中の商品が存在しますので削除できません。';
$userMainte['019']  = '異動先の施設を選択した場合は必ず更新実施日を入力して下さい。';
$userMainte['020']  = '服種が選択されていません。';
$userMainte['021']  = '職員コードは半角英数字12桁です。';

$userMainte['101']  = '新規追加処理に失敗しました。';
$userMainte['102']  = '更新処理に失敗しました。';
$userMainte['103']  = '削除処理に失敗しました。';

$seikyuMeisai['002'] = '集計開始日、集計終了日を入力してください。';
$seikyuMeisai['003'] = '日付はYYYY/MM/DDの形式にて、入力してください。';
$seikyuMeisai['005'] = '対象の請求対象データが存在しません。';

$taiyoList['002'] = '集計日を入力してください。';
$taiyoList['003'] = '集計日はYYYY/MM/DDの形式にて、入力してください。';
$taiyoList['005'] = '対象の請求対象データが存在しません。';

$koukanFee['002'] = '申請日を入力してください。';
$koukanFee['003'] = '申請日（開始）はYYYY/MM/DDの形式にて、入力してください。';
$koukanFee['004'] = '申請日（終了）はYYYY/MM/DDの形式にて、入力してください。';
$koukanFee['005'] = '対象の有償交換データが存在しません。';

// パスワード初期化 （clear_password.php）
// 09/03/25 uesugi
$clearPassword = array();

$clearPassword['001'] = 'ユーザーIDを入力してください。';
$clearPassword['002'] = '入力されたユーザーIDは登録されていません。ご確認ください。';
$clearPassword['901'] = 'パスワードの初期化に失敗しました。' . "\n" . 'システム管理者にお問い合わせください。';

// 定期発注用返却申請明細出力 (teikioutput/teikireturn_excel_dl.php)
$teikiOutput = array();
$teikiOutput['001'] = '会社を選択してください。';
$teikiOutput['002'] = '申請日範囲は必ず指定してください。';
$teikiOutput['003'] = '申請日範囲（開始日）はYY/MM/DD形式で入力して下さい。';
$teikiOutput['004'] = '申請日範囲（終了日）はYY/MM/DD形式で入力して下さい。';
$teikiOutput['005'] = '該当する定期発注用返却申請が存在しません。';
$teikiOutput['006'] = '本部コードを選択して下さい。';



/* ../error_message/errorMessage.php end */


/* ../include/redirectPost.php start */

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


/* ../include/redirectPost.php end */



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

//var_dump($post);die;
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

?><!DOCTYPE html>
<html>
  <head>
    <META http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <script src="//code.jquery.com/jquery-1.12.4.min.js"></script>
    <link href="/main.css" rel="stylesheet" type="text/css">
    <title>制服管理システム</title>
  </head>
  <body>
    <div id="main">
      <div align="center">
<?php if(!$isLogin) { ?>
        <table border="0" cellpadding="0" cellspacing="0" class="tb_login">
          <tr>
            <td colspan="7"><a href="<?php isset($homeUrl) ? print($homeUrl) : print('&#123;homeUrl&#125;'); ?>login.php"><img src="/img/logo.gif" alt="logo" width="163" height="42" border="0"></a><img src="/img/logo_02.gif" width="569" height="42"></td>
          </tr>
<?php } ?>
<?php if($isLogin) { ?>
       <form method="post" name="grobalMenuForm">
        <table border="0" cellpadding="0" cellspacing="0">
          <tr>
<?php if($isLevelAdmin) { ?>
            <td colspan="8">
<?php } ?>
<?php if($isLevelNormal) { ?>
            <td colspan="8">
<?php } ?>
              <a href="<?php isset($homeUrl) ? print($homeUrl) : print('&#123;homeUrl&#125;'); ?>top.php"><img src="/img/logo.gif" alt="logo" width="163" height="42" border="0"></a><img src="/img/logo_02.gif" width="569" height="42">
            </td>
          </tr>
<?php } ?>
<?php if($isLogin) { ?>
          <tr>
    </script>

    <input type="hidden" name="appliReason">

            

<?php if($isLevelAdmin) { ?>
<?php if(!$isLevelHonbu) { ?>
<?php if(!$isLevelSyonin) { ?>
<?php if($isMenuOrder) { ?>
            <td><a href="/hachu/hachu_top.php"><img src="/img/bt_06-2.gif" alt="代理発注"  border="0"></a></td>
<?php } ?>
<?php if(!$isMenuOrder) { ?>
            <td><a href="/hachu/hachu_top.php"><img src="/img/bt_06.gif" alt="代理発注"  border="0"></a></td>
<?php } ?>
<?php if($isMenuExchange) { ?>
            <td><a href="/koukan/koukan_top.php"><img src="/img/bt_07-2.gif" alt="代理交換"  border="0"></a></td>
<?php } ?>
<?php if(!$isMenuExchange) { ?>
            <td><a href="/koukan/koukan_top.php"><img src="/img/bt_07.gif" alt="代理交換"  border="0"></a></td>
<?php } ?>
<?php if($isMenuReturn) { ?>
            <td><a href="/henpin/henpin_top.php"><img src="/img/bt_08-2.gif" alt="代理返却"  border="0"></a></td>
<?php } ?>
<?php if(!$isMenuReturn) { ?>
            <td><a href="/henpin/henpin_top.php"><img src="/img/bt_08.gif" alt="代理返却"  border="0"></a></td>
<?php } ?>
<?php if($isMenuCondition) { ?>
            <td><a href="/chakuyou/chakuyou.php"><img src="/img/bt_03-2.gif" alt="貸与内容"  border="0"></a></td>
<?php } ?>
<?php if(!$isMenuCondition) { ?>
            <td><a href="/chakuyou/chakuyou.php"><img src="/img/bt_03.gif" alt="貸与内容"  border="0"></a></td>
<?php } ?>
<?php if($isMenuHistory) { ?>
            <td><a href="/rireki/rireki.php"><img src="/img/bt_04-2.gif" alt="申請履歴"  border="0"></a></td>
<?php } ?>
<?php if(!$isMenuHistory) { ?>
            <td><a href="/rireki/rireki.php"><img src="/img/bt_04.gif" alt="申請履歴"  border="0"></a></td>
<?php } ?>
<?php if($isMenuAcceptation) { ?>
            <td><a href="/syounin/syounin.php"><img src="/img/bt_09-2.gif" alt="承認" border="0"></td>
<?php } ?>
<?php if(!$isMenuAcceptation) { ?>
            <td><a href="/syounin/syounin.php"><img src="/img/bt_09.gif" alt="承認" border="0"></td>
<?php } ?>
<?php if($isMenuAdmin) { ?>
            <td><a href="/admin/admin_select.php"><img src="/img/bt_10-2.gif" alt="管理機能"  border="0"></td>
<?php } ?>
<?php if(!$isMenuAdmin) { ?>
            <td><a href="/admin/admin_select.php"><img src="/img/bt_10.gif" alt="管理機能"  border="0"></td>
<?php } ?>
<?php if($isMenuManual) { ?>
            <td><a href="<?php isset($manualUrl) ? print($manualUrl) : print('&#123;manualUrl&#125;'); ?>"><img src="/img/bt_05-2.gif" alt="マニュアル" border="0"></td>
<?php } ?>
<?php if(!$isMenuManual) { ?>
            <td><a href="<?php isset($manualUrl) ? print($manualUrl) : print('&#123;manualUrl&#125;'); ?>"><img src="/img/bt_05.gif" alt="マニュアル" border="0"></td>
<?php } ?>
<?php } ?>

<?php if($isLevelSyonin) { ?>
<?php if($isMenuOrder) { ?>
            <td><a href="/hachu/hachu_top.php"><img src="/img/bt_06-2.gif" alt="代理発注"  border="0"></a></td>
<?php } ?>
<?php if(!$isMenuOrder) { ?>
            <td><a href="/hachu/hachu_top.php"><img src="/img/bt_06.gif" alt="代理発注"  border="0"></a></td>
<?php } ?>
<?php if($isMenuExchange) { ?>
            <td><a href="/koukan/koukan_top.php"><img src="/img/bt_07-2.gif" alt="代理交換"  border="0"></a></td>
<?php } ?>
<?php if(!$isMenuExchange) { ?>
            <td><a href="/koukan/koukan_top.php"><img src="/img/bt_07.gif" alt="代理交換"  border="0"></a></td>
<?php } ?>
<?php if($isMenuReturn) { ?>
            <td><a href="/henpin/henpin_top.php"><img src="/img/bt_08-2.gif" alt="代理返却"  border="0"></a></td>
<?php } ?>
<?php if(!$isMenuReturn) { ?>
            <td><a href="/henpin/henpin_top.php"><img src="/img/bt_08.gif" alt="代理返却"  border="0"></a></td>
<?php } ?>
<?php if($isMenuCondition) { ?>
            <td><a href="/chakuyou/chakuyou.php"><img src="/img/bt_03-2.gif" alt="貸与内容"  border="0"></a></td>
<?php } ?>
<?php if(!$isMenuCondition) { ?>
            <td><a href="/chakuyou/chakuyou.php"><img src="/img/bt_03.gif" alt="貸与内容"  border="0"></a></td>
<?php } ?>
<?php if($isMenuHistory) { ?>
            <td><a href="/rireki/rireki.php"><img src="/img/bt_04-2.gif" alt="申請履歴"  border="0"></a></td>
<?php } ?>
<?php if(!$isMenuHistory) { ?>
            <td><a href="/rireki/rireki.php"><img src="/img/bt_04.gif" alt="申請履歴"  border="0"></a></td>
<?php } ?>
            <td><img src="/img/bt_00.gif" border="0"></td>
<?php if($isMenuAdmin) { ?>
            <td><a href="/admin/admin_select.php"><img src="/img/bt_10-2.gif" alt="管理機能"  border="0"></td>
<?php } ?>
<?php if(!$isMenuAdmin) { ?>
            <td><a href="/admin/admin_select.php"><img src="/img/bt_10.gif" alt="管理機能"  border="0"></td>
<?php } ?>
<?php if($isMenuManual) { ?>
            <td><a href="<?php isset($manualUrl) ? print($manualUrl) : print('&#123;manualUrl&#125;'); ?>"><img src="/img/bt_05-2.gif" alt="マニュアル" border="0"></td>
<?php } ?>
<?php if(!$isMenuManual) { ?>
            <td><a href="<?php isset($manualUrl) ? print($manualUrl) : print('&#123;manualUrl&#125;'); ?>"><img src="/img/bt_05.gif" alt="マニュアル" border="0"></td>
<?php } ?>
<?php } ?>
<?php } ?>
<?php if($isLevelHonbu) { ?>
<?php if($isMenuCondition) { ?>
            <td><a href="/chakuyou/chakuyou.php"><img src="/img/bt_03-2.gif" alt="貸与内容"  border="0"></a></td>
<?php } ?>
<?php if(!$isMenuCondition) { ?>
            <td><a href="/chakuyou/chakuyou.php"><img src="/img/bt_03.gif" alt="貸与内容"  border="0"></a></td>
<?php } ?>
<?php if($isMenuHistory) { ?>
            <td><a href="/rireki/rireki.php"><img src="/img/bt_04-2.gif" alt="申請履歴"  border="0"></a></td>
<?php } ?>
<?php if(!$isMenuHistory) { ?>
            <td><a href="/rireki/rireki.php"><img src="/img/bt_04.gif" alt="申請履歴"  border="0"></a></td>
<?php } ?>
            <td><img src="/img/bt_00.gif" border="0"></td>
            <td><img src="/img/bt_00.gif" border="0"></td>
            <td><img src="/img/bt_00.gif" border="0"></td>
            <td><img src="/img/bt_00.gif" border="0"></td>
<?php if($isMenuAdmin) { ?>
            <td><a href="/admin/admin_select.php"><img src="/img/bt_10-2.gif" alt="管理機能"  border="0"></td>
<?php } ?>
<?php if(!$isMenuAdmin) { ?>
            <td><a href="/admin/admin_select.php"><img src="/img/bt_10.gif" alt="管理機能"  border="0"></td>
<?php } ?>
<?php if($isMenuManual) { ?>
            <td><a href="<?php isset($manualUrl) ? print($manualUrl) : print('&#123;manualUrl&#125;'); ?>"><img src="/img/bt_05-2.gif" alt="マニュアル" border="0"></td>
<?php } ?>
<?php if(!$isMenuManual) { ?>
            <td><a href="<?php isset($manualUrl) ? print($manualUrl) : print('&#123;manualUrl&#125;'); ?>"><img src="/img/bt_05.gif" alt="マニュアル" border="0"></td>
<?php } ?>
<?php } ?>
 
<?php } ?>
            

<?php if($isLevelNormal) { ?>
<?php if($isMenuOrder) { ?>
            <td><a href="/hachu/hachu_top.php"><img src="/img/bt_01-2.gif" alt="発注"  border="0"></a></td>
<?php } ?>
<?php if(!$isMenuOrder) { ?>
            <td><a href="/hachu/hachu_top.php"><img src="/img/bt_01.gif" alt="発注"  border="0"></a></td>
<?php } ?>
<?php if($isMenuExchange) { ?>
            <td><a href="/koukan/koukan_top.php"><img src="/img/bt_12-2.gif" alt="交換"  border="0"></a></td>
<?php } ?>
<?php if(!$isMenuExchange) { ?>
            <td><a href="/koukan/koukan_top.php"><img src="/img/bt_12.gif" alt="交換"  border="0"></a></td>
<?php } ?>
<?php if($isMenuReturn) { ?>
            <td><a href="/henpin/henpin_top.php"><img src="/img/bt_02-2.gif" alt="返却"  border="0"></a></td>
<?php } ?>
<?php if(!$isMenuReturn) { ?>
            <td><a href="/henpin/henpin_top.php"><img src="/img/bt_02.gif" alt="返却"  border="0"></a></td>
<?php } ?>
<?php if($isMenuCondition) { ?>
            <td><a href="/chakuyou/chakuyou.php"><img src="/img/bt_03-2.gif" alt="貸与内容"  border="0"></a></td>
<?php } ?>
<?php if(!$isMenuCondition) { ?>
            <td><a href="/chakuyou/chakuyou.php"><img src="/img/bt_03.gif" alt="貸与内容"  border="0"></a></td>
<?php } ?>
<?php if($isMenuHistory) { ?>
            <td><a href="/rireki/rireki.php"><img src="/img/bt_04-2.gif" alt="申請履歴"  border="0"></a></td>
<?php } ?>
<?php if(!$isMenuHistory) { ?>
            <td><a href="/rireki/rireki.php"><img src="/img/bt_04.gif" alt="申請履歴"  border="0"></a></td>
<?php } ?>
            <td><img src="/img/bt_00.gif" border="0"></td>
<?php if($isMenuAdmin) { ?>
            <td><a href="/admin/admin_select.php"><img src="/img/bt_10-2.gif" alt="管理機能"  border="0"></td>
<?php } ?>
<?php if(!$isMenuAdmin) { ?>
            <td><a href="/admin/admin_select.php"><img src="/img/bt_10.gif" alt="管理機能"  border="0"></td>
<?php } ?>

<?php if($isMenuManual) { ?>
            <td><a href="<?php isset($manualUrl) ? print($manualUrl) : print('&#123;manualUrl&#125;'); ?>"><img src="/img/bt_05-2.gif" alt="マニュアル" border="0"></a></td>
<?php } ?>
<?php if(!$isMenuManual) { ?>
            <td><a href="<?php isset($manualUrl) ? print($manualUrl) : print('&#123;manualUrl&#125;'); ?>"><img src="/img/bt_05.gif" alt="マニュアル" border="0"></a></td>
<?php } ?>
    <input type="hidden" name="appliReason">

    <script language="JavaScript">
    <!--
    function MoveNext(source, appliReason) {
      document.grobalMenuForm.appliReason.value = '1';
      document.grobalMenuForm.action = source; 
      document.grobalMenuForm.submit();
     
      return false;

    }
    // -->
    </script>

<?php } ?>
          </tr>
          <tr>
<?php if($isLevelAdmin) { ?>
            <td colspan="8" class="headimg" height="20px" align="right">
<?php } ?>
<?php if($isLevelNormal) { ?>
            <td colspan="8" class="headimg" height="20px" align="right">
<?php } ?>
             <font size="2"><?php isset($userCd) ? print($userCd) : print('&#123;userCd&#125;'); ?>:<?php isset($userNm) ? print($userNm) : print('&#123;userNm&#125;'); ?></font>&nbsp;&nbsp;<a href="<?php isset($homeUrl) ? print($homeUrl) : print('&#123;homeUrl&#125;'); ?>login.php"><img src="/img/logout.gif" alt="ログアウト" width="82" height="21" border="0"></a>
            </td>
          </tr>
<?php } ?>
        </table>
       </form>
        <div id="contents">
          <h1>エラーメッセージ表示</h1>
          <table border="0" width="700" cellpadding="0" cellspacing="0" class="tb_1">
            <tr height="100">
              <td align="center">
                <div class="ftbold">
<?php for ($i1_errors=0; $i1_errors<count($errors); $i1_errors++) { ?>
                  
                  <?php isset($errors[$i1_errors]['errorMessage']) ? print(nl2br($errors[$i1_errors]['errorMessage'])) : print('&#123;br(errors.errorMessage)&#125;'); ?><br>
                  
<?php } ?>
                </div>
              </td>
            </tr>
            <tr>
              <td>
                <form action="<?php isset($returnUrl) ? print($returnUrl) : print('&#123;returnUrl&#125;'); ?>" name="errorForm" method="post">
                  
                  <div class="bot" align="center"><a href="#" onclick="document.errorForm.submit(); return false;"><img src="./img/modoru.gif" alt="戻る" width="112" height="32" border="0"></a></div>
                  
<?php for ($i1_hiddenHtml=0; $i1_hiddenHtml<count($hiddenHtml); $i1_hiddenHtml++) { ?>
        <input type="hidden" value="<?php isset($hiddenHtml[$i1_hiddenHtml]['value']) ? print($hiddenHtml[$i1_hiddenHtml]['value']) : print('&#123;hiddenHtml.value&#125;'); ?>" name="<?php isset($hiddenHtml[$i1_hiddenHtml]['name']) ? print($hiddenHtml[$i1_hiddenHtml]['name']) : print('&#123;hiddenHtml.name&#125;'); ?>">
<?php } ?>
                </form>
              </td>
            </tr>
          </table>
          <br>
        </div>
        <br><br><br>
      </div>
    </div>
  </body>
</html>

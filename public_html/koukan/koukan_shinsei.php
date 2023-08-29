<?php
/*
 * 交換申請入力画面
 * koukan_shinsei.src.php
 *
 * create 2007/03/19 H.Osugi
 *
 *
 */

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


/* ../../include/getComp.php start */

/*
 * 店舗情報取得モジュール
 * getComp.php
 *
 * create 2007/03/13 H.Osugi
 *
 */

/*
 * 店舗情報を取得する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$compId    => 店舗ID
 *       ：$isEntity => 0：エンティティしない / エンティティする
 * 戻り値：店舗情報（array）
 *
 * create 2007/03/13 H.Osugi
 *
 */
function getComp($dbConnect, $compId, $isEntity = 0) {

	// 初期化
	$result = array();

	//compCdが空なら処理を終了
	if ($compId == '' && $compId !== 0) {
		return $result;
	}

	// 該当の店舗情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" CompID,";
	$sql .= 	" CompCd,";
	$sql .= 	" CompName,";
	$sql .= 	" Zip,";
	$sql .= 	" Adrr,";
	$sql .= 	" Tel";
	$sql .= " FROM";
	$sql .= 	" M_Comp";
	$sql .= " WHERE";
	$sql .= 	" CompID = '" . db_Escape($compId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;
	
	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	// エンティティ処理を行わない場合
	if ($isEntity == 0) {
		return $result[0];
	}
	
	$result[0]['CompName'] = htmlspecialchars($result[0]['CompName'], HTMLENTITY_QUOTE_STYLE, HTMLENTITY_ENCODE);
	$result[0]['Zip']      = htmlspecialchars($result[0]['Zip'], HTMLENTITY_QUOTE_STYLE, HTMLENTITY_ENCODE);
	$result[0]['Adrr']     = htmlspecialchars($result[0]['Adrr'], HTMLENTITY_QUOTE_STYLE, HTMLENTITY_ENCODE);
	$result[0]['Tel']      = htmlspecialchars($result[0]['Tel'], HTMLENTITY_QUOTE_STYLE, HTMLENTITY_ENCODE);

	return $result[0];

}


/* ../../include/getComp.php end */


/* ../../include/getUser.php start */

/*
 * ユーザー情報取得モジュール
 * getUser.php
 *
 * create 2007/03/13 H.Osugi
 *
 */

/*
 * ユーザー名を取得する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$nameCd    => ユーザID
 *       ：$isEntity => 0：エンティティしない / エンティティする
 * 戻り値：ユーザー名
 *
 * create 2007/03/13 H.Osugi
 *
 */
function getUserName($dbConnect, $nameCd, $isEntity = 0) {

	// 初期化
	$result = array();

	// nameCdが空なら処理を終了
	if ($nameCd == '' && $nameCd !== 0) {
		return false;
	}

	// 該当のユーザー名を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" Name";
	$sql .= " FROM";
	$sql .= 	" M_User";
	$sql .= " WHERE";
	$sql .= 	" NameCd = '" . db_Escape($nameCd) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;
	
	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0 || !isset($result[0]['Name'])) {
	 	return false;
	}

	// エンティティ処理を行わない場合
	if ($isEntity == 0) {
		return $result[0]['Name'];
	}
	
	$result[0]['Name'] = htmlspecialchars($result[0]['Name'], HTMLENTITY_QUOTE_STYLE, HTMLENTITY_ENCODE);

	return $result[0]['Name'];
	
}


/* ../../include/getUser.php end */


/* ../../include/getSize.php start */

/*
 * サイズ取得モジュール
 * getSize.php
 *
 * create 2007/03/13 H.Osugi
 *
 */

/*
 * サイズの各項目を取得する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$sizeID    => サイズID
 *       ：$isEntity => 0：エンティティしない / 1：エンティティする
 * 戻り値：サイズ情報（array）
 *
 * create 2007/03/13 H.Osugi
 *
 */
function getSize($dbConnect, $sizeID, $isEntity = 0) {

	// 初期化
	$result = array();

	//sizeIDが空なら処理を終了
	if ($sizeID == '' && $sizeID !== 0) {
		return $result;
	}

	// 該当のサイズ情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" Size1,";
	$sql .= 	" Size2,";
	$sql .= 	" Size3,";
	$sql .= 	" Size4,";
	$sql .= 	" Size5,";
	$sql .= 	" Size6,";
	$sql .= 	" Size7,";
	$sql .= 	" Size8,";
	$sql .= 	" Size9,";
	$sql .= 	" Size10,";
	$sql .= 	" Size11,";
	$sql .= 	" Size12,";
	$sql .= 	" Size13,";
	$sql .= 	" Size14,";
	$sql .= 	" Size15,";
	$sql .= 	" Size16,";
	$sql .= 	" Size17,";
	$sql .= 	" Size18,";
	$sql .= 	" Size19,";
	$sql .= 	" Size20,";
	$sql .= 	" Size21,";
	$sql .= 	" Size22,";
	$sql .= 	" Size23,";
	$sql .= 	" Size24,";
	$sql .= 	" Size25,";
	$sql .= 	" Size26,";
	$sql .= 	" Size27,";
	$sql .= 	" Size28,";
	$sql .= 	" Size29,";
	$sql .= 	" Size30,";
	$sql .= 	" Size31,";
	$sql .= 	" Size32,";
	$sql .= 	" Size33,";
	$sql .= 	" Size34,";
	$sql .= 	" Size35,";
	$sql .= 	" Size36,";
	$sql .= 	" Size37,";
	$sql .= 	" Size38,";
	$sql .= 	" Size39,";
    $sql .=     " Size40,";
    $sql .=     " Size41,";
    $sql .=     " Size42,";
    $sql .=     " Size43,";
    $sql .=     " Size44,";
    $sql .=     " Size45,";
    $sql .=     " Size46,";
    $sql .=     " Size47,";
    $sql .=     " Size48,";
    $sql .=     " Size49,";
    $sql .=     " Size50,";
    $sql .=     " Size51,";
    $sql .=     " Size52,";
    $sql .=     " Size53,";
    $sql .=     " Size54,";
    $sql .=     " Size55,";
    $sql .=     " Size56,";
    $sql .=     " Size57,";
    $sql .=     " Size58,";
    $sql .=     " Size59,";
    $sql .=     " Size60,";
    $sql .=     " Size61,";
    $sql .=     " Size62,";
    $sql .=     " Size63,";
    $sql .=     " Size64,";
    $sql .=     " Size65,";
    $sql .=     " Size66,";
    $sql .=     " Size67,";
    $sql .=     " Size68,";
    $sql .=     " Size69,";
    $sql .=     " Size70,";
    $sql .=     " Size71,";
    $sql .=     " Size72,";
    $sql .=     " Size73,";
    $sql .=     " Size74,";
    $sql .=     " Size75,";
    $sql .=     " Size76,";
    $sql .=     " Size77,";
    $sql .=     " Size78,";
    $sql .=     " Size79,";
    $sql .=     " Size80,";
    $sql .=     " Size81,";
    $sql .=     " Size82,";
    $sql .=     " Size83,";
    $sql .=     " Size84,";
    $sql .=     " Size85,";
    $sql .=     " Size86,";
    $sql .=     " Size87,";
    $sql .=     " Size88,";
    $sql .=     " Size89,";
    $sql .=     " Size90,";
    $sql .=     " Size91,";
    $sql .=     " Size92,";
    $sql .=     " Size93,";
    $sql .=     " Size94,";
    $sql .=     " Size95,";
    $sql .=     " Size96,";
    $sql .=     " Size97,";
    $sql .=     " Size98,";
    $sql .=     " Size99";
	$sql .= " FROM";
	$sql .= 	" M_Size";
	$sql .= " WHERE";
	$sql .= 	" SizeID = '" . db_Escape($sizeID) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	// データが存在しないカラムは消去する
	for ($i=1; $i<=99; $i++) {
		if ($result[0]['Size' .$i] == '') {
			unset($result[0]['Size' .$i]);
		}
    }

	// エンティティ処理を行わない場合
	if ($isEntity == 0) {
		return $result[0];
	}

	// size1～size99の値をHTMLエンティティ
	for ($i=1; $i<=99; $i++) {
		if (!isset($result[0]['Size' .$i]) || $result[0]['Size' .$i] == '') {
			continue;
		}
		$result[0]['Size' .$i] = htmlspecialchars($result[0]['Size' . $i], HTMLENTITY_QUOTE_STYLE, HTMLENTITY_ENCODE);
    }
	return  $result[0];
	
}

/*
 * アイテムIDからサイズの各項目を取得する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$itemID    => アイテムID
 *       ：$isEntity => 0：エンティティしない / 1：エンティティする
 * 戻り値：サイズ情報（array）
 *
 * create 2007/03/13 H.Osugi
 *
 */
function getSizeByItem($dbConnect, $itemID, $isEntity = 0) {

    if ($itemID == '' || is_null($itemID)) {
        return false;
    }

    $sql = '';
    $sql .= ' SELECT';
    $sql .=     ' SizeID';
    $sql .= ' FROM';
    $sql .=     ' M_Item';
    $sql .= ' WHERE';
    $sql .=     ' ItemID = '.$itemID;
    $sql .= ' AND';
    $sql .=     ' Del = '.DELETE_OFF;

    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合
    if (count($result) <= 0) {
        $result = array();
        return $result;
    }

    if (isset($result[0]['SizeID']) && $result[0]['SizeID'] != '') {
        return getSize($dbConnect, $result[0]['SizeID'], $isEntity);
    } else {
        return false;
    }
}

/*
 * OrderDetIDからサイズの各項目を取得する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$itemID    => アイテムID
 *       ：$isEntity => 0：エンティティしない / 1：エンティティする
 * 戻り値：サイズ情報（array）
 *
 * create 2007/03/13 H.Osugi
 *
 */
function getSizeByOrderDetId($dbConnect, $orderDetID, $isEntity = 0) {

    if ($orderDetID == '' || is_null($orderDetID)) {
        return false;
    }

    $sql = '';
    $sql .= ' SELECT';
    $sql .=     ' MI.SizeID';
    $sql .= ' FROM';
    $sql .=     ' M_Item MI';
    $sql .=     ' INNER JOIN';
    $sql .=     ' T_Order_Details TOD';
    $sql .=     ' ON';
    $sql .=         ' TOD.ItemID = MI.ItemID';
    $sql .= ' WHERE';
    $sql .=     ' TOD.OrderDetID = '.$orderDetID;
    $sql .= ' AND';
    $sql .=     ' TOD.Del = '.DELETE_OFF;
    $sql .= ' AND';
    $sql .=     ' MI.Del = '.DELETE_OFF;

    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合
    if (count($result) <= 0) {
        $result = array();
        return $result;
    }

    if (isset($result[0]['SizeID']) && $result[0]['SizeID'] != '') {
        return getSize($dbConnect, $result[0]['SizeID'], $isEntity);
    } else {
        return false;
    }
}

/*
 * リストボックスを生成する値に成型する
 * 引数  ：$sizeDatas      => サイズ情報（array）
 *       ：$selectedValue  => 選択されたvalueの値
 * 戻り値：$selectDatas    => リストボックスを生成するための値(array)
 *
 * create 2007/03/13 H.Osugi
 *
 */
function castListboxSize($sizeDatas, $selectedValue = '') {

	// 初期化
	$selectDatas = array();

	// $sizeDatasが配列でなければ終了
	if (!is_array($sizeDatas)) {
		return  $selectDatas;
	}

	// $sizeDatasにデータが1件もなければ終了
	if (count($sizeDatas) <= 0) {
		return  $selectDatas;
	}

	// リストボックスを生成するための値に成型
	$listCount = count($sizeDatas);
	for ($i=0; $i<$listCount; $i++) {

		// サイズ情報が空ならば処理をスルー
		if ($sizeDatas['Size' . ($i+1)] == '') {
			continue;
		}

		$selectDatas[$i]['selected'] = false;
		if ('Size' . ($i+1) == $selectedValue) {
			$selectDatas[$i]['selected'] = true;
		}

		$selectDatas[$i]['value']     = 'Size' . ($i+1);

//		$selectDatas[$i]['isKantoku'] = false;
		// HTMLエンティティ処理
		$selectDatas[$i]['display'] = htmlspecialchars($sizeDatas['Size' . ($i+1)], HTMLENTITY_QUOTE_STYLE, HTMLENTITY_ENCODE);

//		if (strpos($selectDatas[$i]['display'], "特") !== false && $selectDatas[$i]['display'] != "特寸") {
//			$selectDatas[$i]['isKantoku'] = true;
//		}
	}

	return $selectDatas;

}


/* ../../include/getSize.php end */


/* ../../include/getStaff.php start */

/*
 * スタッフ情報取得モジュール
 * getStaff.php
 *
 * create 2007/03/19 H.Osugi
 *
 */

/*
 * スタッフコードとStaffIDを一覧取得する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$compId    => 店舗ID
 *       ：$mode      => 取得モード 1:サイズ交換 2:汚損・破損交換 3:紛失交換 4:不良品交換 / 11:退職・異動返却 12:その他返却
 *       ：$isEntity  => 0：エンティティしない / 1：エンティティする
 * 戻り値：サイズ情報（array）
 *
 * create 2007/03/19 H.Osugi
 *
 */
function getStaffAll($dbConnect, $compId, $mode, $isEntity = 0) {

	// 初期化
	$result = array();

	// スタッフコードの一覧を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" DISTINCT";
	$sql .= 	" ts.StaffID,";
	$sql .= 	" ts.StaffCode";
	$sql .= " FROM";
	$sql .= 	" T_Staff ts";
	$sql .= " INNER JOIN";
	$sql .= 	" T_Staff_Details tsd";
	$sql .= " ON";
	$sql .= 	" ts.StaffID = tsd.StaffID";
	$sql .= " AND";
	$sql .= 	" tsd.Del = " . DELETE_OFF;
	$sql .= " INNER JOIN";
	$sql .= 	" T_Order_Details tod";
	$sql .= " ON";
	$sql .= 	" tsd.OrderDetID = tod.OrderDetID";
	$sql .= " AND";
	$sql .= 	" tod.Del = " . DELETE_OFF;

	// サイズ交換の場合はSizeが１つの商品ははぶく
	if ($mode == 1) {
		$sql .= " INNER JOIN";
		$sql .= 	" M_Item mi";
		$sql .= " ON";
		$sql .= 	" tod.ItemID = mi.ItemID";
		$sql .= " AND";
		$sql .= 	" mi.Del = " . DELETE_OFF;
	}

	$sql .= " WHERE";
	$sql .= 	" ts.CompID = '" . db_Escape($compId) . "'";
	$sql .= " AND";
	$sql .= 	" tsd.Status IN ( " . STATUS_SHIP . ", ". STATUS_DELIVERY .")";		// ステータスが出荷済(15),納品済(16)

	// サイズ交換の場合はSizeが１つの商品ははぶく
	if ($mode == 1) {
		$sql .= " AND";
		$sql .= 	" mi.SizeID <> 3";
	}

	////$sql .= " AND";
	////$sql .= 	" ts.AllReturnFlag = 0";
	$sql .= " AND";
	$sql .= 	" ts.Del = " . DELETE_OFF;
	$sql .= " ORDER BY";
	$sql .= 	" ts.StaffCode ASC";

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	// エンティティ処理を行わない場合
	if ($isEntity == 0) {
		return $result;
	}

	// 取得した値をHTMLエンティティ
	$resultCount = count($result);
	for ($i=0; $i<$resultCount; $i++) {
		$result[$i]['StaffCode'] = htmlspecialchars($result[$i]['StaffCode'], HTMLENTITY_QUOTE_STYLE, HTMLENTITY_ENCODE);
		$result[$i]['StaffID'] = $result[$i]['StaffID'];
	}
	
	return  $result;
	
}

/*
 * スタッフコードとStaffIDを取得する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$compId    => 店舗ID
 *       ：$staffCode => スタッフコード
 *       ：$mode      => 取得モード 1:サイズ交換 2:汚損・破損交換 3:紛失交換 4:不良品交換 / 11:退職・異動返却 12:その他返却
 *       ：$isEntity  => 0：エンティティしない / 1：エンティティする
 * 戻り値：サイズ情報（array）
 *
 * create 2007/04/10 H.Osugi
 *
 */
function getStaff($dbConnect, $compId, $staffCode, $mode, $isEntity = 0) {

	// 初期化
	$result = array();

	// スタッフコードの一覧を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" DISTINCT";
	$sql .= 	" ts.StaffID,";
	$sql .= 	" ts.StaffCode";
	$sql .= " FROM";
	$sql .= 	" T_Staff ts";
	$sql .= " INNER JOIN";
	$sql .= 	" T_Staff_Details tsd";
	$sql .= " ON";
	$sql .= 	" ts.StaffID = tsd.StaffID";
	$sql .= " AND";
	$sql .= 	" tsd.Del = " . DELETE_OFF;
	$sql .= " INNER JOIN";
	$sql .= 	" T_Order_Details tod";
	$sql .= " ON";
	$sql .= 	" tsd.OrderDetID = tod.OrderDetID";
	$sql .= " AND";
	$sql .= 	" tod.Del = " . DELETE_OFF;

	// サイズ交換の場合はSizeが１つの商品ははぶく
	if ($mode == 1) {
		$sql .= " INNER JOIN";
		$sql .= 	" M_Item mi";
		$sql .= " ON";
		$sql .= 	" tod.ItemID = mi.ItemID";
		$sql .= " AND";
		$sql .= 	" mi.Del = " . DELETE_OFF;
	}

	$sql .= " WHERE";
	$sql .= 	" ts.CompID = '" . db_Escape($compId) . "'";
    if (isSetValue($staffCode)) {
    	$sql .= " AND";
    	$sql .= 	" ts.StaffCode = '" . db_Escape($staffCode) . "'";
    }

	switch ($mode) {
		case 11:			// 退職・異動返却の場合のみ返却未申請のユニフォームを持っているスタッフを表示する
			$sql .= " AND";
			$sql .= 	" tsd.Status IN ( " . STATUS_SHIP . ", ". STATUS_DELIVERY ." , " . STATUS_RETURN_NOT_APPLY . ")";		// ステータスが出荷済(15),納品済(16),返却未申請（25）
			break;
		default:
			$sql .= " AND";
			$sql .= 	" tsd.Status IN ( " . STATUS_SHIP . ", ". STATUS_DELIVERY .")";		// ステータスが出荷済(15),納品済(16)
			break;
	}

	// サイズ交換の場合はSizeが１つの商品ははぶく
	if ($mode == 1) {
		$sql .= " AND";
		$sql .= 	" mi.SizeID <> 3";
	}

	////$sql .= " AND";
	////$sql .= 	" ts.AllReturnFlag = 0";
	$sql .= " AND";
	$sql .= 	" ts.Del = " . DELETE_OFF;
	$sql .= " ORDER BY";
	$sql .= 	" ts.StaffCode ASC";

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	// エンティティ処理を行わない場合
	if ($isEntity == 0) {
		return $result;
	}

	// 取得した値をHTMLエンティティ
	$resultCount = count($result);
	for ($i=0; $i<$resultCount; $i++) {
		$result[$i]['StaffCode'] = htmlspecialchars($result[$i]['StaffCode'], HTMLENTITY_QUOTE_STYLE, HTMLENTITY_ENCODE);
		$result[$i]['StaffID'] = $result[$i]['StaffID'];
	}
	
	return  $result;
	
}


/*
 * スタッフコードを取得する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$staffId    => StaffID
 *       ：$isEntity  => 0：エンティティしない / 1：エンティティする
 * 戻り値：スタッフコード
 *
 * create 2007/04/06 H.Osugi
 *
 */
function getStaffCode($dbConnect, $staffId, $isEntity = 0) {

	// 初期化
	$result = array();

	// スタッフコードを取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" StaffCode";
	$sql .= " FROM";
	$sql .= 	" T_Staff";
	$sql .= " WHERE";
	$sql .= 	" StaffID = '" . db_Escape($staffId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (!isset($result[0]['StaffCode']) || count($result) <= 0) {
	 	return false;
	}

	$staffCode = $result[0]['StaffCode'];

	// エンティティ処理を行わない場合
	if ($isEntity == 0) {
		return $staffCode;
	}

	// 取得した値をHTMLエンティティ
	$staffCode = htmlspecialchars($staffCode, HTMLENTITY_QUOTE_STYLE, HTMLENTITY_ENCODE);
	
	return  $staffCode;
	
}

/*
 * スタッフ名を取得する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$staffId    => StaffID
 *       ：$isEntity  => 0：エンティティしない / 1：エンティティする
 * 戻り値：スタッフコード
 *
 * create 2007/04/06 H.Osugi
 *
 */
function getStaffName($dbConnect, $staffId, $isEntity = 0) {

    // 初期化
    $result = array();

    // スタッフコードを取得する
    $sql  = "";
    $sql .= " SELECT";
    $sql .=     " MS.PersonName";
    $sql .= " FROM";
    $sql .=     " T_Staff TS";
    $sql .=     " INNER JOIN";
    $sql .=     " M_Staff MS";
    $sql .=     " ON";
    $sql .=     " MS.StaffSeqID = TS.StaffID";
    $sql .= " WHERE";
    $sql .=     " TS.StaffID = '" . db_Escape($staffId) . "'";
    $sql .= " AND";
    $sql .=     " TS.Del = " . DELETE_OFF;
    $sql .= " AND";
    $sql .=     " MS.Del = " . DELETE_OFF;

    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合
    if (!isset($result[0]['PersonName']) || count($result) <= 0) {
        return false;
    }

    $personName = $result[0]['PersonName'];

    // エンティティ処理を行わない場合
    if ($isEntity == 0) {
        return $personName;
    }

    // 取得した値をHTMLエンティティ
    $$personName = htmlspecialchars($personName, HTMLENTITY_QUOTE_STYLE, HTMLENTITY_ENCODE);
    
    return  $personName;
    
}


/* ../../include/getStaff.php end */


/* ../../include/createRequestNo.php start */

/*
 * 申請番号生成モジュール
 * createRequestNo.php
 *
 * create 2007/03/15 H.Osugi
 *
 */

/*
 * 申請番号を生成する
 * 引数  ：$dbConnect   => コネクションハンドラ
 *       ：$compCd      => 店舗コード
 *       ：$requestCode => 申請/返却コードフラグ 1：申請 / 2：返却 / 3：交換
 * 戻り値：$requestNo   => 申請番号 / 生成失敗時は false
 *
 * create 2007/03/15 H.Osugi
 *
 */
function createRequestNo($dbConnect, $compId, $requestCode) {

	// 初期化
	$isError      = false;
	$requestNo    = '';

	//compIdが空の場合
	if ($compId == '' && $compId !== 0) {
		return false;
	}

	// トランザクション開始
	db_Transaction_Begin($dbConnect);

	// 該当の店舗情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" CompCode,";
	$sql .= 	" Cycle,";
	$sql .= 	" GETDATE() as now";
	$sql .= " FROM";
	$sql .= 	" M_Comp";
	$sql .= " WHERE";
	$sql .= 	" CompId = '" . db_Escape($compId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;
	
	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0 || !isset($result[0]['CompCode']) || !isset($result[0]['Cycle']) || !isset($result[0]['now'])) {
		// ロールバック
		db_Transaction_Rollback($dbConnect);
		return false;
	}

	// 申請番号の1文字目を生成
	switch ($requestCode) {
		case '1':		// 申請
			$requestNo .= 'A';
			break;		// 返却
		case '2':
			$requestNo .= 'R';
			break;
		case '3':		// 交換（交換の場合は頭文字（A or R）を付けずに生成）
			break;
		default:
			$isError = true;
			break;
	}

	if ($isError == true) {
		// ロールバック
		db_Transaction_Rollback($dbConnect);
		return false;
	}

	// 店舗毎のコードを付加
	$requestNo .= $result[0]['CompCode'];

	// 該当の店舗情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" CompCode,";
	$sql .= 	" Cycle,";
	$sql .= 	" UpdDay,";
	$sql .= 	" GETDATE() as now";
	$sql .= " FROM";
	$sql .= 	" M_Comp";
	$sql .= " WHERE";
	$sql .= 	" CompId = '" . db_Escape($compId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;
	
	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0 || !isset($result[0]['CompCode'])
		 || !isset($result[0]['Cycle']) || !isset($result[0]['UpdDay'])
		 || !isset($result[0]['now']))
	{
		// ロールバック
		db_Transaction_Rollback($dbConnect);
		return false;
	}
	
	// DBの現在日時を基にコードを付加
	$date = date('ymd', strtotime($result[0]['now']));
	$requestNo .= $date;

	// 更新日付が現在日付よりも古い場合採番の基準値を0に戻す
	if (date('Ymd', strtotime($result[0]['UpdDay'])) < date('Ymd', strtotime($result[0]['now']))) {

		// DBの採番の基準値を変更する
		$sql  = "";
		$sql .= " UPDATE";
		$sql .= 	" M_Comp";
		$sql .= " SET";
		$sql .= 	" Cycle   = 0,";
		$sql .= 	" UpdDay  = GETDATE(),";
		$sql .= 	" UpdUser = '" . db_Escape($_SESSION['NAMECODE']) . "'";
		$sql .= " WHERE";
		$sql .= 	" CompId = '" . db_Escape($compId) . "'";
		$sql .= " AND";
		$sql .= 	" Del = " . DELETE_OFF;
		
		$isSuccess = db_Execute($dbConnect, $sql);
	
		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			// ロールバック
			db_Transaction_Rollback($dbConnect);
			return false;
		}
		
		$result[0]['Cycle'] = 0;
		
	}

	$cycleNo = $result[0]['Cycle'] + 1;

	// 採番に問題がある場合
	if ($cycleNo > 9999 || $cycleNo <= 0) {
		// ロールバック
		db_Transaction_Rollback($dbConnect);
		return false;
	}

	$requestNo .= sprintf('%04d', $cycleNo);

	// DBの採番の基準値を変更する
	$sql  = "";
	$sql .= " UPDATE";
	$sql .= 	" M_Comp";
	$sql .= " SET";
	$sql .= 	" Cycle   = Cycle + 1,";
	$sql .= 	" UpdDay  = GETDATE(),";
	$sql .= 	" UpdUser = '" . db_Escape($_SESSION['NAMECODE']) . "'";
	$sql .= " WHERE";
	$sql .= 	" CompId = '" . db_Escape($compId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;
	
	$isSuccess = db_Execute($dbConnect, $sql);

	// 実行結果が失敗の場合
	if ($isSuccess == false) {
		// ロールバック
		db_Transaction_Rollback($dbConnect);
		return false;
	}

	// コミット
	db_Transaction_Commit($dbConnect);
	return $requestNo;
	
}


/* ../../include/createRequestNo.php end */


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


/* ../../include/commonFunc.php start */

/*
 * 共通関数モジュール
 * commonFunc.php
 *
 * create 2008/04/14 W.Takasaki
 *
 */


/*
 * 値が空値かどうかを検査する
 * 引数  ：$param       => 検査する値 
 * 戻り値：true=>値がセットされている false=>値がセットされていない(empty,null)
 *
 * create 2008/04/14 W.Takasaki
 *
 */
function isSetValue($value) {
    $result = false;
    if (is_array($value)) {
        foreach ($value as $key => $val) {
            if (isSetValue($val)) {
                $result = true;
            }
        }
    } else {
        if (isset($value) && !is_null($value) && $value != '') {
            $result = true;
        }
    }
    return $result;
}

/*
 * TOPにリダイレクトする
 * 引数  ：なし 
 * 戻り値：なし
 *
 * create 2008/04/15 W.Takasaki
 *
 */
function redirectTop($hiddens = array()) {
    $returnUrl = HOME_URL . 'top.php';
    redirectPost($returnUrl, $hiddens);
}

/*
 * ページヘッダー部分に表示する情報を取得する
 * 引数     ：$dbConnect => コネクションハンドラ
 *       ：$staffID   => スタッフID
 * 戻り値：$result     => 表示する情報
 *
 * create 2008/04/14 W.Takasaki
 *
 */
function getHeaderData($dbConnect, $staffId) {

    // 初期化
    $result = array();

    // 表示する情報を取得する
    $sql  = "";
    $sql .= " SELECT";
    $sql .=     " S.StaffSeqID,";
    $sql .=     " S.StaffCode,";
    $sql .=     " S.PersonName,";
    $sql .=     " C.CompID,";
    $sql .=     " C.CompCd,";
    $sql .=     " C.CompKind,";
    $sql .=     " C.Zip,";
    $sql .=     " C.Adrr,";
    $sql .=     " C.CompName,";
    $sql .=     " C.Tel,";
	$sql .=		" C.ShipName,";
	$sql .=		" C.TantoName";
    $sql .= " FROM";
    $sql .=     " M_Staff S";
    $sql .=     " INNER JOIN";
    $sql .=     " M_Comp C";
    $sql .=     " ON";
    $sql .=         " S.CompID = C.CompID";
    $sql .= " WHERE";
    $sql .=     " S.StaffSeqID = " . db_Escape($staffId) . "";
    $sql .= " AND";
    $sql .=     " S.Del = " . DELETE_OFF;
    $sql .= " AND";
    $sql .=     " C.Del = " . DELETE_OFF;

    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合
    if (count($result) <= 0) {
        return $result;
    }
    return $result[0];
}






/* ../../include/commonFunc.php end */


/* ./koukan_func.php start */

/*
 * 交換機能で使用する共通関数
 * koukan_func.php
 *
 * create 2008/04/22 W.Takasaki
 *
 *
 */

/*
 * 交換可能商品一覧情報を取得する
 * 引数  ：$dbConnect    => コネクションハンドラ
 *       ：$staffId      => StaffID
 *       ：$appliReason => 交換理由 
 *       ：$compId       => 店舗ID
 *       ：$post         => POST値
 * 戻り値：$result       => 交換可能商品一覧情報
 *
 * create 2007/03/19 H.Osugi
 *
 */
function getStaffOrder($dbConnect, $staffId, $appliReason, $post) {

    // 初期化
    $result = array();

    // 商品の一覧を取得する
    $sql  = "";
    $sql .= " SELECT";
    $sql .=     " tod.OrderDetID,";
    $sql .=     " mi.ItemID,";
    $sql .=     " mi.ItemName,";
    $sql .=     " tod.BarCd,";
    $sql .=     " tod.Size,";
    $sql .=     " mi.SizeID";
    $sql .= " FROM";
    $sql .=     " T_Staff_Details tsd";
    $sql .= " INNER JOIN";
    $sql .=     " T_Staff ts";
    $sql .= " ON";
    $sql .=     " tsd.StaffID = ts.StaffID";
    $sql .= " AND";
    $sql .=     " ts.Del = " . DELETE_OFF;
    $sql .= " INNER JOIN";
    $sql .=     " T_Order_Details tod";
    $sql .= " ON";
    $sql .=     " tsd.OrderDetID = tod.OrderDetID";
    $sql .= " AND";
    $sql .=     " tod.Del = " . DELETE_OFF;
    // 初回サイズ交換の場合は、出荷から一定期間内であれば交換可能とする。
    if ($appliReason == APPLI_REASON_EXCHANGE_FIRST) {
        // 出荷日から起算して10日以上経過している商品は表示しない
// 一時的に日数制限を解除 by T.Uno at 2023/04/27
//        $sql .= " AND";
//        $sql .=     " CONVERT(char, tod.ShipDay, 111) >= '" . date("Y/m/d", strtotime(EXCHANGE_TERM)) . "'";
    }

    $sql .= " INNER JOIN";
    $sql .=     " M_Item mi";
    $sql .= " ON";
    $sql .=     " tod.ItemID = mi.ItemID";
    $sql .= " AND";
    $sql .=     " mi.Del = " . DELETE_OFF;
    $sql .= " INNER JOIN";
    $sql .=     " T_Order tor";
    $sql .= " ON";
    $sql .=     " tod.OrderID = tor.OrderID";
    $sql .= " AND";
    $sql .=     " tor.Del = " . DELETE_OFF;
    $sql .= " WHERE";
    $sql .=     " ts.StaffID = '" . db_Escape($staffId) . "'";
    $sql .= " AND";
    $sql .=     " tsd.ReturnFlag = 0";
    $sql .= " AND";
    $sql .=     " tsd.ReturnDetID IS NULL";
    $sql .= " AND";
    $sql .=     " tsd.Status IN ( " . STATUS_SHIP . ", ". STATUS_DELIVERY .")";     // ステータスが出荷済(15),納品済(16)
    $sql .= " AND";
    $sql .=     " tsd.Del = " . DELETE_OFF;
    $sql .= " ORDER BY";
    $sql .=     " mi.ItemID ASC";
    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合
    if (count($result) <= 0) {
        $result = array();
        return $result;
    }

    // 取得した値をHTMLエンティティ
    $resultCount = count($result);
    $returnAry = array();

	$j = 0;
    for ($i=0; $i<$resultCount; $i++) {

        // サイズ展開を取得
        $sizeData = array();
        $sizeData = getSize($dbConnect, $result[$i]['SizeID'], 1);

        // サイズ交換の場合はSizeが１つの商品ははぶく
        if ($appliReason == APPLI_REASON_EXCHANGE_FIRST || $appliReason == APPLI_REASON_EXCHANGE_SIZE) {
            if (count($sizeData) <= 1) {
                continue;
            }
        }

        $returnAry[$j]['OrderDetID'] = $result[$i]['OrderDetID'];
        $returnAry[$j]['ItemName']   = castHtmlEntity($result[$i]['ItemName']);
        $returnAry[$j]['BarCd']      = castHtmlEntity($result[$i]['BarCd']);
        $returnAry[$j]['Size']       = castHtmlEntity($result[$i]['Size']);
        $returnAry[$j]['SizeID']     = $result[$i]['SizeID'];
        // バーコードが空かどうか判定
        $returnAry[$j]['isEmptyBarCd'] = false;
        if ($returnAry[$j]['BarCd'] == '') {
            $returnAry[$j]['isEmptyBarCd'] = true;
        }

        // 選択チェックボックスが選択されているか判定
        $returnAry[$j]['checked'] = false;
        if (is_array($post['orderDetIds'])) {
            if (in_array($returnAry[$j]['OrderDetID'], $post['orderDetIds'])) {
                $returnAry[$j]['checked'] = true;
            }
        }

        // サイズのリストボックス情報を生成
        $returnAry[$j]['isSelect'] = false;
        if (isset($sizeData)) {

            // サイズが1件のものはリストボックスにはせずに文字列表示
            if (count($sizeData) == 1) {
                $returnAry[$j]['sizes'] = castHtmlEntity($sizeData['Size1']);
            } else {

                // 初期化
                $returnAry[$j]['sizes'] = array();
                $returnAry[$j]['isSelect'] = true;
                $selectedSize = '';

                if (isset($post['size'][$result[$i]['OrderDetID']])) {
                    $selectedSize = trim($post['size'][$result[$i]['OrderDetID']]);
                } elseif ($appliReason != APPLI_REASON_EXCHANGE_FIRST && $appliReason != APPLI_REASON_EXCHANGE_SIZE) {
                    // 初回サイズ交換、サイズ交換以外は現在のサイズを初期表示する
                    foreach($sizeData as $key => $value) {
                        if ($returnAry[$j]['Size'] == $value) {
                            $selectedSize = $key;
                            break;
                        }
                    }
                }

                // リストボックス用に値を成型    
                $returnAry[$j]['sizes'] = castListboxSize($sizeData, $selectedSize);
            }
        }

        // 選択チェックボックスが選択されているか判定
        $returnAry[$j]['isUnused'] = false;
        if (isset($post['itemUnused'][$result[$i]['OrderDetID']]) && trim($post['itemUnused'][$result[$i]['OrderDetID']]) == '1') {
            $returnAry[$j]['isUnused'] = true;
        }

		$j++;
    }

    return  $returnAry;

}

/*
 * 交換未出荷カウント
 * 交換後商品（サイズ含めて）が未出荷商品があれば、そのアイテムはサイズ交換不可とする
 
 * 引数  ：$dbConnect    => コネクションハンドラ
 *       ：$staffId      => StaffID
 *       ：$appliReason => 交換理由 
 *       ：$compId       => 店舗ID
 *       ：$post         => POST値
 * 戻り値：$result       => 交換可能商品一覧情報
 *
 * create 2007/03/19 H.Osugi
 *
 */
function getSizeKoukanUnshipped($dbConnect, $staffId, $appliReason, $itemID) {

    // 初期化
    $result = array();

	// 着用状況の一覧を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" tod.ItemID,";
	$sql .= 	" tod.Status,";
	$sql .= 	" count(tod.OrderDetID) AS ItemCount";
	$sql .= " FROM";
	$sql .= 	" T_Order_Details tod";
	$sql .= " INNER JOIN";
	$sql .= 	" (";
	$sql .= 		" (";
	$sql .= 			" SELECT";
	$sql .= 				" DISTINCT";
	$sql .= 				" tsd_uni1.OrderDetID,";
	$sql .= 				" tsd_uni1.StaffID,";
	$sql .= 				" tsd_uni1.Status";
	$sql .= 			" FROM";
	$sql .= 				" T_Order_Details tod_uni1";
	$sql .= 			" INNER JOIN";
	$sql .= 				" T_Staff_Details tsd_uni1";
	$sql .= 			" ON";
	$sql .= 				" tod_uni1.OrderDetID = tsd_uni1.OrderDetID";
	$sql .= 			" AND";
	$sql .= 				" tsd_uni1.ReturnDetID is NULL";
	$sql .= 			" AND";
	$sql .= 				" tsd_uni1.Del = " . DELETE_OFF;
	$sql .= 			" WHERE";
	$sql .= 				" tod_uni1.Del = " . DELETE_OFF;

	$sql .= 			" AND";
	$sql .= 				" tsd_uni1.Status IN (";
	$sql .= 					"'" . STATUS_APPLI . "',";            // 申請済（承認待）
	$sql .= 					"'" . STATUS_APPLI_ADMIT . "',";      // 申請済（承認済）
	$sql .= 					"'" . STATUS_STOCKOUT . "',";         // 在庫切
	$sql .= 					"'" . STATUS_ORDER . "',";            // 受注済
	$sql .= 					"'" . STATUS_SHIP . "',";             // 出荷済
	$sql .= 					"'" . STATUS_DELIVERY . "'";          // 納品済
	$sql .= 				" )";

	$sql .= 		" )";
	$sql .= 	" UNION ALL";
	$sql .= 		" (";
	$sql .= 			" SELECT";
	$sql .= 				" DISTINCT";
	$sql .= 				" tod_uni2.OrderDetID,";
	$sql .= 				" tsd_uni2.StaffID,";
	$sql .= 				" tsd_uni2.Status";
	$sql .= 			" FROM";
	$sql .= 				" T_Order_Details tod_uni2";
	$sql .= 			" INNER JOIN";
	$sql .= 				" T_Staff_Details tsd_uni2";
	$sql .= 			" ON";
	$sql .= 				" tod_uni2.OrderDetID = tsd_uni2.ReturnDetID";
	$sql .= 			" AND";
	$sql .= 				" tsd_uni2.Del = " . DELETE_OFF;
	$sql .= 			" WHERE";
	$sql .= 				" tod_uni2.Del = " . DELETE_OFF;

	$sql .= 			" AND";
	$sql .= 				" tsd_uni2.Status IN (";
	$sql .= 					"'" . STATUS_APPLI . "',";
	$sql .= 					"'" . STATUS_APPLI_ADMIT . "',";
	$sql .= 					"'" . STATUS_STOCKOUT . "',";
	$sql .= 					"'" . STATUS_ORDER . "',";
	$sql .= 					"'" . STATUS_SHIP . "',";
	$sql .= 					"'" . STATUS_DELIVERY . "'";
	$sql .= 				" )";

	$sql .= 		" )";
	$sql .= 	" ) tsd";
	$sql .= " ON";
	$sql .= 	" tod.OrderDetID = tsd.OrderDetID";

	if ($isLevelAgency == true) {
		$sql .= 	" INNER JOIN";
		$sql .= 		" T_Staff ts_age";
		$sql .= 	" ON";
		$sql .= 		" tsd.StaffID = ts_age.StaffID";
		$sql .= 	" AND";
		$sql .= 		" ts_age.Del = " . DELETE_OFF;
		$sql .= 	" INNER JOIN";
		$sql .= 		" M_Comp mc";
		$sql .= 	" ON";
		$sql .= 		" mc.AgencyID = '" . db_Escape($_SESSION['COMPID']) . "'";
		$sql .= 	" AND";
		$sql .= 		" ts_age.CompID = mc.CompID";
		$sql .= 	" AND";
		$sql .= 		" mc.Del = " . DELETE_OFF;
	}

	$sql .= " INNER JOIN";
	$sql .= 	" (";
	$sql .= 		" SELECT";
	$sql .= 			" *";
	$sql .= 		" FROM";
	$sql .= 			" T_Order tor2";
	$sql .= 		" WHERE";
	$sql .= 				" tor2.StaffCode = (";
	$sql .= 				" SELECT";
	$sql .= 						" TOP 1";
	$sql .= 						" StaffCode";
	$sql .= 					" FROM";
	$sql .= 						" (";
	$sql .= 							" SELECT";
	$sql .= 								" DISTINCT";
	$sql .= 								" TOP " . ($offset + 1);
	$sql .= 								" ts.StaffCode";
	$sql .= 							" FROM";
	$sql .= 								" T_Staff ts";
	$sql .= 							" INNER JOIN";
	$sql .= 								" T_Order tor3";
	$sql .= 							" ON";
	$sql .= 								" ts.StaffCode = tor3.StaffCode";
	$sql .= 							" AND";
	$sql .= 								" ts.CompID = tor3.CompID";
	$sql .= 							" AND";
	$sql .= 								" tor3.Del = " . DELETE_OFF;

	$sql .= 							" INNER JOIN";
	$sql .= 								" M_Comp mc";
	$sql .= 							" ON";
	$sql .= 								" tor3.CompID = mc.CompID";
	$sql .= 							" AND";
	$sql .= 								" mc.Del = " . DELETE_OFF;

	if ($corpCode != '') {
		$sql .= " AND";
		$sql .= 	" mc.CorpCd = '" . db_Escape($corpCode) . "'";
	}

	if ($compId != '') {
		$sql .= 							" AND";
		$sql .= 								" tor3.CompID = " . db_Escape($compId);
	}

	$sql .= 							" INNER JOIN";
	$sql .= 								" T_Order_Details tod2";
	$sql .= 							" ON";
	$sql .= 								" tor3.OrderID = tod2.OrderID";
	$sql .=		 						" AND";
	$sql .= 								" tod2.Del = " . DELETE_OFF;
	$sql .= 							" INNER JOIN";
	$sql .= 								" (";
	$sql .= 									" (";
	$sql .= 										" SELECT";
	$sql .= 											" DISTINCT";
	$sql .= 											" tod_uni3.OrderID";
	$sql .= 										" FROM";
	$sql .= 											" T_Order_Details tod_uni3";
	$sql .= 										" INNER JOIN";
	$sql .= 											" T_Staff_Details tsd_uni3";
	$sql .= 										" ON";
	$sql .= 											" tod_uni3.OrderDetID = tsd_uni3.OrderDetID";
	$sql .= 										" AND";
	$sql .= 											" tsd_uni3.ReturnDetID is NULL";
	$sql .= 										" AND";
	$sql .= 											" tsd_uni3.Del = " . DELETE_OFF;
	$sql .= 										" WHERE";
	$sql .= 											" tod_uni3.Del = " . DELETE_OFF;

	$sql .= 									" AND";
	$sql .= 										" tsd_uni3.Status IN (";
	$sql .= 											"'" . STATUS_APPLI . "',";
	$sql .= 											"'" . STATUS_APPLI_ADMIT . "',";
	$sql .= 											"'" . STATUS_STOCKOUT . "',";
	$sql .= 											"'" . STATUS_ORDER . "',";
	$sql .= 											"'" . STATUS_SHIP . "',";
	$sql .= 											"'" . STATUS_DELIVERY . "'";
	$sql .= 										" )";

	$sql .= 									" )";
	$sql .= 								" UNION ALL";
	$sql .= 									" (";
	$sql .= 										" SELECT";
	$sql .= 											" DISTINCT";
	$sql .= 											" tod_uni4.OrderID";
	$sql .= 										" FROM";
	$sql .= 											" T_Order_Details tod_uni4";
	$sql .= 										" INNER JOIN";
	$sql .= 											" T_Staff_Details tsd_uni4";
	$sql .= 										" ON";
	$sql .= 											" tod_uni4.OrderDetID = tsd_uni4.ReturnDetID";
	$sql .= 										" AND";
	$sql .= 											" tsd_uni4.Del = " . DELETE_OFF;
	$sql .= 										" WHERE";
	$sql .= 											" tod_uni4.Del = " . DELETE_OFF;

	$sql .= 									" AND";
	$sql .= 										" tsd_uni4.Status IN (";
	$sql .= 											"'" . STATUS_APPLI . "',";
	$sql .= 											"'" . STATUS_APPLI_ADMIT . "',";
	$sql .= 											"'" . STATUS_STOCKOUT . "',";
	$sql .= 											"'" . STATUS_ORDER . "',";
	$sql .= 											"'" . STATUS_SHIP . "',";
	$sql .= 											"'" . STATUS_DELIVERY . "'";
	$sql .= 										" )";

	$sql .= 									" )";
	$sql .= 								" ) tsd2";
	$sql .= 							" ON";
	$sql .= 								" tor3.OrderID = tsd2.OrderID";

	$sql .= 							" WHERE";

	if ($compId != '') {
		$sql .= 								" ts.CompID = " . db_Escape($compId);
		$sql .= 							" AND";
	}

	////$sql .= 								" ts.AllReturnFlag = 0";
	////$sql .= 							" AND";
	$sql .= 								" ts.Del = " . DELETE_OFF;

											// スタッフコードの指定があった場合
	$sql .= 								" AND";
	$sql .= 									" ts.StaffID = '" . db_Escape($staffId) . "'";

	$sql .= 					" ORDER BY";
	$sql .= 						" ts.StaffCode ASC";
	$sql .= 									" ) tor4";

	$sql .= 					" ORDER BY";
	$sql .= 						" tor4.StaffCode DESC";
	$sql .= 				" )";

	$sql .= 			" ) tor";
	$sql .= 	" ON";
	$sql .= 		" tod.OrderID = tor.OrderID";

	if ($compId != '') {
		$sql .= 	" AND";
		$sql .= 		" tor.CompID = " . db_Escape($compId);
	}

	$sql .= 	" WHERE";
	$sql .= 		" tod.Del= " . DELETE_OFF;

	$sql .= 	" AND";
	$sql .= 		" tod.ItemID= '" . db_Escape($itemID) . "'";

	$sql .= 	" GROUP BY";
	$sql .= 		" tod.ItemID,";
	$sql .= 		" tod.Status";

	$sql .= 	" ORDER BY";
	$sql .= 		" tod.ItemID ASC,";
	$sql .= 		" tod.Status ASC";
//var_dump($sql);die;
	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}
	
	return $result;
}



/* ./koukan_func.php end */



// 初期設定
$isMenuExchange = true;	// 交換のメニューをアクティブに

// 変数の初期化 ここから ******************************************************
$searchCompCd = '';
$searchCompName = '';
$searchCompId = '';
$searchStaffCd = '';
$searchPersonName = '';

$requestNo = '';					// 申請番号
$staffCode = '';					// スタッフコード
$personName = '';                    // スタッフコード
$zip1      = '';					// 郵便番号（前半3桁）
$zip2      = '';					// 郵便番号（後半4桁）
$address   = '';					// 住所
$shipName  = '';					// 出荷先名
$staffName = '';					// ご担当者
$tel       = '';					// 電話番号
$memo      = '';					// メモ

$displayRequestNo = '';				// 申請番号（表示用）

$selectedSize = array();			// 選択されたサイズ

$selectedReason1 = false;			// 交換理由（サイズ交換）
$selectedReason2 = false;			// 交換理由（汚損・破損交換）
$selectedReason3 = false;			// 交換理由（紛失交換）
$selectedReason4 = false;			// 交換理由（不良品交換）
$selectedReason5 = false;           // 交換理由（初回サイズ交換）

$exchangeGuideMessage = '';			// 初回サイズ交換時に表示するメッセージ文字列

$isMotoTok      = false;            // 交換訂正する時に元の発注で特寸が選択されていたか

// 変数の初期化 ここまで ******************************************************

// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST); 

// 検索パラメーターの設定
//$searchStaffCode = $post['searchStaffCode'];


if (isset($post['searchFlg']) && $post['searchFlg'] != '') {
    $searchFlg = $post['searchFlg'];
} else {
    $searchFlg = '';
}

if (isset($post['nowPage']) && $post['nowPage'] != '') {
    $nowPage = $post['nowPage'];
} else {
    $nowPage = '';
}

if (isset($post['appliReason']) && $post['appliReason'] != '') {
    $appliReason = $post['appliReason'];
} else {
    $appliReason = '';
}

if (isset($post['searchStaffCode']) && $post['searchStaffCode'] != '') {
    $searchStaffCode = $post['searchStaffCode'];
} else {
    $searchStaffCode = '';
}

if (isset($post['searchPersonName']) && $post['searchPersonName'] != '') {
    $searchPersonName = $post['searchPersonName'];
} else {
    $searchPersonName = '';
}

if (isset($post['searchHonbuId']) && $post['searchHonbuId'] != '') {
    $searchHonbuId = $post['searchHonbuId'];
} else {
    $searchHonbuId = '';
}

if (isset($post['searchShitenId']) && $post['searchShitenId'] != '') {
    $searchShitenId = $post['searchShitenId'];
} else {
    $searchShitenId = '';
}

if (isset($post['searchEigyousyoId']) && $post['searchEigyousyoId'] != '') {
    $searchEigyousyoId = $post['searchEigyousyoId'];
} else {
    $searchEigyousyoId = '';
}


// スタッフIDが取得できなければエラーに
if (!isset($post['rirekiFlg']) || !$post['rirekiFlg']) {
    if (!isSetValue($post['staffId'])) {
    
    	// TOP画面に強制遷移
//var_dump("aaaa");die;
        $returnUrl = HOME_URL . 'top.php';
    	redirectPost($returnUrl, $hiddens);
    }
} else {    // 変更時は申請IDがなければエラー
    if (!isSetValue($post['orderId'])) {
//var_dump("bbbb");die;

        redirectTop();
    }

    $isMenuExchange   = false; // 交換のメニューをオフ
    $isMenuHistory = true;  // 申請履歴のメニューをアクティブに
    $haveRirekiFlg = true;  // 交換申請か交換変更かを判定するフラグ
}

$staffId = trim($post['staffId']);		// StaffID

$appliReason = trim($post['appliReason']);  // 交換理由

// 交換理由
switch (trim($appliReason)) {

    // 交換理由（サイズ交換）
    case APPLI_REASON_EXCHANGE_SIZE:
        $selectedReason1 = true;
        break;

    // 交換理由（汚損・破損交換）
    case APPLI_REASON_EXCHANGE_BREAK:
        $selectedReason2 = true;
        break;

    // 交換理由（紛失交換）
    case APPLI_REASON_EXCHANGE_LOSS:
        $selectedReason3 = true;
        break;

    // 交換理由（不良品交換）
    case APPLI_REASON_EXCHANGE_INFERIORITY:
        $selectedReason4 = true;
        break;

    // 交換理由（初回サイズ交換）
    case APPLI_REASON_EXCHANGE_FIRST:
        $selectedReason5 = true;
// 一時的に日数制限を解除 by T.Uno at 2023/04/27
//        $exchangeGuideMessage = '※出荷から' . EXCHANGE_TERM_DAY . '日以内の商品のみ表示されます。';
        $exchangeGuideMessage = '';
        break;

    default:
        break;
}

// 画面上部に表示するデータを取得
$headerData = getHeaderData($dbConnect, $staffId);

// 店舗コードを取得
$compCd = '';
if (isSetValue($headerData['CompCd'])) {
    $compCd = $headerData['CompCd'];
}

// 店舗名を取得
$compName = '';
if (isSetValue($headerData['CompName'])) {
    $compName = $headerData['CompName'];
}

// スタッフコードを取得
$staffCode = '';
if (isSetValue($headerData['StaffCode'])) {
    $staffCode = $headerData['StaffCode'];
}

// 着用者氏名
$personName = '';
if (isSetValue($headerData['PersonName'])) {
    $personName = $headerData['PersonName'];
}


// 履歴からの遷移の場合は、申請内容を取得
if (isset($post['rirekiFlg']) && $post['rirekiFlg'] == COMMON_FLAG_ON) {     
    if (!isset($post['hachuShinseiFlg']) || !$post['hachuShinseiFlg']) {    
        $post = getOrderedContents($dbConnect, $post['orderId'], $post);
    }
}   

// 交換可能な商品一覧を表示
$items = getStaffOrder($dbConnect, $staffId, trim($post['appliReason']), $post);

// 交換可能商品が０件の場合
if (count($items) <= 0) {

	$hiddens['errorName']       = 'koukanShinsei';
	$hiddens['menuName']        = 'isMenuExchange';
	$hiddens['returnUrl']       = 'koukan/select_staff.php';
	$hiddens['errorId'][]       = '901';
	$errorUrl                   = HOME_URL . 'error.php';

	$hiddens['appliReason']    = trim($post['appliReason']);
	$hiddens['searchStaffCode'] = $post['searchStaffCode'];
	$hiddens['searchFlg']       = '1';

	if ($isLevelAdmin == true) {
		$hiddens['searchCompCd']   = trim($post['searchCompCd']);		// 店舗番号
		$hiddens['searchCompName'] = trim($post['searchCompName']);		// 店舗名
		$hiddens['searchCompId']   = trim($post['searchCompId']);		// 店舗名
	}

	redirectPost($errorUrl, $hiddens);
}


// 表示する値の成型 ここから +++++++++++++++++++++++++++++++++++++++++++++++++++
// 初期表示の場合
if (!isset($post['koukanShinseiFlg']) || $post['koukanShinseiFlg'] != '1') {

	// 申請番号を生成
	$requestNo = createRequestNo($dbConnect, $headerData['CompID'], 3);
	$displayRequestNo = 'A' . trim($requestNo);		// 頭文字に'A'をつける

	// 申請番号の生成に失敗した場合はエラー
	if ($requestNo == false) {
		// エラー処理を行う
	}

	// 郵便番号
	if (isset($headerData['Zip'])) {
		list($zip1, $zip2) = explode('-', $headerData['Zip']);
	}

	// 住所
	if (isset($headerData['Adrr'])) {
		$address = $headerData['Adrr'];
	}

	// 出荷先名
	if (isset($headerData['ShipName'])) {
		$shipName = $headerData['ShipName'];
	}

	// 電話番号
	if (isset($headerData['Tel'])) {
		$tel = $headerData['Tel'];
	}

	// ご担当者名を取得（HTMLエンティティ済）
    if (isset($headerData['TantoName'])) {
        $staffName = $headerData['TantoName'];
    } else {
        $staffName  = DEFAULT_STAFF_NAME;
	}

}
// POST情報を引き継ぐ場合
else {

	// 申請番号を生成
	$requestNo = trim($post['requestNo']);
	$displayRequestNo = 'A' . trim($post['requestNo']);		// 頭文字に'A'をつける

	// スタッフコード
	$staffCode = trim($headerData['StaffCode']);

    // 着用者氏名
    $personName = trim($headerData['PersonName']);

	// 郵便番号
	$zip1 = trim($post['zip1']);
	$zip2 = trim($post['zip2']);

	// 住所
	$address = trim($post['address']);

	// 出荷先名
	$shipName = trim($post['shipName']);

	// ご担当者
	$staffName  = trim($post['staffName']);

	// 電話番号
	$tel = trim($post['tel']);

	// メモ
	$memo = trim($post['memo']);

}

if ($isLevelAdmin == true) {
	$searchCompCd        = $post['searchCompCd'];		// 店舗番号
	$searchCompName      = $post['searchCompName'];		// 店舗名
	$searchCompId        = $post['searchCompId'];		// 店舗名
}
$searchStaffCd    = trim($post['searchStaffCd']);		// スタッフコード
$searchPersonName = trim($post['searchPersonName']);	// スタッフ氏名

// 表示する値の成型 ここまで +++++++++++++++++++++++++++++++++++++++++++++++++++
/*
 * 交換可能商品一覧情報を取得する
 * 引数  ：$dbConnect    => コネクションハンドラ
 *       ：$orderId    => 注文ID
 *       ：$post       => POST値
 * 戻り値：$result       => 交換可能商品一覧情報
 *
 * create 2007/03/19 H.Osugi
 *
 */
function getOrderedContents($dbConnect, $orderId, $post) {

    $result = array();

    // orderＩdからAppliNoを取得する
    $sql  = " SELECT";
    $sql .=     " AppliNo";
    $sql .= " FROM";
    $sql .=     " T_Order";
    $sql .= " WHERE";
    $sql .=     " OrderID = '".$orderId."'";

    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合
    if (count($result) <= 0) {
        $result = array();
        return $result;
    }

    $returnAppliNo = "R".substr($result[0]['AppliNo'], 1);
    $orderAppliNo  = "A".substr($result[0]['AppliNo'], 1);

    // 初期化
    $result = array();

    // 商品の一覧を取得する
    $sql  = "";
    $sql .= " SELECT";
    $sql .=     " todr.MotoOrderDetID,";
    $sql .=     " mi.ItemID,";
    $sql .=     " tod.Size,";
    $sql .=     " mi.SizeID";
    $sql .= " FROM";
    $sql .=     " T_Staff_Details tsd";
    $sql .= " INNER JOIN";
    $sql .=     " T_Order_Details tod";
    $sql .= " ON";
    $sql .=     " tsd.OrderDetID = tod.OrderDetID";
    $sql .= " AND";
    $sql .=     " tod.Del = " . DELETE_OFF;
    $sql .= " INNER JOIN";
    $sql .=     " T_Order_Details todr";
    $sql .= " ON";
    $sql .=     " todr.OrderDetID = tod.OrderDetID";
    $sql .= " AND";
    $sql .=     " todr.Del = " . DELETE_OFF;
    $sql .= " INNER JOIN";
    $sql .=     " M_Item mi";
    $sql .= " ON";
    $sql .=     " tod.ItemID = mi.ItemID";
    $sql .= " AND";
    $sql .=     " mi.Del = " . DELETE_OFF;
    $sql .= " INNER JOIN";
    $sql .=     " T_Order tor";
    $sql .= " ON";
    $sql .=     " tod.OrderID = tor.OrderID";
    $sql .= " AND";
    $sql .=     " tor.Del = " . DELETE_OFF;
    $sql .= " WHERE";
    $sql .=     " tor.AppliNo = '" . db_Escape($orderId) . "'";
    $sql .= " AND";
    $sql .=     " tsd.ReturnFlag = 0";
    $sql .= " AND";
    $sql .=     " tsd.ReturnDetID IS NULL";
    $sql .= " AND";
    $sql .=     " tsd.Del = " . DELETE_OFF;
    $sql .= " ORDER BY";
    $sql .=     " mi.ItemID ASC";

    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合
    if (count($result) <= 0) {
        $result = array();
        return $result;
    }

    // 取得した値をHTMLエンティティ
    foreach ($result as $key => $val) {
        $post['orderDetIds'][] = $val['MotoOrderDetID'];                

        // サイズ展開を取得
        $sizeData = array();
        $sizeData = array_flip(getSize($dbConnect, $val['SizeID'], 1));

        $post['size'][$val['MotoOrderDetID']] = $sizeData[$val['Size']];
    }

    return  $post;

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
        

        <form method="post" action="koukan_shinsei_kakunin.php" name="registForm">
          <div id="contents">
            <h1>
<?php if($selectedSeason1) { ?>
              秋冬用
<?php } ?>
<?php if($selectedSeason2) { ?>
              
              春夏用
              
<?php } ?>
              &nbsp;ユニフォーム交換申請　
<?php if($selectedReason1) { ?>
              （サイズ交換）
<?php } ?>
<?php if($selectedReason2) { ?>
              
              （汚損・破損交換）
              
<?php } ?>
<?php if($selectedReason3) { ?>
              
              （紛失交換）
              
<?php } ?>
<?php if($selectedReason4) { ?>
              
              （不良品交換）
              
<?php } ?>
<?php if($selectedReason5) { ?>
              
              （初回サイズ交換）
              
<?php } ?>
            </h1>
            <table border="0" width="700" cellpadding="0" cellspacing="0" class="tb_1" style="border-bottom:1px solid #CCC; padding-bottom:10px;">
              <tr height="30">
                <td width="100" class="line"><span class="fbold">申請番号</span></td>
                <td colspan="3" class="line"><?php isset($displayRequestNo) ? print($displayRequestNo) : print('&#123;displayRequestNo&#125;'); ?></td>
              </tr>
              <tr height="30">
                <td class="line"><span class="fbold">施設名</span></td>
                <td colspan="3" class="line"><?php isset($compCd) ? print($compCd) : print('&#123;compCd&#125;'); ?>：<?php isset($compName) ? print($compName) : print('&#123;compName&#125;'); ?></td>
              </tr>
              <tr height="30">
                <td class="line"><span class="fbold">職員コード</span></td>
                <td class="line"><?php isset($staffCode) ? print($staffCode) : print('&#123;staffCode&#125;'); ?></td>
                <td width="80" class="line"><span class="fbold">職員名</span></td>
                <td width="400" class="line"><?php isset($personName) ? print($personName) : print('&#123;personName&#125;'); ?>&nbsp;</td>
              </tr>
              <tr height="30">
                <td><span class="fbold">出荷先</span></td>
                <td colspan="3"">〒
                  <input name="zip1" type="text" style="width:40px;" value="<?php isset($zip1) ? print($zip1) : print('&#123;zip1&#125;'); ?>" maxlength="3"/> － <input name="zip2" type="text" value="<?php isset($zip2) ? print($zip2) : print('&#123;zip2&#125;'); ?>" style="width:40px;" maxlength="4">
                </td>
              </tr>
              <tr height="30">
                <td width="100"></td>
                <td width="100"><span class="fbold">住所</span></td>
                <td width="482" colspan="2"><input name="address" type="text" value="<?php isset($address) ? print($address) : print('&#123;address&#125;'); ?>" style="width:400px;"></td>
              </tr>
              <tr height="30">
                <td width="100"></td>
                <td width="100"><span class="fbold">出荷先名</span></td>
                <td width="482" colspan="2"><input name="shipName" type="text" value="<?php isset($shipName) ? print($shipName) : print('&#123;shipName&#125;'); ?>" style="width:400px;"></td>
              </tr>
              <tr height="30">
                <td width="100"></td>
                <td width="100"><span class="fbold">ご担当者</span></td>
                <td width="482" colspan="2"><input name="staffName" type="text" value="<?php isset($staffName) ? print($staffName) : print('&#123;staffName&#125;'); ?>" style="width:200px;"></td>
              </tr>
              <tr height="30">
                <td width="100" class="line"></td>
                <td width="100" class="line"><span class="fbold">電話番号</span></td>
                <td width="482" colspan="2" class="line"><input name="tel" type="text" value="<?php isset($tel) ? print($tel) : print('&#123;tel&#125;'); ?>" style="width:200px;"></td>
              </tr>
              <tr height="30">
                <td width="100" class="line"><span class="fbold">交換理由</span></td>
                <td colspan="3" class="line">
<?php if($selectedReason1) { ?>
                  サイズ交換
<?php } ?>
<?php if($selectedReason2) { ?>
                  
                  汚損・破損交換
                  
<?php } ?>
<?php if($selectedReason3) { ?>
                  
                  紛失交換
                  
<?php } ?>
<?php if($selectedReason4) { ?>
                  
                  不良品交換
                  
<?php } ?>
<?php if($selectedReason5) { ?>
                  
                  初回サイズ交換
                  
<?php } ?>
                </td>
              </tr>
              <tr height="30">
                <td width="100"><span class="fbold">メモ</span></td>
                <td colspan="3">
                  <input name="memo" type="text" value="<?php isset($memo) ? print($memo) : print('&#123;memo&#125;'); ?>" style="width:500px;">
                </td>
              </tr>
              <tr>
                <td width="100" class="line">&nbsp;</td>
                <td colspan="3" class="line">
<?php if(!$selectedReason2) { ?>
                  <span style="color:red">
                    ※メモ欄に交換理由の詳細をご入力願います。
                  </span>
<?php } ?>
<?php if($selectedReason2) { ?>
                  <span style="color:red">
                    ※交換理由を下記から選びメモ欄へご記入願います。<br>
                    　・作業・洗濯中に汚した・破れた<br>
                    　・経年劣化（シミ・くすみが取れない）<br>
                    　・経年劣化（ほつれ）<br>
                    　・その他（理由を記載）
                  </span>
<?php } ?>
                </td>
              </tr>
              <tr height="30">
                <td colspan="7" align="right" valign="bottom"><a href="<?php isset($sizeUrl) ? print($sizeUrl) : print('&#123;sizeUrl&#125;'); ?>" target="_blank"><img src="../img/size_pattern.gif" alt="サイズ表" border="0"></a></td>
              </tr>
            </table>
            <h3>◆交換するユニフォームをチェックし、交換後のサイズを選択してください。</h3>
<?php if($selectedReason5) { ?>
            <table width="630" border="0" class="tb_1" id="content-table" cellpadding="0" cellspacing="3">
<?php } ?>
<?php if(!$selectedReason5) { ?>
            <table width="570" border="0" class="tb_1" id="content-table" cellpadding="0" cellspacing="3">
<?php } ?>
            <thead>
              <tr>
                <th align="center" width="50">選択</th>
                <th align="center" width="200">アイテム名</th>
                <th align="center" width="80">現在のサイズ</th>
                <th align="center" width="100">単品番号</th>
                <th align="center" width="140">交換後のサイズ</th>
<?php if($selectedReason5) { ?>
                <th align="center" width="60">未着用</th>
<?php } ?>
              </tr>
            </thead>
            <tbody>
<?php for ($i1_items=0; $i1_items<count($items); $i1_items++) { ?>
              <tr height="25" id="<?php isset($items[$i1_items]['OrderDetID']) ? print($items[$i1_items]['OrderDetID']) : print('&#123;items.OrderDetID&#125;'); ?>">
                <td class="line2" align="center">
<?php if($items[$i1_items]['checked']) { ?>
                  <input name="orderDetIds[]" type="checkbox" id="checkbox[]" value="<?php isset($items[$i1_items]['OrderDetID']) ? print($items[$i1_items]['OrderDetID']) : print('&#123;items.OrderDetID&#125;'); ?>" checked="checked">
<?php } ?>
<?php if(!$items[$i1_items]['checked']) { ?>
                  
                  <input name="orderDetIds[]" type="checkbox" id="checkbox[]" value="<?php isset($items[$i1_items]['OrderDetID']) ? print($items[$i1_items]['OrderDetID']) : print('&#123;items.OrderDetID&#125;'); ?>">
                  
<?php } ?>
                </td>
                <td class="line2" align="left"><?php isset($items[$i1_items]['ItemName']) ? print($items[$i1_items]['ItemName']) : print('&#123;items.ItemName&#125;'); ?></td>
                <td class="line2" align="center"><?php isset($items[$i1_items]['Size']) ? print($items[$i1_items]['Size']) : print('&#123;items.Size&#125;'); ?></td>
                <td class="line2" align="center">
<?php if($items[$i1_items]['isEmptyBarCd']) { ?>
                  
                  &nbsp;
                  
<?php } ?>
<?php if(!$items[$i1_items]['isEmptyBarCd']) { ?>
                  <?php isset($items[$i1_items]['BarCd']) ? print($items[$i1_items]['BarCd']) : print('&#123;items.BarCd&#125;'); ?>
<?php } ?>
                </td>
                <td class="line2" align="center">
<?php if($items[$i1_items]['isSelect']) { ?>
                  <select name="size[<?php isset($items[$i1_items]['OrderDetID']) ? print($items[$i1_items]['OrderDetID']) : print('&#123;items.OrderDetID&#125;'); ?>]" id="select">
                    <option value="">--サイズ選択--</option>
<?php for ($i2_items['sizes']=0; $i2_items['sizes']<count($items[$i1_items]['sizes']); $i2_items['sizes']++) { ?>
<?php if($items[$i1_items]['sizes'][$i2_items['sizes']]['selected']) { ?>
                    <option value="<?php isset($items[$i1_items]['sizes'][$i2_items['sizes']]['value']) ? print($items[$i1_items]['sizes'][$i2_items['sizes']]['value']) : print('&#123;items.sizes.value&#125;'); ?>" selected="selected"><?php isset($items[$i1_items]['sizes'][$i2_items['sizes']]['display']) ? print($items[$i1_items]['sizes'][$i2_items['sizes']]['display']) : print('&#123;items.sizes.display&#125;'); ?></option>
<?php } ?>
<?php if(!$items[$i1_items]['sizes'][$i2_items['sizes']]['selected']) { ?>
                    
                    <option value="<?php isset($items[$i1_items]['sizes'][$i2_items['sizes']]['value']) ? print($items[$i1_items]['sizes'][$i2_items['sizes']]['value']) : print('&#123;items.sizes.value&#125;'); ?>"><?php isset($items[$i1_items]['sizes'][$i2_items['sizes']]['display']) ? print($items[$i1_items]['sizes'][$i2_items['sizes']]['display']) : print('&#123;items.sizes.display&#125;'); ?></option>
                    
<?php } ?>
<?php } ?>
                  </select>
<?php } ?>
                  <input type="hidden" name="sizeType[<?php isset($items[$i1_items]['OrderDetID']) ? print($items[$i1_items]['OrderDetID']) : print('&#123;items.OrderDetID&#125;'); ?>]" value="<?php isset($items[$i1_items]['SizeID']) ? print($items[$i1_items]['SizeID']) : print('&#123;items.SizeID&#125;'); ?>">
<?php if(!$items[$i1_items]['isSelect']) { ?>
                  
                  <?php isset($items[$i1_items]['sizes']) ? print($items[$i1_items]['sizes']) : print('&#123;items.sizes&#125;'); ?>
                  
<?php } ?>
                </td>

<?php if($selectedReason5) { ?>
                <td class="line2" align="center">
<?php if($items[$i1_items]['isUnused']) { ?>
                  <input name="itemUnused[<?php isset($items[$i1_items]['OrderDetID']) ? print($items[$i1_items]['OrderDetID']) : print('&#123;items.OrderDetID&#125;'); ?>]" type="checkbox" id="checkbox[]" value="1" checked="checked">
<?php } ?>
<?php if(!$items[$i1_items]['isUnused']) { ?>
                  
                  <input name="itemUnused[<?php isset($items[$i1_items]['OrderDetID']) ? print($items[$i1_items]['OrderDetID']) : print('&#123;items.OrderDetID&#125;'); ?>]" type="checkbox" id="checkbox[]" value="1">
                  
<?php } ?>
                </td>
<?php } ?>
              </tr>
<?php } ?>
<?php if($selectedReason5) { ?>
              <tr><td colspan="6" align="left"><font color="red"><?php isset($exchangeGuideMessage) ? print($exchangeGuideMessage) : print('&#123;exchangeGuideMessage&#125;'); ?></font></td></tr>
              <tr><td colspan="6" align="left"><font color="red">※未着用のアイテムの場合は、未着用欄にチェックを付けて下さい。</font></td></tr>
<?php } ?>
            <tbody>
            </table>
            
            <div class="bot">
             <a href="#" onclick="document.registForm.action='../select_staff.php'; document.registForm.submit(); return false;"><img src="../img/modoru.gif" alt="戻る" width="112" height="32" border="0"></a> &nbsp;&nbsp;&nbsp;&nbsp; 
             <a href="#" onclick="document.registForm.submit(); return false;"><img src="../img/tsugihe.gif" alt="次へ" width="112" height="32" border="0"></a>
            </div>
            
          </div>
          <input type="hidden" name="appliReason" value="<?php isset($appliReason) ? print($appliReason) : print('&#123;appliReason&#125;'); ?>">
          <input type="hidden" name="requestNo" value="<?php isset($requestNo) ? print($requestNo) : print('&#123;requestNo&#125;'); ?>">
          <input type="hidden" name="staffCode" value="<?php isset($staffCode) ? print($staffCode) : print('&#123;staffCode&#125;'); ?>">
          <input type="hidden" name="staffId" value="<?php isset($staffId) ? print($staffId) : print('&#123;staffId&#125;'); ?>">
          <input type="hidden" name="searchStaffCode" value="<?php isset($searchStaffCode) ? print($searchStaffCode) : print('&#123;searchStaffCode&#125;'); ?>">
          <input type="hidden" name="searchFlg" value="1">
<?php if($isLevelAdmin) { ?>
          <input type="hidden" name="searchCompCd" value="<?php isset($searchCompCd) ? print($searchCompCd) : print('&#123;searchCompCd&#125;'); ?>">
          <input type="hidden" name="searchCompName" value="<?php isset($searchCompName) ? print($searchCompName) : print('&#123;searchCompName&#125;'); ?>">
          <input type="hidden" name="searchCompId" value="<?php isset($searchCompId) ? print($searchCompId) : print('&#123;searchCompId&#125;'); ?>">

          <input type="hidden" name="searchFlg" value="<?php isset($searchFlg) ? print($searchFlg) : print('&#123;searchFlg&#125;'); ?>">
          <input type="hidden" name="nowPage" value="<?php isset($nowPage) ? print($nowPage) : print('&#123;nowPage&#125;'); ?>">
          <input type="hidden" name="searchStaffCode" value="<?php isset($searchStaffCode) ? print($searchStaffCode) : print('&#123;searchStaffCode&#125;'); ?>">
          <input type="hidden" name="searchPersonName" value="<?php isset($searchPersonName) ? print($searchPersonName) : print('&#123;searchPersonName&#125;'); ?>">
          <input type="hidden" name="searchHonbuId" value="<?php isset($searchHonbuId) ? print($searchHonbuId) : print('&#123;searchHonbuId&#125;'); ?>">
          <input type="hidden" name="searchShitenId" value="<?php isset($searchShitenId) ? print($searchShitenId) : print('&#123;searchShitenId&#125;'); ?>">
          <input type="hidden" name="searchEigyousyoId" value="<?php isset($searchEigyousyoId) ? print($searchEigyousyoId) : print('&#123;searchEigyousyoId&#125;'); ?>">

<?php } ?>
          <input type="hidden" name="searchStaffCd" value="<?php isset($searchStaffCd) ? print($searchStaffCd) : print('&#123;searchStaffCd&#125;'); ?>">
          <input type="hidden" name="searchPersonName" value="<?php isset($searchPersonName) ? print($searchPersonName) : print('&#123;searchPersonName&#125;'); ?>">
        </form>
        <br><br><br>
        

      </div>
    </div>
<?php if($selectedReason1) { ?>
<script type="text/javascript">
/*---------------------------------------------------
	$('input[type=checkbox]').change(function()
	{
		var checkjudge = true;
		var sizevalJudge = true;
		if(!($(this).is(":checked")))
		{
			checkjudge = false;
		}
		SizeChange(this,checkjudge,sizevalJudge);
	})

	$('select').change(function()
	{
		var checkjudge = true;
		var sizevalJudge = true;
		if($(this).val() ==="")
		{
			sizevalJudge = false;
		}
		SizeChange(this,checkjudge,sizevalJudge);
	})
	
	function SizeChange(child,checkresult,sizeresult)
	{
		var parentID = $(child).parent().parent().attr("id");
		var selectItemName = $(':nth-child(2)', '#' +parentID).html();
		var selectSizeVal = $('#' + parentID).find('option:selected').val();

		var tr = $("#content-table tbody tr");
		var tr_itemName ="";
		var i = 0;
		for (i = 0; i < tr.length; i++) 
		{
			tr_itemName = $(':nth-child(2)',tr[i]).html();
			if(tr_itemName === selectItemName)
			{
				var trId = $(tr[i]).attr("id");
				if(checkresult === false || sizeresult === false)
				{
					$('#' + trId).find('input[type=checkbox]').prop("checked",false);
					$('#' + trId).find('select').val("");
				}
				else
				{
					$('#' + trId).find('input[type=checkbox]').prop("checked",true);
					$('#' + trId).find('select').val(selectSizeVal);
				}
			}
		}
	}
---------------------------------------------------*/
</script>
<?php } ?>
  </body>
</html>

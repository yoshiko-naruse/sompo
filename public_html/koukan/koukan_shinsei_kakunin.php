<?php
/*
 * 交換確認画面
 * koukan_shinsei_kakunin.src.php
 *
 * create 2007/03/20 H.Osugi
 * update 2007/04/03 H.Osugi 特寸処理追加
 *
 */

// 各種モジュールの取得
include_once('../../include/define.php');				// 定数定義
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


/* ../../include/checkData.php start */

/*
 * エラー判定モジュール
 * checkData.php
 *
 * create 2007/03/14 H.Osugi
 *
 */

/*
 * 対象文字列にエラーが無いか判定する
 * 引数  ：$string => 対象文字列
 *       ：$mode   => エラー判定モード
 *                    'Text'      ：文字列
 *                    'Digit'     ：数値（整数）
 *                    'Numeric'   ：数値（小数もOK）
 *                    'HalfWidth' ：半角文字列
 *                    'Tel'       ：電話番号
 *       ：$empty  => false：空判定なし / true：空判定あり
 *       ：$max    => 最大値（ここで指定されたバイト数を超過すればエラー）
 *       ：$min    => 最小値（ここで指定されたバイト数未満であればエラー）
 *
 * 戻り値：エラー状況 ⇒ 'ok'               ：エラーが無かった場合
 *                       'empty'            ：空判定ありの場合に空だった場合
 *                       'max'              ：指定最大値を上回る場合
 *                       'min'              ：指定最小値を下回る場合
 *                       'mode'             ：エラー判定モードに違反する場合
 *                       'nonexistentMode'  ：モードの指定が間違っている場合
 *
 * create 2007/03/14 H.Osugi
 *
 */
function checkData($string, $mode, $empty, $max = '', $min = '') {

	// 空判定
	if ($empty == true && $string == '' && $string !== 0) {
		return 'empty';
	}

	// 文字列のモード判定
	switch ($mode) {

		case 'Text':
			break;

		// 数値判定（整数のみ）
		case 'Digit':
			if (!ctype_digit($string)) {
				return 'mode';
			}
			break;

		// 数値判定（整数・小数）
		case 'Numeric':
			if (!is_numeric($string) || $string < 0) {
				return 'mode';
			}
			break;

		// 半角判定
		case 'HalfWidth':
			if (!mb_ereg('^[\x00-\x7F]+$', $string)) {
				return 'mode';
			}
			break;

		// 電話番号判定
		case 'Tel':
			if (!mb_ereg('^[-0-9]+$', $string)) {
				return 'mode';
			}
			break;

        // 日付判定 (YYYY/MM/DD)
        case 'Date':
            // YYYY/MM/DD かチェック
            if (!mb_ereg('^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$', $string)) {
                return 'mode';
            } else {
                $dateAry = explode('/', $string);
                if (!checkDate($dateAry[1], $dateAry[2], $dateAry[0])) {
                    return 'mode';
                }
            }
            break;

		// 半角、英数字判定追加
		case 'Alphanumeric':
			// 半角、英数の半角のみで校正されているかを判定
			if (mb_ereg('[0-9a-zA-Z]', $string)){
				//半角数字、半角英字が混在しているかを確認
				if(!mb_ereg('[^0-9]', $string) || !mb_ereg('[^a-zA-Z]', $string)){
					 return 'mode';
				}
			}else{
				return 'mode';
			}
			break;

		// modeの指定が間違っていた場合
		default:
			return 'nonexistentMode';
			break;

	}

	// 最大値判定
	if ($max != '' || $max === 0) {
        switch ($mode) { 
            case 'Date':
                $paramAry = explode('/', $string);
                $checkAry = explode('/', $max);
                if (checkdate($paramAry[1], $paramAry[2], $paramAry[0]) && checkdate($checkAry[1], $checkAry[2], $checkAry[0])) {
                    if (mktime(0,0,0,$paramAry[1], $paramAry[2], $paramAry[0]) > mktime(0,0,0,$checkAry[1], $checkAry[2], $checkAry[0])) {
                        return 'max';
                    }
                }
                break;

            default:
        		if (mb_strwidth($string) > $max) {
        				return 'max';
        		}
                break;
        }
	}

	// 最小値判定
	if ($min != '' || $min === 0) {
        switch ($mode) { 
            case 'Date':
                $paramAry = explode('/', $string);
                $checkAry = explode('/', $min);
                if (checkdate($paramAry[1], $paramAry[2], $paramAry[0]) && checkdate($checkAry[1], $checkAry[2], $checkAry[0])) {
                    if (mktime(0,0,0,$paramAry[1], $paramAry[2], $paramAry[0]) < mktime(0,0,0,$checkAry[1], $checkAry[2], $checkAry[0])) {
                        return 'min';
                    }
                }
                break;

            default:
        		if (mb_strwidth($string) < $min) {
        				return 'min';
        		}
                break;
        }
	}

	return 'ok';

}


/*
 * 対象文字列が指定された値(array)の中に存在するか判定する
 * 引数  ：$string => 対象文字列
 *       ：$allows => 指定された値（array）
 * 戻り値：エラー状況 ⇒ true  ：エラーが無かった場合
 *                       false ：対象文字列が指定された値の中に存在しなかった場合
 *
 * create 2007/03/15 H.Osugi
 *
 */
function checkDataExist($string, $allows) {

	$isExist = in_array($string, $allows);

	return $isExist;

}

/*
 * 機能  ：エラー判定用のサイズデータ取得
 * 引数  ：$dbConnect  => コネクションハンドラ
 *       ：$post       => POSTデータ
 * 戻り値：なし
 *
 * create 2008/04/15 W.Takasaki
 *
 */
function getValidateSizeData($dbConnect, $post)
{
    // 初期化
    $returnAry = array();

    // サイズの項目名を取得
    $sql = "";
    $sql .= " SELECT";
    $sql .=     " I.ItemID";
    $sql .=    " ,I.SizeID";
    $sql .= " FROM";
    $sql .=    " M_Item I";
    $sql .= " WHERE";
    $sql .=     " I.Del = " . DELETE_OFF;

    $result = db_Read($dbConnect, $sql);

    // 検索結果が配列ではない場合
    if (!is_array($result) || count($result) <= 0) {
        return false;
    }

    foreach ($result as $key => $val) {    
        // 初期化
        $returnAry[$key]['validAry'] = array();

        $returnAry[$key]['name'] = 'size'.$val['ItemID'];

        $returnAry[$key]['validAry'] = array_keys(getSize($dbConnect, $val['SizeID'], 0));
//var_dump($returnAry[$key]['validAry']);
    }

    return $returnAry;
}


/* ../../include/checkData.php end */


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


/* ../../include/checkDuplicateAppli.php start */

/*
 * 申請番号がすでに登録されていないか判定
 * checkRequestNo.php
 *
 * create 2007/04/05 H.Osugi
 *
 */

/*
 * 申請番号がすでに登録されていないかを判定する
 * 引数  ：$dbConnect  => コネクションハンドラ
 *       ：$requestNo  => 検証したいAppliNo
 *       ：$returnUrl  => 戻り先URL
 *       ：$mode       => 1：発注 / 2：交換 / 3：返却
 * 戻り値：なし
 */
function checkDuplicateAppliNo($dbConnect, $requestNo, $returnUrl, $mode) {

	// 選択されたorderDetID
	if($requestNo == '') {
		return;
	}

	// 該当の申請番号が存在しないかを判定する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" count(*) as count_order";
	$sql .= " FROM";
	$sql .= 	" T_Order";
	$sql .= " WHERE";

	if ($mode == '2') {
		$sql .= 	" (";
		$sql .= 			" AppliNo = '" . db_Escape('A' . $requestNo) . "'";
		$sql .= 		" OR";
		$sql .= 			" AppliNo = '" . db_Escape('R' . $requestNo) . "'";
		$sql .= 	" )";
	}
	else {
		$sql .= 	" AppliNo = '" . db_Escape($requestNo) . "'";
	}

	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;

	$result = db_Read($dbConnect, $sql);

	// 該当の申請番号が存在する場合
	if (isset($result[0]['count_order']) && $result[0]['count_order'] > 0) {

		// エラー画面で必要な値のセット
		$hiddens = array();
		$hiddens['errorName'] = 'checkRequestNo';
		switch ($mode) {
			case '1':
				$hiddens['menuName']  = 'isMenuOrder';
				break;
			case '2':
				$hiddens['menuName']  = 'isMenuExchange';
				break;
			case '3':
				$hiddens['menuName']  = 'isMenuReturn';
				break;
			default:
				break;
		}
			
		$hiddens['returnUrl'] = $returnUrl;
		$hiddens['errorId'][] = '901';

		redirectPost(HOME_URL . 'error.php', $hiddens);

	}
}


/* ../../include/checkDuplicateAppli.php end */


/* ../../include/castHidden.php start */

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


/* ../../include/castHidden.php end */


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


/* ../../include/checkExchange.php start */

/*
 * 交換できるユニフォームか判定
 * checkExchange.php
 *
 * create 2007/03/20 H.Osugi
 *
 */

/*
 * 交換できないユニフォームが存在しないかを判定する
 * 引数  ：$dbConnect   => コネクションハンドラ
 *       ：$orderDetIds => 検証したいOrderDetID(array)
 *       ：$returnUrl  => 戻り先URL
 * 戻り値：なし
 */
function checkExchange($dbConnect, $orderDetIds, $returnUrl, $hiddenHtml) {

	// 選択されたorderDetID
	$orderDetId = '';
	if(is_array($orderDetIds)) {
		$orderDetId = implode(', ', $orderDetIds);
	}

//var_dump("orderDetId:" . $orderDetId);die;


	// 交換できないユニフォームが存在しないかを判定する
    if ($orderDetId != '') {
    	$sql  = "";
    	$sql .= " SELECT";
    	$sql .= 	" count(*) as count_staffdet";
    	$sql .= " FROM";
    	$sql .= 	" T_Staff_Details";
    	$sql .= " WHERE";
    	$sql .= 	" OrderDetID IN (" . db_Escape($orderDetId) . ")";
    	$sql .= " AND";
    	$sql .= 	" Status <> " . STATUS_SHIP;			// 出荷済
    	$sql .= " AND";
    	$sql .= 	" Status <> " . STATUS_DELIVERY;		// 納品済
    	$sql .= " AND";
    	$sql .= 	" Del = " . DELETE_OFF;

    	$result = db_Read($dbConnect, $sql);
    }
	
	// 交換できないユニフォームが存在する場合
	if ($orderDetId == '' || !isset($result[0]['count_staffdet']) || $result[0]['count_staffdet'] > 0) {

		$hiddenHtml = castHtmlEntity($hiddenHtml);

		// エラー画面で必要な値のセット
		$hiddens = array();
		$hiddens['errorName'] = 'koukanShinsei';
		$hiddens['menuName']  = 'isMenuExchange';
		$hiddens['returnUrl'] = $returnUrl;
		$hiddens['errorId'][] = '904';

		if (is_array($hiddenHtml)) {
			$hiddens = array_merge($hiddens, $hiddenHtml);
		}

		redirectPost(HOME_URL . 'error.php', $hiddens);

	}
}


/* ../../include/checkExchange.php end */


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


/* ./koukan_shinsei.val.php start */

/*
 * エラー判定処理
 * koukan_shinsei.val.php
 *
 * create 2007/03/20 H.Osugi
 *
 *
 */

/*
 * 機能  ：エラー判定を行う
 * 引数  ：$dbConnect  => コネクションハンドラ
 *       ：$post       => POSTデータ
 *       ：$sizeData1 => サイズ1
 *       ：$sizeData2 => サイズ2
 * 戻り値：なし
 *
 * create 2007/03/20 H.Osugi
 *
 */
function validatePostData($dbConnect, $post) {

	// 初期化
	$hiddens = array();

	// 郵便番号（前半）が存在しなければ初期化
	if (!isset($post['zip1'])) {
		$post['zip1'] = '';
	}

	// 郵便番号（前半）の判定
	$isZipError = false;
	$result = checkData(trim($post['zip1']), 'Digit', true, 3, 3);

	// エラーが発生したならば、エラーメッセージを取得
	switch ($result) {

		// 空白ならば
		case 'empty':
			$isZipError = true;
			$hiddens['errorId'][] = '001';
			break;
			
		// 半角以外の文字ならば
		case 'mode':
			$isZipError = true;
			$hiddens['errorId'][] = '002';
			break;

		// 指定文字数以外ならば
		case 'max':
		case 'min':
			$isZipError = true;
			$hiddens['errorId'][] = '002';
			break;

		default:
			break;

	}

	// エラーが発生したならば、エラーメッセージを取得
	if ($isZipError == false) {

		// 郵便番号（後半）が存在しなければ初期化
		if (!isset($post['zip2'])) {
			$post['zip2'] = '';
		}

		// 郵便番号（後半）の判定
		$result = checkData(trim($post['zip2']), 'Digit', true, 4, 4);

		switch ($result) {
	
			// 空白ならば
			case 'empty':
				$hiddens['errorId'][] = '001';
				break;
				
			// 半角以外の文字ならば
			case 'mode':
				$hiddens['errorId'][] = '002';
				break;
	
			// 指定文字数以外ならば
			case 'max':
			case 'min':
				$hiddens['errorId'][] = '002';
				break;
	
			default:
				break;
	
		}
	}

	// 住所が存在しなければ初期化
	if (!isset($post['address'])) {
		$post['address'] = '';
	}

	// 住所の判定
	$result = checkData(trim($post['address']), 'Text', true, 240);

	// エラーが発生したならば、エラーメッセージを取得
	switch ($result) {

		// 空白ならば
		case 'empty':
			$hiddens['errorId'][] = '011';
			break;
			
		// 最大値超過ならば
		case 'max':
			$hiddens['errorId'][] = '012';
			break;

		default:
			break;

	}

	// 出荷先名が存在しなければ初期化
	if (!isset($post['shipName'])) {
		$post['shipName'] = '';
	}

	// 出荷先名の判定
	$result = checkData(trim($post['shipName']), 'Text', true, 120);

	// エラーが発生したならば、エラーメッセージを取得
	switch ($result) {

		// 空白ならば
		case 'empty':
			$hiddens['errorId'][] = '021';
			break;
			
		// 最大値超過ならば
		case 'max':
			$hiddens['errorId'][] = '022';
			break;

		default:
			break;

	}

	// ご担当者が存在しなければ初期化
	if (!isset($post['staffName'])) {
		$post['staffName'] = '';
	}

	// ご担当者の判定
	$result = checkData(trim($post['staffName']), 'Text', true, 40);

	// エラーが発生したならば、エラーメッセージを取得
	switch ($result) {

		// 空白ならば
		case 'empty':
			$hiddens['errorId'][] = '031';
			break;
			
		// 最大値超過ならば
		case 'max':
			$hiddens['errorId'][] = '032';
			break;

		default:
			break;

	}

	// 電話番号が存在しなければ初期化
	if (!isset($post['tel'])) {
		$post['tel'] = '';
	}

	// 電話番号の判定
	$result = checkData(trim($post['tel']), 'Tel', true, 15);

	// エラーが発生したならば、エラーメッセージを取得
	switch ($result) {

		// 空白ならば
		case 'empty':
			$hiddens['errorId'][] = '041';
			break;

		// 電話番号に利用可能な文字（数値とハイフン）以外の文字ならば
		case 'mode':
			$hiddens['errorId'][] = '042';
			break;

		// 最大値超過ならば
		case 'max':
			$hiddens['errorId'][] = '043';
			break;

		default:
			break;

	}

	// メモが存在しなければ初期化
	if (!isset($post['memo'])) {
		$post['memo'] = '';
	}

	// メモの判定
	$result = checkData(trim($post['memo']), 'Text', true, 128);

	// エラーが発生したならば、エラーメッセージを取得
	switch ($result) {

		// 空白ならば
		case 'empty':
//			if ($post['appliReason'] == APPLI_REASON_EXCHANGE_INFERIORITY) {	// 不良品交換の場合はメモ欄必須　2008/07/24
			$hiddens['errorId'][] = '052';
//			}

			break;

		// 最大値超過ならば
		case 'max':
			$hiddens['errorId'][] = '051';
			break;

		default:
			break;

	}

	// ユニフォーム選択の判定
	$countOrderIds = count($post['orderDetIds']);

    // １つも選択されていない場合
    if ($countOrderIds <= 0) {
        $hiddens['errorId'][] = '071';
    } else {    // 選択されたアイテムがあればサイズをチェック

        // サイズのエラー判定フラグ
        $isSizeError = false;

        // -----------------------------------------------------------------//
        // サイズ交換のみ、交換後サイズ選択チェックを行う。
        // サイズ交換以外の交換は、サイズを変更可能にすると、
        // 同一アイテム同一サイズ保持の考え方が成立しなくなるため。
        // -----------------------------------------------------------------//
        //if ($post['appliReason'] == APPLI_REASON_EXCHANGE_SIZE) {

            // サイズの判定
            foreach ($post['orderDetIds'] as $key => $selectedID) {

                if ((int)$selectedID) { 
		    	    // チェックされたアイテムに対して、展開されているサイズを取得
                    $sizeDataAry = getSizeByOrderDetId($dbConnect, $selectedID, 0);

                    // サイズのエラー判定フラグ
                    $isSizeError = false;
            
                    // サイズ項目が存在しなければ初期化
                    if (!isset($post['size'.$selectedID])) {
                        $post['size'.$selectedID] = '';
                    }
    
                    // 判定
                    $result = array_key_exists(trim($post['size'][$selectedID]), $sizeDataAry);
                    // 選択されていなければ、エラーメッセージを取得
                    if (!$result) {
                        $isSizeError = true;
                    }
		    	} else {
                    $isSizeError = true;
		    	}
            }

            if ($isSizeError) {
                $hiddens['errorId'][] = '071';		// サイズが選択されていないアイテムがあります。
            }
        //}
    }


	// １つも選択されていない場合
	if ($countOrderIds <= 0) {
		$hiddens['errorId'][] = '061';
	}

	// サイズ交換の場合のみ同サイズを選択されていないか判定
	if (($post['appliReason'] == APPLI_REASON_EXCHANGE_FIRST || $post['appliReason'] == APPLI_REASON_EXCHANGE_SIZE) && $isSizeError == false && $countOrderIds > 0) {

		$orderDetIds = '';
		if(is_array($post['orderDetIds'])) {
			$check = true;
			foreach($post['orderDetIds'] as $key => $value) {
				if (!(int)$value) {
					$check = false;
				}
			}
			if ($check) { 
				$orderDetIds = implode(', ', $post['orderDetIds']);

				//furukawa
//var_dump("itemIdselect:" . $post['itemId']);

			}
		}
		if ($check) {

    		// スタッフコードの一覧を取得する
    		$sql  = "";
    		$sql .= " SELECT";
    		$sql .= 	" OrderDetID,";
    		$sql .= 	" ItemID,";
    		$sql .= 	" Size";
    		$sql .= " FROM";
    		$sql .= 	" T_Order_Details";
    		$sql .= " WHERE";
    		$sql .= 	" OrderDetID IN (" . db_Escape($orderDetIds) . ")";
    		$sql .= " AND";
    		$sql .= 	" Del = 0";
    		$sql .= " ORDER BY";
    		$sql .= 	" ItemID";


    		$result = db_Read($dbConnect, $sql);
    
		    $itemIdWk = '';
		    $itemIds = array();
				$j = 0;
			$resultsTmp = array();
			$resultItem = array();
			$isSizeError = false;

    		$countOrderDet = count($result);
    		for ($i=0; $i<$countOrderDet; $i++) {

                $sizeDataAry = getSizeByOrderDetId($dbConnect, $result[$i]['OrderDetID'], 0);

				// サイズ交換の場合は同じサイズの交換はできません
    			if ($sizeDataAry[$post['size'][$result[$i]['OrderDetID']]] == $result[$i]['Size']) {
    				$hiddens['errorId'][] = '081';
    				break;
    			}

                if ( !array_key_exists($result[$i]['ItemID'], $resultsTmp) ) {
                    // オブジェクトにキーsales_dateが含まれていない:
                    // 集計結果の初期値を生成する。
                    $resultItem = array(
                                    'ItemID' => $result[$i]['ItemID'],
                                    'size'   => $result[$i]['Size'],
                                    'total'  => 1
                                   );

                    // 生成した初期値をキーsales_dateに関連付けてオブジェクトに格納する。
                    $resultsTmp[$result[$i]['ItemID']] = $resultItem;
                }
                else 
                {
                    if($resultsTmp[$result[$i]['ItemID']]['size'] != $size)
                    {
                        $isSizeError = true;
                        break;
                    }

                    $resultsTmp[$result[$i]['ItemID']]['total'] += 1;
                }
    		}

			if ($isSizeError == false) {

                foreach ($resultsTmp as $key => $value) {
                
	                $isShip = false;
					$koukanMaeItem = array();
					$koukanMaeItemCount = 0;

               		//同一アイテムは全て同じサイズをご指定ください。// 対象の商品は未出荷の商品が
   					if (isset($value['ItemID'])) {

   					    // 交換前のアイテム毎のサイズ、数量抽出
			    		$koukanMaeItem = getSizeKoukanUnshipped($dbConnect, $post['staffId'], $appliReason, $value['ItemID']);

						if (count($koukanMaeItem) > 0) {

							for ($j=0; $j < count($koukanMaeItem); $j++) {

								// 交換前のアイテムに未出荷のものがあればエラーにする。
								if ($koukanMaeItem[$j]['Status'] <= STATUS_ORDER) {
									$isShip = true;
								}
								// 交換前アイテムカウント集計（アイテム毎）
								$koukanMaeItemCount = $koukanMaeItemCount + $koukanMaeItem[$j]['ItemCount']++;
							}
						}

						// 貸与アイテムで未出荷のアイテムが含まれる場合、エラー
						if ($isShip == true) {
							$hiddens['errorId'][] = '083';
						}

						//// 貸与アイテムで未出荷のアイテムが含まれる場合（且つ）貸与アイテム数と選択したアイテム数が不一致の場合はエラー
						//if ($isShip == false) {
						//	// 同一アイテムは全て同じサイズをご指定ください。
						//	if ($koukanMaeItemCount != $value['total']) {
						//		$hiddens['errorId'][] = '082';
						//	}
						//}
   					}
                }
            }

		} else {
				$hiddens['errorId'][] = '081';
		}
	}

	// エラーが存在したならば、エラー画面に遷移
	if (count($hiddens['errorId']) > 0) {
		$hiddens['errorName'] = 'koukanShinsei';
		$hiddens['menuName']  = 'isMenuExchange';
		$hiddens['returnUrl'] = 'koukan/koukan_shinsei.php';
		$errorUrl             = HOME_URL . 'error.php';

		$post['koukanShinseiFlg'] = '1';

		// POST値をHTMLエンティティ
		$post = castHtmlEntity($post); 

		// hidden値の成型
		$countOrderDetIds = count($post['orderDetIds']);
		for ($i=0; $i<$countOrderDetIds; $i++) {
			$post['orderDetIds[' . $i . ']'] = $post['orderDetIds'][$i];
			$post['size[' . $post['orderDetIds'][$i] . ']'] = $post['size'][$post['orderDetIds'][$i]];
		}
		$notArrowKeys = array('orderDetIds' , 'size', 'sizeType');
		$hiddenHtml = castHiddenError($post, $notArrowKeys);

		$hiddens = array_merge($hiddens, $hiddenHtml);

		redirectPost($errorUrl, $hiddens);

	}

}


/* ./koukan_shinsei.val.php end */


/* ./koukan_tokusun.val.php start */

/*
 * エラー判定処理
 * koukan_tokusun.val.php
 *
 * create 2007/04/03 H.Osugi
 *
 *
 */

/*
 * 機能  ：エラー判定を行う
 * 引数  ：$post       => POSTデータ
 * 戻り値：なし
 *
 * create 2007/04/03 H.Osugi
 *
 */
function validateTokData($post) {

	// 初期化
	$hiddens = array();

	$isSizeInputFlag = false;
	$isBikouInputFlag = false;

	// 身長が存在しなければ初期化
	if (!isset($post['high'])) {
		$post['high'] = '';
	}

	// 身長の判定
	$result = checkData(trim($post['high']), 'Numeric', true, 8);

	// エラーが発生したならば、エラーメッセージを取得
	switch ($result) {

		// 空白ならば
		case 'empty':
			//$hiddens['errorId'][] = '001';
			break;
			
		// 数値以外の文字ならば
		case 'mode':
			$hiddens['errorId'][] = '002';
			break;

		// 最大値超過ならば
		case 'max':
			$hiddens['errorId'][] = '002';
			break;

		default:
			$isSizeInputFlag = true;
			break;

	}

	// 体重が存在しなければ初期化
	if (!isset($post['weight'])) {
		$post['weight'] = '';
	}

	// 体重の判定
	$result = checkData(trim($post['weight']), 'Numeric', true, 8);

	// エラーが発生したならば、エラーメッセージを取得
	switch ($result) {

		// 空白ならば
		case 'empty':
			//$hiddens['errorId'][] = '011';
			break;
			
		// 数値以外の文字ならば
		case 'mode':
			$hiddens['errorId'][] = '012';
			break;

		// 最大値超過ならば
		case 'max':
			$hiddens['errorId'][] = '012';
			break;

		default:
			$isSizeInputFlag = true;
			break;

	}

	// バストが存在しなければ初期化
	if (!isset($post['bust'])) {
		$post['bust'] = '';
	}

	// バストの判定
	$result = checkData(trim($post['bust']), 'Numeric', true, 8);

	// エラーが発生したならば、エラーメッセージを取得
	switch ($result) {

		// 空白ならば
		case 'empty':
			//$hiddens['errorId'][] = '021';
			break;
		
		// 数値以外の文字ならば
		case 'mode':
			$hiddens['errorId'][] = '022';
			break;

		// 最大値超過ならば
		case 'max':
			$hiddens['errorId'][] = '022';
			break;

		default:
			$isSizeInputFlag = true;
			break;

	}

	// ウエストが存在しなければ初期化
	if (!isset($post['waist'])) {
		$post['waist'] = '';
	}

	// ウエストの判定
	$result = checkData(trim($post['waist']), 'Numeric', true, 8);

	// エラーが発生したならば、エラーメッセージを取得
	switch ($result) {

		// 空白ならば
		case 'empty':
			//$hiddens['errorId'][] = '031';
			break;
			
		// 数値以外の文字ならば
		case 'mode':
			$hiddens['errorId'][] = '032';
			break;

		// 最大値超過ならば
		case 'max':
			$hiddens['errorId'][] = '032';
			break;

		default:
			$isSizeInputFlag = true;
			break;

	}

	// ヒップが存在しなければ初期化
	if (!isset($post['hips'])) {
		$post['hips'] = '';
	}

	// ヒップの判定
	$result = checkData(trim($post['hips']), 'Numeric', true, 8);

	// エラーが発生したならば、エラーメッセージを取得
	switch ($result) {

		// 空白ならば
		case 'empty':
			//$hiddens['errorId'][] = '041';
			break;
			
		// 数値以外の文字ならば
		case 'mode':
			$hiddens['errorId'][] = '042';
			break;

		// 最大値超過ならば
		case 'max':
			$hiddens['errorId'][] = '042';
			break;

		default:
			$isSizeInputFlag = true;
			break;

	}

	// 肩幅が存在しなければ初期化
	if (!isset($post['shoulder'])) {
		$post['shoulder'] = '';
	}

	// 肩幅の判定
	$result = checkData(trim($post['shoulder']), 'Numeric', true, 8);

	// エラーが発生したならば、エラーメッセージを取得
	switch ($result) {

		// 空白ならば
		case 'empty':
			//$hiddens['errorId'][] = '051';
			break;
			
		// 数値以外の文字ならば
		case 'mode':
			$hiddens['errorId'][] = '052';
			break;

		// 最大値超過ならば
		case 'max':
			$hiddens['errorId'][] = '052';
			break;

		default:
			$isSizeInputFlag = true;
			break;

	}

	// 袖丈が存在しなければ初期化
	if (!isset($post['sleeve'])) {
		$post['sleeve'] = '';
	}

	// 袖丈の判定
	$result = checkData(trim($post['sleeve']), 'Numeric', true, 8);

	// エラーが発生したならば、エラーメッセージを取得
	switch ($result) {

		// 空白ならば
		case 'empty':
			//$hiddens['errorId'][] = '061';
			break;
			
		// 数値以外の文字ならば
		case 'mode':
			$hiddens['errorId'][] = '062';
			break;

		// 最大値超過ならば
		case 'max':
			$hiddens['errorId'][] = '062';
			break;

		default:
			$isSizeInputFlag = true;
			break;

	}

    // 着丈が存在しなければ初期化
    if (!isset($post['kitake'])) {
        $post['kitake'] = '';
    }

    // 着丈の判定
    $result = checkData(trim($post['kitake']), 'Numeric', true, 8);

    // エラーが発生したならば、エラーメッセージを取得
    switch ($result) {

        // 空白ならば
        case 'empty':
            //$hiddens['errorId'][] = '091';
            break;
            
        // 数値以外の文字ならば
        case 'mode':
            $hiddens['errorId'][] = '092';
            break;

        // 最大値超過ならば
        case 'max':
            $hiddens['errorId'][] = '092';
            break;

        default:
			$isSizeInputFlag = true;
            break;

    }

    // 裄丈が存在しなければ初期化
    if (!isset($post['yukitake'])) {
        $post['yukitake'] = '';
    }

    // 裄丈の判定
    $result = checkData(trim($post['yukitake']), 'Numeric', true, 8);

    // エラーが発生したならば、エラーメッセージを取得
    switch ($result) {

        // 空白ならば
        case 'empty':
            //$hiddens['errorId'][] = '101';
            break;
            
        // 数値以外の文字ならば
        case 'mode':
            $hiddens['errorId'][] = '102';
            break;

        // 最大値超過ならば
        case 'max':
            $hiddens['errorId'][] = '102';
            break;

        default:
			$isSizeInputFlag = true;
            break;

    }

    // 股下が存在しなければ初期化
    if (!isset($post['inseam'])) {
        $post['inseam'] = '';
    }

    // 股下の判定
    $result = checkData(trim($post['inseam']), 'Numeric', true, 8);

    // エラーが発生したならば、エラーメッセージを取得
    switch ($result) {

        // 空白ならば
        case 'empty':
            //$hiddens['errorId'][] = '111';
            break;
            
        // 数値以外の文字ならば
        case 'mode':
            $hiddens['errorId'][] = '112';
            break;

        // 最大値超過ならば
        case 'max':
            $hiddens['errorId'][] = '112';
            break;

        default:
			$isSizeInputFlag = true;
            break;

    }

	// 首周りが存在しなければ初期化
	if (!isset($post['length'])) {
		$post['length'] = '';
	}

	// 首周りの判定
	$result = checkData(trim($post['length']), 'Numeric', true, 8);

	// エラーが発生したならば、エラーメッセージを取得
	switch ($result) {

		// 空白ならば
		case 'empty':
			//$hiddens['errorId'][] = '071';
			break;
			
		// 数値以外の文字ならば
		case 'mode':
			$hiddens['errorId'][] = '072';
			break;

		// 最大値超過ならば
		case 'max':
			$hiddens['errorId'][] = '072';
			break;

		default:
			$isSizeInputFlag = true;
			break;

	}

	// 特寸備考が存在しなければ初期化
	if (!isset($post['tokMemo'])) {
		$post['tokMemo'] = '';
	}

	// 特寸備考の判定
	$result = checkData(trim($post['tokMemo']), 'Text', true, 128);

	// エラーが発生したならば、エラーメッセージを取得
	switch ($result) {

		// 空白ならば
		case 'empty':
			//$hiddens['errorId'][] = '081';
			break;
			
		// 最大値超過ならば
		case 'max':
			$hiddens['errorId'][] = '082';
			break;

		default:
			$isBikouInputFlag = true;
			break;

	}

	// ヌード寸法もしくは備考欄のどちらかに入力があったかの判定
	if (!$isSizeInputFlag && !$isBikouInputFlag) {
		$hiddens['errorId'][] = '121';
	}

	// エラーが存在したならば、エラー画面に遷移
	if (count($hiddens['errorId']) > 0) {
		$hiddens['errorName'] = 'koukanTokusun';
		$hiddens['menuName']  = 'isMenuExchange';
		$hiddens['returnUrl'] = 'koukan/koukan_tokusun.php';
		$errorUrl             = HOME_URL . 'error.php';

		$post['tokFlg'] = 1;

		// POST値をHTMLエンティティ
		$post = castHtmlEntity($post); 

		$hiddenHtml = castHiddenError($post);

		$hiddens = array_merge($hiddens, $hiddenHtml);
		redirectPost($errorUrl, $hiddens);

	}

}


/* ./koukan_tokusun.val.php end */



// 初期設定
$isMenuExchange = true;			// 交換のメニューをアクティブに

// 変数の初期化 ここから *******************************************************
$requestNo = '';					// 申請番号
$staffCode = '';					// スタッフコード
$zip1      = '';					// 郵便番号（前半3桁）
$zip2      = '';					// 郵便番号（後半4桁）
$address   = '';					// 住所
$shipName  = '';					// 出荷先名
$staffName = '';					// ご担当者
$tel       = '';					// 電話番号
$memo      = '';					// メモ

$displayRequestNo = '';				// 申請番号（表示用）

$isEmptyMemo = true;				// メモが空かどうかを判定するフラグ

$selectedSize = array();			// 選択されたサイズ

$selectedReason1 = false;			// 交換理由（サイズ交換）
$selectedReason2 = false;			// 交換理由（汚損・破損交換）
$selectedReason3 = false;			// 交換理由（紛失交換）
$selectedReason4 = false;			// 交換理由（不良品交換）
$selectedReason5 = false;           // 交換理由（初回サイズ交換）

$dispTwoPane     = false;           // 画面構成を２段にするか

$haveTok  = false;					// 特寸から遷移してきたか判定フラグ

$high     = '';						// 身長
$weight   = '';						// 体重
$bust     = '';						// バスト
$waist    = '';						// ウエスト
$hips     = '';						// ヒップ
$shoulder = '';						// 肩幅
$sleeve   = '';						// 袖丈
$length   = '';						// 首周り
$kitake   = '';                     // 着丈
$yukitake = '';                     // 裄丈
$inseam   = '';                     // 股下
$tokMemo  = '';						// 特寸備考

$returnUrl = '';					// 戻り先URL

// 変数の初期化 ここまで ******************************************************

// スタッフIDが取得できなければエラーに
if (!isset($_POST['staffId']) || $_POST['staffId'] == '') {
	// TOP画面に強制遷移
    $returnUrl = HOME_URL . 'top.php';
	redirectPost($returnUrl, $hiddens);
}

// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST); 

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
        break;

    default:
        break;
}


//}

// 申請番号がすでに登録されていないか判定
checkDuplicateAppliNo($dbConnect, $_POST['requestNo'], 'koukan/koukan_top.php', 2);

// エラー判定
validatePostData($dbConnect, $_POST);

// 交換できないユニフォームが存在しないかを判定する
checkExchange($dbConnect, $_POST['orderDetIds'], 'koukan/koukan_shinsei.php', $_POST);

$items = getStaffOrderSelect($dbConnect, $post);


if (isset($post['tokFlg']) && $post['tokFlg'] == 1) {
	// 特寸エラー判定
	validateTokData($_POST);
}
else {
	// 特寸判定
	checkTok($dbConnect, $post, $items);
}

// 表示する値の成型 ここから +++++++++++++++++++++++++++++++++++++++++++++++++++

// 画面上部に表示するデータを取得
$headerData = getHeaderData($dbConnect, $post['staffId']);

// 申請番号
$requestNo = trim($post['requestNo']);
$displayRequestNo = 'A' . trim($requestNo);		// 頭文字に'A'をつける

// 店舗コード
$compCd = trim($headerData['CompCd']);
// 店舗ID
$compId = trim($headerData['CompID']);
// 店舗名
$compName = trim($headerData['CompName']);

// 着用者名
$personName = trim($headerData['PersonName']);

// スタッフコード
$staffCode = trim($headerData['StaffCode']);

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

if ($memo != '' || $memo === 0) {
	$isEmptyMemo = false;
}

// 戻り先URL
$returnUrl = './koukan_shinsei.php';

// 特寸から遷移してきた場合
if (isset($post['tokFlg']) && $post['tokFlg'] == 1) {

	$haveTok = true;

	// 身長
	$high     = trim($post['high']);

	// 体重
	$weight   = trim($post['weight']);

	// バスト
	$bust     = trim($post['bust']);

	// ウエスト
	$waist    = trim($post['waist']);

	// ヒップ
	$hips     = trim($post['hips']);

	// 肩幅
	$shoulder = trim($post['shoulder']);

	// 袖丈
	$sleeve   = trim($post['sleeve']);

	// 首周り
	$length   = trim($post['length']);

    // 着丈
    $kitake   = trim($post['kitake']);

    // 裄丈
    $yukitake = trim($post['yukitake']);

    // 股下
    $inseam   = trim($post['inseam']);

	// 特寸備考
	$tokMemo  = trim($post['tokMemo']);

	// 戻り先URL
	$returnUrl = './koukan_tokusun.php';
	
}

if ($isLevelAdmin == true) {
	$searchCompCd    = $post['searchCompCd'];		// 店舗番号
	$searchCompName  = $post['searchCompName'];	// 店舗名
}

// hidden値の成型

$countOrderDetIds = count($post['orderDetIds']);

for ($i=0; $i<$countOrderDetIds; $i++) {
    $post['orderDetIds[' . $i . ']'] = $post['orderDetIds'][$i];
    if (count(getSize($dbConnect, $post['sizeType'][$post['orderDetIds'][$i]], 1)) > 1) {  // フリーかどうか判定
        $post['size[' . $post['orderDetIds'][$i] . ']'] = trim($post['size'][$post['orderDetIds'][$i]]);
    }
    $post['sizeType[' . $post['orderDetIds'][$i] . ']'] = trim($post['sizeType'][$post['orderDetIds'][$i]]);
    $post['itemUnused[' . $post['orderDetIds'][$i] . ']'] = trim($post['itemUnused'][$post['orderDetIds'][$i]]);
}
$notArrowKeys = array('orderDetIds' , 'size', 'sizeType', 'itemUnused');

$hiddenHtml = castHidden($post, $notArrowKeys);
// 表示する値の成型 ここまで ++++++++++++++++++++++++++++++++++++++++++++++++++

/*
 * 選択された商品一覧情報を取得する
 * 引数  ：$dbConnect    => コネクションハンドラ
 *       ：$post         => POST値
 *       ：$sizeData1    => サイズ情報1
 *       ：$sizeData2    => サイズ情報2
 *       ：$sizeData3    => サイズ情報3
 * 戻り値：$result       => 選択された商品一覧情報
 *
 * create 2007/03/20 H.Osugi
 *
 */
function getStaffOrderSelect($dbConnect, $post) {

	$orderDetIds = '';
	if(is_array($post['orderDetIds'])) {
		$orderDetIds = implode(', ', $post['orderDetIds']);
	}

	// 商品情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" tod.OrderDetID,";
	$sql .= 	" mi.ItemID,";
	$sql .= 	" mi.ItemName,";
	$sql .= 	" tod.BarCd,";
	$sql .= 	" tod.Size,";
	$sql .= 	" mi.SizeID";
	$sql .= " FROM";
	$sql .= 	" T_Staff_Details tsd";
	$sql .= " INNER JOIN";
	$sql .= 	" T_Staff ts";
	$sql .= " ON";
	$sql .= 	" tsd.StaffID = ts.StaffID";
	$sql .= " AND";
	$sql .= 	" ts.Del = " . DELETE_OFF;
	$sql .= " INNER JOIN";
	$sql .= 	" T_Order_Details tod";
	$sql .= " ON";
	$sql .= 	" tsd.OrderDetID = tod.OrderDetID";
	$sql .= " AND";
	$sql .= 	" tod.Del = " . DELETE_OFF;
	$sql .= " INNER JOIN";
	$sql .= 	" M_Item mi";
	$sql .= " ON";
	$sql .= 	" tod.ItemID = mi.ItemID";
	$sql .= " AND";
	$sql .= 	" mi.Del = " . DELETE_OFF;
	$sql .= " INNER JOIN";
	$sql .= 	" T_Order tor";
	$sql .= " ON";
	$sql .= 	" tod.OrderID = tor.OrderID";
	$sql .= " AND";
	$sql .= 	" tor.Del = " . DELETE_OFF;
	$sql .= " WHERE";
	$sql .= 	" tod.OrderDetID IN (" . db_Escape($orderDetIds) . ")";
	$sql .= " AND";
	$sql .= 	" tsd.Del = " . DELETE_OFF;
  	$sql .= " ORDER BY";
	$sql .= 	" mi.ItemID ASC";

//var_dump($sql);

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	// 取得した値をHTMLエンティティ
	$resultCount = count($result);
	for ($i=0; $i<$resultCount; $i++) {
		$result[$i]['OrderDetID'] = $result[$i]['OrderDetID'];
		$result[$i]['ItemName']   = castHtmlEntity($result[$i]['ItemName']);
		$result[$i]['BarCd']      = castHtmlEntity($result[$i]['BarCd']);
		$result[$i]['Size']       = castHtmlEntity($result[$i]['Size']);
		$result[$i]['SizeID']     = $result[$i]['SizeID'];

		// バーコードが空かどうか判定
		$result[$i]['isEmptyBarCd'] = false;
		if ($result[$i]['BarCd'] == '') {
			$result[$i]['isEmptyBarCd'] = true;
		}

		// サイズの表示情報を成型
        $sizeAry = array();
        $sizeAry = getSize($dbConnect, $result[$i]['SizeID'], 1);
		if (is_array($sizeAry) && isSetValue($sizeAry)) {
    		$result[$i]['selectedSize'] = castHtmlEntity($sizeAry[$post['size'][$result[$i]['OrderDetID']]]);
		}

        // 選択チェックボックスが選択されているか判定
        $result[$i]['isUnused'] = false;
        if (isset($post['itemUnused'][$result[$i]['OrderDetID']]) && trim($post['itemUnused'][$result[$i]['OrderDetID']]) == '1') {
            $result[$i]['isUnused'] = true;
        }
	}

	return  $result;

}

/*
 * 特寸が選択されているか判定
 * 引数  ：$post        => POST値
 *     ：$dispTwoPane => true:マタニティか役職変更
 * 戻り値：なし
 *
 * create 2007/04/03 H.Osugi
 *
 */
function checkTok($dbConnect, $post, $items) {

	// ==============================================================
	// 対象の申請に特注アイテムが含まれているか確認
	// ==============================================================
	$isTokFlg = false;
	for ($i=0; $i<count($items); $i++) {
		if(isset($items[$i]['selectedSize']) && (strpos($items[$i]['selectedSize'], "特") !== false) && $items[$i]['selectedSize'] != '') {
		 	$isTokFlg = true;
		}
	}

	if ($isTokFlg == true) {

    	$tokUrl = './koukan_tokusun.php';

    	$post['koukanShinseiFlg'] = 1;

		$hiddenHtml = castHiddenError($post);

		// 特注入力画面に遷移
		redirectPost($tokUrl, $hiddenHtml);
	}
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
<?php if($selectedReason1) { ?>
          <h1>ユニフォーム交換確認　（サイズ交換）</h1>
<?php } ?>
<?php if($selectedReason2) { ?>
          
          <h1>ユニフォーム交換確認　（汚損・破損交換）</h1>
          
<?php } ?>
<?php if($selectedReason3) { ?>
          
          <h1>ユニフォーム交換確認　（紛失交換）</h1>
          
<?php } ?>
<?php if($selectedReason4) { ?>
          
          <h1>ユニフォーム交換確認　（不良品交換）</h1>
          
<?php } ?>
<?php if($selectedReason5) { ?>
          
          <h1>ユニフォーム交換確認　（初回サイズ交換）</h1>
          
<?php } ?>
          <table border="0" width="700" cellpadding="0" cellspacing="0" class="tb_1">
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
              <td  class="line"><?php isset($staffCode) ? print($staffCode) : print('&#123;staffCode&#125;'); ?></td>
              <td width="80" class="line">
                <span class="fbold">職員名</span>
              </td>
              <td width="400" class="line">
               <?php isset($personName) ? print($personName) : print('&#123;personName&#125;'); ?>
              </td>
            </tr>
            <tr height="30">
              <td><span class="fbold">出荷先</span></td>
              <td colspan="3">〒<?php isset($zip1) ? print($zip1) : print('&#123;zip1&#125;'); ?>-<?php isset($zip2) ? print($zip2) : print('&#123;zip2&#125;'); ?></td>
            </tr>
            <tr height="30">
              <td width="100"></td>
              <td width="100"><span class="fbold">住所</span></td> 
              <td width="482" colspan="2"><?php isset($address) ? print($address) : print('&#123;address&#125;'); ?></td>
            </tr>
            <tr height="25">
              <td width="100"></td>
              <td width="100"><span class="fbold">出荷先名</span></td> 
              <td width="482" colspan="2"><?php isset($shipName) ? print($shipName) : print('&#123;shipName&#125;'); ?></td>
            </tr>
            <tr height="25">
              <td width="100"></td>
              <td width="100"><span class="fbold">ご担当者</span></td>
              <td width="482" colspan="2"><?php isset($staffName) ? print($staffName) : print('&#123;staffName&#125;'); ?></td>
            </tr>
            <tr height="25">
              <td width="100" class="line">&nbsp;</td>
              <td width="100" class="line"><span class="fbold">電話番号</span></td>
              <td width="482" colspan="2" class="line"><?php isset($tel) ? print($tel) : print('&#123;tel&#125;'); ?></td>
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
              <td width="100" class="line"><span class="fbold">メモ</span></td>
              <td colspan="3" class="line">
<?php if($isEmptyMemo) { ?>
                
                &nbsp;
                
<?php } ?>
<?php if(!$isEmptyMemo) { ?>
                <?php isset($memo) ? print($memo) : print('&#123;memo&#125;'); ?>
<?php } ?>
              </td>
            </tr>
          </table>

          <h3>◆下記の内容で申請されています。交換しますか？</h3>
<?php if($selectedReason5) { ?>
          <table width="600" border="0" class="tb_1" cellpadding="0" cellspacing="3">
<?php } ?>
<?php if(!$selectedReason5) { ?>
          <table width="540" border="0" class="tb_1" cellpadding="0" cellspacing="3">
<?php } ?>
            <tr>
              <th align="center" width="200">アイテム名</th>
              <th align="center" width="100">現在のサイズ</th>
              <th align="center" width="100">単品番号</th>
              <th align="center" width="140">交換後のサイズ</th>
<?php if($selectedReason5) { ?>
              <th align="center" width="60">未着用</th>
<?php } ?>
            </tr>
<?php for ($i1_items=0; $i1_items<count($items); $i1_items++) { ?>
            <tr height="20">
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
              <td class="line2" align="center"><b><?php isset($items[$i1_items]['selectedSize']) ? print($items[$i1_items]['selectedSize']) : print('&#123;items.selectedSize&#125;'); ?></b></td>
<?php if($selectedReason5) { ?>
              <td class="line2" align="center">
<?php if($items[$i1_items]['isUnused']) { ?>
                  有り
<?php } ?>
<?php if(!$items[$i1_items]['isUnused']) { ?>
                  
                  &nbsp;
                  
<?php } ?>
              </td>
<?php } ?>
            </tr>
<?php } ?>
          </table>

<?php if($haveTok) { ?>
          
          <br>
          <table width="520" border="0" class="tb_2" cellpadding="0" cellspacing="1">
            <tr>
              <th align="center" width="40" rowspan="5">特注<br>入力</th>
              <th align="center" width="60">身長</th>
              <th align="center" width="60">体重</th>
              <th align="center" width="60">バスト</th>
              <th align="center" width="60">ウエスト</th>
              <th align="center" width="60">ヒップ</th>
              <th align="center" width="60">肩幅</th>
              <th align="center" width="60">袖丈</th>
              <th align="center" width="60">着丈</th>
            </tr>
            <tr>
              <td align="center"><?php isset($high) ? print($high) : print('&#123;high&#125;'); ?>cm</td>
              <td align="center"><?php isset($weight) ? print($weight) : print('&#123;weight&#125;'); ?>kg</td>
              <td align="center"><?php isset($bust) ? print($bust) : print('&#123;bust&#125;'); ?>cm</td>
              <td align="center"><?php isset($waist) ? print($waist) : print('&#123;waist&#125;'); ?>cm</td>
              <td align="center"><?php isset($hips) ? print($hips) : print('&#123;hips&#125;'); ?>cm</td>
              <td align="center"><?php isset($shoulder) ? print($shoulder) : print('&#123;shoulder&#125;'); ?>cm</td>
              <td align="center"><?php isset($sleeve) ? print($sleeve) : print('&#123;sleeve&#125;'); ?>cm</td>
              <td align="center"><?php isset($kitake) ? print($kitake) : print('&#123;kitake&#125;'); ?>cm</td>
            </tr>
            <tr>
              <th align="center" width="80">裄丈</th>
              <th align="center" width="80">股下</th>
              <th align="center" width="80">首周り</th>
              <th align="center" width="400" colspan="5">&nbsp;</th>
            </tr>
            <tr>
              <td align="center"><?php isset($yukitake) ? print($yukitake) : print('&#123;yukitake&#125;'); ?>cm</td>
              <td align="center"><?php isset($inseam) ? print($inseam) : print('&#123;inseam&#125;'); ?>cm</td>
              <td align="center"><?php isset($length) ? print($length) : print('&#123;length&#125;'); ?>cm</td>
              <td align="center" colspan="5">&nbsp;</td>
            </tr>
            <tr>
              <th align="center">特注備考</th>
              <td align="left" colspan="7"><?php isset($tokMemo) ? print($tokMemo) : print('&#123;tokMemo&#125;'); ?></td>
            </tr>
          </table>
          
<?php } ?>
          <form action="koukan_shinsei_kanryo.php" name="confForm" method="post">
            
            <div class="bot"><a href="#" onclick="document.confForm.action='<?php isset($returnUrl) ? print($returnUrl) : print('&#123;returnUrl&#125;'); ?>'; document.confForm.submit(); return false;"><img src="../img/modoru.gif" alt="戻る" width="112" height="32" border="0"></a> &nbsp;&nbsp;&nbsp;&nbsp; <a href="#" onclick="document.confForm.submit(); return false;"><img src="../img/koukan.gif" alt="交換" width="112" height="32" border="0"></a></div>
            
            

            <input type="hidden" value="1" name="koukanShinseiFlg">
<?php for ($i1_hiddenHtml=0; $i1_hiddenHtml<count($hiddenHtml); $i1_hiddenHtml++) { ?>
        <input type="hidden" value="<?php isset($hiddenHtml[$i1_hiddenHtml]['value']) ? print($hiddenHtml[$i1_hiddenHtml]['value']) : print('&#123;hiddenHtml.value&#125;'); ?>" name="<?php isset($hiddenHtml[$i1_hiddenHtml]['name']) ? print($hiddenHtml[$i1_hiddenHtml]['name']) : print('&#123;hiddenHtml.name&#125;'); ?>">
<?php } ?>
          </form>
          

        </div>
        <br><br><br>
      </div>
    </div>
  </body>
</html>

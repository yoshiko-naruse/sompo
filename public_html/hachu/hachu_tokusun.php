<?php
/*
 * 特寸入力画面
 * hachu_tokusun.src.php
 *
 * create 2007/03/30 H.Osugi
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



// 初期設定
$isMenuOrder = true;	// 発注のメニューをアクティブに

// 変数の初期化 ここから *******************************************************
$high     = '';					// 身長
$weight   = '';					// 体重
$bust     = '';					// バスト
$waist    = '';					// ウエスト
$hips     = '';					// ヒップ
$shoulder = '';					// 肩幅
$sleeve   = '';					// 袖丈
$length   = '';					// スカート丈
$kitake   = '';                 // 着丈
$yukitake = '';                 // 裄丈
$inseam   = '';                 // 股下
$tokMemo  = '';					// 特寸備考

$nextUrl   = '';				// 遷移先URL
$returnUrl = '';				// 戻り先URL
// 変数の初期化 ここまで ******************************************************

$post = $_POST;

// 遷移先URLと戻り先URLの設定
switch($post['hachuShinseiFlg']) {
	case true:
		$nextUrl   = './hachu_shinsei_kakunin.php';
		$returnUrl = './hachu_shinsei.php';
		break;

	default:
		$hidden = array();
		redirectPost('./hachu_top.php', $hidden);
		break;
}

// 申請履歴から遷移してきた場合（発注変更）
if (isset($post['rirekiFlg']) && $post['rirekiFlg'] == 1
	&& (!isset($post['tokFlg']) || $post['tokFlg'] != 1)) {

	$post = getTokData($dbConnect, $post);

}

// POST値をHTMLエンティティ
$post = castHtmlEntity($post); 

// 表示する値の成型 ここから +++++++++++++++++++++++++++++++++++++++++++++++++++
// 身長
$high = trim($post['high']);

// 体重
$weight = trim($post['weight']);

// バスト
$bust = trim($post['bust']);

// ウエスト
$waist = trim($post['waist']);

// ヒップ
$hips = trim($post['hips']);

// 肩幅
$shoulder = trim($post['shoulder']);

// 袖丈
$sleeve = trim($post['sleeve']);

// スカート丈
$length = trim($post['length']);

// 着丈
$kitake = trim($post['kitake']);

// 裄丈
$yukitake = trim($post['yukitake']);

// 股下
$inseam = trim($post['inseam']);

// 特寸備考
$tokMemo = trim($post['tokMemo']);

if (isset($post['rirekiFlg']) && $post['rirekiFlg'] == 1) {

	$isMenuOrder   = false;
	$isMenuHistory = true;	// 申請履歴のメニューをアクティブに
	$haveRirekiFlg = true;	// 発注申請か発注変更かを判定するフラグ

}

// hidden値の成型
// 状態
$countSearchStatus = count($post['searchStatus']);
for ($i=0; $i<$countSearchStatus; $i++) {
	$post['searchStatus[' . $i . ']'] = $post['searchStatus'][$i];
}
$countItemIds = count($post['itemIds']);
for ($i=0; $i<$countItemIds; $i++) {
	$post['itemIds[' . $i . ']'] = $post['itemIds'][$i];
	if ($post['sizeType'][$post['itemIds'][$i]] != 3) {
		$post['size[' . $post['itemIds'][$i] . ']'] = trim($post['size'][$post['itemIds'][$i]]);
	}
	$post['sizeType[' . $post['itemIds'][$i] . ']'] = trim($post['sizeType'][$post['itemIds'][$i]]);
	$post['itemNumber[' . $post['itemIds'][$i] . ']'] = trim($post['itemNumber'][$post['itemIds'][$i]]);

    $post['groupId[' . $post['itemIds'][$i] . ']'] = trim($post['groupId'][$post['itemIds'][$i]]);
    if (isset($post['limitNum'][$post['itemIds'][$i]])) { 
        $post['limitNum[' . $post['itemIds'][$i] . ']'] = trim($post['limitNum'][$post['itemIds'][$i]]);
    }
}
$notAllows = array('high', 'weight', 'bust', 'waist', 'hips', 'shoulder', 'sleeve', 'kitake', 'yukitake', 'inseam', 'length', 'tokMemo', 'tokFlg', 'errorId', 'searchStatus', 'itemIds', 'size', 'sizeType', 'itemNumber', 'groupId', 'limitNum');
$hiddenHtml = castHidden($post, $notAllows);
// 表示する値の成型 ここまで ++++++++++++++++++++++++++++++++++++++++++++++++++

/*
 * 変更する特寸情報を取得する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$post      => POST値
 * 戻り値：$result    => 変更する特寸情報
 *
 * create 2007/03/30 H.Osugi
 *
 */
function getTokData($dbConnect, $post) {

	// 初期化
	$returnDatas = $post;

	// 申請番号
	$requestNo = trim($post['requestNo']);

	// 変更する発注申請情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" tt.Height,";
	$sql .= 	" tt.Weight,";
	$sql .= 	" tt.Bust,";
	$sql .= 	" tt.Waist,";
	$sql .= 	" tt.Hips,";
	$sql .= 	" tt.Shoulder,";
	$sql .= 	" tt.Sleeve,";
	$sql .= 	" tt.Length,";
    $sql .=     " tt.Kitake,";
    $sql .=     " tt.Yukitake,";
    $sql .=     " tt.Inseam,";
	$sql .= 	" tor.TokNote";
	$sql .= " FROM";
	$sql .= 	" T_Tok tt";
	$sql .= " INNER JOIN";
	$sql .= 	" T_Order tor";
	$sql .= " ON";
	$sql .= 	" tt.OrderID = tor.OrderID";
	$sql .= " AND";
	$sql .= 	" tor.Del = " . DELETE_OFF;
	$sql .= " WHERE";
	$sql .= 	" tor.AppliNo = '" . db_Escape($requestNo) . "'";
	$sql .= " AND";
	$sql .= 	" tt.Del = " . DELETE_OFF;

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
	 	return $returnDatas;
	}

	$returnDatas['high']     = $result[0]['Height'];		// 身長
	$returnDatas['weight']   = $result[0]['Weight'];		// 体重
	$returnDatas['bust']     = $result[0]['Bust'];			// バスト
	$returnDatas['waist']    = $result[0]['Waist'];			// ウエスト
	$returnDatas['hips']     = $result[0]['Hips'];			// ヒップ
	$returnDatas['shoulder'] = $result[0]['Shoulder'];		// 肩幅
	$returnDatas['sleeve']   = $result[0]['Sleeve'];		// 袖丈
	$returnDatas['length']   = $result[0]['Length'];		// スカート丈
    $returnDatas['kitake']   = $result[0]['Kitake'];        // 着丈
    $returnDatas['yukitake']   = $result[0]['Yukitake'];      // 裄丈
    $returnDatas['inseam']   = $result[0]['Inseam'];        // 股下
	$returnDatas['tokMemo']  = $result[0]['TokNote'];		// 特寸備考

 	return $returnDatas;

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
<?php if(!$haveRirekiFlg) { ?>
          <h1>特注サイズ入力</h1>
<?php } ?>
<?php if($haveRirekiFlg) { ?>
          
          <h1>特注サイズ入力　（<span style="color:red">訂正</span>）</h1>
          
<?php } ?>
          <div><b>ヌード寸法の測り方</b></div><br>
          <table border="0" width="700" cellpadding="0" cellspacing="0" class="tb_1">
            <tr>
              <td width="100" class="line">バスト</td>
              <td width="600" class="line">両脇下部の周囲を、胸の一番高いところを通って水平に測る。</td>
            </tr>
            <tr>
              <td class="line">ウエスト</td>
              <td class="line">ウエスト帯部の周囲。（胴部の最も細い部分の水平周囲）</td>
            </tr>
            <tr>
              <td class="line">ヒップ</td>
              <td class="line">腰部の最も大きい部分の水平周囲。</td>
            </tr>
            <tr>
              <td class="line">肩幅</td>
              <td class="line">後ろ衿ぐりの中央を通って、左右の肩先から肩先までを測る。</td>
            </tr>
            <tr>
              <td class="line">袖丈</td>
              <td class="line">肩先から袖口端まで測る</td>
            </tr>
            <tr>
              <td class="line">着丈</td>
              <td class="line">後ろ衿ぐりの中央から後身頃の裾までを垂直に測る。</td>
            </tr>
            <tr>
              <td class="line">裄丈</td>
              <td class="line">後ろ衿ぐりの中央から肩先を通って袖口の端までを測る。</td>
            </tr>
            <tr>
              <td class="line">股下</td>
              <td class="line">股の中心からくるぶしまで。</td>
            </tr>
            <tr>
              <td class="line">首周り</td>
              <td class="line">シャツのネック箇所（一周）を測る。</td>
            </tr>
          </table>
          <br>
          <br>
          <h3>◆ヌード寸法を入力して下さい。</h3>
          <form action="<?php isset($nextUrl) ? print($nextUrl) : print('&#123;nextUrl&#125;'); ?>" name="confForm" method="post">
            <table width="500" border="0" class="tb_2" cellpadding="0" cellspacing="3">
              <tr>
                <th align="center" width="100">身長</th>
                <th align="center" width="100">体重</th>
                <th align="center" width="100">バスト</th>
                <th align="center" width="100">ウエスト</th>
                <th align="center" width="100">ヒップ</th>
              </tr>
              <tr height="25" valign="middle">
                <td align="center"><input name="high" type="text" value="<?php isset($high) ? print($high) : print('&#123;high&#125;'); ?>" size="6">cm</td>
                <td align="center"><input name="weight" type="text" value="<?php isset($weight) ? print($weight) : print('&#123;weight&#125;'); ?>" size="6">kg</td>
                <td align="center"><input name="bust" type="text" value="<?php isset($bust) ? print($bust) : print('&#123;bust&#125;'); ?>" size="6">cm</td>
                <td align="center"><input name="waist" type="text" value="<?php isset($waist) ? print($waist) : print('&#123;waist&#125;'); ?>" size="6">cm</td>
                <td align="center"><input name="hips" type="text" value="<?php isset($hips) ? print($hips) : print('&#123;hips&#125;'); ?>" size="6">cm</td>
              </tr>
              <tr>
                <th align="center" width="100">肩幅</th>
                <th align="center" width="100">袖丈</th>
                <th align="center" width="100">着丈</th>
                <th align="center" width="100">裄丈</th>
                <th align="center" width="100">股下</th>
              </tr>
              <tr height="25" valign="middle">
                <td align="center"><input name="shoulder" type="text" value="<?php isset($shoulder) ? print($shoulder) : print('&#123;shoulder&#125;'); ?>" size="6">cm</td>
                <td align="center"><input name="sleeve" type="text" value="<?php isset($sleeve) ? print($sleeve) : print('&#123;sleeve&#125;'); ?>" size="6">cm</td>
                <td align="center"><input name="kitake" type="text" value="<?php isset($kitake) ? print($kitake) : print('&#123;kitake&#125;'); ?>" size="6">cm</td>
                <td align="center"><input name="yukitake" type="text" value="<?php isset($yukitake) ? print($yukitake) : print('&#123;yukitake&#125;'); ?>" size="6">cm</td>
                <td align="center"><input name="inseam" type="text" value="<?php isset($inseam) ? print($inseam) : print('&#123;inseam&#125;'); ?>" size="6">cm</td>
              </tr>
              <tr>
                <th align="center" width="100">首周り</th>
                <th align="center" width="100">&nbsp;</th>
                <th align="center" width="100">&nbsp;</th>
                <th align="center" width="100">&nbsp;</th>
                <th align="center" width="100">&nbsp;</th>
              </tr>
              <tr height="25" valign="middle">
                <td align="center"><input name="length" type="text" value="<?php isset($length) ? print($length) : print('&#123;length&#125;'); ?>" size="6">cm</td>
                <td align="center">&nbsp;</td>
                <td align="center">&nbsp;</td>
                <td align="center">&nbsp;</td>
                <td align="center">&nbsp;</td>
              </tr>
            </table>
            <br>
            <table width="500" border="0" class="tb_2" cellpadding="0" cellspacing="3">
              <tr>
                <th align="center">特注備考</th>
              </tr>
              <tr height="25" valign="middle">
                <td align="center">
                  <input name="tokMemo" type="text" value="<?php isset($tokMemo) ? print($tokMemo) : print('&#123;tokMemo&#125;'); ?>" size="90"><br>
                </td>
              </tr>
            </table>
            <table width="680" border="0" cellpadding="0" cellspacing="3">
              <tr height="40">
                <td align="left" valign="bottom">
                  <span style="color:red">
                  6L・7Lサイズは数枚ご用意がありますが、それ以外は特注となり60日程度の納期を頂きます。<br>
                  6L・7Lをご希望の場合は備考欄にご記入下さい。
                  </span>
                </td>
              </tr>
            </table>
            
            <div class="bot" align="center"><a href="#" onclick="document.confForm.action='<?php isset($returnUrl) ? print($returnUrl) : print('&#123;returnUrl&#125;'); ?>'; document.confForm.submit(); return false;"><img src="../img/modoru.gif" alt="戻る" width="112" height="32" border="0"></a> &nbsp;&nbsp;&nbsp;&nbsp; <a href="#" onclick="document.confForm.tokFlg.value='1'; document.confForm.submit(); return false;"><img src="../img/tsugihe.gif" alt="次へ" width="112" height="32" border="0"></a></div>
            
<?php if($haveRirekiFlg) { ?>
            <input type="hidden" name="rirekiFlg" value="1">
<?php } ?>
            <input type="hidden" name="tokFlg">
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

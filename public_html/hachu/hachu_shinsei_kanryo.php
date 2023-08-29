<?php
/*
 * 発注申請発注結果画面
 * hachu_shinsei_kanryo.src.php
 *
 * create 2007/03/16 H.Osugi
 * update 2007/03/26 H.Osugi    発注変更処理を追加
 * update 2007/03/30 H.Osugi    特寸処理を追加
 * update 2008/04/16 W.Takasaki 初回発注と個別発注を統合
 *
 */

// 各種モジュールの取得
include_once('../../include/define.php');               // 定数定義
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


/* ../../include/checkDuplicateStaff.php start */

/*
 * 申請発注重複判定
 * checkDuplicateStaff.php
 *
 * create 2016/11/30 H.Osugi
 *
 */

/*
 * 申請発注重複判定モジュール（同じスタッフコードですでに発注していないか判定）
 * 引数  ：$dbConnect  => コネクションハンドラ
 *       ：$staffID  => スタッフID
 *       ：$returnUrl  => 戻り先URL
 *       ：$hiddenHtml => 遷移時に送信したいPOST値(array)
 * 戻り値：なし
 */
function checkDuplicateStaffID($dbConnect, $staffID, $returnUrl, $hiddenHtml = '', $appliReason = '') {

	// 指定スタッフIDが貸与中か確認する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" COUNT(*) as count_order";
	$sql .= " FROM";
	$sql .= 	" T_Staff_Details tsd";
	$sql .= " INNER JOIN";
	$sql .= 	" T_Staff tsf";
	$sql .= " ON tsd.StaffID = tsf.StaffID AND tsf.Del = " . DELETE_OFF;
	$sql .= " INNER JOIN";
	$sql .= 	" T_Order_Details tod";
	$sql .= " ON tsd.OrderDetID = tod.OrderDetID AND tod.Del = " . DELETE_OFF . " AND tod.Status <> " . STATUS_CANCEL . " AND tod.Status <> " . STATUS_APPLI_DENY;
	$sql .= " INNER JOIN";
	$sql .= 	" T_Order tor";
	$sql .= " ON tod.OrderID = tor.OrderID AND tor.Del = " . DELETE_OFF . " AND tor.Status <> " . STATUS_CANCEL . " AND tod.Status <> " . STATUS_APPLI_DENY;
	if ($appliReason == APPLI_REASON_ORDER_GRADEUP) {	// グレードアップタイ
		$sql .= " AND";
		$sql .= 	" tor.AppliReason = '" . APPLI_REASON_ORDER_GRADEUP . "'";
	} else {											// 基本パターン または 新入社員
		$sql .= " AND";
		$sql .= 	" (tor.AppliReason = '" .APPLI_REASON_ORDER_BASE . "' OR tor.AppliReason = '" . APPLI_REASON_ORDER_FRESHMAN . "')" ;
	}
	$sql .= " WHERE";
	$sql .= 	" tsd.StaffID = '" . db_Escape($staffID) . "'";
	$sql .= " AND";
	$sql .= 	" tsd.ReturnDetID IS NULL";
	$sql .= " AND";
	$sql .= 	" tsd.Del = " . DELETE_OFF;
	$sql .= " AND";
	$sql .= 	" tsd.Status <> " . STATUS_CANCEL;
	$result = db_Read($dbConnect, $sql);

	// 該当の情報がすでに存在する場合
	if (isset($result[0]['count_order']) && $result[0]['count_order'] > 0) {

		// エラー画面で必要な値のセット
		$hiddens = array();
		$hiddens['errorName'] = 'checkStaffID';
		$hiddens['menuName']  = 'isMenuOrder';
		$hiddens['returnUrl'] = $returnUrl;
		$hiddens['errorId'][] = '901';

		if (is_array($hiddenHtml)) {
			$hiddens = array_merge($hiddens, $hiddenHtml);
		}

		redirectPost(HOME_URL . 'error.php', $hiddens);

	}
}

/*
 * スタッフコード重複登録判定モジュール
 * 引数  ：$dbConnect  => コネクションハンドラ
 *       ：$corpCd     => 会社コード
 *       ：$compId     => 部署ID
 *       ：$staffCode  => スタッフコード
 *       ：$staffKbn   => 新規更新区分(1:新規 2:更新)
 *       ：$requestNo  => 申請番号
 * 戻り値：True: 重複なし、False: 重複あり
 */
function checkDuplicateCorpStaff($dbConnect, $staffCode, $staffKbn, $requestNo = '') {

	// 同一会社内で異なる部署に既に登録されていないかを確認する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" tsf.StaffID";
	$sql .= " FROM";
	$sql .= 	" T_Staff tsf";

	$sql .= " INNER JOIN";
	$sql .= 	" M_Comp mcp";
	$sql .= " ON";
	$sql .= 	" tsf.CompID = mcp.CompID";
	$sql .= " AND";
	$sql .= 	" mcp.Del = ".DELETE_OFF;

	$sql .= " INNER JOIN";
	$sql .= 	" T_Order tor";
	$sql .= " ON";
	$sql .= 	" tsf.StaffID = tor.StaffID";
	$sql .= " AND";
	$sql .= 	" tor.Del = ".DELETE_OFF;
	$sql .= " AND";
	$sql .= 	" tor.Status <> ".STATUS_CANCEL;

	$sql .= " INNER JOIN";
	$sql .= 	" T_Order_Details tod";
	$sql .= " ON";
	$sql .= 	" tor.OrderID = tod.OrderID";
	$sql .= " AND";
	$sql .= 	" tod.Del = ".DELETE_OFF;
	$sql .= " AND";
	$sql .= 	" tod.Status <> ".STATUS_CANCEL;

	$sql .= " WHERE";
	$sql .= 	" tsf.StaffCode = '" . db_Escape($staffCode) . "'";
	// 新規時は１件でもあればＮＧ
	// 更新時は他部署にある場合はＮＧ
	if ($staffKbn == '2') {
		$sql .= " AND";
		$sql .= 	" tsf.CompID <> '" . db_Escape($compId) . "'";
	}
	// 申請変更画面からのチェックの場合、同一申請番号以外をチェック対象とする
	if ($requestNo != '') {
		$sql .= " AND";
		$sql .= 	" tor.AppliNo <> '" . db_Escape($requestNo) . "'";
	}
//	$sql .= " AND";
//	$sql .= 	" mcp.CorpCd = '" . db_Escape($corpCd) . "'";
	$sql .= " AND";
	$sql .= 	" tsf.Del = " . DELETE_OFF;

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		return true;		// 重複なし
	} else {
		// 重複があっても全返却済の場合は更新貸与を許可する（再雇用）
		if ($staffKbn == '2') {
			// 指定スタッフコードで現在も保持アイテムがある場合はＮＧ
			if (checkStaffKeepItem($dbConnect, $staffCode)) {
				return true;		// 保持アイテムなし
			} else {
				return false;		// 保持アイテムあり
			}
		} else {
			return false;		// 重複あり
		}
	}

}

/*
 * スタッフコード保持アイテムチェック
 * 引数  ：$dbConnect  => コネクションハンドラ
 *       ：$staffCode  => スタッフコード
 * 戻り値：True: 保持アイテムなし、False: 保持アイテムあり
 */
function checkStaffKeepItem($dbConnect, $staffCode) {

	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" tsd.StaffDetID";
	$sql .= " FROM";
	$sql .= 	" T_Staff tsf";

	$sql .= " INNER JOIN";
	$sql .= 	" T_Staff_Details tsd";
	$sql .= " ON";
	$sql .= 	" tsf.StaffID = tsd.StaffID";
	$sql .= " AND";
	$sql .= 	" tsd.Status < ".STATUS_RETURN;		// 返却済未満
	$sql .= " AND";
	$sql .= 	" tsd.Del = ".DELETE_OFF;

	$sql .= " WHERE";
    $sql .=     " tsf.StaffCode = '" . db_Escape($staffCode) . "'";
	$sql .= " AND";
	$sql .= 	" tsf.Del = ".DELETE_OFF;

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		return true;		// 保持アイテムなし
	} else {
		return false;		// 保持アイテムあり
	}

}


/* ../../include/checkDuplicateStaff.php end */


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


/* ../../include/createHachuMail.php start */

/*
 * 発注申請メール生成モジュール
 * createHachuMail.php
 *
 * create 2007/03/30 H.Osugi
 *
 */

/*
 * 発注申請メールの件名と本文を作成する
 *
 * 引数  ：$dbConnect  => コネクションハンドラ
 *       ：$orderId    => OrderID
 *       ：$tolFlg     => 特寸フラグ 1なら特寸
 *       ：$filePath   => メールテンプレートのファイルパス
 *       ：$subject    => 件名
 *       ：$message    => 本文
 *
 * 戻り値：true：メールテンプレート取得成功/false：取得失敗
 *
 * create 2007/03/30 H.Osugi
 *
 */
function hachuShinseiMail($dbConnect, $orderId, $tokFlg, $filePath, &$subject, &$message) {

	// 初期化
	$subject = '';
	$message = '';

	// T_Orderの情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" AppliNo,";
	$sql .= 	" AppliDay,";
	$sql .= 	" AppliCompCd,";
	$sql .= 	" AppliCompName,";
	$sql .= 	" StaffCode,";
    $sql .=     " PersonName,";
	$sql .= 	" Note,";
	$sql .= 	" TokNote";
	$sql .= " FROM";
	$sql .= 	" T_Order";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;
	
	$orderData = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($orderData) <= 0) {
	 	return false;
	}

	// 件名
	$subject = MAIL_SUBJECT_HEADER . "(" . $orderData[0]['AppliNo'] . ")発注申請";
	if ($tokFlg == 1) {
		$subject .= " （特注）";
	}

	// T_Order_Detailsの情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" ItemName,";
	$sql .= 	" Size";
	$sql .= " FROM";
	$sql .= 	" T_Order_Details";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;
	$sql .= " ORDER BY";
	$sql .= 	" OrderDetID ASC";
	
	$orderDetailData = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($orderDetailData) <= 0) {
	 	return false;
	}

	$items = '';
	$countOrderDetail = count($orderDetailData);
	for ($i=0; $i<$countOrderDetail; $i++) {
		$items .= "　○発注：　". $orderDetailData[$i]['ItemName'] . "（" . $orderDetailData[$i]['Size'] . "）";
		if ($i < $countOrderDetail - 1) {
			$items .= "\n";
		}
	}

	$tokusun = '';
	if ($tokFlg == 1) {

		// T_Tokの情報を取得する
		$sql  = "";
		$sql .= " SELECT";
		$sql .= 	" Height,";
		$sql .= 	" Weight,";
		$sql .= 	" Bust,";
		$sql .= 	" Waist,";
		$sql .= 	" Hips,";
		$sql .= 	" Shoulder,";
		$sql .= 	" Sleeve,";
		$sql .= 	" Length,";
        $sql .=     " Kitake,";
        $sql .=     " Yukitake,";
        $sql .=     " Inseam";
		$sql .= " FROM";
		$sql .= 	" T_Tok";
		$sql .= " WHERE";
		$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
		$sql .= " AND";
		$sql .= 	" Del = " . DELETE_OFF;
		
		$tokData = db_Read($dbConnect, $sql);
	
		// 検索結果が0件の場合
		if (count($tokData) <= 0) {
		 	return false;
		}

		// 特寸部分のテンプレート
		$tokusun = file_get_contents($filePath . 'tokusunTemplate.txt');

		// 特寸部分のテンプレートの置換
		$tokusun = mb_ereg_replace('###HEIGHT###', $tokData[0]['Height'], $tokusun);
		$tokusun = mb_ereg_replace('###WEIGHT###', $tokData[0]['Weight'], $tokusun);
		$tokusun = mb_ereg_replace('###BUST###', $tokData[0]['Bust'], $tokusun);
		$tokusun = mb_ereg_replace('###WAIST###', $tokData[0]['Waist'], $tokusun);
		$tokusun = mb_ereg_replace('###HIPS###', $tokData[0]['Hips'], $tokusun);
		$tokusun = mb_ereg_replace('###SHOULDER###', $tokData[0]['Shoulder'], $tokusun);
		$tokusun = mb_ereg_replace('###SLEEVE###', $tokData[0]['Sleeve'], $tokusun);
		$tokusun = mb_ereg_replace('###SETALE###', $tokData[0]['Length'], $tokusun);
        $tokusun = mb_ereg_replace('###KITAKE###', $tokData[0]['Kitake'], $tokusun);
        $tokusun = mb_ereg_replace('###YUKITAKE###', $tokData[0]['Yukitake'], $tokusun);
        $tokusun = mb_ereg_replace('###INSEAM###', $tokData[0]['Inseam'], $tokusun);
		$tokusun = mb_ereg_replace('###TOKNOTE###', $orderData[0]['TokNote'], $tokusun);

	}

	// 現在日時
	$date = date("Y年n月j日 H時i分");

	// 申請日
	$appliDay = date("Y/m/d", strtotime($orderData[0]['AppliDay']));

	// 本文
	$message = file_get_contents($filePath . 'hachuShinsei.txt');

	// 本文の置換
	$message = mb_ereg_replace('###HEAD###', MAIL_BODY_HEADER, $message);
	$message = mb_ereg_replace('###DATE###', $date, $message);

	if ($tokFlg == 1) {
		$message = mb_ereg_replace('###TOK###', "(特注)", $message);
	}
	else {
		$message = mb_ereg_replace('###TOK###', "", $message);
	}

	$message = mb_ereg_replace('###APPLIDAY###', $appliDay, $message);
	$message = mb_ereg_replace('###APPLINO###', $orderData[0]['AppliNo'], $message);
	$message = mb_ereg_replace('###COMPCODE###', $orderData[0]['AppliCompCd'], $message);
	$message = mb_ereg_replace('###COMPNAME###', $orderData[0]['AppliCompName'], $message);
	$message = mb_ereg_replace('###STAFFCODE###', $orderData[0]['StaffCode'], $message);
    $message = mb_ereg_replace('###PERSONNAME###', $orderData[0]['PersonName'], $message);
	$message = mb_ereg_replace('###NOTE###', $orderData[0]['Note'], $message);
	$message = mb_ereg_replace('###ITEM###', $items, $message);
	$message = mb_ereg_replace('###TOKUSUN###', $tokusun, $message);

	return true;

}

/*
 * 発注訂正メールの件名と本文を作成する
 *
 * 引数  ：$dbConnect  => コネクションハンドラ
 *       ：$orderId    => OrderID
 *       ：$tolFlg     => 特寸フラグ 1なら特寸
 *       ：$filePath   => メールテンプレートのファイルパス
 *       ：$subject    => 件名
 *       ：$message    => 本文
 *
 * 戻り値：true：メールテンプレート取得成功/false：取得失敗
 *
 * create 2007/04/02 H.Osugi
 *
 */
function hachuTeiseiMail($dbConnect, $orderId, $tokFlg, $motoTokFlg, $filePath, &$subject, &$message) {

	// 初期化
	$subject = '';
	$message = '';

	// T_Orderの情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" AppliNo,";
	$sql .= 	" AppliDay,";
	$sql .= 	" AppliCompCd,";
	$sql .= 	" AppliCompName,";
	$sql .= 	" StaffCode,";
    $sql .=     " PersonName,";
	$sql .= 	" Note,";
	$sql .= 	" TokNote";
	$sql .= " FROM";
	$sql .= 	" T_Order";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;
	
	$orderData = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($orderData) <= 0) {
	 	return false;
	}

	// 件名
	$subject = MAIL_SUBJECT_HEADER . "(" . $orderData[0]['AppliNo'] . ")発注訂正";
	if ($tokFlg == 1 || $motoTokFlg == 1) {
		$subject .= " （特注）";
	}

	// T_Order_Detailsの情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" ItemName,";
	$sql .= 	" Size";
	$sql .= " FROM";
	$sql .= 	" T_Order_Details";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;
	$sql .= " ORDER BY";
	$sql .= 	" OrderDetID ASC";
	
	$orderDetailData = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($orderDetailData) <= 0) {
	 	return false;
	}

	$items = '';
	$countOrderDetail = count($orderDetailData);
	for ($i=0; $i<$countOrderDetail; $i++) {
		$items .= "  ○発注：  ". $orderDetailData[$i]['ItemName'] . "（" . $orderDetailData[$i]['Size'] . "）";
		if ($i < $countOrderDetail - 1) {
			$items .= "\n";
		}
	}

	$tokusun = '';
	if ($tokFlg == 1) {

		// T_Tokの情報を取得する
		$sql  = "";
		$sql .= " SELECT";
		$sql .= 	" Height,";
		$sql .= 	" Weight,";
		$sql .= 	" Bust,";
		$sql .= 	" Waist,";
		$sql .= 	" Hips,";
		$sql .= 	" Shoulder,";
		$sql .= 	" Sleeve,";
		$sql .= 	" Length,";
        $sql .=     " Kitake,";
        $sql .=     " Yukitake,";
        $sql .=     " Inseam";
		$sql .= " FROM";
		$sql .= 	" T_Tok";
		$sql .= " WHERE";
		$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
		$sql .= " AND";
		$sql .= 	" Del = " . DELETE_OFF;
		
		$tokData = db_Read($dbConnect, $sql);
	
		// 検索結果が0件の場合
		if (count($tokData) <= 0) {
		 	return false;
		}

		// 特寸部分のテンプレート
		$tokusun = file_get_contents($filePath . 'tokusunTemplate.txt');

		// 特寸部分のテンプレートの置換
		$tokusun = mb_ereg_replace('###HEIGHT###', $tokData[0]['Height'], $tokusun);
		$tokusun = mb_ereg_replace('###WEIGHT###', $tokData[0]['Weight'], $tokusun);
		$tokusun = mb_ereg_replace('###BUST###', $tokData[0]['Bust'], $tokusun);
		$tokusun = mb_ereg_replace('###WAIST###', $tokData[0]['Waist'], $tokusun);
		$tokusun = mb_ereg_replace('###HIPS###', $tokData[0]['Hips'], $tokusun);
		$tokusun = mb_ereg_replace('###SHOULDER###', $tokData[0]['Shoulder'], $tokusun);
		$tokusun = mb_ereg_replace('###SLEEVE###', $tokData[0]['Sleeve'], $tokusun);
		$tokusun = mb_ereg_replace('###SETALE###', $tokData[0]['Length'], $tokusun);
        $tokusun = mb_ereg_replace('###KITAKE###', $tokData[0]['Kitake'], $tokusun);
        $tokusun = mb_ereg_replace('###YUKITAKE###', $tokData[0]['Yukitake'], $tokusun);
        $tokusun = mb_ereg_replace('###INSEAM###', $tokData[0]['Inseam'], $tokusun);
		$tokusun = mb_ereg_replace('###TOKNOTE###', $orderData[0]['TokNote'], $tokusun);

	}

	// 現在日時
	$date = date("Y年n月j日 H時i分");

	// 申請日
	$appliDay = date("Y/m/d", strtotime($orderData[0]['AppliDay']));

	// 本文
	$message = file_get_contents($filePath . 'hachuTeisei.txt');

	// 本文の置換
	$message = mb_ereg_replace('###HEAD###', MAIL_BODY_HEADER, $message);
	$message = mb_ereg_replace('###DATE###', $date, $message);

	if ($tokFlg == 1 || $motoTokFlg == 1) {
		$message = mb_ereg_replace('###TOK###', "(特注)", $message);
	}
	else {
		$message = mb_ereg_replace('###TOK###', "", $message);
	}

	$message = mb_ereg_replace('###APPLIDAY###', $appliDay, $message);
	$message = mb_ereg_replace('###APPLINO###', $orderData[0]['AppliNo'], $message);
	$message = mb_ereg_replace('###COMPCODE###', $orderData[0]['AppliCompCd'], $message);
	$message = mb_ereg_replace('###COMPNAME###', $orderData[0]['AppliCompName'], $message);
	$message = mb_ereg_replace('###STAFFCODE###', $orderData[0]['StaffCode'], $message);
    $message = mb_ereg_replace('###PERSONNAME###', $orderData[0]['PersonName'], $message);
	$message = mb_ereg_replace('###NOTE###', $orderData[0]['Note'], $message);
	$message = mb_ereg_replace('###ITEM###', $items, $message);
	$message = mb_ereg_replace('###TOKUSUN###', $tokusun, $message);

	return true;

}

/*
 * 発注キャンセルメールの件名と本文を作成する
 *
 * 引数  ：$dbConnect  => コネクションハンドラ
 *       ：$orderId    => OrderID
 *       ：$filePath   => メールテンプレートのファイルパス
 *       ：$subject    => 件名
 *       ：$message    => 本文
 *       ：$tolFlg     => 特寸フラグ 1なら特寸
 *
 * 戻り値：true：メールテンプレート取得成功/false：取得失敗
 *
 * create 2007/04/02 H.Osugi
 *
 */
function hachuCancelMail($dbConnect, $orderId, $filePath, &$subject, &$message, &$tokFlg) {

	// 初期化
	$subject = '';
	$message = '';

	// T_Orderの情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" AppliNo,";
	$sql .= 	" AppliCompCd,";
	$sql .= 	" AppliCompName,";
	$sql .= 	" StaffCode,";
    $sql .=     " PersonName,";
	$sql .= 	" Note,";
	$sql .= 	" Tok";
	$sql .= " FROM";
	$sql .= 	" T_Order";
	$sql .= " WHERE";
	$sql .= 	" OrderId = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_ON;
	
	$orderData = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($orderData) <= 0) {
	 	return false;
	}

	$tokFlg = $orderData[0]['Tok'];

	// 件名
	$subject = MAIL_SUBJECT_HEADER . "(" . $orderData[0]['AppliNo'] . ")発注キャンセル";
	if ($tokFlg == 1) {
		$subject .= " （特注）";
	}

	// 現在日時
	$date = date("Y年n月j日 H時i分");

	// 申請日
	$appliDay = date("Y/m/d", strtotime($orderData[0]['AppliDay']));

	// 本文
	$message = file_get_contents($filePath . 'hachuCancel.txt');

	// 本文の置換
	$message = mb_ereg_replace('###HEAD###', MAIL_BODY_HEADER, $message);
	$message = mb_ereg_replace('###DATE###', $date, $message);

	if ($tokFlg == 1) {
		$message = mb_ereg_replace('###TOK###', "(特注)", $message);
	}
	else {
		$message = mb_ereg_replace('###TOK###', "", $message);
	}

	$message = mb_ereg_replace('###APPLIDAY###', $appliDay, $message);
	$message = mb_ereg_replace('###APPLINO###', $orderData[0]['AppliNo'], $message);
	$message = mb_ereg_replace('###COMPCODE###', $orderData[0]['AppliCompCd'], $message);
	$message = mb_ereg_replace('###COMPNAME###', $orderData[0]['AppliCompName'], $message);
	$message = mb_ereg_replace('###STAFFCODE###', $orderData[0]['StaffCode'], $message);
    $message = mb_ereg_replace('###PERSONNAME###', $orderData[0]['PersonName'], $message);
	$message = mb_ereg_replace('###NOTE###', $orderData[0]['Note'], $message);

	return true;

}


/* ../../include/createHachuMail.php end */


/* ../../include/sendTextMail.php start */

/*
 * テキストメール送信モジュール
 * sendTextMail.php
 *
 * create 2007/03/30 H.Osugi
 *
 */

/*
 * テキストメールを送信する
 *
 * 引数  ：$to         => 送信先メールアドレス
 *       ：$subject    => 件名
 *       ：$message    => 本文
 *       ：$fromAddr   => 送信元メールアドレス
 *       ：$fromName   => 送信元名
 *       ：$bbcAddr    => BCC(カンマ区切りで複数指定可)
 *       ：$encode     => 文字コード
 *       ：$returnAddr => リターンアドレス
 *
 * 戻り値：$result => 送信成功：true / 送信失敗：false
 *
 * create 2007/03/30 H.Osugi
 *
 */
function sendTextMail($to, $subject, $message, $fromAddr, $fromName, $bccAddr, $encode = 'UTF-8', $returnAddr = '') {

	// 無駄な改行が発生しないための処理
	$body = preg_replace('/[\r]/', '', $message);

	mb_language('uni');
	mb_internal_encoding($encode);

	// 半角カナを全角カナに変換
	$subject = mb_convert_kana($subject, 'KV', $encode);
	$body    = mb_convert_kana($body, 'KV', $encode);

	$date = date('r');
	$header = sprintf("Date:%s\n", $date);

	if ($fromName != '') {
		$fromName = mb_convert_kana($fromName, 'KV', $encode);
		$fromName = mb_convert_encoding($fromName, 'JIS', $encode);
		$fromName = "=?iso-2022-jp?B?" . base64_encode($fromName) . "?=";
	}

	//$header .= sprintf("From:%s<%s>\n", $fromName, $fromAddr);
	$header .= sprintf("From:%s\n", $fromAddr);

	if ($bccAddr != '') {
		$bccList = sprintf("BCC:%s\n", $bccAddr);
		$header .= $bccList;
	}

	if ($returnAddr != '') {
		$returnPath = sprintf("Return-Path:%s\n", $returnAddr);
		$header .= $returnPath;
	}

	$result = mb_send_mail($to, $subject, $body, $header);

	return $result;

}


/* ../../include/sendTextMail.php end */


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


/* ./hachu_shinsei.val.php start */

/*
 * エラー判定処理
 * hachu_shinsei.val.php
 *
 * create 2007/03/15 H.Osugi
 *
 *
 */

/*
 * 機能  ：エラー判定を行う
 * 引数  ：$dbConnect  => コネクションハンドラ
 *       ：$post       => POSTデータ
 * 戻り値：なし
 *
 * create 2007/03/15 H.Osugi
 *
 */
function validatePostData($dbConnect, $post) {

    // 初期化
    $hiddens = array();

    // すでに発注申請されていないか判定(初回申請時)
    if (($post['staffId'] != '' || $post['staffId'] === 0) 
        && in_array($post['appliReason'], array(APPLI_REASON_ORDER_COMMON, APPLI_REASON_ORDER_OFFICER, APPLI_REASON_ORDER_ISETAN, APPLI_REASON_ORDER_MATERNITY))) {

        $post['hachuShinseiFlg'] = true;

        $requestNo = '';
        if (isset($post['rirekiFlg']) && $post['rirekiFlg'] == 1) {
            $requestNo = $post['requestNo'];
        }

        $hiddenHtml = castHiddenError($post);

        $returnUrl = 'hachu/hachu_shinsei.php';

        //checkDuplicateStaffCode($dbConnect, $post['staffId'], $returnUrl, $hiddenHtml, $requestNo);
    }

    //---------------------------------------------------------
    // 郵便番号
    //---------------------------------------------------------
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
            $hiddens['errorId'][] = '011';
            break;

        // 半角以外の文字ならば
        case 'mode':
            $isZipError = true;
            $hiddens['errorId'][] = '012';
            break;

        // 指定文字数以外ならば
        case 'max':
        case 'min':
            $isZipError = true;
            $hiddens['errorId'][] = '012';
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

        // エラーが発生したならば、エラーメッセージを取得
        switch ($result) {

            // 空白ならば
            case 'empty':
                $hiddens['errorId'][] = '011';
                break;

            // 半角以外の文字ならば
            case 'mode':
                $hiddens['errorId'][] = '012';
                break;

            // 指定文字数以外ならば
            case 'max':
            case 'min':
                $hiddens['errorId'][] = '012';
                break;

            default:
                break;

        }
    }

    //---------------------------------------------------------
    // 住所
    //---------------------------------------------------------
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
            $hiddens['errorId'][] = '021';
            break;

        // 最大値超過ならば
        case 'max':
            $hiddens['errorId'][] = '022';
            break;

        default:
            break;

    }

    //---------------------------------------------------------
    // 出荷先名
    //---------------------------------------------------------
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
            $hiddens['errorId'][] = '031';
            break;

        // 最大値超過ならば
        case 'max':
            $hiddens['errorId'][] = '032';
            break;

        default:
            break;

    }

    //---------------------------------------------------------
    // 担当者
    //---------------------------------------------------------
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
            $hiddens['errorId'][] = '041';
            break;

        // 最大値超過ならば
        case 'max':
            $hiddens['errorId'][] = '042';
            break;

        default:
            break;

    }

    //---------------------------------------------------------
    // 電話番号
    //---------------------------------------------------------
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
            $hiddens['errorId'][] = '051';
            break;

        // 電話番号に利用可能な文字（数値とハイフン）以外の文字ならば
        case 'mode':
            $hiddens['errorId'][] = '052';
            break;

        // 最大値超過ならば
        case 'max':
            $hiddens['errorId'][] = '053';  
            break;

        default:
            break;

    }

    //---------------------------------------------------------
    // 出荷指定日
    //---------------------------------------------------------
    // 出荷指定日が存在しなければ初期化
    if (!isset($post['yoteiDay'])) {
        $post['yoteiDay'] = '';
    }

    // 出荷指定日の判定
    $result = checkData(trim($post['yoteiDay']), 'Date', true, 10);

    // エラーが発生したならば、エラーメッセージを取得
    switch ($result) {
        // 空白ならば
        case 'empty':
            break;
        // 正しい日付以外ならば
        case 'mode':
            $hiddens['errorId'][] = '111';				// 出荷指定日が正しい日付ではありません。
            break;
        default:
            if (strtotime(date("Y/m/d")) >= strtotime(trim($post['yoteiDay']))) {
              $hiddens['errorId'][] = '112';			// 出荷指定日に発注入力当日と過去日付は指定できません。
            } else {
                $week = date('w', strtotime(trim($post['yoteiDay'])));
                if ($week == 0 || $week == 6) {
                    $hiddens['errorId'][] = '113';		// 出荷指定日に土曜日と日曜日は指定できません。
                }
            }

        break;
    }

	//---------------------------------------------------------
	// メモ
	//---------------------------------------------------------
    // メモが存在しなければ初期化
    if (!isset($post['memo'])) {
        $post['memo'] = '';
    }

    // メモの判定
    $result = checkData(trim($post['memo']), 'Text', false, 128);

    // エラーが発生したならば、エラーメッセージを取得
    switch ($result) {

        // 最大値超過ならば
        case 'max':
            $hiddens['errorId'][] = '061';
            break;

        default:
            break;

    }

//	// Modify by Y.Furukawa at 2020/05/12 個別発注以外はレンタル開始日チェック
//	if ($post['appliReason'] <> APPLI_REASON_ORDER_PERSONAL) { 
//
//    	// レンタル開始日が存在しなければ初期化
//    	if (!isset($post['rentalStartDay'])) {
//    	    $post['rentalStartDay'] = '';
//    	}
//
//    	// レンタル開始日の判定
//    	$minDateTime = mktime(0,0,0,date('m'), date('d'), date('Y'));
//    	$result = checkData(trim($post['rentalStartDay']), 'Date', true, '', date('Y', $minDateTime).'/'.date('m', $minDateTime).'/'.date('d', $minDateTime));
//
//    	// エラーが発生したならば、エラーメッセージを取得
//    	switch ($result) {
//
//    	    case 'empty':
//    	        $hiddens['errorId'][] = '100';
//    	        break;
//
//    	    // 存在しない日付なら
//    	    case 'mode':
//    	        $hiddens['errorId'][] = '101';
//    	        break;
//
//    	    // 今日以前なら
//    	    case 'min':
//    	        $hiddens['errorId'][] = '102';
//    	        break;
//
//    	    default:
//    	        break;
//    	
//    	}
//
//	}

	//追加 uesugi 081119
	$isItemIdError = false;

	for($i=0;$i<count($post['itemIds']);$i++){
		$post['itemIds'][$i] = (int)$post['itemIds'][$i];
		if($post['itemIds'][$i] <= 0){
			$isItemIdError = true;
		}
	}
    // ユニフォーム選択の判定
    $countItemIds = count($post['itemIds']);

    // １つも選択されていない場合
//    if ($countItemIds <= 0) {
    if ($isItemIdError == true || $countItemIds <= 0) {
        $hiddens['errorId'][] = '071';
    } else {    // 選択されたアイテムがあればサイズをチェック
    
        // サイズのエラー判定フラグ
        $isSizeError = false;
        // サイズの判定
        foreach ($post['itemIds'] as $key => $selectedID) {
            // サイズ項目が存在しなければ初期化
            if (!isset($post['size'.$selectedID])) {
                $post['size'.$selectedID] = '';
            }

            if (isset($post['itemNumber'][$selectedID]) && $post['itemNumber'][$selectedID] != '' && $post['itemNumber'][$selectedID] > 0) {

                // チェックされたアイテムに対して、展開されているサイズを取得
                $sizeDataAry = getSizeByItem($dbConnect, $selectedID, 0);
    
                // 判定
                if ($post['size'.$selectedID] != '' && !is_null($post['size'.$selectedID]) ) {
                    $result = array_key_exists(trim($post['size'.$selectedID]), $sizeDataAry);
    
                    // 選択されていなければ、エラーメッセージを取得
                    if (!$result) {
                        $isSizeError = true;
                    }
                } else {
                    $isSizeError = true;
                }
            }
        }

        if ($isSizeError) {
            $hiddens['errorId'][] = '081';
        }

    }

    // 初回申請時のグループをチェック
    $groupIdAry = array();
    $isSetGroup = false;
    for ($i=0; $i<$countItemIds;$i++) {     // チェックされたアイテムをループ
        if (isset($post['groupId'][$post['itemIds'][$i]]) && $post['groupId'][$post['itemIds'][$i]] != 0) {
            // 初期化
            if (!isset($groupIdAry[$post['groupId'][$post['itemIds'][$i]]])) {
                $groupIdAry[$post['groupId'][$post['itemIds'][$i]]] = 0;
            }
            // 同グループIDのアイテム個数を集計
            if (!isset($post['itemNumber'][$post['itemIds'][$i]]) || $post['itemNumber'][$post['itemIds'][$i]] == '') {
                $post['itemNumber'][$post['itemIds'][$i]] = 0;      
            } 
            $groupIdAry[$post['groupId'][$post['itemIds'][$i]]] = (int)$groupIdAry[$post['groupId'][$post['itemIds'][$i]]] + (int)trim($post['itemNumber'][$post['itemIds'][$i]]);

            $isSetGroup = true;
        }
    } 

    // 数量
    for ($i=0; $i<$countItemIds;$i++) {     // チェックされたアイテムをループ


        $result = checkData((string)$post['itemNumber'][$post['itemIds'][$i]], 'Digit', true, 2);

        switch ($result) {
    
            // 空白ならば
            case 'empty':
                // グループ設定されたアイテムの場合は空白を許可
                if ($post['groupId'][$post['itemIds'][$i]] == 0) {
                    $hiddens['errorId'][] = '091';
                    $isSizeError = true;
                }
                break;
                
            // 半角以外の文字ならば
            case 'mode':
                $hiddens['errorId'][] = '093';
                $isSizeError = true;
                break;
    
            // 最大値超過ならば
            case 'max':
                $hiddens['errorId'][] = '093';
                $isSizeError = true;
                break;
    
            default:
                // グループ設定されたアイテムの場合は０以下を許可
                if (trim($post['itemNumber'][$post['itemIds'][$i]]) <= 0 && $post['groupId'][$post['itemIds'][$i]] == 0) {
                    $hiddens['errorId'][] = '094';
                    $isSizeError = true;
                }
                break;
    
        }

        if ($isSizeError == true) {
            break;
        }
    }


    // エラーが存在したならば、エラー画面に遷移
    if (count($hiddens['errorId']) > 0) {
        $hiddens['errorName']    = 'hachuShinsei';
        $hiddens['menuName']     = 'isMenuOrder';
        $hiddens['appliReason']  = $post['appliReason'];

        if (isset($post['rirekiFlg']) && $post['rirekiFlg'] == 1) {
            $hiddens['menuName']  = 'isMenuHistory';
        }

        $hiddens['returnUrl'] = 'hachu/hachu_shinsei.php';
        $errorUrl             = HOME_URL . 'error.php';

        $post['hachuShinseiFlg'] = true;

        // POST値をHTMLエンティティ
        $post = castHtmlEntity($post); 

        $hiddenHtml = castHiddenError($post);

        $hiddens = array_merge($hiddens, $hiddenHtml);

        redirectPost($errorUrl, $hiddens);

    }

}


/* ./hachu_shinsei.val.php end */



//var_dump( $_POST);die;

// 初期設定
$isMenuOrder = true;    // 発注のメニューをアクティブに

// 変数の初期化 ここから ******************************************************
$requestNo = '';                    // 申請番号
$staffCode = '';					// スタッフコード
$zip1      = '';					// 郵便番号（前半3桁）
$zip2      = '';					// 郵便番号（後半4桁）
$address   = '';					// 住所
$shipName  = '';					// 出荷先名
$staffName = '';					// ご担当者
$tel       = '';					// 電話番号
$yoteiDay  = '';					// 出荷指定日
$memo      = '';					// メモ

$isEmptyMemo  = true;				// メモが空かどうかを判定するフラグ

$dispRentalStartDay = false;        // レンタル終了日表示設定

$haveTok      = false;				// 特寸から遷移してきたか判定フラグ

$high     = '';						// 身長
$weight   = '';						// 体重
$bust     = '';						// バスト
$waist    = '';						// ウエスト
$hips     = '';						// ヒップ
$shoulder = '';						// 肩幅
$sleeve   = '';						// 袖丈
$length   = '';						// スカート丈
$kitake   = '';                     // 着丈
$yukitake = '';                     // 裄丈
$inseam   = '';                     // 股下
$tokMemo  = '';						// 特寸備考

// 変数の初期化 ここまで ******************************************************

// 発注訂正の場合
if (!isset($_POST['rirekiFlg']) || $_POST['rirekiFlg'] != 1) {

    // スタッフIDが取得できなければエラーに
    if (!isSetValue($_POST['staffId']) || !isSetValue($_POST['appliReason']) || !(int)$_POST['staffId'] || !(int)$_POST['appliReason'] || !(int)$_POST['searchPatternId']) {
        // TOP画面に強制遷移
        redirectTop();
    }

    // 申請番号がすでに登録されていないか判定
    checkDuplicateAppliNo($dbConnect, $_POST['requestNo'], 'hachu/hachu_shinsei.php', 1);
}

// レンタル終了日表示設定
//if ($isLevelAdmin) {
//    $dispRentalStartDay = true;
//}

// エラー判定
validatePostData($dbConnect, $_POST);

if ($_POST['appliReason'] == APPLI_REASON_ORDER_PERSONAL) {  // 個別発注
    $isSyokai = false;  // 画面表示分岐用
    $dispRentalStartDay = false;
} else {                                                    // 初回発注
    $isSyokai = true;  // 画面表示分岐用
    $dispRentalStartDay = true;
}

// スタッフ(着用者）情報を取得
$staffData = getHeaderData($dbConnect, $_POST['staffId']);

// トランザクション開始
db_Transaction_Begin($dbConnect);

$haveRirekiFlg = false;
if (isset($_POST['rirekiFlg']) && $_POST['rirekiFlg'] == 1) {   // 発注訂正

	// 発注訂正情報をDB更新
	$isSuccess = updateOrder($dbConnect, $_POST, $staffData, $dispRentalStartDay);
	
	$orderId = trim($_POST['orderId']);

	$isMenuOrder   = false;
	$isMenuHistory = true;	// 申請履歴のメニューをアクティブに
	$haveRirekiFlg = true;

} else {    // 新規発注
	// 発注申請情報をDBに登録
	$isSuccess = createOrder($dbConnect, $_POST, $staffData, $orderId, $dispRentalStartDay);
}

// 登録が失敗した場合はエラー画面へ遷移
if (!$isSuccess) {

	// ロールバック
	db_Transaction_Rollback($dbConnect);
//die('out');
	$hiddens['errorName'] = 'hachuShinsei';
	$hiddens['menuName']  = 'isMenuOrder';
	$hiddens['returnUrl'] = 'hachu/hachu_top.php';
	$hiddens['errorId'][] = '901';
	$errorUrl             = HOME_URL . 'error.php';

	// エラー画面に強制遷移
	redirectPost($errorUrl, $hiddens);

}

// コミット
db_Transaction_Commit($dbConnect);

// 発注申請メール送信
//$isSuccess = sendMailShinsei($dbConnect, $_POST, $staffData, $orderId);
$isSuccess = sendMailShinsei($dbConnect, $_POST, $orderId);

// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST); 

// 表示する値の成型 ここから +++++++++++++++++++++++++++++++++++++++++++++++++++

if(isSetValue($post['searchPatternId'])) {
    $searchPatternId = $post['searchPatternId'];
    $searchPatternName = getStaffPattern($dbConnect, $searchPatternId);
}

// スタッフID
$staffId = '';
if (isSetValue($post['staffId'])) {
    $staffId = $post['staffId'];
} 

// 新品/中古区分
$new_Item = false;
$newOldKbn = trim($post['newOldKbn']);
if($newOldKbn == 1){
	$new_Item = true;
}

// スタッフコード
$staffCode = '';
if (isSetValue($staffData['StaffCode'])) {
    $staffCode = $staffData['StaffCode'];
} 

// 着用者名コード
$personName = '';
if (isSetValue($staffData['PersonName'])) {
    $personName = $staffData['PersonName'];
} 

// 店舗ID
$compId = '';
if (isSetValue($staffData['CompID'])) {
    $compId = $staffData['CompID'];
} 

// 店舗コード
$compCd = '';
if (isSetValue($staffData['CompCd'])) {
    $compCd = $staffData['CompCd'];
} 

// 店名
$compName = '';
if (isSetValue($staffData['CompName'])) {
    $compName = $staffData['CompName'];
} 

// 申請番号
$requestNo = trim($post['requestNo']);

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

// 出荷指定日
$yoteiDay = trim($post['yoteiDay']);

// メモ
$memo = trim($post['memo']);

if ($memo != '' || $memo === 0) {
    $isEmptyMemo = false;
}

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

	// スカート丈
	$length   = trim($post['length']);

    // 着丈
    $kitake   = trim($post['kitake']);

    // 裄丈
    $yukitake   = trim($post['yukitake']);

    // 股下
    $inseam   = trim($post['inseam']);

	// 特寸備考
	$tokMemo  = trim($post['tokMemo']);

}

// 表示するアイテム情報を取得
$displayData = getDispItem($dbConnect, $post);

$GoukeiKingaku = 0;
for ($i=0; $i<sizeof($displayData); $i++) {
	$GoukeiKingaku += $displayData[$i]['dispPrice'];
	$displayData[$i]['dispPrice'] = number_format($displayData[$i]['dispPrice']);
}
$GoukeiKingaku = number_format($GoukeiKingaku);

// 表示する値の成型 ここまで ++++++++++++++++++++++++++++++++++++++++++++++++++

/*
 * 発注申請情報を登録する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$post      => POST値
 *       ：$staffData => 着用者情報
 *       ：$orderId   => OrderID
 *       ：$dispRentalStartDay => レンタル開始日表示設定
 * 戻り値：true：登録成功 / false：登録失敗
 *
 * create 2007/03/16 H.Osugi
 * update 2008/04/16 W.Takasaki
 *
 */
function createOrder($dbConnect, $post, $staffData, &$orderId, $dispRentalStartDay) {

	// 郵便番号を成型する
	$zip = trim($post['zip1']) . '-' . trim($post['zip2']);

	// T_Orderに登録する
	$sql  = "";
	$sql .= " INSERT INTO";
	$sql .= 	" T_Order";
	$sql .= 		" (";
	$sql .= 		" AppliDay,";
	$sql .= 		" AppliNo,";
	$sql .= 		" AppliUserID,";
	$sql .= 		" AppliCompCd,";
	$sql .= 		" AppliCompName,";
	$sql .= 		" AppliMode,";
	$sql .= 		" AppliReason,";
	$sql .= 		" AppliPattern,";
	$sql .= 		" CompID,";
    $sql .=         " StaffID,";
	$sql .= 		" StaffCode,";
	$sql .= 		" PersonName,";
	$sql .= 		" Zip,";
	$sql .= 		" Adrr,";
	$sql .= 		" Tel,";
	$sql .= 		" ShipName,";
	$sql .= 		" TantoName,";
	$sql .= 		" Note,";
	$sql .= 		" Status,";
	$sql .= 		" Tok,";
	$sql .= 		" TokNote,";
	$sql .= 		" YoteiDay,";
	$sql .= 		" NewOldKbn,";
	$sql .= 		" Del,";
	$sql .= 		" RegistDay,";
	$sql .= 		" RegistUser";
	$sql .= 		" )";
	$sql .= " VALUES";
	$sql .= 		" (";
	$sql .= 		" GETDATE(),";												// AppliDay
	$sql .= 		" '" . db_Escape(trim($post['requestNo'])) ."',";			// AppliNo
	$sql .= 		" '" . db_Escape(trim($_SESSION['USERID'])) . "',";			// AppliUserID
	$sql .= 		" '" . db_Escape(trim($staffData['CompCd'])) . "',";		// AppliCompCd
	$sql .= 		" '" . db_Escape(trim($staffData['CompName'])) . "',";		// AppliCompName
	$sql .= 		" " . APPLI_MODE_ORDER . ",";								// AppliMode (発注:1)
	$sql .= 		" '" . db_Escape(trim($post['appliReason'])) . "',";		// AppliReason
	$sql .= 		" '" . db_Escape(trim($post['searchPatternId'])) . "',";	// AppliPattern
	$sql .= 		" '" . db_Escape(trim($staffData['CompID'])) . "',";		// CompID
    $sql .=         " '" . db_Escape(trim($post['staffId'])) . "',";			// StaffID
	$sql .= 		" '" . db_Escape(trim($staffData['StaffCode'])) . "',";		// StaffCode
    $sql .=         " '" . db_Escape(trim($staffData['PersonName'])) . "',";	// PersonName
	$sql .= 		" '" . db_Escape(trim($zip)) . "',";						// Zip
	$sql .= 		" '" . db_Escape(trim($post['address'])) . "',";			// Adrr
	$sql .= 		" '" . db_Escape(trim($post['tel'])) . "',";				// Tel
	$sql .= 		" '" . db_Escape(trim($post['shipName'])) . "',";			// ShipName
	$sql .= 		" '" . db_Escape(trim($post['staffName'])) . "',";			// TantoName
	$sql .= 		" '" . db_Escape(trim($post['memo'])) . "',";				// Note
	$sql .= 		" " . STATUS_APPLI . ",";									// Status (承認待:1)
	// 特寸フラグが有効な場合
	if (isset($post['tokFlg']) && $post['tokFlg'] == 1) {
		$sql .= 		" 1,";													// Tok
		$sql .= 		" '" . db_Escape(trim($post['tokMemo'])) . "',";		// TokNote
	} else {
		$sql .= 		" 0,";													// Tok
		$sql .= 		" NULL,";												// TokNote
	}
	// 出荷予定日が入力されている場合
	if (isset($post['yoteiDay']) && $post['yoteiDay'] != '') {
		$sql .=			" '" . db_Escape(trim($post['yoteiDay'])) . "',";		// YoteiDay
	} else {
		$sql .= 		" NULL,";												// YoteiDay
	}
	$sql .= 		" '" . db_Escape(trim($post['newOldKbn'])) . "',";			// NewOldKbn
	$sql .= 		" " . DELETE_OFF . ",";										// Del
	$sql .= 		" GETDATE(),";												// RegistDay
	$sql .= 		" '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";		// RegistUser
	$sql .= 		" )";
//var_dump($sql);

	$isSuccess = db_Execute($dbConnect, $sql);
//var_dump("isSuccess" . $isSuccess);die;
	// 実行結果が失敗の場合
	if ($isSuccess == false) {
		return false;
	}

	// 直近のシーケンスIDを取得
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" SCOPE_IDENTITY() as scope_identity";

	$result = db_Read($dbConnect, $sql);

	// 実行結果が失敗の場合
	if ($result == false || !isset($result[0]['scope_identity']) || $result[0]['scope_identity'] == '') {
		return false;
	}

	$orderId = $result[0]['scope_identity'];

	// 特寸情報を登録
	if (isset($post['tokFlg']) && $post['tokFlg'] == 1) {

		// T_Tokに登録する
		$sql  = "";
		$sql .= " INSERT INTO";
		$sql .= 	" T_Tok";
		$sql .= 		" (";
		$sql .= 		" OrderID,";
		$sql .= 		" Height,";
		$sql .= 		" Weight,";
		$sql .= 		" Bust,";
		$sql .= 		" Waist,";
		$sql .= 		" Hips,";
		$sql .= 		" Shoulder,";
		$sql .= 		" Sleeve,";
		$sql .= 		" Length,";
        $sql .=         " Kitake,";
        $sql .=         " YuKitake,";
        $sql .=         " Inseam,";
		$sql .= 		" Del,";
		$sql .= 		" RegistDay,";
		$sql .= 		" RegistUser";
		$sql .= 		" )";
		$sql .= " VALUES";
		$sql .= 		" (";
		$sql .= 		" '" . db_Escape(trim($orderId)) . "',";
		$sql .= 		" '" . db_Escape(trim($post['high'])) . "',";
		$sql .= 		" '" . db_Escape(trim($post['weight'])) . "',";
		$sql .= 		" '" . db_Escape(trim($post['bust'])) . "',";
		$sql .= 		" '" . db_Escape(trim($post['waist'])) . "',";
		$sql .= 		" '" . db_Escape(trim($post['hips'])) . "',";
		$sql .= 		" '" . db_Escape(trim($post['shoulder'])) . "',";
		$sql .= 		" '" . db_Escape(trim($post['sleeve'])) . "',";
		$sql .= 		" '" . db_Escape(trim($post['length'])) . "',";
        $sql .=         " '" . db_Escape(trim($post['kitake'])) . "',";
        $sql .=         " '" . db_Escape(trim($post['yukitake'])) . "',";
        $sql .=         " '" . db_Escape(trim($post['inseam'])) . "',";
		$sql .= 		" " . DELETE_OFF . ",";
		$sql .= 		" GETDATE(),";
		$sql .= 		" '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= 		" )";
		
		$isSuccess = db_Execute($dbConnect, $sql);
	
		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}
	}

	// T_Staffの情報を取得
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" COUNT(StaffID) as countID";
	$sql .= " FROM";
	$sql .= 	" T_Staff";
	$sql .= " WHERE";
    $sql .=     " StaffID = '" . db_Escape(trim($post['staffId'])) . "'";
    $sql .= " AND";
	$sql .= 	" StaffCode = '" . db_Escape(trim($staffData['StaffCode'])) . "'";
	$sql .= " AND";

	$sql .= 	" CompID = '" . db_Escape(trim($staffData['CompID'])) . "'";

	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;

	$result = db_Read($dbConnect, $sql);

	$count = 0;
	if (isset($result[0]['countID'])) {
		$count = $result[0]['countID'];
	}

	// スタッフがまだ登録されていない場合
	if ($count == 0) {

		// T_Staffに登録する
		$sql  = "";
		$sql .= " INSERT INTO";
		$sql .= 	" T_Staff";
		$sql .= 		" (";
        $sql .=         " StaffID,";
		$sql .= 		" CompID,";
		$sql .= 		" StaffCode,";
		// パターンＩＤを記録 Y.Furukawa 2017/05/02
		$sql .= 		" PatternID,";
		$sql .= 		" WithdrawalFlag,";
		$sql .= 		" AllReturnFlag,";
		$sql .= 		" Del,";
		$sql .= 		" RegistDay,";
		$sql .= 		" RegistUser";
		$sql .= 		" )";
		$sql .= " VALUES";
		$sql .= 		" (";
        $sql .=         " '" . db_Escape(trim($post['staffId'])) . "',";

		$sql .= 		" '" . db_Escape(trim($staffData['CompID'])) . "',";

		$sql .= 		" '" . db_Escape(trim($staffData['StaffCode'])) . "',";

		// パターンＩＤを記録 Y.Furukawa 2017/05/02
		$sql .= 		" '" . db_Escape(trim($post['searchPatternId'])) . "',";

		$sql .= 		" 0,";	// WithdrawalFlagの初期値は0
		$sql .= 		" 0,";	// AllReturnFlagの初期値は0
		$sql .= 		" " . DELETE_OFF . ",";
		$sql .= 		" GETDATE(),";
		$sql .= 		" '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= 		" )";
//var_dump($sql);
		$isSuccess = db_Execute($dbConnect, $sql);
//var_dump($isSuccess);die;
		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}

	} else {
		// （スタッフが存在する場合）パターンＩＤを記録 Y.Furukawa 2017/05/02
	    $sql  = "";
	    $sql .= " UPDATE";
	    $sql .=     " T_Staff";
	    $sql .= " SET";
	    $sql .=     " PatternID = '" . db_Escape(trim($post['searchPatternId'])) . "',";
	    $sql .=     " UpdDay = GETDATE(),";
	    $sql .=     " UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= " WHERE";
	    $sql .=     " StaffID = '" . db_Escape(trim($post['staffId'])) . "'";
	    $sql .= " AND";
		$sql .= 	" StaffCode = '" . db_Escape(trim($staffData['StaffCode'])) . "'";
		$sql .= " AND";
		$sql .= 	" CompID = '" . db_Escape(trim($staffData['CompID'])) . "'";
		$sql .= " AND";
		$sql .= 	" Del = " . DELETE_OFF;

		$isSuccess = db_Execute($dbConnect, $sql);

		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}
	}

	// T_Order_Detailsに登録するための情報をセットここから +++++++++++++++++++++++++++++++

    $orderDetails = array();

    $itemIds = '';
    if(is_array($post['itemIds'])) {
        $itemIds = implode(', ', $post['itemIds']);
    }

    // 発注申請の一覧を取得する
    $sql  = "";
    $sql .= " SELECT";
    $sql .=     " mi.ItemID,";
    $sql .=     " mi.ItemNo,";
    $sql .=     " mi.ItemName,";
    $sql .=     " mi.SizeID";
    $sql .= " FROM";
    $sql .=     " M_Item mi";
    $sql .= " WHERE";
    $sql .=     " mi.Del = " . DELETE_OFF;
    $sql .= " AND";
    $sql .=     " mi.ItemID IN (" . db_Escape($itemIds) . ")";
    $sql .= " ORDER BY";
    $sql .=     " mi.ItemID ASC";

    $result = db_Read($dbConnect, $sql);
    // 検索結果が0件の場合
    if (count($result) <= 0) {
        $result = array();
        return $result;
    }

    // 取得した値をHTMLエンティティ
    $rowCnt = 0;
    foreach ($result as $key => $val) {
        if ($post['itemNumber'][$val['ItemID']] != '' && $post['itemNumber'][$val['ItemID']] > 0) {    
            $orderDetails[$rowCnt]['itemId'] = $val['ItemID'];
            $orderDetails[$rowCnt]['itemNo'] = $val['ItemNo'];
            $orderDetails[$rowCnt]['itemName'] = $val['ItemName'];
            $orderDetails[$rowCnt]['num'] = $post['itemNumber'][$val['ItemID']];
    
            // アイテムごとのサイズを取得
            $sizeAry = getSize($dbConnect, $val['SizeID'], 1);
            $orderDetails[$rowCnt]['size'] = $sizeAry[$post['size'.$val['ItemID']]];

            $rowCnt++;
        }
    }
    
	// T_Order_Detailsに登録するための情報をセット ここまで ++++++++++++++++++++++++++++++

	// T_Order_Detailsの登録
	$countOrderDetail = count($orderDetails);
    $line = 1;  // 行番号
	for ($i=0; $i<$countOrderDetail; $i++) {

		// 初期化
		$orderDetailId = '';

		// ストック情報を取得
		$sql  = "";
		$sql .= " SELECT";
		$sql .= 	" StockCD";
		$sql .= " FROM";
		$sql .= 	" M_StockCtrl";
		$sql .= " WHERE";
		$sql .= 	" ItemNo = '" . db_Escape($orderDetails[$i]['itemNo']) . "'";
		$sql .= " AND";
		$sql .= 	" Size = '" . db_Escape($orderDetails[$i]['size']) . "'";
		$sql .= " AND";
		$sql .= 	" Del = " . DELETE_OFF;

		$stockDatas = db_Read($dbConnect, $sql);

		// 実行結果が失敗の場合
		if ($stockDatas == false || count($stockDatas) <= 0) {
			return false;
		}
        $stockCD = $stockDatas[0]['StockCD'];

        // 各アイテムの数量分繰り返す
        for ($t = 1;$t<=$orderDetails[$i]['num'];$t++) {
    		// T_Order_Detailsの登録
    		$sql  = "";
    		$sql .= " INSERT INTO";
    		$sql .= 	" T_Order_Details";
    		$sql .= 		" (";
    		$sql .= 		" OrderID,";
    		$sql .= 		" AppliNo,";
    		$sql .= 		" AppliLNo,";
    		$sql .= 		" ItemID,";
    		$sql .= 		" ItemNo,";
    		$sql .= 		" ItemName,";
    		$sql .= 		" Size,";
    		$sql .= 		" StockCd,";
    		$sql .= 		" Status,";
    		$sql .= 		" AppliDay,";
    		$sql .= 		" Del,";
    		$sql .= 		" RegistDay,";
    		$sql .= 		" RegistUser";
    		$sql .= 		" )";
    		$sql .= " VALUES";
    		$sql .= 		" (";
    		$sql .= 		" '" . db_Escape($orderId) ."',";
    		$sql .= 		" '" . db_Escape(trim($post['requestNo'])) ."',";
    		$sql .= 		" '" . db_Escape($line) ."',";
    		$sql .= 		" '" . db_Escape(trim($orderDetails[$i]['itemId'])) ."',";
    		$sql .= 		" '" . db_Escape(trim($orderDetails[$i]['itemNo'])) ."',";
    		$sql .= 		" '" . db_Escape(trim($orderDetails[$i]['itemName'])) ."',";
    		$sql .= 		" '" . db_Escape(trim($orderDetails[$i]['size'])) ."',";
    		$sql .= 		" '" . db_Escape(trim($stockCD)) ."',";
    		$sql .= 		" " . STATUS_APPLI . ",";		// 申請済（承認待ち）は1
//            $sql .=         " " . STATUS_APPLI_ADMIT . ",";       // 承認機能がないため承認済をsセット
    		$sql .= 		" GETDATE(),";
    		$sql .= 		" " . DELETE_OFF . ",";
    		$sql .= 		" GETDATE(),";
    		$sql .= 		" '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
    		$sql .= 		" );";
//var_dump($sql);
    		$isSuccess = db_Execute($dbConnect, $sql);
//var_dump($isSuccess);die;
    		// 実行結果が失敗の場合
    		if ($isSuccess == false) {
    			return false;
    		}
    	
    		// 直近のシーケンスIDを取得
    		$sql  = "";
    		$sql .= " SELECT";
    		$sql .= 	" SCOPE_IDENTITY() as scope_identity;";
    		
    		$result = db_Read($dbConnect, $sql);
    	
    		// 実行結果が失敗の場合
    		if ($result == false || !isset($result[0]['scope_identity']) || $result[0]['scope_identity'] == '') {
    			return false;
    		}
    	
    		$orderDetailId = $result[0]['scope_identity'];
    
    		// T_Staff_Detailsの登録
    		$sql  = "";
    		$sql .= " INSERT INTO";
    		$sql .= 	" T_Staff_Details";
    		$sql .= 		" (";
    		$sql .= 		" StaffID,";
    		$sql .= 		" OrderDetID,";
    		$sql .= 		" Status,";
    		$sql .= 		" ReturnFlag,";
    		$sql .= 		" Del,";
    		$sql .= 		" RegistDay,";
    		$sql .= 		" RegistUser";
    		$sql .= 		" )";
    		$sql .= " VALUES";
    		$sql .= 		" (";
    		$sql .= 		" '" . db_Escape($post['staffId']) ."',";
    		$sql .= 		" '" . db_Escape($orderDetailId) ."',";
			$sql .=         " " . STATUS_APPLI . ",";       // 申請済（承認待ち）は1
//            $sql .=         " " . STATUS_APPLI_ADMIT . ",";       // 承認機能がないため承認済をsセット
    		$sql .= 		" 0,";							// ReturnFlagの初期値は0
    		$sql .= 		" " . DELETE_OFF . ",";
    		$sql .= 		" GETDATE(),";
    		$sql .= 		" '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
    		$sql .= 		" );";
    
    		$isSuccess = db_Execute($dbConnect, $sql);
    	
    		// 実行結果が失敗の場合
    		if ($isSuccess == false) {
    			return false;
    		}
    
            $line++;  // 行番号
        }
	}

	return true;

}

/*
 * 発注申請情報を変更する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$post      => POST値
 *       ：$staffData => 着用者情報
 *       ：$dispRentalStartDay => レンタル開始日表示設定
 * 戻り値：true：変更成功 / false：変更失敗
 *
 * create 2007/03/26 H.Osugi
 *
 */
function updateOrder($dbConnect, $post, $staffData, $dispRentalStartDay) {

	$orderId = trim($post['orderId']);

	// 郵便番号を成型する
	$zip = trim($post['zip1']) . '-' . trim($post['zip2']);

	// T_Orderを変更する
	$sql  = "";
	$sql .= " UPDATE";
	$sql .= 	" T_Order";
	$sql .= " SET";
	$sql .= 	" AppliDay = GETDATE(),";
	$sql .= 	" AppliUserID = '" . db_Escape(trim($_SESSION['USERID'])) . "',";
	$sql .= 	" AppliCompCd = '" . db_Escape(trim($staffData['CompCd'])) . "',";
	$sql .= 	" AppliCompName = '" . db_Escape(trim($staffData['CompName'])) . "',";
	$sql .= 	" AppliMode = " . APPLI_MODE_ORDER . ",";								// 発注:1
	$sql .= 	" AppliReason = " . $post['appliReason'] . ",";
	$sql .= 	" AppliPattern = '" . db_Escape(trim($post['searchPatternId'])) . "',";
	$sql .= 	" CompID = '" . db_Escape(trim($staffData['CompID'])) . "',";
	$sql .= 	" StaffCode = '" . db_Escape(trim($staffData['StaffCode'])) . "',";
	$sql .= 	" Zip = '" . db_Escape(trim($zip)) . "',";
	$sql .= 	" Adrr = '" . db_Escape(trim($post['address'])) . "',";
	$sql .= 	" Tel = '" . db_Escape(trim($post['tel'])) . "',";
	$sql .= 	" ShipName = '" . db_Escape(trim($post['shipName'])) . "',";
	$sql .= 	" TantoName = '" . db_Escape(trim($post['staffName'])) . "',";
	$sql .= 	" Note = '" . db_Escape(trim($post['memo'])) . "',";
	if (isset($post['yoteiDay']) && $post['yoteiDay'] != '') {
		$sql .=		" YoteiDay = '" . db_Escape(trim($post['yoteiDay'])) . "',";
	} else {
		$sql .= 	" YoteiDay = NULL,";
	}
	$sql .= 	" NewOldKbn = '" . db_Escape(trim($post['newOldKbn'])) . "',";
	$sql .= 	" Status = " . STATUS_APPLI . ",";										// 承認待:1

	// 特寸フラグが有効な場合
	if (isset($post['tokFlg']) && $post['tokFlg'] == 1) {
		$sql .= 	" Tok = 1,";
		$sql .= 	" TokNote = '" . db_Escape(trim($post['tokMemo'])) . "',";
	}
	else {
		$sql .= 	" Tok = 0,";
		$sql .= 	" TokNote = NULL,";
	}

	$sql .= 	" UpdDay = GETDATE(),";
	$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape(trim($orderId)) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;

	$isSuccess = db_Execute($dbConnect, $sql);

	// 実行結果が失敗の場合
	if ($isSuccess == false) {
		return false;
	}

	// 特寸情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" TokID";
	$sql .= " FROM";
	$sql .= 	" T_Tok";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;

	$result = db_Read($dbConnect, $sql);

	$tokId = '';
	if (isset($result[0]['TokID'])) {
		$tokId = $result[0]['TokID'];
	}

	// 特寸情報を登録
	if ($tokId == '' && isset($post['tokFlg']) && $post['tokFlg'] == 1) {

		// T_Tokに登録する
		$sql  = "";
		$sql .= " INSERT INTO";
		$sql .= 	" T_Tok";
		$sql .= 		" (";
		$sql .= 		" OrderID,";
		$sql .= 		" Height,";
		$sql .= 		" Weight,";
		$sql .= 		" Bust,";
		$sql .= 		" Waist,";
		$sql .= 		" Hips,";
		$sql .= 		" Shoulder,";
		$sql .= 		" Sleeve,";
		$sql .= 		" Length,";
        $sql .=         " Kitake,";
        $sql .=         " YuKitake,";
        $sql .=         " Inseam,";
		$sql .= 		" Del,";
		$sql .= 		" RegistDay,";
		$sql .= 		" RegistUser";
		$sql .= 		" )";
		$sql .= " VALUES";
		$sql .= 		" (";
		$sql .= 		" '" . db_Escape(trim($orderId)) . "',";
		$sql .= 		" '" . db_Escape(trim($post['high'])) . "',";
		$sql .= 		" '" . db_Escape(trim($post['weight'])) . "',";
		$sql .= 		" '" . db_Escape(trim($post['bust'])) . "',";
		$sql .= 		" '" . db_Escape(trim($post['waist'])) . "',";
		$sql .= 		" '" . db_Escape(trim($post['hips'])) . "',";
		$sql .= 		" '" . db_Escape(trim($post['shoulder'])) . "',";
		$sql .= 		" '" . db_Escape(trim($post['sleeve'])) . "',";
		$sql .= 		" '" . db_Escape(trim($post['length'])) . "',";
        $sql .=         " '" . db_Escape(trim($post['kitake'])) . "',";
        $sql .=         " '" . db_Escape(trim($post['yukitake'])) . "',";
        $sql .=         " '" . db_Escape(trim($post['inseam'])) . "',";
		$sql .= 		" " . DELETE_OFF . ",";
		$sql .= 		" GETDATE(),";
		$sql .= 		" '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= 		" )";
		
		$isSuccess = db_Execute($dbConnect, $sql);
	
		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}

	}

	// 特寸情報を変更
	elseif ($tokId != '' && isset($post['tokFlg']) && $post['tokFlg'] == 1) {

		// T_Tokを変更する
		$sql  = "";
		$sql .= " UPDATE";
		$sql .= 	" T_Tok";
		$sql .= " SET";
		$sql .= 	" Height = '" . db_Escape(trim($post['high'])) . "',";
		$sql .= 	" Weight = '" . db_Escape(trim($post['weight'])) . "',";
		$sql .= 	" Bust = '" . db_Escape(trim($post['bust'])) . "',";
		$sql .= 	" Waist = '" . db_Escape(trim($post['waist'])) . "',";
		$sql .= 	" Hips = '" . db_Escape(trim($post['hips'])) . "',";
		$sql .= 	" Shoulder = '" . db_Escape(trim($post['shoulder'])) . "',";
		$sql .= 	" Sleeve = '" . db_Escape(trim($post['sleeve'])) . "',";
		$sql .= 	" Length = '" . db_Escape(trim($post['length'])) . "',";
        $sql .=     " Kitake = '" . db_Escape(trim($post['kitake'])) . "',";
        $sql .=     " Yukitake = '" . db_Escape(trim($post['yukitake'])) . "',";
        $sql .=     " Inseam = '" . db_Escape(trim($post['inseam'])) . "',";
		$sql .= 	" UpdDay = GETDATE(),";
		$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= " WHERE";
		$sql .= 	" TokID = '" . db_Escape(trim($tokId)) . "'";
		$sql .= " AND";
		$sql .= 	" Del = " . DELETE_OFF;
	
		$isSuccess = db_Execute($dbConnect, $sql);

		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}

	}

	// 特寸情報を論理削除
	elseif ($tokId != '' && (!isset($post['tokFlg']) || $post['tokFlg'] != 1)) {

		// T_Tokを論理削除する
		$sql  = "";
		$sql .= " UPDATE";
		$sql .= 	" T_Tok";
		$sql .= " SET";
		$sql .= 	" Del = " . DELETE_ON . ",";
		$sql .= 	" UpdDay = GETDATE(),";
		$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= " WHERE";
		$sql .= 	" TokID = '" . db_Escape(trim($tokId)) . "'";
	
		$isSuccess = db_Execute($dbConnect, $sql);

		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}

	}

	// T_Staffの情報を取得
    $sql  = "";
    $sql .= " SELECT";
    $sql .=     " COUNT(StaffID) as countID";
    $sql .= " FROM";
    $sql .=     " T_Staff";
    $sql .= " WHERE";
    $sql .=     " StaffID = '" . db_Escape(trim($post['staffId'])) . "'";
    $sql .= " AND";
    $sql .=     " StaffCode = '" . db_Escape(trim($staffData['StaffCode'])) . "'";
    $sql .= " AND";
    $sql .=     " CompID = '" . db_Escape(trim($staffData['CompID'])) . "'";
    $sql .= " AND";
    $sql .=     " Del = " . DELETE_OFF;

	$result = db_Read($dbConnect, $sql);

    $count = 0;
    if (isset($result[0]['countID'])) {
        $count = $result[0]['countID'];
    }

    // スタッフがまだ登録されていない場合
    $newStaffFlg = false;
    if ($count == 0) {

		// T_Staffに登録する
        $sql  = "";
        $sql .= " INSERT INTO";
        $sql .=     " T_Staff";
        $sql .=         " (";
        $sql .=         " StaffID,";
        $sql .=         " CompID,";
        $sql .=         " StaffCode,";
  		// パターンＩＤを記録 Y.Furukawa 2017/05/02
        $sql .=         " PatternID,";
        $sql .=         " WithdrawalFlag,";
        $sql .=         " AllReturnFlag,";
        $sql .=         " Del,";
        $sql .=         " RegistDay,";
        $sql .=         " RegistUser";
        $sql .=         " )";
        $sql .= " VALUES";
        $sql .=         " (";
        $sql .=         " '" . db_Escape(trim($post['staffId'])) . "',";
        $sql .=         " '" . db_Escape(trim($staffData['CompID'])) . "',";
        $sql .=         " '" . db_Escape(trim($staffData['StaffCode'])) . "',";
  		// パターンＩＤを記録 Y.Furukawa 2017/05/02
   		$sql .= 		" '" . db_Escape(trim($post['searchPatternId'])) . "',";
        $sql .=         " 0,";  // WithdrawalFlagの初期値は0
        $sql .=         " 0,";  // AllReturnFlagの初期値は0
        $sql .=         " " . DELETE_OFF . ",";
        $sql .=         " GETDATE(),";
        $sql .=         " '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
        $sql .=         " )";

		$isSuccess = db_Execute($dbConnect, $sql);
	
		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}

		$staffId = $post['staffId'];

		$newStaffFlg = true;  

	}

    // T_Staff_Detailsの論理削除
    $sql  = "";
    $sql .= " UPDATE";
    $sql .=     " T_Staff_Details";
    $sql .= " SET";
    $sql .=     " UpdDay = GETDATE(),";
    $sql .=     " UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "',";
    $sql .=     " Del = " . DELETE_ON;
    $sql .= " WHERE";
    $sql .=     " OrderDetID IN (";
    $sql .=         " SELECT";
    $sql .=             " OrderDetID";
    $sql .=         " FROM";
    $sql .=             " T_Order_Details";
    $sql .=         " WHERE";
    $sql .=             " AppliNo = '". db_Escape(trim($post['requestNo'])) ."'";
    $sql .=         " AND";
    $sql .=             " Del = " . DELETE_OFF;
    $sql .=     " )";
    $sql .= " AND";
    $sql .=     " Del = " . DELETE_OFF;
    
    $isSuccess = db_Execute($dbConnect, $sql);

    // 実行結果が失敗の場合
    if ($isSuccess == false) {
        return false;
    }

    // T_Order_Detailsの論理削除
    $sql  = "";
    $sql .= " UPDATE";
    $sql .=     " T_Order_Details";
    $sql .= " SET";
    $sql .=     " UpdDay = GETDATE(),";
    $sql .=     " UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "',";
    $sql .=     " Del = " . DELETE_ON;
    $sql .= " WHERE";
    $sql .=     " AppliNo = '". db_Escape(trim($post['requestNo'])) ."'";
    $sql .= " AND";
    $sql .=     " Del = " . DELETE_OFF;
    
    $isSuccess = db_Execute($dbConnect, $sql);

    // 実行結果が失敗の場合
    if ($isSuccess == false) {
        return false;
    }

	// T_Order_Detailsに登録するための情報をセットここから +++++++++++++++++++++++++++++++
    $orderDetails = array();

    $itemIds = '';
    if(is_array($post['itemIds'])) {
        $itemIds = implode(', ', $post['itemIds']);
    }

    // 発注申請の一覧を取得する
    $sql  = "";
    $sql .= " SELECT";
    $sql .=     " mi.ItemID,";
    $sql .=     " mi.ItemNo,";
    $sql .=     " mi.ItemName,";
    $sql .=     " mi.SizeID";
    $sql .= " FROM";
    $sql .=     " M_Item mi";
    $sql .= " WHERE";
    $sql .=     " mi.Del = " . DELETE_OFF;
    $sql .= " AND";
    $sql .=     " mi.ItemID IN (" . db_Escape($itemIds) . ")";
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
        $orderDetails[$key]['itemId'] = $val['ItemID'];
        $orderDetails[$key]['itemNo'] = $val['ItemNo'];
        $orderDetails[$key]['itemName'] = $val['ItemName'];
        $orderDetails[$key]['num'] = $post['itemNumber'][$val['ItemID']];

        // アイテムごとのサイズを取得
        $sizeAry = getSize($dbConnect, $val['SizeID'], 1);
        $orderDetails[$key]['size'] = $sizeAry[$post['size'.$val['ItemID']]];
    }

	// T_Order_Detailsに登録するための情報をセット ここまで ++++++++++++++++++++++++++++++

	// T_Order_Detailsの登録
    $line = 1;  // 行番号
	$countOrderDetail = count($orderDetails);
	for ($i=0; $i<$countOrderDetail; $i++) {

		// ストック情報を取得
		$sql  = "";
		$sql .= " SELECT";
		$sql .= 	" StockCD";
		$sql .= " FROM";
		$sql .= 	" M_StockCtrl";
		$sql .= " WHERE";
		$sql .= 	" ItemNo = '" . db_Escape($orderDetails[$i]['itemNo']) . "'";
		$sql .= " AND";
		$sql .= 	" Size = '" . db_Escape($orderDetails[$i]['size']) . "'";
		$sql .= " AND";
		$sql .= 	" Del = " . DELETE_OFF;

		$stockDatas = db_Read($dbConnect, $sql);

		// 実行結果が失敗の場合
		if ($stockDatas == false || count($stockDatas) <= 0) {
			return false;
		}

        // 各アイテムの数量分繰り返す
        for ($t = 1;$t<=$orderDetails[$i]['num'];$t++) {
            // T_Order_Detailsの登録
            $sql  = "";
            $sql .= " INSERT INTO";
            $sql .=     " T_Order_Details";
            $sql .=         " (";
            $sql .=         " OrderID,";
            $sql .=         " AppliNo,";
            $sql .=         " AppliLNo,";
            $sql .=         " ItemID,";
            $sql .=         " ItemNo,";
            $sql .=         " ItemName,";
            $sql .=         " Size,";
            $sql .=         " StockCd,";
            $sql .=         " Status,";
            $sql .=         " AppliDay,";
            $sql .=         " Del,";
            $sql .=         " RegistDay,";
            $sql .=         " RegistUser";
            $sql .=         " )";
            $sql .= " VALUES";
            $sql .=         " (";
            $sql .=         " '" . db_Escape($orderId) ."',";
            $sql .=         " '" . db_Escape(trim($post['requestNo'])) ."',";
            $sql .=         " '" . db_Escape($line) ."',";
            $sql .=         " '" . db_Escape(trim($orderDetails[$i]['itemId'])) ."',";
            $sql .=         " '" . db_Escape(trim($orderDetails[$i]['itemNo'])) ."',";
            $sql .=         " '" . db_Escape(trim($orderDetails[$i]['itemName'])) ."',";
            $sql .=         " '" . db_Escape(trim($orderDetails[$i]['size'])) ."',";
            $sql .=         " '" . db_Escape(trim($stockDatas[0]['StockCD'])) ."',";
			$sql .=         " " . STATUS_APPLI . ",";       // 申請済（承認待ち）は1
//            $sql .=         " " . STATUS_APPLI_ADMIT . ",";       // 承認機能がないため承認済をsセット
            $sql .=         " GETDATE(),";
            $sql .=         " " . DELETE_OFF . ",";
            $sql .=         " GETDATE(),";
            $sql .=         " '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
            $sql .=         " );";
    
            $isSuccess = db_Execute($dbConnect, $sql);
        
            // 実行結果が失敗の場合
            if ($isSuccess == false) {
                return false;
            }

            // 直近のシーケンスIDを取得
            $sql  = "";
            $sql .= " SELECT";
            $sql .=     " SCOPE_IDENTITY() as scope_identity;";
            
            $result = db_Read($dbConnect, $sql);
        
            // 実行結果が失敗の場合
            if ($result == false || !isset($result[0]['scope_identity']) || $result[0]['scope_identity'] == '') {
                return false;
            }
        
            $orderDetailId = $result[0]['scope_identity'];
    
            // T_Staff_Detailsの登録
            $sql  = "";
            $sql .= " INSERT INTO";
            $sql .=     " T_Staff_Details";
            $sql .=         " (";
            $sql .=         " StaffID,";
            $sql .=         " OrderDetID,";
            $sql .=         " Status,";
            $sql .=         " ReturnFlag,";
            $sql .=         " Del,";
            $sql .=         " RegistDay,";
            $sql .=         " RegistUser";
            $sql .=         " )";
            $sql .= " VALUES";
            $sql .=         " (";
            $sql .=         " '" . db_Escape($post['staffId']) ."',";
            $sql .=         " '" . db_Escape($orderDetailId) ."',";
			$sql .=         " " . STATUS_APPLI . ",";       // 申請済（承認待ち）は1
//            $sql .=         " " . STATUS_APPLI_ADMIT . ",";       // 承認機能がないため承認済をsセット
            $sql .=         " 0,";                          // ReturnFlagの初期値は0
            $sql .=         " " . DELETE_OFF . ",";
            $sql .=         " GETDATE(),";
            $sql .=         " '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
            $sql .=         " );";
    
            $isSuccess = db_Execute($dbConnect, $sql);
        
            // 実行結果が失敗の場合
            if ($isSuccess == false) {
                return false;
            }

            $line++;    // 行番号更新
        }
	}

	return true;
}

/*
 * 発注申請（訂正）メールを送信する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$post      => POST値
 *       ：$orderId   => OrderID
 * 戻り値：true：変更成功 / false：変更失敗
 *
 * create 2007/03/30 H.Osugi
 *
 */
function sendMailShinsei($dbConnect, $post, $orderId) {

	$tokFlg = 0;
	if (isset($post['tokFlg']) && trim($post['tokFlg']) != '') {
		$tokFlg = trim($post['tokFlg']);
	}

	$filePath = '../../mail_template/';

	// 発注訂正時
	$motoTokFlg = 0;
	if (isset($post['rirekiFlg']) && $post['rirekiFlg'] == 1) {

		if (isset($post['motoTokFlg']) && trim($post['motoTokFlg']) == '1') {
			$motoTokFlg = trim($post['motoTokFlg']);
		}

		// 訂正メールの件名と本文を取得
		$isSuccess = hachuTeiseiMail($dbConnect, $orderId, $tokFlg, $motoTokFlg, $filePath, $subject, $message);
	
	}
	// 発注申請時
	else {
	
		// 申請メールの件名と本文を取得
		$isSuccess = hachuShinseiMail($dbConnect, $orderId, $tokFlg, $filePath, $subject, $message);
	
	}

	if ($isSuccess == false) {
		return false;
	}

	$toAddr = MAIL_GROUP_1;

	// 特寸情報があった場合は特寸のメールグループにもメール送信
	if ($tokFlg == 1 || $motoTokFlg == 1) {

		if($toAddr != '') {
			$toAddr .= ',';
		}
		$toAddr .= MAIL_GROUP_4;

	}

	$bccAddr = MAIL_GROUP_0;
	$fromAddr = FROM_MAIL;
	$returnAddr = RETURN_MAIL;

	// メールを送信
	$isSuccess = sendTextMail($toAddr, $subject, $message, $fromAddr, '', $bccAddr, 'UTF-8', $returnAddr);

	if ($isSuccess == false) {
		return false;
	}

	return true;

}

/*
 * 表示するアイテム情報を取得する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$post      => POST値
 * 戻り値：$result    => 表示する商品一覧情報
 *
 * create 2008/04/15 W.Takasaki
 *
 */
function getDispItem($dbConnect, $post)
{
    $itemIds = '';
    if(is_array($post['itemIds'])) {
        $itemIds = implode(', ', $post['itemIds']);
    }

    $returnData = array();

    // 商品情報を取得する
    $sql  = "";
    $sql .= " SELECT";
    $sql .=     " mi.ItemID,";
    $sql .=     " mi.ItemNo,";
    $sql .=     " mi.ItemName,";
    $sql .=     " mi.Price,";
    $sql .=     " mi.SizeID";
    $sql .= " FROM";
    $sql .=     " M_Item mi";
    $sql .= " WHERE";
    $sql .=     " mi.ItemID IN (" . db_Escape($itemIds) . ")";
    $sql .= " AND";
    $sql .=     " mi.Del = " . DELETE_OFF;
    $sql .= " ORDER BY";
    //$sql .=     " mi.ItemID ASC";
    $sql .=     " mi.DispFlg ASC,  mi.ItemID ASC";

    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合
    if (count($result) <= 0) {
        $result = array();
        return $result;
    }

    // 取得した値をHTMLエンティティ
    $rowCnt = 0;
    foreach ($result as $key => $val) {    
        if (isset($post['itemNumber'][$val['ItemID']]) && $post['itemNumber'][$val['ItemID']] != '' && $post['itemNumber'][$val['ItemID']] > 0) {
            $returnData[$rowCnt]['itemId'] = $val['ItemID'];
            $returnData[$rowCnt]['dispName'] = $val['ItemName'];
            $returnData[$rowCnt]['count'] = $rowCnt+1;
    
            $returnData[$rowCnt]['dispNum'] = $post['itemNumber'][$val['ItemID']];
            $returnData[$rowCnt]['dispPrice'] = $val['Price'] * $returnData[$rowCnt]['dispNum'];
    
            // アイテムごとのサイズを取得
            if (isset($val['SizeID']) && $val['SizeID'] != '' && $returnData[$rowCnt]['dispNum'] > 0) {
                $sizeAry = getSize($dbConnect, $val['SizeID'], 1);
                $returnData[$rowCnt]['sizeData'] = $sizeAry[$post['size'.$val['ItemID']]];
            }
    
            $rowCnt++;
        }
    }

    return  $returnData;
}


// 対象スタッフの所属している貸与パターン選択コンボボックス作成
function getStaffPattern($dbConnect, $patternID) {

	// 初期化
	$result = array();

	$sql = " SELECT";
	$sql .= 	" PatternName";
	$sql .= " FROM";
	$sql .= 	" M_Pattern";
	$sql .= " WHERE";
	$sql .= 	" PatternID = '" . db_Escape($patternID) . "'";
	$sql .= " AND";
	$sql .= 	" Del = '" . DELETE_OFF . "'";
	$sql .= " GROUP BY";
	$sql .= 	" PatternName";

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	return $result[0]['PatternName'];
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
          <h1>ユニフォーム発注結果</h1>
<?php } ?>
<?php if($haveRirekiFlg) { ?>
          <h1>ユニフォーム発注結果　（<span style="color:red">訂正</span>）</h1>
<?php } ?>
          <table border="0" width="700" cellpadding="0" cellspacing="0" class="tb_1">
            <tr height="30">
              <td width="100" class="line"><span class="fbold">申請番号</span></td>
              <td colspan="3" class="line"><?php isset($requestNo) ? print($requestNo) : print('&#123;requestNo&#125;'); ?></td>
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
              <td width="100" class="line"><span class="fbold">貸与パターン</span></td>
              <td colspan="3" class="line"><?php isset($searchPatternName) ? print($searchPatternName) : print('&#123;searchPatternName&#125;'); ?></td>
            </tr>
            <tr height="30">
              <td><span class="fbold">出荷先</span></td>
              <td colspan="3">〒<?php isset($zip1) ? print($zip1) : print('&#123;zip1&#125;'); ?>-<?php isset($zip2) ? print($zip2) : print('&#123;zip2&#125;'); ?></td>
            </tr>
            <tr height="25">
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
              <td width="100" class="line"></td>
              <td width="100" class="line"><span class="fbold">電話番号</span></td>
              <td width="482" colspan="5" class="line"><?php isset($tel) ? print($tel) : print('&#123;tel&#125;'); ?></td>
            </tr>
            <tr height="30">
              <td class="line"><span class="fbold">出荷指定日</span></td>
              <td colspan="3" class="line"><?php isset($yoteiDay) ? print($yoteiDay) : print('&#123;yoteiDay&#125;'); ?></td>
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
<?php if(!$haveRirekiFlg) { ?>
          <h3>◆下記の内容で<span style="color:blue">発注申請</span>を受付ました。</h3>
<?php } ?>
<?php if($haveRirekiFlg) { ?>
          <h3>◆下記の内容で<span style="color:blue">発注申請</span>を訂正しました。</h3>
<?php } ?>
          <table width="600" border="0" class="tb_1" cellpadding="0" cellspacing="3">
            <tr>
              <th align="center" width="50">No</th>
              <th align="center" width="300">アイテム名</th>
              <th align="center" width="150">サイズ</th>
              <th align="center" width="100">数量</th>
            </tr>
<?php for ($i1_displayData=0; $i1_displayData<count($displayData); $i1_displayData++) { ?>
            <tr height="20">
              <td class="line2" align="center"><?php isset($displayData[$i1_displayData]['count']) ? print($displayData[$i1_displayData]['count']) : print('&#123;displayData.count&#125;'); ?></td>
              <td class="line2"><?php isset($displayData[$i1_displayData]['dispName']) ? print($displayData[$i1_displayData]['dispName']) : print('&#123;displayData.dispName&#125;'); ?></td>
              <td class="line2" align="center"><?php isset($displayData[$i1_displayData]['sizeData']) ? print($displayData[$i1_displayData]['sizeData']) : print('&#123;displayData.sizeData&#125;'); ?></td>
              <td class="line2" align="center"><?php isset($displayData[$i1_displayData]['dispNum']) ? print($displayData[$i1_displayData]['dispNum']) : print('&#123;displayData.dispNum&#125;'); ?>着</td>
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
              <th align="center" width="60">裄丈</th>
              <th align="center" width="60">股下</th>
              <th align="center" width="60">首周り</th>
              <th align="center" width="60" colspan="5">&nbsp;</th>
            </tr>
            <tr>
              <td align="center"><?php isset($yukitake) ? print($yukitake) : print('&#123;yukitake&#125;'); ?>kg</td>
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
        </div>
        

        <div><p><BR></p></div>
        <br><br><br>
      </div>
    </div>
  </body>
</html>

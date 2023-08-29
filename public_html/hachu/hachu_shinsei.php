<?php
/*
 * 発注申請画面（女性用）
 * hachu_shinsei.src.php
 *
 * create 2007/03/14 H.Osugi
 * update 2007/03/26 H.Osugi
 * update 2008/04/14 W.Takasaki
 *
 */

// 各種モジュールの取得
include_once('../../include/define.php');           	// 定数定義
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




// 初期設定
$isMenuOrder   = true;  // 発注のメニューをアクティブに

// 変数の初期化 ここから ******************************************************
$requestNo      = '';               // 申請番号
$staffCode      = '';               // スタッフコード
$personName     = '';               // スタッフ氏名
$zip1           = '';               // 郵便番号（前半3桁）
$zip2           = '';               // 郵便番号（後半4桁）
$address        = '';               // 住所
$shipName       = '';               // 出荷先名
$staffName      = '';               // ご担当者
$tel            = '';               // 電話番号
$yoteiDay       = '';               // 出荷予定日
$memo           = '';               // メモ

$selectedSize1 = '';                // 選択されたサイズ（No1）
$selectedSize2 = '';                // 選択されたサイズ（No2）
$selectedSize3 = '';                // 選択されたサイズ（No3）
$selectedSize4 = '';                // 選択されたサイズ（No4）
$selectedSize5 = '';                // 選択されたサイズ（No5）

$selectedColor1 = false;            // 選択されたブラウスの色（オフホワイト）
$selectedColor2 = false;            // 選択されたブラウスの色（ペールピンク）
$selectedColor3 = false;            // 選択されたブラウスの色（サックスブルー）

$haveRirekiFlg  = false;            // 発注申請か発注変更かの判定フラグ（true：発注変更 / false：発注申請）

$isMotoTok      = false;            // 発注訂正する時に元の発注で特寸が選択されていたか

// 変数の初期化 ここまで ******************************************************

$post = $_POST; 
// スタッフIDが取得できなければエラーに
if (!isset($post['rirekiFlg']) || !$post['rirekiFlg']) {
    if (!isSetValue($post['staffId'])) {
        redirectTop();
    }
    // 新規の場合は初回か個別かの判定値がなければエラー
    if (!isset($post['hachuShinseiFlg']) || !$post['hachuShinseiFlg']) {
        if(isSetValue($post['appliReason'])) {
            $appliReason     = $post['appliReason'];
        } else {
            redirectTop();
        }

// Commented by T.Uno at 2022/09/30
//       if(isSetValue($post['searchPatternId'])) {
//            $searchPatternId = $post['searchPatternId'];
//        } else {
//			$returnUrl = './hachu/hachu_top.php';
//
//		    $hiddenHtml = castHiddenError($post);
//
//			// エラー画面で必要な値のセット
//			$hiddens = array();
//			$hiddens['errorName'] = 'hachuShinsei';
//			$hiddens['menuName']  = 'isMenuOrder';
//			$hiddens['returnUrl'] = $returnUrl;
//			$hiddens['errorId'][] = '200';
//
//			if (is_array($hiddenHtml)) {
//				$hiddens = array_merge($hiddens, $hiddenHtml);
//			}
//			redirectPost(HOME_URL . 'error.php', $hiddens);
//      }


//        if(isSetValue($post['searchFukusyuID'])) {
//            $searchFukusyuID = $post['searchFukusyuID'];
//        } else {
//            redirectTop();
//        }
//        if(isSetValue($post['searchGenderKbn'])) {
//            $searchGenderKbn = $post['searchGenderKbn'];
//        } else {
//            redirectTop();
//        }
    }
} else {    // 変更時は申請IDがなければエラー
    if (!isSetValue($post['orderId'])) {
        redirectTop();
    }

    $isMenuOrder   = false; // 発注のメニューをオフ
    $isMenuHistory = true;  // 申請履歴のメニューをアクティブに
    $haveRirekiFlg = true;  // 発注申請か発注変更かを判定するフラグ

}

// POST値をHTMLエンティティ
$post = castHtmlEntity($post); 

// 表示する値の成型 ここから +++++++++++++++++++++++++++++++++++++++++++++++++++

// 初期表示の場合
if (!isset($post['hachuShinseiFlg']) || !$post['hachuShinseiFlg']) {
    if (isset($post['rirekiFlg']) && $post['rirekiFlg']) {     // 申請履歴から遷移してきた場合（発注変更）
        $orderId = trim($post['orderId']);
        // 注文情報を取得
        $post = castHtmlEntity(getOrdarData($dbConnect, $_POST, $isMotoTok));
       
        $compCd   = $post['compCd'];    // 店舗番号
        $compName = $post['compName'];  // 店舗名
        $compId   = $post['compId'];    // 店舗ID
    }
}

// 発注区分をセット
if (isSetValue($post['appliReason']) && (int)$post['appliReason']) {
    $appliReason = $post['appliReason'];
} else {
    redirectTop();
}


// Commented by T.Uno at 2022/09/30
//if(isSetValue($post['searchPatternId'])) {
//    $searchPatternId = $post['searchPatternId'];
//    $searchPatternName = getStaffPattern($dbConnect, $searchPatternId);
//} else {
//    redirectTop();
//}

//if (isSetValue($post['searchFukusyuID']) && (int)$post['searchFukusyuID']) {
//    $searchFukusyuID = $post['searchFukusyuID'];
//} else {
//    redirectTop();
//}
//if (isSetValue($post['searchGenderKbn']) && (int)$post['searchGenderKbn']) {
//    $searchGenderKbn = $post['searchGenderKbn'];
//} else {
//    redirectTop();
//}

// 社員IDからページヘッダー部分に表示する情報を取得
if (isSetValue($post['staffId'])) {
    $headerInfo = getHeaderData($dbConnect, $post['staffId']);
} else {
    redirectTop();
}

// Added by T.Uno at 2022/09/30
switch ($appliReason) {
	case APPLI_REASON_ORDER_BASE:		// 基本パターン
	case APPLI_REASON_ORDER_FRESHMAN:	// 新入社員
		switch ($headerInfo['CompKind']) {
			case '1':	// そんぽの家系
				$searchPatternId = PATTERNID_JITAKU_LIKE;	// そんぽの家系
				break;
			case '2':	// ラヴィーレ系
				$searchPatternId = PATTERNID_HOTEL_LIKE;	// ラヴィーレ系
				break;
		}
		break;
	case APPLI_REASON_ORDER_GRADEUP:	// グレードアップタイ
		$searchPatternId = PATTERNID_GRADEUP_TIE;			// グレードアップタイ
		break;
	case APPLI_REASON_ORDER_PERSONAL:	// 個別発注申請
		$searchPatternId = PATTERNID_PERSONAL;				// 個別発注申請
		break;
}
if(isSetValue($searchPatternId)) {
    $searchPatternName = getStaffPattern($dbConnect, $searchPatternId);
} else {
    redirectTop();
}

// スタッフID
$staffId = '';
if (isSetValue($post['staffId'])) {
    $staffId = $post['staffId'];
}

// スタッフコード
$staffCode = '';
if (isSetValue($headerInfo['StaffCode'])) {
    $staffCode = $headerInfo['StaffCode'];
} 

// 着用者名コード
$personName = '';
if (isSetValue($headerInfo['PersonName'])) {
    $personName = $headerInfo['PersonName'];
} 

// 店舗ID
$compId = '';
if (isSetValue($headerInfo['CompID'])) {
    $compId = $headerInfo['CompID'];
} 

// 店舗コード
$compCd = '';
if (isSetValue($headerInfo['CompCd'])) {
    $compCd = $headerInfo['CompCd'];
} 

// 店舗ｺｰﾄﾞが伊勢丹新宿店の場合
// 追加 uesugi 09/01/30
if ($compCd == ORDER_ISETAN_SINJUKU){
	$post['AddIsetanItemFlg'] = True;
}else{
	$post['AddIsetanItemFlg'] = False;
}

// 店名
$compName = '';
if (isSetValue($headerInfo['CompName'])) {
    $compName = $headerInfo['CompName'];
} 

// 初回アクセス
if (!isset($post['hachuShinseiFlg']) || !$post['hachuShinseiFlg']) {

    // 表示用にHTMLエスケープ
    if (isSetValue($headerInfo)) {
        foreach ($headerInfo as $key => $val) {
            $headerInfo[$key] = htmlspecialchars($val, HTMLENTITY_QUOTE_STYLE, HTMLENTITY_ENCODE);
        }
    }

    if (isset($post['rirekiFlg']) && $post['rirekiFlg']) {  // 履歴からのアクセス

        // 申請番号を生成
        $requestNo = trim($post['requestNo']);

        // 郵便番号
        $zip1 = '';
        $zip2 = '';
        if (isSetValue($post['Zip'])) {
            list($zip1, $zip2) = explode('-', $post['Zip']);
        }
    
        // 住所
        $address = '';
        if (isSetValue($post['Adrr'])) {
            $address = $post['Adrr'];
        }
    
        // 出荷先名
        $shipName = '';
        if (isSetValue($post['ShipName'])) {
            $shipName = $post['ShipName'];
        }
    
        // 電話番号
        $tel = '';
        if (isSetValue($post['Tel'])) {
            $tel = $post['Tel'];
        }
    
        // ご担当者名を取得（HTMLエンティティ済）
        $staffName = '';
        if (isSetValue($post['TantoName'])) {
            $staffName = $post['TantoName'];
        }

        // 出荷予定日
        $yoteiDay = '';
        if (isSetValue($post['yoteiDay'])) {
            $yoteiDay = $post['yoteiDay'];
        }

        // メモ
        $memo = '';
        if (isSetValue($post['memo'])) {
            $memo = $post['memo'];
        }

		// 新品中古区分
		$new_Item = false;
		if(trim($post['newOldKbn']) == 1){
			$new_Item = true;
		}

    } else {

        // 申請番号を生成
        $requestNo = createRequestNo($dbConnect, $headerInfo['CompID'], 1);
    
        // 申請番号の生成に失敗した場合はエラー
        if ($requestNo == false) {
            redirectTop();
        }

        // 郵便番号
        $zip1 = '';
        $zip2 = '';
        if (isSetValue($headerInfo['Zip'])) {
            list($zip1, $zip2) = explode('-', $headerInfo['Zip']);
        }
    
        // 住所
        $address = '';
        if (isSetValue($headerInfo['Adrr'])) {
            $address = $headerInfo['Adrr'];
        }
    
        // 出荷先名
        $shipName = '';
        if (isSetValue($headerInfo['ShipName'])) {
            $shipName = $headerInfo['ShipName'];
        }
    
        // 電話番号
        $tel = '';
        if (isSetValue($headerInfo['Tel'])) {
            $tel = $headerInfo['Tel'];
        }
	
        // ご担当者名を取得（HTMLエンティティ済）
        $staffName = '';
        if (isSetValue($headerInfo['TantoName'])) {
            $staffName = $headerInfo['TantoName'];
        } else {
            $staffName  = DEFAULT_STAFF_NAME;
		}

		// 新品中古区分
		$new_Item = true;
    }

} else {  // POST情報を引き継ぐ場合

    // OrderId
    if (isset($post['orderId'])) {
        $orderId = trim($post['orderId']);
    }

    // 申請番号を生成
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

    // 出荷予定日
    $yoteiDay = trim($post['yoteiDay']);

    // メモ
    $memo = trim($post['memo']);

	// 新品中古区分
	$new_Item = false;
	if(trim($post['newOldKbn']) == 1){
		$new_Item = true;
	}

    if (isset($post['motoTokFlg']) && trim($post['motoTokFlg']) == 1) {
        $isMotoTok = true;
    }

}

// hidden値の生成
if (isset($post['rirekiFlg']) && $post['rirekiFlg']) {

    $hiddens = array();

    // 検索フラグ
//    $hiddens['searchFlg'] = '';
//    if (isset($post['searchFlg']) && $post['searchFlg'] != '') {
//        $hiddens['searchFlg'] = $post['searchFlg'];
//    }

    // 現在のページ数
    if (isset($post['nowPage']) && $post['nowPage'] != '') {
        $hiddens['nowPage'] = $post['nowPage'];
    }

    // 施設コード
    if (isset($post['searchCompCd']) && $post['searchCompCd'] != '') {
        $hiddens['searchCompCd'] = $post['searchCompCd'];
    }

    // 施設名
    if (isset($post['searchCompName']) && $post['searchCompName'] != '') {
        $hiddens['searchCompName'] = $post['searchCompName'];
    }

    // 施設ID
    if (isset($post['searchCompId']) && $post['searchCompId'] != '') {
        $hiddens['searchCompId'] = $post['searchCompId'];
    }

    // 申請番号
    if (isset($post['searchAppliNo']) && $post['searchAppliNo'] != '') {
        $hiddens['searchAppliNo'] = $post['searchAppliNo'];
    }

    // 申請日
    if (isset($post['searchAppliDayFrom']) && $post['searchAppliDayFrom'] != '') {
        $hiddens['searchAppliDayFrom'] = $post['searchAppliDayFrom'];
    }
    if (isset($post['searchAppliDayTo']) && $post['searchAppliDayTo'] != '') {
        $hiddens['searchAppliDayTo'] = $post['searchAppliDayTo'];
    }

    // 出荷日
    if (isset($post['searchShipDayFrom']) && $post['searchShipDayFrom'] != '') {
        $hiddens['searchShipDayFrom'] = $post['searchShipDayFrom'];
    }
    if (isset($post['searchShipDayTo']) && $post['searchShipDayTo'] != '') {
        $hiddens['searchShipDayTo'] = $post['searchShipDayTo'];
    }

    // スタッフコード
    if (isset($post['searchStaffCode']) && $post['searchStaffCode'] != '') {
        $hiddens['searchStaffCode'] = $post['searchStaffCode'];
    }

    // 単品番号 
    if (isset($post['searchBarCode']) && $post['searchBarCode'] != '') {
        $hiddens['searchBarCode'] = $post['searchBarCode'];
    }

    // 状態
    $countSearchStatus = count($post['searchStatus']);
    for ($i=0; $i<$countSearchStatus; $i++) {
        $hiddens['searchStatus[' . $i . ']'] = $post['searchStatus'][$i];
    }

    $hiddenHtml = castHidden($hiddens);

} else {

    $hiddens = array();

    // 検索フラグ
//    $hiddens['searchFlg'] = '';
//    if (isset($post['searchFlg']) && $post['searchFlg'] != '') {
//        $hiddens['searchFlg']         = $post['searchFlg'];
//    }

    // 現在のページ数
    if (isset($post['nowPage']) && $post['nowPage'] != '') {
        $hiddens['nowPage']           = $post['nowPage'];
    }

    // 事業部
    if (isset($post['searchHonbuId']) && $post['searchHonbuId'] != '') {
        $hiddens['searchHonbuId']     = $post['searchHonbuId'];
    }

    // エリア
    if (isset($post['searchShitenId']) && $post['searchShitenId'] != '') {
        $hiddens['searchShitenId']    = $post['searchShitenId'];
    }

    // 施設
    if (isset($post['searchEigyousyoId']) && $post['searchEigyousyoId'] != '') {
        $hiddens['searchEigyousyoId'] = $post['searchEigyousyoId'];
    }

    // 職員コード
    if (isset($post['searchStaffCode']) && $post['searchStaffCode'] != '') {
        $hiddens['searchStaffCode']   = $post['searchStaffCode'];
    }

    // 氏名
    if (isset($post['searchPersonName']) && $post['searchPersonName'] != '') {
        $hiddens['searchPersonName']  = $post['searchPersonName'];
    }

    $hiddenHtml = castHidden($hiddens);
}

if ($post['appliReason'] == APPLI_REASON_ORDER_PERSONAL) {  // 個別発注
    $isSyokai = false;  // 画面表示分岐用
} else {                                                    // 初回発注
    $isSyokai = true;  // 画面表示分岐用

	// Modify by Y.Furukawa at 2020/05/12 個別発注の場合は重複貸与チェックは無しとする。
	// スタッフIDの重複チェックを行う
	if ((!isset($post['hachuShinseiFlg']) || !$post['hachuShinseiFlg'])
	 && (!isset($post['rirekiFlg']) || !$post['rirekiFlg'])) {

		$returnUrl = './select_staff.php';
		//$returnUrl = './hachu/hachu_top.php';

	    $hiddenPost = castHiddenError($post);

		// 重複チェック
		checkDuplicateStaffID($dbConnect, $staffId, $returnUrl, $hiddenPost, $post['appliReason']);
	}
}

// 表示する情報を出力
$displayData = getDispItem($dbConnect, $post, $searchPatternId);
if (!$displayData) {
   redirectTop();
}

// 表示する値の成型 ここまで +++++++++++++++++++++++++++++++++++++++++++++++++++

/*
 * 変更する発注申請情報を取得する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$post      => POST値
 *       ：$sizeData1 => サイズ1
 *       ：$sizeData2 => サイズ2
 *       ：$sizeData3 => サイズ3
 *       ：$isMotoTok => 元の発注で特寸が選択されていたかどうか
 * 戻り値：$result    => 変更する商品一覧情報
 *
 * create 2007/03/26 H.Osugi
 *
 */
function getOrdarData($dbConnect, $post, &$isMotoTok) {

    // 初期化
    $returnDatas = $post;
    $isMotoTok = false;

    // OrderID
    $orderId = trim($post['orderId']);

    // 変更する発注申請情報を取得する
    $sql  = "";
    $sql .= " SELECT";
    $sql .=     " O.AppliNo,";
    $sql .=     " O.AppliCompCd,";
    $sql .=     " O.AppliCompName,";
    $sql .=     " O.AppliReason,";
    $sql .=     " O.AppliPattern,";
    $sql .=     " O.CompID,";
    $sql .=     " O.StaffID,";
    $sql .=     " O.StaffCode,";
    $sql .=     " S.FukusyuID,";
    $sql .=     " S.GenderKbn,";
    $sql .=     " O.Zip,";
    $sql .=     " O.Adrr,";
    $sql .=     " O.ShipName,";
    $sql .=     " O.TantoName,";
    $sql .=     " O.Tel,";
    $sql .=     " O.Note,";
    $sql .=     " O.NewOldKbn,";
    $sql .=     " YoteiDay = CASE";
    $sql .=     " WHEN";
    $sql .=         " O.YoteiDay = NULL";
    $sql .=             " THEN";
    $sql .=                 " NULL";
    $sql .=             " ELSE";
    $sql .=             " CONVERT(varchar,O.YoteiDay,111)";
    $sql .=         " END,";
    $sql .=     " C.CompKind";
    $sql .= " FROM";
    $sql .=     " T_Order O";
    $sql .=     " INNER JOIN";
    $sql .=     " M_Comp C";
    $sql .=     " ON";
    $sql .=         " C.CompID = O.CompID";
    $sql .=     " INNER JOIN";
    $sql .=     " M_Staff S";
    $sql .=     " ON";
    $sql .=         " S.StaffSeqID = O.StaffID";
    $sql .= " WHERE";
    $sql .=     " O.OrderID = '" . db_Escape($orderId) . "'";
    $sql .= " AND";
    $sql .=     " O.Del = " . DELETE_OFF;
    $sql .= " AND";
    $sql .=     " C.Del = " . DELETE_OFF;

    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合
    if (count($result) <= 0) {
        return $returnDatas;
    }

    $returnDatas['requestNo']              = $result[0]['AppliNo'];              // 申請番号
    $returnDatas['compCd']                 = $result[0]['AppliCompCd'];          // 店舗コード
    $returnDatas['compName']               = $result[0]['AppliCompName'];        // 店舗名
    $returnDatas['appliReason']            = $result[0]['AppliReason'];          // 発注区分
    $returnDatas['searchPatternId']        = $result[0]['AppliPattern'];         // 貸与パターン
    $returnDatas['compId']                 = $result[0]['CompID'];               // 店舗ID
    $returnDatas['compKind']               = $result[0]['CompKind'];             // 店舗種類
    $returnDatas['staffCode']              = $result[0]['StaffCode'];            // スタッフコード
    $returnDatas['staffId']                = $result[0]['StaffID'];              // スタッフID
//    $returnDatas['searchFukusyuID']        = $result[0]['FukusyuID'];            // 服種ID
//    $returnDatas['searchGenderKbn']        = $result[0]['GenderKbn'];            // 性別
    $returnDatas['newOldKbn']              = $result[0]['NewOldKbn'];            // 新品/中古

    list($returnDatas['zip1'], $returnDatas['zip2']) = explode('-', $result[0]['Zip']);     // 郵便番号

    $returnDatas['address']                = $result[0]['Adrr'];                 // 住所
    $returnDatas['shipName']               = $result[0]['ShipName'];             // 出荷先名
    $returnDatas['staffName']              = $result[0]['TantoName'];            // ご担当者
    $returnDatas['tel']                    = $result[0]['Tel'];                  // 電話番号
    $returnDatas['memo']                   = $result[0]['Note'];                 // メモ
    $returnDatas['yoteiDay']               = $result[0]['YoteiDay'];             // 出荷予定日

    $returnDatas['hachuShinseiFlg']        = true;                               // 処理分岐のフラグ

    $returnDatas['rirekiFlg']              = true;                               // 発注申請か発注変更かの判定フラグ


    // 変更する発注申請詳細情報を取得する
    $sql  = "";
    $sql .= " SELECT";
//    $sql .=     " DISTINCT";
    $sql .=     " mi.ItemNo,";
    $sql .=     " mi.ItemID,";
    $sql .=     " tod.Size,";
    $sql .=     " mi.SizeID";
    $sql .= " FROM";
    $sql .=     " T_Order_Details tod";
    $sql .= " INNER JOIN";
    $sql .=     " M_Item mi";
    $sql .= " ON";
    $sql .=     " tod.ItemID = mi.ItemID";
    $sql .= " AND";
    $sql .=     " mi.Del = " . DELETE_OFF;
    $sql .= " WHERE";
    $sql .=     " tod.OrderID = '" . db_Escape($orderId) . "'";
    $sql .= " AND";
    $sql .=     " tod.Del = " . DELETE_OFF;

    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合
    if (count($result) <= 0) {
        return $returnDatas;
    }

    $countOrderDetail = count($result);
    for ($i=0; $i<$countOrderDetail; $i++) {

        if ($i == 0 || $result[$i]['ItemID'] != $result[$i-1]['ItemID']) { 
            $sizeArray = array();
            $sizeArray = getSize($dbConnect, $result[$i]['SizeID'], 1);
    
            $returnDatas['size'.$result[$i]['ItemID']] = array_search($result[$i]['Size'], $sizeArray);
    
            // 特寸サイズが選択されていないか判定
            //if (array_search($result[$i]['Size'], $sizeArray) == 'Size'.count($sizeArray)) {
            //    $isMotoTok = true;
            //}
            if (trim($result[$i]['Size']) == '特寸') {
                $isMotoTok = true;
            }

            // アイテム個数
            $returnDatas['itemNumber'][$result[$i]['ItemID']] = 1;
        } else {
            $returnDatas['itemNumber'][$result[$i]['ItemID']]++;
        }
    }

    return $returnDatas;

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
function getDispItem($dbConnect, $post, $patternId)
{

    $returnData = array();

    if ($post['appliReason'] == APPLI_REASON_ORDER_PERSONAL || (isset($post['rirekiFlg']) && $post['rirekiFlg'])) {  // 個別発注時

        // 表示するアイテム一覧を取得
        $sql  = "";
        $sql .= " SELECT";
        $sql .=     " mi.ItemID,";
        $sql .=     " mi.ItemNo,";
        $sql .=     " mi.ItemName,";
        $sql .=     " mi.SizeID";
        $sql .= " FROM";
        $sql .=     " M_Item mi";

	    if ($post['appliReason'] == APPLI_REASON_ORDER_PERSONAL && !isset($post['rirekiFlg'])) {
            $sql .= " INNER JOIN";
            $sql .= " M_ItemSelect ISelect";
            $sql .= " ON";
            $sql .= " mi.ItemID = ISelect.ItemID";
            $sql .= " AND";
            $sql .= " ISelect.PatternID = " . $patternId;
        }

        if (isset($post['rirekiFlg']) && $post['rirekiFlg']) {
            $sql .= " INNER JOIN";
            $sql .= " T_Order_Details tod";
            $sql .= " ON";
            $sql .= " mi.ItemID = tod.ItemID";
            $sql .= " AND";
            $sql .= " tod.Del = " . DELETE_OFF;
            $sql .= " AND";
            $sql .= " tod.OrderID = ".$post['orderId'];
        }
        $sql .= " WHERE";
        $sql .=     " mi.Del = " . DELETE_OFF;
        //if ($post['appliReason'] == APPLI_REASON_ORDER_PERSONAL && !isset($post['rirekiFlg'])) {
        //    $sql .= " AND";
        //    $sql .= 	" mi.DispFlg = " . COMMON_FLAG_ON;
        //}
        $sql .= " GROUP BY";
        $sql .=     " mi.ItemID,";
        $sql .=     " mi.ItemNo,";
        $sql .=     " mi.ItemName,";
        $sql .=     " mi.SizeID";
        $sql .= " ORDER BY";
        $sql .=     " mi.ItemID ASC";
        $result = db_Read($dbConnect, $sql);
    
        // 検索結果が0件の場合
        if (!is_array($result) || count($result) <= 0) {
            return false;
        }

    } else {        // 新規の初回
        // 表示するアイテム一覧を取得
        $sql = "";
        $sql .= " SELECT";
        $sql .=     " I.ItemID";
        $sql .=    " ,I.ItemNo";
        $sql .=    " ,ISelect.SizeID";
        $sql .=    " ,ISelect.ItemSelectName as ItemName";
        $sql .=    " ,ISelect.ItemSelectNum";
        $sql .=    " ,ISelect.FreeSizeFlag";
        $sql .=    " ,ISelect.GroupID";
        $sql .= " FROM";
        $sql .=    " M_Item I";
        $sql .=    " INNER JOIN";
        $sql .=    " M_ItemSelect ISelect";
        $sql .=    " ON";
        $sql .=    " I.ItemID = ISelect.ItemID";
        $sql .= " WHERE";
        //$sql .=     " ISelect.AppliReason = " . $post['appliReason'];
        $sql .=     " ISelect.PatternID = " . $patternId;
        $sql .= " AND";
        $sql .=     " I.Del = " . DELETE_OFF;
        $sql .= " AND";
        $sql .=     " ISelect.Del = " . DELETE_OFF;
    
        $result = db_Read($dbConnect, $sql);
    
        // 検索結果が0件の場合
        if (!is_array($result) || count($result) <= 0) {
            return false;
        }

        // データを整形
        for ( $chk=0; $chk < count($result); $chk++) {
//            $post['itemNumber'][$result[$chk]['ItemID']] = $result[$chk]['ItemSelectNum'];
        }
    }

    // サイズを取得,整形
    $limitAry = array();
    foreach ($result as $key => $val) {    

        $returnData[$key]['itemId'] = $val['ItemID'];
        $returnData[$key]['dispName'] = $val['ItemName'];
        $returnData[$key]['count'] = $key+1;

        // チェックボックスが選択されているか判定
        $returnData[$key]['checked'] = false;
        if (isset($post['itemIds']) && is_array($post['itemIds'])) {
            if (in_array($val['ItemID'], $post['itemIds'])) {
                $returnData[$key]['checked'] = true;
            }
        }

        if ($val['FreeSizeFlag']) {
            $returnData[$key]['isFree'] = true;    
            $returnData[$key]['sizeName'] = 'size'.$val['ItemID'];
        } else {
            $returnData[$key]['isFree'] = false;    

            // アイテムごとのサイズを取得
 	        $returnData[$key]['sizeData'] = castListboxSize(getSize($dbConnect, $val['SizeID'], 1), $post['size'.$val['ItemID']]);

            $returnData[$key]['sizeName'] = 'size'.$val['ItemID'];

        }

        // アイテムグループ（数量を同グループアイテムの合計で扱う）
        $returnData[$key]['isGroup'] = false;   // グループ設定されているか
        $returnData[$key]['groupId'] = '0';     // グループ設定されていない場合は0
        $returnData[$key]['limitNum'] = '0';    // 上限アイテム数
        if (isset($val['GroupID']) && !is_null($val['GroupID']) && $val['GroupID'] != 0) {
            $returnData[$key]['isGroup'] = true;   
            $returnData[$key]['groupId'] = $val['GroupID'];
            if (!isset($limitAry[$val['GroupID']])) {
                $limitAry[$val['GroupID']] = 0;
            }
            $limitAry[$val['GroupID']] = $limitAry[$val['GroupID']] + $val['ItemSelectNum'];
//            $returnData[$key]['limitNum'] = $val['ItemSelectNum'];
        }

        // アイテム数量   
        $returnData[$key]['dispNum'] = '';
        if (!isset($post['itemNumber'][$val['ItemID']]) && (!isset($post['hachuShinseiFlg']) || !$post['hachuShinseiFlg']) && isset($val['ItemSelectNum'])) {
            $returnData[$key]['dispNum'] = $val['ItemSelectNum'];
        } else if (isset($post['itemNumber'][$val['ItemID']])) {
            $returnData[$key]['dispNum'] = $post['itemNumber'][$val['ItemID']];
        } else {
//            $returnData[$key]['dispNum'] = 0;
            $returnData[$key]['dispNum'] = '';
        }

    }
    foreach ($limitAry as $laKey => $laVal) {
        foreach ($returnData as $rdKey => $rdVal) {
            if ($rdVal['groupId'] == $laKey)  {
                $returnData[$rdKey]['limitNum'] = $laVal;                
            }
        }        
    } 

    return $returnData;
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
    <link rel="stylesheet" href="//code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />
    <title>制服管理システム</title>
    <script src="//code.jquery.com/jquery-1.9.1.js"></script>
    <script src="//code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
    <script src="//rawgit.com/jquery/jquery-ui/master/ui/i18n/datepicker-ja.js"></script>
    <script language="JavaScript">
    <!--
    function confirmCancel() {

      if (confirm('キャンセルしてもよろしいですか')) {
        document.registForm.action='../rireki/cancel_kanryo.php';
        document.registForm.submit();
      }

      return false;

    }
    // -->
    </script>
    <script type="text/javascript">
      $(function() {
        $("#datepicker").datepicker();
        $('#datepicker').datepicker("option", "dateFormat", 'yy/mm/dd' );
        $("#datepicker").datepicker("setDate", "<?php isset($yoteiDay) ? print($yoteiDay) : print('&#123;yoteiDay&#125;'); ?>");
      });
    </script>

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
          <h1>ユニフォーム発注申請</h1>
<?php } ?>
<?php if($haveRirekiFlg) { ?>
          
          <h1>ユニフォーム発注申請　（<span style="color:red">訂正</span>）</h1>
          
<?php } ?>
          <table border="0" cellpadding="0" cellspacing="0">
            <tr>
              <td>
                <form name="registForm" method="post" action="hachu_shinsei_kakunin.php">
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
                      <td class="line"><?php isset($staffCode) ? print($staffCode) : print('&#123;staffCode&#125;'); ?>&nbsp;</td>
                      <td width="80" class="line">
                        <span class="fbold">職員名</span>
                        &nbsp;
                      </td>
                      <td width="400" class="line">
                        <?php isset($personName) ? print($personName) : print('&#123;personName&#125;'); ?>
                        &nbsp;
                      </td>
                    </tr>
                    <tr height="30">
                      <td width="100" class="line"><span class="fbold">貸与パターン</span></td>
                      <td colspan="3" class="line"><?php isset($searchPatternName) ? print($searchPatternName) : print('&#123;searchPatternName&#125;'); ?></td>
                    </tr>
                    <tr height="30">
                      <td><span class="fbold">出荷先</span></td>
                      <td colspan="2">〒
                        <input name="zip1" type="text" value="<?php isset($zip1) ? print($zip1) : print('&#123;zip1&#125;'); ?>" style="width:40px;" maxlength="3"/>
                        -
                        <input name="zip2" type="text" value="<?php isset($zip2) ? print($zip2) : print('&#123;zip2&#125;'); ?>" style="width:40px;" maxlength="4">
                      </td>
                      <td>
                        <input name="shop_btn" type="button" value="出荷先選択" onclick="window.open('search_comp.php', 'searchComp', 'width=500, height=400, menubar=no, toolbar=no, scrollbars=yes'); return false;">
                        <span style="color:red">出荷先住所は手入力での入力も可能です。</span>
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
                      <td class="line"><span class="fbold">出荷指定日</span></td>
                      <td class="line"><input name="yoteiDay" type="text" value="<?php isset($yoteiDay) ? print($yoteiDay) : print('&#123;yoteiDay&#125;'); ?>" class="input_date" maxlength="10" id="datepicker" style="width:80px;"></td>
                      <td colspan="2" class="line"><font size="1" color="red">※出荷指定日に対し、倉庫の休業日等によりご希望に添えない場合が有ります。<br>　発注入力当日・土日はご指定いただけません。</font></td>
                    </tr>
                    <tr height="30">
                      <td width="100" class="line"><span class="fbold">メモ</span></td>
                      <td colspan="3" class="line"><input name="memo" type="text" value="<?php isset($memo) ? print($memo) : print('&#123;memo&#125;'); ?>" style="width:500px;"></td>
                    </tr>
                    <tr height="30">
                      <td colspan="4" align="right" valign="bottom"><span style="color:red">サイズ選びの際、こちらを参照して下さい。</span>&nbsp;<a href="<?php isset($sizeUrl) ? print($sizeUrl) : print('&#123;sizeUrl&#125;'); ?>" target="_blank"><img src="../img/size_pattern.gif" alt="サイズ表" border="0"></a></td>
                    </tr>
                  </table>
<?php if(!$haveRirekiFlg) { ?>
                  <h3>◆発注したいユニフォームのサイズを入力してください。</h3>
<?php } ?>
<?php if($haveRirekiFlg) { ?>
                  
                  <h3>◆申請済のユニフォームのサイズを訂正してください。</h3>
                  
<?php } ?>
                  <div align="center">
                  <table width="600" border="0" class="tb_1" cellpadding="0" cellspacing="3">
                    <tr>
<?php if($haveRirekiFlg) { ?>
                      <th align="center" width="50">No</th>
<?php } ?>
<?php if(!$haveRirekiFlg) { ?>
<?php if($isSyokai) { ?>
                      <th align="center" width="50">No</th>
<?php } ?>
<?php if(!$isSyokai) { ?>
                      <th align="center" width="50">選択</th>
<?php } ?>
<?php } ?>
                      <th align="center" width="300">アイテム名</th>
                      <th align="center" width="150">サイズ</th>
                      <th align="center" width="100">数量</th>
                    </tr>

<?php for ($i1_displayData=0; $i1_displayData<count($displayData); $i1_displayData++) { ?>
                     <tr valign="middle" height="20">
<?php if($haveRirekiFlg) { ?>
                       <td class="line2" align="center">
                        <?php isset($displayData[$i1_displayData]['count']) ? print($displayData[$i1_displayData]['count']) : print('&#123;displayData.count&#125;'); ?>
                       <input name="itemIds[]" type="hidden" value="<?php isset($displayData[$i1_displayData]['itemId']) ? print($displayData[$i1_displayData]['itemId']) : print('&#123;displayData.itemId&#125;'); ?>">
                       </td>
<?php } ?>
<?php if(!$haveRirekiFlg) { ?>
<?php if($isSyokai) { ?>
                       <td class="line2" align="center">
                       <?php isset($displayData[$i1_displayData]['count']) ? print($displayData[$i1_displayData]['count']) : print('&#123;displayData.count&#125;'); ?>
                       <input name="itemIds[]" type="hidden" value="<?php isset($displayData[$i1_displayData]['itemId']) ? print($displayData[$i1_displayData]['itemId']) : print('&#123;displayData.itemId&#125;'); ?>">
                       </td>
<?php } ?>
<?php if(!$isSyokai) { ?>
<?php if($displayData[$i1_displayData]['checked']) { ?>
                       <td class="line2" align="center"><input name="itemIds[]" type="checkbox" id="checkbox[]" value="<?php isset($displayData[$i1_displayData]['itemId']) ? print($displayData[$i1_displayData]['itemId']) : print('&#123;displayData.itemId&#125;'); ?>" checked="checked"></td>
<?php } ?>
<?php if(!$displayData[$i1_displayData]['checked']) { ?>
                       
                       <td class="line2" align="center"><input name="itemIds[]" type="checkbox" id="checkbox[]" value="<?php isset($displayData[$i1_displayData]['itemId']) ? print($displayData[$i1_displayData]['itemId']) : print('&#123;displayData.itemId&#125;'); ?>"></td>
                       
<?php } ?>
<?php } ?>
<?php } ?>
                      <td class="line2"><?php isset($displayData[$i1_displayData]['dispName']) ? print($displayData[$i1_displayData]['dispName']) : print('&#123;displayData.dispName&#125;'); ?></td>
                      <td class="line2" align="center">
<?php if(!$displayData[$i1_displayData]['isFree']) { ?>
                        <select name="<?php isset($displayData[$i1_displayData]['sizeName']) ? print($displayData[$i1_displayData]['sizeName']) : print('&#123;displayData.sizeName&#125;'); ?>">
                          <option value="">--サイズ選択--</option>
<?php for ($i2_displayData['sizeData']=0; $i2_displayData['sizeData']<count($displayData[$i1_displayData]['sizeData']); $i2_displayData['sizeData']++) { ?>
<?php if($displayData[$i1_displayData]['sizeData'][$i2_displayData['sizeData']]['selected']) { ?>
                          
                          <option value="<?php isset($displayData[$i1_displayData]['sizeData'][$i2_displayData['sizeData']]['value']) ? print($displayData[$i1_displayData]['sizeData'][$i2_displayData['sizeData']]['value']) : print('&#123;displayData.sizeData.value&#125;'); ?>" selected="selected"><?php isset($displayData[$i1_displayData]['sizeData'][$i2_displayData['sizeData']]['display']) ? print($displayData[$i1_displayData]['sizeData'][$i2_displayData['sizeData']]['display']) : print('&#123;displayData.sizeData.display&#125;'); ?></option>
                          
<?php } ?>
<?php if(!$displayData[$i1_displayData]['sizeData'][$i2_displayData['sizeData']]['selected']) { ?>
                          <option value="<?php isset($displayData[$i1_displayData]['sizeData'][$i2_displayData['sizeData']]['value']) ? print($displayData[$i1_displayData]['sizeData'][$i2_displayData['sizeData']]['value']) : print('&#123;displayData.sizeData.value&#125;'); ?>"><?php isset($displayData[$i1_displayData]['sizeData'][$i2_displayData['sizeData']]['display']) ? print($displayData[$i1_displayData]['sizeData'][$i2_displayData['sizeData']]['display']) : print('&#123;displayData.sizeData.display&#125;'); ?></option>
<?php } ?>
<?php } ?>
                        </select>
<?php } ?>
<?php if($displayData[$i1_displayData]['isFree']) { ?>
                    F(ﾌﾘｰ)
                    <input type="hidden" name="<?php isset($displayData[$i1_displayData]['sizeName']) ? print($displayData[$i1_displayData]['sizeName']) : print('&#123;displayData.sizeName&#125;'); ?>" value="Size1">
<?php } ?>
                      </td>

<?php if($isSyokai) { ?>
<?php if(!$displayData[$i1_displayData]['isGroup']) { ?>
                      <td class="line2" align="center">
                       <?php isset($displayData[$i1_displayData]['dispNum']) ? print($displayData[$i1_displayData]['dispNum']) : print('&#123;displayData.dispNum&#125;'); ?>着
                       <input type="hidden" name="itemNumber[<?php isset($displayData[$i1_displayData]['itemId']) ? print($displayData[$i1_displayData]['itemId']) : print('&#123;displayData.itemId&#125;'); ?>]" value="<?php isset($displayData[$i1_displayData]['dispNum']) ? print($displayData[$i1_displayData]['dispNum']) : print('&#123;displayData.dispNum&#125;'); ?>">
                       <input type="hidden" name="groupId[<?php isset($displayData[$i1_displayData]['itemId']) ? print($displayData[$i1_displayData]['itemId']) : print('&#123;displayData.itemId&#125;'); ?>]" value="<?php isset($displayData[$i1_displayData]['groupId']) ? print($displayData[$i1_displayData]['groupId']) : print('&#123;displayData.groupId&#125;'); ?>">
                      </td>
<?php } ?>
<?php if($displayData[$i1_displayData]['isGroup']) { ?>
                      <td class="line2" align="center">
                       <input name="itemNumber[<?php isset($displayData[$i1_displayData]['itemId']) ? print($displayData[$i1_displayData]['itemId']) : print('&#123;displayData.itemId&#125;'); ?>]" type="text" size="3" style="text-align: right" value="<?php isset($displayData[$i1_displayData]['dispNum']) ? print($displayData[$i1_displayData]['dispNum']) : print('&#123;displayData.dispNum&#125;'); ?>" maxlength="1">着
                       <input type="hidden" name="groupId[<?php isset($displayData[$i1_displayData]['itemId']) ? print($displayData[$i1_displayData]['itemId']) : print('&#123;displayData.itemId&#125;'); ?>]" value="<?php isset($displayData[$i1_displayData]['groupId']) ? print($displayData[$i1_displayData]['groupId']) : print('&#123;displayData.groupId&#125;'); ?>">
                       <input type="hidden" name="limitNum[<?php isset($displayData[$i1_displayData]['itemId']) ? print($displayData[$i1_displayData]['itemId']) : print('&#123;displayData.itemId&#125;'); ?>]" value="<?php isset($displayData[$i1_displayData]['limitNum']) ? print($displayData[$i1_displayData]['limitNum']) : print('&#123;displayData.limitNum&#125;'); ?>">
                      </td>
<?php } ?>
<?php } ?>
<?php if(!$isSyokai) { ?>
                      <td class="line2" align="center">
                       <input name="itemNumber[<?php isset($displayData[$i1_displayData]['itemId']) ? print($displayData[$i1_displayData]['itemId']) : print('&#123;displayData.itemId&#125;'); ?>]" type="text" size="3" style="text-align: right" value="<?php isset($displayData[$i1_displayData]['dispNum']) ? print($displayData[$i1_displayData]['dispNum']) : print('&#123;displayData.dispNum&#125;'); ?>" maxlength="1">着
                       <input type="hidden" name="groupId[<?php isset($displayData[$i1_displayData]['itemId']) ? print($displayData[$i1_displayData]['itemId']) : print('&#123;displayData.itemId&#125;'); ?>]" value="0">
                      </td>
<?php } ?>

                    </tr>
<?php } ?>
                    <tr height="50">
                      <td colspan="4" align="left" valign="bottom">
                        <span style="color:red">
                        5Lより大きいサイズをご要望の方は「特寸」サイズを選択し<br>
                        次の画面でヌード寸法を入力して下さい。
                        </span>
                      </td>
                    </tr>
                  </table>
                  </div>
<?php if(!$haveRirekiFlg) { ?>
                  
                  <div class="bot" align="center"><a href="#" onclick="document.registForm.action='../select_staff.php'; document.registForm.submit(); return false;"><img src="../img/modoru.gif" alt="戻る" width="112" height="32" border="0"></a> &nbsp;&nbsp;&nbsp;&nbsp; <a href="#" onclick="document.registForm.submit(); return false;"><img src="../img/tsugihe.gif" alt="次へ" width="112" height="32" border="0"></a></div>
                  
<?php } ?>
<?php if($haveRirekiFlg) { ?>
                  
                  <table width="700" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                      <td width="500">
                        <div class="bot" align="center"><a href="#" onclick="document.registForm.action='../rireki/rireki.php'; document.registForm.submit(); return false;"><img src="../img/modoru.gif" alt="戻る" width="112" height="32" border="0"></a> &nbsp;&nbsp;&nbsp;&nbsp; <a href="#" onclick="document.registForm.submit(); return false;"><img src="../img/tsugihe.gif" alt="次へ" width="112" height="32" border="0"></a></div>
                      </td>
                      <td width="200">
                        <div class="bot" align="center">
                          <span style="color:red">発注をキャンセルする場合は<br>キャンセルボタンを押して下さい</span><br>
                          <a href="#" onclick="confirmCancel();"><img src="../img/cancel.gif" alt="キャンセル" width="112" height="32" border="4" style="border-color:red"></a>
                        </div>
                      </td>
                    </tr>
                  </table>
                  

                  <input type="hidden" name="rirekiFlg" value="1">
                  <input type="hidden" name="cancelMode" value="1">
                  <input type="hidden" name="isSelectedAdmin" value="1">
                  <input type="hidden" name="orderId" value="<?php isset($orderId) ? print($orderId) : print('&#123;orderId&#125;'); ?>">
<?php if($isMotoTok) { ?>
                  <input type="hidden" name="motoTokFlg" value="1">
<?php } ?>
<?php } ?>
                  <input type="hidden" name="encodeHint" value="京">
                  <input type="hidden" name="requestNo" value="<?php isset($requestNo) ? print($requestNo) : print('&#123;requestNo&#125;'); ?>">
                  <input type="hidden" name="staffId" value="<?php isset($staffId) ? print($staffId) : print('&#123;staffId&#125;'); ?>">
                  <input type="hidden" name="appliReason" value="<?php isset($appliReason) ? print($appliReason) : print('&#123;appliReason&#125;'); ?>">
                  <input type="hidden" name="searchPatternId" value="<?php isset($searchPatternId) ? print($searchPatternId) : print('&#123;searchPatternId&#125;'); ?>">
                  <input type="hidden" name="searchFlg" value="1">
<?php for ($i1_hiddenHtml=0; $i1_hiddenHtml<count($hiddenHtml); $i1_hiddenHtml++) { ?>
        <input type="hidden" value="<?php isset($hiddenHtml[$i1_hiddenHtml]['value']) ? print($hiddenHtml[$i1_hiddenHtml]['value']) : print('&#123;hiddenHtml.value&#125;'); ?>" name="<?php isset($hiddenHtml[$i1_hiddenHtml]['name']) ? print($hiddenHtml[$i1_hiddenHtml]['name']) : print('&#123;hiddenHtml.name&#125;'); ?>">
<?php } ?>
                </form>
              </td>
            </tr>
          </table>
          

        </div>
        <br><br><br>
      </div>
    </div>
  </body>
</html>

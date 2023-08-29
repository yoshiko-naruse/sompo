<?php
/*
 * 返却申請入力画面
 * henpin_shinsei.src.php
 *
 * create 2007/03/22 H.Osugi
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
$isMenuReturn = true;	// 返却のメニューをアクティブに

// 変数の初期化 ここから ******************************************************
$searchCompCd = '';
$searchCompName = '';
$searchCompId = '';
$searchStaffCd = '';
$searchPersonName = '';

$requestNo = '';					// 申請番号
$staffCode = '';					// スタッフコード
$memo      = '';					// メモ
$rentalEndDay = '';					// レンタル終了日

$selectedReason1 = false;			// 返却理由（退職・異動返却）
$selectedReason2 = false;			// 返却理由（その他返却）

// 変数の初期化 ここまで ******************************************************

// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST); 

// スタッフIDが取得できなければエラーに
if (!isset($post['staffId']) || $post['staffId'] == '') {
    // TOP画面に強制遷移
	$returnUrl             = HOME_URL . 'top.php';
	redirectPost($returnUrl, $hiddens);
}

$staffId = trim($post['staffId']);		// StaffID

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


// 画面表示用データ取得
$headerData = getHeaderData($dbConnect, $staffId);

// 店舗ID
$compId     = $headerData['CompID'];

// 店舗コード
$compCd     = $headerData['CompCd'];

// 店舗名
$compName   = $headerData['CompName'];

// スタッフコード
$staffCode  = $headerData['StaffCode'];

// 着用者名
$personName = $headerData['PersonName'];

// 返却可能は商品一覧を表示
$items = getStaffOrder($dbConnect, $staffId, $compId, $post);

// 返却可能商品が０件の場合
if (count($items) <= 0) {

	$hiddens['errorName'] = 'henpinShinsei';
	$hiddens['menuName']  = 'isMenuReturn';
	$hiddens['returnUrl'] = 'select_staff.php';
	$hiddens['errorId'][] = '901';
	$errorUrl             = HOME_URL . 'error.php';

	$hiddens['appliReason'] = trim($post['appliReason']);

	redirectPost($errorUrl, $hiddens);

}

// 表示する値の成型 ここから +++++++++++++++++++++++++++++++++++++++++++++++++++
// 初期表示の場合
if (!isset($post['henpinShinseiFlg']) || $post['henpinShinseiFlg'] != '1') {

	// 申請番号を生成
	$requestNo = createRequestNo($dbConnect, $compId, 2);

	// 申請番号の生成に失敗した場合はエラー
	if ($requestNo == false) {
		// エラー処理を行う
	}

}
// POST情報を引き継ぐ場合
else {

	// スタッフコード
	$staffCode = trim($post['staffCode']);

	// 申請番号を生成
	$requestNo = trim($post['requestNo']);

	// レンタル終了日
	$rentalEndDay = trim($post['rentalEndDay']);

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

$appliReason = trim($post['appliReason']);		// 返却理由

// 返却理由
switch (trim($appliReason)) {

	// 返却理由（退職・異動返却）
	case APPLI_REASON_RETURN_RETIRE:
		$selectedReason1 = true;
		break;

	// 返却理由（退職・異動返却）
	case APPLI_REASON_RETURN_OTHER:
		$selectedReason2 = true;
		break;

	default:
		break;
}

// 表示する値の成型 ここまで +++++++++++++++++++++++++++++++++++++++++++++++++++

/*
 * 返却可能商品一覧情報を取得する
 * 引数  ：$dbConnect    => コネクションハンドラ
 *       ：$staffId      => StaffID
 *       ：$compId       => 店舗ID
 *       ：$post         => POST値
 * 戻り値：$result       => 返却可能商品一覧情報
 *
 * create 2007/03/22 H.Osugi
 *
 */
function getStaffOrder($dbConnect, $staffId, $compId, $post) {

	// 返却可能商品の一覧を取得する
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
	$sql .= 	" ts.StaffID = '" . db_Escape($staffId) . "'";
	$sql .= " AND";
	$sql .= 	" ts.CompID = '" . db_Escape($compId) . "'";
	$sql .= " AND";
	$sql .= 	" tsd.ReturnFlag = 0";
//	$sql .= " AND";
//	$sql .= 	" tsd.ReturnDetID IS NULL";
	$sql .= " AND";
	$sql .= 	" tsd.Status IN ( " . STATUS_SHIP . ", ". STATUS_DELIVERY  . ")";		// ステータスが出荷済(15),納品済(16)
	$sql .= " AND";
	$sql .= 	" tsd.Del = " . DELETE_OFF;
	$sql .= " ORDER BY";
	$sql .= 	" mi.ItemID ASC";

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

		$result[$i]['Num']        = $i;

		// バーコードが空かどうか判定
		$result[$i]['isEmptyBarCd'] = false;
		if ($result[$i]['BarCd'] == '') {
			$result[$i]['isEmptyBarCd'] = true;
		}

		// ラジオボックスが選択されているか判定
		$result[$i]['isReturnChecked'] = false;
		$result[$i]['isLostChecked']   = false;

		// POST値が送信されてきた場合
		if (is_array($post['orderDetIds'])) {
			// 返却がチェックされている場合
			if (isset($post['returnChk'][$result[$i]['OrderDetID']]) && $post['returnChk'][$result[$i]['OrderDetID']] == 1) {
				$result[$i]['isReturnChecked'] = true;
			}
			// 紛失がチェックされている場合
			elseif (isset($post['lostChk'][$result[$i]['OrderDetID']]) && $post['lostChk'][$result[$i]['OrderDetID']] == 1) {
				$result[$i]['isLostChecked'] = true;
			}

			// 汚損・破損がチェックされている場合
			if (isset($post['brokenChk'][$result[$i]['OrderDetID']]) && $post['brokenChk'][$result[$i]['OrderDetID']] == 1) {
				$result[$i]['isBrokenChecked'] = true;
			}

		}
		// 退職・異動返却の場合、初期設定は全ての商品で返却をチェックする
		else {
			if ($post['appliReason'] == APPLI_REASON_RETURN_RETIRE) {
				$result[$i]['isReturnChecked'] = true;
			}
		}
	}

	return  $result;

}

?><!DOCTYPE html>
<html>
  <head>
    <META http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <script src="//code.jquery.com/jquery-1.12.4.min.js"></script>
    <link href="/main.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="//code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />
    <script src="//code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
    <script language="JavaScript">
    <!--
    function retn_chkbox(i) {
      if (document.registForm.elements['returnChk[' + i + ']'].checked) {
        document.registForm.elements['lostChk[' + i + ']'].checked = false;
      }
      if (document.registForm.elements['returnChk[' + i + ']'].checked == false && document.registForm.elements['lostChk[' + i + ']'].checked == false) {
        document.registForm.elements['brokenChk[' + i + ']'].checked = false;
      }
    }
    function lost_chkbox(i) {
      if (document.registForm.elements['lostChk[' + i + ']'].checked) {
        document.registForm.elements['returnChk[' + i + ']'].checked = false;
        document.registForm.elements['brokenChk[' + i + ']'].checked = false;
      }
    }
    function broken_chkbox(i) {
      if (document.registForm.elements['lostChk[' + i + ']'].checked) {
        document.registForm.elements['brokenChk[' + i + ']'].checked = false;
      }
      if (document.registForm.elements['returnChk[' + i + ']'].checked == false && document.registForm.elements['lostChk[' + i + ']'].checked == false) {
        document.registForm.elements['brokenChk[' + i + ']'].checked = false;
      }
    }
    // -->
    </script>
    <script type="text/javascript">
      $(function() {
        $("#datepicker").datepicker();
        $('#datepicker').datepicker("option", "dateFormat", 'yy/mm/dd' );
        $("#datepicker").datepicker("setDate", "<?php isset($rentalEndDay) ? print($rentalEndDay) : print('&#123;rentalEndDay&#125;'); ?>");
      });
    </script>
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
        

        <form method="post" action="henpin_shinsei_kakunin.php" name="registForm">
          <div id="contents">
            <h1>
<?php if($selectedReason1) { ?>
              ユニフォーム返却申請　（退職・異動返却）
<?php } ?>
<?php if($selectedReason2) { ?>
              
              ユニフォーム返却申請　（その他返却）
              
<?php } ?>
            </h1>
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
                <td width="100" class="line"><span class="fbold">職員コード</span></td>
                <td width="100" class="line"><?php isset($staffCode) ? print($staffCode) : print('&#123;staffCode&#125;'); ?></td>
                <td width="100" class="line"><span class="fbold">職員名</span></td>
                <td width="400" class="line"><?php isset($personName) ? print($personName) : print('&#123;personName&#125;'); ?></td>
              </tr>
              <tr height="30">
                <td class="line"><span class="fbold">返却理由</span></td>
                <td colspan="3" class="line">
<?php if($selectedReason1) { ?>
                  退職・異動返却
<?php } ?>
<?php if($selectedReason2) { ?>
                  
                  その他返却
                  
<?php } ?>
                </td>
              </tr>
              <tr height="30">
                <td class="line"><span class="fbold">メモ</span></td>
                <td colspan="3" class="line"><input name="memo" type="text" value="<?php isset($memo) ? print($memo) : print('&#123;memo&#125;'); ?>" style="width:500px;"></td>
              </tr>
            </table>
            <h3>◆返却するユニフォームをチェックしてください。（紛失した商品は「紛失」にチェック）</h3>
            <table width="670" border="0" class="tb_1" cellpadding="0" cellspacing="3">
              <tr>
                <th align="center" width="50">返却</th>
                <th align="center" width="50">紛失</th>
                <th align="center" width="250">アイテム名</th>
                <th align="center" width="100">サイズ</th>
                <th align="center" width="120">単品番号</th>
                <th align="center" width="100">汚損・破損</th>
              </tr>
<?php for ($i1_items=0; $i1_items<count($items); $i1_items++) { ?>
              <tr height="20">
                <td class="line2" align="center">
<?php if($items[$i1_items]['isReturnChecked']) { ?>
                  <input name="returnChk[<?php isset($items[$i1_items]['OrderDetID']) ? print($items[$i1_items]['OrderDetID']) : print('&#123;items.OrderDetID&#125;'); ?>]" type="checkbox" id="checkbox[]" value="1" onClick="retn_chkbox(<?php isset($items[$i1_items]['OrderDetID']) ? print($items[$i1_items]['OrderDetID']) : print('&#123;items.OrderDetID&#125;'); ?>)" checked="checked">
<?php } ?>
<?php if(!$items[$i1_items]['isReturnChecked']) { ?>
                  
                  <input name="returnChk[<?php isset($items[$i1_items]['OrderDetID']) ? print($items[$i1_items]['OrderDetID']) : print('&#123;items.OrderDetID&#125;'); ?>]" type="checkbox" id="checkbox[]" value="1" onClick="retn_chkbox(<?php isset($items[$i1_items]['OrderDetID']) ? print($items[$i1_items]['OrderDetID']) : print('&#123;items.OrderDetID&#125;'); ?>)">
                  
<?php } ?>
                </td>
                <td class="line2" align="center">
<?php if($items[$i1_items]['isLostChecked']) { ?>
                  
                  <input name="lostChk[<?php isset($items[$i1_items]['OrderDetID']) ? print($items[$i1_items]['OrderDetID']) : print('&#123;items.OrderDetID&#125;'); ?>]" type="checkbox" id="checkbox[]" value="1" onClick="lost_chkbox(<?php isset($items[$i1_items]['OrderDetID']) ? print($items[$i1_items]['OrderDetID']) : print('&#123;items.OrderDetID&#125;'); ?>)" checked="checked">
                  
<?php } ?>
<?php if(!$items[$i1_items]['isLostChecked']) { ?>
                  <input name="lostChk[<?php isset($items[$i1_items]['OrderDetID']) ? print($items[$i1_items]['OrderDetID']) : print('&#123;items.OrderDetID&#125;'); ?>]" type="checkbox" id="checkbox[]" value="1" onClick="lost_chkbox(<?php isset($items[$i1_items]['OrderDetID']) ? print($items[$i1_items]['OrderDetID']) : print('&#123;items.OrderDetID&#125;'); ?>)">
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
                  <input type="hidden" name="orderDetIds[]" value="<?php isset($items[$i1_items]['OrderDetID']) ? print($items[$i1_items]['OrderDetID']) : print('&#123;items.OrderDetID&#125;'); ?>">
                </td>
                <td class="line2" align="center">
<?php if($items[$i1_items]['isBrokenChecked']) { ?>
                  
                  <input name="brokenChk[<?php isset($items[$i1_items]['OrderDetID']) ? print($items[$i1_items]['OrderDetID']) : print('&#123;items.OrderDetID&#125;'); ?>]" type="checkbox" id="checkbox[]" value="1" onClick="broken_chkbox(<?php isset($items[$i1_items]['OrderDetID']) ? print($items[$i1_items]['OrderDetID']) : print('&#123;items.OrderDetID&#125;'); ?>)" checked="checked">
                  
<?php } ?>
<?php if(!$items[$i1_items]['isBrokenChecked']) { ?>
                  <input name="brokenChk[<?php isset($items[$i1_items]['OrderDetID']) ? print($items[$i1_items]['OrderDetID']) : print('&#123;items.OrderDetID&#125;'); ?>]" type="checkbox" id="checkbox[]" value="1" onClick="broken_chkbox(<?php isset($items[$i1_items]['OrderDetID']) ? print($items[$i1_items]['OrderDetID']) : print('&#123;items.OrderDetID&#125;'); ?>)">
<?php } ?>
                </td>
              </tr>
<?php } ?>
<?php if($selectedReason1) { ?>
              <tr height="15">
                <td colspan="6"><span style="color:red">※必ず「返却」「紛失」のどちらかをチェックして下さい。</span></td>
              </tr>
<?php } ?>
              <tr height="15">
                <td colspan="6"><span style="color:red">※返却する商品に汚損および破損がある場合は、「汚損・破損」にチェックをして下さい。</span></td>
              </tr>
            </table>
            
            <div class="bot"><a href="#" onclick="document.registForm.action='../select_staff.php'; document.registForm.submit(); return false;"><img src="../img/modoru.gif" alt="戻る" width="112" height="32" border="0"></a> &nbsp;&nbsp;&nbsp;&nbsp; <a href="#" onclick="document.registForm.submit(); return false;"><img src="../img/tsugihe.gif" alt="次へ" width="112" height="32" border="0"></a></div>
            
          </div>
          <input type="hidden" name="appliReason" value="<?php isset($appliReason) ? print($appliReason) : print('&#123;appliReason&#125;'); ?>">
          <input type="hidden" name="requestNo" value="<?php isset($requestNo) ? print($requestNo) : print('&#123;requestNo&#125;'); ?>">
          <input type="hidden" name="staffId" value="<?php isset($staffId) ? print($staffId) : print('&#123;staffId&#125;'); ?>">
          <input type="hidden" name="staffCode" value="<?php isset($staffCode) ? print($staffCode) : print('&#123;staffCode&#125;'); ?>">
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
  </body>
</html>

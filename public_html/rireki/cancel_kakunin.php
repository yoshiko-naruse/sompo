<?php
/*
 * キャンセル確認画面
 * chancel_kakunin.src.php
 *
 * create 2007/03/28 H.Osugi
 *
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


/* ../../include/checkReturn.php start */

/*
 * 返却できるユニフォームか判定
 * checkReturn.php
 *
 * create 2007/03/22 H.Osugi
 *
 */

/*
 * 返却できないユニフォームが存在しないかを判定する
 * 引数  ：$dbConnect   => コネクションハンドラ
 *       ：$orderDetIds => 検証したいOrderDetID(array)
 *       ：$returnUrl  => 戻り先URL
 * 戻り値：なし
 */
function checkReturn($dbConnect, $orderDetIds, $returnUrl) {

	// 選択されたorderDetID
	$orderDetId = '';
	if(is_array($orderDetIds)) {
		foreach ($orderDetIds as $key => $value) {
			if (!(int)$value) {
				// エラー画面で必要な値のセット
				$hiddens = array();
				$hiddens['errorName'] = 'henpinShinsei';
				$hiddens['menuName']  = 'isMenuReturn';
				$hiddens['returnUrl'] = $returnUrl;
				$hiddens['errorId'][] = '903';
		
				redirectPost(HOME_URL . 'error.php', $hiddens);
			}
		}
		$orderDetId = implode(', ', $orderDetIds);
	}

	// 返却できないユニフォームが存在しないかを判定する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" count(*) as count_staffdet";
	$sql .= " FROM";
	$sql .= 	" T_Staff_Details";
	$sql .= " WHERE";
	$sql .= 	" OrderDetID IN (" . db_Escape($orderDetId) . ")";
	$sql .= " AND";
	$sql .= 	" Status <> " . STATUS_SHIP;				// 出荷済
	$sql .= " AND";
	$sql .= 	" Status <> " . STATUS_DELIVERY;			// 納品済
	$sql .= " AND";
	$sql .= 	" Status <> " . STATUS_RETURN_NOT_APPLY;	// 返却未申請
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;

	$result = db_Read($dbConnect, $sql);

	// 返却できないユニフォームが存在する場合
	if (!isset($result[0]['count_staffdet']) || $result[0]['count_staffdet'] > 0) {

		// エラー画面で必要な値のセット
		$hiddens = array();
		$hiddens['errorName'] = 'henpinShinsei';
		$hiddens['menuName']  = 'isMenuReturn';
		$hiddens['returnUrl'] = $returnUrl;
		$hiddens['errorId'][] = '903';

		redirectPost(HOME_URL . 'error.php', $hiddens);

	}
}


/* ../../include/checkReturn.php end */


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
$isMenuHistory = true;	// 申請履歴のメニューをアクティブに

// 変数の初期化 ここから *******************************************************
$requestNo  = '';					// 申請番号
$requestDay = '';					// 申請日
$compName   = '';					// 店舗名 
$compCd     = '';					// 店舗コード 
$staffCode  = '';					// スタッフコード
$zip1       = '';					// 郵便番号（前半3桁）
$zip2       = '';					// 郵便番号（後半4桁）
$address    = '';					// 住所
$shipName   = '';					// 出荷先名
$staffName  = '';					// ご担当者
$tel        = '';					// 電話番号
$memo       = '';					// メモ
$rentalStartDay = '';				// レンタル開始日
$rentalEndDay = '';					// レンタル終了日

$selectedReason1  = false;			// サイズ交換
$selectedReason2  = false;			// 汚損・破損交換
$selectedReason3  = false;			// 紛失交換
$selectedReason4  = false;          // 不良品交換
$selectedReason5  = false;          // 初回サイズ交換
$selectedReason11 = false;			// 退職・異動返却
$selectedReason12 = false;			// その他返却

$isEmptyMemo = true;				// メモが空かどうかを判定するフラグ
$isEmptyRentalStartDay = true;		// レンタル開始日が空かどうかを判定するフラグ
$isEmptyRentalEndDay = true;		// レンタル終了日が空かどうかを判定するフラグ

$haveTok     = false;				// 特寸があるかどうかを判定するフラグ

$isReturn    = false;				// 返却か交換かを判定するフラグ
$isOrder     = false;				// 発注かどうかを判定するフラグ

$dispTwoPane = false;               // 画面を二段構成にするかどうかを判定するフラグ
// 変数の初期化 ここまで ******************************************************

// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST);

// 表示する値の成型 ここから +++++++++++++++++++++++++++++++++++++++++++++++++++
// OrderID
$orderId = trim($post['orderId']);
$orderReturnId = '';

// 申請情報の取得
$orderData = getOrderData($dbConnect, $orderId);

$headerData = getHeaderData($dbConnect, $orderData['StaffID']);

// 申請情報をHTMLエンティティ
$orderData = castHtmlEntity($orderData); 

// 申請番号
$requestNo = $orderData['AppliNo'];

switch ($orderData['AppliMode']) {

    case APPLI_MODE_RETURN:       // 返却の場合

        // 表示する商品詳細情報取得
        $items = getStaffOrderDetailReturns($dbConnect, $orderId);

        // 返却かどうかを判定するフラグ
        $isReturn   = true;
        break;

    case APPLI_MODE_EXCHANGE:     // 交換の場合

        // 申請番号
        $requestNo = substr($requestNo, 1);
        $displayRequestNo = 'A' . $requestNo;
    
        // OrderIDの取得
        $orderId       = getOrderId($dbConnect, 'A' . $requestNo);
        $orderReturnId = getOrderId($dbConnect, 'R' . $requestNo);

        // 交換する商品が同一ではない場合
        if ($orderData['AppliReason'] == APPLI_REASON_EXCHANGE_CHANGEGRADE || $orderData['AppliReason'] == APPLI_REASON_EXCHANGE_MATERNITY) { 
            
            // 返却する商品詳細情報を取得
            $returnItems = getStaffOrderDetailExchangesTwoPane($dbConnect, $orderReturnId);

            // 発注する商品詳細情報を取得
            $orderItems  = getStaffOrderDetailExchangesTwoPane($dbConnect, $orderId);

            $dispTwoPane = true;        // 画面を二段構成に。
        } else {
        
            // 表示する商品詳細情報取得
            $items = getStaffOrderDetailExchanges($dbConnect, $orderId, $orderReturnId);

        }
        break;

    case APPLI_MODE_ORDER:        // 発注の場合

        // 個別申請の場合
        $items =getStaffOrderDetailPersonal($dbConnect, $orderId);
        $resonIsPersonal = true;
    
        // 発注かどうかを判定するフラグ
        $isOrder   = true;
        break;

    default:
        break;

}

// 表示する情報が取得できなければエラー
$lostItemData = false;
if ($dispTwoPane) { 
    if (count($returnItems) <= 0 || count($orderItems) <= 0) {
        $lostItemData = true;
    }
} else {
    if (count($items) <= 0) {
        $lostItemData = true;
    }
}
if ($lostItemData)  {
    
    $hiddens['errorName'] = 'cancel';
    $hiddens['menuName']  = 'isMenuHistory';
    $hiddens['returnUrl'] = 'rireki/rireki.php';
    $hiddens['errorId'][] = '902';
    $errorUrl             = HOME_URL . 'error.php';
    
    redirectPost($errorUrl, $hiddens);
}


// 特寸があるかどうか
if ($orderData['Tok'] == '1') {

	$tokData = getTokData($dbConnect, $orderId);

	// 特寸情報をHTMLエンティティ
	$tokData = castHtmlEntity($tokData); 

	// 特寸情報が存在するかの判定フラグ
	$haveTok = true;

	// 身長
	$high     = $tokData['Height'];

	// 体重
	$weight   = $tokData['Weight'];

	// バスト
	$bust     = $tokData['Bust'];

	// ウエスト
	$waist    = $tokData['Waist'];

	// ヒップ
	$hips     = $tokData['Hips'];

	// 肩幅
	$shoulder = $tokData['Shoulder'];

	// 袖丈
	$sleeve   = $tokData['Sleeve'];

	// スカート丈
	$length   = $tokData['Length'];

    // 着丈
    $kitake   = $tokData['Kitake'];

    // 裄丈
    $yukitake = $tokData['Yukitake'];

    // 股下
    $inseam   = $tokData['Inseam'];

	// 特寸備考
	$tokMemo  = $orderData['TokNote'];


}

// 申請日
$isEmptyRequestDay = false;
$requestDay = '';
if ($orderData['AppliDay'] != '') {
	$requestDay = strtotime($orderData['AppliDay']);
}
else {
	$isEmptyRequestDay = true;
}


// 店舗名
$compName = $orderData['AppliCompName'];

// 店舗コード
$compCd = $orderData['AppliCompCd'];

// スタッフコード
$staffCode = $orderData['StaffCode'];

// スタッフ名
$personName = $headerData['PersonName'];

// 発注・交換の場合
if ($orderData['AppliMode'] == APPLI_MODE_EXCHANGE || $orderData['AppliMode'] == APPLI_MODE_ORDER) {

	// 郵便番号
	list($zip1, $zip2) = explode('-', $orderData['Zip']);
	
	// 住所
	$address = $orderData['Adrr'];
	
	// 出荷先名
	$shipName = $orderData['ShipName'];
	
	// ご担当者
	$staffName = $orderData['TantoName'];
	
	// 電話番号
	$tel = $orderData['Tel'];

}

// レンタル開始日
$rentalStartDay = trim($orderData['RentalStartDay']);

if ($rentalStartDay != '' || $rentalStartDay === 0) {
	$isEmptyRentalStartDay = false;
}

// レンタル終了日
$rentalEndDay = trim($orderData['RentalEndDay']);

if ($rentalEndDay != '' || $rentalEndDay === 0) {
	$isEmptyRentalEndDay = false;
}

// メモ
$memo = trim($orderData['Note']);

if ($memo != '' || $memo === 0) {
	$isEmptyMemo = false;
}

// 返却理由
switch ($orderData['AppliReason']) {

	// 返却理由（退職・異動返却）
	case APPLI_REASON_RETURN_RETIRE:
		$selectedReason11 = true;
		break;

	// 返却理由（その他返却）
	case APPLI_REASON_RETURN_OTHER:
		$selectedReason12 = true;
		break;

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

$countSearchStatus = count($post['searchStatus']);
for ($i=0; $i<$countSearchStatus; $i++) {
	$post['searchStatus[' . $i . ']'] = $post['searchStatus'][$i];
}
$notArrowKeys = array('searchStatus', 'requestNo', 'orderId');
$hiddenHtml = castHidden($post, $notArrowKeys);
// 表示する値の成型 ここまで ++++++++++++++++++++++++++++++++++++++++++++++++++

/*
 * 表示する商品一覧情報（交換）を取得する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$requestNo      => 申請番号
 * 戻り値：$result         => 表示する商品一覧情報
 *
 * create 2007/03/28 H.Osugi
 *
 */
function getStaffOrderDetailExchanges($dbConnect, $orderId, $orderReturnId) {

	// 表示する商品の一覧を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" DISTINCT";
	$sql .= 	" tod.OrderDetID,";
	$sql .= 	" tod.ItemName,";
	$sql .= 	" tod.BarCd,";
	$sql .= 	" tod.Size,";
	$sql .= 	" tod.Status,";
	$sql .= 	" tod.AppliLNo,";
	$sql .= 	" tod2.Size as selectedSize";
	$sql .= " FROM";
	$sql .= 	" T_Order tor";
	$sql .= " INNER JOIN";
	$sql .= 	" T_Order_Details tod";
	$sql .= " ON";
	$sql .= 	" tor.OrderID = tod.OrderID";
	$sql .= " AND";
	$sql .= 	" tod.OrderID = '" . db_Escape($orderReturnId) . "'";
	$sql .= " AND";
	$sql .= 	" tod.Del = " . DELETE_OFF;
	$sql .= " INNER JOIN";
	$sql .= 	" T_Order_Details tod2";
	$sql .= " ON";
	$sql .= 	" tod2.OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" SUBSTRING(tod.AppliNo, 2, 12) = SUBSTRING(tod2.AppliNo, 2, 12)";
	$sql .= " AND";
	$sql .= 	" tod.ItemID = tod2.ItemID";
	$sql .= " AND";
	$sql .= 	" tod2.Del = " . DELETE_OFF;
	$sql .= " INNER JOIN";
	$sql .= 	" M_Item mi";
	$sql .= " ON";
	$sql .= 	" tod.ItemID = mi.ItemID";
	$sql .= " AND";
	$sql .= 	" mi.Del = " . DELETE_OFF;
	$sql .= " WHERE";
	$sql .= 	" tor.Del = " . DELETE_OFF;
	$sql .= " ORDER BY";
	$sql .= 	" tod.AppliLNo ASC";

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	// 取得した値をHTMLエンティティ
	$resultCount = count($result);
	for ($i=0; $i<$resultCount; $i++) {
		$result[$i]['ItemName']     = castHtmlEntity($result[$i]['ItemName']);
		$result[$i]['BarCd']        = castHtmlEntity($result[$i]['BarCd']);
		$result[$i]['Size']         = castHtmlEntity($result[$i]['Size']);
		$result[$i]['selectedSize'] = castHtmlEntity($result[$i]['selectedSize']);

		// バーコードが空かどうか判定
		$result[$i]['isEmptyBarCd'] = false;
		if ($result[$i]['BarCd'] == '') {
			$result[$i]['isEmptyBarCd'] = true;
		}

	}

	return  $result;

}

/*
 * 表示する商品一覧情報（交換:二画面構成）を取得する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$orderId      => 申請番号
 * 戻り値：$result         => 表示する商品一覧情報
 *
 * create 2008/05/01 W.Takasaki
 *
 */
function getStaffOrderDetailExchangesTwoPane($dbConnect, $orderId) {

    // 表示する商品の一覧を取得する
    $sql  = "";
    $sql .= " SELECT";
    $sql .=     " DISTINCT";
    $sql .=     " tod.OrderDetID,";
    $sql .=     " tod.ItemName,";
    $sql .=     " tod.BarCd,";
    $sql .=     " tod.Size,";
    $sql .=     " tod.Status,";
    $sql .=     " tod.AppliLNo";
    $sql .= " FROM";
    $sql .=     " T_Order_Details tod";
    $sql .= " INNER JOIN";
    $sql .=     " M_Item mi";
    $sql .= " ON";
    $sql .=     " tod.ItemID = mi.ItemID";
    $sql .= " AND";
    $sql .=     " tod.OrderID = '" . db_Escape($orderId) . "'";
    $sql .= " AND";
    $sql .=     " mi.Del = " . DELETE_OFF;
    $sql .= " AND";
    $sql .=     " tod.Del = " . DELETE_OFF;
    $sql .= " ORDER BY";
    $sql .=     " tod.AppliLNo ASC";

    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合
    if (count($result) <= 0) {
        $result = array();
        return $result;
    }

    // 取得した値をHTMLエンティティ
    $resultCount = count($result);
    for ($i=0; $i<$resultCount; $i++) {
        $result[$i]['ItemName']     = castHtmlEntity($result[$i]['ItemName']);
        $result[$i]['BarCd']        = castHtmlEntity($result[$i]['BarCd']);
        $result[$i]['Size']         = castHtmlEntity($result[$i]['Size']);

        // バーコードが空かどうか判定
        $result[$i]['isEmptyBarCd'] = false;
        if ($result[$i]['BarCd'] == '') {
            $result[$i]['isEmptyBarCd'] = true;
        }

    }

    return  $result;

}
/*
 * 表示する商品一覧情報（返却）を取得する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$orderId        => 申請番号
 * 戻り値：$result         => 表示する商品一覧情報
 *
 * create 2007/03/28 H.Osugi
 *
 */
function getStaffOrderDetailReturns($dbConnect, $orderId) {

	// 表示する商品の一覧を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" tod.ItemName,";
	$sql .= 	" tod.BarCd,";
	$sql .= 	" tod.Size,";
	$sql .= 	" tod.Status,";
	$sql .= 	" tod.DamageCheck";
	$sql .= " FROM";
	$sql .= 	" T_Order tor";
	$sql .= " INNER JOIN";
	$sql .= 	" T_Order_Details tod";
	$sql .= " ON";
	$sql .= 	" tor.OrderID = tod.OrderID";
	$sql .= " AND";
	$sql .= 	" tod.Del = " . DELETE_OFF;
	$sql .= " AND";
	$sql .= 	" tod.OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " INNER JOIN";
	$sql .= 	" M_Item mi";
	$sql .= " ON";
	$sql .= 	" tod.ItemID = mi.ItemID";
	$sql .= " AND";
	$sql .= 	" mi.Del = " . DELETE_OFF;
	$sql .= " WHERE";
	$sql .= 	" tod.Status <> " . STATUS_RETURN_NOT_APPLY;	// 返却未申請（25）ははぶく
	$sql .= " AND";
	$sql .= 	" tor.Del = " . DELETE_OFF;
	$sql .= " ORDER BY";
	$sql .= 	" tod.OrderDetID ASC";

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	// 取得した値をHTMLエンティティ
	$resultCount = count($result);
	for ($i=0; $i<$resultCount; $i++) {
		$result[$i]['ItemName']   = castHtmlEntity($result[$i]['ItemName']);
		$result[$i]['BarCd']      = castHtmlEntity($result[$i]['BarCd']);
		$result[$i]['Size']       = castHtmlEntity($result[$i]['Size']);

		// バーコードが空かどうか判定
		$result[$i]['isEmptyBarCd'] = false;
		if ($result[$i]['BarCd'] == '') {
			$result[$i]['isEmptyBarCd'] = true;
		}

		// 返却・紛失のどちらか判定
		switch ($result[$i]['Status']) {

			case STATUS_NOT_RETURN:			// 返却承認待の場合
			case STATUS_NOT_RETURN_ADMIT:	// 未返却の場合
			case STATUS_NOT_RETURN_ORDER:	// 未返却の場合
			case STATUS_NOT_RETURN_DENY:	// 返却否認の場合
				$result[$i]['isCheckedReturn'] = true;
				break;
			case STATUS_LOSS:				// 紛失承認待の場合
			case STATUS_LOSS_ADMIT:			// 紛失の場合
			case STATUS_LOSS_ORDER:			// 紛失の場合
			case STATUS_LOSS_DENY:			// 紛失否認の場合
				$result[$i]['isCheckedReturn'] = false;
				break;
			default:
				break;
			
		}

		// 汚損・破損が選択されたか判定
		$result[$i]['isCheckedBroken'] = false;
		if (isset($result[$i]['DamageCheck']) && $result[$i]['DamageCheck'] == 1) {
			$result[$i]['isCheckedBroken'] = true;
		}

	}

	return  $result;

}

/*
 * 申請情報を取得する
 * 引数  ：$dbConnect    => コネクションハンドラ
 *       ：$orderId      => OrderID
 * 戻り値：$result       => 申請情報
 *
 * create 2007/03/28 H.Osugi
 *
 */
function getOrderData($dbConnect, $orderId) {

	// 初期化
	$requestDay = '';

	// 表示する申請情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" AppliNo,";
	$sql .= 	" AppliDay,";
	$sql .= 	" AppliCompCd,";
	$sql .= 	" AppliCompName,";
	$sql .= 	" AppliReason,";
    $sql .=     " StaffID,";
	$sql .= 	" StaffCode,";
	$sql .= 	" AppliMode,";
	$sql .= 	" AppliReason,";
	$sql .= 	" Zip,";
	$sql .= 	" Adrr,";
	$sql .= 	" Tel,";
	$sql .= 	" ShipName,";
	$sql .= 	" TantoName,";
	$sql .= 	" Tok,";
	$sql .= 	" Note,";
	$sql .= 	" TokNote,";
	$sql .= 	" CONVERT(varchar,RentalStartDay,111) AS RentalStartDay,";
	$sql .= 	" CONVERT(varchar,RentalEndDay,111) AS RentalEndDay";
	$sql .= " FROM";
	$sql .= 	" T_Order";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;

	$result = db_Read($dbConnect, $sql);

	// 情報が取得できなかった場合
	if (!is_array($result) || count($result) <= 0) {
	 	return false;
	}

	return $result[0];

}

/*
 * OrderIDを取得する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$requestNo => 申請番号
 * 戻り値：$orderId   => OrderID
 *
 * create 2007/04/02 H.Osugi
 *
 */
function getOrderId($dbConnect, $requestNo) {

	// OrderIDを取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" OrderID";
	$sql .= " FROM";
	$sql .= 	" T_Order";
	$sql .= " WHERE";
	$sql .= 	" AppliNo = '" . db_Escape($requestNo) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;

	$orderDatas = db_Read($dbConnect, $sql);

	// 実行結果が失敗の場合
	if (!isset($orderDatas[0]['OrderID']) || $orderDatas[0]['OrderID'] == '') {
		return false;
	}

	return $orderDatas[0]['OrderID'];

}

/*
 * 申請情報を取得する
 * 引数  ：$dbConnect    => コネクションハンドラ
 *       ：$orderId      => OrderID
 * 戻り値：$result       => 申請情報
 *
 * create 2007/04/23 H.Osugi
 *
 */
function getTokData($dbConnect, $orderId) {

	// 表示する申請情報を取得する
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

	$result = db_Read($dbConnect, $sql);

	// 情報が取得できなかった場合
	if (!is_array($result) || count($result) <= 0) {
	 	return false;
	}

	return $result[0];

}

/*
 * 表示する商品一覧情報（個別申請）を取得する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$orderId        => 申請番号
 * 戻り値：$result         => 表示する商品一覧情報
 *
 * create 2007/05/23 H.Osugi
 *
 */
function getStaffOrderDetailPersonal($dbConnect, $orderId) {

	// 変更する発注申請詳細情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" COUNT(tod.ItemNo) as itemNumber,";
	$sql .= 	" mi.ItemID,";
	$sql .= 	" mi.ItemName,";
	$sql .= 	" tod.Size,";
	$sql .= 	" mi.SizeID";
	$sql .= " FROM";
	$sql .= 	" T_Order_Details tod";
	$sql .= " INNER JOIN";
	$sql .= 	" M_Item mi";
	$sql .= " ON";
	$sql .= 	" tod.ItemID = mi.ItemID";
	$sql .= " AND";
	$sql .= 	" mi.Del = " . DELETE_OFF;
	$sql .= " WHERE";
	$sql .= 	" tod.OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" tod.Del = " . DELETE_OFF;
	$sql .= " GROUP BY";
	$sql .= 	" mi.ItemID,";
	$sql .= 	" mi.ItemName,";
	$sql .= 	" tod.Size,";
	$sql .= 	" mi.SizeID";
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
		$result[$i]['ItemName']     = castHtmlEntity($result[$i]['ItemName']);
		$result[$i]['itemNumber']   = $result[$i]['itemNumber'];
		$result[$i]['selectedSize'] = castHtmlEntity($result[$i]['Size']);

	}

	return  $result;

}

?><!DOCTYPE html>
<html>
  <head>
    <META http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <script src="//code.jquery.com/jquery-1.12.4.min.js"></script>
    <link href="/main.css" rel="stylesheet" type="text/css">
    <title>制服管理システム</title>
    <script language="JavaScript">
    <!--
    function confirmCancel() {

      if (confirm('キャンセルしてもよろしいですか')) {
        document.confForm.submit();
      }

      return false;

    }
    // -->
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

<?php if($isReturn) { ?>
          <h1>ユニフォーム返却確認</h1>
          <table border="0" width="700" cellpadding="0" cellspacing="0" class="tb_1">
            <tr height="30">
              <td width="100" class="line"><span class="fbold">申請番号</span></td>
              <td colspan="3" class="line"><?php isset($requestNo) ? print($requestNo) : print('&#123;requestNo&#125;'); ?></td>
            </tr>
            <tr height="30">
              <td width="100" class="line"><span class="fbold">施設名</span></td>
              <td colspan="3" class="line"><?php isset($compCd) ? print($compCd) : print('&#123;compCd&#125;'); ?>：<?php isset($compName) ? print($compName) : print('&#123;compName&#125;'); ?></td>
            </tr>
            <tr height="30">
              <td width="100" class="line"><span class="fbold">職員コード</span></td>
              <td width="100" class="line"><?php isset($staffCode) ? print($staffCode) : print('&#123;staffCode&#125;'); ?></td>
              <td width="100" class="line">
                <span class="fbold">職員氏名</span>
                &nbsp;
              </td>
              <td width="400" class="line">
                <?php isset($personName) ? print($personName) : print('&#123;personName&#125;'); ?>
                &nbsp;
              </td>
            </tr>
            <tr height="30">
              <td class="line"><span class="fbold">返却理由</span></td>
              <td colspan="3" class="line">
<?php if($selectedReason11) { ?>
                
                退職・異動返却
                
<?php } ?>
<?php if($selectedReason12) { ?>
                
                その他返却
                
<?php } ?>
              </td>
            </tr>
            <tr height="30">
              <td class="line"><span class="fbold">メモ</span></td>
              <td colspan="3"  class="line">
<?php if($isEmptyMemo) { ?>
                
                &nbsp;
                
<?php } ?>
<?php if(!$isEmptyMemo) { ?>
                <?php isset($memo) ? print($memo) : print('&#123;memo&#125;'); ?>
<?php } ?>
              </td>
            </tr>
          </table>
<?php } ?>
<?php if(!$isReturn) { ?>
          
<?php if($isOrder) { ?>
          <h1>ユニフォーム発注確認</h1>
<?php } ?>
<?php if(!$isOrder) { ?>
          <h1>ユニフォーム交換確認</h1>
<?php } ?>
          <table border="0" width="700" cellpadding="0" cellspacing="0" class="tb_1">
            <tr height="30">
              <td width="100" class="line"><span class="fbold">申請番号</span></td>
<?php if(!$isOrder) { ?>
              <td colspan="3" class="line"><?php isset($displayRequestNo) ? print($displayRequestNo) : print('&#123;displayRequestNo&#125;'); ?></td>
<?php } ?>
<?php if($isOrder) { ?>
              <td colspan="3" class="line"><?php isset($requestNo) ? print($requestNo) : print('&#123;requestNo&#125;'); ?></td>
<?php } ?>
            </tr>
            <tr height="30">
              <td class="line"><span class="fbold">施設名</span></td>
              <td colspan="3" class="line"><?php isset($compCd) ? print($compCd) : print('&#123;compCd&#125;'); ?>：<?php isset($compName) ? print($compName) : print('&#123;compName&#125;'); ?></td>
            </tr>
            <tr height="30">
              <td class="line"><span class="fbold">職員コード</span></td>
              <td  class="line"><?php isset($staffCode) ? print($staffCode) : print('&#123;staffCode&#125;'); ?></td>
              <td width="80" class="line">
                <span class="fbold">職員氏名</span>
                &nbsp;
              </td>
              <td width="400" class="line">
                <?php isset($personName) ? print($personName) : print('&#123;personName&#125;'); ?>
                &nbsp;
              </td>
            </tr>
            <tr height="25">
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
              <td width="482" colspan="2" class="line"><?php isset($tel) ? print($tel) : print('&#123;tel&#125;'); ?></td>
            </tr>
<?php if(!$isOrder) { ?>
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
<?php } ?>
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
          
<?php } ?>
<?php if($isReturn) { ?>
          <h3>◆下記の内容で返却申請されています。キャンセルしますか？</h3>
<?php } ?>
<?php if(!$isReturn) { ?>
<?php if($isOrder) { ?>
          
          <h3>◆下記の内容で発注申請されています。キャンセルしますか？</h3>
          
<?php } ?>
<?php if(!$isOrder) { ?>
          
          <h3>◆下記の内容で交換申請されています。キャンセルしますか？</h3>
          
<?php } ?>
<?php } ?>
          

<?php if($isReturn) { ?>
          <table width="640" border="0" class="tb_1" cellpadding="0" cellspacing="3">
            <tr>
              <th align="center" width="70">選択</th>
              <th align="center" width="250">アイテム名</th>
              <th align="center" width="100">サイズ</th>
              <th align="center" width="120">単品番号</th>
              <th align="center" width="100">汚損・破損</th>
            </tr>
<?php for ($i1_items=0; $i1_items<count($items); $i1_items++) { ?>
<?php if($items[$i1_items]['isCheckedReturn']) { ?>
<?php if($items[$i1_items]['isCheckedBroken']) { ?>
            
            <tr height="20" class="chakuyo_2">
            
<?php } ?>
<?php if(!$items[$i1_items]['isCheckedBroken']) { ?>
            <tr height="20">
<?php } ?>
              <td class="line2" align="center">返却</td>
<?php } ?>
<?php if(!$items[$i1_items]['isCheckedReturn']) { ?>
            
            <tr height="20" class="chakuyo_1">
              <td class="line2" align="center"><span style="color:Teal">紛失</span></td>
            
<?php } ?>
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
<?php if($items[$i1_items]['isCheckedBroken']) { ?>
                
                有り
                
<?php } ?>
<?php if(!$items[$i1_items]['isCheckedBroken']) { ?>
                &nbsp;
<?php } ?>
              </td>
            </tr>
<?php } ?>
          </table>
<?php } ?>
<?php if(!$isReturn) { ?>


          

<?php if(!$isOrder) { ?>
<?php if($dispTwoPane) { ?>
          <h2>交換前のアイテム</h2>
          <table width="580" border="0" class="tb_1" cellpadding="0" cellspacing="3">
            <tr>
              <th align="center" width="340">アイテム名</th>
              <th align="center" width="100">単品番号</th>
              <th align="center" width="140">サイズ</th>
            </tr>
<?php for ($i1_returnItems=0; $i1_returnItems<count($returnItems); $i1_returnItems++) { ?>
            <tr height="20">
              <td class="line2" align="left"><?php isset($returnItems[$i1_returnItems]['ItemName']) ? print($returnItems[$i1_returnItems]['ItemName']) : print('&#123;returnItems.ItemName&#125;'); ?></td>
              <td class="line2" align="center">
<?php if($returnItems[$i1_returnItems]['isEmptyBarCd']) { ?>
                  
                  &nbsp;
                  
<?php } ?>
<?php if(!$returnItems[$i1_returnItems]['isEmptyBarCd']) { ?>
                  <?php isset($returnItems[$i1_returnItems]['BarCd']) ? print($returnItems[$i1_returnItems]['BarCd']) : print('&#123;returnItems.BarCd&#125;'); ?>
<?php } ?>
              </td>
              <td class="line2" align="center"><b><?php isset($returnItems[$i1_returnItems]['Size']) ? print($returnItems[$i1_returnItems]['Size']) : print('&#123;returnItems.Size&#125;'); ?></b></td>
            </tr>
<?php } ?>
          </table>
          <h2>交換後のアイテム</h2>
          <table width="580" border="0" class="tb_1" cellpadding="0" cellspacing="3">
            <tr>
              <th align="center" width="340">アイテム名</th>
              <th align="center" width="100">単品番号</th>
              <th align="center" width="140">サイズ</th>
            </tr>
<?php for ($i1_orderItems=0; $i1_orderItems<count($orderItems); $i1_orderItems++) { ?>
            <tr height="20">
              <td class="line2" align="left"><?php isset($orderItems[$i1_orderItems]['ItemName']) ? print($orderItems[$i1_orderItems]['ItemName']) : print('&#123;orderItems.ItemName&#125;'); ?></td>
              <td class="line2" align="center">
<?php if($orderItems[$i1_orderItems]['isEmptyBarCd']) { ?>
                  
                  &nbsp;
                  
<?php } ?>
<?php if(!$orderItems[$i1_orderItems]['isEmptyBarCd']) { ?>
                  <?php isset($orderItems[$i1_orderItems]['BarCd']) ? print($orderItems[$i1_orderItems]['BarCd']) : print('&#123;orderItems.BarCd&#125;'); ?>
<?php } ?>
              </td>
              <td class="line2" align="center"><b><?php isset($orderItems[$i1_orderItems]['Size']) ? print($orderItems[$i1_orderItems]['Size']) : print('&#123;orderItems.Size&#125;'); ?></b></td>
            </tr>
<?php } ?>
          </table>
<?php } ?>
<?php if(!$dispTwoPane) { ?>
          <table width="580" border="0" class="tb_1" cellpadding="0" cellspacing="3">
            <tr>
              <th align="center" width="240">アイテム名</th>
              <th align="center" width="100">現在のサイズ</th>
              <th align="center" width="100">単品番号</th>
              <th align="center" width="140">交換後のサイズ</th>
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
            </tr>
<?php } ?>
          </table>
<?php } ?>
<?php } ?>

          

<?php if($isOrder) { ?>
<?php if(!$resonIsPersonal) { ?>
<?php } ?>
<?php if($resonIsPersonal) { ?>
          <table width="550" border="0" class="tb_1" cellpadding="0" cellspacing="3">
            <tr>
              <th align="center" width="300">アイテム名</th>
              <th align="center" width="150">サイズ</th>
              <th align="center" width="100">着数</th>
            </tr>
<?php for ($i1_items=0; $i1_items<count($items); $i1_items++) { ?>
            <tr height="20">
              <td class="line2" align="left"><?php isset($items[$i1_items]['ItemName']) ? print($items[$i1_items]['ItemName']) : print('&#123;items.ItemName&#125;'); ?></td>
              <td class="line2" align="center"><?php isset($items[$i1_items]['selectedSize']) ? print($items[$i1_items]['selectedSize']) : print('&#123;items.selectedSize&#125;'); ?></td>
              <td class="line2" align="center"><?php isset($items[$i1_items]['itemNumber']) ? print($items[$i1_items]['itemNumber']) : print('&#123;items.itemNumber&#125;'); ?>着</td>
            </tr>
<?php } ?>
          </table>
<?php } ?>
<?php } ?>
<?php } ?>


<?php if($haveTok) { ?>
          
          <br>
          <table width="690" border="0" class="tb_2" cellpadding="0" cellspacing="1">
            <tr>
              <th align="center" width="50" rowspan="5">特注<br>入力</th>
              <th align="center" width="80">身長</th>
              <th align="center" width="80">体重</th>
              <th align="center" width="80">バスト</th>
              <th align="center" width="80">ウエスト</th>
              <th align="center" width="80">ヒップ</th>
              <th align="center" width="80">肩幅</th>
              <th align="center" width="80">袖丈</th>
              <th align="center" width="80">着丈</th>
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
              <th align="center" width="80" colspan="5">&nbsp;</th>
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
          <form action="cancel_kanryo.php" name="confForm" method="post">
            <div class="bot">
              <table width="330" border="0" cellpadding="0" cellspacing="0">
                <tr>
                  <td width="150">
                    &nbsp;
                  </td>
                  <td width="180">
<?php if($isReturn) { ?>
                    <span style="color:red">返却をキャンセルする場合は<br>キャンセルボタンを押して下さい</span><br>
<?php } ?>
<?php if(!$isReturn) { ?>
<?php if(!$isOrder) { ?>
                    
                    <span style="color:red">交換をキャンセルする場合は<br>キャンセルボタンを押して下さい</span><br>
                    
<?php } ?>
<?php if($isOrder) { ?>
                    
                    <span style="color:red">発注をキャンセルする場合は<br>キャンセルボタンを押して下さい</span><br>
                    
<?php } ?>
<?php } ?>
                  </td>
                </tr>
                <tr>
                  <td>
                    
                    <a href="#" onclick="document.confForm.action='./rireki.php'; document.confForm.submit(); return false;"><img src="../img/modoru.gif" alt="戻る" width="112" height="32" border="0"></a>
                    
                  </td>
                  <td>
                    
                    <a href="#" onclick="confirmCancel();"><img src="../img/cancel.gif" alt="キャンセル" width="112" height="32" border="4" style="border-color:red"></a>
                    
                  </td>
                </tr>
              </table>
            </div>
            

            <input type="hidden" name="encodeHint" value="京">
            <input type="hidden" value="<?php isset($orderId) ? print($orderId) : print('&#123;orderId&#125;'); ?>" name="orderId">
            <input type="hidden" value="<?php isset($orderReturnId) ? print($orderReturnId) : print('&#123;orderReturnId&#125;'); ?>" name="orderReturnId">
<?php if($isReturn) { ?>
            <input type="hidden" value="3" name="cancelMode">
<?php } ?>
<?php if(!$isReturn) { ?>
<?php if(!$isOrder) { ?>
            <input type="hidden" value="2" name="cancelMode">
<?php } ?>
<?php if($isOrder) { ?>
            <input type="hidden" value="1" name="cancelMode">
<?php } ?>
<?php } ?>
<?php if($isTok) { ?>
            <input type="hidden" value="1" name="tokFlg">
<?php } ?>
<?php for ($i1_hiddenHtml=0; $i1_hiddenHtml<count($hiddenHtml); $i1_hiddenHtml++) { ?>
        <input type="hidden" value="<?php isset($hiddenHtml[$i1_hiddenHtml]['value']) ? print($hiddenHtml[$i1_hiddenHtml]['value']) : print('&#123;hiddenHtml.value&#125;'); ?>" name="<?php isset($hiddenHtml[$i1_hiddenHtml]['name']) ? print($hiddenHtml[$i1_hiddenHtml]['name']) : print('&#123;hiddenHtml.name&#125;'); ?>">
<?php } ?>
          </form>

        <br><br><br>
        </div>
      </div>
    </div>
  </body>
</html>

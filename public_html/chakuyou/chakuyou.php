<?php
/*
 * 着用状況画面
 * chakuyou.src.php
 *
 * create 2007/03/29 H.Osugi
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


/* ../../include/setPaging.php start */

/*
 * ページング情報セッティングモジュール
 * setPaging.php
 *
 * create 2007/03/28 H.Osugi
 *
 */

/*
 * ページング情報をセッティングする
 * 引数  ：$nowPage  => 現在のページ数
 *       ：$perCount => １ページあたりの表示件数
 *       ：$allCount => 表示するデータの全件数
 * 戻り値：ページング情報
 *
 * create 2007/03/28 H.Osugi
 *
 */
function setPaging($nowPage, $perCount, $allCount) {

	if ($nowPage == '') {
		$nowPage = 1;
	}

	// 初期化
	$paging = array();
	$paging['isPaging']    = false;
	$paging['isPrev']      = false;
	$paging['isNext']      = false;
	$paging['prev']        = '';
	$paging['next']        = '';
	$paging['nowPage']     = '';

	// 1ページあたりの件数の設定がおかしい時
	if ($perCount <= 0) {
		return $paging;
	}

	// ページングする件数に達していない時
	if ($allCount <= $perCount) {
		return $paging;
	}

	// ページング機能の表示設定
	$paging['isPaging'] = true;

	// 現在のページ
	$paging['nowPage']  = $nowPage;

	// 前のページのボタン表示設定
	if ($nowPage > 1) {
		$paging['isPrev'] = true;
		$paging['prev']  = $nowPage - 1;
	}

	// 前のページのボタン表示設定
	if ($nowPage >= 1 && $nowPage < ceil($allCount / $perCount)) {
		$paging['isNext'] = true;
		$paging['next']  = $nowPage + 1;
	}

	return $paging;

}


/* ../../include/setPaging.php end */



// 初期設定
$isMenuCondition = true;	// 着用状況のメニューをアクティブに

// 変数の初期化 ここから ******************************************************
$searchCompCd    = '';					// 店舗コード
$searchCompName  = '';					// 店舗名
$searchCompId    = '';					// 店舗ID
$searchStaffCode = '';					// スタッフコード
$searchBarCode   = '';					// バーコード
$searchStatus    = array();				// ステータス

$isSelectedAdmin = false;				// 管理者権限で検索を行ったかどうか

// 状態の表示文字列
$DISPLAY_STATUS_APPLI       = $DISPLAY_STATUS[1];		// 承認待
$DISPLAY_STATUS_APPLI_ADMIT = $DISPLAY_STATUS[3];		// 承認済
$DISPLAY_STATUS_ORDER       = $DISPLAY_STATUS[14];		// 受注済
$DISPLAY_STATUS_SHIP        = $DISPLAY_STATUS[15];		// 出荷済
$DISPLAY_STATUS_DELIVERY    = $DISPLAY_STATUS[16];		// 納品済
$DISPLAY_STATUS_STOCKOUT    = $DISPLAY_STATUS[13];		// 在庫切
$DISPLAY_STATUS_NOT_RETURN  = $DISPLAY_STATUS[20];		// 未返却

$compCd    = castHtmlEntity($_SESSION['COMPCD']);	// 店舗番号
$compName  = castHtmlEntity($_SESSION['COMPNAME']);	// 店舗名

if ($isLevelAgency == true) {
	$isLevelAdmin = true;
}
// 変数の初期化 ここまで ******************************************************

// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST); 

$nowPage = 1;
if ($post['nowPage'] != '' && (int)$post['nowPage']) {
	$nowPage = trim($post['nowPage']);
}

$isSelectedAdmin = trim($post['isSelectedAdmin']);

// 検索時は1ページ目に戻す
if ($post['searchFlg'] == 1) {
	$nowPage = 1;
	$isSelectedAdmin = true;
}

if ($isLevelAdmin == false || $isSelectedAdmin == true) {

	$isSelectedAdmin = true;

	// 管理者権限の場合は検索条件を指定しているか判定
//	if ($isLevelAdmin == true) {
//
//		// 条件が指定されているか判定
//		$hasCondition = checkCondition($post);
//
//		if ($hasCondition == false) {
//
//			$hiddens['errorName'] = 'chakuyou';
//			$hiddens['menuName']  = 'isMenuCondition';
//			$hiddens['returnUrl'] = 'chakuyou/chakuyou.php';
//			$hiddens['errorId'][] = '902';
//			$errorUrl             = HOME_URL . 'error.php';
//
//			redirectPost($errorUrl, $hiddens);
//
//		}
//	}

	// 表示する着用状況一覧を取得
	$items = getOrderDetail($dbConnect, $post, $nowPage, $DISPLAY_STATUS, $allCount, $isLevelItc, $isLevelHonbu);

	// ページングのセッティング
	$pagingStaff = setPaging($nowPage, 1, $allCount);

	// スタッフが０件の場合
	if ($allCount <= 0) {

		// 条件が指定されているか判定
		$hasCondition = checkCondition($post);

		$hiddens['errorName'] = 'chakuyou';
		$hiddens['menuName']  = 'isMenuCondition';

		if ($hasCondition == true) {
			$hiddens['returnUrl'] = 'chakuyou/chakuyou.php';
		}
		else {
			$hiddens['returnUrl'] = 'top.php';
		}

		$hiddens['errorId'][] = '901';
		$errorUrl             = HOME_URL . 'error.php';

		redirectPost($errorUrl, $hiddens);

    } else {
        // ヘッダー部分に表示する着用者情報を取得する
        if (is_array($items) && count($items) != 0) {
            $headerData = getHeaderData($dbConnect, $items[0]['StaffID']);
        }   
	}
}

// 表示する値の成型 ここから +++++++++++++++++++++++++++++++++++++++++++++++++++

// 店舗コード
$searchCompCd    = trim($post['searchCompCd']);

// 店舗名
$searchCompName  = trim($post['searchCompName']);

// 店舗ID
$searchCompId    = trim($post['searchCompId']);

// スタッフコード
$searchStaffCode = trim($post['searchStaffCode']);

// 単品番号
$searchBarCode   = trim($post['searchBarCode']);

// 状態
for ($i=1; $i<=7; $i++) {

	${'isSelectedStatus' . $i} = false;
	if (isset($post['searchStatus']) && is_array($post['searchStatus']) && in_array($i, $post['searchStatus'])) {
		${'isSelectedStatus' . $i} = true;
	}

}
// 表示する値の成型 ここまで +++++++++++++++++++++++++++++++++++++++++++++++++++

/*
 * 着用状況一覧情報を取得する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$post           => POST値
 *       ：$nowPage        => 現在のページ数
 *       ：$DISPLAY_STATUS => 状態
 *       ：$allCount       => 全件数
 * 戻り値：$result         => 注文履歴一覧情報
 *
 * create 2007/03/29 H.Osugi
 *
 */
function getOrderDetail($dbConnect, $post, $nowPage, $DISPLAY_STATUS ,&$allCount, $isLevelItc, $isLevelHonbu) {

	global $isLevelAdmin;	// 管理者権限の有無
	global $isLevelAgency;	// 一次代理店

	// 初期化
	$compId    = '';
	$staffCode = '';
	$barCode   = '';
	$status    = '';
	$offset    = '';

	// 取得したいデータの開始位置
	$offset = ($nowPage - 1);

	// 店舗ID
	$compId = $_SESSION['COMPID'];
	if ($isLevelAdmin == true) {
		$compId = $post['searchCompId'];

		// 店舗IDに不正な値が入っていた場合
		if ($compId != '' && !ctype_digit($compId)) {
			$result = array();
			return $result;
		}
	}

	if ($isLevelAdmin == true) {

        if (!$isLevelItc) {

            if ($isLevelHonbu) {
                // 本部権限
                if (isset($_SESSION['HONBUCD'])) {
                    $honbuCd = $_SESSION['HONBUCD'];
                } else {
                    $honbuCd = '';
                }

            } else {
                // 支部権限
                if (isset($_SESSION['SHIBUCD']) && isset($_SESSION['HONBUCD'])) {
                    $honbuCd = $_SESSION['HONBUCD'];
                    $shibuCd = $_SESSION['SHIBUCD'];
                } else {
                    $honbuCd = '';
                    $shibuCd = '';
                }
            }
        }
    }

	// スタッフコード
	$staffCode = $post['searchStaffCode'];

	// 単品番号
	$barCode = $post['searchBarCode'];

	// 状態
	$countStatus = 0;
	if (isset($post['searchStatus']) && is_array($post['searchStatus'])) {
		$countStatus = count($post['searchStatus']);
	}
	for ($i=0; $i<$countStatus; $i++) {
		switch ($post['searchStatus'][$i]) {
			case '1':
				$status .= 	" " . STATUS_APPLI;				// 承認待
				break;
			case '2':
				$status .= 	" " . STATUS_APPLI_ADMIT;		// 申請済（承認済）
				break;
			case '3':
				$status .= 	" " . STATUS_ORDER;				// 受注済
				break;
			case '4':
				$status .= 	" " . STATUS_SHIP;				// 出荷済
				break;
			case '5':
				$status .= 	" " . STATUS_DELIVERY;			// 納品済
				break;
			case '6':
				$status .= 	" " . STATUS_STOCKOUT;			// 在庫切れ
				break;
			case '7':
				$status .= 	" " . STATUS_NOT_RETURN_ADMIT;	// 未返却（承認済）
				$status .= ",";
				$status .= 	" " . STATUS_NOT_RETURN_ORDER;	// 未返却（受注済）
				break;
			default:
				break;
		}

		if ($i != $countStatus -1) {
			$status .= ",";
		}

	}

	// 状態が何も選択されていない場合
	if ($countStatus <= 0) {

		$status  = 	" " . STATUS_APPLI;				// 申請済（承認待ち）
		$status .= ",";
		$status .= 	" " . STATUS_APPLI_ADMIT;		// 申請済（承認済）
		$status .= ",";
		$status .= 	" " . STATUS_STOCKOUT;			// 在庫切れ
		$status .= ",";
		$status .= 	" " . STATUS_ORDER;				// 受注済
		$status .= ",";
		$status .= 	" " . STATUS_SHIP;				// 出荷済
		$status .= ",";
		$status .= 	" " . STATUS_DELIVERY;			// 納品済
		$status .= ",";
		$status .= 	" " . STATUS_NOT_RETURN;		// 未返却（承認待ち）
		$status .= ",";
		$status .= 	" " . STATUS_NOT_RETURN_ADMIT;	// 未返却（承認済）
		$status .= ",";
		$status .= 	" " . STATUS_NOT_RETURN_ORDER;	// 未返却（受注済）
		$status .= ",";
		$status .= 	" " . STATUS_RETURN_NOT_APPLY;	// 返却未申請
		$status .= ",";
		$status .= 	" " . STATUS_LOSS;				// 紛失（承認待ち）

	}

	// 着用状況の件数を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" COUNT(DISTINCT ts.StaffCode) as count_staff";
	$sql .= " FROM";
	$sql .= 	" T_Staff ts";
	$sql .= " INNER JOIN";
	$sql .= 	" T_Order tor";
	$sql .= " ON";
	$sql .= 	" ts.StaffCode = tor.StaffCode";
	$sql .= " AND";
	$sql .= 	" ts.CompID = tor.CompID";
	$sql .= " AND";
	$sql .= 	" tor.Del = " . DELETE_OFF;

	if ($compId != '') {
		$sql .= " AND";
		$sql .= 	" tor.CompID = " . db_Escape($compId);
	}

	$sql .= " INNER JOIN";
	$sql .= 	" M_Comp mc";
	$sql .= " ON";
	$sql .= 	" tor.CompID = mc.CompID";
	$sql .= " AND";
	$sql .= 	" mc.Del = " . DELETE_OFF;
	$sql .= " AND";
    $sql .=     " mc.ShopFlag <> 0";        // ShopFlagが0以外の店舗を表示する

	if ($corpCode != '') {
		$sql .= " AND";
		$sql .= 	" mc.CorpCd = '" . db_Escape($corpCode) . "'";
	}

	if ($honbuCd != '') {
		$sql .= " AND";
		$sql .= 	" mc.HonbuCd = '" . db_Escape($honbuCd) . "'";
	}

	if ($shibuCd != '') {
		$sql .= " AND";
		$sql .= 	" mc.ShibuCd = '" . db_Escape($shibuCd) . "'";
	}

	$sql .= " INNER JOIN";
	$sql .= 	" (";
	$sql .= 		" (";
	$sql .= 			" SELECT";
	$sql .= 				" DISTINCT";
	$sql .= 				" tod_uni1.OrderID";
	$sql .= 			" FROM";
	$sql .= 				" T_Order_Details tod_uni1";
	$sql .= 			" INNER JOIN";
	$sql .= 				" T_Staff_Details tsd_uni1";
	$sql .= 			" ON";
	$sql .= 				" tod_uni1.OrderDetID = tsd_uni1.OrderDetID";
	$sql .= 			" AND";
	$sql .= 				" tsd_uni1.ReturnDetID is NULL";
	$sql .= 			" AND";
	$sql .= 				" tod_uni1.Del = " . DELETE_OFF;
	$sql .= 			" AND";
	$sql .= 				" tsd_uni1.Del = " . DELETE_OFF;

	// 単品番号の指定があった場合
	if ($barCode != '') {
		$sql .= 		" AND";
		//$sql .= 			" tod_uni1.BarCd = '" . db_Escape($barCode) . "'";
		$sql .= 			" tod_uni1.BarCd LIKE '" . db_Like_Escape($barCode) . "%'";
	}

	// 状態の指定があった場合
	if ($status != '') {
		$sql .= 		" AND";
		$sql .= 			" tsd_uni1.Status IN (";
		$sql .= 				$status;
		$sql .= 			" )";
	}

	$sql .= 		" )";
	$sql .= 	" UNION ALL";
	$sql .= 		" (";
	$sql .= 			" SELECT";
	$sql .= 				" DISTINCT";
	$sql .= 				" tod_uni2.OrderID";
	$sql .= 			" FROM";
	$sql .= 				" T_Order_Details tod_uni2";
	$sql .= 			" INNER JOIN";
	$sql .= 				" T_Staff_Details tsd_uni2";
	$sql .= 			" ON";
	$sql .= 				" tod_uni2.OrderDetID = tsd_uni2.ReturnDetID";
	$sql .= 			" AND";
	$sql .= 				" tod_uni2.Del = " . DELETE_OFF;
	$sql .= 			" AND";
	$sql .= 				" tsd_uni2.Del = " . DELETE_OFF;

	// 単品番号の指定があった場合
	if ($barCode != '') {
		$sql .= 		" AND";
		//$sql .= 			" tod_uni2.BarCd = '" . db_Escape($barCode) . "'";
		$sql .= 			" tod_uni2.BarCd LIKE '" . db_Like_Escape($barCode) . "%'";
	}

	// 状態の指定があった場合
	if ($status != '') {
		$sql .= 		" AND";
		$sql .= 			" tsd_uni2.Status IN (";
		$sql .= 				$status;
		$sql .= 			" )";
	}

	$sql .= 		" )";
	$sql .= 	" ) tsd";
	$sql .= " ON";
	$sql .= 	" tor.OrderID = tsd.OrderID";
	$sql .= " WHERE";
	////$sql .= 	" ts.AllReturnFlag = 0";
	////$sql .= " AND";
	$sql .= 	" ts.Del = " . DELETE_OFF;

	// スタッフコードの指定があった場合
	if ($staffCode != '') {
		$sql .= " AND";
		$sql .= 	" ts.StaffCode = '" . db_Escape($staffCode) . "'";
	}

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	$allCount = 0;
	if (!isset($result[0]['count_staff']) || $result[0]['count_staff'] <= 0) {
		$result = array();
	 	return $result;
	}

	// 全件数
	$allCount = $result[0]['count_staff'];

	// 着用状況の一覧を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" tor.StaffCode,";
	$sql .= 	" tod.AppliDay,";
	$sql .= 	" tod.ItemID,";
	$sql .= 	" tod.ItemName,";
	$sql .= 	" tod.Size,";
	$sql .= 	" tod.BarCd,";
	$sql .= 	" tsd.StaffID,";
	$sql .= 	" tsd.Status";
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

	// 状態の指定があった場合
	if ($status != '') {
		$sql .= 		" AND";
		$sql .= 			" tsd_uni1.Status IN (";
		$sql .= 				$status;
		$sql .= 			" )";
	}

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

	// 状態の指定があった場合
	if ($status != '') {
		$sql .= 		" AND";
		$sql .= 			" tsd_uni2.Status IN (";
		$sql .= 				$status;
		$sql .= 			" )";
	}

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
	$sql .= 							" AND";

    if ($_SESSION['ADMINLVL'] == 0) {   // ティファニー
        if ($_SESSION['SHOPFLAG'] == EXCEPTIONALSHOP_EXCEPTIONAL) {
            $sql .=     " mc.ShopFlag = 2";         // ShopFlagが2の店舗のみを表示する
        } else {
            $sql .=     " mc.ShopFlag = 1";         // ShopFlagが1の店舗のみを表示する
        }
    } else {                            // ＩＴＣ管理者
        $sql .=     " mc.ShopFlag <> 0";        // ShopFlagが0以外の店舗を表示する
    }

	if ($corpCode != '') {
		$sql .= " AND";
		$sql .= 	" mc.CorpCd = '" . db_Escape($corpCode) . "'";
	}

	if ($honbuCd != '') {
		$sql .= " AND";
		$sql .= 	" mc.HonbuCd = '" . db_Escape($honbuCd) . "'";
	}

	if ($shibuCd != '') {
		$sql .= " AND";
		$sql .= 	" mc.ShibuCd = '" . db_Escape($shibuCd) . "'";
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

	// 状態の指定があった場合
	if ($status != '') {
		$sql .= 									" AND";
		$sql .= 										" tsd_uni3.Status IN (";
		$sql .= 											$status;
		$sql .= 										" )";
	}

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

	// 状態の指定があった場合
	if ($status != '') {
		$sql .= 									" AND";
		$sql .= 										" tsd_uni4.Status IN (";
		$sql .= 											$status;
		$sql .= 										" )";
	}

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
	if ($staffCode != '') {
		$sql .= 					" AND";
		$sql .= 						" ts.StaffCode = '" . db_Escape($staffCode) . "'";
	}

	// 単品番号の指定があった場合
	if ($barCode != '') {
		$sql .= 					" AND";
		//$sql .= 						" tod2.BarCd = '" . db_Escape($barCode) . "'";
		$sql .= 						" tod2.BarCd LIKE '" . db_Like_Escape($barCode) . "%'";
	}

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

	// 単品番号の指定があった場合
	if ($barCode != '') {
		$sql .= 	" AND";
		//$sql .= 		" tod.BarCd = '" . db_Escape($barCode) . "'";
		$sql .= 		" tod.BarCd LIKE '" . db_Like_Escape($barCode) . "%'";
	}

	$sql .= 	" ORDER BY";
	$sql .= 		" tod.ItemID ASC,";
	$sql .= 		" tsd.Status ASC";

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	// 取得した値をHTMLエンティティ
	$resultCount = count($result);
	for ($i=0; $i<$resultCount; $i++) {

		$result[$i]['requestDay'] = strtotime($result[$i]['AppliDay']);
		$result[$i]['StaffCode']  = castHtmlEntity($result[$i]['StaffCode']);
        $result[$i]['StaffID']  = castHtmlEntity($result[$i]['StaffID']);
		$result[$i]['ItemName']   = castHtmlEntity($result[$i]['ItemName']);
		$result[$i]['Size']       = castHtmlEntity($result[$i]['Size']);
		$result[$i]['BarCd']      = castHtmlEntity($result[$i]['BarCd']);

		$result[$i]['isEmptyBarCd'] = true;
		if ($result[$i]['BarCd'] != '') {
			$result[$i]['isEmptyBarCd'] = false;
		}

		// 状態
		$result[$i]['status']  = castHtmlEntity($DISPLAY_STATUS[$result[$i]['Status']]);

		// 状態の文字列の色
		$result[$i]['statusIsBlue']  = false;
		$result[$i]['statusIsRed']   = false;
		$result[$i]['statusIsTeal']  = false;
		$result[$i]['statusIsGreen'] = false;
		$result[$i]['statusIsGray']  = false;
		$result[$i]['statusIsPink']  = false;
		$result[$i]['statusIsBlack'] = false;
		switch ($result[$i]['Status']) {
			case STATUS_APPLI:					// 申請済（承認待ち）
			case STATUS_STOCKOUT:				// 在庫切れ
				$result[$i]['statusIsGray']  = true;
				break;
			case STATUS_APPLI_DENY:				// 申請済（否認）
			case STATUS_NOT_RETURN_DENY:		// 未返却 （否認）
			case STATUS_LOSS_DENY:				// 紛失（否認）
				$result[$i]['statusIsPink']  = true;
				break;
			case STATUS_APPLI_ADMIT:			// 申請済（承認済）
				$result[$i]['statusIsGreen'] = true;
				break;
			case STATUS_ORDER:					// 受注済
				$result[$i]['statusIsBlue']  = true;
				break;
			case STATUS_NOT_RETURN:				// 未返却（承認待ち）
			case STATUS_NOT_RETURN_ADMIT:		// 未返却（承認済）
			case STATUS_NOT_RETURN_ORDER:		// 未返却（受注済）
				$result[$i]['statusIsRed']   = true;
				break;
			case STATUS_LOSS:					// 紛失（承認待ち）
			case STATUS_LOSS_ADMIT:				// 紛失（承認済）
			case STATUS_LOSS_ORDER:				// 紛失（受注済）
				$result[$i]['statusIsTeal']  = true;
				break;
			default:
				$result[$i]['statusIsBlack'] = true;
				break;
		}

		// 背景の文字列の色
		$result[$i]['bgcolorIsNone']   = false;
		$result[$i]['bgcolorIsRed']    = false;
		$result[$i]['bgcolorIsYellow'] = false;
		switch ($result[$i]['Status']) {
			case STATUS_NOT_RETURN:				// 未返却（承認待ち）
			case STATUS_NOT_RETURN_ADMIT:		// 未返却（承認済）
			case STATUS_NOT_RETURN_ORDER:		// 未返却（受注済）
				$result[$i]['bgcolorIsRed']  = true;
				break;
			case STATUS_APPLI:					// 申請済（承認待ち）
			case STATUS_APPLI_ADMIT:			// 申請済（承認済）
			case STATUS_STOCKOUT:				// 在庫切れ
				$result[$i]['bgcolorIsYellow']  = true;
				break;
			default:
				$result[$i]['bgcolorIsNone'] = true;
				break;
		}

	}

	return  $result;

}

/*
 * 検索条件を指定しているか判定
 * 引数  ：$post           => POST値
 * 戻り値：true：条件を指定している / false：条件を指定していない
 *
 * create 2007/04/06 H.Osugi
 *
 */
function checkCondition($post) {

	global $isLevelAdmin;	// 管理者権限の有無

	// 店舗IDの指定があった場合
	if ($isLevelAdmin == true) {
		if (isset($post['searchCompId']) && $post['searchCompId'] != '') {
			return true;
		}
	}
	
	// スタッフコードの指定があった場合
	if (isset($post['searchStaffCode']) && $post['searchStaffCode'] != '') {
		return true;
	}

	// 単品番号の指定があった場合
	if (isset($post['searchBarCode']) && $post['searchBarCode'] != '') {
		return true;
	}

	// 状態の指定があった場合
	if (isset($post['searchStatus']) && count($post['searchStatus']) > 0) {
		return true;
	}

	return false;

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
        

        <form method="post" action="./chakuyou.php" name="pagingForm">
          <div id="contents">
            <h1>ユニフォーム貸与内容</h1>
            <table border="0" width="700" cellpadding="0" cellspacing="0" class="tb_1">
              <tr height="40">
                <td width="80" class="line"><span class="fbold">所属施設</span></td>
<?php if(!$isLevelAdmin) { ?>
                <td colspan="3" class="line"><?php isset($compCd) ? print($compCd) : print('&#123;compCd&#125;'); ?>：<?php isset($compName) ? print($compName) : print('&#123;compName&#125;'); ?></td>
<?php } ?>
<?php if($isLevelAdmin) { ?>
                <td colspan="3" class="line">
                  <input type="text" name="searchCompCd" value="<?php isset($searchCompCd) ? print($searchCompCd) : print('&#123;searchCompCd&#125;'); ?>" style="width:60px;" readonly="readonly"><input type="text" name="searchCompName" value="<?php isset($searchCompName) ? print($searchCompName) : print('&#123;searchCompName&#125;'); ?>" style="width:310px;" readonly="readonly">
                  <input type="hidden" name="searchCompId" value="<?php isset($searchCompId) ? print($searchCompId) : print('&#123;searchCompId&#125;'); ?>">
                <input name="shop_btn" type="button" value="施設検索" onclick="window.open('../search_comp.php', 'searchCOmp', 'width=500, height=400, menubar=no, toolbar=no, scrollbars=yes'); return false;">
                </td>
<?php } ?>
                <td></td>
              </tr>
              <tr height="40">
                <td width="80" class="line"><span class="fbold">職員コード</span></td>
                <td width="210" class="line"><input name="searchStaffCode" type="text" value="<?php isset($searchStaffCode) ? print($searchStaffCode) : print('&#123;searchStaffCode&#125;'); ?>" style="width:140px;" maxlength="12"></td>
                <td width="80" class="line" align="center"><span class="fbold">単品番号</span></td>
                <td width="210" class="line"><input name="searchBarCode" type="text" value="<?php isset($searchBarCode) ? print($searchBarCode) : print('&#123;searchBarCode&#125;'); ?>" style="width:140px;"></td>
              </tr>
              <tr height="30">
                <td width="80"><span class="fbold">状態</span></td>
                <td colspan="3">
<?php if($isSelectedStatus2) { ?>
                        <input type="checkbox" name="searchStatus[]" value="2" id="status2" checked="checked">
<?php } ?>
<?php if(!$isSelectedStatus2) { ?>
                        <input type="checkbox" name="searchStatus[]" value="2" id="status2">
<?php } ?>
                        <label for="status2"><?php isset($DISPLAY_STATUS_APPLI_ADMIT) ? print($DISPLAY_STATUS_APPLI_ADMIT) : print('&#123;DISPLAY_STATUS_APPLI_ADMIT&#125;'); ?></label>
<?php if($isSelectedStatus3) { ?>
                        <input type="checkbox" name="searchStatus[]" value="3" id="status3" checked="checked">
<?php } ?>
<?php if(!$isSelectedStatus3) { ?>
                        <input type="checkbox" name="searchStatus[]" value="3" id="status3">
<?php } ?>
                        <label for="status3"><?php isset($DISPLAY_STATUS_ORDER) ? print($DISPLAY_STATUS_ORDER) : print('&#123;DISPLAY_STATUS_ORDER&#125;'); ?></label>
<?php if($isSelectedStatus4) { ?>
                        <input type="checkbox" name="searchStatus[]" value="4" id="status4" checked="checked">
<?php } ?>
<?php if(!$isSelectedStatus4) { ?>
                        <input type="checkbox" name="searchStatus[]" value="4" id="status4">
<?php } ?>
                        <label for="status4"><?php isset($DISPLAY_STATUS_SHIP) ? print($DISPLAY_STATUS_SHIP) : print('&#123;DISPLAY_STATUS_SHIP&#125;'); ?></label>
<?php if($isSelectedStatus5) { ?>
                        <input type="checkbox" name="searchStatus[]" value="5" id="status5" checked="checked">
<?php } ?>
<?php if(!$isSelectedStatus5) { ?>
                        <input type="checkbox" name="searchStatus[]" value="5" id="status5">
<?php } ?>
                        <label for="status5"><?php isset($DISPLAY_STATUS_DELIVERY) ? print($DISPLAY_STATUS_DELIVERY) : print('&#123;DISPLAY_STATUS_DELIVERY&#125;'); ?></label>
                </td>
                <td align="center"><input type="button" value="     検索     " onclick="document.pagingForm.searchFlg.value='1'; document.pagingForm.submit(); return false;"></td>
              </tr>
              <tr height="30">
                <td width="80" class="line"></td>
                <td colspan="3" class="line">
<?php if($isSelectedStatus6) { ?>
                        <input type="checkbox" name="searchStatus[]" value="6" id="status6" checked="checked">
<?php } ?>
<?php if(!$isSelectedStatus6) { ?>
                        <input type="checkbox" name="searchStatus[]" value="6" id="status6">
<?php } ?>
                        <label for="status6"><?php isset($DISPLAY_STATUS_STOCKOUT) ? print($DISPLAY_STATUS_STOCKOUT) : print('&#123;DISPLAY_STATUS_STOCKOUT&#125;'); ?></label>
<?php if($isSelectedStatus7) { ?>
                        <input type="checkbox" name="searchStatus[]" value="7" id="status7" checked="checked">
<?php } ?>
<?php if(!$isSelectedStatus7) { ?>
                        <input type="checkbox" name="searchStatus[]" value="7" id="status7">
<?php } ?>
                        <label for="status7"><?php isset($DISPLAY_STATUS_NOT_RETURN) ? print($DISPLAY_STATUS_NOT_RETURN) : print('&#123;DISPLAY_STATUS_NOT_RETURN&#125;'); ?></label>
                </td>
                <td align="center" class="line">
<?php if($isLevelAdmin) { ?>
<?php if(!$isSelectedAdmin) { ?>
                  <input type="button" value=" ダウンロード " disabled="disabled">
<?php } ?>
<?php if($isSelectedAdmin) { ?>
                  <input type="button" value=" ダウンロード " onclick="document.pagingForm.action='./chakuyou_csv_dl.php'; document.pagingForm.submit(); document.pagingForm.action='./chakuyou.php'; return false;">
<?php } ?>
<?php } ?>
<?php if(!$isLevelAdmin) { ?>
                  &nbsp;
<?php } ?>
                </td>
              </tr>
            </table>
<?php if($isSelectedAdmin) { ?>
            <h3>◆職員</h3>
             <table border="0" width="600" cellpadding="0" cellspacing="0" class="tb_1" style="border-bottom:1px solid #CCC; padding-bottom:10px;">
              <tr height="20">
                <td width="100"><span class="fbold">施設名</span></td>
                <td width="500"><?php isset($headerData['CompCd']) ? print($headerData['CompCd']) : print('&#123;headerData.CompCd&#125;'); ?>&nbsp;：&nbsp;<?php isset($headerData['CompName']) ? print($headerData['CompName']) : print('&#123;headerData.CompName&#125;'); ?></td>
              </tr>
              <tr height="20">
                <td width="100"><span class="fbold">職員名</span></td>
                <td width="500"><?php isset($headerData['StaffCode']) ? print($headerData['StaffCode']) : print('&#123;headerData.StaffCode&#125;'); ?>&nbsp;：&nbsp;<?php isset($headerData['PersonName']) ? print($headerData['PersonName']) : print('&#123;headerData.PersonName&#125;'); ?></td>
              </tr>
            </table>
            <br>

            <h3>◆貸与内容</h3>
            <table border="0" width="600" cellpadding="0" cellspacing="2" class="tb_1">
              <tr>
                <!--<th width="100">職員</th>-->
                <th width="200">アイテム名</th>
                <th width="100">サイズ</th>
                <th width="100">単品番号</th>
                <th width="100">申請日</th>
                <th width="100">状態</th>
              </tr>
<?php for ($i1_items=0; $i1_items<count($items); $i1_items++) { ?>
<?php if($items[$i1_items]['bgcolorIsNone']) { ?>
              <tr height="20">
<?php } ?>
<?php if($items[$i1_items]['bgcolorIsRed']) { ?>
              <tr height="20" class="chakuyo_2">
<?php } ?>
<?php if($items[$i1_items]['bgcolorIsYellow']) { ?>
              <tr height="20" class="chakuyo_1">
<?php } ?>
                <!--<td class="line2" align="center"><?php isset($items[$i1_items]['StaffCode']) ? print($items[$i1_items]['StaffCode']) : print('&#123;items.StaffCode&#125;'); ?></td>-->
                <td class="line2" align="left">&nbsp;&nbsp;<?php isset($items[$i1_items]['ItemName']) ? print($items[$i1_items]['ItemName']) : print('&#123;items.ItemName&#125;'); ?></td>
                <td class="line2" align="center"><?php isset($items[$i1_items]['Size']) ? print($items[$i1_items]['Size']) : print('&#123;items.Size&#125;'); ?></td>
                <td class="line2" align="center">
<?php if($items[$i1_items]['isEmptyBarCd']) { ?>
                  
                  &nbsp;
                  
<?php } ?>
<?php if(!$items[$i1_items]['isEmptyBarCd']) { ?>
                  <?php isset($items[$i1_items]['BarCd']) ? print($items[$i1_items]['BarCd']) : print('&#123;items.BarCd&#125;'); ?>
<?php } ?>
                </td>
                <td class="line2" align="center"><?php isset($items[$i1_items]['requestDay']) ? print(date("y/m/d", $items[$i1_items]['requestDay'])) : print('&#123;dateFormat(items.requestDay, "y/m/d")&#125;'); ?></td>
                <td class="line2" align="center">
<?php if($items[$i1_items]['statusIsBlue']) { ?>
                  
                  <span style="color:blue"><?php isset($items[$i1_items]['status']) ? print($items[$i1_items]['status']) : print('&#123;items.status&#125;'); ?></span>
                  
<?php } ?>
<?php if($items[$i1_items]['statusIsRed']) { ?>
                  
                  <span style="color:red"><?php isset($items[$i1_items]['status']) ? print($items[$i1_items]['status']) : print('&#123;items.status&#125;'); ?></span>
                  
<?php } ?>
<?php if($items[$i1_items]['statusIsTeal']) { ?>
                  
                  <span style="color:Teal"><?php isset($items[$i1_items]['status']) ? print($items[$i1_items]['status']) : print('&#123;items.status&#125;'); ?></span>
                  
<?php } ?>
<?php if($items[$i1_items]['statusIsGreen']) { ?>
                  
                  <span style="color:green"><?php isset($items[$i1_items]['status']) ? print($items[$i1_items]['status']) : print('&#123;items.status&#125;'); ?></span>
                  
<?php } ?>
<?php if($items[$i1_items]['statusIsGray']) { ?>
                  
                  <span style="color:gray"><?php isset($items[$i1_items]['status']) ? print($items[$i1_items]['status']) : print('&#123;items.status&#125;'); ?></span>
                  
<?php } ?>
<?php if($items[$i1_items]['statusIsPink']) { ?>
                  
                  <span style="color:fuchsia"><?php isset($items[$i1_items]['status']) ? print($items[$i1_items]['status']) : print('&#123;items.status&#125;'); ?></span>
                  
<?php } ?>
<?php if($items[$i1_items]['statusIsBlack']) { ?>
                  <?php isset($items[$i1_items]['status']) ? print($items[$i1_items]['status']) : print('&#123;items.status&#125;'); ?>
<?php } ?>
                </td>
              </tr>
<?php } ?>
            </table>
            <br>
<?php if($pagingStaff['isPaging']) { ?>
            <br>
            <table border="0" width="640" cellpadding="0" cellspacing="0" class="tb_1">
              <tr>
                <td width="120" align="left">
<?php if($pagingStaff['isPrev']) { ?>
                  <input name="prev_btn" type="button" value="&lt;&lt;前の職員" onclick="document.pagingForm.action='#'; document.pagingForm.nowPage.value='<?php isset($pagingStaff['prev']) ? print($pagingStaff['prev']) : print('&#123;pagingStaff.prev&#125;'); ?>'; document.pagingForm.submit(); return false;">
<?php } ?>
                </td>
                <td width="400">&nbsp;</td>
                <td width="120" align="right">
<?php if($pagingStaff['isNext']) { ?>
                  <input name="next_btn" type="button" value="次の職員&gt;&gt;" onclick="document.pagingForm.action='#'; document.pagingForm.nowPage.value='<?php isset($pagingStaff['next']) ? print($pagingStaff['next']) : print('&#123;pagingStaff.next&#125;'); ?>'; document.pagingForm.submit(); return false;">
<?php } ?>
                </td>
              </tr>
            </table>
            <input type="hidden" name="nowPage" value="<?php isset($pagingStaff['nowPage']) ? print($pagingStaff['nowPage']) : print('&#123;pagingStaff.nowPage&#125;'); ?>">
<?php } ?>
<?php } ?>
          </div>
          <input type="hidden" name="encodeHint" value="京">
          <input type="hidden" name="searchFlg">
          <input type="hidden" name="isSelectedAdmin" value="<?php isset($isSelectedAdmin) ? print($isSelectedAdmin) : print('&#123;isSelectedAdmin&#125;'); ?>">
        </form>
        <br><br><br>
        

      </div>
    </div>
  </body>
</html>

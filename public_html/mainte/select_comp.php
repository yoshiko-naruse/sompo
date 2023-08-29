<?php
/*
 * 店舗検索画面
 * search_comp.src.php
 *
 * create 2007/04/12 H.Osugi
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



//// 管理権限のないユーザが閲覧しようとするとTOPに強制遷移
//if ($isLevelAdmin == false) {
//
//	$hiddens = array();
//	redirectPost(HOME_URL . 'top.php', $hiddens);
//
//}
 
// 変数の初期化 ここから ******************************************************
$comps      = array();
$searchComp = '';					// 基地名
$searchCompCode = '';				// 基地コード

$isSearched = false;				// 検索を行ったかどうか
// 変数の初期化 ここまで ******************************************************

// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST); 

$nowPage = 1;
if ($post['nowPage'] != '') {
	$nowPage = trim($post['nowPage']);
}

$isSearched = trim($post['isSearched']);

// 検索時は1ページ目に戻す
if ($post['searchFlg'] == 1) {
	$nowPage = 1;
	$isSearched = true;
}

if ($isSearched == true) {

	// 表示する店舗一覧を取得
	$comps = getCompData($dbConnect, $post, $nowPage, $allCount, $isLevelItc, $isLevelHonbu);
	
	// ページングのセッティング
	$paging = setPaging($nowPage, PAGE_PER_DISPLAY_SEARCH_COMP, $allCount);
	
	// 店舗が０件の場合
	if ($allCount <= 0) {
		$isSearched = false;
	}
}

// 表示する値の成型 ここから +++++++++++++++++++++++++++++++++++++++++++++++++++

if (isset($post['searchCompCode']) && $post['searchCompCode'] != '') {
    // 基地コード
    $searchCompCode = trim($post['searchCompCode']);
}

if (isset($post['searchComp']) && $post['searchComp'] != '') {
    // 基地名
    $searchComp     = trim($post['searchComp']);
}

// 表示する値の成型 ここまで +++++++++++++++++++++++++++++++++++++++++++++++++++

/*
 * 店舗一覧情報を取得する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$post           => POST値
 *       ：$nowPage        => 現在のページ数
 *       ：$allCount       => 全件数
 * 戻り値：$result         => 店舗一覧情報
 *
 * create 2007/04/12 H.Osugi
 *
 */
function getCompData($dbConnect, $post, $nowPage, &$allCount, $isLevelItc, $isLevelHonbu) {

	global $isLevelAgency;

	// 初期化
	$compName  = '';
	$offset    = '';
	$corpCode  = '';
	$honbuCd   = '';
	$shibuCd   = '';

	// 取得したい件数
	$limit = PAGE_PER_DISPLAY_SEARCH_COMP;		// 1ページあたりの表示件数;

	// 取得したいデータの開始位置
	$offset = ($nowPage - 1) * PAGE_PER_DISPLAY_SEARCH_COMP;

	// 基地名
	$compName = $post['searchComp'];

	// 基地コード
	$compCode = $post['searchCompCode'];

    if (!$isLevelItc) {

        if ($isLevelHonbu) {
            // 支部権限
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

	// 店舗の件数を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" COUNT(CompID) as count_comp";
	$sql .= " FROM";
	$sql .= 	" M_Comp";
	$sql .= " WHERE";
    $sql .=     " ShopFlag <> 0";

	if ($isLevelAgency == true) {
		$sql .= " AND";
		$sql .= 	" AgencyID = '" . db_Escape($_SESSION['COMPID']) . "'";
	}

	if ($compCode != '') {
		$sql .= " AND";
		$sql .= 	" CompCd = '" . db_Like_Escape($compCode) . "'";
	}

	if ($compName != '') {
		$sql .= " AND";
		$sql .= 	" CompName LIKE '%" . db_Like_Escape($compName) . "%'";
	}

	if ($honbuCd != '') {
		$sql .= " AND";
		$sql .= 	" HonbuCd = '" . db_Like_Escape($honbuCd) . "'";
	}

	if ($shibuCd != '') {
		$sql .= " AND";
		$sql .= 	" ShibuCd = '" . db_Like_Escape($shibuCd) . "'";
	}
	
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	$allCount = 0;
	if (!isset($result[0]['count_comp']) || $result[0]['count_comp'] <= 0) {
		$result = array();
	 	return $result;
	}

	// 全件数
	$allCount = $result[0]['count_comp'];

	$top = $offset + $limit;
	if ($top > $allCount) {
		$limit = $limit - ($top - $allCount);
		$top   = $allCount;
	}

	// 店舗の一覧を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" CompID,";
	$sql .= 	" CompCd,";
	$sql .= 	" CompName";
	$sql .= " FROM";
	$sql .= 	" (";
	$sql .= 		" SELECT";
	$sql .= 			" TOP " . $limit;
	$sql .= 			" *";
	$sql .= 		" FROM";
	$sql .= 			" M_Comp mco2";
	$sql .= 		" WHERE";
	$sql .= 			" mco2.CompID IN (";
	$sql .= 						" SELECT";
	$sql .= 							" CompID";
	$sql .= 						" FROM";
	$sql .= 							" (";
	$sql .= 								" SELECT";
	$sql .= 									" DISTINCT";
	$sql .= 									" TOP " . ($top);
	$sql .= 									" mco3.HonbuCd,";
	$sql .= 									" mco3.ShibuCd,";
	$sql .= 									" mco3.CompCd,";
	$sql .= 									" mco3.CompID";
	$sql .= 								" FROM";
	$sql .= 									" M_Comp mco3";
	$sql .= 								" WHERE";

    if ($_SESSION['ADMINLVL'] == 0) {   // ティファニー
        if ($_SESSION['SHOPFLAG'] == EXCEPTIONALSHOP_EXCEPTIONAL) {
            $sql .=     " mco3.ShopFlag = 2";         // ShopFlagが2の店舗のみを表示する
        } else {
            $sql .=     " mco3.ShopFlag = 1";         // ShopFlagが1の店舗のみを表示する
        }
    } else {                            // ＩＴＣ管理者
        $sql .=     " mco3.ShopFlag <> 0";        // ShopFlagが0以外の店舗を表示する
    }

	if ($isLevelAgency == true) {
		$sql .= 							" AND";
		$sql .= 								" mco3.AgencyID = '" . db_Escape($_SESSION['COMPID']) . "'";
	}

	if ($compCode != '') {
		$sql .= 								" AND";
		$sql .= 									" mco3.CompCd = '" . db_Like_Escape($compCode) . "'";
	}

	if ($compName != '') {
		$sql .= 								" AND";
		$sql .= 									" mco3.CompName LIKE '%" . db_Like_Escape($compName) . "%'";
	}

	if ($corpCode != '') {
		$sql .= 								" AND";
		$sql .= 									" mco3.CorpCd = '" . db_Like_Escape($corpCode) . "'";
	}

	if ($honbuCd != '') {
		$sql .= 								" AND";
		$sql .= 									" mco3.HonbuCd = '" . db_Like_Escape($honbuCd) . "'";
	}

	if ($shibuCd								 != '') {
		$sql .= 								" AND";
		$sql .= 									" mco3.ShibuCd = '" . db_Like_Escape($shibuCd) . "'";
	}

	$sql .= 								" AND";
	$sql .= 									" mco3.Del = " . DELETE_OFF;
	$sql .= 								" ORDER BY";
	$sql .= 									" mco3.HonbuCd ASC,";
	$sql .= 									" mco3.ShibuCd ASC,";
	$sql .= 									" mco3.CompCd ASC,";
	$sql .= 									" mco3.CompID";
	$sql .= 							" ) mco4";
	$sql .= 						" )";
	$sql .=					" ORDER BY";
	$sql .= 					" mco2.HonbuCd DESC,";
	$sql .= 					" mco2.ShibuCd DESC,";
	$sql .= 					" mco2.CompCd DESC,";
	$sql .=						" mco2.CompID";

	$sql .= 	" ) mco";

	$sql .= 	" ORDER BY";
	//$sql .= 		" mco.CompCd ASC,";
	//$sql .= 		" mco.CompID ASC";
	$sql .= 		" mco.HonbuCd ASC,";
	$sql .= 		" mco.ShibuCd ASC,";
	$sql .= 		" mco.CompCd";
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

		$result[$i]['CompID']   = $result[$i]['CompID'];
		$result[$i]['CompCd']   = castHtmlEntity($result[$i]['CompCd']);
		$result[$i]['CompName'] = castHtmlEntity($result[$i]['CompName']);

	}

	return  $result;

}

?><!DOCTYPE html>
<html>
  <head>
    <META http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <script src="//code.jquery.com/jquery-1.12.4.min.js"></script>
    <link href="/main.css" rel="stylesheet" type="text/css">
    <script language="JavaScript">
    <!--
    function setComp(compCd, compName, compId) {

      if(!window.opener || window.opener.closed){
        window.close(); 
      } 

      window.opener.document.pagingForm.selectCompId.value = compId;
      window.opener.document.pagingForm.selectCompCd.value = compCd;
      window.opener.document.pagingForm.selectCompName.value = compName;
      window.close();
      return false;

    }

    function funcformonkeydown() {

      var src = window.event.srcElement;

      if(event.keyCode == 13) {
        if (src.type == '' ) {
          src.click();
        }
        else if (src.type != 'submit'
          && src.type != 'button'
          && src.type != 'textarea' ) {

          return false;
        }
      }
    }
    // -->
    </script>
    <title>制服管理システム</title>
  </head>
  <body>
    <div id="main3">
      

      <form method="post" action="./select_comp.php" name="pagingForm" onkeydown="javascript:return funcformonkeydown();">
        <div id="contents">
          <p class="fbold tb_1" style="margin-top:0px; font-size:120%">施設選択</p>
          <table border="0" width="400" cellpadding="0" cellspacing="0" class="tb_1">
            <tr>
              <td colspan="2" height="20">※ 検索したい施設名を入力してください。（部分一致）</td>
            </tr>
            <tr>
              <td width="120" class="line">施設CD<input type="text" name="searchCompCode" value="<?php isset($searchCompCode) ? print($searchCompCode) : print('&#123;searchCompCode&#125;'); ?>" style="width:80px;"></td>
              <td width="250" class="line">施設名<input type="text" name="searchComp" value="<?php isset($searchComp) ? print($searchComp) : print('&#123;searchComp&#125;'); ?>" style="width:200px;"></td>
              <td width="100" class="line"><input name="shop_btn" type="button" value="　検索　" onclick="document.pagingForm.searchFlg.value='1'; document.pagingForm.submit(); return false;"></td>
            </tr>
          </table>
<?php if($isSearched) { ?>
          <p class="fbold tb_1" style="font-size:120%">◆該当施設</p>
          <table border="0" width="400" cellpadding="0" cellspacing="2" class="tb_1">
            <tr>
              <th width="120">施設コード</th>
              <th width="280">施設名</th>
            </tr>
<?php for ($i1_comps=0; $i1_comps<count($comps); $i1_comps++) { ?>
            <tr height="20">
              <td class="line2" align="center"><a href="#" onclick="setComp('<?php isset($comps[$i1_comps]['CompCd']) ? print($comps[$i1_comps]['CompCd']) : print('&#123;comps.CompCd&#125;'); ?>', '<?php isset($comps[$i1_comps]['CompName']) ? print($comps[$i1_comps]['CompName']) : print('&#123;comps.CompName&#125;'); ?>', '<?php isset($comps[$i1_comps]['CompID']) ? print($comps[$i1_comps]['CompID']) : print('&#123;comps.CompID&#125;'); ?>');"><?php isset($comps[$i1_comps]['CompCd']) ? print($comps[$i1_comps]['CompCd']) : print('&#123;comps.CompCd&#125;'); ?></a></td>
              <td class="line2" align="left"><?php isset($comps[$i1_comps]['CompName']) ? print($comps[$i1_comps]['CompName']) : print('&#123;comps.CompName&#125;'); ?></td>
            </tr>
<?php } ?>
          </table>
<?php if($paging['isPaging']) { ?>
            <br>
            <div class="tb_1">
              <table border="0" width="120" cellpadding="0" cellspacing="0" class="tb_1">
                <tr>
                  <td width="60" align="left">
<?php if($paging['isPrev']) { ?>
                    <input name="prev_btn" type="button" value="&lt;&lt;" onclick="document.pagingForm.action='#'; document.pagingForm.nowPage.value='<?php isset($paging['prev']) ? print($paging['prev']) : print('&#123;paging.prev&#125;'); ?>'; document.pagingForm.submit(); return false;">
<?php } ?>
                  </td>
                  <td width="60" align="right">
<?php if($paging['isNext']) { ?>
                    <input name="next_btn" type="button" value="&gt;&gt;" onclick="document.pagingForm.action='#'; document.pagingForm.nowPage.value='<?php isset($paging['next']) ? print($paging['next']) : print('&#123;paging.next&#125;'); ?>'; document.pagingForm.submit(); return false;">
<?php } ?>
                  </td>
                </tr>
              </table>
            </div>
            <input type="hidden" name="nowPage" value="<?php isset($paging['nowPage']) ? print($paging['nowPage']) : print('&#123;paging.nowPage&#125;'); ?>">
<?php } ?>
<?php } ?>
          <br>
          <div align="center">
          <table border="0" cellpadding="0" cellspacing="0">
            <tr>
              <td>
              <td align="center" colspan="5">
                <a href="javascript:window.close()"><img src="../img/close.gif" width="112" height="32" border="0"></a></div>
              </td>
            </tr>
          </table>
          </div>
        </div>
        <input type="hidden" name="encodeHint" value="京">
        <input type="hidden" name="searchFlg">
        <input type="hidden" name="isSearched" value="<?php isset($isSearched) ? print($isSearched) : print('&#123;isSearched&#125;'); ?>">
      </form>
        <br><br><br>
      

    </div>
  </body>
</html>

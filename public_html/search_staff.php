<?php
/*
 * 店舗検索画面
 * search_comp.src.php
 *
 * create 2008/01/12
 *
 *
 */

// 各種モジュールの取得
include_once('../include/define.php');			// 定数定義
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


/* ../include/setPaging.php start */

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


/* ../include/setPaging.php end */



// 変数の初期化 ここから ******************************************************
$appliReason = '';                     // 申請理由
$staffs      = array();                // 検索結果
$searchCompCode = '';				   // 店舗コード
$searchCompName = '';                  // 店舗名
$searchHonbuCode = '';				   // 本部コード
$searchHonbuName = '';                 // 本部名
$searchShibuCode = '';				   // 支部コード
$searchShibuName = '';                 // 支部名
$searchStaffCode = '';                 // 社員番号
$searchPersonName = '';                // 氏名
$compData    = array();                // 会社情報

$isSearched = false;				// 検索を行ったかどうか
$isEmpty    = false;                // 検索結果が0かどうか
// 変数の初期化 ここまで ******************************************************

// GET・POST値をHTMLエンティティ
$post = castHtmlEntity($_POST); 
$get = castHtmlEntity($_GET); 

//var_dump("isLevelItc:" . $isLevelItc);

// 表示する値の成型 ここから +++++++++++++++++++++++++++++++++++++++++++++++++++

// 社員NO
if (isset($post['searchStaffCode'])) {
    $searchStaffCode = trim($post['searchStaffCode']);
}

// 社員名
if (isset($post['searchPersonName'])) {
    $searchPersonName = trim($post['searchPersonName']);
}

if ($isLevelAdmin) {
    //// 店舗コード
    //if (isset($post['searchCompCode'])) {
    //    $searchCompCode = trim($post['searchCompCode']);
    //}

    // 本部コード
    if (isset($post['searchHonbuId'])) {
        $searchHonbuId = trim($post['searchHonbuId']);
    }

    // 支部コード
    if (isset($post['searchShitenId'])) {
        $searchShitenId = trim($post['searchShitenId']);
    }

    // 基地局コード
    if (isset($post['searchEigyousyoId'])) {
        $searchEigyousyoId = trim($post['searchEigyousyoId']);
    }

	$compData = getCompName($dbConnect, $post, $isLevelAdmin, $isLevelItc);

	$searchHonbuName  = $compData[0]['HonbuName'];
	$searchShitenName = $compData[0]['ShibuName'];

	if (isset($compData[0]['CompCd']) && $compData[0]['CompCd'] != '') {
		$searchCompCode   = $compData[0]['CompCd'];

	} else {
		$searchCompCode   = '';

	}

	if (isset($compData[0]['CompName']) && $compData[0]['CompName'] != '') {
		$searchCompName   = $compData[0]['CompName'];

	} else {
		$searchCompName   = '';

	}

} else {    // 一般ユーザーの場合はログイン情報から取得

    // 店舗コード
    if (isset($_SESSION['COMPCD'])) {
        $searchCompCode    = trim($_SESSION['COMPCD']);
    }

    // 店舗名
    if (isset($_SESSION['COMPNAME'])) {
        $searchCompName    = trim($_SESSION['COMPNAME']);
    }

}

//var_dump($compData);

// 表示する値の成型 ここまで +++++++++++++++++++++++++++++++++++++++++++++++++++

// 発注区分
if (isset($get['appliReason'])) {
    $appliReason = $get['appliReason'];

} else {    // 発注区分がなければ、TOP画面に強制遷移

    // TOP画面に強制遷移
    $returnUrl = HOME_URL . 'top.php';
    $hiddens = array();
    redirectPost($returnUrl, $hiddens);
}

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

	// 表示する社員一覧を取得
	$staffs = getStaffData($dbConnect, $post, $nowPage, $allCount, $appliReason, $isLevelAdmin, $isLevelItc);

	// ページングのセッティング
	$paging = setPaging($nowPage, PAGE_PER_DISPLAY_SEARCH_STAFF, $allCount);

	// 社員が０件の場合
	if ($allCount <= 0) {
        $isEmpty    = true;
		$isSearched = false;
	}

}

/*
 * 社員一覧情報を取得する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$post           => POST値
 *       ：$nowPage        => 現在のページ数
 *       ：$allCount       => 全件数
 *       ：$appliReason    => 発注区分
 * 戻り値：$result         => 社員一覧情報
 *
 * create 2007/04/12 H.Osugi
 *
 */
function getStaffData($dbConnect, $post, $nowPage, &$allCount, $appliReason, $isLevelAdmin, $isLevelItc) {

    // 取引理由配列
    // 交換
    $koukanReasonAry = array(APPLI_REASON_EXCHANGE_SIZE, 
                            APPLI_REASON_EXCHANGE_INFERIORITY, 
                            APPLI_REASON_EXCHANGE_LOSS, 
                            APPLI_REASON_EXCHANGE_BREAK, 
                            APPLI_REASON_EXCHANGE_REPAIR, 
                            APPLI_REASON_EXCHANGE_CHANGEGRADE, 
                            APPLI_REASON_EXCHANGE_MATERNITY
                        );

    // 返却
    $henpinReasonAry = array(APPLI_REASON_RETURN_RETIRE, 
                            APPLI_REASON_RETURN_OTHER 
                        );

    // 初期化
    $compName  = '';
    $offset    = '';
	$corpCode  = '';

    // 取得したい件数
    $limit = PAGE_PER_DISPLAY_SEARCH_STAFF;     // 1ページあたりの表示件数;

    // 取得したいデータの開始位置
    $offset = ($nowPage - 1) * PAGE_PER_DISPLAY_SEARCH_STAFF;

    // パラメータをセット
    if ($isLevelAdmin) {

        // 本部コード
        if (isset($post['searchHonbuId'])) {
            $honbuCode = trim($post['searchHonbuId']);
        } else {
            $honbuCode = '';
        }

        // 支部コード
        if (isset($post['searchShitenId'])) {
            $shibuCode = trim($post['searchShitenId']);
        } else {
            $shibuCode = '';
        }

        // 基地局コード
        if (isset($post['searchEigyousyoId'])) {
            $compID = trim($post['searchEigyousyoId']);
        } else {
            $compID = '';
        }

        // 基地局コード
        if (isset($post['searchCompCode'])) {
            $compCode = trim($post['searchCompCode']);
        } else {
            $compCode = '';
        }

        //// 店舗CD
        //if (isset($post['searchCompCode'])) {
        //    $compCode = $post['searchCompCode'];
        //} else {
        //    $compCode = '';
        //}
        //// 店舗名
        //if (isset($post['searchCompName'])) {
        //    $compName = $post['searchCompName'];
        //} else {
        //    $compName = '';
        //}

        // 本部権限、支部権限なら、スコープを絞っておく
//    	if (!$isLevelItc) {
//    	    // 支店CD
//    	    if (isset($_SESSION['CORPCD'])) {
//    	        $corpCode = $_SESSION['CORPCD'];
//    	    } else {
//    	        $corpCode = '';
//    	    }
//		}


    } else {
        // 店舗CD
        if (isset($_SESSION['COMPCD'])) {
            $compCode = $_SESSION['COMPCD'];
        } else {
            $compCode = '';
        }

        // 店舗名
        if (isset($_SESSION['COMPNAME'])) {
            $compName = $_SESSION['COMPNAME'];
        } else {
            $compName = '';
        }
    }

    // 社員CD
    if (isset($post['searchStaffCode'])) {
        $staffCode = $post['searchStaffCode'];
    } else {
        $staffCode = '';
    }

    // 社員名
    if (isset($post['searchPersonName'])) {
        $personName = $post['searchPersonName'];
    } else {
        $personName = '';
    }

    if ($appliReason == APPLI_REASON_EXCHANGE_SIZE) {   // サイズ交換の場合はワンサイズ展開のサイズIDを取得

        $sizeAry = array();

        $sql = "";
        $sql .= " SELECT";
        $sql .=     " SizeID";
        $sql .= " FROM";
        $sql .=     " M_Size";
        $sql .= " WHERE";
        $sql .=     " Del = " . DELETE_OFF;
        $sql .= " AND";
        $sql .=     " Size2 IS NULL ";

        $result = db_Read($dbConnect, $sql);

        if (is_array($result) && count($result) != 0) {
            foreach ($result as $key => $val) {
                $sizeAry[] = $val['SizeID'];
            }
        } 
    }

    // 社員の件数を取得する
    $sql  = "";
    $sql .= " SELECT";
    $sql .=     " COUNT(DISTINCT S.StaffSeqID) as count_staff";
    $sql .= " FROM";
    $sql .=     " M_Staff S";
    $sql .=     " INNER JOIN";
    $sql .=     " M_Comp C";
    $sql .=     " ON";
    $sql .=         " S.CompID = C.CompID";
    if (in_array($appliReason, $koukanReasonAry) || in_array($appliReason, $henpinReasonAry)) {
        $sql .= " INNER JOIN";
        $sql .=     " T_Staff ts";
        $sql .= " ON";
        $sql .=     " S.StaffSeqID = ts.StaffID";
        $sql .= " INNER JOIN";
        $sql .=     " T_Staff_Details tsd";
        $sql .= " ON";
        $sql .=     " S.StaffSeqID = tsd.StaffID";
        $sql .= " AND";
        $sql .=     " S.Del = " . DELETE_OFF;
        $sql .= " INNER JOIN";
        $sql .=     " T_Order_Details tod";
        $sql .= " ON";
        $sql .=     " tsd.OrderDetID = tod.OrderDetID";
        $sql .= " AND";
        $sql .=     " tod.Del = " . DELETE_OFF;

    }

    // サイズ交換の場合はSizeが１つの商品ははぶく
    if ($appliReason == APPLI_REASON_EXCHANGE_SIZE && count($sizeAry) != 0) {
        $sql .= " INNER JOIN";
        $sql .=     " M_Item mi";
        $sql .= " ON";
        $sql .=     " tod.ItemID = mi.ItemID";
        $sql .= " AND";
        $sql .=     " mi.Del = " . DELETE_OFF;
    }
    $sql .= " WHERE";
    $sql .=     " S.Del = " . DELETE_OFF . " AND C.Del = " . DELETE_OFF;
    $sql .= " AND";

    if ($_SESSION['ADMINLVL'] == 0) {   // ティファニー
        if ($_SESSION['SHOPFLAG'] == EXCEPTIONALSHOP_EXCEPTIONAL) {
            $sql .=     " C.ShopFlag = 2";         // ShopFlagが2の店舗のみを表示する
        } else {
            $sql .=     " C.ShopFlag = 1";         // ShopFlagが1の店舗のみを表示する
        }
    } else {                            // ＩＴＣ管理者
        $sql .=     " C.ShopFlag <> 0";        // ShopFlagが0以外の店舗を表示する
    }

    if (in_array($appliReason, $koukanReasonAry) || in_array($appliReason, $henpinReasonAry)) {
            $sql .= " AND";
            $sql .=     " tsd.Status IN ( " . STATUS_SHIP . ", ". STATUS_DELIVERY .")";     // ステータスが出荷済(15),納品済(16)
            ////$sql .= " AND";
            ////$sql .=     " ts.AllReturnFlag = ".COMMON_FLAG_OFF;
    }

    // サイズ交換の場合はSizeが１つの商品ははぶく
    if ($appliReason == APPLI_REASON_EXCHANGE_SIZE && count($sizeAry) != 0) {
        $sql .= " AND";
        $sql .=     " mi.SizeID NOT IN (".implode(', ', $sizeAry).")";
    }

    if ($honbuCode != '') {
        $sql .= " AND";
        $sql .=     " C.HonbuCd = '" . db_Escape($honbuCode) . "'";
    }

    if ($shibuCode != '') {
        $sql .= " AND";
        $sql .=     " C.ShibuCd = '" . db_Escape($shibuCode) . "'";
    }

    if ($compID != '') {
        $sql .= " AND";
        $sql .=     " C.CompID = '" . db_Escape($CompID) . "'";
    }

    if ($compCode != '') {
        $sql .= " AND";
        $sql .=     " C.CompCd = '" . db_Escape($compCode) . "'";
    }

    if ($compName != '') {
        $sql .= " AND";
        $sql .=     " C.CompName LIKE '%" . db_Like_Escape($compName) . "%'";
    }

	if ($corpCode != '') {
		$sql .= " AND";
		$sql .= 	" C.CorpCd = '" . db_Like_Escape($corpCode) . "'";
	}

    if ($staffCode != '') {
        $sql .= " AND";
        $sql .=     " S.StaffCode LIKE '%" . db_Like_Escape($staffCode) . "%'";
    }

    if ($personName != '') {
        $sql .= " AND";
        $sql .=     " S.PersonName LIKE '%" . db_Like_Escape($personName) . "%'";
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

    $top = $offset + $limit;
    if ($top > $allCount) {
        $limit = $limit - ($top - $allCount);
        $top   = $allCount;
    }

    // 店員の一覧を取得する
    $sql  = "";
    $sql .= " SELECT";
    $sql .=     " StaffSeqID,";
    $sql .=     " CompID,";
    $sql .=     " CompCd,";
    $sql .=     " CompName,";
    $sql .=     " StaffCode,";
    $sql .=     " PersonName,";
    $sql .=     " FukusyuID,";
    $sql .=     " GenderKbn";
    $sql .= " FROM";
    $sql .=     " (";
    $sql .=         " SELECT";
    $sql .=             " TOP " . $limit;
    $sql .=             " mco2.StaffSeqID,";
    $sql .=             " mcp2.CompID,";
    $sql .=             " mcp2.CompCd,";
    $sql .=             " mcp2.CompName,";
    $sql .=             " mcp2.CompKind,";
    $sql .=             " mco2.StaffCode,";
    $sql .=             " mco2.PersonName,";
    $sql .=             " mco2.FukusyuID,";
    $sql .=             " mco2.GenderKbn";
    $sql .=         " FROM";
    $sql .=             " M_Staff mco2";
    $sql .=             " INNER JOIN M_Comp mcp2 ON mco2.CompID=mcp2.CompID";
    $sql .=         " WHERE";
    $sql .=             " mco2.StaffSeqID IN (";
    $sql .=                         " SELECT";
    $sql .=                         " StaffSeqID";
    $sql .=                         " FROM";
    $sql .=                             " (";
    $sql .=                                 " SELECT";
    $sql .=                                     " DISTINCT";
    $sql .=                                     " TOP " . ($top);
    $sql .=                                     " mco3.StaffSeqID,";
    $sql .=                                     " mcp3.CompCd";
    $sql .=                                 " FROM";
    $sql .=                                     " M_Staff mco3";
    $sql .=                                     " INNER JOIN";
    $sql .=                                     " M_Comp mcp3";
    $sql .=                                     " ON";
    $sql .=                                         " mco3.CompID=mcp3.CompID";
    if (in_array($appliReason, $koukanReasonAry) || in_array($appliReason, $henpinReasonAry)) {
        $sql .=                                 " INNER JOIN";
        $sql .=                                 " T_Staff ts";
        $sql .=                                 " ON";
        $sql .=                                     " mco3.StaffSeqID = ts.StaffID";
        $sql .=                                 " INNER JOIN";
        $sql .=                                 " T_Staff_Details tsd";
        $sql .=                                 " ON";
        $sql .=                                     " mco3.StaffSeqID = tsd.StaffID";
        $sql .=                                 " AND";
        $sql .=                                     " mco3.Del = " . DELETE_OFF;
        $sql .=                                 " INNER JOIN";
        $sql .=                                 " T_Order_Details tod";
        $sql .=                                 " ON";
        $sql .=                                     " tsd.OrderDetID = tod.OrderDetID";
        $sql .=                                 " AND";
        $sql .=                                     " tod.Del = " . DELETE_OFF;

    }
    // サイズ交換の場合はSizeが１つの商品ははぶく
    if ($appliReason == APPLI_REASON_EXCHANGE_SIZE && count($sizeAry) != 0) {
        $sql .=                                 " INNER JOIN";
        $sql .=                                 " M_Item mi";
        $sql .=                                 " ON";
        $sql .=                                     " tod.ItemID = mi.ItemID";
        $sql .=                                 " AND";
        $sql .=                                     " mi.Del = " . DELETE_OFF;
    }
    $sql .=                                 " WHERE";
    $sql .=                                     " mco3.Del = " . DELETE_OFF;
    $sql .=                                 " AND mcp3.Del = " . DELETE_OFF;

    if (in_array($appliReason, $koukanReasonAry) || in_array($appliReason, $henpinReasonAry)) {
        $sql .=                             " AND";
        $sql .=                                 " tsd.Status IN ( " . STATUS_SHIP . ", ". STATUS_DELIVERY .")";     // ステータスが出荷済(15),納品済(16)
        ////$sql .=                             " AND";
        ////$sql .=                                 " ts.AllReturnFlag = ".COMMON_FLAG_OFF;
    }

    // サイズ交換の場合はSizeが１つの商品ははぶく
    if ($appliReason == APPLI_REASON_EXCHANGE_SIZE && count($sizeAry) != 0) {
        $sql .= " AND";
        $sql .=     " mi.SizeID NOT IN (".implode(', ', $sizeAry).")";
    }

    $sql .=                                 " AND";

    if ($_SESSION['ADMINLVL'] == 0) {   // ティファニー
        if ($_SESSION['SHOPFLAG'] == EXCEPTIONALSHOP_EXCEPTIONAL) {
            $sql .=     " mcp3.ShopFlag = 2";         // ShopFlagが2の店舗のみを表示する
        } else {
            $sql .=     " mcp3.ShopFlag = 1";         // ShopFlagが1の店舗のみを表示する
        }
    } else {                            // ＩＴＣ管理者
        $sql .=     " mcp3.ShopFlag <> 0";        // ShopFlagが0以外の店舗を表示する
    }

    if ($honbuCode != '') {
        $sql .=                             " AND";
        $sql .=                                 " mcp3.HonbuCd = '" . db_Escape($honbuCode) . "'";
    }

    if ($shibuCode != '') {
        $sql .=                             " AND";
        $sql .=                                 " mcp3.ShibuCd = '" . db_Escape($shibuCode) . "'";
    }

    if ($compID != '') {
        $sql .=                             " AND";
        $sql .=                                 " mcp3.CompID = '" . db_Escape($compID) . "'";
    }

    if ($compCode != '') {
        $sql .=                             " AND";
        $sql .=                                 " mcp3.CompCd LIKE '%" . db_Like_Escape($compCode) . "%'";
    }

    if ($compName != '') {
        $sql .=                             " AND";
        $sql .=                                 " mcp3.CompName LIKE '%" . db_Like_Escape($compName) . "%'";
    }

	if ($corpCode != '') {
		$sql .= 							" AND";
		$sql .= 								" mcp3.CorpCd = '" . db_Like_Escape($corpCode) . "'";
	}

    if ($staffCode != '') {
        $sql .=                             " AND";
        $sql .=                                 " mco3.StaffCode LIKE '%" . db_Like_Escape($staffCode) . "%'";
    }

    if ($personName != '') {
        $sql .=                             " AND";
        $sql .=                                 " mco3.PersonName LIKE '%" . db_Like_Escape($personName) . "%'";
    }

    $sql .=                                 " ORDER BY";
    $sql .=                                     " mcp3.CompCd ASC,";
    $sql .=                                 " mco3.StaffSeqID ASC";
    $sql .=                             " ) mco4";
    $sql .=                         " )";

    $sql .=                 " ORDER BY";
    $sql .=                     " mcp2.CompCd DESC,";
    $sql .=                     " mco2.StaffSeqID DESC";

    $sql .=     " ) mco";

    $sql .=     " ORDER BY";
    $sql .=         " mco.CompCd ASC,";
    $sql .=         " mco.StaffSeqID ASC";

//var_dump($sql);die;

    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合
    if (count($result) <= 0) {
        $result = array();
        return $result;
    }

    // 取得した値をHTMLエンティティ
    $resultCount = count($result);
    for ($i=0; $i<$resultCount; $i++) {

        $result[$i]['StaffID']   = castHtmlEntity($result[$i]['StaffSeqID']);
        $result[$i]['StaffCode'] = castHtmlEntity($result[$i]['StaffCode']);
        $result[$i]['StaffName'] = castHtmlEntity($result[$i]['PersonName']);
        $result[$i]['FukusyuID'] = castHtmlEntity($result[$i]['FukusyuID']);
        $result[$i]['GenderKbn'] = castHtmlEntity($result[$i]['GenderKbn']);

    }

    return  $result;

}

function getCompName($dbConnect, $post, $isLevelAdmin, $isLevelItc) {

//var_dump($post);

//var_dump($isLevelAdmin);
//var_dump($post);
    // パラメータをセット
    if ($isLevelAdmin) {

        // 本部コード
        if (isset($post['searchHonbuId'])) {
            $honbuCode = trim($post['searchHonbuId']);
        } else {
            $honbuCode = '';
        }

        // 支部コード
        if (isset($post['searchShitenId'])) {
            $shibuCode = trim($post['searchShitenId']);
        } else {
            $shibuCode = '';
        }

        // 基地局コード
        if (isset($post['searchEigyousyoId'])) {
            $compID = trim($post['searchEigyousyoId']);
        } else {
            $compID = '';
        }

        // 本部権限、支部権限なら、スコープを絞っておく
//    	if (!$isLevelItc) {
//    	    // 支店CD
//    	    if (isset($_SESSION['CORPCD'])) {
//    	        $corpCode = $_SESSION['CORPCD'];
//    	    } else {
//    	        $corpCode = '';
//    	    }
//		}


    } else {
        // 店舗CD
        if (isset($_SESSION['COMPCD'])) {
            $compCode = $_SESSION['COMPCD'];
        } else {
            $compCode = '';
        }

        // 店舗名
        if (isset($_SESSION['COMPNAME'])) {
            $compName = $_SESSION['COMPNAME'];
        } else {
            $compName = '';
        }
    }

	// 初期化
	$result = array();


//var_dump("shibuCode:" . $shibuCode);
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" HonbuCd";
	$sql .= 	",HonbuName";
	$sql .= 	",ShibuCd";
	$sql .= 	",ShibuName";

    if ($compID != '') {
		$sql .= 	",CompCd";
		$sql .= 	",CompName";
	}
	$sql .= " FROM";
	$sql .= 	" M_Comp";
	$sql .= " WHERE";
	$sql .= 	" Del = " . DELETE_OFF;

    if ($honbuCode != '') {
        $sql .= " AND";
        $sql .=     " HonbuCd = '" . db_Escape($honbuCode) . "'";
    }

    if ($shibuCode != '') {
        $sql .= " AND";
        $sql .=     " ShibuCd = '" . db_Escape($shibuCode) . "'";
    }

    if ($compID != '') {
        $sql .= " AND";
        $sql .=     " CompID = '" . db_Escape($compID) . "'";
    }
//var_dump($sql);

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	return $result;
}

?><!DOCTYPE html>
<html>
  <head>
    <META http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <script src="//code.jquery.com/jquery-1.12.4.min.js"></script>
    <link href="/main.css" rel="stylesheet" type="text/css">
    <script language="JavaScript">
    <!--
    function setStaff(staffId, staffCode, staffName, fukusyuId, genderKbn)
    {

      if(!window.opener || window.opener.closed)
      {
        window.close(); 
      } 

      window.opener.document.selectForm.staffId.value = staffId;
      window.opener.document.selectForm.searchStaffCd.value = staffCode;
      window.opener.document.selectForm.searchPersonName.value = staffName;
      window.opener.document.selectForm.searchFukusyuID.value = fukusyuId;
      window.opener.document.selectForm.searchGenderKbn.value = genderKbn;

      window.close();
      return false;

    }

    function funcFormOnKeyDown() {

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
      

      <form method="post" action="./search_staff.php?appliReason=<?php isset($appliReason) ? print($appliReason) : print('&#123;appliReason&#125;'); ?>" name="pagingForm" onkeydown="javascript:return funcFormOnKeyDown();">
        <div id="contents">
          <p class="fbold tb_1" style="margin-top:20px; font-size:120%">職員検索</p>
          <table border="0" width="450" cellpadding="0" cellspacing="0" class="tb_1">
            <tr>
              <td colspan="5" height="20">※ 検索したい職員の情報を入力してください。（部分一致）</td>
            </tr>
            <tr>
              <td width="70"  align="left" class="line">事業部コード</td>
              <td width="80"  align="left" class="line"><?php isset($searchHonbuId) ? print($searchHonbuId) : print('&#123;searchHonbuId&#125;'); ?></td>
              <td width="50"  align="left" class="line">事業部名</td>
              <td width="150" align="left" class="line"><?php isset($searchHonbuName) ? print($searchHonbuName) : print('&#123;searchHonbuName&#125;'); ?></td>
            </tr>

            <tr>
              <td width="70"  align="left" class="line">エリアコード</td>
              <td width="80"  align="left" class="line"><?php isset($searchShitenId) ? print($searchShitenId) : print('&#123;searchShitenId&#125;'); ?></td>
              <td width="50"  align="left" class="line">エリア名</td>
              <td width="150" align="left" class="line"><?php isset($searchShitenName) ? print($searchShitenName) : print('&#123;searchShitenName&#125;'); ?></td>
            </tr>

            <tr>
              <td width="70"  align="left" class="line">施設コード</td>
              <td width="80"  align="left" class="line"><?php isset($searchCompCode) ? print($searchCompCode) : print('&#123;searchCompCode&#125;'); ?></td>
              <td width="50"  align="left" class="line">施設名</td>
              <td width="150" align="left" class="line"><?php isset($searchCompName) ? print($searchCompName) : print('&#123;searchCompName&#125;'); ?></td>

              <td width="100" rowspan="2" align="center" class="line"><input name="staff_btn" type="button" value="　検索　" onclick="document.pagingForm.searchFlg.value='1'; document.pagingForm.submit(); return false;"></td>
            </tr>
            <tr>
              <td width="70"  align="left" class="line">職員コード</td>
              <td width="80"  align="left" class="line"><input type="text" name="searchStaffCode" value="<?php isset($searchStaffCode) ? print($searchStaffCode) : print('&#123;searchStaffCode&#125;'); ?>" size="10"></td>
              <td width="50"  align="left" class="line">氏名</td>
              <td width="150" align="left" class="line"><input type="text" name="searchPersonName" value="<?php isset($searchPersonName) ? print($searchPersonName) : print('&#123;searchPersonName&#125;'); ?>" size="24"></td>
            </tr>
            <input type="hidden" name="searchHonbuId" value="<?php isset($searchHonbuId) ? print($searchHonbuId) : print('&#123;searchHonbuId&#125;'); ?>">
            <input type="hidden" name="searchShitenId" value="<?php isset($searchShitenId) ? print($searchShitenId) : print('&#123;searchShitenId&#125;'); ?>">
            <input type="hidden" name="searchEigyousyoId" value="<?php isset($searchEigyousyoId) ? print($searchEigyousyoId) : print('&#123;searchEigyousyoId&#125;'); ?>">
            
          </table>
<?php if(!$isSearched) { ?>
<?php if($isEmpty) { ?>
	           <span style="color:red">該当する職員データはありません。</span>
<?php } ?>
<?php } ?>
<?php if($isSearched) { ?>
          <p class="fbold tb_1" style="font-size:120%">◆該当職員</p>
          <table border="0" width="410" cellpadding="0" cellspacing="2" class="tb_1">
            <tr>
              <th width="80">職員コード</th>
              <th width="120">氏名</th>
              <th width="80">施設コード</th>
              <th width="130">施設名</th>
            </tr>
<?php for ($i1_staffs=0; $i1_staffs<count($staffs); $i1_staffs++) { ?>
            <tr height="20">
              <td class="line2" width="80"  align="center"><a href="#" onclick="setStaff('<?php isset($staffs[$i1_staffs]['StaffID']) ? print($staffs[$i1_staffs]['StaffID']) : print('&#123;staffs.StaffID&#125;'); ?>', '<?php isset($staffs[$i1_staffs]['StaffCode']) ? print($staffs[$i1_staffs]['StaffCode']) : print('&#123;staffs.StaffCode&#125;'); ?>', '<?php isset($staffs[$i1_staffs]['StaffName']) ? print($staffs[$i1_staffs]['StaffName']) : print('&#123;staffs.StaffName&#125;'); ?>', '<?php isset($staffs[$i1_staffs]['FukusyuID']) ? print($staffs[$i1_staffs]['FukusyuID']) : print('&#123;staffs.FukusyuID&#125;'); ?>', '<?php isset($staffs[$i1_staffs]['GenderKbn']) ? print($staffs[$i1_staffs]['GenderKbn']) : print('&#123;staffs.GenderKbn&#125;'); ?>');"><?php isset($staffs[$i1_staffs]['StaffCode']) ? print($staffs[$i1_staffs]['StaffCode']) : print('&#123;staffs.StaffCode&#125;'); ?></a></td>
              <td class="line2" width="120"  align="left"><?php isset($staffs[$i1_staffs]['StaffName']) ? print($staffs[$i1_staffs]['StaffName']) : print('&#123;staffs.StaffName&#125;'); ?></td>
              <td class="line2" width="80"  align="center"><?php isset($staffs[$i1_staffs]['CompCd']) ? print($staffs[$i1_staffs]['CompCd']) : print('&#123;staffs.CompCd&#125;'); ?></td>
              <td class="line2" width="130" align="left"><?php isset($staffs[$i1_staffs]['CompName']) ? print($staffs[$i1_staffs]['CompName']) : print('&#123;staffs.CompName&#125;'); ?></td>
            </tr>
<?php } ?>
          </table>
          <br>
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
                <a href="javascript:window.close()"><img src="./img/close.gif" width="112" height="32" border="0"></a></div>
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

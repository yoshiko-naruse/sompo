<?php
/*
 * 職員選択画面
 * select_staff.src.php
 *
 * create 2008/04/25 W.takasaki
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


/* ../include/commonFunc.php start */

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






/* ../include/commonFunc.php end */



// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST);

$title = '';
$title .= '職員選択　';

$isSizeChange = false;

// 取引区分をセット
if (isset($post['appliReason'])) {

    $appliReason = trim($post['appliReason']); 

    $isExchangeFirst = false;		// 初回サイズ交換フラグON
    $exchangeGuideMessage = '';		// 初回サイズ交換時に表示するメッセージ文字列

    $title = '';
    if ($isLevelAdmin) {
        $title .= '代行入力　';
    }
    $title .= '職員選択　';

    switch($appliReason) {

        case APPLI_REASON_ORDER_BASE:                   // 発注（そんぽの家系／ラヴィーレ系）
            $isMenuOrder = true;                        // 発注のメニューをアクティブに
            $nextUrl = './hachu/hachu_shinsei.php';
            if ($isLevelNormal == true) {
                switch($_SESSION['COMPKIND']) {
                    case '1':		// そんぽの家系
                        $title .= '(発注申請・そんぽの家系)';
                        break;
                    case '2':		// ラヴィーレ系
                        $title .= '(発注申請・ラヴィーレ系)';
                        break;
                    default:
                        $title .= '(発注申請・そんぽの家系／ラヴィーレ系)';
                        break;
                }
            } else {
                $title .= '(発注申請・そんぽの家系／ラヴィーレ系)';
            }
            break;

        case APPLI_REASON_ORDER_GRADEUP:                // 発注（グレードアップタイ）
            $isMenuOrder = true;                        // 発注のメニューをアクティブに
            $nextUrl = './hachu/hachu_shinsei.php';
            $title .= '(発注申請・グレードアップタイ)';
            break;

        case APPLI_REASON_ORDER_FRESHMAN:               // 発注（新入社員）
            $isMenuOrder = true;                        // 発注のメニューをアクティブに
            $nextUrl = './hachu/hachu_shinsei.php';
            $title .= '(発注申請・新入社員)';
            break;

        case APPLI_REASON_ORDER_PERSONAL:               // 発注（個別発注申請）
            $isMenuOrder = true;                        // 発注のメニューをアクティブに
            $nextUrl = './hachu/hachu_shinsei.php';
            $title .= '(発注申請・個別発注申請)';
            break;

        case APPLI_REASON_EXCHANGE_FIRST:               // 交換：初回サイズ
            $isMenuExchange = true;                     // 交換のメニューをアクティブに
            $nextUrl = './koukan/koukan_shinsei.php';
            $title .= '(初回サイズ交換)';
            $isExchangeFirst = true;                    // 初回サイズ交換フラグON
// 一時的に日数制限を解除 by T.Uno at 2023/04/27
//            $exchangeGuideMessage = '出荷から' . EXCHANGE_TERM_DAY . '日以内の商品を保持している職員のみ表示されます。';
            $exchangeGuideMessage = '';
            $isSizeChange = true;                       // サイズ交換フラグON
            break;

        case APPLI_REASON_EXCHANGE_SIZE:                // 交換：サイズ
            $isMenuExchange = true;                     // 交換のメニューをアクティブに
            $nextUrl = './koukan/koukan_shinsei.php';
            $title .= '(サイズ交換)';
            $isSizeChange = true;                       // サイズ交換フラグON
            break;

//        case APPLI_REASON_EXCHANGE_INFERIORITY:         // 交換：不良品
//            $isMenuExchange = true;                     // 交換のメニューをアクティブに
//            $nextUrl = './koukan/koukan_shinsei.php';
//            $title .= '(不良品交換)';
//            break;

        case APPLI_REASON_EXCHANGE_LOSS:                // 交換：紛失
            $isMenuExchange = true;                     // 交換のメニューをアクティブに
            $nextUrl = './koukan/koukan_shinsei.php';
            $title .= '(紛失交換)';
            break;

        case APPLI_REASON_EXCHANGE_BREAK:               // 交換：汚損・破損
            $isMenuExchange = true;                     // 交換のメニューをアクティブに
            $nextUrl = './koukan/koukan_shinsei.php';
            $title .= '(汚損・破損交換)';
            break;

        case APPLI_REASON_RETURN_RETIRE:                // 返却：退職・異動
            $isMenuReturn = true;                       // 返却のメニューをアクティブに
            $nextUrl = './henpin/henpin_shinsei.php';
            $title .= '(退職・異動返却)';
            break;
 
        case APPLI_REASON_RETURN_OTHER:                 // 返却：その他
            $isMenuReturn = true;                       // 返却のメニューをアクティブに
            $nextUrl = './henpin/henpin_shinsei.php';
            $title .= '(その他返却)';
            break;

        default:
            // TOP画面に強制遷移
            redirectTop();
            break;
    }

} else {    // 発注区分がなければ、TOP画面に強制遷移
    // TOP画面に強制遷移
    redirectTop();
}

// ------- ここから ---------------------
$honbuID = '';
$honbuName = '';
$shitenID = '';
$shitenName = '';
$eigyousyoID = '';
$eigyousyoName = '';
$isLevelShibu = false;

// 本部検索時は支部検索値クリア
if (isset($post['searchSelectflg']) && $post['searchSelectflg'] == '1') {
	$post['isSearched']='';
	$post['searchShitenId']='';
	$post['searchEigyousyoId']='';
	$post['searchStaffCode']='';
	$post['searchPersonName']='';
}

// 支部検索時は支部検索値クリア
if (isset($post['searchSelectflg']) && $post['searchSelectflg'] == '2') {
	$post['isSearched']='';
	$post['searchEigyousyoId']='';
	$post['searchStaffCode']='';
	$post['searchPersonName']='';
}

// 基地検索時は基地検索値クリア
if (isset($post['searchSelectflg']) && $post['searchSelectflg'] == '3') {
	$post['isSearched']='';
	$post['searchStaffCode']='';
	$post['searchPersonName']='';
}

// 職員検索時は検索値クリア
if (isset($post['searchFlg'])) {
	$searchFlg=$post['searchFlg'];
}

//// 支部検索時は支部検索値クリア
//if (isset($post['searchSelectflg']) && $post['searchSelectflg'] == '2') {
//	$post['searchShitenId']='';
//}

//var_dump('searchHonbuId:' . $post['searchHonbuId']);
//var_dump('searchShitenId:' . $post['searchShitenId']);
//var_dump('searchEigyousyoId:' . $post['searchEigyousyoId']);

if ($isLevelAdmin == true) {

	if ($isLevelItc == true) { 
		$honbuID = trim($post['searchHonbuId']);

		// 支店リスト取得
		$honbu = castListbox_Honbu(getHonbuName($dbConnect), $honbuID);

	} else { 
		$honbuID   = trim($_SESSION['HONBUCD']);
		$honbuName = trim($_SESSION['HONBUNAME']);
	}

} else {
	$honbuID   = trim($_SESSION['HONBUCD']);
	$honbuName = trim($_SESSION['HONBUNAME']);

}

if ($isLevelAdmin == true) {

	if ($isLevelItc == true || $isLevelHonbu == true) { 

		$shitenID = trim($post['searchShitenId']);

		// 支店リスト取得
		$shiten = castListbox_Shibu(getShitenName($dbConnect, $honbuID), $shitenID);

	} else {
		$shitenID     = trim($_SESSION['SHIBUCD']);
		$shitenName   = trim($_SESSION['SHIBUNAME']);
		$isLevelShibu = true;

	}
} else {
	$shitenID     = trim($_SESSION['SHIBUCD']);
	$shitenName   = trim($_SESSION['SHIBUNAME']);
	$isLevelShibu = true;

}

$eigyousyoID = trim($post['searchEigyousyoId']);

// グレードアップタイを選択されている場合はラヴィーレ系のみを選択するようコード設定
if ($appliReason == APPLI_REASON_ORDER_GRADEUP) {
	$compKind = '2';	// ラヴィーレ系
} else {
	$compKind = '';
}

// 営業所リスト取得
$eigyousyo = castListbox(getEigyousyoName($dbConnect, $honbuID, $shitenID, $compKind), $eigyousyoID);

// 一般権限の場合は店舗CD,店舗名セット
if ($isLevelNormal) {

    $compCd = '';
    if (isset($_SESSION['COMPCD'])) {
        $compCd = $_SESSION['COMPCD'];
    }

    $eigyousyoID = '';
    if (isset($_SESSION['COMPID'])) {
        $eigyousyoID = $_SESSION['COMPID'];
    }

    $compName = '';
    if (isset($_SESSION['COMPNAME'])) {
        $compName = $_SESSION['COMPNAME'];
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

$nowPage = 1;

$isSearched = trim($post['isSearched']);

// 検索時は1ページ目に戻す
if ($post['searchFlg'] == 1) {
	$nowPage = 1;
	$isSearched = true;
}

if ($post['nowPage'] != '') {
	$nowPage = trim($post['nowPage']);
}

if ($isSearched == true) {

	// 表示する社員一覧を取得
	$staffs = getStaffData($dbConnect, $post, $nowPage, $allCount, $appliReason, $isLevelAdmin, $isLevelItc, $isLevelHonbu);

	// ページングのセッティング
	$paging = setPaging($nowPage, PAGE_PER_DISPLAY_SEARCH_STAFF, $allCount);

	// 社員が０件の場合
	if ($allCount <= 0) {
        $isEmpty    = true;
		$isSearched = false;
	}

}

function getHonbuName($dbConnect) {

	// 初期化
	$result = array();

	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" HonbuCd,";
	$sql .= 	" HonbuName";
	$sql .= " FROM";
	$sql .= 	" M_Comp";
	$sql .= " WHERE";
	$sql .= 	" Del = " . DELETE_OFF;
	$sql .= " AND";
	$sql .= 	" ShopFlag = 1";	// 対象データ

	$sql .= " GROUP BY";
	$sql .= 	" HonbuCd,";
	$sql .= 	" HonbuName";
	$sql .= " ORDER BY";
	$sql .= 	" HonbuCd,";
	$sql .= 	" HonbuName";

//	$sql .= " AND";
//	$sql .= 	" ShopKbn = 0";		// 支店

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	return $result;
}

function getShitenName($dbConnect, $honbuID='') {

	// 初期化
	$result = array();

	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" ShibuCd,";
	$sql .= 	" ShibuName";
	$sql .= " FROM";
	$sql .= 	" M_Comp";
	$sql .= " WHERE";
	$sql .= 	" Del = " . DELETE_OFF;
	if (isset($honbuID) &&  $honbuID != '') {
		$sql .= " AND";
		$sql .= 	" HonbuCd = " . $honbuID;
	}
	$sql .= " AND";
	$sql .= 	" ShopFlag = 1";	// 対象データ
	$sql .= " GROUP BY";
	$sql .= 	" ShibuCd,";
	$sql .= 	" ShibuName";
	$sql .= " ORDER BY";
	$sql .= 	" ShibuCd,";
	$sql .= 	" ShibuName";
	//$sql .= " AND";
	//$sql .= 	" ShopKbn = 0";		// 支店
//var_dump($sql);
	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	return $result;
}

function getEigyousyoName($dbConnect, $honbuID='', $shitenID='', $compKind='') {

	// 初期化
	$result = array();

	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" CompID,";
	$sql .= 	" CompName";
	$sql .= " FROM";
	$sql .= 	" M_Comp";
	$sql .= " WHERE";
	$sql .= 	" Del = " . DELETE_OFF;
	if (isset($honbuID) &&  $honbuID != '') {
		$sql .= " AND";
		$sql .= 	" HonbuCd = " . $honbuID;
	}
	if (isset($shitenID) &&  $shitenID != '') {
		$sql .= " AND";
		$sql .= 	" ShibuCd = " . $shitenID;
	}
	if (isset($compKind) &&  $compKind != '') {
		$sql .= " AND";
		$sql .= 	" CompKind = " . $compKind;
	}
	$sql .= " AND";
	$sql .= 	" ShopFlag = 1";	// 対象データ
//	$sql .= " AND";
//	$sql .= 	" ShopKbn = 1";		// 営業所
//	$sql .= " AND";
//	$sql .= 	" AgencyID = '" . db_Escape($compID) . "'";		// 支店

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	return $result;
}

function castListbox($compData, $selectedValue = '') {

	// 初期化
	$selectDatas = array();

	// $sizeDatasが配列でなければ終了
	if (!is_array($compData)) {
		return  $selectDatas;
	}

	// $compDataにデータが1件もなければ終了
	if (count($compData) <= 0) {
		return  $selectDatas;
	}

	// リストボックスを生成するための値に成型
	$listCount = count($compData);
	for ($i=0; $i<$listCount; $i++) {

		$selectDatas[$i]['selected'] = false;
		if (trim($compData[$i]['CompID']) == $selectedValue) {
			$selectDatas[$i]['selected'] = true;
		}

		$selectDatas[$i]['value'] = trim($compData[$i]['CompID']);

		// HTMLエンティティ処理
		$selectDatas[$i]['display'] = htmlspecialchars($compData[$i]['CompName'], HTMLENTITY_QUOTE_STYLE, HTMLENTITY_ENCODE);

	}

	return $selectDatas;

}

function castListbox_Honbu($honbuData, $selectedValue = '') {

	// 初期化
	$selectDatas = array();

	// $sizeDatasが配列でなければ終了
	if (!is_array($honbuData)) {
		return  $selectDatas;
	}

	// $compDataにデータが1件もなければ終了
	if (count($honbuData) <= 0) {
		return  $selectDatas;
	}

	// リストボックスを生成するための値に成型

	$listCount = count($honbuData);
	for ($i=0; $i<$listCount; $i++) {

		$selectDatas[$i]['selected'] = false;
		if (trim($honbuData[$i]['HonbuCd']) == $selectedValue) {
			$selectDatas[$i]['selected'] = true;

		}
//var_dump($honbuData);

		$selectDatas[$i]['value'] = trim($honbuData[$i]['HonbuCd']);

		// HTMLエンティティ処理
		$selectDatas[$i]['display'] = htmlspecialchars($honbuData[$i]['HonbuName'], HTMLENTITY_QUOTE_STYLE, HTMLENTITY_ENCODE);

	}

	return $selectDatas;

}

function castListbox_Shibu($shibuData, $selectedValue = '') {

	// 初期化
	$selectDatas = array();

	// $sizeDatasが配列でなければ終了
	if (!is_array($shibuData)) {
		return  $selectDatas;
	}

	// $compDataにshibuDataが1件もなければ終了
	if (count($shibuData) <= 0) {
		return  $selectDatas;
	}

	// リストボックスを生成するための値に成型

	$listCount = count($shibuData);
	for ($i=0; $i<$listCount; $i++) {

		$selectDatas[$i]['selected'] = false;
		if (trim($shibuData[$i]['ShibuCd']) == $selectedValue) {
			$selectDatas[$i]['selected'] = true;

		}
//var_dump($shibuData);

		$selectDatas[$i]['value'] = trim($shibuData[$i]['ShibuCd']);

		// HTMLエンティティ処理
		$selectDatas[$i]['display'] = htmlspecialchars($shibuData[$i]['ShibuName'], HTMLENTITY_QUOTE_STYLE, HTMLENTITY_ENCODE);

	}

	return $selectDatas;

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
function getStaffData($dbConnect, $post, $nowPage, &$allCount, $appliReason, $isLevelAdmin, $isLevelItc, $isLevelHonbu) {

    // 取引理由配列
    // 交換
    $koukanReasonAry = array(APPLI_REASON_EXCHANGE_FIRST, 
                            APPLI_REASON_EXCHANGE_SIZE, 
                            APPLI_REASON_EXCHANGE_INFERIORITY, 
                            APPLI_REASON_EXCHANGE_LOSS, 
                            APPLI_REASON_EXCHANGE_BREAK
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

        if (!$isLevelItc) {

            if ($isLevelHonbu) {
                // 本部権限
                if (isset($_SESSION['HONBUCD'])) {
                    $honbuCode = $_SESSION['HONBUCD'];
                } else {
                    $honbuCode = '';
                }

            } else {
                // 支部権限
                if (isset($_SESSION['SHIBUCD']) && isset($_SESSION['HONBUCD'])) {
                    $honbuCode = $_SESSION['HONBUCD'];
                    $shibuCode = $_SESSION['SHIBUCD'];
                } else {
                    $honbuCode = '';
                    $shibuCode = '';
                }
            }
        }

    } else {
        // 本部CD
        if (isset($_SESSION['HONBUCD'])) {
            $honbuCode = $_SESSION['HONBUCD'];
        } else {
            $honbuCode = '';
        }

        // 支部CD
        if (isset($_SESSION['SHIBUCD'])) {
            $shibuCode = $_SESSION['SHIBUCD'];
        } else {
            $shibuCode = '';
        }

        // 基地CD
        if (isset($_SESSION['COMPCD'])) {
            $compCode = $_SESSION['COMPCD'];
        } else {
            $compCode = '';
        }

        //// 店舗名
        //if (isset($_SESSION['COMPNAME'])) {
        //    $compName = $_SESSION['COMPNAME'];
        //} else {
        //    $compName = '';
        //}
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

    if ($appliReason == APPLI_REASON_EXCHANGE_FIRST || $appliReason == APPLI_REASON_EXCHANGE_SIZE) {   // サイズ交換の場合はワンサイズ展開のサイズIDを取得
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
    if ($appliReason == APPLI_REASON_ORDER_GRADEUP) {
        $sql .= " AND";
        $sql .=     " C.CompKind = 2";
    }
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
        // 初回サイズ交換の場合は、出荷から一定期間内であれば交換可能とする。
        if ($appliReason == APPLI_REASON_EXCHANGE_FIRST) {
            // 出荷日から起算して10日以上経過している商品は表示しない
// 一時的に日数制限を解除 by T.Uno at 2023/04/27
//            $sql .= " AND";
//            $sql .=     " CONVERT(char, tod.ShipDay, 111) >= '" . date("Y/m/d", strtotime(EXCHANGE_TERM)) . "'";
        }
    }

    // サイズ交換の場合はSizeが１つの商品ははぶく
    if (($appliReason == APPLI_REASON_EXCHANGE_FIRST || $appliReason == APPLI_REASON_EXCHANGE_SIZE) && count($sizeAry) != 0) {
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
    if (($appliReason == APPLI_REASON_EXCHANGE_FIRST || $appliReason == APPLI_REASON_EXCHANGE_SIZE) && count($sizeAry) != 0) {
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
        $sql .=     " C.CompID = '" . db_Escape($compID) . "'";
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
    $sql .=     " HonbuCd,";
    $sql .=     " HonbuName,";
    $sql .=     " ShibuCd,";
    $sql .=     " ShibuName,";
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
    $sql .=             " mcp2.HonbuCd,";
    $sql .=             " mcp2.HonbuName,";
    $sql .=             " mcp2.ShibuCd,";
    $sql .=             " mcp2.ShibuName,";
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
    if ($appliReason == APPLI_REASON_ORDER_GRADEUP) {
        $sql .=                                 " AND";
        $sql .=                                     " mcp3.CompKind = 2";
    }
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
        // 初回サイズ交換の場合は、出荷から一定期間内であれば交換可能とする。
        if ($appliReason == APPLI_REASON_EXCHANGE_FIRST) {
            // 出荷日から起算して10日以上経過している商品は表示しない
// 一時的に日数制限を解除 by T.Uno at 2023/04/27
//            $sql .=                                 " AND";
//            $sql .=                                     " CONVERT(char, tod.ShipDay, 111) >= '" . date("Y/m/d", strtotime(EXCHANGE_TERM)) . "'";
        }
    }
    // サイズ交換の場合はSizeが１つの商品ははぶく
    if (($appliReason == APPLI_REASON_EXCHANGE_FIRST || $appliReason == APPLI_REASON_EXCHANGE_SIZE) && count($sizeAry) != 0) {
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
    if (($appliReason == APPLI_REASON_EXCHANGE_FIRST || $appliReason == APPLI_REASON_EXCHANGE_SIZE) && count($sizeAry) != 0) {
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

        $result[$i]['StaffID']   = castHtmlEntity($result[$i]['StaffSeqID']);
        $result[$i]['StaffCode'] = castHtmlEntity($result[$i]['StaffCode']);
        $result[$i]['StaffName'] = castHtmlEntity($result[$i]['PersonName']);
        $result[$i]['FukusyuID'] = castHtmlEntity($result[$i]['FukusyuID']);
        $result[$i]['GenderKbn'] = castHtmlEntity($result[$i]['GenderKbn']);

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
    function setStaff(staffId, nextUrl) {

      document.pagingForm.staffId.value = staffId;
      document.pagingForm.searchFlg.value = '1';
      //document.pagingForm.searchHonbuId.value = '';
      //document.pagingForm.searchShitenId.value = '';
      //document.pagingForm.searchEigyousyoId.value = '';
      document.pagingForm.action = nextUrl; 
      document.pagingForm.submit();
     
      return false;

    }
    // -->
    </script>
  </head>


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
        

        <form method="post" action="./select_staff.php" name="pagingForm">
          <div id="contents">
            <h1><?php isset($title) ? print($title) : print('&#123;title&#125;'); ?></h1>
            <table border="0" width="700" cellpadding="0" cellspacing="0" class="tb_1">
<?php if(!$isLevelAdmin) { ?>
              <tr height="40">
                <td width="100" class="line"><span class="fbold">事業部</span></td>
                <td width="450" class="line"><?php isset($honbuID) ? print($honbuID) : print('&#123;honbuID&#125;'); ?>&nbsp;&nbsp;<?php isset($honbuName) ? print($honbuName) : print('&#123;honbuName&#125;'); ?></td>
                <td width="150" class="line">&nbsp;</td>
              </tr>
              <tr height="40">
                <td width="100" class="line"><span class="fbold">エリア</span></td>
                <td width="450" class="line"><?php isset($shitenID) ? print($shitenID) : print('&#123;shitenID&#125;'); ?>&nbsp;&nbsp;<?php isset($shitenName) ? print($shitenName) : print('&#123;shitenName&#125;'); ?></td>
                <td width="150" class="line">&nbsp;</td>
              </tr>
              <tr height="40">
                <td width="100" class="line"><span class="fbold">施設</span></td>
                <td width="450" class="line"><?php isset($compCd) ? print($compCd) : print('&#123;compCd&#125;'); ?>&nbsp;&nbsp;<?php isset($compName) ? print($compName) : print('&#123;compName&#125;'); ?></td>
                <td width="150" class="line">&nbsp;</td>
              </tr>
<?php } ?>
<?php if($isLevelAdmin) { ?>
              <input type="hidden" name="searchSelectflg">
<?php if($isLevelItc) { ?>
              <tr height="40">
                <td width="100" align="left"><span class="fbold">事業部</span></td>
                <td align="left" colspan="2">
                  <select name="searchHonbuId" class="select-300px" onChange="document.pagingForm.action='./select_staff.php'; document.pagingForm.searchSelectflg.value='1'; document.pagingForm.submit(); return false;">
                    <option value="">--事業部選択--</option>
<?php for ($i1_honbu=0; $i1_honbu<count($honbu); $i1_honbu++) { ?>
<?php if($honbu[$i1_honbu]['selected']) { ?>
                    <option value="<?php isset($honbu[$i1_honbu]['value']) ? print($honbu[$i1_honbu]['value']) : print('&#123;honbu.value&#125;'); ?>" selected="selected"><?php isset($honbu[$i1_honbu]['display']) ? print($honbu[$i1_honbu]['display']) : print('&#123;honbu.display&#125;'); ?></option>
<?php } ?>
<?php if(!$honbu[$i1_honbu]['selected']) { ?>
                    <option value="<?php isset($honbu[$i1_honbu]['value']) ? print($honbu[$i1_honbu]['value']) : print('&#123;honbu.value&#125;'); ?>"><?php isset($honbu[$i1_honbu]['display']) ? print($honbu[$i1_honbu]['display']) : print('&#123;honbu.display&#125;'); ?></option>
<?php } ?>
<?php } ?>
                  </select>
                </td>
              </tr>
<?php } ?>
<?php if(!$isLevelItc) { ?>
              <tr height="40">
                <td width="100" class="line"><span class="fbold">事業部</span></td>
                <td width="450" class="line"><?php isset($honbuID) ? print($honbuID) : print('&#123;honbuID&#125;'); ?>&nbsp;&nbsp;<?php isset($honbuName) ? print($honbuName) : print('&#123;honbuName&#125;'); ?></td>
                <td width="150" class="line">&nbsp;</td>
              </tr>
<?php } ?>
<?php if($isLevelShibu) { ?>
              <tr height="40">
                <td width="100" class="line"><span class="fbold">エリア</span></td>
                <td width="450" class="line"><?php isset($shitenID) ? print($shitenID) : print('&#123;shitenID&#125;'); ?>&nbsp;&nbsp;<?php isset($shitenName) ? print($shitenName) : print('&#123;shitenName&#125;'); ?></td>
                <td width="150" class="line">&nbsp;</td>
              </tr>
<?php } ?>
<?php if(!$isLevelShibu) { ?>
              <tr height="40">
                <td width="100" align="left"><span class="fbold">エリア</span></td>
                <td align="left" colspan="2">
                  <select name="searchShitenId" class="select-300px" onChange="document.pagingForm.action='./select_staff.php'; document.pagingForm.searchSelectflg.value='2'; document.pagingForm.submit(); return false;">
                    <option value="">--エリア選択--</option>
<?php for ($i1_shiten=0; $i1_shiten<count($shiten); $i1_shiten++) { ?>
<?php if($shiten[$i1_shiten]['selected']) { ?>
                    <option value="<?php isset($shiten[$i1_shiten]['value']) ? print($shiten[$i1_shiten]['value']) : print('&#123;shiten.value&#125;'); ?>" selected="selected"><?php isset($shiten[$i1_shiten]['display']) ? print($shiten[$i1_shiten]['display']) : print('&#123;shiten.display&#125;'); ?></option>
<?php } ?>
<?php if(!$shiten[$i1_shiten]['selected']) { ?>
                    <option value="<?php isset($shiten[$i1_shiten]['value']) ? print($shiten[$i1_shiten]['value']) : print('&#123;shiten.value&#125;'); ?>"><?php isset($shiten[$i1_shiten]['display']) ? print($shiten[$i1_shiten]['display']) : print('&#123;shiten.display&#125;'); ?></option>
<?php } ?>
<?php } ?>
                  </select>
                </td>
              </tr>
<?php } ?>
              <tr height="40">
                <td width="100" align="left"><span class="fbold">施設</span></td>
                <td align="left" colspan="2">
                  <select name="searchEigyousyoId" class="select-300px" onChange="document.pagingForm.action='./select_staff.php'; document.pagingForm.searchSelectflg.value='3'; document.pagingForm.submit(); return false;">
                    <option value="">--施設選択--</option>
<?php for ($i1_eigyousyo=0; $i1_eigyousyo<count($eigyousyo); $i1_eigyousyo++) { ?>
<?php if($eigyousyo[$i1_eigyousyo]['selected']) { ?>
                    <option value="<?php isset($eigyousyo[$i1_eigyousyo]['value']) ? print($eigyousyo[$i1_eigyousyo]['value']) : print('&#123;eigyousyo.value&#125;'); ?>" selected="selected"><?php isset($eigyousyo[$i1_eigyousyo]['display']) ? print($eigyousyo[$i1_eigyousyo]['display']) : print('&#123;eigyousyo.display&#125;'); ?></option>
<?php } ?>
<?php if(!$eigyousyo[$i1_eigyousyo]['selected']) { ?>
                    <option value="<?php isset($eigyousyo[$i1_eigyousyo]['value']) ? print($eigyousyo[$i1_eigyousyo]['value']) : print('&#123;eigyousyo.value&#125;'); ?>"><?php isset($eigyousyo[$i1_eigyousyo]['display']) ? print($eigyousyo[$i1_eigyousyo]['display']) : print('&#123;eigyousyo.display&#125;'); ?></option>
<?php } ?>
<?php } ?>
                  </select>　<span style="color:red"></span>
                </td>
              </tr>
<?php } ?>

              <tr height="40">
                <td width="100" class="line"><span class="fbold">職員コード</span></td>
                <td width="450" class="line">
                  <input type="text" name="searchStaffCode" value="<?php isset($staffCode) ? print($staffCode) : print('&#123;staffCode&#125;'); ?>" style="width:100px;" maxlength="12" placeholder="部分一致">&nbsp;&nbsp;&nbsp;<b>氏名</b>　<input type="text" name="searchPersonName" value="<?php isset($personName) ? print($personName) : print('&#123;personName&#125;'); ?>" style="width:260px;" placeholder="部分一致※苗字と名前の間は全角スペース">
                  <input type="hidden" name="staffId" value="">
                  <input type="hidden" name="appliReason" value="<?php isset($appliReason) ? print($appliReason) : print('&#123;appliReason&#125;'); ?>">
                  <input type="hidden" name="searchFukusyuID" value="">
                  <input type="hidden" name="searchGenderKbn" value="">
                </td>
                <td width="150" class="line" align="center">
                  <input name="staff_btn" type="button" value="職員検索" onclick="document.pagingForm.action='./select_staff.php'; document.pagingForm.searchFlg.value='1'; document.pagingForm.submit(); return false;">
                </td>
              </tr>
            </table>

<?php if($isSearched) { ?>
            <h3>◆職員一覧</h3>
<?php if($staffs) { ?>
            <table width="700" border="0" class="tb_1" cellpadding="0" cellspacing="3">
<?php if($isExchangeFirst) { ?>
              <tr>
                <td colspan="7"><font color="red"><?php isset($exchangeGuideMessage) ? print($exchangeGuideMessage) : print('&#123;exchangeGuideMessage&#125;'); ?></font></td>
              </tr>
<?php } ?>
              <tr>
                <th width="100">事業部</th>
                <th width="100">エリア</th>
                <th width="60">施設CD</th>
                <th width="200">施設名</th>
                <th width="60">職員コード</th>
                <th width="120">職員名</th>
                <th width="60">&nbsp;</th>
              </tr>
<?php for ($i1_staffs=0; $i1_staffs<count($staffs); $i1_staffs++) { ?>
              <tr height="20">
                <td class="line2" align="center"><?php isset($staffs[$i1_staffs]['HonbuName']) ? print($staffs[$i1_staffs]['HonbuName']) : print('&#123;staffs.HonbuName&#125;'); ?></td>
                <td class="line2" align="center"><?php isset($staffs[$i1_staffs]['ShibuName']) ? print($staffs[$i1_staffs]['ShibuName']) : print('&#123;staffs.ShibuName&#125;'); ?></td>
                <td class="line2" align="left"><?php isset($staffs[$i1_staffs]['CompCd']) ? print($staffs[$i1_staffs]['CompCd']) : print('&#123;staffs.CompCd&#125;'); ?></td>
                <td class="line2" align="center"><?php isset($staffs[$i1_staffs]['CompName']) ? print($staffs[$i1_staffs]['CompName']) : print('&#123;staffs.CompName&#125;'); ?></td>
                <td class="line2" align="left"><?php isset($staffs[$i1_staffs]['StaffCode']) ? print($staffs[$i1_staffs]['StaffCode']) : print('&#123;staffs.StaffCode&#125;'); ?></td>
                <td class="line2" align="center"><?php isset($staffs[$i1_staffs]['StaffName']) ? print($staffs[$i1_staffs]['StaffName']) : print('&#123;staffs.StaffName&#125;'); ?></td>
                <td class="line2" align="center">
<!--                  <input type="button" value="次へ" onclick="setStaff('<?php isset($staffs[$i1_staffs]['StaffID']) ? print($staffs[$i1_staffs]['StaffID']) : print('&#123;staffs.StaffID&#125;'); ?>', '<?php isset($staffs[$i1_staffs]['StaffCode']) ? print($staffs[$i1_staffs]['StaffCode']) : print('&#123;staffs.StaffCode&#125;'); ?>', '<?php isset($staffs[$i1_staffs]['StaffName']) ? print($staffs[$i1_staffs]['StaffName']) : print('&#123;staffs.StaffName&#125;'); ?>');">-->
                  <input type="button" value="次へ" onclick="setStaff('<?php isset($staffs[$i1_staffs]['StaffID']) ? print($staffs[$i1_staffs]['StaffID']) : print('&#123;staffs.StaffID&#125;'); ?>', '<?php isset($nextUrl) ? print($nextUrl) : print('&#123;nextUrl&#125;'); ?>');">
                </td>
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
<?php if(!$staffs) { ?>
            <table width="730" border="0" class="tb_1" cellpadding="0" cellspacing="3">
              <tr>
               <td colspan="9" align="center"><span style="color=red"><b>該当する申請データが登録されていません。</b></span></td>
              </tr>
             </table>
<?php } ?>
<?php } ?>


          </div>
          <input type="hidden" name="encodeHint" value="京">
          <input type="hidden" name="searchFlg">
          <input type="hidden" name="isSearched" value="<?php isset($isSearched) ? print($isSearched) : print('&#123;isSearched&#125;'); ?>">
        </form>

        <br><br><br>
        

      </div>
    </div>
  </body>
</html>

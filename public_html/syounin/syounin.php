<?php
/*
 * 承認処理画面
 * syounin.src.php
 *
 * create 2007/04/23 H.Osugi
 * update 2007/04/27 H.Osugi 返却を検索対象から外す
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



// 承認権限のないユーザが閲覧しようとするとTOPに強制遷移
if ($isLevelAdmin  == false && $isLevelAcceptation  == false) {

	$hiddens = array();
	redirectPost(HOME_URL . 'top.php', $hiddens);

}

// 初期設定
$isMenuAcceptation = true;	// 承認処理のメニューをアクティブに

// 変数の初期化 ここから ******************************************************
$searchCompCd       = '';					// 店舗コード
$searchCompName     = '';					// 店舗名
$searchCompId       = '';					// 店舗ID
$searchStaffCode    = '';					// スタッフコード
$searchPersonCode   = '';					// スタッフ氏名
$searchBarCode      = '';					// バーコード
$searchAppliDayFrom = '';					// 申請日
$searchAppliDayTo   = '';					// 申請日
$searchStatus       = array();				// ステータス
$orders             = array();

$isSearched          = false;				// 検索を行ったかどうか

// 状態の表示文字列
$DISPLAY_STATUS_APPLI            = $DISPLAY_STATUS_ACCEPTATION[1];		// 承認待
$DISPLAY_STATUS_APPLI_ADMIT      = $DISPLAY_STATUS_ACCEPTATION[3];		// 承認済
$DISPLAY_STATUS_APPLI_DENY       = $DISPLAY_STATUS_ACCEPTATION[2];		// 否認
$DISPLAY_STATUS_NOT_RETURN       = $DISPLAY_STATUS_ACCEPTATION[18];		// 返却承認待
$DISPLAY_STATUS_NOT_RETURN_ADMIT = $DISPLAY_STATUS_ACCEPTATION[20];		// 返却承認済
$DISPLAY_STATUS_NOT_RETURN_DENY  = $DISPLAY_STATUS_ACCEPTATION[19];		// 返却否認

$compCd    = '';	// 店舗番号
$compName  = '';	// 店舗名
// 変数の初期化 ここまで ******************************************************

// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST); 

if ((isset($post['searchFlg']) && $post['searchFlg'] == 1)
    || (isset($post['errorFlg']) && $post['errorFlg'] == 1)
	|| (isset($post['confFlg']) && $post['confFlg'] == 1)) {

	// 条件が指定されているか判定
	$hasCondition = checkCondition($post);
		
	if ($hasCondition == false) {
	
		$hiddens['errorName'] = 'syounin';
		$hiddens['menuName']  = 'isMenuAcceptation';
		$hiddens['returnUrl'] = 'syounin/syounin.php';
		$hiddens['errorId'][] = '902';
		$errorUrl             = HOME_URL . 'error.php';
	
		redirectPost($errorUrl, $hiddens);
	
	}

	// 表示する承認情報一覧を取得
	$orders = getOrder($dbConnect, $post, $DISPLAY_STATUS_ACCEPTATION, $isLevelItc, $isLevelHonbu);

	// 承認情報が０件の場合
	if (count($orders) <= 0) {
	
	//	$hiddens['errorName'] = 'syounin';
	//	$hiddens['menuName']  = 'isMenuAcceptation';
	//	$hiddens['returnUrl'] = 'syounin/syounin.php';
	//	$hiddens['errorId'][] = '901';
	//	$errorUrl             = HOME_URL . 'error.php';
	
	//	redirectPost($errorUrl, $hiddens);

		$isSearched = false;

	}

	$isSearched = true;

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

// スタッフ氏名
$searchPersonName = trim($post['searchPersonName']);

// 申請日
$searchAppliDayFrom = trim($post['searchAppliDayFrom']);
$searchAppliDayTo   = trim($post['searchAppliDayTo']);

// 状態
for ($i=1; $i<=6; $i++) {

	${'isSelectedStatus' . $i} = false;
	if (isset($post['searchStatus']) && is_array($post['searchStatus']) && in_array($i, $post['searchStatus'])) {
		${'isSelectedStatus' . $i} = true;
	}

}

// 表示する値の成型 ここまで +++++++++++++++++++++++++++++++++++++++++++++++++++

/*
 * 注文履歴一覧情報を取得する
 * 引数  ：$dbConnect                  => コネクションハンドラ
 *       ：$post                       => POST値
 *       ：$DISPLAY_STATUS_ACCEPTATION => 状態
 * 戻り値：$result         => 注文履歴一覧情報
 *
 * create 2007/04/23 H.Osugi
 *
 */
function getOrder($dbConnect, $post, $DISPLAY_STATUS_ACCEPTATION, $isLevelItc, $isLevelHonbu) {

	global $isLevelAdmin;	// 管理者権限の有無
	global $isLevelAgency;	// 一次代理店

	// 初期化
	$compId     = '';
	$staffCode  = '';
	$personName = '';
	$barCode    = '';
	$status     = '';
    $honbuCd = '';
    $shibuCd = '';

	// 店舗ID
	$compId = $post['searchCompId'];

	// 店舗IDに不正な値が入っていた場合
	if ($comId != '' && !ctype_digit($compId)) {
		$result = array();
		return $result;
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

	// スタッフ氏名
	$personName = $post['searchPersonName'];

	// 申請日
	$appliDayFrom = $post['searchAppliDayFrom'];
	$appliDayTo   = $post['searchAppliDayTo'];

	// YY/MM/DD以外の文字列であれば処理を終了する
	if ($appliDayFrom != '' && !ereg('^[0-9]{4}/[0-9]{2}/[0-9]{2}$', $appliDayFrom)) {
		return $result;
	}

	// YY/MM/DD以外の文字列であれば処理を終了する
	if ($appliDayTo != '' && !ereg('^[0-9]{4}/[0-9]{2}/[0-9]{2}$', $appliDayTo)) {
		return $result;
	}

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
				$status .= 	" " . STATUS_APPLI_ADMIT;		// 承認済
				break;
			case '3':
				$status .= 	" " . STATUS_APPLI_DENY;		// 否認
				break;
			case '4':
				$status .= 	" " . STATUS_NOT_RETURN;		// 返却承認待
				$status .= 	" ," . STATUS_LOSS;				// 紛失承認待
				break;
			case '5':
				$status .= 	" " . STATUS_NOT_RETURN_ADMIT;	// 返却承認済
				$status .= 	" ," . STATUS_LOSS_ADMIT;		// 紛失承認済
				break;
			case '6':
				$status .= 	" " . STATUS_NOT_RETURN_DENY;	// 返却否認
				$status .= 	" ," . STATUS_LOSS_DENY;		// 紛失否認
				break;
			default:
				break;
		}

		if ($i != $countStatus -1) {
			$status .= " ,";
		}

	}

	if ($status == '') {
		$status  .= " " . STATUS_APPLI;
		$status  .= " ," . STATUS_APPLI_ADMIT;
		$status  .= " ," . STATUS_APPLI_DENY;

// 返却を検索対象から外す　2007/04/27
//		$status  .= " ," . STATUS_NOT_RETURN;
//		$status .= 	" ," . STATUS_LOSS;
//		$status  .= " ," . STATUS_NOT_RETURN_ADMIT;
//		$status .= 	" ," . STATUS_LOSS_ADMIT;
//		$status  .= " ," . STATUS_NOT_RETURN_DENY;
//		$status .= 	" ," . STATUS_LOSS_DENY;

	}

	// 注文履歴の一覧を取得する
	$sql  = "";
	$sql .= " (";
	$sql .= 	" SELECT";
	$sql .= 		" DISTINCT";
	$sql .= 		" tor.OrderID,";
	$sql .= 		" NULL as ReturnOrderID,";
	$sql .= 		" tor.AppliDay as AppliDay,";
	$sql .= 		" tor.AppliNo,";
	$sql .= 		" NULL as ReturnAppliNo,";
	$sql .= 		" tor.AppliCompCd,";
	$sql .= 		" tor.AppliCompName,";
	$sql .= 		" tor.StaffCode,";
	$sql .= 		" tor.PersonName,";
	$sql .= 		" tor.AppliMode,";
	$sql .= 		" tor.AppliSeason,";
	$sql .= 		" tor.Status,";
	$sql .= 		" tor.AgreeReason,";
	$sql .= 		" tor.AgreeDay";
	$sql .= 	" FROM";
	$sql .= 		" T_Order tor";
	$sql .= 	" INNER JOIN";
	$sql .= 		" T_Order_Details tod";
	$sql .= 	" ON";
	$sql .= 		" tor.OrderID = tod.OrderID";
	$sql .= 	" AND";
	$sql .= 		" tod.Del = " . DELETE_OFF;

	if ($status != '') {
		$sql .= 	" AND";
		$sql .= 		" tor.Status IN (";
		$sql .= 			$status;
		$sql .= 		" )";
	}

	$sql .= 	" INNER JOIN";
	$sql .= 		" M_Comp mco";
	$sql .= 	" ON";
	$sql .= 		" tor.CompID = mco.CompID";
	$sql .= 	" AND";
	$sql .= 		" mco.Del = " . DELETE_OFF;

	$sql .= 	" WHERE";
	$sql .= 		" tor.Del = " . DELETE_OFF;
	$sql .= 	" AND";
	$sql .= 		" tor.AppliMode = " . APPLI_MODE_ORDER;		// 発注

	if ($appliDayFrom != '') {
		$sql .= 	" AND";
		$sql .= 		" CONVERT(char, tor.AppliDay, 111) >= '" . db_Escape($appliDayFrom) . "'";
	}

	if ($appliDayTo != '') {
		$sql .= 	" AND";
		$sql .= 		" CONVERT(char, tor.AppliDay, 111) <= '" . db_Escape($appliDayTo) . "'";
	}

	if ($compId != '') {
		$sql .= 	" AND";
		$sql .= 		" tor.CompID = " . db_Escape($compId);
	}

	if ($staffCode != '') {
		$sql .= 	" AND";
		$sql .= 		" tor.StaffCode = '" . db_Escape($staffCode) . "'";
	}

	if ($personName != '') {
		$sql .= 	" AND";
		$sql .= 		" tor.PersonName LIKE ('%" . db_Escape($personName) . "%')";
	}

	//if ($_SESSION['ADMINLVL'] != '1') {
	//	$sql .= 	" AND";
	//	$sql .= 		" mco.CorpCd = '" . db_Escape($_SESSION['CORPCD']) . "'";
	//	if ($isLevelAgency == true) {
	//		$sql .= 	" AND";
	//		$sql .= 		" mco.AgencyID = '" . db_Escape($_SESSION['COMPID']) . "'";
	//	}
	//}
	if ($honbuCd != '') {
		$sql .= " AND";
		$sql .= 	" mco.HonbuCd = '" . db_Escape($honbuCd) . "'";
	}

	if ($shibuCd != '') {
		$sql .= " AND";
		$sql .= 	" mco.ShibuCd = '" . db_Escape($shibuCd) . "'";
	}

	$sql .= " )";

	if ($isLevelItc) {
		$sql .= " UNION ALL";

		$sql .= " (";
		$sql .= 	" SELECT";
		$sql .= 		" DISTINCT";
		$sql .= 		" tor2.OrderID,";
		$sql .= 		" tor3.OrderID as ReturnOrderID,";
		$sql .= 		" tor2.AppliDay as AppliDay,";
		$sql .= 		" tor2.AppliNo,";
		$sql .= 		" tor3.AppliNo as ReturnAppliNo,";
		$sql .= 		" tor2.AppliCompCd,";
		$sql .= 		" tor2.AppliCompName,";
		$sql .= 		" tor2.StaffCode,";
		$sql .= 		" tor2.PersonName,";
		$sql .= 		" tor2.AppliMode,";
		$sql .= 		" tor2.AppliSeason,";
		$sql .= 		" tor2.Status,";
		$sql .= 		" tor2.AgreeReason,";
		$sql .= 		" tor2.AgreeDay";
		$sql .= 	" FROM";
		$sql .= 		" T_Order tor2";
		$sql .= 	" INNER JOIN";
		$sql .= 		" T_Order_Details tod2";
		$sql .= 	" ON";
		$sql .= 		" tor2.OrderID = tod2.OrderID";
		$sql .= 	" AND";
		$sql .= 		" tod2.Del = " . DELETE_OFF;

		if ($status != '') {
			$sql .= 	" AND";
			$sql .= 		" tor2.Status IN (";
			$sql .= 			$status;
			$sql .= 		" )";
		}

		$sql .= 	" INNER JOIN";
		$sql .= 		" M_Comp mco2";
		$sql .= 	" ON";
		$sql .= 		" tor2.CompID = mco2.CompID";
		$sql .= 	" AND";
		$sql .= 		" mco2.Del = " . DELETE_OFF;

		$sql .= 	" INNER JOIN";
		$sql .= 		" T_Order tor3";
		$sql .= 	" ON";
		$sql .= 		" SUBSTRING(tor2.AppliNo, 2, 12) = SUBSTRING(tor3.AppliNo, 2, 12)";
		$sql .= 	" AND";
		$sql .= 		" SUBSTRING(tor3.AppliNo, 1, 1) = 'R'";		// 返却申請情報の取得
		$sql .= 	" AND";
		$sql .= 		" tor3.Del = " . DELETE_OFF;

		$sql .= 	" WHERE";
		$sql .= 		" tor2.Del = " . DELETE_OFF;
		$sql .= 	" AND";
		$sql .= 		" SUBSTRING(tor2.AppliNo, 1, 1) = 'A'";			// 発注申請情報の取得
		$sql .= 	" AND";
		$sql .= 		" tor2.AppliMode = " . APPLI_MODE_EXCHANGE;		// 交換

		if ($appliDayFrom != '') {
			$sql .= 	" AND";
			$sql .= 		" CONVERT(char, tor2.AppliDay, 111) >= '" . db_Escape($appliDayFrom) . "'";
		}

		if ($appliDayTo != '') {
			$sql .= 	" AND";
			$sql .= 		" CONVERT(char, tor2.AppliDay, 111) <= '" . db_Escape($appliDayTo) . "'";
		}

		if ($compId != '') {
			$sql .= 	" AND";
			$sql .= 		" tor2.CompID = " . db_Escape($compId);
		}

		if ($staffCode != '') {
			$sql .= 	" AND";
			$sql .= 		" tor2.StaffCode = '" . db_Escape($staffCode) . "'";
		}

		if ($personName != '') {
			$sql .= 	" AND";
			$sql .= 		" tor2.PersonName LIKE ('%" . db_Escape($personName) . "%')";
		}

		//if ($_SESSION['ADMINLVL'] != '1') {
		//	$sql .= 	" AND";
		//	$sql .= 		" mco2.CorpCd = '" . db_Escape($_SESSION['CORPCD']) . "'";
		//	if ($isLevelAgency == true) {
		//		$sql .= 	" AND";
		//		$sql .= 		" mco2.AgencyID = '" . db_Escape($_SESSION['COMPID']) . "'";
		//	}
		//}

		if ($honbuCd != '') {
			$sql .= 	" AND";
			$sql .= 		" mco2.HonbuCd = '" . db_Escape($honbuCd) . "'";
		}

		if ($shibuCd != '') {
			$sql .= 	" AND";
			$sql .= 		" mco2.ShibuCd = '" . db_Escape($shibuCd) . "'";
		}

		$sql .= " )";
	}

	$sql .= 	" ORDER BY";
	$sql .= 		" AppliDay DESC";

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	// 取得した値をHTMLエンティティ
	$resultCount = count($result);
	for ($i=0; $i<$resultCount; $i++) {

		$result[$i]['requestDay']      = strtotime($result[$i]['AppliDay']);
		$result[$i]['requestNo']       = $result[$i]['AppliNo'];
		$result[$i]['returnRequestNo'] = $result[$i]['ReturnAppliNo'];
		$result[$i]['orderId']         = $result[$i]['OrderID'];
		$result[$i]['returnOrderId']   = $result[$i]['ReturnOrderID'];
		$result[$i]['CompCd']          = castHtmlEntity($result[$i]['AppliCompCd']);
		$result[$i]['CompName']        = castHtmlEntity($result[$i]['AppliCompName']);
		$result[$i]['staffCode']       = castHtmlEntity($result[$i]['StaffCode']);
		$result[$i]['personName']       = castHtmlEntity($result[$i]['PersonName']);
		$result[$i]['reason']          = castHtmlEntity($result[$i]['AgreeReason']);


		// 申請番号の遷移先決定
		$result[$i]['isAppli'] = false;
		if (ereg('^A.*$', $result[$i]['AppliNo'])) {
			$result[$i]['isAppli'] = true;
		}

		// 承認日
		$result[$i]['isEmptyAgreeDay'] = true;
		if (isset($result[$i]['AgreeDay']) && $result[$i]['AgreeDay'] != '') {
			$result[$i]['AgreeDay']   = strtotime($result[$i]['AgreeDay']);
			$result[$i]['isEmptyAgreeDay'] = false;
		}

		// 区分
		$result[$i]['divisionOrder']    = false;
		$result[$i]['divisionExchange'] = false;
		$result[$i]['divisionReturn']   = false;
		switch ($result[$i]['AppliMode']) {
			case APPLI_MODE_ORDER:							// 発注
				$result[$i]['divisionOrder']    = true;
				break;
			case APPLI_MODE_EXCHANGE:						// 交換
				$result[$i]['divisionExchange'] = true;
				break;
			case APPLI_MODE_RETURN:							// 返却
				$result[$i]['divisionReturn']   = true;
				break;
			default:
				break;
		}

		// 状態
		$result[$i]['status']  = castHtmlEntity($DISPLAY_STATUS_ACCEPTATION[$result[$i]['Status']]);

		// 状態の文字列の色
		$result[$i]['statusIsRed']   = false;
		$result[$i]['statusIsGreen'] = false;
		$result[$i]['statusIsGray']  = false;
		$result[$i]['statusIsTeal']  = false;
		$result[$i]['statusIsPink']  = false;
		$result[$i]['statusIsBlack'] = false;
		switch ($result[$i]['Status']) {
			case STATUS_APPLI:							// 承認待
				$result[$i]['statusIsGray']  = true;
				break;
			case STATUS_APPLI_DENY:						// 否認
			case STATUS_NOT_RETURN_DENY:				// 返却否認
			case STATUS_LOSS_DENY:						// 紛失否認
				$result[$i]['statusIsPink']  = true;
				break;
			case STATUS_APPLI_ADMIT:					// 承認済
				$result[$i]['statusIsGreen'] = true;
				break;
			case STATUS_NOT_RETURN:						// 返却承認待
			case STATUS_NOT_RETURN_ADMIT:				// 返却承認済
				$result[$i]['statusIsRed']   = true;
				break;
			case STATUS_LOSS:							// 紛失承認待
			case STATUS_LOSS_ADMIT:						// 紛失承認済
				$result[$i]['statusIsTeal']  = true;
				break;
			default:
				$result[$i]['statusIsBlack'] = true;
				break;
		}

		// 承認待ちか承認済みか
		$result[$i]['isAgree'] = false;
		switch ($result[$i]['Status']) {
			case STATUS_APPLI_DENY:						// 否認
			case STATUS_APPLI_ADMIT:					// 承認済
			case STATUS_NOT_RETURN_DENY:				// 返却否認
			case STATUS_NOT_RETURN_ADMIT:				// 返却承認済
			case STATUS_LOSS_DENY:						// 紛失否認
			case STATUS_LOSS_ADMIT:						// 紛失承認済
				$result[$i]['isAgree'] = true;
				break;
			default:
				break;
		}


		// 承認済みか否認済みか
		$result[$i]['acceptationIsYes'] = false;
		$result[$i]['acceptationIsNo']  = false;
		switch ($result[$i]['Status']) {
			case STATUS_APPLI_ADMIT:					// 承認済
			case STATUS_NOT_RETURN_ADMIT:				// 返却承認済
			case STATUS_LOSS_ADMIT:						// 紛失承認済
				$result[$i]['acceptationIsYes'] = true;
				break;
			case STATUS_APPLI_DENY:						// 否認
			case STATUS_NOT_RETURN_DENY:				// 返却否認
			case STATUS_LOSS_DENY:						// 紛失否認
				$result[$i]['acceptationIsNo'] = true;
				break;
			default:
				break;
		}

		switch ($result[$i]['Status']) {
			case STATUS_APPLI:							// 承認待
				$result[$i]['StatusYes'] = STATUS_APPLI_ADMIT;			// 承認済
				$result[$i]['StatusNo']  = STATUS_APPLI_DENY;			// 否認
				break;
			case STATUS_NOT_RETURN:						// 返却承認待
				$result[$i]['StatusYes'] = STATUS_NOT_RETURN_ADMIT;		// 返却承認済
				$result[$i]['StatusNo']  = STATUS_NOT_RETURN_DENY;		// 返却否認
				break;
			case STATUS_LOSS:							// 紛失承認待
				$result[$i]['StatusYes'] = STATUS_LOSS_ADMIT;			// 紛失承認済
				$result[$i]['StatusNo']  = STATUS_LOSS_DENY;			// 紛失否認
				break;
			default:
				break;
		}

		// 検索ボタンを押した時は入力された情報は引き継がない
		if (!isset($post['searchFlg']) || $post['searchFlg'] != '1') {
			if (isset($post['acceptationY'][$result[$i]['orderId']]) && $post['acceptationY'][$result[$i]['orderId']] != '') {
				$result[$i]['acceptationIsYes'] = true;
			}
	
			if (isset($post['acceptationN'][$result[$i]['orderId']]) && $post['acceptationN'][$result[$i]['orderId']] != '') {
				$result[$i]['acceptationIsNo'] = true;
			}
	
			if (isset($post['reason'][$result[$i]['orderId']]) && $post['reason'][$result[$i]['orderId']] != '') {
				$result[$i]['reason'] = trim($post['reason'][$result[$i]['orderId']]);
			}
		}

	}

	return  $result;

}

/*
 * 検索条件を指定しているか判定
 * 引数  ：$post           => POST値
 * 戻り値：true：条件を指定している / false：条件を指定していない
 *
 * create 2007/04/23 H.Osugi
 *
 */
function checkCondition($post) {

	// 店舗IDの指定があった場合
	if (isset($post['searchCompId']) && $post['searchCompId'] != '') {
		return true;
	}

	// スタッフコードの指定があった場合
	if (isset($post['searchStaffCode']) && $post['searchStaffCode'] != '') {
		return true;
	}

	// スタッフ氏名の指定があった場合
	if (isset($post['searchPersonName']) && $post['searchPersonName'] != '') {
		return true;
	}

	// 申請日の指定があった場合
	if (isset($post['searchAppliDayFrom']) && $post['searchAppliDayFrom'] != '') {
		return true;
	}
	if (isset($post['searchAppliDayTo']) && $post['searchAppliDayTo'] != '') {
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
    <link rel="stylesheet" href="//code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />
    <script src="//code.jquery.com/jquery-1.9.1.js"></script>
    <script src="//code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
    <script language="JavaScript">
    <!--
    function yes_chkbox(i) {
      if (document.pagingForm.elements['acceptationY[' + i + ']'].checked) {
        document.pagingForm.elements['acceptationN[' + i + ']'].checked = false;
      }
    }
    function no_chkbox(i) {
      if (document.pagingForm.elements['acceptationN[' + i + ']'].checked) {
        document.pagingForm.elements['acceptationY[' + i + ']'].checked = false;
      }
    }
    function confirmAcceptation() {

      if (confirm('承認処理を行いますがよろしいですか')) {
        document.pagingForm.action = './syounin_kanryo.php';
        document.pagingForm.submit();
      }

      return false;

    }
	// 申請のチェックボックスをONOFFにする
	function chkAll_form() {
		var flg;

		flg = false;	// 全てOFF
		for (var i = 0; i < document.pagingForm.length; i++) {
			if (document.pagingForm.elements[i].type == 'checkbox') {
				if (document.pagingForm.elements[i].name.substring(0,12) == 'acceptationY') {
					if (!document.pagingForm.elements[i].checked) {
						// １件でも未承認あれば全件ON
						flg = true;		// 全てON
						break;
					}
				}
			}
		}

		for (var i = 0; i < document.pagingForm.length; i++) {
			if (document.pagingForm.elements[i].type == 'checkbox') {
				if (document.pagingForm.elements[i].name.substring(0,12) == 'acceptationY') {
					if (flg) {
						document.pagingForm.elements[i].checked = true;
					} else {
						document.pagingForm.elements[i].checked = false;
					}
				}
				if (document.pagingForm.elements[i].name.substring(0,12) == 'acceptationN') {
					document.pagingForm.elements[i].checked = false;
				}
			}
		}
	}
    // -->
    </script>
    <script type="text/javascript">
      $(function() {
        $("#datepicker1").datepicker();
        $('#datepicker1').datepicker("option", "dateFormat", 'yy/mm/dd' );
        $("#datepicker1").datepicker("setDate", "<?php isset($searchAppliDayFrom) ? print($searchAppliDayFrom) : print('&#123;searchAppliDayFrom&#125;'); ?>");
        $("#datepicker2").datepicker();
        $('#datepicker2').datepicker("option", "dateFormat", 'yy/mm/dd' );
        $("#datepicker2").datepicker("setDate", "<?php isset($searchAppliDayTo) ? print($searchAppliDayTo) : print('&#123;searchAppliDayTo&#125;'); ?>");
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
        

        <form method="post" action="./syounin.php" name="pagingForm">
        <div id="contents">
        <h1>ユニフォーム承認処理</h1>
            <table border="0" width="700" cellpadding="0" cellspacing="0" class="tb_1">
              <tr height="40">
                <td width="80" class="line"><span class="fbold">所属施設</span></td>
                <td colspan="3" class="line">
                  <input type="text" name="searchCompCd" value="<?php isset($searchCompCd) ? print($searchCompCd) : print('&#123;searchCompCd&#125;'); ?>" style="width:60px;" readonly="readonly"><input type="text" name="searchCompName" value="<?php isset($searchCompName) ? print($searchCompName) : print('&#123;searchCompName&#125;'); ?>" style="width:310px;" readonly="readonly">
                  <input type="hidden" name="searchCompId" value="<?php isset($searchCompId) ? print($searchCompId) : print('&#123;searchCompId&#125;'); ?>">
                  <input type="hidden" name="searchCorpCd" value="">
                <input name="shop_btn" type="button" value="施設検索" onclick="window.open('../search_comp.php', 'searchComp', 'width=500, height=400, menubar=no, toolbar=no, scrollbars=yes'); return false;"></td>
                <td></td>
              </tr>
              <tr height="40">
                <td width="80" class="line"><span class="fbold">職員コード</span></td>
                <td width="160" class="line"><input name="searchStaffCode" type="text" value="<?php isset($searchStaffCode) ? print($searchStaffCode) : print('&#123;searchStaffCode&#125;'); ?>" style="width:100px;" maxlength="12"></td>
                <td width="80" class="line" align="center"><span class="fbold">氏名</span></td>
                <td width="260" class="line"><input name="searchPersonName" type="text" value="<?php isset($searchPersonName) ? print($searchPersonName) : print('&#123;searchPersonName&#125;'); ?>" style="width:200px;" maxlength="10"></td>
                <td></td>
              </tr>
              <tr height="40">
                <td width="80" class="line"><span class="fbold">申請日</span></td>
                <td class="line" colspan="3">
                  <input name="searchAppliDayFrom" type="text" value="<?php isset($searchAppliDayFrom) ? print($searchAppliDayFrom) : print('&#123;searchAppliDayFrom&#125;'); ?>" style="width:100px;" maxlength="10" id="datepicker1">～&nbsp;<input name="searchAppliDayTo" type="text" value="<?php isset($searchAppliDayTo) ? print($searchAppliDayTo) : print('&#123;searchAppliDayTo&#125;'); ?>" style="width:100px;" maxlength="10" id="datepicker2">
                </td>
                <td></td>
              </tr>
              <tr height="40">
                <td width="80" class="line"><span class="fbold">状態</span></td>
                <td class="line" colspan="3">
<?php if($isSelectedStatus1) { ?>
                        <input type="checkbox" name="searchStatus[]" value="1" id="status1" checked="checked">
<?php } ?>
<?php if(!$isSelectedStatus1) { ?>
                        
                        <input type="checkbox" name="searchStatus[]" value="1" id="status1">
                        
<?php } ?>
                      <label for="status1"><?php isset($DISPLAY_STATUS_APPLI) ? print($DISPLAY_STATUS_APPLI) : print('&#123;DISPLAY_STATUS_APPLI&#125;'); ?></label>
                      
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
                      
                      <label for="status3"><?php isset($DISPLAY_STATUS_APPLI_DENY) ? print($DISPLAY_STATUS_APPLI_DENY) : print('&#123;DISPLAY_STATUS_APPLI_DENY&#125;'); ?></label>
                      
                      </td>
                      <td class="line" align="center">
                      <input type="button" value="     検索     " onclick="document.pagingForm.searchFlg.value='1'; document.pagingForm.submit(); return false;">
                      </td>
              </tr>
            </table>
<?php if($isSearched) { ?>
            <h3>◆承認／否認を選択して下さい。</h3>
            <table width="740" border="0" class="tb_1" cellpadding="0" cellspacing="3">
              <tr>
                <td align="right">
                  <input type="button" name="all_syonin" value="全件承認する" onclick="chkAll_form()">
                </td>
              </tr>
            </table>
            <table width="740" border="0" class="tb_1" cellpadding="0" cellspacing="3">
              <tr>
                <th width="60">申請日</th>
                <th width="90">申請番号</th>
                <th width="70">施設CD</th>
                <th width="120">施設名</th>
                <th width="80">職員</th>
                <th width="40">区分</th>
                <th width="60">承認日</th>
                <th width="60">状態</th>
                <th width="70">承認確認<br>承認/否認</th>
                <th width="90">理由</th>
              </tr>
<?php for ($i1_orders=0; $i1_orders<count($orders); $i1_orders++) { ?>
              <tr height="20">
                <td width="60" class="line2" align="center"><?php isset($orders[$i1_orders]['requestDay']) ? print(date("y/m/d", $orders[$i1_orders]['requestDay'])) : print('&#123;dateFormat(orders.requestDay, "y/m/d")&#125;'); ?></td>
                <td width="90" class="line2" align="center">
<?php if(!$orders[$i1_orders]['divisionExchange']) { ?>
<?php if(!$orders[$i1_orders]['isAppli']) { ?>
                  <a href="#" onclick="document.pagingForm.orderId.value='<?php isset($orders[$i1_orders]['orderId']) ? print($orders[$i1_orders]['orderId']) : print('&#123;orders.orderId&#125;'); ?>'; document.pagingForm.action='../rireki/henpin_meisai.php'; document.pagingForm.submit(); return false;"><?php isset($orders[$i1_orders]['requestNo']) ? print($orders[$i1_orders]['requestNo']) : print('&#123;orders.requestNo&#125;'); ?></a></td>
<?php } ?>
<?php if($orders[$i1_orders]['isAppli']) { ?>
                  
                  <a href="#" onclick="document.pagingForm.orderId.value='<?php isset($orders[$i1_orders]['orderId']) ? print($orders[$i1_orders]['orderId']) : print('&#123;orders.orderId&#125;'); ?>'; document.pagingForm.action='../rireki/hachu_meisai.php'; document.pagingForm.submit(); return false;"><?php isset($orders[$i1_orders]['requestNo']) ? print($orders[$i1_orders]['requestNo']) : print('&#123;orders.requestNo&#125;'); ?></a></td>
                  
<?php } ?>
<?php } ?>
<?php if($orders[$i1_orders]['divisionExchange']) { ?>
                  <a href="#" onclick="document.pagingForm.orderId.value='<?php isset($orders[$i1_orders]['orderId']) ? print($orders[$i1_orders]['orderId']) : print('&#123;orders.orderId&#125;'); ?>'; document.pagingForm.action='../rireki/hachu_meisai.php'; document.pagingForm.submit(); return false;"><?php isset($orders[$i1_orders]['requestNo']) ? print($orders[$i1_orders]['requestNo']) : print('&#123;orders.requestNo&#125;'); ?></a><br>
                  <a href="#" onclick="document.pagingForm.orderId.value='<?php isset($orders[$i1_orders]['returnOrderId']) ? print($orders[$i1_orders]['returnOrderId']) : print('&#123;orders.returnOrderId&#125;'); ?>'; document.pagingForm.action='../rireki/henpin_meisai.php'; document.pagingForm.submit(); return false;"><?php isset($orders[$i1_orders]['returnRequestNo']) ? print($orders[$i1_orders]['returnRequestNo']) : print('&#123;orders.returnRequestNo&#125;'); ?></a></td>
<?php } ?>

                <td width="70" class="line2" align="center"><?php isset($orders[$i1_orders]['CompCd']) ? print($orders[$i1_orders]['CompCd']) : print('&#123;orders.CompCd&#125;'); ?></td>
                <td width="120" class="line2" align="left"><?php isset($orders[$i1_orders]['CompName']) ? print($orders[$i1_orders]['CompName']) : print('&#123;orders.CompName&#125;'); ?></td>
                <td width="80" class="line2" align="center"><?php isset($orders[$i1_orders]['PersonName']) ? print($orders[$i1_orders]['PersonName']) : print('&#123;orders.PersonName&#125;'); ?></td>
                <td width="40" class="line2" align="center">
<?php if($orders[$i1_orders]['divisionOrder']) { ?>
                  発注
<?php } ?>
<?php if($orders[$i1_orders]['divisionExchange']) { ?>
                  
                  交換
                  
<?php } ?>
<?php if($orders[$i1_orders]['divisionReturn']) { ?>
                  
                  返却
                  
<?php } ?>
                </td>
                <td width="60" class="line2" align="center">
<?php if($orders[$i1_orders]['isEmptyAgreeDay']) { ?>
                  
                  &nbsp;
                  
<?php } ?>
<?php if(!$orders[$i1_orders]['isEmptyAgreeDay']) { ?>
                  <?php isset($orders[$i1_orders]['AgreeDay']) ? print(date("y/m/d", $orders[$i1_orders]['AgreeDay'])) : print('&#123;dateFormat(orders.AgreeDay, "y/m/d")&#125;'); ?>
<?php } ?>
                </td>
                <td width="60" class="line2" align="center" nowrap="nowrap">
<?php if($orders[$i1_orders]['statusIsRed']) { ?>
                  
<?php if(!$orders[$i1_orders]['isAgree']) { ?>
                  <span style="color:red"><?php isset($orders[$i1_orders]['status']) ? print($orders[$i1_orders]['status']) : print('&#123;orders.status&#125;'); ?></span>
<?php } ?>
<?php if($orders[$i1_orders]['isAgree']) { ?>
<?php if(!$orders[$i1_orders]['divisionExchange']) { ?>
                  <a href="#" onclick="document.pagingForm.orderId.value='<?php isset($orders[$i1_orders]['orderId']) ? print($orders[$i1_orders]['orderId']) : print('&#123;orders.orderId&#125;'); ?>'; document.pagingForm.action='./syounin_cancel_kakunin.php'; document.pagingForm.submit(); return false;"><span style="color:red"><?php isset($orders[$i1_orders]['status']) ? print($orders[$i1_orders]['status']) : print('&#123;orders.status&#125;'); ?></span></a>
<?php } ?>
<?php if($orders[$i1_orders]['divisionExchange']) { ?>
                  <a href="#" onclick="document.pagingForm.orderId.value='<?php isset($orders[$i1_orders]['orderId']) ? print($orders[$i1_orders]['orderId']) : print('&#123;orders.orderId&#125;'); ?>'; document.pagingForm.returnOrderId.value='<?php isset($orders[$i1_orders]['returnOrderId']) ? print($orders[$i1_orders]['returnOrderId']) : print('&#123;orders.returnOrderId&#125;'); ?>'; document.pagingForm.action='./syounin_cancel_kakunin.php'; document.pagingForm.submit(); return false;"><span style="color:red"><?php isset($orders[$i1_orders]['status']) ? print($orders[$i1_orders]['status']) : print('&#123;orders.status&#125;'); ?></span></a>
<?php } ?>
<?php } ?>
                  
<?php } ?>
<?php if($orders[$i1_orders]['statusIsGreen']) { ?>
                  
<?php if(!$orders[$i1_orders]['isAgree']) { ?>
                  <span style="color:green"><?php isset($orders[$i1_orders]['status']) ? print($orders[$i1_orders]['status']) : print('&#123;orders.status&#125;'); ?></span>
<?php } ?>
<?php if($orders[$i1_orders]['isAgree']) { ?>
<?php if(!$orders[$i1_orders]['divisionExchange']) { ?>
                  <a href="#" onclick="document.pagingForm.orderId.value='<?php isset($orders[$i1_orders]['orderId']) ? print($orders[$i1_orders]['orderId']) : print('&#123;orders.orderId&#125;'); ?>'; document.pagingForm.action='./syounin_cancel_kakunin.php'; document.pagingForm.submit(); return false;"><span style="color:green"><?php isset($orders[$i1_orders]['status']) ? print($orders[$i1_orders]['status']) : print('&#123;orders.status&#125;'); ?></span></a>
<?php } ?>
<?php if($orders[$i1_orders]['divisionExchange']) { ?>
                  <a href="#" onclick="document.pagingForm.orderId.value='<?php isset($orders[$i1_orders]['orderId']) ? print($orders[$i1_orders]['orderId']) : print('&#123;orders.orderId&#125;'); ?>'; document.pagingForm.returnOrderId.value='<?php isset($orders[$i1_orders]['returnOrderId']) ? print($orders[$i1_orders]['returnOrderId']) : print('&#123;orders.returnOrderId&#125;'); ?>'; document.pagingForm.action='./syounin_cancel_kakunin.php'; document.pagingForm.submit(); return false;"><span style="color:green"><?php isset($orders[$i1_orders]['status']) ? print($orders[$i1_orders]['status']) : print('&#123;orders.status&#125;'); ?></span></a>
<?php } ?>
<?php } ?>
                  
<?php } ?>
<?php if($orders[$i1_orders]['statusIsGray']) { ?>
                  
                  <span style="color:gray"><?php isset($orders[$i1_orders]['status']) ? print($orders[$i1_orders]['status']) : print('&#123;orders.status&#125;'); ?></span>
                  
<?php } ?>
<?php if($orders[$i1_orders]['statusIsTeal']) { ?>
                  
<?php if(!$orders[$i1_orders]['isAgree']) { ?>
                  <span style="color:Teal"><?php isset($orders[$i1_orders]['status']) ? print($orders[$i1_orders]['status']) : print('&#123;orders.status&#125;'); ?></span>
<?php } ?>
<?php if($orders[$i1_orders]['isAgree']) { ?>
<?php if(!$orders[$i1_orders]['divisionExchange']) { ?>
                  <a href="#" onclick="document.pagingForm.orderId.value='<?php isset($orders[$i1_orders]['orderId']) ? print($orders[$i1_orders]['orderId']) : print('&#123;orders.orderId&#125;'); ?>'; document.pagingForm.action='./syounin_cancel_kakunin.php'; document.pagingForm.submit(); return false;"><span style="color:Teal"><?php isset($orders[$i1_orders]['status']) ? print($orders[$i1_orders]['status']) : print('&#123;orders.status&#125;'); ?></span></a>
<?php } ?>
<?php if($orders[$i1_orders]['divisionExchange']) { ?>
                  <a href="#" onclick="document.pagingForm.orderId.value='<?php isset($orders[$i1_orders]['orderId']) ? print($orders[$i1_orders]['orderId']) : print('&#123;orders.orderId&#125;'); ?>'; document.pagingForm.returnOrderId.value='<?php isset($orders[$i1_orders]['returnOrderId']) ? print($orders[$i1_orders]['returnOrderId']) : print('&#123;orders.returnOrderId&#125;'); ?>'; document.pagingForm.action='./syounin_cancel_kakunin.php'; document.pagingForm.submit(); return false;"><span style="color:Teal"><?php isset($orders[$i1_orders]['status']) ? print($orders[$i1_orders]['status']) : print('&#123;orders.status&#125;'); ?></span></a>
<?php } ?>
<?php } ?>
                  
<?php } ?>
<?php if($orders[$i1_orders]['statusIsPink']) { ?>
                  
<?php if(!$orders[$i1_orders]['isAgree']) { ?>
                  <span style="color:fuchsia"><?php isset($orders[$i1_orders]['status']) ? print($orders[$i1_orders]['status']) : print('&#123;orders.status&#125;'); ?></span>
<?php } ?>
<?php if($orders[$i1_orders]['isAgree']) { ?>
<?php if(!$orders[$i1_orders]['divisionExchange']) { ?>
                  <a href="#" onclick="document.pagingForm.orderId.value='<?php isset($orders[$i1_orders]['orderId']) ? print($orders[$i1_orders]['orderId']) : print('&#123;orders.orderId&#125;'); ?>'; document.pagingForm.action='./syounin_cancel_kakunin.php'; document.pagingForm.submit(); return false;"><span style="color:fuchsia"><?php isset($orders[$i1_orders]['status']) ? print($orders[$i1_orders]['status']) : print('&#123;orders.status&#125;'); ?></span></a>
<?php } ?>
<?php if($orders[$i1_orders]['divisionExchange']) { ?>
                  <span style="color:fuchsia"><?php isset($orders[$i1_orders]['status']) ? print($orders[$i1_orders]['status']) : print('&#123;orders.status&#125;'); ?></span>
<?php } ?>
<?php } ?>
                  
<?php } ?>
<?php if($orders[$i1_orders]['statusIsBlack']) { ?>
                  <?php isset($orders[$i1_orders]['status']) ? print($orders[$i1_orders]['status']) : print('&#123;orders.status&#125;'); ?>
<?php } ?>
                </td>
                <td width="70" class="line2" align="center">
<?php if(!$orders[$i1_orders]['isAgree']) { ?>
<?php if($orders[$i1_orders]['acceptationIsYes']) { ?>
                  <input type="checkbox" id="checkbox[]" name="acceptationY[<?php isset($orders[$i1_orders]['orderId']) ? print($orders[$i1_orders]['orderId']) : print('&#123;orders.orderId&#125;'); ?>]" value="<?php isset($orders[$i1_orders]['StatusYes']) ? print($orders[$i1_orders]['StatusYes']) : print('&#123;orders.StatusYes&#125;'); ?>" onClick="yes_chkbox(<?php isset($orders[$i1_orders]['orderId']) ? print($orders[$i1_orders]['orderId']) : print('&#123;orders.orderId&#125;'); ?>)" checked="checked">
<?php } ?>
<?php if(!$orders[$i1_orders]['acceptationIsYes']) { ?>
                  
                  <input type="checkbox" id="checkbox[]" name="acceptationY[<?php isset($orders[$i1_orders]['orderId']) ? print($orders[$i1_orders]['orderId']) : print('&#123;orders.orderId&#125;'); ?>]" value="<?php isset($orders[$i1_orders]['StatusYes']) ? print($orders[$i1_orders]['StatusYes']) : print('&#123;orders.StatusYes&#125;'); ?>" onClick="yes_chkbox(<?php isset($orders[$i1_orders]['orderId']) ? print($orders[$i1_orders]['orderId']) : print('&#123;orders.orderId&#125;'); ?>)">
                  
<?php } ?>
<?php } ?>
<?php if($orders[$i1_orders]['isAgree']) { ?>
<?php if($orders[$i1_orders]['acceptationIsYes']) { ?>
                  
                  <img src="/img/chkon.jpg" alt="on" border="0">
                  
<?php } ?>
<?php if(!$orders[$i1_orders]['acceptationIsYes']) { ?>
                  
                  <img src="/img/chkoff.jpg" alt="onf" border="0">
                  
<?php } ?>
<?php } ?>
<?php if(!$orders[$i1_orders]['isAgree']) { ?>
<?php if($orders[$i1_orders]['acceptationIsNo']) { ?>
                  
                  <input type="checkbox" id="checkbox[]" name="acceptationN[<?php isset($orders[$i1_orders]['orderId']) ? print($orders[$i1_orders]['orderId']) : print('&#123;orders.orderId&#125;'); ?>]" value="<?php isset($orders[$i1_orders]['StatusNo']) ? print($orders[$i1_orders]['StatusNo']) : print('&#123;orders.StatusNo&#125;'); ?>" onClick="no_chkbox(<?php isset($orders[$i1_orders]['orderId']) ? print($orders[$i1_orders]['orderId']) : print('&#123;orders.orderId&#125;'); ?>)" checked="checked">
                  
<?php } ?>
<?php if(!$orders[$i1_orders]['acceptationIsNo']) { ?>
                  <input type="checkbox" id="checkbox[]" name="acceptationN[<?php isset($orders[$i1_orders]['orderId']) ? print($orders[$i1_orders]['orderId']) : print('&#123;orders.orderId&#125;'); ?>]" value="<?php isset($orders[$i1_orders]['StatusNo']) ? print($orders[$i1_orders]['StatusNo']) : print('&#123;orders.StatusNo&#125;'); ?>" onClick="no_chkbox(<?php isset($orders[$i1_orders]['orderId']) ? print($orders[$i1_orders]['orderId']) : print('&#123;orders.orderId&#125;'); ?>)">
<?php } ?>
                  <input type="hidden" name="orderIds[]" value="<?php isset($orders[$i1_orders]['orderId']) ? print($orders[$i1_orders]['orderId']) : print('&#123;orders.orderId&#125;'); ?>">
<?php if($orders[$i1_orders]['divisionExchange']) { ?>
                  <input type="hidden" name="returnOrderIds[<?php isset($orders[$i1_orders]['orderId']) ? print($orders[$i1_orders]['orderId']) : print('&#123;orders.orderId&#125;'); ?>]" value="<?php isset($orders[$i1_orders]['returnOrderId']) ? print($orders[$i1_orders]['returnOrderId']) : print('&#123;orders.returnOrderId&#125;'); ?>">
<?php } ?>
<?php } ?>
<?php if($orders[$i1_orders]['isAgree']) { ?>
<?php if($orders[$i1_orders]['acceptationIsNo']) { ?>
                  
                  <img src="/img/chkon.jpg" alt="on" border="0">
                  
<?php } ?>
<?php if(!$orders[$i1_orders]['acceptationIsNo']) { ?>
                  
                  <img src="/img/chkoff.jpg" alt="off" border="0">
                  
<?php } ?>
<?php } ?>
                </td>
<?php if(!$orders[$i1_orders]['isAgree']) { ?>
                <td width="90" class="line2" align="center"><input name="reason[<?php isset($orders[$i1_orders]['orderId']) ? print($orders[$i1_orders]['orderId']) : print('&#123;orders.orderId&#125;'); ?>]" type="text" value="<?php isset($orders[$i1_orders]['reason']) ? print($orders[$i1_orders]['reason']) : print('&#123;orders.reason&#125;'); ?>" size="14" maxlength="60"></td>
<?php } ?>
<?php if($orders[$i1_orders]['isAgree']) { ?>
                
                <td width="90" class="line2" align="center"><input name="reason[<?php isset($orders[$i1_orders]['orderId']) ? print($orders[$i1_orders]['orderId']) : print('&#123;orders.orderId&#125;'); ?>]" type="text" value="<?php isset($orders[$i1_orders]['reason']) ? print($orders[$i1_orders]['reason']) : print('&#123;orders.reason&#125;'); ?>" size="14" readonly="readonly"></td>
                
<?php } ?>
              </tr>
<?php } ?>
            </table>
            <div class="bot"><a href="#" onClick="confirmAcceptation(); return false;"><img src="/img/toroku.gif" alt="登録" width="112" height="32" border="0"></a></div>
<?php } ?>
          </div>
          <input type="hidden" name="encodeHint" value="京">
          <input type="hidden" name="orderId">
          <input type="hidden" name="returnOrderId">
          <input type="hidden" name="searchFlg">
          <input type="hidden" name="syouninFlg" value="1">
        </form>
        <br><br><br>
        

      </div>
    </div>
  </body>
</html>
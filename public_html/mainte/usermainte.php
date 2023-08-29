<?php
/*
 * 申請履歴画面
 * rireki.src.php
 *
 * create 2007/03/26 H.Osugi
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


/* ../../include/createMoveMail.php start */

/*
 * 店舗移動通知メール生成モジュール
 * createMoveMail.php
 *
 * create 2008/06/27 W.Takasaki
 *
 */

/*
 * 店舗移動通知メールの件名と本文を作成する
 *
 * 引数  ：  $dbConnect   => DB接続オブジェクト
 *       ：$filePath   => メールテンプレートのファイルパス
 *       ：$staffInfo  => スタッフ情報
 *       ：$compInfo   => 店舗情報
 *       ：$subject    => 件名
 *       ：$message    => 本文
 *
 * 戻り値：true：メールテンプレート取得成功/false：取得失敗
 *
 * create 2007/04/19 H.Osugi
 *
 */
function moveCompMail($dbConnect,$filePath, $compInfo, $staffInfo, &$subject, &$message) {

	// 初期化
	$subject = '';
	$message = '';

	// 件名
	$subject = MAIL_SUBJECT_HEADER . "所属店舗変更通知";

	// 現在日時
	$date = date("Y年n月j日 H時i分");

	// 本文
	$message = file_get_contents($filePath . 'moveComp.txt');

	// 本文の置換
	$message = mb_ereg_replace('###HEAD###', MAIL_BODY_HEADER, $message);
	$message = mb_ereg_replace('###DATE###', $date, $message);

	$message = mb_ereg_replace('###OLDCOMPCD###', trim($compInfo['old']['CompCd']), $message);
	$message = mb_ereg_replace('###OLDCOMPNAME###', trim($compInfo['old']['CompName']), $message);
	$message = mb_ereg_replace('###OLDSTAFFCODE###', trim($staffInfo['old']['StaffCode']), $message);
    $message = mb_ereg_replace('###OLDPERSONNAME###', trim($staffInfo['old']['PersonName']), $message);

    $message = mb_ereg_replace('###NEWCOMPCD###', trim($compInfo['new']['CompCd']), $message);
    $message = mb_ereg_replace('###NEWCOMPNAME###', trim($compInfo['new']['CompName']), $message);
    $message = mb_ereg_replace('###NEWSTAFFCODE###', trim($staffInfo['new']['StaffCode']), $message);
    $message = mb_ereg_replace('###NEWPERSONNAME###', trim($staffInfo['new']['PersonName']), $message);

	return true;

}



/* ../../include/createMoveMail.php end */


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



//admin以外はTOPへ遷移
//08/11/20 uesugi
//if (!$isLevelAdmin){
//    redirectTop();
//}
// 初期設定
$isMenuAdmin = true;	// 管理機能のメニューをアクティブに

// 変数の初期化 ここから ******************************************************
$staffCode          = '';		// 社員番号
$personName         = '';		// 氏名
$hatureibi          = '';		// 発令日

$selectCompId       = '';		// 施設ID
$selectCompCd       = '';		// 施設コード
$selectCompName     = '';		// 施設名
$selectNextCompId   = '';		// 異動先施設ID
$selectNextCompCd   = '';		// 異動先施設コード
$selectNextCompName = '';		// 異動先施設名

$searchCompCd       = "";
$searchCompName     = "";
$searchCompId       = "";
$searchStaffCode    = "";
$searchPersonName   = "";
$isSelectedAdmin    = "";
$StaffSeqID         = "";
$nowPage            = "";
$motoStaffCode      = "";
$genderMensFlag     = ture;

$next_taiyoNum = NEXT_TAIYO_NUM;
$isUpdateFlag   = false;	// 更新フラグ
// 変数の初期化 ここまで ******************************************************

// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST); 

// 職員コード
if (isSetValue($post['staffCode'])) {
	$staffCode = $post['staffCode'];
}
// 氏名
if (isSetValue($post['personName'])) {
	$personName = $post['personName'];
}
// 更新実施日
if (isSetValue($post['hatureibi'])) {
	$hatureibi = $post['hatureibi'];
}
// 施設ID
if (isSetValue($post['selectCompId'])) {
	$selectCompId = $post['selectCompId'];
}
// 施設コード
if (isSetValue($post['selectCompCd'])) {
	$selectCompCd = $post['selectCompCd'];
}
// 施設名
if (isSetValue($post['selectCompName'])) {
	$selectCompName = $post['selectCompName'];
}
// 異動先施設ID
if (isSetValue($post['selectNextCompId'])) {
	$selectNextCompId = $post['selectNextCompId'];
}
// 異動先施設コード
if (isSetValue($post['selectNextCompCd'])) {
	$selectNextCompCd = $post['selectNextCompCd'];
}
// 異動先施設名
if (isSetValue($post['selectNextCompName'])) {
	$selectNextCompName = $post['selectNextCompName'];
}

switch(trim($post['Mode'])){

	case 'ins':
		if ($isLevelNormal == true) {
			if (!isSetValue($post['selectCompId'])) {
				$selectCompId   = $_SESSION['COMPID'];
				$selectCompCd   = $_SESSION['COMPCD'];
				$selectCompName = $_SESSION['COMPNAME'];
			}
		}
		$ope = "insert";
		break;

	case 'upd':
		$StaffSeqID = trim($post['StaffSeqID']);
		// ユーザー取得
		$userData = getUserMaster($dbConnect, trim($StaffSeqID));

		$staffCode          = trim($userData['StaffCode']);			// 職員コード
		$personName         = trim($userData['PersonName']);		// 氏名
		$hatureibi          = trim($userData['HatureiDay']);		// 発令日

		$selectCompId       = trim($userData['CompID']);			// 施設ID
		$selectCompCd       = trim($userData['CompCd']);			// 施設コード
		$selectCompName     = trim($userData['CompName']);			// 施設名

		$selectNextCompId   = trim($userData['NextCompID']);		// 異動先施設ID
		$selectNextCompCd   = trim($userData['NextCompCd']);		// 異動先施設コード
		$selectNextCompName = trim($userData['NextCompName']);		// 異動先施設名

		$isUpdateFlag   = true;

		$ope = "update";

		break;

	case 'insert':
		// エラーチェック
		_check_Data($dbConnect,$post);

		// トランザクション開始
		db_Transaction_Begin($dbConnect);
		$isSuccess =_Insert_M_Staff($dbConnect,$post);
		if($isSuccess == false){
			db_Transaction_Rollback($dbConnect);
			$post['errorName'] = 'userMainte';
			$post['menuName']  = 'isMenuAdmin';
			$post['returnUrl'] = 'mainte/usermainte_top.php';
			$post['errorId'][] = '101';
			$errorUrl             = HOME_URL . 'error.php';
			redirectPost($errorUrl, $post);
		}
		// コミット
		db_Transaction_Commit($dbConnect);

		$errorUrl             = '/mainte/usermainte_top.php';
		redirectPost($errorUrl, "");

		break;

	case 'update':
		if ($post['chkDelete'] == '1') {
			// 貸与中チェック
			$isSuccess = _Check_StaffOrder($dbConnect,$post);
			if($isSuccess == false){

				$post['errorName'] = 'userMainte';
				$post['menuName']  = 'isMenuAdmin';
				$post['returnUrl'] = '/mainte/usermainte_top.php';
				$post['errorId'][] = '018';
				$errorUrl             = HOME_URL . 'error.php';
				redirectPost($errorUrl, $post);
			}

			// トランザクション開始
			db_Transaction_Begin($dbConnect);

			// 削除
			$isSuccess = _Delete_M_Staff($dbConnect,$post);
			if($isSuccess == false){
				db_Transaction_Rollback($dbConnect);
				$post['errorName'] = 'userMainte';
				$post['menuName']  = 'isMenuAdmin';
				$post['returnUrl'] = '/mainte/usermainte_top.php';
				$post['errorId'][] = '103';
				$errorUrl             = HOME_URL . 'error.php';
				redirectPost($errorUrl, $post);
			}

			// コミット
			db_Transaction_Commit($dbConnect);
		} else {
			// エラーチェック
			_check_Data($dbConnect,$post);

			// トランザクション開始
			db_Transaction_Begin($dbConnect);

            // 店舗変更があったかチェック
            $isMoveComp = _checkMoveComp($dbConnect,$post,$oldCompId);
            if ($isMoveComp) {
                // 店舗変更通知メール用に変更前のユーザー情報を取得
                $oldStaffInfo = getUserMaster($dbConnect, $post['StaffSeqID']);                
            } 

			// 更新
			$isSuccess = _Update_M_Staff($dbConnect,$post);

            if ($isSuccess) {
                // 申請済発注情報の発送先を変更
                $isSuccess = _Update_T_Order($dbConnect,$post);
            }

			if($isSuccess == false){
				db_Transaction_Rollback($dbConnect);
				$post['errorName'] = 'userMainte';
				$post['menuName']  = 'isMenuAdmin';
				$post['returnUrl'] = '/mainte/usermainte_top.php';
				$post['errorId'][] = '102';
				$errorUrl             = HOME_URL . 'error.php';
				redirectPost($errorUrl, $post);
			}

			// コミット
			db_Transaction_Commit($dbConnect);

            // 店舗が変更されていたらメール送信
            if ($isMoveComp) {
                sendMoveInfo($dbConnect,$post,$oldCompId, $oldStaffInfo);
            }

		}

		$errorUrl             = '/mainte/usermainte_top.php';
		redirectPost($errorUrl, "");
		break;
		
	case 'kakunin':
		
		$staffCode     = trim($post['staffCode']);		// 社員番号
		$personName    = trim($post['personName']);		// 氏名
		$staffFirstDay = trim($post['staffFirstDay']);	// 初回貸与年月日
		$staffNextDay  = trim($post['staffNextDay']);	// 再貸与予定年月日	
		$hatureibi     = trim($post['hatureibi']);		// 発令日
		$staffNextCode = trim($post['staffNextCode']);	// 発令後・社員番号
		$NextCompID    = trim($post['compNextSelect']);	// 発令後・個所
		$StaffSeqID    = trim($post['StaffSeqID']);		// シーケンスID
		$nowPage       = trim($post['nowPage']);		
		$motoStaffCode = trim($post['motoStaffCode']);	// 重複登録用

		$isUpdateFlag = trim($post['isUpdateFlag']);
		$ope = trim($post['motoMode']);
		break;
}


// 検索条件
$searchCompCd     = $post['searchCompCd'];
$searchCompName   = $post['searchCompName'];
$searchCompId     = $post['searchCompId'];
$searchStaffCode  = $post['searchStaffCode'];
$searchPersonName = $post['searchPersonName'];
$isSelectedAdmin  =	$post['isSelectedAdmin'];
$nowPage          =	$post['nowPage'];
// 元スタッフID設定
if(trim($post['motoStaffCode']) == ""){
	$motoStaffCode = trim($userData['StaffCode']);
}else{
	$motoStaffCode = trim($post['motoStaffCode']);
}

# $hidden = makehidden($post);

// 表示する値の成型 ここまで +++++++++++++++++++++++++++++++++++++++++++++++++++

// 追加処理
function _Insert_M_Staff($dbConnect,$post){

	$sql  = " INSERT INTO M_Staff (";
	$sql .= "  CompID";				// 店舗ID
	$sql .= " ,CompCd";				// 店舗コード
	$sql .= " ,StaffCode";			// スタッフコード
	$sql .= " ,PersonName";			// スタッフ氏名
	$sql .= " ,HatureiDay";			// 発令日
	$sql .= " ,NextCompID";			// 人事異動・店舗ID
	$sql .= " ,Del";				// 削除フラグ
	$sql .= " ,RegistDay";			// 登録日
	$sql .= " ,RegistUser";			// 登録ユーザー
	$sql .= " ,UpdDay";				// 更新日
	$sql .= " ,UpdUser";			// 更新ユーザー
	$sql .= " ) VALUES (";
	$sql .= " '".db_Escape(trim($post['selectCompId']))."'";	// 施設ID
 	$sql .= ",'".db_Escape(trim($post['selectCompCd']))."'";	// 施設コード
	$sql .= ",'".db_Escape(trim($post['staffCode']))."'";		// スタッフコード
	$sql .= ",'".db_Escape(trim($post['personName']))."'";		// スタッフ氏名
	$sql .= " ,NULL";											// 発令日
	$sql .= " ,NULL";											// 人事異動・店舗ID
	$sql .= " ,".DELETE_OFF;									// 削除フラグ
	$sql .= " ,GETDATE()";										// 登録日
	$sql .= " ,'".db_Escape(trim($_SESSION['NAMECODE']))."' ";	// 登録ユーザー
	$sql .= " ,NULL";											// 更新日
	$sql .= " ,NULL";											// 更新ユーザー
	$sql .= " )";

	$isSuccess = db_Execute($dbConnect, $sql);
	
 	// 実行結果が失敗の場合
	if ($isSuccess == false) {
		return false;
	}
	return true;
}

// 更新処理
function _Update_M_Staff($dbConnect,$post){

    // T_Staffに登録があれば更新
    $sql  = " SELECT";
    $sql .=     " COUNT (*) AS count_data";
    $sql .= " FROM";
    $sql .=     " T_Staff";
    $sql .= " WHERE";
    $sql .=     " StaffID = '" . db_Escape(trim($post['StaffSeqID']))."'";
    $sql .= " AND";
    $sql .=     " Del = ".DELETE_OFF;

    $result = db_Read($dbConnect, $sql);

    if ($result[0]['count_data'] != 0) {

        $sql  = " UPDATE T_Staff SET  ";
        $sql .= "  CompID        = '".db_Escape(trim($post['selectCompId']))."'";
        $sql .= " ,StaffCode     = '".db_Escape(trim($post['staffCode']))."'";
        $sql .= " ,UpdDay        = GETDATE()";
        $sql .= " ,UpdUser       = '".db_Escape(trim($_SESSION['NAMECODE']))."' ";
        $sql .= " WHERE ";
        $sql .=     " StaffID = '" . db_Escape(trim($post['StaffSeqID']))."'";
	    $sql .= " AND";
        $sql .=     " Del = ".DELETE_OFF;

        $isSuccess = db_Execute($dbConnect, $sql);

        // 実行結果が失敗の場合
        if ($isSuccess == false) {
            return false;
        }

    }

    // M_Staffを更新
    $sql  = " UPDATE M_Staff SET  ";
    $sql .= "  CompID        = '".db_Escape(trim($post['selectCompId']))."'";
    $sql .= " ,CompCd        = '".db_Escape(trim($post['selectCompCd']))."'";
    $sql .= " ,StaffCode     = '".db_Escape(trim($post['staffCode']))."'";
    $sql .= " ,PersonName    = '".db_Escape(trim($post['personName']))."'";

    if($post['hatureibi'] == ""){
        $sql .= " ,HatureiDay = NULL";
    }else{
        $sql .= " ,HatureiDay = '".db_Escape(trim($post['hatureibi']))."'";
    }
    $sql .= " ,NextCompID    = '".db_Escape(trim($post['selectNextCompId']))."'";

    $sql .= " ,UpdDay        = GETDATE()";
    $sql .= " ,UpdUser       = '".db_Escape(trim($_SESSION['NAMECODE']))."' ";
    $sql .= " WHERE ";
    $sql .=     " Del = ".DELETE_OFF;
    $sql .= " AND";
    $sql .=     " StaffSeqID = '" . db_Escape(trim($post['StaffSeqID']))."'";

    $isSuccess = db_Execute($dbConnect, $sql);

    // 実行結果が失敗の場合
    if ($isSuccess == false) {
        return false;
    }

	return true;
}

// 更新処理
function _Update_T_Order($dbConnect,$post){

    // T_Orderの情報を更新
    $sql  = " UPDATE T_Order SET  ";
    $sql .= "  CompID                 = '".db_Escape(trim($post['selectCompId']))."'";
    $sql .= " ,StaffCode              = '".db_Escape(trim($post['staffCode']))."'";
    $sql .= " ,PersonName             = '".db_Escape(trim($post['personName']))."'";

    $sql .= " ,UpdDay                 = GETDATE()";
    $sql .= " ,UpdUser                = '".db_Escape(trim($_SESSION['NAMECODE']))."' ";
    $sql .= " WHERE ";
    $sql .=     " StaffID             = '" . db_Escape(trim($post['StaffSeqID']))."'";
    $sql .= " AND";
    $sql .=     " Del                 = ".DELETE_OFF;

    $isSuccess = db_Execute($dbConnect, $sql);

    // 実行結果が失敗の場合
    if ($isSuccess == false) {
        return false;
    }

    // まだ倉庫に送信していないデータがあれば更新
    $sql  = " SELECT";
    $sql .=     " COUNT (*) AS count_data";
    $sql .= " FROM";
    $sql .=     " T_Order";
    $sql .= " WHERE";
    $sql .=     " StaffID = '" . db_Escape(trim($post['StaffSeqID']))."'";
    $sql .= " AND";
    $sql .=     " Status IN (".STATUS_APPLI.",".STATUS_APPLI_ADMIT.",".STATUS_NOT_RETURN.",".STATUS_NOT_RETURN_ADMIT.",".STATUS_LOSS.",".STATUS_LOSS_ADMIT.")";
    $sql .= " AND";
    $sql .=     " Del = ".DELETE_OFF;

    $result = db_Read($dbConnect, $sql);

    if ($result[0]['count_data'] != 0) {
        
        $shipData = getShipData($dbConnect,$post['selectCompId']);

        $sql  = " UPDATE T_Order SET  ";
        $sql .= "  AppliCompCd            = '".db_Escape(trim($shipData['CompCd']))."'";
        $sql .= " ,AppliCompName          = '".db_Escape(trim($shipData['CompName']))."'";
        $sql .= " ,Zip                    = '".db_Escape(trim($shipData['Zip']))."'";
        $sql .= " ,Adrr                   = '".db_Escape(trim($shipData['Adrr']))."'";
        $sql .= " ,Tel                    = '".db_Escape(trim($shipData['Tel']))."'";
        $sql .= " ,ShipName               = '".db_Escape(trim($shipData['ShipName']))."'";
        $sql .= " ,TantoName              = '".db_Escape(trim($shipData['TantoName']))."'";

        $sql .= " ,UpdDay                 = GETDATE()";
        $sql .= " ,UpdUser                = '".db_Escape(trim($_SESSION['NAMECODE']))."' ";
        $sql .= " WHERE ";
        $sql .=     " StaffID             = '" . db_Escape(trim($post['StaffSeqID']))."'";
        $sql .= " AND";
        $sql .=     " Status IN (".STATUS_APPLI.",".STATUS_APPLI_ADMIT.",".STATUS_NOT_RETURN.",".STATUS_NOT_RETURN_ADMIT.",".STATUS_LOSS.",".STATUS_LOSS_ADMIT.")";
        $sql .= " AND";
        $sql .=     " Del                 = ".DELETE_OFF;

        $isSuccess = db_Execute($dbConnect, $sql);

        // 実行結果が失敗の場合
        if ($isSuccess == false) {
            return false;
        }

    }

    return true;
}

// 削除処理
function _Delete_M_Staff($dbConnect,$post){

    // M_Staffを更新
    $sql  = " UPDATE M_Staff SET ";
	$sql .= 	"  Del = 1";
    $sql .= 	" ,UpdDay        = GETDATE()";
    $sql .= 	" ,UpdUser       = '".db_Escape(trim($_SESSION['NAMECODE']))."' ";
    $sql .= " WHERE ";
    $sql .= 	" Del = ".DELETE_OFF;
    $sql .= " AND";
    $sql .= 	" StaffSeqID = '" . db_Escape(trim($post['StaffSeqID']))."'";

    $isSuccess = db_Execute($dbConnect, $sql);

    // 実行結果が失敗の場合
    if ($isSuccess == false) {
        return false;
    }

	return true;
}

// 商品の貸与中チェック
function _Check_StaffOrder($dbConnect,$post) {

	$sql  = " SELECT";
	$sql .= 	" COUNT(StaffDetID) AS cnt";
	$sql .= " FROM";
	$sql .= 	" T_Staff_Details AS tsd";
	$sql .= " INNER JOIN";
	$sql .= 	" T_Staff AS ts";
	$sql .= " ON";
	$sql .= 	" tsd.StaffID = ts.StaffID ";
	$sql .= " WHERE";
	$sql .= 	" ts.StaffCode = '" . $post['motoStaffCode'] . "'";
	$sql .= " AND";
	$sql .= 	" tsd.Del = ".DELETE_OFF;
	$sql .= " AND";
	$sql .= 	" ts.Del = ".DELETE_OFF;
	$sql .= " AND";
	$sql .= 	" tsd.Status IN (";
	$sql .= 	" " . STATUS_APPLI;					// 申請済(承認待)
	$sql .= 	"," . STATUS_APPLI_ADMIT;			// 申請済
	$sql .= 	"," . STATUS_STOCKOUT;				// 在庫切
	$sql .= 	"," . STATUS_ORDER;					// 受注済
	$sql .= 	"," . STATUS_SHIP;					// 出荷済
	$sql .= 	"," . STATUS_DELIVERY;				// 納品済
	$sql .= 	"," . STATUS_NOT_RETURN_ADMIT;		// 返却申請済
	$sql .= 	"," . STATUS_NOT_RETURN_ORDER;		// 返却受注済
	$sql .= 	"," . STATUS_LOSS_ADMIT;			// 紛失申請済
	$sql .= " )";

	$result = db_Read($dbConnect, $sql);

	if($result[0]['cnt'] == 0){
		return true;

	}else{
		return false;

	}
}

/*
 * ユーザーマスタ情報を取得する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$userId         => 検索ユーザーID
 * 戻り値：$result         => ユーザーマスタ情報
 *
 * create 2007/03/26 H.Osugi
 *
 */
function getUserMaster($dbConnect, $StaffSeqID) {

	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	"  ms.StaffSeqID";
	$sql .= 	" ,ms.CompID";
	$sql .= 	" ,mc1.CompCd AS CompCd";
	$sql .= 	" ,mc1.CompName AS CompName";
	$sql .= 	" ,ms.StaffCode";
	$sql .= 	" ,ms.PersonName";
	$sql .= 	" ,CONVERT(char, ms.HatureiDay, 11) as HatureiDay";	
	$sql .=		" ,ms.NextCompID";
	$sql .= 	" ,mc2.CompCd AS NextCompCd";
	$sql .=		" ,mc2.CompName AS NextCompName";
	$sql .= " FROM";
	$sql .= 	" M_Staff AS ms";
	$sql .= " LEFT JOIN M_Comp AS mc1";
	$sql .= 	" ON ms.CompID = mc1.CompID";
	$sql .= 	" AND mc1.Del = ".DELETE_OFF;
	$sql .= " LEFT JOIN M_Comp AS mc2";
	$sql .= 	" ON ms.NextCompID = mc2.CompID";
	$sql .= 	" AND mc2.Del = ".DELETE_OFF;

	$sql .= " WHERE";
	$sql .= 	" ms.Del = ".DELETE_OFF;
	$sql .= " AND";
	$sql .= 	" ms.StaffSeqID = '" . db_Escape($StaffSeqID) . "'";

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
	 	return false;
	}

	return $result[0];
}

// StaffCodeが存在するかチェック
function _Seach_StaffCode($dbConnect, $StaffCode) {

	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	"  count(ms.StaffSeqID) as cnt";
	$sql .= " FROM";
	$sql .= 	" M_Staff AS ms";
	$sql .= " WHERE";
	$sql .= 	" ms.Del = ".DELETE_OFF;
	$sql .= " AND";
	$sql .= 	" ms.StaffCode = '" . db_Escape($StaffCode) . "'";

	$result = db_Read($dbConnect, $sql);

	if($result[0]['cnt'] == 0){
		return true;
	}else{
		return false;
	}
}

/*
 * Compマスター情報を取得する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$compId         => 検索企業CD
 * 戻り値：$result         => ユーザーマスタ情報
 *
 * create 2007/03/26 H.Osugi
 *
 */
function getCompMaster($dbConnect,$compId="") {

	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" CompID";
	$sql .= 	",CompCd";
	$sql .= 	",CompName";
	$sql .= " FROM";
	$sql .= 	" M_Comp";
	$sql .= " WHERE";
	$sql .= 	" Del = ".DELETE_OFF;
	$sql .= " AND";
    $sql .=     " ShopFlag <> 0";        // ShopFlagが0以外の店舗を表示する

	if($compId != ""){
		$sql .= " AND";
		$sql .= 	" CompID  = '" . db_Escape($compId) . "'";
	}

	$sql .= " ORDER BY";
	// 並び順変更
	//$sql .= 	" CompCd";
	$sql .= 	" HonbuCd,";
	$sql .= 	" ShibuCd,";
	$sql .= 	" CompCd";

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	return $result;
}

// 発送先情報を取得
function getShipData ($dbConnect,$compId) {

    $sql  = "";
    $sql .= " SELECT";
    $sql .=     " CompID";
    $sql .=     ",CompCd";
    $sql .=     ",CompName";
    $sql .=     ",Zip";
    $sql .=     ",Adrr";
    $sql .=     ",Tel";
    $sql .=     ",ShipName";
    $sql .=     ",TantoName";
    $sql .= " FROM";
    $sql .=     " M_Comp";
    $sql .= " WHERE";
    $sql .=     " Del = ".DELETE_OFF;
    $sql .= " AND";
    $sql .=     " ShopFlag <> 0";        // ShopFlagが0以外の店舗を表示する

    if($compId != ""){
        $sql .= " AND";
        $sql .=     " CompID  = '" . db_Escape($compId) . "'";
    }

    $sql .= " ORDER BY CompCd";
    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合
    if (count($result) <= 0) {
        $result = array();
        return $result;
    }

    return $result[0];
    
}

// hidden値作成
function makehidden($post){
	$i = 0;
	foreach($post as $key => $val){
		
		if(is_array($val)){
			foreach($val as $key2 => $val2){
				$hiddens[$i]['hdn'] = "<input type=\"hidden\" name=\"".$key."[".$key2."]\" value=\"".$val2."\">\n";
				$i++;
			}
		}else{
			$hiddens[$i]['hdn'] = "<input type=\"hidden\" name=\"".$key."\" value=\"".$val."\">\n";
			$i++;
		}
		
	}
	return $hiddens;
}

// 店舗移動チェック
function _checkMoveComp($dbConnect,$post,&$oldCompId) {
    
    $sql  = " SELECT";
    $sql .=     " CompID";
    $sql .= " FROM";
    $sql .=     " M_Staff";
    $sql .= " WHERE";
    $sql .=     " StaffSeqID = '" . db_Escape($post['StaffSeqID']) . "'";
    $sql .= " AND";
    $sql .=     " Del = ".DELETE_OFF;

    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合
    if (count($result) <= 0) {
        return false;
    }

    $oldCompId = $result[0]['CompID'];

    // 登録店舗と画面入力値を比較
    if ($oldCompId == $post['selectCompId']) {
        return false;
    }

    return true;
}

// データチェック
function _check_Data($dbConnect,$post){

	$post['errorId'] = "";
	$errflg = false;

	// 職員コードが空の時エラー
	if(trim($post['staffCode']) == ""){
		$errflg =true;
		$post['errorId'][] = '011';
	}else{
		// 職員コードに変更があった場合は既に存在するコードかどうかをチェック
		if(trim($post['staffCode']) != trim($post['motoStaffCode'])){
			$rtn = _Seach_StaffCode($dbConnect, trim($post['staffCode']));
			// 存在チェック
			if(!$rtn){
				$errflg =true;
				$post['errorId'][] = '016';
			}
		}
		// 職員コードは数字アルファベットの半角12桁
     	if(!preg_match('/^[0-9a-zA-Z]{12,12}$/', $post['staffCode'])){
			$errflg =true;
			$post['errorId'][] = '021';
		}
	}
	// 社員名が空のとき
	if(trim($post['personName']) == ""){
		$errflg =true;
		$post['errorId'][] = '012';
	}

	// 施設
	if(trim($post['selectCompId']) == ""){
		$errflg =true;
		$post['errorId'][] = '013';
	}

	// 発令以外が入力されている。
	if(trim($post['selectNextCompId']) != ""){
		if(trim($post['hatureibi']) == ""){
			$errflg =true;
			$post['errorId'][] = '019';
		}
	}

	// 発令日
	if(trim($post['hatureibi']) != ""){
		// 発令日日付チェック
		if(!_chk_is_date2(trim($post['hatureibi']))){	
			$errflg =true;
			$post['errorId'][] = '017';
		}

		// 異動先施設
		if(trim($post['selectNextCompId']) == ""){
			$errflg =true;
			$post['errorId'][] = '015';
		}
	}

	if($errflg){
		$post['errorName'] = 'userMainte';
		$post['menuName']  = 'isMenuAdmin';
		$post['returnUrl'] = 'mainte/usermainte.php';
		$errorUrl             = HOME_URL . 'error.php';
		$post['motoMode'] = $post['Mode'];
		$post['Mode'] = "kakunin";
		redirectPost($errorUrl, $post);
	}

	return true;

}
// 日付判定
function _chk_is_date1($pValue, $pSplit = "/")
{
    if ( substr_count($pValue, $pSplit) <> 2 ) {
        return FALSE;
    }
    
    list($year, $month, $day) = explode($pSplit, $pValue);
    if ( ereg('^[0-9]{2}', $year) && _chk_is_number2($month) && _chk_is_number2($day) ) {
        $rtn =  ( checkdate($month, $day, $year) ) ? TRUE : FALSE;
    } else {
        $rtn = FALSE;
    }
    return $rtn;
}
function _chk_is_date2($pValue, $pSplit = "/")
{
    if ( substr_count($pValue, $pSplit) <> 2 ) {
        return FALSE;
    }
    
    list($year, $month, $day) = explode($pSplit, $pValue);
    if ( ereg('^[0-9]{4}', $year) && _chk_is_number2($month) && _chk_is_number2($day) ) {
        $rtn =  ( checkdate($month, $day, $year) ) ? TRUE : FALSE;
    } else {
        $rtn = FALSE;
    }
    return $rtn;
}

// 数値判定
function _chk_is_number2($pValue)
{
    if ( !ereg('^[0-9]+$', $pValue) ) {
        $rtn = FALSE;
    } else {
        $rtn = TRUE;
    }
    return $rtn;
}

// 店舗移動メールを送信する
function sendMoveInfo($dbConnect,$post,$oldCompId,$oldStaffInfo) {

    // 新旧の店舗情報を取得
    $result = getCompMaster($dbConnect,trim($post['selectCompId']));
    $compInfo['new'] = $result[0];

    $result = getCompMaster($dbConnect,trim($oldCompId));
    $compInfo['old'] = $result[0];

    // 現在のスタッフ情報を取得
    $staffInfo['new'] = getUserMaster($dbConnect, $post['StaffSeqID']); 
    $staffInfo['old'] = $oldStaffInfo; 

    
    $filePath = '../../mail_template/';

	// Modify by Y.Furukawa at 2017/12/12
    //$isSuccess = moveCompMail($dbConnect,$filePath, $compInfo, $staffInfo, &$subject, &$message);
    $isSuccess = moveCompMail($dbConnect,$filePath, $compInfo, $staffInfo, $subject, $message);

    if ($isSuccess == false) {
        return false;
    }
    $toAddr = MAIL_GROUP_6;

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
?><!DOCTYPE html>
<html>
  <head>
    <META http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <script src="//code.jquery.com/jquery-1.12.4.min.js"></script>
    <link href="/main.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="//code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />
    <script src="//code.jquery.com/jquery-1.9.1.js"></script>
    <script src="//code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
    <title>制服管理システム</title>
	<script language="JavaScript">
    <!--
    // HttpRequestオブジェクト取得
	function newXMLHttpRequest () {
	    if ( typeof ActiveXObject!="undefined" ) {
	        return new ActiveXObject("Microsoft.XMLHTTP");
	    } else if ( typeof XMLHttpRequest!="undefined" ) {
	        return new XMLHttpRequest();
	    } else{
	        return null;
	    }
	}

    // 一括で変更すべき注文情報があるかどうかを検索し、Submit
    function checkOrderSubmit(staffId) {
    
        var xmlHttp = newXMLHttpRequest();
        if ( ! xmlHttp ) return false;

        var url = 'checkUpdateOrder.php' + '?id=' + escape(staffId);

        xmlHttp.onreadystatechange = function() {
            if ((xmlHttp.readyState == 4) && (xmlHttp.status == 200)) {
                if (xmlHttp.status == 200) {
                    var xmlData = xmlHttp.responseText;
                    if (xmlData) {
//                        if (confirm('職員情報を登録します。申請済発注情報の発送先も変更されますが、よろしいですか？')) {
//                            submitData('./usermainte.php','<?php isset($ope) ? print($ope) : print('&#123;ope&#125;'); ?>');
//                        }
                        if (confirm('職員情報を登録します。よろしいですか？')) {
                            submitData('./usermainte.php','<?php isset($ope) ? print($ope) : print('&#123;ope&#125;'); ?>');
                        }
                    } else {
                        if (confirm('職員情報を登録します。よろしいですか？')) {
                            submitData('./usermainte.php','<?php isset($ope) ? print($ope) : print('&#123;ope&#125;'); ?>');
                        }
                    }
                } else if (xmlHttp.status == 404) {
                }
            } else if (xmlHttp.readyState == 3){
            } else if (xmlHttp.readyState == 2){
            } else if (xmlHttp.readyState == 1){
            }
        };

        xmlHttp.open('GET', url);
        xmlHttp.send(null);
    
    }

    function submitData(url,mode) {

	  document.pagingForm.Mode.value=mode; 
	  document.pagingForm.action=url; 
	  document.pagingForm.submit();
      return false;

    }
	// 再貸与計算
	function fn_RetireScheduleDay(){
		// 初回取得
 		staffFirstDay = document.pagingForm.staffFirstDay.value;
		// 誕生日が日付の場合処理
 		if(fn_dateCheck(staffFirstDay)){
 			var Nextdate;
 		 	// 再貸与日作成
 			Nextdate = fn_rtn_Nextdate(staffFirstDay);
 			//再貸与日セット
 			document.pagingForm.staffNextDay.value = Nextdate;
 
		}else{
// 			// 日付で無い時の処理
// 			// alert("誕生日に日付ではない値が入力されています。");
 		}
		
	}
	
	// 次回作成
	function fn_rtn_Nextdate(birth){
	
		var Nextdate;
 		
 		days = birth.split('/');
 		var vYear = days[0] - 1; 
 		var vMonth = days[1];
 		var vDay = days[2]; 
		// defineに設定されている年数を加算する。
 		vYear = vYear + <?php isset($next_taiyoNum) ? print($next_taiyoNum) : print('&#123;next_taiyoNum&#125;'); ?>;
		Nextdate = vYear + '/' + vMonth + '/' + vDay ;

		return Nextdate;
	}
	
	// 日付チェック
	function fn_dateCheck(day){
		// 文字列分割
		days = day.split('/');
		
		var vYear = days[0] - 0; 
		var vMonth = days[1] - 1; // Javascriptは、0-11で表現 
		var vDay = days[2] - 0; 

		if(days[0].length != 4){
			return false;
		}
		if(days[1].length > 2){
			return false;
		}
		if(days[2].length > 2){
			return false;
		}
		
		// 月,日の妥当性チェック 
		if(vMonth >= 0 && vMonth <= 11 && vDay >= 1 && vDay <= 31){ 
			var vDt = new Date(vYear, vMonth, vDay); 
			if(isNaN(vDt)){ 
				return false; 
			}else if(vDt.getFullYear() == vYear && vDt.getMonth() == vMonth && vDt.getDate() == vDay){ 
				return true; 
			}else{ 
				return false; 
			} 
		}else{ 
			return false; 
		} 
		
	}
    // -->
    </script>
    <script type="text/javascript">
      $(function() {
        $("#datepicker").datepicker();
        $('#datepicker').datepicker("option", "dateFormat", 'yy/mm/dd' );
        $("#datepicker").datepicker("setDate", "<?php isset($hatureibi) ? print($hatureibi) : print('&#123;hatureibi&#125;'); ?>");
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
        

        <form method="post" action="#" name="pagingForm">
          <div id="contents">
            <h1>職員マスタメンテナンス</h1>

            <table width="700" border="0" cellpadding="0" cellspacing="0" class="tb_1">
              <tr height="30">
                <td class="line" width="140" align="left">職員コード</td>
                <td class="line" width="560" align="left">
                  <input type="text" name="staffCode" value="<?php isset($staffCode) ? print($staffCode) : print('&#123;staffCode&#125;'); ?>" size="20" maxlength="12"><font color="red">(半角12桁)</font>
                </td>
              </tr>
              <tr height="30">
                <td class="line" align="left">氏名</td>
                <td class="line" align="left"><input type="text" name="personName" value="<?php isset($personName) ? print($personName) : print('&#123;personName&#125;'); ?>" size="20"><font color="red">(苗字と名前の間に全角スペースを入れて下さい)</font></td>
              </tr>
              <tr height="30">
                <td class="line" align="left">施設</td>
                <td class="line" align="left">
                  <input type="text" name="selectCompName" value="<?php isset($selectCompName) ? print($selectCompName) : print('&#123;selectCompName&#125;'); ?>" style="width:320px;" readonly="readonly">
                  <input type="hidden" name="selectCompCd" value="<?php isset($selectCompCd) ? print($selectCompCd) : print('&#123;selectCompCd&#125;'); ?>">
                  <input type="hidden" name="selectCompId" value="<?php isset($selectCompId) ? print($selectCompId) : print('&#123;selectCompId&#125;'); ?>">
                  <input name="shop_btn" type="button" value="施設選択" onclick="window.open('./select_comp.php', 'selectComp', 'width=500, height=400, menubar=no, toolbar=no, scrollbars=yes'); return false;">
                </td>
              </tr>
              <!--------------------
              <tr height="30">
                <td class="line" align="left">施設</td>
                <td class="line" align="left">
                  <select name="compSelect" id="select">
                    <option value="">-- 施設選択 --</option>
<?php for ($i1_compSelect=0; $i1_compSelect<count($compSelect); $i1_compSelect++) { ?>
<?php if(!$compSelect[$i1_compSelect]['selected']) { ?>
                    <option value="<?php isset($compSelect[$i1_compSelect]['CompID']) ? print($compSelect[$i1_compSelect]['CompID']) : print('&#123;compSelect.CompID&#125;'); ?>"><?php isset($compSelect[$i1_compSelect]['CompName']) ? print($compSelect[$i1_compSelect]['CompName']) : print('&#123;compSelect.CompName&#125;'); ?></option>
<?php } ?>
<?php if($compSelect[$i1_compSelect]['selected']) { ?>
                    <option value="<?php isset($compSelect[$i1_compSelect]['CompID']) ? print($compSelect[$i1_compSelect]['CompID']) : print('&#123;compSelect.CompID&#125;'); ?>" selected="selected"><?php isset($compSelect[$i1_compSelect]['CompName']) ? print($compSelect[$i1_compSelect]['CompName']) : print('&#123;compSelect.CompName&#125;'); ?></option>
<?php } ?>
<?php } ?>
                  </select>
                </td>
              </tr>
              --------------------->
<?php if($isUpdateFlag) { ?>
              <tr height="30">
                <td class="line" align="left">削除</td>
                <td class="line" align="left">
                  <input type="checkbox" name="chkDelete" value="1" size="20">職員マスタからの削除　<span style="color:red">（貸与中の商品がある場合は削除できません）</span>
                </td>
              </tr>
<?php } ?>
            </table>

<?php if($isUpdateFlag) { ?>
            <h3>人事異動先情報</h3>

            <table width="700" border="0" cellpadding="0" cellspacing="0" class="tb_1">
              <tr height="30">
                <td class="line" width="140" align="left">更新実施日</td>
                <td class="line" width="560" align="left">
                  <input type="text" name="hatureibi" value="<?php isset($hatureibi) ? print($hatureibi) : print('&#123;hatureibi&#125;'); ?>" size="20" maxlength="10" id="datepicker"><font color="red">(YYYY/MM/DD)</font>
                </td>
              </tr>
              <tr height="30">
                <td class="line" align="left">施設</td>
                <td class="line" align="left">
                  <input type="text" name="selectNextCompName" value="<?php isset($selectNextCompName) ? print($selectNextCompName) : print('&#123;selectNextCompName&#125;'); ?>" style="width:320px;" readonly="readonly">
                  <input type="hidden" name="selectNextCompCd" value="<?php isset($selectNextCompCd) ? print($selectNextCompCd) : print('&#123;selectNextCompCd&#125;'); ?>">
                  <input type="hidden" name="selectNextCompId" value="<?php isset($selectNextCompId) ? print($selectNextCompId) : print('&#123;selectNextCompId&#125;'); ?>">
                  <input name="shop_btn" type="button" value="施設選択" onclick="window.open('./select_nextcomp.php', 'selectNextComp', 'width=500, height=400, menubar=no, toolbar=no, scrollbars=yes'); return false;">
                </td>
              </tr>
              <!--------------------
              <tr height="30">
                <td class="line" align="left">施設</td>
                <td class="line" align="left">
                  <select name="compNextSelect" id="select">
                    <option value="">-- 施設選択 --</option>
<?php for ($i1_compSelect2=0; $i1_compSelect2<count($compSelect2); $i1_compSelect2++) { ?>
<?php if(!$compSelect2[$i1_compSelect2]['selected']) { ?>
                    <option value="<?php isset($compSelect2[$i1_compSelect2]['CompID']) ? print($compSelect2[$i1_compSelect2]['CompID']) : print('&#123;compSelect2.CompID&#125;'); ?>"><?php isset($compSelect2[$i1_compSelect2]['CompName']) ? print($compSelect2[$i1_compSelect2]['CompName']) : print('&#123;compSelect2.CompName&#125;'); ?></option>
<?php } ?>
<?php if($compSelect2[$i1_compSelect2]['selected']) { ?>
                    <option value="<?php isset($compSelect2[$i1_compSelect2]['CompID']) ? print($compSelect2[$i1_compSelect2]['CompID']) : print('&#123;compSelect2.CompID&#125;'); ?>" selected="selected"><?php isset($compSelect2[$i1_compSelect2]['CompName']) ? print($compSelect2[$i1_compSelect2]['CompName']) : print('&#123;compSelect2.CompName&#125;'); ?></option>
<?php } ?>
<?php } ?>
                  </select>
                </td>
              </tr>
              --------------------->
            </table>
<?php } ?>

          </div>
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
          <div class="bot" align="center">
            <a href="#" onclick="submitData('./usermainte_top.php','');"><img src="../img/modoru.gif" alt="戻る" border="0"></a>
            &nbsp;&nbsp;&nbsp;&nbsp;
            <a href="#"><img src="../img/toroku.gif" alt="登録" border="0" onclick="checkOrderSubmit('<?php isset($StaffSeqID) ? print($StaffSeqID) : print('&#123;StaffSeqID&#125;'); ?>');"></a>
          </div>

          <input type="hidden" name="isUpdateFlag" value=<?php isset($isUpdateFlag) ? print($isUpdateFlag) : print('&#123;isUpdateFlag&#125;'); ?>>
          <input type="hidden" name="Mode">

          <input type="hidden" name="motoStaffCode"    value="<?php isset($motoStaffCode) ? print($motoStaffCode) : print('&#123;motoStaffCode&#125;'); ?>">
          <input type="hidden" name="nowPage"          value="<?php isset($nowPage) ? print($nowPage) : print('&#123;nowPage&#125;'); ?>">
          <input type="hidden" name="StaffSeqID"       value="<?php isset($StaffSeqID) ? print($StaffSeqID) : print('&#123;StaffSeqID&#125;'); ?>">
          <input type="hidden" name="searchCompCd"     value="<?php isset($searchCompCd) ? print($searchCompCd) : print('&#123;searchCompCd&#125;'); ?>">
          <input type="hidden" name="searchCompName"   value="<?php isset($searchCompName) ? print($searchCompName) : print('&#123;searchCompName&#125;'); ?>">
          <input type="hidden" name="searchCompId"     value="<?php isset($searchCompId) ? print($searchCompId) : print('&#123;searchCompId&#125;'); ?>">
          <input type="hidden" name="searchStaffCode"  value="<?php isset($searchStaffCode) ? print($searchStaffCode) : print('&#123;searchStaffCode&#125;'); ?>">
          <input type="hidden" name="searchPersonName" value="<?php isset($searchPersonName) ? print($searchPersonName) : print('&#123;searchPersonName&#125;'); ?>">
          <input type="hidden" name="isSelectedAdmin"  value="<?php isset($isSelectedAdmin) ? print($isSelectedAdmin) : print('&#123;isSelectedAdmin&#125;'); ?>">

        </form>
        <br><br><br>
        

      </div>
    </div>
  </body>
</html>

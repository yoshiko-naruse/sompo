<?php
/*
 * 承認完了画面
 * syounin_kanryo.src.php
 *
 * create 2007/04/23 H.Osugi
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


/* ./syounin.val.php start */

/*
 * エラー判定処理
 * syounin.val.php
 *
 * create 2007/04/23 H.Osugi
 *
 *
 */

/*
 * 機能  ：エラー判定を行う
 * 引数  ：$post       => POSTデータ
 * 戻り値：なし
 *
 * create 2007/04/23 H.Osugi
 *
 */
function validatePostData($post) {

	// 初期化
	$hiddens = array();

	// 承認/否認が選択されているか判定
	$isEmpty = true;
	$countOrderId = count($post['orderIds']);
	for ($i=0; $i<$countOrderId; $i++) {

		if (isset($post['acceptationY'][$post['orderIds'][$i]]) && $post['acceptationY'][$post['orderIds'][$i]] != '') {
			$isEmpty = false;
			break;
		}
		if (isset($post['acceptationN'][$post['orderIds'][$i]]) && $post['acceptationN'][$post['orderIds'][$i]] != '') {
			$isEmpty = false;
			break;
		}
	}

	if ($isEmpty == true) {
		$hiddens['errorId'][] = '001';
	}
	else {

		// 理由
		$isError = false;
		for ($i=0; $i<$countOrderId; $i++) {

			// 理由が存在しなければ初期化
			if (!isset($post['reason'][$post['orderIds'][$i]])) {
				$post['reason'][$post['orderIds'][$i]] = '';
			}

			// 理由の判定
			$result = checkData(trim($post['reason'][$post['orderIds'][$i]]), 'Text', false, 60);

			// エラーが発生したならば、エラーメッセージを取得
			switch ($result) {

				// 全角30文字超過ならば
				case 'max':
					$hiddens['errorId'][] = '011';
					$isError = true;
					break;
		
				default:
					break;
		
			}

			if ($isError == true) {
				break;
			}

		}
	
	}

	// エラーが存在したならば、エラー画面に遷移
	if (count($hiddens['errorId']) > 0) {

		$hiddens['errorName'] = 'syounin';
		$hiddens['menuName']  = 'isMenuAcceptation';
		$hiddens['returnUrl'] = 'syounin/syounin.php';
		$errorUrl             = HOME_URL . 'error.php';

		$post['errorFlg'] = '1';

		// POST値をHTMLエンティティ
		$post = castHtmlEntity($post); 

		$hiddenHtml = castHiddenError($post);

		$hiddens = array_merge($hiddens, $hiddenHtml);

		redirectPost($errorUrl, $hiddens);

	}

}


/* ./syounin.val.php end */



// 承認権限のないユーザが閲覧しようとするとTOPに強制遷移
if ($isLevelAdmin  == false && $isLevelAcceptation  == false) {

	$hiddens = array();
	redirectPost(HOME_URL . 'top.php', $hiddens);

}

// 変数の初期化 ここから *******************************************************
$post       = $_POST;				// POST値
// 変数の初期化 ここまで ******************************************************

// エラー判定
validatePostData($_POST);

// トランザクション開始
db_Transaction_Begin($dbConnect);

// 承認変更処理
$isSuccess = acceptationOrder($dbConnect, $post);

// 登録が失敗した場合はエラー画面へ遷移
if ($isSuccess == false) {

	// ロールバック
	db_Transaction_Rollback($dbConnect);

	$hiddens['errorName'] = 'syounin';
	$hiddens['menuName']  = 'isMenuAcceptation';
	$hiddens['returnUrl'] = 'syounin/syounin.php';
	$hiddens['errorId'][] = '903';
	$errorUrl             = HOME_URL . 'error.php';

	// エラー画面に強制遷移
	redirectPost($errorUrl, $hiddens);

}

// コミット
db_Transaction_Commit($dbConnect);

$countSearchStatus = count($post['searchStatus']);
for ($i=0; $i<$countSearchStatus; $i++) {
	$post['searchStatus[' . $i . ']'] = $post['searchStatus'][$i];
}

$post['searchFlg'] = '1';

// POST値をHTMLエンティティ
$post = castHtmlEntity($post); 

$notArrowKeys = array('searchStatus', 'acceptationY', 'acceptationN', 'reason', 'orderIds', 'returnOrderIds');

$hiddenHtml = array();
if (is_array($post) && count($post) > 0) {
	$hiddenHtml = castHiddenError($post, $notArrowKeys);
}

$nextUrl = HOME_URL . 'syounin/syounin.php';

// 承認画面に強制遷移
redirectPost($nextUrl, $hiddenHtml);

/*
 * 承認処理
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$post           => POST値
 * 戻り値：$isSuccess      => ture：承認成功 / false：承認失敗
 *
 * create 2007/04/23 H.Osugi
 *
 */
function acceptationOrder($dbConnect, $post) {

	$userName = getUserName($dbConnect, $_SESSION['NAMECODE'], 0);

	$countOrderId = count($post['orderIds']);
	for ($i=0; $i<$countOrderId; $i++) {

		// orderId
		$orderId = trim($post['orderIds'][$i]);

		$status = '';
		if (isset($post['acceptationY'][$orderId]) && trim($post['acceptationY'][$orderId]) != '') {
			$status = trim($post['acceptationY'][$orderId]);
		}
		if (isset($post['acceptationN'][$orderId]) && trim($post['acceptationN'][$orderId]) != '') {
			$status = trim($post['acceptationN'][$orderId]);
		}

		$reason = '';
		if (isset($post['reason'][$orderId]) && trim($post['reason'][$orderId]) != '') {
			$reason = trim($post['reason'][$orderId]);
		}

		$returnOrderId = '';
		if (isset($post['returnOrderIds'][$orderId]) && trim($post['returnOrderIds'][$orderId]) != '') {
			$returnOrderId = trim($post['returnOrderIds'][$orderId]);
		}

		// 規定の値で無かった場合は承認処理を行わない
		switch ($status) {
			case STATUS_APPLI_ADMIT:
			case STATUS_APPLI_DENY:
			case STATUS_NOT_RETURN_ADMIT:
			case STATUS_NOT_RETURN_DENY:
			case STATUS_LOSS_ADMIT:
			case STATUS_LOSS_DENY:
				break;
			default:
				$status = '';
				break;
		}
	
		if ($status == '') {
			continue;
		}

		$isSuccess = updateOrder($dbConnect, $orderId, $userName, $status, $reason);

		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}

		if ($returnOrderId != '') {

			$status = getOrderStatus ($dbConnect, $returnOrderId);

			$nextStatus = '';
			if (isset($post['acceptationY'][$orderId]) && trim($post['acceptationY'][$orderId]) != '') {

				$acceptation = true;

				switch ($status) {
					case STATUS_APPLI:
						$nextStatus = STATUS_APPLI_ADMIT;
						break;
					case STATUS_NOT_RETURN:
						$nextStatus = STATUS_NOT_RETURN_ADMIT;
						break;
					case STATUS_LOSS:
						$nextStatus = STATUS_LOSS_ADMIT;
						break;
					default:
						break;
				}

			}
			if (isset($post['acceptationN'][$orderId]) && trim($post['acceptationN'][$orderId]) != '') {

				$acceptation = false;

				switch ($status) {
					case STATUS_APPLI:
						$nextStatus = STATUS_APPLI_DENY;
						break;
					case STATUS_NOT_RETURN:
						$nextStatus = STATUS_NOT_RETURN_DENY;
						break;
					case STATUS_LOSS:
						$nextStatus = STATUS_LOSS_DENY;
						break;
					default:
						break;
				}

			}

			if ($nextStatus == '') {
				continue;
			}

			$isSuccess = updateOrder($dbConnect, $returnOrderId, $userName, $nextStatus, $reason, $acceptation);
	
			// 実行結果が失敗の場合
			if ($isSuccess == false) {
				return false;
			}
		}

	}


	return true;

}

/*
 * 承認処理
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$post           => POST値
 * 戻り値：$isSuccess      => ture：承認成功 / false：承認失敗
 *
 * create 2007/04/23 H.Osugi
 *
 */
function updateOrder($dbConnect, $orderId, $userName ,$status, $reason, $acceptation = true) {

	// Statusを取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" AppliMode";
	$sql .= " FROM";
	$sql .= 	" T_Order";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;

	$orderDatas = db_Read($dbConnect, $sql);

	$appliMode = $orderDatas[0]['AppliMode'];

	// T_Orderを変更する
	$sql  = "";
	$sql .= " UPDATE";
	$sql .= 	" T_Order";
	$sql .= " SET";
	$sql .= 	" Status = " . db_Escape($status) . ",";
	$sql .= 	" AgreeReason = '" . db_Escape($reason) . "',";
	$sql .= 	" AgreeUserID = '" . db_Escape($_SESSION['USERID']) . "',";
	$sql .= 	" AgreeNameCd = '" . db_Escape($_SESSION['NAMECODE']) . "',";
	$sql .= 	" AgreeName = '" . db_Escape($userName) . "',";
	$sql .= 	" AgreeDay = GETDATE(),";
	$sql .= 	" UpdDay = GETDATE(),";
	$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;

	$isSuccess = db_Execute($dbConnect, $sql);

	// 実行結果が失敗の場合
	if ($isSuccess == false) {
		return false;
	}

	// 返却の場合のみ処理が異なる
	if ($appliMode == APPLI_MODE_RETURN) {

		switch ($status) {
			case STATUS_NOT_RETURN_ADMIT:
			case STATUS_LOSS_ADMIT:
				$returnStatus = STATUS_NOT_RETURN_ADMIT;
				$lossStatus   = STATUS_LOSS_ADMIT;
				break;
			case STATUS_NOT_RETURN_DENY:
			case STATUS_LOSS_DENY:
				$returnStatus = STATUS_NOT_RETURN_DENY;
				$lossStatus   = STATUS_LOSS_DENY;
				break;
			default:
				break;
		}

		// T_Staff_Detailsを変更する
		$sql  = "";
		$sql .= " UPDATE";
		$sql .= 	" T_Staff_Details";
		$sql .= " SET";
		$sql .= 	" Status = " . db_Escape($returnStatus) . ",";
		$sql .= 	" UpdDay = GETDATE(),";
		$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= " WHERE";
	
		$sql .= 	" ReturnDetID IN (";
		$sql .= 		" SELECT";
		$sql .= 			" OrderDetID";
		$sql .= 		" FROM";
		$sql .= 			" T_Order_Details";
		$sql .= 		" WHERE";
		$sql .= 			" OrderID = '" . db_Escape($orderId) . "'";
		$sql .= 		" AND";
		$sql .= 			" Del = " . DELETE_OFF;
		$sql .= 	" )";
		$sql .= " AND";
		$sql .= 	" Status = " . STATUS_NOT_RETURN;			// 未返却（承認待ち）
		$sql .= " AND";
		$sql .= 	" Del = " . DELETE_OFF;
	
		$isSuccess = db_Execute($dbConnect, $sql);
	
		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}

		// T_Staff_Detailsを変更する
		$sql  = "";
		$sql .= " UPDATE";
		$sql .= 	" T_Staff_Details";
		$sql .= " SET";
		$sql .= 	" Status = " . db_Escape($lossStatus) . ",";
		$sql .= 	" UpdDay = GETDATE(),";
		$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= " WHERE";
	
		$sql .= 	" ReturnDetID IN (";
		$sql .= 		" SELECT";
		$sql .= 			" OrderDetID";
		$sql .= 		" FROM";
		$sql .= 			" T_Order_Details";
		$sql .= 		" WHERE";
		$sql .= 			" OrderID = '" . db_Escape($orderId) . "'";
		$sql .= 		" AND";
		$sql .= 			" Del = " . DELETE_OFF;
		$sql .= 	" )";
		$sql .= " AND";
		$sql .= 	" Status = " . STATUS_LOSS;			// 紛失（承認待ち）
		$sql .= " AND";
		$sql .= 	" Del = " . DELETE_OFF;
	
		$isSuccess = db_Execute($dbConnect, $sql);
	
		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}

		// T_Order_Detailsを変更する
		$sql  = "";
		$sql .= " UPDATE";
		$sql .= 	" T_Order_Details";
		$sql .= " SET";
		$sql .= 	" Status = " . db_Escape($returnStatus) . ",";
		$sql .= 	" UpdDay = GETDATE(),";
		$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= " WHERE";
		$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
		$sql .= " AND";
		$sql .= 	" Status = " . STATUS_NOT_RETURN;			// 未返却（承認待ち）
		$sql .= " AND";
		$sql .= 	" Del = " . DELETE_OFF;
	
		$isSuccess = db_Execute($dbConnect, $sql);
	
		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}

		// T_Order_Detailsを変更する
		$sql  = "";
		$sql .= " UPDATE";
		$sql .= 	" T_Order_Details";
		$sql .= " SET";
		$sql .= 	" Status = " . db_Escape($lossStatus) . ",";
		$sql .= 	" UpdDay = GETDATE(),";
		$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= " WHERE";
		$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
		$sql .= " AND";
		$sql .= 	" Status = " . STATUS_LOSS;			// 紛失（承認待ち）
		$sql .= " AND";
		$sql .= 	" Del = " . DELETE_OFF;
	
		$isSuccess = db_Execute($dbConnect, $sql);
	
		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}

	}

	// 交換で否認の場合
	elseif ($appliMode == APPLI_MODE_EXCHANGE && $acceptation == false) {

		if ($status == STATUS_APPLI_ADMIT || $status == STATUS_APPLI_DENY) {

			// T_Staff_Detailsを変更する
			$sql  = "";
			$sql .= " UPDATE";
			$sql .= 	" T_Staff_Details";
			$sql .= " SET";
			$sql .= 	" Del = " . DELETE_ON . ",";
			$sql .= 	" UpdDay = GETDATE(),";
			$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
			$sql .= " WHERE";
			$sql .= 	" OrderDetID IN (";
			$sql .= 		" SELECT";
			$sql .= 			" OrderDetID";
			$sql .= 		" FROM";
			$sql .= 			" T_Order_Details";
			$sql .= 		" WHERE";
			$sql .= 			" OrderID = '" . db_Escape($orderId) . "'";
			$sql .= 		" AND";
			$sql .= 			" Del = " . DELETE_OFF;
			$sql .= 	" )";
			$sql .= " AND";
			$sql .= 	" Del = " . DELETE_OFF;
		
			$isSuccess = db_Execute($dbConnect, $sql);
		
			// 実行結果が失敗の場合
			if ($isSuccess == false) {
				return false;
			}

		}
		else {

			// Statusを取得する
			$sql  = "";
			$sql .= " SELECT";
			$sql .= 	" tod1.OrderDetID,";
			$sql .= 	" tod2.Status";
			$sql .= " FROM";
			$sql .= 	" T_Order_Details tod1";
			$sql .= " INNER JOIN";
			$sql .= 	" T_Order_Details tod2";
			$sql .= " ON";
			$sql .= 	" tod1.MotoOrderDetID = tod2.OrderDetID";
			$sql .= " AND";
			$sql .= 	" tod2.Del = " . DELETE_OFF;
			$sql .= " WHERE";
			$sql .= 	" tod1.OrderID = '" . db_Escape($orderId) . "'";
			$sql .= " AND";
			$sql .= 	" tod1.Del = " . DELETE_OFF;


			$result = db_Read($dbConnect, $sql);

			$countStatus = count($result);
			for ($i=0; $i<$countStatus; $i++) {

				// T_Staff_Detailsを変更する
				$sql  = "";
				$sql .= " UPDATE";
				$sql .= 	" T_Staff_Details";
				$sql .= " SET";
				$sql .= 	" ReturnDetID = NULL,";
				$sql .= 	" Status = '" . db_Escape($result[$i]['Status']) . "',";
				$sql .= 	" UpdDay = GETDATE(),";
				$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
				$sql .= " WHERE";
				$sql .= 	" ReturnDetID = '" . db_Escape($result[$i]['OrderDetID']) . "'";
			
				$isSuccess = db_Execute($dbConnect, $sql);
			
				// 実行結果が失敗の場合
				if ($isSuccess == false) {
					return false;
				}
			}
		}

		// T_Order_Detailsを変更する
		$sql  = "";
		$sql .= " UPDATE";
		$sql .= 	" T_Order_Details";
		$sql .= " SET";
		$sql .= 	" Status = " . db_Escape($status) . ",";
		$sql .= 	" UpdDay = GETDATE(),";
		$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= " WHERE";
		$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
		$sql .= " AND";
		$sql .= 	" Del = " . DELETE_OFF;
	
		$isSuccess = db_Execute($dbConnect, $sql);
	
		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}


	}

	else {
	
		// T_Staff_Detailsを変更する
		$sql  = "";
		$sql .= " UPDATE";
		$sql .= 	" T_Staff_Details";
		$sql .= " SET";
		$sql .= 	" Status = " . db_Escape($status) . ",";
		$sql .= 	" UpdDay = GETDATE(),";
		$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= " WHERE";
	
		if ($status == STATUS_APPLI_ADMIT || $status == STATUS_APPLI_DENY) {
			$sql .= 	" OrderDetID IN (";
		}
		else {
			$sql .= 	" ReturnDetID IN (";
		}
	
		$sql .= 		" SELECT";
		$sql .= 			" OrderDetID";
		$sql .= 		" FROM";
		$sql .= 			" T_Order_Details";
		$sql .= 		" WHERE";
		$sql .= 			" OrderID = '" . db_Escape($orderId) . "'";
		$sql .= 		" AND";
		$sql .= 			" Del = " . DELETE_OFF;
		$sql .= 	" )";
		$sql .= " AND";
		$sql .= 	" Del = " . DELETE_OFF;
	
		$isSuccess = db_Execute($dbConnect, $sql);
	
		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}
	
		// T_Order_Detailsを変更する
		$sql  = "";
		$sql .= " UPDATE";
		$sql .= 	" T_Order_Details";
		$sql .= " SET";
		$sql .= 	" Status = " . db_Escape($status) . ",";
		$sql .= 	" UpdDay = GETDATE(),";
		$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= " WHERE";
		$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
		$sql .= " AND";
		$sql .= 	" Del = " . DELETE_OFF;
	
		$isSuccess = db_Execute($dbConnect, $sql);
	
		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}

	}

	return true;

}

/*
 * Statusを取得する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$orderId        => OrderID
 * 戻り値：状態
 *
 * create 2007/04/24 H.Osugi
 *
 */
function getOrderStatus($dbConnect, $orderId) {

	// Statusを取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" Status";
	$sql .= " FROM";
	$sql .= 	" T_Order";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;

	$orderDatas = db_Read($dbConnect, $sql);

	return $orderDatas[0]['Status'];

}

?>
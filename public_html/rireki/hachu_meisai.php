<?php
/*
 * 返却明細画面
 * henpin_meisai.src.php
 *
 * create 2007/03/26 H.Osugi
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


/* ../../include/castOrderDetId.php start */

/*
 * 返却で選択された商品のorderDetIDを成型
 * castOrderDetId.php
 *
 * create 2007/03/22 H.Osugi
 *
 */

/*
 * 選択された商品のorderDetIDを成型する
 * 引数  ：$post        => POST値
 * 戻り値：$orderDetIds => 選択された商品のorderDetID(array)
 *
 * create 2007/03/22 H.Osugi
 *
 */
function castOrderDetId($post) {

	// 初期化
	$orderDetIds = array();

	// orderDetIDが無ければそのまま終了
	if (count($post['orderDetIds']) <= 0) {
		return $orderDetIds;
	}

	// 返却・紛失が選択されたユニフォームのorderDetIDのみを取得する
	$countOrderDetId = count($post['orderDetIds']);
	$j = 0;
	for ($i=0; $i<$countOrderDetId; $i++) {
		if(isset($post['orderDetIds'][$i])) {
			if (isset($post['returnChk'][$post['orderDetIds'][$i]]) || isset($post['lostChk'][$post['orderDetIds'][$i]])) {
				$orderDetIds[$j] = trim($post['orderDetIds'][$i]);
				$j++;
			}
		}
	}

	return $orderDetIds;

}


/* ../../include/castOrderDetId.php end */


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
$yoteiDay   = '';					// 出荷指定日
$memo       = '';					// メモ
$rentalStartDay = '';				// レンタル開始日

$haveTok  = false;					// 特寸から遷移してきたか判定フラグ

$high     = '';						// 身長
$weight   = '';						// 体重
$bust     = '';						// バスト
$waist    = '';						// ウエスト
$hips     = '';						// ヒップ
$shoulder = '';						// 肩幅
$sleeve   = '';						// 袖丈
$length   = '';						// スカート丈
$kitake   = '';                     // 着丈
$yukitake = '';                     // 裄丈
$inseam   = '';                     // 股下
$tokMemo  = '';						// 特寸備考

$isEmptyMemo = true;				// メモが空かどうかを判定するフラグ
$isEmptyRentalStartDay = true;		// レンタル開始日が空かどうかを判定するフラグ
$isSizeNoDisp = false;				// サイズ非表示フラグ

$isAppliMode = false;
// 変数の初期化 ここまで ******************************************************

// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST);

// 表示する値の成型 ここから +++++++++++++++++++++++++++++++++++++++++++++++++++

// OrderID
$orderId = trim($post['orderId']);

// 表示する商品詳細情報取得
$orders = getStaffOrderDetails($dbConnect, $orderId, $DISPLAY_STATUS);

$GoukeiKingaku = 0;
for ($i=0; $i<sizeof($orders); $i++) {
	$GoukeiKingaku += $orders[$i]['Price'];
	$orders[$i]['Price'] = number_format($orders[$i]['Price']);
}
$GoukeiKingaku = number_format($GoukeiKingaku);

// 表示する情報が取得できなければエラー
if (count($orders) <= 0) {

	$hiddens['errorName'] = 'hachuMeisai';
	$hiddens['menuName']  = 'isMenuHistory';
	$hiddens['returnUrl'] = 'rireki/rireki.php';
	$hiddens['errorId'][] = '901';
	$errorUrl             = HOME_URL . 'error.php';

	redirectPost($errorUrl, $hiddens);

}

// 一般ユーザーの場合はサイズを＊表示
//if ($_SESSION['USERLVL'] == USER_AUTH_LEVEL_GENERAL) {
//	$orderCount = count($orders);
//	for ($i=0; $i<$orderCount; $i++) {
//		$orders[$i]['Size'] = '****';
//	}
//	$isSizeNoDisp = true;				// サイズ非表示フラグON
//}

// 申請情報の取得
$orderData = getOrderData($dbConnect, $orderId);

// 申請情報をHTMLエンティティ
$orderData = castHtmlEntity($orderData); 

// 申請番号
$requestNo = $orderData['AppliNo'];

// 申請日
$isEmptyRequestDay = false;
$requestDay = '';
if ($orderData['AppliDay'] != '') {
	$requestDay = strtotime($orderData['AppliDay']);
}
else {
	$isEmptyRequestDay = true;
}

// 新品/中古区分
$new_Item = false;
$newOldKbn = trim($orderData['NewOldKbn']);
if($newOldKbn == 1){
	$new_Item = true;
}

// 店舗名
$compName = $orderData['AppliCompName'];

// 店舗コード
$compCd = $orderData['AppliCompCd'];

if (isset($orderData['AppliMode']) && $orderData['AppliMode'] == '1') {
	$isAppliMode = true;
}

// スタッフコード
$staffCode = $orderData['StaffCode'];

// 着用者氏名
$personName = $orderData['PersonName'];

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

// 出荷予定日
$yoteiDay = $orderData['YoteiDay'];

// レンタル開始日
$rentalStartDay = $orderData['RentalStartDay'];

if ($rentalStartDay != '' || $rentalStartDay === 0) {
	$isEmptyRentalStartDay = false;
}

// 貸与パターン名
$appliPattern = getStaffPattern($dbConnect, $orderData['AppliPattern']);

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

	// 交換理由（修理交換）
	case APPLI_REASON_EXCHANGE_REPAIR:
		$selectedReason7 = true;
		break;

	default:
		break;

}

// 特寸情報があった場合
if ($orderData['Tok'] == 1) {

	// 特寸情報の取得
	$tokData = getTokData($dbConnect, $orderId);

	// 特寸情報をHTMLエンティティ
	$tokData = castHtmlEntity($tokData); 

	// 特寸情報が存在するかの判定フラグ
	$haveTok  = true;

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
    $yukitake   = $tokData['Yukitake'];

    // 股下
    $inseam   = $tokData['Inseam'];

	// 特寸備考
	$tokMemo  = $orderData['TokNote'];

}

// 表示する値の成型 ここまで ++++++++++++++++++++++++++++++++++++++++++++++++++

/*
 * 表示する商品一覧情報を取得する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$orderId        => OrderID
 *       ：$DISPLAY_STATUS => 状態
 * 戻り値：$result         => 表示する商品一覧情報
 *
 * create 2007/03/26 H.Osugi
 *
 */
function getStaffOrderDetails($dbConnect, $orderId, $DISPLAY_STATUS) {

	// OrderIDが空の場合
	if ($orderId == '') {
		$result = array();
	 	return $result;
	}

	// 表示する商品の一覧を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" mi.ItemID,";
	$sql .= 	" tod.ItemName,";
	$sql .= 	" mi.Price,";
	$sql .= 	" tod.BarCd,";
	$sql .= 	" tod.IcTagCd,";
	$sql .= 	" tod.Size,";
	$sql .= 	" tod.Status,";
	$sql .= 	" tod.TakuhaiNo";
	$sql .= " FROM";
	$sql .= 	" T_Order tor";
	$sql .= " INNER JOIN";
	$sql .= 	" T_Order_Details tod";
	$sql .= " ON";
	$sql .= 	" tor.OrderID = tod.OrderID";
	$sql .= " AND";
	$sql .= 	" tod.Del = " . DELETE_OFF;
	$sql .= " INNER JOIN";
	$sql .= 	" M_Item mi";
	$sql .= " ON";
	$sql .= 	" tod.ItemID = mi.ItemID";
	$sql .= " AND";
	$sql .= 	" mi.Del = " . DELETE_OFF;
	$sql .= " WHERE";
	$sql .= 	" tor.OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" tor.Del = " . DELETE_OFF;
	$sql .= " ORDER BY";
	$sql .= 	" tod.AppliLNo ASC,";
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
		$result[$i]['ItemName']   = castHtmlEntity($result[$i]['ItemName']);
		$result[$i]['BarCd']      = castHtmlEntity($result[$i]['BarCd']);
		$result[$i]['IcTagCd']    = castHtmlEntity($result[$i]['IcTagCd']);
		$result[$i]['Size']       = castHtmlEntity($result[$i]['Size']);
		$result[$i]['Price']      = castHtmlEntity($result[$i]['Price']);

		$result[$i]['num'] = ($i + 1);

		// バーコードが空かどうか判定
		$result[$i]['isEmptyBarCd'] = false;
		if ($result[$i]['BarCd'] == '') {
			$result[$i]['isEmptyBarCd'] = true;
		}

		// ICタグコードが空かどうか判定
		$result[$i]['isEmptyIcTagCd'] = false;
		if ($result[$i]['IcTagCd'] == '') {
			$result[$i]['isEmptyIcTagCd'] = true;
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
		$result[$i]['TakuhaiNo']      = castHtmlEntity($result[$i]['TakuhaiNo']);

	}

	return  $result;

}


/*
 * 申請情報を取得する
 * 引数  ：$dbConnect    => コネクションハンドラ
 *       ：$orderId      => OrderID
 * 戻り値：$result       => 申請情報
 *
 * create 2007/03/26 H.Osugi
 *
 */
function getOrderData($dbConnect, $orderId) {

	// 表示する申請情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" AppliNo,";
	$sql .= 	" AppliDay,";
	$sql .= 	" AppliCompCd,";
	$sql .= 	" AppliCompName,";
	$sql .= 	" AppliMode,";
	$sql .= 	" StaffCode,";
    $sql .=     " PersonName,";
	$sql .= 	" AppliReason,";
	$sql .= 	" AppliPattern,";
	$sql .= 	" Zip,";
	$sql .= 	" Adrr,";
	$sql .= 	" Tel,";
	$sql .= 	" ShipName,";
	$sql .= 	" TantoName,";
	$sql .= 	" Note,";
	$sql .= 	" TantoName,";
	$sql .= 	" CONVERT(varchar,RentalStartDay,111) AS RentalStartDay,";
	$sql .= 	" CONVERT(varchar,YoteiDay,111) AS YoteiDay,";
	$sql .= 	" NewOldKbn,";
	$sql .= 	" Tok,";
	$sql .= 	" TokNote";
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
 * 申請情報を取得する
 * 引数  ：$dbConnect    => コネクションハンドラ
 *       ：$orderId      => OrderID
 * 戻り値：$result       => 申請情報
 *
 * create 2007/03/26 H.Osugi
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
        

        <div id="contents">
          <h1>ユニフォーム申請明細</h1>
          <table border="0" width="700" cellpadding="0" cellspacing="0" class="tb_1">
            <tr height="30">
              <td width="100" class="line"><span class="fbold">申請番号</span></td>
              <td class="line"><?php isset($requestNo) ? print($requestNo) : print('&#123;requestNo&#125;'); ?></td>
              <td width="60" class="line"><span class="fbold">申請日</span></td>
              <td class="line">
<?php if(!$isEmptyRequestDay) { ?>
                <?php isset($requestDay) ? print(date("y/m/d", $requestDay)) : print('&#123;dateFormat(requestDay, "y/m/d")&#125;'); ?>
<?php } ?>
<?php if($isEmptyRequestDay) { ?>
                
                &nbsp;
                
<?php } ?>
              </td>
            </tr>
            <tr height="30">
              <td class="line"><span class="fbold">施設名</span></td>
              <td colspan="3" class="line"><?php isset($compCd) ? print($compCd) : print('&#123;compCd&#125;'); ?>：<?php isset($compName) ? print($compName) : print('&#123;compName&#125;'); ?></td>
            </tr>
            <tr height="30">
              <td class="line"><span class="fbold">職員コード</span></td>
              <td  class="line"><?php isset($staffCode) ? print($staffCode) : print('&#123;staffCode&#125;'); ?></td>
              <td width="80" class="line">
                <span class="fbold">職員名</span>
              </td>
              <td width="400" class="line"><?php isset($personName) ? print($personName) : print('&#123;personName&#125;'); ?></td>
            </tr>
<?php if($isAppliMode) { ?>
            <tr height="30">
              <td class="line"><span class="fbold">貸与パターン</span></td>
              <td colspan="3" class="line"><?php isset($appliPattern) ? print($appliPattern) : print('&#123;appliPattern&#125;'); ?></td>
            </tr>
<?php } ?>
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
            <tr height="30">
              <td class="line"><span class="fbold">出荷指定日</span></td>
              <td colspan="3" class="line"><?php isset($yoteiDay) ? print($yoteiDay) : print('&#123;yoteiDay&#125;'); ?></td>
            </tr>
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
          <h3>◆発注申請明細</h3>
<?php if(!$isLevelAdmin) { ?>
          <table width="620" border="0" class="tb_1" cellpadding="0" cellspacing="3">
            <tr>
              <th align="center" width="40">No</th>
              <th align="center" width="300">アイテム名</th>
              <th align="center" width="80">サイズ</th>
              <th align="center" width="100">単品番号</th>
              <th align="center" width="100">送り状No.</th>
              <th align="center" width="100">状態</th>
            </tr>
<?php } ?>
<?php if($isLevelAdmin) { ?>
          <table width="720" border="0" class="tb_1" cellpadding="0" cellspacing="3">
            <tr>
              <th align="center" width="40">No</th>
              <th align="center" width="300">アイテム名</th>
              <th align="center" width="80">サイズ</th>
              <th align="center" width="100">単品番号</th>
              <th align="center" width="100">送り状No.</th>
              <th align="center" width="100">状態</th>
            </tr>
<?php } ?>
<?php for ($i1_orders=0; $i1_orders<count($orders); $i1_orders++) { ?>
            <tr height="20">
              <td class="line2" align="center"><?php isset($orders[$i1_orders]['num']) ? print($orders[$i1_orders]['num']) : print('&#123;orders.num&#125;'); ?></td>
              <td class="line2"><?php isset($orders[$i1_orders]['ItemName']) ? print($orders[$i1_orders]['ItemName']) : print('&#123;orders.ItemName&#125;'); ?></td>
              <td class="line2" align="center"><?php isset($orders[$i1_orders]['Size']) ? print($orders[$i1_orders]['Size']) : print('&#123;orders.Size&#125;'); ?></td>
              <td class="line2" align="center">
<?php if($orders[$i1_orders]['isEmptyBarCd']) { ?>
                
                &nbsp;
                
<?php } ?>
<?php if(!$orders[$i1_orders]['isEmptyBarCd']) { ?>
                <?php isset($orders[$i1_orders]['BarCd']) ? print($orders[$i1_orders]['BarCd']) : print('&#123;orders.BarCd&#125;'); ?>
<?php } ?>
              </td>
              <td class="line2" align="center"><?php isset($orders[$i1_orders]['TakuhaiNo']) ? print($orders[$i1_orders]['TakuhaiNo']) : print('&#123;orders.TakuhaiNo&#125;'); ?></td>
              <td class="line2" align="center">
<?php if($orders[$i1_orders]['statusIsBlue']) { ?>
                
                <span style="color:blue"><?php isset($orders[$i1_orders]['status']) ? print($orders[$i1_orders]['status']) : print('&#123;orders.status&#125;'); ?></span>
                
<?php } ?>
<?php if($orders[$i1_orders]['statusIsRed']) { ?>
                
                <span style="color:red"><?php isset($orders[$i1_orders]['status']) ? print($orders[$i1_orders]['status']) : print('&#123;orders.status&#125;'); ?></span>
                
<?php } ?>
<?php if($orders[$i1_orders]['statusIsTeal']) { ?>
                
                <span style="color:Teal"><?php isset($orders[$i1_orders]['status']) ? print($orders[$i1_orders]['status']) : print('&#123;orders.status&#125;'); ?></span>
                
<?php } ?>
<?php if($orders[$i1_orders]['statusIsGreen']) { ?>
                
                <span style="color:green"><?php isset($orders[$i1_orders]['status']) ? print($orders[$i1_orders]['status']) : print('&#123;orders.status&#125;'); ?></span>
                
<?php } ?>
<?php if($orders[$i1_orders]['statusIsGray']) { ?>
                
                <span style="color:gray"><?php isset($orders[$i1_orders]['status']) ? print($orders[$i1_orders]['status']) : print('&#123;orders.status&#125;'); ?></span>
                
<?php } ?>
<?php if($orders[$i1_orders]['statusIsPink']) { ?>
                
                <span style="color:fuchsia"><?php isset($orders[$i1_orders]['status']) ? print($orders[$i1_orders]['status']) : print('&#123;orders.status&#125;'); ?></span>
                
<?php } ?>
<?php if($orders[$i1_orders]['statusIsBlack']) { ?>
                <?php isset($orders[$i1_orders]['status']) ? print($orders[$i1_orders]['status']) : print('&#123;orders.status&#125;'); ?>
<?php } ?>
              </td>
            </tr>
<?php } ?>

          </table>
<?php if($haveTok) { ?>
          
          <br>
          <table width="570" border="0" class="tb_2" cellpadding="0" cellspacing="1">
            <tr>
              <th align="center" width="40" rowspan="5">特注<br>入力</th>
              <th align="center" width="65">身長</th>
              <th align="center" width="65">体重</th>
              <th align="center" width="65">バスト</th>
              <th align="center" width="65">ウエスト</th>
              <th align="center" width="65">ヒップ</th>
              <th align="center" width="65">肩幅</th>
              <th align="center" width="65">袖丈</th>
              <th align="center" width="65">着丈</th>
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
              <th align="center" width="65">裄丈</th>
              <th align="center" width="65">股下</th>
              <th align="center" width="65">首周り</th>
              <th align="center" width="65" colspan="5">&nbsp;</th>
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
          <br><br>
        </div>
        <br><br><br>
        

      </div>
    </div>
  </body>
</html>

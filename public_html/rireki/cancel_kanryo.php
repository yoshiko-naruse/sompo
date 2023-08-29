<?php
/*
 * キャンセル完了画面
 * cancel_kanryo.src.php
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


/* ../../include/createHachuMail.php start */

/*
 * 発注申請メール生成モジュール
 * createHachuMail.php
 *
 * create 2007/03/30 H.Osugi
 *
 */

/*
 * 発注申請メールの件名と本文を作成する
 *
 * 引数  ：$dbConnect  => コネクションハンドラ
 *       ：$orderId    => OrderID
 *       ：$tolFlg     => 特寸フラグ 1なら特寸
 *       ：$filePath   => メールテンプレートのファイルパス
 *       ：$subject    => 件名
 *       ：$message    => 本文
 *
 * 戻り値：true：メールテンプレート取得成功/false：取得失敗
 *
 * create 2007/03/30 H.Osugi
 *
 */
function hachuShinseiMail($dbConnect, $orderId, $tokFlg, $filePath, &$subject, &$message) {

	// 初期化
	$subject = '';
	$message = '';

	// T_Orderの情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" AppliNo,";
	$sql .= 	" AppliDay,";
	$sql .= 	" AppliCompCd,";
	$sql .= 	" AppliCompName,";
	$sql .= 	" StaffCode,";
    $sql .=     " PersonName,";
	$sql .= 	" Note,";
	$sql .= 	" TokNote";
	$sql .= " FROM";
	$sql .= 	" T_Order";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;
	
	$orderData = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($orderData) <= 0) {
	 	return false;
	}

	// 件名
	$subject = MAIL_SUBJECT_HEADER . "(" . $orderData[0]['AppliNo'] . ")発注申請";
	if ($tokFlg == 1) {
		$subject .= " （特注）";
	}

	// T_Order_Detailsの情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" ItemName,";
	$sql .= 	" Size";
	$sql .= " FROM";
	$sql .= 	" T_Order_Details";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;
	$sql .= " ORDER BY";
	$sql .= 	" OrderDetID ASC";
	
	$orderDetailData = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($orderDetailData) <= 0) {
	 	return false;
	}

	$items = '';
	$countOrderDetail = count($orderDetailData);
	for ($i=0; $i<$countOrderDetail; $i++) {
		$items .= "　○発注：　". $orderDetailData[$i]['ItemName'] . "（" . $orderDetailData[$i]['Size'] . "）";
		if ($i < $countOrderDetail - 1) {
			$items .= "\n";
		}
	}

	$tokusun = '';
	if ($tokFlg == 1) {

		// T_Tokの情報を取得する
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
		
		$tokData = db_Read($dbConnect, $sql);
	
		// 検索結果が0件の場合
		if (count($tokData) <= 0) {
		 	return false;
		}

		// 特寸部分のテンプレート
		$tokusun = file_get_contents($filePath . 'tokusunTemplate.txt');

		// 特寸部分のテンプレートの置換
		$tokusun = mb_ereg_replace('###HEIGHT###', $tokData[0]['Height'], $tokusun);
		$tokusun = mb_ereg_replace('###WEIGHT###', $tokData[0]['Weight'], $tokusun);
		$tokusun = mb_ereg_replace('###BUST###', $tokData[0]['Bust'], $tokusun);
		$tokusun = mb_ereg_replace('###WAIST###', $tokData[0]['Waist'], $tokusun);
		$tokusun = mb_ereg_replace('###HIPS###', $tokData[0]['Hips'], $tokusun);
		$tokusun = mb_ereg_replace('###SHOULDER###', $tokData[0]['Shoulder'], $tokusun);
		$tokusun = mb_ereg_replace('###SLEEVE###', $tokData[0]['Sleeve'], $tokusun);
		$tokusun = mb_ereg_replace('###SETALE###', $tokData[0]['Length'], $tokusun);
        $tokusun = mb_ereg_replace('###KITAKE###', $tokData[0]['Kitake'], $tokusun);
        $tokusun = mb_ereg_replace('###YUKITAKE###', $tokData[0]['Yukitake'], $tokusun);
        $tokusun = mb_ereg_replace('###INSEAM###', $tokData[0]['Inseam'], $tokusun);
		$tokusun = mb_ereg_replace('###TOKNOTE###', $orderData[0]['TokNote'], $tokusun);

	}

	// 現在日時
	$date = date("Y年n月j日 H時i分");

	// 申請日
	$appliDay = date("Y/m/d", strtotime($orderData[0]['AppliDay']));

	// 本文
	$message = file_get_contents($filePath . 'hachuShinsei.txt');

	// 本文の置換
	$message = mb_ereg_replace('###HEAD###', MAIL_BODY_HEADER, $message);
	$message = mb_ereg_replace('###DATE###', $date, $message);

	if ($tokFlg == 1) {
		$message = mb_ereg_replace('###TOK###', "(特注)", $message);
	}
	else {
		$message = mb_ereg_replace('###TOK###', "", $message);
	}

	$message = mb_ereg_replace('###APPLIDAY###', $appliDay, $message);
	$message = mb_ereg_replace('###APPLINO###', $orderData[0]['AppliNo'], $message);
	$message = mb_ereg_replace('###COMPCODE###', $orderData[0]['AppliCompCd'], $message);
	$message = mb_ereg_replace('###COMPNAME###', $orderData[0]['AppliCompName'], $message);
	$message = mb_ereg_replace('###STAFFCODE###', $orderData[0]['StaffCode'], $message);
    $message = mb_ereg_replace('###PERSONNAME###', $orderData[0]['PersonName'], $message);
	$message = mb_ereg_replace('###NOTE###', $orderData[0]['Note'], $message);
	$message = mb_ereg_replace('###ITEM###', $items, $message);
	$message = mb_ereg_replace('###TOKUSUN###', $tokusun, $message);

	return true;

}

/*
 * 発注訂正メールの件名と本文を作成する
 *
 * 引数  ：$dbConnect  => コネクションハンドラ
 *       ：$orderId    => OrderID
 *       ：$tolFlg     => 特寸フラグ 1なら特寸
 *       ：$filePath   => メールテンプレートのファイルパス
 *       ：$subject    => 件名
 *       ：$message    => 本文
 *
 * 戻り値：true：メールテンプレート取得成功/false：取得失敗
 *
 * create 2007/04/02 H.Osugi
 *
 */
function hachuTeiseiMail($dbConnect, $orderId, $tokFlg, $motoTokFlg, $filePath, &$subject, &$message) {

	// 初期化
	$subject = '';
	$message = '';

	// T_Orderの情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" AppliNo,";
	$sql .= 	" AppliDay,";
	$sql .= 	" AppliCompCd,";
	$sql .= 	" AppliCompName,";
	$sql .= 	" StaffCode,";
    $sql .=     " PersonName,";
	$sql .= 	" Note,";
	$sql .= 	" TokNote";
	$sql .= " FROM";
	$sql .= 	" T_Order";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;
	
	$orderData = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($orderData) <= 0) {
	 	return false;
	}

	// 件名
	$subject = MAIL_SUBJECT_HEADER . "(" . $orderData[0]['AppliNo'] . ")発注訂正";
	if ($tokFlg == 1 || $motoTokFlg == 1) {
		$subject .= " （特注）";
	}

	// T_Order_Detailsの情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" ItemName,";
	$sql .= 	" Size";
	$sql .= " FROM";
	$sql .= 	" T_Order_Details";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;
	$sql .= " ORDER BY";
	$sql .= 	" OrderDetID ASC";
	
	$orderDetailData = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($orderDetailData) <= 0) {
	 	return false;
	}

	$items = '';
	$countOrderDetail = count($orderDetailData);
	for ($i=0; $i<$countOrderDetail; $i++) {
		$items .= "  ○発注：  ". $orderDetailData[$i]['ItemName'] . "（" . $orderDetailData[$i]['Size'] . "）";
		if ($i < $countOrderDetail - 1) {
			$items .= "\n";
		}
	}

	$tokusun = '';
	if ($tokFlg == 1) {

		// T_Tokの情報を取得する
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
		
		$tokData = db_Read($dbConnect, $sql);
	
		// 検索結果が0件の場合
		if (count($tokData) <= 0) {
		 	return false;
		}

		// 特寸部分のテンプレート
		$tokusun = file_get_contents($filePath . 'tokusunTemplate.txt');

		// 特寸部分のテンプレートの置換
		$tokusun = mb_ereg_replace('###HEIGHT###', $tokData[0]['Height'], $tokusun);
		$tokusun = mb_ereg_replace('###WEIGHT###', $tokData[0]['Weight'], $tokusun);
		$tokusun = mb_ereg_replace('###BUST###', $tokData[0]['Bust'], $tokusun);
		$tokusun = mb_ereg_replace('###WAIST###', $tokData[0]['Waist'], $tokusun);
		$tokusun = mb_ereg_replace('###HIPS###', $tokData[0]['Hips'], $tokusun);
		$tokusun = mb_ereg_replace('###SHOULDER###', $tokData[0]['Shoulder'], $tokusun);
		$tokusun = mb_ereg_replace('###SLEEVE###', $tokData[0]['Sleeve'], $tokusun);
		$tokusun = mb_ereg_replace('###SETALE###', $tokData[0]['Length'], $tokusun);
        $tokusun = mb_ereg_replace('###KITAKE###', $tokData[0]['Kitake'], $tokusun);
        $tokusun = mb_ereg_replace('###YUKITAKE###', $tokData[0]['Yukitake'], $tokusun);
        $tokusun = mb_ereg_replace('###INSEAM###', $tokData[0]['Inseam'], $tokusun);
		$tokusun = mb_ereg_replace('###TOKNOTE###', $orderData[0]['TokNote'], $tokusun);

	}

	// 現在日時
	$date = date("Y年n月j日 H時i分");

	// 申請日
	$appliDay = date("Y/m/d", strtotime($orderData[0]['AppliDay']));

	// 本文
	$message = file_get_contents($filePath . 'hachuTeisei.txt');

	// 本文の置換
	$message = mb_ereg_replace('###HEAD###', MAIL_BODY_HEADER, $message);
	$message = mb_ereg_replace('###DATE###', $date, $message);

	if ($tokFlg == 1 || $motoTokFlg == 1) {
		$message = mb_ereg_replace('###TOK###', "(特注)", $message);
	}
	else {
		$message = mb_ereg_replace('###TOK###', "", $message);
	}

	$message = mb_ereg_replace('###APPLIDAY###', $appliDay, $message);
	$message = mb_ereg_replace('###APPLINO###', $orderData[0]['AppliNo'], $message);
	$message = mb_ereg_replace('###COMPCODE###', $orderData[0]['AppliCompCd'], $message);
	$message = mb_ereg_replace('###COMPNAME###', $orderData[0]['AppliCompName'], $message);
	$message = mb_ereg_replace('###STAFFCODE###', $orderData[0]['StaffCode'], $message);
    $message = mb_ereg_replace('###PERSONNAME###', $orderData[0]['PersonName'], $message);
	$message = mb_ereg_replace('###NOTE###', $orderData[0]['Note'], $message);
	$message = mb_ereg_replace('###ITEM###', $items, $message);
	$message = mb_ereg_replace('###TOKUSUN###', $tokusun, $message);

	return true;

}

/*
 * 発注キャンセルメールの件名と本文を作成する
 *
 * 引数  ：$dbConnect  => コネクションハンドラ
 *       ：$orderId    => OrderID
 *       ：$filePath   => メールテンプレートのファイルパス
 *       ：$subject    => 件名
 *       ：$message    => 本文
 *       ：$tolFlg     => 特寸フラグ 1なら特寸
 *
 * 戻り値：true：メールテンプレート取得成功/false：取得失敗
 *
 * create 2007/04/02 H.Osugi
 *
 */
function hachuCancelMail($dbConnect, $orderId, $filePath, &$subject, &$message, &$tokFlg) {

	// 初期化
	$subject = '';
	$message = '';

	// T_Orderの情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" AppliNo,";
	$sql .= 	" AppliCompCd,";
	$sql .= 	" AppliCompName,";
	$sql .= 	" StaffCode,";
    $sql .=     " PersonName,";
	$sql .= 	" Note,";
	$sql .= 	" Tok";
	$sql .= " FROM";
	$sql .= 	" T_Order";
	$sql .= " WHERE";
	$sql .= 	" OrderId = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_ON;
	
	$orderData = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($orderData) <= 0) {
	 	return false;
	}

	$tokFlg = $orderData[0]['Tok'];

	// 件名
	$subject = MAIL_SUBJECT_HEADER . "(" . $orderData[0]['AppliNo'] . ")発注キャンセル";
	if ($tokFlg == 1) {
		$subject .= " （特注）";
	}

	// 現在日時
	$date = date("Y年n月j日 H時i分");

	// 申請日
	$appliDay = date("Y/m/d", strtotime($orderData[0]['AppliDay']));

	// 本文
	$message = file_get_contents($filePath . 'hachuCancel.txt');

	// 本文の置換
	$message = mb_ereg_replace('###HEAD###', MAIL_BODY_HEADER, $message);
	$message = mb_ereg_replace('###DATE###', $date, $message);

	if ($tokFlg == 1) {
		$message = mb_ereg_replace('###TOK###', "(特注)", $message);
	}
	else {
		$message = mb_ereg_replace('###TOK###', "", $message);
	}

	$message = mb_ereg_replace('###APPLIDAY###', $appliDay, $message);
	$message = mb_ereg_replace('###APPLINO###', $orderData[0]['AppliNo'], $message);
	$message = mb_ereg_replace('###COMPCODE###', $orderData[0]['AppliCompCd'], $message);
	$message = mb_ereg_replace('###COMPNAME###', $orderData[0]['AppliCompName'], $message);
	$message = mb_ereg_replace('###STAFFCODE###', $orderData[0]['StaffCode'], $message);
    $message = mb_ereg_replace('###PERSONNAME###', $orderData[0]['PersonName'], $message);
	$message = mb_ereg_replace('###NOTE###', $orderData[0]['Note'], $message);

	return true;

}


/* ../../include/createHachuMail.php end */


/* ../../include/createKoukanMail.php start */

/*
 * 交換申請メール生成モジュール
 * createKoukanMail.php
 *
 * create 2007/04/25 H.Osugi
 *
 */

/*
 * 交換申請メールの件名と本文を作成する
 *
 * 引数  ：$dbConnect  => コネクションハンドラ
 *       ：$orderId    => OrderID
 *       ：$tolFlg     => 特寸フラグ 1なら特寸
 *       ：$filePath   => メールテンプレートのファイルパス
 *       ：$subject    => 件名
 *       ：$message    => 本文
 *
 * 戻り値：true：メールテンプレート取得成功/false：取得失敗
 *
 * create 2007/04/25 H.Osugi
 *
 */
function koukanShinseiMail($dbConnect, $orderId, $returnOrderId, $tokFlg, $filePath, &$subject, &$message) {

	// 初期化
	$subject = '';
	$message = '';

	// T_Orderの情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" AppliNo,";
	$sql .= 	" AppliReason,";
	$sql .= 	" AppliDay,";
	$sql .= 	" AppliCompCd,";
	$sql .= 	" AppliCompName,";
	$sql .= 	" StaffCode,";
    $sql .=     " PersonName,";
	$sql .= 	" Note,";
	$sql .= 	" TokNote";
	$sql .= " FROM";
	$sql .= 	" T_Order";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;
	
	$orderData = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($orderData) <= 0) {
	 	return false;
	}

	// 件名
	$subject = MAIL_SUBJECT_HEADER . "(" . $orderData[0]['AppliNo'] . ")";
	switch ($orderData[0]['AppliReason']) {
		case APPLI_REASON_EXCHANGE_SIZE:
			$subject .= "サイズ交換申請";
			break;

		case APPLI_REASON_EXCHANGE_INFERIORITY:
			$subject .= "不良品交換申請";
			break;

		case APPLI_REASON_EXCHANGE_LOSS:
			$subject .= "紛失交換申請";
			break;

		case APPLI_REASON_EXCHANGE_BREAK:
			$subject .= "汚損・破損交換申請";
			break;

        case APPLI_REASON_EXCHANGE_CHANGEGRADE:
            $subject .= "役職変更交換申請";
            break;

        case APPLI_REASON_EXCHANGE_MATERNITY:
            $subject .= "マタニティ交換申請";
            break;

		case APPLI_REASON_EXCHANGE_REPAIR:
			$subject .= "修理交換申請";
			break;

		default:
			break;
	}
	if ($tokFlg == 1) {
		$subject .= " （特注）";
	}

	// T_Order_Detailsの情報を取得する（返却）
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" ItemName,";
	$sql .= 	" Size,";
	$sql .= 	" BarCd,";
	$sql .= 	" Status,";
	$sql .= 	" DamageCheck";
	$sql .= " FROM";
	$sql .= 	" T_Order_Details";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($returnOrderId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;
	$sql .= " ORDER BY";
	$sql .= 	" OrderDetID ASC";
	
	$returnDetailData = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($returnDetailData) <= 0) {
	 	return false;
	}

	$returns = '';
	$countReturnDetail = count($returnDetailData);
	for ($i=0; $i<$countReturnDetail; $i++) {

		switch ($returnDetailData[$i]['Status']) {
			case STATUS_NOT_RETURN:				// 未返却（承認待）
			case STATUS_NOT_RETURN_ADMIT:		// 未返却（承認済）
				$returns .= "　○返却：　". $returnDetailData[$i]['ItemName'] . "（" . $returnDetailData[$i]['Size'] . "）";
				break;

			case STATUS_LOSS:					// 紛失（承認待）
			case STATUS_LOSS_ADMIT:				// 紛失（承認済）
				$returns .= "　●紛失：　". $returnDetailData[$i]['ItemName'] . "（" . $returnDetailData[$i]['Size'] . "）";
				break;
			default:
				break;
		}

		if (isset($returnDetailData[$i]['BarCd']) && $returnDetailData[$i]['BarCd'] != '') {
			$returns .= " " . $returnDetailData[$i]['BarCd'];
		}

		// 汚損・破損の場合
		if (isset($returnDetailData[$i]['DamageCheck']) && $returnDetailData[$i]['DamageCheck'] == 1) {
			$returns .= " ▼汚損・破損";
		}

		if ($i < $countReturnDetail - 1) {
			$returns .= "\n";
		}

	}

	// T_Order_Detailsの情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" ItemName,";
	$sql .= 	" Size,";
	$sql .= 	" BarCd";
	$sql .= " FROM";
	$sql .= 	" T_Order_Details";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;
	$sql .= " ORDER BY";
	$sql .= 	" OrderDetID ASC";
	
	$orderDetailData = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($orderDetailData) <= 0) {
	 	return false;
	}

	$orders = '';
	$countOrderDetail = count($orderDetailData);
	for ($i=0; $i<$countOrderDetail; $i++) {

		$orders .= "　○発注：　". $orderDetailData[$i]['ItemName'] . "（" . $orderDetailData[$i]['Size'] . "）";

		if (isset($orderDetailData[$i]['BarCd']) && $orderDetailData[$i]['BarCd'] != '') {
			$orders .= " " . $orderDetailData[$i]['BarCd'];
		}

		if ($i < $countOrderDetail - 1) {
			$orders .= "\n";
		}
	}

	$tokusun = '';
	if ($tokFlg == 1) {

		// T_Tokの情報を取得する
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
		
		$tokData = db_Read($dbConnect, $sql);
	
		// 検索結果が0件の場合
		if (count($tokData) <= 0) {
		 	return false;
		}

		// 特寸部分のテンプレート
		$tokusun = file_get_contents($filePath . 'tokusunTemplate.txt');

		// 特寸部分のテンプレートの置換
		$tokusun = mb_ereg_replace('###HEIGHT###', $tokData[0]['Height'], $tokusun);
		$tokusun = mb_ereg_replace('###WEIGHT###', $tokData[0]['Weight'], $tokusun);
		$tokusun = mb_ereg_replace('###BUST###', $tokData[0]['Bust'], $tokusun);
		$tokusun = mb_ereg_replace('###WAIST###', $tokData[0]['Waist'], $tokusun);
		$tokusun = mb_ereg_replace('###HIPS###', $tokData[0]['Hips'], $tokusun);
		$tokusun = mb_ereg_replace('###SHOULDER###', $tokData[0]['Shoulder'], $tokusun);
		$tokusun = mb_ereg_replace('###SLEEVE###', $tokData[0]['Sleeve'], $tokusun);
		$tokusun = mb_ereg_replace('###SETALE###', $tokData[0]['Length'], $tokusun);
        $tokusun = mb_ereg_replace('###KITAKE###', $tokData[0]['Kitake'], $tokusun);
        $tokusun = mb_ereg_replace('###YUKITAKE###', $tokData[0]['Yukitake'], $tokusun);
        $tokusun = mb_ereg_replace('###INSEAM###', $tokData[0]['Inseam'], $tokusun);
		$tokusun = mb_ereg_replace('###TOKNOTE###', $orderData[0]['TokNote'], $tokusun);

	}

	// 現在日時
	$date = date("Y年n月j日 H時i分");

	// 申請日
	$appliDay = date("Y/m/d", strtotime($orderData[0]['AppliDay']));

	// 本文
	$message = file_get_contents($filePath . 'koukanShinsei.txt');

	// 本文の置換
	$message = mb_ereg_replace('###HEAD###', MAIL_BODY_HEADER, $message);
	$message = mb_ereg_replace('###DATE###', $date, $message);

	if ($tokFlg == 1) {
		$message = mb_ereg_replace('###TOK###', "(特注)", $message);
	}
	else {
		$message = mb_ereg_replace('###TOK###', "", $message);
	}

	// 理由
	switch ($orderData[0]['AppliReason']) {
		case APPLI_REASON_EXCHANGE_SIZE:
			$reason = "サイズ交換";
			break;

		case APPLI_REASON_EXCHANGE_INFERIORITY:
			$reason = "不良品交換";
			break;

		case APPLI_REASON_EXCHANGE_LOSS:
			$reason = "紛失交換";
			break;

		case APPLI_REASON_EXCHANGE_BREAK:
			$reason = "汚損・破損交換";
			break;

        case APPLI_REASON_EXCHANGE_CHANGEGRADE:
            $reason = "役職変更交換申請";
            break;

        case APPLI_REASON_EXCHANGE_MATERNITY:
            $reason = "マタニティ交換申請";
            break;

        case APPLI_REASON_EXCHANGE_REPAIR:
            $reason = "修理交換申請";
            break;

		default:
			break;
	}

    $message = mb_ereg_replace('###REASON###', $reason, $message);
	$message = mb_ereg_replace('###APPLIDAY###', $appliDay, $message);
	$message = mb_ereg_replace('###APPLINO###', $orderData[0]['AppliNo'], $message);
	$message = mb_ereg_replace('###COMPCODE###', $orderData[0]['AppliCompCd'], $message);
	$message = mb_ereg_replace('###COMPNAME###', $orderData[0]['AppliCompName'], $message);
	$message = mb_ereg_replace('###STAFFCODE###', $orderData[0]['StaffCode'], $message);
    $message = mb_ereg_replace('###PERSONNAME###', $orderData[0]['PersonName'], $message);
	$message = mb_ereg_replace('###NOTE###', $orderData[0]['Note'], $message);
	$message = mb_ereg_replace('###RETURNITEM###', $returns, $message);
	$message = mb_ereg_replace('###ORDERITEM###', $orders, $message);
	$message = mb_ereg_replace('###TOKUSUN###', $tokusun, $message);

	return true;

}

/*
 * 交換キャンセルメールの件名と本文を作成する
 *
 * 引数  ：$dbConnect  => コネクションハンドラ
 *       ：$orderId    => OrderID
 *       ：$filePath   => メールテンプレートのファイルパス
 *       ：$subject    => 件名
 *       ：$message    => 本文
 *       ：$tolFlg     => 特寸フラグ 1なら特寸
 *
 * 戻り値：true：メールテンプレート取得成功/false：取得失敗
 *
 * create 2007/04/25 H.Osugi
 *
 */
function koukanCancelMail($dbConnect, $orderId, $filePath, &$subject, &$message, &$tokFlg) {

	// 初期化
	$subject = '';
	$message = '';

	// T_Orderの情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" AppliNo,";
	$sql .= 	" AppliReason,";
	$sql .= 	" AppliCompCd,";
	$sql .= 	" AppliCompName,";
	$sql .= 	" StaffCode,";
    $sql .=     " PersonName,";
	$sql .= 	" Note,";
	$sql .= 	" Tok";
	$sql .= " FROM";
	$sql .= 	" T_Order";
	$sql .= " WHERE";
	$sql .= 	" OrderId = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_ON;
	
	$orderData = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($orderData) <= 0) {
	 	return false;
	}

	$tokFlg = $orderData[0]['Tok'];

	// 件名
	$subject = MAIL_SUBJECT_HEADER . "(" . $orderData[0]['AppliNo'] . ")";
	switch ($orderData[0]['AppliReason']) {
		case APPLI_REASON_EXCHANGE_SIZE:
			$subject .= "サイズ交換キャンセル";
			break;

		case APPLI_REASON_EXCHANGE_INFERIORITY:
			$subject .= "不良品交換キャンセル";
			break;

		case APPLI_REASON_EXCHANGE_LOSS:
			$subject .= "紛失交換キャンセル";
			break;

		case APPLI_REASON_EXCHANGE_BREAK:
			$subject .= "汚損・破損交換キャンセル";
			break;

        case APPLI_REASON_EXCHANGE_CHANGEGRADE:
            $subject .= "役職変更交換キャンセル";
            break;

        case APPLI_REASON_EXCHANGE_MATERNITY:
            $subject .= "マタニティ交換キャンセル";
            break;

		case APPLI_REASON_EXCHANGE_REPAIR:
			$subject .= "修理交換キャンセル";
			break;

		default:
			break;
	}
	if ($tokFlg == 1) {
		$subject .= " （特注）";
	}


	// 現在日時
	$date = date("Y年n月j日 H時i分");

	// 申請日
	$appliDay = date("Y/m/d", strtotime($orderData[0]['AppliDay']));

	// 本文
	$message = file_get_contents($filePath . 'koukanCancel.txt');

	// 本文の置換
	$message = mb_ereg_replace('###HEAD###', MAIL_BODY_HEADER, $message);
	$message = mb_ereg_replace('###DATE###', $date, $message);

	if ($tokFlg == 1) {
		$message = mb_ereg_replace('###TOK###', "(特注)", $message);
	}
	else {
		$message = mb_ereg_replace('###TOK###', "", $message);
	}

	// 理由
	switch ($orderData[0]['AppliReason']) {
		case APPLI_REASON_EXCHANGE_SIZE:
			$reason = "サイズ交換";
			break;

		case APPLI_REASON_EXCHANGE_INFERIORITY:
			$reason = "不良品交換";
			break;

		case APPLI_REASON_EXCHANGE_LOSS:
			$reason = "紛失交換";
			break;

		case APPLI_REASON_EXCHANGE_BREAK:
			$reason = "汚損・破損交換";
			break;

        case APPLI_REASON_EXCHANGE_CHANGEGRADE:
            $reason = "役職変更交換";
            break;

        case APPLI_REASON_EXCHANGE_MATERNITY:
            $reason = "マタニティ交換";
            break;

        case APPLI_REASON_EXCHANGE_REPAIR:
            $reason = "修理交換申請";
            break;

		default:
			break;
	}

    $message = mb_ereg_replace('###REASON###', $reason, $message);
	$message = mb_ereg_replace('###APPLIDAY###', $appliDay, $message);
	$message = mb_ereg_replace('###APPLINO###', $orderData[0]['AppliNo'], $message);
	$message = mb_ereg_replace('###COMPCODE###', $orderData[0]['AppliCompCd'], $message);
	$message = mb_ereg_replace('###COMPNAME###', $orderData[0]['AppliCompName'], $message);
	$message = mb_ereg_replace('###STAFFCODE###', $orderData[0]['StaffCode'], $message);
    $message = mb_ereg_replace('###PERSONNAME###', $orderData[0]['PersonName'], $message);
	$message = mb_ereg_replace('###NOTE###', $orderData[0]['Note'], $message);

	return true;

}


/* ../../include/createKoukanMail.php end */


/* ../../include/createHenpinMail.php start */

/*
 * 返却申請メール生成モジュール
 * createHenpinMail.php
 *
 * create 2007/04/25 H.Osugi
 *
 */

/*
 * 返却申請メールの件名と本文を作成する
 *
 * 引数  ：$dbConnect  => コネクションハンドラ
 *       ：$orderId    => OrderID
 *       ：$filePath   => メールテンプレートのファイルパス
 *       ：$subject    => 件名
 *       ：$message    => 本文
 *
 * 戻り値：true：メールテンプレート取得成功/false：取得失敗
 *
 * create 2007/04/25 H.Osugi
 *
 */
function henpinShinseiMail($dbConnect, $orderId, $filePath, &$subject, &$message) {

	// 初期化
	$subject = '';
	$message = '';

	// T_Orderの情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" AppliNo,";
	$sql .= 	" AppliReason,";
	$sql .= 	" AppliDay,";
	$sql .= 	" AppliCompCd,";
	$sql .= 	" AppliCompName,";
	$sql .= 	" StaffCode,";
    $sql .=     " PersonName,";
	$sql .= 	" Note,";
	$sql .= 	" TokNote";
	$sql .= " FROM";
	$sql .= 	" T_Order";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;
	
	$orderData = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($orderData) <= 0) {
	 	return false;
	}

	// 件名
	$subject = MAIL_SUBJECT_HEADER . "(" . $orderData[0]['AppliNo'] . ")";
	switch ($orderData[0]['AppliReason']) {
		case APPLI_REASON_RETURN_RETIRE:
			$subject .= "退職・異動返却申請";
			break;

		case APPLI_REASON_RETURN_OTHER:
			$subject .= "その他返却申請";
			break;

		default:
			break;
	}

	// T_Order_Detailsの情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" ItemName,";
	$sql .= 	" Size,";
	$sql .= 	" BarCd,";
	$sql .= 	" Status,";
	$sql .= 	" DamageCheck";
	$sql .= " FROM";
	$sql .= 	" T_Order_Details";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;
	$sql .= " ORDER BY";
	$sql .= 	" OrderDetID ASC";
	
	$orderDetailData = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($orderDetailData) <= 0) {
	 	return false;
	}

	$items = '';
	$countOrderDetail = count($orderDetailData);
	for ($i=0; $i<$countOrderDetail; $i++) {

		// 返却未申請ならば次へ
		if ($orderDetailData[$i]['Status'] == STATUS_RETURN_NOT_APPLY) {
			continue;
		}

		switch ($orderDetailData[$i]['Status']) {

			case STATUS_NOT_RETURN_ADMIT:		// 未返却（承認済）
				$items .= "　○返却：　". $orderDetailData[$i]['ItemName'] . "（" . $orderDetailData[$i]['Size'] . "）";
				break;

			case STATUS_LOSS_ADMIT:				// 紛失（承認済）
				$items .= "　●紛失：　". $orderDetailData[$i]['ItemName'] . "（" . $orderDetailData[$i]['Size'] . "）";
				break;
			default:
				break;
		}

		if (isset($orderDetailData[$i]['BarCd']) && $orderDetailData[$i]['BarCd'] != '') {
			$items .= " " . $orderDetailData[$i]['BarCd'];
		}

		// 汚損・破損の場合
		if (isset($orderDetailData[$i]['DamageCheck']) && $orderDetailData[$i]['DamageCheck'] == 1) {
			$items .= " ▼汚損・破損";
		}

		if ($i < $countOrderDetail - 1) {
			$items .= "\n";
		}
	}

	// 現在日時
	$date = date("Y年n月j日 H時i分");

	// 申請日
	$appliDay = date("Y/m/d", strtotime($orderData[0]['AppliDay']));

	// 本文
	$message = file_get_contents($filePath . 'henpinShinsei.txt');

	// 本文の置換
	$message = mb_ereg_replace('###HEAD###', MAIL_BODY_HEADER, $message);
	$message = mb_ereg_replace('###DATE###', $date, $message);

	// 理由
	switch ($orderData[0]['AppliReason']) {
		case APPLI_REASON_RETURN_RETIRE:
			$message = mb_ereg_replace('###REASON###', "退職・異動", $message);
			break;

		case APPLI_REASON_RETURN_OTHER:
			$message = mb_ereg_replace('###REASON###', "その他", $message);
			break;

		default:
			break;
	}

	$message = mb_ereg_replace('###APPLIDAY###', $appliDay, $message);
	$message = mb_ereg_replace('###APPLINO###', $orderData[0]['AppliNo'], $message);
	$message = mb_ereg_replace('###COMPCODE###', $orderData[0]['AppliCompCd'], $message);
	$message = mb_ereg_replace('###COMPNAME###', $orderData[0]['AppliCompName'], $message);
	$message = mb_ereg_replace('###STAFFCODE###', $orderData[0]['StaffCode'], $message);
    $message = mb_ereg_replace('###PERSONNAME###', $orderData[0]['PersonName'], $message);
	$message = mb_ereg_replace('###NOTE###', $orderData[0]['Note'], $message);
	$message = mb_ereg_replace('###ITEM###', $items, $message);

	return true;

}

/*
 * 返却キャンセルメールの件名と本文を作成する
 *
 * 引数  ：$dbConnect  => コネクションハンドラ
 *       ：$orderId    => OrderID
 *       ：$filePath   => メールテンプレートのファイルパス
 *       ：$subject    => 件名
 *       ：$message    => 本文
 *
 * 戻り値：true：メールテンプレート取得成功/false：取得失敗
 *
 * create 2007/04/25 H.Osugi
 *
 */
function henpinCancelMail($dbConnect, $orderId, $filePath, &$subject, &$message) {

	// 初期化
	$subject = '';
	$message = '';

	// T_Orderの情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" AppliNo,";
	$sql .= 	" AppliReason,";
	$sql .= 	" AppliCompCd,";
	$sql .= 	" AppliCompName,";
	$sql .= 	" StaffCode,";
    $sql .=     " PersonName,";
	$sql .= 	" Note";
	$sql .= " FROM";
	$sql .= 	" T_Order";
	$sql .= " WHERE";
	$sql .= 	" OrderId = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_ON;
	
	$orderData = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($orderData) <= 0) {
	 	return false;
	}

	// 件名
	$subject = MAIL_SUBJECT_HEADER . "(" . $orderData[0]['AppliNo'] . ")";
	switch ($orderData[0]['AppliReason']) {
		case APPLI_REASON_RETURN_RETIRE:
			$subject .= "退職・異動返却キャンセル";
			break;

		case APPLI_REASON_RETURN_OTHER:
			$subject .= "その他返却キャンセル";
			break;

		default:
			break;
	}

	// 現在日時
	$date = date("Y年n月j日 H時i分");

	// 申請日
	$appliDay = date("Y/m/d", strtotime($orderData[0]['AppliDay']));

	// 本文
	$message = file_get_contents($filePath . 'henpinCancel.txt');

	// 本文の置換
	$message = mb_ereg_replace('###HEAD###', MAIL_BODY_HEADER, $message);
	$message = mb_ereg_replace('###DATE###', $date, $message);

	// 理由
	switch ($orderData[0]['AppliReason']) {
		case APPLI_REASON_RETURN_RETIRE:
			$message = mb_ereg_replace('###REASON###', "退職・異動", $message);
			break;

		case APPLI_REASON_RETURN_OTHER:
			$message = mb_ereg_replace('###REASON###', "その他", $message);
			break;

		default:
			break;
	}

	$message = mb_ereg_replace('###APPLIDAY###', $appliDay, $message);
	$message = mb_ereg_replace('###APPLINO###', $orderData[0]['AppliNo'], $message);
	$message = mb_ereg_replace('###COMPCODE###', $orderData[0]['AppliCompCd'], $message);
	$message = mb_ereg_replace('###COMPNAME###', $orderData[0]['AppliCompName'], $message);
	$message = mb_ereg_replace('###STAFFCODE###', $orderData[0]['StaffCode'], $message);
    $message = mb_ereg_replace('###PERSONNAME###', $orderData[0]['PersonName'], $message);
	$message = mb_ereg_replace('###NOTE###', $orderData[0]['Note'], $message);

	return true;

}

/*
 * 退職・異動アラートメールの件名と本文を作成する
 *
 * 引数  ：$dbConnect  => コネクションハンドラ
 *       ：$orderId    => OrderID
 *       ：$stockouts  => 在庫切れ商品情報
 *       ：$filePath   => メールテンプレートのファイルパス
 *       ：$subject    => 件名
 *       ：$message    => 本文
 *
 * 戻り値：true：メールテンプレート取得成功/false：取得失敗
 *
 * create 2007/06/27 H.Osugi
 *
 */
function henpinStockOutMail($dbConnect, $orderId, $stockouts, $filePath, &$subject, &$message) {

	// 初期化
	$subject = '';
	$message = '';

	// T_Orderの情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" AppliDay,";
	$sql .= 	" AppliCompCd,";
	$sql .= 	" AppliCompName,";
	$sql .= 	" StaffCode,";
    $sql .=     " PersonName";
	$sql .= " FROM";
	$sql .= 	" T_Order";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;
	
	$orderData = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($orderData) <= 0) {
	 	return false;
	}

	// 件名
	$subject = MAIL_SUBJECT_HEADER . " 退職・異動アラート";

	$stockOutTemplateHeader = file_get_contents($filePath . 'stockOutTemplateHeader.txt');
	$stockOutTemplate = file_get_contents($filePath . 'stockOutTemplate.txt');

	$orders = '';
	$appliNo = '';
	$countStockout = count($stockouts);
	for ($i=0; $i<$countStockout; $i++) {

		if ($appliNo != $stockouts[$i]['AppliNo']) {

			$orders .= mb_ereg_replace('###APPLINO###', $stockouts[$i]['AppliNo'], $stockOutTemplateHeader);
			$orders .= "\n";

			$appliNo = $stockouts[$i]['AppliNo'];

		}

		$itemData = '';
		$itemData = mb_ereg_replace('###ITEMNO###', $stockouts[$i]['ItemNo'], $stockOutTemplate);
		$itemData = mb_ereg_replace('###ITEMNAME###', $stockouts[$i]['ItemName'], $itemData);
		$itemData = mb_ereg_replace('###SIZE###', $stockouts[$i]['Size'], $itemData);

		$orders .= $itemData;
		$orders .= "\n\n";

	}

	// 現在日時
	$date = date("Y年n月j日 H時i分");

	// 申請日
	$appliDay = date("Y/m/d", strtotime($orderData[0]['AppliDay']));

	// 本文
	$message = file_get_contents($filePath . 'henpinStockOut.txt');

	// 本文の置換
	$message = mb_ereg_replace('###HEAD###', MAIL_BODY_HEADER, $message);
	$message = mb_ereg_replace('###DATE###', $date, $message);

	$message = mb_ereg_replace('###APPLIDAY###', $appliDay, $message);
	$message = mb_ereg_replace('###COMPCODE###', $orderData[0]['AppliCompCd'], $message);
	$message = mb_ereg_replace('###COMPNAME###', $orderData[0]['AppliCompName'], $message);
	$message = mb_ereg_replace('###STAFFCODE###', $orderData[0]['StaffCode'], $message);
    $message = mb_ereg_replace('###PERSONNAME###', $orderData[0]['PersonName'], $message);
	$message = mb_ereg_replace('###ORDER###', $orders, $message);

	return true;

}


/* ../../include/createHenpinMail.php end */


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



// 変数の初期化 ここから *******************************************************
$cancelMode = '';					// キャンセルモード
$orderId    = '';					// OrderID
$post       = $_POST;				// POST値
// 変数の初期化 ここまで ******************************************************

// 必要な値が取得できなければエラー画面へ遷移
if (!isset($_POST['cancelMode']) || trim($_POST['cancelMode']) == ''
	 || !isset($_POST['orderId']) || trim($_POST['orderId']) == '') {

	$hiddens['errorName'] = 'cancel';
	$hiddens['menuName']  = 'isMenuHistory';
	$hiddens['returnUrl'] = 'top.php';
	$hiddens['errorId'][] = '901';
	$errorUrl             = HOME_URL . 'error.php';

	// エラー画面に強制遷移
	redirectPost($errorUrl, $hiddens);

}

$cancelMode = trim($_POST['cancelMode']);
$orderId    = trim($_POST['orderId']);
if (isset($_POST['orderReturnId'])) {
	$orderReturnId = trim($_POST['orderReturnId']);
}

// トランザクション開始
db_Transaction_Begin($dbConnect);

switch($cancelMode) {

	case '1':		// 発注の場合
		$isSuccess = cancelOrder($dbConnect, $orderId);
		break;
		
	case '2':		// 交換の場合
		$isSuccess = cancelExchange($dbConnect, $orderId, $orderReturnId);
		break;

	case '3':		// 返却の場合
		$isSuccess = cancelReturn($dbConnect, $orderId);
		break;

	default:
		$isSuccess = false;
		break;

}

// 登録が失敗した場合はエラー画面へ遷移
if ($isSuccess == false) {

	// ロールバック
	db_Transaction_Rollback($dbConnect);

	$hiddens['errorName'] = 'cancel';
	$hiddens['menuName']  = '';
	$hiddens['returnUrl'] = 'top.php';
	$hiddens['errorId'][] = '901';
	$errorUrl             = HOME_URL . 'error.php';

	// エラー画面に強制遷移
	redirectPost($errorUrl, $hiddens);
}


// コミット
db_Transaction_Commit($dbConnect);

// キャンセルメールを送信する
switch($cancelMode) {

	case '1':		// 発注の場合
		$isSuccess = sendMailOrder($dbConnect, $orderId);
		break;
		
	case '2':		// 交換の場合
		$isSuccess = sendMailExchange($dbConnect, $orderId);
		break;

	case '3':		// 返却の場合
		$isSuccess = sendMailReturn($dbConnect, $orderId);
		break;

	default:
		$isSuccess = false;
		break;

}

$countSearchStatus = count($post['searchStatus']);
for ($i=0; $i<$countSearchStatus; $i++) {
	$post['searchStatus[' . $i . ']'] = $post['searchStatus'][$i];
}

// POST値をHTMLエンティティ
$post = castHtmlEntity($post); 

$notArrowKeys = array('searchStatus');

$hiddenHtml = array();
if (is_array($post) && count($post) > 0) {
	$hiddenHtml = castHiddenError($post, $notArrowKeys);
}
 
$nextUrl = HOME_URL . 'rireki/rireki.php';

// 申請履歴画面に強制遷移
redirectPost($nextUrl, $hiddenHtml);

/*
 * キャンセル処理（発注の場合）
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$orderId        => OrderID
 * 戻り値：$isSuccess      => ture：キャンセル成功 / false：キャンセル失敗
 *
 * create 2007/03/28 H.Osugi
 *
 */
function cancelOrder($dbConnect, $orderId) {

	// T_Staff_Details の該当情報を論理削除する
	$isSuccess = cancelTStaffDetail($dbConnect, $orderId);

	if ($isSuccess == false) {
		return $isSuccess;
	}

	// T_Order T_Order_Details の該当情報を論理削除する
	$isSuccess = cancelTOrder($dbConnect, $orderId);

	return  $isSuccess;

}

/*
 * キャンセル処理（交換の場合）
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$orderId        => 発注のOrderID
 *       ：$orderReturnId  => 返却のOrderID
 * 戻り値：$isSuccess      => ture：キャンセル成功 / false：キャンセル失敗
 *
 * create 2007/03/28 H.Osugi
 *
 */
function cancelExchange($dbConnect, $orderId, $orderReturnId) {

	// T_Staff_Details の該当情報を論理削除する
	$isSuccess = cancelTStaffDetail($dbConnect, $orderId);

	if ($isSuccess == false) {
		return $isSuccess;
	}

	// T_Staff_Details の該当情報を返却前の情報に戻す
	$isSuccess = returnTStaffDetail($dbConnect, $orderReturnId);

	if ($isSuccess == false) {
		return $isSuccess;
	}

	// T_Order T_Order_Details の該当情報を論理削除する
	$isSuccess = cancelTOrder($dbConnect, $orderId);

	if ($isSuccess == false) {
		return $isSuccess;
	}

	// T_Order T_Order_Details の該当情報を論理削除する
	$isSuccess = cancelTOrder($dbConnect, $orderReturnId);

	return  $isSuccess;

}

/*
 * キャンセル処理（返却の場合）
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$orderId        => OrderID
 * 戻り値：$isSuccess      => ture：キャンセル成功 / false：キャンセル失敗
 *
 * create 2007/03/28 H.Osugi
 *
 */
function cancelReturn($dbConnect, $orderId) {

	// Statusを取得
	$status = getOrderStatus($dbConnect, $orderId) ;

	if ($status == '') {
		return false;
	}

	// T_Staff_Details の該当情報を返却前の情報に戻す
	$isSuccess = returnTStaffDetail($dbConnect, $orderId);

	if ($isSuccess == false) {
		return $isSuccess;
	}

	if ($status == STATUS_NOT_RETURN_ORDER) {		// 未返却（受注済）の場合はキャンセルに
		// T_Order T_Order_Details の該当情報をキャンセルに変更
		$isSuccess = returnTOrderCancel($dbConnect, $orderId);
	}
	else {
		// T_Order T_Order_Details の該当情報を論理削除する
		$isSuccess = cancelTOrder($dbConnect, $orderId);
	}

	return  $isSuccess;

}


/*
 * Statusを取得する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$orderId        => OrderID
 * 戻り値：$isSuccess      => ture：キャンセル成功 / false：キャンセル失敗
 *
 * create 2007/04/16 H.Osugi
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

/*
 * T_Order T_Order_Detailsを論理削除する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$orderId        => OrderID
 * 戻り値：$isSuccess      => ture：キャンセル成功 / false：キャンセル失敗
 *
 * create 2007/03/28 H.Osugi
 *
 */
function cancelTOrder($dbConnect, $orderId) {

	// TokIDを取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" ttk.TokID";
	$sql .= " FROM";
	$sql .= 	" T_Tok ttk";
	$sql .= " INNER JOIN";
	$sql .= 	" T_Order tor";
	$sql .= " ON";
	$sql .= 	" ttk.OrderID = tor.OrderID";
	$sql .= " AND";
	$sql .= 	" tor.Del = " . DELETE_OFF;
	$sql .= " WHERE";
	$sql .= 	" tor.OrderID = '" . db_Escape($orderId) . "'";
	$sql .= " AND";
	$sql .= 	" ttk.Del = " . DELETE_OFF;

	$orderDatas = db_Read($dbConnect, $sql);

	// TokIDが取得できた場合はT_Tokを論理削除
	if (isset($orderDatas[0]['TokID']) && $orderDatas[0]['TokID'] != '') {

		$tokID = $orderDatas[0]['TokID'];

		// T_Tokを論理削除する
		$sql  = "";
		$sql .= " UPDATE";
		$sql .= 	" T_Tok";
		$sql .= " SET";
		$sql .= 	" Del = " . DELETE_ON . ",";
		$sql .= 	" UpdDay = GETDATE(),";
		$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= " WHERE";
		$sql .= 	" TokID = '" . db_Escape($tokID) . "'";
	
		$isSuccess = db_Execute($dbConnect, $sql);
	
		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}

	}

	// T_Orderを論理削除する
	$sql  = "";
	$sql .= " UPDATE";
	$sql .= 	" T_Order";
	$sql .= " SET";
	$sql .= 	" Del = " . DELETE_ON . ",";
	$sql .= 	" UpdDay = GETDATE(),";
	$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";

	$isSuccess = db_Execute($dbConnect, $sql);

	// 実行結果が失敗の場合
	if ($isSuccess == false) {
		return false;
	}

	// T_Order_Detailsを論理削除する
	$sql  = "";
	$sql .= " UPDATE";
	$sql .= 	" T_Order_Details";
	$sql .= " SET";
	$sql .= 	" Del = " . DELETE_ON . ",";
	$sql .= 	" UpdDay = GETDATE(),";
	$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";

	$isSuccess = db_Execute($dbConnect, $sql);

	// 実行結果が失敗の場合
	if ($isSuccess == false) {
		return false;
	}

	return true;

}

/*
 * T_Order T_Order_Detailsをキャンセルにする
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$orderId        => OrderID
 * 戻り値：$isSuccess      => ture：キャンセル成功 / false：キャンセル失敗
 *
 * create 2007/04/16 H.Osugi
 *
 */
function returnTOrderCancel($dbConnect, $orderId) {

	// T_Orderをキャンセルする
	$sql  = "";
	$sql .= " UPDATE";
	$sql .= 	" T_Order";
	$sql .= " SET";
	$sql .= 	" Status = " . STATUS_CANCEL . ",";		// Statusをキャンセルに変更
	$sql .= 	" UpdDay = GETDATE(),";
	$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";

	$isSuccess = db_Execute($dbConnect, $sql);

	// 実行結果が失敗の場合
	if ($isSuccess == false) {
		return false;
	}

	// T_Order_Detailsを論理削除する
	$sql  = "";
	$sql .= " UPDATE";
	$sql .= 	" T_Order_Details";
	$sql .= " SET";
	$sql .= 	" Status = " . STATUS_CANCEL . ",";		// Statusをキャンセルに変更
	$sql .= 	" UpdDay = GETDATE(),";
	$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
	$sql .= " WHERE";
	$sql .= 	" OrderID = '" . db_Escape($orderId) . "'";

	$isSuccess = db_Execute($dbConnect, $sql);

	// 実行結果が失敗の場合
	if ($isSuccess == false) {
		return false;
	}

	return true;

}

/*
 * T_Staff_Detailsを論理削除する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$orderId        => OrderID
 * 戻り値：$isSuccess      => ture：キャンセル成功 / false：キャンセル失敗
 *
 * create 2007/03/28 H.Osugi
 *
 */
function cancelTStaffDetail($dbConnect, $orderId) {

	// T_Staff_Detailsを論理削除する
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
	$sql .= 		" 	OrderDetID";
	$sql .= 		" FROM";
	$sql .= 			" T_Order_Details";
	$sql .= 		" WHERE";
	$sql .= 			" OrderID = '" . db_Escape($orderId) . "'";
	$sql .= 		" AND";
	$sql .= 			" Del = " . DELETE_OFF. ")";

	$isSuccess = db_Execute($dbConnect, $sql);

	// 実行結果が失敗の場合
	if ($isSuccess == false) {
		return false;
	}

	return true;

}

/*
 * T_Staff_Detailsを納品済みに戻す
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$orderId        => OrderID
 * 戻り値：$isSuccess      => ture：キャンセル成功 / false：キャンセル失敗
 *
 * create 2007/03/28 H.Osugi
 *
 */
function returnTStaffDetail($dbConnect, $orderId) {

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

		// T_Staff_Detailsのstatusを納品済にする
		$sql  = "";
		$sql .= " UPDATE";
		$sql .= 	" T_Staff_Details";
		$sql .= " SET";
		$sql .= 	" ReturnDetID = NULL,";					// 返却時のOrderDetIDをNULLに変更
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

	return true;

}

/*
 * 発注キャンセルメールを送信する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$orderId   => OrderID
 * 戻り値：true：変更成功 / false：変更失敗
 *
 * create 2007/04/02 H.Osugi
 *
 */
function sendMailOrder($dbConnect, $orderId) {

	$filePath = '../../mail_template/';

	// キャンセルメールの件名と本文を取得
	$isSuccess = hachuCancelMail($dbConnect, $orderId, $filePath, $subject, $message, $tokFlg);

	if ($isSuccess == false) {
		return false;
	}

	$toAddr = MAIL_GROUP_1;

	// 特寸情報があった場合は特寸のメールグループにもメール送信
	if ($tokFlg == 1) {

		if($toAddr != '') {
			$toAddr .= ',';
		}
		$toAddr .= MAIL_GROUP_4;

	}

	$bccAddr = MAIL_GROUP_0;
	$fromAddr = FROM_MAIL;
	$returnAddr = RETURN_MAIL;

	// メールを送信
	$isSuccess = sendTextMail($toAddr, $subject, $message, $fromAddr, '', $bccAddr, 'UTF-8', $rturnAddr);

	if ($isSuccess == false) {
		return false;
	}

	return true;

}

/*
 * 交換キャンセルメールを送信する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$orderId   => OrderID
 * 戻り値：true：変更成功 / false：変更失敗
 *
 * create 2007/04/25 H.Osugi
 *
 */
function sendMailExchange($dbConnect, $orderId) {

	$filePath = '../../mail_template/';

	// キャンセルメールの件名と本文を取得
	$isSuccess = koukanCancelMail($dbConnect, $orderId, $filePath, $subject, $message, $tokFlg);

	if ($isSuccess == false) {
		return false;
	}

	$toAddr = MAIL_GROUP_2;

	// 特寸情報があった場合は特寸のメールグループにもメール送信
	if ($tokFlg == 1) {

		if($toAddr != '') {
			$toAddr .= ',';
		}
		$toAddr .= MAIL_GROUP_4;

	}

	$bccAddr = MAIL_GROUP_0;
	$fromAddr = FROM_MAIL;
	$returnAddr = RETURN_MAIL;

	// メールを送信
	$isSuccess = sendTextMail($toAddr, $subject, $message, $fromAddr, '', $bccAddr, 'UTF-8', $rturnAddr);

	if ($isSuccess == false) {
		return false;
	}

	return true;

}

/*
 * 返却キャンセルメールを送信する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$post      => POST値
 *       ：$orderId   => OrderID
 * 戻り値：true：変更成功 / false：変更失敗
 *
 * create 2007/04/25 H.Osugi
 *
 */
function sendMailReturn($dbConnect, $orderId) {

	$filePath = '../../mail_template/';

	// キャンセルメールの件名と本文を取得
	$isSuccess = henpinCancelMail($dbConnect, $orderId, $filePath, $subject, $message);

	if ($isSuccess == false) {
		return false;
	}

	$toAddr = MAIL_GROUP_3;
	$bccAddr = MAIL_GROUP_0;
	$fromAddr = FROM_MAIL;
	$returnAddr = RETURN_MAIL;

	// メールを送信
	$isSuccess = sendTextMail($toAddr, $subject, $message, $fromAddr, '', $bccAddr, 'UTF-8', $rturnAddr);

	if ($isSuccess == false) {
		return false;
	}

	return true;

}

?>
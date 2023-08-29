<?php
/*
 * 返却確認画面
 * henpin_shinsei_kakunin.src.php
 *
 * create 2007/03/22 H.Osugi
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


/* ../../include/checkDuplicateAppli.php start */

/*
 * 申請番号がすでに登録されていないか判定
 * checkRequestNo.php
 *
 * create 2007/04/05 H.Osugi
 *
 */

/*
 * 申請番号がすでに登録されていないかを判定する
 * 引数  ：$dbConnect  => コネクションハンドラ
 *       ：$requestNo  => 検証したいAppliNo
 *       ：$returnUrl  => 戻り先URL
 *       ：$mode       => 1：発注 / 2：交換 / 3：返却
 * 戻り値：なし
 */
function checkDuplicateAppliNo($dbConnect, $requestNo, $returnUrl, $mode) {

	// 選択されたorderDetID
	if($requestNo == '') {
		return;
	}

	// 該当の申請番号が存在しないかを判定する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" count(*) as count_order";
	$sql .= " FROM";
	$sql .= 	" T_Order";
	$sql .= " WHERE";

	if ($mode == '2') {
		$sql .= 	" (";
		$sql .= 			" AppliNo = '" . db_Escape('A' . $requestNo) . "'";
		$sql .= 		" OR";
		$sql .= 			" AppliNo = '" . db_Escape('R' . $requestNo) . "'";
		$sql .= 	" )";
	}
	else {
		$sql .= 	" AppliNo = '" . db_Escape($requestNo) . "'";
	}

	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;

	$result = db_Read($dbConnect, $sql);

	// 該当の申請番号が存在する場合
	if (isset($result[0]['count_order']) && $result[0]['count_order'] > 0) {

		// エラー画面で必要な値のセット
		$hiddens = array();
		$hiddens['errorName'] = 'checkRequestNo';
		switch ($mode) {
			case '1':
				$hiddens['menuName']  = 'isMenuOrder';
				break;
			case '2':
				$hiddens['menuName']  = 'isMenuExchange';
				break;
			case '3':
				$hiddens['menuName']  = 'isMenuReturn';
				break;
			default:
				break;
		}
			
		$hiddens['returnUrl'] = $returnUrl;
		$hiddens['errorId'][] = '901';

		redirectPost(HOME_URL . 'error.php', $hiddens);

	}
}


/* ../../include/checkDuplicateAppli.php end */


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


/* ./henpin_shinsei.val.php start */

/*
 * エラー判定処理
 * henpin_shinsei.val.php
 *
 * create 2007/03/22 H.Osugi
 *
 *
 */

/*
 * 機能  ：エラー判定を行う
 * 引数  ：$post       => POSTデータ
 * 戻り値：なし
 *
 * create 2007/03/22 H.Osugi
 *
 */
function validatePostData($post) {

	// 初期化
	$hiddens = array();

	// メモが存在しなければ初期化
	if (!isset($post['memo'])) {
		$post['memo'] = '';
	}

	// その他返却の場合
	if ($post['appliReason'] == APPLI_REASON_RETURN_OTHER) {

		// スタッフコードの判定
		$result = checkData(trim($post['memo']), 'Text', true, 128);
	
		// エラーが発生したならば、エラーメッセージを取得
		switch ($result) {
	
			// 空白ならば
			case 'empty':
				$hiddens['errorId'][] = '001';
				break;
				
			// 最大値超過ならば
			case 'max':
				$hiddens['errorId'][] = '002';
				break;
	
		}

	}


	// 退店返却の場合
	else {

		// スタッフコードの判定
		$result = checkData(trim($post['memo']), 'Text', false, 128);
	
		// エラーが発生したならば、エラーメッセージを取得
		switch ($result) {
	
			// 最大値超過ならば
			case 'max':
				$hiddens['errorId'][] = '002';
				break;
	

			default:

				// 選択されていないユニフォームが存在するか判定
				$countOrderDetId = count($post['orderDetIds']);
				if ($countOrderDetId <= 0) {
					$hiddens['errorId'][] = '003';
					break;
				} else {
					for ($i=0; $i<$countOrderDetId; $i++) {
						if ((!isset($post['returnChk'][$post['orderDetIds'][$i]]) && !isset($post['lostChk'][$post['orderDetIds'][$i]])) || !(int)$post['orderDetIds'][$i]) {
							$hiddens['errorId'][] = '003';
							break;
						}
					}
				}
				break;
		}

/*----------------------------------------------------------
		// 退職返却の場合、レンタル終了日をチェック
		if ($post['appliReason'] == APPLI_REASON_RETURN_RETIRE) {

			// レンタル終了日が存在しなければ初期化
			if (!isset($post['rentalEndDay'])) {
				$post['rentalEndDay'] = '';
			}

		    // レンタル終了日の判定
		    $minDateTime = mktime(0,0,0,date('m'), date('d'), date('Y'));
		    $result = checkData(trim($post['rentalEndDay']), 'Date', true, '', date('Y', $minDateTime).'/'.date('m', $minDateTime).'/'.date('d', $minDateTime));

		    // エラーが発生したならば、エラーメッセージを取得
		    switch ($result) {

		        case 'empty':
		            $hiddens['errorId'][] = '100';
		            break;

		        // 存在しない日付なら
		        case 'mode':
		            $hiddens['errorId'][] = '101';
		            break;

		        // 今日以前なら
		        case 'min':
		            $hiddens['errorId'][] = '102';
		            break;

		        default:
		            break;

		    }
	    }
----------------------------------------------------------*/
	}

	// 返却または紛失がひとつでも選択されているか判定
	$count = 0;
	$countOrderDetId = count($post['orderDetIds']);
	for ($i=0; $i<$countOrderDetId; $i++) {
		if ((isset($post['returnChk'][$post['orderDetIds'][$i]]) && $post['returnChk'][$post['orderDetIds'][$i]] == '1')
		|| (isset($post['lostChk'][$post['orderDetIds'][$i]]) && $post['lostChk'][$post['orderDetIds'][$i]] == '1')) {
			break;
		}
		$count++;
	}
	if ($countOrderDetId == $count) {
		$hiddens['errorId'][] = '004';
	}

	// エラーが存在したならば、エラー画面に遷移
	if (count($hiddens['errorId']) > 0) {
		$hiddens['errorName'] = 'henpinShinsei';
		$hiddens['menuName']  = 'isMenuReturn';
		$hiddens['returnUrl'] = 'henpin/henpin_shinsei.php';
		$errorUrl             = HOME_URL . 'error.php';

		$post['henpinShinseiFlg'] = '1';

		// POST値をHTMLエンティティ
		$post = castHtmlEntity($post); 

		// hidden値の成型
		$countOrderDetIds = count($post['orderDetIds']);
		for ($i=0; $i<$countOrderDetIds; $i++) {
			$post['orderDetIds[' . $i . ']'] = $post['orderDetIds'][$i];
			if (isset($post['returnChk'][$post['orderDetIds'][$i]]) && $post['returnChk'][$post['orderDetIds'][$i]] == 1) {
				$post['returnChk[' . $post['orderDetIds'][$i] . ']'] = trim($post['returnChk'][$post['orderDetIds'][$i]]);
			}
			if (isset($post['lostChk'][$post['orderDetIds'][$i]]) && $post['lostChk'][$post['orderDetIds'][$i]] == 1) {
				$post['lostChk[' . $post['orderDetIds'][$i] . ']'] = trim($post['lostChk'][$post['orderDetIds'][$i]]);
			}
			if (isset($post['brokenChk'][$post['orderDetIds'][$i]]) && $post['brokenChk'][$post['orderDetIds'][$i]] == 1) {
				$post['brokenChk[' . $post['orderDetIds'][$i] . ']'] = trim($post['brokenChk'][$post['orderDetIds'][$i]]);
			}
		}
		$notArrowKeys = array('orderDetIds', 'returnChk', 'lostChk', 'brokenChk');
		$hiddenHtml = castHiddenError($post, $notArrowKeys);

		$hiddens = array_merge($hiddens, $hiddenHtml);

		redirectPost($errorUrl, $hiddens);

	}

}


/* ./henpin_shinsei.val.php end */



// 初期設定
$isMenuReturn = true;			// 返却のメニューをアクティブに

// 変数の初期化 ここから *******************************************************
$requestNo = '';					// 申請番号
$staffCode = '';					// スタッフコード
$memo      = '';					// メモ
$rentalEndDay  = '';              	// レンタル終了日

$isEmptyMemo = true;				// メモが空かどうかを判定するフラグ
$isEmptyRentalEndDay = true;		// レンタル終了日が空かどうかを判定するフラグ

$selectedReason1 = false;			// 返却理由（退職・異動返却）
$selectedReason2 = false;			// 返却理由（その他返却）

// 変数の初期化 ここまで ******************************************************

// スタッフIDが取得できなければエラーに
if (!isset($_POST['staffId']) || $_POST['staffId'] == '' || !(int)$_POST['staffId']) {
    // TOP画面に強制遷移
	$returnUrl             = HOME_URL . 'top.php';
	redirectPost($returnUrl, $hiddens);
}

// 申請番号がすでに登録されていないか判定
checkDuplicateAppliNo($dbConnect, $_POST['requestNo'], 'henpin/henpin_top.php', 3);

// エラー判定
validatePostData($_POST);

// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST); 


if (isset($post['searchFlg']) && $post['searchFlg'] != '') {
    $searchFlg = $post['searchFlg'];
} else {
    $searchFlg = '';
}

if (isset($post['nowPage']) && $post['nowPage'] != '') {
    $nowPage = $post['nowPage'];
} else {
    $nowPage = '';
}

if (isset($post['appliReason']) && $post['appliReason'] != '') {
    $appliReason = $post['appliReason'];
} else {
    $appliReason = '';
}

if (isset($post['searchStaffCode']) && $post['searchStaffCode'] != '') {
    $searchStaffCode = $post['searchStaffCode'];
} else {
    $searchStaffCode = '';
}

if (isset($post['searchPersonName']) && $post['searchPersonName'] != '') {
    $searchPersonName = $post['searchPersonName'];
} else {
    $searchPersonName = '';
}

if (isset($post['searchHonbuId']) && $post['searchHonbuId'] != '') {
    $searchHonbuId = $post['searchHonbuId'];
} else {
    $searchHonbuId = '';
}

if (isset($post['searchShitenId']) && $post['searchShitenId'] != '') {
    $searchShitenId = $post['searchShitenId'];
} else {
    $searchShitenId = '';
}

if (isset($post['searchEigyousyoId']) && $post['searchEigyousyoId'] != '') {
    $searchEigyousyoId = $post['searchEigyousyoId'];
} else {
    $searchEigyousyoId = '';
}


// 返却・紛失のどちらかが選択されたorderDetIDを取得する
$orderDetIds = castOrderDetId($post);

// 返却できないユニフォームが存在しないかを判定する
if ($post['appliReason'] == APPLI_REASON_RETURN_RETIRE) {
	checkReturn($dbConnect, $post['orderDetIds'], 'henpin/henpin_top.php');
}
else {
	checkReturn($dbConnect, $orderDetIds, 'henpin/henpin_top.php');
}
// 表示する商品詳細情報取得
$items = getStaffOrderSelect($dbConnect, $post, $orderDetIds);

// 表示する値の成型 ここから +++++++++++++++++++++++++++++++++++++++++++++++++++

// 画面表示用データ取得
$headerData = getHeaderData($dbConnect, $post['staffId']);

// 店舗ID
$compId     = $headerData['CompID'];

// 店舗コード
$compCd     = $headerData['CompCd'];

// 店舗名
$compName   = $headerData['CompName'];

// スタッフコード
$staffCode  = $headerData['StaffCode'];

// 着用者名
$personName = $headerData['PersonName'];

// 申請番号
$requestNo = trim($post['requestNo']);

// レンタル終了日
$rentalEndDay = trim($post['rentalEndDay']);

if ($rentalEndDay != '' || $rentalEndDay === 0) {
	$isEmptyRentalEndDay = false;
}

// メモ
$memo = trim($post['memo']);

if ($memo != '' || $memo === 0) {
	$isEmptyMemo = false;
}

$appliReason = trim($post['appliReason']);	// 返却理由

// 返却理由
switch (trim($post['appliReason'])) {

	// 返却理由（退職・異動返却）
	case APPLI_REASON_RETURN_RETIRE:
		$selectedReason1 = true;
		break;

	// 返却理由（その他返却）
	case APPLI_REASON_RETURN_OTHER:
		$selectedReason2 = true;
		break;

	default:
		break;

}

// hidden値の成型
$countOrderDetIds = count($post['orderDetIds']);
// 退職・異動返却の場合
if ($post['appliReason'] == APPLI_REASON_RETURN_RETIRE) {
	for ($i=0; $i<$countOrderDetIds; $i++) {
		$post['orderDetIds[' . $i . ']'] = $post['orderDetIds'][$i];
		if (isset($post['returnChk'][$post['orderDetIds'][$i]]) && $post['returnChk'][$post['orderDetIds'][$i]] == 1) {
			$post['returnChk[' . $post['orderDetIds'][$i] . ']'] = trim($post['returnChk'][$post['orderDetIds'][$i]]);
		}
		elseif (isset($post['lostChk'][$post['orderDetIds'][$i]]) && $post['lostChk'][$post['orderDetIds'][$i]] == 1) {
			$post['lostChk[' . $post['orderDetIds'][$i] . ']'] = trim($post['lostChk'][$post['orderDetIds'][$i]]);
		}
		if (isset($post['brokenChk'][$post['orderDetIds'][$i]]) && $post['brokenChk'][$post['orderDetIds'][$i]] == 1) {
			$post['brokenChk[' . $post['orderDetIds'][$i] . ']'] = trim($post['brokenChk'][$post['orderDetIds'][$i]]);
		}
	}
}
// その他返却の場合
elseif ($post['appliReason'] == APPLI_REASON_RETURN_OTHER) {
	$j = 0;
	for ($i=0; $i<$countOrderDetIds; $i++) {
		if (isset($post['returnChk'][$post['orderDetIds'][$i]]) && $post['returnChk'][$post['orderDetIds'][$i]] == 1) {
			$post['orderDetIds[' . $j . ']'] = $post['orderDetIds'][$i];
			$post['returnChk[' . $post['orderDetIds'][$i] . ']'] = trim($post['returnChk'][$post['orderDetIds'][$i]]);
			$j++;
		}
		elseif (isset($post['lostChk'][$post['orderDetIds'][$i]]) && $post['lostChk'][$post['orderDetIds'][$i]] == 1) {
			$post['orderDetIds[' . $j . ']'] = $post['orderDetIds'][$i];
			$post['lostChk[' . $post['orderDetIds'][$i] . ']'] = trim($post['lostChk'][$post['orderDetIds'][$i]]);
			$j++;
		}
		if (isset($post['brokenChk'][$post['orderDetIds'][$i]]) && $post['brokenChk'][$post['orderDetIds'][$i]] == 1) {
			$post['brokenChk[' . $post['orderDetIds'][$i] . ']'] = trim($post['brokenChk'][$post['orderDetIds'][$i]]);
		}
	}
}

$notArrowKeys = array('orderDetIds', 'returnChk', 'lostChk', 'brokenChk');
$hiddenHtml = castHidden($post, $notArrowKeys);
// 表示する値の成型 ここまで ++++++++++++++++++++++++++++++++++++++++++++++++++

/*
 * 選択された商品一覧情報を取得する
 * 引数  ：$dbConnect    => コネクションハンドラ
 *       ：$post         => POST値
 *       ：$orderDetIds  => 選択されたorderDetID(array)
 * 戻り値：$result       => 選択された商品一覧情報
 *
 * create 2007/03/22 H.Osugi
 *
 */
function getStaffOrderSelect($dbConnect, $post, $orderDetIds) {

	// 初期化
	$result = array();

	if (!is_array($orderDetIds) || count($orderDetIds) <= 0) {
		return $result;
	}

	$orderDetId = '';
	if(is_array($orderDetIds)) {
		$orderDetId = implode(', ', $orderDetIds);
	}

	// 選択された商品の一覧を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" tod.OrderDetID,";
	$sql .= 	" mi.ItemID,";
	$sql .= 	" mi.ItemName,";
	$sql .= 	" tod.BarCd,";
	$sql .= 	" tod.Size";
	$sql .= " FROM";
	$sql .= 	" T_Staff_Details tsd";
	$sql .= " INNER JOIN";
	$sql .= 	" T_Staff ts";
	$sql .= " ON";
	$sql .= 	" tsd.StaffID = ts.StaffID";
	$sql .= " AND";
	$sql .= 	" ts.Del = " . DELETE_OFF;
	$sql .= " INNER JOIN";
	$sql .= 	" T_Order_Details tod";
	$sql .= " ON";
	$sql .= 	" tsd.OrderDetID = tod.OrderDetID";
	$sql .= " AND";
	$sql .= 	" tod.OrderDetID IN (" . db_Escape($orderDetId) . ")";
	$sql .= " AND";
	$sql .= 	" tod.Del = " . DELETE_OFF;
	$sql .= " INNER JOIN";
	$sql .= 	" M_Item mi";
	$sql .= " ON";
	$sql .= 	" tod.ItemID = mi.ItemID";
	$sql .= " AND";
	$sql .= 	" mi.Del = " . DELETE_OFF;
	$sql .= " INNER JOIN";
	$sql .= 	" T_Order tor";
	$sql .= " ON";
	$sql .= 	" tod.OrderID = tor.OrderID";
	$sql .= " AND";
	$sql .= 	" tor.Del = " . DELETE_OFF;
	$sql .= " WHERE";
	$sql .= 	" tsd.Del = " . DELETE_OFF;
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
		$result[$i]['OrderDetID'] = $result[$i]['OrderDetID'];
		$result[$i]['ItemName']   = castHtmlEntity($result[$i]['ItemName']);
		$result[$i]['BarCd']      = castHtmlEntity($result[$i]['BarCd']);
		$result[$i]['Size']       = castHtmlEntity($result[$i]['Size']);

		// バーコードが空かどうか判定
		$result[$i]['isEmptyBarCd'] = false;
		if ($result[$i]['BarCd'] == '') {
			$result[$i]['isEmptyBarCd'] = true;
		}

		// 返却・紛失のどちらが選択されたか判定
		$result[$i]['isCheckedReturn'] = true;
		if (isset($post['lostChk'][$result[$i]['OrderDetID']]) && $post['lostChk'][$result[$i]['OrderDetID']] == 1) {
			$result[$i]['isCheckedReturn'] = false;
		}

		// 汚損・破損が選択されたか判定
		$result[$i]['isCheckedBroken'] = false;
		if (isset($post['brokenChk'][$result[$i]['OrderDetID']]) && $post['brokenChk'][$result[$i]['OrderDetID']] == 1) {
			$result[$i]['isCheckedBroken'] = true;
		}

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
<?php if($selectedReason1) { ?>
          <h1>ユニフォーム返却確認　（退職・異動返却）</h1>
<?php } ?>
<?php if($selectedReason2) { ?>
          
          <h1>ユニフォーム返却確認　（その他返却）</h1>
          
<?php } ?>
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
              <td width="100" class="line"><span class="fbold">職員名</span></td>
              <td width="400" class="line"><?php isset($personName) ? print($personName) : print('&#123;personName&#125;'); ?></td>
            </tr>
            <tr height="30">
              <td class="line"><span class="fbold">返却理由</span></td>
              <td colspan="3" class="line">
<?php if($selectedReason1) { ?>
                退職・異動返却
<?php } ?>
<?php if($selectedReason2) { ?>
                
                その他返却
                
<?php } ?>
              </td>
            </tr>
            <tr height="30">
              <td class="line"><span class="fbold">メモ</span></td>
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
          <h3>◆下記の内容で申請されています。返却しますか？</h3>
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
          <form action="henpin_shinsei_kanryo.php" name="confForm" method="post">
            
            <div class="bot"><a href="#" onclick="document.confForm.action='./henpin_shinsei.php'; document.confForm.submit(); return false;"><img src="../img/modoru.gif" alt="戻る" width="112" height="32" border="0"></a> &nbsp;&nbsp;&nbsp;&nbsp; <a href="#" onclick="document.confForm.submit(); return false;"><img src="../img/henpin.gif" alt="返却" width="112" height="32" border="0"></a></div>
            
            

            <input type="hidden" value="1" name="henpinShinseiFlg">
<?php for ($i1_hiddenHtml=0; $i1_hiddenHtml<count($hiddenHtml); $i1_hiddenHtml++) { ?>
        <input type="hidden" value="<?php isset($hiddenHtml[$i1_hiddenHtml]['value']) ? print($hiddenHtml[$i1_hiddenHtml]['value']) : print('&#123;hiddenHtml.value&#125;'); ?>" name="<?php isset($hiddenHtml[$i1_hiddenHtml]['name']) ? print($hiddenHtml[$i1_hiddenHtml]['name']) : print('&#123;hiddenHtml.name&#125;'); ?>">
<?php } ?>
          </form>
          

        </div>
        <br><br><br>
      </div>
    </div>
  </body>
</html>

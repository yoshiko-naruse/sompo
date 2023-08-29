<?php
/*
 * 返却完了画面
 * henpin_shinsei_kanryo.src.php
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
$orderId   = '';					// OrderID
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
if (!isset($_POST['staffId']) || $_POST['staffId'] == '') {
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

// 返却できないユニフォームが存在しないかを判定する
checkReturn($dbConnect, $post['orderDetIds'], 'henpin/henpin_top.php');

// 退職・異動申請の場合
if ($post['appliReason'] == APPLI_REASON_RETURN_RETIRE) { 
	// 在庫切れ商品が無いか判定
	$stockOutData = getStockOut($dbConnect, $post);
}

// 返却がひとつも選択されていなかったら紛失申請
$isAllLoss = false;
if (count($post['returnChk']) <= 0) {
	$isAllLoss = true;
}

// 返却が選択されているか
$hasReturn = false;
if (count($post['returnChk']) > 0) {
	$hasReturn = true;
}

// 紛失が選択されているか
$hasLoss = false;
if (count($post['lostChk']) > 0) {
	$hasLoss = true;
}

// 汚損・破損が選択されているか
$hasBroken = false;
if (count($post['brokenChk']) > 0) {
	$hasBroken = true;
}

// 画面表示用データ取得
$headerData = getHeaderData($dbConnect, $post['staffId']);

// トランザクション開始
db_Transaction_Begin($dbConnect);

// 返却申請処理
$isSuccessReturn = createReturn($dbConnect, $post, $isAllLoss, $headerData, $orderId);

// 返却処理失敗時
if ($isSuccessReturn == false) {

	// ロールバック
	db_Transaction_Rollback($dbConnect);

	$hiddens['errorName'] = 'henpinShinsei';
	$hiddens['menuName']  = 'isMenuReturn';
	$hiddens['returnUrl'] = 'henpin/henpin_top.php';
	$hiddens['errorId'][] = '902';
	$errorUrl             = HOME_URL . 'error.php';

	// エラー画面に強制遷移
	redirectPost($errorUrl, $hiddens);

}

// コミット
db_Transaction_Commit($dbConnect);

// 返却申請メール送信
$isSuccess = sendMailShinsei($dbConnect, $orderId);

// 退職・異動申請の場合
if ($post['appliReason'] == APPLI_REASON_RETURN_RETIRE) {

	if (isset($stockOutData) && count($stockOutData) > 0) {

		// 退職・異動アラートメール送信
		$isSuccess = sendMailStockOut($dbConnect, $orderId, $stockOutData);

	}
}

// 返却・紛失のどちらかが選択されたorderDetIDを取得する
$orderDetIds = castOrderDetId($post);

// 表示する返却申請情報取得
$returns = getReturnSelect($dbConnect, $post, $orderDetIds);

// 表示する値の成型 ここから +++++++++++++++++++++++++++++++++++++++++++++++++++
// 申請番号
$requestNo = trim($post['requestNo']);

// 店舗コード
$compCd     = trim($headerData['CompCd']);

// 店舗名
$compName   = trim($headerData['CompName']);

// スタッフコード
$staffCode  = trim($headerData['StaffCode']);

// 着用者名
$personName = trim($headerData['PersonName']);

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
switch (trim($appliReason)) {

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
$notArrowKeys = array('orderDetIds', 'returnChk', 'lostChk', 'brokenChk');
$hiddenHtml = castHidden($post, $notArrowKeys);
// 表示する値の成型 ここまで ++++++++++++++++++++++++++++++++++++++++++++++++++

/*
 * 返却申請一覧情報を取得する
 * 引数  ：$dbConnect    => コネクションハンドラ
 *       ：$post         => POST値
 * 戻り値：$result       => 返却申請された商品一覧情報
 *
 * create 2007/03/22 H.Osugi
 *
 */
function getReturnSelect($dbConnect, $post, $orderDetIds) {

	// 初期化
	$result = array();

	if (!is_array($orderDetIds) || count($orderDetIds) <= 0) {
		return $result;
	}

	$orderDetId = '';
	if(is_array($orderDetIds)) {
		foreach ($orderDetIds as $key => $value) {
			if (!(int)$value) {
				return $result;
			}
		}
		$orderDetId = implode(', ', $orderDetIds);
	}

	// 返却申請一覧の一覧を取得する
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

/*
 * 返却申請を登録する
 * 引数  ：$dbConnect    => コネクションハンドラ
 *       ：$post         => POST値
 *       ：$isAllLoss    => 返却申請か紛失申請の判定
 *       ：$headerData   => 店舗コード、スタッフコード等
 *       ：$orderId      => OrderID
 * 戻り値：true：登録成功 / false：登録失敗
 *
 * create 2007/03/22 H.Osugi
 *
 */
function createReturn($dbConnect, $post, $isAllLoss, $headerData, &$orderId) {

	global $isLevelAdmin;

	// 選択されたorderDetID
	$orderDetIds = '';
	if(is_array($post['orderDetIds'])) {
		foreach ($post['orderDetIds'] as $key => $value) {
			if (!(int)$value) {
				return false;
			}
		}
		$orderDetIds = implode(', ', $post['orderDetIds']);
	}

	// T_Orderに登録する
	$sql  = "";
	$sql .= " INSERT INTO";
	$sql .= 	" T_Order";
	$sql .= 		" (";
	$sql .= 		" AppliDay,";
	$sql .= 		" AppliNo,";
	$sql .= 		" AppliUserID,";
	$sql .= 		" AppliCompCd,";
	$sql .= 		" AppliCompName,";
	$sql .= 		" AppliMode,";
	$sql .= 		" AppliSeason,";
	$sql .= 		" AppliReason,";
	$sql .= 		" CompID,";
	$sql .= 		" StaffID,";
	$sql .= 		" StaffCode,";
	$sql .= 		" PersonName,";
	$sql .= 		" Note,";
	$sql .= 		" Status,";
	$sql .= 		" Tok,";
	$sql .= 		" RentalEndDay,";
	$sql .= 		" Del,";
	$sql .= 		" RegistDay,";
	$sql .= 		" RegistUser";
	$sql .= 		" )";
	$sql .= " VALUES";
	$sql .= 		" (";
	$sql .= 		" GETDATE(),";
	$sql .= 		" '" . db_Escape(trim($post['requestNo'])) . "',";
	$sql .= 		" '" . db_Escape(trim($_SESSION['USERID'])) . "',";

	$sql .= 		" '" . db_Escape(trim($headerData['CompCd'])) . "',";
	$sql .= 		" '" . db_Escape(trim($headerData['CompName'])) . "',";

	$sql .= 		" " . APPLI_MODE_RETURN . ",";		// 返却は3
	$sql .= 		" '',";		// 返却時の季節は0

	$sql .= 		" " . trim($post['appliReason']) . ",";

	$sql .= 		" '" . db_Escape(trim($headerData['CompID'])) . "',";

	$sql .= 		" " . trim($post['staffId']) . ",";
	$sql .= 		" '" . db_Escape(trim($headerData['StaffCode'])) . "',";
    $sql .=         " '" . db_Escape(trim($headerData['PersonName'])) . "',";
	$sql .= 		" '" . db_Escape(trim($post['memo'])) . "',";

	if ($isAllLoss == false) {
		$sql .= 		" " . STATUS_NOT_RETURN_ADMIT . ",";		// 未返却（承認済）は20
	}
	else {
		$sql .= 		" " . STATUS_LOSS_ADMIT . ",";		// 紛失（承認済）は34
	}
	$sql .= 		" 0,";
	if (trim($post['appliReason']) == APPLI_REASON_RETURN_RETIRE) {
		$sql .= 		" '" . db_Escape(trim($post['rentalEndDay'])) . "',";
	} else {
		$sql .= 	" NULL,";
	}
	$sql .= 		" " . DELETE_OFF . ",";				// DELの初期は0
	$sql .= 		" GETDATE(),";
	$sql .= 		" '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
	$sql .= 		" )";
	
	$isSuccess = db_Execute($dbConnect, $sql);

	// 実行結果が失敗の場合
	if ($isSuccess == false) {
		return false;
	}

	// 直近のシーケンスIDを取得
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" SCOPE_IDENTITY() as scope_identity";
	
	$result = db_Read($dbConnect, $sql);

	// 実行結果が失敗の場合
	if ($result == false || !isset($result[0]['scope_identity']) || $result[0]['scope_identity'] == '') {
		return false;
	}

	$orderId = $result[0]['scope_identity'];

	$staffId = trim($post['staffId']);

	// 退職・異動返却の場合はT_StaffのWithdrawalFlagを1に
	if (trim($post['appliReason']) == APPLI_REASON_RETURN_RETIRE) {
	
		// T_Staffの変更
		$sql  = "";
		$sql .= " UPDATE";
		$sql .= 	" T_Staff";
		$sql .= " SET";
		$sql .= 	" WithdrawalFlag = 1,";
		$sql .= 	" WithdrawalDay = GETDATE(),";
		$sql .= 	" UpdDay = GETDATE(),";
		$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= " WHERE";
		$sql .= 	" StaffID = '" . db_Escape($staffId) . "'";
		$sql .= " AND";
		$sql .= 	" CompID = '" . db_Escape(trim($headerData['CompID'])) . "'";

		$isSuccess = db_Execute($dbConnect, $sql);
	
		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}

	}

	$orderDetails = array();

	// 退職・異動返却の場合のみ返却未申請の情報を論理削除する
	if (trim($post['appliReason']) == APPLI_REASON_RETURN_RETIRE) {

		// T_Order_Detailsの変更
		$sql  = "";
		$sql .= " UPDATE";
		$sql .= 	" T_Order_Details";
		$sql .= " SET";
		$sql .= 	" Del = " . DELETE_ON . ",";
		$sql .= 	" UpdDay = GETDATE(),";
		$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= " WHERE";
		$sql .= 	" OrderID IN (";

		$sql .= 		" SELECT";
		$sql .= 			" OrderID";
		$sql .= 		" FROM";
		$sql .= 			" T_Order";
		$sql .= 		" WHERE";
		$sql .= 			" StaffCode = '" . db_Escape($headerData['StaffCode']) . "'";
		$sql .= 		" AND";
		$sql .= 			" CompID = '" . db_Escape(trim($headerData['CompID'])) . "'";
		$sql .= 		" AND";
		$sql .= 			" Del = " . DELETE_OFF;

		$sql .= 	" )";
		$sql .= " AND";
		$sql .= 	" Status = " .  STATUS_RETURN_NOT_APPLY;		// 返却未申請

		$isSuccess = db_Execute($dbConnect, $sql);
	
		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}

	}

	// T_Order_Detailsの情報を取得
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" tod.OrderDetID,";
	$sql .= 	" tod.Size,";
	$sql .= 	" tod.BarCd,";
	$sql .= 	" tod.IcTagCd,";
	$sql .= 	" mi.ItemID,";
	$sql .= 	" mi.ItemNo,";
	$sql .= 	" mi.ItemName,";
	$sql .= 	" msc.StockCD";
	$sql .= " FROM";
	$sql .= 	" T_Order_Details tod";
	$sql .= " INNER JOIN";
	$sql .= 	" M_Item mi";
	$sql .= " ON";
	$sql .= 	" tod.ItemID = mi.ItemID";
	$sql .= " AND";
	$sql .= 	" mi.Del = " . DELETE_OFF;
	$sql .= " INNER JOIN";
	$sql .= 	" M_StockCtrl msc";
	$sql .= " ON";
	$sql .= 	" mi.ItemNo = msc.ItemNo";
	$sql .= " AND";
	$sql .= 	" tod.Size = msc.Size";
	$sql .= " AND";
	$sql .= 	" msc.Del = " . DELETE_OFF;
	$sql .= " WHERE";
	$sql .= 	" tod.OrderDetId IN (" . db_Escape($orderDetIds) . ")";
	$sql .= " AND";
	$sql .= 	" tod.Del = " . DELETE_OFF;
	$sql .= " ORDER BY";
	$sql .= 	" mi.ItemID ASC";

	$orderDetails = db_Read($dbConnect, $sql);

	// T_Order_Detailsの登録
	$countOrderDetail = count($orderDetails);
	for ($i=0; $i<$countOrderDetail; $i++) {

		// 返却・紛失・返却未申請の判定
		if (isset($post['returnChk'][$orderDetails[$i]['OrderDetID']]) && $post['returnChk'][$orderDetails[$i]['OrderDetID']] == '1') {
			$notReturnFlag = 1;		// 返却
		}
		elseif (isset($post['lostChk'][$orderDetails[$i]['OrderDetID']]) && $post['lostChk'][$orderDetails[$i]['OrderDetID']] == '1') {
			$notReturnFlag = 2;		// 紛失
		}


		// 汚損・破損の判定
		$isBroken = false;
		if (isset($post['brokenChk'][$orderDetails[$i]['OrderDetID']]) && $post['brokenChk'][$orderDetails[$i]['OrderDetID']] == '1') {
			$isBroken = true;
		}

		// 初期化
		$orderDetailId = '';

		// T_Order_Detailsの登録
		$sql  = "";
		$sql .= " INSERT INTO";
		$sql .= 	" T_Order_Details";
		$sql .= 		" (";
		$sql .= 		" OrderID,";
		$sql .= 		" AppliNo,";
		$sql .= 		" AppliLNo,";
		$sql .= 		" ItemID,";
		$sql .= 		" ItemNo,";
		$sql .= 		" ItemName,";
		$sql .= 		" Size,";
		$sql .= 		" StockCd,";
		$sql .= 		" BarCd,";
		$sql .= 		" IcTagCd,";
		$sql .= 		" Status,";
		$sql .= 		" DamageCheck,";
		$sql .= 		" AppliDay,";
		$sql .= 		" MotoOrderDetID,";
		$sql .= 		" Del,";
		$sql .= 		" RegistDay,";
		$sql .= 		" RegistUser";
		$sql .= 		" )";
		$sql .= " VALUES";
		$sql .= 		" (";
		$sql .= 		" '" . db_Escape($orderId) ."',";
		$sql .= 		" '" . db_Escape($post['requestNo']) ."',";
		$sql .= 		" '" . db_Escape($i + 1) ."',";
		$sql .= 		" '" . db_Escape(trim($orderDetails[$i]['ItemID'])) ."',";
		$sql .= 		" '" . db_Escape(trim($orderDetails[$i]['ItemNo'])) ."',";
		$sql .= 		" '" . db_Escape(trim($orderDetails[$i]['ItemName'])) ."',";
		$sql .= 		" '" . db_Escape(trim($orderDetails[$i]['Size'])) ."',";
		$sql .= 		" '" . db_Escape(trim($orderDetails[$i]['StockCD'])) ."',";

		// BarCd
		if (trim($orderDetails[$i]['BarCd'])  != '') {
			$sql .= 		" '" . db_Escape(trim($orderDetails[$i]['BarCd'])) ."',";
		}
		else {
			$sql .= 		" NULL,";
		}

		// IcTagCd
		if (trim($orderDetails[$i]['IcTagCd'])  != '') {
			$sql .= 		" '" . db_Escape(trim($orderDetails[$i]['IcTagCd'])) ."',";
		}
		else {
			$sql .= 		" NULL,";
		}

		// Status
		switch ($notReturnFlag) {
			case '1':
				$sql .= 		" " . STATUS_NOT_RETURN_ADMIT . ",";	// 未返却（承認済）は20
				break;

			case '2':
				$sql .= 		" " . STATUS_LOSS_ADMIT . ",";			// 紛失（承認済）は34
				break;

			default:
				break;
		}

		// DamageCheck
		if ($isBroken  == true) {
			$sql .= 		" 1,";
		}
		else {
			$sql .= 		" 0,";
		}


		$sql .= 		" GETDATE(),";
		$sql .= 		" '" . db_Escape(trim($orderDetails[$i]['OrderDetID'])) ."',";
		$sql .= 		" " . DELETE_OFF . ","; 	// DELの初期は0
		$sql .= 		" GETDATE(),";
		$sql .= 		" '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= 		" );";

		$isSuccess = db_Execute($dbConnect, $sql);
	
		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}
	
		// 直近のシーケンスIDを取得
		$sql  = "";
		$sql .= " SELECT";
		$sql .= 	" SCOPE_IDENTITY() as scope_identity;";
		
		$result = db_Read($dbConnect, $sql);
	
		// 実行結果が失敗の場合
		if ($result == false || !isset($result[0]['scope_identity']) || $result[0]['scope_identity'] == '') {
			return false;
		}
	
		$orderDetailId = $result[0]['scope_identity'];

		// T_Staff_Detailsの変更
		$sql  = "";
		$sql .= " UPDATE";
		$sql .= 	" T_Staff_Details";
		$sql .= " SET";
		$sql .= 	" ReturnDetID = '" . db_Escape($orderDetailId) ."',";

		// status
		switch ($notReturnFlag) {
			case '1':
				$sql .= 	" Status = " . STATUS_NOT_RETURN_ADMIT . ",";		// 未返却（承認済）は20
				break;

			case '2':
				$sql .= 	" Status = " . STATUS_LOSS_ADMIT . ",";				// 紛失（承認済）は34
				break;

			default:
				break;
		}

		$sql .= 	" UpdDay = GETDATE(),";
		$sql .= 	" UpdUser = '" . db_Escape(trim($_SESSION['NAMECODE'])) . "'";
		$sql .= " WHERE";
		$sql .= 	" OrderDetID = '" . db_Escape($orderDetails[$i]['OrderDetID']) . "'";
		$sql .= " AND";
		$sql .= 	" StaffID = '" . db_Escape($staffId) . "'";

		$isSuccess = db_Execute($dbConnect, $sql);
	
		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}

	}

	return true;


}

/*
 * 返却申請メールを送信する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$orderId   => OrderID
 * 戻り値：true：変更成功 / false：変更失敗
 *
 * create 2007/04/25 H.Osugi
 *
 */
function sendMailShinsei($dbConnect, $orderId) {

	$filePath = '../../mail_template/';

	// 申請メールの件名と本文を取得
	$isSuccess = henpinShinseiMail($dbConnect, $orderId, $filePath, $subject, $message);

	if ($isSuccess == false) {
		return false;
	}

	$toAddr = MAIL_GROUP_3;
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

/*
 * 退職・異動アラートメールを送信する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$orderId   => OrderID
 *       ：$stockouts => 在庫切れ情報
 * 戻り値：true：変更成功 / false：変更失敗
 *
 * create 2007/06/27 H.Osugi
 *
 */
function sendMailStockOut($dbConnect, $orderId, $stockouts) {

	$filePath = '../../mail_template/';

	// 申請メールの件名と本文を取得
	$isSuccess = henpinStockOutMail($dbConnect, $orderId, $stockouts, $filePath, $subject, $message);

	if ($isSuccess == false) {
		return false;
	}

	$toAddr = MAIL_GROUP_5;
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

/*
 * 返却申請一覧情報を取得する
 * 引数  ：$dbConnect    => コネクションハンドラ
 *       ：$post         => POST値
 * 戻り値：$result       => 在庫切れ商品一覧情報
 *
 * create 2007/06/27 H.Osugi
 *
 */
function getStockOut($dbConnect, $post) {

	$stockouts = array();

	$staffId = '';
	if (isset($post['staffId']) && trim($post['staffId']) != '') {
		$staffId = trim($post['staffId']);
	}
	else {
		return $stockouts;
	}

		$sql  = "";
		$sql .= " SELECT";
		$sql .= 	" tod.AppliNo,";
		$sql .= 	" tod.ItemNo,";
		$sql .= 	" tod.ItemName,";
		$sql .= 	" tod.Size";
		$sql .= " FROM";
		$sql .= 	" T_Staff_Details tsd";
		$sql .= " INNER JOIN";
		$sql .= 	" T_Order_Details tod";
		$sql .= " ON";
		$sql .= 	" tod.OrderDetID = tsd.OrderDetID";
		$sql .= " AND";
		$sql .= 	" tod.Del = " . DELETE_OFF;
		$sql .= " WHERE";
		$sql .= 	" tsd.StaffID = '" . db_Escape($staffId) . "'";
		$sql .= " AND";
		$sql .= 	" tsd.Status IN ('" . db_Escape(STATUS_STOCKOUT) . "', '" . db_Escape(STATUS_ORDER) . "')";
		$sql .= " AND";
		$sql .= 	" tsd.ReturnDetID IS NULL";
		$sql .= " AND";
		$sql .= 	" tsd.Del = " . DELETE_OFF;
		$sql .= " ORDER BY";
		$sql .= 	" tod.AppliNo ASC";

		$stockouts = db_Read($dbConnect, $sql);

		if (!isset($stockouts) || count($stockouts) <= 0) {
			$stockouts = array();
			return $stockouts;
		}

		return $stockouts;

}

?><!DOCTYPE html>
<html>
  <head>
    <META http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <script src="//code.jquery.com/jquery-1.12.4.min.js"></script>
    <link href="/main.css" rel="stylesheet" type="text/css">
    <script language="JavaScript">
    <!--
    function MM_openBrWindow(theURL,winName,features) {
      window.open(theURL,winName,features);
      document.finForm.target = winName;
      document.finForm.action = theURL;
      document.finForm.submit();
      document.finForm.target = '_self';
      document.finForm.action = '../rireki/henpin_meisai.php';
    }
    // -->
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
        

        <div id="contents">
<?php if($selectedReason1) { ?>
          <h1>ユニフォーム返却結果　（退職・異動返却）</h1>
<?php } ?>
<?php if($selectedReason2) { ?>
          
          <h1>ユニフォーム返却結果　（その他返却）</h1>
          
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
<?php if(!$isAllLoss) { ?>
          <h3>◆下記の内容で<span style="color:red">返却申請</span>を受付ました。</h3>
<?php } ?>
<?php if($isAllLoss) { ?>
          
          <h3>◆下記の内容で<span style="color:red">紛失申請</span>を受付ました。</h3>
          
<?php } ?>
          <table width="640" border="0" class="tb_1" cellpadding="0" cellspacing="3">
            <tr>
              <th align="center" width="70">選択</th>
              <th align="center" width="250">アイテム名</th>
              <th align="center" width="100">サイズ</th>
              <th align="center" width="120">単品番号</th>
              <th align="center" width="100">汚損・破損</th>
            </tr>
<?php for ($i1_returns=0; $i1_returns<count($returns); $i1_returns++) { ?>
<?php if($returns[$i1_returns]['isCheckedReturn']) { ?>
<?php if($returns[$i1_returns]['isCheckedBroken']) { ?>
            
            <tr height="20" class="chakuyo_2">
            
<?php } ?>
<?php if(!$returns[$i1_returns]['isCheckedBroken']) { ?>
            <tr height="20">
<?php } ?>
              <td class="line2" align="center">返却</td>
<?php } ?>
<?php if(!$returns[$i1_returns]['isCheckedReturn']) { ?>
            
            <tr height="20" class="chakuyo_1">
              <td class="line2" align="center"><span style="color:Teal">紛失</span></td>
            
<?php } ?>
              <td class="line2" align="left"><?php isset($returns[$i1_returns]['ItemName']) ? print($returns[$i1_returns]['ItemName']) : print('&#123;returns.ItemName&#125;'); ?></td>
              <td class="line2" align="center"><?php isset($returns[$i1_returns]['Size']) ? print($returns[$i1_returns]['Size']) : print('&#123;returns.Size&#125;'); ?></td>
              <td class="line2" align="center">
<?php if($returns[$i1_returns]['isEmptyBarCd']) { ?>
                &nbsp;
<?php } ?>
<?php if(!$returns[$i1_returns]['isEmptyBarCd']) { ?>
                <?php isset($returns[$i1_returns]['BarCd']) ? print($returns[$i1_returns]['BarCd']) : print('&#123;returns.BarCd&#125;'); ?>
<?php } ?>
              </td>
              <td class="line2" align="center">
<?php if($returns[$i1_returns]['isCheckedBroken']) { ?>
                
                有り
                
<?php } ?>
<?php if(!$returns[$i1_returns]['isCheckedBroken']) { ?>
                &nbsp;
<?php } ?>
              </td>
            </tr>
<?php } ?>
          </table>
          <br><br>
<?php if($hasReturn) { ?>
          <form action="../rireki/henpin_meisai.php" name="finForm" method="post">
            <table border="0" cellspacing="0" cellpadding="0" bordercolor="#FF0000" width="640">
              <tr>
                <td align="left"><img src="../img/attention.gif" alt="ATTENTION" border="0"></td>
              </tr>
            </table>
            <table border="2" cellspacing="0" cellpadding="3" bordercolor="#FF0000" width="640">
              <tr>
                <td align="center">
                  <table border="0" cellspacing="0" cellpadding="0" width="600">

                    <tr>
                      <td colspan="3">&nbsp;</td>
                    </tr>
                    <tr>
                      <td width="400" align="left" style="border-bottom: 1px solid gray;">
                        商品の返却時には、必ず「返却申請明細」をプリントアウトし、<br>
                        返却商品に添付してご返送下さい。<br>
                      </td>
                      <td align="center" width="10"  style="border-bottom: 1px solid gray;">&nbsp</td>
                      <td align="center" width="190">
                        <a href="#" onclick="document.finForm.submit(); return false;"><img src="../img/hyouji.gif" alt="返却申請明細の表示" border="0"></a>
                      </td>
                    </tr>

<?php if($hasBroken) { ?>
<!--{
                    <tr>
                      <td colspan="3">&nbsp;</td>
                    </tr>
                    <tr>
                      <td align="left" style="border-bottom: 1px solid gray;">
                        「汚損・破損届」をプリントアウトし、提出して下さい。
                      </td>
                      <td align="center" style="border-bottom: 1px solid gray;">&nbsp</td>
                      <td align="center">
                        <a href="javascript:MM_openBrWindow('../broken_dsp_frame.php?orderId=<?php isset($orderId) ? print($orderId) : print('&#123;orderId&#125;'); ?>','brokenDsp','resizable=yes,scrollbars=yes,width=720,height=950')"><img src="../img/oson_print.gif" alt="破損・汚損届の印刷" border="0"></a>
                      </td>
                    </tr>
}-->
<?php } ?>
<?php if($hasLoss) { ?>
<!--{
                    <tr>
                      <td colspan="3">&nbsp;</td>
                    </tr>
                    <tr>
                      <td align="left" style="border-bottom: 1px solid gray;">
                        「紛失届」をプリントアウトし、提出して下さい。
                      </td>
                      <td align="center" style="border-bottom: 1px solid gray;">&nbsp</td>
                      <td align="center">
                        <a href="javascript:MM_openBrWindow('../lost_dsp_frame.php?orderId=<?php isset($orderId) ? print($orderId) : print('&#123;orderId&#125;'); ?>','lostDsp','resizable=yes,scrollbars=yes,width=720,height=950')"><img src="../img/lost_print.gif" alt="紛失届の印刷" border="0"></a><br>
                      </td>
                    </tr>
}-->
<?php } ?>
                    <tr>
                      <td colspan="3">&nbsp;</td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
            <br>
            <input type="hidden" name="orderId" value="<?php isset($orderId) ? print($orderId) : print('&#123;orderId&#125;'); ?>">
            

<?php for ($i1_hiddenHtml=0; $i1_hiddenHtml<count($hiddenHtml); $i1_hiddenHtml++) { ?>
        <input type="hidden" value="<?php isset($hiddenHtml[$i1_hiddenHtml]['value']) ? print($hiddenHtml[$i1_hiddenHtml]['value']) : print('&#123;hiddenHtml.value&#125;'); ?>" name="<?php isset($hiddenHtml[$i1_hiddenHtml]['name']) ? print($hiddenHtml[$i1_hiddenHtml]['name']) : print('&#123;hiddenHtml.name&#125;'); ?>">
<?php } ?>
          </form>
<?php } ?>
          

        </div>
        <br><br><br>
      </div>
    </div>
  </body>
</html>

<?php
/*
 * ログイン画面
 * login.src.php
 *
 * create 2007/03/13 H.Osugi
 *
 *
 */
// 各種モジュールの取得
include_once('../include/define.php');				// 定数定義
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


/* ../include/checkData.php start */

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


/* ../include/checkData.php end */


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


/* ../error_message/indexError.php start */

/*
 * index画面でのエラーメッセージ一覧
 * index_error.php
 *
 * create 2007/03/14 H.Osugi
 *
 *
 */

// ログイン画面(top.php)
$indexErrors = array();
$indexErrors['001'] = '入力されたID,パスワードではご利用できません。';

$indexErrors['002'] = 'パスワードが変更されました。再度ログインしてください。';


/* ../error_message/indexError.php end */


/* ./login.val.php start */

/*
 * エラー判定処理
 * login.val.php
 *
 * create 2007/03/13 H.Osugi
 *
 *
 */

/*
 * 機能  ：エラー判定を行う
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$post      => POSTデータ
 * 戻り値：なし
 *
 * create 2007/03/13 H.Osugi
 *
 */
function validatePostData($dbConnect, $post) {

	// エラー判定フラグ
	$isError = false;

	// ログインIDが存在しなければ初期化
	if (!isset($post['loginId'])) {
		$post['loginId'] = '';
	}

	// デモサイト判定追加
	// uesugi 081119
	//デモフラグがONの場合
	if(SET_DEMO_SITE){
		//先頭4文字切り出し
		$demoStr = substr($post['loginId'],0,4);
		if($demoStr == "demo" || $demoStr == "DEMO"){
			$loginLen = strlen($post['loginId']);
			$post['loginId'] = substr($post['loginId'],4,$loginLen - 4);
		}else{
			//先頭4文字が'demo'じゃない場合、初期化
			$post['loginId'] = '';
		}
	}

	// ログインIDの判定
	$result = checkData(trim($post['loginId']), 'HalfWidth', true);

	// エラーが発生したならば、エラーメッセージを取得
	switch ($result) {

		// 空白ならば
		case 'empty':
			$isError = true;
			break;
			
		// 半角以外の文字ならば
		case 'mode':
			$isError = true;
			break;

	}

	// パスワードが存在しなければ初期化
	if (!isset($post['passWd'])) {
		$post['passWd'] = '';
	}

	// パスワードの判定
	$result = checkData(trim($post['passWd']), 'HalfWidth', true);

	// エラーが発生したならば、エラーメッセージを取得
	switch ($result) {

		// 空白ならば
		case 'empty':
			$isError = true;
			break;
			
		// 半角以外の文字ならば
		case 'mode':
			$isError = true;
			break;

	}

	// ここまでにエラーがなければDBに存在するかの判定を行う
	if ($isError == false) {

		// SESSIONに保存する値を取得
		$sql  = "";
		$sql .= " SELECT";
		$sql .= 	" mu.UserID,";
		$sql .= 	" mu.Name,";
		$sql .= 	" mu.UserLvl,";
		$sql .= 	" mu.AdminLvl,";
		// 伊藤忠、ユニコ、ソウシンク、伊藤忠ロジでログインした際はシステム管理者
		// ＪＡＦ本社、ＪＡＦサービスとは切り分けするために設けた区分
		$sql .= 	" mu.AttrKbn,";
		$sql .= 	" mc.CompID,";
		$sql .= 	" mc.CompCd,";
        $sql .=     " mc.CompName,";
		$sql .= 	" mc.CorpCd,";
        $sql .=     " mc.CorpName,";
		$sql .= 	" mc.HonbuCd,";
        $sql .=     " mc.HonbuName,";
		$sql .= 	" mc.ShibuCd,";
        $sql .=     " mc.ShibuName,";
        $sql .=     " mc.CompKind,";
		$sql .= 	" mc.ShopFlag";
		$sql .= " FROM";
		$sql .= 	" M_User mu";
		$sql .= " INNER JOIN";
		$sql .= 	" M_Comp mc";
		$sql .= " ON";
		$sql .= 	" mu.CompID = mc.CompID";
		$sql .= " WHERE";
		$sql .= 	" convert(binary(21), rtrim(mu.NameCd)) = convert(binary(21), '" . db_Escape($post['loginId']) . "')";
		$sql .= " AND";
		$sql .= 	" convert(binary(21), rtrim(mu.PassWd)) = convert(binary(21), '" . db_Escape($post['passWd']) . "')";
		$sql .= " AND";
		$sql .= 	" mu.Del = " . DELETE_OFF;
		$sql .= " AND";
		$sql .= 	" mc.Del = " . DELETE_OFF;
		
		$result = db_Read($dbConnect, $sql);
		
		// 該当データが取得できない場合はエラー
		if (!isset($result) || count($result) <= 0) {
			$isError = true;
		}
		// 取得できた場合はSESSIONに保存
		else {

			// ユーザID
			if (isset($post['loginId'])) {
				$_SESSION['NAMECODE'] = trim($post['loginId']);
				 setcookie("userId", trim($post['loginId']));
			}

			// パスワード
			if (isset($post['passWd'])) {
				$_SESSION['PASSWORD'] = trim($post['passWd']);
				 setcookie("pass", md5(trim($post['passWd'])));
			}

			// ユーザID（シーケンスID）
			if (isset($result[0]['UserID'])) {
				$_SESSION['USERID'] = trim($result[0]['UserID']);
			}

			// ユーザ名
			if (isset($result[0]['Name'])) {
				$_SESSION['USERNAME'] = trim($result[0]['Name']);
			}

			// 権限レベル
			if (isset($result[0]['UserLvl'])) {
				$_SESSION['USERLVL'] = trim($result[0]['UserLvl']);
			}

			// 管理レベル（2:max権限管理、1:JAF管理権限管理）
			if (isset($result[0]['AdminLvl'])) {
				$_SESSION['ADMINLVL'] = trim($result[0]['AdminLvl']);
			}

			// 属性区分（1:「伊藤忠」／1以外は「ＪＡＦ」）
			if (isset($result[0]['AttrKbn'])) {
				$_SESSION['ATTRKBN'] = trim($result[0]['AttrKbn']);
			}

			// 店舗ID
			if (isset($result[0]['CompID'])) {
				$_SESSION['COMPID'] = trim($result[0]['CompID']);
			}

			// 店舗コード
			if (isset($result[0]['CompCd'])) {
				$_SESSION['COMPCD'] = trim($result[0]['CompCd']);
			}

			// 店舗名
			if (isset($result[0]['CompName'])) {
				$_SESSION['COMPNAME'] = trim($result[0]['CompName']);
			}

			// 会社コード
			if (isset($result[0]['CorpCd'])) {
				$_SESSION['CORPCD'] = trim($result[0]['CorpCd']);
			}

			// 会社名
			if (isset($result[0]['CorpName'])) {
				$_SESSION['CORPNAME'] = trim($result[0]['CorpName']);
			}

			// 本部コード
			if (isset($result[0]['HonbuCd'])) {
				$_SESSION['HONBUCD'] = trim($result[0]['HonbuCd']);
			}

			// 本部名
			if (isset($result[0]['HonbuName'])) {
				$_SESSION['HONBUNAME'] = trim($result[0]['HonbuName']);
			}

			// 支部コード
			if (isset($result[0]['ShibuCd'])) {
				$_SESSION['SHIBUCD'] = trim($result[0]['ShibuCd']);
			}

			// 支部名
			if (isset($result[0]['ShibuName'])) {
				$_SESSION['SHIBUNAME'] = trim($result[0]['ShibuName']);
			}

            // 特殊店舗フラグ
            if (isset($result[0]['CompKind'])) {
                $_SESSION['COMPKIND'] = trim($result[0]['CompKind']);
            }

            // 特殊店舗フラグ
            if (isset($result[0]['ShopFlag'])) {
                $_SESSION['SHOPFLAG'] = trim($result[0]['ShopFlag']);
            }

			// ログイン情報をログに残す
			$fp = fopen(LOGIN_FILE_PATH, 'a');

			//バッファを0に指定（排他制御の保証）
			stream_set_write_buffer($fp,0);

			//ファイルのロック （排他制御）
			flock($fp, LOCK_EX);

			$log = date('Y/m/d H:i:s') . ',' . $_SESSION['NAMECODE'] . ',' . $_SESSION['COMPID'] . ',' . $_SESSION['COMPCD'] . ',' . $_SESSION['COMPNAME'] . "\n";

			// 書き込む文字列の文字コードをshift_Jisに変換
			$log = mb_convert_encoding($log, 'SJIS', 'auto');

			//ファイルに書きこみ（時間 / ログインID / ショップID / ショップコード : ショップ名）
			fwrite($fp, $log);

			//ロックの開放
			flock($fp, LOCK_UN);

			//ファイルのクローズ
			fclose($fp);

/*
            // 初期設定パスワードの場合は、パスワード変更画面に遷移
            if ($post['passWd'] == SYSTEM_DEFAULT_PASSWORD) {
                header('Location: ' . './change_password.php');
                exit;
            }
*/
		}
	}

	return $isError;

}


/* ./login.val.php end */



// 初期値の設定
$isLogin = false;		// 未ログイン状態
$homeUrl = HOME_URL;	// サイトトップのURL

// SESSIONの開始
session_start();
session_cache_limiter('private');

// SESSION情報を持っていたらSESSIONを開放
if (isset($_SESSION['NAMECODE']) || isset($_SESSION['PASSWORD'])) {

	// SESSION変数をすべて解除
	$_SESSION = array();

	// クライアントのCOOKIEの値も削除
	setcookie("PHPSESSID", '', time() - 3600, '/');
	setcookie("userId", '', time() - 3600, '/');
	setcookie("pass", '', time() - 3600, '/');

	// SESSIONの破棄
	session_destroy();

}

// ログイン判定を行う
if (isset($_POST['loginFlg'])) {

	// エラー判定処理（エラーが無ければSESSIONにログイン情報を保存する）
	$isError = validatePostData($dbConnect, $_POST);

	// エラーが無かった場合はTOP画面へ遷移
	if ($isError == false) {
		// 初期パスワードの場合は、パスワード変更画面へ遷移
		// 09/04/08 uesugi
		if($_SESSION['PASSWORD'] == SYSTEM_DEFAULT_PASSWORD){
			$_SESSION['FROM_LOGIN'] = "1";
			redirectPost("./change_password.php", "");
		}
		// パスワード更新日をチェック
		// 09/04/06 uesugi
		if(!checkChangePassDay($dbConnect)){
			$_SESSION['FROM_LOGIN'] = "1";
			redirectPost("./change_password.php", "");
		}

		header('Location: ' . './top.php');
		exit;
	}

	// エラーメッセージ
	$errorMessage = $indexErrors['001'];

}

if (isset($_GET['changePass']) && $_GET['changePass'] == COMMON_FLAG_ON) {
    // 画面にメッセージを表示
    $isError = true;
    $errorMessage = $indexErrors['002'];

}
/*
 * パスワード更新日をチェック
 * 引数  ：更新日以上の日数経過 false 更新日以内 true
 * 戻り値：なし
 */
function checkChangePassDay($dbConnect) {
	// 期限が設定されていない場合は無期限
	$exp_days = CHANGE_PASS_EXPDAY;
	if(!isset($exp_days) || $exp_days =="" || !is_numeric($exp_days)){
		return true;
	}else{
		// 現在の日付より、期限開始日を求める
		$exp_startday = mktime (0, 0, 0, date("m"), date("d"),  date("y")) - 86400 * $exp_days;
		$exp_startday = date('y/m/d', $exp_startday);

		// 期限開始日より前に更新されているかをチェック
		$sql  = "";
		$sql .= " SELECT";
		$sql .= 	" count(*) As OrderCount";
		$sql .= " FROM";
		$sql .= 	" M_User";
		$sql .= " WHERE";
		$sql .= 	" convert(binary(21), rtrim(NameCd)) = convert(binary(21), '" . db_Escape($_SESSION['NAMECODE']) . "')";
		$sql .= " AND";
		$sql .=	" (PassWdUpdDay IS Null OR CONVERT(char, PassWdUpdDay, 11) < '" . db_Escape($exp_startday) . "')";
		$sql .= " AND";
		$sql .= 	" Del = " . DELETE_OFF;
		$result = db_Read($dbConnect, $sql);
//var_dump($sql);die;
		if (!isset($result[0]['OrderCount']) || $result[0]['OrderCount'] >= 1) {
			return false;
		}else{
			return true;
		}
	}
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
          <br><br>
          <form method="post" action="login.php" name="loginForm">
            <table border="0" cellpadding="0" cellspacing="0">
              <tr height="40">
                <td nowrap>ログインID：&nbsp;</td>
                <td><input type="text" name="loginId" maxlength="16" style="width:200px;"></td>
              </tr>
              <tr height="40">
                <td nowrap>パスワード：&nbsp;</td>
                <td><input type="password" name="passWd" maxlength="16" style="width:200px;"></td>
              </tr>
              <tr height="30">
                <td align="center" colspan="2">
                  <div class="bot">
                    <a href="#" onclick="document.loginForm.loginFlg.value=1; document.loginForm.submit(); return false;"><img src="./img/ok.gif" width="112" height="32" border="0"></a>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="#" onclick="document.loginForm.loginId.value=''; document.loginForm.passWd.value='';"><img src="./img/cancel.gif" width="112" height="32" border="0"></a></div>
               </td>
              </tr>
            </table>
            <input type="hidden" name="encodeHint" value="京">
            <input type="hidden" name="loginFlg" value="">
          </form>
<?php if($isError) { ?>
          <div class="ftbold">
            
            <?php isset($errorMessage) ? print($errorMessage) : print('&#123;errorMessage&#125;'); ?>
            
          </div>
<?php } ?>
        </div>
        <br><br><br>
      </div>
    </div>
  </body>
</html>

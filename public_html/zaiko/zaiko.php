<?php
/*
 * 在庫照会画面
 * zaiko.src.php
 *
 * create 2007/05/22 H.Osugi
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



// 初期設定
$isMenuAdmin = true;	// 管理機能のメニューをアクティブに

// 変数の初期化 ここから ******************************************************
$items       = array();
// 変数の初期化 ここまで ******************************************************

// 管理者権限が無ければトップに強制遷移
if ($isLevelAdmin == false) {

	$returnUrl             = HOME_URL . 'top.php';
	
	// TOP画面に強制遷移
	redirectPost($returnUrl, $hiddens);

} 

// 表示する在庫一覧を取得
$stocks = getStock($dbConnect);
// 表示する値の成型 ここまで +++++++++++++++++++++++++++++++++++++++++++++++++++

/*
 * 在庫情報を取得する
 * 引数  ：$dbConnect      => コネクションハンドラ
 * 戻り値：$stocks         => 在庫情報
 *
 * create 2007/05/22 H.Osugi
 *
 */
function getStock($dbConnect) {

	// 在庫情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" msc.Size,";
	$sql .= 	" mi.ItemName,";
	$sql .= 	" mi.ItemNo,";
	$sql .= 	" ts.HikiateQty,";			// 新品・論理在庫
	$sql .= 	" ts.JitsuStock,";			// 新品・実在庫
	$sql .= 	" ts.OldHikiateQty,";		// 中古（公益社）・論理在庫
	$sql .= 	" ts.OldJitsuStock";		// 中古（公益社）・実在庫
	$sql .= " FROM";
	$sql .= 	" M_StockCtrl msc";
	$sql .= " INNER JOIN";
	$sql .= 	" M_Item mi";
	$sql .= " ON";
	$sql .= 	" msc.ItemNo = mi.ItemNo";
	$sql .= " AND";
	$sql .= 	" mi.Del = " . DELETE_OFF;
	$sql .= " LEFT JOIN";
	$sql .= 	" T_Stock ts";
	$sql .= " ON";
	$sql .= 	" msc.StockCD = ts.StockCD";
	$sql .= " AND";
	$sql .= 	" ts.Del = " . DELETE_OFF;
	$sql .= " WHERE";
	$sql .= 	" msc.Del = " . DELETE_OFF;
	$sql .= " ORDER BY";
	$sql .= 	" msc.StockCD ASC";
//var_dump($sql);
	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	$resultCount = count($result);
	$itemNo = '';
	$stocks = array();
	$j = -1;
	$k = 0;

	for ($i=0; $i<$resultCount; $i++) {

		if ($itemNo != $result[$i]['ItemNo']) {

			$j++;

			$stocks[$j]['ItemNo']   = castHtmlEntity($result[$i]['ItemNo']);
			$stocks[$j]['ItemName'] = castHtmlEntity($result[$i]['ItemName']);
			
			$k = 0;

		}
		$itemNo = $result[$i]['ItemNo'];

		// サイズ
		$stocks[$j]['sizes'][$k]['Size']       = castHtmlEntity($result[$i]['Size']);

		$stocks[$j]['sizes'][$k]['HikiateQty'] = 0;
		//if ($result[$i]['HikiateQty'] != '') {
		if ($result[$i]['HikiateQty'] != '' && $result[$i]['HikiateQty'] > 0) {
			$stocks[$j]['sizes'][$k]['HikiateQty'] = $result[$i]['HikiateQty'];
		}
		$stocks[$j]['sizes'][$k]['OldHikiateQty'] = 0;
		//if ($result[$i]['OldHikiateQty'] != '') {
		if ($result[$i]['OldHikiateQty'] != '' && $result[$i]['OldHikiateQty'] > 0) {
			$stocks[$j]['sizes'][$k]['OldHikiateQty'] = $result[$i]['OldHikiateQty'];
		}

		$stocks[$j]['sizes'][$k]['isEmptySize'] = false;

		$k++;

	}

	$countStocks = count($stocks);
	//var_dump("countStocks:" . $countStocks);
	for ($i=0; $i<$countStocks; $i++) {
		$countSizes = count($stocks[$i]['sizes']);
		if ($countSizes <= 12) {
			$stocks[$i]['multiline']  = false;
            $stocks[$i]['2line']      = false;
            $stocks[$i]['3line']      = false;
            $stocks[$i]['4line']      = false;
            $stocks[$i]['5line']      = false;
            $stocks[$i]['6line']      = false;
            $stocks[$i]['7line']      = false;
            $stocks[$i]['8line']      = false;
            $stocks[$i]['rowspan']    = 2;
			for ($j=0; $j<$countSizes; $j++) {
				$stocks[$i]['sizeline1'][$j]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
				$stocks[$i]['sizeline1'][$j]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
				$stocks[$i]['sizeline1'][$j]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
				$stocks[$i]['sizeline1'][$j]['isEmptySize'] = false;
			}
			for ($j=$countSizes; $j<12; $j++) {
				$stocks[$i]['sizeline1'][$j]['isEmptySize'] = true;
			}
		} elseif ($countSizes <= 24) {
//		var_dump("aaaaaaaaaaaaaaaaaaaaaaaaaa");
			$stocks[$i]['multiline']  = true;
            $stocks[$i]['2line']      = true;
            $stocks[$i]['3line']      = false;
            $stocks[$i]['4line']      = false;
            $stocks[$i]['5line']      = false;
            $stocks[$i]['6line']      = false;
            $stocks[$i]['7line']      = false;
            $stocks[$i]['8line']      = false;
            $stocks[$i]['rowspan']    = 4;
			for ($j=0; $j<12; $j++) {
				$stocks[$i]['sizeline1'][$j]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
				$stocks[$i]['sizeline1'][$j]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
				$stocks[$i]['sizeline1'][$j]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
				$stocks[$i]['sizeline1'][$j]['isEmptySize'] = false;
			}
			for ($j=12; $j<$countSizes; $j++) {
				$stocks[$i]['sizeline2'][$j-12]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
				$stocks[$i]['sizeline2'][$j-12]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
				$stocks[$i]['sizeline2'][$j-12]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
				$stocks[$i]['sizeline2'][$j-12]['isEmptySize'] = false;
			}
			for ($j=$countSizes-12; $j<12; $j++) {
				$stocks[$i]['sizeline2'][$j]['isEmptySize'] = true;
			}
        } elseif ($countSizes <= 36) {
            $stocks[$i]['multiline']  = true;
            $stocks[$i]['2line']      = true;
            $stocks[$i]['3line']      = true;
            $stocks[$i]['4line']      = false;
            $stocks[$i]['5line']      = false;
            $stocks[$i]['6line']      = false;
            $stocks[$i]['7line']      = false;
            $stocks[$i]['8line']      = false;
            $stocks[$i]['rowspan']    = 6;
            for ($j=0; $j<12; $j++) {
                $stocks[$i]['sizeline1'][$j]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline1'][$j]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline1'][$j]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline1'][$j]['isEmptySize'] = false;
            }
            for ($j=12; $j<24; $j++) {
                $stocks[$i]['sizeline2'][$j-12]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline2'][$j-12]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline2'][$j-12]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline2'][$j-12]['isEmptySize'] = false;
            }
            for ($j=24; $j<$countSizes; $j++) {
                $stocks[$i]['sizeline3'][$j-24]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline3'][$j-24]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline3'][$j-24]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline3'][$j-24]['isEmptySize'] = false;
            }
            for ($j=$countSizes-24; $j<12; $j++) {
                $stocks[$i]['sizeline3'][$j]['isEmptySize'] = true;
            }
        } elseif ($countSizes <= 48) {
            $stocks[$i]['multiline']  = true;
            $stocks[$i]['2line']      = true;
            $stocks[$i]['3line']      = true;
            $stocks[$i]['4line']      = true;
            $stocks[$i]['5line']      = false;
            $stocks[$i]['6line']      = false;
            $stocks[$i]['7line']      = false;
            $stocks[$i]['8line']      = false;
            $stocks[$i]['rowspan']    = 8;
            for ($j=0; $j<12; $j++) {
                $stocks[$i]['sizeline1'][$j]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline1'][$j]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline1'][$j]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline1'][$j]['isEmptySize'] = false;
            }
            for ($j=12; $j<24; $j++) {
                $stocks[$i]['sizeline2'][$j-12]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline2'][$j-12]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline2'][$j-12]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline2'][$j-12]['isEmptySize'] = false;
            }
            for ($j=24; $j<36; $j++) {
                $stocks[$i]['sizeline3'][$j-24]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline3'][$j-24]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline3'][$j-24]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline3'][$j-24]['isEmptySize'] = false;
            }
            for ($j=36; $j<$countSizes; $j++) {
                $stocks[$i]['sizeline4'][$j-36]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline4'][$j-36]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline4'][$j-36]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline4'][$j-36]['isEmptySize'] = false;
            }
            for ($j=$countSizes-36; $j<12; $j++) {
                $stocks[$i]['sizeline4'][$j]['isEmptySize'] = true;
            }
        } elseif ($countSizes <= 60) {
            $stocks[$i]['multiline']  = true;
            $stocks[$i]['2line']      = true;
            $stocks[$i]['3line']      = true;
            $stocks[$i]['4line']      = true;
            $stocks[$i]['5line']      = true;
            $stocks[$i]['6line']      = false;
            $stocks[$i]['7line']      = false;
            $stocks[$i]['8line']      = false;
            $stocks[$i]['rowspan']    = 10;
            for ($j=0; $j<12; $j++) {
                $stocks[$i]['sizeline1'][$j]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline1'][$j]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline1'][$j]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline1'][$j]['isEmptySize'] = false;
            }
            for ($j=12; $j<24; $j++) {
                $stocks[$i]['sizeline2'][$j-12]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline2'][$j-12]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline2'][$j-12]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline2'][$j-12]['isEmptySize'] = false;
            }
            for ($j=24; $j<36; $j++) {
                $stocks[$i]['sizeline3'][$j-24]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline3'][$j-24]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline3'][$j-24]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline3'][$j-24]['isEmptySize'] = false;
            }
            for ($j=36; $j<48; $j++) {
                $stocks[$i]['sizeline4'][$j-36]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline4'][$j-36]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline4'][$j-36]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline4'][$j-36]['isEmptySize'] = false;
            }
            for ($j=48; $j<$countSizes; $j++) {
                $stocks[$i]['sizeline5'][$j-48]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline5'][$j-48]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline5'][$j-48]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline5'][$j-48]['isEmptySize'] = false;
            }
            for ($j=$countSizes-48; $j<12; $j++) {
                $stocks[$i]['sizeline5'][$j]['isEmptySize'] = true;
            }
        } elseif ($countSizes <= 72) {
            $stocks[$i]['multiline']  = true;
            $stocks[$i]['2line']      = true;
            $stocks[$i]['3line']      = true;
            $stocks[$i]['4line']      = true;
            $stocks[$i]['5line']      = true;
            $stocks[$i]['6line']      = true;
            $stocks[$i]['7line']      = false;
            $stocks[$i]['8line']      = false;
            $stocks[$i]['rowspan']    = 12;
           for ($j=0; $j<12; $j++) {
                $stocks[$i]['sizeline1'][$j]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline1'][$j]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline1'][$j]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline1'][$j]['isEmptySize'] = false;
            }
            for ($j=12; $j<24; $j++) {
                $stocks[$i]['sizeline2'][$j-12]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline2'][$j-12]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline2'][$j-12]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline2'][$j-12]['isEmptySize'] = false;
            }
            for ($j=24; $j<36; $j++) {
                $stocks[$i]['sizeline3'][$j-24]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline3'][$j-24]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline3'][$j-24]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline3'][$j-24]['isEmptySize'] = false;
            }
            for ($j=36; $j<48; $j++) {
                $stocks[$i]['sizeline4'][$j-36]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline4'][$j-36]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline4'][$j-36]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline4'][$j-36]['isEmptySize'] = false;
            }
            for ($j=48; $j<60; $j++) {
                $stocks[$i]['sizeline5'][$j-48]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline5'][$j-48]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline5'][$j-48]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline5'][$j-48]['isEmptySize'] = false;
            }
            for ($j=60; $j<$countSizes; $j++) {
                $stocks[$i]['sizeline6'][$j-60]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline6'][$j-60]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline6'][$j-60]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline6'][$j-60]['isEmptySize'] = false;
            }
            for ($j=$countSizes-60; $j<12; $j++) {
                $stocks[$i]['sizeline6'][$j]['isEmptySize'] = true;
            }
        } elseif ($countSizes <= 84) {
            $stocks[$i]['multiline']  = true;
            $stocks[$i]['2line']      = true;
            $stocks[$i]['3line']      = true;
            $stocks[$i]['4line']      = true;
            $stocks[$i]['5line']      = true;
            $stocks[$i]['6line']      = true;
            $stocks[$i]['7line']      = true;
            $stocks[$i]['8line']      = false;
            $stocks[$i]['rowspan']    = 14;
            for ($j=0; $j<12; $j++) {
                $stocks[$i]['sizeline1'][$j]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline1'][$j]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline1'][$j]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline1'][$j]['isEmptySize'] = false;
            }
            for ($j=12; $j<24; $j++) {
                $stocks[$i]['sizeline2'][$j-12]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline2'][$j-12]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline2'][$j-12]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline2'][$j-12]['isEmptySize'] = false;
            }
            for ($j=24; $j<36; $j++) {
                $stocks[$i]['sizeline3'][$j-24]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline3'][$j-24]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline3'][$j-24]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline3'][$j-24]['isEmptySize'] = false;
            }
            for ($j=36; $j<48; $j++) {
                $stocks[$i]['sizeline4'][$j-36]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline4'][$j-36]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline4'][$j-36]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline4'][$j-36]['isEmptySize'] = false;
            }
            for ($j=48; $j<60; $j++) {
                $stocks[$i]['sizeline5'][$j-48]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline5'][$j-48]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline5'][$j-48]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline5'][$j-48]['isEmptySize'] = false;
            }
            for ($j=60; $j<72; $j++) {
                $stocks[$i]['sizeline6'][$j-60]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline6'][$j-60]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline6'][$j-60]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline6'][$j-60]['isEmptySize'] = false;
            }
            for ($j=72; $j<$countSizes; $j++) {
                $stocks[$i]['sizeline7'][$j-72]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline7'][$j-72]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline7'][$j-72]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline7'][$j-72]['isEmptySize'] = false;
            }
            for ($j=$countSizes-72; $j<12; $j++) {
                $stocks[$i]['sizeline7'][$j]['isEmptySize'] = true;
            }
        } elseif ($countSizes <= 96) {
            $stocks[$i]['multiline']  = true;
            $stocks[$i]['2line']      = true;
            $stocks[$i]['3line']      = true;
            $stocks[$i]['4line']      = true;
            $stocks[$i]['5line']      = true;
            $stocks[$i]['6line']      = true;
            $stocks[$i]['7line']      = true;
            $stocks[$i]['8line']      = true;
            $stocks[$i]['rowspan']    = 16;
            for ($j=0; $j<12; $j++) {
                $stocks[$i]['sizeline1'][$j]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline1'][$j]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline1'][$j]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline1'][$j]['isEmptySize'] = false;
            }
            for ($j=12; $j<24; $j++) {
                $stocks[$i]['sizeline2'][$j-12]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline2'][$j-12]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline2'][$j-12]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline2'][$j-12]['isEmptySize'] = false;
            }
            for ($j=24; $j<36; $j++) {
                $stocks[$i]['sizeline3'][$j-24]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline3'][$j-24]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline3'][$j-24]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline3'][$j-24]['isEmptySize'] = false;
            }
            for ($j=36; $j<48; $j++) {
                $stocks[$i]['sizeline4'][$j-36]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline4'][$j-36]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline4'][$j-36]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline4'][$j-36]['isEmptySize'] = false;
            }
            for ($j=48; $j<60; $j++) {
                $stocks[$i]['sizeline5'][$j-48]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline5'][$j-48]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline5'][$j-48]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline5'][$j-48]['isEmptySize'] = false;
            }
            for ($j=60; $j<72; $j++) {
                $stocks[$i]['sizeline6'][$j-60]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline6'][$j-60]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline6'][$j-60]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline6'][$j-60]['isEmptySize'] = false;
            }
            for ($j=72; $j<84; $j++) {
                $stocks[$i]['sizeline7'][$j-72]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline7'][$j-72]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline7'][$j-72]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline7'][$j-72]['isEmptySize'] = false;
            }
            for ($j=84; $j<$countSizes; $j++) {
                $stocks[$i]['sizeline8'][$j-84]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline8'][$j-84]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline8'][$j-84]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline8'][$j-84]['isEmptySize'] = false;
            }
            for ($j=$countSizes-84; $j<12; $j++) {
                $stocks[$i]['sizeline8'][$j]['isEmptySize'] = true;
            }
        }
	}

	return  $stocks;

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
        

        <form method="post" action="" name="zaikoForm">
          <div id="contents">
            <h1>在庫照会</h1>
            <table border="0" width="700" cellpadding="0" cellspacing="2" class="tb_1">
              <tr>
                <td colspan="13">※ 在庫確認（上段：新品／下段：リユース品）</td>
              </tr>
              <tr>
                <th width="55">No</th>
                <th width="165">アイテム</th>
                <th width="40">&nbsp;</th>
                <th width="40">&nbsp;</th>
                <th width="40">&nbsp;</th>
                <th width="40">&nbsp;</th>
                <th width="40">&nbsp;</th>
                <th width="40">&nbsp;</th>
                <th width="40">&nbsp;</th>
                <th width="40">&nbsp;</th>
                <th width="40">&nbsp;</th>
                <th width="40">&nbsp;</th>
                <th width="40">&nbsp;</th>
                <th width="40">&nbsp;</th>
              </tr>
<?php for ($i1_stocks=0; $i1_stocks<count($stocks); $i1_stocks++) { ?>
              <tr>
<?php if(!$stocks[$i1_stocks]['multiline']) { ?>
	                <td class="line2" align="center" rowspan="3"><?php isset($stocks[$i1_stocks]['ItemNo']) ? print($stocks[$i1_stocks]['ItemNo']) : print('&#123;stocks.ItemNo&#125;'); ?></td>
	                <td class="line2" align="left" rowspan="3"><?php isset($stocks[$i1_stocks]['ItemName']) ? print($stocks[$i1_stocks]['ItemName']) : print('&#123;stocks.ItemName&#125;'); ?></td>
<?php } ?>
<?php if($stocks[$i1_stocks]['multiline']) { ?>
                    <td class="line2" align="center" rowspan="<?php isset($stocks[$i1_stocks]['rowspan']) ? print($stocks[$i1_stocks]['rowspan']) : print('&#123;stocks.rowspan&#125;'); ?>"><?php isset($stocks[$i1_stocks]['ItemNo']) ? print($stocks[$i1_stocks]['ItemNo']) : print('&#123;stocks.ItemNo&#125;'); ?></td>
                    <td class="line2" align="left" rowspan="<?php isset($stocks[$i1_stocks]['rowspan']) ? print($stocks[$i1_stocks]['rowspan']) : print('&#123;stocks.rowspan&#125;'); ?>"><?php isset($stocks[$i1_stocks]['ItemName']) ? print($stocks[$i1_stocks]['ItemName']) : print('&#123;stocks.ItemName&#125;'); ?></td>
<?php } ?>
<?php for ($i2_stocks['sizeline1']=0; $i2_stocks['sizeline1']<count($stocks[$i1_stocks]['sizeline1']); $i2_stocks['sizeline1']++) { ?>
<?php if(!$stocks[$i1_stocks]['sizeline1'][$i2_stocks['sizeline1']]['isEmptySize']) { ?>
                <td class="line2" align="center" style="background-color:#FFE1E1;"><?php isset($stocks[$i1_stocks]['sizeline1'][$i2_stocks['sizeline1']]['Size']) ? print($stocks[$i1_stocks]['sizeline1'][$i2_stocks['sizeline1']]['Size']) : print('&#123;stocks.sizeline1.Size&#125;'); ?></td>
<?php } ?>
<?php if($stocks[$i1_stocks]['sizeline1'][$i2_stocks['sizeline1']]['isEmptySize']) { ?>
                <td class="line2" align="center" style="background-color:#FFE1E1;">&nbsp;</td>
<?php } ?>
<?php } ?>
              </tr>
              <tr>
<?php for ($i2_stocks['sizeline1']=0; $i2_stocks['sizeline1']<count($stocks[$i1_stocks]['sizeline1']); $i2_stocks['sizeline1']++) { ?>
<?php if(!$stocks[$i1_stocks]['sizeline1'][$i2_stocks['sizeline1']]['isEmptySize']) { ?>
                <td class="line2" align="center"><?php isset($stocks[$i1_stocks]['sizeline1'][$i2_stocks['sizeline1']]['HikiateQty']) ? print($stocks[$i1_stocks]['sizeline1'][$i2_stocks['sizeline1']]['HikiateQty']) : print('&#123;stocks.sizeline1.HikiateQty&#125;'); ?></td>
<?php } ?>
<?php if($stocks[$i1_stocks]['sizeline1'][$i2_stocks['sizeline1']]['isEmptySize']) { ?>
                <td class="line2" align="center">&nbsp;</td>
<?php } ?>
<?php } ?>
              </tr>
              <tr>
<?php for ($i2_stocks['sizeline1']=0; $i2_stocks['sizeline1']<count($stocks[$i1_stocks]['sizeline1']); $i2_stocks['sizeline1']++) { ?>
<?php if(!$stocks[$i1_stocks]['sizeline1'][$i2_stocks['sizeline1']]['isEmptySize']) { ?>
                <td class="line2" align="center"><?php isset($stocks[$i1_stocks]['sizeline1'][$i2_stocks['sizeline1']]['OldHikiateQty']) ? print($stocks[$i1_stocks]['sizeline1'][$i2_stocks['sizeline1']]['OldHikiateQty']) : print('&#123;stocks.sizeline1.OldHikiateQty&#125;'); ?></td>
<?php } ?>
<?php if($stocks[$i1_stocks]['sizeline1'][$i2_stocks['sizeline1']]['isEmptySize']) { ?>
                <td class="line2" align="center">&nbsp;</td>
<?php } ?>
<?php } ?>
              </tr>
<?php if($stocks[$i1_stocks]['multiline']) { ?>
<?php if($stocks[$i1_stocks]['2line']) { ?>
	              <tr>
<?php for ($i2_stocks['sizeline2']=0; $i2_stocks['sizeline2']<count($stocks[$i1_stocks]['sizeline2']); $i2_stocks['sizeline2']++) { ?>
<?php if(!$stocks[$i1_stocks]['sizeline2'][$i2_stocks['sizeline2']]['isEmptySize']) { ?>
	                <td class="line2" align="center" style="background-color:#FFE1E1;"><?php isset($stocks[$i1_stocks]['sizeline2'][$i2_stocks['sizeline2']]['Size']) ? print($stocks[$i1_stocks]['sizeline2'][$i2_stocks['sizeline2']]['Size']) : print('&#123;stocks.sizeline2.Size&#125;'); ?></td>
<?php } ?>
<?php if($stocks[$i1_stocks]['sizeline2'][$i2_stocks['sizeline2']]['isEmptySize']) { ?>
	                <td class="line2" align="center" style="background-color:#FFE1E1;">&nbsp;</td>
<?php } ?>
<?php } ?>
	              </tr>
	              <tr>
<?php for ($i2_stocks['sizeline2']=0; $i2_stocks['sizeline2']<count($stocks[$i1_stocks]['sizeline2']); $i2_stocks['sizeline2']++) { ?>
<?php if(!$stocks[$i1_stocks]['sizeline2'][$i2_stocks['sizeline2']]['isEmptySize']) { ?>
	                <td class="line2" align="center"><?php isset($stocks[$i1_stocks]['sizeline2'][$i2_stocks['sizeline2']]['HikiateQty']) ? print($stocks[$i1_stocks]['sizeline2'][$i2_stocks['sizeline2']]['HikiateQty']) : print('&#123;stocks.sizeline2.HikiateQty&#125;'); ?></td>
<?php } ?>
<?php if($stocks[$i1_stocks]['sizeline2'][$i2_stocks['sizeline2']]['isEmptySize']) { ?>
	                <td class="line2" align="center">&nbsp;</td>
<?php } ?>
<?php } ?>
	              </tr>
              <tr>
<?php for ($i2_stocks['sizeline2']=0; $i2_stocks['sizeline2']<count($stocks[$i1_stocks]['sizeline2']); $i2_stocks['sizeline2']++) { ?>
<?php if(!$stocks[$i1_stocks]['sizeline2'][$i2_stocks['sizeline2']]['isEmptySize']) { ?>
                <td class="line2" align="center"><?php isset($stocks[$i1_stocks]['sizeline2'][$i2_stocks['sizeline2']]['OldHikiateQty']) ? print($stocks[$i1_stocks]['sizeline2'][$i2_stocks['sizeline2']]['OldHikiateQty']) : print('&#123;stocks.sizeline2.OldHikiateQty&#125;'); ?></td>
<?php } ?>
<?php if($stocks[$i1_stocks]['sizeline2'][$i2_stocks['sizeline2']]['isEmptySize']) { ?>
                <td class="line2" align="center">&nbsp;</td>
<?php } ?>
<?php } ?>
              </tr>

<?php } ?>

<?php if($stocks[$i1_stocks]['3line']) { ?>
                  <tr>
<?php for ($i2_stocks['sizeline3']=0; $i2_stocks['sizeline3']<count($stocks[$i1_stocks]['sizeline3']); $i2_stocks['sizeline3']++) { ?>
<?php if(!$stocks[$i1_stocks]['sizeline3'][$i2_stocks['sizeline3']]['isEmptySize']) { ?>
                    <td class="line2" align="center" style="background-color:#FFE1E1;"><?php isset($stocks[$i1_stocks]['sizeline3'][$i2_stocks['sizeline3']]['Size']) ? print($stocks[$i1_stocks]['sizeline3'][$i2_stocks['sizeline3']]['Size']) : print('&#123;stocks.sizeline3.Size&#125;'); ?></td>
<?php } ?>
<?php if($stocks[$i1_stocks]['sizeline3'][$i2_stocks['sizeline3']]['isEmptySize']) { ?>
                    <td class="line2" align="center" style="background-color:#FFE1E1;">&nbsp;</td>
<?php } ?>
<?php } ?>
                  </tr>
                  <tr>
<?php for ($i2_stocks['sizeline3']=0; $i2_stocks['sizeline3']<count($stocks[$i1_stocks]['sizeline3']); $i2_stocks['sizeline3']++) { ?>
<?php if(!$stocks[$i1_stocks]['sizeline3'][$i2_stocks['sizeline3']]['isEmptySize']) { ?>
                    <td class="line2" align="center"><?php isset($stocks[$i1_stocks]['sizeline3'][$i2_stocks['sizeline3']]['HikiateQty']) ? print($stocks[$i1_stocks]['sizeline3'][$i2_stocks['sizeline3']]['HikiateQty']) : print('&#123;stocks.sizeline3.HikiateQty&#125;'); ?></td>
<?php } ?>
<?php if($stocks[$i1_stocks]['sizeline3'][$i2_stocks['sizeline3']]['isEmptySize']) { ?>
                    <td class="line2" align="center">&nbsp;</td>
<?php } ?>
<?php } ?>
                  </tr>
              <tr>
<?php for ($i2_stocks['sizeline3']=0; $i2_stocks['sizeline3']<count($stocks[$i1_stocks]['sizeline3']); $i2_stocks['sizeline3']++) { ?>
<?php if(!$stocks[$i1_stocks]['sizeline3'][$i2_stocks['sizeline3']]['isEmptySize']) { ?>
                <td class="line2" align="center"><?php isset($stocks[$i1_stocks]['sizeline3'][$i2_stocks['sizeline3']]['OldHikiateQty']) ? print($stocks[$i1_stocks]['sizeline3'][$i2_stocks['sizeline3']]['OldHikiateQty']) : print('&#123;stocks.sizeline3.OldHikiateQty&#125;'); ?></td>
<?php } ?>
<?php if($stocks[$i1_stocks]['sizeline3'][$i2_stocks['sizeline3']]['isEmptySize']) { ?>
                <td class="line2" align="center">&nbsp;</td>
<?php } ?>
<?php } ?>
              </tr>
<?php } ?>

<?php if($stocks[$i1_stocks]['4line']) { ?>
                  <tr>
<?php for ($i2_stocks['sizeline4']=0; $i2_stocks['sizeline4']<count($stocks[$i1_stocks]['sizeline4']); $i2_stocks['sizeline4']++) { ?>
<?php if(!$stocks[$i1_stocks]['sizeline4'][$i2_stocks['sizeline4']]['isEmptySize']) { ?>
                    <td class="line2" align="center" style="background-color:#FFE1E1;"><?php isset($stocks[$i1_stocks]['sizeline4'][$i2_stocks['sizeline4']]['Size']) ? print($stocks[$i1_stocks]['sizeline4'][$i2_stocks['sizeline4']]['Size']) : print('&#123;stocks.sizeline4.Size&#125;'); ?></td>
<?php } ?>
<?php if($stocks[$i1_stocks]['sizeline4'][$i2_stocks['sizeline4']]['isEmptySize']) { ?>
                    <td class="line2" align="center" style="background-color:#FFE1E1;">&nbsp;</td>
<?php } ?>
<?php } ?>
                  </tr>
                  <tr>
<?php for ($i2_stocks['sizeline4']=0; $i2_stocks['sizeline4']<count($stocks[$i1_stocks]['sizeline4']); $i2_stocks['sizeline4']++) { ?>
<?php if(!$stocks[$i1_stocks]['sizeline4'][$i2_stocks['sizeline4']]['isEmptySize']) { ?>
                    <td class="line2" align="center"><?php isset($stocks[$i1_stocks]['sizeline4'][$i2_stocks['sizeline4']]['HikiateQty']) ? print($stocks[$i1_stocks]['sizeline4'][$i2_stocks['sizeline4']]['HikiateQty']) : print('&#123;stocks.sizeline4.HikiateQty&#125;'); ?></td>
<?php } ?>
<?php if($stocks[$i1_stocks]['sizeline4'][$i2_stocks['sizeline4']]['isEmptySize']) { ?>
                    <td class="line2" align="center">&nbsp;</td>
<?php } ?>
<?php } ?>
                  </tr>
              <tr>
<?php for ($i2_stocks['sizeline4']=0; $i2_stocks['sizeline4']<count($stocks[$i1_stocks]['sizeline4']); $i2_stocks['sizeline4']++) { ?>
<?php if(!$stocks[$i1_stocks]['sizeline4'][$i2_stocks['sizeline4']]['isEmptySize']) { ?>
                <td class="line2" align="center"><?php isset($stocks[$i1_stocks]['sizeline4'][$i2_stocks['sizeline4']]['OldHikiateQty']) ? print($stocks[$i1_stocks]['sizeline4'][$i2_stocks['sizeline4']]['OldHikiateQty']) : print('&#123;stocks.sizeline4.OldHikiateQty&#125;'); ?></td>
<?php } ?>
<?php if($stocks[$i1_stocks]['sizeline4'][$i2_stocks['sizeline4']]['isEmptySize']) { ?>
                <td class="line2" align="center">&nbsp;</td>
<?php } ?>
<?php } ?>
              </tr>
<?php } ?>

<?php if($stocks[$i1_stocks]['5line']) { ?>
                  <tr>
<?php for ($i2_stocks['sizeline5']=0; $i2_stocks['sizeline5']<count($stocks[$i1_stocks]['sizeline5']); $i2_stocks['sizeline5']++) { ?>
<?php if(!$stocks[$i1_stocks]['sizeline5'][$i2_stocks['sizeline5']]['isEmptySize']) { ?>
                    <td class="line2" align="center" style="background-color:#FFE1E1;"><?php isset($stocks[$i1_stocks]['sizeline5'][$i2_stocks['sizeline5']]['Size']) ? print($stocks[$i1_stocks]['sizeline5'][$i2_stocks['sizeline5']]['Size']) : print('&#123;stocks.sizeline5.Size&#125;'); ?></td>
<?php } ?>
<?php if($stocks[$i1_stocks]['sizeline5'][$i2_stocks['sizeline5']]['isEmptySize']) { ?>
                    <td class="line2" align="center" style="background-color:#FFE1E1;">&nbsp;</td>
<?php } ?>
<?php } ?>
                  </tr>
                  <tr>
<?php for ($i2_stocks['sizeline5']=0; $i2_stocks['sizeline5']<count($stocks[$i1_stocks]['sizeline5']); $i2_stocks['sizeline5']++) { ?>
<?php if(!$stocks[$i1_stocks]['sizeline5'][$i2_stocks['sizeline5']]['isEmptySize']) { ?>
                    <td class="line2" align="center"><?php isset($stocks[$i1_stocks]['sizeline5'][$i2_stocks['sizeline5']]['HikiateQty']) ? print($stocks[$i1_stocks]['sizeline5'][$i2_stocks['sizeline5']]['HikiateQty']) : print('&#123;stocks.sizeline5.HikiateQty&#125;'); ?></td>
<?php } ?>
<?php if($stocks[$i1_stocks]['sizeline5'][$i2_stocks['sizeline5']]['isEmptySize']) { ?>
                    <td class="line2" align="center">&nbsp;</td>
<?php } ?>
<?php } ?>
                  </tr>
              <tr>
<?php for ($i2_stocks['sizeline5']=0; $i2_stocks['sizeline5']<count($stocks[$i1_stocks]['sizeline5']); $i2_stocks['sizeline5']++) { ?>
<?php if(!$stocks[$i1_stocks]['sizeline5'][$i2_stocks['sizeline5']]['isEmptySize']) { ?>
                <td class="line2" align="center"><?php isset($stocks[$i1_stocks]['sizeline5'][$i2_stocks['sizeline5']]['OldHikiateQty']) ? print($stocks[$i1_stocks]['sizeline5'][$i2_stocks['sizeline5']]['OldHikiateQty']) : print('&#123;stocks.sizeline5.OldHikiateQty&#125;'); ?></td>
<?php } ?>
<?php if($stocks[$i1_stocks]['sizeline5'][$i2_stocks['sizeline5']]['isEmptySize']) { ?>
                <td class="line2" align="center">&nbsp;</td>
<?php } ?>
<?php } ?>
              </tr>
<?php } ?>

<?php if($stocks[$i1_stocks]['6line']) { ?>
                  <tr>
<?php for ($i2_stocks['sizeline6']=0; $i2_stocks['sizeline6']<count($stocks[$i1_stocks]['sizeline6']); $i2_stocks['sizeline6']++) { ?>
<?php if(!$stocks[$i1_stocks]['sizeline6'][$i2_stocks['sizeline6']]['isEmptySize']) { ?>
                    <td class="line2" align="center" style="background-color:#FFE1E1;"><?php isset($stocks[$i1_stocks]['sizeline6'][$i2_stocks['sizeline6']]['Size']) ? print($stocks[$i1_stocks]['sizeline6'][$i2_stocks['sizeline6']]['Size']) : print('&#123;stocks.sizeline6.Size&#125;'); ?></td>
<?php } ?>
<?php if($stocks[$i1_stocks]['sizeline6'][$i2_stocks['sizeline6']]['isEmptySize']) { ?>
                    <td class="line2" align="center" style="background-color:#FFE1E1;">&nbsp;</td>
<?php } ?>
<?php } ?>
                  </tr>
                  <tr>
<?php for ($i2_stocks['sizeline6']=0; $i2_stocks['sizeline6']<count($stocks[$i1_stocks]['sizeline6']); $i2_stocks['sizeline6']++) { ?>
<?php if(!$stocks[$i1_stocks]['sizeline6'][$i2_stocks['sizeline6']]['isEmptySize']) { ?>
                    <td class="line2" align="center"><?php isset($stocks[$i1_stocks]['sizeline6'][$i2_stocks['sizeline6']]['HikiateQty']) ? print($stocks[$i1_stocks]['sizeline6'][$i2_stocks['sizeline6']]['HikiateQty']) : print('&#123;stocks.sizeline6.HikiateQty&#125;'); ?></td>
<?php } ?>
<?php if($stocks[$i1_stocks]['sizeline6'][$i2_stocks['sizeline6']]['isEmptySize']) { ?>
                    <td class="line2" align="center">&nbsp;</td>
<?php } ?>
<?php } ?>
                  </tr>
              <tr>
<?php for ($i2_stocks['sizeline6']=0; $i2_stocks['sizeline6']<count($stocks[$i1_stocks]['sizeline6']); $i2_stocks['sizeline6']++) { ?>
<?php if(!$stocks[$i1_stocks]['sizeline6'][$i2_stocks['sizeline6']]['isEmptySize']) { ?>
                <td class="line2" align="center"><?php isset($stocks[$i1_stocks]['sizeline6'][$i2_stocks['sizeline6']]['OldHikiateQty']) ? print($stocks[$i1_stocks]['sizeline6'][$i2_stocks['sizeline6']]['OldHikiateQty']) : print('&#123;stocks.sizeline6.OldHikiateQty&#125;'); ?></td>
<?php } ?>
<?php if($stocks[$i1_stocks]['sizeline6'][$i2_stocks['sizeline6']]['isEmptySize']) { ?>
                <td class="line2" align="center">&nbsp;</td>
<?php } ?>
<?php } ?>
              </tr>
<?php } ?>

<?php if($stocks[$i1_stocks]['7line']) { ?>
                  <tr>
<?php for ($i2_stocks['sizeline7']=0; $i2_stocks['sizeline7']<count($stocks[$i1_stocks]['sizeline7']); $i2_stocks['sizeline7']++) { ?>
<?php if(!$stocks[$i1_stocks]['sizeline7'][$i2_stocks['sizeline7']]['isEmptySize']) { ?>
                    <td class="line2" align="center" style="background-color:#FFE1E1;"><?php isset($stocks[$i1_stocks]['sizeline7'][$i2_stocks['sizeline7']]['Size']) ? print($stocks[$i1_stocks]['sizeline7'][$i2_stocks['sizeline7']]['Size']) : print('&#123;stocks.sizeline7.Size&#125;'); ?></td>
<?php } ?>
<?php if($stocks[$i1_stocks]['sizeline7'][$i2_stocks['sizeline7']]['isEmptySize']) { ?>
                    <td class="line2" align="center" style="background-color:#FFE1E1;">&nbsp;</td>
<?php } ?>
<?php } ?>
                  </tr>
                  <tr>
<?php for ($i2_stocks['sizeline7']=0; $i2_stocks['sizeline7']<count($stocks[$i1_stocks]['sizeline7']); $i2_stocks['sizeline7']++) { ?>
<?php if(!$stocks[$i1_stocks]['sizeline7'][$i2_stocks['sizeline7']]['isEmptySize']) { ?>
                    <td class="line2" align="center"><?php isset($stocks[$i1_stocks]['sizeline7'][$i2_stocks['sizeline7']]['HikiateQty']) ? print($stocks[$i1_stocks]['sizeline7'][$i2_stocks['sizeline7']]['HikiateQty']) : print('&#123;stocks.sizeline7.HikiateQty&#125;'); ?></td>
<?php } ?>
<?php if($stocks[$i1_stocks]['sizeline7'][$i2_stocks['sizeline7']]['isEmptySize']) { ?>
                    <td class="line2" align="center">&nbsp;</td>
<?php } ?>
<?php } ?>
                  </tr>
              <tr>
<?php for ($i2_stocks['sizeline7']=0; $i2_stocks['sizeline7']<count($stocks[$i1_stocks]['sizeline7']); $i2_stocks['sizeline7']++) { ?>
<?php if(!$stocks[$i1_stocks]['sizeline7'][$i2_stocks['sizeline7']]['isEmptySize']) { ?>
                <td class="line2" align="center"><?php isset($stocks[$i1_stocks]['sizeline7'][$i2_stocks['sizeline7']]['OldHikiateQty']) ? print($stocks[$i1_stocks]['sizeline7'][$i2_stocks['sizeline7']]['OldHikiateQty']) : print('&#123;stocks.sizeline7.OldHikiateQty&#125;'); ?></td>
<?php } ?>
<?php if($stocks[$i1_stocks]['sizeline7'][$i2_stocks['sizeline7']]['isEmptySize']) { ?>
                <td class="line2" align="center">&nbsp;</td>
<?php } ?>
<?php } ?>
              </tr>
<?php } ?>

<?php if($stocks[$i1_stocks]['8line']) { ?>
                  <tr>
<?php for ($i2_stocks['sizeline8']=0; $i2_stocks['sizeline8']<count($stocks[$i1_stocks]['sizeline8']); $i2_stocks['sizeline8']++) { ?>
<?php if(!$stocks[$i1_stocks]['sizeline8'][$i2_stocks['sizeline8']]['isEmptySize']) { ?>
                    <td class="line2" align="center" style="background-color:#FFE1E1;"><?php isset($stocks[$i1_stocks]['sizeline8'][$i2_stocks['sizeline8']]['Size']) ? print($stocks[$i1_stocks]['sizeline8'][$i2_stocks['sizeline8']]['Size']) : print('&#123;stocks.sizeline8.Size&#125;'); ?></td>
<?php } ?>
<?php if($stocks[$i1_stocks]['sizeline8'][$i2_stocks['sizeline8']]['isEmptySize']) { ?>
                    <td class="line2" align="center" style="background-color:#FFE1E1;">&nbsp;</td>
<?php } ?>
<?php } ?>
                  </tr>
                  <tr>
<?php for ($i2_stocks['sizeline8']=0; $i2_stocks['sizeline8']<count($stocks[$i1_stocks]['sizeline8']); $i2_stocks['sizeline8']++) { ?>
<?php if(!$stocks[$i1_stocks]['sizeline8'][$i2_stocks['sizeline8']]['isEmptySize']) { ?>
                    <td class="line2" align="center"><?php isset($stocks[$i1_stocks]['sizeline8'][$i2_stocks['sizeline8']]['HikiateQty']) ? print($stocks[$i1_stocks]['sizeline8'][$i2_stocks['sizeline8']]['HikiateQty']) : print('&#123;stocks.sizeline8.HikiateQty&#125;'); ?></td>
<?php } ?>
<?php if($stocks[$i1_stocks]['sizeline8'][$i2_stocks['sizeline8']]['isEmptySize']) { ?>
                    <td class="line2" align="center">&nbsp;</td>
<?php } ?>
<?php } ?>
                  </tr>
              <tr>
<?php for ($i2_stocks['sizeline8']=0; $i2_stocks['sizeline8']<count($stocks[$i1_stocks]['sizeline8']); $i2_stocks['sizeline8']++) { ?>
<?php if(!$stocks[$i1_stocks]['sizeline8'][$i2_stocks['sizeline8']]['isEmptySize']) { ?>
                <td class="line2" align="center"><?php isset($stocks[$i1_stocks]['sizeline8'][$i2_stocks['sizeline8']]['OldHikiateQty']) ? print($stocks[$i1_stocks]['sizeline8'][$i2_stocks['sizeline8']]['OldHikiateQty']) : print('&#123;stocks.sizeline8.OldHikiateQty&#125;'); ?></td>
<?php } ?>
<?php if($stocks[$i1_stocks]['sizeline8'][$i2_stocks['sizeline8']]['isEmptySize']) { ?>
                <td class="line2" align="center">&nbsp;</td>
<?php } ?>
<?php } ?>
              </tr>
<?php } ?>

<?php } ?>
<?php } ?>
            </table>
          </div>
        </form>
        <br><br><br>
        

      </div>
    </div>
  </body>
</html>
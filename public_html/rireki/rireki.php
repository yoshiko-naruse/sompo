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


include_once('../../include/msSqlControl.php');		// DB操作モジュール
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



// 初期設定
$isMenuHistory = true;	// 申請履歴のメニューをアクティブに

// 変数の初期化 ここから ******************************************************
$searchCompCd       = '';					// 店舗コード
$searchCompName     = '';					// 店舗名
$searchCompId       = '';					// 店舗ID
$searchAppliNo      = '';					// 申請番号
$searchAppliDayFrom = '';					// 申請日
$searchAppliDaryTo  = '';					// 申請日
$searchShipDayFrom  = '';					// 出荷日
$searchShipDaryTo   = '';					// 出荷日
$searchStaffCode    = '';					// スタッフコード
$searchBarCode      = '';					// バーコード
$searchStatus       = array();				// ステータス

$isSelectedAdmin    = false;				// 管理者権限で検索を行ったかどうか

// 状態の表示文字列
$DISPLAY_STATUS_APPLI       = $DISPLAY_STATUS[1];		// 承認待
$DISPLAY_STATUS_APPLI_ADMIT = $DISPLAY_STATUS[3];		// 申請済
$DISPLAY_STATUS_ORDER       = $DISPLAY_STATUS[14];		// 受注済
$DISPLAY_STATUS_SHIP        = $DISPLAY_STATUS[15];		// 出荷済
$DISPLAY_STATUS_DELIVERY    = $DISPLAY_STATUS[16];		// 納品済
$DISPLAY_STATUS_STOCKOUT    = $DISPLAY_STATUS[13];		// 在庫切
$DISPLAY_STATUS_NOT_RETURN  = $DISPLAY_STATUS[20];		// 未返却
$DISPLAY_STATUS_RETURN      = $DISPLAY_STATUS[22];		// 返却済
$DISPLAY_STATUS_LOSS        = $DISPLAY_STATUS[34];		// 紛失

$compCd    = castHtmlEntity($_SESSION['COMPCD']);	// 店舗番号
$compName  = castHtmlEntity($_SESSION['COMPNAME']);	// 店舗名

if ($isLevelAgency == true) {
	$isLevelAdmin = true;
}
// 変数の初期化 ここまで ******************************************************

// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST); 

$nowPage = 1;
if ($post['nowPage'] != '') {
	$nowPage = trim($post['nowPage']);
}

$isSelectedAdmin = trim($post['isSelectedAdmin']);

// 検索時は1ページ目に戻す
if ($post['searchFlg'] == 1) {
	$nowPage = 1;
	$isSelectedAdmin = true;
}

if ($isLevelAdmin == false || $isSelectedAdmin == true) {

	$isSelectedAdmin = true;

	// 管理者権限の場合は検索条件を指定しているか判定
//	if ($isLevelAdmin == true) {
//
//		// 条件が指定されているか判定
//		$hasCondition = checkCondition($post);
//	
//		if ($hasCondition == false) {
//
//			$hiddens['errorName'] = 'rireki';
//			$hiddens['menuName']  = 'isMenuHistory';
//			$hiddens['returnUrl'] = 'rireki/rireki.php';
//			$hiddens['errorId'][] = '902';
//			$errorUrl             = HOME_URL . 'error.php';
//
//			redirectPost($errorUrl, $hiddens);
//
//		}
//	}

	// 表示する注文履歴一覧を取得
	//$orders = castHtmlEntity(getOrder($dbConnect, $_POST, $nowPage, $DISPLAY_STATUS, $allCount));
	$orders = getOrder($dbConnect, $_POST, $nowPage, $DISPLAY_STATUS, $allCount, $isLevelItc, $isLevelHonbu);

	// ページングのセッティング
	$paging = setPaging($nowPage, PAGE_PER_DISPLAY_HISTORY, $allCount);

	// 注文履歴が０件の場合
	if (count($orders) <= 0) {

		// 条件が指定されているか判定
		$hasCondition = checkCondition($post);

		$hiddens['errorName'] = 'rireki';
		$hiddens['menuName']  = 'isMenuHistory';

		if ($hasCondition == true) {
			$hiddens['returnUrl'] = 'rireki/rireki.php';
		}
		else {
			$hiddens['returnUrl'] = 'top.php';
		}

		$hiddens['errorId'][] = '901';
		$errorUrl             = HOME_URL . 'error.php';

		redirectPost($errorUrl, $hiddens);

	}

}
$orders = castHtmlEntity($orders);
// 表示する値の成型 ここから +++++++++++++++++++++++++++++++++++++++++++++++++++

// 店舗コード
$searchCompCd       = trim($post['searchCompCd']);

// 店舗名
$searchCompName     = trim($post['searchCompName']);

// 店舗ID
$searchCompId       = trim($post['searchCompId']);

// 申請番号
$searchAppliNo      = trim($post['searchAppliNo']);

// 申請日
$searchAppliDayFrom = trim($post['searchAppliDayFrom']);
$searchAppliDayTo   = trim($post['searchAppliDayTo']);

// 出荷日
$searchShipDayFrom = trim($post['searchShipDayFrom']);
$searchShipDayTo   = trim($post['searchShipDayTo']);

// スタッフコード
$searchStaffCode    = trim($post['searchStaffCode']);

// 単品番号
$searchBarCode      = trim($post['searchBarCode']);

// 状態
for ($i=1; $i<=9; $i++) {

	${'isSelectedStatus' . $i} = false;
	if (isset($post['searchStatus']) && is_array($post['searchStatus']) && in_array($i, $post['searchStatus'])) {
		${'isSelectedStatus' . $i} = true;
	}

}
// 表示する値の成型 ここまで +++++++++++++++++++++++++++++++++++++++++++++++++++

/*
 * 注文履歴一覧情報を取得する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$post           => POST値
 *       ：$nowPage        => 現在のページ数
 *       ：$DISPLAY_STATUS => 状態
 *       ：$allCount       => 全件数
 * 戻り値：$result         => 注文履歴一覧情報
 *
 * create 2007/03/26 H.Osugi
 *
 */
function getOrder($dbConnect, $post, $nowPage, $DISPLAY_STATUS ,&$allCount, $isLevelItc, $isLevelHonbu) {

	global $isLevelAdmin;	// 管理者権限の有無
	global $isLevelAgency;	// 一次代理店

	// 初期化
	$compId       = '';
	$appliNo      = '';
	$appliDayFrom = '';
	$appliDayTo   = '';
	$shipDayFrom  = '';
	$shipDayTo    = '';
	$staffCode    = '';
	$barCode      = '';
	$status       = '';
	$limit        = '';
	$offset       = '';
	$corpCode     = '';
    $honbuCd      = '';
    $shibuCd      = '';

	// 取得したい件数
	$limit = PAGE_PER_DISPLAY_HISTORY;		// 1ページあたりの表示件数;

	// 取得したいデータの開始位置
	$offset = ($nowPage - 1) * PAGE_PER_DISPLAY_HISTORY;

	// 店舗ID
	$compId = $_SESSION['COMPID'];
	if ($isLevelAdmin == true) {
		$compId = $post['searchCompId'];

		// 店舗IDに不正な値が入っていた場合
		if ($compId != '' && !ctype_digit($compId)) {
			$result = array();
			return $result;
		}

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

	// 申請番号
	if ($isLevelAdmin == true) {
		$appliNo = $post['searchAppliNo'];
	}

	// 申請日
	if ($isLevelAdmin == true) {

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

	}

	// 出荷日
	if ($isLevelAdmin == true) {

		$shipDayFrom = $post['searchShipDayFrom'];
		$shipDayTo   = $post['searchShipDayTo'];

		// YY/MM/DD以外の文字列であれば処理を終了する
		if ($shipDayFrom != '' && !ereg('^[0-9]{4}/[0-9]{2}/[0-9]{2}$', $shipDayFrom)) {
			return $result;
		}
	
		// YY/MM/DD以外の文字列であれば処理を終了する
		if ($shipDayTo != '' && !ereg('^[0-9]{4}/[0-9]{2}/[0-9]{2}$', $shipDayTo)) {
			return $result;
		}

	}

	// スタッフコード
	$staffCode = $post['searchStaffCode'];

	// 単品番号
	$barCode = $post['searchBarCode'];

	// 状態
	$countStatus = 0;
	if (isset($post['searchStatus']) && is_array($post['searchStatus'])) {
		$countStatus = count($post['searchStatus']);
	}
	for ($i=0; $i<$countStatus; $i++) {
		switch ($post['searchStatus'][$i]) {
			case '1':
				$status .= 	" " . STATUS_APPLI;					// 申請済（承認待ち）
				break;
			case '2':
				$status .= 	" " . STATUS_APPLI_ADMIT;			// 申請済（承認済）
				break;
			case '3':
				$status .= 	" " . STATUS_ORDER;					// 受注済
				break;
			case '4':
				$status .= 	" " . STATUS_SHIP;					// 出荷済
				break;
			case '5':
				$status .= 	" " . STATUS_DELIVERY;				// 納品済
				break;
			case '6':
				$status .= 	" " . STATUS_STOCKOUT;				// 在庫切れ
				break;
			case '7':
				$status .= 	" " . STATUS_NOT_RETURN;			// 未返却（承認待ち）
				$status .= 	" ," . STATUS_NOT_RETURN_ADMIT;		// 未返却（承認済）
				$status .= 	" ," . STATUS_NOT_RETURN_ORDER;		// 未返却（受注済）
				break;
			case '8':
				$status .= 	" " . STATUS_RETURN;				// 返却済
				break;
			case '9':
				$status .= 	" " . STATUS_LOSS;					// 紛失（承認待ち）
				$status .= 	" ," . STATUS_LOSS_ADMIT;			// 紛失（承認済）
				$status .= 	" ," . STATUS_LOSS_ORDER;			// 紛失（受注済）
				break;
			default:
				break;
		}

		if ($i != $countStatus -1) {
			$status .= " ,";
		}

	}


	// 注文履歴の件数を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" COUNT(DISTINCT tor.OrderID) as count_order";
	$sql .= " FROM";
	$sql .= 	" T_Order tor";

	if ($barCode != '' || $status != '') {
		$sql .= " INNER JOIN";
		$sql .= 	" T_Order_Details tod";
		$sql .= " ON";
		$sql .= 	" tor.OrderID = tod.OrderID";
		$sql .= " AND";
		$sql .= 	" tod.Del = " . DELETE_OFF;
	}

	$sql .= " INNER JOIN";
	$sql .= 	" M_Comp mc";
	$sql .= " ON";
	$sql .= 	" tor.CompID = mc.CompID";
	$sql .= " AND";
	$sql .= 	" mc.Del = " . DELETE_OFF;
	$sql .= " AND";
    $sql .=     " mc.ShopFlag <> 0";        // ShopFlagが0以外の店舗を表示する

	$sql .= " WHERE";

	if ($compId != '') {
		$sql .= 	" tor.CompID = " . db_Escape($compId);
		$sql .= " AND";
	}

	$sql .= 	" tor.Del = " . DELETE_OFF;

	// 申請番号を前方一致
	if ($appliNo != '') {
		$sql .= " AND";
		$sql .= 	" tor.AppliNo LIKE ('" . db_Like_Escape($appliNo) . "%')";
	}

	if ($appliDayFrom != '') {
		$sql .= " AND";
		$sql .= 	" CONVERT(char, tor.AppliDay, 111) >= '" . db_Escape($appliDayFrom) . "'";
	}

	if ($appliDayTo != '') {
		$sql .= " AND";
		$sql .= 	" CONVERT(char, tor.AppliDay, 111) <= '" . db_Escape($appliDayTo) . "'";
	}

	if ($shipDayFrom != '') {
		$sql .= " AND";
		$sql .= 	" CONVERT(char, tor.ShipDay, 111) >= '" . db_Escape($shipDayFrom) . "'";
	}

	if ($shipDayTo != '') {
		$sql .= " AND";
		$sql .= 	" CONVERT(char, tor.ShipDay, 111) <= '" . db_Escape($shipDayTo) . "'";
	}

	if ($staffCode != '') {
		$sql .= " AND";
		$sql .= 	" tor.StaffCode = '" . db_Escape($staffCode) . "'";
	}

	if ($barCode != '') {
		$sql .= " AND";
		//$sql .= 	" tod.BarCd = '" . db_Escape($barCode) . "'";
		$sql .= 	" tod.BarCd LIKE '" . db_Like_Escape($barCode) . "%'";
	}

	if ($honbuCd != '') {
		$sql .= " AND";
		$sql .= 	" mc.HonbuCd = '" . db_Escape($honbuCd) . "'";
	}

	if ($shibuCd != '') {
		$sql .= " AND";
		$sql .= 	" mc.ShibuCd = '" . db_Escape($shibuCd) . "'";
	}

	if ($status != '') {
		$sql .= " AND";
		$sql .= 	" tod.Status IN (";
		$sql .= 			$status;
		$sql .= 	" )";
	}

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	$allCount = 0;
	if (!isset($result[0]['count_order']) || $result[0]['count_order'] <= 0) {
		$result = array();
	 	return $result;
	}

	// 全件数
	$allCount = $result[0]['count_order'];

	$top = $offset + $limit;
	if ($top > $allCount) {
		$limit = $limit - ($top - $allCount);
		$top   = $allCount;
	}

	// 注文履歴の一覧を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" tor.OrderID,";
	$sql .= 	" tor.AppliDay,";
	$sql .= 	" tor.AppliNo,";
	$sql .= 	" tor.AppliCompCd,";
	$sql .= 	" tor.AppliCompName,";
	$sql .= 	" tor.StaffCode,";
    $sql .=     " tor.PersonName,";
	$sql .= 	" tor.AppliMode,";
	$sql .= 	" tor.AppliSeason,";
	$sql .= 	" tor.AppliReason,";
	$sql .= 	" tor.Status,";
	$sql .= 	" tor.ShipDay,";
	$sql .= 	" tor.ReturnDay";
	$sql .= " FROM";
	$sql .= 	" (";
	$sql .= 		" SELECT";
	$sql .= 			" TOP " . $limit;
    $sql .=             " tor2.OrderID,";
    $sql .=             " tor2.AppliDay,";
    $sql .=             " tor2.AppliNo,";
    $sql .=             " mc2.CompCd as AppliCompCd,";
    $sql .=             " mc2.CompName as AppliCompName,";
    $sql .=             " tor2.StaffCode,";
    $sql .=             " tor2.PersonName,";
    $sql .=             " tor2.AppliMode,";
    $sql .=             " tor2.AppliSeason,";
    $sql .=             " tor2.AppliReason,";
    $sql .=             " tor2.Status,";
    $sql .=             " tor2.ShipDay,";
    $sql .=             " tor2.ReturnDay";
	$sql .= 		" FROM";
	$sql .= 			" T_Order tor2";
    $sql .=             " INNER JOIN";
    $sql .=             " M_Staff ms2";
    $sql .=             " ON";
    $sql .=               " tor2.StaffID = ms2.StaffSeqID";
    $sql .=             " AND";
    $sql .=               " ms2.Del = " . DELETE_OFF;
	$sql .=             " INNER JOIN";
    $sql .=             " M_Comp mc2";
    $sql .=             " ON";
    $sql .=               " ms2.CompID = mc2.CompID";
    $sql .=             " AND";
    $sql .=               " mc2.Del = " . DELETE_OFF;
	$sql .= 		" WHERE";
	$sql .= 			" tor2.OrderID IN (";
	$sql .= 						" SELECT";
	$sql .= 							" OrderID";
	$sql .= 						" FROM";
	$sql .= 							" (";
	$sql .= 								" SELECT";
	$sql .= 									" DISTINCT";
	$sql .= 									" TOP " . ($top);
	$sql .= 									" tor3.OrderID,";
	$sql .= 									" tor3.AppliDay";
	$sql .= 								" FROM";
	$sql .= 									" T_Order tor3";

	if ($barCode != '' || $status != '') {
		$sql .= 							" INNER JOIN";
		$sql .= 								" T_Order_Details tod";
		$sql .= 							" ON";
		$sql .= 								" tor3.OrderID = tod.OrderID";
		$sql .= 							" AND";
		$sql .= 								" tod.Del = " . DELETE_OFF;
	}

	$sql .= 								" INNER JOIN";
	$sql .= 									" M_Comp mc";
	$sql .= 								" ON";
	$sql .= 									" tor3.CompID = mc.CompID";
	$sql .= 								" AND";
	$sql .= 									" mc.Del = " . DELETE_OFF;
	$sql .= 								" AND";
    $sql .=     								" mc.ShopFlag <> 0";        // ShopFlagが0以外の店舗を表示する

	$sql .= 								" WHERE";

	if ($compId != '') {
		$sql .= 									" tor3.CompID = " . db_Escape($compId);
		$sql .= 								" AND";
	}

	$sql .= 									" tor3.Del = " . DELETE_OFF;

	// 申請番号を前方一致
	if ($appliNo != '') {
		$sql .= 							" AND";
		$sql .= 								" tor3.AppliNo LIKE ('" . db_Like_Escape($appliNo) . "%')";
	}

	if ($appliDayFrom != '') {
		$sql .= 							" AND";
		$sql .= 								" CONVERT(char, tor3.AppliDay, 111) >= '" . db_Escape($appliDayFrom) . "'";
	}

	if ($appliDayTo != '') {
		$sql .= 							" AND";
		$sql .= 								" CONVERT(char, tor3.AppliDay, 111) <= '" . db_Escape($appliDayTo) . "'";
	}

	if ($shipDayFrom != '') {
		$sql .= 							" AND";
		$sql .= 								" CONVERT(char, tor3.ShipDay, 111) >= '" . db_Escape($shipDayFrom) . "'";
	}

	if ($shipDayTo != '') {
		$sql .= 							" AND";
		$sql .= 								" CONVERT(char, tor3.ShipDay, 111) <= '" . db_Escape($shipDayTo) . "'";
	}

	if ($staffCode != '') {
		$sql .= 							" AND";
		$sql .= 								" tor3.StaffCode = '" . db_Escape($staffCode) . "'";
	}
	
	if ($barCode != '') {
		$sql .= 							" AND";
		//$sql .= 								" tod.BarCd = '" . db_Escape($barCode) . "'";
		$sql .= 								" tod.BarCd LIKE '" . db_Like_Escape($barCode) . "%'";
	}

	if ($honbuCd != '') {
		$sql .= 							" AND";
		$sql .= 								" mc.HonbuCd = '" . db_Escape($honbuCd) . "'";
	}

	if ($shibuCd != '') {
		$sql .= 							" AND";
		$sql .= 								" mc.ShibuCd = '" . db_Escape($shibuCd) . "'";
	}

	if ($status != '') {
		$sql .= 							" AND";
		$sql .= 								" tod.Status IN(";
		$sql .= 										$status;
		$sql .= 								" )";
	}

	$sql .= 								" ORDER BY";
	$sql .= 									" tor3.AppliDay DESC,";
	$sql .= 									" tor3.OrderID DESC";
	$sql .= 							" ) tor4";
	$sql .= 						" )";
	$sql .= 				" ORDER BY";
	$sql .= 					" tor2.AppliDay ASC,";
	$sql .= 					" tor2.OrderID ASC";

	$sql .= 	" ) tor";

	$sql .= 	" ORDER BY";
	$sql .= 		" tor.AppliDay DESC,";
	$sql .= 		" tor.OrderID DESC";
//var_dump($sql);
	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	// 取得した値をHTMLエンティティ
	$resultCount  = count($result);
    $tempStoreAry = array();
    $tempIdxAry   = array();
	for ($i=0; $i<$resultCount; $i++) {
		$result[$i]['requestDay'] = strtotime($result[$i]['AppliDay']);
		$result[$i]['requestNo']  = $result[$i]['AppliNo'];
		$result[$i]['orderId']    = $result[$i]['OrderID'];
		$result[$i]['CompCd']     = castHtmlEntity($result[$i]['AppliCompCd']);
		$result[$i]['CompName']   = castHtmlEntity($result[$i]['AppliCompName']);
		$result[$i]['staffCode']  = castHtmlEntity($result[$i]['StaffCode']);
        $result[$i]['personName']  = castHtmlEntity($result[$i]['PersonName']);

		// 申請番号の遷移先決定
		$result[$i]['isAppli'] = false;
		if (ereg('^A.*$', $result[$i]['AppliNo'])) {
			$result[$i]['isAppli'] = true;
		}

		// 出荷日
		$result[$i]['isEmptyShipDay'] = true;
		if (isset($result[$i]['ShipDay']) && $result[$i]['ShipDay'] != '') {
			$result[$i]['ShipDay']   = strtotime($result[$i]['ShipDay']);
			$result[$i]['isEmptyShipDay'] = false;
		}

		// 返却日
		$result[$i]['isEmptyReturnDay'] = true;
		if (isset($result[$i]['ReturnDay']) && $result[$i]['ReturnDay'] != '') {
			$result[$i]['ReturnDay']  = strtotime($result[$i]['ReturnDay']);
			$result[$i]['isEmptyReturnDay'] = false;
		}

		// 区分
		$result[$i]['divisionOrder']    = false;
		$result[$i]['divisionExchange'] = false;
		$result[$i]['divisionReturn']   = false;
		switch ($result[$i]['AppliMode']) {
			case APPLI_MODE_ORDER:						// 発注
				$result[$i]['divisionOrder']    = true;
				break;
			case APPLI_MODE_EXCHANGE:					// 交換
				$result[$i]['divisionExchange'] = true;
				break;
			case APPLI_MODE_RETURN:						// 返却
				$result[$i]['divisionReturn']   = true;
				break;
			default:
				break;
		}

		// 状態
		$result[$i]['statusName']  = castHtmlEntity($DISPLAY_STATUS[$result[$i]['Status']]);

		// 状態の文字列の色
		$result[$i]['statusIsBlue']  = false;
		$result[$i]['statusIsRed']   = false;
		$result[$i]['statusIsTeal']  = false;
		$result[$i]['statusIsGreen'] = false;
		$result[$i]['statusIsGray']  = false;
		$result[$i]['statusIsPink']  = false;
		$result[$i]['statusIsBlack'] = false;
		switch ($result[$i]['Status']) {
			case STATUS_APPLI:							// 申請済（承認待ち）
			case STATUS_STOCKOUT:						// 在庫切れ
				$result[$i]['statusIsGray']  = true;
				break;
			case STATUS_APPLI_DENY:						// 申請済（否認）
			case STATUS_NOT_RETURN_DENY:				// 未返却 （否認）
			case STATUS_LOSS_DENY:						// 紛失（否認）
				$result[$i]['statusIsPink']  = true;
				break;
			case STATUS_APPLI_ADMIT:					// 申請済（承認済）
				$result[$i]['statusIsGreen'] = true;
				break;
			case STATUS_ORDER:							// 受注済
				$result[$i]['statusIsBlue']  = true;
				break;
			case STATUS_NOT_RETURN:						// 未返却（承認待ち）
			case STATUS_NOT_RETURN_ADMIT:				// 未返却（承認済）
			case STATUS_NOT_RETURN_ORDER:				// 未返却（受注済）
				$result[$i]['statusIsRed']   = true;
				break;
			case STATUS_LOSS:							// 紛失（承認待ち）
			case STATUS_LOSS_ADMIT:						// 紛失（承認済）
			case STATUS_LOSS_ORDER:						// 紛失（受注済）
				$result[$i]['statusIsTeal']  = true;
				break;
			default:
				$result[$i]['statusIsBlack'] = true;
				break;
		}

		// 訂正
		$result[$i]['reasonIsPersonal']  = false;
		$result[$i]['reviseIsCancel']   = false;
		$result[$i]['reviseIsEmpty']    = true;
		switch ($result[$i]['AppliMode']) {
			case APPLI_MODE_ORDER:									// 発注
				switch ($result[$i]['Status']) {
					case STATUS_APPLI:								// 承認待
                    case STATUS_APPLI_ADMIT:                        // 申請済（承認済）
						$result[$i]['reviseIsEmpty']    = false;
                        $result[$i]['reasonIsPersonal']  = true;
                        $result[$i]['reviseIsCancel']   = false;
						break;

                    case STATUS_ORDER:                              // 受注済
                        $result[$i]['reviseIsEmpty']    = true;
                        $result[$i]['reasonIsPersonal']  = false;
                        $result[$i]['reviseIsCancel']   = false;
                        break;

                    case STATUS_SHIP;                               // 出荷済
                    case STATUS_DELIVERY;                           // 納品済
                        $result[$i]['reviseIsEmpty']    = true;
                        $result[$i]['reasonIsPersonal']  = false;
                        $result[$i]['reviseIsCancel']   = false;
					default:
						break;
				}
				break;
			case APPLI_MODE_EXCHANGE:								// 交換 
				switch ($result[$i]['Status']) {
					case STATUS_APPLI:								// 承認待
                    case STATUS_APPLI_ADMIT:                        // 申請済（承認済）
                        $result[$i]['reviseIsEmpty']    = false;
                        $result[$i]['reasonIsPersonal'] = false;
                        $result[$i]['reviseIsCancel']   = true;
                        break;

                    case STATUS_ORDER:                              // 受注済
                        $result[$i]['reviseIsEmpty']    = true;
                        $result[$i]['reasonIsPersonal']  = false;
                        $result[$i]['reviseIsCancel']   = false;
                        break;

					case STATUS_NOT_RETURN:							// 承認待
					case STATUS_NOT_RETURN_ADMIT:					// 未返却（承認済）
                        $result[$i]['reviseIsEmpty']    = false;
                        $result[$i]['reasonIsPersonal'] = false;
						$result[$i]['reviseIsCancel']   = true;
						break;

                    case STATUS_RETURN:                             // 返却済
                        $result[$i]['reviseIsEmpty']    = true;
                        $result[$i]['reasonIsPersonal']  = false;
                        $result[$i]['reviseIsCancel']   = false;
                        break;

					case STATUS_LOSS:								// 承認待
					case STATUS_LOSS_ADMIT:							// 紛失（承認済）
                        $result[$i]['reviseIsEmpty']    = false;
                        $result[$i]['reasonIsPersonal'] = false;
						$result[$i]['reviseIsCancel']   = true;
						break;

                    case STATUS_LOSS_ORDER:                         // 紛失
                        $result[$i]['reviseIsEmpty']    = true;
                        $result[$i]['reasonIsPersonal']  = false;
                        $result[$i]['reviseIsCancel']   = false;
                        break;

					default:
						break;
				}
				break;
			case APPLI_MODE_RETURN:									// 返却
				if ($result[$i]['AppliReason'] != APPLI_REASON_EXCHANGE_SIZE_RETURN) {	// サイズ交換キャンセル返却以外
					switch ($result[$i]['Status']) {
						case STATUS_NOT_RETURN:							// 未返却（承認待ち）
						case STATUS_NOT_RETURN_ADMIT:					// 未返却（承認済）
						case STATUS_NOT_RETURN_ORDER:					// 未返却（受注済）

							// Modified by Y.Furukawa at 17/07/29 退職返却申請後に発注申請があるかどうか（=ある場合はｷｬﾝｾﾙ不可（リンク無））
							if (getOrderCnt($dbConnect, $result[$i]['AppliDay'], $result[$i]['staffCode']) > 0) {
								$result[$i]['reviseIsCancel']   = false;
								$result[$i]['reviseIsEmpty']    = true;
							} else {
								$result[$i]['reviseIsCancel']   = true;
								$result[$i]['reviseIsEmpty']    = false;
							}
							//$result[$i]['reviseIsCancel']   = true;
							//$result[$i]['reviseIsEmpty']    = false;
							break;
						default:
							break;
					}
				}
				break;
			default:
				break;
		} 

        // 交換のORDERの場合は「返却」「発注」のどちらかが「受注済」に達した時点で両方をキャンセル不可にする
        if ($result[$i]['divisionExchange']) {
            $innerAppliNo = substr($result[$i]['AppliNo'], 1);
            $overWrite = false;
            $copy      = false;
            if ($result[$i]['isAppli']) {       // 発注申請の場合
                switch ($result[$i]['Status']) {
                    case STATUS_STOCKOUT:           // 在庫切れ
                    case STATUS_ORDER:              // 受注済
                    case STATUS_SHIP:               // 出荷済
                    case STATUS_DELIVERY:           // 納品済
                        $copy = true;
                        break;
                    case STATUS_APPLI_ADMIT:        // 申請済（承認済）
                        $overWrite = true;
                        break;
                    default:
                        break;
                }
            } else {    // 返却申請の場合
                switch ($result[$i]['Status']) {
                    case STATUS_NOT_RETURN_ADMIT:   // 未返却（承認済）
                        $overWrite = true;
                        break;
                    case STATUS_NOT_RETURN_ORDER:   // 未返却（受注済）
                    case STATUS_RETURN:             // 返却済
                        $copy = true;
                        break;
                    default:
                        break;
                }
            }
 
            if ($overWrite) {
                if (isset($tempIdxAry[$innerAppliNo]) && $tempIdxAry[$innerAppliNo] != '') {
                    $result[$i]['reviseIsEmpty']    = $result[$tempIdxAry[$innerAppliNo]]['reviseIsEmpty'];
                    $result[$i]['reasonIsPersonal'] = $result[$tempIdxAry[$innerAppliNo]]['reasonIsPersonal'];
                    $result[$i]['reviseIsCancel']   = $result[$tempIdxAry[$innerAppliNo]]['reviseIsCancel'];
                } else {
                    $tempIdxAry[$innerAppliNo] = (string)$i;
                }
            }
            if ($copy) {
                if (isset($tempIdxAry[$innerAppliNo]) && $tempIdxAry[$innerAppliNo] != '') {
                    $result[$tempIdxAry[$innerAppliNo]]['reviseIsEmpty']    = $result[$i]['reviseIsEmpty'];
                    $result[$tempIdxAry[$innerAppliNo]]['reasonIsPersonal'] = $result[$i]['reasonIsPersonal'];
                    $result[$tempIdxAry[$innerAppliNo]]['reviseIsCancel']   = $result[$i]['reviseIsCancel'];
                } else {
                    $tempIdxAry[$innerAppliNo] = (string)$i;
                }
            } 
 
        }        

	}

	return  $result;

}

/*
 * 返却申請後に発注申請がされているかどうか
 * 引数  ：$dbConnect      => コネクションハンドラ
 * 　　  ：$requestDay     => 発注ID
 * 　　  ：$staffCode      => 発注ID
 * 戻り値：返却済件数
 *
 * create 2017/07/29 Y.Furukawa
 *
 */
function getOrderCnt($dbConnect, $requestDay, $staffCode) {

	// 対象スタッフの退職返却申請後の発注申請の件数を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" COUNT(DISTINCT OrderID) as count_order";
	$sql .= " FROM";
	$sql .= 	" T_Order";
	$sql .= " WHERE";
	$sql .= 	" AppliDay >= '" . db_Escape($requestDay) . "'";
	$sql .= " AND";
	$sql .= 	" AppliMode = '" . db_Escape(APPLI_MODE_ORDER) . "'";
	$sql .= " AND";
	$sql .= 	" StaffCode = '" . db_Escape($staffCode) . "'";
	$sql .= " AND";
	$sql .= "   (Status = " . STATUS_APPLI . " OR Status = " . STATUS_APPLI_ADMIT . " OR Status = " . STATUS_STOCKOUT . " OR Status = " . STATUS_ORDER . " OR Status = " . STATUS_SHIP . " OR Status = " . STATUS_DELIVERY . ")";	// 承認待、申請済、在庫切、受注済、出荷済、納品済
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
	 	return 0;
	}

	return (int)$result[0]['count_order'];
}

/*
 * 検索条件を指定しているか判定
 * 引数  ：$post           => POST値
 * 戻り値：true：条件を指定している / false：条件を指定していない
 *
 * create 2007/04/06 H.Osugi
 *
 */
function checkCondition($post) {

	global $isLevelAdmin;	// 管理者権限の有無

	// 店舗IDの指定があった場合
	if ($isLevelAdmin == true) {
		if (isset($post['searchCompId']) && $post['searchCompId'] != '') {
			return true;
		}
	}

	// 申請番号の指定があった場合
	if ($isLevelAdmin == true) {
		if (isset($post['searchAppliNo']) && $post['searchAppliNo'] != '') {
			return true;
		}
	}

	// 申請日の指定があった場合
	if ($isLevelAdmin == true) {
		if (isset($post['searchAppliDayFrom']) && $post['searchAppliDayFrom'] != '') {
			return true;
		}
		if (isset($post['searchAppliDayTo']) && $post['searchAppliDayTo'] != '') {
			return true;
		}
	}

	// 出荷日の指定があった場合
	if ($isLevelAdmin == true) {
		if (isset($post['searchShipDayFrom']) && $post['searchShipDayFrom'] != '') {
			return true;
		}
		if (isset($post['searchShipDayTo']) && $post['searchShipDayTo'] != '') {
			return true;
		}
	}

	// スタッフコードの指定があった場合
	if (isset($post['searchStaffCode']) && $post['searchStaffCode'] != '') {
		return true;
	}

	// 単品番号の指定があった場合
	if (isset($post['searchBarCode']) && $post['searchBarCode'] != '') {
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
    <title>制服管理システム</title>
    <script src="//code.jquery.com/jquery-1.9.1.js"></script>
    <script src="//code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
    <script type="text/javascript">
      $(function() {
        $("#datepicker1").datepicker();
        $('#datepicker1').datepicker("option", "dateFormat", 'yy/mm/dd' );
        $("#datepicker1").datepicker("setDate", "<?php isset($searchAppliDayFrom) ? print($searchAppliDayFrom) : print('&#123;searchAppliDayFrom&#125;'); ?>");
        $("#datepicker2").datepicker();
        $('#datepicker2').datepicker("option", "dateFormat", 'yy/mm/dd' );
        $("#datepicker2").datepicker("setDate", "<?php isset($searchAppliDayTo) ? print($searchAppliDayTo) : print('&#123;searchAppliDayTo&#125;'); ?>");
        $("#datepicker3").datepicker();
        $('#datepicker3').datepicker("option", "dateFormat", 'yy/mm/dd' );
        $("#datepicker3").datepicker("setDate", "<?php isset($searchShipDayFrom) ? print($searchShipDayFrom) : print('&#123;searchShipDayFrom&#125;'); ?>");
        $("#datepicker4").datepicker();
        $('#datepicker4').datepicker("option", "dateFormat", 'yy/mm/dd' );
        $("#datepicker4").datepicker("setDate", "<?php isset($searchShipDayTo) ? print($searchShipDayTo) : print('&#123;searchShipDayTo&#125;'); ?>");
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
        

        <form method="post" action="./rireki.php" name="pagingForm">
          <div id="contents">
            <h1>ユニフォーム申請履歴</h1>
            <table border="0" width="700" cellpadding="0" cellspacing="0" class="tb_1">
              <tr height="40">
                <td width="80" class="line"><span class="fbold">所属施設</span></td>
<?php if(!$isLevelAdmin) { ?>
                <td colspan="3" class="line"><?php isset($compCd) ? print($compCd) : print('&#123;compCd&#125;'); ?>：<?php isset($compName) ? print($compName) : print('&#123;compName&#125;'); ?></td>
<?php } ?>
<?php if($isLevelAdmin) { ?>
                <td colspan="3" class="line">
                  <input type="text" name="searchCompCd" value="<?php isset($searchCompCd) ? print($searchCompCd) : print('&#123;searchCompCd&#125;'); ?>" style="width:60px" readonly="readonly"><input type="text" name="searchCompName" value="<?php isset($searchCompName) ? print($searchCompName) : print('&#123;searchCompName&#125;'); ?>" style="width:310px" readonly="readonly">
                  <input type="hidden" name="searchCompId" value="<?php isset($searchCompId) ? print($searchCompId) : print('&#123;searchCompId&#125;'); ?>">
                <input name="shop_btn" type="button" value="施設検索" onclick="window.open('../search_comp.php', 'searchComp', 'width=500, height=400, menubar=no, toolbar=no, scrollbars=yes'); return false;">
                </td>
<?php } ?>
                <td></td>
              </tr>
<?php if($isLevelAdmin) { ?>
              
              <tr height="40">
                <td width="80" class="line"><span class="fbold">申請番号</span></td>
                <td colspan="3" class="line"><input name="searchAppliNo" type="text" value="<?php isset($searchAppliNo) ? print($searchAppliNo) : print('&#123;searchAppliNo&#125;'); ?>" style="width:200px;" maxlength="13"></td>
              </tr>
              <tr height="40">
                <td width="80" class="line"><span class="fbold">申請日</span></td>
                <td colspan="3" width="150" class="line"><input name="searchAppliDayFrom" type="text" value="<?php isset($searchAppliDayFrom) ? print($searchAppliDayFrom) : print('&#123;searchAppliDayFrom&#125;'); ?>" style="width:100px;" maxlength="10" id="datepicker1">～ <input name="searchAppliDayTo" type="text" value="<?php isset($searchAppliDayTo) ? print($searchAppliDayTo) : print('&#123;searchAppliDayTo&#125;'); ?>" style="width:100px;" maxlength="10" id="datepicker2"></td>
              </tr>
              <tr height="40">
                <td width="80" class="line"><span class="fbold">出荷日</span></td>
                <td colspan="3" width="150" class="line"><input name="searchShipDayFrom" type="text" value="<?php isset($searchShipDayFrom) ? print($searchShipDayFrom) : print('&#123;searchShipDayFrom&#125;'); ?>" style="width:100px;" maxlength="10" id="datepicker3">～ <input name="searchShipDayTo" type="text" value="<?php isset($searchShipDayTo) ? print($searchShipDayTo) : print('&#123;searchShipDayTo&#125;'); ?>" style="width:100px;" maxlength="10" id="datepicker4"></td>
              </tr>
              
<?php } ?>
              <tr height="40">
                <td width="80" class="line"><span class="fbold">職員コード</span></td>
                <td width="210" class="line"><input name="searchStaffCode" type="text" value="<?php isset($searchStaffCode) ? print($searchStaffCode) : print('&#123;searchStaffCode&#125;'); ?>" style="width:140px;" maxlength="12"></td>
                <td width="80" class="line" align="center"><span class="fbold">単品番号</span></td>
                <td width="210" class="line"><input name="searchBarCode" type="text" value="<?php isset($searchBarCode) ? print($searchBarCode) : print('&#123;searchBarCode&#125;'); ?>" style="width:140px;"></td>
              </tr>
              <tr height="30">
                <td width="80"><span class="fbold">状態</span></td>
                <td colspan="3">
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
                        <label for="status3"><?php isset($DISPLAY_STATUS_ORDER) ? print($DISPLAY_STATUS_ORDER) : print('&#123;DISPLAY_STATUS_ORDER&#125;'); ?></label>
<?php if($isSelectedStatus4) { ?>
                        <input type="checkbox" name="searchStatus[]" value="4" id="status4" checked="checked">
<?php } ?>
<?php if(!$isSelectedStatus4) { ?>
                        <input type="checkbox" name="searchStatus[]" value="4" id="status4">
<?php } ?>
                        <label for="status4"><?php isset($DISPLAY_STATUS_SHIP) ? print($DISPLAY_STATUS_SHIP) : print('&#123;DISPLAY_STATUS_SHIP&#125;'); ?></label>
<?php if($isSelectedStatus5) { ?>
                        <input type="checkbox" name="searchStatus[]" value="5" id="status5" checked="checked">
<?php } ?>
<?php if(!$isSelectedStatus5) { ?>
                        <input type="checkbox" name="searchStatus[]" value="5" id="status5">
<?php } ?>
                        <label for="status5"><?php isset($DISPLAY_STATUS_DELIVERY) ? print($DISPLAY_STATUS_DELIVERY) : print('&#123;DISPLAY_STATUS_DELIVERY&#125;'); ?></label>
                </td>
                <td align="center">
                	<input type="button" value="     検索     " onclick="document.pagingForm.searchFlg.value='1'; document.pagingForm.submit(); return false;">
                </td>
              </tr>
              <tr height="30">
              	<td width="80" class="line"></td>
                <td class="line" colspan="3">
<?php if($isSelectedStatus6) { ?>
                        <input type="checkbox" name="searchStatus[]" value="6" id="status6" checked="checked">
<?php } ?>
<?php if(!$isSelectedStatus6) { ?>
                        <input type="checkbox" name="searchStatus[]" value="6" id="status6">
<?php } ?>
                        <label for="status6"><?php isset($DISPLAY_STATUS_STOCKOUT) ? print($DISPLAY_STATUS_STOCKOUT) : print('&#123;DISPLAY_STATUS_STOCKOUT&#125;'); ?></label>
<?php if($isSelectedStatus7) { ?>
                        <input type="checkbox" name="searchStatus[]" value="7" id="status7" checked="checked">
<?php } ?>
<?php if(!$isSelectedStatus7) { ?>
                        <input type="checkbox" name="searchStatus[]" value="7" id="status7">
<?php } ?>
                        <label for="status7"><?php isset($DISPLAY_STATUS_NOT_RETURN) ? print($DISPLAY_STATUS_NOT_RETURN) : print('&#123;DISPLAY_STATUS_NOT_RETURN&#125;'); ?></label>
<?php if($isSelectedStatus8) { ?>
                        <input type="checkbox" name="searchStatus[]" value="8" id="status8" checked="checked">
<?php } ?>
<?php if(!$isSelectedStatus8) { ?>
                        <input type="checkbox" name="searchStatus[]" value="8" id="status8">
<?php } ?>
                        <label for="status8"><?php isset($DISPLAY_STATUS_RETURN) ? print($DISPLAY_STATUS_RETURN) : print('&#123;DISPLAY_STATUS_RETURN&#125;'); ?></label>
<?php if($isSelectedStatus9) { ?>
                        <input type="checkbox" name="searchStatus[]" value="9" id="status9" checked="checked">
<?php } ?>
<?php if(!$isSelectedStatus9) { ?>
                        <input type="checkbox" name="searchStatus[]" value="9" id="status9">
<?php } ?>
                        <label for="status9"><?php isset($DISPLAY_STATUS_LOSS) ? print($DISPLAY_STATUS_LOSS) : print('&#123;DISPLAY_STATUS_LOSS&#125;'); ?></label>
                </td>
                <td class="line" align="center">
<?php if($isLevelAdmin) { ?>
<?php if(!$isSelectedAdmin) { ?>
                		<input type="button" value=" ダウンロード " disabled="disabled">
<?php } ?>
<?php if($isSelectedAdmin) { ?>
                		<input type="button" value=" ダウンロード " onclick="document.pagingForm.action='./rireki_csv_dl.php'; document.pagingForm.submit(); document.pagingForm.action='./rireki.php'; return false;">
<?php } ?>
<?php } ?>
<?php if(!$isLevelAdmin) { ?>
<?php } ?>
                </td>
              </tr>
            </table>
<?php if($isSelectedAdmin) { ?>
            <h3>◆着用状況</h3>
            <table width="720" border="0" class="tb_1" cellpadding="0" cellspacing="3">
              <tr>
                <th width="60">申請日</th>
                <th width="90">申請番号</th>
                <th width="150">施設名</th>
                <th width="120">職員名</th>
                <th width="40">区分</th>
                <th width="60">出荷日</th>
                <th width="60">返却日</th>
                <th width="60" nowrap="nowrap">状態</th>
<?php if(!$isLevelAgency) { ?>
                <th width="60">訂正</th>
<?php } ?>
              </tr>
<?php for ($i1_orders=0; $i1_orders<count($orders); $i1_orders++) { ?>
              <tr height="20">
                <td class="line2" align="center"><?php isset($orders[$i1_orders]['requestDay']) ? print(date("y/m/d", $orders[$i1_orders]['requestDay'])) : print('&#123;dateFormat(orders.requestDay, "y/m/d")&#125;'); ?></td>
                <td class="line2" align="center">
<?php if(!$orders[$i1_orders]['isAppli']) { ?>
                  <a href="#" onclick="document.pagingForm.orderId.value='<?php isset($orders[$i1_orders]['orderId']) ? print($orders[$i1_orders]['orderId']) : print('&#123;orders.orderId&#125;'); ?>'; document.pagingForm.action='./henpin_meisai.php'; document.pagingForm.submit(); return false;"><?php isset($orders[$i1_orders]['requestNo']) ? print($orders[$i1_orders]['requestNo']) : print('&#123;orders.requestNo&#125;'); ?></a></td>
<?php } ?>
<?php if($orders[$i1_orders]['isAppli']) { ?>
                  
                  <a href="#" onclick="document.pagingForm.orderId.value='<?php isset($orders[$i1_orders]['orderId']) ? print($orders[$i1_orders]['orderId']) : print('&#123;orders.orderId&#125;'); ?>'; document.pagingForm.action='./hachu_meisai.php'; document.pagingForm.submit(); return false;"><?php isset($orders[$i1_orders]['requestNo']) ? print($orders[$i1_orders]['requestNo']) : print('&#123;orders.requestNo&#125;'); ?></a></td>
                  
<?php } ?>
                <td class="line2" align="left"><?php isset($orders[$i1_orders]['CompName']) ? print($orders[$i1_orders]['CompName']) : print('&#123;orders.CompName&#125;'); ?></td>
                <td class="line2" align="left"><?php isset($orders[$i1_orders]['personName']) ? print($orders[$i1_orders]['personName']) : print('&#123;orders.personName&#125;'); ?></td>
                <td class="line2" align="center">
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
                <td class="line2" align="center">
<?php if($orders[$i1_orders]['isEmptyShipDay']) { ?>
                  
                  &nbsp;
                  
<?php } ?>
<?php if(!$orders[$i1_orders]['isEmptyShipDay']) { ?>
                  <?php isset($orders[$i1_orders]['ShipDay']) ? print(date("y/m/d", $orders[$i1_orders]['ShipDay'])) : print('&#123;dateFormat(orders.ShipDay, "y/m/d")&#125;'); ?>
<?php } ?>
                </td>
                <td class="line2" align="center">
<?php if($orders[$i1_orders]['isEmptyReturnDay']) { ?>
                  
                  &nbsp;
                  
<?php } ?>
<?php if(!$orders[$i1_orders]['isEmptyReturnDay']) { ?>
                  <?php isset($orders[$i1_orders]['ReturnDay']) ? print(date("y/m/d", $orders[$i1_orders]['ReturnDay'])) : print('&#123;dateFormat(orders.ReturnDay, "y/m/d")&#125;'); ?>
<?php } ?>
                </td>
                <td class="line2" align="center" nowrap="nowrap">
<?php if($orders[$i1_orders]['statusIsBlue']) { ?>
                  
                  <span style="color:blue"><?php isset($orders[$i1_orders]['statusName']) ? print($orders[$i1_orders]['statusName']) : print('&#123;orders.statusName&#125;'); ?></span>
                  
<?php } ?>
<?php if($orders[$i1_orders]['statusIsRed']) { ?>
                  
                  <span style="color:red"><?php isset($orders[$i1_orders]['statusName']) ? print($orders[$i1_orders]['statusName']) : print('&#123;orders.statusName&#125;'); ?></span>
                  
<?php } ?>
<?php if($orders[$i1_orders]['statusIsTeal']) { ?>
                  
                  <span style="color:Teal"><?php isset($orders[$i1_orders]['statusName']) ? print($orders[$i1_orders]['statusName']) : print('&#123;orders.statusName&#125;'); ?></span>
                  
<?php } ?>
<?php if($orders[$i1_orders]['statusIsGreen']) { ?>
                  
                  <span style="color:green"><?php isset($orders[$i1_orders]['statusName']) ? print($orders[$i1_orders]['statusName']) : print('&#123;orders.statusName&#125;'); ?></span>
                  
<?php } ?>
<?php if($orders[$i1_orders]['statusIsGray']) { ?>
                  
                  <span style="color:gray"><?php isset($orders[$i1_orders]['statusName']) ? print($orders[$i1_orders]['statusName']) : print('&#123;orders.statusName&#125;'); ?></span>
                  
<?php } ?>
<?php if($orders[$i1_orders]['statusIsPink']) { ?>
                  
                  <span style="color:fuchsia"><?php isset($orders[$i1_orders]['statusName']) ? print($orders[$i1_orders]['statusName']) : print('&#123;orders.statusName&#125;'); ?></span>
                  
<?php } ?>
<?php if($orders[$i1_orders]['statusIsBlack']) { ?>
                  <?php isset($orders[$i1_orders]['statusName']) ? print($orders[$i1_orders]['statusName']) : print('&#123;orders.statusName&#125;'); ?>
<?php } ?>
                </td>
                <td class="line2" align="center">
                  
<?php if($orders[$i1_orders]['reasonIsPersonal']) { ?>
<?php if($orders[$i1_orders]['divisionOrder']) { ?>
                    <a href="#" onclick="document.pagingForm.orderId.value='<?php isset($orders[$i1_orders]['orderId']) ? print($orders[$i1_orders]['orderId']) : print('&#123;orders.orderId&#125;'); ?>'; document.pagingForm.action='../hachu/hachu_shinsei.php'; document.pagingForm.submit(); return false;">変更</a>
<?php } ?>
<?php if($orders[$i1_orders]['divisionReturn']) { ?>
                    <a href="#" onclick="document.pagingForm.orderId.value='<?php isset($orders[$i1_orders]['orderId']) ? print($orders[$i1_orders]['orderId']) : print('&#123;orders.orderId&#125;'); ?>'; document.pagingForm.action='../koukan/koukan_shinsei.php'; document.pagingForm.submit(); return false;">変更</a>
<?php } ?>
<?php if($orders[$i1_orders]['divisionExchange']) { ?>
                    <a href="#" onclick="document.pagingForm.orderId.value='<?php isset($orders[$i1_orders]['orderId']) ? print($orders[$i1_orders]['orderId']) : print('&#123;orders.orderId&#125;'); ?>'; document.pagingForm.action='../henpin/henpin_shinsei.php'; document.pagingForm.submit(); return false;">変更</a>
<?php } ?>
<?php } ?>
<?php if($orders[$i1_orders]['reviseIsCancel']) { ?>
                   
                   <a href="#" onclick="document.pagingForm.orderId.value='<?php isset($orders[$i1_orders]['orderId']) ? print($orders[$i1_orders]['orderId']) : print('&#123;orders.orderId&#125;'); ?>'; document.pagingForm.action='./cancel_kakunin.php'; document.pagingForm.submit(); return false;">ｷｬﾝｾﾙ</a>
                   
<?php } ?>
<?php if($orders[$i1_orders]['reviseIsEmpty']) { ?>
                   &nbsp;
<?php } ?>
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
          </div>
          <input type="hidden" name="encodeHint" value="京">
          <input type="hidden" name="orderId">
          <input type="hidden" name="searchFlg">
          <input type="hidden" name="rirekiFlg" value="1">
          <input type="hidden" name="isSelectedAdmin" value="<?php isset($isSelectedAdmin) ? print($isSelectedAdmin) : print('&#123;isSelectedAdmin&#125;'); ?>">
        </form>
        <br><br><br>
        

      </div>
    </div>
  </body>
</html>
<?php
/*
 * 着用状況CSV出力
 * chakuyou_csv_dl.src.php
 *
 * create 2007/05/08 H.Osugi
 *
 *
 */

// 出力する際の文字コードを設定
mb_internal_encoding('SJIS');
mb_http_output('SJIS');
ob_start('mb_output_handler');

// 各種モジュールの取得
include_once('../../include/define.php');			// 定数定義
require_once('../../include/dbConnect.php');		// DB接続モジュール
require_once('../../include/msSqlControl.php');		// DB操作モジュール
require_once('../../include/checkLogin.php');		// ログイン判定モジュール
require_once('../../include/redirectPost.php');		// リダイレクトポストモジュール

// 制限時間の解除
set_time_limit(0);

// 管理権限の場合は店舗IDが取得できなければエラーに
if ($isLevelAdmin == false && $isLevelAgency == false) {

	$returnUrl             = HOME_URL . 'top.php';
	
	// TOP画面に強制遷移
	redirectPost($returnUrl, $hiddens);

} 

// 出力する着用状況一覧を取得
$outputDatas = getOrderDetail($dbConnect, $_POST);

// ヘッダの生成
header('Cache-Control: public');
header('Pragma: public');
// header('Content-Disposition: inline ; filename=' . mb_convert_encoding(CHAKUYOU_CSV_FILE_NAME . '.csv', 'SJIS', 'auto'));
// header('Content-type: text/octet-stream') ;
header('Content-Disposition: attachment; filename=' . mb_convert_encoding(CHAKUYOU_CSV_FILE_NAME . '.csv', 'SJIS', 'auto'));
header('Content-type: text/comma-separated-values');

// 項目名
$header  = '申請日,';
$header .= '申請番号,';
$header .= '基地コード,';
$header .= '基地名,';
$header .= '職員コード,';
$header .= '職員氏名,';
$header .= '区分,';
$header .= '出荷日,';
$header .= '返却日,';
$header .= 'アイテム名,';
$header .= 'サイズ,';
$header .= '単品番号,';
$header .= 'ICタグコード,';
$header .= '状態' . "\n";

print(mb_convert_encoding($header, 'SJIS', 'auto'));

$countDatas = count($outputDatas);
for ($i=0; $i<$countDatas; $i++) {

	$status    = $DISPLAY_STATUS[$outputDatas[$i]['Status']];
	$appliMode = $DISPLAY_APPLI_MODE[$outputDatas[$i]['AppliMode']];

	print($outputDatas[$i]['AppliDay'] . ',');							// 申請日
	print($outputDatas[$i]['AppliNo'] . ',');							// 申請番号
	print('="' . $outputDatas[$i]['AppliCompCd'] . '",');				// 店舗コード
	print($outputDatas[$i]['AppliCompName'] . ',');						// 店舗名
	print($outputDatas[$i]['StaffCode'] . ',');							// 職員コード
	print($outputDatas[$i]['PersonName'] . ',');						// 職員氏名
	print(mb_convert_encoding($appliMode, 'SJIS', 'auto') . ',');		// 区分
	print($outputDatas[$i]['ShipDay'] . ',');							// 出荷日
	print($outputDatas[$i]['ReturnDay'] . ',');							// 返却日
	print($outputDatas[$i]['ItemName'] . ',');							// アイテム名
	print($outputDatas[$i]['Size'] . ',');								// サイズ
	print('="' . $outputDatas[$i]['BarCd'] . '",');						// 単品番号
	print('="' . $outputDatas[$i]['IcTagCd'] . '",');					// ICタグコード
	print(mb_convert_encoding($status, 'SJIS', 'auto') . "\n");		// 状態

}

/*
 * 着用状況一覧情報を取得する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$post           => POST値
 * 戻り値：$result         => 着用状況一覧情報
 *
 * create 2007/05/08 H.Osugi
 *
 */
function getOrderDetail($dbConnect, $post) {

	global $isLevelAgency;
	global $isLevelAdmin;
	global $isLevelItc;
	global $isLevelHonbu;

	// 初期化
	$compId    = '';
	$staffCode = '';
	$barCode   = '';
	$status    = '';
	$offset    = '';
	$corpCode = '';

	// 取得したいデータの開始位置
	$offset = ($nowPage - 1);

	// 店舗ID
	//$compId = $post['searchCompId'];

	// 店舗ID
	if ($isLevelAdmin == true) {	// 管理者権限
		// 選択店舗コードがあればＩＤ取得
		if (isset($post['searchCompId']) && trim($post['searchCompId']) != '') {
			$compId = $post['searchCompId'];
		} else {
			$compId = '';
		}
	} else {						// 一般権限
		$compId = $_SESSION['COMPID'];		// ログインID
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

	// 職員コード
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
			case '2':
				$status .= 	" " . STATUS_APPLI_ADMIT;		// 申請済（承認済）
				break;
			case '3':
				$status .= 	" " . STATUS_ORDER;				// 受注済
				break;
			case '4':
				$status .= 	" " . STATUS_SHIP;				// 出荷済
				break;
			case '5':
				$status .= 	" " . STATUS_DELIVERY;			// 納品済
				break;
			case '6':
				$status .= 	" " . STATUS_STOCKOUT;			// 在庫切れ
				break;
			case '7':
				$status .= 	" " . STATUS_NOT_RETURN_ADMIT;	// 未返却（承認済）
				$status .= ",";
				$status .= 	" " . STATUS_NOT_RETURN_ORDER;	// 未返却（受注済）
				break;
			default:
				break;
		}

		if ($i != $countStatus -1) {
			$status .= ",";
		}

	}

	// 状態が何も選択されていない場合
	if ($countStatus <= 0) {

		$status  = 	" " . STATUS_APPLI;				// 申請済（承認待ち）
		$status .= ",";
		$status .= 	" " . STATUS_APPLI_ADMIT;		// 申請済（承認済）
		$status .= ",";
		$status .= 	" " . STATUS_STOCKOUT;			// 在庫切れ
		$status .= ",";
		$status .= 	" " . STATUS_ORDER;				// 受注済
		$status .= ",";
		$status .= 	" " . STATUS_SHIP;				// 出荷済
		$status .= ",";
		$status .= 	" " . STATUS_DELIVERY;			// 納品済
		$status .= ",";
		$status .= 	" " . STATUS_NOT_RETURN;		// 未返却（承認待ち）
		$status .= ",";
		$status .= 	" " . STATUS_NOT_RETURN_ADMIT;	// 未返却（承認済）
		$status .= ",";
		$status .= 	" " . STATUS_NOT_RETURN_ORDER;	// 未返却（受注済）
		$status .= ",";
		$status .= 	" " . STATUS_RETURN_NOT_APPLY;	// 返却未申請
		$status .= ",";
		$status .= 	" " . STATUS_LOSS;				// 紛失（承認待ち）

	}

	// 着用状況の一覧を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" DISTINCT";
	$sql .= 	" tod.OrderDetID,";
	$sql .= 	" tod.AppliNo,";
	$sql .= 	" CONVERT(char, tor.AppliDay, 111) AS AppliDay,";
	$sql .= 	" tor.AppliCompCd,";
	$sql .= 	" tor.AppliCompName,";
	$sql .= 	" tor.StaffCode,";
	$sql .= 	" tor.PersonName,";
	$sql .= 	" tor.AppliMode,";
	$sql .= 	" CONVERT(char, tor.ShipDay, 111) AS ShipDay,";
	$sql .= 	" CONVERT(char, tor.ReturnDay, 111) AS ReturnDay,";
	$sql .= 	" tod.ItemName,";
	$sql .= 	" tod.Size,";
	$sql .= 	" tod.BarCd,";
	$sql .= 	" tod.IcTagCd,";
	$sql .= 	" tsd.Status,";
	$sql .= 	" tod.ItemID,";
	$sql .= 	" mi.CleaningPrice";	// Modified by T.Uno at 10/05/25 クリーニング単価追加
	$sql .= " FROM";
	$sql .= 	" T_Order tor";
	$sql .= " INNER JOIN";
	$sql .= 	" T_Order_Details tod";
	$sql .= " ON";
	$sql .= 	" tor.OrderID = tod.OrderID";
	$sql .= " AND";
	$sql .= 	" tod.Del= " . DELETE_OFF;

	$sql .= " INNER JOIN";
	$sql .= 	" M_Item mi";
	$sql .= " ON";
	$sql .= 	" tod.ItemID = mi.ItemID";
	$sql .= " AND";
	$sql .= 	" mi.Del= " . DELETE_OFF;

	$sql .= " INNER JOIN";
	$sql .= 	" (";
	$sql .= 		" (";
	$sql .= 			" SELECT";
	$sql .= 				" DISTINCT";
	$sql .= 				" tod_uni1.OrderDetID,";
	$sql .= 				" tsd_uni1.StaffID,";
	$sql .= 				" tsd_uni1.Status";
	$sql .= 			" FROM";
	$sql .= 				" T_Order_Details tod_uni1";
	$sql .= 			" INNER JOIN";
	$sql .= 				" T_Staff_Details tsd_uni1";
	$sql .= 			" ON";
	$sql .= 				" tod_uni1.OrderDetID = tsd_uni1.OrderDetID";
	$sql .= 			" AND";
	$sql .= 				" tsd_uni1.ReturnDetID is NULL";
	$sql .= 			" AND";
	$sql .= 				" tsd_uni1.Del = " . DELETE_OFF;
	$sql .= 			" WHERE";
	$sql .= 				" tod_uni1.Del = " . DELETE_OFF;

	// 状態の指定があった場合
	if ($status != '') {
		$sql .= 		" AND";
		$sql .= 			" tsd_uni1.Status IN (";
		$sql .= 				$status;
		$sql .= 			" )";
	}

	$sql .= 		" )";
	$sql .= 	" UNION ALL";
	$sql .= 		" (";
	$sql .= 			" SELECT";
	$sql .= 				" DISTINCT";
	$sql .= 				" tod_uni2.OrderDetID,";
	$sql .= 				" tsd_uni2.StaffID,";
	$sql .= 				" tsd_uni2.Status";
	$sql .= 			" FROM";
	$sql .= 				" T_Order_Details tod_uni2";
	$sql .= 			" INNER JOIN";
	$sql .= 				" T_Staff_Details tsd_uni2";
	$sql .= 			" ON";
	$sql .= 				" tod_uni2.OrderDetID = tsd_uni2.ReturnDetID";
	$sql .= 			" AND";
	$sql .= 				" tsd_uni2.Del = " . DELETE_OFF;
	$sql .= 			" WHERE";
	$sql .= 				" tod_uni2.Del = " . DELETE_OFF;

	// 状態の指定があった場合
	if ($status != '') {
		$sql .= 		" AND";
		$sql .= 			" tsd_uni2.Status IN (";
		$sql .= 				$status;
		$sql .= 			" )";
	}

	$sql .= 		" )";
	$sql .= 	" ) tsd";
	$sql .= " ON";
	$sql .= 	" tod.OrderDetID = tsd.OrderDetID";

	$sql .= " INNER JOIN";
	$sql .= 	" M_Comp mc";
	$sql .= " ON";
	$sql .= 	" tor.CompID = mc.CompID";
	$sql .= " AND";
	$sql .= 	" mc.Del = " . DELETE_OFF;
	$sql .= " AND";
    $sql .=     " mc.ShopFlag <> 0";        // ShopFlagが0以外の店舗を表示する

	$sql .= " INNER JOIN";
	$sql .= 	" T_Staff ts";
	$sql .= " ON";
	$sql .= 	" tor.StaffCode = ts.StaffCode";
	$sql .= " AND";
	$sql .= 	" tor.CompID = ts.CompID";
	////$sql .= " AND";
	////$sql .= 	" ts.AllReturnFlag = 0";
	$sql .= " AND";
	$sql .= 	" ts.Del = " . DELETE_OFF;

	// 店舗の指定があった場合
	if ($compId != '') {
		$sql .= " AND";
		$sql .= 	" ts.CompID = " . db_Escape($compId);
	}

	// 【支店】検索
	if ($corpCode != '') {
		$sql .= " AND";
		$sql .= 	" mc.CorpCd = " . db_Escape($corpCode);
	}

	if ($honbuCd != '') {
		$sql .= " AND";
		$sql .= 	" mc.HonbuCd = '" . db_Escape($honbuCd) . "'";
	}

	if ($shibuCd != '') {
		$sql .= " AND";
		$sql .= 	" mc.ShibuCd = '" . db_Escape($shibuCd) . "'";
	}

	// 職員コードの指定があった場合
	if ($staffCode != '') {
		$sql .= " AND";
		$sql .= 	" ts.StaffCode = '" . db_Escape($staffCode) . "'";
	}

	$sql .= " WHERE";
	$sql .= 	" tor.Del= " . DELETE_OFF;

	// 単品番号の指定があった場合
	if ($barCode != '') {
		$sql .= " AND";
		$sql .= 	" tod.BarCd = '" . db_Escape($barCode) . "'";
	}

	$sql .= " ORDER BY";
	$sql .= 	" tor.StaffCode ASC,";
	$sql .= 	" tod.ItemID ASC,";
	$sql .= 	" tsd.Status ASC";

	$result = db_Read_Csv($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	return  $result;

}


?>
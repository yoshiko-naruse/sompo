<?php
/*
 * 履歴CSV出力
 * rireki_csv_dl.src.php
 *
 * create 2007/05/09 H.Osugi
 *
 *
 */

// 出力する際の文字コードを設定
mb_internal_encoding('SJIS-WIN');
mb_http_output('SJIS-WIN');
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
header('Content-Disposition: attachment; filename=' . mb_convert_encoding(RIREKI_CSV_FILE_NAME . '.csv', 'SJIS-WIN', 'auto'));
header('Content-type: text/comma-separated-values');

// 項目名
$header  = '申請日,';
$header .= '申請番号,';
$header .= '施設コード,';
$header .= '施設名,';
$header .= '職員コード,';
$header .= '職員氏名,';
$header .= '区分,';
$header .= '区分詳細,';
$header .= '出荷指定日,';
$header .= '出荷日,';
$header .= '返却日,';
$header .= 'アイテム名,';
$header .= 'サイズ,';
$header .= '単品番号,';
$header .= 'ICタグコード,';
$header .= '状態,';
$header .= 'メモ' . "\n";

print(mb_convert_encoding($header, 'SJIS-WIN', 'auto'));

// 定数を配列にセット
$appliReasonAryJp = array(
		APPLI_REASON_ORDER_BASE      => 'そんぽの家系／ラヴィーレ系',
		APPLI_REASON_ORDER_GRADEUP   => 'グレードアップタイ',
		APPLI_REASON_ORDER_FRESHMAN   => '新入社員',
		APPLI_REASON_RETURN_RETIRE   => '退職・異動',
		APPLI_REASON_EXCHANGE_SIZE   => 'サイズ',
		APPLI_REASON_EXCHANGE_INFERIORITY => '不良品',
		APPLI_REASON_EXCHANGE_LOSS => '紛失',
		APPLI_REASON_EXCHANGE_BREAK => '汚損・破損'
);

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
	print(mb_convert_encoding($appliMode, 'SJIS-WIN', 'auto') . ',');	// 区分
	print(mb_convert_encoding($appliReasonAryJp[$outputDatas[$i]['AppliReason']], 'SJIS-WIN', 'auto') . ',');	// 区分詳細
	print($outputDatas[$i]['YoteiDay'] . ',');							// 出荷指定日
	print($outputDatas[$i]['ShipDay'] . ',');							// 出荷日
	print($outputDatas[$i]['ReturnDay'] . ',');							// 返却日
	print($outputDatas[$i]['ItemName'] . ',');							// アイテム名
	print($outputDatas[$i]['Size'] . ',');								// サイズ
	print('="' . $outputDatas[$i]['BarCd'] . '",');						// 単品番号
	print('="' . $outputDatas[$i]['IcTagCd'] . '",');					// ICタグコード
	print(mb_convert_encoding($status, 'SJIS-WIN', 'auto') . ',');		// 状態
	print($outputDatas[$i]['Note'] . "\n");			// メモ

}

/*
 * 着用状況一覧情報を取得する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$post           => POST値
 * 戻り値：$result         => 着用状況一覧情報
 *
 * create 2007/05/09 H.Osugi
 *
 */
function getOrderDetail($dbConnect, $post) {

	global $isLevelAgency;
	global $isLevelAdmin;
	global $isLevelItc;
	global $isLevelHonbu;

	// 初期化
	$compId    = '';
	$appliNo      = '';
	$appliDayFrom = '';
	$appliDayTo   = '';
	$shipDayFrom  = '';
	$shipDayTo    = '';
	$staffCode    = '';
	$barCode      = '';
	$status       = '';
	$offset       = '';
	$corpCode     = '';
    $honbuCd      = '';
    $shibuCd      = '';

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

	// 申請番号
	$appliNo = $post['searchAppliNo'];

	// 申請日
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


	// 出荷日
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
			case '1':
				$status .= 	" " . STATUS_APPLI;				// 申請済（承認待ち）
				break;
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
				$status .= 	" " . STATUS_NOT_RETURN;		// 未返却（承認待ち）
				$status .= ",";
				$status .= 	" " . STATUS_NOT_RETURN_ADMIT;	// 未返却（承認済）
				$status .= ",";
				$status .= 	" " . STATUS_NOT_RETURN_ORDER;	// 未返却（受注済）
				break;
			case '8':
				$status .= 	" " . STATUS_RETURN;				// 返却済
				break;
			case '9':
				$status .= 	" " . STATUS_LOSS;					// 紛失（承認待ち）
				$status .= ",";
				$status .= 	" " . STATUS_LOSS_ADMIT;			// 紛失（承認済）
				$status .= ",";
				$status .= 	" " . STATUS_LOSS_ORDER;			// 紛失（受注済）
				break;
			default:
				break;
		}

		if ($i != $countStatus -1) {
			$status .= ",";
		}

	}

	// 履歴の一覧を取得する
	$sql  = "";
	$sql .= " SELECT";
	//$sql .= 	" DISTINCT";
	$sql .= 	" tor.OrderID,";
	$sql .= 	" tod.OrderDetID,";
	$sql .= 	" tod.AppliNo,";
	$sql .= 	" CONVERT(char, tod.AppliDay, 111) AS AppliDay,";
	$sql .= 	" tor.AppliCompCd,";
	$sql .= 	" tor.AppliCompName,";
	$sql .= 	" tor.StaffCode,";
	$sql .=		" tor.PersonName,";
	$sql .= 	" tor.AppliMode,";
	$sql .= 	" tor.AppliReason,";
	$sql .= 	" tor.Note,";
	$sql .= 	" CONVERT(char, tor.YoteiDay, 111) AS YoteiDay,";
	$sql .= 	" CONVERT(char, tod.ShipDay, 111) AS ShipDay,";
	$sql .= 	" CONVERT(char, tod.ReturnDay, 111) AS ReturnDay,";
	$sql .= 	" tod.ItemName,";
	$sql .= 	" tod.Size,";
	$sql .= 	" tod.BarCd,";
	$sql .= 	" tod.IcTagCd,";
	$sql .= 	" tod.Status,";
	$sql .= 	" tod.ItemID";
	$sql .= " FROM";
	$sql .= 	" T_Order tor";
	$sql .= " INNER JOIN";
	$sql .= 	" T_Order_Details tod";
	$sql .= " ON";
	$sql .= 	" tor.OrderID = tod.OrderID";
	$sql .= " AND";
	$sql .= 	" tod.Del= " . DELETE_OFF;

	$sql .= " INNER JOIN";
	$sql .= 	" M_Comp mc";
	$sql .= " ON";
	$sql .= 	" tor.CompID = mc.CompID";
	$sql .= " AND";
	$sql .= 	" mc.Del = " . DELETE_OFF;
	$sql .= " AND";
    $sql .=     " mc.ShopFlag <> 0";        // ShopFlagが0以外の店舗を表示する

	$sql .= " WHERE";
	$sql .= 	" tor.Del= " . DELETE_OFF;

	// 店舗の指定があった場合
	if ($compId != '') {
		$sql .= " AND";
		$sql .= 	" tor.CompID = " . db_Escape($compId);
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

	// 申請番号を前方一致
	if ($appliNo != '') {
		$sql .= " AND";
		$sql .= 	" tor.AppliNo LIKE ('" . db_Like_Escape($appliNo) . "%')";
	}

	// 申請日の指定があった場合
	if ($appliDayFrom != '') {
		$sql .= " AND";
		$sql .= 	" CONVERT(char, tor.AppliDay, 111) >= '" . db_Escape($appliDayFrom) . "'";
	}
	if ($appliDayTo != '') {
		$sql .= " AND";
		$sql .= 	" CONVERT(char, tor.AppliDay, 111) <= '" . db_Escape($appliDayTo) . "'";
	}

	// 出荷日の指定があった場合
	if ($shipDayFrom != '') {
		$sql .= " AND";
		$sql .= 	" CONVERT(char, tod.ShipDay, 111) >= '" . db_Escape($shipDayFrom) . "'";
	}
	if ($shipDayTo != '') {
		$sql .= " AND";
		$sql .= 	" CONVERT(char, tod.ShipDay, 111) <= '" . db_Escape($shipDayTo) . "'";
	}

	// 職員コードの指定があった場合
	if ($staffCode != '') {
		$sql .= " AND";
		$sql .= 	" tor.StaffCode = '" . db_Escape($staffCode) . "'";
	}

	$sql .= " AND";
	$sql .= 	" tor.OrderID IN (";
	$sql .= 			" SELECT";
	$sql .= 				" DISTINCT";
	$sql .= 				" OrderID";
	$sql .= 			" FROM";
	$sql .= 				" T_Order_Details tod_uni1";
	$sql .= 			" WHERE";
	$sql .= 				" tod_uni1.Del = " . DELETE_OFF;

	// 状態の指定があった場合
	if ($status != '') {
		$sql .= 		" AND";
		$sql .= 			" tod_uni1.Status IN (";
		$sql .= 				$status;
		$sql .= 			" )";
	}

	// 単品番号の指定があった場合
	if ($barCode != '') {
		$sql .= 		" AND";
		$sql .= 			" tod_uni1.BarCd = '" . db_Escape($barCode) . "'";
	}

	$sql .= 	" )";

	$sql .= " ORDER BY";
	$sql .= 	" tor.AppliDay DESC,";
	$sql .= 	" tor.OrderID DESC,";
	$sql .= 	" tod.ItemID ASC,";
	$sql .= 	" tod.Status ASC";

	$result = db_Read_Csv($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	return  $result;

}


?>
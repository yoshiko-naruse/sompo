<?php
/*
 * エクセル出力
 * common_excel_dl.src.php
 *
 * create 2013/04/18 T.Uno
 *
 *
 */

//mb_internal_encoding('UTF-8');
//mb_http_output('UTF-8');

// 各種モジュールの取得
include_once('../../include/define.php');			// 定数定義
require_once('../../include/dbConnect.php');		// DB接続モジュール
require_once('../../include/msSqlControl.php');		// DB操作モジュール
require_once('../../include/checkLogin.php');		// ログイン判定モジュール
require_once('../../include/redirectPost.php');		// リダイレクトポストモジュール
include_once('../../include/castHidden.php');       // hidden値成型モジュール
require_once('../../include/castHtmlEntity.php');	// HTMLエンティティモジュール
//require_once('../../error_message/errorMessage.php');		// エラーメッセージ

include_once('../../include/myExcel/PHPExcel.php');
include_once('../../include/myExcel/PHPExcel/Writer/Excel5.php');
include_once('../../include/myExcel/PHPExcel/Writer/Excel2007.php');
include_once('../../include/myExcel/PHPExcel/IOFactory.php');

// 制限時間の解除
set_time_limit(0);

// 管理権限でなければトップに
if ($isLevelAdmin == false) {

	$returnUrl = HOME_URL . 'top.php';
	
	// TOP画面に強制遷移
	redirectPost($returnUrl, $hiddens);

}

// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST); 

$inputFromDay = $post['inputFromDay'];	      // 集計開始日
$inputToDay   = $post['inputToDay'];	      // 集計終了日

// 検索条件チェック
// 集計開始日、集計終了日（必須）
if (trim($inputFromDay) == '' || trim($inputToDay) == '') {
    $hiddens['errorName'] = 'seikyuMeisai';
    $hiddens['menuName']  = 'isMenuKanri';
    $hiddens['returnUrl'] = 'admin/seikyumeisai_download.php';
    $hiddens['errorId'][] = '002';
    $errorUrl             = HOME_URL . 'error.php';

    $hiddenHtml = castHiddenError($post);
    $hiddens = array_merge($hiddens, $hiddenHtml);

    // エラー画面に強制遷移
    redirectPost($errorUrl, $hiddens);
}

// YYYY/MM/DD以外の文字列であればエラー
if ( ($inputFromDay != '' && !ereg('^[0-9]{4}/[0-9]{2}/[0-9]{2}$', $inputFromDay))
 || ($inputToDay != '' && !ereg('^[0-9]{4}/[0-9]{2}/[0-9]{2}$', $inputToDay)) ) {
    $hiddens['errorName'] = 'seikyuMeisai';
    $hiddens['menuName']  = 'isMenuKanri';
    $hiddens['returnUrl'] = 'admin/seikyumeisai_download.php';
    $hiddens['errorId'][] = '003';
    $errorUrl             = HOME_URL . 'error.php';

    $hiddenHtml = castHiddenError($post);
    $hiddens = array_merge($hiddens, $hiddenHtml);

    // エラー画面に強制遷移
    redirectPost($errorUrl, $hiddens);
}
//$time_start = microtime(true);    // 出力時間計

// 出力する申請ヘッダ一覧を取得
$outputDatas = getSeikyuData($dbConnect, $_POST);
if (!$outputDatas) {
    $hiddens['errorName'] = 'seikyuMeisai';
    $hiddens['menuName']  = 'isMenuKanri';
    $hiddens['returnUrl'] = 'admin/seikyumeisai_download.php';
    $hiddens['errorId'][] = '005';
    $errorUrl             = HOME_URL . 'error.php';

    $hiddenHtml = castHiddenError($post);
    $hiddens = array_merge($hiddens, $hiddenHtml);

    // エラー画面に強制遷移
    redirectPost($errorUrl, $hiddens);
}

$xlsReader = PHPExcel_IOFactory::createReader('Excel2007');

// エクセルファイルをオープン
$xlsObject = $xlsReader->load("./temp/template_seikyumeisai.xlsx");

//var_dump("aaaaa2");die;
//アクティブなシートを変数に格納
$xlsObject->setActiveSheetIndex(0);
$worksheet = $xlsObject->getActiveSheet();

$lineoffset = 3;

//$honbuWk     = '';     // ブレイク処理用
//$startColumn = '';     // 本部単位での開始カラム ⇒本部ごとの貸与者種類合計にて使用
//$endColumn   = '';     // 本部単位での終了カラム ⇒本部ごとの貸与者種類合計にて使用

$outputCnt = count($outputDatas);

$fromDate = new DateTime($inputFromDay);
$toDate   = new DateTime($inputToDay);

// 請求資料タイトル
$title = "";
$title = "SOMPOケアフーズ様ユニフォーム請求明細（" . $fromDate->format('Y.m.d') . "～" . $toDate->format('Y.m.d') . "）";

// 請求資料シート名
$sheettitle = "請求明細";

// 出力ファイル名
$outFileName = "";
$outFileName = "請求明細【" . $fromDate->format('Y.m.d') . "～" . $toDate->format('Y.m.d') . "】";
$outFileName = mb_convert_encoding($outFileName, 'sjis-win', 'auto');

// タイトル作成（日付付き）
$worksheet->setCellValueExplicit("A1", mb_convert_encoding($title, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
$worksheet->setTitle(mb_convert_encoding($sheettitle, 'UTF-8', 'auto'));

for ($i = 0; $i < $outputCnt; $i++) {

	// 申請日
	$worksheet->setCellValueExplicit("A".($lineoffset), mb_convert_encoding($outputDatas[$i]['AppliDay'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
	// 申請番号
	$worksheet->setCellValueExplicit("B".($lineoffset), mb_convert_encoding($outputDatas[$i]['AppliNo'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
	// 施設コード
	$worksheet->setCellValueExplicit("C".($lineoffset), mb_convert_encoding($outputDatas[$i]['CompCd'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
	// 施設名
	$worksheet->setCellValueExplicit("D".($lineoffset), mb_convert_encoding($outputDatas[$i]['CompName'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
	// 職員コード
	$worksheet->setCellValueExplicit("E".($lineoffset), mb_convert_encoding($outputDatas[$i]['StaffCode'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
	// 職員氏名
	$worksheet->setCellValueExplicit("F".($lineoffset), mb_convert_encoding($outputDatas[$i]['PersonName'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);

	// 区分
	switch ($outputDatas[$i]['AppliMode']) {
		case APPLI_MODE_ORDER:						// 発注
			$appliMode = mb_convert_encoding('発注', 'UTF-8', 'auto');
			break;
		case APPLI_MODE_EXCHANGE:					// 交換
			$appliMode = mb_convert_encoding('交換', 'UTF-8', 'auto');
			break;
		default:
			$appliMode = mb_convert_encoding('', 'UTF-8', 'auto');
			break;
	}
	$worksheet->setCellValueExplicit("G".($lineoffset), $appliMode, PHPExcel_Cell_DataType::TYPE_STRING);

	// 区分詳細
	switch ($outputDatas[$i]['AppliMode']) {
		case APPLI_REASON_ORDER_BASE:				// 発注（そんぽの家系／ラヴィーレ系）
			$appliReason = mb_convert_encoding('そんぽの家系／ラヴィーレ系', 'UTF-8', 'auto');
			break;
		case APPLI_REASON_ORDER_GRADEUP:			// 発注（グレードアップタイ）
			$appliReason = mb_convert_encoding('グレードアップタイ', 'UTF-8', 'auto');
			break;
		case APPLI_REASON_ORDER_FRESHMAN:			// 発注（新入社員）
			$appliReason = mb_convert_encoding('新入社員', 'UTF-8', 'auto');
			break;
		case APPLI_REASON_ORDER_PERSONAL:			// 発注（個別発注申請）
			$appliReason = mb_convert_encoding('個別発注申請', 'UTF-8', 'auto');
			break;
		case APPLI_REASON_EXCHANGE_FIRST:			// 交換（初回サイズ交換）
			$appliReason = mb_convert_encoding('初回サイズ交換', 'UTF-8', 'auto');
			break;
		case APPLI_REASON_EXCHANGE_SIZE:			// 交換（サイズ交換）
			$appliReason = mb_convert_encoding('サイズ交換', 'UTF-8', 'auto');
			break;
		case APPLI_REASON_EXCHANGE_INFERIORITY:		// 交換（不良失品交換）
			$appliReason = mb_convert_encoding('不良失品交換', 'UTF-8', 'auto');
			break;
		case APPLI_REASON_EXCHANGE_LOSS:			// 交換（紛交換）
			$appliReason = mb_convert_encoding('紛交換', 'UTF-8', 'auto');
			break;
		case APPLI_REASON_EXCHANGE_BREAK:			// 交換（汚損・破損交換）
			$appliReason = mb_convert_encoding('汚損・破損交換', 'UTF-8', 'auto');
			break;
		default:
			$appliReason = mb_convert_encoding('', 'UTF-8', 'auto');
			break;
	}
	$worksheet->setCellValueExplicit("H".($lineoffset), $appliReason, PHPExcel_Cell_DataType::TYPE_STRING);

	// 出荷日
	$worksheet->setCellValueExplicit("I".($lineoffset), mb_convert_encoding($outputDatas[$i]['ShipDay'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
	// アイテム名
	$worksheet->setCellValueExplicit("J".($lineoffset), mb_convert_encoding($outputDatas[$i]['ItemName'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
	// サイズ
	$worksheet->setCellValueExplicit("K".($lineoffset), mb_convert_encoding($outputDatas[$i]['Size'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);

	// リユース区分
	switch ($outputDatas[$i]['NewOldOrderKbn']) {
		case 0:				// 新品
			$newOldKbn = mb_convert_encoding('新品', 'UTF-8', 'auto');
			$price = $outputDatas[$i]['Price'];		// 新品単価
			break;
		case 1:				// リユース品
			$newOldKbn = mb_convert_encoding('リユース品', 'UTF-8', 'auto');
			$price = $outputDatas[$i]['OldPrice'];	// リユース品単価
			break;
	}
	$worksheet->setCellValueExplicit("L".($lineoffset), $newOldKbn, PHPExcel_Cell_DataType::TYPE_STRING);

	// 単価
	$worksheet->setCellValueExplicit("M".($lineoffset), mb_convert_encoding($price, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
	$worksheet->getStyle("H".($lineoffset))->getNumberFormat()->setFormatCode('#,##0');
	// 単品番号
	$worksheet->setCellValueExplicit("N".($lineoffset), mb_convert_encoding($outputDatas[$i]['BarCd'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);

	$lineoffset = $lineoffset + 1;

	// 改ページ
//	$worksheet->setBreak("A".($lineoffset - 1), PHPExcel_Worksheet::BREAK_ROW);
}

// 書式設定
setRowFormat(0, 13, $lineoffset-1, '', 'thin', '', '', '', $worksheet);

// 最終合計行以降を削除とする。
//$lineoffset = $lineoffset+1;

//$worksheet->removeRow($lineoffset, 10000);   // 最下位行指定

//印刷範囲
$worksheet->getPageSetup()->setPrintArea("A1:" . "N".($lineoffset-1) );

$xlsWriter = PHPExcel_IOFactory::createWriter($xlsObject, 'Excel5');

//die;
header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Content-Type: application/force-download");
header("Content-Type: application/octet-stream");
header("Content-Type: application/download");
header("Content-Disposition: attachment;filename=" . $outFileName . ".xls");
header("Content-Transfer-Encoding: binary");
$xlsWriter->save('php://output');

/*
 * セル行の書式設定
 * 引数  ：$max         => 列数max値
 *       ：$column      => 対象列数
 *       ：$lineoffset  => 対象行数
 *       ：$top         => 上の線
 *       ：$bottom      => 下の線
 *       ：$left        => 左の線
 *       ：$right       => 右の線
 *       ：$color       => セル色
 * medium ⇒太字
 * thin   ⇒細線
 * double ⇒2重線
 */
function setRowFormat($min='', $max='', $lineoffset, $top='', $bottom='', $left='', $right='', $color='', $worksheet) {

	for ($column=$min; $column<=$max ;$column++) {

		// セル（上）に罫線を引く 
		if (isset($top) && $top != '') {
			$worksheet->getStyleByColumnAndRow($column, $lineoffset)->
			    getBorders()->getTop()->setBorderStyle($top);
		}
		// セル（下）に罫線を引く 
		if (isset($bottom) && $bottom != '') {
			$worksheet->getStyleByColumnAndRow($column, $lineoffset)->
			    getBorders()->getBottom()->setBorderStyle($bottom);
		}
		// セル（左）に罫線を引く
		if (isset($left) && $left != '') {
			$worksheet->getStyleByColumnAndRow($column, $lineoffset)->
			    getBorders()->getLeft()->setBorderStyle($left);
		}
		// セル（右）に罫線を引く
		if (isset($right) && $right != '') {
			$worksheet->getStyleByColumnAndRow($column, $lineoffset)->
			    getBorders()->getRight()->setBorderStyle($right);
		}
		// セルを（任意の）色で塗りつぶす
		if (isset($color) && $color != '') {
			$worksheet->getStyleByColumnAndRow($column, $lineoffset)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB($color);
		}
	}
}

/*
 * 貸与簿一覧情報を取得する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$post           => POST値
 * 戻り値：$result         => 注文履歴一覧情報
 *
 * create 2013/04/17 T.Uno
 *
 */
function getSeikyuData($dbConnect, $post) {
	global $isLevelAgency;

	$result = array();
	$details = array();

	$sql .= " SELECT";
	$sql .= 	" CONVERT(VARCHAR, tor.AppliDay, 111) AS AppliDay,";
	$sql .= 	" tor.AppliNo,";
	$sql .= 	" mcp.CompCd,";
	$sql .= 	" mcp.CompName,";
	$sql .= 	" tor.StaffCode,";
	$sql .= 	" tor.PersonName,";
	$sql .= 	" tor.AppliMode,";
	$sql .= 	" tor.AppliReason,";
	$sql .= 	" CONVERT(VARCHAR, tod.ShipDay, 111) AS ShipDay,";
	$sql .= 	" mit.ItemName,";
	$sql .= 	" tod.Size,";
	$sql .= 	" tod.NewOldOrderKbn,";
	$sql .= 	" tod.Size,";
	$sql .= 	" mit.Price,";
	$sql .= 	" mit.OldPrice,";
	$sql .= 	" tod.BarCd";

	$sql .= " FROM T_Order_Details tod";
	$sql .= " INNER JOIN T_Order tor";
	$sql .= 	" ON  tod.OrderID = tor.OrderID";
	$sql .= 	" AND tor.Del = " . DELETE_OFF;
	$sql .= " INNER JOIN M_Comp mcp";
	$sql .= 	" ON  tor.CompID = mcp.CompID";
	$sql .= 	" AND mcp.Del = " . DELETE_OFF;
	$sql .= " INNER JOIN M_Item mit";
	$sql .= 	" ON  tod.ItemID = mit.ItemID";
	$sql .= 	" AND mit.Del = " . DELETE_OFF;

	$sql .= " WHERE";
	$sql .= 	" tod.Del = " . DELETE_OFF;
	$sql .= " AND";
	// 【出荷日】
	$sql .= 	" (";
	$sql .= 		" CONVERT(char, tod.ShipDay, 111) >= '" . db_Escape($post['inputFromDay']) . "'";
	$sql .= 		" AND";
	$sql .= 		" CONVERT(char, tod.ShipDay, 111) <= '" . db_Escape($post['inputToDay']) . "'";
	$sql .= 	" )";
	$sql .= " AND";
	// 【ステータス】
	$sql .= 	" tod.Status IN (";
	$sql .= 			STATUS_SHIP . ",";		// 出荷済
	$sql .= 			STATUS_DELIVERY;		// 納品済
	$sql .= 	" )";
	$sql .= " ORDER BY";
	$sql .= 	" tor.AppliNo,";
	$sql .= 	" tod.AppliLNo";

//var_dump($sql);die;
	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		return false;
	}

	$resultCnt = count($result);

	return $result;
}

////////////////////////////////////////
// PHPEXCEL 行コピー
////////////////////////////////////////
function copyRows(
	$xlsObject,
	$srcRow,	// 複製元行番号
	$dstRow,	// 複製先行番号
	$height, 	// 複製行数
	$width		// 複製カラム数
) {
	$sheet = $xlsObject->getActiveSheet();

	for($row=0; $row<$height; $row++) {
		// セルの書式と値の複製
		for ($col=0; $col<$width; $col++) {
			$cell = $sheet->getCellByColumnAndRow($col, $srcRow+$row);
			$style = $sheet->getStyleByColumnAndRow($col, $srcRow+$row);
			
			$dstCell = PHPExcel_Cell::stringFromColumnIndex($col).(string)($dstRow+$row);
			$sheet->setCellValue($dstCell, $cell->getValue());
			$sheet->duplicateStyle($style, $dstCell);
		}
		
		// 行の高さ複製。
		$h = $sheet->getRowDimension($srcRow+$row)->getRowHeight();
		$sheet->getRowDimension($dstRow+$row)->setRowHeight($h);
	}

	// セル結合の複製
	// - $mergeCell="AB12:AC15" 複製範囲の物だけ行を加算して復元。 
	// - $merge="AB16:AC19"
	foreach ($sheet->getMergeCells() as $mergeCell) {
		$mc = explode(":", $mergeCell);
		$col_s = preg_replace("/[0-9]*/" , "",$mc[0]);
		$col_e = preg_replace("/[0-9]*/" , "",$mc[1]);
		$row_s = ((int)preg_replace("/[A-Z]*/" , "",$mc[0])) - $srcRow;
		$row_e = ((int)preg_replace("/[A-Z]*/" , "",$mc[1])) - $srcRow;

		// 複製先の行範囲なら。
		if (0 <= $row_s && $row_s < $height) {
			$merge = $col_s.(string)($dstRow+$row_s).":".$col_e.(string)($dstRow+$row_e);
			$sheet->mergeCells($merge);
		}
	}
}

?>

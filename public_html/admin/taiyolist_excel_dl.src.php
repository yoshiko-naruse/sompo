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

$inputDay = $post['inputDay'];	      // 集計日

// 検索条件チェック
// 集計日（必須）
if (trim($inputDay) == '') {
    $hiddens['errorName'] = 'taiyoList';
    $hiddens['menuName']  = 'isMenuKanri';
    $hiddens['returnUrl'] = 'admin/taiyolist_download.php';
    $hiddens['errorId'][] = '002';
    $errorUrl             = HOME_URL . 'error.php';

    $hiddenHtml = castHiddenError($post);
    $hiddens = array_merge($hiddens, $hiddenHtml);

    // エラー画面に強制遷移
    redirectPost($errorUrl, $hiddens);
}

// YY/MM/DD以外の文字列であればエラー
if ($inputDay != '' && !ereg('^[0-9]{4}/[0-9]{2}/[0-9]{2}$', $inputDay)) {
    $hiddens['errorName'] = 'taiyoList';
    $hiddens['menuName']  = 'isMenuKanri';
    $hiddens['returnUrl'] = 'admin/taiyolist_download.php';
    $hiddens['errorId'][] = '003';
    $errorUrl             = HOME_URL . 'error.php';

    $hiddenHtml = castHiddenError($post);
    $hiddens = array_merge($hiddens, $hiddenHtml);

    // エラー画面に強制遷移
    redirectPost($errorUrl, $hiddens);
}

// 出力する申請ヘッダ一覧を取得
$outputDatas = getTaiyoData($dbConnect, $_POST);
if (!$outputDatas) {
    $hiddens['errorName'] = 'taiyoList';
    $hiddens['menuName']  = 'isMenuKanri';
    $hiddens['returnUrl'] = 'admin/taiyolist_download.php';
    $hiddens['errorId'][] = '005';
    $errorUrl             = HOME_URL . 'error.php';

    $hiddenHtml = castHiddenError($post);
    $hiddens = array_merge($hiddens, $hiddenHtml);

    // エラー画面に強制遷移
    redirectPost($errorUrl, $hiddens);
}

// ここから、エクセル出力
$xlsReader = PHPExcel_IOFactory::createReader('Excel2007');

// エクセルファイルをオープン
$xlsObject = $xlsReader->load("./temp/template_taiyo.xlsx");

//アクティブなシートを変数に格納
$xlsObject->setActiveSheetIndex(0);
$worksheet = $xlsObject->getActiveSheet();

$lineoffset = 5;

$honbuWk     = '';     // ブレイク処理用
$honbuNameWk = '';     // 合計行本部名退避用

$corpNameWk  = '';     // JAF or JAFメディアワークス

$honbuSum1   = '';      // パターン①：エクセル数式格納（本部合計）
$honbuSum2   = '';      // パターン②：エクセル数式格納（本部合計）
$honbuSum3   = '';      // パターン③：エクセル数式格納（本部合計）
$honbuSum4   = '';      // パターン④：エクセル数式格納（本部合計）
$honbuSum5   = '';      // パターン⑤：エクセル数式格納（本部合計）
$honbuSum6   = '';      // パターン⑥：エクセル数式格納（本部合計）

$jafSum1     = '';      // パターン①：エクセル数式格納（会社合計）
$jafSum2     = '';      // パターン②：エクセル数式格納（会社合計）
$jafSum3     = '';      // パターン③：エクセル数式格納（会社合計）
$jafSum4     = '';      // パターン④：エクセル数式格納（会社合計）
$jafSum5     = '';      // パターン⑤：エクセル数式格納（会社合計）
$jafSum6     = '';      // パターン⑥：エクセル数式格納（会社合計）

$totalLine   = '';

// メディアワークス以外のときは「false」
$mediaWorksFlg = false;

$startColumn = '';     // 本部単位での開始カラム ⇒本部ごとの貸与者種類合計にて使用
$endColumn   = '';     // 本部単位での終了カラム ⇒本部ごとの貸与者種類合計にて使用

$date = new DateTime($inputDay);

// 貸与一覧資料タイトル
$title = "";
$title = "貸与一覧　（集計日：" . $date->format('Y.m.d') . "）";

// 貸与一覧シート名
$sheettitle = "";
$sheettitle = $date->format('md') . "現在";

// 出力ファイル名
$outFileName = "";
$outFileName = "貸与一覧表【" . $date->format('Y.m.d') . "】";
$outFileName = mb_convert_encoding($outFileName, 'sjis-win', 'auto');

// タイトル作成（日付付き）
$worksheet->setCellValueExplicit("B2", mb_convert_encoding($title, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
$worksheet->setTitle(mb_convert_encoding($sheettitle, 'UTF-8', 'auto'));

$outputCnt = count($outputDatas);
for ($i = 0; $i < $outputCnt; $i++) {

	// 2件目以降で、前行の本部コードが現在の行の本部コードと一致している場合、
	// 前行までの本部合計行を作成する。
	if ($honbuWk == $outputDatas[$i]['HonbuCd']) {
		// 本部名
		$worksheet->setCellValueExplicit("B".($lineoffset), '', PHPExcel_Cell_DataType::TYPE_STRING);
		$endColumn   = $lineoffset;

		// 合計
		$worksheet->setCellValue("J".($lineoffset) ,"=SUM(D" . $lineoffset  . ":I" . $lineoffset . ")");

	// 本部コードの変わり目
	} else {
		// 1行目の処理（※何も処理せず、書き出す）
		if ($honbuWk == '') {
			// 本部名
			$worksheet->setCellValueExplicit("B".($lineoffset), mb_convert_encoding($outputDatas[$i]['HonbuName'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
			$startColumn = $lineoffset;
			$endColumn   = $lineoffset;

			// 合計
			$worksheet->setCellValue("J".($lineoffset) ,"=SUM(D" . $lineoffset  . ":I" . $lineoffset . ")");

		// 2行目以降で本部コードの変わり目の処理　⇒本部合計行を作成し、合計行 + 1行の位置から次の本部の処理を行う。
		} else {

			// 本部合計行の出力
			$worksheet->setCellValueExplicit("B".($lineoffset), mb_convert_encoding($honbuNameWk . "計", 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);

			$worksheet->setCellValue("D".($lineoffset) ,"=SUM(D" . $startColumn . ":D" . $endColumn . ")");
			$worksheet->setCellValue("E".($lineoffset) ,"=SUM(E" . $startColumn . ":E" . $endColumn . ")");
			$worksheet->setCellValue("F".($lineoffset) ,"=SUM(F" . $startColumn . ":F" . $endColumn . ")");
			$worksheet->setCellValue("G".($lineoffset) ,"=SUM(G" . $startColumn . ":G" . $endColumn . ")");
			$worksheet->setCellValue("H".($lineoffset) ,"=SUM(H" . $startColumn . ":H" . $endColumn . ")");
			$worksheet->setCellValue("I".($lineoffset) ,"=SUM(I" . $startColumn . ":I" . $endColumn . ")");

			// 合計
			$worksheet->setCellValue("J".($lineoffset) ,"=SUM(D" . $lineoffset  . ":I" . $lineoffset . ")");

			// 本部毎の総合計数式サマリー
			$honbuSum1 = $honbuSum1 . "+ D".($lineoffset);
			$honbuSum2 = $honbuSum2 . "+ E".($lineoffset);
			$honbuSum3 = $honbuSum3 . "+ F".($lineoffset);
			$honbuSum4 = $honbuSum4 . "+ G".($lineoffset);
			$honbuSum5 = $honbuSum5 . "+ H".($lineoffset);
			$honbuSum6 = $honbuSum6 . "+ I".($lineoffset);

			// 本部合計行書式
			setRowFormat(9, $column, $lineoffset, 'thin', 'double', '', '', 'ebf1de', $worksheet);

			$lineoffset = $lineoffset + 1;

			// 現在の読み込み行でJMWが存在 且つ JMW初回読み込み
			if (isset($outputDatas[$i]['HonbuCd']) && $outputDatas[$i]['HonbuCd'] == JAF_MEDIAWORKS && $mediaWorksFlg == false ) {

				// JAF本部⇒JAFメディアワークス切り替わり位置：JAFの合計行
				setRowFormat(9, $column, $lineoffset, 'thick', 'thick', '', '', 'fff2cc', $worksheet);

				// 最終行の本部総合計行の出力 Start
				$worksheet->setCellValueExplicit("B".($lineoffset), mb_convert_encoding($corpNameWk . "計", 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);

				// 貸与パターン①
				$worksheet->setCellValue("D".($lineoffset) ,"=" . $honbuSum1);
				// 貸与パターン②
				$worksheet->setCellValue("E".($lineoffset) ,"=" . $honbuSum2);
				// 貸与パターン③
				$worksheet->setCellValue("F".($lineoffset) ,"=" . $honbuSum3);
				// 貸与パターン④
				$worksheet->setCellValue("G".($lineoffset) ,"=" . $honbuSum4);
				// 貸与パターン⑤
				$worksheet->setCellValue("H".($lineoffset) ,"=" . $honbuSum5);
				// 貸与パターン⑥
				$worksheet->setCellValue("I".($lineoffset) ,"=" . $honbuSum6);

				// JAF行合計数式退避
				$jafSum1 = $honbuSum1;
				$jafSum2 = $honbuSum2;
				$jafSum3 = $honbuSum3;
				$jafSum4 = $honbuSum4;
				$jafSum5 = $honbuSum5;
				$jafSum6 = $honbuSum6;

				// 合計
				$worksheet->setCellValue("J".($lineoffset) ,"=SUM(D" . $lineoffset  . ":I" . $lineoffset . ")");

				// ＪＡＦメディアワークス用
				$honbuSum1   = '';
				$honbuSum2   = '';
				$honbuSum3   = '';
				$honbuSum4   = '';
				$honbuSum5   = '';
				$honbuSum6   = '';
				
				$mediaWorksFlg = true;

				$lineoffset = $lineoffset + 1;
			}

			// 合計
			$worksheet->setCellValue("J".($lineoffset) ,"=SUM(D" . $lineoffset  . ":I" . $lineoffset . ")");

			// 本部名
			$worksheet->setCellValueExplicit("B".($lineoffset), mb_convert_encoding($outputDatas[$i]['HonbuName'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
			$startColumn = $lineoffset;
			$endColumn   = $lineoffset;

		}
	}

	// 共通処理：書き出し
	// 本部名
//	$worksheet->setCellValueExplicit("B".($lineoffset), mb_convert_encoding($outputDatas[$i]['HonbuName'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
	// 支部名
	$worksheet->setCellValueExplicit("C".($lineoffset), mb_convert_encoding($outputDatas[$i]['ShibuName'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
	// 貸与パターン①
	$worksheet->setCellValueExplicit("D".($lineoffset), mb_convert_encoding($outputDatas[$i]['ptn1'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
	// 貸与パターン②
	$worksheet->setCellValueExplicit("E".($lineoffset), mb_convert_encoding($outputDatas[$i]['ptn2'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
	// 貸与パターン③
	$worksheet->setCellValueExplicit("F".($lineoffset), mb_convert_encoding($outputDatas[$i]['ptn3'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
	// 貸与パターン④
	$worksheet->setCellValueExplicit("G".($lineoffset), mb_convert_encoding($outputDatas[$i]['ptn4'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
	// 貸与パターン⑤
	$worksheet->setCellValueExplicit("H".($lineoffset), mb_convert_encoding($outputDatas[$i]['ptn5'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
	// 貸与パターン⑥
	$worksheet->setCellValueExplicit("I".($lineoffset), mb_convert_encoding($outputDatas[$i]['ptn6'], 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);

	$lineoffset = $lineoffset + 1;

	// 現在の行の本部コード、本部名を退避（※次行で前行のコードの比較に使用する。）
	$honbuWk     = $outputDatas[$i]['HonbuCd'];
	$honbuNameWk = $outputDatas[$i]['HonbuName'];
	
	// 総合計行の会社名称を退避
	if ($outputDatas[$i]['HonbuCd'] == JAF_MEDIAWORKS) {
		$corpNameWk = "JAFメディアワークス";
	} else {
		$corpNameWk = "JAF";
	}
	// 改ページ
//	$worksheet->setBreak("A".($lineoffset - 1), PHPExcel_Worksheet::BREAK_ROW);
}

// 最終行の本部合計行の出力 Start
$worksheet->setCellValueExplicit("B".($lineoffset), mb_convert_encoding($honbuNameWk . "計", 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);

$worksheet->setCellValue("D".($lineoffset) ,"=SUM(D" . $startColumn . ":D" . $endColumn . ")");
$worksheet->setCellValue("E".($lineoffset) ,"=SUM(E" . $startColumn . ":E" . $endColumn . ")");
$worksheet->setCellValue("F".($lineoffset) ,"=SUM(F" . $startColumn . ":F" . $endColumn . ")");
$worksheet->setCellValue("G".($lineoffset) ,"=SUM(G" . $startColumn . ":G" . $endColumn . ")");
$worksheet->setCellValue("H".($lineoffset) ,"=SUM(H" . $startColumn . ":H" . $endColumn . ")");
$worksheet->setCellValue("I".($lineoffset) ,"=SUM(I" . $startColumn . ":I" . $endColumn . ")");

$honbuSum1 = $honbuSum1 . "+ D".($lineoffset);
$honbuSum2 = $honbuSum2 . "+ E".($lineoffset);
$honbuSum3 = $honbuSum3 . "+ F".($lineoffset);
$honbuSum4 = $honbuSum4 . "+ G".($lineoffset);
$honbuSum5 = $honbuSum5 . "+ H".($lineoffset);
$honbuSum6 = $honbuSum6 . "+ I".($lineoffset);

// JAFのみだった場合の本部合計行（JAFメディアワークスを含まない）
if ($mediaWorksFlg == false) { 
	setRowFormat(9, $column, $lineoffset, 'thin', 'thick', '', '', 'ebf1de', $worksheet);

} else {
	setRowFormat(9, $column, $lineoffset, 'double', 'thick', '', '', 'fff2cc', $worksheet);
}

// 合計
$worksheet->setCellValue("J".($lineoffset) ,"=SUM(D" . $lineoffset  . ":I" . $lineoffset . ")");

//-----------------------------------------------------

// JAFのみだった場合、JAF合計行（JAFメディアワークスを含まない）
if ($mediaWorksFlg == false) { 

	$lineoffset = $lineoffset + 1;

	setRowFormat(9, $column, $lineoffset, 'thick', 'thick', '', '', 'fff2cc', $worksheet);

		
	// 最終行の本部総合計行の出力 Start
	$worksheet->setCellValueExplicit("B".($lineoffset), mb_convert_encoding($corpNameWk . "計", 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);

	// 貸与パターン①
	$worksheet->setCellValue("D".($lineoffset) ,"=" . $honbuSum1);
	// 貸与パターン②
	$worksheet->setCellValue("E".($lineoffset) ,"=" . $honbuSum2);
	// 貸与パターン③
	$worksheet->setCellValue("F".($lineoffset) ,"=" . $honbuSum3);
	// 貸与パターン④
	$worksheet->setCellValue("G".($lineoffset) ,"=" . $honbuSum4);
	// 貸与パターン⑤
	$worksheet->setCellValue("H".($lineoffset) ,"=" . $honbuSum5);
	// 貸与パターン⑥
	$worksheet->setCellValue("I".($lineoffset) ,"=" . $honbuSum6);

	// 合計
	$worksheet->setCellValue("J".($lineoffset) ,"=SUM(D" . $lineoffset  . ":I" . $lineoffset . ")");
}
//------------------------------------------------------------
// 最終行の本部合計行の出力 End

// 総合計行出力
$lineoffset = $lineoffset + 1;

// 最終行の本部総合計行の出力 Start
$worksheet->setCellValueExplicit("B".($lineoffset), mb_convert_encoding("総合計", 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);

$worksheet->setCellValue("D".($lineoffset) ,"=" . $jafSum1 . $honbuSum1);
$worksheet->setCellValue("E".($lineoffset) ,"=" . $jafSum2 . $honbuSum2);
$worksheet->setCellValue("F".($lineoffset) ,"=" . $jafSum3 . $honbuSum3);
$worksheet->setCellValue("G".($lineoffset) ,"=" . $jafSum4 . $honbuSum4);
$worksheet->setCellValue("H".($lineoffset) ,"=" . $jafSum5 . $honbuSum5);
$worksheet->setCellValue("I".($lineoffset) ,"=" . $jafSum6 . $honbuSum6);

$totalLine = $lineoffset;

// 合計
$worksheet->setCellValue("J".($lineoffset) ,"=SUM(D" . $lineoffset  . ":I" . $lineoffset . ")");

setRowFormat(9, $column, $lineoffset, 'thick', 'thick', '', '', 'ffe699', $worksheet);

// 最終合計行以降を削除とする。
$lineoffset = $lineoffset + 1;

$worksheet->removeRow($lineoffset, 400);

// （総合計行 + 2）行目から、総ステータスを記録
$lineoffset = $lineoffset + 1;

// 出力する請求サマリー情報
$outputRentalInfo = getRentalPrice($dbConnect);

$outputRentalCnt = count($outputRentalInfo);

for ($i = 0; $i < $outputRentalCnt; $i++) {

    switch ($outputRentalInfo[$i]['PatternID']) {
        case 1:
		case 2:
		case 3:
		case 4:
            $ptn1Price    = $outputRentalInfo[$i]['Price'];
            $ptn1PriceTax = $outputRentalInfo[$i]['PriceTax'];
            break;
		case 6:				// 
            $ptn2Price    = $outputRentalInfo[$i]['Price'];
            $ptn2PriceTax = $outputRentalInfo[$i]['PriceTax'];
            break;
		case 7:				// 
            $ptn3Price    = $outputRentalInfo[$i]['Price'];
            $ptn3PriceTax = $outputRentalInfo[$i]['PriceTax'];
            break;
		case 9:				// 
            $ptn4Price    = $outputRentalInfo[$i]['Price'];
            $ptn4PriceTax = $outputRentalInfo[$i]['PriceTax'];
            break;
		case 8:				// 
            $ptn5Price    = $outputRentalInfo[$i]['Price'];
            $ptn5PriceTax = $outputRentalInfo[$i]['PriceTax'];
            break;
		case 10:				// 
            $ptn6Price    = $outputRentalInfo[$i]['Price'];
            $ptn6PriceTax = $outputRentalInfo[$i]['PriceTax'];
            break;
        default:
            break;
    }
   
}

// レンタル料/月（税別）-------------------------------
$worksheet->setCellValueExplicit("B".($lineoffset), mb_convert_encoding("レンタル料/月（税別）", 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
// 貸与パターン①
$worksheet->setCellValueExplicit("D".($lineoffset), mb_convert_encoding($ptn1Price, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("D".($lineoffset))->getNumberFormat()->setFormatCode('"\"#,##0');
// 貸与パターン②
$worksheet->setCellValueExplicit("E".($lineoffset), mb_convert_encoding($ptn2Price, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("E".($lineoffset))->getNumberFormat()->setFormatCode('"\"#,##0');
// 貸与パターン③
$worksheet->setCellValueExplicit("F".($lineoffset), mb_convert_encoding($ptn3Price, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("F".($lineoffset))->getNumberFormat()->setFormatCode('"\"#,##0');
// 貸与パターン④
$worksheet->setCellValueExplicit("G".($lineoffset), mb_convert_encoding($ptn4Price, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("G".($lineoffset))->getNumberFormat()->setFormatCode('"\"#,##0');
// 貸与パターン⑤
$worksheet->setCellValueExplicit("H".($lineoffset), mb_convert_encoding($ptn5Price, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("H".($lineoffset))->getNumberFormat()->setFormatCode('"\"#,##0');
// 貸与パターン⑥
$worksheet->setCellValueExplicit("I".($lineoffset), mb_convert_encoding($ptn6Price, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("I".($lineoffset))->getNumberFormat()->setFormatCode('"\"#,##0');

// 書式設定
setRowFormat(8, $column, $lineoffset, 'thin', 'thin', 'thin', 'thin', '', $worksheet);

// セルを結合する
$worksheet->mergeCells("B".($lineoffset) . ":" . "C".($lineoffset) );

// 消費税 -------------------------------
$worksheet->setCellValueExplicit("B".($lineoffset+1), mb_convert_encoding("消費税", 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
// 貸与パターン①
$worksheet->setCellValueExplicit("D".($lineoffset+1), mb_convert_encoding($ptn1PriceTax - $ptn1Price, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("D".($lineoffset+1))->getNumberFormat()->setFormatCode('"\"#,##0');
// 貸与パターン②
$worksheet->setCellValueExplicit("E".($lineoffset+1), mb_convert_encoding($ptn2PriceTax - $ptn2Price, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("E".($lineoffset+1))->getNumberFormat()->setFormatCode('"\"#,##0');
// 貸与パターン③
$worksheet->setCellValueExplicit("F".($lineoffset+1), mb_convert_encoding($ptn3PriceTax - $ptn3Price, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("F".($lineoffset+1))->getNumberFormat()->setFormatCode('"\"#,##0');
// 貸与パターン④
$worksheet->setCellValueExplicit("G".($lineoffset+1), mb_convert_encoding($ptn4PriceTax - $ptn4Price, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("G".($lineoffset+1))->getNumberFormat()->setFormatCode('"\"#,##0');
// 貸与パターン⑤
$worksheet->setCellValueExplicit("H".($lineoffset+1), mb_convert_encoding($ptn5PriceTax - $ptn5Price, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("H".($lineoffset+1))->getNumberFormat()->setFormatCode('"\"#,##0');
// 貸与パターン⑥
$worksheet->setCellValueExplicit("I".($lineoffset+1), mb_convert_encoding($ptn6PriceTax - $ptn6Price, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("I".($lineoffset+1))->getNumberFormat()->setFormatCode('"\"#,##0');

// 書式設定
setRowFormat(8, $column, $lineoffset+1, 'thin', 'thin', 'thin', 'thin', '', $worksheet);

// セルを結合する
$worksheet->mergeCells("B".($lineoffset+1) . ":" . "C".($lineoffset+1) );

// レンタル料/月（税込）-------------------------------
$worksheet->setCellValueExplicit("B".($lineoffset+2), mb_convert_encoding("レンタル料/月（税込）", 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
// 貸与パターン①
$worksheet->setCellValueExplicit("D".($lineoffset+2), mb_convert_encoding($ptn1PriceTax, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("D".($lineoffset+2))->getNumberFormat()->setFormatCode('"\"#,##0');
// 貸与パターン②
$worksheet->setCellValueExplicit("E".($lineoffset+2), mb_convert_encoding($ptn2PriceTax, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("E".($lineoffset+2))->getNumberFormat()->setFormatCode('"\"#,##0');
// 貸与パターン③
$worksheet->setCellValueExplicit("F".($lineoffset+2), mb_convert_encoding($ptn3PriceTax, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("F".($lineoffset+2))->getNumberFormat()->setFormatCode('"\"#,##0');
// 貸与パターン④
$worksheet->setCellValueExplicit("G".($lineoffset+2), mb_convert_encoding($ptn4PriceTax, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("G".($lineoffset+2))->getNumberFormat()->setFormatCode('"\"#,##0');
// 貸与パターン⑤
$worksheet->setCellValueExplicit("H".($lineoffset+2), mb_convert_encoding($ptn5PriceTax, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("H".($lineoffset+2))->getNumberFormat()->setFormatCode('"\"#,##0');
// 貸与パターン⑥
$worksheet->setCellValueExplicit("I".($lineoffset+2), mb_convert_encoding($ptn6PriceTax, 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_NUMERIC);
$worksheet->getStyle("I".($lineoffset+2))->getNumberFormat()->setFormatCode('"\"#,##0');

// 書式設定
setRowFormat(8, $column, $lineoffset+2, 'thin', 'thin', 'thin', 'thin', '', $worksheet);

// セルを結合する
$worksheet->mergeCells("B".($lineoffset+2) . ":" . "C".($lineoffset+2) );

// 種類別合計（税別）-------------------------------
$worksheet->setCellValueExplicit("B".($lineoffset+3), mb_convert_encoding("種類別合計（税別）", 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
//
$worksheet->setCellValue("D".($lineoffset+3) ,"=D".($totalLine) . "*" . "D".($lineoffset));
$worksheet->setCellValue("E".($lineoffset+3) ,"=E".($totalLine) . "*" . "E".($lineoffset));
$worksheet->setCellValue("F".($lineoffset+3) ,"=F".($totalLine) . "*" . "F".($lineoffset));
$worksheet->setCellValue("G".($lineoffset+3) ,"=G".($totalLine) . "*" . "G".($lineoffset));
$worksheet->setCellValue("H".($lineoffset+3) ,"=H".($totalLine) . "*" . "H".($lineoffset));
$worksheet->setCellValue("I".($lineoffset+3) ,"=I".($totalLine) . "*" . "I".($lineoffset));

$worksheet->getStyle("D".($lineoffset+3))->getNumberFormat()->setFormatCode('"\"#,##0');
$worksheet->getStyle("E".($lineoffset+3))->getNumberFormat()->setFormatCode('"\"#,##0');
$worksheet->getStyle("F".($lineoffset+3))->getNumberFormat()->setFormatCode('"\"#,##0');
$worksheet->getStyle("G".($lineoffset+3))->getNumberFormat()->setFormatCode('"\"#,##0');
$worksheet->getStyle("H".($lineoffset+3))->getNumberFormat()->setFormatCode('"\"#,##0');
$worksheet->getStyle("I".($lineoffset+3))->getNumberFormat()->setFormatCode('"\"#,##0');//

// 書式設定
setRowFormat(8, $column, $lineoffset+3, 'thin', 'thin', 'thin', 'thin', '', $worksheet);

// セルを結合する
$worksheet->mergeCells("B".($lineoffset+3) . ":" . "C".($lineoffset+3) );

// 種類別合計（①×②）（税込）-------------------------------
$worksheet->setCellValueExplicit("B".($lineoffset+4), mb_convert_encoding("種類別合計（税込）", 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
//
$worksheet->setCellValue("D".($lineoffset+4) ,"=D".($totalLine) . "*" . "D".($lineoffset+2));
$worksheet->setCellValue("E".($lineoffset+4) ,"=E".($totalLine) . "*" . "E".($lineoffset+2));
$worksheet->setCellValue("F".($lineoffset+4) ,"=F".($totalLine) . "*" . "F".($lineoffset+2));
$worksheet->setCellValue("G".($lineoffset+4) ,"=G".($totalLine) . "*" . "G".($lineoffset+2));
$worksheet->setCellValue("H".($lineoffset+4) ,"=H".($totalLine) . "*" . "H".($lineoffset+2));
$worksheet->setCellValue("I".($lineoffset+4) ,"=I".($totalLine) . "*" . "I".($lineoffset+2));

$worksheet->getStyle("D".($lineoffset+4))->getNumberFormat()->setFormatCode('"\"#,##0');
$worksheet->getStyle("E".($lineoffset+4))->getNumberFormat()->setFormatCode('"\"#,##0');
$worksheet->getStyle("F".($lineoffset+4))->getNumberFormat()->setFormatCode('"\"#,##0');
$worksheet->getStyle("G".($lineoffset+4))->getNumberFormat()->setFormatCode('"\"#,##0');
$worksheet->getStyle("H".($lineoffset+4))->getNumberFormat()->setFormatCode('"\"#,##0');
$worksheet->getStyle("I".($lineoffset+4))->getNumberFormat()->setFormatCode('"\"#,##0');//

// 書式設定
setRowFormat(8, $column, $lineoffset+4, 'thin', 'thin', 'thin', 'thin', '', $worksheet);

// セルを結合する
$worksheet->mergeCells("B".($lineoffset+4) . ":" . "C".($lineoffset+4) );

// 合計 -------------------------------
$worksheet->setCellValueExplicit("B".($lineoffset+5), mb_convert_encoding("合計", 'UTF-8', 'auto'), PHPExcel_Cell_DataType::TYPE_STRING);
//
// 合計
$worksheet->setCellValue("D".($lineoffset+5) ,"=SUM(D" . ($lineoffset+4)  . ":I" . ($lineoffset+4) . ")");

$worksheet->getStyle("D".($lineoffset+5))->getNumberFormat()->setFormatCode('"\"#,##0');//

// 書式設定
setRowFormat(8, $column, $lineoffset+5, 'thick', 'thick', 'thick', 'thick', '', $worksheet);

// セルを結合する
$worksheet->mergeCells("B".($lineoffset+5) . ":" . "C".($lineoffset+5) );
// 合計行のセル結合
$worksheet->mergeCells("D".($lineoffset+5) . ":" . "I".($lineoffset+5) );

/* 幅と高さを1ページに収める */
//$worksheet->getPageSetup()->setFitToWidth( 1 )->setFitToHeight( 1 );

//印刷範囲
$worksheet->getPageSetup()->setPrintArea("A1:" . "J".($lineoffset+5) );

$xlsWriter = PHPExcel_IOFactory::createWriter($xlsObject, 'Excel5');

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
function setRowFormat($max='', $column, $lineoffset, $top='', $bottom='', $left='', $right='', $color='', $worksheet) {

	for ($column=1; $column<=$max ;$column++) {

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
function getTaiyoData($dbConnect, $post) {

	global $isLevelAgency;

	$result = array();
	$details = array();

	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" piv.HonbuCd,";
	$sql .= 	" piv.HonbuName,";
	$sql .= 	" piv.ShibuCd,";
	$sql .= 	" piv.ShibuName,";
	$sql .= 	" sum(case piv.AppliPattern when 1 then cnt when 2 then cnt when 3 then cnt when 4 then cnt else null end) as ptn1,";
	$sql .= 	" sum(case piv.AppliPattern when 6 then cnt else null end) as ptn2,";
	$sql .= 	" sum(case piv.AppliPattern when 7 then cnt else null end) as ptn3,";
	$sql .= 	" sum(case piv.AppliPattern when 9 then cnt else null end) as ptn4,";
	$sql .= 	" sum(case piv.AppliPattern when 8 then cnt else null end) as ptn5,";
	$sql .= 	" sum(case piv.AppliPattern when 10 then cnt else null end) as ptn6";
	$sql .= " FROM";
	$sql .= 	" (";
	$sql .= 	" SELECT";
	$sql .= 		" ship.HonbuCd,";
	$sql .= 		" ship.HonbuName,";
	$sql .= 		" ship.ShibuCd,";
	$sql .= 		" ship.ShibuName,";
	$sql .= 		" ship.AppliPattern,";
	$sql .= 		" PatternName,";
	$sql .= 		" count(ship.StaffID) AS cnt";
	$sql .= 	" FROM";
	$sql .= 	" (";
	$sql .= 		" SELECT";
	$sql .= 			" mc.HonbuCd,";
	$sql .= 			" mc.HonbuName,";
	$sql .= 			" mc.ShibuCd,";
	$sql .= 			" mc.ShibuName,";
	$sql .= 			" tor.StaffID,";
	$sql .= 			" tor.StaffCode,";
	$sql .= 			" ROW_NUMBER() OVER(PARTITION BY tor.StaffID ORDER BY tor.OrderID) AS OrderCnt,";
	$sql .= 			" tor.OrderID,";
	$sql .= 			" tor.AppliMode,";
	$sql .= 			" tor.AppliPattern,";
	$sql .= 			" tor.Status,";
	$sql .= 			" CONVERT(varchar, tor.RentalStartDay, 111) AS RentalStartDay";
	$sql .= 		" FROM";
	$sql .= 			" jaf.T_Order tor";

	$sql .= 			" INNER JOIN";
	$sql .= 				" M_Comp mc";
	$sql .= 			" ON  tor.CompID = mc.CompID";
	$sql .= 			" AND mc.Del = " . DELETE_OFF;
	$sql .= 			" AND mc.ShopFlag <> 0";

	$sql .= 		" WHERE";
	$sql .= 			" tor.AppliMode = " . APPLI_MODE_ORDER;
	// Y.Furukawa 2017/08/24 キャンセル・否認は除外
	$sql .= 		" AND tor.Status NOT IN (" . STATUS_CANCEL . "," . STATUS_APPLI_DENY .")";
	$sql .= 		" AND tor.Del = " . DELETE_OFF;
	$sql .= 		" AND RentalStartDay <= '" . db_Escape($post['inputDay']) . "'";
	$sql .= 	" ) ship";
	$sql .= 	" LEFT JOIN";
	$sql .= 	" (";
	$sql .= 		" SELECT";
	$sql .= 			" tor.StaffID,";
	$sql .= 			" tor.StaffCode,";
	$sql .= 			" ROW_NUMBER() OVER(PARTITION BY tor.StaffID ORDER BY tor.OrderID) AS OrderCnt,";
	$sql .= 			" tor.OrderID,";
	$sql .= 			" tor.AppliMode,";
	$sql .= 			" tor.Status,";
	$sql .= 			" CONVERT(varchar, tor.RentalEndDay, 111) AS RentalEndDay";
	$sql .= 		" FROM";
	$sql .= 			" jaf.T_Order tor";
	$sql .= 		" WHERE";
	$sql .= 			" tor.AppliMode = " . APPLI_MODE_RETURN;
	// Y.Furukawa 2017/08/24 キャンセル・否認は除外
	$sql .= 		" AND tor.Status NOT IN (" . STATUS_CANCEL . "," . STATUS_APPLI_DENY .")";
	$sql .= 		" AND tor.Del = " . DELETE_OFF;
	$sql .= 	" ) ret";
	$sql .= 	" ON  ship.StaffID = ret.StaffID";
	$sql .= 	" AND ship.OrderCnt = ret.OrderCnt";
	$sql .= 	" INNER JOIN";
	$sql .= 	" (";
	$sql .= 		" SELECT";
	$sql .= 			" DISTINCT PatternID,";
	$sql .= 			" PatternName,";
	$sql .= 			" Price,";
	$sql .= 			" PriceTax,";
	$sql .= 			" sum(ItemSelectNum) AS PatternCount";
	$sql .= 		" FROM";
	$sql .= 			" M_ItemSelect";
	$sql .= 		" WHERE";
	$sql .= 			" Del = " . DELETE_OFF;
	$sql .= 		" GROUP BY";
	$sql .= 			" PatternID,";
	$sql .= 			" PatternName,";
	$sql .= 			" Price,";
	$sql .= 			" PriceTax";
	$sql .= 	" ) mis";
	$sql .= 	" ON  mis.PatternID = ship.AppliPattern";
	$sql .= " WHERE";
	$sql .= 	" ret.RentalEndDay >=  '" . db_Escape($post['inputDay']) . "'";
	$sql .= " OR  ret.RentalEndDay is null";

	$sql .= " GROUP BY";
	$sql .= 	" ship.HonbuCd,";
	$sql .= 	" ship.HonbuName,";
	$sql .= 	" ship.ShibuCd,";
	$sql .= 	" ship.ShibuName,";
	$sql .= 	" ship.AppliPattern,";
	$sql .= 	" PatternName";

	$sql .= 	" ) piv";

	$sql .= " GROUP BY";
	$sql .= 	" piv.HonbuCd,";
	$sql .= 	" piv.HonbuName,";
	$sql .= 	" piv.ShibuCd,";
	$sql .= 	" piv.ShibuName";

	$sql .= " ORDER BY";
	$sql .= 	" piv.HonbuCd,";
	$sql .= 	" piv.HonbuName,";
	$sql .= 	" piv.ShibuCd,";
	$sql .= 	" piv.ShibuName";

//var_dump($sql);die;
	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		return false;
	}

	$resultCnt = count($result);

	// 各申請の明細一覧を取得する
	for ($i = 0; $i < $resultCnt; $i++) {

	}

	return $result;
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
function getRentalPrice($dbConnect) {

	global $isLevelAgency;

	$result = array();
	$details = array();

	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" DISTINCT";
	$sql .= 	" PatternID,";
	$sql .= 	" PatternName,";
	$sql .= 	" Price,";
	$sql .= 	" PriceTax";
	$sql .= " FROM";
	$sql .= 	" M_ItemSelect";
	$sql .= " WHERE";
	$sql .= 	" Del = " . DELETE_OFF;
	$sql .= " GROUP BY";
	$sql .= 	" PatternID,";
	$sql .= 	" PatternName,";
	$sql .= 	" Price,";
	$sql .= 	" PriceTax";

//var_dump($sql);die;
	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		return false;
	}

	$resultCnt = count($result);

	// 各申請の明細一覧を取得する
	for ($i = 0; $i < $resultCnt; $i++) {

	}

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

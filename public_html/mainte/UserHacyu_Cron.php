<?php
/*
 * 発注申請一括登録処理
 * UserHacyu_Cron.php
 *
 * create 2013/04/12
 *
 *
 */

$rootDir = dirname(dirname(dirname(__FILE__)));

include_once($rootDir . '/include/define.php');					// 定数定義
include_once($rootDir . '/include/dbConnect.php');				// DB接続モジュール
include_once($rootDir . '/include/msSqlControl.php');			// DB操作モジュール
include_once($rootDir . '/include/checkData.php');				// 対象文字列検証モジュール
include_once($rootDir . '/include/createRequestNo.php');		// 申請番号生成モジュール
include_once($rootDir . '/include/commonFunc.php');				// 共通関数モジュール
//include_once($rootDir . '/include/checkDuplicateStaff.php');	// 職員重複チェックモジュール
include_once($rootDir . '/include/createHachuMail.php');		// 発注申請メール生成モジュール
include_once($rootDir . '/include/sendTextMail.php');			// テキストメール送信モジュール

include_once($rootDir . '/include/myExcel/PHPExcel.php');
include_once($rootDir . '/include/myExcel/PHPExcel/Writer/Excel5.php');
include_once($rootDir . '/include/myExcel/PHPExcel/IOFactory.php');

set_time_limit(0);

$upfliePath = dirname(__FILE__).'\\up_file\\';

//$argv[1] = 109;

// コマンドラインから引数を受け取る実行ID 
$queueData = getT_OrderQueue($dbConnect, $argv[1]);
if (!$queueData) {
	exit(0);
}

// ファイル拡張子のチェック
if (substr($queueData['UserUpSetFile'], -5, 5) == '.xlsx') {

	// Excel2007形式
	$xlsReader = PHPExcel_IOFactory::createReader('Excel2007');

} elseif (substr($queueData['UserUpSetFile'], -4, 4) == '.xls') {

	// Excel2003形式
	$xlsReader = PHPExcel_IOFactory::createReader('Excel5');

} else {
	$errMsg = array();
	$errMsg[0]['lineNo'] = 0;
	$errMsg[0]['message'] = "対象ファイルはエクセル表（拡張子.xlsxまたは.xls）ではありません。";

	if (!errorInsert($dbConnect, $queueData, $errMsg)) {
		exit(1);
	}
	if (!updateT_OrderQueue($dbConnect, $queueData, 2, "エラーが発生しました")) {
		exit(1);
	}
	exit(0);
}

// エクセルファイルをオープン
$xlsObject = $xlsReader->load($upfliePath.$queueData['UserUpSetFile']);

//アクティブなシートを変数に格納
$xlsObject->setActiveSheetIndex(0);
$worksheet = $xlsObject->getActiveSheet();

$startRowNo = 5;	// 発注開始行位置
$startTokNo = 0;	// 特寸入力開始カラム(初期値)

//////////////////////////////////////////////////////////
// エクセル表の内容を読込
//////////////////////////////////////////////////////////
$rowdata = array();

// エクセルファイルのデータ読込
$rowMax = $worksheet->getHighestRow();	// 行の最大値

$colMax = PHPExcel_Cell::columnIndexFromString($worksheet->getHighestColumn());	// 列の最大値

for ($i = 1; $i <= $rowMax; $i++){
	// １行目の取得
	if ($i == 1) {
    	$firstLine = array();
		for ($j = 0; $j < $colMax; $j++) {
			$firstLine[] = $worksheet->getCellByColumnAndRow($j, $i)->getValue();
		}

	// 職員発注データの取得
	} elseif ($i >= $startRowNo) {
    	$coldata = array();
		for ($j = 0; $j < $colMax; $j++) {
			$coldata[] = $worksheet->getCellByColumnAndRow($j, $i)->getValue();
		}
	    $rowdata[] = $coldata;

	} else {
        continue;

	}
}

// アップロードシートのモードチェック
if (mb_convert_encoding(trim($firstLine[0]), "UTF-8", "auto") == "職員制服着用申請") {
	$appliReason = APPLI_REASON_ORDER_EXCEL;

} else{
	$errMsg = array();
	$errMsg[0]['lineNo'] = 1;
	$errMsg[0]['message'] = "エクセル表は発注申請用ではありません。";

	if (!errorInsert($dbConnect, $queueData, $errMsg)) {
		exit(1);
	}
	if (!updateT_OrderQueue($dbConnect, $queueData, 2, "エラーが発生しました")) {
		exit(1);
	}
	exit(0);
}

//if ($appliReason == APPLI_REASON_ORDER_COMMON) {	// 共用品
//	$startColNo = 3;	// 共用品のみアイテム入力開始カラム
//} else {											// 共用品以外
	$startColNo = 14;	// アイテム入力開始カラム
//}

//////////////////////////////////////////////////////////
// アイテムNoデータを整形
//////////////////////////////////////////////////////////
$itemdata = array();
$firstLineMax = count($firstLine);
for ($i = 0; $i < $firstLineMax; $i++) {
	// 先頭行のセルが"特寸サイズ"となるまでアイテムNoを配列セット
	if (mb_convert_encoding(trim($firstLine[$i]), "UTF-8", "auto") == "特寸サイズ") {
		$startTokNo = $i + 1;					// 特寸開始カラム位置セット
		break;
	} else if ($i < ($startColNo - 1)) {		// アイテムNoの始まりまで空白
		$itemdata[$i] = "";
	} else {									// アイテムNoをコピー
		$itemdata[$i] = trim($firstLine[$i]);
	}
}

// アイテムNoの存在チェック
$errMsg = checkItemData($dbConnect, $appliReason, $itemdata);
if ($errMsg) {
	if (!errorInsert($dbConnect, $queueData, $errMsg)) {
		exit(1);
	}
	if (!updateT_OrderQueue($dbConnect, $queueData, 2, "エラーが発生しました")) {
		exit(1);
	}
	exit(0);
}

	$staffData = array();
	$staffCnt = count($rowdata);

	for ($i = 0; $i < $staffCnt; $i++) {

		$staffData[$i]['lineNo']       = $i + $startRowNo;		// Excel行番号
		$staffData[$i]['staffCode']    = $rowdata[$i][0];												// 職員コード
		$staffData[$i]['staffName']    = mb_convert_encoding($rowdata[$i][1], "UTF-8", "auto");			// 職員名
		$staffData[$i]['compCode']     = $rowdata[$i][2];												// 基地コード
		$staffData[$i]['compName']     = mb_convert_encoding($rowdata[$i][3], "UTF-8", "auto");			// 基地名

		$staffData[$i]['staffPattern'] = mb_convert_encoding($rowdata[$i][4], "UTF-8", "auto");			// 貸与パターン名


		if (!strtotime($rowdata[$i][5])) {
			$staffData[$i]['rentalStartDay']  = $rowdata[$i][5];										// レンタル開始日

		} else {
			$staffData[$i]['rentalStartDay']  = date('Y/m/d', strtotime($rowdata[$i][5]));				// レンタル開始日

		}

		// 送付先情報
		$staffData[$i]['zip1']         = mb_convert_encoding($rowdata[$i][6], "UTF-8", "auto");			// 郵便番号（前3桁）
		$staffData[$i]['zip2']         = mb_convert_encoding($rowdata[$i][7], "UTF-8", "auto");			// 郵便番号（後4桁）
		$staffData[$i]['addr']         = mb_convert_encoding($rowdata[$i][8], "UTF-8", "auto");			// 住所
		$staffData[$i]['tel']          = mb_convert_encoding($rowdata[$i][9], "UTF-8", "auto");			// 電話番号
		$staffData[$i]['shipName']     = mb_convert_encoding($rowdata[$i][10], "UTF-8", "auto");		// 送付先名
		$staffData[$i]['tantoName']    = mb_convert_encoding($rowdata[$i][11], "UTF-8", "auto");		// 担当者名

		$staffData[$i]['memo']         = mb_convert_encoding($rowdata[$i][12], "UTF-8", "auto");		// メモ

		$staffData[$i]['tokFlag']   = 0;

		$staffData[$i]['item'] = array();

		$itemMax = count($itemdata);

		// 数量指定無。アイテムパターンより取得するよう変更。Y.Furukawa 2017/06/20
		//for ($j = $startColNo - 1, $k = 0; $j < $itemMax; $j+=2, $k++) {
		for ($j = $startColNo - 1, $k = 0; $j < $itemMax; $j++, $k++) {

			$staffData[$i]['item'][$k]['itemNo'] = mb_convert_encoding($itemdata[$j], "UTF-8", "auto");		// アイテムNo
			$staffData[$i]['item'][$k]['size']   = mb_convert_encoding($rowdata[$i][$j], "UTF-8", "auto");	// サイズ
//			$staffData[$i]['item'][$k]['num']    = $rowdata[$i][$j+1];	// 数量

			// 特寸サイズの時は特寸フラグON
			if (strpos($staffData[$i]['item'][$k]['size'], "特") !== false)
			{
				$staffData[$i]['tokFlag']   = 1;
			}
		}

		// 特寸入力欄がある場合は特寸情報を保存
		$staffData[$i]['tok']['height']   = "";
		$staffData[$i]['tok']['weight']   = "";
		$staffData[$i]['tok']['bust']     = "";
		$staffData[$i]['tok']['waist']    = "";
		$staffData[$i]['tok']['hips']     = "";
		$staffData[$i]['tok']['shoulder'] = "";
		$staffData[$i]['tok']['sleeve']   = "";
		$staffData[$i]['tok']['kitake']   = "";
		$staffData[$i]['tok']['yukitake'] = "";
		$staffData[$i]['tok']['inseam']   = "";
		$staffData[$i]['tok']['length']    = "";
		$staffData[$i]['tok']['bikou']    = "";

		if ($startTokNo > 0) {
			$pos = $startTokNo - 1;		// 特寸入力欄開始位置
			if (isset($rowdata[$i][$pos + 0]))  $staffData[$i]['tok']['height']   = mb_convert_encoding($rowdata[$i][$pos + 0],  "UTF-8", "auto");	// 身長
			if (isset($rowdata[$i][$pos + 1]))  $staffData[$i]['tok']['weight']   = mb_convert_encoding($rowdata[$i][$pos + 1],  "UTF-8", "auto");	// 体重
			if (isset($rowdata[$i][$pos + 2]))  $staffData[$i]['tok']['bust']     = mb_convert_encoding($rowdata[$i][$pos + 2],  "UTF-8", "auto");	// バスト
			if (isset($rowdata[$i][$pos + 3]))  $staffData[$i]['tok']['waist']    = mb_convert_encoding($rowdata[$i][$pos + 3],  "UTF-8", "auto");	// ウエスト
			if (isset($rowdata[$i][$pos + 4]))  $staffData[$i]['tok']['hips']     = mb_convert_encoding($rowdata[$i][$pos + 4],  "UTF-8", "auto");	// ヒップ
			if (isset($rowdata[$i][$pos + 5]))  $staffData[$i]['tok']['shoulder'] = mb_convert_encoding($rowdata[$i][$pos + 5],  "UTF-8", "auto");	// 肩幅
			if (isset($rowdata[$i][$pos + 6]))  $staffData[$i]['tok']['sleeve']   = mb_convert_encoding($rowdata[$i][$pos + 6],  "UTF-8", "auto");	// 袖丈
			if (isset($rowdata[$i][$pos + 7]))  $staffData[$i]['tok']['kitake']   = mb_convert_encoding($rowdata[$i][$pos + 7],  "UTF-8", "auto");	// 着丈
			if (isset($rowdata[$i][$pos + 8]))  $staffData[$i]['tok']['yukitake'] = mb_convert_encoding($rowdata[$i][$pos + 8],  "UTF-8", "auto");	// 裄丈
			if (isset($rowdata[$i][$pos + 9]))  $staffData[$i]['tok']['inseam']   = mb_convert_encoding($rowdata[$i][$pos + 9],  "UTF-8", "auto");	// 股下
			if (isset($rowdata[$i][$pos + 10])) $staffData[$i]['tok']['length']   = mb_convert_encoding($rowdata[$i][$pos + 10], "UTF-8", "auto");	// スカート丈
			if (isset($rowdata[$i][$pos + 11])) $staffData[$i]['tok']['bikou']    = mb_convert_encoding($rowdata[$i][$pos + 11], "UTF-8", "auto");	// 特寸備考

		}
	}

//////////////////////////////////////////////////////////
// エクセル表チェック
//////////////////////////////////////////////////////////
$errMsg = checkExcelData($dbConnect, $queueData, $appliReason, $staffData);
if ($errMsg) {
	if (!errorInsert($dbConnect, $queueData, $errMsg)) {
		exit(1);
	}
	if (!updateT_OrderQueue($dbConnect, $queueData, 2, "エラーが発生しました")) {
		exit(1);
	}
	exit(0);
}


//////////////////////////////////////////////////////////
// データ登録
//////////////////////////////////////////////////////////
db_Transaction_Begin($dbConnect);

$staffDataCnt = count($staffData);
for ($i = 0; $i < $staffDataCnt; $i++) {

	if ($staffData[$i]['staffCode'] != '') {

	    // 基地マスタの取得 Y.Furukawa 2017/06/20
	    $userData = getM_Comp($dbConnect, $staffData[$i]['compCode']);
	    if (!$userData) {

			$errMsg = array();
			$errMsg[0]['lineNo'] = $staffData[$i]['lineNo'];
			$errMsg[0]['message'] = "基地・所属先マスタの取得に失敗しました。";

			if (!errorInsert($dbConnect, $queueData, $errMsg)) {
				exit(1);
			}
			if (!updateT_OrderQueue($dbConnect, $queueData, 2, "エラーが発生しました")) {
				exit(1);
			}
			exit(0);

	    }

//		// 重複申請の場合はエラー
//		$dupliOrder = checkDuplicateStaffID($dbConnect, $userData['StaffID']);
//
//		if (!$dupliOrder) {
//			db_Transaction_Rollback($dbConnect);
//
//			$errMsg = array();
//			$errMsg[0]['lineNo'] = $staffData[$i]['lineNo'];
//			$errMsg[0]['message'] = "発注された職員コード:" . $staffData[$i]['staffCode'] . "は現在貸与中です。";
//
//			if (!errorInsert($dbConnect, $queueData, $errMsg)) {
//				exit(1);
//			}
//			if (!updateT_OrderQueue($dbConnect, $queueData, 2, "エラーが発生しました")) {
//				exit(1);
//			}
//			exit(0);
//		}
//
		// 申請番号を新規生成
		//$requestNo = createRequestNo($dbConnect, $queueData['CompID'], 1);
		$staffData[$i]['requestNo'] = createRequestNo($dbConnect, $userData['CompID'], 1);

		if (!$staffData[$i]['requestNo']) {
			db_Transaction_Rollback($dbConnect);

			$errMsg = array();
			$errMsg[0]['lineNo'] = $staffData[$i]['lineNo'];
			$errMsg[0]['message'] = "申請書番号の生成に失敗しました。";

			if (!errorInsert($dbConnect, $queueData, $errMsg)) {
				exit(1);
			}
			if (!updateT_OrderQueue($dbConnect, $queueData, 2, "エラーが発生しました")) {
				exit(1);
			}
			exit(0);
		}

//		// 所属先マスタの取得
//		$userData = _getUser($dbConnect,  $staffData[$i]['staffCode']);
//
//		if (!$userData) {
//			db_Transaction_Rollback($dbConnect);
//
//			$errMsg = array();
//			$errMsg[0]['lineNo'] = $staffData[$i]['lineNo'];
//			$errMsg[0]['message'] = "ユーザー情報の取得に失敗しました。";
//
//			if (!errorInsert($dbConnect, $queueData, $errMsg)) {
//				exit(1);
//			}
//			if (!updateT_OrderQueue($dbConnect, $queueData, 2, "エラーが発生しました")) {
//				exit(1);
//			}
//			exit(0);
//		}

		$patternID='';

		// パターンＩＤの取得
		$patternID = getPatternID($dbConnect,  $staffData[$i]['staffPattern']);

		if (!$patternID) {
			db_Transaction_Rollback($dbConnect);

			$errMsg = array();
			$errMsg[0]['lineNo'] = $staffData[$i]['lineNo'];
			$errMsg[0]['message'] = "貸与パターン情報の取得に失敗しました。";

			if (!errorInsert($dbConnect, $queueData, $errMsg)) {
				exit(1);
			}
			if (!updateT_OrderQueue($dbConnect, $queueData, 2, "エラーが発生しました")) {
				exit(1);
			}
			exit(0);
		}

		// ユーザーの存在チェックし、存在しなかった場合、登録処理
	 	if(getStaff($dbConnect, $queueData, $staffData[$i]['staffCode'])){

			$staffNewId = insertM_Staff($dbConnect, $queueData, $staffData[$i], $userData);

			if($staffNewId == false){
				db_Transaction_Rollback($dbConnect);

				$errMsg = array();
				$errMsg[0]['lineNo'] = $staffData[$i]['lineNo'];
				$errMsg[0]['message'] = "職員発注管理データの追加に失敗しました。";

				if (!errorInsert($dbConnect, $queueData, $errMsg)) {
					exit(1);
				}
				if (!updateT_UserMstQueue($dbConnect, $queueData, 2, "エラーが発生しました")) {
					exit(1);
				}
				exit(0);
			}
		// ユーザーの存在チェックし、存在していたら、エラー
		}else{
			db_Transaction_Rollback($dbConnect);

			$errMsg = array();
			$errMsg[0]['lineNo'] = $staffData[$i]['lineNo'];
			$errMsg[0]['message'] = ERR_MSG_CRON_DBERR_KBN1;
			
			if (!errorInsert($dbConnect, $queueData, $errMsg)) {
				exit(1);
			}
			if (!updateT_UserMstQueue($dbConnect, $queueData, 2, "エラーが発生しました")) {
				exit(1);
			}
			exit(0);
		}

		// T_Staff テーブルの追加
		$result = insertT_Staff($dbConnect, $queueData, $staffData[$i], $userData, $patternID, $staffNewId);
		if (!$result) {
			db_Transaction_Rollback($dbConnect);

			$errMsg = array();
			$errMsg[0]['lineNo'] = $staffData[$i]['lineNo'];
			$errMsg[0]['message'] = "職員発注管理データの追加に失敗しました。";

			if (!errorInsert($dbConnect, $queueData, $errMsg)) {
				exit(1);
			}
			if (!updateT_OrderQueue($dbConnect, $queueData, 2, "エラーが発生しました")) {
				exit(1);
			}
			exit(0);
		}

		$orderData['AppliNo']        = $staffData[$i]['requestNo'];
		$orderData['AppliUserID']    = $queueData['UserID'];
		$orderData['RegistUser']     = $queueData['RegistUser'];
		$orderData['AppliCompCd']    = $userData['CompCd'];
		$orderData['AppliCompName']  = $userData['CompName'];
	 	$orderData['AppliMode']      = APPLI_MODE_ORDER;
		$orderData['AppliSeason']    = 0;
	 	$orderData['AppliReason']    = $appliReason;
		$orderData['CompID']         = $userData['CompID'];
		$orderData['StaffID']        = $staffNewId;
		$orderData['StaffCode']      = $staffData[$i]['staffCode'];
		$orderData['AppliPattern']   = $patternID;
		//$orderData['PersonName']     = $userData['PersonName'];
		// Y.Furukawa スタッフ名不備対応 2018/04/20
		//$orderData['PersonName']     = $userData['staffName'];
		$orderData['PersonName']     = $staffData[$i]['staffName'];

		//$orderData['StaffKbn']       = $staffData[$i]['staffKbn'];
		//$orderData['StaffSaiyoDay']  = $staffData[$i]['saiyoDay'];

		if ((isset($staffData[$i]['zip1']) && $staffData[$i]['zip1'] != '') && (isset($staffData[$i]['zip2']) && $staffData[$i]['zip2'] != '')) {
			$orderData['Zip']            = $staffData[$i]['zip1'] . "-" . $staffData[$i]['zip2'];
		} else {
			$orderData['Zip']            = $userData['Zip'];
		}

		if (isset($staffData[$i]['addr']) && $staffData[$i]['addr'] != '' ) {
			$orderData['Adrr']           = $staffData[$i]['addr'];
		} else {
			$orderData['Adrr']           = $userData['Adrr'];
		}

		if (isset($staffData[$i]['tel']) && $staffData[$i]['tel'] != '' ) {
			$orderData['Tel']            = $staffData[$i]['tel'];
		} else {
			$orderData['Tel']            = $userData['Tel'];
		}

		if (isset($staffData[$i]['shipName']) && $staffData[$i]['shipName'] != '' ) {
			$orderData['ShipName']       = $staffData[$i]['shipName'];
		} else {
			$orderData['ShipName']       = $userData['ShipName'];
		}

		if (isset($staffData[$i]['tantoName']) && $staffData[$i]['tantoName'] != '' ) {
			$orderData['TantoName']       = $staffData[$i]['tantoName'];
		} else {
			$orderData['TantoName']      = $userData['TantoName'];
		}

		$orderData['Note']           = $staffData[$i]['memo'];
		$orderData['RentalStartDay'] = $staffData[$i]['rentalStartDay'];
		$orderData['Status']         = STATUS_APPLI;	// 承認待
		$orderData['Tok']            = $staffData[$i]['tokFlag'];
		$orderData['TokNote']        = $staffData[$i]['tok']['bikou'];

		// T_Order テーブルの追加
		$orderId = insertT_Order($dbConnect, $orderData);

		if (!$orderId) {
			db_Transaction_Rollback($dbConnect);

			$errMsg = array();
			$errMsg[0]['lineNo'] = $staffData[$i]['lineNo'];
			$errMsg[0]['message'] = "発注データの追加に失敗しました。";

			if (!errorInsert($dbConnect, $queueData, $errMsg)) {
				exit(1);
			}
			if (!updateT_OrderQueue($dbConnect, $queueData, 2, "エラーが発生しました")) {
				exit(1);
			}
			exit(0);
		}

		// T_Tok テーブルの追加
		if ($staffData[$i]['tokFlag'] == '1') {
			if (!insertT_Tok($dbConnect, $orderId, $orderData, $staffData[$i]['tok'])) {
				db_Transaction_Rollback($dbConnect);

				$errMsg = array();
				$errMsg[0]['lineNo'] = $staffData[$i]['lineNo'];
				$errMsg[0]['message'] = "特寸データの追加に失敗しました。";

				if (!errorInsert($dbConnect, $queueData, $errMsg)) {
					exit(1);
				}
				if (!updateT_OrderQueue($dbConnect, $queueData, 2, "エラーが発生しました")) {
					exit(1);
				}
				exit(0);
			}
		}

		$stockData = array();
		$stockCnt = count($staffData[$i]['item']);
		for ($j = 0, $k = 0; $j < $stockCnt; $j++) {
//			if ($staffData[$i]['item'][$j]['size'] != '' && $staffData[$i]['item'][$j]['num'] != '') {
			if ($staffData[$i]['item'][$j]['size'] != '') {

				$stockData[$k] = getM_StockCtrl($dbConnect, $staffData[$i]['item'][$j]['itemNo'], $staffData[$i]['item'][$j]['size'], $patternID);
				if (!$stockData[$k]) {
					db_Transaction_Rollback($dbConnect);

					$errMsg = array();
					$errMsg[0]['lineNo'] = $staffData[$i]['lineNo'];
					$errMsg[0]['message'] = "在庫コード：" . $itemNo . " " . $size . " の取得に失敗しました。";

					if (!errorInsert($dbConnect, $queueData, $errMsg)) {
						exit(1);
					}
					if (!updateT_OrderQueue($dbConnect, $queueData, 2, "エラーが発生しました")) {
						exit(1);
					}
					exit(0);
				}
				//$stockData[$k]['Num'] = $staffData[$i]['item'][$j]['num'];
				$k++;
			}
		}

		// T_Staff_DetailsとT_Order_Detailsの登録
		if (!insertT_Staff_Order_Details($dbConnect, $orderId, $orderData, $stockData)) {

			db_Transaction_Rollback($dbConnect);

			$errMsg = array();
			$errMsg[0]['lineNo'] = $staffData[$i]['lineNo'];
			$errMsg[0]['message'] = "発注明細データの追加に失敗しました。";

			if (!errorInsert($dbConnect, $queueData, $errMsg)) {
				exit(1);
			}
			if (!updateT_OrderQueue($dbConnect, $queueData, 2, "エラーが発生しました")) {
				exit(1);
			}
			exit(0);
		}
	}
}

////}

db_Transaction_Commit($dbConnect);

if (!updateT_OrderQueue($dbConnect, $queueData, 1, "正常終了")) {
	exit(1);
}

// 一括発注申請メール送信
//$isSuccess = sendMailIkkatsu($dbConnect, $queueData, $staffData, $appliReason);


//////////////////////////////////////////////////////////
// 発注申請メールを送信する
//////////////////////////////////////////////////////////
function sendMailIkkatsu($dbConnect, $queueData, $staffData, $appliReason) {

	$filePath = '../../mail_template/';

	$corpCd = $queueData['CorpCd'];		// 会社コード
	$compId = $queueData['CompID'];		// 料金所ID

	for ($i = 0; $i < count($staffData); $i++) {
		$appliData[$i]['AppliNo']  = $staffData[$i]['requestNo'];		// 申請番号
		$appliData[$i]['StaffCd']  = $staffData[$i]['staffCode'];		// 社員番号
		$appliData[$i]['StaffKbn'] = $staffData[$i]['staffKbn'];		// 1:新規/2:更新/3:その他
	}

	// 申請メールの件名と本文を取得
	$isSuccess = hachuIkkatsuMail($dbConnect, $compId, $appliReason, $appliData, $filePath, $subject, $message);

	if ($isSuccess == false) {
		return false;
	}

	// 送付先会社アドレス設定
	switch ($corpCd) {
		case HONSYA_NAME_YOKOHAMA_CD:	// 横浜
			$toAddr = MAIL_GROUP_8_1;
			break;
		case HONSYA_NAME_NAGOYA_CD:		// 名古屋
			$toAddr = MAIL_GROUP_8_2;
			break;
	}

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

//////////////////////////////////////////////////////////
// T_Order テーブルInsert
//////////////////////////////////////////////////////////
function insertT_Order($dbConnect, $orderData) {

	$sql  = "";
	$sql .= " INSERT INTO T_Order (";
	$sql .= "  AppliDay";
	$sql .= " ,AppliNo";
	$sql .= " ,AppliUserID";
	$sql .= " ,AppliCompCd";
	$sql .= " ,AppliCompName";
	$sql .= " ,AppliMode";
	$sql .= " ,AppliSeason";
	$sql .= " ,AppliReason";
	$sql .= " ,AppliPattern";
//	$sql .= " ,CorpCd";
	$sql .= " ,CompID";
    $sql .= " ,StaffID";
	$sql .= " ,StaffCode";
	$sql .= " ,PersonName";
//	$sql .= " ,StaffKbn";
	$sql .= " ,RentalStartDay";
	$sql .= " ,Zip";
	$sql .= " ,Adrr";
	$sql .= " ,Tel";
	$sql .= " ,ShipName";
	$sql .= " ,TantoName";
	$sql .= " ,Note";
	$sql .= " ,Status";
	$sql .= " ,Tok";
	$sql .= " ,TokNote";
	$sql .= " ,Del";
	$sql .= " ,RegistDay";
	$sql .= " ,RegistUser";
	$sql .= " )VALUES(";
	$sql .= " GETDATE()"; // AppliDay
	$sql .= ",'". db_Escape($orderData['AppliNo'])."'"; 			// AppliNo
	$sql .= ",'". db_Escape($orderData['AppliUserID'])."'"; 		// AppliUserID
	$sql .= ",'". db_Escape($orderData['AppliCompCd'])."'"; 		// AppliCompCd
	$sql .= ",'". db_Escape($orderData['AppliCompName'])."'"; 		// AppliCompName
	$sql .= ",'". db_Escape($orderData['AppliMode'])."'"; 			// AppliMode
	$sql .= ",'". db_Escape($orderData['AppliSeason'])."'";			// AppliSeason
	$sql .= ",'". db_Escape($orderData['AppliReason'])."'";			// AppliReason
	$sql .= ",'". db_Escape($orderData['AppliPattern'])."'";			// AppliPattern
//	$sql .= ",'". db_Escape($orderData['CorpCd'])."'";				// CorpCd
	$sql .= ",'". db_Escape($orderData['CompID'])."'";				// CompID
    $sql .= ",'". db_Escape($orderData['StaffID'])."'";				// StaffID
	$sql .= ",'". db_Escape($orderData['StaffCode'])."'"; 			// StaffCode
	$sql .= ",'". db_Escape($orderData['PersonName'])."'"; 			// PersonName
//	$sql .= ",'". db_Escape($orderData['StaffKbn'])."'"; 			// StaffKbn
	$sql .= ",'". db_Escape($orderData['RentalStartDay'])."'"; 		// RentalStartDay
	$sql .= ",'". db_Escape($orderData['Zip'])."'"; 				// Zip
	$sql .= ",'". db_Escape($orderData['Adrr'])."'"; 				// Adrr
	$sql .= ",'". db_Escape($orderData['Tel'])."'"; 				// Tel
	$sql .= ",'". db_Escape($orderData['ShipName'])."'"; 			// ShipName
	$sql .= ",'". db_Escape($orderData['TantoName'])."'"; 			// TantoName
	$sql .= ",'". db_Escape($orderData['Note'])."'"; 				// Note
	$sql .= ",'". db_Escape($orderData['Status'])."'"; 				// Status
	$sql .= ",'". db_Escape($orderData['Tok'])."'";					// Tok
	$sql .= ",'". db_Escape($orderData['TokNote'])."'";				// TokNote
	$sql .= "," . DELETE_OFF; 										// Del
	$sql .= ",GETDATE()"; 											// RegistDay
	$sql .= ",'". db_Escape($orderData['RegistUser'])."'"; 			// RegistUser
	$sql .= " )";

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

	return $orderId;
}

//////////////////////////////////////////////////////////
// T_Tok テーブルInsert
//////////////////////////////////////////////////////////
function insertT_Staff_Order_Details($dbConnect, $orderId, $orderData, $stockData) {

	// アイテム数だけオーダー詳細情報へ追加
	$max = count($stockData);
	for($i = 0, $k = 1; $i < $max; $i++) {
		for($j = 0; $j < $stockData[$i]['Num']; $j++){

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
			$sql .= 		" Status,";
			$sql .= 		" AppliDay,";
			$sql .= 		" Del,";
			$sql .= 		" RegistDay,";
			$sql .= 		" RegistUser";
			$sql .= 		" )";
			$sql .= " VALUES";
			$sql .= 		" (";
			$sql .= 		" '" . db_Escape($orderId) ."',";
			$sql .= 		" '" . db_Escape($orderData['AppliNo']) ."',";
			$sql .= 		" '" . db_Escape($k) ."',";
			$sql .= 		" '" . db_Escape($stockData[$i]['ItemID']) ."',";
			$sql .= 		" '" . db_Escape($stockData[$i]['ItemNo']) ."',";
			$sql .= 		" '" . db_Escape($stockData[$i]['ItemName']) ."',";
			$sql .= 		" '" . db_Escape($stockData[$i]['Size']) ."',";
			$sql .= 		" '" . db_Escape($stockData[$i]['StockCD']) ."',";
			$sql .= 		" '" . db_Escape($orderData['Status']) ."',";
			$sql .= 		" GETDATE(),";
			$sql .= 		" " . DELETE_OFF . ",";
			$sql .= 		" GETDATE(),";
			$sql .= 		" '". db_Escape($orderData['RegistUser'])."'";
			$sql .= 		" );";

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

			$orderDetId = $result[0]['scope_identity'];

			// T_Staff_Detailsの登録
			$sql  = "";
			$sql .= " INSERT INTO";
			$sql .= 	" T_Staff_Details";
			$sql .= 		" (";
			$sql .= 		" StaffID,";
			$sql .= 		" OrderDetID,";
			$sql .= 		" Status,";
			$sql .= 		" ReturnFlag,";
			$sql .= 		" Del,";
			$sql .= 		" RegistDay,";
			$sql .= 		" RegistUser";
			$sql .= 		" )";
			$sql .= " VALUES";
			$sql .= 		" (";
			$sql .= 		" '" . db_Escape($orderData['StaffID']) ."',";
			$sql .= 		" '" . db_Escape($orderDetId) ."',";
			$sql .= 		" '" . db_Escape($orderData['Status']) ."',";
			$sql .= 		COMMON_FLAG_OFF . ",";
			$sql .= 		" " . DELETE_OFF . ",";
			$sql .= 		" GETDATE(),";
			$sql .= 		" '". db_Escape($orderData['RegistUser'])."'";
			$sql .= 		" );";

			$isSuccess = db_Execute($dbConnect, $sql);

			// 実行結果が失敗の場合
			if ($isSuccess == false) {
				return false;
			}

			$k++;

		}
	}

	return true;
}

//////////////////////////////////////////////////////////
// T_Tok テーブルInsert
//////////////////////////////////////////////////////////
function insertT_Tok($dbConnect, $orderId, $orderData, $tokData) {

	// T_Tokに登録する
	$sql  = "";
	$sql .= " INSERT INTO";
	$sql .= 	" T_Tok";
	$sql .= 		" (";
	$sql .=			"  OrderID";
	$sql .= 		" ,Height";
	$sql .= 		" ,Weight";
	$sql .= 		" ,Bust";
	$sql .= 		" ,Waist";
	$sql .= 		" ,Hips";
	$sql .= 		" ,Shoulder";
	$sql .= 		" ,Sleeve";
//	$sql .= 		" ,Neck";
	$sql .= 		" ,Length";
    $sql .=         " ,Kitake";
    $sql .=         " ,Yukitake";
    $sql .=         " ,Inseam";
//  $sql .=         " ,Head";
	$sql .= 		" ,Del";
	$sql .= 		" ,RegistDay";
	$sql .= 		" ,RegistUser";
	$sql .= 		" )";
	$sql .= " VALUES";
	$sql .= 		" (";
	$sql .=			"  " . db_Escape($orderId) . ",";
	$sql .= 		" '" . db_Escape($tokData['height']) . "',";		// 身長
	$sql .= 		" '" . db_Escape($tokData['weight']) . "',";		// 体重
	$sql .= 		" '" . db_Escape($tokData['bust']) . "',";			// バスト
	$sql .= 		" '" . db_Escape($tokData['waist']) . "',";			// ウエスト
	$sql .= 		" '" . db_Escape($tokData['hips']) . "',";			// ヒップ
	$sql .= 		" '" . db_Escape($tokData['shoulder']) . "',";		// 肩幅
	$sql .= 		" '" . db_Escape($tokData['sleeve']) . "',";		// 袖丈
//  $sql .= 		" '" . db_Escape($tokData['neck']) . "',";			// 首周り
	$sql .= 		" '" . db_Escape($tokData['length']) . "',";		// スカート丈
    $sql .=         " '" . db_Escape($tokData['kitake']) . "',";		// 着丈
    $sql .=         " '" . db_Escape($tokData['yukitake']) . "',";		// 裄丈
    $sql .=         " '" . db_Escape($tokData['inseam']) . "',";		// 股下
//  $sql .=         " '" . db_Escape($tokData['head']) . "',";			// 頭囲
	$sql .= 		" " . DELETE_OFF . ",";								// Del
	$sql .= 		" GETDATE(),";										// RegistDay
	$sql .= 		" '". db_Escape($orderData['RegistUser'])."'"; 	// RegistUser
	$sql .= 		" )";

	$isSuccess = db_Execute($dbConnect, $sql);

	// 実行結果が失敗の場合
	if ($isSuccess == false) {
		return false;
	}

	return true;
}

//////////////////////////////////////////////////////////
// T_Staff テーブルInsert
//////////////////////////////////////////////////////////
// T_Staffの情報を取得検索し、登録されていない場合登録する。
// T_Staffは最新の申請時の所属先情報
//
function insertT_Staff($dbConnect, $queueData, $staffData, $userData, $patternID, $staffNewId) {

	// 検索
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" tsf.StaffID";
	$sql .= " FROM";
	$sql .= 	" T_Staff tsf";
	$sql .= " INNER JOIN";
	$sql .= 	" M_Comp mcp";
	$sql .= " ON";
	$sql .= 	" tsf.CompID = mcp.CompID";
	$sql .= " AND";
	$sql .= 	" mcp.Del = ".DELETE_OFF;
	$sql .= " WHERE";
	$sql .= 	" tsf.StaffCode = '" . db_Escape($staffData['staffCode']) . "'";
//	$sql .= " AND";
//	$sql .= 	" mcp.CorpCd = '" . db_Escape($queueData['CorpCd']) . "'";
	$sql .= " AND";
	$sql .= 	" tsf.Del = " . DELETE_OFF;
//var_dump($sql);die;
	$result = db_Read($dbConnect, $sql);

	$staffId = '';
	if (isset($result[0]['StaffID'])) {
		$staffId = $result[0]['StaffID'];
	}

	//  職員がまだ登録されていない場合登録
    if ($staffId == '') {

		// T_Staffに登録する
		$sql  = "";
		$sql .= " INSERT INTO";
		$sql .= 	" T_Staff";
		$sql .= 		" (";
		$sql .= 		" StaffID,";
		$sql .= 		" CompID,";
		$sql .= 		" StaffCode,";
		// パターンＩＤ追加 Y.Furukawa 2017/05/02
		$sql .= 		" PatternID,";
//		$sql .= 		" PersonName_Sei,";
//		$sql .= 		" PersonName_Mei,";
//		$sql .= 		" StaffSaiyoDay,";
		$sql .= 		" WithdrawalFlag,";
		$sql .= 		" AllReturnFlag,";
		$sql .= 		" Del,";
		$sql .= 		" RegistDay,";
		$sql .= 		" RegistUser";
		$sql .= 		" )";
		$sql .= " VALUES";
		$sql .= 		" (";
		//$sql .= 		" '" . db_Escape($userData['StaffID']) . "',";
		$sql .= 		" '" . db_Escape($staffNewId) . "',";
		$sql .= 		" '" . db_Escape($userData['CompID']) . "',";
		$sql .= 		" '" . db_Escape($staffData['staffCode']) . "',";
		// パターンＩＤ追加 Y.Furukawa 2017/05/02
		$sql .= 		" '" . db_Escape($patternID) . "',";

//		$sql .= 		" '" . db_Escape($staffData['name_sei']) . "',";
//		$sql .= 		" '" . db_Escape($staffData['name_mei']) . "',";
//		$sql .= 		" '" . db_Escape($staffData['saiyoDay']) . "',";
		$sql .= 		COMMON_FLAG_OFF . ",";
		$sql .= 		COMMON_FLAG_OFF . ",";
		$sql .= 		" " . DELETE_OFF . ",";
		$sql .= 		" GETDATE(),";
		$sql .= 		" '" . db_Escape($queueData['RegistUser']) . "'";
		$sql .= 		" )";

		$isSuccess = db_Execute($dbConnect, $sql);

		// 実行結果が失敗の場合
		if ($isSuccess == false) {
			return false;
		}
	} else {

		// T_Staffを更新する
//		$sql  = "";
//		$sql .= " UPDATE T_Staff";
//		$sql .= " SET";
//		$sql .= 	" CompID         = '" . db_Escape($userData['CompID']) . "',";
//		$sql .= 	" PatternID      = '" . db_Escape($patternID) . "',";
//		$sql .= 	" WithdrawalFlag = '" . COMMON_FLAG_OFF . "',";
//		$sql .= 	" AllReturnFlag  = '" . COMMON_FLAG_OFF . "',";
//		$sql .= 	" UpdDay         = GETDATE(),";
//		$sql .= 	" UpdUser        = '" . db_Escape($queueData['RegistUser']) . "'";
//		$sql .= " WHERE";
//		$sql .= 	" StaffID        = '" . db_Escape($userData['StaffID']) . "'";
//
//		$isSuccess = db_Execute($dbConnect, $sql);
//
//		// 実行結果が失敗の場合
//		if ($isSuccess == false) {
//var_dump($sql);die;
//
		return false;
//		}

	}

	return true;
}



/*
 * ユーザー追加処理
 * 引数  ：$dbConnect => コネクション
 * 引数  ：$data      => エクセルデータ
 * 引数  ：$list      => エクセル登録者情報
 * 引数  ：$rtn       => 登録情報
 * create 2007/11/05 DF
 *
 */
function insertM_Staff($dbConnect, $queueData, $staffData, $userData){

// M_Staffの情報を取得検索し、登録されていない場合登録する。

	// M_Staff追加
	$sql  = " INSERT INTO M_Staff (";
    $sql .= " CompID";
	$sql .= " ,CompCd";
	$sql .= " ,StaffCode";
	$sql .= " ,PersonName";
	$sql .= " ,Del";
	$sql .= " ,RegistDay";
	$sql .= " ,RegistUser";
	$sql .= " ,UpdDay";
	$sql .= " ,UpdUser";
	$sql .= " ) VALUES (";
	
    $sql .= "'".db_Escape(trim($userData['CompID']))."'";
 	$sql .= ",'".db_Escape(trim($userData['CompCd']))."'";
	$sql .= ",'".db_Escape(trim($staffData['staffCode']))."'";
	$sql .= ",'".db_Escape(trim($staffData['staffName']))."'";
	$sql .= " ,". DELETE_OFF;
	$sql .= " ,GETDATE()";
	$sql .= " ,'".db_Escape(trim($queueData['RegistUser']))."' ";
	$sql .= " ,NULL";
	$sql .= " ,NULL";
	$sql .= " )";

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

	$staffId = $result[0]['scope_identity'];

	return $staffId;

//	return true;

}

//////////////////////////////////////////////////////////
// T_OrderQueue テーブルUpdate
//////////////////////////////////////////////////////////
function updateT_OrderQueue($dbConnect, $queueData, $flg, $msg) {

	$sql  = " UPDATE T_OrderQueue SET  ";
	$sql .= "  CompFlag          = '" . db_Escape($flg) . "'";
	$sql .= " ,CompDay           = GETDATE()";
	$sql .= " ,CompMsg           = '" . db_Escape($msg) . "'";
	$sql .= " ,UpdDay            = GETDATE()";
	$sql .= " ,UpdUser           = '" . db_Escape($queueData['RegistUser']) ."' ";
	$sql .= " WHERE ";
	$sql .= " UserUpID = '" . db_Escape($queueData['UserUpID']) . "'";

	$isSuccess = db_Execute($dbConnect, $sql);

	// 実行結果が失敗の場合
	if ($isSuccess == false) {
		return false;
	}

	return true;
}

function getM_Comp($dbConnect, $compCd) {

	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	"  CompID";
	$sql .= 	" ,CorpCd";        // 会社コード
	$sql .= 	" ,CorpName";      // 会社名
	$sql .= 	" ,HonbuCd";       // 本部コード
	$sql .= 	" ,HonbuName";     // 本部名
	$sql .= 	" ,ShibuCd";       // 支部コード
	$sql .= 	" ,ShibuName";     // 支部名
	$sql .= 	" ,CompCd";        // 基地コード
	$sql .= 	" ,CompName";      // 基地名
	$sql .= 	" ,CompKind";
	$sql .= 	" ,Zip";
	$sql .= 	" ,Adrr";
	$sql .= 	" ,Tel";
	$sql .= 	" ,ShipName";
	$sql .= 	" ,TantoName";
	$sql .= " FROM";
	$sql .= 	" M_Comp";
	$sql .= " WHERE";
	$sql .= 	" CompCd = '".db_Escape($compCd)."'";
	$sql .= " AND";
	$sql .= 	" Del = ".DELETE_OFF;
	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		return false;
	}

	return $result[0];
}

function getM_StockCtrl($dbConnect, $itemNo, $size, $patternID) {

	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" mi.ItemID";
	$sql .= 	",mi.ItemNo";
	$sql .= 	",mi.ItemName";
	$sql .= 	",ms.StockCD";
	$sql .= 	",ms.Size";
	$sql .= 	",mis.ItemSelectNum as Num";
	$sql .= " FROM";
	$sql .= 	" M_StockCtrl ms";
	$sql .= " INNER JOIN";
	$sql .= 	" M_Item mi";
	$sql .= " ON";
	$sql .= 	" ms.ItemNo = mi.ItemNo";
	$sql .= " AND";
	$sql .= 	" mi.Del = " . DELETE_OFF;

	$sql .= " INNER JOIN";
	$sql .= 	" M_ItemSelect mis";
	$sql .= " ON";
	$sql .= 	" mis.ItemNo = mi.ItemNo";
	$sql .= " AND";
	$sql .= 	" mis.PatternID = '" . db_Escape($patternID) . "'";
	$sql .= " AND";
	$sql .= 	" mis.Del = " . DELETE_OFF;

	$sql .= " WHERE";
	$sql .= 	" ms.ItemNo = '" . db_Escape($itemNo) . "'";
	$sql .= " AND";
	$sql .= 	" ms.Size = '" . db_Escape($size) . "'";
	$sql .= " AND";
	$sql .= 	" ms.Del = " . DELETE_OFF;

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {

		return false;
	}

	return $result[0];
}

// 更新対象データをT_UserMstQueueから抽出
function getT_OrderQueue($dbConnect, $queueId) {

	$sql  = "";
	$sql .= " SELECT";
	$sql .= "  UserUpID";
	$sql .= " ,UserID";
	$sql .= " ,UserUpDay";
	$sql .= " ,UserUpFile";
	$sql .= " ,UserUpSetFile";
	$sql .= " ,CompFlag";
	$sql .= " ,CompDay";
	$sql .= " ,CompMsg";
	$sql .= " ,Del";
	$sql .= " ,RegistDay";
	$sql .= " ,RegistUser";
	$sql .= " ,UpdDay";
	$sql .= " ,UpdUser";
	
	$sql .= " FROM";
	$sql .= " T_OrderQueue";

	$sql .= " WHERE";
	$sql .= 	" UserUpID = '".db_Escape($queueId)."'";
	$sql .= " AND";
	$sql .= 	" CompFlag = " . UPLOAD_FILE_COMP_FLAG_WAIT;
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		return false;
	}


	$sql  = " UPDATE T_OrderQueue SET  ";
	$sql .= 	" CompFlag = 99";	// 実行中
	$sql .= " WHERE ";
	$sql .= 	" UserUpID = '".db_Escape($queueId)."'";
	$sql .= " AND";
	$sql .= 	" Del = ".DELETE_OFF;

	$isSuccess = db_Execute($dbConnect, $sql);
	// 実行結果が失敗の場合
	if ($isSuccess == false) {
		return false;
	}

	return $result[0];
}

//////////////////////////////////////////////////////////
// データ整合性チェック
//////////////////////////////////////////////////////////
function checkExcelData($dbConnect, $queueData, $appliReason, $staffData) {

	$errMsg = array();
	$errMsgCnt = 0;

//	if ($appliReason != APPLI_REASON_ORDER_COMMON) {	// 共用品以外
//	if ($appliReason == APPLI_REASON_ORDER_EXCEL) {     // エクセル一括発注申請
//
//		// アイテムパターンマスタから発注可能な最小値、最大値を取得する。
//		$itemSelect = getItemSelect($dbConnect, $queueData, $appliReason);
//		if (!$itemSelect) {
//			$errMsg[$errMsgCnt]['lineNo'] = 1;
//			$errMsg[$errMsgCnt]['message'] = "貸与パターンが取得できませんでした。";
//			return $errMsg;
//		}
//	}
// 「対象職員の許可された貸与パターンは存在しません。」
// 「対象職員の貸与パターンは存在しません。」
// "アイテムNo：" . $staffData[$i]['item'][$j]['itemNo'] . "は許可されたパターンに一致しません。";
//
//	// 所属先マスタの取得
//	$compData = getM_Comp($dbConnect, $queueData['CompID']);
//	if (!$compData) {
//		$errMsg[$errMsgCnt]['lineNo'] = 1;
//		$errMsg[$errMsgCnt]['message'] = "営業所・部署マスタの取得に失敗しました。";
//		return $errMsg;
//	}

	$staffCnt = count($staffData);
//var_dump("staffCnt:" . $staffCnt);
	for ($i = 0; $i < $staffCnt; $i++) {

		// 職員コードがある時のみ、処理
		if ($staffData[$i]['staffCode'] != '') {

//		if ($appliReason != APPLI_REASON_ORDER_COMMON) {	// 共用品以外


			// 社員番号の判定
			$result = checkData($staffData[$i]['staffCode'], 'HalfWidth', true, 8, 8);
			switch ($result) {
				case 'empty':	// 空白
					$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
					$errMsg[$errMsgCnt]['message'] = "職員コードは省略できません。";
					$errMsgCnt++;
					break;
				case 'mode':	// 半角以外
				case 'max':		// 最大値超過ならば
				case 'min':		// 最小値未満ならば
					$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
					$errMsg[$errMsgCnt]['message'] = "職員コードは半角8文字で入力して下さい。";
					$errMsgCnt++;
					break;
				default:
					break;
			}

			// 職員名の判定
		    $result = checkData($staffData[$i]['staffName'], 'Text', true, 40);
			switch ($result) {
				case 'empty':	// 空白
					$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
					$errMsg[$errMsgCnt]['message'] = "職員名は省略できません。";
					$errMsgCnt++;
					break;
				case 'max':		// 最大値超過ならば
					$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
					$errMsg[$errMsgCnt]['message'] = "職員名は全角20(半角40)文字までで入力して下さい。";
					$errMsgCnt++;
					break;
				default:
					break;
			}

			// 職員マスタに登録されているかどうか
			$isStaff = getStaff($dbConnect, $queueData, $staffData[$i]['staffCode']);
			if (!$isStaff) {
				$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
				//$errMsg[$errMsgCnt]['message'] = "職員情報を取得できませんした。職員情報が登録されているか、ご確認ください。";
				$errMsg[$errMsgCnt]['message'] = "職員コード：" . $staffData[$i]['staffCode'] . "はマスタに既に登録されています。" ;
				$errMsgCnt++;
//				return $errMsg;
			}

			// 基地コードの判定
			$result = checkData($staffData[$i]['compCode'], 'HalfWidth', true, 6, 6);
			switch ($result) {
				case 'empty':	// 空白
					$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
					$errMsg[$errMsgCnt]['message'] = "基地コードは省略できません。";
					$errMsgCnt++;
					break;
				case 'mode':	// 半角以外
				case 'max':		// 最大値超過ならば
				case 'min':		// 最小値未満ならば
					$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
					$errMsg[$errMsgCnt]['message'] = "基地コードは半角6文字で入力して下さい。";
					$errMsgCnt++;
					break;
				default:
					break;
			}

//			// 職員名の判定
//		    $result = checkData($staffData[$i]['compName'], 'Text', true, 80);
//			switch ($result) {
//				case 'empty':	// 空白
//					$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
//					$errMsg[$errMsgCnt]['message'] = "基地名は省略できません。";
//					$errMsgCnt++;
//					break;
//				case 'max':		// 最大値超過ならば
//					$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
//					$errMsg[$errMsgCnt]['message'] = "基地名は全角40(半角80)文字までで入力して下さい。";
//					$errMsgCnt++;
//					break;
//				default:
//					break;
//			}

	    	// 基地マスタの取得 Y.Furukawa 2017/06/20
	    	$compData = getM_Comp($dbConnect, $staffData[$i]['compCode']);
	    	if (!$compData) {
	    		$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
	    		$errMsg[$errMsgCnt]['message'] = "基地・所属先マスタの取得に失敗しました。";
				$errMsgCnt++;
	    	}


			if ($isStaff && $compData) { 
				// アイテムパターンマスタから発注可能な最小値、最大値を取得する。
				//$return = getStaffItemSelect($dbConnect, $queueData, $appliReason, $staffData[$i]['staffCode'], $staffData[$i]['staffPattern']);
				$return = getStaffItemSelect($dbConnect, $queueData, $appliReason, $staffData[$i]['compCode'], $staffData[$i]['staffPattern']);
				if (!$return) {
					$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
					//$errMsg[$errMsgCnt]['message'] = "対象職員の所属部署に対応した貸与パターンは存在しません。";
					$errMsg[$errMsgCnt]['message'] = "対象職員の指定された基地に対応した貸与パターンは存在しません。";
					$errMsgCnt++;
//				return $errMsg;
				}
				// スタッフが存在すれば、エラーとなったため、不要 Y.Furukawa 2017/06/20
////			// 重複申請の場合はエラー
////			$dupliOrder = checkDuplicateStaffCode($dbConnect, $staffData[$i]['staffCode']);
////
////			if (!$dupliOrder) {
////				$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
////				$errMsg[$errMsgCnt]['message'] = "発注された職員コード:" . $staffData[$i]['staffCode'] . "は現在貸与中です。";
////				$errMsgCnt++;
////			}
			}

			// 貸与パターン名の判定
		    $result = checkData($staffData[$i]['staffPattern'], 'Text', true, 40);
			switch ($result) {
				case 'empty':	// 空白
					$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
					$errMsg[$errMsgCnt]['message'] = "貸与パターン名は省略できません。";
					$errMsgCnt++;
					break;
				default:
					break;
			}

			// 貸与パターン取得エラー
		    $itemSelect = getItemSelect($dbConnect, $queueData, $staffData[$i]['staffPattern']);
			if (!$itemSelect) {
				$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
				$errMsg[$errMsgCnt]['message'] = "貸与パターンが取得できませんでした。";
				$errMsgCnt++;
//				return $errMsg;
			}

			// レンタル開始日の判定
		    $result = checkData($staffData[$i]['rentalStartDay'], 'Date', true);
			switch ($result) {
				case 'empty':	// 空白
					$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
					$errMsg[$errMsgCnt]['message'] = "レンタル開始日は省略できません。";
					$errMsgCnt++;
					break;
				case 'mode':	// 存在しない日付
					$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
					$errMsg[$errMsgCnt]['message'] = "レンタル開始日が正しい日付ではありません。";
					$errMsgCnt++;
					break;
				default:
					break;
			}

            // 郵便番号（前半）が存在しなければ初期化
            if (!isset($staffData[$i]['zip1'])) {
                $staffData[$i]['zip1'] = '';
            }

            // 郵便番号（前半）の判定
            $isZipError = false;
            $result = checkData(trim($staffData[$i]['zip1']), 'Digit', true, 3, 3);

            // エラーが発生したならば、エラーメッセージを取得
            switch ($result) {

                // 半角以外の文字ならば
                case 'mode':
                    $isZipError = true;
					$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
					$errMsg[$errMsgCnt]['message'] = "郵便番号は半角数値の[3桁]-[4桁]で入力してください。";
					$errMsgCnt++;
                    break;

                // 指定文字数以外ならば
                case 'max':
                case 'min':
                    $isZipError = true;
					$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
					$errMsg[$errMsgCnt]['message'] = "郵便番号は半角数値の[3桁]-[4桁]で入力してください。";
					$errMsgCnt++;
                    break;

                default:
                    break;
            }

            // エラーが発生したならば、エラーメッセージを取得
            if ($isZipError == false) {

                // 郵便番号（後半）が存在しなければ初期化
                if (!isset($staffData[$i]['zip2'])) {
                    $staffData[$i]['zip2'] = '';
                }

                // 郵便番号（後半）の判定
                $result = checkData(trim($staffData[$i]['zip2']), 'Digit', true, 4, 4);

                // エラーが発生したならば、エラーメッセージを取得
                switch ($result) {

                    // 半角以外の文字ならば
                    case 'mode':
						$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
						$errMsg[$errMsgCnt]['message'] = "郵便番号は半角数値の[3桁]-[4桁]で入力してください。";
						$errMsgCnt++;
                        break;

                    // 指定文字数以外ならば
                    case 'max':
                    case 'min':
						$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
						$errMsg[$errMsgCnt]['message'] = "郵便番号は半角数値の[3桁]-[4桁]で入力してください。";
						$errMsgCnt++;
                        break;

                    default:
                        break;

                }
            }

    		// 住所が存在しなければ初期化
    		if (!isset($staffData[$i]['addr'])) {
    		    $staffData[$i]['addr'] = '';
    		}

    		// 住所の判定
    		$result = checkData(trim($staffData[$i]['addr']), 'Text', true, 240);

    		// エラーが発生したならば、エラーメッセージを取得
    		switch ($result) {

    		    // 最大値超過ならば
    		    case 'max':
					$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
					$errMsg[$errMsgCnt]['message'] = "住所は全角120文字以内で入力してください。";
					$errMsgCnt++;
    		        break;

    		    default:
    		        break;

    		}

            // 電話番号が存在しなければ初期化
            if (!isset($staffData[$i]['tel'])) {
                $staffData[$i]['tel'] = '';
            }

            // 電話番号の判定
            $result = checkData(trim($staffData[$i]['tel']), 'Tel', true, 15);

            // エラーが発生したならば、エラーメッセージを取得
            switch ($result) {

                // 電話番号に利用可能な文字（数値とハイフン）以外の文字ならば
                case 'mode':
					$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
					$errMsg[$errMsgCnt]['message'] = "電話番号は半角数値で入力してください。";
					$errMsgCnt++;
                    break;

                // 最大値超過ならば
                case 'max':
					$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
					$errMsg[$errMsgCnt]['message'] = "電話番号は半角数値15文字以内で入力してください。";
					$errMsgCnt++;
                    break;

                default:
                    break;

            }

            // 出荷先名が存在しなければ初期化
            if (!isset($staffData[$i]['shipName'])) {
                $staffData[$i]['shipName'] = '';
            }

            // 出荷先名の判定
            $result = checkData(trim($staffData[$i]['shipName']), 'Text', true, 120);

            // エラーが発生したならば、エラーメッセージを取得
            switch ($result) {

                // 最大値超過ならば
                case 'max':
					$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
					$errMsg[$errMsgCnt]['message'] = "出荷先名は全角60文字以内で入力してください。";
					$errMsgCnt++;
                    break;

                default:
                    break;

            }

            // ご担当者が存在しなければ初期化
            if (!isset($staffData[$i]['tantoName'])) {
                $staffData[$i]['tantoName'] = '';
            }

            // ご担当者の判定
            $result = checkData(trim($staffData[$i]['tantoName']), 'Text', true, 40);

            // エラーが発生したならば、エラーメッセージを取得
            switch ($result) {

                // 最大値超過ならば
                case 'max':
					$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
					$errMsg[$errMsgCnt]['message'] = "ご担当者は全角20文字以内で入力してください。";
					$errMsgCnt++;
                    break;

                default:
                    break;

            }

			// メモの判定
		    $result = checkData($staffData[$i]['memo'], 'Text', false, 128);
			switch ($result) {
				case 'max':		// 最大値超過ならば
					$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
					$errMsg[$errMsgCnt]['message'] = "メモは全角64(半角128)文字までで入力して下さい。";
					$errMsgCnt++;
					break;
				default:
					break;
			}

			// アイテムの判定
			$itemMax = count($staffData[$i]['item']);

			for ($j = 0; $j < $itemMax; $j++) {

//				if ($staffData[$i]['item'][$j]['size'] != '' || $staffData[$i]['item'][$j]['num'] != '') {
////					// サイズの入力なし
////					if ($staffData[$i]['item'][$j]['size'] == '') {
////						$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
////						$errMsg[$errMsgCnt]['message'] = "アイテムNo：" . $staffData[$i]['item'][$j]['itemNo'] . "のサイズが入力されていません。";
////						$errMsgCnt++;
////					}
					// サイズ・数量の入力あり
//					if ($staffData[$i]['item'][$j]['size'] != '' && $staffData[$i]['item'][$j]['num'] != '') {
					if ($staffData[$i]['item'][$j]['size'] != '') {

						if ($appliReason == APPLI_REASON_ORDER_EXCEL) {	// 一括発注
							// アイテムパターンの存在チェック
							if (!isset($itemSelect[$staffData[$i]['item'][$j]['itemNo']])) {
								$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
								$errMsg[$errMsgCnt]['message'] = "アイテムNo：" . $staffData[$i]['item'][$j]['itemNo'] . "は許可されたパターンに一致しません。";
								$errMsgCnt++;

//							} else {
//
//	//							$result = checkData((string)$staffData[$i]['item'][$j]['num'], 'Digit', true, (string)$itemSelect[$staffData[$i]['item'][$j]['itemNo']]['NumBase'], (string)$itemSelect[$staffData[$i]['item'][$j]['itemNo']]['NumBase']);
//								$result = checkData((string)$staffData[$i]['item'][$j]['num'], 'Digit', true);
//								switch ($result) {
//									case 'mode':
//										$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
//										$errMsg[$errMsgCnt]['message'] = "アイテムNo：" . $staffData[$i]['item'][$j]['itemNo'] . "　数量は半角数値で入力してください。";
//										$errMsgCnt++;
//										break;
//
//									default:
//										break;
//								}
							}

//							// アイテム毎のエクセル発注数量と貸与パターン基準数量を比較
//							if ($staffData[$i]['item'][$j]['num'] != $itemSelect[$staffData[$i]['item'][$j]['itemNo']]['NumBase']) {
//								$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
//								$errMsg[$errMsgCnt]['message'] = "アイテムNo：" . $staffData[$i]['item'][$j]['itemNo'] . "は許可された数量パターンではございません。パターン数量:" . $itemSelect[$staffData[$i]['item'][$j]['itemNo']]['NumBase'] . "　発注数量:" . $staffData[$i]['item'][$j]['num'];
//								$errMsgCnt++;
//							}
						}
	////////						// グループのチェック
	////////						if (isset($itemSelect[$staffData[$i]['item'][$j]['itemNo']]['BandleId']) && $itemSelect[$staffData[$i]['item'][$j]['itemNo']]['BandleId'] != 0) {
	////////							// 初期化(最大値、最小値は最初の値を利用)
	////////							if (!isset($bundleIdAry[$itemSelect[$staffData[$i]['item'][$j]['itemNo']]['BandleId']])) {
	////////								$bundleIdAry[$itemSelect[$staffData[$i]['item'][$j]['itemNo']]['BandleId']]['itemNumber'] = 0;
	////////								$bundleIdAry[$itemSelect[$staffData[$i]['item'][$j]['itemNo']]['BandleId']]['itemNumberMax'] = $itemSelect[$staffData[$i]['item'][$j]['itemNo']]['NumMax'];	// グループ最大値
	////////								$bundleIdAry[$itemSelect[$staffData[$i]['item'][$j]['itemNo']]['BandleId']]['itemNumberMin'] = $itemSelect[$staffData[$i]['item'][$j]['itemNo']]['NumMin'];	// グループ最小値
	////////							}
	////////							// 同グループIDのアイテム個数を集計
	////////							if (!isset($staffData[$i]['item'][$j]['num']) || $staffData[$i]['item'][$j]['num'] == '') {
	////////								$staffData[$i]['item'][$j]['num'] = 0;
	////////							}
	////////							$bundleIdAry[$itemSelect[$staffData[$i]['item'][$j]['itemNo']]['BandleId']]['itemNumber'] = (int)$bundleIdAry[$itemSelect[$staffData[$i]['item'][$j]['itemNo']]['BandleId']]['itemNumber'] + (int)trim($staffData[$i]['item'][$j]['num']);
	////////						}
	//					} else {											// 共用品
	//						if (!ctype_digit((string)$staffData[$i]['item'][$j]['num'])) {
	//							$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
	//							$errMsg[$errMsgCnt]['message'] = "アイテムNo：" . $staffData[$i]['item'][$j]['itemNo'] . "の数量が数値ではありません。";
	//							$errMsgCnt++;
	//						}

					}
					 else {

						if (isset($itemSelect[$staffData[$i]['item'][$j]['itemNo']]['NumBase']) && $itemSelect[$staffData[$i]['item'][$j]['itemNo']]['NumBase'] > 0) {

							if ($staffData[$i]['item'][$j]['size'] == '') {
								$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
								$errMsg[$errMsgCnt]['message'] = "アイテムNo：" . $staffData[$i]['item'][$j]['itemNo'] . "のサイズが入力されていません。";
								$errMsgCnt++;
							}

////							$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
////							$errMsg[$errMsgCnt]['message'] = "アイテムNo：" . $staffData[$i]['item'][$j]['itemNo'] . "は許可されたパターンに一致しません。";
////							$errMsgCnt++;
						}
					}
//				}

//				} else {
//
//					if (isset($itemSelect[$staffData[$i]['item'][$j]['itemNo']]['NumBase']) && $itemSelect[$staffData[$i]['item'][$j]['itemNo']]['NumBase'] > 0) {
//						$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
//						$errMsg[$errMsgCnt]['message'] = "アイテムNo：" . $staffData[$i]['item'][$j]['itemNo'] . "は許可されたパターンに一致しません。";
//						$errMsgCnt++;
//					}
//				}
			}
	//		foreach ($bundleIdAry as $key => $bundleData) {
	//			if (intval($bundleData['itemNumber']) < intval($bundleData['itemNumberMin'])
	//			 || intval($bundleData['itemNumber']) > intval($bundleData['itemNumberMax'])) {
	//				switch ($key) {
	//				//	case 1:		// 名古屋男性用：帽子(メッシュ)＆帽子(サンバイザー)の合計は１枚
	//				//		$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
	//				//		$errMsg[$errMsgCnt]['message'] = "帽子（メッシュ）と帽子（サンバイザー）はどちらか１枚を選択して下さい。";
	//				//		$errMsgCnt++;
	//					case 2:		// 横浜女性用：合服パンツ＆冬服パンツ＆スカートの合計は４枚
	//						$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
	//						$errMsg[$errMsgCnt]['message'] = "合服パンツ・冬服パンツ・スカートは合計で４枚となるよう選択して下さい。";
	//						$errMsgCnt++;
	//						break;
	//					case 3:		// 名古屋女性用：半袖ブラウス＆長袖ブラウス(夏)の合計は４枚
	//						$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
	//						$errMsg[$errMsgCnt]['message'] = "半袖ブラウス・長袖ブラウス（夏）は合計で４枚以下となるよう選択して下さい。";
	//						$errMsgCnt++;
	//						break;
	//				}
	//			}
	//		}

			///////////////////////////////////
			// 特寸入力内容のチェック
			///////////////////////////////////
			if ($staffData[$i]['tokFlag'] == 1) {
				$isSizeInputFlag = false;
				$isBikouInputFlag = false;

				// 身長の判定
				$result = checkData(trim($staffData[$i]['tok']['height']), 'Number', true, 8);
				switch ($result) {
					case 'empty':
						break;
					case 'mode':
					case 'max':
						$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
						$errMsg[$errMsgCnt]['message'] = "身長は小数点を含めて半角数値の8桁以内で入力してください。";
						$errMsgCnt++;
						break;
					default:
						$isSizeInputFlag = true;
						break;
				}

				// 体重の判定
				$result = checkData(trim($staffData[$i]['tok']['weight']), 'Number', true, 8);
				switch ($result) {
					case 'empty':
						break;
					case 'mode':
					case 'max':
						$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
						$errMsg[$errMsgCnt]['message'] = "体重は小数点を含めて半角数値の8桁以内で入力してください。";
						$errMsgCnt++;
						break;
					default:
						$isSizeInputFlag = true;
						break;
				}

				// バストの判定
				$result = checkData(trim($staffData[$i]['tok']['bust']), 'Number', true, 8);
				switch ($result) {
					case 'empty':
						break;
					case 'mode':
					case 'max':
						$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
						$errMsg[$errMsgCnt]['message'] = "バストは小数点を含めて半角数値の8桁以内で入力してください。";
						$errMsgCnt++;
						break;
					default:
						$isSizeInputFlag = true;
						break;
				}

				// ウエストの判定
				$result = checkData(trim($staffData[$i]['tok']['waist']), 'Number', true, 8);
				switch ($result) {
					case 'empty':
						break;
					case 'mode':
					case 'max':
						$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
						$errMsg[$errMsgCnt]['message'] = "ウエストは小数点を含めて半角数値の8桁以内で入力してください。";
						$errMsgCnt++;
						break;
					default:
						$isSizeInputFlag = true;
						break;
				}

				// ヒップの判定
				$result = checkData(trim($staffData[$i]['tok']['hips']), 'Number', true, 8);
				switch ($result) {
					case 'empty':
						break;
					case 'mode':
					case 'max':
						$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
						$errMsg[$errMsgCnt]['message'] = "ヒップは小数点を含めて半角数値の8桁以内で入力してください。";
						$errMsgCnt++;
						break;
					default:
						$isSizeInputFlag = true;
						break;
				}

				// 肩幅の判定
				$result = checkData(trim($staffData[$i]['tok']['shoulder']), 'Number', true, 8);
				switch ($result) {
					case 'empty':
						break;
					case 'mode':
					case 'max':
						$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
						$errMsg[$errMsgCnt]['message'] = "肩幅は小数点を含めて半角数値の8桁以内で入力してください。";
						$errMsgCnt++;
						break;
					default:
						$isSizeInputFlag = true;
						break;
				}

				// 袖丈の判定
				$result = checkData(trim($staffData[$i]['tok']['sleeve']), 'Number', true, 8);
				switch ($result) {
					case 'empty':
						break;
					case 'mode':
					case 'max':
						$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
						$errMsg[$errMsgCnt]['message'] = "袖丈は小数点を含めて半角数値の8桁以内で入力してください。";
						$errMsgCnt++;
						break;
					default:
						$isSizeInputFlag = true;
						break;
				}
				
				// スカート丈の判定
				$result = checkData(trim($staffData[$i]['tok']['length']), 'Number', true, 8);
				switch ($result) {
					case 'empty':
						break;
					case 'mode':
					case 'max':
						$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
						$errMsg[$errMsgCnt]['message'] = "首周りは小数点を含めて半角数値の8桁以内で入力してください。";
						$errMsgCnt++;
						break;
					default:
						$isSizeInputFlag = true;
						break;
				}

			    // 着丈の判定
			    $result = checkData(trim($staffData[$i]['tok']['kitake']), 'Number', true, 8);
				switch ($result) {
					case 'empty':
						break;
					case 'mode':
					case 'max':
						$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
						$errMsg[$errMsgCnt]['message'] = "着丈は小数点を含めて半角数値の8桁以内で入力してください。";
						$errMsgCnt++;
						break;
					default:
						$isSizeInputFlag = true;
						break;
				}

			    // 裄丈の判定
			    $result = checkData(trim($staffData[$i]['tok']['yukitake']), 'Number', true, 8);
				switch ($result) {
					case 'empty':
						break;
					case 'mode':
					case 'max':
						$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
						$errMsg[$errMsgCnt]['message'] = "裄丈は小数点を含めて半角数値の8桁以内で入力してください。";
						$errMsgCnt++;
						break;
					default:
						$isSizeInputFlag = true;
						break;
				}

			    // 股下の判定
			    $result = checkData(trim($staffData[$i]['tok']['inseam']), 'Number', true, 8);
				switch ($result) {
					case 'empty':
						break;
					case 'mode':
					case 'max':
						$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
						$errMsg[$errMsgCnt]['message'] = "股下は小数点を含めて半角数値の8桁以内で入力してください。";
						$errMsgCnt++;
						break;
					default:
						$isSizeInputFlag = true;
						break;
				}

				// 特寸備考の判定
				$result = checkData(trim($staffData[$i]['tok']['bikou']), 'Text', true, 240);
				switch ($result) {
					case 'empty':
						break;
					case 'max':
						$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
						$errMsg[$errMsgCnt]['message'] = "特寸備考は全角120文字以内で入力してください。";
						$errMsgCnt++;
						break;
					default:
						$isBikouInputFlag = true;
						break;
				}

				// ヌード寸法もしくは備考欄のどちらかに入力があったかの判定
				if (!$isSizeInputFlag && !$isBikouInputFlag) {
					$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
					$errMsg[$errMsgCnt]['message'] = "ヌード寸法または特寸備考のどちらかを必ず入力してください。";
					$errMsgCnt++;
				}
			}
		}
	}

	return $errMsg;
}


//////////////////////////////////////////////////////////
// アイテムNoチェック
//////////////////////////////////////////////////////////
function checkItemData($dbConnect, $appliReason, $itemdata){

	$errMsg = array();
	$errMsgCnt = 0;

	$itemdataMax = count($itemdata);
	for ($i = 0; $i < $itemdataMax; $i++) {
		if (trim($itemdata[$i]) != '') {
			$sql = "SELECT ItemID FROM M_Item WHERE ItemNo = '" . $itemdata[$i] . "' AND Del = " . DELETE_OFF;
//var_dump($sql);
			$result = db_Read($dbConnect, $sql);
			// 検索結果が0件の場合
			if (count($result) <= 0) {
				$errMsg[$errMsgCnt]['lineNo'] = 1;
				$errMsg[$errMsgCnt]['message'] = "アイテムNo：" . $itemdata[$i] . "はアイテムマスタに存在しません。";
				$errMsgCnt++;
			}
		}
	}
	return $errMsg;
}

//////////////////////////////////////////////////////////
// 職員マスタ存在チェック
//////////////////////////////////////////////////////////
function getStaff($dbConnect, $queueData, $staffCode='') {

	$result = array();

	$sql  = " SELECT";
	$sql .= 	" StaffSeqID";
	$sql .= " FROM";
	$sql .= 	" M_Staff";
	$sql .= " WHERE";
	$sql .= 	" Del='0'";
	$sql .= " AND";
	$sql .= 	" StaffCode='" . db_Escape($staffCode) . "'";

    $result = db_Read($dbConnect, $sql);

	//if (count($result) <= 0) {
	if (count($result) > 0) {
//var_dump($sql);die;

		return false;
	}
	return true;
}

//////////////////////////////////////////////////////////
// アイテムNoチェック
//////////////////////////////////////////////////////////
function getItemSelect($dbConnect, $queueData, $staffPattern) {

	$result = array();

	$sql = " SELECT";
	$sql .= 	" ItemNo,";
	$sql .= 	" PatternID,";
	$sql .= 	" PatternName,";
	$sql .= 	" ItemSelectNum";
	$sql .= " FROM";
	$sql .= 	" M_ItemSelect";
	$sql .= " WHERE";
	$sql .= 	" Del=" . DELETE_OFF;
	$sql .= " AND";
	$sql .= 	" PatternName = '" . db_Escape($staffPattern) . "'";
//var_dump($sql);die;
    $result = db_Read($dbConnect, $sql);

	if (count($result) <= 0) {
		return false;
	}

	$resCnt = count($result);
	for ($i = 0; $i < $resCnt; $i++) {
		// 対象貸与パターンのアイテム毎の基準数量取得
		$itemSelect[$result[$i]['ItemNo']]['NumBase'] = $result[$i]['ItemSelectNum'];
//		$itemSelect[$result[$i]['ItemNo']]['BandleId'] = $result[$i]['BundleID'];
	}

	return $itemSelect;
//	return true;
}

//////////////////////////////////////////////////////////
// 対象職員の所属先本部・支部に指定した貸与パターンが適切かどうか、確認
//////////////////////////////////////////////////////////
function getStaffItemSelect($dbConnect, $queueData, $appliReason, $compCode, $staffPattern) {

//    $result = array();
//
//    $isNext = false;
//
//	$sql .= " SELECT";
//	$sql .= 	" NextCompID";
//	$sql .= " FROM";
//	$sql .= 	" M_Staff";
//	$sql .= " WHERE";
//	$sql .= 	" StaffCode='" . db_Escape($staffCode) . "'";
//	$sql .= " AND";
//	$sql .= 	" Del='0'";
//
//    $result = db_Read($dbConnect, $sql);
//
//
//	if (isset($result[0]['NextCompID']) && $result[0]['NextCompID'] != '') {

		// 初期化
		$resultNext = array();

		$sql = " SELECT";
		$sql .= 	" 	mp.PatternID,";
		$sql .= 	" 	mp.PatternName,";
		$sql .= 	" 	mc.HonbuCd,";
		$sql .= 	" 	mc.ShibuCd,";
		$sql .= 	" 	mp.CompKind,";
		$sql .= 	" 	mc.CompID";
//		$sql .= 	" 	ms.StaffCode";
		$sql .= " FROM";
		$sql .= 	" M_Comp mc";
		$sql .= " INNER JOIN";
		$sql .= 	" M_Pattern mp";
		$sql .= " ON";
		$sql .= 	" mp.HonbuCd = mc.HonbuCd";
		$sql .= " AND";
		$sql .= 	" mp.ShibuCd = mc.ShibuCd";
		// 支部本部判定追加 2017/05/29 Y.Furukawa
		$sql .= " AND";
		$sql .= 	" mp.CompKind = mc.CompKind";
		$sql .= " AND";
		$sql .= 	" mp.Del=" . DELETE_OFF;
		$sql .= " WHERE";
		$sql .= 	" mp.PatternName like '" . db_Escape($staffPattern) . "'";
		$sql .= " AND";
		$sql .= 	" mc.Del='0'";
		$sql .= " AND";
		//$sql .= 	" mc.CompCd = " . db_Escape($result[0]['NextCompID']);
		$sql .= 	" mc.CompCd = '" . db_Escape($compCode) . "'";

//var_dump($sql);die;
		$resultNext = db_Read($dbConnect, $sql);

		if (count($resultNext) <= 0) {
//			 $isNext = true;
			return false;
		}
//	} else {
//
//		$result = array();
//
//		$sql .= " SELECT";
//		$sql .= 	" 	mp.PatternID,";
//		$sql .= 	" 	mp.PatternName,";
//		$sql .= 	" 	mc.HonbuCd,";
//		$sql .= 	" 	mc.ShibuCd,";
//		$sql .= 	" 	mp.CompKind,";
//		$sql .= 	" 	mc.CompID,";
//		$sql .= 	" 	ms.StaffCode";
//		$sql .= " FROM";
//		$sql .= 	" M_Staff ms";
//
//		$sql .= " INNER JOIN";
//		$sql .= 	" M_Comp mc";
//		$sql .= " ON";
//		$sql .= 	" ms.CompID = mc.CompID";
//		$sql .= " AND";
//		$sql .= 	" ms.Del='0'";
//		$sql .= " AND";
//		$sql .= 	" mc.Del='0'";
//		$sql .= " AND";
//		$sql .= 	" StaffCode='" . db_Escape($staffCode) . "'";
//
//		$sql .= " INNER JOIN";
//		$sql .= 	" M_Pattern mp";
//		$sql .= " ON";
//		$sql .= 	" mp.HonbuCd = mc.HonbuCd";
//		$sql .= " AND";
//		$sql .= 	" mp.ShibuCd = mc.ShibuCd";
//		// 支部本部判定追加 2017/05/29 Y.Furukawa
//		$sql .= " AND";
//		$sql .= 	" mp.CompKind = mc.CompKind";
//		$sql .= " AND";
//		$sql .= 	" mp.Del=" . DELETE_OFF;
//		$sql .= " WHERE";
//		$sql .= 	" mp.PatternName like '" . db_Escape($staffPattern) . "'";
//
//	    $result = db_Read($dbConnect, $sql);
//
//		if (count($result) <= 0) {
////			if ($isNext == true) {
////				return true;
////			}
//			return false;
//		}
//	}
////	$resCnt = count($result);
////	for ($i = 0; $i < $resCnt; $i++) {
////		$itemSelect[$result[$i]['ItemNo']]['NumMin'] = $result[$i]['ItemSelectNumMin'];
////		$itemSelect[$result[$i]['ItemNo']]['NumMax'] = $result[$i]['ItemSelectNumMax'];
////		$itemSelect[$result[$i]['ItemNo']]['BandleId'] = $result[$i]['BundleID'];
////	}
////
////	return $itemSelect;
	return true;
}

//////////////////////////////////////////////////////////
// パターンＩＤ抽出
//////////////////////////////////////////////////////////
function getPatternID($dbConnect, $staffPattern) {

	$result = array();

	$sql  = " SELECT";
	$sql .= 	" PatternID";
	$sql .= " FROM";
	$sql .= 	" M_ItemSelect";
	$sql .= " WHERE";
	$sql .= 	" Del=" . DELETE_OFF;
	$sql .= " AND";
	$sql .= 	" PatternName = '" . db_Escape($staffPattern) . "'";
	$sql .= " GROUP BY";
	$sql .= 	" PatternID";

    $result = db_Read($dbConnect, $sql);

	if (count($result) <= 0) {
		return false;
	}
	return $result[0]['PatternID'];
}

/* ユーザーの存在有無ををチェックする。
 * 引数  ：$dbConnect => コネクション
 * 引数  ：$id        => UserIDかloginCd
 * ユーザーがいる場合データ いない場合FALSE
 */ 
function _getUser($dbConnect, $staffCode){

	$sql  = " SELECT  ";
    $sql .= 	"  ms.StaffSeqID as StaffID";
	$sql .= 	" ,ms.CompCd";
	$sql .= 	" ,ms.StaffCode";
	$sql .= 	" ,ms.PersonName";
	$sql .= 	" ,mu.UserID ";
	$sql .= 	" ,mu.Name ";
	$sql .= 	" ,mc.CompID ";
	$sql .= 	" ,mc.CompName ";
	$sql .= 	" ,mc.Zip ";			// 郵便番号
	$sql .= 	" ,mc.Adrr ";           // 住所
	$sql .= 	" ,mc.Tel ";            // 電話番号
	$sql .= 	" ,mc.ShipName ";		// 出荷先
	$sql .= 	" ,mc.TantoName ";		// ご担当者名
	
	$sql .= " FROM ";
	$sql .= 	"  M_Staff as ms ";
	$sql .= " LEFT JOIN";
	$sql .= 	" M_Comp as mc";
	$sql .= " ON";
	$sql .= 	" ms.CompCd = mc.CompCd ";
	$sql .= " AND";
	$sql .= 	" mc.Del = ".DELETE_OFF;
	$sql .= " LEFT JOIN";
	$sql .= 	" M_User as mu";
	$sql .= " ON";
	$sql .= 	" mu.CompID = mc.CompID ";
	$sql .= " AND";
	$sql .= 	" mu.Del = ".DELETE_OFF;

	$sql .= " WHERE ";
	$sql .= 	" ms.StaffCode = '".db_Escape(trim($staffCode))."'";
	$sql .= " AND";
	$sql .= 	" ms.Del = ".DELETE_OFF;

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合 エラー
	if (count($result) <= 0) {
	 	return false;
	}
	return $result[0];
}

/*
 * 申請発注重複判定モジュール（同じ職員コードですでに発注していないか判定）
 * 引数  ：$dbConnect  => コネクションハンドラ
 *       ：$staffID  => 職員ID
 *       ：$returnUrl  => 戻り先URL
 *       ：$hiddenHtml => 遷移時に送信したいPOST値(array)
 * 戻り値：なし
 */
function checkDuplicateStaffID($dbConnect, $staffID) {

	// 指定職員IDが貸与中か確認する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" COUNT(*) as count_order";
	$sql .= " FROM";
	$sql .= 	" T_Staff_Details tsd";
	$sql .= " INNER JOIN";
	$sql .= 	" T_Staff tsf";
	$sql .= " ON";
	$sql .= 	" tsd.StaffID = tsf.StaffID";
	$sql .= " AND";
	$sql .= 	" tsf.Del = " . DELETE_OFF;

	$sql .= " INNER JOIN";
	$sql .= 	" T_Order_Details tod";
	$sql .= " ON";
	$sql .= 	" tsd.OrderDetID = tod.OrderDetID";
	$sql .= " AND";
	$sql .= 	" tod.Del = " . DELETE_OFF;

	$sql .= " INNER JOIN";
	$sql .= 	" T_Order tor";
	$sql .= " ON";
	$sql .= 	" tod.OrderID = tor.OrderID";
	$sql .= " AND";
	$sql .= 	" tor.Del = " . DELETE_OFF;
	$sql .= " WHERE";
	$sql .= 	" tsd.StaffID = '" . db_Escape($staffID) . "'";
	$sql .= " AND";
	$sql .= 	" tsd.ReturnDetID IS NULL";
	$sql .= " AND";
	$sql .= 	" tsd.Del = " . DELETE_OFF;
//var_dump($sql);die;
	$result = db_Read($dbConnect, $sql);

	// 該当の情報がすでに存在する場合
	if (isset($result[0]['count_order']) && $result[0]['count_order'] > 0) {
		return false;
	}
	return true;

}

/*
 * 申請発注重複判定モジュール（同じ職員コードですでに発注していないか判定）
 * 引数  ：$dbConnect  => コネクションハンドラ
 *       ：$staffID  => 職員ID
 *       ：$returnUrl  => 戻り先URL
 *       ：$hiddenHtml => 遷移時に送信したいPOST値(array)
 * 戻り値：なし
 */
function checkDuplicateStaffCode($dbConnect, $staffCode) {


	// 所属先マスタの取得
	$userData = _getUser($dbConnect, $staffCode);

	if (!$userData) {
		return false;
	}

	// 指定職員IDが貸与中か確認する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" COUNT(*) as count_order";
	$sql .= " FROM";
	$sql .= 	" T_Staff_Details tsd";
	$sql .= " INNER JOIN";
	$sql .= 	" T_Staff tsf";
	$sql .= " ON";
	$sql .= 	" tsd.StaffID = tsf.StaffID";
	$sql .= " AND";
	$sql .= 	" tsf.Del = " . DELETE_OFF;

	$sql .= " INNER JOIN";
	$sql .= 	" T_Order_Details tod";
	$sql .= " ON";
	$sql .= 	" tsd.OrderDetID = tod.OrderDetID";
	$sql .= " AND";
	$sql .= 	" tod.Del = " . DELETE_OFF;

	$sql .= " INNER JOIN";
	$sql .= 	" T_Order tor";
	$sql .= " ON";
	$sql .= 	" tod.OrderID = tor.OrderID";
	$sql .= " AND";
	$sql .= 	" tor.Del = " . DELETE_OFF;
	$sql .= " WHERE";
	$sql .= 	" tsd.StaffID = '" . db_Escape($userData['StaffID']) . "'";
	$sql .= " AND";
	$sql .= 	" tsd.ReturnDetID IS NULL";
	$sql .= " AND";
	$sql .= 	" tsd.Del = " . DELETE_OFF;

	$result = db_Read($dbConnect, $sql);

	// 該当の情報がすでに存在する場合
	if (isset($result[0]['count_order']) && $result[0]['count_order'] > 0) {
//var_dump($sql);die;
		return false;
	}
	return true;

}

// ユーザーの存在有無ををチェックする。
// ユーザーがいる場合TRUE いない場合FALSE
function _UserCheck($dbConnect, $StaffCode){

	$sql  = " SELECT ";
	$sql .= 	" StaffSeqID ";
	$sql .= " FROM ";
	$sql .= 	" M_Staff ";
	$sql .= " WHERE ";
	$sql .= 	" StaffCode = '" . db_Escape(trim($StaffCode)) . "'";
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合 エラー
	if (count($result) <= 0) {

		return false;
	}
//var_dump($result);

	return $result;
}

//////////////////////////////////////////////////////////
// エラーレコード挿入
//////////////////////////////////////////////////////////
function errorInsert($dbConnect, $queueData, $errMsg) {

	$errMsgCnt = count($errMsg);
	for ($i = 0; $i < $errMsgCnt; $i++) {
		$sql  = "INSERT INTO T_OrderWork (";
		$sql .= " UserUpID";
	    $sql .= ",UserID";
		$sql .= ",[LineNo]";
		$sql .= ",ErrMsg";
		$sql .= ",Del";
		$sql .= ",RegistDay";
		$sql .= ",RegistUser";
		$sql .= ",UpdDay";
		$sql .= ",UpdUser";
		$sql .= " ) VALUES (";
		$sql .= "  '" . db_Escape($queueData['UserUpID']) . "'"; // キューID
	    $sql .= " ,'" . db_Escape(trim($queueData['UserID']))."'"; 		// UserID
		$sql .= " ,'" . db_Escape($errMsg[$i]['lineNo']) . "'"; 	// Excel行番号
		$sql .= " ,'" . db_Escape(trim($errMsg[$i]['message'])) . "'"; 	// エラーメッセージ
		$sql .= " ,". DELETE_OFF; 									// 削除フラグ
		$sql .= " ,GETDATE()"; 										// 登録日
		$sql .= " ,'". db_Escape(trim($queueData['RegistUser']))."'"; 	// 登録ユーザー
		$sql .= " ,NULL"; 											// 更新日
		$sql .= " ,NULL"; 											// 更新ユーザー
		$sql .= " ) ";

		$isSudccess = db_Execute($dbConnect, $sql);
		// 実行結果が失敗の場合
		if ($isSudccess == false) {
			return false;
		}
	}
	return true;
}
?>

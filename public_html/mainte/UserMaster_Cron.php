<?php
/*
 * ユーザーマスタ一括登録処理
 * UserMaster_Cron.php
 *
 * create 2007/11/30 DF
 *
 *
 */
$rootDir = dirname(dirname(dirname(__FILE__)));

include_once($rootDir . '/include/define.php');					// 定数定義
include_once($rootDir . '/include/dbConnect.php');				// DB接続モジュール
include_once($rootDir . '/include/msSqlControl.php');			// DB操作モジュール
include_once($rootDir . '/include/checkData.php');				// 対象文字列検証モジュール
include_once($rootDir . '/include/checkDuplicateStaff.php');	// 職員重複チェックモジュール

include_once($rootDir . '/include/myExcel/PHPExcel.php');
include_once($rootDir . '/include/myExcel/PHPExcel/Writer/Excel5.php');
include_once($rootDir . '/include/myExcel/PHPExcel/IOFactory.php');

include_once(dirname(__FILE__).'/Upload_common.val.php');

set_time_limit(0);

$upfliePath = dirname(__FILE__).'\\up_file\\';

//$argv[1]=42;

// コマンドラインから引数を受け取る実行ID 
$queueData = getT_UserMstQueue($dbConnect, $argv[1]);
if (!$queueData) {
	exit(0);
}
//var_dump($queueData);die;
//////// コマンドラインから引数を受け取る実行ID
//////// 値があるとき即実行
//////if ($argv[1] == '') {
//////	$list = _getT_UserMstQueue($dbConnect,COMMON_FLAG_ON);
//////}else{
//////	// バッチ処理 未処理のもの全てを対象
//////	$list = _getT_UserMstQueue($dbConnect,COMMON_FLAG_OFF,$argv[1]);
//////}


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
//	errorInsert($dbConnect,$queueData,);
		exit(1);
	}
	//if (!updateT_OrderQueue($dbConnect, $queueData, 2, "エラーが発生しました")) {
    if (!updateT_UserMstQueue($dbConnect,$queueData,UPLOAD_FILE_COMP_FLAG_ERROR,"エラーが発生しました")) {
		exit(1);
	}
//die;
	exit(0);
}

// エクセルファイルをオープン
$xlsObject = $xlsReader->load($upfliePath.$queueData['UserUpSetFile']);

//アクティブなシートを変数に格納
$xlsObject->setActiveSheetIndex(0);
$worksheet = $xlsObject->getActiveSheet();

$startRowNo = 3;	// 発注開始行位置
//$startTokNo = 0;	// 特寸入力開始カラム(初期値)
	
//////////////////////////////////////////////////////////
// エクセル表の内容を読込
//////////////////////////////////////////////////////////
$rowdata = array();

// エクセルファイルのデータ読込
$rowMax = $worksheet->getHighestRow();	// 行の最大値
$colMax = PHPExcel_Cell::columnIndexFromString($worksheet->getHighestColumn());	// 列の最大値

//var_dump($colMax);die;

for ($i = 1; $i <= $rowMax; $i++){
//for ($i = 1; $i <= 4; $i++){
	// １行目の取得(※職員マスタ一括アップロードでは使用しない)
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

// 職員情報読み込み開始位置（列：col）
$startColNo = 1;

// 職員マスタメンテ情報読み込み開始
$staffData = array();
$staffCnt = count($rowdata);

for ($i = 0; $i < $staffCnt; $i++) {

	$staffData[$i]['lineNo']          = $i + $startRowNo;		                                    // Excel行番号
	$staffData[$i]['staffKbn']        = $rowdata[$i][0];		                                    // 区分(1:追加 2:変更 3:削除)
	$staffData[$i]['staffCode']       = $rowdata[$i][1];		                                    // 職員コード
	$staffData[$i]['staffName']       = mb_convert_encoding($rowdata[$i][2], "UTF-8", "auto");		// 氏名
	$staffData[$i]['compCode']        = $rowdata[$i][3];											// 基地コード
	$staffData[$i]['compName']        = mb_convert_encoding($rowdata[$i][4], "UTF-8", "auto");		// 基地名
	if (!strtotime($rowdata[$i][5])) {
		$staffData[$i]['tekiyouDay']  = $rowdata[$i][5];											// 更新指定日
	} else {
		$staffData[$i]['tekiyouDay']  = date('Y/m/d', strtotime($rowdata[$i][5]));					// 更新指定日
	}
//var_dump("lineNo:"    . $staffData[$i]['lineNo'] );
//var_dump("staffKbn:"  . $staffData[$i]['staffKbn'] );
//var_dump("staffCode:" . $staffData[$i]['staffCode'] );
//var_dump("staffName:" . $staffData[$i]['staffName'] );
//var_dump("compCode:"  . $staffData[$i]['compCode'] );
//var_dump("compName:"  . $staffData[$i]['compName'] );
//echo("<br>");
}

//////////////////////////////////////////////////////////
// エクセル表チェック
//////////////////////////////////////////////////////////
$errMsg = checkExcelData($dbConnect, $queueData, $staffData);

if ($errMsg) {
	if (!errorInsert($dbConnect, $queueData, $errMsg)) {
		exit(1);
	}
	if (!updateT_UserMstQueue($dbConnect, $queueData, 2, "エラーが発生しました")) {
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

			// 申請番号を新規生成
			//$requestNo = createRequestNo($dbConnect, $queueData['CompID'], 1);
			//$staffData[$i]['requestNo'] = createRequestNo($dbConnect, $queueData['CompID'], 1);

			// 所属先マスタの取得
			$compData = getM_Comp($dbConnect, $staffData[$i]['compCode']);

			if (!$compData) {

				db_Transaction_Rollback($dbConnect);

				$errMsg = array();
				$errMsg[0]['lineNo'] = $staffData[$i]['lineNo'];
				$errMsg[0]['message'] = "基地・所属先マスタの取得に失敗しました。";

				if (!errorInsert($dbConnect, $queueData, $errMsg)) {
					exit(1);
				}
				if (!updateT_UserMstQueue($dbConnect, $queueData, 2, "エラーが発生しました")) {
					exit(1);
				}
				exit(0);
			}

	//		// T_Staff テーブルの追加
	//		$staffID = insertM_Staff($dbConnect, $queueData, $staffData[$i], $compData);
	//
	//		if (!$staffID) {
	//
	//			db_Transaction_Rollback($dbConnect);
	//
	//			$errMsg = array();
	//			$errMsg[0]['lineNo'] = $staffData[$i]['lineNo'];
	//			$errMsg[0]['message'] = "職員発注管理データの追加に失敗しました。";
	//
	//			if (!errorInsert($dbConnect, $queueData, $errMsg)) {
	//				exit(1);
	//			}
	//			if (!updateT_UserMstQueue($dbConnect, $queueData, 2, "エラーが発生しました")) {
	//				exit(1);
	//			}
	//			exit(0);
	//		}

			// 更新処理
			switch(trim($staffData[$i]['staffKbn'])){
				case 1:		// 追加処理

	//var_dump("kubun1");

					// ユーザーの存在チェックする。
	 				if(!_UserCheck($dbConnect,$staffData[$i]['staffCode'])){
	//var_dump("kubun1-1");
						$isSuccess = insertM_Staff($dbConnect, $queueData, $staffData[$i], $compData);

						if($isSuccess == false){
	//var_dump("kubun1-2");
							db_Transaction_Rollback($dbConnect);

							$errMsg = array();
							$errMsg[0]['lineNo'] = $staffData[$i]['lineNo'];
							$errMsg[0]['message'] = "職員発注管理データの追加に失敗しました。";

							if (!errorInsert($dbConnect, $queueData, $errMsg)) {
	//var_dump("kubun1-3");die;
								exit(1);
							}
							if (!updateT_UserMstQueue($dbConnect, $queueData, 2, "エラーが発生しました")) {
	//var_dump("kubun1-4");die;
								exit(1);
							}
	//var_dump("kubun1-5");die;
							exit(0);
						}

					}else{
	//var_dump("kubun1-20");
						db_Transaction_Rollback($dbConnect);

						$errMsg = array();
						$errMsg[0]['lineNo'] = $staffData[$i]['lineNo'];
						$errMsg[0]['message'] = ERR_MSG_CRON_DBERR_KBN1;
						
						if (!errorInsert($dbConnect, $queueData, $errMsg)) {
	//var_dump("kubun1-30");die;
							exit(1);
						}
						if (!updateT_UserMstQueue($dbConnect, $queueData, 2, "エラーが発生しました")) {
	//var_dump("kubun1-40");die;
							exit(1);
						}
	//var_dump("kubun1-50");die;
						exit(0);
					}

	                break;


				case 2:		// 変更処理
	//var_dump("kubun2");
					// ユーザーの存在チェックする。
	 				if(_UserCheck($dbConnect,$staffData[$i]['staffCode'])){
	//var_dump("kubun2-1");
						// 更新処理
						$isSuccess = updateM_Staff($dbConnect, $queueData, $staffData[$i], $compData);

						if($isSuccess == false){
	//var_dump("kubun2-2");
							db_Transaction_Rollback($dbConnect);

							$errMsg = array();
							$errMsg[0]['lineNo'] = $staffData[$i]['lineNo'];
							$errMsg[0]['message'] = "職員発注管理データの更新に失敗しました。";

							if (!errorInsert($dbConnect, $queueData, $errMsg)) {
	//var_dump("kubun2-3");die;
								exit(1);
							}
							if (!updateT_UserMstQueue($dbConnect, $queueData, 2, "エラーが発生しました")) {
	//var_dump("kubun2-4");die;
								exit(1);
							}
	//var_dump("kubun2-5");die;
							exit(0);
						}

					}else{

						db_Transaction_Rollback($dbConnect);

						$errMsg = array();
						$errMsg[0]['lineNo'] = $staffData[$i]['lineNo'];
						$errMsg[0]['message'] = ERR_MSG_CRON_DBERR_KBN2;
						
						if (!errorInsert($dbConnect, $queueData, $errMsg)) {
							exit(1);
						}
						if (!updateT_UserMstQueue($dbConnect, $queueData, 2, "エラーが発生しました")) {
							exit(1);
						}
						exit(0);

					}
					break;

				case 3:		// 退職処理

	//var_dump("kubun3");
	//die;

					// ユーザーの存在チェックする。
	 				if(_UserCheck($dbConnect,$staffData[$i]['staffCode'])){

						// 貸与中チェック
						$isSuccess = _Check_StaffOrder($dbConnect, $staffData[$i]);

						if($isSuccess == false){

							db_Transaction_Rollback($dbConnect);

							$errMsg = array();
							$errMsg[0]['lineNo'] = $staffData[$i]['lineNo'];
							$errMsg[0]['message'] = "対象の職員は貸与中のアイテムがあるため、削除できません。";

							if (!errorInsert($dbConnect, $queueData, $errMsg)) {
								exit(1);
							}
							if (!updateT_UserMstQueue($dbConnect, $queueData, 2, "エラーが発生しました")) {
								exit(1);
							}
							exit(0);
						}

						// 退職更新
						$isSuccess = _Update_Retireuser($dbConnect, $queueData, $staffData[$i], $compData);

						if($isSuccess == false){

							db_Transaction_Rollback($dbConnect);

							$errMsg = array();
							$errMsg[0]['lineNo'] = $staffData[$i]['lineNo'];
							$errMsg[0]['message'] = "職員発注管理データの更新に失敗しました。";

							if (!errorInsert($dbConnect, $queueData, $errMsg)) {
								exit(1);
							}
							if (!updateT_UserMstQueue($dbConnect, $queueData, 2, "エラーが発生しました")) {
								exit(1);
							}
							exit(0);
						}

					}else{
						db_Transaction_Rollback($dbConnect);

						// 退職するユーザがいない場合エラー
						$errMsg = array();
						$errMsg[0]['lineNo'] = $staffData[$i]['lineNo'];
						$errMsg[0]['message'] = ERR_MSG_CRON_ERR_NOTAISYOKU;
						
						if (!errorInsert($dbConnect, $queueData, $errMsg)) {
							exit(1);
						}
						if (!updateT_UserMstQueue($dbConnect, $queueData, 2, "エラーが発生しました")) {
							exit(1);
						}
						exit(0);
					}
					break;
			}
		}
	}
// エラーがなければ、コミットする
db_Transaction_Commit($dbConnect);

if (!updateT_UserMstQueue($dbConnect, $queueData, 1, "正常終了")) {
	exit(1);
}

// ===========================================================================================



////	//=======================================================//
////	/* ------------------- $dataの配列構造 ------------------*/
////	/* ------------------- $data[i][j] ----------------------*/
////	//=======================================================//
////	// jの番号に入っているデータは以下のようになる
////	//=======================================================//
////
////    // 1 : 区分(1:追加 2:変更 3:退社)
////    // 2 : 職員コード
////    // 3 : 氏名
////    // 4 : 店舗コード
////    // 5 : 人事異動情報 発令日
////    // 6 : 人事異動情報 職員コード
////    // 7 : 人事異動情報 店舗コード
////	//=======================================================//
////	/* -------------------    E N D     ---------------------*/
////	//=======================================================//
////
////	// 抽出されたデータ全ての更新ファイルを実行
////	$upmax = count($list);
////
////	for ($k=0;$k<$upmax;$k++) {
////		
////		// エクセルデータの取得配列に保存
////		$data = $xls->_ExcelRead($upfliePath.$list[$k]['UserUpSetFile'],3);
////
////		$flg = COMMON_FLAG_OFF; // 1件も更新されないときのフラグ
////		$max = count($data);
////		for ($i=0;$i<$max;$i++) {
////		
////			// 従業員番号がある時のみ処理
////			if(trim($data[$i][2]) != ""){
////
////				$rtn = _check_data($dbConnect,$data[$i],$list[$k]);
////				# d($rtn);
////				if($rtn['flg']){
////					// 更新処理
////					switch(trim($data[$i][1])){
////						case 1:		// 追加処理
////							// ユーザーの存在チェックする。
//// 							if(!_UserCheck($dbConnect,$data[$i][2])){
////								// 追加処理
////								$isSuccess = _Insert_user($dbConnect,$data[$i],$list[$k],$rtn);
////								if($isSuccess == false){
////									$rtn['msg'] =ERR_MSG_CRON_ERR_INSERT ;
////									$isSuccess = _err_insert($dbConnect,$data[$i],$list[$k],$rtn,$i);
////								}
////							}else{
////								$rtn['msg'] =ERR_MSG_CRON_DBERR_KBN1 ;
////								$isSuccess = _err_insert($dbConnect,$data[$i],$list[$k],$rtn,$i);
////							}
////                            break;
////						case 2:		// 変更処理
////							// ユーザーの存在チェックする。
//// 							if(_UserCheck($dbConnect,$data[$i][2])){
////
////								// 更新処理
////								$isSuccess = _Update_user($dbConnect,$data[$i],$list[$k],$rtn);
////								if($isSuccess == false){
////									$rtn['msg'] =ERR_MSG_CRON_ERR_UPDATE;
////									$isSuccess = _err_insert($dbConnect,$data[$i],$list[$k],$rtn,$i);
////								}
////
////							}else{
////								$rtn['msg'] =ERR_MSG_CRON_DBERR_KBN2;
////								$isSuccess = _err_insert($dbConnect,$data[$i],$list[$k],$rtn,$i);
////							}
////							break;
////						case 3:		// 退社処理
////						
////							// ユーザーの存在チェックする。
////							if(_UserCheck($dbConnect,$data[$i][2])){
////							
////								// 退職更新
////								$isSuccess = _Update_Retireuser($dbConnect,$data[$i],$list[$k]);
////								if($isSuccess == false){
////									$rtn['msg'] =ERR_MSG_CRON_ERR_UPDATE;
////									$isSuccess = _err_insert($dbConnect,$data[$i],$list[$k],$rtn,$i);
////								}
////								
////							}else{
////								// 退職するユーザがいない場合エラー
////								$rtn['msg'] = ERR_MSG_CRON_ERR_NOTAISYOKU;
////								$isSuccess = _err_insert($dbConnect,$data[$i],$list[$k],$rtn,$i);
////							}
////							break;
////					}
////				}else{
////					//エラー処理を行う
////					$isSuccess = _err_insert($dbConnect,$data[$i],$list[$k],$rtn,$i);
////				}
////				
////				$flg = COMMON_FLAG_ON;
////				
////			}
////			// デバッグ用　無視行処理
////			else{
////			}
////		}
////		// 1件も登録が無い
////		if($flg == COMMON_FLAG_OFF){
////			errorInsert($dbConnect,$list[$k],ERR_MSG_CRON_MISS_EMPTY);
////		}
////		
////		// 最後にエラー明細を検索し、その結果をヘッダーメッセージ部に登録
////		$checkErrdata = _Seach_Err($dbConnect,$list[$k]);
////		
////		if($checkErrdata == 0){
////			// エラー無し
////		 	$isSuccess = updateT_UserMstQueue($dbConnect,$list[$k],UPLOAD_FILE_COMP_FLAG_COMPLETE,ERR_MSG_CRON_OK);
////		}else{
////			// エラー有り
////	 		$isSuccess = updateT_UserMstQueue($dbConnect,$list[$k],UPLOAD_FILE_COMP_FLAG_ERROR,ERR_MSG_CRON_ERR);
////		}
////# db_Transaction_Rollback($dbConnect);
////// コミット
////# db_Transaction_Commit($dbConnect);
////
////	}
////
////# d($list);
////# d($data);

// ------------------------------

// ------------------------------------------------------------
// 商品の貸与中チェック
// ------------------------------------------------------------
function _Check_StaffOrder($dbConnect, $staffData) {

	$sql  = " SELECT";
	$sql .= 	" COUNT(StaffDetID) AS cnt";
	$sql .= " FROM";
	$sql .= 	" T_Staff_Details AS tsd";

	$sql .= " INNER JOIN T_Staff AS ts ON tsd.StaffID = ts.StaffID ";

	$sql .= " WHERE";
	$sql .= 	" ts.StaffCode = '" . $staffData['staffCode'] . "'";
	$sql .= " AND";
	$sql .= 	" tsd.Del = ".DELETE_OFF;
	$sql .= " AND";
	$sql .= 	" ts.Del = ".DELETE_OFF;
	$sql .= " AND";
	$sql .= 	" tsd.Status IN (";
	$sql .= " " . STATUS_APPLI;					// 申請済(承認待)
	$sql .= "," . STATUS_APPLI_ADMIT;			// 申請済
	$sql .= "," . STATUS_STOCKOUT;				// 在庫切
	$sql .= "," . STATUS_ORDER;					// 受注済
	$sql .= "," . STATUS_SHIP;					// 出荷済
	$sql .= "," . STATUS_DELIVERY;				// 納品済
	$sql .= "," . STATUS_NOT_RETURN_ADMIT;		// 返却申請済
	$sql .= "," . STATUS_NOT_RETURN_ORDER;		// 返却受注済
	$sql .= "," . STATUS_LOSS_ADMIT;			// 紛失申請済
	$sql .= " )";

	$result = db_Read($dbConnect, $sql);

	if($result[0]['cnt'] == 0){
		return true;
	}else{
		return false;
	}
}

// 処理したエクセルにエラーがあったチェック
function _Seach_Err($dbConnect,$list){

	$sql  = " SELECT ";
	$sql .= 	" count(*) as err_cnt";
	$sql .= " FROM ";
	$sql .= 	" T_UserMstWork ";
	$sql .= " WHERE ";
	$sql .= 	" UserUpID = '".db_Escape(trim($list['UserUpID']))."'";
	$sql .= " AND Del = ".DELETE_OFF;

	$result = db_Read($dbConnect, $sql);

	return $result[0]['err_cnt'];

}

/*
 * アップロードファイル登録テーブル更新
 * 引数  ：$dbConnect => コネクション
 * 引数  ：$list      => 更新対象データ
 * 引数  ：$flg       => 更新フラグ 1:更新完了 2:エラー
 * 引数  ：$msg       => 更新メッセージ
 * create 2007/11/05 DF
 *
 */
function updateT_UserMstQueue($dbConnect, $queueData, $flg, $msg){

	$sql  = " UPDATE";
	$sql .= 	" T_UserMstQueue";
	$sql .= " SET ";
	$sql .= 	"  CompFlag          = '".db_Escape(trim($flg))."'";
	$sql .= 	" ,CompDay           = GETDATE()";
	$sql .= 	" ,CompMsg           = '".db_Escape(trim($msg))."'";
	$sql .= 	" ,UpdDay            = GETDATE()";
	$sql .= 	" ,UpdUser           = '". db_Escape(trim($queueData['RegistUser']))."' ";
	$sql .= " WHERE ";
	$sql .= 	" UserUpID = '" . db_Escape(trim($queueData['UserUpID']))."'";

	$isSuccess = db_Execute($dbConnect, $sql);

	// 実行結果が失敗の場合
	if ($isSuccess == false) {
//var_dump($sql);die;
		return false;
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
function insertM_Staff($dbConnect, $queueData, $staffData, $compData){

// M_Staffの情報を取得検索し、登録されていない場合登録する。

	// M_Staff追加
	$sql  = " INSERT INTO M_Staff (";
    $sql .= " CompID";
	$sql .= " ,CompCd";
	$sql .= " ,StaffCode";
	$sql .= " ,PersonName";
//	$sql .= " ,HatureiDay";
//	$sql .= " ,NextNameCd";
//	$sql .= " ,NextCompID";
	$sql .= " ,Del";
	$sql .= " ,RegistDay";
	$sql .= " ,RegistUser";
	$sql .= " ,UpdDay";
	$sql .= " ,UpdUser";
	$sql .= " ) VALUES (";
	
    $sql .= "'".db_Escape(trim($compData['CompID']))."'";
 	$sql .= ",'".db_Escape(trim($compData['CompCd']))."'";
	$sql .= ",'".db_Escape(trim($staffData['staffCode']))."'";
	$sql .= ",'".db_Escape(trim($staffData['staffName']))."'";

// 新規職員追加の場合は不要とする Y.Furukawa 2017/04/25
//	if($staffData['tekiyouDay'] == ""){
//		$sql .= " ,NULL";
//	}else{
//		$sql .= " ,'".db_Escape(trim($staffData['tekiyouDay']))."'";
//	}
//	if($staffData['staffCode'] == ""){
//		$sql .= " ,NULL";
//	}else{
//		$sql .= " ,'".db_Escape(trim($staffData['staffCode']))."'";
//	}
//	if($compData['CompID'] == ""){
//		$sql .= " ,NULL";
//	}else{
//		$sql .= " ,'".db_Escape(trim($compData['CompID']))."'";
//	}

	$sql .= " ,". DELETE_OFF;
	$sql .= " ,GETDATE()";
	$sql .= " ,'".db_Escape(trim($queueData['RegistUser']))."' ";
	$sql .= " ,NULL";
	$sql .= " ,NULL";
	$sql .= " )";

	$isSuccess = db_Execute($dbConnect, $sql);
	
 	// 実行結果が失敗の場合
	if ($isSuccess == false) {
//var_dump($sql);die;
		return false;
	}
	
	return true;
}

/*
 * ユーザー更新処理
 * 引数  ：$dbConnect => コネクション
 * 引数  ：$data      => エクセルデータ
 * 引数  ：$list      => エクセル登録者情報
 * 引数  ：$rtn       => 登録情報
 * 引数  ：$line      => 行数
 * create 2007/11/05 DF
 *
 */
function updateM_Staff($dbConnect, $queueData, $staffData, $compData){

	$isDate = false;

	// 本日日付取得
	$today = date("Y/m/d");


	// エクセル一括アップロードの更新適用日が本日日付より大きい場合は、NEXTに反映する。
	if (strtotime($today) < strtotime($staffData['tekiyouDay'])) {
		$isDate = true;
	}

	$sql  = " UPDATE M_Staff SET  ";

	// エクセル一括アップロードの更新適用日が過去日付の場合はエラーとする。
	if ($isDate == true) {
		//$sql .= "  CompID   = '".db_Escape(trim($compData['CompID']))."'";
 		//$sql .= " ,CompCd   = '".db_Escape(trim($compData['CompCd']))."'";
		$sql .= "  PersonName = '".db_Escape(trim($staffData['staffName']))."'";
		$sql .= " ,HatureiDay = '".db_Escape(trim($staffData['tekiyouDay']))."'";
		$sql .= " ,NextNameCd = '".db_Escape(trim($staffData['staffCode']))."'";
		$sql .= " ,NextCompID = '".db_Escape(trim($compData['CompID']))."'";

	} else {
		$sql .= "  CompID     = '".db_Escape(trim($compData['CompID']))."'";
 		$sql .= " ,CompCd     = '".db_Escape(trim($compData['CompCd']))."'";
 		// 更新時は職員コードも修正する。
		$sql .= " ,StaffCode  = '".db_Escape(trim($staffData['staffCode']))."'";
		$sql .= " ,PersonName = '".db_Escape(trim($staffData['staffName']))."'";
		$sql .= " ,HatureiDay = NULL";
		$sql .= " ,NextNameCd = NULL";
		$sql .= " ,NextCompID = NULL";

	}

	$sql .= " ,UpdDay         = GETDATE()";
	$sql .= " ,UpdUser        = '".db_Escape(trim($queueData['RegistUser']))."' ";

	$sql .= " WHERE ";
	$sql .= 	" Del = ". DELETE_OFF;
	
	$StaffSeqID = _UserCheck($dbConnect,trim($staffData['staffCode']));

	$sql .= " AND";
	$sql .= 	" StaffSeqID = '" . db_Escape(trim($StaffSeqID[0]['StaffSeqID']))."'";

	$isSuccess = db_Execute($dbConnect, $sql);

	// 実行結果が失敗の場合
	if ($isSuccess == false) {
//var_dump("isDate:" . $isDate);die;
		return false;
	}

	// 更新実施日が当日日付以下だった場合、即反映する
	if ($isDate == false) {

		// T_Staff登録・更新
		$isSuccess = _Update_T_Staff($dbConnect, $queueData, $staffData, $compData);

		if (!$isSuccess) {
			return false;

		} else {
        	// 申請済発注情報の発送先を変更
			$isSuccess = _Update_T_Order($dbConnect, $queueData, $staffData, $compData);

            if (!$isSuccess) {
                return false;
            } else {
                // コミット
                return true;

                //// 店舗が変更されていたらメール送信
                //if ($isMoveComp) {
                //    sendMoveInfo($dbConnect,$alluser[$i], $oldStaffInfo);
                //}
            }
		}
	}
	return true;
}

// 人事異動データを反映する
/*
 * ユーザー更新処理
 * 引数  ：$dbConnect => コネクション
 * 引数  ：$data      => ユーザーデータ
 * create 2007/11/05 DF
 *
 */
function _Update_T_Staff($dbConnect, $queueData, $staffData, $compData){

	$StaffSeqID = _UserCheck($dbConnect,trim($staffData['staffCode']));

    // T_Staffに登録があれば更新
    $sql  = " SELECT";
    $sql .=     " COUNT (*) AS count_data";
    $sql .= " FROM";
    $sql .=     " T_Staff";
    $sql .= " WHERE";
    $sql .=     " StaffID = '" . db_Escape(trim($StaffSeqID[0]['StaffSeqID']))."'";
    $sql .= " AND";
    $sql .=     " Del = ".DELETE_OFF;

    $result = db_Read($dbConnect, $sql);

    if ($result[0]['count_data'] != 0) {

        $sql  = " UPDATE T_Staff SET  ";
        $sql .= "  CompID        = '".db_Escape(trim($compData['CompID']))."'";
        $sql .= " ,StaffCode     = '".db_Escape(trim($staffData['staffCode']))."'";
        $sql .= " ,UpdDay        = GETDATE()";
        $sql .= " ,UpdUser       = '".db_Escape(trim($queueData['RegistUser']))."' ";
        $sql .= " WHERE ";
        $sql .=     " StaffID = '" . db_Escape(trim($StaffSeqID[0]['StaffSeqID']))."'";
        $sql .= " AND";
        $sql .=     " Del = ".DELETE_OFF;

        $isSuccess = db_Execute($dbConnect, $sql);

        // 実行結果が失敗の場合
        if ($isSuccess == false) {
//var_dump($sql);die;
            return false;
        }

    }
    return true;
}

// 更新処理
function _Update_T_Order($dbConnect, $queueData, $staffData, $compData){

	$StaffSeqID = _UserCheck($dbConnect,trim($staffData['staffCode']));

    // T_Orderの情報を更新
    $sql  = " UPDATE T_Order SET ";
    $sql .= "  CompID                 = '".db_Escape(trim($compData['CompID']))."'";
    $sql .= " ,StaffCode              = '".db_Escape(trim($staffData['staffCode']))."'";
    $sql .= " ,PersonName             = '".db_Escape(trim($staffData['staffName']))."'";
    $sql .= " ,UpdDay                 = GETDATE()";
    $sql .= " ,UpdUser                = '".db_Escape(trim($queueData['RegistUser']))."' ";
    $sql .= " WHERE ";
    $sql .=     " StaffID             = '" . db_Escape(trim($StaffSeqID[0]['StaffSeqID']))."'";
    $sql .= " AND";
    $sql .=     " Del                 = ".DELETE_OFF;

    $isSuccess = db_Execute($dbConnect, $sql);

    // 実行結果が失敗の場合
    if ($isSuccess == false) {
//var_dump($sql);die;
        return false;
    }

    // まだ倉庫に送信していないデータがあれば更新
    $sql  = " SELECT";
    $sql .=     " COUNT (*) AS count_data";
    $sql .= " FROM";
    $sql .=     " T_Order";
    $sql .= " WHERE";
    $sql .=     " StaffID = '" . db_Escape(trim($StaffSeqID[0]['StaffSeqID']))."'";
    $sql .= " AND";
    $sql .=     " Status IN (".STATUS_APPLI.",".STATUS_APPLI_ADMIT.",".STATUS_NOT_RETURN.",".STATUS_NOT_RETURN_ADMIT.",".STATUS_LOSS.",".STATUS_LOSS_ADMIT.")";
    $sql .= " AND";
    $sql .=     " Del = ".DELETE_OFF;

    $result = db_Read($dbConnect, $sql);

    if ($result[0]['count_data'] != 0) {

        $sql  = " UPDATE T_Order SET  ";
        $sql .= "  AppliCompCd            = '".db_Escape(trim($compData['CompCd']))."'";
        $sql .= " ,AppliCompName          = '".db_Escape(trim($compData['CompName']))."'";
        $sql .= " ,Zip                    = '".db_Escape(trim($compData['Zip']))."'";
        $sql .= " ,Adrr                   = '".db_Escape(trim($compData['Adrr']))."'";
        $sql .= " ,Tel                    = '".db_Escape(trim($compData['Tel']))."'";
        $sql .= " ,ShipName               = '".db_Escape(trim($compData['ShipName']))."'";
        $sql .= " ,TantoName              = '".db_Escape(trim($compData['TantoName']))."'";

        $sql .= " ,UpdDay                 = GETDATE()";
        $sql .= " ,UpdUser                = '".db_Escape(trim($queueData['RegistUser']))."' ";
        $sql .= " WHERE ";
        $sql .=     " StaffID             = '" . db_Escape(trim($StaffSeqID[0]['StaffSeqID']))."'";
        $sql .= " AND";
        $sql .=     " Status IN (".STATUS_APPLI.",".STATUS_APPLI_ADMIT.",".STATUS_NOT_RETURN.",".STATUS_NOT_RETURN_ADMIT.",".STATUS_LOSS.",".STATUS_LOSS_ADMIT.")";
        $sql .= " AND";
        $sql .=     " Del                 = ".DELETE_OFF;

        $isSuccess = db_Execute($dbConnect, $sql);

        // 実行結果が失敗の場合
        if ($isSuccess == false) {
//var_dump($sql);die;
            return false;
        }

    }

    return true;
}


/*
 * ユーザー退職更新処理
 * 引数  ：$dbConnect => コネクション
 * 引数  ：$data      => エクセルデータ
 * 引数  ：$list      => エクセル登録者情報
 * 引数  ：$line      => 行数
 * create 2007/11/05 DF
 *
 */
function _Update_Retireuser($dbConnect, $queueData, $staffData, $compData) {

	// M_user更新
	$sql  = " UPDATE";
	$sql .= 	" M_Staff";
	$sql .= " SET";
	$sql .= 	" Del = '" . DELETE_ON . "'";
	$sql .= 	" ,UpdDay = GETDATE()";
	$sql .= 	" ,UpdUser = '". db_Escape(trim($queueData['RegistUser']))."' ";
	$sql .= " WHERE ";
    $sql .= 	" Del = ".DELETE_OFF;
	// ユーザーID抽出
	$user = _UserCheck($dbConnect, trim($staffData['staffCode']));
	$sql .= " AND";
	$sql .= 	" StaffSeqID = '" . db_Escape(trim($user[0]['StaffSeqID']))."'";

	$isSuccess = db_Execute($dbConnect, $sql);

	// 実行結果が失敗の場合
	if ($isSuccess == false) {
//var_dump($sql);die;
		return false;
	}

	return true;
}

/*
 * 1件も更新されない場合T_UserMstWorkにエラー追加
 * 引数  ：$dbConnect => コネクション
 * 引数  ：$list      => エクセル登録者情報
 * 引数  ：$msg       => エラー情報
 * create 2007/11/05 DF
 *
 */
function errorInsert($dbConnect,$queueData,$errMsg){
//var_dump("msg::::::" . $msg);die;

	$errMsgCnt = count($errMsg);
	for ($i = 0; $i < $errMsgCnt; $i++) {

	    $sql  = "INSERT INTO T_UserMstWork (";
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
	    $sql .= " '".db_Escape(trim($queueData['UserUpID']))."'"; 		// UserUpID
	    $sql .= " ,'".db_Escape(trim($queueData['UserID']))."'"; 		// UserID
	    $sql .= " ,'".db_Escape(trim($errMsg[$i]['lineNo']))."'";	// [LineNo]
	    $sql .= " ,'".db_Escape(trim($errMsg[$i]['message']))."'";	// ErrMsg
	    $sql .= " ," . DELETE_OFF; 									// Del
	    $sql .= " ,GETDATE()"; 										// RegistDay
	    $sql .= " ,'". db_Escape(trim($queueData['RegistUser']))."'"; 	// RegistUser
	    $sql .= " ,NULL"; 											// UpdDay
	    $sql .= " ,NULL"; 											// UpdUser
	    $sql .= " ) ";

	    $isSuccess = db_Execute($dbConnect, $sql);

	    // 実行結果が失敗の場合
	    if ($isSuccess == false) {
//var_dump($sql);die;
	    	return false;
	    }
	}
	return true;
}


// ユーザーID取得
function _maxUserID($dbConnect){

	$sql .= " SELECT";
	$sql .= 	" UserID";
	$sql .= " FROM";
	$sql .= 	" M_User";
	$sql .= " WHERE";
	$sql .= 	" GroupID = " . MATTER_CODE;
	$sql .= " GROUP BY";
	$sql .= 	" UserID";
	$sql .= " ORDER BY";
	$sql .= 	" UserID ASC";

	$result = db_Read($dbConnect, $sql);
	// 検索結果が0件の場合
	if (count($result) <= 0) {
//var_dump($sql);die;
	 	return 1;
	}
	return $result[count($result)-1]['UserID'] + 1 ;

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

// 更新対象データをT_UserMstQueueから抽出
//function _getT_UserMstQueue($dbConnect,$flg,$seq=0){
function getT_UserMstQueue($dbConnect,$seq=0){

	$sql .= " SELECT";
	$sql .= 	"  UserUpID";
	$sql .= 	" ,UserID";
	$sql .= 	" ,UserUpDay";
	$sql .= 	" ,UserUpFile";
	$sql .= 	" ,UserUpSetFile";
	$sql .= 	" ,CompFlag";
	$sql .= 	" ,CompDay";
	$sql .= 	" ,CompMsg";
	$sql .= 	" ,Del";
	$sql .= 	" ,RegistDay";
	$sql .= 	" ,RegistUser";
	$sql .= 	" ,UpdDay";
	$sql .= 	" ,UpdUser";
	$sql .= " FROM";
	$sql .= 	" T_UserMstQueue";
	$sql .= " WHERE";
	
//	if($flg == COMMON_FLAG_ON){
//		$sql .= " CompFlag = ". UPLOAD_FILE_COMP_FLAG_WAIT;
//	}else{
	$sql .= " UserUpID = '".db_Escape(trim($seq))."'";
//	}
	$sql .= " AND";
	$sql .= 	" CompFlag = " . UPLOAD_FILE_COMP_FLAG_WAIT;
	$sql .= " AND";
	$sql .= 	" Del = " . DELETE_OFF;

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
//		$result = array();
//	 	return $result;
//var_dump($sql);die;
		return false;
	}

	$sql  = " UPDATE T_UserMstQueue SET  ";
	$sql .= 	" CompFlag = 99";	// 実行中
	$sql .= " WHERE ";
	$sql .= 	" UserUpID = '".db_Escape(trim($seq))."'";
	$sql .= " AND";
	$sql .= 	" Del = ".DELETE_OFF;

	$isSuccess = db_Execute($dbConnect, $sql);
	// 実行結果が失敗の場合
	if ($isSuccess == false) {
//var_dump($sql);die;;
		return false;
	}

	return $result[0];

//	return $result;
}

//////////////////////////////////////////////////////////
// 基地コード存在チェック
//////////////////////////////////////////////////////////
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
//var_dump($sql);die;
		return false;
	}

	return $result[0];
}

//////////////////////////////////////////////////////////
// データ整合性チェック
//////////////////////////////////////////////////////////
function checkExcelData($dbConnect, $queueData, $staffData, $appliReason='') {

	$errMsg = array();
	$errMsgCnt = 0;
	$isDate = false;

	// 本日日付取得
	$today = date("Y/m/d");

//	if ($appliReason != APPLI_REASON_ORDER_COMMON) {	// 共用品以外
//		// アイテムパターンマスタから発注可能な最小値、最大値を取得する。
//		$itemSelect = getItemSelect($dbConnect, $queueData, $appliReason);
//		if (!$itemSelect) {
//			$errMsg[$errMsgCnt]['lineNo'] = 1;
//			$errMsg[$errMsgCnt]['message'] = "アイテムパターンが取得できませんでした。";
//			return $errMsg;
//		}
//	}


//var_dump("count:" . count($staffData));

	$staffCnt = count($staffData);
//var_dump($staffCnt);die;
	for ($i = 0; $i < $staffCnt; $i++) {

//		if ($appliReason != APPLI_REASON_ORDER_COMMON) {	// 共用品以外

		if ($staffData[$i]['staffCode'] != '') {

		    // 所属先マスタの取得
		    $compData = getM_Comp($dbConnect, $staffData[$i]['compCode']);
		    if (!$compData) {
		    	$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
		    	$errMsg[$errMsgCnt]['message'] = "基地・所属先マスタの取得に失敗しました。";
				$errMsgCnt++;
		    	return $errMsg;
		    }

			// 職員コードの判定
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

	//		// 社員名（名）の判定
	//		$result = checkData($staffData[$i]['name_mei'], 'Text', true, 20);
	//		switch ($result) {
	//			case 'empty':	// 空白
	//				$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
	//				$errMsg[$errMsgCnt]['message'] = "社員名（名）は省略できません。";
	//				$errMsgCnt++;
	//				break;
	//			case 'max':		// 最大値超過ならば
	//				$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
	//				$errMsg[$errMsgCnt]['message'] = "社員名（名）は全角10(半角20文字)文字までで入力して下さい。";
	//				$errMsgCnt++;
	//				break;
	//			default:
	//				break;
	//		}
	//


	//var_dump("lineNo:"    . $staffData[$i]['lineNo'] );
	//var_dump("staffKbn:"  . $staffData[$i]['staffKbn'] );区分(1:追加 2:変更 3:退職 4:削除)
	//var_dump("staffCode:" . $staffData[$i]['staffCode'] );
	//var_dump("staffName:" . $staffData[$i]['staffName'] );
	//var_dump("compCode:"  . $staffData[$i]['compCode'] );
	//var_dump("compName:"  . $staffData[$i]['compName'] );
	//tekiyouDay

			// 新規/更新区分の判定
			$result = checkData(strval($staffData[$i]['staffKbn']), 'Digit', true, 3, 1);
			switch ($result) {
				case 'empty':	// 空白
					$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
					$errMsg[$errMsgCnt]['message'] = "新規/更新区分は省略できません。";
					$errMsgCnt++;
					break;
				case 'mode':	// 数字以外
				case 'max':		// 最大値超過ならば
				case 'min':		// 最小値未満ならば
					$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
					$errMsg[$errMsgCnt]['message'] = "新規/更新区分は1:追加、2:変更、3:削除の何れかしか入力できません。";
					$errMsgCnt++;
					break;
				default:
					break;
			}

			// 採用年月日の判定
			$result = checkData($staffData[$i]['tekiyouDay'], 'Date', true);
			switch ($result) {
				case 'empty':	// 空白
					$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
					$errMsg[$errMsgCnt]['message'] = "更新年月日は省略できません。";
					$errMsgCnt++;
					$isDate = true;
					break;
				case 'mode':	// 存在しない日付

	//var_dump($staffData[$i]['lineNo'] . ":" . $staffData[$i]['tekiyouDay']);
					$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
					$errMsg[$errMsgCnt]['message'] = "更新年月日が正しい日付ではありません。";
					$errMsgCnt++;
					$isDate = true;
					break;
				default:
					break;
			}

			if ($isDate == false) {
				// エクセル一括アップロードの更新適用日が過去日付の場合はエラーとする。
				if (strtotime($today) > strtotime($staffData[$i]['tekiyouDay'])) {
					$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
					$errMsg[$errMsgCnt]['message'] = "更新年月日に過去年月日が指定されています。";
					$errMsgCnt++;
				}
			}

			if ($staffData[$i]['staffKbn'] == '1') {
				// Modified by T.Uno at 2015/11/17
				// StaffCodeの重複エラーチェック
	//			if (!checkDuplicateCorpStaff($dbConnect, $compData['CorpCd'], $compData['CompID'], $staffData[$i]['staffCode'], $staffData[$i]['staffKbn'])) {
				if (!checkDuplicateCorpStaff($dbConnect, $staffData[$i]['staffCode'], $staffData[$i]['staffKbn'])) {
					$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
					$errMsg[$errMsgCnt]['message'] = "職員コード：" . $staffData[$i]['staffCode'] . "は既に使用されています。";
					$errMsgCnt++;
				}
			}
			
			// 職員区分整合性チェック
			if ($staffData[$i]['staffKbn'] == '1') {


			} else {


			}


			switch(trim($staffData[$i]['staffKbn'])){

				case 1:		// 追加処理
					// ユーザーの存在チェックする。
	 				if(_UserCheck($dbConnect,$staffData[$i]['staffCode'])){

						$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
						$errMsg[$errMsgCnt]['message'] = ERR_MSG_CRON_DBERR_KBN1;
						$errMsgCnt++;

					}
					break;

				case 2:		// 変更処理
					// ユーザーの存在チェックする。
	 				if(!_UserCheck($dbConnect,$staffData[$i]['staffCode'])){
						$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
						$errMsg[$errMsgCnt]['message'] = ERR_MSG_CRON_DBERR_KBN2;
						$errMsgCnt++;
					}
					break;

				case 3:		// 退職処理
					// ユーザーの存在チェックする。
	 				if(!_UserCheck($dbConnect,$staffData[$i]['staffCode'])){
						// 退職するユーザがいない場合エラー
						$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
						$errMsg[$errMsgCnt]['message'] = ERR_MSG_CRON_ERR_NOTAISYOKU;
						$errMsgCnt++;
					}
					break;

				default:
					break;

			}


		//	// 新規社員の時、既に指定社員番号が利用されている場合はエラー
		//	if ($staffData[$i]['staffKbn'] == '1') {
		//		if (!checkDuplicateStaffCode($dbConnect, $staffData[$i]['staffCode'])) {
		//			$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
		//			$errMsg[$errMsgCnt]['message'] = "新規区分で指定された社員番号は既に発注済です。";
		//			$errMsgCnt++;
		//			break;
		//		}
		//	}
//		}

//		// メモの判定
//	    $result = checkData($staffData[$i]['memo'], 'Text', false, 128);
//		switch ($result) {
//			case 'max':		// 最大値超過ならば
//				$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
//				$errMsg[$errMsgCnt]['message'] = "メモは全角64(半角128)文字までで入力して下さい。";
//				$errMsgCnt++;
//				break;
//			default:
//				break;
//		}

//		// アイテムの判定
//	    $bundleIdAry = array();
//		$itemMax = count($staffData[$i]['item']);
//		for ($j = 0; $j < $itemMax; $j++) {
//			if ($staffData[$i]['item'][$j]['size'] != '' || $staffData[$i]['item'][$j]['num'] != '') {
//				// サイズの入力なし
//				if ($staffData[$i]['item'][$j]['size'] == '' && $staffData[$i]['item'][$j]['num'] != '') {
//					$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
//					$errMsg[$errMsgCnt]['message'] = "アイテムNo：" . $staffData[$i]['item'][$j]['itemNo'] . "のサイズが入力されていません。";
//					$errMsgCnt++;
//				}
//				// 数量の入力なし
//				if ($staffData[$i]['item'][$j]['size'] != '' && $staffData[$i]['item'][$j]['num'] == '') {
//					$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
//					$errMsg[$errMsgCnt]['message'] = "アイテムNo：" . $staffData[$i]['item'][$j]['itemNo'] . "の数量が入力されていません。";
//					$errMsgCnt++;
//				}
//				// サイズ・数量の入力あり
//				if ($staffData[$i]['item'][$j]['size'] != '' && $staffData[$i]['item'][$j]['num'] != '') {
//
//					if ($appliReason != APPLI_REASON_ORDER_COMMON) {	// 共用品以外
//						// アイテムパターンの存在チェック
//						if (!isset($itemSelect[$staffData[$i]['item'][$j]['itemNo']])) {
//							$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
//							$errMsg[$errMsgCnt]['message'] = "アイテムNo：" . $staffData[$i]['item'][$j]['itemNo'] . "は許可されたパターンに一致しません。";
//							$errMsgCnt++;
//						} else {
//							$result = checkData((string)$staffData[$i]['item'][$j]['num'], 'Digit', true, (string)$itemSelect[$staffData[$i]['item'][$j]['itemNo']]['NumMax'], (string)$itemSelect[$staffData[$i]['item'][$j]['itemNo']]['NumMin']);
//							switch ($result) {
//								case 'mode':
//									$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
//									$errMsg[$errMsgCnt]['message'] = "アイテムNo：" . $staffData[$i]['item'][$j]['itemNo'] . "　数量は半角数値で入力してください。";
//									$errMsgCnt++;
//									break;
//								case 'max':
//									$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
//									$errMsg[$errMsgCnt]['message'] = "アイテムNo：" . $staffData[$i]['item'][$j]['itemNo'] . "　アイテムの発注数が上限値を超えています。";
//									$errMsgCnt++;
//									break;
//								case 'min':
//									$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
//									$errMsg[$errMsgCnt]['message'] = "アイテムNo：" . $staffData[$i]['item'][$j]['itemNo'] . "　アイテムの発注数が下限値を超えています。";
//									$errMsgCnt++;
//									break;
//								default:
//									break;
//							}
//						}
//
//						// グループのチェック
//						if (isset($itemSelect[$staffData[$i]['item'][$j]['itemNo']]['BandleId']) && $itemSelect[$staffData[$i]['item'][$j]['itemNo']]['BandleId'] != 0) {
//							// 初期化(最大値、最小値は最初の値を利用)
//							if (!isset($bundleIdAry[$itemSelect[$staffData[$i]['item'][$j]['itemNo']]['BandleId']])) {
//								$bundleIdAry[$itemSelect[$staffData[$i]['item'][$j]['itemNo']]['BandleId']]['itemNumber'] = 0;
//								$bundleIdAry[$itemSelect[$staffData[$i]['item'][$j]['itemNo']]['BandleId']]['itemNumberMax'] = $itemSelect[$staffData[$i]['item'][$j]['itemNo']]['NumMax'];	// グループ最大値
//								$bundleIdAry[$itemSelect[$staffData[$i]['item'][$j]['itemNo']]['BandleId']]['itemNumberMin'] = $itemSelect[$staffData[$i]['item'][$j]['itemNo']]['NumMin'];	// グループ最小値
//							}
//							// 同グループIDのアイテム個数を集計
//							if (!isset($staffData[$i]['item'][$j]['num']) || $staffData[$i]['item'][$j]['num'] == '') {
//								$staffData[$i]['item'][$j]['num'] = 0;
//							}
//							$bundleIdAry[$itemSelect[$staffData[$i]['item'][$j]['itemNo']]['BandleId']]['itemNumber'] = (int)$bundleIdAry[$itemSelect[$staffData[$i]['item'][$j]['itemNo']]['BandleId']]['itemNumber'] + (int)trim($staffData[$i]['item'][$j]['num']);
//						}
//					} else {											// 共用品
//						if (!ctype_digit((string)$staffData[$i]['item'][$j]['num'])) {
//							$errMsg[$errMsgCnt]['lineNo'] = $staffData[$i]['lineNo'];
//							$errMsg[$errMsgCnt]['message'] = "アイテムNo：" . $staffData[$i]['item'][$j]['itemNo'] . "の数量が数値ではありません。";
//							$errMsgCnt++;
//						}
//					}
//				}
//			}
//		}

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

		}
	}

	return $errMsg;
}

////////////////////////////////////////////////////////////
//// T_Staff テーブルInsert
////////////////////////////////////////////////////////////
//// T_Staffの情報を取得検索し、登録されていない場合登録する。
//function insert_Staff($dbConnect, $queueData, $staffData) {
//
//	// 検索
//	$sql  = "";
//	$sql .= " SELECT";
//	$sql .= 	" tsf.StaffID";
//	$sql .= " FROM";
//	$sql .= 	" T_Staff tsf";
//	$sql .= " INNER JOIN";
//	$sql .= 	" M_Comp mcp";
//	$sql .= " ON";
//	$sql .= 	" tsf.CompID = mcp.CompID";
//	$sql .= " AND";
//	$sql .= 	" mcp.Del = ".DELETE_OFF;
//	$sql .= " WHERE";
//	$sql .= 	" tsf.StaffCode = '" . db_Escape($staffData['staffCode']) . "'";
////	$sql .= " AND";
////	$sql .= 	" mcp.CorpCd = '" . db_Escape($queueData['CorpCd']) . "'";
//	$sql .= " AND";
//	$sql .= 	" tsf.Del = " . DELETE_OFF;
//
//	$result = db_Read($dbConnect, $sql);
//
//	$staffId = '';
//	if (isset($result[0]['StaffID'])) {
//		$staffId = $result[0]['StaffID'];
//	}
//
//	//  職員がまだ登録されていない場合登録
//    if ($staffId == '') {
//
//		// T_Staffに登録する
//		$sql  = "";
//		$sql .= " INSERT INTO";
//		$sql .= 	" T_Staff";
//		$sql .= 		" (";
//		$sql .= 		" CompID,";
//		$sql .= 		" StaffCode,";
//		$sql .= 		" PersonName_Sei,";
//		$sql .= 		" PersonName_Mei,";
//		$sql .= 		" StaffSaiyoDay,";
//		$sql .= 		" WithdrawalFlag,";
//		$sql .= 		" AllReturnFlag,";
//		$sql .= 		" Del,";
//		$sql .= 		" RegistDay,";
//		$sql .= 		" RegistUser";
//		$sql .= 		" )";
//		$sql .= " VALUES";
//		$sql .= 		" (";
//		$sql .= 		" '" . db_Escape($queueData['CompID']) . "',";
//		$sql .= 		" '" . db_Escape($staffData['staffCode']) . "',";
//		$sql .= 		" '" . db_Escape($staffData['name_sei']) . "',";
//		$sql .= 		" '" . db_Escape($staffData['name_mei']) . "',";
//		$sql .= 		" '" . db_Escape($staffData['saiyoDay']) . "',";
//		$sql .= 		COMMON_FLAG_OFF . ",";
//		$sql .= 		COMMON_FLAG_OFF . ",";
//		$sql .= 		" " . DELETE_OFF . ",";
//		$sql .= 		" GETDATE(),";
//		$sql .= 		" '" . db_Escape($queueData['RegistUser']) . "'";
//		$sql .= 		" )";
//
//		$isSuccess = db_Execute($dbConnect, $sql);
//
//		// 実行結果が失敗の場合
//		if ($isSuccess == false) {
//			return false;
//		}
//
//		// 直近のシーケンスIDを取得
//		$sql  = "";
//		$sql .= " SELECT";
//		$sql .= 	" SCOPE_IDENTITY() as scope_identity";
//
//		$result = db_Read($dbConnect, $sql);
//
//		// 実行結果が失敗の場合
//		if ($result == false || !isset($result[0]['scope_identity']) || $result[0]['scope_identity'] == '') {
//			return false;
//		}
//
//		$staffId = $result[0]['scope_identity'];
//	} else {
//
//		// T_Staffを更新する
//		$sql  = "";
//		$sql .= " UPDATE T_Staff";
//		$sql .= " SET";
//		$sql .= 	" CompID = '" . db_Escape($queueData['CompID']) . "',";
//		$sql .= 	" PersonName_Sei = '" . db_Escape($staffData['name_sei']) . "',";
//		$sql .= 	" PersonName_Mei = '" . db_Escape($staffData['name_mei']) . "',";
//		$sql .= 	" StaffSaiyoDay = '" . db_Escape($staffData['saiyoDay']) . "',";
//		$sql .= 	" WithdrawalFlag = '" . COMMON_FLAG_OFF . "',";
//		$sql .= 	" AllReturnFlag = '" . COMMON_FLAG_OFF . "',";
//		$sql .= 	" UpdDay = GETDATE(),";
//		$sql .= 	" UpdUser = '" . db_Escape($queueData['RegistUser']) . "'";
//		$sql .= " WHERE";
//		$sql .= 	" StaffID = '" . db_Escape($staffId) . "'";
//
//		$isSuccess = db_Execute($dbConnect, $sql);
//
//		// 実行結果が失敗の場合
//		if ($isSuccess == false) {
//			return false;
//		}
//
//	}
//
//	return $staffId;
//}



?>
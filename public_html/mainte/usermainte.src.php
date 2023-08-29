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
require_once('../../include/dbConnect.php');		// DB接続モジュール
require_once('../../include/msSqlControl.php');		// DB操作モジュール
require_once('../../include/checkLogin.php');		// ログイン判定モジュール
require_once('../../include/castHtmlEntity.php');	// HTMLエンティティモジュール
require_once('../../include/redirectPost.php');		// リダイレクトポストモジュール
require_once('../../include/setPaging.php');		// ページング情報セッティングモジュール
require_once('../../include/createMoveMail.php');   // 店舗移動通知メール作成モジュール
require_once('../../include/sendTextMail.php');         // テキストメール送信モジュール
require_once('../../include/commonFunc.php');       // 共通関数モジュール

//admin以外はTOPへ遷移
//08/11/20 uesugi
//if (!$isLevelAdmin){
//    redirectTop();
//}
// 初期設定
$isMenuAdmin = true;	// 管理機能のメニューをアクティブに

// 変数の初期化 ここから ******************************************************
$staffCode          = '';		// 社員番号
$personName         = '';		// 氏名
$hatureibi          = '';		// 発令日

$selectCompId       = '';		// 施設ID
$selectCompCd       = '';		// 施設コード
$selectCompName     = '';		// 施設名
$selectNextCompId   = '';		// 異動先施設ID
$selectNextCompCd   = '';		// 異動先施設コード
$selectNextCompName = '';		// 異動先施設名

$searchCompCd       = "";
$searchCompName     = "";
$searchCompId       = "";
$searchStaffCode    = "";
$searchPersonName   = "";
$isSelectedAdmin    = "";
$StaffSeqID         = "";
$nowPage            = "";
$motoStaffCode      = "";
$genderMensFlag     = ture;

$next_taiyoNum = NEXT_TAIYO_NUM;
$isUpdateFlag   = false;	// 更新フラグ
// 変数の初期化 ここまで ******************************************************

// POST値をHTMLエンティティ
$post = castHtmlEntity($_POST); 

// 職員コード
if (isSetValue($post['staffCode'])) {
	$staffCode = $post['staffCode'];
}
// 氏名
if (isSetValue($post['personName'])) {
	$personName = $post['personName'];
}
// 更新実施日
if (isSetValue($post['hatureibi'])) {
	$hatureibi = $post['hatureibi'];
}
// 施設ID
if (isSetValue($post['selectCompId'])) {
	$selectCompId = $post['selectCompId'];
}
// 施設コード
if (isSetValue($post['selectCompCd'])) {
	$selectCompCd = $post['selectCompCd'];
}
// 施設名
if (isSetValue($post['selectCompName'])) {
	$selectCompName = $post['selectCompName'];
}
// 異動先施設ID
if (isSetValue($post['selectNextCompId'])) {
	$selectNextCompId = $post['selectNextCompId'];
}
// 異動先施設コード
if (isSetValue($post['selectNextCompCd'])) {
	$selectNextCompCd = $post['selectNextCompCd'];
}
// 異動先施設名
if (isSetValue($post['selectNextCompName'])) {
	$selectNextCompName = $post['selectNextCompName'];
}

switch(trim($post['Mode'])){

	case 'ins':
		if ($isLevelNormal == true) {
			if (!isSetValue($post['selectCompId'])) {
				$selectCompId   = $_SESSION['COMPID'];
				$selectCompCd   = $_SESSION['COMPCD'];
				$selectCompName = $_SESSION['COMPNAME'];
			}
		}
		$ope = "insert";
		break;

	case 'upd':
		$StaffSeqID = trim($post['StaffSeqID']);
		// ユーザー取得
		$userData = getUserMaster($dbConnect, trim($StaffSeqID));

		$staffCode          = trim($userData['StaffCode']);			// 職員コード
		$personName         = trim($userData['PersonName']);		// 氏名
		$hatureibi          = trim($userData['HatureiDay']);		// 発令日

		$selectCompId       = trim($userData['CompID']);			// 施設ID
		$selectCompCd       = trim($userData['CompCd']);			// 施設コード
		$selectCompName     = trim($userData['CompName']);			// 施設名

		$selectNextCompId   = trim($userData['NextCompID']);		// 異動先施設ID
		$selectNextCompCd   = trim($userData['NextCompCd']);		// 異動先施設コード
		$selectNextCompName = trim($userData['NextCompName']);		// 異動先施設名

		$isUpdateFlag   = true;

		$ope = "update";

		break;

	case 'insert':
		// エラーチェック
		_check_Data($dbConnect,$post);

		// トランザクション開始
		db_Transaction_Begin($dbConnect);
		$isSuccess =_Insert_M_Staff($dbConnect,$post);
		if($isSuccess == false){
			db_Transaction_Rollback($dbConnect);
			$post['errorName'] = 'userMainte';
			$post['menuName']  = 'isMenuAdmin';
			$post['returnUrl'] = 'mainte/usermainte_top.php';
			$post['errorId'][] = '101';
			$errorUrl             = HOME_URL . 'error.php';
			redirectPost($errorUrl, $post);
		}
		// コミット
		db_Transaction_Commit($dbConnect);

		$errorUrl             = '/mainte/usermainte_top.php';
		redirectPost($errorUrl, "");

		break;

	case 'update':
		if ($post['chkDelete'] == '1') {
			// 貸与中チェック
			$isSuccess = _Check_StaffOrder($dbConnect,$post);
			if($isSuccess == false){

				$post['errorName'] = 'userMainte';
				$post['menuName']  = 'isMenuAdmin';
				$post['returnUrl'] = '/mainte/usermainte_top.php';
				$post['errorId'][] = '018';
				$errorUrl             = HOME_URL . 'error.php';
				redirectPost($errorUrl, $post);
			}

			// トランザクション開始
			db_Transaction_Begin($dbConnect);

			// 削除
			$isSuccess = _Delete_M_Staff($dbConnect,$post);
			if($isSuccess == false){
				db_Transaction_Rollback($dbConnect);
				$post['errorName'] = 'userMainte';
				$post['menuName']  = 'isMenuAdmin';
				$post['returnUrl'] = '/mainte/usermainte_top.php';
				$post['errorId'][] = '103';
				$errorUrl             = HOME_URL . 'error.php';
				redirectPost($errorUrl, $post);
			}

			// コミット
			db_Transaction_Commit($dbConnect);
		} else {
			// エラーチェック
			_check_Data($dbConnect,$post);

			// トランザクション開始
			db_Transaction_Begin($dbConnect);

            // 店舗変更があったかチェック
            $isMoveComp = _checkMoveComp($dbConnect,$post,$oldCompId);
            if ($isMoveComp) {
                // 店舗変更通知メール用に変更前のユーザー情報を取得
                $oldStaffInfo = getUserMaster($dbConnect, $post['StaffSeqID']);                
            } 

			// 更新
			$isSuccess = _Update_M_Staff($dbConnect,$post);

            if ($isSuccess) {
                // 申請済発注情報の発送先を変更
                $isSuccess = _Update_T_Order($dbConnect,$post);
            }

			if($isSuccess == false){
				db_Transaction_Rollback($dbConnect);
				$post['errorName'] = 'userMainte';
				$post['menuName']  = 'isMenuAdmin';
				$post['returnUrl'] = '/mainte/usermainte_top.php';
				$post['errorId'][] = '102';
				$errorUrl             = HOME_URL . 'error.php';
				redirectPost($errorUrl, $post);
			}

			// コミット
			db_Transaction_Commit($dbConnect);

            // 店舗が変更されていたらメール送信
            if ($isMoveComp) {
                sendMoveInfo($dbConnect,$post,$oldCompId, $oldStaffInfo);
            }

		}

		$errorUrl             = '/mainte/usermainte_top.php';
		redirectPost($errorUrl, "");
		break;
		
	case 'kakunin':
		
		$staffCode     = trim($post['staffCode']);		// 社員番号
		$personName    = trim($post['personName']);		// 氏名
		$staffFirstDay = trim($post['staffFirstDay']);	// 初回貸与年月日
		$staffNextDay  = trim($post['staffNextDay']);	// 再貸与予定年月日	
		$hatureibi     = trim($post['hatureibi']);		// 発令日
		$staffNextCode = trim($post['staffNextCode']);	// 発令後・社員番号
		$NextCompID    = trim($post['compNextSelect']);	// 発令後・個所
		$StaffSeqID    = trim($post['StaffSeqID']);		// シーケンスID
		$nowPage       = trim($post['nowPage']);		
		$motoStaffCode = trim($post['motoStaffCode']);	// 重複登録用

		$isUpdateFlag = trim($post['isUpdateFlag']);
		$ope = trim($post['motoMode']);
		break;
}


// 検索条件
$searchCompCd     = $post['searchCompCd'];
$searchCompName   = $post['searchCompName'];
$searchCompId     = $post['searchCompId'];
$searchStaffCode  = $post['searchStaffCode'];
$searchPersonName = $post['searchPersonName'];
$isSelectedAdmin  =	$post['isSelectedAdmin'];
$nowPage          =	$post['nowPage'];
// 元スタッフID設定
if(trim($post['motoStaffCode']) == ""){
	$motoStaffCode = trim($userData['StaffCode']);
}else{
	$motoStaffCode = trim($post['motoStaffCode']);
}

# $hidden = makehidden($post);

// 表示する値の成型 ここまで +++++++++++++++++++++++++++++++++++++++++++++++++++

// 追加処理
function _Insert_M_Staff($dbConnect,$post){

	$sql  = " INSERT INTO M_Staff (";
	$sql .= "  CompID";				// 店舗ID
	$sql .= " ,CompCd";				// 店舗コード
	$sql .= " ,StaffCode";			// スタッフコード
	$sql .= " ,PersonName";			// スタッフ氏名
	$sql .= " ,HatureiDay";			// 発令日
	$sql .= " ,NextCompID";			// 人事異動・店舗ID
	$sql .= " ,Del";				// 削除フラグ
	$sql .= " ,RegistDay";			// 登録日
	$sql .= " ,RegistUser";			// 登録ユーザー
	$sql .= " ,UpdDay";				// 更新日
	$sql .= " ,UpdUser";			// 更新ユーザー
	$sql .= " ) VALUES (";
	$sql .= " '".db_Escape(trim($post['selectCompId']))."'";	// 施設ID
 	$sql .= ",'".db_Escape(trim($post['selectCompCd']))."'";	// 施設コード
	$sql .= ",'".db_Escape(trim($post['staffCode']))."'";		// スタッフコード
	$sql .= ",'".db_Escape(trim($post['personName']))."'";		// スタッフ氏名
	$sql .= " ,NULL";											// 発令日
	$sql .= " ,NULL";											// 人事異動・店舗ID
	$sql .= " ,".DELETE_OFF;									// 削除フラグ
	$sql .= " ,GETDATE()";										// 登録日
	$sql .= " ,'".db_Escape(trim($_SESSION['NAMECODE']))."' ";	// 登録ユーザー
	$sql .= " ,NULL";											// 更新日
	$sql .= " ,NULL";											// 更新ユーザー
	$sql .= " )";

	$isSuccess = db_Execute($dbConnect, $sql);
	
 	// 実行結果が失敗の場合
	if ($isSuccess == false) {
		return false;
	}
	return true;
}

// 更新処理
function _Update_M_Staff($dbConnect,$post){

    // T_Staffに登録があれば更新
    $sql  = " SELECT";
    $sql .=     " COUNT (*) AS count_data";
    $sql .= " FROM";
    $sql .=     " T_Staff";
    $sql .= " WHERE";
    $sql .=     " StaffID = '" . db_Escape(trim($post['StaffSeqID']))."'";
    $sql .= " AND";
    $sql .=     " Del = ".DELETE_OFF;

    $result = db_Read($dbConnect, $sql);

    if ($result[0]['count_data'] != 0) {

        $sql  = " UPDATE T_Staff SET  ";
        $sql .= "  CompID        = '".db_Escape(trim($post['selectCompId']))."'";
        $sql .= " ,StaffCode     = '".db_Escape(trim($post['staffCode']))."'";
        $sql .= " ,UpdDay        = GETDATE()";
        $sql .= " ,UpdUser       = '".db_Escape(trim($_SESSION['NAMECODE']))."' ";
        $sql .= " WHERE ";
        $sql .=     " StaffID = '" . db_Escape(trim($post['StaffSeqID']))."'";
	    $sql .= " AND";
        $sql .=     " Del = ".DELETE_OFF;

        $isSuccess = db_Execute($dbConnect, $sql);

        // 実行結果が失敗の場合
        if ($isSuccess == false) {
            return false;
        }

    }

    // M_Staffを更新
    $sql  = " UPDATE M_Staff SET  ";
    $sql .= "  CompID        = '".db_Escape(trim($post['selectCompId']))."'";
    $sql .= " ,CompCd        = '".db_Escape(trim($post['selectCompCd']))."'";
    $sql .= " ,StaffCode     = '".db_Escape(trim($post['staffCode']))."'";
    $sql .= " ,PersonName    = '".db_Escape(trim($post['personName']))."'";

    if($post['hatureibi'] == ""){
        $sql .= " ,HatureiDay = NULL";
    }else{
        $sql .= " ,HatureiDay = '".db_Escape(trim($post['hatureibi']))."'";
    }
    $sql .= " ,NextCompID    = '".db_Escape(trim($post['selectNextCompId']))."'";

    $sql .= " ,UpdDay        = GETDATE()";
    $sql .= " ,UpdUser       = '".db_Escape(trim($_SESSION['NAMECODE']))."' ";
    $sql .= " WHERE ";
    $sql .=     " Del = ".DELETE_OFF;
    $sql .= " AND";
    $sql .=     " StaffSeqID = '" . db_Escape(trim($post['StaffSeqID']))."'";

    $isSuccess = db_Execute($dbConnect, $sql);

    // 実行結果が失敗の場合
    if ($isSuccess == false) {
        return false;
    }

	return true;
}

// 更新処理
function _Update_T_Order($dbConnect,$post){

    // T_Orderの情報を更新
    $sql  = " UPDATE T_Order SET  ";
    $sql .= "  CompID                 = '".db_Escape(trim($post['selectCompId']))."'";
    $sql .= " ,StaffCode              = '".db_Escape(trim($post['staffCode']))."'";
    $sql .= " ,PersonName             = '".db_Escape(trim($post['personName']))."'";

    $sql .= " ,UpdDay                 = GETDATE()";
    $sql .= " ,UpdUser                = '".db_Escape(trim($_SESSION['NAMECODE']))."' ";
    $sql .= " WHERE ";
    $sql .=     " StaffID             = '" . db_Escape(trim($post['StaffSeqID']))."'";
    $sql .= " AND";
    $sql .=     " Del                 = ".DELETE_OFF;

    $isSuccess = db_Execute($dbConnect, $sql);

    // 実行結果が失敗の場合
    if ($isSuccess == false) {
        return false;
    }

    // まだ倉庫に送信していないデータがあれば更新
    $sql  = " SELECT";
    $sql .=     " COUNT (*) AS count_data";
    $sql .= " FROM";
    $sql .=     " T_Order";
    $sql .= " WHERE";
    $sql .=     " StaffID = '" . db_Escape(trim($post['StaffSeqID']))."'";
    $sql .= " AND";
    $sql .=     " Status IN (".STATUS_APPLI.",".STATUS_APPLI_ADMIT.",".STATUS_NOT_RETURN.",".STATUS_NOT_RETURN_ADMIT.",".STATUS_LOSS.",".STATUS_LOSS_ADMIT.")";
    $sql .= " AND";
    $sql .=     " Del = ".DELETE_OFF;

    $result = db_Read($dbConnect, $sql);

    if ($result[0]['count_data'] != 0) {
        
        $shipData = getShipData($dbConnect,$post['selectCompId']);

        $sql  = " UPDATE T_Order SET  ";
        $sql .= "  AppliCompCd            = '".db_Escape(trim($shipData['CompCd']))."'";
        $sql .= " ,AppliCompName          = '".db_Escape(trim($shipData['CompName']))."'";
        $sql .= " ,Zip                    = '".db_Escape(trim($shipData['Zip']))."'";
        $sql .= " ,Adrr                   = '".db_Escape(trim($shipData['Adrr']))."'";
        $sql .= " ,Tel                    = '".db_Escape(trim($shipData['Tel']))."'";
        $sql .= " ,ShipName               = '".db_Escape(trim($shipData['ShipName']))."'";
        $sql .= " ,TantoName              = '".db_Escape(trim($shipData['TantoName']))."'";

        $sql .= " ,UpdDay                 = GETDATE()";
        $sql .= " ,UpdUser                = '".db_Escape(trim($_SESSION['NAMECODE']))."' ";
        $sql .= " WHERE ";
        $sql .=     " StaffID             = '" . db_Escape(trim($post['StaffSeqID']))."'";
        $sql .= " AND";
        $sql .=     " Status IN (".STATUS_APPLI.",".STATUS_APPLI_ADMIT.",".STATUS_NOT_RETURN.",".STATUS_NOT_RETURN_ADMIT.",".STATUS_LOSS.",".STATUS_LOSS_ADMIT.")";
        $sql .= " AND";
        $sql .=     " Del                 = ".DELETE_OFF;

        $isSuccess = db_Execute($dbConnect, $sql);

        // 実行結果が失敗の場合
        if ($isSuccess == false) {
            return false;
        }

    }

    return true;
}

// 削除処理
function _Delete_M_Staff($dbConnect,$post){

    // M_Staffを更新
    $sql  = " UPDATE M_Staff SET ";
	$sql .= 	"  Del = 1";
    $sql .= 	" ,UpdDay        = GETDATE()";
    $sql .= 	" ,UpdUser       = '".db_Escape(trim($_SESSION['NAMECODE']))."' ";
    $sql .= " WHERE ";
    $sql .= 	" Del = ".DELETE_OFF;
    $sql .= " AND";
    $sql .= 	" StaffSeqID = '" . db_Escape(trim($post['StaffSeqID']))."'";

    $isSuccess = db_Execute($dbConnect, $sql);

    // 実行結果が失敗の場合
    if ($isSuccess == false) {
        return false;
    }

	return true;
}

// 商品の貸与中チェック
function _Check_StaffOrder($dbConnect,$post) {

	$sql  = " SELECT";
	$sql .= 	" COUNT(StaffDetID) AS cnt";
	$sql .= " FROM";
	$sql .= 	" T_Staff_Details AS tsd";
	$sql .= " INNER JOIN";
	$sql .= 	" T_Staff AS ts";
	$sql .= " ON";
	$sql .= 	" tsd.StaffID = ts.StaffID ";
	$sql .= " WHERE";
	$sql .= 	" ts.StaffCode = '" . $post['motoStaffCode'] . "'";
	$sql .= " AND";
	$sql .= 	" tsd.Del = ".DELETE_OFF;
	$sql .= " AND";
	$sql .= 	" ts.Del = ".DELETE_OFF;
	$sql .= " AND";
	$sql .= 	" tsd.Status IN (";
	$sql .= 	" " . STATUS_APPLI;					// 申請済(承認待)
	$sql .= 	"," . STATUS_APPLI_ADMIT;			// 申請済
	$sql .= 	"," . STATUS_STOCKOUT;				// 在庫切
	$sql .= 	"," . STATUS_ORDER;					// 受注済
	$sql .= 	"," . STATUS_SHIP;					// 出荷済
	$sql .= 	"," . STATUS_DELIVERY;				// 納品済
	$sql .= 	"," . STATUS_NOT_RETURN_ADMIT;		// 返却申請済
	$sql .= 	"," . STATUS_NOT_RETURN_ORDER;		// 返却受注済
	$sql .= 	"," . STATUS_LOSS_ADMIT;			// 紛失申請済
	$sql .= " )";

	$result = db_Read($dbConnect, $sql);

	if($result[0]['cnt'] == 0){
		return true;

	}else{
		return false;

	}
}

/*
 * ユーザーマスタ情報を取得する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$userId         => 検索ユーザーID
 * 戻り値：$result         => ユーザーマスタ情報
 *
 * create 2007/03/26 H.Osugi
 *
 */
function getUserMaster($dbConnect, $StaffSeqID) {

	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	"  ms.StaffSeqID";
	$sql .= 	" ,ms.CompID";
	$sql .= 	" ,mc1.CompCd AS CompCd";
	$sql .= 	" ,mc1.CompName AS CompName";
	$sql .= 	" ,ms.StaffCode";
	$sql .= 	" ,ms.PersonName";
	$sql .= 	" ,CONVERT(char, ms.HatureiDay, 11) as HatureiDay";	
	$sql .=		" ,ms.NextCompID";
	$sql .= 	" ,mc2.CompCd AS NextCompCd";
	$sql .=		" ,mc2.CompName AS NextCompName";
	$sql .= " FROM";
	$sql .= 	" M_Staff AS ms";
	$sql .= " LEFT JOIN M_Comp AS mc1";
	$sql .= 	" ON ms.CompID = mc1.CompID";
	$sql .= 	" AND mc1.Del = ".DELETE_OFF;
	$sql .= " LEFT JOIN M_Comp AS mc2";
	$sql .= 	" ON ms.NextCompID = mc2.CompID";
	$sql .= 	" AND mc2.Del = ".DELETE_OFF;

	$sql .= " WHERE";
	$sql .= 	" ms.Del = ".DELETE_OFF;
	$sql .= " AND";
	$sql .= 	" ms.StaffSeqID = '" . db_Escape($StaffSeqID) . "'";

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
	 	return false;
	}

	return $result[0];
}

// StaffCodeが存在するかチェック
function _Seach_StaffCode($dbConnect, $StaffCode) {

	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	"  count(ms.StaffSeqID) as cnt";
	$sql .= " FROM";
	$sql .= 	" M_Staff AS ms";
	$sql .= " WHERE";
	$sql .= 	" ms.Del = ".DELETE_OFF;
	$sql .= " AND";
	$sql .= 	" ms.StaffCode = '" . db_Escape($StaffCode) . "'";

	$result = db_Read($dbConnect, $sql);

	if($result[0]['cnt'] == 0){
		return true;
	}else{
		return false;
	}
}

/*
 * Compマスター情報を取得する
 * 引数  ：$dbConnect      => コネクションハンドラ
 *       ：$compId         => 検索企業CD
 * 戻り値：$result         => ユーザーマスタ情報
 *
 * create 2007/03/26 H.Osugi
 *
 */
function getCompMaster($dbConnect,$compId="") {

	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" CompID";
	$sql .= 	",CompCd";
	$sql .= 	",CompName";
	$sql .= " FROM";
	$sql .= 	" M_Comp";
	$sql .= " WHERE";
	$sql .= 	" Del = ".DELETE_OFF;
	$sql .= " AND";
    $sql .=     " ShopFlag <> 0";        // ShopFlagが0以外の店舗を表示する

	if($compId != ""){
		$sql .= " AND";
		$sql .= 	" CompID  = '" . db_Escape($compId) . "'";
	}

	$sql .= " ORDER BY";
	// 並び順変更
	//$sql .= 	" CompCd";
	$sql .= 	" HonbuCd,";
	$sql .= 	" ShibuCd,";
	$sql .= 	" CompCd";

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	return $result;
}

// 発送先情報を取得
function getShipData ($dbConnect,$compId) {

    $sql  = "";
    $sql .= " SELECT";
    $sql .=     " CompID";
    $sql .=     ",CompCd";
    $sql .=     ",CompName";
    $sql .=     ",Zip";
    $sql .=     ",Adrr";
    $sql .=     ",Tel";
    $sql .=     ",ShipName";
    $sql .=     ",TantoName";
    $sql .= " FROM";
    $sql .=     " M_Comp";
    $sql .= " WHERE";
    $sql .=     " Del = ".DELETE_OFF;
    $sql .= " AND";
    $sql .=     " ShopFlag <> 0";        // ShopFlagが0以外の店舗を表示する

    if($compId != ""){
        $sql .= " AND";
        $sql .=     " CompID  = '" . db_Escape($compId) . "'";
    }

    $sql .= " ORDER BY CompCd";
    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合
    if (count($result) <= 0) {
        $result = array();
        return $result;
    }

    return $result[0];
    
}

// hidden値作成
function makehidden($post){
	$i = 0;
	foreach($post as $key => $val){
		
		if(is_array($val)){
			foreach($val as $key2 => $val2){
				$hiddens[$i]['hdn'] = "<input type=\"hidden\" name=\"".$key."[".$key2."]\" value=\"".$val2."\">\n";
				$i++;
			}
		}else{
			$hiddens[$i]['hdn'] = "<input type=\"hidden\" name=\"".$key."\" value=\"".$val."\">\n";
			$i++;
		}
		
	}
	return $hiddens;
}

// 店舗移動チェック
function _checkMoveComp($dbConnect,$post,&$oldCompId) {
    
    $sql  = " SELECT";
    $sql .=     " CompID";
    $sql .= " FROM";
    $sql .=     " M_Staff";
    $sql .= " WHERE";
    $sql .=     " StaffSeqID = '" . db_Escape($post['StaffSeqID']) . "'";
    $sql .= " AND";
    $sql .=     " Del = ".DELETE_OFF;

    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合
    if (count($result) <= 0) {
        return false;
    }

    $oldCompId = $result[0]['CompID'];

    // 登録店舗と画面入力値を比較
    if ($oldCompId == $post['selectCompId']) {
        return false;
    }

    return true;
}

// データチェック
function _check_Data($dbConnect,$post){

	$post['errorId'] = "";
	$errflg = false;

	// 職員コードが空の時エラー
	if(trim($post['staffCode']) == ""){
		$errflg =true;
		$post['errorId'][] = '011';
	}else{
		// 職員コードに変更があった場合は既に存在するコードかどうかをチェック
		if(trim($post['staffCode']) != trim($post['motoStaffCode'])){
			$rtn = _Seach_StaffCode($dbConnect, trim($post['staffCode']));
			// 存在チェック
			if(!$rtn){
				$errflg =true;
				$post['errorId'][] = '016';
			}
		}
		// 職員コードは数字アルファベットの半角12桁
     	if(!preg_match('/^[0-9a-zA-Z]{12,12}$/', $post['staffCode'])){
			$errflg =true;
			$post['errorId'][] = '021';
		}
	}
	// 社員名が空のとき
	if(trim($post['personName']) == ""){
		$errflg =true;
		$post['errorId'][] = '012';
	}

	// 施設
	if(trim($post['selectCompId']) == ""){
		$errflg =true;
		$post['errorId'][] = '013';
	}

	// 発令以外が入力されている。
	if(trim($post['selectNextCompId']) != ""){
		if(trim($post['hatureibi']) == ""){
			$errflg =true;
			$post['errorId'][] = '019';
		}
	}

	// 発令日
	if(trim($post['hatureibi']) != ""){
		// 発令日日付チェック
		if(!_chk_is_date2(trim($post['hatureibi']))){	
			$errflg =true;
			$post['errorId'][] = '017';
		}

		// 異動先施設
		if(trim($post['selectNextCompId']) == ""){
			$errflg =true;
			$post['errorId'][] = '015';
		}
	}

	if($errflg){
		$post['errorName'] = 'userMainte';
		$post['menuName']  = 'isMenuAdmin';
		$post['returnUrl'] = 'mainte/usermainte.php';
		$errorUrl             = HOME_URL . 'error.php';
		$post['motoMode'] = $post['Mode'];
		$post['Mode'] = "kakunin";
		redirectPost($errorUrl, $post);
	}

	return true;

}
// 日付判定
function _chk_is_date1($pValue, $pSplit = "/")
{
    if ( substr_count($pValue, $pSplit) <> 2 ) {
        return FALSE;
    }
    
    list($year, $month, $day) = explode($pSplit, $pValue);
    if ( ereg('^[0-9]{2}', $year) && _chk_is_number2($month) && _chk_is_number2($day) ) {
        $rtn =  ( checkdate($month, $day, $year) ) ? TRUE : FALSE;
    } else {
        $rtn = FALSE;
    }
    return $rtn;
}
function _chk_is_date2($pValue, $pSplit = "/")
{
    if ( substr_count($pValue, $pSplit) <> 2 ) {
        return FALSE;
    }
    
    list($year, $month, $day) = explode($pSplit, $pValue);
    if ( ereg('^[0-9]{4}', $year) && _chk_is_number2($month) && _chk_is_number2($day) ) {
        $rtn =  ( checkdate($month, $day, $year) ) ? TRUE : FALSE;
    } else {
        $rtn = FALSE;
    }
    return $rtn;
}

// 数値判定
function _chk_is_number2($pValue)
{
    if ( !ereg('^[0-9]+$', $pValue) ) {
        $rtn = FALSE;
    } else {
        $rtn = TRUE;
    }
    return $rtn;
}

// 店舗移動メールを送信する
function sendMoveInfo($dbConnect,$post,$oldCompId,$oldStaffInfo) {

    // 新旧の店舗情報を取得
    $result = getCompMaster($dbConnect,trim($post['selectCompId']));
    $compInfo['new'] = $result[0];

    $result = getCompMaster($dbConnect,trim($oldCompId));
    $compInfo['old'] = $result[0];

    // 現在のスタッフ情報を取得
    $staffInfo['new'] = getUserMaster($dbConnect, $post['StaffSeqID']); 
    $staffInfo['old'] = $oldStaffInfo; 

    
    $filePath = '../../mail_template/';

	// Modify by Y.Furukawa at 2017/12/12
    //$isSuccess = moveCompMail($dbConnect,$filePath, $compInfo, $staffInfo, &$subject, &$message);
    $isSuccess = moveCompMail($dbConnect,$filePath, $compInfo, $staffInfo, $subject, $message);

    if ($isSuccess == false) {
        return false;
    }
    $toAddr = MAIL_GROUP_6;

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
?>
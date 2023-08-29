<?php
/*
 * 発注申請画面（女性用）
 * hachu_shinsei.src.php
 *
 * create 2007/03/14 H.Osugi
 * update 2007/03/26 H.Osugi
 * update 2008/04/14 W.Takasaki
 *
 */

// 各種モジュールの取得
include_once('../../include/define.php');           	// 定数定義
require_once('../../include/dbConnect.php');        	// DB接続モジュール
require_once('../../include/msSqlControl.php');     	// DB操作モジュール
require_once('../../include/checkLogin.php');       	// ログイン判定モジュール
require_once('../../include/checkDuplicateStaff.php');	// スタッフ重複判定モジュール
require_once('../../include/getComp.php');          	// 店舗情報取得モジュール
require_once('../../include/getUser.php');          	// ユーザ情報取得モジュール
require_once('../../include/getSize.php');          	// サイズ情報取得モジュール
require_once('../../include/createRequestNo.php');  	// 申請番号生成モジュール
require_once('../../include/castHidden.php');       	// hidden値成型モジュール
require_once('../../include/castHtmlEntity.php');   	// HTMLエンティティモジュール
require_once('../../include/redirectPost.php');     	// リダイレクトポストモジュール
require_once('../../include/commonFunc.php');       	// 共通関数モジュール


// 初期設定
$isMenuOrder   = true;  // 発注のメニューをアクティブに

// 変数の初期化 ここから ******************************************************
$requestNo      = '';               // 申請番号
$staffCode      = '';               // スタッフコード
$personName     = '';               // スタッフ氏名
$zip1           = '';               // 郵便番号（前半3桁）
$zip2           = '';               // 郵便番号（後半4桁）
$address        = '';               // 住所
$shipName       = '';               // 出荷先名
$staffName      = '';               // ご担当者
$tel            = '';               // 電話番号
$yoteiDay       = '';               // 出荷予定日
$memo           = '';               // メモ

$selectedSize1 = '';                // 選択されたサイズ（No1）
$selectedSize2 = '';                // 選択されたサイズ（No2）
$selectedSize3 = '';                // 選択されたサイズ（No3）
$selectedSize4 = '';                // 選択されたサイズ（No4）
$selectedSize5 = '';                // 選択されたサイズ（No5）

$selectedColor1 = false;            // 選択されたブラウスの色（オフホワイト）
$selectedColor2 = false;            // 選択されたブラウスの色（ペールピンク）
$selectedColor3 = false;            // 選択されたブラウスの色（サックスブルー）

$haveRirekiFlg  = false;            // 発注申請か発注変更かの判定フラグ（true：発注変更 / false：発注申請）

$isMotoTok      = false;            // 発注訂正する時に元の発注で特寸が選択されていたか

// 変数の初期化 ここまで ******************************************************

$post = $_POST; 
// スタッフIDが取得できなければエラーに
if (!isset($post['rirekiFlg']) || !$post['rirekiFlg']) {
    if (!isSetValue($post['staffId'])) {
        redirectTop();
    }
    // 新規の場合は初回か個別かの判定値がなければエラー
    if (!isset($post['hachuShinseiFlg']) || !$post['hachuShinseiFlg']) {
        if(isSetValue($post['appliReason'])) {
            $appliReason     = $post['appliReason'];
        } else {
            redirectTop();
        }

// Commented by T.Uno at 2022/09/30
//       if(isSetValue($post['searchPatternId'])) {
//            $searchPatternId = $post['searchPatternId'];
//        } else {
//			$returnUrl = './hachu/hachu_top.php';
//
//		    $hiddenHtml = castHiddenError($post);
//
//			// エラー画面で必要な値のセット
//			$hiddens = array();
//			$hiddens['errorName'] = 'hachuShinsei';
//			$hiddens['menuName']  = 'isMenuOrder';
//			$hiddens['returnUrl'] = $returnUrl;
//			$hiddens['errorId'][] = '200';
//
//			if (is_array($hiddenHtml)) {
//				$hiddens = array_merge($hiddens, $hiddenHtml);
//			}
//			redirectPost(HOME_URL . 'error.php', $hiddens);
//      }


//        if(isSetValue($post['searchFukusyuID'])) {
//            $searchFukusyuID = $post['searchFukusyuID'];
//        } else {
//            redirectTop();
//        }
//        if(isSetValue($post['searchGenderKbn'])) {
//            $searchGenderKbn = $post['searchGenderKbn'];
//        } else {
//            redirectTop();
//        }
    }
} else {    // 変更時は申請IDがなければエラー
    if (!isSetValue($post['orderId'])) {
        redirectTop();
    }

    $isMenuOrder   = false; // 発注のメニューをオフ
    $isMenuHistory = true;  // 申請履歴のメニューをアクティブに
    $haveRirekiFlg = true;  // 発注申請か発注変更かを判定するフラグ

}

// POST値をHTMLエンティティ
$post = castHtmlEntity($post); 

// 表示する値の成型 ここから +++++++++++++++++++++++++++++++++++++++++++++++++++

// 初期表示の場合
if (!isset($post['hachuShinseiFlg']) || !$post['hachuShinseiFlg']) {
    if (isset($post['rirekiFlg']) && $post['rirekiFlg']) {     // 申請履歴から遷移してきた場合（発注変更）
        $orderId = trim($post['orderId']);
        // 注文情報を取得
        $post = castHtmlEntity(getOrdarData($dbConnect, $_POST, $isMotoTok));
       
        $compCd   = $post['compCd'];    // 店舗番号
        $compName = $post['compName'];  // 店舗名
        $compId   = $post['compId'];    // 店舗ID
    }
}

// 発注区分をセット
if (isSetValue($post['appliReason']) && (int)$post['appliReason']) {
    $appliReason = $post['appliReason'];
} else {
    redirectTop();
}


// Commented by T.Uno at 2022/09/30
//if(isSetValue($post['searchPatternId'])) {
//    $searchPatternId = $post['searchPatternId'];
//    $searchPatternName = getStaffPattern($dbConnect, $searchPatternId);
//} else {
//    redirectTop();
//}

//if (isSetValue($post['searchFukusyuID']) && (int)$post['searchFukusyuID']) {
//    $searchFukusyuID = $post['searchFukusyuID'];
//} else {
//    redirectTop();
//}
//if (isSetValue($post['searchGenderKbn']) && (int)$post['searchGenderKbn']) {
//    $searchGenderKbn = $post['searchGenderKbn'];
//} else {
//    redirectTop();
//}

// 社員IDからページヘッダー部分に表示する情報を取得
if (isSetValue($post['staffId'])) {
    $headerInfo = getHeaderData($dbConnect, $post['staffId']);
} else {
    redirectTop();
}

// Added by T.Uno at 2022/09/30
switch ($appliReason) {
	case APPLI_REASON_ORDER_BASE:		// 基本パターン
	case APPLI_REASON_ORDER_FRESHMAN:	// 新入社員
		switch ($headerInfo['CompKind']) {
			case '1':	// そんぽの家系
				$searchPatternId = PATTERNID_JITAKU_LIKE;	// そんぽの家系
				break;
			case '2':	// ラヴィーレ系
				$searchPatternId = PATTERNID_HOTEL_LIKE;	// ラヴィーレ系
				break;
		}
		break;
	case APPLI_REASON_ORDER_GRADEUP:	// グレードアップタイ
		$searchPatternId = PATTERNID_GRADEUP_TIE;			// グレードアップタイ
		break;
	case APPLI_REASON_ORDER_PERSONAL:	// 個別発注申請
		$searchPatternId = PATTERNID_PERSONAL;				// 個別発注申請
		break;
}
if(isSetValue($searchPatternId)) {
    $searchPatternName = getStaffPattern($dbConnect, $searchPatternId);
} else {
    redirectTop();
}

// スタッフID
$staffId = '';
if (isSetValue($post['staffId'])) {
    $staffId = $post['staffId'];
}

// スタッフコード
$staffCode = '';
if (isSetValue($headerInfo['StaffCode'])) {
    $staffCode = $headerInfo['StaffCode'];
} 

// 着用者名コード
$personName = '';
if (isSetValue($headerInfo['PersonName'])) {
    $personName = $headerInfo['PersonName'];
} 

// 店舗ID
$compId = '';
if (isSetValue($headerInfo['CompID'])) {
    $compId = $headerInfo['CompID'];
} 

// 店舗コード
$compCd = '';
if (isSetValue($headerInfo['CompCd'])) {
    $compCd = $headerInfo['CompCd'];
} 

// 店舗ｺｰﾄﾞが伊勢丹新宿店の場合
// 追加 uesugi 09/01/30
if ($compCd == ORDER_ISETAN_SINJUKU){
	$post['AddIsetanItemFlg'] = True;
}else{
	$post['AddIsetanItemFlg'] = False;
}

// 店名
$compName = '';
if (isSetValue($headerInfo['CompName'])) {
    $compName = $headerInfo['CompName'];
} 

// 初回アクセス
if (!isset($post['hachuShinseiFlg']) || !$post['hachuShinseiFlg']) {

    // 表示用にHTMLエスケープ
    if (isSetValue($headerInfo)) {
        foreach ($headerInfo as $key => $val) {
            $headerInfo[$key] = htmlspecialchars($val, HTMLENTITY_QUOTE_STYLE, HTMLENTITY_ENCODE);
        }
    }

    if (isset($post['rirekiFlg']) && $post['rirekiFlg']) {  // 履歴からのアクセス

        // 申請番号を生成
        $requestNo = trim($post['requestNo']);

        // 郵便番号
        $zip1 = '';
        $zip2 = '';
        if (isSetValue($post['Zip'])) {
            list($zip1, $zip2) = explode('-', $post['Zip']);
        }
    
        // 住所
        $address = '';
        if (isSetValue($post['Adrr'])) {
            $address = $post['Adrr'];
        }
    
        // 出荷先名
        $shipName = '';
        if (isSetValue($post['ShipName'])) {
            $shipName = $post['ShipName'];
        }
    
        // 電話番号
        $tel = '';
        if (isSetValue($post['Tel'])) {
            $tel = $post['Tel'];
        }
    
        // ご担当者名を取得（HTMLエンティティ済）
        $staffName = '';
        if (isSetValue($post['TantoName'])) {
            $staffName = $post['TantoName'];
        }

        // 出荷予定日
        $yoteiDay = '';
        if (isSetValue($post['yoteiDay'])) {
            $yoteiDay = $post['yoteiDay'];
        }

        // メモ
        $memo = '';
        if (isSetValue($post['memo'])) {
            $memo = $post['memo'];
        }

		// 新品中古区分
		$new_Item = false;
		if(trim($post['newOldKbn']) == 1){
			$new_Item = true;
		}

    } else {

        // 申請番号を生成
        $requestNo = createRequestNo($dbConnect, $headerInfo['CompID'], 1);
    
        // 申請番号の生成に失敗した場合はエラー
        if ($requestNo == false) {
            redirectTop();
        }

        // 郵便番号
        $zip1 = '';
        $zip2 = '';
        if (isSetValue($headerInfo['Zip'])) {
            list($zip1, $zip2) = explode('-', $headerInfo['Zip']);
        }
    
        // 住所
        $address = '';
        if (isSetValue($headerInfo['Adrr'])) {
            $address = $headerInfo['Adrr'];
        }
    
        // 出荷先名
        $shipName = '';
        if (isSetValue($headerInfo['ShipName'])) {
            $shipName = $headerInfo['ShipName'];
        }
    
        // 電話番号
        $tel = '';
        if (isSetValue($headerInfo['Tel'])) {
            $tel = $headerInfo['Tel'];
        }
	
        // ご担当者名を取得（HTMLエンティティ済）
        $staffName = '';
        if (isSetValue($headerInfo['TantoName'])) {
            $staffName = $headerInfo['TantoName'];
        } else {
            $staffName  = DEFAULT_STAFF_NAME;
		}

		// 新品中古区分
		$new_Item = true;
    }

} else {  // POST情報を引き継ぐ場合

    // OrderId
    if (isset($post['orderId'])) {
        $orderId = trim($post['orderId']);
    }

    // 申請番号を生成
    $requestNo = trim($post['requestNo']);

    // 郵便番号
    $zip1 = trim($post['zip1']);
    $zip2 = trim($post['zip2']);

    // 住所
    $address = trim($post['address']);

    // 出荷先名
    $shipName = trim($post['shipName']);

    // ご担当者
    $staffName  = trim($post['staffName']);

    // 電話番号
    $tel = trim($post['tel']);

    // 出荷予定日
    $yoteiDay = trim($post['yoteiDay']);

    // メモ
    $memo = trim($post['memo']);

	// 新品中古区分
	$new_Item = false;
	if(trim($post['newOldKbn']) == 1){
		$new_Item = true;
	}

    if (isset($post['motoTokFlg']) && trim($post['motoTokFlg']) == 1) {
        $isMotoTok = true;
    }

}

// hidden値の生成
if (isset($post['rirekiFlg']) && $post['rirekiFlg']) {

    $hiddens = array();

    // 検索フラグ
//    $hiddens['searchFlg'] = '';
//    if (isset($post['searchFlg']) && $post['searchFlg'] != '') {
//        $hiddens['searchFlg'] = $post['searchFlg'];
//    }

    // 現在のページ数
    if (isset($post['nowPage']) && $post['nowPage'] != '') {
        $hiddens['nowPage'] = $post['nowPage'];
    }

    // 施設コード
    if (isset($post['searchCompCd']) && $post['searchCompCd'] != '') {
        $hiddens['searchCompCd'] = $post['searchCompCd'];
    }

    // 施設名
    if (isset($post['searchCompName']) && $post['searchCompName'] != '') {
        $hiddens['searchCompName'] = $post['searchCompName'];
    }

    // 施設ID
    if (isset($post['searchCompId']) && $post['searchCompId'] != '') {
        $hiddens['searchCompId'] = $post['searchCompId'];
    }

    // 申請番号
    if (isset($post['searchAppliNo']) && $post['searchAppliNo'] != '') {
        $hiddens['searchAppliNo'] = $post['searchAppliNo'];
    }

    // 申請日
    if (isset($post['searchAppliDayFrom']) && $post['searchAppliDayFrom'] != '') {
        $hiddens['searchAppliDayFrom'] = $post['searchAppliDayFrom'];
    }
    if (isset($post['searchAppliDayTo']) && $post['searchAppliDayTo'] != '') {
        $hiddens['searchAppliDayTo'] = $post['searchAppliDayTo'];
    }

    // 出荷日
    if (isset($post['searchShipDayFrom']) && $post['searchShipDayFrom'] != '') {
        $hiddens['searchShipDayFrom'] = $post['searchShipDayFrom'];
    }
    if (isset($post['searchShipDayTo']) && $post['searchShipDayTo'] != '') {
        $hiddens['searchShipDayTo'] = $post['searchShipDayTo'];
    }

    // スタッフコード
    if (isset($post['searchStaffCode']) && $post['searchStaffCode'] != '') {
        $hiddens['searchStaffCode'] = $post['searchStaffCode'];
    }

    // 単品番号 
    if (isset($post['searchBarCode']) && $post['searchBarCode'] != '') {
        $hiddens['searchBarCode'] = $post['searchBarCode'];
    }

    // 状態
    $countSearchStatus = count($post['searchStatus']);
    for ($i=0; $i<$countSearchStatus; $i++) {
        $hiddens['searchStatus[' . $i . ']'] = $post['searchStatus'][$i];
    }

    $hiddenHtml = castHidden($hiddens);

} else {

    $hiddens = array();

    // 検索フラグ
//    $hiddens['searchFlg'] = '';
//    if (isset($post['searchFlg']) && $post['searchFlg'] != '') {
//        $hiddens['searchFlg']         = $post['searchFlg'];
//    }

    // 現在のページ数
    if (isset($post['nowPage']) && $post['nowPage'] != '') {
        $hiddens['nowPage']           = $post['nowPage'];
    }

    // 事業部
    if (isset($post['searchHonbuId']) && $post['searchHonbuId'] != '') {
        $hiddens['searchHonbuId']     = $post['searchHonbuId'];
    }

    // エリア
    if (isset($post['searchShitenId']) && $post['searchShitenId'] != '') {
        $hiddens['searchShitenId']    = $post['searchShitenId'];
    }

    // 施設
    if (isset($post['searchEigyousyoId']) && $post['searchEigyousyoId'] != '') {
        $hiddens['searchEigyousyoId'] = $post['searchEigyousyoId'];
    }

    // 職員コード
    if (isset($post['searchStaffCode']) && $post['searchStaffCode'] != '') {
        $hiddens['searchStaffCode']   = $post['searchStaffCode'];
    }

    // 氏名
    if (isset($post['searchPersonName']) && $post['searchPersonName'] != '') {
        $hiddens['searchPersonName']  = $post['searchPersonName'];
    }

    $hiddenHtml = castHidden($hiddens);
}

if ($post['appliReason'] == APPLI_REASON_ORDER_PERSONAL) {  // 個別発注
    $isSyokai = false;  // 画面表示分岐用
} else {                                                    // 初回発注
    $isSyokai = true;  // 画面表示分岐用

	// Modify by Y.Furukawa at 2020/05/12 個別発注の場合は重複貸与チェックは無しとする。
	// スタッフIDの重複チェックを行う
	if ((!isset($post['hachuShinseiFlg']) || !$post['hachuShinseiFlg'])
	 && (!isset($post['rirekiFlg']) || !$post['rirekiFlg'])) {

		$returnUrl = './select_staff.php';
		//$returnUrl = './hachu/hachu_top.php';

	    $hiddenPost = castHiddenError($post);

		// 重複チェック
		checkDuplicateStaffID($dbConnect, $staffId, $returnUrl, $hiddenPost, $post['appliReason']);
	}
}

// 表示する情報を出力
$displayData = getDispItem($dbConnect, $post, $searchPatternId);
if (!$displayData) {
   redirectTop();
}

// 表示する値の成型 ここまで +++++++++++++++++++++++++++++++++++++++++++++++++++

/*
 * 変更する発注申請情報を取得する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$post      => POST値
 *       ：$sizeData1 => サイズ1
 *       ：$sizeData2 => サイズ2
 *       ：$sizeData3 => サイズ3
 *       ：$isMotoTok => 元の発注で特寸が選択されていたかどうか
 * 戻り値：$result    => 変更する商品一覧情報
 *
 * create 2007/03/26 H.Osugi
 *
 */
function getOrdarData($dbConnect, $post, &$isMotoTok) {

    // 初期化
    $returnDatas = $post;
    $isMotoTok = false;

    // OrderID
    $orderId = trim($post['orderId']);

    // 変更する発注申請情報を取得する
    $sql  = "";
    $sql .= " SELECT";
    $sql .=     " O.AppliNo,";
    $sql .=     " O.AppliCompCd,";
    $sql .=     " O.AppliCompName,";
    $sql .=     " O.AppliReason,";
    $sql .=     " O.AppliPattern,";
    $sql .=     " O.CompID,";
    $sql .=     " O.StaffID,";
    $sql .=     " O.StaffCode,";
    $sql .=     " S.FukusyuID,";
    $sql .=     " S.GenderKbn,";
    $sql .=     " O.Zip,";
    $sql .=     " O.Adrr,";
    $sql .=     " O.ShipName,";
    $sql .=     " O.TantoName,";
    $sql .=     " O.Tel,";
    $sql .=     " O.Note,";
    $sql .=     " O.NewOldKbn,";
    $sql .=     " YoteiDay = CASE";
    $sql .=     " WHEN";
    $sql .=         " O.YoteiDay = NULL";
    $sql .=             " THEN";
    $sql .=                 " NULL";
    $sql .=             " ELSE";
    $sql .=             " CONVERT(varchar,O.YoteiDay,111)";
    $sql .=         " END,";
    $sql .=     " C.CompKind";
    $sql .= " FROM";
    $sql .=     " T_Order O";
    $sql .=     " INNER JOIN";
    $sql .=     " M_Comp C";
    $sql .=     " ON";
    $sql .=         " C.CompID = O.CompID";
    $sql .=     " INNER JOIN";
    $sql .=     " M_Staff S";
    $sql .=     " ON";
    $sql .=         " S.StaffSeqID = O.StaffID";
    $sql .= " WHERE";
    $sql .=     " O.OrderID = '" . db_Escape($orderId) . "'";
    $sql .= " AND";
    $sql .=     " O.Del = " . DELETE_OFF;
    $sql .= " AND";
    $sql .=     " C.Del = " . DELETE_OFF;

    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合
    if (count($result) <= 0) {
        return $returnDatas;
    }

    $returnDatas['requestNo']              = $result[0]['AppliNo'];              // 申請番号
    $returnDatas['compCd']                 = $result[0]['AppliCompCd'];          // 店舗コード
    $returnDatas['compName']               = $result[0]['AppliCompName'];        // 店舗名
    $returnDatas['appliReason']            = $result[0]['AppliReason'];          // 発注区分
    $returnDatas['searchPatternId']        = $result[0]['AppliPattern'];         // 貸与パターン
    $returnDatas['compId']                 = $result[0]['CompID'];               // 店舗ID
    $returnDatas['compKind']               = $result[0]['CompKind'];             // 店舗種類
    $returnDatas['staffCode']              = $result[0]['StaffCode'];            // スタッフコード
    $returnDatas['staffId']                = $result[0]['StaffID'];              // スタッフID
//    $returnDatas['searchFukusyuID']        = $result[0]['FukusyuID'];            // 服種ID
//    $returnDatas['searchGenderKbn']        = $result[0]['GenderKbn'];            // 性別
    $returnDatas['newOldKbn']              = $result[0]['NewOldKbn'];            // 新品/中古

    list($returnDatas['zip1'], $returnDatas['zip2']) = explode('-', $result[0]['Zip']);     // 郵便番号

    $returnDatas['address']                = $result[0]['Adrr'];                 // 住所
    $returnDatas['shipName']               = $result[0]['ShipName'];             // 出荷先名
    $returnDatas['staffName']              = $result[0]['TantoName'];            // ご担当者
    $returnDatas['tel']                    = $result[0]['Tel'];                  // 電話番号
    $returnDatas['memo']                   = $result[0]['Note'];                 // メモ
    $returnDatas['yoteiDay']               = $result[0]['YoteiDay'];             // 出荷予定日

    $returnDatas['hachuShinseiFlg']        = true;                               // 処理分岐のフラグ

    $returnDatas['rirekiFlg']              = true;                               // 発注申請か発注変更かの判定フラグ


    // 変更する発注申請詳細情報を取得する
    $sql  = "";
    $sql .= " SELECT";
//    $sql .=     " DISTINCT";
    $sql .=     " mi.ItemNo,";
    $sql .=     " mi.ItemID,";
    $sql .=     " tod.Size,";
    $sql .=     " mi.SizeID";
    $sql .= " FROM";
    $sql .=     " T_Order_Details tod";
    $sql .= " INNER JOIN";
    $sql .=     " M_Item mi";
    $sql .= " ON";
    $sql .=     " tod.ItemID = mi.ItemID";
    $sql .= " AND";
    $sql .=     " mi.Del = " . DELETE_OFF;
    $sql .= " WHERE";
    $sql .=     " tod.OrderID = '" . db_Escape($orderId) . "'";
    $sql .= " AND";
    $sql .=     " tod.Del = " . DELETE_OFF;

    $result = db_Read($dbConnect, $sql);

    // 検索結果が0件の場合
    if (count($result) <= 0) {
        return $returnDatas;
    }

    $countOrderDetail = count($result);
    for ($i=0; $i<$countOrderDetail; $i++) {

        if ($i == 0 || $result[$i]['ItemID'] != $result[$i-1]['ItemID']) { 
            $sizeArray = array();
            $sizeArray = getSize($dbConnect, $result[$i]['SizeID'], 1);
    
            $returnDatas['size'.$result[$i]['ItemID']] = array_search($result[$i]['Size'], $sizeArray);
    
            // 特寸サイズが選択されていないか判定
            //if (array_search($result[$i]['Size'], $sizeArray) == 'Size'.count($sizeArray)) {
            //    $isMotoTok = true;
            //}
            if (trim($result[$i]['Size']) == '特寸') {
                $isMotoTok = true;
            }

            // アイテム個数
            $returnDatas['itemNumber'][$result[$i]['ItemID']] = 1;
        } else {
            $returnDatas['itemNumber'][$result[$i]['ItemID']]++;
        }
    }

    return $returnDatas;

}

/*
 * 表示するアイテム情報を取得する
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$post      => POST値
 * 戻り値：$result    => 表示する商品一覧情報
 *
 * create 2008/04/15 W.Takasaki
 *
 */
function getDispItem($dbConnect, $post, $patternId)
{

    $returnData = array();

    if ($post['appliReason'] == APPLI_REASON_ORDER_PERSONAL || (isset($post['rirekiFlg']) && $post['rirekiFlg'])) {  // 個別発注時

        // 表示するアイテム一覧を取得
        $sql  = "";
        $sql .= " SELECT";
        $sql .=     " mi.ItemID,";
        $sql .=     " mi.ItemNo,";
        $sql .=     " mi.ItemName,";
        $sql .=     " mi.SizeID";
        $sql .= " FROM";
        $sql .=     " M_Item mi";

	    if ($post['appliReason'] == APPLI_REASON_ORDER_PERSONAL && !isset($post['rirekiFlg'])) {
            $sql .= " INNER JOIN";
            $sql .= " M_ItemSelect ISelect";
            $sql .= " ON";
            $sql .= " mi.ItemID = ISelect.ItemID";
            $sql .= " AND";
            $sql .= " ISelect.PatternID = " . $patternId;
        }

        if (isset($post['rirekiFlg']) && $post['rirekiFlg']) {
            $sql .= " INNER JOIN";
            $sql .= " T_Order_Details tod";
            $sql .= " ON";
            $sql .= " mi.ItemID = tod.ItemID";
            $sql .= " AND";
            $sql .= " tod.Del = " . DELETE_OFF;
            $sql .= " AND";
            $sql .= " tod.OrderID = ".$post['orderId'];
        }
        $sql .= " WHERE";
        $sql .=     " mi.Del = " . DELETE_OFF;
        //if ($post['appliReason'] == APPLI_REASON_ORDER_PERSONAL && !isset($post['rirekiFlg'])) {
        //    $sql .= " AND";
        //    $sql .= 	" mi.DispFlg = " . COMMON_FLAG_ON;
        //}
        $sql .= " GROUP BY";
        $sql .=     " mi.ItemID,";
        $sql .=     " mi.ItemNo,";
        $sql .=     " mi.ItemName,";
        $sql .=     " mi.SizeID";
        $sql .= " ORDER BY";
        $sql .=     " mi.ItemID ASC";
        $result = db_Read($dbConnect, $sql);
    
        // 検索結果が0件の場合
        if (!is_array($result) || count($result) <= 0) {
            return false;
        }

    } else {        // 新規の初回
        // 表示するアイテム一覧を取得
        $sql = "";
        $sql .= " SELECT";
        $sql .=     " I.ItemID";
        $sql .=    " ,I.ItemNo";
        $sql .=    " ,ISelect.SizeID";
        $sql .=    " ,ISelect.ItemSelectName as ItemName";
        $sql .=    " ,ISelect.ItemSelectNum";
        $sql .=    " ,ISelect.FreeSizeFlag";
        $sql .=    " ,ISelect.GroupID";
        $sql .= " FROM";
        $sql .=    " M_Item I";
        $sql .=    " INNER JOIN";
        $sql .=    " M_ItemSelect ISelect";
        $sql .=    " ON";
        $sql .=    " I.ItemID = ISelect.ItemID";
        $sql .= " WHERE";
        //$sql .=     " ISelect.AppliReason = " . $post['appliReason'];
        $sql .=     " ISelect.PatternID = " . $patternId;
        $sql .= " AND";
        $sql .=     " I.Del = " . DELETE_OFF;
        $sql .= " AND";
        $sql .=     " ISelect.Del = " . DELETE_OFF;
    
        $result = db_Read($dbConnect, $sql);
    
        // 検索結果が0件の場合
        if (!is_array($result) || count($result) <= 0) {
            return false;
        }

        // データを整形
        for ( $chk=0; $chk < count($result); $chk++) {
//            $post['itemNumber'][$result[$chk]['ItemID']] = $result[$chk]['ItemSelectNum'];
        }
    }

    // サイズを取得,整形
    $limitAry = array();
    foreach ($result as $key => $val) {    

        $returnData[$key]['itemId'] = $val['ItemID'];
        $returnData[$key]['dispName'] = $val['ItemName'];
        $returnData[$key]['count'] = $key+1;

        // チェックボックスが選択されているか判定
        $returnData[$key]['checked'] = false;
        if (isset($post['itemIds']) && is_array($post['itemIds'])) {
            if (in_array($val['ItemID'], $post['itemIds'])) {
                $returnData[$key]['checked'] = true;
            }
        }

        if ($val['FreeSizeFlag']) {
            $returnData[$key]['isFree'] = true;    
            $returnData[$key]['sizeName'] = 'size'.$val['ItemID'];
        } else {
            $returnData[$key]['isFree'] = false;    

            // アイテムごとのサイズを取得
 	        $returnData[$key]['sizeData'] = castListboxSize(getSize($dbConnect, $val['SizeID'], 1), $post['size'.$val['ItemID']]);

            $returnData[$key]['sizeName'] = 'size'.$val['ItemID'];

        }

        // アイテムグループ（数量を同グループアイテムの合計で扱う）
        $returnData[$key]['isGroup'] = false;   // グループ設定されているか
        $returnData[$key]['groupId'] = '0';     // グループ設定されていない場合は0
        $returnData[$key]['limitNum'] = '0';    // 上限アイテム数
        if (isset($val['GroupID']) && !is_null($val['GroupID']) && $val['GroupID'] != 0) {
            $returnData[$key]['isGroup'] = true;   
            $returnData[$key]['groupId'] = $val['GroupID'];
            if (!isset($limitAry[$val['GroupID']])) {
                $limitAry[$val['GroupID']] = 0;
            }
            $limitAry[$val['GroupID']] = $limitAry[$val['GroupID']] + $val['ItemSelectNum'];
//            $returnData[$key]['limitNum'] = $val['ItemSelectNum'];
        }

        // アイテム数量   
        $returnData[$key]['dispNum'] = '';
        if (!isset($post['itemNumber'][$val['ItemID']]) && (!isset($post['hachuShinseiFlg']) || !$post['hachuShinseiFlg']) && isset($val['ItemSelectNum'])) {
            $returnData[$key]['dispNum'] = $val['ItemSelectNum'];
        } else if (isset($post['itemNumber'][$val['ItemID']])) {
            $returnData[$key]['dispNum'] = $post['itemNumber'][$val['ItemID']];
        } else {
//            $returnData[$key]['dispNum'] = 0;
            $returnData[$key]['dispNum'] = '';
        }

    }
    foreach ($limitAry as $laKey => $laVal) {
        foreach ($returnData as $rdKey => $rdVal) {
            if ($rdVal['groupId'] == $laKey)  {
                $returnData[$rdKey]['limitNum'] = $laVal;                
            }
        }        
    } 

    return $returnData;
}

// 対象スタッフの所属している貸与パターン選択コンボボックス作成
function getStaffPattern($dbConnect, $patternID) {

	// 初期化
	$result = array();

	$sql = " SELECT";
	$sql .= 	" PatternName";
	$sql .= " FROM";
	$sql .= 	" M_Pattern";
	$sql .= " WHERE";
	$sql .= 	" PatternID = '" . db_Escape($patternID) . "'";
	$sql .= " AND";
	$sql .= 	" Del = '" . DELETE_OFF . "'";
	$sql .= " GROUP BY";
	$sql .= 	" PatternName";

	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	return $result[0]['PatternName'];
}

?>
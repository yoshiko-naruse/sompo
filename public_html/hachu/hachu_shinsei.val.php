<?php
/*
 * エラー判定処理
 * hachu_shinsei.val.php
 *
 * create 2007/03/15 H.Osugi
 *
 *
 */

/*
 * 機能  ：エラー判定を行う
 * 引数  ：$dbConnect  => コネクションハンドラ
 *       ：$post       => POSTデータ
 * 戻り値：なし
 *
 * create 2007/03/15 H.Osugi
 *
 */
function validatePostData($dbConnect, $post) {

    // 初期化
    $hiddens = array();

    // すでに発注申請されていないか判定(初回申請時)
    if (($post['staffId'] != '' || $post['staffId'] === 0) 
        && in_array($post['appliReason'], array(APPLI_REASON_ORDER_COMMON, APPLI_REASON_ORDER_OFFICER, APPLI_REASON_ORDER_ISETAN, APPLI_REASON_ORDER_MATERNITY))) {

        $post['hachuShinseiFlg'] = true;

        $requestNo = '';
        if (isset($post['rirekiFlg']) && $post['rirekiFlg'] == 1) {
            $requestNo = $post['requestNo'];
        }

        $hiddenHtml = castHiddenError($post);

        $returnUrl = 'hachu/hachu_shinsei.php';

        //checkDuplicateStaffCode($dbConnect, $post['staffId'], $returnUrl, $hiddenHtml, $requestNo);
    }

    //---------------------------------------------------------
    // 郵便番号
    //---------------------------------------------------------
    // 郵便番号（前半）が存在しなければ初期化
    if (!isset($post['zip1'])) {
        $post['zip1'] = '';
    }

    // 郵便番号（前半）の判定
    $isZipError = false;
    $result = checkData(trim($post['zip1']), 'Digit', true, 3, 3);

    // エラーが発生したならば、エラーメッセージを取得
    switch ($result) {

        // 空白ならば
        case 'empty':
            $isZipError = true;
            $hiddens['errorId'][] = '011';
            break;

        // 半角以外の文字ならば
        case 'mode':
            $isZipError = true;
            $hiddens['errorId'][] = '012';
            break;

        // 指定文字数以外ならば
        case 'max':
        case 'min':
            $isZipError = true;
            $hiddens['errorId'][] = '012';
            break;

        default:
            break;

    }

    // エラーが発生したならば、エラーメッセージを取得
    if ($isZipError == false) {

        // 郵便番号（後半）が存在しなければ初期化
        if (!isset($post['zip2'])) {
            $post['zip2'] = '';
        }

        // 郵便番号（後半）の判定
        $result = checkData(trim($post['zip2']), 'Digit', true, 4, 4);

        // エラーが発生したならば、エラーメッセージを取得
        switch ($result) {

            // 空白ならば
            case 'empty':
                $hiddens['errorId'][] = '011';
                break;

            // 半角以外の文字ならば
            case 'mode':
                $hiddens['errorId'][] = '012';
                break;

            // 指定文字数以外ならば
            case 'max':
            case 'min':
                $hiddens['errorId'][] = '012';
                break;

            default:
                break;

        }
    }

    //---------------------------------------------------------
    // 住所
    //---------------------------------------------------------
    // 住所が存在しなければ初期化
    if (!isset($post['address'])) {
        $post['address'] = '';
    }

    // 住所の判定
    $result = checkData(trim($post['address']), 'Text', true, 240);

    // エラーが発生したならば、エラーメッセージを取得
    switch ($result) {

        // 空白ならば
        case 'empty':
            $hiddens['errorId'][] = '021';
            break;

        // 最大値超過ならば
        case 'max':
            $hiddens['errorId'][] = '022';
            break;

        default:
            break;

    }

    //---------------------------------------------------------
    // 出荷先名
    //---------------------------------------------------------
    // 出荷先名が存在しなければ初期化
    if (!isset($post['shipName'])) {
        $post['shipName'] = '';
    }

    // 出荷先名の判定
    $result = checkData(trim($post['shipName']), 'Text', true, 120);

    // エラーが発生したならば、エラーメッセージを取得
    switch ($result) {

        // 空白ならば
        case 'empty':
            $hiddens['errorId'][] = '031';
            break;

        // 最大値超過ならば
        case 'max':
            $hiddens['errorId'][] = '032';
            break;

        default:
            break;

    }

    //---------------------------------------------------------
    // 担当者
    //---------------------------------------------------------
    // ご担当者が存在しなければ初期化
    if (!isset($post['staffName'])) {
        $post['staffName'] = '';
    }

    // ご担当者の判定
    $result = checkData(trim($post['staffName']), 'Text', true, 40);

    // エラーが発生したならば、エラーメッセージを取得
    switch ($result) {

        // 空白ならば
        case 'empty':
            $hiddens['errorId'][] = '041';
            break;

        // 最大値超過ならば
        case 'max':
            $hiddens['errorId'][] = '042';
            break;

        default:
            break;

    }

    //---------------------------------------------------------
    // 電話番号
    //---------------------------------------------------------
    // 電話番号が存在しなければ初期化
    if (!isset($post['tel'])) {
        $post['tel'] = '';
    }

    // 電話番号の判定
    $result = checkData(trim($post['tel']), 'Tel', true, 15);

    // エラーが発生したならば、エラーメッセージを取得
    switch ($result) {

        // 空白ならば
        case 'empty':
            $hiddens['errorId'][] = '051';
            break;

        // 電話番号に利用可能な文字（数値とハイフン）以外の文字ならば
        case 'mode':
            $hiddens['errorId'][] = '052';
            break;

        // 最大値超過ならば
        case 'max':
            $hiddens['errorId'][] = '053';  
            break;

        default:
            break;

    }

    //---------------------------------------------------------
    // 出荷指定日
    //---------------------------------------------------------
    // 出荷指定日が存在しなければ初期化
    if (!isset($post['yoteiDay'])) {
        $post['yoteiDay'] = '';
    }

    // 出荷指定日の判定
    $result = checkData(trim($post['yoteiDay']), 'Date', true, 10);

    // エラーが発生したならば、エラーメッセージを取得
    switch ($result) {
        // 空白ならば
        case 'empty':
            break;
        // 正しい日付以外ならば
        case 'mode':
            $hiddens['errorId'][] = '111';				// 出荷指定日が正しい日付ではありません。
            break;
        default:
            if (strtotime(date("Y/m/d")) >= strtotime(trim($post['yoteiDay']))) {
              $hiddens['errorId'][] = '112';			// 出荷指定日に発注入力当日と過去日付は指定できません。
            } else {
                $week = date('w', strtotime(trim($post['yoteiDay'])));
                if ($week == 0 || $week == 6) {
                    $hiddens['errorId'][] = '113';		// 出荷指定日に土曜日と日曜日は指定できません。
                }
            }

        break;
    }

	//---------------------------------------------------------
	// メモ
	//---------------------------------------------------------
    // メモが存在しなければ初期化
    if (!isset($post['memo'])) {
        $post['memo'] = '';
    }

    // メモの判定
    $result = checkData(trim($post['memo']), 'Text', false, 128);

    // エラーが発生したならば、エラーメッセージを取得
    switch ($result) {

        // 最大値超過ならば
        case 'max':
            $hiddens['errorId'][] = '061';
            break;

        default:
            break;

    }

//	// Modify by Y.Furukawa at 2020/05/12 個別発注以外はレンタル開始日チェック
//	if ($post['appliReason'] <> APPLI_REASON_ORDER_PERSONAL) { 
//
//    	// レンタル開始日が存在しなければ初期化
//    	if (!isset($post['rentalStartDay'])) {
//    	    $post['rentalStartDay'] = '';
//    	}
//
//    	// レンタル開始日の判定
//    	$minDateTime = mktime(0,0,0,date('m'), date('d'), date('Y'));
//    	$result = checkData(trim($post['rentalStartDay']), 'Date', true, '', date('Y', $minDateTime).'/'.date('m', $minDateTime).'/'.date('d', $minDateTime));
//
//    	// エラーが発生したならば、エラーメッセージを取得
//    	switch ($result) {
//
//    	    case 'empty':
//    	        $hiddens['errorId'][] = '100';
//    	        break;
//
//    	    // 存在しない日付なら
//    	    case 'mode':
//    	        $hiddens['errorId'][] = '101';
//    	        break;
//
//    	    // 今日以前なら
//    	    case 'min':
//    	        $hiddens['errorId'][] = '102';
//    	        break;
//
//    	    default:
//    	        break;
//    	
//    	}
//
//	}

	//追加 uesugi 081119
	$isItemIdError = false;

	for($i=0;$i<count($post['itemIds']);$i++){
		$post['itemIds'][$i] = (int)$post['itemIds'][$i];
		if($post['itemIds'][$i] <= 0){
			$isItemIdError = true;
		}
	}
    // ユニフォーム選択の判定
    $countItemIds = count($post['itemIds']);

    // １つも選択されていない場合
//    if ($countItemIds <= 0) {
    if ($isItemIdError == true || $countItemIds <= 0) {
        $hiddens['errorId'][] = '071';
    } else {    // 選択されたアイテムがあればサイズをチェック
    
        // サイズのエラー判定フラグ
        $isSizeError = false;
        // サイズの判定
        foreach ($post['itemIds'] as $key => $selectedID) {
            // サイズ項目が存在しなければ初期化
            if (!isset($post['size'.$selectedID])) {
                $post['size'.$selectedID] = '';
            }

            if (isset($post['itemNumber'][$selectedID]) && $post['itemNumber'][$selectedID] != '' && $post['itemNumber'][$selectedID] > 0) {

                // チェックされたアイテムに対して、展開されているサイズを取得
                $sizeDataAry = getSizeByItem($dbConnect, $selectedID, 0);
    
                // 判定
                if ($post['size'.$selectedID] != '' && !is_null($post['size'.$selectedID]) ) {
                    $result = array_key_exists(trim($post['size'.$selectedID]), $sizeDataAry);
    
                    // 選択されていなければ、エラーメッセージを取得
                    if (!$result) {
                        $isSizeError = true;
                    }
                } else {
                    $isSizeError = true;
                }
            }
        }

        if ($isSizeError) {
            $hiddens['errorId'][] = '081';
        }

    }

    // 初回申請時のグループをチェック
    $groupIdAry = array();
    $isSetGroup = false;
    for ($i=0; $i<$countItemIds;$i++) {     // チェックされたアイテムをループ
        if (isset($post['groupId'][$post['itemIds'][$i]]) && $post['groupId'][$post['itemIds'][$i]] != 0) {
            // 初期化
            if (!isset($groupIdAry[$post['groupId'][$post['itemIds'][$i]]])) {
                $groupIdAry[$post['groupId'][$post['itemIds'][$i]]] = 0;
            }
            // 同グループIDのアイテム個数を集計
            if (!isset($post['itemNumber'][$post['itemIds'][$i]]) || $post['itemNumber'][$post['itemIds'][$i]] == '') {
                $post['itemNumber'][$post['itemIds'][$i]] = 0;      
            } 
            $groupIdAry[$post['groupId'][$post['itemIds'][$i]]] = (int)$groupIdAry[$post['groupId'][$post['itemIds'][$i]]] + (int)trim($post['itemNumber'][$post['itemIds'][$i]]);

            $isSetGroup = true;
        }
    } 

    // 数量
    for ($i=0; $i<$countItemIds;$i++) {     // チェックされたアイテムをループ


        $result = checkData((string)$post['itemNumber'][$post['itemIds'][$i]], 'Digit', true, 2);

        switch ($result) {
    
            // 空白ならば
            case 'empty':
                // グループ設定されたアイテムの場合は空白を許可
                if ($post['groupId'][$post['itemIds'][$i]] == 0) {
                    $hiddens['errorId'][] = '091';
                    $isSizeError = true;
                }
                break;
                
            // 半角以外の文字ならば
            case 'mode':
                $hiddens['errorId'][] = '093';
                $isSizeError = true;
                break;
    
            // 最大値超過ならば
            case 'max':
                $hiddens['errorId'][] = '093';
                $isSizeError = true;
                break;
    
            default:
                // グループ設定されたアイテムの場合は０以下を許可
                if (trim($post['itemNumber'][$post['itemIds'][$i]]) <= 0 && $post['groupId'][$post['itemIds'][$i]] == 0) {
                    $hiddens['errorId'][] = '094';
                    $isSizeError = true;
                }
                break;
    
        }

        if ($isSizeError == true) {
            break;
        }
    }


    // エラーが存在したならば、エラー画面に遷移
    if (count($hiddens['errorId']) > 0) {
        $hiddens['errorName']    = 'hachuShinsei';
        $hiddens['menuName']     = 'isMenuOrder';
        $hiddens['appliReason']  = $post['appliReason'];

        if (isset($post['rirekiFlg']) && $post['rirekiFlg'] == 1) {
            $hiddens['menuName']  = 'isMenuHistory';
        }

        $hiddens['returnUrl'] = 'hachu/hachu_shinsei.php';
        $errorUrl             = HOME_URL . 'error.php';

        $post['hachuShinseiFlg'] = true;

        // POST値をHTMLエンティティ
        $post = castHtmlEntity($post); 

        $hiddenHtml = castHiddenError($post);

        $hiddens = array_merge($hiddens, $hiddenHtml);

        redirectPost($errorUrl, $hiddens);

    }

}

?>
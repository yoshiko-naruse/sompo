<?php
/*
 * エラー判定処理
 * koukan_shinsei.val.php
 *
 * create 2007/03/20 H.Osugi
 *
 *
 */

/*
 * 機能  ：エラー判定を行う
 * 引数  ：$dbConnect  => コネクションハンドラ
 *       ：$post       => POSTデータ
 *       ：$sizeData1 => サイズ1
 *       ：$sizeData2 => サイズ2
 * 戻り値：なし
 *
 * create 2007/03/20 H.Osugi
 *
 */
function validatePostData($dbConnect, $post) {

	// 初期化
	$hiddens = array();

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
			$hiddens['errorId'][] = '001';
			break;
			
		// 半角以外の文字ならば
		case 'mode':
			$isZipError = true;
			$hiddens['errorId'][] = '002';
			break;

		// 指定文字数以外ならば
		case 'max':
		case 'min':
			$isZipError = true;
			$hiddens['errorId'][] = '002';
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

		switch ($result) {
	
			// 空白ならば
			case 'empty':
				$hiddens['errorId'][] = '001';
				break;
				
			// 半角以外の文字ならば
			case 'mode':
				$hiddens['errorId'][] = '002';
				break;
	
			// 指定文字数以外ならば
			case 'max':
			case 'min':
				$hiddens['errorId'][] = '002';
				break;
	
			default:
				break;
	
		}
	}

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
			$hiddens['errorId'][] = '011';
			break;
			
		// 最大値超過ならば
		case 'max':
			$hiddens['errorId'][] = '012';
			break;

		default:
			break;

	}

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
			$hiddens['errorId'][] = '021';
			break;
			
		// 最大値超過ならば
		case 'max':
			$hiddens['errorId'][] = '022';
			break;

		default:
			break;

	}

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
			$hiddens['errorId'][] = '031';
			break;
			
		// 最大値超過ならば
		case 'max':
			$hiddens['errorId'][] = '032';
			break;

		default:
			break;

	}

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
			$hiddens['errorId'][] = '041';
			break;

		// 電話番号に利用可能な文字（数値とハイフン）以外の文字ならば
		case 'mode':
			$hiddens['errorId'][] = '042';
			break;

		// 最大値超過ならば
		case 'max':
			$hiddens['errorId'][] = '043';
			break;

		default:
			break;

	}

	// メモが存在しなければ初期化
	if (!isset($post['memo'])) {
		$post['memo'] = '';
	}

	// メモの判定
	$result = checkData(trim($post['memo']), 'Text', true, 128);

	// エラーが発生したならば、エラーメッセージを取得
	switch ($result) {

		// 空白ならば
		case 'empty':
//			if ($post['appliReason'] == APPLI_REASON_EXCHANGE_INFERIORITY) {	// 不良品交換の場合はメモ欄必須　2008/07/24
			$hiddens['errorId'][] = '052';
//			}

			break;

		// 最大値超過ならば
		case 'max':
			$hiddens['errorId'][] = '051';
			break;

		default:
			break;

	}

	// ユニフォーム選択の判定
	$countOrderIds = count($post['orderDetIds']);

    // １つも選択されていない場合
    if ($countOrderIds <= 0) {
        $hiddens['errorId'][] = '071';
    } else {    // 選択されたアイテムがあればサイズをチェック

        // サイズのエラー判定フラグ
        $isSizeError = false;

        // -----------------------------------------------------------------//
        // サイズ交換のみ、交換後サイズ選択チェックを行う。
        // サイズ交換以外の交換は、サイズを変更可能にすると、
        // 同一アイテム同一サイズ保持の考え方が成立しなくなるため。
        // -----------------------------------------------------------------//
        //if ($post['appliReason'] == APPLI_REASON_EXCHANGE_SIZE) {

            // サイズの判定
            foreach ($post['orderDetIds'] as $key => $selectedID) {

                if ((int)$selectedID) { 
		    	    // チェックされたアイテムに対して、展開されているサイズを取得
                    $sizeDataAry = getSizeByOrderDetId($dbConnect, $selectedID, 0);

                    // サイズのエラー判定フラグ
                    $isSizeError = false;
            
                    // サイズ項目が存在しなければ初期化
                    if (!isset($post['size'.$selectedID])) {
                        $post['size'.$selectedID] = '';
                    }
    
                    // 判定
                    $result = array_key_exists(trim($post['size'][$selectedID]), $sizeDataAry);
                    // 選択されていなければ、エラーメッセージを取得
                    if (!$result) {
                        $isSizeError = true;
                    }
		    	} else {
                    $isSizeError = true;
		    	}
            }

            if ($isSizeError) {
                $hiddens['errorId'][] = '071';		// サイズが選択されていないアイテムがあります。
            }
        //}
    }


	// １つも選択されていない場合
	if ($countOrderIds <= 0) {
		$hiddens['errorId'][] = '061';
	}

	// サイズ交換の場合のみ同サイズを選択されていないか判定
	if (($post['appliReason'] == APPLI_REASON_EXCHANGE_FIRST || $post['appliReason'] == APPLI_REASON_EXCHANGE_SIZE) && $isSizeError == false && $countOrderIds > 0) {

		$orderDetIds = '';
		if(is_array($post['orderDetIds'])) {
			$check = true;
			foreach($post['orderDetIds'] as $key => $value) {
				if (!(int)$value) {
					$check = false;
				}
			}
			if ($check) { 
				$orderDetIds = implode(', ', $post['orderDetIds']);

				//furukawa
//var_dump("itemIdselect:" . $post['itemId']);

			}
		}
		if ($check) {

    		// スタッフコードの一覧を取得する
    		$sql  = "";
    		$sql .= " SELECT";
    		$sql .= 	" OrderDetID,";
    		$sql .= 	" ItemID,";
    		$sql .= 	" Size";
    		$sql .= " FROM";
    		$sql .= 	" T_Order_Details";
    		$sql .= " WHERE";
    		$sql .= 	" OrderDetID IN (" . db_Escape($orderDetIds) . ")";
    		$sql .= " AND";
    		$sql .= 	" Del = 0";
    		$sql .= " ORDER BY";
    		$sql .= 	" ItemID";


    		$result = db_Read($dbConnect, $sql);
    
		    $itemIdWk = '';
		    $itemIds = array();
				$j = 0;
			$resultsTmp = array();
			$resultItem = array();
			$isSizeError = false;

    		$countOrderDet = count($result);
    		for ($i=0; $i<$countOrderDet; $i++) {

                $sizeDataAry = getSizeByOrderDetId($dbConnect, $result[$i]['OrderDetID'], 0);

				// サイズ交換の場合は同じサイズの交換はできません
    			if ($sizeDataAry[$post['size'][$result[$i]['OrderDetID']]] == $result[$i]['Size']) {
    				$hiddens['errorId'][] = '081';
    				break;
    			}

                if ( !array_key_exists($result[$i]['ItemID'], $resultsTmp) ) {
                    // オブジェクトにキーsales_dateが含まれていない:
                    // 集計結果の初期値を生成する。
                    $resultItem = array(
                                    'ItemID' => $result[$i]['ItemID'],
                                    'size'   => $result[$i]['Size'],
                                    'total'  => 1
                                   );

                    // 生成した初期値をキーsales_dateに関連付けてオブジェクトに格納する。
                    $resultsTmp[$result[$i]['ItemID']] = $resultItem;
                }
                else 
                {
                    if($resultsTmp[$result[$i]['ItemID']]['size'] != $size)
                    {
                        $isSizeError = true;
                        break;
                    }

                    $resultsTmp[$result[$i]['ItemID']]['total'] += 1;
                }
    		}

			if ($isSizeError == false) {

                foreach ($resultsTmp as $key => $value) {
                
	                $isShip = false;
					$koukanMaeItem = array();
					$koukanMaeItemCount = 0;

               		//同一アイテムは全て同じサイズをご指定ください。// 対象の商品は未出荷の商品が
   					if (isset($value['ItemID'])) {

   					    // 交換前のアイテム毎のサイズ、数量抽出
			    		$koukanMaeItem = getSizeKoukanUnshipped($dbConnect, $post['staffId'], $appliReason, $value['ItemID']);

						if (count($koukanMaeItem) > 0) {

							for ($j=0; $j < count($koukanMaeItem); $j++) {

								// 交換前のアイテムに未出荷のものがあればエラーにする。
								if ($koukanMaeItem[$j]['Status'] <= STATUS_ORDER) {
									$isShip = true;
								}
								// 交換前アイテムカウント集計（アイテム毎）
								$koukanMaeItemCount = $koukanMaeItemCount + $koukanMaeItem[$j]['ItemCount']++;
							}
						}

						// 貸与アイテムで未出荷のアイテムが含まれる場合、エラー
						if ($isShip == true) {
							$hiddens['errorId'][] = '083';
						}

						//// 貸与アイテムで未出荷のアイテムが含まれる場合（且つ）貸与アイテム数と選択したアイテム数が不一致の場合はエラー
						//if ($isShip == false) {
						//	// 同一アイテムは全て同じサイズをご指定ください。
						//	if ($koukanMaeItemCount != $value['total']) {
						//		$hiddens['errorId'][] = '082';
						//	}
						//}
   					}
                }
            }

		} else {
				$hiddens['errorId'][] = '081';
		}
	}

	// エラーが存在したならば、エラー画面に遷移
	if (count($hiddens['errorId']) > 0) {
		$hiddens['errorName'] = 'koukanShinsei';
		$hiddens['menuName']  = 'isMenuExchange';
		$hiddens['returnUrl'] = 'koukan/koukan_shinsei.php';
		$errorUrl             = HOME_URL . 'error.php';

		$post['koukanShinseiFlg'] = '1';

		// POST値をHTMLエンティティ
		$post = castHtmlEntity($post); 

		// hidden値の成型
		$countOrderDetIds = count($post['orderDetIds']);
		for ($i=0; $i<$countOrderDetIds; $i++) {
			$post['orderDetIds[' . $i . ']'] = $post['orderDetIds'][$i];
			$post['size[' . $post['orderDetIds'][$i] . ']'] = $post['size'][$post['orderDetIds'][$i]];
		}
		$notArrowKeys = array('orderDetIds' , 'size', 'sizeType');
		$hiddenHtml = castHiddenError($post, $notArrowKeys);

		$hiddens = array_merge($hiddens, $hiddenHtml);

		redirectPost($errorUrl, $hiddens);

	}

}

?>
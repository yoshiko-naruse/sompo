<?php
/*
 * エラー判定処理
 * henpin_shinsei.val.php
 *
 * create 2007/03/22 H.Osugi
 *
 *
 */

/*
 * 機能  ：エラー判定を行う
 * 引数  ：$post       => POSTデータ
 * 戻り値：なし
 *
 * create 2007/03/22 H.Osugi
 *
 */
function validatePostData($post) {

	// 初期化
	$hiddens = array();

	// メモが存在しなければ初期化
	if (!isset($post['memo'])) {
		$post['memo'] = '';
	}

	// その他返却の場合
	if ($post['appliReason'] == APPLI_REASON_RETURN_OTHER) {

		// スタッフコードの判定
		$result = checkData(trim($post['memo']), 'Text', true, 128);
	
		// エラーが発生したならば、エラーメッセージを取得
		switch ($result) {
	
			// 空白ならば
			case 'empty':
				$hiddens['errorId'][] = '001';
				break;
				
			// 最大値超過ならば
			case 'max':
				$hiddens['errorId'][] = '002';
				break;
	
		}

	}


	// 退店返却の場合
	else {

		// スタッフコードの判定
		$result = checkData(trim($post['memo']), 'Text', false, 128);
	
		// エラーが発生したならば、エラーメッセージを取得
		switch ($result) {
	
			// 最大値超過ならば
			case 'max':
				$hiddens['errorId'][] = '002';
				break;
	

			default:

				// 選択されていないユニフォームが存在するか判定
				$countOrderDetId = count($post['orderDetIds']);
				if ($countOrderDetId <= 0) {
					$hiddens['errorId'][] = '003';
					break;
				} else {
					for ($i=0; $i<$countOrderDetId; $i++) {
						if ((!isset($post['returnChk'][$post['orderDetIds'][$i]]) && !isset($post['lostChk'][$post['orderDetIds'][$i]])) || !(int)$post['orderDetIds'][$i]) {
							$hiddens['errorId'][] = '003';
							break;
						}
					}
				}
				break;
		}

/*----------------------------------------------------------
		// 退職返却の場合、レンタル終了日をチェック
		if ($post['appliReason'] == APPLI_REASON_RETURN_RETIRE) {

			// レンタル終了日が存在しなければ初期化
			if (!isset($post['rentalEndDay'])) {
				$post['rentalEndDay'] = '';
			}

		    // レンタル終了日の判定
		    $minDateTime = mktime(0,0,0,date('m'), date('d'), date('Y'));
		    $result = checkData(trim($post['rentalEndDay']), 'Date', true, '', date('Y', $minDateTime).'/'.date('m', $minDateTime).'/'.date('d', $minDateTime));

		    // エラーが発生したならば、エラーメッセージを取得
		    switch ($result) {

		        case 'empty':
		            $hiddens['errorId'][] = '100';
		            break;

		        // 存在しない日付なら
		        case 'mode':
		            $hiddens['errorId'][] = '101';
		            break;

		        // 今日以前なら
		        case 'min':
		            $hiddens['errorId'][] = '102';
		            break;

		        default:
		            break;

		    }
	    }
----------------------------------------------------------*/
	}

	// 返却または紛失がひとつでも選択されているか判定
	$count = 0;
	$countOrderDetId = count($post['orderDetIds']);
	for ($i=0; $i<$countOrderDetId; $i++) {
		if ((isset($post['returnChk'][$post['orderDetIds'][$i]]) && $post['returnChk'][$post['orderDetIds'][$i]] == '1')
		|| (isset($post['lostChk'][$post['orderDetIds'][$i]]) && $post['lostChk'][$post['orderDetIds'][$i]] == '1')) {
			break;
		}
		$count++;
	}
	if ($countOrderDetId == $count) {
		$hiddens['errorId'][] = '004';
	}

	// エラーが存在したならば、エラー画面に遷移
	if (count($hiddens['errorId']) > 0) {
		$hiddens['errorName'] = 'henpinShinsei';
		$hiddens['menuName']  = 'isMenuReturn';
		$hiddens['returnUrl'] = 'henpin/henpin_shinsei.php';
		$errorUrl             = HOME_URL . 'error.php';

		$post['henpinShinseiFlg'] = '1';

		// POST値をHTMLエンティティ
		$post = castHtmlEntity($post); 

		// hidden値の成型
		$countOrderDetIds = count($post['orderDetIds']);
		for ($i=0; $i<$countOrderDetIds; $i++) {
			$post['orderDetIds[' . $i . ']'] = $post['orderDetIds'][$i];
			if (isset($post['returnChk'][$post['orderDetIds'][$i]]) && $post['returnChk'][$post['orderDetIds'][$i]] == 1) {
				$post['returnChk[' . $post['orderDetIds'][$i] . ']'] = trim($post['returnChk'][$post['orderDetIds'][$i]]);
			}
			if (isset($post['lostChk'][$post['orderDetIds'][$i]]) && $post['lostChk'][$post['orderDetIds'][$i]] == 1) {
				$post['lostChk[' . $post['orderDetIds'][$i] . ']'] = trim($post['lostChk'][$post['orderDetIds'][$i]]);
			}
			if (isset($post['brokenChk'][$post['orderDetIds'][$i]]) && $post['brokenChk'][$post['orderDetIds'][$i]] == 1) {
				$post['brokenChk[' . $post['orderDetIds'][$i] . ']'] = trim($post['brokenChk'][$post['orderDetIds'][$i]]);
			}
		}
		$notArrowKeys = array('orderDetIds', 'returnChk', 'lostChk', 'brokenChk');
		$hiddenHtml = castHiddenError($post, $notArrowKeys);

		$hiddens = array_merge($hiddens, $hiddenHtml);

		redirectPost($errorUrl, $hiddens);

	}

}

?>
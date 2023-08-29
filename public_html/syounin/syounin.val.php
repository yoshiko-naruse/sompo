<?php
/*
 * エラー判定処理
 * syounin.val.php
 *
 * create 2007/04/23 H.Osugi
 *
 *
 */

/*
 * 機能  ：エラー判定を行う
 * 引数  ：$post       => POSTデータ
 * 戻り値：なし
 *
 * create 2007/04/23 H.Osugi
 *
 */
function validatePostData($post) {

	// 初期化
	$hiddens = array();

	// 承認/否認が選択されているか判定
	$isEmpty = true;
	$countOrderId = count($post['orderIds']);
	for ($i=0; $i<$countOrderId; $i++) {

		if (isset($post['acceptationY'][$post['orderIds'][$i]]) && $post['acceptationY'][$post['orderIds'][$i]] != '') {
			$isEmpty = false;
			break;
		}
		if (isset($post['acceptationN'][$post['orderIds'][$i]]) && $post['acceptationN'][$post['orderIds'][$i]] != '') {
			$isEmpty = false;
			break;
		}
	}

	if ($isEmpty == true) {
		$hiddens['errorId'][] = '001';
	}
	else {

		// 理由
		$isError = false;
		for ($i=0; $i<$countOrderId; $i++) {

			// 理由が存在しなければ初期化
			if (!isset($post['reason'][$post['orderIds'][$i]])) {
				$post['reason'][$post['orderIds'][$i]] = '';
			}

			// 理由の判定
			$result = checkData(trim($post['reason'][$post['orderIds'][$i]]), 'Text', false, 60);

			// エラーが発生したならば、エラーメッセージを取得
			switch ($result) {

				// 全角30文字超過ならば
				case 'max':
					$hiddens['errorId'][] = '011';
					$isError = true;
					break;
		
				default:
					break;
		
			}

			if ($isError == true) {
				break;
			}

		}
	
	}

	// エラーが存在したならば、エラー画面に遷移
	if (count($hiddens['errorId']) > 0) {

		$hiddens['errorName'] = 'syounin';
		$hiddens['menuName']  = 'isMenuAcceptation';
		$hiddens['returnUrl'] = 'syounin/syounin.php';
		$errorUrl             = HOME_URL . 'error.php';

		$post['errorFlg'] = '1';

		// POST値をHTMLエンティティ
		$post = castHtmlEntity($post); 

		$hiddenHtml = castHiddenError($post);

		$hiddens = array_merge($hiddens, $hiddenHtml);

		redirectPost($errorUrl, $hiddens);

	}

}

?>
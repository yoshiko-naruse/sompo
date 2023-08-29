<?php
/*
 * 返却で選択された商品のorderDetIDを成型
 * castOrderDetId.php
 *
 * create 2007/03/22 H.Osugi
 *
 */

/*
 * 選択された商品のorderDetIDを成型する
 * 引数  ：$post        => POST値
 * 戻り値：$orderDetIds => 選択された商品のorderDetID(array)
 *
 * create 2007/03/22 H.Osugi
 *
 */
function castOrderDetId($post) {

	// 初期化
	$orderDetIds = array();

	// orderDetIDが無ければそのまま終了
	if (count($post['orderDetIds']) <= 0) {
		return $orderDetIds;
	}

	// 返却・紛失が選択されたユニフォームのorderDetIDのみを取得する
	$countOrderDetId = count($post['orderDetIds']);
	$j = 0;
	for ($i=0; $i<$countOrderDetId; $i++) {
		if(isset($post['orderDetIds'][$i])) {
			if (isset($post['returnChk'][$post['orderDetIds'][$i]]) || isset($post['lostChk'][$post['orderDetIds'][$i]])) {
				$orderDetIds[$j] = trim($post['orderDetIds'][$i]);
				$j++;
			}
		}
	}

	return $orderDetIds;

}

?>
<?php
/*
 * ページング情報セッティングモジュール
 * setPaging.php
 *
 * create 2007/03/28 H.Osugi
 *
 */

/*
 * ページング情報をセッティングする
 * 引数  ：$nowPage  => 現在のページ数
 *       ：$perCount => １ページあたりの表示件数
 *       ：$allCount => 表示するデータの全件数
 * 戻り値：ページング情報
 *
 * create 2007/03/28 H.Osugi
 *
 */
function setPaging($nowPage, $perCount, $allCount) {

	if ($nowPage == '') {
		$nowPage = 1;
	}

	// 初期化
	$paging = array();
	$paging['isPaging']    = false;
	$paging['isPrev']      = false;
	$paging['isNext']      = false;
	$paging['prev']        = '';
	$paging['next']        = '';
	$paging['nowPage']     = '';

	// 1ページあたりの件数の設定がおかしい時
	if ($perCount <= 0) {
		return $paging;
	}

	// ページングする件数に達していない時
	if ($allCount <= $perCount) {
		return $paging;
	}

	// ページング機能の表示設定
	$paging['isPaging'] = true;

	// 現在のページ
	$paging['nowPage']  = $nowPage;

	// 前のページのボタン表示設定
	if ($nowPage > 1) {
		$paging['isPrev'] = true;
		$paging['prev']  = $nowPage - 1;
	}

	// 前のページのボタン表示設定
	if ($nowPage >= 1 && $nowPage < ceil($allCount / $perCount)) {
		$paging['isNext'] = true;
		$paging['next']  = $nowPage + 1;
	}

	return $paging;

}

?>
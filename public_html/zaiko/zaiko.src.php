<?php
/*
 * 在庫照会画面
 * zaiko.src.php
 *
 * create 2007/05/22 H.Osugi
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

// 初期設定
$isMenuAdmin = true;	// 管理機能のメニューをアクティブに

// 変数の初期化 ここから ******************************************************
$items       = array();
// 変数の初期化 ここまで ******************************************************

// 管理者権限が無ければトップに強制遷移
if ($isLevelAdmin == false) {

	$returnUrl             = HOME_URL . 'top.php';
	
	// TOP画面に強制遷移
	redirectPost($returnUrl, $hiddens);

} 

// 表示する在庫一覧を取得
$stocks = getStock($dbConnect);
// 表示する値の成型 ここまで +++++++++++++++++++++++++++++++++++++++++++++++++++

/*
 * 在庫情報を取得する
 * 引数  ：$dbConnect      => コネクションハンドラ
 * 戻り値：$stocks         => 在庫情報
 *
 * create 2007/05/22 H.Osugi
 *
 */
function getStock($dbConnect) {

	// 在庫情報を取得する
	$sql  = "";
	$sql .= " SELECT";
	$sql .= 	" msc.Size,";
	$sql .= 	" mi.ItemName,";
	$sql .= 	" mi.ItemNo,";
	$sql .= 	" ts.HikiateQty,";			// 新品・論理在庫
	$sql .= 	" ts.JitsuStock,";			// 新品・実在庫
	$sql .= 	" ts.OldHikiateQty,";		// 中古（公益社）・論理在庫
	$sql .= 	" ts.OldJitsuStock";		// 中古（公益社）・実在庫
	$sql .= " FROM";
	$sql .= 	" M_StockCtrl msc";
	$sql .= " INNER JOIN";
	$sql .= 	" M_Item mi";
	$sql .= " ON";
	$sql .= 	" msc.ItemNo = mi.ItemNo";
	$sql .= " AND";
	$sql .= 	" mi.Del = " . DELETE_OFF;
	$sql .= " LEFT JOIN";
	$sql .= 	" T_Stock ts";
	$sql .= " ON";
	$sql .= 	" msc.StockCD = ts.StockCD";
	$sql .= " AND";
	$sql .= 	" ts.Del = " . DELETE_OFF;
	$sql .= " WHERE";
	$sql .= 	" msc.Del = " . DELETE_OFF;
	$sql .= " ORDER BY";
	$sql .= 	" msc.StockCD ASC";
//var_dump($sql);
	$result = db_Read($dbConnect, $sql);

	// 検索結果が0件の場合
	if (count($result) <= 0) {
		$result = array();
	 	return $result;
	}

	$resultCount = count($result);
	$itemNo = '';
	$stocks = array();
	$j = -1;
	$k = 0;

	for ($i=0; $i<$resultCount; $i++) {

		if ($itemNo != $result[$i]['ItemNo']) {

			$j++;

			$stocks[$j]['ItemNo']   = castHtmlEntity($result[$i]['ItemNo']);
			$stocks[$j]['ItemName'] = castHtmlEntity($result[$i]['ItemName']);
			
			$k = 0;

		}
		$itemNo = $result[$i]['ItemNo'];

		// サイズ
		$stocks[$j]['sizes'][$k]['Size']       = castHtmlEntity($result[$i]['Size']);

		$stocks[$j]['sizes'][$k]['HikiateQty'] = 0;
		//if ($result[$i]['HikiateQty'] != '') {
		if ($result[$i]['HikiateQty'] != '' && $result[$i]['HikiateQty'] > 0) {
			$stocks[$j]['sizes'][$k]['HikiateQty'] = $result[$i]['HikiateQty'];
		}
		$stocks[$j]['sizes'][$k]['OldHikiateQty'] = 0;
		//if ($result[$i]['OldHikiateQty'] != '') {
		if ($result[$i]['OldHikiateQty'] != '' && $result[$i]['OldHikiateQty'] > 0) {
			$stocks[$j]['sizes'][$k]['OldHikiateQty'] = $result[$i]['OldHikiateQty'];
		}

		$stocks[$j]['sizes'][$k]['isEmptySize'] = false;

		$k++;

	}

	$countStocks = count($stocks);
	//var_dump("countStocks:" . $countStocks);
	for ($i=0; $i<$countStocks; $i++) {
		$countSizes = count($stocks[$i]['sizes']);
		if ($countSizes <= 12) {
			$stocks[$i]['multiline']  = false;
            $stocks[$i]['2line']      = false;
            $stocks[$i]['3line']      = false;
            $stocks[$i]['4line']      = false;
            $stocks[$i]['5line']      = false;
            $stocks[$i]['6line']      = false;
            $stocks[$i]['7line']      = false;
            $stocks[$i]['8line']      = false;
            $stocks[$i]['rowspan']    = 2;
			for ($j=0; $j<$countSizes; $j++) {
				$stocks[$i]['sizeline1'][$j]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
				$stocks[$i]['sizeline1'][$j]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
				$stocks[$i]['sizeline1'][$j]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
				$stocks[$i]['sizeline1'][$j]['isEmptySize'] = false;
			}
			for ($j=$countSizes; $j<12; $j++) {
				$stocks[$i]['sizeline1'][$j]['isEmptySize'] = true;
			}
		} elseif ($countSizes <= 24) {
//		var_dump("aaaaaaaaaaaaaaaaaaaaaaaaaa");
			$stocks[$i]['multiline']  = true;
            $stocks[$i]['2line']      = true;
            $stocks[$i]['3line']      = false;
            $stocks[$i]['4line']      = false;
            $stocks[$i]['5line']      = false;
            $stocks[$i]['6line']      = false;
            $stocks[$i]['7line']      = false;
            $stocks[$i]['8line']      = false;
            $stocks[$i]['rowspan']    = 4;
			for ($j=0; $j<12; $j++) {
				$stocks[$i]['sizeline1'][$j]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
				$stocks[$i]['sizeline1'][$j]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
				$stocks[$i]['sizeline1'][$j]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
				$stocks[$i]['sizeline1'][$j]['isEmptySize'] = false;
			}
			for ($j=12; $j<$countSizes; $j++) {
				$stocks[$i]['sizeline2'][$j-12]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
				$stocks[$i]['sizeline2'][$j-12]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
				$stocks[$i]['sizeline2'][$j-12]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
				$stocks[$i]['sizeline2'][$j-12]['isEmptySize'] = false;
			}
			for ($j=$countSizes-12; $j<12; $j++) {
				$stocks[$i]['sizeline2'][$j]['isEmptySize'] = true;
			}
        } elseif ($countSizes <= 36) {
            $stocks[$i]['multiline']  = true;
            $stocks[$i]['2line']      = true;
            $stocks[$i]['3line']      = true;
            $stocks[$i]['4line']      = false;
            $stocks[$i]['5line']      = false;
            $stocks[$i]['6line']      = false;
            $stocks[$i]['7line']      = false;
            $stocks[$i]['8line']      = false;
            $stocks[$i]['rowspan']    = 6;
            for ($j=0; $j<12; $j++) {
                $stocks[$i]['sizeline1'][$j]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline1'][$j]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline1'][$j]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline1'][$j]['isEmptySize'] = false;
            }
            for ($j=12; $j<24; $j++) {
                $stocks[$i]['sizeline2'][$j-12]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline2'][$j-12]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline2'][$j-12]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline2'][$j-12]['isEmptySize'] = false;
            }
            for ($j=24; $j<$countSizes; $j++) {
                $stocks[$i]['sizeline3'][$j-24]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline3'][$j-24]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline3'][$j-24]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline3'][$j-24]['isEmptySize'] = false;
            }
            for ($j=$countSizes-24; $j<12; $j++) {
                $stocks[$i]['sizeline3'][$j]['isEmptySize'] = true;
            }
        } elseif ($countSizes <= 48) {
            $stocks[$i]['multiline']  = true;
            $stocks[$i]['2line']      = true;
            $stocks[$i]['3line']      = true;
            $stocks[$i]['4line']      = true;
            $stocks[$i]['5line']      = false;
            $stocks[$i]['6line']      = false;
            $stocks[$i]['7line']      = false;
            $stocks[$i]['8line']      = false;
            $stocks[$i]['rowspan']    = 8;
            for ($j=0; $j<12; $j++) {
                $stocks[$i]['sizeline1'][$j]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline1'][$j]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline1'][$j]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline1'][$j]['isEmptySize'] = false;
            }
            for ($j=12; $j<24; $j++) {
                $stocks[$i]['sizeline2'][$j-12]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline2'][$j-12]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline2'][$j-12]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline2'][$j-12]['isEmptySize'] = false;
            }
            for ($j=24; $j<36; $j++) {
                $stocks[$i]['sizeline3'][$j-24]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline3'][$j-24]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline3'][$j-24]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline3'][$j-24]['isEmptySize'] = false;
            }
            for ($j=36; $j<$countSizes; $j++) {
                $stocks[$i]['sizeline4'][$j-36]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline4'][$j-36]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline4'][$j-36]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline4'][$j-36]['isEmptySize'] = false;
            }
            for ($j=$countSizes-36; $j<12; $j++) {
                $stocks[$i]['sizeline4'][$j]['isEmptySize'] = true;
            }
        } elseif ($countSizes <= 60) {
            $stocks[$i]['multiline']  = true;
            $stocks[$i]['2line']      = true;
            $stocks[$i]['3line']      = true;
            $stocks[$i]['4line']      = true;
            $stocks[$i]['5line']      = true;
            $stocks[$i]['6line']      = false;
            $stocks[$i]['7line']      = false;
            $stocks[$i]['8line']      = false;
            $stocks[$i]['rowspan']    = 10;
            for ($j=0; $j<12; $j++) {
                $stocks[$i]['sizeline1'][$j]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline1'][$j]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline1'][$j]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline1'][$j]['isEmptySize'] = false;
            }
            for ($j=12; $j<24; $j++) {
                $stocks[$i]['sizeline2'][$j-12]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline2'][$j-12]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline2'][$j-12]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline2'][$j-12]['isEmptySize'] = false;
            }
            for ($j=24; $j<36; $j++) {
                $stocks[$i]['sizeline3'][$j-24]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline3'][$j-24]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline3'][$j-24]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline3'][$j-24]['isEmptySize'] = false;
            }
            for ($j=36; $j<48; $j++) {
                $stocks[$i]['sizeline4'][$j-36]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline4'][$j-36]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline4'][$j-36]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline4'][$j-36]['isEmptySize'] = false;
            }
            for ($j=48; $j<$countSizes; $j++) {
                $stocks[$i]['sizeline5'][$j-48]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline5'][$j-48]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline5'][$j-48]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline5'][$j-48]['isEmptySize'] = false;
            }
            for ($j=$countSizes-48; $j<12; $j++) {
                $stocks[$i]['sizeline5'][$j]['isEmptySize'] = true;
            }
        } elseif ($countSizes <= 72) {
            $stocks[$i]['multiline']  = true;
            $stocks[$i]['2line']      = true;
            $stocks[$i]['3line']      = true;
            $stocks[$i]['4line']      = true;
            $stocks[$i]['5line']      = true;
            $stocks[$i]['6line']      = true;
            $stocks[$i]['7line']      = false;
            $stocks[$i]['8line']      = false;
            $stocks[$i]['rowspan']    = 12;
           for ($j=0; $j<12; $j++) {
                $stocks[$i]['sizeline1'][$j]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline1'][$j]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline1'][$j]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline1'][$j]['isEmptySize'] = false;
            }
            for ($j=12; $j<24; $j++) {
                $stocks[$i]['sizeline2'][$j-12]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline2'][$j-12]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline2'][$j-12]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline2'][$j-12]['isEmptySize'] = false;
            }
            for ($j=24; $j<36; $j++) {
                $stocks[$i]['sizeline3'][$j-24]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline3'][$j-24]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline3'][$j-24]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline3'][$j-24]['isEmptySize'] = false;
            }
            for ($j=36; $j<48; $j++) {
                $stocks[$i]['sizeline4'][$j-36]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline4'][$j-36]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline4'][$j-36]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline4'][$j-36]['isEmptySize'] = false;
            }
            for ($j=48; $j<60; $j++) {
                $stocks[$i]['sizeline5'][$j-48]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline5'][$j-48]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline5'][$j-48]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline5'][$j-48]['isEmptySize'] = false;
            }
            for ($j=60; $j<$countSizes; $j++) {
                $stocks[$i]['sizeline6'][$j-60]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline6'][$j-60]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline6'][$j-60]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline6'][$j-60]['isEmptySize'] = false;
            }
            for ($j=$countSizes-60; $j<12; $j++) {
                $stocks[$i]['sizeline6'][$j]['isEmptySize'] = true;
            }
        } elseif ($countSizes <= 84) {
            $stocks[$i]['multiline']  = true;
            $stocks[$i]['2line']      = true;
            $stocks[$i]['3line']      = true;
            $stocks[$i]['4line']      = true;
            $stocks[$i]['5line']      = true;
            $stocks[$i]['6line']      = true;
            $stocks[$i]['7line']      = true;
            $stocks[$i]['8line']      = false;
            $stocks[$i]['rowspan']    = 14;
            for ($j=0; $j<12; $j++) {
                $stocks[$i]['sizeline1'][$j]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline1'][$j]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline1'][$j]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline1'][$j]['isEmptySize'] = false;
            }
            for ($j=12; $j<24; $j++) {
                $stocks[$i]['sizeline2'][$j-12]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline2'][$j-12]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline2'][$j-12]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline2'][$j-12]['isEmptySize'] = false;
            }
            for ($j=24; $j<36; $j++) {
                $stocks[$i]['sizeline3'][$j-24]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline3'][$j-24]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline3'][$j-24]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline3'][$j-24]['isEmptySize'] = false;
            }
            for ($j=36; $j<48; $j++) {
                $stocks[$i]['sizeline4'][$j-36]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline4'][$j-36]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline4'][$j-36]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline4'][$j-36]['isEmptySize'] = false;
            }
            for ($j=48; $j<60; $j++) {
                $stocks[$i]['sizeline5'][$j-48]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline5'][$j-48]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline5'][$j-48]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline5'][$j-48]['isEmptySize'] = false;
            }
            for ($j=60; $j<72; $j++) {
                $stocks[$i]['sizeline6'][$j-60]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline6'][$j-60]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline6'][$j-60]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline6'][$j-60]['isEmptySize'] = false;
            }
            for ($j=72; $j<$countSizes; $j++) {
                $stocks[$i]['sizeline7'][$j-72]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline7'][$j-72]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline7'][$j-72]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline7'][$j-72]['isEmptySize'] = false;
            }
            for ($j=$countSizes-72; $j<12; $j++) {
                $stocks[$i]['sizeline7'][$j]['isEmptySize'] = true;
            }
        } elseif ($countSizes <= 96) {
            $stocks[$i]['multiline']  = true;
            $stocks[$i]['2line']      = true;
            $stocks[$i]['3line']      = true;
            $stocks[$i]['4line']      = true;
            $stocks[$i]['5line']      = true;
            $stocks[$i]['6line']      = true;
            $stocks[$i]['7line']      = true;
            $stocks[$i]['8line']      = true;
            $stocks[$i]['rowspan']    = 16;
            for ($j=0; $j<12; $j++) {
                $stocks[$i]['sizeline1'][$j]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline1'][$j]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline1'][$j]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline1'][$j]['isEmptySize'] = false;
            }
            for ($j=12; $j<24; $j++) {
                $stocks[$i]['sizeline2'][$j-12]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline2'][$j-12]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline2'][$j-12]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline2'][$j-12]['isEmptySize'] = false;
            }
            for ($j=24; $j<36; $j++) {
                $stocks[$i]['sizeline3'][$j-24]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline3'][$j-24]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline3'][$j-24]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline3'][$j-24]['isEmptySize'] = false;
            }
            for ($j=36; $j<48; $j++) {
                $stocks[$i]['sizeline4'][$j-36]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline4'][$j-36]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline4'][$j-36]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline4'][$j-36]['isEmptySize'] = false;
            }
            for ($j=48; $j<60; $j++) {
                $stocks[$i]['sizeline5'][$j-48]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline5'][$j-48]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline5'][$j-48]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline5'][$j-48]['isEmptySize'] = false;
            }
            for ($j=60; $j<72; $j++) {
                $stocks[$i]['sizeline6'][$j-60]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline6'][$j-60]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline6'][$j-60]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline6'][$j-60]['isEmptySize'] = false;
            }
            for ($j=72; $j<84; $j++) {
                $stocks[$i]['sizeline7'][$j-72]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline7'][$j-72]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline7'][$j-72]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline7'][$j-72]['isEmptySize'] = false;
            }
            for ($j=84; $j<$countSizes; $j++) {
                $stocks[$i]['sizeline8'][$j-84]['Size']       = castHtmlEntity($stocks[$i]['sizes'][$j]['Size']);
                $stocks[$i]['sizeline8'][$j-84]['HikiateQty'] = $stocks[$i]['sizes'][$j]['HikiateQty'];
                $stocks[$i]['sizeline8'][$j-84]['OldHikiateQty'] = $stocks[$i]['sizes'][$j]['OldHikiateQty'];
                $stocks[$i]['sizeline8'][$j-84]['isEmptySize'] = false;
            }
            for ($j=$countSizes-84; $j<12; $j++) {
                $stocks[$i]['sizeline8'][$j]['isEmptySize'] = true;
            }
        }
	}

	return  $stocks;

}

?>
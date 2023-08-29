<?php
/*
 * 西暦から元号に変換する
 * castGengou.php
 *
 * create 2007/04/26 H.Osugi
 *
 */

/*
 * 与えられた西暦の日付から元号に変換する
 * 引数  ：$baseDate => 元号に変換したい日付 (yyyy/mm/dd)
 * 戻り値：$gengouDatas => name  : 元号 （例:2007/01/01 ⇒ 平成）
 *                      => year  : 年 （例:2007/01/01 ⇒ 19, 1989/01/08 ⇒ 元）
 *                      => month : 月 
 *                      => day   : 日 
 *
 * create 2007/04/26 H.Osugi
 *
 */
function castGengou($baseDate) {

	global $GENGOU;		// define定義の元号情報

	// 初期化
	$gengouDatas = array();

	$countGengou = count($GENGOU);
//	list($baseDateY, $baseDateM, $baseDateD)          = explode('/', $baseDate);
	list($baseDateY, $baseDateM, $baseDateD)          = explode('-', $baseDate);
	$base        = sprintf('%04d%02d%02d', $baseDateY, $baseDateM, $baseDateD);
	for ($i=0; $i<$countGengou; $i++) {

//		list($gengouStartY, $gengouStartM, $gengouStartD) = explode('/', $GENGOU[$i]['startDay']);
//		list($gengouEndY, $gengouEndM, $gengouEndD)       = explode('/', $GENGOU[$i]['endDay']);
		list($gengouStartY, $gengouStartM, $gengouStartD) = explode('-', $GENGOU[$i]['startDay']);
		list($gengouEndY, $gengouEndM, $gengouEndD)       = explode('-', $GENGOU[$i]['endDay']);

		$gengouStart = sprintf('%04d%02d%02d', $gengouStartY, $gengouStartM, $gengouStartD);
		$gengouEnd   = sprintf('%04d%02d%02d', $gengouEndY, $gengouEndM, $gengouEndD);

		if ($GENGOU[$i]['endDay'] != '') {

			if ($gengouStart <= $base && $base <= $gengouEnd) {

				$gengouDatas['name'] = $GENGOU[$i]['name'];
				$gengouDatas['year'] = ($baseDateY - $gengouStartY) + 1;

				// 元号の開始年と同じ場合は元年
				if ($gengouDatas['year'] == 1) {
					$gengouDatas['year'] = '元';
				}

				$gengouDatas['month'] = $baseDateM;
				$gengouDatas['day']   = $baseDateD;

				return $gengouDatas;

			}

		}
		else {

			if ($gengouStart <= $base) {

				$gengouDatas['name'] = $GENGOU[$i]['name'];
				$gengouDatas['year'] = ($baseDateY - $gengouStartY) + 1;

				// 元号の開始年と同じ場合は元年
				if ($gengouDatas['year'] == 1) {
					$gengouDatas['year'] = '元';
				}

				$gengouDatas['month'] = $baseDateM;
				$gengouDatas['day']   = $baseDateD;

				return $gengouDatas;

			}

		} 
		
	}

	return false;

}

?>
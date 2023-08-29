<?php
/*
 * プログラムの終了時の処理
 * processFin.php
 *
 * create 2007/03/23 H.Osugi
 *
 */

/*
 * プログラム終了時の処理
 * 引数  ：$dbConnect => コネクションハンドラ
 * 戻り値：なし
 *
 * create 2007/03/23 H.Osugi
 *
 */
function processFin(&$dbConnect) {

	// DBの切断
	db_Close($dbConnect);
	
}

/*
 * プログラム強制終了時の処理
 * 引数  ：なし
 * 戻り値：なし
 *
 * create 2007/03/23 H.Osugi
 *
 */
function processExit($dbConnect) {

	// DBの切断
	db_Close($dbConnect);
	exit;

}


?>
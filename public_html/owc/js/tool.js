/**
 * OPTIMA Web Compiler
 * ツール
 * 
 * @author	George Mitsumoto
 * @version	2.0.0 (2006-05-14)
 */

/**
 * コンパイルを開始する
 */
function startCompile() {
	window.parent.frames["dir"].document.forms["tree"].submit();
}

/**
 * コンパイル開始トリガを有効にする
 */
function enableStartCompileTrigger() {
	document.getElementById("startCompileTrigger").onclick = startCompile;
}

/**
 * ドキュメント初期化
 */
window.addOnLoadHandler(enableStartCompileTrigger);

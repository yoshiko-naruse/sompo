/**
 * OPTIMA Web Compiler
 * ファイル検索
 * 
 * @author	George Mitsumoto
 * @version	2.0.0 (2006-05-09)
 */

/**
 * キーワードテキストボックスを有効にする
 */
function enableKeyword() {
	var keyInput = document.getElementById("keyword");
	keyInput.onfocus = function() {
		if (this.value != "") {
			this.select();
		}
	};
	keyInput.focus();
}

/**
 * ドキュメント初期化
 */
window.addOnLoadHandler(enableKeyword);

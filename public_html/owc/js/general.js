/**
 * OPTIMA Web Compiler
 * 共通スクリプト
 * 
 * @author	George Mitsumoto
 * @version	2.0.0 (2006-05-09)
 */

/**
 * ブラウザ識別
 */
window.navigator.isWinIE = null;
window.navigator.isMacIE = null;
window.navigator.isMozilla = null;
window.navigator.isOpera = null;
window.navigator.isSafari = null;
window.navigator.isUnknown = null;

if (navigator.userAgent.indexOf("Safari") > -1) {
	//  Safari
	window.navigator.isSafari = true;
} else if (window.opera) {
	//  Opera
	window.navigator.isOpera = true;
} else if (navigator.userAgent.indexOf("MSIE") > -1 && navigator.platform.indexOf("Mac") > -1) {
	//  MacIE
	window.navigator.isMacIE = true;
} else if (document.all && document.selection) {
	//  WinIE
	window.navigator.isWinIE = true;
} else if (window.controllers) {
	//  Mozilla
	window.navigator.isMozilla = true;
} else {
	//  Unknown
	window.navigator.isUnknown = true;
}

/**
 * window.onload ハンドラを割り当てる
 * 
 * @example		window.addOnLoadHandler(hoge:Function);
 */
window.onLoadHandlers = new Array();
window.addOnLoadHandler = function(handler) {
	window.onLoadHandlers.push(handler);
};

window.onLoadHandler = function() {
	for (var i = 0; i < window.onLoadHandlers.length; i++) {
		window.onLoadHandlers[i]();
	}
};

if (window.addEventListener) {
	window.addEventListener("load", onLoadHandler, true);
} else {
	window.attachEvent("onload", onLoadHandler);
}

/**
 * 呼び出すノード以下から指定されたclass名のノードを取得する
 * 
 * @param 	_className	クラス名
 * @return				取得した要素の配列
 * @example	someNode.getElementsByClassName("hoge");
 */
function getElementsByClassName(_className){
	var numElements = this.getElementsByTagName("*").length;
	var detectedElements = new Array();
	for (var i = 0; i < numElements; i++) {
		if (this.getElementsByTagName("*")[i].className == _className) {
			detectedElements.push(this.getElementsByTagName("*")[i]);
		}
	}
	return detectedElements;
}

/**
 * getElementsByClassNameを有効にする
 */
function enableGetElementsByClassName(){
	
	//  getElementsByClassNameを各要素に割り当てる
	document.getElementsByClassName = getElementsByClassName;
	document.body.getElementsByClassName = getElementsByClassName;
	var numElements = document.body.getElementsByTagName("*").length;
	for (var i = 0; i < numElements; i++) {
		document.body.getElementsByTagName("*")[i].getElementsByClassName = getElementsByClassName;
	}
}

/**
 * ドキュメント初期化
 */
window.addOnLoadHandler(enableGetElementsByClassName);

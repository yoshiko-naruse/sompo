/**
 * OPTIMA Web Compiler
 * ディレクトリツリー
 * 
 * @author	George Mitsumoto
 * @version	2.0.0 (2006-05-14)
 */

//  IEで水平スクロールバーを抑止する
if (navigator.isWinIE) {
	document.write('<style type="text/css"> * html { overflow-y: scroll; } </style>');
}

/**
 * ディレクトリノードのチェックを切り替える
 */
function toggleDirCheck() {
	var childList = this.parentNode.parentNode.getElementsByTagName("UL").item(0);
	if (childList) {
		
		//  子ノードのチェックを切り替える
		var childChecks = childList.getElementsByTagName("INPUT");
		for (var i = 0; i < childChecks.length; i++) {
			childChecks[i].checked = this.checked;
		}
	} else {
		
		//  子ノードが存在しない場合はチェックを切り替えない
	}
	
	//  チェックの切り替えを親ディレクトリノードのチェックと連動させる
	if (!this.checked) {
		var currentDirNode = this.parentNode.parentNode;	//  現在のディレクトリノード
		var parentDirNode;	//  親のディレクトリノード
		do {
			parentDirNode = currentDirNode.parentNode.parentNode;
			if (parentDirNode.nodeName != "LI") {
				break;
			}
			parentDirNode.firstChild.firstChild.checked = this.checked;
			currentDirNode = parentDirNode;
		} while (parentDirNode);
	}
}

/**
 * toggleDirCheckを有効にする
 */
function enableToggleDirCheck() {
	var nodes = document.getElementsByClassName("dir");
	for (var i = 0; i < nodes.length; i++) {
		nodes[i].firstChild.firstChild.onclick = toggleDirCheck;
	}
}

/**
 * ファイルノードのチェックを切り替える
 */
function toggleFileCheck() {
	
	//  チェックの切り替えを親ディレクトリノードのチェックと連動させる
	if (!this.checked) {
		var currentFileNode = this.parentNode.parentNode;	//  現在のファイルノード
		var parentDirNode;	//  親のディレクトリノード
		do {
			parentDirNode = currentFileNode.parentNode.parentNode;
			if (parentDirNode.nodeName != "LI") {
				break;
			}
			parentDirNode.firstChild.firstChild.checked = this.checked;
			currentFileNode = parentDirNode;
		} while (parentDirNode);
	}
}

/**
 * toggleFileCheckを有効にする
 */
function enableToggleFileCheck() {
	var nodes = document.getElementsByClassName("file");
	for (var i = 0; i < nodes.length; i++) {
		nodes[i].firstChild.firstChild.onclick = toggleFileCheck;
	}
}

/**
 * ディレクトリノードの開閉を切り替える
 */
function toggleDirOpen() {
	var child = this.parentNode.parentNode.getElementsByTagName("UL").item(0);
	var mark = this.nextSibling.firstChild;
	
	if (this.opened) {
		
		//  閉じる
		if (child) {
			child.style.display = "none";
		}
		mark.nodeValue = "+";
		this.opened = false;
	} else {
		
		//  展開する
		if (child) {
			child.style.display = "block";
		}
		mark.nodeValue = "-";
		this.opened = true;
	}
	return false;
}

/**
 * toggleDirOpenを有効にする
 */
function enableToggleDirOpen() {
	var nodes = document.getElementsByClassName("dir");
	for (var i = 1; i < nodes.length; i++) {	//  rootディレクトリには割り当てない
		nodes[i].firstChild.childNodes.item(1).opened = true;	//  拡張プロパティ opened に開閉状態を保持させる
		nodes[i].firstChild.childNodes.item(1).onclick = toggleDirOpen;
	}
}

/**
 * LABEL要素とINPUT要素を自動的に関連付ける
 */
function enableLabelRelation() {
	var idPrefix = "input";
	var idNum = parseInt((new Date()).getUTCMilliseconds());
	var labels = document.getElementsByTagName("LABEL");
	var input;
	
	for (var i = 0; i < labels.length; i++) {
		//alert("enableLabelRelation");
		if (labels[i].htmlFor == "") {	//  意図的にfor属性が指定されているものは対象外
			input = labels[i].getElementsByTagName("INPUT")[0];
			if (input) {
				if (input.type == "text" || input.type == "checkbox" || input.type == "radio") {
					if (input.id != "") {
						
						//  INPUT要素に既にid属性が指定されていればそれを用いてLABEL属性と関連付ける
						labels[i].htmlFor = input.id;
					} else {
						
						//  INPUT要素にid属性が指定されていなければ自動的にidと関連付けを設定
						input.id = idPrefix + String(idNum);
						labels[i].htmlFor = input.id;
						idNum++;
					}
				}
			}
		}
	}
}

/**
 * ドキュメント初期化
 */
window.addOnLoadHandler(enableToggleDirCheck);
window.addOnLoadHandler(enableToggleFileCheck);
window.addOnLoadHandler(enableToggleDirOpen);

//  IEでLABEL要素とINPUT要素の自動関連付けを行う
if (window.navigator.isWinIE) {
	window.addOnLoadHandler(enableLabelRelation);
}

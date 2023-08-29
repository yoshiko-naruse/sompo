<?php
/*
 * エラー判定処理
 * change_password.val.php
 *
 * create 2011/06/13 T.Uno
 *
 *
 */

/*
 * 機能  ：エラー判定を行う
 * 引数  ：$dbConnect  => コネクションハンドラ
 *       ：$post       => POSTデータ
 * 戻り値：なし
 *
 * create 2011/06/13 T.Uno
 *
 */
function validatePostData($dbConnect, $post) {

	// 初期化
	$hiddens = array();
	$isError = false;

	// ユーザーIDが存在しなければ初期化
	if (!isset($post['usercode'])) {
		$post['usercode'] = '';
	}

	// ユーザーIDが空ならば
	if ($post['usercode'] == '') {
		$hiddens['errorId'][] = '001';
		$isError = true;
	}

	// ここまでにエラーがなければDBに存在するかの判定を行う
	if ($isError == false) {

		// 該当のユーザ情報が存在しているか判定
		$sql  = "";
		$sql .= " SELECT";
		$sql .= 	" count(UserID) as count_user";
		$sql .= " FROM";
		$sql .= 	" M_User";
		$sql .= " WHERE";
		$sql .= 	" NameCd = '" . db_Escape($post['usercode']) . "'";
		$sql .= " AND";
		$sql .= 	" Del = " . DELETE_OFF;

		$result = db_Read($dbConnect, $sql);

		// 該当データが取得できない場合はエラー
		if (!isset($result[0]['count_user']) || $result[0]['count_user'] <= 0) {
			$hiddens['errorId'][] = '002';
			$isError = true;
		}

	}

	// エラーが存在したならば、エラー画面に遷移
	if (count($hiddens['errorId']) > 0) {
		$hiddens['errorName'] = 'clearPassword';
		$hiddens['returnUrl'] = 'admin/clear_password.php';
		$errorUrl             = '../error.php';

		// POST値をHTMLエンティティ
		$post = castHtmlEntity($post); 

		$notAllows = array();
		$notAllows[] = 'update_flg';
		$hiddenHtml = castHiddenError($post,$notAllows);
		$hiddens = array_merge($hiddens, $hiddenHtml);

		redirectPost($errorUrl, $hiddens);
	}
}

?>
<?php
/*
 * エラー判定処理
 * change_password.val.php
 *
 * create 2007/04/04 H.Osugi
 *
 *
 */

/*
 * 機能  ：エラー判定を行う
 * 引数  ：$dbConnect  => コネクションハンドラ
 *       ：$post       => POSTデータ
 * 戻り値：なし
 *
 * create 2007/04/04 H.Osugi
 *
 */
function validatePostData($dbConnect, $post) {

	// 初期化
	$hiddens = array();
	$isError = false;

	// 現在のパスワードが存在しなければ初期化
	if (!isset($post['nowPassword'])) {
		$post['nowPassword'] = '';
	}

	// 現在のパスワードが空ならば
	if ($post['nowPassword'] == '') {
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
		$sql .= 	" UserID = '" . db_Escape($_SESSION['USERID']) . "'";
		$sql .= " AND";
		$sql .= 	" convert(binary(21), rtrim(PassWd)) = convert(binary(21), '" . db_Escape($post['nowPassword']) . "')";
		$sql .= " AND";
		$sql .= 	" Del = " . DELETE_OFF;

		$result = db_Read($dbConnect, $sql);

		// 該当データが取得できない場合はエラー
		if (!isset($result[0]['count_user']) || $result[0]['count_user'] <= 0) {
			$hiddens['errorId'][] = '011';
			$isError = true;
		}

	}

	// ここまでにエラーがなければ新しいパスワードに問題が無いかの判定を行う
	if ($isError == false) {

		// 新しいパスワードが存在しなければ初期化
		if (!isset($post['newPassword1'])) {
			$post['newPassword1'] = '';
		}

		// 新しいパスワード（確認用）が存在しなければ初期化
		if (!isset($post['newPassword2'])) {
			$post['newPassword2'] = '';
		}
	
		if ($post['newPassword1'] == '') {
			$hiddens['errorId'][] = '021';
			$isError = true;
		}
		elseif ($post['newPassword1'] != $post['newPassword2'])  {
			$hiddens['errorId'][] = '022';
			$isError = true;
		}
	}

	// 初期パスワードと同一でないことを確認
	// 09/04/08 uesugi
	if ($isError == false){
		if($post['newPassword1'] == SYSTEM_DEFAULT_PASSWORD){
			$hiddens['errorId'][] = '025';
			$isError = true;
		}
	}
	
	// 現パスワードと同一でないことを確認
	// 09/04/08 uesugi
	if ($isError == false){
		if($post['nowPassword'] == $post['newPassword1']){
			$hiddens['errorId'][] = '026';
			$isError = true;
		}
	}

	// ユーザーIDがパスワードに含まれていないことを確認
	// 09/04/08 uesugi
	if ($isError == false){
		if(mb_ereg($_SESSION['NAMECODE'], $post['newPassword1'])){
			$hiddens['errorId'][] = '027';
			$isError = true;
		}
	}

	// ここまでにエラーがなければ新しいパスワードに問題が無いかの判定を行う
	if ($isError == false) {

		// パスワードの判定
		$result = checkData(trim($post['newPassword1']), 'Alphanumeric', true, 12, 8);
	
		// エラーが発生したならば、エラーメッセージを取得
		switch ($result) {
	
			// 空白ならば
			case 'empty':
				$hiddens['errorId'][] = '021';
				break;
				
			// 数値以外の文字ならば
			case 'mode':
				$hiddens['errorId'][] = '024';
				break;
	
			// 最大値超過ならば
			case 'max':
				$hiddens['errorId'][] = '023';
				break;
	
			// 最小値未満ならば
			case 'min':
				$hiddens['errorId'][] = '023';
				break;

			default:
                // 初期設定と同じなら
                //if (trim($post['newPassword1']) === SYSTEM_DEFAULT_PASSWORD) {
                //    $hiddens['errorId'][] = '024';
                //}
				break;
		}
	}

	// エラーが存在したならば、エラー画面に遷移
	if (count($hiddens['errorId']) > 0) {
		$hiddens['errorName'] = 'changePassword';
		$hiddens['returnUrl'] = 'change_password.php';
		$errorUrl             = './error2.php';

		// POST値をHTMLエンティティ
		$post = castHtmlEntity($post); 

		$hiddenHtml = castHiddenError($post);

		$hiddens = array_merge($hiddens, $hiddenHtml);

		redirectPost($errorUrl, $hiddens);

	}

}

?>
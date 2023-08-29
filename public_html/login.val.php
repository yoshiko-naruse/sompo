<?php
/*
 * エラー判定処理
 * login.val.php
 *
 * create 2007/03/13 H.Osugi
 *
 *
 */

/*
 * 機能  ：エラー判定を行う
 * 引数  ：$dbConnect => コネクションハンドラ
 *       ：$post      => POSTデータ
 * 戻り値：なし
 *
 * create 2007/03/13 H.Osugi
 *
 */
function validatePostData($dbConnect, $post) {

	// エラー判定フラグ
	$isError = false;

	// ログインIDが存在しなければ初期化
	if (!isset($post['loginId'])) {
		$post['loginId'] = '';
	}

	// デモサイト判定追加
	// uesugi 081119
	//デモフラグがONの場合
	if(SET_DEMO_SITE){
		//先頭4文字切り出し
		$demoStr = substr($post['loginId'],0,4);
		if($demoStr == "demo" || $demoStr == "DEMO"){
			$loginLen = strlen($post['loginId']);
			$post['loginId'] = substr($post['loginId'],4,$loginLen - 4);
		}else{
			//先頭4文字が'demo'じゃない場合、初期化
			$post['loginId'] = '';
		}
	}

	// ログインIDの判定
	$result = checkData(trim($post['loginId']), 'HalfWidth', true);

	// エラーが発生したならば、エラーメッセージを取得
	switch ($result) {

		// 空白ならば
		case 'empty':
			$isError = true;
			break;
			
		// 半角以外の文字ならば
		case 'mode':
			$isError = true;
			break;

	}

	// パスワードが存在しなければ初期化
	if (!isset($post['passWd'])) {
		$post['passWd'] = '';
	}

	// パスワードの判定
	$result = checkData(trim($post['passWd']), 'HalfWidth', true);

	// エラーが発生したならば、エラーメッセージを取得
	switch ($result) {

		// 空白ならば
		case 'empty':
			$isError = true;
			break;
			
		// 半角以外の文字ならば
		case 'mode':
			$isError = true;
			break;

	}

	// ここまでにエラーがなければDBに存在するかの判定を行う
	if ($isError == false) {

		// SESSIONに保存する値を取得
		$sql  = "";
		$sql .= " SELECT";
		$sql .= 	" mu.UserID,";
		$sql .= 	" mu.Name,";
		$sql .= 	" mu.UserLvl,";
		$sql .= 	" mu.AdminLvl,";
		// 伊藤忠、ユニコ、ソウシンク、伊藤忠ロジでログインした際はシステム管理者
		// ＪＡＦ本社、ＪＡＦサービスとは切り分けするために設けた区分
		$sql .= 	" mu.AttrKbn,";
		$sql .= 	" mc.CompID,";
		$sql .= 	" mc.CompCd,";
        $sql .=     " mc.CompName,";
		$sql .= 	" mc.CorpCd,";
        $sql .=     " mc.CorpName,";
		$sql .= 	" mc.HonbuCd,";
        $sql .=     " mc.HonbuName,";
		$sql .= 	" mc.ShibuCd,";
        $sql .=     " mc.ShibuName,";
        $sql .=     " mc.CompKind,";
		$sql .= 	" mc.ShopFlag";
		$sql .= " FROM";
		$sql .= 	" M_User mu";
		$sql .= " INNER JOIN";
		$sql .= 	" M_Comp mc";
		$sql .= " ON";
		$sql .= 	" mu.CompID = mc.CompID";
		$sql .= " WHERE";
		$sql .= 	" convert(binary(21), rtrim(mu.NameCd)) = convert(binary(21), '" . db_Escape($post['loginId']) . "')";
		$sql .= " AND";
		$sql .= 	" convert(binary(21), rtrim(mu.PassWd)) = convert(binary(21), '" . db_Escape($post['passWd']) . "')";
		$sql .= " AND";
		$sql .= 	" mu.Del = " . DELETE_OFF;
		$sql .= " AND";
		$sql .= 	" mc.Del = " . DELETE_OFF;
		
		$result = db_Read($dbConnect, $sql);
		
		// 該当データが取得できない場合はエラー
		if (!isset($result) || count($result) <= 0) {
			$isError = true;
		}
		// 取得できた場合はSESSIONに保存
		else {

			// ユーザID
			if (isset($post['loginId'])) {
				$_SESSION['NAMECODE'] = trim($post['loginId']);
				 setcookie("userId", trim($post['loginId']));
			}

			// パスワード
			if (isset($post['passWd'])) {
				$_SESSION['PASSWORD'] = trim($post['passWd']);
				 setcookie("pass", md5(trim($post['passWd'])));
			}

			// ユーザID（シーケンスID）
			if (isset($result[0]['UserID'])) {
				$_SESSION['USERID'] = trim($result[0]['UserID']);
			}

			// ユーザ名
			if (isset($result[0]['Name'])) {
				$_SESSION['USERNAME'] = trim($result[0]['Name']);
			}

			// 権限レベル
			if (isset($result[0]['UserLvl'])) {
				$_SESSION['USERLVL'] = trim($result[0]['UserLvl']);
			}

			// 管理レベル（2:max権限管理、1:JAF管理権限管理）
			if (isset($result[0]['AdminLvl'])) {
				$_SESSION['ADMINLVL'] = trim($result[0]['AdminLvl']);
			}

			// 属性区分（1:「伊藤忠」／1以外は「ＪＡＦ」）
			if (isset($result[0]['AttrKbn'])) {
				$_SESSION['ATTRKBN'] = trim($result[0]['AttrKbn']);
			}

			// 店舗ID
			if (isset($result[0]['CompID'])) {
				$_SESSION['COMPID'] = trim($result[0]['CompID']);
			}

			// 店舗コード
			if (isset($result[0]['CompCd'])) {
				$_SESSION['COMPCD'] = trim($result[0]['CompCd']);
			}

			// 店舗名
			if (isset($result[0]['CompName'])) {
				$_SESSION['COMPNAME'] = trim($result[0]['CompName']);
			}

			// 会社コード
			if (isset($result[0]['CorpCd'])) {
				$_SESSION['CORPCD'] = trim($result[0]['CorpCd']);
			}

			// 会社名
			if (isset($result[0]['CorpName'])) {
				$_SESSION['CORPNAME'] = trim($result[0]['CorpName']);
			}

			// 本部コード
			if (isset($result[0]['HonbuCd'])) {
				$_SESSION['HONBUCD'] = trim($result[0]['HonbuCd']);
			}

			// 本部名
			if (isset($result[0]['HonbuName'])) {
				$_SESSION['HONBUNAME'] = trim($result[0]['HonbuName']);
			}

			// 支部コード
			if (isset($result[0]['ShibuCd'])) {
				$_SESSION['SHIBUCD'] = trim($result[0]['ShibuCd']);
			}

			// 支部名
			if (isset($result[0]['ShibuName'])) {
				$_SESSION['SHIBUNAME'] = trim($result[0]['ShibuName']);
			}

            // 特殊店舗フラグ
            if (isset($result[0]['CompKind'])) {
                $_SESSION['COMPKIND'] = trim($result[0]['CompKind']);
            }

            // 特殊店舗フラグ
            if (isset($result[0]['ShopFlag'])) {
                $_SESSION['SHOPFLAG'] = trim($result[0]['ShopFlag']);
            }

			// ログイン情報をログに残す
			$fp = fopen(LOGIN_FILE_PATH, 'a');

			//バッファを0に指定（排他制御の保証）
			stream_set_write_buffer($fp,0);

			//ファイルのロック （排他制御）
			flock($fp, LOCK_EX);

			$log = date('Y/m/d H:i:s') . ',' . $_SESSION['NAMECODE'] . ',' . $_SESSION['COMPID'] . ',' . $_SESSION['COMPCD'] . ',' . $_SESSION['COMPNAME'] . "\n";

			// 書き込む文字列の文字コードをshift_Jisに変換
			$log = mb_convert_encoding($log, 'SJIS', 'auto');

			//ファイルに書きこみ（時間 / ログインID / ショップID / ショップコード : ショップ名）
			fwrite($fp, $log);

			//ロックの開放
			flock($fp, LOCK_UN);

			//ファイルのクローズ
			fclose($fp);

/*
            // 初期設定パスワードの場合は、パスワード変更画面に遷移
            if ($post['passWd'] == SYSTEM_DEFAULT_PASSWORD) {
                header('Location: ' . './change_password.php');
                exit;
            }
*/
		}
	}

	return $isError;

}

?>
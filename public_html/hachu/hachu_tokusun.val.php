<?php
/*
 * エラー判定処理
 * hachu_tokusun.val.php
 *
 * create 2007/03/30 H.Osugi
 *
 *
 */

/*
 * 機能  ：エラー判定を行う
 * 引数  ：$post       => POSTデータ
 * 戻り値：なし
 *
 * create 2007/03/30 H.Osugi
 *
 */
function validateTokData($post) {

	// 初期化
	$hiddens = array();

	$isSizeInputFlag = false;
	$isBikouInputFlag = false;

	// 身長が存在しなければ初期化
	if (!isset($post['high'])) {
		$post['high'] = '';
	}

	// 身長の判定
	$result = checkData(trim($post['high']), 'Numeric', true, 8);

	// エラーが発生したならば、エラーメッセージを取得
	switch ($result) {

		// 空白ならば
		case 'empty':
		//	$hiddens['errorId'][] = '001';
			break;
			
		// 数値以外の文字ならば
		case 'mode':
			$hiddens['errorId'][] = '002';
			break;

		// 最大値超過ならば
		case 'max':
			$hiddens['errorId'][] = '002';
			break;

		default:
			$isSizeInputFlag = true;
			break;

	}

	// 体重が存在しなければ初期化
	if (!isset($post['weight'])) {
		$post['weight'] = '';
	}

	// 体重の判定
	$result = checkData(trim($post['weight']), 'Numeric', true, 8);

	// エラーが発生したならば、エラーメッセージを取得
	switch ($result) {

		// 空白ならば
		case 'empty':
		//	$hiddens['errorId'][] = '011';
			break;
			
		// 数値以外の文字ならば
		case 'mode':
			$hiddens['errorId'][] = '012';
			break;

		// 最大値超過ならば
		case 'max':
			$hiddens['errorId'][] = '012';
			break;

		default:
			$isSizeInputFlag = true;
			break;

	}

	// バストが存在しなければ初期化
	if (!isset($post['bust'])) {
		$post['bust'] = '';
	}

	// バストの判定
	$result = checkData(trim($post['bust']), 'Numeric', true, 8);

	// エラーが発生したならば、エラーメッセージを取得
	switch ($result) {

		// 空白ならば
		case 'empty':
		//	$hiddens['errorId'][] = '021';
			break;
			
		// 数値以外の文字ならば
		case 'mode':
			$hiddens['errorId'][] = '022';
			break;

		// 最大値超過ならば
		case 'max':
			$hiddens['errorId'][] = '022';
			break;

		default:
			$isSizeInputFlag = true;
			break;

	}

	// ウエストが存在しなければ初期化
	if (!isset($post['waist'])) {
		$post['waist'] = '';
	}

	// ウエストの判定
	$result = checkData(trim($post['waist']), 'Numeric', true, 8);

	// エラーが発生したならば、エラーメッセージを取得
	switch ($result) {

		// 空白ならば
		case 'empty':
		//	$hiddens['errorId'][] = '031';
			break;
			
		// 数値以外の文字ならば
		case 'mode':
			$hiddens['errorId'][] = '032';
			break;

		// 最大値超過ならば
		case 'max':
			$hiddens['errorId'][] = '032';
			break;

		default:
			$isSizeInputFlag = true;
			break;

	}

	// ヒップが存在しなければ初期化
	if (!isset($post['hips'])) {
		$post['hips'] = '';
	}

	// ヒップの判定
	$result = checkData(trim($post['hips']), 'Numeric', true, 8);

	// エラーが発生したならば、エラーメッセージを取得
	switch ($result) {

		// 空白ならば
		case 'empty':
		//	$hiddens['errorId'][] = '041';
			break;
			
		// 数値以外の文字ならば
		case 'mode':
			$hiddens['errorId'][] = '042';
			break;

		// 最大値超過ならば
		case 'max':
			$hiddens['errorId'][] = '042';
			break;

		default:
			$isSizeInputFlag = true;
			break;

	}

	// 肩幅が存在しなければ初期化
	if (!isset($post['shoulder'])) {
		$post['shoulder'] = '';
	}

	// 肩幅の判定
	$result = checkData(trim($post['shoulder']), 'Numeric', true, 8);

	// エラーが発生したならば、エラーメッセージを取得
	switch ($result) {

		// 空白ならば
		case 'empty':
		//	$hiddens['errorId'][] = '051';
			break;
			
		// 数値以外の文字ならば
		case 'mode':
			$hiddens['errorId'][] = '052';
			break;

		// 最大値超過ならば
		case 'max':
			$hiddens['errorId'][] = '052';
			break;

		default:
			$isSizeInputFlag = true;
			break;

	}

	// 袖丈が存在しなければ初期化
	if (!isset($post['sleeve'])) {
		$post['sleeve'] = '';
	}

	// 袖丈の判定
	$result = checkData(trim($post['sleeve']), 'Numeric', true, 8);

	// エラーが発生したならば、エラーメッセージを取得
	switch ($result) {

		// 空白ならば
		case 'empty':
		//	$hiddens['errorId'][] = '061';
			break;
			
		// 数値以外の文字ならば
		case 'mode':
			$hiddens['errorId'][] = '062';
			break;

		// 最大値超過ならば
		case 'max':
			$hiddens['errorId'][] = '062';
			break;

		default:
			$isSizeInputFlag = true;
			break;

	}

    // 着丈が存在しなければ初期化
    if (!isset($post['kitake'])) {
        $post['kitake'] = '';
    }

    // 着丈の判定
    $result = checkData(trim($post['kitake']), 'Numeric', true, 8);

    // エラーが発生したならば、エラーメッセージを取得
    switch ($result) {

        // 空白ならば
        case 'empty':
        //    $hiddens['errorId'][] = '091';
            break;
            
        // 数値以外の文字ならば
        case 'mode':
            $hiddens['errorId'][] = '092';
            break;

        // 最大値超過ならば
        case 'max':
            $hiddens['errorId'][] = '092';
            break;

        default:
			$isSizeInputFlag = true;
            break;

    }

    // 裄丈の判定
    $result = checkData(trim($post['yukitake']), 'Numeric', true, 8);

    // エラーが発生したならば、エラーメッセージを取得
    switch ($result) {

        // 空白ならば
        case 'empty':
        //    $hiddens['errorId'][] = '101';
            break;
            
        // 数値以外の文字ならば
        case 'mode':
            $hiddens['errorId'][] = '102';
            break;

        // 最大値超過ならば
        case 'max':
            $hiddens['errorId'][] = '102';
            break;

        default:
			$isSizeInputFlag = true;
            break;

    }

    // 股下の判定
    $result = checkData(trim($post['inseam']), 'Numeric', true, 8);

    // エラーが発生したならば、エラーメッセージを取得
    switch ($result) {

        // 空白ならば
        case 'empty':
        //    $hiddens['errorId'][] = '111';
            break;
            
        // 数値以外の文字ならば
        case 'mode':
            $hiddens['errorId'][] = '112';
            break;

        // 最大値超過ならば
        case 'max':
            $hiddens['errorId'][] = '112';
            break;

        default:
			$isSizeInputFlag = true;
            break;

    }

	// 首周りが存在しなければ初期化
	if (!isset($post['length'])) {
		$post['length'] = '';
	}

	// 首周りの判定
	$result = checkData(trim($post['length']), 'Numeric', true, 8);

	// エラーが発生したならば、エラーメッセージを取得
	switch ($result) {

		// 空白ならば
		case 'empty':
		//	$hiddens['errorId'][] = '071';
			break;
			
		// 数値以外の文字ならば
		case 'mode':
			$hiddens['errorId'][] = '072';
			break;

		// 最大値超過ならば
		case 'max':
			$hiddens['errorId'][] = '072';
			break;

		default:
			$isSizeInputFlag = true;
			break;

	}

	// 特寸備考が存在しなければ初期化
	if (!isset($post['tokMemo'])) {
		$post['tokMemo'] = '';
	}

	// 特寸備考の判定
	$result = checkData(trim($post['tokMemo']), 'Text', true, 128);

	// エラーが発生したならば、エラーメッセージを取得
	switch ($result) {

		// 空白ならば
		case 'empty':
		//	$hiddens['errorId'][] = '081';
			break;
			
		// 最大値超過ならば
		case 'max':
			$hiddens['errorId'][] = '082';
			break;

		default:
			$isBikouInputFlag = true;
			break;

	}

	// ヌード寸法もしくは備考欄のどちらかに入力があったかの判定
	if (!$isSizeInputFlag && !$isBikouInputFlag) {
		$hiddens['errorId'][] = '121';
	}

	// エラーが存在したならば、エラー画面に遷移
	if (count($hiddens['errorId']) > 0) {
		$hiddens['errorName'] = 'hachuTokusun';
		$hiddens['menuName']  = 'isMenuOrder';
		if (isset($post['rirekiFlg']) && $post['rirekiFlg'] == 1) {
			$hiddens['menuName']  = 'isMenuHistory';
		}
		$hiddens['returnUrl'] = 'hachu/hachu_tokusun.php';
		$errorUrl             = '../error.php';

		$post['tokFlg'] = 1;

		// POST値をHTMLエンティティ
		$post = castHtmlEntity($post); 

		$hiddenHtml = castHiddenError($post);

		$hiddens = array_merge($hiddens, $hiddenHtml);

		redirectPost($errorUrl, $hiddens);

	}

}

?>
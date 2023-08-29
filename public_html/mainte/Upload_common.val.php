<?php
/*
 * アップロード系共通関数
 * Upload_common.val.php
 *
 * create 2008/02/19 DF
 *
 *
 */

// メッセージ部
define('ERR_MSG_CRON_ERR'				, '処理中にエラーが発生しています。');
define('ERR_MSG_CRON_OK'				, '更新完了');
define('ERR_MSG_CRON_WAIT'				, '更新予約中');

// ユーザー一括
define('ERR_MSG_CRON_NOKUBUN'			, '区分が入力されていません。');
define('ERR_MSG_CRON_DBERR_KUBUN'		, '入力された区分が間違っています。');
define('ERR_MSG_CRON_LOGINCD'			, '従業員番号が入力されていません。');
define('ERR_MSG_CRON_NOSIMEI'			, '氏名が入力されていません。');
define('ERR_MSG_CRON_DBERR_FIRSTDATE'	, '初回貸与年月日が日付ではありません。');
define('ERR_MSG_CRON_DBERR_REDATE'		, '再貸与予定年月日が日付ではありません。');
define('ERR_MSG_CRON_DBERR_KOSYO'		, '個所が入力されていません。');
define('ERR_MSG_CRON_DBERR_NOKOSYO'		, '入力された個所は存在しません。');

define('ERR_MSG_CRON_DBERR_JINJI_HATUREI'	, '入力された人事異動情報の発令日が日付ではありません。');
define('ERR_MSG_CRON_DBERR_JINJI_SYAINNO'	, '入力された人事異動情報の社員番号が正しくありません。');
define('ERR_MSG_CRON_DBERR_JINJI_KOSYO'		, '入力された人事異動情報の個所は存在しません。');

define('ERR_MSG_CRON_DBERR_KBN1'			, '入力されたユーザーは既に登録されているため登録できません。');
define('ERR_MSG_CRON_DBERR_KBN2'			, '入力されたユーザーは存在しないため更新できません。');

define('ERR_MSG_CRON_ERR_INSERT'		, '追加処理に失敗しました。');
define('ERR_MSG_CRON_ERR_UPDATE'		, '更新処理に失敗しました。');

define('ERR_MSG_CRON_MISS_EMPTY'		, '1件もデータが更新されませんでした。従業員番号が入力されていないか、正しい雛形ではありません。');
//define('ERR_MSG_CRON_ERR_NOTAISYOKU'	, '入力された従業員番号は存在しないため、退職・異動処理ができません。');
define('ERR_MSG_CRON_ERR_NOTAISYOKU'	, '入力されたスタッフコードは存在しないため、削除処理ができません。');

// 一括申請
define('ERR_MSG_CRON_NOITEM'			, 'アイテムが一件も入力されていません。');
define('ERR_MSG_CRON_KEIHI'				, '経費の値に誤りがあります。');
define('ERR_MSG_NOUSER'					, 'ユーザーが見つかりませんでした。');
define('ERR_MSG_NO_NUM'					, '着数は数値で入力して下さい');
define('ERR_MSG_CRON_OVER99'			, '着数の総合計は99着以下してください。');
define('ERR_MSG_CRON_HYOUSIKUBUN'		, '制服の雛形が入力されていません。');
define('ERR_MSG_CRON_SOUFUSAKI'			, '送付先を一箇所でも入力した場合、全箇所が必須項目となります。');
define('ERR_MSG_CRON_ZIP'               , '郵便番号は半角数値とハイフンの8文字以内で入力してください。');
define('ERR_MSG_CRON_SIZE_CYAKUSU'		, 'サイズ、着数どちらかを入力した場合、両項目が必須となります。');
define('ERR_MSG_CRON_NOITEMNO'			, 'このアイテムNoは存在しません。');
define('ERR_MSG_CRON_NOSIZE'			, 'このサイズは存在しません。');
define('ERR_MSG_CRON_MISS_HACYU'		, '発注申請に失敗しています。システム管理者に問い合わせてください。');
define('ERR_MSG_CRON_MISS_EMPTY'		, '1件もデータが更新されませんでした。従業員番号が入力されていないか、正しい雛形ではありません。');


// 共通関数部

/*
 * ファイルダウンロード
 * 引数  ：$dlname      => ダウンロード時の表示名
 *       ：$fullpath    => ダウンロードさせるファイルのフルパス(ファイル名を含む)
 * 戻り値：なし
 *
 * create 2007/11/30 DF
 *
 */
function _file_download($dlname,$fullpath){
	$filename = mb_convert_encoding($dlname, "sjis", "euc");
	header("Cache-Control: public");
	header("Pragma: public");
	header("Content-type: application/download");
	// ダウンロードするファイル名を指定
	header("Content-Disposition: attachment; filename={$filename}");
	// オリジナルのファイルを指定
	readfile($fullpath);
	exit;
}
/*
 * 拡張子チェック
 * 引数  ：$filename      => チェックするファイル名
 *       ：$ext　　　　　 　 => TRUEにする拡張子(カンマ区切りで複数指定可能)
 * 戻り値：true/false
 *
 * create 2007/11/30 DF
 *
 */
function _chk_Extension($filename, $ext){
    if($filename != ""){
        // 許可されている拡張子の取り出し
        $ext_arr=split(',',$ext);
        
        // 拡張子チェック
        $tmp = explode(".", strtolower($filename));
        $type = array_pop($tmp);
        
        for($i=0;$i<count($ext_arr);$i++){
            if($type==$ext_arr[$i]) {
                return TRUE;
            }
        }
    }
    return FALSE;
}
/*
 * 即ファイルダウンロード
 * 引数  ：$filename      => ダウンロード時の表示名
 *       ：$data　        => ダウンロードさせるデータ
 * 戻り値：なし
 *
 * create 2008/01/23 DF
 *
 */
function _FAST_FileDownload($filename,$data){

	header("Cache-Control: public");
	header("Pragma: public");
	header("Content-type: application/download");
	// ダウンロードするファイル名を指定
	header("Content-Disposition: attachment; filename={$filename}");
	print $data;
	exit;
}

// デバッグ用
function d($data){
	print "<pre>";
	print_r($data);
	print "</pre>";
}

?>
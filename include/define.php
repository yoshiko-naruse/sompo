<?php
/*
 * 定数定義
 * define.php
 *
 * create 2007/03/12 H.Osugi
 *
 */
// 全てのエラー出力をオフにする
//error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', 0);

// タイムアウト無し
set_time_limit(0);
//-------------------------------------------------------サーバー固有設定ここから--------------------------------------
// DB接続情報
// 本番接続情報
define('DB_SERVER_NAME', '192.168.20.40,49215');		// DB接続サーバ
define('DB_DATABASE_NAME', 'sompocf');					// DB接続データベース名
define('DB_USER_NAME', 'sompocf');						// DB接続ユーザID
define('DB_PASSWORD', 'sompocf');						// DB接続パスワード
define('DB_CHARSET', 'UTF-8');							// DB接続文字コード


// URL
define('HOME_URL', 'http://sompocfdemo.localhost/');
define('HOME_PATH','C:\\www\\sompocfdemo\\');

// PHP.EXE(バッチ用)
define('PHP_CLI_EXE_PATH', 'C:/php/php.exe');

// メール
define('FROM_MAIL', 'unisupply@uniform.co.jp');         // 送信者メールアドレス
define('RETURN_MAIL', 'uno.takashi@sothink.jp');        // リターンメールアドレス

// メールグループ
define('MAIL_GROUP_0', 'uno.takashi@sothink.jp');       // 全てのメールを受け取る
define('MAIL_GROUP_1', '');                         // 発注メールを受け取る
define('MAIL_GROUP_2', '');                         // 交換メールを受け取る
define('MAIL_GROUP_3', '');                         // 返却メールを受け取る
define('MAIL_GROUP_4', '');                         // 特注メールを受け取る
define('MAIL_GROUP_5', '');                         // 退店・店舗移動アラートメールを受け取る
define('MAIL_GROUP_6', '');  // 店舗移動アラートメールを受け取る

// 着払い伝票の送信先メールアドレス
define('DENPYO_MAIL' , '');

//-------------------------------------------------------サーバー固有設定ここまで--------------------------------------

// 一括登録用Excelファイルアップロードディレクトリ
define('BATCH_EXCEL_UPLOAD_DIR', HOME_PATH.'public_html\\mainte/up_file/');
// クリーニング情報登録用Excelファイルアップロードディレクトリ
define('CLEANING_RESULT_EXCEL_UPLOAD_DIR', HOME_PATH.'public_html\\cleaning/up_file/');

// HTMLエンティティの変換方法
define('HTMLENTITY_QUOTE_STYLE', '3');          //HTMLエンティティでのクォートスタイル
define('HTMLENTITY_ENCODE', 'UTF-8');           //HTMLエンティティでの文字エンコード

// ユーザー権限(M_User:UserLvl)
define('USER_AUTH_LEVEL_ADMIN',   '3');         // 管理者
define('USER_AUTH_LEVEL_GENERAL', '1');         // 一般ユーザー

// ユーザー権限(M_User:AdminLvl)
define('USER_AUTH_LEVEL_ITC',     '2');         // 管理者:管理者権限
define('USER_AUTH_LEVEL_HONBU',   '1');         // 管理者:本部権限
define('USER_AUTH_LEVEL_SYONIN',  '0');         // 管理者:承認権限

// 区分値：発注時初回/個別区分
define('KBN_HACHU_SYOKAI', '1');                // 初回発注申請
define('KBN_HACHU_KOBETU', '2');                // 個別発注申請

// 区分値：発注時アイテム表示区分
define('KBN_HACHU_ITEM_OFFICER', '1');          // 役職者用
define('KBN_HACHU_ITEM_COMMON', '2');           // 一般用
define('KBN_HACHU_ITEM_MATERNITY', '3');        // マタニティ用
define('KBN_HACHU_ITEM_ISETAN', '4');           // 伊勢丹用

// 店舗種類
define ('COMPKIND_COMMON', '0');                // 通常店舗
define ('COMPKIND_ISETAN', '1');                // 伊勢丹

// 特殊店舗
define ('EXCEPTIONALSHOP_GENERAL', '1');        // 通常店舗
define ('EXCEPTIONALSHOP_EXCEPTIONAL', '2');    // 特殊店舗

// 伝票種別
define ('DENPYO_SYUBETSU_PELICAN', 'pelican');  // ペリカン
define ('DENPYO_SYUBETSU_SAGAWA', 'sagawa');    // 佐川

define ('DENPYO_SYUBETSU_ARY_JP', serialize(array(
    DENPYO_SYUBETSU_PELICAN => 'ペリカン便用',
    DENPYO_SYUBETSU_SAGAWA =>  '佐川急便用',
)));

// ステータス
define('STATUS_APPLI', '1');                    // 申請済（承認待ち）
define('STATUS_APPLI_DENY', '2');               // 申請済（否認）
define('STATUS_APPLI_ADMIT', '3');              // 申請済（承認済）

define('STATUS_STOCKOUT', '13');                // 在庫切れ
define('STATUS_ORDER', '14');                   // 受注済
define('STATUS_SHIP', '15');                    // 出荷済
define('STATUS_DELIVERY', '16');                // 納品済

define('STATUS_NOT_RETURN', '18');              // 未返却（承認待ち）
define('STATUS_NOT_RETURN_DENY', '19');         // 未返却（否認）
define('STATUS_NOT_RETURN_ADMIT', '20');        // 未返却（承認済）
define('STATUS_NOT_RETURN_ORDER', '21');        // 未返却（受注済）
define('STATUS_RETURN', '22');                  // 返却済

define('STATUS_RETURN_NOT_APPLY', '25');        // 返却未申請

define('STATUS_CANCEL', '30');                  // キャンセル（強制）
define('STATUS_DISPOSAL', '31');                // 廃棄

define('STATUS_LOSS', '32');                    // 紛失（承認待ち）
define('STATUS_LOSS_DENY', '33');               // 紛失（否認）
define('STATUS_LOSS_ADMIT', '34');              // 紛失（承認済）
define('STATUS_LOSS_ORDER', '35');              // 紛失（受注済）

// ステータス（配列）
$DISPLAY_STATUS = array(
                        STATUS_APPLI            => '承認待',       // 1
                        STATUS_APPLI_DENY       => '否認',        // 2
//                      STATUS_APPLI_ADMIT      => '承認済',       // 3
                        STATUS_APPLI_ADMIT      => '申請中',       // 3 承認の概念がないため、名称変更
                        STATUS_STOCKOUT         => '在庫切',       // 13
                        STATUS_ORDER            => '受注済',       // 14
                        STATUS_SHIP             => '出荷済',       // 15
                        STATUS_DELIVERY         => '納品済',       // 16
                        STATUS_NOT_RETURN       => '返却承認待',     // 18
                        STATUS_NOT_RETURN_DENY  => '返却否認',      // 19
                        STATUS_NOT_RETURN_ADMIT => '未返却',       // 20
                        STATUS_NOT_RETURN_ORDER => '未返却',       // 21
                        STATUS_RETURN           => '返却済',       // 22
                        STATUS_RETURN_NOT_APPLY => '返却未申請',     // 25
                        STATUS_CANCEL           => 'キャンセル',     // 30
                        STATUS_DISPOSAL         => '廃棄',          // 31
                        STATUS_LOSS             => '紛失承認待',     // 32
                        STATUS_LOSS_DENY        => '紛失否認',      // 33
                        STATUS_LOSS_ADMIT       => '紛失',            // 34
                        STATUS_LOSS_ORDER       => '紛失'             // 35
);

// ステータス（承認画面用）（配列）
$DISPLAY_STATUS_ACCEPTATION = array(
                            STATUS_APPLI            => '承認待',        // 1
                            STATUS_APPLI_DENY       => '否認',          // 2
                            STATUS_APPLI_ADMIT      => '承認済',        // 3
                            STATUS_NOT_RETURN       => '返却承認待',      // 18
                            STATUS_NOT_RETURN_DENY  => '返却否認',       // 19
                            STATUS_NOT_RETURN_ADMIT => '返却承認済',      // 20
                            STATUS_LOSS             => '紛失承認待',      // 32
                            STATUS_LOSS_DENY        => '紛失否認',       // 33
                            STATUS_LOSS_ADMIT       => '紛失承認済'      // 34
);

// AppliMode
define('APPLI_MODE_ORDER', '1');                    // 発注
define('APPLI_MODE_EXCHANGE', '2');                 // 交換
define('APPLI_MODE_RETURN', '3');                   // 返却

$DISPLAY_APPLI_MODE = array(
                            APPLI_MODE_ORDER    => '発注',
                            APPLI_MODE_EXCHANGE => '交換',
                            APPLI_MODE_RETURN   => '返却'
);


// AppliReason
define('APPLI_REASON_ORDER_BASE', '1');				// 発注（そんぽの家系／ラヴィーレ系）
define('APPLI_REASON_ORDER_GRADEUP', '2');			// 発注（グレードアップタイ）
define('APPLI_REASON_ORDER_FRESHMAN', '3');			// 発注（新入社員※新品優先）
define('APPLI_REASON_ORDER_PERSONAL', '4');         // 発注（個別発注申請）

define('APPLI_REASON_RETURN_RETIRE', '11');			// 返却（退職・異動）
define('APPLI_REASON_RETURN_OTHER', '12');          // 返却（その他）

define('APPLI_REASON_EXCHANGE_FIRST', '20');		// 交換（初回サイズ交換）
define('APPLI_REASON_EXCHANGE_SIZE', '21');			// 交換（サイズ交換）
define('APPLI_REASON_EXCHANGE_INFERIORITY', '22');	// 交換（不良品交換）
define('APPLI_REASON_EXCHANGE_LOSS', '23');			// 交換（紛失交換）
define('APPLI_REASON_EXCHANGE_BREAK', '24');		// 交換（汚損・破損交換）

$DISPLAY_APPLI_REASON = array(
							APPLI_REASON_RETURN_TEIKI          => '新アイテム入替'
					);

// _grobalで使用するため、変数にセット
// AppliReason
$APPLI_REASON_ORDER_BASE            = APPLI_REASON_ORDER_BASE;				// 発注（そんぽの家系／ラヴィーレ系）
$APPLI_REASON_ORDER_GRADEUP         = APPLI_REASON_ORDER_GRADEUP;			// 発注（グレードアップタイ）
$APPLI_REASON_ORDER_FRESHMAN        = APPLI_REASON_ORDER_FRESHMAN;			// 発注（新入社員※新品優先）
$APPLI_REASON_ORDER_PERSONAL        = APPLI_REASON_ORDER_PERSONAL;			// 発注（個別発注申請）

$APPLI_REASON_RETURN_RETIRE         = APPLI_REASON_RETURN_RETIRE;			// 返却（退店）
$APPLI_REASON_RETURN_OTHER          = APPLI_REASON_RETURN_OTHER;			// 返却（その他）

$APPLI_REASON_EXCHANGE_FIRST        = APPLI_REASON_EXCHANGE_FIRST;			// 交換（初回サイズ交換）
$APPLI_REASON_EXCHANGE_SIZE         = APPLI_REASON_EXCHANGE_SIZE;			// 交換（サイズ交換）
$APPLI_REASON_EXCHANGE_INFERIORITY  = APPLI_REASON_EXCHANGE_INFERIORITY;	// 交換（不良失品交換）
$APPLI_REASON_EXCHANGE_LOSS         = APPLI_REASON_EXCHANGE_LOSS;			// 交換（紛交換）
$APPLI_REASON_EXCHANGE_BREAK        = APPLI_REASON_EXCHANGE_BREAK;			// 交換（汚損・破損交換）

// PattenID
define('PATTERNID_JITAKU_LIKE', '1');				// そんぽの家系
define('PATTERNID_HOTEL_LIKE',  '2');				// ラヴィーレ系
define('PATTERNID_GRADEUP_TIE', '3');				// グレードアップタイ
define('PATTERNID_PERSONAL', '4');					// 個別発注申請

// 初回サイズ交換可能期間(出荷から10日間)
define('EXCHANGE_TERM_DAY', '10');					// ガイダンス表示するための日数
define('EXCHANGE_TERM', '-10 day');					// strtotimeの指定文字列

// del
define('DELETE_OFF', '0');                          // 論理削除フラグ
define('DELETE_ON', '1');                           // 論理削除フラグ（削除済）

// WAITフラグ
define('ORDER_WAIT_FLAG', '1');                    // WAITフラグ設定値

// 汎用フラグ
define('COMMON_FLAG_OFF', '0');                     // 汎用フラグOFF
define('COMMON_FLAG_ON', '1');                      // 汎用フラグON

define('JAF_MEDIAWORKS', '210');                    // JAFメディアワークス

// 特寸サイズ名称
define('TOKUSUN_SIZE_NAME', '特');

// エクセルダウンロードで使用
define('ITEM_IJF001', 'IJF001');              // ブルゾン
define('ITEM_IJF002', 'IJF002');              // 長袖シャツ
define('ITEM_IJF003', 'IJF003');              // 半袖シャツ
define('ITEM_IJF004', 'IJF004');              // 冬ズボン
define('ITEM_IJF005', 'IJF005');              // 夏ズボン
define('ITEM_IJF006', 'IJF006');              // 反射ベスト
define('ITEM_IJF007', 'IJF007');              // 帽子
define('ITEM_IJF008', 'IJF008');              // ネクタイ

// ファイルアップロード時のQueueテーブル完了フラグ
define('UPLOAD_FILE_COMP_FLAG_WAIT', '0');
define('UPLOAD_FILE_COMP_FLAG_COMPLETE', '1');
define('UPLOAD_FILE_COMP_FLAG_ERROR', '2');

// 発注時の商品No(役職による交換時)
define('ORDER_ITEM_JACKET_COMMON', '034856');    // 一般ジャケット
define('ORDER_ITEM_JACKET_OFFICER',  '034855');  // 役職ジャケット

// 1ページあたりの表示件数
define('PAGE_PER_DISPLAY_HISTORY', '20');           // 申請履歴の表示件数
define('PAGE_PER_DISPLAY_SEARCH_STAFF', '5');       // 社員検索の表示件数
define('PAGE_PER_DISPLAY_SEARCH_COMP', '5');        // 店舗検索の表示件数
define('PAGE_PER_DISPLAY_USERMASTER', '20');        // スタッフマスタの表示件数

// パスワード変更時の送信先メールアドレス
define('PASSWORD_HENKOU_MAIL' , '');

// 着用者変更時の送信先メールアドレス
define('CHAKUYOUSYA_HENKOU_MAIL' , '');

// メール定型文
define('MAIL_SUBJECT_HEADER', '【テスト】ＳＯＭＰＯケアフーズ　制服管理システム');	// メール件名
define('MAIL_BODY_HEADER', '*** ＳＯＭＰＯケアフーズ　制服管理システム ***');					// メール本文

// マニュアルページのURL
define('MANUAL_URL', '/op_manual.php');

// サイズ表のURL
define('SIZE_URL', '/sizepattern.pdf');
define('SIZE_URL_SS', '/sizepattern_ss.html');		// 春夏用
define('SIZE_URL_FW', '/sizepattern_fw.html');		// 秋冬用

// 着払い伝票の依頼枚数
define('VOUCHER_NUM', '20');

// 届の標準行数
define('BROKEN_DSP_COLS', '12');		// 汚損・破損届の標準行数
define('LOSS_DSP_COLS', '12');			// 紛失届の標準行数

// 元号（元号が変わったら追加してください）
$GENGOU = array();
$GENGOU[0]['name']     = '明治';			// 元号
$GENGOU[0]['startDay'] = '1868/01/25';		// その元号の開始日
$GENGOU[0]['endDay']   = '1912/07/29';		// その元号の終了日
$GENGOU[1]['name']     = '大正';
$GENGOU[1]['startDay'] = '1912/07/30';
$GENGOU[1]['endDay']   = '1926/12/24';
$GENGOU[2]['name']     = '昭和';
$GENGOU[2]['startDay'] = '1926/12/25';
$GENGOU[2]['endDay']   = '1989/01/07';
$GENGOU[3]['name']     = '平成';
$GENGOU[3]['startDay'] = '1989/01/08';
$GENGOU[3]['endDay']   = '';				// 現在の元号の終了日は空にしてください

// 元号が変わったら平成のendDayを追加して新しい元号のnameとstartDayを追加してください。
// $GENGOU[4]['name']     = '';
// $GENGOU[4]['startDay'] = '';
// $GENGOU[4]['endDay']   = '';

// 代行入力時のご担当者の初期値
define('DEFAULT_STAFF_NAME', 'ユニフォーム担当者');

// CSVファイルのファイル名
define('CHAKUYOU_CSV_FILE_NAME', 'chakuyou');		// 着用状況
define('RIREKI_CSV_FILE_NAME', 'rireki');			// 申請履歴
define('ICHIRAN_CSV_FILE_NAME', 'ichiran');			// 着用者一覧
define('SUIIHYO_CSV_FILE_NAME', 'suiihyo');			// 着用者一覧推移表
define('SHINSEI_ODR_CSV_FILE_NAME', 'shinsei_odr');	// 初回申請者一覧
define('SHINSEI_RTN_CSV_FILE_NAME', 'shinsei_rtn');	// 退店・その他申請者一覧
define('USER_RTN_CSV_FILE_NAME', 'user_rtn');       // ログインユーザー一覧
define('CLEANING_CSV_FILE_NAME', 'cleaning_record');  // 洗濯実績一覧

// ログインログファイルのファイルパス
define('LOGIN_FILE_PATH', HOME_PATH.'log\login_log.txt');

//一括発注、一括ユーザー登録Excelクーロン起動用 PHP.EXEとクーロンのパス
define('EXCEL_CURON_KICK_PATH_BYORDER', PHP_CLI_EXE_PATH.' '.HOME_PATH.'public_html\mainte\UserHacyu_Cron.php ');
define('EXCEL_CURON_KICK_PATH_BYSTAFF', PHP_CLI_EXE_PATH.' '.HOME_PATH.'public_html\mainte\UserMaster_Cron.php ');
define('EXCEL_CRON_KICK_PATH_BYCLEANING', PHP_CLI_EXE_PATH.' '.HOME_PATH.'public_html\cleaning\CleaningResult_Cron.php ');

// 初期パスワード
// '00000000'から'AAAAAAAA'に変更 09/04/17 uesugi
define('SYSTEM_DEFAULT_PASSWORD', 'AAAAAAAA');

// パスワード期限 09/04/17 uesugi
// 日数を設定(空欄の場合は無期限)
//define('CHANGE_PASS_EXPDAY' ,'90');
define('CHANGE_PASS_EXPDAY' ,'');

//追加 uesugi 081119
// デモサイトかどうか判別
//デモサイト：1 本番サイト：0
define('SET_DEMO_SITE', '0');

//追加 uesugi 090130
//伊勢丹新宿店　店舗ｺｰﾄﾞ
define('ORDER_ISETAN_SINJUKU', '8010');
//伊勢丹新宿店用申請理由
define('APPLI_REASON_ISETAN_SINJUKU','5');
//ジャケット（伊勢丹既製品）グループID
define('GROUPID_ISETAN_JACKET','3');
//パンツ（伊勢丹既製品）グループID
define('GROUPID_ISETAN_PANTS','4');

// クッキーSECUREモード「１」:本番用（開発時は「0」にする事！）
ini_set( 'session.cookie_secure', 0 );
// PHP のセッション ID に HttpOnly 属性を付与する
ini_set( 'session.cookie_httponly', 1 );

?>

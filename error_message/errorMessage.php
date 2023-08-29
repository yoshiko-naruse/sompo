<?php
/*
 * エラーメッセージ一覧
 * index_error.php
 *
 * create 2007/03/14 H.Osugi
 *
 *
 */

// DB接続失敗時エラー(include/dbConnect.php)
$connectFailed = array();

$connectFailed['001'] = 'DB接続できませんでした。' . "\n" . 'システム管理者にお問い合わせください。';

// staffCode重複チェック(include/checkDuplicateAppli.php)
$duplicateAppli = array();
$duplicateAppli['001'] = '入力された職員コードはすでに申請が行われています。';

// 発注申請(hachu/hachu_shinsei.php)
$hachuShinsei = array();

$hachuShinsei['001'] = '職員コードが入力されていません。';
$hachuShinsei['002'] = '職員コードは半角で入力してください。';
$hachuShinsei['003'] = '職員コードは半角英数字12文字で入力してください。';
$hachuShinsei['004'] = '職員コードは' . COMMON_STAFF_CODE . 'で始まる半角英数字12文字で入力してください。';

$hachuShinsei['011'] = '郵便番号が入力されていません。';
$hachuShinsei['012'] = '郵便番号は半角数値の[3桁]-[4桁]で入力してください。';

$hachuShinsei['021'] = '住所が入力されていません。';
$hachuShinsei['022'] = '住所は全角120文字以内で入力してください。';

$hachuShinsei['031'] = '出荷先名が入力されていません。';
$hachuShinsei['032'] = '出荷先名は全角60文字以内で入力してください。';

$hachuShinsei['041'] = 'ご担当者が入力されていません。';
$hachuShinsei['042'] = 'ご担当者は全角20文字以内で入力してください。';

$hachuShinsei['051'] = '電話番号が入力されていません。';
$hachuShinsei['052'] = '電話番号は半角数値で入力してください。';
$hachuShinsei['053'] = '電話番号は半角数値15文字以内で入力してください。';

$hachuShinsei['061'] = 'メモは全角64文字以内で入力してください。';

$hachuShinsei['071'] = 'アイテムを選択してください。';

$hachuShinsei['081'] = 'サイズが選択されていないアイテムがあります。';

$hachuShinsei['091'] = '数量が入力されていないアイテムがあります。';

$hachuShinsei['092'] = 'スカートとパンツは合計で２着になるように入力してください。';

$hachuShinsei['093'] = '数量は半角数値で入力してください。';

$hachuShinsei['094'] = 'チェックしたアイテムの数量は1以上を入力してください。';

$hachuShinsei['095'] = 'アイテムの数量を入力してください。';

$hachuShinsei['111'] = '出荷指定日が正しい日付ではありません。';
$hachuShinsei['112'] = '出荷指定日に発注入力当日と過去日付は指定できません。';
$hachuShinsei['113'] = '出荷指定日に土曜日と日曜日は指定できません。';

$hachuShinsei['200'] = '貸与パターンを選択してください。';

$hachuShinsei['901'] = '発注申請に失敗しました。' . "\n" . 'システム管理者にお問い合わせください。';


// 交換職員選択（koukan/koukan_sentaku.php）
$koukanSentaku = array();

$koukanSentaku['901'] = '該当の職員は見つかりませんでした。';
$koukanSentaku['902'] = '交換理由が選択されていません。';

// 交換 (koukan/koukan_shinsei.php)
$koukanShinsei = array();

$koukanShinsei['001'] = '郵便番号が入力されていません。';
$koukanShinsei['002'] = '郵便番号は半角数値の[3桁]-[4桁]で入力してください。';

$koukanShinsei['011'] = '住所が入力されていません。';
$koukanShinsei['012'] = '住所は全角120文字以内で入力してください。';

$koukanShinsei['021'] = '出荷先名が入力されていません。';
$koukanShinsei['022'] = '出荷先名は全角60文字以内で入力してください。';

$koukanShinsei['031'] = 'ご担当者が入力されていません。';
$koukanShinsei['032'] = 'ご担当者は全角20文字以内で入力してください。';

$koukanShinsei['041'] = '電話番号が入力されていません。';
$koukanShinsei['042'] = '電話番号は半角数値で入力してください。';
$koukanShinsei['043'] = '電話番号は半角数値15文字以内で入力してください。';

$koukanShinsei['051'] = 'メモは全角64文字以内で入力してください。';
$koukanShinsei['052'] = 'メモを入力してください。';

$koukanShinsei['061'] = '交換するユニフォームが選択されていません。';

$koukanShinsei['071'] = 'サイズが選択されていないアイテムがあります。';

$koukanShinsei['081'] = 'サイズ交換の場合は同じサイズの交換はできません。';
$koukanShinsei['082'] = '同一アイテムは全て同じサイズをご指定ください。';
$koukanShinsei['083'] = '選択した商品に未出荷の商品が存在するため、サイズ交換できません。';

$koukanShinsei['901'] = '交換できるユニフォームはありません。';
$koukanShinsei['902'] = '交換するユニフォームの返却申請が失敗しました。' . "\n" . 'システム管理者にお問い合わせください。';
$koukanShinsei['903'] = '交換するユニフォームの発注申請が失敗しました。' . "\n" . 'システム管理者にお問い合わせください。';
$koukanShinsei['904'] = '選択されたユニフォームは現在交換できません。';
$koukanShinsei['905'] = '先に営業所を検索してください。';

// 返却職員選択（henpin/henpin_sentaku.php）
$henpinSentaku = array();

$henpinSentaku['901'] = '該当の職員は見つかりませんでした。';
$henpinSentaku['902'] = '返却理由が選択されていません。';

// 返却 (henpin/henpin_shinsei.php)
$henpinShinsei = array();

$henpinShinsei['001'] = 'メモを入力してください。';
$henpinShinsei['002'] = 'メモは全角64文字以内で入力してください。';
$henpinShinsei['003'] = '未選択のユニフォームがあります。必ず「返却」「紛失」のどちらかをチェックして下さい。';
$henpinShinsei['004'] = '返却するユニフォームが選択されていません。';

$henpinShinsei['100'] = 'レンタル終了日を入力してください。';
$henpinShinsei['101'] = 'レンタル終了日に存在しない日付が入力されています。';
$henpinShinsei['102'] = 'レンタル終了日は本日以降の日付を入力してください。';

$henpinShinsei['901'] = '返却できるユニフォームはありません。';
$henpinShinsei['902'] = 'ユニフォームの返却申請が失敗しました。' . "\n" . 'システム管理者にお問い合わせください。';
$henpinShinsei['903'] = '選択されたユニフォームは現在返却できません。';

// 申請履歴（rireki/rireki.php）
$rireki = array();

$rireki['901'] = '該当する申請履歴はありませんでした。';
$rireki['902'] = '検索条件を指定してください。';

// キャンセル（rireki/cancel.php）
$cancel = array();

$cancel['901'] = 'キャンセルに失敗しました。' . "\n" . 'システム管理者にお問い合わせください。';
$cancel['902'] = 'キャンセルする情報が取得できませんでした。';

// 返却明細（rireki/henpin_meisai.php）
$henpinMeisai = array();

$henpinMeisai['901'] = '返却明細を表示するための情報が取得できませんでした。';

// 発注明細（rireki/hachu_meisai.php）
$hachuMeisai = array();

$hachuMeisai['901'] = '発注明細を表示するための情報が取得できませんでした。';


// 着用状況（chakuyou/chakuyou.php）
$chakuyou = array();

$chakuyou['901'] = '該当する貸与データはありませんでした。';
$chakuyou['902'] = '検索条件を指定してください。';

// 発注（特寸）（hachu/hachu_tokusun.php）
$hachuTokusun = array();

$hachuTokusun['001'] = '身長が入力されていません。';
$hachuTokusun['002'] = '身長は小数点を含めて半角数値の8桁以内で入力してください。';

$hachuTokusun['011'] = '体重が入力されていません。';
$hachuTokusun['012'] = '体重は小数点を含めて半角数値の8桁以内で入力してください。';

$hachuTokusun['021'] = 'バストが入力されていません。';
$hachuTokusun['022'] = 'バストは小数点を含めて半角数値の8桁以内で入力してください。';

$hachuTokusun['031'] = 'ウエストが入力されていません。';
$hachuTokusun['032'] = 'ウエストは小数点を含めて半角数値の8桁以内で入力してください。';

$hachuTokusun['041'] = 'ヒップが入力されていません。';
$hachuTokusun['042'] = 'ヒップは小数点を含めて半角数値の8桁以内で入力してください。';

$hachuTokusun['051'] = '肩幅が入力されていません。';
$hachuTokusun['052'] = '肩幅は小数点を含めて半角数値の8桁以内で入力してください。';

$hachuTokusun['061'] = '袖丈が入力されていません。';
$hachuTokusun['062'] = '袖丈は小数点を含めて半角数値の8桁以内で入力してください。';

$hachuTokusun['071'] = '首周りが入力されていません。';
$hachuTokusun['072'] = '首周りは小数点を含めて半角数値の8桁以内で入力してください。';

$hachuTokusun['091'] = '着丈が入力されていません。';
$hachuTokusun['092'] = '着丈は小数点を含めて半角数値の8桁以内で入力してください。';

$hachuTokusun['101'] = '裄丈が入力されていません。';
$hachuTokusun['102'] = '裄丈は小数点を含めて半角数値の8桁以内で入力してください。';

$hachuTokusun['111'] = '股下が入力されていません。';
$hachuTokusun['112'] = '股下は小数点を含めて半角数値の8桁以内で入力してください。';

$hachuTokusun['081'] = '特注備考が入力されていません。';
$hachuTokusun['082'] = '特注備考は全角64文字以内で入力してください。';

$hachuTokusun['121'] = 'ヌード寸法または特注備考のどちらかを必ず入力してください。';

// 交換（特寸）（koukan/koukan_tokusun.php）
$koukanTokusun = array();

$koukanTokusun['001'] = '身長が入力されていません。';
$koukanTokusun['002'] = '身長は小数点を含めて半角数値の8桁以内で入力してください。';

$koukanTokusun['011'] = '体重が入力されていません。';
$koukanTokusun['012'] = '体重は小数点を含めて半角数値の8桁以内で入力してください。';

$koukanTokusun['021'] = 'バストが入力されていません。';
$koukanTokusun['022'] = 'バストは小数点を含めて半角数値の8桁以内で入力してください。';

$koukanTokusun['031'] = 'ウエストが入力されていません。';
$koukanTokusun['032'] = 'ウエストは小数点を含めて半角数値の8桁以内で入力してください。';

$koukanTokusun['041'] = 'ヒップが入力されていません。';
$koukanTokusun['042'] = 'ヒップは小数点を含めて半角数値の8桁以内で入力してください。';

$koukanTokusun['051'] = '肩幅が入力されていません。';
$koukanTokusun['052'] = '肩幅は小数点を含めて半角数値の8桁以内で入力してください。';

$koukanTokusun['061'] = '袖丈が入力されていません。';
$koukanTokusun['062'] = '袖丈は小数点を含めて半角数値の8桁以内で入力してください。';

$koukanTokusun['071'] = '首周りが入力されていません。';
$koukanTokusun['072'] = '首周りは小数点を含めて半角数値の8桁以内で入力してください。';

$koukanTokusun['091'] = '着丈が入力されていません。';
$koukanTokusun['092'] = '着丈は小数点を含めて半角数値の8桁以内で入力してください。';

$koukanTokusun['101'] = '裄丈が入力されていません。';
$koukanTokusun['102'] = '裄丈は小数点を含めて半角数値の8桁以内で入力してください。';

$koukanTokusun['111'] = '股下が入力されていません。';
$koukanTokusun['112'] = '股下は小数点を含めて半角数値の8桁以内で入力してください。';

$koukanTokusun['081'] = '特注備考が入力されていません。';
$koukanTokusun['082'] = '特注備考は全角64文字以内で入力してください。';

$koukanTokusun['121'] = 'ヌード寸法または特注備考のどちらかを必ず入力してください。';

// パスワード変更 （change_password.php）
$changePassword = array();

$changePassword['001'] = '現在のパスワードを入力してください。';

$changePassword['011'] = '現在のパスワードが間違っています。ご確認ください。';

$changePassword['021'] = '新しいパスワードを入力してください。';
$changePassword['022'] = '新しいパスワードが新しいパスワード（確認）と一致しません。';
// パスワード変更エラーメッセージ 追加 09/04/08 uesugi
//$changePassword['023'] = '新しいパスワードは半角で6文字～12文字で入力してください。';
$changePassword['023'] = '新しいパスワードは半角英数字で8文字～12文字で入力してください。';
//$changePassword['024'] = '新しいパスワードは初期設定とは異なるものを指定してください。';
$changePassword['024'] = '新しいパスワードは数字、英字の両方を一文字以上含めて入力してください。';
$changePassword['025'] = '新しいパスワードに初期パスワードと異なるパスワードを入力してください。';
$changePassword['026'] = '新しいパスワードに現在のパスワードと異なるパスワードを入力してください。';
$changePassword['027'] = '新しいパスワードにユーザーID文字列を含めずに入力してください。';
$changePassword['901'] = 'パスワードの変更に失敗しました。' . "\n" . 'システム管理者にお問い合わせください。';

// 申請番号重複チェック （include/checkDuplicateAppli.php）
$checkRequestNo = array();

$checkRequestNo['901'] = 'この申請番号ではすでに申請されています。';


// 職員ID重複チェック （include/checkDuplicateStaff.php）
$checkStaffID = array();

$checkStaffID['901'] = 'この職員は現在貸与中です。';


// 営業所検索 （search_comp.php）
$searchComp = array();

$searchComp['901'] = '条件に該当する営業所はありませんでした。';

// 承認処理（syounin/syounin.php）
$syounin = array();

$syounin['001'] = '承認/否認したい申請を選択してください。';
$syounin['011'] = '理由は全角30文字以内で入力してください。';

$syounin['901'] = '該当する申請情報はありませんでした。';
$syounin['902'] = '検索条件を指定してください。';
$syounin['903'] = '承認/否認の処理に失敗しました。' . "\n" . 'システム管理者にお問い合わせください。';

// 承認キャンセル処理（syounin/syounin_cancel.php）
$syouninCancel = array();

$syouninCancel['901'] = 'キャンセルに失敗しました。' . "\n" . 'システム管理者にお問い合わせください。';
$syouninCancel['902'] = 'キャンセルする情報が取得できませんでした。';


// 着用者一覧（admin/chakuyousya_ichiran.php）
$chakuyousyaIchiran = array();
$chakuyousyaIchiran['901'] = '抽出対象となるデータが存在しませんでした。';
$chakuyousyaIchiran['902'] = '検索条件を指定してください。';
$chakuyousyaIchiran['903'] = '日付を指定してください。';
$chakuyousyaIchiran['904'] = 'シーズンを選択してください。';

// 発注一括申請 (mainte/orderupresult.php)
$orderResult['001'] = '削除処理に失敗しました。';
$orderResult['002'] = 'アップロードデータが存在しません。';

// 職員一括申請 (mainte/staffupresult.php)
$staffResult['001'] = '削除処理に失敗しました。';
$staffResult['002'] = 'アップロードデータが存在しません。';

// 職員マスタメンテ (mainte/usermainte_top.php)
$userMainte['001'] = '該当する職員は存在しませんでした。';

$userMainte['011']  = '職員コードが入力されていません。';
$userMainte['012']  = '氏名が入力されていません。';
$userMainte['013']  = '営業所が選択されていません。';
$userMainte['014']  = '人事異動先情報、職員コードが正しくありません。';
$userMainte['015']  = '人事異動先情報、営業所が入力されていません。';
$userMainte['016']  = '入力された職員コードは既に存在します。';
$userMainte['017']  = '更新実施日の日付が正しくありません。';
$userMainte['018']  = '現在貸与中の商品が存在しますので削除できません。';
$userMainte['019']  = '異動先の施設を選択した場合は必ず更新実施日を入力して下さい。';
$userMainte['020']  = '服種が選択されていません。';
$userMainte['021']  = '職員コードは半角英数字12桁です。';

$userMainte['101']  = '新規追加処理に失敗しました。';
$userMainte['102']  = '更新処理に失敗しました。';
$userMainte['103']  = '削除処理に失敗しました。';

$seikyuMeisai['002'] = '集計開始日、集計終了日を入力してください。';
$seikyuMeisai['003'] = '日付はYYYY/MM/DDの形式にて、入力してください。';
$seikyuMeisai['005'] = '対象の請求対象データが存在しません。';

$taiyoList['002'] = '集計日を入力してください。';
$taiyoList['003'] = '集計日はYYYY/MM/DDの形式にて、入力してください。';
$taiyoList['005'] = '対象の請求対象データが存在しません。';

$koukanFee['002'] = '申請日を入力してください。';
$koukanFee['003'] = '申請日（開始）はYYYY/MM/DDの形式にて、入力してください。';
$koukanFee['004'] = '申請日（終了）はYYYY/MM/DDの形式にて、入力してください。';
$koukanFee['005'] = '対象の有償交換データが存在しません。';

// パスワード初期化 （clear_password.php）
// 09/03/25 uesugi
$clearPassword = array();

$clearPassword['001'] = 'ユーザーIDを入力してください。';
$clearPassword['002'] = '入力されたユーザーIDは登録されていません。ご確認ください。';
$clearPassword['901'] = 'パスワードの初期化に失敗しました。' . "\n" . 'システム管理者にお問い合わせください。';

// 定期発注用返却申請明細出力 (teikioutput/teikireturn_excel_dl.php)
$teikiOutput = array();
$teikiOutput['001'] = '会社を選択してください。';
$teikiOutput['002'] = '申請日範囲は必ず指定してください。';
$teikiOutput['003'] = '申請日範囲（開始日）はYY/MM/DD形式で入力して下さい。';
$teikiOutput['004'] = '申請日範囲（終了日）はYY/MM/DD形式で入力して下さい。';
$teikiOutput['005'] = '該当する定期発注用返却申請が存在しません。';
$teikiOutput['006'] = '本部コードを選択して下さい。';

?>

<?php
require_once 'Excel/reader.php';
require_once 'Excel/Writer.php';
require_once 'Excel/reviser.php';

/**
* Excel操作クラス
* Excel_Operation.php
* 概要
* エクセル操作ライブラリを読み込こんでエクセル操作を簡略化
* PEARで作成されているものを抜き出しクラス化しました。以下使用ライブラリ。
* Spreadsheet_Excel_Writer
* Spreadsheet_Excel_Reader
* 履歴
* 	更新内容	更新者	更新日
* 	新規作成 	DF		2007/10/25
*/
//http://iidx.jp/archives/2007/07/phpexcel.html
class Excel_Operation {
	
	/* エクセル読み込みでの保存用 */
	var $rowcnt;
	var $colcnt;
	var $readflg;
	var $filename;
	
	/* エクセル書き込みでの保存用 */
	
	
	function Excel_Operation() {
	
	}
	
	/**
    * Excelファイルを読む関数。
    * 各カラムのデータをHashの配列に格納し返却する。
    * 引数
    * $file_name    : 読み込むエクセルファイル名
	* $flg		    : 配列名のスイッチフラグ　デフォルト：0
	*				: 1　		1行目のレコードを配列名として扱う。
	* 							この場合一行目をレコードとして扱わない
	*				: 2　		配列名を数値の連番とする。
	*							一行目をレコードとして扱う
	*				: 3			東急専用処理(ユーザーマスタテンプレート読み込み）
	*							3行目から読み込みを開始する。
	*				: 4			東急専用処理(一括発注テンプレートを読み込み）
	*							1行目と4行目以降に入っている値を配列に入れ値を返す。
	*							2行目と4行目のなんらかのセルが原因で正しくセルを読み取れない為。
	*				: その他	1と2以外の値を渡すとその渡された値+連番の配列名となる (例：$flg="rec" rec1 rec2 rec3)
    *							一行目をレコードとして扱う
	* $enc　　　    : エンコード(euc-jp,sjis,UTF-8) デフォルトeuc-jp
    * 戻り値        : 配列
	*
	* 更新内容	更新者	更新日
	* 新規作成 	DF		2007/10/25
    */
	function _ExcelRead($file_name,$flg=1,$enc='euc-jp'){
		
		$files = array($file_name);
		$xls = new Spreadsheet_Excel_Reader();
		$xls->setUTFEncoder('mb');
		$xls->setOutputEncoding($enc);
		foreach ($files as $file_name) {
			$xls->read($file_name);
			$k = 1;
			$l = 1;
			for ($i = 1; $i <= $xls->sheets[0]['numRows']; $i++) {
				for ($j = 1; $j <= $xls->sheets[0]['numCols']; $j++) {
					if (!isset($xls->sheets[0]['cells'][$i][$j])) {
						$col_data = "";
					}else{
						$col_data = $xls->sheets[0]['cells'][$i][$j];
					}
					
					if ($col_data == 'END_OF_DATA') break 2;
					// フラグ4の時特殊処理
					if($flg == 4){
						if($i == 1){
							$data[$i][$k] = $col_data;
							$k++;
						}else{
						
							if($i > 3){
								$data[$i - 2][$l] = $col_data;
							}
							if($xls->sheets[0]['numCols'] == $l){
								$l = 1;
							}else{
								if($i > 3){
									$l++;
								}
							}
						}
					}else{
						// フラグ3の時特殊処理
						// 東急専用
						if($flg == 3){
							if($i > 2){
								$data[$i - 3][$k] = $col_data;
							}
							if($xls->sheets[0]['numCols'] == $k){
								$k = 1;
							}else{
								if($i > 2){
									$k++;
								}
							}
					
						}else{
							if ($i == 1) {
								if($flg == 1){
									$column_names[$j - 1] = $col_data;
								}else if($flg == 1){
									$column_names[$j - 1] = $j;
									$data[$i - 1][$column_names[$j - 1]] = $col_data;
								}else{
									$column_names[$j - 1] = $flg.$j;
									$data[$i - 1][$column_names[$j - 1]] = $col_data;
								}
							} else {
								$data[$i - 1][$column_names[$j - 1]] = $col_data;
							}
						}
					}
				}
			}
		}
		
		// 情報保持
		$this->rowcnt   = $xls->sheets[0]['numRows'];
		$this->colcnt   = $xls->sheets[0]['numCols'];
		$this->filename = $file_name;
		$this->readflg  = $flg;
		
		return $data;
	}
	// 読み込んだエクセルの列の最大値を返す
	function _read_row_max(){
		return $this->rowcnt;
	}
	// 読みこんだエクセルの行の最大値を返す。フラグを見て数値を変化。
	function _read_col_max(){
		if($this->readflg == 1){
			return $this->colcnt - 1;
		}
		return $this->colcnt;
	}
	/**
 	* Excelファイルを書き込む関数。
	* 各カラムのデータをHashの配列に格納し返却する。
	* 1行目のデータはカラム名として扱う。
	* 2行目移行のデータをレコードとして扱います。
	* 引数
	* $data			: エクセルに書き込むデータ配列 例:このような形の配列を渡す。array('0'=>array('AAA' => '01','AAA' => '01'))
	* $savefile		: エクセルファイル名
	* $flg			: 1		ファイルをダウンロードさせる
	* 				: 2		ファイルを指定フォルダに保存
	* $path			: ファイル保存時に指定、指定パスへファイルを作成する。
	* $encデフォルト:shift_jis
	* $file_name    : 読み込む配列
	* $enc　　　    : エンコードデフォルトshift_jis
	* 戻り値        : bool
	* 参考 http://phpspot.net/php/man/pear/package.fileformats.spreadsheet-excel-writer.html
	*
	* 更新内容	更新者	更新日
	* 新規作成 	DF		2007/10/25
	*/
   
	function _Excelwriter($data,$savefile,$flg=1,$path="",$enc="shift_jis",$startrow=0){
		
		if(is_array($data)){
			# ファイル保存時はパスを渡す
			if($flg==1){
				$workbook = new Spreadsheet_Excel_Writer();
			}else{
				$workbook = new Spreadsheet_Excel_Writer($path.$savefile);
			}
			$tablename = "sampletable";
			$worksheet =& $workbook->addWorksheet($tablename);

			$format =& $workbook->addFormat();
			$format->_font_name = mb_convert_encoding("MS UI Gothic", $enc,"auto");
	
			$i=0 + $startrow;
			$j=0;
			foreach($data as $key=>$val){
				foreach($val as $key2=>$val2){
					$worksheet->write($i,$j,mb_convert_encoding($val2, $enc,"auto")); // セルに書き込み
					$j++;
				}
				$j=0;
				$i++;
			}
		}else{
			return false;
		}
		if($flg==1){
			// ファイルダウンロードとなる
		 	$workbook->send($savefile);
		}
		$workbook->close();
		return true;
	}
	/**
 	* Exceltemplateにファイルを書き込む関数。
	* 各カラムのデータをHashの配列に格納し返却する。
	* 1行目のデータはカラム名として扱う。
	* 2行目移行のデータをレコードとして扱います。
	* 引数
	* $data		　　	: エクセルに書き込むデータ配列 例:このような形の配列を渡す。array('0'=>array('1' => '01','2' => '01'))
	* $templatefile		: テンプレートとなるエクセルファイル名
	* $downloadname	　　: ファイルをダウンロードさせる名前
	* $startrow			: 書き込み開始行
	* $path		 		: パスを入れることでその場所に保存する。指定しない場合は書き込んでダウンロードとなります。
	* $encデフォルト:shift_jis
	* 戻り値        : bool
	* 参考 http://d.hatena.ne.jp/reviser/?of=20
	*
	* 更新内容	更新者	更新日
	* 新規作成 	DF		2007/12/03
	*/
	function _template_write($data,$templatefile,$downloadname,$startrow,$path=""){

		$reviser= new Excel_Reviser;
		
		$i=0 + $startrow -1;
		$j=0;
		foreach($data as $key=>$val){
			foreach($val as $key2=>$val2){
# 				if($val2=="EOF"){
# 				$reviser->addNumber(0,$i,$j,1); // セルに書き込み
# 				$j++;
# 				}else{
				$reviser->addString(0,$i,$j,$val2); // セルに書き込み
				$j++;
# 				}
			}
			$j=0;
			$i++;
			

			
		}
		if($path == ""){
			$reviser->reviseFile($templatefile,$downloadname);
		}else{
			$reviser->reviseFile($templatefile,$downloadname,$path);
		}
	}

}
?>
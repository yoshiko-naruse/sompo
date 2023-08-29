<?php
require_once 'Excel/reader.php';
require_once 'Excel/Writer.php';
require_once 'Excel/reviser.php';

/**
* Excel����N���X
* Excel_Operation.php
* �T�v
* �G�N�Z�����색�C�u������ǂݍ�����ŃG�N�Z��������ȗ���
* PEAR�ō쐬����Ă�����̂𔲂��o���N���X�����܂����B�ȉ��g�p���C�u�����B
* Spreadsheet_Excel_Writer
* Spreadsheet_Excel_Reader
* ����
* 	�X�V���e	�X�V��	�X�V��
* 	�V�K�쐬 	DF		2007/10/25
*/
//http://iidx.jp/archives/2007/07/phpexcel.html
class Excel_Operation {
	
	/* �G�N�Z���ǂݍ��݂ł̕ۑ��p */
	var $rowcnt;
	var $colcnt;
	var $readflg;
	var $filename;
	
	/* �G�N�Z���������݂ł̕ۑ��p */
	
	
	function Excel_Operation() {
	
	}
	
	/**
    * Excel�t�@�C����ǂފ֐��B
    * �e�J�����̃f�[�^��Hash�̔z��Ɋi�[���ԋp����B
    * ����
    * $file_name    : �ǂݍ��ރG�N�Z���t�@�C����
	* $flg		    : �z�񖼂̃X�C�b�`�t���O�@�f�t�H���g�F0
	*				: 1�@		1�s�ڂ̃��R�[�h��z�񖼂Ƃ��Ĉ����B
	* 							���̏ꍇ��s�ڂ����R�[�h�Ƃ��Ĉ���Ȃ�
	*				: 2�@		�z�񖼂𐔒l�̘A�ԂƂ���B
	*							��s�ڂ����R�[�h�Ƃ��Ĉ���
	*				: 3			���}��p����(���[�U�[�}�X�^�e���v���[�g�ǂݍ��݁j
	*							3�s�ڂ���ǂݍ��݂��J�n����B
	*				: 4			���}��p����(�ꊇ�����e���v���[�g��ǂݍ��݁j
	*							1�s�ڂ�4�s�ڈȍ~�ɓ����Ă���l��z��ɓ���l��Ԃ��B
	*							2�s�ڂ�4�s�ڂ̂Ȃ�炩�̃Z���������Ő������Z����ǂݎ��Ȃ��ׁB
	*				: ���̑�	1��2�ȊO�̒l��n���Ƃ��̓n���ꂽ�l+�A�Ԃ̔z�񖼂ƂȂ� (��F$flg="rec" rec1 rec2 rec3)
    *							��s�ڂ����R�[�h�Ƃ��Ĉ���
	* $enc�@�@�@    : �G���R�[�h(euc-jp,sjis,UTF-8) �f�t�H���geuc-jp
    * �߂�l        : �z��
	*
	* �X�V���e	�X�V��	�X�V��
	* �V�K�쐬 	DF		2007/10/25
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
					// �t���O4�̎����ꏈ��
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
						// �t���O3�̎����ꏈ��
						// ���}��p
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
		
		// ���ێ�
		$this->rowcnt   = $xls->sheets[0]['numRows'];
		$this->colcnt   = $xls->sheets[0]['numCols'];
		$this->filename = $file_name;
		$this->readflg  = $flg;
		
		return $data;
	}
	// �ǂݍ��񂾃G�N�Z���̗�̍ő�l��Ԃ�
	function _read_row_max(){
		return $this->rowcnt;
	}
	// �ǂ݂��񂾃G�N�Z���̍s�̍ő�l��Ԃ��B�t���O�����Đ��l��ω��B
	function _read_col_max(){
		if($this->readflg == 1){
			return $this->colcnt - 1;
		}
		return $this->colcnt;
	}
	/**
 	* Excel�t�@�C�����������ފ֐��B
	* �e�J�����̃f�[�^��Hash�̔z��Ɋi�[���ԋp����B
	* 1�s�ڂ̃f�[�^�̓J�������Ƃ��Ĉ����B
	* 2�s�ڈڍs�̃f�[�^�����R�[�h�Ƃ��Ĉ����܂��B
	* ����
	* $data			: �G�N�Z���ɏ������ރf�[�^�z�� ��:���̂悤�Ȍ`�̔z���n���Barray('0'=>array('AAA' => '01','AAA' => '01'))
	* $savefile		: �G�N�Z���t�@�C����
	* $flg			: 1		�t�@�C�����_�E�����[�h������
	* 				: 2		�t�@�C�����w��t�H���_�ɕۑ�
	* $path			: �t�@�C���ۑ����Ɏw��A�w��p�X�փt�@�C�����쐬����B
	* $enc�f�t�H���g:shift_jis
	* $file_name    : �ǂݍ��ޔz��
	* $enc�@�@�@    : �G���R�[�h�f�t�H���gshift_jis
	* �߂�l        : bool
	* �Q�l http://phpspot.net/php/man/pear/package.fileformats.spreadsheet-excel-writer.html
	*
	* �X�V���e	�X�V��	�X�V��
	* �V�K�쐬 	DF		2007/10/25
	*/
   
	function _Excelwriter($data,$savefile,$flg=1,$path="",$enc="shift_jis",$startrow=0){
		
		if(is_array($data)){
			# �t�@�C���ۑ����̓p�X��n��
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
					$worksheet->write($i,$j,mb_convert_encoding($val2, $enc,"auto")); // �Z���ɏ�������
					$j++;
				}
				$j=0;
				$i++;
			}
		}else{
			return false;
		}
		if($flg==1){
			// �t�@�C���_�E�����[�h�ƂȂ�
		 	$workbook->send($savefile);
		}
		$workbook->close();
		return true;
	}
	/**
 	* Exceltemplate�Ƀt�@�C�����������ފ֐��B
	* �e�J�����̃f�[�^��Hash�̔z��Ɋi�[���ԋp����B
	* 1�s�ڂ̃f�[�^�̓J�������Ƃ��Ĉ����B
	* 2�s�ڈڍs�̃f�[�^�����R�[�h�Ƃ��Ĉ����܂��B
	* ����
	* $data		�@�@	: �G�N�Z���ɏ������ރf�[�^�z�� ��:���̂悤�Ȍ`�̔z���n���Barray('0'=>array('1' => '01','2' => '01'))
	* $templatefile		: �e���v���[�g�ƂȂ�G�N�Z���t�@�C����
	* $downloadname	�@�@: �t�@�C�����_�E�����[�h�����閼�O
	* $startrow			: �������݊J�n�s
	* $path		 		: �p�X�����邱�Ƃł��̏ꏊ�ɕۑ�����B�w�肵�Ȃ��ꍇ�͏�������Ń_�E�����[�h�ƂȂ�܂��B
	* $enc�f�t�H���g:shift_jis
	* �߂�l        : bool
	* �Q�l http://d.hatena.ne.jp/reviser/?of=20
	*
	* �X�V���e	�X�V��	�X�V��
	* �V�K�쐬 	DF		2007/12/03
	*/
	function _template_write($data,$templatefile,$downloadname,$startrow,$path=""){

		$reviser= new Excel_Reviser;
		
		$i=0 + $startrow -1;
		$j=0;
		foreach($data as $key=>$val){
			foreach($val as $key2=>$val2){
# 				if($val2=="EOF"){
# 				$reviser->addNumber(0,$i,$j,1); // �Z���ɏ�������
# 				$j++;
# 				}else{
				$reviser->addString(0,$i,$j,$val2); // �Z���ɏ�������
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
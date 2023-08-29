                      ionCube Loader - バージョン 2.1
											--------------------------------

このパッケージには、お客様が選択したPHPバージョンとOSで使用できる最新のローダー
が含まれております。また、文中のローダーとは、ionCube Loaderを指します。

まず以下のコンテンツが含まれているか確認して下さい。

* ローダー

* ランタイムローディングのサポートテストスクリプト。
（ioncube-rtl-tester.phpファイルです）

* php.ini インストールアシスタントスクリプト。
(ioncube-install-assistant.phpファイルです)

* ローダーとエンコードファイル使用のライセンス。
（LICENSEファイルです）


インストール
------------

* ランタイムローディングの為のインストール

ランタイムローディングはエンコードされたファイルを実行する為の最も簡単な方法です
ので、適宜エンコードされたファイルを適切なローダーにインストールしましょう。

ターゲットシステムでランタイムローディングがサポートされているのであれば、ユーザ
ーはローダーをインストールしたりシステム構成をアップデートしたり、あるいは使用し
ているPHPバージョンやOSを知らなくても、エンコードファイルとローダーが組み合わさ
れます。

ランタイムローディングを有効にするには、ローダーを含んでいる'ionucube'というディ
レクトリを、エンコードファイルの最上層ディレクトリの中あるいはそれより上の階層に
置いて下さい。例えば、エンコードされたファイルが'/var/www/htdocs/'というディレク
トリにあったとした場合、'ioncube'ディレクトリを'/var/www/htdocs' か '/var/www'に
置いて下さい。もしそこにお使いのアプリケーションやライブラリがあっても、ioncube
ディレクトリはプロジェクトやライブラリの最上層ディレクトリに置くことができます。


動きません---原因は？
-----------------------

エンコードファイルがランタイムローディングで実行ない場合は、本パッケージに含まれ
ている'ioncube-rtl-tester.php'ファイルを使って以下のテストを行ってみて下さい。

1. 'ioncube-rtl-tester.php'スクリプトファイルを、エンコードファイルを動かしたい
ディレクトリの中にコピーして下さい。

2. そのスクリプトファイルにWebサーバーかPHP cliあるいはCGIでアクセスして下さい。

3. スクリプトは指定されたローダーにインストールしようとします。そして成功したら
問題の原因が出力されます。

4. スクリプトからの出力が問題の解決にならなかった場合は、support@ioncube.jpまで
その出力結果とともにメールを下さい。


* php.ini ファイルにインストールする

php.iniファイルにインストールする方法も同様に簡単で、エンコードファイルの実行を
最適化させます。またセーフモードを使用しているシステム、もしくは PHP がスレッド
をサポートした状態で構築されている (Windows 環境など) の場合は必要になります。

'ioncube-install-assistant.php'ファイルがこのインストールのお手伝いをします。こ
のファイルにWebサーバーかPHP cliあるいはCGIでアクセスして下さい。するとどのロー
ダーにインストールするか、どのファイルを編集するか、何を追記するべきか（1行だけ
編集が必要です）を自動的に表示します。

このアシスタントスクリプトなしでインストールを進める場合は、以下の項をお読み下さ
い。

* php.ini にインストールする

インストールの前に以下の点を御確認下さい。

1) お客様がお使いのOSについて。

2) お客様がお使いのPHPのバージョンについて。

3) PHPはスレッド化されたものかどうかについて？

4) php.iniファイルの場所について。

phpinfo(1)を呼び出すと、必要な情報を教えてくれます。

例：

	PHP Version => 4.3.0
	System => Linux pod 2.2.16 #1 Sat Sep 30 22:47:40 BST 2000 i686
	Build Date => May 28 2003 13:41:42
	Configure Command =>  './configure'
	Server API => Command Line Interface
	Virtual Directory Support => disabled
	Configuration File (php.ini) Path => /usr/local/lib/php.ini
	PHP API => 20020918
	PHP Extension => 20020429
	Zend Extension => 20021010
	Debug Build => no
	Thread Safety => disabled

これによって以下のことがわかります:

1) OSが Linuxであること。

2) PHPのバージョンは4.3.0であること。

3) PHPはスレッド化されていないということ。

4) php.iniファイルが /usr/local/lib  にあること。

ここでLinuxではない方の為に以下の項目を用意しました。

* UNIXをお使いなら
----------------

PHPがスレッド化されていない場合、ローダーを以下のように呼び出して下さい：

ioncube_loader_<OSの種類>_<お使いのPHPのバージョン>.so

PHPがスレッド化されている場合、ローダーを以下のように呼び出して下さい：

ioncube_loader_<OSの種類>_<お使いのPHPのバージョン>_ts.so

<OSの種類>は'lin' は Linuxを、 'fre' は FreeBSDを、'sun' は Solarisをそれぞれ表
しています。

<お使いのPHPのバージョン>は4.1や4.2、4.3といった、使用しているPHPバージョンの最
初の2文字を入力します。

そして、php.ini ファイルを修正し、他の zend_extension 項目よりも先に、以下の通り
に記述します。

zend_extension = /<パス>/ioncube_loader_<OSの種類>_<お使いのPHPのバージョン>.s

ただし、Threaded Safety 機能をもつ PHP の場合は以下の通り記述します。

zend_extension_ts = /<パス>/ioncube_loader_<OSの種類>_<お使いのPHPのバージョン
>_ts.so

<OSの種類> と <お使いのPHPのバージョン>はお使いのシステムのOSの種類とPHPのバージ
ョンについて、また<パス>はローダーがインストールされている場所を適宜入れ替えて下
さい。

例 /usr/local/ioncube

例えば：

nuxにPHP4.1.2とApache1を走らせている場合は以下のように追加します：

	zend_extension = /usr/local/ioncube/ioncube_loader_lin_4.1.so

FreeBSDにPHP4.3.1とApache2を走らせている場合は以下のように追加します：

	zend_extension_ts = /usr/local/ioncube/ioncube_loader_fre_4.3_ts.so


* Windowsをお使いなら
-------------------

ローダーを以下のように呼び出して下さい：

	ioncube_loader_win_<お使いのPHPのバージョン>.dll

<お使いのPHPのバージョン>は4.1や4.2、4.3といった、使用しているPHPバージョンの最
初の2文字を入力します。

php.iniファイルに以下のように加えます：

	zend_extension_ts = <ドライブ>:\<パス>\ioncube_loader_win_<お使いのPHPのバージ
ョン>.dll


<ドライブ> および <パス> は、ionCube ローダーの場所を指定します。また、<お使いの
PHPのバージョン> には、あなたのシステムで使用している PHP のバージョンを指定しま
す。


例えば：

	zend_extension_ts = c:\WINNT\ioncube_loader_win_4.3.dll



Copyright (c) 2003- アシアル株式会社ionCube Team        最終更新日2003年8月10日


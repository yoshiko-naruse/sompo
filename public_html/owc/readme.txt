
  OPTIMA Web Compiler 2.1.7

-------------------------------------------------------------------------------

このたびは OPTIMA Web Compiler をダウンロードいただき、
誠にありがとうございます。
このドキュメントには、OPTIMA Web Compiler (以下"OWC")
のインストール方法および使用方法が記載されております。
利用規約、ライセンスについては別添のドキュメントをご覧ください。

■インストール方法-------------------------------------------------------------

OWCはPHPが動作するサーバにインストールします。
ダウンロードしたアーカイブ (owc-2.x.x-xxx.zip) を解凍したものをディレクトリごと
FTPクライアントで以下のようにアップロードし、パーミッションを設定してください。

【例】※()内の数字はパーミッションを表します。

[/home/hoge/] (705)
	+ [public_html] (707)
		+ [owc] (705)
		+ [ioncube] (705)

OWCはコンパイル時にPHPソースの構文エラーチェックを行うことができます。
構文エラーチェック機能を使いたい場合は、[owc]ディレクトリ内の[config.php]に
PHPパーサのパスを設定してください。

以上でインストールは完了です。

■使用方法---------------------------------------------------------------------

【ソースファイル・テンプレートファイルのアップロード】
PHPソースファイル (xxx.src.php) およびテンプレートファイル (xxx.html)
をOWCがインストールされたサーバにアップロードします。

【コンパイル】
OWCはウェブブラウザ上で操作します。
ウェブブラウザでインストールしたディレクトリ[owc]にアクセスしてください。

【例】
http://www.example.com/owc/

ディレクトリツリーとアップロードされているPHPソースファイル (xxx.src.php)
が表示されます。
コンパイルしたいファイルを選択し、
[コンパイル開始]ボタンをクリックしてください。

【コンパイルしたPHPの動作確認】
xxx.src.php をコンパイルすると、実行ファイル xxx.php が生成されます。
この実行ファイルにアクセスし、表示および動作を確認してください。

【キーボードショートカット】
OWCはウェブブラウザのアクセスキーに対応しております。
※以下はWindows版Internet ExplorerおよびFirefoxの例です。
ご利用のウェブブラウザに合わせて適宜読み替えてください。

[Alt]+[T]	ファイル検索フィールドにフォーカス
[Alt]+[F]	ファイル検索を実行/ディレクトリツリーを更新
[Alt]+[C]	選択したファイルをコンパイル開始

-------------------------------------------------------------------------------

2006/11/2

OPTIMA | 株式会社ブレインウェーブ
http://optima.bwave.co.jp/

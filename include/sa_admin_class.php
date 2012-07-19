<?php

/******************************************************************************
 * 管理画面のクラス
 *****************************************************************************/
class SimpleAmazonAdmin {

	private $cache;
	private $options;

	/**
	 * @param	none
	 * @return	object $this
	 */
	public function __construct() {

		global $simple_amazon_options;

		$this->options = $simple_amazon_options;
		$this->cache = new SimpleAmazonCacheControl();
	}

	/**
	 * @brief	管理画面のhtmlを生成する
	 * @param	none
	 * @return	string 管理画面のhtml
	 */
	public function simple_amazon_options_page() {

		$message = "";

		if ( $_POST['action'] == 'save_options' ){
			$this->simple_amazon_save_options();
			$message .= '<div class="updated"><p><strong>設定を保存しました。</strong></p></div>' . "\n"; 
		}

/*
		if ( $_POST['action'] == 'clear_cache' ){
			$this->cache->clear();
			$message .= '<div class="updated"><p><strong>キャッシュを削除しました。</strong></p></div>' . "\n"; 
		}
*/

		switch( $this->options['windowtarget']) {
			case newwin: $newwindow = ' checked'; $selfwindow = ''; break;
			default: $newwindow = ''; $selfwindow = ' checked';
		}

		switch( $this->options['imgsize'] ) {
			case small: $s_imgsize = ' checked'; $m_imgsize = ''; $l_imgsize = ''; break;
			case large: $s_imgsize = ''; $m_imgsize = ''; $l_imgsize = ' checked'; break;
			default: $s_imgsize = ''; $m_imgsize = ' checked'; $l_imgsize = '';
		}

		switch( $this->options['layout_type'] ) {
			case 1: $default_layout = ''; $medium_layout = ' checked'; $simple_layout = ''; $noimage_layout = ''; break;
			case 2: $default_layout  = ''; $medium_layout = ''; $simple_layout = ' checked'; $noimage_layout = ''; break;
			case 3: $default_layout  = ''; $medium_layout = ''; $simple_layout = ''; $noimage_layout = ' checked'; break;
			default: $default_layout  = ' checked';$medium_layout = '';  $simple_layout = ''; $noimage_layout = '';
		}

		switch( $this->options['setcss']) {
			case yes: $setcss_yes = ' checked'; $setcss_no = ''; break;
			default: $setcss_yes = ''; $setcss_no = ' checked';
		}

		switch( $this->options['delete_setting']) {
			case yes: $delete_setting_yes = ' checked'; $delete_setting_no = ''; break;
			default: $delete_setting_yes = ''; $delete_setting_no = ' checked';
		}

		// 管理画面のテンプレート
		$simple_amazon_admin_html =
			'<div class="wrap" id="footnote-options">' . "\n".
			'<h2>Simple Amazon プラグイン設定</h2>' . "\n";

		if ( $message ){
			$simple_amazon_admin_html .= $message; 
		}

		// cacheディレクトリが設定されているかチェック
		$simple_amazon_admin_html .= $this->cache->is_error();

		if( ! $this->options['accesskeyid'] ) {
			$simple_amazon_admin_html .= '<div class="error"><p><strong>Access Key ID</strong> を設定して下さい。</p></div>' . "\n";
		}

		if( ! $this->options['secretaccesskey'] ) {
			$simple_amazon_admin_html .= '<div class="error"><p><strong>Secret Access Key</strong> を設定して下さい。</p></div>' . "\n";
		}

		$check_associatesid = 
			$this->options['associatesid_ca'] .
			$this->options['associatesid_cn'] .
			$this->options['associatesid_de'] .
			$this->options['associatesid_es'] .
			$this->options['associatesid_fr'] .
			$this->options['associatesid_it'] .
			$this->options['associatesid_jp'] .
			$this->options['associatesid_uk'] .
			$this->options['associatesid_us'];

		if( !$check_associatesid ) {
			$simple_amazon_admin_html .= '<div class="error"><p><strong>アソシエイト ID</strong> を設定して下さい。</p></div>' . "\n";
		}

		$simple_amazon_admin_html .=
			'<form method="post" action="' . $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING'] . '">' .
			'<input type="hidden" name="action" value="save_options" />' .
			'<h3>基本設定</h3>' . "\n" .
			'<table class="form-table">' . "\n" .

			'<tr>' . "\n" .
			'<th>Access Key ID</th>' . "\n" .
			'<td><input type="text" size="22" name="accesskeyid" value="' . $this->options['accesskeyid'] . '" /></td>' . "\n" .
			'</tr>' . "\n" .

			'<tr>' . "\n" .
			'<th>Secret Access Key</th>' . "\n" .
			'<td><input type="text" size="42" name="secretaccesskey" value="' . $this->options['secretaccesskey'] . '" /></td>' . "\n" .
			'</tr>' . "\n" .

			'</table>' . "\n" .

			'<h3>アソシエイト ID</h3>' . "\n" .
			'<table class="form-table">' . "\n";

		// international mode が設定されている場合は設定されている国だけ表示する
		if( in_array('ca', $this->options['imode']) ) {
		$simple_amazon_admin_html .=
			'<tr>' .
			'<th>CA (カナダ)</th>' .
			'<td><input type="text" name="associatesid_ca" value="' . $this->options['associatesid_ca'] . '" /></td>' .
			'</tr>' . "\n";
		}

		if( in_array('cn', $this->options['imode']) ) {
		$simple_amazon_admin_html .=
			'<tr>' .
			'<th>CN (中国)</th>' .
			'<td><input type="text" name="associatesid_cn" value="' . $this->options['associatesid_cn'] . '" /></td>' .
			'</tr>' . "\n";
		}

		if( in_array('de', $this->options['imode']) ) {
		$simple_amazon_admin_html .=
			'<tr>' .
			'<th>DE (ドイツ)</th>' .
			'<td><input type="text" name="associatesid_de" value="' . $this->options['associatesid_de'] . '" /></td>' .
			'</tr>' . "\n";
		}

		if( in_array('es', $this->options['imode']) ) {
		$simple_amazon_admin_html .=
			'<tr>' .
			'<th>ES (スペイン)</th>' .
			'<td><input type="text" name="associatesid_es" value="' . $this->options['associatesid_es'] . '" /></td>' .
			'</tr>' . "\n";
		}

		if( in_array('fr', $this->options['imode']) ) {
		$simple_amazon_admin_html .=
			'<tr>' .
			'<th>FR (フランス)</th>' .
			'<td><input type="text" name="associatesid_fr" value="' . $this->options['associatesid_fr'] . '" /></td>' .
			'</tr>' . "\n";
		}

		if( in_array('it', $this->options['imode']) ) {
		$simple_amazon_admin_html .=
			'<tr>' .
			'<th>it (イタリア)</th>' .
			'<td><input type="text" name="associatesid_it" value="' . $this->options['associatesid_it'] . '" /></td>' .
			'</tr>' . "\n";
		}

		if( !isset($this->options['imode']) || in_array('jp', $this->options['imode']) ) {
		$simple_amazon_admin_html .=
			'<tr>' .
			'<th>JP (日本)</th>' .
			'<td><input type="text" name="associatesid_jp" value="' . $this->options['associatesid_jp'] . '" /></td>' .
			'</tr>' . "\n";
		}

		if( in_array('uk', $this->options['imode']) ) {
		$simple_amazon_admin_html .=
			'<tr>' .
			'<th>UK (イギリス)</th>' .
			'<td><input type="text" name="associatesid_uk" value="' . $this->options['associatesid_uk'] . '" /></td>' .
			'</tr>' . "\n";
		}

		if( in_array('us', $this->options['imode']) ) {
		$simple_amazon_admin_html .=
			'<tr>' .
			'<th>US (アメリカ)</th>' .
			'<td><input type="text" name="associatesid_us" value="' . $this->options['associatesid_us'] . '" /></td>' .
			'</tr>' . "\n";
		}

		$simple_amazon_admin_html .=
			'</table>' . "\n" .

			'<h3>オプション設定</h3>' . "\n" .
			'<table class="form-table">' . "\n" .

			'<tr><th>商品リンクの動作</th>' . "\n" .
			'<td><input type="radio" name="windowtarget" value="self"' . $selfwindow . ' />&nbsp;同じウィンドウ ( target 指定なし )<br />' . "\n" .
			'<input type="radio" name="windowtarget" value="newwin"' . $newwindow . ' />&nbsp;新規ウィンドウ ( target="_blank" )</td>' . "\n" .
			'</tr>' . "\n" .

			'<tr><th>商品詳細の表示項目</th>' . "\n" .
			'<td><input type="radio" name="layout_type" value="0"' . $default_layout . ' />&nbsp;Full ( 画像、タイトル、出版社、発売時期、著者、価格、本のタイプ、ページ数、ISBN。本以外はこれに準ずる項目 )<br />' . "\n" .
			'<input type="radio" name="layout_type" value="1"' . $medium_layout . ' />&nbsp;Detail ( 画像、タイトル、出版社、著者、発売時期。Fullから価格情報、コード情報を省略 )<br />' . "\n" .
			'<input type="radio" name="layout_type" value="2"' . $simple_layout . ' />&nbsp;Title &amp; Image ( 画像とタイトルのみ )<br />' . "\n" .
			'<input type="radio" name="layout_type" value="3"' . $noimage_layout . ' />&nbsp;Title ( タイトルのみ )</td>' . "\n" .
			'</tr>' . "\n" .

			'<tr><th>商品画像のサイズ</th>' . "\n" .
			'<td><input type="radio" name="imgsize" value="small"' . $s_imgsize . ' />&nbsp;Small ( 最大 75 x 75px )<br />' . "\n" .
			'<input type="radio" name="imgsize" value="medium"' . $m_imgsize . ' />&nbsp;Medium ( 最大 160 x 160px )<br />' . "\n" .
			'<input type="radio" name="imgsize" value="large"' . $l_imgsize . ' />&nbsp;Large ( 最大 500 x 500px )</td>' . "\n" .
			'</tr>' . "\n" .

			'<tr><th>CSSの読み込み</th>' . "\n" .
			'<td><input type="radio" name="setcss" value="no"' . $setcss_no . ' />&nbsp;style.css ( デフォルトのCSSのみ使用 )<br />' . "\n" .
			'<input type="radio" name="setcss" value="yes"' . $setcss_yes . ' />&nbsp;simple-amazon.css ( Simple Amazon付属のCSSを使用 )</td>' . "\n" .
			'</tr>' . "\n" .

			'<tr><th>アンインストール時の処理</th>' . "\n" .
			'<td><input type="radio" name="delete_setting" value="no"' . $delete_setting_no . ' />&nbsp;設定を残す<br />' . "\n" .
			'<input type="radio" name="delete_setting" value="yes"' . $delete_setting_yes . ' />&nbsp;設定を削除する</td>' . "\n" .
			'</tr>' . "\n" .

			'</table>' . "\n" .
			'<p class="submit"><input type="submit" name="Submit" value="設定を保存 &raquo;" /></p>' . "\n" .
			'</form>' . "\n";

		echo $simple_amazon_admin_html;
	}

	
	/**
	* @brief	管理画面にプラグインのメニューを追加する
	* @param	none
	* @return	none
	*/
	public function simple_amazon_add_options() {

		// Add a new menu under Options:
		add_options_page(
			'Simple Amazon',
			'Simple Amazon',
			8,
			__FILE__,
			array( &$this, 'simple_amazon_options_page' )
		);
	}

	/**
	 * @brief	オプション設定の保存を行う
	 * @param	none
	 * @return	none
	 */
	function simple_amazon_save_options() {

		// create array
		$options = array(
			'accesskeyid'     => trim( $_POST['accesskeyid'] ),

			'associatesid_ca' => isset($_POST['associatesid_ca']) ? trim($_POST['associatesid_ca']) : '',
			'associatesid_cn' => isset($_POST['associatesid_cn']) ? trim($_POST['associatesid_cn']) : '',
			'associatesid_de' => isset($_POST['associatesid_de']) ? trim($_POST['associatesid_de']) : '',
			'associatesid_es' => isset($_POST['associatesid_es']) ? trim($_POST['associatesid_es']) : '',
			'associatesid_fr' => isset($_POST['associatesid_fr']) ? trim($_POST['associatesid_fr']) : '',
			'associatesid_it' => isset($_POST['associatesid_it']) ? trim($_POST['associatesid_it']) : '',
			'associatesid_jp' => isset($_POST['associatesid_jp']) ? trim($_POST['associatesid_jp']) : '',
			'associatesid_uk' => isset($_POST['associatesid_uk']) ? trim($_POST['associatesid_uk']) : '',
			'associatesid_us' => isset($_POST['associatesid_us']) ? trim($_POST['associatesid_us']) : '',

			'delete_setting'  => $_POST['delete_setting'],
			'imgsize'         => $_POST['imgsize'],
			'layout_type'     => $_POST['layout_type'],
			'secretaccesskey' => trim( $_POST['secretaccesskey'] ),
			'setcss'          => $_POST['setcss'],
			'windowtarget'    => $_POST['windowtarget']
		);

		update_option( 'simple_amazon_admin_options', $options );

		$this->options = $options;

	}

	/**
	 * @brief	オプション設定をデータベースから削除する
	 * @param	none
	 * @return	none
	 */
	public function uninstall() {
		if( $this->options['delete_setting'] == 'yes' ) {
			delete_option( 'simple_amazon_admin_options' );
		}
	}

}

?>

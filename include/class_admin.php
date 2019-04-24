<?php

/******************************************************************************
 * 管理画面のクラス
 *****************************************************************************/
class SimpleAmazonAdmin {

	private $cache;
	private $options;
	private $lib;

	/**
	 * Construct
	 * @param none
	 * @return none
	 */
	public function __construct() {

		global $simple_amazon_options;

		$this->options = $simple_amazon_options;
		$this->cache   = new SimpleAmazonCacheControl();
		$this->lib     = new SimpleAmazonLib();

		//アクションの設定
		add_action('admin_menu', array($this, 'simple_amazon_add_options'));
		add_action('admin_enqueue_scripts', array($this, 'addScripts'));

	}

	/**
	 * JavascriptとCSSを読み込む
	 */
	function addScripts() {

		// add jQuery tabs for options page. Use jQuery UI Tabs from WP
		if ( isset($_GET['page']) && $_GET['page'] == 'simple_amazon' ) {
			wp_enqueue_script('simple-amazon-admin', SIMPLE_AMAZON_PLUGIN_URL.'include/simple-amazon-admin.js', array(), SIMPLE_AMAZON_VER);
		}

	}
	
	/**
	 * @brief	管理画面のhtmlを生成する
	 * @param	none
	 * @return	string 管理画面のhtml
	 */
	public function simple_amazon_options_page() {

		$message = "";

		//　設定の更新
		if ( isset($_POST['action']) ){

			if ( $_POST['action'] == 'save_options' ){
				$this->simple_amazon_save_options();
				$message .= '<div class="updated"><p><strong>設定を保存しました。</strong></p></div>' . "\n"; 
			}

			if ( $_POST['action'] == 'clear_cache' ){
				$this->cache->clear();
				$message .= '<div class="updated"><p><strong>キャッシュを削除しました。</strong></p></div>' . "\n"; 
			}

			if ( $_POST['action'] == 'clear_log' ){
				file_put_contents( SIMPLE_AMAZON_CACHE_DIR . 'error.log', '' );
				$message .= '<div class="updated"><p><strong>ログを削除しました。</strong></p></div>' . "\n"; 
			}
		}
		
		//「テンプレート」の設定
		$template_dir = SIMPLE_AMAZON_PLUGIN_DIR . '/template/';
		$templates = scandir( $template_dir );

		$options_template = "";
		foreach ($templates as $template) {
			if( is_file( $template_dir . $template ) ) {
				if($template == $this->options['template']) {
					$selected = ' selected';
				} else {
					$selected = '';
				}
				$options_template .= '<option value="' . $template . '"' . $selected . '>' . $template . '</option>' . "\n";
			}
		}

		//「デフォルトの国」の設定
		$options_default_domain = "";
		foreach ( $this->lib->lang_domain_list as $domain ) {
			if( $domain == $this->options['default_domain'] ) {
				$selected = ' selected';
			} else {
				$selected = '';
			}
			$options_default_domain .= '<option value="' . $domain . '"' . $selected . '>' . $domain . '</option>' . "\n";
		}

		//CSS
		switch( $this->options['setcss']) {
			case 'yes': $setcss_yes = ' checked'; $setcss_no = ''; break;
			default: $setcss_yes = ''; $setcss_no = ' checked';
		}

		//アンインストール時の処理
		switch( $this->options['delete_setting']) {
			case 'yes': $delete_setting_yes = ' checked'; $delete_setting_no = ''; break;
			default: $delete_setting_yes = ''; $delete_setting_no = ' checked';
		}

		// 管理画面のテンプレート
		$simple_amazon_admin_html =
			'<div class="wrap">' . "\n".
			'<h2>Simple Amazon プラグイン設定</h2>' . "\n";

		if ( $message ){
			$simple_amazon_admin_html .= $message; 
		}

		// cacheディレクトリが設定されているかチェック
		$simple_amazon_admin_html .= $this->cache->is_error();

		if( ! $this->options['accesskeyid'] ) {
			$simple_amazon_admin_html .= '<div class="error"><p><strong>基本設定</strong> の <strong>Access Key ID</strong> を設定して下さい。</p></div>' . "\n";
		}

		if( ! $this->options['secretaccesskey'] ) {
			$simple_amazon_admin_html .= '<div class="error"><p><strong>基本設定</strong> の <strong>Secret Access Key</strong> を設定して下さい。</p></div>' . "\n";
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
			$simple_amazon_admin_html .= '<div class="error"><p><strong>基本設定</strong> の <strong>アソシエイト ID</strong> を設定して下さい。</p></div>' . "\n";
		}

		if( ! $this->options['default_domain'] ) {
			$simple_amazon_admin_html .= '<div class="error"><p><strong>基本設定</strong> の <strong>デフォルトのドメイン</strong> を設定して下さい。</p></div>' . "\n";
		}

		$simple_amazon_admin_html .=
			'<div id="simple-amazon-options">' . "\n";

		// タブ
		$simple_amazon_admin_html .=
			'<div class="nav-tab-wrapper" id="simple-amazon-options-menu">' . "\n" .
			'<a href="#tabs-1" class="nav-tab">オプション設定</a>' . "\n" .
			'<a href="#tabs-2" class="nav-tab">基本設定</a>' . "\n" .
			'<a href="#tabs-3" class="nav-tab">キャッシュ設定</a>' . "\n" .
			'</div>' . "\n";
		
		$simple_amazon_admin_html .=
			'<form method="post" action="' . str_replace( '%7E', '~', $_SERVER['REQUEST_URI'] ) . '">' . "\n" .
			'<input type="hidden" name="action" value="save_options" />' . "\n";



		// オプション設定
		$simple_amazon_admin_html .=
			'<div class="group" id="tabs-1">' . "\n" .

			'<h2>オプション設定</h2>' . "\n" .
			'<table class="form-table">' . "\n" .

			'<tr><th>テンプレート</th>' . "\n" .
			'<td><select name="template">' . "\n" .
			$options_template . 
			'</select></td>' . "\n" .
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

			'<p><input type="submit" class="button-primary" name="Submit" value="設定を保存 &raquo;" /></p>' . "\n" .

			'</div>' . "\n";

		// 基本設定
		$simple_amazon_admin_html .=
			'<div class="group" id="tabs-2">' . "\n" .

			'<h2>基本設定</h2>' . "\n" .
			'<table class="form-table">' . "\n" .

			'<tr>' . "\n" .
			'<th>Access Key ID</th>' . "\n" .
			'<td><input type="text" size="22" name="accesskeyid" value="' . $this->options['accesskeyid'] . '" /></td>' . "\n" .
			'</tr>' . "\n" .

			'<tr>' . "\n" .
			'<th>Secret Access Key</th>' . "\n" .
			'<td><input type="text" size="42" name="secretaccesskey" value="' . $this->options['secretaccesskey'] . '" /></td>' . "\n" .
			'</tr>' . "\n" .

			'<tr>' .
			'<th>デフォルトのドメイン</th>' .
			'<td><select name="default_domain">' . "\n" .
			$options_default_domain . 
			'</select></td>' . "\n" .
			'</tr>' . "\n" .

			'</table>' . "\n";

		$simple_amazon_admin_html .=

			'<h2>アソシエイト ID</h2>' . "\n";
			
		$simple_amazon_admin_html .=
			'<table class="form-table">' . "\n";

		$simple_amazon_admin_html .=
			'<tr>' .
			'<th>CA (カナダ)</th>' .
			'<td><input type="text" name="associatesid_ca" value="' . $this->options['associatesid_ca'] . '" /></td>' .
			'</tr>' . "\n";

		$simple_amazon_admin_html .=
			'<tr>' .
			'<th>CN (中国)</th>' .
			'<td><input type="text" name="associatesid_cn" value="' . $this->options['associatesid_cn'] . '" /></td>' .
			'</tr>' . "\n";

		$simple_amazon_admin_html .=
			'<tr>' .
			'<th>DE (ドイツ)</th>' .
			'<td><input type="text" name="associatesid_de" value="' . $this->options['associatesid_de'] . '" /></td>' .
			'</tr>' . "\n";

		$simple_amazon_admin_html .=
			'<tr>' .
			'<th>ES (スペイン)</th>' .
			'<td><input type="text" name="associatesid_es" value="' . $this->options['associatesid_es'] . '" /></td>' .
			'</tr>' . "\n";

		$simple_amazon_admin_html .=
			'<tr>' .
			'<th>FR (フランス)</th>' .
			'<td><input type="text" name="associatesid_fr" value="' . $this->options['associatesid_fr'] . '" /></td>' .
			'</tr>' . "\n";

		$simple_amazon_admin_html .=
			'<tr>' .
			'<th>IT (イタリア)</th>' .
			'<td><input type="text" name="associatesid_it" value="' . $this->options['associatesid_it'] . '" /></td>' .
			'</tr>' . "\n";

		$simple_amazon_admin_html .=
			'<tr>' .
			'<th>JP (日本)</th>' .
			'<td><input type="text" name="associatesid_jp" value="' . $this->options['associatesid_jp'] . '" /></td>' .
			'</tr>' . "\n";

		$simple_amazon_admin_html .=
			'<tr>' .
			'<th>UK (イギリス)</th>' .
			'<td><input type="text" name="associatesid_uk" value="' . $this->options['associatesid_uk'] . '" /></td>' .
			'</tr>' . "\n";

		$simple_amazon_admin_html .=
			'<tr>' .
			'<th>US (アメリカ)</th>' .
			'<td><input type="text" name="associatesid_us" value="' . $this->options['associatesid_us'] . '" /></td>' .
			'</tr>' . "\n";

		$simple_amazon_admin_html .=
			'</table>' . "\n" .

			'<p><input type="submit" class="button-primary" name="Submit" value="設定を保存 &raquo;" /></p>' . "\n" .

			'</div>' .

			'</form>' . "\n";

		//キャッシュ設定

		// オプション設定

		//ログファイルの読み込み
		$log = file_get_contents( SIMPLE_AMAZON_CACHE_DIR . 'error.log' );
		$log = str_replace( "\n", "<br />", $log );
		$log = preg_replace('/(https?:\/\/.+?),/i', "<a href=\"$1\">$1</a>,", $log );

		$logfile_url = SIMPLE_AMAZON_PLUGIN_URL . 'cache/error.log';

		$simple_amazon_admin_html .=
			'<div class="group" id="tabs-3">' . "\n" .

			'<h2>エラーログ</h2>' . "\n" .

			'<div style="padding: 0 0.5em; border: 1px solid #ccc; background-color:#f6f6f6; height:30vh; overflow:auto;">' . $log . '</div>' . "\n" .

			'<form method="post" action="' . str_replace( '%7E', '~', $_SERVER['REQUEST_URI'] ) . '">' . "\n" .
			'<input type="hidden" name="action" value="clear_log" />' . "\n" .
			'<p><input type="submit" class="button-primary" name="Submit" value="ログを削除する" />　<a href="' . $logfile_url . '">ログファイルを参照する</a></p>' . "\n" .
			'</form>' . "\n" .

			'<h2>キャッシュの削除</h2>' . "\n" .

			'<form method="post" action="' . str_replace( '%7E', '~', $_SERVER['REQUEST_URI'] ) . '">' . "\n" .
			'<input type="hidden" name="action" value="clear_cache" />' . "\n" .
			'<p><input type="submit" class="button-primary" name="Submit" value="キャッシュを削除する" /></p>' . "\n" .
			'</form>' . "\n" .

			'</div>' . "\n";

		$simple_amazon_admin_html .=
			'</div><!-- //.simple-amazon-options -->' . "\n";

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
			'level_8',
//			__FILE__,
			'simple_amazon',
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
			'accesskeyid'     => esc_html( $_POST['accesskeyid'] ),
			'secretaccesskey' => esc_html( $_POST['secretaccesskey'] ),

			'default_domain'  => $_POST['default_domain'],

			'associatesid_ca' => isset($_POST['associatesid_ca']) ? esc_html($_POST['associatesid_ca']) : '',
			'associatesid_cn' => isset($_POST['associatesid_cn']) ? esc_html($_POST['associatesid_cn']) : '',
			'associatesid_de' => isset($_POST['associatesid_de']) ? esc_html($_POST['associatesid_de']) : '',
			'associatesid_es' => isset($_POST['associatesid_es']) ? esc_html($_POST['associatesid_es']) : '',
			'associatesid_fr' => isset($_POST['associatesid_fr']) ? esc_html($_POST['associatesid_fr']) : '',
			'associatesid_it' => isset($_POST['associatesid_it']) ? esc_html($_POST['associatesid_it']) : '',
			'associatesid_jp' => isset($_POST['associatesid_jp']) ? esc_html($_POST['associatesid_jp']) : '',
			'associatesid_uk' => isset($_POST['associatesid_uk']) ? esc_html($_POST['associatesid_uk']) : '',
			'associatesid_us' => isset($_POST['associatesid_us']) ? esc_html($_POST['associatesid_us']) : '',

			'template'        => $_POST['template'],
			'setcss'          => $_POST['setcss'],
			'delete_setting'  => $_POST['delete_setting']
		);

		update_option( 'simple_amazon_admin_options', $options );

		$this->options = $options;

	}

}
?>

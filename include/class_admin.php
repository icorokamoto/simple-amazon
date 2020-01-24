<?php

/******************************************************************************
 * 管理画面のクラス
 *****************************************************************************/
class SimpleAmazonAdmin {

	private $cache;
	private $opt;

	/**
	 * Construct
	 * @param none
	 * @return none
	 */
	public function __construct() {

		$this->opt   = new SimpleAmazonOptionsControl();
		$this->cache = new SimpleAmazonCacheControl();

		//アクションの設定
		add_action('admin_menu', array($this, 'simple_amazon_add_options'));
		add_action('admin_enqueue_scripts', array($this, 'addScripts'));

	}

	/**
	 * JavascriptとCSSを読み込む
	 * @param none
	 * @return none
	 */
	public function addScripts() {

		// add jQuery tabs for options page. Use jQuery UI Tabs from WP
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'simple_amazon' ) {
			wp_enqueue_script( 'simple-amazon-admin', SIMPLE_AMAZON_PLUGIN_URL.'include/simple-amazon-admin.js', array(), SIMPLE_AMAZON_VER );
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
		$setting = $this->opt->get_option( 'template' );

		$options_template = "";
		foreach ( $templates as $template ) {
			if( is_file( $template_dir . $template ) ) {
				if( $template == $setting ) {
					$selected = ' selected';
				} else {
					$selected = '';
				}
				$options_template .= '<option value="' . $template . '"' . $selected . '>' . $template . '</option>' . "\n";
			}
		}

		//「デフォルトの国」の設定
		$setting = $this->opt->get_option( 'default_domain' );
		$domain_list = $this->opt->get_list( 'domain' );

		$options_default_domain = "";
		foreach ( $domain_list as $domain ) {
			if( $domain == $setting ) {
				$selected = ' selected';
			} else {
				$selected = '';
			}
			$options_default_domain .= '<option value="' . $domain . '"' . $selected . '>' . $domain . '</option>' . "\n";
		}

		//CSS
		$setting = $this->opt->get_option( 'setcss' );
		switch( $setting ) {
			case 'yes': $setcss_yes = ' checked'; $setcss_no = ''; break;
			default: $setcss_yes = ''; $setcss_no = ' checked';
		}

		//アンインストール時の処理
		$setting = $this->opt->get_option( 'delete_setting' );
		switch( $setting ) {
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

		$flag = $this->opt->isset_option( 'accesskeyid' );
		if( ! $flag ) {
			$simple_amazon_admin_html .= '<div class="error"><p><strong>基本設定</strong> の <strong>Access Key ID</strong> を設定して下さい。</p></div>' . "\n";
		}

		$flag = $this->opt->isset_option( 'secretaccesskey' );
		if( ! $flag ) {
			$simple_amazon_admin_html .= '<div class="error"><p><strong>基本設定</strong> の <strong>Secret Access Key</strong> を設定して下さい。</p></div>' . "\n";
		}

		$flag = $this->opt->isset_option( 'associatesid' );
		if( ! $flag ) {
			$simple_amazon_admin_html .= '<div class="error"><p><strong>基本設定</strong> の <strong>アソシエイト ID</strong> を設定して下さい。</p></div>' . "\n";
		}

		$flag = $this->opt->isset_option( 'default_domain' );
		if( ! $flag ) {
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

		$opt_accesskeyid     = $this->opt->get_option( 'accesskeyid' );
		$opt_secretaccesskey = $this->opt->get_option( 'secretaccesskey' );

		$simple_amazon_admin_html .=
			'<div class="group" id="tabs-2">' . "\n" .

			'<h2>基本設定</h2>' . "\n" .
			'<table class="form-table">' . "\n" .

			'<tr>' . "\n" .
			'<th>Access Key ID</th>' . "\n" .
			'<td><input type="text" size="22" name="accesskeyid" value="' . $opt_accesskeyid . '" /></td>' . "\n" .
			'</tr>' . "\n" .

			'<tr>' . "\n" .
			'<th>Secret Access Key</th>' . "\n" .
			'<td><input type="text" size="42" name="secretaccesskey" value="' . $opt_secretaccesskey . '" /></td>' . "\n" .
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

		//アソシエイトIDのフォーム
		$countries = $this->opt->get_list( 'name', 'code' );
		foreach( $countries as $code => $name ) {
			$option_associatesid = $this->opt->get_option( 'associatesid_' .$code );
			$simple_amazon_admin_html .=
				'<tr>' .
				'<th>' . strtoupper( $code ) . ' (' . $name . ')</th>' .
				'<td><input type="text" name="associatesid_' . $code . '" value="' . $option_associatesid . '" /></td>' .
				'</tr>' . "\n";
		}

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
			'template'        => $_POST['template'],
			'setcss'          => $_POST['setcss'],
			'delete_setting'  => $_POST['delete_setting']
		);

//		$countries = $this->opt->get_code_name_list();
		$countries = $this->opt->get_list( 'code' );
		foreach( $countries as $code ) {
			$options['associatesid_' . $code ] = isset($_POST['associatesid_' . $code]) ? esc_html($_POST['associatesid_' . $code]) : '';
		}

		$this->opt->update_options( $options );

	}

}
?>

<?php
/******************************************************************************
 * 管理画面のクラス
 *****************************************************************************/
namespace Icoro\SimpleAmazon;

if ( ! defined( 'ABSPATH' ) ) exit;

class Admin {

	// private $cache;
	private Options $options;

	/**
	 * Construct
	 */
	public function __construct() {

		$this->options = new Options();

		//アクションの設定
		add_action( 'admin_menu', array( $this, 'simple_amazon_add_options' ) );
		// add_action('admin_enqueue_scripts', array($this, 'simple_amazon_add_scripts'));

	}

	/**
	* @brief	管理画面にプラグインのメニューを追加する
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
	 * JavascriptとCSSを読み込む
	 */
	// public function simple_amazon_add_scripts() {
	// 	// add jQuery tabs for options page. Use jQuery UI Tabs from WP
	// 	if ( isset( $_GET['page'] ) && $_GET['page'] == 'simple_amazon' ) {
	// 		wp_enqueue_script( 'simple-amazon-admin', SIMPLE_AMAZON_JS_URL.'simple-amazon-admin.js', array(), SIMPLE_AMAZON_VER );
	// 	}
	// }
	
	/**
	 * @brief	管理画面のhtmlを生成する
	 * @return string 管理画面のhtml
	 */
	public function simple_amazon_options_page() {

		$message = "";

		//　設定の更新
		if ( isset($_POST['action']) ){

			if ( $_POST['action'] == 'save_options' ){
				$this->simple_amazon_save_options();
				$message .= '<div class="updated"><p><strong>設定を保存しました。</strong></p></div>' . "\n"; 
			}

		}

		// 変数の設定
		$credential_id      = $this->options->get_option( 'credential_id' );
		$credential_secret  = $this->options->get_option( 'credential_secret' );
		$credential_version = $this->options->get_option( 'credential_version' );
		$partner_tag        = $this->options->get_option( 'partner_tag' );
		$marketplace        = $this->options->get_option( 'marketplace' );
		
		//「テンプレート」の設定
		$template_dir = SIMPLE_AMAZON_PLUGIN_DIR . '/template/';
		$templates = scandir( $template_dir );
		$setting = $this->options->get_option( 'template' );

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

		//CSS
		$setting = $this->options->get_option( 'setcss' );
		switch( $setting ) {
			case 'yes': $setcss_yes = ' checked'; $setcss_no = ''; break;
			default: $setcss_yes = ''; $setcss_no = ' checked';
		}

		//アンインストール時の処理
		$setting = $this->options->get_option( 'delete_setting' );
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

		$flag = $this->options->isset_option( 'credential_id' );
		if( ! $flag ) {
			$simple_amazon_admin_html .= '<div class="error"><p><strong>基本設定</strong> の <strong>Credential ID</strong> を設定して下さい。</p></div>' . "\n";
		}

		$flag = $this->options->isset_option( 'credential_secret' );
		if( ! $flag ) {
			$simple_amazon_admin_html .= '<div class="error"><p><strong>基本設定</strong> の <strong>Credential Secret</strong> を設定して下さい。</p></div>' . "\n";
		}

		$flag = $this->options->isset_option( 'credential_version' );
		if( ! $flag ) {
			$simple_amazon_admin_html .= '<div class="error"><p><strong>基本設定</strong> の <strong>Credential Version</strong> を設定して下さい。</p></div>' . "\n";
		}

		$flag = $this->options->isset_option( 'partner_tag' );
		if( ! $flag ) {
			$simple_amazon_admin_html .= '<div class="error"><p><strong>基本設定</strong> の <strong>Partner Tag（アソシエイト ID）</strong> を設定して下さい。</p></div>' . "\n";
		}

		$flag = $this->options->isset_option( 'marketplace' );
		if( ! $flag ) {
			$simple_amazon_admin_html .= '<div class="error"><p><strong>基本設定</strong> の <strong>Marketplace</strong> を設定して下さい。</p></div>' . "\n";
		}

		$simple_amazon_admin_html .=
			'<div id="simple-amazon-options">' . "\n";
		
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

			'</div>' . "\n";

		// 基本設定
		$simple_amazon_admin_html .=
			'<div class="group" id="tabs-2">' . "\n" .

			'<h2>基本設定</h2>' . "\n" .
			'<table class="form-table">' . "\n" .

			'<tr>' . "\n" .
			'<th>Credential ID</th>' . "\n" .
			'<td><input type="text" size="42" name="credentialid" value="' . $credential_id . '" /></td>' . "\n" .
			'</tr>' . "\n" .

			'<tr>' . "\n" .
			'<th>Credential Secret</th>' . "\n" .
			'<td><input type="text" size="42" name="credentialsecret" value="' . $credential_secret . '" /></td>' . "\n" .
			'</tr>' . "\n" .

			'<tr>' . "\n" .
			'<th>Credential Version</th>' . "\n" .
			'<td><input type="text" size="42" name="credentialversion" value="' . $credential_version . '" /></td>' . "\n" .
			'</tr>' . "\n" .

			'<tr>' . "\n" .
			'<th>Partner Tag</th>' . "\n" .
			'<td><input type="text" size="22" name="partnertag" value="' . $partner_tag . '" /></td>' . "\n" .
			'</tr>' . "\n" .

			'<tr>' . "\n" .
			'<th>Marketplace</th>' . "\n" .
			'<td><input type="text" size="22" name="marketplace" value="' . $marketplace . '" /></td>' . "\n" .
			'</tr>' . "\n" .

			'</table>' . "\n";

		$simple_amazon_admin_html .=
		// 	'</table>' . "\n" .

			'<p><input type="submit" class="button-primary" name="Submit" value="設定を保存 &raquo;" /></p>' . "\n" .

			'</div>' .

			'</form>' . "\n";

		$simple_amazon_admin_html .=
			'</div><!-- //.simple-amazon-options -->' . "\n";

		echo $simple_amazon_admin_html;
	}

	/**
	 * @brief	オプション設定の保存を行う
	 */
	function simple_amazon_save_options() {

		// create array
		$options = array(
			'credential_id'      => esc_html( $_POST['credentialid'] ),
			'credential_secret'  => esc_html( $_POST['credentialsecret'] ),
			'credential_version' => esc_html( $_POST['credentialversion'] ),
			'partner_tag'        => esc_html( $_POST['partnertag'] ),
			'marketplace'        => esc_html( $_POST['marketplace'] ),
			'template'           => $_POST['template'],
			'setcss'             => $_POST['setcss'],
			'delete_setting'     => $_POST['delete_setting']
		);

		$this->options->update_options( $options );

	}

}

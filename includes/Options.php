<?php
namespace Icoro\SimpleAmazon;

if ( ! defined( 'ABSPATH' ) ) exit;

class Options {

	public mixed $options;
	// public array $country_list;

	/**
	 * Construct
	 */
	public function __construct() {

		//データベースからオプションの読み込み
		$this->options = get_option( 'simple_amazon_admin_options' );

	}

	/**
	 * オプションの値を取得する
	 * @param string $option_type
	 * @return string $option
	 */
	public function get_option( $option_type ) {
		$option = '';
		if( array_key_exists( $option_type, $this->options ) ) {
			$option = $this->options[$option_type];
		}
		return $option;
	}

	/**
	 * 必須項目のオプションが入力されているかチェックする
	 * @return boolean $flag
	 */
	public function isset_required_options() {

		$flag = false;

		$check_credential_id      = $this->isset_option( 'credential_id' );
		$check_credential_secret  = $this->isset_option( 'credential_secret' );
		$check_credential_version = $this->isset_option( 'credential_version' );

		if( $check_credential_id && $check_credential_secret && $check_credential_version ) {
			$flag = true;
		}

		return $flag;

	}

	/**
	 * オプションに値が入力されているかチェックする
	 * @param string $option_type
	 * @return boolean $flag
	 */
	public function isset_option( $option_type ) {

		$flag = false;
		$check_option = '';

		$check_option = $this->get_option( $option_type );
		// $check_option = $this->options[$option_type];

		if( $check_option ) {
			$flag = true;
		}

		return $flag;
	}

	/**
	 * オプションをアップデートする
	 * @param array $options
	 */
	public function update_options( $options ) {

		$this->options = $options;

		update_option( 'simple_amazon_admin_options', $this->options );

	}

	/**
	 * オプションをデータベースから削除する
	 */
	public function delete_options() {

		$option_delete_setting = $this->get_option( 'delete_setting' );
		if( $option_delete_setting == 'yes' ) {
			delete_option( 'simple_amazon_admin_options' );
		}

	}

	/**
	 * オプションを読み込む
	 */
	public function load_options() {

		$this->options = get_option( 'simple_amazon_admin_options' );

		// オプションがまだ設定されていない場合はデフォルトの設定を書き込む
		if ( ! $this->options ) {

			//デフォルトのドメインを設定
			// $domain = 'amazon.com';
			// $wplang = get_locale();

			// lang を key とした domain の配列(.'amazon.co.jp' => 'jp' )を取得
			// $lang_domain_list = $this->extract_country_list( 'domain', 'lang' );

			// if( array_key_exists( $wplang, $lang_domain_list ) ) {
			// 	$domain = $lang_domain_list[$wplang];
			// }

			//オプションを初期化
			$this->options = array(
				'credential_id'      => '',
				'credential_secret'  => '',
				'credential_version' => '',
				'partner_tag'        => '',
				'marketplace'        => '',
				'template'           => 'sa-default.php',
				'setcss'             => 'yes',
				'delete_setting'     => 'no'
			);

			// アソシエイトIDを初期化
			// codeの配列(.'jp' )を取得
			// $code_list = $this->extract_country_list( 'code' );

			// foreach( $code_list as $code ) {
			// 	$this->options['associatesid_' . $code ] = '';
			// }

			update_option( 'simple_amazon_admin_options', $this->options );

		}

	}

}

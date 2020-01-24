<?php
class SimpleAmazonOptionsControl {

	public $options;
	public $country_list;


	/**
	 * Construct
	 * @param none
	 * @return none
	 */
	public function __construct() {

		//データベースからオプションの読み込み
		$this->options = get_option( 'simple_amazon_admin_options' );

		$this->country_list = array(
			array( 'code' => 'ae', 'domain' => 'amazon.ae',     'lang' => 'ar',    'name' => 'アラブ首長国連邦' ),
			array( 'code' => 'au', 'domain' => 'amazon.com.au', 'lang' => 'en_AU', 'name' => 'オーストラリア' ),
			array( 'code' => 'br', 'domain' => 'amazon.com.br', 'lang' => 'pt_BR', 'name' => 'ブラジル' ),
			array( 'code' => 'ca', 'domain' => 'amazon.ca',     'lang' => 'en_CA', 'name' => 'カナダ' ),
			array( 'code' => 'de', 'domain' => 'amazon.de',     'lang' => 'es_ES', 'name' => 'スペイン' ),
			array( 'code' => 'fr', 'domain' => 'amazon.fr',     'lang' => 'fr_FR', 'name' => 'フランス' ),
			array( 'code' => 'in', 'domain' => 'amazon.in',     'lang' => 'en_IN', 'name' => 'インド' ),
			array( 'code' => 'it', 'domain' => 'amazon.it',     'lang' => 'it_IT', 'name' => 'イタリア' ),
			array( 'code' => 'jp', 'domain' => 'amazon.co.jp',  'lang' => 'ja',    'name' => '日本' ),
			array( 'code' => 'mx', 'domain' => 'amazon.com.mx', 'lang' => 'es_MX', 'name' => 'メキシコ' ),
			array( 'code' => 'sg', 'domain' => 'amazon.sg',     'lang' => 'en_SG', 'name' => 'シンガポール' ),
			array( 'code' => 'tr', 'domain' => 'amazon.com.tr', 'lang' => 'tr_TR', 'name' => 'トルコ' ),
			array( 'code' => 'uk', 'domain' => 'amazon.co.uk',  'lang' => 'en_GB', 'name' => 'イギリス' ),
			array( 'code' => 'us', 'domain' => 'amazon.com',    'lang' => 'en_US', 'name' => 'アメリカ' )
		);

	}

	/**
	 * リストを取得する
	 * @param String $value
	 * @param String $key
	 * @return Array $list
	 */
	public function get_list( $value, $key = null ) {
		$list = array_column( $this->country_list, $value, $key );
		return $list;
	}

	/**
	 * ドメインから対応するアソシエイトIDを取得する
	 * @param String $domain
	 * @return String $aid
	 */
	public function get_aid( $domain ) {
		$domain_code_list = array_column( $this->country_list, 'code', 'domain' );
		$code = $domain_code_list[$domain];

		$aid = $this->options['associatesid_' . $code];
		return $aid;
	}

	/**
	 * オプションの値を取得する
	 * @param String $optiontype
	 * @return String $option
	 */
	public function get_option( $optiontype ) {

		return $this->options[$optiontype];

	}

	/**
	 * 必須項目のオプションが入力されているかチェックする
	 * @param none
	 * @return Boolean $result
	 */
	public function isset_required_options() {

		$flag = false;

		$check_accesskeyid     = $this->isset_option( 'accesskeyid' );
		$check_secretaccesskey = $this->isset_option( 'secretaccesskey' );
		$check_associatesid    = $this->isset_option( 'associatesid' );

		if( $check_accesskeyid && $check_secretaccesskey && $check_associatesid ) {
			$flag = true;
		}

		return $flag;

	}

	/**
	 * オプションに値が入力されているかチェックする
	 * @param none
	 * @return Boolean $flag
	 */
	public function isset_option( $optiontype ) {

		$flag = false;
		$check_option = '';

		if( $optiontype == 'associatesid' ) {
			$code_list = array_column( $this->country_list, 'code' );

			foreach( $code_list as $code ) {
				$check_option .= $this->options['associatesid_' . $code];
			}
		} else {
			$check_option = $this->options[$optiontype];
		}

		if( $check_option ) {
			$flag = true;
		}

		return $flag;
	}

	/**
	 * オプションをアップデートする
	 * @param Array $options
	 * @return none
	 */
	public function update_options( $options ) {

		$this->options = $options;

		update_option( 'simple_amazon_admin_options', $this->options );

	}

	/**
	 * オプションをデータベースから削除する
	 * @param none
	 * @return none
	 */
	public function delete_options() {

		if( $this->options['delete_setting'] == 'yes' ) {
			delete_option( 'simple_amazon_admin_options' );
		}

	}

	/**
	 * オプションを読み込む
	 * @param none
	 * @return none
	 */
	public function load_options() {
		$this->options = get_option( 'simple_amazon_admin_options' );

		// オプションがまだ設定されていない場合はデフォルトの設定を書き込む
		if ( ! $this->options ) {
			$this->set_default_options();
		}
	}

	/**
	 * デフォルトのオプションを設定する
	 * @param none
	 * @return none
	 */
	public function set_default_options() {

		//デフォルトのドメインを設定
		$domain = 'amazon.com';
		$wplang = get_locale();

		$lang_domain_list = array_column( $this->country_list, 'domain', 'lang' );

		if( array_key_exists( $wplang, $lang_domain_list ) ) {
			$domain = $lang_domain_list[$wplang];
		}

		//オプションを初期化
		$this->options = array(
			'accesskeyid'     => '',
			'secretaccesskey' => '',
			'default_domain'  => $domain,
			'delete_setting'  => 'no',
			'setcss'          => 'yes',
			'template'        => 'sa-default.php'
		);

		// アソシエイトIDを初期化
		$code_list = array_column( $this->country_list, 'code' );

		foreach( $code_list as $code ) {
			$this->options['associatesid_' . $code ] = '';
		}

		update_option( 'simple_amazon_admin_options', $this->options );
	}

}
?>
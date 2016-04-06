<?php
/*
Plugin Name: Simple Amazon
Plugin URI: http://www.icoro.com/
Description: ASIN を指定して Amazon から個別商品の情報を取出します。BOOKS, DVD, CD は詳細情報を取り出せます。
Author: icoro
Version: 6.0
Author URI: http://www.icoro.com/
Special Thanks: tomokame (http://http://tomokame.moo.jp/)
Special Thanks: websitepublisher.net (http://www.websitepublisher.net/article/aws-php/)
Special Thanks: hiromasa.zone :o) (http://hiromasa.zone.ne.jp/)
Special Thanks: PEAR :: Package :: Cache_Lite (http://pear.php.net/package/Cache_Lite)
Special Thanks: Amazon® AWS HMAC signed request using PHP (http://mierendo.com/software/aws_signed_query/)
Special Thanks: PHP による Amazon PAAPI の毎秒ルール制限の実装とキャッシュの構築例 (http://sakuratan.biz/archives/1395)
*/

if( $_SERVER['SCRIPT_FILENAME'] == __FILE__ ) die();


/******************************************************************************
 * 定数の設定 (主にディレクトリのパスとか)
 *****************************************************************************/
define( 'SIMPLE_AMAZON_VER', '6.0' );
define( 'SIMPLE_AMAZON_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'SIMPLE_AMAZON_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
//define( 'SIMPLE_AMAZON_IMG_URL',    SIMPLE_AMAZON_PLUGIN_URL . 'images' );


/******************************************************************************
 * globalな変数の設定
 *****************************************************************************/
global $simple_amazon_options;


/******************************************************************************
 * クラスの読み込み
 *****************************************************************************/
include_once(SIMPLE_AMAZON_PLUGIN_DIR . 'include/class_xml_parse.php');
include_once(SIMPLE_AMAZON_PLUGIN_DIR . 'include/class_cache_control.php');
include_once(SIMPLE_AMAZON_PLUGIN_DIR . 'include/class_lib.php');
include_once(SIMPLE_AMAZON_PLUGIN_DIR . 'include/class_admin.php');
include_once(SIMPLE_AMAZON_PLUGIN_DIR . 'include/class_view.php');
include_once(SIMPLE_AMAZON_PLUGIN_DIR . 'include/class_list_view.php');


/******************************************************************************
 * Simple Amazon クラスの設定
 *****************************************************************************/
$SimpleAmazon = new SimpleAmazon();

class SimpleAmazon {

	private $options;

	private $view;
	private $listview;
	private $admin;

	/**
	 * construct
	 * @param none
	 * @return none
	 */
	public function __construct() {

		//オプション設定の読み込み
		$this->set_options();

		//オブジェクトの設定
		$this->view     = new SimpleAmazonView();
		$this->listview = new SimpleAmazonListView();

		if (is_admin()) {
			$this->admin = new SimpleAmazonAdmin();
		}

		//インストール&アンインストール時の処理
		register_activation_hook(__FILE__, array($this, 'plugin_activation'));
		register_deactivation_hook(__FILE__, array($this, 'plugin_deactivation'));

		// 定期的に期限切れのキャッシュを削除する feat. wp-cron
		add_action('simple_amazon_clear_chache_hook', array($this, 'clean_cache'));

		// simple amazonのcssを読み込む
		add_action('wp_head', array($this, 'add_stylesheet'), 1);

		// amazon のURLをhtmlに置き換える
		add_filter('the_content', array($this->view, 'replace'));

	}


	/**
	 * オプションの設定
	 * @param none
	 * @return none
	 */
	private function set_options() {

		global $simple_amazon_options;

		$this->options = get_option('simple_amazon_admin_options');

		// デフォルトの設定
		if ( ! $this->options ){
			$this->options = array(
				'accesskeyid'     => '',
				'associatesid_ca' => '',
				'associatesid_cn' => '',
				'associatesid_de' => '',
				'associatesid_es' => '',
				'associatesid_fr' => '',
				'associatesid_it' => '',
				'associatesid_jp' => '',
				'associatesid_uk' => '',
				'associatesid_us' => '',
				'delete_setting'  => 'no',
				'secretaccesskey' => '',
				'setcss'          => 'yes',
				'template'        => 'sa-default.php'
			);
			update_option( 'simple_amazon_admin_options', $this->options );
		}
		
		$simple_amazon_options = $this->options;

	}


	/**
	 * インストール時の処理
	 * @param none
	 * @return none
	 */
	public function plugin_activation() {
		// simple_amazon_clear_chache_hook を wp-cron に追加する
		wp_schedule_event(time(), 'daily', 'simple_amazon_clear_chache_hook');
	}


	/**
	 * アンインストール時の処理
	 * @param none
	 * @return none
	 */
	public function plugin_deactivation() {

		// オプション値の削除
		if( $this->options['delete_setting'] == 'yes' ) {
			delete_option( 'simple_amazon_admin_options' );
		}

		// simple_amazon_clear_chache_hook を wp-cron から削除する
		wp_clear_scheduled_hook('simple_amazon_clear_chache_hook');
	}


	/**
	 * simple amazonのcssを読み込む
	 * @param none
	 * @return none
	 */
	public function add_stylesheet() {

		global $simple_amazon_options;

		if( $simple_amazon_options['setcss'] == 'yes') {
			wp_enqueue_style('simple-amazon', SIMPLE_AMAZON_PLUGIN_URL.'simple-amazon.css', array(), SIMPLE_AMAZON_VER);
		}
	}


	/**
	 * 定期的に期限切れのキャッシュを削除する feat. wp-cron
	 * @param none
	 * @return none
	 */
	private function clean_cache() {
		$SimpleAmazonCacheController = new SimpleAmazonCacheControl();
		$SimpleAmazonCacheController->clean();
	}

}


/******************************************************************************
 * 関数の設定
 *****************************************************************************/
 
/* 指定したasinの商品情報を表示する関数 */
function simple_amazon_view( $asin, $code = null, $styles = null ) {
	global $simpleAmazonView;
	$simpleAmazonView->view( $asin, esc_html($code), $styles );
}

/* カスタムフィールドから値を取得して表示する関数 */
function simple_amazon_custum_view() {
	global $simpleAmazonView;
	$simpleAmazonView->view_custom_field();
}

/* 指定したリクエストのリストを表示する関数 */
function simple_amazon_list_view( $params, $code = null, $styles = null ) {
	global $simpleAmazonListView;
	$simpleAmazonListView->view( $params, esc_html($code), $styles );
/*
		$params = array(
			'SearchIndex'   => 'Books',
			'BrowseNode'    => '466280',
			'Power'         => $power
		);
*/
}

?>

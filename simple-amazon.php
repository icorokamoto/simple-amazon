<?php
/*
Plugin Name: Simple Amazon
Plugin URI: http://www.icoro.com/
Description: ASIN を指定して Amazon から個別商品の情報を取出します。BOOKS, DVD, CD は詳細情報を取り出せます。
Author: icoro
Version: 8.0.1
Author URI: http://www.icoro.com/
Special Thanks: tomokame (http://tomokame.moo.jp/)
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
define( 'SIMPLE_AMAZON_VER', '8.0.1' );
define( 'SIMPLE_AMAZON_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SIMPLE_AMAZON_CACHE_DIR',  SIMPLE_AMAZON_PLUGIN_DIR . 'cache/' );
define( 'SIMPLE_AMAZON_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SIMPLE_AMAZON_IMG_URL',    SIMPLE_AMAZON_PLUGIN_URL . 'images/' );


/******************************************************************************
 * globalな変数の設定
 *****************************************************************************/
//global $simple_amazon_options; //不使用


/******************************************************************************
 * クラスの読み込み
 *****************************************************************************/
include_once(SIMPLE_AMAZON_PLUGIN_DIR . 'include/class_options.php');
include_once(SIMPLE_AMAZON_PLUGIN_DIR . 'include/class_parse_json.php');
include_once(SIMPLE_AMAZON_PLUGIN_DIR . 'include/class_cache_control.php');
include_once(SIMPLE_AMAZON_PLUGIN_DIR . 'include/class_lib.php');
include_once(SIMPLE_AMAZON_PLUGIN_DIR . 'include/class_admin.php');
include_once(SIMPLE_AMAZON_PLUGIN_DIR . 'include/class_view.php');
//include_once(SIMPLE_AMAZON_PLUGIN_DIR . 'include/class_list_view.php');
include_once(SIMPLE_AMAZON_PLUGIN_DIR . 'include/awsv4.php');


/******************************************************************************
 * Simple Amazon クラスの設定
 *****************************************************************************/
$simpleAmazon = new SimpleAmazon();

class SimpleAmazon {

	public $saView;
	public $saListView;

	private $lib;
	private $opt;

	private $saAdmin;

	/**
	 * construct
	 * @param none
	 * @return none
	 */
	public function __construct() {

		$this->lib = new SimpleAmazonLib();
		$this->opt = new SimpleAmazonOptionsControl();

		//オプション設定の読み込み
		$this->opt->load_options();

		//オブジェクトの設定
		$this->saView     = new SimpleAmazonView();
//書換が必要なのでとりあえず一時停止
//		$this->saListView = new SimpleAmazonListView();

		if ( is_admin() ) {
			$this->saAdmin = new SimpleAmazonAdmin();
		}

		//インストール&アンインストール時の処理
		register_activation_hook(__FILE__, array($this, 'plugin_activation'));
		register_deactivation_hook(__FILE__, array($this, 'plugin_deactivation'));

		// 定期的に期限切れのキャッシュを削除する feat. wp-cron
		add_action('simple_amazon_clear_chache_hook', array($this, 'clean_cache'));

		// simple amazonのcssを読み込む
		add_action('wp_head', array($this, 'add_stylesheet'), 1);

		// amazon のURLをhtmlに置き換える
		add_filter('the_content', array($this->saView, 'replace'), 1);

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
		$this->opt->delete_options();

		// simple_amazon_clear_chache_hook を wp-cron から削除する
		wp_clear_scheduled_hook('simple_amazon_clear_chache_hook');

	}


	/**
	 * simple amazonのcssを読み込む
	 * @param none
	 * @return none
	 */
	public function add_stylesheet() {

		$setting = $this->opt->get_option( 'setcss' );

		if( $setting == 'yes') {
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
function simple_amazon_view( $asin, $code = null, $template = null ) {
	global $simpleAmazon;
	$html = $simpleAmazon->saView->generate_html( $asin, $code, $template );
	echo $html;
}

/* カスタムフィールドから値を取得して表示する関数 */
function simple_amazon_custum_view() {
	global $simpleAmazon;
	$html = $simpleAmazon->saView->generate_html_custom_field();
	echo $html;
}

/* 指定したリクエストのリストを表示する関数 */
function simple_amazon_list_view( $params, $code = null, $styles = null ) {
	global $simpleAmazon;
//書換が必要なのでとりあえず一時停止
//	$simpleAmazon->saListView->view( $params, $code, $styles );
/*
		$params = array(
			'SearchIndex'   => 'Books',
			'BrowseNode'    => '466280',
			'Power'         => $power
		);
*/
}

/******************************************************************************
 * ショートコード
 *****************************************************************************/

/* ショートコード */
// [sa asin="10文字のASIN" word="検索に使用するキーワード"]
function sa_shotcode( $atts ) {
    $atts = shortcode_atts( array(
        'asin'    => null,
		'code'    => null,
		'tpl'     => null,
		'word'    => null,
		'rakuten' => 1,
		'yahoo'   => 1
    ), $atts );

	$options = null;
	
	//実は楽天とヤフーのアフィリエイトも仕込める
	if( $atts['word'] ) {
		$options = Array( 
			'r' => trim( $atts['rakuten'] ),
			'y' => trim( $atts['yahoo'] )
		);
	}

	global $simpleAmazon;
	$html = $simpleAmazon->saView->generate_html( $atts['asin'], $atts['code'], $atts['tpl'], $atts['word'], $options );

	return $html;
}
add_shortcode( 'sa', 'sa_shotcode' );

?>

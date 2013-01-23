<?php
/*
Plugin Name: Simple Amazon
Plugin URI: http://www.icoro.com/
Description: ASIN を指定して Amazon から個別商品の情報を取出します。BOOKS, DVD, CD は詳細情報を取り出せます。
Author: icoro
Version: 5.4
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
if ( ! defined( 'SIMPLE_AMAZON_VER' ) )
	define( 'SIMPLE_AMAZON_VER', '5.4' );

if ( ! defined( 'SIMPLE_AMAZON_DIR_NAME' ) )
	define( 'SIMPLE_AMAZON_DIR_NAME', plugin_basename( dirname( __FILE__ ) ) );

if ( ! defined( 'SIMPLE_AMAZON_PLUGIN_DIR' ) )
	define( 'SIMPLE_AMAZON_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . SIMPLE_AMAZON_DIR_NAME );

if ( ! defined( 'SIMPLE_AMAZON_PLUGIN_URL' ) )
	define( 'SIMPLE_AMAZON_PLUGIN_URL', WP_PLUGIN_URL . '/' . SIMPLE_AMAZON_DIR_NAME );

if ( ! defined( 'SIMPLE_AMAZON_IMG_URL' ) )
	define( 'SIMPLE_AMAZON_IMG_URL', SIMPLE_AMAZON_PLUGIN_URL . '/images' );


/******************************************************************************
 * globalな変数の設定
 *****************************************************************************/
global $simple_amazon_options;

$simple_amazon_options = get_option('simple_amazon_admin_options');

if ( ! $simple_amazon_options ){
	$simple_amazon_options = array(
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
		'imgsize'         => 'medium',
		'layout_type'     => 0,
		'secretaccesskey' => '',
		'setcss'          => 'yes',
		'windowtarget'    => 'self'
	);
	update_option( 'simple_amazon_admin_options', $simple_amazon_options );
}

/******************************************************************************
 * クラスの読み込み
 *****************************************************************************/
include_once(SIMPLE_AMAZON_PLUGIN_DIR . '/include/sa_view_class.php');
include_once(SIMPLE_AMAZON_PLUGIN_DIR . '/include/sa_xmlparse_class.php');
include_once(SIMPLE_AMAZON_PLUGIN_DIR . '/include/sa_cache_control_class.php');
include_once(SIMPLE_AMAZON_PLUGIN_DIR . '/include/sa_admin_class.php');

$simpleAmazonView  = new SimpleAmazonView();
$simpleAmazonAdmin = new SimpleAmazonAdmin();


/******************************************************************************
 * アクション&フィルタの設定
 *****************************************************************************/

/* amazon のURLをhtmlに置き換える */
add_filter('the_content', array($simpleAmazonView, 'replace'));

/* simple amazonのcssを読み込む */
function add_simpleamazon_stylesheet(){

	global $simple_amazon_options;

	if( $simple_amazon_options['setcss'] == 'yes') {
		wp_enqueue_style('simple-amazon', SIMPLE_AMAZON_PLUGIN_URL.'/include/simple-amazon.css', array(), SIMPLE_AMAZON_VER);
	}
}
add_action('wp_head', 'add_simpleamazon_stylesheet', 1);

/* 定期的に期限切れのキャッシュを削除する feat. wp-cron */
function simple_amazon_clean_cache() {
	$SimpleAmazonCacheController = new SimpleAmazonCacheControl();
	$SimpleAmazonCacheController->clean();
}
add_action('simple_amazon_clear_chache_hook', 'simple_amazon_clean_cache');


/******************************************************************************
 * インストール&アンインストール時の設定
 *****************************************************************************/

/* インストール時 */
function simple_amazon_activation() {
	// simple_amazon_clear_chache_hook を wp-cron に追加する
	wp_schedule_event(time(), 'daily', 'simple_amazon_clear_chache_hook');
}

/* アンインストール時 */
function simple_amazon_deactivation() {
	global $simpleAmazonAdmin;

	// オプション値の削除
	$simpleAmazonAdmin->uninstall();

	// simple_amazon_clear_chache_hook を wp-cron から削除する
	wp_clear_scheduled_hook('simple_amazon_clear_chache_hook');
}

register_activation_hook(__FILE__, 'simple_amazon_activation');
register_deactivation_hook(__FILE__, 'simple_amazon_deactivation');


/******************************************************************************
 * 関数の設定
 *****************************************************************************/
 
/* 指定したasinの商品情報を表示する関数 */
function simple_amazon_view( $asin, $code = null, $style = null ) {
	global $simpleAmazonView;
	$simpleAmazonView->view( $asin, esc_html($code), $style );
}

/* カスタムフィールドから値を取得して表示する関数 */
function simple_amazon_custum_view() {
	global $simpleAmazonView;
	$simpleAmazonView->view_custom_field();
}

?>

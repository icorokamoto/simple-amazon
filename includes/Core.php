<?php
namespace Icoro\SimpleAmazon;

if ( ! defined( 'ABSPATH' ) ) exit;

class Core {

	public View $view;
	
	private Options $options;
	private Admin $admin;
	// private $lib;

	/**
	 * construct
	 */
	public function __construct() {

		//オブジェクトの設定
		$this->view = new View();
		$this->options = new Options();
		// $this->lib = new Lib();

		//オプション設定の読み込み
		$this->options->load_options();

		//書換が必要なのでとりあえず一時停止
		// $this->saListView = new SimpleAmazonListView();

		// 管理画面の読み込み
		if ( is_admin() ) {
			$this->admin = new Admin();
		}

		//インストール&アンインストール時の処理
		register_activation_hook(__FILE__, array($this, 'plugin_activation'));
		register_deactivation_hook(__FILE__, array($this, 'plugin_deactivation'));

		// 定期的に期限切れのキャッシュを削除する feat. wp-cron
		// add_action('simple_amazon_clear_chache_hook', array($this, 'clean_cache'));

		// simple amazonのcssを読み込む
		add_action('wp_head', array($this, 'add_stylesheet'), 1);

		// 投稿中のamazonのURLをhtmlに置き換える
		add_filter('the_content', array($this->view, 'replace_urls'), 1);
	
		// 初期設定
		add_action( 'init', function() {
			// ショートコード設定
			add_shortcode( 'sa', array( $this, 'sa_shortcode' ) );

			// カスタムブロックの設定
			register_block_type( SIMPLE_AMAZON_PLUGIN_DIR . 'build/amazon-link-block' );
		});
	}

	/**
	 * インストール時の処理
	 */
	public function plugin_activation() {
	}

	/**
	 * アンインストール時の処理
	 */
	public function plugin_deactivation() {
		// オプション値の削除
		$this->options->delete_options();
  }

	/**
	 * simple amazonのcssを読み込む
	 */
	public function add_stylesheet() {

		$setting = $this->options->get_option( 'setcss' );

		if( $setting == 'yes') {
			wp_enqueue_style( 'simple-amazon', SIMPLE_AMAZON_PLUGIN_URL.'simple-amazon.css', array(), SIMPLE_AMAZON_VER );
		}

	}

	/**
	 * Amazon のリンクを取得
	 * @param string $search_type
	 * @param string $search_keyword
	 * @return string $html
	 */
	public function get_amazon_link( $search_type, $search_keyword ) {
		$html = $this->view->generate_link_html( $search_type, $search_keyword );
		return $html;
	}

	/**
	 * ショートコード
	 * @param array $atts
	 * @return string $html
	 *  */
	public function sa_shortcode( $atts ) {

		// [sa asin="10文字のASIN" word="検索に使用するキーワード"]
		$atts = shortcode_atts( array(
	    'asin'    => null,
			'word'    => null
	  ), $atts );

		$search_type    = ( $atts['asin'] ) ? 'get' : 'search';
		$search_keyword = ( $atts['asin'] ) ? $atts['asin'] : $atts['word'];

		$html = $this->view->generate_link_html( $search_type, $search_keyword );
		// $html = $this->get_amazon_link( $search_type, $search_keyword );

		// $html = '';

		// if( $atts['asin'] ) {
		// 	$this->view->set_request_type( 'get' );
		// 	$this->view->set_search_keyword( $atts['asin'] );
		// } elseif( $atts['word'] ) {
		// 	$this->view->set_request_type( 'search' );
		// 	$this->view->set_search_keyword( $atts['word'] );
		// }

		// $html = $this->view->generate_link_html();

		return $html;
	}

}

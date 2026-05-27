<?php
namespace Icoro\SimpleAmazon;
use Exception;

if ( ! defined( 'ABSPATH' ) ) exit;

/******************************************************************************
 * Amazon から取得した商品情報を操作するクラス
 *****************************************************************************/
class View {

	private Options $options;
	// private Lib $lib;

	// apiなしの商品リンク表示などに使用
	private string $asin = '';
	private string $search_keyword = '';

	/**
	 * Construct
	 */
	public function __construct() {
		$this->options = new Options();
		// $this->lib = new SimpleAmazonLib();
	}

	/**
	 * 記事本文中のコードを個別商品表示 HTML に置き換える
	 * @param string $content
	 * @return string $content ( HTML )
	 */
	public function replace_urls( $content ) { // 記事本文中の呼び出しコードを変換

		//オプションの設定が終わってない場合は置換せずに返す
		$flag = $this->options->isset_required_options();
		if( ! $flag ) {
			return $content;
		}

//		$patterns[] = '/\[tmkm-amazon\](?P<asin>[A-Z0-9]{10,13})\[\/tmkm-amazon\]/';
		$patterns[] = '/<amazon>(?P<asin>[A-Z0-9]{10,13})<\/amazon>/';
		// $patterns[] = '/(^|<p>)https?:\/\/www\.(?P<domain>amazon\.com\.au|amazon\.com\.br|amazon\.in|amazon\.sg|amazon\.com\.mx|amazon\.ae|amazon\.com\.tr|amazon\.ca|amazon\.de|amazon\.es|amazon\.fr|amazon\.it|amazon\.co\.jp|amazon\.co\.uk|amazon\.com)\/?(.*)\/(dp|gp\/product|gp\/aw\/d)\/(?P<asin>[A-Z0-9]{10}).*?($|<\/p>)/m';
		$patterns[] = '/(^|<p>)https?:\/\/www\.amazon\.[a-z\.]+(?:\/(?<name>[^\/]+))?\/dp\/(?<asin>[A-Z0-9]{10}).*?($|<\/p>)/m';

		// リクエストタイプを設定
		// $this->set_request_type( 'get' );

		foreach( $patterns as $pattern ) {
			if( preg_match_all($pattern, $content, $arr) ) {
				for ( $i=0; $i<count($arr[0]); $i++ ) {
					$this->asin = $arr['asin'][$i];
					// $name = ( isset($arr['name'][$i]) ) ? urldecode($arr['name'][$i]) : '';

					$item_html = $this->generate_link_html( 'get', $this->asin );

					// URLの置換
					$content = str_replace( $arr[0][$i], $item_html, $content );
				}
			}
		}

		return $content;

	}
	
	/**
	 * カスタムフィールドからURLを取得して商品情報を表示する
	 * @return String $html
	 */
	// public function generate_html_custom_field() {

	// 	global $post;

	// 	$amazon_index = get_post_custom_values( 'amazon', $post->ID );
	// 	if( $amazon_index ) {
	// 		$html = "";
	// 		foreach( $amazon_index as $content ) {
	// 			$html .= $this->replace( $content );
	// 		}
	// 		echo $html;
	// 	}
	// }

	/**
	 * APIを使用せずにリンクを生成する
	 * @return string $html
	 */
	private function generate_link_without_api() {
		$asin           = $this->asin;
		$search_keyword = $this->search_keyword;
		$marketplace    = $this->options->get_option( 'marketplace' );
		$partner_tag    = $this->options->get_option('partner_tag');

		$query = ( $asin ) ? 'dp/' . $asin . '/?' : 's/?k=' . urlencode( $search_keyword ) . '&';
		$query .= 'tag=' . $partner_tag;
		$amazon_url = 'https://' . $marketplace . '/' . $query;

		$html = "<p><a href=\"{$amazon_url}\">Amazon: {$search_keyword}</a></p>";

		return $html;
	}

	/**
	 * ASIN または AmazonのURL から商品情報を出力する
	 * @param string $request_type
	 * @param string $request_keyword
	 * @return string $item_link_html
	 */
	public function generate_link_html( $request_type, $request_keyword ) {

		$item_link_html = $this->generate_html( $request_type, $request_keyword );

		return $item_link_html;
		
	}

	/**
	 * 商品情報のhtmlを出力する
	 * @param string $request_type
	 * @param string $request_keyword
	 */
	private function generate_html( $request_type, $request_keyword ) {

		//オプションの設定が終わってない場合は置換せずに返す
		$flag = $this->options->isset_required_options();
		if( ! $flag ) return '';

		// $request_type    = $this->request_type;
		// $request_keyword = $this->request_keyword;

		if( $request_type == 'get' ) {
			if( strlen( $request_keyword ) != 10 ) {
				// URLだった場合（ASINではなかった場合）
				// URLからASINと商品名(name)を取得する
				$product_info_array = $this->parse_amazon_url( $request_keyword );
				$request_keyword = $product_info_array['asin']; // ASINをリクエストキーワードに設定
				$this->asin = $product_info_array['asin'];
				$this->search_keyword = $product_info_array['name'];
			}
		} elseif( $request_type == 'search' ) {
			$this->search_keyword = $request_keyword;
		}

		// $html_no_api = $this->generate_link_without_api();

		// リクエストの作成
		$request = new Request( [
			'request_type' => $request_type,
			'request_keyword' => $request_keyword
		] );

		try{

			// リクエストを投げる
			$json = $request->request();

		}catch( Exception $e ) {

			// エラー処理
      $messages = array();
      $current = $e;

    	// getPrevious() がある限り、過去のエラーを遡る
      while ($current) {
        $messages[] = $current->getMessage();
        $current = $current->getPrevious();
      }

			$error_message = '';
			$html_no_api = $this->generate_link_without_api();
			// $error_message .= $html_no_api;

			//管理者の場合のみエラーメッセージを表示
			if ( is_user_logged_in() ) {
				$error_message = '<div class="notice">' . "\n"
					. 'Amazonの商品情報取得時にエラーが発生したようです。<br />（このメッセージは管理者にのみ表示されています。）'  . "\n"
					. '<pre style="overflow-x: auto;">' . esc_html( implode( "\n", $messages ) ) . '</pre>' . "\n"
					. '</div>' . "\n";
			}

			return $html_no_api . $error_message;
		}

		//テンプレートを適用
		$item_html = $this->apply_template( $json );		

		return $item_html;

	}

	/**
	 * テンプレートからhtmlを生成する
	 * @param object $json
	 * @return string $item_html
	 */
	private function apply_template( $json ) {

		//テンプレート
		$template = $this->options->get_option( 'template' );

		// 検索キーワード
		$keyword = $this->search_keyword;

		// レスポンスの振り分け
		if( property_exists( $json, 'itemsResult' ) ) {
			$response_type = 'itemsResult';
			$item = $json->{'itemsResult'}->{'items'}[0];
		} elseif( property_exists( $json, 'searchResult' ) ) {
			$response_type = 'searchResult';
			$item = $json->{'searchResult'}->{'items'}[0];
		}

		// //アフィリエイトオプション
		// $aff = $options;
		
		//商品情報
		$itemInfo        = $item->{'itemInfo'};
		$byLineInfo      = $itemInfo->{'byLineInfo'} ?? null;
		$classifications = $itemInfo->{'classifications'} ?? null;

		$contentInfo = $itemInfo->{'contentInfo'} ?? null;
		$productInfo = $itemInfo->{'productInfo'} ?? null;
		$tradeInInfo = $itemInfo->{'tradeInInfo'} ?? null;

		$title        = $itemInfo->{'title'}->{'displayValue'}; // 商品名
		$url          = esc_url( $item->{'detailPageURL'} ); // リンクURL

		$productGroup = $classifications->{'productGroup'}->{'displayValue'} ?? null; //グループ
		$price        = $tradeInInfo->{'price'}->{'displayAmount'} ?? null; //価格

		//images
		$images = $item->{'images'}->{'primary'} ?? null;

		$eximg = property_exists( $images, 'small');
		$s_image_url = ( $eximg ) ? $images->{'small'}->{'url'}    : SIMPLE_AMAZON_IMG_URL . 'amazon_noimg_small.png';
		$s_image_h   = ( $eximg ) ? $images->{'small'}->{'height'} : 75;
		$s_image_w   = ( $eximg ) ? $images->{'small'}->{'width'}  : 75;

		$eximg = property_exists($images, 'medium');
		$m_image_url = ( $eximg ) ? $images->{'medium'}->{'url'}    : SIMPLE_AMAZON_IMG_URL . 'amazon_noimg.png';
		$m_image_h   = ( $eximg ) ? $images->{'medium'}->{'height'} : 160;
		$m_image_w   = ( $eximg ) ? $images->{'medium'}->{'width'}  : 160;

		$eximg = property_exists($images, 'large');
		$l_image_url = ( $eximg ) ? $images->{'large'}->{'url'}    : SIMPLE_AMAZON_IMG_URL . 'amazon_noimg_large.png';
		$l_image_h   = ( $eximg ) ? $images->{'large'}->{'height'} : 500;
		$l_image_w   = ( $eximg ) ? $images->{'large'}->{'width'}  : 500;

		// テンプレート出力 //
		ob_start();
		include( SIMPLE_AMAZON_PLUGIN_DIR . '/template/' . $template );
		$item_html = ob_get_contents();
		ob_end_clean();

		return $item_html;

	}

	/**
	 * AmazonのURLから商品名とasinを取得する
	 * @param string $url
	 * @return array $parse_array
	 */
	private function parse_amazon_url( $url ) {

		$pattern = '/https?:\/\/www\.amazon\.[a-z\.]+(?:\/(?<name>[^\/]+))?\/dp\/(?<asin>[A-Z0-9]{10})(?:\/|$)/';
		$parse_array = array(
			"name" => '',
			"asin" => ''
		);

		if ( preg_match( $pattern, $url, $matches ) ) {
      // 名前付きグループから値を取得
      // 商品名がない場合は空文字をセット
      $parse_array["name"] = !empty( $matches['name'] ) ? urldecode($matches['name']) : '';
      $parse_array["asin"] = $matches['asin'];
		}

		return $parse_array;
	}

}
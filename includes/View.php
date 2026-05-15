<?php
namespace Icoro\SimpleAmazon;
use Exception;

if ( ! defined( 'ABSPATH' ) ) exit;

/******************************************************************************
 * Amazon から取得した商品情報を操作するクラス
 *****************************************************************************/
class View {

	private Options $options;
	// private $lib;

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

//		$regexps[] = '/\[tmkm-amazon\](?P<asin>[A-Z0-9]{10,13})\[\/tmkm-amazon\]/';
		$regexps[] = '/<amazon>(?P<asin>[A-Z0-9]{10,13})<\/amazon>/';
//		$regexps[] = '/(^|<p>)https?:\/\/www\.(?P<domain>amazon\.com|amazon\.ca|amazon\.co\.uk|amazon\.fr|amazon\.de|amazon\.co\.jp)\/?(.*)\/(dp|gp\/product|gp\/aw\/d)\/(?P<asin>[A-Z0-9]{10}).*?($|<\/p>)/m';
		$regexps[] = '/(^|<p>)https?:\/\/www\.(?P<domain>amazon\.com\.au|amazon\.com\.br|amazon\.in|amazon\.sg|amazon\.com\.mx|amazon\.ae|amazon\.com\.tr|amazon\.ca|amazon\.de|amazon\.es|amazon\.fr|amazon\.it|amazon\.co\.jp|amazon\.co\.uk|amazon\.com)\/?(.*)\/(dp|gp\/product|gp\/aw\/d)\/(?P<asin>[A-Z0-9]{10}).*?($|<\/p>)/m';

		foreach( $regexps as $regexp ) {
			if( preg_match_all($regexp, $content, $arr) ) {
				for ( $i=0; $i<count($arr[0]); $i++ ) {
					$asin = $arr['asin'][$i];

					// $name = ( isset($arr['name'][$i]) ) ? urldecode($arr['name'][$i]) : '';

					$item_html = $this->generate_html_by_asin( $asin );

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
	 * ASINから商品情報を出力する
	 * @param string $asin
	 * @retun string $item_html
	 */
	public function generate_html_by_asin( $asin ) {
		$item_html = $this->generate_html( $asin, 'get' );
		return $item_html;
	}

	/**
	 * キーワードから商品情報を出力する
	 * @param string $word
	 * @retun string $item_html
	 */
	public function generate_html_by_word( $word ) {
		$item_html = $this->generate_html( $word, 'search' );
		return $item_html;
	}

	/**
	 * 商品情報のhtmlを出力する
	 * @param string $keyword
	 * @param string $request_type 'get' or 'search'
	 * @return string $item_html
	 */
	private function generate_html( $keyword, $request_type ) {

		$item_html = "";

		//オプションの設定が終わってない場合は置換せずに返す
		$flag = $this->options->isset_required_options();
		if( ! $flag ) {
			return $item_html;
		}

		$request = new Request();
		$request->set_request_type( $request_type );
		$request->set_keyword( $keyword );

		try{
			$json = $request->request();
		}catch( Exception $e ) {
      $messages = [];
      $current = $e;

    	// getPrevious() がある限り、過去のエラーを遡る
      while ($current) {
        $messages[] = $current->getMessage();
        $current = $current->getPrevious();
      }

			$error_message = '';

			//管理者の場合のみエラーメッセージを表示
			if ( is_user_logged_in() ) {
				$error_message = '<div class="notice">' . "\n"
					. 'Amazonの商品情報取得時にエラーが発生したようです。<br />（このメッセージは管理者にのみ表示されています。）'  . "\n"
					. '<pre>' . esc_html( implode( "\n", $messages ) ) . '</pre>' . "\n"
					. '</div>' . "\n";
			}

			return $error_message;
		}

		//テンプレートを適用
		$item_html = $this->apply_template( $json, $keyword );		

		return $item_html;

	}

	/**
	 * テンプレートからhtmlを生成する
	 * @param object $json
	 * @param string $keyword
	 * @return string $item_html
	 */
	private function apply_template( $json, $keyword ) {

		//テンプレート
		$template = $this->options->get_option( 'template' );

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
	
}
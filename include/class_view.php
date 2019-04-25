<?php
/******************************************************************************
 * Amazon から取得した商品情報を操作するクラス
 *****************************************************************************/
class SimpleAmazonView {

	private $options;
	private $lib;

	/**
	 * @param none
	 * @return none
	 */
	public function __construct() {

		global $simple_amazon_options;

		$this->options = $simple_amazon_options;
		$this->lib     = new SimpleAmazonLib();

	}
 
	/**
	 * 記事本文中のコードを個別商品表示 HTML に置き換える
	 * @param String $content
	 * @return String $content ( HTML )
	 */
	public function replace( $content ) { // 記事本文中の呼び出しコードを変換

		//オプションの設定が終わってない場合は置換せずに返す
		if( ! $this->lib->check_options( $this->options ) ) {
			return $content;
		}

//		$regexps[] = '/\[tmkm-amazon\](?P<asin>[A-Z0-9]{10,13})\[\/tmkm-amazon\]/';
		$regexps[] = '/<amazon>(?P<asin>[A-Z0-9]{10,13})<\/amazon>/';
		$regexps[] = '/(^|<p>)https?:\/\/www\.(?P<domain>amazon\.com|amazon\.ca|amazon\.co\.uk|amazon\.fr|amazon\.de|amazon\.co\.jp)\/?(.*)\/(dp|gp\/product|gp\/aw\/d)\/(?P<asin>[A-Z0-9]{10}).*?($|<\/p>)/m';

		foreach( $regexps as $regexp ) {
			if( preg_match_all($regexp, $content, $arr) ) {
				for ( $i=0; $i<count($arr[0]); $i++ ) {
					$asin = $arr['asin'][$i];
					// $name = ( isset($arr['name'][$i]) ) ? urldecode($arr['name'][$i]) : '';
					$domain_code = null;
					if( isset( $arr['domain'][$i] ) ) {
						$domain_code = trim( $arr['domain'][$i] );
					}

					//商品情報の出力
					$item = new SimpleAmazonItem( $domain_code );
					$item->set_asin( $asin );
					$item_html = $item->generate_html();
// echo '<!--';
// var_dump( $item );
// echo '-->';
					// URLの置換
					$content = str_replace( $arr[0][$i], $item_html, $content );
				}
			}
		}

		return $content;

	}
	
	/**
	 * PHP の関数として Amazon の個別商品 HTML を呼び出す
	 * @param String $asin
	 * @param String $code
	 * @param String $template
	 * @param Array $options
	 * @return String $html
	 */
	public function generate_html( $asin, $code = null, $template = null, $keyword = null, $options = null ) {

		$item_html = "";

		//オプションの設定が終わってない場合は置換せずに返す
		if( ! $this->lib->check_options( $this->options ) ) {
			return $item_html;
		}

		//商品情報の出力
		$item = new SimpleAmazonItem( $code );
		$item->set_asin( $asin );
		$item->set_keyword( $keyword );
		$item_html = $item->generate_html( $template, $options );

		return $item_html;

	}

	/**
	 * カスタムフィールドからURLを取得して商品情報を表示する
	 * @param none
	 * @return none
	 */
	public function generate_html_custom_field() {

		global $post;

		$amazon_index = get_post_custom_values( 'amazon', $post->ID );
		if( $amazon_index ) {
			$html = "";
			foreach( $amazon_index as $content ) {
				$html .= $this->replace( $content );
			}
			echo $html;
		}
	}

}


/******************************************************************************
 * 商品情報のクラス
 *****************************************************************************/
class SimpleAmazonItem {

	private $asin;
	private $keyword;

	private $domain;

	private $item;
	private $error_message;

	private $options;
	private $lib;

	/**
	 * @param String $domain_code
	 * @return none
	 */
	public function __construct( $domain_code = null ) {

		global $simple_amazon_options;

		$this->options = $simple_amazon_options;
		$this->lib     = new SimpleAmazonLib();
		$this->domain  = $this->lib->get_domain( trim( $domain_code ) );
	}
 
	/**
	 * ASINから商品情報を設定する
	 * @param String $asin
	 * @return none
	 */
	public function set_asin( $asin ) {

		// ISBN13をISBN10に変換
		if( strlen( $asin ) == 13 ) {
			$asin = $this->lib->calc_chkdgt_isbn10( substr( $asin, 3, 9 ) );
		}

		$this->asin = $asin;
		
	}

	/**
	 * キーワードから商品情報を設定する
	 * @param String $keyword
	 * @return none
	 */
	public function set_keyword( $keyword ) {

		$this->keyword = $keyword;

	}

	/**
	 * 商品情報のXMLオブジェクトを取得して設定する
	 * @param none
	 * @return none
	 */
	private function set_item_object()  {

		$aid = $this->lib->get_aid( $this->domain, $this->options ); //アソシエイトID
		$tld = $this->lib->get_TLD( $this->domain ); //TLD

		if( $this->asin ) {
			// ItemLookup
			$params = array(
				'AssociateTag'  => $aid,
				'MerchantId'    => 'All',
				'Condition'     => 'All',
				'ResponseGroup' => 'Images,ItemAttributes',
				'Operation'     => 'ItemLookup',
				'ItemId'        => $this->asin
			);
		} else {
			// ItemSearch
			$params = array(
				'AssociateTag'  => $aid,
				'MerchantId'    => 'All',
//				'Condition'     => 'New',
				'ResponseGroup' => 'Images,ItemAttributes',
				'Operation'     => 'ItemSearch',
				'SearchIndex'   => 'All',
				'Keywords'      => $this->keyword
			);
		}

		// レスポンスの取得
		// 正常に取得出来た場合は xml オブジェクトが、エラーの場合は文字列が返ってくる
		$parser = new SimpleAmazonXmlParse();
		$xml = $parser->getamazonxml( $tld, $params );

		// echo '<!--';
		// var_dump($xml);
		// echo '-->';
		
		if( is_string( $xml ) ) {
			//エラーログの出力
			$this->errorlog( $this->asin );

			//エラーメッセージ
			if ( is_user_logged_in() ) {
				$this->error_message = '<div class="notice">' . "\n"
						. 'Amazonの商品情報取得時に以下のエラーが発生したようです。<br />（このメッセージはログインしているユーザにのみ表示されています。）'  . "\n"
						. '<pre>' . $xml . '</pre>' . "\n"
						. '</div>' . "\n";
			}
		} else {
			$this->item = $xml->Items->Item;
		}

	}

	/**
	 * Amazon 商品の HTML を生成
	 * @param object $options
	 * @return string $item_html
	 */
	public function generate_html( $template = null, $options = null ) {

		//商品情報を設定
		$this->set_item_object();

		//商品情報がない場合はエラーメッセージを返す
		if( !$this->item ) {
			return $this->error_message;
		}

		//テンプレートの設定
		if( !file_exists( SIMPLE_AMAZON_PLUGIN_DIR . '/template/' . $template ) || !$template ) {
			$template = $this->options['template'];
		}

		// よく使いそうな項目をあらかじめ簡単な変数にしておく

		//キーワード
		$keyword = $this->keyword;

		//アフィリエイトオプション
		$aff = $options;
		
		//商品情報
		$item  = $this->item;
		$title = $item->ItemAttributes->Title; //商品名
		$url   = $item->DetailPageURL; //URL

		//images
		$eximg = property_exists($item, 'SmallImage');
		$s_image_url = ( $eximg ) ? $item->SmallImage->URL    : SIMPLE_AMAZON_IMG_URL . 'amazon_noimg_small.png';
		$s_image_h   = ( $eximg ) ? $item->SmallImage->Height : 75;
		$s_image_w   = ( $eximg ) ? $item->SmallImage->Width  : 75;

		$eximg = property_exists($item, 'MediumImage');
		$m_image_url = ( $eximg ) ? $item->MediumImage->URL    : SIMPLE_AMAZON_IMG_URL . 'amazon_noimg.png';
		$m_image_h   = ( $eximg ) ? $item->MediumImage->Height : 160;
		$m_image_w   = ( $eximg ) ? $item->MediumImage->Width  : 160;

		$eximg = property_exists($item, 'LargeImage');
		$l_image_url = ( $eximg ) ? $item->LargeImage->URL    : SIMPLE_AMAZON_IMG_URL . 'amazon_noimg_large.png';
		$l_image_h   = ( $eximg ) ? $item->LargeImage->Height : 500;
		$l_image_w   = ( $eximg ) ? $item->LargeImage->Width  : 500;

		// テンプレート出力 //
		ob_start();
		include( SIMPLE_AMAZON_PLUGIN_DIR . '/template/' . $template );
		$item_html = ob_get_contents();
		ob_end_clean();
		
		return $item_html;

	}

	/**
	 * エラーログを書き出す
	 * @return none
	 */
	private function errorlog() {

		global $post;

		$logfile = SIMPLE_AMAZON_CACHE_DIR . 'error.log';

		$url = get_permalink( $post->ID );
		$data = $url . ', ' . $this->asin . "\n";

		file_put_contents( $logfile, $data, FILE_APPEND );
	}

}
?>

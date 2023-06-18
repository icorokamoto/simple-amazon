<?php
/******************************************************************************
 * Amazon から取得した商品情報を操作するクラス
 *****************************************************************************/
class SimpleAmazonView {

	private $opt;
	private $lib;

	/**
	 * @param none
	 * @return none
	 */
	public function __construct() {
		$this->opt = new SimpleAmazonOptionsControl();
		$this->lib = new SimpleAmazonLib();
	}

	/**
	 * 記事本文中のコードを個別商品表示 HTML に置き換える
	 * @param String $content
	 * @return String $content ( HTML )
	 */
	public function replace( $content ) { // 記事本文中の呼び出しコードを変換

		//オプションの設定が終わってない場合は置換せずに返す
		$flag = $this->opt->isset_required_options();
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
					$domain = null;
					if( isset( $arr['domain'][$i] ) ) {
						$domain = trim( $arr['domain'][$i] );
					}

					//商品情報の出力
					$item = new SimpleAmazonItem();
					$item->set_domain( $domain );
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
		$flag = $this->opt->isset_required_options();
		if( ! $flag ) {
			return $item_html;
		}

		//商品情報の出力
		$item = new SimpleAmazonItem();
		$item->set_domain_bycode( $code );
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

	private $aid;
	private $asin;
	private $keyword;

	private $domain;

	private $error_message;

	private $opt;
	private $lib;

	/**
	 * @param String $domain_code
	 * @return none
	 */
	public function __construct() {
		$this->opt     = new SimpleAmazonOptionsControl();
		$this->lib     = new SimpleAmazonLib();
	}

	/**
	 * ドメインを設定する
	 * @param String $domain
	 * @return none
	 */
	public function set_domain( $domain ) {

		$domain = trim( $domain );

		$domain_list  = $this->opt->get_list( 'domain' );
		$flag = array_key_exists( $domain, $domain_list );

		if( ! $flag ) {
			$this->domain  = $this->opt->get_option( 'default_domain' );
		}

		$this->domain = $domain;
	}

	/**
	 * 国コードからドメインを設定する
	 * @param String $domain
	 * @return none
	 */
	public function set_domain_bycode( $code ) {

		$code = trim( $code );

		//ドメインを国コードに変換
		if( $code == 'com' )  $code = 'us';

		$code_domain_list  = $this->opt->get_list( 'domain', 'code' );
		$flag = array_key_exists( $code, $code_domain_list );
		
		if( $flag ) {
			$domain = $code_domain_list[$code];
		} else {
			$domain = $this->opt->get_option( 'default_domain' );
		}

		$this->domain = $domain;
	}

	/**
	 * ASINを設定する
	 * @param String $asin
	 * @return none
	 */
	public function set_asin( $asin ) {

		$asin = trim( $asin );

		// ISBN13をISBN10に変換
		if( strlen( $asin ) == 13 ) {
			$asin = $this->lib->calc_chkdgt_isbn10( substr( $asin, 3, 9 ) );
		}

		$this->asin = $asin;
		
	}

	/**
	 * キーワードを設定する
	 * @param String $keyword
	 * @return none
	 */
	public function set_keyword( $keyword ) {

		$this->keyword = trim( $keyword );

	}

	/**
	 * Amazon 商品の HTML を生成
	 * @param object $options
	 * @return string $item_html
	 */
	public function generate_html( $template = null, $options = null ) {

		//ドメインに対応するアソシエイトIDを取得
		$this->aid = $this->opt->get_aid( $this->domain );

		if( $this->aid ) {
			//商品情報を取得する
			$Item = $this->get_item_object();
		} else {
			$Item = "対応するアソシエイトIDが設定されていません";
		}

		//商品情報がない場合はエラーメッセージを返す
		//（商品情報 $Item にエラーメッセージが入っている）
		if( is_string( $Item ) ) {
			//管理者の場合のみエラーメッセージを表示
			if ( is_user_logged_in() ) {
				$error_message = '<div class="notice">' . "\n"
						. 'Amazonの商品情報取得時にエラーが発生したようです。<br />（このメッセージは管理者にのみ表示されています。）'  . "\n"
						. '<pre>' . esc_html( $Item ) . '</pre>' . "\n"
						. '</div>' . "\n";
				return $error_message;
			}
			// 管理者以外にはなにも表示しない
			return '';
		}

		//テンプレートの設定
		if( !file_exists( SIMPLE_AMAZON_PLUGIN_DIR . '/template/' . $template ) || !$template ) {
			$template = $this->opt->get_option('template');
		}

		// よく使いそうな項目をあらかじめ簡単な変数にしておく

		//キーワード
		$keyword = $this->keyword;

		//アフィリエイトオプション
		$aff = $options;
		
		//商品情報
		$ItemInfo        = $Item->{'ItemInfo'};
		$ByLineInfo      = $ItemInfo->{'ByLineInfo'} ?? null;
		$Classifications = $ItemInfo->{'Classifications'} ?? null;

		$ContentInfo = $ItemInfo->{'ContentInfo'} ?? null;
		$ProductInfo = $ItemInfo->{'ProductInfo'} ?? null;
		$TradeInInfo = $ItemInfo->{'TradeInInfo'} ?? null;

		$title        = $ItemInfo->{'Title'}->{'DisplayValue'}; // 商品名
		$url          = esc_url( $Item->DetailPageURL ); // リンクURL
		$ProductGroup = $Classifications->{'ProductGroup'}->{'DisplayValue'} ?? null; //グループ

		$Price        = $TradeInInfo->{'Price'}->{'DisplayAmount'} ?? null; //価格

		//images
		$images = $Item->{'Images'}->{'Primary'} ?? null;

		$eximg = property_exists( $images, 'Small');
		$s_image_url = ( $eximg ) ? $images->{'Small'}->{'URL'}    : SIMPLE_AMAZON_IMG_URL . 'amazon_noimg_small.png';
		$s_image_h   = ( $eximg ) ? $images->{'Small'}->{'Height'} : 75;
		$s_image_w   = ( $eximg ) ? $images->{'Small'}->{'Width'}  : 75;

		$eximg = property_exists($images, 'Medium');
		$m_image_url = ( $eximg ) ? $images->{'Medium'}->{'URL'}    : SIMPLE_AMAZON_IMG_URL . 'amazon_noimg.png';
		$m_image_h   = ( $eximg ) ? $images->{'Medium'}->{'Height'} : 160;
		$m_image_w   = ( $eximg ) ? $images->{'Medium'}->{'Width'}  : 160;

		$eximg = property_exists($images, 'Large');
		$l_image_url = ( $eximg ) ? $images->{'Large'}->{'URL'}    : SIMPLE_AMAZON_IMG_URL . 'amazon_noimg_large.png';
		$l_image_h   = ( $eximg ) ? $images->{'Large'}->{'Height'} : 500;
		$l_image_w   = ( $eximg ) ? $images->{'Large'}->{'Width'}  : 500;

		// テンプレート出力 //
		ob_start();
		include( SIMPLE_AMAZON_PLUGIN_DIR . '/template/' . $template );
		$item_html = ob_get_contents();
		ob_end_clean();
		
		return $item_html;

	}

	/**
	 * 商品情報のXMLオブジェクトを取得する
	 * @param none
	 * @return json $json
	 */
	private function get_item_object() {

		$searchItemRequest = new SearchItemsRequest();
		$searchItemRequest->PartnerType = "Associates";
		$searchItemRequest->PartnerTag = $this->aid;
		$searchItemRequest->Resources = [
			"Images.Primary.Small",
			"Images.Primary.Medium",
			"Images.Primary.Large",
			"ItemInfo.Title",
			"ItemInfo.ByLineInfo",
			"ItemInfo.ContentInfo",
			"ItemInfo.Classifications",
			"ItemInfo.ProductInfo",
			"ItemInfo.Title",
			"ItemInfo.TradeInInfo"
		];

		if( $this->asin ) {
			// getitems
			$path = '/paapi5/getitems';
			$searchItemRequest->ItemIds = array( $this->asin );
		} else {
			// searchitems
			$path = "/paapi5/searchitems";
			$searchItemRequest->Keywords = $this->keyword;
			$searchItemRequest->ItemCount = 1;
//			$searchItemRequest->SearchIndex = "All";
		}

		$payload = json_encode( $searchItemRequest );

		// レスポンスの取得
		// 正常に取得出来た場合は xml オブジェクトが、エラーの場合は文字列が返ってくる
		$parser = new SimpleAmazonParseJSON();
		$json_item = $parser->getamazonjson( $this->domain, $path, $payload );

		// echo '<!--';
		// var_dump( $json_item );
		// echo '-->';
		
		if( is_string( $json_item ) ) {
			//エラーログの出力
			$this->errorlog( $this->asin );
		}

		return $json_item;
	}

	/**
	 * エラーログを書き出す
	 * @return none
	 */
	private function errorlog() {

		global $post;

		$log_file = SIMPLE_AMAZON_CACHE_DIR . 'error.log';
		$log_line = 500; //ログの最大行数

		if( count( file( $log_file ) ) < $log_line ) {

			// log data
			$url = get_permalink( $post->ID );
			$date = date("Y/m/d H:i:s");
			$log = $url . ', ' . $this->asin . ', ' . $date . "\n";

			//ログの書き込み
			file_put_contents( $log_file, $log, FILE_APPEND );
		}
	}
}

class SearchItemsRequest {
    public $PartnerType;
    public $PartnerTag;
    public $Keywords;
    public $SearchIndex;
    public $Resources;
}

?>

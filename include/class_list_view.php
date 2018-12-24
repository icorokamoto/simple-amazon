<?php

/******************************************************************************
 * 記事本文中に Amazon から取得した商品情報を表示するクラス
 *****************************************************************************/
class SimpleAmazonListView {

	private $options;
	private $styles;
	private $lib;

	/**
	 * @param	none
	 * @return	none
	 */
	public function __construct() {

		global $simple_amazon_options;

		$this->options = $simple_amazon_options;
		$this->lib = new SimpleAmazonLib();
	}

	/**
	 * PHP の関数として Amazon の商品リストの HTML を呼び出す
	 * @param array $params = array(
	 *   //'ResponseGroup' => 'Images,ItemAttributes',
	 *   //'Operation'     => 'ItemSearch',
	 *   //'MerchantId'    => 'All',
	 *   //'Condition'     => 'All',
	 *     'SearchIndex'   => 'Books',
	 *     'BrowseNode'    => '466280',
	 *   //'Sort'          => 'salesrank',
	 *     'Power'         => $power
	 *	);
	 * @param string $code
	 * @param array $style
	 * @return none
	 */
	public function view( $params, $code, $style ) {

		$display = "";

		if( $this->lib->check_options( $this->options ) ) {
			$code    = esc_html($code);
			$domain  = $this->lib->get_domain($code);
			$display = $this->generate( $params, $domain, $style );
		}

		echo $display;

	}

	/**
	 * parserにパラメータを渡してレスポンスを得る
	 * @param array $params
	 * @param array $style
	 * @return string $html
	 */
	public function generate( $params, $domain, $styles ) {

		// style
		$default_styles = array(
			'imgsize'        => 'small',
			'before_list'    => '<ul>',
			'after_list'     => '</ul>',
			'before_li'      => '<li>',
			'after_li'       => '</li>',
			'show_thumbnail' => true,
			'show_title'     => true
		);
		$this->styles = wp_parse_args($styles, $default_styles);

		$domain = $this->lib->get_domain();
		$tld    = $this->lib->get_TLD($domain);

		// params
		$default_params = array(
			'AssociateTag'  => $this->lib->get_aid($tld, $this->options),
			'MerchantId'    => 'All',
			'Condition'     => 'All',
			'Operation'     => 'ItemSearch',
			'ResponseGroup' => ($this->styles['show_thumbnail']) ? 'Images,ItemAttributes' : 'ItemAttributes'
		);

		// MarketplaceDomain(というかjavari.jp)を設定
//		if( $domain == "javari.jp" )
//			$default_params['MarketplaceDomain'] = 'www.javari.jp';

		// $params として リクエストの配列が与えられた場合
		// 商品一覧のHTMLを取得
		$params = wp_parse_args($params, $default_params);

		// HTMLを取得 //

		// レスポンスの取得
		// 正常に取得出来た場合は xml オブジェクトが、エラーの場合は文字列が返ってくる
		$parser = new SimpleAmazonXmlParse();
		$xml = $parser->getamazonxml( $tld, $params );
		
		if( is_string($xml) ) {
			//エラーメッセージ
//			$html = $xml;
			$html = '<!--Amazonのサーバでエラーが起こっているかもしれません。ページを再読み込みしてみてください。-->';
		} else {
			//商品リストのHTML
			$html = $this->generate_list_html( $xml );
		}

		return $html;
	}


	/**
	 * 商品リストのHTMLを生成する
	 * @param Object $xml
	 * @return String $html
	 */
	private function generate_list_html( $xml ) {

		$imgsize        = $this->styles['imgsize'];
		$before_list    = $this->styles['before_list'];
		$after_list     = $this->styles['after_list'];
		$before_li      = $this->styles['before_li'];
		$after_li       = $this->styles['after_li'];
		$show_title     = $this->styles['show_title'];
		$show_thumbnail = $this->styles['show_thumbnail'];

		$items = $xml->Items->Item;

		$list = '';

		foreach($items as $item) {

			$list .= $before_li;

			$url = $item->DetailPageURL;
			$title = $item->ItemAttributes->Title;
			$author = $item->ItemAttributes->Author;

			if($show_thumbnail) {
				$img = $this->get_img($item, $imgsize);
				$img_src = $img->URL;
				$img_h =  $img->Height;
				$img_w =  $img->Width;
				$list .= "<a href=\"{$url}\" class=\"pub_img\" rel=\"nofollow\"><img src=\"{$img_src}\" width=\"{$img_w}\" height=\"{$img_h}\" title=\"{$title}\" /></a>";
			}

			if($show_title) {
				$pubdate = $item->ItemAttributes->PublicationDate;
				$list .= "<a href=\"{$url}\" rel=\"nofollow\">{$title}</a> <span class=\"pub_info\">{$author} {$pubdate}</span>";
			}

			$list .= $after_li;
		}

		$html = $before_list . $list . $after_list;

		return $html;
	}

	/**
	 * 画像のURL、width、heightを設定する
	 * @param Object $xml
	 * @param String $imgsize
	 * @return Object $img
	 */
	public function get_img( $xml, $imgsize ) {

		$img = new stdClass();

		if($xml == null )
			$xml = new stdClass();

		$default_img = array(
			'small'  => SIMPLE_AMAZON_IMG_URL . 'amazon_noimg_small.png',
			'medium' => SIMPLE_AMAZON_IMG_URL . 'amazon_noimg.png',
			'large'  => SIMPLE_AMAZON_IMG_URL . 'amazon_noimg_large.png'
		);

		switch( $imgsize ) {
			case 'small':
				if( property_exists($xml, 'SmallImage') ){
					$img = $xml->SmallImage;
				} else {
					$img->URL    = $default_img['small'];
					$img->Width  = 75;
					$img->Height = 75;
				}
				break;
			case 'large':
				if( property_exists($xml, 'LargeImage') ){
					$img = $xml->LargeImage;
				} else {
					$img->URL    = $default_img['large'];
					$img->Width  = 500;
					$img->Height = 500;
				}
				break;
			default:
				if( property_exists($xml, 'MediumImage') ){
					$img = $xml->MediumImage;
				} else {
					$img->URL    = $default_img['medium'];
					$img->Width  = 160;
					$img->Height = 160;
				}
		}

		return $img;

	}

}

?>

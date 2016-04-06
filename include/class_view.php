<?php

/******************************************************************************
 * 記事本文中に Amazon から取得した商品情報を表示するクラス
 *****************************************************************************/
class SimpleAmazonView {

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
	 * PHP の関数として Amazon の個別商品 HTML を呼び出す
	 * @param	string $asin
	 * @param	string $tld
	 * @param	array $style
	 * @return	none
	 */
	public function view( $asin, $code, $styles ) {

		$domain = $this->lib->get_domain($code);

		$display = $this->generate( $asin, $domain, $styles );
		echo $display;

	}

	/**
	 * カスタムフィールドから値を取得して商品情報を表示する
	 * @param	none
	 * @return	none
	 */
	public function view_custom_field() {

		global $post;

		$amazon_index = get_post_custom_values('amazon', $post->ID);
		if($amazon_index) {
			$html = '';
			foreach($amazon_index as $content) {
				$html .= $this->replace( $content );
			}
			echo $html;
		}
	}
	
	/**
	 * 記事本文中のコードを個別商品表示 HTML に置き換える
	 * @param	string $content
	 * @return	string $content ( HTML )
	 */
	public function replace($content) { // 記事本文中の呼び出しコードを変換

//		$regexps[] = '/\[tmkm-amazon\](?P<asin>[A-Z0-9]{10,13})\[\/tmkm-amazon\]/';
		$regexps[] = '/<amazon>(?P<asin>[A-Z0-9]{10,13})<\/amazon>/';
//		$regexps[] = '/(^|<p>)http:\/\/www\.(?P<domain>amazon\.com|amazon\.ca|amazon\.co\.uk|amazon\.fr|amazon\.de|amazon\.co\.jp|javari\.jp)\/(?P<name>[\S]+)\/dp\/(?P<asin>[A-Z0-9]{10}).*?($|<\/p>)/m';
//		$regexps[] = '/(^|<p>)http:\/\/www\.(?P<domain>amazon\.com|amazon\.ca|amazon\.co\.uk|amazon\.fr|amazon\.de|amazon\.co\.jp|javari\.jp)\/gp\/product\/(?P<asin>[A-Z0-9]{10}).*?($|<\/p>)/m';
		$regexps[] = '/(^|<p>)http:\/\/www\.(?P<domain>amazon\..+|javari\.jp)\/(?P<name>[\S]+)\/dp\/(?P<asin>[A-Z0-9]{10}).*?($|<\/p>)/m';
		$regexps[] = '/(^|<p>)http:\/\/www\.(?P<domain>amazon\..+|javari\.jp)\/gp\/product\/(?P<asin>[A-Z0-9]{10}).*?($|<\/p>)/m';

		$default_domain = $this->lib->get_domain();

		foreach( $regexps as $regexp ) {
			if( preg_match_all($regexp, $content, $arr) ) {
				for ($i=0; $i<count($arr[0]); $i++) {
					$asin = $arr['asin'][$i];
					$name = ( isset($arr['name'][$i]) ) ? urldecode($arr['name'][$i]) : '';

					if( isset($arr['domain'][$i]) ) {
						$domain = trim($arr['domain'][$i]);
//						$this->tld = $this->lib->get_TLD($this->domain);
					} else {
						$domain = $default_domain;
					}

					$display = $this->generate( $asin, $domain, array( 'name' => $name ) );

					// URLの置換
					$content = str_replace($arr[0][$i], $display, $content);
				}
			}
		}

		/* for WYSWYG Editer */
//	  $content = str_replace('<p><div class="simple-amazon-view">', '<div class="simple-amazon-view">', $content);
//	  $content = str_replace('<hr class="simple-amazon-clear" /></div></p>', '<hr class="simple-amazon-clear" /></div>', $content);

		return $content;

	}
	
	/**
	 * parserにパラメータを渡してレスポンスを得る
	 * @param string $asin
	 * @param array $style
	 * @return string $html
	 */
	public function generate( $asin, $domain, $styles ) {

		// ISBN13をISBN10に変換
		if( strlen( $asin ) == 13 ) {
			$generalfunclib = new CalcISBNLibrary();
			$asin = $generalfunclib->calc_chkdgt_isbn10( substr( $asin, 3, 9 ) );
		}

		// TLD
		$tld = $this->lib->get_TLD($domain);

		// params
		$params = array(
			'AssociateTag'  => $this->lib->get_aid($tld, $this->options),
			'MerchantId'    => 'All',
			'Condition'     => 'All',
			'Operation'     => 'ItemLookup',
			'ResponseGroup' => 'Images,ItemAttributes',
			'ItemId'        => $asin
		);

		// MarketplaceDomain(というかjavari.jp)を設定
		if( $domain == "javari.jp" )
			$params['MarketplaceDomain'] = 'www.javari.jp';

		// HTMLを取得 //

		// レスポンスの取得
		// 正常に取得出来た場合は xml オブジェクトが、エラーの場合は文字列が返ってくる
		$parser = new SimpleAmazonXmlParse();
		$xml = $parser->getamazonxml( $tld, $params );

		if( is_string($xml) ) {
//			$html = $this->generate_item_html_nonres( $params['ItemId'], $domain );
			$html = '<!--Amazonのサーバでエラーが起こっているかもしれません。ページを再読み込みしてみてください。-->';
		} else {
			$html = $this->generate_item_html( $xml );
		}

		return $html;
	}

	/**
	 * Amazon 商品の HTML を生成(レスポンスがない場合)
	 * @param string $asin ( ASIN )
	 * @return string $output ( HTML )
	 */
	private function generate_item_html_nonres( $asin, $domain ) {

		$tld = $this->lib->get_TLD($domain);
		$name = ($this->styles['name']) ? $this->styles['name'] : "Amazon.co.jpの詳細ページへ &raquo;";
		$tag = '?tag=' . $this->lib->get_aid($tld, $this->options);

		$amazonlink = 'http://www.' . $domain . '/dp/' . $asin . $tag;
		$amazonimg_url = 'http://images.amazon.com/images/P/' . $asin . '.09.THUMBZZZ.jpg';
		$amazonimg_size = '';

//		if( !$name ) $name = "Amazon.co.jpの詳細ページへ &raquo;";

		if( function_exists('getimagesize') ) {
			$imgsize = getimagesize( $amazonimg_url );
			if( $imgsize[0] == 1 && $imgsize[1] == 1 ) {
				//画像キメ打ち
				$amazonimg_url = SIMPLE_AMAZON_IMG_URL . '/amazon_noimg_small.png';
				$amazonimg_size = ' width="75" height="75"';
			} else {
				$amazonimg_size = ' ' . $imgsize[3];
			}
		}

		$amazonimg_tag = '<img src="' . $amazonimg_url . '"' . $amazonimg_size . ' class="sa-image" />';

		// レスポンスがない場合のテンプレート
//		$output = '<!--Amazonのサーバでエラーが起こっているかもしれません。一度ページを再読み込みしてみてください。-->';
		$output =
			"\n".'<div class="simple-amazon-view">' . "\n" .
			'<p class="sa-img-box"><a href="' . $amazonlink . '">' . $amazonimg_tag . '</a></p>' . "\n" .
			'<p class="sa-title"><a href="' . $amazonlink . '">' . $name . '</a></p>' . "\n" .
			'</div>';

		return $output;

	}

	/**
	 * Amazon 商品の HTML を生成 ( レスポンスがある場合 )
	 * @param object $AmazonXml ( レスポンス )
	 * @return string $output ( HTML )
	 */
	private function generate_item_html( $AmazonXml ) {

		$item = $AmazonXml->Items->Item;
//		$attr = $item->ItemAttributes;

		// よく使いそうな項目はあらかじめ簡単な変数にしておく

		//商品名
		$title = $item->ItemAttributes->Title;
		
		//URL
		$url  = $item->DetailPageURL;

		//images
		$s_image_url = $item->SmallImage->URL;
		$s_image_h   = $item->SmallImage->Height;
		$s_image_w   = $item->SmallImage->Width;

		$m_image_url = $item->MediumImage->URL;
		$m_image_h   = $item->MediumImage->Height;
		$m_image_w   = $item->MediumImage->Width;

		$l_image_url = $item->LargeImage->URL;
		$l_image_h   = $item->LargeImage->Height;
		$l_image_w   = $item->LargeImage->Width;

		// テンプレート //
		$template = $this->options['template'];

		ob_start();
		include( SIMPLE_AMAZON_PLUGIN_DIR . '/template/' . $template );
		$output = ob_get_contents();
		ob_end_clean();
		
		return $output;

	}

}

?>

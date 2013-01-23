<?php

/******************************************************************************
 * 記事本文中に Amazon から取得した商品情報を表示するクラス
 *****************************************************************************/
class SimpleAmazonView {

	private $options;
	private $style;
	private $domain;
	private $tld;
	private $img;

	/**
	 * @param	none
	 * @return	none
	 */
	public function __construct() {

		global $simple_amazon_options;

		$this->options = $simple_amazon_options;

		// デフォルトのドメインとTLDの設定
		switch( WPLANG ) {
			case 'en_CA': $this->domain = 'amazon.ca'; $this->tld = 'ca'; break;
			case 'zh_CN': $this->domain = 'amazon.cn'; $this->tld = 'cn'; break;
			case 'de_DE': $this->domain = 'amazon.de'; $this->tld = 'de'; break;
			case 'es_ES': $this->domain = 'amazon.es'; $this->tld = 'es'; break;
			case 'fr_FR': $this->domain = 'amazon.fr'; $this->tld = 'fr'; break;
			case 'it_IT': $this->domain = 'amazon.it'; $this->tld = 'it'; break;
			case 'ja':    $this->domain = 'amazon.co.jp'; $this->tld = 'jp'; break;
			case 'en_GB': $this->domain = 'amazon.co.uk'; $this->tld = 'uk'; break;
			default:      $this->domain = 'amazon.com'; $this->tld = 'com';
		}

		$this->img = array(
			'small'     => SIMPLE_AMAZON_IMG_URL . '/amazon_noimg_small.png',
			'medium'    => SIMPLE_AMAZON_IMG_URL . '/amazon_noimg.png',
			'large'     => SIMPLE_AMAZON_IMG_URL . '/amazon_noimg_large.png'
		);
	}

	/**
	 * PHP の関数として Amazon の個別商品 HTML を呼び出す
	 * @param	string $asin
	 * @param	string $tld
	 * @param	array $style
	 * @return	none
	 */
	public function view( $asin, $code, $style ) {

		if($code) {
			// set TLD
			$this->domain = $this->get_domain($code);
			$this->tld = $this->get_TLD($this->domain);
		}

		$display = $this->generate( $asin, $style );
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
		$regexps[] = '/(^|<p>)http:\/\/www\.(?P<domain>amazon\..+)\/(?P<name>[\S]+)\/dp\/(?P<asin>[A-Z0-9]{10}).*?($|<\/p>)/m';
		$regexps[] = '/(^|<p>)http:\/\/www\.(?P<domain>amazon\..+)\/dp\/(?P<asin>[A-Z0-9]{10}).*?($|<\/p>)/m';
		$regexps[] = '/(^|<p>)http:\/\/www\.(?P<domain>amazon\..+)\/gp\/product\/(?P<asin>[A-Z0-9]{10}).*?($|<\/p>)/m';

		foreach( $regexps as $regexp ) {
			if( preg_match_all($regexp, $content, $arr) ) {
				for ($i=0; $i<count($arr[0]); $i++) {
					$asin = $arr['asin'][$i];
					$name = ( isset($arr['name'][$i]) ) ? urldecode($arr['name'][$i]) : '';

					if( isset($arr['domain'][$i]) ) {
						$this->domain = trim($arr['domain'][$i]);
						$this->tld = $this->get_TLD($this->domain);
					}

					$display = $this->generate( $asin, array( 'name' => $name ) );

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
	public function generate( $asin, $style ) {

		// ISBN13をISBN10に変換 //
		if( strlen( $asin ) == 13 ) {
			$generalfunclib = new CalcISBNLibrary();
			$asin = $generalfunclib->calc_chkdgt_isbn10( substr( $asin, 3, 9 ) );
		}

		// default style
		$default_style = array(
			'name'        => '',
			'layout_type' => $this->options['layout_type'],
			'imgsize'     => $this->options['imgsize']
		);
		$this->style = wp_parse_args($style, $default_style);

		// params
		$params = array(
			'AssociateTag'  => $this->get_aid($this->tld),
			'MerchantId'    => 'All',
			'Condition'     => 'All',
			'Operation'     => 'ItemLookup',
			'ResponseGroup' => 'Images,ItemAttributes',
			'ItemId'        => $asin
		);

		// MarketplaceDomain(というかjavari.jp)を設定
		if( $this->domain == "javari.jp" )
			$params['MarketplaceDomain'] = 'www.javari.jp';

		// HTMLを取得 //

		// レスポンスの取得
		// 正常に取得出来た場合は xml オブジェクトが、エラーの場合は文字列が返ってくる
		$parser = new SimpleAmazonXmlParse();
		$xml = $parser->getamazonxml( $this->tld, $params );

		if( is_string($xml) ) {
//			$html = $this->generate_item_html_nonres( $params['ItemId'] );
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
	private function generate_item_html_nonres( $asin ) {

		$name = ($this->style['name']) ? $this->style['name'] : "Amazon.co.jpの詳細ページへ &raquo;";
		$tag = '?tag=' . $this->get_aid($this->tld);
		$windowtarget = $this->options['windowtarget'];

		switch( $windowtarget ) {
			case 'newwin': $windowtarget = ' target="_blank"'; break;
			case 'self': $windowtarget = '';
		}

		$amazonlink = 'http://www.' . $this->domain . '/dp/' . $asin . $tag;
		$amazonimg_url = 'http://images.amazon.com/images/P/' . $asin . '.09.THUMBZZZ.jpg';
		$amazonimg_size = '';

//		if( !$name ) $name = "Amazon.co.jpの詳細ページへ &raquo;";

		if( function_exists('getimagesize') ) {
			$imgsize = getimagesize( $amazonimg_url );
			if( $imgsize[0] == 1 && $imgsize[1] == 1 ) {
				$amazonimg_url = $this->img['small'];
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
			'<p class="sa-img-box"><a href="' . $amazonlink . '"' . $windowtarget . '>' . $amazonimg_tag . '</a></p>' . "\n" .
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

		$layout_type = $this->style['layout_type'];
		$imgsize = $this->style['imgsize'];
		$windowtarget = $this->options['windowtarget'];

		$item = $AmazonXml->Items->Item;
		$attr = $item->ItemAttributes;
		$url = $item->DetailPageURL;

		if( $layout_type != '3' )
			$img = $this->get_img( $item, $imgsize );

		switch( $windowtarget ) {
			case 'newwin': $windowtarget = ' target="_blank"'; break;
			case 'self': $windowtarget = '';
		}

		// テンプレート //
		//Title
		if( $layout_type == 3 ) {
			$output = '<a href="'.$url.'"' . $windowtarget . '>' . $attr->Title . '</a>';
		}

		//Title & Image
		if( $layout_type == 2 ) {
			$output = '<div class="simple-amazon-view">' . "\n";
			$output .= "\t" . '<p class="sa-img-box"><a href="'.$url.'"' . $windowtarget . '><img src="' . $img->URL . '" height="' . $img->Height . '" width="' . $img->Width . '" alt="" class="sa-image" /></a></p>' . "\n";
			$output .= "\t" . '<p class="sa-title"><a href="'.$url.'"' . $windowtarget . '>' . $attr->Title . '</a></p>' . "\n";
			$output .= '</div>' . "\n";
		}

		//Detail
		if( $layout_type == 1 ) {
			$output = '<div class="simple-amazon-view">' . "\n";
			$output .= "\t" . '<p class="sa-img-box"><a href="'.$url.'"' . $windowtarget . '><img src="' . $img->URL . '" height="' . $img->Height . '" width="' . $img->Width . '" alt="" class="sa-image" /></a></p>' . "\n";
			$output .= "\t" . '<p class="sa-title"><a href="'.$url.'"' . $windowtarget . '>' . $attr->Title . '</a></p>' . "\n";

			$output_list = "";

			switch($attr->ProductGroup) {
				case "Book":
					if( $attr->Author !="" ) {
						$output_list .= "\t" ."<li>著者／訳者：";
						if( count($attr->Author) == 1 ) {
							$output_list .= $attr->Author; 
						} else {
							foreach($attr->Author as $auth){ $output_list .= $auth.' '; }
						}
						$output_list .= "</li>\n";
					}
					$output_list .= "\t" . "<li>出版社：" . $attr->Manufacturer . "( " . $attr->PublicationDate . " )</li>" . "\n";
					$output_list .= "\t" . "<li>" . $attr->Binding . "：" . $attr->NumberOfPages . " ページ</li>\n";
					break;
				case "DVD":
					$output_list .= "\t" . "<li>販売元：" . $attr->Manufacturer . "( " . $attr->ReleaseDate . " )</li>" . "\n";
					$output_list .= "\t" . "<li>時間：" . $attr->RunningTime . " 分</li>" . "\n";
					$output_list .= "\t" . "<li>" . $attr->NumberOfDiscs . " 枚組 ( " . $attr->Binding . " )</li>\n";
					break;
				case "Music":
					$output_list .= "<li>アーティスト：" . $attr->Artist . "</li>" . "\n";
					$output_list .= "\t" . "<li>レーベル：" . $attr->Manufacturer . "( " . $attr->ReleaseDate . " )</li>\n";
					break;
				default:
					if( $attr->ReleaseDate ) $output_list .= "\t" . "<li>発売日：" . $attr->ReleaseDate . "</li>\n";
			}

			if ( $output_list ) {
				$output .= "\t" .'<ul class="sa-detail">' . "\n";
				$output .= $output_list;
				$output .= "\t" . '</ul>' . "\n";
			}

			$output .= '</div>' . "\n";
		}

		//Full
		if( $layout_type < 1 ) {
			$output = '<div class="simple-amazon-view">' . "\n";
			$output .= "\t" . '<p class="sa-img-box"><a href="' . $url . '"' . $windowtarget . '><img src="' . $img->URL . '" height="' . $img->Height . '" width="' . $img->Width . '" alt="" class="sa-image" /></a></p>' . "\n";
			$output .= "\t" . '<p class="sa-title"><a href="' . $url . '"' . $windowtarget . '>' . $attr->Title . '</a></p>' . "\n";

			$output_list = "";

			switch($attr->ProductGroup) {
				case "Book":
					if( $attr->Author !="" ) {
						$output_list .= "\t" ."<li>著者／訳者：";
						if( count($attr->Author) == 1 ) {
							$output_list .= $attr->Author; 
						} else {
							foreach($attr->Author as $auth){ $output_list .= $auth.' '; }
						}
						$output_list .= "</li>\n";
					}
					$output_list .= "\t" . "<li>出版社：" . $attr->Manufacturer . "( " . $attr->PublicationDate . " )</li>" . "\n";
					$output_list .= "\t" . "<li>" . $attr->Binding . "：" . $attr->NumberOfPages . " ページ</li>\n";
					$output_list .= "\t" . "<li>ISBN-10 : " . $attr->ISBN . "</li>\n";
					$output_list .= "\t" . "<li>ISBN-13 : " . $attr->EAN . "</li>\n";
					break;
				case "DVD":
					$output_list .= "\t" . "<li>販売元：" . $attr->Manufacturer . "( " . $attr->ReleaseDate . " )</li>" . "\n";
					$output_list .= "\t" . "<li>時間：" . $attr->RunningTime . " 分</li>" . "\n";
					$output_list .= "\t" . "<li>" . $attr->NumberOfDiscs . " 枚組 ( " . $attr->Binding . " )</li>\n";
					break;
				case "Music":
					$output_list .= "<li>アーティスト：" . $attr->Artist . "</li>" . "\n";
					$output_list .= "\t" . "<li>レーベル：" . $attr->Manufacturer . "( " . $attr->ReleaseDate . " )</li>\n";
					break;
				default:
					if( $attr->ReleaseDate ) $output_list .= "\t" . "<li>発売日：" . $attr->ReleaseDate . "</li>\n";
					if( $attr->Binding ) $output_list .= "\t" . "<li>カテゴリ：" . $attr->Binding . "</li>\n";
			}
			if( $attr->ListPrice->FormattedPrice ) $output_list .= "\t" . "<li>定価：" . $attr->ListPrice->FormattedPrice . "</li>\n";
//			if( $rating ) $output_list .= "\t" . "<li>おすすめ度：" . $rating . "</li>\n";

			if ( $output_list ) {
				$output .= "\t" .'<ul class="sa-detail">' . "\n";
				$output .= $output_list;
				$output .= "\t" . '</ul>' . "\n";
			}
			$output .= '</div>' . "\n";
		}

		return $output;

	}
	

	/**
	 * 国コードからドメインを取得する
	 * @param String $code
	 * @return String $domain
	 */
	private function get_domain( $code ) {
		switch($code) {
			case 'ca':        $domain = 'amazon.ca'; break;
			case 'cn':        $domain = 'amazon.cn'; break;
			case 'de':        $domain = 'amazon.de'; break;
			case 'es':        $domain = 'amazon.es'; break;
			case 'fr':        $domain = 'amazon.fr'; break;
			case 'it':        $domain = 'amazon.it'; break;
			case 'jp':        $domain = 'amazon.co.jp'; break;
			case 'uk':        $domain = 'amazon.co.uk'; break;
			case 'javari.jp': $domain = 'javari.jp'; break;
			case 'us':        $domain = 'amazon.com'; break;
			default:          $domain = $this->domain;
		}
		return $domain;
	}

	/**
	 * ドメインからTLDを取得する
	 * @param String $domain
	 * @return String $tld
	 */
	private function get_TLD( $domain ) {
		switch($domain) {
			case 'amazon.ca':    $tld = 'ca'; break;
			case 'amazon.cn':    $tld = 'cn'; break;
			case 'amazon.de':    $tld = 'de'; break;
			case 'amazon.es':    $tld = 'es'; break;
			case 'amazon.fr':    $tld = 'fr'; break;
			case 'amazon.it':    $tld = 'it'; break;
			case 'amazon.co.jp': $tld = 'jp'; break;
			case 'amazon.co.uk': $tld = 'uk'; break;
			case 'javari.jp':    $tld = 'jp'; break;
			case 'amazon.com':   $tld = 'com'; break;
			default:             $tld = $this->tld;
		}
		return $tld;
	}

	/**
	 * TLDからアソシエイトIDを取得する
	 * @param String $tld
	 * @return String $aid
	 */
	private function get_aid( $tld ) {
		switch($tld) {
			case 'ca': $aid = $this->options['associatesid_ca']; break;
			case 'cn': $aid = $this->options['associatesid_cn']; break;
			case 'de': $aid = $this->options['associatesid_de']; break;
			case 'es': $aid = $this->options['associatesid_es']; break;
			case 'fr': $aid = $this->options['associatesid_fr']; break;
			case 'it': $aid = $this->options['associatesid_it']; break;
			case 'jp': $aid = $this->options['associatesid_jp']; break;
			case 'uk': $aid = $this->options['associatesid_uk']; break;
			case 'com': $aid = $this->options['associatesid_us']; break;
			default: $aid = '';
		}
		return $aid;
	}

	/**
	 * 画像のURL、width、heightを設定する
	 * @param Object $xml
	 * @param String $imgsize
	 * @return Object $img
	 */
	private function get_img( $xml, $imgsize ) {

		$img = new stdClass();

		switch( $imgsize ) {
			case 'small':
				if( property_exists($xml, 'SmallImage') ){
					$img = $xml->SmallImage;
				} else {
					$img->URL    = $this->img['small'];
					$img->Width  = 75;
					$img->Height = 75;
				}
				break;
			case 'large':
				if( property_exists($xml, 'LargeImage') ){
					$img = $xml->LargeImage;
				} else {
					$img->URL    = $this->img['large'];
					$img->Width  = 500;
					$img->Height = 500;
				}
				break;
			default:
				if( property_exists($xml, 'MediumImage') ){
					$img = $xml->MediumImage;
				} else {
					$img->URL    = $this->img['medium'];
					$img->Width  = 160;
					$img->Height = 160;
				}
		}

//		var_dump($img);

		return $img;

	}

}

/******************************************************************************
 * ISBN13とISBN10を相互に変換するクラス
 *****************************************************************************/
class CalcISBNLibrary {

	public function calc_chkdgt_mod10( $val ){
		$f = 0;
		$g = 0;
		$k = 0;

		$mod_res = explode( ',',chunk_split( $val, 1, ',' ) );
		for( $ii=count( $mod_res )-1; $ii>-1; $ii-- ){
			$x=intval( $mod_res[$ii] );
			if( $f == 0 ){
				$k += $x;
				$f = 1;
			} else {
				$g += $x;
				$f = 0;
			}
		}
		$chkdgt = substr( strval( 10-intval( substr( strval( $g*3 + $k ), -1 ) ) ),-1 );
		return $val.$chkdgt;
	}

	public function calc_chkdgt_isbn10( $val ){
		$g = 0;

		$mod_res = explode( ',',chunk_split( $val, 1, ',' ) );
		for( $ii=count( $mod_res )-1; $ii>-1; $ii-- ){
			$x=intval( $mod_res[$ii] );
			$g += $x*( 11-( $ii+1 ));
		}

		$checksum = (( (int)( $g/11 ) )+1)*11 - $g;

		if( $checksum == 11 ){
			$chkdgt = 0;
		} elseif( $checksum == 10 ){
			$chkdgt = 'X';
		} else {
			$chkdgt = $checksum;
		}

		return $val.$chkdgt;
	}

}

?>

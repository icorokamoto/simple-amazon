<?php

/******************************************************************************
 * 記事本文中に Amazon から取得した商品情報を表示するクラス
 *****************************************************************************/
class SimpleAmazonView {

	private $options;
	private $style;
	private $domain;
	private $tld;
//	private $aid;
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
	 * 記事本文中のコードを個別商品表示 HTML に置き換える
	 * @param	string $content
	 * @return	string $content ( HTML )
	 */
	public function replace($content) { // 記事本文中の呼び出しコードを変換

//		$regexps[] = '/\[tmkm-amazon\](?P<asin>[A-Z0-9]{10,13})\[\/tmkm-amazon\]/';
		$regexps[] = '/<amazon>(?P<asin>[A-Z0-9]{10,13})<\/amazon>/';
//		$regexps[] = '/(^|<p>)http:\/\/www\.(?P<domain>amazon\.com|amazon\.ca|amazon\.co\.uk|amazon\.fr|amazon\.de|amazon\.co\.jp|javari\.jp)\/(?P<name>[\S]+)\/dp\/(?P<asin>[A-Z0-9]{10}).*?($|<\/p>)/m';
//		$regexps[] = '/(^|<p>)http:\/\/www\.(?P<domain>amazon\.com|amazon\.ca|amazon\.co\.uk|amazon\.fr|amazon\.de|amazon\.co\.jp|javari\.jp)\/gp\/product\/(?P<asin>[A-Z0-9]{10}).*?($|<\/p>)/m';
		$regexps[] = '/(^|<p>)http:\/\/www\.(?P<domain>.+)\/(?P<name>[\S]+)\/dp\/(?P<asin>[A-Z0-9]{10}).*?($|<\/p>)/m';
		$regexps[] = '/(^|<p>)http:\/\/www\.(?P<domain>.+)\/gp\/product\/(?P<asin>[A-Z0-9]{10}).*?($|<\/p>)/m';

		foreach( $regexps as $regexp ) {
			if( preg_match_all($regexp, $content, $arr) ) {
				for ($i=0; $i<count($arr[0]); $i++) {
					$asin = $arr['asin'][$i];
					$name = ( isset($arr['name'][$i]) ) ? $arr['name'][$i] : '';

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

//		$display = $this->htmlGenerater->format_amazon( $domain, $asin, $name );
		$display = $this->generate( $asin, $style );
		echo $display;

	}

	/**
	 * カスタムフィールドから値を取得して商品情報を表示する
	 * @param	string $content
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
	 * parserにパラメータを渡してレスポンスを得る
	 * @param array or string $params
	 * @param string $domain ( ドメイン: amazon.ca/cn/de/es/fr/it/co.jp/co.uk/com/javari.jp )
	 * @param array $style
	 * @return string $html
	 */
//	public function generate( $params, $domain, $style ) {
	public function generate( $params, $style ) {

		// style
		$default_style = array(
			'name'           => '',
			'layout_type'    => $this->options['layout_type'],
			'imgsize'        => $this->options['imgsize'],
			'before_list'    => '<ul>',
			'after_list'     => '</ul>',
			'before_li'      => '<li>',
			'after_li'       => '</li>',
			'show_thumbnail' => true,
			'show_title'     => true
		);
		$this->style = wp_parse_args($style, $default_style);

		// params
		$default_params = array(
			'AssociateTag' => $this->get_aid($this->tld),
			'MerchantId'	=> 'All',
			'Condition'	 => 'All'
		);

		// MarketplaceDomain(というかjavari.jp)を設定
		if( $this->domain == "javari.jp" )
			$default_params['MarketplaceDomain'] = 'www.javari.jp';

		// HTMLを取得 //
		if( is_string($params) ) {

			// 商品情報のHTMLを取得
			$params = wp_parse_args( array(
				'Operation'	 => 'ItemLookup',
				'ResponseGroup' => 'Images,ItemAttributes',
				'ItemId'		=> $params
			), $default_params);
			$html = $this->generate_item( $params );

		} else {

			// 商品一覧のHTMLを取得
			$params = array_merge( array(
				'Operation'	 => 'ItemSearch',
				'ResponseGroup' => ($this->style['show_thumbnail']) ? 'Images,ItemAttributes' : 'ItemAttributes'
			), $params);
			$params = wp_parse_args($params, $default_params);
			$html = $this->generate_list( $params );

		}
		return $html;
	}

	/**
	 * @brief	商品情報の HTML を生成
	 * @param	string $domain ( ドメイン: amazon.ca/cn/de/es/fr/it/co.jp/co.uk/com/javari.jp )
	 * @param	string $asin ( ASIN )
	 * @param	array $style
	 * @return	string $output ( HTML )
	 */
	private function generate_item( $params ) {

		// ISBN13をISBN10に変換 //
		if( strlen( $params['ItemId'] ) == 13 ) {
			$generalfunclib = new CalcISBNLibrary();
			$params['ItemId'] = $generalfunclib->calc_chkdgt_isbn10( substr( $params['ItemId'], 3, 9 ) );
		}

		// レスポンスの取得
		// 正常に取得出来た場合は xmlオブジェクトが、エラーの場合は文字列が返ってくる
		$xml = $this->get_xml( $params );

		if( is_string($xml) ) {
			$output = $this->generate_item_html_nonres( $asin );
		} else {
			$output = $this->generate_item_html( $xml );
		}

		return $output;

	}

	/**
	 * @brief	Amazon 商品の HTML を生成(レスポンスがない場合)
	 * @param	string $domain ( ドメイン: amazon.ca/cn/de/es/fr/it/co.jp/co.uk/com/javari.jp )
	 * @param	string $asin ( ASIN )
	 * @param	string $name ( 商品名 )
	 * @return	string $output ( HTML )
	 */
	private function generate_item_html_nonres( $asin ) {

		$name = ($this->style['name']) ? urldecode($this->style['name']) : "Amazon.co.jpの詳細ページへ &raquo;";
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
	 * @brief	Amazon 商品の HTML を生成 ( レスポンスがある場合 )
	 * @param	object $AmazonXML ( レスポンス )
	 * @return	string $output ( HTML )
	 */
	private function generate_item_html( $AmazonXml ) {

//		if( isset($this->style['layout_type']) )
//			$this->options['layout_type'] = $this->style['layout_type'];
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
			$output .= "\t" . '<p class="sa-img-box"><a href="'.$url.'"' . $windowtarget . '><img src="' . $img->url . '" height="' . $img->height . '" width="' . $img->width . '" alt="" class="sa-image" /></a></p>' . "\n";
			$output .= "\t" . '<p class="sa-title"><a href="'.$url.'"' . $windowtarget . '>' . $attr->Title . '</a></p>' . "\n";
			$output .= '</div>' . "\n";
		}

		//Detail
		if( $layout_type == 1 ) {
			$output = '<div class="simple-amazon-view">' . "\n";
			$output .= "\t" . '<p class="sa-img-box"><a href="'.$url.'"' . $windowtarget . '><img src="' . $img->url . '" height="' . $img->height . '" width="' . $img->width . '" alt="" class="sa-image" /></a></p>' . "\n";
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
			$output .= "\t" . '<p class="sa-img-box"><a href="' . $url . '"' . $windowtarget . '><img src="' . $img->url . '" height="' . $img->height . '" width="' . $img->width . '" alt="" class="sa-image" /></a></p>' . "\n";
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

	private function generate_list( $params ) {

		// レスポンスの取得
		// 正常に取得出来た場合は xmlオブジェクトが、エラーの場合は文字列が返ってくる
		$xml = $this->get_xml( $params );

		if( is_string($xml) ) {
			$output = $xml;
		} else {
			$output = $this->generate_list_html( $xml );
		}

		return $output;
	}

	private function generate_list_html( $xml ) {

		$imgsize        = $this->style['imgsize'];
		$before_list    = $this->style['before_list'];
		$after_list     = $this->style['after_list'];
		$before_li      = $this->style['before_li'];
		$after_li       = $this->style['after_li'];
		$show_title     = $this->style['show_title'];
		$show_thumbnail = $this->style['show_thumbnail'];

		$items = $xml->Items->Item;

		$list = '';

		foreach($items as $item) {

			$list .= $before_li;

			$url = $item->DetailPageURL;
			$title = $item->ItemAttributes->Title;
			$author = $item->ItemAttributes->Author;

			if($show_thumbnail) {
				$img = $this->get_img($item, $imgsize);
				$img_src = $img->url;
				$img_h =  $img->height;
				$img_w =  $img->width;
				$list .= "<a href=\"{$url}\" class=\"pub_img\"><img src=\"{$img_src}\" width=\"{$img_w}\" height=\"{$img_h}\" title=\"{$title}\" /></a>";
			}

			if($show_title) {
				$pubdate = $item->ItemAttributes->PublicationDate;
				$list .= "<a href=\"{$url}\">{$title}</a> <span class=\"pub_info\">{$author} {$pubdate}</span>";
			}

			$list .= $after_li;
		}

		$html = $before_list . $list . $after_list;

		return $html;
	}

	/**
	 * parserにパラメータを渡してレスポンスを得る
	 * @param array $params
	 * @param string $name ( 商品名 )
	 * @param string $domain ( ドメイン: amazon.ca/cn/de/es/fr/it/co.jp/co.uk/com/javari.jp )
	 * @return object $parsed_data
	 * @return string $parsed_data (レスポンスがエラーだった場合)
	 */
	private function get_xml( $params ) {

		$parser = new SimpleAmazonXmlParse();
		$parsed_data = $parser->getamazonxml( $this->tld, $params );

//		DEBUG
//		$parsed_data = false;
//		echo "<pre>\n";
//		print_r($parsed_data);
//		echo "</pre>\n";

		return $parsed_data;

	}

	/**
	 * @brief	国コードからドメインを取得する
	 * @param	string $domain
	 * @return	object $img
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
	 * @brief	ドメインからTLDを取得する
	 * @param	string $domain
	 * @return	object $img
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
	 * @brief	TLDからアソシエイトIDを取得する
	 * @param	string $domain
	 * @return	object $img
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
	 * @brief	画像のURL、width、heightを設定する
	 * @param	object $xml
	 * @return	object $img
	 */
	private function get_img( $xml, $imgsize ) {

		$img->url    = '';
//		$img->size   = '';
		$img->width  = 0;
		$img->height = 0;

		switch( $imgsize ) {
			case 'small':
				$temp = $xml->SmallImage;
				if( !$item->URL ) {
					$img->url		= $this->img['small'];
//					$img->size		= ' height="75" width="75"';
					$img->width		= 75;
					$img->height	= 75;
				}
				break;
			case 'large':
				$temp = $xml->LargeImage;
				if( !$temp->URL ) {
					$img->url		= $this->img['large'];
//					$img->size		= ' height="500" width="500"';
					$img->width		= 500;
					$img->height	= 500;
				}
				break;
			default:
				$temp = $xml->MediumImage;
//				var_dump($temp);
				if( !$temp->URL ) {
					$img->url		= $this->img['medium'];
//					$img->size		= ' height="160" width="160"';
					$img->width		= 160;
					$img->height	= 160;
				}
		}

		if( !$img->url ) {
			$img->url		= $temp->URL;
//			$img->size 		= ' height="' . $temp->Height . '" width="' . $temp->Width . '"';
			$img->width		= $temp->Width;
			$img->height	= $temp->Height;
//var_dump($img->url);
		}

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

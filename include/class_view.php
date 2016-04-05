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
		$regexps[] = '/(^|<p>)http:\/\/www\.(?P<domain>.+)\/(?P<name>[\S]+)\/dp\/(?P<asin>[A-Z0-9]{10}).*?($|<\/p>)/m';
		$regexps[] = '/(^|<p>)http:\/\/www\.(?P<domain>.+)\/gp\/product\/(?P<asin>[A-Z0-9]{10}).*?($|<\/p>)/m';

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

		//style
		$default_styles = array(
			'name'         => '',
			'layout_type'  => $this->options['layout_type'],
			'imgsize'      => $this->options['imgsize'],
			'windowtarget' => $this->options['windowtarget']
		);
		$this->styles = wp_parse_args($styles, $default_styles);

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
		$windowtarget = $this->styles['windowtarget'];

		switch( $windowtarget ) {
			case 'newwin': $windowtarget = ' target="_blank"'; break;
			case 'self': $windowtarget = '';
		}

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

		$layout_type  = $this->styles['layout_type'];
		$imgsize      = $this->styles['imgsize'];
		$windowtarget = $this->styles['windowtarget'];

		switch( $windowtarget ) {
			case 'self' : $windowtarget = ''; break;
			default     : $windowtarget = ' target="_blank"';
		}

		$item = $AmazonXml->Items->Item;
		$attr = $item->ItemAttributes;
		$url  = $item->DetailPageURL;

		// テンプレート //
		
		//image
		if( $layout_type == 'image' ) {
			$img = $this->lib->get_img($item, $imgsize);
			$output = '<a href="'.$url.'"' . $windowtarget . ' rel="nofollow"><img src="' . $img->URL . '" height="' . $img->Height . '" width="' . $img->Width . '" title="' . $attr->Title . '" class="sa-image" /></a>';
		}
		
		//Title
		elseif( $layout_type == 3 || $layout_type == 'title' ) {
			$output = '<a href="'.$url.'"' . $windowtarget . ' rel="nofollow">' . $attr->Title . '</a>';
		}

		//Title & Image
		elseif( $layout_type == 2 || $layout_type == 'simple' ) {

			$img = $this->lib->get_img($item, $imgsize);

			$output = '<div class="simple-amazon-view">' . "\n";
			$output .= "\t" . '<p class="sa-img-box"><a href="'.$url.'"' . $windowtarget . ' rel="nofollow"><img src="' . $img->URL . '" height="' . $img->Height . '" width="' . $img->Width . '" alt="" class="sa-image" /></a></p>' . "\n";
			$output .= "\t" . '<p class="sa-title"><a href="'.$url.'"' . $windowtarget . ' rel="nofollow">' . $attr->Title . '</a></p>' . "\n";
			$output .= '</div>' . "\n";
		}

/*
		//Detail
		elseif( $layout_type == 1 || $layout_type == 'detail' ) {

			$img = $this->lib->get_img($item, $imgsize);

			$output = '<div class="simple-amazon-view">' . "\n";
			$output .= "\t" . '<p class="sa-img-box"><a href="'.$url.'"' . $windowtarget . ' rel="nofollow"><img src="' . $img->URL . '" height="' . $img->Height . '" width="' . $img->Width . '" alt="" class="sa-image" /></a></p>' . "\n";
			$output .= "\t" . '<p class="sa-title"><a href="'.$url.'"' . $windowtarget . ' rel="nofollow">' . $attr->Title . '</a></p>' . "\n";

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
					if( $attr->Manufacturer ) $output_list .= "\t" . "<li>メーカー：" . $attr->Manufacturer . "</li>\n";
					if( $attr->Binding ) $output_list .= "\t" . "<li>カテゴリ：" . $attr->Binding . "</li>\n";
					if( $attr->ReleaseDate ) $output_list .= "\t" . "<li>発売日：" . $attr->ReleaseDate . "</li>\n";
			}

			if ( $output_list ) {
				$output .= "\t" .'<ul class="sa-detail">' . "\n";
				$output .= $output_list;
				$output .= "\t" . '</ul>' . "\n";
			}

			$output .= '</div>' . "\n";

		}
*/
		//Full
		else {

			$img = $this->lib->get_img($item, $imgsize);

			$output = '<div class="simple-amazon-view">' . "\n";
			$output .= "\t" . '<p class="sa-img-box"><a href="' . $url . '"' . $windowtarget . ' rel="nofollow"><img src="' . $img->URL . '" height="' . $img->Height . '" width="' . $img->Width . '" alt="" class="sa-image" /></a></p>' . "\n";
			$output .= "\t" . '<p class="sa-title"><a href="' . $url . '"' . $windowtarget . ' rel="nofollow">' . $attr->Title . '</a></p>' . "\n";

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
//					$feature_array = array();
//					foreach($attr->Feature as $f) {
//						array_push($feature_array, $f);
//					}
//					$feature = implode(' / ', $feature_array);

					if( $attr->Manufacturer ) $output_list .= "\t" . "<li>メーカー：" . $attr->Manufacturer . "</li>\n";
					if( $attr->Binding ) $output_list .= "\t" . "<li>カテゴリ：" . $attr->Binding . "</li>\n";
					if( $attr->ReleaseDate ) $output_list .= "\t" . "<li>発売日：" . $attr->ReleaseDate . "</li>\n";
//					if( $feature ) $output_list .= "\t" . "<li>" . $feature . "</li>\n";
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

}

?>

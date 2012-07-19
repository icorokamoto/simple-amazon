<?php

/******************************************************************************
 * Amazon ECS から取得した XML から HTML を生成
 *****************************************************************************/
class SimpleAmazonGenerateHtml {

	private $conf;
	private $amazonparse;
	private $generalfunclib;

	/**
	 * @param	none
	 * @return	object $this
	 */
	function __construct() {

		global $simple_amazon_options;

		$this->conf->layout_type	= $simple_amazon_options['layout_type'];
		$this->conf->windowtarget	= $simple_amazon_options['windowtarget'];
		$this->conf->imgsize		= $simple_amazon_options['imgsize'];

		$this->conf->aid = array (
			'amazon.ca'		=> $simple_amazon_options['associatesid_ca'],
			'amazon.cn'		=> $simple_amazon_options['associatesid_cn'],
			'amazon.de'		=> $simple_amazon_options['associatesid_de'],
			'amazon.es'		=> $simple_amazon_options['associatesid_es'],
			'amazon.fr'		=> $simple_amazon_options['associatesid_fr'],
			'amazon.it'		=> $simple_amazon_options['associatesid_it'],
			'amazon.co.jp'	=> $simple_amazon_options['associatesid_jp'],
			'amazon.co.uk'	=> $simple_amazon_options['associatesid_uk'],
			'amazon.com'	=> $simple_amazon_options['associatesid_us'],
			'javari.jp'		=> $simple_amazon_options['associatesid_jp']
		);

		$this->conf->domain = array (
			'amazon.ca'		=> 'ca',
			'amazon.cn'		=> 'cn',
			'amazon.de'		=> 'de',
			'amazon.es'		=> 'es',
			'amazon.fr'		=> 'fr',
			'amazon.it'		=> 'it',
			'amazon.co.jp'	=> 'jp',
			'amazon.co.uk'	=> 'co.uk',
			'amazon.com'	=> 'com',
			'javari.jp'		=> 'jp'
		);

		$this->conf->imgfile->small = SIMPLE_AMAZON_IMG_URL . '/amazon_noimg_small.png';
		$this->conf->imgfile->medium = SIMPLE_AMAZON_IMG_URL . '/amazon_noimg.png';
		$this->conf->imgfile->large = SIMPLE_AMAZON_IMG_URL . '/amazon_noimg_large.png';

		$this->amazonparse = new SimpleAmazonXmlParse();
		$this->generalfunclib = new CalcISBNLibrary();

	}

	/**
	 * @brief	Amazon 商品の HTML を生成
	 * @param	string $domain ( ドメイン: ca/de/fr/co.jp/co.uk/com )
	 * @param	string $asin ( ASIN )
	 * @param	string $name ( 商品名 )
	 * @return	string $output ( HTML )
	 */
	public function format_amazon( $domain, $asin, $name ) {

		$output = '';
		$tld = $this->conf->domain[$domain];

		$params = array(
//			'domain'		=> $this->conf->domain[$domain],
			'AssociateTag'	=> $this->conf->aid[$domain],
			'ItemId'		=> $asin,
			'ResponseGroup'	=> 'Images,ItemAttributes',
			'Operation'		=> 'ItemLookup',
			'MerchantId'	=> 'All',
			'Condition'		=> 'All'
		);

		// ISBN13をISBN10に変換 //
		if( strlen( $asin ) == 13 )
			$params['ItemId'] = $this->generalfunclib->calc_chkdgt_isbn10( substr( $asin, 3, 9 ) );

		// MarketplaceDomain(というかjavari.jp)を設定 //
		if( $domain == "javari.jp" )
			$params['MarketplaceDomain'] = 'www.javari.jp';

		$AmazonXml = $this->amazonparse->getamazonxml( $tld, $params );

//		DEBUG
//		$AmazonXml = false;
//		echo "<pre>\n";
//		print_r($AmazonXml);
//		echo "</pre>\n";

		// サーバーからリクエストはある?
		if( $AmazonXml === false ) {
			$output = $this->generate_non_res_html( $domain, $asin, $name );
			return $output;
		}

		// リクエストは有効?
		if( !$AmazonXml->Items || $AmazonXml->Items->Request->IsValid == 'False' ) {
			$output = '<!-- 与えられたリクエストが正しくありません -->';
			return $output;
		}

		// エラー発生してない？
		if( $AmazonXml->Items->Request->Errors ) {
			$error = $AmazonXml->Items->Request->Errors->Error;
			$output = '<!-- ' . "\n"
					. 'Occurrence of an Error' . "\n"
					. '  Code: ' . $error->Code . "\n"
					. '  Message: ' . $error->Message . "\n"
					. ' -->';
			return $output;
		}

		// リクエストを表示
		$output = $this->generate_html( $AmazonXml );
		return $output;

	}

	/**
	 * @brief	Amazon 商品の HTML を生成(レスポンスがない場合)
	 * @param	string $domain ( ドメイン: ca/cn/de/es/fr/it/co.jp/co.uk/com )
	 * @param	string $asin ( ASIN )
	 * @param	string $name ( 商品名 )
	 * @return	string $output ( HTML )
	 */
	function generate_non_res_html( $domain, $asin, $name ) {

		$tag = '';

		switch( $this->conf->windowtarget ) {
			case newwin: $windowtarget = ' target="_blank"'; break;
			case self: $windowtarget = '';
		}

		if( $this->conf->aid[$domain] )
			$tag = '?tag=' . $this->conf->aid[$domain];

		$amazonlink = 'http://www.' . $domain . '/dp/' . $asin . $tag;
		$amazonimg_url = 'http://images.amazon.com/images/P/' . $asin . '.09.THUMBZZZ.jpg';
		$amazonimg_size = '';

		if( !$name ) $name = "Amazon.co.jpの詳細ページへ &raquo;";

		if( function_exists('getimagesize') ) {
			$imgsize = getimagesize( $amazonimg_url );
//			if( $imgsize[0] == 1 && $imgsize[1] == 1 ) {
			if( $imgsize[0] == 1 ) {
				$amazonimg_url = $this->conf->imgfile->small;
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
			'<p class="sa-title"><a href="' . $amazonlink . '">' . urldecode($name) . '</a></p>' . "\n" .
			'</div>';

		return $output;

	}

	/**
	 * @brief	Amazon 商品の HTML を生成 ( レスポンスがある場合 )
	 * @param	object $AmazonXML ( レスポンス )
	 * @return	string $output ( HTML )
	 */
	function generate_html( $AmazonXml ) {

		$item = $AmazonXml->Items->Item;
		
		$attr = $item->ItemAttributes;
		$url = $item->DetailPageURL;
		$img = $this->set_img_vars( $item );

		switch( $this->conf->windowtarget ) {
			case newwin: $windowtarget = ' target="_blank"'; break;
			case self: $windowtarget = '';
		}

//		if( $this->conf->layout_type == 0 ) {
//			$rating = $item->CustomerReviews->AverageRating;
//			if ($rating) {
//				$rating = SIMPLE_AMAZON_IMG_URL . '/stars-' . str_replace('.', '', $rating) . '.gif';
//				$rating = '<img src="' . $rating . '" width="64" height="12" alt="" class="sa-rating" />';
//			}
//		}

		// テンプレート //
		//Title
		if( $this->conf->layout_type == 3 ) {
			$output = '<a href="'.$url.'"' . $windowtarget . '>' . $attr->Title . '</a>';
		}

		//Title & Image
		if( $this->conf->layout_type == 2 ) {
			$output = '<div class="simple-amazon-view">' . "\n";
			$output .= "\t" . '<p class="sa-img-box"><a href="'.$url.'"' . $windowtarget . '><img src="' . $img->url . '"' . $img->imgsize . ' alt="" class="sa-image" /></a></p>' . "\n";
			$output .= "\t" . '<p class="sa-title"><a href="'.$url.'"' . $windowtarget . '>' . $attr->Title . '</a></p>' . "\n";
			$output .= '</div>' . "\n";
		}

		//Detail
		if( $this->conf->layout_type == 1 ) {
			$output = '<div class="simple-amazon-view">' . "\n";
			$output .= "\t" . '<p class="sa-img-box"><a href="'.$url.'"' . $windowtarget . '><img src="' . $img->url . '"' . $img->size . ' alt="" class="sa-image" /></a></p>' . "\n";
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
					$output_list .= '<li>アーティスト：' . $attr->Artist . "</li>" . "\n";
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
		if( $this->conf->layout_type < 1 ) {
			$output = '<div class="simple-amazon-view">' . "\n";
			$output .= "\t" . '<p class="sa-img-box"><a href="' . $url . '"' . $windowtarget . '><img src="' . $img->url . '"' . $img->size . ' alt="" class="sa-image" /></a></p>' . "\n";
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
	 * @brief	画像のURLとsizeを設定する
	 * @param	object $xml
	 * @return	object $img
	 */
	function set_img_vars( $xml ) {

		$img->url = '';
		$img->size = '';

		if( $this->conf->layout_type != '3' ) {
			if($this->conf->imgsize == 'small') {
				$temp = $xml->SmallImage;
				if( !$item->URL ) {
					$img->url = $this->conf->imgfile->smallimgfile;
					$img->size = ' height="75" width="75"';
				}
			} elseif($this->conf->imgsize == 'large') {
				$temp = $xml->LargeImage;
				if( !$temp->URL ) {
					$img->url = $this->conf->imgfile->largeimgfile;
					$img->size = ' height="500" width="500"';
				}
			} else {
				$temp = $xml->MediumImage;
				if( !$temp->URL ) {
					$img->url = $this->conf->imgfile->medium;
					$img->size = ' height="160" width="160"';
				}
			}

			if( !$img->url ) {
				$img->url = $temp->URL;
				$img->size = ' height="' . $temp->Height . '" width="' . $temp->Width . '"';
			}
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

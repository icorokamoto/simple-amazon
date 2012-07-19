<?php

/******************************************************************************
 * 記事本文中に Amazon から取得した商品情報を表示するクラス
 *****************************************************************************/
class SimpleAmazonView {

	private $htmlGenerater;
	private $domain;

	/**
	 * @param	none
	 * @return	object $this;
	 */
	function __construct() {

		$this->htmlGenerater = new SimpleAmazonGenerateHtml();

		switch( WPLANG ) {
			case 'en_CA':	$this->domain = 'amazon.ca'; break;
			case 'zh_CN':	$this->domain = 'amazon.cn'; break;
			case 'de_DE':	$this->domain = 'amazon.de'; break;
			case 'es_ES':	$this->domain = 'amazon.es'; break;
			case 'fr_FR':	$this->domain = 'amazon.fr'; break;
			case 'it_IT':	$this->domain = 'amazon.it'; break;
			case 'ja':		$this->domain = 'amazon.co.jp'; break;
			case 'en_GB':	$this->domain = 'amazon.co.uk'; break;
			default:		$this->domain = 'amazon.com';
		}

	}

	/**
	 * 記事本文中のコードを個別商品表示 HTML に置き換える
	 * @param	string $content
	 * @return	string $content ( HTML )
	 */
	public function _replacestrings($content) { // 記事本文中の呼び出しコードを変換

		global $post;

//		$regexps[] = '/\[tmkm-amazon\](?P<asin>[A-Z0-9]{10,13})\[\/tmkm-amazon\]/';
		$regexps[] = '/<amazon>(?P<asin>[A-Z0-9]{10,13})<\/amazon>/';
//		$regexps[] = '/(^|<p>)http:\/\/www\.(?P<domain>amazon\.com|amazon\.ca|amazon\.co\.uk|amazon\.fr|amazon\.de|amazon\.co\.jp|javari\.jp)\/(?P<name>[\S]+)\/dp\/(?P<asin>[A-Z0-9]{10}).*?($|<\/p>)/m';
//		$regexps[] = '/(^|<p>)http:\/\/www\.(?P<domain>amazon\.com|amazon\.ca|amazon\.co\.uk|amazon\.fr|amazon\.de|amazon\.co\.jp|javari\.jp)\/gp\/product\/(?P<asin>[A-Z0-9]{10}).*?($|<\/p>)/m';
		$regexps[] = '/(^|<p>)http:\/\/www\.(?P<domain>.+)\/(?P<name>[\S]+)\/dp\/(?P<asin>[A-Z0-9]{10}).*?($|<\/p>)/m';
		$regexps[] = '/(^|<p>)http:\/\/www\.(?P<domain>.+)\/gp\/product\/(?P<asin>[A-Z0-9]{10}).*?($|<\/p>)/m';

		foreach( $regexps as $regexp ) {
			if( preg_match_all($regexp, $content, $arr) ) {
				for ($i=0; $i<count($arr[0]); $i++) {
					$domain = ( isset( $arr['domain'][$i] ) ) ? $arr['domain'][$i] : $this->domain;
					$display = $this->htmlGenerater->format_amazon( $domain, $arr['asin'][$i], $arr['name'][$i] );
					// ASINコードの置換
					$content = str_replace($arr[0][$i], $display, $content);
				}
			}
		}

		/* for WYSWYG Editer */
//		$content = str_replace('<p><div class="simple-amazon-view">', '<div class="simple-amazon-view">', $content);
//		$content = str_replace('<hr class="simple-amazon-clear" /></div></p>', '<hr class="simple-amazon-clear" /></div>', $content);

		return $content;

	}

	/**
	 * PHP 関数として Amazon の個別商品 HTML を呼び出す
	 * @param	string $asin
	 * @return	none
	 */
	public function simple_amazon_view( $asin, $domain_str = null ) { // PHP テ−マファイル中に記述する関数

		$name = '';

		switch( trim($domain_str) ) {
			case 'ca':			$domain = 'amazon.ca'; break;
			case 'cn':			$domain = 'amazon.cn'; break;
			case 'de':			$domain = 'amazon.de'; break;
			case 'es':			$domain = 'amazon.es'; break;
			case 'fr':			$domain = 'amazon.fr'; break;
			case 'it':			$domain = 'amazon.it'; break;
			case 'ja':			$domain = 'amazon.co.jp'; break;
			case 'uk':			$domain = 'amazon.co.uk'; break;
			case 'com':			$domain = 'amazon.com'; break;
			case 'javari.jp':	$domain = 'javari.jp'; break;
			default:			$domain = $this->domain;
		}

		$display = $this->htmlGenerater->format_amazon( $domain, $asin, $name );
		echo $display;
	}

}

?>

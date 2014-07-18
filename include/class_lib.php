<?php
/******************************************************************************
 * その他もろもろクラス
 *****************************************************************************/
class SimpleAmazonLib {

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
			'small'     => SIMPLE_AMAZON_IMG_URL . '/amazon_noimg_small.png',
			'medium'    => SIMPLE_AMAZON_IMG_URL . '/amazon_noimg.png',
			'large'     => SIMPLE_AMAZON_IMG_URL . '/amazon_noimg_large.png'
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

	/**
	 * 国コードからドメインを取得する
	 * 国コードがnullの場合は言語設定からdomainを設定する
	 * @param String $code
	 * @return String $domain
	 */
	public function get_domain( $code = null ) {
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
			default:          $code = null;
		}

		if($code == null) {
			switch(WPLANG) {
				case 'en_CA': $domain = 'amazon.ca'; break;
				case 'zh_CN': $domain = 'amazon.cn'; break;
				case 'de_DE': $domain = 'amazon.de'; break;
				case 'es_ES': $domain = 'amazon.es'; break;
				case 'fr_FR': $domain = 'amazon.fr'; break;
				case 'it_IT': $domain = 'amazon.it'; break;
				case 'ja':    $domain = 'amazon.co.jp'; break;
				case 'en_GB': $domain = 'amazon.co.uk'; break;
				default:      $domain = 'amazon.com';
			}
		}
		
		return $domain;
	}
	
	/**
	 * ドメインからTLDを取得する
	 * @param String $domain
	 * @return String $tld
	 */
	public function get_TLD( $domain ) {
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
	 * @param Array $ids
	 * @return String $aid
	 */
	public function get_aid($tld, $array_id) {
		
		if($tld == 'com')
			$tld = 'us';

		$associatesid_key = 'associatesid_' . $tld;

		$aid = $array_id[$associatesid_key];

		return $aid;
	}

	/**
	 * ISBN10をISBN13に変換する
	 * @param String $val
	 * @return String $val.$chkdgt
	 */
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

	/**
	 * ISBN13をISBN10に変換する
	 * @param String $val
	 * @return String $val.$chkdgt
	 */
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

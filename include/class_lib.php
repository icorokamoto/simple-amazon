<?php
/******************************************************************************
 * その他もろもろクラス
 *****************************************************************************/
class SimpleAmazonLib {

	public $lang_domain_list = array(
		'en_CA' => 'amazon.ca',
		'zh_CN' => 'amazon.cn',
		'de_DE' => 'amazon.de',
		'es_ES' => 'amazon.es',
		'fr_FR' => 'amazon.fr',
		'it_IT' => 'amazon.it',
		'ja'    => 'amazon.co.jp',
		'en_GB' => 'amazon.co.uk',
		'en_US' => 'amazon.com'
	);

	public $code_domain_list = array(
		'ca'  => 'amazon.ca',
		'cn'  => 'amazon.cn',
		'de'  => 'amazon.de',
		'es'  => 'amazon.es',
		'fr'  => 'amazon.fr',
		'it'  => 'amazon.it',
		'jp'  => 'amazon.co.jp',
		'uk'  => 'amazon.co.uk',
		'com' => 'amazon.com'
	);

	/**
	 * オプションの必須項目が入力されているかチェックする
	 * @param Array $options
	 * @return Boolean $result
	 */
	public function check_options( $options ) {

		$result = false;

		$check_associatesid = 
			$options['associatesid_ca'] .
			$options['associatesid_cn'] .
			$options['associatesid_de'] .
			$options['associatesid_es'] .
			$options['associatesid_fr'] .
			$options['associatesid_it'] .
			$options['associatesid_jp'] .
			$options['associatesid_uk'] .
			$options['associatesid_us'];

		if( $options['accesskeyid'] && $options['secretaccesskey'] && $check_associatesid ) {
			$result = true;
		}

		return $result;

	}

	/**
	 * 国コードからドメインを取得する
	 * 国コードがnullの場合は言語設定からdomainを設定する
	 * @param String $code
	 * @return String $domain
	 */
	public function get_domain( $code = null ) {

		$domain = 'amazon.com';

		if( $code && array_key_exists( $code, $this->code_domain_list ) ) {
			$domain = $this->code_domain_list[$code];
		} else {
			$code = null;
		}

		if( !$code ) {
			$wplang = get_locale();
			if( array_key_exists( $wplang, $this->lang_domain_list ) ) {
				$domain = $this->lang_domain_list[$wplang];
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
		$domain_code_list = array_flip( $this->code_domain_list );
		if( array_key_exists( $domain, $domain_code_list ) ) {
			$tld = $domain_code_list[$domain];
		}
		return $tld;
	}
	
	/**
	 * ドメインからアソシエイトIDを取得する
	 * @param String $domain
	 * @param Array $array_id
	 * @return String $aid
	 */
	public function get_aid( $domain, $array_id ) {
		$domain_code_list = array_flip( $this->code_domain_list );
		$code = $domain_code_list[$domain];
		if( $code == 'com' ) $code = 'us';
		$aid = $array_id['associatesid_' . $code];
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

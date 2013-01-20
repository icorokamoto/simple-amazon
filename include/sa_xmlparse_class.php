<?php

/******************************************************************************
 * 取得した xml をパースするクラス
 *****************************************************************************/
class SimpleAmazonXmlParse {

	private $options;
	private $cache;
	private $access_key;
	private $private_key;

	/**
	 * @param	none
	 * @return	object $this
	 */
	function __construct() {

		global $simple_amazon_options;

		$this->options = $simple_amazon_options;
		$this->cache   = new SimpleAmazonCacheControl();
		$this->cp_path = 'checkpoint.php';

	}

	/**
	 * Amazonのレスポンスを返す
	 * @param	array $arr
	 * @rerturn	object $parsedata
	 */
	public function getamazonxml( $tld, $params ) {

		if( !$this->options['accesskeyid'] || !$this->options['secretaccesskey'] ) {
			return false;
		}

		// --- Set an ID for this cache ---
		$id = $tld . $params['ItemId'];

		// Check to see if there is a valid cache of xml
		$xmldata = $this->cache->get( $id );

		if ($xmldata) {

		// there is a cache, so parse cached xml
//			echo "<!-- read cache -->";

		} else {

		// there is no cache, so generate feed
//			echo "<!-- read feed -->";

			if( ! function_exists('checkpoint') )
				include_once( $this->cp_path );

			// Feed URI を生成
			$feed_uri = $this->generate_feed_uri( $tld, $params );

			//ロックファイルの設定
			$lockfile = $this->cache->get_cache_dir() . 'lockfile';

			if ( checkpoint( $lockfile, 1 ) ) {
				$xmldata = @file_get_contents($feed_uri);
				if($xmldata) {
					$this->cache->save( $xmldata, $id );
				}
			}
		}

// レスポンスヘッダを出力
//		echo $http_response_header;

		//xmlをパース
		$parsedata = simplexml_load_string( $xmldata );

		return $parsedata;

	}

	/**
	 * Amazonにリクエストするためのfeedを生成する
	 * @param	string $tld
	 * @param	array $params
	 * @rerturn	string $feed_uri
	 */
	private function generate_feed_uri($tld, $params) {

		$method = "GET";
		$host = "ecs.amazonaws." . $tld;
		$uri = "/onca/xml";

		$params = array_merge(
			array(
				'Service'        => 'AWSECommerceService',
				'AWSAccessKeyId' => $this->options['accesskeyid'],
				'Timestamp'      => gmdate("Y-m-d\TH:i:s\Z"),
				'Version'        => '2011-08-01',
				'ContentType'    => 'text/xml'
//				,
//				'Operation'      => 'ItemLookup',
//				'MerchantId'     => 'All',
//				'Condition'      => 'All'
			), $params );

		// sort the parameters
		ksort($params);
/*
echo '<!--';
var_dump($params);
echo '-->';
*/
		// create the canonicalized query
		$canonicalized_query_array = array();
		foreach ($params as $param=>$value) {
			$param = str_replace("%7E", "~", rawurlencode($param));
			$value = str_replace("%7E", "~", rawurlencode($value));
			$canonicalized_query_array[] = $param."=".$value;
		}
		$canonicalized_query = implode("&", $canonicalized_query_array);

		// create the string to sign
		$string_to_sign = $method."\n".$host."\n".$uri."\n".$canonicalized_query;

		// calculate HMAC with SHA256 and base64-encoding
		$signature = base64_encode(hash_hmac("sha256", $string_to_sign, $this->options['secretaccesskey'], true));

		// encode the signature for the request
		$signature = str_replace("%7E", "~", rawurlencode($signature));

		// create request
		$feed_uri = "http://".$host.$uri."?".$canonicalized_query."&Signature=".$signature;

		return $feed_uri;
	}

}

?>

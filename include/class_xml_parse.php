<?php

/******************************************************************************
 * 取得した xml をパースするクラス
 *****************************************************************************/
class SimpleAmazonXmlParse {

	private $options;
	private $cp_path;
	private $tld;

	/**
	 * Construct
	 * @param none
	 * @return none
	 */
	function __construct() {

		global $simple_amazon_options;

		$this->options = $simple_amazon_options;
		$this->cp_path = 'checkpoint.php';

	}

	/**
	 * Amazonのレスポンスを返す
	 * @param String $tld
	 * @param Array $params
	 * @rerturn Object $AmazonXml
	 */
	public function getamazonxml( $tld, $params ) {

		if( !$this->options['accesskeyid'] || !$this->options['secretaccesskey'] ) {
			return false;
		}
		
		$this->tld = $tld;

		// --- Set an ID for this cache ---
//		$id = $tld . $params['Operation'] . $params['ResponseGroup'];
//		$id = $tld . $params['ItemId'];

//		if( $params['Operation'] == 'ItemLookup' ) {
//			$id .= $params['ItemId'];
//		} elseif( $params['Operation'] == 'ItemSearch' ) {
//			$id .= $params['SearchIndex'] . $params['BrowseNode'];
//			$id = implode('', $params);
//			echo '<!-- ' . $id . '-->';
//		}
//		$id = md5($id);

		$id = implode('', $params);
		$cache = new SimpleAmazonCacheControl();

		// Check to see if there is a valid cache of xml
		$xmldata = $cache->get( $id );

		if ($xmldata) {

		// there is a cache, so parse cached xml
//			echo "<!-- read cache -->";

		} else {

		// there is no cache, so generate feed
//			echo "<!-- read feed -->";

			if( ! function_exists('checkpoint') )
				include_once( $this->cp_path );

			// Feed URI を生成
			$feed_uri = $this->generate_feed_uri($params);

			//ロックファイルの設定
			$lockfile = $cache->get_cache_dir() . 'lockfile';

			if ( checkpoint( $lockfile, 1 ) ) {
				$xmldata = @file_get_contents($feed_uri);
				if($xmldata) {
					$cache->save( $xmldata, $id );
//					$status = $this->cache->save( $xmldata, $id );
//					var_dump($status);
				}
			}
		}

// レスポンスヘッダを出力
//		echo $http_response_header;

		// サーバーからレスポンスはある?
		if( $xmldata === false ) {
//			$error_message = '<!-- レスポンスがありません。 -->' . "\n";
			$error_message = '<!-- ' . $http_response_header . ' -->';
			return $error_message;
		}

		// xmlをパース
		$AmazonXml = simplexml_load_string( $xmldata );

		// リクエストは有効?
		if( !$AmazonXml->Items || $AmazonXml->Items->Request->IsValid == 'False' ) {
			$error_message = '<!-- 与えられたリクエストが正しくありません -->';
			return $error_message;
		}

		// エラー発生してない？
		if( $AmazonXml->Items->Request->Errors ) {
			$error = $AmazonXml->Items->Request->Errors->Error;
			$error_message = '<!-- ' . "\n"
					. 'Occurrence of an Error' . "\n"
					. '  Code: ' . $error->Code . "\n"
					. '  Message: ' . $error->Message . "\n"
					. ' -->';
			return $error_message;
		}

		return $AmazonXml;
	}

	/**
	 * Amazonにリクエストするためのfeedを生成する
	 * @param array $params
	 * @rerturn string $feed_uri
	 */
	private function generate_feed_uri($params) {

		$method = "GET";
		$host = "ecs.amazonaws." . $this->tld;
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

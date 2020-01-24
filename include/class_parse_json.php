<?php

/******************************************************************************
 * 取得した json をパースするクラス
 *****************************************************************************/
class SimpleAmazonParseJSON {

	private $opt;
	private $cp_path;

	/**
	 * Construct
	 * @param none
	 * @return none
	 */
	function __construct() {
		$this->opt     = new SimpleAmazonOptionsControl();
		$this->cp_path = 'checkpoint.php';
	}

	/**
	 * Amazonのレスポンスを返す
	 * @param String $domain
	 * @param String $path
	 * @param Array $payload
	 * @return Object $AmazonXml
	 */
	public function getamazonjson( $domain, $path, $payload ) {

		//必須項目が入力されているかチェック
		$flag = $this->opt->isset_required_options();
		if( ! $flag ) {
			return false;
		}

		// --- Set an ID for this cache ---
		$id = md5( $domain . $path . $payload );

		$cache = new SimpleAmazonCacheControl();

		// Check to see if there is a valid cache of xml
		$jsondata = $cache->get( $id );

		if ( $jsondata ) {

		// there is a cache, so parse cached xml
//			echo "<!-- read cache -->";

		} else {

		// there is no cache, so generate feed
//			echo "<!-- read feed -->";

			if( ! function_exists('checkpoint') )
				include_once( $this->cp_path );

			//ロックファイルの設定
			$lockfile = $cache->get_cache_dir() . 'lockfile';

			if ( checkpoint( $lockfile, 1 ) ) {

				$region = $this->get_region( $domain );
				$host = "webservices." . $domain;
				$option_accesskeyid = $this->opt->get_option( 'accesskeyid' );
				$option_secretaccesskey = $this->opt->get_option( 'secretaccesskey' );

				if( $path == '/paapi5/getitems' ) {
					$target = 'com.amazon.paapi5.v1.ProductAdvertisingAPIv1.GetItems';
				} else {
					$target = 'com.amazon.paapi5.v1.ProductAdvertisingAPIv1.SearchItems';
				}

				$awsv4 = new AwsV4( $option_accesskeyid, $option_secretaccesskey );
				$awsv4->setRegionName( $region );
				$awsv4->setServiceName( "ProductAdvertisingAPI" );
				$awsv4->setPath ( $path );
				$awsv4->setPayload ( $payload );
				$awsv4->setRequestMethod ( "POST" );
				$awsv4->addHeader ( 'content-encoding', 'amz-1.0' );
				$awsv4->addHeader ( 'content-type', 'application/json; charset=utf-8' );
				$awsv4->addHeader ( 'host', $host );
				$awsv4->addHeader ( 'x-amz-target', $target );

				$headers = $awsv4->getHeaders();

				$headerString = "";
				foreach ( $headers as $key => $value ) {
					$headerString .= $key . ': ' . $value . "\r\n";
				}

				$params = array (
					'http' => array (
						'header' => $headerString,
						'method' => 'POST',
						'content' => $payload
					)
				);

//var_dump( $payload );

				$stream = stream_context_create ( $params );

				$fp = @fopen( 'https://' . $host . $path, 'rb', false, $stream );

				if ( ! $fp ) {
					$error_message = 'ファイルを取得できませんでした' . "\n";
//					throw new Exception ( "Exception Occured" );
					return $error_message;
				}

				$jsondata = @stream_get_contents ( $fp );

				if ($jsondata === false) {
					$error_message = 'データがありません' . "\n";
//					throw new Exception ( "Exception Occured" );
					return $error_message;
				}

				$cache->save( $jsondata, $id );
			}
		}

		// jsonをデコード
		$json = json_decode( $jsondata );

//var_dump($json);

		// エラー発生してない？
		if( property_exists( $json, 'Errors' ) ) {
			$error_message = ' Occurrence of an Error' . "\n"
				. '  Code: ' . $json->{'Errors'}[0]->{'Code'} . "\n"
				. '  Message: ' . $json->{'Errors'}[0]->{'Message'};
			return $error_message;
		}

		// 商品はある？
		if( property_exists( $json->{'ItemsResult'}, 'Items' ) ) {
			$json_item = $json->{'ItemsResult'}->{'Items'}[0];
		} elseif ( property_exists( $json->{'SearchResult'}, 'Items' ) ) {
			$json_item = $json->{'SearchResult'}->{'Items'}[0];
		} else {
			$error_message = '該当する商品がありませんでした' . "\n";
			return $error_message;
		}

		return $json_item;
	}

	/**
	 * ドメインからリージョンを取得する
	 * @param String $domain
	 * @return String $region
	 */
	private function get_region( $domain ) {
		$domain_region_list = array(
			'amazon.com.au' => 'us-west-2',
			'amazon.com.br' => 'us-west-1',
			'amazon.in'     => 'eu-west-1',
			'amazon.com.mx' => 'us-east-1',
			'amazon.sg'     => 'us-west-2',
			'amazon.com.tr' => 'eu-west-1',
			'amazon.ae'     => 'eu-west-1',
			'amazon.ca'     => 'us-west-1',
			'amazon.es'     => 'eu-west-1',
			'amazon.de'     => 'eu-west-1',
			'amazon.fr'     => 'eu-west-1',
			'amazon.it'     => 'eu-west-1',
			'amazon.co.jp'  => 'us-west-2',
			'amazon.co.uk'  => 'eu-west-1',
			'amazon.com'    => 'us-east-1'
		);

		if( !array_key_exists( $domain, $domain_region_list ) ) {
			$domain = 'amazon.com';
		}
		$region = $domain_region_list[$domain];

		return $region;

	}

}

?>

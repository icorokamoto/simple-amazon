<?php
namespace Icoro\SimpleAmazon;

// stauss source
use Icoro\SimpleAmazon\Vendor\Amazon\CreatorsAPI\v1\Configuration;
use Icoro\SimpleAmazon\Vendor\Amazon\CreatorsAPI\v1\com\amazon\creators\api\DefaultApi;
use Icoro\SimpleAmazon\Vendor\Amazon\CreatorsAPI\v1\com\amazon\creators\model\SearchItemsRequestContent;
use Icoro\SimpleAmazon\Vendor\Amazon\CreatorsAPI\v1\com\amazon\creators\model\SearchItemsResource;
use Icoro\SimpleAmazon\Vendor\Amazon\CreatorsAPI\v1\com\amazon\creators\model\GetItemsRequestContent;
use Icoro\SimpleAmazon\Vendor\Amazon\CreatorsAPI\v1\com\amazon\creators\model\GetItemsResource;
use Icoro\SimpleAmazon\Vendor\Amazon\CreatorsAPI\v1\ApiException;

// original source
// use Amazon\CreatorsAPI\v1\Configuration;
// use Amazon\CreatorsAPI\v1\com\amazon\creators\api\DefaultApi;
// use Amazon\CreatorsAPI\v1\com\amazon\creators\model\SearchItemsRequestContent;
// use Amazon\CreatorsAPI\v1\com\amazon\creators\model\SearchItemsResource;
// use Amazon\CreatorsAPI\v1\com\amazon\creators\model\GetItemsRequestContent;
// use Amazon\CreatorsAPI\v1\com\amazon\creators\model\GetItemsResource;
// use Amazon\CreatorsAPI\v1\ApiException;

use Exception;
use stdClass;

class Request {

  private Options $options;
  private string $request_type;
  private string $request_keyword;

	/**
	 * Construct
	 */
	public function __construct() {
		$this->options = new Options();
	}

  /**
   * リクエストタイプを設定
   * @param string $request_type 'get', 'search' 
   */
  public function set_request_type( $request_type ) {
    $this->request_type = $request_type;
  }

  /**
   * 検索キーワードを設定
   * @param string $request_keyword
   */
  public function set_keyword( $request_keyword ) {
    $this->request_keyword = $request_keyword;
  }

  /**
   * リクエストを送信
   */
  public function request() {


    // --- Set an ID for this cache ---
		$cache_id = 'simpleamazon_' . $this->request_type . '_' . md5( $this->request_keyword );

    // キャッシュを取得
    $json = get_transient( $cache_id );

    // キャッシュがなかった場合
    // リクエストを飛ばす
    if( $json === false ) {

      // データを取得
      try {

        $json = $this->get_requsest();

        // レスポンスをチェック
		    $error_message = $this->check_response( $json );

        if( $error_message ) {
          throw new Exception( message: $error_message );
        }

      } catch( Exception $e ) {
        $error_message = $e->getMessage();
        if( $e->getPrevious() ) {
          $error_message .= PHP_EOL . $e->getPrevious()->getMessage();
        }
        throw new Exception(
          message: 'Error: ' . $error_message
          // previous: $e
        );
      }

      //キャッシュを保存
      set_transient( $cache_id, $json, 24 * HOUR_IN_SECONDS );
    }

    return $json;
  }

  /**
   * リクエストを取得
   * @return object|false $request
   */
  private function get_requsest() {

  	// Initialize configuration with credential details
    $credential_id      = $this->options->get_option('credential_id');
		$credential_secret  = $this->options->get_option('credential_secret');
		$credential_version = $this->options->get_option('credential_version');
		$partner_tag        = $this->options->get_option('partner_tag');

    $config = new Configuration();
    $config->setCredentialId( $credential_id );
    $config->setCredentialSecret( $credential_secret );
    $config->setVersion( $credential_version );

    // Initialize API
    $api = new DefaultApi( null, $config );

    /**
     * Add marketplace. For more details, refer: https://affiliate-program.amazon.com/creatorsapi/docs/en-us/api-reference/common-request-headers-and-parameters#marketplace-locale-reference
     */
    $marketplace = $this->options->get_option( 'marketplace' );

		/**
     * Choose resources you want from SearchItemsResource enum
     * For more details, refer: https://affiliate-program.amazon.com/creatorsapi/docs/en-us/api-reference/operations/search-items#resources-parameter
     */
		$resources = [
			"images.primary.small",
			"images.primary.medium",
			"images.primary.large",
			"itemInfo.title",
			"itemInfo.byLineInfo",
			"itemInfo.contentInfo",
			"itemInfo.classifications",
			"itemInfo.productInfo",
			"itemInfo.title",
			"itemInfo.tradeInInfo"
		];
    
		$itemsRequest = null;

    if( $this->request_type == 'get' ) {
  		// Create GetItems request
	    $itemsRequest = new GetItemsRequestContent();
    	$itemsRequest->setItemIds( [$this->request_keyword] );      
    } else {
	    // Create SearchItems request
    	$itemsRequest = new SearchItemsRequestContent();
    	$itemsRequest->setKeywords( $this->request_keyword );
	    $itemsRequest->setItemCount(1);
    }

    $itemsRequest->setPartnerTag( $partner_tag );
    $itemsRequest->setResources( $resources );

		$error_message = '';

    $max_retries = 3;
    $retry_count = 0;

    while( $retry_count < $max_retries ) {
      try {
        $response = $this->request_type == 'get' 
          ? $api->getItems( $marketplace, $itemsRequest )     // Call the GetItems API
          : $api->searchItems( $marketplace, $itemsRequest ); // Call the SearchItems API
		    return json_decode( $response );
      } catch ( ApiException $e ) {
        if( $e->getCode() === 429 ) {
          $retry_count++;
          sleep( pow( 2, $retry_count ) );
          continue;
        }
        throw new Exception( "API Error: " . $e->getMessage(), 0, $e );
      } catch ( Exception $e ) {
        throw new Exception( "Unexpected System Error", 0, $e );
      }
    }
    throw new Exception( "Maximum retries reached due to API rate limit." );
  }

	/**
	 * レスポンスにエラーがないかチェックする
	 * @param object $json
	 * @return object|string $json|$error_message
	 */
	private function check_response( $json ) {

		$error_message = '';

		// エラー発生してない？
		if( property_exists( $json, 'errors' ) ) {
			$error_message = ' Occurrence of an Error' . "\n"
				. '  Code: ' . $json->{'errors'}[0]->{'code'} . "\n"
				. '  Message: ' . $json->{'errors'}[0]->{'message'};
			// return $error_message;
		}

		// レスポンスの振り分け
    $json_items = new stdClass;
		if( property_exists( $json, 'itemsResult' ) ) {
			$json_items = $json->{'itemsResult'};
		} elseif( property_exists( $json, 'searchResult' ) ) {
			$json_items = $json->{'searchResult'};
		}

		// 商品はある？
		if( !property_exists( $json_items, 'items' ) ) {
		// 	$json = $json->{'items'}[0];
		// } else {
			$error_message = '該当する商品がありませんでした' . "\n";
		}

			return $error_message;

	}
    
}
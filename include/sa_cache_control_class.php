<?php

/******************************************************************************
 * キャッシュを操作するクラス
 *****************************************************************************/
class SimpleAmazonCacheControl {

	private $config;
	private $objCache;

	/**
	 * @param	none
	 * @return	object $this
	  */
	public function __construct() {

		global $simple_amazon_settings;

		$this->config = $simple_amazon_settings;

		if( !class_exists('Icoro_Cache_Lite') ) {
			include_once( $this->config['litephp_path'] );
		}

		$options = array(
				'cacheDir' => $this->config['cache_dir'],
				'lifeTime' => $this->config['cache_time']
			);

		$this->objCache = new Icoro_Cache_Lite( $options );

	}

	/**
	 * @brief	キャッシュディレクトリが書き込み可能かチェックする
	 * @param	nane
	 * @reutrn	string
	 */
	public function is_error() {

		$error = null;

		if( file_exists($this->config['cache_dir']) ) {
			if( !is_writable($this->config['cache_dir']) ) {
				$error = '<div class="error"><p>以下のキャッシュディレクトリのパーミッションを <strong>777</strong> (または <strong>707</strong> ) にしてください。</p>' . "\n" . '<p><code>' . $this->config['cache_dir'] . '</code></p></div>' . "\n";
			}
		} else {
			$error = '<div class="error"><p>以下のキャッシュディレクトリを作成し、パーミッションを <strong>777</strong> (または <strong>707</strong> ) にしてください。</p>' . "\n" . '<p><code>' . $this->config['cache_dir'] . '</code></p></div>' . "\n";
		}

		return $error;

	}

	/**
	 * @brief	キャッシュを保存する
	 * @param	string $feed
	 * @param	string $id
	 * @return	object $data
	 */
	public function save( $data, $id ) {
		$status = $this->objCache->save( $data, $id );
		return $status;
	}

	/**
	 * @brief	キャッシュを取得する
	 * @param	string $id
	 * @return	object $cache
	 */
	public function get( $id ) {
		$cache = $this->objCache->get( $id );
		return $cache;
	}

	/**
	 * @brief	キャッシュを削除する
	 * @param	none
	 * @return	none
	 */
	public function clear() {
		$this->objCache->clean();
	}

	/**
	 * @brief	有効期限切れのキャッシュを削除する
	 * @param	none
	 * @return	none
	 */
	public function clean() {
		$this->objCache->clean(false, 'old');
	}

}
?>

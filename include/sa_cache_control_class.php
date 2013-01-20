<?php

/******************************************************************************
 * キャッシュを操作するクラス
 *****************************************************************************/
class SimpleAmazonCacheControl {

	private $objCache;

	/**
	 * @param	none
	 * @return	object $this
	  */
	public function __construct() {

		$this->cache_dir = SIMPLE_AMAZON_PLUGIN_DIR . '/cache/'; // cacheディレクトリのpath

		$litephp_path = SIMPLE_AMAZON_PLUGIN_DIR . '/include/Lite.php'; // Lite.phpのpath
		$cache_time   = 60*60*24;                                       // cacheの有効時間(秒単位)

		if( !class_exists('Icoro_Cache_Lite') ) {
			include_once($litephp_path);
		}

		$options = array(
			'cacheDir' => $this->cache_dir,
			'lifeTime' => $cache_time
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

		if( file_exists($this->cache_dir) ) {
			if( !is_writable($this->cache_dir) ) {
				$error = '<div class="error"><p>以下のキャッシュディレクトリのパーミッションを <strong>777</strong> (または <strong>707</strong> ) にしてください。</p>' . "\n" . '<p><code>' . $this->cache_dir . '</code></p></div>' . "\n";
			}
		} else {
			$error = '<div class="error"><p>以下のキャッシュディレクトリを作成し、パーミッションを <strong>777</strong> (または <strong>707</strong> ) にしてください。</p>' . "\n" . '<p><code>' . $this->cache_dir . '</code></p></div>' . "\n";
		}

		return $error;

	}

	/**
	 * キャッシュディレクトリを取得する
	 * @param none
	 * @return string $dir
	 */
	public function get_cache_dir() {
		$dir = $this->cache_dir;
		return $dir;
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

<?php
/**
 * PHP file to use when rendering the block type on the server to show on the front end.
 *
 * The following variables are exposed to the file:
 *     $attributes (array): The block attributes.
 *     $content (string): The block default content.
 *     $block (WP_Block): The block instance.
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */

namespace Icoro\SimpleAmazon;

// 定数が定義されているか確認（念のための安全策）
if ( ! defined( 'SIMPLE_AMAZON_PLUGIN_DIR' ) ) {
    return;
}

// オートローダーの読み込み
// メインファイルで読み込み済みなら、ここはクラスの存在チェックだけでもOK
if ( ! class_exists( Core::class ) ) {
	// strauss のオートローダ
  $autoload_path = SIMPLE_AMAZON_PLUGIN_DIR . 'includes/vendor/autoload.php';
	// composer のオートローダ
  // $autoload_path = SIMPLE_AMAZON_PLUGIN_DIR . 'vendor/autoload.php';
  if ( file_exists( $autoload_path ) ) {
      require_once $autoload_path;
  }
}

/**
 * @var array    $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 */

// 属性の取得
$search_type = $attributes['searchType'] ?? 'keyword';
$keyword     = $attributes['keyword'] ?? '';
$asin        = $attributes['asin'] ?? '';

// 表示する値の決定
$target_value = ( 'asin' === $search_type ) ? $asin : $keyword;
if ( empty( $target_value ) ) {
  return;
}

$atts = array(
	'asin'    => $asin,
	'word'    => $keyword
);

// リンクの生成
// ショートコードのメソッドを流用
$sa = new Core();
$link_html = $sa->sa_shortcode( $atts );

echo $link_html;
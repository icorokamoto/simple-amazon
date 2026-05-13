<?php
/*
Plugin Name: Simple Amazon
Plugin URI: http://www.icoro.com/
Description: ASIN を指定して Amazon から個別商品の情報を取出します。BOOKS, DVD, CD は詳細情報を取り出せます。
Author: icoro
Version: 9.0
Author URI: http://www.icoro.com/
Special Thanks: tomokame (http://tomokame.moo.jp/)
Special Thanks: websitepublisher.net (http://www.websitepublisher.net/article/aws-php/)
Special Thanks: hiromasa.zone :o) (http://hiromasa.zone.ne.jp/)
Special Thanks: PEAR :: Package :: Cache_Lite (http://pear.php.net/package/Cache_Lite)
Special Thanks: Amazon® AWS HMAC signed request using PHP (http://mierendo.com/software/aws_signed_query/)
Special Thanks: PHP による Amazon PAAPI の毎秒ルール制限の実装とキャッシュの構築例 (http://sakuratan.biz/archives/1395)
*/

namespace Icoro\SimpleAmazon;

if( $_SERVER['SCRIPT_FILENAME'] == __FILE__ ) die();

// strauss のオートローダを読み込み
if ( file_exists( __DIR__ . '/includes/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/includes/vendor/autoload.php';
}

// Composerのオートローダーを読み込み
// if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
//     require_once __DIR__ . '/vendor/autoload.php';
// }


/******************************************************************************
 * 定数の設定 (主にディレクトリのパスとか)
 *****************************************************************************/
define( 'SIMPLE_AMAZON_VER', '9.0' );
define( 'SIMPLE_AMAZON_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SIMPLE_AMAZON_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SIMPLE_AMAZON_IMG_URL',    SIMPLE_AMAZON_PLUGIN_URL . 'assets/images/' );


/******************************************************************************
 * Simple Amazon クラスの設定
 *****************************************************************************/
$simpleAmazon = new Core();

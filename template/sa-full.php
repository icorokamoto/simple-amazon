<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// 商品詳細のリストを作成する
$detail = "";
$detail_list = "";

// $itemInfo        = $item->{'itemInfo'};
// $byLineInfo      = $itemInfo->{'byLineInfo'};
// $classifications = $itemInfo->{'classifications'};
// $contentInfo     = $itemInfo->{'contentInfo'};
// $productInfo     = $itemInfo->{'productInfo'};
// $tradeInInfo     = $itemInfo->{'tradeInInfo'};

// $productGroup    = $classifications->{'productGroup'}->{'displayValue'};
// $price           = $tradeInInfo->{'price'}->{'displayAmount'};

$contributors    = isset( $byLineInfo->{'Contributors'} ) ? $byLineInfo->{'Contributors'} : null;
$manufacturer    = isset( $byLineInfo->{'manufacturer'}->{'displayValue'} ) ? esc_html( $byLineInfo->{'manufacturer'}->{'displayValue'} ) : null;
$publicationDate = isset( $contentInfo->{'publicationDate'}->{'displayValue'} ) ? date( "Y/m/d", strtotime( $contentInfo->{'publicationDate'}->{'displayValue'} ) ) : null;
$pagesCount      = isset( $contentInfo->{'pagesCount'}->{'displayValue'} ) ? esc_html( $contentInfo->{'pagesCount'}->{'displayValue'} ) : null;
$binding         = isset( $classifications->{'binding'}->{'displayValue'} ) ? esc_html( $classifications->{'binding'}->{'displayValue'} ) : null;
$releaseDate     = isset( $productInfo->{'releaseDate'}->{'displayValue'} ) ? date( "Y/m/d", strtotime( $productInfo->{'releaseDate'}->{'displayValue'} ) ) : null;

//商品のカテゴリ別に取得する情報を変える
switch( strtolower( $productGroup ) ) {
	case "book":
		if( count( $contributors ) ) {
			$author_list = '';
			foreach( $contributors as $contributor ) {
				if( $contributor->{'RoleType'} == 'author' ) {
					$author_list .= esc_html( $contributor->{'Name'} . ' ' );
				}
			}
			if( $author_list != '' ) {
				$detail_list .= "\t" ."<li>著者：" . trim( $author_list ) . "</li>" . PHP_EOL;
			}
		}
		$detail_list .= "\t" . "<li>出版社：" . $manufacturer . " ( " . $publicationDate . " )</li>" . PHP_EOL;
		$detail_list .= "\t" . "<li>" . $binding . "：" . $pagesCount . " ページ</li>" . PHP_EOL;
		break;
	case "ebooks":
		if( count( $contributors ) ) {
			$author_list = '';
			foreach( $contributors as $contributor ) {
				if( $contributor->{'RoleType'} == 'author' ) {
					$author_list .= esc_html( $contributor->{'Name'} . ' ' );
				}
			}
			if( $author_list != '' ) {
				$detail_list .= "\t" ."<li>著者：" . trim( $author_list ) . "</li>" . PHP_EOL;
			}
		}
		$detail_list .= "\t" . "<li>出版社：" . $manufacturer . " ( " . $publicationDate . " )</li>" . PHP_EOL;
		$detail_list .= "\t" . "<li>" . $binding . "：" . $pagesCount . " ページ</li>" . PHP_EOL;
		break;
	case "dvd":
		$detail_list .= "\t" . "<li>販売元：" . $manufacturer . " ( " . $releaseDate . " )</li>" . PHP_EOL;
//		$detail_list .= "\t" . "<li>時間：" . $attr->RunningTime . " 分</li>" . PHP_EOL;
//		$detail_list .= "\t" . "<li>" . $attr->NumberOfDiscs . " 枚組 ( " . $attr->binding . " )</li>" . PHP_EOL;
		if( $binding ) $detail_list .= "\t" . "<li>カテゴリ：" . $binding . "</li>" . PHP_EOL;
		break;
	case "music":
		if( count( $contributors ) ) {
			$author_list = '';
			foreach( $contributors as $contributor ) {
				if( $contributor->{'RoleType'} == 'artist' ) {
					$author_list .= esc_html( $contributor->{'Name'} . ' ' );
				}
			}
			if( $author_list != '' ) {
				$detail_list .= "\t" ."<li>アーティスト：" . trim( $author_list ) . "</li>" . PHP_EOL;
			}
		}
//		$detail_list .= "<li>アーティスト：" . $Artist . "</li>" . PHP_EOL;
		$detail_list .= "\t" . "<li>レーベル：" . $manufacturer . "( " . $releaseDate . " )</li>" . PHP_EOL;
		break;
	default:
		if( $manufacturer ) $detail_list .= "\t" . "<li>メーカー：" . $manufacturer . "</li>" . PHP_EOL;
		if( $binding ) $detail_list .= "\t" . "<li>カテゴリ：" . $binding . "</li>" . PHP_EOL;
		if( $releaseDate ) $detail_list .= "\t" . "<li>発売日：" . $releaseDate . "</li>" . PHP_EOL;
}
if( $price ) $detail_list .= "\t" . "<li>価格：" . $price . "</li>" . PHP_EOL;

if ( $detail_list ) {
	$detail .= '<ul class="sa-detail">' . PHP_EOL;
	$detail .= $detail_list;
	$detail .= '</ul>';
}

?>
<div class="simple-amazon-view">

<div class="sa-img-box"><a href="<?php echo $url; ?>" rel="sponsored"><img src="<?php echo $m_image_url; ?>" height="<?php echo $m_image_h; ?>" width="<?php echo $m_image_w; ?>" title="<?php echo $title; ?>" class="sa-image" /></a></div>

<div class="sa-detail-box">
<p class="sa-title"><a href="<?php echo $url; ?>" rel="sponsored"><?php echo $title; ?></a></p>
<?php echo $detail; //商品詳細リストを出力 ?>
<p class="sa-link"><a href="<?php echo $url; ?>" rel="sponsored">Amazon詳細ページへ</a></p>
</div>

</div><!-- simple-amazon-view -->

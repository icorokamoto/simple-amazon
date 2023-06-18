<?php

// 商品詳細のリストを作成する
$detail = "";
$detail_list = "";

// $ItemInfo        = $item->{'ItemInfo'};
// $ByLineInfo      = $ItemInfo->{'ByLineInfo'};
// $Classifications = $ItemInfo->{'Classifications'};
// $ContentInfo     = $ItemInfo->{'ContentInfo'};
// $ProductInfo     = $ItemInfo->{'ProductInfo'};
// $TradeInInfo     = $ItemInfo->{'TradeInInfo'};

// $ProductGroup    = $Classifications->{'ProductGroup'}->{'DisplayValue'};
// $Price           = $TradeInInfo->{'Price'}->{'DisplayAmount'};

$Contributors    = isset( $ByLineInfo->{'Contributors'} ) ? $ByLineInfo->{'Contributors'} : null;
$Manufacturer    = isset( $ByLineInfo->{'Manufacturer'}->{'DisplayValue'} ) ? esc_html( $ByLineInfo->{'Manufacturer'}->{'DisplayValue'} ) : null;
$PublicationDate = isset( $ContentInfo->{'PublicationDate'}->{'DisplayValue'} ) ? date( "Y/m/d", strtotime( $ContentInfo->{'PublicationDate'}->{'DisplayValue'} ) ) : null;
$PagesCount      = isset( $ContentInfo->{'PagesCount'}->{'DisplayValue'} ) ? esc_html( $ContentInfo->{'PagesCount'}->{'DisplayValue'} ) : null;
$Binding         = isset( $Classifications->{'Binding'}->{'DisplayValue'} ) ? esc_html( $Classifications->{'Binding'}->{'DisplayValue'} ) : null;
$ReleaseDate     = isset( $ProductInfo->{'ReleaseDate'}->{'DisplayValue'} ) ? date( "Y/m/d", strtotime( $ProductInfo->{'ReleaseDate'}->{'DisplayValue'} ) ) : null;

//商品のカテゴリ別に取得する情報を変える
switch( strtolower( $ProductGroup ) ) {
	case "book":
		if( count( $Contributors ) ) {
			$author_list = '';
			foreach( $Contributors as $Contributor ) {
				if( $Contributor->{'RoleType'} == 'author' ) {
					$author_list .= esc_html( $Contributor->{'Name'} . ' ' );
				}
			}
			if( $author_list != '' ) {
				$detail_list .= "\t" ."<li>著者：" . trim( $author_list ) . "</li>\n";
			}
		}
		$detail_list .= "\t" . "<li>出版社：" . $Manufacturer . " ( " . $PublicationDate . " )</li>" . "\n";
		$detail_list .= "\t" . "<li>" . $Binding . "：" . $PagesCount . " ページ</li>\n";
		break;
	case "ebooks":
		if( count( $Contributors ) ) {
			$author_list = '';
			foreach( $Contributors as $Contributor ) {
				if( $Contributor->{'RoleType'} == 'author' ) {
					$author_list .= esc_html( $Contributor->{'Name'} . ' ' );
				}
			}
			if( $author_list != '' ) {
				$detail_list .= "\t" ."<li>著者：" . trim( $author_list ) . "</li>\n";
			}
		}
		$detail_list .= "\t" . "<li>出版社：" . $Manufacturer . " ( " . $PublicationDate . " )</li>" . "\n";
		$detail_list .= "\t" . "<li>" . $Binding . "：" . $PagesCount . " ページ</li>\n";
		break;
	case "dvd":
		$detail_list .= "\t" . "<li>販売元：" . $Manufacturer . " ( " . $ReleaseDate . " )</li>" . "\n";
//		$detail_list .= "\t" . "<li>時間：" . $attr->RunningTime . " 分</li>" . "\n";
//		$detail_list .= "\t" . "<li>" . $attr->NumberOfDiscs . " 枚組 ( " . $attr->Binding . " )</li>\n";
		if( $Binding ) $detail_list .= "\t" . "<li>カテゴリ：" . $Binding . "</li>\n";
		break;
	case "music":
		if( count( $Contributors ) ) {
			$author_list = '';
			foreach( $Contributors as $Contributor ) {
				if( $Contributor->{'RoleType'} == 'artist' ) {
					$author_list .= esc_html( $Contributor->{'Name'} . ' ' );
				}
			}
			if( $author_list != '' ) {
				$detail_list .= "\t" ."<li>アーティスト：" . trim( $author_list ) . "</li>\n";
			}
		}
//		$detail_list .= "<li>アーティスト：" . $Artist . "</li>" . "\n";
		$detail_list .= "\t" . "<li>レーベル：" . $Manufacturer . "( " . $ReleaseDate . " )</li>\n";
		break;
	default:
		if( $Manufacturer ) $detail_list .= "\t" . "<li>メーカー：" . $Manufacturer . "</li>\n";
		if( $Binding ) $detail_list .= "\t" . "<li>カテゴリ：" . $Binding . "</li>\n";
		if( $ReleaseDate ) $detail_list .= "\t" . "<li>発売日：" . $ReleaseDate . "</li>\n";
}
if( $Price ) $detail_list .= "\t" . "<li>価格：" . $Price . "</li>\n";

if ( $detail_list ) {
	$detail .= '<ul class="sa-detail">' . "\n";
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

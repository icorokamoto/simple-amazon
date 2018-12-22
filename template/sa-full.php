<?php

// 商品詳細のリストを作成する
$detail = "";
$detail_list = "";

$attr = $item->ItemAttributes;

//商品のカテゴリ別に取得する情報を変える
switch($attr->ProductGroup) {
	case "Book":
		if( $attr->Author !="" ) {
			$detail_list .= "\t" ."<li>著者／訳者：";
			if( count($attr->Author) == 1 ) {
				$detail_list .= $attr->Author; 
			} else {
				foreach($attr->Author as $auth){ $detail_list .= $auth.' '; }
			}
			$detail_list .= "</li>\n";
		}
		$detail_list .= "\t" . "<li>出版社：" . $attr->Manufacturer . " ( " . $attr->PublicationDate . " )</li>" . "\n";
		$detail_list .= "\t" . "<li>" . $attr->Binding . "：" . $attr->NumberOfPages . " ページ</li>\n";
		break;
	case "eBooks":
		if( $attr->Author !="" ) {
			$detail_list .= "\t" ."<li>著者／訳者：";
			if( count($attr->Author) == 1 ) {
				$detail_list .= $attr->Author; 
			} else {
				foreach($attr->Author as $auth){ $detail_list .= $auth.' '; }
			}
			$detail_list .= "</li>\n";
		}
		$detail_list .= "\t" . "<li>出版社：" . $attr->Manufacturer . " ( " . $attr->PublicationDate . " )</li>" . "\n";
		$detail_list .= "\t" . "<li>" . $attr->Binding . "：" . $attr->NumberOfPages . " ページ</li>\n";
		break;
	case "DVD":
		$detail_list .= "\t" . "<li>販売元：" . $attr->Manufacturer . " ( " . $attr->ReleaseDate . " )</li>" . "\n";
		$detail_list .= "\t" . "<li>時間：" . $attr->RunningTime . " 分</li>" . "\n";
		$detail_list .= "\t" . "<li>" . $attr->NumberOfDiscs . " 枚組 ( " . $attr->Binding . " )</li>\n";
		break;
	case "Music":
		$detail_list .= "<li>アーティスト：" . $attr->Artist . "</li>" . "\n";
		$detail_list .= "\t" . "<li>レーベル：" . $attr->Manufacturer . "( " . $attr->ReleaseDate . " )</li>\n";
		break;
	default:
		if( $attr->Manufacturer ) $detail_list .= "\t" . "<li>メーカー：" . $attr->Manufacturer . "</li>\n";
		if( $attr->Binding ) $detail_list .= "\t" . "<li>カテゴリ：" . $attr->Binding . "</li>\n";
		if( $attr->ReleaseDate ) $detail_list .= "\t" . "<li>発売日：" . $attr->ReleaseDate . "</li>\n";
}
if( $attr->ListPrice->FormattedPrice ) $detail_list .= "\t" . "<li>定価：" . $attr->ListPrice->FormattedPrice . "</li>\n";

if ( $detail_list ) {
	$detail .= '<ul class="sa-detail">' . "\n";
	$detail .= $detail_list;
	$detail .= '</ul>';
}

?>
<div class="simple-amazon-view">

<div class="sa-img-box"><a href="<?php echo $url; ?>" rel="nofollow"><img src="<?php echo $m_image_url; ?>" height="<?php echo $m_image_h; ?>" width="<?php echo $m_image_w; ?>" title="<?php echo $title; ?>" class="sa-image" /></a></div>

<div class="sa-detail-box">
<p class="sa-title"><a href="<?php echo $url; ?>" rel="nofollow"><?php echo $title; ?></a></p>
<?php echo $detail; //商品詳細リストを出力 ?>
<p class="sa-link"><a href="<?php echo $url; ?>" rel="nofollow">Amazon詳細ページへ</a></p>
</div>

</div><!-- simple-amazon-view -->

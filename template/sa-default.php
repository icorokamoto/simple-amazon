<?php
/*

テンプレートでは以下の変数が利用できます。

商品名:     <?php echo $title; ?>
商品リンク: <?php echo $url; ?>

画像 小(75 x 75px)
  URL:  <?php echo $s_image_url; ?>
  高さ: <?php echo $s_image_h; ?>
  幅:   <?php echo $s_image_w; ?>

画像 中(160 x 160px)
  URL:  <?php echo $m_image_url; ?>
  高さ: <?php echo $m_image_h; ?>
  幅:   <?php echo $m_image_w; ?>

画像 小(500 x 500px)
  URL:  <?php echo $l_image_url; ?>
  高さ: <?php echo $l_image_h; ?>
  幅:   <?php echo $l_image_w; ?>

また、$item を使って、ItemAttributesレスポンスグループを使うことも出来ます。

$item->ItemAttributes->Binding

という感じでレスポンスグループを参照できます。詳しくは Product Advertising APIの開発者ガイド( https://images-na.ssl-images-amazon.com/images/G/09/associates/paapi/dg/index.html )のAPIリファレンスやテンプレートファイルの sa-full.php を参照してください。

*/
?>
<div class="simple-amazon-view">
	<p class="sa-img-box"><a href="<?php echo $url; ?>" rel="nofollow"><img src="<?php echo $m_image_url; ?>" height="<?php echo $m_image_h; ?>" width="<?php echo $m_image_w; ?>" title="<?php echo $title; ?>" class="sa-image" /></a></p>
	<p class="sa-title"><a href="<?php echo $url; ?>" rel="nofollow"><?php echo $title; ?></a></p>
</div>

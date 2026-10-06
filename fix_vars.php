<?php $c = file_get_contents("App/Segment/Form/Panel/contentItem.php"); $c = str_replace("\$card[\"content\"][\$i]", "\$item", $c); file_put_contents("App/Segment/Form/Panel/contentItem.php", $c); ?>

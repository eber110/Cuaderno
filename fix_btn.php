<?php
$c = file_get_contents("App/Segment/Form/Panel/contentButtonPanel.php");
$c = preg_replace("/<button type=\"submit\" name=\"add_content_type\" value=\"([a-z_]+)\"/", "<button type=\"button\" data-action=\"add-block-template\" data-type=\"$1\"", $c);
file_put_contents("App/Segment/Form/Panel/contentButtonPanel.php", $c);
?>

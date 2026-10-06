<?php
$lines = file("App/Segment/Form/Panel/contentButtonPanel.php");
$top = array_slice($lines, 0, 52);
$bottom = array_slice($lines, 1340);
$middle = "      <?php for (\$i=0; \$i < \$cant; \$i++) {\n        \$item = \$card[\"content\"][\$i];\n        include __DIR__ . \"/contentItem.php\";\n      } ?>\n";
file_put_contents("App/Segment/Form/Panel/contentButtonPanel.php", implode("", $top) . $middle . implode("", $bottom));
?>

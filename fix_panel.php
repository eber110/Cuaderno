<?php
$c = file_get_contents("App/Segment/Form/Panel/contentButtonPanel.php");
$c = str_replace("      <?php for (\$i=0; \$i < \$cant; \$i++) :\n      <?php for (\$i=0; \$i < \$cant; \$i++) {\n        \$item = \$card[\"content\"][\$i];\n        include __DIR__ . \"/contentItem.php\";\n      } ?>\n      <?php endfor?>",
"      <?php for (\$i=0; \$i < \$cant; \$i++) {\n        \$item = \$card[\"content\"][\$i];\n        include __DIR__ . \"/contentItem.php\";\n      } ?>", $c);
file_put_contents("App/Segment/Form/Panel/contentButtonPanel.php", $c);
?>

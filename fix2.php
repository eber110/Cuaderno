<?php
$lines = file("App/Segment/Form/Panel/contentButtonPanel.php");
$res = array_slice($lines, 0, 51);
$res[] = "    <div id=\"sortable-content-list\" class=\"flex-column gap20 w100\">\n";
$res[] = "      <?php for (\$i=0; \$i < \$cant; \$i++) {\n";
$res[] = "        \$item = \$card[\"content\"][\$i];\n";
$res[] = "        include __DIR__ . \"/contentItem.php\";\n";
$res[] = "      } ?>\n";
$res[] = "    </div>\n";
$res[] = "    <input type=\"submit\" value=\"Guardar\" class=\"hidden\">\n";
$res[] = "  </div>\n";
$res[] = "</form>\n";

$res[] = "<!-- TEMPLATES PARA FASE 3 -->\n";
$res[] = "<div id=\"editor-templates\" style=\"display:none;\">\n";
$res[] = "  <?php\n";
$res[] = "    \$templateTypes = [\"link\", \"video\", \"product\", \"product_group\", \"campaign\", \"banner\", \"title\", \"text\", \"separator\"];\n";
$res[] = "    foreach (\$templateTypes as \$tType) {\n";
$res[] = "      \$i = \"{{INDEX}}\";\n";
$res[] = "      \$item = [\"type\" => \$tType];\n";
$res[] = "      echo \"<template id=\\\"tpl_block_{\$tType}\\\">\";\n";
$res[] = "      include __DIR__ . \"/contentItem.php\";\n";
$res[] = "      echo \"</template>\";\n";
$res[] = "    }\n";
$res[] = "  ?>\n";
$res[] = "</div>\n";

file_put_contents("App/Segment/Form/Panel/contentButtonPanel.php", implode("", $res));
?>

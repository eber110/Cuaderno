<?php
$c = file_get_contents("App/Public/Js/designDraftManager.js");
$js = <<<JS
  function updateJsonDraftFromPath(path, value) {
    window.updateJsonDraft(state => {
      // Regex para encontrar arrays como content[0][title] o variables directas como title
      const parts = path.split(/\[|\]\[|\]/).filter(Boolean);
      let current = state;
      for (let i = 0; i < parts.length - 1; i++) {
        let part = parts[i];
        if (current[part] === undefined) {
           current[part] = (isNaN(parseInt(parts[i+1], 10))) ? {} : [];
        }
        current = current[part];
      }
      current[parts[parts.length - 1]] = value;
      return state;
    });
  }
JS;

$c = str_replace("  function setDraftField(name, value) {\n    if (!name) return;", $js . "\n  function setDraftField(name, value) {\n    if (!name) return;\n    updateJsonDraftFromPath(name, value);", $c);
file_put_contents("App/Public/Js/designDraftManager.js", $c);
?>

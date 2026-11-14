<?php
  /** @var mixed $card */
  $rrssItems = !empty($card["rrss"]) && is_array($card["rrss"]) ? $card["rrss"] : [];
?>
<div class="preview-rrss-container flex-row center-center wrap gap10 gap-sml-8 p30 pt8 pb0 x30 xp20"<?= empty($rrssItems) ? ' style="display: none;"' : '' ?>>
  <?php foreach ($rrssItems as $value) :?>
    <?php
      $socialName = $value[0] ?? $value["name"] ?? '';
      $socialUrl  = $value[1] ?? $value["url"] ?? '';
      $hasUrl     = !empty($socialUrl);
    ?>
    <a href="<?= eUrl($socialUrl ?: '#') ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e($socialName) ?>" class="color-text-card hover-lift-ns track-link-click" data-user="<?= e($card['profile'] ?? '') ?>" data-link-id="rrss_<?= e($socialName) ?>" style="<?= $hasUrl ? '' : 'display: none;' ?>">
      <?= svg($socialName)?>
    </a>
  <?php endforeach?>
</div>
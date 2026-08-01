<?php
  /** 
   * @var array $summary 
   */
?>
<div class="grid col-desk-4 col-mid-2 col-sml-2 gap15 w100">
  <div class="flex-column center-center p15 br15 border-item-panel text-c gap5 shadow-soft">
    <span class="text-muted bold500 x13">Total Visitas</span>
    <span class="x24 bold700"><?= number_format($summary['total_views'] ?? 0) ?></span>
  </div>
  <div class="flex-column center-center p15 br15 border-item-panel text-c gap5 shadow-soft">
    <span class="text-muted bold500 x13">Visitas Únicas</span>
    <span class="x24 bold700"><?= number_format($summary['unique_views'] ?? 0) ?></span>
  </div>
  <div class="flex-column center-center p15 br15 border-item-panel text-c gap5 shadow-soft">
    <span class="text-muted bold500 x13">Total Clics</span>
    <span class="x24 bold700"><?= number_format($summary['total_clicks'] ?? 0) ?></span>
  </div>
  <div class="flex-column center-center p15 br15 border-item-panel text-c gap5 shadow-soft">
    <span class="text-muted bold500 x13">CTR Global</span>
    <span class="x24 bold700 text-success"><?= number_format($summary['ctr'] ?? 0, 2) ?>%</span>
  </div>
</div>

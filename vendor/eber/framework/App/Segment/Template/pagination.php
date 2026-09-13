<?php
/**
 * Componente de paginación reutilizable para listas de Builder.
 * 
 * Requiere variables:
 * - $currentPage (int)
 * - $totalPages (int)
 * - $baseUrl (string opcional)
 */
$currentPage = (int)($currentPage ?? 1);
$totalPages  = (int)($totalPages ?? 1);
$baseUrl     = $baseUrl ?? strtok($_SERVER["REQUEST_URI"] ?? '/', '?');

if ($totalPages > 1):
?>
<nav class="pagination-container flex-row center gap10 my25" aria-label="Paginación">
  <?php if ($currentPage > 1): ?>
    <a href="<?= htmlspecialchars($baseUrl . '?page=' . ($currentPage - 1), ENT_QUOTES, 'UTF-8') ?>" class="btn-page p8 px12 br6 border text-none color2" rel="prev">&laquo; Anterior</a>
  <?php endif; ?>

  <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
    <?php if ($i === $currentPage): ?>
      <span class="btn-page-active p8 px12 br6 back-color color2 bold600"><?= $i ?></span>
    <?php else: ?>
      <a href="<?= htmlspecialchars($baseUrl . '?page=' . $i, ENT_QUOTES, 'UTF-8') ?>" class="btn-page p8 px12 br6 border text-none color2"><?= $i ?></a>
    <?php endif; ?>
  <?php endfor; ?>

  <?php if ($currentPage < $totalPages): ?>
    <a href="<?= htmlspecialchars($baseUrl . '?page=' . ($currentPage + 1), ENT_QUOTES, 'UTF-8') ?>" class="btn-page p8 px12 br6 border text-none color2" rel="next">Siguiente &raquo;</a>
  <?php endif; ?>
</nav>
<?php endif; ?>

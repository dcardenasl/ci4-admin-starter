<?php
$currentPage = (int) ($pagination['current_page'] ?? 1);
$lastPage    = (int) ($pagination['last_page'] ?? 1);
$total       = (int) ($pagination['total'] ?? 0);
$baseUrl     = $paginationUrl ?? current_url();

if ($lastPage <= 1) {
    return;
}
?>
<div class="mt-6 flex items-center justify-between border-t border-gray-200 pt-4 text-sm text-gray-600">
    <span><?= esc("Pagina {$currentPage} de {$lastPage} ({$total} resultados)") ?></span>
    <nav class="flex items-center gap-1">
        <?php if ($currentPage > 1): ?>
            <a href="<?= esc($baseUrl . '?page=' . ($currentPage - 1)) ?>"
               class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50">Anterior</a>
        <?php endif; ?>

        <?php
        $start = max(1, $currentPage - 2);
        $end   = min($lastPage, $currentPage + 2);
        ?>
        <?php for ($i = $start; $i <= $end; $i++): ?>
            <?php if ($i === $currentPage): ?>
                <span class="rounded-lg bg-brand-600 px-3 py-1.5 text-xs text-white"><?= $i ?></span>
            <?php else: ?>
                <a href="<?= esc($baseUrl . '?page=' . $i) ?>"
                   class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($currentPage < $lastPage): ?>
            <a href="<?= esc($baseUrl . '?page=' . ($currentPage + 1)) ?>"
               class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50">Siguiente</a>
        <?php endif; ?>
    </nav>
</div>

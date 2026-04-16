<?php
$mode        = (string) ($pagination['mode'] ?? 'page');
$current_page = (int) ($pagination['current_page'] ?? 1);
$lastPage    = (int) ($pagination['last_page'] ?? 1);
$total       = (int) ($pagination['total'] ?? 0);
$nextCursor  = (string) ($pagination['next_cursor'] ?? '');
$prevCursor  = (string) ($pagination['prev_cursor'] ?? '');
$hasMore     = (bool) ($pagination['has_more'] ?? false);
$baseUrl     = $paginationUrl ?? current_url();
$queryParams = $paginationQuery ?? [];

if ($mode === 'page' && $lastPage <= 1) {
    return;
}

if ($mode === 'cursor' && $prevCursor === '' && $nextCursor === '' && ! $hasMore) {
    return;
}

$buildPageUrl = static function (int $page) use ($baseUrl, $queryParams): string {
    $query = $queryParams;
    $query['page'] = $page;

    return $baseUrl . '?' . http_build_query($query);
};

$buildCursorUrl = static function (string $cursor) use ($baseUrl, $queryParams): string {
    $query = $queryParams;
    $query['cursor'] = $cursor;

    return $baseUrl . '?' . http_build_query($query);
};
?>
<div class="mt-6 flex items-center justify-between border-t border-gray-200 pt-4 text-sm text-gray-600">
    <span>
        <?php if ($mode === 'cursor'): ?>
            <?= esc(lang('App.visible_results') . ': ' . $total) ?>
        <?php else: ?>
            <?= esc(lang('App.page_summary', [$current_page, $lastPage, $total])) ?>
        <?php endif; ?>
    </span>
    <nav class="flex items-center gap-1">
        <?php if ($mode === 'cursor'): ?>
            <?php if ($prevCursor !== ''): ?>
                <a href="<?= esc($buildCursorUrl($prevCursor)) ?>" data-table-nav="1"
                   class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50"><?= lang('App.previous') ?></a>
            <?php endif; ?>

            <?php if ($nextCursor !== ''): ?>
                <a href="<?= esc($buildCursorUrl($nextCursor)) ?>" data-table-nav="1"
                   class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50"><?= lang('App.next') ?></a>
            <?php endif; ?>
        <?php else: ?>
            <?php if ($current_page > 1): ?>
                <a href="<?= esc($buildPageUrl($current_page - 1)) ?>" data-table-nav="1"
               class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50"><?= lang('App.previous') ?></a>
            <?php endif; ?>

            <?php
            $start = max(1, $current_page - 2);
$end   = min($lastPage, $current_page + 2);
?>
            <?php for ($i = $start; $i <= $end; $i++): ?>
                <?php if ($i === $current_page): ?>
                    <span class="rounded-lg bg-brand-600 px-3 py-1.5 text-xs text-white"><?= $i ?></span>
                <?php else: ?>
                    <a href="<?= esc($buildPageUrl($i)) ?>" data-table-nav="1"
                   class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($current_page < $lastPage): ?>
                <a href="<?= esc($buildPageUrl($current_page + 1)) ?>" data-table-nav="1"
               class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50"><?= lang('App.next') ?></a>
            <?php endif; ?>
        <?php endif; ?>
    </nav>
</div>

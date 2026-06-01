<?php
/**
 * @var string $value
 * @var string|null $color
 */

$value = strtolower(trim($value));
$label = ucwords(str_replace(['_', '-'], ' ', $value));

// Core premium color mapping matching the design system
$colors = [
    'active'     => 'bg-green-50 text-green-700 border-green-200',
    'success'    => 'bg-green-50 text-green-700 border-green-200',
    'published'  => 'bg-green-50 text-green-700 border-green-200',
    'open'       => 'bg-blue-50 text-blue-700 border-blue-200',
    'pending'    => 'bg-amber-50 text-amber-700 border-amber-200',
    'draft'      => 'bg-gray-50 text-gray-700 border-gray-200',
    'inactive'   => 'bg-gray-50 text-gray-700 border-gray-200',
    'archived'   => 'bg-gray-50 text-gray-700 border-gray-200',
    'closed'     => 'bg-red-50 text-red-700 border-red-200',
    'unhealthy'  => 'bg-red-50 text-red-700 border-red-200',
    'error'      => 'bg-red-50 text-red-700 border-red-200',
    'danger'     => 'bg-red-50 text-red-700 border-red-200',
];

$class = $color ?? $colors[$value] ?? 'bg-brand-50 text-brand-700 border-brand-200';
?>
<span class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-semibold <?= $class ?>">
    <?= esc($label) ?>
</span>

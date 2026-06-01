<?php
/**
 * @var float|int $value
 * @var int|null $decimals
 * @var string|null $type 'currency', 'decimal', or 'percent' (default: 'decimal')
 * @var string|null $currency Currency code (e.g. 'USD', 'CLP')
 * @var string|null $locale Custom locale (e.g. 'en_US', 'es_CL')
 */

$type = $type ?? 'decimal';
$locale = $locale ?? service('language')->getLocale() ?? 'en';

// Normalize locales (CI4 uses 'es', 'en', php NumberFormatter prefers 'es_ES', 'es_CL', 'en_US')
$localeMap = [
    'es' => 'es_CL', // default Spanish to Chile for CLP
    'en' => 'en_US',
];
$canonicalLocale = $localeMap[$locale] ?? $locale;

if ($type === 'currency') {
    // If locale is Chile or Spanish, default currency to CLP, otherwise USD
    $currency = $currency ?? (str_contains(strtolower($canonicalLocale), 'cl') || $locale === 'es' ? 'CLP' : 'USD');
    $formatter = new NumberFormatter($canonicalLocale, NumberFormatter::CURRENCY);
    $formatted = $formatter->formatCurrency((float) $value, $currency);
} else {
    $decimals = $decimals ?? 2;
    $formatter = new NumberFormatter($canonicalLocale, NumberFormatter::DECIMAL);
    $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $decimals);
    $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $decimals);
    $formatted = $formatter->format((float) $value);
}
?>
<span class="text-sm font-medium text-gray-900 font-mono whitespace-nowrap">
    <?= esc($formatted) ?>
</span>

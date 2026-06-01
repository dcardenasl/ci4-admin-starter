<?php
/**
 * @var string|null $value
 * @var string|null $format Standard PHP date format (e.g. 'Y-m-d H:i')
 * @var string|null $locale Custom locale (e.g. 'es', 'en')
 * @var int|null $dateType IntlDateFormatter constant (default: IntlDateFormatter::MEDIUM)
 * @var int|null $timeType IntlDateFormatter constant (default: IntlDateFormatter::SHORT)
 */

$format = $format ?? null;
$locale = $locale ?? service('language')->getLocale() ?? 'en';
$dateType = $dateType ?? IntlDateFormatter::MEDIUM;
$timeType = $timeType ?? IntlDateFormatter::SHORT;
$display = '-';

if (!empty($value)) {
    try {
        $date = new DateTime($value);

        if ($format !== null) {
            $display = $date->format($format);
        } else {
            $localeMap = [
                'es' => 'es_CL',
                'en' => 'en_US',
            ];
            $canonicalLocale = $localeMap[$locale] ?? $locale;

            $formatter = new IntlDateFormatter(
                $canonicalLocale,
                $dateType,
                $timeType
            );
            $display = $formatter->format($date);
        }
    } catch (Exception $e) {
        $display = esc($value);
    }
}
?>
<span class="text-sm text-gray-500 whitespace-nowrap">
    <?= esc($display) ?>
</span>

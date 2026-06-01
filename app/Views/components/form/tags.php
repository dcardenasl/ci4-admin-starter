<?php
/**
 * @var string $name
 * @var string $label
 * @var mixed|null $value Either array or comma-separated string
 * @var bool|null $required
 * @var string|null $placeholder
 * @var string|null $help
 */

helper('form');

$required = $required ?? false;
$rawValue = old($name, $value ?? '');
if (is_array($rawValue)) {
    $rawValue = implode(',', $rawValue);
}
$placeholder = $placeholder ?? lang('App.add_tag') ?? 'Add tag...';
$help = $help ?? '';
?>
<div x-data="{
    newTag: '',
    tags: <?= json_encode(array_filter(array_map('trim', explode(',', (string) $rawValue)))) ?>,
    addTag() {
        let tag = this.newTag.trim();
        if (tag && !this.tags.includes(tag)) {
            this.tags.push(tag);
        }
        this.newTag = '';
    },
    removeTag(index) {
        this.tags.splice(index, 1);
    }
}">
    <label class="block text-sm font-medium text-gray-700" for="<?= esc($name, 'attr') ?>-input">
        <?= lang($label) ?>
        <?php if ($required): ?>
            <span class="text-red-500" aria-hidden="true">*</span>
        <?php endif; ?>
    </label>
    
    <div class="mt-1 w-full rounded-lg border border-gray-300 bg-white p-1.5 focus-within:border-brand-500 focus-within:ring-1 focus-within:ring-brand-500">
        <div class="flex flex-wrap gap-1.5 items-center">
            <template x-for="(tag, index) in tags" :key="index">
                <span class="inline-flex items-center gap-1 rounded-md bg-brand-50 px-2 py-1 text-xs font-medium text-brand-700">
                    <span x-text="tag"></span>
                    <button type="button" @click="removeTag(index)" class="text-brand-400 hover:text-brand-600 focus:outline-none">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </span>
            </template>
            <input 
                id="<?= esc($name, 'attr') ?>-input"
                type="text" 
                x-model="newTag" 
                @keydown.enter.prevent="addTag()" 
                @keydown.comma.prevent="addTag()"
                @blur="addTag()"
                placeholder="<?= esc($placeholder, 'attr') ?>"
                class="flex-1 min-w-[120px] bg-transparent border-0 p-0 text-sm focus:ring-0 focus:outline-none"
            >
        </div>
        <input type="hidden" name="<?= esc($name, 'attr') ?>" :value="tags.join(',')">
    </div>
    
    <?php if ($help): ?>
        <p class="mt-1 text-xs text-gray-500"><?= lang($help) ?></p>
    <?php endif; ?>
    <?= render_field_error($name) ?>
</div>

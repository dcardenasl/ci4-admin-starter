<?php
/**
 * @var string $name
 * @var string $label
 * @var array|null $options Associative array for static mode [value => label]
 * @var mixed|null $value
 * @var bool|null $required
 * @var bool|null $async
 * @var string|null $api_endpoint API endpoint for async search
 * @var string|null $placeholder
 * @var string|null $help
 */

helper('form');

$required = $required ?? false;
$value = old($name, $value ?? '');
$placeholder = $placeholder ?? '';
$help = $help ?? '';
$async = $async ?? false;
$options = $options ?? [];
$api_endpoint = $api_endpoint ?? '';
$hasOptions = is_array($options) && $options !== [];
?>
<?php if ($async): ?>
<div x-data="{
    open: false,
    search: '',
    value: '<?= esc($value, 'js') ?>',
    label: '',
    loading: false,
    options: [],
    async init() {
        if (this.value) {
            this.loading = true;
            try {
                const response = await fetch(`<?= esc($api_endpoint, 'js') ?>/${this.value}`);
                if (response.ok) {
                    const data = await response.json();
                    this.label = data.name || data.title || data.label || this.value;
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.loading = false;
            }
        }
    },
    async searchOptions() {
        if (this.search.length < 2) {
            this.options = [];
            return;
        }
        this.loading = true;
        try {
            const response = await fetch(`<?= esc($api_endpoint, 'js') ?>?search=${encodeURIComponent(this.search)}`);
            if (response.ok) {
                const data = await response.json();
                this.options = data.items || data;
            }
        } catch (e) {
            console.error(e);
        } finally {
            this.loading = false;
        }
    },
    select(opt) {
        this.value = opt.id || opt.value;
        this.label = opt.name || opt.title || opt.label;
        this.open = false;
        this.search = '';
    }
}" class="relative">
    <label class="block text-sm font-medium text-gray-700" for="<?= esc($name, 'attr') ?>">
        <?= lang($label) ?>
        <?php if ($required): ?>
            <span class="text-red-500" aria-hidden="true">*</span>
        <?php endif; ?>
    </label>
    
    <div class="relative mt-1">
        <input type="hidden" name="<?= esc($name, 'attr') ?>" :value="value">
        <button 
            type="button" 
            @click="open = !open; if(open) { $nextTick(() => $refs.searchField.focus()) }"
            class="flex w-full items-center justify-between rounded-lg border border-gray-300 bg-white px-3 py-2 text-left text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
            <?= $required ? 'required' : '' ?>
        >
            <span x-text="label || '<?= esc($placeholder ?: lang('App.select_option'), 'js') ?>'" :class="!label && 'text-gray-400'"></span>
            <span class="pointer-events-none flex items-center">
                <svg class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 8l4 4 4-4" />
                </svg>
            </span>
        </button>

        <div 
            x-show="open" 
            @click.away="open = false" 
            x-cloak
            class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-lg border border-gray-200 bg-white p-2 shadow-lg"
        >
            <input 
                x-ref="searchField"
                type="text" 
                x-model="search" 
                @input.debounce.300ms="searchOptions()"
                placeholder="<?= esc(lang('App.search'), 'attr') ?>..." 
                class="mb-2 w-full rounded-md border border-gray-200 px-3 py-1.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
            >
            <div x-show="loading" class="py-2 text-center text-xs text-gray-500">
                <?= esc(lang('App.loading')) ?>...
            </div>
            <div x-show="!loading && options.length === 0" class="py-2 text-center text-xs text-gray-500">
                <?= esc(lang('App.no_results')) ?>
            </div>
            <ul class="space-y-1">
                <template x-for="opt in options" :key="opt.id || opt.value">
                    <li>
                        <button 
                            type="button" 
                            @click="select(opt)" 
                            class="w-full rounded-md px-3 py-2 text-left text-sm hover:bg-brand-50 hover:text-brand-700"
                            x-text="opt.name || opt.title || opt.label"
                        ></button>
                    </li>
                </template>
            </ul>
        </div>
    </div>
    <?php if ($help): ?>
        <p class="mt-1 text-xs text-gray-500"><?= lang($help) ?></p>
    <?php endif; ?>
    <?= render_field_error($name) ?>
</div>
<?php else: ?>
<div>
    <label class="block text-sm font-medium text-gray-700" for="<?= esc($name, 'attr') ?>">
        <?= lang($label) ?>
        <?php if ($required): ?>
            <span class="text-red-500" aria-hidden="true">*</span>
        <?php endif; ?>
    </label>
    <?php if (! $hasOptions): ?>
        <div class="mt-1 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900" role="alert">
            <p class="font-medium"><?= esc(lang('App.relation_missing_options')) ?></p>
            <p class="mt-1 text-xs text-amber-800"><?= esc(lang('App.relation_missing_options_desc')) ?></p>
        </div>
        <?php if ($help): ?>
            <p class="mt-1 text-xs text-gray-500"><?= lang($help) ?></p>
        <?php endif; ?>
        <?= render_field_error($name) ?>
    <?php else: ?>
        <select 
            id="<?= esc($name, 'attr') ?>" 
            name="<?= esc($name, 'attr') ?>" 
            class="<?= input_class($name) ?>"
            <?= $required ? 'required' : '' ?>
            <?= field_aria_attrs($name, $required) ?>
        >
            <option value=""><?= esc($placeholder ?: lang('App.select_option')) ?></option>
            <?php foreach ($options as $val => $lbl): ?>
                <option value="<?= esc($val, 'attr') ?>" <?= (string) $val === (string) $value ? 'selected' : '' ?>>
                    <?= esc($lbl) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($help): ?>
            <p class="mt-1 text-xs text-gray-500"><?= lang($help) ?></p>
        <?php endif; ?>
        <?= render_field_error($name) ?>
    <?php endif; ?>
</div>
<?php endif; ?>

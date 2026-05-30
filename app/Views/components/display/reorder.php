<?php
/**
 * Reusable visual sorting / drag-and-drop component.
 *
 * @var array       $items         Flat list of items or array of groups
 * @var string      $saveUrl       Endpoint to POST the final sorted array
 * @var string|null $displayKey    Attribute name to show in lists (default: 'name')
 * @var bool|null   $grouped       Whether the list is grouped by category/parent (default: false)
 * @var string|null $groupTitleKey Attribute name for the group's title (default: 'name')
 * @var string|null $itemsKey      Attribute name for the child array inside groups (default: 'items')
 * @var string|null $backUrl       URL for the back navigation button (default: '#')
 * @var string|null $title         Component title (default: lang('App.reorder'))
 * @var string|null $helpText      Help instruction text under the title
 */

helper('form');

$displayKey    = $displayKey ?? 'name';
$grouped       = $grouped ?? false;
$groupTitleKey = $groupTitleKey ?? 'name';
$itemsKey      = $itemsKey ?? 'items';
$backUrl       = $backUrl ?? '#';
$title         = $title ?? lang('App.reorder') ?? 'Reordenar';
$helpText      = $helpText ?? lang('Files.gallery_drag_help') ?? 'Arrastra los elementos para cambiar su orden.';

// Normalize data structure to support both grouped and flat lists uniformly in Alpine.js
$groups = [];
if ($grouped) {
    foreach ($items as $group) {
        $groups[] = [
            'category' => [
                'id'   => $group['id'] ?? 0,
                'name' => $group[$groupTitleKey] ?? '',
            ],
            'items' => $group[$itemsKey] ?? [],
        ];
    }
} else {
    $groups[] = [
        'category' => [
            'id'   => 0,
            'name' => lang('App.items') ?? 'Elementos',
        ],
        'items' => $items,
    ];
}
?>

<div class="mb-4 flex items-center justify-between">
    <a href="<?= esc($backUrl, 'attr') ?>" class="text-sm text-brand-600 hover:text-brand-700 font-medium inline-flex items-center gap-1">
        &larr; <?= esc(lang('App.back')) ?>
    </a>
</div>

<section class="max-w-4xl" x-data="{
    groups: <?= esc(json_encode(array_values($groups))) ?>,
    draggingGroupIndex: null,
    draggingItemIndex: null,
    saving: false,
    dragStart(groupIndex, itemIndex) {
        this.draggingGroupIndex = groupIndex;
        this.draggingItemIndex = itemIndex;
    },
    dragOver(groupIndex, itemIndex) {
        if (this.draggingGroupIndex === null || this.draggingItemIndex === null) return;
        if (this.draggingGroupIndex !== groupIndex) return; // Only reorder inside the same group/category
        if (this.draggingItemIndex === itemIndex) return;
        
        const list = this.groups[groupIndex].items;
        const movedItem = list.splice(this.draggingItemIndex, 1)[0];
        list.splice(itemIndex, 0, movedItem);
        this.draggingItemIndex = itemIndex;
    },
    async saveOrder() {
        this.saving = true;
        const itemsToSave = [];
        this.groups.forEach(group => {
            group.items.forEach((item, index) => {
                itemsToSave.push({
                    id: item.id,
                    sort_order: index
                });
            });
        });

        try {
            const response = await fetch('<?= esc($saveUrl, 'js') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
                },
                body: JSON.stringify({ items: itemsToSave })
            });
            const result = await response.json();
            if (result.ok) {
                if (window.Alpine && this.$store && this.$store.toast) {
                    this.$store.toast.push('success', result.message || 'Orden guardado con éxito.');
                } else {
                    alert(result.message || 'Orden guardado con éxito.');
                }
            } else {
                if (window.Alpine && this.$store && this.$store.toast) {
                    this.$store.toast.push('error', result.message || 'Error al guardar el orden.');
                } else {
                    alert(result.message || 'Error al guardar el orden.');
                }
            }
        } catch (err) {
            if (window.Alpine && this.$store && this.$store.toast) {
                this.$store.toast.push('error', 'Error en la solicitud.');
            } else {
                alert('Error en la solicitud.');
            }
        } finally {
            this.saving = false;
        }
    }
}">
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h3 class="text-xl font-bold text-gray-900"><?= esc($title) ?></h3>
            <p class="text-sm text-gray-500 mt-1"><?= esc($helpText) ?></p>
        </div>
        <button type="button" @click="saveOrder()" :disabled="saving"
            class="<?= esc(action_button_class('primary')) ?> px-5 py-2.5 text-sm font-semibold flex items-center gap-2">
            <span x-show="!saving" class="flex items-center gap-2">
                <?= ui_icon('check', 'h-4 w-4') ?>
                <?= esc(lang('App.save') ?? 'Guardar Orden') ?>
            </span>
            <span x-show="saving" class="flex items-center gap-2">
                <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <?= esc(lang('Files.gallery_save_order') ?? 'Guardando...') ?>
            </span>
        </button>
    </div>

    <div class="space-y-6">
        <template x-for="(group, gIdx) in groups" :key="String(group.category.id)">
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden transition-all duration-300">
                <!-- Group / Category Header (Only shown if grouped or has distinct category name) -->
                <div x-show="group.category.id !== 0 || <?= $grouped ? 'true' : 'false' ?>"
                     class="bg-gradient-to-r from-gray-50 to-gray-100/50 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 rounded-lg bg-brand-50 text-brand-600">
                            <?= ui_icon('layers', 'h-4 w-4') ?>
                        </span>
                        <h4 class="font-bold text-gray-800" x-text="group.category.name"></h4>
                    </div>
                    <span class="text-xs bg-gray-200/60 text-gray-600 font-bold px-2.5 py-1 rounded-full" 
                          x-text="group.items.length + ' ' + (group.items.length === 1 ? '<?= esc(lang('App.item') ?? 'elemento') ?>' : '<?= esc(lang('App.items') ?? 'elementos') ?>')"></span>
                </div>

                <!-- Items list container -->
                <div class="divide-y divide-gray-100 p-2 min-h-[60px] bg-gray-50/30">
                    <template x-if="group.items.length === 0">
                        <div class="py-6 text-center text-sm text-gray-400">
                            <?= esc(lang('App.no_results') ?? 'Sin elementos.') ?>
                        </div>
                    </template>
                    
                    <template x-for="(item, iIdx) in group.items" :key="String(item.id)">
                        <div draggable="true"
                            @dragstart="dragStart(gIdx, iIdx)"
                            @dragover.prevent="dragOver(gIdx, iIdx)"
                            @dragend="draggingGroupIndex = null; draggingItemIndex = null"
                            class="group relative flex items-center justify-between gap-4 p-4 rounded-xl hover:bg-white hover:shadow-md transition-all duration-200 border border-transparent hover:border-gray-100 bg-white/70 backdrop-blur-sm mb-2 last:mb-0"
                            style="cursor: grab;"
                            :class="draggingGroupIndex === gIdx && draggingItemIndex === iIdx ? 'opacity-40 border-dashed border-brand-400' : ''">
                            
                            <div class="flex items-center gap-3 flex-1">
                                <!-- Drag handler visual handle -->
                                <div class="text-gray-400 hover:text-gray-600 transition-colors" style="cursor: grab;">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 9h.01M16 9h.01M8 15h.01M16 15h.01M12 9h.01M12 15h.01M12 12h.01M8 12h.01M16 12h.01" />
                                    </svg>
                                </div>
                                <div class="text-sm font-semibold text-gray-800" x-text="item['<?= esc($displayKey, 'js') ?>']"></div>
                            </div>

                            <span class="text-xs font-mono text-gray-400 bg-gray-100 px-2 py-1 rounded" x-text="'#' + (iIdx + 1)"></span>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>
</section>

<div class="mb-4">
    <a href="/admin/universal/<?= esc($resource) ?>" class="text-sm text-indigo-600 hover:text-indigo-700 font-semibold">&larr; Back to <?= esc($schema['title'] ?? ucfirst($resource)) ?> List</a>
</div>

<section class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 max-w-2xl">
    <h3 class="text-lg font-bold text-gray-900"><?= esc($title) ?></h3>
    <p class="text-xs text-gray-500 mt-1">Zero-Code Dynamic Administrative CRUD Console</p>

    <form method="post" action="<?= $mode === 'create' ? "/admin/universal/" . esc($resource) : "/admin/universal/" . esc($resource) . "/" . esc($recordId) ?>" class="mt-6 space-y-5">
        <?= csrf_field() ?>

        <?php foreach ($schema['fields'] ?? [] as $field): ?>
            <?php
                $name = $field['name'] ?? '';
            if ($name === 'id' || $name === 'created_at' || $name === 'updated_at' || $name === 'deleted_at') {
                continue;
            }
            $type = $field['type'] ?? 'string';
            $titleAttr = $field['title'] ?? ucfirst($name);
            $required = ($field['required'] ?? false) ? 'required' : '';
            $val = old($name, $record[$name] ?? ($field['default'] ?? ''));
            ?>

            <div>
                <?php if ($type === 'boolean'): ?>
                    <label class="inline-flex items-center gap-3 rounded-lg border border-gray-200 w-full px-4 py-3 hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="<?= esc($name) ?>" value="1" <?= $val ? 'checked' : '' ?>
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                        <div>
                            <span class="block text-sm font-semibold text-gray-900"><?= esc($titleAttr) ?></span>
                            <?php if (isset($field['description'])): ?>
                                <span class="block text-xs text-gray-500"><?= esc($field['description']) ?></span>
                            <?php endif; ?>
                        </div>
                    </label>
                <?php elseif ($type === 'text'): ?>
                    <label class="block text-sm font-medium text-gray-700" for="<?= esc($name) ?>"><?= esc($titleAttr) ?></label>
                    <textarea id="<?= esc($name) ?>" name="<?= esc($name) ?>" <?= $required ?> rows="4"
                              class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"><?= esc($val) ?></textarea>
                <?php elseif ($type === 'integer' || $type === 'number'): ?>
                    <label class="block text-sm font-medium text-gray-700" for="<?= esc($name) ?>"><?= esc($titleAttr) ?></label>
                    <input id="<?= esc($name) ?>" name="<?= esc($name) ?>" type="number" value="<?= esc($val) ?>" <?= $required ?>
                           class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <?php else: ?>
                    <label class="block text-sm font-medium text-gray-700" for="<?= esc($name) ?>"><?= esc($titleAttr) ?></label>
                    <input id="<?= esc($name) ?>" name="<?= esc($name) ?>" type="text" value="<?= esc($val) ?>" <?= $required ?>
                           class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <?php endif; ?>
                <?= render_field_error($name) ?>
            </div>
        <?php endforeach; ?>

        <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
            <button type="submit" class="rounded-lg bg-indigo-600 text-white px-4 py-2 text-sm font-semibold hover:bg-indigo-700">
                <?= $mode === 'create' ? 'Create' : 'Save Changes' ?>
            </button>
            <a href="/admin/universal/<?= esc($resource) ?>" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 font-semibold hover:bg-gray-50">
                Cancel
            </a>
        </div>
    </form>
</section>

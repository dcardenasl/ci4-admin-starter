<section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
    <h3 class="text-lg font-semibold text-gray-900"><?= lang('Files.uploadTitle') ?></h3>
    <form method="post" action="<?= site_url('files/upload') ?>" enctype="multipart/form-data" class="mt-4 space-y-4" x-data="{ dragging: false }"
        @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="dragging = false">
        <?= csrf_field() ?>
        <label class="block rounded-xl border-2 border-dashed p-6 text-center cursor-pointer"
            :class="dragging ? 'border-brand-400 bg-brand-50' : 'border-gray-300 bg-gray-50'">
            <input type="file" name="file" class="hidden" required>
            <p class="text-sm text-gray-700"><?= lang('Files.dragDrop') ?></p>
        </label>
        <div>
            <label class="block text-sm font-medium text-gray-700" for="visibility"><?= lang('Files.visibility') ?></label>
            <select id="visibility" name="visibility" class="mt-1 w-full md:w-56 rounded-lg border border-gray-300 px-3 py-2">
                <option value="private"><?= lang('Files.private') ?></option>
                <option value="public"><?= lang('Files.public') ?></option>
            </select>
        </div>
        <button type="submit" class="rounded-lg bg-brand-600 text-white px-4 py-2 text-sm hover:bg-brand-700"><?= lang('Files.uploadButton') ?></button>
    </form>
</section>

<section class="mt-6 bg-white border border-gray-200 rounded-xl shadow-sm p-5">
    <div class="flex items-center justify-between gap-3">
        <h3 class="text-lg font-semibold text-gray-900"><?= lang('Files.myFiles') ?></h3>
        <form method="get" action="<?= site_url('files') ?>" class="flex gap-2">
            <input type="text" name="search" value="<?= esc((string) request()->getGet('search')) ?>" placeholder="<?= lang('Files.searchPlaceholder') ?>"
                class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <button type="submit" class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"><?= lang('App.search') ?></button>
        </form>
    </div>

    <?php if (empty($files)): ?>
        <p class="mt-4 text-sm text-gray-500"><?= lang('Files.noFiles') ?></p>
    <?php else: ?>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-gray-500">
                    <tr>
                        <th class="py-2 pr-4"><?= lang('Files.fileName') ?></th>
                        <th class="py-2 pr-4"><?= lang('Files.status') ?></th>
                        <th class="py-2 pr-4"><?= lang('Files.date') ?></th>
                        <th class="py-2 pr-4"><?= lang('Files.actions') ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($files as $file): ?>
                        <?php $id = (string) ($file['id'] ?? ''); ?>
                        <tr>
                            <td class="py-3 pr-4 text-gray-800"><?= esc((string) ($file['name'] ?? $file['filename'] ?? '-')) ?></td>
                            <td class="py-3 pr-4">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs <?= status_badge($file['status'] ?? 'active') ?>">
                                    <?= esc((string) ($file['status'] ?? 'active')) ?>
                                </span>
                            </td>
                            <td class="py-3 pr-4 text-gray-600"><?= esc(format_date($file['created_at'] ?? null)) ?></td>
                            <td class="py-3 pr-4">
                                <div class="flex items-center gap-2">
                                    <a href="<?= site_url('files/' . esc($id, 'url') . '/download') ?>" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50"><?= lang('Files.download') ?></a>
                                    <form method="post" action="<?= site_url('files/' . esc($id, 'url') . '/delete') ?>" onsubmit="return confirm('<?= lang('Files.confirmDelete') ?>');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="rounded-lg bg-red-600 px-3 py-1.5 text-xs text-white hover:bg-red-700"><?= lang('Files.delete') ?></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

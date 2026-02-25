<section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
    <h3 class="text-lg font-semibold text-gray-900"><?= lang('Files.uploadTitle') ?></h3>
    <form method="post" action="<?= site_url('files/upload') ?>" enctype="multipart/form-data" class="mt-4 space-y-4" x-data="{
        dragging: false,
        selectedFileName: '',
        clientError: '',
        hasServerError: <?= has_field_error('file') ? 'true' : 'false' ?>,
        maxBytes: 10485760,
        onFileChange(event) {
            const files = event.target.files ?? [];
            const file = files.length > 0 ? files[0] : null;

            if (!file) {
                this.selectedFileName = '';
                this.clientError = '';
                return;
            }

            if (file.size > this.maxBytes) {
                this.selectedFileName = '';
                this.clientError = '<?= esc(lang('Files.fileTooLarge')) ?>';
                event.target.value = '';
                return;
            }

            this.selectedFileName = file.name;
            this.clientError = '';
            this.hasServerError = false;
        }
    }"
        @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="dragging = false">
        <?= csrf_field() ?>
        <label class="block rounded-xl border-2 border-dashed p-6 text-center cursor-pointer"
            :class="(clientError !== '' || hasServerError) ? 'border-red-400 bg-red-50' : (selectedFileName !== '' ? 'border-green-400 bg-green-50' : (dragging ? 'border-brand-400 bg-brand-50' : 'border-gray-300 bg-gray-50'))">
            <input type="file" name="file" class="hidden" required @change="onFileChange($event)">
            <p class="text-sm font-medium text-gray-800" aria-live="polite" x-show="selectedFileName !== ''" x-text="selectedFileName"></p>
            <p class="text-sm text-gray-700" x-show="selectedFileName === ''"><?= lang('Files.dragDrop') ?></p>
            <p class="mt-1 text-xs text-green-700" x-show="selectedFileName !== ''"><?= lang('Files.fileReady') ?></p>
            <p class="mt-1 text-xs text-red-700" x-show="clientError !== ''" x-text="clientError"></p>
        </label>
        <?= render_field_error('file') ?>
        <div>
            <label class="block text-sm font-medium text-gray-700" for="visibility"><?= lang('Files.visibility') ?></label>
            <select id="visibility" name="visibility" class="mt-1 w-full md:w-56 rounded-lg border border-gray-300 px-3 py-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                <option value="private"><?= lang('Files.private') ?></option>
                <option value="public"><?= lang('Files.public') ?></option>
            </select>
        </div>
        <button type="submit" class="<?= esc(action_button_class('primary')) ?>">
            <?= ui_icon('plus', 'h-3.5 w-3.5') ?><?= lang('Files.uploadButton') ?>
        </button>
    </form>
</section>

<?= view('files/partials/list_section') ?>

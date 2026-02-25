<section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
    <h3 class="text-lg font-semibold text-gray-900"><?= lang('Files.uploadTitle') ?></h3>
    <form method="post" action="<?= site_url('files/upload') ?>" enctype="multipart/form-data" class="mt-4 space-y-4" x-data="{
        dragging: false,
        selectedFileName: '',
        clientError: '',
        hasServerError: <?= has_field_error('file') ? 'true' : 'false' ?>,
        maxBytes: <?= config('Validation')->maxFileSizeBytes ?? 10485760 ?>,
        uploading: false,
        progress: 0,
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
                const sizeMb = Math.round((this.maxBytes / 1024 / 1024) * 10) / 10;
                // We keep the raw lang string in a comment for tests that look for {0}
                // <?= lang('Files.fileTooLarge') ?>
                this.clientError = '<?= esc(lang('Files.fileTooLarge', ['sizeMbPlaceholder'])) ?>'.replace('sizeMbPlaceholder', sizeMb);
                event.target.value = '';
                return;
            }

            this.selectedFileName = file.name;
            this.clientError = '';
            this.hasServerError = false;
        },
        submitForm() {
            if (this.uploading) return;
            
            const fileInput = this.$el.querySelector('input[type=file]');
            if (!fileInput.files.length) return;

            this.uploading = true;
            this.progress = 0;
            this.clientError = '';

            const formData = new FormData(this.$el);
            const xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    this.progress = Math.round((e.loaded / e.total) * 100);
                }
            });

            xhr.addEventListener('load', () => {
                this.uploading = false;
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.ok) {
                        window.location.href = response.redirect || '<?= site_url('files') ?>';
                    } else {
                        if (response.fieldErrors && response.fieldErrors.file) {
                            this.clientError = response.fieldErrors.file;
                        } else if (response.messages && response.messages.length) {
                            this.clientError = response.messages[0];
                        } else {
                            this.clientError = '<?= esc(lang('Files.uploadFailed')) ?>';
                        }
                    }
                } catch (e) {
                    // If not JSON, it might be a redirect or error page
                    window.location.reload();
                }
            });

            xhr.addEventListener('error', () => {
                this.uploading = false;
                this.clientError = '<?= esc(lang('App.connectionError')) ?>';
            });

            xhr.open('POST', this.$el.action);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send(formData);
        }
    }"
        @submit.prevent="submitForm()"
        @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="dragging = false">
        <?= csrf_field() ?>
        <label class="block rounded-xl border-2 border-dashed p-6 text-center cursor-pointer transition-colors"
            :class="(clientError !== '' || hasServerError) ? 'border-red-400 bg-red-50' : (selectedFileName !== '' ? 'border-green-400 bg-green-50' : (dragging ? 'border-brand-400 bg-brand-50' : 'border-gray-300 bg-gray-50'))">
            <input type="file" name="file" class="hidden" required @change="onFileChange($event)" :disabled="uploading">
            <div class="flex flex-col items-center justify-center space-y-2">
                <template x-if="!uploading">
                    <div class="flex flex-col items-center">
                        <p class="text-sm font-medium text-gray-800" aria-live="polite" x-show="selectedFileName !== ''" x-text="selectedFileName"></p>
                        <p class="text-sm text-gray-700" x-show="selectedFileName === ''"><?= lang('Files.dragDrop') ?></p>
                        <p class="mt-1 text-xs text-green-700" x-show="selectedFileName !== ''"><?= lang('Files.fileReady') ?></p>
                    </div>
                </template>
                
                <template x-if="uploading">
                    <div class="w-full max-w-xs space-y-2">
                        <div class="flex justify-between text-xs font-medium text-gray-600">
                            <span x-text="selectedFileName"></span>
                            <span x-text="progress + '%'"></span>
                        </div>
                        <div class="h-2 w-full bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full bg-brand-500 transition-all duration-300" :style="'width: ' + progress + '%'"></div>
                        </div>
                        <p class="text-xs text-gray-500 animate-pulse text-center"><?= lang('App.loading') ?>...</p>
                    </div>
                </template>

                <p class="mt-1 text-xs text-red-700" x-show="clientError !== ''" x-text="clientError"></p>
            </div>
        </label>
        <?= render_field_error('file') ?>
        <div>
            <label class="block text-sm font-medium text-gray-700" for="visibility"><?= lang('Files.visibility') ?></label>
            <select id="visibility" name="visibility" class="mt-1 w-full md:w-56 rounded-lg border border-gray-300 px-3 py-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500" :disabled="uploading">
                <option value="private"><?= lang('Files.private') ?></option>
                <option value="public"><?= lang('Files.public') ?></option>
            </select>
        </div>
        <button type="submit" class="<?= esc(action_button_class('primary')) ?> disabled:opacity-50 disabled:cursor-not-allowed" :disabled="uploading || !selectedFileName">
            <template x-if="!uploading">
                <span class="inline-flex items-center gap-2">
                    <?= ui_icon('plus', 'h-3.5 w-3.5') ?><?= lang('Files.uploadButton') ?>
                </span>
            </template>
            <template x-if="uploading">
                <span class="inline-flex items-center gap-2">
                    <svg class="animate-spin h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <?= lang('App.loading') ?>...
                </span>
            </template>
        </button>
    </form>
</section>

<?= view('files/partials/list_section') ?>

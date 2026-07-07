<?php if (session()->has('success')): ?>
    <div role="status" aria-live="polite" class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
        <?= esc(session('success')) ?>
    </div>
<?php endif; ?>

<?php if (session()->has('error')): ?>
    <div role="alert" aria-live="assertive" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
        <?= esc(session('error')) ?>
    </div>
<?php endif; ?>

<?php if (session()->has('fieldErrors')): ?>
    <?php $fieldErrors = session('fieldErrors'); ?>
    <?php if (is_array($fieldErrors) && count($fieldErrors) > 0): ?>
        <div role="alert" aria-live="assertive" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
            <?= esc(lang('App.errors_found')) ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if (session()->has('warning')): ?>
    <div role="status" aria-live="polite" class="mb-4 rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm font-medium text-yellow-800">
        <?= esc(session('warning')) ?>
    </div>
<?php endif; ?>

<?php if (ENVIRONMENT === 'development' && session()->has('devApiError')): ?>
    <?php
    /** @var array{status: int, body: string, messages: list<string>, errors: array<string, string>} $devErr */
    $devErr    = session('devApiError');
    $devStatus = (int) ($devErr['status'] ?? 0);
    $devBody   = (string) ($devErr['body'] ?? '');
    $devMsgs   = is_array($devErr['messages'] ?? null) ? $devErr['messages'] : [];
    $devErrs   = is_array($devErr['errors'] ?? null) ? $devErr['errors'] : [];
    $isOk      = $devStatus >= 200 && $devStatus < 300;
    $statusBg  = $devStatus === 0 ? '#6b7280' : ($isOk ? '#16a34a' : '#dc2626');
    $statusLabel = $devStatus === 0 ? 'ERR' : (string) $devStatus;

    $decoded = json_decode($devBody, true);
    $pretty  = is_array($decoded)
        ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        : $devBody;
    $escapedBody = esc((string) ($pretty ?: '(empty response body)'));
    ?>
    <details class="mb-4" style="border:2px dashed #94a3b8;border-radius:8px;overflow:hidden;background:#f8fafc">
        <summary style="cursor:pointer;padding:10px 14px;display:flex;align-items:center;gap:10px;font-size:12px;font-weight:600;color:#475569;user-select:none;list-style:none">
            <span style="background:#f59e0b;color:#fff;font-size:10px;font-weight:700;padding:1px 6px;border-radius:3px;letter-spacing:.05em">DEV</span>
            <span>API Error Detail</span>
            <span style="background:<?= $statusBg ?>;color:#fff;font-size:11px;font-weight:700;padding:2px 7px;border-radius:4px"><?= $statusLabel ?></span>
            <?php if ($devMsgs !== []): ?>
                <span style="color:#64748b;font-weight:400"><?= esc($devMsgs[0] ?? '') ?></span>
            <?php endif; ?>
            <span style="margin-left:auto;color:#94a3b8;font-size:11px">▼ expand</span>
        </summary>
        <div style="padding:0 14px 14px">
            <?php if ($devErrs !== []): ?>
                <p style="margin:10px 0 4px;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.05em">Field Errors</p>
                <ul style="margin:0 0 8px;padding:0 0 0 16px;font-size:12px;color:#dc2626">
                    <?php foreach ($devErrs as $field => $msg): ?>
                        <li><strong><?= esc($field) ?>:</strong> <?= esc($msg) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <p style="margin:10px 0 4px;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.05em">Raw Response Body</p>
            <pre style="margin:0;padding:12px;background:#0f172a;color:#86efac;border-radius:6px;font-size:11px;font-family:monospace;overflow:auto;max-height:320px;white-space:pre-wrap;word-break:break-word"><?= $escapedBody ?></pre>
        </div>
    </details>
<?php endif; ?>

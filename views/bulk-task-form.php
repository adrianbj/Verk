<?php namespace ProcessWire;
/**
 * @var Verk $this
 * @var array $pages
 * @var string $ruleLabel
 * @var array $users
 * @var array $sprints
 */
$url   = $this->page->url;
$csrf  = $this->getCSRFToken();
$csrfN = $this->getCSRFName();
$pageIdsStr = implode(',', array_column($pages, 'id'));
$currentYear = (int)date('Y');
$returnUrl = $this->safeLocalUrl((string)$this->wire('input')->get('return_url', 'string'));
$backUrl = $returnUrl ?: $url . '?view=audit';

ob_start();
?>

<div class="vk-page-head vk-compact-head">
    <div>
        <h2 class="vk-page-title"><?= __('Bulk Create Tasks') ?></h2>
        <p><?= sprintf(__('%d pages selected'), count($pages)) ?><?= $ruleLabel ? ' ' . sprintf(__('from: %s'), htmlspecialchars($ruleLabel)) : '' ?></p>
    </div>
    <?php if ($returnUrl): ?>
    <div class="vk-actions">
        <a href="<?= htmlspecialchars($returnUrl) ?>" class="uk-button uk-button-default uk-button-small"><i class="fa fa-arrow-left"></i> <?= __('Back to Audit') ?></a>
    </div>
    <?php endif; ?>
</div>

<div class="vk-bulk-workspace">

    <div>
        <div class="uk-card uk-card-default vk-form-compact">
            <div class="uk-card-header"><h3 class="vk-card-title"><?= __('Task Settings') ?></h3></div>
            <div class="uk-card-body">
                <form method="post" action="<?= $url ?>">
                    <input type="hidden" name="<?= $csrfN ?>" value="<?= $csrf ?>">
                    <input type="hidden" name="action"   value="bulk_audit_tasks">
                    <input type="hidden" name="page_ids" value="<?= htmlspecialchars($pageIdsStr) ?>">
                    <?php if ($returnUrl): ?><input type="hidden" name="return_url" value="<?= htmlspecialchars($returnUrl) ?>"><?php endif; ?>

                    <div class="vk-field">
                        <label class="uk-form-label"><?= __('Title Template') ?></label>
                        <input type="text" name="title_template" value="<?= __('Fix: {page_title}') ?>"
                               placeholder="<?= __('{page_title} = page title') ?>" class="uk-input">
                        <small class="vk-field-help"><?= sprintf(__('Use %s for the page name. Example: "Fix SEO: {page_title}"'), '<code>{page_title}</code>') ?></small>
                    </div>

                    <div class="vk-form-grid is-2">
                        <div>
                            <div class="vk-field">
                                <label class="uk-form-label"><?= __('Status') ?></label>
                                <select name="status" class="uk-select">
                                    <option value="open" selected><?= htmlspecialchars($this->statusLabel('open')) ?></option>
                                    <option value="in_progress"><?= htmlspecialchars($this->statusLabel('in_progress')) ?></option>
                                    <option value="review"><?= htmlspecialchars($this->statusLabel('review')) ?></option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <div class="vk-field">
                                <label class="uk-form-label"><?= __('Priority') ?></label>
                                <select name="priority" class="uk-select">
                                    <option value="low"><?= htmlspecialchars($this->priorityLabel('low')) ?></option>
                                    <option value="medium" selected><?= htmlspecialchars($this->priorityLabel('medium')) ?></option>
                                    <option value="high"><?= htmlspecialchars($this->priorityLabel('high')) ?></option>
                                    <option value="critical"><?= htmlspecialchars($this->priorityLabel('critical')) ?></option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="vk-form-grid is-2">
                        <div>
                            <div class="vk-field">
                                <label class="uk-form-label"><?= __('Due Date') ?></label>
                                <div class="vk-quarter-due-picker" data-quarter-due-picker data-year="<?= $currentYear ?>" data-quarter-start="<?= (int)$this->quarterStartMonth() ?>">
                                    <div class="vk-quarter-planner-head">
                                        <span><?= __('Due quarter') ?></span>
                                        <span data-quarter-hint><?= __('Set a date or choose a quarter') ?></span>
                                    </div>
                                    <div class="vk-quarter-buttons">
                                        <?php for ($q = 1; $q <= 4; $q++): ?>
                                        <button type="button" class="uk-button uk-button-default uk-button-small" data-quarter="<?= $q ?>">Q<?= $q ?></button>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <input type="date" name="due_date" class="uk-input" data-quarter-date>
                            </div>
                        </div>
                        <div>
                            <div class="vk-field">
                                <label class="uk-form-label"><?= __('Assignee') ?></label>
                                <select name="assignee_id" class="uk-select">
                                    <option value="">&mdash; <?= __('Unassigned') ?> &mdash;</option>
                                    <?php foreach ($users as $u): ?>
                                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="vk-field">
                        <label class="uk-form-label"><?= __('Sprint') ?></label>
                        <select name="sprint_id" class="uk-select">
                            <option value="">&mdash; <?= __('No sprint') ?> &mdash;</option>
                            <?php foreach ($sprints as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?><?= $s['status']==='active' ? ' &#9733;' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="vk-form-grid is-3">
                        <div>
                            <div class="vk-field">
                                <label class="uk-form-label"><?= __('Estimate') ?></label>
                                <select name="estimate_h" class="uk-select">
                                    <option value="">&mdash;</option>
                                    <option value="2">2h</option>
                                    <option value="4" selected><?= __('4h (default)') ?></option>
                                    <option value="8">8h</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <div class="vk-field">
                                <label class="uk-form-label"><?= __('Story Points') ?></label>
                                <select name="story_points" class="uk-select">
                                    <option value="">&mdash;</option>
                                    <?php foreach ([1,2,3,5,8,13,21] as $sp): ?>
                                    <option value="<?= $sp ?>"><?= $sp ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="vk-field">
                        <label class="uk-form-label"><?= __('Description (applied to all tasks)') ?></label>
                        <textarea name="description" class="uk-textarea vk-short-textarea" placeholder="<?= __('Optional shared description…') ?>"></textarea>
                    </div>

                    <div class="vk-field">
                        <button type="submit" class="uk-button uk-button-primary"><i class="fa fa-plus"></i> <?= $returnUrl ? sprintf(__('Create %d tasks and return'), count($pages)) : sprintf(__('Create %d Tasks'), count($pages)) ?></button>
                        <a href="<?= htmlspecialchars($backUrl) ?>" class="uk-button uk-button-default"><?= __('Cancel') ?></a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div>
        <div class="uk-card uk-card-default vk-bulk-pages-card">
            <div class="uk-card-header vk-card-header-row">
                <h3 class="vk-card-title"><?= __('Pages') ?></h3>
                <span class="vk-card-pager-meta"><?= sprintf(__('%d total'), count($pages)) ?></span>
            </div>
            <div class="vk-scroll-list">
                <?php foreach ($pages as $p): ?>
                <div class="vk-page-pick-row">
                    <?php $d = $this->pageStatusDisplay($p['status'] ?? []); ?>
                    <span class="vk-page-pick-title <?= $d['class'] ?>"<?= $d['label'] !== '' ? ' title="' . htmlspecialchars($d['label']) . '"' : '' ?>><?= $d['icon'] ?><?= htmlspecialchars($p['title']) ?></span>
                    <span class="vk-page-pick-url"><?= htmlspecialchars($p['url']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

</div>

<script>
(function() {
    const picker = document.querySelector('[data-quarter-due-picker]');
    if (!picker) return;
    const input = picker.parentElement.querySelector('[data-quarter-date]');
    const hint = picker.querySelector('[data-quarter-hint]');
    if (!input || !hint) return;
    const emptyText = <?= json_encode(__('Set a date or choose a quarter')) ?>;
    const fiscalStart = Math.max(1, Math.min(12, Number(picker.dataset.quarterStart || 1)));
    const pad = (n) => String(n).padStart(2, '0');
    function quarterEnd(q, fiscalYear) {
        const rawStart = fiscalStart + ((Number(q) - 1) * 3);
        const startMonth = ((rawStart - 1) % 12) + 1;
        const startYear = Number(fiscalYear) + Math.floor((rawStart - 1) / 12);
        const endDate = new Date(startYear, startMonth - 1 + 3, 0);
        return endDate.getFullYear() + '-' + pad(endDate.getMonth() + 1) + '-' + pad(endDate.getDate());
    }
    function label(value) {
        if (!value) return '';
        const parts = value.split('-').map(Number);
        const offset = (parts[1] - fiscalStart + 12) % 12;
        const q = Math.floor(offset / 3) + 1;
        const fiscalYear = parts[1] < fiscalStart ? parts[0] - 1 : parts[0];
        return 'Q' + q + ' ' + fiscalYear;
    }
    function update() {
        const text = label(input.value);
        hint.textContent = text || emptyText;
        picker.classList.toggle('is-active', !!text);
    }
    picker.querySelectorAll('[data-quarter]').forEach(button => {
        button.addEventListener('click', function() {
            const year = (input.value || picker.dataset.year + '-01-01').slice(0, 4);
            input.value = quarterEnd(button.dataset.quarter, year);
            update();
        });
    });
    input.addEventListener('input', update);
    input.addEventListener('change', update);
    update();
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/_layout.php';

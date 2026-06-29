<?php namespace ProcessWire;
/**
 * @var Verk $this
 * @var array $rules
 * @var array $results
 * @var int   $runRule
 * @var array $auditSummary
 */
$url   = $this->page->url;
$csrf  = $this->getCSRFToken();
$csrfN = $this->getCSRFName();
$users   = $this->getAllUsers();
$sprints = $this->getAllSprints();
$currentYear = (int)date('Y');
$auditSummary = $auditSummary ?? [];
$totalGaps = array_sum(array_map(fn($item) => (int)($item['count'] ?? 0), $auditSummary));
$activeRule = ($runRule !== false && isset($rules[$runRule])) ? $rules[$runRule] : null;
$activeCount = isset($results['total']) ? (int)$results['total'] : (isset($results['pages']) ? count($results['pages']) : 0);
$activeStatus = isset($results['error']) ? __('Selector error') : (isset($results['setup']) ? __('Needs setup') : ($activeCount > 0 ? __('Needs work') : __('All clear')));
$auditReturnUrl = $url . '?view=audit' . ($runRule !== false ? '&rule=' . (int)$runRule : '');
$auditReturnParam = rawurlencode($auditReturnUrl);

ob_start();
?>

<div class="vk-page-head">
    <div>
        <h2 class="vk-page-title"><?= __('Content Audit') ?></h2>
        <p><?= __('Find incomplete content by field path and turn gaps into tasks.') ?></p>
    </div>
</div>

<?php if (!$rules): ?>
<div class="vk-empty vk-empty-panel">
    <i class="fa fa-search vk-empty-icon"></i>
    <div class="vk-empty-title"><?= __('No audit rules configured.') ?></div>
    <p class="vk-muted-line"><?= __('Add field-path rules to find content gaps and create tasks from them.') ?></p>
    <div class="vk-empty-actions">
        <a href="<?= $url ?>?view=settings" class="uk-button uk-button-primary uk-button-small"><i class="fa fa-plus"></i> <?= __('Add rules') ?></a>
    </div>
</div>
<?php else: ?>

<div class="vk-audit-summary-grid">
    <div class="vk-audit-summary-card">
        <span><?= __('Rules') ?></span>
        <strong><?= count($rules) ?></strong>
        <small><?= __('Configured selectors') ?></small>
    </div>
    <div class="vk-audit-summary-card <?= $totalGaps > 0 ? 'is-warning' : 'is-success' ?>">
        <span><?= __('Open gaps') ?></span>
        <strong><?= (int)$totalGaps ?></strong>
        <small><?= __('Across all rules') ?></small>
    </div>
    <div class="vk-audit-summary-card">
        <span><?= __('Active rule') ?></span>
        <strong><?= $activeRule ? htmlspecialchars(mb_strimwidth((string)$activeRule['label'], 0, 24, '...')) : '—' ?></strong>
        <small><?= htmlspecialchars($activeStatus) ?></small>
    </div>
    <div class="vk-audit-summary-card">
        <span><?= __('Current result') ?></span>
        <strong><?= (int)$activeCount ?></strong>
        <small><?= __('Matching pages') ?></small>
    </div>
</div>

<ul class="uk-subnav uk-subnav-pill vk-view-switcher vk-audit-rule-tabs">
    <?php foreach ($rules as $i => $rule): ?>
    <li class="<?= $runRule === $i ? 'uk-active' : '' ?>">
        <?php
            $summaryCount = 0;
            foreach ($auditSummary as $summaryItem) {
                if ((int)$summaryItem['index'] === (int)$i) {
                    $summaryCount = (int)$summaryItem['count'];
                    break;
                }
            }
        ?>
        <a href="<?= $url ?>?view=audit&rule=<?= $i ?>"><?= htmlspecialchars($rule['label']) ?> <span class="vk-audit-tab-count"><?= $summaryCount ?></span></a>
    </li>
    <?php endforeach; ?>
</ul>

<?php if (isset($results['error'])): ?>
<div class="vk-audit-notice vk-audit-error"><p><?= sprintf(__('Scope selector error: %s'), htmlspecialchars($results['error'])) ?></p></div>
<?php elseif (isset($results['setup'])): ?>
<div class="vk-audit-notice vk-audit-setup">
    <i class="fa fa-sliders"></i>
    <div>
        <div><?= __('Rule needs a valid field path') ?></div>
        <p><?= $results['setup'] ?></p>
    </div>
</div>
<?php elseif (!empty($results['pages'])): ?>

<form method="post" action="<?= $url ?>" id="vk-bulk-form">
    <input type="hidden" name="<?= $csrfN ?>" value="<?= $csrf ?>">
    <input type="hidden" name="action" value="bulk_audit_tasks">
    <input type="hidden" name="rule_label" value="<?= htmlspecialchars($rules[$runRule]['label'] ?? '') ?>">
    <input type="hidden" name="page_ids" id="vk-page-ids-hidden" value="">
    <input type="hidden" name="return_url" value="<?= htmlspecialchars($auditReturnUrl) ?>">

    <div class="uk-card uk-card-default vk-audit-results-card">
        <div class="uk-card-header vk-card-header-row">
            <div class="vk-inline-actions">
                <input type="checkbox" id="vk-check-all" class="uk-checkbox">
                <label for="vk-check-all"><?= htmlspecialchars($rules[$runRule]['label'] ?? '') ?></label>
                <span class="vk-card-pager-meta vk-inline-meta">
                    <?= sprintf(__('%d pages'), count($results['pages'])) ?><?= ($results['total'] > count($results['pages'])) ? ' ' . sprintf(__('(showing %1$d of %2$d)'), count($results['pages']), $results['total']) : '' ?>
                </span>
            </div>
            <div class="vk-inline-actions">
                <span id="vk-sel-count" class="vk-card-pager-meta vk-inline-meta is-right"></span>
                <a href="<?= $url ?>?view=export-docx&type=audit&rule=<?= $runRule ?>" class="uk-button uk-button-default uk-button-small"><i class="fa fa-file-word-o"></i> <?= __('Export .docx') ?></a>
            </div>
        </div>
        <div class="vk-audit-page-list">
            <?php foreach ($results['pages'] as $p): ?>
            <article class="vk-audit-page-row">
                <div class="vk-audit-page-check">
                    <input type="checkbox" name="vk_page_cb" value="<?= $p['id'] ?>" class="vk-row-check uk-checkbox">
                </div>
                <div class="vk-audit-page-main">
                    <?php $d = $this->pageStatusDisplay($p); ?>
                    <a href="<?= $p['edit'] ?>" target="_blank" class="vk-audit-page-title <?= $d['class'] ?>"<?= $d['label'] !== '' ? ' title="' . htmlspecialchars($d['label']) . '"' : '' ?>><?= $d['icon'] ?><?= htmlspecialchars((string)$p['title']) ?></a>
                    <div class="vk-audit-page-meta">
                        <span><?= htmlspecialchars((string)$p['template']) ?></span>
                        <span><?= htmlspecialchars((string)$p['url']) ?></span>
                    </div>
                </div>
                <div class="vk-audit-page-actions">
                    <a href="<?= $p['edit'] ?>" class="vk-icon-button" target="_blank" title="<?= __('Edit page') ?>" aria-label="<?= __('Edit page') ?>"><i class="fa fa-pencil-square-o"></i></a>
                    <a href="<?= $url ?>?view=task-edit&page_id=<?= (int)$p['id'] ?>&return_url=<?= $auditReturnParam ?>" class="vk-icon-button" title="<?= __('Create task') ?>" aria-label="<?= __('Create task') ?>"><i class="fa fa-plus"></i></a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Bulk action panel -->
    <div id="vk-bulk-panel" class="uk-card uk-card-default vk-card-stack vk-bulk-panel" hidden>
        <div class="uk-card-header">
            <span><i class="fa fa-tasks"></i> <?= __('Create tasks for selected pages') ?></span>
            <span id="vk-bulk-count" class="vk-card-pager-meta vk-inline-meta"></span>
        </div>
        <div class="uk-card-body">
            <div class="vk-form-grid is-2">
                <div>
                    <div class="vk-field">
                        <label class="uk-form-label"><?= __('Title Template') ?> <span class="vk-inline-note"><code>{page_title}</code> = <?= __('page name') ?></span></label>
                        <input type="text" name="title_template" value="{page_title}" placeholder="<?= __('{page_title} = page name, e.g. Fix: {page_title}') ?>" class="uk-input">
                    </div>
                </div>
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
            </div>
            <div class="vk-form-grid is-3">
                <div>
                    <div class="vk-field">
                        <label class="uk-form-label"><?= __('Priority') ?></label>
                        <select name="priority" class="uk-select">
                            <option value="medium"><?= htmlspecialchars($this->priorityLabel('medium')) ?></option>
                            <option value="high"><?= htmlspecialchars($this->priorityLabel('high')) ?></option>
                            <option value="low"><?= htmlspecialchars($this->priorityLabel('low')) ?></option>
                            <option value="critical"><?= htmlspecialchars($this->priorityLabel('critical')) ?></option>
                        </select>
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
                <div>
                    <div class="vk-field">
                        <label class="uk-form-label"><?= __('Sprint') ?></label>
                        <select name="sprint_id" class="uk-select">
                            <option value="">&mdash; <?= __('No sprint') ?> &mdash;</option>
                            <?php foreach ($sprints as $sp): ?>
                            <option value="<?= $sp['id'] ?>" <?= $sp['status']==='active' ? 'selected' : '' ?>><?= htmlspecialchars($sp['name']) ?><?= $sp['status']==='active' ? ' &#9733;' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="vk-field">
                <button type="submit" class="uk-button uk-button-primary"><i class="fa fa-plus"></i> <?= __('Create Tasks') ?></button>
            </div>
        </div>
    </div>
</form>

<script>
	(function() {
    const checkAll  = document.getElementById('vk-check-all');
    const checks    = document.querySelectorAll('[name="vk_page_cb"]');
    const panel     = document.getElementById('vk-bulk-panel');
    const bulkCount = document.getElementById('vk-bulk-count');
    const selCount  = document.getElementById('vk-sel-count');
    const T = {
        pageSel:  <?= json_encode(__('page selected')) ?>,
        pagesSel: <?= json_encode(__('pages selected')) ?>,
        selected: <?= json_encode(__('selected')) ?>
    };

    function update() {
        const sel = document.querySelectorAll('[name="vk_page_cb"]:checked').length;
        panel.hidden = sel <= 0;
        bulkCount.textContent = sel + ' ' + (sel !== 1 ? T.pagesSel : T.pageSel);
        selCount.textContent  = sel > 0 ? sel + ' ' + T.selected : '';
        checkAll.indeterminate = sel > 0 && sel < checks.length;
        checkAll.checked = sel === checks.length;
    }

    checkAll.addEventListener('change', function() {
        checks.forEach(c => c.checked = this.checked);
        update();
    });
    checks.forEach(c => c.addEventListener('change', update));

    document.getElementById('vk-bulk-form').addEventListener('submit', function() {
        const ids = Array.from(document.querySelectorAll('.vk-row-check:checked')).map(c => c.value);
        document.getElementById('vk-page-ids-hidden').value = ids.join(',');
    });
	})();
	</script>

    <script>
    (function() {
        document.querySelectorAll('[data-quarter-due-picker]').forEach(picker => {
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
        });
    })();
    </script>

	<?php elseif ($runRule !== false): ?>
<div class="vk-empty vk-empty-panel">
    <i class="fa fa-check-circle-o vk-empty-icon is-success"></i>
    <div class="vk-empty-title"><?= __('All clear') ?></div>
    <p class="vk-muted-line"><?= __('No pages match this audit rule.') ?></p>
</div>
<?php endif; ?>

<div class="uk-card uk-card-default vk-card-stack vk-audit-config">
    <div class="uk-card-header"><h3 class="vk-card-title"><?= __('Audit Rules') ?></h3></div>
    <div class="uk-card-body">
        <div class="vk-audit-rule-format">
            <span><?= __('One rule per line') ?></span>
            <code><?= __('Label | Scope selector | Field path | Message | Users') ?></code>
        </div>
        <form method="post" action="<?= $url ?>">
            <input type="hidden" name="<?= $csrfN ?>" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="save_audit_rules">
            <div class="vk-field">
                <textarea name="audit_rules" class="uk-textarea vk-audit-rules"><?php
foreach ($rules as $r) {
    $users = implode(',', $r['users'] ?? []);
    echo htmlspecialchars($r['label'] . ' | ' . $r['selector'] . ' | ' . ($r['field'] ?? '') . ' | ' . ($r['message'] ?? '') . ' | ' . $users) . "\n";
}
?></textarea>
            </div>
            <div class="vk-actions">
                <button type="submit" class="uk-button uk-button-primary"><?= __('Save Rules') ?></button>
            </div>
        </form>
        <div class="vk-audit-reference">
            <div class="vk-audit-reference-title"><?= __('Dot notation') ?></div>
            <code>Products without images | template=product | images | No product images</code>
            <code>Empty city | template=location | address.city | City is missing</code>
            <code>Missing hero title | template=page | blocks.hero.title | Hero title is empty</code>
            <code>Missing table amount | template=product | prices.*.amount | Price amount is empty</code>
            <code>Needs clinical review | template=message | | Needs clinical review | john-smith,jane-brown</code>
        </div>
    </div>
</div>

<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/_layout.php';

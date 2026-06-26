<?php namespace ProcessWire;
/**
 * @var Verk $this
 * @var array $config
 * @var array $settingsStats
 */
$url    = $this->page->url;
$csrf   = $this->getCSRFToken();
$csrfN  = $this->getCSRFName();
$cfg    = $config;

$allTemplates = [];
foreach ($this->wire('templates') as $t) {
    if (strpos($t->name, 'admin') !== false || $t->flags & Template::flagSystem) continue;
    $allTemplates[] = $t->name;
}
sort($allTemplates);

$dateFields = [];
foreach ($this->wire('fields') as $f) {
    if (in_array($f->type->className(), ['FieldtypeDate','FieldtypeDatetime'])) {
        $dateFields[] = $f->name;
    }
}
sort($dateFields);

$allRoles = [];
foreach ($this->wire('roles') as $role) {
    if ($role->name === 'guest') continue;
    $allRoles[] = $role->name;
}
sort($allRoles);

$calendarTemplates = array_values(array_filter(array_map('trim', explode(',', (string)$cfg['calendar_template']))));
$calendarDateField = trim((string)$cfg['calendar_date_field']);
$calendarReady = $calendarTemplates && $calendarDateField;
$calendarStatus = $calendarReady ? __('Configured') : __('Needs setup');
$calendarStatusClass = $calendarReady ? 'vk-label-done' : 'vk-label-open';
$quarterStartMonth = max(1, min(12, (int)($cfg['quarter_start_month'] ?? 1)));
$assigneeRoles = trim((string)($cfg['assignee_roles'] ?? ''));
$widgetEnabled = !empty($cfg['page_widget_enabled']);
$widgetStatus = $widgetEnabled ? __('Enabled') : __('Disabled');
$widgetStatusClass = $widgetEnabled ? 'vk-label-done' : 'vk-label-open';
$checked = static fn($value): string => !empty($value) ? ' checked' : '';
$settingsStats = $settingsStats ?? ['open_tasks' => 0, 'active_sprints' => 0, 'notes' => 0, 'audit_rules' => 0];

ob_start();
?>

<div class="vk-page-head">
    <div>
        <h2 class="vk-page-title"><?= __('Settings') ?></h2>
        <p><?= __('Configure the Verk calendar and page editor integration.') ?></p>
    </div>
</div>

<div class="vk-settings-overview">
    <a href="<?= $url ?>?view=calendar" class="vk-settings-overview-card <?= $calendarReady ? 'is-ready' : 'is-warning' ?>">
        <span><?= __('Calendar') ?></span>
        <strong><?= htmlspecialchars($calendarStatus) ?></strong>
        <small><?= $calendarReady ? htmlspecialchars(implode(', ', $calendarTemplates)) : __('Publication source missing') ?></small>
    </a>
    <a href="<?= $url ?>?view=tasks" class="vk-settings-overview-card">
        <span><?= __('Open tasks') ?></span>
        <strong><?= (int)$settingsStats['open_tasks'] ?></strong>
        <small><?= __('Operational workload') ?></small>
    </a>
    <a href="<?= $url ?>?view=sprints&status=active" class="vk-settings-overview-card">
        <span><?= __('Active sprints') ?></span>
        <strong><?= (int)$settingsStats['active_sprints'] ?></strong>
        <small><?= __('Running delivery windows') ?></small>
    </a>
    <a href="<?= $url ?>?view=audit" class="vk-settings-overview-card">
        <span><?= __('Audit rules') ?></span>
        <strong><?= (int)$settingsStats['audit_rules'] ?></strong>
        <small><?= __('Configured checks') ?></small>
    </a>
    <a href="<?= $url ?>?view=kb" class="vk-settings-overview-card">
        <span><?= __('KB notes') ?></span>
        <strong><?= (int)$settingsStats['notes'] ?></strong>
        <small><?= __('Reusable knowledge') ?></small>
    </a>
    <div class="vk-settings-overview-card <?= $widgetEnabled ? 'is-ready' : 'is-muted' ?>" data-vk-widget-overview>
        <span><?= __('Page widget') ?></span>
        <strong data-vk-widget-overview-status><?= htmlspecialchars($widgetStatus) ?></strong>
        <small data-vk-widget-overview-detail><?= htmlspecialchars(ucfirst((string)($cfg['page_widget_position'] ?? 'top'))) ?> · <?= (int)$cfg['page_widget_limit'] ?> <?= __('tasks') ?></small>
    </div>
</div>

<div class="vk-settings-grid">
    <div class="uk-card uk-card-default vk-settings-card vk-form-compact is-primary">
        <div class="uk-card-header">
            <h3 class="vk-card-title"><?= __('Editorial Calendar') ?></h3>
            <span class="uk-label <?= $calendarStatusClass ?>"><?= $calendarStatus ?></span>
        </div>
        <div class="uk-card-body">
            <p class="vk-settings-intro"><?= __('Controls which ProcessWire pages appear as publication dates. Task deadlines are shown on the calendar even when this source is not configured.') ?></p>

            <div class="vk-settings-summary">
                <div>
                    <span><?= __('Templates') ?></span>
                    <strong><?= $calendarTemplates ? htmlspecialchars(implode(', ', $calendarTemplates)) : __('Not set') ?></strong>
                </div>
                <div>
                    <span><?= __('Date field') ?></span>
                    <strong><?= $calendarDateField ? htmlspecialchars($calendarDateField) : __('Not set') ?></strong>
                </div>
            </div>

            <form method="post" action="<?= $url ?>">
                <input type="hidden" name="<?= $csrfN ?>" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="save_settings">

                <div class="vk-field">
                    <label class="uk-form-label"><?= __('Template(s) for calendar') ?> <span class="vk-inline-note"><?= __('(comma-separated)') ?></span></label>
                    <input type="text" name="calendar_template"
                        value="<?= htmlspecialchars($cfg['calendar_template']) ?>"
                        placeholder="<?= __('e.g. post, article') ?>"
                        class="uk-input"
                        list="vk-templates-list">
                    <div class="vk-field-help"><?= __('Use template names, not labels. Multiple templates are separated with commas.') ?></div>
                    <datalist id="vk-templates-list">
                        <?php foreach ($allTemplates as $t): ?><option value="<?= $t ?>"><?php endforeach; ?>
                    </datalist>
                </div>

                <div class="vk-field">
                    <label class="uk-form-label"><?= __('Date field') ?></label>
                    <?php if ($dateFields): ?>
                    <select name="calendar_date_field" class="uk-select">
                        <option value="">&mdash; <?= __('Select field') ?> &mdash;</option>
                        <?php foreach ($dateFields as $f): ?>
                        <option value="<?= $f ?>" <?= $cfg['calendar_date_field']===$f?'selected':'' ?>><?= $f ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="vk-field-help"><?= __('The selected field decides the calendar day for each matching page.') ?></div>
                    <?php else: ?>
                    <input type="text" name="calendar_date_field" value="<?= htmlspecialchars($cfg['calendar_date_field']) ?>" placeholder="<?= __('e.g. publish_date') ?>" class="uk-input">
                    <div class="vk-field-help"><?= __('No Date/Datetime fields found. Enter field name manually.') ?></div>
                    <?php endif; ?>
                </div>

                <div class="vk-field">
                    <label class="uk-form-label"><?= __('Quarter year starts') ?></label>
                    <select name="quarter_start_month" class="uk-select">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $quarterStartMonth === $m ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                        <?php endfor; ?>
                    </select>
                    <div class="vk-field-help"><?= __('All Q1-Q4 labels, dashboard filters, task due quarters, and sprint quarters use this fiscal start month.') ?></div>
                </div>

                <div class="vk-settings-examples">
                    <span><?= __('Examples') ?></span>
                    <code>post, article</code>
                    <code>publish_date</code>
                </div>

                <div class="vk-form-actions">
                    <button type="submit" class="uk-button uk-button-primary"><?= __('Save Settings') ?></button>
                </div>
            </form>
        </div>
    </div>

    <div class="vk-settings-side">
        <div class="uk-card uk-card-default vk-settings-card">
            <div class="uk-card-header">
                <h3 class="vk-card-title"><?= __('Content Audit Rules') ?></h3>
                <span class="vk-settings-card-note"><?= __('Managed on audit page') ?></span>
            </div>
            <div class="uk-card-body">
                <p class="vk-settings-intro"><?= __('Audit rules live on the audit screen so you can edit and test them in the same place.') ?></p>
                <div class="vk-settings-mini-list">
                    <div><i class="fa fa-check"></i> <?= __('Supports ProcessWire selectors') ?></div>
                    <div><i class="fa fa-check"></i> <?= __('Supports dot-notation field paths') ?></div>
                    <div><i class="fa fa-check"></i> <?= __('Creates tasks from audit results') ?></div>
                </div>
                <a href="<?= $url ?>?view=audit" class="uk-button uk-button-default"><i class="fa fa-search"></i> <?= __('Open Audit') ?></a>
            </div>
        </div>

        <div class="uk-card uk-card-default vk-settings-card">
            <div class="uk-card-header">
                <h3 class="vk-card-title"><?= __('Task Assignment') ?></h3>
                <span class="vk-settings-card-note"><?= $assigneeRoles ? htmlspecialchars($assigneeRoles) : __('All non-guest users') ?></span>
            </div>
            <div class="uk-card-body">
                <p class="vk-settings-intro"><?= __('Limit assignee dropdowns and filters to ProcessWire users with selected roles. Existing task assignees remain visible when editing or filtering those tasks.') ?></p>
                <form method="post" action="<?= $url ?>">
                    <input type="hidden" name="<?= $csrfN ?>" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="save_settings">

                    <div class="vk-field">
                        <label class="uk-form-label"><?= __('Assignee roles') ?> <span class="vk-inline-note"><?= __('(comma-separated)') ?></span></label>
                        <input type="text" name="assignee_roles"
                            value="<?= htmlspecialchars($assigneeRoles) ?>"
                            placeholder="<?= __('e.g. editor, superuser') ?>"
                            class="uk-input"
                            list="vk-assignee-roles-list">
                        <div class="vk-field-help"><?= __('Leave empty to allow every non-guest user. Use role names, not labels.') ?></div>
                        <datalist id="vk-assignee-roles-list">
                            <?php foreach ($allRoles as $roleName): ?><option value="<?= htmlspecialchars($roleName) ?>"><?php endforeach; ?>
                        </datalist>
                    </div>

                    <div class="vk-form-actions">
                        <button type="submit" class="uk-button uk-button-primary"><?= __('Save Assignment') ?></button>
                    </div>
                </form>
            </div>
        </div>

        <div class="uk-card uk-card-default vk-settings-card">
            <div class="uk-card-header">
                <h3 class="vk-card-title"><?= __('Page Editor Widget') ?></h3>
                <span class="uk-label <?= $widgetStatusClass ?>"><?= $widgetStatus ?></span>
            </div>
            <div class="uk-card-body">
                <p class="vk-settings-intro"><?= __('Verk automatically adds a collapsed task widget to every page editor. Tasks linked to that page appear there with a direct link to create a new task.') ?></p>
                <div class="vk-widget-config-layout">
                    <div>
                <form method="post" action="<?= $url ?>" class="vk-widget-settings-form" id="vk-widget-settings-form">
                    <input type="hidden" name="<?= $csrfN ?>" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="save_settings">
                    <input type="hidden" name="calendar_template" value="<?= htmlspecialchars($cfg['calendar_template']) ?>">
                    <input type="hidden" name="calendar_date_field" value="<?= htmlspecialchars($cfg['calendar_date_field']) ?>">
                    <input type="hidden" name="quarter_start_month" value="<?= $quarterStartMonth ?>">

                    <div class="vk-settings-options">
                        <label><input type="hidden" name="page_widget_enabled" value="0"><input type="checkbox" name="page_widget_enabled" value="1"<?= $checked($cfg['page_widget_enabled']) ?>><span><strong><?= __('Show widget') ?></strong><small><?= __('Display Verk tasks in the page editor') ?></small></span></label>
                        <label><input type="hidden" name="page_widget_collapsed" value="0"><input type="checkbox" name="page_widget_collapsed" value="1"<?= $checked($cfg['page_widget_collapsed']) ?>><span><strong><?= __('Collapsed by default') ?></strong><small><?= __('Keep the editor compact until opened') ?></small></span></label>
                        <label><input type="hidden" name="page_widget_show_done" value="0"><input type="checkbox" name="page_widget_show_done" value="1"<?= $checked($cfg['page_widget_show_done']) ?>><span><strong><?= __('Include done tasks') ?></strong><small><?= __('Show completed work in the widget') ?></small></span></label>
                        <label><input type="hidden" name="page_widget_show_create" value="0"><input type="checkbox" name="page_widget_show_create" value="1"<?= $checked($cfg['page_widget_show_create']) ?>><span><strong><?= __('Create task link') ?></strong><small><?= __('Let editors add a task from the page') ?></small></span></label>
                        <label><input type="hidden" name="page_widget_show_empty" value="0"><input type="checkbox" name="page_widget_show_empty" value="1"<?= $checked($cfg['page_widget_show_empty']) ?>><span><strong><?= __('Empty state') ?></strong><small><?= __('Explain when a page has no tasks') ?></small></span></label>
                    </div>

                    <div class="vk-settings-field-grid">
                        <div>
                            <label class="uk-form-label"><?= __('Position') ?></label>
                            <select name="page_widget_position" class="uk-select">
                                <option value="top" <?= ($cfg['page_widget_position'] ?? 'top') === 'top' ? 'selected' : '' ?>><?= __('Top of editor') ?></option>
                                <option value="bottom" <?= ($cfg['page_widget_position'] ?? 'top') === 'bottom' ? 'selected' : '' ?>><?= __('Bottom of editor') ?></option>
                            </select>
                        </div>
                        <div>
                            <label class="uk-form-label"><?= __('Sort by') ?></label>
                            <select name="page_widget_sort" class="uk-select">
                                <option value="status" <?= ($cfg['page_widget_sort'] ?? 'status') === 'status' ? 'selected' : '' ?>><?= __('Status') ?></option>
                                <option value="due" <?= ($cfg['page_widget_sort'] ?? 'status') === 'due' ? 'selected' : '' ?>><?= __('Due date') ?></option>
                                <option value="newest" <?= ($cfg['page_widget_sort'] ?? 'status') === 'newest' ? 'selected' : '' ?>><?= __('Newest') ?></option>
                                <option value="priority" <?= ($cfg['page_widget_sort'] ?? 'status') === 'priority' ? 'selected' : '' ?>><?= __('Priority') ?></option>
                            </select>
                        </div>
                        <div>
                            <label class="uk-form-label"><?= __('Task limit') ?></label>
                            <input type="number" name="page_widget_limit" class="uk-input" min="1" max="50" value="<?= (int)$cfg['page_widget_limit'] ?>">
                        </div>
                    </div>

                    <div class="vk-settings-subtitle"><?= __('Task fields') ?></div>
                    <div class="vk-settings-options is-compact">
                        <label><input type="hidden" name="page_widget_show_status" value="0"><input type="checkbox" name="page_widget_show_status" value="1"<?= $checked($cfg['page_widget_show_status']) ?>><span><?= __('Status') ?></span></label>
                        <label><input type="hidden" name="page_widget_show_priority" value="0"><input type="checkbox" name="page_widget_show_priority" value="1"<?= $checked($cfg['page_widget_show_priority']) ?>><span><?= __('Priority') ?></span></label>
                        <label><input type="hidden" name="page_widget_show_due_date" value="0"><input type="checkbox" name="page_widget_show_due_date" value="1"<?= $checked($cfg['page_widget_show_due_date']) ?>><span><?= __('Due date') ?></span></label>
                        <label><input type="hidden" name="page_widget_show_quarter" value="0"><input type="checkbox" name="page_widget_show_quarter" value="1"<?= $checked($cfg['page_widget_show_quarter']) ?>><span><?= __('Quarter') ?></span></label>
                        <label><input type="hidden" name="page_widget_show_assignee" value="0"><input type="checkbox" name="page_widget_show_assignee" value="1"<?= $checked($cfg['page_widget_show_assignee']) ?>><span><?= __('Assignee') ?></span></label>
                    </div>

                    <div class="vk-form-actions">
                        <button type="submit" class="uk-button uk-button-primary"><?= __('Save Widget') ?></button>
                    </div>
                </form>

                <form method="post" action="<?= $url ?>" class="vk-widget-settings-form" id="vk-notify-settings-form" style="margin-top:24px">
                    <input type="hidden" name="<?= $csrfN ?>" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="save_settings">

                    <div class="vk-settings-subtitle"><?= __('Email notifications') ?></div>
                    <p class="vk-settings-intro"><?= __('Email users when they are added to a task as assignee, collaborator, or reviewer.') ?></p>
                    <div class="vk-settings-options">
                        <label><input type="hidden" name="notify_enabled" value="0"><input type="checkbox" name="notify_enabled" value="1"<?= $checked($cfg['notify_enabled']) ?>><span><strong><?= __('Enable notifications') ?></strong><small><?= __('Master switch for all task membership emails') ?></small></span></label>
                        <label><input type="hidden" name="notify_assignee" value="0"><input type="checkbox" name="notify_assignee" value="1"<?= $checked($cfg['notify_assignee']) ?>><span><strong><?= __('Assignee') ?></strong><small><?= __('Email when set as a task assignee') ?></small></span></label>
                        <label><input type="hidden" name="notify_collaborator" value="0"><input type="checkbox" name="notify_collaborator" value="1"<?= $checked($cfg['notify_collaborator']) ?>><span><strong><?= __('Collaborator') ?></strong><small><?= __('Email when added as a collaborator') ?></small></span></label>
                        <label><input type="hidden" name="notify_reviewer" value="0"><input type="checkbox" name="notify_reviewer" value="1"<?= $checked($cfg['notify_reviewer']) ?>><span><strong><?= __('Reviewer') ?></strong><small><?= __('Email when added as a reviewer') ?></small></span></label>
                    </div>

                    <div class="vk-form-actions">
                        <button type="submit" class="uk-button uk-button-primary"><?= __('Save Notifications') ?></button>
                    </div>
                </form>
                    </div>

                <div class="vk-widget-preview" id="vk-widget-preview"
                    data-user="<?= htmlspecialchars($this->wire('user')->name) ?>"
                    data-today="<?= htmlspecialchars(date('M j')) ?>"
                    data-quarter="<?= htmlspecialchars($this->quarterLabelForDate(date('Y-m-d'))) ?>"
                    data-enabled-label="<?= htmlspecialchars(__('Enabled')) ?>"
                    data-disabled-label="<?= htmlspecialchars(__('Disabled')) ?>"
                    data-top-label="<?= htmlspecialchars(__('Top of editor')) ?>"
                    data-bottom-label="<?= htmlspecialchars(__('Bottom of editor')) ?>"
                    data-tasks-label="<?= htmlspecialchars(__('tasks')) ?>"
                    data-includes-done-label="<?= htmlspecialchars(__('includes done')) ?>"
                    data-hides-done-label="<?= htmlspecialchars(__('hides done')) ?>"
                    data-sort-status-label="<?= htmlspecialchars(__('sort by status')) ?>"
                    data-sort-due-label="<?= htmlspecialchars(__('sort by due date')) ?>"
                    data-sort-newest-label="<?= htmlspecialchars(__('newest first')) ?>"
                    data-sort-priority-label="<?= htmlspecialchars(__('sort by priority')) ?>">
                    <div class="vk-settings-subtitle"><?= __('Preview') ?></div>
                    <div class="vk-widget-preview-box">
                        <div class="vk-widget-preview-head">
                            <span><?= __('Verk Tasks') ?></span>
                            <a href="#" data-vk-preview-create><?= __('+ New task for this page') ?></a>
                        </div>
                        <div class="vk-widget-preview-collapsed" data-vk-preview-collapsed><?= __('Collapsed in page editor by default') ?></div>
                        <div class="vk-widget-preview-row">
                            <span class="vk-widget-preview-dot"></span>
                            <span class="vk-widget-preview-title"><?= __('Update product copy') ?></span>
                            <span class="vk-widget-preview-meta" data-vk-preview-meta></span>
                        </div>
                        <div class="vk-widget-preview-empty" data-vk-preview-empty><?= __('No tasks for this page.') ?></div>
                        <div class="vk-widget-preview-disabled" data-vk-preview-disabled><?= __('Widget is disabled and will not appear in page editors.') ?></div>
                        <div class="vk-widget-preview-placement" data-vk-preview-placement></div>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const form = document.getElementById('vk-widget-settings-form');
    const preview = document.getElementById('vk-widget-preview');
    if (!form || !preview) return;

    const statusBadge = form.closest('.vk-settings-card')?.querySelector('.uk-card-header .uk-label');
    const meta = preview.querySelector('[data-vk-preview-meta]');
    const create = preview.querySelector('[data-vk-preview-create]');
    const collapsed = preview.querySelector('[data-vk-preview-collapsed]');
    const row = preview.querySelector('.vk-widget-preview-row');
    const empty = preview.querySelector('[data-vk-preview-empty]');
    const disabled = preview.querySelector('[data-vk-preview-disabled]');
    const placement = preview.querySelector('[data-vk-preview-placement]');
    const overview = document.querySelector('[data-vk-widget-overview]');
    const overviewStatus = document.querySelector('[data-vk-widget-overview-status]');
    const overviewDetail = document.querySelector('[data-vk-widget-overview-detail]');

    const field = (name) => form.elements[name];
    const checkbox = (name) => form.querySelector('input[type="checkbox"][name="' + name + '"]');
    const checked = (name) => {
        const el = checkbox(name);
        return !!(el && el.checked);
    };

    function setHidden(el, value) {
        if (el) el.hidden = !!value;
    }

    function updatePreview() {
        const enabled = checked('page_widget_enabled');
        const showEmpty = checked('page_widget_show_empty');
        const position = field('page_widget_position')?.value || 'top';
        const sort = field('page_widget_sort')?.value || 'status';
        const limit = field('page_widget_limit')?.value || '10';

        const parts = [];
        if (checked('page_widget_show_status')) parts.push('Open');
        if (checked('page_widget_show_priority')) parts.push('High');
        if (checked('page_widget_show_due_date')) parts.push(preview.dataset.today || '');
        if (checked('page_widget_show_quarter')) parts.push(preview.dataset.quarter || '');
        if (checked('page_widget_show_assignee')) parts.push(preview.dataset.user || '');

        if (meta) meta.textContent = parts.filter(Boolean).join(' · ');
        setHidden(create, !checked('page_widget_show_create'));
        setHidden(collapsed, !checked('page_widget_collapsed') || !enabled);
        setHidden(row, !enabled);
        setHidden(empty, !enabled || !showEmpty);
        setHidden(disabled, enabled);
        if (placement) {
            const sortLabels = {
                status: preview.dataset.sortStatusLabel,
                due: preview.dataset.sortDueLabel,
                newest: preview.dataset.sortNewestLabel,
                priority: preview.dataset.sortPriorityLabel
            };
            const doneLabel = checked('page_widget_show_done') ? preview.dataset.includesDoneLabel : preview.dataset.hidesDoneLabel;
            const positionLabel = position === 'bottom' ? preview.dataset.bottomLabel : preview.dataset.topLabel;
            placement.textContent = positionLabel + ' · up to ' + limit + ' ' + preview.dataset.tasksLabel + ' · ' + (sortLabels[sort] || sortLabels.status) + ' · ' + doneLabel;
            placement.hidden = !enabled;
        }

        preview.classList.toggle('is-disabled', !enabled);
        preview.classList.toggle('is-collapsed', checked('page_widget_collapsed') && enabled);

        if (statusBadge) {
            statusBadge.textContent = enabled ? 'Enabled' : 'Disabled';
            statusBadge.classList.toggle('vk-label-done', enabled);
            statusBadge.classList.toggle('vk-label-open', !enabled);
        }

        if (overview) {
            const positionLabel = position === 'bottom' ? preview.dataset.bottomLabel : preview.dataset.topLabel;
            overview.classList.toggle('is-ready', enabled);
            overview.classList.toggle('is-muted', !enabled);
            if (overviewStatus) overviewStatus.textContent = enabled ? preview.dataset.enabledLabel : preview.dataset.disabledLabel;
            if (overviewDetail) overviewDetail.textContent = positionLabel + ' · ' + limit + ' ' + preview.dataset.tasksLabel;
        }

        form.querySelectorAll('.vk-settings-options label').forEach(label => {
            const box = label.querySelector('input[type="checkbox"]');
            label.classList.toggle('is-checked', !!(box && box.checked));
        });
    }

    form.addEventListener('change', updatePreview);
    form.addEventListener('input', updatePreview);
    updatePreview();
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/_layout.php';

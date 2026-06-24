<?php namespace ProcessWire;
/**
 * @var Verk $this
 * @var array|null $sprint
 * @var array $tasks
 */
$url    = $this->page->url;
$csrf   = $this->getCSRFToken();
$csrfN  = $this->getCSRFName();
$isEdit = !empty($sprint);
$s      = $sprint ?? ['name'=>'','status'=>'planned','start_date'=>'','end_date'=>'','goal'=>''];
$today  = date('Y-m-d');
$returnUrl = $this->safeLocalUrl((string)$this->wire('input')->get('return_url', 'string'));
$prefillQuarter = (int)$this->wire('input')->get('plan_quarter');
$prefillYear = (int)$this->wire('input')->get('plan_year');
if ($prefillQuarter >= 1 && $prefillQuarter <= 4 && !$s['start_date'] && !$s['end_date']) {
    $ctx = $this->quarterContext($prefillQuarter, $prefillYear ?: (int)date('Y'));
    $s['start_date'] = $ctx['start'];
    $s['end_date'] = $ctx['end'];
}

$totalSP     = array_sum(array_column($tasks, 'story_points'));
$totalEst    = array_sum(array_column($tasks, 'estimate_h'));
$totalActual = array_sum(array_column($tasks, 'actual_h'));
$doneTasks   = count(array_filter($tasks, fn($t) => $t['status'] === 'done'));
$remainingTasks = max(0, count($tasks) - $doneTasks);
$donePercent = count($tasks) > 0 ? (int)round($doneTasks / count($tasks) * 100) : 0;
$sprintQuarterLabel = $this->quarterLabelForRange($s['start_date'] ?? '', $s['end_date'] ?? '');
$sprintDateText = __('No dates set');
if (!empty($s['start_date']) && !empty($s['end_date'])) {
    $sprintDateText = date('M j', strtotime($s['start_date'])) . ' - ' . date('M j, Y', strtotime($s['end_date']));
} elseif (!empty($s['start_date'])) {
    $sprintDateText = sprintf(__('Starts %s'), date('M j, Y', strtotime($s['start_date'])));
} elseif (!empty($s['end_date'])) {
    $sprintDateText = sprintf(__('Ends %s'), date('M j, Y', strtotime($s['end_date'])));
}
$sprintScheduleState = __('Unscheduled');
if (!empty($s['start_date']) && !empty($s['end_date'])) {
    if ($s['end_date'] < $today) $sprintScheduleState = __('Past');
    elseif ($s['start_date'] > $today) $sprintScheduleState = __('Upcoming');
    else $sprintScheduleState = __('In window');
}
$taskDueInWindow = 0;
$taskDueMissing = 0;
$taskDueOutside = 0;
$hasSprintWindow = !empty($s['start_date']) && !empty($s['end_date']);
foreach ($tasks as $taskRow) {
    $taskDue = (string)($taskRow['due_date'] ?? '');
    if ($taskDue === '') {
        $taskDueMissing++;
    } elseif ($hasSprintWindow && $taskDue >= $s['start_date'] && $taskDue <= $s['end_date']) {
        $taskDueInWindow++;
    } elseif ($hasSprintWindow) {
        $taskDueOutside++;
    }
}
$readinessItems = [
    [
        'key' => 'schedule',
        'label' => __('Schedule'),
        'done' => !empty($s['start_date']) && !empty($s['end_date']),
        'hint' => !empty($s['start_date']) && !empty($s['end_date']) ? $sprintDateText : __('Add start and end dates'),
    ],
    [
        'key' => 'goal',
        'label' => __('Goal'),
        'done' => trim(strip_tags((string)($s['goal'] ?? ''))) !== '',
        'hint' => trim(strip_tags((string)($s['goal'] ?? ''))) !== '' ? __('Goal written') : __('Write the sprint outcome'),
    ],
    [
        'key' => 'tasks',
        'label' => __('Tasks'),
        'done' => count($tasks) > 0,
        'hint' => count($tasks) > 0 ? sprintf(__('%d tasks attached'), count($tasks)) : __('Attach tasks to the sprint'),
    ],
    [
        'key' => 'estimate',
        'label' => __('Estimate'),
        'done' => (float)$totalEst > 0,
        'hint' => (float)$totalEst > 0 ? number_format((float)$totalEst, 1) . 'h' : __('Add estimates to tasks'),
    ],
];
$readinessDone = count(array_filter($readinessItems, fn($item) => !empty($item['done'])));
$readinessPercent = (int)round($readinessDone / max(1, count($readinessItems)) * 100);
$currentYear = (int)date('Y');
$sprintReturnUrl = $isEdit ? ($url . '?view=sprint-edit&id=' . (int)$s['id'] . '#vk-sprint-tasks') : '';

ob_start();
?>

<div class="vk-page-head">
    <div>
        <h2 class="vk-page-title"><?= $isEdit ? __('Edit Sprint') : __('New Sprint') ?></h2>
        <p><?= $isEdit ? __('Update the sprint window, goal, and related task plan.') : __('Create a sprint to group tasks around a delivery window.') ?></p>
    </div>
    <?php if ($isEdit || $returnUrl): ?>
    <div class="vk-actions">
        <?php if ($returnUrl): ?>
        <a href="<?= htmlspecialchars($returnUrl) ?>" class="uk-button uk-button-default uk-button-small"><i class="fa fa-arrow-left"></i> <?= __('Back to Sprints') ?></a>
        <?php endif; ?>
        <?php if ($isEdit): ?>
        <a href="<?= $url ?>?view=export-docx&type=sprint&id=<?= $s['id'] ?>" class="uk-button uk-button-default uk-button-small"><i class="fa fa-file-word-o"></i> <?= __('Export .docx') ?></a>
        <form method="post" action="<?= $url ?>" onsubmit="return confirm('<?= __('Delete sprint?') ?>')">
            <input type="hidden" name="<?= $csrfN ?>" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="delete_sprint">
            <input type="hidden" name="id" value="<?= $s['id'] ?>">
            <?php if ($returnUrl): ?><input type="hidden" name="return_url" value="<?= htmlspecialchars($returnUrl) ?>"><?php endif; ?>
            <button class="uk-button uk-button-danger uk-button-small"><i class="fa fa-trash"></i> <?= __('Delete') ?></button>
        </form>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<div class="<?= $isEdit ? 'vk-task-layout vk-sprint-workspace has-sidebar' : 'vk-task-layout vk-sprint-create' ?>">
    <div class="vk-sprint-main">
        <div class="uk-card uk-card-default vk-task-card vk-form-compact">
            <div class="uk-card-header">
                <h3 class="vk-card-title"><?= __('Sprint Details') ?></h3>
                <p><?= __('Use a clear sprint name and a short outcome-oriented goal.') ?></p>
            </div>
            <div class="uk-card-body">
                <form method="post" action="<?= $url ?>">
                    <input type="hidden" name="<?= $csrfN ?>" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="save_sprint">
                    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= $s['id'] ?>"><?php endif; ?>
                    <?php if ($returnUrl): ?><input type="hidden" name="return_url" value="<?= htmlspecialchars($returnUrl) ?>"><?php endif; ?>

                    <div class="vk-form-section">
                        <div class="vk-form-section-title"><?= __('Basics') ?></div>
                        <div class="vk-field">
                            <label class="uk-form-label"><?= __('Sprint Name') ?> *</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($s['name']) ?>" required autofocus placeholder="<?= __('e.g. Sprint 3 – Product Catalog') ?>" class="uk-input">
                        </div>

                        <div class="vk-field">
                            <label class="uk-form-label"><?= __('Status') ?></label>
                            <select name="status" class="uk-select">
                                <?php foreach (['planned','active','completed'] as $v): ?>
                                <option value="<?= $v ?>" <?= $s['status']===$v?'selected':'' ?>><?= htmlspecialchars($this->sprintStatusLabel($v)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="vk-form-section" id="vk-sprint-schedule">
                        <div class="vk-form-section-title"><?= __('Schedule') ?></div>
                        <div class="vk-quarter-planner" data-quarter-planner data-year="<?= $currentYear ?>" data-quarter-start="<?= (int)$this->quarterStartMonth() ?>">
                            <div class="vk-quarter-planner-head">
                                <span><?= __('Delivery quarter') ?></span>
                                <span data-sprint-quarter-hint><?= $sprintQuarterLabel ? htmlspecialchars($sprintQuarterLabel) : __('Set dates or choose a quarter') ?></span>
                            </div>
                            <div class="vk-quarter-buttons">
                                <?php for ($q = 1; $q <= 4; $q++): ?>
                                <button type="button" class="uk-button uk-button-default uk-button-small" data-quarter="<?= $q ?>">Q<?= $q ?></button>
                                <?php endfor; ?>
                                <button type="button" class="uk-button uk-button-default uk-button-small" data-quarter-clear><?= __('Clear') ?></button>
                            </div>
                        </div>
                        <div class="vk-form-grid is-2">
                            <div>
                                <div class="vk-field">
                                    <label class="uk-form-label"><?= __('Start Date') ?></label>
                                    <input type="date" name="start_date" value="<?= $s['start_date'] ?: '' ?>" class="uk-input" data-sprint-start>
                                </div>
                            </div>
                            <div>
                                <div class="vk-field">
                                    <label class="uk-form-label"><?= __('End Date') ?></label>
                                    <input type="date" name="end_date" value="<?= $s['end_date'] ?: '' ?>" class="uk-input" data-sprint-end>
                                </div>
                            </div>
                        </div>
                        <div class="vk-field-error" data-sprint-date-error hidden><?= __('End Date must be after Start Date.') ?></div>

                        <div class="vk-field" id="vk-sprint-goal">
                            <label class="uk-form-label"><?= __('Sprint Goal') ?></label>
                            <div class="vk-rich-editor vk-sprint-editor">
                                <?= $this->renderRichTextEditor('goal', (string)$s['goal'], 150) ?>
                            </div>
                        </div>
                    </div>

                    <div class="vk-form-actions">
                        <button type="submit" class="uk-button uk-button-primary"><?= $returnUrl ? ($isEdit ? __('Save and return') : __('Create and return')) : ($isEdit ? __('Save') : __('Create Sprint')) ?></button>
                        <a href="<?= htmlspecialchars($returnUrl ?: ($url . '?view=sprints')) ?>" class="uk-button uk-button-default"><?= __('Cancel') ?></a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if ($isEdit): ?>
    <div class="vk-sprint-sidebar">
        <div id="vk-sprint-stats" class="uk-card uk-card-default vk-sprint-plan-card <?= $tasks ? '' : 'vk-is-hidden' ?>">
            <div class="uk-card-header">
                <h3 class="vk-card-title"><?= __('Sprint Plan') ?></h3>
                <div class="vk-sprint-plan-head-metrics">
                    <span><span data-stat="done"><?= $doneTasks ?></span>/<span data-stat="tasks"><?= count($tasks) ?></span> <?= __('done') ?></span>
                    <span><span data-stat="remaining"><?= $remainingTasks ?></span> <?= __('left') ?></span>
                </div>
            </div>
            <div class="uk-card-body">
                <div class="vk-sprint-plan-progress">
                    <span data-stat="progress"><?= $donePercent ?>%</span>
                    <progress class="uk-progress vk-sprint-progress" value="<?= $donePercent ?>" max="100" data-stat-progress></progress>
                </div>
                <div class="vk-sprint-plan-health">
                    <span class="is-good"><strong data-stat="due_in_window"><?= $taskDueInWindow ?></strong> <?= __('in window') ?></span>
                    <span><strong data-stat="due_missing"><?= $taskDueMissing ?></strong> <?= __('no due') ?></span>
                    <span class="<?= $taskDueOutside > 0 ? 'is-danger' : '' ?>"><strong data-stat="due_outside"><?= $taskDueOutside ?></strong> <?= __('outside') ?></span>
                </div>
                <div class="vk-sprint-plan-alert <?= $taskDueOutside > 0 ? '' : 'vk-is-hidden' ?>" data-due-alert>
                    <i class="fa fa-warning"></i>
                    <span data-due-alert-text><?= sprintf(__('%d task is outside the sprint window.'), $taskDueOutside) ?></span>
                    <a href="#vk-sprint-tasks"><?= __('Review') ?></a>
                </div>
                <div class="vk-sprint-plan-kpis">
                    <div data-stat-box="story_points" class="<?= $totalSP > 0 ? '' : 'vk-is-hidden' ?>"><span><?= __('Story Pts') ?></span><strong data-stat="story_points"><?= $totalSP ?></strong></div>
                    <div data-stat-box="estimated" class="<?= $totalEst > 0 ? '' : 'vk-is-hidden' ?>"><span><?= __('Estimated') ?></span><strong data-stat="estimated"><?= $totalEst ?>h</strong></div>
                    <div data-stat-box="actual" class="<?= $totalActual > 0 ? '' : 'vk-is-hidden' ?>"><span><?= __('Actual') ?></span><strong class="<?= $totalActual > $totalEst && $totalEst > 0 ? 'is-danger' : 'is-accent' ?>" data-stat="actual"><?= number_format((float)$totalActual, 1) ?>h</strong></div>
                </div>
            </div>
        </div>

        <div class="uk-card uk-card-default vk-card-stack vk-sprint-schedule-card">
            <div class="uk-card-header">
                <h3 class="vk-card-title"><?= __('Schedule') ?></h3>
                <span class="vk-sprint-schedule-state" data-schedule-state><?= htmlspecialchars($sprintScheduleState) ?></span>
            </div>
            <div class="uk-card-body">
                <div class="vk-sprint-schedule-summary">
                    <div>
                        <span><?= __('Quarter') ?></span>
                        <strong data-schedule-quarter><?= $sprintQuarterLabel ? htmlspecialchars($sprintQuarterLabel) : __('Not planned') ?></strong>
                    </div>
                    <div>
                        <span><?= __('Window') ?></span>
                        <strong data-schedule-window><?= htmlspecialchars($sprintDateText) ?></strong>
                    </div>
                </div>
                <div class="vk-sprint-schedule-actions">
                    <a href="#vk-sprint-schedule" class="uk-button uk-button-default uk-button-small"><i class="fa fa-calendar-o"></i> <?= __('Edit dates') ?></a>
                    <a href="<?= $url ?>?view=calendar&cal_view=quarter<?= $s['start_date'] ? '&month=' . (int)date('n', strtotime($s['start_date'])) . '&year=' . (int)date('Y', strtotime($s['start_date'])) : '' ?>" class="uk-button uk-button-default uk-button-small" data-schedule-calendar-url><i class="fa fa-th-large"></i> <?= __('Open calendar') ?></a>
                </div>
            </div>
        </div>

        <div class="uk-card uk-card-default vk-card-stack vk-sprint-readiness-card">
            <div class="uk-card-header">
                <h3 class="vk-card-title"><?= __('Readiness') ?></h3>
                <span class="vk-sprint-readiness-score" data-readiness-score><?= $readinessPercent ?>%</span>
            </div>
            <div class="uk-card-body">
                <div class="vk-sprint-readiness-progress">
                    <progress class="uk-progress" value="<?= $readinessPercent ?>" max="100" data-readiness-progress></progress>
                </div>
                <div class="vk-sprint-readiness-list">
                    <?php foreach ($readinessItems as $item): ?>
                    <a class="vk-readiness-item <?= $item['done'] ? 'is-done' : '' ?>" href="<?= $item['key'] === 'goal' ? '#vk-sprint-goal' : ($item['key'] === 'tasks' ? '#vk-sprint-tasks' : '#vk-sprint-schedule') ?>" data-readiness-item="<?= htmlspecialchars($item['key']) ?>">
                        <span class="vk-readiness-check"><i class="fa <?= $item['done'] ? 'fa-check' : 'fa-circle-o' ?>"></i></span>
                        <span>
                            <strong><?= htmlspecialchars($item['label']) ?></strong>
                            <small><?= htmlspecialchars($item['hint']) ?></small>
                        </span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="uk-card uk-card-default vk-card-stack vk-sprint-tasks" id="vk-sprint-tasks" data-sprint-id="<?= (int)$s['id'] ?>">
            <div class="uk-card-header vk-card-header-row">
                <h3 class="vk-card-title"><?= __('Tasks in Sprint') ?></h3>
                <div class="vk-card-header-actions">
                    <a href="<?= $url ?>?view=tasks&sprint_id=<?= (int)$s['id'] ?>" class="uk-button uk-button-default uk-button-small"><i class="fa fa-list"></i> <?= __('View tasks') ?></a>
                    <a href="<?= $url ?>?view=task-edit&sprint_id=<?= (int)$s['id'] ?>&return_url=<?= rawurlencode($sprintReturnUrl) ?>" class="uk-button uk-button-default uk-button-small"><i class="fa fa-plus-circle"></i> <?= __('Create Task') ?></a>
                    <button type="button" id="vk-add-existing-task" class="uk-button uk-button-default uk-button-small" aria-expanded="false" aria-controls="vk-sprint-picker"><i class="fa fa-plus"></i> <?= __('Add Task') ?></button>
                </div>
            </div>
            <div class="vk-sprint-picker" id="vk-sprint-picker" hidden>
                <label class="uk-form-label" for="vk-sprint-task-query"><?= __('Add existing task') ?></label>
                <div class="vk-sprint-search">
                    <i class="fa fa-search" aria-hidden="true"></i>
                    <input type="search" id="vk-sprint-task-query" class="uk-input" placeholder="<?= __('Search tasks...') ?>" autocomplete="off">
                </div>
                <div id="vk-sprint-task-results" class="vk-sprint-task-results" aria-live="polite"></div>
            </div>
            <div id="vk-sprint-task-list">
                <?php if ($tasks): ?>
                    <div class="vk-sprint-issue-list">
                    <?php foreach ($tasks as $t): ?>
                    <div class="vk-sprint-issue" data-task-due="<?= htmlspecialchars((string)($t['due_date'] ?? '')) ?>">
                        <div class="vk-sprint-issue-main">
                            <div class="vk-sprint-issue-titleline">
                                <span class="vk-issue-key"><?= __('TASK') ?>-<?= (int)$t['id'] ?></span>
                                <a href="<?= $url ?>?view=task-edit&id=<?= (int)$t['id'] ?>&return_url=<?= rawurlencode($sprintReturnUrl) ?>" class="vk-sprint-issue-title"><?= htmlspecialchars($t['title']) ?></a>
                            </div>
                            <div class="vk-sprint-issue-meta">
                                <span><?= htmlspecialchars($this->priorityLabel((string)$t['priority'])) ?></span>
                                <?php if ($t['assignee_name']): ?><span><?= htmlspecialchars($t['assignee_name']) ?></span><?php endif; ?>
                                <span class="<?= empty($t['due_date']) ? 'is-muted' : '' ?>"><i class="fa fa-calendar-o"></i> <?= !empty($t['due_date']) ? htmlspecialchars(date('M j', strtotime((string)$t['due_date']))) : __('No due date') ?></span>
                                <?php if (!empty($t['due_date'])): ?><span class="vk-quarter-inline"><?= htmlspecialchars($this->quarterLabelForDate((string)$t['due_date'])) ?></span><?php endif; ?>
                            </div>
                        </div>
                        <div class="vk-sprint-issue-side">
                            <span class="uk-label vk-label-<?= $t['status'] ?>"><?= htmlspecialchars($this->statusLabel($t['status'])) ?></span>
                            <?php if ($t['story_points']): ?><span class="vk-sprint-pill"><?= (int)$t['story_points'] ?> <?= __('SP') ?></span><?php endif; ?>
                            <?php $estD = $this->formatEstimate($t['estimate_h']); if ($estD !== ''): ?><span class="vk-sprint-pill"><?= htmlspecialchars($estD) ?></span><?php endif; ?>
                            <?php if ($t['actual_h'] !== null && $t['actual_h'] !== ''): ?><span class="vk-sprint-pill <?= ($t['estimate_h'] && $t['actual_h'] > $t['estimate_h']) ? 'is-over' : '' ?>"><?= number_format((float)$t['actual_h'], 1) ?>h</span><?php endif; ?>
                            <button type="button" class="vk-sprint-remove-task" data-task-id="<?= (int)$t['id'] ?>" title="<?= __('Remove from sprint') ?>" aria-label="<?= __('Remove from sprint') ?>"><i class="fa fa-times"></i></button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="vk-empty vk-empty-panel vk-sprint-empty-state">
                    <div class="vk-empty-icon"><i class="fa fa-tasks" aria-hidden="true"></i></div>
                    <h3><?= __('No tasks in this sprint yet') ?></h3>
                    <p><?= __('Create a task for this sprint or add an existing task from the picker above.') ?></p>
                    <div class="vk-empty-actions">
                        <a href="<?= $url ?>?view=task-edit&sprint_id=<?= (int)$s['id'] ?>&return_url=<?= rawurlencode($sprintReturnUrl) ?>" class="uk-button uk-button-default uk-button-small"><i class="fa fa-plus-circle"></i> <?= __('Create task in sprint') ?></a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
(function() {
    const planner = document.querySelector('[data-quarter-planner]');
    const start = document.querySelector('[data-sprint-start]');
    const end = document.querySelector('[data-sprint-end]');
    const hint = document.querySelector('[data-sprint-quarter-hint]');
    const dateError = document.querySelector('[data-sprint-date-error]');
    const scheduleQuarter = document.querySelector('[data-schedule-quarter]');
    const scheduleWindow = document.querySelector('[data-schedule-window]');
    const scheduleState = document.querySelector('[data-schedule-state]');
    const scheduleCalendarUrl = document.querySelector('[data-schedule-calendar-url]');
    const readinessScore = document.querySelector('[data-readiness-score]');
    const readinessProgress = document.querySelector('[data-readiness-progress]');
    const readinessSchedule = document.querySelector('[data-readiness-item="schedule"]');
    if (!planner || !start || !end || !hint) return;

    const emptyText = <?= json_encode(__('Set dates or choose a quarter')) ?>;
    const notPlannedText = <?= json_encode(__('Not planned')) ?>;
    const noDatesText = <?= json_encode(__('No dates set')) ?>;
    const unscheduledText = <?= json_encode(__('Unscheduled')) ?>;
    const pastText = <?= json_encode(__('Past')) ?>;
    const upcomingText = <?= json_encode(__('Upcoming')) ?>;
    const inWindowText = <?= json_encode(__('In window')) ?>;
    const startsText = <?= json_encode(__('Starts %s')) ?>;
    const endsText = <?= json_encode(__('Ends %s')) ?>;
    const addDatesText = <?= json_encode(__('Add start and end dates')) ?>;
    const outsideOneText = <?= json_encode(__('%d task is outside the sprint window.')) ?>;
    const outsideManyText = <?= json_encode(__('%d tasks are outside the sprint window.')) ?>;
    const dateErrorText = <?= json_encode(__('End Date must be after Start Date.')) ?>;
    const calendarBaseUrl = <?= json_encode($url . '?view=calendar&cal_view=quarter') ?>;
    const fiscalStart = Math.max(1, Math.min(12, Number(planner.dataset.quarterStart || 1)));
    const pad = (n) => String(n).padStart(2, '0');

    function quarterRange(q, fiscalYear) {
        const rawStart = fiscalStart + ((Number(q) - 1) * 3);
        const startMonth = ((rawStart - 1) % 12) + 1;
        const startYear = Number(fiscalYear) + Math.floor((rawStart - 1) / 12);
        const endDate = new Date(startYear, startMonth - 1 + 3, 0);
        return [
            startYear + '-' + pad(startMonth) + '-01',
            endDate.getFullYear() + '-' + pad(endDate.getMonth() + 1) + '-' + pad(endDate.getDate())
        ];
    }

    function labelForDate(value) {
        const info = quarterInfo(value);
        return info ? 'Q' + info.q + ' ' + info.fiscalYear : '';
    }

    function formatDate(value, withYear) {
        if (!value) return '';
        const date = new Date(value + 'T00:00:00');
        if (Number.isNaN(date.getTime())) return value;
        return new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric', year: withYear ? 'numeric' : undefined }).format(date);
    }

    function formatRange() {
        if (start.value && end.value) return formatDate(start.value, false) + ' - ' + formatDate(end.value, true);
        if (start.value) return startsText.replace('%s', formatDate(start.value, true));
        if (end.value) return endsText.replace('%s', formatDate(end.value, true));
        return noDatesText;
    }

    function scheduleStateLabel() {
        if (!start.value || !end.value) return unscheduledText;
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const startDate = new Date(start.value + 'T00:00:00');
        const endDate = new Date(end.value + 'T00:00:00');
        if (endDate < today) return pastText;
        if (startDate > today) return upcomingText;
        return inWindowText;
    }

    function updateCalendarUrl() {
        if (!scheduleCalendarUrl) return;
        const basis = start.value || end.value || '';
        if (!basis) {
            scheduleCalendarUrl.href = calendarBaseUrl;
            return;
        }
        const parts = basis.split('-').map(Number);
        if (!parts[0] || !parts[1]) {
            scheduleCalendarUrl.href = calendarBaseUrl;
            return;
        }
        scheduleCalendarUrl.href = calendarBaseUrl + '&month=' + parts[1] + '&year=' + parts[0];
    }

    function quarterInfo(value) {
        if (!value) return '';
        const parts = value.split('-').map(Number);
        if (parts.length < 2 || !parts[0] || !parts[1]) return '';
        const offset = (parts[1] - fiscalStart + 12) % 12;
        const q = Math.floor(offset / 3) + 1;
        const fiscalYear = parts[1] < fiscalStart ? parts[0] - 1 : parts[0];
        return { q, fiscalYear };
    }

    function updateHint() {
        const startLabel = labelForDate(start.value);
        const endLabel = labelForDate(end.value || start.value);
        const hasSchedule = !!(start.value && end.value);
        hint.textContent = startLabel ? (endLabel && endLabel !== startLabel ? startLabel + ' - ' + endLabel : startLabel) : emptyText;
        if (scheduleQuarter) scheduleQuarter.textContent = startLabel ? (endLabel && endLabel !== startLabel ? startLabel + ' - ' + endLabel : startLabel) : notPlannedText;
        if (scheduleWindow) scheduleWindow.textContent = formatRange();
        if (scheduleState) scheduleState.textContent = scheduleStateLabel();
        updateCalendarUrl();
        updateDueHealth();
        if (readinessSchedule) {
            readinessSchedule.classList.toggle('is-done', hasSchedule);
            const icon = readinessSchedule.querySelector('.fa');
            const hintEl = readinessSchedule.querySelector('small');
            if (icon) icon.className = 'fa ' + (hasSchedule ? 'fa-check' : 'fa-circle-o');
            if (hintEl) hintEl.textContent = hasSchedule ? formatRange() : addDatesText;
        }
        updateReadiness();
        planner.classList.toggle('is-active', !!startLabel);
        const active = quarterInfo(start.value);
        planner.querySelectorAll('[data-quarter]').forEach(button => {
            button.classList.toggle('is-active', !!active && Number(button.dataset.quarter) === active.q);
        });
        validateDates();
    }

    function updateDueHealth() {
        const inWindowNode = document.querySelector('[data-stat="due_in_window"]');
        const missingNode = document.querySelector('[data-stat="due_missing"]');
        const outsideNode = document.querySelector('[data-stat="due_outside"]');
        if (!inWindowNode || !missingNode || !outsideNode) return;
        const hasWindow = !!(start.value && end.value);
        let inWindow = 0;
        let missing = 0;
        let outside = 0;
        document.querySelectorAll('#vk-sprint-task-list [data-task-due]').forEach(item => {
            const due = item.dataset.taskDue || '';
            item.classList.remove('is-due-in-window', 'is-due-missing', 'is-due-outside');
            if (!due) {
                missing++;
                item.classList.add('is-due-missing');
            } else if (hasWindow && due >= start.value && due <= end.value) {
                inWindow++;
                item.classList.add('is-due-in-window');
            } else if (hasWindow) {
                outside++;
                item.classList.add('is-due-outside');
            }
        });
        inWindowNode.textContent = inWindow;
        missingNode.textContent = missing;
        outsideNode.textContent = outside;
        outsideNode.parentElement.classList.toggle('is-danger', outside > 0);
        const alert = document.querySelector('[data-due-alert]');
        const alertText = document.querySelector('[data-due-alert-text]');
        if (alert) alert.classList.toggle('vk-is-hidden', outside <= 0);
        if (alertText) alertText.textContent = (outside === 1 ? outsideOneText : outsideManyText).replace('%d', outside);
    }

    window.vkUpdateSprintDueHealth = updateDueHealth;

    function updateReadiness() {
        if (!readinessScore || !readinessProgress) return;
        const items = Array.from(document.querySelectorAll('[data-readiness-item]'));
        if (!items.length) return;
        const done = items.filter(item => item.classList.contains('is-done')).length;
        const percent = Math.round(done / items.length * 100);
        readinessScore.textContent = percent + '%';
        readinessProgress.value = percent;
    }

    function validateDates() {
        const invalid = !!(start.value && end.value && end.value < start.value);
        end.setCustomValidity(invalid ? dateErrorText : '');
        if (dateError) dateError.hidden = !invalid;
        planner.classList.toggle('is-invalid', invalid);
        return !invalid;
    }

    planner.querySelectorAll('[data-quarter]').forEach(button => {
        button.addEventListener('click', function() {
            const q = button.dataset.quarter;
            const year = (start.value || end.value || planner.dataset.year + '-01-01').slice(0, 4);
            const range = quarterRange(q, year);
            start.value = range[0];
            end.value = range[1];
            updateHint();
        });
    });

    const clear = planner.querySelector('[data-quarter-clear]');
    if (clear) {
        clear.addEventListener('click', function() {
            start.value = '';
            end.value = '';
            updateHint();
            start.focus();
        });
    }

    start.addEventListener('input', updateHint);
    start.addEventListener('change', updateHint);
    end.addEventListener('input', updateHint);
    end.addEventListener('change', updateHint);
    const form = planner.closest('form');
    if (form) {
        form.addEventListener('submit', function(event) {
            if (!validateDates()) {
                event.preventDefault();
                end.reportValidity();
            }
        });
    }
    updateHint();
})();
</script>

<?php if ($isEdit): ?>
<script>
(function() {
    const card = document.getElementById('vk-sprint-tasks');
    const toggle = document.getElementById('vk-add-existing-task');
    const picker = document.getElementById('vk-sprint-picker');
    const query = document.getElementById('vk-sprint-task-query');
    const results = document.getElementById('vk-sprint-task-results');
    const taskList = document.getElementById('vk-sprint-task-list');
    const stats = document.getElementById('vk-sprint-stats');
    if (!card || !toggle || !picker || !query || !results || !taskList || !stats) return;

    const sprintId = card.dataset.sprintId;
    const endpoint = <?= json_encode($url) ?>;
    const createTaskUrl = <?= json_encode($url . '?view=task-edit&sprint_id=' . (int)$s['id'] . '&return_url=' . rawurlencode($sprintReturnUrl)) ?>;
    const csrfName = <?= json_encode($csrfN) ?>;
    const csrfToken = <?= json_encode($csrf) ?>;
    const T = {
        updateError: <?= json_encode(__('Could not update sprint.')) ?>,
        loadError:   <?= json_encode(__('Could not load tasks.')) ?>,
        none:        <?= json_encode(__('No available tasks found.')) ?>,
        loading:     <?= json_encode(__('Loading tasks...')) ?>,
        add:         <?= json_encode(__('Add')) ?>,
        createTask:  <?= json_encode(__('Create task in sprint')) ?>,
        movesFrom:   <?= json_encode(__('moves from')) ?>,
        noSprint:    <?= json_encode(__('no sprint')) ?>,
        noDueDate:   <?= json_encode(__('No due date')) ?>,
        remove:      <?= json_encode(__('Remove from sprint')) ?>,
        empty:       <?= json_encode(__('No tasks in this sprint yet.')) ?>,
        emptyHelp:   <?= json_encode(__('Create a task for this sprint or add an existing task from the picker above.')) ?>,
        task:        <?= json_encode(__('Task')) ?>,
        statusHead:  <?= json_encode(__('Status')) ?>,
        sp:          <?= json_encode(__('SP')) ?>,
        est:         <?= json_encode(__('Est')) ?>,
        actual:      <?= json_encode(__('Actual')) ?>,
        sessionExpired: <?= json_encode(__('Session expired, please reload and try again.')) ?>,
        taskKey:     <?= json_encode(__('TASK')) ?>,
        status:      <?= json_encode(['open'=>$this->statusLabel('open'),'in_progress'=>$this->statusLabel('in_progress'),'review'=>$this->statusLabel('review'),'done'=>$this->statusLabel('done')]) ?>
    };
    let timer;

    function parseJsonResponse(response) {
        return response.text().then(text => {
            try {
                return { ok: response.ok, data: JSON.parse(text) };
            } catch (e) {
                return { ok: false, data: { ok: false, message: T.sessionExpired } };
            }
        });
    }

    function showMessage(message, isError, withCreate) {
        results.innerHTML = '';
        const state = document.createElement('div');
        state.className = 'vk-sprint-picker-state' + (isError ? ' is-error' : '');
        const text = document.createElement('span');
        text.textContent = message;
        state.appendChild(text);
        if (withCreate && createTaskUrl) {
            const action = document.createElement('a');
            action.className = 'uk-button uk-button-default uk-button-small';
            action.href = createTaskUrl;
            action.innerHTML = '<i class="fa fa-plus-circle"></i> ' + T.createTask;
            state.appendChild(action);
        }
        results.appendChild(state);
    }

    function postTask(action, taskId) {
        const body = new FormData();
        body.append(csrfName, csrfToken);
        body.append('action', action);
        body.append('sprint_id', sprintId);
        body.append('task_id', taskId);
        return fetch(endpoint, { method: 'POST', body: body, credentials: 'same-origin' })
            .then(parseJsonResponse)
            .then(result => {
                if (!result.ok || !result.data.ok) throw new Error(result.data.message || T.updateError);
                updateStats(result.data.summary);
                renderSprintTasks(result.data.tasks || []);
                search();
            });
    }

    function formatHours(value) {
        const n = Number(value || 0);
        if (n > 0 && n < 1) return Math.round(n * 60) + 'm';
        return Number.isInteger(n) ? n + 'h' : (Math.round(n * 100) / 100) + 'h';
    }

    function updateStats(summary) {
        const hasTasks = summary && Number(summary.tasks || 0) > 0;
        stats.classList.toggle('vk-is-hidden', !hasTasks);
        if (!summary) return;
        const values = {
            tasks: summary.tasks || 0,
            done: summary.done || 0,
            remaining: Math.max(0, Number(summary.tasks || 0) - Number(summary.done || 0)),
            story_points: summary.story_points || 0,
            estimated: formatHours(summary.estimated || 0),
            actual: formatHours(summary.actual || 0)
        };
        values.due_in_window = summary.due_in_window || 0;
        values.due_missing = summary.due_missing || 0;
        values.due_outside = summary.due_outside || 0;
        Object.keys(values).forEach(key => {
            const node = stats.querySelector('[data-stat="' + key + '"]');
            if (node) node.textContent = values[key];
        });
        const progress = hasTasks ? Math.round(Number(summary.done || 0) / Number(summary.tasks || 1) * 100) : 0;
        const progressText = stats.querySelector('[data-stat="progress"]');
        const progressBar = stats.querySelector('[data-stat-progress]');
        if (progressText) progressText.textContent = progress + '%';
        if (progressBar) progressBar.value = progress;
        ['story_points', 'estimated', 'actual'].forEach(key => {
            const box = stats.querySelector('[data-stat-box="' + key + '"]');
            if (box) box.classList.toggle('vk-is-hidden', Number(summary[key] || 0) <= 0);
        });
        const actual = stats.querySelector('[data-stat="actual"]');
        if (actual) {
            const over = Number(summary.actual || 0) > Number(summary.estimated || 0) && Number(summary.estimated || 0) > 0;
            actual.style.color = over ? 'var(--pw-error-inline-text-color)' : 'var(--pw-main-color)';
        }
        const outside = stats.querySelector('[data-stat="due_outside"]');
        if (outside && outside.parentElement) {
            outside.parentElement.classList.toggle('is-danger', Number(summary.due_outside || 0) > 0);
        }
    }

    function renderSprintTasks(tasks) {
        taskList.innerHTML = '';
        if (!tasks.length) {
            const empty = document.createElement('div');
            empty.className = 'vk-empty vk-empty-panel vk-sprint-empty-state';
            const icon = document.createElement('div');
            icon.className = 'vk-empty-icon';
            icon.innerHTML = '<i class="fa fa-tasks" aria-hidden="true"></i>';
            empty.appendChild(icon);
            const title = document.createElement('h3');
            title.textContent = T.empty;
            empty.appendChild(title);
            const copy = document.createElement('p');
            copy.textContent = T.emptyHelp;
            empty.appendChild(copy);
            const actionWrap = document.createElement('div');
            actionWrap.className = 'vk-empty-actions';
            const action = document.createElement('a');
            action.className = 'uk-button uk-button-default uk-button-small';
            action.href = createTaskUrl;
            action.innerHTML = '<i class="fa fa-plus-circle"></i> ' + T.createTask;
            actionWrap.appendChild(action);
            empty.appendChild(actionWrap);
            taskList.appendChild(empty);
            if (typeof window.vkUpdateSprintDueHealth === 'function') window.vkUpdateSprintDueHealth();
            return;
        }

        const list = document.createElement('div');
        list.className = 'vk-sprint-issue-list';
        tasks.forEach(task => {
            const item = document.createElement('div');
            item.className = 'vk-sprint-issue';
            item.dataset.taskDue = task.due_date || '';

            const main = document.createElement('div');
            main.className = 'vk-sprint-issue-main';
            const titleLine = document.createElement('div');
            titleLine.className = 'vk-sprint-issue-titleline';
            const key = document.createElement('span');
            key.className = 'vk-issue-key';
            key.textContent = T.taskKey + '-' + task.id;
            const link = document.createElement('a');
            link.className = 'vk-sprint-issue-title';
            link.href = task.edit_url || (endpoint + '?view=task-edit&id=' + task.id);
            link.textContent = task.title;
            titleLine.appendChild(key);
            titleLine.appendChild(link);
            main.appendChild(titleLine);
            const meta = document.createElement('div');
            meta.className = 'vk-sprint-issue-meta';
            [task.priority_label || task.priority, task.assignee_name].filter(Boolean).forEach(text => {
                const span = document.createElement('span');
                span.textContent = text;
                meta.appendChild(span);
            });
            const due = document.createElement('span');
            if (!task.due_date) due.className = 'is-muted';
            const dueIcon = document.createElement('i');
            dueIcon.className = 'fa fa-calendar-o';
            due.appendChild(dueIcon);
            due.appendChild(document.createTextNode(' ' + (task.due_label || T.noDueDate)));
            meta.appendChild(due);
            if (task.quarter_label) {
                const quarter = document.createElement('span');
                quarter.className = 'vk-quarter-inline';
                quarter.textContent = task.quarter_label;
                meta.appendChild(quarter);
            }
            main.appendChild(meta);
            item.appendChild(main);

            const side = document.createElement('div');
            side.className = 'vk-sprint-issue-side';
            const status = document.createElement('span');
            status.className = 'uk-label vk-label-' + task.status;
            status.textContent = task.status_label || T.status[task.status] || task.status;
            side.appendChild(status);
            if (Number(task.story_points || 0) > 0) {
                const sp = document.createElement('span');
                sp.className = 'vk-sprint-pill';
                sp.textContent = task.story_points + ' ' + T.sp;
                side.appendChild(sp);
            }
            if (Number(task.estimate_h || 0) > 0) {
                const est = document.createElement('span');
                est.className = 'vk-sprint-pill';
                est.textContent = formatHours(task.estimate_h);
                side.appendChild(est);
            }
            if (task.actual_h !== null && task.actual_h !== '') {
                const actual = document.createElement('span');
                actual.className = 'vk-sprint-pill' + (Number(task.estimate_h || 0) > 0 && Number(task.actual_h || 0) > Number(task.estimate_h || 0) ? ' is-over' : '');
                actual.textContent = Number(task.actual_h || 0).toFixed(1) + 'h';
                side.appendChild(actual);
            }
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'vk-sprint-remove-task';
            remove.dataset.taskId = task.id;
            remove.title = T.remove;
            remove.setAttribute('aria-label', T.remove);
            const icon = document.createElement('i');
            icon.className = 'fa fa-times';
            remove.appendChild(icon);
            side.appendChild(remove);
            item.appendChild(side);

            list.appendChild(item);
        });
        taskList.appendChild(list);
        attachRemoveHandlers();
        if (typeof window.vkUpdateSprintDueHealth === 'function') window.vkUpdateSprintDueHealth();
    }

    function renderTasks(tasks) {
        results.innerHTML = '';
        if (!tasks.length) {
            showMessage(T.none, false, true);
            return;
        }
        tasks.forEach(task => {
            const item = document.createElement('div');
            item.className = 'vk-sprint-pick-item';
            const details = document.createElement('div');
            details.className = 'vk-sprint-pick-details';
            const titleLine = document.createElement('div');
            titleLine.className = 'vk-sprint-pick-titleline';
            const key = document.createElement('span');
            key.className = 'vk-issue-key';
            key.textContent = T.taskKey + '-' + task.id;
            const title = document.createElement('div');
            title.className = 'vk-sprint-pick-title';
            title.textContent = task.title;
            titleLine.appendChild(key);
            titleLine.appendChild(title);
            const meta = document.createElement('div');
            meta.className = 'vk-sprint-pick-meta';
            const statusText = T.status[task.status] || task.status;
            const priorityText = task.priority_label || task.priority || '';
            [priorityText, task.sprint_name ? T.movesFrom + ' ' + task.sprint_name : T.noSprint].filter(Boolean).forEach(text => {
                const span = document.createElement('span');
                span.textContent = text;
                meta.appendChild(span);
            });
            details.appendChild(titleLine);
            details.appendChild(meta);
            const side = document.createElement('div');
            side.className = 'vk-sprint-pick-side';
            const status = document.createElement('span');
            status.className = 'uk-label vk-label-' + task.status;
            status.textContent = task.status_label || statusText;
            side.appendChild(status);
            const add = document.createElement('button');
            add.type = 'button';
            add.className = 'uk-button uk-button-primary uk-button-small vk-sprint-pick-add';
            add.innerHTML = '<i class="fa fa-plus"></i> ' + T.add;
            add.addEventListener('click', function() {
                add.disabled = true;
                postTask('attach_sprint_task', task.id).catch(error => {
                    add.disabled = false;
                    showMessage(error.message, true);
                });
            });
            side.appendChild(add);
            item.appendChild(details);
            item.appendChild(side);
            results.appendChild(item);
        });
    }

    function search() {
        showMessage(T.loading, false);
        fetch(endpoint + '?view=ajax-sprint-tasks&sprint_id=' + encodeURIComponent(sprintId) + '&q=' + encodeURIComponent(query.value.trim()), { credentials: 'same-origin' })
            .then(parseJsonResponse)
            .then(result => {
                if (!result.ok || !result.data.ok) throw new Error(result.data.message || T.loadError);
                renderTasks(result.data.tasks);
            })
            .catch(error => showMessage(error.message, true));
    }

    toggle.addEventListener('click', function() {
        picker.hidden = !picker.hidden;
        toggle.setAttribute('aria-expanded', picker.hidden ? 'false' : 'true');
        if (!picker.hidden) {
            query.focus();
            search();
        }
    });

    query.addEventListener('input', function() {
        clearTimeout(timer);
        timer = setTimeout(search, 220);
    });

    function attachRemoveHandlers() {
        card.querySelectorAll('.vk-sprint-remove-task').forEach(button => {
            button.addEventListener('click', function() {
                button.disabled = true;
                postTask('detach_sprint_task', button.dataset.taskId).catch(error => {
                    button.disabled = false;
                    showMessage(error.message, true);
                    picker.hidden = false;
                });
            });
        });
    }

    attachRemoveHandlers();
})();
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/_layout.php';

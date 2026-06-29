<?php namespace ProcessWire;
/**
 * @var Verk $this
 * @var array $tasks
 * @var array $users
 * @var int   $total
 * @var int   $totalPages
 * @var int   $pageNum
 */
$url             = $this->page->url;
$today           = date('Y-m-d');
$input           = $this->wire('input');
$statusFilter    = $input->get('status', 'string') ?: '';
$prioFilter      = $input->get('priority', 'string') ?: '';
$searchFilter    = trim($input->get('q', 'string') ?: '');
$sortFilter      = $input->get('sort', 'string') ?: 'default';
$sortLabels      = [
    'default' => __('Workflow order'),
    'due' => __('Due date'),
    'priority' => __('Priority'),
    'created' => __('Recently created'),
    'title' => __('Title A-Z'),
];
if (!isset($sortLabels[$sortFilter])) $sortFilter = 'default';
$assigneeFilter  = $currentAssigneeId ?? (int)$input->get('assignee_id');
$reviewerFilter  = $currentReviewerId ?? (int)$input->get('reviewer_id');
$collaboratorFilter = $currentCollaboratorId ?? (int)$input->get('collaborator_id');
$sprintFilter    = $currentSprintId ?? 0;
$taskDateState   = $currentTaskDateState ?? ($input->get('date_state', 'string') === 'none' ? 'none' : '');
$sprints         = $sprints ?? [];
$taskQuarter     = $taskQuarter ?? null;
$taskQuarterLabel = $taskQuarter ? $this->quarterLabel($taskQuarter) : '';
$taskQuarterYear = $taskQuarterYear ?? ($taskQuarter ? (int)$taskQuarter['year'] : (int)$this->quarterContextForDate(date('Y-m-d'))['year']);
$taskQuarterCounts = $taskQuarterCounts ?? [];
$taskNoDueCount = $taskNoDueCount ?? 0;
$taskStatusSummary = $taskStatusSummary ?? [];
$buildTaskUrl = function(array $changes = []) use ($url, $statusFilter, $prioFilter, $searchFilter, $sortFilter, $assigneeFilter, $reviewerFilter, $collaboratorFilter, $sprintFilter, $taskQuarter, $taskDateState) {
    $params = ['view' => 'tasks'];
    if ($statusFilter) $params['status'] = $statusFilter;
    if ($prioFilter) $params['priority'] = $prioFilter;
    if ($searchFilter) $params['q'] = $searchFilter;
    if ($sortFilter !== 'default') $params['sort'] = $sortFilter;
    if ($assigneeFilter) $params['assignee_id'] = $assigneeFilter;
    if ($reviewerFilter) $params['reviewer_id'] = $reviewerFilter;
    if ($collaboratorFilter) $params['collaborator_id'] = $collaboratorFilter;
    if ($sprintFilter) $params['sprint_id'] = $sprintFilter;
    if ($taskDateState) $params['date_state'] = $taskDateState;
    if ($taskQuarter) {
        $params['quarter'] = (int)$taskQuarter['quarter'];
        $params['year'] = (int)$taskQuarter['year'];
    }
    foreach ($changes as $key => $value) {
        if ($value === '' || $value === null || $value === 0) unset($params[$key]);
        else $params[$key] = $value;
    }
    return $url . '?' . http_build_query($params);
};

$pageNum    = $pageNum ?? 1;
$totalPages = $totalPages ?? 1;
$total      = $total ?? 0;
$taskListReturnUrl = $buildTaskUrl($pageNum > 1 ? ['page' => $pageNum] : []);
$taskListReturnParam = rawurlencode($taskListReturnUrl);
$sprintFilterName = '';
$sprintFilterData = null;
$assigneeFilterName = '';
if ((int)$assigneeFilter === -1) {
    $assigneeFilterName = __('Unassigned');
}
if ((int)$sprintFilter === -1) {
    $sprintFilterName = __('No sprint');
}
foreach ($users as $u) {
    if ((int)$u['id'] === (int)$assigneeFilter) {
        $assigneeFilterName = (string)$u['name'];
        break;
    }
}
$reviewerFilterName = '';
if ((int)$reviewerFilter === -1) {
    $reviewerFilterName = __('No reviewer');
}
foreach ($users as $u) {
    if ((int)$u['id'] === (int)$reviewerFilter) {
        $reviewerFilterName = (string)$u['name'];
        break;
    }
}
$collaboratorFilterName = '';
if ((int)$collaboratorFilter === -1) {
    $collaboratorFilterName = __('No collaborator');
}
foreach ($users as $u) {
    if ((int)$u['id'] === (int)$collaboratorFilter) {
        $collaboratorFilterName = (string)$u['name'];
        break;
    }
}
foreach ($sprints as $s) {
    if ((int)$s['id'] === (int)$sprintFilter) {
        $sprintFilterName = (string)$s['name'];
        $sprintFilterData = $s;
        break;
    }
}
$sprintTaskStats = [
    'estimate' => 0.0,
    'actual' => 0.0,
    'open' => 0,
    'done' => 0,
];
foreach ($taskStatusSummary as $state => $summary) {
    $sprintTaskStats['estimate'] += (float)($summary['estimate_h'] ?? 0);
    $sprintTaskStats['actual'] += (float)($summary['actual_h'] ?? 0);
    if ($state === 'done') $sprintTaskStats['done'] += (int)($summary['n'] ?? 0);
    else $sprintTaskStats['open'] += (int)($summary['n'] ?? 0);
}
$sprintDateText = '';
if ($sprintFilterData) {
    $sprintStart = (string)($sprintFilterData['start_date'] ?? '');
    $sprintEnd = (string)($sprintFilterData['end_date'] ?? '');
    if ($sprintStart && $sprintEnd) $sprintDateText = date('M j', strtotime($sprintStart)) . ' - ' . date('M j, Y', strtotime($sprintEnd));
    elseif ($sprintStart) $sprintDateText = sprintf(__('Starts %s'), date('M j, Y', strtotime($sprintStart)));
    elseif ($sprintEnd) $sprintDateText = sprintf(__('Ends %s'), date('M j, Y', strtotime($sprintEnd)));
    else $sprintDateText = __('No dates set');
}
$activeFilters = [];
if ($searchFilter) $activeFilters[] = ['label' => sprintf(__('Search: %s'), $searchFilter), 'href' => $buildTaskUrl(['q' => null, 'page' => null])];
if ($statusFilter) $activeFilters[] = ['label' => sprintf(__('Status: %s'), $this->statusLabel($statusFilter)), 'href' => $buildTaskUrl(['status' => null, 'page' => null])];
if ($prioFilter) $activeFilters[] = ['label' => sprintf(__('Priority: %s'), $this->priorityLabel($prioFilter)), 'href' => $buildTaskUrl(['priority' => null, 'page' => null])];
if ($assigneeFilter) $activeFilters[] = ['label' => sprintf(__('Assignee: %s'), $assigneeFilterName ?: ('#' . (int)$assigneeFilter)), 'href' => $buildTaskUrl(['assignee_id' => null, 'page' => null])];
if ($reviewerFilter) $activeFilters[] = ['label' => sprintf(__('Reviewer: %s'), $reviewerFilterName ?: ('#' . (int)$reviewerFilter)), 'href' => $buildTaskUrl(['reviewer_id' => null, 'page' => null])];
if ($collaboratorFilter) $activeFilters[] = ['label' => sprintf(__('Collaborator: %s'), $collaboratorFilterName ?: ('#' . (int)$collaboratorFilter)), 'href' => $buildTaskUrl(['collaborator_id' => null, 'page' => null])];
if ($taskQuarterLabel) $activeFilters[] = ['label' => sprintf(__('Quarter: %s'), $taskQuarterLabel), 'href' => $buildTaskUrl(['quarter' => null, 'year' => null, 'page' => null])];
if ($taskDateState === 'none') $activeFilters[] = ['label' => __('No due date'), 'href' => $buildTaskUrl(['date_state' => null, 'page' => null])];
if ($sprintFilter) $activeFilters[] = ['label' => sprintf(__('Sprint: %s'), $sprintFilterName ?: ('#' . (int)$sprintFilter)), 'href' => $buildTaskUrl(['sprint_id' => null, 'page' => null])];
if ($sortFilter !== 'default') $activeFilters[] = ['label' => sprintf(__('Sort: %s'), $sortLabels[$sortFilter]), 'href' => $buildTaskUrl(['sort' => null, 'page' => null])];

ob_start();
?>

<div class="vk-page-head">
    <div>
        <h2 class="vk-page-title"><?= __('Tasks') ?></h2>
        <p><?= sprintf(__('%d tasks'), $total) ?><?= $searchFilter ? ' · ' . sprintf(__('search "%s"'), htmlspecialchars($searchFilter)) : '' ?><?= $statusFilter ? ' ' . sprintf(__('filtered by %s'), htmlspecialchars($this->statusLabel($statusFilter))) : '' ?><?= $prioFilter ? ' ' . sprintf(__('with %s priority'), htmlspecialchars($this->priorityLabel($prioFilter))) : '' ?><?= $taskQuarterLabel ? ' · ' . htmlspecialchars($taskQuarterLabel) : '' ?><?= $taskDateState === 'none' ? ' · ' . __('No due date') : '' ?>.</p>
    </div>
    <div class="vk-actions">
        <a href="<?= $url ?>?view=export-docx&type=tasks&status=<?= urlencode($statusFilter) ?>&priority=<?= urlencode($prioFilter) ?>&q=<?= urlencode($searchFilter) ?>&assignee_id=<?= (int)$assigneeFilter ?>&sprint_id=<?= $sprintFilter ?><?= $taskQuarter ? '&quarter=' . (int)$taskQuarter['quarter'] . '&year=' . (int)$taskQuarter['year'] : '' ?><?= $taskDateState ? '&date_state=' . urlencode($taskDateState) : '' ?>" class="uk-button uk-button-default">
            <i class="fa fa-file-word-o"></i> <?= __('Export') ?>
        </a>
        <a href="<?= $url ?>?view=task-edit<?= $sprintFilter > 0 ? '&sprint_id=' . (int)$sprintFilter : '' ?>&return_url=<?= $taskListReturnParam ?>" class="uk-button uk-button-primary"><i class="fa fa-plus"></i> <?= __('New Task') ?></a>
    </div>
</div>

<?php if ($sprintFilter > 0 && $sprintFilterData): ?>
<section class="vk-task-sprint-context">
    <div class="vk-task-sprint-main">
        <span class="vk-issue-key"><?= __('SPRINT') ?>-<?= (int)$sprintFilter ?></span>
        <div>
            <h3><?= htmlspecialchars($sprintFilterName) ?></h3>
            <p><?= htmlspecialchars($sprintDateText) ?> · <?= htmlspecialchars($this->sprintStatusLabel((string)$sprintFilterData['status'])) ?></p>
        </div>
    </div>
    <div class="vk-task-sprint-stats">
        <span><strong><?= (int)$sprintTaskStats['open'] ?></strong> <?= __('open') ?></span>
        <span><strong><?= (int)$sprintTaskStats['done'] ?></strong> <?= __('done') ?></span>
        <span><strong><?= number_format($sprintTaskStats['actual'], 1) ?>h</strong> / <?= number_format($sprintTaskStats['estimate'], 1) ?>h</span>
    </div>
    <a class="uk-button uk-button-default uk-button-small" href="<?= $url ?>?view=sprint-edit&id=<?= (int)$sprintFilter ?>#vk-sprint-tasks">
        <i class="fa fa-arrow-left"></i> <?= __('Back to sprint') ?>
    </a>
</section>
<?php endif; ?>

<div class="vk-task-status-strip">
    <?php foreach (['open', 'in_progress', 'review', 'done'] as $state):
        $summary = $taskStatusSummary[$state] ?? ['n' => 0, 'estimate_h' => 0, 'actual_h' => 0];
        $isActive = $statusFilter === $state;
    ?>
    <a class="vk-task-status-card <?= $isActive ? 'is-active' : '' ?>" href="<?= $buildTaskUrl(['status' => $state, 'page' => null]) ?>">
        <span class="vk-task-status-name"><?= htmlspecialchars($this->statusLabel($state)) ?></span>
        <strong><?= (int)$summary['n'] ?></strong>
        <small><?= number_format((float)$summary['actual_h'], 1) ?>h / <?= number_format((float)$summary['estimate_h'], 1) ?>h</small>
    </a>
    <?php endforeach; ?>
</div>

<div class="vk-task-filters vk-filter-panel">
    <div class="vk-task-filter-group vk-task-search-group">
        <div class="vk-task-filter-label"><?= __('Search') ?></div>
        <form method="get" action="<?= $url ?>" class="vk-task-search-form">
            <input type="hidden" name="view" value="tasks">
            <?php if ($statusFilter): ?><input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>"><?php endif; ?>
            <?php if ($prioFilter): ?><input type="hidden" name="priority" value="<?= htmlspecialchars($prioFilter) ?>"><?php endif; ?>
            <?php if ($assigneeFilter): ?><input type="hidden" name="assignee_id" value="<?= (int)$assigneeFilter ?>"><?php endif; ?>
            <?php if ($reviewerFilter): ?><input type="hidden" name="reviewer_id" value="<?= (int)$reviewerFilter ?>"><?php endif; ?>
            <?php if ($collaboratorFilter): ?><input type="hidden" name="collaborator_id" value="<?= (int)$collaboratorFilter ?>"><?php endif; ?>
            <?php if ($sprintFilter): ?><input type="hidden" name="sprint_id" value="<?= (int)$sprintFilter ?>"><?php endif; ?>
            <?php if ($sortFilter !== 'default'): ?><input type="hidden" name="sort" value="<?= htmlspecialchars($sortFilter) ?>"><?php endif; ?>
            <?php if ($taskDateState): ?><input type="hidden" name="date_state" value="<?= htmlspecialchars($taskDateState) ?>"><?php endif; ?>
            <?php if ($taskQuarter): ?>
            <input type="hidden" name="quarter" value="<?= (int)$taskQuarter['quarter'] ?>">
            <input type="hidden" name="year" value="<?= (int)$taskQuarter['year'] ?>">
            <?php endif; ?>
            <div class="vk-task-search-control">
                <i class="fa fa-search"></i>
                <input class="uk-input" type="search" name="q" value="<?= htmlspecialchars($searchFilter) ?>" placeholder="<?= __('Search title, TASK-ID, section, notes') ?>">
                <?php if ($searchFilter): ?><a href="<?= $buildTaskUrl(['q' => null, 'page' => null]) ?>" class="vk-filter-reset"><?= __('Clear') ?></a><?php endif; ?>
                <button class="uk-button uk-button-default uk-button-small"><?= __('Search') ?></button>
            </div>
        </form>
    </div>

    <div class="vk-task-filter-group is-tabs">
        <div class="vk-task-filter-label"><?= __('Status') ?></div>
        <ul class="uk-subnav uk-subnav-pill vk-view-switcher vk-task-filter-tabs">
            <?php
            $statuses = ['' => __('All'), 'active' => $this->statusLabel('active'), 'open' => $this->statusLabel('open'), 'in_progress' => $this->statusLabel('in_progress'), 'review' => $this->statusLabel('review'), 'done' => $this->statusLabel('done')];
            foreach ($statuses as $v => $l):
                $active = $statusFilter === $v;
                $href   = $buildTaskUrl(['status' => $v, 'page' => null]);
            ?>
            <li class="<?= $active ? 'uk-active' : '' ?>"><a href="<?= $href ?>"><?= $l ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="vk-task-filter-group is-tabs">
        <div class="vk-task-filter-label"><?= __('Priority') ?></div>
        <ul class="uk-subnav uk-subnav-pill vk-view-switcher vk-task-filter-tabs">
            <?php
            $prios = ['' => __('All'), 'critical' => $this->priorityLabel('critical'), 'high' => $this->priorityLabel('high'), 'medium' => $this->priorityLabel('medium'), 'low' => $this->priorityLabel('low')];
            foreach ($prios as $v => $l):
                $active = $prioFilter === $v;
                $href   = $buildTaskUrl(['priority' => $v, 'page' => null]);
            ?>
            <li class="<?= $active ? 'uk-active' : '' ?>"><a href="<?= $href ?>"><?= $l ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="vk-task-filter-group is-tabs">
        <div class="vk-task-filter-label"><?= __('Quarter') ?></div>
        <ul class="uk-subnav uk-subnav-pill vk-view-switcher vk-task-filter-tabs">
            <li class="<?= !$taskQuarter && $taskDateState !== 'none' ? 'uk-active' : '' ?>"><a href="<?= $buildTaskUrl(['quarter' => null, 'year' => null, 'date_state' => null, 'page' => null]) ?>"><?= __('All quarters') ?></a></li>
            <?php $yearForQuarters = (int)$taskQuarterYear; ?>
            <?php for ($q = 1; $q <= 4; $q++): ?>
            <li class="<?= $taskQuarter && (int)$taskQuarter['quarter'] === $q ? 'uk-active' : '' ?>"><a href="<?= $buildTaskUrl(['quarter' => $q, 'year' => $yearForQuarters, 'date_state' => null, 'page' => null]) ?>">Q<?= $q ?></a></li>
            <?php endfor; ?>
            <li class="<?= $taskDateState === 'none' ? 'uk-active' : '' ?>"><a href="<?= $buildTaskUrl(['quarter' => null, 'year' => null, 'date_state' => 'none', 'page' => null]) ?>"><?= __('No due date') ?></a></li>
        </ul>
    </div>

    <div class="vk-task-filter-group">
        <div class="vk-task-filter-label"><?= __('Assignee') ?></div>
        <form method="get" action="<?= $url ?>" class="vk-filter-select-form">
            <input type="hidden" name="view" value="tasks">
            <?php if ($statusFilter): ?><input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>"><?php endif; ?>
            <?php if ($prioFilter): ?><input type="hidden" name="priority" value="<?= htmlspecialchars($prioFilter) ?>"><?php endif; ?>
            <?php if ($searchFilter): ?><input type="hidden" name="q" value="<?= htmlspecialchars($searchFilter) ?>"><?php endif; ?>
            <?php if ($sortFilter !== 'default'): ?><input type="hidden" name="sort" value="<?= htmlspecialchars($sortFilter) ?>"><?php endif; ?>
            <?php if ($sprintFilter): ?><input type="hidden" name="sprint_id" value="<?= (int)$sprintFilter ?>"><?php endif; ?>
            <?php if ($taskDateState): ?><input type="hidden" name="date_state" value="<?= htmlspecialchars($taskDateState) ?>"><?php endif; ?>
            <?php if ($taskQuarter): ?>
            <input type="hidden" name="quarter" value="<?= (int)$taskQuarter['quarter'] ?>">
            <input type="hidden" name="year" value="<?= (int)$taskQuarter['year'] ?>">
            <?php endif; ?>
            <select class="uk-select" name="assignee_id" data-vk-autosubmit>
                <option value=""><?= __('Anyone') ?></option>
                <option value="-1" <?= (int)$assigneeFilter === -1 ? 'selected' : '' ?>><?= __('Unassigned') ?></option>
                <?php foreach ($users as $u): ?>
                <option value="<?= (int)$u['id'] ?>" <?= (int)$assigneeFilter === (int)$u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($assigneeFilter): ?>
            <a class="vk-filter-reset" href="<?= $buildTaskUrl(['assignee_id' => null, 'page' => null]) ?>"><?= __('Clear assignee') ?></a>
            <?php endif; ?>
        </form>
    </div>

    <div class="vk-task-filter-group">
        <div class="vk-task-filter-label"><?= __('Collaborator') ?></div>
        <form method="get" action="<?= $url ?>" class="vk-filter-select-form">
            <input type="hidden" name="view" value="tasks">
            <?php if ($statusFilter): ?><input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>"><?php endif; ?>
            <?php if ($prioFilter): ?><input type="hidden" name="priority" value="<?= htmlspecialchars($prioFilter) ?>"><?php endif; ?>
            <?php if ($searchFilter): ?><input type="hidden" name="q" value="<?= htmlspecialchars($searchFilter) ?>"><?php endif; ?>
            <?php if ($sortFilter !== 'default'): ?><input type="hidden" name="sort" value="<?= htmlspecialchars($sortFilter) ?>"><?php endif; ?>
            <?php if ($assigneeFilter): ?><input type="hidden" name="assignee_id" value="<?= (int)$assigneeFilter ?>"><?php endif; ?>
            <?php if ($reviewerFilter): ?><input type="hidden" name="reviewer_id" value="<?= (int)$reviewerFilter ?>"><?php endif; ?>
            <?php if ($sprintFilter): ?><input type="hidden" name="sprint_id" value="<?= (int)$sprintFilter ?>"><?php endif; ?>
            <?php if ($taskDateState): ?><input type="hidden" name="date_state" value="<?= htmlspecialchars($taskDateState) ?>"><?php endif; ?>
            <?php if ($taskQuarter): ?>
            <input type="hidden" name="quarter" value="<?= (int)$taskQuarter['quarter'] ?>">
            <input type="hidden" name="year" value="<?= (int)$taskQuarter['year'] ?>">
            <?php endif; ?>
            <select class="uk-select" name="collaborator_id" data-vk-autosubmit>
                <option value=""><?= __('Anyone') ?></option>
                <option value="-1" <?= (int)$collaboratorFilter === -1 ? 'selected' : '' ?>><?= __('No collaborator') ?></option>
                <?php foreach ($users as $u): ?>
                <option value="<?= (int)$u['id'] ?>" <?= (int)$collaboratorFilter === (int)$u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($collaboratorFilter): ?>
            <a class="vk-filter-reset" href="<?= $buildTaskUrl(['collaborator_id' => null, 'page' => null]) ?>"><?= __('Clear collaborator') ?></a>
            <?php endif; ?>
        </form>
    </div>

    <div class="vk-task-filter-group">
        <div class="vk-task-filter-label"><?= __('Reviewer') ?></div>
        <form method="get" action="<?= $url ?>" class="vk-filter-select-form">
            <input type="hidden" name="view" value="tasks">
            <?php if ($statusFilter): ?><input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>"><?php endif; ?>
            <?php if ($prioFilter): ?><input type="hidden" name="priority" value="<?= htmlspecialchars($prioFilter) ?>"><?php endif; ?>
            <?php if ($searchFilter): ?><input type="hidden" name="q" value="<?= htmlspecialchars($searchFilter) ?>"><?php endif; ?>
            <?php if ($sortFilter !== 'default'): ?><input type="hidden" name="sort" value="<?= htmlspecialchars($sortFilter) ?>"><?php endif; ?>
            <?php if ($assigneeFilter): ?><input type="hidden" name="assignee_id" value="<?= (int)$assigneeFilter ?>"><?php endif; ?>
            <?php if ($sprintFilter): ?><input type="hidden" name="sprint_id" value="<?= (int)$sprintFilter ?>"><?php endif; ?>
            <?php if ($collaboratorFilter): ?><input type="hidden" name="collaborator_id" value="<?= (int)$collaboratorFilter ?>"><?php endif; ?>
            <?php if ($taskDateState): ?><input type="hidden" name="date_state" value="<?= htmlspecialchars($taskDateState) ?>"><?php endif; ?>
            <?php if ($taskQuarter): ?>
            <input type="hidden" name="quarter" value="<?= (int)$taskQuarter['quarter'] ?>">
            <input type="hidden" name="year" value="<?= (int)$taskQuarter['year'] ?>">
            <?php endif; ?>
            <select class="uk-select" name="reviewer_id" data-vk-autosubmit>
                <option value=""><?= __('Anyone') ?></option>
                <option value="-1" <?= (int)$reviewerFilter === -1 ? 'selected' : '' ?>><?= __('No reviewer') ?></option>
                <?php foreach ($users as $u): ?>
                <option value="<?= (int)$u['id'] ?>" <?= (int)$reviewerFilter === (int)$u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($reviewerFilter): ?>
            <a class="vk-filter-reset" href="<?= $buildTaskUrl(['reviewer_id' => null, 'page' => null]) ?>"><?= __('Clear reviewer') ?></a>
            <?php endif; ?>
        </form>
    </div>

    <div class="vk-task-filter-group">
        <div class="vk-task-filter-label"><?= __('Sprint') ?></div>
        <form method="get" action="<?= $url ?>" class="vk-filter-select-form">
            <input type="hidden" name="view" value="tasks">
            <?php if ($statusFilter): ?><input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>"><?php endif; ?>
            <?php if ($prioFilter): ?><input type="hidden" name="priority" value="<?= htmlspecialchars($prioFilter) ?>"><?php endif; ?>
            <?php if ($searchFilter): ?><input type="hidden" name="q" value="<?= htmlspecialchars($searchFilter) ?>"><?php endif; ?>
            <?php if ($assigneeFilter): ?><input type="hidden" name="assignee_id" value="<?= (int)$assigneeFilter ?>"><?php endif; ?>
            <?php if ($reviewerFilter): ?><input type="hidden" name="reviewer_id" value="<?= (int)$reviewerFilter ?>"><?php endif; ?>
            <?php if ($collaboratorFilter): ?><input type="hidden" name="collaborator_id" value="<?= (int)$collaboratorFilter ?>"><?php endif; ?>
            <?php if ($sortFilter !== 'default'): ?><input type="hidden" name="sort" value="<?= htmlspecialchars($sortFilter) ?>"><?php endif; ?>
            <?php if ($taskDateState): ?><input type="hidden" name="date_state" value="<?= htmlspecialchars($taskDateState) ?>"><?php endif; ?>
            <?php if ($taskQuarter): ?>
            <input type="hidden" name="quarter" value="<?= (int)$taskQuarter['quarter'] ?>">
            <input type="hidden" name="year" value="<?= (int)$taskQuarter['year'] ?>">
            <?php endif; ?>
            <select class="uk-select" name="sprint_id" data-vk-autosubmit>
                <option value=""><?= __('All sprints') ?></option>
                <option value="-1" <?= (int)$sprintFilter === -1 ? 'selected' : '' ?>><?= __('No sprint') ?></option>
                <?php foreach ($sprints as $s): ?>
                <option value="<?= (int)$s['id'] ?>" <?= (int)$sprintFilter === (int)$s['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['name']) ?> · <?= htmlspecialchars($this->sprintStatusLabel((string)$s['status'])) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php if ($sprintFilter): ?>
            <a class="vk-filter-reset" href="<?= $buildTaskUrl(['sprint_id' => null, 'page' => null]) ?>"><?= __('Clear sprint') ?></a>
            <?php endif; ?>
        </form>
    </div>

    <div class="vk-task-filter-group">
        <div class="vk-task-filter-label"><?= __('Sort') ?></div>
        <form method="get" action="<?= $url ?>" class="vk-filter-select-form">
            <input type="hidden" name="view" value="tasks">
            <?php if ($statusFilter): ?><input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>"><?php endif; ?>
            <?php if ($prioFilter): ?><input type="hidden" name="priority" value="<?= htmlspecialchars($prioFilter) ?>"><?php endif; ?>
            <?php if ($searchFilter): ?><input type="hidden" name="q" value="<?= htmlspecialchars($searchFilter) ?>"><?php endif; ?>
            <?php if ($assigneeFilter): ?><input type="hidden" name="assignee_id" value="<?= (int)$assigneeFilter ?>"><?php endif; ?>
            <?php if ($reviewerFilter): ?><input type="hidden" name="reviewer_id" value="<?= (int)$reviewerFilter ?>"><?php endif; ?>
            <?php if ($collaboratorFilter): ?><input type="hidden" name="collaborator_id" value="<?= (int)$collaboratorFilter ?>"><?php endif; ?>
            <?php if ($sprintFilter): ?><input type="hidden" name="sprint_id" value="<?= (int)$sprintFilter ?>"><?php endif; ?>
            <?php if ($taskDateState): ?><input type="hidden" name="date_state" value="<?= htmlspecialchars($taskDateState) ?>"><?php endif; ?>
            <?php if ($taskQuarter): ?>
            <input type="hidden" name="quarter" value="<?= (int)$taskQuarter['quarter'] ?>">
            <input type="hidden" name="year" value="<?= (int)$taskQuarter['year'] ?>">
            <?php endif; ?>
            <select class="uk-select" name="sort" data-vk-autosubmit>
                <?php foreach ($sortLabels as $sortValue => $sortLabel): ?>
                <option value="<?= htmlspecialchars($sortValue) ?>" <?= $sortFilter === $sortValue ? 'selected' : '' ?>><?= htmlspecialchars($sortLabel) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($sortFilter !== 'default'): ?>
            <a class="vk-filter-reset" href="<?= $buildTaskUrl(['sort' => null, 'page' => null]) ?>"><?= __('Reset sort') ?></a>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if ($activeFilters): ?>
<div class="vk-active-filters">
    <span class="vk-active-filters-label"><?= __('Active filters') ?></span>
    <?php foreach ($activeFilters as $filter): ?>
    <a class="vk-active-filter-chip" href="<?= $filter['href'] ?>">
        <?= htmlspecialchars($filter['label']) ?>
        <i class="fa fa-times"></i>
    </a>
    <?php endforeach; ?>
    <a class="vk-active-filter-clear" href="<?= $url ?>?view=tasks"><?= __('Clear all') ?></a>
</div>
<?php endif; ?>

<div class="vk-quarter-overview">
    <div class="vk-quarter-overview-head">
        <span><?= __('Quarter load') ?></span>
        <form method="get" action="<?= $url ?>" class="vk-quarter-year">
            <input type="hidden" name="view" value="tasks">
            <?php if ($statusFilter): ?><input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>"><?php endif; ?>
            <?php if ($prioFilter): ?><input type="hidden" name="priority" value="<?= htmlspecialchars($prioFilter) ?>"><?php endif; ?>
            <?php if ($searchFilter): ?><input type="hidden" name="q" value="<?= htmlspecialchars($searchFilter) ?>"><?php endif; ?>
            <?php if ($assigneeFilter): ?><input type="hidden" name="assignee_id" value="<?= (int)$assigneeFilter ?>"><?php endif; ?>
            <?php if ($reviewerFilter): ?><input type="hidden" name="reviewer_id" value="<?= (int)$reviewerFilter ?>"><?php endif; ?>
            <?php if ($collaboratorFilter): ?><input type="hidden" name="collaborator_id" value="<?= (int)$collaboratorFilter ?>"><?php endif; ?>
            <?php if ($sortFilter !== 'default'): ?><input type="hidden" name="sort" value="<?= htmlspecialchars($sortFilter) ?>"><?php endif; ?>
            <?php if ($sprintFilter): ?><input type="hidden" name="sprint_id" value="<?= (int)$sprintFilter ?>"><?php endif; ?>
            <?php if ($taskDateState): ?><input type="hidden" name="date_state" value="<?= htmlspecialchars($taskDateState) ?>"><?php endif; ?>
            <?php if ($taskQuarter): ?><input type="hidden" name="quarter" value="<?= (int)$taskQuarter['quarter'] ?>"><?php endif; ?>
            <input class="uk-input" type="number" name="year" min="2000" max="2100" value="<?= (int)$taskQuarterYear ?>" aria-label="<?= __('Year') ?>">
        </form>
    </div>
    <div class="vk-quarter-overview-grid">
        <?php for ($q = 1; $q <= 4; $q++):
            $ctx = $taskQuarterCounts[$q]['context'] ?? $this->quarterContext($q, (int)$taskQuarterYear);
            $count = (int)($taskQuarterCounts[$q]['count'] ?? 0);
            $active = $taskQuarter && (int)$taskQuarter['quarter'] === $q && (int)$taskQuarter['year'] === (int)$taskQuarterYear;
        ?>
        <a class="vk-quarter-card <?= $active ? 'is-active' : '' ?>" href="<?= $buildTaskUrl(['quarter' => $q, 'year' => (int)$taskQuarterYear, 'date_state' => null, 'page' => null]) ?>">
            <span><?= htmlspecialchars($this->quarterLabel($ctx)) ?></span>
            <strong><?= $count ?></strong>
            <small><?= htmlspecialchars(date('M j', strtotime($ctx['start'])) . ' - ' . date('M j', strtotime($ctx['end']))) ?></small>
        </a>
        <?php endfor; ?>
        <a class="vk-quarter-card is-muted <?= $taskDateState === 'none' ? 'is-active' : '' ?>" href="<?= $buildTaskUrl(['quarter' => null, 'year' => null, 'date_state' => 'none', 'page' => null]) ?>">
            <span><?= __('No due date') ?></span>
            <strong><?= (int)$taskNoDueCount ?></strong>
            <small><?= __('Backlog') ?></small>
        </a>
    </div>
</div>

<?php if ($tasks): ?>
<div class="uk-card uk-card-default vk-task-list-card">
    <div class="vk-issue-list-head">
        <span><?= __('Issue') ?></span>
        <span><?= __('State') ?></span>
    </div>
    <div class="vk-issue-row-list">
        <?php foreach ($tasks as $t): ?>
        <?php $taskDescription = trim(mb_strimwidth(strip_tags((string)($t['description'] ?? '')), 0, 150, '...')); ?>
        <article class="vk-issue-row">
            <div class="vk-issue-row-main">
                <div class="vk-issue-title-line">
                    <span class="vk-issue-key"><?= __('TASK') ?>-<?= (int)$t['id'] ?></span>
                    <a href="<?= $url ?>?view=task-edit&id=<?= (int)$t['id'] ?>&return_url=<?= $taskListReturnParam ?>" class="vk-issue-row-title"><?= htmlspecialchars((string)$t['title']) ?></a>
                </div>
                <?php if ($taskDescription): ?>
                <div class="vk-issue-description"><?= htmlspecialchars($taskDescription) ?></div>
                <?php endif; ?>
                <div class="vk-issue-row-meta">
                    <?php if ($t['section']): ?><span><?= htmlspecialchars($t['section']) ?></span><?php endif; ?>
                    <span class="<?= $t['assignee_name'] ? '' : 'is-muted' ?>"><i class="fa fa-user-o"></i> <?= $t['assignee_name'] ? htmlspecialchars($t['assignee_name']) : __('Unassigned') ?></span>
                    <?php if ($t['sprint_name']): ?><span><i class="fa fa-bolt"></i> <?= htmlspecialchars($t['sprint_name']) ?></span><?php endif; ?>
                    <?php if (!empty($t['linked_page_title'])): $dlp = $this->pageStatusDisplay($t['linked_page_status'] ?? []); ?><span><a href="<?= $t['linked_page_edit'] ?>" class="vk-inline-page-link <?= $dlp['class'] ?>" target="_blank"<?= $dlp['label'] !== '' ? ' title="' . htmlspecialchars($dlp['label']) . '"' : '' ?>><i class="fa fa-pencil-square-o"></i> <?= $dlp['icon'] ?><?= htmlspecialchars(mb_strimwidth((string)$t['linked_page_title'], 0, 34, '...')) ?></a></span><?php endif; ?>
                    <span class="<?= ($t['due_date'] && $t['due_date'] < $today && $t['status'] !== 'done') ? 'is-overdue' : (!$t['due_date'] ? 'is-muted' : '') ?>"><i class="fa fa-calendar-o"></i> <?= $t['due_date'] ? htmlspecialchars($t['due_date']) : __('No due date') ?></span>
                    <?php if ($t['due_date']): ?><span class="vk-quarter-inline"><?= htmlspecialchars($this->quarterLabelForDate($t['due_date'])) ?></span><?php endif; ?>
                </div>
            </div>
            <div class="vk-issue-row-side">
                <span class="uk-label vk-label vk-label-<?= $t['priority'] ?>"><?= htmlspecialchars($this->priorityLabel($t['priority'])) ?></span>
                <span class="uk-label vk-label vk-label-<?= $t['status'] ?>"><?= htmlspecialchars($this->statusLabel($t['status'])) ?></span>
                <?php if ($t['story_points']): ?><span class="vk-sprint-pill"><?= (int)$t['story_points'] ?> <?= __('SP') ?></span><?php endif; ?>
                <?php $estD = $this->formatEstimate($t['estimate_h']); if($estD !== ''): ?><span class="vk-sprint-pill"><?= htmlspecialchars($estD) ?></span><?php endif; ?>
                <?php if($t['actual_h'] !== null && $t['actual_h'] !== ''): ?><span class="vk-sprint-pill <?= ($t['estimate_h'] && $t['actual_h'] > $t['estimate_h']) ? 'is-over' : '' ?>"><?= number_format((float)$t['actual_h'],1) ?>h</span><?php endif; ?>
                <?php if (!empty($t['linked_page_edit'])): ?><a href="<?= $t['linked_page_edit'] ?>" class="vk-icon-button" target="_blank" title="<?= __('Edit linked page') ?>" aria-label="<?= __('Edit linked page') ?>"><i class="fa fa-pencil-square-o"></i></a><?php endif; ?>
                <?php if (!empty($t['linked_page_url']) && !empty($t['linked_page_viewable'])): ?><a href="<?= $t['linked_page_url'] ?>" class="vk-icon-button" target="_blank" title="<?= __('Open page') ?>" aria-label="<?= __('Open page') ?>"><i class="fa fa-external-link"></i></a><?php endif; ?>
                <a href="<?= $url ?>?view=task-edit&id=<?= (int)$t['id'] ?>&return_url=<?= $taskListReturnParam ?>" class="vk-icon-button" title="<?= __('Edit') ?>" aria-label="<?= __('Edit') ?>"><i class="fa fa-pencil"></i></a>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php if ($totalPages > 1): ?>
    <div class="vk-pagination-wrap">
        <ul class="uk-pagination">
            <?php
            $buildUrl = function($p) use ($url, $statusFilter, $prioFilter, $searchFilter, $sortFilter, $assigneeFilter, $reviewerFilter, $collaboratorFilter, $sprintFilter, $taskQuarter, $taskDateState) {
                $parts = "view=tasks&page=$p";
                if ($statusFilter) $parts .= "&status=$statusFilter";
                if ($prioFilter)   $parts .= "&priority=$prioFilter";
                if ($searchFilter) $parts .= "&q=" . urlencode($searchFilter);
                if ($sortFilter !== 'default') $parts .= "&sort=" . urlencode($sortFilter);
                if ($assigneeFilter) $parts .= "&assignee_id=$assigneeFilter";
                if ($reviewerFilter) $parts .= "&reviewer_id=$reviewerFilter";
                if ($collaboratorFilter) $parts .= "&collaborator_id=$collaboratorFilter";
                if ($sprintFilter) $parts .= "&sprint_id=$sprintFilter";
                if ($taskQuarter)  $parts .= "&quarter=" . (int)$taskQuarter['quarter'] . "&year=" . (int)$taskQuarter['year'];
                if ($taskDateState) $parts .= "&date_state=" . urlencode($taskDateState);
                return $url . '?' . $parts;
            };
            ?>
            <li class="vk-pagination-summary"><?= sprintf(__('%d tasks'), $total) ?></li>
            <?php if ($pageNum > 1): ?>
            <li><a href="<?= $buildUrl($pageNum - 1) ?>"><span uk-pagination-previous></span></a></li>
            <?php endif; ?>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <li class="<?= $p === $pageNum ? 'uk-active' : '' ?>"><a href="<?= $buildUrl($p) ?>"><?= $p ?></a></li>
            <?php endfor; ?>
            <?php if ($pageNum < $totalPages): ?>
            <li><a href="<?= $buildUrl($pageNum + 1) ?>"><span uk-pagination-next></span></a></li>
            <?php endif; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="vk-empty vk-empty-panel">
    <i class="fa fa-inbox vk-empty-icon"></i>
    <div class="vk-empty-title"><?= __('No tasks match this filter.') ?></div>
    <p class="vk-muted-line"><?= __('Clear filters or create a task for the current context.') ?></p>
    <div class="vk-empty-actions">
        <a href="<?= $url ?>?view=tasks" class="uk-button uk-button-default uk-button-small"><i class="fa fa-times"></i> <?= __('Clear filters') ?></a>
        <a href="<?= $url ?>?view=task-edit&return_url=<?= $taskListReturnParam ?>" class="uk-button uk-button-primary uk-button-small"><i class="fa fa-plus"></i> <?= __('New Task') ?></a>
    </div>
</div>
<?php endif; ?>

<script>
(function() {
    document.querySelectorAll('select[data-vk-autosubmit]').forEach(function(sel) {
        sel.addEventListener('change', function() { if (sel.form) sel.form.submit(); });
    });
})();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/_layout.php';

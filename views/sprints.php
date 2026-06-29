<?php namespace ProcessWire;
/**
 * @var Verk $this
 * @var array $sprints
 */
$url   = $this->page->url;
$csrf  = $this->getCSRFToken();
$csrfN = $this->getCSRFName();
$today = date('Y-m-d');
$sprintQuarter = $sprintQuarter ?? null;
$sprintQuarterYear = $sprintQuarterYear ?? ($sprintQuarter ? (int)$sprintQuarter['year'] : (int)$this->quarterContextForDate(date('Y-m-d'))['year']);
$sprintQuarterCounts = $sprintQuarterCounts ?? [];
$sprintNoDateCount = $sprintNoDateCount ?? 0;
$sprintBacklogCount = $sprintBacklogCount ?? 0;
$sprintStats = $sprintStats ?? ['active' => 0, 'planned' => 0, 'completed' => 0, 'tasks' => 0, 'done_tasks' => 0, 'story_points' => 0, 'estimate_h' => 0, 'actual_h' => 0, 'needs_attention' => 0];
$sprintStatusCounts = $sprintStatusCounts ?? ['active' => (int)$sprintStats['active'], 'planned' => (int)$sprintStats['planned'], 'completed' => (int)$sprintStats['completed']];
$sprintTaskPreview = $sprintTaskPreview ?? [];
$sprintTaskProgress = (int)$sprintStats['tasks'] > 0 ? (int)round((int)$sprintStats['done_tasks'] / (int)$sprintStats['tasks'] * 100) : 0;
$input = $this->wire('input');
$sprintSearch = $sprintSearch ?? trim($input->get('q', 'string') ?: '');
$sprintStatus = $sprintStatus ?? $input->get('status', 'string');
if (!in_array($sprintStatus, ['planned', 'active', 'completed'], true)) $sprintStatus = '';
$sprintHealth = $sprintHealth ?? $input->get('health', 'string');
if (!in_array($sprintHealth, ['attention'], true)) $sprintHealth = '';
$sprintDateState = $sprintDateState ?? $input->get('date_state', 'string');
if (!in_array($sprintDateState, ['none'], true)) $sprintDateState = '';
$hasSprintFilters = $sprintSearch !== '' || $sprintStatus || $sprintHealth || $sprintDateState || $sprintQuarter;
$yearForQuarters = (int)$sprintQuarterYear;
$buildSprintUrl = function(array $changes = []) use ($url, $sprintQuarter, $yearForQuarters, $sprintSearch, $sprintStatus, $sprintHealth, $sprintDateState) {
    $params = ['view' => 'sprints'];
    if ($sprintQuarter) {
        $params['quarter'] = (int)$sprintQuarter['quarter'];
        $params['year'] = (int)$sprintQuarter['year'];
    } elseif ($yearForQuarters) {
        $params['year'] = (int)$yearForQuarters;
    }
    if ($sprintSearch !== '') $params['q'] = $sprintSearch;
    if ($sprintStatus) $params['status'] = $sprintStatus;
    if ($sprintHealth) $params['health'] = $sprintHealth;
    if ($sprintDateState) $params['date_state'] = $sprintDateState;
    foreach ($changes as $key => $value) {
        if ($value === null || $value === '' || $value === 0) unset($params[$key]);
        else $params[$key] = $value;
    }
    return $url . '?' . http_build_query($params);
};
$currentSprintUrl = $buildSprintUrl([]);
$currentSprintReturn = rawurlencode($currentSprintUrl);
$currentQuarterContext = $this->quarterContextForDate(date('Y-m-d'));
$currentQuarterLabel = $this->quarterLabel($currentQuarterContext);
$currentQuarterPlanParams = '&plan_quarter=' . (int)$currentQuarterContext['quarter'] . '&plan_year=' . (int)$currentQuarterContext['year'];
$newSprintPlanParams = $sprintQuarter ? '&plan_quarter=' . (int)$sprintQuarter['quarter'] . '&plan_year=' . (int)$sprintQuarter['year'] : '';
$nextQuarterNumber = (int)$currentQuarterContext['quarter'] === 4 ? 1 : (int)$currentQuarterContext['quarter'] + 1;
$nextQuarterYear = (int)$currentQuarterContext['quarter'] === 4 ? (int)$currentQuarterContext['year'] + 1 : (int)$currentQuarterContext['year'];
$nextQuarterContext = $this->quarterContext($nextQuarterNumber, $nextQuarterYear);
$nextQuarterLabel = $this->quarterLabel($nextQuarterContext);
$nextQuarterPlanParams = '&plan_quarter=' . $nextQuarterNumber . '&plan_year=' . $nextQuarterYear;

ob_start();
?>

<div class="vk-page-head">
    <div>
        <h2 class="vk-page-title"><?= __('Sprints') ?></h2>
        <p><?= __('Plan delivery windows, group tasks, and track sprint progress.') ?><?= $sprintQuarter ? ' · ' . htmlspecialchars($this->quarterLabel($sprintQuarter)) : '' ?></p>
    </div>
    <div class="vk-actions">
        <a href="<?= $url ?>?view=sprint-edit&return_url=<?= $currentSprintReturn ?><?= $newSprintPlanParams ?>" class="uk-button uk-button-primary"><i class="fa fa-plus"></i> <?= __('New Sprint') ?></a>
    </div>
</div>

<div class="vk-sprint-summary-grid">
    <a href="<?= $buildSprintUrl(['status' => $sprintStatus === 'active' ? null : 'active']) ?>" class="vk-sprint-summary-card <?= $sprintStatus === 'active' ? 'is-active' : '' ?>">
        <span><?= __('Active') ?></span>
        <strong><?= (int)$sprintStatusCounts['active'] ?></strong>
        <small><?= __('Running delivery windows') ?></small>
    </a>
    <a href="<?= $buildSprintUrl(['status' => $sprintStatus === 'planned' ? null : 'planned']) ?>" class="vk-sprint-summary-card <?= $sprintStatus === 'planned' ? 'is-active' : '' ?>">
        <span><?= __('Planned') ?></span>
        <strong><?= (int)$sprintStatusCounts['planned'] ?></strong>
        <small><?= __('Upcoming work') ?></small>
    </a>
    <a href="<?= $buildSprintUrl(['status' => $sprintStatus === 'completed' ? null : 'completed']) ?>" class="vk-sprint-summary-card <?= $sprintStatus === 'completed' ? 'is-active' : '' ?>">
        <span><?= __('Completed') ?></span>
        <strong><?= (int)$sprintStatusCounts['completed'] ?></strong>
        <small><?= __('Closed delivery windows') ?></small>
    </a>
    <div class="vk-sprint-summary-card">
        <span><?= __('Task progress') ?></span>
        <strong><?= $sprintTaskProgress ?>%</strong>
        <small><?= sprintf(__('%1$d/%2$d tasks done'), (int)$sprintStats['done_tasks'], (int)$sprintStats['tasks']) ?></small>
    </div>
    <div class="vk-sprint-summary-card">
        <span><?= __('Capacity') ?></span>
        <strong><?= number_format((float)$sprintStats['actual_h'], 1) ?>h</strong>
        <small><?= sprintf(__('%s estimated'), number_format((float)$sprintStats['estimate_h'], 1) . 'h') ?></small>
    </div>
    <a href="<?= $buildSprintUrl(['health' => $sprintHealth === 'attention' ? null : 'attention']) ?>" class="vk-sprint-summary-card <?= (int)$sprintStats['needs_attention'] > 0 ? 'is-warning' : 'is-success' ?> <?= $sprintHealth === 'attention' ? 'is-active' : '' ?>">
        <span><?= __('Needs attention') ?></span>
        <strong><?= (int)$sprintStats['needs_attention'] ?></strong>
        <small><?= __('Missing dates, goals, tasks, or overdue') ?></small>
    </a>
    <a href="<?= $url ?>?view=tasks&sprint_id=-1" class="vk-sprint-summary-card <?= (int)$sprintBacklogCount > 0 ? 'is-warning' : 'is-success' ?>">
        <span><?= __('Backlog') ?></span>
        <strong><?= (int)$sprintBacklogCount ?></strong>
        <small><?= __('Tasks without sprint') ?></small>
    </a>
</div>

<div class="vk-search-toolbar vk-sprint-toolbar">
    <form method="get" action="<?= $url ?>" class="vk-task-search-form">
        <input type="hidden" name="view" value="sprints">
        <?php if ($sprintQuarter): ?>
        <input type="hidden" name="quarter" value="<?= (int)$sprintQuarter['quarter'] ?>">
        <input type="hidden" name="year" value="<?= (int)$sprintQuarter['year'] ?>">
        <?php endif; ?>
        <?php if ($sprintStatus): ?><input type="hidden" name="status" value="<?= htmlspecialchars($sprintStatus) ?>"><?php endif; ?>
        <?php if ($sprintHealth): ?><input type="hidden" name="health" value="<?= htmlspecialchars($sprintHealth) ?>"><?php endif; ?>
        <?php if ($sprintDateState): ?><input type="hidden" name="date_state" value="<?= htmlspecialchars($sprintDateState) ?>"><?php endif; ?>
        <div class="vk-task-search-control">
            <i class="fa fa-search"></i>
            <input class="uk-input" type="search" name="q" value="<?= htmlspecialchars($sprintSearch) ?>" placeholder="<?= __('Search sprint name or goal') ?>">
            <?php if ($sprintSearch !== ''): ?><a href="<?= $buildSprintUrl(['q' => null]) ?>" class="vk-filter-reset"><?= __('Clear') ?></a><?php endif; ?>
            <button class="uk-button uk-button-default uk-button-small"><?= __('Search') ?></button>
        </div>
    </form>
</div>

<div class="vk-task-filters vk-filter-panel vk-sprint-filter-panel">
    <div class="vk-task-filter-group is-tabs">
        <div class="vk-task-filter-label"><?= __('Quarter') ?></div>
        <ul class="uk-subnav uk-subnav-pill vk-view-switcher vk-task-filter-tabs">
            <li class="<?= !$sprintQuarter && $sprintDateState !== 'none' ? 'uk-active' : '' ?>"><a href="<?= $buildSprintUrl(['quarter' => null, 'year' => null, 'date_state' => null]) ?>"><?= __('All quarters') ?></a></li>
            <?php for ($q = 1; $q <= 4; $q++): ?>
            <li class="<?= $sprintQuarter && (int)$sprintQuarter['quarter'] === $q ? 'uk-active' : '' ?>"><a href="<?= $buildSprintUrl(['quarter' => $q, 'year' => $yearForQuarters, 'date_state' => null]) ?>">Q<?= $q ?></a></li>
            <?php endfor; ?>
            <li class="<?= $sprintDateState === 'none' ? 'uk-active' : '' ?>"><a href="<?= $buildSprintUrl(['quarter' => null, 'year' => null, 'date_state' => 'none']) ?>"><?= __('Missing dates') ?></a></li>
        </ul>
    </div>
    <div class="vk-task-filter-group is-tabs">
        <div class="vk-task-filter-label"><?= __('Status') ?></div>
        <ul class="uk-subnav uk-subnav-pill vk-view-switcher vk-task-filter-tabs">
            <li class="<?= !$sprintStatus ? 'uk-active' : '' ?>"><a href="<?= $buildSprintUrl(['status' => null]) ?>"><?= __('All statuses') ?></a></li>
            <?php foreach (['planned','active','completed'] as $statusOption): ?>
            <li class="<?= $sprintStatus === $statusOption ? 'uk-active' : '' ?>"><a href="<?= $buildSprintUrl(['status' => $statusOption]) ?>"><?= htmlspecialchars($this->sprintStatusLabel($statusOption)) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="vk-task-filter-group">
        <div class="vk-task-filter-label"><?= __('Planning year') ?></div>
        <form method="get" action="<?= $url ?>" class="vk-quarter-year">
            <input type="hidden" name="view" value="sprints">
            <?php if ($sprintQuarter): ?><input type="hidden" name="quarter" value="<?= (int)$sprintQuarter['quarter'] ?>"><?php endif; ?>
            <?php if ($sprintSearch !== ''): ?><input type="hidden" name="q" value="<?= htmlspecialchars($sprintSearch) ?>"><?php endif; ?>
            <?php if ($sprintStatus): ?><input type="hidden" name="status" value="<?= htmlspecialchars($sprintStatus) ?>"><?php endif; ?>
            <?php if ($sprintHealth): ?><input type="hidden" name="health" value="<?= htmlspecialchars($sprintHealth) ?>"><?php endif; ?>
            <?php if ($sprintDateState): ?><input type="hidden" name="date_state" value="<?= htmlspecialchars($sprintDateState) ?>"><?php endif; ?>
            <input class="uk-input" type="number" name="year" min="2000" max="2100" value="<?= $yearForQuarters ?>" aria-label="<?= __('Year') ?>">
        </form>
    </div>
</div>

<?php if ($sprintSearch !== '' || $sprintStatus || $sprintHealth || $sprintDateState || $sprintQuarter): ?>
<div class="vk-active-filters">
    <span class="vk-active-filters-label"><?= __('Active filters') ?></span>
    <?php if ($sprintSearch !== ''): ?>
    <a class="vk-active-filter-chip" href="<?= $buildSprintUrl(['q' => null]) ?>"><?= sprintf(__('Search: %s'), htmlspecialchars($sprintSearch)) ?> <i class="fa fa-times"></i></a>
    <?php endif; ?>
    <?php if ($sprintStatus): ?>
    <a class="vk-active-filter-chip" href="<?= $buildSprintUrl(['status' => null]) ?>"><?= sprintf(__('Status: %s'), htmlspecialchars($this->sprintStatusLabel($sprintStatus))) ?> <i class="fa fa-times"></i></a>
    <?php endif; ?>
    <?php if ($sprintHealth): ?>
    <a class="vk-active-filter-chip" href="<?= $buildSprintUrl(['health' => null]) ?>"><?= __('Needs attention') ?> <i class="fa fa-times"></i></a>
    <?php endif; ?>
    <?php if ($sprintDateState): ?>
    <a class="vk-active-filter-chip" href="<?= $buildSprintUrl(['date_state' => null]) ?>"><?= __('Missing dates') ?> <i class="fa fa-times"></i></a>
    <?php endif; ?>
    <?php if ($sprintQuarter): ?>
    <a class="vk-active-filter-chip" href="<?= $buildSprintUrl(['quarter' => null, 'year' => null]) ?>"><?= sprintf(__('Quarter: %s'), htmlspecialchars($this->quarterLabel($sprintQuarter))) ?> <i class="fa fa-times"></i></a>
    <?php endif; ?>
    <a class="vk-active-filter-clear" href="<?= $url ?>?view=sprints"><?= __('Clear all') ?></a>
</div>
<?php endif; ?>

<div class="vk-quarter-overview">
    <div class="vk-quarter-overview-head">
        <span><?= __('Quarter plan') ?></span>
    </div>
    <div class="vk-quarter-overview-grid">
        <?php for ($q = 1; $q <= 4; $q++):
            $ctx = $sprintQuarterCounts[$q]['context'] ?? $this->quarterContext($q, $yearForQuarters);
            $count = (int)($sprintQuarterCounts[$q]['count'] ?? 0);
            $active = $sprintQuarter && (int)$sprintQuarter['quarter'] === $q && (int)$sprintQuarter['year'] === $yearForQuarters;
        ?>
        <a class="vk-quarter-card <?= $active ? 'is-active' : '' ?>" href="<?= $buildSprintUrl(['quarter' => $q, 'year' => $yearForQuarters, 'date_state' => null]) ?>">
            <span><?= htmlspecialchars($this->quarterLabel($ctx)) ?></span>
            <strong><?= $count ?></strong>
            <small><?= htmlspecialchars(date('M j', strtotime($ctx['start'])) . ' - ' . date('M j', strtotime($ctx['end']))) ?></small>
        </a>
        <?php endfor; ?>
        <a class="vk-quarter-card is-muted <?= $sprintDateState === 'none' ? 'is-active' : '' ?>" href="<?= $buildSprintUrl(['quarter' => null, 'year' => null, 'date_state' => $sprintDateState === 'none' ? null : 'none']) ?>">
            <span><?= __('Missing dates') ?></span>
            <strong><?= (int)$sprintNoDateCount ?></strong>
            <small><?= __('Planning backlog') ?></small>
        </a>
    </div>
</div>

<?php if ($sprintDateState === 'none'): ?>
<?php $firstNoDateSprint = $sprints[0] ?? null; ?>
<div class="vk-calendar-notice vk-sprint-context-notice">
    <i class="fa fa-calendar-o"></i>
    <div>
        <div class="vk-calendar-notice-title"><?= __('These sprints need a complete delivery window') ?></div>
        <p>
            <?= __('Set start and end dates to place a sprint into a quarter, show its timeline, and make calendar planning clearer.') ?>
            <?php if ($firstNoDateSprint): ?>
            <?php endif; ?>
        </p>
        <?php if ($firstNoDateSprint): ?>
        <div class="vk-sprint-notice-actions">
            <a href="<?= $url ?>?view=sprint-edit&id=<?= (int)$firstNoDateSprint['id'] ?>&return_url=<?= $currentSprintReturn ?>#vk-sprint-schedule"><i class="fa fa-calendar-o"></i> <?= __('Set dates') ?></a>
            <a href="<?= $url ?>?view=sprint-edit&id=<?= (int)$firstNoDateSprint['id'] ?>&return_url=<?= $currentSprintReturn ?><?= $currentQuarterPlanParams ?>#vk-sprint-schedule"><i class="fa fa-calendar-check-o"></i> <?= sprintf(__('Plan %s'), htmlspecialchars($currentQuarterLabel)) ?></a>
            <a href="<?= $url ?>?view=sprint-edit&id=<?= (int)$firstNoDateSprint['id'] ?>&return_url=<?= $currentSprintReturn ?><?= $nextQuarterPlanParams ?>#vk-sprint-schedule"><i class="fa fa-calendar-plus-o"></i> <?= sprintf(__('Plan %s'), htmlspecialchars($nextQuarterLabel)) ?></a>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if ($sprints): ?>

<?php foreach (['active','planned','completed'] as $group):
    $groupSprints = array_filter($sprints, fn($s) => $s['status'] === $group);
    if (!$groupSprints) continue;
    $groupTaskCount = array_sum(array_map(fn($s) => (int)($s['task_count'] ?? 0), $groupSprints));
    $groupDoneCount = array_sum(array_map(fn($s) => (int)($s['done_count'] ?? 0), $groupSprints));
    $groupEstimate = array_sum(array_map(fn($s) => (float)($s['total_est'] ?? 0), $groupSprints));
    $groupActual = array_sum(array_map(fn($s) => (float)($s['total_actual'] ?? 0), $groupSprints));
?>
<div class="vk-sprint-group-head">
    <div class="vk-section-label"><?= htmlspecialchars($this->sprintStatusLabel($group)) ?></div>
    <div class="vk-sprint-group-meta">
        <span><?= sprintf(_n('%d sprint', '%d sprints', count($groupSprints)), count($groupSprints)) ?></span>
        <span><?= sprintf(__('%1$d/%2$d tasks done'), $groupDoneCount, $groupTaskCount) ?></span>
        <span><?= number_format($groupActual, 1) ?>h / <?= number_format($groupEstimate, 1) ?>h</span>
    </div>
</div>
<div class="vk-sprints-list">
<?php foreach ($groupSprints as $s):
    $total    = (int)$s['task_count'];
    $done     = (int)$s['done_count'];
    $pct      = $total > 0 ? round($done / $total * 100) : 0;
    $estimateH = (float)($s['total_est'] ?? 0);
    $actualH = (float)($s['total_actual'] ?? 0);
    $capacityPct = $estimateH > 0 ? min(100, (int)round($actualH / $estimateH * 100)) : 0;
    $isOverdue = $s['end_date'] && $s['end_date'] < $today && $s['status'] !== 'completed';
    $healthItems = [];
    if (!$s['start_date'] || !$s['end_date']) $healthItems[] = ['label' => __('Dates missing'), 'class' => 'is-warning'];
    if (!trim(strip_tags((string)$s['goal']))) $healthItems[] = ['label' => __('Goal missing'), 'class' => 'is-muted'];
    if ($total === 0) $healthItems[] = ['label' => __('No tasks'), 'class' => 'is-warning'];
    if ($isOverdue) $healthItems[] = ['label' => __('Overdue'), 'class' => 'is-danger'];
    if (!$healthItems) $healthItems[] = ['label' => __('Ready'), 'class' => 'is-ready'];
    $daysLabel = __('Missing dates');
    $daysClass = 'is-muted';
    if ($s['end_date']) {
        $days = (int)floor((strtotime($s['end_date']) - strtotime($today)) / 86400);
        if ($s['status'] === 'completed') {
            $daysLabel = __('Completed');
            $daysClass = 'is-done';
        } elseif ($days < 0) {
            $daysLabel = sprintf(_n('%d day overdue', '%d days overdue', abs($days)), abs($days));
            $daysClass = 'is-overdue';
        } elseif ($days === 0) {
            $daysLabel = __('Due today');
            $daysClass = 'is-warning';
        } else {
            $daysLabel = sprintf(_n('%d day left', '%d days left', $days), $days);
            $daysClass = '';
        }
    }
    $timelinePct = null;
    if (!empty($s['start_date']) && !empty($s['end_date'])) {
        $startTs = strtotime((string)$s['start_date']);
        $endTs = strtotime((string)$s['end_date']);
        $todayTs = strtotime($today);
        if ($startTs !== false && $endTs !== false && $endTs >= $startTs) {
            $span = max(1, $endTs - $startTs);
            $timelinePct = max(0, min(100, (int)round(($todayTs - $startTs) / $span * 100)));
        }
    }
    $hasPlanActions = !$s['start_date'] || !$s['end_date'] || in_array($s['status'], ['planned', 'active'], true);
?>
<div class="uk-card uk-card-default vk-sprint-board-card">
    <div class="uk-card-body vk-sprint-board-row">
        <div>
            <div class="vk-sprint-heading">
                <span class="vk-sprint-dot is-<?= htmlspecialchars($s['status']) ?>"></span>
                <a href="<?= $url ?>?view=sprint-edit&id=<?= (int)$s['id'] ?>&return_url=<?= $currentSprintReturn ?>" class="vk-sprint-name"><?= htmlspecialchars($s['name']) ?></a>
                <span class="uk-label vk-label-<?= $s['status'] === 'active' ? 'done' : 'open' ?>"><?= htmlspecialchars($this->sprintStatusLabel($s['status'])) ?></span>
                <?php $qLabel = $this->quarterLabelForRange($s['start_date'] ?? '', $s['end_date'] ?? ''); ?>
                <?php if ($qLabel): ?><span class="vk-quarter-badge"><?= htmlspecialchars($qLabel) ?></span><?php endif; ?>
                <span class="vk-sprint-days <?= $daysClass ?>"><?= htmlspecialchars($daysLabel) ?></span>
            </div>
            <?php if ($s['start_date'] || $s['end_date']): ?>
            <div class="vk-sprint-date">
                <?= $s['start_date'] ?: '?' ?> - <?= $s['end_date'] ? '<span class="' . ($isOverdue ? 'vk-text-danger' : '') . '">'.$s['end_date'].'</span>' : '?' ?>
            </div>
            <?php endif; ?>
            <?php if ($timelinePct !== null): ?>
            <div class="vk-sprint-timeline" aria-label="<?= __('Sprint timeline') ?>" style="--vk-progress: <?= $timelinePct ?>%">
                <div class="vk-sprint-timeline-track">
                    <span class="vk-sprint-timeline-fill"></span>
                    <span class="vk-sprint-timeline-marker"></span>
                </div>
                <div class="vk-sprint-timeline-labels">
                    <span><?= htmlspecialchars(date('M j', strtotime((string)$s['start_date']))) ?></span>
                    <span><?= $timelinePct ?>%</span>
                    <span><?= htmlspecialchars(date('M j', strtotime((string)$s['end_date']))) ?></span>
                </div>
            </div>
            <?php endif; ?>
            <div class="vk-sprint-health">
                <?php foreach ($healthItems as $healthItem): ?>
                <?php
                    $healthHref = $url . '?view=sprint-edit&id=' . (int)$s['id'] . '&return_url=' . $currentSprintReturn;
                    if ($healthItem['label'] === __('Dates missing')) $healthHref .= '#vk-sprint-schedule';
                    if ($healthItem['label'] === __('Goal missing')) $healthHref .= '#vk-sprint-goal';
                    if ($healthItem['label'] === __('No tasks')) $healthHref = $url . '?view=task-edit&sprint_id=' . (int)$s['id'] . '&return_url=' . $currentSprintReturn;
                ?>
                <a href="<?= $healthHref ?>" class="<?= htmlspecialchars($healthItem['class']) ?>"><?= htmlspecialchars($healthItem['label']) ?></a>
                <?php endforeach; ?>
            </div>
            <?php if ($s['goal']): ?>
            <div class="vk-sprint-goal-preview vk-rich-text"><?= $this->renderRichText($s['goal']) ?></div>
            <?php endif; ?>
            <?php $previewTasks = $sprintTaskPreview[(int)$s['id']] ?? []; ?>
            <?php if ($previewTasks): ?>
            <div class="vk-sprint-task-preview">
                <?php foreach ($previewTasks as $previewTask): ?>
                <a href="<?= $url ?>?view=task-edit&id=<?= (int)$previewTask['id'] ?>&return_url=<?= $currentSprintReturn ?>" class="vk-sprint-task-chip is-<?= htmlspecialchars((string)$previewTask['status']) ?>">
                    <span class="vk-sprint-task-key"><?= __('TASK') ?>-<?= (int)$previewTask['id'] ?></span>
                    <span class="vk-sprint-task-title"><?= htmlspecialchars((string)$previewTask['title']) ?></span>
                    <?php if (!empty($previewTask['linked_page_title'])): $dsp = $this->pageStatusDisplay($previewTask['linked_page_status'] ?? []); ?><span class="vk-sprint-task-page <?= $dsp['class'] ?>"<?= $dsp['label'] !== '' ? ' title="' . htmlspecialchars($dsp['label']) . '"' : '' ?>><i class="fa fa-file-o"></i> <?= $dsp['icon'] ?><?= htmlspecialchars((string)$previewTask['linked_page_title']) ?></span><?php endif; ?>
                    <?php if (!empty($previewTask['due_date'])): ?><span class="vk-sprint-task-date"><?= htmlspecialchars(date('M j', strtotime((string)$previewTask['due_date']))) ?></span><?php endif; ?>
                </a>
                <?php endforeach; ?>
                <?php if ($total > count($previewTasks)): ?>
                <a href="<?= $url ?>?view=tasks&sprint_id=<?= (int)$s['id'] ?>" class="vk-sprint-task-more"><?= sprintf(__('+%d more'), $total - count($previewTasks)) ?></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="vk-sprint-metrics">
            <div class="vk-sprint-metric-line">
                <span><?= sprintf(__('%1$d/%2$d tasks done'), $done, $total) ?></span>
                <span><?= $pct ?>%</span>
            </div>
            <progress class="uk-progress vk-sprint-progress" value="<?= $pct ?>" max="100"></progress>
            <?php if ($estimateH > 0 || $actualH > 0): ?>
            <div class="vk-sprint-capacity-line">
                <span><?= __('Capacity') ?></span>
                <span><?= number_format($actualH, 1) ?>h / <?= number_format($estimateH, 1) ?>h</span>
            </div>
            <div class="vk-sprint-capacity-track <?= $estimateH > 0 && $actualH > $estimateH ? 'is-over' : '' ?>" style="--vk-progress: <?= $estimateH > 0 ? $capacityPct : 0 ?>%">
                <span></span>
            </div>
            <?php endif; ?>
            <div class="vk-sprint-kpis">
                <?php if ($s['total_sp'] > 0): ?><span class="vk-kpi"><?= (int)$s['total_sp'] ?> <?= __('SP') ?></span><?php endif; ?>
                <?php if ($s['total_est'] > 0): ?><span class="vk-kpi"><?= (int)$s['total_est'] ?>h <?= __('est') ?></span><?php endif; ?>
                <?php if ($s['total_actual'] > 0): ?><span class="vk-kpi <?= $s['total_actual'] > $s['total_est'] && $s['total_est'] > 0 ? 'is-over' : '' ?>"><?= number_format((float)$s['total_actual'], 1) ?>h <?= __('actual') ?></span><?php endif; ?>
            </div>
        </div>

        <div class="vk-actions vk-sprint-actions">
            <?php if ($hasPlanActions): ?>
            <div class="vk-sprint-action-group is-plan">
            <?php if (!$s['start_date'] || !$s['end_date']): ?>
            <a href="<?= $url ?>?view=sprint-edit&id=<?= (int)$s['id'] ?>&return_url=<?= $currentSprintReturn ?>#vk-sprint-schedule" class="uk-button uk-button-default uk-button-small vk-sprint-schedule-action"><i class="fa fa-calendar"></i> <?= __('Set dates') ?></a>
            <a href="<?= $url ?>?view=sprint-edit&id=<?= (int)$s['id'] ?>&return_url=<?= $currentSprintReturn ?><?= $currentQuarterPlanParams ?>#vk-sprint-schedule" class="uk-button uk-button-default uk-button-small vk-sprint-schedule-action"><i class="fa fa-calendar-check-o"></i> <?= htmlspecialchars($currentQuarterLabel) ?></a>
            <?php endif; ?>
            <?php if ($s['status'] === 'planned'): ?>
            <form method="post" action="<?= $url ?>">
                <input type="hidden" name="<?= $csrfN ?>" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="update_sprint_status">
                <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                <input type="hidden" name="status" value="active">
                <input type="hidden" name="return_url" value="<?= htmlspecialchars($currentSprintUrl) ?>">
                <button class="uk-button uk-button-default uk-button-small vk-sprint-status-action"><i class="fa fa-play"></i> <?= __('Start') ?></button>
            </form>
            <?php elseif ($s['status'] === 'active'): ?>
            <form method="post" action="<?= $url ?>">
                <input type="hidden" name="<?= $csrfN ?>" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="update_sprint_status">
                <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                <input type="hidden" name="status" value="completed">
                <input type="hidden" name="return_url" value="<?= htmlspecialchars($currentSprintUrl) ?>">
                <button class="uk-button uk-button-default uk-button-small vk-sprint-status-action"><i class="fa fa-check"></i> <?= __('Complete') ?></button>
            </form>
            <?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="vk-sprint-action-group is-main">
            <a href="<?= $url ?>?view=task-edit&sprint_id=<?= (int)$s['id'] ?>&return_url=<?= $currentSprintReturn ?>" class="uk-button uk-button-primary uk-button-small" title="<?= __('Create task in this sprint') ?>"><i class="fa fa-plus"></i> <?= __('Task') ?></a>
            <a href="<?= $url ?>?view=tasks&sprint_id=<?= (int)$s['id'] ?>" class="uk-button uk-button-default uk-button-small"><?= __('Tasks') ?> <i class="fa fa-arrow-right"></i></a>
            <a href="<?= $url ?>?view=export-docx&type=sprint&id=<?= (int)$s['id'] ?>" class="uk-button uk-button-default uk-button-small" title="<?= __('Export sprint to Word') ?>"><i class="fa fa-file-word-o"></i></a>
            <a href="<?= $url ?>?view=sprint-edit&id=<?= (int)$s['id'] ?>&return_url=<?= $currentSprintReturn ?>" class="uk-button uk-button-default uk-button-small"><i class="fa fa-pencil"></i></a>
            <form method="post" action="<?= $url ?>" onsubmit="return confirm('<?= __('Delete sprint? Tasks will be unlinked.') ?>')">
                <input type="hidden" name="<?= $csrfN ?>" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="delete_sprint">
                <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                <input type="hidden" name="return_url" value="<?= htmlspecialchars($currentSprintUrl) ?>">
                <button class="uk-button uk-button-danger uk-button-small"><i class="fa fa-trash"></i></button>
            </form>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endforeach; ?>

<?php else: ?>
<div class="vk-empty vk-empty-panel vk-sprint-empty-state">
    <i class="fa fa-bolt vk-empty-icon"></i>
    <?php if ($hasSprintFilters): ?>
    <div class="vk-empty-title"><?= __('No sprints match these filters.') ?></div>
    <p class="vk-muted-line"><?= __('Clear the current sprint filters or create a new sprint for this planning context.') ?></p>
    <div class="vk-empty-actions">
        <a href="<?= $url ?>?view=sprints" class="uk-button uk-button-default uk-button-small"><i class="fa fa-times"></i> <?= __('Clear filters') ?></a>
        <a href="<?= $url ?>?view=sprint-edit&return_url=<?= $currentSprintReturn ?><?= $newSprintPlanParams ?>" class="uk-button uk-button-primary uk-button-small"><i class="fa fa-plus"></i> <?= __('New Sprint') ?></a>
    </div>
    <?php else: ?>
    <div class="vk-empty-title"><?= __('No sprints yet.') ?></div>
    <p class="vk-muted-line"><?= __('Create a sprint to group tasks into a delivery window.') ?></p>
    <div class="vk-empty-actions">
        <a href="<?= $url ?>?view=sprint-edit&return_url=<?= $currentSprintReturn ?><?= $newSprintPlanParams ?>" class="uk-button uk-button-primary uk-button-small"><i class="fa fa-plus"></i> <?= __('New Sprint') ?></a>
        <a href="<?= $url ?>?view=task-edit&return_url=<?= $currentSprintReturn ?>" class="uk-button uk-button-default uk-button-small"><i class="fa fa-plus"></i> <?= __('Create task without sprint') ?></a>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/_layout.php';

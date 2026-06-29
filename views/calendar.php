<?php namespace ProcessWire;
/**
 * @var Verk $this
 * @var int   $month
 * @var int   $year
 * @var array $items
 * @var array $deadlines
 * @var string $calView
 * @var string $weekStartDate
 * @var string $weekEndDate
 * @var int $quarter
 * @var int $quarterYear
 * @var string $quarterStartDate
 * @var string $quarterEndDate
 */
$url      = $this->page->url;
$today    = date('Y-m-d');
$todayDay = (int)date('j');
$todayYM  = date('Y-m');

$prevMonth = $month - 1; $prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $month + 1; $nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

$monthName  = date('F Y', mktime(0,0,0,$month,1,$year));
$weekTitle = $weekStartDate ? date('M j', strtotime($weekStartDate)) . ' - ' . date('M j, Y', strtotime($weekEndDate)) : '';
$quarterYear = $quarterYear ?? $year;
$quarterTitle = $quarterStartDate ? $this->quarterLabel(['quarter' => (int)$quarter, 'year' => (int)$quarterYear]) : '';
$firstDay   = (int)date('N', mktime(0,0,0,$month,1,$year));
$daysInMonth = (int)date('t', mktime(0,0,0,$month,1,$year));
$curYM       = sprintf('%04d-%02d', $year, $month);
$cfg         = $this->getConfig();
$calendarConfigured = !empty($cfg['calendar_template']) && !empty($cfg['calendar_date_field']);
$calendarPlanStats = $calendarPlanStats ?? ['overdue' => 0, 'next7' => 0, 'no_due' => 0, 'quarter_open' => 0];
$publicationCount = array_sum(array_map('count', $items));
$taskCount = array_sum(array_map('count', $deadlines));
$openTaskCount = 0;
$doneTaskCount = 0;
foreach ($deadlines as $dayTasks) {
    foreach ($dayTasks as $task) {
        if (($task['status'] ?? '') === 'done') $doneTaskCount++;
        else $openTaskCount++;
    }
}
$agendaTasks = [];
foreach ($deadlines as $day => $dayTasks) {
    foreach ($dayTasks as $task) {
        $agendaTasks[] = $task;
    }
}
usort($agendaTasks, fn($a, $b) => strcmp((string)($a['due_date'] ?? ''), (string)($b['due_date'] ?? '')));
$toolbarTitle = $calView === 'week' ? $weekTitle : ($calView === 'quarter' ? $quarterTitle : $monthName);
$prevWeekDate = $weekStartDate ? date('Y-m-d', strtotime($weekStartDate . ' -7 days')) : '';
$nextWeekDate = $weekStartDate ? date('Y-m-d', strtotime($weekStartDate . ' +7 days')) : '';
$prevQuarter = $quarter <= 1 ? 4 : $quarter - 1;
$prevQuarterYear = $quarter <= 1 ? $year - 1 : $year;
$nextQuarter = $quarter >= 4 ? 1 : $quarter + 1;
$nextQuarterYear = $quarter >= 4 ? $year + 1 : $year;
$calendarAssigneeParam = !empty($calendarAssigneeId) ? '&assignee_id=' . (int)$calendarAssigneeId : '';
$prevHref = match($calView) {
    'week' => $url . '?view=calendar&cal_view=week&date=' . $prevWeekDate . $calendarAssigneeParam,
    'quarter' => $url . '?view=calendar&cal_view=quarter&quarter=' . $prevQuarter . '&year=' . $prevQuarterYear . $calendarAssigneeParam,
    default => $url . '?view=calendar&month=' . $prevMonth . '&year=' . $prevYear . $calendarAssigneeParam,
};
$nextHref = match($calView) {
    'week' => $url . '?view=calendar&cal_view=week&date=' . $nextWeekDate . $calendarAssigneeParam,
    'quarter' => $url . '?view=calendar&cal_view=quarter&quarter=' . $nextQuarter . '&year=' . $nextQuarterYear . $calendarAssigneeParam,
    default => $url . '?view=calendar&month=' . $nextMonth . '&year=' . $nextYear . $calendarAssigneeParam,
};
$todayQuarterContext = $this->quarterContextForDate(date('Y-m-d'));
$todayQuarter = (int)$todayQuarterContext['quarter'];
$todayQuarterYear = (int)$todayQuarterContext['year'];
$todayHref = match($calView) {
    'week' => $url . '?view=calendar&cal_view=week&date=' . date('Y-m-d') . $calendarAssigneeParam,
    'quarter' => $url . '?view=calendar&cal_view=quarter&quarter=' . $todayQuarter . '&year=' . $todayQuarterYear . $calendarAssigneeParam,
    default => $url . '?view=calendar' . $calendarAssigneeParam,
};
$calendarReturnUrl = match($calView) {
    'week' => $url . '?view=calendar&cal_view=week&date=' . ($weekStartDate ?: date('Y-m-d')) . $calendarAssigneeParam,
    'quarter' => $url . '?view=calendar&cal_view=quarter&quarter=' . (int)$quarter . '&year=' . (int)$quarterYear . $calendarAssigneeParam,
    default => $url . '?view=calendar&month=' . (int)$month . '&year=' . (int)$year . $calendarAssigneeParam,
};
$calendarReturnParam = rawurlencode($calendarReturnUrl);
$quarterMonths = $calView === 'quarter' ? ($quarterMonths ?? []) : [];
$agendaLabel = $calView === 'week' ? $weekTitle : ($calView === 'quarter' ? $quarterTitle : $monthName);
$monthLinkYear = $calView === 'quarter' && !empty($quarterMonths[0]['year']) ? (int)$quarterMonths[0]['year'] : (int)$year;
$weekLinkDate = $weekStartDate ?: ($quarterStartDate ?: sprintf('%04d-%02d-01', $monthLinkYear, $month));

$renderDay = function(string $dateForTask, bool $isOther = false) use ($url, $items, $deadlines, $today, $calendarReturnParam) {
    $dayNum = (int)date('j', strtotime($dateForTask));
    $isToday = $dateForTask === $today;
    $cls = trim(($isToday ? ' vk-cal-today' : '') . ($isOther ? ' vk-cal-other' : ''));
    $newTaskForDayUrl = $url . '?view=task-edit&due_date=' . $dateForTask . '&return_url=' . $calendarReturnParam;
    echo '<td class="' . $cls . '">';
    echo '<div class="vk-cal-day-n"><span>' . $dayNum . '</span><a href="' . $newTaskForDayUrl . '" class="vk-cal-day-add" title="' . __('New task for this day') . '">+</a></div>';

    if (isset($items[$dateForTask])) {
        foreach ($items[$dateForTask] as $item) {
            $d = $this->pageStatusDisplay($item['status'] ?? []);
            $calTitle = $item['title'] . ($d['label'] !== '' ? ' (' . $d['label'] . ')' : '');
            echo '<a href="' . $item['edit'] . '" class="vk-cal-item vk-cal-pub ' . $d['class'] . '" target="_blank" title="' . htmlspecialchars($calTitle) . '">'
                . $d['icon']
                . htmlspecialchars(mb_strimwidth($item['title'], 0, 22, '…'))
                . '</a>';
        }
    }
    if (isset($deadlines[$dateForTask])) {
        foreach ($deadlines[$dateForTask] as $task) {
            $taskUrl = $url . '?view=task-edit&id=' . (int)$task['id'] . '&return_url=' . $calendarReturnParam;
            $taskDate = !empty($task['due_date']) ? date('M j', strtotime($task['due_date'])) : '';
            $taskStatus = preg_replace('/[^a-z0-9_-]/', '', (string)($task['status'] ?? 'open'));
            $taskPriority = preg_replace('/[^a-z0-9_-]/', '', (string)($task['priority'] ?? 'medium'));
            $taskMeta = array_filter([
                $taskDate,
                $this->statusLabel((string)$task['status']),
                (string)($task['assignee_name'] ?? ''),
            ]);
            echo '<a href="' . $taskUrl . '" class="vk-cal-item vk-cal-task is-' . $taskStatus . ' is-priority-' . $taskPriority . '" title="' . __('Task:') . ' ' . htmlspecialchars((string)$task['title']) . '">'
                . '<span>' . htmlspecialchars(mb_strimwidth((string)$task['title'], 0, 22, '…')) . '</span>'
                . ($taskMeta ? '<small class="vk-cal-task-date">' . htmlspecialchars(implode(' · ', $taskMeta)) . '</small>' : '')
                . '</a>';
        }
    }

    echo '</td>';
};

ob_start();
?>

<div class="vk-page-head">
    <div>
        <h2 class="vk-page-title"><?= __('Calendar') ?></h2>
        <p><?= __('Task deadlines are always shown. Publication dates appear after calendar settings are configured.') ?></p>
    </div>
    <div class="vk-actions">
        <a href="<?= $url ?>?view=task-edit&return_url=<?= $calendarReturnParam ?>" class="uk-button uk-button-primary"><i class="fa fa-plus"></i> <?= __('New Task') ?></a>
    </div>
</div>

<div class="vk-cal-toolbar">
    <a href="<?= $prevHref ?>" class="uk-button uk-button-default uk-button-small"><i class="fa fa-chevron-left"></i></a>
    <span class="vk-cal-title"><?= htmlspecialchars($toolbarTitle) ?></span>
    <a href="<?= $nextHref ?>" class="uk-button uk-button-default uk-button-small"><i class="fa fa-chevron-right"></i></a>
    <a href="<?= $todayHref ?>" class="uk-button uk-button-default uk-button-small"><i class="fa fa-calendar-check-o"></i> <?= __('Today') ?></a>
    <div class="vk-cal-mode">
        <a href="<?= $url ?>?view=calendar&month=<?= (int)$month ?>&year=<?= (int)$monthLinkYear ?><?= $calendarAssigneeParam ?>" class="<?= $calView === 'month' ? 'is-active' : '' ?>"><?= __('Month') ?></a>
        <a href="<?= $url ?>?view=calendar&cal_view=week&date=<?= htmlspecialchars($weekLinkDate) ?><?= $calendarAssigneeParam ?>" class="<?= $calView === 'week' ? 'is-active' : '' ?>"><?= __('Week') ?></a>
        <a href="<?= $url ?>?view=calendar&cal_view=quarter&quarter=<?= (int)$quarter ?>&year=<?= (int)$quarterYear ?><?= $calendarAssigneeParam ?>" class="<?= $calView === 'quarter' ? 'is-active' : '' ?>"><?= __('Quarter') ?></a>
    </div>
    <form method="get" action="<?= $url ?>" class="vk-cal-jump">
        <input type="hidden" name="view" value="calendar">
        <input type="hidden" name="cal_view" value="<?= htmlspecialchars($calView) ?>">
        <?php if ($calView === 'week'): ?>
        <input type="date" name="date" class="uk-input vk-control-small" value="<?= htmlspecialchars($weekStartDate ?: date('Y-m-d')) ?>" aria-label="<?= __('Week date') ?>">
        <?php elseif ($calView === 'quarter'): ?>
        <select name="quarter" class="uk-select vk-control-small" aria-label="<?= __('Quarter') ?>">
            <?php for ($q = 1; $q <= 4; $q++): ?>
            <option value="<?= $q ?>" <?= $q === (int)$quarter ? 'selected' : '' ?>>Q<?= $q ?></option>
            <?php endfor; ?>
        </select>
        <input type="number" name="year" class="uk-input vk-control-small" min="2000" max="2100" value="<?= (int)$year ?>" aria-label="<?= __('Year') ?>">
        <?php else: ?>
        <select name="month" class="uk-select vk-control-small" aria-label="<?= __('Month') ?>">
            <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>" <?= $m === $month ? 'selected' : '' ?>><?= date('M', mktime(0, 0, 0, $m, 1, $year)) ?></option>
            <?php endfor; ?>
        </select>
        <input type="number" name="year" class="uk-input vk-control-small" min="2000" max="2100" value="<?= (int)$year ?>" aria-label="<?= __('Year') ?>">
        <?php endif; ?>
        <select name="assignee_id" class="uk-select vk-control-small" aria-label="<?= __('Assignee') ?>">
            <option value="0"><?= __('All assignees') ?></option>
            <?php foreach (($calendarUsers ?? []) as $calendarUser): ?>
            <option value="<?= (int)$calendarUser['id'] ?>" <?= (int)$calendarAssigneeId === (int)$calendarUser['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string)$calendarUser['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="uk-button uk-button-default uk-button-small"><?= __('Go') ?></button>
    </form>
</div>

<div class="vk-calendar-planner">
    <a href="<?= $url ?>?view=tasks&sort=due" class="vk-calendar-plan-card <?= (int)$calendarPlanStats['overdue'] > 0 ? 'is-danger' : '' ?>">
        <span><?= __('Overdue') ?></span>
        <strong><?= (int)$calendarPlanStats['overdue'] ?></strong>
        <small><?= __('Open tasks past due date') ?></small>
    </a>
    <a href="<?= $url ?>?view=calendar&cal_view=week&date=<?= date('Y-m-d') ?>" class="vk-calendar-plan-card">
        <span><?= __('Next 7 days') ?></span>
        <strong><?= (int)$calendarPlanStats['next7'] ?></strong>
        <small><?= __('Open deadlines coming up') ?></small>
    </a>
    <a href="<?= $url ?>?view=tasks&quarter=<?= (int)$quarter ?>&year=<?= (int)$quarterYear ?>&sort=due" class="vk-calendar-plan-card">
        <span><?= __('Quarter open') ?></span>
        <strong><?= (int)$calendarPlanStats['quarter_open'] ?></strong>
        <small><?= htmlspecialchars($this->quarterLabel(['quarter' => (int)$quarter, 'year' => (int)$quarterYear])) ?></small>
    </a>
    <a href="<?= $url ?>?view=tasks&date_state=none&sort=due" class="vk-calendar-plan-card is-muted">
        <span><?= __('Backlog') ?></span>
        <strong><?= (int)$calendarPlanStats['no_due'] ?></strong>
        <small><?= __('Open tasks without due date') ?></small>
    </a>
</div>

<div class="vk-cal-summary">
    <div>
        <span><?= __('Task deadlines') ?></span>
        <strong><?= (int)$taskCount ?></strong>
    </div>
    <div>
        <span><?= __('Open tasks') ?></span>
        <strong><?= (int)$openTaskCount ?></strong>
    </div>
    <div>
        <span><?= __('Done tasks') ?></span>
        <strong><?= (int)$doneTaskCount ?></strong>
    </div>
    <div>
        <span><?= __('Publications') ?></span>
        <strong><?= (int)$publicationCount ?></strong>
    </div>
</div>

<?php if ($calView === 'quarter'): ?>
<div class="vk-cal-quarter">
    <?php foreach ($quarterMonths as $quarterMonthData): ?>
    <?php
    $quarterMonth = (int)($quarterMonthData['month'] ?? $quarterMonthData);
    $quarterMonthYear = (int)($quarterMonthData['year'] ?? $year);
    $quarterMonthName = date('F Y', mktime(0, 0, 0, $quarterMonth, 1, $quarterMonthYear));
    $quarterMonthFirstDay = (int)date('N', mktime(0, 0, 0, $quarterMonth, 1, $quarterMonthYear));
    $quarterMonthDays = (int)date('t', mktime(0, 0, 0, $quarterMonth, 1, $quarterMonthYear));
    ?>
    <div class="uk-card uk-card-default vk-cal-quarter-month">
        <div class="vk-cal-month-label"><?= htmlspecialchars($quarterMonthName) ?></div>
        <table class="vk-cal">
            <thead>
                <tr>
                    <?php foreach ([__('Mon'),__('Tue'),__('Wed'),__('Thu'),__('Fri'),__('Sat'),__('Sun')] as $d): ?>
                    <th><?= $d ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php
            $cell = 0;
            echo '<tr>';
            for ($i = 1; $i < $quarterMonthFirstDay; $i++) { echo '<td class="vk-cal-other"></td>'; $cell++; }
            for ($d = 1; $d <= $quarterMonthDays; $d++) {
                $renderDay(sprintf('%04d-%02d-%02d', $quarterMonthYear, $quarterMonth, $d));
                $cell++;
                if ($cell % 7 === 0 && $d < $quarterMonthDays) echo '</tr><tr>';
            }
            $remaining = 7 - ($cell % 7);
            if ($remaining < 7) {
                for ($i = 0; $i < $remaining; $i++) echo '<td class="vk-cal-other"></td>';
            }
            echo '</tr>';
            ?>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="uk-card uk-card-default vk-calendar-card">
    <table class="vk-cal <?= $calView === 'week' ? 'vk-cal-week' : '' ?>">
        <thead>
            <tr>
                <?php foreach ([__('Mon'),__('Tue'),__('Wed'),__('Thu'),__('Fri'),__('Sat'),__('Sun')] as $d): ?>
                <th><?= $d ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
        <?php
        if ($calView === 'week') {
            echo '<tr>';
            for ($i = 0; $i < 7; $i++) {
                $date = date('Y-m-d', strtotime($weekStartDate . " +$i days"));
                $renderDay($date, substr($date, 5, 2) !== str_pad((string)$month, 2, '0', STR_PAD_LEFT));
            }
            echo '</tr>';
        } else {
            $cell = 0;
            echo '<tr>';
            for ($i = 1; $i < $firstDay; $i++) { echo '<td class="vk-cal-other"></td>'; $cell++; }
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $renderDay(sprintf('%04d-%02d-%02d', $year, $month, $d));
                $cell++;
                if ($cell % 7 === 0 && $d < $daysInMonth) echo '</tr><tr>';
            }
            $remaining = 7 - ($cell % 7);
            if ($remaining < 7) {
                for ($i = 0; $i < $remaining; $i++) echo '<td class="vk-cal-other"></td>';
            }
            echo '</tr>';
        }
        ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if (!$calendarConfigured): ?>
<div class="vk-calendar-notice">
    <i class="fa fa-calendar"></i>
    <div>
        <div class="vk-calendar-notice-title"><?= __('Publication source is not configured') ?></div>
        <p><?= __('Task deadlines still appear in the calendar. Set templates and a date field to add publication dates.') ?>
            <a href="<?= $url ?>?view=settings" class="vk-card-action"><?= __('Open settings') ?> <i class="fa fa-arrow-right"></i></a>
        </p>
    </div>
</div>
<?php endif; ?>

<div class="vk-cal-legend">
    <?php if ($calendarConfigured): ?>
    <span class="vk-cal-key is-publication"><?= __('Publication') ?></span>
    <?php endif; ?>
    <span class="vk-cal-key is-task"><?= __('Task deadline') ?></span>
</div>

<div class="uk-card uk-card-default vk-cal-agenda">
    <div class="uk-card-header">
        <h3 class="vk-card-title"><?= __('Task Agenda') ?></h3>
        <div class="vk-cal-agenda-tools">
            <span class="vk-settings-card-note"><?= htmlspecialchars($agendaLabel) ?></span>
            <div class="vk-agenda-filter" role="group" aria-label="<?= __('Agenda filter') ?>">
                <button type="button" class="is-active" data-agenda-filter="all"><?= __('All') ?></button>
                <button type="button" data-agenda-filter="open"><?= __('Open') ?></button>
                <button type="button" data-agenda-filter="done"><?= __('Done') ?></button>
            </div>
        </div>
    </div>
    <?php if ($agendaTasks): ?>
    <div class="vk-cal-agenda-list" id="vk-cal-agenda-list">
        <?php foreach ($agendaTasks as $task): ?>
        <?php
        $taskUrl = $url . '?view=task-edit&id=' . (int)$task['id'] . '&return_url=' . $calendarReturnParam;
        $taskStatus = (string)($task['status'] ?? 'open');
        $taskPriority = (string)($task['priority'] ?? 'medium');
        $agendaMeta = array_filter([
            $this->statusLabel($taskStatus),
            $this->priorityLabel($taskPriority),
            (string)($task['assignee_name'] ?? ''),
        ]);
        ?>
        <a href="<?= $taskUrl ?>" class="vk-cal-agenda-row" data-agenda-status="<?= htmlspecialchars($taskStatus) ?>">
            <span class="vk-cal-agenda-date"><?= htmlspecialchars(date('M j', strtotime((string)$task['due_date']))) ?></span>
            <span class="vk-cal-agenda-main">
                <span class="vk-cal-agenda-title"><?= htmlspecialchars((string)$task['title']) ?></span>
                <span class="vk-cal-agenda-meta"><?= htmlspecialchars(implode(' · ', $agendaMeta)) ?></span>
            </span>
            <span class="uk-label vk-label-<?= htmlspecialchars($taskStatus) ?>"><?= htmlspecialchars($this->statusLabel($taskStatus)) ?></span>
        </a>
        <?php endforeach; ?>
    </div>
    <div class="vk-empty vk-agenda-filter-empty" id="vk-agenda-filter-empty" hidden>
        <i class="fa fa-filter"></i>
        <span><?= __('No tasks match this filter.') ?></span>
    </div>
    <?php else: ?>
    <div class="vk-empty">
        <i class="fa fa-calendar-check-o"></i>
        <span><?= __('No task deadlines in this view.') ?></span>
    </div>
    <?php endif; ?>
</div>

<script>
(function() {
    const list = document.getElementById('vk-cal-agenda-list');
    if (!list) return;
    const card = list.closest('.vk-cal-agenda');
    const buttons = card.querySelectorAll('[data-agenda-filter]');
    const rows = Array.from(list.querySelectorAll('[data-agenda-status]'));
    const empty = document.getElementById('vk-agenda-filter-empty');

    function applyFilter(value) {
        let shown = 0;
        rows.forEach((row) => {
            const status = row.dataset.agendaStatus || 'open';
            const match = value === 'all' || (value === 'done' ? status === 'done' : status !== 'done');
            row.hidden = !match;
            if (match) shown++;
        });
        if (empty) empty.hidden = shown > 0;
        buttons.forEach((button) => button.classList.toggle('is-active', button.dataset.agendaFilter === value));
    }

    buttons.forEach((button) => {
        button.addEventListener('click', () => applyFilter(button.dataset.agendaFilter || 'all'));
    });
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/_layout.php';

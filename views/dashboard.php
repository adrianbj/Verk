<?php namespace ProcessWire;
/**
 * @var Verk $this
 * @var array $myTasks
 * @var array $myReviews
 * @var array $myCollaborations
 * @var array $recentTasks
 * @var array $upcoming
 * @var array $auditSummary
 * @var array $taskStats
 * @var array $dashboardSprints
 */
$url   = $this->page->url;
$today = date('Y-m-d');

$open  = ($taskStats['open'] ?? 0) + ($taskStats['in_progress'] ?? 0) + ($taskStats['review'] ?? 0);
$done  = $taskStats['done'] ?? 0;
$dashboardQuarter = $dashboardQuarter ?? $this->quarterContextForDate(date('Y-m-d'));
$quarterLabel = $this->quarterLabel($dashboardQuarter);
$quarterTaskStats = $quarterTaskStats ?? ['total' => 0, 'done_count' => 0, 'open_count' => 0];
$quarterProgress = $quarterProgress ?? 0;
$overdueTaskCount = $overdueTaskCount ?? 0;
$unassignedTaskCount = $unassignedTaskCount ?? 0;
$dashboardSprintCount = $dashboardSprintCount ?? count($dashboardSprints ?? []);
$quarterBaseUrl = $url . '?view=dashboard';
$buildDashboardUrl = function(array $changes = []) use ($url, $dashboardQuarter, $myPage, $recentPage) {
    $params = [
        'view' => 'dashboard',
        'quarter' => (int)$dashboardQuarter['quarter'],
        'year' => (int)$dashboardQuarter['year'],
    ];
    if ($myPage > 1) $params['my_page'] = (int)$myPage;
    if ($recentPage > 1) $params['recent_page'] = (int)$recentPage;
    foreach ($changes as $key => $value) {
        if ($value === null || $value === '' || $value === 0 || $value === 1) unset($params[$key]);
        else $params[$key] = $value;
    }
    return $url . '?' . http_build_query($params);
};
$dashboardReturnUrl = $buildDashboardUrl([]);
$dashboardReturnParam = rawurlencode($dashboardReturnUrl);

ob_start();
?>

<div class="vk-dashboard-head vk-dashboard-toolbar">
    <div>
        <h2 class="vk-page-title"><?= __('Dashboard') ?></h2>
        <p><?= __('Tasks, upcoming publications, and audit signals for this ProcessWire site.') ?></p>
    </div>
    <div class="vk-actions">
        <div class="vk-quarter-nav">
            <?php for ($q = 1; $q <= 4; $q++): ?>
            <a class="<?= (int)$dashboardQuarter['quarter'] === $q ? 'is-active' : '' ?>" href="<?= $quarterBaseUrl ?>&quarter=<?= $q ?>&year=<?= (int)$dashboardQuarter['year'] ?>">Q<?= $q ?></a>
            <?php endfor; ?>
            <form method="get" action="<?= $url ?>" class="vk-quarter-year">
                <input type="hidden" name="view" value="dashboard">
                <input type="hidden" name="quarter" value="<?= (int)$dashboardQuarter['quarter'] ?>">
                <input class="uk-input" type="number" name="year" min="2000" max="2100" value="<?= (int)$dashboardQuarter['year'] ?>" aria-label="<?= __('Year') ?>">
            </form>
        </div>
        <a href="<?= $url ?>?view=task-edit&return_url=<?= $dashboardReturnParam ?>" class="uk-button uk-button-primary">
            <i class="fa fa-plus"></i> <?= __('New Task') ?>
        </a>
    </div>
</div>

<section class="vk-dashboard-focus">
    <div class="vk-dashboard-focus-main">
        <div>
            <span class="vk-section-label"><?= __('Quarter focus') ?></span>
            <h3><?= htmlspecialchars($quarterLabel) ?></h3>
            <p><?= sprintf(__('%1$d of %2$d due tasks completed'), (int)$quarterTaskStats['done_count'], (int)$quarterTaskStats['total']) ?></p>
        </div>
        <div class="vk-dashboard-focus-progress">
            <span><?= (int)$quarterProgress ?>%</span>
            <progress class="uk-progress vk-sprint-progress" value="<?= (int)$quarterProgress ?>" max="100"></progress>
        </div>
    </div>
    <a class="vk-dashboard-focus-card <?= $overdueTaskCount > 0 ? 'is-danger' : '' ?>" href="<?= $url ?>?view=tasks&sort=due">
        <span><?= __('Overdue') ?></span>
        <strong><?= (int)$overdueTaskCount ?></strong>
        <small><?= __('Open tasks past due date') ?></small>
    </a>
    <a class="vk-dashboard-focus-card" href="<?= $url ?>?view=sprints&quarter=<?= (int)$dashboardQuarter['quarter'] ?>&year=<?= (int)$dashboardQuarter['year'] ?>">
        <span><?= __('Sprint plan') ?></span>
        <strong><?= (int)$dashboardSprintCount ?></strong>
        <small><?= __('Active and planned in quarter') ?></small>
    </a>
</section>

<div class="vk-dashboard-stats">
    <div><a href="<?= $url ?>?view=tasks&status=active" class="vk-stat vk-stat-link uk-card uk-card-default uk-card-small"><div class="vk-stat-l"><?= __('Active') ?></div><div class="vk-stat-n"><?= $open ?></div><span class="vk-stat-note"><?= __('Needs attention') ?></span></a></div>
    <div><a href="<?= $url ?>?view=tasks&status=done" class="vk-stat vk-stat-link uk-card uk-card-default uk-card-small"><div class="vk-stat-l"><?= __('Done') ?></div><div class="vk-stat-n"><?= $done ?></div><span class="vk-stat-note"><?= __('Completed tasks') ?></span></a></div>
    <div><a href="<?= $url ?>?view=calendar" class="vk-stat vk-stat-link uk-card uk-card-default uk-card-small"><div class="vk-stat-l"><?= __('Upcoming') ?></div><div class="vk-stat-n"><?= (int)($upcomingTotal ?? count($upcoming)) ?></div><span class="vk-stat-note"><?= __('Next 14 days') ?></span></a></div>
    <div><a href="<?= $url ?>?view=tasks&status=active&quarter=<?= (int)$dashboardQuarter['quarter'] ?>&year=<?= (int)$dashboardQuarter['year'] ?>&sort=due" class="vk-stat vk-stat-link uk-card uk-card-default uk-card-small"><div class="vk-stat-l"><?= htmlspecialchars($quarterLabel) ?></div><div class="vk-stat-n"><?= (int)$quarterTaskStats['open_count'] ?></div><span class="vk-stat-note"><?= __('Active due this quarter') ?></span></a></div>
    <?php foreach ($auditSummary as $a): ?>
    <div><a href="<?= $url ?>?view=audit&rule=<?= (int)$a['index'] ?>" class="vk-stat vk-stat-link uk-card uk-card-default uk-card-small">
        <div class="vk-stat-l" title="<?= htmlspecialchars($a['label']) ?>"><?= __('Audit') ?></div>
        <div class="vk-stat-n <?= $a['count'] > 0 ? 'is-warning' : 'is-success' ?>"><?= $a['count'] ?></div>
        <span class="vk-stat-note"><?= htmlspecialchars(mb_strimwidth($a['label'], 0, 28, '...')) ?></span>
    </a></div>
    <?php endforeach; ?>
</div>

<div class="vk-dashboard-queue">
    <a href="<?= $url ?>?view=tasks&status=open" class="vk-dashboard-queue-item">
        <span><?= __('Open queue') ?></span>
        <strong><?= (int)($taskStats['open'] ?? 0) ?></strong>
    </a>
    <a href="<?= $url ?>?view=tasks&status=in_progress" class="vk-dashboard-queue-item">
        <span><?= __('In progress') ?></span>
        <strong><?= (int)($taskStats['in_progress'] ?? 0) ?></strong>
    </a>
    <a href="<?= $url ?>?view=tasks&status=review" class="vk-dashboard-queue-item">
        <span><?= __('Review') ?></span>
        <strong><?= (int)($taskStats['review'] ?? 0) ?></strong>
    </a>
    <a href="<?= $url ?>?view=tasks&assignee_id=-1" class="vk-dashboard-queue-item <?= $unassignedTaskCount > 0 ? 'is-warning' : '' ?>">
        <span><?= __('Unassigned') ?></span>
        <strong><?= (int)$unassignedTaskCount ?></strong>
    </a>
    <a href="<?= $url ?>?view=tasks&status=active&quarter=<?= (int)$dashboardQuarter['quarter'] ?>&year=<?= (int)$dashboardQuarter['year'] ?>&sort=due" class="vk-dashboard-queue-item">
        <span><?= __('Due this quarter') ?></span>
        <strong><?= (int)$quarterTaskStats['open_count'] ?></strong>
    </a>
    <a href="<?= $url ?>?view=tasks&sort=due" class="vk-dashboard-queue-item <?= $overdueTaskCount > 0 ? 'is-danger' : '' ?>">
        <span><?= __('Overdue') ?></span>
        <strong><?= (int)$overdueTaskCount ?></strong>
    </a>
</div>

<div class="vk-dashboard-grid">

    <div class="vk-dashboard-side">
        <div class="uk-card uk-card-default vk-dashboard-card">
            <div class="uk-card-header vk-card-header-row">
                <h3 class="vk-card-title"><?= __('My Tasks') ?></h3>
                <a href="<?= $url ?>?view=tasks&assignee_id=<?= (int)$uid ?>" class="vk-card-action"><?= __('All mine') ?> <i class="fa fa-arrow-right"></i></a>
            </div>
            <?php if ($myTasks): ?>
            <div class="vk-mini-list">
                <?php foreach ($myTasks as $t): ?>
                <article class="vk-mini-row">
                    <div class="vk-mini-main">
                        <a href="<?= $url ?>?view=task-edit&id=<?= (int)$t['id'] ?>&return_url=<?= $dashboardReturnParam ?>" class="vk-mini-title"><?= htmlspecialchars($t['title']) ?></a>
                        <div class="vk-mini-meta">
                            <span><?= __('TASK') ?>-<?= (int)$t['id'] ?></span>
                            <?php if ($t['due_date']): ?><span class="<?= $t['due_date'] < $today ? 'is-overdue' : '' ?>"><?= htmlspecialchars($t['due_date']) ?></span><?php endif; ?>
                            <?php if ($t['due_date']): ?><span class="vk-quarter-inline"><?= htmlspecialchars($this->quarterLabelForDate($t['due_date'])) ?></span><?php endif; ?>
                        </div>
                        <?php if (!empty($t['linked_page_title'])): ?>
                        <a href="<?= $t['linked_page_edit'] ?>" class="vk-chip" target="_blank">
                            <i class="fa fa-pencil-square-o"></i> <?= htmlspecialchars((string)$t['linked_page_title']) ?>
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="vk-mini-side">
                        <span class="uk-label vk-label-<?= $t['priority'] ?>"><?= htmlspecialchars($this->priorityLabel($t['priority'])) ?></span>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php if ($myTotalPages > 1): ?>
            <div class="vk-card-pager">
                <span class="vk-card-pager-meta"><?= sprintf(__('%d tasks'), $myTaskTotal) ?></span>
                <span class="vk-card-pager-links">
                    <?php if ($myPage > 1): ?><a href="<?= $buildDashboardUrl(['my_page' => $myPage - 1]) ?>" class="vk-pager-link"><i class="fa fa-chevron-left"></i> <?= __('Prev') ?></a><?php endif; ?>
                    <span class="vk-card-pager-meta"><?= sprintf(__('Page %1$d/%2$d'), $myPage, $myTotalPages) ?></span>
                    <?php if ($myPage < $myTotalPages): ?><a href="<?= $buildDashboardUrl(['my_page' => $myPage + 1]) ?>" class="vk-pager-link"><?= __('Next') ?> <i class="fa fa-chevron-right"></i></a><?php endif; ?>
                </span>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <div class="uk-card-body vk-dashboard-empty">
                <i class="fa fa-check-circle-o"></i>
                <span><?= __('All clear') ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="uk-card uk-card-default vk-card-stack vk-dashboard-card">
            <div class="uk-card-header vk-card-header-row">
                <h3 class="vk-card-title"><?= __('Collaborating On') ?></h3>
                <a href="<?= $url ?>?view=tasks&collaborator_id=<?= (int)$uid ?>" class="vk-card-action"><?= __('All mine') ?> <i class="fa fa-arrow-right"></i></a>
            </div>
            <?php if (!empty($myCollaborations)): ?>
            <div class="vk-mini-list">
                <?php foreach ($myCollaborations as $c): ?>
                <article class="vk-mini-row">
                    <div class="vk-mini-main">
                        <a href="<?= $url ?>?view=task-edit&id=<?= (int)$c['id'] ?>&return_url=<?= $dashboardReturnParam ?>" class="vk-mini-title"><?= htmlspecialchars($c['title']) ?></a>
                        <div class="vk-mini-meta">
                            <span><?= __('TASK') ?>-<?= (int)$c['id'] ?></span>
                            <?php if ($c['due_date']): ?><span class="<?= $c['due_date'] < $today ? 'is-overdue' : '' ?>"><?= htmlspecialchars($c['due_date']) ?></span><?php endif; ?>
                        </div>
                    </div>
                    <div class="vk-mini-side">
                        <span class="uk-label vk-label-<?= $c['priority'] ?>"><?= htmlspecialchars($this->priorityLabel($c['priority'])) ?></span>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="uk-card-body vk-dashboard-empty">
                <i class="fa fa-check-circle-o"></i>
                <span><?= __('No tasks to collaborate on') ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="uk-card uk-card-default vk-card-stack vk-dashboard-card">
            <div class="uk-card-header vk-card-header-row">
                <h3 class="vk-card-title"><?= __('My Reviews') ?></h3>
                <a href="<?= $url ?>?view=tasks&status=review&reviewer_id=<?= (int)$uid ?>" class="vk-card-action"><?= __('All mine') ?> <i class="fa fa-arrow-right"></i></a>
            </div>
            <?php if (!empty($myReviews)): ?>
            <div class="vk-mini-list">
                <?php foreach ($myReviews as $r): ?>
                <article class="vk-mini-row">
                    <div class="vk-mini-main">
                        <a href="<?= $url ?>?view=task-edit&id=<?= (int)$r['id'] ?>&return_url=<?= $dashboardReturnParam ?>" class="vk-mini-title"><?= htmlspecialchars($r['title']) ?></a>
                        <div class="vk-mini-meta">
                            <span><?= __('TASK') ?>-<?= (int)$r['id'] ?></span>
                            <?php if ($r['due_date']): ?><span class="<?= $r['due_date'] < $today ? 'is-overdue' : '' ?>"><?= htmlspecialchars($r['due_date']) ?></span><?php endif; ?>
                        </div>
                    </div>
                    <div class="vk-mini-side">
                        <span class="uk-label vk-label-<?= $r['priority'] ?>"><?= htmlspecialchars($this->priorityLabel($r['priority'])) ?></span>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="uk-card-body vk-dashboard-empty">
                <i class="fa fa-check-circle-o"></i>
                <span><?= __('Nothing to review') ?></span>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($auditSummary): ?>
        <div class="uk-card uk-card-default vk-card-stack vk-dashboard-card">
            <div class="uk-card-header vk-card-header-row">
                <h3 class="vk-card-title"><?= __('My Content Audit') ?></h3>
                <a href="<?= $url ?>?view=audit" class="vk-card-action"><?= __('Run audit') ?> <i class="fa fa-arrow-right"></i></a>
            </div>
            <div class="uk-card-body vk-card-body-flush">
                <?php foreach ($auditSummary as $a): ?>
                <a href="<?= $url ?>?view=audit&rule=<?= (int)$a['index'] ?>" class="vk-audit-item">
                    <span class="vk-audit-item-label"><?= htmlspecialchars($a['label']) ?></span>
                    <span class="vk-audit-count <?= $a['count'] > 0 ? 'is-warning' : 'is-success' ?>">
                        <?= sprintf(__('%d pages'), $a['count']) ?>
                    </span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php
        $workload = $this->getWorkloadByAssignee();
        $wlConfig = [
                'status' => [
                    'keys'   => ['open', 'in_progress', 'review'],
                    'labels' => [
                        'open'        => $this->statusLabel('open'),
                        'in_progress' => $this->statusLabel('in_progress'),
                        'review'      => $this->statusLabel('review'),
                    ],
                ],
                'priority' => [
                    'keys'   => ['critical', 'high', 'medium', 'low'],
                    'labels' => [
                        'critical' => $this->priorityLabel('critical'),
                        'high'     => $this->priorityLabel('high'),
                        'medium'   => $this->priorityLabel('medium'),
                        'low'      => $this->priorityLabel('low'),
                    ],
                ],
            ];
        ?>
        <div class="uk-card uk-card-default vk-card-stack vk-dashboard-card vk-workload-card"
             data-workload="<?= htmlspecialchars(json_encode($workload['assignees']), ENT_QUOTES) ?>"
             data-workload-config="<?= htmlspecialchars(json_encode($wlConfig), ENT_QUOTES) ?>"
             data-workload-missing="<?= $workload['missingEstimates'] ? '1' : '0' ?>">
            <div class="uk-card-header vk-card-header-row">
                <h3 class="vk-card-title"><?= __('Workload') ?></h3>
                <div class="vk-workload-toggles">
                    <div class="vk-seg" data-wl-toggle="metric">
                        <button type="button" data-wl-value="count" class="is-active"><?= __('Count') ?></button>
                        <button type="button" data-wl-value="hours"><?= __('Hours') ?></button>
                    </div>
                    <div class="vk-seg" data-wl-toggle="breakdown">
                        <button type="button" data-wl-value="status" class="is-active"><?= __('Status') ?></button>
                        <button type="button" data-wl-value="priority"><?= __('Priority') ?></button>
                    </div>
                </div>
            </div>
            <div class="uk-card-body">
                <div class="vk-workload-legend" data-wl-legend></div>
                <div class="vk-workload-rows" data-wl-rows></div>
                <p class="vk-workload-hint" data-wl-hint hidden><?= __('Some open tasks have no estimate, so Hours understates the real load.') ?></p>
            </div>
        </div>
        <script>
        (function() {
            const card = document.currentScript.previousElementSibling;
            if (!card || !card.classList.contains('vk-workload-card')) return;
            const data    = JSON.parse(card.dataset.workload || '[]');
            const config  = JSON.parse(card.dataset.workloadConfig || '{}');
            const missing = card.dataset.workloadMissing === '1';
            const legendEl = card.querySelector('[data-wl-legend]');
            const rowsEl   = card.querySelector('[data-wl-rows]');
            const hintEl   = card.querySelector('[data-wl-hint]');
            const T = { none: <?= json_encode(__('No open tasks.')) ?>, noEst: <?= json_encode(__('No estimates recorded yet.')) ?> };
            let metric = 'count', breakdown = 'status';

            const fmt = (v) => metric === 'hours'
                ? (Math.round(v * 10) / 10) + 'h'
                : String(v);
            const bucketKey = () => 'by' + breakdown.charAt(0).toUpperCase() + breakdown.slice(1);

            function render() {
                const keys = config[breakdown].keys;
                const labels = config[breakdown].labels;

                if (!data.length) {
                    legendEl.textContent = '';
                    rowsEl.textContent = '';
                    const msg = document.createElement('p');
                    msg.className = 'vk-workload-empty';
                    msg.textContent = T.none;
                    rowsEl.appendChild(msg);
                    hintEl.hidden = true;
                    return;
                }

                const max = data.reduce((m, a) => Math.max(m, a[metric]), 0);

                legendEl.textContent = '';
                keys.forEach(k => {
                    const item = document.createElement('span');
                    item.className = 'vk-wl-legend-item';
                    const sw = document.createElement('span');
                    sw.className = 'vk-wl-swatch is-' + k;
                    const lbl = document.createElement('span');
                    lbl.textContent = labels[k];
                    item.append(sw, lbl);
                    legendEl.appendChild(item);
                });

                rowsEl.textContent = '';
                if (max <= 0) {
                    const msg = document.createElement('p');
                    msg.className = 'vk-workload-empty';
                    msg.textContent = metric === 'hours' ? T.noEst : T.none;
                    rowsEl.appendChild(msg);
                } else {
                    const bucket = bucketKey();
                    data.forEach(a => {
                        const total = a[metric];
                        const row = document.createElement('div');
                        row.className = 'vk-wl-row';

                        const name = document.createElement('span');
                        name.className = 'vk-wl-name';
                        name.textContent = a.name;
                        name.title = a.name;

                        const track = document.createElement('div');
                        track.className = 'vk-wl-track';
                        const bar = document.createElement('div');
                        bar.className = 'vk-wl-bar';
                        bar.style.width = (total / max * 100) + '%';
                        keys.forEach(k => {
                            const v = a[bucket][k][metric];
                            if (v <= 0) return;
                            const seg = document.createElement('span');
                            seg.className = 'vk-wl-seg is-' + k;
                            seg.style.width = (v / total * 100) + '%';
                            seg.title = labels[k] + ': ' + fmt(v);
                            bar.appendChild(seg);
                        });
                        track.appendChild(bar);

                        const tot = document.createElement('span');
                        tot.className = 'vk-wl-total';
                        tot.textContent = fmt(total);

                        row.append(name, track, tot);
                        rowsEl.appendChild(row);
                    });
                }

                hintEl.hidden = !(metric === 'hours' && missing);
            }

            card.querySelectorAll('[data-wl-toggle]').forEach(group => {
                const kind = group.dataset.wlToggle;
                group.querySelectorAll('button').forEach(btn => {
                    btn.addEventListener('click', function() {
                        group.querySelectorAll('button').forEach(b => b.classList.remove('is-active'));
                        btn.classList.add('is-active');
                        if (kind === 'metric') metric = btn.dataset.wlValue;
                        else breakdown = btn.dataset.wlValue;
                        render();
                    });
                });
            });

            render();
        })();
        </script>
    </div>

    <div class="vk-dashboard-side">
        <div class="uk-card uk-card-default vk-dashboard-card">
            <div class="uk-card-header vk-card-header-row">
                <h3 class="vk-card-title"><?= __('Upcoming (14 days)') ?></h3>
                <a href="<?= $url ?>?view=calendar" class="vk-card-action"><?= __('Calendar') ?> <i class="fa fa-arrow-right"></i></a>
            </div>
            <?php if ($upcoming): ?>
            <div class="vk-mini-list">
                <?php foreach ($upcoming as $item): ?>
                <article class="vk-mini-row">
                    <div class="vk-mini-main">
                        <a href="<?= $item['edit'] ?>" target="_blank" class="vk-mini-title"><?= htmlspecialchars($item['title']) ?></a>
                        <div class="vk-mini-meta"><span><?= htmlspecialchars($item['date']) ?></span></div>
                    </div>
                    <div class="vk-mini-side">
                        <a href="<?= $item['url'] ?>" target="_blank" class="vk-icon-button" title="<?= __('Open page') ?>" aria-label="<?= __('Open page') ?>"><i class="fa fa-external-link"></i></a>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="uk-card-body vk-dashboard-empty">
                <?php $cfg = $this->getConfig(); if (!$cfg['calendar_template']): ?>
                <a href="<?= $url ?>?view=settings" class="vk-card-action"><?= __('Configure calendar template') ?> <i class="fa fa-arrow-right"></i></a>
                <?php else: ?>
                <span class="vk-muted-line"><?= __('No upcoming publications.') ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="uk-card uk-card-default vk-card-stack vk-dashboard-card vk-dashboard-card-recent">
            <div class="uk-card-header vk-card-header-row">
                <h3 class="vk-card-title"><?= __('Recent Open Tasks') ?></h3>
                <a href="<?= $url ?>?view=tasks&sort=created" class="vk-card-action"><?= __('All open') ?> <i class="fa fa-arrow-right"></i></a>
            </div>
            <?php if ($recentTasks): ?>
            <div class="vk-mini-list">
                <?php foreach ($recentTasks as $t): ?>
                <article class="vk-mini-row">
                    <div class="vk-mini-main">
                        <a href="<?= $url ?>?view=task-edit&id=<?= (int)$t['id'] ?>&return_url=<?= $dashboardReturnParam ?>" class="vk-mini-title"><?= htmlspecialchars($t['title']) ?></a>
                        <div class="vk-mini-meta">
                            <span><?= __('TASK') ?>-<?= (int)$t['id'] ?></span>
                            <?php if (!empty($t['linked_page_title'])): ?><span><?= htmlspecialchars((string)$t['linked_page_title']) ?></span><?php endif; ?>
                            <?php if ($t['due_date']): ?><span class="vk-quarter-inline"><?= htmlspecialchars($this->quarterLabelForDate($t['due_date'])) ?></span><?php endif; ?>
                        </div>
                        <?php if (!empty($t['linked_page_title'])): ?>
                        <a href="<?= $t['linked_page_edit'] ?>" class="vk-chip" target="_blank">
                            <i class="fa fa-pencil-square-o"></i> <?= htmlspecialchars(mb_strimwidth((string)$t['linked_page_title'], 0, 34, '...')) ?>
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="vk-mini-side">
                        <span class="uk-label vk-label-<?= $t['status'] ?>"><?= htmlspecialchars($this->statusLabel($t['status'])) ?></span>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php if ($recentTotalPages > 1): ?>
            <div class="vk-card-pager">
                <span class="vk-card-pager-meta"><?= sprintf(__('%d tasks'), $recentTotal) ?></span>
                <span class="vk-card-pager-links">
                    <?php if ($recentPage > 1): ?><a href="<?= $buildDashboardUrl(['recent_page' => $recentPage - 1]) ?>" class="vk-pager-link"><i class="fa fa-chevron-left"></i> <?= __('Prev') ?></a><?php endif; ?>
                    <span class="vk-card-pager-meta"><?= sprintf(__('Page %1$d/%2$d'), $recentPage, $recentTotalPages) ?></span>
                    <?php if ($recentPage < $recentTotalPages): ?><a href="<?= $buildDashboardUrl(['recent_page' => $recentPage + 1]) ?>" class="vk-pager-link"><?= __('Next') ?> <i class="fa fa-chevron-right"></i></a><?php endif; ?>
                </span>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <div class="uk-card-body vk-dashboard-empty">
                <span><?= __('No open tasks.') ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="uk-card uk-card-default vk-card-stack vk-dashboard-card vk-dashboard-card-sprints">
            <div class="uk-card-header vk-card-header-row">
                <h3 class="vk-card-title"><?= __('Sprints') ?> · <?= htmlspecialchars($quarterLabel) ?></h3>
                <a href="<?= $url ?>?view=sprints&quarter=<?= (int)$dashboardQuarter['quarter'] ?>&year=<?= (int)$dashboardQuarter['year'] ?>" class="vk-card-action"><?= __('All sprints') ?> <i class="fa fa-arrow-right"></i></a>
            </div>
            <?php if ($dashboardSprints): ?>
            <div class="vk-sprint-summary">
                <?php foreach ($dashboardSprints as $s):
                    $total = (int)$s['task_count'];
                    $done = (int)$s['done_count'];
                    $pct = $total ? (int)round($done / $total * 100) : 0;
                ?>
                <a class="vk-sprint-row" href="<?= $url ?>?view=sprint-edit&id=<?= (int)$s['id'] ?>&return_url=<?= $dashboardReturnParam ?>">
                    <div class="vk-sprint-row-head">
                        <span><?= htmlspecialchars($s['name']) ?></span>
                        <span class="uk-label vk-label-<?= $s['status'] === 'active' ? 'done' : 'open' ?>"><?= htmlspecialchars($this->sprintStatusLabel($s['status'])) ?></span>
                    </div>
                    <div class="vk-sprint-row-meta">
                        <span><?= sprintf(__('%1$d/%2$d tasks'), $done, $total) ?></span>
                        <span><?= $s['end_date'] ?: __('No end date') ?></span>
                    </div>
                    <progress class="uk-progress vk-sprint-progress" value="<?= $pct ?>" max="100"></progress>
                </a>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="uk-card-body vk-dashboard-empty">
                <a href="<?= $url ?>?view=sprint-edit&return_url=<?= $dashboardReturnParam ?>"><i class="fa fa-plus"></i> <?= __('Create a sprint') ?></a>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/_layout.php';

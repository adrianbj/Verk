<?php namespace ProcessWire;
/**
 * @var Verk $this
 * @var array|null $task
 * @var Page|null $linkedPage
 * @var array $users
 * @var int $pageId
 */
$url     = $this->page->url;
$csrf    = $this->getCSRFToken();
$csrfN   = $this->getCSRFName();
$isEdit  = !empty($task);
$sprintIdPrefill = (int)$this->wire('input')->get('sprint_id');
$returnUrl = $this->safeLocalUrl((string)$this->wire('input')->get('return_url', 'string'));
$t       = $task ?? ['title'=>'','description'=>'','status'=>'open','priority'=>'medium','due_date'=>$dueDatePrefill ?? '','page_id'=>$pageId ?? 0,'assignee_id'=>0,'section'=>'','sprint_id'=>$sprintIdPrefill,'estimate_h'=>4,'actual_h'=>'','story_points'=>''];
$sprints = $this->getAllSprints();
$curUser = $this->wire('user');
$today   = date('Y-m-d');
$adminUrl = $this->wire('config')->urls->admin;
$backUrl  = $url . '?view=task-edit&id=' . ($t['id'] ?? 0) . ($returnUrl ? '&return_url=' . rawurlencode($returnUrl) : '');
$layoutClass = $isEdit ? 'vk-task-layout vk-task-workspace has-sidebar' : 'vk-task-layout vk-task-create';
$linkedPageTitle = $linkedPage ? $this->pageTitleForDisplay($linkedPage) : '';
$linkedPageStatus = $linkedPage ? $this->pageStatusDisplay($this->pageStatusFlags($linkedPage)) : ['class'=>'','label'=>'','icon'=>''];
$assigneeName = __('Unassigned');
foreach ($users as $taskUser) {
    if ((int)$taskUser['id'] === (int)($t['assignee_id'] ?? 0)) {
        $assigneeName = $taskUser['name'];
        break;
    }
}
$reviewerIds = array_map('intval', $task['reviewer_ids'] ?? []);
$reviewerNames = implode(', ', array_map(fn(array $r): string => (string) $r['name'], $task['reviewers'] ?? []));
$collaboratorIds = array_map('intval', $task['collaborator_ids'] ?? []);
$collaboratorNames = implode(', ', array_map(fn(array $c): string => (string) $c['name'], $task['collaborators'] ?? []));
$sprintName = __('No sprint');
foreach ($sprints as $taskSprint) {
    if ((int)$taskSprint['id'] === (int)($t['sprint_id'] ?? 0)) {
        $sprintName = $taskSprint['name'];
        break;
    }
}
$dueQuarterLabel = $this->quarterLabelForDate($t['due_date'] ?? '');
ob_start();
?>

<?php if (!$isEdit): ?>
<div class="vk-page-head vk-compact-head">
    <div>
        <h2 class="vk-page-title"><?= __('New Task') ?></h2>
        <p><?= __('Create a focused task and connect it to a ProcessWire page when needed.') ?></p>
    </div>
    <?php if ($returnUrl): ?>
    <div class="vk-actions">
        <a href="<?= htmlspecialchars($returnUrl) ?>" class="uk-button uk-button-default uk-button-small"><i class="fa fa-arrow-left"></i> <?= __('Back to Tasks') ?></a>
    </div>
    <?php endif; ?>
</div>
<?php else: ?>
<section class="vk-issue-panel">
    <div class="vk-issue-header">
        <div class="vk-issue-heading">
            <div class="vk-issue-key">TASK-<?= (int)$t['id'] ?></div>
            <h2 class="vk-issue-title"><?= htmlspecialchars($t['title']) ?></h2>
            <div class="vk-issue-badges">
                <span class="uk-label vk-label vk-label-<?= htmlspecialchars($t['status']) ?>"><?= htmlspecialchars($this->statusLabel($t['status'])) ?></span>
                <span class="vk-priority vk-priority-<?= htmlspecialchars($t['priority']) ?>"><i class="fa fa-arrow-up"></i> <?= htmlspecialchars($this->priorityLabel($t['priority'])) ?></span>
            </div>
        </div>
        <div class="vk-actions">
            <?php if ($returnUrl): ?>
            <a href="<?= htmlspecialchars($returnUrl) ?>" class="uk-button uk-button-default uk-button-small"><i class="fa fa-arrow-left"></i> <?= __('Back to Tasks') ?></a>
            <?php endif; ?>
            <a href="#vk-edit-task" class="uk-button uk-button-default uk-button-small"><i class="fa fa-pencil"></i> <?= __('Edit details') ?></a>
            <form method="post" action="<?= $url ?>" onsubmit="return confirm('<?= __('Delete this task?') ?>')">
                <input type="hidden" name="<?= $csrfN ?>" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="delete_task">
                <input type="hidden" name="id" value="<?= $t['id'] ?>">
                <?php if ($returnUrl): ?><input type="hidden" name="return_url" value="<?= htmlspecialchars($returnUrl) ?>"><?php endif; ?>
                <button class="uk-button uk-button-danger uk-button-small"><i class="fa fa-trash"></i> <?= __('Delete') ?></button>
            </form>
        </div>
    </div>
    <div class="vk-issue-body">
        <div class="vk-issue-description">
            <div class="vk-issue-label"><?= __('Description') ?></div>
            <?php if (!empty($t['description'])): ?>
            <div class="vk-rich-text"><?= $this->renderRichText($t['description']) ?></div>
            <?php else: ?>
            <p class="vk-muted-line"><?= __('No description provided.') ?></p>
            <?php endif; ?>
        </div>
        <dl class="vk-issue-meta">
            <div><dt><?= __('Assignee') ?></dt><dd><?= htmlspecialchars($assigneeName) ?></dd></div>
            <div><dt><?= __('Collaborators') ?></dt><dd<?= $collaboratorNames ? ' uk-tooltip="' . htmlspecialchars($collaboratorNames, ENT_QUOTES) . '"' : '' ?>><?= $collaboratorNames ? htmlspecialchars($collaboratorNames) : __('None') ?></dd></div>
            <div><dt><?= __('Reviewers') ?></dt><dd<?= $reviewerNames ? ' uk-tooltip="' . htmlspecialchars($reviewerNames, ENT_QUOTES) . '"' : '' ?>><?= $reviewerNames ? htmlspecialchars($reviewerNames) : __('None') ?></dd></div>
            <div><dt><?= __('Due date') ?></dt><dd><?= htmlspecialchars($t['due_date'] ?: __('Not set')) ?></dd></div>
            <div><dt><?= __('Quarter') ?></dt><dd><?= htmlspecialchars($dueQuarterLabel ?: __('Not set')) ?></dd></div>
            <div><dt><?= __('Sprint') ?></dt><dd><?= htmlspecialchars($sprintName) ?></dd></div>
            <div><dt><?= __('Estimate') ?></dt><dd><?php $estD = $this->formatEstimate($t['estimate_h'] ?? ''); ?><?= $estD !== '' ? htmlspecialchars($estD) : __('Not set') ?></dd></div>
            <div><dt><?= __('Story points') ?></dt><dd><?= !empty($t['story_points']) ? (int)$t['story_points'] : '&mdash;' ?></dd></div>
            <div><dt><?= __('Linked page') ?></dt><dd><?php if ($linkedPage): ?><span class="<?= $linkedPageStatus['class'] ?>"<?= $linkedPageStatus['label'] !== '' ? ' title="' . htmlspecialchars($linkedPageStatus['label']) . '"' : '' ?>><?= $linkedPageStatus['icon'] ?><?= htmlspecialchars($linkedPageTitle) ?></span><?php else: ?><?= __('None') ?><?php endif; ?></dd></div>
        </dl>
    </div>
</section>
<?php endif; ?>

<div class="<?= $layoutClass ?>">
    <div class="vk-task-main">
        <div id="vk-edit-task" class="uk-card uk-card-default vk-task-card vk-form-compact <?= $isEdit ? 'vk-edit-card' : '' ?>">
            <div class="uk-card-header">
                <h3 class="vk-card-title"><?= $isEdit ? __('Edit details') : __('Task Details') ?></h3>
                <p><?= __('Keep the title action-oriented. Use the page picker only when the task belongs to a specific page.') ?></p>
            </div>
            <div class="uk-card-body">
                <form method="post" action="<?= $url ?>">
                    <input type="hidden" name="<?= $csrfN ?>" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="save_task">
                    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= $t['id'] ?>"><?php endif; ?>
                    <?php if ($returnUrl): ?><input type="hidden" name="return_url" value="<?= htmlspecialchars($returnUrl) ?>"><?php endif; ?>

                    <div class="vk-form-section">
                        <div class="vk-form-section-title"><?= __('Context') ?></div>
                        <div class="vk-field">
                            <label class="uk-form-label"><?= __('Task Title') ?> *</label>
                            <input type="text" name="title" value="<?= htmlspecialchars($t['title']) ?>" required autofocus class="uk-input">
                        </div>

                        <div class="vk-field">
                            <label class="uk-form-label"><?= __('Linked PW Page') ?> <span class="vk-inline-note"><?= __('(optional)') ?></span></label>
                            <div class="vk-picker" id="vk-picker">
                                <input type="text" id="vk-picker-input" class="uk-input"
                                    placeholder="<?= __('Search pages by title…') ?>"
                                    value="<?= $linkedPage ? htmlspecialchars($linkedPageTitle) : '' ?>"
                                    autocomplete="off">
                                <input type="hidden" name="page_id" id="vk-page-id" value="<?= (int)$t['page_id'] ?>">
                                <div class="vk-picker-results" id="vk-picker-results"></div>
                            </div>
                            <?php if ($linkedPage): ?>
                            <div class="vk-inline-actions vk-linked-page-actions">
                                <a href="<?= $adminUrl ?>page/edit/?id=<?= $linkedPage->id ?>" class="vk-chip <?= $linkedPageStatus['class'] ?>" target="_blank"<?= $linkedPageStatus['label'] !== '' ? ' title="' . htmlspecialchars($linkedPageStatus['label']) . '"' : '' ?>>
                                    <i class="fa fa-pencil-square-o"></i> <?= __('Edit:') ?> <?= $linkedPageStatus['icon'] ?><?= htmlspecialchars($linkedPageTitle) ?>
                                </a>
                                <?php if ($linkedPage->viewable()): ?>
                                <a href="<?= $linkedPage->httpUrl() ?>" class="vk-chip" target="_blank">
                                    <i class="fa fa-external-link"></i> <?= __('View on site') ?>
                                </a>
                                <?php endif; ?>
                                <button type="button" onclick="document.getElementById('vk-page-id').value='';document.getElementById('vk-picker-input').value='';this.closest('div').querySelectorAll('.vk-chip').forEach(e=>e.remove());" class="uk-button uk-button-default uk-button-small"><i class="fa fa-times"></i> <?= __('Clear') ?></button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="vk-form-section vk-workflow-section">
                        <div class="vk-form-section-title"><?= __('Workflow') ?></div>
                        <div class="vk-form-grid is-2">
                            <div>
                                <div class="vk-field">
                                    <label class="uk-form-label"><?= __('Status') ?></label>
                                    <select name="status" class="uk-select">
                                        <?php foreach (['open','in_progress','review','done'] as $v): ?>
                                        <option value="<?= $v ?>" <?= $t['status']===$v?'selected':'' ?>><?= htmlspecialchars($this->statusLabel($v)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <div class="vk-field">
                                    <label class="uk-form-label"><?= __('Priority') ?></label>
                                    <select name="priority" class="uk-select">
                                        <?php foreach (['low','medium','high','critical'] as $v): ?>
                                        <option value="<?= $v ?>" <?= $t['priority']===$v?'selected':'' ?>><?= htmlspecialchars($this->priorityLabel($v)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="vk-form-grid is-2">
                            <div>
                                <div class="vk-field">
                                    <label class="uk-form-label"><?= __('Due Date') ?></label>
                                    <input type="date" name="due_date" value="<?= $t['due_date'] ?: '' ?>" class="uk-input" data-quarter-date data-quarter-start="<?= (int)$this->quarterStartMonth() ?>">
                                    <div class="vk-field-hint" data-quarter-hint><?= $dueQuarterLabel ? htmlspecialchars($dueQuarterLabel) : __('No quarter until due date is set') ?></div>
                                </div>
                            </div>
                            <div>
                                <div class="vk-field">
                                    <label class="uk-form-label"><?= __('Assignee') ?></label>
                                    <select name="assignee_id" class="uk-select">
                                        <option value="">&mdash; <?= __('Unassigned') ?> &mdash;</option>
                                        <?php foreach ($users as $u): ?>
                                        <option value="<?= $u['id'] ?>" <?= $t['assignee_id']==$u['id']?'selected':'' ?>>
                                            <?= htmlspecialchars($u['name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="vk-field vk-reviewers-field">
                            <label class="uk-form-label"><?= __('Collaborators') ?></label>
                            <?= $this->renderPeopleSelect('collaborator_ids', $users, $collaboratorIds, __('Add collaborator'), __('Remove collaborator')) ?>
                        </div>

                        <div class="vk-field vk-reviewers-field">
                            <label class="uk-form-label"><?= __('Reviewers') ?></label>
                            <?= $this->renderReviewerSelect($users, $reviewerIds) ?>
                        </div>

                        <div class="vk-form-grid is-2">
                            <div>
                                <div class="vk-field">
                                    <label class="uk-form-label"><?= __('Section / Tag') ?> <span class="vk-inline-note"><?= __('(e.g. "Products", "SEO", "Blog")') ?></span></label>
                                    <select name="section" id="vk-section-select" class="uk-select">
                                        <option value="">&mdash; <?= __('None') ?> &mdash;</option>
                                        <?php foreach (($sections ?? []) as $s): ?>
                                        <option value="<?= htmlspecialchars($s) ?>" <?= ($t['section'] ?? '') === $s ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
                                        <?php endforeach; ?>
                                        <option value="__new__"><?= __('+ New section…') ?></option>
                                    </select>
                                </div>
                                <div class="vk-field vk-hidden-row" id="vk-new-section-row">
                                    <label class="uk-form-label"><?= __('New Section / Tag') ?></label>
                                    <input type="text" name="new_section" id="vk-new-section" maxlength="100" placeholder="<?= __('e.g. Products') ?>" class="uk-input">
                                </div>
                                <script>
                                (function () {
                                    var sel = document.getElementById('vk-section-select');
                                    if (!sel) return;
                                    var row = document.getElementById('vk-new-section-row');
                                    var inp = document.getElementById('vk-new-section');
                                    sel.addEventListener('change', function () {
                                        if (sel.value === '__new__') {
                                            row.classList.remove('vk-hidden-row');
                                            inp.required = true;
                                            inp.focus();
                                        } else {
                                            row.classList.add('vk-hidden-row');
                                            inp.required = false;
                                            inp.value = '';
                                        }
                                    });
                                })();
                                </script>
                            </div>
                            <div>
                                <div class="vk-field">
                                    <label class="uk-form-label"><?= __('Sprint') ?></label>
                                    <select name="sprint_id" class="uk-select">
                                        <option value="">&mdash; <?= __('No sprint') ?> &mdash;</option>
                                        <?php foreach ($sprints as $sp): ?>
                                        <option value="<?= $sp['id'] ?>" <?= ($t['sprint_id'] ?? 0) == $sp['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($sp['name']) ?>
                                            <?php if ($sp['status'] === 'active'): ?> &#9733;<?php endif; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="vk-form-section vk-planning-section">
                        <div class="vk-form-section-title"><?= __('Planning') ?></div>
                        <div class="vk-form-grid is-3">
                            <div>
                                <div class="vk-field">
                                    <label class="uk-form-label"><?= __('Estimate') ?> <span class="vk-inline-note"><?= __('(hours)') ?></span></label>
                                    <select name="estimate_h" class="uk-select">
                                        <option value="">&mdash;</option>
                                        <option value="0.25" <?= ($t['estimate_h'] ?? '') == 0.25 ? 'selected' : '' ?>><?= __('15m') ?></option>
                                        <option value="0.5" <?= ($t['estimate_h'] ?? '') == 0.5 ? 'selected' : '' ?>><?= __('30m') ?></option>
                                        <?php foreach ([1, 2, 4, 6, 8, 12, 16, 24, 32, 40] as $h): ?>
                                        <option value="<?= $h ?>" <?= ($t['estimate_h'] ?? '') == $h ? 'selected' : '' ?>><?= $h ?>h<?= $h === 4 ? ' ' . __('(default)') : '' ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <div class="vk-field">
                                    <label class="uk-form-label"><?= __('Actual Hours') ?></label>
                                    <?php $hasTimeLogs = $isEdit && !empty($task['time_logs']); ?>
                                    <?php if ($hasTimeLogs): ?>
                                    <input type="number" name="actual_h" value="<?= $t['actual_h'] !== null && $t['actual_h'] !== '' ? number_format((float)$t['actual_h'], 1) : '' ?>" step="0.5" min="0" readonly class="uk-input vk-readonly-input">
                                    <small class="vk-field-help"><?= __('Calculated from Time Log') ?></small>
                                    <?php else: ?>
                                    <input type="number" name="actual_h" value="<?= $t['actual_h'] !== null && $t['actual_h'] !== '' ? number_format((float)$t['actual_h'], 1) : '' ?>" step="0.5" min="0" placeholder="0.0" class="uk-input">
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <div class="vk-field">
                                    <label class="uk-form-label"><?= __('Story Points') ?></label>
                                    <select name="story_points" class="uk-select">
                                        <option value="">&mdash;</option>
                                        <?php foreach ([1, 2, 3, 5, 8, 13, 21] as $sp): ?>
                                        <option value="<?= $sp ?>" <?= ($t['story_points'] ?? '') == $sp ? 'selected' : '' ?>><?= $sp ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="vk-field">
                            <label class="uk-form-label"><?= __('Description / Notes') ?></label>
                            <div class="vk-rich-editor">
                                <?= $this->renderRichTextEditor('description', (string)($t['description'] ?? ''), 120) ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($isEdit): ?>
                    <div class="vk-form-section">
                        <?php $attachEntityType = 'task'; $attachEntityId = (int)$t['id']; require __DIR__ . '/partials/attachments.php'; ?>
                    </div>
                    <?php endif; ?>

                    <div class="vk-form-actions">
                        <button type="submit" class="uk-button uk-button-primary"><?= $returnUrl ? ($isEdit ? __('Save and return') : __('Create and return')) : ($isEdit ? __('Save Changes') : __('Create Task')) ?></button>
                        <a href="<?= htmlspecialchars($returnUrl ?: ($url . '?view=tasks')) ?>" class="uk-button uk-button-default"><?= __('Cancel') ?></a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if ($isEdit): ?>
    <div class="vk-task-sidebar">
        <div class="uk-card uk-card-default vk-discussion-card">
            <div class="uk-card-header"><h3 class="vk-card-title"><?= __('Discussion') ?></h3></div>
            <div class="uk-card-body">
                <?php if ($isEdit && $t['status'] === 'review'): ?>
                <form method="post" action="<?= $url ?>" class="vk-review-decision">
                    <input type="hidden" name="<?= $csrfN ?>" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="review_decision">
                    <input type="hidden" name="task_id" value="<?= $t['id'] ?>">
                    <input type="hidden" name="back" value="<?= htmlspecialchars($backUrl) ?>">
                    <label class="uk-form-label"><?= __('Review decision') ?></label>
                    <textarea name="text" class="uk-textarea" rows="2" placeholder="<?= __('Optional note…') ?>"></textarea>
                    <div class="vk-review-decision-actions">
                        <button type="submit" name="decision" value="approved" class="uk-button uk-button-primary uk-button-small"><i class="fa fa-check"></i> <?= __('Approve') ?></button>
                        <button type="submit" name="decision" value="changes_requested" class="uk-button uk-button-default uk-button-small"><i class="fa fa-undo"></i> <?= __('Request changes') ?></button>
                    </div>
                </form>
                <?php endif; ?>
                <?php if (!empty($task['comments'])): ?>
                <div class="vk-comment-list">
                    <?php foreach ($task['comments'] as $c): ?>
                    <div class="vk-comment">
                        <div class="vk-comment-head">
                            <span class="vk-comment-author"><?= htmlspecialchars($c['author_name']) ?></span>
                            <?php $cKind = $c['kind'] ?? 'comment'; ?>
                            <?php if ($cKind === 'approved'): ?><span class="uk-label vk-label vk-label-done"><?= __('Approved') ?></span>
                            <?php elseif ($cKind === 'changes_requested'): ?><span class="uk-label vk-label vk-label-review"><?= __('Changes requested') ?></span><?php endif; ?>
                            <span class="vk-comment-date"><?= $c['created_at'] ?></span>
                            <?php if ($curUser->isSuperuser() || $c['user_id'] == $curUser->id): ?>
                            <form method="post" action="<?= $url ?>" class="vk-inline-delete">
                                <input type="hidden" name="<?= $csrfN ?>" value="<?= $csrf ?>">
                                <input type="hidden" name="action" value="delete_comment">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <input type="hidden" name="back" value="<?= htmlspecialchars($backUrl) ?>">
                                <button class="vk-icon-button vk-icon-button-danger" title="<?= __('Delete') ?>" aria-label="<?= __('Delete') ?>" onclick="return confirm('<?= __('Delete?') ?>')"><i class="fa fa-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </div>
                        <div class="vk-comment-text vk-rich-text"><?= $this->renderRichText($c['text']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <form method="post" action="<?= $url ?>">
                    <input type="hidden" name="<?= $csrfN ?>" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="save_comment">
                    <input type="hidden" name="task_id" value="<?= $t['id'] ?>">
                    <input type="hidden" name="back" value="<?= htmlspecialchars($backUrl) ?>">
                    <div class="vk-rich-editor vk-comment-editor">
                        <?= $this->renderRichTextEditor('text', '', 110) ?>
                    </div>
                    <button type="submit" class="uk-button uk-button-primary uk-button-small vk-button-stack"><?= __('Post') ?></button>
                </form>
            </div>
        </div>

        <?php
        $timeLogs    = $task['time_logs'] ?? [];
        $totalLogged = array_sum(array_column($timeLogs, 'hours'));
        ?>
        <div class="uk-card uk-card-default vk-card-stack vk-time-card">
            <div class="uk-card-header vk-card-header-row">
                <div>
                    <h3 class="vk-card-title"><?= __('Time Log') ?></h3>
                    <span class="vk-time-subtitle"><?= __('Track effort on this task') ?></span>
                </div>
                <div class="vk-time-total">
                    <span><?= number_format($totalLogged, 1) ?>h</span>
                    <small><?php $estD = $this->formatEstimate($t['estimate_h'] ?? ''); ?><?= $estD !== '' ? sprintf(__('of %s'), htmlspecialchars($estD)) : __('logged') ?></small>
                </div>
            </div>
            <div class="uk-card-body">
                <?php if ($timeLogs): ?>
                <div class="vk-time-entries">
                    <?php foreach ($timeLogs as $log): ?>
                    <div class="vk-time-entry">
                        <div>
                            <span class="vk-time-hours"><?= number_format((float)$log['hours'], 1) ?>h</span>
                            <span class="vk-time-date"><?= htmlspecialchars($log['logged_date']) ?></span>
                            <div class="vk-time-person"><?= htmlspecialchars($log['user_name']) ?><?= $log['note'] ? ' - ' . htmlspecialchars($log['note']) : '' ?></div>
                        </div>
                        <div>
                            <?php if ($curUser->isSuperuser() || $log['user_id'] == $curUser->id): ?>
                            <form method="post" action="<?= $url ?>" class="vk-form-inline">
                                <input type="hidden" name="<?= $csrfN ?>" value="<?= $csrf ?>">
                                <input type="hidden" name="action"  value="delete_time_log">
                                <input type="hidden" name="id"      value="<?= $log['id'] ?>">
                                <input type="hidden" name="task_id" value="<?= $t['id'] ?>">
                                <input type="hidden" name="back" value="<?= htmlspecialchars($backUrl) ?>">
                                <button class="vk-icon-button vk-icon-button-danger" title="<?= __('Delete') ?>" aria-label="<?= __('Delete') ?>" onclick="return confirm('<?= __('Delete?') ?>')"><i class="fa fa-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="vk-time-empty">
                    <span><?= __('No time logged yet.') ?></span>
                    <small><?= __('Use the form below to record work against this task.') ?></small>
                </div>
                <?php endif; ?>

                <form method="post" action="<?= $url ?>" class="vk-time-form">
                    <input type="hidden" name="<?= $csrfN ?>" value="<?= $csrf ?>">
                    <input type="hidden" name="action"  value="log_time">
                    <input type="hidden" name="task_id" value="<?= $t['id'] ?>">
                    <input type="hidden" name="back" value="<?= htmlspecialchars($backUrl) ?>">
                    <div>
                        <label class="uk-form-label"><?= __('Hours') ?></label>
                        <input type="number" name="hours" step="0.5" min="0.5" max="24" placeholder="0.0" class="uk-input" required>
                    </div>
                    <div>
                        <label class="uk-form-label"><?= __('Date') ?></label>
                        <input type="date" name="logged_date" value="<?= date('Y-m-d') ?>" class="uk-input">
                    </div>
                    <div class="vk-time-note">
                        <label class="uk-form-label"><?= __('Note') ?> <span class="vk-inline-note"><?= __('(optional)') ?></span></label>
                        <input type="text" name="note" placeholder="<?= __('What did you work on?') ?>" class="uk-input">
                    </div>
                    <div class="vk-time-submit">
                        <button type="submit" class="uk-button uk-button-primary uk-button-small"><i class="fa fa-plus"></i> <?= __('Log time') ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
(function() {
    const dateInput = document.querySelector('[data-quarter-date]');
    const hint = document.querySelector('[data-quarter-hint]');
    if (!dateInput || !hint) return;
    const emptyText = <?= json_encode(__('No quarter until due date is set')) ?>;
    const fiscalStart = Math.max(1, Math.min(12, Number(dateInput.dataset.quarterStart || 1)));
    function updateQuarterHint() {
        if (!dateInput.value) {
            hint.textContent = emptyText;
            hint.classList.remove('is-active');
            return;
        }
        const parts = dateInput.value.split('-').map(Number);
        const offset = (parts[1] - fiscalStart + 12) % 12;
        const q = Math.floor(offset / 3) + 1;
        const fiscalYear = parts[1] < fiscalStart ? parts[0] - 1 : parts[0];
        hint.textContent = 'Q' + q + ' ' + fiscalYear;
        hint.classList.add('is-active');
    }
    dateInput.addEventListener('input', updateQuarterHint);
    dateInput.addEventListener('change', updateQuarterHint);
    updateQuarterHint();
})();

(function() {
    const input    = document.getElementById('vk-picker-input');
    const results  = document.getElementById('vk-picker-results');
    const hiddenId = document.getElementById('vk-page-id');
    if (!input) return;

    let timer;
    const sessionExpiredText = <?= json_encode(__('Session expired, please reload and try again.')) ?>;
    function parseJsonResponse(response) {
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                throw new Error(sessionExpiredText);
            }
        });
    }
    input.addEventListener('input', function() {
        clearTimeout(timer);
        const q = input.value.trim();
        if (q.length < 2) { results.classList.remove('open'); return; }
        timer = setTimeout(() => {
            fetch('<?= $url ?>?view=ajax-search&q=' + encodeURIComponent(q))
                .then(parseJsonResponse)
                .then(data => {
                    results.innerHTML = '';
                    if (!data.length) {
                        results.innerHTML = '<div class="vk-picker-result is-empty"><?= __('No results') ?></div>';
                    } else {
                        data.forEach(p => {
                            const div = document.createElement('div');
                            div.className = 'vk-picker-result';
                            const strong = document.createElement('strong');
                            const statusBits = [];
                            if (p.trashed) {
                                const icon = document.createElement('i');
                                icon.className = 'fa fa-trash vk-status-icon';
                                strong.appendChild(icon);
                                statusBits.push('<?= __('Trashed') ?>');
                            }
                            strong.appendChild(document.createTextNode(p.title));
                            if (p.hidden)      { strong.classList.add('vk-status-hidden');      statusBits.push('<?= __('Hidden') ?>'); }
                            if (p.unpublished) { strong.classList.add('vk-status-unpublished'); statusBits.push('<?= __('Unpublished') ?>'); }
                            if (statusBits.length) div.title = statusBits.join(', ');
                            const small = document.createElement('small');
                            small.textContent = ' ' + p.url + ' · ' + p.template;
                            div.appendChild(strong);
                            div.appendChild(small);
                            div.addEventListener('mousedown', () => {
                                hiddenId.value = p.id;
                                input.value    = p.title;
                                results.classList.remove('open');
                            });
                            results.appendChild(div);
                        });
                    }
                    results.classList.add('open');
                })
                .catch(error => {
                    results.innerHTML = '';
                    const div = document.createElement('div');
                    div.className = 'vk-picker-result is-empty';
                    div.textContent = error.message || sessionExpiredText;
                    results.appendChild(div);
                    results.classList.add('open');
                });
        }, 280);
    });

    document.addEventListener('click', e => {
        if (!e.target.closest('#vk-picker')) results.classList.remove('open');
    });
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/_layout.php';

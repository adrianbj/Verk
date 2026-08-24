<?php namespace ProcessWire;

require_once __DIR__ . '/src/Services/VerkExportService.php';
require_once __DIR__ . '/src/Services/VerkExternalApprovals.php';
require_once __DIR__ . '/src/Traits/VerkUiTrait.php';
require_once __DIR__ . '/src/Traits/VerkAuditTrait.php';
require_once __DIR__ . '/src/Traits/VerkSprintTrait.php';
require_once __DIR__ . '/src/Traits/VerkDataTrait.php';
require_once __DIR__ . '/src/Traits/VerkEndpointTrait.php';
require_once __DIR__ . '/src/Traits/VerkMetaTrait.php';

/**
 * Verk
 *
 * Site operations layer for ProcessWire.
 * Tasks, sprints, quarter planning, editorial calendar, content audit, and knowledge base.
 *
 * @author  Maxim Semenov <maxim@smnv.org> (smnv.org)
 * @license MIT
 * @version 160
 */
class Verk extends Process implements Module, ConfigurableModule {

    private VerkExportService $export;
    public VerkFiles $files;
    public VerkNotify $notify;

    use VerkUiTrait;
    use VerkAuditTrait;
    use VerkSprintTrait;
    use VerkDataTrait;
    use VerkEndpointTrait;
    use VerkMetaTrait;

    public static function getModuleInfo(): array {
        return [
            'title'    => 'Verk',
            'version'  => 160,
            'summary'  => 'Site ops layer for ProcessWire: tasks, sprints, quarter planning, editorial calendar, content audit, and knowledge base.',
            'author'   => 'Maxim Semenov',
            'href'     => 'https://smnv.org',
            'icon'     => 'dashboard',
            'singular' => true,
            'autoload' => 'template=admin',
            'requires' => ['ProcessWire>=3.0.200', 'PHP>=8.0'],
            'permission'  => 'verk',
            'permissions' => ['verk' => 'Use the Verk site operations panel'],
            'page'     => [
                'name'   => 'verk',
                'parent' => 'admin',
                'title'  => 'Verk',
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Module config defaults
    // -------------------------------------------------------------------------

    public static function getDefaultConfig(): array {
        return [
            'calendar_template'  => 'basic-page',
            'calendar_date_field'=> 'regional_publish_date',
            'quarter_start_month' => 1,
            'audit_rules'        => '',   // JSON array of audit rule objects
            'page_widget_enabled' => 1,
            'page_widget_position' => 'top',
            'page_widget_collapsed' => 0,
            'page_widget_limit' => 12,
            'page_widget_sort' => 'due',
            'page_widget_show_done' => 1,
            'page_widget_show_create' => 1,
            'page_widget_show_empty' => 1,
            'page_widget_show_status' => 1,
            'page_widget_show_priority' => 1,
            'page_widget_show_due_date' => 1,
            'page_widget_show_quarter' => 1,
            'page_widget_show_assignee' => 1,
            'assignee_roles' => '',
            'notify_enabled' => 1,
            'notify_assignee' => 1,
            'notify_collaborator' => 1,
            'notify_reviewer' => 1,
            'notify_status' => 0,
            'status_edit_reviewer' => 0,
            'status_edit_collaborator' => 0,
        ];
    }

    public function getModuleConfigInputfields(array $data): InputfieldWrapper {
        $wrapper = $this->wire('modules')->get('InputfieldWrapper');
        if ($this->wire('modules')->isInstalled('InputfieldMarkup')) {
            $note = $this->wire('modules')->get('InputfieldMarkup');
            $note->label = 'Verk Settings';
            $note->value = '<p>Configure Verk on the <strong>Verk → Settings</strong> page in the admin menu.</p>';
            $wrapper->add($note);
        }
        return $wrapper;
    }

    // -------------------------------------------------------------------------
    // Install / Uninstall
    // -------------------------------------------------------------------------

    public function ___install(): void {
        parent::___install();
        require_once __DIR__ . '/src/Services/VerkDB.php';
        VerkDB::install($this->wire('database'));
        VerkDB::migrate($this->wire('database'));
    }

    public function ___uninstall(): void {
        parent::___uninstall();
        require_once __DIR__ . '/src/Services/VerkDB.php';
        VerkDB::uninstall($this->wire('database'), $this->wire('config')->paths->assets . 'Verk/');
    }

    public function ___upgrade($fromVersion, $toVersion): void {
        require_once __DIR__ . '/src/Services/VerkDB.php';
        VerkDB::migrate($this->wire('database'));

        // Installs upgrading from a version that predates module-level
        // permissions won't get the `verk` permission from the moduleInfo
        // `permissions` array (that only runs on fresh install), so create it here.
        $permissions = $this->wire('permissions');
        if (!$permissions->get('verk')->id) {
            $p = $permissions->add('verk');
            $p->title = 'Use the Verk site operations panel';
            $p->save();
        }
    }

    // -------------------------------------------------------------------------
    // Autoload hooks
    // -------------------------------------------------------------------------

    public function init(): void {
        $this->export = new VerkExportService($this);
        require_once __DIR__ . '/src/Services/VerkFiles.php';
        $this->files = new VerkFiles($this);
        require_once __DIR__ . '/src/Services/VerkNotify.php';
        $this->notify = new VerkNotify($this);
        // Inject task widget into page editor
        $this->addHookAfter('ProcessPageEdit::buildForm', $this, 'hookPageEditWidget');
    }

    /** Public, idempotent integration API; metadata must already be redacted. */
    public function createExternalApproval(string $provider, string $externalId, array $metadata, int $createdBy): array {
        return (new VerkExternalApprovals($this))->create($provider, $externalId, $metadata, $createdBy);
    }

    public function getExternalApproval(string $provider, string $externalId): ?array {
        return (new VerkExternalApprovals($this))->find($provider, $externalId);
    }

    public function hookPageEditWidget(HookEvent $event): void {
        $cfg = $this->getConfig();
        if (empty($cfg['page_widget_enabled'])) return;
        if (!$this->shouldRenderPageEditWidget()) return;

        $form    = $event->return;
        $page    = $event->object->getPage();
        if (!$page || !$page->id) return;

        $limit = max(1, min(50, (int)$cfg['page_widget_limit']));
        $where = "t.page_id = :pid";
        if (empty($cfg['page_widget_show_done'])) {
            $where .= " AND t.status != 'done'";
        }
        $sort = match($cfg['page_widget_sort'] ?? 'status') {
            'newest' => 't.created_at DESC',
            'due' => '(t.due_date IS NULL), t.due_date ASC, t.created_at DESC',
            'priority' => "FIELD(t.priority, 'critical', 'high', 'medium', 'low'), t.created_at DESC",
            default => 't.status ASC, t.created_at DESC',
        };
        $db    = $this->wire('database');
        $stmt  = $db->prepare(
            "SELECT t.id, t.title, t.status, t.priority, t.due_date, t.assignee_id
             FROM vk_tasks t
             WHERE $where
             ORDER BY $sort
             LIMIT $limit"
        );
        $stmt->execute([':pid' => $page->id]);
        $tasks = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $deskPage = $this->wire('pages')->get('name=verk, template=admin');
        if (!$deskPage->id) return;
        $deskUrl    = $deskPage->url;
        $newTaskUrl = $deskUrl . '?view=task-edit&page_id=' . $page->id;

        $sanitizer = $this->wire('sanitizer');
        $html = '<style>
            .verk-page-widget{display:grid;gap:8px;margin:0 0 4px}
            .verk-page-widget__head{align-items:center;display:flex;gap:10px;justify-content:space-between}
            .verk-page-widget__title{color:var(--pw-text-color);font-size:.86rem;line-height:1.2}
            .verk-page-widget__new{align-items:center;border:1px solid var(--pw-border-color);border-radius:4px;color:var(--pw-main-color);display:inline-flex;font-size:.76rem;gap:5px;line-height:1;min-height:28px;padding:0 9px;text-decoration:none}
            .verk-page-widget__new:hover{border-color:var(--pw-main-color);text-decoration:none}
            .verk-page-widget__list{display:grid;gap:5px}
            .verk-page-widget__task{align-items:center;background:var(--pw-content-background);border:1px solid var(--pw-border-color);border-radius:4px;color:var(--pw-text-color);display:grid;gap:8px;grid-template-columns:8px minmax(0,1fr) auto;min-height:36px;padding:6px 8px;text-decoration:none}
            .verk-page-widget__task:hover{border-color:var(--pw-main-color);text-decoration:none}
            .verk-page-widget__dot{border-radius:50%;height:8px;width:8px}
            .verk-page-widget__dot.is-low{background:var(--pw-alert-success)}
            .verk-page-widget__dot.is-medium{background:var(--pw-main-color)}
            .verk-page-widget__dot.is-high{background:var(--pw-alert-warning)}
            .verk-page-widget__dot.is-critical{background:var(--pw-error-inline-text-color)}
            .verk-page-widget__name{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
            .verk-page-widget__meta{color:var(--pw-muted-color);font-size:.74rem;text-align:right;white-space:nowrap}
            .verk-page-widget__empty{color:var(--pw-muted-color);font-size:.82rem}
            .verk-page-widget__audit{display:grid;gap:6px;margin-top:12px}
            .verk-page-widget__audit-head{align-items:center;color:var(--pw-muted-color);display:flex;font-size:.7rem;font-weight:600;gap:7px;letter-spacing:.05em;text-transform:uppercase}
            .verk-page-widget__audit-count{align-items:center;background:var(--pw-alert-warning);border-radius:999px;color:color-mix(in srgb,var(--pw-alert-warning) 25%,var(--pw-text-color));display:inline-flex;font-size:.66rem;font-weight:600;justify-content:center;line-height:1;min-width:17px;padding:3px 6px}
            .verk-page-widget__gap{align-items:center;background:color-mix(in srgb,var(--pw-alert-warning) 14%,var(--pw-content-background));border:1px solid color-mix(in srgb,var(--pw-alert-warning) 45%,var(--pw-border-color));border-left:3px solid color-mix(in srgb,var(--pw-alert-warning) 60%,var(--pw-text-color));border-radius:4px;display:grid;gap:9px;grid-template-columns:auto minmax(0,1fr);padding:8px 11px}
            .verk-page-widget__gap-icon{color:color-mix(in srgb,var(--pw-alert-warning) 50%,var(--pw-text-color));font-size:.9rem;line-height:1}
            .verk-page-widget__gap-text{display:grid;gap:1px;min-width:0}
            .verk-page-widget__gap-label{color:var(--pw-text-color);font-size:.82rem;font-weight:500}
            .verk-page-widget__gap-msg{color:var(--pw-muted-color);font-size:.74rem;line-height:1.3}
        </style>';
        $html .= '<div class="verk-page-widget">';
        $html .= '<div class="verk-page-widget__head">';
        $html .= '<span class="verk-page-widget__title">' . $this->_('Verk Tasks') . '</span>';
        if (!empty($cfg['page_widget_show_create'])) {
            $html .= '<a href="' . $sanitizer->entities($newTaskUrl) . '" class="verk-page-widget__new"><i class="fa fa-plus"></i>' . $this->_('New task') . '</a>';
        }
        $html .= '</div>';

        if ($tasks) {
            $html .= '<div class="verk-page-widget__list">';
            foreach ($tasks as $t) {
                $priorityClass = preg_replace('/[^a-z0-9_-]/', '', (string)($t['priority'] ?? 'medium')) ?: 'medium';
                $sLabel = $this->statusLabel((string)$t['status']);
                $meta = [];
                if (!empty($cfg['page_widget_show_status'])) $meta[] = $sLabel;
                if (!empty($cfg['page_widget_show_priority'])) $meta[] = $this->priorityLabel((string)$t['priority']);
                if (!empty($cfg['page_widget_show_due_date']) && !empty($t['due_date'])) $meta[] = date('M j', strtotime((string)$t['due_date']));
                if (!empty($cfg['page_widget_show_quarter']) && !empty($t['due_date'])) $meta[] = $this->quarterLabelForDate((string)$t['due_date']);
                if (!empty($cfg['page_widget_show_assignee']) && !empty($t['assignee_id'])) {
                    $assignee = $this->wire('users')->get((int)$t['assignee_id']);
                    if ($assignee->id) $meta[] = $sanitizer->entities($assignee->name);
                }
                $editUrl = $deskUrl . '?view=task-edit&id=' . (int)$t['id'];
                $html .= '<a href="' . $sanitizer->entities($editUrl) . '" class="verk-page-widget__task">';
                $html .= '<span class="verk-page-widget__dot is-' . $priorityClass . '"></span>';
                $html .= '<span class="verk-page-widget__name">' . $sanitizer->entities($t['title']) . '</span>';
                if ($meta) {
                    $html .= '<span class="verk-page-widget__meta">' . implode(' · ', $meta) . '</span>';
                }
                $html .= '</a>';
            }
            $html .= '</div>';
        } elseif (!empty($cfg['page_widget_show_empty'])) {
            $html .= '<div class="verk-page-widget__empty">' . $this->_('No tasks for this page.') . '</div>';
        }

        $gaps = $this->getPageAuditGaps($page);
        if ($gaps) {
            $html .= '<div class="verk-page-widget__audit">';
            $html .= '<span class="verk-page-widget__audit-head">' . $this->_('Audit gaps')
                . '<span class="verk-page-widget__audit-count">' . count($gaps) . '</span></span>';
            foreach ($gaps as $gap) {
                $html .= '<div class="verk-page-widget__gap">';
                $html .= '<i class="fa fa-flag-o verk-page-widget__gap-icon" aria-hidden="true"></i>';
                $html .= '<span class="verk-page-widget__gap-text">';
                $html .= '<span class="verk-page-widget__gap-label">' . $sanitizer->entities($gap['label']) . '</span>';
                // Only show the message when it adds something beyond the label.
                if ($gap['message'] !== '' && strcasecmp(trim($gap['message']), trim($gap['label'])) !== 0) {
                    $html .= '<span class="verk-page-widget__gap-msg">' . $sanitizer->entities($gap['message']) . '</span>';
                }
                $html .= '</span>';
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';

        $field = $this->wire('modules')->get('InputfieldMarkup');
        $field->label = 'Verk';
        $field->icon  = 'dashboard';
        $field->value = $html;
        $field->collapsed = !empty($cfg['page_widget_collapsed']) ? Inputfield::collapsedYes : Inputfield::collapsedNo;

        if (($cfg['page_widget_position'] ?? 'top') === 'bottom') {
            $form->append($field);
        } else {
            $form->prepend($field);
        }
    }

    protected function shouldRenderPageEditWidget(): bool {
        $input = $this->wire('input');
        foreach (['field', 'fields', 'field_id', 'file', 'filename', 'InputfieldFileAjax', 'InputfieldImageAjax'] as $key) {
            $value = $input->get($key);
            if ($value !== null && $value !== '') return false;
        }
        return true;
    }

    // -------------------------------------------------------------------------
    // Router
    // -------------------------------------------------------------------------

    public function ___execute(): string {
        $input = $this->wire('input');

        if ($input->requestMethod('POST')) {
            $postView = $input->get('view', 'string');
            if ($postView === 'file-upload') return $this->viewFileUpload();
            if ($postView === 'file-delete') return $this->viewFileDelete();
            $action = $input->post('action', 'string');
            return match($action) {
                'save_task'      => $this->actionSaveTask(),
                'delete_task'    => $this->actionDeleteTask(),
                'save_note'      => $this->actionSaveNote(),
                'delete_note'    => $this->actionDeleteNote(),
                'save_comment'   => $this->actionSaveComment(),
                'delete_comment' => $this->actionDeleteComment(),
                'review_decision' => $this->actionReviewDecision(),
                'update_task_status' => $this->actionUpdateTaskStatus(),
                'save_audit_rules' => $this->actionSaveAuditRules(),
                'save_settings'    => $this->actionSaveSettings(),
                'save_sprint'      => $this->actionSaveSprint(),
                'update_sprint_status' => $this->actionUpdateSprintStatus(),
                'delete_sprint'    => $this->actionDeleteSprint(),
                'attach_sprint_task' => $this->actionAttachSprintTask(),
                'detach_sprint_task' => $this->actionDetachSprintTask(),
                'log_time'         => $this->actionLogTime(),
                'delete_time_log'  => $this->actionDeleteTimeLog(),
                'bulk_audit_tasks' => $this->actionBulkAuditTasks(),
                default          => $this->redirect(),
            };
        }

        $view = $input->get('view', 'string') ?: 'dashboard';
        if (!in_array($view, ['ajax-search', 'ajax-sprint-tasks', 'export-docx', 'file-upload', 'file', 'file-delete'], true)) {
            $this->setAdminChrome($view);
        }
        return match($view) {
            'tasks'     => $this->viewTasks(),
            'task-edit' => $this->viewTaskEdit(),
            'calendar'  => $this->viewCalendar(),
            'audit'     => $this->viewAudit(),
            'kb'        => $this->viewKB(),
            'note-edit' => $this->viewNoteEdit(),
            'settings'  => $this->viewSettings(),
            'sprints'      => $this->viewSprints(),
            'sprint-edit'  => $this->viewSprintEdit(),
            'ajax-search'  => $this->viewAjaxSearch(),
            'ajax-sprint-tasks' => $this->viewAjaxSprintTasks(),
            'bulk-audit'   => $this->viewBulkAuditForm(),
            'export-docx'  => $this->viewExportDocx(),
            'file-upload'  => $this->viewFileUpload(),
            'file'         => $this->viewFile(),
            'file-delete'  => $this->viewFileDelete(),
            default        => $this->viewDashboard(),
        };
    }

    // -------------------------------------------------------------------------
    // Views
    // -------------------------------------------------------------------------

    protected function viewDashboard(): string {
        $db      = $this->wire('database');
        $user    = $this->wire('user');
        $input   = $this->wire('input');
        $uid     = (int)$user->id;
        $dashboardQuarterInput = (int)$input->get('quarter');
        $dashboardQuarter = $dashboardQuarterInput
            ? $this->quarterContext($dashboardQuarterInput, (int)$input->get('year') ?: (int)date('Y'))
            : $this->quarterContextForDate(date('Y-m-d'));

        // My tasks — paginated
        $myPage    = max(1, (int)$input->get('my_page', 'int') ?: 1);
        $myLimit   = 5;
        $myOffset  = ($myPage - 1) * $myLimit;

        $stmt = $db->prepare("SELECT COUNT(*) FROM vk_tasks WHERE assignee_id = :uid AND status != 'done'");
        $stmt->execute([':uid' => $uid]);
        $myTaskTotal = (int)$stmt->fetchColumn();
        $myTotalPages = max(1, (int)ceil($myTaskTotal / $myLimit));

        $stmt = $db->prepare(
            "SELECT t.* FROM vk_tasks t
             WHERE t.assignee_id = :uid AND t.status != 'done'
             ORDER BY FIELD(t.priority,'critical','high','medium','low'), t.due_date ASC
             LIMIT $myLimit OFFSET $myOffset"
        );
        $stmt->execute([':uid' => $uid]);
        $myTasks = $this->enrichTaskPagesBatch($stmt->fetchAll(\PDO::FETCH_ASSOC));

        // My reviews — tasks in review where the current user is a reviewer
        $stmt = $db->prepare(
            "SELECT t.* FROM vk_tasks t
             JOIN vk_task_reviewers r ON r.task_id = t.id
             WHERE r.user_id = :uid AND t.status = 'review'
             ORDER BY FIELD(t.priority,'critical','high','medium','low'), t.due_date IS NULL ASC, t.due_date ASC
             LIMIT 8"
        );
        $stmt->execute([':uid' => $uid]);
        $myReviews = $this->enrichTaskPagesBatch($stmt->fetchAll(\PDO::FETCH_ASSOC));

        // Collaborating on — open tasks where the current user is a collaborator
        $stmt = $db->prepare(
            "SELECT t.* FROM vk_tasks t
             JOIN vk_task_collaborators c ON c.task_id = t.id
             WHERE c.user_id = :uid AND t.status != 'done'
             ORDER BY FIELD(t.priority,'critical','high','medium','low'), t.due_date IS NULL ASC, t.due_date ASC
             LIMIT 8"
        );
        $stmt->execute([':uid' => $uid]);
        $myCollaborations = $this->enrichTaskPagesBatch($stmt->fetchAll(\PDO::FETCH_ASSOC));

        // Recent tasks (all) — paginated
        $recentPage    = max(1, (int)$input->get('recent_page', 'int') ?: 1);
        $recentLimit   = 8;
        $recentOffset  = ($recentPage - 1) * $recentLimit;

        $stmt = $db->query("SELECT COUNT(*) FROM vk_tasks WHERE status != 'done'");
        $recentTotal = (int)$stmt->fetchColumn();
        $recentTotalPages = max(1, (int)ceil($recentTotal / $recentLimit));

        $stmt = $db->query(
            "SELECT t.* FROM vk_tasks t WHERE t.status != 'done' ORDER BY t.created_at DESC LIMIT $recentLimit OFFSET $recentOffset"
        );
        $recentTasks = $this->enrichTaskPagesBatch($stmt->fetchAll(\PDO::FETCH_ASSOC));

        // Upcoming calendar items (next 14 days)
        $upcomingAll = $this->getUpcomingPublications(14);
        $upcomingTotal = count($upcomingAll);
        $upcoming = array_slice($upcomingAll, 0, 5);

        $stmt = $db->prepare(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status='done' THEN 1 ELSE 0 END) as done_count,
                SUM(CASE WHEN status!='done' THEN 1 ELSE 0 END) as open_count
             FROM vk_tasks
             WHERE due_date IS NOT NULL AND due_date >= :start AND due_date <= :end"
        );
        $stmt->execute([
            ':start' => $dashboardQuarter['start'],
            ':end'   => $dashboardQuarter['end'],
        ]);
        $quarterTaskStats = $stmt->fetch(\PDO::FETCH_ASSOC) ?: ['total' => 0, 'done_count' => 0, 'open_count' => 0];
        $quarterTaskTotal = (int)($quarterTaskStats['total'] ?? 0);
        $quarterDoneCount = (int)($quarterTaskStats['done_count'] ?? 0);
        $quarterProgress = $quarterTaskTotal > 0 ? (int)round($quarterDoneCount / $quarterTaskTotal * 100) : 0;

        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM vk_tasks
             WHERE status != 'done'
               AND due_date IS NOT NULL
               AND due_date < :today"
        );
        $stmt->execute([':today' => date('Y-m-d')]);
        $overdueTaskCount = (int)$stmt->fetchColumn();

        $stmt = $db->query("SELECT COUNT(*) FROM vk_tasks WHERE status != 'done' AND (assignee_id IS NULL OR assignee_id = 0)");
        $unassignedTaskCount = (int)$stmt->fetchColumn();

        // Audit quick summary
        $auditSummary = $this->getAuditSummary(true);

        // Selected quarter sprint plan
        $stmt = $db->prepare(
            "SELECT s.*,
                    COUNT(t.id) as task_count,
                    SUM(CASE WHEN t.status='done' THEN 1 ELSE 0 END) as done_count
             FROM vk_sprints s
             LEFT JOIN vk_tasks t ON t.sprint_id = s.id
             WHERE s.status IN ('active','planned')
               AND s.start_date IS NOT NULL
               AND s.start_date <= :end
               AND (s.end_date IS NULL OR s.end_date >= :start)
             GROUP BY s.id
             ORDER BY FIELD(s.status,'active','planned'), s.start_date ASC
             LIMIT 4"
        );
        $stmt->execute([
            ':start' => $dashboardQuarter['start'],
            ':end'   => $dashboardQuarter['end'],
        ]);
        $dashboardSprints = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $dashboardSprintCount = count($dashboardSprints);

        // Stats
        $stmt = $db->query("SELECT status, COUNT(*) as n FROM vk_tasks GROUP BY status");
        $taskStats = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $r) $taskStats[$r['status']] = $r['n'];

        ob_start(); require __DIR__ . '/views/dashboard.php'; return ob_get_clean();
    }

    protected function viewTasks(): string {
        $db       = $this->wire('database');
        $input    = $this->wire('input');
        $validStatus = ['active', 'open', 'in_progress', 'review', 'done'];
        $validPrio   = ['low', 'medium', 'high', 'critical'];
        $status   = in_array($input->get('status', 'string'), $validStatus, true) ? $input->get('status', 'string') : '';
        $prio     = in_array($input->get('priority', 'string'), $validPrio, true)  ? $input->get('priority', 'string') : '';
        $assigneeId = (int)$input->get('assignee_id');
        $reviewerId = (int)$input->get('reviewer_id');
        $collaboratorId = (int)$input->get('collaborator_id');
        $sprintId = (int)$input->get('sprint_id');
        $search   = trim(substr($input->get('q', 'string') ?: '', 0, 120));
        $sort      = $input->get('sort', 'string') ?: 'default';
        $sortSqlMap = [
            'default' => "FIELD(t.status,'open','in_progress','review','done'), FIELD(t.priority,'critical','high','medium','low'), t.due_date IS NULL ASC, t.due_date ASC, t.created_at DESC",
            'due' => "t.due_date IS NULL ASC, t.due_date ASC, FIELD(t.priority,'critical','high','medium','low'), t.created_at DESC",
            'priority' => "FIELD(t.priority,'critical','high','medium','low'), t.due_date IS NULL ASC, t.due_date ASC, t.created_at DESC",
            'created' => "t.created_at DESC",
            'title' => "t.title ASC",
        ];
        if (!isset($sortSqlMap[$sort])) $sort = 'default';
        $quarter  = (int)$input->get('quarter');
        $dateStateInput = $input->get('date_state', 'string');
        $taskDateState = in_array($dateStateInput, ['none', 'overdue'], true) ? $dateStateInput : '';
        $taskQuarterYear = (int)$input->get('year');
        if (!$taskQuarterYear) $taskQuarterYear = (int)$this->quarterContextForDate(date('Y-m-d'))['year'];
        $taskQuarter = $quarter ? $this->quarterContext($quarter, $taskQuarterYear) : null;

        $pageNum = max(1, (int)$input->get('page', 'int') ?: 1);
        $limit   = min(100, max(10, (int)$input->get('limit', 'int') ?: 10));

        $where  = ['1=1'];
        $params = [];
        if ($status !== '') { $where[] = $this->taskStatusWhere($status, $params); }
        if ($prio)     { $where[] = 't.priority = :prio';       $params[':prio']   = $prio; }
        if ($assigneeId === -1) { $where[] = '(t.assignee_id IS NULL OR t.assignee_id = 0)'; }
        elseif ($assigneeId > 0) { $where[] = 't.assignee_id = :assignee_id'; $params[':assignee_id'] = $assigneeId; }
        if ($reviewerId === -1) { $where[] = 'NOT EXISTS (SELECT 1 FROM vk_task_reviewers r WHERE r.task_id = t.id)'; }
        elseif ($reviewerId > 0) { $where[] = 'EXISTS (SELECT 1 FROM vk_task_reviewers r WHERE r.task_id = t.id AND r.user_id = :reviewer_id)'; $params[':reviewer_id'] = $reviewerId; }
        if ($collaboratorId === -1) { $where[] = 'NOT EXISTS (SELECT 1 FROM vk_task_collaborators c WHERE c.task_id = t.id)'; }
        elseif ($collaboratorId > 0) { $where[] = 'EXISTS (SELECT 1 FROM vk_task_collaborators c WHERE c.task_id = t.id AND c.user_id = :collaborator_id)'; $params[':collaborator_id'] = $collaboratorId; }
        if ($sprintId === -1) { $where[] = '(t.sprint_id IS NULL OR t.sprint_id = 0)'; }
        elseif ($sprintId > 0) { $where[] = 't.sprint_id = :sprint_id'; $params[':sprint_id'] = $sprintId; }
        if ($search) {
            $where[] = '(t.title LIKE :search OR t.section LIKE :search OR t.description LIKE :search OR t.id = :search_id)';
            $params[':search'] = '%' . $search . '%';
            $params[':search_id'] = ctype_digit($search) ? (int)$search : 0;
        }
        if ($taskQuarter) {
            $where[] = 't.due_date IS NOT NULL AND t.due_date >= :quarter_start AND t.due_date <= :quarter_end';
            $params[':quarter_start'] = $taskQuarter['start'];
            $params[':quarter_end'] = $taskQuarter['end'];
        } elseif ($taskDateState === 'none') {
            $where[] = 't.due_date IS NULL';
        } elseif ($taskDateState === 'overdue') {
            $where[] = "t.due_date IS NOT NULL AND t.due_date < :today AND t.status != 'done'";
            $params[':today'] = date('Y-m-d');
        }

        $whereSql = implode(' AND ', $where);

        $quarterBaseWhere = ['1=1'];
        $quarterBaseParams = [];
        if ($status !== '') { $quarterBaseWhere[] = $this->taskStatusWhere($status, $quarterBaseParams); }
        if ($prio)     { $quarterBaseWhere[] = 't.priority = :prio';       $quarterBaseParams[':prio'] = $prio; }
        if ($assigneeId === -1) { $quarterBaseWhere[] = '(t.assignee_id IS NULL OR t.assignee_id = 0)'; }
        elseif ($assigneeId > 0) { $quarterBaseWhere[] = 't.assignee_id = :assignee_id'; $quarterBaseParams[':assignee_id'] = $assigneeId; }
        if ($reviewerId === -1) { $quarterBaseWhere[] = 'NOT EXISTS (SELECT 1 FROM vk_task_reviewers r WHERE r.task_id = t.id)'; }
        elseif ($reviewerId > 0) { $quarterBaseWhere[] = 'EXISTS (SELECT 1 FROM vk_task_reviewers r WHERE r.task_id = t.id AND r.user_id = :reviewer_id)'; $quarterBaseParams[':reviewer_id'] = $reviewerId; }
        if ($collaboratorId === -1) { $quarterBaseWhere[] = 'NOT EXISTS (SELECT 1 FROM vk_task_collaborators c WHERE c.task_id = t.id)'; }
        elseif ($collaboratorId > 0) { $quarterBaseWhere[] = 'EXISTS (SELECT 1 FROM vk_task_collaborators c WHERE c.task_id = t.id AND c.user_id = :collaborator_id)'; $quarterBaseParams[':collaborator_id'] = $collaboratorId; }
        if ($sprintId === -1) { $quarterBaseWhere[] = '(t.sprint_id IS NULL OR t.sprint_id = 0)'; }
        elseif ($sprintId > 0) { $quarterBaseWhere[] = 't.sprint_id = :sprint_id'; $quarterBaseParams[':sprint_id'] = $sprintId; }
        if ($search) {
            $quarterBaseWhere[] = '(t.title LIKE :search OR t.section LIKE :search OR t.description LIKE :search OR t.id = :search_id)';
            $quarterBaseParams[':search'] = '%' . $search . '%';
            $quarterBaseParams[':search_id'] = ctype_digit($search) ? (int)$search : 0;
        }
        $quarterBaseSql = implode(' AND ', $quarterBaseWhere);
        $taskQuarterCounts = [];
        for ($q = 1; $q <= 4; $q++) {
            $ctx = $this->quarterContext($q, $taskQuarterYear);
            $countStmt = $db->prepare(
                "SELECT COUNT(*) FROM vk_tasks t
                 WHERE $quarterBaseSql
                   AND t.due_date IS NOT NULL
                   AND t.due_date >= :q_start
                   AND t.due_date <= :q_end"
            );
            $countStmt->execute($quarterBaseParams + [
                ':q_start' => $ctx['start'],
                ':q_end' => $ctx['end'],
            ]);
            $taskQuarterCounts[$q] = [
                'context' => $ctx,
                'count' => (int)$countStmt->fetchColumn(),
            ];
        }
        $noDueStmt = $db->prepare("SELECT COUNT(*) FROM vk_tasks t WHERE $quarterBaseSql AND t.due_date IS NULL");
        $noDueStmt->execute($quarterBaseParams);
        $taskNoDueCount = (int)$noDueStmt->fetchColumn();

        $statusBaseWhere = ['1=1'];
        $statusBaseParams = [];
        if ($prio)     { $statusBaseWhere[] = 't.priority = :prio';       $statusBaseParams[':prio'] = $prio; }
        if ($assigneeId === -1) { $statusBaseWhere[] = '(t.assignee_id IS NULL OR t.assignee_id = 0)'; }
        elseif ($assigneeId > 0) { $statusBaseWhere[] = 't.assignee_id = :assignee_id'; $statusBaseParams[':assignee_id'] = $assigneeId; }
        if ($reviewerId === -1) { $statusBaseWhere[] = 'NOT EXISTS (SELECT 1 FROM vk_task_reviewers r WHERE r.task_id = t.id)'; }
        elseif ($reviewerId > 0) { $statusBaseWhere[] = 'EXISTS (SELECT 1 FROM vk_task_reviewers r WHERE r.task_id = t.id AND r.user_id = :reviewer_id)'; $statusBaseParams[':reviewer_id'] = $reviewerId; }
        if ($collaboratorId === -1) { $statusBaseWhere[] = 'NOT EXISTS (SELECT 1 FROM vk_task_collaborators c WHERE c.task_id = t.id)'; }
        elseif ($collaboratorId > 0) { $statusBaseWhere[] = 'EXISTS (SELECT 1 FROM vk_task_collaborators c WHERE c.task_id = t.id AND c.user_id = :collaborator_id)'; $statusBaseParams[':collaborator_id'] = $collaboratorId; }
        if ($sprintId === -1) { $statusBaseWhere[] = '(t.sprint_id IS NULL OR t.sprint_id = 0)'; }
        elseif ($sprintId > 0) { $statusBaseWhere[] = 't.sprint_id = :sprint_id'; $statusBaseParams[':sprint_id'] = $sprintId; }
        if ($search) {
            $statusBaseWhere[] = '(t.title LIKE :search OR t.section LIKE :search OR t.description LIKE :search OR t.id = :search_id)';
            $statusBaseParams[':search'] = '%' . $search . '%';
            $statusBaseParams[':search_id'] = ctype_digit($search) ? (int)$search : 0;
        }
        if ($taskQuarter) {
            $statusBaseWhere[] = 't.due_date IS NOT NULL AND t.due_date >= :quarter_start AND t.due_date <= :quarter_end';
            $statusBaseParams[':quarter_start'] = $taskQuarter['start'];
            $statusBaseParams[':quarter_end'] = $taskQuarter['end'];
        } elseif ($taskDateState === 'none') {
            $statusBaseWhere[] = 't.due_date IS NULL';
        } elseif ($taskDateState === 'overdue') {
            $statusBaseWhere[] = "t.due_date IS NOT NULL AND t.due_date < :today AND t.status != 'done'";
            $statusBaseParams[':today'] = date('Y-m-d');
        }
        $statusBaseSql = implode(' AND ', $statusBaseWhere);
        $statusStmt = $db->prepare(
            "SELECT t.status,
                    COUNT(*) AS n,
                    COALESCE(SUM(t.estimate_h), 0) AS estimate_h,
                    COALESCE(SUM(t.actual_h), 0) AS actual_h
             FROM vk_tasks t
             WHERE $statusBaseSql
             GROUP BY t.status"
        );
        $statusStmt->execute($statusBaseParams);
        $taskStatusSummary = [];
        foreach ($statusStmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $taskStatusSummary[$row['status']] = $row;
        }

        // Total count for pagination
        $countStmt = $db->prepare("SELECT COUNT(*) FROM vk_tasks t WHERE $whereSql");
        $countStmt->execute($params);
        $total     = (int)$countStmt->fetchColumn();
        $pagination = $this->paginationBounds($pageNum, $limit, $total);
        $pageNum = $pagination['page'];
        $totalPages = $pagination['total_pages'];
        $offset = $pagination['offset'];

        $sql = "SELECT t.*, s.name as sprint_name
                FROM vk_tasks t
                LEFT JOIN vk_sprints s ON s.id = t.sprint_id
                WHERE $whereSql
                ORDER BY {$sortSqlMap[$sort]}
                LIMIT $limit OFFSET $offset";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $tasks = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $userMap = $this->getUserMap(array_column($tasks, 'assignee_id'));
        foreach ($tasks as &$t) {
            $t['assignee_name'] = $userMap[(int)$t['assignee_id']] ?? null;
        }
        unset($t);
        $tasks   = $this->enrichTaskPagesBatch($tasks);
        $users   = $this->getAllUsers($assigneeId > 0 ? [$assigneeId] : []);
        $sprints = $this->getAllSprints();
        $currentAssigneeId = $assigneeId;
        $currentReviewerId = $reviewerId;
        $currentCollaboratorId = $collaboratorId;
        $currentSprintId = $sprintId;
        $currentTaskDateState = $taskDateState;
        ob_start(); require __DIR__ . '/views/tasks.php'; return ob_get_clean();
    }

    protected function viewTaskEdit(): string {
        $id     = (int)$this->wire('input')->get('id');
        $pageId = (int)$this->wire('input')->get('page_id');
        $task   = $id ? $this->getTask($id) : null;
        if ($id && !$task) {
            $this->redirectMissingRecord($this->_('Task does not exist.'), 'tasks', 'task-edit');
        }
        $dueDatePrefill = $task ? '' : $this->sanDate($this->wire('input')->get('due_date'));

        if ($task) {
            $db   = $this->wire('database');
            $stmt = $db->prepare(
                "SELECT c.* FROM vk_comments c
                 WHERE c.task_id = :tid ORDER BY c.created_at ASC"
            );
            $stmt->execute([':tid' => $id]);
            $task['comments'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $userMap = $this->getUserMap(array_column($task['comments'], 'user_id'));
            foreach ($task['comments'] as &$c) {
                $c['author_name'] = $userMap[(int)$c['user_id']] ?? '?';
            }
            unset($c);
            $pageId = $task['page_id'];

            $revStmt = $db->prepare("SELECT user_id FROM vk_task_reviewers WHERE task_id = :tid");
            $revStmt->execute([':tid' => $id]);
            $task['reviewer_ids'] = array_map('intval', $revStmt->fetchAll(\PDO::FETCH_COLUMN));
            $revMap = $this->getUserMap($task['reviewer_ids']);
            $task['reviewers'] = array_map(
                fn(int $rid): array => ['id' => $rid, 'name' => $revMap[$rid] ?? ('#' . $rid)],
                $task['reviewer_ids']
            );

            $colStmt = $db->prepare("SELECT user_id FROM vk_task_collaborators WHERE task_id = :tid");
            $colStmt->execute([':tid' => $id]);
            $task['collaborator_ids'] = array_map('intval', $colStmt->fetchAll(\PDO::FETCH_COLUMN));
            $colMap = $this->getUserMap($task['collaborator_ids']);
            $task['collaborators'] = array_map(
                fn(int $cid): array => ['id' => $cid, 'name' => $colMap[$cid] ?? ('#' . $cid)],
                $task['collaborator_ids']
            );

            // Time logs
            $tlStmt = $db->prepare(
                "SELECT tl.* FROM vk_time_logs tl
                 WHERE tl.task_id = :tid ORDER BY tl.logged_date DESC, tl.created_at DESC"
            );
            $tlStmt->execute([':tid' => $id]);
            $task['time_logs'] = $tlStmt->fetchAll(\PDO::FETCH_ASSOC);
            $userMap += $this->getUserMap(array_column($task['time_logs'], 'user_id'));
            foreach ($task['time_logs'] as &$tl) {
                $tl['user_name'] = $userMap[(int)$tl['user_id']] ?? '?';
            }
            unset($tl);
            // Sum actual from time logs
            $task['actual_h_logged'] = array_sum(array_column($task['time_logs'], 'hours'));
        }

        // Resolve linked PW page
        $linkedPage = null;
        if ($pageId) {
            $lp = $this->wire('pages')->get($pageId);
            if ($lp->id) $linkedPage = $lp;
        }

        $includeUserIds = [];
        if (!empty($task['assignee_id'])) $includeUserIds[] = (int) $task['assignee_id'];
        if (!empty($task['reviewer_ids'])) $includeUserIds = array_merge($includeUserIds, $task['reviewer_ids']);
        if (!empty($task['collaborator_ids'])) $includeUserIds = array_merge($includeUserIds, $task['collaborator_ids']);
        $users = $this->getAllUsers($includeUserIds);
        $sections = $this->wire('database')
            ->query("SELECT DISTINCT section FROM vk_tasks WHERE section != '' ORDER BY section")
            ->fetchAll(\PDO::FETCH_COLUMN);
        ob_start(); require __DIR__ . '/views/task-form.php'; return ob_get_clean();
    }

    protected function viewCalendar(): string {
        $input     = $this->wire('input');
        $calViewRaw = $input->get('cal_view', 'string');
        $calView   = in_array($calViewRaw, ['week', 'quarter'], true) ? $calViewRaw : 'month';
        $month     = max(1, min(12, (int)($input->get('month') ?: date('n'))));
        $year      = max(2000, min(2100, (int)($input->get('year') ?: date('Y'))));
        $weekDate  = $this->sanDate($input->get('date')) ?: date('Y-m-d');
        $calendarAssigneeId = max(0, (int)$input->get('assignee_id'));
        $quarterInput = (int)$input->get('quarter');
        $quarterContext = $quarterInput
            ? $this->quarterContext($quarterInput, $year)
            : $this->quarterContextForDate(sprintf('%04d-%02d-01', $year, $month));
        $quarter = $quarterContext['quarter'];
        $quarterYear = (int)$quarterContext['year'];

        if ($calView === 'week') {
            $dt = new \DateTime($weekDate);
            $dt->modify('monday this week');
            $weekStartDate = $dt->format('Y-m-d');
            $weekEndDate = (clone $dt)->modify('+6 days')->format('Y-m-d');
            $month = (int)$dt->format('n');
            $year = (int)$dt->format('Y');
            $quarterContext = $this->quarterContextForDate($weekStartDate);
            $quarter = (int)$quarterContext['quarter'];
            $quarterYear = (int)$quarterContext['year'];
            $items = $this->getCalendarItemsRange($weekStartDate, $weekEndDate);
            $deadlines = $this->getTaskDeadlinesRange($weekStartDate, $weekEndDate, $calendarAssigneeId);
            $quarterStartDate = '';
            $quarterEndDate = '';
            $quarterMonths = [];
        } elseif ($calView === 'quarter') {
            $quarterStartMonth = $quarterContext['start_month'];
            $month = $quarterStartMonth;
            $year = $quarterYear;
            $quarterStartDate = $quarterContext['start'];
            $quarterEndDate = $quarterContext['end'];
            $items = $this->getCalendarItemsRange($quarterStartDate, $quarterEndDate, 300);
            $deadlines = $this->getTaskDeadlinesRange($quarterStartDate, $quarterEndDate, $calendarAssigneeId);
            $quarterMonths = $quarterContext['months'];
            $weekStartDate = '';
            $weekEndDate = '';
        } else {
            $items = $this->getCalendarItems($month, $year);
            $deadlines = $this->getTaskDeadlines($month, $year, $calendarAssigneeId);
            $quarterMonths = [];
            $weekStartDate = '';
            $weekEndDate = '';
            $quarterStartDate = '';
            $quarterEndDate = '';
        }
        $today = date('Y-m-d');
        $next7 = date('Y-m-d', strtotime('+7 days'));
        $calendarPlanStats = [
            'overdue' => 0,
            'next7' => 0,
            'no_due' => 0,
            'quarter_open' => 0,
        ];
        $calendarStatsWhere = $calendarAssigneeId > 0 ? ' WHERE assignee_id = :assignee_id' : '';
        $stmt = $this->wire('database')->prepare(
            "SELECT
                SUM(CASE WHEN status != 'done' AND due_date IS NOT NULL AND due_date < :today THEN 1 ELSE 0 END) AS overdue,
                SUM(CASE WHEN status != 'done' AND due_date IS NOT NULL AND due_date >= :today AND due_date <= :next7 THEN 1 ELSE 0 END) AS next7,
                SUM(CASE WHEN status != 'done' AND due_date IS NULL THEN 1 ELSE 0 END) AS no_due,
                SUM(CASE WHEN status != 'done' AND due_date IS NOT NULL AND due_date >= :quarter_start AND due_date <= :quarter_end THEN 1 ELSE 0 END) AS quarter_open
             FROM vk_tasks$calendarStatsWhere"
        );
        $calendarStatsParams = [
            ':today' => $today,
            ':next7' => $next7,
            ':quarter_start' => $quarterContext['start'],
            ':quarter_end' => $quarterContext['end'],
        ];
        if ($calendarAssigneeId > 0) $calendarStatsParams[':assignee_id'] = $calendarAssigneeId;
        $stmt->execute($calendarStatsParams);
        $calendarPlanStats = array_merge($calendarPlanStats, array_map('intval', $stmt->fetch(\PDO::FETCH_ASSOC) ?: []));
        $calendarUsers = $this->getAllUsers($calendarAssigneeId > 0 ? [$calendarAssigneeId] : []);
        ob_start(); require __DIR__ . '/views/calendar.php'; return ob_get_clean();
    }

    protected function viewAudit(): string {
        $rules   = $this->getAuditRules();
        $results = [];
        $input   = $this->wire('input');
        $ruleGet = $input->get('rule', 'string'); // null if not in URL
        $runRule = ($ruleGet !== null && $ruleGet !== '') ? (int)$ruleGet : ($rules ? 0 : false);
        // Clamp to valid index; ignore out-of-range values
        if ($runRule !== false && !isset($rules[$runRule])) $runRule = $rules ? 0 : false;

        if ($runRule !== false && isset($rules[$runRule])) {
            $results = $this->runAuditRule($rules[$runRule]);
        }

        $auditSummary = $this->getAuditSummary();
        ob_start(); require __DIR__ . '/views/audit.php'; return ob_get_clean();
    }

    protected function viewKB(): string {
        $db     = $this->wire('database');
        $input  = $this->wire('input');
        $cat    = $input->get('cat', 'string') ?: '';
        $kbQuery = trim(substr($input->get('q', 'string') ?: '', 0, 120));

        $whereParts = [];
        $params = [];
        if ($cat) {
            $whereParts[] = "category = :cat";
            $params[':cat'] = $cat;
        }
        if ($kbQuery !== '') {
            $whereParts[] = "(title LIKE :q OR body LIKE :q OR category LIKE :q)";
            $params[':q'] = '%' . $kbQuery . '%';
        }
        $where = $whereParts ? 'WHERE ' . implode(' AND ', $whereParts) : '';

        $stmt = $db->prepare("SELECT * FROM vk_notes $where ORDER BY category, updated_at DESC, title ASC");
        $stmt->execute($params);
        $notes = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $kbStats = [
            'total' => count($notes),
            'categories' => 0,
            'updated_at' => '',
        ];
        $kbCategories = [];
        foreach ($notes as $note) {
            $categoryKey = trim((string)$note['category']) ?: $this->_('Uncategorized');
            $kbCategories[$categoryKey] = true;
            if (!empty($note['updated_at']) && (!$kbStats['updated_at'] || $note['updated_at'] > $kbStats['updated_at'])) {
                $kbStats['updated_at'] = $note['updated_at'];
            }
        }
        $kbStats['categories'] = count($kbCategories);

        $stmt = $db->query("SELECT DISTINCT category FROM vk_notes WHERE category != '' ORDER BY category");
        $categories = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        ob_start(); require __DIR__ . '/views/kb.php'; return ob_get_clean();
    }

    protected function viewNoteEdit(): string {
        $id   = (int)$this->wire('input')->get('id');
        $note = $id ? $this->getNote($id) : null;
        if ($id && !$note) {
            $this->redirectMissingRecord($this->_('Note does not exist.'), 'kb', 'note-edit');
        }
        $db   = $this->wire('database');
        $stmt = $db->query("SELECT DISTINCT category FROM vk_notes WHERE category != '' ORDER BY category");
        $categories = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        ob_start(); require __DIR__ . '/views/note-form.php'; return ob_get_clean();
    }

    protected function viewSettings(): string {
        $config = $this->getConfig();
        $templates = $this->wire('templates');
        $fields    = $this->wire('fields');
        $db        = $this->wire('database');
        $settingsStats = [
            'open_tasks' => 0,
            'active_sprints' => 0,
            'notes' => 0,
            'audit_rules' => count($this->getAuditRules()),
        ];
        try {
            $settingsStats['open_tasks'] = (int)$db->query("SELECT COUNT(*) FROM vk_tasks WHERE status != 'done'")->fetchColumn();
            $settingsStats['active_sprints'] = (int)$db->query("SELECT COUNT(*) FROM vk_sprints WHERE status = 'active'")->fetchColumn();
            $settingsStats['notes'] = (int)$db->query("SELECT COUNT(*) FROM vk_notes")->fetchColumn();
        } catch (\Throwable $e) {
            // Keep settings available even if optional module tables are not ready yet.
        }
        ob_start(); require __DIR__ . '/views/settings.php'; return ob_get_clean();
    }

    // -------------------------------------------------------------------------
    // POST Actions
    // -------------------------------------------------------------------------

    protected function actionSaveTask(): string {
        $this->requireCSRF();
        $input = $this->wire('input');
        $db    = $this->wire('database');
        $user  = $this->wire('user');
        $id    = (int)$input->post('id');
        $returnUrl = $this->safeLocalUrl((string)$input->post('return_url'));
        $pageId     = (int)$input->post('page_id') ?: null;
        $assigneeId = (int)$input->post('assignee_id') ?: null;
        $sprintId   = (int)$input->post('sprint_id') ?: null;
        $dueDate    = $this->sanDate($input->post('due_date'));
        $editUrl = $this->page->url . '?view=task-edit' . ($id ? '&id=' . $id : '');
        if (!$id) {
            if ($pageId) $editUrl .= '&page_id=' . (int)$pageId;
            if ($sprintId) $editUrl .= '&sprint_id=' . (int)$sprintId;
            if ($dueDate) $editUrl .= '&due_date=' . rawurlencode($dueDate);
        }
        if ($returnUrl) $editUrl .= '&return_url=' . rawurlencode($returnUrl);
        $this->requireOwnerForExisting('vk_tasks', $id);

        // Snapshot current membership before writes, to detect newly-added users.
        $notifyBefore = ['assignee' => 0, 'reviewer' => [], 'collaborator' => []];
        $statusBefore = '';  // stays empty for new tasks, which never notify
        if ($id) {
            $bStmt = $db->prepare("SELECT assignee_id, status FROM vk_tasks WHERE id = :id");
            $bStmt->execute([':id' => $id]);
            $bRow = $bStmt->fetch(\PDO::FETCH_ASSOC) ?: [];
            $notifyBefore['assignee'] = (int) ($bRow['assignee_id'] ?? 0);
            $statusBefore = (string) ($bRow['status'] ?? '');
            $rPrev = $db->prepare("SELECT user_id FROM vk_task_reviewers WHERE task_id = :tid");
            $rPrev->execute([':tid' => $id]);
            $notifyBefore['reviewer'] = array_map('intval', $rPrev->fetchAll(\PDO::FETCH_COLUMN));
            $cPrev = $db->prepare("SELECT user_id FROM vk_task_collaborators WHERE task_id = :tid");
            $cPrev->execute([':tid' => $id]);
            $notifyBefore['collaborator'] = array_map('intval', $cPrev->fetchAll(\PDO::FETCH_COLUMN));
        }

        $title = substr($this->san($input->post('title')), 0, 255);
        if (!$title) {
            $this->error($this->_('Title is required.'));
            $this->wire('session')->redirect($editUrl);
        }

        if ($pageId && !$this->fwPageExists($pageId)) {
            $this->error($this->_('Linked page does not exist.'));
            $this->wire('session')->redirect($editUrl);
        }
        if ($assigneeId && !$this->fwUserExists($assigneeId)) {
            $this->error($this->_('Assignee does not exist.'));
            $this->wire('session')->redirect($editUrl);
        }
        if ($sprintId && !$this->fwSprintExists($sprintId)) {
            $this->error($this->_('Sprint does not exist.'));
            $this->wire('session')->redirect($editUrl);
        }

        $estimate = $this->sanAllowedNum($input->post('estimate_h'), [0.25, 0.5, 1, 2, 4, 6, 8, 12, 16, 24, 32, 40]);
        $sp       = $this->sanAllowedInt($input->post('story_points'), [1, 2, 3, 5, 8, 13, 21]);

        // actual_h: if task already has time logs, recalculate from DB to prevent POST tampering
        if ($id) {
            $tlSum = $db->prepare("SELECT COALESCE(SUM(hours), 0) FROM vk_time_logs WHERE task_id = :id");
            $tlSum->execute([':id' => $id]);
            $tlTotal = (float)$tlSum->fetchColumn();
            $actualH = $tlTotal > 0 ? round($tlTotal, 1) : null;
            // If no time logs exist, accept manual POST value
            if ($actualH === null) {
                $actualH = $this->sanNonNegativeDecimal($input->post('actual_h', 'string'));
            }
        } else {
            $actualH = $this->sanNonNegativeDecimal($input->post('actual_h', 'string'));
        }

        $data = [
            ':title'        => $title,
            ':description'  => $this->sanRichText($input->post('description', 'string')),
            ':status'       => $this->sanEnum($input->post('status'), ['open','in_progress','review','done']),
            ':priority'     => $this->sanEnum($input->post('priority'), ['low','medium','high','critical']),
            ':due_date'     => $dueDate,
            ':page_id'      => $pageId,
            ':assignee_id'  => $assigneeId,
            ':section'      => $this->sectionValue($input->post('section'), $input->post('new_section')),
            ':sprint_id'    => $sprintId,
            ':estimate_h'   => $estimate,
            ':actual_h'     => $actualH,
            ':story_points' => $sp,
        ];

        if ($id) {
            $stmt = $db->prepare(
                "UPDATE vk_tasks SET title=:title, description=:description, status=:status,
                 priority=:priority, due_date=:due_date, page_id=:page_id,
                 assignee_id=:assignee_id, section=:section,
                 sprint_id=:sprint_id, estimate_h=:estimate_h, actual_h=:actual_h,
                 story_points=:story_points WHERE id=:id"
            );
            $data[':id'] = $id;
            $stmt->execute($data);
            $this->message($this->_('Task updated.'));
        } else {
            $stmt = $db->prepare(
                "INSERT INTO vk_tasks (title, description, status, priority, due_date, page_id, assignee_id, section, sprint_id, estimate_h, actual_h, story_points, created_by, created_at)
                 VALUES (:title, :description, :status, :priority, :due_date, :page_id, :assignee_id, :section, :sprint_id, :estimate_h, :actual_h, :story_points, :uid, NOW())"
            );
            $data[':uid'] = $user->id;
            $stmt->execute($data);
            $id = (int)$db->lastInsertId();
            $this->message($this->_('Task created.'));
        }

        // Sync reviewers (many-to-many); keep only ids in the assignable pool.
        $allowedIds = array_map('intval', array_column($this->getAllUsers(), 'id'));
        $reviewerIds = [];
        foreach ((array) $input->post('reviewer_ids') as $rid) {
            $rid = (int) $rid;
            if ($rid > 0 && in_array($rid, $allowedIds, true)) $reviewerIds[$rid] = $rid;
        }
        $db->prepare("DELETE FROM vk_task_reviewers WHERE task_id = :tid")->execute([':tid' => $id]);
        if ($reviewerIds) {
            $ins = $db->prepare("INSERT INTO vk_task_reviewers (task_id, user_id) VALUES (:tid, :uid)");
            foreach ($reviewerIds as $rid) $ins->execute([':tid' => $id, ':uid' => $rid]);
        }

        // Sync collaborators (many-to-many); keep only ids in the assignable pool.
        $collaboratorIds = [];
        foreach ((array) $input->post('collaborator_ids') as $cid) {
            $cid = (int) $cid;
            if ($cid > 0 && in_array($cid, $allowedIds, true)) $collaboratorIds[$cid] = $cid;
        }
        $db->prepare("DELETE FROM vk_task_collaborators WHERE task_id = :tid")->execute([':tid' => $id]);
        if ($collaboratorIds) {
            $insC = $db->prepare("INSERT INTO vk_task_collaborators (task_id, user_id) VALUES (:tid, :uid)");
            foreach ($collaboratorIds as $cid) $insC->execute([':tid' => $id, ':uid' => $cid]);
        }

        // Notify users newly added to a role on this task.
        $notifyAfter = [
            'assignee'     => (int) ($assigneeId ?? 0),
            'reviewer'     => array_values($reviewerIds),
            'collaborator' => array_values($collaboratorIds),
        ];
        $this->notify->membershipChanged($id, $title, $notifyBefore, $notifyAfter, (int) $user->id);

        // Tell everyone on the task when its status moved. Skipped for new tasks
        // ($statusBefore is empty), and recipients are read after the role sync
        // above so members added in this same save are included.
        if ($statusBefore !== '' && $statusBefore !== $data[':status']) {
            $this->notify->statusChanged(
                $id, $title, $statusBefore, (string) $data[':status'],
                $this->taskNotifyRecipients($id), (int) $user->id
            );
        }

        if ($returnUrl) $this->wire('session')->redirect($returnUrl);
        $this->redirect('task-edit', $id);
    }

    protected function actionDeleteTask(): string {
        $this->requireCSRF();
        $input = $this->wire('input');
        $id = (int)$input->post('id');
        $returnUrl = $this->safeLocalUrl((string)$input->post('return_url'));
        if (!$id) { $this->redirect('tasks'); }
        $this->requireOwner('vk_tasks', $id);
        $db = $this->wire('database');
        $db->prepare("DELETE FROM vk_time_logs WHERE task_id=:id")->execute([':id'=>$id]);
        $db->prepare("DELETE FROM vk_comments WHERE task_id=:id")->execute([':id'=>$id]);
        $db->prepare("DELETE FROM vk_tasks WHERE id=:id")->execute([':id'=>$id]);
        $this->files->deleteForEntity('task', $id);
        // Comment attachments (Phase 3) are keyed by comment id, not task id;
        // their cleanup will be handled when comment attachments are added.
        $this->message($this->_('Task deleted.'));
        if ($returnUrl) $this->wire('session')->redirect($returnUrl);
        $this->redirect('tasks');
    }

    protected function actionSaveNote(): string {
        $this->requireCSRF();
        $input = $this->wire('input');
        $db    = $this->wire('database');
        $user  = $this->wire('user');
        $id    = (int)$input->post('id');
        $returnUrl = $this->safeLocalUrl((string)$input->post('return_url'));
        $editUrl = $this->page->url . '?view=note-edit' . ($id ? '&id=' . $id : '');
        if ($returnUrl) $editUrl .= '&return_url=' . rawurlencode($returnUrl);
        $this->requireOwnerForExisting('vk_notes', $id);

        $title = substr($this->san($input->post('title')), 0, 255);
        if (!$title) {
            $this->error($this->_('Title required.'));
            $this->wire('session')->redirect($editUrl);
        }

        $data = [
            ':title'    => $title,
            ':body'     => $this->sanRichText($input->post('body', 'string')),
            ':category' => $this->noteCategoryValue($input->post('category'), $input->post('new_category')),
        ];

        if ($id) {
            $stmt = $db->prepare("UPDATE vk_notes SET title=:title, body=:body, category=:category, updated_at=NOW() WHERE id=:id");
            $data[':id'] = $id;
            $stmt->execute($data);
            $this->message($this->_('Note updated.'));
        } else {
            $stmt = $db->prepare("INSERT INTO vk_notes (title, body, category, created_by, created_at, updated_at) VALUES (:title, :body, :category, :uid, NOW(), NOW())");
            $data[':uid'] = $user->id;
            $stmt->execute($data);
            $id = (int)$db->lastInsertId();
            $this->message($this->_('Note saved.'));
        }

        if ($returnUrl) $this->wire('session')->redirect($returnUrl);
        $this->redirect('note-edit', $id);
    }

    protected function actionDeleteNote(): string {
        $this->requireCSRF();
        $input = $this->wire('input');
        $id = (int)$input->post('id');
        $returnUrl = $this->safeLocalUrl((string)$input->post('return_url'));
        if (!$id) { $this->redirect('kb'); }
        $this->requireOwner('vk_notes', $id);
        $this->wire('database')->prepare("DELETE FROM vk_notes WHERE id=:id")->execute([':id'=>$id]);
        $this->files->deleteForEntity('note', $id);
        $this->message($this->_('Note deleted.'));
        if ($returnUrl) $this->wire('session')->redirect($returnUrl);
        $this->redirect('kb');
    }

    protected function actionSaveComment(): string {
        $this->requireCSRF();
        $input  = $this->wire('input');
        $db     = $this->wire('database');
        $user   = $this->wire('user');
        $taskId = (int)$input->post('task_id');
        $text   = $this->sanRichText($input->post('text', 'string'));
        $back = $this->safeLocalUrl($input->post('back', 'string') ?: '') ?: $this->page->url . '?view=task-edit&id=' . $taskId;

        if ($text && $taskId && $this->fwTaskExists($taskId)) {
            $stmt = $db->prepare("INSERT INTO vk_comments (task_id, user_id, text, created_at) VALUES (:tid, :uid, :text, NOW())");
            $stmt->execute([':tid' => $taskId, ':uid' => $user->id, ':text' => $text]);
        } elseif ($text && $taskId) {
            $this->error($this->_('Task does not exist.'));
        }
        $this->wire('session')->redirect($back);
        return '';
    }

    protected function actionDeleteComment(): string {
        $this->requireCSRF();
        $id   = (int)$this->wire('input')->post('id');
        $back = $this->safeLocalUrl($this->wire('input')->post('back', 'string') ?: '') ?: $this->page->url;
        $user = $this->wire('user');
        $db   = $this->wire('database');
        $stmt = $db->prepare("SELECT user_id FROM vk_comments WHERE id=:id");
        $stmt->execute([':id'=>$id]);
        $c = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($c && ($user->isSuperuser() || $c['user_id'] == $user->id)) {
            $db->prepare("DELETE FROM vk_comments WHERE id=:id")->execute([':id'=>$id]);
        }
        $this->wire('session')->redirect($back);
        return '';
    }

    protected function actionReviewDecision(): string {
        $this->requireCSRF();
        $input  = $this->wire('input');
        $db     = $this->wire('database');
        $user   = $this->wire('user');
        $taskId = (int) $input->post('task_id');
        $decision = (string) $input->post('decision', 'string');
        $text   = $this->sanRichText($input->post('text', 'string'));
        $back = $this->safeLocalUrl($input->post('back', 'string') ?: '') ?: $this->page->url . '?view=task-edit&id=' . $taskId;

        if (!in_array($decision, ['approved', 'changes_requested'], true)) {
            $this->wire('session')->redirect($back);
            return '';
        }
        $task = $taskId ? $this->getTask($taskId) : null;
        if (!$task) {
            $this->error($this->_('Task does not exist.'));
            $this->wire('session')->redirect($back);
            return '';
        }
        if (!$this->canDecideReview($taskId, $task)) {
            $this->error($this->_('You do not have permission to review this task.'));
            $this->wire('session')->redirect($back);
            return '';
        }

        $external = (new VerkExternalApprovals($this))->forTask($taskId);
        if($external && $external['provider'] === 'mailbox') {
            if(!$user->hasPermission('mailbox-confirm-links') || !$this->wire('modules')->isInstalled('Mailbox')) {
                $this->error($this->_('Mailbox confirmation permission and the Mailbox module are required.'));
                $this->wire('session')->redirect($back);
                return '';
            }
            try {
                $mailboxApi = $this->wire('modules')->get('Mailbox')->api($user);
                if($decision === 'approved') $mailboxApi->approveConfirmation($external['external_id']);
                else $mailboxApi->rejectConfirmation($external['external_id']);
            } catch(\Throwable $error) {
                $wanted = $decision === 'approved' ? 'approved' : 'rejected';
                $alreadyApplied = false;
                try {
                    foreach($mailboxApi->proposals() as $proposal) {
                        if(($proposal['id'] ?? '') === $external['external_id'] && ($proposal['status'] ?? '') === $wanted) $alreadyApplied = true;
                    }
                } catch(\Throwable $ignored) {}
                if(!$alreadyApplied) {
                    $this->error($this->_('Mailbox decision failed; the Verk task was not changed.'));
                    $this->wire('session')->redirect($back);
                    return '';
                }
            }
        }

        $db->prepare("INSERT INTO vk_comments (task_id, user_id, text, kind, created_at) VALUES (:tid, :uid, :text, :kind, NOW())")
           ->execute([':tid' => $taskId, ':uid' => $user->id, ':text' => $text, ':kind' => $decision]);

        if ($task['status'] === 'review') {
            $newStatus = $decision === 'approved' ? 'done' : 'in_progress';
            $db->prepare("UPDATE vk_tasks SET status = :s WHERE id = :id")->execute([':s' => $newStatus, ':id' => $taskId]);
            $this->notify->statusChanged(
                $taskId, (string) $task['title'], (string) $task['status'], $newStatus,
                $this->taskNotifyRecipients($taskId), (int) $user->id
            );
            $this->message($decision === 'approved'
                ? $this->_('Review approved; task marked done.')
                : $this->_('Changes requested; task moved to In Progress.'));
        } else {
            $this->message($this->_('Review decision recorded.'));
        }
        $this->wire('session')->redirect($back);
        return '';
    }

    /** AJAX: inline status change from list views (e.g. dashboard "My Tasks"). */
    protected function actionUpdateTaskStatus(): string {
        $this->requireAjaxCSRF();
        $input  = $this->wire('input');
        $db     = $this->wire('database');
        $taskId = (int) $input->post('task_id');
        $status = $this->sanEnum($input->post('status'), ['open','in_progress','review','done']);

        if (!$taskId) {
            $this->jsonResponse(['ok' => false, 'message' => $this->_('Task does not exist.')], 404);
        }

        $row = $this->getTaskRoleRow($taskId);
        if (!$row) {
            $this->jsonResponse(['ok' => false, 'message' => $this->_('Task does not exist.')], 404);
        }
        if (!$this->canChangeTaskStatus($row)) {
            $this->jsonResponse(['ok' => false, 'message' => $this->_('You do not have permission to change this task.')], 403);
        }

        $db->prepare("UPDATE vk_tasks SET status = :s WHERE id = :id")
           ->execute([':s' => $status, ':id' => $taskId]);

        $this->notify->statusChanged(
            $taskId, (string) $row['title'], (string) $row['status'], $status,
            $this->taskNotifyRecipients($taskId), (int) $this->wire('user')->id
        );

        $this->jsonResponse([
            'ok'           => true,
            'status'       => $status,
            'status_label' => $this->statusLabel($status),
        ]);
    }

    protected function actionSaveSettings(): string {
        $this->requireCSRF();
        $input = $this->wire('input');
        $current = $this->getConfig();
        $has = fn(string $key): bool => $input->post($key) !== null;
        $this->wire('modules')->saveConfig($this, [
            'calendar_template'   => $has('calendar_template') ? $this->san($input->post('calendar_template')) : $current['calendar_template'],
            'calendar_date_field' => $has('calendar_date_field') ? $this->san($input->post('calendar_date_field')) : $current['calendar_date_field'],
            'quarter_start_month' => $has('quarter_start_month') ? max(1, min(12, (int)$input->post('quarter_start_month'))) : (int)$current['quarter_start_month'],
            'page_widget_enabled' => $has('page_widget_enabled') ? (int)(bool)$input->post('page_widget_enabled') : (int)$current['page_widget_enabled'],
            'page_widget_position' => $has('page_widget_position') ? ($input->post('page_widget_position') === 'bottom' ? 'bottom' : 'top') : $current['page_widget_position'],
            'page_widget_collapsed' => $has('page_widget_collapsed') ? (int)(bool)$input->post('page_widget_collapsed') : (int)$current['page_widget_collapsed'],
            'page_widget_limit' => $has('page_widget_limit') ? max(1, min(50, (int)$input->post('page_widget_limit'))) : (int)$current['page_widget_limit'],
            'page_widget_sort' => $has('page_widget_sort') ? (in_array($input->post('page_widget_sort'), ['status', 'newest', 'due', 'priority'], true) ? $input->post('page_widget_sort') : 'status') : $current['page_widget_sort'],
            'page_widget_show_done' => $has('page_widget_show_done') ? (int)(bool)$input->post('page_widget_show_done') : (int)$current['page_widget_show_done'],
            'page_widget_show_create' => $has('page_widget_show_create') ? (int)(bool)$input->post('page_widget_show_create') : (int)$current['page_widget_show_create'],
            'page_widget_show_empty' => $has('page_widget_show_empty') ? (int)(bool)$input->post('page_widget_show_empty') : (int)$current['page_widget_show_empty'],
            'page_widget_show_status' => $has('page_widget_show_status') ? (int)(bool)$input->post('page_widget_show_status') : (int)$current['page_widget_show_status'],
            'page_widget_show_priority' => $has('page_widget_show_priority') ? (int)(bool)$input->post('page_widget_show_priority') : (int)$current['page_widget_show_priority'],
            'page_widget_show_due_date' => $has('page_widget_show_due_date') ? (int)(bool)$input->post('page_widget_show_due_date') : (int)$current['page_widget_show_due_date'],
            'page_widget_show_quarter' => $has('page_widget_show_quarter') ? (int)(bool)$input->post('page_widget_show_quarter') : (int)$current['page_widget_show_quarter'],
            'page_widget_show_assignee' => $has('page_widget_show_assignee') ? (int)(bool)$input->post('page_widget_show_assignee') : (int)$current['page_widget_show_assignee'],
            'assignee_roles' => $has('assignee_roles') ? $this->sanRoleList((string)$input->post('assignee_roles')) : (string)($current['assignee_roles'] ?? ''),
            'notify_enabled' => $has('notify_enabled') ? (int)(bool)$input->post('notify_enabled') : (int)$current['notify_enabled'],
            'notify_assignee' => $has('notify_assignee') ? (int)(bool)$input->post('notify_assignee') : (int)$current['notify_assignee'],
            'notify_collaborator' => $has('notify_collaborator') ? (int)(bool)$input->post('notify_collaborator') : (int)$current['notify_collaborator'],
            'notify_reviewer' => $has('notify_reviewer') ? (int)(bool)$input->post('notify_reviewer') : (int)$current['notify_reviewer'],
            'notify_status' => $has('notify_status') ? (int)(bool)$input->post('notify_status') : (int)$current['notify_status'],
            'status_edit_reviewer' => $has('status_edit_reviewer') ? (int)(bool)$input->post('status_edit_reviewer') : (int)$current['status_edit_reviewer'],
            'status_edit_collaborator' => $has('status_edit_collaborator') ? (int)(bool)$input->post('status_edit_collaborator') : (int)$current['status_edit_collaborator'],
            // saveConfig() with an array replaces the whole config blob, so carry
            // over keys this form doesn't manage (otherwise they're wiped).
            'audit_rules' => (string)($current['audit_rules'] ?? ''),
        ]);
        $this->message($this->_('Settings saved.'));
        $this->redirect('settings');
    }

    protected function actionSaveAuditRules(): string {
        $this->requireCSRF();
        $raw   = $this->wire('input')->post('audit_rules', 'string');
        $rules = [];
        foreach (explode("\n", $raw) as $line) {
            $line = trim($line);
            if (!$line || str_starts_with($line, '#')) continue;
            // format: Label | scope selector | field.path | message | users
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) >= 4) {
                $rules[] = $this->normalizeAuditRule([
                    'label'    => $parts[0],
                    'selector' => $parts[1],
                    'field'    => $parts[2],
                    'message'  => $parts[3] ?: $this->_('Field is empty'),
                    'users'    => $parts[4] ?? '',
                ]);
            } elseif (count($parts) >= 2) {
                // Previous versions stored an empty-field selector in column two.
                $rules[] = $this->normalizeAuditRule([
                    'label'    => $parts[0],
                    'selector' => $parts[1],
                    'message'  => $parts[2] ?? $this->_('Field is empty'),
                ]);
            }
        }
        // saveConfig() with an array replaces the whole config blob, so retrieve
        // the current config and update only audit_rules (otherwise every other
        // setting is wiped).
        $cfg = $this->wire('modules')->getConfig($this);
        $cfg['audit_rules'] = json_encode($rules);
        $this->wire('modules')->saveConfig($this, $cfg);
        $this->message($this->_('Audit rules saved.'));
        $this->redirect('audit');
    }
}

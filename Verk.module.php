<?php namespace ProcessWire;

require_once __DIR__ . '/VerkExportService.php';

/**
 * Verk
 *
 * Site operations layer for ProcessWire.
 * Tasks, sprints, quarter planning, editorial calendar, content audit, and knowledge base.
 *
 * @author  Maxim Semenov <maxim@smnv.org> (smnv.org)
 * @license MIT
 * @version 133
 */
class Verk extends Process implements Module, ConfigurableModule {

    private VerkExportService $export;

    public static function getModuleInfo(): array {
        return [
            'title'    => 'Verk',
            'version'  => 133,
            'summary'  => 'Site ops layer for ProcessWire: tasks, sprints, quarter planning, editorial calendar, content audit, and knowledge base.',
            'author'   => 'Maxim Semenov',
            'href'     => 'https://smnv.org',
            'icon'     => 'dashboard',
            'singular' => true,
            'autoload' => true,
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
        require_once __DIR__ . '/VerkDB.php';
        VerkDB::install($this->wire('database'));
        VerkDB::migrate($this->wire('database'));
    }

    public function ___uninstall(): void {
        parent::___uninstall();
        require_once __DIR__ . '/VerkDB.php';
        VerkDB::uninstall($this->wire('database'));
    }

    public function ___upgrade($fromVersion, $toVersion): void {
        require_once __DIR__ . '/VerkDB.php';
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
        // Inject task widget into page editor
        $this->addHookAfter('ProcessPageEdit::buildForm', $this, 'hookPageEditWidget');
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
            $action = $input->post('action', 'string');
            return match($action) {
                'save_task'      => $this->actionSaveTask(),
                'delete_task'    => $this->actionDeleteTask(),
                'save_note'      => $this->actionSaveNote(),
                'delete_note'    => $this->actionDeleteNote(),
                'save_comment'   => $this->actionSaveComment(),
                'delete_comment' => $this->actionDeleteComment(),
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
        if (!in_array($view, ['ajax-search', 'ajax-sprint-tasks', 'export-docx'], true)) {
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

        // Recent tasks (all) — paginated
        $recentPage    = max(1, (int)$input->get('recent_page', 'int') ?: 1);
        $recentLimit   = 4;
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
        $auditSummary = $this->getAuditSummary();

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
        $validStatus = ['open', 'in_progress', 'review', 'done'];
        $validPrio   = ['low', 'medium', 'high', 'critical'];
        $status   = in_array($input->get('status', 'string'), $validStatus, true) ? $input->get('status', 'string') : '';
        $prio     = in_array($input->get('priority', 'string'), $validPrio, true)  ? $input->get('priority', 'string') : '';
        $assigneeId = (int)$input->get('assignee_id');
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
        $taskDateState = $input->get('date_state', 'string') === 'none' ? 'none' : '';
        $taskQuarterYear = (int)$input->get('year');
        if (!$taskQuarterYear) $taskQuarterYear = (int)$this->quarterContextForDate(date('Y-m-d'))['year'];
        $taskQuarter = $quarter ? $this->quarterContext($quarter, $taskQuarterYear) : null;

        $pageNum = max(1, (int)$input->get('page', 'int') ?: 1);
        $limit   = min(100, max(10, (int)$input->get('limit', 'int') ?: 10));

        $where  = ['1=1'];
        $params = [];
        if ($status)   { $where[] = 't.status = :status';       $params[':status'] = $status; }
        if ($prio)     { $where[] = 't.priority = :prio';       $params[':prio']   = $prio; }
        if ($assigneeId === -1) { $where[] = '(t.assignee_id IS NULL OR t.assignee_id = 0)'; }
        elseif ($assigneeId > 0) { $where[] = 't.assignee_id = :assignee_id'; $params[':assignee_id'] = $assigneeId; }
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
        }

        $whereSql = implode(' AND ', $where);

        $quarterBaseWhere = ['1=1'];
        $quarterBaseParams = [];
        if ($status)   { $quarterBaseWhere[] = 't.status = :status';       $quarterBaseParams[':status'] = $status; }
        if ($prio)     { $quarterBaseWhere[] = 't.priority = :prio';       $quarterBaseParams[':prio'] = $prio; }
        if ($assigneeId === -1) { $quarterBaseWhere[] = '(t.assignee_id IS NULL OR t.assignee_id = 0)'; }
        elseif ($assigneeId > 0) { $quarterBaseWhere[] = 't.assignee_id = :assignee_id'; $quarterBaseParams[':assignee_id'] = $assigneeId; }
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

        $users = $this->getAllUsers(!empty($task['assignee_id']) ? [(int)$task['assignee_id']] : []);
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

        $estimate = $this->sanAllowedInt($input->post('estimate_h'), [2, 4, 8]);
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
            ':section'      => substr($this->san($input->post('section')), 0, 100),
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
            // format: Label | scope selector | field.path | message
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) >= 4) {
                $rules[] = $this->normalizeAuditRule([
                    'label'    => $parts[0],
                    'selector' => $parts[1],
                    'field'    => $parts[2],
                    'message'  => $parts[3] ?: $this->_('Field is empty'),
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
        $this->wire('modules')->saveConfig($this, ['audit_rules' => json_encode($rules)]);
        $this->message($this->_('Audit rules saved.'));
        $this->redirect('audit');
    }

    // -------------------------------------------------------------------------
    // Data helpers
    // -------------------------------------------------------------------------

    protected function getTask(int $id): ?array {
        $stmt = $this->wire('database')->prepare("SELECT * FROM vk_tasks WHERE id=:id");
        $stmt->execute([':id'=>$id]);
        $t = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $t ? $this->enrichTaskPage($t) : null;
    }

    protected function getNote(int $id): ?array {
        $stmt = $this->wire('database')->prepare("SELECT * FROM vk_notes WHERE id=:id");
        $stmt->execute([':id'=>$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    protected function enrichTaskPage(array $task): array {
        $task['linked_page']      = null;
        $task['linked_page_edit'] = '';
        $task['linked_page_url']  = '';
        $task['linked_page_title'] = '';
        $task['linked_page_viewable'] = false;
        if (!empty($task['page_id'])) {
            $p = $this->wire('pages')->get((int)$task['page_id']);
            if ($p->id) {
                $task['linked_page']      = $p;
                $task['linked_page_edit'] = $this->wire('config')->urls->admin . 'page/edit/?id=' . $p->id;
                $task['linked_page_viewable'] = $p->viewable();
                $task['linked_page_url']  = $task['linked_page_viewable'] ? $p->httpUrl() : '';
                $task['linked_page_title'] = $this->pageTitleForDisplay($p);
            }
        }
        return $task;
    }

    protected function enrichTaskPagesBatch(array $tasks): array {
        $ids = $this->taskPageIds($tasks);
        if (!$ids) {
            return array_map(fn($t) => $this->emptyLinkedPageData($t), $tasks);
        }
        $pages = $this->wire('pages')->find('id=' . implode('|', $ids) . ', include=all');
        $pageMap = [];
        foreach ($pages as $p) $pageMap[$p->id] = $p;
        $adminUrl = $this->wire('config')->urls->admin;
        foreach ($tasks as &$t) {
            $t = $this->emptyLinkedPageData($t);
            $pid = (int)($t['page_id'] ?? 0);
            if ($pid && isset($pageMap[$pid])) {
                $p = $pageMap[$pid];
                $t['linked_page']      = $p;
                $t['linked_page_edit'] = $adminUrl . 'page/edit/?id=' . $p->id;
                $t['linked_page_viewable'] = $p->viewable();
                $t['linked_page_url']  = $t['linked_page_viewable'] ? $p->httpUrl() : '';
                $t['linked_page_title'] = $this->pageTitleForDisplay($p);
            }
        }
        return $tasks;
    }

    protected function taskPageIds(array $tasks): array {
        $ids = [];
        foreach ($tasks as $task) {
            $id = (int)($task['page_id'] ?? 0);
            if ($id <= 0 || isset($ids[$id])) continue;
            $ids[$id] = $id;
        }
        return array_values($ids);
    }

    protected function emptyLinkedPageData(array $task): array {
        $task['linked_page'] = null;
        $task['linked_page_edit'] = '';
        $task['linked_page_url'] = '';
        $task['linked_page_title'] = '';
        $task['linked_page_viewable'] = false;
        return $task;
    }

    protected function pageTitleForDisplay(Page $page): string {
        $title = trim((string)$page->title);
        return $title !== '' ? $title : ('#' . (int)$page->id . ' ' . $page->name);
    }

    protected function getUpcomingPublications(int $days = 14): array {
        $cfg = $this->getConfig();
        if (!$cfg['calendar_template'] || !$cfg['calendar_date_field']) return [];

        $field = $this->wire('fields')->get($cfg['calendar_date_field']);
        if (!$field) return [];

        $today   = date('Y-m-d');
        $future  = date('Y-m-d', strtotime("+$days days"));
        $tpl     = $cfg['calendar_template'];

        try {
            $pages = $this->wire('pages')->find(
                "template=$tpl, {$cfg['calendar_date_field']}>=$today, {$cfg['calendar_date_field']}<=$future, sort={$cfg['calendar_date_field']}, limit=30"
            );
        } catch (\Exception $e) {
            return [];
        }

        $out = [];
        foreach ($pages as $p) {
            $out[] = [
                'id'    => $p->id,
                'title' => $p->title,
                'date'  => (string)$p->get($cfg['calendar_date_field']),
                'url'   => $p->httpUrl(),
                'edit'  => $this->wire('config')->urls->admin . 'page/edit/?id=' . $p->id,
            ];
        }
        return $out;
    }

    protected function getCalendarItems(int $month, int $year, int $limit = 100): array {
        $start = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
        $end   = date('Y-m-t', strtotime($start));
        return $this->getCalendarItemsRange($start, $end, $limit);
    }

    protected function getCalendarItemsRange(string $start, string $end, int $limit = 100): array {
        $cfg = $this->getConfig();
        if (!$cfg['calendar_template'] || !$cfg['calendar_date_field']) return [];

        try {
            $pages = $this->wire('pages')->find(
                "template={$cfg['calendar_template']}, {$cfg['calendar_date_field']}>=$start, {$cfg['calendar_date_field']}<=$end, sort={$cfg['calendar_date_field']}, limit=$limit"
            );
        } catch (\Exception $e) {
            return [];
        }

        $byDay = [];
        foreach ($pages as $p) {
            $d = (string)$p->get($cfg['calendar_date_field']);
            $dateKey = substr($d, 0, 10);
            $byDay[$dateKey][] = [
                'id'    => $p->id,
                'title' => $p->title,
                'date'  => $d,
                'edit'  => $this->wire('config')->urls->admin . 'page/edit/?id=' . $p->id,
                'url'   => $p->httpUrl(),
            ];
        }
        return $byDay;
    }

    protected function getTaskDeadlines(int $month, int $year, int $assigneeId = 0): array {
        $start = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
        $end   = date('Y-m-t', strtotime($start));
        return $this->getTaskDeadlinesRange($start, $end, $assigneeId);
    }

    protected function getTaskDeadlinesRange(string $start, string $end, int $assigneeId = 0): array {
        $where = "due_date IS NOT NULL AND due_date >= :start AND due_date <= :end";
        $params = [':start' => $start, ':end' => $end];
        if ($assigneeId > 0) {
            $where .= " AND assignee_id = :assignee_id";
            $params[':assignee_id'] = $assigneeId;
        }
        $stmt = $this->wire('database')->prepare(
            "SELECT id, title, due_date, priority, status, assignee_id FROM vk_tasks
             WHERE $where
             ORDER BY due_date ASC"
        );
        $stmt->execute($params);
        $tasks = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $userMap = $this->getUserMap(array_column($tasks, 'assignee_id'));

        $byDay = [];
        foreach ($tasks as $t) {
            $t['assignee_name'] = $userMap[(int)($t['assignee_id'] ?? 0)] ?? '';
            $dateKey = substr((string)$t['due_date'], 0, 10);
            $byDay[$dateKey][] = $t;
        }
        return $byDay;
    }

    protected function getAuditRules(): array {
        $cfg = $this->getConfig();
        if (empty($cfg['audit_rules'])) return $this->getDefaultAuditRules();
        $rules = json_decode($cfg['audit_rules'], true);
        if (!is_array($rules)) return $this->getDefaultAuditRules();
        return array_values(array_map(fn(array $rule): array => $this->normalizeAuditRule($rule), $rules));
    }

    protected function getDefaultAuditRules(): array {
        return [
            ['label' => $this->_('Pages without body text'), 'selector' => 'template!=admin', 'field' => 'body',   'message' => $this->_('Body field is empty')],
            ['label' => $this->_('Pages without images'),    'selector' => 'template!=admin', 'field' => 'images', 'message' => $this->_('No images found')],
        ];
    }

    protected function normalizeAuditRule(array $rule): array {
        $selector = trim((string)($rule['selector'] ?? 'template!=admin'));
        $field    = trim((string)($rule['field'] ?? ''));

        if ($field === '') {
            $scope = [];
            foreach (array_filter(array_map('trim', explode(',', $selector)), 'strlen') as $part) {
                if ($field === '' && preg_match('/^([A-Za-z_][A-Za-z0-9_]*(?:\.(?:[A-Za-z0-9_]+|\*))+|[A-Za-z_][A-Za-z0-9_]*)\s*=\s*$/', $part, $match)) {
                    $field = $match[1];
                    continue;
                }
                $scope[] = $part;
            }
            if ($field !== '') $selector = implode(', ', $scope);
        }

        return [
            'label'    => trim((string)($rule['label'] ?? $this->_('Audit rule'))),
            'selector' => $selector ?: 'template!=admin',
            'field'    => preg_replace('/[^A-Za-z0-9_.*]/', '', $field) ?? '',
            'message'  => trim((string)($rule['message'] ?? $this->_('Field is empty'))),
        ];
    }

    protected function runAuditRule(array $rule): array {
        $rule  = $this->normalizeAuditRule($rule);
        $field = $rule['field'];
        $root  = strtok($field, '.');
        // Field path is optional. When set, the rule reports pages in scope
        // whose field-path value is empty (a "missing content" audit). When left
        // blank, the rule is a pure scope-selector audit that reports every page
        // the selector matches.
        if ($field !== '' && !$this->wire('fields')->get($root) && !in_array($root, ['id', 'name', 'title', 'url'], true)) {
            return [
                'setup' => sprintf($this->_('Field path "%s" is not available on this site. Enter a field or dot-notation subfield that exists.'), $field),
                'pages' => [],
                'total' => 0,
            ];
        }

        try {
            $pages = $this->wire('pages')->find($rule['selector'] . ', limit=200');
        } catch (\Exception $e) {
            return ['error' => $e->getMessage(), 'pages' => []];
        }

        $out = [];
        foreach ($pages as $p) {
            if ($field !== '') {
                if (!$this->pageHasAuditField($p, $root)) continue;
                if (!$this->auditValueIsEmpty($this->auditDotValue($p, $field))) continue;
            }
            $out[] = [
                'id'       => $p->id,
                'title'    => $this->pageTitleForDisplay($p),
                'template' => (string)$p->template,
                'edit'     => $this->wire('config')->urls->admin . 'page/edit/?id=' . $p->id,
                'url'      => $p->url,
            ];
        }
        return ['pages' => $out, 'total' => count($out)];
    }

    protected function pageHasAuditField(Page $page, string $field): bool {
        if (in_array($field, ['id', 'name', 'title', 'url'], true)) return true;
        if (method_exists($page, 'hasField')) return (bool)$page->hasField($field);
        return (bool)$page->template->fieldgroup->hasField($field);
    }

    public function getAuditExportResults(array $rule): array {
        return $this->runAuditRule($rule);
    }

    protected function auditDotValue($page, string $path): mixed {
        $segments = explode('.', $path);
        $root     = array_shift($segments);
        $field    = $this->wire('fields')->get($root);
        $value    = method_exists($page, 'getUnformatted') ? $page->getUnformatted($root) : $page->get($root);
        if (!$segments) return $value;

        if ($field && $field->type->className() === 'FieldtypeRepeaterMatrix') {
            return $this->auditMatrixValue($value, $segments, $field);
        }
        return $this->auditNestedValue($value, $segments);
    }

    protected function auditMatrixValue($items, array $segments, $field): mixed {
        if (!$items || !count($items) || !$segments) return null;
        $first = array_shift($segments);

        if ($first === '*') {
            $values = [];
            foreach ($items as $item) {
                $itemSegments = $segments;
                if (count($itemSegments) > 1) {
                    $type = array_shift($itemSegments);
                    if ($this->auditMatrixTypeName($item, $field) !== $type) continue;
                }
                $values[] = $this->auditNestedValue($item, $itemSegments);
            }
            return $values;
        }

        if ($segments) {
            foreach ($items as $item) {
                if ($this->auditMatrixTypeName($item, $field) === $first) {
                    return $this->auditNestedValue($item, $segments);
                }
            }
        }

        $item = $items->first();
        return $item ? $this->auditNestedValue($item, array_merge([$first], $segments)) : null;
    }

    protected function auditMatrixTypeName($item, $field): string {
        try {
            if (method_exists($item, 'matrix')) return (string)$item->matrix('name');
        } catch (\Throwable) {
        }
        try {
            $type = (int)$item->getUnformatted('repeater_matrix_type');
            return $type > 0 ? (string)$field->get("matrix{$type}_name") : '';
        } catch (\Throwable) {
            return '';
        }
    }

    protected function auditNestedValue($value, array $segments): mixed {
        if (!$segments) return $value;
        $segment = array_shift($segments);

        if ($segment === '*') {
            $values = [];
            if (is_iterable($value)) {
                foreach ($value as $item) $values[] = $this->auditNestedValue($item, $segments);
            }
            return $values;
        }
        if ($value instanceof WireArray) {
            $value = $value->first();
        } elseif (is_array($value)) {
            $isList = $value === [] || array_keys($value) === range(0, count($value) - 1);
            if ($isList && !array_key_exists($segment, $value)) $value = reset($value);
        }
        if ($value === false || $value === null) return null;
        if (is_array($value)) return $this->auditNestedValue($value[$segment] ?? null, $segments);
        if (is_object($value) && method_exists($value, 'get')) {
            return $this->auditNestedValue($value->get($segment), $segments);
        }
        return null;
    }

    protected function auditValueIsEmpty(mixed $value): bool {
        if (is_array($value)) {
            if (!$value) return true;
            foreach ($value as $item) {
                if (!$this->auditValueIsEmpty($item)) return false;
            }
            return true;
        }
        if ($value instanceof PageArray || $value instanceof WireArray) return count($value) === 0;
        if ($value instanceof Page) return !$value->id;
        if ($value === null || $value === false || $value === '') return true;
        if (is_string($value)) return trim(strip_tags($value)) === '';
        return false;
    }

    protected function getAuditSummary(): array {
        $rules  = $this->getAuditRules();
        $summary = [];
        foreach ($rules as $i => $rule) {
            $result = $this->runAuditRule($rule);
            $count = (int)($result['total'] ?? 0);
            $summary[] = ['label' => $rule['label'], 'count' => $count, 'index' => $i];
        }
        return $summary;
    }

    protected function getConfig(): array {
        $saved = $this->wire('modules')->getConfig($this);
        return array_merge(self::getDefaultConfig(), is_array($saved) ? $saved : []);
    }

    public function getAllUsers(array $includeUserIds = []): array {
        $out = [];
        foreach ($this->findAssignableUsers($includeUserIds) as $u) {
            $out[(int)$u->id] = ['id' => $u->id, 'name' => $u->name, 'email' => $u->email];
        }
        uasort($out, fn(array $a, array $b): int => strcasecmp((string)$a['name'], (string)$b['name']));
        return array_values($out);
    }

    protected function sanRoleList(string $value): string {
        $roles = [];
        foreach (array_filter(array_map('trim', explode(',', $value))) as $roleName) {
            $roleName = preg_replace('/[^A-Za-z0-9_-]/', '', $roleName) ?? '';
            if ($roleName === '') continue;
            $role = $this->wire('roles')->get($roleName);
            if ($role && $role->id) $roles[$roleName] = $roleName;
        }
        return implode(', ', array_values($roles));
    }

    protected function assigneeRoleNames(): array {
        $cfg = $this->getConfig();
        $roles = [];
        foreach (array_filter(array_map('trim', explode(',', (string)($cfg['assignee_roles'] ?? '')))) as $roleName) {
            $roleName = preg_replace('/[^A-Za-z0-9_-]/', '', $roleName) ?? '';
            if ($roleName !== '') $roles[$roleName] = $roleName;
        }
        return array_values($roles);
    }

    protected function findAssignableUsers(array $includeUserIds = []): array {
        $users = $this->wire('users');
        $guestId = (int)$this->wire('config')->guestUserPageID;
        $selectorParts = ["id!=$guestId"];
        $roleNames = $this->assigneeRoleNames();
        if ($roleNames) {
            $selectorParts[] = 'roles=' . implode('|', $roleNames);
        }

        $out = [];
        foreach ($users->find(implode(', ', $selectorParts) . ', sort=name, limit=500') as $u) {
            $out[(int)$u->id] = $u;
        }

        foreach (array_unique(array_filter(array_map('intval', $includeUserIds))) as $userId) {
            if ($userId === $guestId || isset($out[$userId])) continue;
            $u = $users->get($userId);
            if ($u && $u->id && (int)$u->id !== $guestId) $out[(int)$u->id] = $u;
        }

        uasort($out, fn($a, $b): int => strcasecmp((string)$a->name, (string)$b->name));
        return array_values($out);
    }

    // Translated display labels for the status/priority enums (shared by views).
    public function statusLabel(string $status): string {
        $map = [
            'open'        => $this->_('Open'),
            'in_progress' => $this->_('In Progress'),
            'review'      => $this->_('Review'),
            'done'        => $this->_('Done'),
        ];
        return $map[$status] ?? $status;
    }

    public function priorityLabel(string $priority): string {
        $map = [
            'low'      => $this->_('Low'),
            'medium'   => $this->_('Medium'),
            'high'     => $this->_('High'),
            'critical' => $this->_('Critical'),
        ];
        return $map[$priority] ?? $priority;
    }

    public function sprintStatusLabel(string $status): string {
        $map = [
            'planned'   => $this->_('Planned'),
            'active'    => $this->_('Active'),
            'completed' => $this->_('Completed'),
        ];
        return $map[$status] ?? $status;
    }

    public function quarterLabel(array $quarter): string {
        return 'Q' . (int)$quarter['quarter'] . ' ' . (int)$quarter['year'];
    }

    public function quarterLabelForDate(?string $date): string {
        if (!$date) return '';
        $ts = strtotime($date);
        if (!$ts) return '';
        $ctx = $this->quarterContextForDate(date('Y-m-d', $ts));
        return $this->quarterLabel($ctx);
    }

    public function quarterLabelForRange(?string $startDate, ?string $endDate): string {
        $startLabel = $this->quarterLabelForDate($startDate);
        $endLabel = $this->quarterLabelForDate($endDate ?: $startDate);
        if (!$startLabel && !$endLabel) return '';
        if (!$endLabel || $startLabel === $endLabel) return $startLabel;
        return $startLabel . ' - ' . $endLabel;
    }

    public function quarterStartMonth(): int {
        $cfg = $this->getConfig();
        return max(1, min(12, (int)($cfg['quarter_start_month'] ?? 1)));
    }

    public function quarterContext(int $quarter = 0, int $year = 0): array {
        $fiscalStart = $this->quarterStartMonth();
        if ($quarter <= 0 || $year <= 0) {
            $currentMonth = (int)date('n');
            $currentYear = (int)date('Y');
            $offset = ($currentMonth - $fiscalStart + 12) % 12;
            if ($quarter <= 0) $quarter = (int)floor($offset / 3) + 1;
            if ($year <= 0) $year = $currentMonth < $fiscalStart ? $currentYear - 1 : $currentYear;
        }
        $quarter = max(1, min(4, $quarter));
        $year = max(2000, min(2100, $year));
        $startMonthRaw = $fiscalStart + (($quarter - 1) * 3);
        $startMonth = (($startMonthRaw - 1) % 12) + 1;
        $startYear = $year + (int)floor(($startMonthRaw - 1) / 12);
        $start = sprintf('%04d-%02d-01', $startYear, $startMonth);
        $end = date('Y-m-t', strtotime($start . ' +2 months'));

        $months = [];
        for ($i = 0; $i < 3; $i++) {
            $monthDate = strtotime($start . " +$i months");
            $months[] = [
                'month' => (int)date('n', $monthDate),
                'year' => (int)date('Y', $monthDate),
            ];
        }

        return [
            'quarter' => $quarter,
            'year' => $year,
            'start_month' => $startMonth,
            'end_month' => (int)date('n', strtotime($end)),
            'start' => $start,
            'end' => $end,
            'fiscal_start_month' => $fiscalStart,
            'months' => $months,
        ];
    }

    public function quarterContextForDate(string $date): array {
        $ts = strtotime($date);
        if (!$ts) return $this->quarterContext();
        $fiscalStart = $this->quarterStartMonth();
        $month = (int)date('n', $ts);
        $calendarYear = (int)date('Y', $ts);
        $offset = ($month - $fiscalStart + 12) % 12;
        $quarter = (int)floor($offset / 3) + 1;
        $fiscalYear = $month < $fiscalStart ? $calendarYear - 1 : $calendarYear;
        return $this->quarterContext($quarter, $fiscalYear);
    }


    // -------------------------------------------------------------------------
    // Bulk audit task creation
    // -------------------------------------------------------------------------

    protected function viewBulkAuditForm(): string {
        $raw     = $this->wire('input')->get('page_ids', 'string');
        $pageIds = $this->sanIdList($raw, 200);
        if (!$pageIds) { $this->redirect('audit'); }

        $pages = [];
        foreach ($pageIds as $pid) {
            $p = $this->wire('pages')->get($pid);
            if ($p->id) $pages[] = ['id'=>$p->id, 'title'=>$p->title, 'url'=>$p->url];
        }

        if (!$pages) {
            $this->error($this->_('None of the selected pages could be resolved.'));
            $this->redirect('audit');
        }

        $ruleLabel = $this->san($this->wire('input')->get('rule_label'));
        $users     = $this->getAllUsers();
        $sprints   = $this->getAllSprints();

        ob_start(); require __DIR__ . '/views/bulk-task-form.php'; return ob_get_clean();
    }


    // -------------------------------------------------------------------------
    // DOCX export
    // -------------------------------------------------------------------------

    protected function viewExportDocx(): string {
        $input = $this->wire('input');
        $type  = $input->get('type', 'string');
        $id    = (int)$input->get('id');

        while (ob_get_level() > 0) ob_end_clean();
        switch ($type) {
            case 'sprint':   $this->export->exportSprintDocx($id);                                          break;
            case 'tasks':    $this->export->exportTasksDocx(
                                 ...array_values($this->exportTaskFilters([
                                     'status' => $input->get('status', 'string') ?: '',
                                     'priority' => $input->get('priority', 'string') ?: '',
                                     'assignee_id' => (int)$input->get('assignee_id'),
                                     'sprint_id' => (int)$input->get('sprint_id'),
                                     'quarter' => (int)$input->get('quarter'),
                                     'year' => (int)$input->get('year'),
                                     'q' => $input->get('q', 'string') ?: '',
                                     'date_state' => $input->get('date_state', 'string') ?: '',
                                 ]))
                             );                                                                               break;
            case 'kb_note':  $this->export->exportKbNoteDocx($id);                                          break;
            case 'kb_cat':   $this->export->exportKbCatDocx($input->get('cat', 'string') ?: '');            break;
            case 'audit':    $this->export->exportAuditDocx((int)$input->get('rule'), $this->getAuditRules()); break;
            default:         header('Location: ' . $this->page->url); exit;
        }
        exit;
    }

    protected function exportTaskFilters(array $input): array {
        $status = in_array((string)($input['status'] ?? ''), ['open', 'in_progress', 'review', 'done'], true)
            ? (string)$input['status']
            : '';
        $priority = in_array((string)($input['priority'] ?? ''), ['low', 'medium', 'high', 'critical'], true)
            ? (string)$input['priority']
            : '';
        $quarter = (int)($input['quarter'] ?? 0);
        return [
            'status' => $status,
            'priority' => $priority,
            'assignee_id' => (int)($input['assignee_id'] ?? 0),
            'sprint_id' => (int)($input['sprint_id'] ?? 0),
            'quarter' => $quarter ? $this->quarterContext($quarter, (int)($input['year'] ?? 0) ?: (int)date('Y')) : null,
            'search' => trim(substr((string)($input['q'] ?? ''), 0, 120)),
            'date_state' => (string)($input['date_state'] ?? '') === 'none' ? 'none' : '',
        ];
    }

    // -------------------------------------------------------------------------
    // Sprint views
    // -------------------------------------------------------------------------

    protected function viewSprints(): string {
        $db     = $this->wire('database');
        $input  = $this->wire('input');
        $quarter = (int)$input->get('quarter');
        $sprintSearch = trim(substr($input->get('q', 'string') ?: '', 0, 120));
        $sprintStatus = $input->get('status', 'string');
        if (!in_array($sprintStatus, ['planned', 'active', 'completed'], true)) $sprintStatus = '';
        $sprintHealth = $input->get('health', 'string');
        if (!in_array($sprintHealth, ['attention'], true)) $sprintHealth = '';
        $sprintDateState = $input->get('date_state', 'string');
        if (!in_array($sprintDateState, ['none'], true)) $sprintDateState = '';
        $sprintQuarterYear = (int)$input->get('year');
        if (!$sprintQuarterYear) $sprintQuarterYear = (int)$this->quarterContextForDate(date('Y-m-d'))['year'];
        if ($sprintDateState === 'none') $quarter = 0;
        $sprintQuarter = $quarter ? $this->quarterContext($quarter, $sprintQuarterYear) : null;
        $sprintCountWhere = [];
        $sprintCountParams = [];
        if ($sprintStatus) {
            $sprintCountWhere[] = "s.status = :count_status";
            $sprintCountParams[':count_status'] = $sprintStatus;
        }
        if ($sprintSearch !== '') {
            $sprintCountWhere[] = "(s.name LIKE :count_search OR s.goal LIKE :count_search)";
            $sprintCountParams[':count_search'] = '%' . $sprintSearch . '%';
        }
        $sprintCountSql = $sprintCountWhere ? ' AND ' . implode(' AND ', $sprintCountWhere) : '';
        $sprintQuarterCounts = [];
        for ($q = 1; $q <= 4; $q++) {
            $ctx = $this->quarterContext($q, $sprintQuarterYear);
            $quarterStmt = $db->prepare(
                "SELECT COUNT(*)
                 FROM vk_sprints s
                 WHERE s.start_date IS NOT NULL
                   AND s.start_date <= :quarter_end
                   AND (s.end_date IS NULL OR s.end_date >= :quarter_start)
                   $sprintCountSql"
            );
            $quarterStmt->execute($sprintCountParams + [
                ':quarter_start' => $ctx['start'],
                ':quarter_end' => $ctx['end'],
            ]);
            $sprintQuarterCounts[$q] = [
                'context' => $ctx,
                'count' => (int)$quarterStmt->fetchColumn(),
            ];
        }

        $noDateStmt = $db->prepare(
            "SELECT COUNT(*)
             FROM vk_sprints s
             WHERE (s.start_date IS NULL OR s.end_date IS NULL)
               $sprintCountSql"
        );
        $noDateStmt->execute($sprintCountParams);
        $sprintNoDateCount = (int)$noDateStmt->fetchColumn();
        $sprintBacklogCount = (int)$db
            ->query("SELECT COUNT(*) FROM vk_tasks t WHERE t.sprint_id IS NULL OR t.sprint_id = 0")
            ->fetchColumn();
        $baseWhereParts = [];
        $baseParams = [];
        if ($sprintQuarter) {
            $baseWhereParts[] = "s.start_date IS NOT NULL AND s.start_date <= :quarter_end AND (s.end_date IS NULL OR s.end_date >= :quarter_start)";
            $baseParams[':quarter_start'] = $sprintQuarter['start'];
            $baseParams[':quarter_end'] = $sprintQuarter['end'];
        }
        if ($sprintDateState === 'none') {
            $baseWhereParts[] = "(s.start_date IS NULL OR s.end_date IS NULL)";
        }
        if ($sprintSearch !== '') {
            $baseWhereParts[] = "(s.name LIKE :sprint_search OR s.goal LIKE :sprint_search)";
            $baseParams[':sprint_search'] = '%' . $sprintSearch . '%';
        }

        $statusSummaryWhere = $baseWhereParts ? 'WHERE ' . implode(' AND ', $baseWhereParts) : '';
        $statusSummaryStmt = $db->prepare(
            "SELECT s.status, COUNT(*) AS n
             FROM vk_sprints s
             $statusSummaryWhere
             GROUP BY s.status"
        );
        $statusSummaryStmt->execute($baseParams);
        $sprintStatusCounts = ['active' => 0, 'planned' => 0, 'completed' => 0];
        foreach ($statusSummaryStmt->fetchAll(\PDO::FETCH_ASSOC) as $statusRow) {
            $statusKey = (string)($statusRow['status'] ?? '');
            if (isset($sprintStatusCounts[$statusKey])) $sprintStatusCounts[$statusKey] = (int)$statusRow['n'];
        }

        $whereParts = $baseWhereParts;
        $params = $baseParams;
        if ($sprintStatus) {
            $whereParts[] = "s.status = :sprint_status";
            $params[':sprint_status'] = $sprintStatus;
        }
        $where = $whereParts ? 'WHERE ' . implode(' AND ', $whereParts) : '';

        $stmt = $db->prepare(
            "SELECT s.*,
                    COUNT(t.id) as task_count,
                    SUM(CASE WHEN t.status='done' THEN 1 ELSE 0 END) as done_count,
                    COALESCE(SUM(t.story_points), 0) as total_sp,
                    COALESCE(SUM(t.estimate_h), 0) as total_est,
                    COALESCE(SUM(t.actual_h), 0) as total_actual
             FROM vk_sprints s
             LEFT JOIN vk_tasks t ON t.sprint_id = s.id
             $where
             GROUP BY s.id
             ORDER BY FIELD(s.status,'active','planned','completed'), s.start_date DESC"
        );
        $stmt->execute($params);
        $sprints = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $today = date('Y-m-d');
        $sprintHealthMap = [];
        foreach ($sprints as $sprintRow) {
            $sid = (int)$sprintRow['id'];
            $hasMissingDates = empty($sprintRow['start_date']) || empty($sprintRow['end_date']);
            $hasMissingGoal = trim(strip_tags((string)($sprintRow['goal'] ?? ''))) === '';
            $hasNoTasks = (int)($sprintRow['task_count'] ?? 0) === 0;
            $isOverdue = !empty($sprintRow['end_date']) && (string)$sprintRow['end_date'] < $today && (string)$sprintRow['status'] !== 'completed';
            $sprintHealthMap[$sid] = [
                'needs_attention' => $hasMissingDates || $hasMissingGoal || $hasNoTasks || $isOverdue,
                'missing_dates' => $hasMissingDates,
                'missing_goal' => $hasMissingGoal,
                'no_tasks' => $hasNoTasks,
                'overdue' => $isOverdue,
            ];
        }
        if ($sprintHealth === 'attention') {
            $sprints = array_values(array_filter($sprints, function(array $sprintRow) use ($sprintHealthMap): bool {
                return !empty($sprintHealthMap[(int)$sprintRow['id']]['needs_attention']);
            }));
        }
        $sprintTaskPreview = [];
        $sprintIds = array_values(array_filter(array_map('intval', array_column($sprints, 'id'))));
        if ($sprintIds) {
            $placeholders = implode(',', array_fill(0, count($sprintIds), '?'));
            $taskPreviewStmt = $db->prepare(
                "SELECT id, sprint_id, title, status, priority, due_date, page_id
                 FROM vk_tasks
                 WHERE sprint_id IN ($placeholders)
                 ORDER BY sprint_id ASC,
                          FIELD(status,'open','in_progress','review','done'),
                          FIELD(priority,'critical','high','medium','low'),
                          due_date IS NULL,
                          due_date ASC,
                          id DESC"
            );
            $taskPreviewStmt->execute($sprintIds);
            $taskPreviewRows = $this->enrichTaskPagesBatch($taskPreviewStmt->fetchAll(\PDO::FETCH_ASSOC));
            foreach ($taskPreviewRows as $taskPreview) {
                $sid = (int)$taskPreview['sprint_id'];
                if (!isset($sprintTaskPreview[$sid])) $sprintTaskPreview[$sid] = [];
                if (count($sprintTaskPreview[$sid]) < 4) $sprintTaskPreview[$sid][] = $taskPreview;
            }
        }
        $sprintStats = [
            'active' => 0,
            'planned' => 0,
            'completed' => 0,
            'tasks' => 0,
            'done_tasks' => 0,
            'story_points' => 0,
            'estimate_h' => 0,
            'actual_h' => 0,
            'needs_attention' => 0,
        ];
        foreach ($sprints as $sprintRow) {
            $statusKey = (string)($sprintRow['status'] ?? '');
            if (isset($sprintStats[$statusKey])) $sprintStats[$statusKey]++;
            $sprintStats['tasks'] += (int)($sprintRow['task_count'] ?? 0);
            $sprintStats['done_tasks'] += (int)($sprintRow['done_count'] ?? 0);
            $sprintStats['story_points'] += (int)($sprintRow['total_sp'] ?? 0);
            $sprintStats['estimate_h'] += (float)($sprintRow['total_est'] ?? 0);
            $sprintStats['actual_h'] += (float)($sprintRow['total_actual'] ?? 0);
            if (!empty($sprintHealthMap[(int)$sprintRow['id']]['needs_attention'])) $sprintStats['needs_attention']++;
        }
        ob_start(); require __DIR__ . '/views/sprints.php'; return ob_get_clean();
    }

    protected function viewSprintEdit(): string {
        $id     = (int)$this->wire('input')->get('id');
        $sprint = $id ? $this->getSprint($id) : null;
        if ($id && !$sprint) {
            $this->redirectMissingRecord($this->_('Sprint does not exist.'), 'sprints', 'sprint-edit');
        }

        $tasks = $id ? $this->getSprintTasks($id) : [];

        ob_start(); require __DIR__ . '/views/sprint-form.php'; return ob_get_clean();
    }

    protected function getSprint(int $id): ?array {
        $stmt = $this->wire('database')->prepare("SELECT * FROM vk_sprints WHERE id=:id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    public function getAllSprints(): array {
        $stmt = $this->wire('database')->query(
            "SELECT id, name, status, start_date, end_date FROM vk_sprints
             ORDER BY FIELD(status,'active','planned','completed'), start_date DESC"
        );
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    protected function getSprintTasks(int $sprintId): array {
        $stmt = $this->wire('database')->prepare(
            "SELECT t.* FROM vk_tasks t
             WHERE t.sprint_id = :sid
             ORDER BY FIELD(t.status,'open','in_progress','review','done'),
                      FIELD(t.priority,'critical','high','medium','low')"
        );
        $stmt->execute([':sid' => $sprintId]);
        $tasks = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $userMap = $this->getUserMap(array_column($tasks, 'assignee_id'));
        $returnUrl = $this->page->url . '?view=sprint-edit&id=' . $sprintId . '#vk-sprint-tasks';
        foreach ($tasks as &$t) {
            $t['assignee_name'] = $userMap[(int)$t['assignee_id']] ?? null;
            $t['status_label'] = $this->statusLabel((string)$t['status']);
            $t['priority_label'] = $this->priorityLabel((string)$t['priority']);
            $t['due_label'] = !empty($t['due_date']) ? date('M j', strtotime((string)$t['due_date'])) : $this->_('No due date');
            $t['quarter_label'] = !empty($t['due_date']) ? $this->quarterLabelForDate((string)$t['due_date']) : '';
            $t['edit_url'] = $this->page->url . '?view=task-edit&id=' . (int)$t['id'] . '&return_url=' . rawurlencode($returnUrl);
        }
        unset($t);
        return $tasks;
    }

    protected function sprintTaskPayload(int $sprintId): array {
        $tasks = $this->getSprintTasks($sprintId);
        $sprint = $this->getSprint($sprintId);
        $totalSP = (int)array_sum(array_column($tasks, 'story_points'));
        $totalEst = (float)array_sum(array_column($tasks, 'estimate_h'));
        $totalActual = (float)array_sum(array_column($tasks, 'actual_h'));
        $dueInWindow = 0;
        $dueMissing = 0;
        $dueOutside = 0;
        $hasWindow = $sprint && !empty($sprint['start_date']) && !empty($sprint['end_date']);
        foreach ($tasks as $task) {
            $due = (string)($task['due_date'] ?? '');
            if ($due === '') {
                $dueMissing++;
            } elseif ($hasWindow && $due >= $sprint['start_date'] && $due <= $sprint['end_date']) {
                $dueInWindow++;
            } elseif ($hasWindow) {
                $dueOutside++;
            }
        }
        return [
            'tasks' => $tasks,
            'summary' => [
                'tasks' => count($tasks),
                'done' => count(array_filter($tasks, fn($t) => $t['status'] === 'done')),
                'story_points' => $totalSP,
                'estimated' => $totalEst,
                'actual' => $totalActual,
                'due_in_window' => $dueInWindow,
                'due_missing' => $dueMissing,
                'due_outside' => $dueOutside,
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Sprint actions
    // -------------------------------------------------------------------------

    protected function actionSaveSprint(): string {
        $this->requireCSRF();
        $input = $this->wire('input');
        $db    = $this->wire('database');
        $user  = $this->wire('user');
        $id    = (int)$input->post('id');
        $this->requireOwnerForExisting('vk_sprints', $id);

        $name = substr($this->san($input->post('name')), 0, 255);
        if (!$name) {
            $this->error($this->_('Sprint name is required.'));
            $returnUrl = $this->safeLocalUrl((string)$input->post('return_url'));
            $editUrl = $this->page->url . '?view=sprint-edit' . ($id ? '&id=' . $id : '');
            if ($returnUrl) $editUrl .= '&return_url=' . rawurlencode($returnUrl);
            $this->wire('session')->redirect($editUrl);
        }

        $startDate = $this->sanDate($input->post('start_date'));
        $endDate   = $this->sanDate($input->post('end_date'));
        if ($startDate && $endDate && $endDate < $startDate) {
            $this->error($this->_('Sprint end date must be after the start date.'));
            $returnUrl = $this->safeLocalUrl((string)$input->post('return_url'));
            $editUrl = $this->page->url . '?view=sprint-edit' . ($id ? '&id=' . $id : '');
            if ($returnUrl) {
                $editUrl .= '&return_url=' . rawurlencode($returnUrl);
            }
            $this->wire('session')->redirect($editUrl . '#vk-sprint-schedule');
        }

        $data = [
            ':name'       => $name,
            ':status'     => $this->sanEnum($input->post('status'), ['planned','active','completed']),
            ':start_date' => $startDate,
            ':end_date'   => $endDate,
            ':goal'       => $this->sanRichText($input->post('goal', 'string')),
        ];

        if ($id) {
            $stmt = $db->prepare("UPDATE vk_sprints SET name=:name, status=:status, start_date=:start_date, end_date=:end_date, goal=:goal WHERE id=:id");
            $data[':id'] = $id;
            $stmt->execute($data);
            $this->message($this->_('Sprint updated.'));
        } else {
            $stmt = $db->prepare("INSERT INTO vk_sprints (name, status, start_date, end_date, goal, created_by, created_at) VALUES (:name, :status, :start_date, :end_date, :goal, :uid, NOW())");
            $data[':uid'] = $user->id;
            $stmt->execute($data);
            $id = (int)$db->lastInsertId();
            $this->message($this->_('Sprint created.'));
        }

        $returnUrl = $this->safeLocalUrl((string)$input->post('return_url'));
        if ($returnUrl) {
            $this->wire('session')->redirect($returnUrl);
        }
        $this->redirect('sprint-edit', $id);
    }

    protected function actionDeleteSprint(): string {
        $this->requireCSRF();
        $input = $this->wire('input');
        $id = (int)$input->post('id');
        if (!$id) { $this->redirect('sprints'); }
        $this->requireOwner('vk_sprints', $id);
        $db = $this->wire('database');
        // Unlink tasks — don't delete them
        $db->prepare("UPDATE vk_tasks SET sprint_id=NULL WHERE sprint_id=:id")->execute([':id' => $id]);
        $db->prepare("DELETE FROM vk_sprints WHERE id=:id")->execute([':id' => $id]);
        $this->message($this->_('Sprint deleted. Tasks were unlinked.'));
        $returnUrl = $this->safeLocalUrl((string)$input->post('return_url'));
        if ($returnUrl) {
            $this->wire('session')->redirect($returnUrl);
        }
        $this->redirect('sprints');
    }

    protected function actionUpdateSprintStatus(): string {
        $this->requireCSRF();
        $input = $this->wire('input');
        $id = (int)$input->post('id');
        $status = $this->sanEnum($input->post('status'), ['planned', 'active', 'completed']);
        if (!$id) { $this->redirect('sprints'); }
        $this->requireOwner('vk_sprints', $id);
        $this->wire('database')
            ->prepare("UPDATE vk_sprints SET status=:status WHERE id=:id")
            ->execute([':status' => $status, ':id' => $id]);
        $this->message($this->_('Sprint status updated.'));
        $returnUrl = $this->safeLocalUrl((string)$input->post('return_url'));
        if ($returnUrl) {
            $this->wire('session')->redirect($returnUrl);
        }
        $this->redirect('sprints');
    }

    protected function actionAttachSprintTask(): string {
        $this->requireAjaxCSRF();
        $input    = $this->wire('input');
        $sprintId = (int)$input->post('sprint_id');
        $taskId   = (int)$input->post('task_id');
        if (!$this->fwSprintExists($sprintId) || !$this->fwTaskExists($taskId)) {
            $this->jsonResponse(['ok' => false, 'message' => $this->_('Task or sprint was not found.')], 404);
        }

        $stmt = $this->wire('database')->prepare("UPDATE vk_tasks SET sprint_id=:sprint_id WHERE id=:task_id");
        $stmt->execute([':sprint_id' => $sprintId, ':task_id' => $taskId]);
        $this->jsonResponse(['ok' => true] + $this->sprintTaskPayload($sprintId));
    }

    protected function actionDetachSprintTask(): string {
        $this->requireAjaxCSRF();
        $input    = $this->wire('input');
        $sprintId = (int)$input->post('sprint_id');
        $taskId   = (int)$input->post('task_id');
        if (!$this->fwSprintExists($sprintId) || !$this->fwTaskExists($taskId)) {
            $this->jsonResponse(['ok' => false, 'message' => $this->_('Task or sprint was not found.')], 404);
        }

        $stmt = $this->wire('database')->prepare(
            "UPDATE vk_tasks SET sprint_id=NULL WHERE id=:task_id AND sprint_id=:sprint_id"
        );
        $stmt->execute([':sprint_id' => $sprintId, ':task_id' => $taskId]);
        $this->jsonResponse(['ok' => true] + $this->sprintTaskPayload($sprintId));
    }

    // -------------------------------------------------------------------------
    // Time Log actions
    // -------------------------------------------------------------------------

    protected function actionLogTime(): string {
        $this->requireCSRF();
        $input   = $this->wire('input');
        $db      = $this->wire('database');
        $user    = $this->wire('user');
        $taskId  = (int)$input->post('task_id');
        $back = $this->safeLocalUrl($input->post('back', 'string') ?: '');
        if (!$taskId) {
            $this->error($this->_('Invalid task.'));
            $this->redirect('tasks');
        }
        if (!$this->fwTaskExists($taskId)) {
            $this->error($this->_('Task does not exist.'));
            $this->redirect('tasks');
        }

        $hours = $this->sanPositiveDecimal($input->post('hours', 'string'));
        if ($hours === null) {
            $this->error($this->_('Hours must be greater than 0.'));
            if ($back) $this->wire('session')->redirect($back);
            $this->redirect('task-edit', $taskId);
        }

        $logDate = $this->sanDate($input->post('logged_date')) ?: date('Y-m-d');
        $note    = substr($this->san($input->post('note')), 0, 255);

        $stmt = $db->prepare(
            "INSERT INTO vk_time_logs (task_id, user_id, hours, note, logged_date, created_at)
             VALUES (:task_id, :uid, :hours, :note, :date, NOW())"
        );
        $stmt->execute([
            ':task_id' => $taskId,
            ':uid'     => $user->id,
            ':hours'   => $hours,
            ':note'    => $note,
            ':date'    => $logDate,
        ]);

        // Update actual_h on task with sum of all time logs
        $sum = $db->prepare("SELECT SUM(hours) FROM vk_time_logs WHERE task_id = :id");
        $sum->execute([':id' => $taskId]);
        $total = (float)$sum->fetchColumn();
        $db->prepare("UPDATE vk_tasks SET actual_h = :h WHERE id = :id")
           ->execute([':h' => $total, ':id' => $taskId]);

        $this->message(sprintf($this->_('%sh logged.'), number_format($hours, 1)));
        if ($back) $this->wire('session')->redirect($back);
        $this->redirect('task-edit', $taskId);
    }

    protected function actionDeleteTimeLog(): string {
        $this->requireCSRF();
        $input  = $this->wire('input');
        $db     = $this->wire('database');
        $user   = $this->wire('user');
        $id     = (int)$input->post('id');
        $taskId = (int)$input->post('task_id');
        $back = $this->safeLocalUrl($input->post('back', 'string') ?: '');

        $stmt = $db->prepare("SELECT task_id, user_id FROM vk_time_logs WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row) {
            if (!$user->isSuperuser() && (int)$row['user_id'] !== (int)$user->id) {
                $this->error($this->_('You do not have permission to delete this time log entry.'));
                if ($back) $this->wire('session')->redirect($back);
                $this->redirect('task-edit', (int)$row['task_id']);
            }
            $taskId = (int)$row['task_id'];
            $db->prepare("DELETE FROM vk_time_logs WHERE id = :id")->execute([':id' => $id]);

            // Recalculate actual_h
            $sum = $db->prepare("SELECT COALESCE(SUM(hours), 0) FROM vk_time_logs WHERE task_id = :id");
            $sum->execute([':id' => $taskId]);
            $total = (float)$sum->fetchColumn();
            $db->prepare("UPDATE vk_tasks SET actual_h = :h WHERE id = :id")
               ->execute([':h' => $total ?: null, ':id' => $taskId]);

            $this->message($this->_('Time log deleted.'));
            if ($back) $this->wire('session')->redirect($back);
            $this->redirect('task-edit', $taskId);
        }
        // Log entry not found - go back to task list
        $this->error($this->_('Time log entry not found.'));
        $this->redirect('tasks');
    }

    // -------------------------------------------------------------------------
    // Bulk audit task creation
    // -------------------------------------------------------------------------

    protected function actionBulkAuditTasks(): string {
        $this->requireCSRF();
        $input    = $this->wire('input');
        $db       = $this->wire('database');
        $user     = $this->wire('user');
        $returnUrl = $this->safeLocalUrl((string)$input->post('return_url'));

        // page_ids comes as comma-separated string from hidden field
        $pageIds = $this->sanIdList($input->post('page_ids', 'string'), 200);
        if (!$pageIds) {
            $this->error($this->_('No pages selected.'));
            if ($returnUrl) $this->wire('session')->redirect($returnUrl);
            $this->redirect('audit');
        }

        $titleTpl   = $this->san($input->post('title_template')) ?: '{page_title}';
        $assigneeId = (int)$input->post('assignee_id') ?: null;
        $sprintId   = (int)$input->post('sprint_id')   ?: null;

        if ($assigneeId && !$this->fwUserExists($assigneeId)) {
            $this->error($this->_('Assignee does not exist.'));
            if ($returnUrl) $this->wire('session')->redirect($returnUrl);
            $this->redirect('audit');
        }
        if ($sprintId && !$this->fwSprintExists($sprintId)) {
            $this->error($this->_('Sprint does not exist.'));
            if ($returnUrl) $this->wire('session')->redirect($returnUrl);
            $this->redirect('audit');
        }

        $priority   = $this->sanEnum($input->post('priority'), ['low','medium','high','critical']);
        $status     = $this->sanEnum($input->post('status'),   ['open','in_progress','review','done']);
        $dueDate    = $this->sanDate($input->post('due_date'));
        $estimateH  = $this->sanAllowedInt($input->post('estimate_h'), [2, 4, 8]);
        $spVal      = $this->sanAllowedInt($input->post('story_points'), [1, 2, 3, 5, 8, 13, 21]);
        $desc       = $this->san($input->post('description'));

        $stmt = $db->prepare(
            "INSERT INTO vk_tasks (title, description, status, priority, due_date, page_id,
             assignee_id, sprint_id, estimate_h, story_points, created_by, created_at)
             VALUES (:title, :desc, :status, :priority, :due_date, :page_id,
             :assignee, :sprint, :est, :sp, :uid, NOW())"
        );

        $created = 0;
        foreach ($pageIds as $pid) {
            $page = $this->wire('pages')->get($pid);
            if (!$page->id) continue;
            $title = str_replace('{page_title}', $page->title, $titleTpl);
            $stmt->execute([
                ':title'    => substr($title, 0, 255),
                ':desc'     => $desc,
                ':status'   => $status,
                ':priority' => $priority,
                ':due_date' => $dueDate,
                ':page_id'  => $pid,
                ':assignee' => $assigneeId,
                ':sprint'   => $sprintId,
                ':est'      => $estimateH,
                ':sp'       => $spVal,
                ':uid'      => $user->id,
            ]);
            $created++;
        }

        $this->message(sprintf($this->_n('%d task created.', '%d tasks created.', $created), $created));
        if ($returnUrl) $this->wire('session')->redirect($returnUrl);
        $this->redirect('tasks');
    }

    // Page search (AJAX for task page picker)
    // -------------------------------------------------------------------------

    protected function viewAjaxSearch(): string {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json');
        $q = $this->wire('sanitizer')->text($this->wire('input')->get('q'));
        if (strlen($q) < 2) { echo json_encode([]); exit; }

        $pages = $this->wire('pages')->find("title%=$q, template!=admin, limit=15, sort=title");
        $out   = [];
        foreach ($pages as $p) {
            $out[] = ['id' => $p->id, 'title' => $p->title, 'url' => $p->url, 'template' => (string)$p->template];
        }
        echo json_encode($out);
        exit;
    }

    protected function viewAjaxSprintTasks(): string {
        $input    = $this->wire('input');
        $sprintId = (int)$input->get('sprint_id');
        if (!$this->fwSprintExists($sprintId)) {
            $this->jsonResponse(['ok' => false, 'message' => $this->_('Sprint was not found.')], 404);
        }

        $q      = trim($this->wire('sanitizer')->text($input->get('q')));
        $params = [':sprint_id' => $sprintId];
        $where  = '(t.sprint_id IS NULL OR t.sprint_id != :sprint_id)';
        if ($q !== '') {
            $where .= ' AND t.title LIKE :q';
            $params[':q'] = '%' . $q . '%';
        }

        $stmt = $this->wire('database')->prepare(
            "SELECT t.id, t.title, t.status, t.priority, t.due_date, t.sprint_id, s.name AS sprint_name
             FROM vk_tasks t
             LEFT JOIN vk_sprints s ON s.id = t.sprint_id
             WHERE {$where}
             ORDER BY FIELD(t.status,'open','in_progress','review','done'),
                      FIELD(t.priority,'critical','high','medium','low'), t.created_at DESC
             LIMIT 20"
        );
        $stmt->execute($params);
        $tasks = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($tasks as &$task) {
            $task['status_label'] = $this->statusLabel((string)$task['status']);
            $task['priority_label'] = $this->priorityLabel((string)$task['priority']);
            $task['quarter_label'] = !empty($task['due_date']) ? $this->quarterLabelForDate((string)$task['due_date']) : '';
        }
        unset($task);
        $this->jsonResponse(['ok' => true, 'tasks' => $tasks]);
    }

    // -------------------------------------------------------------------------
    // Shared nav helper (called from views)
    // -------------------------------------------------------------------------

    public function nav(): string {
        $url  = $this->page->url;
        $view = $this->wire('input')->get('view') ?: 'dashboard';
        $activeView = $this->navGroup((string)$view);
        $items = [
            ''         => $this->_('Dashboard'),
            'tasks'    => $this->_('Tasks'),
            'calendar' => $this->_('Calendar'),
            'audit'    => $this->_('Content Audit'),
            'kb'       => $this->_('Knowledge Base'),
            'sprints'  => $this->_('Sprints'),
        ];
        $html = '<div class="vk-admin-nav uk-margin-medium-bottom"><ul class="uk-subnav uk-subnav-pill">';
        foreach ($items as $v => $item) {
            $active = ($activeView === $v || (!$v && $activeView === 'dashboard'));
            $href   = $url . ($v ? '?view=' . $v : '');
            $html  .= '<li class="' . ($active ? 'uk-active' : '') . '">';
            $html  .= '<a href="' . $href . '">' . $item . '</a>';
            $html  .= '</li>';
        }
        $settingsClass = $activeView === 'settings' ? ' is-active' : '';
        $settingsLabel = $this->_('Settings');
        $html .= '</ul><a class="vk-settings-link' . $settingsClass . '" href="' . $url . '?view=settings" title="' . $settingsLabel . '" aria-label="' . $settingsLabel . '">' . $this->renderSettingsIcon() . '</a></div>';
        return $html;
    }

    protected function renderSettingsIcon(): string {
        return '<svg aria-hidden="true" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">'
            . '<path d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.272-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z" stroke-linecap="round" stroke-linejoin="round"></path>'
            . '<path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" stroke-linecap="round" stroke-linejoin="round"></path>'
            . '</svg>';
    }

    protected function navGroup(string $view): string {
        return match($view) {
            'task-edit' => 'tasks',
            'note-edit' => 'kb',
            'sprint-edit' => 'sprints',
            'bulk-audit' => 'audit',
            default => $view,
        };
    }

    protected function setAdminChrome(string $view): void {
        $input = $this->wire('input');
        $titles = [
            'dashboard' => $this->_('Dashboard'),
            'tasks' => $this->_('Tasks'),
            'calendar' => $this->_('Editorial Calendar'),
            'audit' => $this->_('Content Audit'),
            'kb' => $this->_('Knowledge Base'),
            'settings' => $this->_('Settings'),
            'sprints' => $this->_('Sprints'),
            'task-edit' => $input->get('id', 'int') ? $this->_('Edit Task') : $this->_('New Task'),
            'note-edit' => $input->get('id', 'int') ? $this->_('Edit Note') : $this->_('New Note'),
            'sprint-edit' => $input->get('id', 'int') ? $this->_('Edit Sprint') : $this->_('New Sprint'),
            'bulk-audit' => $this->_('Bulk Create Tasks'),
        ];

        $title = $titles[$view] ?? 'Dashboard';
        $this->headline($title);

        $breadcrumbs = $this->wire('breadcrumbs');
        if ($breadcrumbs && method_exists($breadcrumbs, 'add')) {
            $baseUrl = $this->page->url;
            $breadcrumbs->add(new Breadcrumb($baseUrl, 'Verk'));

            $group = $this->navGroup($view);
            $parents = [
                'tasks' => [$this->_('Tasks'), $baseUrl . '?view=tasks'],
                'calendar' => [$this->_('Editorial Calendar'), $baseUrl . '?view=calendar'],
                'audit' => [$this->_('Content Audit'), $baseUrl . '?view=audit'],
                'kb' => [$this->_('Knowledge Base'), $baseUrl . '?view=kb'],
                'settings' => [$this->_('Settings'), $baseUrl . '?view=settings'],
                'sprints' => [$this->_('Sprints'), $baseUrl . '?view=sprints'],
            ];

            if (isset($parents[$group]) && $view !== $group) {
                $breadcrumbs->add(new Breadcrumb($parents[$group][1], $parents[$group][0]));
            }
            if ($view !== 'dashboard') {
                $breadcrumbs->add(new Breadcrumb($this->adminChromeViewUrl($view), $title));
            }
        }
    }

    protected function adminChromeViewUrl(string $view): string {
        $baseUrl = $this->page->url;
        $url = $baseUrl . '?view=' . rawurlencode($view);
        if (in_array($view, ['task-edit', 'note-edit', 'sprint-edit'], true)) {
            $id = (int)$this->wire('input')->get('id');
            if ($id > 0) $url .= '&id=' . $id;
        }
        return $url;
    }

    // -------------------------------------------------------------------------
    // Sanitizers / utils
    // -------------------------------------------------------------------------

    protected function san(mixed $v): string {
        // Store clean text in DB; htmlspecialchars is applied on OUTPUT in views
        return trim(strip_tags((string)$v));
    }

    protected function sanRichText(mixed $v): string {
        return trim($this->wire('sanitizer')->purify((string)$v));
    }

    protected function noteCategoryValue(mixed $category, mixed $newCategory): string {
        $category = (string)$category;
        return $category === '__new__'
            ? substr($this->san($newCategory), 0, 100)
            : substr($this->san($category), 0, 100);
    }

    public function textStats(string $html): array {
        $text = preg_replace('/<(br|\/p|\/li|\/h[1-6])\b[^>]*>/i', ' ', $html) ?? $html;
        $text = trim(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        preg_match_all('/[\p{L}\p{N}]+(?:[-\'][\p{L}\p{N}]+)*/u', $text, $matches);
        return [
            'text' => $text,
            'words' => count($matches[0] ?? []),
            'characters' => mb_strlen($text),
        ];
    }

    protected function sanEnum(mixed $v, array $allowed): string {
        $v = trim((string)$v);
        return in_array($v, $allowed, true) ? $v : $allowed[0];
    }

    protected function sanAllowedInt(mixed $v, array $allowed): ?int {
        $raw = trim((string)$v);
        if ($raw === '') return null;
        $n = (int)$raw;
        return in_array($n, $allowed, true) ? $n : null;
    }

    protected function sanNonNegativeDecimal(mixed $v): ?float {
        $raw = trim((string)$v);
        if ($raw === '' || !is_numeric($raw)) return null;
        $n = round((float)$raw, 1);
        return $n >= 0 ? $n : null;
    }

    protected function sanPositiveDecimal(mixed $v): ?float {
        $raw = trim((string)$v);
        if ($raw === '' || !is_numeric($raw)) return null;
        $n = round((float)$raw, 1);
        return $n > 0 ? $n : null;
    }

    protected function sanIdList(mixed $v, int $limit = 200): array {
        $ids = [];
        foreach (preg_split('/[,\s]+/', (string)$v, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $raw) {
            if (!ctype_digit($raw)) continue;
            $id = (int)$raw;
            if ($id <= 0 || isset($ids[$id])) continue;
            $ids[$id] = $id;
            if (count($ids) >= max(1, $limit)) break;
        }
        return array_values($ids);
    }

    protected function sanDate(mixed $v): ?string {
        $v = trim((string)$v);
        if (!$v) return null;
        $d = \DateTime::createFromFormat('Y-m-d', $v);
        if (!$d) return null;
        $errors = \DateTime::getLastErrors();
        if ($errors && ((int)$errors['warning_count'] > 0 || (int)$errors['error_count'] > 0)) return null;
        return $d->format('Y-m-d') === $v ? $v : null;
    }

    protected function safeLocalUrl(string $url): string {
        return $this->normalizeSafeLocalUrl($url, $this->page->url, $this->wire('config')->urls->admin);
    }

    protected function normalizeSafeLocalUrl(string $url, string $pageUrl, string $adminUrl): string {
        $url = trim($url);
        if ($url === '') return '';
        $parsed = parse_url($url);
        if (!empty($parsed['scheme']) || !empty($parsed['host'])) return '';
        if (str_starts_with($url, $pageUrl) || str_starts_with($url, $adminUrl)) return $url;
        return '';
    }

    protected function paginationBounds(int $requestedPage, int $limit, int $total): array {
        $limit = max(1, $limit);
        $total = max(0, $total);
        $totalPages = max(1, (int)ceil($total / $limit));
        $page = max(1, min(max(1, $requestedPage), $totalPages));
        return [
            'page' => $page,
            'total_pages' => $totalPages,
            'offset' => ($page - 1) * $limit,
        ];
    }

    protected function redirect(string $view = '', int $id = 0): never {
        $url = $this->page->url;
        if ($view) $url .= '?view=' . $view . ($id ? "&id=$id" : '');
        $this->wire('session')->redirect($url);
        exit;
    }

    protected function redirectMissingRecord(string $message, string $fallbackView, string $currentView): never {
        $returnUrl = $this->safeLocalUrl((string)$this->wire('input')->get('return_url'));
        $this->error($message);
        if ($returnUrl && !str_contains($returnUrl, 'view=' . $currentView)) {
            $this->wire('session')->redirect($returnUrl);
            exit;
        }
        $this->redirect($fallbackView);
    }

    public function getCSRFToken(): string { return $this->wire('session')->CSRF->getTokenValue(); }
    public function getCSRFName(): string  { return $this->wire('session')->CSRF->getTokenName(); }

    protected function jsonResponse(array $payload, int $status = 200): never {
        while (ob_get_level() > 0) ob_end_clean();
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
    }

    protected function requireAjaxCSRF(): void {
        if (!$this->wire('config')->protectCSRF || $this->wire('session')->CSRF->hasValidToken()) return;
        $this->jsonResponse(['ok' => false, 'message' => $this->_('Session expired, please reload and try again.')], 403);
    }

    protected function requireCSRF(): void {
        if (!$this->wire('config')->protectCSRF) return;
        if (!$this->wire('session')->CSRF->hasValidToken()) {
            $this->error($this->_('Session expired, please try again.'));
            $this->redirect();
        }
    }

    protected function requireOwner(string $table, int $id): void {
        if ($this->wire('user')->isSuperuser()) return;
        $table = $this->ownerTableName($table);
        if (!$table) {
            $this->error($this->_('Invalid item type.'));
            $this->redirect();
        }
        $stmt = $this->wire('database')->prepare(
            "SELECT created_by FROM `$table` WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row || (int)$row['created_by'] !== (int)$this->wire('user')->id) {
            $this->error($this->_('You do not have permission to change this item.'));
            $this->redirect();
        }
    }

    protected function requireOwnerForExisting(string $table, int $id): void {
        if ($id <= 0) return;
        if (!$this->moduleRecordExists($table, $id)) {
            $this->error($this->_('Item does not exist.'));
            $this->redirect();
        }
        $this->requireOwner($table, $id);
    }

    protected function ownerTableName(string $table): string {
        return in_array($table, ['vk_tasks', 'vk_notes', 'vk_sprints'], true) ? $table : '';
    }

    protected function moduleRecordExists(string $table, int $id): bool {
        $table = $this->ownerTableName($table);
        if (!$table || $id <= 0) return false;
        $stmt = $this->wire('database')->prepare("SELECT id FROM `$table` WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return (bool)$stmt->fetchColumn();
    }

    // FK validation helpers
    protected function fwPageExists(int $id): bool {
        $p = $this->wire('pages')->get($id);
        return $p->id !== 0;
    }

    protected function fwUserExists(int $id): bool {
        $u = $this->wire('users')->get($id);
        return $u && $u->id !== 0;
    }

    protected function getUserMap(array $userIds = []): array {
        $map = [];
        $guestId = (int)$this->wire('config')->guestUserPageID;
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));

        if ($userIds) {
            foreach ($userIds as $userId) {
                if ($userId === $guestId) continue;
                $u = $this->wire('users')->get($userId);
                if ($u && $u->id) $map[(int)$u->id] = $u->name;
            }
            return $map;
        }

        foreach ($this->findAssignableUsers() as $u) {
            $map[(int)$u->id] = $u->name;
        }
        return $map;
    }

    public function renderRichTextEditor(string $name, string $value, int $height = 160): string {
        $editor = $this->wire('modules')->get('InputfieldTinyMCE');
        if (!$editor) {
            return '<textarea name="' . htmlspecialchars($name) . '" class="uk-textarea">'
                . htmlspecialchars($value) . '</textarea>';
        }

        $editor->attr('name', $name);
        $editor->attr('id', 'vk-editor-' . str_replace('_', '-', $name));
        $editor->addClass('vk-tinymce-editor');
        $editor->val($value);
        $editor->height = $height;
        $editor->features = ['toolbar', 'menubar', 'statusbar', 'stickybars', 'purifier', 'pasteFilter'];
        $settings = $this->tinyMceSettings($height);
        $editor->settingsJSON = json_encode($settings);
        $editor->renderReady();
        return '<div class="Inputfield InputfieldTinyMCE vk-tinymce-inputfield" data-configName="default" data-features="pasteFilter" data-settings="'
            . htmlspecialchars(json_encode($settings), ENT_QUOTES, 'UTF-8')
            . '">' . $editor->render() . '</div>';
    }

    protected function tinyMceSettings(int $height): array {
        return [
            'height' => $height . 'px',
            'resize' => true,
            'plugins' => 'anchor code link lists table',
            'toolbar' => 'styles bold italic link blockquote hr bullist numlist table code',
            'menubar' => 'edit view insert format table tools',
            'menu' => [
                'edit' => ['title' => 'Edit', 'items' => 'undo redo | cut copy paste pastetext | selectall'],
                'view' => ['title' => 'View', 'items' => 'code'],
                'insert' => ['title' => 'Insert', 'items' => 'link anchor | hr inserttable'],
                'format' => ['title' => 'Format', 'items' => 'bold italic underline strikethrough | blocks | removeformat'],
                'table' => ['title' => 'Table', 'items' => 'inserttable | cell row column | tableprops deletetable'],
                'tools' => ['title' => 'Tools', 'items' => 'code'],
            ],
            'contextmenu' => 'link unlink lists table removeformat',
        ];
    }

    public function renderRichText(string $value): string {
        if ($value === '') return '';
        if (strpos($value, '<') === false) return nl2br(htmlspecialchars($value));
        return $this->wire('sanitizer')->purify($value);
    }

    protected function fwSprintExists(int $id): bool {
        $stmt = $this->wire('database')->prepare("SELECT id FROM vk_sprints WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return (bool)$stmt->fetchColumn();
    }

    protected function fwTaskExists(int $id): bool {
        $stmt = $this->wire('database')->prepare("SELECT id FROM vk_tasks WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return (bool)$stmt->fetchColumn();
    }
}

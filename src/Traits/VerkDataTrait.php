<?php namespace ProcessWire;

trait VerkDataTrait {

    protected function getTask(int $id): ?array {
        $stmt = $this->wire('database')->prepare("SELECT * FROM vk_tasks WHERE id=:id");
        $stmt->execute([':id'=>$id]);
        $t = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $t ? $this->enrichTaskPage($t) : null;
    }

    /**
     * Everyone attached to a task — creator, assignee, reviewers, collaborators —
     * as a deduplicated list of user ids. Used to decide who hears about a
     * status change. UNION does the dedup so callers get each user once.
     */
    public function taskNotifyRecipients(int $taskId): array {
        if ($taskId <= 0) return [];
        $stmt = $this->wire('database')->prepare(
            "SELECT created_by AS uid FROM vk_tasks WHERE id = :id1 AND created_by IS NOT NULL
             UNION SELECT assignee_id FROM vk_tasks WHERE id = :id2 AND assignee_id IS NOT NULL
             UNION SELECT user_id FROM vk_task_reviewers WHERE task_id = :id3
             UNION SELECT user_id FROM vk_task_collaborators WHERE task_id = :id4"
        );
        $stmt->execute([':id1' => $taskId, ':id2' => $taskId, ':id3' => $taskId, ':id4' => $taskId]);
        return array_values(array_filter(array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN))));
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
        $task['linked_page_status'] = [];
        if (!empty($task['page_id'])) {
            $p = $this->wire('pages')->get((int)$task['page_id']);
            if ($p->id) {
                $task['linked_page']      = $p;
                $task['linked_page_edit'] = $this->wire('config')->urls->admin . 'page/edit/?id=' . $p->id;
                $task['linked_page_viewable'] = $p->viewable();
                $task['linked_page_url']  = $task['linked_page_viewable'] ? $p->httpUrl() : '';
                $task['linked_page_title'] = $this->pageTitleForDisplay($p);
                $task['linked_page_status'] = $this->pageStatusFlags($p);
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
                $t['linked_page_status'] = $this->pageStatusFlags($p);
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
        $task['linked_page_status'] = [];
        return $task;
    }

    protected function pageTitleForDisplay(Page $page): string {
        $title = trim((string)$page->title);
        return $title !== '' ? $title : ('#' . (int)$page->id . ' ' . $page->name);
    }

    /** Publication-status flags for a page. */
    protected function pageStatusFlags(Page $page): array {
        return [
            'hidden'      => $page->isHidden(),
            'unpublished' => $page->isUnpublished(),
            'trashed'     => $page->isTrash(),
        ];
    }

    /**
     * Presentation pieces derived from status flags, for consistent rendering
     * across views: 'class' (space-joined modifier classes, '' if none),
     * 'label' (human status, e.g. "Hidden, Unpublished", '' if none), and
     * 'icon' (trash-icon HTML, '' unless trashed; safe/pre-escaped).
     */
    protected function pageStatusDisplay(array $flags): array {
        $classes = [];
        $label   = [];
        if (!empty($flags['hidden']))      { $classes[] = 'vk-status-hidden';      $label[] = $this->_('Hidden'); }
        if (!empty($flags['unpublished'])) { $classes[] = 'vk-status-unpublished'; $label[] = $this->_('Unpublished'); }
        if (!empty($flags['trashed']))     { $classes[] = 'vk-status-trashed';     $label[] = $this->_('Trashed'); }
        return [
            'class' => implode(' ', $classes),
            'label' => implode(', ', $label),
            'icon'  => !empty($flags['trashed'])
                ? '<i class="fa fa-trash vk-status-icon" aria-hidden="true"></i>'
                : '',
        ];
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
                'id'     => $p->id,
                'title'  => $p->title,
                'date'   => (string)$p->get($cfg['calendar_date_field']),
                'url'    => $p->httpUrl(),
                'edit'   => $this->wire('config')->urls->admin . 'page/edit/?id=' . $p->id,
                'status' => $this->pageStatusFlags($p),
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
                'id'     => $p->id,
                'title'  => $p->title,
                'date'   => $d,
                'edit'   => $this->wire('config')->urls->admin . 'page/edit/?id=' . $p->id,
                'url'    => $p->httpUrl(),
                'status' => $this->pageStatusFlags($p),
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
}

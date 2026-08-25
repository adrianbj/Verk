<?php namespace ProcessWire;

trait VerkMetaTrait {

    /**
     * Open-task workload per assignee for the dashboard chart. One grouped
     * query; aggregated in PHP into counts and estimate hours for both the
     * status and priority breakdowns. Unassigned tasks fold into id 0.
     *
     * @return array{assignees: array<int, array>, missingEstimates: bool}
     */
    public function getWorkloadByAssignee(): array {
        $statuses   = ['open', 'in_progress', 'review'];
        $priorities = ['critical', 'high', 'medium', 'low'];

        $stmt = $this->wire('database')->query(
            "SELECT assignee_id, status, priority, COUNT(*) AS cnt, SUM(COALESCE(estimate_h, 0)) AS hrs
             FROM vk_tasks
             WHERE status != 'done'
             GROUP BY assignee_id, status, priority"
        );

        $rows = [];
        $missingEstimates = false;
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $aid = (int)$r['assignee_id'];
            if (!isset($rows[$aid])) {
                $rows[$aid] = [
                    'count'      => 0,
                    'hours'      => 0.0,
                    'byStatus'   => array_fill_keys($statuses, ['count' => 0, 'hours' => 0.0]),
                    'byPriority' => array_fill_keys($priorities, ['count' => 0, 'hours' => 0.0]),
                ];
            }
            $cnt = (int)$r['cnt'];
            $hrs = (float)$r['hrs'];
            $st  = in_array($r['status'], $statuses, true) ? $r['status'] : 'open';
            $pr  = in_array($r['priority'], $priorities, true) ? $r['priority'] : 'medium';
            if ($hrs <= 0) $missingEstimates = true;

            $rows[$aid]['count'] += $cnt;
            $rows[$aid]['hours'] += $hrs;
            $rows[$aid]['byStatus'][$st]['count']   += $cnt;
            $rows[$aid]['byStatus'][$st]['hours']   += $hrs;
            $rows[$aid]['byPriority'][$pr]['count'] += $cnt;
            $rows[$aid]['byPriority'][$pr]['hours'] += $hrs;
        }

        $out = [];
        foreach ($rows as $aid => $data) {
            if ($aid === 0) {
                $name = $this->_('Unassigned');
            } else {
                $u = $this->wire('users')->get($aid);
                $name = ($u && $u->id) ? $u->name : sprintf('User #%d', $aid);
            }
            $out[] = ['id' => $aid, 'name' => $name] + $data;
        }
        usort($out, fn(array $a, array $b): int => $b['count'] <=> $a['count']);

        return ['assignees' => $out, 'missingEstimates' => $missingEstimates];
    }

    public function getConfig(): array {
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

    /**
     * Whether the current user holds one of the configured status manager
     * roles. Status managers may change the status of, and edit, any task —
     * including tasks they are not on. Empty config means no managers.
     */
    public function isStatusManager(): bool {
        $cfg = $this->getConfig();
        $user = $this->wire('user');
        foreach (array_filter(array_map('trim', explode(',', (string)($cfg['status_manager_roles'] ?? '')))) as $roleName) {
            if ($user->hasRole($roleName)) return true;
        }
        return false;
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

    /**
     * WHERE fragment for a task status filter. The pseudo-status 'active' matches
     * any non-done task (open + in progress + review) — the same set the
     * dashboard's "Open / Needs attention" tile counts. Returns '' for no filter.
     */
    public function taskStatusWhere(string $status, array &$params): string {
        if ($status === '') return '';
        if ($status === 'active') return "t.status != 'done'";
        $params[':status'] = $status;
        return 't.status = :status';
    }

    // Translated display labels for the status/priority enums (shared by views).
    public function statusLabel(string $status): string {
        $map = [
            'active'      => $this->_('Active'),
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
}

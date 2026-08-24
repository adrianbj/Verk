<?php namespace ProcessWire;

trait VerkUiTrait {

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

    protected function sectionValue(mixed $section, mixed $newSection): string {
        $section = (string) $section;
        return $section === '__new__'
            ? substr($this->san($newSection), 0, 100)
            : substr($this->san($section), 0, 100);
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

    protected function sanAllowedNum(mixed $v, array $allowed): ?float {
        $raw = trim((string)$v);
        if ($raw === '') return null;
        $n = (float)$raw;
        foreach ($allowed as $a) {
            if (abs($n - (float)$a) < 0.001) return $n;
        }
        return null;
    }

    /** Format an hours value for display: <1h as minutes (15m), else trimmed hours (4h, 1.5h). */
    public function formatEstimate($h): string {
        $h = (float)$h;
        if ($h <= 0) return '';
        if ($h < 1) return rtrim(rtrim(number_format($h * 60, 2, '.', ''), '0'), '.') . 'm';
        return rtrim(rtrim(number_format($h, 2, '.', ''), '0'), '.') . 'h';
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

    /**
     * Fetch the facts needed to decide who may touch a task, plus the title and
     * current status callers need for messaging: its creator, its assignee, and
     * whether the current user is a listed reviewer or collaborator. One query
     * so callers don't fan out per role.
     */
    protected function getTaskRoleRow(int $taskId): ?array {
        if ($taskId <= 0) return null;
        $uid  = (int) $this->wire('user')->id;
        $stmt = $this->wire('database')->prepare(
            "SELECT t.created_by, t.assignee_id, t.title, t.status,
                    EXISTS(SELECT 1 FROM vk_task_reviewers r
                           WHERE r.task_id = t.id AND r.user_id = :ruid)     AS is_reviewer,
                    EXISTS(SELECT 1 FROM vk_task_collaborators c
                           WHERE c.task_id = t.id AND c.user_id = :cuid)     AS is_collaborator
             FROM vk_tasks t WHERE t.id = :id"
        );
        $stmt->execute([':id' => $taskId, ':ruid' => $uid, ':cuid' => $uid]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * The assignee, the creator, and superusers may always change a task's
     * status. Reviewers and collaborators may too, but only while the matching
     * setting is enabled. Expects a row from getTaskRoleRow().
     */
    protected function canChangeTaskStatus(array $row): bool {
        $user = $this->wire('user');
        if ($user->isSuperuser()) return true;
        $uid = (int) $user->id;
        if ((int)($row['created_by'] ?? 0) === $uid) return true;
        if ((int)($row['assignee_id'] ?? 0) === $uid) return true;
        $cfg = $this->getConfig();
        if (!empty($row['is_reviewer']) && !empty($cfg['status_edit_reviewer'])) return true;
        if (!empty($row['is_collaborator']) && !empty($cfg['status_edit_collaborator'])) return true;
        return false;
    }

    /**
     * Who may approve or request changes on a task: superusers, the task's
     * creator, and the users actually listed as its reviewers.
     *
     * Provider-backed tasks are exempt. VerkExternalApprovals::create() makes
     * them with no assignee and no reviewer rows, so this rule would leave only
     * the requester able to decide — the opposite of the separation of duties
     * those integrations enforce. They are gated by the provider's own
     * permission check in actionReviewDecision() instead.
     */
    public function canDecideReview(int $taskId, array $task): bool {
        $user = $this->wire('user');
        if ($user->isSuperuser()) return true;
        if ((new VerkExternalApprovals($this))->forTask($taskId)) return true;
        $uid = (int) $user->id;
        if ((int)($task['created_by'] ?? 0) === $uid) return true;
        if (isset($task['reviewer_ids'])) {
            return in_array($uid, array_map('intval', (array) $task['reviewer_ids']), true);
        }
        $stmt = $this->wire('database')->prepare(
            "SELECT 1 FROM vk_task_reviewers WHERE task_id = :tid AND user_id = :uid LIMIT 1"
        );
        $stmt->execute([':tid' => $taskId, ':uid' => $uid]);
        return (bool) $stmt->fetchColumn();
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

    /**
     * Self-contained people-picker: a native "add" dropdown + removable chip
     * rows, each chip carrying a hidden {$field}[] input. Used for reviewers and
     * collaborators. No third-party enhancement.
     */
    public function renderPeopleSelect(string $field, array $users, array $selected, string $addLabel, string $removeLabel): string {
        $selected = array_map('intval', $selected);
        $selectedSet = array_flip($selected);
        $esc = fn($s): string => htmlspecialchars((string) $s, ENT_QUOTES);

        $nameById = [];
        foreach ($users as $u) $nameById[(int) $u['id']] = (string) $u['name'];

        $options = '<option value="">' . $esc($addLabel . '…') . '</option>';
        foreach ($users as $u) {
            $id = (int) $u['id'];
            if (isset($selectedSet[$id])) continue;
            $options .= '<option value="' . $id . '">' . $esc($u['name']) . '</option>';
        }

        $chips = '';
        foreach ($selected as $id) {
            $chips .= $this->peopleChip($field, $id, $nameById[$id] ?? ('#' . $id), $removeLabel);
        }

        $out  = '<div class="vk-rev" data-rev data-field="' . $esc($field) . '" data-remove-label="' . $esc($removeLabel) . '">';
        $out .= '<select class="uk-select vk-rev-add" data-rev-add aria-label="' . $esc($addLabel) . '">' . $options . '</select>';
        $out .= '<div class="vk-rev-list" data-rev-list>' . $chips . '</div>';
        $out .= '</div>';
        $out .= $this->peopleWidgetScript();
        return $out;
    }

    /** Reviewers picker — thin wrapper over the shared people-picker. */
    public function renderReviewerSelect(array $users, array $selected): string {
        return $this->renderPeopleSelect('reviewer_ids', $users, $selected, $this->_('Add reviewer'), $this->_('Remove reviewer'));
    }

    /** One selected-person chip (server-rendered; mirrors the JS makeChip). */
    protected function peopleChip(string $field, int $id, string $name, string $removeLabel): string {
        $esc = fn($s): string => htmlspecialchars((string) $s, ENT_QUOTES);
        return '<span class="vk-rev-chip" data-id="' . $id . '">'
            . '<span class="vk-rev-name">' . $esc($name) . '</span>'
            . '<button type="button" class="vk-rev-remove" data-rev-remove aria-label="' . $esc($removeLabel) . '">&times;</button>'
            . '<input type="hidden" name="' . $esc($field) . '[]" value="' . $id . '">'
            . '</span>';
    }

    /** Inline script powering the people-picker widget (add/remove, no navigation). */
    protected function peopleWidgetScript(): string {
        return <<<'JS'
<script>
(function () {
    var root = document.currentScript.previousElementSibling;
    if (!root || !root.matches('[data-rev]')) return;
    var add = root.querySelector('[data-rev-add]');
    var list = root.querySelector('[data-rev-list]');
    var removeLabel = root.getAttribute('data-remove-label') || 'Remove';
    var field = root.getAttribute('data-field') || '';

    function makeChip(id, name) {
        var chip = document.createElement('span');
        chip.className = 'vk-rev-chip';
        chip.dataset.id = id;
        var n = document.createElement('span');
        n.className = 'vk-rev-name';
        n.textContent = name;
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'vk-rev-remove';
        btn.setAttribute('data-rev-remove', '');
        btn.setAttribute('aria-label', removeLabel);
        btn.textContent = '×';
        var hid = document.createElement('input');
        hid.type = 'hidden';
        hid.name = field + '[]';
        hid.value = id;
        chip.appendChild(n);
        chip.appendChild(btn);
        chip.appendChild(hid);
        return chip;
    }

    function reAddOption(id, name) {
        var opt = document.createElement('option');
        opt.value = id;
        opt.textContent = name;
        var rest = Array.prototype.slice.call(add.options, 1);
        var before = null;
        for (var i = 0; i < rest.length; i++) {
            if (rest[i].textContent.toLowerCase() > name.toLowerCase()) { before = rest[i]; break; }
        }
        add.insertBefore(opt, before);
    }

    add.addEventListener('change', function () {
        if (!add.value) return;
        var opt = add.options[add.selectedIndex];
        list.appendChild(makeChip(add.value, opt.textContent));
        opt.remove();
        add.value = '';
    });

    list.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-rev-remove]');
        if (!btn || !list.contains(btn)) return;
        var chip = btn.closest('.vk-rev-chip');
        if (!chip) return;
        reAddOption(chip.dataset.id, chip.querySelector('.vk-rev-name').textContent);
        chip.remove();
    });
})();
</script>
JS;
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

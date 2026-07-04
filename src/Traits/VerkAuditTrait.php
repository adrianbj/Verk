<?php namespace ProcessWire;

trait VerkAuditTrait {

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

        $rawUsers = $rule['users'] ?? [];
        if (is_string($rawUsers)) $rawUsers = explode(',', $rawUsers);
        $users = [];
        foreach ((array)$rawUsers as $name) {
            $name = strtolower(trim((string)$name));
            $name = preg_replace('/[^a-z0-9\-_.]/', '', $name) ?? '';
            if ($name !== '' && !in_array($name, $users, true)) $users[] = $name;
        }

        return [
            'label'    => trim((string)($rule['label'] ?? $this->_('Audit rule'))),
            'selector' => $selector ?: 'template!=admin',
            'field'    => preg_replace('/[^A-Za-z0-9_.*]/', '', $field) ?? '',
            'message'  => trim((string)($rule['message'] ?? $this->_('Field is empty'))),
            'users'    => $users,
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
            ] + $this->pageStatusFlags($p);
        }
        return ['pages' => $out, 'total' => count($out)];
    }

    /**
     * Audit gaps for a single page, evaluated in memory (no site-wide find).
     *
     * Mirrors the per-page test in runAuditRule() but matches the rule's scope
     * selector against just this page, so it is cheap enough to run on the
     * page-edit screen. Returns a list of ['label' => ..., 'message' => ...].
     */
    protected function getPageAuditGaps(Page $page): array {
        $gaps = [];
        foreach ($this->getAuditRules() as $rule) {
            $rule  = $this->normalizeAuditRule($rule);
            $field = $rule['field'];
            $root  = strtok($field, '.');
            if ($field !== '' && !$this->wire('fields')->get($root) && !in_array($root, ['id', 'name', 'title', 'url'], true)) {
                continue;
            }
            // Evaluate scope with the same selector engine the dashboard audit
            // uses (pages->find), restricted to just this page's id. Page::matches()
            // runs in memory and silently mishandles find-directives such as
            // include=, check_access= or status=, so it can report a page as out
            // of scope even when the site-wide audit lists it (e.g. unpublished
            // pages reached via include=). Scoping a real query to id keeps the
            // page-edit widget consistent with the Content Audit report.
            try {
                if (!$this->wire('pages')->count(trim($rule['selector']) . ", id={$page->id}")) continue;
            } catch (\Exception $e) {
                continue;
            }
            if ($field !== '') {
                if (!$this->pageHasAuditField($page, $root)) continue;
                if (!$this->auditValueIsEmpty($this->auditDotValue($page, $field))) continue;
            }
            $gaps[] = ['label' => $rule['label'], 'message' => $rule['message']];
        }
        return $gaps;
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

    /**
     * A rule with no users listed is global (visible to everyone). Otherwise it
     * is visible only to the named users. No superuser override — the dashboard
     * "My Content Audit" card stays personal, consistent with "My Tasks".
     */
    protected function auditRuleVisibleToUser(array $rule, string $userName): bool {
        $users = $rule['users'] ?? [];
        if (empty($users)) return true;
        return in_array(strtolower(trim($userName)), $users, true);
    }

    protected function getAuditSummary(bool $onlyMine = false): array {
        $rules    = $this->getAuditRules();
        $userName = $onlyMine ? (string)$this->wire('user')->name : '';
        $summary  = [];
        foreach ($rules as $i => $rule) {
            if ($onlyMine && !$this->auditRuleVisibleToUser($rule, $userName)) continue;
            $result = $this->runAuditRule($rule);
            $count = (int)($result['total'] ?? 0);
            $summary[] = ['label' => $rule['label'], 'count' => $count, 'index' => $i];
        }
        return $summary;
    }
}

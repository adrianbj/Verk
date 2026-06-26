<?php namespace ProcessWire;

class VerkNotify {

    public function __construct(protected Verk $module) {}

    /**
     * Notify users newly added to a task role. Consolidates multiple new roles
     * for the same user into one email. Never emails the actor.
     * $before/$after shape: ['assignee'=>int, 'reviewer'=>[int], 'collaborator'=>[int]]
     */
    public function membershipChanged(int $taskId, string $title, array $before, array $after, int $actorId): void {
        $roles = [
            'assignee'     => ['cfg' => 'notify_assignee',     'label' => 'Assignee'],
            'collaborator' => ['cfg' => 'notify_collaborator', 'label' => 'Collaborator'],
            'reviewer'     => ['cfg' => 'notify_reviewer',     'label' => 'Reviewer'],
        ];

        // userId => [role labels they were newly given on this save]
        $newRolesByUser = [];

        foreach ($roles as $role => $meta) {
            if (!$this->cfgOn($meta['cfg'])) continue;

            if ($role === 'assignee') {
                $newId = (int) ($after['assignee'] ?? 0);
                $oldId = (int) ($before['assignee'] ?? 0);
                $added = ($newId > 0 && $newId !== $oldId) ? [$newId] : [];
            } else {
                $afterIds  = array_map('intval', (array) ($after[$role] ?? []));
                $beforeIds = array_map('intval', (array) ($before[$role] ?? []));
                $added = array_values(array_diff($afterIds, $beforeIds));
            }

            foreach ($added as $uid) {
                if ($uid === $actorId) continue; // never notify the actor about their own change
                $newRolesByUser[$uid][] = $meta['label'];
            }
        }

        if (!$newRolesByUser) return;

        $base = $this->deskUrl();
        $taskUrl = $base . '?view=task-edit&id=' . $taskId;
        $actor = $this->actorName($actorId);

        foreach ($newRolesByUser as $uid => $labels) {
            $to = $this->recipient((int) $uid);
            if (!$to) continue;

            $rolesText = implode(', ', $labels);
            $subject = sprintf('[Verk] You\'ve been added to a task: "%s"', $title);
            $body = sprintf(
                "Hi %s,\n\n%s added you to a Verk task as: %s\n\nTask: %s\n\nOpen the task:\n%s\n",
                $to['name'] ?: 'there',
                $actor,
                $rolesText,
                $title,
                $taskUrl
            );
            $this->sendPlain($to['email'], $subject, $body);
        }
    }

    /** Send a single digest email to an assignee given N freshly bulk-created tasks. */
    public function bulkAssigned(int $assigneeId, int $count, int $actorId): void {
        if ($count < 1) return;
        if ($assigneeId === $actorId) return;        // actor assigned themselves
        if (!$this->cfgOn('notify_assignee')) return; // gated by master + assignee toggle

        $to = $this->recipient($assigneeId);
        if (!$to) return;

        $listUrl = $this->deskUrl() . '?view=tasks&assignee_id=' . $assigneeId;
        $actor = $this->actorName($actorId);

        $subject = sprintf('[Verk] You\'ve been assigned %d new task%s', $count, $count === 1 ? '' : 's');
        $body = sprintf(
            "Hi %s,\n\n%s assigned you %d new Verk task%s.\n\nView your tasks:\n%s\n",
            $to['name'] ?: 'there',
            $actor,
            $count,
            $count === 1 ? '' : 's',
            $listUrl
        );
        $this->sendPlain($to['email'], $subject, $body);
    }

    // ---- private helpers -------------------------------------------------

    /** Master toggle AND the per-role toggle must both be on. */
    protected function cfgOn(string $key): bool {
        $cfg = $this->module->getConfig();
        return !empty($cfg['notify_enabled']) && !empty($cfg[$key]);
    }

    /** Absolute URL of the Verk admin (desk) page, e.g. https://host/admin/verk/ */
    protected function deskUrl(): string {
        $desk = $this->module->wire('pages')->get('name=verk, template=admin');
        return $desk && $desk->id ? $desk->httpUrl : '';
    }

    /** Resolve a recipient's name+email, or null if the user has no usable email. */
    protected function recipient(int $userId): ?array {
        if ($userId < 1) return null;
        $u = $this->module->wire('users')->get($userId);
        if (!$u || !$u->id) return null;
        $email = trim((string) $u->email);
        if ($email === '') return null;
        return ['name' => (string) $u->name, 'email' => $email];
    }

    /** Display name for the actor (who made the change), falling back gracefully. */
    protected function actorName(int $actorId): string {
        $u = $this->module->wire('users')->get($actorId);
        return ($u && $u->id) ? (string) $u->name : 'Someone';
    }

    /** Send one plain-text email. Never throws; logs failures to verk-notify. */
    protected function sendPlain(string $email, string $subject, string $body): void {
        try {
            $mail = $this->module->wire('mail')->new();
            $mail->to($email);
            $mail->subject($subject);
            $mail->body($body); // plain text only — do not set bodyHTML
            $mail->send();
        } catch (\Throwable $e) {
            $this->module->wire('log')->save('verk-notify', 'sendPlain failed: ' . $e->getMessage());
        }
    }
}

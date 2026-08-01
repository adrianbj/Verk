<?php namespace ProcessWire;

/** Idempotent bridge between external approval records and Verk review tasks. */
final class VerkExternalApprovals {

    private Verk $verk;

    public function __construct(Verk $verk) {
        $this->verk = $verk;
    }

    public function create(string $provider, string $externalId, array $metadata, int $createdBy): array {
        $provider = strtolower(trim($provider));
        $externalId = strtolower(trim($externalId));
        if(!preg_match('/^[a-z][a-z0-9_.-]{1,31}$/', $provider)) throw new WireException('Invalid external approval provider.');
        if(!preg_match('/^[a-f0-9-]{16,64}$/', $externalId)) throw new WireException('Invalid external approval ID.');
        $existing = $this->find($provider, $externalId);
        if($existing) return $existing;
        $stale = $this->verk->wire('database')->prepare('DELETE FROM vk_external_approvals WHERE provider=:provider AND external_id=:external_id AND task_id NOT IN (SELECT id FROM vk_tasks)');
        $stale->execute([':provider' => $provider, ':external_id' => $externalId]);

        $host = strtolower(trim((string) ($metadata['host'] ?? '')));
        $folder = trim((string) ($metadata['folder'] ?? ''));
        $uid = max(0, (int) ($metadata['uid'] ?? 0));
        $accountId = max(0, (int) ($metadata['account_id'] ?? 0));
        if($host === '' || strlen($host) > 253 || !preg_match('/^[a-z0-9.-]+$/', $host)) throw new WireException('Invalid external approval host.');
        if($folder === '' || strlen($folder) > 255 || preg_match('/[<>\r\n\0]/', $folder) || $uid < 1 || $accountId < 1) throw new WireException('Invalid external approval metadata.');
        $user = $this->verk->wire('users')->get($createdBy);
        if(!$user || !$user->id) throw new WireException('External approval requester does not exist.');

        $db = $this->verk->wire('database');
        $db->beginTransaction();
        try {
            $task = $db->prepare("INSERT INTO vk_tasks (title, description, status, priority, page_id, section, created_by, created_at) VALUES (:title, :description, 'review', 'high', NULL, 'Mailbox approvals', :created_by, NOW())");
            $task->execute([
                ':title' => substr('Mailbox confirmation — ' . $host, 0, 255),
                ':description' => "External approval request\nProvider: {$provider}\nReference: {$externalId}\nAccount: {$accountId}\nFolder: {$folder}\nMessage UID: {$uid}\nHost: {$host}\n\nNo message body, URL path, query token, or mailbox credentials are stored in Verk.",
                ':created_by' => (int) $user->id,
            ]);
            $taskId = (int) $db->lastInsertId();
            $mapping = $db->prepare('INSERT INTO vk_external_approvals (provider, external_id, task_id, created_at) VALUES (:provider, :external_id, :task_id, NOW())');
            $mapping->execute([':provider' => $provider, ':external_id' => $externalId, ':task_id' => $taskId]);
            $db->commit();
        } catch(\Throwable $error) {
            if($db->inTransaction()) $db->rollBack();
            $existing = $this->find($provider, $externalId);
            if($existing) return $existing;
            throw $error;
        }
        return $this->find($provider, $externalId) ?: ['provider' => $provider, 'external_id' => $externalId, 'task_id' => $taskId, 'status' => 'review'];
    }

    public function find(string $provider, string $externalId): ?array {
        $stmt = $this->verk->wire('database')->prepare('SELECT a.provider, a.external_id, a.task_id, t.status FROM vk_external_approvals a JOIN vk_tasks t ON t.id=a.task_id WHERE a.provider=:provider AND a.external_id=:external_id LIMIT 1');
        $stmt->execute([':provider' => strtolower(trim($provider)), ':external_id' => strtolower(trim($externalId))]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? ['provider' => (string) $row['provider'], 'external_id' => (string) $row['external_id'], 'task_id' => (int) $row['task_id'], 'status' => (string) $row['status']] : null;
    }

    public function forTask(int $taskId): ?array {
        $stmt = $this->verk->wire('database')->prepare('SELECT provider, external_id, task_id FROM vk_external_approvals WHERE task_id=:task_id LIMIT 1');
        $stmt->execute([':task_id' => $taskId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? ['provider' => (string) $row['provider'], 'external_id' => (string) $row['external_id'], 'task_id' => (int) $row['task_id']] : null;
    }
}

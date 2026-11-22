<?php
declare(strict_types=1);


function parse_dt(string $v): ?string {
    $d = DateTime::createFromFormat('Y-m-d\TH:i', $v);
    return $d ? $d->format('Y-m-d H:i:s') : null;
}

function dt_input(string $v): string {
    return date('Y-m-d\TH:i', strtotime($v));
}

function find_election(int $id): ?array {
    $s = db()->prepare('SELECT * FROM elections WHERE id = ?');
    $s->execute([$id]);
    return $s->fetch() ?: null;
}

function find_position(int $id): ?array {
    $s = db()->prepare('SELECT * FROM positions WHERE id = ?');
    $s->execute([$id]);
    return $s->fetch() ?: null;
}

function find_candidate(int $id): ?array {
    $s = db()->prepare('SELECT * FROM candidates WHERE id = ?');
    $s->execute([$id]);
    return $s->fetch() ?: null;
}

// Ballot structure can only change while the election is a draft
function is_locked(?array $election): bool {
    return !$election || $election['status'] !== 'draft';
}

function db_error_message(PDOException $e): string {
    $code = $e->errorInfo[1] ?? 0;
    if ($code === 1451) {
        return 'Cannot delete this because votes already exist for it.';
    }
    error_log($e->getMessage());
    return config('debug') ? 'Database error: ' . $e->getMessage() : 'Something went wrong. Please try again.';
}

function status_badge(string $status): string {
    $cls = ['draft' => 'secondary', 'open' => 'success', 'closed' => 'dark'][$status] ?? 'light';
    return '<span class="badge text-bg-' . $cls . '">' . e($status) . '</span>';
}
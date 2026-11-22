<?php
declare(strict_types=1);


function open_election(): ?array {
    return db()->query("
        SELECT * FROM elections
        WHERE status = 'open' AND NOW() BETWEEN start_at AND end_at
        ORDER BY start_at DESC LIMIT 1
    ")->fetch() ?: null;
}

function ballot_positions(int $electionId): array {
    $stmt = db()->prepare('SELECT id, title FROM positions WHERE election_id = ? ORDER BY id');
    $stmt->execute([$electionId]);
    $positions = $stmt->fetchAll();

    $cstmt = db()->prepare('SELECT id, name, manifesto FROM candidates WHERE position_id = ? ORDER BY name');
    foreach ($positions as &$p) {
        $cstmt->execute([$p['id']]);
        $p['candidates'] = $cstmt->fetchAll();
    }
    unset($p);
    return $positions;
}

function voter_choices(int $voterId, int $electionId): array {
    $stmt = db()->prepare("
        SELECT p.title AS position_title, c.name AS candidate_name, v.voted_at
        FROM votes v
        JOIN positions p  ON p.id = v.position_id
        JOIN candidates c ON c.id = v.candidate_id
        WHERE v.voter_id = ? AND p.election_id = ?
        ORDER BY p.id
    ");
    $stmt->execute([$voterId, $electionId]);
    return $stmt->fetchAll();
}
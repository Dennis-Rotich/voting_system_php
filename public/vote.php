<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/voting_helpers.php';
require_role('voter');  

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('public/ballot.php');
}
csrf_check();

$voterId  = current_user()['id'];
$election = open_election();

if (!$election) {
    flash('error', 'Voting is not open right now.');
    redirect('public/ballot.php');
}
$electionId = (int)$election['id'];

$submitted = $_POST['vote'] ?? [];
if (!is_array($submitted)) {
    flash('error', 'Invalid ballot.');
    redirect('public/ballot.php');
}

$stmt = db()->prepare('SELECT id FROM positions WHERE election_id = ?');
$stmt->execute([$electionId]);
$validPositions = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

$choices = [];   
foreach ($submitted as $positionId => $candidateId) {
    if (!is_scalar($candidateId)) {
        flash('error', 'Invalid ballot.');
        redirect('public/ballot.php');
    }
    $positionId  = (int)$positionId;
    $candidateId = (int)$candidateId;

    if ($candidateId === 0) continue;   

    if (!in_array($positionId, $validPositions, true)) {
        flash('error', 'Invalid ballot. Please reload the page and try again.');
        redirect('public/ballot.php');
    }
    $choices[$positionId] = $candidateId;
}

if (!$choices) {
    flash('error', 'Select at least one candidate before submitting.');
    redirect('public/ballot.php');
}

$pdo = db();

try {
    $pdo->beginTransaction();

    $pdo->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE')->execute([$voterId]);

    $already = $pdo->prepare("
        SELECT COUNT(*) FROM votes v
        JOIN positions p ON p.id = v.position_id
        WHERE v.voter_id = ? AND p.election_id = ?
    ");
    $already->execute([$voterId, $electionId]);
    if ((int)$already->fetchColumn() > 0) {
        throw new RuntimeException('You have already voted in this election.');
    }

    $check  = $pdo->prepare('SELECT COUNT(*) FROM candidates WHERE id = ? AND position_id = ?');
    $insert = $pdo->prepare('INSERT INTO votes (voter_id, position_id, candidate_id) VALUES (?, ?, ?)');

    foreach ($choices as $positionId => $candidateId) {
        $check->execute([$candidateId, $positionId]);
        if ((int)$check->fetchColumn() !== 1) {
            throw new RuntimeException('Invalid selection. Please reload the ballot and try again.');
        }
        $insert->execute([$voterId, $positionId, $candidateId]);
    }

    $pdo->commit();
    flash('success', 'Your vote has been recorded. Thank you!');

} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash('error', $e->getMessage());

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    if (($e->errorInfo[1] ?? 0) === 1062) {

        flash('error', 'You have already voted in this election.');
    } else {
        error_log('Vote failed: ' . $e->getMessage());
        flash('error', 'Your vote could not be recorded and nothing was saved. Please try again.');
    }
}

redirect('public/ballot.php');
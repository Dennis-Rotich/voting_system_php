<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/voting_helpers.php';
require_role('voter');

$election  = open_election();
$voterId   = current_user()['id'];
$myVotes   = $election ? voter_choices($voterId, (int)$election['id']) : [];
$positions = ($election && !$myVotes) ? ballot_positions((int)$election['id']) : [];

$pageTitle = 'Ballot';
require __DIR__ . '/../includes/header.php';
?>

<?php if (!$election): ?>

  <div class="alert alert-info">There is no election open for voting right now. Please check back later.</div>

<?php elseif ($myVotes): ?>

  <h1 class="h3 mb-1"><?= e($election['title']) ?></h1>
  <p class="text-success">&#10003; Your vote has been recorded. Voting is final.</p>
  <div class="card">
    <div class="card-header">Your ballot</div>
    <ul class="list-group list-group-flush">
      <?php foreach ($myVotes as $v): ?>
        <li class="list-group-item d-flex justify-content-between">
          <span><?= e($v['position_title']) ?></span>
          <strong><?= e($v['candidate_name']) ?></strong>
        </li>
      <?php endforeach; ?>
    </ul>
    <div class="card-footer text-muted small">Recorded <?= e($myVotes[0]['voted_at']) ?></div>
  </div>
  <p class="text-muted small mt-3">Results will be published after the election closes.</p>

<?php else: ?>

  <h1 class="h3 mb-1"><?= e($election['title']) ?></h1>
  <p class="text-muted">Voting closes <?= e($election['end_at']) ?>. Choose one candidate per position, or leave a position blank. You cannot change your vote after submitting.</p>

  <form method="post" action="<?= e(url('public/vote.php')) ?>"
        onsubmit="return confirm('Submit your ballot? This is final.')">
    <?= csrf_field() ?>

    <?php foreach ($positions as $p): ?>
      <div class="card mb-3">
        <div class="card-header fw-semibold"><?= e($p['title']) ?></div>
        <div class="card-body">
          <?php foreach ($p['candidates'] as $c): ?>
            <div class="form-check mb-2">
              <input class="form-check-input" type="radio"
                     name="vote[<?= (int)$p['id'] ?>]" id="c<?= (int)$c['id'] ?>"
                     value="<?= (int)$c['id'] ?>">
              <label class="form-check-label" for="c<?= (int)$c['id'] ?>"><?= e($c['name']) ?></label>
              <?php if (!empty($c['manifesto'])): ?>
                <details class="small text-muted ms-1"><summary>Manifesto</summary><?= nl2br(e($c['manifesto'])) ?></details>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
          <div class="form-check text-muted">
            <input class="form-check-input" type="radio"
                   name="vote[<?= (int)$p['id'] ?>]" id="blank<?= (int)$p['id'] ?>" value="0" checked>
            <label class="form-check-label" for="blank<?= (int)$p['id'] ?>">No selection</label>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

    <button class="btn btn-primary btn-lg w-100">Submit ballot</button>
  </form>

<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
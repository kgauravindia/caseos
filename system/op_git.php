<?php 
require_once('all_header.php'); 

// Access Control: Developer role only
if (strtoupper($user_type ?? '') !== 'DEV') {
    echo "<main class='content'><div class='container-fluid p-4'><div class='alert alert-danger shadow-sm'>
            <h4 class='alert-heading mb-1'><i class='align-middle me-2' data-feather='alert-octagon'></i>Access Denied</h4>
            <p class='mb-0'>This module is strictly restricted to Developer accounts.</p>
          </div></div></main>";
    require_once('footer.php');
    exit;
}

$repo_dir = dirname(__DIR__);

// Helper to run git command safely
function execute_git($cmd_args, $dir) {
    $command = 'cd /d ' . escapeshellarg($dir) . ' && git ' . $cmd_args . ' 2>&1';
    $output = shell_exec($command);
    return trim($output ?? '');
}

$action_result = null;
$executed_cmd = null;
$action_status = 'info';

// Determine current branch
$current_branch = execute_git('rev-parse --abbrev-ref HEAD', $repo_dir);
if (empty($current_branch) || strpos($current_branch, 'fatal') !== false) {
    $current_branch = 'main';
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['git_action'])) {
    $action = $_POST['git_action'];
    $target_branch = !empty($_POST['branch']) ? preg_replace('/[^a-zA-Z0-9_\-\.\/]/', '', $_POST['branch']) : $current_branch;

    if ($action === 'fetch') {
        $executed_cmd = "git fetch origin";
        $action_result = execute_git("fetch origin", $repo_dir);
        if ($action_result === '') {
            $action_result = "Fetch completed successfully. Remote references are up to date.";
        }
        $action_status = 'success';
    } 
    elseif ($action === 'pull') {
        $executed_cmd = "git pull origin " . $target_branch;
        $action_result = execute_git("pull origin " . escapeshellarg($target_branch), $repo_dir);
        $action_status = (strpos($action_result, 'fatal:') !== false || strpos($action_result, 'error:') !== false || strpos($action_result, 'CONFLICT') !== false) ? 'danger' : 'success';
    } 
    elseif ($action === 'push') {
        $executed_cmd = "git push origin " . $target_branch;
        $action_result = execute_git("push origin " . escapeshellarg($target_branch), $repo_dir);
        $action_status = (strpos($action_result, 'fatal:') !== false || strpos($action_result, 'error:') !== false) ? 'danger' : 'success';
    } 
    elseif ($action === 'commit_push') {
        $commit_msg = trim($_POST['commit_message'] ?? '');
        if (empty($commit_msg)) {
            $action_result = "Error: Commit message cannot be empty.";
            $action_status = 'danger';
        } else {
            $executed_cmd = "git add -A && git commit -m " . escapeshellarg($commit_msg) . " && git push origin " . $target_branch;
            $add_res = execute_git("add -A", $repo_dir);
            $commit_res = execute_git("commit -m " . escapeshellarg($commit_msg), $repo_dir);
            $push_res = execute_git("push origin " . escapeshellarg($target_branch), $repo_dir);
            $action_result = "--- STAGE & COMMIT ---\n" . ($commit_res ?: "Nothing to commit or stage completed.") . "\n\n--- PUSH ---\n" . ($push_res ?: "Push completed.");
            $action_status = (strpos($action_result, 'fatal:') !== false || strpos($action_result, 'error:') !== false) ? 'danger' : 'success';
        }
    } 
    elseif ($action === 'stash') {
        $executed_cmd = "git stash";
        $action_result = execute_git("stash", $repo_dir);
        $action_status = 'info';
    } 
    elseif ($action === 'stash_pop') {
        $executed_cmd = "git stash pop";
        $action_result = execute_git("stash pop", $repo_dir);
        $action_status = (strpos($action_result, 'CONFLICT') !== false || strpos($action_result, 'error:') !== false) ? 'danger' : 'success';
    }
}

// Fetch fresh status details
$remote_url = execute_git('remote get-url origin', $repo_dir);
$status_short = execute_git('status --short', $repo_dir);
$modified_files = array_filter(explode("\n", str_replace("\r", "", $status_short)));
$uncommitted_count = count($modified_files);

// Ahead / Behind counts against tracking branch
$ahead_count = execute_git("rev-list --count origin/{$current_branch}..HEAD", $repo_dir);
$behind_count = execute_git("rev-list --count HEAD..origin/{$current_branch}", $repo_dir);
if (!is_numeric($ahead_count)) { $ahead_count = 0; }
if (!is_numeric($behind_count)) { $behind_count = 0; }

// Latest Commit
$latest_commit = execute_git('log -1 --pretty=format:"%h - %s (%cr) <%an>"', $repo_dir);

// Recent Commits (Last 8)
$log_raw = execute_git('log -n 8 --pretty=format:"%h|%s|%an|%cr|%cd"', $repo_dir);
$commits = [];
if (!empty($log_raw)) {
    foreach (explode("\n", str_replace("\r", "", $log_raw)) as $line) {
        $parts = explode("|", $line, 5);
        if (count($parts) >= 4) {
            $commits[] = [
                'hash' => $parts[0],
                'subject' => $parts[1],
                'author' => $parts[2],
                'time_ago' => $parts[3],
                'date' => $parts[4] ?? ''
            ];
        }
    }
}
?>

<main class="content">
    <div class="container-fluid p-0">

        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h1 class="h3 mb-1 text-dark fw-bold">
                    <i class="align-middle me-2 text-primary" data-feather="git-pull-request"></i>Git Sync & Deployment
                </h1>
                <p class="text-muted mb-0 small">Manage version control synchronization, pull updates, and push codebase changes to remote repository.</p>
            </div>
            <div>
                <form method="post" class="d-inline">
                    <input type="hidden" name="git_action" value="fetch">
                    <button type="submit" class="btn btn-outline-secondary btn-sm shadow-sm" title="Fetch latest refs without changing working tree">
                        <i class="align-middle me-1" data-feather="refresh-cw"></i> Fetch Remote
                    </button>
                </form>
            </div>
        </div>

        <!-- Terminal Output Alert (if action performed) -->
        <?php if ($action_result !== null): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-<?= $action_status ?> bg-opacity-10 py-2 d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-<?= $action_status ?>">
                        <i class="align-middle me-1" data-feather="<?= $action_status === 'success' ? 'check-circle' : ($action_status === 'danger' ? 'alert-circle' : 'info') ?>"></i>
                        Command Output: <code class="text-dark"><?= htmlspecialchars($executed_cmd ?? '') ?></code>
                    </span>
                    <span class="badge bg-<?= $action_status ?> text-uppercase"><?= $action_status ?></span>
                </div>
                <div class="card-body bg-dark text-light p-3 rounded-bottom font-monospace" style="font-size: 0.85rem; max-height: 280px; overflow-y: auto;">
                    <pre class="mb-0 text-light" style="white-space: pre-wrap; word-break: break-all;"><?= htmlspecialchars($action_result) ?></pre>
                </div>
            </div>
        <?php endif; ?>

        <!-- Repository Status Metrics -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="row">
                            <div class="col mt-0">
                                <h5 class="card-title text-muted text-uppercase mb-2" style="font-size: 0.75rem;">Current Branch</h5>
                            </div>
                            <div class="col-auto">
                                <div class="stat text-primary bg-primary bg-opacity-10 p-2 rounded-3">
                                    <i class="align-middle" data-feather="git-branch"></i>
                                </div>
                            </div>
                        </div>
                        <h3 class="mt-1 mb-1 fw-bold text-dark"><?= htmlspecialchars($current_branch) ?></h3>
                        <div class="small text-muted text-truncate" title="<?= htmlspecialchars($remote_url) ?>">
                            <i class="align-middle me-1" data-feather="globe" style="width: 14px; height: 14px;"></i> <?= htmlspecialchars(basename($remote_url, '.git')) ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="row">
                            <div class="col mt-0">
                                <h5 class="card-title text-muted text-uppercase mb-2" style="font-size: 0.75rem;">Ahead (Unpushed)</h5>
                            </div>
                            <div class="col-auto">
                                <div class="stat text-warning bg-warning bg-opacity-10 p-2 rounded-3">
                                    <i class="align-middle" data-feather="arrow-up-circle"></i>
                                </div>
                            </div>
                        </div>
                        <h3 class="mt-1 mb-1 fw-bold <?= $ahead_count > 0 ? 'text-warning' : 'text-dark' ?>"><?= $ahead_count ?></h3>
                        <div class="small">
                            <?php if ($ahead_count > 0): ?>
                                <span class="badge bg-warning text-dark"><i class="align-middle me-1" data-feather="upload-cloud" style="width: 12px; height: 12px;"></i> Ready to Push</span>
                            <?php else: ?>
                                <span class="text-success"><i class="align-middle me-1" data-feather="check" style="width: 14px; height: 14px;"></i> Remote in sync</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="row">
                            <div class="col mt-0">
                                <h5 class="card-title text-muted text-uppercase mb-2" style="font-size: 0.75rem;">Behind (Incoming)</h5>
                            </div>
                            <div class="col-auto">
                                <div class="stat text-info bg-info bg-opacity-10 p-2 rounded-3">
                                    <i class="align-middle" data-feather="arrow-down-circle"></i>
                                </div>
                            </div>
                        </div>
                        <h3 class="mt-1 mb-1 fw-bold <?= $behind_count > 0 ? 'text-danger' : 'text-dark' ?>"><?= $behind_count ?></h3>
                        <div class="small">
                            <?php if ($behind_count > 0): ?>
                                <span class="badge bg-danger"><i class="align-middle me-1" data-feather="download-cloud" style="width: 12px; height: 12px;"></i> Pull available</span>
                            <?php else: ?>
                                <span class="text-success"><i class="align-middle me-1" data-feather="check" style="width: 14px; height: 14px;"></i> Up to date</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="row">
                            <div class="col mt-0">
                                <h5 class="card-title text-muted text-uppercase mb-2" style="font-size: 0.75rem;">Working Tree</h5>
                            </div>
                            <div class="col-auto">
                                <div class="stat text-success bg-success bg-opacity-10 p-2 rounded-3">
                                    <i class="align-middle" data-feather="file-text"></i>
                                </div>
                            </div>
                        </div>
                        <h3 class="mt-1 mb-1 fw-bold text-dark"><?= $uncommitted_count ?></h3>
                        <div class="small">
                            <?php if ($uncommitted_count > 0): ?>
                                <span class="badge bg-danger-soft text-danger border border-danger border-opacity-25"><?= $uncommitted_count ?> file(s) modified/untracked</span>
                            <?php else: ?>
                                <span class="text-success"><i class="align-middle me-1" data-feather="check-circle" style="width: 14px; height: 14px;"></i> Clean working tree</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Operations -->
            <div class="col-lg-6">
                <!-- Git Pull & Push Quick Actions -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            <i class="align-middle me-2 text-primary" data-feather="zap"></i>Quick Sync Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Git Pull Box -->
                            <div class="col-12 col-md-6">
                                <div class="p-3 border rounded-3 bg-light bg-opacity-50 h-100 d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="bg-primary text-white p-2 rounded-circle me-2 d-inline-flex">
                                                <i class="align-middle" data-feather="download" style="width: 16px; height: 16px;"></i>
                                            </div>
                                            <h6 class="fw-bold mb-0">Git Pull</h6>
                                        </div>
                                        <p class="text-muted small mb-3">Fetch and merge remote updates from GitHub into your local <code><?= htmlspecialchars($current_branch) ?></code> branch.</p>
                                    </div>
                                    <form method="post" onsubmit="return confirm('Execute git pull origin <?= htmlspecialchars($current_branch) ?>?');">
                                        <input type="hidden" name="git_action" value="pull">
                                        <input type="hidden" name="branch" value="<?= htmlspecialchars($current_branch) ?>">
                                        <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm">
                                            <i class="align-middle me-1" data-feather="download-cloud"></i> Pull from Origin
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Git Push Box -->
                            <div class="col-12 col-md-6">
                                <div class="p-3 border rounded-3 bg-light bg-opacity-50 h-100 d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="bg-success text-white p-2 rounded-circle me-2 d-inline-flex">
                                                <i class="align-middle" data-feather="upload" style="width: 16px; height: 16px;"></i>
                                            </div>
                                            <h6 class="fw-bold mb-0">Git Push</h6>
                                        </div>
                                        <p class="text-muted small mb-3">Publish your local commits directly to the remote repository on branch <code><?= htmlspecialchars($current_branch) ?></code>.</p>
                                    </div>
                                    <form method="post" onsubmit="return confirm('Execute git push origin <?= htmlspecialchars($current_branch) ?>?');">
                                        <input type="hidden" name="git_action" value="push">
                                        <input type="hidden" name="branch" value="<?= htmlspecialchars($current_branch) ?>">
                                        <button type="submit" class="btn btn-success w-100 fw-bold shadow-sm">
                                            <i class="align-middle me-1" data-feather="upload-cloud"></i> Push to Origin
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Stash controls (optional) -->
                        <div class="mt-3 pt-3 border-top d-flex gap-2">
                            <form method="post" class="d-inline" onsubmit="return confirm('Stash current uncommitted changes?');">
                                <input type="hidden" name="git_action" value="stash">
                                <button type="submit" class="btn btn-outline-warning btn-sm" title="Temporarily shelve uncommitted changes">
                                    <i class="align-middle me-1" data-feather="archive"></i> Git Stash
                                </button>
                            </form>
                            <form method="post" class="d-inline" onsubmit="return confirm('Apply latest stashed changes back?');">
                                <input type="hidden" name="git_action" value="stash_pop">
                                <button type="submit" class="btn btn-outline-secondary btn-sm" title="Restore stashed changes">
                                    <i class="align-middle me-1" data-feather="inbox"></i> Stash Pop
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Stage, Commit & Push in One Step -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            <i class="align-middle me-2 text-info" data-feather="check-square"></i>Stage, Commit & Push
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="post" onsubmit="return confirm('Stage all changes, commit and push to remote?');">
                            <input type="hidden" name="git_action" value="commit_push">
                            <input type="hidden" name="branch" value="<?= htmlspecialchars($current_branch) ?>">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted">Commit Message</label>
                                <textarea name="commit_message" class="form-control" rows="2" placeholder="e.g. Update dashboard widgets and fix bug in billing module" required></textarea>
                                <div class="form-text small">This runs <code>git add -A</code>, commits with your message, and immediately runs <code>git push origin <?= htmlspecialchars($current_branch) ?></code>.</div>
                            </div>

                            <button type="submit" class="btn btn-info text-white fw-bold shadow-sm" <?= $uncommitted_count === 0 && $ahead_count === 0 ? 'title="No modified files"' : '' ?>>
                                <i class="align-middle me-1" data-feather="send"></i> Commit & Push All
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Uncommitted / Modified Files List -->
                <?php if (!empty($modified_files)): ?>
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0 fw-bold text-danger">
                                <i class="align-middle me-2" data-feather="edit-3"></i>Modified & Untracked Files (<?= count($modified_files) ?>)
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush" style="max-height: 250px; overflow-y: auto;">
                                <?php foreach ($modified_files as $f): 
                                    $f = trim($f);
                                    if (empty($f)) continue;
                                    $status_code = substr($f, 0, 2);
                                    $file_path = substr($f, 2);
                                ?>
                                    <div class="list-group-item d-flex align-items-center justify-content-between py-2 px-3">
                                        <code class="text-dark small"><?= htmlspecialchars($file_path) ?></code>
                                        <span class="badge bg-secondary font-monospace"><?= htmlspecialchars($status_code) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Column: Repository Details & Recent History -->
            <div class="col-lg-6">
                <!-- Repo Information -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            <i class="align-middle me-2 text-secondary" data-feather="info"></i>Repository Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <th class="text-muted ps-0" style="width: 140px;">Remote Origin:</th>
                                    <td>
                                        <a href="<?= htmlspecialchars($remote_url) ?>" target="_blank" class="text-decoration-none fw-semibold">
                                            <?= htmlspecialchars($remote_url) ?> <i class="align-middle ms-1" data-feather="external-link" style="width: 12px; height: 12px;"></i>
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-muted ps-0">Root Path:</th>
                                    <td><code><?= htmlspecialchars($repo_dir) ?></code></td>
                                </tr>
                                <tr>
                                    <th class="text-muted ps-0">Latest Commit:</th>
                                    <td><small class="text-dark"><?= htmlspecialchars($latest_commit ?: 'None') ?></small></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Commits Log -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            <i class="align-middle me-2 text-primary" data-feather="clock"></i>Recent Commit History
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 430px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="font-size: 0.75rem;">HASH</th>
                                        <th style="font-size: 0.75rem;">MESSAGE</th>
                                        <th style="font-size: 0.75rem;">AUTHOR</th>
                                        <th style="font-size: 0.75rem;">TIME</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($commits)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-3">No commit records found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($commits as $c): ?>
                                            <tr>
                                                <td><span class="badge bg-light text-primary border font-monospace"><?= htmlspecialchars($c['hash']) ?></span></td>
                                                <td class="fw-semibold text-dark small text-truncate" style="max-width: 250px;" title="<?= htmlspecialchars($c['subject']) ?>">
                                                    <?= htmlspecialchars($c['subject']) ?>
                                                </td>
                                                <td class="text-muted small"><?= htmlspecialchars($c['author']) ?></td>
                                                <td class="text-muted small text-nowrap"><?= htmlspecialchars($c['time_ago']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<?php require_once('footer.php'); ?>

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

// Check if a PHP function is callable and not disabled in php.ini
function is_exec_available($func) {
    if (!function_exists($func)) {
        return false;
    }
    $disabled = ini_get('disable_functions');
    if (!empty($disabled)) {
        $disabled_arr = array_map('trim', explode(',', strtolower($disabled)));
        if (in_array(strtolower($func), $disabled_arr, true)) {
            return false;
        }
    }
    return true;
}

// Detect which execution function is available
$available_exec = null;
foreach (['shell_exec', 'exec', 'proc_open', 'system', 'passthru'] as $fn) {
    if (is_exec_available($fn)) {
        $available_exec = $fn;
        break;
    }
}
$cli_enabled = ($available_exec !== null);

// Helper to run git command safely across Windows & Linux
function execute_git($cmd_args, $dir) {
    global $available_exec;
    if (!$available_exec) {
        return false;
    }

    // Suppress SSH host key verification prompts and failures on Linux/Hostinger servers
    $ssh_opts = '-c core.sshCommand="ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null"';
    $git_cmd = 'git -C ' . escapeshellarg($dir) . ' ' . $ssh_opts . ' ' . $cmd_args . ' 2>&1';

    // 1. Try shell_exec
    if (is_exec_available('shell_exec')) {
        $out = @shell_exec($git_cmd);
        if ($out !== null) {
            return trim($out);
        }
    }

    // 2. Try exec
    if (is_exec_available('exec')) {
        $output_lines = [];
        @exec($git_cmd, $output_lines);
        if (!empty($output_lines)) {
            return trim(implode("\n", $output_lines));
        }
    }

    // 3. Try proc_open
    if (is_exec_available('proc_open')) {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        $process = @proc_open($git_cmd, $descriptors, $pipes, $dir);
        if (is_resource($process)) {
            fclose($pipes[0]);
            $stdout = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            proc_close($process);
            $combined = trim($stdout . ($stderr ? "\n" . $stderr : ''));
            if ($combined !== '') {
                return $combined;
            }
        }
    }

    // 4. Try system with output buffering
    if (is_exec_available('system')) {
        ob_start();
        @system($git_cmd);
        $out = ob_get_clean();
        if (!empty($out)) {
            return trim($out);
        }
    }

    // 5. Try passthru with output buffering
    if (is_exec_available('passthru')) {
        ob_start();
        @passthru($git_cmd);
        $out = ob_get_clean();
        if (!empty($out)) {
            return trim($out);
        }
    }

    return false;
}

$action_result = null;
$executed_cmd = null;
$action_status = 'info';

// Determine current branch (with filesystem fallback if CLI is disabled)
$current_branch = 'main';
if ($cli_enabled) {
    $branch_out = execute_git('rev-parse --abbrev-ref HEAD', $repo_dir);
    if (!empty($branch_out) && strpos($branch_out, 'fatal') === false) {
        $current_branch = $branch_out;
    }
} else {
    // Read directly from .git/HEAD
    $head_file = $repo_dir . '/.git/HEAD';
    if (is_file($head_file)) {
        $head_content = trim(file_get_contents($head_file));
        if (strpos($head_content, 'ref: refs/heads/') === 0) {
            $current_branch = trim(str_replace('ref: refs/heads/', '', $head_content));
        }
    }
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['git_action'])) {
    if (!$cli_enabled) {
        $action_result = "Cannot execute Git command: Shell execution functions (shell_exec, exec, proc_open) are disabled by your hosting provider in php.ini (disable_functions). Please enable them in your hosting control panel.";
        $action_status = 'danger';
        $executed_cmd = "git " . htmlspecialchars($_POST['git_action']);
    } else {
        $action = $_POST['git_action'];
        $target_branch = !empty($_POST['branch']) ? preg_replace('/[^a-zA-Z0-9_\-\.\/]/', '', $_POST['branch']) : $current_branch;

        if ($action === 'fetch') {
            $executed_cmd = "git fetch origin";
            $res = execute_git("fetch origin", $repo_dir);
            $action_result = ($res === '' || $res === false) ? "Fetch completed successfully. Remote references are up to date." : $res;
            $action_status = 'success';
        } 
        elseif ($action === 'pull') {
            $executed_cmd = "git pull origin " . $target_branch;
            $action_result = execute_git("pull origin " . escapeshellarg($target_branch), $repo_dir);
            if ($action_result === false) {
                $action_result = "Execution failed. Check server permissions or git installation.";
                $action_status = 'danger';
            } else {
                $action_status = (strpos($action_result, 'fatal:') !== false || strpos($action_result, 'error:') !== false || strpos($action_result, 'CONFLICT') !== false) ? 'danger' : 'success';
            }
        } 
        elseif ($action === 'push') {
            $executed_cmd = "git push origin " . $target_branch;
            $action_result = execute_git("push origin " . escapeshellarg($target_branch), $repo_dir);
            if ($action_result === false) {
                $action_result = "Execution failed. Check server permissions or git credentials.";
                $action_status = 'danger';
            } else {
                $action_status = (strpos($action_result, 'fatal:') !== false || strpos($action_result, 'error:') !== false) ? 'danger' : 'success';
            }
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
        elseif ($action === 'set_remote') {
            $new_remote = trim($_POST['remote_url'] ?? '');
            if (!empty($new_remote)) {
                $executed_cmd = "git remote set-url origin " . $new_remote;
                $res = execute_git("remote set-url origin " . escapeshellarg($new_remote), $repo_dir);
                $action_result = ($res === '' || $res === false) ? "Remote URL successfully updated to: " . $new_remote : $res;
                $action_status = 'success';
            }
        }
    }
}

// Fetch status details
$remote_url = '';
$status_short = '';
$modified_files = [];
$uncommitted_count = 0;
$ahead_count = 0;
$behind_count = 0;
$latest_commit = 'None';
$commits = [];

if ($cli_enabled) {
    $remote_url = execute_git('remote get-url origin', $repo_dir) ?: '';
    $status_short = execute_git('status --short', $repo_dir) ?: '';
    $modified_files = array_filter(explode("\n", str_replace("\r", "", $status_short)));
    $uncommitted_count = count($modified_files);

    $ahead_raw = execute_git("rev-list --count origin/{$current_branch}..HEAD", $repo_dir);
    $behind_raw = execute_git("rev-list --count HEAD..origin/{$current_branch}", $repo_dir);
    $ahead_count = is_numeric($ahead_raw) ? (int)$ahead_raw : 0;
    $behind_count = is_numeric($behind_raw) ? (int)$behind_raw : 0;

    $latest_commit = execute_git('log -1 --pretty=format:"%h - %s (%cr) <%an>"', $repo_dir) ?: 'None';

    $log_raw = execute_git('log -n 8 --pretty=format:"%h|%s|%an|%cr|%cd"', $repo_dir);
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
} else {
    // Filesystem parse of .git/config for remote origin
    $config_file = $repo_dir . '/.git/config';
    if (is_file($config_file)) {
        $ini_content = file_get_contents($config_file);
        if (preg_match('/\[remote\s+"origin"\][^\[]*url\s*=\s*([^\r\n]+)/', $ini_content, $m)) {
            $remote_url = trim($m[1]);
        }
    }
    // Filesystem parse for latest commit hash
    $branch_ref_file = $repo_dir . '/.git/refs/heads/' . $current_branch;
    if (is_file($branch_ref_file)) {
        $hash = trim(file_get_contents($branch_ref_file));
        $latest_commit = substr($hash, 0, 7) . ' (retrieved from git ref)';
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
                <?php if ($cli_enabled): ?>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="git_action" value="fetch">
                        <button type="submit" class="btn btn-outline-secondary btn-sm shadow-sm" title="Fetch latest refs without changing working tree">
                            <i class="align-middle me-1" data-feather="refresh-cw"></i> Fetch Remote
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Hosting Security Advisory Notice if shell execution functions are disabled -->
        <?php if (!$cli_enabled): ?>
            <div class="alert alert-warning border-0 shadow-sm mb-4">
                <div class="d-flex align-items-start">
                    <div class="me-3 mt-1">
                        <i class="align-middle text-warning" data-feather="alert-triangle" style="width: 24px; height: 24px;"></i>
                    </div>
                    <div>
                        <h5 class="alert-heading fw-bold mb-1">PHP Shell Execution is Disabled by Hosting Provider</h5>
                        <p class="mb-2 small">Your server configuration (<code>disable_functions</code> in <code>php.ini</code>) does not allow PHP to run shell commands (<code>shell_exec</code>, <code>exec</code>, or <code>proc_open</code>). Read-only repository details are loaded from the filesystem, but interactive Git commands (Pull/Push) require enabling execution functions.</p>
                        
                        <div class="bg-white bg-opacity-75 p-3 rounded-3 small">
                            <strong class="d-block mb-1 text-dark"><i class="align-middle me-1" data-feather="tool" style="width: 14px; height: 14px;"></i>How to Enable Git Pull/Push in Hostinger / cPanel:</strong>
                            <ol class="mb-0 ps-3">
                                <li>Log in to your hosting panel (e.g., <strong>Hostinger hPanel</strong>).</li>
                                <li>Navigate to <strong>Websites &rarr; Manage &rarr; Advanced &rarr; PHP Configuration</strong>.</li>
                                <li>Click on the <strong>PHP Functions</strong> tab.</li>
                                <li>Under <strong>Disable functions</strong>, remove <code>shell_exec</code> and <code>exec</code> from the list, then click <strong>Save</strong>.</li>
                                <li>Refresh this page, and your one-click Git Pull & Push buttons will be fully active.</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- SSH Remote Advisory Notice if SSH is detected -->
        <?php 
        $is_ssh_remote = !empty($remote_url) && (strpos($remote_url, 'git@') === 0 || strpos($remote_url, 'ssh://') === 0);
        ?>
        <?php if ($is_ssh_remote): ?>
            <div class="alert alert-info border-0 shadow-sm mb-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h5 class="alert-heading fw-bold mb-1 text-dark"><i class="align-middle me-2 text-primary" data-feather="key"></i>SSH Remote Detected</h5>
                        <p class="mb-0 small text-muted">Your server repository is configured with an SSH remote (<code><?= htmlspecialchars($remote_url) ?></code>). Since this repository is public, switching to <strong>HTTPS</strong> resolves <em>"Host key verification failed"</em> and allows 1-click pulls without requiring server SSH keys.</p>
                    </div>
                    <form method="post" class="m-0">
                        <input type="hidden" name="git_action" value="set_remote">
                        <input type="hidden" name="remote_url" value="https://github.com/kgauravindia/caseos.git">
                        <button type="submit" class="btn btn-primary btn-sm shadow-sm fw-bold">
                            <i class="align-middle me-1" data-feather="repeat"></i> Switch to HTTPS (Fix Host Key)
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Command Execution Output (if action performed) -->
        <?php if ($action_result !== null): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-<?= $action_status ?> bg-opacity-10 py-2 d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-<?= $action_status ?>">
                        <i class="align-middle me-1" data-feather="<?= $action_status === 'success' ? 'check-circle' : ($action_status === 'danger' ? 'alert-circle' : 'info') ?>"></i>
                        Command Output: <code class="text-dark"><?= htmlspecialchars($executed_cmd ?? '') ?></code>
                    </span>
                    <span class="badge bg-<?= $action_status ?> text-uppercase"><?= $action_status ?></span>
                </div>
                <div class="card-body bg-dark text-light p-3 <?= ($action_status === 'danger' && (strpos($action_result, 'Host key verification failed') !== false || strpos($action_result, 'Could not read from remote repository') !== false)) ? '' : 'rounded-bottom' ?> font-monospace" style="font-size: 0.85rem; max-height: 280px; overflow-y: auto;">
                    <pre class="mb-0 text-light" style="white-space: pre-wrap; word-break: break-all;"><?= htmlspecialchars($action_result) ?></pre>
                </div>
                <?php if ($action_status === 'danger' && (strpos($action_result, 'Host key verification failed') !== false || strpos($action_result, 'Could not read from remote repository') !== false)): ?>
                    <div class="card-footer bg-danger bg-opacity-10 py-2 px-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <span class="small text-danger fw-semibold">
                            <i class="align-middle me-1" data-feather="alert-circle"></i> Host key verification failed. Switch the remote URL to HTTPS to pull without SSH authentication.
                        </span>
                        <form method="post" class="m-0">
                            <input type="hidden" name="git_action" value="set_remote">
                            <input type="hidden" name="remote_url" value="https://github.com/kgauravindia/caseos.git">
                            <button type="submit" class="btn btn-sm btn-danger fw-bold shadow-sm">
                                <i class="align-middle me-1" data-feather="repeat"></i> Fix Now: Switch to HTTPS
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
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
                            <i class="align-middle me-1" data-feather="globe" style="width: 14px; height: 14px;"></i> <?= htmlspecialchars(basename($remote_url, '.git') ?: 'local') ?>
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
                                <span class="badge bg-danger-soft text-danger border border-danger border-opacity-25"><?= $uncommitted_count ?> file(s) modified</span>
                            <?php else: ?>
                                <span class="text-success"><i class="align-middle me-1" data-feather="check-circle" style="width: 14px; height: 14px;"></i> Clean</span>
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
                                        <p class="text-muted small mb-3">Fetch and merge remote updates from GitHub into local <code><?= htmlspecialchars($current_branch) ?></code> branch.</p>
                                    </div>
                                    <form method="post" onsubmit="return confirm('Execute git pull origin <?= htmlspecialchars($current_branch) ?>?');">
                                        <input type="hidden" name="git_action" value="pull">
                                        <input type="hidden" name="branch" value="<?= htmlspecialchars($current_branch) ?>">
                                        <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm" <?= !$cli_enabled ? 'disabled title="Shell execution disabled"' : '' ?>>
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
                                        <button type="submit" class="btn btn-success w-100 fw-bold shadow-sm" <?= !$cli_enabled ? 'disabled title="Shell execution disabled"' : '' ?>>
                                            <i class="align-middle me-1" data-feather="upload-cloud"></i> Push to Origin
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Stash controls -->
                        <div class="mt-3 pt-3 border-top d-flex gap-2">
                            <form method="post" class="d-inline" onsubmit="return confirm('Stash current uncommitted changes?');">
                                <input type="hidden" name="git_action" value="stash">
                                <button type="submit" class="btn btn-outline-warning btn-sm" <?= !$cli_enabled ? 'disabled' : '' ?> title="Temporarily shelve uncommitted changes">
                                    <i class="align-middle me-1" data-feather="archive"></i> Git Stash
                                </button>
                            </form>
                            <form method="post" class="d-inline" onsubmit="return confirm('Apply latest stashed changes back?');">
                                <input type="hidden" name="git_action" value="stash_pop">
                                <button type="submit" class="btn btn-outline-secondary btn-sm" <?= !$cli_enabled ? 'disabled' : '' ?> title="Restore stashed changes">
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
                                <textarea name="commit_message" class="form-control" rows="2" placeholder="e.g. Update dashboard widgets and fix bug in billing module" required <?= !$cli_enabled ? 'disabled' : '' ?>></textarea>
                                <div class="form-text small">This runs <code>git add -A</code>, commits with your message, and immediately pushes to <code>origin <?= htmlspecialchars($current_branch) ?></code>.</div>
                            </div>

                            <button type="submit" class="btn btn-info text-white fw-bold shadow-sm" <?= !$cli_enabled ? 'disabled' : '' ?>>
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
                                        <?php if (!empty($remote_url)): ?>
                                            <a href="<?= htmlspecialchars($remote_url) ?>" target="_blank" class="text-decoration-none fw-semibold">
                                                <?= htmlspecialchars($remote_url) ?> <i class="align-middle ms-1" data-feather="external-link" style="width: 12px; height: 12px;"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Not detected</span>
                                        <?php endif; ?>
                                        <div class="mt-1">
                                            <a class="small text-primary text-decoration-none" data-bs-toggle="collapse" href="#changeRemoteCollapse" role="button">
                                                <i class="align-middle me-1" data-feather="edit-2" style="width: 12px; height: 12px;"></i>Edit Remote URL
                                            </a>
                                            <div class="collapse mt-2" id="changeRemoteCollapse">
                                                <form method="post" class="d-flex gap-2">
                                                    <input type="hidden" name="git_action" value="set_remote">
                                                    <input type="text" name="remote_url" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars($remote_url ?: 'https://github.com/kgauravindia/caseos.git') ?>" required>
                                                    <button type="submit" class="btn btn-dark btn-sm text-nowrap" <?= !$cli_enabled ? 'disabled' : '' ?>>Save</button>
                                                </form>
                                            </div>
                                        </div>
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
                                <tr>
                                    <th class="text-muted ps-0">Execution Mode:</th>
                                    <td>
                                        <?php if ($cli_enabled): ?>
                                            <span class="badge bg-success-soft text-success border border-success border-opacity-25">
                                                <i class="align-middle me-1" data-feather="check-circle" style="width: 12px; height: 12px;"></i> <?= htmlspecialchars($available_exec) ?>() active
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-soft text-danger border border-danger border-opacity-25">
                                                <i class="align-middle me-1" data-feather="slash" style="width: 12px; height: 12px;"></i> Shell functions disabled
                                            </span>
                                        <?php endif; ?>
                                    </td>
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
                                            <td colspan="4" class="text-center text-muted py-3">
                                                <?= $cli_enabled ? 'No commit records found.' : 'Log history requires shell execution or git CLI.' ?>
                                            </td>
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

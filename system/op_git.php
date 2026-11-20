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

// Format Git Diff with syntax highlighting for additions, deletions, and hunk headers
function format_diff_html($raw_diff) {
    if (empty($raw_diff)) {
        return "<div class='p-3 text-muted'>No diff changes to display. File matches repository HEAD.</div>";
    }
    $lines = explode("\n", $raw_diff);
    $html = "<div class='diff-view font-monospace small' style='background: #0f172a; color: #f1f5f9; padding: 12px; border-radius: 8px; overflow-x: auto; max-height: 480px;'>";
    foreach ($lines as $line) {
        $line_escaped = htmlspecialchars($line);
        if (str_starts_with($line, 'diff --git')) {
            $html .= "<div style='color: #94a3b8; font-weight: bold; margin-top: 10px; border-bottom: 1px solid #334155; padding-bottom: 2px;'>$line_escaped</div>";
        } elseif (str_starts_with($line, '+++') || str_starts_with($line, '---')) {
            $html .= "<div style='color: #cbd5e1; font-weight: bold;'>$line_escaped</div>";
        } elseif (str_starts_with($line, '@@')) {
            $html .= "<div style='background: rgba(56, 189, 248, 0.15); color: #38bdf8; padding: 2px 4px; margin: 4px 0; border-radius: 4px;'>$line_escaped</div>";
        } elseif (str_starts_with($line, '+')) {
            $html .= "<div style='background: rgba(34, 197, 94, 0.2); color: #4ade80; padding: 1px 4px;'>$line_escaped</div>";
        } elseif (str_starts_with($line, '-')) {
            $html .= "<div style='background: rgba(239, 68, 68, 0.2); color: #f87171; padding: 1px 4px;'>$line_escaped</div>";
        } else {
            $html .= "<div style='color: #94a3b8; padding: 1px 4px;'>$line_escaped</div>";
        }
    }
    $html .= "</div>";
    return $html;
}

// -------------------------------------------------------------------------
// AJAX Handlers (for interactive Diff viewer & Commit Inspector)
// -------------------------------------------------------------------------
if (isset($_GET['ajax'])) {
    header('Content-Type: text/html; charset=utf-8');
    $ajax_type = $_GET['ajax'];
    
    if ($ajax_type === 'diff' && !empty($_GET['file'])) {
        $target_file = preg_replace('/[^a-zA-Z0-9_\-\.\/\\\\]/', '', $_GET['file']);
        $diff_output = execute_git("diff -- " . escapeshellarg($target_file), $repo_dir);
        if ($diff_output === '' || $diff_output === false) {
            // Check if untracked
            $diff_output = execute_git("status --porcelain -- " . escapeshellarg($target_file), $repo_dir);
            if ($diff_output && str_starts_with(trim($diff_output), '??')) {
                echo "<div class='p-3 text-warning font-monospace small bg-dark rounded'>Untracked file (new file not yet in repository history).</div>";
                exit;
            }
        }
        echo format_diff_html($diff_output);
        exit;
    }
    
    if ($ajax_type === 'commit' && !empty($_GET['hash'])) {
        $hash = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['hash']);
        $show_output = execute_git("show --stat -p " . escapeshellarg($hash), $repo_dir);
        echo format_diff_html($show_output);
        exit;
    }
}

// -------------------------------------------------------------------------
// Determine Current Branch
// -------------------------------------------------------------------------
$current_branch = 'main';
if ($cli_enabled) {
    $branch_out = execute_git('rev-parse --abbrev-ref HEAD', $repo_dir);
    if (!empty($branch_out) && strpos($branch_out, 'fatal') === false) {
        $current_branch = $branch_out;
    }
} else {
    $head_file = $repo_dir . '/.git/HEAD';
    if (is_file($head_file)) {
        $head_content = trim(file_get_contents($head_file));
        if (strpos($head_content, 'ref: refs/heads/') === 0) {
            $current_branch = trim(str_replace('ref: refs/heads/', '', $head_content));
        }
    }
}

$action_result = null;
$executed_cmd = null;
$action_status = 'info';

// -------------------------------------------------------------------------
// Handle Form Submissions
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['git_action'])) {
    if (!$cli_enabled) {
        $action_result = "Cannot execute Git command: Shell execution functions (shell_exec, exec, proc_open) are disabled by your hosting provider in php.ini (disable_functions). Please enable them in your hosting control panel.";
        $action_status = 'danger';
        $executed_cmd = "git " . htmlspecialchars($_POST['git_action']);
    } else {
        $action = $_POST['git_action'];
        $target_branch = !empty($_POST['branch']) ? preg_replace('/[^a-zA-Z0-9_\-\.\/]/', '', $_POST['branch']) : $current_branch;

        if ($action === 'fetch') {
            $executed_cmd = "git fetch --all --tags";
            $res = execute_git("fetch --all --tags", $repo_dir);
            $action_result = ($res === '' || $res === false) ? "Fetch completed successfully. Remote branches and tags are synchronized." : $res;
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
        elseif ($action === 'switch_branch') {
            $new_branch = preg_replace('/[^a-zA-Z0-9_\-\.\/]/', '', $_POST['new_branch'] ?? '');
            if (!empty($new_branch)) {
                $executed_cmd = "git checkout " . $new_branch;
                $action_result = execute_git("checkout " . escapeshellarg($new_branch), $repo_dir);
                $action_status = (strpos($action_result, 'error:') !== false || strpos($action_result, 'fatal:') !== false) ? 'danger' : 'success';
                if ($action_status === 'success') {
                    $current_branch = $new_branch;
                }
            }
        }
        elseif ($action === 'create_branch') {
            $create_name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $_POST['create_branch_name'] ?? '');
            if (!empty($create_name)) {
                $executed_cmd = "git checkout -b " . $create_name;
                $action_result = execute_git("checkout -b " . escapeshellarg($create_name), $repo_dir);
                $action_status = (strpos($action_result, 'fatal:') !== false || strpos($action_result, 'error:') !== false) ? 'danger' : 'success';
                if ($action_status === 'success') {
                    $current_branch = $create_name;
                }
            }
        }
        elseif ($action === 'create_tag') {
            $tag_name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $_POST['tag_name'] ?? '');
            $tag_msg  = trim($_POST['tag_message'] ?? "Release $tag_name");
            if (!empty($tag_name)) {
                $executed_cmd = "git tag -a " . $tag_name . " -m " . escapeshellarg($tag_msg);
                $tag_res = execute_git("tag -a " . escapeshellarg($tag_name) . " -m " . escapeshellarg($tag_msg), $repo_dir);
                $push_tag = execute_git("push origin " . escapeshellarg($tag_name), $repo_dir);
                $action_result = "Created Tag: $tag_name\n" . ($push_tag ?: "Pushed tag to origin.");
                $action_status = 'success';
            }
        }
        elseif ($action === 'discard_file') {
            $file = preg_replace('/[^a-zA-Z0-9_\-\.\/\\\\]/', '', $_POST['file_name'] ?? '');
            if (!empty($file)) {
                $executed_cmd = "git checkout -- " . $file;
                $action_result = execute_git("checkout -- " . escapeshellarg($file), $repo_dir);
                $action_status = 'warning';
                if ($action_result === '') {
                    $action_result = "Discarded local changes in: " . $file;
                    $action_status = 'success';
                }
            }
        }
        elseif ($action === 'discard_all') {
            $executed_cmd = "git reset --hard HEAD && git clean -fd";
            $reset_res = execute_git("reset --hard HEAD", $repo_dir);
            $clean_res = execute_git("clean -fd", $repo_dir);
            $action_result = ($reset_res ?: "") . "\n" . ($clean_res ?: "");
            $action_status = 'warning';
        }
        elseif ($action === 'git_prune') {
            $executed_cmd = "git gc --prune=now";
            $action_result = execute_git("gc --prune=now", $repo_dir) ?: "Garbage collection and repository optimization completed.";
            $action_status = 'success';
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

// -------------------------------------------------------------------------
// Query Repository Metadata
// -------------------------------------------------------------------------
$remote_url = '';
$status_short = '';
$modified_files = [];
$uncommitted_count = 0;
$ahead_count = 0;
$behind_count = 0;
$latest_commit = 'None';
$commits = [];
$branches = [];
$tags = [];

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

    // Fetch branches
    $branches_raw = execute_git('branch -a', $repo_dir);
    if (!empty($branches_raw)) {
        foreach (explode("\n", str_replace("\r", "", $branches_raw)) as $b) {
            $b_trim = trim($b);
            if (empty($b_trim) || strpos($b_trim, '->') !== false) continue;
            $is_current = str_starts_with($b_trim, '*');
            $b_name = trim(ltrim($b_trim, '*'));
            $is_remote = str_starts_with($b_name, 'remotes/');
            $branches[] = [
                'name' => $b_name,
                'clean_name' => str_replace(['remotes/origin/', 'remotes/'], '', $b_name),
                'is_current' => $is_current,
                'is_remote' => $is_remote
            ];
        }
    }

    // Fetch tags
    $tags_raw = execute_git('tag -l -n1', $repo_dir);
    if (!empty($tags_raw)) {
        foreach (explode("\n", str_replace("\r", "", $tags_raw)) as $t) {
            $t = trim($t);
            if (empty($t)) continue;
            $parts = preg_split('/\s+/', $t, 2);
            $tags[] = [
                'name' => $parts[0] ?? '',
                'desc' => $parts[1] ?? 'Release'
            ];
        }
    }

    // Fetch commit history
    $log_raw = execute_git('log -n 35 --pretty=format:"%h|%s|%an|%cr|%cd"', $repo_dir);
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

// Active tab detection
$active_tab = $_GET['tab'] ?? 'sync';
?>

<style>
    .nav-tabs-vcs .nav-link {
        font-weight: 600;
        color: #475569;
        border: none;
        border-bottom: 2px solid transparent;
        padding: 0.75rem 1.25rem;
        transition: all 0.2s ease;
    }
    .nav-tabs-vcs .nav-link:hover {
        color: #0f172a;
        border-color: #cbd5e1;
    }
    .nav-tabs-vcs .nav-link.active {
        color: #2563eb;
        background: transparent;
        border-bottom: 2px solid #2563eb;
    }
    .vcs-stat-card {
        border-radius: 12px;
        transition: transform 0.15s ease;
    }
    .vcs-stat-card:hover {
        transform: translateY(-2px);
    }
    .code-preview-box {
        background: #0f172a;
        color: #f1f5f9;
        border-radius: 8px;
        font-family: 'JetBrains Mono', Menlo, Monaco, Consolas, monospace;
        font-size: 0.8rem;
    }
</style>

<main class="content">
    <div class="container-fluid p-0">

        <!-- Top Header & Breadcrumbs -->
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
            <div>
                <h1 class="h3 mb-1 text-dark fw-bold d-flex align-items-center gap-2">
                    <i class="align-middle text-primary" data-feather="git-branch"></i> Version Control (VCS)
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 fs-6 fw-normal px-2 py-1">
                        <?= htmlspecialchars($current_branch) ?>
                    </span>
                </h1>
                <p class="text-muted mb-0 small">
                    Integrated Git synchronization, branching, release tags, and file diff viewer for CaseOS.
                </p>
            </div>
            <div class="d-flex gap-2">
                <?php if ($cli_enabled): ?>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="git_action" value="fetch">
                        <button type="submit" class="btn btn-outline-secondary btn-sm shadow-sm" title="Synchronize remote branches & tags without touching working tree">
                            <i class="align-middle me-1" data-feather="refresh-cw" style="width: 14px; height: 14px;"></i> Fetch All
                        </button>
                    </form>
                    <form method="post" class="d-inline" onsubmit="return confirm('Pull latest updates from GitHub into branch <?= htmlspecialchars($current_branch) ?>?');">
                        <input type="hidden" name="git_action" value="pull">
                        <input type="hidden" name="branch" value="<?= htmlspecialchars($current_branch) ?>">
                        <button type="submit" class="btn btn-primary btn-sm shadow-sm fw-semibold">
                            <i class="align-middle me-1" data-feather="download-cloud" style="width: 14px; height: 14px;"></i> Pull Latest
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
                        <p class="mb-2 small">Your server configuration (<code>disable_functions</code> in <code>php.ini</code>) restricts PHP from running shell commands (<code>shell_exec</code>, <code>exec</code>, or <code>proc_open</code>). Read-only repository details are loaded from the filesystem, but interactive Git commands require enabling execution functions.</p>
                        
                        <div class="bg-white bg-opacity-75 p-3 rounded-3 small">
                            <strong class="d-block mb-1 text-dark"><i class="align-middle me-1" data-feather="tool" style="width: 14px; height: 14px;"></i>How to Enable Git Pull/Push in Hostinger / cPanel:</strong>
                            <ol class="mb-0 ps-3">
                                <li>Log in to your hosting panel (e.g., <strong>Hostinger hPanel</strong>).</li>
                                <li>Navigate to <strong>Websites &rarr; Manage &rarr; Advanced &rarr; PHP Configuration</strong>.</li>
                                <li>Click on the <strong>PHP Functions</strong> tab.</li>
                                <li>Under <strong>Disable functions</strong>, remove <code>shell_exec</code> and <code>exec</code> from the list, then click <strong>Save</strong>.</li>
                                <li>Refresh this page, and all Version Control actions will become fully operational.</li>
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

        <!-- Command Execution Output Notification -->
        <?php if ($action_result !== null): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-<?= $action_status ?> bg-opacity-10 py-2 d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-<?= $action_status ?>">
                        <i class="align-middle me-1" data-feather="<?= $action_status === 'success' ? 'check-circle' : ($action_status === 'danger' ? 'alert-circle' : 'info') ?>"></i>
                        Command Result: <code class="text-dark"><?= htmlspecialchars($executed_cmd ?? '') ?></code>
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

        <!-- Key VCS Metrics -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 vcs-stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small text-uppercase fw-semibold" style="font-size: 0.7rem;">Active Branch</span>
                            <div class="stat text-primary bg-primary bg-opacity-10 p-2 rounded-3">
                                <i class="align-middle" data-feather="git-branch"></i>
                            </div>
                        </div>
                        <h3 class="mb-1 fw-bold text-dark"><?= htmlspecialchars($current_branch) ?></h3>
                        <div class="small text-muted text-truncate" title="<?= htmlspecialchars($remote_url) ?>">
                            <i class="align-middle me-1" data-feather="globe" style="width: 13px; height: 13px;"></i> <?= htmlspecialchars(basename($remote_url, '.git') ?: 'local') ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 vcs-stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small text-uppercase fw-semibold" style="font-size: 0.7rem;">Uncommitted Changes</span>
                            <div class="stat text-<?= $uncommitted_count > 0 ? 'warning' : 'success' ?> bg-<?= $uncommitted_count > 0 ? 'warning' : 'success' ?> bg-opacity-10 p-2 rounded-3">
                                <i class="align-middle" data-feather="edit-3"></i>
                            </div>
                        </div>
                        <h3 class="mb-1 fw-bold text-dark"><?= $uncommitted_count ?></h3>
                        <div class="small">
                            <?php if ($uncommitted_count > 0): ?>
                                <span class="badge bg-warning bg-opacity-15 text-warning border border-warning border-opacity-25"><?= $uncommitted_count ?> modified file(s)</span>
                            <?php else: ?>
                                <span class="text-success"><i class="align-middle me-1" data-feather="check-circle" style="width: 14px; height: 14px;"></i> Working tree clean</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 vcs-stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small text-uppercase fw-semibold" style="font-size: 0.7rem;">Outgoing (Ahead)</span>
                            <div class="stat text-warning bg-warning bg-opacity-10 p-2 rounded-3">
                                <i class="align-middle" data-feather="arrow-up-circle"></i>
                            </div>
                        </div>
                        <h3 class="mb-1 fw-bold <?= $ahead_count > 0 ? 'text-warning' : 'text-dark' ?>"><?= $ahead_count ?></h3>
                        <div class="small">
                            <?php if ($ahead_count > 0): ?>
                                <span class="badge bg-warning text-dark"><i class="align-middle me-1" data-feather="upload-cloud" style="width: 12px; height: 12px;"></i> Ready to push</span>
                            <?php else: ?>
                                <span class="text-success"><i class="align-middle me-1" data-feather="check" style="width: 14px; height: 14px;"></i> Synchronized</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 vcs-stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small text-uppercase fw-semibold" style="font-size: 0.7rem;">Incoming (Behind)</span>
                            <div class="stat text-info bg-info bg-opacity-10 p-2 rounded-3">
                                <i class="align-middle" data-feather="arrow-down-circle"></i>
                            </div>
                        </div>
                        <h3 class="mb-1 fw-bold <?= $behind_count > 0 ? 'text-danger' : 'text-dark' ?>"><?= $behind_count ?></h3>
                        <div class="small">
                            <?php if ($behind_count > 0): ?>
                                <span class="badge bg-danger"><i class="align-middle me-1" data-feather="download-cloud" style="width: 12px; height: 12px;"></i> Updates available</span>
                            <?php else: ?>
                                <span class="text-success"><i class="align-middle me-1" data-feather="check" style="width: 14px; height: 14px;"></i> Up to date</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs nav-tabs-vcs mb-4 border-bottom" id="vcsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $active_tab === 'sync' ? 'active' : '' ?>" id="sync-tab" data-bs-toggle="tab" data-bs-target="#tab-sync" type="button" role="tab">
                    <i class="align-middle me-1" data-feather="refresh-cw" style="width: 15px; height: 15px;"></i> Sync & Operations
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $active_tab === 'changes' ? 'active' : '' ?>" id="changes-tab" data-bs-toggle="tab" data-bs-target="#tab-changes" type="button" role="tab">
                    <i class="align-middle me-1" data-feather="file-text" style="width: 15px; height: 15px;"></i> Working Tree & Diff
                    <?php if ($uncommitted_count > 0): ?>
                        <span class="badge bg-danger rounded-pill ms-1"><?= $uncommitted_count ?></span>
                    <?php endif; ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $active_tab === 'branches' ? 'active' : '' ?>" id="branches-tab" data-bs-toggle="tab" data-bs-target="#tab-branches" type="button" role="tab">
                    <i class="align-middle me-1" data-feather="git-branch" style="width: 15px; height: 15px;"></i> Branches
                    <span class="badge bg-secondary rounded-pill ms-1"><?= count($branches) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $active_tab === 'history' ? 'active' : '' ?>" id="history-tab" data-bs-toggle="tab" data-bs-target="#tab-history" type="button" role="tab">
                    <i class="align-middle me-1" data-feather="clock" style="width: 15px; height: 15px;"></i> Commit History
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $active_tab === 'releases' ? 'active' : '' ?>" id="releases-tab" data-bs-toggle="tab" data-bs-target="#tab-releases" type="button" role="tab">
                    <i class="align-middle me-1" data-feather="tag" style="width: 15px; height: 15px;"></i> Version Releases
                    <span class="badge bg-info rounded-pill ms-1"><?= count($tags) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $active_tab === 'settings' ? 'active' : '' ?>" id="settings-tab" data-bs-toggle="tab" data-bs-target="#tab-settings" type="button" role="tab">
                    <i class="align-middle me-1" data-feather="settings" style="width: 15px; height: 15px;"></i> Remote & Config
                </button>
            </li>
        </ul>

        <!-- Tab Contents -->
        <div class="tab-content" id="vcsTabContent">

            <!-- TAB 1: SYNC & OPERATIONS -->
            <div class="tab-pane fade <?= $active_tab === 'sync' ? 'show active' : '' ?>" id="tab-sync" role="tabpanel">
                <div class="row g-4">
                    
                    <div class="col-lg-7">
                        <!-- Quick Pull & Push -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom py-3">
                                <h5 class="card-title mb-0 fw-bold text-dark">
                                    <i class="align-middle me-2 text-primary" data-feather="zap"></i>One-Click Sync Actions
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="p-3 border rounded-3 bg-light bg-opacity-50 h-100 d-flex flex-column justify-content-between">
                                            <div>
                                                <div class="d-flex align-items-center mb-2">
                                                    <div class="bg-primary text-white p-2 rounded-circle me-2 d-inline-flex">
                                                        <i class="align-middle" data-feather="download" style="width: 16px; height: 16px;"></i>
                                                    </div>
                                                    <h6 class="fw-bold mb-0">Git Pull</h6>
                                                </div>
                                                <p class="text-muted small mb-3">Fetch & merge remote commits into current <code><?= htmlspecialchars($current_branch) ?></code> branch.</p>
                                            </div>
                                            <form method="post" onsubmit="return confirm('Execute git pull origin <?= htmlspecialchars($current_branch) ?>?');">
                                                <input type="hidden" name="git_action" value="pull">
                                                <input type="hidden" name="branch" value="<?= htmlspecialchars($current_branch) ?>">
                                                <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm" <?= !$cli_enabled ? 'disabled' : '' ?>>
                                                    <i class="align-middle me-1" data-feather="download-cloud"></i> Pull from Origin
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="p-3 border rounded-3 bg-light bg-opacity-50 h-100 d-flex flex-column justify-content-between">
                                            <div>
                                                <div class="d-flex align-items-center mb-2">
                                                    <div class="bg-success text-white p-2 rounded-circle me-2 d-inline-flex">
                                                        <i class="align-middle" data-feather="upload" style="width: 16px; height: 16px;"></i>
                                                    </div>
                                                    <h6 class="fw-bold mb-0">Git Push</h6>
                                                </div>
                                                <p class="text-muted small mb-3">Push local committed changes directly to remote <code><?= htmlspecialchars($current_branch) ?></code>.</p>
                                            </div>
                                            <form method="post" onsubmit="return confirm('Execute git push origin <?= htmlspecialchars($current_branch) ?>?');">
                                                <input type="hidden" name="git_action" value="push">
                                                <input type="hidden" name="branch" value="<?= htmlspecialchars($current_branch) ?>">
                                                <button type="submit" class="btn btn-success w-100 fw-bold shadow-sm" <?= !$cli_enabled ? 'disabled' : '' ?>>
                                                    <i class="align-middle me-1" data-feather="upload-cloud"></i> Push to Origin
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3 pt-3 border-top d-flex flex-wrap gap-2">
                                    <form method="post" class="d-inline" onsubmit="return confirm('Stash current uncommitted changes?');">
                                        <input type="hidden" name="git_action" value="stash">
                                        <button type="submit" class="btn btn-outline-warning btn-sm" <?= !$cli_enabled ? 'disabled' : '' ?> title="Temporarily shelve uncommitted changes">
                                            <i class="align-middle me-1" data-feather="archive"></i> Stash Changes
                                        </button>
                                    </form>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Apply latest stashed changes back?');">
                                        <input type="hidden" name="git_action" value="stash_pop">
                                        <button type="submit" class="btn btn-outline-secondary btn-sm" <?= !$cli_enabled ? 'disabled' : '' ?> title="Restore stashed changes">
                                            <i class="align-middle me-1" data-feather="inbox"></i> Pop Stash
                                        </button>
                                    </form>
                                    <form method="post" class="d-inline ms-auto" onsubmit="return confirm('Run repository cleanup and optimization (git gc)?');">
                                        <input type="hidden" name="git_action" value="git_prune">
                                        <button type="submit" class="btn btn-outline-dark btn-sm" <?= !$cli_enabled ? 'disabled' : '' ?> title="Prune and optimize local repository packfiles">
                                            <i class="align-middle me-1" data-feather="cpu"></i> Clean / Prune
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Stage, Commit & Push Box -->
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
                                        <textarea name="commit_message" class="form-control" rows="2" placeholder="e.g. Update invoice design and fix edge case in payment handler" required <?= !$cli_enabled ? 'disabled' : '' ?>></textarea>
                                        <div class="form-text small">This runs <code>git add -A</code>, creates a commit, and immediately pushes to <code>origin <?= htmlspecialchars($current_branch) ?></code>.</div>
                                    </div>

                                    <button type="submit" class="btn btn-info text-white fw-bold shadow-sm" <?= !$cli_enabled ? 'disabled' : '' ?>>
                                        <i class="align-middle me-1" data-feather="send"></i> Commit & Push All
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <!-- Summary Details -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom py-3">
                                <h5 class="card-title mb-0 fw-bold text-dark">
                                    <i class="align-middle me-2 text-secondary" data-feather="info"></i>Environment & Remote
                                </h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm table-borderless mb-0">
                                    <tbody>
                                        <tr>
                                            <th class="text-muted ps-0" style="width: 130px;">Remote URL:</th>
                                            <td>
                                                <small class="text-dark font-monospace text-break"><?= htmlspecialchars($remote_url ?: 'None') ?></small>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="text-muted ps-0">Root Path:</th>
                                            <td><code class="text-break"><?= htmlspecialchars($repo_dir) ?></code></td>
                                        </tr>
                                        <tr>
                                            <th class="text-muted ps-0">Latest Commit:</th>
                                            <td><small class="text-dark"><?= htmlspecialchars($latest_commit ?: 'None') ?></small></td>
                                        </tr>
                                        <tr>
                                            <th class="text-muted ps-0">Execution Mode:</th>
                                            <td>
                                                <?php if ($cli_enabled): ?>
                                                    <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25">
                                                        <?= htmlspecialchars($available_exec) ?>() Active
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger bg-opacity-15 text-danger border border-danger border-opacity-25">
                                                        Disabled in php.ini
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Quick Branch Overview -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0 fw-bold text-dark">
                                    <i class="align-middle me-2 text-primary" data-feather="git-branch"></i>Branches Overview
                                </h5>
                                <a href="#tab-branches" data-bs-toggle="tab" class="small text-primary text-decoration-none">Manage All &rarr;</a>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush">
                                    <?php 
                                    $displayed_branches = array_slice($branches, 0, 5);
                                    foreach ($displayed_branches as $b): 
                                    ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                            <div class="d-flex align-items-center">
                                                <i class="align-middle me-2 text-<?= $b['is_current'] ? 'primary' : 'muted' ?>" data-feather="git-branch" style="width: 14px; height: 14px;"></i>
                                                <span class="small <?= $b['is_current'] ? 'fw-bold text-dark' : 'text-muted' ?>">
                                                    <?= htmlspecialchars($b['name']) ?>
                                                </span>
                                            </div>
                                            <?php if ($b['is_current']): ?>
                                                <span class="badge bg-primary">Active</span>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- TAB 2: WORKING TREE & DIFF VIEWER -->
            <div class="tab-pane fade <?= $active_tab === 'changes' ? 'show active' : '' ?>" id="tab-changes" role="tabpanel">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="card-title mb-0 fw-bold text-dark">
                                <i class="align-middle me-2 text-warning" data-feather="edit-3"></i>Working Tree Changes
                            </h5>
                            <span class="small text-muted"><?= count($modified_files) ?> file(s) modified or untracked</span>
                        </div>
                        <div class="d-flex gap-2">
                            <?php if (!empty($modified_files)): ?>
                                <form method="post" onsubmit="return confirm('WARNING: Discard ALL uncommitted changes across the entire repository?');">
                                    <input type="hidden" name="git_action" value="discard_all">
                                    <button type="submit" class="btn btn-outline-danger btn-sm" <?= !$cli_enabled ? 'disabled' : '' ?>>
                                        <i class="align-middle me-1" data-feather="trash-2" style="width: 14px; height: 14px;"></i> Discard All Changes
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($modified_files)): ?>
                            <div class="text-center py-5">
                                <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle d-inline-flex mb-3">
                                    <i data-feather="check-circle" style="width: 32px; height: 32px;"></i>
                                </div>
                                <h5 class="fw-bold text-dark mb-1">Working Tree Clean</h5>
                                <p class="text-muted small mb-0">No unstaged or modified files detected. All changes are committed.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="font-size: 0.75rem; width: 80px;">STATUS</th>
                                            <th style="font-size: 0.75rem;">FILE PATH</th>
                                            <th style="font-size: 0.75rem; width: 180px;" class="text-end pe-3">ACTIONS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($modified_files as $f): 
                                            $f = trim($f);
                                            if (empty($f)) continue;
                                            $code = substr($f, 0, 2);
                                            $file_path = trim(substr($f, 2));
                                            $badge_class = str_contains($code, 'M') ? 'bg-warning text-dark' : (str_contains($code, 'A') || str_contains($code, '?') ? 'bg-success' : 'bg-danger');
                                        ?>
                                            <tr>
                                                <td>
                                                    <span class="badge <?= $badge_class ?> font-monospace"><?= htmlspecialchars($code) ?></span>
                                                </td>
                                                <td>
                                                    <code class="text-dark"><?= htmlspecialchars($file_path) ?></code>
                                                </td>
                                                <td class="text-end pe-3">
                                                    <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" onclick="inspectDiff('<?= addslashes($file_path) ?>')">
                                                        <i class="align-middle me-1" data-feather="eye" style="width: 13px; height: 13px;"></i> View Diff
                                                    </button>
                                                    <?php if (!str_contains($code, '?')): ?>
                                                        <form method="post" class="d-inline" onsubmit="return confirm('Discard changes in <?= addslashes($file_path) ?>?');">
                                                            <input type="hidden" name="git_action" value="discard_file">
                                                            <input type="hidden" name="file_name" value="<?= htmlspecialchars($file_path) ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" title="Revert to HEAD">
                                                                <i class="align-middle" data-feather="rotate-ccw" style="width: 13px; height: 13px;"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- TAB 3: BRANCHES -->
            <div class="tab-pane fade <?= $active_tab === 'branches' ? 'show active' : '' ?>" id="tab-branches" role="tabpanel">
                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom py-3">
                                <h5 class="card-title mb-0 fw-bold text-dark">
                                    <i class="align-middle me-2 text-primary" data-feather="git-branch"></i>Available Branches
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="font-size: 0.75rem;">BRANCH</th>
                                                <th style="font-size: 0.75rem;">TYPE</th>
                                                <th style="font-size: 0.75rem; width: 140px;" class="text-end pe-3">ACTION</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($branches as $b): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <i class="align-middle me-2 text-<?= $b['is_current'] ? 'primary' : 'muted' ?>" data-feather="git-branch" style="width: 15px; height: 15px;"></i>
                                                            <span class="font-monospace small <?= $b['is_current'] ? 'fw-bold text-primary' : 'text-dark' ?>">
                                                                <?= htmlspecialchars($b['name']) ?>
                                                            </span>
                                                            <?php if ($b['is_current']): ?>
                                                                <span class="badge bg-primary ms-2">Active</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-light text-muted border">
                                                            <?= $b['is_remote'] ? 'Remote Tracked' : 'Local' ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-end pe-3">
                                                        <?php if (!$b['is_current'] && !$b['is_remote']): ?>
                                                            <form method="post" class="d-inline" onsubmit="return confirm('Switch working branch to <?= addslashes($b['clean_name']) ?>?');">
                                                                <input type="hidden" name="git_action" value="switch_branch">
                                                                <input type="hidden" name="new_branch" value="<?= htmlspecialchars($b['clean_name']) ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-primary py-1 px-2" <?= !$cli_enabled ? 'disabled' : '' ?>>
                                                                    Checkout
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom py-3">
                                <h5 class="card-title mb-0 fw-bold text-dark">
                                    <i class="align-middle me-2 text-success" data-feather="plus-circle"></i>Create New Branch
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="post" onsubmit="return confirm('Create and checkout new branch?');">
                                    <input type="hidden" name="git_action" value="create_branch">
                                    
                                    <div class="mb-3">
                                        <label class="form-label small text-muted fw-bold">Branch Name</label>
                                        <input type="text" name="create_branch_name" class="form-control" placeholder="feature/new-module or fix/billing" required <?= !$cli_enabled ? 'disabled' : '' ?>>
                                        <div class="form-text small">Branch will be branched directly off <code><?= htmlspecialchars($current_branch) ?></code>.</div>
                                    </div>

                                    <button type="submit" class="btn btn-success fw-bold shadow-sm" <?= !$cli_enabled ? 'disabled' : '' ?>>
                                        <i class="align-middle me-1" data-feather="git-pull-request"></i> Create & Switch Branch
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 4: COMMIT LOG -->
            <div class="tab-pane fade <?= $active_tab === 'history' ? 'show active' : '' ?>" id="tab-history" role="tabpanel">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            <i class="align-middle me-2 text-primary" data-feather="clock"></i>Detailed Commit History
                        </h5>
                        <span class="badge bg-light text-muted border"><?= count($commits) ?> commits loaded</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="font-size: 0.75rem; width: 100px;">COMMIT</th>
                                        <th style="font-size: 0.75rem;">MESSAGE</th>
                                        <th style="font-size: 0.75rem; width: 160px;">AUTHOR</th>
                                        <th style="font-size: 0.75rem; width: 140px;">TIME</th>
                                        <th style="font-size: 0.75rem; width: 100px;" class="text-end pe-3">INSPECT</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($commits)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">No commit records available.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($commits as $c): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-light text-primary border font-monospace"><?= htmlspecialchars($c['hash']) ?></span>
                                                </td>
                                                <td>
                                                    <strong class="text-dark small"><?= htmlspecialchars($c['subject']) ?></strong>
                                                </td>
                                                <td>
                                                    <span class="text-muted small"><?= htmlspecialchars($c['author']) ?></span>
                                                </td>
                                                <td>
                                                    <span class="text-muted small text-nowrap"><?= htmlspecialchars($c['time_ago']) ?></span>
                                                </td>
                                                <td class="text-end pe-3">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" onclick="inspectCommit('<?= htmlspecialchars($c['hash']) ?>')">
                                                        <i class="align-middle" data-feather="file-text" style="width: 13px; height: 13px;"></i> Diff
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 5: RELEASES & VERSION TAGS -->
            <div class="tab-pane fade <?= $active_tab === 'releases' ? 'show active' : '' ?>" id="tab-releases" role="tabpanel">
                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom py-3">
                                <h5 class="card-title mb-0 fw-bold text-dark">
                                    <i class="align-middle me-2 text-info" data-feather="tag"></i>Version Release Tags
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($tags)): ?>
                                    <div class="text-center py-5">
                                        <div class="bg-light p-3 rounded-circle d-inline-flex mb-3">
                                            <i data-feather="tag" style="width: 32px; height: 32px;" class="text-muted"></i>
                                        </div>
                                        <h5 class="fw-bold text-dark mb-1">No Version Tags Found</h5>
                                        <p class="text-muted small mb-0">Tag releases (e.g., <code>v1.0.0</code>) to mark stable deployment milestones.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="font-size: 0.75rem;">VERSION TAG</th>
                                                    <th style="font-size: 0.75rem;">ANNOTATION / NOTES</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($tags as $t): ?>
                                                    <tr>
                                                        <td>
                                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 font-monospace fs-6 px-2 py-1">
                                                                <i class="align-middle me-1" data-feather="tag" style="width: 12px; height: 12px;"></i> <?= htmlspecialchars($t['name']) ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="text-dark small"><?= htmlspecialchars($t['desc']) ?></span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom py-3">
                                <h5 class="card-title mb-0 fw-bold text-dark">
                                    <i class="align-middle me-2 text-success" data-feather="plus-circle"></i>Create Version Release Tag
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="post" onsubmit="return confirm('Create and push this version release tag to remote?');">
                                    <input type="hidden" name="git_action" value="create_tag">
                                    
                                    <div class="mb-3">
                                        <label class="form-label small text-muted fw-bold">Version Name</label>
                                        <input type="text" name="tag_name" class="form-control font-monospace" placeholder="e.g. v1.1.0 or v2.0-stable" required <?= !$cli_enabled ? 'disabled' : '' ?>>
                                        <div class="form-text small">Standard semantic versions: <code>vMAJOR.MINOR.PATCH</code>.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small text-muted fw-bold">Release Annotation</label>
                                        <textarea name="tag_message" class="form-control" rows="2" placeholder="e.g. Release 1.1: Added Non-GST invoices and improved razorpay verification" required <?= !$cli_enabled ? 'disabled' : '' ?>></textarea>
                                    </div>

                                    <button type="submit" class="btn btn-success fw-bold shadow-sm" <?= !$cli_enabled ? 'disabled' : '' ?>>
                                        <i class="align-middle me-1" data-feather="tag"></i> Tag & Publish Release
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 6: SETTINGS & REMOTE CONFIG -->
            <div class="tab-pane fade <?= $active_tab === 'settings' ? 'show active' : '' ?>" id="tab-settings" role="tabpanel">
                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom py-3">
                                <h5 class="card-title mb-0 fw-bold text-dark">
                                    <i class="align-middle me-2 text-secondary" data-feather="link"></i>Remote Origin Configuration
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="post">
                                    <input type="hidden" name="git_action" value="set_remote">
                                    
                                    <div class="mb-3">
                                        <label class="form-label small text-muted fw-bold">Remote Origin URL</label>
                                        <input type="text" name="remote_url" class="form-control font-monospace" value="<?= htmlspecialchars($remote_url ?: 'https://github.com/kgauravindia/caseos.git') ?>" required <?= !$cli_enabled ? 'disabled' : '' ?>>
                                        <div class="form-text small">Change between HTTPS (<code>https://...</code>) and SSH (<code>git@...</code>). HTTPS is strongly recommended for shared hosting environments.</div>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary fw-bold" <?= !$cli_enabled ? 'disabled' : '' ?>>
                                            Update Remote URL
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="document.querySelector('input[name=remote_url]').value='https://github.com/kgauravindia/caseos.git'">
                                            Reset to Default
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom py-3">
                                <h5 class="card-title mb-0 fw-bold text-dark">
                                    <i class="align-middle me-2 text-info" data-feather="terminal"></i>System Diagnostics
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush small">
                                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                        <span>PHP CLI Function</span>
                                        <strong><?= htmlspecialchars($available_exec ?: 'None available') ?></strong>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                        <span>Operating System</span>
                                        <strong><?= htmlspecialchars(PHP_OS_FAMILY) ?> (<?= htmlspecialchars(php_uname('s')) ?>)</strong>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                        <span>PHP Version</span>
                                        <strong><?= htmlspecialchars(PHP_VERSION) ?></strong>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                        <span>Git Version</span>
                                        <strong><?= htmlspecialchars(execute_git('--version', $repo_dir) ?: 'Not detected') ?></strong>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</main>

<!-- Interactive Diff / Commit Modal -->
<div class="modal fade" id="vcsInspectModal" tabindex="-1" aria-labelledby="vcsInspectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title font-monospace small mb-0" id="vcsInspectModalLabel">
                    <i class="align-middle me-2" data-feather="file-text"></i> Inspector
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" id="vcsInspectModalBody" style="min-height: 250px;">
                <div class="text-center py-5 text-muted">
                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div> Loading diff...
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    var inspectModal;
    document.addEventListener("DOMContentLoaded", function() {
        var modalEl = document.getElementById('vcsInspectModal');
        if (modalEl) {
            inspectModal = new bootstrap.Modal(modalEl);
        }
    });

    function inspectDiff(filePath) {
        document.getElementById('vcsInspectModalLabel').innerHTML = '<i class="align-middle me-2" data-feather="file-text"></i> Diff: ' + filePath;
        document.getElementById('vcsInspectModalBody').innerHTML = '<div class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Loading diff...</div>';
        if (inspectModal) inspectModal.show();

        fetch('op_git?ajax=diff&file=' + encodeURIComponent(filePath))
            .then(function(res) { return res.text(); })
            .then(function(html) {
                document.getElementById('vcsInspectModalBody').innerHTML = html;
                feather.replace();
            })
            .catch(function(err) {
                document.getElementById('vcsInspectModalBody').innerHTML = '<div class="alert alert-danger m-3">Failed to load diff: ' + err + '</div>';
            });
    }

    function inspectCommit(hash) {
        document.getElementById('vcsInspectModalLabel').innerHTML = '<i class="align-middle me-2" data-feather="clock"></i> Commit Diff: ' + hash;
        document.getElementById('vcsInspectModalBody').innerHTML = '<div class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Loading commit changes...</div>';
        if (inspectModal) inspectModal.show();

        fetch('op_git?ajax=commit&hash=' + encodeURIComponent(hash))
            .then(function(res) { return res.text(); })
            .then(function(html) {
                document.getElementById('vcsInspectModalBody').innerHTML = html;
                feather.replace();
            })
            .catch(function(err) {
                document.getElementById('vcsInspectModalBody').innerHTML = '<div class="alert alert-danger m-3">Failed to load commit: ' + err + '</div>';
            });
    }
</script>

<?php require_once('footer.php'); ?>

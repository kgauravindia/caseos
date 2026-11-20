<?php
echo "=== 1. PHP SYNTAX LINTING ===\n";
$dirs = ['.', 'public', 'system', 'ppay'];
$php_files = [];
foreach ($dirs as $d) {
    foreach (glob($d . "/*.php") as $f) {
        $php_files[] = $f;
    }
}

$syntax_errors = [];
foreach ($php_files as $f) {
    $out = shell_exec('"D:\laragon\bin\php\php-8.3.28-Win32-vs16-x64\php.exe" -l ' . escapeshellarg($f) . ' 2>&1');
    if (strpos($out, "No syntax errors detected") === false) {
        $syntax_errors[] = "$f: " . trim($out);
    }
}
if (empty($syntax_errors)) {
    echo "SUCCESS: All " . count($php_files) . " PHP files passed syntax checks without errors.\n";
} else {
    echo "ERRORS FOUND:\n" . implode("\n", $syntax_errors) . "\n";
}

echo "\n=== 2. STATIC INCLUDE CHECK ===\n";
$missing_includes = [];
foreach ($php_files as $f) {
    $content = file_get_contents($f);
    $dir = dirname($f);
    // match require, require_once, include, include_once with string literals
    if (preg_match_all('/(?:require|require_once|include|include_once)\s*[\(\s]*[\'"]([^\'"]+\.php)[\'"]\s*[\)]*;/i', $content, $matches)) {
        foreach ($matches[1] as $inc) {
            // resolve relative to file's directory
            $target = $dir . '/' . $inc;
            if (!file_exists($target) && !file_exists($inc)) {
                $missing_includes[] = "File: $f includes '$inc' which cannot be found at '$target'.";
            }
        }
    }
}

if (empty($missing_includes)) {
    echo "SUCCESS: All static require/include paths point to existing files.\n";
} else {
    echo "POTENTIAL BROKEN INCLUDES:\n" . implode("\n", $missing_includes) . "\n";
}

echo "\n=== 3. PHP 8 DEPRECATION SCAN ===\n";
$deprecations = [];
foreach ($php_files as $f) {
    $content = file_get_contents($f);
    // Find function declaration with optional param before required param
    if (preg_match_all('/function\s+([a-zA-Z0-9_]+)\s*\(([^)]+)\)/i', $content, $matches)) {
        foreach ($matches[2] as $idx => $param_str) {
            $params = explode(',', $param_str);
            $has_default = false;
            foreach ($params as $p) {
                $p = trim($p);
                if (strpos($p, '=') !== false) {
                    $has_default = true;
                } elseif ($has_default && strpos($p, '$') !== false) {
                    $fn_name = $matches[1][$idx];
                    $deprecations[] = "File: $f function $fn_name() has optional param before required param: ($param_str)";
                    break;
                }
            }
        }
    }
}

if (empty($deprecations)) {
    echo "SUCCESS: No optional-before-required parameter deprecations found.\n";
} else {
    echo "DEPRECATIONS FOUND:\n" . implode("\n", $deprecations) . "\n";
}

echo "\n=== COMPLETE ===\n";

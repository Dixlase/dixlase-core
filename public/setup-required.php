<?php

/**
 * Standalone fallback rendered by public/index.php when
 * vendor/autoload.php does not exist yet.
 *
 * Self-contained on purpose: at the point this is required, Composer
 * has not bootstrapped, so no Laravel facades, helpers, config, or
 * autoloaded classes are available. The page uses only built-in PHP
 * functions and inline CSS, and returns HTTP 503 to make it explicit
 * that the application is not yet serving requests.
 */

$missingVendor = ! is_file(__DIR__.'/../vendor/autoload.php');
$missingBuild = ! is_file(__DIR__.'/assets/build/manifest.json');

/**
 * Resolve the project root as the operator should cd to it from their
 * shell.
 *
 * Resolution order:
 *   1. DIXLASE_HOST_PROJECT_PATH env var — wins if the installer or
 *      operator explicitly sets it.
 *   2. /proc/self/mountinfo parsing — recovers the host-side bind
 *      mount source for /var/www/html. Handles Docker Desktop's
 *      virtio-fs convention (super source `/run/host_mark/<top>`
 *      indicating that /<top> on the host is bind-mounted into the VM)
 *      and plain Linux Docker (where the source field is already the
 *      host path).
 *   3. realpath() — the path PHP itself sees. Correct for plain local
 *      installs without containerisation. Falls back to a literal
 *      relative path if even that fails.
 */
function dixlase_detect_host_project_path(): ?string
{
    $env = getenv('DIXLASE_HOST_PROJECT_PATH');
    if ($env !== false && $env !== '') {
        return $env;
    }

    if (! is_readable('/proc/self/mountinfo')) {
        return null;
    }

    $mountPoint = '/var/www/html';
    foreach (@file('/proc/self/mountinfo', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $parts = explode(' - ', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $left = preg_split('/\s+/', $parts[0]);
        $right = preg_split('/\s+/', $parts[1]);
        if (($left[4] ?? null) !== $mountPoint) {
            continue;
        }
        $source = $left[3] ?? '';
        $superSource = $right[1] ?? '';

        // Docker Desktop (macOS / Windows) maps host /<top> into the VM
        // as /run/host_mark/<top>, so we re-prepend /<top> to the
        // source path to recover the operator's host-side path.
        if (preg_match('#^/run/host_mark/([^/]+)#', $superSource, $hm)) {
            return '/'.$hm[1].$source;
        }

        // Plain Linux Docker: the bind-mount source field is already
        // the host path. The "/" sentinel means a tmpfs / overlay
        // rather than a bind mount and isn't useful here.
        if ($source !== '' && $source !== '/' && str_starts_with($source, '/')) {
            return $source;
        }
    }

    return null;
}

$projectPath = dixlase_detect_host_project_path()
    ?? (realpath(__DIR__.'/..') ?: __DIR__.'/..');

http_response_code(503);
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Setup required — Dixlase / セットアップが必要です — Dixlase</title>
<style>
*{box-sizing:border-box}
html,body{margin:0;padding:0;min-height:100%}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",sans-serif;background:#0f172a;color:#f1f5f9;padding:2rem 1rem;line-height:1.55}
.wrap{max-width:760px;margin:0 auto}
.card{background:#1e293b;border:1px solid #334155;border-radius:0.75rem;padding:2rem;margin-bottom:1.5rem;box-shadow:0 10px 25px rgba(0,0,0,0.3)}
h1{font-size:1.5rem;font-weight:600;margin:0 0 0.5rem;color:#f8fafc;display:flex;align-items:center;gap:0.625rem;flex-wrap:wrap}
h1 .icon{width:1.5rem;height:1.5rem;color:#fbbf24;flex-shrink:0}
h2{font-size:1.0625rem;font-weight:600;margin:1.5rem 0 0.5rem;color:#e2e8f0}
h2 .lang{font-size:0.75rem;color:#94a3b8;font-weight:400;margin-left:0.5rem}
p{margin:0.5rem 0;color:#cbd5e1;font-size:0.9375rem}
.subtitle{color:#94a3b8;margin:0 0 1.25rem;font-size:0.875rem}
.status{display:flex;gap:0.5rem;align-items:center;font-size:0.8125rem;color:#94a3b8;margin:0.375rem 0;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}
.status.bad{color:#fca5a5}
.status .dot{width:0.5rem;height:0.5rem;border-radius:50%;background:#64748b;flex-shrink:0}
.status.bad .dot{background:#ef4444}
pre{background:#0b1220;border:1px solid #1e293b;border-radius:0.5rem;padding:1rem;overflow-x:auto;font-size:0.8125rem;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;color:#e2e8f0;margin:0.75rem 0;line-height:1.5}
code{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:0.875em;background:#0b1220;padding:0.125rem 0.375rem;border-radius:0.25rem;color:#e2e8f0}
a{color:#60a5fa;text-decoration:none}
a:hover{text-decoration:underline}
.footer{text-align:center;font-size:0.75rem;color:#64748b;margin-top:1.5rem}
hr{border:none;border-top:1px solid #334155;margin:1.5rem 0}
strong{color:#f1f5f9}
</style>
</head>
<body>
<div class="wrap">
<div class="card">
<h1>
<svg class="icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>
<span>Setup required / セットアップが必要です</span>
</h1>
<p class="subtitle">Dixlase CMS</p>

<div class="status <?= $missingVendor ? 'bad' : '' ?>">
<span class="dot" aria-hidden="true"></span>
<span><code>vendor/</code> &mdash; <?= $missingVendor ? 'missing (Composer dependencies not installed)' : 'OK' ?></span>
</div>
<?php if ($missingBuild): ?>
<div class="status bad">
<span class="dot" aria-hidden="true"></span>
<span><code>public/assets/build/</code> &mdash; missing (front-end assets not built)</span>
</div>
<?php endif; ?>

<hr>

<h2>English</h2>
<p>This Dixlase CMS installation has not finished its initial setup. The PHP and front-end dependencies still need to be installed before the application can boot.</p>

<p><strong>Prerequisites:</strong> the commands below need <a href="https://getcomposer.org/download/" target="_blank" rel="noopener noreferrer">Composer</a> and <a href="https://nodejs.org/" target="_blank" rel="noopener noreferrer">Node.js</a> (which bundles <code>npm</code>) installed on this machine. Follow the links for installation instructions if either is missing.</p>

<p><strong>Run these from the project root:</strong></p>
<pre>cd <?= htmlspecialchars($projectPath, ENT_QUOTES, 'UTF-8') ?>

composer install --no-dev --optimize-autoloader
npm install
npm run build</pre>

<p>When the commands finish, reload this page. If everything succeeded, <code>/</code> automatically redirects to <code>/install</code> and the installation wizard begins.</p>

<hr>

<h2>日本語 <span class="lang">(Japanese)</span></h2>
<p>この Dixlase CMS は初期セットアップが完了していません。PHP とフロントエンドの依存関係を先にインストールする必要があります。</p>

<p><strong>前提条件:</strong> 以下のコマンドの実行には、このマシンに <a href="https://getcomposer.org/download/" target="_blank" rel="noopener noreferrer">Composer</a> と <a href="https://nodejs.org/" target="_blank" rel="noopener noreferrer">Node.js</a>（<code>npm</code> 同梱)がインストールされている必要があります。未導入の場合は各リンクからインストール手順を参照してください。</p>

<p><strong>プロジェクトルートで以下を実行してください:</strong></p>
<pre>cd <?= htmlspecialchars($projectPath, ENT_QUOTES, 'UTF-8') ?>

composer install --no-dev --optimize-autoloader
npm install
npm run build</pre>

<p>コマンドの実行が完了したら、このページを再読込してください。問題なくセットアップできていれば、<code>/</code> から <code>/install</code> に自動でリダイレクトされ、インストールウィザードが始まります。</p>
</div>

<div class="footer">
Dixlase CMS &middot; <a href="https://github.com/Dixlase/dixlase-core">github.com/Dixlase/dixlase-core</a>
</div>
</div>
</body>
</html>

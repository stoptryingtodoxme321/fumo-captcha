<?php
declare(strict_types=1);

function render(string $view, array $data = []): string
{
    $file = __DIR__ . '/views/' . $view . '.php';
    if (!is_file($file)) {
        fail(500, 'missing view: ' . $view);
    }
    extract($data, EXTR_SKIP);
    ob_start();
    require $file;
    return (string) ob_get_clean();
}

const STYLES = <<<'CSS'
:root { --blue:#1a73e8; --blue-dim:#1765cc; --line:#dadce0; }
* { box-sizing:border-box }
body { margin:0; min-height:100vh; display:flex; padding:12px;
  background:#202124; color:#202124;
  font:16px/1.4 Roboto,Arial,sans-serif }
.sr { position:absolute; width:1px; height:1px; overflow:hidden;
  clip-path:inset(50%); white-space:nowrap }

.card { width:min(96vw,440px); margin:auto; background:#fff; border-radius:0;
  box-shadow:0 1px 6px rgba(0,0,0,.2); overflow:hidden;
  color-scheme:light; border:1px solid #d3d3d3 }

.rcap-head { padding:16px 18px 12px; background:var(--blue); color:#fff }
.rcap-head .lead { font-size:14px; line-height:1.3; color:#ffffffcc }
.rcap-head h1 { margin:2px 0 0; font-size:20px; line-height:1.25;
  font-weight:700; letter-spacing:.2px }

.grid { display:grid; grid-template-columns:repeat(var(--cols,4),1fr); gap:2px;
  background:#fff; padding:2px }
.tile { position:relative; display:block; cursor:pointer; line-height:0;
  background:#eee; border-radius:0; overflow:hidden }
.tile input { position:absolute; opacity:0; width:1px; height:1px }
.tile img { width:100%; aspect-ratio:1; object-fit:cover; display:block;
  transition:opacity .1s }
.tile:hover img { opacity:.85 }
.tile input:focus-visible ~ img { outline:3px solid var(--blue); outline-offset:-3px }

.tile:has(input:checked)::after { content:''; position:absolute; inset:0; z-index:2;
  background-color:#1a73e8b0;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%23fff' d='M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z'/%3E%3C/svg%3E");
  background-repeat:no-repeat; background-position:center; background-size:36% }

.err { padding:10px 18px; font-size:14px; color:#d93025; background:#fce8e6 }
.err:empty { display:none }

.rcap-foot { display:flex; align-items:center; gap:4px; padding:6px 8px;
  border-top:1px solid #e8eaed; background:#fff }
.rcap-foot .spacer { flex:1 }
.icon-btn { display:grid; place-items:center; width:36px; height:36px; padding:0;
  border:0; border-radius:0; background:none; color:#5f6368; cursor:pointer }
.icon-btn:hover:not(:disabled) { background:#f8f9fa }
.icon-btn:disabled { color:#c8c9cb; cursor:default }
.icon-btn svg { width:20px; height:20px; display:block }
button.go { min-width:104px; padding:9px 16px; border:0; border-radius:0;
  background:var(--blue); color:#fff; font:500 14px/1 inherit; cursor:pointer;
  letter-spacing:.5px; text-transform:uppercase }
button.go:hover:not(:disabled) { background:var(--blue-dim) }
button.go:disabled { background:#dadce0; color:#80868b; cursor:default }
:focus-visible { outline:2px solid var(--blue); outline-offset:2px }

.home { padding:24px }
.home h1 { margin:0 0 8px; font-size:20px }
.home p  { margin:0 0 16px; color:#5f6368; font-size:13px }
.home button.go { width:100%; margin:0 }
.ok { color:#188038 }
CSS;

function head(string $title): void
{
    $t = e($title);


    $styles = STYLES;
    echo <<<HTML
    <!doctype html>
    <html lang="en">
    <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{$t}</title>
    <style>{$styles}</style>
    </head>
    <body>
    HTML;
}

function foot(): void
{
    echo "\n</body>\n</html>\n";
}
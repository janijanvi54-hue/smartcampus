# DSVV SmartCampus - quick sanity check before pushing to GitHub / Render.
# Usage (from the project folder):  powershell -ExecutionPolicy Bypass -File check.ps1
$root = $PSScriptRoot
$fail = 0

# Char-by-char JS bracket balance check (handles strings, comments, template
# literals and regex literals, so it won't cry about /^\// or "//" inside code).
function Test-JsBalance([string]$js) {
    $n = $js.Length
    $depth = 0
    $i = 0
    $mode = 'code'
    $prev = ';'
    while ($i -lt $n) {
        $ch = $js[$i]
        if ($mode -eq 'code') {
            if ($ch -eq "'")  { $mode = 'sq'; $prev = $ch; $i++; continue }
            if ($ch -eq '"')  { $mode = 'dq'; $prev = $ch; $i++; continue }
            if ($ch -eq '`')  { $mode = 'tpl'; $prev = $ch; $i++; continue }
            if ($ch -eq '/' -and $i + 1 -lt $n) {
                $nx = $js[$i + 1]
                if ($nx -eq '/') { while ($i -lt $n -and $js[$i] -ne "`n") { $i++ }; continue }
                if ($nx -eq '*') {
                    $i += 2
                    while ($i + 1 -lt $n -and -not ($js[$i] -eq '*' -and $js[$i + 1] -eq '/')) { $i++ }
                    $i += 2; continue
                }
                if ($prev -eq '' -or $prev -match '[(,=:\[!&|?{};]') {
                    # regex literal - skip to the closing unescaped slash
                    $i++
                    while ($i -lt $n) {
                        if ($js[$i] -eq '\') { $i += 2; continue }
                        if ($js[$i] -eq '/') { $i++; break }
                        $i++
                    }
                    $prev = '/'; continue
                }
            }
            if ($ch -eq '(' -or $ch -eq '{') { $depth++ }
            elseif ($ch -eq ')' -or $ch -eq '}') { $depth--; if ($depth -lt 0) { return $false } }
            $prev = $ch
        } elseif ($mode -eq 'sq') {
            if ($ch -eq '\') { $i++ } elseif ($ch -eq "'") { $mode = 'code'; $prev = $ch }
        } elseif ($mode -eq 'dq') {
            if ($ch -eq '\') { $i++ } elseif ($ch -eq '"') { $mode = 'code'; $prev = $ch }
        } else { # template literal (treated as opaque - fine for this codebase)
            if ($ch -eq '\') { $i++ } elseif ($ch -eq '`') { $mode = 'code'; $prev = $ch }
        }
        $i++
    }
    return $depth -eq 0
}

Write-Host "== PHP syntax check ==" -ForegroundColor Cyan
Get-ChildItem -Path $root -Recurse -Filter *.php | ForEach-Object {
    $out = & php -l $_.FullName 2>&1
    if ($LASTEXITCODE -ne 0) {
        Write-Host "FAIL $($_.FullName)" -ForegroundColor Red
        $out | ForEach-Object { Write-Host "    $_" -ForegroundColor Red }
        $fail = 1
    }
}
Write-Host "PHP check done."

Write-Host "== JS bracket balance check ==" -ForegroundColor Cyan
Get-ChildItem -Path $root -Recurse -Filter *.js | ForEach-Object {
    $js = Get-Content $_.FullName -Raw
    if (-not (Test-JsBalance $js)) {
        Write-Host "FAIL $($_.FullName)" -ForegroundColor Red
        $fail = 1
    }
}
Write-Host "JS check done."

if ($fail -eq 0) {
    Write-Host "All checks passed - safe to push." -ForegroundColor Green
} else {
    Write-Host "Issues found - fix them before pushing." -ForegroundColor Yellow
}
exit $fail

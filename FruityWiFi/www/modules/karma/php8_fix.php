<?php
/**
 * Generic PHP 8 compatibility patcher for FruityWifi modules.
 *
 * Run from inside any module directory on the Pi, e.g.:
 *   sudo php /usr/share/fruitywifi/www/modules/karma/php8_fix.php
 *   sudo php /usr/share/fruitywifi/www/modules/dnsspoof/php8_fix.php
 *
 * Patches index.php and includes/module_action.php in the same directory.
 */

function patch_file(string $path): void
{
    if (!file_exists($path)) {
        echo "$path: not found, skipping.\n";
        return;
    }

    $src  = file_get_contents($path);
    $orig = $src;

    // 1. Short open tags -> <?php  (skip <?php, <?=, <?xml)
    $src = preg_replace('/<\?(?!php|=|xml)(\s)/', '<?php$1', $src);

    // 2. regex_standard() — guard all $_GET/$_POST keys with ?? ''
    $src = preg_replace(
        '/regex_standard\(\s*\$_(GET|POST)\[("[^"]+"|\'[^\']+\')\]\s*,/',
        'regex_standard($_$1[$2] ?? \'\',',
        $src
    );

    // 3. Variable assignments from $_GET/$_POST without null coalescing
    //    e.g.  $foo = $_GET["foo"];  or  $foo = $_POST['foo'];
    $src = preg_replace(
        '/(\$\w+)\s*=\s*\$_(GET|POST)\[("[^"]+"|\'[^\']+\')\];/',
        '$1 = $_$2[$3] ?? \'\';',
        $src
    );

    // 4. htmlspecialchars($data) — null-safe cast
    $src = str_replace(
        'htmlspecialchars($data)',
        'htmlspecialchars((string)($data ?? \'\'))',
        $src
    );

    // 5. explode() where second arg may be null (open_file() returns null on missing file)
    //    Pattern: explode("...", $data)  ->  explode("...", (string)($data ?? ''))
    $src = preg_replace(
        '/explode\(("(?:[^"\\\\]|\\\\.)*"|\'(?:[^\'\\\\]|\\\\.)*\')\s*,\s*\$data\)/',
        'explode($1, (string)($data ?? \'\'))',
        $src
    );

    // 6. filesize() without file_exists() guard
    $src = preg_replace(
        '/\(\s*0\s*<\s*filesize\(\s*(\$\w+)\s*\)\s*\)/',
        '(file_exists($1) && 0 < filesize($1))',
        $src
    );

    // 7. print_r($a) — $a is never assigned in any module, dead debug code
    $src = str_replace(
        'print_r($a);',
        '// print_r($a); // removed: $a was never assigned',
        $src
    );

    // 8. $_GET["tab"] comparisons without null coalescing
    $src = preg_replace(
        '/\$_GET\["tab"\]\s*==\s*(\d+)/',
        '($_GET["tab"] ?? \'\') == $1',
        $src
    );

    if ($src === $orig) {
        echo basename($path) . ": already patched, no changes needed.\n";
    } else {
        file_put_contents($path . '.bak', $orig);
        file_put_contents($path, $src);
        echo basename($path) . ": patched successfully. Backup saved to " . basename($path) . ".bak\n";
    }
}

patch_file(__DIR__ . '/index.php');
patch_file(__DIR__ . '/includes/module_action.php');


$file = __DIR__ . '/index.php';

if (!file_exists($file)) {
    die("ERROR: index.php not found at $file\n");
}

$src = file_get_contents($file);
$orig = $src;

// 1. Short open tags -> <?php
$src = preg_replace('/<\?(?!php|=|xml)(\s)/', '<?php$1', $src);

// 2. regex_standard() calls — guard array keys with ?? ''
$src = str_replace(
    'regex_standard($_POST["newdata"], "msg.php", $regex_extra);',
    'regex_standard($_POST["newdata"] ?? \'\', "msg.php", $regex_extra);',
    $src
);
$src = str_replace(
    'regex_standard($_GET["logfile"], "msg.php", $regex_extra);',
    'regex_standard($_GET["logfile"] ?? \'\', "msg.php", $regex_extra);',
    $src
);
$src = str_replace(
    'regex_standard($_GET["action"], "msg.php", $regex_extra);',
    'regex_standard($_GET["action"] ?? \'\', "msg.php", $regex_extra);',
    $src
);
$src = str_replace(
    'regex_standard($_POST["service"], "msg.php", $regex_extra);',
    'regex_standard($_POST["service"] ?? \'\', "msg.php", $regex_extra);',
    $src
);

// 3. Variable assignments from unguarded array keys
$src = str_replace(
    "\$newdata = \$_POST['newdata'];",
    "\$newdata = \$_POST['newdata'] ?? '';",
    $src
);
$src = str_replace(
    '$logfile = $_GET["logfile"];',
    '$logfile = $_GET["logfile"] ?? \'\';',
    $src
);
$src = str_replace(
    '$action = $_GET["action"];',
    '$action = $_GET["action"] ?? \'\';',
    $src
);
$src = str_replace(
    '$tempname = $_GET["tempname"];',
    '$tempname = $_GET["tempname"] ?? \'\';',
    $src
);
$src = str_replace(
    '$service = $_POST["service"];',
    '$service = $_POST["service"] ?? \'\';',
    $src
);

// 4. htmlspecialchars() — cast to string to handle null from open_file()
$src = str_replace(
    'htmlspecialchars($data)',
    'htmlspecialchars((string)($data ?? \'\'))',
    $src
);

// 5. print_r($a) — $a is never assigned, dead debug code
$src = str_replace(
    'print_r($a);',
    '// print_r($a); // removed: $a was never assigned',
    $src
);

// 6. $_GET["tab"] comparisons — guard with ?? ''
$src = str_replace(
    'if ($_GET["tab"] == 1)',
    'if (($_GET["tab"] ?? \'\') == 1)',
    $src
);
$src = str_replace(
    'if ($_GET["tab"] == 2)',
    'if (($_GET["tab"] ?? \'\') == 2)',
    $src
);
$src = str_replace(
    'if ($_GET["tab"] == 3)',
    'if (($_GET["tab"] ?? \'\') == 3)',
    $src
);
$src = str_replace(
    'if ($_GET["tab"] == 4)',
    'if (($_GET["tab"] ?? \'\') == 4)',
    $src
);
// handle else-if variants too
$src = str_replace(
    'else if ($_GET["tab"] == 2)',
    'else if (($_GET["tab"] ?? \'\') == 2)',
    $src
);
$src = str_replace(
    'else if ($_GET["tab"] == 3)',
    'else if (($_GET["tab"] ?? \'\') == 3)',
    $src
);
$src = str_replace(
    'else if ($_GET["tab"] == 4)',
    'else if (($_GET["tab"] ?? \'\') == 4)',
    $src
);

if ($src === $orig) {
    echo "index.php: No changes needed (already patched or strings not matched).\n";
} else {
    file_put_contents($file . '.bak', $orig);
    file_put_contents($file, $src);
    echo "index.php: Patched successfully. Backup saved to index.php.bak\n";
}

// -----------------------------------------------------------------------
// Patch includes/module_action.php
// -----------------------------------------------------------------------

$file2 = __DIR__ . '/includes/module_action.php';

if (!file_exists($file2)) {
    die("ERROR: includes/module_action.php not found at $file2\n");
}

$src2 = file_get_contents($file2);
$orig2 = $src2;

// 1. Short open tags -> <?php
$src2 = preg_replace('/<\?(?!php|=|xml)(\s)/', '<?php$1', $src2);

// 2. regex_standard() calls — guard array keys with ?? ''
foreach (['service', 'action', 'page', 'install'] as $key) {
    $src2 = str_replace(
        "regex_standard(\$_GET[\"$key\"],",
        "regex_standard(\$_GET[\"$key\"] ?? '',",
        $src2
    );
}

// 3. Variable assignments from unguarded $_GET keys
foreach (['service', 'action', 'page', 'install'] as $key) {
    $src2 = str_replace(
        "\$$key = \$_GET['$key'];",
        "\$$key = \$_GET['$key'] ?? '';",
        $src2
    );
}

// 4. filesize() on potentially missing log file — guard with file_exists()
$src2 = str_replace(
    'if ( 0 < filesize( $mod_logs ) ) {',
    'if ( file_exists($mod_logs) && 0 < filesize( $mod_logs ) ) {',
    $src2
);

if ($src2 === $orig2) {
    echo "includes/module_action.php: No changes needed.\n";
} else {
    file_put_contents($file2 . '.bak', $orig2);
    file_put_contents($file2, $src2);
    echo "includes/module_action.php: Patched successfully. Backup saved to module_action.php.bak\n";
}

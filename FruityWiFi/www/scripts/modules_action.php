<?php 
/*
    Copyright (C) 2013-2014 xtr4nge [_AT_] gmail.com

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/ 
?>
<?php

include "../login_check.php";
include "../config/config.php";
include "../functions.php";

//$bin_danger = "/usr/share/fruitywifi/bin/danger"; //DEPRECATED

// Checking POST & GET variables...
if ($regex == 1) {
    regex_standard($_GET["action"] ?? '', "../msg.php", $regex_extra);
    regex_standard($_GET["module"] ?? '', "../msg.php", $regex_extra);
    regex_standard($_GET["version"] ?? '', "../msg.php", $regex_extra);
}

$action = $_GET["action"] ?? '';
$module = $_GET["module"] ?? '';
$version = $_GET["version"] ?? '';

if ($action == "") {
    header('Location: ../page_modules.php');
    exit;
}

if ($module == "") {
    header('Location: ../page_modules.php');
    exit;
}

if ($action == "install") {
    $modulesDir = "/usr/share/fruitywifi/www/modules";
    $zipUrl     = "https://github.com/xtr4nge/module_{$module}/archive/refs/tags/v{$version}.zip";
    $zipPath    = "{$modulesDir}/module_{$module}-{$version}.zip";
    $extractDir = "{$modulesDir}/module_{$module}-{$version}";
    $destDir    = "{$modulesDir}/{$module}";

    // Download via PHP cURL (no sudo / wget needed)
    $ch = curl_init($zipUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT        => 120,
    ]);
    $zipData   = curl_exec($ch);
    $curlError = curl_errno($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlError || $httpCode !== 200 || empty($zipData)) {
        echo json_encode(["error" => "download-failed", "http" => $httpCode, "curl" => $curlError]);
        exit;
    }

    if (file_put_contents($zipPath, $zipData) === false) {
        echo json_encode(["error" => "write-failed"]);
        exit;
    }

    // Extract via ZipArchive (no sudo / unzip needed)
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        unlink($zipPath);
        echo json_encode(["error" => "unzip-failed"]);
        exit;
    }
    $zip->extractTo($modulesDir);
    $zip->close();
    unlink($zipPath);

    // GitHub extracts as module_{name}-{version}/, rename to {name}/
    if (is_dir($extractDir)) {
        if (is_dir($destDir)) {
            exec_fruitywifi("rm -rf " . escapeshellarg($destDir));
        }
        rename($extractDir, $destDir);
    } else {
        echo json_encode(["error" => "rename-failed", "expected" => $extractDir]);
        exit;
    }

    echo json_encode(["0" => "mod-installed"]);
    exit;
}

if ($action == "remove") {
    $exec = "apt-get -y remove fruitywifi-module-$module";
    exec_fruitywifi($exec);
    
    $exec = "rm -R /usr/share/fruitywifi/www/modules/$module";
    //exec("$bin_danger \"" . $exec . "\"" ); //DEPRECATED
    exec_fruitywifi($exec);
    
    $output[0] = "removed";
    echo json_encode($output);
    exit;
}

if ($action == "install-deb") {
    $exec = "apt-get -y install fruitywifi-module-$module";
    exec_fruitywifi($exec);
    
    $output[0] = "deb-mod-installed";
    echo json_encode($output);
    exit;
}

if ($action == "remove-deb") {
    $exec = "apt-get -y remove fruitywifi-module-$module";
    exec_fruitywifi($exec);
    
    $exec = "rm -R /usr/share/fruitywifi/www/modules/$module";
    exec_fruitywifi($exec);
    
    $output[0] = "remove-deb";
    echo json_encode($output);
    exit;
}

if (isset($_GET["show"])) {
    header('Location: ../page_modules.php?show');
    exit;
} else {
    header('Location: ../page_modules.php');
}

if (isset($_GET["show-deb"])) {
    header('Location: ../page_modules.php?show-deb');
} else {
    header('Location: ../page_modules.php');
}

?>
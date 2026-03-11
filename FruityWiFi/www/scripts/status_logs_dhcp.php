<?php
include "../config/config.php";

$filename = "$log_path/dhcp.leases";
$data = "";
if (file_exists($filename) && filesize($filename) > 0) {
	$fh = fopen($filename, "r");
	if ($fh !== false) {
		$data = fread($fh, filesize($filename));
		if ($data === false) { $data = ""; }
		fclose($fh);
	}
}
$data = explode("\n",$data);

for ($i=0; $i < count($data); $i++) {
	$tmp = explode(" ", $data[$i]);
	$output[] = $tmp[2] . " " . $tmp[1] . " " . $tmp[3];
	//echo $tmp[2] . " " . $tmp[3] . " " . $tmp[4] . "<br>";
}

echo json_encode($output);
?>

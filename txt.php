<?php
$f = __DIR__ . "/{$_GET['name']}.txt";
if ( file_exists($f) ) {
	echo file_get_contents($f);
	exit;
}
echo json_encode(["code"=>404, "message"=>"file not exists", "f"=>$f ]);
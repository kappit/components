<?php
declare(strict_types = 1);
const APP_PATH = __DIR__ . '\..\..\App\\';
function input () : string
{
	$handle = fopen('php://stdin', 'r');
	$line = fgets($handle);
	fclose($handle);
	$lineSanitized = filter_var($line, FILTER_SANITIZE_STRING);
	$lineRemoveSpaces = preg_replace('/\s+/', '', $lineSanitized);

	return $lineRemoveSpaces;
}

function createFolder (string $folder) : void
{
	$oldmask = umask(0);
	mkdir(APP_PATH . $folder, 0777, true);
	umask($oldmask);
}

function createJsonFile (string $path, string $file, object $json) : void
{
	$fp = fopen(APP_PATH . "\\$path\\$file.json", 'w');
	fwrite($fp, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
	fclose($fp);
}

$welcome = 'Hi! This utility will setup the required folders';
$welcome .= PHP_EOL;
$welcome .= 'and configure a configuration file for you for the project.';
$welcome .= PHP_EOL;
$welcome .= PHP_EOL;
echo $welcome;
$config = new stdClass();
foreach ([
(object) [
'name' => 'developmentHostname',
'message' => 'development hostname: (ex: localhost:8000/MyApp) '
],
(object) [
'name' => 'productionHostname',
'message' => 'production hostname: (ex: www.myapp.com) '
]
] as $question) {
	echo $question->message;
	$property = $question->name;
	$config->$property = input();
}
foreach ([
'Components',
'Models',
'Routes',
'Services',
'StronglyTypedViews',
'Templates'
] as $folder) {
	createFolder($folder);
}
createFolder('Config');
createJsonFile('Config', 'config', $config);
echo 'Done - have fun!';
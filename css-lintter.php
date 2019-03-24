<?php
require_once 'vendor/autoload.php';
include('parser.php');

$phpCli = new Commando\Command();

//Validation that a css file was passed
$phpCli->option()
    ->require()
    ->describe('A css file to be parsed');

$phpCli->option('f')
    ->aka('fix')
    ->describe('fix the issues found in the file')
    ->boolean();


$parser = new CssParser();
$parser->loadFile($argv[1]);
$parsedCss = $parser->parse();
//var_dump($parser->parsed['main']);
foreach ($parser->parsed['main'] as $selector => $propertyArray) {
    ksort($parser->parsed['main'][$selector]);
}

$css = $parser->glue();
$sourcePath = explode('.css', $argv[1]);
file_put_contents($sourcePath[0] . '_' . microtime() . '.css', $css);

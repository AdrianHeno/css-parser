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
var_dump($parser);

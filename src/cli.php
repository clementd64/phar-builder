<?php

namespace PharBuilder;

require_once 'phar-builder.php';
require_once 'args.php';

$args = parseArgs($argv);

if ($argc === 1 || ($args['-h'] ?? $args['--help'] ?? FALSE)) {
    // print help message
    require_once 'help.txt';
    exit;
}

// get needed arguments
$mainFile = $args['-m'] ?? $args['--mainFile'] ?? NULL;
$loaderFile = $args['-l'] ?? $args['--loaderFile'] ?? NULL;
$dirName = $args[1] ?? 'src/';
$outFile = $args[2] ?? 'dist.phar';

// add final '/' in dir name if not present
if ($dirName[strlen($dirName)-1] !== '/') $dirName .= '/';

try {
    $phar = new PharBuilder($dirName, $outFile);
    if ($mainFile !== NULL && $loaderFile !== NULL)
        $phar->createStubMainAndLoader($mainFile, $loaderFile);
    elseif ($mainFile !== NULL)
        $phar->createStubMain($mainFile);
    elseif ($loaderFile !== NULL)
        $phar->createStubLoader($loaderFile);
    else
        $phar->createDefaultStub();
    $phar->createPhar()->printSize();
} catch (Exception $e) {
    echo $e->getMessage();
}
<?php

namespace PharBuilder;

function parseArgs(array $argv): array {
    $args = ['count' => 0];
    $l = count($argv);
    for ($i = 0; $i < $l; $i++) {
        // if the argument is an option
        if ($argv[$i][0] === '-') {
            // if a next argument exist and is not an option
            if ($i + 1 < $l && $argv[$i + 1][0] !== '-')
                $args[$argv[$i]] = $argv[++$i];
            // if don't have next argument or if it's an option
            else
                $args[$argv[$i]] = TRUE;
        // if the argument is not an option
        } else {
            $args[] = $argv[$i];
            // increse number of argument
            $args['count']++;
        }
    }
    return $args;
}
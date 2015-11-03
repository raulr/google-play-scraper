<?php

return Symfony\CS\Config\Config::create()
    ->fixers(array(
        '-psr0',
        'psr4',
    ))
    ->finder(
        Symfony\CS\Finder\DefaultFinder::create()
            ->exclude('vendor')
            ->in(__DIR__)
    )
;
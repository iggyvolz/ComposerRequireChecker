<?php

namespace ComposerRequireChecker\FileLocator;

use Traversable;
use Webmozart\Glob\Glob;

class LocateFilesByGlobPattern
{

    /**
     * @param string[] $globPatterns a list of glob patterns to find files in
     * @param string $rootDir the root directory that should be used when patterns are relative paths
     * @return Traversable<string> the files found by the given glob patterns
     * @see https://github.com/webmozart/glob
     */
    public function __invoke(array $globPatterns = [], string $rootDir): Traversable
    {
        foreach ($globPatterns as $globPattern) {
            yield from Glob::glob(rtrim($rootDir, '/') . '/' . $globPattern);
        }
    }

}

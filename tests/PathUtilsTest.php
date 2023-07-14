<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require __DIR__ . '/../src/common/utils/path.php';

final class PathUtilsTest extends TestCase
{
    public function testPathJoin()
    {
        // "normal" path
        $paths = ['/some/valid/path/', '/another/normal/path/'];
        $this->assertEquals('/some/valid/path/another/normal/path/', path_join($paths[0], $paths[1]));
    }

}
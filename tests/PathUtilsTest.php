<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

use function Flyer\Utils\Path\path_join;

final class PathUtilsTest extends TestCase
{
    public function testPathJoin()
    {
        // "normal" path
        $paths = ['/some/valid/path/', '/another/normal/path/'];
        $this->assertEquals('/some/valid/path/another/normal/path/', path_join($paths[0], $paths[1]));

        $paths = ['/some/valid/path/.', '/another/normal/path/.'];
        $this->assertEquals('/some/valid/path/another/normal/path', path_join($paths[0], $paths[1]));

        // some crazy paths
        $paths = ['/some/////crazy/.//path/.', '/////youshould/be/able/to/handle/this///////////normal/path/.'];
        $this->assertEquals('/some/crazy/path/youshould/be/able/to/handle/this/normal/path', path_join($paths[0], $paths[1]));
    }

}
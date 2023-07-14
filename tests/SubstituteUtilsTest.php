<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require __DIR__ . '/../src/common/utils/substitute.php';

final class SubstituteUtilsTest extends TestCase
{
    public function testMacroSubstitute()
    {
        $arr = [
            'value' => '${FLYER_SOMETHING} IN THE ${FLYER_DARK}'
        ];
        $this->assertEquals(
            macro_subtitute($arr['value'], [
                'FLYER_SOMETHING' => 'FOO',
                'FLYER_DARK' => 'BAR'
            ]),
            'FOO IN THE BAR'
        );
    }

    public function testMacroSubstituteArrDeep()
    {
        $arr = [
            'value' => '${FLYER_SOMETHING} IN THE ${FLYER_DARK}',
            'nothing' => 'qux',
            'bar' => '${FLYER_SOMETHING} hoho ${FLYER_baz}'
        ];

        $this->assertEquals(
            macro_subtitute_arr_deep($arr, [
                'FLYER_SOMETHING' => 'FOO',
                'FLYER_DARK' => 'BAR',
                'FLYER_baz' => 'lemawo',
            ]),
            [
                'value' => 'FOO IN THE BAR',
                'nothing' => 'qux',
                'bar' => 'FOO hoho lemawo',
            ]
        );
    }

}
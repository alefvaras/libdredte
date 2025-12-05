<?php

declare(strict_types=1);

/**
 * Derafu: Support - Essential PHP Utilities.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsSupport\Arr;

use Derafu\Support\Arr;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Arr::class)]
class ArrTest extends TestCase
{
    #[Test]
    #[DataProvider('provideAutoCastData')]
    public function shouldAutoCastArrayValues(
        array $input,
        mixed $emptyValue,
        array $expected
    ): void {
        $result = Arr::cast($input, $emptyValue);
        $this->assertSame($expected, $result);
    }

    public static function provideAutoCastData(): array
    {
        return [
            'simple types' => [
                ['1', '2.5', 'text', ''],
                null,
                [1, 2.5, 'text', null],
            ],
            'nested arrays' => [
                ['a' => ['1', '2.5'], 'b' => ['text', '']],
                '',
                ['a' => [1, 2.5], 'b' => ['text', '']],
            ],
            'custom empty value' => [
                ['1', '', '2', ''],
                'EMPTY',
                [1, 'EMPTY', 2, 'EMPTY'],
            ],
            'trim values' => [
                [' 1 ', ' text '],
                null,
                [1, 'text'],
            ],
        ];
    }

    #[Test]
    #[DataProvider('provideTableToAssociativeData')]
    public function shouldConvertTableToAssociativeArray(
        array $table,
        array $expected
    ): void {
        $result = Arr::tableToAssociative($table);
        $this->assertSame($expected, $result);
    }

    public static function provideTableToAssociativeData(): array
    {
        return [
            'simple table' => [
                [['key1', 'value1'], ['key2', 'value2']],
                ['key1' => 'value1', 'key2' => 'value2'],
            ],
            'mixed types' => [
                [['key1', 1], ['key2', true]],
                ['key1' => 1, 'key2' => true],
            ],
        ];
    }

    #[Test]
    #[DataProvider('provideGroupToTableData')]
    public function shouldConvertGroupedArrayToTable(
        array $input,
        ?array $keys,
        array $expected
    ): void {
        $result = Arr::groupToTable($input, $keys);
        $this->assertSame($expected, $result);
    }

    public static function provideGroupToTableData(): array
    {
        return [
            'simple groups' => [
                [
                    'key1' => [1, 2, 3],
                    'key2' => [4, 5, 6],
                ],
                null,
                [
                    ['key1' => 1, 'key2' => 4],
                    ['key1' => 2, 'key2' => 5],
                    ['key1' => 3, 'key2' => 6],
                ],
            ],
            'specific keys' => [
                [
                    'key1' => [1, 2],
                    'key2' => [3, 4],
                    'key3' => [5, 6],
                ],
                ['key1', 'key3'],
                [
                    ['key1' => 1, 'key3' => 5],
                    ['key1' => 2, 'key3' => 6],
                ],
            ],
            'uneven groups' => [
                [
                    'key1' => [1, 2],
                    'key2' => [3],
                ],
                null,
                [
                    ['key1' => 1, 'key2' => 3],
                    ['key1' => 2, 'key2' => null],
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('provideTreeData')]
    public function shouldConvertArrayToTree(
        array $items,
        string $parentField,
        string $childrenField,
        mixed $parentId,
        array $expected
    ): void {
        $result = Arr::toTree($items, $parentField, $childrenField, $parentId);
        $this->assertSame($expected, $result);
    }

    public static function provideTreeData(): array
    {
        return [
            'simple tree' => [
                [
                    1 => ['name' => 'Parent', 'parent_id' => null],
                    2 => ['name' => 'Child', 'parent_id' => 1],
                ],
                'parent_id',
                'children',
                null,
                [
                    1 => [
                        'name' => 'Parent',
                        'children' => [
                            2 => ['name' => 'Child', 'children' => []],
                        ],
                    ],
                ],
            ],
            'multiple levels' => [
                [
                    1 => ['name' => 'Root', 'parent_id' => null],
                    2 => ['name' => 'Child1', 'parent_id' => 1],
                    3 => ['name' => 'Child2', 'parent_id' => 1],
                    4 => ['name' => 'Grandchild', 'parent_id' => 2],
                ],
                'parent_id',
                'children',
                null,
                [
                    1 => [
                        'name' => 'Root',
                        'children' => [
                            2 => [
                                'name' => 'Child1',
                                'children' => [
                                    4 => ['name' => 'Grandchild', 'children' => []],
                                ],
                            ],
                            3 => ['name' => 'Child2', 'children' => []],
                        ],
                    ],
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('provideTreeToListData')]
    public function shouldConvertTreeToList(
        array $tree,
        string $nameField,
        string $childrenField,
        array $expected
    ): void {
        $result = Arr::treeToList($tree, $nameField, $childrenField);
        $this->assertSame($expected, $result);
    }

    public static function provideTreeToListData(): array
    {
        return [
            'simple tree' => [
                [
                    1 => [
                        'name' => 'Parent',
                        'children' => [
                            2 => ['name' => 'Child', 'children' => []],
                        ],
                    ],
                ],
                'name',
                'children',
                [
                    1 => ['name' => 'Parent', 'level' => 0],
                    2 => ['name' => 'Child', 'level' => 1],
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('provideIdAttributeData')]
    public function shouldAddIdAttribute(
        array $input,
        string $idField,
        array $expected
    ): void {
        $result = Arr::ensureIdInElements($input, $idField);
        $this->assertSame($expected, $result);
    }

    public static function provideIdAttributeData(): array
    {
        return [
            'numeric keys' => [
                [
                    1 => ['name' => 'Item 1'],
                    2 => ['name' => 'Item 2'],
                ],
                'id',
                [
                    1 => ['id' => 1, 'name' => 'Item 1'],
                    2 => ['id' => 2, 'name' => 'Item 2'],
                ],
            ],
            'string keys' => [
                [
                    'a' => ['name' => 'Item A'],
                    'b' => ['name' => 'Item B'],
                ],
                'key',
                [
                    'a' => ['key' => 'a', 'name' => 'Item A'],
                    'b' => ['key' => 'b', 'name' => 'Item B'],
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('provideArrayPathData')]
    public function shouldEnsureArrayAtPath(
        array $input,
        string $path,
        array $expected
    ): void {
        Arr::ensureArrayAtPath($input, $path);
        $this->assertSame($expected, $input);
    }

    public static function provideArrayPathData(): array
    {
        return [
            'simple path' => [
                ['key' => 'value'],
                'key',
                ['key' => ['value']],
            ],
            'nested path' => [
                ['a' => ['b' => 'value']],
                'a.b',
                ['a' => ['b' => ['value']]],
            ],
            'nested path associative array' => [
                ['a' => ['b' => ['key' => 'value']]],
                'a.b',
                ['a' => ['b' => [['key' => 'value']]]],
            ],
            'already array' => [
                ['key' => ['value']],
                'key',
                ['key' => ['value']],
            ],
        ];
    }

    #[Test]
    #[DataProvider('provideSubsetsData')]
    public function shouldGenerateSubsets(
        array $input,
        int $minLength,
        array $expected
    ): void {
        $result = Arr::subsets($input, $minLength);
        sort($result); // Normalize order for comparison
        sort($expected);
        $this->assertSame($expected, $result);
    }

    public static function provideSubsetsData(): array
    {
        return [
            'simple array' => [
                [1, 2],
                1,
                [[1], [2], [1, 2]],
            ],
            'with min length' => [
                [1, 2, 3],
                2,
                [[1, 2], [1, 3], [2, 3], [1, 2, 3]],
            ],
        ];
    }
}

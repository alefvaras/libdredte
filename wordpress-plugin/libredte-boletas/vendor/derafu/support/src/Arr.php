<?php

declare(strict_types=1);

/**
 * Derafu: Support - Essential PHP Utilities.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Support;

use InvalidArgumentException;
use SimpleXMLElement;

/**
 * Array manipulation and utility class.
 *
 * This class provides a collection of static methods for array manipulation,
 * including advanced merging, type casting, tree operations, and data structure
 * transformations.
 */
final class Arr
{
    /**
     * Flattens a multi-dimensional array into a single level array with dot
     * notation keys.
     *
     * @param array $array The array to flatten.
     * @param string $prefix The prefix to use for flattened keys.
     * @return array The flattened array.
     */
    public static function dot(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $newKey = $prefix ? "{$prefix}.{$key}" : $key;
            if (is_array($value)) {
                $result = array_merge($result, self::dot($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Converts a flat array with dot notation keys back to a multi-dimensional
     * array.
     *
     * @param array $array The flat array with dot notation keys.
     * @return array The multi-dimensional array.
     */
    public static function nested(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (str_contains($key, '.')) {
                $keys = explode('.', $key);
                $current = &$result;

                foreach ($keys as $k) {
                    if (!isset($current[$k]) || !is_array($current[$k])) {
                        $current[$k] = [];
                    }
                    $current = &$current[$k];
                }

                $current = $value;
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Recursively casts array values automatically based on their content.
     *
     * Rules applied:
     *
     *   - Trims strings.
     *   - Converts numeric strings to their appropriate type (int or float).
     *   - Can replace empty strings with a specified value.
     *
     * @param array $array The array to process.
     * @param mixed $emptyValue Value to use for empty strings.
     * @return array The processed array with cast values.
     */
    public static function cast(array $array, mixed $emptyValue = ''): array
    {
        array_walk_recursive($array, function (&$value, $key, $emptyValue) {
            if (is_string($value)) {
                $value = trim($value);
                if ($value === '') {
                    $value = $emptyValue;
                } elseif (is_numeric($value)) {
                    $value = str_contains($value, '.')
                        ? (float) $value
                        : (int) $value
                    ;
                }
            }
        }, $emptyValue);

        return $array;
    }

    /**
     * Ensures that the value at a specific path in an array is an array with
     * numeric indexes (not an associative array).
     *
     * @param array $array The input array.
     * @param string $path Dot notation path to the target location.
     * @return void
     */
    public static function ensureArrayAtPath(array &$array, string $path): void
    {
        $keys = explode('.', $path);
        $current = &$array;

        foreach ($keys as $key) {
            if (!isset($current[$key])) {
                return;
            }
            $current = &$current[$key];
        }

        if (!is_array($current) || (!empty($current) && !isset($current[0]))) {
            $current = [$current];
        }
    }

    /**
     * Ensure an ID attribute in each array element using the array key.
     *
     * @param array<int|string,array> $data Array of arrays.
     * @param string $idField Name of the field to store the ID.
     * @return array The modified array with IDs added.
     */
    public static function ensureIdInElements(array $data, string $idField): array
    {
        return array_combine(
            array_keys($data),
            array_map(
                fn ($id, $item) => array_merge(
                    [$idField => $id],
                    $item
                ),
                array_keys($data),
                array_values($data)
            )
        );
    }

    /**
     * Generates all possible subsets of an array with a minimum length.
     *
     * @param array $array Input array.
     * @param int $minLength Minimum length for subsets.
     * @return array Array of all possible subsets.
     */
    public static function subsets(array $array, int $minLength = 1): array
    {
        $count = count($array);
        $members = pow(2, $count);
        $subsets = [];

        for ($i = 0; $i < $members; $i++) {
            $subset = [];
            for ($j = 0; $j < $count; $j++) {
                if ($i & (1 << $j)) {
                    $subset[] = $array[$j];
                }
            }
            if (count($subset) >= $minLength) {
                $subsets[] = $subset;
            }
        }

        return $subsets;
    }

    /**
     * Creates a hierarchical tree from a flat array using parent references.
     *
     * @param array $items Array of items.
     * @param string $parentField Name of the field containing the parent reference.
     * @param string $childrenField Name of the field to store children.
     * @param mixed $parentId Value indicating root level items.
     * @return array The hierarchical tree.
     */
    public static function toTree(
        array $items,
        string $parentField,
        string $childrenField,
        mixed $parentId = null
    ): array {
        $tree = [];

        foreach ($items as $key => $item) {
            if (!array_key_exists($parentField, $item)) {
                continue;
            }

            if ($item[$parentField] === $parentId) {
                $current = $item;
                unset($current[$parentField]);
                $current[$childrenField] = self::toTree(
                    $items,
                    $parentField,
                    $childrenField,
                    $key
                );
                $tree[$key] = $current;
            }
        }

        return $tree;
    }

    /**
     * Converts a hierarchical tree to a flat list with level indicators.
     *
     * @param array $tree The tree structure.
     * @param string $nameField Field containing the item name.
     * @param string $childrenField Field containing child items.
     * @param int $level Current level (used internally).
     * @param array $result Accumulated result (used internally).
     * @return array The flattened list with level information.
     */
    public static function treeToList(
        array $tree,
        string $nameField,
        string $childrenField,
        int $level = 0,
        array &$result = []
    ): array {
        foreach ($tree as $key => $item) {
            if (!isset($item[$nameField])) {
                continue;
            }

            $result[$key] = [
                'name' => $item[$nameField],
                'level' => $level,
            ];

            if (!empty($item[$childrenField])) {
                self::treeToList(
                    $item[$childrenField],
                    $nameField,
                    $childrenField,
                    $level + 1,
                    $result
                );
            }
        }

        return $result;
    }

    /**
     * Transforms a grouped array into a table format.
     *
     * Converts an array like:
     *
     *   [
     *     'key1' => [1,2,3],
     *     'key2' => [4,5,6]
     *   ]
     *
     * into:
     *
     *   [
     *     ['key1' => 1, 'key2' => 4],
     *     ['key1' => 2, 'key2' => 5],
     *     ['key1' => 3, 'key2' => 6],
     *   ]
     *
     * @param array $array The grouped array to transform.
     * @param array|null $keys Specific keys to extract (null for all keys).
     * @return array The resulting table array.
     */
    public static function groupToTable(array $array, ?array $keys = null): array
    {
        $keys = $keys ?? array_keys($array);
        if (empty($keys)) {
            return [];
        }

        // Find the maximum length among all groups.
        $maxLength = max(array_map(
            'count',
            array_intersect_key($array, array_flip($keys))
        ));

        $result = [];
        for ($i = 0; $i < $maxLength; $i++) {
            $row = [];
            foreach ($keys as $key) {
                $row[$key] = $array[$key][$i] ?? null;
            }
            $result[] = $row;
        }

        return $result;
    }

    /**
     * Converts a table of N rows and 2 columns into an associative array.
     *
     * @param array $table The table to convert, where each row has exactly 2 columns.
     * @return array The resulting associative array.
     * @throws InvalidArgumentException If any row doesn't have exactly 2 columns.
     */
    public static function tableToAssociative(array $table): array
    {
        $result = [];
        foreach ($table as $index => $row) {
            if (!is_array($row) || count($row) !== 2) {
                throw new InvalidArgumentException(
                    "Row {$index} must have exactly 2 columns"
                );
            }
            $result[array_shift($row)] = array_shift($row);
        }

        return $result;
    }

    /**
     * Converts an array to XML.
     *
     * @param array $array The array to convert.
     * @param string $rootElement The name of the root XML element.
     * @return string The XML string.
     */
    public static function toXml(array $array, string $rootElement = 'root'): string
    {
        $xml = new SimpleXMLElement(
            "<?xml version=\"1.0\"?><{$rootElement}></{$rootElement}>"
        );
        self::arrayToXml($array, $xml);

        return $xml->asXML();
    }

    /**
     * Helper method to convert array to XML recursively.
     *
     * @param array $array Array to convert.
     * @param \SimpleXMLElement $xml XML element being built.
     * @return void
     */
    private static function arrayToXml(array $array, \SimpleXMLElement $xml): void
    {
        foreach ($array as $key => $value) {
            $key = is_numeric($key) ? 'item' : $key;

            if (is_array($value)) {
                $child = $xml->addChild($key);
                self::arrayToXml($value, $child);
            } else {
                $xml->addChild($key, htmlspecialchars((string)$value));
            }
        }
    }
}

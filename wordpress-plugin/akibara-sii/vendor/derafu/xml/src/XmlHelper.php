<?php

declare(strict_types=1);

/**
 * Derafu: XML - Library for XML manipulation.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Xml;

use DOMDocument;
use DOMNodeList;
use DOMXPath;
use InvalidArgumentException;

/**
 * Class with utilities to work with XML strings.
 */
final class XmlHelper
{
    /**
     * Executes an XPath query on an XML document.
     *
     * @param string|DOMDocument $xml XML document to query.
     * @param string $expression XPath expression to execute on the XML.
     * @return DOMNodeList Nodes resulting from the XPath query.
     */
    public static function xpath(
        string|DOMDocument $xml,
        string $expression
    ): DOMNodeList {
        if (is_string($xml)) {
            $document = new DOMDocument();
            $document->loadXml($xml);
        } else {
            $document = $xml;
        }

        $xpath = new DOMXPath($document);
        $result = @$xpath->query($expression);

        if ($result === false) {
            throw new InvalidArgumentException(sprintf(
                'Invalid XPath expression: %s',
                $expression
            ));
        }

        return $result;
    }

    /**
     * Sanitizes the values that are assigned to the tags of the XML.
     *
     * @param string $value Text to assign as value to the XML node.
     * @return string Sanitized text.
     */
    public static function sanitize(string $value): string
    {
        // If no text is passed or it is a number, do nothing.
        if (!$value || is_numeric($value)) {
            return $value;
        }

        // Remove control characters (ASCII 0x00-0x1F and 0x7F) that can cause
        // problems in XML-DSIG.
        $value = preg_replace('/[\x00-\x1F\x7F]/', '', $value);

        // Convert "predefined entities" of XML.
        $replace = [
            '&amp;' => '&',
            '&#38;' => '&',
            '&lt;' => '<',
            '&#60;' => '<',
            '&gt;' => '>',
            '&#62' => '>',
            '&quot;' => '"',
            '&#34;' => '"',
            '&apos;' => '\'',
            '&#39;' => '\'',
        ];
        $value = str_replace(array_keys($replace), array_values($replace), $value);

        // This is on purpose, the replacements must be done again.
        $value = str_replace('&', '&amp;', $value);

        /*$value = str_replace(
            ['"', '\''],
            ['&quot;', '&apos;'],
            $value
        );*/

        // Return the sanitized text.
        return $value;
    }

    /**
     * Fixes the entities '&apos;' and '&quot;' in the XML.
     *
     * The correction is done in the content of the XML tags, and also in the
     * attributes of the tags.
     *
     * @param string $xml XML to fix.
     * @return string Fixed XML.
     */
    public static function fixEntities(string $xml): string
    {
        $replace = [
            '\'' => '&apos;',
            '"' => '&quot;',
        ];
        $replaceFrom = array_keys($replace);
        $replaceTo = array_values($replace);

        $newXml = '';
        $n_chars = strlen($xml);
        $convert = false;
        $inAttribute = false;
        $attributeDelimiter = null;

        for ($i = 0; $i < $n_chars; ++$i) {
            $char = $xml[$i];

            // Detect when we enter/exit attribute values.
            if (
                !$convert
                && $char === '='
                && $i + 1 < $n_chars
                && ($xml[$i + 1] === '"' || $xml[$i + 1] === "'")
            ) {
                $inAttribute = true;
                $attributeDelimiter = $xml[$i + 1];
                $i++; // Skip the delimiter.
                $newXml .= $char . $attributeDelimiter;
                continue;
            }

            // Detect when we exit attribute values.
            if ($inAttribute && $char === $attributeDelimiter) {
                $inAttribute = false;
                $attributeDelimiter = null;
                $newXml .= $char;
                continue;
            }

            // Toggle convert mode for tag content.
            if ($char === '>') {
                $convert = true;
            }
            if ($char === '<') {
                $convert = false;
            }

            // Only convert entities if we're in tag content and not in an
            // attribute.
            if ($convert && !$inAttribute) {
                $newXml .= str_replace($replaceFrom, $replaceTo, $char);
            } else {
                $newXml .= $char;
            }
        }

        return $newXml;
    }
}

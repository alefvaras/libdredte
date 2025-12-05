<?php

declare(strict_types=1);

/**
 * Derafu: XML - Library for XML manipulation.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Xml\Service;

use Derafu\Xml\Contract\XmlDecoderInterface;
use Derafu\Xml\Contract\XmlDocumentInterface;
use DOMElement;
use DOMNodeList;
use DOMText;

/**
 * Class that creates a PHP array from an XML document.
 */
final class XmlDecoder implements XmlDecoderInterface
{
    /**
     * {@inheritDoc}
     */
    public function decode(
        XmlDocumentInterface|DOMElement $documentElement,
        ?array &$data = null,
        bool $twinsAsArray = false
    ): array {
        // If no tagElement is passed, search one, if not one is obtained, stop
        // the generation.
        $tagElement = $documentElement instanceof DOMElement
            ? $documentElement
            : $documentElement->getDocumentElement()
        ;
        if ($tagElement === null) {
            return [];
        }

        // Index in the array that represents the tag. Also it is a shorter
        // variable name :)
        $key = $tagElement->tagName;

        // If there is no destination array for the data, create an array with
        // the index of the main node with an empty value.
        if ($data === null) {
            //$data = [$key => self::getEmptyValue()];
            $data = [$key => null];
        }

        // If the tagElement has attributes, add them to the array within the
        // special '@attributes' index.
        if ($tagElement->hasAttributes()) {
            $data[$key]['@attributes'] = [];
            foreach ($tagElement->attributes as $attribute) {
                $data[$key]['@attributes'][$attribute->name] = $attribute->value;
            }
        }

        // If the tagElement has child nodes, add them to the value of the tag.
        if ($tagElement->hasChildNodes()) {
            self::arrayAddChilds(
                $data,
                $tagElement,
                $tagElement->childNodes,
                $twinsAsArray
            );
        }

        // Return the data of the XML document as an array.
        return $data;
    }

    /**
     * Adds child nodes of an XML document to a PHP array.
     *
     * @param array &$data Array where the child nodes will be added.
     * @param DOMElement $tagElement Parent node from which the child nodes will
     * be extracted.
     * @param DOMNodeList $childs List of child nodes of the parent node.
     * @param bool $twinsAsArray Indicates if the twin nodes should be treated
     * as an array.
     * @return void
     */
    private function arrayAddChilds(
        array &$data,
        DOMElement $tagElement,
        DOMNodeList $childs,
        bool $twinsAsArray,
    ): void {
        $key = $tagElement->tagName;
        // Loop through each of the child nodes.
        foreach ($childs as $child) {
            if ($child instanceof DOMText) {
                $textContent = trim($child->textContent);
                if ($textContent !== '') {
                    if ($tagElement->hasAttributes()) {
                        $data[$key]['@value'] = $textContent;
                    } elseif ($childs->length === 1 && empty($data[$key])) {
                        $data[$key] = $textContent;
                    } else {
                        $array[$key]['@value'] = $textContent;
                    }
                }
            } elseif ($child instanceof DOMElement) {
                $n_twinsNodes = self::nodeCountTwins(
                    $tagElement,
                    $child->tagName
                );
                if ($n_twinsNodes === 1) {
                    if ($twinsAsArray) {
                        self::decode($child, $data);
                    } else {
                        self::decode($child, $data[$key]);
                    }
                } else {
                    // Create a list for the child node, because it has several
                    // equal nodes in the XML.
                    if (!isset($data[$key][$child->tagName])) {
                        $data[$key][$child->tagName] = [];
                    }

                    // Check if the child node is scalar. If it is, add it
                    // directly to the list.
                    $textContent = trim($child->textContent);
                    if ($textContent !== '') {
                        $data[$key][$child->tagName][] = $textContent;
                    }
                    // If the child node is scalar, not a list of nodes, it is
                    // built as if it were a normal array with the call to
                    // decode().
                    else {
                        $siguiente = count($data[$key][$child->tagName]);
                        $data[$key][$child->tagName][$siguiente] = [];
                        self::decode(
                            $child,
                            $data[$key][$child->tagName][$siguiente],
                            true
                        );
                    }
                }
            }
        }
    }

    /**
     * Counts the nodes with the same name as the child nodes of a DOMElement.
     *
     * @param DOMElement $dom Element where the nodes will be searched.
     * @param string $tagName Name of the tag to count.
     * @return int Quantity of nodes with the same name.
     */
    private function nodeCountTwins(DOMElement $dom, string $tagName): int
    {
        $twins = 0;
        foreach ($dom->childNodes as $child) {
            if ($child instanceof DOMElement && $child->tagName === $tagName) {
                $twins++;
            }
        }

        return $twins;
    }
}

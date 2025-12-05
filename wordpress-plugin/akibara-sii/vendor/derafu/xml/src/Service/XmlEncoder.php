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

use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\Contract\XmlEncoderInterface;
use Derafu\Xml\XmlDocument;
use Derafu\Xml\XmlHelper;
use DOMElement;
use DOMNode;
use InvalidArgumentException;

/**
 * Class that creates an XML document from a PHP array.
 */
final class XmlEncoder implements XmlEncoderInterface
{
    /**
     * Rules for converting a PHP array to XML and vice versa.
     *
     * @var array
     */
    private array $rules = [
        // How to process the values of the nodes.
        'node_values' => [
            // Values that make the node not be generated (are skipped).
            'skip_generation' => [null, false, []],
            // Values that generate an empty node.
            'generate_empty' => ['', true],
        ],
    ];

    /**
     * {@inheritDoc}
     */
    public function encode(
        array $data,
        ?array $namespace = null,
        ?DOMElement $parent = null,
        ?XmlDocumentInterface $doc = null
    ): XmlDocumentInterface {
        // If there is no complete XML document (from root, not a node), then
        // it is created, since it will be needed to create the future nodes.
        if ($doc === null) {
            $doc = new XmlDocument();
        }

        // If there is no parent element, then it is being requested to create
        // the XML document from 0 (from the root node).
        if ($parent === null) {
            $parent = $doc;
        }

        // Iterate the first level of the array to find the tags that must be
        // added to the XML document.
        foreach ($data as $key => $value) {

            // If the index is '@attributes' then the value of this index is an
            // array where the key is the name of the attribute of the tag of
            // $parent and the value is the value of the attribute.
            if ($key === '@attributes') {
                // Only attributes are added if the value is an array.
                if (is_array($value)) {
                    // In the first iteration of recursion it must be checked
                    // that $parent is a DOMElement. And only in that case
                    // continue.
                    if ($parent instanceof DOMElement) {
                        $this->nodeAddAttributes($parent, $value);
                    }
                }
            }

            // If the index is '@value' then the value must be assigned directly
            // to the node, since it is a scalar (not an array with child nodes).
            // This case is usually used when creating a node that must have a
            // value and attributes.
            elseif ($key === '@value') {
                if (!$this->skipValue($value)) {
                    $parent->nodeValue = XmlHelper::sanitize($value);
                }
            }

            // Here the index is the name of a node. In this case, the node is
            // an array. So it will be processed recursively to add the child
            // nodes that are in the array to this node.
            elseif (is_array($value)) {
                // Only the node is created if it has child nodes. The node will
                // not be created if an empty array (without children) is passed.
                if (!empty($value)) {
                    $this->nodeAddChilds(
                        $doc,
                        $parent,
                        $key,
                        $value,
                        $namespace
                    );
                }
            }

            // The node is a scalar (not an array, not child nodes). So the
            // node is created and the value is assigned directly.
            else {
                if (!$this->skipValue($value)) {
                    $this->nodeAddValue(
                        $doc,
                        $parent,
                        $key,
                        (string) $value,
                        $namespace
                    );
                }
            }
        }

        // Return the generated XML document.
        return $doc;
    }

    /**
     * Adds attributes to an XML node from an array.
     *
     * @param DOMElement $node Node to which the attributes will be added.
     * @param array $attributes Array of attributes (key => value).
     * @return void
     * @throws InvalidArgumentException If an attribute value is an array.
     */
    private function nodeAddAttributes(DOMElement $node, array $attributes): void
    {
        foreach ($attributes as $attribute => $value) {
            // If the attribute value is an array, it cannot be assigned.
            if (is_array($value)) {
                throw new InvalidArgumentException(sprintf(
                    'The type of data of the value entered for the attribute "%s" of the node "%s" is incorrect (cannot be an array). The value is: %s',
                    $attribute,
                    $node->tagName,
                    json_encode($value)
                ));
            }

            // Assign the attribute value only if it is not skipped according to
            // the type of value to be assigned.
            if (!$this->skipValue($value)) {
                $node->setAttribute($attribute, $value);
            }
        }
    }

    /**
     * Adds child nodes to an XML node from an array.
     *
     * @param XmlDocumentInterface $doc XML document in which the nodes will be added.
     * @param DOMNode $parent Node parent to which the child nodes will be added.
     * @param string $tagName Name of the child node tag.
     * @param array $childs Array of data of the child nodes.
     * @param array|null $namespace XML namespace (URI and prefix).
     * @return void
     * @throws InvalidArgumentException If a child node is not an array.
     */
    private function nodeAddChilds(
        XmlDocumentInterface $doc,
        DOMNode $parent,
        string $tagName,
        array $childs,
        ?array $namespace = null,
    ): void {
        $keys = array_keys($childs);
        if (!is_int($keys[0])) {
            $childs = [$childs];
        }
        foreach ($childs as $child) {
            // Skip values that must be skipped.
            if ($this->skipValue($child)) {
                continue;
            }

            // If the child is an array, a node is created for the child and the
            // elements that are in the array are added.
            if (is_array($child)) {

                // If the array is not associative (with new nodes) error.
                if (isset($child[0])) {
                    throw new InvalidArgumentException(sprintf(
                        'The node "%s" allows including arrays, but they must be arrays with other nodes. The current value is incorrect: %s',
                        $tagName,
                        json_encode($child)
                    ));
                }

                // Add child nodes of the child node (add associative to the
                // node $tagName).
                $Node = $namespace
                    ? $doc->createElementNS(
                        $namespace[0],
                        $namespace[1] . ':' . $tagName
                    )
                    : $doc->createElement($tagName)
                ;
                $parent->appendChild($Node);
                $this->encode($child, $namespace, $Node, $doc);
            }
            // If the child is not an array, it is simply a duplicate node that
            // must be added at the same level as the parent node.
            else {
                $value = XmlHelper::sanitize((string) $child);
                $Node = $namespace
                    ? $doc->createElementNS(
                        $namespace[0],
                        $namespace[1] . ':' . $tagName,
                        $value
                    )
                    : $doc->createElement($tagName, $value)
                ;
                $parent->appendChild($Node);
            }
        }
    }

    /**
     * Adds an XML node with a scalar value to a parent node.
     *
     * @param XmlDocumentInterface $doc XML document in which the nodes will be added.
     * @param DOMNode $parent Node parent to which the node will be added.
     * @param string $tagName Name of the child node tag.
     * @param string $value Value of the child node.
     * @param array|null $namespace XML namespace (URI and prefix).
     * @return void
     */
    private function nodeAddValue(
        XmlDocumentInterface $doc,
        DOMNode $parent,
        string $tagName,
        string $value,
        ?array $namespace = null,
    ): void {
        $value = XmlHelper::sanitize($value);
        $Node = $namespace
            ? $doc->createElementNS(
                $namespace[0],
                $namespace[1] . ':' . $tagName,
                $value
            )
            : $doc->createElement($tagName, $value)
        ;
        $parent->appendChild($Node);
    }

    /**
     * Checks if a value must be skipped when generating an XML node.
     *
     * @param mixed $value Value to check.
     * @return bool `true` if the value must be skipped, `false` otherwise.
     */
    private function skipValue(mixed $value): bool
    {
        return in_array(
            $value,
            $this->rules['node_values']['skip_generation'],
            true
        );
    }

    /**
     * Checks if a value must generate an empty XML node.
     *
     * @param mixed $value Value to check.
     * @return bool `true` if the value must generate an empty node, `false` otherwise.
     */
    // private function createWithEmptyValue(mixed $value): bool
    // {
    //     return in_array(
    //         $value,
    //         $this->rules['node_values']['generate_empty'],
    //         true
    //     );
    // }
}

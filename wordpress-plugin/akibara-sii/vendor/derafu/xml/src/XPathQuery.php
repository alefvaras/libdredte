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
use DOMNode;
use DOMNodeList;
use DOMXPath;
use InvalidArgumentException;
use LogicException;

/**
 * Class to facilitate XML handling using XPath.
 */
final class XPathQuery
{
    /**
     * Instance of the XML document.
     *
     * @var DOMDocument
     */
    private readonly DOMDocument $dom;

    /**
     * Instance that represents the XPath searcher.
     *
     * @var DOMXPath
     */
    private readonly DOMXPath $xpath;

    /**
     * Whether to use the namespace prefixes or ignore them.
     *
     * @var boolean
     */
    private readonly bool $registerNodeNS;

    /**
     * Constructor that receives the XML document and prepares XPath.
     *
     * @param string|DOMDocument $xml XML document.
     * @param array $namespaces Associative array with prefix and URI.
     */
    public function __construct(
        string|DOMDocument $xml,
        array $namespaces = []
    ) {
        // Assign the DOM document instance.
        if ($xml instanceof DOMDocument) {
            $this->dom = $xml;
        } else {
            $this->dom = new DOMDocument();
            $this->loadXml($xml);
        }

        // Create the XPath instance over the DOM document.
        $this->xpath = new DOMXPath($this->dom);

        // Assign or disable the use of namespaces.
        if ($namespaces) {
            foreach ($namespaces as $prefix => $namespace) {
                $this->xpath->registerNamespace($prefix, $namespace);
            }
            $this->registerNodeNS = true;
        } else {
            $this->registerNodeNS = false;
        }
    }

    /**
     * Returns the DOMDocument used internally.
     *
     * @return DOMDocument
     */
    public function getDomDocument(): DOMDocument
    {
        return $this->dom;
    }

    /**
     * Executes an XPath query and returns the processed result.
     *
     * The result will depend on what is found:
     *
     *   - `null`: if there was no match.
     *   - string: if there was one match.
     *   - string[]: if there was more than one match.
     *
     * If the node has children, it returns a recursive array representing
     * the entire structure of the nodes.
     *
     * @param string $query XPath query.
     * @param array $params Array of parameters.
     * @param DOMNode|null $contextNode From which node to evaluate the expression.
     * @return string|string[]|null The processed value: string, array or null.
     */
    public function get(
        string $query,
        array $params = [],
        ?DOMNode $contextNode = null
    ): string|array|null {
        $nodes = $this->getNodes($query, $params, $contextNode);

        // No matches.
        if ($nodes->length === 0) {
            return null;
        }

        // Single node.
        if ($nodes->length === 1) {
            return $this->processNode($nodes->item(0));
        }

        // Multiple nodes.
        $results = [];
        foreach ($nodes as $node) {
            $results[] = $this->processNode($node);
        }

        return $results;
    }

    /**
     * Executes an XPath query and returns an array of values.
     *
     * @param string $query XPath query.
     * @param array $params Array of parameters.
     * @param DOMNode|null $contextNode From which node to evaluate the expression.
     * @return string[] Array of found values.
     */
    public function getValues(
        string $query,
        array $params = [],
        ?DOMNode $contextNode = null
    ): array {
        $nodes = $this->getNodes($query, $params, $contextNode);

        $results = [];
        foreach ($nodes as $node) {
            $results[] = $node->nodeValue;
        }

        return $results;
    }

    /**
     * Executes an XPath query and returns the first result as a string.
     *
     * @param string $query XPath query.
     * @param array $params Array of parameters.
     * @param DOMNode|null $contextNode From which node to evaluate the expression.
     * @return string|null The value of the node, or `null` if it does not exist.
     */
    public function getValue(
        string $query,
        array $params = [],
        ?DOMNode $contextNode = null
    ): ?string {
        $nodes = $this->getNodes($query, $params, $contextNode);

        return $nodes->length > 0
            ? $nodes->item(0)->nodeValue
            : null
        ;
    }

    /**
     * Executes an XPath query and returns the resulting nodes.
     *
     * @param string $query XPath query.
     * @param array $params Array of parameters.
     * @param DOMNode|null $contextNode From which node to evaluate the expression.
     * @return DOMNodeList Resulting nodes from the XPath query.
     */
    public function getNodes(
        string $query,
        array $params = [],
        ?DOMNode $contextNode = null
    ): DOMNodeList {
        try {
            $query = $this->resolveQuery($query, $params);
            $nodes = $this->execute(fn () => $this->xpath->query(
                $query,
                $contextNode,
                $this->registerNodeNS
            ));
        } catch (LogicException $e) {
            throw new InvalidArgumentException(sprintf(
                'An error occurred while executing the XPath expression: %s. %s',
                $query,
                $e->getMessage()
            ));
        }

        return $nodes;
    }

    /**
     * Loads an XML string into the $dom attribute.
     *
     * @param string $xml
     * @return static
     */
    private function loadXml(string $xml): static
    {
        try {
            $this->execute(fn () => $this->dom->loadXml($xml));
        } catch (LogicException $e) {
            throw new InvalidArgumentException(sprintf(
                'The provided XML is not valid: %s',
                $e->getMessage()
            ));
        }

        return $this;
    }

    /**
     * Processes a DOM node and its children recursively.
     *
     * @param DOMNode $node DOM node to process.
     * @return string|array The value of the node or the structure of children as an array.
     */
    private function processNode(DOMNode $node): string|array
    {
        if ($node->hasChildNodes()) {
            $children = [];
            $nodeCounts = [];

            foreach ($node->childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE) {
                    $nodeName = $child->nodeName;

                    // Count occurrences of each node name.
                    $nodeCounts[$nodeName] = ($nodeCounts[$nodeName] ?? 0) + 1;

                    // If this is the first occurrence, store it directly.
                    if ($nodeCounts[$nodeName] === 1) {
                        $children[$nodeName] = $this->processNode($child);
                    } else {
                        // If this is a duplicate node name, convert to array.
                        if ($nodeCounts[$nodeName] === 2) {
                            // Convert the existing single value to an array.
                            $children[$nodeName] = [$children[$nodeName]];
                        }
                        // Add the new value to the array.
                        $children[$nodeName][] = $this->processNode($child);
                    }
                }
            }

            // If it has processed children, return the structure.
            return count($children) > 0 ? $children : $node->nodeValue;
        }

        // If it has no children, return the value.
        return $node->nodeValue;
    }

    /**
     * Resolves the parameters of an XPath query.
     *
     * This method replaces the named markers (like `:param`) in the query with
     * the escaped values in quotes.
     *
     * @param string $query XPath query with named markers (e.g.: ":param").
     * @param array $params Array of parameters in the format ['param' => 'value'].
     * @return string XPath query with the values replaced.
     */
    private function resolveQuery(string $query, array $params = []): string
    {
        // If the namespaces are disabled, adapt the XPath query.
        if (!$this->registerNodeNS) {
            $query = preg_replace_callback(
                '/(?<=\/|^)(\w+)/',
                fn ($matches) => '*[local-name()="' . $matches[1] . '"]',
                $query
            );
        }

        // Replace parameters.
        foreach ($params as $key => $value) {
            $placeholder = ':' . ltrim($key, ':');
            $quotedValue = $this->quoteValue($value);
            $query = str_replace($placeholder, $quotedValue, $query);
        }

        // Return the resolved query.
        return $query;
    }

    /**
     * Escapes a value to use it in an XPath query.
     *
     * If the version is PHP 8.4 or higher, DOMXPath::quote() is used.
     * Otherwise, a manual implementation is used.
     *
     * @param string $value Value to escape.
     * @return string Escaped value as an XPath literal.
     */
    private function quoteValue(string $value): string
    {
        // Available only from PHP 8.4.
        if (method_exists(DOMXPath::class, 'quote')) {
            return DOMXPath::quote($value);
        }

        // Manual implementation for versions prior to PHP 8.4.
        if (!str_contains($value, "'")) {
            return "'" . $value . "'";
        }
        if (!str_contains($value, '"')) {
            return '"' . $value . '"';
        }

        // If it contains single and double quotes, combine them with concat().
        return "concat('" . str_replace("'", "',\"'\",'", $value) . "')";
    }

    /**
     * Wrapper to execute capturing the errors of the methods associated with
     * the XPath instance.
     *
     * @param callable $function
     * @return mixed
     */
    private function execute(callable $function): mixed
    {
        $use_errors = libxml_use_internal_errors(true);

        $result = $function();

        $error = $this->getLastError();
        if ($result === false || $error) {
            libxml_clear_errors();
            libxml_use_internal_errors($use_errors);

            $message = $error ?: 'Ocurrió un error en XPathQuery.';
            throw new LogicException($message);
        }

        libxml_clear_errors();
        libxml_use_internal_errors($use_errors);

        return $result;
    }

    /**
     * Returns, if it exists, the last error generated from XML.
     *
     * @return string|null
     */
    private function getLastError(): ?string
    {
        $error = libxml_get_last_error();

        if (!$error) {
            return null;
        }

        return trim($error->message) . '.';
    }
}

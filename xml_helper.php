<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('array_to_xml'))
{
    /**
     * Convent assoc array to xml.
     * @param array | string $data
     * @param simpleXMLElement $xml
     * @param string  $root
     * @param bool $format_output
     * @return string xml
     */
    function array_to_xml($data, $xml = null, $root = 'root', $format_output = TRUE)
    {
        if ($xml == null) {
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?><'. $root. '></'. $root. '>');
        }
        if ( ! is_array($data)) {
            $xml->addChild(htmlspecialchars($data));
            return $xml->asXML();
        }

        foreach ($data as $key=>$value)
        {
            if (is_array($value)) {
                if ( ! is_numeric($key) ) {
                    array_to_xml($value, $xml->addChild("$key"));
                } else {
                    array_to_xml($value, $xml->addChild("items"));
                }
            } else {
                $xml->addChild("$key", htmlspecialchars("$value"));
            }
        }

        if ( ! $format_output ) {
            return $xml->asXML();
        } else {
            $dom = new DOMDocument('1.0', 'utf-8');
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($xml->asXML());
            return $dom->saveXML();
        }
    }
}

if ( ! function_exists('xml_to_array'))
{
    /**
     * Convert xml to array
     * @param $xml string
     * @return array
     */
    function xml_to_array($xml)
    {
        $xml = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
        $root = $xml->getName();
        $data["$root"] = json_decode(json_encode($xml), TRUE);

        return $data;
    }
}

if ( ! function_exists('array2xml'))
{
    /**
     * Further convert array to xml.
     *
     * @param array $data
     * @param string $node_name
     * @param string $version
     * @param string $encoding
     * @return string xml
     */
    function array2xml($data, $node_name, $version = '1.0', $encoding = 'utf-8')
    {
        $xml = new DomDocument($version, $encoding);
        $xml->formatOutput = TRUE;
        $xml->appendChild(convent($node_name, $data, $xml));
        return $xml->saveXML();
    }

}

if ( ! function_exists('convent'))
{
    /**
     * Create root element.
     * @param $node_name
     * @param array $data
     * @param $xml
     * @return mixed
     */
    function convent($node_name, $data = array(), DOMDocument &$xml)
    {
        $node = $xml->createElement($node_name);

        if ( !is_array($data) ) {
            $node->appendChild($xml->createTextNode(bool2str($data)));
        }

        if ( is_array($data) ) {
            // fetch the attributes first
            if ( isset($data['@attributes']) ) {
                foreach($data['@attributes'] as $key=>$value) {
                    $node->setAttribute($key, bool2str($value));
                }
                unset($data['@attributes']);
            }
            // check whether it has a value stored in @value
            if ( isset($data['@value']) ) {
                $node->appendChild($xml->createTextNode(bool2str($data['@value'])));
                unset($data['@value']);

                // a note with value cannot have child nodes.
                return $node;
            }
            if ( isset($data['@cdata'])) {
                $node->appendChild($xml->createCDATASection(bool2str($data['@cdata'])));
                unset($data['@cdata']);
                // a note with cdata cannot have child nodes.
                return $node;
            }

            // create subnodes using recursion.
            foreach($data as $key=>$value) {
                if ( is_array($value) && is_numeric(key($value)) ) {
                    foreach($value as $k=>$v) {
                        $node->appendChild(convent($key, $v, $xml));
                    }
                } else {
                    $node->appendChild(convent($key, $value, $xml));
                }
            }
        }

        return $node;
    }
}

if ( ! function_exists('bool2str'))
{
    /**
     * Convent bool value to string format.
     * @param $data
     * @return string
     */
    function bool2str($data)
    {
        TRUE === $data && $data = 'true';
        FALSE === $data && $data = 'false';
        return $data;
    }
}


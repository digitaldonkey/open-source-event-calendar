<?php

namespace Osec\Http\Response;

/**
 * Render the request as xml.
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Render_Strategy_Xml, Ai1ec_XML_Builder
 * @author     Time.ly Network Inc.
 */
class RenderXml extends RenderStrategyAbstract
{
    public function render(array $params)
    {
        $this->_dump_buffers();
        header('HTTP/1.1 200 OK');
        header('Content-Type: text/xml; charset=UTF-8');
        $data   = ResponseHelper::utf8($params['data']);
        $output = $this->serialize_to_xml($data);
        // Untested.
        echo esc_xml($output);
        ResponseHelper::stop();
    }

    /**
     * Serializes given data (array, object, etc.) and generates an XML
     * document from it. If $wrap_json is true, simply serializes the data as
     * JSON and generates a simple XML wrapper around it.
     *
     * Function adapted from
     * http://www.sean-barton.co.uk/2009/03/turning-an-array-or-object-into-xml-using-php/
     *
     * @param  mixed  $data  Data to serialize.
     * @param  bool  $wrap_json  Whether to serialize data in JSON format.
     * @param  string  $node_block  Name of root-level XML element.
     * @param  string  $node_name  Name of XML element to wrap around ordinal array elements.
     *
     * @return string               Valid XML document.
     */
    public static function serialize_to_xml(
        mixed $data,
        $wrap_json = true,
        $node_block = 'data',
        $node_name = 'node'
    ) {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>';

        $xml .= '<' . $node_block . '>';

        if ($wrap_json) {
            $xml .= '<![CDATA[' . wp_json_encode($data) . ']]>';
        } else {
            $xml .= self::_generate_xml_from_value($data, $node_name);
        }

        $xml .= '</' . $node_block . '>';

        return $xml;
    }

    /**
     * Serializes $value into an XML document fragment.
     *
     * Function adapted from
     * http://www.sean-barton.co.uk/2009/03/turning-an-array-or-object-into-xml-using-php/
     *
     * @param $value
     * @param  string  $node_name  Name of XML element to wrap around ordinal array elements.
     *
     * @return string               Valid XML document.
     */
    private static function _generate_xml_from_value($value, $node_name)
    {
        if (is_array($value) || is_object($value)) {
            $xml = '';

            foreach ($value as $key => $v) {
                if (is_numeric($key)) {
                    $key = $node_name;
                }

                $xml .= '<' . $key . '>' .
                        self::_generate_xml_from_value($v, $node_name) .
                        '</' . $key . '>';
            }
        } else {
            $xml = htmlspecialchars((string)$value, ENT_QUOTES);
        }

        return $xml;
    }
}

<?php

namespace MainlyCode\Xmpp;

use RuntimeException;

class StanzaParser
{
    /**
     * @param  string           $data
     * @return SimpleXMLElement
     * @throws RuntimeException
     */
    public function parse($data)
    {
        $xml = @simplexml_load_string($data);
        if (! $xml) {
            throw new RuntimeException('unable to parse xml');
        }

        return $xml;
    }
}

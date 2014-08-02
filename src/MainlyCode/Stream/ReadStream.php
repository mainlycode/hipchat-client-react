<?php

namespace MainlyCode\Stream;

use Exception;
use MainlyCode\Xmpp\StanzaParser;
use React\Stream\WritableStream;

class ReadStream extends WritableStream
{
    private $parser;

    /**
     * @param StanzaParser $parser
     */
    public function __construct(StanzaParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * @param string $data
     */
    public function write($data)
    {
        $this->emit('data', array($data));

        if (preg_match("/<starttls xmlns=('|\")urn:ietf:params:xml:ns:xmpp-tls('|\")>(<required\/>)?<\/starttls>/", $data)) {
            $this->emit('xmpp.tls.required', array($data));

            return;
        }

        if ('<proceed xmlns=\'urn:ietf:params:xml:ns:xmpp-tls\'/>' == $data) {
            $this->emit('xmpp.tls.proceed', array($data));

            return;
        }

        if (preg_match('/PLAIN/', $data)) {
            $this->emit('xmpp.features', array($data));

            return;
        }

        if ('<iq type=\'result\' id=\'1001\'/>' === $data) {
            $this->emit('xmpp.authentication.success', array($data));

            return;
        }

        if ('<iq type=\'result\' from=\'chat.hipchat.com\' id=\'sess_1\'/>' === $data) {
            $this->emit('xmpp.session.established', array($data));

            return;
        }

        if ('</stream:stream>' == $data) {
            $this->emit('xmpp.stream.end', array($data));

            return;
        }

        try {
            $xml = $this->parser->parse($data);

            if ('message' === $xml->getName()) {
                $this->emit('xmpp.message.received', array($xml));
            }
            $this->emit('xmpp.stanza.received', array($xml));

            return;
        } catch (Exception $e) {
            // ignore for now
        }

        $this->emit('xmpp.received', array($data));
    }
}

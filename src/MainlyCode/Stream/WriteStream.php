<?php

namespace MainlyCode\Stream;

use MainlyCode\Xmpp\JabberId;
use React\Stream\ReadableStream;

class WriteStream extends ReadableStream
{
    /**
     * @param string $data
     */
    private function send($data)
    {
        $this->emit('data', array($data));
    }

    public function keepAlive()
    {
        $this->send(' ');
    }

    /**
     * @param string $host
     */
    public function xmppStartStream($host)
    {
        $this->send(sprintf('<stream:stream to="%s" xmlns:stream="http://etherx.jabber.org/streams" xmlns="jabber:client" version="1.0">', $host));
    }

    public function xmppCloseStream()
    {
        $this->send('</stream:stream>');
    }

    public function xmppStartTls()
    {
        $this->send('<starttls xmlns="urn:ietf:params:xml:ns:xmpp-tls"/>');
    }

    /**
     * @param string $username
     * @param string $password
     * @param string $resource
     */
    public function xmppAuthenticateNonSasl($username, $password, $resource)
    {
        $this->send(sprintf('<iq type="set" id="1001"><query xmlns="jabber:iq:auth"><username>%s</username><password>%s</password><resource>%s</resource></query></iq>', $this->xmlEncode($username), $this->xmlEncode($password), $this->xmlEncode($resource)));
    }

    /**
     * @param string $host
     */
    public function xmppEstablishSession($host)
    {
        $this->send(sprintf('<iq to="%s" type="set" id="sess_1"><session xmlns="urn:ietf:params:xml:ns:xmpp-session"/></iq>', $host));
    }

    /**
     * @param string $resource
     */
    public function xmppBind($resource)
    {
        $this->send(sprintf('<iq type="set" id="bind_2"><bind xmlns="urn:ietf:params:xml:ns:xmpp-bind"><resource>%s</resource></bind></iq>', $resource));
    }

    /**
     * @param JabberId $jabberId
     * @param JabberId $room
     * @param string   $nickname
     */
    public function xmppJoin(JabberId $jabberId, JabberId $room, $nickname)
    {
        $this->send(sprintf('<presence from="%s" to="%s/%s"><x xmlns="http://jabber.org/protocol/muc"/></presence>', $jabberId->getId(), $room->getId(), $nickname));
    }

    /**
     * @param JabberId $room
     * @param JabberId $from
     * @param string   $message
     */
    public function xmppMessage(JabberId $room, JabberId $from, $message)
    {
        $message = $this->xmlEncode($message);

        $this->send(sprintf('<message to="%s" from="%s" type="groupchat" xml:lang="en"><body>%s</body></message>', $room->getId(), $from->getId(), $message));
    }

    /**
     * @param JabberId    $jabberId
     * @param string|null $show
     */
    public function xmppPresence(JabberId $jabberId, $show = null)
    {
        if ($show) {
            $this->send(sprintf('<presence from="%s"><show>%s</show></presence>', $jabberId->getId(), $show));
        } else {
            $this->send(sprintf('<presence from="%s"></presence>', $jabberId->getId()));
        }
    }

    /**
     * @param string $message
     * @return string
     */
    public function xmlEncode($message)
    {
        return htmlspecialchars($message, ENT_COMPAT, 'UTF-8');
    }
}

<?php

namespace MainlyCode\Xmpp;

use MainlyCode\TestCase;

class ConnectionTest extends TestCase
{
    /**
     * @test
     */
    public function it_gets_properties()
    {
        $jabberId   = new JabberId('42_1337@chat.hipchat.com/bot');
        $connection = new Connection($jabberId, 'leetbot', '1337b0t', 'chat.hipchat.com', 5222);

        $this->assertSame($jabberId, $connection->getJabberId());
        $this->assertEquals('leetbot', $connection->getNickname());
        $this->assertEquals('1337b0t', $connection->getPassword());
        $this->assertEquals('chat.hipchat.com', $connection->getHost());
    }

    /**
     * @test
     */
    public function it_returns_socket_uri()
    {
        $connection = new Connection(
            new JabberId('42_1337@chat.hipchat.com/bot'),
            'leetbot',
            '1337b0t',
            'chat.hipchat.com',
            5222
        );

        $this->assertEquals('tcp://chat.hipchat.com:5222', $connection->getRemoteSocket());
    }
}

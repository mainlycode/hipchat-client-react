<?php

namespace MainlyCode\HipChat;

use MainlyCode\Stream\ReadStream;
use MainlyCode\Stream\WriteStream;
use MainlyCode\Xmpp\Client as BaseClient;
use React\Stream\Stream;

class Client extends BaseClient
{
    protected function configureStreams(Stream $stream, ReadStream $read, WriteStream $write, Connection $connection)
    {
        parent::configureStreams($stream, $read, $write, $connection);

        $loop = $this->loop;

        $this->on('connect.after', function ($connection) use ($write, $loop) {
            $write->xmppStartStream($connection->getHost());

            $loop->addPeriodicTimer(60, function () use ($write) {
                $write->keepAlive();
            });
        });

        $read->on('xmpp.tls.required', function ($data) use ($write) {
            $write->xmppStartTls();
        });

        $read->on('xmpp.tls.proceed', function ($data) use ($stream, $write, $connection) {
            $this->encryptSocket($stream->stream);
            $write->xmppStartStream($connection->getHost());
        });

        $read->on('xmpp.features', function ($data) use ($connection, $write) {
            $write->xmppAuthenticateNonSasl(
                $connection->getJabberId()->getLocalPart(),
                $connection->getPassword(),
                $connection->getJabberId()->getResourcePart()
            );
        });

        $read->on('xmpp.authentication.success', function ($data) use ($connection, $write) {
            //$write->xmppBind(); // @todo not required on HipChat?
            $write->xmppEstablishSession($connection->getHost());
        });

        $read->on('xmpp.stream.end', function ($data) use ($loop) {
            $loop->stop();
        });

        $this->on('connect.end', function ($connection) use ($loop) {
            $loop->stop();
        });
    }
}

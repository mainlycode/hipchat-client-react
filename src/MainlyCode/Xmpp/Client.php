<?php

namespace MainlyCode\Xmpp;

use Evenement\EventEmitter;
use Exception;
use MainlyCode\Stream\ReadStream;
use MainlyCode\Stream\SecureStream;
use MainlyCode\Stream\WriteStream;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Stream\Stream;
use RuntimeException;

class Client extends EventEmitter
{
    protected $loop;
    private   $readStream;
    private   $writeStream;

    /**
     * @param LoopInterface $loop
     */
    public function __construct(LoopInterface $loop = null)
    {
        if (null === $loop) {
            $loop = Factory::create();
        }

        $this->loop        = $loop;
        $this->readStream  = new ReadStream(new StanzaParser());
        $this->writeStream = new WriteStream();
    }

    /**
     * @param Connection $connection
     */
    public function run(Connection $connection)
    {
        $this->connect($connection);

        $this->loop->run();
    }

    /**
     * @param Connection $connection
     */
    public function connect(Connection $connection)
    {
        $this->emit('connect.before', array($connection));

        try {
            $socket = $this->createSocket($connection);
            $stream = $this->createStream($socket);

            $this->configureStreams($stream, $this->readStream, $this->writeStream, $connection);
        } catch (Exception $e) {
            $this->emit('connect.error', array($e->getMessage(), $connection));
        }

        $this->emit('connect.after', array($connection));
    }

    protected function configureStreams(Stream $stream, ReadStream $read, WriteStream $write, Connection $connection)
    {
        $write->pipe($stream)->pipe($read);

        $read->on('data', $this->getReadCallback($write, $connection, 'xmpp.received'));
        $read->on('xmpp.stanza.received', $this->getReadCallback($write, $connection, 'xmpp.stanza.received'));
        $read->on('xmpp.session.established', $this->getReadCallback($write, $connection, 'xmpp.session.established'));
        $read->on('xmpp.message.received', $this->getReadCallback($write, $connection, 'xmpp.message.received'));
        $write->on('data', $this->getWriteCallback($connection));

        $stream->on('end', $this->getEndCallback($connection));

        $error = $this->getErrorCallback($connection);
        $read->on('error', $error);
        $write->on('error', $error);
    }

    /**
     * @param Connection $connection
     * @return resource
     * @throws RuntimeException
     */
    public function createSocket(Connection $connection)
    {
        $socket = stream_socket_client(
            $connection->getRemoteSocket(),
            $errno,
            $errstr,
            ini_get('default_socket_timeout'),
            STREAM_CLIENT_CONNECT
        );

        if (! $socket) {
            throw new RuntimeException(
                'Unable to connect to remote ' . $connection->getRemoteSocket() .
                ': socket error ' . $errno . ' ' . $errstr
            );
        }

        stream_set_blocking($socket, 0);

        return $socket;
    }

    /**
     * @param resource $socket
     * @return boolean
     */
    protected function encryptSocket($socket)
    {
        stream_set_blocking ($socket, 1);
        $result = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        stream_set_blocking ($socket, 0);

        return $result;
    }

    /**
     * @param resource $socket
     * @return SecureStream
     */
    protected function createStream($socket)
    {
        return new SecureStream($socket, $this->loop);
    }

    /**
     * @param WriteStream $write
     * @param Connection $connection
     * @param string $event
     * @return callable
     */
    protected function getReadCallback($write, $connection, $event)
    {
        $client = $this;
        return function($message) use ($client, $write, $connection, $event) {
            $client->emit($event, array($message, $write, $connection));
        };
    }

    /**
     * @param Connection $connection
     * @return callable
     */
    protected function getWriteCallback($connection)
    {
        $client = $this;
        return function($message) use ($client, $connection) {
            $client->emit('xmpp.sent', array($message, $connection));
        };
    }

    /**
     * @param Connection $connection
     * @return callable
     */
    protected function getErrorCallback($connection)
    {
        $client = $this;
        return function($message) use ($client, $connection) {
            $client->emit('connect.error', array($message, $connection));
        };
    }

    /**
     * @param Connection $connection
     * @return callable
     */
    protected function getEndCallback($connection)
    {
        $client = $this;
        return function() use ($client, $connection) {
            $client->emit('connect.end', array($connection));
        };
    }

    /**
     * @return WriteStream
     */
    public function getWriteStream()
    {
        return $this->writeStream;
    }
} 

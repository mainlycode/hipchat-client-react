<?php

namespace MainlyCode\Stream;

use React\Stream\Stream;

class SecureStream extends Stream
{
    /**
     * @param resource $stream
     */
    public function handleData($stream)
    {
        if (!is_resource($stream) || feof($stream)) {
            $this->end();

            return;
        }

        while ($data = fread($stream, $this->bufferSize)) {
            $this->emit('data', array($data, $this));
        }
    }
}

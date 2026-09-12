<?php
/**
 * MiniRedis — a tiny hand-rolled Redis client speaking RESP directly
 * over a TCP socket. Written so the boutique app needs nothing beyond
 * the stock php:8.2-apache image (no phpredis extension to install,
 * no custom Docker image to build for this project).
 *
 * Supports just what this app needs: PING, SET, GET, LPUSH, LRANGE, DEL.
 */
class MiniRedis
{
    private $sock;

    public function __construct(string $host, int $port, float $timeout = 2.0)
    {
        $this->sock = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if (!$this->sock) {
            throw new Exception("Could not connect to Redis at {$host}:{$port} — {$errstr} ({$errno})");
        }
        stream_set_timeout($this->sock, (int) $timeout);
    }

    private function command(...$args)
    {
        $cmd = '*' . count($args) . "\r\n";
        foreach ($args as $arg) {
            $arg = (string) $arg;
            $cmd .= '$' . strlen($arg) . "\r\n" . $arg . "\r\n";
        }
        fwrite($this->sock, $cmd);
        return $this->readReply();
    }

    private function readReply()
    {
        $line = fgets($this->sock);
        if ($line === false) {
            throw new Exception('Redis connection closed unexpectedly.');
        }
        $type = $line[0];
        $rest = rtrim(substr($line, 1), "\r\n");

        switch ($type) {
            case '+': // simple string
                return $rest;
            case '-': // error
                throw new Exception('Redis error: ' . $rest);
            case ':': // integer
                return (int) $rest;
            case '$': // bulk string
                $len = (int) $rest;
                if ($len === -1) {
                    return null;
                }
                $data = '';
                $toRead = $len + 2; // +2 for trailing \r\n
                while ($toRead > 0) {
                    $chunk = fread($this->sock, $toRead);
                    if ($chunk === false || $chunk === '') {
                        break;
                    }
                    $data .= $chunk;
                    $toRead -= strlen($chunk);
                }
                return substr($data, 0, $len);
            case '*': // array
                $count = (int) $rest;
                if ($count === -1) {
                    return null;
                }
                $arr = [];
                for ($i = 0; $i < $count; $i++) {
                    $arr[] = $this->readReply();
                }
                return $arr;
            default:
                return null;
        }
    }

    public function ping()
    {
        return $this->command('PING');
    }

    public function set(string $key, string $value)
    {
        return $this->command('SET', $key, $value);
    }

    public function get(string $key)
    {
        return $this->command('GET', $key);
    }

    public function lpush(string $key, string $value)
    {
        return $this->command('LPUSH', $key, $value);
    }

    public function lrange(string $key, int $start, int $stop)
    {
        return $this->command('LRANGE', $key, $start, $stop);
    }

    public function del(string $key)
    {
        return $this->command('DEL', $key);
    }

    public function close()
    {
        if ($this->sock) {
            fclose($this->sock);
        }
    }
}

<?php

namespace Phunkie\Streams\text {
    const lines = "\\Phunkie\\Streams\\text\\lines";
    function lines($chunk): array {
        return explode(PHP_EOL, $chunk);
    }

    const utf8Encode = "\\Phunkie\\Streams\\text\\utf8Encode";
    function utf8Encode(string $chunk): string {
        static $useMbstring = null;

        if ($useMbstring === null) {
            $useMbstring = extension_loaded('mbstring');
        }

        if ($useMbstring) {
            return mb_convert_encoding($chunk, 'UTF-8', 'auto');
        } else {
            $encoded = '';
            $length = strlen($chunk);
            for ($i = 0; $i < $length; $i++) {
                $c = ord($chunk[$i]);
                if ($c < 128) {
                    $encoded .= chr($c);
                } elseif ($c < 2048) {
                    $encoded .= chr(192 + ($c >> 6));
                    $encoded .= chr(128 + ($c & 63));
                } else {
                    $encoded .= chr(224 + ($c >> 12));
                    $encoded .= chr(128 + (($c >> 6) & 63));
                    $encoded .= chr(128 + ($c & 63));
                }
            }
            return $encoded;
        }
    }

    const utf8Decode = "\\Phunkie\\Streams\\text\\utf8Decode";
    function utf8Decode(string $chunk): string {
        static $useMbstring = null;

        if ($useMbstring === null) {
            $useMbstring = extension_loaded('mbstring');
        }

        if ($useMbstring) {
            return mb_convert_encoding($chunk, 'auto', 'UTF-8');
        } else {
            $decoded = '';
            $length = strlen($chunk);
            for ($i = 0; $i < $length; $i++) {
                $c = ord($chunk[$i]);
                if ($c < 128) {
                    $decoded .= $chunk[$i];
                } elseif ($c < 224) {
                    $decoded .= chr(($c & 31) << 6 | ord($chunk[++$i]) & 63);
                } elseif ($c < 240) {
                    $decoded .= chr(($c & 15) << 12 | (ord($chunk[++$i]) & 63) << 6 | ord($chunk[++$i]) & 63);
                }
            }
            return $decoded;
        }
    }

    const trim = "\\Phunkie\\Streams\\text\\trim";
    function trim($chunk): string {
        return \trim($chunk);
    }

    function splitBy(string $delimiter): \Closure {
        return function ($chunk) use ($delimiter) {
            return explode($delimiter, $chunk);
        };
    }
}

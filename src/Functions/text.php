<?php

/*
 * This file is part of Phunkie Streams, a PHP functional library to work with Streams.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Streams\text {
    const lines = "\\Phunkie\\Streams\\text\\lines";

    /**
     * Split a string chunk into lines by the platform EOL character.
     *
     * @param string $chunk The text to split
     * @return array<string> Array of lines
     */
    function lines($chunk): array
    {
        return explode(PHP_EOL, $chunk);
    }

    const utf8Encode = "\\Phunkie\\Streams\\text\\utf8Encode";

    /**
     * Encode a string chunk to UTF-8. Uses mbstring if available, otherwise a manual fallback.
     *
     * @param string $chunk The string to encode
     * @return string UTF-8 encoded string
     */
    function utf8Encode(string $chunk): string
    {
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

    /**
     * Decode a UTF-8 encoded string chunk. Uses mbstring if available, otherwise a manual fallback.
     *
     * @param string $chunk The UTF-8 string to decode
     * @return string Decoded string
     */
    function utf8Decode(string $chunk): string
    {
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

    /**
     * Trim whitespace from both ends of a string chunk.
     *
     * @param string $chunk The string to trim
     * @return string Trimmed string
     */
    function trim($chunk): string
    {
        return \trim($chunk);
    }

    /**
     * Create a closure that splits a string chunk by the given delimiter.
     *
     * @param string $delimiter The delimiter to split on
     * @return \Closure(string): array<string>
     */
    function splitBy(string $delimiter): \Closure
    {
        return function ($chunk) use ($delimiter) {
            return explode($delimiter, $chunk);
        };
    }
}

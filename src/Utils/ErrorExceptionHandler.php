<?php

namespace App\Utils;

/**
 * Convert notices/warnings/errors into exceptions.
 *
 * Usage:
 *
 *     set_error_handler(new ErrorExceptionHandler());
 */
final class ErrorExceptionHandler {
    public function __invoke($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return;
        }

        throw new \ErrorException($message, 0, $severity, $file, $line);
    }
}

<?php

/**
 * Logger utility
 *
 * @package FP\Privacy\Utils
 */

namespace FP\Privacy\Utils;

class Logger
{
    /**
     * Log a message
     *
     * @param string $message
     * @param string $level
     */
    public static function log(string $message, string $level = 'INFO'): void
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG || !defined('WP_DEBUG_LOG') || !WP_DEBUG_LOG) {
            return;
        }

        $timestamp = gmdate('Y-m-d H:i:s');
        $logMessage = sprintf(
            '[%s] [FP-Privacy] [%s] %s',
            $timestamp,
            $level,
            $message
        );

        error_log($logMessage);
    }

    /**
     * Log info message
     */
    public static function info(string $message): void
    {
        self::log($message, 'INFO');
    }

    /**
     * Log error message
     */
    public static function error(string $message): void
    {
        self::log($message, 'ERROR');
    }

    /**
     * Log warning message
     */
    public static function warning(string $message): void
    {
        self::log($message, 'WARNING');
    }

    /**
     * Log debug message
     */
    public static function debug(string $message): void
    {
        self::log($message, 'DEBUG');
    }
}


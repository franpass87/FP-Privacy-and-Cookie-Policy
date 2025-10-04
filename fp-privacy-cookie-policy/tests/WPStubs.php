<?php

declare(strict_types=1);

if (!function_exists('add_query_arg')) {
    function add_query_arg($args, $url)
    {
        if (!is_array($args)) {
            $args = array();
        }

        $query = http_build_query($args, '', '&', PHP_QUERY_RFC3986);

        if ('' === $query) {
            return $url;
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . $query;
    }
}

if (!class_exists('WP_Error')) {
    class WP_Error extends Exception
    {
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing)
    {
        return $thing instanceof WP_Error;
    }
}

if (!function_exists('wp_remote_get')) {
    $GLOBALS['_wp_remote_get_stub']      = null;
    $GLOBALS['_wp_remote_get_requests'] = array();

    function wp_remote_get($url, $args = array())
    {
        $GLOBALS['_wp_remote_get_requests'][] = array(
            'url'  => $url,
            'args' => $args,
        );

        if (is_callable($GLOBALS['_wp_remote_get_stub'])) {
            return call_user_func($GLOBALS['_wp_remote_get_stub'], $url, $args);
        }

        return array(
            'body' => '',
        );
    }
}

if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response)
    {
        if (is_array($response) && isset($response['body'])) {
            return (string) $response['body'];
        }

        return '';
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($value)
    {
        $value = (string) $value;
        $value = strip_tags($value);
        $value = preg_replace('/[\r\n\t ]+/', ' ', $value);

        return trim($value ?? '');
    }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key($key)
    {
        $key = strtolower((string) $key);
        $key = preg_replace('/[^a-z0-9_\-]/', '', $key);

        return $key ?? '';
    }
}

if (!function_exists('esc_url_raw')) {
    function esc_url_raw($url)
    {
        $url = trim((string) $url);

        if ('' === $url) {
            return '';
        }

        if (!preg_match('#^(https?:|/)#i', $url)) {
            return '';
        }

        return filter_var($url, FILTER_SANITIZE_URL) ?: '';
    }
}

if (!function_exists('wp_kses_post')) {
    function wp_kses_post($content)
    {
        $allowed = '<a><br><em><strong><p><ul><ol><li><span>'; // minimal subset for tests.
        $content = preg_replace('#<script\b[^>]*>.*?</script>#is', '', (string) $content);

        return strip_tags($content ?? '', $allowed);
    }
}

if (!function_exists('sanitize_email')) {
    function sanitize_email($email)
    {
        $email = filter_var((string) $email, FILTER_SANITIZE_EMAIL);

        return $email ?: '';
    }
}

if (!function_exists('sanitize_hex_color')) {
    function sanitize_hex_color($color)
    {
        $color = trim((string) $color);

        if (preg_match('/^#([0-9a-fA-F]{3}){1,2}$/', $color)) {
            return strtolower($color);
        }

        return '';
    }
}

if (!function_exists('wp_parse_args')) {
    function wp_parse_args($args, $defaults = array())
    {
        if (is_object($args)) {
            $args = get_object_vars($args);
        } elseif (!is_array($args)) {
            parse_str((string) $args, $args);
        }

        if (!is_array($args)) {
            $args = array();
        }

        return array_merge($defaults, $args);
    }
}

if (!function_exists('rest_sanitize_boolean')) {
    function rest_sanitize_boolean($value)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($value)
    {
        return json_encode($value);
    }
}

<?php

/**
 * Cookie Scanner Service
 *
 * @package FP\Privacy\Services
 */

namespace FP\Privacy\Services;

class CookieScanner
{
    /**
     * Scan a URL for cookies
     */
    public function scanUrl(string $url): array
    {
        $cookies = [];

        try {
            $response = wp_remote_get($url, [
                'timeout' => 30,
                'redirection' => 5,
            ]);

            if (is_wp_error($response)) {
                return $cookies;
            }

            // Analizza i cookie dalla risposta
            $headers = wp_remote_retrieve_headers($response);

            if (isset($headers['set-cookie'])) {
                $setCookies = (array) $headers['set-cookie'];

                foreach ($setCookies as $cookieString) {
                    $cookie = $this->parseCookie($cookieString);
                    if ($cookie) {
                        $cookies[] = $cookie;
                    }
                }
            }

            // Analizza il contenuto HTML per script di terze parti
            $body = wp_remote_retrieve_body($response);
            $thirdPartyScripts = $this->detectThirdPartyScripts($body);

            foreach ($thirdPartyScripts as $script) {
                $cookies[] = [
                    'name' => $script['name'],
                    'domain' => $script['domain'],
                    'category' => $script['category'],
                    'type' => 'third-party',
                ];
            }

        } catch (\Exception $e) {
            // Log error silently
        }

        return $cookies;
    }

    /**
     * Parse a Set-Cookie header
     */
    private function parseCookie(string $cookieString): ?array
    {
        $parts = array_map('trim', explode(';', $cookieString));
        
        if (empty($parts)) {
            return null;
        }

        // Prima parte: nome=valore
        $nameValue = explode('=', $parts[0], 2);
        
        if (count($nameValue) !== 2) {
            return null;
        }

        $cookie = [
            'name' => $nameValue[0],
            'value' => $nameValue[1],
            'type' => 'first-party',
            'category' => $this->categorizeCookie($nameValue[0]),
        ];

        // Attributi aggiuntivi
        foreach (array_slice($parts, 1) as $part) {
            if (stripos($part, 'domain=') === 0) {
                $cookie['domain'] = substr($part, 7);
            } elseif (stripos($part, 'path=') === 0) {
                $cookie['path'] = substr($part, 5);
            } elseif (stripos($part, 'expires=') === 0) {
                $cookie['expires'] = substr($part, 8);
            }
        }

        return $cookie;
    }

    /**
     * Categorize a cookie based on its name
     */
    private function categorizeCookie(string $name): string
    {
        $name = strtolower($name);

        // Cookie necessari
        $necessary = ['wordpress_', 'wp-', 'PHPSESSID', 'fp_privacy_consent'];
        foreach ($necessary as $prefix) {
            if (strpos($name, $prefix) === 0) {
                return 'necessary';
            }
        }

        // Cookie analytics
        $analytics = ['_ga', '_gid', '_gat', 'gtm'];
        foreach ($analytics as $prefix) {
            if (strpos($name, $prefix) === 0) {
                return 'analytics';
            }
        }

        // Cookie marketing
        $marketing = ['_fb', 'fr', 'ads', 'pixel'];
        foreach ($marketing as $prefix) {
            if (strpos($name, $prefix) === 0) {
                return 'marketing';
            }
        }

        return 'necessary';
    }

    /**
     * Detect third-party scripts in HTML
     */
    private function detectThirdPartyScripts(string $html): array
    {
        $scripts = [];

        $patterns = [
            'Google Analytics' => [
                'domain' => 'google-analytics.com',
                'category' => 'analytics',
                'patterns' => ['/google-analytics\.com/', '/googletagmanager\.com/'],
            ],
            'Facebook Pixel' => [
                'domain' => 'facebook.com',
                'category' => 'marketing',
                'patterns' => ['/connect\.facebook\.net/'],
            ],
            'Google Ads' => [
                'domain' => 'googleadservices.com',
                'category' => 'marketing',
                'patterns' => ['/googleadservices\.com/', '/doubleclick\.net/'],
            ],
        ];

        foreach ($patterns as $name => $config) {
            foreach ($config['patterns'] as $pattern) {
                if (preg_match($pattern, $html)) {
                    $scripts[] = [
                        'name' => $name,
                        'domain' => $config['domain'],
                        'category' => $config['category'],
                    ];
                    break;
                }
            }
        }

        return $scripts;
    }
}


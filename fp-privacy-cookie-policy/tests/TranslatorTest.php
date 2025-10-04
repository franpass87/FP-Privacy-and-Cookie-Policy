<?php

declare(strict_types=1);

require_once __DIR__ . '/WPStubs.php';

use FP\Privacy\Utils\Translator;
use PHPUnit\Framework\TestCase;

final class TranslatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetRemoteStub();
    }

    protected function tearDown(): void
    {
        $this->resetRemoteStub();
        parent::tearDown();
    }

    public function testTranslateStringSkipsRemoteCallWhenLocalesMatch(): void
    {
        global $_wp_remote_get_stub, $_wp_remote_get_requests;

        $_wp_remote_get_stub = function (): void {
            throw new RuntimeException('Remote translation should not be invoked when locales match.');
        };

        $translator = new Translator();
        $result     = $translator->translate_string('Ciao', 'it_IT', 'it');

        $this->assertSame('Ciao', $result);
        $this->assertSame(array(), $_wp_remote_get_requests);
    }

    public function testTranslateStringReturnsDecodedRemoteResponse(): void
    {
        global $_wp_remote_get_stub;

        $capturedUrl = null;
        $_wp_remote_get_stub = function (string $url) use (&$capturedUrl): array {
            $capturedUrl = $url;

            return array(
                'body' => json_encode(
                    array(
                        'responseData' => array(
                            'translatedText' => 'Hello &amp; welcome',
                        ),
                    )
                ),
            );
        };

        $translator = new Translator();
        $result     = $translator->translate_string('Ciao', 'it_IT', 'en_US');

        $this->assertSame('Hello & welcome', $result);
        $this->assertNotNull($capturedUrl);
        $this->assertStringContainsString('langpair=it%7Cen', (string) $capturedUrl);
    }

    public function testTranslateBannerTextsTranslatesTranslatableFields(): void
    {
        global $_wp_remote_get_stub;

        $_wp_remote_get_stub = static function (string $url): array {
            $parts = parse_url($url);
            parse_str($parts['query'] ?? '', $query);
            $text = $query['q'] ?? '';

            return array(
                'body' => json_encode(
                    array(
                        'responseData' => array(
                            'translatedText' => 'translated:' . $text,
                        ),
                    )
                ),
            );
        };

        $translator = new Translator();
        $result     = $translator->translate_banner_texts(
            array(
                'title'       => 'Titolo',
                'message'     => 'Messaggio',
                'btn_accept'  => 'Accetta',
                'link_policy' => '/privacy',
            ),
            'it_IT',
            'en_US'
        );

        $this->assertSame('translated:Titolo', $result['title']);
        $this->assertSame('translated:Messaggio', $result['message']);
        $this->assertSame('translated:Accetta', $result['btn_accept']);
        $this->assertSame('/privacy', $result['link_policy']);
    }

    private function resetRemoteStub(): void
    {
        global $_wp_remote_get_stub, $_wp_remote_get_requests;

        $_wp_remote_get_stub     = null;
        $_wp_remote_get_requests = array();
    }
}

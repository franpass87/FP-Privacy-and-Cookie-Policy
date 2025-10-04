<?php

declare(strict_types=1);

require_once __DIR__ . '/WPStubs.php';

use FP\Privacy\Utils\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorAutoTranslationsTest extends TestCase
{
    public function testSanitizeAutoTranslationsNormalizesPayload(): void
    {
        $defaults = array(
            'title'           => 'Default title',
            'message'         => 'Default message',
            'btn_accept'      => 'Accept',
            'btn_reject'      => 'Reject',
            'btn_prefs'       => 'Preferences',
            'modal_title'     => 'Modal title',
            'modal_close'     => 'Close',
            'modal_save'      => 'Save',
            'revision_notice' => 'Notice',
            'toggle_locked'   => 'Always on',
            'toggle_enabled'  => 'Enabled',
            'debug_label'     => 'Debug',
            'link_policy'     => '',
        );

        $translations = array(
            'banner' => array(
                'en_US' => array(
                    'hash'  => " <b>hash</b> ",
                    'texts' => array(
                        'title'       => '<strong>Hello</strong>',
                        'message'     => '<p>Welcome<script>alert(1)</script></p>',
                        'btn_accept'  => ' <em>Allow</em> ',
                        'link_policy' => 'javascript:alert(1)',
                    ),
                ),
            ),
            'categories' => array(
                'en_US' => array(
                    'hash'  => ' abc ',
                    'items' => array(
                        'Invalid Slug!' => array(
                            'label'       => '<em>Marketing</em>',
                            'description' => '<p>Trackers<script></script></p>',
                        ),
                        '   ' => array(
                            'label'       => 'Ignored',
                            'description' => 'Ignored',
                        ),
                    ),
                ),
            ),
        );

        $sanitized = Validator::sanitize_auto_translations($translations, $defaults);

        $this->assertArrayHasKey('banner', $sanitized);
        $this->assertSame('hash', $sanitized['banner']['en_US']['hash']);
        $this->assertSame('Hello', $sanitized['banner']['en_US']['texts']['title']);
        $this->assertSame('<p>Welcome</p>', $sanitized['banner']['en_US']['texts']['message']);
        $this->assertSame('Allow', $sanitized['banner']['en_US']['texts']['btn_accept']);
        $this->assertSame('', $sanitized['banner']['en_US']['texts']['link_policy']);

        $this->assertArrayHasKey('categories', $sanitized);
        $this->assertSame('abc', $sanitized['categories']['en_US']['hash']);
        $this->assertArrayHasKey('invalidslug', $sanitized['categories']['en_US']['items']);
        $this->assertSame('Marketing', $sanitized['categories']['en_US']['items']['invalidslug']['label']);
        $this->assertSame('<p>Trackers</p>', $sanitized['categories']['en_US']['items']['invalidslug']['description']);
        $this->assertArrayNotHasKey('', $sanitized['categories']['en_US']['items']);
    }
}

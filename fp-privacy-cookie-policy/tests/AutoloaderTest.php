<?php

declare(strict_types=1);

use Composer\Autoload\ClassLoader;
use PHPUnit\Framework\TestCase;

final class AutoloaderTest extends TestCase
{
    /**
     * Ensures that the fallback autoloader can load core plugin classes when the Composer autoloader is unavailable.
     *
     * @runInSeparateProcess
     */
    public function testLoadsPluginClassFromSourceTree(): void
    {
        $this->requirePluginBootstrap();

        $this->assertTrue(class_exists('FP\\Privacy\\Plugin'));
    }

    /**
     * The autoloader must ignore symlinks that point outside the plugin source tree to prevent unintended file loads.
     *
     * @runInSeparateProcess
     */
    public function testRejectsSymlinkedFilesOutsideSourceTree(): void
    {
        $pluginRoot = dirname(__DIR__);
        $outside    = sys_get_temp_dir() . '/fp-privacy-autoloader-outside.php';
        $symlinkDir = $pluginRoot . '/src/AutoloaderTest';
        $symlink    = $symlinkDir . '/Outside.php';

        if (!is_dir($symlinkDir) && !mkdir($symlinkDir, 0777, true) && !is_dir($symlinkDir)) {
            $this->fail('Unable to create temporary autoloader test directory.');
        }

        file_put_contents($outside, "<?php\\nclass OutsideAutoloaderSymlinked {}\\n");

        if (file_exists($symlink)) {
            unlink($symlink);
        }

        if (!symlink($outside, $symlink)) {
            $this->fail('Unable to create symlink for autoloader test.');
        }

        try {
            $this->requirePluginBootstrap();

            $this->assertFalse(class_exists('FP\\Privacy\\AutoloaderTest\\Outside'));
            $this->assertFalse(class_exists('OutsideAutoloaderSymlinked', false));
        } finally {
            if (file_exists($symlink)) {
                unlink($symlink);
            }

            if (is_dir($symlinkDir)) {
                rmdir($symlinkDir);
            }

            if (file_exists($outside)) {
                unlink($outside);
            }
        }
    }

    private function requirePluginBootstrap(): void
    {
        if (!defined('ABSPATH')) {
            define('ABSPATH', __DIR__ . '/');
        }

        $this->registerWordPressStubs();
        $this->disableComposerAutoloaders();

        require_once dirname(__DIR__) . '/fp-privacy-cookie-policy.php';
    }

    private function disableComposerAutoloaders(): void
    {
        $autoloaders = spl_autoload_functions();

        if (false === $autoloaders) {
            return;
        }

        foreach ($autoloaders as $autoloader) {
            if (!is_array($autoloader)) {
                continue;
            }

            [$loader] = $autoloader;

            if (class_exists(ClassLoader::class, false) && $loader instanceof ClassLoader) {
                $loader->setPsr4('FP\\Privacy\\', array());
            }
        }
    }

    private function registerWordPressStubs(): void
    {
        if (!function_exists('plugin_dir_path')) {
            function plugin_dir_path($file)
            {
                return dirname($file) . DIRECTORY_SEPARATOR;
            }
        }

        if (!function_exists('plugin_dir_url')) {
            function plugin_dir_url($file)
            {
                return 'https://example.test/' . basename(dirname($file)) . '/';
            }
        }

        if (!function_exists('register_activation_hook')) {
            function register_activation_hook($file, $callback)
            {
                // Intentionally left blank for the test stub.
            }
        }

        if (!function_exists('register_deactivation_hook')) {
            function register_deactivation_hook($file, $callback)
            {
                // Intentionally left blank for the test stub.
            }
        }

        if (!function_exists('add_action')) {
            function add_action($hook, $callback, $priority = 10, $accepted_args = 1)
            {
                // No-op hook registration stub for tests.
            }
        }

        if (!function_exists('is_multisite')) {
            function is_multisite()
            {
                return false;
            }
        }

        if (!function_exists('get_sites')) {
            function get_sites($args = array())
            {
                return array();
            }
        }

        if (!function_exists('wp_next_scheduled')) {
            function wp_next_scheduled($hook)
            {
                return false;
            }
        }

        if (!function_exists('wp_schedule_event')) {
            function wp_schedule_event($timestamp, $recurrence, $hook)
            {
                return true;
            }
        }

        if (!function_exists('wp_clear_scheduled_hook')) {
            function wp_clear_scheduled_hook($hook)
            {
                return true;
            }
        }
    }
}

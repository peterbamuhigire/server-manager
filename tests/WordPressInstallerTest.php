<?php

/**
 * WordPress Installer Test
 * Tests for WordPress installation functionality
 */

require_once __DIR__ . '/../app/Services/WordPressInstaller.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

class WordPressInstallerTest
{
    private $installer;
    private $testSubdomain;
    private $testAppId;
    private $testDbId;

    public function __construct()
    {
        $this->installer = new WordPressInstaller();
    }

    public function run()
    {
        echo "Running WordPress Installer Tests...\n";

        try {
            $this->testSaltGeneration();
            $this->testWpConfigTemplate();
            // Note: Full WordPress installation test would require:
            // - Internet connection to download WordPress
            // - MySQL database creation permissions
            // - Apache configuration
            // These tests are integration tests best run in a staging environment

            echo "✓ All WordPress Installer tests passed!\n\n";
            return true;
        } catch (Exception $e) {
            echo "✗ Test failed: " . $e->getMessage() . "\n";
            echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
            return false;
        }
    }

    private function testSaltGeneration()
    {
        echo "  Testing salt key generation...\n";

        // Use reflection to test private method
        $reflection = new ReflectionClass($this->installer);
        $method = $reflection->getMethod('generateSaltKeys');
        $method->setAccessible(true);

        $salts = $method->invoke($this->installer);

        if (empty($salts)) {
            throw new Exception('Salt keys generation failed');
        }

        // Check that all required keys are present
        $requiredKeys = [
            'AUTH_KEY',
            'SECURE_AUTH_KEY',
            'LOGGED_IN_KEY',
            'NONCE_KEY',
            'AUTH_SALT',
            'SECURE_AUTH_SALT',
            'LOGGED_IN_SALT',
            'NONCE_SALT'
        ];

        foreach ($requiredKeys as $key) {
            if (strpos($salts, $key) === false) {
                throw new Exception("Missing required salt key: $key");
            }
        }

        // Check that salts contain define statements
        if (substr_count($salts, "define('") !== 8) {
            throw new Exception('Incorrect number of salt definitions');
        }
    }

    private function testWpConfigTemplate()
    {
        echo "  Testing wp-config template generation...\n";

        // Use reflection to test private method
        $reflection = new ReflectionClass($this->installer);
        $method = $reflection->getMethod('getDefaultWpConfigTemplate');
        $method->setAccessible(true);

        $template = $method->invoke($this->installer);

        if (empty($template)) {
            throw new Exception('Wp-config template generation failed');
        }

        // Check for required placeholders
        $requiredPlaceholders = [
            '{{DB_NAME}}',
            '{{DB_USER}}',
            '{{DB_PASSWORD}}',
            '{{DB_HOST}}',
            '{{TABLE_PREFIX}}',
            '{{SALT_KEYS}}'
        ];

        foreach ($requiredPlaceholders as $placeholder) {
            if (strpos($template, $placeholder) === false) {
                throw new Exception("Missing required placeholder: $placeholder");
            }
        }

        // Check for WordPress essentials
        if (strpos($template, 'wp-settings.php') === false) {
            throw new Exception('Missing wp-settings.php require');
        }

        if (strpos($template, 'ABSPATH') === false) {
            throw new Exception('Missing ABSPATH definition');
        }
    }

    private function testRandomStringGeneration()
    {
        echo "  Testing random string generation...\n";

        // Use reflection to test private method
        $reflection = new ReflectionClass($this->installer);
        $method = $reflection->getMethod('generateRandomString');
        $method->setAccessible(true);

        $length = 64;
        $randomString = $method->invoke($this->installer, $length);

        if (strlen($randomString) !== $length) {
            throw new Exception("Random string length mismatch. Expected: $length, Got: " . strlen($randomString));
        }

        // Generate another string and ensure they're different
        $randomString2 = $method->invoke($this->installer, $length);

        if ($randomString === $randomString2) {
            throw new Exception('Random strings should be different');
        }
    }
}

// Run test if executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $test = new WordPressInstallerTest();
    exit($test->run() ? 0 : 1);
}

<?php

/**
 * SSL Certificate Model Test
 * Tests for SslCertificate model methods
 */

require_once __DIR__ . '/../app/Models/SslCertificate.php';
require_once __DIR__ . '/../app/Models/Domain.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

class SslCertificateModelTest
{
    private $certModel;
    private $domainModel;
    private $testDomainId;
    private $testUserId = 1; // Assuming user ID 1 exists
    private $testCertId;

    public function __construct()
    {
        $this->certModel = new SslCertificate();
        $this->domainModel = new Domain();
    }

    public function run()
    {
        echo "Running SSL Certificate Model Tests...\n";

        try {
            $this->setup();
            $this->testCreateCertificate();
            $this->testFindCertificate();
            $this->testGetAllCertificates();
            $this->testGetByDomain();
            $this->testGetExpiringSoon();
            $this->testIsExpiring();
            $this->testUpdateCertificate();
            $this->cleanup();

            echo "✓ All SSL Certificate Model tests passed!\n\n";
            return true;
        } catch (Exception $e) {
            echo "✗ Test failed: " . $e->getMessage() . "\n";
            echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
            $this->cleanup();
            return false;
        }
    }

    private function setup()
    {
        echo "  Setting up test data...\n";

        // Create test domain
        $this->testDomainId = $this->domainModel->create([
            'domain_name' => 'ssl-test.example.com',
            'app_id' => null,
            'document_root' => null,
            'dns_configured' => 1,
            'vhost_configured' => 0,
            'ssl_enabled' => 0,
            'status' => 'pending',
            'user_id' => $this->testUserId
        ]);

        if (!$this->testDomainId) {
            throw new Exception('Failed to create test domain');
        }
    }

    private function testCreateCertificate()
    {
        echo "  Testing certificate creation...\n";

        $this->testCertId = $this->certModel->create([
            'domain_id' => $this->testDomainId,
            'certificate_path' => '/test/path/cert.pem',
            'private_key_path' => '/test/path/key.pem',
            'chain_path' => '/test/path/chain.pem',
            'provider' => 'letsencrypt',
            'status' => 'active',
            'issued_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+90 days')),
            'auto_renew' => 1,
            'user_id' => $this->testUserId
        ]);

        if (!$this->testCertId) {
            throw new Exception('Failed to create SSL certificate');
        }
    }

    private function testFindCertificate()
    {
        echo "  Testing find certificate...\n";

        $cert = $this->certModel->find($this->testCertId);

        if (!$cert) {
            throw new Exception('Certificate not found');
        }

        if ($cert['domain_id'] != $this->testDomainId) {
            throw new Exception('Domain ID mismatch');
        }

        if ($cert['provider'] !== 'letsencrypt') {
            throw new Exception('Provider mismatch');
        }

        if ($cert['status'] !== 'active') {
            throw new Exception('Status mismatch');
        }

        if ($cert['auto_renew'] != 1) {
            throw new Exception('Auto-renew mismatch');
        }
    }

    private function testGetAllCertificates()
    {
        echo "  Testing get all certificates...\n";

        $certs = $this->certModel->getAll();

        if (!is_array($certs)) {
            throw new Exception('getAll should return an array');
        }

        $found = false;
        foreach ($certs as $cert) {
            if ($cert['id'] == $this->testCertId) {
                $found = true;
                if (!isset($cert['domain_name'])) {
                    throw new Exception('domain_name should be joined in getAll');
                }
                if (!isset($cert['creator_name'])) {
                    throw new Exception('creator_name should be joined in getAll');
                }
                break;
            }
        }

        if (!$found) {
            throw new Exception('Test certificate not found in getAll results');
        }
    }

    private function testGetByDomain()
    {
        echo "  Testing get certificate by domain...\n";

        $cert = $this->certModel->getByDomain($this->testDomainId);

        if (!$cert) {
            throw new Exception('Certificate not found by domain');
        }

        if ($cert['id'] != $this->testCertId) {
            throw new Exception('Wrong certificate returned');
        }

        if (!isset($cert['domain_name'])) {
            throw new Exception('domain_name should be joined in getByDomain');
        }
    }

    private function testGetExpiringSoon()
    {
        echo "  Testing get expiring soon certificates...\n";

        // Create a certificate expiring in 15 days
        $expiringCertId = $this->certModel->create([
            'domain_id' => $this->testDomainId,
            'certificate_path' => '/test/path/expiring-cert.pem',
            'private_key_path' => '/test/path/expiring-key.pem',
            'chain_path' => null,
            'provider' => 'self-signed',
            'status' => 'active',
            'issued_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+15 days')),
            'auto_renew' => 1,
            'user_id' => $this->testUserId
        ]);

        $expiringSoon = $this->certModel->getExpiringSoon(30);

        if (!is_array($expiringSoon)) {
            throw new Exception('getExpiringSoon should return an array');
        }

        $found = false;
        foreach ($expiringSoon as $cert) {
            if ($cert['id'] == $expiringCertId) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            throw new Exception('Expiring certificate not found in getExpiringSoon results');
        }

        // Clean up
        $this->certModel->delete($expiringCertId);
    }

    private function testIsExpiring()
    {
        echo "  Testing is expiring check...\n";

        // Certificate expires in 90 days, should not be expiring within 30 days
        $isExpiring = $this->certModel->isExpiring($this->testCertId, 30);

        if ($isExpiring) {
            throw new Exception('Certificate should not be expiring within 30 days');
        }

        // Should be expiring within 100 days
        $isExpiring = $this->certModel->isExpiring($this->testCertId, 100);

        if (!$isExpiring) {
            throw new Exception('Certificate should be expiring within 100 days');
        }
    }

    private function testUpdateCertificate()
    {
        echo "  Testing certificate update...\n";

        $updated = $this->certModel->update($this->testCertId, [
            'status' => 'renewing',
            'last_renewed_at' => date('Y-m-d H:i:s')
        ]);

        if (!$updated) {
            throw new Exception('Failed to update certificate');
        }

        $cert = $this->certModel->find($this->testCertId);

        if ($cert['status'] !== 'renewing') {
            throw new Exception('Status was not updated');
        }

        if (empty($cert['last_renewed_at'])) {
            throw new Exception('last_renewed_at was not updated');
        }
    }

    private function cleanup()
    {
        echo "  Cleaning up test data...\n";

        if ($this->testCertId) {
            $this->certModel->delete($this->testCertId);
        }

        if ($this->testDomainId) {
            $this->domainModel->delete($this->testDomainId);
        }
    }
}

// Run test if executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $test = new SslCertificateModelTest();
    exit($test->run() ? 0 : 1);
}

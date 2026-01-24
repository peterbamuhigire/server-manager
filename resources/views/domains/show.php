<?php
$pageTitle = $domain['domain_name'];
ob_start();
?>

<div class="mb-6">
    <a href="/domains" class="text-blue-600 hover:text-blue-800 text-sm font-medium">← Back to Domains</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-4">Domain Details</h2>
            <div class="space-y-3">
                <div><strong>Domain:</strong> <?= e($domain['domain_name']) ?></div>
                <div><strong>Status:</strong>
                    <span class="px-2 py-1 text-xs rounded-full
                        <?php if ($domain['status'] === 'active'): ?>bg-green-100 text-green-800
                        <?php elseif ($domain['status'] === 'error'): ?>bg-red-100 text-red-800
                        <?php else: ?>bg-yellow-100 text-yellow-800<?php endif; ?>">
                        <?= ucfirst($domain['status']) ?>
                    </span>
                </div>
                <div><strong>DNS Configured:</strong> <?= $domain['dns_configured'] ? 'Yes' : 'No' ?></div>
                <div><strong>VHost Configured:</strong> <?= $domain['vhost_configured'] ? 'Yes' : 'No' ?></div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 mt-6">
            <h2 class="text-xl font-bold mb-4">DNS Check</h2>
            <?php if ($dnsCheck['configured']): ?>
                <div class="bg-green-50 border border-green-200 rounded p-4">
                    <p class="text-green-800">✓ DNS is correctly configured</p>
                </div>
            <?php else: ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded p-4">
                    <p class="text-yellow-800">⚠ DNS not configured</p>
                    <p class="text-sm mt-2"><?= e($dnsCheck['message']) ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold mb-4">Actions</h3>
            <?php if (!$domain['vhost_configured']): ?>
                <button onclick="configureDomain(<?= $domain['id'] ?>)"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded mb-2">
                    Configure Domain
                </button>
            <?php endif; ?>
            <button onclick="checkDNS(<?= $domain['id'] ?>)"
                class="w-full bg-gray-600 hover:bg-gray-700 text-white py-2 rounded mb-2">
                Check DNS
            </button>
            <button onclick="deleteDomain(<?= $domain['id'] ?>)"
                class="w-full bg-red-600 hover:bg-red-700 text-white py-2 rounded">
                Delete Domain
            </button>
        </div>
    </div>
</div>

<script>
function configureDomain(id) {
    if (!confirm('Configure this domain?')) return;
    fetch(`/domains/${id}/configure`, {
        method: 'POST',
        headers: {'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content}
    }).then(r => r.json()).then(data => {
        if (data.success) location.reload();
        else alert(data.message);
    });
}

function checkDNS(id) {
    fetch(`/domains/${id}/check-dns`).then(r => r.json()).then(data => {
        if (data.success) location.reload();
    });
}

function deleteDomain(id) {
    if (!confirm('Delete this domain?')) return;
    fetch(`/domains/${id}`, {
        method: 'DELETE',
        headers: {'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content}
    }).then(r => r.json()).then(data => {
        if (data.success) location.href = '/domains';
        else alert(data.message);
    });
}
</script>

<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>

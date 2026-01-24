<?php
$pageTitle = 'Create Cron Job';
ob_start();
?>

<div class="mb-6">
    <div class="flex items-center gap-2 text-sm text-gray-600 mb-4">
        <a href="/cron" class="hover:text-gray-900">Cron Jobs</a>
        <span>/</span>
        <span>Create</span>
    </div>
    <h1 class="text-2xl font-bold text-gray-900">Create Cron Job</h1>
    <p class="text-gray-600 mt-1">Schedule a new automated task</p>
</div>

<div class="max-w-3xl">
    <form method="POST" action="/cron/store" class="bg-white rounded-lg shadow-md p-6">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <div class="space-y-6">
            <!-- Job Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                    Job Name <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="e.g., Database Backup"
                >
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                    Description
                </label>
                <textarea
                    id="description"
                    name="description"
                    rows="3"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="What does this job do?"
                ></textarea>
            </div>

            <!-- Command -->
            <div>
                <label for="command" class="block text-sm font-medium text-gray-700 mb-2">
                    Command <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="command"
                    name="command"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm"
                    placeholder="/usr/bin/php /var/www/html/script.php"
                >
                <p class="mt-1 text-sm text-gray-500">Full path to the command or script to execute</p>
            </div>

            <!-- Schedule -->
            <div>
                <label for="schedule" class="block text-sm font-medium text-gray-700 mb-2">
                    Schedule <span class="text-red-500">*</span>
                </label>

                <!-- Common Schedules Dropdown -->
                <select
                    id="common_schedule"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent mb-2"
                    onchange="if(this.value) document.getElementById('schedule').value = this.value"
                >
                    <option value="">-- Select a common schedule --</option>
                    <?php foreach ($commonSchedules as $label => $schedule): ?>
                        <option value="<?= htmlspecialchars($schedule) ?>"><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- Custom Schedule Input -->
                <input
                    type="text"
                    id="schedule"
                    name="schedule"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm"
                    placeholder="* * * * *"
                >
                <p class="mt-1 text-sm text-gray-500">Cron expression: minute hour day month weekday</p>
            </div>

            <!-- Schedule Reference Card -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Cron Schedule Format</h3>
                <div class="text-xs text-gray-600 space-y-1 font-mono">
                    <div class="grid grid-cols-5 gap-2 mb-2">
                        <div class="text-center">
                            <div class="font-bold">*</div>
                            <div class="text-gray-500">minute</div>
                            <div class="text-gray-400">(0-59)</div>
                        </div>
                        <div class="text-center">
                            <div class="font-bold">*</div>
                            <div class="text-gray-500">hour</div>
                            <div class="text-gray-400">(0-23)</div>
                        </div>
                        <div class="text-center">
                            <div class="font-bold">*</div>
                            <div class="text-gray-500">day</div>
                            <div class="text-gray-400">(1-31)</div>
                        </div>
                        <div class="text-center">
                            <div class="font-bold">*</div>
                            <div class="text-gray-500">month</div>
                            <div class="text-gray-400">(1-12)</div>
                        </div>
                        <div class="text-center">
                            <div class="font-bold">*</div>
                            <div class="text-gray-500">weekday</div>
                            <div class="text-gray-400">(0-6)</div>
                        </div>
                    </div>
                    <div class="pt-2 border-t border-gray-300">
                        <p><strong>Examples:</strong></p>
                        <p>*/5 * * * * = Every 5 minutes</p>
                        <p>0 2 * * * = Daily at 2:00 AM</p>
                        <p>0 0 * * 0 = Weekly on Sunday at midnight</p>
                    </div>
                </div>
            </div>

            <!-- Ubuntu/Debian Notice -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-blue-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="ml-3 text-sm text-blue-700">
                        <p><strong>Ubuntu/Debian:</strong> Jobs will be added to the www-data user crontab. Make sure commands use full paths and have proper permissions.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="mt-6 flex gap-3">
            <button
                type="submit"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-200"
            >
                Create Cron Job
            </button>
            <a
                href="/cron"
                class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"
            >
                Cancel
            </a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

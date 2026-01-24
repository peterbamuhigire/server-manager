<?php
$pageTitle = 'Web Terminal';
ob_start();
?>

<div class="mb-6" x-data="terminal()">
    <div class="flex justify-between items-center mb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Web Terminal</h1>
            <p class="text-gray-600 mt-1">Execute shell commands on your server</p>
        </div>
        <div class="flex gap-2">
            <a href="/terminal/history" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                Command History
            </a>
        </div>
    </div>

    <!-- Statistics -->
    <?php if ($stats && $stats['total_commands'] > 0): ?>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-500">Total Commands</p>
            <p class="text-2xl font-semibold text-gray-900"><?= number_format($stats['total_commands']) ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-500">Successful</p>
            <p class="text-2xl font-semibold text-green-600"><?= number_format($stats['successful']) ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-500">Failed</p>
            <p class="text-2xl font-semibold text-red-600"><?= number_format($stats['failed']) ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-500">Avg. Execution Time</p>
            <p class="text-2xl font-semibold text-gray-900"><?= number_format($stats['avg_execution_time'], 3) ?>s</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Terminal Window -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="bg-gray-800 px-4 py-2 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <div class="flex gap-1.5">
                    <div class="w-3 h-3 rounded-full bg-red-500"></div>
                    <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                </div>
                <span class="ml-3 text-sm font-mono text-gray-300"><?= htmlspecialchars($workingDir) ?></span>
            </div>
            <button @click="clearTerminal()" class="px-3 py-1 bg-gray-700 text-gray-300 rounded hover:bg-gray-600 text-sm">
                Clear
            </button>
        </div>

        <!-- Terminal Output -->
        <div id="terminal-output" class="bg-gray-900 p-4 font-mono text-sm text-gray-100 overflow-y-auto" style="min-height: 400px; max-height: 600px;">
            <div class="text-green-400">Chwezi Server Manager - Web Terminal</div>
            <div class="text-gray-400 mb-4">Type a command and press Enter to execute. Type 'help' for common commands.</div>
        </div>

        <!-- Command Input -->
        <div class="bg-gray-800 p-4 flex items-center gap-2">
            <span class="text-green-400 font-mono">$</span>
            <input
                type="text"
                id="command-input"
                x-model="command"
                @keydown.enter="executeCommand()"
                @keydown.up="navigateHistory('up')"
                @keydown.down="navigateHistory('down')"
                @keydown.tab.prevent="autoComplete()"
                class="flex-1 bg-transparent text-gray-100 font-mono outline-none border-none focus:ring-0"
                placeholder="Enter command..."
                autocomplete="off"
                :disabled="executing"
            >
            <button
                @click="executeCommand()"
                :disabled="executing || command.trim() === ''"
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span x-show="!executing">Execute</span>
                <span x-show="executing">Executing...</span>
            </button>
        </div>
    </div>

    <!-- Common Commands -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Common Commands</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($commonCommands as $category => $commands): ?>
                <div>
                    <h4 class="font-semibold text-gray-700 mb-2"><?= htmlspecialchars($category) ?></h4>
                    <div class="space-y-1">
                        <?php foreach ($commands as $cmd => $description): ?>
                            <button
                                @click="command = '<?= addslashes($cmd) ?>'; document.getElementById('command-input').focus();"
                                class="w-full text-left px-3 py-2 text-sm bg-gray-50 hover:bg-gray-100 rounded flex justify-between items-center group"
                            >
                                <code class="text-blue-600"><?= htmlspecialchars($cmd) ?></code>
                                <span class="text-xs text-gray-500 hidden group-hover:inline"><?= htmlspecialchars($description) ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div x-show="showConfirmation" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" x-cloak>
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Dangerous Command Warning</h3>
            <p class="text-gray-600 mb-4">
                The command you're about to execute is potentially dangerous. Are you sure you want to proceed?
            </p>
            <div class="bg-gray-900 p-3 rounded mb-4">
                <code class="text-red-400 text-sm" x-text="pendingCommand"></code>
            </div>
            <div class="flex gap-3 justify-end">
                <button
                    @click="showConfirmation = false; pendingCommand = ''"
                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400"
                >
                    Cancel
                </button>
                <button
                    @click="confirmExecution()"
                    class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
                >
                    Execute Anyway
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function terminal() {
    return {
        command: '',
        executing: false,
        commandHistory: [],
        historyIndex: -1,
        showConfirmation: false,
        pendingCommand: '',

        init() {
            // Focus on command input
            document.getElementById('command-input').focus();

            // Load command history
            this.loadHistory();
        },

        async executeCommand() {
            if (this.executing || this.command.trim() === '') return;

            const cmd = this.command.trim();

            // Handle special commands
            if (cmd === 'clear') {
                this.clearTerminal();
                this.command = '';
                return;
            }

            if (cmd === 'help') {
                this.showHelp();
                this.command = '';
                return;
            }

            this.executing = true;
            this.appendOutput('$ ' + cmd, 'text-green-400');

            try {
                const response = await fetch('/terminal/execute', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        command: cmd,
                        confirmed: 'false',
                        csrf_token: '<?= $_SESSION['csrf_token'] ?>'
                    })
                });

                const result = await response.json();

                if (result.requires_confirmation) {
                    this.showConfirmation = true;
                    this.pendingCommand = cmd;
                } else if (result.success) {
                    this.appendOutput(result.output || '(no output)', result.exit_code === 0 ? 'text-gray-300' : 'text-red-400');
                    if (result.execution_time) {
                        this.appendOutput(`Execution time: ${result.execution_time}s`, 'text-gray-500 text-xs');
                    }
                    this.commandHistory.unshift(cmd);
                    this.historyIndex = -1;
                } else {
                    this.appendOutput('Error: ' + result.message, 'text-red-400');
                }
            } catch (error) {
                this.appendOutput('Error: ' + error.message, 'text-red-400');
            }

            this.executing = false;
            this.command = '';
        },

        async confirmExecution() {
            this.showConfirmation = false;
            this.executing = true;

            const cmd = this.pendingCommand;
            this.appendOutput('$ ' + cmd, 'text-green-400');

            try {
                const response = await fetch('/terminal/execute', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        command: cmd,
                        confirmed: 'true',
                        csrf_token: '<?= $_SESSION['csrf_token'] ?>'
                    })
                });

                const result = await response.json();

                if (result.success) {
                    this.appendOutput(result.output || '(no output)', result.exit_code === 0 ? 'text-gray-300' : 'text-red-400');
                    if (result.execution_time) {
                        this.appendOutput(`Execution time: ${result.execution_time}s`, 'text-gray-500 text-xs');
                    }
                    this.commandHistory.unshift(cmd);
                    this.historyIndex = -1;
                } else {
                    this.appendOutput('Error: ' + result.message, 'text-red-400');
                }
            } catch (error) {
                this.appendOutput('Error: ' + error.message, 'text-red-400');
            }

            this.executing = false;
            this.pendingCommand = '';
        },

        appendOutput(text, className = 'text-gray-300') {
            const output = document.getElementById('terminal-output');
            const div = document.createElement('div');
            div.className = className;
            div.textContent = text;
            output.appendChild(div);
            output.scrollTop = output.scrollHeight;
        },

        clearTerminal() {
            document.getElementById('terminal-output').innerHTML = '';
            this.appendOutput('Terminal cleared', 'text-gray-500 text-sm');
        },

        showHelp() {
            this.appendOutput('Common Commands:', 'text-yellow-400 font-semibold');
            this.appendOutput('  clear     - Clear terminal output', 'text-gray-400');
            this.appendOutput('  help      - Show this help message', 'text-gray-400');
            this.appendOutput('  ls        - List files', 'text-gray-400');
            this.appendOutput('  pwd       - Print working directory', 'text-gray-400');
            this.appendOutput('  whoami    - Show current user', 'text-gray-400');
            this.appendOutput('\nUse arrow keys to navigate command history', 'text-gray-500 text-sm');
        },

        navigateHistory(direction) {
            if (this.commandHistory.length === 0) return;

            if (direction === 'up') {
                if (this.historyIndex < this.commandHistory.length - 1) {
                    this.historyIndex++;
                    this.command = this.commandHistory[this.historyIndex];
                }
            } else if (direction === 'down') {
                if (this.historyIndex > 0) {
                    this.historyIndex--;
                    this.command = this.commandHistory[this.historyIndex];
                } else if (this.historyIndex === 0) {
                    this.historyIndex = -1;
                    this.command = '';
                }
            }
        },

        async loadHistory() {
            try {
                const response = await fetch('/terminal/history?limit=20');
                const result = await response.json();
                if (result.success) {
                    this.commandHistory = result.history.map(h => h.command);
                }
            } catch (error) {
                console.error('Failed to load history:', error);
            }
        },

        autoComplete() {
            // Simple tab completion (can be enhanced)
            const commonCommands = ['ls', 'pwd', 'cd', 'mkdir', 'rm', 'cp', 'mv', 'cat', 'grep', 'find', 'ps', 'top', 'df', 'du', 'systemctl'];
            const input = this.command.trim();

            if (!input) return;

            const matches = commonCommands.filter(cmd => cmd.startsWith(input));
            if (matches.length === 1) {
                this.command = matches[0] + ' ';
            }
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

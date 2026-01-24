# Chwezi Server Manager - Test Suite

This directory contains unit and integration tests for the Chwezi Server Manager application.

## Test Files

### Phase 9: Cron Jobs
- **CronServiceTest.php** - Tests cron job management, schedule validation, job execution, and system crontab integration

### Phase 10: Log Viewer
- **LogViewerServiceTest.php** - Tests log file discovery, reading, tailing, searching, and clearing functionality

### Other Tests
- **AppModelTest.php** - Tests application model CRUD operations
- **DomainModelTest.php** - Tests domain model operations
- **MysqlDatabaseModelTest.php** - Tests MySQL database model
- **SslCertificateModelTest.php** - Tests SSL certificate model
- **WordPressInstallerTest.php** - Tests WordPress installation functionality

## Running Tests

### Run All Tests

```bash
php tests/run-tests.php
```

### Run Individual Test

```bash
php tests/CronServiceTest.php
php tests/LogViewerServiceTest.php
```

### Run Specific Test on Windows (WAMP)

```bash
C:\wamp64\bin\php\php8.3.28\php.exe C:\wamp64\www\server-manager\tests\CronServiceTest.php
```

## Test Coverage

### CronServiceTest

**What it tests:**
- ✓ Cron schedule validation (5-part cron expressions)
- ✓ Common schedule templates (every minute, hourly, daily, weekly, monthly)
- ✓ Next run time calculation
- ✓ Cron job creation and database storage
- ✓ Job enable/disable functionality
- ✓ Manual job execution with output logging
- ✓ Execution history tracking

**Expected Results:**
```
Running Cron Service Tests...
  Testing cron schedule validation...
    ✓ Schedule validation works correctly
  Testing common schedule templates...
    ✓ Common schedules are correct
  Testing next run time calculation...
    ✓ Next run calculation works
  Testing cron job creation...
    ✓ Job creation works correctly
  Testing job enable/disable...
    ✓ Job toggle works correctly
  Testing job execution...
    ✓ Job execution works correctly
  Cleaning up test data...
    ✓ Cleanup complete
✓ All Cron Service tests passed!
```

### LogViewerServiceTest

**What it tests:**
- ✓ Log file detection and filtering (.log, .txt, .err, .out extensions)
- ✓ Log file reading with size and line count
- ✓ Tail functionality (last N lines)
- ✓ Log search with case-sensitive/insensitive options
- ✓ Log file information retrieval
- ✓ Clear log file functionality
- ✓ Log category management

**Expected Results:**
```
Running Log Viewer Service Tests...
  Setting up test environment...
    ✓ Test environment ready
  Testing log file detection...
    ✓ Log file detection works correctly
  Testing log file reading...
    ✓ Log reading works correctly
  Testing log file tailing...
    ✓ Log tailing works correctly
  Testing log file search...
    ✓ Log search works correctly
  Testing log file info retrieval...
    ✓ Log info retrieval works correctly
  Testing log file clearing...
    ✓ Log clearing works correctly
  Testing log categories...
    ✓ Log categories are correct
  Cleaning up test data...
    ✓ Cleanup complete
✓ All Log Viewer Service tests passed!
```

## Test Configuration

Tests use the following database configuration (from `run-tests.php`):

```php
$_ENV['DB_HOST'] = 'localhost';
$_ENV['DB_DATABASE'] = 'chwezi_sm';
$_ENV['DB_USERNAME'] = 'peter';
$_ENV['DB_PASSWORD'] = 'Wirtz2025@';
```

**Note:** Make sure these match your local development environment.

## Test Data

Tests create temporary data:
- **CronServiceTest**: Creates test cron jobs (automatically cleaned up)
- **LogViewerServiceTest**: Creates test log files in `storage/logs/test/` (automatically cleaned up)

All tests include cleanup methods to ensure no test data persists after execution.

## Exit Codes

- **0**: All tests passed
- **1**: One or more tests failed

## Requirements

- PHP 8.2 or higher
- MySQL database connection
- Write permissions to `storage/logs/test/` directory

## Troubleshooting

### Database Connection Errors

Make sure your MySQL credentials in `run-tests.php` are correct and the `chwezi_sm` database exists.

### Permission Errors

Ensure the web server user (www-data on Ubuntu/Debian) has write access to the storage directory:

```bash
sudo chown -R www-data:www-data storage/
sudo chmod -R 775 storage/
```

### Windows-Specific Issues

On Windows development environments:
- Some tests skip Linux-specific functionality (e.g., system crontab operations)
- Log viewer tests work with limited log paths
- Use full PHP path: `C:\wamp64\bin\php\php8.3.28\php.exe`

## CI/CD Integration

To run tests in a CI/CD pipeline:

```bash
#!/bin/bash
cd /path/to/server-manager
php tests/run-tests.php
exit $?
```

## Contributing

When adding new features, please:
1. Create corresponding test files
2. Follow the existing test structure
3. Include setup and cleanup methods
4. Test both success and failure cases
5. Verify tests pass before committing

## Test Status

All tests currently passing ✓

- AppModelTest ✓
- CronServiceTest ✓
- DomainModelTest ✓
- LogViewerServiceTest ✓
- MysqlDatabaseModelTest ✓
- SslCertificateModelTest ✓
- WordPressInstallerTest ✓

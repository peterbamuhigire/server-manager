<?php

/**
 * Validation Helper Functions
 * Chwezi Server Manager
 */

/**
 * Validate required field
 * @param mixed $value
 * @return bool
 */
function validate_required($value): bool
{
    if (is_string($value)) {
        return trim($value) !== '';
    }
    return !empty($value);
}

/**
 * Validate email format
 * @param string $email
 * @return bool
 */
function validate_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate username format
 * @param string $username
 * @return bool
 */
function validate_username(string $username): bool
{
    // 3-50 characters, alphanumeric, underscore, hyphen
    return preg_match('/^[a-zA-Z0-9_-]{3,50}$/', $username) === 1;
}

/**
 * Validate password strength
 * @param string $password
 * @return array ['valid' => bool, 'errors' => array]
 */
function validate_password(string $password): array
{
    $errors = [];

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }

    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }

    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Validate minimum length
 * @param string $value
 * @param int $min
 * @return bool
 */
function validate_min_length(string $value, int $min): bool
{
    return strlen($value) >= $min;
}

/**
 * Validate maximum length
 * @param string $value
 * @param int $max
 * @return bool
 */
function validate_max_length(string $value, int $max): bool
{
    return strlen($value) <= $max;
}

/**
 * Validate URL format
 * @param string $url
 * @return bool
 */
function validate_url(string $url): bool
{
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Validate domain name
 * @param string $domain
 * @return bool
 */
function validate_domain(string $domain): bool
{
    return preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]$/i', $domain) === 1;
}

/**
 * Validate slug format
 * @param string $slug
 * @return bool
 */
function validate_slug(string $slug): bool
{
    return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) === 1;
}

/**
 * Validate integer
 * @param mixed $value
 * @return bool
 */
function validate_integer($value): bool
{
    return filter_var($value, FILTER_VALIDATE_INT) !== false;
}

/**
 * Validate boolean
 * @param mixed $value
 * @return bool
 */
function validate_boolean($value): bool
{
    return is_bool($value) || in_array($value, [0, 1, '0', '1', 'true', 'false'], true);
}

/**
 * Validate unique value in database
 * @param string $table
 * @param string $column
 * @param mixed $value
 * @param int|null $excludeId Exclude this ID from check (for updates)
 * @return bool
 */
function validate_unique(string $table, string $column, $value, ?int $excludeId = null): bool
{
    $db = db();

    if ($excludeId) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = ? AND id != ?");
        $stmt->execute([$value, $excludeId]);
    } else {
        $stmt = $db->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = ?");
        $stmt->execute([$value]);
    }

    return $stmt->fetchColumn() == 0;
}

/**
 * Validate exists in database
 * @param string $table
 * @param string $column
 * @param mixed $value
 * @return bool
 */
function validate_exists(string $table, string $column, $value): bool
{
    $db = db();
    $stmt = $db->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = ?");
    $stmt->execute([$value]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Sanitize string input
 * @param string $input
 * @return string
 */
function sanitize_string(string $input): string
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize email
 * @param string $email
 * @return string
 */
function sanitize_email(string $email): string
{
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

/**
 * Sanitize URL
 * @param string $url
 * @return string
 */
function sanitize_url(string $url): string
{
    return filter_var(trim($url), FILTER_SANITIZE_URL);
}

/**
 * Sanitize integer
 * @param mixed $value
 * @return int
 */
function sanitize_int($value): int
{
    return (int)filter_var($value, FILTER_SANITIZE_NUMBER_INT);
}

/**
 * Validate and sanitize input data
 * @param array $data Input data
 * @param array $rules Validation rules
 * @return array ['valid' => bool, 'errors' => array, 'data' => array]
 */
function validate_input(array $data, array $rules): array
{
    $errors = [];
    $sanitized = [];

    foreach ($rules as $field => $rule) {
        $value = $data[$field] ?? null;
        $fieldRules = is_string($rule) ? explode('|', $rule) : $rule;

        foreach ($fieldRules as $fieldRule) {
            // Parse rule and parameters
            $parts = explode(':', $fieldRule);
            $ruleName = $parts[0];
            $params = isset($parts[1]) ? explode(',', $parts[1]) : [];

            // Apply validation
            switch ($ruleName) {
                case 'required':
                    if (!validate_required($value)) {
                        $errors[$field][] = ucfirst($field) . ' is required';
                    }
                    break;

                case 'email':
                    if ($value && !validate_email($value)) {
                        $errors[$field][] = ucfirst($field) . ' must be a valid email';
                    }
                    break;

                case 'username':
                    if ($value && !validate_username($value)) {
                        $errors[$field][] = ucfirst($field) . ' must be 3-50 alphanumeric characters';
                    }
                    break;

                case 'min':
                    if ($value && !validate_min_length($value, (int)$params[0])) {
                        $errors[$field][] = ucfirst($field) . " must be at least {$params[0]} characters";
                    }
                    break;

                case 'max':
                    if ($value && !validate_max_length($value, (int)$params[0])) {
                        $errors[$field][] = ucfirst($field) . " must not exceed {$params[0]} characters";
                    }
                    break;

                case 'unique':
                    $table = $params[0];
                    $column = $params[1] ?? $field;
                    $excludeId = $data['id'] ?? null;
                    if ($value && !validate_unique($table, $column, $value, $excludeId)) {
                        $errors[$field][] = ucfirst($field) . ' already exists';
                    }
                    break;

                case 'exists':
                    $table = $params[0];
                    $column = $params[1] ?? 'id';
                    if ($value && !validate_exists($table, $column, $value)) {
                        $errors[$field][] = ucfirst($field) . ' does not exist';
                    }
                    break;

                case 'url':
                    if ($value && !validate_url($value)) {
                        $errors[$field][] = ucfirst($field) . ' must be a valid URL';
                    }
                    break;

                case 'domain':
                    if ($value && !validate_domain($value)) {
                        $errors[$field][] = ucfirst($field) . ' must be a valid domain';
                    }
                    break;
            }
        }

        // Sanitize value
        if ($value !== null) {
            if (is_string($value)) {
                $sanitized[$field] = sanitize_string($value);
            } else {
                $sanitized[$field] = $value;
            }
        }
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'data' => $sanitized
    ];
}

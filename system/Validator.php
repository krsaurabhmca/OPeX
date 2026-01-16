<?php
/**
 * Validator Class - Input Validation
 * 
 * Provides comprehensive input validation
 * 
 * @package OPeX
 * @author OfferPlant Technologies
 * @version 2.0
 */

class Validator
{
    private $errors = [];
    private $data = [];

    /**
     * Create new validator instance
     * 
     * @param array $data Data to validate
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Validate data against rules
     * 
     * @param array $rules Validation rules
     * @return bool True if valid
     */
    public function validate(array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $ruleSet) {
            $ruleList = is_string($ruleSet) ? explode('|', $ruleSet) : $ruleSet;
            $value = $this->data[$field] ?? null;

            foreach ($ruleList as $rule) {
                // Parse rule and parameters
                $params = [];
                if (strpos($rule, ':') !== false) {
                    list($rule, $paramString) = explode(':', $rule, 2);
                    $params = explode(',', $paramString);
                }

                // Call validation method
                $method = 'validate' . str_replace('_', '', ucwords($rule, '_'));
                if (method_exists($this, $method)) {
                    $this->$method($field, $value, $params);
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * Get validation errors
     * 
     * @return array Errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error message
     * 
     * @return string|null First error
     */
    public function getFirstError(): ?string
    {
        return !empty($this->errors) ? reset($this->errors)[0] : null;
    }

    /**
     * Add error message
     * 
     * @param string $field Field name
     * @param string $message Error message
     */
    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    // ========== Validation Rules ==========

    /**
     * Required field
     */
    protected function validateRequired(string $field, $value): void
    {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' is required');
        }
    }

    /**
     * Email validation
     */
    protected function validateEmail(string $field, $value): void
    {
        if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, ucfirst($field) . ' must be a valid email address');
        }
    }

    /**
     * Minimum length
     */
    protected function validateMin(string $field, $value, array $params): void
    {
        $min = (int)($params[0] ?? 0);
        if ($value && strlen($value) < $min) {
            $this->addError($field, ucfirst($field) . " must be at least $min characters");
        }
    }

    /**
     * Maximum length
     */
    protected function validateMax(string $field, $value, array $params): void
    {
        $max = (int)($params[0] ?? 0);
        if ($value && strlen($value) > $max) {
            $this->addError($field, ucfirst($field) . " must not exceed $max characters");
        }
    }

    /**
     * Numeric validation
     */
    protected function validateNumeric(string $field, $value): void
    {
        if ($value && !is_numeric($value)) {
            $this->addError($field, ucfirst($field) . ' must be a number');
        }
    }

    /**
     * Integer validation
     */
    protected function validateInteger(string $field, $value): void
    {
        if ($value && !filter_var($value, FILTER_VALIDATE_INT)) {
            $this->addError($field, ucfirst($field) . ' must be an integer');
        }
    }

    /**
     * Alpha (letters only)
     */
    protected function validateAlpha(string $field, $value): void
    {
        if ($value && !preg_match('/^[a-zA-Z]+$/', $value)) {
            $this->addError($field, ucfirst($field) . ' must contain only letters');
        }
    }

    /**
     * Alphanumeric
     */
    protected function validateAlphanumeric(string $field, $value): void
    {
        if ($value && !preg_match('/^[a-zA-Z0-9]+$/', $value)) {
            $this->addError($field, ucfirst($field) . ' must contain only letters and numbers');
        }
    }

    /**
     * URL validation
     */
    protected function validateUrl(string $field, $value): void
    {
        if ($value && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->addError($field, ucfirst($field) . ' must be a valid URL');
        }
    }

    /**
     * IP address validation
     */
    protected function validateIp(string $field, $value): void
    {
        if ($value && !filter_var($value, FILTER_VALIDATE_IP)) {
            $this->addError($field, ucfirst($field) . ' must be a valid IP address');
        }
    }

    /**
     * Date validation
     */
    protected function validateDate(string $field, $value): void
    {
        if ($value && !strtotime($value)) {
            $this->addError($field, ucfirst($field) . ' must be a valid date');
        }
    }

    /**
     * Date format validation
     */
    protected function validateDateFormat(string $field, $value, array $params): void
    {
        $format = $params[0] ?? 'Y-m-d';
        if ($value) {
            $date = DateTime::createFromFormat($format, $value);
            if (!$date || $date->format($format) !== $value) {
                $this->addError($field, ucfirst($field) . " must be in format $format");
            }
        }
    }

    /**
     * In array validation
     */
    protected function validateIn(string $field, $value, array $params): void
    {
        if ($value && !in_array($value, $params)) {
            $this->addError($field, ucfirst($field) . ' must be one of: ' . implode(', ', $params));
        }
    }

    /**
     * Not in array validation
     */
    protected function validateNotIn(string $field, $value, array $params): void
    {
        if ($value && in_array($value, $params)) {
            $this->addError($field, ucfirst($field) . ' cannot be one of: ' . implode(', ', $params));
        }
    }

    /**
     * Mobile number validation (Indian format)
     */
    protected function validateMobile(string $field, $value): void
    {
        if ($value && !preg_match('/^[6-9]\d{9}$/', $value)) {
            $this->addError($field, ucfirst($field) . ' must be a valid 10-digit mobile number');
        }
    }

    /**
     * Password strength validation
     */
    protected function validateStrongPassword(string $field, $value): void
    {
        if ($value) {
            if (strlen($value) < 8) {
                $this->addError($field, 'Password must be at least 8 characters');
                return;
            }
            if (!preg_match('/[A-Z]/', $value)) {
                $this->addError($field, 'Password must contain at least one uppercase letter');
                return;
            }
            if (!preg_match('/[a-z]/', $value)) {
                $this->addError($field, 'Password must contain at least one lowercase letter');
                return;
            }
            if (!preg_match('/[0-9]/', $value)) {
                $this->addError($field, 'Password must contain at least one number');
                return;
            }
            if (!preg_match('/[@$!%*?&#]/', $value)) {
                $this->addError($field, 'Password must contain at least one special character (@$!%*?&#)');
            }
        }
    }

    /**
     * Confirmation field match
     */
    protected function validateConfirmed(string $field, $value): void
    {
        $confirmField = $field . '_confirmation';
        $confirmValue = $this->data[$confirmField] ?? null;
        
        if ($value !== $confirmValue) {
            $this->addError($field, ucfirst($field) . ' confirmation does not match');
        }
    }

    /**
     * Unique value in database
     */
    protected function validateUnique(string $field, $value, array $params): void
    {
        if ($value && isset($params[0])) {
            $table = $params[0];
            $column = $params[1] ?? $field;
            $exceptId = $params[2] ?? null;
            
            $db = Database::getInstance();
            $sql = "SELECT COUNT(*) as count FROM `$table` WHERE `$column` = ?";
            $bindParams = [$value];
            
            if ($exceptId) {
                $sql .= " AND id != ?";
                $bindParams[] = $exceptId;
            }
            
            $result = $db->query($sql, $bindParams);
            
            if ($result && $result[0]['count'] > 0) {
                $this->addError($field, ucfirst($field) . ' already exists');
            }
        }
    }

    /**
     * Exists in database
     */
    protected function validateExists(string $field, $value, array $params): void
    {
        if ($value && isset($params[0])) {
            $table = $params[0];
            $column = $params[1] ?? $field;
            
            $db = Database::getInstance();
            $result = $db->query("SELECT COUNT(*) as count FROM `$table` WHERE `$column` = ?", [$value]);
            
            if ($result && $result[0]['count'] == 0) {
                $this->addError($field, ucfirst($field) . ' does not exist');
            }
        }
    }
}

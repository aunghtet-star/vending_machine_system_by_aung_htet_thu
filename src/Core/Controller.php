<?php
/**
 * Base Controller
 * 
 * Provides common functionality for all controllers.
 */

namespace App\Core;

class Controller
{
    protected array $data = [];

    /**
     * Render a view
     */
    protected function view(string $view, array $data = []): void
    {
        $data = array_merge($this->data, $data);
        extract($data);
        
        $viewFile = __DIR__ . '/../../views/' . str_replace('.', '/', $view) . '.php';
        
        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View '{$view}' not found");
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Check if layout is needed
        $layoutFile = __DIR__ . '/../../views/layouts/main.php';
        if (file_exists($layoutFile) && !($data['noLayout'] ?? false)) {
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    /**
     * Return JSON response
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Redirect to URL
     */
    protected function redirect(string $url, int $statusCode = 302): void
    {
        http_response_code($statusCode);
        header("Location: {$url}");
        exit;
    }

    /**
     * Set flash message
     */
    protected function flash(string $type, string $message): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['flash'][$type] = $message;
    }

    /**
     * Get flash message
     */
    protected function getFlash(string $type): ?string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $message = $_SESSION['flash'][$type] ?? null;
        unset($_SESSION['flash'][$type]);
        return $message;
    }

    /**
     * Check if request is AJAX
     */
    protected function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Get request input
     */
    protected function input(?string $key = null, mixed $default = null): mixed
    {
        $input = array_merge($_GET, $_POST);
        
        // Also check JSON body
        $jsonBody = json_decode(file_get_contents('php://input'), true);
        if (is_array($jsonBody)) {
            $input = array_merge($input, $jsonBody);
        }

        if ($key === null) {
            return $input;
        }

        return $input[$key] ?? $default;
    }

    /**
     * Validate input
     */
    protected function validate(array $data, array $rules): array
    {
        $errors = [];
        
        foreach ($rules as $field => $ruleString) {
            $fieldRules = explode('|', $ruleString);
            $value = $data[$field] ?? null;
            
            foreach ($fieldRules as $rule) {
                $ruleParts = explode(':', $rule);
                $ruleName = $ruleParts[0];
                $ruleParam = $ruleParts[1] ?? null;
                
                $error = $this->validateField($field, $value, $ruleName, $ruleParam);
                if ($error) {
                    $errors[$field][] = $error;
                }
            }
        }
        
        return $errors;
    }

    /**
     * Validate a single field
     */
    private function validateField(string $field, mixed $value, string $rule, ?string $param): ?string
    {
        $fieldName = ucfirst(str_replace('_', ' ', $field));
        
        return match($rule) {
            'required' => empty($value) && $value !== '0' ? "{$fieldName} is required" : null,
            'string' => !is_string($value) && $value !== null ? "{$fieldName} must be a string" : null,
            'numeric' => !is_numeric($value) && $value !== null ? "{$fieldName} must be a number" : null,
            'integer' => !filter_var($value, FILTER_VALIDATE_INT) && $value !== null ? "{$fieldName} must be an integer" : null,
            'email' => !filter_var($value, FILTER_VALIDATE_EMAIL) && $value !== null ? "{$fieldName} must be a valid email" : null,
            'min' => strlen($value) < (int)$param ? "{$fieldName} must be at least {$param} characters" : null,
            'max' => strlen($value) > (int)$param ? "{$fieldName} must not exceed {$param} characters" : null,
            'min_value' => (float)$value < (float)$param ? "{$fieldName} must be at least {$param}" : null,
            'max_value' => (float)$value > (float)$param ? "{$fieldName} must not exceed {$param}" : null,
            'positive' => (float)$value <= 0 ? "{$fieldName} must be positive" : null,
            'non_negative' => (float)$value < 0 ? "{$fieldName} must be non-negative" : null,
            default => null,
        };
    }
}

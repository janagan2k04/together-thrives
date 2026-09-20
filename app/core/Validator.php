<?php
namespace Core;

/**
 * Rules: required|email|min:8|max:255|numeric|integer|matches:field
 *        in:a,b,c|unique:table,column|date|phone_lk|nic_lk|url|accepted
 */
class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];
    private array $labels;

    public function __construct(array $data, array $rules, array $labels = [])
    {
        $this->data   = $data;
        $this->rules  = $rules;
        $this->labels = $labels;
    }

    public function passes(): bool
    {
        foreach ($this->rules as $field => $ruleString) {
            foreach (explode('|', $ruleString) as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                $this->apply($field, $name, $param);
            }
        }
        return empty($this->errors);
    }

    public function errors(): array { return $this->errors; }

    public function validated(): array
    {
        return array_intersect_key($this->data, $this->rules);
    }

    private function label(string $field): string
    {
        return $this->labels[$field] ?? ucwords(str_replace('_', ' ', $field));
    }

    private function fail(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    private function apply(string $field, string $rule, ?string $param): void
    {
        $value = $this->data[$field] ?? null;
        $label = $this->label($field);
        $empty = $value === null || $value === '';

        // only "required" and "accepted" care about empty values
        if ($empty && !in_array($rule, ['required', 'accepted'], true)) {
            return;
        }

        switch ($rule) {
            case 'required':
                if ($empty) $this->fail($field, "{$label} is required.");
                break;

            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL))
                    $this->fail($field, "Enter a valid email address.");
                break;

            case 'min':
                if (mb_strlen((string) $value) < (int) $param)
                    $this->fail($field, "{$label} must be at least {$param} characters.");
                break;

            case 'max':
                if (mb_strlen((string) $value) > (int) $param)
                    $this->fail($field, "{$label} must not exceed {$param} characters.");
                break;

            case 'numeric':
                if (!is_numeric($value)) $this->fail($field, "{$label} must be a number.");
                break;

            case 'integer':
                if (filter_var($value, FILTER_VALIDATE_INT) === false)
                    $this->fail($field, "{$label} must be a whole number.");
                break;

            case 'min_value':
                if ((float) $value < (float) $param)
                    $this->fail($field, "{$label} must be at least {$param}.");
                break;

            case 'matches':
                if ($value !== ($this->data[$param] ?? null))
                    $this->fail($field, "{$label} does not match.");
                break;

            case 'in':
                if (!in_array($value, explode(',', (string) $param), true))
                    $this->fail($field, "{$label} is not a valid option.");
                break;

            case 'unique':
                [$table, $column] = explode(',', (string) $param);
                $taken = (int) Database::value(
                    "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?", [$value]
                ) > 0;
                if ($taken) $this->fail($field, "This {$label} is already registered.");
                break;

            case 'date':
                if (!strtotime((string) $value)) $this->fail($field, "{$label} must be a valid date.");
                break;

            case 'after_today':
                if (strtotime((string) $value) < strtotime('today'))
                    $this->fail($field, "{$label} cannot be in the past.");
                break;

            case 'phone_lk':
                if (!preg_match('/^0\d{9}$/', preg_replace('/\s+/', '', (string) $value)))
                    $this->fail($field, "Enter a valid 10-digit phone number.");
                break;

            case 'nic_lk':
                if (!preg_match('/^([0-9]{9}[vVxX]|[0-9]{12})$/', (string) $value))
                    $this->fail($field, "Enter a valid NIC number.");
                break;

            case 'password':
                if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d).{8,}$/', (string) $value))
                    $this->fail($field, "Password must be at least 8 characters and include a letter and a number.");
                break;

            case 'accepted':
                if (empty($value)) $this->fail($field, "You must accept the {$label}.");
                break;
        }
    }
}

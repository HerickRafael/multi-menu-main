<?php
/**
 * Input Validation Framework
 * 
 * Framework robusto para validação de inputs com suporte a múltiplas regras,
 * validações encadeadas, sanitização automática e mensagens de erro customizáveis.
 * 
 * Uso:
 * ```php
 * $validator = new InputValidator();
 * $result = $validator->validate($_POST, [
 *     'name' => 'required|string|min:3|max:100',
 *     'email' => 'required|email',
 *     'age' => 'required|integer|between:18,120'
 * ]);
 * 
 * if (!$result->isValid()) {
 *     $errors = $result->getErrors();
 * }
 * ```
 * 
 * @package App\Middleware
 * @author MultiMenu Security Team
 * @version 1.0.0
 */

namespace App\Middleware;

class InputValidator
{
    /**
     * Regras de validação
     * @var array
     */
    private array $rules = [];
    
    /**
     * Mensagens de erro customizadas
     * @var array
     */
    private array $customMessages = [];
    
    /**
     * Dados a serem validados
     * @var array
     */
    private array $data = [];
    
    /**
     * Erros de validação
     * @var array
     */
    private array $errors = [];
    
    /**
     * Dados validados e sanitizados
     * @var array
     */
    private array $validated = [];
    
    /**
     * Mensagens de erro padrão
     * @var array
     */
    private const DEFAULT_MESSAGES = [
        'required' => 'O campo :field é obrigatório.',
        'string' => 'O campo :field deve ser uma string.',
        'integer' => 'O campo :field deve ser um número inteiro.',
        'float' => 'O campo :field deve ser um número decimal.',
        'boolean' => 'O campo :field deve ser verdadeiro ou falso.',
        'array' => 'O campo :field deve ser um array.',
        'email' => 'O campo :field deve ser um e-mail válido.',
        'url' => 'O campo :field deve ser uma URL válida.',
        'date' => 'O campo :field deve ser uma data válida.',
        'datetime' => 'O campo :field deve ser uma data/hora válida.',
        'min' => 'O campo :field deve ter no mínimo :param caracteres.',
        'max' => 'O campo :field deve ter no máximo :param caracteres.',
        'between' => 'O campo :field deve estar entre :param1 e :param2.',
        'regex' => 'O campo :field possui formato inválido.',
        'in' => 'O campo :field deve ser um dos valores: :param.',
        'notIn' => 'O campo :field não pode ser um dos valores: :param.',
        'alpha' => 'O campo :field deve conter apenas letras.',
        'alphaNum' => 'O campo :field deve conter apenas letras e números.',
        'numeric' => 'O campo :field deve ser numérico.',
        'confirmed' => 'A confirmação do campo :field não confere.',
        'unique' => 'O campo :field já está em uso.',
        'exists' => 'O campo :field não existe.',
        'file' => 'O campo :field deve ser um arquivo.',
        'image' => 'O campo :field deve ser uma imagem.',
        'mimes' => 'O campo :field deve ser do tipo: :param.',
        'size' => 'O campo :field deve ter :param KB.',
        'maxSize' => 'O campo :field não deve ser maior que :param KB.',
    ];
    
    /**
     * Valida dados com base nas regras fornecidas
     * 
     * @param array $data Dados a validar
     * @param array $rules Regras de validação
     * @param array $customMessages Mensagens customizadas
     * @return ValidationResult
     */
    public function validate(array $data, array $rules, array $customMessages = []): ValidationResult
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->customMessages = $customMessages;
        $this->errors = [];
        $this->validated = [];
        
        foreach ($rules as $field => $ruleString) {
            $this->validateField($field, $ruleString);
        }
        
        return new ValidationResult(
            empty($this->errors),
            $this->errors,
            $this->validated
        );
    }
    
    /**
     * Valida um campo específico
     * 
     * @param string $field Nome do campo
     * @param string $ruleString Regras separadas por pipe
     * @return void
     */
    private function validateField(string $field, string $ruleString): void
    {
        $rules = explode('|', $ruleString);
        $value = $this->data[$field] ?? null;
        $isRequired = in_array('required', $rules);
        $isNullable = in_array('nullable', $rules);
        
        // Se campo está vazio e não é obrigatório/nullable, pular validação
        if (empty($value) && !$isRequired && !$isNullable) {
            return;
        }
        
        foreach ($rules as $rule) {
            // Parse rule com parâmetros
            [$ruleName, $params] = $this->parseRule($rule);
            
            // Executar validação
            $method = 'validate' . ucfirst($ruleName);
            if (method_exists($this, $method)) {
                $valid = $this->$method($field, $value, $params);
                if (!$valid) {
                    $this->addError($field, $ruleName, $params);
                    // Se falhou validação required, parar outras validações deste campo
                    if ($ruleName === 'required') {
                        break;
                    }
                }
            }
        }
        
        // Se passou todas as validações, adicionar aos dados validados
        if (!isset($this->errors[$field])) {
            $this->validated[$field] = $this->sanitizeValue($value, $rules);
        }
    }
    
    /**
     * Faz parse de uma regra com parâmetros
     * 
     * @param string $rule Regra (ex: "min:3" ou "between:10,20")
     * @return array [nome da regra, array de parâmetros]
     */
    private function parseRule(string $rule): array
    {
        if (strpos($rule, ':') === false) {
            return [$rule, []];
        }
        
        [$name, $paramString] = explode(':', $rule, 2);
        $params = explode(',', $paramString);
        
        return [$name, $params];
    }
    
    /**
     * Adiciona erro de validação
     * 
     * @param string $field Nome do campo
     * @param string $rule Regra que falhou
     * @param array $params Parâmetros da regra
     * @return void
     */
    private function addError(string $field, string $rule, array $params = []): void
    {
        $message = $this->getErrorMessage($field, $rule, $params);
        
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        
        $this->errors[$field][] = $message;
        
        // Log do erro de validação
        if (function_exists('logger')) {
            logger("Validation failed for field: $field (rule: $rule)", 'WARNING');
        }
    }
    
    /**
     * Obtém mensagem de erro
     * 
     * @param string $field Nome do campo
     * @param string $rule Regra
     * @param array $params Parâmetros
     * @return string
     */
    private function getErrorMessage(string $field, string $rule, array $params): string
    {
        // Verificar mensagem customizada
        $customKey = "$field.$rule";
        if (isset($this->customMessages[$customKey])) {
            return $this->customMessages[$customKey];
        }
        
        // Usar mensagem padrão
        $message = self::DEFAULT_MESSAGES[$rule] ?? "O campo $field é inválido.";
        
        // Substituir placeholders
        $message = str_replace(':field', $field, $message);
        if (!empty($params)) {
            $message = str_replace(':param', implode(', ', $params), $message);
            $message = str_replace(':param1', $params[0] ?? '', $message);
            $message = str_replace(':param2', $params[1] ?? '', $message);
        }
        
        return $message;
    }
    
    /**
     * Sanitiza valor após validação
     * 
     * @param mixed $value Valor
     * @param array $rules Regras aplicadas
     * @return mixed
     */
    private function sanitizeValue($value, array $rules)
    {
        // Se é string, fazer trim
        if (is_string($value)) {
            $value = trim($value);
        }
        
        // Type casting baseado nas regras
        if (in_array('integer', $rules)) {
            return (int)$value;
        }
        if (in_array('float', $rules)) {
            return (float)$value;
        }
        if (in_array('boolean', $rules)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }
        
        return $value;
    }
    
    // ==================== VALIDATION RULES ====================
    
    /**
     * Valida campo obrigatório
     */
    private function validateRequired(string $field, $value, array $params): bool
    {
        if ($value === null || $value === '') {
            return false;
        }
        
        if (is_array($value) && empty($value)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Valida string
     */
    private function validateString(string $field, $value, array $params): bool
    {
        return is_string($value);
    }
    
    /**
     * Valida inteiro
     */
    private function validateInteger(string $field, $value, array $params): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }
    
    /**
     * Valida float
     */
    private function validateFloat(string $field, $value, array $params): bool
    {
        return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
    }
    
    /**
     * Valida boolean
     */
    private function validateBoolean(string $field, $value, array $params): bool
    {
        return in_array($value, [true, false, 0, 1, '0', '1', 'true', 'false'], true);
    }
    
    /**
     * Valida array
     */
    private function validateArray(string $field, $value, array $params): bool
    {
        return is_array($value);
    }
    
    /**
     * Valida email
     */
    private function validateEmail(string $field, $value, array $params): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Valida URL
     */
    private function validateUrl(string $field, $value, array $params): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Valida data (Y-m-d)
     */
    private function validateDate(string $field, $value, array $params): bool
    {
        $format = $params[0] ?? 'Y-m-d';
        $d = \DateTime::createFromFormat($format, $value);
        return $d && $d->format($format) === $value;
    }
    
    /**
     * Valida datetime
     */
    private function validateDatetime(string $field, $value, array $params): bool
    {
        $format = $params[0] ?? 'Y-m-d H:i:s';
        $d = \DateTime::createFromFormat($format, $value);
        return $d && $d->format($format) === $value;
    }
    
    /**
     * Valida tamanho mínimo
     */
    private function validateMin(string $field, $value, array $params): bool
    {
        $min = (int)($params[0] ?? 0);
        
        if (is_string($value)) {
            return mb_strlen($value) >= $min;
        }
        
        if (is_numeric($value)) {
            return $value >= $min;
        }
        
        if (is_array($value)) {
            return count($value) >= $min;
        }
        
        return false;
    }
    
    /**
     * Valida tamanho máximo
     */
    private function validateMax(string $field, $value, array $params): bool
    {
        $max = (int)($params[0] ?? 0);
        
        if (is_string($value)) {
            return mb_strlen($value) <= $max;
        }
        
        if (is_numeric($value)) {
            return $value <= $max;
        }
        
        if (is_array($value)) {
            return count($value) <= $max;
        }
        
        return false;
    }
    
    /**
     * Valida valor entre dois números
     */
    private function validateBetween(string $field, $value, array $params): bool
    {
        if (count($params) < 2) {
            return false;
        }
        
        $min = $params[0];
        $max = $params[1];
        
        if (is_numeric($value)) {
            return $value >= $min && $value <= $max;
        }
        
        if (is_string($value)) {
            $length = mb_strlen($value);
            return $length >= $min && $length <= $max;
        }
        
        return false;
    }
    
    /**
     * Valida regex
     */
    private function validateRegex(string $field, $value, array $params): bool
    {
        $pattern = $params[0] ?? '';
        return preg_match($pattern, $value) === 1;
    }
    
    /**
     * Valida se está na lista
     */
    private function validateIn(string $field, $value, array $params): bool
    {
        return in_array($value, $params);
    }
    
    /**
     * Valida se não está na lista
     */
    private function validateNotIn(string $field, $value, array $params): bool
    {
        return !in_array($value, $params);
    }
    
    /**
     * Valida apenas letras
     */
    private function validateAlpha(string $field, $value, array $params): bool
    {
        return preg_match('/^[a-zA-ZÀ-ÿ\s]+$/u', $value) === 1;
    }
    
    /**
     * Valida letras e números
     */
    private function validateAlphaNum(string $field, $value, array $params): bool
    {
        return preg_match('/^[a-zA-Z0-9À-ÿ\s]+$/u', $value) === 1;
    }
    
    /**
     * Valida numérico
     */
    private function validateNumeric(string $field, $value, array $params): bool
    {
        return is_numeric($value);
    }
    
    /**
     * Valida confirmação (campo_confirmation)
     */
    private function validateConfirmed(string $field, $value, array $params): bool
    {
        $confirmField = $field . '_confirmation';
        return isset($this->data[$confirmField]) && $this->data[$confirmField] === $value;
    }
    
    /**
     * Valida opcional (sempre passa)
     */
    private function validateOptional(string $field, $value, array $params): bool
    {
        return true;
    }
    
    /**
     * Valida nullable (sempre passa)
     */
    private function validateNullable(string $field, $value, array $params): bool
    {
        return true;
    }
}

/**
 * Classe de resultado de validação
 */
class ValidationResult
{
    private bool $valid;
    private array $errors;
    private array $validated;
    
    public function __construct(bool $valid, array $errors, array $validated)
    {
        $this->valid = $valid;
        $this->errors = $errors;
        $this->validated = $validated;
    }
    
    /**
     * Verifica se validação passou
     */
    public function isValid(): bool
    {
        return $this->valid;
    }
    
    /**
     * Verifica se validação falhou
     */
    public function fails(): bool
    {
        return !$this->valid;
    }
    
    /**
     * Retorna erros
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * Retorna erros de um campo específico
     */
    public function getFieldErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }
    
    /**
     * Retorna primeira mensagem de erro
     */
    public function getFirstError(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            return $fieldErrors[0] ?? null;
        }
        return null;
    }
    
    /**
     * Retorna dados validados
     */
    public function getValidated(): array
    {
        return $this->validated;
    }
    
    /**
     * Retorna valor validado de um campo
     */
    public function get(string $field)
    {
        return $this->validated[$field] ?? null;
    }
}

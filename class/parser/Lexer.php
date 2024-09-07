<?php

class Lexer {
    private $input;
    private $position = 0;
    private $tokens = [];
    private $inCodeBlock = false;
    private $inString = false;
    private $stringQuote = '';

    public function __construct($input) {
        if (!is_string($input)) {
            throw new Exception("Input must be a string");
        }
        $this->input = $input;
    }

    public function tokenize() {
        if (!is_string($this->input) || strlen($this->input) === 0) {
            throw new Exception("Invalid input");
        }
        while ($this->position < strlen($this->input)) {
            if ($this->position < strlen($this->input)) {
                $char = $this->input[$this->position];
            } else {
                throw new Exception("Invalid position: " . $this->position);
            }

            if ($char === '{') {
                if (!$this->inCodeBlock) {
                    $this->tokens[] = ['type' => 'TEXT', 'value' => $this->readText()];
                }
                $this->tokens[] = ['type' => 'LBRACE', 'value' => '{'];
                $this->inCodeBlock = true;
                $this->position++;
            } elseif ($char === '}') {
                $this->tokens[] = ['type' => 'RBRACE', 'value' => '}'];
                $this->inCodeBlock = false;
                $this->position++;
            } elseif ($this->inCodeBlock) {
                if ($char === '.') {
                    $this->tokens[] = ['type' => 'DOT', 'value' => '.'];
                    $this->position++;
                } elseif ($char === '?') {
                    $this->tokens[] = ['type' => 'TERNARY', 'value' => '?'];
                    $this->position++;
                } elseif ($char === ':') {
                    $this->tokens[] = ['type' => 'COLON', 'value' => ':'];
                    $this->position++;
                } elseif ($char === '>' || $char === '<' || $char === '=' || $char === '!') {
                    list($type, $operator) = $this->readOperator();
                    $this->tokens[] = ['type' => $type, 'value' => $operator];
                } elseif ($char === '|' || $char === '&') {
                    list($type, $operator) = $this->readLogicalOperator();
                    $this->tokens[] = ['type' => $type, 'value' => $operator];
                } elseif (ctype_digit($char)) {
                    $this->tokens[] = ['type' => 'NUMBER', 'value' => $this->readNumber()];
                } elseif ($char === "'" || $char === '"') {
                    $this->readString($char);
                } elseif (ctype_alpha($char) || $char === '_') {
                    $this->tokens[] = ['type' => 'IDENTIFIER', 'value' => $this->readIdentifier()];
                } elseif ($char === ' ' || $char === "\t" || $char === "\n" || $char === "\r") {
                    $this->position++;
                } elseif ($char === '(') {
                    $this->tokens[] = ['type' => 'LPAREN', 'value' => '('];
                    $this->position++;
                } elseif ($char === ')') {
                    $this->tokens[] = ['type' => 'RPAREN', 'value' => ')'];
                    $this->position++;
                } else {
                    throw new Exception("Unexpected character: " . $char);
                }
            } else {
                $this->tokens[] = ['type' => 'TEXT', 'value' => $this->readText()];
            }
        }
        if (!$this->inCodeBlock) {
            $this->tokens[] = ['type' => 'TEXT', 'value' => $this->readText()];
        }
        return $this->tokens;
    }

    private function readText() {
        $start = $this->position;
        while ($this->position < strlen($this->input) && $this->input[$this->position] !== '{' && $this->input[$this->position] !== '}') {
            $this->position++;
        }
        return substr($this->input, $start, $this->position - $start);
    }

    private function readNumber() {
        $start = $this->position;
        while ($this->position < strlen($this->input) && ctype_digit($this->input[$this->position])) {
            $this->position++;
        }
        return substr($this->input, $start, $this->position - $start);
    }

    private function readString($quote) {
        $start = $this->position;
        $this->position++;
        $this->inString = true;
        $this->stringQuote = $quote;
        $value = '';
        while ($this->position < strlen($this->input)) {
            $char = $this->input[$this->position];
            if ($char === $quote && $this->input[$this->position - 1] !== '\\') {
                $this->inString = false;
                $this->stringQuote = '';
                $this->position++;
                break;
            } elseif ($char === 'v' && $this->input[$this->position + 1] === '(') {
                // 遇到嵌套表达式，先添加当前解析的字符串部分
                if ($value !== '') {
                    $this->tokens[] = ['type' => 'STRING', 'value' => $value];
                    $value = '';
                }
                $this->tokens[] = ['type' => 'IDENTIFIER', 'value' => 'v'];
                $this->tokens[] = ['type' => 'LPAREN', 'value' => '('];
                $this->position += 2; // 跳过 'v('
                $this->readNestedExpression();
            } else {
                $value .= $char;
                $this->position++;
            }
        }
        if ($this->inString) {
            throw new Exception("Unterminated string");
        }
        if ($value !== '') {
            $this->tokens[] = ['type' => 'STRING', 'value' => $value];
        } else {
            // 处理空字符串的情况
            $this->tokens[] = ['type' => 'STRING', 'value' => ''];
        }
    }

    private function readNestedExpression() {
        while ($this->position < strlen($this->input)) {
            $char = $this->input[$this->position];
            if ($char === ')') {
                $this->tokens[] = ['type' => 'RPAREN', 'value' => ')'];
                $this->position++;
                break;
            } elseif ($char === '.') {
                $this->tokens[] = ['type' => 'DOT', 'value' => '.'];
                $this->position++;
            } elseif (ctype_alpha($char) || $char === '_') {
                $this->tokens[] = ['type' => 'IDENTIFIER', 'value' => $this->readIdentifier()];
            } else {
                throw new Exception("Unexpected character in nested expression: " . $char);
            }
        }
    }

    private function readIdentifier() {
        $start = $this->position;
        while ($this->position < strlen($this->input) && (ctype_alnum($this->input[$this->position]) || $this->input[$this->position] === '_')) {
            $this->position++;
        }
        return substr($this->input, $start, $this->position - $start);
    }

    private function readOperator() {
        $start = $this->position;
        while ($start < strlen($this->input) && in_array($this->input[$start], ['>', '<', '=', '!'])) {
            $start++;
        }
        $operator = substr($this->input, $this->position, $start - $this->position);
        switch ($operator) {
            case '>':
                $this->position++; // 移动到下一个字符
                $type = 'GT';
                break;
            case '<':
                $this->position++; // 移动到下一个字符
                $type = 'LT';
                break;
            case '==':
                $this->position += 2; // 移动到下一个字符
                $type = 'EQ';
                break;
            case '!=':
                $this->position += 2; // 移动到下一个字符
                $type = 'NEQ';
                break;
            default:
                throw new Exception("Unexpected operator: " . $operator);
        }
        return [$type, $operator];
    }

    private function readLogicalOperator() {
        $start = $this->position;
        while ($start < strlen($this->input) && in_array($this->input[$start], ['|', '&'])) {
            $start++;
        }
        $operator = substr($this->input, $this->position, $start - $this->position);
        switch ($operator) {
            case '||':
                $this->position += 2; // 移动到下一个字符
                $type = 'OR';
                break;
            case '&&':
                $this->position += 2; // 移动到下一个字符
                $type = 'AND';
                break;
            default:
                throw new Exception("Unexpected logical operator: " . $operator);
        }
        return [$type, $operator];
    }
}

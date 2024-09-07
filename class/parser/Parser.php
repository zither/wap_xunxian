<?php

require_once __DIR__. '/../lexical_analysis.php';


class Parser {
    private $tokens;
    private $position = 0;
    private $db;
    private $sid;
    private $oid;
    private $mid;
    private $jid;
    private $type;
    private $para;

    public function __construct($tokens, $db, $sid, $oid, $mid, $jid, $type, $para = null) {
        $this->tokens = $tokens;
        $this->db = $db;
        $this->sid = $sid;
        $this->oid = $oid;
        $this->mid = $mid;
        $this->jid = $jid;
        $this->type = $type;
        $this->para = $para;
    }

    private function match($type) {
        if ($this->position < count($this->tokens) && $this->tokens[$this->position]['type'] === $type) {
            return $this->tokens[$this->position++]['value'];
        }
        var_dump($this->tokens[$this->position]['type'], $type);
        throw new Exception("Unexpected token match: " . $this->tokens[$this->position]['type']);
    }

    private function parseCodeBlock() {
        $this->match('LBRACE');
        $expression = $this->parseExpression();
        $this->match('RBRACE');
        return $this->evaluateExpression($expression);
    }

    private function parseExpression() {
        $left = $this->parseTerm();
        
        while ($this->position < count($this->tokens) && in_array($this->tokens[$this->position]['type'], ['GT', 'LT', 'EQ', 'NEQ', 'OR', 'AND'])) {
            $operator = $this->match($this->tokens[$this->position]['type']);
            $right = $this->parseTerm();
            $left = [
                'type' => 'binary_expression',
                'operator' => $operator,
                'left' => $left,
                'right' => $right
            ];
        }
        
        if ($this->position < count($this->tokens) && $this->tokens[$this->position]['type'] === 'TERNARY') {
            $this->match('TERNARY');
            $trueValue = $this->parseExpression(); // 递归解析 true 分支
            $this->match('COLON');
            $falseValue = $this->parseExpression(); // 递归解析 false 分支
            return [
                'type' => 'ternary_expression',
                'condition' => $left,
                'true_value' => $trueValue,
                'false_value' => $falseValue
            ];
        }
        
        return $left;
    }

    private function parseStringWithNestedExpression($initialValue)
    {
        $result = $initialValue;

        while ($this->position < count($this->tokens)) {
            $token = $this->tokens[$this->position];

            if ($token['type'] === 'STRING') {
                $result .= $this->match('STRING');
            } elseif ($token['type'] === 'NESTED_START') {
                $nestedExpression = $this->parseNestedExpression();
                $result .= $nestedExpression;
            } else {
                break;
            }
        }

        return $result;
    }

    private function parseNestedExpression() {
        $this->match('NESTED_START');
        $expression = $this->parseExpression();
        $this->match('NESTED_END');
        
        // 处理嵌套表达式前后的 STRING token
        $result = '';
        // 处理嵌套表达式
        $result .= $this->evaluateExpression($expression);
        
        // 处理嵌套表达式后的 STRING token
        while ($this->position < count($this->tokens) && $this->tokens[$this->position]['type'] === 'STRING') {
            $result .= $this->match('STRING');
        }
        
        return $result;
    }

    private function parseTerm() {
        $token = $this->tokens[$this->position];
        if ($this->tokens[$this->position]['type'] === 'NUMBER') {
            return $this->match('NUMBER');
        } elseif ($this->tokens[$this->position]['type'] === 'STRING') {
            // 递归处理嵌套表达式及其前后可能存在的 STRING token
            $token['value'] = $this->parseStringWithNestedExpression('');
            return $token;
        } elseif ($this->tokens[$this->position]['type'] === 'IDENTIFIER') {
            $identifier = $this->match('IDENTIFIER');
            if ($this->tokens[$this->position]['type'] === 'DOT') {
                $property = $this->parsePropertyChain();
                return [
                    'type' => 'property_access',
                    'object' => $identifier,
                    'property' => $property
                ];
            }
            return $identifier;
        }
        throw new Exception("Unexpected token parseTerm: " . $this->tokens[$this->position]['type']);

    }

    private function parsePropertyChain() {
        $property = '';
        while ($this->tokens[$this->position]['type'] === 'DOT') {
            $this->match('DOT');
            $property .= '.' . $this->match('IDENTIFIER');
        }
        return ltrim($property, '.');
    }

    private function evaluateExpression($expression) {
        if (is_array($expression)) {
            switch ($expression['type']) {
                case 'STRING':
                    return $expression['value'];
                case 'binary_expression':
                    $left = $this->evaluateExpression($expression['left']);
                    $right = $this->evaluateExpression($expression['right']);
                    switch ($expression['operator']) {
                        case '>':
                            return ($left > $right) ? 1 : 0;
                        case '<':
                            return ($left < $right) ? 1 : 0;
                        case '==':
                            return ($left == $right) ? 1 : 0;
                        case '!=':
                            return ($left != $right) ? 1 : 0;
                        case '||':
                            return ($left || $right) ? 1 : 0;
                        case '&&':
                            return ($left && $right) ? 1 : 0;
                        default:
                            throw new Exception("Unknown operator: " . $expression['operator']);
                    }
                case 'ternary_expression':
                    $condition = $this->evaluateExpression($expression['condition']);
                    return $condition ? $this->evaluateExpression($expression['true_value']) : $this->evaluateExpression($expression['false_value']);
                case 'property_access':
                    return \lexical_analysis\process_attribute($expression['object'], $expression['property'], $this->sid, $this->oid, $this->mid, $this->jid, $this->type, $this->db, $this->para);
                default:
                    throw new Exception("Unknown expression type: " . $expression['type']);
            }
        }
        return $expression;
    }

    public function parse() {
        $result = '';
        while ($this->position < count($this->tokens)) {
            $token = $this->tokens[$this->position];
            if ($token['type'] === 'IDENTIFIER') {
                $result .= $token['value'];
                $this->position++;
            } elseif ($token['type'] === 'LBRACE') {
                $result .= $this->parseCodeBlock();
            } elseif ($token['type'] === 'TEXT') {
                $result .= $token['value'];
                $this->position++;
            } else {
                throw new Exception("Unexpected token: " . $token['type']);
            }
        }
        return $result;
    }
}
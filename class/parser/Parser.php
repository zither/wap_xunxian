<?php

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
        throw new Exception("Unexpected token match: " . $this->tokens[$this->position]['type']);
    }

    private function parseCodeBlock() {
        $this->match('LBRACE');
        $expression = $this->parseExpression();
        $this->match('RBRACE');
        return [
            'type' => 'code_block',
            'expression' => $expression
        ];
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

    private function parseStringWithNestedExpression($initialValue) {
        $result = [
            'type' => 'nested_expression',
            'parts' => []
        ];
    
        while ($this->position < count($this->tokens)) {
            $token = $this->tokens[$this->position];
    
            if ($token['type'] === 'STRING') {
                $result['parts'][] = ['type' => 'string', 'value' => $this->match('STRING')];
            } elseif ($token['type'] === 'IDENTIFIER' && $this->tokens[$this->position + 1]['type'] === 'LPAREN') {
                $result['parts'][] = $this->parseNestedExpression();
            } else {
                break;
            }
        }
    
        return $result;
    }

    private function parseNestedExpression() {
        $this->match('IDENTIFIER');
        $this->match('LPAREN');
        $expression = $this->parseExpression();
        $this->match('RPAREN');
        return $expression;
    }

private function parseFuncCallExpression() {
        $identifier = $this->match('IDENTIFIER');
        $this->match('LPAREN');
        $expression = $this->parseExpression();
        $this->match('RPAREN');
        return [
            'type' => 'func_call_expression',
            'identifier' => $identifier,
            'arguments' => $expression,
        ];
    }

    private function parseTerm() {
        if ($this->tokens[$this->position]['type'] === 'NUMBER') {
            return ['type' => 'number', 'value' => $this->match('NUMBER')];
        } elseif ($this->tokens[$this->position]['type'] === 'STRING') {
            // 递归处理嵌套表达式及其前后可能存在的 STRING token
            return $this->parseStringWithNestedExpression('');
        } elseif ($this->tokens[$this->position]['type'] === 'IDENTIFIER') {
            if ($this->tokens[$this->position + 1]['type'] === 'LPAREN') {
                $func = $this->parseFuncCallExpression();
                $property = $this->parsePropertyChain();
                    return [
                        'type' => 'property_access',
                        'object' => $func,
                        'property' => $property
                    ];
            } else {
                $identifier = $this->match('IDENTIFIER');
                if ($this->tokens[$this->position]['type'] === 'DOT') {
                    $property = $this->parsePropertyChain();
                    return [
                        'type' => 'property_access',
                        'object' => $identifier,
                        'property' => $property
                    ];
                }
                return ['type' => 'identifier', 'value' => $identifier];
            }
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
                case 'number':
                case 'string':
                case 'identifier':
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
                    $object = $expression['object'];
                    if (is_array($object) && $object['type'] === 'func_call_expression') {
                       return $this->evaluateFuncCall($expression);
                    }
                    return \lexical_analysis\process_attribute($object, $expression['property'], $this->sid, $this->oid, $this->mid, $this->jid, $this->type, $this->db, $this->para);
                case 'code_block':
                    return $this->evaluateExpression($expression['expression']);
                case 'nested_expression':
                    $result = '';
                    foreach ($expression['parts'] as $part) {
                        $result .= $this->evaluateExpression($part);
                    }
                    return $result;
                case 'func_call_expression':
                    return $this->evaluateFuncCall($expression);
                default:
                    throw new Exception("Unknown expression type: " . $expression['type']);
            }
        }
        return $expression;
    }

    private function evaluateFuncCall($expression) 
    {
        $object = $expression['object'];
        $uid = $this->evaluateExpression($object['arguments']);
        $new_expression = [
            'type' => 'property_access',
            'object' => 'u',
            'property' => $expression['property'],
        ];
        $newSid = get_sid($uid);
        $parser = new Parser(
            [],
            $this->db,
            $newSid,
            $this->oid,
            $this->mid,
            $this->jid,
            $this->type,
            $this->para
        );
        return $parser->evaluateExpression($new_expression);
    }

    public function parse() {
        $ast = [
            'type' => 'root',
            'children' => []
        ];
    
        while ($this->position < count($this->tokens)) {
            $token = $this->tokens[$this->position];
            if ($token['type'] === 'IDENTIFIER') {
                $ast['children'][] = ['type' => 'identifier', 'value' => $token['value']];
                $this->position++;
            } elseif ($token['type'] === 'LBRACE') {
                $ast['children'][] = $this->parseCodeBlock();
            } elseif ($token['type'] === 'TEXT') {
                $ast['children'][] = ['type' => 'text', 'value' => $token['value']];
                $this->position++;
            } else {
                throw new Exception("Unexpected token: " . $token['type']);
            }
        }
    
        return $ast;
    }

    public function evaluate($ast) {
        $result = '';
        foreach ($ast['children'] as $node) {
            if ($node['type'] === 'text') {
                $result .= $node['value'];
            } else {
                $result .= $this->evaluateExpression($node);
            }
        }
        return $result;
    }

    public function printAST($node, $indent = 0) {
        $indentStr = str_repeat('  ', $indent);
    
        if (is_array($node)) {
            echo $indentStr . "{\n";
            foreach ($node as $key => $value) {
                echo $indentStr . "  $key: ";
                if (is_array($value)) {
                    $this->printAST($value, $indent + 1);
                } else {
                    echo $indentStr . "  $value\n";
                }
            }
            echo $indentStr . "}\n";
        } else {
            echo $indentStr . $node . "\n";
        }
    }
}
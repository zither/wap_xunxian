<?php

require_once 'class/parser/Lexer.php';
require_once 'class/parser/Parser.php';


function process_string_remake($input, $sid, $oid = null, $mid = null, $jid = null, $type = null, $para = null)
{
    if (empty($input) || strpos($input, '{') === false) {
        return $input;  
    }
    $lexer = new Lexer($input);
    $tokens = $lexer->tokenize();
    //echo var_export(json_encode($tokens));exit;
    $db = DB::pdo();
    $parser = new Parser($tokens, $db, $sid, $oid, $mid, $jid, $type, $para);
    $ast = $parser->parse();
    return $parser->evaluate($ast);
}
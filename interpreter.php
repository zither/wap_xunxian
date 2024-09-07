<?php

$configs  = require 'configs.php';
include_once 'pdo.php';
include_once 'parser.php';
include_once 'class/lexical_analysis.php';
include_once 'class/parser/Lexer.php';
include_once 'class/parser/Parser.php';


function process_string($input, $sid, $oid = null, $mid = null, $jid = null, $type = null, $para = null)
{
    if (empty($input) || strpos($input, '{') === false) {
        return $input;  
    }
    $lexer = new Lexer($input);
    $tokens = $lexer->tokenize();
    foreach ($tokens as $token) {
        //echo $token['type'] .'  ' . $token['value']. '<br>';
    }
    //exit;
    //echo var_export(json_encode($tokens));exit;
    $db = DB::pdo();
    $parser = new Parser($tokens, $db, $sid, $oid, $mid, $jid, $type, $para);
    $ast = $parser->parse();
    return $parser->evaluate($ast);
}

try {
    $sid = 'ce92959759596c93595a1492dee1fbe5';
    $oid = 'npc';
    $mid = 46;
    //$oid = null;
    //$mid = null;

    $jid = null;

    //$inputString = "等级：v(o.lvl)级";
    // {o.kill == 1 ? "等级：v(o.lvl)级" : "一个善良的人"}
    //$inputString = '{o.kill == 0 ?"等级：v(o.lvl)级" : "一个善良的人"}';
    //$inputString = "{'ee' == ''}";
    //$inputString = "{1||0}";
    //$inputString = '{"我是v(u.name)大刷格"}';
    //$inputString = "{o.nick_name !=''||o.nick_name != 0}";
    //$inputString = '「{o.name}」<br /> 性别：{o.sex?o.sex:"未知"}<br /> {o.kill==1?"等级：v(o.lvl)级":"一个善良的人"}';
    //$inputString = '{o.equips_cmmt}';
    //$inputString = '{f(u.id).name}';
    $inputString = '{f(u.id).level > 100 ? "你好，v(o.name)是个大帅哥！": "不认识"}';
    $t =  process_string($inputString, $sid, $oid, $mid, $jid);
    echo "当前解析表达式为：" . $inputString, "<br>";
    echo "解析结果为：" . $t, "<br><br>";

    $inputString = '{f(u.id).name ? "你好，v(o.name)是个大帅哥！": "不认识"}';
    $t =  process_string($inputString, $sid, $oid, $mid, $jid);
    echo "当前解析表达式为：" . $inputString, "<br>";
    echo "解析结果为：" . $t, "<br><br>";

    $inputString = '「{o.name}」<br /> 性别：{o.sex?o.sex:"未知"}<br /> {o.kill==1?"等级：v(o.lvl)级":"一个善良的人"}';
    $t =  process_string($inputString, $sid, $oid, $mid, $jid);
    echo "当前解析表达式为：" . $inputString, "<br>";
    echo "解析结果为：" . $t;
} catch (Exception $e) {
    echo $e->getMessage();
}

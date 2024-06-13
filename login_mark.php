<?php
require_once 'class/player.php';
require_once 'class/encode.php';
include_once 'pdo.php';
include_once 'class/iniclass.php';
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: Sat, 1 Jan 2000 00:00:00 GMT");

$encode = new \encode\encode();//创建一个名为 $encode 的新对象，并使用命名空间 \encode\encode() 实例化该对象。


$Dcmd = $_SERVER['QUERY_STRING'];
$result = array();
parse_str($Dcmd, $result);
$token = $result['token'];
try{
if(isset($token)){
    $sql = "select uid,sid from game1 where token='$token'";
    $cxjg = $dblj->query($sql);
    $cxjg->bindColumn('sid',$sid);
    $cxjg->bindColumn('uid',$uid);
    $cxjg->fetch(PDO::FETCH_ASSOC);
    $wjid = $uid;
    include './ini/xuser_ini.php';
    $a10 = ($iniFile->getItem('验证信息', 'xcmid值'));
    $sql = "select username from userinfo where token='$token'";
    $cxjg = $dblj->query($sql);
    $cxjg->bindColumn('username',$username);
    $cxjg->fetch(PDO::FETCH_ASSOC);
    
    if ($sid==null){
        $cmd = "cmd=cj&token=$token";
    }else{
        $cmd = "cmd=login&ucmd=0&sid=$sid";
        $nowdate = date('Y-m-d H:i:s');
        $sql = "update game1 set endtime = '$nowdate',sfzx=1 WHERE sid=?";
        $stmt = $dblj->prepare($sql);
        $stmt->execute(array($sid));
    }
        $cmd = $encode->encode($cmd);
        $now_time = date('m-d H:i:s');
        $login_html =<<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
    <link rel="stylesheet" href="css/gamecss.css">
    <title>主页</title>
</head>
<body>
尊敬的{$username}，欢迎您回来!<br/><br/>

<image src="images/login.gif"><a href="game.php?cmd=$cmd">快速进入游戏</a><br/><br/>

<b>注意: 请存此页为书签,方便下次直接登陆!</b><br/>
----------------<br/>
客服电话: 暂无<br/>
官方Q①群: 暂无<br/><br/>
<a href="https://xunxian.txsj.ink">登录界面</a>|<a href="password_change.php?uid=$username&token=$token" >修改密码</a><br/>
$now_time<br/>
</body>
</html>
HTML;
}
}
catch (Exception $e){
        header("Location: https://xunxian.txsj.ink", true, 302);
        exit;
}
echo $login_html;
?>
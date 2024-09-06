<?php
$gm_game_globaleventdefine_attr_last = $encode->encode("cmd=game_event_attrchange&step_belong_id=$step_belong_id&step_id=$step_id&sid=$sid");
$attrchange_post = $encode->encode("cmd=game_event_attrchange&step_belong_id=$step_belong_id&step_id=$step_id&sid=$sid");
$attr_value_2 = htmlspecialchars($attr_value, ENT_QUOTES, 'UTF-8');

$gm_html = <<<HTML
<p>修改事件步骤的更改属性的值<br/>
</p>
<form action="?cmd=$attrchange_post" method="post">
<input type="hidden" name="step_belong_id" value="$step_belong_id">
<input type="hidden" name="step_id" value="$step_id">
<input name="old_key" type="hidden" value="{$attr_key}"/>
<input name="old_value" type="hidden" value="{$attr_value_2}"/>
属性名:<input name="key" type="text" value="$attr_key" maxlength="50"/><br/>
属性值表达式(以""包裹起来表示字符串表达式):<textarea name="value" maxlength="4096" rows="4" cols="40">{$attr_value}</textarea><br/>
<input name="submit" type="submit" title="确定" value="确定"/><input name="submit" type="hidden" title="确定" value="确定"/></form><br/>
<a href="?cmd=$gm_game_globaleventdefine_attr_last">返回上级</a><br/>
HTML;
echo $gm_html;

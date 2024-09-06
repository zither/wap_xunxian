<?php

if (!empty($_POST)) {
    $old_key = $_POST['old_key'];
    $old_value = $_POST['old_value'];
    $key = $_POST['key'];
    $value = $_POST['value'];
    $step_belong_id = $_POST['step_belong_id'];
    $step_id = $_POST['step_id'];
    $string_old = $old_key . "=" . $old_value;
    $string_new = $key . "=" . $value;
}
if (!empty($old_key) && !empty($key)) {
    // 准备 SQL 查询语句，使用占位符代替变量
    $query = "UPDATE system_event_evs SET s_attrs = REPLACE(s_attrs, :old_value, :new_value) WHERE belong = :belong AND id = :id";
    // 准备并执行预处理语句
    $stmt = $dblj->prepare($query);
    // 绑定参数值
    $stmt->bindParam(':old_value', $string_old);
    $stmt->bindParam(':new_value', $string_new);
    $stmt->bindParam(':belong', $step_belong_id);
    $stmt->bindParam(':id', $step_id);
    // 执行查询
    $stmt->execute();
} elseif (empty($old_key) && !empty($key)) {
    // 检查 s_attrs 字段是否为空
    $query = "SELECT s_attrs FROM system_event_evs where belong = '$step_belong_id' and id = '$step_id'";
    $stmt = $dblj->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (empty($result['s_attrs'])) {
        // s_attrs 字段为空，直接赋值为 $string_new
        $query = "UPDATE system_event_evs SET s_attrs = :new_value where belong = '$step_belong_id' and id = '$step_id'";
        $stmt = $dblj->prepare($query);
        $stmt->bindValue(':new_value', $string_new);
        $stmt->execute();
    } else {
        // s_attrs 字段不为空，在原有值后面加上逗号和 $string_new
        $query = "UPDATE system_event_evs SET s_attrs = CONCAT(s_attrs, ',', :new_value) where belong = '$step_belong_id' and id = '$step_id'";
        $stmt = $dblj->prepare($query);
        $stmt->bindValue(':new_value', $string_new);
        $stmt->execute();
    }
} elseif (!empty($old_key) && $key == 0) {
    // 准备 SQL 查询语句
    $query = "SELECT s_attrs FROM system_event_evs WHERE belong = '$step_belong_id' and id = '$step_id'";

    // 执行查询
    $result = $dblj->query($query);

    // 获取结果
    if ($result) {
        $row = $result->fetch(PDO::FETCH_ASSOC);
        $s_attrs = $row['s_attrs'];
    } else {
        echo "查询失败";
    }
    $elements = explode(",", $s_attrs);
    $index = array_search($string_old, $elements);
    if ($index !== false) {
        unset($elements[$index]);
    }
    $newString = implode(",", $elements);

    // 准备 SQL 更新语句，使用占位符代替变量
    $query = "UPDATE system_event_evs SET s_attrs = :newstring WHERE belong = '$step_belong_id' and id = '$step_id'";

    // 准备并执行预处理语句
    $stmt = $dblj->prepare($query);

    // 绑定参数值
    $stmt->bindParam(':newstring', $newString);

    // 执行更新
    if ($stmt->execute()) {
        echo "更新成功";
    } else {
        echo "更新失败";
    }
}


// 查询 system_event_evs 表获取 s_attrs 字段的值
$query = "SELECT s_attrs FROM system_event_evs where belong = '$step_belong_id' and id = '$step_id'";
$stmt = $dblj->query($query);
$rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
$gm_game_globaleventdefine_attr_last = $encode->encode("cmd=gm_game_globaleventdefine_steps&step_belong_id=$step_belong_id&step_id=$step_id&sid=$sid");
// 输出 HTML 锚点链接
foreach ($rows as $row) {
    $s_attrs = $row['s_attrs'];
    $attrs = explode(',', $s_attrs);
    foreach ($attrs as $index => $attr) {
        $attr = trim($attr);
        $equalPos = strpos($attr, '=');
        $key = substr($attr, 0, $equalPos);
        $value = substr($attr, $equalPos + 1);
        $key = urlencode($key);
        $value = urlencode($value);
        $index_attr = $encode->encode("cmd=game_event_attrset_2&step_belong_id=$step_belong_id&step_id=$step_id&attr_key=$key&attr_value=$value&sid=$sid");
        $index_attr_add = $encode->encode("cmd=game_event_attradd&step_belong_id=$step_belong_id&step_id=$step_id&post_type=0&sid=$sid");
        $attr_html .= <<<HTML
        <a href="?cmd=$index_attr">$attr</a><br/>
HTML;
    }
}

// 关闭数据库连接等清理操作

$gm_html = <<<HTML
<p>定义事件步骤的设置属性<br/>
$attr_html
<a href="?cmd=$index_attr_add">增加属性</a><br/>
<a href="?cmd=$gm_game_globaleventdefine_attr_last">返回上级</a><br/>
</p>
HTML;
echo $gm_html;

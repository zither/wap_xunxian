<?php

$configs  = require 'configs.php';
include 'pdo.php';
include 'class/lexical_analysis.php';
include 'class/parser/Lexer.php';
include 'class/parser/Parser.php';


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
    $inputString = '{"我是v(u.name)"}';
    //$inputString = "{o.nick_name !=''||o.nick_name != 0}";
    $inputString = '「{o.name}」<br /> 性别：{o.sex?o.sex:"未知"}<br /> {o.kill==1?"等级：v(o.lvl)级":"一个善良的人"}';
    //$inputString = '{o.equips_cmmt}';
    $t =  process_string($inputString, $sid, $oid, $mid, $jid);
    var_dump($t);
} catch (Exception $e) {
    echo $e->getMessage();
}



function process_attribute($attr1, $attr2, $sid, $oid, $mid, $jid, $type, $db, $para = null)
{
    $db = DB::pdo();
    switch ($attr1) {
        case 'u':
            if (strpos($attr2, "env.") === 0) {
                $attr3 = substr($attr2, 4); // 提取 "env." 后面的部分
                switch ($attr3) {
                    case 'user_count':
                        // 构建 SQL 查询语句
                        $sql = "SELECT COUNT(*) as count FROM game1 WHERE sfzx=1 and nowmid IN (SELECT nowmid FROM game1 WHERE sid = :sid) and uis_sailing = 0";
                        // 使用预处理语句
                        $stmt = $db->prepare($sql);
                        // 执行查询
                        $stmt->execute([':sid' => $sid]);
                        // 获取查询结果
                        $op = $stmt->fetchColumn();
                        break;
                    case 'npc_count':
                        $sql = "SELECT mnpc_now FROM system_map WHERE mid = (SELECT nowmid FROM game1 WHERE sid = :sid)";
                        // 使用预处理语句
                        $stmt = $db->prepare($sql);
                        // 执行查询
                        $stmt->execute([':sid' => $sid]);
                        // 处理结果
                        $totalNpcCount = 0;
                        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($rows as $row) {
                            $mnpc = $row["mnpc_now"];
                            $npcs = explode(",", $mnpc); // 拆分成每个npc项
                            foreach ($npcs as $npc) {
                                $npc_show_cond = urldecode(explode("|", $npc)[2]);
                                $show_cond = checkTriggerCondition($npc_show_cond, $dblj, $sid);
                                if (is_null($show_cond)) {
                                    $show_cond = true;
                                }
                                if ($show_cond) {
                                    list(, $npcCount) = explode("|", $npc);
                                    $totalNpcCount += (int)$npcCount; // 将每个npc的数量累加
                                }
                            }
                        }

                        $op = $totalNpcCount;
                        break;
                    case 'monster_count':
                        $sql = "SELECT COUNT(*) as count FROM system_npc_midguaiwu WHERE nsid = '' and nmid = (SELECT nowmid FROM game1 WHERE sid = :sid)";
                        // 使用预处理语句
                        $stmt = $db->prepare($sql);
                        // 执行查询
                        $stmt->execute([':sid' => $sid]);
                        // 获取查询结果
                        $op = $stmt->fetchColumn();
                        break;
                    case 'item_count':
                        $sql = "SELECT mitem_now FROM system_map WHERE mid = (SELECT nowmid FROM game1 WHERE sid = :sid)";
                        // 使用预处理语句
                        $stmt = $db->prepare($sql);
                        // 执行查询
                        $stmt->execute([':sid' => $sid]);
                        // 获取查询结果
                        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        // 处理结果
                        $totalItemCount = 0;
                        foreach ($rows as $row) {
                            $mitem = $row["mitem_now"];
                            $items = explode(",", $mitem); // 拆分成每个item项
                            foreach ($items as $item) {
                                list(, $itemCount) = explode("|", $item);
                                $totalItemCount += (int)$itemCount; // 将每个item的数量累加
                            }
                        }
                        $op = $totalItemCount;
                        break;
                    case 'justmid':
                        // 构建 SQL 查询语句
                        $sql = "SELECT justmid FROM game1 WHERE sid = :sid";
                        // 使用预处理语句
                        $stmt = $db->prepare($sql);
                        // 执行查询
                        $stmt->execute([':sid' => $sid]);
                        // 获取查询结果
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        $op = $row["justmid"];
                        break;
                    case 'nowmid':
                        // 构建 SQL 查询语句
                        $sql = "SELECT nowmid FROM game1 WHERE sid = :sid";
                        // 使用预处理语句
                        $stmt = $db->prepare($sql);
                        // 执行查询
                        $stmt->execute([':sid' => $sid]);
                        // 获取查询结果
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        $op = $row["nowmid"];
                        break;
                    case 'name':
                        // 构建 SQL 查询语句
                        $sql = "SELECT mname FROM system_map WHERE mid = (SELECT nowmid FROM game1 WHERE sid = :sid)";
                        // 使用预处理语句
                        $stmt = $db->prepare($sql);
                        // 执行查询
                        $stmt->execute([':sid' => $sid]);
                        // 获取查询结果
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        $op = $row["mname"];

                        $sql = "SELECT uis_sailing FROM game1 WHERE sid = :sid";
                        // 使用预处理语句
                        $stmt = $db->prepare($sql);
                        // 执行查询
                        $stmt->execute([':sid' => $sid]);
                        // 获取查询结果
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        $is_sailing = $row["uis_sailing"];
                        if ($is_sailing == 1) {
                            $op = "茫茫大海";
                        }
                        break;
                }
            } elseif (strpos($attr2, "input.") === 0) {
                $attr3 = substr($attr2, 6); // 提取 "input." 后面的部分
                switch ($attr3) {
                    case 'value':
                        // 构建 SQL 查询语句
                        $sql = "SELECT * FROM system_player_inputs WHERE sid = :sid";
                        // 使用预处理语句
                        $stmt = $db->prepare($sql);
                        // 执行查询
                        $stmt->execute([':sid' => $sid]);
                        // 获取查询结果
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        $op = $row["value"];
                        if ($op === null || $op == '') {
                            $op = "\"\""; // 或其他默认值
                        }
                        break;
                }
            } elseif (strpos($attr2, "refresh_time") === 0) {
                $sql = "SELECT mgtime, mrefresh_time FROM system_map WHERE mid = (SELECT nowmid FROM game1 WHERE sid = :sid)";
                // 使用预处理语句
                $stmt = $db->prepare($sql);
                $stmt->execute([':sid' => $sid]);
                // 获取查询结果
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $nowdate = date('Y-m-d H:i:s');
                $mid_time = $row["mgtime"];
                $mid_refresh_time = $row['mrefresh_time'];
                $op = $mid_refresh_time - floor((strtotime($nowdate) - strtotime($mid_time)) / 60); //获取刷新分钟剩余
            } elseif (strpos($attr2, "team_member_count") === 0) {
                $sql = "SELECT team_member FROM system_team_user WHERE team_member IN (SELECT uid FROM game1 WHERE sid = :sid)";
                // 使用预处理语句
                $stmt = $db->prepare($sql);
                // 执行查询
                $stmt->execute([':sid' => $sid]);
                // 获取查询结果
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $team_member = $row["team_member"];
                $op = @count(explode(',', $team_member));
            } elseif (strpos($attr2, "team_members") === 0) {
                $attr3 = substr($attr2, 13); // 提取 "team_members." 后面的部分
                $para = explode(".", $attr3);
                $order = intval($para[0]);
                $attr_player = "u" . $para[1];
                $sql = "
    SELECT g1.*
    FROM game1 g1
    JOIN system_team_user stu ON FIND_IN_SET(g1.uid, stu.team_member) > 0
    WHERE EXISTS (
        SELECT uid
        FROM game1
        WHERE sid = :sid
    )
    ORDER BY FIND_IN_SET(g1.uid, stu.team_member);
";
                // 使用预处理语句
                $stmt = $db->prepare($sql);
                // 执行查询
                $stmt->execute([':sid' => $sid]);
                // 获取查询结果
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                // 获取指定顺序的行
                $row = isset($rows[$order]) ? $rows[$order] : null;
                $op = $row ? ($row[$attr_player] ?? null) : null;
            } elseif (strpos($attr2, "tasks.") === 0) {
                $attr3 = substr($attr2, 6); // 提取 "tasks." 后面的部分
                if (strpos($attr3, 't') === 0) {
                    $attr3 = substr($attr3, 1); // 去掉开头的 "t"
                }
                $tid = $attr3;
                $sql = "SELECT ttype FROM system_task WHERE tid = :tid";
                $stmt = $db->prepare($sql);
                $stmt->execute([':tid' => $tid]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $ttype = $row["ttype"];

                $sql = "SELECT tstate FROM system_task_user WHERE tid = :tid AND sid = :sid";
                $stmt = $db->prepare($sql);
                $stmt->execute([':tid' => $tid, ':sid' => $sid]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $op = $row["tstate"];

                if (is_null($op)) {
                    $op = 0;
                } elseif ($ttype == 3) {
                    $op = 2;
                }
            } elseif (strpos($attr2, "ic.") === 0) {
                $attr3 = substr($attr2, 3); // 提取 "ic." 后面的部分
                if (strpos($attr3, 'i') === 0) {
                    $attr3 = substr($attr3, 1); // 去掉开头的 "i"
                }
                $iid = $attr3;
                $sql = "SELECT icount FROM system_item WHERE iid = :iid AND sid = :sid";
                // 使用预处理语句
                $stmt = $db->prepare($sql);
                // 执行查询
                $stmt->execute([':iid' => $iid, ':sid' => $sid]);
                // 获取查询结果
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $op = $row["icount"] ?? 0;
            } elseif (strpos($attr2, "jv.") === 0) {
                $attr3 = substr($attr2, 3); // 提取 "jv." 后面的部分
                if (strpos($attr3, 'j') === 0) {
                    $attr3 = substr($attr3, 1); // 去掉开头的 "j"
                }
                $jid = $attr3;
                $sql = "SELECT jlvl FROM system_skill_user WHERE jid = :jid AND jsid = :sid";
                // 使用预处理语句
                $stmt = $db->prepare($sql);
                // 执行查询
                $stmt->execute([':jid' => $jid, ':sid' => $sid]);
                // 获取查询结果
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $op = $row["jlvl"] ?? 0;
            } elseif (strpos($attr2, "enemys.") === 0) {
                $attr3 = substr($attr2, 7); // 提取 "enemys." 后面的部分
                $jid = $attr3;
                if ($attr3 == "count") {
                    $sql = "SELECT COUNT(*) as enemys_count FROM system_npc_midguaiwu WHERE nsid = :sid";
                    // 使用预处理语句
                    $stmt = $db->prepare($sql);
                    // 执行查询
                    $stmt->execute([':sid' => $sid]);
                    // 获取查询结果
                    $op = $stmt->fetchColumn();
                } else {
                    $para = explode(".", $attr3);
                    $order = (int)$para[0];
                    $attr_guai = "n" . $para[1];
                    $sql = "SELECT * FROM system_npc_midguaiwu WHERE nsid = :sid";
                    // 使用预处理语句
                    $stmt = $db->prepare($sql);
                    // 执行查询
                    $stmt->execute([':sid' => $sid]);
                    // 获取查询结果
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if (isset($rows[$order])) {
                        $op = $rows[$order][$attr_guai] ?? null;
                    } else {
                        $op = null;
                    }
                }
                if (is_null($op)) {
                    $op = 0;
                }
            } elseif (strpos($attr2, "alive_enemys.") === 0) {
                $attr3 = substr($attr2, 13); // 提取 "alive_enemys." 后面的部分
                $jid = $attr3;
                if ($attr3 == "count") {
                    $sql = "SELECT COUNT(*) as alive_enemys_count FROM system_npc_midguaiwu WHERE nhp > 0 AND nsid = :sid";
                    // 使用预处理语句
                    $stmt = $db->prepare($sql);
                    // 执行查询
                    $stmt->execute([':sid' => $sid]);
                    // 获取查询结果
                    $op = $stmt->fetchColumn();
                } else {
                    $para = explode(".", $attr3);
                    $order = (int)$para[0];
                    $attr_guai = "n" . $para[1];
                    $sql = "SELECT * FROM system_npc_midguaiwu WHERE nhp > 0 AND nsid = :sid";
                    // 使用预处理语句
                    $stmt = $db->prepare($sql);
                    // 执行查询
                    $stmt->execute([':sid' => $sid]);
                    // 获取查询结果
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if (isset($rows[$order])) {
                        $op = $rows[$order][$attr_guai] ?? null;
                    } else {
                        $op = null;
                    }
                }
                if (is_null($op)) {
                    $op = 0;
                }
            } elseif (strpos($attr2, "equips.") === 0) {
                $attr3 = substr($attr2, 7); // 提取 "equips." 后面的部分
                if (strpos($attr3, 'b.') === 0) {
                    $attr4 = substr($attr3, 2); // 提取 "b." 后面的部分
                    $bid = $attr4;
                    $sql = "SELECT eq_true_id FROM system_equip_user WHERE eq_type = 1 AND eqsid = :sid";
                    // 使用预处理语句
                    $stmt = $db->prepare($sql);
                    // 执行查询
                    $stmt->execute([':sid' => $sid]);
                    // 获取查询结果
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $op = $row["eq_true_id"] ?? null;
                    if (!$op) {
                        $op = 0;
                    } else {
                        if (strpos($attr4, 'embed.') === 0) {
                            //镶物属性相关
                            $attr5 = substr($attr4, 6); // 提取 "embed." 后面的部分
                            if (preg_match('/^(\d+\.)?(.*)/', $attr5, $matches)) {
                                $prefix = $matches[1]; // 匹配到的前缀部分（数字加点号)
                                $mosaic_pos = rtrim($prefix, ".");

                                $attr6 = $matches[2]; // 匹配到的剩余部分
                            }
                            $sql = "SELECT equip_mosaic FROM player_equip_mosaic WHERE equip_id = :op";
                            // 使用预处理语句
                            $stmt = $db->prepare($sql);
                            // 执行查询
                            $stmt->execute([':op' => $op]);

                            // 获取查询结果
                            $row = $stmt->fetch(PDO::FETCH_ASSOC);
                            $mosaic_list = $row['equip_mosaic'];
                            $mosaic_para = explode('|', $mosaic_list);
                            if (!isset($mosaic_para[$mosaic_pos])) {
                                $op = 0;
                            } else {
                                $mosaic_id = $mosaic_para[$mosaic_pos];
                                $xid = "i" . $attr6;
                                $sql = "SELECT * FROM system_item_module WHERE iid = (SELECT iid FROM system_item WHERE item_true_id = :mosaic_id)";
                                // 使用预处理语句
                                $stmt = $db->prepare($sql);
                                // 执行查询
                                $stmt->execute([':mosaic_id' => $mosaic_id]);

                                // 获取查询结果
                                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                if ($row === null || $row == '') {
                                    $op = 0; // 或其他默认值
                                } else {
                                    $op = nl2br($row[$xid]);
                                }
                            }
                            //镶物属性相关
                        } else {
                            $bid = "i" . $bid;
                            $sql = "SELECT * FROM system_item_module WHERE iid = (SELECT iid FROM system_item WHERE item_true_id = :op)";
                            // 使用预处理语句
                            $stmt = $db->prepare($sql);
                            // 执行查询
                            $stmt->execute([':op' => $op]);
                            // 获取查询结果
                            $row = $stmt->fetch(PDO::FETCH_ASSOC);
                            if ($attr4 == "count") {
                                $op = $op ? 1 : 0;
                            } elseif ($attr4 == "embed_count") {
                                $sql = "SELECT equip_mosaic FROM player_equip_mosaic WHERE equip_id = :op";
                                // 使用预处理语句
                                $stmt = $db->prepare($sql);
                                // 执行查询
                                $stmt->execute([':op' => $op]);

                                // 获取查询结果
                                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                if ($row) {
                                    $op = count(explode('|', $row['equip_mosaic']));
                                } else {
                                    $op = 0;
                                }
                            } else {
                                if ($row === null || $row == '') {
                                    $op = 0; // 或其他默认值
                                } else {
                                    $op = nl2br($row[$bid]);
                                }
                            }
                        }
                    }
                    $op = process_string($op, $sid, $oid, $mid, $jid, $type, $para);
                } elseif (preg_match('/^(\d+\.)?(.*)/', $attr3, $matches)) {
                    $prefix = $matches[1]; // 匹配到的前缀部分（数字加点号)
                    $equiped_pos = rtrim($prefix, ".");
                    $attr4 = $matches[2]; // 匹配到的剩余部分
                    // SQL 查询语句
                    $sql = "SELECT id FROM system_equip_def WHERE type = 2 ORDER BY id";

                    // 执行查询并检查是否有结果
                    $stmt = $db->query($sql);
                    $idArray = $stmt->fetchAll(PDO::FETCH_COLUMN);

                    $equiped_pos = $idArray[$equiped_pos];

                    $fid = $attr4;
                    $sql = "SELECT eq_true_id FROM system_equip_user WHERE eq_type = 2 AND equiped_pos_id = :equiped_pos AND eqsid = :sid";

                    // 使用预处理语句
                    $stmt = $db->prepare($sql);
                    // 执行查询
                    $stmt->execute([':equiped_pos' => $equiped_pos, ':sid' => $sid]);

                    // 获取查询结果
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $op = $row["eq_true_id"] ?? 0;

                    if ($op) {
                        if (strpos($attr4, 'embed.') === 0) {
                            $attr5 = substr($attr4, 6); // 提取 "embed." 后面的部分
                            if (preg_match('/^(\d+\.)?(.*)/', $attr5, $matches)) {
                                $prefix = $matches[1]; // 匹配到的前缀部分（数字加点号)
                                $mosaic_pos = rtrim($prefix, ".");

                                $attr6 = $matches[2]; // 匹配到的剩余部分
                            }
                            $sql = "SELECT equip_mosaic FROM player_equip_mosaic WHERE equip_id = :op";
                            // 使用预处理语句
                            $stmt = $db->prepare($sql);
                            // 执行查询
                            $stmt->execute([':op' => $op]);

                            // 获取查询结果
                            $row = $stmt->fetch(PDO::FETCH_ASSOC);
                            $mosaic_list = $row['equip_mosaic'] ?? '';
                            $mosaic_para = explode('|', $mosaic_list);
                            if (!isset($mosaic_para[$mosaic_pos])) {
                                $op = 0;
                            } else {
                                $mosaic_id = $mosaic_para[$mosaic_pos];
                                $xid = "i" . $attr6;
                                $sql = "SELECT * FROM system_item_module WHERE iid = (SELECT iid FROM system_item WHERE item_true_id = :mosaic_id)";
                                // 使用预处理语句
                                $stmt = $db->prepare($sql);
                                // 执行查询
                                $stmt->execute([':mosaic_id' => $mosaic_id]);

                                // 获取查询结果
                                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                if ($row === null || $row == '') {
                                    $op = 0; // 或其他默认值
                                } else {
                                    $op = nl2br($row[$xid]);
                                }
                            }
                            //镶物属性相关
                        } else {
                            $fid = "i" . $fid;
                            $sql = "SELECT * FROM system_item_module WHERE iid = (SELECT iid FROM system_item WHERE item_true_id = :op)";
                            // 使用预处理语句
                            $stmt = $db->prepare($sql);
                            // 执行查询
                            $stmt->execute([':op' => $op]);

                            // 获取查询结果
                            $row = $stmt->fetch(PDO::FETCH_ASSOC);
                            if ($attr4 == "count") {
                                $op = $op ? 1 : 0;
                            } elseif ($attr4 == "embed_count") {
                                $sql = "SELECT equip_mosaic FROM player_equip_mosaic WHERE equip_id = :op";
                                // 使用预处理语句
                                $stmt = $db->prepare($sql);
                                // 执行查询
                                $stmt->execute([':op' => $op]);

                                // 获取查询结果
                                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                if ($row) {
                                    $op = count(explode('|', $row['equip_mosaic']));
                                } else {
                                    $op = 0;
                                }
                            } else {
                                if ($row === null || $row == '') {
                                    $op = 0; // 或其他默认值
                                } else {
                                    $op = nl2br($row[$fid]);
                                }
                            }
                        }
                    }
                    $op = process_string($op, $sid, $oid, $mid, $jid, $type, $para);
                }
            } elseif (strpos($attr2, "callout_adopt.") === 0) {
                $attr3 = substr($attr2, 14); // 提取 "callout_adopt." 后面的部分
                if (strpos($attr3, 'count') === 0) {
                    $sql = "SELECT COUNT(*) as total_callout FROM system_pet_player WHERE pstate = 1 AND psid = :psid";
                    // 使用预处理语句
                    $stmt = $db->prepare($sql);
                    // 绑定参数并执行查询
                    $stmt->execute([':psid' => $sid]);

                    // 获取查询结果
                    $op = $stmt->fetchColumn();
                    if (!$op) {
                        $op = 0;
                    }
                    $op = process_string($op, $sid, $oid, $mid, $jid, $type, $para);
                } elseif (preg_match('/^(\d+\.)?(.*)/', $attr3, $matches)) {
                    $prefix = $matches[1]; // 匹配到的前缀部分（数字加点号)
                    $pet_pos = rtrim($prefix, ".");
                    $attr4 = $matches[2]; // 匹配到的剩余部分
                    // SQL 查询语句
                    $sql = "SELECT pid FROM system_pet_player WHERE psid = :sid ORDER BY pid";

                    // 使用预处理语句
                    $stmt = $db->prepare($sql);
                    // 执行查询
                    $stmt->execute([':sid' => $sid]);

                    // 获取查询结果
                    $idArray = $stmt->fetchAll(PDO::FETCH_COLUMN);

                    $pet_pos = $idArray[$pet_pos] ?? null;

                    $fid = $attr4;

                    if (strpos($attr4, 'cut_hp') === 0) {
                        $attr5 = substr($attr4, 6); // 提取 "embed." 后面的部分
                        if (preg_match('/^(\d+\.)?(.*)/', $attr5, $matches)) {
                            $prefix = $matches[1]; // 匹配到的前缀部分（数字加点号)
                            $mosaic_pos = rtrim($prefix, ".");

                            $attr6 = $matches[2]; // 匹配到的剩余部分
                        }
                        $sql = "SELECT equip_mosaic FROM player_equip_mosaic WHERE equip_id = :op";
                        // 使用预处理语句
                        $stmt = $db->prepare($sql);
                        // 执行查询
                        $stmt->execute([':op' => $op]);

                        // 获取查询结果
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        $mosaic_list = $row['equip_mosaic'] ?? '';
                        $mosaic_para = explode('|', $mosaic_list);
                        if (!isset($mosaic_para[$mosaic_pos])) {
                            $op = 0;
                        } else {
                            $mosaic_id = $mosaic_para[$mosaic_pos];
                            $xid = "i" . $attr6;
                            $sql = "SELECT * FROM system_item_module WHERE iid = (SELECT iid FROM system_item WHERE item_true_id = :mosaic_id)";
                            // 使用预处理语句
                            $stmt = $db->prepare($sql);
                            // 执行查询
                            $stmt->execute([':mosaic_id' => $mosaic_id]);

                            // 获取查询结果
                            $row = $stmt->fetch(PDO::FETCH_ASSOC);
                            if ($row === null || $row == '') {
                                $op = 0; // 或其他默认值
                            } else {
                                $op = nl2br($row[$xid]);
                            }
                        }
                        //镶物属性相关
                    } else {
                        $pid = "p" . $fid;
                        $sql = "SELECT * FROM system_pet_player WHERE pid = :pet_pos";
                        // 使用预处理语句
                        $stmt = $db->prepare($sql);
                        // 执行查询
                        $stmt->execute([':pet_pos' => $pet_pos]);

                        // 获取查询结果
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($row === null || $row == '') {
                            $op = 0; // 或其他默认值
                        } else {
                            $op = nl2br($row[$pid]);
                        }
                    }

                    $op = process_string($op, $sid, $oid, $mid, $jid, $type, $para);
                }
            } else {
                $attr3 = $attr1 . $attr2;
                $attr3 = str_replace('.', '', $attr3);
                $sql = "SHOW COLUMNS FROM game1 LIKE :attr3";
                $stmt = $db->prepare($sql);
                $stmt->execute([':attr3' => $attr3]);
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($result) > 0) {
                    $sql = "SELECT * FROM game1 WHERE sid = :sid";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([':sid' => $sid]);
                } else {
                    $sql = "SELECT * FROM system_addition_attr WHERE sid = :sid AND name = :attr3";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([':sid' => $sid, ':attr3' => $attr3]);
                    $attr_type = 1;
                }

                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($row === false || $row === null) {
                    $op = 0; // 或其他默认值
                } else {
                    if (!isset($attr_type) || $attr_type != 1) {
                        $op = nl2br($row[$attr3]);
                    } else {
                        $op = nl2br($row['value']);
                    }
                }

                $op = process_string($op, $sid, $oid, $mid, $jid, $type, $para);
                // 替换字符串中的变量
            }
            break;
        case 'ut':
            switch ($attr2) {
                case 'is_computer':
                    $userAgent = $_SERVER['HTTP_USER_AGENT'];
                    if (strpos($userAgent, 'Mobile') !== false) {
                        // 用户正在使用移动设备（手机或平板）
                        $op = 0;
                    } else {
                        // 用户正在使用桌面设备（电脑）
                        $op = 1;
                    }
                    break;
                case 'cut_hp':
                    // 构建 SQL 查询语句
                    $sql = "SELECT SUM(cut_hp) AS total_cut_hp FROM game2 WHERE sid = :sid AND gid != ''";
                    $sql_2 = "SELECT SUM(cut_hp) AS total_cut_hp FROM game2 WHERE sid = :sid AND gid = ''";
                    // 使用预处理语句
                    $stmt = $db->prepare($sql);
                    $stmt->execute([':sid' => $sid]);
                    // 获取查询结果
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);

                    // 使用预处理语句
                    $stmt_2 = $db->prepare($sql_2);
                    $stmt_2->execute([':sid' => $sid]);
                    // 获取查询结果
                    $row_2 = $stmt_2->fetch(PDO::FETCH_ASSOC);

                    // 获取总和并处理结果
                    $total_cut_hp = $row["total_cut_hp"];
                    $total_cut_hp_2 = $row_2["total_cut_hp"];
                    $op = ($total_cut_hp <= 0 ? "+" : "-") . abs($total_cut_hp);
                    $op_2 = ($total_cut_hp_2 <= 0 ? "+" : "-") . abs($total_cut_hp_2);

                    // 合并字符串
                    if ($total_cut_hp_2 != 0) {
                        $op = $op . $op_2;
                    } else {
                        $op = $op;
                    }

                    if ($op === null || $op == '') {
                        $op = "\"\""; // 或其他默认值
                    }

                    $op = process_string($op, $sid, $oid, $mid, $jid, $type, $para);
                    break;
                case 'busy':
                    $sql = "SELECT attr_value FROM player_temp_attr WHERE obj_id = :sid AND obj_type = 1 AND attr_name = 'busy'";
                    // 使用预处理语句
                    $stmt = $db->prepare($sql);
                    $stmt->execute([':sid' => $sid]);
                    // 获取查询结果
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($row) {
                        $op = $row["attr_value"];
                    }
                    if ($op === null || $op == '') {
                        $op = "0"; // 或其他默认值
                    }
                    $op = process_string($op, $sid, $oid, $mid, $jid, $type, $para);
                    break;
                case 'fight_umsg':
                    $sql = "SELECT fight_umsg FROM game2 WHERE sid = :sid";
                    // 使用预处理语句
                    $stmt = $db->prepare($sql);
                    $stmt->execute([':sid' => $sid]);
                    // 初始化 $op
                    $op = '';
                    // 获取查询结果
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $op .= $row["fight_umsg"];
                    }
                    if ($op === '' || $op === null) {
                        $op = "0"; // 或其他默认值
                    } else {
                        $op = nl2br($op);
                    }
                    $op = process_string($op, $sid, $oid, $mid, $jid, $type, $para);
                    break;
                case 'fight_omsg':
                    $sql = "SELECT fight_omsg FROM game2 WHERE sid = :sid";
                    // 使用预处理语句
                    $stmt = $db->prepare($sql);
                    $stmt->execute([':sid' => $sid]);
                    // 初始化 $op
                    $op = '';
                    // 获取查询结果
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $op .= $row["fight_omsg"];
                    }
                    if ($op === '' || $op === null) {
                        $op = "0"; // 或其他默认值
                    } else {
                        $op = nl2br($op);
                    }
                    $op = process_string($op, $sid, $oid, $mid, $jid, $type, $para);
                    break;
            }
            break;
        case 'ot':
            switch ($attr2) {
                case 'is_computer':
                    $sql = "SELECT sfzx FROM game1 WHERE sid = :sid";
                    // 使用预处理语句
                    $stmt = $db->prepare($sql);
                    $stmt->execute([':sid' => $mid]);

                    // 获取查询结果
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $sfzx = $row["sfzx"];
                    if ($sfzx == 1) {
                        $sql = "SELECT device_agent FROM game4 WHERE sid = :sid";
                        // 使用预处理语句
                        $stmt = $db->prepare($sql);
                        $stmt->execute([':sid' => $mid]);

                        // 获取查询结果
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        $userAgent = $row["device_agent"];
                        if (strpos($userAgent, 'Mobile') !== false) {
                            // 用户正在使用移动设备（手机或平板）
                            $op = 0;
                        } else {
                            // 用户正在使用桌面设备（电脑）
                            $op = 1;
                        }
                    } else {
                        // 用户离线
                        $op = 2;
                    }
                    break;
                case 'cut_hp':
                    // 构建 SQL 查询语句
                    $sql = "SELECT * FROM game2 WHERE sid = :sid";

                    // 使用预处理语句
                    $stmt = $db->prepare($sql);
                    $stmt->execute([':sid' => $sid]);

                    // 获取查询结果
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $op = $row["hurt_hp"];
                    if ($op === null || $op == '') {
                        $op = "\"\""; // 或其他默认值
                    }
                    break;
            }
            break;
        case 'o':
            switch ($oid) {
                case 'scene':
                    $attr3 = 'm' . $attr2;
                    $sql = "SELECT * FROM system_map WHERE mid = :mid";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([':mid' => $mid]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($row === false) {
                        $op = 0; // 或其他默认值
                    } else {
                        $op = nl2br($row[$attr3] ?? '');
                        if ($op === '') {
                            $op = "\"\""; // 或其他默认值
                        }
                    }
                    $op = process_string($op, $sid, $oid, $mid, $jid, $type, $para);
                    // 替换字符串中的变量
                    //$input = str_replace("{{$match}}", $op, $input);
                    break;
                case 'pet':
                    if ($attr2 == "skills_cmmt") {
                        $sql = "SELECT * FROM system_skill_user WHERE jpid = :jpid";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([':jpid' => $mid]);
                        $row_result = "";

                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $skill_id = $row['jid'];
                            $skill_lvl = $row['jlvl'];
                            $sql2 = "SELECT * FROM system_skill WHERE jid = :jid";
                            $stmt2 = $db->prepare($sql2);
                            $stmt2->execute([':jid' => $skill_id]);
                            $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
                            $row_result .= "，" . $row2['jname'] . "_" . "{$skill_lvl}";
                        }
                        $op = ltrim($row_result, "，");
                    } else {
                        $attr3 = 'p' . $attr2;
                        $sql = "SELECT * FROM system_pet_player WHERE pid = :pid";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([':pid' => $mid]);
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($row === false) {
                            $op = 0; // 或其他默认值
                        } else {
                            $op = nl2br($row[$attr3] ?? '');
                        }
                        if ($op === null || $op === '') {
                            $op = "\"\""; // 或其他默认值
                        }
                    }
                    $op = process_string($op, $sid, $oid, $mid, $jid, $type, $para);
                    // 替换字符串中的变量
                    //$input = str_replace("{{$match}}", $op, $input);
                    break;
                case 'npc':
                    $attr3 = 'n' . $attr2;
                    if (is_numeric($mid)) {
                        $sql = "SELECT * FROM system_npc WHERE nid = :nid";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([':nid' => $mid]);
                    } else {
                        $data_mid = explode("|", $mid);
                        $mid2 = $data_mid[1];
                        $sql = "SELECT * FROM system_npc_midguaiwu WHERE ngid = :ngid";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([':ngid' => $mid2]);
                    }
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (!$row) {
                        die('查询失败: ' . $db->errorInfo()[2]);
                    }
                    if ($attr2 == "skills_cmmt") {
                        $skills_cmmt = $row['nskills'];
                        $skill_cmmt = explode(',', $skills_cmmt);
                        $row_result = "";
                        if ($skills_cmmt) {
                            foreach ($skill_cmmt as $skill_cmmt_detail) {
                                $skill_para = explode('|', $skill_cmmt_detail);
                                $skill_id = $skill_para[0];
                                $skill_lvl = $skill_para[1];
                                $sql = "SELECT jname FROM system_skill WHERE jid = :jid";
                                $stmt = $db->prepare($sql);
                                $stmt->execute([':jid' => $skill_id]);
                                $skill_row = $stmt->fetch(PDO::FETCH_ASSOC);
                                $row_result .= "，" . $skill_row['jname'] . "_" . "{$skill_lvl}";
                            }
                            $row_result = ltrim($row_result, "，");
                        }
                    } elseif ($attr2 == "equips_cmmt") {
                        $equips_cmmt = $row['nequips'];
                        $equip_cmmt = explode(',', $equips_cmmt);
                        $row_result = "";
                        if ($equips_cmmt) {
                            foreach ($equip_cmmt as $equips_cmmt_para) {
                                $equips_cmmt_id = explode('_', $equips_cmmt_para)[2];
                                $sql = "SELECT iname FROM system_item_module WHERE iid = :iid";
                                $stmt = $db->prepare($sql);
                                $stmt->execute([':iid' => $equips_cmmt_id]);
                                $equip_row = $stmt->fetch(PDO::FETCH_ASSOC);
                                $row_result .= "，" . $equip_row['iname'];
                            }
                            $row_result = ltrim($row_result, "，");
                        }
                    } else {
                        $row_result = $row[$attr3];
                    }

                    if ($row_result === null || $row_result === '') {
                        $op = 0; // 或其他默认值
                    } else {
                        $op = nl2br($row_result);
                    }
                    $op = process_string($op, $sid, $oid, $mid, $jid, $type, $para);
                    // 替换字符串中的变量
                    //$input = str_replace("{{$match}}", $op, $input);
                    break;
                case 'item':
                    $attr3 = 'i' . $attr2;
                    if ($attr3 == "icount" || $attr3 == "iroot") {
                        $sql = "SELECT * FROM system_item WHERE item_true_id = :mid AND sid = :sid";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([':mid' => $mid, ':sid' => $sid]);
                    } else {
                        $sql = "SELECT * FROM system_item_module WHERE iid = (SELECT iid FROM system_item WHERE item_true_id = :mid AND sid = :sid)";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([':mid' => $mid, ':sid' => $sid]);
                    }
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (!$row) {
                        die('查询失败: ' . $db->errorInfo()[2]);
                    }
                    $row_result = $row[$attr3];
                    if ($attr3 == "iroot") {
                        $item_para = explode("|", $row_result);
                        $para_1 = $item_para[0];
                        $para_2 = $item_para[1];
                        if ($para_1 == 1) {
                            $sql = "SELECT nname FROM system_npc WHERE nid = :nid";
                            $stmt = $db->prepare($sql);
                            $stmt->execute([':nid' => $para_2]);
                            $row_npc = $stmt->fetch(PDO::FETCH_ASSOC);
                            $row_npc_name = $row_npc['nname'];
                            $row_result = "怪物掉落" . "|" . $row_npc_name;
                        } else {
                            $row_result = "未知来源";
                        }
                    }
                    if ($row_result === null || $row_result === '') {
                        $op = 0; // 或其他默认值
                    } else {
                        $op = nl2br($row_result);
                    }
                    $op = process_string($op, $sid, $oid, $mid, $jid, $type, $para);
                    // 替换字符串中的变量
                    //$input = str_replace("{{$match}}", $op, $input);
                    break;
                case 'scene_oplayer':
                    if (strpos($attr2, "env.") === 0) {
                        $attr3 = substr($attr2, 4); // 提取 "env." 后面的部分
                        switch ($attr3) {
                            case 'user_count':
                                // 构建 SQL 查询语句
                                $sql = "SELECT COUNT(*) as count FROM game1 WHERE sfzx=1 and nowmid IN (SELECT nowmid FROM game1 WHERE sid = :sid)";
                                // 使用预处理语句
                                $stmt = $db->prepare($sql);
                                // 执行查询
                                $stmt->execute([':sid' => $mid]);
                                // 获取查询结果
                                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                $op = $row["count"];
                                break;
                            case 'npc_count':
                                $sql = "SELECT mnpc_now FROM system_map WHERE mid = (SELECT nowmid FROM game1 WHERE sid = :sid)";
                                // 使用预处理语句
                                $stmt = $db->prepare($sql);
                                $stmt->execute([':sid' => $mid]);
                                // 获取查询结果
                                $totalNpcCount = 0;
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    $mnpc = $row["mnpc_now"];
                                    $npcs = explode(",", $mnpc); // 拆分成每个npc项
                                    foreach ($npcs as $npc) {
                                        list(, $npcCount) = explode("|", $npc);
                                        $totalNpcCount += (int)$npcCount; // 将每个npc的数量累加
                                    }
                                }
                                $op = $totalNpcCount;
                                break;
                            case 'monster_count':
                                $sql = "SELECT COUNT(*) as count FROM system_npc_midguaiwu WHERE nsid = '' and nmid = (SELECT nowmid FROM game1 WHERE sid = :sid)";
                                // 使用预处理语句
                                $stmt = $db->prepare($sql);
                                // 执行查询
                                $stmt->execute([':sid' => $mid]);
                                // 获取查询结果
                                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                // 处理结果
                                $op = $row["count"];
                                break;
                            case 'item_count':
                                $sql = "SELECT mitem_now FROM system_map WHERE mid = (SELECT nowmid FROM game1 WHERE sid = :sid)";
                                // 使用预处理语句
                                $stmt = $db->prepare($sql);
                                // 执行查询
                                $stmt->execute([':sid' => $mid]);
                                // 获取查询结果
                                // 处理结果
                                $totalItemCount = 0;
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    $mitem = $row["mitem_now"];
                                    $items = explode(",", $mitem); // 拆分成每个item项
                                    foreach ($items as $item) {
                                        list(, $itemCount) = explode("|", $item);
                                        $totalItemCount += (int)$itemCount; // 将每个item的数量累加
                                    }
                                }
                                $op = $totalItemCount;
                                break;
                            case 'justmid':
                                // 构建 SQL 查询语句
                                $sql = "SELECT justmid FROM game1 WHERE sid = :sid";
                                // 使用预处理语句
                                $stmt = $db->prepare($sql);
                                // 执行查询
                                $stmt->execute([':sid' => $mid]);
                                // 获取查询结果
                                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                $op = $row["justmid"];
                                break;
                            case 'nowmid':
                                // 构建 SQL 查询语句
                                $sql = "SELECT nowmid FROM game1 WHERE sid = :sid";
                                // 使用预处理语句
                                $stmt = $db->prepare($sql);
                                // 执行查询
                                $stmt->execute([':sid' => $mid]);
                                // 获取查询结果
                                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                $op = $row["nowmid"];
                                break;
                            case 'name':
                                // 构建 SQL 查询语句
                                $sql = "SELECT mname FROM system_map WHERE mid = (SELECT nowmid FROM game1 WHERE sid = :sid)";
                                // 使用预处理语句
                                $stmt = $db->prepare($sql);
                                // 执行查询
                                $stmt->execute([':sid' => $mid]);
                                // 获取查询结果
                                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                $op = $row["mname"];
                                $sql = "SELECT uis_sailing FROM game1 WHERE sid = :sid";
                                // 使用预处理语句
                                $stmt = $db->prepare($sql);
                                // 执行查询
                                $stmt->execute([':sid' => $mid]);
                                // 获取查询结果
                                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                $is_sailing = $row["uis_sailing"];
                                if ($is_sailing == 1) {
                                    $op = "茫茫大海";
                                }
                                break;
                        }
                    } else {
                        $attr3 = 'u' . $attr2;
                        $sql = "SHOW COLUMNS FROM game1 LIKE :attr3";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([':attr3' => $attr3]);
                        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        if (count($result) > 0) {
                            $sql = "SELECT * FROM game1 WHERE sid = :sid";
                            $stmt = $db->prepare($sql);
                            $stmt->execute([':sid' => $mid]);
                        } else {
                            $sql = "SELECT * FROM system_addition_attr WHERE sid = :sid AND name = :attr3";
                            $stmt = $db->prepare($sql);
                            $stmt->execute([':sid' => $mid, ':attr3' => $attr3]);
                            $attr_type = 1;
                        }
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($row === false) {
                            $op = 0; // 或其他默认值
                        } else {
                            if (!isset($attr_type) || $attr_type != 1) {
                                $op = nl2br($row[$attr3]);
                            } else {
                                $op = nl2br($row['value']);
                            }
                        }
                        if ($op === '') {
                            $op = 0;
                        }
                        $op = process_string($op, $sid, $oid, $mid, $jid, $type, $para);
                        // 替换字符串中的变量
                        break;
                    }
                    break;
                default:
                    $attr3 = 'n' . $attr2;
                    $sql = "SELECT * FROM system_npc_midguaiwu WHERE ngid = :ngid AND nsid = :nsid";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([':ngid' => $oid, ':nsid' => $sid]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($row === false) {
                        $op = 0; // 或其他默认值
                    } else {
                        $op = nl2br($row[$attr3]);
                    }
                    $op = process_string($op, $sid, $oid, $mid, $jid, $type, $para);
                    // 替换字符串中的变量
                    //$input = str_replace("{{$match}}", $op, $input);
                    break;
            }
            break;
        case 'm':
            if ($para != 1) {
                switch ($type) {
                    case 'fight':
                        $attr3 = 'j' . $attr2;
                        if ($attr3 == "jlvl" || $attr3 == "jpoint" || $attr3 == "jdefault") {
                            $sql = "SELECT * FROM system_skill_user WHERE jid = :jid AND jsid = :jsid";
                            $stmt = $db->prepare($sql);
                            $stmt->execute([':jid' => $jid, ':jsid' => $sid]);
                        } else {
                            $sql = "SELECT * FROM system_skill WHERE jid = :jid";
                            $stmt = $db->prepare($sql);
                            $stmt->execute([':jid' => $jid]);
                        }
                        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        if (count($result) == 0) {
                            die('查询失败: 没有找到匹配的记录');
                        }
                        $row = $result[0];
                        $row_result = $row[$attr3] ?? null;
                        if ($row_result === null || $row_result === '') {
                            $op = 0; // 或其他默认值
                        } else {
                            $op = nl2br($row_result);
                        }
                        $op = process_string($op, $sid, $oid, $mid, $jid, $type, $para);
                        if ($attr3 == "jgroup_attack") {
                            if ($row_result == -1) {
                                $op = "群体";
                            } elseif ($row_result == 1) {
                                $op = "单体";
                            }
                        } elseif ($attr3 == "jhurt_attr" || $attr3 == "jdeplete_attr") {
                            // 查询获取 name 字段值
                            $query = "SELECT name FROM gm_game_attr WHERE value_type = 1 AND id = :id";
                            $stmt = $db->prepare($query);
                            $stmt->execute([':id' => $row_result]);
                            $op = $stmt->fetchColumn();
                        }

                        // 替换字符串中的变量
                        //$input = str_replace("{{$match}}", $op, $input);
                        break;
                    default:
                        $attr3 = 'j' . $attr2;
                        if ($attr3 == "jlvl" || $attr3 == "jpoint" || $attr3 == "jdefault") {
                            $sql = "SELECT * FROM system_skill_user WHERE jid = :jid AND jsid = :jsid";
                            $stmt = $db->prepare($sql);
                            $stmt->execute([':jid' => $jid, ':jsid' => $sid]);
                        } else {
                            $sql = "SELECT * FROM system_skill WHERE jid = :jid";
                            $stmt = $db->prepare($sql);
                            $stmt->execute([':jid' => $jid]);
                        }
                        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        if (empty($result)) {
                            die('查询失败: 没有找到匹配的记录');
                        }
                        $row = $result[0];
                        $row_result = $row[$attr3] ?? null;
                        if ($row_result === null || $row_result === '') {
                            $op = 0; // 或其他默认值
                        } else {
                            $op = nl2br($row_result);
                        }
                        $op = process_string($op, $sid, $oid, $mid, $jid, $type, $para);
                        if ($attr3 == "jgroup_attack") {
                            if ($row_result == -1) {
                                $op = "群体";
                            } elseif ($row_result == 1) {
                                $op = "单体";
                            }
                        } elseif ($attr3 == "jhurt_attr" || $attr3 == "jdeplete_attr") {
                            // 查询获取 name 字段值
                            $query = "SELECT name FROM gm_game_attr WHERE value_type = 1 AND id = :id";
                            $stmt = $db->prepare($query);
                            $stmt->execute([':id' => $row_result]);
                            $op = $stmt->fetchColumn();
                        }
                        break;
                }
            } elseif ($para == 1) {
                $attr3 = 'j' . $attr2;
                //TODO 代码逻辑有问题，待修改
                if ($attr3 == "jlvl") {
                    $sql = "SELECT * FROM system_npc_midguaiwu WHERE ngid = :ngid";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([':ngid' => $oid]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $row_result = $row['nskills'];
                    $monster_skills_lvl = explode(',', $row_result);
                } else {
                    $sql = "SELECT * FROM system_skill WHERE jid = :jid";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([':jid' => $mid]);
                }
                if (!$stmt) {
                    die('查询失败: ' . $db->errorInfo()[2]);
                }
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $row_result = $row[$attr3];
                if ($para != 1) {
                    $row_result = $row[$attr3];
                }
                if ($row_result === null || $row_result === '') {
                    $op = 0; // 或其他默认值
                } else {
                    $op = nl2br($row_result);
                }
                $op = process_string($op, $sid, $oid, $mid, $jid, $type, $para);
            }
            break;
        case 'c':
            switch ($attr2) {
                case 'time':
                    $op = date('U');
                    break;
                case 'day':
                    $op = date('N');
                    break;
                case 'year':
                    $op = date('Y');
                    break;
                case 'month':
                    $op = date('n');
                    break;
                case 'date':
                    $op = date('j');
                    break;
                case 'hour':
                    $op = date('G');
                    break;
                case 'minute':
                    $op = 1 * date('i');
                    break;
                case 'second':
                    $op = 1 * date('s');
                    break;
                case 'online_user_count':
                    $query = "SELECT COUNT(*) FROM game1 WHERE sfzx = 1";
                    // 执行查询语句并获取结果
                    $stmt = $db->prepare($query);
                    $stmt->execute();
                    // 获取行数
                    $op = $stmt->fetchColumn();
                    break;
                default:
                    $game_id = '19980925';
                    $attr4 = 'game_';
                    $attr3 = $attr4 . $attr2;
                    $sql = "SELECT * FROM gm_game_basic WHERE game_id = ?";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([$game_id]);
                    if (!$stmt) {
                        die('查询失败: ' . $db->errorInfo()[2]);
                    }
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($row === null) {
                        $op = 0; // 或其他默认值
                    } else {
                        $op = nl2br($row[$attr3]);
                    }
            }
            // 使用正则表达式匹配字符串中的时间格式部分
            $pattern = '/nowtime_([UNYnjGHhist:]+)/';
            if (preg_match($pattern, $attr2, $matches)) {
                // 获取当前时间，并根据格式解析为具体时间信息
                $op = date($matches[1]);
            }
            $op = process_string($op, $sid, $oid, $mid, $jid, $type, $para);
            // 替换字符串中的变量
            //$input = str_replace("{{$match}}", $op, $input);
            break;
        case 'g':
            $sql = "SELECT gvalue FROM global_data WHERE gid = :attr2";
            $stmt = $db->prepare($sql);
            $stmt->execute(['attr2' => $attr2]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row === false) {
                $op = 0; // 或其他默认值
            } else {
                $op = nl2br($row['gvalue']);
            }
            $op = process_string($op, $sid, $oid, $mid, $jid, $type, $para);
            // 替换字符串中的变量
            //$input = str_replace("{{$match}}", $op, $input);
            break;
        case 'e':
            $sql = "SELECT * FROM system_exp_def WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$attr2]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                die('查询失败: ' . $db->errorInfo()[2]);
            }
            $op = nl2br($row['value']);
            // 替换字符串中的变量
            $op = process_string($op, $sid, $oid, $mid, $jid, $type, $para);

            $op = @eval("return '$op';");
            //$input = str_replace("{{$match}}", $op, $input);
            break;
        case 'r':
            if (!is_numeric($attr2)) {
                $attr2 = "{" . $attr2 . "}";
            }
            $attr2 = process_string($attr2, $sid, $oid, $mid, $jid, $type, $para);
            if (intval($attr2) <= 0) {
                $attr2 = 1;
            }
            $op = rand(0, intval($attr2) - 1); // 生成随机整数
            //$op = "\"$op\"";
            break;
        case 'gph':
            $attr_para = explode(".", "$attr2");
            $attr_id = $attr_para[0];
            $attr_pos = $attr_para[1];
            $attr_attr = $attr_para[2];
            // 提取获取排名数据的函数
            if (!function_exists('lexical_analysis\getRankData')) {
                function getRankData($db)
                {
                    $sql = "SELECT * FROM system_rank";
                    $stmt = $db->query($sql);
                    if (!$stmt) {
                        die('查询失败: ' . $db->errorInfo()[2]);
                    }
                    $rankData = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    return $rankData;
                }
            }

            // 提取获取用户数据的函数
            if (!function_exists('lexical_analysis\getUserData')) {
                function getUserData($db, $rankExp, $showCond)
                {
                    $sql = "SELECT uname, sid, uid FROM game1";
                    $stmt = $db->query($sql);
                    if (!$stmt) {
                        die('查询失败: ' . $db->errorInfo()[2]);
                    }
                    $userData = [];
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $userSid = $row['sid'];
                        $userExp = process_string($rankExp, $userSid);
                        $userShowCond = checkTriggerCondition($showCond, $db, $userSid);

                        if (is_null($userShowCond)) {
                            $userShowCond = 1;
                        }

                        if ($userShowCond) {
                            $user_name = $row['uname'];
                            $userUid = $row['uid'];
                            $userData[] = [
                                'score' => $userExp,
                                'id' => $userUid,
                                'name' => $user_name
                            ];
                        }
                    }
                    return $userData;
                }
            }
            // 获取排名数据
            $rankData = getRankData($db);

            $counter = 0;
            foreach ($rankData as $row) {
                $rankExp = $row['rank_exp'];
                $show_cond = $row['show_cond'];
                $userData = getUserData($db, $rankExp, $show_cond);
                usort($userData, function ($a, $b) {
                    return $b['score'] - $a['score'];
                });

                if ($attr_id == $counter) {
                    $op = isset($userData[$attr_pos][$attr_attr]) ? $userData[$attr_pos][$attr_attr] : 0;
                }
                $counter++;
            }
            $op = process_string($op, $sid, $oid, $mid, $jid, $type, $para);
            break;
        case 'gphn':
            $attr_para = explode(".", "$attr2");
            $attr_name = $attr_para[0];
            $attr_pos = $attr_para[1];
            $attr_attr = $attr_para[2];

            // 缓存的键名
            $rankCacheKey = "rankData_$attr_name";
            $userCacheKey = "userData_$attr_name";

            // 提取获取排名数据的函数
            if (!function_exists('lexical_analysis\getRankData2')) {
                function getRankData2($db, $rank_name)
                {
                    $sql = "SELECT * FROM system_rank WHERE rank_name = :rank_name";
                    $stmt = $db->prepare($sql);
                    $stmt->execute(['rank_name' => $rank_name]);
                    if (!$stmt) {
                        die('查询失败: ' . $db->errorInfo()[2]);
                    }
                    $rankData = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    return $rankData;
                }
            }

            // 提取获取用户数据的函数
            if (!function_exists('lexical_analysis\getUserData2')) {
                function getUserData2($db, $rankExp, $showCond)
                {
                    $sql = "SELECT uname, sid, uid FROM game1";
                    $stmt = $db->query($sql);
                    if (!$stmt) {
                        die('查询失败: ' . $db->errorInfo()[2]);
                    }
                    $userData = [];
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $userSid = $row['sid'];
                        $userExp = process_string($rankExp, $userSid);
                        $userShowCond = checkTriggerCondition($showCond, $db, $userSid);
                        if (is_null($userShowCond)) {
                            $userShowCond = 1;
                        }
                        if ($userShowCond) {
                            $user_name = $row['uname'];
                            $userUid = $row['uid'];
                            $userData[] = [
                                'score' => $userExp,
                                'id' => $userUid,
                                'name' => $user_name
                            ];
                        }
                    }
                    return $userData;
                }
            }

            // 从缓存获取排名数据
            $rankData = Cache::get($rankCacheKey);
            if ($rankData === false) {
                $rankData = getRankData2($db, $attr_name);
                Cache::set($rankCacheKey, $rankData, 1); // 缓存1秒
            }

            foreach ($rankData as $row) {
                $rankExp = $row['rank_exp'];
                $show_cond = $row['show_cond'];

                // 从缓存获取用户数据
                $userData = Cache::get($userCacheKey);
                if ($userData === false) {
                    $userData = getUserData2($db, $rankExp, $show_cond);
                    Cache::set($userCacheKey, $userData, 1); // 缓存1秒
                }

                usort($userData, function ($a, $b) {
                    return $b['score'] - $a['score'];
                });
                $op = $userData[$attr_pos][$attr_attr] ?? 0;
            }
            $op = process_string($op, $sid, $oid, $mid, $jid, $type, $para);
            break;
        default:
            return 0;
            break;
    }
    // 在这里根据属性的不同进行处理
    // ...
    // 返回属性值，处理过程中可能会嵌套调用 process_string
    return $op;
}


class Cache
{
    private static $cache = [];
    private static $expiry = [];

    public static function set($key, $value, $ttl)
    {
        self::$cache[$key] = $value;
        self::$expiry[$key] = time() + $ttl;
    }

    public static function get($key)
    {
        if (isset(self::$cache[$key]) && time() < self::$expiry[$key]) {
            return self::$cache[$key];
        }
        return false;
    }

    public static function clear($key)
    {
        unset(self::$cache[$key]);
        unset(self::$expiry[$key]);
    }
}
   
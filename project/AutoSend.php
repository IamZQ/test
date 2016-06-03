<?php
/**
 * 活动开始30天后，定时陆续给邀请人发放 30 天前注册的被邀请人投资所获得的现金券奖励
 * User: Administrator
 * Date: 2016/5/19
 * Time: 13:57
 */
ini_set('display_errors', 1);//设置开启错误提示
error_reporting('E_ALL & ~E_NOTICE ');//错误等级提示
include ("core/config.inc.php");
set_time_limit(0);
$startTime = mktime(0, 0, 0, 4, 25, 2016);//活动开始时间

//取出满足发放现金券的用户信息
function statistic_user($startTime)
{
    global $mysql;
    $nowTime = time();  //现在的时间
    $mapTime = $nowTime - 30*24*60*60;//获取注册的时间已经超过30天的
    $sql = " SELECT
 a.inviteuser_id,a.tender_money,a.borrow_id,a.register_time,b.user_id,b.username
FROM rd_luck_tender a INNER  JOIN rd_user b ON a.inviteuser_id = b.user_id WHERE a.register_time  = $mapTime
AND a.register_time >$startTime AND a.tender_money > 3000";

    $result=$mysql->db_query($sql);
    if($result){
        return $result;
    }else{
        return false;
    }


}
//发放
function add_voucher()
{
    global $mysql;
    try {
        $keys = array('username', 'tender_money', 'add_ip', 'add_byuser', 'add_reason');
        if (count($data) != count($keys)) {
            throw new Exception('参数长度不匹配', -1);
        }
        foreach ($keys as $val) {
            if (!array_key_exists($val, $data)) {
                throw new Exception("参数：{$val}不存在", -1);
            }
        }
        $user_id = $this->get_userid($data['username']);
        if (!$user_id) {
            throw new Exception("无此用户", -1);
        }
        $data['type'] = 0;
        $data['account'] = 30;
        $data['user_id'] = $user_id;
        $mysql->beginTransaction();//开启事务

        for ($i = 0; $i < $nums; $i++) {
            $this->_add_vocher($data);          //循环插入数据
        }
        $mysql->commit();

    } catch (Exception $e) {
        echo $e->getMessage();
        if ($e->getCode() != -1) {
            $mysql->rollBack();//回滚
        }
        return false;
    }
    return true;
}


function _add_vocher($data)
{
    if (!isset($kfxpdo)) {
        $sql = "insert into rd_voucher(vid,type,stat,user_id,account,exp_time,add_time,add_ip,add_byuser,add_reason) values(:vid,:type,:stat,:user_id,:account,:exp_time,:add_time,:add_ip,:add_byuser,:add_reason)");

        $info = $mysql->prepare($sql);

    }

    $data['stat'] = 0;//未使用标识 -1过期 1已用
    $data['add_time'] = time();
    if (!isset($data['exp_time'])) {
        $data['exp_time'] = mktime(23, 59, 59, date('n', $data['add_time']) + 3, date("d", $data['add_time']), date("Y", $data['add_time']));
    }
    $data['vid'] = uniqid();//基于以微秒计的当前时间，生成一个唯一的 ID


}
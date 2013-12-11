<?php 

require 'vendor/autoload.php';
require_once(dirname(__FILE__) . '/src/Yapo/YapoCondition.php');


$condition = new Yapo\YapoCondition(array(array('haha', '=', 'xx'), array('fsaf', '!=', 'fas')));
$condition = new Yapo\YapoCondition();
$condition->and(array('haha', '=', 'xx'), array('fsaf', '!=', 'fas'));
$condition->or(array(array('fdsa', '!=', 'yy'), array('fd', 'in', array('zz', 'gg'))));
print($condition->sql());
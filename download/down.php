<?php

require_once dirname(__FILE__).'/../../source/class/class_core.php';

if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

$tempMethod = $_SERVER['REQUEST_METHOD'];
$_SERVER['REQUEST_METHOD'] = 'POST';
define('DISABLEXSSCHECK', 1);
define('DISABLEDEFENSE', 1);
C::creatapp();
C::app()->init();
$_SERVER['REQUEST_METHOD'] = $tempMethod;

$_G['siteurl'] = substr($_G['siteurl'], 0, -18);
$url = $_G['siteurl'].'/plugin.php?id=appbyme_app:download';
header('Location: '.$url);
?>

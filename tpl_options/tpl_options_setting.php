<?php

/*
Plugin Name: 模板设置
Version: 0.1
Plugin URL: https://github.com/Baiqiang/em_tpl_options
Description: 允许为支持的模板设置参数。
ForEmlog: 5.2.0+
Author: emlog
Author URL: http://www.qiyuuu.com
*/
!defined('EMLOG_ROOT') && exit('access deined!');

//插件设置页面
function plugin_setting_view() {
	TplOptions::getInstance()->setting();
}

//插件设置函数，不用
function plugin_setting() {
}

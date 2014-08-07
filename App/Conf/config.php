<?php
return array(
	'APP_DEBUG'   =>  true,
	'URL_MODEL'=>0,
	'URL_CASE_INSENSITIVE' =>true,
	'TMPL_ACTION_ERROR' => 'Public:message', 
	'TMPL_ACTION_SUCCESS' => 'Public:message',
	'TMPL_EXCEPTION_FILE'=>'./App/Tpl/Public/exception.html',
	'DEFAULT_TIMEZONE' => 'PRC',
	'LOAD_EXT_CONFIG' => 'db,version',
	'LOG_RECORD'			=>	true,  // 进行日志记录
    'LOG_EXCEPTION_RECORD'  => 	true,    // 是否记录异常信息日志
	'LOG_LEVEL'  =>'EMERG,ALERT,CRIT,ERR,WARN',
	'OUTPUT_ENCODE' => false,
    'LANG_SWITCH_ON' => true,
    'LANG_AUTO_DETECT' => true,
    'LANG_LIST' => 'zh-cn',
    'VAR_LANGUAGE' => 'l',
    'SHOW_ERROR_MSG'        => false,    // 显示错误信息
	'URL_ROUTER_ON'   => true, //开启路由
	'URL_ROUTE_RULES' => array( //定义路由规则
		'yhb/:id'               => 'index.php?m=product&a=edit&product_id=1',
	),
);
?>
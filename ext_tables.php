<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

t3lib_extMgm::allowTableOnStandardPages('tx_nntpreader_groups');

$TCA["tx_nntpreader_server"] = array (
	"ctrl" => array (
		'title'     => 'LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_server',		
		'label'     => 'name',	
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => "ORDER BY crdate",	
		'delete' => 'deleted',	
		'enablecolumns' => array (		
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_nntpreader_server.gif',
	),
	"feInterface" => array (
		"fe_admin_fieldList" => "hidden, name, address, port",
	)
);


$TCA["tx_nntpreader_groups"] = array (
	"ctrl" => array (
		'title'     => 'LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_groups',		
		'label'     => 'name',	
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => "ORDER BY crdate",	
		'delete' => 'deleted',	
		'enablecolumns' => array (		
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_nntpreader_groups.gif',
	),
	"feInterface" => array (
		"fe_admin_fieldList" => "hidden, name, nntp_name, pull, pull_interval, server, messages, last_uid",
	)
);



$TCA["tx_nntpreader_messages"] = array (
	"ctrl" => array (
		'title'     => 'LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_messages',		
		'label'     => 'subject',	
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => "ORDER BY crdate",	
		'delete' => 'deleted',	
		'enablecolumns' => array (		
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_nntpreader_messages.gif',
	),
	"feInterface" => array (
		"fe_admin_fieldList" => "hidden, msgid, parentid, msgroot, group_81d513b7a0, emailfrom, namefrom, emailreplyto, namereplyto, subject, references, parts, maildate, mailsize, views, answers",
	)
);

$TCA["tx_nntpreader_text"] = array (
	"ctrl" => array (
		'title'     => 'LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_text',		
		'label'     => 'subject',	
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => "ORDER BY crdate",	
		'delete' => 'deleted',	
		'enablecolumns' => array (		
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_nntpreader_text.gif',
	),
	"feInterface" => array (
		"fe_admin_fieldList" => "hidden, msgparent, partparent, subparts, type, file, text, indextext",
	)
);


t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key';


t3lib_extMgm::addPlugin(array('LLL:EXT:nntpreader/locallang_db.xml:tt_content.list_type_pi1', $_EXTKEY.'_pi1'),'list_type');


t3lib_extMgm::addStaticFile($_EXTKEY,"pi1/static/","NNTP Reader");


t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi2']='layout,select_key';


t3lib_extMgm::addPlugin(array('LLL:EXT:nntpreader/locallang_db.xml:tt_content.list_type_pi2', $_EXTKEY.'_pi2'),'list_type');


t3lib_extMgm::addStaticFile($_EXTKEY,"pi2/static/","NNTP Admin");

t3lib_extMgm::addStaticFile($_EXTKEY,'static/NNTP_Settings/', 'NNTP Settings');
?>

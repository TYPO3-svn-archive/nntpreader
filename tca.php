<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA["tx_nntpreader_server"] = array (
	"ctrl" => $TCA["tx_nntpreader_server"]["ctrl"],
	"interface" => array (
		"showRecordFieldList" => "hidden,name,address,port"
	),
	"feInterface" => $TCA["tx_nntpreader_server"]["feInterface"],
	"columns" => array (
		'hidden' => array (		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		"name" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_server.name",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",
			)
		),
		"address" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_server.address",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",
			)
		),
		"port" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_server.port",		
			"config" => Array (
				"type"     => "input",
				"size"     => "4",
				"max"      => "4",
				"eval"     => "int",
				"checkbox" => "0",
				"range"    => Array (
					"upper" => "1000",
					"lower" => "10"
				),
				"default" => 0
			)
		),
	),
	"types" => array (
		"0" => array("showitem" => "hidden;;1;;1-1-1, name, address, port")
	),
	"palettes" => array (
		"1" => array("showitem" => "")
	)
);



$TCA["tx_nntpreader_groups"] = array (
	"ctrl" => $TCA["tx_nntpreader_groups"]["ctrl"],
	"interface" => array (
		"showRecordFieldList" => "hidden,new,name,nntp_name,category,pull,pull_interval,server,messages,threads,last_uid,last_number"
	),
	"feInterface" => $TCA["tx_nntpreader_groups"]["feInterface"],
	"columns" => array (
		'hidden' => array (		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		"name" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_groups.name",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",
			)
		),
		"nntp_name" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_groups.nntp_name",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",
			)
		),
		"category" => Array (        
            "exclude" => 1,        
            "label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_groups.category",        
            "config" => Array (
                "type" => "select",
                "items" => Array (
                    Array("LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_groups.category.I.0", "0"),
                    Array("LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_groups.category.I.1", "1"),
                    Array("LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_groups.category.I.2", "2"),
                    Array("LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_groups.category.I.3", "3"),
                    Array("LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_groups.category.I.4", "4"),
                ),
                "size" => 1,    
                "maxitems" => 1,
            )
        ),
		"pull" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_groups.pull",		
			"config" => Array (
				"type" => "check",
			)
		),
		"pull_interval" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_groups.pull_interval",		
			"config" => Array (
				"type"     => "input",
				"size"     => "4",
				"max"      => "4",
				"eval"     => "int",
				"checkbox" => "0",
				"range"    => Array (
					"upper" => "1000",
					"lower" => "10"
				),
				"default" => 0
			)
		),
		"server" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_groups.server",		
			"config" => Array (
				"type" => "select",	
				"foreign_table" => "tx_nntpreader_server",	
				"foreign_table_where" => "ORDER BY tx_nntpreader_server.uid",	
				"size" => 1,	
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
		"messages" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_groups.messages",		
			"config" => Array (
				"type"     => "input",
				"size"     => "4",
				"max"      => "6",
				"eval"     => "int",
				"checkbox" => "0",
				"default" => 0
			)
		),
		"threads" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_groups.threads",		
			"config" => Array (
				"type"     => "input",
				"size"     => "4",
				"max"      => "6",
				"eval"     => "int",
				"checkbox" => "0",
				"default" => 0
			)
		),
		"last_uid" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_groups.last_uid",		
			"config" => Array (
				"type"     => "input",
				"size"     => "4",
				"max"      => "6",
				"eval"     => "int",
				"checkbox" => "0",
				"default" => 0
			)
		),
		"last_number" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_groups.last_number",		
			"config" => Array (
				"type"     => "input",
				"size"     => "4",
				"max"      => "6",
				"eval"     => "int",
				"checkbox" => "0",
				"default" => 0
			)
		),
		"new" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_groups.new",		
			"config" => Array (
				"type" => "check",
			)
		),
	),
	"types" => array (
		"0" => array("showitem" => "hidden;;1;;1-1-1, new, name, nntp_name, category, pull, pull_interval, server, messages, threads, last_uid,last_number")
	),
	"palettes" => array (
		"1" => array("showitem" => "")
	)
);





$TCA["tx_nntpreader_messages"] = array (
	"ctrl" => $TCA["tx_nntpreader_messages"]["ctrl"],
	"interface" => array (
		"showRecordFieldList" => "hidden,msguid,msgnumber,msgid,parentid,msgroot,newsgroup,emailfrom,namefrom,emailreplyto,namereplyto,subject,msgreference,type,files,maildate,mailsize,views,answers,hasChild, header, body, body_raw"
	),
	"feInterface" => $TCA["tx_nntpreader_messages"]["feInterface"],
	"columns" => array (
		'hidden' => array (		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		"msguid" => Array (     
            "exclude" => 1,     
            "label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_messages.msguid",     
            "config" => Array (
                "type" => "input",  
                "size" => "60",
            )
        ),
        "msgnumber" => Array (      
            "exclude" => 1,     
            "label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_messages.msgnumber",      
            "config" => Array (
                "type" => "input",  
                "size" => "60",
            )
        ),
        "msgid" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_messages.msgid",		
			"config" => Array (
				"type" => "input",	
				"size" => "60",
			)
		),
		"parentid" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_messages.parentid",		
			"config" => Array (
				"type" => "input",	
				"size" => "60",
			)
		),
		"msgroot" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_messages.msgroot",		
			"config" => Array (
				"type"     => "input",
				"size"     => "4",
				"max"      => "4",
				"eval"     => "int",
				"checkbox" => "0",
				"default" => 0
			)
		),
		"newsgroup" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_messages.newsgroup",		
			"config" => Array (
				"type" => "select",    
                "foreign_table" => "tx_nntpreader_groups",    
                "foreign_table_where" => "ORDER BY tx_nntpreader_groups.nntp_name",    
                "size" => 1,    
                "minitems" => 0,
                "maxitems" => 1,
                
			)
		),
		"emailfrom" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_messages.emailfrom",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",
			)
		),
		"namefrom" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_messages.namefrom",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",
			)
		),
		"emailreplyto" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_messages.emailreplyto",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",
			)
		),
		"namereplyto" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_messages.namereplyto",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",
			)
		),
		"subject" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_messages.subject",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",
			)
		),
		"msgreference" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_messages.msgreferences",		
			"config" => Array (
				"type" => "input",	
				"size" => "60",
			)
		),
		"type" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_messages.type",		
			"config" => Array (
				"type" => "input",	
				"size" => "60",
			)
		),
		'files' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_messages.files',		
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'file',
				'uploadfolder' => 'uploads/tx_nntpreader',
				'show_thumbs' => 1,
				'size' => 5,
				'maxitems' => 5,
				'minitems' => 0,
			)
		),
		"maildate" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_messages.maildate",		
			"config" => Array (
				"type"     => "input",
				"size"     => "11",
				"max"      => "11",
				"eval"     => "int",
				"checkbox" => "0",
				"default" => 0
			)
		),
		"mailsize" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_messages.mailsize",		
			"config" => Array (
				"type"     => "input",
				"size"     => "8",
				"max"      => "8",
				"eval"     => "int",
				"checkbox" => "0",
				"default" => 0
			)
		),
		"views" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_messages.views",		
			"config" => Array (
				"type"     => "input",
				"size"     => "8",
				"max"      => "8",
				"eval"     => "int",
				"checkbox" => "0",
				"default" => 0
			)
		),
		"answers" => Array (        
            "exclude" => 1,     
            "label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_messages.answers",        
            "config" => Array (
                "type"     => "input",
                "size"     => "8",
                "max"      => "8",
                "eval"     => "int",
                "checkbox" => "0",
                "default" => 0
            )
        ),
        "hasChild" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_messages.hasChild",		
			"config" => Array (
				"type"     => "check",
				"default" => 0
			)
		),
		"textuid" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_messages.textuid",		
			"config" => Array (
				'type' => 'group',
				'foreign_table' => 'tx_nntpreader_text',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		
		
	),
	"types" => array (
		"0" => array("showitem" => "hidden;;1;;1-1-1, msguid,msgnumber,msgid, parentid, msgroot, newsgroup, emailfrom, namefrom, emailreplyto, namereplyto, subject, msgreference,type, files, maildate, mailsize, views, answers, hasChild, header, body, body_raw")
	),
	"palettes" => array (
		"1" => array("showitem" => "")
	)
);

$TCA['tx_nntpreader_text'] = Array (
	'ctrl' => $TCA['tx_nntpreader_text']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'hidden,body,body_raw,header,parent,newsgroup,subject'
	),
	'feInterface' => $TCA['tx_nntpif_messages']['feInterface'],
	'columns' => Array (
		'hidden' => Array (		
			'exclude' => 1,	
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		"body" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_text.body",		
			"config" => Array (
				"type" => "text",
				"cols" => "30",	
				"rows" => "5",
			)
		),
		"body_raw" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_text.body_raw",		
			"config" => Array (
				"type" => "text",
				"cols" => "30",	
				"rows" => "5",
			)
		),
		"header" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_text.header",		
			"config" => Array (
				"type" => "text",
				"cols" => "30",	
				"rows" => "5",
			)
		),
		"parent" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_text.parent",		
			"config" => Array (
				'type' => 'group',
				"internal_type" => "db",    
				'foreign_table' => 'tx_nntpreader_messages',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		"newsgroup" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_messages.newsgroup",		
			"config" => Array (
				"type" => "group",	
				"internal_type" => "db",    
				'foreign_table' => 'tx_nntpreader_groups',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		"subject" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:nntpreader/locallang_db.xml:tx_nntpreader_messages.subject",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",
			)
		),
	),
		
	'types' => Array (
		'0' => Array('showitem' => 'hidden;;1;;1-1-1, body,body_raw,header,parent,newsgroup,subject')
	),
	'palettes' => Array (
		"1" => array("showitem" => "")
	)
);
?>

#
# Table structure for table 'tx_nntpreader_groups'
#
CREATE TABLE tx_nntpreader_groups (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	name tinytext NOT NULL,
	nntp_name tinytext NOT NULL,
	category tinyint(4) DEFAULT '0' NOT NULL,
	pull tinyint(3) DEFAULT '0' NOT NULL,
	pull_interval int(11) DEFAULT '0' NOT NULL,
	server int(11) DEFAULT '0' NOT NULL,
    messages int(11) DEFAULT '0' NOT NULL,
	threads int(11) DEFAULT '0' NOT NULL,
    last_uid int(11) DEFAULT '0' NOT NULL,
	last_number int(11) DEFAULT '0' NOT NULL,
	new tinyint(4) DEFAULT '0' NOT NULL,
	
	PRIMARY KEY (uid),
	KEY parent (pid)
);



#
# Table structure for table 'tx_nntpreader_server'
#
CREATE TABLE tx_nntpreader_server (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	name tinytext NOT NULL,
	address tinytext NOT NULL,
	port int(11) DEFAULT '0' NOT NULL,
	
	PRIMARY KEY (uid),
	KEY parent (pid)
);



#
# Table structure for table 'tx_nntpreader_messages'
#
CREATE TABLE tx_nntpreader_messages (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
    msguid int(11) DEFAULT '0' NOT NULL,
	msgnumber int(11) DEFAULT '0' NOT NULL,
	msgid tinytext NOT NULL,
	parentid int(11) DEFAULT '0' NOT NULL,
	msgroot int(11) DEFAULT '0' NOT NULL,
	newsgroup int(11) DEFAULT '0' NOT NULL,
	emailfrom tinytext NOT NULL,
	namefrom tinytext NOT NULL,
	emailreplyto tinytext NOT NULL,
	namereplyto tinytext NOT NULL,
	subject tinytext NOT NULL,
	msgreference tinytext NOT NULL,
	type tinyint(4) DEFAULT '0' NOT NULL,
	files tinytext NOT NULL,
	maildate int(11) DEFAULT '0' NOT NULL,
	mailsize int(11) DEFAULT '0' NOT NULL,
	views int(11) DEFAULT '0' NOT NULL,
	answers int(11) DEFAULT '0' NOT NULL,
	hasChild tinyint(3) DEFAULT '0' NOT NULL,
	textuid int(11) DEFAULT '0' NOT NULL,
	
	PRIMARY KEY (uid),
	KEY parent (pid),
);


CREATE TABLE tx_nntpreader_text (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	body mediumtext NOT NULL,
	header mediumtext NOT NULL,
	
	PRIMARY KEY (uid),
	KEY parent (pid),
	FULLTEXT KEY messagetext (body),
);

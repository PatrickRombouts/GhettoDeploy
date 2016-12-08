<?php

$Configuration = array (
	'general' => array( 
		'gitexecutable' => 'git', // Path to git(.exe)
		'wget' => 'wget',
		'ncftpput' => 'ncftpput',
		'backupdir' => __DIR__.'/backup/',
		'deploydir' => __DIR__.'/deploy/',
	),
	'sites' => array(
		'mysite' => array (
			'deploymethod' => 'FTP',	// FTP and SSH supported
			'deploysource' => 'folder',	// folder and git supported
			'backup' => false,			// Always backup?
			
			// deploysource == git
			'giturl' => 'git://myserver.com/myproject.git',
			'gitbranch' => '',
			
			//deploysource == folder
			'sourcefolder' => __DIR__.'/../awesomeProject/',
			
			// Destination details
			'FTP' => array(
				'hostname' => 'myserver.com',
				'username' => 'r00t',
				'password' => 'secretpassword',
				'port' => 21,
			),
			'SSH' => array(
				'hostname' => '',
				'username' => '',
				'password' => '', /* not used, use public key authentication */
				'port' => 22,
			),
			
			'backuppaths' => array(
				'/domains/myserver.com/public_html/index.php' => './',
				'/domains/myserver.com/public_html/site/' => './',
				'/domains/myserver.com/cronjobs/' => './',
			),
			'excludebackuppaths' => array(
				'/domains/myserver.com/public_html/site/uploadedimg/'
			),			
			'excludecopypaths' => array( // Files simply will be removen from deploypath before uploading
				'/www/index.php',
				'/www/.htaccess',
				'/wordpress/wp-config.php',
				'/wordpress/.htaccess',
			),
			'replacefiles' => array(
				//relative from pusher			relative from deploypath
				'/custom/myawesomesite/.htaccess' => 	'/www/.htaccess',
			),
			'replacelines' => array(
				'/cronjobs/myCron.php' => array (
					'11' => "define('WWWDIR', __DIR__.\"/../../../\");",
				),		
			),
			'copypaths' => array(
				'/www/' => '/domains/myserver.com/public_html/',
				'/wordpress/' => '/domains/myserver.com/public_html/wordpress/',
				'/cronjobs/' => '/domains/myserver.com/cronjobs/',
			)
		) 
	)
);
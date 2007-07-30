<?php
/*---------------------------------------------------------------------------------
                            user-configurable options
---------------------------------------------------------------------------------*/

    /* _MAXKEYLENGTH = integer -
    sets the maximum length of indexes, used for sorting */
define("_MAXKEYLENGTH",10);

    /* _ALLOWUPGRADEINPLACE = false | true -
    allow the user to upgrade the current installation by over-writing it.
    If false, then the user should enter a new prefix in config.php          */
define("_ALLOWUPGRADEINPLACE",true);

    /* _ALLOWUNINSTALL = false | true -
    allow the user to remove tables associated with a particular GTD installation */
define("_ALLOWUNINSTALL",false);


/*---------------------------------------
             Debugging options
---------------------------------------*/

    /* _DEBUG = false | true -
    show lots of debugging information during execution */
define("_DEBUG",false);

    /* _DRY_RUN = false | true - dry run won't change the database, but will
    mime all the actions that would be done: use _DEBUG true to see these */
define("_DRY_RUN",false);

/*---------------------------------------------------------------------------------
                            End of user options
---------------------------------------------------------------------------------*/




/* _USEFULLTEXT = false | true - use FULLTEXT indexes, which take up a lot of
space, but allow you to use MATCH ... AGAINST NB GTD-php does not currently use this */
define("_USEFULLTEXT",false); 

require_once('gtd_constants.inc.php');
define("_DEFAULTDATE","1990-01-01");
define ("_TEMPPREFIX","_gtdphp_temptable_");

if (_USEFULLTEXT) {
   define("_CREATESUFFIX",' ENGINE=MyISAM ');
   define("_FULLTEXT",' FULLTEXT ');
   define ("_INDEXLEN",'');
}else{
   define("_CREATESUFFIX",' ');
   define("_FULLTEXT",' ');
   define ("_INDEXLEN",'('._MAXKEYLENGTH.')');
}

/* ============================================================================
  global variables
*/
$config=array();

$tablesByVersion=array( // NB the order of tables in these arrays is CRITICAL. they must be consistent across the 0.8 sub-versions
    // we don't offer an upgrade path from 0.6.  Any 0.6 installations should first upgrade to 0.7, then run this routine
    '0.6'     => array('context','goals','maybe','maybesomeday','nextactions','projects','reference','waitingon'),
    // 0.7 is the earliest version that we can upgrade from, here
    '0.7'     => array('categories','checklist','checklistItems','context','goals','itemattributes','items','itemstatus','list','listItems','nextactions','projectattributes','projects','projectstatus','tickler','timeitems'),
    // 0.8rc-1 was a major change, with goals, actions and projects all being merged into the items files
    '0.8rc-1' => array('categories','checklist','checklistItems','context','itemattributes','items','itemstatus','list','listItems','lookup','nextactions','tickler','timeitems','version'),
    // 0.8rc-2 added the preferences table
    '0.8rc-3' => array('categories','checklist','checklistItems','context','itemattributes','items','itemstatus','list','listItems','lookup','nextactions','tickler','timeitems','version','preferences'),
    // 0.8rc-4 saw all table names being standardised in lower case: this meant changing listItems and checklistItems
    '0.8rc-4' => array('categories','checklist','checklistitems','context','itemattributes','items','itemstatus','list','listitems','lookup','nextactions','tickler','timeitems','version','preferences')
    );

$versions=array(
    '0.6'=>     array(  'tables'=>'0.6',
                        'database'=>'0.6',
                        'upgradepath'=>'X'),
    '0.7'=>     array(  'tables'=>'0.7',
                        'database'=>'0.7',
                        'upgradepath'=>'0.7'),
    '0.8rc-1'=> array(  'tables'=>'0.8rc-1',
                        'database'=>'0.8rc-1',
                        'upgradepath'=>'0.8rc-1'),
    '0.8rc-3'=> array(  'tables'=>'0.8rc-3',
                        'database'=>'0.8rc-3',
                        'upgradepath'=>'0.8rc-3'),
    '0.8rc-4'=> array(  'tables'=>'0.8rc-4',
                        'database'=>'0.8rc-4',
                        'upgradepath'=>'copy')
    );

/*
  end of global variables
 ============================================================================*/

// initialise variables used for checking what this run is supposed to do
$areUpdating=false;
$wantToDelete=false;
$areDeleting=false;

require_once('headerHtml.inc.php');
echo "</head><body><div id='container'>";

//temporary warning - remove at release - TOFIX
echo "<h2 class='warning'>This installer is in beta: back up your data before using it!</h2>\n";

if (_DEBUG) echo '<pre>'
	,(_DRY_RUN)?'Executing Dry run - no tables will be amended in this run':'This is a <b>live</b> run'
	,'<br />POST variables: ',print_r($_POST,true),"</pre>\n";

if (isset($_POST['cancel']))
    ; // we've cancelled an over-write or a delete, so go back to the installation menu
elseif (isset($_POST['install'])) {
    $toPrefix=$_POST['prefix'];
    $toDB=$_POST['db'];
    // check to see whether the prefix in config.php hsa been changed between POST and now
    if ($toPrefix===$config['prefix'] && $toDB===$config['db'])
        $areUpdating=true; // ok, it's sfe to update.
    else {
        echo "<p class='error warning'>config.php has changed during the installation process. "
            ," The upgrade cannot continue. Please select your upgrade option again.";
    }
}elseif (_ALLOWUNINSTALL && !isset($_POST['check'])) {
    foreach ($_POST as $thiskey=>$thisval) {
        if (_DEBUG)echo "<p class='debug'>Is $thiskey is a delete key? ";
        if ($thiskey!==($tst=preg_replace('/^Delete_(.*)$/','$1',$thiskey))) {
            if (_DEBUG)echo "Yes</p>\n";
            $wantToDelete=true;
            $versionToDelete=$thisval;
            break;
        }
        if (_DEBUG)echo "No</p>\n";
    }
    if (isset($_POST['delete'])) $areDeleting=true;
}
if (isset($versionToDelete)) {
    $args=explode('=',$versionToDelete);
    $fromPrefix=$args[1];
    $installType=$args[2];
} elseif (isset($_POST['installkey'])) {
    $args=explode('=',$_POST['installkey']);
    $installType=$args[0];
    $fromPrefix=(count($args)>1)?$args[1]:null;
}

if ($areUpdating) {
    if ($fromPrefix===$toPrefix && !isset($_POST['upgrade']))
        getConfirmation('upgrade',$toPrefix);
    else {
        $install_success = false;
        $rollback = array();
    	doInstall($installType,$fromPrefix);
    }
}elseif ($areDeleting)
    deleteInstall($installType,$fromPrefix);
elseif ($wantToDelete)
    getConfirmation('delete',$fromPrefix);
else {
    $checkState='in';
	checkInstall();
}
include_once('footer.php');
return;
/*
   ======================================================================================
   end of main output.


   Function to decide what installation action(s) to offer to the user:
   ======================================================================================
*/
function checkInstall() {
	global $config,$versions,$tablelist,$sort,$checkState,$tablesByVersion;

    register_shutdown_function('failDuringCheck');
	$goodToGo=true; // assume we'll be able to upgrade, until we find something to stop us

    echo "<p>Read the <a href='INSTALL'>INSTALL</a> file for information on using this install/upgrade program</p>\n";

	if (_DEBUG) {
		$included_files = get_included_files();
		echo '<pre>Included files:',print_r($included_files,true),'</pre>';
	}

    if (isset($_GET['warn'])) {
        switch ($_GET['warn']) {
            case 'upgradeneeded':
                echo "<p class='warning'>Your version of the database needs upgrading before we can continue.</p>\n";
                break;
            default:
                break;
        }
    }
    // check the config file
	$checkState='config';
	if (_DEBUG) echo '<p class="debug">Got config.php:</p><pre>',print_r($config,true),'</pre>';
	if (!isset($config['db'])) {
        echo "<p class='warning'>Fatal Error: no valid config.php file has been found. "
            ," you should update the config.php file, based on the config.sample.php "
            ," file supplied with GTD-PHP, before using this installer.</p>\n";
        exit();
    }

	// check to see whether config file is 0.7 or 0.8 style: if the former, warn the installer, and link to documentation
	$configFileIsOld=!(isset($config['firstDayOfWeek']) && is_array($sort));
	if ($configFileIsOld) {
        echo "<p class='warning'>Warning: your config.php file appears to be for "
            ," an earlier version of GTD-PHP! The installation may be able to "
            ," proceed successfully, but you should update the config.php file, "
            ," based on the config.sample.php file supplied, before using the "
            ," package.</p>\n";
	}

	// validate the prefix
	$checkState='prefix';
	if (!checkPrefix($config['prefix'])) exit(); // invalid prefix = fatal error

    // try to open the database
    $checkState='db';
	require_once('headerDB.inc.php');

	// got a database; now get a list of its tables
	$checkState='tables';
    $tablelist=array();
	$tables = mysql_list_tables($config['db']);
	while ($tbl = mysql_fetch_row($tables))
	   array_push($tablelist,$tbl[0]);
	$nt=count($tablelist);
	if (_DEBUG) echo "<pre>Number of tables: $nt<br />",print_r($tablelist,true),"</pre>";

	/*
		Build an array of current installlations,
		and offer choice of upgrading from one of these, or doing a fresh install
	*/
	$checkState='installations';
	$gotVersions=array();
	$destInUse=false;
	$gotPrefixes=(preg_grep("/.*preferences$/",$tablelist));
	if (_DEBUG) echo '<pre>Preference tables:',print_r($gotPrefixes,true),'</pre>';
	foreach ($gotPrefixes as $thisPreference) {
		$thisPrefix=substr($thisPreference,0,-11);
		$thisVer=checkPrefixedTables($thisPrefix);
		if ($thisVer!='') $gotVersions["{$thisVer}={$thisPrefix}"]=$thisVer;
		if ($thisPrefix==$config['prefix']) { // we have an installation already using our target prefix
            $destInUse=true;
            if ($thisVer==_GTD_VERSION) {     // and it's the latest version - so no upgrade needed!
    			// this destination is already in use - let's go!
                require_once('headerMenu.inc.php');
    			echo "<div id='main'>\n<h2>Installed Version is up to date</h2>\n"
                    ,"<p>There is already an installation of "
                    ,_GTD_VERSION," with prefix '{$config['prefix']}'</p>"
                    ,"<p>It's ready for you to <a href='index.php'>start using it.</a></p>\n"
                    ,"<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>\n";
                $goodToGo=false;
                if (_ALLOWUNINSTALL)
                    offerToDeleteOne($thisPrefix,$thisVer);
            } else if (_ALLOWUPGRADEINPLACE || $versions[$thisVer]['database']===$versions[_GTD_VERSION]['database'])
                /* now reset the versions array, and quit this loop,
                   because if we already have an installation with this prefix,
                   we don't want to offer any other kind of upgrade */
                $gotVersions=array("{$thisVer}={$thisPrefix}"=>$thisVer);
            else { // not allowed to upgrade in place, but an upgrade is required, so abort
                $goodToGo=false;
                showNoUpgradeMsg($thisPrefix,$thisVer);
            }
            break;
		}
	}
	$checkState='report';
    // get server information for problem reports
    if ($goodToGo)
        echo "<div id='main'><h1>gtd-php installation/upgrade</h1>\n";

    echo "<h2>Installation Info</h2>\n"
        ,"<p>php: ",phpversion(),"</p>\n"
        ,"<p>mysql: ",mysql_get_server_info(),"</p>\n";

	// check for 0.8rc-1
	if (!$destInUse && !count(array_diff($tablesByVersion['0.8rc-1'],$tablelist)) && checkVersion('')==='0.8rc-1') {
        if ($config['prefix']=='') { // prefixes weren't used in 0.8rc-1, so a blank target prefix means we are trying to upgrade in place, over the top of 0.8rc-1
            if (_ALLOWUPGRADEINPLACE) {
                $destInUse=true;
                /* now reset the versions array, because if we already have an
                 installation with this prefix, we don't want to offer any other
                 kind of upgrade */
                $gotVersions=array('0.8rc-1='=>'0.8rc-1');
            } else {
                $goodToGo=false;
                if (_ALLOWUNINSTALL)
                    offerToDeleteOne($config['prefix'],'0.8rc-1');
                else showNoUpgradeMsg($config['prefix'],'0.8rc-1');
            }
        } else $gotVersions['0.8rc-1=']='0.8rc-1';
    }

    $checkState='v0.7search';
	if (!$destInUse && !count(array_diff($tablesByVersion['0.7'],$tablelist))) {

		if ($config['prefix']=='') {  // prefixes weren't used in 0.7, so a blank target prefix means we are trying to upgrade in place, over the top of 0.8rc-1
            if (_ALLOWUPGRADEINPLACE) {
                $gotVersions=array('0.7'=>'0.7');
                $destInUse=true;
            } else {
                $goodToGo=false;
                if (_ALLOWUNINSTALL)
                    offerToDeleteOne('','0.7');
                else showNoUpgradeMsg('','0.7');
            }
        } else $gotVersions['0.7']='0.7';

		// check to see if there are any tables with that prefix, left over from a failed upgrade
		$temp =  $config['prefix']._TEMPPREFIX;
		$tmptables=array();

		foreach ($tablelist as $table)
            if (strpos($table,$temp)===0)
                $tmptables[]=$table;

        if (count($tmptables)) {
            $gotVersions['0.7']='!';
            $msg="Some temporary files from a previous aborted upgrade of 0.7 have been "
                ." left over and these are preventing you from upgrading the current "
                ." installation of 0.7 to the latest version, using this prefix.";
            if (_ALLOWUNINSTALL) echo showDeleteWarning(true)
                ,"<form action='install.php' method='post'>\n"
                ,"<p class='warning'>$msg<br />\n"
                ,"You can delete these temporary files here:"
                ,makeDeleteButton('temporary','tables')
                ,"<input type='hidden' name='tablesToDelete' value='"
                ,implode(' ',$tmptables)
                ,"'></p></form>\n";
            else echo "<p class='warning'>$msg<br />Change the installation prefix in config.php, or consult your administrator, to fix the problem.</p>\n";
        }

    }
    
    $checkState='v0.6search';
	if (!count(array_diff($tablesByVersion['0.6'],$tablelist))) {
		echo '<p>Found what looks like a version of GTD-PHP earlier than 0.7: this install program cannot upgrade this</p>';
        if ($config['prefix']=='') {   // prefixes weren't used in 0.6, so a blank target prefix means we are trying to upgrade in place, over the top of 0.8rc-1
            $goodToGo=false;
            $destInUse=true;
        }
		$gotVersions['0.6']=true;
	}

	if (_DEBUG) echo '<pre>Versions found: ',print_r($gotVersions,true),"</pre>\n";

	if ($goodToGo) {
		echo '<form action="install.php" method="post">'
			,"\n<h2>Select an upgrade or installation</h2>\n"
			,"<h3>Creating "._GTD_VERSION." installation with "
			,(($config['prefix']=='')?'no prefix':"prefix '{$config['prefix']}'")
			,"</h3>\n";
		if (($destInUse || _ALLOWUNINSTALL) && count($gotVersions)) showDeleteWarning();
		echo "<table summary='table of installation alternatives'>\n"
            ,"<thead><tr><th>Use</th><th>From</th>\n";
        if (_DEBUG) echo "<th class='debug'>name</th>\n";
        if (_ALLOWUNINSTALL && count($gotVersions)) echo "<th class='warning'>Press to delete: no installation will be done; the only action that will be taken is the removal of tables</th>\n";
        echo "</tr></thead><tbody>\n";
		foreach ($gotVersions as $thisKey=>$thisVer) {
			$tmp=explode('=',$thisKey);
			$fromVer=$tmp[0];
			$fromPrefix=$tmp[1];
			$isUpdate= ($fromPrefix==$config['prefix']);
			$action=($fromVer==_GTD_VERSION)?"Copy":'Update';
			$msg="$action current $fromVer installation"
				.(($fromPrefix=='')?' with no prefix':" with prefix $fromPrefix");
            $key=$versions[$fromVer]['upgradepath']."=$fromPrefix";
			echo '<tr>',tabulateOption($thisVer,$key,$msg);
			if (_ALLOWUNINSTALL)
                echo "<td>",makeDeleteButton($fromPrefix,$fromVer),"</td>\n";
            echo "</tr>\n";
		}
		if (!$destInUse) {
            echo "<tr>",tabulateOption('','1',"New install with sample data");
			if (_ALLOWUNINSTALL  && count($gotVersions)) echo "<td>&nbsp;</td>\n";
            echo "</tr>\n<tr>",tabulateOption('','0',"New install with empty database");
			if (_ALLOWUNINSTALL && count($gotVersions)) echo "<td>&nbsp;</td>\n";
			echo "</tr>\n";
        }
		// and finally, close the table
		echo "</tbody></table>\n<div>\n"
            ,"<input type='hidden' name='prefix' value='{$config['prefix']}' />\n"
            ,"<input type='hidden' name='db' value='{$config['db']}' />\n"
            ,"<input type='submit' name='install' value='Install' />\n";
        if ($destInUse)
            echo "<span class='warning'>Warning: this will over-write your current installation! "
                ," Make sure you have a backup of your data first! If you're not sure, "
                ," change the prefix in config.php, to create a new installation, "
                ," rather than over-writing the current one.</span>\n";
        echo "</div>\n</form>\n";
	}
	$checkState='ok';
}
/*
   ======================================================================================
   
   Do an installation / upgrade:
   
   ======================================================================================
*/
function doInstall($installType,$fromPrefix) {
	global $config,$temp,$install_success,$versions,$tablesByVersion;

    require_once("headerDB.inc.php");

	$temp='';
	register_shutdown_function('cleanup');
	if (_DEBUG) echo "<pre>Install type is: $installType<br />Source database has prefix $fromPrefix</pre>";
	echo "<p>Installing ... please wait</p>\n";
	if (version_compare(PHP_VERSION, "4.2.0",'>=')) ob_flush();
	flush();
    switch($installType){
	  case '0': // new install =============================================================================
		create_tables();
		fixLastModifiedColumn();
    	$install_success = true;
       // give some direction about what happens next for the user.
       $endMsg="<h2>Welcome to GTD-PHP</h2>\n
<p>You have just successfully installed GTD-PHP.\n
There are some preliminary steps you should take to set up your\n
installation for use and familiarize yourself with the system.</p>\n
<ol>\n
  <li>You need to set up <a href='editCat.php?field=context&amp;id=0'>spatial</a> and\n
  <a href='editCat.php?field=time-context&amp;id=0'>time contexts</a> that suit your situation.</li>\n
   <li>You need to enter ....</li>\n
   <li></li>\n
   <li></li>\n
</ol>\n";  // TOFIX - add documentation for after a successful installation.
       // end new install
	   break;
	 case '1': // new install with sample data
		create_tables();
		fixLastModifiedColumn();
		create_data();
    	$install_success = true;
       // give some direction about what happens next for the user.
       $endMsg="<h2>Welcome to GTD-PHP</h2>\n
<p>You have just successfully installed GTD-PHP. Sample data has been created as part of the installation.</p>\n
<ol>\n
  <li>Check that the <a href='editCat.php?field=context'>spatial</a> and\n
  <a href='editCat.php?field=time-context'>time contexts</a> suit your situation.</li>\n
</ol>\n";
		break;
	 case 'copy': // already at latest release ============================================================
    	if ($fromPrefix!==$config['prefix']){
			create_tables();
			foreach ($tablesByVersion[$versions[_GTD_VERSION]['tables']] as $table){
				$q = "INSERT INTO ".$config['prefix']. $table . " select * from `". $fromPrefix . $table ."`";
				send_query($q);
			}
			fixData();
			fixLastModifiedColumn();
			updateVersion();
			$install_success = true;
		}
	   $endMsg='<p>Database copied.</p>';
	   break;
	 case '0.8rc-3': // ugprade from 0.8rc-2 or -3    ============================================================
    	if ($fromPrefix===$config['prefix']){ // updating in place
	       $q = "drop table IF EXISTS `{$fromPrefix}version`";
	       send_query($q);
            foreach (array('checklistItems','listItems') as $temptable) {
                $q="rename table `{$config['prefix']}{$temptable}` to `{$config['prefix']}"
                .strtolower($temptable)."`";
                send_query($q);
            }
            amendIndexes();
            createVersion();
            fixAllDates();
        } else {                              // updating to new prefix
			create_tables();
			foreach ($tablesByVersion[$versions[$installType]['tables']] as $key=>$table) if ($table!='version') {
				$q = "INSERT INTO {$config['prefix']}".$tablesByVersion[$versions[_GTD_VERSION]['tables']][$key]
                        . " select * from `$fromPrefix$table`";
				send_query($q);
			}
        	fixAllDates();
			fixLastModifiedColumn();
		}
        $install_success = true;
        $endMsg='<p>GTD-PHP 0.8 upgraded from 0.8rc-3 to '._GTD_VERSION.' - thanks for your beta-testing</p>';
	    break;
	 case '0.8rc-1':  // upgrade from 0.8rc-1 =================================================================
        // if there's a prefix, and there wasn't before, copy tables over
    	if ($fromPrefix==$config['prefix']) {
            foreach (array('checklistItems','listItems') as $temptable) {
                $q="rename table `{$config['prefix']}{$temptable}` to `{$config['prefix']}"
                .strtolower($temptable)."`";
                send_query($q);
            }
    		create_table('preferences');
	   		amendIndexes();
	   		updateVersion();
		} else {
			create_tables();
	   		foreach ($tablesByVersion[$versions[$installType]['tables']] as $table){
				if ($table!="version") {
                    $q = "INSERT INTO {$config['prefix']}".$tablesByVersion[$versions[_GTD_VERSION]['tables']][$key]
                            . " select * from `$fromPrefix$table`";
                    send_query($q);
				}
			}
		}
	    fixAllDates();
		fixLastModifiedColumn();
		$install_success = true;
	    $endMsg='<p>GTD-PHP 0.8 upgraded from 0.8rc-1 to '._GTD_VERSION.' - thanks for your beta-testing</p>';
	    break;
    /*

    ==============================================================================

    */
	  case '0.7': // upgrade from 0.7 =============================================

    	// temp table prefix
		$temp =  _TEMPPREFIX;
		// first check to see if there are any tables with that prefix, and if so,  getConfirmation of deletion first

        // update

        create_table('preferences');

		// categories
		create_table("categories");
		$q="INSERT INTO ".$config['prefix']. $temp . "categories select * from `categories`";
		send_query($q);

		// checklist
		create_table("checklist");
		$q="INSERT INTO ".$config['prefix']. $temp . "checklist  SELECT * FROM `checklist`";
		send_query($q);

		// checklistitems
		create_table("checklistitems");
		$q="INSERT INTO ".$config['prefix']. $temp . "checklistitems  SELECT * FROM `checklistItems`";
		send_query($q);

		// context
		create_table("context");
		$q="INSERT INTO ".$config['prefix']. $temp . "context SELECT * FROM `context`";
		send_query($q);

		// goals
		create_table("goals");
		$q="INSERT INTO ".$config['prefix']. $temp . "goals  SELECT * FROM `goals`";
		send_query($q);

       // itemattributes
       $q="create table `{$config['prefix']}{$temp}itemattributes` (";
       $q.="`itemId` int(10) NOT NULL auto_increment, ";
       $q.="`type` enum('a', 'r', 'w') NOT NULL default 'a' ,";
       $q.="`projectId` int(10) unsigned NOT NULL default '0', ";
       $q.="`contextId` int(10) unsigned NOT NULL default '0', ";
       $q.="`timeframeId` int(10) unsigned NOT NULL default '0', ";
       $q.="`deadline` date default NULL , ";
       $q.="`repeat` int( 10 ) unsigned NOT NULL default '0', ";
       $q.=" `suppress` enum( 'y', 'n' ) NOT NULL default 'n', ";
       $q.="`suppressUntil` int( 10 ) unsigned default NULL , ";
       $q.="PRIMARY KEY ( `itemId` ) , KEY `projectId` ( `projectId` ) ,";
       $q.="KEY `contextId` ( `contextId` ) , ";
       $q.="KEY `suppress` ( `suppress` ) , KEY `type` ( `type` ) ,";
       $q.=" KEY `timeframeId` ( `timeframeId` ) )";

       send_query($q);
       $q="INSERT INTO ".$config['prefix']. $temp . "itemattributes  SELECT * FROM `itemattributes`";
       send_query($q);

       // items
		create_table("items");

       $q="INSERT INTO ".$config['prefix']. $temp . "items (itemId,title,description) SELECT * from `items` ";
       send_query($q);


       $q="CREATE TABLE `{$config['prefix']}{$temp}itemstatus` ( ";
       $q.="`itemId` int( 10 ) unsigned NOT NULL auto_increment ,";
       $q.=" `dateCreated` date default NULL, ";
       $q.="`lastModified` timestamp default '"._DEFAULTDATE."' ,";
       $q.="`dateCompleted` date default NULL , ";
       $q.=" `completed` int( 10 ) unsigned default NULL , ";
       $q.="PRIMARY KEY ( `itemId` ) ) ";

       send_query($q);
       $q="INSERT INTO ".$config['prefix']. $temp . "itemstatus SELECT * FROM `itemstatus`";
       send_query($q);


		create_table('list');

       $q="INSERT INTO ".$config['prefix']. $temp . "list  SELECT * FROM `list` ";
       send_query($q);

		create_table("listitems");

       $q="INSERT INTO ".$config['prefix']. $temp . "listitems SELECT * FROM `listItems`";
       send_query($q);

       $q="CREATE TABLE `{$config['prefix']}{$temp}nextactions` ( ";
       $q.="`projectId` int( 10 ) unsigned NOT NULL default '0', ";
       $q.=" `nextaction` int( 10 ) unsigned NOT NULL default '0', ";
       $q.=" PRIMARY KEY ( `projectId` , `nextaction` ) ) ";
       send_query($q);
       $q="INSERT INTO ".$config['prefix']. $temp . "nextactions SELECT * FROM `nextactions`";
       send_query($q);


       $q="CREATE TABLE `{$config['prefix']}{$temp}projectattributes` ( ";
       $q.="`projectId` int( 10 ) unsigned NOT NULL auto_increment , ";
       $q.=" `categoryId` int( 10 ) unsigned NOT NULL default '1', ";
       $q.="`isSomeday` enum( 'y', 'n' ) NOT NULL default 'n', ";
       $q.=" `deadline` date default NULL , `repeat` int( 11 ) unsigned NOT
       NULL default '0', ";
       $q.="`suppress` enum( 'y', 'n' ) NOT NULL default 'n', ";
       $q.=" `suppressUntil` int( 10 ) unsigned default NULL , PRIMARY KEY (
          `projectId` ) ,";
       $q.=" KEY `categoryId` ( `categoryId` ) , KEY `isSomeday` (
          `isSomeday`) ,";
          $q.="KEY `suppress` ( `suppress` ) ) ";

       send_query($q);
       $q="INSERT INTO ".$config['prefix']. $temp . "projectattributes SELECT * FROM `projectattributes` ";
       send_query($q);

       $q="CREATE TABLE `{$config['prefix']}{$temp}projects` ( ";
       $q.="`projectId` int( 10 ) unsigned NOT NULL auto_increment , ";
       $q.=" `name` text NOT NULL , `description` text, `desiredOutcome` text, ";
       $q.="PRIMARY KEY ( `projectId` ) , "._FULLTEXT." KEY `desiredOutcome` (
          `desiredOutcome`"._INDEXLEN.") , ";
       $q.=_FULLTEXT." KEY `name` ( `name`"._INDEXLEN.") , "._FULLTEXT." KEY `description` (
          `description`"._INDEXLEN.") ) ". _CREATESUFFIX;

       send_query($q);

       $q="INSERT INTO ".$config['prefix']. $temp . "projects SELECT * FROM `projects` ";
       send_query($q);


       $q="CREATE TABLE `{$config['prefix']}{$temp}projectstatus` ( ";
       $q.="`projectId` int( 10 ) unsigned NOT NULL auto_increment ,
       `dateCreated` date  default NULL, `lastModified` timestamp default '"._DEFAULTDATE."' ,
       `dateCompleted` date default NULL , PRIMARY KEY (
          `projectId` ) ) ";

       send_query($q);
       $q="INSERT INTO ".$config['prefix']. $temp . "projectstatus SELECT * FROM
       `projectstatus`";
       send_query($q);


		create_table("tickler");

       $q="INSERT INTO ".$config['prefix']. $temp . "tickler (ticklerId,date,title,note) SELECT * FROM `tickler`";
       send_query($q);

       $q="CREATE TABLE `{$config['prefix']}{$temp}timeitems` ( ";
       $q.="`timeframeId` int( 10 ) unsigned NOT NULL auto_increment , ";
       $q.=" `timeframe` text NOT NULL , `description` text, PRIMARY KEY (
          `timeframeId` ) )" . _CREATESUFFIX;
       send_query($q);

       $q="INSERT INTO ".$config['prefix']. $temp . "timeitems SELECT * FROM
       `timeitems`";
       send_query($q);


		// Dealing with the lookup table
       create_table('lookup');
       $q="INSERT INTO ".$config['prefix'].$temp."lookup (`parentId`,`itemId`) SELECT `projectId`,`itemId`
       FROM `itemattributes`";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix'].$temp."itemattributes DROP `projectId`";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix'].$temp."itemattributes ADD `isSomeday`
       ENUM( 'y', 'n' ) NOT NULL DEFAULT 'n' AFTER `type`, ADD `categoryId`
       INT( 11 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `isSomeday` ";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix'].$temp."itemattributes ADD INDEX (
          `isSomeday` )";
       send_query($q);
       $q="ALTER TABLE ".$config['prefix'].$temp."itemattributes ADD INDEX (
          `categoryId`)";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix'].$temp."itemstatus DROP `completed`";
       send_query($q);
       $q="ALTER TABLE ".$config['prefix'].$temp."itemattributes CHANGE `type`
       `type` ENUM( 'm', 'v', 'o', 'g', 'p', 'a', 'r', 'w', 'i' ) NOT NULL
       DEFAULT 'i'";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix'].$temp."projectattributes ADD `type` ENUM(
          'p' ) NOT NULL DEFAULT 'p' AFTER `projectId`";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix'].$temp."nextactions DROP PRIMARY KEY, ADD
       PRIMARY KEY ( `projectId` , `nextaction`)";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix'].$temp."nextactions CHANGE `projectId`
       `parentId` INT( 10 ) UNSIGNED NOT NULL DEFAULT'0'";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix'].$temp."items ADD `prikey` INT UNSIGNED
       NOT NULL FIRST";
       send_query($q);
       $q="ALTER TABLE ".$config['prefix'].$temp."itemattributes ADD `prikey` INT
       UNSIGNED NOT NULL FIRST";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix'].$temp."itemstatus ADD `prikey` INT
       UNSIGNED NOT NULL FIRST";
       send_query($q);


       $q="ALTER TABLE ".$config['prefix'].$temp."items CHANGE `itemId` `itemId`
       INT( 10 ) UNSIGNED NOT NULL";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix'].$temp."itemattributes CHANGE `itemId`
       `itemId` INT( 10 ) UNSIGNED NOT NULL";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix'].$temp."itemstatus CHANGE `itemId`
       `itemId` INT( 10 ) UNSIGNED NOT NULL";
       send_query($q);

	// migrate actions

$maxnum = " +(
	CASE  WHEN (SELECT MAX(`id`) FROM `goals`) IS NULL THEN 0
		ELSE (SELECT MAX(`id`) FROM `goals`)
	END
	)+(
	CASE  WHEN (SELECT MAX(`projectId`) FROM `projects`) IS NULL THEN 0
		ELSE (SELECT MAX(`projectId`) FROM `projects`)
	END
	)";


		$q=" UPDATE ".$config['prefix'].$temp."items SET `prikey`=`itemId`" . $maxnum;
		send_query($q);

		$q="UPDATE ".$config['prefix'].$temp."itemattributes SET `prikey`=`itemId`" . $maxnum;
		send_query($q);

		$q="UPDATE `".$config['prefix'].$temp."itemstatus` SET `prikey`=`itemId`" . $maxnum;
		send_query($q);

       $q="ALTER TABLE ".$config['prefix'].$temp."items DROP PRIMARY KEY, ADD
       PRIMARY KEY (`prikey`)";
       send_query($q);
       $q="ALTER TABLE ".$config['prefix'].$temp."itemattributes DROP PRIMARY KEY,
       ADD PRIMARY KEY (`prikey`)";
       send_query($q);
       $q="ALTER TABLE ".$config['prefix'].$temp."itemstatus DROP PRIMARY KEY, ADD
       PRIMARY KEY (`prikey`)";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix'].$temp."items DROP `itemId`";
       send_query($q);
       $q="ALTER TABLE ".$config['prefix'].$temp."itemattributes DROP `itemId`";
       send_query($q);
       $q="ALTER TABLE ".$config['prefix'].$temp."itemstatus DROP `itemId`";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix'].$temp."items CHANGE `prikey` `itemId`
       INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'";
       send_query($q);
       $q="ALTER TABLE ".$config['prefix'].$temp."itemattributes CHANGE `prikey`
       `itemId` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'";
       send_query($q);
       $q="ALTER TABLE ".$config['prefix'].$temp."itemstatus CHANGE `prikey`
       `itemId` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'";
       send_query($q);

       $q="DELETE FROM ".$config['prefix'].$temp."nextactions WHERE `nextaction`
       =0";
       send_query($q);

       $q="UPDATE `".$config['prefix'].$temp."nextactions` SET `nextaction`=`nextaction`" . $maxnum;
       send_query($q);

       $q="ALTER TABLE ".$config['prefix'].$temp."lookup ADD `prikey` INT UNSIGNED
       NOT NULL";
       send_query($q);

       $q="UPDATE `".$config['prefix'].$temp."lookup` SET `prikey` =`itemId`" . $maxnum;
       send_query($q);

       $q="ALTER TABLE ".$config['prefix'].$temp."lookup DROP PRIMARY KEY, ADD
       PRIMARY KEY (`parentId` , `prikey`)";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix'].$temp."lookup DROP `itemId`";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix'].$temp."lookup CHANGE `prikey` `itemId`
       INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'";
       send_query($q);

	// Migrate Projects


       $q="INSERT INTO ".$config['prefix'].$temp."items
       (`itemId`,`title`,`description`,`desiredOutcome`) SELECT
       `projectId`,`name`,`description`,`desiredOutcome` FROM `projects`";
       send_query($q);

       $q="INSERT INTO
       ".$config['prefix'].$temp."itemattributes(`itemId`,`type`,`categoryId`,`isSomeday`,`deadline`,`repeat`,`suppress`,`suppressUntil`)
       SELECT
       `projectId`,'p',`categoryId`,`isSomeday`,`deadline`,`repeat`,`suppress`,`suppressUntil`
       FROM `projectattributes`";
       send_query($q);


       $q="INSERT INTO ".$config['prefix'].$temp."itemstatus
       (`itemId`,`dateCreated`, `lastModified`, `dateCompleted`) SELECT
       `projectId`,`dateCreated`, `lastModified`, `dateCompleted` FROM
       `projectstatus`";
       send_query($q);

	// Migrate Goals
$maxnum = "+(
	CASE  WHEN (SELECT MAX(`projectId`) FROM `projects`) IS NULL THEN 0
		ELSE (SELECT MAX(`projectId`) FROM `projects`)
	END
	)";

       $q="ALTER TABLE ".$config['prefix'].$temp."goals ADD `prikey` INT
       UNSIGNED NOT NULL FIRST";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix'].$temp."goals CHANGE `id` `id` INT( 10 )
       UNSIGNED NOT NULL";
       send_query($q);

       $q="UPDATE ".$config['prefix'].$temp."goals SET `prikey`=`id`" . $maxnum;
       send_query($q);

       $q="ALTER TABLE ".$config['prefix'].$temp."goals DROP PRIMARY KEY, ADD
       PRIMARY KEY (`prikey`)";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix'].$temp."goals DROP `id`";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix'].$temp."goals CHANGE `prikey` `id` INT( 10
    ) UNSIGNED NOT NULL DEFAULT '0'";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix'].$temp."timeitems ADD `type` ENUM( 'v',
       'o', 'g', 'p', 'a' ) NOT NULL DEFAULT 'a'";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix'].$temp."timeitems ADD INDEX ( `type` )";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix'].$temp."goals ADD `timeframeId` INT
       UNSIGNED NOT NULL";
       send_query($q);

       $q="UPDATE `".$config['prefix'].$temp."goals` SET `timeframeId`= (1 + (
	CASE  WHEN (SELECT MAX(`timeframeId`) FROM `timeitems`) IS NULL THEN 0
		ELSE (SELECT MAX(`timeframeId`) FROM `timeitems`)
	END
	)) WHERE `type`='weekly'";
       send_query($q);

       $q="UPDATE `".$config['prefix'].$temp."goals` SET `timeframeId`= (2 + (
	CASE  WHEN (SELECT MAX(`timeframeId`) FROM `timeitems`) IS NULL THEN 0
		ELSE (SELECT MAX(`timeframeId`) FROM `timeitems`)
	END
	)) WHERE `type`='quarterly'";
       send_query($q);

       $q="INSERT INTO ".$config['prefix'].$temp."items
       (`itemId`,`title`,`description`) SELECT `id`,`goal`,`description` FROM
       `".$config['prefix'].$temp."goals`";
       send_query($q);

       $q="INSERT INTO ".$config['prefix'].$temp."itemattributes
       (`itemId`,`type`,`timeframeId`,`deadline`) SELECT
       `id`, 'g',`timeframeId`, `deadline` FROM `".$config['prefix'].$temp."goals`";
       send_query($q);

       $q="INSERT INTO ".$config['prefix'].$temp."itemstatus
       (`itemId`,`dateCreated`, `dateCompleted`) SELECT `id`, `created`,
       `completed` FROM `".$config['prefix'].$temp."goals`";
       send_query($q);

       $q="INSERT INTO ".$config['prefix'].$temp."lookup (`parentId`,`itemId`)
       SELECT `projectId`,`id` FROM `goals`";
       send_query($q);

       $q="INSERT INTO ".$config['prefix'].$temp."timeitems ( `timeframeId` ,
       `timeframe` , `description` , `type` ) VALUES (NULL , 'Weekly', NULL,
       'g'), (NULL , 'Quarterly', NULL , 'g')";
       send_query($q);


       $q="ALTER TABLE ".$config['prefix'].$temp."items  ORDER BY `itemId`";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix'].$temp."itemattributes  ORDER BY `itemId`";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix'].$temp."itemstatus  ORDER BY `itemId`";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix'].$temp."itemattributes ADD INDEX (
          `isSomeday`)";
       send_query($q);


       $q="ALTER TABLE ".$config['prefix'].$temp."items CHANGE `itemId` `itemId`
       INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix'].$temp."itemattributes CHANGE `itemId`
       `itemId` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT";
       send_query($q);
       $q="ALTER TABLE ".$config['prefix'].$temp."itemattributes CHANGE `itemId`
       `itemId` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT";
       send_query($q);

        $q="ALTER TABLE ".$config['prefix'].$temp."itemstatus CHANGE `itemId`
        `itemId` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix'].$temp."timeitems ADD "._FULLTEXT." KEY (`timeframe`"._INDEXLEN.")";
       send_query($q);

       $q="ALTER TABLE ".$config['prefix'].$temp."timeitems ADD "._FULLTEXT." KEY (`description`"._INDEXLEN.")";
       send_query($q);

       createVersion();
       foreach (array('projectattributes','projects','projectstatus','goals') as $temptable)
            drop_table($config['prefix'].$temp.$temptable);

       // this is the point of no return - if anything goes wrong after here, the database will be really badly broken!

       if ($config['prefix']=='') {
    		drop_table('waitingOn');        // drop waitingOn, in case left over from 0.6
            foreach ($tablesByVersion[$versions[$installType]['tables']] as $temptable)
                drop_table($temptable);
        }
        foreach ($tablesByVersion[$versions[_GTD_VERSION]['tables']] as $temptable) {
            $q="rename table `{$config['prefix']}{$temp}{$temptable}` to `{$config['prefix']}{$temptable}`";
            send_query($q);
        }
        fixAllDates();
        fixLastModifiedColumn();
		$install_success = true;
		$endMsg='<p>GTD-PHP upgraded from 0.7 to v0.8</p>';
	   break;
    /*
        end of upgrade from v0.7
    =============================================================================

    */
	 default: // no idea what the current installation is ==========================
	 	$endMsg='<p class="error">The install script has not been able to work out'
	 		 .' whether this is an installation, or an upgrade;'
	  		 .' and if the latter, what version we are upgrading from.<br />'
			 .'Note that this installation script cannot upgrade'
			 .' an installation from gtd-php versions earlier than 0.7</p>';
		break;
    } // end of switch

	if ($install_success) {
        require_once('headerMenu.inc.php');
        echo "<div id='main'><p>Installation completed: <a href='index.php'>Let's begin</a></p>\n";
    } else echo "<div id='main'>\n";
	echo $endMsg;
}
/*
   ======================================================================================
   Remove an installation
   ======================================================================================
*/
function deleteInstall($ver,$prefix) {
global $versions,$config,$tablesByVersion;
    require_once('headerDB.inc.php');
	echo "<h1>GTD-PHP - Deleting an installation</h1>\n<div id='main'>\n";
	if (isset($_POST['tablesToDelete'])) {
        echo "<p>Deleting temporary installation tables</p>\n<ol>\n";
        $tablelist=explode(' ',$_POST['tablesToDelete']);
        $prefix='';
	} else {
        echo "<p>Deleting installation version '$ver' with prefix '$prefix'</p>\n<ol>\n";
        $tablelist=$tablesByVersion[$versions[$ver]['tables']];
    }
    foreach ($tablelist as $thistable) {
        echo "<li>Deleting $prefix$thistable</li>\n";
        drop_table($prefix.$thistable);
    }
    echo "</ol><p>Finished - <a href='install.php'>return to install screen</a></p>\n";
}
/*
   ======================================================================================
   various auxiliary functions
   ======================================================================================
*/
function makeDeleteButton($prefix,$ver) {
    global $versions;
    $out="<input class='warning' type='submit' value='Delete=$prefix=$ver' name='Delete_$prefix' />";
    return $out;
}
/*
   ======================================================================================
*/
function offerToDeleteOne($prefix,$ver) {
    echo showDeleteWarning(true)
        ,"<form action='install.php' method='post'><div>\n"
        ,"You can delete the current GTD-PHP data set here:"
        ,makeDeleteButton($prefix,$ver)
        ,"</div></form>\n";
}
/*
   ======================================================================================
*/
function showNoUpgradeMsg($prefix,$ver) {
    echo "<div id='main'>\n"
        ,"<p class='warning'>Found an earlier GTD-PHP installation "
        ," (version $ver) with prefix '$prefix'<br />\n"
        ," The installation options that are currently set do "
        ," not allow you to upgrade the current installation in place: change the "
        ," prefix in config.php to allow you to create a new installation.</p>\n";
}
/*
   ======================================================================================
*/
function fixLastModifiedColumn() {
    global $config;

   $q="UPDATE `{$config['prefix']}itemstatus` SET `lastModified`=`dateCreated` WHERE `lastModified`='"._DEFAULTDATE."'";
   send_query($q,false);
   $q="ALTER TABLE `{$config['prefix']}itemstatus` MODIFY `lastModified`
   timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP";
   send_query($q);
}
/*
   ======================================================================================
*/
function fixDate($tableName,$columnName){
    global $config;
   // change dates of "0000-00-00" to NULL
   # fix date NULL versus 0000-00-00 issue
   $q=" update `{$config['prefix']}{$tableName}` set {$columnName}=NULL where `$columnName`='0000-00-00'";
   send_query($q);
}
/*
   ======================================================================================
*/
function fixAllDates() {
   fixDate('itemattributes','deadline');
   fixDate('itemstatus','dateCompleted');
   fixDate('itemstatus','dateCreated');
   fixDate('itemstatus','lastModified');
   fixDate('listitems','dateCompleted');
   fixDate('tickler','date');
   fixData();
}
/*
   ======================================================================================
*/
function fixData() {
    global $config;

    // remove partial items from database
    $items1=array('items','itemstatus','itemattributes');
    $items2=array('items','itemattributes');
    foreach ($items1 as $t1) {
        foreach ($items2 as $t2) {
            if ($t1!=$t2) {
                $q="DELETE FROM `{$config['prefix']}$t1` WHERE `itemId` NOT IN (SELECT `itemId` FROM `{$config['prefix']}$t2`)";
                send_query($q);
            }
        }
    }
    // it's possible that some legacy items might have no itemstatus: fix that now
    $q="INSERT INTO `{$config['prefix']}itemstatus` (`itemId`)
            SELECT `itemId` from `{$config['prefix']}items` WHERE `itemId` NOT IN
                (SELECT `itemId` FROM `{$config['prefix']}itemstatus`)";
    send_query($q);

    // if tickle flag is set with no deadline, remove tickle flag
    $q="update `{$config['prefix']}itemattributes` set `suppress`='n' where `deadline`=NULL";
    send_query($q);

    // if any titles are blank, call them 'untitled'
    $q="update `{$config['prefix']}items` set `title`='untitled' where `title`=NULL OR `title`=''";
    send_query($q);
    
}

/*
   ======================================================================================
*/
function tabulateOption($prefix,$key,$msg) {
	static $isChecked=' checked="checked" ';
	$result="<td>";
	if ($prefix==='!')
        $result.="X";
	else {
	   $result.="<input type='radio' name='installkey' value='$key' $isChecked />";
	   $isChecked='';
    }
    $result.="</td><td>$msg</td>"
        .((_DEBUG)?"<td class='debug'>Option: $key</td>":"")
        ."\n";
    
	return $result;
}
/*
   ======================================================================================
*/
function checkPrefix($prefix) {
	// check that the proposed prefix is valid for a gtd-php installation.
	if (_DEBUG) echo '<p class="debug">Validating prefix '."'{$prefix}'</p>\n";
	$prefixOK=preg_match("/^[-_a-zA-Z0-9]*$/",$prefix);
	if (!$prefixOK)
		echo '<p class="error">Prefix "',$prefix, '" is invalid - change config.php.'
			 ," The only valid characters are numbers, letters, _ (underscore) and - (hyphen):"
             ," for maximum compatibility and portability, we recommend using no upper case letters</p>\n";

	return $prefixOK;
}
/*
   ======================================================================================
*/
function checkTables($prefix,$ver) {
	global $versions,$tablelist,$tablesByVersion;
	$doneOK=true;
   	foreach ($tablesByVersion[$versions[$ver]['tables']] as $thisTable)
		if (!in_array($prefix.$thisTable,$tablelist,true)) {
			$doneOK=false;
			break;
		}
    return $doneOK;
}
/*
   ======================================================================================
*/
function checkPrefixedTables($prefix) {
	global $versions;
	if (_DEBUG) echo '<p class="debug">Is there a current 0.8 installation with prefix "',$prefix,'"? ';
	foreach (array('0.8rc-3','0.8rc-4') as $ver) {
        $doneOK=checkTables($prefix,$ver);
        if ($doneOK) {
            $retval=$ver;
            break;
        }
    }
	if (!$doneOK) $retval=false;
    if (_DEBUG) echo (($doneOK)?'YES':'NO'),"</p><p class='debug'>Resolved version number as: '$retval'</p>\n";
	return $retval;
}
/*
   ======================================================================================
*/
function checkVersion($prefix) {
	$q="SELECT `version` from `{$prefix}version`";
	$result = mysql_query($q);
	if ($result) {
		$row = mysql_fetch_row($result);
		if (_DEBUG) echo '<p class="debug">Found Version field: "',$row[0],'"</p>';
		$retval=$row[0];
	} else
		$retval='0.8rc-2';
	
    return $retval;
}
/*
   ======================================================================================
*/
function getExistingDestinationTables($prefix) {
	global $tablelist,$versions,$tablesByVersion;
	if (_DEBUG) echo '<p class="debug">Checking availability of destination prefix "{$prefix}"</p>';
	$destInUse=array();
	foreach ($tablesByVersion[$versions[_GTD_VERSION]['tables']] as $thisTable)
   		if (count(array_keys($prefix.$thisTable,$tablelist,true)))
			array_push($destInUse,$prefix.$thisTable);
	return $destInUse;
}
/*
   ======================================================================================
*/
function create_tables() {
	global $config,$install_success;
    // start creating new tables
	create_table('preferences');
	create_table("categories");
	create_table("checklist");
	create_table("checklistitems");
	create_table("context");
	create_table("itemattributes");
	create_table("items");
	create_table("itemstatus");
	create_table('list');
	create_table("listitems");
	create_table('lookup');
	create_table("nextactions");
	create_table("tickler");
	create_table("timeitems");
	createVersion();
}
/*
   ======================================================================================
*/
function amendIndexes() {
    global $config;
    $indexarray=array(
        'categories'    =>array('category','description'),
        'checklist'     =>array('description','title'),
        'checklistitems'=>array('notes','item'),
        'context'       =>array('name','description'),
    	'items'         =>array('title','desiredOutcome','description'),
    	'list'          =>array('description','title'),
    	'listitems'     =>array('notes','item'),
    	'tickler'       =>array('note','title'),
    	'timeitems'     =>array('timeframe','description') );

    $q="ALTER TABLE {$config['prefix']}tickler DROP INDEX `notes`";
    send_query($q,false);

    foreach ($indexarray as $table=>$indexes) {
        foreach ($indexes as $col) {
            $q="ALTER TABLE {$config['prefix']}$table DROP INDEX `$col`";
            send_query($q,false);
            $q="ALTER TABLE {$config['prefix']}$table ADD INDEX "._FULLTEXT." `$col` (`$col`"._INDEXLEN.")";
            send_query($q);
        }
    }
}
/*
   ======================================================================================
*/
function drop_table($name){
	global $rollback;
	$q = "drop table if exists `$name`";
    send_query($q);
    unset($rollback[$name]);
}
/*
   ======================================================================================
*/
function send_query($q,$dieOnFail=true) {
    global $rollback;
   	if (_DEBUG) echo "<p class='debug'>{$q}</p>\n";
    if (_DRY_RUN)
        $result=true;
    else
		$result = mysql_query($q);

    if ($result) {
        if (_DEBUG) echo "<p class='debug'>",mysql_affected_rows()," rows affected</p>\n";
        if (stristr($q,'create table')!==FALSE) {
            $tmp=explode('`',$q);
            $newfile=$tmp[1];
            $rollback[$newfile] = "DROP TABLE IF EXISTS `$newfile`";
        } elseif (stristr($q,'rename table')!==FALSE) {
            $tmp=explode('`',$q);
            $oldfile=$tmp[1];
            $newfile=$tmp[3];
            $rollback[$newfile] = "DROP TABLE IF EXISTS `$newfile`";
            unset($rollback[$oldfile]);
        }
    } else {
        if($dieOnFail) {
            echo "<p class='error'>Fatal error: Failed to do MySQL query: '$q'<br />",mysql_error(),"</p>\n";
            die("<p class='error'>Installation terminated</p>");
        }elseif (_DEBUG)
            echo "<p class='warning debug'>Warning: Failed to do MySQL query: '$q'<br />",mysql_error(),"</p>\n";
    }
}
/*
   ======================================================================================
*/
function createVersion()  {
    global $config,$temp;
    create_table('version');
    $q="INSERT INTO `{$config['prefix']}{$temp}version` (`version`) VALUES";
    $q.=" ('"._GTD_VERSION."')";
    send_query($q);
}
/*
   ======================================================================================
*/
function updateVersion() {
    global $config;
    $q="UPDATE `{$config['prefix']}version` SET `version`='"._GTD_VERSION."'";
    send_query($q,false);
}
/*
   ======================================================================================
*/
function cleanup($message='cleaning up the mess') {
	global $rollback,$install_success;
	if ($install_success) return $message;

	foreach($rollback as $query) send_query($query,false);
	echo "<p class='error'>Installation aborted, and cleanup done - <a href='install.php'>return to main install screen</a></p></div></body></html>";
	return $message;
}
/*
   ======================================================================================
*/
function showDeleteWarning($noPrint=false) {
    static $alreadyShown=false;
    if ($alreadyShown)
        return '';
    else
        $alreadyShown=true;

    if (_ALLOWUPGRADEINPLACE || _ALLOWUNINSTALL) {
        $outStr="<p class='warning'>Warning: ";
        if (_ALLOWUNINSTALL) {
            $outStr.=' deletions ';
            if (_ALLOWUPGRADEINPLACE) $outStr.=' and ';
        }
        if (_ALLOWUPGRADEINPLACE) $outStr.=' over-writing ';
        $outStr.=" cannot be undone.<br /> \n"
            ." Make sure that any information you are about to remove is backed up "
            ." somewhere, and that you are sure it belongs to GTD-PHP and to you.</p>\n";
    } else $outStr='';
    if (!$noPrint) echo $outStr;
    return $outStr;
}
/*
   ======================================================================================
*/
function getConfirmation($action,$prefix) {
    
    echo "<h1>GTD-PHP Installation</h1>\n"
        ,showDeleteWarning(true)
        ,"<div id='main'><form action='install.php' method='post'><div>\n"
        ,"<p class='warning'>\n Are you sure you wish to $action the installation "
        ," with prefix '$prefix'? This action is irreversible.</p>\n";
    foreach ($_POST as $var=>$val)
        echo "<input type='hidden' name='$var' value='$val' />\n";
    echo "<input type='submit' name='$action' value='Continue' />\n"
        ,"<input type='submit' name='cancel' value='Cancel' />\n"
        ,"</div></form>\n";
}
/*
   ======================================================================================
*/
function failDuringCheck() {
    global $checkState,$config;
    switch ($checkState) {
        case 'ok':return; // reached end ok, so nothing to do
        case 'in': // barely started
            echo "<p class='error'>Unable to start the installation pre-flight checks</p>";
            break;
        case 'config': // no valid config.php
    		echo "<p class='error'>No valid config.php file found.<br />"
    			,"Copy the config.sample.php file to config.php, and set the MySQL parameters</p>\n";
    		// TOFIX - link to config.php documentation here

            break;
        case 'db': // failed during attempt to open database
            echo "<p class='error'>";
            if ($config['db']=='')
                echo "No database name was found in the config.php file: you will need to add the "
                    ," database name, database user name, and password, to the config.php file ";
            else
                echo "Please check your config.php file. It's currently set to use the '{$config['db']}' MySQL database."
                ," If that is the correct name, it may be that the database is not yet created, "
                ," or that the database user name or password in the config.php file are incorrect."
                ," Either create the database, adjust the user permissions, or set the username and password correctly,";
                
            echo " (contact your administrator if you don't know how to do this)"
                ," and then return to this page.</p>\n";
            break;
        case 'tables':
            echo "<p class='error'>Failed to get a list of the current tables in the database: check your MySQL database structure</p>\n";
            break;
        case 'prefix':
            echo "<p class='error'>Change the prefix value in config.php file, then return to this page</p>\n";
            break;
        case 'installations':
            echo "<p class='error'>Failed while examining current installations</p>\n";
            break;
        case 'report':
            echo "<p class='error'>Failed while producing table of installation options</p>\n";
            break;
        default: // failed some other time
            echo "<p class='error'>Failed during check, at the '$checkState' stage</p>\n";
            break;
    }
    echo "</div></body></html>";
}
/*
   ======================================================================================
     Table Creation Queries
   ======================================================================================
*/

function create_table ($name) {
	global $config, $temp;
	$tablename=$config['prefix'].$temp.$name;
    $q="CREATE TABLE `$tablename` (";
	switch ($name) {
	case "categories":
       $q.="`categoryId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`category` text NOT NULL, ";
       $q.="`description` text, ";
       $q.="PRIMARY KEY  (`categoryId`), ";
       $q.=_FULLTEXT." KEY `category` (`category`"._INDEXLEN."), ";
       $q.=_FULLTEXT." KEY `description` (`description`"._INDEXLEN."))"._CREATESUFFIX;
    break;
    case "checklist":
       $q.="`checklistId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`title` text NOT NULL, ";
       $q.="`categoryId` int(10) unsigned NOT NULL default '0', ";
       $q.="`description` text, ";
       $q.="PRIMARY KEY  (`checklistId`),    ";
       $q.=_FULLTEXT." KEY `description` (`description`"._INDEXLEN."), ";
       $q.=_FULLTEXT." KEY `title` (`title`"._INDEXLEN."))"._CREATESUFFIX;
	break;
	case "checklistitems":
       $q.="`checklistItemId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`item` text NOT NULL, ";
       $q.="`notes` text, ";
       $q.="`checklistId` int(10) unsigned NOT NULL default '0', ";
       $q.="`checked` enum ('y', 'n') NOT NULL default 'n', ";
       $q.="PRIMARY KEY (`checklistItemId`), KEY `checklistId` (`checklistId`),";
       $q.=_FULLTEXT." KEY `notes` (`notes`"._INDEXLEN."), "._FULLTEXT." KEY `item` (`item`"._INDEXLEN."))"._CREATESUFFIX;
    break;
	case "itemattributes";
       $q.="`itemId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`type` enum ('m','v','o','g','p','a','r','w','i') NOT NULL default 'i', ";
       $q.="`isSomeday` enum('y','n') NOT NULL default 'n', ";
       $q.="`categoryId` int(11) unsigned NOT NULL default '0', ";
       $q.="`contextId` int(10) unsigned NOT NULL default '0', ";
       $q.="`timeframeId` int(10) unsigned NOT NULL default '0', ";
       $q.="`deadline` date default NULL, ";
       $q.="`repeat` int(10) unsigned NOT NULL default '0', ";
       $q.="`suppress` enum('y','n') NOT NULL default 'n', ";
       $q.="`suppressUntil` int(10) unsigned default NULL, ";
       $q.="PRIMARY KEY (`itemId`), ";
       $q.="KEY `contextId` (`contextId`), ";
       $q.="KEY `suppress` (`suppress`), ";
       $q.="KEY `type` (`type`), ";
       $q.="KEY `timeframeId` (`timeframeId`), ";
       $q.="KEY `isSomeday` (`isSomeday`),    ";
       $q.="KEY `categoryId` (`categoryId`),  ";
       $q.="KEY `isSomeday_2` (`isSomeday`))";
	break;
    case "context":
       $q.="`contextId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`name` text NOT NULL, ";
       $q.="`description` text, ";
       $q.="PRIMARY KEY  (`contextId`), ";
       $q.=_FULLTEXT." KEY `name` (`name`"._INDEXLEN."), ";
       $q.=_FULLTEXT." KEY `description` (`description`"._INDEXLEN."))"._CREATESUFFIX;
	break;
	case "items":
       $q.="`itemId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`title` text NOT NULL, ";
       $q.="`description` longtext, ";
       $q.="`desiredOutcome` text, ";
       $q.="PRIMARY KEY  (`itemId`), ";
       $q.=_FULLTEXT." KEY `title` (`title`"._INDEXLEN."), ";
       $q.=_FULLTEXT." KEY `desiredOutcome` (`desiredOutcome`"._INDEXLEN."), ";
       $q.=_FULLTEXT." KEY `description` (`description`"._INDEXLEN."))"._CREATESUFFIX;
	break;
	case "itemstatus":
       $q.="`itemId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`dateCreated` date  default NULL, ";
       $q.="`lastModified` timestamp default '"._DEFAULTDATE."' ,";
       $q.="`dateCompleted` date default NULL, ";
       $q.="PRIMARY KEY  (`itemId`))";
	break;
	case "list":
       $q.="`listId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`title` text NOT NULL, ";
       $q.="`categoryId` int(10) unsigned NOT NULL default '0', ";
       $q.="`description` text, ";
       $q.="PRIMARY KEY  (`listId`), ";
       $q.="KEY `categoryId` (`categoryId`), ";
       $q.=_FULLTEXT." KEY `description` (`description`"._INDEXLEN."), ";
       $q.=_FULLTEXT." KEY `title` (`title`"._INDEXLEN.")) "._CREATESUFFIX;
	break;
	case "listitems":
       $q.="`listItemId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`item` text NOT NULL, ";
       $q.="`notes` text, ";
       $q.="`listId` int(10) unsigned NOT NULL default '0', ";
       $q.="`dateCompleted` date default NULL, ";
       $q.="PRIMARY KEY  (`listItemId`), ";
       $q.="KEY `listId` (`listId`), ";
       $q.=_FULLTEXT." KEY `notes` (`notes`"._INDEXLEN."), ";
       $q.=_FULLTEXT." KEY `item` (`item`"._INDEXLEN.")) "._CREATESUFFIX;
	break;
	case "tickler":
       $q.="`ticklerId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`date` date  default NULL, ";
       $q.="`title` text NOT NULL, ";
       $q.="`note` longtext, ";
       $q.="`repeat` int(10) unsigned NOT NULL default '0', ";
       $q.="`suppressUntil` int(10) unsigned NOT NULL default '0', ";
       $q.="PRIMARY KEY  (`ticklerId`), ";
       $q.="KEY `date` (`date`), ";
       $q.=_FULLTEXT." KEY `note` (`note`"._INDEXLEN."), ";
       $q.=_FULLTEXT." KEY `title` (`title`"._INDEXLEN.")) "._CREATESUFFIX;
	break;
	case "goals":
       $q.="`id` int(11) NOT NULL auto_increment, ";
       $q.="`goal`   longtext, ";
       $q.="`description`   longtext, ";
       $q.="`created` date default NULL, ";
       $q.="`deadline` date default NULL, ";
       $q.="`completed` date default NULL, ";
       $q.="`type` enum('weekly', 'quarterly') default NULL ,";
       $q.="`projectId` int(11) default NULL, PRIMARY KEY (`id`) )";
	break;
	case "lookup":
       $q.="`parentId` int(11) NOT NULL default '0', ";
       $q.="`itemId` int(10) unsigned NOT NULL default '0', ";
       $q.="PRIMARY KEY  (`parentId`,`itemId`) )";
    break;
	case "preferences":
       $q.="`id`  int(10) unsigned NOT NULL auto_increment, ";
       $q.="`uid` int(10)  NOT NULL default '0', ";
       $q.="`option`  text, ";
       $q.="`value`  text, ";
       $q.="PRIMARY KEY  (`id`)); ";
	break;
	case "nextactions":
       $q.="`parentId` int(10) unsigned NOT NULL default '0', ";
       $q.="`nextaction` int(10) unsigned NOT NULL default '0', ";
       $q.="PRIMARY KEY  (`parentId`,`nextaction`))";
	break;
	case "timeitems":
       $q.="`timeframeId` int(10) unsigned NOT NULL auto_increment, ";
       $q.="`timeframe` text NOT NULL, ";
       $q.="`description` text, ";
       $q.="`type` enum('v','o','g','p','a') NOT NULL default 'a', ";
       $q.="PRIMARY KEY  (`timeframeId`), ";
       $q.="KEY `type` (`type`), ";
       $q.=_FULLTEXT." KEY `timeframe` (`timeframe`"._INDEXLEN."), ";
       $q.=_FULLTEXT." KEY `description` (`description`"._INDEXLEN."))"._CREATESUFFIX;
    break;
	case "version":
       $q.="`version` text NOT NULL, ";
       $q.="`updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update ";
       $q.=" CURRENT_TIMESTAMP)";
    break;
    default:
    break;
    }
    send_query($q);
}
/*
   ======================================================================================
*/
function create_data() {
	global $config;
	// a load of inserts here to create the sample data
}
/*
   ======================================================================================
*/

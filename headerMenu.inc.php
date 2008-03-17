<?php
require_once("headerDB.inc.php");
/*
   ----------------------------------------------
   first, define the default menus
*/
$menu=array();

$menu[] = array("link"=>'','label'=>'Capture');
//-------------------------------------------
$menu[] = array("link"=>"item.php?type=i", 'title'=>"Drop an item into the inbox", 'label' => "Inbox item");
$menu[] = array("link"=>"item.php?type=a&amp;nextonly=true", 'title'=>"Create a new next action", 'label' => "Next Action");
$menu[] = array("link"=>"item.php?type=a", 'title'=>"Create a new action", 'label' => "Action");
$menu[] = array("link"=>"item.php?type=w", 'title'=>"Create a new waiting on item", 'label' => "Waiting On");
$menu[] = array("link"=>"item.php?type=r", 'title'=>"Create a reference", 'label' => "Reference");
$menu[] = array("link"=>"item.php?type=p", 'title'=>"Create a new project", 'label' => "Project");
$menu[] = array("link"=>"item.php?type=p&amp;someday=true", 'title'=>"Create a future project", 'label' => "Someday/Maybe");
$menu[] = array("link"=>'','label'=>'separator');
$menu[] = array("link"=>"item.php?type=g", 'title'=>"Define a new goal", 'label' => "Goal");
$menu[] = array("link"=>"item.php?type=o", 'title'=>"Define a new role", 'label' => "Role");
$menu[] = array("link"=>"item.php?type=v", 'title'=>"Define a new vision", 'label' => "Vision");
$menu[] = array("link"=>"item.php?type=m", 'title'=>"Define a new value", 'label' => "Value");
/*
   ----------------------------------------------
*/
$menu[] = array("link"=>'','label'=>'Process');
//-------------------------------------------
$menu[] = array("link"=>"listItems.php?type=i", 'title'=>"Process inbox", 'label' => "Inbox");
$menu[] = array("link"=>"listItems.php?type=a&amp;nextonly=true", 'title'=>"Next actions", 'label' => "Next Actions");
$menu[] = array("link"=>"listItems.php?type=a", 'title'=>"Process actions", 'label' => "Actions");
$menu[] = array("link"=>"listItems.php?type=w", 'title'=>"Process waiting-ons", 'label' => "Waiting On");
$menu[] = array("link"=>"listItems.php?type=r", 'title'=>"Process references", 'label' => "References");
$menu[] = array("link"=>"listItems.php?type=p", 'title'=>"Process projects", 'label' => "Projects");
$menu[] = array("link"=>"listItems.php?type=p&amp;someday=true", 'title'=>"Process Someday projects", 'label' => "Someday/Maybes");
$menu[] = array("link"=>'','label'=>'separator');
$menu[] = array("link"=>"reportContext.php", 'title'=>"Process actions sorted by space context", 'label' => "Actions in context");
$menu[] = array("link"=>"index.php", 'title'=>"Summary view", 'label' => "Summary");
$menu[] = array("link"=>"listItems.php?quickfind", 'title'=>'Find an item based on text in its title, description or outcome', 'label'=>'Quick Find');
/*
   ----------------------------------------------
*/
$menu[] = array("link"=>'','label'=>'Review');
//-------------------------------------------
$menu[] = array("link"=>"weekly.php", 'title'=>"Steps in the Weekly Review", 'label' => "Weekly Review");
$menu[] = array("link"=>"orphans.php", 'title'=>"List items without a parent item", 'label' => "Orphaned Items");
$menu[] = array("link"=>"listItems.php?type=a&amp;tickler=true", 'title'=>"Hidden items and reminders", 'label' => "Tickler File");
$menu[] = array("link"=>'','label'=>'separator');
$menu[] = array("link"=>"listItems.php?type=g", 'title'=>"Review goals", 'label' => "Goals");
$menu[] = array("link"=>"listItems.php?type=o", 'title'=>"Review roles / Areas of Responsibility", 'label' => "Roles");
$menu[] = array("link"=>"listItems.php?type=v", 'title'=>"Review visions", 'label' => "Visions");
$menu[] = array("link"=>"listItems.php?type=m", 'title'=>"Review values / Mission", 'label' => "Values");
/*
   ----------------------------------------------
*/
$menu[] = array("link"=>'','label'=>'Lists');
//-------------------------------------------
$menu[] = array("link"=>"editLists.php?type=L", 'title'=>"Create a general purpose list", 'label' => "New List");
$menu[] = array("link"=>"listLists.php?type=L", 'title'=>"Show general-purpose lists", 'label' => "Show Lists");
$menu[] = array("link"=>'','label'=>'separator');
$menu[] = array("link"=>"editLists.php?type=C", 'title'=>"Create a reusable list", 'label' => "New Checklist");
$menu[] = array("link"=>"listLists.php?type=C", 'title'=>"Show reusable checklists", 'label' => "Show Checklists");
/*
   ----------------------------------------------
*/
$menu[] = array("link"=>'','label'=>'Configure');
//-------------------------------------------
$menu[] = array("link"=>"editCat.php?field=category", 'title'=>"Edit Meta-categories", 'label' => "Categories");
$menu[] = array("link"=>"editCat.php?field=context", 'title'=>"Edit spatial contexts", 'label' => "Space Contexts");
$menu[] = array("link"=>"editCat.php?field=time-context", 'title'=>"Edit time contexts", 'label' => "Time Contexts");
$menu[] = array("link"=>'','label'=>'separator');
$menu[] = array("link"=>"preferences.php", 'title'=>"User preferences", 'label' => "User Preferences");
if ($config['showAdmin'])
    $menu[] = array("link"=>"admin.php", 'title'=>"Administration", 'label' => "Admin");
/*
   ----------------------------------------------
*/
$menu[] = array("link"=>'','label'=>'Help');
//-------------------------------------------
$newbuglink="https://www.hosted-projects.com/trac/toae/gtdphp/newticket";
if (!$config['withholdVersionInfo']) $newbuglink.='?milestone='._GTDPHP_VERSION.'&amp;description='
    .urlencode('gtd-php='._GTD_REVISION.' , GTD-db='._GTD_VERSION
    .' , PHP='.PHP_VERSION.' , Database='.getDBVersion()
    );
$menu[] = array("link"=>"http://www.gtd-php.com/Users/Documentation", 'title'=>"Documentation", 'label' => "Helpfile Wiki");
$menu[] = array("link"=>$newbuglink, 'title'=>"Report a bug on the gtd-php trac system", 'label' => "Report a bug");
$menu[] = array("link"=>"listkeys.php", 'title'=>"List the shortcut keys", 'label' => "Show shortcut keys");
$menu[] = array("link"=>"http://toae.org/boards", 'title'=>"Help and development discussions", 'label' => "Support Forum");
$menu[] = array('link'=>'http://www.gtd-php.com/Developers/Contrib','title'=>'User-contributed enhancements','label'=>'Themes and add-ons');
$menu[] = array("link"=>"https://www.hosted-projects.com/trac/toae/gtdphp", 'title'=>"Bug tracking and project development", 'label' => "Developers' wiki");
$menu[] = array("link"=>"http://www.frappr.com/gtdphp", 'title'=>"Tell us where you are", 'label' => "Frappr Map");
if ($config['debug'] & _GTD_DEBUG) {
    $menu[] = array("link"=>"https://www.hosted-projects.com/trac/toae/gtdphp/log?action=stop_on_copy&amp;rev="
        ._GTD_REVISION."&amp;stop_rev=411&amp;mode=follow_copy&amp;verbose=on"
        ,'title'=>'Changelog (requires trac login)', 'label'=>'Changelog');
}
$menu[] = array("link"=>'','label'=>'separator');
$menu[] = array("link"=>"donate.php", 'title'=>"Help us defray our costs", 'label' => "Donate");
$menu[] = array("link"=>"credits.php", 'title'=>"The GTD-PHP development team", 'label' => "Credits");
$menu[] = array("link"=>"license.php", 'title'=>"The GTD-PHP license", 'label' => "License");
$menu[] = array("link"=>"version.php", 'title'=>"Version information", 'label' => "Version");
/*
   ----------------------------------------------
        now process addons
*/
if (!empty($config['addons'])) foreach ($config['addons'] as $addonid=>$thisaddon) {
    $url=$thisaddon['where'];
    foreach ($menu as $key=>$line) {
        if ($url!==$line['link']) continue;
        switch ($thisaddon['when']) {
            case 'before':
                $offset=$key;
                $length=0;
                break;
            case 'replace':
                $offset=$key;
                $length=1;
                break;
            case 'after': // deliberately flows through to default
            default:
                $offset=$key+1;
                $length=0;
                break;
        }
        unset($thisaddon['where']);
        unset($thisaddon['when']);
        $thisaddon['link']="addon.php?addonid=$addonid";
        array_splice($menu,$offset,$length,array($thisaddon));
        break;
    }
}
/*
   ----------------------------------------------
        finally, echo out the menus
*/
?>
<div id="header">
	<h1 id='sitename'><a href='index.php'><?php echo $config['title'];?></a></h1>
</div>
<div id="menudiv">
	<ul id="menulist">
    <?php
    $class=$menuend='';
    foreach ($menu as $index=>$line) {
        if (empty($line['link'])) {
            if ($line['label']==='separator') {
                $class=" class='menuseparator' ";
            } else {
                echo "$menuend<li>{$line['label']}<ul>\n";
                $menuend="</ul></li>\n";
            }
        } else {
            if (empty($acckey[$line['link']]))
                $accesskey=$keypress='';
            else {
                $menu[$index]['key']=$acckey[$line['link']];
                $keypress=" ({$acckey[$line['link']]})";
                $accesskey=" accesskey='{$acckey[$line['link']]}'";
            }
	        echo "<li$class>\n"
                ,"<a href='{$line['link']}' title='{$line['title']}' $accesskey>"
                ,"{$line['label']}$keypress</a></li>\n";
            $class='';
        }
    }
    echo $menuend;
    ?>
	</ul>
</div>

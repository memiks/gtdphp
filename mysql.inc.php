<?php

//query listing for MySQL database
//API Documentation available in __________
//NB: All queries written to operate on single table for compatibility with other databases
//MySQL extensions to standard SQL have been avoided where known
//Queries may be rewritten in future as SQL evolves and as other database types are supported

//GENERAL RULES:
//"select" = query for something by its id; a single-row result
//"get" = query for something of a particular type; a multi-row result
//"new", "update", "delete" are self-explanatory
//"check"="complete" for checklistselectbox
//"complete" = set status to completed
//"remove" = remove by association Id (items associated with a project, etc)
//"Count" = # of a particular type in table
//"selectbox" = get results to create a selectbox- for assignment or filter

$sql = array(
        "categoryselectbox"         => "SELECT `categories`.`categoryId`, `categories`.`category`, `categories`.`description` FROM `categories` ORDER BY {$sort['categoryselectbox']}",
        "checkchecklistitem"        => "UPDATE `checklistItems` SET `checked` = 'y' WHERE `checklistItemId`='{$values['Cli']}'",
        "checklistselectbox"        => "SELECT `checklist`.`checklistId`, `checklist`.`title`, `checklist`.`description`, `checklist`.`categoryId`, `categories`.`category` FROM `checklist`, `categories` WHERE `checklist`.`categoryId`=`categories`.`categoryId` ORDER BY {$sort['checklistselectbox']}",
        "clearchecklist"            => "UPDATE `checklistItems` SET `checked` = 'n' WHERE `checklistId` = '{$values['checklistId']}'",
        "completeitem"              => "UPDATE `itemstatus` SET `dateCompleted`='{$values['date']}' WHERE `itemId`='{$values['completedNa']}'",
        "completelistitem"          => "UPDATE `listItems` SET `dateCompleted`='{$values['date']}' WHERE `listItemId`='{$values['completedLi']}'",
        "copynextaction"            => "INSERT INTO `nextactions` (`parentId`,`nextaction`) VALUES ('{$values['parentId']}','{$values['newitemId']}') ON DUPLICATE KEY UPDATE `nextaction`='{$values['newitemId']}'",
        "countitems"                => "SELECT `type`, COUNT(*) AS nitems FROM `itemattributes`, `itemstatus` WHERE `itemattributes`.`itemId`=`itemstatus`.`itemId` ".$values['filterquery']." GROUP BY `type`",
        "countnextactions"          => "SELECT COUNT(`nextaction`) AS nnextactions FROM `nextactions`",
        "countcontextreport_naonly" => "SELECT `itemattributes`.`contextId`, `itemattributes`.`timeframeId`, COUNT(*) AS count FROM `itemattributes`, `itemstatus`, `nextactions` WHERE `itemstatus`.`itemId`=`itemattributes`.`itemId` AND  `nextactions`.`nextaction` = `itemstatus`.`itemId`AND `itemattributes`.`isSomeday`='n' AND (`itemstatus`.`dateCompleted` IS NULL OR `itemstatus`.`dateCompleted`='0000-00-00') GROUP BY `itemattributes`.`contextId`, `itemattributes`.`timeframeId`",
        "countcontextreport_all"    => "SELECT `itemattributes`.`contextId`, `itemattributes`.`timeframeId`, COUNT(*) AS count FROM `itemattributes`, `itemstatus` WHERE `itemstatus`.`itemId`=`itemattributes`.`itemId` AND `itemattributes`.`type`='a' AND `itemattributes`.`isSomeday`='n' AND (`itemstatus`.`dateCompleted` IS NULL OR `itemstatus`.`dateCompleted`='0000-00-00')  GROUP BY `itemattributes`.`contextId`, `itemattributes`.`timeframeId`",
        "countspacecontexts"        => "SELECT COUNT(`name`) AS ncontexts FROM `context`",
        "deletecategory"            => "DELETE FROM `categories` WHERE `categoryId`='{$values['categoryId']}'",
        "deletechecklist"           => "DELETE FROM `checklist` WHERE `checklistId`='{$values['checklistId']}'",
        "deletechecklistitem"       => "DELETE FROM `checklistItems` WHERE `checklistItemId`='{$values['checklistItemId']}'",
        "deleteitem"                => "DELETE FROM `items` WHERE `itemId`='{$values['itemId']}'",
        "deleteitemattributes"      => "DELETE FROM `itemattributes` WHERE `itemId`='{$values['itemId']}'",
        "deleteitemstatus"          => "DELETE FROM `itemstatus` WHERE `itemId`='{$values['itemId']}'",
        "deletelist"                => "DELETE FROM `list` WHERE `listId`='{$values['listId']}'",
        "deletelistitem"            => "DELETE FROM `listItems` WHERE `listItemId`='{$values['listItemId']}'",
        "deletelookup"              => "DELETE FROM `lookup` WHERE `itemId` ='{$values['itemId']}'",
        "deletenextaction"          => "DELETE FROM `nextactions` WHERE `nextAction`='{$values['itemId']}'",
        "deletenote"                => "DELETE FROM `tickler` WHERE `ticklerId`='{$values['noteId']}'",
        "deletespacecontext"        => "DELETE FROM `context` WHERE `contextId`='{$values['contextId']}'",
        "deletetimecontext"         => "DELETE FROM `timeitems` WHERE `timeframeId`='{$values['tcId']}'",
        "getchecklistitems"         => "SELECT `checklistItems`.`checklistitemId`, `checklistItems`.`item`, `checklistItems`.`notes`, `checklistItems`.`checklistId`, `checklistItems`.`checked` FROM `checklistItems` LEFT JOIN `checklist` on `checklistItems`.`checklistId` = `checklist`.`checklistId` WHERE `checklist`.`checklistId` = '{$values['checklistId']}' ORDER BY {$sort['getchecklistitems']}",
        "getchecklists"		    => "SELECT `checklist`.`checklistId`, `checklist`.`title`, `checklist`.`description`, `checklist`.`categoryId`, `categories`.`category` FROM `checklist`, `categories` WHERE `checklist`.`categoryId`=`categories`.`categoryId` ".$values['filterquery']." ORDER BY {$sort['getchecklists']}",
        "getchildren"               => "SELECT `items`.`itemId`, `items`.`title`, `items`.`description`, `items`.`desiredOutcome`, `itemattributes`.`type`, `itemattributes`.`isSomeday`, `itemattributes`.`deadline`, `itemattributes`.`repeat`, `itemattributes`.`suppress`, `itemattributes`.`suppressUntil`, `itemstatus`.`dateCreated`, `itemstatus`.`dateCompleted`, `itemstatus`.`lastmodified`, `itemattributes`.`categoryId`, `categories`.`category`, `itemattributes`.`contextId`, `context`.`name` AS cname, `itemattributes`.`timeframeId`, `timeitems`.`timeframe` FROM `itemattributes`, `lookup` JOIN `items` ON (`itemattributes`.`itemId` = `items`.`itemId`) JOIN `itemstatus` ON (`itemattributes`.`itemId` = `itemstatus`.`itemId`) LEFT OUTER JOIN `context` ON (`itemattributes`.`contextId` = `context`.`contextId`) LEFT OUTER JOIN `categories` ON (`itemattributes`.`categoryId` = `categories`.`categoryId`) LEFT OUTER JOIN `timeitems` ON (`itemattributes`.`timeframeId` = `timeitems`.`timeframeId`) WHERE `lookup`.`itemId`= `itemattributes`.`itemId` and `lookup`.`parentId`= '{$values['parentId']}' ".$values['filterquery']." ORDER BY {$sort['getchildren']}",
        "getcompleteditems"	    => "SELECT `itemattributes`.`projectId`, `projects`.`name` AS pname, `items`.`title`, `items`.`description`, `itemstatus`.`dateCreated`, `context`.`contextId`, `context`.`name` AS cname, `items`.`itemId`, `itemstatus`.`dateCompleted`, `itemattributes`.`deadline`, `itemattributes`.`repeat`, `itemattributes`.`suppress`, `itemattributes`.`suppressUntil`, `itemattributes`.`type` FROM `items`, `itemattributes`, `itemstatus`, `projects`, `projectattributes`, `projectstatus`, `context` WHERE `itemstatus`.`itemId` = `items`.`itemId` AND `itemattributes`.`itemId` = `items`.`itemId` AND `itemattributes`.`contextId` = `context`.`contextId` AND `itemattributes`.`projectId` = `projects`.`projectId` AND `projectattributes`.`projectId`=`itemattributes`.`projectId` AND `projectstatus`.`projectId` = `itemattributes`.`projectId` ".$values['filterquery']." ORDER BY {$sort['getcompleteditems']}",
        "getitems"                  => "SELECT `items`.`itemId`, `items`.`title`, `items`.`description` FROM `itemattributes` JOIN `items` ON (`itemattributes`.`itemId` = `items`.`itemId`) JOIN `itemstatus` ON (`itemattributes`.`itemId` = `itemstatus`.`itemId`) LEFT OUTER JOIN `context` ON (`itemattributes`.`contextId` = `context`.`contextId`) LEFT OUTER JOIN `categories` ON (`itemattributes`.`categoryId` = `categories`.`categoryId`) LEFT OUTER JOIN `timeitems` ON (`itemattributes`.`timeframeId` = `timeitems`.`timeframeId`) ".$values['filterquery']." ORDER BY {$sort['getitems']}",
        "getitemsandparent"         => "SELECT x.itemId, x.title, x.description, x.desiredOutcome, x.type, x.isSomeday, x.deadline, x.repeat, x.suppress, x.suppressUntil, x.dateCreated, x.dateCompleted, x.lastmodified, x.categoryId, x.category, x.contextId, x.cname, x.timeframeId, x.timeframe, y.parentId, y.ptitle, y.pdescription, y.pdesiredOutcome, y.ptype, y.pisSomeday, y.pdeadline, y.prepeat, y.psuppress, y.psuppressUntil, y.pdateCreated, y.pdateCompleted, y.plastmodified, y.pcategoryId, y.pcatname, y.pcontextId, y.pcname, y.ptimeframeId, y.ptimeframe FROM (SELECT `items`.`itemId`, `items`.`title`, `items`.`description`, `items`.`desiredOutcome`, `itemattributes`.`type`, `itemattributes`.`isSomeday`, `itemattributes`.`deadline`, `itemattributes`.`repeat`, `itemattributes`.`suppress`, `itemattributes`.`suppressUntil`,  `itemstatus`.`dateCreated`, `itemstatus`.`dateCompleted`, `itemstatus`.`lastmodified`, `itemattributes`.`categoryId`, `categories`.`category`, `itemattributes`.`contextId`, `context`.`name` AS cname, `itemattributes`.`timeframeId`, `timeitems`.`timeframe`, `lookup`.`parentId` FROM `itemattributes` JOIN `items` ON (`itemattributes`.`itemId` = `items`.`itemId`) JOIN `itemstatus` ON (`itemattributes`.`itemId` = `itemstatus`.`itemId`) LEFT OUTER JOIN `context` ON (`itemattributes`.`contextId` = `context`.`contextId`) LEFT OUTER JOIN `categories` ON (`itemattributes`.`categoryId` = `categories`.`categoryId`) LEFT OUTER JOIN `timeitems` ON (`itemattributes`.`timeframeId` = `timeitems`.`timeframeId`) LEFT OUTER JOIN `lookup` ON (`itemattributes`.`itemId` = `lookup`.`itemId`)".$values['childfilterquery'].") x LEFT OUTER JOIN (SELECT `items`.`itemId` AS parentId, `items`.`title` AS ptitle, `items`.`description` AS pdescription, `items`.`desiredOutcome` AS pdesiredOutcome, `itemattributes`.`type` AS ptype, `itemattributes`.`isSomeday` AS pisSomeday, `itemattributes`.`deadline` AS pdeadline, `itemattributes`.`repeat` AS prepeat, `itemattributes`.`suppress` AS psuppress, `itemattributes`.`suppressUntil` AS psuppressUntil,  `itemstatus`.`dateCreated` AS pdateCreated, `itemstatus`.`dateCompleted` AS pdateCompleted, `itemstatus`.`lastmodified` AS plastmodified, `itemattributes`.`categoryId` AS pcategoryId, `categories`.`category` as pcatname, `itemattributes`.`contextId` AS pcontextId, `context`.`name` AS pcname, `itemattributes`.`timeframeId` AS ptimeframeId, `timeitems`.`timeframe` AS ptimeframe FROM `itemattributes` JOIN `items` ON (`itemattributes`.`itemId` = `items`.`itemId`) JOIN `itemstatus` ON (`itemattributes`.`itemId` = `itemstatus`.`itemId`) LEFT OUTER JOIN `context` ON (`itemattributes`.`contextId` = `context`.`contextId`) LEFT OUTER JOIN `categories` ON (`itemattributes`.`categoryId` = `categories`.`categoryId`) LEFT OUTER JOIN `timeitems` ON (`itemattributes`.`timeframeId` = `timeitems`.`timeframeId`)".$values['parentfilterquery'].") y ON (y.parentId = x.parentId) ORDER BY {$sort['getitemsandparent']}",
        "getlistitems"              => "SELECT `listItems`.`listItemId`, `listItems`.`item`, `listItems`.`notes`, `listItems`.`listId` FROM `listItems` LEFT JOIN `list` on `listItems`.`listId` = `list`.`listId` WHERE `list`.`listId` = '{$values['listId']}' ".$values['filterquery']." ORDER BY {$sort['getlistitems']}",
        "getlists"                  => "SELECT `list`.`listId`, `list`.`title`, `list`.`description`, `list`.`categoryId`, `categories`.`category` FROM `list`, `categories` WHERE `list`.`categoryId`=`categories`.`categoryId` ".$values['filterquery']." ORDER BY {$sort['getlists']}",
        "getnotes"                  => "SELECT `ticklerId`, `title`, `note`, `date` FROM `tickler` ".$values['filterquery']." ORDER BY {$sort['getnotes']}",
        "getnextactions"            => "SELECT `parentId`, `nextaction` FROM `nextactions`",
	"getorphaneditems"	    => "SELECT `itemattributes`.`itemId`, `itemattributes`.`type`, `items`.`title`, `items`.`description` FROM `itemattributes`, `items`,`itemstatus` WHERE `items`.`itemId`=`itemattributes`.`itemId` AND `itemstatus`.`itemId`=`itemattributes`.`itemId` AND (`itemstatus`.`dateCompleted` IS NULL OR `itemstatus`.`dateCompleted`='0000-00-00') AND `itemattributes`.`type`!='m' AND `itemattributes`.`type`!='i' AND (`itemattributes`.`itemId` NOT IN (SELECT `lookup`.`itemId` FROM `lookup`)) ORDER BY {$sort['getorphaneditems']}",
        "getspacecontexts"          => "SELECT `contextId`, `name` FROM `context`",
        "gettimecontexts"           => "SELECT `timeframeId`, `timeframe`, `description` FROM `timeitems`",
        "listselectbox"             => "SELECT `list`.`listId`, `list`.`title`, `list`.`description`, `list`.`categoryId`, `categories`.`category` FROM `list`, `categories` WHERE `list`.`categoryId`=`categories`.`categoryId` ORDER BY {$sort['listselectbox']}",
        "lookupparent"              => "SELECT `parentId`, `itemId` FROM `lookup` WHERE `itemId`='{$values['itemId']}'",
        "newcategory"               => "INSERT INTO `categories` VALUES (NULL, '{$values['category']}', '{$values['description']}')",
        "newchecklist"              => "INSERT INTO `checklist` VALUES (NULL, '{$values['title']}', '{$values['categoryId']}', '{$values['description']}')",
        "newchecklistitem"          => "INSERT INTO `checklistItems`  VALUES (NULL, '{$values['item']}', '{$values['notes']}', '{$values['checklistId']}', 'n')",
        "newitem"                   => "INSERT INTO `items` (`title`,`description`,`desiredOutcome`) VALUES ('{$values['title']}','{$values['description']}','{$values['desiredOutcome']}')",
        "newitemattributes"         => "INSERT INTO `itemattributes` (`itemId`,`type`,`isSomeday`,`categoryId`,`contextId`,`timeframeId`,`deadline`,`repeat`,`suppress`,`suppressUntil`) VALUES ('{$values['newitemId']}','{$values['type']}','{$values['isSomeday']}',{$values['categoryId']},'{$values['contextId']}','{$values['timeframeId']}','{$values['deadline']}','{$values['repeat']}','{$values['suppress']}','{$values['suppressUntil']}')",
        "newitemstatus"             => "INSERT INTO `itemstatus` (`itemId`,`dateCreated`,`dateCompleted`) VALUES ('{$values['newitemId']}',CURRENT_DATE,'{$values['dateCompleted']}')",
        "newlist"                   => "INSERT INTO `list` VALUES (NULL, '{$values['title']}', '{$values['categoryId']}', '{$values['description']}')",
        "newlistitem"               => "INSERT INTO `listItems` VALUES (NULL, '{$values['item']}', '{$values['notes']}', '{$values['listId']}', 'n')",
        "newnextaction"             => "INSERT INTO `nextactions` (`parentId`,`nextaction`) VALUES ('{$values['parentId']}','{$values['newitemId']}') ON DUPLICATE KEY UPDATE `nextaction`='{$values['newitemId']}'",
        "newnote"                   => "INSERT INTO `tickler` (date,title,note,repeat,suppressUntil) VALUES ('{$values['date']}','{$values['title']}','{$values['note']}','{$values['repeat']}','{$values['suppressUntil']}')",
        "newparent"                 => "INSERT INTO `lookup` (`parentId`,`itemId`) VALUES ('{$values['parentId']}','{$values['newitemId']}')",
        "newspacecontext"           => "INSERT INTO `context`  VALUES (NULL, '{$values['name']}', '{$values['description']}')",
        "newtimecontext"            => "INSERT INTO `timeitems`  VALUES (NULL, '{$values['name']}', '{$values['description']}', '{$values['type']}')",
        "parentselectbox"           => "SELECT `items`.`itemId`, `items`.`title`, `items`.`description`, `itemattributes`.`isSomeday` FROM `items`, `itemattributes`, `itemstatus` WHERE `itemattributes`.`itemId`=`items`.`itemId` AND `itemstatus`.`itemId`=`items`.`itemId` AND `itemattributes`.`type`='{$values['ptype']}' AND (`itemstatus`.`dateCompleted` IS NULL OR `itemstatus`.`dateCompleted` = '0000-00-00') ORDER BY {$sort['parentselectbox']}",
        "reassigncategory"          => "UPDATE `itemattributes` SET `categoryId`='{$values['newCategoryId']}' WHERE `categoryId`='{$values['categoryId']}'",
        "reassignspacecontext"      => "UPDATE `itemattributes` SET `contextId`='{$values['newContextId']}' WHERE `contextId`='{$values['contextId']}'",
        "reassigntimecontext"       => "UPDATE `itemattributes` SET `timeframeId`='{$values['ntcId']}' WHERE `timeframeId`='{$values['tcId']}'",
        "removechecklistitems"      => "DELETE FROM `checklistItems` WHERE `checklistId`='{$values['checklistId']}'",
        "removeitems"               => "DELETE `itemattributes` FROM `itemattributes`, `items`, `itemstatus` WHERE `items`.`itemId`=`itemattributes`.`itemId` AND `itemstatus`.`itemId`=`itemattributes`.`itemId` AND `itemattributes`.`projectId` = '{$values['projectId']}'",
        "removelistitems"           => "DELETE FROM `listItems` WHERE `listId`='{$values['listId']}'",
        "removenextactions"         => "DELETE FROM `nextactions` WHERE `parentId`='{$values['parentId']}'",
        "removenextactionitem"      => "DELETE FROM `nextactions` WHERE `nextaction`='{$values['itemId']}'",
        "repeatnote"                => "UPDATE `tickler` SET `date` = DATE_ADD(`date`, INTERVAL ".$values['repeat']." DAY), `note` = '{$values['note']}', `title` = '{$values['title']}', `repeat` = '{$values['repeat']}', `suppressUntil` = '{$values['suppressUntil']}' WHERE `ticklerId` = '{$values['noteId']}'",
        "selectcategory"            => "SELECT `categoryId`, `category`, `description` FROM `categories` WHERE `categoryId` = '{$values['categoryId']}'",
        "selectchecklist"           => "SELECT `checklist`.`checklistId`, `checklist`.`title`, `checklist`.`description`, `checklist`.`categoryId`, `categories`.`category` FROM `checklist`, `categories` WHERE `checklist`.`categoryId`=`categories`.`categoryId` AND `checklistId`='{$values['checklistId']}' ".$values['filterquery']." ORDER BY {$sort['selectchecklist']}",
        "selectchecklistitem"       => "SELECT `checklistItems`.`checklistItemId`, `checklistItems`.`item`, `checklistItems`.`notes`, `checklistItems`.`checklistId`, `checklistItems`.`checked` FROM `checklistItems` WHERE `checklistItemId` = '{$values['checklistItemId']}'",
        "selectcontext"             => "SELECT `context`.`contextId`, `context`.`name`, `context`.`description` FROM `context` WHERE `context`.`contextId` = '{$values['contextId']}'",
        "selectitem"                => "SELECT `items`.`itemId`, `itemattributes`.`type`, `items`.`title`, `items`.`description`, `items`.`desiredOutcome`, `itemattributes`.`categoryId`, `itemattributes`.`contextId`, `itemattributes`.`timeframeId`, `itemattributes`.`isSomeday`, `itemattributes`.`deadline`, `itemattributes`.`repeat`, `itemattributes`.`suppress`, `itemattributes`.`suppressUntil`, `itemstatus`.`dateCreated`, `itemstatus`.`dateCompleted`, `itemstatus`.`lastModified`, `categories`.`category`,`timeitems`.`timeframe`, `context`.`name` AS `cname`  FROM `items`, `itemattributes`, `itemstatus` LEFT OUTER JOIN `categories` ON (`categories`.`categoryId`=`itemattributes`.`categoryId`) LEFT OUTER JOIN `context` ON (`context`.`contextId` = `itemattributes`.`contextId`) LEFT OUTER JOIN `timeitems` ON (`timeitems`.`timeframeId` = `itemattributes`.`timeframeId`) WHERE `itemstatus`.`itemId`=`items`.`itemId` AND `itemattributes`.`itemId`=`items`.`itemId` AND `items`.`itemId` = '{$values['itemId']}'",
        "selectlist"                => "SELECT `list`.`listId`, `list`.`title`, `list`.`description`, `list`.`categoryId` FROM `list` WHERE `list`.`listId` = '{$values['listId']}'",
        "selectlistitem"            => "SELECT `listItems`.`listItemId`, `listItems`.`item`, `listItems`.`notes`, `listItems`.`listId`, `listItems`.`dateCompleted` FROM `listItems` WHERE `listItems`.`listItemId` = {$values['listItemId']}",
        "selectnextaction"          => "SELECT `nextactions`.`parentId`, `nextactions`.`nextaction` FROM `nextactions` WHERE `nextactions`.`parentId` = '{$values['parentId']}'",
        "selectnote"                => "SELECT `tickler`.`ticklerId`, `tickler`.`title`, `tickler`.`note`, `tickler`.`date`, `tickler`.`repeat`, `tickler`.`suppressUntil` FROM `tickler` WHERE `tickler`.`ticklerId` = '{$values['noteId']}'",
        "selecttimecontext"         => "SELECT `timeitems`.`timeframeId`, `timeitems`.`timeframe`, `timeitems`.`description`, `timeitems`.`type` FROM `timeitems` WHERE `timeitems`.`timeframeId` = '{$values['tcId']}'",
        "spacecontextselectbox"     => "SELECT `contextId`, `name`, `description` FROM `context` ORDER BY {$sort['spacecontextselectbox']}",
        "testitemrepeat"            => "SELECT `itemattributes`.`repeat` FROM `itemattributes` WHERE `itemattributes`.`itemId`='{$values['completedNa']}'",
        "testnextaction"            => "SELECT `parentId`, `nextaction` FROM `nextactions` WHERE `nextaction`='{$values['itemId']}'",
        "testnoterepeat"            => "SELECT  `tickler`.`repeat` FROM `tickler` WHERE `tickler`.`ticklerId` ='{$values['noteId']}'",
        "timecontextselectbox"      => "SELECT `timeframeId`, `timeframe`, `description` FROM `timeitems`".$values['timefilterquery']."ORDER BY {$sort['timecontextselectbox']}",
        "updatecategory"            => "UPDATE `categories` SET `category` ='{$values['category']}', `description` ='{$values['description']}' WHERE `categoryId` ='{$values['categoryId']}'",
        "updatechecklist"           => "UPDATE `checklist` SET `title` = '{$values['newchecklistTitle']}', `description` = '{$values['newdescription']}', `categoryId` = '{$values['newcategoryId']}' WHERE `checklistId` ='{$values['checklistId']}'",
        "updatechecklistitem"       => "UPDATE `checklistItems` SET `notes` = '{$values['newnotes']}', `item` = '{$values['newitem']}', `checklistId` = '{$values['checklistId']}', `checked`='{$values['newchecked']}' WHERE `checklistItemId` ='{$values['checklistItemId']}'",
        "updatespacecontext"        => "UPDATE `context` SET `name` ='{$values['name']}', `description`='{$values['description']}' WHERE `contextId` ='{$values['contextId']}'",
        "updateitem"                => "UPDATE `items` SET `description` = '{$values['description']}', `title` = '{$values['title']}', `desiredOutcome` = '{$values['desiredOutcome']}' WHERE `itemId` = '{$values['itemId']}'",
        "updateitemattributes"      => "UPDATE `itemattributes` SET `type` = '{$values['type']}', `isSomeday`= '{$values['isSomeday']}', `categoryId` = '{$values['categoryId']}', `contextId` = '{$values['contextId']}', `timeframeId` = '{$values['timeframeId']}', `deadline` ='{$values['deadline']}', `repeat` = '{$values['repeat']}', `suppress`='{$values['suppress']}', `suppressUntil`='{$values['suppressUntil']}' WHERE `itemId` = '{$values['itemId']}'",
        "updateitemstatus"          => "UPDATE `itemstatus` SET `dateCompleted` = '{$values['dateCompleted']}' WHERE `itemId` = '{$values['itemId']}'",
        "updatelist"                => "UPDATE `list` SET `title` = '{$values['newlistTitle']}', `description` = '{$values['newdescription']}', `categoryId` = '{$values['newcategoryId']}' WHERE `listId` ='{$values['listId']}'",
        "updatelistitem"            => "UPDATE `listItems` SET `notes` = '{$values['newnotes']}', `item` = '{$values['newitem']}', `listId` = '{$values['listId']}', `dateCompleted`='{$values['newdateCompleted']}' WHERE `listItemId` ='{$values['listItemId']}'",
        "updateparent"              => "INSERT INTO `lookup` (`parentId`,`itemId`) VALUES ('{$values['parentId']}','{$values['itemId']}') ON DUPLICATE KEY UPDATE `parentId`='{$values['parentId']}'",
        "updatenextaction"          => "INSERT INTO `nextactions` (`parentId`,`nextaction`) VALUES ('{$values['parentId']}','{$values['itemId']}') ON DUPLICATE KEY UPDATE `nextaction`='{$values['itemId']}'",
        "updatenote"                => "UPDATE `tickler` SET `date` = '{$values['date']}', `note` = '{$values['note']}', `title` = '{$values['title']}', `repeat` = '{$values['repeat']}', `suppressUntil` = '{$values['suppressUntil']}' WHERE `ticklerId` = '{$values['noteId']}'",
        "updatetimecontext"         => "UPDATE `timeitems` SET `timeframe` ='{$values['name']}', `description`='{$values['description']}', `type`='{$values['type']}' WHERE `timeframeId` ='{$values['tcId']}'",
    );
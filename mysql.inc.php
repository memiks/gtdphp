<?php

//query listing for MySQL database
//API Documentation available in __________
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
function getsql($config,$values,$sort,$querylabel) {
	switch ($querylabel) {
		case "categoryselectbox":
			$sql="SELECT c.`categoryId`, c.`category`, c.`description`
				FROM `". $config['prefix'] ."categories` as c
				ORDER BY {$sort['categoryselectbox']}";
			break;

		case "checkchecklistitem":
			$sql="UPDATE `". $config['prefix'] ."checklistitems`
				SET `checked` = 'y'
				WHERE `checklistItemId`='{$values['Cli']}'";
			break;

		case "checklistselectbox":
			$sql="SELECT cl.`checklistId`, cl.`title`,
						cl.`description`, cl.`categoryId`, c.`category`
				FROM `". $config['prefix'] ."checklist` as cl
				LEFT OUTER JOIN `". $config['prefix'] ."categories` as c USING (`categoryId`)
				ORDER BY {$sort['checklistselectbox']}";
			break;

		case "clearchecklist":
			$sql="UPDATE `". $config['prefix'] ."checklistitems`
				SET `checked` = 'n'
				WHERE `checklistId` = '{$values['checklistId']}'";
			break;

		case "completeitem":
			$sql="UPDATE `". $config['prefix'] ."itemstatus`
				SET `dateCompleted`=" . $values['dateCompleted'].
				", `lastModified` = NULL
				WHERE `itemId`=" . $values['itemId'];
			break;

		case "completelistitem":
			$sql="UPDATE `". $config['prefix'] ."listitems`
				SET `dateCompleted`='{$values['date']}'
				WHERE `listItemId`='{$values['completedLi']}'";
			break;

		case "copynextaction":
			$sql="INSERT INTO `". $config['prefix'] ."nextactions` (`parentId`,`nextaction`)
				VALUES ('{$values['parentId']}','{$values['newitemId']}')
				ON DUPLICATE KEY UPDATE `nextaction`='{$values['newitemId']}'";
			break;

        case 'countactionsbycontext':
            $sql="SELECT cn.`name` AS cname,cn.`contextId`,COUNT(x.`itemId`) AS count
                    FROM `{$config['prefix']}itemattributes` as x
                    JOIN `{$config['prefix']}itemattributes` as ia USING (`itemId`)
                    JOIN `{$config['prefix']}itemstatus` as its USING (`itemId`)
					LEFT OUTER JOIN `{$config['prefix']}context` AS cn
						ON (ia.`contextId` = cn.`contextId`)
                     {$values['filterquery']}
                     GROUP BY ia.`contextId` ORDER BY cn.`name`";
            break;
            
		case "countchildren":
			$sql="SELECT il.`itemId`
				FROM `". $config['prefix'] ."lookup` as il,
						`". $config['prefix'] ."itemstatus` as its
				WHERE il.`itemId`=its.`itemId`
					AND il.`parentId`=". $values['parentId'] . "
					AND its.`dateCompleted` IS NULL";
			break;

		case "countitems":
			$sql="SELECT COUNT(*)
				FROM `". $config['prefix'] ."itemattributes` as ia,
						`". $config['prefix'] ."itemstatus` as its
				WHERE ia.`itemId`=its.`itemId` ".$values['filterquery'];
			break;

		case "countnextactions":
			$sql="SELECT COUNT(DISTINCT `nextaction`) AS nnextactions
				FROM `". $config['prefix'] ."nextactions` as na
					JOIN `". $config['prefix'] . "itemattributes` as ia
						ON (ia.`itemId` = na.`nextaction`)
					JOIN `". $config['prefix'] . "itemstatus` as its
						ON (ia.`itemId` = its.`itemId`) ".
				$values['filterquery'];
			break;
		case "countselected":
			$sql="SELECT FOUND_ROWS()";
			break;
		case "countspacecontexts":
			$sql="SELECT COUNT(*)
				FROM `". $config['prefix'] ."context`";
			break;
		case "deletecategory":
			$sql="DELETE FROM `". $config['prefix'] ."categories`
				WHERE `categoryId`='{$values['id']}'";
			break;
		case "deletechecklist":
			$sql="DELETE FROM `". $config['prefix'] ."checklist`
				WHERE `checklistId`='{$values['checklistId']}'";
			break;
		case "deletechecklistitem":
			$sql="DELETE FROM `". $config['prefix'] ."checklistitems`
				WHERE `checklistItemId`='{$values['checklistItemId']}'";
			break;
		case "deleteitem":
			$sql="DELETE FROM `". $config['prefix'] ."items`
				WHERE `itemId`='{$values['itemId']}'";
			break;
		case "deleteitemattributes":
			$sql="DELETE FROM `". $config['prefix'] ."itemattributes`
				WHERE `itemId`='{$values['itemId']}'";
			break;
		case "deleteitemstatus":
			$sql="DELETE FROM `". $config['prefix'] ."itemstatus`
				WHERE `itemId`='{$values['itemId']}'";
			break;
		case "deletelist":
			$sql="DELETE FROM `". $config['prefix'] ."list`
				WHERE `listId`='{$values['listId']}'";
			break;
		case "deletelistitem":
			$sql="DELETE FROM `". $config['prefix'] ."listitems`
				WHERE `listItemId`='{$values['listItemId']}'";
			break;
		case "deletelookup":
			$sql="DELETE FROM `". $config['prefix'] ."lookup`
				WHERE `itemId` ='{$values['itemId']}'";
			break;
		case "deletelookupparents":
			$sql="DELETE FROM `". $config['prefix'] ."lookup`
				WHERE `parentId` ='{$values['itemId']}'";
			break;
		case "deletenextaction":
			$sql="DELETE FROM `". $config['prefix'] ."nextactions`
				WHERE `nextAction`='{$values['itemId']}'";
			break;
		case "deletenextactionparents":
			$sql="DELETE FROM `". $config['prefix'] ."nextactions`
				WHERE `parentId` ='{$values['itemId']}'";
			break;
		case "deletenote":
			$sql="DELETE FROM `". $config['prefix'] ."tickler`
				WHERE `ticklerId`='{$values['noteId']}'";
			break;
		case "deletespacecontext":
			$sql="DELETE FROM `". $config['prefix'] ."context`
				WHERE `contextId`='{$values['id']}'";
			break;
		case "deletetimecontext":
			$sql="DELETE FROM `". $config['prefix'] ."timeitems`
				WHERE `timeframeId`='{$values['id']}'";
			break;


		case "getchecklistitems":
			$sql="SELECT cli.`checklistitemId`, cli.`item`, cli.`notes`,
						cli.`checklistId`, cli.`checked`
				FROM `". $config['prefix'] . "checklistitems` as cli
					LEFT JOIN `". $config['prefix'] ."checklist` as cl
						ON cli.`checklistId` = cl.`checklistId`
				WHERE cl.`checklistId` = '{$values['checklistId']}'
				ORDER BY {$sort['getchecklistitems']}";
			break;

		case "getchecklists":
			$sql="SELECT cl.`checklistId`, cl.`title`,
						cl.`description`, cl.`categoryId`, c.`category`
				FROM `". $config['prefix'] ."checklist` as cl
				LEFT OUTER JOIN `{$config['prefix']}categories` as c USING (`categoryId`) "
				.$values['filterquery']." ORDER BY {$sort['getchecklists']}";
			break;
			
		case "getchildren":
			$sql="SELECT i.`itemId`, i.`title`, i.`description`,
					i.`desiredOutcome`, ia.`type`,
					ia.`isSomeday`, ia.`deadline`, ia.`repeat`,
					ia.`suppress`, ia.`suppressUntil`,
					its.`dateCreated`, its.`dateCompleted`,
					its.`lastModified`, ia.`categoryId`,
					c.`category`, ia.`contextId`,
					cn.`name` AS cname, ia.`timeframeId`, ti.`timeframe`
					, na.nextaction as NA
				FROM `". $config['prefix'] . "itemattributes` as ia
					JOIN `{$config['prefix']}lookup` AS lu USING (`itemId`)
					JOIN `". $config['prefix'] . "items` AS i USING (`itemId`)
					JOIN `". $config['prefix'] . "itemstatus` AS its USING (`itemId`)
					LEFT OUTER JOIN `". $config['prefix'] . "context` AS cn
						ON (ia.`contextId` = cn.`contextId`)
					LEFT OUTER JOIN `". $config['prefix'] ."categories` AS c
						ON (ia.`categoryId` = c.`categoryId`)
					LEFT OUTER JOIN `". $config['prefix'] . "timeitems` AS ti
						ON (ia.`timeframeId` = ti.`timeframeId`)
				LEFT JOIN (
						SELECT DISTINCT nextaction FROM {$config['prefix']}nextactions
					) AS na ON(na.nextaction=i.itemId)
				WHERE lu.`parentId`= '{$values['parentId']}' {$values['filterquery']}
				ORDER BY {$sort['getchildren']}";
			break;

		case "getgtdphpversion":
			$sql="SELECT `version` FROM `{$config['prefix']}version`";
			break;

		case "getitems":
			$sql="SELECT i.`itemId`, i.`title`, i.`description`
				FROM `". $config['prefix'] . "itemattributes` as ia
					JOIN `". $config['prefix'] . "items` as i
						ON (ia.`itemId` = i.`itemId`)
					JOIN `". $config['prefix'] . "itemstatus` as its
						ON (ia.`itemId` = its.`itemId`)
					LEFT OUTER JOIN `". $config['prefix'] . "context` as cn
						ON (ia.`contextId` = cn.`contextId`)
					LEFT OUTER JOIN `". $config['prefix'] ."categories` as c
						ON (ia.`categoryId` = c.`categoryId`)
					LEFT OUTER JOIN `". $config['prefix'] . "timeitems` as ti
						ON (ia.`timeframeId` = ti.`timeframeId`) ".$values['filterquery']."
				ORDER BY {$sort['getitems']}";
			break;

		case "getitemsandparent":
			$sql="SELECT
    				x.`itemId`, x.`title`, x.`description`,
    				x.`desiredOutcome`, x.`type`, x.`isSomeday`,
    				x.`deadline`, x.`repeat`, x.`suppress`,
    				x.`suppressUntil`, x.`dateCreated`, x.`dateCompleted`,
    				x.`lastModified`, x.`categoryId`, x.`category`,
    				x.`contextId`, x.`cname`, x.`timeframeId`,
    				x.`timeframe`,
    				GROUP_CONCAT(DISTINCT y.`parentId` ORDER BY y.`ptitle`) as `parentId`,
    				GROUP_CONCAT(DISTINCT y.`ptitle` ORDER BY y.`ptitle` SEPARATOR '{$config['separator']}') AS `ptitle`
    				{$values['extravarsfilterquery']}
				FROM (
						SELECT
							i.`itemId`, i.`title`, i.`description`,
							i.`desiredOutcome`, ia.`type`, ia.`isSomeday`,
							ia.`deadline`, ia.`repeat`, ia.`suppress`,
							ia.`suppressUntil`, its.`dateCreated`,
							its.`dateCompleted`, its.`lastModified`,
							ia.`categoryId`, c.`category`, ia.`contextId`,
							cn.`name` AS cname, ia.`timeframeId`,
							ti.`timeframe`, lu.`parentId`
						FROM
								`". $config['prefix'] . "itemattributes` as ia
							JOIN `". $config['prefix'] . "items` as i
								ON (ia.`itemId` = i.`itemId`)
							JOIN `". $config['prefix'] . "itemstatus` as its
								ON (ia.`itemId` = its.`itemId`)
							LEFT OUTER JOIN `". $config['prefix'] . "context` as cn
								ON (ia.`contextId` = cn.`contextId`)
							LEFT OUTER JOIN `". $config['prefix'] ."categories` as c
								ON (ia.`categoryId` = c.`categoryId`)
							LEFT OUTER JOIN `". $config['prefix'] . "timeitems` as ti
								ON (ia.`timeframeId` = ti.`timeframeId`)
							LEFT OUTER JOIN `". $config['prefix'] . "lookup` as lu
								ON (ia.`itemId` = lu.`itemId`)".$values['childfilterquery']."
				) as x
					LEFT OUTER JOIN
					(
						SELECT
							i.`itemId` AS parentId, i.`title` AS ptitle,
							i.`description` AS pdescription,
							i.`desiredOutcome` AS pdesiredOutcome,
							ia.`type` AS ptype, ia.`isSomeday` AS pisSomeday,
							ia.`deadline` AS pdeadline, ia.`repeat` AS prepeat,
							ia.`suppress` AS psuppress,
							ia.`suppressUntil` AS psuppressUntil,
							its.`dateCompleted` AS pdateCompleted
						FROM
								`". $config['prefix'] . "itemattributes` as ia
							JOIN `". $config['prefix'] . "items` as i
								ON (ia.`itemId` = i.`itemId`)
							JOIN `". $config['prefix'] . "itemstatus` as its
								ON (ia.`itemId` = its.`itemId`)
						{$values['parentfilterquery']}
					) as y ON (y.parentId = x.parentId)
				{$values['filterquery']} GROUP BY x.`itemId`
				ORDER BY {$sort['getitemsandparent']}";
			break;


		case "getitembrief":
			$sql="SELECT `title`, `description`
				FROM  `". $config['prefix'] . "items`
				WHERE `itemId` = {$values['itemId']}";
			break;

		case "getlistitems":
			$sql="SELECT li.`listItemId`, li.`item`, li.`notes`, li.`listId`
				FROM `". $config['prefix'] . "listitems` as li
					LEFT JOIN `". $config['prefix'] . "list` as l
						on li.`listId` = l.`listId`
				WHERE l.`listId` = '{$values['listId']}' ".$values['filterquery']."
				ORDER BY {$sort['getlistitems']}";
			break;

		case "getlists":
			$sql="SELECT l.`listId`, l.`title`, l.`description`, l.`categoryId`, c.`category`
				FROM `". $config['prefix'] . "list` as l
				LEFT OUTER JOIN `{$config['prefix']}categories` as c USING (`categoryId`) "
				.$values['filterquery']." ORDER BY {$sort['getlists']}";
			break;

		case "getnotes":
			$sql="SELECT `ticklerId`, `title`, `note`, `date`
				FROM `". $config['prefix'] . "tickler`  as tk".$values['filterquery']."
				ORDER BY {$sort['getnotes']}";
			break;

		case "getorphaneditems":
			$sql="SELECT ia.`itemId`, ia.`type`, i.`title`, i.`description`, ia.`isSomeday`
				FROM `{$config['prefix']}itemattributes` AS ia
				JOIN `{$config['prefix']}items`		  AS i   USING (itemId)
				JOIN `{$config['prefix']}itemstatus`	 AS its USING (itemId)
				WHERE (its.`dateCompleted` IS NULL)
					AND ia.`type` NOT IN ({$values['notOrphansfilterquery']})
					AND ia.`itemId` NOT IN
						(SELECT lu.`itemId` FROM `". $config['prefix'] . "lookup` as lu)
				ORDER BY {$sort['getorphaneditems']}";
			break;

		case "getspacecontexts":
			$sql="SELECT `contextId`, `name`
				FROM `". $config['prefix'] . "context`";
			break;

		case "gettimecontexts":
			$sql="SELECT `timeframeId`, `timeframe`, `description`
				FROM `". $config['prefix'] . "timeitems` AS ti
				{$values['timefilterquery']}";
			break;


		case "listselectbox":
			$sql="SELECT l.`listId`, l.`title`, l.`description`,
						l.`categoryId`, c.`category`
				FROM `". $config['prefix'] . "list` as l
				LEFT OUTER JOIN `{$config['prefix']}categories` as c USING (`categoryId`)
				ORDER BY {$sort['listselectbox']}";
			break;

		case "lookupparent":
			$sql="SELECT lu.`parentId`,i.`title` AS `ptitle`,ia.`isSomeday`,ia.`type` AS `ptype`
				FROM `". $config['prefix'] . "lookup` AS lu
				JOIN `{$config['prefix']}items` AS i ON (lu.`parentId` = i.`itemId`)
				JOIN `{$config['prefix']}itemattributes` AS ia ON (lu.`parentId` = ia.`itemId`)
				WHERE lu.`itemId`='{$values['itemId']}'";
			break;

		case "newcategory":
			$sql="INSERT INTO `". $config['prefix'] ."categories`
				VALUES (NULL, '{$values['name']}', '{$values['description']}')";
			break;

		case "newchecklist":
			$sql="INSERT INTO `". $config['prefix'] ."checklist`
				VALUES (NULL, '{$values['title']}',
						'{$values['categoryId']}', '{$values['description']}')";
			break;

		case "newchecklistitem":
			$sql="INSERT INTO `". $config['prefix'] . "checklistitems`
				VALUES (NULL, '{$values['item']}',
						'{$values['notes']}', '{$values['checklistId']}', 'n')";
			break;

		case "newitem":
			$sql="INSERT INTO `". $config['prefix'] . "items`
						(`title`,`description`,`desiredOutcome`)
				VALUES ('{$values['title']}',
						'{$values['description']}','{$values['desiredOutcome']}')";
			break;

		case "newitemattributes":
			$sql="INSERT INTO `". $config['prefix'] . "itemattributes`
						(`itemId`,`type`,`isSomeday`,`categoryId`,`contextId`,
						`timeframeId`,`deadline`,`repeat`,`suppress`,`suppressUntil`)
				VALUES ('{$values['newitemId']}','{$values['type']}','{$values['isSomeday']}',
						'{$values['categoryId']}','{$values['contextId']}','{$values['timeframeId']}',
						{$values['deadline']},'{$values['repeat']}','{$values['suppress']}',
						'{$values['suppressUntil']}')";
			break;

		case "newitemstatus":
			$sql="INSERT INTO `". $config['prefix'] . "itemstatus`
						(`itemId`,`dateCreated`,`lastModified`,`dateCompleted`)
				VALUES ('{$values['newitemId']}',
						CURRENT_DATE,NULL,{$values['dateCompleted']})";
			break;

		case "newlist":
			$sql="INSERT INTO `". $config['prefix'] . "list`
				VALUES (NULL, '{$values['title']}',
						'{$values['categoryId']}', '{$values['description']}')";
			break;

		case "newlistitem":
			$sql="INSERT INTO `". $config['prefix'] . "listitems`
				VALUES (NULL, '{$values['item']}',
						'{$values['notes']}', '{$values['listId']}', NULL)";
			break;

		case "newnextaction":
			$sql="INSERT INTO `". $config['prefix'] . "nextactions`
						(`parentId`,`nextaction`)
				VALUES ('{$values['parentId']}','{$values['newitemId']}')
				ON DUPLICATE KEY UPDATE `nextaction`='{$values['newitemId']}'";
			break;

		case "newnote":
			$sql="INSERT INTO `". $config['prefix'] . "tickler`
						(`date`,`title`,`note`,`repeat`,`suppressUntil`)
				VALUES ('{$values['date']}','{$values['title']}',
						'{$values['note']}','{$values['repeat']}',
						'{$values['suppressUntil']}')";
			break;

		case "newparent":
			$sql="INSERT INTO `". $config['prefix'] . "lookup`
						(`parentId`,`itemId`)
				VALUES ('{$values['parentId']}','{$values['newitemId']}')";
			break;

		case "newspacecontext":
			$sql="INSERT INTO `". $config['prefix'] . "context`
						(`name`,`description`)
				VALUES ('{$values['name']}', '{$values['description']}')";
			break;

		case "newtimecontext":
			$sql="INSERT INTO `". $config['prefix'] . "timeitems`
						(`timeframe`,`description`,`type`)
				VALUES ('{$values['name']}', '{$values['description']}', '{$values['type']}')";
			break;

		case "parentselectbox":
			$sql="SELECT i.`itemId`, i.`title`,
						i.`description`, ia.`isSomeday`,ia.`type`
				FROM `". $config['prefix'] . "items` as i
				JOIN `{$config['prefix']}itemattributes` as ia USING (`itemId`)
				JOIN `{$config['prefix']}itemstatus` as its USING (`itemId`)
				WHERE (its.`dateCompleted` IS NULL) {$values['ptypefilterquery']}
				ORDER BY ia.`type`,i.`title`";
				#ORDER BY {$sort['parentselectbox']}";
			break;


		case "reassigncategory":
			$sql="UPDATE `". $config['prefix'] . "itemattributes`
				SET `categoryId`='{$values['newId']}'
				WHERE `categoryId`='{$values['id']}'";
			break;

		case "reassignspacecontext":
			$sql="UPDATE `". $config['prefix'] . "itemattributes`
				SET `contextId`='{$values['newId']}'
				WHERE `contextId`='{$values['id']}'";
			break;

		case "reassigntimecontext":
			$sql="UPDATE `". $config['prefix'] . "itemattributes`
				SET `timeframeId`='{$values['newId']}'
				WHERE `timeframeId`='{$values['id']}'";
			break;


		case "removechecklistitems":
			$sql="DELETE
				FROM `". $config['prefix'] . "checklistitems`
				WHERE `checklistId`='{$values['checklistId']}'";
			break;

		case "removelistitems":
			$sql="DELETE
				FROM `". $config['prefix'] . "listitems`
				WHERE `listId`='{$values['listId']}'";
			break;

		case "repeatnote":
			$sql="UPDATE `". $config['prefix'] . "tickler`
				SET `date` = DATE_ADD(`date`, INTERVAL ".$values['repeat']." DAY),
					`note` = '{$values['note']}', `title` = '{$values['title']}',
					`repeat` = '{$values['repeat']}',
					`suppressUntil` = '{$values['suppressUntil']}'
				WHERE `ticklerId` = '{$values['noteId']}'";
			break;

		case "selectcategory":
			$sql="SELECT `categoryId`, `category`, `description`
				FROM `". $config['prefix'] ."categories`
				WHERE `categoryId` = '{$values['categoryId']}'";
			break;

		case "selectchecklist":
			$sql="SELECT cl.`checklistId`, cl.`title`,
						cl.`description`, cl.`categoryId`, c.`category`
				FROM `". $config['prefix'] ."checklist` as cl,
						`". $config['prefix'] ."categories` as c
				WHERE cl.`categoryId`=c.`categoryId`
					AND cl.`checklistId`='{$values['checklistId']}' ".$values['filterquery']."
				ORDER BY {$sort['selectchecklist']}";
			break;

		case "selectchecklistitem":
			$sql="SELECT `checklistItemId`,
						`item`,
						`notes`,
						`checklistId`,
						`checked`
				FROM `". $config['prefix'] . "checklistitems`
				WHERE `checklistItemId` = '{$values['checklistItemId']}'";
			break;

		case "selectcontext":
			$sql="SELECT `contextId`, `name`, `description`
				FROM `". $config['prefix'] . "context`
				WHERE `contextId` = '{$values['contextId']}'";
			break;

		case "selectitem":
			$sql="SELECT i.`itemId`, ia.`type`, i.`title`,
					i.`description`, i.`desiredOutcome`,
					ia.`categoryId`, ia.`contextId`,
					ia.`timeframeId`, ia.`isSomeday`,
					ia.`deadline`, ia.`repeat`,
					ia.`suppress`, ia.`suppressUntil`,
					its.`dateCreated`, its.`dateCompleted`,
					its.`lastModified`, c.`category`, ti.`timeframe`,
					cn.`name` AS `cname`
				FROM (`". $config['prefix'] . "items` as i,
						 `". $config['prefix'] . "itemattributes` as ia,
						 `". $config['prefix'] . "itemstatus` as its)
					LEFT OUTER JOIN `". $config['prefix'] ."categories` as c
						ON (c.`categoryId` = ia.`categoryId`)
					LEFT OUTER JOIN `". $config['prefix'] . "context` as cn
						ON (cn.`contextId` = ia.`contextId`)
					LEFT OUTER JOIN `". $config['prefix'] . "timeitems` as ti
						ON (ti.`timeframeId` = ia.`timeframeId`)
				WHERE its.`itemId`=i.`itemId`
					AND ia.`itemId`=i.`itemId`
					AND i.`itemId` = '{$values['itemId']}'";
			break;

		case "selectitemshort":
			$sql="SELECT i.`itemId`, i.`title`,
						i.`description`, ia.`isSomeday`,ia.`type`
				FROM `". $config['prefix'] . "items` as i
				JOIN `{$config['prefix']}itemattributes` AS ia USING (`itemId`)
				JOIN `{$config['prefix']}itemstatus` AS its USING (`itemId`)
				WHERE i.`itemId` = '{$values['itemId']}'";
			break;

		case "selectlist":
			$sql="SELECT `listId`, `title`, `description`, `categoryId`
				FROM `". $config['prefix'] . "list`
				WHERE `listId` = '{$values['listId']}'";
			break;

		case "selectlistitem":
			$sql="SELECT `listItemId`, `item`,
						`notes`, `listId`, `dateCompleted`
				FROM `". $config['prefix'] . "listitems`
				WHERE `listItemId` = {$values['listItemId']}";
			break;

		case "selectnote":
			$sql="SELECT `ticklerId`, `title`, `note`,
						`date`, `repeat`, `suppressUntil`
				FROM `". $config['prefix'] . "tickler`
				WHERE `ticklerId` = '{$values['noteId']}'";
			break;

		case "selecttimecontext":
			$sql="SELECT `timeframeId`, `timeframe`, `description`, `type`
				FROM `". $config['prefix'] . "timeitems`
				WHERE `timeframeId` = '{$values['tcId']}'";
			break;

		case "spacecontextselectbox":
			$sql="SELECT `contextId`, `name`, `description`
				FROM `". $config['prefix'] . "context` as cn
				ORDER BY {$sort['spacecontextselectbox']}";
			break;

		case "testitemrepeat":
			$sql="SELECT ia.`repeat`,its.`dateCompleted`
				FROM `{$config['prefix']}itemattributes` as ia
                JOIN `{$config['prefix']}itemstatus` as its USING (`itemId`)
				WHERE ia.`itemId`='{$values['itemId']}'";
			break;

		case "testnextaction":
			$sql="SELECT `parentId`, `nextaction`
				FROM `". $config['prefix'] . "nextactions`
				WHERE `nextaction`='{$values['itemId']}'";
			break;

		case "timecontextselectbox":
			$sql="SELECT `timeframeId`, `timeframe`, `description`, `type`
				FROM `". $config['prefix'] . "timeitems` as ti".$values['timefilterquery']."
				ORDER BY {$sort['timecontextselectbox']}";
			break;

		case "touchitem":
			$sql="UPDATE `". $config['prefix'] . "itemstatus`
				SET `lastModified` = NULL
				WHERE `itemId` = '{$values['itemId']}'";
			break;

		case "updatecategory":
			$sql="UPDATE `". $config['prefix'] ."categories`
				SET `category` ='{$values['name']}',
						`description` ='{$values['description']}'
				WHERE `categoryId` ='{$values['id']}'";
			break;

		case "updatechecklist":
			$sql="UPDATE `". $config['prefix'] ."checklist`
				SET `title` = '{$values['newchecklistTitle']}',
						`description` = '{$values['newdescription']}',
						`categoryId` = '{$values['newcategoryId']}'
				WHERE `checklistId` ='{$values['checklistId']}'";
			break;

		case "updatechecklistitem":
			$sql="UPDATE `". $config['prefix'] . "checklistitems`
				SET `notes` = '{$values['newnotes']}', `item` = '{$values['newitem']}',
						`checklistId` = '{$values['checklistId']}',
						`checked`='{$values['newchecked']}'
				WHERE `checklistItemId` ='{$values['checklistItemId']}'";
			break;

		case "updatedeadline":
			$sql="UPDATE `{$config['prefix']}itemattributes`
				SET `deadline` ={$values['deadline']}
				WHERE `itemId` = '{$values['itemId']}'";
			break;
			
		case "updatespacecontext":
			$sql="UPDATE `". $config['prefix'] . "context`
				SET `name` ='{$values['name']}',
						`description`='{$values['description']}'
				WHERE `contextId` ='{$values['id']}'";
			break;

		case "updateitem":
			$sql="UPDATE `". $config['prefix'] . "items`
				SET `description` = '{$values['description']}',
						`title` = '{$values['title']}',
						`desiredOutcome` = '{$values['desiredOutcome']}'
				WHERE `itemId` = '{$values['itemId']}'";
			break;

		case "updateitemattributes":
			$sql="UPDATE `". $config['prefix'] . "itemattributes`
				SET `type` = '{$values['type']}',
						`isSomeday`= '{$values['isSomeday']}',
						`categoryId` = '{$values['categoryId']}',
						`contextId` = '{$values['contextId']}',
						`timeframeId` = '{$values['timeframeId']}',
						`deadline` ={$values['deadline']},
						`repeat` = '{$values['repeat']}',
						`suppress`='{$values['suppress']}',
						`suppressUntil`='{$values['suppressUntil']}'
				WHERE `itemId` = '{$values['itemId']}'";
			break;

		case "updateitemtype":
			$sql="UPDATE `{$config['prefix']}itemattributes`
				SET `type` = '{$values['type']}',
					`isSomeday`= '{$values['isSomeday']}'
				WHERE `itemId` = '{$values['itemId']}'";
			break;

		case "updatelist":
			$sql="UPDATE `". $config['prefix'] . "list`
				SET `title` = '{$values['newlistTitle']}',
						`description` = '{$values['newdescription']}',
						`categoryId` = '{$values['newcategoryId']}'
				WHERE `listId` ='{$values['listId']}'";
			break;

		case "updatelistitem":
			$sql="UPDATE `". $config['prefix'] . "listitems`
				SET `notes` = '{$values['newnotes']}', `item` = '{$values['newitem']}',
						`listId` = '{$values['listId']}',
						`dateCompleted`={$values['newdateCompleted']}
				WHERE `listItemId` ='{$values['listItemId']}'";
			break;

		case "updateparent":
			$sql="INSERT INTO `". $config['prefix'] . "lookup`
						(`parentId`,`itemId`)
				VALUES ('{$values['parentId']}','{$values['itemId']}')
				ON DUPLICATE KEY UPDATE `parentId`='{$values['parentId']}'";
			break;

		case "updatenextaction":
			$sql="INSERT INTO `". $config['prefix'] . "nextactions`
						(`parentId`,`nextaction`)
				VALUES ('{$values['parentId']}','{$values['itemId']}')
				ON DUPLICATE KEY UPDATE `nextaction`='{$values['itemId']}'";
			break;

		case "updatenote":
			$sql="UPDATE `". $config['prefix'] . "tickler`
				SET `date` = '{$values['date']}',
					`note` = '{$values['note']}',
					`title` = '{$values['title']}',
					`repeat` = '{$values['repeat']}',
					`suppressUntil` = '{$values['suppressUntil']}'
				WHERE `ticklerId` = '{$values['noteId']}'";
			break;

		case "updatetimecontext":
			$sql="UPDATE `". $config['prefix'] . "timeitems`
				SET `timeframe` ='{$values['name']}',
						`description`='{$values['description']}',
						`type`='{$values['type']}'
				WHERE `timeframeId` ='{$values['id']}'";
			break;
	}
	return $sql;
}
// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser

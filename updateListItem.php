<?php
//INCLUDES
include_once('headerDB.inc.php');

//RETRIEVE URL AND FORM VARIABLES
$values=array();
$values['newitem']=$_POST['newitem'];
$values['newnotes']=$_POST['newnotes'];
$values['listId'] = (int) $_POST['listId'];
$values['newdateCompleted'] = ($_POST['newdateCompleted']=='')?'NULL':"'{$_POST['newdateCompleted']}'";
$values['listItemId'] = (int) $_GET['listItemId'];
$values['delete']=$_POST['delete']{0};

//SQL CODE AREA
$q=($values['delete']==="y")?'deletelistitem':'updatelistitem';
query($q,$config,$values);

nextScreen('listReport.php?listId='.$values['listId']);

include_once('footer.php');
?>

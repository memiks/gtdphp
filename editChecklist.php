<?php
//INCLUDES
include_once('header.php');

//RETRIEVE URL FORM VARIABLES
$values=array();	
$values['checklistId'] =(int) $_GET["checklistId"];
$values['checklistTitle'] =(string) $_GET['checklistTitle'];

//SQL CODE
$result = query("selectchecklist",$config,$values,$sort);
$row = $result[0];

    //select all categories for dropdown list
$values['categoryId'] = $row['categoryId'];
$cshtml=categoryselectbox($config,$values,$sort);

//PAGE DISPLAY CODE
echo "<h2>Edit Checklist: {$values['checklistTitle']}</h2>";
?>

<div class='form'>
<form action="updateChecklist.php?checklistId=<?php echo $values['checklistId']; ?>" method="post">
        <div class='formrow'>
                <label for='title' class='left first'>Checklist Title:</label>
                <input type='text' name='newchecklistTitle' id='title' value='<?php echo htmlspecialchars(stripslashes($row['title'])); ?>' />
        </div>

        <div class='formrow'>
                <label for='category' class='left first'>Category:</label>
                <select name='newcategoryId' id='category'>
            <?php echo $cshtml; ?>
            </select>
        </div>


        <div class='formrow'>
                <label for='description' class='left first'>Description:</label>
                <textarea rows="10" name="newdescription" id="description" cols="60"><?php echo htmlspecialchars(stripslashes($row['description'])); ?></textarea>
        </div>
    <div class='formbuttons'>
        <input type="submit" value="Update Checklist" name="submit" />
        <input type="reset" value="Reset" />
        <input type='checkbox' name='delete' id='delete' value='y' title='ALL items will be deleted!' /><label for='delete'>Delete&nbsp;Checklist</label>
        <input type='checkbox' name='clear' id='clear' value='clear' class='notfirst'/><label for='clear'>Clear&nbsp;Checklist</label>
    </div>
</form>
</div>
<?php
        include_once('footer.php');
?>

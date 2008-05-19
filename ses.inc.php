<?php
session_start();
if(isset($_SESSION['views']))
    $_SESSION['views']++;
else{
    $_SESSION['categoryId'] = $_SESSION['contextId'] = 0;
    $_SESSION['debug']=$_SESSION['sort']=$_SESSION['keys']=$_SESSION['config']=
        $_SESSION['hierarchy']=$_SESSION['message']=$_SESSION['addons']=
        $_SESSION['installedaddons']= array();
    $_SESSION['theme'] = 'default';
    $_SESSION['version'] = '';
    $_SESSION['views'] = 1;
    foreach (array('theme','useLiveEnhancements') as $key)
        if (array_key_exists($key,$_COOKIE))
            $_SESSION[$key]=$_COOKIE[$key]; // retrieve cookie values
}

// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser

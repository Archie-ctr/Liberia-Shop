<?php

session_start();

unset($_SESSION["uid"]);

unset($_SESSION["name"]);

$admin_was_logged_in = isset($_SESSION['admin_name']);
unset($_SESSION['admin_id']);
unset($_SESSION['admin_name']);
unset($_SESSION['admin_email']);
unset($_SESSION['admin_role']);

$BackToMyPage = $_SERVER['HTTP_REFERER'] ?? null;
if($admin_was_logged_in) {
    header('Location: signin_form.php');
} elseif(isset($BackToMyPage) && $BackToMyPage !== '') {
    header('Location: '.$BackToMyPage);
} else {
    header('Location: index.php'); // default page
}
   

?>

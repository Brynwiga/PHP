<?php
session_start();

$servername = "ip";
$username = "username";
$password = "password";
$database = "database";
$conn = new mysqli($servername,$username,$password,$database);
if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}
//determine if is logged in or not
$l = -1;
$message = "";
if (isset($_SESSION['li']) && $_SESSION['li']) {
    $l = 1;
    }
//determine what form is posted
if (isset($_POST['log_in'])) {
    $un = mysqli_real_escape_string($conn,$_POST['username']);
    $psswrd = mysqli_real_escape_string($conn,$_POST['password']);
    $str = "SELECT * FROM users WHERE user_name='$un' LIMIT 1;";
    $query = mysqli_query($conn,$str);
    $data = mysqli_fetch_assoc($query);
    $pwrd = $data['user_password'];
    $verification = password_verify($psswrd,$pwrd);
    if ($verification) {
        $l = 1;
        $_SESSION['li'] = true;
        $_SESSION['id'] = $data['user_id'];
        $_SESSION['f'] = $data['user_first'];
        $_SESSION['l'] = $data['user_last'];
        $_SESSION['n'] = $data['user_name'];
        $_SESSION['e'] = $data['user_email'];
        $change_reset_id = $data['user_id'];
        $admin_str = "SELECT * FROM leadership WHERE user='$un';";
        $admin_query = mysqli_query($conn,$admin_str);
        $admin_present = mysqli_num_rows($admin_query) > 0;
        $change_reset = "UPDATE users SET reset=null WHERE user_id='$change_reset_id';";
        $make_change = mysqli_query($conn,$change_reset);
        //check if user has admin privileges - customize to match admin information on your site
        if ($admin_present) {
            $admin_data = mysqli_fetch_assoc($admin_query);
            $_SESSION['leader'] = true;
            $_SESSION['second'] = $admin_data['second_email'];
            $_SESSION['phone'] = $admin_data['phone'];
            $_SESSION['points'] = $admin_data['rank'];
            }
        }
    unset($_POST);
    }
if (isset($_POST['log_out'])) {
    session_unset();
    session_destroy();
    session_start();
    $l = -2;
    unset($_POST);
    }
if (isset($_POST['sign_up'])) {
    session_unset();
    session_destroy();
    session_start();
    $l = -3;
    unset($_POST);
    }
if (isset($_POST['add_usr'])) {
    $first = mysqli_real_escape_string($conn,$_POST['first']);
    $last = mysqli_real_escape_string($conn,$_POST['last']);
    $user = mysqli_real_escape_string($conn,$_POST['usrname']);
    $email = mysqli_real_escape_string($conn,$_POST['email']);
    $p1 = mysqli_real_escape_string($conn,$_POST['psswrd1']);
    $p2 = mysqli_real_escape_string($conn,$_POST['psswrd2']);
    if ($p1 !== $p2) {
        $l = -3;
        } else {
            $hash = password_hash($p1,PASSWORD_DEFAULT);
            if (empty($first) || empty($last) || empty($user) || 
                empty($email) || empty($p1)) {
                $message = "empty fields";
            } else {
            //Check for allowable characters
                if (!preg_match("/^[a-zA-Z]*$/",$first) || 
                    !preg_match("/^[a-zA-Z]*$/",$last)) {
                    $message = "invalid characters";
                } else {
                    if (!filter_var($email,FILTER_VALIDATE_EMAIL)) {
                        $message = "invalid email";
                    } else {
                    $sql = "SELECT * FROM users WHERE user_name='$user'";
                    $result = mysqli_query($conn,$sql);
                    $rows = mysqli_num_rows($result);
                        if ($rows > 0) {
                        $message = "username taken";
                        } else {
                            $sql = "INSERT INTO users (user_first,user_last,
                            user_name,user_email,user_password,reset) VALUES ('$first',
                            '$last','$user','$email','$hash',null);";
                            $result = mysqli_query($conn,$sql);
                            $message = "successful sign up, now log in!";
                            $l = -4;
                        }
                    }
                }
            }
        }
    }
if (isset($_POST['recover'])) {
    session_unset();
    session_destroy();
    session_start();
    unset($_POST['recover']);
    $l = -5;
    $message = "INPUT YOUR USERNAME BELOW TO RECOVER YOUR ACCOUNT";
    }
if (isset($_POST['submit_recover'])) {
    session_unset();
    session_destroy();
    session_start();
    unset($_POST['submit_recover']);
    $l = -6;
    $usrnm = mysqli_real_escape_string($conn,$_POST['recover_username']);
    $recover_str = "SELECT * FROM users WHERE user_name='$usrnm' LIMIT 1;";
    $recover_query = mysqli_query($conn,$recover_str);
    $recover_data = mysqli_fetch_assoc($recover_query);
    $recover_email = $recover_data['user_email'];
    $recover_id = $recover_data['user_id'];
    $message = "RECOVERY EMAIL SENT TO " . $recover_email;
    function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
    $string = generateRandomString();
    $subject = "REQUESTED PASSWORD RESET";
    $body = "A request has been created to reset the password in your account.\n\r" .
    "If you did not initiate this process, log in now to secure your accound.\n\r" .
    "The reset code is: " . $string;
    Mail($recover_email,$subject,$body,"From: MyEmail@MyEmailHost.com");
    $set_reset = "UPDATE users SET reset='$string' WHERE user_id='$recover_id';";
    $set_reset_query = mysqli_query($conn,$set_reset);
    }
if (isset($_POST['submit_new_password'])) {
    session_unset();
    session_destroy();
    session_start();
    unset($_POST['submit_new_password']);
    $l = -7;
    $message = "PASSWORDS DID NOT MATCH";
    $np1 = mysqli_real_escape_string($conn,$_POST['np1']);
    $np2 = mysqli_real_escape_string($conn,$_POST['np2']);
    if ($np1 === $np2) {
        $message = "PASSWORD WAS NOT CHANGED SUCCESSFULLY";
        $changed_hash = password_hash($np1,PASSWORD_DEFAULT);
        $ch_id = mysqli_real_escape_string($conn,$_POST['id']);
        $ch_code = mysqli_real_escape_string($conn,$_POST['code']);
        $change_psswrd_string = "UPDATE users SET user_password='$changed_hash' WHERE user_id='$ch_id' AND reset='$ch_code';";
        $ch_query = mysqli_query($conn,$change_psswrd_string);
        if (!$ch_query) {
            $message = "AN ERROR OCCURRED. PLEASE TRY AGAIN LATER";
        }
        if ($ch_query) {
            $message = "PASSWORD WAS SUCCESSFULLY CHANGED";
            }
    }
}
//generate appropriate form and content
//visible form is the form
//invisible form is the set of buttons that changes the form
//messages include logged in, etc.
$form_data = "";
if ($l === -1) {
$form_data =<<<_FORMDATA
<div id="messages_login">
<p>Log In</p>
</div>
<div id="main_login">
    <form method="POST" action="">
        <input name="username" id="username"><br>
        <input type="password" name="password" id="password"><br>
        <button type="submit" name="log_in">SUBMIT</button>
    </form>
</div><br>
<div id="links_login">
    <form method="POST" action="">
        <button type="submit" name="sign_up">SIGN UP</button>
    </form><br>
    <form method="POST" action="">
        <button type="submit" name="recover">RECOVER</button>
    </form>
</div>
_FORMDATA;
}
if ($l === 1) {
$form_data =<<<_FORMDATA
<div id="messages_login">
<p>YOU ARE SUCCESSFULLY LOGGED IN!</p>
</div>
<div id="links_login">
    <form method="POST" action="">
        <button type="submit" name="log_out" style="margin-top:20px;">LOG OUT</button>
    </form>
</div>
_FORMDATA;
}
if ($l === -2) {
$form_data =<<<_FORMDATA
<div id="messages_login">
<p>YOU ARE SUCCESSFULLY LOGGED OUT!</p>
</div>
<div id="links_login">
    <form method="POST" action="">
        <button type="submit" name="reload" style="margin-top:20px;">LOG IN PAGE</button>
    </form>
</div>
_FORMDATA;
}
if ($l === -3) {
$form_data =<<<_FORMDATA
<div id="messages_login">
<p>SIGN UP</p>
</div>
<div id="main_login">
    <form method="POST" action="">
        <input placeholder="username" name="usrname" type="text" id="usrname"><br>
        <input placeholder="first name" name="first" type="text" id="first"><br>
        <input placeholder="last name" name="last" type="text" id="last"><br>
        <input placeholder="email" name="email" type="text" id="email"><br>
        <input oninput="colors()" placeholder="password" name="psswrd1" type="password" id="psswrd1"><br>
        <input oninput="colors()" placeholder="confirm password" name="psswrd2" type="password" id="psswrd2"><br>
        <button type="submit" name="add_usr">SIGN UP</button><br>
    </form>
</div>
<script>
    function colors() {
        var p1 = document.getElementById('psswrd1');
        var p2 = document.getElementById('psswrd2');
        if (p1.value == '' || p2.value == '') {
            p1.style.backgroundColor = 'white';
            p2.style.backgroundColor = 'white';
        } else if (p1.value === p2.value) {
            p1.style.backgroundColor = 'lightGreen';
            p2.style.backgroundColor = 'lightGreen';
        }
    }
</script>
<div id="links_login">
    <form method="POST" action="">
        <button type="submit" name="reload">GO BACK TO LOG IN PAGE</button>
    </form>
</div>
_FORMDATA;
}
if ($l === -4) {
$form_data =<<<_FORMDATA
<div id="messages_login">
<p>$message</p>
</div>
<div id="links_login">
    <form method="POST" action="">
        <button type="submit" name="reload">BACK TO LOG IN PAGE</button>
    </form>
</div>
_FORMDATA;
}
if ($l === -5) {
$form_data =<<<_FORMDATA
<div id="messages_login">
<p>$message</p>
</div>
<div id="links_login">
    <form method="POST" action="" style="margin-top:10px;">
        <input placeholder="username" name="recover_username" type="text">
        <button type="submit" name="submit_recover" style="margin-top:10px;">SUBMIT</button>
    </form>
    <form method="POST" action="" style="margin-top:10px;">
        <button type="submit" name="reload">BACK TO LOG IN PAGE</button>
    </form>
</div>
_FORMDATA;
}
if ($l === -6) {
$form_data =<<<_FORMDATA
<div id="messages_login">
<p>$message</p>
</div>
<div id="links_login">
    <form method="POST" action="" style="margin-top:10px;">
        <input placeholder="recovery code sent to $recover_email" name="code" type="text">
        <input oninput="colors()" id="psswrd1" placeholder="new password" name="np1" type="password">
        <input oninput="colors()" id="psswrd2" placeholder="confirm new password" name="np2" type="password">
        <input type="hidden" name="id" value="$recover_id">
        <button type="submit" name="submit_new_password" style="margin-top:10px;">SUBMIT</button>
    </form>
    <form method="POST" action="" style="margin-top:10px;">
        <button type="submit" name="reload">BACK TO LOG IN PAGE</button>
    </form>
</div>
<script>
    function colors() {
        var p1 = document.getElementById('psswrd1');
        var p2 = document.getElementById('psswrd2');
        if (p1.value == '' || p2.value == '') {
            p1.style.backgroundColor = 'white';
            p2.style.backgroundColor = 'white';
        } else if (p1.value === p2.value) {
            p1.style.backgroundColor = 'lightGreen';
            p2.style.backgroundColor = 'lightGreen';
        }
    }
</script>
_FORMDATA;
}
if ($l === -7) {
$form_data =<<<_FORMDATA
<div id="messages_login">
<p>$message</p>
</div>
<div id="links_login">
    <form method="POST" action="">
        <button type="submit" name="reload" style="margin-top:20px;">LOG IN PAGE</button>
    </form>
</div>
_FORMDATA;
}
//ensure previous posts are unset
unset($_POST);
//echo document
echo<<<_DOCUMENT
<!DOCTYPE html>
<html lang="en">
    <head>
        <title>My Title</title>
        <link rel="stylesheet" href="style.css">
        <link rel="shortcut icon" type="image/png" href="/MyLogo.png"/>
        <link rel="shortcut icon" type="image/png" href="http://www.example.com/MyLogo.png"/>
        <style>
        </style>
    </head>
    <body>
        <div id="header_login" style="position:fixed;top:0px;">
            <h1>USER ACCOUNTS</h1>
        </div>
        <div id="center_login" style="margin-top:80px">
            $form_data
        </div>
        <div id="home_link" style="display:block;margin-left:auto;margin-right:auto;width:3%;margin-top:20px;">
            <a href="index.php$q" style="display:block;margin-left:auto;margin-right:auto;color:#999999;text-align:center">Home</a>
        </div>
        <div id="html_footer" style="margin-top:10px;position:relative;bottom:0px;color:white;background-color:#dddddd;width:100%;">
            <h4 style="padding:15px; font-size:15px; font-family:arial;">&copy; MyCopyRightInfo</h4>
        </div>
    </body>
</html>
_DOCUMENT;
?>

<?php
session_start();

if (isset($_SESSION['admin_name'])) {
    header('Location: admin/index.php');
    exit;
}

if (isset($_SESSION['uid'])) {
    header('Location: ../index.php');
    exit;
}

$login_error = isset($_GET['error']) ? 'Email or password is incorrect.' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Login</title>

    <link rel="stylesheet" href="./assets/fonts/material-icon/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" href="./assets/css/style.css">
    <style>
        @media (max-width: 767px) {
            body.shared-login-page {
                min-width: 320px;
                min-height: 100vh;
                margin: 0;
                background-color: #f3f6fb;
                background-image: linear-gradient(rgba(16, 42, 86, .18), rgba(16, 42, 86, .18)), url("./assets/images/signup-image.jpg");
                background-position: center;
                background-repeat: no-repeat;
                background-size: cover;
                background-attachment: fixed;
            }

            .shared-login-page > .main {
                display: flex;
                min-height: 100vh;
                min-height: 100dvh;
                align-items: center;
                padding: 24px 14px !important;
                box-sizing: border-box;
                background: transparent;
            }

            .shared-login-page .sign-in,
            .shared-login-page .container {
                width: 100%;
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            .shared-login-page .container {
                border-radius: 0;
                background: transparent;
                box-shadow: none;
            }

            .shared-login-page .signin-content {
                display: block;
                width: 100%;
                margin: 0;
                padding: 0;
                overflow: visible;
                border-radius: 0;
                background: transparent;
                box-shadow: none;
                -webkit-backdrop-filter: none;
                backdrop-filter: none;
            }

            .shared-login-page .signin-image {
                display: none;
            }

            .shared-login-page .signin-form {
                width: 100%;
                margin: 0;
                padding: 34px 22px;
                box-sizing: border-box;
                background: transparent;
            }

            .shared-login-page .form-title {
                margin-bottom: 28px;
                color: #fff;
                font-size: 28px;
                text-align: left;
            }

            .shared-login-page .form-group label {
                color: #fff;
            }

            .shared-login-page .form-group {
                width: 100%;
                margin-bottom: 20px;
            }

            .shared-login-page .form-group input:not([type="submit"]) {
                width: 100%;
                min-height: 48px;
                padding: 10px 12px 10px 38px;
                border: 1px solid #dfe5ee;
                border-radius: 9px;
                box-sizing: border-box;
                font-size: 16px;
            }

            .shared-login-page .form-group input:not([type="submit"]) {
                background: rgba(255, 255, 255, .82);
            }

            .shared-login-page .form-group label {
                top: 14px;
                left: 12px;
            }

            .shared-login-page .form-group.form-button {
                margin-top: 26px;
                text-align: left;
            }

            .shared-login-page .form-submit {
                display: block;
                width: 100%;
                min-height: 48px;
                margin: 0;
                padding: 12px 18px;
                border: 0;
                border-radius: 9px;
                box-sizing: border-box;
                background: #173e78;
                color: #fff;
                font-size: 16px;
            }

            .shared-login-page .alert {
                overflow-wrap: anywhere;
            }

            .shared-login-page .signup-image-link {
                color: #fff;
                text-shadow: 0 1px 5px rgba(0, 0, 0, .45);
            }
        }
    </style>
</head>
<body class="shared-login-page">

    <div class="main" style="padding-top: 90px;">
        <section class="sign-in">
            <div class="container">
                <div class="signin-content">
                    <div class="signin-image">
                        <figure><img src="./assets/images/signup-image.jpg" alt="sing up image"></figure>
                        <a href="../index.php" class="signup-image-link">Back To Home</a>
                    </div>

                    <div class="signin-form">
                        <h2 class="form-title">Login</h2>
                        <form class="register-form" id="login-form" action="../login.php" method="post">
                            <?php if ($login_error !== ''): ?>
                                <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($login_error, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>
                            <div class="form-group">
                                <label for="your_name"><i class="zmdi zmdi-account material-icons-name"></i></label>
                                <input type="email" name="email" id="your_name" placeholder="Email address" autocomplete="username" required/>
                            </div>
                            <div class="form-group">
                                <label for="your_pass"><i class="zmdi zmdi-lock"></i></label>
                                <input type="password" name="password" id="your_pass" placeholder="Password" autocomplete="current-password" required/>
                            </div>

                            <div class="form-group form-button">
                                <input type="submit" name="login" id="signin" class="form-submit" value="Log in"/>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </div>

</body>
</html>

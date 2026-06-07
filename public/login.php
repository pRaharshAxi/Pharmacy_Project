<?php
session_start();
require_once '../config/db_connect.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare(
        "SELECT USER_ID, ROLE, F_NAME, L_NAME, PASSWORD 
         FROM users WHERE EMAIL = ?"
    );
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Check if password is hashed (starts with $2y$)
        $isPasswordValid = false;
        if (strpos($user['PASSWORD'], '$2y$') === 0) {
            // Hashed password - use password_verify
            $isPasswordValid = password_verify($password, $user['PASSWORD']);
        } else {
            // Plain text password (legacy users)
            $isPasswordValid = ($password === $user['PASSWORD']);
        }

        if ($isPasswordValid) {
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['USER_ID'];
            $_SESSION['role']    = strtoupper($user['ROLE']);
            $_SESSION['f_name']  = $user['F_NAME'];
            $_SESSION['l_name']  = $user['L_NAME'];

            switch ($_SESSION['role']) {
                case 'CUSTOMER':
                    header("Location: dashboard_customer.php");
                    exit();
                case 'PHARMACIST':
                    header("Location: dashboard_pharmacist.php");
                    exit();
                case 'ADMIN':
                    header("Location: dashboard_admin.php");
                    exit();
                default:
                    $error = "Unknown user role.";
            }

        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Invalid email or password.";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | MedCare</title>
    <!-- Google Fonts: Montserrat -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Montserrat', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f7f6f3;
        }
        .main-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f7f6f3;
        }
        .card {
            display: flex;
            width: 900px;
            min-height: 540px;
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 4px 32px 0 rgba(60, 72, 88, 0.10);
            overflow: hidden;
        }
        .card-illustration {
            flex: 1.1;
            background: linear-gradient(135deg, #F5F1E8 0%, #E8DCC8 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 32px;
        }
        .card-illustration img {
            width: 260px;
            max-width: 100%;
            margin-bottom: 32px;
            box-shadow: none;
        }
        .card-illustration h2 {
            font-size: 2.1rem;
            font-weight: 700;
            color: #1A1A1A;
            margin-bottom: 12px;
            text-align: center;
        }
        .card-illustration p {
            font-size: 1.05rem;
            color: #4B5563;
            text-align: center;
            max-width: 320px;
        }
        .card-form {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px 32px;
            background: #fff;
        }
        .form-wrapper {
            width: 100%;
            max-width: 340px;
        }
        .form-wrapper h2 {
            color: #1A1A1A;
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 28px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            color: #222;
            font-size: 0.93rem;
            margin-bottom: 6px;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background-color: #f7f6f3;
            color: #222;
            font-size: 0.98rem;
            transition: border 0.2s;
            font-family: inherit;
        }
        .form-group input:focus {
            outline: none;
            border-color: #1A1A1A;
        }
        .error-msg {
            background: rgba(220, 38, 38, 0.10);
            border: 1px solid rgba(220, 38, 38, 0.3);
            color: #FF6B6B;
            padding: 10px 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 0.93rem;
            text-align: center;
        }
        .forgot-password {
            text-align: right;
            margin-bottom: 18px;
        }
        .forgot-password a {
            color: #1A1A1A;
            text-decoration: underline;
            font-size: 0.93rem;
            font-weight: 500;
            transition: color 0.2s;
        }
        .forgot-password a:hover {
            color: #4B5563;
        }
        .login-btn {
            width: 100%;
            padding: 12px;
            background-color: #1A1A1A;
            color: #fff;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, transform 0.2s;
            margin-bottom: 14px;
        }
        .login-btn:hover {
            background-color: #222;
            transform: translateY(-2px);
        }
        .signup-link {
            text-align: center;
            color: #4B5563;
            font-size: 0.93rem;
        }
        .signup-link a {
            color: #1A1A1A;
            text-decoration: underline;
            font-weight: 600;
            transition: color 0.2s;
        }
        .signup-link a:hover {
            color: #4B5563;
        }
        .home-link {
            position: absolute;
            top: 18px;
            right: 24px;
            color: #4B5563;
            text-decoration: none;
            font-size: 0.93rem;
            transition: color 0.2s;
        }
        .home-link:hover {
            color: #1A1A1A;
        }
        @media (max-width: 1024px) {
            .card {
                width: 98vw;
            }
            .card-illustration {
                padding: 32px 12px;
            }
            .card-form {
                padding: 32px 12px;
            }
        }
        @media (max-width: 768px) {
            .main-container {
                padding: 16px 0;
            }
            .card {
                flex-direction: column;
                width: 98vw;
                min-height: unset;
            }
            .card-illustration {
                min-height: 180px;
                padding: 24px 8px 12px 8px;
            }
            .card-form {
                padding: 24px 8px 32px 8px;
            }
        }
    </style>
</head>

<body style="background: url('../assets/background.jpg') center top/cover no-repeat fixed, #23232a; font-family: 'Montserrat', Arial, sans-serif;">
<div style="display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #23232a;">
    <div style="display: flex; width: 900px; min-height: 540px; background: #fff; border-radius: 32px; overflow: hidden; box-shadow: 0 8px 48px rgba(0,0,0,0.13);">
        <!-- Left Side -->
        <div style="flex: 1; background: #fff; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 48px 32px;">
            <img src="../assets/login.jpg" alt="Login Illustration" style="width: 320px; max-width: 100%; border-radius: 18px; margin-bottom: 32px;">
        </div>
        <!-- Right Side -->
        <div style="flex: 1; background: #f5f5f5; display: flex; flex-direction: column; justify-content: center; align-items: center; position: relative; padding: 48px 32px;">
            <a href="index.php" style="position: absolute; top: 24px; right: 32px; color: #23232a; text-decoration: none; font-size: 1rem; opacity: 0.7;">← Home</a>
            <div style="width: 100%; max-width: 340px;">
                <div style="display: flex; flex-direction: column; align-items: center; margin-bottom: 24px;">
                  <div style="font-size: 1.18rem; color: #23232a; background: #eaeaea; border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; margin-bottom: 8px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="black">
  <path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm0 2c-3.33 0-10 1.67-10 5v3h20v-3c0-3.33-6.67-5-10-5z"/>
</svg>
</div>
                  <h2 style="font-size: 1.05rem; color: #23232a; font-weight: 700; margin-bottom: 2px; text-align: center;">Welcome Back</h2>
                  <br>
                  <div style="color: #888; font-size: 0.85rem; text-align: center;">Access your pharmacy workspace and stay in control of daily operations</div>
                </div>
                <?php if (!empty($error)) : ?>
                    <div class="error-msg" style="background: rgba(220,38,38,0.15); border: 1px solid rgba(220,38,38,0.5); color: #FF6B6B; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; text-align: center;"> <?= htmlspecialchars($error) ?> </div>
                <?php endif; ?>
                <form method="POST">
                    <div class="form-group" style="margin-bottom: 13px;">
                        <label style="display: block; color: #23232a; font-size: 0.77rem; margin-bottom: 4px; font-weight: 500;">Email</label>
                        <input type="email" name="email" placeholder="Enter your email" required autofocus style="width: 100%; padding: 8px 11px; border: 1px solid #d1d5db; border-radius: 12px; background: #f8fafc; color: #23232a; font-size: 0.89rem;">
                    </div>
                    <div class="form-group" style="margin-bottom: 32px;">
                        <label style="display: block; color: #23232a; font-size: 0.77rem; margin-bottom: 4px; font-weight: 500;">Password</label>
                        <input type="password" name="password" placeholder="Enter your password" required style="width: 100%; padding: 8px 11px; border: 1px solid #d1d5db; border-radius: 12px; background: #f8fafc; color: #23232a; font-size: 0.89rem;">
                    </div>
                    <button type="submit" class="login-btn" style="width: 100%; padding: 13px; background-color: #23232a; color: #fff; border: none; border-radius: 50px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; margin-bottom: 14px;">Log in</button>
                </form>
                <div style="text-align: center; color: #888; font-size: 0.93rem;">Don't have an account? <a href="register.php" style="color: #23232a; text-decoration: underline; font-weight: 600;">Sign up</a></div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
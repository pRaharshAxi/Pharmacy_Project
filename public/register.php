<?php
session_start();
require_once '../config/db_connect.php';

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fname    = trim($_POST['fname']);
    $lname    = trim($_POST['lname']);
    $email    = trim($_POST['email']);
    $contact  = trim($_POST['contact']);
    $password = $_POST['password'];
    $role     = trim($_POST['role']);

    // Validate role
    $valid_roles = ['PHARMACIST', 'CUSTOMER'];
    if (!in_array($role, $valid_roles)) {
        $error = "Invalid role selected.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {

        // Check if email already exists
        $check = $conn->prepare("SELECT USER_ID FROM users WHERE EMAIL = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Email is already registered!";
        } else {
            // Generate User ID
            $new_user_id = 'U' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Start transaction
            $conn->begin_transaction();

            try {
                // Insert user
                $insert = $conn->prepare(
                    "INSERT INTO users (USER_ID, F_NAME, L_NAME, EMAIL, CONTACT_NUM, ROLE, PASSWORD)
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                $insert->bind_param(
                    "sssssss",
                    $new_user_id,
                    $fname,
                    $lname,
                    $email,
                    $contact,
                    $role,
                    $hashedPassword
                );
                $insert->execute();
                $insert->close();

                // Role-specific inserts
                if ($role === 'CUSTOMER') {
                    // Get customer-specific data
                    $country = trim($_POST['country']);
                    $state = trim($_POST['state']);
                    $city = trim($_POST['city']);
                    $street = trim($_POST['street']);
                    $gender = $_POST['gender'];
                    $b_year = intval($_POST['b_year']);
                    $b_month = intval($_POST['b_month']);
                    $b_date = intval($_POST['b_day']);

                    // Calculate age
                    $current_year = date('Y');
                    $current_month = date('n');
                    $current_day = date('j');
                    
                    $age = $current_year - $b_year;
                    if ($current_month < $b_month || ($current_month == $b_month && $current_day < $b_date)) {
                        $age--;
                    }

                    // Insert into customer table
                    $customer_insert = $conn->prepare(
                        "INSERT INTO customer (USER_ID, AGE, COUNTRY, STATE, CITY, STREET, GENDER, B_YEAR, B_MONTH, B_DATE)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                    );
                    $customer_insert->bind_param(
                        "sisssssiis",
                        $new_user_id,
                        $age,
                        $country,
                        $state,
                        $city,
                        $street,
                        $gender,
                        $b_year,
                        $b_month,
                        $b_date
                    );
                    $customer_insert->execute();
                    $customer_insert->close();

                } elseif ($role === 'PHARMACIST') {
                    // Get pharmacist-specific data
                    $years_exp = intval($_POST['years_exp']);
                    $license_no = trim($_POST['license_no']);

                    // Insert into pharmacist table
                    $pharmacist_insert = $conn->prepare(
                        "INSERT INTO pharmacist (USER_ID, YEARS_OF_EXP, LICENSE_NO)
                         VALUES (?, ?, ?)"
                    );
                    $pharmacist_insert->bind_param(
                        "sis",
                        $new_user_id,
                        $years_exp,
                        $license_no
                    );
                    $pharmacist_insert->execute();
                    $pharmacist_insert->close();
                }

                // Commit transaction
                $conn->commit();
                $success = "Registration successful! 🎉 You can now login.";

            } catch (Exception $e) {
                // Rollback on error
                $conn->rollback();
                $error = "Registration failed: " . $e->getMessage();
            }
        }
        $check->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | MedCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            overflow-x: hidden;
        }

        .register-container {
            display: flex;
            height: 100vh;
        }
        .register-left {
            flex: 1;
            background: linear-gradient(135deg, #F5F1E8 0%, #E8DCC8 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            padding: 60px;
            position: relative;
            overflow: hidden;
        }
        .logo-section {
            position: absolute;
            top: 30px;
            left: 50px;
            display: flex;
            align-items: center;
            gap: 16px;
            cursor: pointer;
            text-decoration: none;
            transition: opacity 0.3s ease;
        }
        .logo-section:hover {
            opacity: 0.8;
        }
        .logo-img {
            height: 60px;
            width: auto;
            object-fit: contain;
        }
        .logo-text {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1A1A1A;
        }
        .register-left-decoration {
            position: absolute;
            font-size: 150px;
            opacity: 0.15;
            bottom: -50px;
            left: -50px;
            animation: float 3s ease-in-out infinite;
        }
        .register-left-decoration.top-right {
            bottom: auto;
            left: auto;
            top: 80px;
            right: 60px;
            font-size: 120px;
            opacity: 0.2;
        }
        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-30px);
            }
        }
        .register-left h1 {
            font-size: 2.8rem;
            color: #1A1A1A;
            margin: 0 0 30px 0;
            font-weight: 700;
            max-width: 500px;
            line-height: 1.2;
        }
        .register-left p {
            font-size: 1.05rem;
            color: #4B5563;
            max-width: 450px;
            line-height: 1.6;
            margin: 0;
        }
        .register-left-img {
            width: 100%;
            max-width: 400px;
            border-radius: 18px;
            margin-bottom: 32px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.10);
        }
        .register-right {
            flex: 1;
            background: #1A1A1A;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px;
            position: relative;
        }
        .register-form-wrapper {
            width: 100%;
            max-width: 420px;
        }
        .register-form-wrapper h2 {
            color: white;
            font-size: 1.8rem;
            margin: 0 0 40px 0;
            font-weight: 600;
            text-align: center;
        }
        .register-form-wrapper .subtitle {
            color: #B0B0B0;
            text-align: center;
            font-size: 0.9rem;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 10px;
        }

        .form-group label {
            display: block;
            color: #E0E0E0;
            font-size: 0.85rem;
            margin-bottom: 3px;
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #4B5563;
            border-radius: 10px;
            background-color: #2A2A2A;
            color: white;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-group input::placeholder {
            color: #808080;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: white;
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .form-row.three-col {
            grid-template-columns: 1fr 1fr 1fr;
        }

        .form-row .form-group {
            margin-bottom: 18px;
        }

        .error-msg {
            background: rgba(220, 38, 38, 0.15);
            border: 1px solid rgba(220, 38, 38, 0.5);
            color: #FF6B6B;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.85rem;
            text-align: center;
        }

        .success-msg {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.5);
            color: #6EE7B7;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.85rem;
            text-align: center;
        }

        .register-btn {
            width: 100%;
            padding: 13px;
            background-color: white;
            color: #1A1A1A;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            margin-bottom: 20px;
        }

        .register-btn:hover {
            background-color: #E8E8E8;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(255, 255, 255, 0.2);
        }

        .login-link {
            text-align: center;
            color: #B0B0B0;
            font-size: 0.9rem;
        }

        .login-link a {
            color: white;
            text-decoration: underline;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
            color: #E0E0E0;
        }

        .home-link {
            position: absolute;
            top: 20px;
            right: 20px;
            color: #E0E0E0;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .home-link:hover {
            color: white;
        }

        .success-links {
            text-align: center;
            margin-top: 20px;
        }

        .success-links a {
            display: block;
            color: white;
            text-decoration: underline;
            font-weight: 600;
            margin: 10px 0;
            transition: color 0.3s ease;
        }

        .success-links a:hover {
            color: #E0E0E0;
        }

        /* Role-specific fields */
        .role-specific-fields {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .role-specific-fields.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .section-divider {
            margin: 25px 0;
            padding-top: 20px;
            border-top: 1px solid #4B5563;
        }

        .section-title {
            color: #E0E0E0;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .register-left {
                flex: 0.8;
            }

            .register-right {
                flex: 1.2;
            }

            .register-left h1 {
                font-size: 2.2rem;
            }
        }

        @media (max-width: 768px) {
            .register-container {
                flex-direction: column;
            }

            .register-left {
                padding: 40px;
                justify-content: flex-start;
                padding-top: 100px;
                min-height: 40vh;
            }

            .logo-section {
                top: 20px;
                left: 20px;
            }

            .register-left h1 {
                font-size: 2rem;
                margin-bottom: 20px;
            }

            .register-left p {
                font-size: 0.95rem;
            }

            .register-right {
                padding: 40px 20px;
                min-height: 60vh;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .form-row.three-col {
                grid-template-columns: 1fr;
            }

            .home-link {
                top: 10px;
                right: 10px;
            }
        }
    </style>
</head>

<body style="background: #23232a; font-family: 'Montserrat', Arial, sans-serif;">

<div style="display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #23232a;">
    <div style="display: flex; width: 900px; min-height: 540px; background: #fff; border-radius: 32px; overflow: hidden; box-shadow: 0 8px 48px rgba(0,0,0,0.13);">
        <!-- Left Side -->
        <div style="flex: 1; background: #fff; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 48px 32px;">
            <img src="../assets/register.jpg" alt="Register Illustration" style="width: 320px; max-width: 100%; border-radius: 18px; margin-bottom: 32px;">
        </div>
        <!-- Right Side -->
        <div style="flex: 1; background: #f5f5f5; display: flex; flex-direction: column; justify-content: center; align-items: center; position: relative; padding: 48px 32px;">
            <a href="index.php" style="position: absolute; top: 24px; right: 32px; color: #23232a; text-decoration: none; font-size: 1rem; opacity: 0.7;">← Home</a>
            <div style="width: 100%; max-width: 340px;">
                  <div style="display: flex; flex-direction: column; align-items: center; margin-bottom: 24px;">
                  <div style="font-size: 1.18rem; color: #23232a; background: #eaeaea; border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; margin-bottom: 8px;">+</div>
                  <h2 style="font-size: 1.05rem; color: #23232a; font-weight: 700; margin-bottom: 2px; text-align: center;">Create your account!</h2>
                  <div style="color: #888; font-size: 0.85rem; text-align: center;">Please enter your details</div>
                </div>
                <?php if ($error): ?>
                    <div class="error-msg" style="background: rgba(220,38,38,0.15); border: 1px solid rgba(220,38,38,0.5); color: #FF6B6B; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; text-align: center;"> <?= htmlspecialchars($error) ?> </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="success-msg" style="background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.5); color: #16A34A; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; text-align: center;"> <?= htmlspecialchars($success) ?> </div>
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="login.php" style="color: #23232a; text-decoration: underline; font-weight: 600; margin: 10px 0; display: block;">Go to Login →</a>
                        <a href="index.php" style="color: #23232a; text-decoration: underline; font-weight: 600; margin: 10px 0; display: block;">← Back to Home</a>
                    </div>
                <?php else: ?>
                <form method="POST" id="registerForm">
                    <div class="form-group" style="margin-bottom: 13px;">
                        <label style="display: block; color: #23232a; font-size: 0.77rem; margin-bottom: 4px; font-weight: 500;">First Name *</label>
                        <input type="text" name="fname" placeholder="First name" required style="width: 100%; padding: 8px 11px; border: 1px solid #d1d5db; border-radius: 12px; background: #f8fafc; color: #23232a; font-size: 0.89rem;">
                    </div>
                    <div class="form-group" style="margin-bottom: 13px;">
                        <label style="display: block; color: #23232a; font-size: 0.77rem; margin-bottom: 4px; font-weight: 500;">Last Name *</label>
                        <input type="text" name="lname" placeholder="Last name" required style="width: 100%; padding: 8px 11px; border: 1px solid #d1d5db; border-radius: 12px; background: #f8fafc; color: #23232a; font-size: 0.89rem;">
                    </div>
                    <div class="form-group" style="margin-bottom: 13px;">
                        <label style="display: block; color: #23232a; font-size: 0.77rem; margin-bottom: 4px; font-weight: 500;">Email *</label>
                        <input type="email" name="email" placeholder="Enter your email" required style="width: 100%; padding: 8px 11px; border: 1px solid #d1d5db; border-radius: 12px; background: #f8fafc; color: #23232a; font-size: 0.89rem;">
                    </div>
                    <div class="form-group" style="margin-bottom: 13px;">
                        <label style="display: block; color: #23232a; font-size: 0.77rem; margin-bottom: 4px; font-weight: 500;">Contact Number *</label>
                        <input type="text" name="contact" placeholder="Phone number" required style="width: 100%; padding: 8px 11px; border: 1px solid #d1d5db; border-radius: 12px; background: #f8fafc; color: #23232a; font-size: 0.89rem;">
                    </div>
                    <div class="form-group" style="margin-bottom: 13px;">
                        <label style="display: block; color: #23232a; font-size: 0.77rem; margin-bottom: 4px; font-weight: 500;">Password *</label>
                        <input type="password" name="password" placeholder="Create a password" required style="width: 100%; padding: 8px 11px; border: 1px solid #d1d5db; border-radius: 12px; background: #f8fafc; color: #23232a; font-size: 0.89rem;">
                    </div>
                    <div class="form-group" style="margin-bottom: 13px;">
                        <label style="display: block; color: #23232a; font-size: 0.77rem; margin-bottom: 4px; font-weight: 500;">Select Your Role *</label>
                        <select name="role" id="roleSelect" required onchange="showRoleFields()" style="width: 100%; padding: 8px 11px; border: 1px solid #d1d5db; border-radius: 12px; background: #f8fafc; color: #23232a; font-size: 0.89rem;">
                            <option value="">-- Select Your Role --</option>
                            <option value="PHARMACIST">Pharmacist</option>
                            <option value="CUSTOMER">Customer</option>
                        </select>
                    </div>
                    <div id="customerFields" class="role-specific-fields" style="display:none;">
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="display: block; color: #23232a; font-size: 0.95rem; margin-bottom: 6px; font-weight: 500;">Country *</label>
                            <input type="text" name="country" placeholder="e.g., Sri Lanka" style="width: 100%; padding: 14px 18px; border: 1px solid #d1d5db; border-radius: 12px; background: #f8fafc; color: #23232a; font-size: 1rem;">
                        </div>
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="display: block; color: #23232a; font-size: 0.95rem; margin-bottom: 6px; font-weight: 500;">State/Province *</label>
                            <input type="text" name="state" placeholder="e.g., Western" style="width: 100%; padding: 14px 18px; border: 1px solid #d1d5db; border-radius: 12px; background: #f8fafc; color: #23232a; font-size: 1rem;">
                        </div>
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="display: block; color: #23232a; font-size: 0.95rem; margin-bottom: 6px; font-weight: 500;">City *</label>
                            <input type="text" name="city" placeholder="e.g., Colombo" style="width: 100%; padding: 14px 18px; border: 1px solid #d1d5db; border-radius: 12px; background: #f8fafc; color: #23232a; font-size: 1rem;">
                        </div>
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="display: block; color: #23232a; font-size: 0.95rem; margin-bottom: 6px; font-weight: 500;">Street Address *</label>
                            <input type="text" name="street" placeholder="e.g., Main Road" style="width: 100%; padding: 14px 18px; border: 1px solid #d1d5db; border-radius: 12px; background: #f8fafc; color: #23232a; font-size: 1rem;">
                        </div>
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="display: block; color: #23232a; font-size: 0.95rem; margin-bottom: 6px; font-weight: 500;">Gender *</label>
                            <select name="gender" style="width: 100%; padding: 14px 18px; border: 1px solid #d1d5db; border-radius: 12px; background: #f8fafc; color: #23232a; font-size: 1rem;">
                                <option value="">-- Select Gender --</option>
                                <option value="MALE">Male</option>
                                <option value="FEMALE">Female</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="display: block; color: #23232a; font-size: 0.95rem; margin-bottom: 6px; font-weight: 500;">Date of Birth *</label>
                            <input type="number" name="b_day" placeholder="Day (1-31)" min="1" max="31" style="width: 32%; display: inline-block; margin-right: 2%; padding: 14px 10px;">
                            <select name="b_month" style="width: 32%; display: inline-block; margin-right: 2%; padding: 14px 10px;">
                                <option value="">Month</option>
                                <option value="1">January</option>
                                <option value="2">February</option>
                                <option value="3">March</option>
                                <option value="4">April</option>
                                <option value="5">May</option>
                                <option value="6">June</option>
                                <option value="7">July</option>
                                <option value="8">August</option>
                                <option value="9">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                            </select>
                            <input type="number" name="b_year" placeholder="Year" min="1900" max="<?= date('Y') ?>" style="width: 32%; display: inline-block; padding: 14px 10px;">
                        </div>
                    </div>
                    <div id="pharmacistFields" class="role-specific-fields" style="display:none;">
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="display: block; color: #23232a; font-size: 0.95rem; margin-bottom: 6px; font-weight: 500;">Years of Experience *</label>
                            <input type="number" name="years_exp" placeholder="e.g., 5" min="0" style="width: 100%; padding: 14px 18px; border: 1px solid #d1d5db; border-radius: 12px; background: #f8fafc; color: #23232a; font-size: 1rem;">
                        </div>
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="display: block; color: #23232a; font-size: 0.95rem; margin-bottom: 6px; font-weight: 500;">License Number *</label>
                            <input type="text" name="license_no" placeholder="e.g., PH123456" style="width: 100%; padding: 14px 18px; border: 1px solid #d1d5db; border-radius: 12px; background: #f8fafc; color: #23232a; font-size: 1rem;">
                        </div>
                    </div>
                    <button type="submit" class="login-btn" style="width: 100%; padding: 14px; background: #23232a; color: #fff; border: none; border-radius: 50px; font-size: 1rem; font-weight: 600; cursor: pointer; margin-top: 10px; margin-bottom: 20px;">Create account</button>
                </form>
                <div class="signup-link" style="text-align: center; color: #888; font-size: 0.95rem;">Already have an account? <a href="login.php" style="color: #23232a; text-decoration: underline; font-weight: 600;">Log in</a></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
    </div>
</div>

<script>
function showRoleFields() {
    const role = document.getElementById('roleSelect').value;
    const customerFields = document.getElementById('customerFields');
    const pharmacistFields = document.getElementById('pharmacistFields');
    // Hide all role-specific fields
    customerFields.style.display = 'none';
    pharmacistFields.style.display = 'none';
    // Remove required attribute from all role-specific inputs
    document.querySelectorAll('.role-specific-fields input, .role-specific-fields select').forEach(field => {
        field.removeAttribute('required');
    });
    // Show and set required for selected role fields
    if (role === 'CUSTOMER') {
        customerFields.style.display = 'block';
        document.querySelectorAll('#customerFields input, #customerFields select').forEach(field => {
            if (field.name !== 'gender') { // Gender is optional
                field.setAttribute('required', 'required');
            }
        });
    } else if (role === 'PHARMACIST') {
        pharmacistFields.style.display = 'block';
        document.querySelectorAll('#pharmacistFields input').forEach(field => {
            field.setAttribute('required', 'required');
        });
    }
}
</script>

</body>
</html>
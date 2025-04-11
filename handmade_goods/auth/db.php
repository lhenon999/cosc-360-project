<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';
session_regenerate_id(true);

echo "<script>console.log('db.php loaded');</script>";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    echo "<script>console.log('POST request received');</script>";

    // Register 
    if (isset($_POST["register"])) {
        echo "<script>console.log('Registering new user');</script>";
        $name = trim($_POST["full_name"]);
        $email = trim($_POST["email"]);
        $password = $_POST["password"];
        $user_type = 'normal';
        $profile_picture = "../assets/images/default_profile.png";

        $upload_dir = "../assets/images/uploads/profile_pictures/";

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
            $imageFileType = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
            $allowed_types = array("jpg", "jpeg", "png", "gif");
        
            if (in_array($imageFileType, $allowed_types)) {
                $file_name = "profile_" . uniqid() . "." . $imageFileType;
                $target_file = $upload_dir . $file_name;

                if (!file_exists($_FILES["profile_picture"]["tmp_name"])) {
                    header("Location: /~rsodhi03/cosc-360-project/handmade_goods/auth/register.php?error=temp_file_missing");
                    exit();
                }

                if (!move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                    header("Location: /~rsodhi03/cosc-360-project/handmade_goods/auth/register.php?error=file_upload_failed");
                    exit();
                } else {
                    $profile_picture = str_replace($_SERVER['DOCUMENT_ROOT'], '', $target_file);
                }
            } else {
                header("Location: /~rsodhi03/cosc-360-project/handmade_goods/auth/register.php?error=invalid_file");
                exit();
            }
            }
            
            $check = $conn->prepare("SELECT email FROM USERS WHERE email = ?");
            $check->bind_param("s", $email);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                header("Location: ../auth/register.php?error=email_taken&email=" . urlencode($email));
                exit();
            }
            $check->close();

            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            try {
                $stmt = $conn->prepare("INSERT INTO USERS (name, email, password, user_type, profile_picture) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $name, $email, $hashed_password, $user_type, $profile_picture);

                if ($stmt->execute()) {
                    $user_id = $stmt->insert_id;
                    $_SESSION["user_id"] = $user_id;
                    $_SESSION["email"] = $email;
                    $_SESSION["user_name"] = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                    $_SESSION["user_type"] = $user_type;
                    $_SESSION["profile_picture"] = $profile_picture;
                    
                    // Log registration event
                    $ip_address = $_SERVER['REMOTE_ADDR'];
                    $user_agent = $_SERVER['HTTP_USER_AGENT'];
                    $log_stmt = $conn->prepare("INSERT INTO ACCOUNT_ACTIVITY (user_id, event_type, ip_address, user_agent) VALUES (?, 'registration', ?, ?)");
                    $log_stmt->bind_param("iss", $user_id, $ip_address, $user_agent);
                    $log_stmt->execute();
                    $log_stmt->close();

                    $api_key = "api-DFEA151D81194B3EB9B6CF30891D53A5";
                    $email_data = [
                        "api_key" => $api_key,
                        "sender" => "handmadegoods@mail2world.com",
                        "to" => [$email],
                        "subject" => "Welcome to Handmade Goods",
                        "html_body" => "<h1>Hello $name,</h1><p>Thank you for signing up! Welcome to Handmade Goods.</p>",
                    ];

                    $ch = curl_init("https://api.smtp2go.com/v3/email/send");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($email_data));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'accept: application/json']);

                $response = curl_exec($ch);
                curl_close($ch);
                
                header("Location: /~rsodhi03/cosc-360-project/handmade_goods/pages/home.php");
                exit();
                } else {
                    header("Location: /~rsodhi03/cosc-360-project/handmade_goods/auth/register.php?error=registration_failed");
                    exit();
                }
                $stmt->close();
            } catch (Exception $e) {
                echo "Error: " . $e->getMessage();
                exit();
            }
    }

    // Login
    if (isset($_POST["login"])) {
        echo "<script>console.log('Loggin in user');</script>";
        $email = trim($_POST["email"]);
        $password = $_POST["password"];
        $remember = isset($_POST["remember"]); 

        if ($remember) {
            $token = bin2hex(random_bytes(32));
    
            $cookie_stmt = $conn->prepare("UPDATE USERS SET remember_token = ? WHERE email = ?");
            $cookie_stmt->bind_param("ss", $token, $email);
            
            if (!$cookie_stmt->execute()) {
                echo "Error updating remember me token.";
            }
            $cookie_stmt->close();
    
            setcookie("remember_token", $token, time() + (30 * 24 * 60 * 60), "/", "", false, true);
            setcookie("user_email", $email, time() + (30 * 24 * 60 * 60), "/", "", false, true);
        }
    
        $stmt = $conn->prepare("SELECT id, name, password, user_type, profile_picture, is_frozen FROM USERS WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($user_id, $name, $hashed_password, $user_type, $profile_picture, $is_frozen);
            $stmt->fetch();
            
            if (password_verify($password, $hashed_password)) {
                // Store the is_frozen status in the session instead of blocking login
                $_SESSION["user_id"] = $user_id;
                $_SESSION["email"] = $email;
                $_SESSION["user_name"] = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                $_SESSION["user_type"] = $user_type;
                $_SESSION["profile_picture"] = $profile_picture;
                $_SESSION["is_frozen"] = $is_frozen;
                
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
                $log_stmt = $conn->prepare("INSERT INTO ACCOUNT_ACTIVITY (user_id, event_type, ip_address, user_agent) VALUES (?, 'login', ?, ?)");
                $log_stmt->bind_param("iss", $user_id, $ip_address, $user_agent);
                $log_stmt->execute();
                $log_stmt->close();
                
                echo "<script>console.log('Login successful, redirecting');</script>";
                header("Location: https://cosc360.ok.ubc.ca/~rsodhi03/cosc-360-project/handmade_goods/pages/home.php");
                exit();
            } else {
                echo "<script>console.log('Invalid pass');</script>";
                header("Location: /~rsodhi03/cosc-360-project/handmade_goods/auth/login.php?error=invalid");
                exit();
            }
        } else {
            echo "<script>console.log('No such user');</script>";
            header("Location: https://cosc360.ok.ubc.ca/~rsodhi03/cosc-360-project/handmade_goods/auth/login.php?error=nouser");
            exit();
        }
        $stmt->close();
    }
}
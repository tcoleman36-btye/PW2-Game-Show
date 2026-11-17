<?php
session_start();
$feedback = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputUser = trim($_POST['username'] ?? '');
    $inputPass = $_POST['password'] ?? '';
    
    do {
        if (empty($inputUser) || empty($inputPass)) {
            $feedback = "Both fields are required.";
            break;
        }

        if (strpos($inputUser, '|') !== false) {
            $feedback = "Username can not have the | character";
            break;
        }

        $userFile = __DIR__ . '/users.txt';
        
        if (file_exists($userFile)) {
            $lines = file($userFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                [$existingUser] = explode('|', $line);
                if ($existingUser === $inputUser) {
                    $feedback = "That username is already taken.";
                    break 2;
                }
            }
        }

        if (!file_exists($userFile)) {
           $feedback = "users.txt does not exist at: " . $userFile;
           break;
        }

        if (!is_writable($userFile)) {
           $feedback = "PHP does not have permission to write to users.txt.";
           break;
        }
        
        $hashedPass = password_hash($inputPass, PASSWORD_DEFAULT);
        $saved = file_put_contents($userFile, $inputUser . '|' . $hashedPass . PHP_EOL, FILE_APPEND | LOCK_EX);
        
        if ($saved === false) {
            $feedback = "Unable to create account. Gain PHP permissions and Try again";
            break;        
        }

        $_SESSION['signup_success'] = 'Trainer has been made! PLease Log in.';
        header("Location: login.php");
        exit();
        
    } while(false);
}
?>
<!DOCTYPE html>
<html lang=en>
<head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>Pokefeud - Sign Up</title>
     <link rel="stylesheet" href="style.css">
</head>
<body class="signin">
<div class="login-container">
    <h2>Create Trainer Account</h2>
    
    <?php if ($feedback): ?>
        <p style="color: #fffb00f6;"><?php echo htmlspecialchars($feedback, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Choose Username</label><br>
        <input type="text" name="username" value="<?php echo htmlspecialchars($inputUser ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>
        
        <label>Choose Password</label><br>
        <input type="password" name="password"><br><br>
        
        <button type="submit">Register & Play</button>
    </form>
    
    <p>Already registered? <a href="login.php">Log in</a></p>
</div>
</body>
</html>
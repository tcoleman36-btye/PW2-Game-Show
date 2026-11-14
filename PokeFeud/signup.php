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
        
        if (file_exists('users.txt')) {
            $lines = file('users.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                [$existingUser] = explode('|', $line);
                if ($existingUser === $inputUser) {
                    $feedback = "That username is already taken.";
                    break 2;
                }
            }
        }
        
        $hashedPass = password_hash($inputPass, PASSWORD_DEFAULT);
        file_put_contents('users.txt', $inputUser . '|' . $hashedPass . PHP_EOL, FILE_APPEND);
        
        session_regenerate_id(true);
        $_SESSION['authorized'] = true;
        $_SESSION['username'] = htmlspecialchars($inputUser);
        header("Location: game-show.php");
        exit();
        
    } while(false);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Pokefeud - Sign Up</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="login-container">
    <h2>Create Trainer Account</h2>
    
    <?php if ($feedback): ?>
        <p style="color: red;"><?php echo $feedback; ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Choose Username</label><br>
        <input type="text" name="username"><br><br>
        
        <label>Choose Password</label><br>
        <input type="password" name="password"><br><br>
        
        <button type="submit">Register & Play</button>
    </form>
    
    <p>Already registered? <a href="login.php">Log in</a></p>
</div>
</body>
</html>
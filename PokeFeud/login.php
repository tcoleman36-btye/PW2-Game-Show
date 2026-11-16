<?php
session_start();
$feedback = "";
$success = $_SESSION['signup_success'] ?? '';
unset($_SESSION['signup_success']);

function loadUserDatabase() {
    $usersFile = __DIR__ . '/users.txt';

    if (!file_exists($usersFile)) return [];
    
    $records = [];
    $lines = file($usersFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        [$u, $h] = explode('|', $line . '|'); 
        if (!empty($u) && !empty($h)) {
            $records[$u] = $h;
        }
    }
    return $records;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputUser = trim($_POST['username'] ?? '');
    $inputPass = $_POST['password'] ?? '';
    
    if (empty($inputUser) || empty($inputPass)) {
        $feedback = "Please fill out all fields.";
    } else {
        $users = loadUserDatabase();
        
        if (isset($users[$inputUser]) && password_verify($inputPass, $users[$inputUser])) {
            session_regenerate_id(true);
            $_SESSION['authorized'] = true;
            $_SESSION['username'] = htmlspecialchars($inputUser);
            header("Location: introduction.php");
            exit();
        } else {
            $feedback = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="login">
<head>
     <meta charset="UTF-8">
     <meta name="description" content="Log in screen for gameshow">
     <title>Pokefeud - Login</title>
     <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="login-container">
    <h2>Trainer Login</h2>

    <?php if ($success): ?>
        <p style="color: #36ff2f;"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>
    
    <?php if ($feedback): ?>
        <p style="color: #FFFF00 ;"><?php echo htmlspecialchars($feedback, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Username</label><br>
        <input type="text" name="username" value="<?php echo htmlspecialchars($inputUser ?? ''); ?>"><br><br>
        
        <label>Password</label><br>
        <input type="password" name="password"><br><br>
        
        <button type="submit">Log In</button>
    </form>
    
    <p>New trainer? <a href="signup.php">Register an account</a></p>
</div>
</body>
</html>
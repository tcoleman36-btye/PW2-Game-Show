<?php
session_start();

if (!isset($_SESSION['authorized']) || $_SESSION['authorized'] !== true) {
    header("Location: login.php");
    exit();
}

//Applying a Cookie to the introduction

$user = $_SESSION['username'];

$welcomeBack = '';

if (isset($_COOKIE['lastVisit'])) {
   $welcomeBack = "Welcome back {$user} to Pokefeud!";

} else {
   $welcomeBack = "Welcome {$user} to Pokefeud! ";
}

//updates the cookie for when you visit the site again
setcookie('lastVisit', date('F j, Y g:i A'), [
    'expires' => time() + (86400 * 30),
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax'
]);

?>



<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
         <meta name="viewport" content="width=device-width, initial-scale=1.0">
         <meta name="description" content="Steve will introduce themselves and give the players the rules of the game">
         <title> Introductions </title>
         <link rel="stylesheet" href="style.css">
    </head>
    
    <body>
        <div class="intro_box">
            <header>
              <h1 class="Welcome-box"><?= htmlspecialchars($welcomeBack, ENT_QUOTES, 'UTF-8') ?></h1>
            </header>
        </div>

        <section id="rule-box">
            <div rule-content>
                <ol>
                    <li>A Question prompt will screen, the players will type a guess on the search bar, get it correct and earn points.</li>
                    <li>Each player will have 3 trys </li>
                </ol>
            </div>

        </section>

        <aside class="logout-box">

        </aside>

    </body>
</html>
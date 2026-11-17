<?php
session_start();

if (!isset($_SESSION['authorized']) || $_SESSION['authorized'] !== true) {
    header("Location: login.php");
    exit();
}

// logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

//Applying a Cookie to the introduction, that way the header will welcome the user

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
<html lang="en" class="intro">
    <head>
        <meta charset="UTF-8">
         <meta name="viewport" content="width=device-width, initial-scale=1.0">
         <meta name="description" content="Steve will introduce themselves and give the players the rules of the game">
         <title> Introductions </title>
         <link rel="stylesheet" href="style.css">
    </head>
    
    <body id="content-box">
        
        <header class="intro-box">
              <h1 class="Welcome"><?= htmlspecialchars($welcomeBack, ENT_QUOTES, 'UTF-8') ?></h1>
        </header>
        

        <main>

            <section id="rule-box">
               <div class="rule-content">
                  <ul>
                     <li>A Question prompt will appear on screen, once it does, players wil type a guess on the search bar to earn points.</li>
                     <li>Each player will have 3 attempts to gain points, guess wrong and get a strike. </li>
                     <li>Once a player gets 3 strikes, the other player gets one attempt to steal points.</li>
                     <li>High points players have the option to skip their turn to give the other player a go.</li>
                     <li>The Player with most points wins the game.</li>
                  </ul>
                </div>

            </section>

            <section class="start-game">
                <div class="start_button">
                 <a href="game-show.php">Start The Game</a>
                </div>
            </section>
        </main>

        <aside class="logout-box">
            <a href="?action=logout" class="logout-link">Log Out</a>
        </aside>

    </body>
</html>
<?php
session_start();

$gameState = $_SESSION['game'] ?? [];

$playerScore =  $gameState['player_score'] ?? 0;

$aiScore = $gameState['ai_score'] ?? 0;

if ($playerScore > $aiScore) {
    $winner = $_SESSION['username'];
} elseif ($aiScore > $playerScore) {
    $winner = 'Rival Wins!';
} else {
    $winner = 'Tie!';
}


if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
         <meta name="viewport" content="width=device-width, initial-scale=1.0">
         <meta name="description" content="Steve will introduce themselves and give the players the rules of the game">
         <title> Game Over</title>
         <link rel="stylesheet" href="style.css">
    </head>
    
    <body>


        <section class="content-box">

            <div class="winner">
                <h2></h2>
            </div>

            <div class="breakdown">

            </div>
        </section>
        
        <aside class=leaderboard>

        </aside>

        <div class="playagain">
            <a href="game-show.php">Play Again</a>
        </div>

        <div>
         <a href="?action=logout" class="logout-link">Log Out</a>
        </div>

    </body>
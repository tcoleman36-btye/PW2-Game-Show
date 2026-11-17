<?php
session_start();

$gameState = $_SESSION['game'] ?? [];

$playerScore =  $gameState['player_score'] ?? 0;

$aiScore = $gameState['ai_score'] ?? 0;

if ($playerScore > $aiScore) {
    $winner = $_SESSION['username'] . ' Wins';
    $winnerImage = 'players/Win_Nurse.png';
} elseif ($aiScore > $playerScore) {
    $winner = 'Rival Wins!';
    $winnerImage = 'players/Officer.png';
} else {
    $winner = 'Tie!';
    $winnerImage = 'background-&-host/Host_confused.png';
}

//Stores the the scores in a text file
$scoreboard = __DIR__ . '/score.txt';
$scores = [];

if (file_exists($scoreboard)) {
    $lines = file($scoreboard, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        [$username, $score] = explode('|', $line);

        $scores[] = [
            'username' => $username,
            'score' => (int)$score
        ];
    }
}

usort($scores, function($a, $b) {
    return $b['score'] <=> $a['score'];
});

$scores = array_slice($scores, 0, 19);

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
         <link rel="stylesheet" href="game-over.css">
    </head>

    <header>
        <h1>Game Over</h1>
    </header>

    <body>
        <main>
            <!-- This box will show the winner and breakdown the scores to the players-->
            <section class="content-box">

              <!--image will display the one that wins the game-->
                <div class="winner">
                  <h2> <?php echo htmlspecialchars($winner, ENT_QUOTES, 'UTF-8'); ?></h2>
                  <img src="" alt="">

                </div>

                <div class="breakdown">
                
                    <div class ="player-score">
                      <h3><?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?> </h3>
                       <p><?php echo $playerScore; ?> points</p>
                    </div>

                    <div class ="rival-score">
                      <H3>Rival</H3>
                      <p><?php echo $aiScore; ?> points</p>
                    </div>
                
                </div>
            </section>
        
            <aside class="leaderboard">
              <h2>leaderboard &#127942</h2>
                <?php if (empty($scores)):?>
                 <p>...</p> <!--Ellipse indicates there is no score-->
                <?php else: ?>
                    <?php foreach ($scores as $index => $entry): ?>
                        <div class="board_entry">
                            <span>
                             <?php echo $index + 1; ?>.
                             <?php echo htmlspecialchars($entry['username'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>

                            <span>
                                <?php echo $entry['score']; ?> points
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                  
            </aside>

            <div class="playagain">
                <a href="game-show.php">Play Again</a>
            </div>

            <div>
              <a href="?action=logout" class="logout-link">Log Out</a>
            </div>

        </main>

    </body>
</html>
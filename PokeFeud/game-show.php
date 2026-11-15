<?php
// ------------------- SECTION 1: SESSION CHECK & LOGIN GUARD ---------------------
session_start();

if (empty($_SESSION['authorized']) || $_SESSION['authorized'] !== true) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}


// -------------------- SECTION 2: SESSION SCORES & LEADERBOARD -----------------
if (!isset($_SESSION['scores'])) {
    $_SESSION['scores'] = [];
}

function saveToLeaderboard($tag, $pts) {
    $_SESSION['scores'][] = [
        'tag'   => htmlspecialchars($tag),
        'score' => (int)$pts,
        'time'  => time()
    ];
}

function getTopScores() {
    $board = $_SESSION['scores'] ?? [];
    usort($board, function($a, $b) {
        return $b['score'] <=> $a['score'];
    });
    return array_slice($board, 0, 5);
}


// -------------------- SECTION 3: GAME STATE & QUESTION BANK -----------------------
if (!isset($_SESSION['game'])) {
    $_SESSION['game'] = [
        'question_index' => 0,
        'phase'          => 'faceoff', // 'faceoff', 'board_play', 'steal'
        'owner'          => null,      // 'player' or 'ai'
        'ai_level'       => 'medium',  // 'low', 'medium', 'high'
        'faceoff_tries'  => ['player' => null, 'ai' => null],
        'strikes'        => 0,
        'round_bank'     => 0,
        'player_score'   => 0,
        'ai_score'       => 0,
        'revealed'       => array_fill(1, 8, false)
    ];
}

// Survey question bank (Ranked 1 to 8 for Safe, Mid, and Risky AI pools)
$qBank = [
    0 => [
        'prompt'  => 'Name something people pack when going on a beach vacation.',
        'answers' => [
            1 => ['text' => 'Sunscreen',  'points' => 35], // Safe Pool (#1-#3)
            2 => ['text' => 'Towel',      'points' => 30], // Safe Pool (#1-#3)
            3 => ['text' => 'Swimsuit',   'points' => 20], // Safe / Mid Pool overlap
            4 => ['text' => 'Sunglasses', 'points' => 15], // Mid Pool (#3-#6)
            5 => ['text' => 'Umbrella',   'points' => 10], // Mid Pool (#3-#6)
            6 => ['text' => 'Cooler',     'points' => 8],  // Mid Pool (#3-#6)
            7 => ['text' => 'Flip-flops', 'points' => 5],  // Risky Pool (#7-#8)
            8 => ['text' => 'Hat',        'points' => 2]   // Risky Pool (#7-#8)
        ]
    ]
];

// AI decision logic based on confidence level and pools (Safe, Mid, Risky, Blurt)
function runAiTurn(&$state, $questions) {
    $confidence = $state['ai_level'];
    // TODO: AI pool picking logic here based on $confidence
}


// ---------------------- SECTION 4: FORM HANDLING & EVALUATION -----------------------
$msg = "";
$guessInput = "";
$gameState = &$_SESSION['game'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
    $guessInput = trim($_POST['player_guess'] ?? '');
    $cleanGuess = htmlspecialchars($guessInput, ENT_QUOTES, 'UTF-8');
    $currentQ = $qBank[$gameState['question_index']];

    // PHASE 1: FACE-OFF
    if ($gameState['phase'] === 'faceoff') {
        if ($action === 'submit' && $cleanGuess !== '') {
            $gameState['faceoff_tries']['player'] = $cleanGuess;
            
            // Transition to board play (placeholder)
            $gameState['phase'] = 'board_play';
            $gameState['owner'] = 'player';
            $msg = "Face-off finished! You control the board.";
            $guessInput = "";
        }
    } 
    // PHASE 2: BOARD PLAY
    elseif ($gameState['phase'] === 'board_play') {
        if ($action === 'skip') {
            $gameState['strikes']++;
            $msg = "Turn skipped! Strike added.";
        } elseif ($action === 'submit') {
            $foundMatch = false;
            foreach ($currentQ['answers'] as $slot => $ans) {
                if (!$gameState['revealed'][$slot]) {
                    if (strcasecmp($cleanGuess, $ans['text']) === 0) {
                        $gameState['revealed'][$slot] = true;
                        $gameState['round_bank'] += $ans['points'];
                        $foundMatch = true;
                        $msg = "Nice! {$ans['text']} is up on the board.";
                        break;
                    }
                }
            }

            if (!$foundMatch) {
                $gameState['strikes']++;
                $msg = "X! Not on the board.";
            }

            // Hit 3 strikes -> Move to Steal Phase
            if ($gameState['strikes'] >= 3) {
                $gameState['phase'] = 'steal';
                $msg = "3 Strikes! Opponent has a chance to STEAL!";
            }
        }
    }
    // PHASE 3: STEAL PHASE
    elseif ($gameState['phase'] === 'steal') {
        if ($action === 'submit') {
            // TODO: Validate steal guess
            $gameState['phase'] = 'faceoff';
            $msg = "Steal attempt resolved!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pokefeud - Arena</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="arena-wrapper">

        <!-- ----------------------- SECTION 5: VIEW RENDERING ------------------>

        <header class="question-header">
            <span class="round-tag">Phase: <?php echo strtoupper($gameState['phase']); ?></span>
            <h1 class="active-prompt">
                <?php 
                    $activeQ = $qBank[$gameState['question_index']];
                    echo htmlspecialchars($activeQ['prompt']); 
                ?>
            </h1>
        </header>

        <div class="stage-container">

            <!-- Player Info -->
            <aside class="player-podium">
                <img src="assets/trainer1.png" alt="Trainer" class="trainer-avatar">
                <div class="podium-info">
                    <h3><?php echo $_SESSION['username']; ?></h3>
                    <div class="score-badge">Score: <span id="p-score"><?php echo $gameState['player_score']; ?></span></div>
                </div>
                <a href="?action=logout" class="logout-link">Log Out</a>
            </aside>

            <main class="game-board-center">
                <div class="board-timer-bar">
                    <span id="countdown">30s | Strikes: <?php echo $gameState['strikes']; ?>/3</span>
                </div>

                <div class="survey-board-grid">
                    <?php for ($i = 1; $i <= 8; $i++): ?>
                        <?php 
                            $isRevealed = $gameState['revealed'][$i];
                            $boxText = $isRevealed ? htmlspecialchars($activeQ['answers'][$i]['text']) : "[ ? ]";
                            $boxPts  = $isRevealed ? $activeQ['answers'][$i]['points'] : "00";
                        ?>
                        <div class="answer-slot <?php echo $isRevealed ? 'revealed' : 'hidden'; ?>">
                            <span class="slot-number"><?php echo $i; ?></span>
                            <span class="slot-content"><?php echo $boxText; ?></span>
                            <span class="slot-points"><?php echo $boxPts; ?></span>
                        </div>
                    <?php endfor; ?>
                </div>
            </main>

            <!-- AI Info -->
            <aside class="player-podium">
                <img src="assets/trainer2.png" alt="Rival" class="trainer-avatar">
                <div class="podium-info">
                    <h3>Rival Trainer (AI)</h3>
                    <div class="score-badge">Score: <span><?php echo $gameState['ai_score']; ?></span></div>
                    <small>Confidence: <?php echo ucfirst($gameState['ai_level']); ?></small>
                </div>
            </aside>

        </div>

        <section class="control-deck">
            <?php if (!empty($msg)): ?>
                <p class="feedback-banner"><?php echo $msg; ?></p>
            <?php endif; ?>

            <form method="POST" class="guess-form-flex">
                <?php if ($gameState['phase'] !== 'steal'): ?>
                    <button type="submit" name="action" value="skip" class="btn-skip">Skip</button>
                <?php endif; ?>

                <input type="text" name="player_guess" class="guess-input-box" 
                       value="<?php echo htmlspecialchars($guessInput); ?>" 
                       placeholder="<?php echo $gameState['phase'] === 'faceoff' ? 'Face-off guess...' : 'Type your answer...'; ?>" autocomplete="off" autofocus>

                <button type="submit" name="action" value="submit" class="btn-submit">Submit Answer</button>
            </form>
        </section>

    </div>

</body>
</html>
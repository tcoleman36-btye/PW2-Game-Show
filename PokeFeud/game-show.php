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

if (isset($_POST['action']) && $_POST['action'] === 'reset_game') {
    unset($_SESSION['game']);
    header("Location: " . $_SERVER['PHP_SELF']);
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
        'sub_phase'      => 'player_turn', // 'player_turn', 'bot_thinking', 'bot_revealed', 'bot_turn'
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

// Survey question bank (Ranked 1 to 8 for Safe, Mid, and Risky AI pools, plus unique wrong and funny arrays)
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
        ],
        'wrong_answers' => [
            'Winter parka',
            'Snowshoes',
            'Ice scraper',
            'Ski goggles'
        ],
        'funny_answers' => [
            'An entire refrigerator',
            'A random boulder',
            'A pet polar bear',
            'A microwave'
        ]
    ]
];

//AI Logic
function botGuess(&$st, $questions) {
    $lvl = $st['ai_level'];
    $qData = $questions[$st['question_index']];
    $ansList = $qData['answers'];
    $badPool = $qData['wrong_answers'];
    $lolPool = $qData['funny_answers'];
    
    $dice = rand(1, 100);

    if ($lvl === 'low') {
        if ($dice <= 15) return $badPool[array_rand($badPool)];
        if ($dice <= 25) return $lolPool[array_rand($lolPool)];
        $safePicks = [];
        foreach ($ansList as $s => $info) {
            if ($s <= 3 && !$st['revealed'][$s]) $safePicks[$s] = $info;
        }
        if (!empty($safePicks)) return $ansList[array_rand($safePicks)]['text'];
    } elseif ($lvl === 'medium') {
        if ($dice <= 5) return $badPool[array_rand($badPool)];
        if ($dice <= 10) return $lolPool[array_rand($lolPool)];
        $midPicks = [];
        foreach ($ansList as $s => $info) {
            if ($s >= 3 && $s <= 6 && !$st['revealed'][$s]) $midPicks[$s] = $info;
        }
        if (!empty($midPicks)) return $ansList[array_rand($midPicks)]['text'];
    } else {
        $riskyPicks = [];
        foreach ($ansList as $s => $info) {
            if ($s >= 3 && !$st['revealed'][$s]) $riskyPicks[$s] = $info;
        }
        if (!empty($riskyPicks)) return $ansList[array_rand($riskyPicks)]['text'];
    }

    $leftovers = [];
    foreach ($ansList as $s => $info) {
        if (!$st['revealed'][$s]) $leftovers[$s] = $info;
    }

    if (empty($leftovers)) return $badPool[array_rand($badPool)];

    return $ansList[array_rand($leftovers)]['text'];
}


// ---------------------- SECTION 4: FORM HANDLING & EVALUATION -----------------------
$msg = "";
$guessInput = "";
$gameState = &$_SESSION['game'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] !== 'reset_game') {

    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
    $guessInput = trim($_POST['player_guess'] ?? '');
    $cleanGuess = htmlspecialchars($guessInput, ENT_QUOTES, 'UTF-8');
    $currentQ = $qBank[$gameState['question_index']];

    // PHASE 1: FACE-OFF
    if ($gameState['phase'] === 'faceoff') {
        if ($gameState['sub_phase'] === 'player_turn') {
            if ($action === 'submit' && $cleanGuess !== '') {
                $gameState['faceoff_tries']['player'] = $cleanGuess;
                $gameState['sub_phase'] = 'bot_thinking';
                $msg = "You guessed '{$cleanGuess}'. Waiting for bot response...";
                $guessInput = "";
            }
        } elseif ($gameState['sub_phase'] === 'bot_thinking') {
            if ($action === 'resolve_ai') {
                $botSaid = botGuess($gameState, $qBank);
                $gameState['faceoff_tries']['ai'] = $botSaid;

                $pSpot = null;
                $botSpot = null;

                foreach ($currentQ['answers'] as $slot => $ans) {
                    if (strcasecmp($gameState['faceoff_tries']['player'], $ans['text']) === 0) {
                        $pSpot = $slot;
                    }
                    if (strcasecmp($botSaid, $ans['text']) === 0) {
                        if ($botSpot === null) {
                            $botSpot = $slot;
                        }
                    }
                }

                if ($pSpot !== null && $botSpot !== null) {
                    if ($pSpot <= $botSpot) {
                        $gameState['owner'] = 'player';
                    } else {
                        $gameState['owner'] = 'ai';
                    }
                } elseif ($pSpot !== null) {
                    $gameState['owner'] = 'player';
                } elseif ($botSpot !== null) {
                    $gameState['owner'] = 'ai';
                } else {
                    $gameState['owner'] = (rand(0, 1) === 0) ? 'player' : 'ai';
                }

                $gameState['sub_phase'] = 'bot_revealed';
                $msg = "Bot guessed '{$botSaid}'. Board controller: " . ucfirst($gameState['owner']);
            }
        } elseif ($gameState['sub_phase'] === 'bot_revealed') {
            if ($action === 'proceed_board') {
                $gameState['phase'] = 'board_play';
                if ($gameState['owner'] === 'ai') {
                    $gameState['sub_phase'] = 'bot_turn';
                    $msg = "Bot controls the board! Taking its turn...";
                } else {
                    $gameState['sub_phase'] = 'player_turn';
                    $msg = "Board play begins!";
                }
            }
        }
    } 
    // PHASE 2: BOARD PLAY
    elseif ($gameState['phase'] === 'board_play') {
        if ($gameState['sub_phase'] === 'bot_turn') {
            if ($action === 'resolve_ai_board') {
                $botSaid = botGuess($gameState, $qBank);
                $gotHit = false;

                foreach ($currentQ['answers'] as $slot => $ans) {
                    if (!$gameState['revealed'][$slot]) {
                        if (strcasecmp($botSaid, $ans['text']) === 0) {
                            $gameState['revealed'][$slot] = true;
                            $gameState['round_bank'] += $ans['points'];
                            $gotHit = true;
                            break;
                        }
                    }
                }

                if ($gotHit) {
                    $msg = "Bot got '{$botSaid}' right and banked the points!";
                } else {
                    $gameState['strikes']++;
                    $msg = "Bot missed with '{$botSaid}'! Strike added ({$gameState['strikes']}/3).";
                }

                $boardDone = true;
                foreach ($currentQ['answers'] as $slot => $ans) {
                    if (!$gameState['revealed'][$slot]) {
                        $boardDone = false;
                        break;
                    }
                }

                if ($boardDone || $gameState['strikes'] >= 3) {
                    if ($boardDone) {
                        $gameState['ai_score'] += $gameState['round_bank'];
                        $msg = "Bot cleared the board! Bot takes all {$gameState['round_bank']} points.";
                    } else {
                        $gameState['phase'] = 'steal';
                        $msg = "Bot hit 3 strikes! You have a chance to STEAL!";
                    }
                    $gameState['round_bank'] = 0;
                    $gameState['strikes'] = 0;
                    $gameState['sub_phase'] = 'player_turn';
                }
            }
        } else {
            if ($action === 'skip') {
                $gameState['strikes']++;
                $msg = "Turn skipped! Strike added.";
            } elseif ($action === 'submit') {
                $gotHit = false;
                foreach ($currentQ['answers'] as $slot => $ans) {
                    if (!$gameState['revealed'][$slot]) {
                        if (strcasecmp($cleanGuess, $ans['text']) === 0) {
                            $gameState['revealed'][$slot] = true;
                            $gameState['round_bank'] += $ans['points'];
                            $gotHit = true;
                            $msg = "Nice! {$ans['text']} is up on the board.";
                            break;
                        }
                    }
                }

                if (!$gotHit) {
                    $gameState['strikes']++;
                    $msg = "X! Not on the board.";
                }

                $boardDone = true;
                foreach ($currentQ['answers'] as $slot => $ans) {
                    if (!$gameState['revealed'][$slot]) {
                        $boardDone = false;
                        break;
                    }
                }

                if ($boardDone) {
                    $gameState['player_score'] += $gameState['round_bank'];
                    $msg = "Board cleared! You take all {$gameState['round_bank']} points.";
                    $gameState['round_bank'] = 0;
                    $gameState['strikes'] = 0;
                    $gameState['phase'] = 'faceoff';
                    $gameState['sub_phase'] = 'player_turn';
                } elseif ($gameState['strikes'] >= 3) {
                    $gameState['phase'] = 'steal';
                    $msg = "3 Strikes! Opponent has a chance to STEAL!";
                }
            }
        }
    }
    // PHASE 3: STEAL PHASE
    elseif ($gameState['phase'] === 'steal') {
        if ($action === 'submit') {
            $whoSteals = ($gameState['owner'] === 'player') ? 'ai' : 'player';
            $stealTry = ($whoSteals === 'ai') ? botGuess($gameState, $qBank) : $cleanGuess;
            
            $stealDidWork = false;

            foreach ($currentQ['answers'] as $slot => $ans) {
                if (!$gameState['revealed'][$slot]) {
                    if (strcasecmp($stealTry, $ans['text']) === 0) {
                        $gameState['revealed'][$slot] = true;
                        $stealDidWork = true;
                        break;
                    }
                }
            }

            if ($stealDidWork) {
                foreach ($currentQ['answers'] as $s => $a) {
                    if (!$gameState['revealed'][$s]) {
                        $gameState['round_bank'] += $a['points'];
                        $gameState['revealed'][$s] = true;
                    }
                }

                if ($whoSteals === 'ai') {
                    $gameState['ai_score'] += $gameState['round_bank'];
                    $msg = "Successful Steal by Bot! Guess '{$stealTry}' was on the board. Bot takes {$gameState['round_bank']} points.";
                } else {
                    $gameState['player_score'] += $gameState['round_bank'];
                    $msg = "Successful Steal! Guess '{$stealTry}' was on the board. You take {$gameState['round_bank']} points.";
                }
            } else {
                if ($gameState['owner'] === 'player') {
                    $gameState['player_score'] += $gameState['round_bank'];
                    $msg = "Steal failed ('{$stealTry}' was wrong)! Original team keeps {$gameState['round_bank']} points.";
                } else {
                    $gameState['ai_score'] += $gameState['round_bank'];
                    $msg = "Steal failed ('{$stealTry}' was wrong)! Original team keeps {$gameState['round_bank']} points.";
                }
            }

            $gameState['round_bank'] = 0;
            $gameState['strikes'] = 0;
            $gameState['phase'] = 'faceoff';
            $gameState['sub_phase'] = 'player_turn';
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
    <?php if ($gameState['phase'] === 'faceoff' && $gameState['sub_phase'] === 'bot_thinking'): ?>
    <script>
        setTimeout(function() {
            document.getElementById('ai-resolve-form').submit();
        }, 2500);
    </script>
    <?php elseif ($gameState['phase'] === 'board_play' && $gameState['sub_phase'] === 'bot_turn'): ?>
    <script>
        setTimeout(function() {
            document.getElementById('ai-board-form').submit();
        }, 2500);
    </script>
    <?php endif; ?>
</head>
<body>

    <div class="arena-wrapper">

        <!-- ----------------------- SECTION 5: VIEW RENDERING ------------------>

        <header class="question-header">
            <span class="round-tag">Phase: <?php echo strtoupper($gameState['phase']); ?> | Controller: <?php echo ucfirst($gameState['owner'] ?? 'None'); ?></span>
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
                <div class="podium-actions" style="margin-top: 10px;">
                    <form method="POST" style="display:inline;">
                        <button type="submit" name="action" value="reset_game" class="btn-reset" onclick="return confirm('Reset current game data?');">Reset Game</button>
                    </form>
                    <a href="?action=logout" class="logout-link">Log Out</a>
                </div>
            </aside>

            <main class="game-board-center">
                <div class="board-timer-bar">
                    <span id="countdown">30s | Strikes: <?php echo $gameState['strikes']; ?>/3 | Bank: <?php echo $gameState['round_bank']; ?>pts</span>
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

            <?php if ($gameState['phase'] === 'faceoff' && $gameState['sub_phase'] === 'bot_thinking'): ?>
                <form id="ai-resolve-form" method="POST">
                    <input type="hidden" name="action" value="resolve_ai">
                    <div class="guess-form-flex" style="justify-content: center; align-items: center;">
                        <div class="guess-input-box" style="text-align: center; line-height: 40px; font-weight: bold;">
                            Thinking...
                        </div>
                    </div>
                </form>
            <?php elseif ($gameState['phase'] === 'faceoff' && $gameState['sub_phase'] === 'bot_revealed'): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="proceed_board">
                    <div class="guess-form-flex" style="justify-content: center; align-items: center; gap: 15px;">
                        <span style="font-weight: bold;">AI Guessed: <?php echo htmlspecialchars($gameState['faceoff_tries']['ai']); ?></span>
                        <button type="submit" class="btn-submit">Proceed to Board Play</button>
                    </div>
                </form>
            <?php elseif ($gameState['phase'] === 'board_play' && $gameState['sub_phase'] === 'bot_turn'): ?>
                <form id="ai-board-form" method="POST">
                    <input type="hidden" name="action" value="resolve_ai_board">
                    <div class="guess-form-flex" style="justify-content: center; align-items: center;">
                        <div class="guess-input-box" style="text-align: center; line-height: 40px; font-weight: bold;">
                            Thinking...
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <form method="POST" class="guess-form-flex">
                    <?php if ($gameState['phase'] !== 'steal'): ?>
                        <button type="submit" name="action" value="skip" class="btn-skip">Skip</button>
                    <?php endif; ?>

                    <input type="text" name="player_guess" class="guess-input-box" 
                           value="<?php echo htmlspecialchars($guessInput); ?>" 
                           placeholder="<?php echo $gameState['phase'] === 'faceoff' ? 'Face-off guess...' : ($gameState['phase'] === 'steal' ? 'Enter steal guess...' : 'Type your answer...'); ?>" autocomplete="off" autofocus>

                    <button type="submit" name="action" value="submit" class="btn-submit">Submit Answer</button>
                </form>
            <?php endif; ?>
        </section>

    </div>

</body>
</html>
logout
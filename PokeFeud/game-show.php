<?php
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

if (!isset($_SESSION['game'])) {
    $_SESSION['game'] = [
        'question_index' => 0,
        'phase'          => 'faceoff', 
        'sub_phase'      => 'player_turn', 
        'owner'          => null,      
        'ai_level'       => 'medium',  
        'faceoff_tries'  => ['player' => null, 'ai' => null],
        'strikes'        => 0,
        'round_bank'     => 0,
        'player_score'   => 0,
        'ai_score'       => 0,
        'revealed'       => array_fill(1, 8, false)
    ];
}

$qBank = [
    0 => [
        'prompt'  => 'Name something people pack when going on a beach vacation.',
        'answers' => [
            1 => ['text' => 'Sunscreen',  'points' => 35],
            2 => ['text' => 'Towel',      'points' => 30],
            3 => ['text' => 'Swimsuit',   'points' => 20],
            4 => ['text' => 'Sunglasses', 'points' => 15],
            5 => ['text' => 'Umbrella',   'points' => 10],
            6 => ['text' => 'Cooler',     'points' => 8],
            7 => ['text' => 'Flip-flops', 'points' => 5],
            8 => ['text' => 'Hat',        'points' => 2]
        ],
        'wrong_answers' => ['Winter parka', 'Snowshoes', 'Ice scraper', 'Ski goggles'],
        'funny_answers' => ['An entire refrigerator', 'A random boulder', 'A pet polar bear', 'A microwave']
    ],
    1 => [
        'prompt'  => 'Name something people buy or eat at a movie theater.',
        'answers' => [
            1 => ['text' => 'Popcorn',    'points' => 40],
            2 => ['text' => 'Soda',       'points' => 25],
            3 => ['text' => 'Candy',      'points' => 15],
            4 => ['text' => 'Nachos',     'points' => 10],
            5 => ['text' => 'Hot dog',    'points' => 5],
            6 => ['text' => 'Pretzel',    'points' => 3],
            7 => ['text' => 'Ice cream',  'points' => 1],
            8 => ['text' => 'Water',      'points' => 1]
        ],
        'wrong_answers' => ['Car engine', 'Scribble pad', 'Garden hose', 'Anvil'],
        'funny_answers' => ['A live turkey', 'A kitchen sink', 'Washing machine', 'Building bricks']
    ],
    2 => [
        'prompt'  => 'Name something you would find in almost every kitchen.',
        'answers' => [
            1 => ['text' => 'Refrigerator', 'points' => 35],
            2 => ['text' => 'Stove',        'points' => 25],
            3 => ['text' => 'Sink',         'points' => 20],
            4 => ['text' => 'Microwave',    'points' => 10],
            5 => ['text' => 'Knife',        'points' => 5],
            6 => ['text' => 'Plate',        'points' => 3],
            7 => ['text' => 'Spoon',        'points' => 1],
            8 => ['text' => 'Fork',         'points' => 1]
        ],
        'wrong_answers' => ['Airplane wing', 'Traffic light', 'Anchor', 'Snowboard'],
        'funny_answers' => ['Roller coaster', 'Swimming pool', 'A live dinosaur', 'Spaceship']
    ]
];

function botGuess(&$st, $questions) {
    $lvl = $st['ai_level'];
    $qData = $questions[$st['question_index']];
    $ansList = $qData['answers'];
    $badPool = $qData['wrong_answers'];
    $lolPool = $qData['funny_answers'];
    
    $dice = rand(1, 100);

    if ($lvl === 'low') {
        if ($dice <= 30) return $badPool[array_rand($badPool)];
        if ($dice <= 55) return $lolPool[array_rand($lolPool)];
        $safePicks = [];
        foreach ($ansList as $s => $info) {
            if ($s <= 3 && !$st['revealed'][$s]) $safePicks[$s] = $info;
        }
        if (!empty($safePicks)) return $ansList[array_rand($safePicks)]['text'];
    } elseif ($lvl === 'medium') {
        if ($dice <= 20) return $badPool[array_rand($badPool)];
        if ($dice <= 40) return $lolPool[array_rand($lolPool)];
        $midPicks = [];
        foreach ($ansList as $s => $info) {
            if ($s >= 3 && $s <= 6 && !$st['revealed'][$s]) $midPicks[$s] = $info;
        }
        if (!empty($midPicks)) return $ansList[array_rand($midPicks)]['text'];
    } else {
        if ($dice <= 10) return $badPool[array_rand($badPool)];
        if ($dice <= 20) return $lolPool[array_rand($lolPool)];
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

$msg = "";
$guessInput = "";
$gameState = &$_SESSION['game'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] !== 'reset_game') {
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
    $guessInput = trim($_POST['player_guess'] ?? '');
    $cleanGuess = htmlspecialchars($guessInput, ENT_QUOTES, 'UTF-8');
    $currentQ = $qBank[$gameState['question_index']];

    if ($gameState['phase'] === 'faceoff') {
        if ($gameState['sub_phase'] === 'player_turn') {
            if ($action === 'submit' && $cleanGuess !== '') {
                $gameState['faceoff_tries']['player'] = $cleanGuess;
                $gameState['sub_phase'] = 'bot_thinking';
                $msg = "You guessed '{$cleanGuess}'. Click below for bot response.";
                $guessInput = "";
            }
        } elseif ($gameState['sub_phase'] === 'bot_thinking') {
            if ($action === 'resolve_ai') {
                $botSecs = rand(8, 15);
                $botSaid = botGuess($gameState, $qBank);
                $gameState['faceoff_tries']['ai'] = $botSaid;

                $pSpot = null;
                $botSpot = null;

                foreach ($currentQ['answers'] as $slot => $ans) {
                    if (strcasecmp($gameState['faceoff_tries']['player'], $ans['text']) === 0) $pSpot = $slot;
                    if (strcasecmp($botSaid, $ans['text']) === 0 && $botSpot === null) $botSpot = $slot;
                }

                if ($pSpot !== null && $botSpot !== null) {
                    $gameState['owner'] = ($pSpot <= $botSpot) ? 'player' : 'ai';
                } elseif ($pSpot !== null) {
                    $gameState['owner'] = 'player';
                } elseif ($botSpot !== null) {
                    $gameState['owner'] = 'ai';
                } else {
                    $gameState['owner'] = (rand(0, 1) === 0) ? 'player' : 'ai';
                }

                $gameState['sub_phase'] = 'bot_revealed';
                $msg = "Bot guessed '{$botSaid}'. (AI took {$botSecs}s) Board controller: " . ucfirst($gameState['owner']);
            }
        } elseif ($gameState['sub_phase'] === 'bot_revealed') {
            if ($action === 'proceed_board') {
                $gameState['phase'] = 'board_play';
                if ($gameState['owner'] === 'ai') {
                    $gameState['sub_phase'] = 'bot_turn';
                    $msg = "Bot controls the board! Click to take its turn.";
                } else {
                    $gameState['sub_phase'] = 'player_turn';
                    $msg = "Board play begins!";
                }
            }
        }
    } elseif ($gameState['phase'] === 'board_play') {
        if ($gameState['sub_phase'] === 'bot_turn') {
            if ($action === 'resolve_ai_board') {
                $botSecs = rand(8, 15);
                $botSaid = botGuess($gameState, $qBank);
                $gotHit = false;

                foreach ($currentQ['answers'] as $slot => $ans) {
                    if (!$gameState['revealed'][$slot] && strcasecmp($botSaid, $ans['text']) === 0) {
                        $gameState['revealed'][$slot] = true;
                        $gameState['round_bank'] += $ans['points'];
                        $gotHit = true;
                        break;
                    }
                }

                if ($gotHit) {
                    $msg = "Bot got '{$botSaid}' right and banked the points! (AI took {$botSecs}s)";
                } else {
                    $gameState['strikes']++;
                    $msg = "Bot missed with '{$botSaid}'! Strike added ({$gameState['strikes']}/3).";
                }

                $boardDone = true;
                foreach ($currentQ['answers'] as $slot => $ans) {
                    if (!$gameState['revealed'][$slot]) { $boardDone = false; break; }
                }

                if ($boardDone || $gameState['strikes'] >= 3) {
                    if ($boardDone) {
                        $gameState['ai_score'] += $gameState['round_bank'];
                        $msg .= " Bot cleared the board! Takes {$gameState['round_bank']} pts.";
                    } else {
                        $gameState['phase'] = 'steal';
                        $msg .= " Bot hit 3 strikes! Chance to STEAL!";
                    }
                    if ($gameState['phase'] !== 'steal') {
                        $gameState['round_bank'] = 0;
                        $gameState['strikes'] = 0;
                        $gameState['question_index']++;
                        $gameState['revealed'] = array_fill(1, 8, false);
                        if ($gameState['question_index'] >= count($qBank)) {
                            header("Location: game-over.php");
                            exit();
                        } else {
                            $gameState['phase'] = 'faceoff';
                            $gameState['sub_phase'] = 'player_turn';
                        }
                    }
                }
            }
        } else {
            if ($action === 'skip') {
                $gameState['strikes']++;
                $msg = "Turn skipped! Strike added.";
            } elseif ($action === 'submit') {
                $gotHit = false;
                foreach ($currentQ['answers'] as $slot => $ans) {
                    if (!$gameState['revealed'][$slot] && strcasecmp($cleanGuess, $ans['text']) === 0) {
                        $gameState['revealed'][$slot] = true;
                        $gameState['round_bank'] += $ans['points'];
                        $gotHit = true;
                        $msg = "Nice! {$ans['text']} is up on the board.";
                        break;
                    }
                }

                if (!$gotHit) {
                    $gameState['strikes']++;
                    $msg = "X! Not on the board.";
                }

                $boardDone = true;
                foreach ($currentQ['answers'] as $slot => $ans) {
                    if (!$gameState['revealed'][$slot]) { $boardDone = false; break; }
                }

                if ($boardDone) {
                    $gameState['player_score'] += $gameState['round_bank'];
                    $msg = "Board cleared! You take {$gameState['round_bank']} pts.";
                    $gameState['round_bank'] = 0;
                    $gameState['strikes'] = 0;
                    $gameState['question_index']++;
                    $gameState['revealed'] = array_fill(1, 8, false);
                    if ($gameState['question_index'] >= count($qBank)) {
                        header("Location: game-over.php");
                        exit();
                    } else {
                        $gameState['phase'] = 'faceoff';
                        $gameState['sub_phase'] = 'player_turn';
                    }
                } elseif ($gameState['strikes'] >= 3) {
                    $gameState['phase'] = 'steal';
                    $msg = "3 Strikes! Opponent has a chance to STEAL!";
                }
            }
        }
    } elseif ($gameState['phase'] === 'steal') {
        if ($action === 'submit') {
            $whoSteals = ($gameState['owner'] === 'player') ? 'ai' : 'player';
            $botSecs = ($whoSteals === 'ai') ? rand(8, 15) : 0;
            $stealTry = ($whoSteals === 'ai') ? botGuess($gameState, $qBank) : $cleanGuess;
            $stealDidWork = false;

            foreach ($currentQ['answers'] as $slot => $ans) {
                if (!$gameState['revealed'][$slot] && strcasecmp($stealTry, $ans['text']) === 0) {
                    $gameState['revealed'][$slot] = true;
                    $stealDidWork = true;
                    break;
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
                    $msg = "Successful Steal by Bot! Takes {$gameState['round_bank']} pts.";
                } else {
                    $gameState['player_score'] += $gameState['round_bank'];
                    $msg = "Successful Steal! You take {$gameState['round_bank']} pts.";
                }
            } else {
                $msg = "Steal failed! Original team keeps points.";
            }

            $gameState['round_bank'] = 0;
            $gameState['strikes'] = 0;
            $gameState['question_index']++;
            $gameState['revealed'] = array_fill(1, 8, false);
            if ($gameState['question_index'] >= count($qBank)) {
                header("Location: game-over.php");
                exit();
            } else {
                $gameState['phase'] = 'faceoff';
                $gameState['sub_phase'] = 'player_turn';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pokefeud - Arena</title>
    <link rel="stylesheet" href="game-show.css">
</head>
<body>
    <div class="arena-wrapper">
        <header class="question-header">
            <span class="round-tag">Round <?php echo min(3, $gameState['question_index'] + 1); ?> of 3 | Phase: <?php echo strtoupper($gameState['phase']); ?> | Controller: <?php echo ucfirst($gameState['owner'] ?? 'None'); ?></span>
            <h1 class="active-prompt">
                <?php 
                    $activeQ = $qBank[$gameState['question_index']];
                    echo htmlspecialchars($activeQ['prompt']); 
                ?>
            </h1>
        </header>

        <aside class="player-podium-left">
            <img src="Players/Nurse.png" alt="Trainer" class="trainer-avatar">
            <div class="podium-info">
                <h3><?php echo htmlspecialchars($_SESSION['username'] ?? 'Player'); ?></h3>
                <div class="score-badge">Score: <span id="p-score"><?php echo $gameState['player_score']; ?></span></div>
            </div>
        </aside>

        <main class="game-board-center">
            <div class="board-info-bar">
                <span>Strikes: <?php echo $gameState['strikes']; ?>/3 | Bank: <?php echo $gameState['round_bank']; ?>pts</span>
            </div>

            <div class="survey-board-grid">
                <?php 
                    for ($i = 1; $i <= 8; $i++): 
                        $isRevealed = $gameState['revealed'][$i];
                        $boxText = $isRevealed ? htmlspecialchars($activeQ['answers'][$i]['text']) : "[ {$i} ]";
                        $boxPts  = $isRevealed ? $activeQ['answers'][$i]['points'] : "--";
                ?>
                    <div class="answer-slot <?php echo $isRevealed ? 'revealed' : 'hidden'; ?>">
                        <span class="slot-number">#<?php echo $i; ?></span>
                        <span class="slot-content"><?php echo $boxText; ?></span>
                        <span class="slot-points"><?php echo $boxPts; ?>pts</span>
                    </div>
                <?php endfor; ?>
            </div>
        </main>

        <aside class="player-podium-right">
            <img src="Players/Officer.png" alt="Rival" class="trainer-avatar">
            <div class="podium-info">
                <h3>Rival Trainer (AI)</h3>
                <div class="score-badge">Score: <span><?php echo $gameState['ai_score']; ?></span></div>
                <small style="color: #666;">Confidence: <?php echo ucfirst($gameState['ai_level']); ?></small>
            </div>
        </aside>

        <section class="control-deck">
            <?php if (!empty($msg)): ?>
                <p class="feedback-banner"><?php echo $msg; ?></p>
            <?php endif; ?>

            <?php if ($gameState['phase'] === 'faceoff' && $gameState['sub_phase'] === 'bot_thinking'): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="resolve_ai">
                    <div class="guess-form-flex" style="justify-content: center; align-items: center; gap: 15px;">
                        <button type="submit" class="btn-submit">Show Bot's Guess</button>
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
                <form method="POST">
                    <input type="hidden" name="action" value="resolve_ai_board">
                    <div class="guess-form-flex" style="justify-content: center; align-items: center; gap: 15px;">
                        <button type="submit" class="btn-submit">Bot Makes a Guess</button>
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

            <div class="control-deck-footer">
                <form method="POST" style="display:inline;">
                    <button type="submit" name="action" value="reset_game" class="btn-reset" onclick="return confirm('Reset current game data?');">Reset Game</button>
                </form>
                <a href="?action=logout" class="logout-link" style="color: #cc0000; font-weight: bold; text-decoration: none; line-height: 35px;">Log Out</a>
            </div>
        </section>
    </div>
</body>
</html>
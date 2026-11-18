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
    unset($_SESSION['score_saved']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
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
    ],
    3 => [
        'prompt'  => 'Name something people do immediately after waking up in the morning.',
        'answers' => [
            1 => ['text' => 'Brush teeth', 'points' => 35],
            2 => ['text' => 'Drink coffee', 'points' => 25],
            3 => ['text' => 'Check phone',  'points' => 20],
            4 => ['text' => 'Go to bathroom','points' => 10],
            5 => ['text' => 'Stretch',      'points' => 5],
            6 => ['text' => 'Shower',       'points' => 3],
            7 => ['text' => 'Make bed',     'points' => 1],
            8 => ['text' => 'Exercise',     'points' => 1]
        ],
        'wrong_answers' => ['Sleep for 8 hours', 'Go to sleep', 'Put on pajamas', 'Dream'],
        'funny_answers' => ['Fight a dragon', 'Eat a brick', 'Fly away', 'Teleport']
    ],
    4 => [
        'prompt'  => 'Name something people pack when going on a road trip.',
        'answers' => [
            1 => ['text' => 'Snacks',       'points' => 35],
            2 => ['text' => 'Phone charger','points' => 25],
            3 => ['text' => 'Playlist',     'points' => 20],
            4 => ['text' => 'Drinks',       'points' => 10],
            5 => ['text' => 'Sunglasses',   'points' => 5],
            6 => ['text' => 'Pillow',       'points' => 3],
            7 => ['text' => 'Cash',         'points' => 1],
            8 => ['text' => 'Map',          'points' => 1]
        ],
        'wrong_answers' => ['Anchor', 'Boat motor', 'Submarine hatch', 'Airplane wing'],
        'funny_answers' => ['A live elephant', 'Kitchen sink', 'Swimming pool', 'Roller coaster']
    ]
];

if (!isset($_SESSION['game'])) {
    $allKeys = array_keys($qBank);
    shuffle($allKeys);
    $selectedKeys = array_slice($allKeys, 0, 3);

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
        'revealed'       => array_fill(1, 8, false),
        'game_questions' => $selectedKeys
    ];
}

$gameState = &$_SESSION['game'];
$gameQKeys = $gameState['game_questions'];

if ($gameState['question_index'] >= count($gameQKeys)) {
    header("Location: game-over.php");
    exit();
}

function updateAiConfidence(&$st, $winner) {
    if ($winner === 'ai') {
        if ($st['ai_level'] === 'low') {
            $st['ai_level'] = 'medium';
        } elseif ($st['ai_level'] === 'medium') {
            $st['ai_level'] = 'high';
        }
    } elseif ($winner === 'player') {
        if ($st['ai_level'] === 'high') {
            $st['ai_level'] = 'medium';
        } elseif ($st['ai_level'] === 'medium') {
            $st['ai_level'] = 'low';
        }
    }
}

function botGuess(&$st, $questions, $currentQKey) {
    $lvl = $st['ai_level'];
    $qData = $questions[$currentQKey];
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
$showStrikeAnimation = false;
$showPointsAnimation = false;
$earnedPoints = 0;
$currentQKey = $gameQKeys[$gameState['question_index']];
$currentQ = $qBank[$currentQKey];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] !== 'reset_game') {
    $action = $_POST['action'] ?? '';
    $guessInput = trim($_POST['player_guess'] ?? '');
    $cleanGuess = $guessInput;

    $maxGuessLength = 40;
    $invalidGuess = false;
    if ($action === 'submit') {
        if ($cleanGuess === '') {
            $msg = "Please type an answer.";
            $invalidGuess = true;
        } elseif (strlen($cleanGuess) > $maxGuessLength) {
            $msg = "Answers must be {$maxGuessLength} characters or fewer.";
            $invalidGuess = true;
        }
    }

    if ($invalidGuess) {
    } elseif ($gameState['phase'] === 'faceoff') {
        if ($gameState['sub_phase'] === 'player_turn') {
            if ($action === 'submit' && $cleanGuess !== '') {
                $gameState['faceoff_tries']['player'] = $cleanGuess;

                $pSpot = null;
                foreach ($currentQ['answers'] as $slot => $ans) {
                    if (!$gameState['revealed'][$slot] && strcasecmp($cleanGuess, $ans['text']) === 0) {
                        $pSpot = $slot;
                        break;
                    }
                }

                if ($pSpot !== null) {
                    $gameState['revealed'][$pSpot] = true;
                    $earnedPoints = $currentQ['answers'][$pSpot]['points'];
                    $gameState['round_bank'] += $earnedPoints;
                    $showPointsAnimation = true;
                }

                $gameState['sub_phase'] = 'bot_thinking';
                $msg = "You guessed '{$cleanGuess}'. Click below for bot response.";
                $guessInput = "";
            }
        } elseif ($gameState['sub_phase'] === 'bot_thinking') {
            if ($action === 'resolve_ai') {
                $botSecs = rand(8, 15);
                $botSaid = botGuess($gameState, $qBank, $currentQKey);
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

                if ($botSpot !== null && !$gameState['revealed'][$botSpot]) {
                    $gameState['revealed'][$botSpot] = true;
                    $earnedPoints = $currentQ['answers'][$botSpot]['points'];
                    $gameState['round_bank'] += $earnedPoints;
                    $showPointsAnimation = true;
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
                $botSaid = botGuess($gameState, $qBank, $currentQKey);
                $gotHit = false;

                foreach ($currentQ['answers'] as $slot => $ans) {
                    if (!$gameState['revealed'][$slot] && strcasecmp($botSaid, $ans['text']) === 0) {
                        $gameState['revealed'][$slot] = true;
                        $earnedPoints = $ans['points'];
                        $gameState['round_bank'] += $earnedPoints;
                        $showPointsAnimation = true;
                        $gotHit = true;
                        break;
                    }
                }

                if ($gotHit) {
                    $msg = "Bot got '{$botSaid}' right and banked the points! (AI took {$botSecs}s)";
                } else {
                    $gameState['strikes']++;
                    $showStrikeAnimation = true;
                    $msg = "Bot missed with '{$botSaid}'! Strike added ({$gameState['strikes']}/3).";
                }

                $boardDone = true;
                foreach ($currentQ['answers'] as $slot => $ans) {
                    if (!$gameState['revealed'][$slot]) { $boardDone = false; break; }
                }

                if ($boardDone || $gameState['strikes'] >= 3) {
                    if ($boardDone) {
                        $gameState['ai_score'] += $gameState['round_bank'];
                        updateAiConfidence($gameState, 'ai');
                        $msg .= " Bot cleared the board! Takes {$gameState['round_bank']} pts.";
                        $gameState['round_bank'] = 0;
                        $gameState['strikes'] = 0;
                        $gameState['question_index']++;
                        $gameState['revealed'] = array_fill(1, 8, false);
                        if ($gameState['question_index'] >= count($gameQKeys)) {
                            header("Location: game-over.php");
                            exit();
                        } else {
                            $gameState['phase'] = 'faceoff';
                            $gameState['sub_phase'] = 'player_turn';
                            $gameState['faceoff_tries'] = ['player' => null, 'ai' => null];
                            $gameState['owner'] = null;
                        }
                    } else {
                        $gameState['phase'] = 'steal';
                        $gameState['steal_turn'] = ($gameState['owner'] === 'player') ? 'ai' : 'player';
                        $msg .= " Bot hit 3 strikes! Chance to STEAL!";
                    }
                }
            }
        } else {
            if ($action === 'skip') {
                $gameState['strikes']++;
                $showStrikeAnimation = true;
                if ($gameState['strikes'] >= 3) {
                    $gameState['phase'] = 'steal';
                    $gameState['steal_turn'] = ($gameState['owner'] === 'player') ? 'ai' : 'player';
                    $msg = "Turn skipped and that is 3 strikes! Opponent has a chance to STEAL!";
                } else {
                    $msg = "Turn skipped! Strike added.";
                }
            } elseif ($action === 'submit') {
                $gotHit = false;
                foreach ($currentQ['answers'] as $slot => $ans) {
                    if (!$gameState['revealed'][$slot] && strcasecmp($cleanGuess, $ans['text']) === 0) {
                        $gameState['revealed'][$slot] = true;
                        $earnedPoints = $ans['points'];
                        $gameState['round_bank'] += $earnedPoints;
                        $showPointsAnimation = true;
                        $gotHit = true;
                        $msg = "Nice! {$ans['text']} is up on the board.";
                        break;
                    }
                }

                if (!$gotHit) {
                    $gameState['strikes']++;
                    $showStrikeAnimation = true;
                    $msg = "X! Not on the board.";
                }

                $boardDone = true;
                foreach ($currentQ['answers'] as $slot => $ans) {
                    if (!$gameState['revealed'][$slot]) { $boardDone = false; break; }
                }

                if ($boardDone) {
                    $gameState['player_score'] += $gameState['round_bank'];
                    updateAiConfidence($gameState, 'player');
                    $msg = "Board cleared! You take {$gameState['round_bank']} pts.";
                    $gameState['round_bank'] = 0;
                    $gameState['strikes'] = 0;
                    $gameState['question_index']++;
                    $gameState['revealed'] = array_fill(1, 8, false);
                    if ($gameState['question_index'] >= count($gameQKeys)) {
                        header("Location: game-over.php");
                        exit();
                    } else {
                        $gameState['phase'] = 'faceoff';
                        $gameState['sub_phase'] = 'player_turn';
                        $gameState['faceoff_tries'] = ['player' => null, 'ai' => null];
                        $gameState['owner'] = null;
                    }
                } elseif ($gameState['strikes'] >= 3) {
                    $gameState['phase'] = 'steal';
                    $gameState['steal_turn'] = ($gameState['owner'] === 'player') ? 'ai' : 'player';
                    $msg = "3 Strikes! Opponent has a chance to STEAL!";
                }
            }
        }
    } elseif ($gameState['phase'] === 'steal') {
        $whoSteals = $gameState['steal_turn'] ?? (($gameState['owner'] === 'player') ? 'ai' : 'player');

        if ($whoSteals === 'ai' && $action === 'resolve_ai_steal') {
            $botSecs = rand(8, 15);
            $stealTry = botGuess($gameState, $qBank, $currentQKey);
            $stealDidWork = false;
            $stealPoints = 0;

            foreach ($currentQ['answers'] as $slot => $ans) {
                if (!$gameState['revealed'][$slot] && strcasecmp($stealTry, $ans['text']) === 0) {
                    $gameState['revealed'][$slot] = true;
                    $stealPoints = $ans['points'];
                    $stealDidWork = true;
                    break;
                }
            }

            if ($stealDidWork) {
                $gameState['round_bank'] += $stealPoints;
                $gameState['ai_score'] += $gameState['round_bank'];
                updateAiConfidence($gameState, 'ai');
                $msg = "Bot guessed '{$stealTry}' and successfully stole {$gameState['round_bank']} pts! (AI took {$botSecs}s)";
            } else {
                $gameState['player_score'] += $gameState['round_bank'];
                updateAiConfidence($gameState, 'player');
                $msg = "Bot guessed '{$stealTry}' and failed! You keep the banked {$gameState['round_bank']} pts.";
            }

            $gameState['round_bank'] = 0;
            $gameState['strikes'] = 0;
            $gameState['question_index']++;
            $gameState['revealed'] = array_fill(1, 8, false);
            unset($gameState['steal_turn']);
            if ($gameState['question_index'] >= count($gameQKeys)) {
                header("Location: game-over.php");
                exit();
            } else {
                $gameState['phase'] = 'faceoff';
                $gameState['sub_phase'] = 'player_turn';
                $gameState['faceoff_tries'] = ['player' => null, 'ai' => null];
                $gameState['owner'] = null;
            }
        } elseif ($whoSteals === 'player' && $action === 'submit') {
            $stealTry = $cleanGuess;
            $stealDidWork = false;
            $stealPoints = 0;

            foreach ($currentQ['answers'] as $slot => $ans) {
                if (!$gameState['revealed'][$slot] && strcasecmp($stealTry, $ans['text']) === 0) {
                    $gameState['revealed'][$slot] = true;
                    $stealPoints = $ans['points'];
                    $stealDidWork = true;
                    break;
                }
            }

            if ($stealDidWork) {
                $gameState['round_bank'] += $stealPoints;
                $gameState['player_score'] += $gameState['round_bank'];
                updateAiConfidence($gameState, 'player');
                $msg = "Successful Steal! You take {$gameState['round_bank']} pts.";
            } else {
                $gameState['ai_score'] += $gameState['round_bank'];
                updateAiConfidence($gameState, 'ai');
                $msg = "Steal failed! Rival keeps the banked {$gameState['round_bank']} pts.";
            }

            $gameState['round_bank'] = 0;
            $gameState['strikes'] = 0;
            $gameState['question_index']++;
            $gameState['revealed'] = array_fill(1, 8, false);
            unset($gameState['steal_turn']);
            if ($gameState['question_index'] >= count($gameQKeys)) {
                header("Location: game-over.php");
                exit();
            } else {
                $gameState['phase'] = 'faceoff';
                $gameState['sub_phase'] = 'player_turn';
                $gameState['faceoff_tries'] = ['player' => null, 'ai' => null];
                $gameState['owner'] = null;
            }
        }
    }
    
    $currentQKey = $gameQKeys[$gameState['question_index']];
    $currentQ = $qBank[$currentQKey];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Play Pokefeud against the rival trainer">
    <title>Pokefeud - Arena</title>
    <link rel="stylesheet" href="game-show.css">
</head>
<body>
    <div class="arena-wrapper">
        <header class="question-header">
            <span class="round-tag">Round <?php echo min(3, $gameState['question_index'] + 1); ?> of 3 | Phase: <?php echo strtoupper(htmlspecialchars($gameState['phase'], ENT_QUOTES, 'UTF-8')); ?> | Controller: <?php echo ucfirst(htmlspecialchars($gameState['owner'] ?? 'None', ENT_QUOTES, 'UTF-8')); ?></span>
            <h1 class="active-prompt">
                <?php echo htmlspecialchars($currentQ['prompt'], ENT_QUOTES, 'UTF-8'); ?>
            </h1>
        </header>

        <aside class="player-podium-left">
            <img src="Players/Nurse.png" alt="Trainer" class="trainer-avatar">
            <div class="podium-info">
                <h3><?php echo htmlspecialchars($_SESSION['username'] ?? 'Player', ENT_QUOTES, 'UTF-8'); ?></h3>
                <div class="score-badge">Score: <span id="p-score"><?php echo (int)$gameState['player_score']; ?></span></div>
            </div>
        </aside>

        <main class="game-board-center" style="position: relative;">
            <?php if ($showStrikeAnimation && $gameState['strikes'] > 0): ?>
                <div class="strike-modal-box">
                    <div class="strike-container-inner">
                        <?php for ($s = 0; $s < $gameState['strikes']; $s++): ?>
                            <img src="Icons/Strike.png" alt="Strike X" class="strike-img" />
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($showPointsAnimation && $earnedPoints > 0): ?>
                <div class="points-modal-box">
                    <div class="points-container-inner">
                        <span class="green-points-text">+<?php echo (int)$earnedPoints; ?> Points</span>
                    </div>
                </div>
            <?php endif; ?>

            <div class="board-info-bar">
                <span>Strikes: <?php echo (int)$gameState['strikes']; ?>/3 | Bank: <?php echo (int)$gameState['round_bank']; ?>pts</span>
            </div>

            <div class="survey-board-grid">
                <?php 
                    for ($i = 1; $i <= 8; $i++): 
                        $isRevealed = $gameState['revealed'][$i];
                        $boxText = $isRevealed ? htmlspecialchars($currentQ['answers'][$i]['text'], ENT_QUOTES, 'UTF-8') : "[ {$i} ]";
                        $boxPts  = $isRevealed ? $currentQ['answers'][$i]['points'] : "--";
                ?>
                    <div class="answer-slot <?php echo $isRevealed ? 'revealed' : 'hidden'; ?>">
                        <span class="slot-number">#<?php echo $i; ?></span>
                        <span class="slot-content"><?php echo $boxText; ?></span>
                        <span class="slot-points"><?php echo htmlspecialchars($boxPts, ENT_QUOTES, 'UTF-8'); ?>pts</span>
                    </div>
                <?php endfor; ?>
            </div>
        </main>

        <aside class="player-podium-right">
            <img src="Players/Officer.png" alt="Rival" class="trainer-avatar">
            <div class="podium-info">
                <h3>Rival Trainer (AI)</h3>
                <div class="score-badge">Score: <span><?php echo (int)$gameState['ai_score']; ?></span></div>
                <small style="color: #666;">Confidence: <?php echo ucfirst(htmlspecialchars($gameState['ai_level'], ENT_QUOTES, 'UTF-8')); ?></small>
            </div>
        </aside>

        <section class="control-deck">
            <?php if (!empty($msg)): ?>
                <p class="feedback-banner" role="status" aria-live="polite"><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></p>
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
                        <span style="font-weight: bold;">AI Guessed: <?php echo htmlspecialchars($gameState['faceoff_tries']['ai'], ENT_QUOTES, 'UTF-8'); ?></span>
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
            <?php elseif ($gameState['phase'] === 'steal' && ($gameState['steal_turn'] ?? (($gameState['owner'] === 'player') ? 'ai' : 'player')) === 'ai'): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="resolve_ai_steal">
                    <div class="guess-form-flex" style="justify-content: center; align-items: center; gap: 15px;">
                        <button type="submit" class="btn-submit">Bot Attempts Steal</button>
                    </div>
                </form>
            <?php else: ?>
                <form method="POST" class="guess-form-flex">
                    <?php if ($gameState['phase'] === 'board_play'): ?>
                        <button type="submit" name="action" value="skip" class="btn-skip">Skip</button>
                    <?php endif; ?>

                    <label for="player-guess" class="visually-hidden">Your answer</label>
                    <input type="text" id="player-guess" name="player_guess" class="guess-input-box" 
                           maxlength="40" autocomplete="off" autofocus
                           value="<?php echo htmlspecialchars($guessInput, ENT_QUOTES, 'UTF-8'); ?>" 
                           placeholder="<?php echo $gameState['phase'] === 'faceoff' ? 'Face-off guess...' : ($gameState['phase'] === 'steal' ? 'Enter steal guess...' : 'Type your answer...'); ?>">

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
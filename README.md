# PW2-Game-Show
In this Project we will be working on a gameshow. This will be used to tell you what the game is about and testing.

Login/Signup php:  The login and signin php are almost one in the same. Once you create an account the system will inform you that the account was saved and you can precend to the introductionphp. Saving the account is possible thanks to do and if statements in the php. All accounts at stored in a txt file.

introduction.php: This page is verry simple, it tells you what the game is including the rules before you strat the game. If this is your first time on this page, a cookie is implemented where it will welcome the you with your user name visable, it even reconizes if you visited the page before. 

gameshow.php: This this page, the player will be competing against the AI in order to gain points. How it works is that each question will be given a promt, both the human and ai will guess on the searchbar to determine who gets to go. Once the player or ai gets a turn, they will guess what is going to be on the board and will have 3 attempts to clear the entire board. if they fail to do so, the opposite player will get a chance to steal all their points, but if they fail to do so, the other player gets to keep their points. There are 3 rounds you and the ai will take part of, good luck ^^

gameover.php: On this page it will display who won the game in gameshow.php, as break down the gameplay by showing off both teams points. That's not all that's in this page, no matter if you win or lose, your username will be displayed in a score  in a leaderboard located on the right side of the screen.



Test 1 Signing up: This is a test to see what happens if I make an account in the site. What should happen is that it needs to transition to the login screen so that you log in the info recored and start playing the game. 

Test 1 Result:When I first attempted to log in, I signed up with a made up name and password, instead of taking me to the login screen it jumped me to the game, and didn't saved my account info.

Fix 1: Not only did I relocated sign up to the log in, I also added $userFile = __DIR__ . '/users.txt'; to make things easier for us in the php. Once you create a user name a text will show on screen to tell you that the account has been recorded to the users.txt


Test 2 Intro in CSS: This is to test if css is implemented correctly on the introduction php 
Test result: After some Test the log in was positioned in the wrong location.
Fix 2: Made some more adjustments in logoutlink and fixed the bug.

Test 3 Scoreboard: This is to test if the player's score would update in the leaderboard

Test 3 Result: After finishing the game, the learderboad did not update, and the win image did not display it's image.

Test 3 fix: Gave codd permission to read the score  directory.

Test 4 Failed Points: If the ai fails to steal points from the player, the player should keep the points
Test result: The system did not give the player or ai points after the failed steal, but it has been fixed.

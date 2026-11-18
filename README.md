# PW2-Game-Show
In this Project we will be working on a gameshow. This will be used to tell you what the game is about and testing.

Login/Signup php: 





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


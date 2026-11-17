# PW2-Game-Show
In this Project we will be working on a gameshow. This will be used to tell you what the game is about and testing.

Login/Signup php: 





Test 1 Signing up: When I first attempted to log in, I signed up with a made up name and password, instead of taking me to the login screen it jumped me to the game, and didn't saved my account info.

Fix 1: Not only did I relocated sign up to the log in, I also added $userFile = __DIR__ . '/users.txt'; to make things easier for us in the php. Once you create a user name a text will show on screen to tell you that the account has been recorded to the users.txt


Test 2 Intro in CSS: After some Test the log in was positioned in the wrong location.
Fix 2: Made some more adjustments in logoutlink and fixed the bug.
# SportSync Project Documentation

## 1. Project overview

SportSync is a PHP and MySQL sports-academy management system. It uses server-rendered PHP pages, HTML forms, SQL CRUD operations, CSS, and a small amount of vanilla JavaScript.

### Main technologies

- PHP running through XAMPP/Apache
- MySQL or MariaDB
- PDO for PHP database access
- HTML forms and server-rendered pages
- CSS in `assets/css/style.css`
- Vanilla JavaScript in `assets/js/app.js`
- PHP sessions for login state and flash messages

The project does not use React, Vue, Angular, Laravel, Symfony, Bootstrap, Tailwind, or an npm build system.

## 2. Main folders and files

| Path | Purpose |
|---|---|
| `config/` | Database and application configuration |
| `includes/` | Shared PHP functions and page layout |
| `assets/css/` | Application styles |
| `assets/js/` | Browser-side JavaScript |
| `Media/` | Logo and favicon images |
| `uploads/` | Uploaded profile pictures |
| `sportsync.sql` | Database creation script and seed sports |
| `database-schema.mmd` | Entity relationship diagram |
| `activity-diagram.mmd` | Application activity diagram |

## 3. Shared PHP files

### `config/config.php`

This file:

1. Defines database constants:
   - Host: `127.0.0.1`
   - Database: `sportsync_db`
   - User: `root`
   - Password: empty by default
2. Defines application name and tagline.
3. Starts a PHP session with `session_start()`.
4. Sets the Colombo timezone.
5. Creates the `$pdo` PDO database connection.
6. Enables PDO exceptions and associative-array query results.

### `includes/functions.php`

This is the common helper file loaded by application pages.

Important helpers:

- `e($value)`: escapes output with `htmlspecialchars()` to reduce HTML injection risk.
- `redirect($path)`: sends a browser redirect and stops the current script.
- `flash($type, $message)`: stores a one-time message in `$_SESSION`.
- `consumeFlash()`: reads and removes the stored flash message.
- `currentUser()`: returns the logged-in user from `$_SESSION["user"]`.
- `requireLogin()`: redirects visitors who are not logged in.
- `requireRole($roles)`: checks whether the logged-in user has an allowed role.
- `post($key)`: reads and trims a POST value.
- `submittedPassword()`: reads a password without trimming it.
- `validatePasswordConfirmation()`: checks password length and confirmation.
- `passwordInput()`: creates a reusable password input and show button.
- `roleLabel()`: changes the internal role name `player` to `Athlete`.
- `formatDateTime()`: formats database date/time values for display.
- `loadSports()`: reads all sports using SQL `SELECT`.
- `sportStatFields()`: returns sport-specific statistic field names.
- `selectedValues()`: reads checkbox values from a form.

### `includes/header.php`

The common header:

- Loads the shared functions.
- Gets the current user.
- Reads flash messages.
- Counts unread messages with SQL.
- Prints the HTML document header.
- Loads the logo, favicon, CSS file, and navigation.
- Shows different links for logged-in and logged-out users.
- Shows the message count for non-admin users.

### `includes/footer.php`

The common footer:

- Closes the main page.
- Prints the current year.
- Loads `assets/js/app.js`.
- Closes the HTML document.

## 4. PHP page explanations

### Public and authentication pages

#### `index.php`

The home page. It displays the SportSync introduction, public players, teams, and upcoming matches. It uses database `SELECT` queries and shared header/footer files.

#### `login.php`

The login page:

1. Reads the email and password from a POST form.
2. Finds an active user with SQL `SELECT`.
3. Checks the stored password using `password_verify()`.
4. Stores the user record in `$_SESSION["user"]`.
5. Redirects the user to `dashboard.php`.

#### `logout.php`

Destroys the login session and redirects the visitor to the public home page.

#### `register-academy.php`

Creates a new academy and its admin user:

- Validates required form fields.
- Reads selected sports.
- Inserts an academy into `academies`.
- Inserts academy/sport links into `academy_sports`.
- Inserts the admin into `users`.
- Redirects to login after success.

#### `join-academy.php`

Creates a membership request:

- Reads personal details, role, academy, sports, email, and password.
- Validates that selected sports are offered by the academy.
- Inserts a pending user into `users`.
- Inserts selected sports into `user_sports`.
- Inserts an approval request into `approvals`.

### Dashboard and profile pages

#### `dashboard.php`

Displays logged-in user information, academy name, team count, upcoming matches, and management links. It uses session data and SQL `SELECT` queries.

#### `profile.php`

Allows a logged-in user to:

- Update first name and last name.
- Update phone number.
- Change password.
- Select personal sports.
- Allow an admin to update academy sports.
- Upload a profile picture.
- Update the `users`, `academy_sports`, and `user_sports` tables.
- Update the matching values in the login session.

The image upload checks the upload error, file size, extension, and whether the file is a real image.

### Academy, team, and player pages

#### `add-venue.php`

Allows an admin to add a venue. It validates the name and location, then performs SQL `INSERT` into `venues`.

#### `create-team.php`

Allows an admin or coach to create a team:

- Loads sports, coaches, and players.
- Checks that the coach belongs to the selected sport.
- Inserts a team into `teams`.
- Inserts selected players into `team_players`.
- Creates an approval for coach-created teams.
- Sends a message to the assigned coach.

#### `teams.php`

Lists active teams and supports a search value from the URL. It uses SQL `SELECT` and joins academy and sport information.

#### `team.php`

Displays one team, its academy, sport, coach, and players. Logged-in players can submit a join request. The page also handles approval-related team membership behavior.

#### `players.php`

Lists active players and supports searching by a GET query. It reads player and academy data with SQL `SELECT`.

#### `player.php`

Displays one player's profile, academy, sports, teams, and statistics.

### Match and result pages

#### `create-match.php`

Allows an admin or coach to create a match:

- Reads title, sport, venue, teams, referee, and date/time.
- Loads valid teams and referees.
- Checks venue ownership.
- Checks that both teams are active and use the selected sport.
- Checks venue and participant conflicts.
- Inserts the event into `events`.
- Inserts participating teams into `event_teams`.
- Sends messages to players and coaches.

#### `matches.php`

Lists scheduled, completed, and available matches using SQL `SELECT` queries. It displays sport, academy, venue, teams, dates, and scores.

#### `match.php`

Displays one match and its related data:

- Sport and academy
- Venue
- Referee
- Participating teams
- Match status and scores
- Player statistics

#### `submit-result.php`

Allows an authorized admin, coach, or assigned referee to submit:

- Home score
- Away score
- Match completion status
- Player statistics

It updates `events` and inserts or updates rows in `player_stats`.

### Messages and approvals

#### `messages.php`

Displays messages for the logged-in user or academy. It can mark messages as read with SQL `UPDATE`.

#### `send-message.php`

Allows an admin or coach to send a message:

- To one active academy member, or
- To the whole academy

It inserts the message into `messages`.

#### `approvals.php`

Allows an admin to approve or reject:

- User registration requests
- Team creation requests
- Team join requests

It updates approval status and the related user/team/request record, then sends a notification message.

## 5. Database documentation

The database is created by `sportsync.sql`.

### Database setup

The SQL script:

1. Creates the `sportsync_db` database.
2. Selects the database.
3. Temporarily disables foreign-key checks.
4. Drops old application tables.
5. Creates all tables.
6. Enables foreign-key checks.
7. Inserts five initial sports.

### Tables

#### `academies`

Stores sports academy information:

- `id`: primary key
- `name`: unique academy name
- `address`: academy address
- `logo_path`: optional logo path
- `created_at`: creation time

#### `sports`

Stores available sports. Sport names are unique. The seed data contains Cricket, Football, Basketball, Hockey, and Rugby.

#### `academy_sports`

Junction table connecting academies and sports. Its composite primary key is `(academy_id, sport_id)`.

#### `users`

Stores all accounts:

- Personal information
- Academy relationship
- Email and password hash
- Role
- Profile status
- Profile photo

Roles include `admin`, `coach`, `player`, `athlete`, and `referee`.

#### `user_sports`

Junction table connecting users and sports. It records the sports for which a user is registered.

#### `venues`

Stores academy venues. A venue name must be unique inside one academy.

#### `teams`

Stores academy teams:

- Academy and sport
- Coach, captain, and vice-captain
- Pending, active, or rejected status

#### `team_players`

Junction table connecting teams and players. It also stores a sport role and join time.

#### `events`

Stores matches/events:

- Academy
- Title and type
- Sport and venue
- Start and end times
- Status
- Scores
- Creator and referee

The database checks that the end time is after the start time.

#### `event_teams`

Junction table connecting events and teams. It stores whether a team is `home` or `away`.

#### `approvals`

Stores admin approval requests. The `type` value identifies the related request, and `reference_id` points to the related record.

#### `team_join_requests`

Stores player requests to join teams. A player/team pair cannot have duplicate join requests.

#### `inter_academy_event_requests`

Stores invitations for teams from another academy to participate in an event.

#### `messages`

Stores academy messages:

- Sender
- Optional recipient
- Subject
- Body
- Read/unread status
- Creation time

When `recipient_id` is NULL, the message can represent an academy-wide message.

#### `player_stats`

Stores statistics for a player in an event:

- Score or runs
- Sport-specific JSON statistics
- Performance description
- User who submitted the statistics

The event/player pair is unique.

## 6. Database relationships

The most important relationships are:

```text
Academy 1 --- many Users
Academy many --- many Sports through academy_sports
User many --- many Sports through user_sports
Academy 1 --- many Teams
Team many --- many Users through team_players
Academy 1 --- many Venues
Academy 1 --- many Events
Event many --- many Teams through event_teams
Event 1 --- many PlayerStats
User 1 --- many Messages as sender
User 1 --- many Messages as recipient
```

Foreign keys use `CASCADE` when dependent records should be removed and `SET NULL` when the related person may leave while the main record remains.

## 7. CRUD operations

### Create

Examples:

- Academy registration creates an academy and admin.
- Join request creates a user and approval.
- Team creation inserts a team and team players.
- Match creation inserts an event and event teams.
- Sending a message inserts a message.

### Read

Examples:

- Login reads a user.
- Dashboard reads upcoming events.
- Player and team pages read related records.
- Header reads unread message count.

### Update

Examples:

- Profile updates user details.
- Login approval updates user status.
- Match result updates scores and status.
- Messages update read status.
- Admin approvals update request status.

### Delete

Examples:

- Profile update deletes and recreates sport links.
- Profile update deletes and recreates academy sport links for admins.
- Database setup drops old tables before recreating them.

## 8. CSS documentation

File: `assets/css/style.css`

### CSS variables

The `:root` section defines reusable colors for blue, orange, background, text, border, success, and error states.

### Global styling

- Universal `box-sizing`
- Body font, color, background, and line height
- Link colors and hover colors

### Layout components

- `.site-header`: sticky top navigation
- `.brand`: logo area
- `.main-nav`: navigation links
- `.page-shell`: centered page content
- `.site-footer`: footer layout

### Content components

- `.hero`: landing-page hero section
- `.hero-card`: hero information card
- `.card`: reusable content card
- `.grid`: three-column content layout
- `.section-head`: section title layout
- `.profile-head`: profile summary section
- `.stats-grid`: statistic cards

### Forms

- `.form-card`: centered form container
- `.form-grid`: two-column form layout
- `.form-actions`: buttons and links
- `.sport-options`: checkbox group
- `.password-field`: password input and toggle button
- `.searchbar`: search form layout

### Tables and messages

- `.table-wrap`: horizontal scrolling table wrapper
- `table`, `th`, and `td`: table formatting
- `.flash.success`: success message
- `.flash.error`: error message
- `.empty`: empty-result message

### Responsive design

At widths below 760px:

- Header becomes a column
- Hero changes to one column
- Grids change to one column
- Footer becomes a column

At widths below 420px:

- Navigation spacing and font sizes reduce
- Buttons become full width in hero actions
- Page padding becomes smaller

## 9. JavaScript documentation

File: `assets/js/app.js`

### Form confirmation

The script selects forms with `data-confirm` and listens for the `submit` event. If the user cancels the browser confirmation dialog, the form submission is stopped.

### Password visibility

The script listens for clicks on `.password-toggle` buttons. It:

1. Finds the related password input.
2. Changes its type between `password` and `text`.
3. Changes the button text between `Show` and `Hide`.
4. Updates the button accessibility label.

This uses basic DOM selectors and `addEventListener()`.

## 10. User flows

### Academy registration

```text
Register academy
    -> validate form
    -> create academy
    -> add academy sports
    -> create active admin
    -> redirect to login
```

### Member registration

```text
Join academy
    -> validate form
    -> create pending user
    -> add user sports
    -> create approval request
    -> admin approves
    -> user can log in
```

### Team creation

```text
Admin/coach opens create team
    -> choose sport
    -> choose coach and players
    -> validate selected people
    -> create team
    -> create approval when needed
```

### Match creation

```text
Admin/coach opens create match
    -> select sport, teams, venue, referee, time
    -> check ownership and conflicts
    -> create event
    -> attach teams
    -> notify participants
```

### Result submission

```text
Authorized user opens match
    -> enter scores and player statistics
    -> update event
    -> save player statistics
    -> mark event completed
```

## 11. Security and validation

The project includes the following basic protections:

- Passwords are stored using `password_hash()`.
- Login uses `password_verify()`.
- PDO prepared statements are used for user-supplied SQL values.
- `e()` escapes values printed into HTML.
- Login-required pages call `requireLogin()`.
- Role-restricted pages call `requireRole()`.
- Form values are validated on the server.
- Email values are checked with `filter_var()`.
- Profile image size and type are checked.
- Database unique keys prevent duplicate emails, academy names, team names, and membership links.
- Foreign keys protect relationships between records.

## 12. Lecture concepts demonstrated

This project demonstrates:

- PHP variables, arrays, conditions, and loops
- PHP functions
- PHP forms and `GET`/`POST`
- File inclusion with `include` and `require_once`
- Sessions with `$_SESSION`
- MySQL connection through PDO
- SQL CRUD
- SQL joins and relationships
- HTML forms
- CSS layout and responsive media queries
- JavaScript DOM selection
- JavaScript `addEventListener`
- Basic client-side interaction
- Server-side validation
- Basic web security

The project combines basic lecture concepts into a larger application. The individual technologies remain PHP, MySQL, HTML, CSS, and vanilla JavaScript.

## 13. Detailed PHP page logic

Every page follows the same general pattern:

1. Load `config/config.php` directly or load `includes/functions.php`, which loads the configuration.
2. Start the page-specific PHP logic before printing HTML.
3. Check the session and role when the page is protected.
4. Read `$_GET` or `$_POST` values.
5. Validate the values on the server.
6. Run a prepared SQL query or a safe fixed query.
7. Store a flash message when user feedback is needed.
8. Redirect after a successful form submission to prevent duplicate form submissions.
9. Load `includes/header.php` and `includes/footer.php` to render the common layout.

### `config/config.php` logic

This is the application bootstrap file. It defines the database connection values and application constants, starts the session, sets the timezone, creates the PDO connection, and configures PDO to throw exceptions and return associative arrays. Because the session and database connection are created here, every included page can use `$_SESSION` and `$pdo`.

### `includes/functions.php` logic

This file contains reusable functions so that validation, security, session checks, and common display operations are not repeated in every page. The function-level explanation is in section 14.

### `includes/header.php` logic

The header loads shared functions, obtains the current logged-in user, consumes any one-time flash message, and counts unread messages for the current user or academy. It then prints the opening HTML structure, navigation links, role-specific links, and flash message. The navigation changes according to whether the visitor is logged in and which role is stored in the session.

### `includes/footer.php` logic

The footer closes the main content area, prints the current year, loads `assets/js/app.js`, and closes the HTML document. Loading the JavaScript here means the DOM already exists when the script attaches its event listeners.

### `index.php` logic

The home page is public. It loads public-facing player, team, and upcoming match data with `SELECT` queries. The result arrays are looped over to create cards or lists. Empty results show an empty-state message instead of failing. The shared header and footer provide navigation and styling.

### `login.php` logic

1. Display the login form on a normal `GET` request.
2. On `POST`, read the email and password.
3. Validate that both values were supplied and normalize the email.
4. Select an active user by email using a prepared statement.
5. Use `password_verify()` to compare the submitted password with the stored hash.
6. If valid, store the user data in `$_SESSION["user"]` and redirect to `dashboard.php`.
7. If invalid, keep the visitor on the page and show an error message.

The page does not store the plain-text password in the session or database.

### `logout.php` logic

The page clears the login session, removes session data, and redirects to the public home page. It does not display a normal page because its only purpose is to end the authenticated session.

### `register-academy.php` logic

1. Display academy, admin, password, and sport-selection fields.
2. On `POST`, validate required fields, email format, password confirmation, and selected sports.
3. Start a transaction so the academy, academy sports, and admin account succeed or fail together.
4. Insert the academy and obtain its generated ID.
5. Insert one row in `academy_sports` for each selected sport.
6. Hash the admin password and insert the admin user linked to the academy.
7. Commit the transaction and redirect to login.
8. Roll back and show an error if a database constraint or validation rule fails.

### `join-academy.php` logic

1. Load the selected academy and its available sports.
2. Display personal, account, role, and sport fields.
3. On `POST`, validate the user details, email, password, academy, and selected sports.
4. Confirm that every selected sport belongs to the selected academy.
5. Insert the user with a pending or inactive status.
6. Insert the selected user-sport links.
7. Create an `approvals` row for an administrator.
8. The user can log in only after the approval workflow activates the account.

### `dashboard.php` logic

The page first calls the login guard. It then uses the session user ID and academy ID to read summary information such as the academy name, team count, upcoming events, and management actions. The HTML displays different action links according to the user's role.

### `profile.php` logic

1. Require an authenticated user and load the current user record.
2. On `POST`, identify which profile form was submitted.
3. Validate names, phone number, selected sports, and password confirmation when applicable.
4. For a password change, hash the new password and update only the password column.
5. For personal sports, delete the user's old `user_sports` rows and insert the new selected rows.
6. For an admin's academy sports, delete the old `academy_sports` rows and insert the new selected rows.
7. For an image upload, check upload status, file size, extension, and real image content before moving the file.
8. Update the `users` row and refresh the matching session values.
9. Redirect back to the profile page with a success or error flash message.

### `add-venue.php` logic

The page requires an admin. It reads the venue name and location, checks that both are present, checks the academy context from the session, and inserts the venue into `venues`. A duplicate name or database error is reported instead of creating a second venue.

### `create-team.php` logic

1. Require an admin or coach.
2. Load available sports, eligible coaches, and eligible players from the current academy.
3. Read the chosen sport, team name, coach, and player IDs.
4. Validate that the coach and players belong to the academy and are suitable for the selected sport.
5. Insert the team with the correct status.
6. Insert selected players into `team_players`.
7. If a coach created the team, create an approval request for an admin.
8. Notify the assigned coach with a row in `messages`.
9. Use a transaction so the team and its members are not partially created.

### `teams.php` logic

The page reads an optional search term from `$_GET`, builds a `SELECT` query for active teams in the relevant public or academy scope, and joins academy and sport names. The filtered rows are looped into the teams table or cards. Each team links to `team.php` using its ID.

### `team.php` logic

1. Read and validate the team ID from the URL.
2. Select the team, academy, sport, coach, and player list.
3. Show the team details and current membership.
4. If the visitor is an eligible logged-in player, accept a join form.
5. Check for an existing membership or pending request.
6. Insert a `team_join_requests` row when a new request is valid.
7. Let the approval page later accept or reject the request.

### `players.php` logic

The page reads a search value from `$_GET`, selects active player/athlete records, joins their academy information, and filters the result when a search term is supplied. It renders one link per player to `player.php`.

### `player.php` logic

The page reads the player ID, selects the player's profile and academy, loads the player's sports and teams through junction tables, and loads event statistics from `player_stats`. It displays the profile even when one of the related collections is empty.

### `create-match.php` logic

1. Require an admin or coach and load valid sports, teams, venues, and referees.
2. Read title, sport, venue, home team, away team, referee, and start/end times.
3. Validate required values and ensure home and away teams are different.
4. Confirm that the venue belongs to the academy and both teams use the selected sport.
5. Check that the venue, teams, and referee are not already occupied during the requested time.
6. Insert the event in `events`.
7. Insert the home and away relationships in `event_teams`.
8. Notify relevant coaches and players.
9. Commit the transaction and redirect to the match page.

### `matches.php` logic

The page selects matches and joins sport, venue, academy, and team information. It can show scheduled and completed events and uses the event status and scores to display the correct state. Each result links to `match.php`.

### `match.php` logic

The page reads an event ID, loads its event details, joins both participating teams, loads the venue and referee, and reads related player statistics. It displays actions such as result submission only when the current user has the required role or assignment.

### `submit-result.php` logic

1. Require an admin, coach, or assigned referee.
2. Load the event and verify that the user is authorized for that event.
3. Read and validate home score, away score, completion status, and statistic fields.
4. Update the event scores and status.
5. For every submitted player statistic, insert a new row or update the existing event/player row.
6. Encode sport-specific values into the statistics column when required.
7. Redirect to the match page with a result message.

### `messages.php` logic

The page requires login and selects messages addressed directly to the current user or broadcast to the user's academy. It displays sender, subject, body, and read status. A read action updates the selected message only when it belongs to the current user's allowed scope.

### `send-message.php` logic

1. Require an admin or coach.
2. Load valid academy recipients.
3. Read the recipient, subject, and message body.
4. Validate that the recipient is either a valid academy member or the academy-wide option.
5. Insert one message row with the sender and optional recipient.
6. Redirect to the messages page after success.

### `approvals.php` logic

1. Require an academy admin.
2. Load pending approval rows and their related users, teams, or join requests.
3. On an approval action, verify the approval ID and request type.
4. Update the related record, such as activating a user or changing a team membership status.
5. Mark the approval row as approved or rejected.
6. Send a notification to the affected user when appropriate.
7. Use a transaction so the request and approval status remain consistent.

## 14. Detailed function logic

### `e($value)`

This is the output-escaping helper. It changes `NULL` to an empty string and passes the value to `htmlspecialchars()` with quotes and UTF-8 enabled. It is used when database or form values are printed inside HTML, reducing the risk that a value is interpreted as HTML or JavaScript.

### `redirect($path)`

This sends an HTTP `Location` header and immediately calls `exit()`. Stopping execution is important because otherwise PHP could continue rendering the old page after a redirect.

### `flash($type, $message)`

This stores one short message in `$_SESSION["flash"]`. The next page can display it after a redirect. The array stores both a presentation type such as `success` or `error` and the message text.

### `consumeFlash()`

This reads the flash array, removes it from the session, and returns it. Removing it immediately makes the message one-time-only.

### `currentUser()`

This returns the user array saved in `$_SESSION["user"]`. If there is no login session, it returns `NULL`, allowing pages to distinguish guests from logged-in users.

### `requireLogin()`

This calls `currentUser()`. If no user exists, it stores an error flash message, redirects to `login.php`, and stops the request. If a user exists, it returns the user array to the calling page.

### `requireRole($roles)`

This first applies the login check, then uses `in_array()` to compare the session role with the allowed roles. An unauthorized user receives a flash message and is redirected to `index.php`. Authorized pages receive the user array.

### `post($key)`

This reads a named POST value, converts a missing value to an empty string, casts it to a string, and trims surrounding whitespace. It is suitable for names, email addresses, titles, and other normal text fields.

### `submittedPassword()`

This reads the password without trimming it. Passwords should not be changed silently by removing leading or trailing spaces, so the raw submitted value is preserved for verification or hashing.

### `validatePasswordConfirmation()`

This reads the password and confirmation fields, checks that the password has at least eight characters, and checks that both values are identical. It returns an error string when invalid and `NULL` when valid, allowing forms to display the returned message.

### `passwordInput($name, $autocomplete, $required)`

This builds the repeated password input HTML used by forms. It escapes the input name and autocomplete value, adds the `required` attribute when requested, and adds a button with the `.password-toggle` class. The JavaScript file uses that class to show or hide the password.

### `roleLabel($role)`

This converts the internal role value `player` to the friendlier label `Athlete`. Other role values are formatted with `ucfirst()`. It changes only display text; it does not change authorization data.

### `formatDateTime($value)`

This converts a database date/time value into a readable format such as `Oct 12, 2026 · 2:30 PM`. If the value is empty, it returns `TBA`, so incomplete schedules do not produce a confusing blank value.

### `loadSports($pdo)`

This runs a fixed `SELECT id, name FROM sports ORDER BY name` query and returns all sports as an associative array. Since there is no user-supplied SQL fragment, no parameter is required.

### `sportStatFields($sport)`

This is a configuration function for result forms. It receives a sport name and returns nested groups of statistic keys and display labels. For example, Cricket returns batting, bowling, and fielding fields, while Football returns attacking, defensive, goalkeeper, and discipline fields. The result page can loop over this structure to build the correct form without writing a separate form for every sport. Unknown sports return an empty array.

### `selectedValues($key)`

This reads a checkbox or multi-select POST array. It converts the input to an array, casts values to integers, removes zero/empty values, removes duplicates, and returns a clean numerically indexed array. This prevents repeated or non-numeric IDs from being passed into later validation and insert logic.

## 15. How the functions work together

For a protected form, the usual call sequence is:

```text
requireRole(...)
    -> currentUser()
    -> requireLogin()
    -> read post(...)
    -> validate values
    -> run PDO query
    -> flash(...)
    -> redirect(...)
```

For output, the sequence is:

```text
query database
    -> receive associative rows
    -> formatDateTime(...) or roleLabel(...)
    -> e(...) before printing HTML
```

This separation keeps access control, input reading, validation, database work, and HTML output understandable while still using only ordinary PHP functions, sessions, forms, PDO, SQL, HTML, CSS, and vanilla JavaScript.

## 16. Recent team and match changes

### Admin profile sports

Administrators now manage only the academy's sports in the profile page. The `My sports` section is hidden for admins because an admin represents the academy rather than registering as a playing member. Admin profile updates therefore change `academy_sports`, while player, athlete, coach, and referee profile updates can still change their own `user_sports` rows.

### Team member management

The active team page allows the academy admin and the assigned team coach to add or remove active players. The page:

1. Checks that the user belongs to the same academy.
2. Allows an admin, or the coach assigned to that team, to manage members.
3. Loads only active players registered for the team's sport.
4. Deletes the current `team_players` rows for that team.
5. Inserts the selected valid players again inside a transaction.
6. Keeps existing `sport_role` values when the edit form does not replace them.

This makes the checkbox list the current source of truth: checked players remain or are added, and unchecked players are removed.

### Match detail page

The match detail page now shows:

- Each participating team in its own team information card.
- The academy and home/away side for each team.
- The players belonging to each specific team.
- A separate player score/statistics area for that exact match.

The player query joins `event_teams`, `teams`, `team_players`, `users`, and `player_stats`. Therefore a player is shown under the correct team and the statistics shown are limited to the current event ID.

### Editing submitted results

Admins, coaches, and the assigned referee can open the result form even after the match is completed. Existing scores and JSON statistics are loaded into the form. Submitting the form updates the event and uses the `event_id` plus `player_id` unique pair to update each existing player-stat row instead of creating a duplicate. The same transaction saves the match score and all player statistics together.

### Match team display

The match page no longer repeats separate `Home` and `Away` badges in the team information cards. Each team card shows the team name, its academy name, and its own player list. In player lists and match score cards, the academy name is displayed in bold before the player name. If a seeded display name already starts with the academy name, the page removes that duplicate prefix before rendering it.

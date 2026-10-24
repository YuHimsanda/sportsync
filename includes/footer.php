</main>
<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-about">
            <span class="footer-logo">Sport<em>Sync</em></span>
            <p>Connecting players, teams and academies  one place to manage matches, stats and messages.</p>
        </div>

        <div class="footer-col">
            <h4>Explore</h4>
            <a href="players.php">Players</a>
            <a href="matches.php">Matches</a>
            <a href="teams.php">Teams</a>
        </div>

        <div class="footer-col">
            <h4>Account</h4>
            <?php if (isset($user) && $user): ?>
                <a href="dashboard.php">Dashboard</a>
                <a href="profile.php">Profile</a>
                <a href="logout.php">Log out</a>
            <?php else: ?>
                <a href="login.php">Log in</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="footer-bottom">
        <span>© <?= date("Y") ?> SportSync. All rights reserved.</span>
    </div>
</footer>
<script>
(function () {
    var btn = document.getElementById("navToggle");
    var nav = document.getElementById("mainNav");
    if (!btn || !nav) return;
    btn.addEventListener("click", function () {
        var open = nav.classList.toggle("open");
        btn.classList.toggle("open", open);
        btn.setAttribute("aria-expanded", open ? "true" : "false");
    });
})();
</script>
<script src="assets/js/app.js?v=2"></script>
</body>
</html>

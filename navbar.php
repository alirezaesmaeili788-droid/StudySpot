<?php

require_once "helpers.php";

if (!function_exists('nav_active_class')) {
    function nav_active_class(array $pages, string $currentPage): string
    {
        return in_array($currentPage, $pages, true) ? ' active' : '';
    }
}

$currentPage = basename($_SERVER['PHP_SELF'] ?? 'index.php');
$newContacts = 0;

if (is_admin()) {
    if (!isset($conn) || !($conn instanceof mysqli)) {
        require_once "db.php";
    }

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM contact_messages WHERE status='new'");
    $stmt->execute();
    $newContacts = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();
}
?>
<div class="nav-back fixed-top"></div>
<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand me-auto" href="index.php">StudySpot</a>

        <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
            <div class="offcanvas-header navbar-offcanvas-header">
                <h5 class="offcanvas-title" id="offcanvasNavbarLabel">
                    <a class="navbar-brand me-auto" href="index.php">StudySpot</a>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Schliessen"></button>
            </div>

            <div class="offcanvas-body navbar-offcanvas-body">
                <ul class="navbar-nav justify-content-center flex-grow-1 pe-3">
                    <li class="nav-item">
                        <a class="nav-link mx-lg-4<?= nav_active_class(['index.php'], $currentPage) ?>" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link mx-lg-4<?= nav_active_class(['spots.php', 'Spots.php', 'spot.php'], $currentPage) ?>" href="spots.php">Spots</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link mx-lg-4<?= nav_active_class(['kontakt.php'], $currentPage) ?>" href="kontakt.php">Kontakt</a>
                    </li>

                    <?php if (current_role() === 'owner' || is_admin()): ?>
                        <li class="nav-item">
                            <a class="nav-link mx-lg-4<?= nav_active_class(['ort_anmelden.php'], $currentPage) ?>" href="ort_anmelden.php">Ort anmelden</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link mx-lg-4<?= nav_active_class(['owner_home.php', 'spot_edit.php', 'spot_delete.php'], $currentPage) ?>" href="owner_home.php">Meine Orte</a>
                        </li>
                    <?php endif; ?>

                    <?php if (is_admin()): ?>
                        <li class="nav-item">
                            <a class="nav-link mx-lg-4<?= nav_active_class(['admin_requests.php'], $currentPage) ?>" href="admin_requests.php">Anfragen</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link mx-lg-4<?= nav_active_class(['admin_contacts.php'], $currentPage) ?>" href="admin_contacts.php">
                                Kontakt
                                <?php if ($newContacts > 0): ?>
                                    <span class="badge bg-danger ms-1"><?= $newContacts ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (is_logged_in()): ?>
                        <li class="nav-item d-lg-none">
                            <a class="nav-link mx-lg-4<?= nav_active_class(['account.php'], $currentPage) ?>" href="account.php">Mein Account</a>
                        </li>
                        <li class="nav-item d-lg-none">
                            <a class="nav-link mx-lg-4" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item d-lg-none">
                            <a class="nav-link mx-lg-4<?= nav_active_class(['login.php'], $currentPage) ?>" href="login.php">Login</a>
                        </li>
                        <li class="nav-item d-lg-none">
                            <a class="nav-link mx-lg-4<?= nav_active_class(['register.php'], $currentPage) ?>" href="register.php">Registrieren</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <?php if (is_logged_in()): ?>
            <a href="account.php" class="login-button d-none d-lg-inline"><?= e($_SESSION["user_name"] ?? "Mein Account") ?></a>
            <a href="logout.php" class="login-button d-none d-lg-inline ms-2">Logout</a>
        <?php else: ?>
            <a href="login.php" class="login-button d-none d-lg-inline">Login</a>
            <a href="register.php" class="login-button d-none d-lg-inline ms-2">Registrieren</a>
        <?php endif; ?>

        <button class="navbar-toggler pe-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar" aria-label="Menue oeffnen">
            <span class="navbar-toggler-icon"></span>
        </button>
    </div>
</nav>

<?php

require_once "helpers.php";
require "db.php";

$q = trim($_GET["q"] ?? "");
$type = trim($_GET["type"] ?? "");
$wifi = $_GET["wifi"] ?? "";
$power = $_GET["power_outlets"] ?? "";
$group = $_GET["group_friendly"] ?? "";
$quiet = trim($_GET["quiet_level"] ?? "");

$sql = "
    SELECT
        s.*,
        ROUND(AVG(r.rating), 1) AS avg_rating,
        COUNT(r.id) AS review_count
    FROM spots s
    LEFT JOIN reviews r ON r.spot_id = s.id
    WHERE 1 = 1
";
$params = [];
$types = "";

if ($q !== "") {
    $sql .= " AND (s.name LIKE ? OR s.address LIKE ? OR s.city LIKE ? OR s.zip LIKE ?)";
    $like = "%" . $q . "%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "ssss";
}

if ($type !== "" && array_key_exists($type, spot_type_options())) {
    $sql .= " AND s.type = ?";
    $params[] = $type;
    $types .= "s";
}

if ($wifi !== "" && ($wifi === "0" || $wifi === "1")) {
    $sql .= " AND s.wifi = ?";
    $params[] = (int)$wifi;
    $types .= "i";
}

if ($power !== "" && ($power === "0" || $power === "1")) {
    $sql .= " AND s.power_outlets = ?";
    $params[] = (int)$power;
    $types .= "i";
}

if ($group !== "" && ($group === "0" || $group === "1")) {
    $sql .= " AND s.group_friendly = ?";
    $params[] = (int)$group;
    $types .= "i";
}

if ($quiet !== "" && array_key_exists($quiet, STUDYSPOT_QUIET_LEVELS)) {
    $sql .= " AND s.quiet_level = ?";
    $params[] = $quiet;
    $types .= "s";
}

$sql .= " GROUP BY s.id ORDER BY CASE WHEN COUNT(r.id) = 0 THEN 1 ELSE 0 END, AVG(r.rating) DESC, s.created_at DESC, s.id DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$spots = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>StudySpot | Spots</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="register.css">
</head>
<body>

<?php include "navbar.php"; ?>

<main class="page-space">
    <div class="wrap">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h1 style="color:#1F5E3B; margin:0;">Lernorte entdecken</h1>
                <p class="sub" style="margin:6px 0 0 0;">Filtere nach Ausstattung und finde den Spot, der zu deiner Lernsituation passt.</p>
            </div>
            <div class="pill"><?= count($spots) ?> Treffer</div>
        </div>

        <form class="row g-2 mb-4" method="get" action="spots.php">
            <div class="col-12 col-lg-4">
                <input class="form-control" name="q" placeholder="Bezirk, Adresse oder Name" value="<?= e($q) ?>">
            </div>

            <div class="col-6 col-lg-2">
                <select class="form-select" name="type">
                    <option value="">Alle Typen</option>
                    <?php foreach (spot_type_options() as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= selected($value, $type) ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-lg-2">
                <select class="form-select" name="wifi">
                    <option value="">WLAN (egal)</option>
                    <option value="1" <?= selected("1", (string)$wifi) ?>>WLAN: ja</option>
                    <option value="0" <?= selected("0", (string)$wifi) ?>>WLAN: nein</option>
                </select>
            </div>

            <div class="col-6 col-lg-2">
                <select class="form-select" name="power_outlets">
                    <option value="">Steckdosen (egal)</option>
                    <option value="1" <?= selected("1", (string)$power) ?>>Steckdosen: ja</option>
                    <option value="0" <?= selected("0", (string)$power) ?>>Steckdosen: nein</option>
                </select>
            </div>

            <div class="col-6 col-lg-2">
                <select class="form-select" name="group_friendly">
                    <option value="">Gruppen (egal)</option>
                    <option value="1" <?= selected("1", (string)$group) ?>>Geeignet</option>
                    <option value="0" <?= selected("0", (string)$group) ?>>Eher nicht</option>
                </select>
            </div>

            <div class="col-6 col-lg-2">
                <select class="form-select" name="quiet_level">
                    <option value="">Lautstaerke</option>
                    <?php foreach (STUDYSPOT_QUIET_LEVELS as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= selected($value, $quiet) ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-lg-2 d-grid">
                <button class="btn btn-outline" type="submit">Filtern</button>
            </div>

            <div class="col-12 col-lg-2 d-grid">
                <a class="btn btn-primary" href="spots.php">Zuruecksetzen</a>
            </div>
        </form>

        <div class="row g-4">
            <?php if (!$spots): ?>
                <div class="col-12">
                    <div class="alert alert-info">Keine Spots gefunden. Passe die Filter an oder starte eine neue Suche.</div>
                </div>
            <?php endif; ?>

            <?php foreach ($spots as $spot): ?>
                <div class="col-12 col-lg-6">
                    <div class="p-3 bg-white spot-card h-100">
                        <?php if (!empty($spot["image_url"])): ?>
                            <img src="<?= e($spot["image_url"]) ?>" class="w-100 mb-3" alt="<?= e($spot["name"]) ?>">
                        <?php else: ?>
                            <div class="w-100 mb-3 spot-card-placeholder">Kein Bild vorhanden</div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <h3 style="margin:0;"><?= e($spot["name"]) ?></h3>
                                <div class="d-flex flex-wrap gap-2 align-items-center mt-2">
                                    <div class="pill"><?= e($spot["type"]) ?></div>
                                    <?php if (!empty($spot["review_count"])): ?>
                                        <div class="small text-success fw-semibold">⭐ <?= e((string)$spot["avg_rating"]) ?> <span class="text-muted">(<?= (int)$spot["review_count"] ?>)</span></div>
                                    <?php else: ?>
                                        <div class="small text-muted">Noch keine Bewertungen</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <p class="sub mt-2 mb-2" style="font-size:14px;">
                            <?= e(trim(($spot["address"] ?? "") . ", " . ($spot["zip"] ?? "") . " " . ($spot["city"] ?? ""))) ?>
                        </p>

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="badge text-bg-light"><?= !empty($spot["wifi"]) ? "WLAN" : "Kein WLAN" ?></span>
                            <span class="badge text-bg-light"><?= !empty($spot["power_outlets"]) ? "Steckdosen" : "Ohne Steckdosen" ?></span>
                            <span class="badge text-bg-light"><?= !empty($spot["group_friendly"]) ? "Gruppenfreundlich" : "Einzellernen" ?></span>
                            <span class="badge text-bg-light"><?= e(quiet_label($spot["quiet_level"] ?? "")) ?></span>
                        </div>

                        <p style="color:var(--muted); margin:0 0 10px 0;">
                            <?= e(mb_strimwidth($spot["description"] ?? "", 0, 160, "...")) ?>
                        </p>

                        <div class="d-flex gap-2 mt-auto">
                            <a class="btn btn-outline" href="spot.php?id=<?= (int)$spot["id"] ?>">Mehr Infos</a>

                            <?php if (can_manage_spot($spot["created_by"] ?? 0)): ?>
                                <a class="btn btn-primary" href="spot_edit.php?id=<?= (int)$spot["id"] ?>">Bearbeiten</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

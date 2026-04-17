<?php

require "auth.php";
require "db.php";

require_role(['owner', 'admin']);

$userId = current_user_id();
$role = current_role();
$isAdminView = $role === 'admin';

if ($isAdminView) {
    $spotsQuery = "SELECT id, name, type, address, zip, city, image_url, wifi, power_outlets, group_friendly, quiet_level, created_at, created_by
                   FROM spots
                   ORDER BY created_at DESC, id DESC";
    $spotStmt = $conn->prepare($spotsQuery);
} else {
    $spotsQuery = "SELECT id, name, type, address, zip, city, image_url, wifi, power_outlets, group_friendly, quiet_level, created_at, created_by
                   FROM spots
                   WHERE created_by = ?
                   ORDER BY created_at DESC, id DESC";
    $spotStmt = $conn->prepare($spotsQuery);
    $spotStmt->bind_param("i", $userId);
}
$spotStmt->execute();
$spots = $spotStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$spotStmt->close();

if ($isAdminView) {
    $requestQuery = "SELECT id, place_name AS name, place_type AS type, street AS address, zip, city, hours, status, photo_url, created_at, created_by
                     FROM place_requests
                     ORDER BY created_at DESC, id DESC";
    $requestStmt = $conn->prepare($requestQuery);
} else {
    $requestQuery = "SELECT id, place_name AS name, place_type AS type, street AS address, zip, city, hours, status, photo_url, created_at, created_by
                     FROM place_requests
                     WHERE created_by = ?
                     ORDER BY created_at DESC, id DESC";
    $requestStmt = $conn->prepare($requestQuery);
    $requestStmt->bind_param("i", $userId);
}
$requestStmt->execute();
$requests = $requestStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$requestStmt->close();

$pendingRequests = 0;
foreach ($requests as $requestRow) {
    if (($requestRow["status"] ?? "") === "pending") {
        $pendingRequests++;
    }
}

function badge_status(string $status): string
{
    $map = [
        "pending" => ["text" => "In Pruefung", "class" => "bg-warning text-dark"],
        "approved" => ["text" => "Freigegeben", "class" => "bg-success"],
        "rejected" => ["text" => "Abgelehnt", "class" => "bg-danger"],
    ];

    $badge = $map[$status] ?? ["text" => $status, "class" => "bg-secondary"];
    return '<span class="badge ' . $badge["class"] . '">' . e($badge["text"]) . '</span>';
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>StudySpot | Owner-Bereich</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="Homepage-body">

<?php include "navbar.php"; ?>

<main class="mt-5 pt-4">
    <div class="container py-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1"><?= $isAdminView ? "Partner- und Spot-Ueberblick" : "Meine Orte" ?></h1>
                <div class="text-muted">
                    <?= $isAdminView ? "Als Admin siehst du alle eingereichten und angelegten Orte." : "Verwalte deine freigegebenen Spots und verfolge offene Einreichungen." ?>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-success" href="ort_anmelden.php">Neuen Ort einreichen</a>
                <?php if ($isAdminView): ?>
                    <a class="btn btn-outline-success" href="admin_requests.php">Admin-Anfragen</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="bg-white rounded-4 shadow-sm p-4 h-100">
                    <div class="text-muted small">Spots</div>
                    <div class="display-6 fw-bold text-success"><?= count($spots) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="bg-white rounded-4 shadow-sm p-4 h-100">
                    <div class="text-muted small">Anfragen</div>
                    <div class="display-6 fw-bold text-success"><?= count($requests) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="bg-white rounded-4 shadow-sm p-4 h-100">
                    <div class="text-muted small">Offen</div>
                    <div class="display-6 fw-bold text-success"><?= $pendingRequests ?></div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-4 shadow-sm p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                <h2 class="h4 mb-0"><?= $isAdminView ? "Alle Spots" : "Meine Spots" ?></h2>
                <a href="spots.php" class="btn btn-outline-success btn-sm">Zur Spot-Liste</a>
            </div>

            <?php if (!$spots): ?>
                <div class="alert alert-info mb-0">Noch keine Spots vorhanden.</div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($spots as $spot): ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="card h-100 border-0 shadow-sm">
                                <?php if (!empty($spot["image_url"])): ?>
                                    <img src="<?= e($spot["image_url"]) ?>" class="card-img-top" alt="<?= e($spot["name"]) ?>" style="height: 220px; object-fit: cover;">
                                <?php endif; ?>

                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between gap-2">
                                        <h5 class="card-title mb-1"><?= e($spot["name"]) ?></h5>
                                        <span class="badge bg-dark"><?= e($spot["type"]) ?></span>
                                    </div>
                                    <div class="text-muted small mb-2"><?= e($spot["zip"] . " " . $spot["city"]) ?></div>

                                    <div class="d-flex flex-wrap gap-1 mb-2">
                                        <span class="badge text-bg-light"><?= !empty($spot["wifi"]) ? "WLAN" : "Kein WLAN" ?></span>
                                        <span class="badge text-bg-light"><?= !empty($spot["power_outlets"]) ? "Steckdosen" : "Keine Steckdosen" ?></span>
                                        <span class="badge text-bg-light"><?= !empty($spot["group_friendly"]) ? "Gruppen" : "Solo" ?></span>
                                        <span class="badge text-bg-light"><?= e(quiet_label($spot["quiet_level"] ?? "")) ?></span>
                                    </div>

                                    <div class="small text-muted">Erstellt: <?= e($spot["created_at"] ?? "") ?></div>
                                </div>

                                <div class="card-footer bg-white d-flex gap-2 border-0 pt-0">
                                    <a class="btn btn-sm btn-outline-success" href="spot.php?id=<?= (int)$spot["id"] ?>">Ansehen</a>
                                    <?php if (can_manage_spot($spot["created_by"] ?? 0)): ?>
                                        <a class="btn btn-sm btn-outline-warning" href="spot_edit.php?id=<?= (int)$spot["id"] ?>">Bearbeiten</a>
                                        <a class="btn btn-sm btn-outline-danger" href="spot_delete.php?id=<?= (int)$spot["id"] ?>">Loeschen</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-4 shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                <h2 class="h4 mb-0"><?= $isAdminView ? "Alle Einreichungen" : "Meine Einreichungen" ?></h2>
                <a href="ort_anmelden.php" class="btn btn-outline-success btn-sm">Neue Anfrage</a>
            </div>

            <?php if (!$requests): ?>
                <div class="alert alert-info mb-0">Noch keine Einreichungen vorhanden.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Ort</th>
                                <th>Typ</th>
                                <th>Adresse</th>
                                <th>Status</th>
                                <th>Erstellt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($request["name"]) ?></td>
                                    <td><?= e(request_place_type_options()[$request["type"]] ?? $request["type"]) ?></td>
                                    <td><?= e($request["zip"] . " " . $request["city"] . ", " . $request["address"]) ?></td>
                                    <td><?= badge_status((string)($request["status"] ?? "pending")) ?></td>
                                    <td class="text-muted small"><?= e($request["created_at"] ?? "") ?></td>
                                </tr>
                                <?php if (!empty($request["hours"]) || !empty($request["photo_url"])): ?>
                                    <tr>
                                        <td colspan="5" class="bg-light">
                                            <?php if (!empty($request["hours"])): ?>
                                                <div><strong>Oeffnungszeiten:</strong> <?= nl2br(e($request["hours"])) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($request["photo_url"])): ?>
                                                <div class="mt-2">
                                                    <img src="<?= e($request["photo_url"]) ?>" alt="<?= e($request["name"]) ?>" style="max-width:260px;border-radius:12px;">
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

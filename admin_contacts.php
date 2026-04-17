<?php

require_once "helpers.php";
require "db.php";
require_role(['admin']);

$status = $_GET['status'] ?? 'all';
$allowedStatus = ['all', 'new', 'read', 'archived'];
if (!in_array($status, $allowedStatus, true)) {
    $status = 'all';
}

$viewId = (int)($_GET['view'] ?? 0);

if (is_post_request()) {
    verify_csrf();

    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($id > 0) {
        $targetStatus = null;
        if ($action === 'mark_read') {
            $targetStatus = 'read';
        } elseif ($action === 'archive') {
            $targetStatus = 'archived';
        } elseif ($action === 'mark_new') {
            $targetStatus = 'new';
        }

        if ($targetStatus !== null) {
            $stmt = $conn->prepare("UPDATE contact_messages SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $targetStatus, $id);
            $stmt->execute();
            $stmt->close();
        }
    }

    redirect(build_url("admin_contacts.php", ["status" => $status, "view" => $viewId ?: null]));
}

if ($viewId > 0) {
    $stmt = $conn->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ? AND status = 'new'");
    $stmt->bind_param("i", $viewId);
    $stmt->execute();
    $stmt->close();
}

$sql = "SELECT id, name, email, subject, status, created_at FROM contact_messages";
if ($status !== 'all') {
    $sql .= " WHERE status = ?";
}
$sql .= " ORDER BY created_at DESC LIMIT 300";

$stmt = $conn->prepare($sql);
if ($status !== 'all') {
    $stmt->bind_param("s", $status);
}
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$currentMessage = null;
if ($viewId > 0) {
    $stmt = $conn->prepare("SELECT * FROM contact_messages WHERE id = ?");
    $stmt->bind_param("i", $viewId);
    $stmt->execute();
    $currentMessage = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$counts = ['new' => 0, 'read' => 0, 'archived' => 0];
$result = $conn->query("SELECT status, COUNT(*) AS total FROM contact_messages GROUP BY status");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $counts[$row['status']] = (int)$row['total'];
    }
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>StudySpot | Admin Kontakt</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .card-soft{background:#fff;border-radius:18px;box-shadow:0 10px 20px rgba(0,0,0,.06);border:1px solid rgba(0,0,0,.06);}
        .pill a{border-radius:999px !important;}
        .msg-item{border-radius:14px;}
        .msg-item:hover{background:rgba(25,135,84,.06);}
        .muted{color:#6c757d;}
        .mono{font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;}
        .prewrap{white-space:pre-wrap;}
    </style>
</head>
<body class="Homepage-body">

<?php include "navbar.php"; ?>

<main class="mt-5 pt-4">
    <div class="container py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h2 class="fw-bold mb-0 text-success">Kontakt-Inbox</h2>
                <div class="muted">Nachrichten aus dem Kontaktformular verwalten</div>
            </div>
            <a href="admin_requests.php" class="btn btn-outline-success" style="border-radius:14px;">Ort-Anfragen</a>
        </div>

        <div class="row g-3">
            <div class="col-12 col-lg-5">
                <div class="card-soft p-3">
                    <div class="d-flex flex-wrap gap-2 pill mb-3">
                        <a class="btn btn-sm <?= $status === 'all' ? 'btn-success' : 'btn-outline-success' ?>" href="admin_contacts.php?status=all">Alle</a>
                        <a class="btn btn-sm <?= $status === 'new' ? 'btn-success' : 'btn-outline-success' ?>" href="admin_contacts.php?status=new">
                            Neu <span class="badge bg-danger ms-1"><?= (int)$counts['new'] ?></span>
                        </a>
                        <a class="btn btn-sm <?= $status === 'read' ? 'btn-success' : 'btn-outline-success' ?>" href="admin_contacts.php?status=read">
                            Gelesen <span class="badge bg-secondary ms-1"><?= (int)$counts['read'] ?></span>
                        </a>
                        <a class="btn btn-sm <?= $status === 'archived' ? 'btn-success' : 'btn-outline-success' ?>" href="admin_contacts.php?status=archived">
                            Archiv <span class="badge bg-dark ms-1"><?= (int)$counts['archived'] ?></span>
                        </a>
                    </div>

                    <?php if (!$messages): ?>
                        <div class="alert alert-info mb-0">Keine Nachrichten gefunden.</div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($messages as $message): ?>
                                <?php $badgeClass = $message['status'] === 'new' ? 'bg-danger' : ($message['status'] === 'read' ? 'bg-secondary' : 'bg-dark'); ?>
                                <a class="list-group-item list-group-item-action msg-item" href="<?= e(build_url("admin_contacts.php", ["status" => $status, "view" => (int)$message['id']])) ?>">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div style="min-width:0;">
                                            <div class="fw-semibold text-truncate"><?= e($message['subject']) ?></div>
                                            <div class="small muted text-truncate"><?= e($message['name']) ?> · <?= e($message['email']) ?></div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge <?= $badgeClass ?>"><?= e($message['status']) ?></span>
                                            <div class="small muted"><?= e($message['created_at']) ?></div>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-12 col-lg-7">
                <div class="card-soft p-4">
                    <?php if (!$currentMessage): ?>
                        <h4 class="fw-bold mb-2">Nachricht anzeigen</h4>
                        <p class="muted mb-0">Waehle links eine Nachricht aus.</p>
                    <?php else: ?>
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                            <div>
                                <h4 class="fw-bold mb-1"><?= e($currentMessage['subject']) ?></h4>
                                <div class="small muted">
                                    Von: <b><?= e($currentMessage['name']) ?></b> ·
                                    <span class="mono"><?= e($currentMessage['email']) ?></span> ·
                                    <?= e($currentMessage['created_at']) ?>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <form method="post" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$currentMessage['id'] ?>">
                                    <button class="btn btn-outline-success btn-sm" name="action" value="mark_read" style="border-radius:12px;">Als gelesen</button>
                                </form>
                                <form method="post" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$currentMessage['id'] ?>">
                                    <button class="btn btn-outline-dark btn-sm" name="action" value="archive" style="border-radius:12px;">Archivieren</button>
                                </form>
                                <form method="post" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$currentMessage['id'] ?>">
                                    <button class="btn btn-outline-danger btn-sm" name="action" value="mark_new" style="border-radius:12px;">Als neu</button>
                                </form>
                            </div>
                        </div>

                        <hr>

                        <div class="prewrap"><?= e($currentMessage['message']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// ============================================================================
// Student Hub Portal - Update Event Record
// Practical 12, 14, 15
// ============================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/upload_poster.php';

init_session();
require_admin();

$db = get_db_connection();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {
    if (is_api_request()) {
        json_response(400, false, "Invalid event ID.");
    }
    header("Location: list.php");
    exit;
}

$stmtF = $db->prepare("SELECT * FROM events WHERE id = ? LIMIT 1");
$stmtF->bind_param("i", $id);
$stmtF->execute();
$event = $stmtF->get_result()->fetch_assoc();
$stmtF->close();

if (!$event) {
    if (is_api_request()) {
        json_response(404, false, "Event not found.");
    }
    $_SESSION['flash_error'] = "Event not found.";
    header("Location: list.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $category    = trim($_POST['category'] ?? 'Technical');
    $eventDate   = trim($_POST['event_date'] ?? '');
    $time        = trim($_POST['time'] ?? '10:00 AM');
    $venue       = trim($_POST['venue'] ?? '');
    $speaker     = trim($_POST['speaker'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $seats       = max(1, (int)($_POST['seats'] ?? 100));
    $status      = in_array($_POST['status'] ?? '', ['open', 'closed', 'upcoming', 'cancelled']) ? $_POST['status'] : 'upcoming';

    if (empty($title) || strlen($title) < 3) {
        $errors['title'] = "Event title is required (minimum 3 characters).";
    }

    if (empty($eventDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)) {
        $errors['event_date'] = "Valid event date (YYYY-MM-DD) is required.";
    }

    if (empty($venue)) {
        $errors['venue'] = "Venue is required.";
    }

    // Poster replacement
    $newPoster = $event['poster'];
    if (isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadResult = handle_poster_upload($_FILES['poster']);
        if (!$uploadResult['success']) {
            $errors['poster'] = $uploadResult['error'];
        } else {
            // Remove old poster if exists
            if (!empty($event['poster']) && file_exists(__DIR__ . '/../../assets/uploads/event_posters/' . $event['poster'])) {
                @unlink(__DIR__ . '/../../assets/uploads/event_posters/' . $event['poster']);
            }
            $newPoster = $uploadResult['filename'];
        }
    }

    if (empty($errors)) {
        try {
            $stmtUp = $db->prepare("
                UPDATE events
                SET title = ?, category = ?, event_date = ?, time = ?, venue = ?, speaker = ?, description = ?, seats = ?, poster = ?, status = ?
                WHERE id = ?
            ");
            $stmtUp->bind_param("sssssssisss", $title, $category, $eventDate, $time, $venue, $speaker, $description, $seats, $newPoster, $status, $id);
            $stmtUp->execute();
            $stmtUp->close();

            log_audit_event("UPDATE_EVENT", "Admin updated event ID $id ($title)");
            $_SESSION['flash_success'] = "Event '$title' updated successfully!";

            if (is_api_request()) {
                json_response(200, true, "Event updated successfully");
            }

            header("Location: list.php");
            exit;

        } catch (Exception $e) {
            error_log("Update Event Error: " . $e->getMessage());
            $errors['general'] = "Database error while updating event.";
        }
    }

    if (is_api_request() && !empty($errors)) {
        json_response(422, false, "Validation errors", ['errors' => $errors]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event - <?= htmlspecialchars($event['title']) ?></title>
    <link rel="stylesheet" href="../../Style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        .form-card { max-width: 760px; margin: 40px auto; padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); font-family: 'Segoe UI', Tahoma, sans-serif; }
        .form-title { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; color: #0f172a; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group.full-width { grid-column: span 2; }
        .form-group label { font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 6px; }
        .form-group input, .form-group select, .form-group textarea { padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; }
        .error-hint { color: #dc2626; font-size: 0.8rem; margin-top: 4px; font-weight: 600; }
        .btn-row { display: flex; gap: 12px; margin-top: 25px; }
        .btn { padding: 10px 20px; border-radius: 6px; font-weight: 600; text-decoration: none; border: none; cursor: pointer; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-secondary { background: #f1f5f9; color: #334155; }
        .current-poster-preview { display: flex; align-items: center; gap: 12px; margin-top: 8px; }
        .current-poster-preview img { width: 50px; height: 50px; object-fit: cover; border-radius: 6px; }
    </style>
</head>
<body>
    <main class="form-card">
        <div class="form-title">
            <i class="fa-solid fa-pen-to-square" style="font-size: 1.5rem; color: #0284c7;"></i>
            <h2>Edit Event: <?= htmlspecialchars($event['title']) ?></h2>
        </div>

        <?php if (!empty($errors['general'])): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 15px;">
                <?= htmlspecialchars($errors['general']) ?>
            </div>
        <?php endif; ?>

        <form action="update.php" method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$event['id'] ?>">

            <div class="form-grid">
                <div class="form-group full-width">
                    <label for="title">Event Title *</label>
                    <input type="text" id="title" name="title" required value="<?= htmlspecialchars($_POST['title'] ?? $event['title']) ?>">
                    <?php if (!empty($errors['title'])): ?><span class="error-hint"><?= $errors['title'] ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="category">Category *</label>
                    <select id="category" name="category" required>
                        <?php foreach (['Technical', 'Workshop', 'Seminar', 'Cultural', 'Sports', 'Career'] as $cat): ?>
                            <option value="<?= $cat ?>" <?= (($event['category'] ?? '') === $cat) ? 'selected' : '' ?>><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status">Event Status</label>
                    <select id="status" name="status">
                        <?php foreach (['upcoming' => 'Upcoming', 'open' => 'Registrations Open', 'closed' => 'Closed', 'cancelled' => 'Cancelled'] as $val => $lbl): ?>
                            <option value="<?= $val ?>" <?= (($event['status'] ?? '') === $val) ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="event_date">Event Date (YYYY-MM-DD) *</label>
                    <input type="date" id="event_date" name="event_date" required value="<?= htmlspecialchars($_POST['event_date'] ?? $event['event_date']) ?>">
                </div>

                <div class="form-group">
                    <label for="time">Time Slot</label>
                    <input type="text" id="time" name="time" value="<?= htmlspecialchars($_POST['time'] ?? $event['time']) ?>">
                </div>

                <div class="form-group">
                    <label for="venue">Venue *</label>
                    <input type="text" id="venue" name="venue" required value="<?= htmlspecialchars($_POST['venue'] ?? $event['venue']) ?>">
                </div>

                <div class="form-group">
                    <label for="speaker">Speaker</label>
                    <input type="text" id="speaker" name="speaker" value="<?= htmlspecialchars($_POST['speaker'] ?? $event['speaker']) ?>">
                </div>

                <div class="form-group">
                    <label for="seats">Seats</label>
                    <input type="number" id="seats" name="seats" min="1" max="1000" value="<?= htmlspecialchars($_POST['seats'] ?? $event['seats']) ?>">
                </div>

                <div class="form-group">
                    <label for="poster">Change Poster Image (Max 2 MB, JPG/PNG/WEBP)</label>
                    <input type="file" id="poster" name="poster" accept="image/jpeg,image/png,image/webp">
                    <?php if (!empty($event['poster']) && file_exists(__DIR__ . '/../../assets/uploads/event_posters/' . $event['poster'])): ?>
                        <div class="current-poster-preview">
                            <img src="../../assets/uploads/event_posters/<?= htmlspecialchars($event['poster']) ?>" alt="Current Poster">
                            <small style="color: #64748b;">Current poster: <?= htmlspecialchars($event['poster']) ?></small>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($errors['poster'])): ?><span class="error-hint"><?= $errors['poster'] ?></span><?php endif; ?>
                </div>

                <div class="form-group full-width">
                    <label for="description">Event Description</label>
                    <textarea id="description" name="description" rows="3"><?= htmlspecialchars($_POST['description'] ?? $event['description']) ?></textarea>
                </div>
            </div>

            <div class="btn-row">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Update Event</button>
                <a href="list.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </main>
</body>
</html>

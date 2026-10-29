<?php
// ============================================================================
// Student Hub Portal - Create Event with Poster Upload & File Validation
// Practical 12, 14, 15
// ============================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/upload_poster.php';

init_session();
require_admin(); // Admin privileges required for event creation

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        $errors['general'] = "The form expired or could not be verified. Reload and try again.";
    }

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
        $errors['venue'] = "Venue / Location is required.";
    }

    // Handle Poster Upload with strict validation
    $posterFilename = null;
    if (isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadResult = handle_poster_upload($_FILES['poster']);
        if (!$uploadResult['success']) {
            $errors['poster'] = $uploadResult['error'];
        } else {
            $posterFilename = $uploadResult['filename'];
        }
    }

    if (empty($errors)) {
        try {
            $db = get_db_connection();
            $adminUser = current_user();
            $createdBy = $adminUser['id'] ?? 1;

            $stmt = $db->prepare("
                INSERT INTO events (title, category, event_date, time, venue, speaker, description, seats, poster, status, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("sssssssisss", $title, $category, $eventDate, $time, $venue, $speaker, $description, $seats, $posterFilename, $status, $createdBy);
            $stmt->execute();
            $newEventId = $stmt->insert_id;
            $stmt->close();

            log_audit_event("CREATE_EVENT", "Admin created event ID $newEventId: $title");
            if ($posterFilename) {
                log_audit_event("UPLOAD_POSTER", "Uploaded poster $posterFilename for event ID $newEventId");
            }

            $_SESSION['flash_success'] = "Event '$title' created successfully!";

            if (is_api_request()) {
                json_response(201, true, "Event created successfully", ['id' => $newEventId, 'title' => $title]);
            }

            header("Location: list.php");
            exit;

        } catch (Exception $e) {
            error_log("Create Event Error: " . $e->getMessage());
            $errors['general'] = "Database error creating event.";
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
    <title>Create Event - Student Hub Portal</title>
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
        .alert-error { background: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; }
        .btn-row { display: flex; gap: 12px; margin-top: 25px; }
        .btn { padding: 10px 20px; border-radius: 6px; font-weight: 600; text-decoration: none; border: none; cursor: pointer; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-secondary { background: #f1f5f9; color: #334155; }
        .upload-hint { font-size: 0.75rem; color: #64748b; margin-top: 4px; }
    </style>
</head>
<body>
    <main class="form-card">
        <div class="form-title">
            <i class="fa-solid fa-calendar-plus" style="font-size: 1.5rem; color: #2563eb;"></i>
            <h2>Add New Campus Event</h2>
        </div>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($errors['general']) ?></div>
        <?php endif; ?>

        <form action="create.php" method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="form-grid">
                <div class="form-group full-width">
                    <label for="title">Event Title *</label>
                    <input type="text" id="title" name="title" required placeholder="e.g. AI & Robotics Hackathon 2026" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                    <?php if (!empty($errors['title'])): ?><span class="error-hint"><?= $errors['title'] ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="category">Category *</label>
                    <select id="category" name="category" required>
                        <option value="Technical">Technical</option>
                        <option value="Workshop">Workshop</option>
                        <option value="Seminar">Seminar</option>
                        <option value="Cultural">Cultural</option>
                        <option value="Sports">Sports</option>
                        <option value="Career">Career</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status">Event Status</label>
                    <select id="status" name="status">
                        <option value="upcoming" selected>Upcoming</option>
                        <option value="open">Registrations Open</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="event_date">Date (YYYY-MM-DD) *</label>
                    <input type="date" id="event_date" name="event_date" required value="<?= htmlspecialchars($_POST['event_date'] ?? date('Y-m-d', strtotime('+7 days'))) ?>">
                    <?php if (!empty($errors['event_date'])): ?><span class="error-hint"><?= $errors['event_date'] ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="time">Time Slot</label>
                    <input type="text" id="time" name="time" placeholder="10:00 AM - 01:00 PM" value="<?= htmlspecialchars($_POST['time'] ?? '10:00 AM - 01:00 PM') ?>">
                </div>

                <div class="form-group">
                    <label for="venue">Venue / Location *</label>
                    <input type="text" id="venue" name="venue" required placeholder="e.g. Computer Lab 3" value="<?= htmlspecialchars($_POST['venue'] ?? '') ?>">
                    <?php if (!empty($errors['venue'])): ?><span class="error-hint"><?= $errors['venue'] ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="speaker">Speaker / Keynote Guest</label>
                    <input type="text" id="speaker" name="speaker" placeholder="e.g. Dr. Kavya Mehta" value="<?= htmlspecialchars($_POST['speaker'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="seats">Total Seat Capacity</label>
                    <input type="number" id="seats" name="seats" min="1" max="1000" value="<?= htmlspecialchars($_POST['seats'] ?? '100') ?>">
                </div>

                <div class="form-group">
                    <label for="poster">Event Poster Image (Max 2 MB, JPG/PNG/WEBP)</label>
                    <input type="file" id="poster" name="poster" accept="image/jpeg,image/png,image/webp">
                    <span class="upload-hint">Supported formats: JPEG, PNG, WEBP. Maximum file size: 2 MB.</span>
                    <?php if (!empty($errors['poster'])): ?><span class="error-hint"><?= $errors['poster'] ?></span><?php endif; ?>
                </div>

                <div class="form-group full-width">
                    <label for="description">Event Description</label>
                    <textarea id="description" name="description" rows="3" placeholder="Brief outline of event topics and objectives..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="btn-row">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Save Event</button>
                <a href="list.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </main>
</body>
</html>

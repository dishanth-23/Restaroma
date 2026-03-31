<?php
$pageClass = "admindash-page";

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
requireAdminLogin();
require __DIR__ . '/../includes/header.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();
}

if (isset($_GET['success'])) {
    if ($_GET['success'] === 'added') $success = "Promo added.";
    if ($_GET['success'] === 'updated') $success = "Promo updated.";
    if ($_GET['success'] === 'deleted') $success = "Promo deleted.";
}

$editPromo = null;

# DELETE PROMO
if (isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];

    $stmt = $pdo->prepare("SELECT images FROM promos WHERE id=:id");
    $stmt->execute([':id'=>$id]);
    $promo = $stmt->fetch();

    if ($promo && $promo['images']) {
        $images = json_decode($promo['images'], true);
        foreach ($images as $img) {
            $imagePath = __DIR__.'/../assets/images/'.basename($img);
            if (file_exists($imagePath)) unlink($imagePath);
        }
    }

    $stmt = $pdo->prepare("DELETE FROM promos WHERE id=:id");
    $stmt->execute([':id'=>$id]);

    header("Location: manage_promos.php?success=deleted");
    exit;
}

# EDIT FETCH
if (isset($_GET['edit'])) {

    $id = (int)$_GET['edit'];

    $stmt = $pdo->prepare("SELECT * FROM promos WHERE id=:id");
    $stmt->execute([':id'=>$id]);

    $editPromo = $stmt->fetch();
}

# CREATE / UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = (int)($_POST['id'] ?? 0);

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $discountPercent = (int)($_POST['discount_percent'] ?? 0);
    $category = trim($_POST['category'] ?? 'General');
    $promoCode = strtoupper(trim($_POST['promo_code'] ?? ''));
    if ($promoCode === '') $promoCode = null;

    $startDateOnly = $_POST['start_date'] ?? '';
    $endDateOnly = $_POST['end_date'] ?? '';
    $startTimeOnly = $_POST['start_time'] ?? '';
    $endTimeOnly = $_POST['end_time'] ?? '';

    $requestedScheduleType = trim($_POST['schedule_type'] ?? '');
    $selectedWeeklyDays = $_POST['weekly_days'] ?? [];

    // If admin selects multiple days via a date range (today -> tomorrow),
    // treat it as a weekly schedule on the specific weekdays in that range,
    // repeating daily time window on those weekdays (e.g. Mon/Tue 12:00-15:00).
    // Admin can also explicitly choose weekly and pick days (e.g. Fri/Sun only).
    $autoScheduleType = ($startDateOnly !== '' && $endDateOnly !== '' && $startDateOnly !== $endDateOnly) ? 'weekly' : 'range';
    $scheduleType = in_array($requestedScheduleType, ['range', 'weekly'], true) ? $requestedScheduleType : $autoScheduleType;

    // Keep start_date/end_date filled for legacy parts of the app.
    $startDate = $startDateOnly.' '.$startTimeOnly.':00';
    $endDate = $endDateOnly.' '.$endTimeOnly.':00';

    $imageNames = [];

    # VALIDATION
    if ($title === '') $errors[] = 'Title required';

    if ($discountPercent < 1 || $discountPercent > 100)
        $errors[] = 'Discount must be 1-100';

    if ($promoCode !== null) {
        if (!preg_match('/^[A-Z0-9]{2,30}$/', $promoCode)) {
            $errors[] = 'Promo code must be 2-30 characters (A-Z, 0-9).';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM promos WHERE UPPER(promo_code)=:c AND id!=:id LIMIT 1");
            $stmt->execute([':c' => $promoCode, ':id' => $id]);
            if ($stmt->fetch()) $errors[] = 'Promo code already exists.';
        }
    }

    $startTimestamp = strtotime($startDate);
    $endTimestamp = strtotime($endDate);

    if (!$startTimestamp || !$endTimestamp)
        $errors[] = 'Invalid dates';

    elseif ($startTimestamp > $endTimestamp)
        $errors[] = 'End date must be after start date';

    if ($startTimeOnly !== '' && $endTimeOnly !== '') {
        if (strtotime("1970-01-01 $startTimeOnly") >= strtotime("1970-01-01 $endTimeOnly")) {
            $errors[] = 'End time must be after start time';
        }
    }

    $weeklyDays = null;
    $validFrom = null;
    $validTo = null;
    $dailyStart = null;
    $dailyEnd = null;

    if ($scheduleType === 'weekly' && empty($errors)) {
        try {
            $from = new DateTimeImmutable($startDateOnly);
            $to = new DateTimeImmutable($endDateOnly);
            if ($from > $to) {
                $errors[] = 'End date must be after start date';
            } else {
                $dayNums = [];

                // If admin explicitly selected weekdays (e.g. Fri & Sun), use them.
                if (is_array($selectedWeeklyDays) && !empty($selectedWeeklyDays)) {
                    foreach ($selectedWeeklyDays as $d) {
                        $d = (string)$d;
                        if (ctype_digit($d)) {
                            $n = (int)$d;
                            if ($n >= 1 && $n <= 7) $dayNums[] = $n;
                        }
                    }
                    $dayNums = array_values(array_unique($dayNums));
                    sort($dayNums);
                }

                // Otherwise, derive weekdays from the selected date range.
                if (empty($dayNums)) {
                    for ($d = $from; $d <= $to; $d = $d->modify('+1 day')) {
                        // ISO-8601: 1 (Mon) .. 7 (Sun)
                        $dayNums[] = (int)$d->format('N');
                    }
                    $dayNums = array_values(array_unique($dayNums));
                    sort($dayNums);
                }

                if (empty($dayNums)) {
                    $errors[] = 'Please select at least one weekday';
                } else {
                    $weeklyDays = implode(',', $dayNums);
                }
                $validFrom = $startDateOnly;
                $validTo = $endDateOnly;
                $dailyStart = $startTimeOnly . ':00';
                $dailyEnd = $endTimeOnly . ':00';
            }
        } catch (Throwable $e) {
            $errors[] = 'Invalid dates';
        }
    }

    # IMAGE UPLOAD
    if (!empty($_FILES['images']['name'][0])) {

        $uploadDir = __DIR__.'/../assets/images/';

        foreach ($_FILES['images']['name'] as $key=>$name) {

            if (!$name) continue;

            $ext = strtolower(pathinfo($name,PATHINFO_EXTENSION));

            $allowedExt = ['jpg','jpeg','png','gif'];
            $allowedMime = ['image/jpeg','image/png','image/gif'];

            $mime = mime_content_type($_FILES['images']['tmp_name'][$key]);

            if (!in_array($ext,$allowedExt) || !in_array($mime,$allowedMime)) {

                $errors[]='Invalid image file';
                continue;
            }

            if ($_FILES['images']['size'][$key] > 2*1024*1024) {

                $errors[]='Image must be under 2MB';
                continue;
            }

            $filename = uniqid().'_'.preg_replace("/[^a-zA-Z0-9_\-.]/","",$name);

            $target = $uploadDir.$filename;

            if (move_uploaded_file($_FILES['images']['tmp_name'][$key],$target)) {

                $imageNames[] = $filename;
            }
        }
    }

    if (empty($errors)) {

        # UPDATE
        if ($id > 0) {

            $imagesJson = $editPromo['images'];

            # only replace if new images uploaded
            if (!empty($imageNames)) {

                if ($editPromo['images']) {

                    $oldImages = json_decode($editPromo['images'],true);

                    foreach ($oldImages as $img) {

                        $imagePath = __DIR__.'/../assets/images/'.basename($img);

                        if (file_exists($imagePath)) unlink($imagePath);
                    }
                }

                $imagesJson = json_encode($imageNames);
            }

            $stmt = $pdo->prepare("UPDATE promos SET
                title=:t,
                description=:d,
                discount_percent=:dp,
                category=:c,
                images=:i,
                start_date=:sd,
                end_date=:ed,
                promo_code=:pc,
                schedule_type=:st,
                valid_from=:vf,
                valid_to=:vt,
                weekly_days=:wd,
                daily_start_time=:dst,
                daily_end_time=:det
                WHERE id=:id");

            $stmt->execute([
                ':t'=>$title,
                ':d'=>$description,
                ':dp'=>$discountPercent,
                ':c'=>$category,
                ':i'=>$imagesJson,
                ':sd'=>$startDate,
                ':ed'=>$endDate,
                ':pc'=>$promoCode,
                ':st'=>$scheduleType,
                ':vf'=>$validFrom,
                ':vt'=>$validTo,
                ':wd'=>$weeklyDays,
                ':dst'=>$dailyStart,
                ':det'=>$dailyEnd,
                ':id'=>$id
            ]);

            header("Location: manage_promos.php?success=updated");
            exit;
        }

        # INSERT
        else {

            $imagesJson = json_encode($imageNames);

            $stmt = $pdo->prepare("INSERT INTO promos
            (title,description,discount_percent,category,images,start_date,end_date,promo_code,schedule_type,valid_from,valid_to,weekly_days,daily_start_time,daily_end_time,created_at)
            VALUES (:t,:d,:dp,:c,:i,:sd,:ed,:pc,:st,:vf,:vt,:wd,:dst,:det,NOW())");

            $stmt->execute([
                ':t'=>$title,
                ':d'=>$description,
                ':dp'=>$discountPercent,
                ':c'=>$category,
                ':i'=>$imagesJson,
                ':sd'=>$startDate,
                ':ed'=>$endDate,
                ':pc'=>$promoCode,
                ':st'=>$scheduleType,
                ':vf'=>$validFrom,
                ':vt'=>$validTo,
                ':wd'=>$weeklyDays,
                ':dst'=>$dailyStart,
                ':det'=>$dailyEnd
            ]);

            header("Location: manage_promos.php?success=added");
            exit;
        }
    }
}

# FETCH PROMOS
$stmt = $pdo->query("SELECT * FROM promos ORDER BY created_at DESC");
$promos = $stmt->fetchAll();
?>

<h1>Manage Promotions</h1>
<div class="alert">
    <?php if($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?>
    </div>
    <?php endif; ?>

    <?php if($errors): ?>
    <div class="alert alert-danger"><?php foreach($errors as $e) echo "<p>".htmlspecialchars($e)."</p>"; ?>
    </div>
    <?php endif; ?>
</div>
<div class="manage-menu-wrapper">
    <h2><?php echo $editPromo ? 'Edit Promo' : 'Add New Promo'; ?></h2>
    <form action="" method="post" enctype="multipart/form-data" class="form-card">
        <?php csrf_input(); ?>
        <input type="hidden" name="id" value="<?php echo $editPromo ? (int)$editPromo['id'] : 0; ?>">

        <div class="form-group">
            <label for="title">Title</label>
            <input required type="text" id="title" name="title"
                value="<?php echo $editPromo ? htmlspecialchars($editPromo['title']) : ''; ?>">
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description"
                name="description"><?php echo $editPromo ? htmlspecialchars($editPromo['description']) : ''; ?></textarea>
        </div>

        <div class="form-group">
            <label for="discount_percent">Discount Percent</label>
            <input required type="number" min="1" max="100" id="discount_percent" name="discount_percent"
                value="<?php echo $editPromo ? (int)$editPromo['discount_percent'] : ''; ?>">
        </div>

        <div class="form-group">
            <label for="promo_code">Promo Code (optional)</label>
            <input type="text" id="promo_code" name="promo_code" maxlength="30" placeholder="e.g. NEW10"
                value="<?php echo $editPromo ? htmlspecialchars((string)($editPromo['promo_code'] ?? '')) : ''; ?>">
        </div>

        <div class="form-group">
            <label for="category">Category</label>
            <select name="category" id="category" required>
                <?php
                $cats = ['General','Lunch','Dinner','Seasonal','Limited Time'];
                foreach ($cats as $cat):
                    $selected = $editPromo && $editPromo['category'] === $cat ? 'selected' : '';
                ?>
                <option value="<?php echo $cat; ?>" <?php echo $selected; ?>><?php echo $cat; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="schedule_type">Promo Schedule</label>
            <?php $st = ($editPromo['schedule_type'] ?? '') ?: 'range'; ?>
            <select name="schedule_type" id="schedule_type" required>
                <option value="range" <?php echo $st === 'range' ? 'selected' : ''; ?>>Date &amp; time range (one
                    continuous period)</option>
                <option value="weekly" <?php echo $st === 'weekly' ? 'selected' : ''; ?>>Weekly days (repeat time on
                    selected weekdays)</option>
            </select>
        </div>

        <div class="form-group">
            <label for="start_date">Start Date</label>
            <input required type="date" id="start_date" name="start_date"
                value="<?php echo $editPromo ? date('Y-m-d', strtotime($editPromo['start_date'])) : ''; ?>">
        </div>

        <div class="form-group">
            <label for="start_time">Start Time</label>
            <input required type="time" id="start_time" name="start_time"
                value="<?php echo $editPromo ? date('H:i', strtotime($editPromo['start_date'])) : ''; ?>">
        </div>

        <div class="form-group">
            <label for="end_date">End Date</label>
            <input required type="date" id="end_date" name="end_date"
                value="<?php echo $editPromo ? date('Y-m-d', strtotime($editPromo['end_date'])) : ''; ?>">
        </div>

        <div class="form-group">
            <label for="end_time">End Time</label>
            <input required type="time" id="end_time" name="end_time"
                value="<?php echo $editPromo ? date('H:i', strtotime($editPromo['end_date'])) : ''; ?>">
        </div>

        <div class="form-group">
            <label>For Weekly Days Schedule</label>
            <?php
                $selectedDays = [];
                if ($editPromo && ($editPromo['schedule_type'] ?? '') === 'weekly' && !empty($editPromo['weekly_days'])) {
                    $selectedDays = array_filter(array_map('trim', explode(',', (string)$editPromo['weekly_days'])));
                }
                $weekLabels = [1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',7=>'Sun'];
            ?>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <?php foreach ($weekLabels as $num => $lbl): ?>
                <label style="display:flex;align-items:center;gap:3px;">
                    <input type="checkbox" name="weekly_days[]" value="<?php echo $num; ?>"
                        <?php echo in_array((string)$num, $selectedDays, true) ? 'checked' : ''; ?>>
                    <?php echo $lbl; ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-group">
            <label for="images">Images (Multiple allowed)</label>
            <input type="file" id="images" name="images[]" accept="image/*" multiple>
            <?php if ($editPromo && $editPromo['images']): ?>
            <p>Current Images:</p>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <?php
            $currentImages = json_decode($editPromo['images'], true);
            foreach ($currentImages as $img):
            ?>
                <img src="../assets/images/<?php echo htmlspecialchars($img); ?>" alt="Current Image"
                    style="width:80px;height:80px;object-fit:cover;border-radius:6px;">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="admin-menu-form">
            <button type="submit" class="btn"><?php echo $editPromo ? 'UPDATE' : 'ADD'; ?></button>
        </div>
    </form>
</div>

<h2>Existing Promos</h2>
<div class="admin-menu-grid">
    <?php foreach ($promos as $promo): ?>
    <div class="admin-menu-card">
        <?php
$now = time();
$start = strtotime($promo['start_date']);
$end = strtotime($promo['end_date']);

$status = "Upcoming";
if (($promo['schedule_type'] ?? 'range') === 'weekly' && !empty($promo['valid_from']) && !empty($promo['valid_to'])) {
    $today = new DateTimeImmutable('now');
    $todayDate = $today->format('Y-m-d');
    $dow = (int)$today->format('N'); // 1..7
    $days = array_filter(array_map('trim', explode(',', (string)($promo['weekly_days'] ?? ''))));
    $inDateRange = ($todayDate >= $promo['valid_from'] && $todayDate <= $promo['valid_to']);

    if (!$inDateRange) {
        $status = ($todayDate < $promo['valid_from']) ? "Upcoming" : "Expired";
    } elseif (!in_array((string)$dow, $days, true)) {
        $status = "Upcoming";
    } else {
        $ds = $promo['daily_start_time'] ?: date('H:i:s', $start);
        $de = $promo['daily_end_time'] ?: date('H:i:s', $end);
        $startToday = strtotime($todayDate . ' ' . $ds);
        $endToday = strtotime($todayDate . ' ' . $de);
        if ($now < $startToday) $status = "Upcoming";
        elseif ($now > $endToday) $status = "Upcoming";
        else $status = "Active";
    }
} else {
    if ($now < $start) {
        $status = "Upcoming";
    } elseif ($now > $end) {
        $status = "Expired";
    } else {
        $status = "Active";
    }
}
?>

        <?php
        $images = json_decode($promo['images'], true);
        if (!empty($images)):
        ?>
        <img src="../assets/images/<?php echo htmlspecialchars($images[0]); ?>"
            alt="<?php echo htmlspecialchars($promo['title']); ?>">
        <?php else: ?>
        <div
            style="width:100%;height:150px;background:#333;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#ffe3be;">
            No Image
        </div>
        <?php endif; ?>
        <h3><?php echo htmlspecialchars($promo['title']); ?></h3>
        <p><?php echo htmlspecialchars($promo['description']); ?></p>
        <p>Discount: <?php echo (int)$promo['discount_percent']; ?>%</p>
        <p>Category: <?php echo htmlspecialchars($promo['category']); ?></p>
        <?php if (($promo['schedule_type'] ?? 'range') === 'weekly' && !empty($promo['valid_from']) && !empty($promo['valid_to'])): ?>
        <?php
            $dayMap = [1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',7=>'Sun'];
            $days = array_filter(array_map('trim', explode(',', (string)($promo['weekly_days'] ?? ''))));
            $labels = [];
            foreach ($days as $d) {
                $n = (int)$d;
                if (isset($dayMap[$n])) $labels[] = $dayMap[$n];
            }
            $ds = $promo['daily_start_time'] ?: date('H:i:s', $start);
            $de = $promo['daily_end_time'] ?: date('H:i:s', $end);
        ?>
        <p>
            Valid:
            <?php echo htmlspecialchars(date('g:ia', strtotime('1970-01-01 '.$ds))); ?> -
            <?php echo htmlspecialchars(date('g:ia', strtotime('1970-01-01 '.$de))); ?>
            every <?php echo htmlspecialchars(implode(', ', $labels)); ?>
            (<?php echo htmlspecialchars($promo['valid_from']); ?> to
            <?php echo htmlspecialchars($promo['valid_to']); ?>)
        </p>
        <?php else: ?>
        <p>Valid: <?php echo htmlspecialchars($promo['start_date']); ?> -
            <?php echo htmlspecialchars($promo['end_date']); ?></p>
        <?php endif; ?>

        <form method="POST" action="" class="delete-form">
            <?php csrf_input(); ?>
            <a href="?edit=<?php echo (int)$promo['id']; ?>" class="edit-btn"><i class="fas fa-edit"></i> Edit</a>
            <input type="hidden" name="delete_id" value="<?php echo (int)$promo['id']; ?>">
            <button type="submit" class="delete-btn" onclick="return confirm('Delete this promo?');"><i
                    class="fas fa-trash"></i> Delete</button>
        </form>
    </div>
    <?php endforeach; ?>
</div>

<?php
require __DIR__ . '/../includes/footer.php';
?>
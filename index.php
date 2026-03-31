<?php
require __DIR__ . '/includes/header.php';
require __DIR__ . '/config/db.php';

$now = time();

/**
 * For weekly promos, compute next (or today's) occurrence window.
 * Returns array with keys: status, start_date, end_date.
 */
function resolvePromoWindow(array $promo, DateTimeImmutable $nowDt): ?array {
    $scheduleType = $promo['schedule_type'] ?? 'range';
    if ($scheduleType !== 'weekly' || empty($promo['valid_from']) || empty($promo['valid_to'])) {
        $start = strtotime($promo['start_date']);
        $end = strtotime($promo['end_date']);
        if (!$start || !$end) return null;
        if ($nowDt->getTimestamp() > $end) return null;
        $status = ($nowDt->getTimestamp() < $start) ? 'Upcoming' : 'Active';
        return [
            'status' => $status,
            'start_date' => date('Y-m-d H:i:s', $start),
            'end_date' => date('Y-m-d H:i:s', $end),
        ];
    }

    $days = array_filter(array_map('trim', explode(',', (string)($promo['weekly_days'] ?? ''))));
    $days = array_values(array_unique(array_filter($days, fn($d) => ctype_digit($d) && (int)$d >= 1 && (int)$d <= 7)));
    if (empty($days)) return null;

    $validFrom = new DateTimeImmutable($promo['valid_from']);
    $validTo = new DateTimeImmutable($promo['valid_to']);
    $timeStart = $promo['daily_start_time'] ?: date('H:i:s', strtotime($promo['start_date']));
    $timeEnd = $promo['daily_end_time'] ?: date('H:i:s', strtotime($promo['end_date']));

    // Find next matching date within [max(today, validFrom), validTo]
    $cursor = $nowDt->setTime(0, 0, 0);
    if ($cursor < $validFrom) $cursor = $validFrom;

    $bestStart = null;
    $bestEnd = null;
    $status = 'Upcoming';

    for ($d = $cursor; $d <= $validTo; $d = $d->modify('+1 day')) {
        $dow = (int)$d->format('N');
        if (!in_array((string)$dow, $days, true)) continue;

        $start = new DateTimeImmutable($d->format('Y-m-d') . ' ' . $timeStart);
        $end = new DateTimeImmutable($d->format('Y-m-d') . ' ' . $timeEnd);

        // If we're on the same date and within the window, treat as Active
        if ($start->format('Y-m-d') === $nowDt->format('Y-m-d')) {
            if ($nowDt >= $start && $nowDt <= $end) {
                $bestStart = $start;
                $bestEnd = $end;
                $status = 'Active';
                break;
            }
            if ($nowDt < $start) {
                $bestStart = $start;
                $bestEnd = $end;
                $status = 'Upcoming';
                break;
            }
            // If it's after today's window, keep searching for next day.
            continue;
        }

        // Future matching day
        $bestStart = $start;
        $bestEnd = $end;
        $status = 'Upcoming';
        break;
    }

    if (!$bestStart || !$bestEnd) return null;

    return [
        'status' => $status,
        'start_date' => $bestStart->format('Y-m-d H:i:s'),
        'end_date' => $bestEnd->format('Y-m-d H:i:s'),
    ];
}

// Fetch all promos
$stmt = $pdo->query("SELECT * FROM promos ORDER BY created_at DESC");
$promos = $stmt->fetchAll();

// Group promos by category and determine status
$promosByCategory = [];
foreach ($promos as $promo) {
    $resolved = resolvePromoWindow($promo, new DateTimeImmutable('now'));
    if (!$resolved) continue;

    $promo['status'] = $resolved['status'];
    // Override start/end for display + countdown (next/active occurrence for weekly promos)
    $promo['start_date'] = $resolved['start_date'];
    $promo['end_date'] = $resolved['end_date'];
    $cat = $promo['category'];
    if (!isset($promosByCategory[$cat])) $promosByCategory[$cat] = [];
    $promosByCategory[$cat][] = $promo;
}
?>

<div class="home-layout">

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <p class="tagline">WELCOME TO</p>
            <h1>Restaroma</h1>
            <p class="tagline">An Elevated Culinary Experience</p>
            <div class="divider"></div>
            <p class="hero-description">
                Discover refined flavors, handcrafted dishes, and an unforgettable fine-dining atmosphere.
            </p>
            <div class="hero-actions">
                <a href="/Restaurant-System/user/menu.php" class="btn-gold">Explore Menu</a>
                <a href="/Restaurant-System/user/login.php" class="btn-outline">Reserve a Table</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="feature">
            <h2>Easy Ordering</h2>
            <p>Select your favorite dishes and place orders with just a few clicks.</p>
        </div>
        <div class="feature">
            <h2>Table Reservations</h2>
            <p>Reserve your table online by selecting date, time, and number of guests.</p>
        </div>
        <div class="feature">
            <h2>Exclusive Promotions</h2>
            <p>Enjoy special discounts and offers available only to our customers.</p>
        </div>
    </section>
</div>

<!-- Promo Section -->
<?php if (empty($promosByCategory)): ?>
<section class="promo-section">
    <h2>Today's Offers</h2>
    <p class="promo-none">No active promotions at the moment. Please check back soon.</p>
</section>
<?php else: ?>
<?php foreach ($promosByCategory as $category => $catPromos): ?>

<?php
        $promoSlides = [];
        $sliderId = preg_replace('/[^a-zA-Z0-9\-]/', '', str_replace(' ', '-', $category));

        foreach ($catPromos as $promo) {
            $images = json_decode($promo['images'], true) ?: ['no-image.png'];
            $chunks = array_chunk($images, 3);

            foreach ($chunks as $chunk) {
                $promoSlides[] = [
                    'images' => $chunk,
                    'title' => $promo['title'],
                    'description' => $promo['description'],
                    'discount_percent' => $promo['discount_percent'],
                    'start_date' => $promo['start_date'],
                    'end_date' => $promo['end_date'],
                    'status' => $promo['status']
                ];
            }
        }
        ?>

<section class="promo-section">
    <h2><?php echo htmlspecialchars($category); ?> Offers</h2>

    <div class="promo-slider" id="promoSlider-<?php echo $sliderId; ?>">
        <?php foreach ($promoSlides as $index => $slide): ?>
        <div class="promo-slide <?php echo $index === 0 ? 'active' : ''; ?>">

            <div class="promo-images num-<?php echo count($slide['images']); ?>">
                <?php foreach ($slide['images'] as $img): ?>
                <img loading="lazy" src="/Restaurant-System/assets/images/<?php echo htmlspecialchars($img); ?>"
                    alt="<?php echo htmlspecialchars($slide['title']); ?>">
                <?php endforeach; ?>
            </div>

            <div class="promo-content">
                <h3><?php echo htmlspecialchars($slide['title']); ?></h3>
                <p><?php echo htmlspecialchars($slide['description']); ?></p>
                <p class="promo-discount"><?php echo (int)$slide['discount_percent']; ?>% OFF</p>
                <p class="promo-dates">
                    Valid <?php echo date('M j H:i', strtotime($slide['start_date'])); ?>
                    - <?php echo date('M j H:i', strtotime($slide['end_date'])); ?>
                </p>
                <p>Status: <strong><?php echo $slide['status']; ?></strong></p>
                <p class="promo-countdown" data-end="<?php echo htmlspecialchars($slide['end_date']); ?>"></p>
            </div>

        </div>
        <?php endforeach; ?>
    </div>

    <?php if (count($promoSlides) > 1): ?>
    <div class="promo-nav">
        <button type="button" class="promo-prev" aria-label="Previous offer">&lsaquo;</button>
        <button type="button" class="promo-next" aria-label="Next offer">&rsaquo;</button>
    </div>
    <?php endif; ?>
</section>
<?php endforeach; ?>
<?php endif; ?>


<?php
require __DIR__ . '/includes/footer.php';
?>
{}
<?php include_once('../components/header.php'); ?>
<?php
function groupMenuItems(array $items): array
{
    $grouped = [];

    foreach ($items as $item) {
        $grouped[$item['item_type']][] = $item;
    }

    return $grouped;
}

$groupedMainDishes = groupMenuItems($mainDishes);
$groupedSides = groupMenuItems($sides);
$groupedDrinks = groupMenuItems($drinks);
?>

<section id="hero" class="hero-shell">
    <video autoplay loop muted playsinline poster="../image/loginBackground.jpg" class="hero-video">
        <source src="../image/SteakOnGrillCloseup.mp4" type="video/mp4">
    </video>
    <div class="hero-overlay"></div>
    <div class="hero container">
        <div class="hero-grid">
            <div class="hero-copy">
                <p class="eyebrow">Kota Kinabalu Fine Dining Experience</p>
                <h1 class="hero-title">Boundless Dining &amp; Bar</h1>
                <p class="hero-description">
                    A polished restaurant experience with premium grills, crafted cocktails, elegant Indian specialties,
                    and warm service for celebrations, business dinners, and memorable nights out.
                </p>
                <div class="hero-actions">
                    <a href="#projects" class="cta">Explore Menu</a>
                    <a href="../CustomerReservation/reservePage.php" class="cta cta-secondary">Reserve A Table</a>
                </div>
            </div>
            <div class="hero-panel">
                <div class="hero-card">
                    <span class="hero-card-label">Opening Hours</span>
                    <strong>10:00 AM - 8:00 PM</strong>
                    <p>Indoor dining, curated drinks, and all-day hospitality.</p>
                </div>
                <div class="hero-card">
                    <span class="hero-card-label">Signature Range</span>
                    <strong>Grills, Indian Classics, South Indian Plates</strong>
                    <p>From premium steaks to biryani, tandoori, and plated desserts.</p>
                </div>
                <div class="hero-card hero-card-accent">
                    <span class="hero-card-label">Reservations</span>
                    <strong>Walk-ins Welcome</strong>
                    <p>Reserve in advance for birthdays, family dinners, and private celebrations.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="experience-strip">
    <div class="experience-grid">
        <div class="experience-item">
            <span>139+</span>
            <p>Curated dishes and beverages</p>
        </div>
        <div class="experience-item">
            <span>5-Star</span>
            <p>Premium pricing and elevated presentation</p>
        </div>
        <div class="experience-item">
            <span>3 Worlds</span>
            <p>Western classics, Indian signatures, and bar craft</p>
        </div>
    </div>
</section>

<section id="projects" class="menu-showcase">
    <div class="projects container">
        <div class="projects-header">
            <p class="eyebrow dark">Signature Selection</p>
            <h1 class="section-title">Our <span>Menu</span></h1>
            <p class="section-lead">
                Discover premium mains, refined sides, crafted drinks, and our expanded Indian and South Indian menu.
            </p>
        </div>

        <div class="menu-intro-grid">
            <article class="menu-intro-card">
                <h3>Main Dishes</h3>
                <p>Steaks, grills, burgers, pasta, seafood, curries, biryanis, and South Indian plates.</p>
            </article>
            <article class="menu-intro-card">
                <h3>Side Snacks</h3>
                <p>Bar bites, breads, salads, desserts, and premium small plates for sharing.</p>
            </article>
            <article class="menu-intro-card">
                <h3>Drinks</h3>
                <p>Classic cocktails, house pours, mocktails, juices, and premium spirits.</p>
            </article>
        </div>

        <div class="menu-columns">
            <div class="menu-panel">
                <div class="menu-panel-header">
                    <h2>Main Dishes</h2>
                    <span>Chef-led mains and signature entrees</span>
                </div>
                <?php foreach ($groupedMainDishes as $type => $items): ?>
                    <div class="menu-group">
                        <h3><?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?></h3>
                        <div class="menu-list">
                            <?php foreach ($items as $item): ?>
                                <article class="menu-item-card">
                                    <div>
                                        <h4><?php echo htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8'); ?></h4>
                                        <p><?php echo htmlspecialchars($item['item_description'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    </div>
                                    <strong>Rs <?php echo number_format((float) $item['item_price'], 0); ?></strong>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="menu-panel">
                <div class="menu-panel-header">
                    <h2>Side Snacks</h2>
                    <span>Starters, breads, desserts, and accompaniment plates</span>
                </div>
                <?php foreach ($groupedSides as $type => $items): ?>
                    <div class="menu-group">
                        <h3><?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?></h3>
                        <div class="menu-list">
                            <?php foreach ($items as $item): ?>
                                <article class="menu-item-card">
                                    <div>
                                        <h4><?php echo htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8'); ?></h4>
                                        <p><?php echo htmlspecialchars($item['item_description'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    </div>
                                    <strong>Rs <?php echo number_format((float) $item['item_price'], 0); ?></strong>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="menu-panel">
                <div class="menu-panel-header">
                    <h2>Drinks</h2>
                    <span>Craft cocktails, premium bottles, mocktails, and juices</span>
                </div>
                <?php foreach ($groupedDrinks as $type => $items): ?>
                    <div class="menu-group">
                        <h3><?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?></h3>
                        <div class="menu-list">
                            <?php foreach ($items as $item): ?>
                                <article class="menu-item-card">
                                    <div>
                                        <h4><?php echo htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8'); ?></h4>
                                        <p><?php echo htmlspecialchars($item['item_description'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    </div>
                                    <strong>Rs <?php echo number_format((float) $item['item_price'], 0); ?></strong>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<section class="hospitality-band">
    <div class="hospitality-grid">
        <div class="hospitality-copy">
            <p class="eyebrow dark">Why Guests Return</p>
            <h2>Built for celebrations, family dining, and polished evenings out.</h2>
            <p>
                Boundless blends a classic dining-room mood with a broader premium menu than before, so guests can move
                from cocktails and grills to biryani, tandoori, and South Indian comfort plates in one setting.
            </p>
        </div>
        <div class="hospitality-points">
            <article>
                <h3>Premium Menu Breadth</h3>
                <p>Western signatures, Indian specialties, desserts, and drinks in one curated flow.</p>
            </article>
            <article>
                <h3>Reservation Friendly</h3>
                <p>Designed for special occasions, family tables, and advance planning.</p>
            </article>
            <article>
                <h3>Comfort + Atmosphere</h3>
                <p>An indoor dining and bar setup that works for both casual and formal visits.</p>
            </article>
        </div>
    </div>
</section>

<section id="about" class="about-shell">
    <div class="about container">
        <div class="col-right">
            <p class="eyebrow dark">About Boundless</p>
            <h1 class="section-title">Dining With <span>Character</span></h1>
            <h2>A restaurant experience built around hospitality, variety, and premium presentation.</h2>
            <p>
                Boundless began as a western dining concept known for steaks, grills, burgers, pasta, and a relaxed bar atmosphere.
                It has since evolved into a broader premium restaurant experience that now also includes Indian starters, tandoori dishes,
                biryanis, curries, naan, rice plates, South Indian classics, and plated desserts.
            </p>
            <p>
                The restaurant is designed for guests who want more than just a quick meal. Whether you are planning a family dinner,
                a celebration, or a comfortable evening out, Boundless aims to deliver attentive service and a menu with enough range
                to suit different tastes at the same table.
            </p>
            <p>
                The result is a more complete hospitality concept: polished enough for occasions, relaxed enough for repeat visits,
                and varied enough to feel memorable.
            </p>
        </div>
    </div>
</section>

<section id="contact" class="contact-shell">
    <div class="contact container">
        <div>
            <p class="eyebrow dark">Reach Out</p>
            <h1 class="section-title">Contact <span>Info</span></h1>
        </div>
        <div class="contact-items">
            <div class="contact-item contact-item-bg">
                <div class="contact-info">
                    <div class="icon"><img src="../image/icons8-phone-100.png" alt="Phone"/></div>
                    <h1>Phone</h1>
                    <h2>9876000000</h2>
                </div>
            </div>

            <div class="contact-item contact-item-bg">
                <div class="contact-info">
                    <div class="icon"><img src="../image/icons8-email-100.png" alt="Email"/></div>
                    <h1>Email</h1>
                    <h2>BoundlessDiningBar@gmail.com</h2>
                </div>
            </div>

            <div class="contact-item contact-item-bg">
                <div class="contact-info">
                    <div class="icon"><img src="../image/icons8-home-address-100.png" alt="Address"/></div>
                    <h1>Address</h1>
                    <h2>Brindhavan Nagar, Mullai Street, Koyembedu, Chennai 600092</h2>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include_once('../components/footer.php'); ?>

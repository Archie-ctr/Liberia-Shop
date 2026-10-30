<?php
require_once __DIR__ . '/db.php';

$allowed_slugs = ['faq', 'returns', 'contact', 'privacy', 'about', 'shipping'];
$script_directory = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$base_path = $script_directory === '.' ? '' : $script_directory;
$slug = strtolower(trim((string)($_GET['slug'] ?? '')));

if (!in_array($slug, $allowed_slugs, true)) {
    http_response_code(404);
    $page_title = 'Page not found';
    $page_meta_description = '';
    $page = [
        'title' => 'Page not found',
        'content' => 'The page you requested could not be found.'
    ];
} else {
    $statement = mysqli_prepare($con, "SELECT title, content, meta_title, meta_description FROM pages WHERE slug = ? AND status = 'published' LIMIT 1");
    if (!$statement) {
        throw new RuntimeException('Unable to prepare page lookup: ' . mysqli_error($con));
    }

    mysqli_stmt_bind_param($statement, 's', $slug);
    if (!mysqli_stmt_execute($statement)) {
        throw new RuntimeException('Unable to load page: ' . mysqli_stmt_error($statement));
    }

    $result = mysqli_stmt_get_result($statement);
    $page = mysqli_fetch_assoc($result);
    mysqli_stmt_close($statement);

    if (!$page) {
        http_response_code(404);
        $page = [
            'title' => 'Page not found',
            'content' => 'The page you requested could not be found.'
        ];
    }

    $page_title = $page['meta_title'] ?: $page['title'];
    $page_meta_description = (string)($page['meta_description'] ?? '');
}

$escape = static function ($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

$paragraphs = preg_split('/\R{2,}/', trim((string)$page['content'])) ?: [];

include __DIR__ . '/header.php';
?>

<main class="section site-information-page">
    <div class="container">
        <div class="row">
            <div class="col-md-9 col-md-offset-1">
                <nav aria-label="Breadcrumb" class="site-page-breadcrumb">
                    <a href="<?php echo htmlspecialchars($base_path, ENT_QUOTES); ?>/">Home</a>
                    <span aria-hidden="true"> / </span>
                    <span><?php echo $escape($page['title']); ?></span>
                </nav>
                <article class="site-page-card">
                    <h1><?php echo $escape($page['title']); ?></h1>
                    <?php if ($slug === 'contact' && isset($con)): ?>
                        <?php
                        $settings_result = mysqli_query($con, "SELECT shop_name, shop_email, shop_phone, shop_address, whatsapp_number, messenger_url FROM shop_settings WHERE id = 1 LIMIT 1");
                        if (!$settings_result) {
                            throw new RuntimeException('Unable to load store contact details: ' . mysqli_error($con));
                        }
                        $store_contact = mysqli_fetch_assoc($settings_result);
                        ?>
                        <?php if ($store_contact): ?>
                            <div class="site-contact-card">
                                <p><strong><?php echo $escape($store_contact['shop_name']); ?></strong></p>
                                <?php if (!empty($store_contact['shop_email'])): ?>
                                    <p>Email: <a href="mailto:<?php echo $escape($store_contact['shop_email']); ?>"><?php echo $escape($store_contact['shop_email']); ?></a></p>
                                <?php endif; ?>
                                <?php if (!empty($store_contact['shop_phone'])): ?>
                                    <?php $contact_phone_uri = preg_replace('/[^0-9+]/', '', (string)$store_contact['shop_phone']); ?>
                                    <p>Phone: <a href="tel:<?php echo $escape($contact_phone_uri); ?>"><?php echo $escape($store_contact['shop_phone']); ?></a></p>
                                <?php endif; ?>
                                <?php if (!empty($store_contact['shop_address'])): ?>
                                    <p>Location: <?php echo $escape($store_contact['shop_address']); ?></p>
                                <?php endif; ?>
                                <?php
                                $whatsapp_digits = preg_replace('/\D+/', '', (string)($store_contact['whatsapp_number'] ?? ''));
                                $whatsapp_link = $whatsapp_digits !== '' ? 'https://wa.me/' . $whatsapp_digits : 'https://wa.me/250794162823';
                                $messenger_link = trim((string)($store_contact['messenger_url'] ?? ''));
                                if ($messenger_link === '') {
                                    $messenger_link = 'https://m.me/archiekgonwoejr';
                                } elseif (!preg_match('~^https?://~i', $messenger_link)) {
                                    $messenger_link = 'https://m.me/' . ltrim($messenger_link, '@/');
                                }
                                ?>
                                <p><a href="<?php echo $escape($whatsapp_link); ?>" target="_blank" rel="noopener noreferrer">Contact us on WhatsApp</a></p>
                                <p><a href="<?php echo $escape($messenger_link); ?>" target="_blank" rel="noopener noreferrer">Message us on Messenger</a></p>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <div class="site-page-content">
                        <?php foreach ($paragraphs as $paragraph): ?>
                            <?php
                            $lines = preg_split('/\R/', trim($paragraph)) ?: [];
                            $first_line = array_shift($lines);
                            ?>
                            <p>
                                <?php if (strpos($first_line, 'Q: ') === 0): ?>
                                    <strong><?php echo $escape(substr($first_line, 3)); ?></strong><br>
                                <?php else: ?>
                                    <?php echo $escape($first_line); ?>
                                <?php endif; ?>
                                <?php if ($lines): ?>
                                    <br><?php echo nl2br($escape(implode("\n", $lines)), false); ?>
                                <?php endif; ?>
                            </p>
                        <?php endforeach; ?>
                    </div>
                    <p class="site-page-actions">
                        <a class="site-page-button" href="<?php echo htmlspecialchars($base_path, ENT_QUOTES); ?>/store.php">Shop products</a>
                        <a class="site-page-secondary" href="<?php echo htmlspecialchars($base_path, ENT_QUOTES); ?>/contact">Contact the store</a>
                    </p>
                </article>
            </div>
        </div>
    </div>
</main>

<style>
.site-information-page { min-height: 55vh; padding: 38px 0 56px; background: #f5f7fa; }
.site-page-breadcrumb { margin: 0 0 16px; color: #64748b; font-size: 14px; }
.site-page-breadcrumb a { color: #0056b3; }
.site-page-card { padding: 30px; border: 1px solid #e4e9f0; border-radius: 12px; background: #fff; box-shadow: 0 8px 26px rgba(20, 35, 55, .07); }
.site-page-card h1 { margin: 0 0 22px; color: #002855; font-size: 30px; }
.site-page-content p { margin: 0 0 17px; color: #364152; font-size: 16px; line-height: 1.75; }
.site-contact-card { margin-bottom: 24px; padding: 18px 20px; border-left: 4px solid #e74c3c; border-radius: 6px; background: #f5f7fa; }
.site-contact-card p { margin: 0 0 9px; line-height: 1.65; }
.site-contact-card p:last-child { margin-bottom: 0; }
.site-contact-card a { color: #0056b3; overflow-wrap: anywhere; }
.site-page-actions { display: flex; flex-wrap: wrap; gap: 12px; margin: 24px 0 0; }
.site-page-button, .site-page-secondary { display: inline-block; padding: 11px 17px; border-radius: 6px; text-decoration: none; font-weight: 600; }
.site-page-button { background: #0056b3; color: #fff; }
.site-page-button:hover, .site-page-button:focus { background: #003d80; color: #fff; text-decoration: none; }
.site-page-secondary { border: 1px solid #0056b3; color: #0056b3; }
.site-page-secondary:hover, .site-page-secondary:focus { background: #eef5ff; text-decoration: none; }
@media (max-width: 767px) {
    .site-information-page { padding: 24px 0 36px; }
    .site-page-card { padding: 22px 18px; }
    .site-page-card h1 { font-size: 25px; }
    .site-page-content p { font-size: 15px; }
}
</style>

<?php include __DIR__ . '/footer.php'; ?>

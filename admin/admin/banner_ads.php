<?php
session_start();
require_once __DIR__ . '/../../db.php';

if (!isset($_SESSION['admin_name'])) {
    $_SESSION['msg'] = 'You must log in first';
    header('Location: ../login.php');
    exit();
}

$create_table = "CREATE TABLE IF NOT EXISTS store_banners (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    caption VARCHAR(255) DEFAULT NULL,
    image_path VARCHAR(255) NOT NULL,
    target_url VARCHAR(500) DEFAULT NULL,
    button_text VARCHAR(60) DEFAULT NULL,
    status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    display_order INT NOT NULL DEFAULT 0,
    starts_at DATETIME DEFAULT NULL,
    ends_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
if (!mysqli_query($con, $create_table)) {
    throw new RuntimeException('Unable to initialize banner adverts: ' . mysqli_error($con));
}

if (empty($_SESSION['banner_ads_csrf'])) {
    $_SESSION['banner_ads_csrf'] = bin2hex(random_bytes(32));
}

$upload_directory = __DIR__ . '/../../img/advertisements';
$public_directory = 'img/advertisements/';
$error_message = '';

$escape = static function ($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

$remove_uploaded_image = static function (?string $image_path) use ($upload_directory, $public_directory): void {
    if (!$image_path || strpos($image_path, $public_directory) !== 0) {
        return;
    }

    $file_path = $upload_directory . DIRECTORY_SEPARATOR . basename($image_path);
    if (is_file($file_path)) {
        unlink($file_path);
    }
};

$validate_target_url = static function (string $url): bool {
    if ($url === '') {
        return true;
    }

    if (filter_var($url, FILTER_VALIDATE_URL)) {
        return in_array(strtolower((string)parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }

    return strpos($url, '//') !== 0
        && strpos($url, '\\') === false
        && preg_match('~^[a-zA-Z0-9_./?=&%-]+$~', $url) === 1;
};

$format_datetime = static function (string $value): ?string {
    if ($value === '') {
        return null;
    }

    $date = DateTime::createFromFormat('Y-m-d\TH:i', $value);
    return $date && $date->format('Y-m-d\TH:i') === $value
        ? $date->format('Y-m-d H:i:s')
        : false;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['banner_ads_csrf'], (string)($_POST['csrf_token'] ?? ''))) {
        $error_message = 'Your form session expired. Refresh the page and try again.';
    } elseif (isset($_POST['delete_banner'])) {
        $banner_id = filter_var($_POST['banner_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$banner_id || $banner_id < 1) {
            $error_message = 'Select a valid banner to delete.';
        } else {
            $stmt = mysqli_prepare($con, 'SELECT image_path FROM store_banners WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'i', $banner_id);
            if (!mysqli_stmt_execute($stmt)) {
                throw new RuntimeException('Unable to find banner: ' . mysqli_stmt_error($stmt));
            }
            $result = mysqli_stmt_get_result($stmt);
            $banner = $result ? mysqli_fetch_assoc($result) : null;

            if (!$banner) {
                $error_message = 'That banner was not found.';
            } else {
                $delete_stmt = mysqli_prepare($con, 'DELETE FROM store_banners WHERE id = ?');
                mysqli_stmt_bind_param($delete_stmt, 'i', $banner_id);
                if (!mysqli_stmt_execute($delete_stmt)) {
                    throw new RuntimeException('Unable to delete banner: ' . mysqli_stmt_error($delete_stmt));
                }
                $remove_uploaded_image($banner['image_path']);
                $_SESSION['banner_ads_notice'] = 'Banner advert deleted.';
                header('Location: banner_ads.php');
                exit();
            }
        }
    } elseif (isset($_POST['save_banner'])) {
        $banner_id = filter_var($_POST['banner_id'] ?? 0, FILTER_VALIDATE_INT);
        $title = trim((string)($_POST['title'] ?? ''));
        $caption = trim((string)($_POST['caption'] ?? ''));
        $target_url = trim((string)($_POST['target_url'] ?? ''));
        $button_text = trim((string)($_POST['button_text'] ?? ''));
        $status = (string)($_POST['status'] ?? 'draft');
        $display_order = filter_var($_POST['display_order'] ?? 0, FILTER_VALIDATE_INT);
        $starts_at = $format_datetime((string)($_POST['starts_at'] ?? ''));
        $ends_at = $format_datetime((string)($_POST['ends_at'] ?? ''));
        $current_image = '';

        if ($banner_id === false || $banner_id < 0 || $title === '' || strlen($title) > 180) {
            $error_message = 'Enter a banner title of 1 to 180 characters.';
        } elseif (strlen($caption) > 255 || strlen($button_text) > 60) {
            $error_message = 'Keep the banner message within 255 characters and button text within 60.';
        } elseif (!in_array($status, ['draft', 'published'], true) || $display_order === false) {
            $error_message = 'Choose a valid publish status and display order.';
        } elseif (!$validate_target_url($target_url)) {
            $error_message = 'Use a valid http/https link or a local path such as product.php?p=12.';
        } elseif ($starts_at === false || $ends_at === false || ($starts_at && $ends_at && $starts_at > $ends_at)) {
            $error_message = 'Enter valid dates and make sure the end time is after the start time.';
        } else {
            if ($banner_id > 0) {
                $existing_stmt = mysqli_prepare($con, 'SELECT image_path FROM store_banners WHERE id = ?');
                mysqli_stmt_bind_param($existing_stmt, 'i', $banner_id);
                if (!mysqli_stmt_execute($existing_stmt)) {
                    throw new RuntimeException('Unable to load banner: ' . mysqli_stmt_error($existing_stmt));
                }
                $existing_result = mysqli_stmt_get_result($existing_stmt);
                $existing = $existing_result ? mysqli_fetch_assoc($existing_result) : null;
                if (!$existing) {
                    $error_message = 'The banner you are editing no longer exists.';
                } else {
                    $current_image = $existing['image_path'];
                }
            }

            $image = $_FILES['banner_image'] ?? null;
            $new_image_name = '';
            if ($error_message === '' && $image && $image['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($image['error'] !== UPLOAD_ERR_OK || $image['size'] > 5 * 1024 * 1024) {
                    $error_message = 'Choose a banner image smaller than 5 MB.';
                } else {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime_type = $finfo->file($image['tmp_name']);
                    $allowed_types = [
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png',
                        'image/webp' => 'webp',
                        'image/gif' => 'gif'
                    ];
                    if (!isset($allowed_types[$mime_type]) || getimagesize($image['tmp_name']) === false) {
                        $error_message = 'Banner images must be valid JPG, PNG, WEBP, or GIF files.';
                    } elseif (!is_dir($upload_directory) && !mkdir($upload_directory, 0755, true) && !is_dir($upload_directory)) {
                        $error_message = 'The banner upload folder could not be created.';
                    } elseif (!is_writable($upload_directory)) {
                        $error_message = 'The banner upload folder is not writable.';
                    } else {
                        $new_image_name = bin2hex(random_bytes(16)) . '.' . $allowed_types[$mime_type];
                    }
                }
            }

            if ($error_message === '' && $new_image_name === '' && $current_image === '') {
                $error_message = 'Upload an image for this banner.';
            }

            if ($error_message === '' && $new_image_name !== '') {
                $destination = $upload_directory . DIRECTORY_SEPARATOR . $new_image_name;
                if (!move_uploaded_file($image['tmp_name'], $destination)) {
                    $error_message = 'The banner image could not be saved.';
                }
            }

            if ($error_message === '') {
                $image_path = $new_image_name !== '' ? $public_directory . $new_image_name : $current_image;
                $starts_at = $starts_at ?: null;
                $ends_at = $ends_at ?: null;
                $target_url = $target_url ?: null;
                $button_text = $button_text ?: null;
                $caption = $caption ?: null;

                if ($banner_id > 0) {
                    $stmt = mysqli_prepare($con, 'UPDATE store_banners SET title = ?, caption = ?, image_path = ?, target_url = ?, button_text = ?, status = ?, display_order = ?, starts_at = ?, ends_at = ? WHERE id = ?');
                    mysqli_stmt_bind_param($stmt, 'ssssssissi', $title, $caption, $image_path, $target_url, $button_text, $status, $display_order, $starts_at, $ends_at, $banner_id);
                } else {
                    $stmt = mysqli_prepare($con, 'INSERT INTO store_banners (title, caption, image_path, target_url, button_text, status, display_order, starts_at, ends_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    mysqli_stmt_bind_param($stmt, 'ssssssiss', $title, $caption, $image_path, $target_url, $button_text, $status, $display_order, $starts_at, $ends_at);
                }

                if (!mysqli_stmt_execute($stmt)) {
                    if ($new_image_name !== '') {
                        $remove_uploaded_image($public_directory . $new_image_name);
                    }
                    throw new RuntimeException('Unable to save banner: ' . mysqli_stmt_error($stmt));
                }

                if ($new_image_name !== '' && $current_image !== '') {
                    $remove_uploaded_image($current_image);
                }
                $_SESSION['banner_ads_notice'] = $banner_id > 0 ? 'Banner advert updated.' : 'Banner advert created.';
                header('Location: banner_ads.php');
                exit();
            }
        }
    }
}

$editing = null;
$edit_id = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT);
if ($edit_id && $edit_id > 0) {
    $stmt = mysqli_prepare($con, 'SELECT * FROM store_banners WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $edit_id);
    if (!mysqli_stmt_execute($stmt)) {
        throw new RuntimeException('Unable to load banner for editing: ' . mysqli_stmt_error($stmt));
    }
    $result = mysqli_stmt_get_result($stmt);
    $editing = $result ? mysqli_fetch_assoc($result) : null;
}

$banners = mysqli_query($con, 'SELECT * FROM store_banners ORDER BY display_order ASC, updated_at DESC');
if (!$banners) {
    throw new RuntimeException('Unable to load banners: ' . mysqli_error($con));
}

include 'sidenav.php';
include 'topheader.php';

$notice = $_SESSION['banner_ads_notice'] ?? '';
unset($_SESSION['banner_ads_notice']);
$form_values = $editing ?: [
    'id' => 0,
    'title' => '',
    'caption' => '',
    'image_path' => '',
    'target_url' => '',
    'button_text' => '',
    'status' => 'draft',
    'display_order' => 0,
    'starts_at' => '',
    'ends_at' => ''
];
$to_datetime_local = static function ($value): string {
    return $value ? date('Y-m-d\TH:i', strtotime($value)) : '';
};
?>

<div class="content">
    <div class="container-fluid">
        <div class="page-header pb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="page-title mb-1">Banner adverts</h2>
                    <p class="page-subtitle text-muted mb-0">Create and schedule image adverts for the storefront carousel.</p>
                </div>
                <div class="col-md-4 text-md-right mt-3 mt-md-0">
                    <a class="btn btn-outline-primary" href="../../index.php" target="_blank" rel="noopener">
                        <i class="material-icons mr-1">open_in_new</i> Preview store
                    </a>
                </div>
            </div>
        </div>

        <?php if ($notice !== ''): ?>
            <div class="alert alert-success" role="status"><?php echo $escape($notice); ?></div>
        <?php endif; ?>
        <?php if ($error_message !== ''): ?>
            <div class="alert alert-danger" role="alert"><?php echo $escape($error_message); ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header card-header-primary">
                        <h4 class="card-title"><?php echo $editing ? 'Edit banner advert' : 'Create banner advert'; ?></h4>
                        <p class="card-category">Recommended artwork: wide landscape banner (about 970 × 160 pixels).</p>
                    </div>
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo $escape($_SESSION['banner_ads_csrf']); ?>">
                            <input type="hidden" name="banner_id" value="<?php echo (int)$form_values['id']; ?>">
                            <div class="form-group">
                                <label for="banner-title">Campaign title *</label>
                                <input id="banner-title" class="form-control" type="text" name="title" maxlength="180" required value="<?php echo $escape($form_values['title']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="banner-caption">Banner message</label>
                                <input id="banner-caption" class="form-control" type="text" name="caption" maxlength="255" value="<?php echo $escape($form_values['caption']); ?>" placeholder="Example: Sports, Fitness &amp; Health Supplements — 20–80% Off">
                                <small class="form-text text-muted">Shown over the image when the artwork does not already contain the text.</small>
                            </div>
                            <div class="form-group">
                                <label for="banner-image">Banner image <?php echo $editing ? '' : '*'; ?></label>
                                <div class="banner-image-picker">
                                    <input id="banner-image" class="sr-only" type="file" name="banner_image" accept="image/jpeg,image/png,image/webp,image/gif" <?php echo $editing ? '' : 'required'; ?>>
                                    <label class="btn btn-outline-primary mb-2" for="banner-image">
                                        <i class="material-icons mr-1">upload_file</i>Choose image from computer
                                    </label>
                                    <div id="banner-image-name" class="small text-muted" aria-live="polite">
                                        <?php echo !empty($form_values['image_path']) ? $escape(basename($form_values['image_path'])) : 'No image selected. You can upload a file without providing an image URL.'; ?>
                                    </div>
                                    <small class="form-text text-muted">Select a JPG, PNG, WEBP, or GIF file from your device (maximum 5 MB). Image URLs are not required.</small>
                                    <img id="banner-image-preview" class="img-fluid mt-3" style="max-height:150px;border-radius:8px;<?php echo empty($form_values['image_path']) ? 'display:none;' : ''; ?>" src="<?php echo !empty($form_values['image_path']) ? '../../' . $escape($form_values['image_path']) : ''; ?>" alt="Banner image preview">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="banner-link">Click-through link</label>
                                <input id="banner-link" class="form-control" type="text" name="target_url" maxlength="500" value="<?php echo $escape($form_values['target_url']); ?>" placeholder="product.php?p=12 or https://example.com">
                            </div>
                            <div class="form-group">
                                <label for="banner-button">Button label (optional)</label>
                                <input id="banner-button" class="form-control" type="text" name="button_text" maxlength="60" value="<?php echo $escape($form_values['button_text']); ?>" placeholder="Shop now">
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="banner-status">Status</label>
                                        <select id="banner-status" class="form-control" name="status">
                                            <option value="draft" <?php echo $form_values['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                            <option value="published" <?php echo $form_values['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="banner-order">Display order</label>
                                        <input id="banner-order" class="form-control" type="number" name="display_order" min="0" max="9999" value="<?php echo (int)$form_values['display_order']; ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="banner-start">Start showing</label>
                                        <input id="banner-start" class="form-control" type="datetime-local" name="starts_at" value="<?php echo $escape($to_datetime_local($form_values['starts_at'])); ?>">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="banner-end">Stop showing</label>
                                        <input id="banner-end" class="form-control" type="datetime-local" name="ends_at" value="<?php echo $escape($to_datetime_local($form_values['ends_at'])); ?>">
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary" name="save_banner">
                                <i class="material-icons mr-1">save</i><?php echo $editing ? 'Save changes' : 'Create advert'; ?>
                            </button>
                            <?php if ($editing): ?>
                                <a class="btn btn-outline-secondary" href="banner_ads.php">Cancel</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header card-header-info">
                        <h4 class="card-title">Storefront adverts</h4>
                        <p class="card-category">Published banners within their scheduled dates appear on the home page.</p>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($banners) === 0): ?>
                            <div class="text-center text-muted py-5">
                                <i class="material-icons" style="font-size:48px;">campaign</i>
                                <p class="mb-0">No banner adverts yet. Create one to promote products or offers.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr><th>Preview</th><th>Campaign</th><th>Status / Schedule</th><th>Order</th><th>Actions</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($banner = mysqli_fetch_assoc($banners)): ?>
                                            <tr>
                                                <td><img src="../../<?php echo $escape($banner['image_path']); ?>" alt="" style="width:130px;height:48px;object-fit:cover;border-radius:5px;"></td>
                                                <td>
                                                    <strong><?php echo $escape($banner['title']); ?></strong>
                                                    <?php if ($banner['caption']): ?><br><small class="text-muted"><?php echo $escape($banner['caption']); ?></small><?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php echo $banner['status'] === 'published' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($escape($banner['status'])); ?></span>
                                                    <?php if ($banner['starts_at'] || $banner['ends_at']): ?>
                                                        <br><small><?php echo $escape($banner['starts_at'] ? date('M j, Y H:i', strtotime($banner['starts_at'])) : 'Now'); ?> – <?php echo $escape($banner['ends_at'] ? date('M j, Y H:i', strtotime($banner['ends_at'])) : 'No end'); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo (int)$banner['display_order']; ?></td>
                                                <td class="text-nowrap">
                                                    <a class="btn btn-sm btn-outline-primary" href="banner_ads.php?edit=<?php echo (int)$banner['id']; ?>" aria-label="Edit <?php echo $escape($banner['title']); ?>">Edit</a>
                                                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this banner advert?');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $escape($_SESSION['banner_ads_csrf']); ?>">
                                                        <input type="hidden" name="banner_id" value="<?php echo (int)$banner['id']; ?>">
                                                        <button class="btn btn-sm btn-outline-danger" type="submit" name="delete_banner">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var fileInput = document.getElementById('banner-image');
    var preview = document.getElementById('banner-image-preview');
    var fileName = document.getElementById('banner-image-name');
    var previousPreviewUrl = null;

    if (!fileInput || !preview || !fileName) {
        return;
    }

    fileInput.addEventListener('change', function () {
        var file = fileInput.files && fileInput.files[0];
        if (!file) {
            return;
        }

        if (previousPreviewUrl) {
            URL.revokeObjectURL(previousPreviewUrl);
        }

        previousPreviewUrl = URL.createObjectURL(file);
        preview.src = previousPreviewUrl;
        preview.style.display = 'block';
        fileName.textContent = file.name;
    });
})();
</script>
<?php include 'footer.php'; ?>

<?php
require 'libs/Parsedown.php'; // Include Parsedown manually
$config = require 'config.php'; // Include Configuration

$Parsedown = new Parsedown(); // Create a new Parsedown instance
$postsDir = 'posts'; // Directory where markdown posts are located
$pagesDir = 'pages'; // Directory where static pages are located
$files = scandir($postsDir); // Get all files in posts directory
$slug = $_GET['slug'] ?? ''; // Get the slug from the URL

// Function to extract metadata from the markdown files
function parseMetadata($content) {
    preg_match('/^---(.*?)---/s', $content, $matches); // Extract metadata block
    if ($matches) {
        $lines = explode("\n", trim($matches[1]));
        $metadata = [];
        foreach ($lines as $line) {
            list($key, $value) = explode(':', $line, 2);
            $metadata[trim($key)] = trim($value);
        }
        return $metadata;
    }
    return [];
}

// Function to find the post by slug
function findPostBySlug($slug, $postsDir) {
    $files = scandir($postsDir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'md') {
            $content = file_get_contents("$postsDir/$file");
            $metadata = parseMetadata($content);
            if (isset($metadata['slug']) && $metadata['slug'] === $slug) {
                return ['content' => $content, 'metadata' => $metadata];
            }
        }
    }
    return false;
}

// Function to generate post excerpt
function generateExcerpt($content) {
    return strip_tags(substr($content, 0, 150)) . '...';
}

// Function to generate the menu from pages
function generateMenu($pagesDir) {
    $menuItems = '';
    $files = scandir($pagesDir); // Scan the pages directory
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'md') {
            $content = file_get_contents("$pagesDir/$file");
            $metadata = parseMetadata($content); // Extract metadata
            $slug = $metadata['slug'] ?? pathinfo($file, PATHINFO_FILENAME);
            $title = $metadata['title'] ?? ucfirst($slug);
            $menuItems .= "<li class='nav-item'><a class='nav-link' href='page.php?slug=" . urlencode($slug) . "'>$title</a></li>";
        }
    }
    return $menuItems;
}

$post = findPostBySlug($slug, $postsDir);

if ($post) {
    $metadata = $post['metadata']; // Extract metadata
    $content = preg_replace('/^---(.*?)---/s', '', $post['content']); // Remove metadata block
    $htmlContent = $Parsedown->text($content); // Parse markdown content into HTML

    // Use default image from config if none is provided in metadata
    $imageUrl = !empty($metadata['image']) ? $metadata['image'] : $config['default_image'];
} else {
    $htmlContent = '<p>Post not found.</p>';
    $imageUrl = $config['default_image'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($metadata['title'] ?? $config['blog_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .hero-banner {
            position: relative;
            background-image: url('<?= htmlspecialchars($imageUrl) ?>');
            background-size: cover;
            background-position: center;
            height: 60vh; /* Adjust this height as necessary */
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero-banner::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.7));
            z-index: 1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            color: white;
            text-align: center;
        }

        .hero-content h1 {
            font-size: 3rem;
            font-weight: bold;
        }

        .hero-content p {
            font-size: 1.2rem;
        }

        .content {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <header class="bg-primary text-white py-3">
        <div class="container">
            <h1 class="mb-0"><?= $config['blog_name'] ?></h1>
            <p class="lead"><?= $config['tagline'] ?></p>
        </div>
    </header>

    
<!-- Hero Banner -->
    <div class="hero-banner">
        <div class="hero-content">
            <h1><?= htmlspecialchars($metadata['title'] ?? 'Untitled') ?></h1>
            <p><?= htmlspecialchars($metadata['date'] ?? 'Date not available') ?></p>
        </div>
    </div>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <!-- Dynamically generated menu items -->
                    <?= generateMenu($pagesDir); ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container content">
        <?php if ($post): ?>
            <div class="content">
                <?= $htmlContent ?>
            </div>
        <?php else: ?>
            <p>Post not found.</p>
        <?php endif; ?>

        <a href="index.php" class="btn btn-primary mt-3">Back to Blog</a>
    </div>

    <footer class="bg-light text-center py-4">
        <div class="container">
            <p class="mb-0"><?= $config['footer_text'] ?></p>
            <p>
                <a href="<?= $config['privacy_policy_link'] ?>">Privacy Policy</a> | 
                <a href="<?= $config['terms_service_link'] ?>">Terms of Service</a>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

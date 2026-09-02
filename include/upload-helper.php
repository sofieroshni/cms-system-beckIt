<?php
// Håndterer upload af ét billede. Returnerer ['src' => ..., 'thumbnail' => ...],
// eller null hvis der ikke blev valgt en ny fil.
function handle_image_upload(array $file): ?array {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        die('Fejl ved upload af billede.');
    }

    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $maxSize    = 5 * 1024 * 1024; // 5 MB

    if ($file['size'] > $maxSize) {
        die('Billedet er for stort (max 5 MB).');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        die('Ugyldigt filformat. Tilladt: ' . implode(', ', $allowedExt));
    }

    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename    = uniqid('img_', true) . '.' . $ext;
    $destination = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        die('Kunne ikke gemme billedet.');
    }

    $thumbFilename    = uniqid('thumb_', true) . '.' . $ext;
    $thumbDestination = $uploadDir . $thumbFilename;
    create_thumbnail($destination, $thumbDestination, 300);

    return [
        'src'       => 'uploads/' . $filename,
        'thumbnail' => 'uploads/' . $thumbFilename,
    ];
}

function create_thumbnail(string $sourcePath, string $destPath, int $maxWidth): void {
    $info = @getimagesize($sourcePath);
    if (!$info) return;
    [$width, $height, $type] = $info;

    $ratio     = $maxWidth / $width;
    $newWidth  = $maxWidth;
    $newHeight = (int) round($height * $ratio);

    $srcImage = match ($type) {
        IMAGETYPE_JPEG => imagecreatefromjpeg($sourcePath),
        IMAGETYPE_PNG  => imagecreatefrompng($sourcePath),
        IMAGETYPE_GIF  => imagecreatefromgif($sourcePath),
        IMAGETYPE_WEBP => imagecreatefromwebp($sourcePath),
        default        => null,
    };
    if (!$srcImage) return;

    $thumb = imagecreatetruecolor($newWidth, $newHeight);
    if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_GIF], true)) {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
    }
    imagecopyresampled($thumb, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    match ($type) {
        IMAGETYPE_JPEG => imagejpeg($thumb, $destPath, 85),
        IMAGETYPE_PNG  => imagepng($thumb, $destPath),
        IMAGETYPE_GIF  => imagegif($thumb, $destPath),
        IMAGETYPE_WEBP => imagewebp($thumb, $destPath),
        default        => null,
    };

    imagedestroy($srcImage);
    imagedestroy($thumb);
}
<?php
function secure_image_upload($file, $target_dir = '../assets/imgs/', $max_size = 2 * 1024 * 1024) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return [false, 'No file uploaded or upload error.'];
    }
    if ($file['size'] > $max_size) {
        return [false, 'File is too large. Max 2MB allowed.'];
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($mime, $allowed_types) || !in_array($ext, $allowed_exts)) {
        return [false, 'Invalid file type. Only JPG, PNG, GIF, WEBP allowed.'];
    }
    $filename = $target_dir . 'img_' . uniqid() . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $filename)) {
        return [false, 'Failed to save file.'];
    }
    return [true, $filename];
} 
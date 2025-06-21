<?php
// Start session if not already started
if (!isset($_SESSION)) session_start();

// Generate a random CAPTCHA string (6 chars, no confusing chars)
$characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$captcha_text = '';
for ($i = 0; $i < 6; $i++) {
    $captcha_text .= $characters[rand(0, strlen($characters) - 1)];
}

// Store the CAPTCHA text in session for later validation
$_SESSION['captcha_code'] = $captcha_text;

// Create a blank image
$width = 150;
$height = 50;
$image = imagecreatetruecolor($width, $height);

// Define colors
$bg_color = imagecolorallocate($image, 224, 224, 224); // Light gray background
$text_color = imagecolorallocate($image, 0, 0, 0);     // Black text
$noise_color = imagecolorallocate($image, 100, 120, 180); // Blue-ish noise

// Fill the background
imagefilledrectangle($image, 0, 0, $width, $height, $bg_color);

// Add random noise (dots)
for ($i = 0; $i < 100; $i++) {
    imageellipse($image, rand(0, $width), rand(0, $height), 1, 1, $noise_color);
}

// Add the CAPTCHA text
$font_size = 20;
$font_file = __DIR__ . '/arial.ttf'; // Path to TTF font file (must exist)
if (file_exists($font_file)) {
    // Use TrueType font if available
    imagettftext(
        $image,
        $font_size,
        rand(-10, 10), // Slight rotation
        20, 35,        // X, Y position
        $text_color,
        $font_file,
        $captcha_text
    );
} else {
    // Fallback to built-in font if TTF not found
    imagestring($image, 5, 35, 18, $captcha_text, $text_color);
}

// Output the image as PNG
header('Content-Type: image/png');
imagepng($image);
imagedestroy($image);
?>
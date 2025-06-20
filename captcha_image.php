<?php

if(!isset($_SESSION))session_start();

// Generate a random string
$characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$captcha_text = '';
for ($i = 0; $i < 6; $i++) {
    $captcha_text .= $characters[rand(0, strlen($characters) - 1)];
}

// Store the captcha text in session
$_SESSION['captcha_code'] = $captcha_text;

// Create the image
$width = 150;
$height = 50;
$image = imagecreatetruecolor($width, $height);

// Colors
$bg_color = imagecolorallocate($image, 0xe0, 0xe0, 0xe0);
$text_color = imagecolorallocate($image, 0, 0, 0);
$noise_color = imagecolorallocate($image, 100, 120, 180);

// Fill background
imagefilledrectangle($image, 0, 0, $width, $height, $bg_color);

// Add noise
for ($i = 0; $i < 100; $i++) {
    imageellipse($image, rand(0, $width), rand(0, $height), 1, 1, $noise_color);
}

// Add the text
$font_size = 20;
$font_file = __DIR__ . '/arial.ttf'; // Make sure you have this font file or change the path
if (file_exists($font_file)) {
    imagettftext($image, $font_size, rand(-10, 10), 20, 35, $text_color, $font_file, $captcha_text);
} else {
    imagestring($image, 5, 35, 18, $captcha_text, $text_color);
}

// Output the image
header('Content-Type: image/png');
imagepng($image);
imagedestroy($image);
?>
<?php
/**
 * Tests for CFRI_AttachmentPath – run with: php tests/test-attachment-path.php
 */

define('ABSPATH', __DIR__ . '/');

require_once dirname(__DIR__) . '/includes/class-attachment-path.php';

function cfri_assert($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    echo "PASS: {$message}\n";
}

// Exact year/month path must be preserved
cfri_assert(
    CFRI_AttachmentPath::relativeFromUrl('https://www.example.com/app/uploads/2026/08/GalleryImg2.jpg') === '2026/08/GalleryImg2.jpg',
    'extracts Bedrock /app/uploads relative path'
);

cfri_assert(
    CFRI_AttachmentPath::relativeFromUrl('https://www.example.com/uploads/2026/06/GalleryImg2.jpg') === '2026/06/GalleryImg2.jpg',
    'extracts /uploads relative path'
);

// Basename collisions must remain distinct
$aug = CFRI_AttachmentPath::relativeFromUrl('https://www.example.com/app/uploads/2026/08/GalleryImg2.jpg');
$june = CFRI_AttachmentPath::relativeFromUrl('https://www.example.com/app/uploads/2026/06/GalleryImg2.jpg');
cfri_assert($aug !== $june, 'same filename in different months stays distinct');

// Substring false-positive that LIKE would match must not equal exact path
$pretty = '2026/06/Pretty-Memories-GalleryImg2.jpg';
cfri_assert($aug !== $pretty, 'GalleryImg2.jpg path is not Pretty-Memories-GalleryImg2.jpg');
cfri_assert(
    CFRI_AttachmentPath::pathsMatch($aug, $pretty) === false,
    'pathsMatch rejects substring filename false positives'
);
cfri_assert(
    CFRI_AttachmentPath::pathsMatch($aug, '2026/08/GalleryImg2.jpg') === true,
    'pathsMatch accepts exact relative path'
);

echo "All tests passed.\n";

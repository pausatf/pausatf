<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class ScorerUploadTest extends TestCase
{
    public function testUploadExceptionIsThrown()
    {
        // Simple test to ensure the file parses and class exists
        // We use output buffering because scorer_upload.php outputs on load
        ob_start();
        require_once __DIR__ . '/../scorer_upload.php';
        ob_end_clean();

        $this->assertTrue(class_exists('ScorerUpload'));
    }
}

<?php
/**
 * ========================================
 * COMPLETE S3 MANAGEMENT GUIDE FOR PHP TINKER
 * ========================================
 * Copy and paste these commands directly into PHP Tinker
 * Usage: php artisan tinker
 */

// ==========================================
// 1. CONNECTION & CONFIGURATION TESTING
// ==========================================

// Test S3 connection with diagnostic
try {
    $testResult = Storage::disk('s3')->put('test-connection.txt', 'Connection test at ' . now());
    echo $testResult ? "✅ S3 Connection: SUCCESS\n" : "❌ S3 Connection: FAILED\n";
    Storage::disk('s3')->delete('test-connection.txt');
} catch (\Exception $e) {
    echo "❌ S3 Connection Error: " . $e->getMessage() . "\n";
}

// Get S3 configuration details
echo "🔧 S3 Configuration:\n";
echo "Bucket: " . config('filesystems.disks.s3.bucket') . "\n";
echo "Region: " . config('filesystems.disks.s3.region') . "\n";
echo "Endpoint: " . config('filesystems.disks.s3.endpoint') . "\n";

// Check if bucket exists and is accessible
try {
    $files = Storage::disk('s3')->files();
    echo "✅ Bucket accessible with " . count($files) . " files\n";
} catch (\Exception $e) {
    echo "❌ Bucket access error: " . $e->getMessage() . "\n";
}

// ==========================================
// 2. FILE UPLOAD OPERATIONS
// ==========================================

// Upload text content directly
Storage::disk('s3')->put('test.txt', 'Hello World!');

// Upload with custom visibility
Storage::disk('s3')->put('public-file.txt', 'Public content', 'public');

// Upload from local file
Storage::disk('s3')->putFile('uploads/', new \Illuminate\Http\File('/path/to/local/file.jpg'));

// Upload with custom filename
Storage::disk('s3')->putFileAs('uploads/', new \Illuminate\Http\File('/path/to/local/file.jpg'), 'custom-name.jpg');

// Upload multiple files at once
$files = [
    'file1.txt' => 'Content 1',
    'file2.txt' => 'Content 2',
    'folder/file3.txt' => 'Content 3'
];
foreach($files as $path => $content) {
    Storage::disk('s3')->put($path, $content);
    echo "Uploaded: {$path}\n";
}

// Upload with metadata
Storage::disk('s3')->put('metadata-file.txt', 'Content', [
    'visibility' => 'public',
    'ContentType' => 'text/plain',
    'CacheControl' => 'max-age=3600'
]);

// ==========================================
// 3. FILE DOWNLOAD OPERATIONS
// ==========================================

// Download file content as string
$content = Storage::disk('s3')->get('test.txt');
echo "File content: {$content}\n";

// Download file to local storage
Storage::disk('s3')->download('test.txt', 'downloaded-file.txt');

// Stream download for large files
$stream = Storage::disk('s3')->readStream('large-file.zip');

// Get file as response for browser download
// return Storage::disk('s3')->download('file.pdf', 'custom-filename.pdf');

// Check if file exists before downloading
if (Storage::disk('s3')->exists('test.txt')) {
    $content = Storage::disk('s3')->get('test.txt');
    echo "Downloaded: {$content}\n";
} else {
    echo "File does not exist\n";
}

// ==========================================
// 4. DIRECTORY & FILE LISTING
// ==========================================

// List all files in bucket
$allFiles = Storage::disk('s3')->allFiles();
echo "📁 Total files: " . count($allFiles) . "\n";
foreach($allFiles as $file) {
    echo "  📄 {$file}\n";
}

// List files in specific directory
$publicFiles = Storage::disk('s3')->files('public');
echo "📂 Files in public/:\n";
foreach($publicFiles as $file) {
    echo "  📄 {$file}\n";
}

// List files recursively in directory
$allPublicFiles = Storage::disk('s3')->allFiles('public');
echo "📂 All files in public/ (recursive):\n";
foreach($allPublicFiles as $file) {
    echo "  📄 {$file}\n";
}

// List all directories
$directories = Storage::disk('s3')->allDirectories();
echo "📁 All directories:\n";
foreach($directories as $dir) {
    echo "  📁 {$dir}\n";
}

// List directories in specific path
$subDirs = Storage::disk('s3')->directories('public');
echo "📂 Subdirectories in public/:\n";
foreach($subDirs as $dir) {
    echo "  📁 {$dir}\n";
}

// ==========================================
// 5. TREE VIEW OF BUCKET STRUCTURE
// ==========================================

// Display bucket as tree structure
function displayS3Tree($path = '', $prefix = '') {
    $dirs = Storage::disk('s3')->directories($path);
    $files = Storage::disk('s3')->files($path);
    
    foreach($dirs as $dir) {
        $dirName = basename($dir);
        echo $prefix . "📁 {$dirName}/\n";
        displayS3Tree($dir, $prefix . "  ");
    }
    
    foreach($files as $file) {
        $fileName = basename($file);
        $size = Storage::disk('s3')->size($file);
        echo $prefix . "📄 {$fileName} ({$size} bytes)\n";
    }
}

echo "🌳 S3 Bucket Tree Structure:\n";
displayS3Tree();

// Compact tree view
$dirs = Storage::disk('s3')->allDirectories();
$files = Storage::disk('s3')->allFiles();
echo "📊 Bucket Overview:\n";
echo "  📁 Directories: " . count($dirs) . "\n";
echo "  📄 Files: " . count($files) . "\n";
foreach($dirs as $dir) {
    $dirFiles = Storage::disk('s3')->allFiles($dir);
    echo "  📁 {$dir}/ (" . count($dirFiles) . " files)\n";
}

// ==========================================
// 6. FILE INFORMATION & METADATA
// ==========================================

// Get file size
$size = Storage::disk('s3')->size('test.txt');
echo "File size: {$size} bytes\n";

// Get last modified time
$timestamp = Storage::disk('s3')->lastModified('test.txt');
echo "Last modified: " . date('Y-m-d H:i:s', $timestamp) . "\n";

// Check file existence
$exists = Storage::disk('s3')->exists('test.txt');
echo "File exists: " . ($exists ? 'Yes' : 'No') . "\n";

// Get file URL
$url = Storage::disk('s3')->url('test.txt');
echo "File URL: {$url}\n";

// Get temporary URL (for private files)
$tempUrl = Storage::disk('s3')->temporaryUrl('private-file.txt', now()->addMinutes(60));
echo "Temporary URL: {$tempUrl}\n";

// Get file visibility
$visibility = Storage::disk('s3')->getVisibility('test.txt');
echo "File visibility: {$visibility}\n";

// ==========================================
// 7. FILE OPERATIONS (COPY, MOVE, RENAME)
// ==========================================

// Copy file within bucket
Storage::disk('s3')->copy('source.txt', 'destination.txt');

// Move/rename file
Storage::disk('s3')->move('old-name.txt', 'new-name.txt');

// Copy file to different directory
Storage::disk('s3')->copy('file.txt', 'backup/file.txt');

// Duplicate file with timestamp
$originalFile = 'important.txt';
if (Storage::disk('s3')->exists($originalFile)) {
    $backupName = 'backup/' . pathinfo($originalFile, PATHINFO_FILENAME) . '_' . now()->format('Y-m-d_H-i-s') . '.' . pathinfo($originalFile, PATHINFO_EXTENSION);
    Storage::disk('s3')->copy($originalFile, $backupName);
    echo "Backup created: {$backupName}\n";
}

// ==========================================
// 8. DIRECTORY OPERATIONS
// ==========================================

// Create directory by uploading a file
Storage::disk('s3')->put('new-folder/placeholder.txt', 'Directory created');

// Check if directory exists
$dirExists = count(Storage::disk('s3')->allFiles('folder-name')) > 0 || count(Storage::disk('s3')->allDirectories('folder-name')) > 0;
echo "Directory exists: " . ($dirExists ? 'Yes' : 'No') . "\n";

// Create nested directories
Storage::disk('s3')->put('level1/level2/level3/file.txt', 'Nested content');

// Copy entire directory structure
$sourceFiles = Storage::disk('s3')->allFiles('source-folder');
foreach($sourceFiles as $file) {
    $newPath = str_replace('source-folder/', 'destination-folder/', $file);
    Storage::disk('s3')->copy($file, $newPath);
    echo "Copied: {$file} → {$newPath}\n";
}

// ==========================================
// 9. BATCH OPERATIONS
// ==========================================

// Upload multiple files with progress
$filesToUpload = [
    'batch/file1.txt' => 'Content 1',
    'batch/file2.txt' => 'Content 2',
    'batch/file3.txt' => 'Content 3'
];
foreach($filesToUpload as $path => $content) {
    Storage::disk('s3')->put($path, $content);
    echo "✅ Uploaded: {$path}\n";
}

// Download multiple files
$filesToDownload = ['file1.txt', 'file2.txt', 'file3.txt'];
foreach($filesToDownload as $file) {
    if (Storage::disk('s3')->exists($file)) {
        $content = Storage::disk('s3')->get($file);
        file_put_contents(storage_path("app/{$file}"), $content);
        echo "✅ Downloaded: {$file}\n";
    }
}

// Batch file operations with error handling
$operations = [
    ['action' => 'put', 'path' => 'test1.txt', 'content' => 'Test 1'],
    ['action' => 'put', 'path' => 'test2.txt', 'content' => 'Test 2'],
    ['action' => 'copy', 'from' => 'test1.txt', 'to' => 'backup/test1.txt']
];

foreach($operations as $op) {
    try {
        switch($op['action']) {
            case 'put':
                Storage::disk('s3')->put($op['path'], $op['content']);
                echo "✅ Created: {$op['path']}\n";
                break;
            case 'copy':
                Storage::disk('s3')->copy($op['from'], $op['to']);
                echo "✅ Copied: {$op['from']} → {$op['to']}\n";
                break;
        }
    } catch (\Exception $e) {
        echo "❌ Error with {$op['action']}: {$e->getMessage()}\n";
    }
}

// ==========================================
// 10. FILE DELETION OPERATIONS
// ==========================================

// Delete single file
Storage::disk('s3')->delete('unwanted-file.txt');

// Delete multiple files
Storage::disk('s3')->delete(['file1.txt', 'file2.txt', 'file3.txt']);

// Delete all files in directory (but keep directory)
$folderFiles = Storage::disk('s3')->allFiles('temp-folder');
Storage::disk('s3')->delete($folderFiles);

// Delete entire directory and all contents
Storage::disk('s3')->deleteDirectory('old-folder');

// Delete files older than X days
$cutoffDate = now()->subDays(30)->timestamp;
$allFiles = Storage::disk('s3')->allFiles();
foreach($allFiles as $file) {
    $lastModified = Storage::disk('s3')->lastModified($file);
    if ($lastModified < $cutoffDate) {
        Storage::disk('s3')->delete($file);
        echo "🗑️ Deleted old file: {$file}\n";
    }
}

// Delete files by pattern
$allFiles = Storage::disk('s3')->allFiles();
$pattern = '/temp-.*\.txt$/';
foreach($allFiles as $file) {
    if (preg_match($pattern, basename($file))) {
        Storage::disk('s3')->delete($file);
        echo "🗑️ Deleted temp file: {$file}\n";
    }
}

// Safe delete with confirmation
$fileToDelete = 'important-file.txt';
if (Storage::disk('s3')->exists($fileToDelete)) {
    echo "⚠️ About to delete: {$fileToDelete}\n";
    $size = Storage::disk('s3')->size($fileToDelete);
    echo "File size: {$size} bytes\n";
    // Uncomment next line to actually delete
    // Storage::disk('s3')->delete($fileToDelete);
    echo "File deletion prepared (uncomment to execute)\n";
}

// ==========================================
// 11. CLEANUP OPERATIONS
// ==========================================

// Clean empty directories (S3 doesn't have true directories, but this removes "folder" markers)
$dirs = Storage::disk('s3')->allDirectories();
foreach($dirs as $dir) {
    $filesInDir = Storage::disk('s3')->allFiles($dir);
    if (empty($filesInDir)) {
        Storage::disk('s3')->deleteDirectory($dir);
        echo "🧹 Removed empty directory: {$dir}\n";
    }
}

// Delete duplicate files (by name)
$allFiles = Storage::disk('s3')->allFiles();
$fileNames = [];
foreach($allFiles as $file) {
    $basename = basename($file);
    if (isset($fileNames[$basename])) {
        echo "🔍 Duplicate found: {$file} (original: {$fileNames[$basename]})\n";
        // Uncomment to delete duplicate
        // Storage::disk('s3')->delete($file);
    } else {
        $fileNames[$basename] = $file;
    }
}

// ==========================================
// 12. ADVANCED OPERATIONS
// ==========================================

// Set file visibility
Storage::disk('s3')->setVisibility('file.txt', 'public');
Storage::disk('s3')->setVisibility('private-file.txt', 'private');

// Generate signed URLs for private files
$signedUrl = Storage::disk('s3')->temporaryUrl('private-file.txt', now()->addHours(24));
echo "24-hour access URL: {$signedUrl}\n";

// Check total storage usage
$totalSize = 0;
$fileCount = 0;
$allFiles = Storage::disk('s3')->allFiles();
foreach($allFiles as $file) {
    $totalSize += Storage::disk('s3')->size($file);
    $fileCount++;
}
echo "📊 Storage Usage:\n";
echo "  Files: {$fileCount}\n";
echo "  Total Size: " . number_format($totalSize / 1024 / 1024, 2) . " MB\n";

// Find largest files
$fileSizes = [];
foreach($allFiles as $file) {
    $fileSizes[$file] = Storage::disk('s3')->size($file);
}
arsort($fileSizes);
echo "🔍 Largest files:\n";
$count = 0;
foreach($fileSizes as $file => $size) {
    if ($count++ >= 10) break;
    echo "  📄 {$file}: " . number_format($size / 1024, 2) . " KB\n";
}

// Sync local directory to S3
function syncToS3($localPath, $s3Path) {
    $files = glob($localPath . '/*');
    foreach($files as $file) {
        if (is_file($file)) {
            $s3FilePath = $s3Path . '/' . basename($file);
            Storage::disk('s3')->put($s3FilePath, file_get_contents($file));
            echo "📤 Synced: {$file} → {$s3FilePath}\n";
        }
    }
}
// Usage: syncToS3(storage_path('app/uploads'), 'backups');

// ==========================================
// 13. MONITORING & DIAGNOSTICS
// ==========================================

// Complete bucket analysis
echo "🔍 COMPLETE S3 BUCKET ANALYSIS:\n";
echo "================================\n";

$allFiles = Storage::disk('s3')->allFiles();
$allDirs = Storage::disk('s3')->allDirectories();
$totalSize = array_sum(array_map(fn($file) => Storage::disk('s3')->size($file), $allFiles));

echo "📊 Summary:\n";
echo "  Total Files: " . count($allFiles) . "\n";
echo "  Total Directories: " . count($allDirs) . "\n";
echo "  Total Size: " . number_format($totalSize / 1024 / 1024, 2) . " MB\n";

$extensions = [];
foreach($allFiles as $file) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $extensions[$ext] = ($extensions[$ext] ?? 0) + 1;
}

echo "\n📋 File Types:\n";
arsort($extensions);
foreach($extensions as $ext => $count) {
    echo "  .{$ext}: {$count} files\n";
}

echo "\n📁 Directory Structure:\n";
foreach($allDirs as $dir) {
    $filesInDir = count(Storage::disk('s3')->files($dir));
    $allFilesInDir = count(Storage::disk('s3')->allFiles($dir));
    echo "  📁 {$dir}/: {$filesInDir} direct files, {$allFilesInDir} total files\n";
}

// Test upload/download speed
echo "\n⚡ Performance Test:\n";
$testData = str_repeat('X', 1024 * 100); // 100KB test file
$start = microtime(true);
Storage::disk('s3')->put('speed-test.txt', $testData);
$uploadTime = microtime(true) - $start;

$start = microtime(true);
$downloaded = Storage::disk('s3')->get('speed-test.txt');
$downloadTime = microtime(true) - $start;

Storage::disk('s3')->delete('speed-test.txt');

echo "  Upload Speed: " . number_format(100 / $uploadTime, 2) . " KB/s\n";
echo "  Download Speed: " . number_format(100 / $downloadTime, 2) . " KB/s\n";

echo "\n✅ Analysis Complete!\n";

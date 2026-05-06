<?php

/**
 * Video Helper - Wrapper for FFmpeg operations
 */
class VideoHelper {
    
    /**
     * Check if FFmpeg is available
     */
    public static function isAvailable(): bool {
        return exec('which ffmpeg') !== '' || exec('ffmpeg -version') !== '';
    }
    
    /**
     * Extract video metadata using FFprobe
     * @param string $filePath Path to the video file
     * @return array|null Metadata or null if failed
     */
    public static function getVideoMetadata(string $filePath): ?array {
        if (!self::isAvailable()) {
            return null;
        }
        
        $cmd = sprintf(
            'ffprobe -v quiet -print_format json -show_format -show_streams "%s"',
            escapeshellarg($filePath)
        );
        
        $output = shell_exec($cmd);
        if (!$output) {
            return null;
        }
        
        $data = json_decode($output, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        
        $metadata = [
            'duration' => 0,
            'width' => 0,
            'height' => 0,
            'bitrate' => 0
        ];
        
        // Extract duration from format
        if (isset($data['format']['duration'])) {
            $metadata['duration'] = (float)$data['format']['duration'];
        }
        
        // Extract bitrate from format
        if (isset($data['format']['bit_rate'])) {
            $metadata['bitrate'] = (int)$data['format']['bit_rate'];
        }
        
        // Find video stream for dimensions
        if (isset($data['streams'])) {
            foreach ($data['streams'] as $stream) {
                if ($stream['codec_type'] === 'video') {
                    if (isset($stream['width'])) {
                        $metadata['width'] = (int)$stream['width'];
                    }
                    if (isset($stream['height'])) {
                        $metadata['height'] = (int)$stream['height'];
                    }
                    break;
                }
            }
        }
        
        return $metadata;
    }
    
    /**
     * Generate thumbnail from video
     * @param string $videoPath Path to the video file
     * @param string $thumbPath Path where thumbnail should be saved
     * @return bool True on success
     */
    public static function generateThumbnail(string $videoPath, string $thumbPath): bool {
        if (!self::isAvailable()) {
            return false;
        }
        
        // Ensure thumbnail directory exists
        $thumbDir = dirname($thumbPath);
        if (!is_dir($thumbDir) && !mkdir($thumbDir, 0755, true)) {
            return false;
        }
        
        // Extract frame at 1 second
        $cmd = sprintf(
            'ffmpeg -i "%s" -ss 00:00:01.000 -vframes 1 "%s" -y',
            escapeshellarg($videoPath),
            escapeshellarg($thumbPath)
        );
        
        $result = shell_exec($cmd . ' 2>&1');
        return file_exists($thumbPath) && filesize($thumbPath) > 0;
    }
}
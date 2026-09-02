<?php

if (!class_exists('Composer\Autoload\ClassLoader')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use OSS\OssClient;
use Aws\S3\S3Client;
use Upyun\Upyun;
use Upyun\Config;

/**
 * 存储助手类 - 统一管理所有存储方式
 */
class StorageHelper {
    
    /**
     * 上传文件到指定存储
     */
    public static function upload($storage, $config, $localFilePath, $remotePath, $options = []) {
        switch ($storage) {
            case 'local':
                // 本地存储不需要额外操作，文件已经在本地
                return true;
                
            case 'oss':
                $client = self::createOssClient($config);
                $client->uploadFile($config['oss_bucket'], $remotePath, $localFilePath);
                return true;
                
            case 's3':
                $client = self::createS3Client($config);
                $params = [
                    'Bucket' => $config['s3_bucket'],
                    'Key' => $remotePath,
                    'SourceFile' => $localFilePath,
                ];

                if (!empty($options['content_type'])) {
                    $params['ContentType'] = $options['content_type'];
                }

                if (!empty($options['content_disposition'])) {
                    $params['ContentDisposition'] = $options['content_disposition'];
                }
                
                // 只有在設定了 ACL 且不為空時才加入
                if (!empty($config['s3_acl'])) {
                    $params['ACL'] = $config['s3_acl'];
                }
                
                $result = $client->putObject($params);
                return $result;
                
            case 'upyun':
                $client = self::createUpyunClient($config);
                $fileContent = file_get_contents($localFilePath);
                $client->write($remotePath, $fileContent);
                return true;
                
            default:
                throw new \Exception("不支持的存储方式: {$storage}");
        }
    }
    
    /**
     * 从指定存储删除文件
     */
    public static function delete($storage, $config, $path) {
        try {
            switch ($storage) {
                case 'local':
                    $cleanPath = ltrim(str_replace('\\', '/', (string)$path), '/');
                    if ($cleanPath === '' || str_contains($cleanPath, "\0") || preg_match('#(^|/)\.\.?(/|$)#', $cleanPath)) {
                        return false;
                    }

                    $appRoot = realpath(__DIR__ . '/..');
                    $storageRoot = $appRoot ? realpath($appRoot . '/storage') : null;

                    $target = $appRoot ? ($appRoot . '/' . $cleanPath) : null;
                    if ($target && file_exists($target)) {
                        $realTarget = realpath($target);
                        if ($realTarget && $storageRoot && str_starts_with($realTarget, $storageRoot) && is_file($realTarget)) {
                            return unlink($realTarget);
                        }
                    }
                    return true;
                    
                case 'oss':
                    $client = self::createOssClient($config);
                    $key = ltrim((string)(parse_url($path, PHP_URL_PATH) ?: $path), '/');
                    $client->deleteObject($config['oss_bucket'], $key);
                    return true;
                    
                case 's3':
                    $client = self::createS3Client($config);
                    $key = !empty($config['s3_cdn_domain']) 
                        ? str_replace(rtrim($config['s3_cdn_domain'], '/') . '/', '', $path) 
                        : $path;
                    $key = ltrim((string)(parse_url($key, PHP_URL_PATH) ?: $key), '/');
                    $client->deleteObject([
                        'Bucket' => $config['s3_bucket'],
                        'Key' => $key,
                    ]);
                    return true;
                    
                case 'upyun':
                    $client = self::createUpyunClient($config);
                    $key = !empty($config['upyun_cdn_domain'])
                        ? str_replace(rtrim($config['upyun_cdn_domain'], '/') . '/', '', $path)
                        : $path;
                    $key = ltrim((string)(parse_url($key, PHP_URL_PATH) ?: $key), '/');
                    $client->delete($key);
                    return true;
                    
                default:
                    throw new \Exception("不支持的存储方式: {$storage}");
            }
        } catch (\Exception $e) {
            // 忽略404错误（文件不存在）
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, '404') !== false || 
                strpos($errorMessage, 'NoSuchKey') !== false ||
                strpos($errorMessage, 'not exist') !== false ||
                strpos($errorMessage, '不存在') !== false) {
                return true;
            }
            // 其他错误继续抛出
            throw $e;
        }
    }
    
    /**
     * 创建OSS客户端
     */
    private static function createOssClient($config) {
        return new OssClient(
            $config['oss_access_key_id'],
            $config['oss_access_key_secret'],
            $config['oss_endpoint']
        );
    }
    
    /**
     * 创建S3客户端
     */
    private static function createS3Client($config) {
        $endpoint = $config['s3_endpoint'] ?? '';
        if ($endpoint && !preg_match('/^https?:\/\//', $endpoint)) {
            $endpoint = 'https://' . $endpoint;
        }

        return new S3Client([
            'region' => $config['s3_region'],
            'version' => 'latest',
            'endpoint' => $endpoint,
            'credentials' => [
                'key' => $config['s3_access_key_id'],
                'secret' => $config['s3_access_key_secret'],
            ],
            'suppress_php_deprecation_warning' => true,
            'http' => [
                'verify' => true
            ]
        ]);
    }
    
    /**
     * 创建又拍云客户端
     */
    private static function createUpyunClient($config) {
        $serviceConfig = new Config(
            $config['upyun_bucket'],
            $config['upyun_operator'],
            $config['upyun_password']
        );
        
        return new Upyun($serviceConfig);
    }
    
    /**
     * 测试存储连接
     */
    public static function testConnection($storage, $config) {
        switch ($storage) {
            case 'local':
                return true;
                
            case 'oss':
                $client = self::createOssClient($config);
                $client->doesBucketExist($config['oss_bucket']);
                return true;
                
            case 's3':
                $client = self::createS3Client($config);
                $client->headBucket(['Bucket' => $config['s3_bucket']]);
                return true;
                
            case 'upyun':
                $client = self::createUpyunClient($config);
                $client->read('/', ['list' => true]);
                return true;
                
            default:
                throw new \Exception("不支持的存储方式");
        }
    }
}

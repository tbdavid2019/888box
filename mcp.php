<?php
/**
 * 888box MCP Server
 * 
 * Implements Model Context Protocol (MCP) over stdio.
 * Allows LLMs to use 888box as a tool.
 */

require_once 'vendor/autoload.php';
require_once 'config/database.php';
require_once 'config/upload.php';

// 初始化数据库
$db = Database::getInstance();
$pdo = $db->getConnection();

/**
 * 验证 Token
 */
function verifyToken($pdo, $token) {
    if (empty($token)) return false;
    $stmt = $pdo->prepare("SELECT id FROM users WHERE token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ? $user['id'] : false;
}

/**
 * 处理 JSON-RPC 请求
 */
function handleRequest($request, $pdo) {
    $method = $request['method'] ?? '';
    $params = $request['params'] ?? [];
    $id = $request['id'] ?? null;

    switch ($method) {
        case 'initialize':
            return [
                'protocolVersion' => '2024-11-05',
                'capabilities' => [
                    'tools' => (object)[],
                    'resources' => (object)[]
                ],
                'serverInfo' => [
                    'name' => '888box-mcp-server',
                    'version' => '1.0.0'
                ]
            ];

        case 'tools/list':
            return [
                'tools' => [
                    [
                        'name' => 'upload_asset_by_url',
                        'description' => 'Upload an image, video, or file from a URL to 888box',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'token' => ['type' => 'string', 'description' => 'API Token'],
                                'url' => ['type' => 'string', 'description' => 'URL of the asset to upload'],
                                'title' => ['type' => 'string', 'description' => 'Title for videos/files (optional)'],
                                'description' => ['type' => 'string', 'description' => 'Description for videos/files (optional)']
                            ],
                            'required' => ['token', 'url']
                        ]
                    ],
                    [
                        'name' => 'list_assets',
                        'description' => 'List recently uploaded assets (images, videos, audios, files)',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'token' => ['type' => 'string', 'description' => 'API Token'],
                                'type' => ['type' => 'string', 'enum' => ['all', 'image', 'video', 'audio', 'file'], 'description' => 'Type of assets to list'],
                                'page' => ['type' => 'integer', 'description' => 'Page number']
                            ],
                            'required' => ['token']
                        ]
                    ],
                    [
                        'name' => 'get_stats',
                        'description' => 'Get statistics about assets in 888box',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'token' => ['type' => 'string', 'description' => 'API Token']
                            ],
                            'required' => ['token']
                        ]
                    ],
                    [
                        'name' => 'get_podcast_info',
                        'description' => 'Get the current Podcast RSS feed URLs (video & audio) and status',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'token' => ['type' => 'string', 'description' => 'API Token']
                            ],
                            'required' => ['token']
                        ]
                    ],
                    [
                        'name' => 'rebuild_podcast_rss',
                        'description' => 'Force a rebuild of the Podcast RSS feeds from the database',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'token' => ['type' => 'string', 'description' => 'API Token'],
                                'type' => ['type' => 'string', 'enum' => ['video', 'audio'], 'description' => 'Type of Podcast to rebuild (video/audio, default: video)']
                            ],
                            'required' => ['token']
                        ]
                    ],
                    [
                        'name' => 'delete_asset',
                        'description' => 'Delete an asset by ID',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'token' => ['type' => 'string', 'description' => 'API Token'],
                                'id' => ['type' => 'integer', 'description' => 'Asset ID to delete']
                            ],
                            'required' => ['token', 'id']
                        ]
                    ]
                ]
            ];

        case 'tools/call':
            $toolName = $params['name'] ?? '';
            $args = $params['arguments'] ?? [];
            $token = $args['token'] ?? '';
            
            $userId = verifyToken($pdo, $token);
            if (!$userId) {
                return ['content' => [['type' => 'text', 'text' => 'Error: Invalid API Token']], 'isError' => true];
            }

            // 設置 Token 到全局，以便被調用的 API 邏輯使用
            $_SESSION['user_id'] = $userId;
            $_POST['token'] = $token;

            if ($toolName === 'list_assets') {
                $type = $args['type'] ?? 'all';
                $page = max(1, (int)($args['page'] ?? 1));
                
                // 為了保持一致性，我們直接內嵌 api.php 的邏輯或發送模擬請求
                // 這裡直接查詢資料庫以確保效能
                $limit = 10;
                $offset = ($page - 1) * $limit;
                $where = "1=1";
                if ($type === 'image') {
                    $where = "is_video = 0 AND is_audio = 0 AND is_file = 0";
                } elseif ($type === 'video') {
                    $where = "is_video = 1";
                } elseif ($type === 'audio') {
                    $where = "is_audio = 1";
                } elseif ($type === 'file') {
                    $where = "is_file = 1";
                }

                $stmt = $pdo->prepare("SELECT * FROM images WHERE $where ORDER BY created_at DESC LIMIT ? OFFSET ?");
                $stmt->bindValue(1, $limit, PDO::PARAM_INT);
                $stmt->bindValue(2, $offset, PDO::PARAM_INT);
                $stmt->execute();
                $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

                return [
                    'content' => [
                        ['type' => 'text', 'text' => "Recently uploaded $type assets:\n" . json_encode($assets, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)]
                    ]
                ];
            }

            if ($toolName === 'upload_asset_by_url') {
                $url = $args['url'] ?? '';
                if (empty($url)) {
                    return ['content' => [['type' => 'text', 'text' => 'Error: URL is required']], 'isError' => true];
                }

                // 捕獲 api.php 的輸出
                ob_start();
                $_REQUEST['action'] = 'upload_url';
                $_REQUEST['url'] = $url;
                $_POST['title'] = $args['title'] ?? '';
                $_POST['description'] = $args['description'] ?? '';
                
                require_once 'api.php'; // 執行上傳邏輯
                
                $output = ob_get_clean();
                return [
                    'content' => [
                        ['type' => 'text', 'text' => "Upload result: " . $output]
                    ]
                ];
            }

            if ($toolName === 'get_stats') {
                $stats = [
                    'total' => (int)$pdo->query("SELECT COUNT(*) FROM images")->fetchColumn(),
                    'image' => (int)$pdo->query("SELECT COUNT(*) FROM images WHERE is_video = 0 AND is_audio = 0 AND is_file = 0")->fetchColumn(),
                    'video' => (int)$pdo->query("SELECT COUNT(*) FROM images WHERE is_video = 1")->fetchColumn(),
                    'audio' => (int)$pdo->query("SELECT COUNT(*) FROM images WHERE is_audio = 1")->fetchColumn(),
                    'file' => (int)$pdo->query("SELECT COUNT(*) FROM images WHERE is_file = 1")->fetchColumn(),
                ];

                return [
                    'content' => [
                        ['type' => 'text', 'text' => "888box Statistics:\n" . json_encode($stats, JSON_PRETTY_PRINT)]
                    ]
                ];
            }

            if ($toolName === 'get_podcast_info') {
                $config = Database::getConfig($pdo);
                $host = $_SERVER['HTTP_HOST'] ?? 'box.david888.com';
                $rssVideoUrl = 'https://' . $host . '/storage/podcast.xml';
                $rssAudioUrl = 'https://' . $host . '/storage/podcast_audio.xml';
                return [
                    'content' => [
                        ['type' => 'text', 'text' => "Podcast Info:\nVideo RSS Feed: $rssVideoUrl\nAudio RSS Feed: $rssAudioUrl\nStorage: " . $config['storage']]
                    ]
                ];
            }

            if ($toolName === 'rebuild_podcast_rss') {
                $type = $args['type'] ?? 'video';
                $config = Database::getConfig($pdo);
                if ($type === 'audio') {
                    require_once 'config/audio_logic.php';
                    rebuildAudioRSS($pdo, $config);
                } else {
                    require_once 'config/video_logic.php';
                    rebuildVideoRSS($pdo, $config);
                }
                return [
                    'content' => [
                        ['type' => 'text', 'text' => "Podcast " . ucfirst($type) . " RSS feed has been rebuilt successfully."]
                    ]
                ];
            }

            if ($toolName === 'delete_asset') {
                $id = (int)($args['id'] ?? 0);
                require_once 'config/delete.php';
                $success = deleteAsset($pdo, $id);
                
                return [
                    'content' => [
                        ['type' => 'text', 'text' => $success ? "Asset $id deleted successfully" : "Failed to delete asset $id"]
                    ],
                    'isError' => !$success
                ];
            }

            return ['content' => [['type' => 'text', 'text' => 'Unknown tool: ' . $toolName]], 'isError' => true];

        default:
            return ['error' => ['code' => -32601, 'message' => 'Method not found']];
    }
}

// 主循環 (stdio 傳輸)
while ($line = fgets(STDIN)) {
    $request = json_decode($line, true);
    if (!$request) continue;
    
    // 清理之前的輸出快照，確保 JSON-RPC 響應純淨
    ob_start();
    $result = handleRequest($request, $pdo);
    $ob_content = ob_get_clean(); // 捕獲可能的意外輸出
    
    $response = [
        'jsonrpc' => '2.0',
        'result' => $result
    ];
    if (isset($request['id'])) {
        $response['id'] = $request['id'];
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE) . "\n";
}


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
                        'name' => 'upload_image',
                        'description' => 'Upload an image from a URL',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'token' => ['type' => 'string', 'description' => 'API Token'],
                                'url' => ['type' => 'string', 'description' => 'URL of the image to upload']
                            ],
                            'required' => ['token', 'url']
                        ]
                    ],
                    [
                        'name' => 'list_images',
                        'description' => 'List recently uploaded images',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'token' => ['type' => 'string', 'description' => 'API Token'],
                                'page' => ['type' => 'integer', 'description' => 'Page number']
                            ],
                            'required' => ['token']
                        ]
                    ],
                    [
                        'name' => 'get_image_details',
                        'description' => 'Get metadata for a specific image by ID',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'token' => ['type' => 'string', 'description' => 'API Token'],
                                'id' => ['type' => 'integer', 'description' => 'Image ID']
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

            if ($toolName === 'list_images') {
                $page = max(1, (int)($args['page'] ?? 1));
                $limit = 10;
                $offset = ($page - 1) * $limit;
                
                $stmt = $pdo->prepare("SELECT * FROM images ORDER BY created_at DESC LIMIT ? OFFSET ?");
                $stmt->bindValue(1, $limit, PDO::PARAM_INT);
                $stmt->bindValue(2, $offset, PDO::PARAM_INT);
                $stmt->execute();
                $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($images as &$img) {
                    $img['share_url'] = $img['url'];
                }

                return [
                    'content' => [
                        ['type' => 'text', 'text' => json_encode($images, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)]
                    ]
                ];
            }

            if ($toolName === 'get_image_details') {
                $id = (int)($args['id'] ?? 0);
                $stmt = $pdo->prepare("SELECT * FROM images WHERE id = ?");
                $stmt->execute([$id]);
                $image = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$image) {
                    return ['content' => [['type' => 'text', 'text' => "Error: Image with ID $id not found"]], 'isError' => true];
                }
                
                $image['share_url'] = $image['url'];

                return [
                    'content' => [
                        ['type' => 'text', 'text' => json_encode($image, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)]
                    ]
                ];
            }

            if ($toolName === 'upload_image') {
                $url = $args['url'] ?? '';
                if (empty($url)) {
                    return ['content' => [['type' => 'text', 'text' => 'Error: URL is required']], 'isError' => true];
                }

                // 核心: 模拟 $_FILES 并调用 handleUploadedFile
                // 但是 handleUploadedFile 依赖 move_uploaded_file，这在非上传请求中会失败。
                // 所以我们可能需要重构 handleUploadedFile 或者手动处理。
                // 为了简单起见，这里先返回提示。
                return ['content' => [['type' => 'text', 'text' => 'Direct URL upload via MCP is partially supported. Please use the /api.php endpoint for stable uploads.']], 'isError' => true];
            }

            return ['content' => [['type' => 'text', 'text' => 'Unknown tool: ' . $toolName]], 'isError' => true];

        default:
            return ['error' => ['code' => -32601, 'message' => 'Method not found']];
    }
}

// 主循环 (stdio 传输)
while ($line = fgets(STDIN)) {
    $request = json_decode($line, true);
    if (!$request) continue;
    
    $result = handleRequest($request, $pdo);
    
    $response = [
        'jsonrpc' => '2.0',
        'result' => $result
    ];
    if (isset($request['id'])) {
        $response['id'] = $request['id'];
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE) . "\n";
}

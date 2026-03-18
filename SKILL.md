# PixPro Image Hosting Skill

A professional image hosting and management system for AI agents and LLMs.

## Overview
PixPro allows you to upload, list, and search for images. It supports modern formats like WebP and handles various storage backends (Local, OSS, S3, UpYun).

## Tools

### upload_image
Upload an image to the PixPro system.
- **Endpoint**: `POST /api.php`
- **Authentication**: Bearer Token in `Authorization` header or `token` in POST body.
- **Parameters**:
  - `file`: The image file (multipart/form-data).
  - `quality`: (Optional) Image quality (1-100, default: 60).
- **Response**: JSON containing the image `url`, `width`, `height`, and `size`.

### list_images
Retrieve a paginated list of recently uploaded images.
- **Endpoint**: `GET /api.php?action=list`
- **Authentication**: Bearer Token or Session.
- **Parameters**:
  - `page`: (Optional) Page number (default: 1).
- **Response**: JSON containing an array of images (each with `url`, `share_url`, `size` etc.) and pagination metadata.

### search_images
Search for images by path or URL.
- **Endpoint**: `GET /api.php?action=search`
- **Authentication**: Bearer Token or Session.
- **Parameters**:
  - `q`: The search query string.
- **Response**: JSON containing an array of matching images (each with `share_url`).

## Authentication
All requests require a valid API Token.
```http
Authorization: Bearer <your_token>
```

## Example Usage

### Uploading an image (via cURL)
```bash
curl -X POST "https://your-pixpro-domain.com/api.php" \
     -H "Authorization: Bearer your_token_here" \
     -F "file=@/path/to/image.jpg"
```

### Searching for images
```bash
curl -G "https://your-pixpro-domain.com/api.php" \
     -H "Authorization: Bearer your_token_here" \
     -d "action=search" \
     -d "q=nature"
```

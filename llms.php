<?php
header('Content-Type: text/markdown; charset=utf-8');

// Determine Base URL dynamically
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = $protocol . $host;
?>
# 888box - Asset Platform for Images, Videos, Audio, and Files

> 888box is a lightweight, self-hosted unified asset management platform. It provides direct web uploads, remote URL ingestion, media transcoding, podcast RSS generation, and dedicated AI agent surfaces (MCP tools and LLM Skills).

## Core API & Agent Interfaces

- [API Gateway](<?= $baseUrl ?>/api.php): Unified JSON API supporting asset upload, remote URL ingestion, list, search, stats, and delete actions.
- [AI Agent Skill Specification](<?= $baseUrl ?>/skill.php): Dynamic Markdown skill definition for LLM agents, complete with live host hints and token authorization details.
- [MCP Server Endpoint](<?= $baseUrl ?>/mcp.php): Model Context Protocol server over HTTP and stdio for AI workspace integration.
- [Full LLM Specification](<?= $baseUrl ?>/llms-full.txt): Complete extended documentation and skill definition for LLMs.

## Platform Centers & Viewer

- [Portal Main Page](<?= $baseUrl ?>/): Bento-style central dashboard routing to upload centers.
- [Image Upload Center](<?= $baseUrl ?>/upload_image.php): Image hosting and WebP conversion UI.
- [Video Upload Center](<?= $baseUrl ?>/upload_video.php): Video hosting, thumbnail generation, and podcast feed UI.
- [Audio Upload Center](<?= $baseUrl ?>/upload_audio.php): Audio hosting, ID3 metadata parsing, and RSS feed UI.
- [File Upload Center](<?= $baseUrl ?>/upload_file.php): Document and general file distribution UI.
- [Asset Viewer / Share Gateway](<?= $baseUrl ?>/view.php): Unified asset presentation page supporting password protection, metadata preview, and direct downloads.

## RSS Feeds & Discovery

- [Video Podcast RSS](<?= $baseUrl ?>/storage/podcast.xml): Dynamic Podcast RSS feed for video media.
- [Audio Podcast RSS](<?= $baseUrl ?>/storage/podcast_audio.xml): Dynamic Podcast RSS feed for audio media.
- [Sitemap](<?= $baseUrl ?>/sitemap.xml): XML sitemap indexing asset share pages.
- [API Catalog](<?= $baseUrl ?>/.well-known/api-catalog): RFC 8288 linkset catalog for agent discovery.
- [MCP Server Card](<?= $baseUrl ?>/.well-known/mcp/server-card.json): MCP server configuration card.

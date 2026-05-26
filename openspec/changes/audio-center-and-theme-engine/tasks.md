## 1. Database & Schema Migration

- [x] 1.1 Add `is_audio` column to the `images` table inside [config/schema.php](file:///Users/david/Documents/git/tbdavid2019/888box/config/schema.php) in both `getCoreTableSql` and `getCoreImageColumns` functions.
- [x] 1.2 Implement `getAudioAssetConditionSql` and update `backfillAssetFlags` inside [config/schema.php](file:///Users/david/Documents/git/tbdavid2019/888box/config/schema.php) to categorize audio formats.
- [x] 1.3 Add default configurations `active_theme` (middle_east_dart) and `max_audio_size` (100) inside [config/schema.php](file:///Users/david/Documents/git/tbdavid2019/888box/config/schema.php) default values.

## 2. Styling Registry & Theme Engine

- [x] 2.1 Create theme configuration registry [config/themes.php](file:///Users/david/Documents/git/tbdavid2019/888box/config/themes.php) containing the Tokyo Night and Middle East Dart presets.
- [x] 2.2 Create [config/theme_helper.php](file:///Users/david/Documents/git/tbdavid2019/888box/config/theme_helper.php) implementing `renderThemeStyles` to inject styles dynamically.
- [x] 2.3 Add dynamic stylesheet injections to portal [index.php](file:///Users/david/Documents/git/tbdavid2019/888box/index.php) and view [view.php](file:///Users/david/Documents/git/tbdavid2019/888box/view.php).
- [x] 2.4 Add stylesheet injections to upload centers (`upload_image.php`, `upload_video.php`, `upload_file.php`) and backoffice dashboard pages.
- [x] 2.5 Update system configuration modal in [admin/settings.php](file:///Users/david/Documents/git/tbdavid2019/888box/admin/settings.php) to support theme selection.

## 3. Core API Upload & Operations

- [x] 3.1 Map audio formats (`mp3`, `wav`, etc.) to respective MIME types inside [config/upload.php](file:///Users/david/Documents/git/tbdavid2019/888box/config/upload.php).
- [x] 3.2 Add `handleAudioUpload`, `rebuildAudioRSS`, and `updateDailyAudioList` inside new helper file [config/audio_logic.php](file:///Users/david/Documents/git/tbdavid2019/888box/config/audio_logic.php).
- [x] 3.3 Create standalone audio upload gateway [audio.php](file:///Users/david/Documents/git/tbdavid2019/888box/audio.php) to handle endpoint POST queries.
- [x] 3.4 Update upload endpoint selection in [api.php](file:///Users/david/Documents/git/tbdavid2019/888box/api.php) to route audio uploads to the audio logic handler.
- [x] 3.5 Update API list and search actions in [api.php](file:///Users/david/Documents/git/tbdavid2019/888box/api.php) to filter audio files and exclude them from general documents.
- [x] 3.6 Update API statistics action in [api.php](file:///Users/david/Documents/git/tbdavid2019/888box/api.php) to calculate and return audio asset counts.

## 4. Frontend & User Interface

- [x] 4.1 Update Bento Grid inside portal [index.php](file:///Users/david/Documents/git/tbdavid2019/888box/index.php) to introduce the 4th bento card "聲音大廳" to form a balanced 2x2 layout.
- [x] 4.2 Create audio upload center layout [upload_audio.php](file:///Users/david/Documents/git/tbdavid2019/888box/upload_audio.php).
- [x] 4.3 Implement `static/js/audio_app.js` to manage the audio upload queue and history statistics.
- [x] 4.4 Update [view.php](file:///Users/david/Documents/git/tbdavid2019/888box/view.php) to support previewing audio files with controls and a revolving disc micro-animation.

## 5. Administrative Dashboard

- [x] 5.1 Create administrative board [admin/audio.php](file:///Users/david/Documents/git/tbdavid2019/888box/admin/audio.php) to list and preview audio assets.
- [x] 5.2 Create audio-specific administration endpoints [api_edit_audio.php](file:///Users/david/Documents/git/tbdavid2019/888box/api_edit_audio.php) and [api_delete_audio.php](file:///Users/david/Documents/git/tbdavid2019/888box/api_delete_audio.php).
- [x] 5.3 Update existing image and file admin panels ([admin/index.php](file:///Users/david/Documents/git/tbdavid2019/888box/admin/index.php), [admin/file.php](file:///Users/david/Documents/git/tbdavid2019/888box/admin/file.php)) to filter out audio assets.
- [x] 5.4 Update [api_rebuild_podcast.php](file:///Users/david/Documents/git/tbdavid2019/888box/api_rebuild_podcast.php) to support the `type` parameter to rebuild audio feeds.

## 6. External Integrations (MCP / Skill)

- [x] 6.1 Update AI capability markdown document [skill.php](file:///Users/david/Documents/git/tbdavid2019/888box/skill.php) to outline audio-specific actions.
- [x] 6.2 Update MCP gateway [mcp.php](file:///Users/david/Documents/git/tbdavid2019/888box/mcp.php) JSON-RPC schemas and tools.

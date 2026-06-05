<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="3.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" xmlns:content="http://purl.org/rss/1.0/modules/content/">
  <xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/>
  <xsl:template match="/">
    <html xmlns="http://www.w3.org/1999/xhtml">
      <head>
        <title><xsl:value-of select="/rss/channel/title"/> - Podcast RSS Feed</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1"/>
        <style type="text/css">
          @font-face { font-family: "888box JetBrains Mono"; src: url("/static/fonts/JetBrainsMono-Medium.woff2") format("woff2"); font-weight: 500; font-style: normal; font-display: swap; }
          @font-face { font-family: "888box Maple Mono"; src: url("/static/fonts/MapleMonoNormal-Medium.woff2") format("woff2"); font-weight: 500; font-style: normal; font-display: swap; }
          body { font-family: "888box JetBrains Mono", "888box Maple Mono", ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace; background-color: #121212; color: #f3f4f6; margin: 0; padding: 20px; line-height: 1.6; }
          .container { max-width: 800px; margin: 0 auto; }
          .header { background: linear-gradient(135deg, #3b82f6, #8b5cf6); padding: 30px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 10px 20px rgba(0,0,0,0.3); }
          .header h1 { margin: 0 0 10px 0; color: #fff; font-size: 2.2rem; }
          .header p { margin: 0; color: #e5e7eb; font-size: 1.1rem; }
          .notice { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); padding: 15px; border-radius: 8px; margin-bottom: 30px; color: #34d399; font-weight: bold; text-align: center; }
          .item { background: #1e1e1e; border: 1px solid #374151; border-radius: 12px; padding: 25px; margin-bottom: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.2); }
          .item h2 { margin: 0 0 10px 0; color: #60a5fa; font-size: 1.5rem; }
          .item .date { font-size: 0.9rem; color: #9ca3af; margin-bottom: 15px; }
          .item .desc { color: #d1d5db; margin-bottom: 20px; white-space: pre-wrap; }
          .item .media { background: #000; padding: 10px; border-radius: 8px; text-align: center; }
          .item video { max-width: 100%; border-radius: 6px; }
          .item .meta { display: flex; gap: 20px; margin-top: 15px; font-size: 0.9rem; color: #9ca3af; }
          a { color: #60a5fa; text-decoration: none; }
          a:hover { text-decoration: underline; }
        </style>
      </head>
      <body>
        <div class="container">
          <div class="notice">
            🚀 這是一個標準的 Podcast RSS Feed 檔案。<br/>你可以複製當前網址，並貼入 Apple Podcasts, Spotify 或其他播放器中訂閱此頻道！
          </div>
          
          <div class="header">
            <h1><xsl:value-of select="/rss/channel/title"/></h1>
            <p><xsl:value-of select="/rss/channel/description"/></p>
            <p style="margin-top: 15px; font-size: 0.9rem;">
              <a href="{/rss/channel/link}" style="color: #fff; text-decoration: underline;">返回主站點</a>
            </p>
          </div>

          <xsl:for-each select="/rss/channel/item">
            <div class="item">
              <h2><xsl:value-of select="title"/></h2>
              <div class="date"><xsl:value-of select="pubDate"/></div>
              <div class="desc"><xsl:value-of select="description"/></div>
              
              <div class="media">
                <video controls="controls" preload="metadata">
                  <xsl:attribute name="src">
                    <xsl:value-of select="enclosure/@url"/>
                  </xsl:attribute>
                </video>
              </div>
              
              <div class="meta">
                <span>檔案大小: <xsl:value-of select="format-number(enclosure/@length div 1048576, '#.00')"/> MB</span>
                <span>格式: <xsl:value-of select="enclosure/@type"/></span>
                <xsl:if test="itunes:duration">
                  <span>時長: <xsl:value-of select="itunes:duration"/> 秒</span>
                </xsl:if>
              </div>
            </div>
          </xsl:for-each>
        </div>
      </body>
    </html>
  </xsl:template>
</xsl:stylesheet>

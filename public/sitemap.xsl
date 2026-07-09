<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:sm="http://www.sitemaps.org/schemas/sitemap/0.9">
<xsl:output method="html" encoding="UTF-8" indent="yes"/>

<xsl:template match="/">
<html lang="vi">
<head>
<meta charset="UTF-8"/>
<title>Sitemap — CacyLinen</title>
<style>
  body { font-family: -apple-system, Segoe UI, Roboto, sans-serif; margin: 2rem; color: #1a1a1a; }
  h1 { font-size: 1.1rem; margin-bottom: 1rem; }
  table { border-collapse: collapse; width: 100%; font-size: 0.85rem; }
  th, td { text-align: left; padding: 0.4rem 0.75rem; border-bottom: 1px solid #e5e5e5; }
  th { background: #f5f5f5; }
  a { color: #0a5cff; text-decoration: none; }
  a:hover { text-decoration: underline; }
  .count { color: #666; font-size: 0.8rem; margin-bottom: 1rem; }
</style>
</head>
<body>
  <xsl:choose>
    <xsl:when test="sm:sitemapindex">
      <h1>Sitemap Index</h1>
      <p class="count"><xsl:value-of select="count(sm:sitemapindex/sm:sitemap)"/> child sitemap(s)</p>
      <table>
        <tr><th>Sitemap</th><th>Last modified</th></tr>
        <xsl:for-each select="sm:sitemapindex/sm:sitemap">
          <tr>
            <td><a href="{sm:loc}"><xsl:value-of select="sm:loc"/></a></td>
            <td><xsl:value-of select="sm:lastmod"/></td>
          </tr>
        </xsl:for-each>
      </table>
    </xsl:when>
    <xsl:otherwise>
      <h1>Sitemap URLs</h1>
      <p class="count"><xsl:value-of select="count(sm:urlset/sm:url)"/> URL(s)</p>
      <table>
        <tr><th>URL</th><th>Last modified</th><th>Change freq</th><th>Priority</th></tr>
        <xsl:for-each select="sm:urlset/sm:url">
          <tr>
            <td><a href="{sm:loc}"><xsl:value-of select="sm:loc"/></a></td>
            <td><xsl:value-of select="sm:lastmod"/></td>
            <td><xsl:value-of select="sm:changefreq"/></td>
            <td><xsl:value-of select="sm:priority"/></td>
          </tr>
        </xsl:for-each>
      </table>
    </xsl:otherwise>
  </xsl:choose>
</body>
</html>
</xsl:template>

</xsl:stylesheet>

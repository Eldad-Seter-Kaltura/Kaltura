<?xml version="1.0"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output method="xml" encoding="utf-8" indent="no"/>
	<xsl:template match="@*|node()">
		<xsl:copy>
			<xsl:apply-templates select="@*|node()"/>
		</xsl:copy>
	</xsl:template>
	<xsl:template match="Pruned"/>
	<xsl:template match="metadata">
		<xsl:copy>
			<xsl:apply-templates select="@*|node()"/>
			<Pruned>true</Pruned>
		</xsl:copy>
	</xsl:template>
</xsl:stylesheet>

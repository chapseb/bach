<?xml version="1.0" encoding="UTF-8"?>
<!--

Displays an EAD fragment as RDF (POC)

Copyright (c) 2014, Anaphore
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are
met:

    (1) Redistributions of source code must retain the above copyright
    notice, this list of conditions and the following disclaimer.

    (2) Redistributions in binary form must reproduce the above copyright
    notice, this list of conditions and the following disclaimer in
    the documentation and/or other materials provided with the
    distribution.

    (3)The name of the author may not be used to
   endorse or promote products derived from this software without
   specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING
IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
POSSIBILITY OF SUCH DAMAGE.

@author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
@license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
@link     http://anaphore.eu
-->
<xsl:stylesheet
    version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:php="http://php.net/xsl"
    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:archives="http://anaphore.eu/archives"
    exclude-result-prefixes="php">

    <!--xsl:output method="xml"/-->
    <xsl:output method="xml" omit-xml-declaration="no" indent="yes"/>

    <xsl:param name="parents" select="''"/>
    <xsl:param name="children" select="''"/>

    <xsl:template match="c|c01|c02|c03|c04|c05|c06|c07|c08|c09|c10|c11|c12|archdesc">
        <!--xsl:copy-of select="/"/-->
        <xsl:variable name="id">
            <xsl:choose>
                <xsl:when test="@id">
                    <xsl:value-of select="@id"/>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:value-of select="generate-id(.)"/>
                </xsl:otherwise>
            </xsl:choose>
        </xsl:variable>

        <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:archives="http://anaphore.eu/archives">
            <rdf:Description rdf:about="http://bach.anaphore.org/{$id}">
                <dc:title>
                    <xsl:apply-templates select="did/unittitle"/>
                </dc:title>
                <xsl:if test="did/unitdate">
                    <dc:date>
                        <xsl:value-of select="did/unitdate"/>
                    </dc:date>
                </xsl:if>
                <xsl:if test="//parents">
                    <xsl:apply-templates select="//parents/related"/>
                </xsl:if>
                <xsl:if test="//children">
                    <xsl:apply-templates select="//children/related"/>
                </xsl:if>
            </rdf:Description>
        </rdf:RDF>
    </xsl:template>

    <xsl:template match="related">
        <archives:related rdf:resource="http://bach.anaphore.org/{.}"/>
    </xsl:template>
</xsl:stylesheet>

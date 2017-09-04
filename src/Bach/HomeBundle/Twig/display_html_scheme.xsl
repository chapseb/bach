<?xml version="1.0" encoding="UTF-8"?>
<!--

Displays an EAD document as HTML

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
@author   Sébastien Chaptal <sebastien.chaptal@anaphore.eu>
@license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
@link     http://anaphore.eu
-->
<xsl:stylesheet
    version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:php="http://php.net/xsl"
    exclude-result-prefixes="php">

    <xsl:output method="html" omit-xml-declaration="yes"/>
    <xsl:param name="docid" select="''"/>
    <xsl:param name="audience" select="''"/>

    <!-- ***** CONTENTS ***** -->
    <xsl:template match="c|c01|c02|c03|c04|c05|c06|c07|c08|c09|c10|c11|c12">
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
        <xsl:variable name="type">
            <xsl:value-of select="./did/unittitle/@type"/>
        </xsl:variable>

        <xsl:if test="not(@audience = 'internal') or $audience != 'false'">
            <xsl:choose>
                <xsl:when test="count(parent::c) = 0">
                    <h3>
                        <xsl:if test="$type='titre' and (count(child::c/c) = 0 or count(child::c/did/unittitle/@type)=0)">
                            <xsl:attribute name="class">standalone</xsl:attribute>
                        </xsl:if>
                        <xsl:apply-templates select="did">
                            <xsl:with-param name="fragid"><xsl:value-of select="$id"/></xsl:with-param>
                        </xsl:apply-templates>
                    </h3>
                    <div>
                        <xsl:if test="$type='titre' and count(child::c/c) &gt; 0 and count(child::c/did/unittitle/@type)!=0">
                            <ul>
                            <xsl:apply-templates select="./c|./c01|./c02|./c03|./c04|./c05|./c06|./c07|./c08|./c09|./c10|./c11|./c12"/>
                            </ul>
                        </xsl:if>
                    </div>
                </xsl:when>
                <xsl:otherwise>
                    <li>
                        <xsl:choose>
                            <xsl:when test="count(child::c/c) &gt; 0 and $type='titre' and count(child::c/did/unittitle/@type)!=0">
                                <xsl:attribute name="class">accordion</xsl:attribute>
                                <h3>
                                    <xsl:apply-templates select="did">
                                        <xsl:with-param name="fragid"><xsl:value-of select="$id"/></xsl:with-param>
                                    </xsl:apply-templates>
                                </h3>
                                <div>
                                    <xsl:if test="count(child::c/c) &gt; 0 and $type='titre' and count(child::c/did/unittitle/@type)!=0">
                                        <ul>
                                            <xsl:apply-templates select="./c|./c01|./c02|./c03|./c04|./c05|./c06|./c07|./c08|./c09|./c10|./c11|./c12"/>
                                        </ul>
                                    </xsl:if>
                                </div>
                            </xsl:when>
                            <xsl:otherwise>
                                <xsl:if test="$type='titre'">
                                <xsl:apply-templates select="did">
                                    <xsl:with-param name="fragid"><xsl:value-of select="$id"/></xsl:with-param>
                                </xsl:apply-templates>
                            </xsl:if>
                            </xsl:otherwise>
                        </xsl:choose>
                    </li>
                </xsl:otherwise>
            </xsl:choose>
        </xsl:if>
    </xsl:template>

    <xsl:template match="did">
        <xsl:param name="fragid"/>
        <xsl:if test="not(unittitle)">
            <a href="#{$fragid}">
                <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayEADFragment::i18nFromXsl', 'Untitled unit')"/>
            </a>
        </xsl:if>
        <xsl:apply-templates>
            <xsl:with-param name="fragid"><xsl:value-of select="$fragid"/></xsl:with-param>
        </xsl:apply-templates>
    </xsl:template>

    <xsl:template match="unittitle">
        <xsl:param name="fragid"/>
        <a href="#{$fragid}">
            <xsl:apply-templates />
        </a>
    </xsl:template>
    <!-- ***** END CONTENTS ***** -->

    <!-- ***** GENERIC TAGS ***** -->
    <xsl:template match="emph">
        <xsl:choose>
            <xsl:when test="@render='bold'">
                <strong>
                    <xsl:apply-templates/>
                </strong>
            </xsl:when>
            <xsl:when test="@render='italic'">
                <em>
                    <xsl:apply-templates/>
                </em>
            </xsl:when>
            <xsl:otherwise>
                <xsl:apply-templates/>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="text()">
        <xsl:copy-of select="."/>
        <!--xsl:copy-of select="normalize-space(.)"/-->
    </xsl:template>
    <!-- ***** END GENERIC TAGS ***** -->

    <!-- Per default, display nothing -->
    <xsl:template match="*|@*|node()"/>
    <xsl:template match="*|@*|node()" mode="header"/>

</xsl:stylesheet>

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

        <li id="{$id}">
            <xsl:choose>
                <xsl:when test="count(child::c) &gt; 0">
                    <input type="checkbox" id="item-{$id}" checked="checked" />
                    <label for="item-{$id}"><xsl:apply-templates select="did"/></label>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:attribute name="class">standalone</xsl:attribute>
                    <xsl:apply-templates select="did"/>
                </xsl:otherwise>
            </xsl:choose>
            <xsl:if test="count(child::c) &gt; 0">
                <ul>
                    <xsl:apply-templates select="./c|./c01|./c02|./c03|./c04|./c05|./c06|./c07|./c08|./c09|./c10|./c11|./c12"/>
                </ul>
            </xsl:if>
        </li>
    </xsl:template>

    <xsl:template match="did">
        <xsl:if test="not(unittitle)">
            <h2 property="dc:title"><xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayEADFragment::i18nFromXsl', 'Untitled unit')"/></h2>
        </xsl:if>
        <xsl:apply-templates/>
    </xsl:template>

    <xsl:template match="unittitle">
        <a class="display_doc">
            <!-- URL cannot ben generated from here. Let's build a specific value to be replaced -->
            <xsl:attribute name="link">
                <xsl:choose>
                    <xsl:when test="not(ancestor::c[1])">
                        <xsl:value-of select="concat('%%%', $docid, '_description%%%')"/>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:value-of select="concat('%%%', $docid, '_', ancestor::c[1]/@id, '%%%')"/>
                    </xsl:otherwise>
                </xsl:choose>
            </xsl:attribute>

            <strong property="dc:title">
                <xsl:apply-templates />
            </strong>

            <xsl:variable name="titre" select="." />
            <xsl:if test="../unitdate and not(./unitdate) and not(../unitdate = $titre)">
                <span class="date" property="dc:date">
                    <strong><xsl:value-of select="concat(' • ', ../unitdate)"/></strong>
                </span>
            </xsl:if>

            <xsl:if test="../unitid[not(@type='ordre_c')]">
                <xsl:text> - </xsl:text>
                <span class="unitid" property="dc:identifier">
                    <xsl:value-of select="../unitid[not(@type='ordre_c')]"/>
                </span>
            </xsl:if>
             <xsl:if test="../../dao or ../../daogrp or ../../daoloc">
                <span class="media_informations well"></span>
            </xsl:if>
        </a>
    </xsl:template>
    <!-- ***** END CONTENTS ***** -->

    <!-- ***** GENERIC TAGS ***** -->
    <xsl:template match="date|language">
        <xsl:value-of select="' '"/>
        <xsl:apply-templates/>
        <xsl:value-of select="' '"/>
    </xsl:template>

    <xsl:template match="titleproper|author|sponsor|addressline|subtitle">
        <xsl:apply-templates/>
    </xsl:template>

    <xsl:template match="unittitle/unitdate">
        <span class="date" property="dc:date">
            <xsl:value-of select="' '"/>
            <xsl:value-of select="."/>
        </span>
    </xsl:template>

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
        <xsl:copy-of select="normalize-space(.)"/>
    </xsl:template>

    <xsl:template match="lb">
        <xsl:if test="not(preceding-sibling::lb)">
            <br/>
        </xsl:if>
    </xsl:template>
    <!-- ***** END GENERIC TAGS ***** -->

    <!-- Per default, display nothing -->
    <xsl:template match="*|@*|node()"/>
    <xsl:template match="*|@*|node()" mode="header"/>

</xsl:stylesheet>

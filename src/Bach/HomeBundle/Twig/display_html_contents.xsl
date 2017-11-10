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
    <xsl:param name="audience" select="''"/>
    <xsl:param name="readingroom" select="''"/>
    <xsl:param name="current_year" select="''"/>
    <xsl:param name="daodetector" select="''"/>
    <xsl:param name="viewer_uri" select="''"/>

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

        <xsl:if test="not(@audience = 'internal') or $audience != 'false'">
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
        </xsl:if>
    </xsl:template>

    <xsl:template match="did">
        <xsl:if test="not(unittitle)">
            <h2 property="dc:title"><xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayEADFragment::i18nFromXsl', 'Untitled unit')"/></h2>
        </xsl:if>
        <xsl:apply-templates/>
    </xsl:template>

    <xsl:template match="unittitle">
    <xsl:variable name="count" select="count(ancestor::c)"/>
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

            <span class="sizeTitle{$count + 1}">
            <strong property="dc:title">
                <xsl:apply-templates />
            </strong>

            <xsl:variable name="titre" select="." />
            <xsl:if test="../unitdate and not(./unitdate) and not(../unitdate = $titre)">
                <span class="date" property="dc:date">
                    <strong><xsl:value-of select="concat(' • ', ../unitdate)"/></strong>
                </span>
            </xsl:if>
            </span>
            <xsl:value-of select="../otherfindaid"/>

            <xsl:if test="../unitid[not(@type='ordre_c')]">
                <xsl:text> - </xsl:text>
                <span class="unitid" property="dc:identifier">
                    <xsl:value-of select="../unitid[not(@type='ordre_c')]"/>
                </span>
            </xsl:if>
            <xsl:if test="../../dao or ../../daogrp or ../../daoloc">
                <xsl:if test="$audience != 'false' or (../../dao and not(../../dao/@audience = 'internal')) or (../../daogrp and not(../../daogrp/@audience = 'internal')) or (../../daoloc and not(../../daoloc/@audience = 'internal')) ">
                    <xsl:if test="$daodetector = '' or not(contains(../../dao/@href,$daodetector))">

                        <xsl:variable name="testComm" match=".">
                        <xsl:if test="../../dao/@communicability_general">
                            <xsl:call-template name="calc-com">
                                <xsl:with-param name="comm_general" select="number(../../dao/@communicability_general)" />
                                <xsl:with-param name="comm_readingroom" select="number(../../dao/@communicability_sallelecture)" />
                                <xsl:with-param name="current_year" select="number($current_year)" />
                                <xsl:with-param name="equalip" select="$readingroom" />
                            </xsl:call-template>
                        </xsl:if>
                        </xsl:variable>

                        <xsl:variable name="testCommDaoloc" match=".">
                            <xsl:if test="../../daogrp/daoloc/@communicability_general">
                            <xsl:call-template name="calc-com">
                                <xsl:with-param name="comm_general" select="number(../../daogrp/daoloc/@communicability_general)" />
                                <xsl:with-param name="comm_readingroom" select="number(../../daogrp/daoloc/@communicability_sallelecture)" />
                                <xsl:with-param name="current_year" select="number($current_year)" />
                                <xsl:with-param name="equalip" select="$readingroom" />
                            </xsl:call-template>
                        </xsl:if>
                        </xsl:variable>


                        <xsl:variable name="a" select="count(../../dao)"/>
                        <xsl:variable name="b" select="count(../../daogrp)"/>
                        <xsl:choose>
                            <xsl:when test="$a + $b &lt; 2">
                                <xsl:choose>
                                    <xsl:when test="($testComm and $testComm = 'false') or ($testCommDaoloc and $testCommDaoloc = 'false')">
                                    </xsl:when>
                                    <xsl:when test="../../dao and ../../dao/@role = 'image'">
                                        <xsl:variable name="linkalone" select="concat($viewer_uri, 'viewer/', ../../dao/@href)"/>
                                        <xsl:element name="a">
                                            <xsl:attribute name="href">
                                                <xsl:value-of select="$linkalone"/>
                                            </xsl:attribute>
                                            <xsl:attribute name="class">gomedia_informations</xsl:attribute>
                                            <xsl:attribute name="target">_blank</xsl:attribute>
                                        </xsl:element>
                                    </xsl:when>
                                    <xsl:when test="../../dao and ../../dao/@role = 'series'">
                                        <xsl:variable name="linkseries" select="concat($viewer_uri, 'series/', ../../dao/@href)"/>
                                        <xsl:variable name="lastcharacterSeries" select="substring(../../dao/@href, string-length(../../dao/@href), 1)" />
                                        <xsl:choose>
                                            <xsl:when test="not(lastcharacterSeries = '/')">
                                                <xsl:variable name="finalAdd" select="concat($linkseries, '/')"/>
                                                <xsl:element name="a">
                                                    <xsl:attribute name="href">
                                                        <xsl:value-of select="$finalAdd"/>
                                                    </xsl:attribute>
                                                    <xsl:attribute name="class">gomedia_informations</xsl:attribute>
                                                    <xsl:attribute name="target">_blank</xsl:attribute>
                                                </xsl:element>
                                            </xsl:when>
                                            <xsl:otherwise>
                                                <xsl:variable name="finalWithoutAdd" select="concat($viewer_uri, 'series/', ../../dao/@href)"/>
                                                <xsl:element name="a">
                                                    <xsl:attribute name="href">
                                                        <xsl:value-of select="$finalWithoutAdd"/>
                                                    </xsl:attribute>
                                                    <xsl:attribute name="class">gomedia_informations</xsl:attribute>
                                                    <xsl:attribute name="target">_blank</xsl:attribute>
                                                </xsl:element>
                                            </xsl:otherwise>
                                        </xsl:choose>
                                    </xsl:when>
                                    <xsl:when test="../../daogrp and ../../daogrp/@role = 'series' and not(../../daogrp/daoloc[@role='thumbnails'])">
                                        <xsl:variable name="brutbeginlink" select="../../daogrp/daoloc[@role='image:first']/@href"/>
                                        <xsl:variable name="brutendlink" select="../../daogrp/daoloc[@role='image:last']/@href"/>

                                        <xsl:variable name="basename">
                                            <xsl:call-template name="substring-before-last">
                                                <xsl:with-param name="string1" select="$brutbeginlink" />
                                                <xsl:with-param name="string2" select="'/'" />
                                            </xsl:call-template>
                                        </xsl:variable>

                                        <xsl:variable name="beginlink">
                                            <xsl:call-template name="substring-after-last">
                                                <xsl:with-param name="string" select="$brutbeginlink" />
                                                <xsl:with-param name="delimiter" select="'/'" />
                                            </xsl:call-template>
                                        </xsl:variable>
                                        <xsl:variable name="endlink">
                                            <xsl:call-template name="substring-after-last">
                                                <xsl:with-param name="string" select="$brutendlink" />
                                                <xsl:with-param name="delimiter" select="'/'" />
                                            </xsl:call-template>
                                        </xsl:variable>

                                        <xsl:variable name="linkseriesBeginend" select="concat(
                                            $viewer_uri,
                                            'series/',
                                            $basename,
                                            '?s=',
                                            $beginlink,
                                            '&amp;e=',
                                            $endlink
                                            )"/>
                                        <xsl:element name="a">
                                            <xsl:attribute name="href">
                                                <xsl:value-of select="$linkseriesBeginend"/>
                                            </xsl:attribute>
                                            <xsl:attribute name="class">gomedia_informations</xsl:attribute>
                                            <xsl:attribute name="target">_blank</xsl:attribute>
                                        </xsl:element>
                                    </xsl:when>
                                    <xsl:when test="../../daogrp and ../../daogrp/@role = 'series' and ../../daogrp/daoloc[@role='thumbnails']">
                                        <xsl:variable name="linkseries" select="concat($viewer_uri, 'series/', ../../daogrp/daoloc/@href)"/>
                                        <xsl:variable name="lastcharacterSeries" select="substring(../../daogrp/daoloc/@href, string-length(../../daogrp/daoloc/@href), 1)" />
                                        <xsl:choose>
                                            <xsl:when test="not(lastcharacterSeries = '/')">
                                                <xsl:variable name="finalAdd" select="concat($linkseries, '/')"/>
                                                <xsl:element name="a">
                                                    <xsl:attribute name="href">
                                                        <xsl:value-of select="$finalAdd"/>
                                                    </xsl:attribute>
                                                    <xsl:attribute name="class">gomedia_informations</xsl:attribute>
                                                    <xsl:attribute name="target">_blank</xsl:attribute>
                                                </xsl:element>
                                            </xsl:when>
                                            <xsl:otherwise>
                                                <xsl:variable name="finalWithoutAdd" select="concat($viewer_uri, 'series/', ../../daogrp/daoloc/@href)"/>
                                                <xsl:element name="a">
                                                    <xsl:attribute name="href">
                                                        <xsl:value-of select="$finalWithoutAdd"/>
                                                    </xsl:attribute>
                                                    <xsl:attribute name="class">gomedia_informations</xsl:attribute>
                                                    <xsl:attribute name="target">_blank</xsl:attribute>
                                                </xsl:element>
                                            </xsl:otherwise>
                                        </xsl:choose>
                                    </xsl:when>
                                    <xsl:otherwise>
                                        <span class="media_informations"></span>
                                    </xsl:otherwise>
                                </xsl:choose>
                            </xsl:when>
                            <xsl:otherwise>
                                <span class="media_informations"></span>
                            </xsl:otherwise>
                        </xsl:choose>
                    </xsl:if>
                </xsl:if>
            </xsl:if>
            <xsl:if test="../../otherfindaid/list/item/archref or ../../relatedmaterial/list/item/extref">
                <xsl:choose>
                    <xsl:when test="count(../../otherfindaid/list/item/archref) = 1 and contains(../../otherfindaid/list/item/archref/@href, '.xml')">
                        <xsl:element name="a">
                            <xsl:attribute name="href">
                                <xsl:variable name="varhref" select="../../otherfindaid/list/item/archref/@href"/>
                                <xsl:call-template name="replace-string">
                                    <xsl:with-param name="text" select="$varhref"/>
                                    <xsl:with-param name="replace" select="'.xml'" />
                                    <xsl:with-param name="with" select="''"/>
                                </xsl:call-template>
                            </xsl:attribute>
                            <xsl:attribute name="class">related_informations</xsl:attribute>
                            <xsl:attribute name="title">
                                <xsl:value-of select="../../otherfindaid/list/item/archref/@title"/>
                            </xsl:attribute>
                            <xsl:attribute name="target">_blank</xsl:attribute>
                        </xsl:element>
                    </xsl:when>
                    <xsl:otherwise>
                        <span class="related_informations"></span>
                    </xsl:otherwise>
                </xsl:choose>
            </xsl:if>

        </a>
    </xsl:template>
    <!-- ***** END CONTENTS ***** -->



    <xsl:template name="replace-string">
        <xsl:param name="text"/>
        <xsl:param name="replace"/>
        <xsl:param name="with"/>
        <xsl:choose>
        <xsl:when test="contains($text,$replace)">
            <xsl:value-of select="substring-before($text,$replace)"/>
            <xsl:value-of select="$with"/>
            <xsl:call-template name="replace-string">
            <xsl:with-param name="text"
    select="substring-after($text,$replace)"/>
            <xsl:with-param name="replace" select="$replace"/>
            <xsl:with-param name="with" select="$with"/>
            </xsl:call-template>
        </xsl:when>
        <xsl:otherwise>
            <xsl:value-of select="$text"/>
        </xsl:otherwise>
        </xsl:choose>
    </xsl:template>



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
        <xsl:copy-of select="."/>
        <!--xsl:copy-of select="normalize-space(.)"/-->
    </xsl:template>

    <xsl:template match="lb">
        <xsl:if test="not(preceding-sibling::lb)">
            <br/>
        </xsl:if>
    </xsl:template>
    <!-- ***** END GENERIC TAGS ***** -->

    <xsl:template name="substring-after-last">
        <xsl:param name="string" />
        <xsl:param name="delimiter" />
        <xsl:choose>
            <xsl:when test="contains($string, $delimiter)">
                <xsl:call-template name="substring-after-last">
                    <xsl:with-param name="string"
                        select="substring-after($string, $delimiter)" />
                    <xsl:with-param name="delimiter" select="$delimiter" />
                </xsl:call-template>
            </xsl:when>
            <xsl:otherwise>
                <xsl:value-of select="$string" />
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template name="substring-before-last">
        <xsl:param name="string1" select="''" />
        <xsl:param name="string2" select="''" />

        <xsl:if test="$string1 != '' and $string2 != ''">
            <xsl:variable name="head" select="substring-before($string1, $string2)" />
            <xsl:variable name="tail" select="substring-after($string1, $string2)" />
            <xsl:value-of select="$head" />
            <xsl:if test="contains($tail, $string2)">
                <xsl:value-of select="$string2" />
                <xsl:call-template name="substring-before-last">
                    <xsl:with-param name="string1" select="$tail" />
                    <xsl:with-param name="string2" select="$string2" />
                </xsl:call-template>
            </xsl:if>
        </xsl:if>
    </xsl:template>

    <xsl:template name="calc-com">
        <xsl:param name="comm_general"/>
        <xsl:param name="comm_readingroom"/>
        <xsl:param name="current_year"/>
        <xsl:param name="equalip"/>
        <xsl:choose>
            <xsl:when test="$comm_general &lt; $current_year">
                <xsl:value-of select="'true'"/>
            </xsl:when>
            <xsl:when test="$equalip = 'true' and $comm_readingroom &lt; $current_year">
                <xsl:value-of select="'true'"/>
            </xsl:when>
            <xsl:otherwise>
                <xsl:value-of select="'false'"/>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <!-- Per default, display nothing -->
    <xsl:template match="*|@*|node()"/>
    <xsl:template match="*|@*|node()" mode="header"/>

</xsl:stylesheet>

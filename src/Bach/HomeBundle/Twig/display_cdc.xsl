<?xml version="1.0" encoding="UTF-8"?>
<!--

Displays an classification scheme as HTML

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

    <xsl:template match="ead">
        <xsl:apply-templates/>
    </xsl:template>

    <xsl:template match="archdesc">
        <xsl:apply-templates select="dsc"/>
    </xsl:template>

    <xsl:template match="dsc">
        <ul>
            <xsl:apply-templates select="./c|./c01|./c02|./c03|./c04|./c05|./c06|./c07|./c08|./c09|./c10|./c11|./c12" mode="lvl"/>
            <xsl:if test="count(//not_matched/*) &gt; 0">
                <li id="not-classified">
                    <input type="checkbox" id="item-nc" checked="checked"/>
                    <label for="item-nc">
                        <strong><xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayCdc::i18nFromXsl', 'Not classified')"/></strong>
                    </label>
                    <ul>
                        <xsl:for-each select="//not_matched/*">
                            <li>
                                <a link="{concat('%%%', local-name(), '%%%')}"><xsl:value-of select="."/></a>
                            </li>
                        </xsl:for-each>
                    </ul>
                </li>
            </xsl:if>
        </ul>
    </xsl:template>

    <xsl:template match="c|c01|c02|c03|c04|c05|c06|c07|c08|c09|c10|c11|c12" mode="lvl">
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
        <xsl:variable name="hasDocs" select="php:function('Bach\HomeBundle\Twig\DisplayCdc::hasPublished', ., //dadocs)"/>

        <li id="{$id}">
            <xsl:choose>
                <xsl:when test="count(child::c) &gt; 0 or $hasDocs">
                    <input type="checkbox" id="item-{$id}" checked="checked"/>
                    <label for="item-{$id}"><xsl:apply-templates select="did"/></label>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:attribute name="class">standalone</xsl:attribute>
                    <xsl:apply-templates select="did"/>
                </xsl:otherwise>
            </xsl:choose>
            <xsl:apply-templates select="*[not(local-name() = 'did')]"/>
            <xsl:if test="bioghist|controlaccess">
                <section class="extended_informations well">
                    <xsl:apply-templates mode="extends"/>
                </section>
            </xsl:if>
            <xsl:if test="count(child::c) &gt; 0">
                <ul>
                    <xsl:apply-templates select="./c|./c01|./c02|./c03|./c04|./c05|./c06|./c07|./c08|./c09|./c10|./c11|./c12" mode="lvl"/>
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
        <strong property="dc:title"><xsl:apply-templates/></strong>

        <xsl:variable name="titre" select="." />
        <xsl:if test="../unitdate and not(./unitdate) and not(../unitdate = $titre)">
            <span class="date" property="dc:date">
                <strong><xsl:value-of select="concat(' • ', ../unitdate)"/></strong>
            </span>
        </xsl:if>

        <xsl:if test="../unitid">
            <xsl:text> - </xsl:text>
            <span class="unitid" property="dc:identifier">
                <xsl:value-of select="../unitid"/>
            </span>
        </xsl:if>
    </xsl:template>

    <xsl:template match="unittitle/unitdate">
        <span class="date" property="dc:date">
            <xsl:value-of select="."/>
        </span>
    </xsl:template>

    <xsl:template match="otherfindaid">
        <xsl:apply-templates/>
    </xsl:template>

    <xsl:template match="head" mode="extends">
        <h5><xsl:value-of select="."/></h5>
    </xsl:template>

    <xsl:template match="table|thead|tbody">
        <xsl:element name="{local-name()}">
            <xsl:apply-templates/>
        </xsl:element>
    </xsl:template>

    <xsl:template match="tgroup">
        <xsl:apply-templates/>
    </xsl:template>

    <xsl:template match="row">
        <xsl:variable name="parent-name" select="local-name(parent::node())"/>
        <tr>
            <xsl:apply-templates>
                <xsl:with-param name="parent-name" select="$parent-name"/>
            </xsl:apply-templates>
        </tr>
    </xsl:template>

    <xsl:template match="entry">
        <xsl:param name="parent-name"/>
        <xsl:choose>
            <xsl:when test="$parent-name = 'thead'">
                <th>
                    <xsl:apply-templates/>
                </th>
            </xsl:when>
            <xsl:otherwise>
                <td>
                    <xsl:apply-templates/>
                </td>
            </xsl:otherwise>
        </xsl:choose>
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

    <xsl:template match="list" mode="extends">
        <ul>
            <xsl:apply-templates/>
        </ul>
    </xsl:template>

    <xsl:template match="list">
        <xsl:variable name="proceed" select="php:function('Bach\HomeBundle\Twig\DisplayCdc::hasPublished', ., //dadocs)"/>
        <!--<xsl:if test="$proceed">-->
            <ul>
                <xsl:apply-templates/>
            </ul>
        <!--</xsl:if>-->
    </xsl:template>

    <xsl:template match="defitem|change">
        <dl>
            <xsl:apply-templates/>
        </dl>
    </xsl:template>

    <xsl:template match="label">
        <xsl:variable name="parent-name" select="local-name(parent::node())"/>
        <xsl:choose>
            <xsl:when test="$parent-name = 'defitem'">
                <dt>
                    <xsl:apply-templates/>
                </dt>
            </xsl:when>
            <xsl:otherwise>
                <xsl:apply-templates/>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="date">
        <xsl:variable name="parent-name" select="local-name(parent::node())"/>
        <xsl:choose>
            <xsl:when test="$parent-name = 'change'">
                <dt>
                    <xsl:apply-templates/>
                </dt>
            </xsl:when>
            <xsl:otherwise>
                <xsl:apply-templates/>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="item">
        <xsl:variable name="parent-name" select="local-name(parent::node())"/>
        <xsl:choose>
            <xsl:when test="$parent-name = 'list'">
                <li class="standalone">
                    <xsl:apply-templates/>
                </li>
            </xsl:when>
            <xsl:when test="$parent-name = 'defitem' or $parent-name = 'change'">
                <dd>
                    <xsl:apply-templates/>
                </dd>
            </xsl:when>
            <xsl:otherwise>
                <xsl:apply-templates/>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="extref|archref">
        <xsl:variable name="docid" select="substring-before(@href, '.xml')"/>

        <xsl:choose>
            <xsl:when test="$docid = ''">
                <!-- Not an xml link -->
                <xsl:copy-of select="php:function('Bach\HomeBundle\Twig\DisplayDao::getDao', string(@href), string(@title), '')"/>
            </xsl:when>
            <xsl:when test="//dadocs/*[local-name() = $docid] or not($docid = '')">
                <a link="{concat('%%%', string($docid), '%%%')}">
                    <xsl:if test="@title and . != ''">
                        <xsl:attribute name="title">
                            <xsl:value-of select="@title"/>
                        </xsl:attribute>
                    </xsl:if>
                    <xsl:choose>
                        <xsl:when test="@title and . = ''">
                            <xsl:value-of select="@title"/>
                        </xsl:when>
                        <xsl:otherwise>
                            <xsl:value-of select="//dadocs/*[local-name() = $docid]"/>
                            </xsl:otherwise>
                    </xsl:choose>
                </a>
            </xsl:when>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="bioghist" mode="extends">
        <div class="contents">
            <xsl:apply-templates/>
        </div>
    </xsl:template>

    <xsl:key name="indexing" match="subject|geogname|persname|corpname|name|function|genreform" use="concat(generate-id(..), '_', local-name())"/>
    <xsl:template match="controlaccess" mode="extends">
        <div class="contents">
            <xsl:apply-templates/>
            <xsl:for-each select="*[generate-id() = generate-id(key('indexing', concat(generate-id(..), '_', local-name()))[1])]">
                <xsl:sort select="local-name()" data-type="text"/>
                <xsl:variable name="elt" select="local-name()"/>
                <div>
                    <strong>
                        <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayEADFragment::i18nFromXsl', concat($elt, ':'))"/>
                        <xsl:text> </xsl:text>
                    </strong>
                    <xsl:for-each select="../*[local-name() = $elt]">
                        <!-- URL cannot ben generated from here. Let's build a specific value to be replaced -->
                        <a link="{concat('%%%', $elt, '::', string(.), '%%%')}">
                            <xsl:if test="not(local-name() = 'function')">
                                <xsl:attribute name="property">
                                    <xsl:choose>
                                        <xsl:when test="local-name() = 'subject'">dc:subject</xsl:when>
                                        <xsl:when test="local-name() = 'geogname'">gn:name</xsl:when>
                                        <xsl:otherwise>foaf:name</xsl:otherwise>
                                    </xsl:choose>
                                </xsl:attribute>
                                <xsl:attribute name="content">
                                    <xsl:value-of select="."/>
                                </xsl:attribute>
                            </xsl:if>
                            <xsl:value-of select="."/>
                        </a>
                        <xsl:if test="following-sibling::*[local-name() = $elt]">
                            <xsl:text>, </xsl:text>
                        </xsl:if>
                    </xsl:for-each>
                </div>
            </xsl:for-each>
        </div>
    </xsl:template>

    <xsl:template match="blockquote">
        <blockquote>
            <xsl:apply-templates/>
        </blockquote>
    </xsl:template>

    <xsl:template match="p">
        <p>
            <xsl:if test="@altrender">
                <xsl:attribute name="class">
                    <xsl:value-of select="@altrender"/>
                </xsl:attribute>
            </xsl:if>
            <xsl:apply-templates/>
        </p>
    </xsl:template>

    <xsl:template match="text()">
        <xsl:copy-of select="translate(., '&#xA;&#xD;', '  ')"/>
    </xsl:template>

    <xsl:template match="lb">
        <xsl:if test="not(preceding-sibling::lb)">
            <br/>
        </xsl:if>
    </xsl:template>

    <!-- Per default, display nothing -->
    <xsl:template match="*|@*|node()"/>
    <xsl:template match="*|@*|node()" mode="extends"/>
    <xsl:template match="*|@*|node()" mode="lvl"/>

</xsl:stylesheet>

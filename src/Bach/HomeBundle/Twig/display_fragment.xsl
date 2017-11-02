<?xml version="1.0" encoding="UTF-8"?>
<!--

Displays an EAD fragment as HTML

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

    <xsl:param name="full" select="1"/>
    <xsl:param name="ajax" select="''"/>
    <xsl:param name="children" select="''"/>
    <xsl:param name="comments_enabled" select="''"/>
    <xsl:param name="comments" select="''"/>
    <xsl:param name="viewer_uri" select="''"/>
    <xsl:param name="covers_dir" select="''"/>
    <xsl:param name="count_subs" select="''"/>
    <xsl:param name="cdc" select="'false'"/>
    <xsl:param name="docid"/>
    <xsl:param name="cote_location" select="''"/>
    <xsl:param name="print" select="''"/>
    <xsl:param name="communicability" select="''"/>
    <xsl:param name="audience" select="''"/>
    <xsl:param name="aws" select="''"/>
    <xsl:param name="cloudfront" select="''"/>
    <xsl:param name="query" select="''"/>

    <xsl:template match="c|c01|c02|c03|c04|c05|c06|c07|c08|c09|c10|c11|c12|archdesc">
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

        <xsl:choose>
            <xsl:when test="$full = 1">
                <xsl:if test="$cdc = 'false' and $print = 'false'">
                    <ul>
                        <xsl:if test="count(./*[not(local-name() = 'did')]) + count(./did/*[not(local-name() = 'unittitle')]) &gt; 0">
                            <li><a href="#{$id}"><xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayEADFragment::i18nFromXsl', 'Description')"/></a></li>
                        </xsl:if>
                        <xsl:if test=".//dao|.//daoloc and $print= 'false' and $audience != 'false'">
                            <li><a href="#relative_documents"><xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayEADFragment::i18nFromXsl', 'Documents')"/></a></li>
                        </xsl:if>
                        <xsl:if test="not($children = '')">
                            <li><a href="#children_documents"><xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayEADFragment::i18nFromXsl', 'Sub-units')"/> (<xsl:value-of select="$count_subs"/>)</a></li>
                        </xsl:if>
                        <xsl:if test="$comments_enabled = 'true'">
                            <xsl:if test="not($comments = '')">
                                <li><a href="#comments"><xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayEADFragment::i18nFromXsl', 'Comments')"/></a></li>
                            </xsl:if>
                            <li><a href="__path_add_comment__"><xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayEADFragment::i18nFromXsl', 'Add comment')"/></a></li>
                        </xsl:if>
                    </ul>
                </xsl:if>

                <xsl:if test="count(./*[not(local-name() = 'did')]) + count(./did/*[not(local-name() = 'unittitle')]) &gt; 0">
                    <div id="{$id}">
                        <xsl:attribute name="class">
                            <xsl:text>content</xsl:text>
                            <xsl:if test="/archdesc">
                                <xsl:text> archdesc</xsl:text>
                            </xsl:if>
                        </xsl:attribute>

                        <xsl:if test="$cote_location = 'top' and did/unitid">
                            <xsl:apply-templates select="did/unitid" mode="cote"/>
                        </xsl:if>

                        <xsl:apply-templates mode="full"/>

                        <xsl:if test="$cote_location = 'bottom' and did/unitid">
                            <xsl:apply-templates select="did/unitid" mode="cote"/>
                        </xsl:if>
                    </div>
                </xsl:if>

                <xsl:if test=".//dao|.//daoloc and $print = 'false' and $audience != 'false'">
                    <figure id="relative_documents">
                        <header>
                            <h3><xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayEADFragment::i18nFromXsl', 'Relative documents')"/></h3>
                        </header>
                        <xsl:variable name="daogrps" select=".//daogrp"/>
                        <xsl:variable name="daos" select=".//dao[not(parent::daogrp)]|.//daoloc[not(parent::daogrp)]"/>
                        <xsl:copy-of select="php:function('Bach\HomeBundle\Twig\DisplayDao::displayDaos', $daogrps, $daos, $viewer_uri, 'medium', $ajax, $covers_dir, $communicability, $aws, $cloudfront, $query)"/>
                    </figure>
                </xsl:if>
            </xsl:when>
            <xsl:otherwise>
                <xsl:choose>
                <xsl:when test="$print = 1">
                        <xsl:apply-templates select="did" mode="resume"/>
                </xsl:when>
                <xsl:otherwise>
                    <div id="{$id}">
                    <xsl:attribute name="class">
                        <xsl:text>content</xsl:text>
                        <xsl:if test="/archdesc">
                            <xsl:text> archdesc</xsl:text>
                        </xsl:if>
                    </xsl:attribute>
                    <xsl:apply-templates mode="resume"/>
                    </div>
                </xsl:otherwise>
            </xsl:choose>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="did/unitid" mode="cote">
        <section class="cote">
            <xsl:if test="@label">
                <xsl:choose>
                    <xsl:when test="/archdesc">
                        <h3><xsl:value-of select="concat(@label, ' ')"/></h3>
                    </xsl:when>
                    <xsl:otherwise>
                        <strong><xsl:value-of select="concat(@label, ' ')"/>: </strong>
                    </xsl:otherwise>
                </xsl:choose>
            </xsl:if>
            <span class="unitid" property="dc:identifier">
                <xsl:value-of select="."/>
            </span>
        </section>
    </xsl:template>

    <xsl:template match="did" mode="full">
        <xsl:if test="not(unittitle)">
            <header>
                <h2 property="dc:title"><xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayEADFragment::i18nFromXsl', 'Untitled unit')"/></h2>
            </header>
            <xsl:apply-templates mode="specific" select="scopecontent"/>
            <xsl:apply-templates mode="specific" select="scopecontent/scopecontent"/>
        </xsl:if>
        <xsl:apply-templates mode="full"/>
    </xsl:template>

    <xsl:template match="unittitle" mode="full">
        <xsl:apply-templates mode="specific" select="../../scopecontent"/>
        <xsl:apply-templates mode="specific" select="../../scopecontent/scopecontent"/>
    </xsl:template>

    <xsl:template match="unitdate" mode="full">
        <xsl:if test="not(parent::unittitle) and not(parent::did)">
            <span class="date" property="dc:date">
                <xsl:if test="@normal">
                    <xsl:attribute name="content">
                        <xsl:value-of select="@normal"/>
                    </xsl:attribute>
                </xsl:if>
                <xsl:if test="@label">
                    <xsl:value-of select="concat(@label, ' ')"/>
                </xsl:if>
                <xsl:value-of select="."/>
            </span>
        </xsl:if>
    </xsl:template>

    <xsl:template match="imprint" mode="full">
        <xsl:choose>
            <xsl:when test="parent::unittitle">
                <xsl:apply-templates mode="full"/>
            </xsl:when>
            <xsl:otherwise>
                <xsl:call-template name="section_content"/>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="subject|geogname|persname|corpname|name|function" mode="full">
        <xsl:if test="not(parent::controlaccess)">
            <xsl:choose>
                <xsl:when test="ancestor::processinfo">
                    <xsl:value-of select="."/>
                </xsl:when>
                <xsl:otherwise>
                <a>
                    <xsl:attribute name="link">
                        <!-- URL cannot ben generated from here. Let's build a specific value to be replaced -->
                        <xsl:value-of select="concat('%%%', local-name(), '::', string(.), '%%%')"/>
                    </xsl:attribute>
                    <xsl:value-of select="."/>
                </a>
                </xsl:otherwise>
            </xsl:choose>
            <xsl:if test="following-sibling::subject or following-sibling::geogname or following-sibling::persname or following-sibling::corpname or following-sibling::name or following-sibling::function">
                <xsl:text>, </xsl:text>
            </xsl:if>
        </xsl:if>
    </xsl:template>

    <xsl:template match="genreform[not(@source='liste-niveau') and not(@source='liste-typedocAC') and not(@type='typir') and not(parent::controlaccess)]|extent|physfacet|dimensions|langmaterial|container|origination" mode="full">
        <xsl:variable name="elt_name">
            <xsl:choose>
                <xsl:when test="preceding-sibling::lb or following-sibling::lb">span</xsl:when>
                <xsl:otherwise>div</xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
        <xsl:element name="{$elt_name}">
            <h4>
                <xsl:choose>
                    <xsl:when test="@label">
                        <xsl:value-of select="@label"/>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:choose>
                            <xsl:when test="local-name() = 'genreform'">
                                <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayEADFragment::i18nFromXsl', 'Gender:')"/>
                            </xsl:when>
                            <xsl:when test="local-name() = 'extent'">
                                <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayEADFragment::i18nFromXsl', 'Extent:')"/>
                            </xsl:when>
                            <xsl:when test="local-name() = 'physfacet'">
                                <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayEADFragment::i18nFromXsl', 'Appearance:')"/>
                            </xsl:when>
                            <xsl:when test="local-name() = 'dimensions'">
                                <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayEADFragment::i18nFromXsl', 'Dimensions:')"/>
                            </xsl:when>
                            <xsl:when test="local-name() = 'langmaterial'">
                                <xsl:value-of select="php:function('Bach\homebundle\twig\displayeadfragment::i18nfromxsl', 'language:')"/>
                            </xsl:when>
                            <xsl:when test="local-name() = 'container'">
                                <xsl:value-of select="php:function('Bach\homebundle\twig\displayeadfragment::i18nfromxsl', 'container:')"/>
                            </xsl:when>
                            <xsl:otherwise>
                                UNKNONWN ELEMENT
                            </xsl:otherwise>
                        </xsl:choose>
                    </xsl:otherwise>
                </xsl:choose>
            </h4>
            <xsl:text> </xsl:text>
            <xsl:value-of select="."/>
        </xsl:element>
    </xsl:template>

    <xsl:template match="title" mode="full">
        <xsl:choose>
            <xsl:when test="not(parent::bibref)">
                <!--<div>-->
                    <strong>
                        <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayEADFragment::i18nFromXsl', 'Title:')"/>
                        <xsl:text> </xsl:text>
                    </strong>
                    <xsl:value-of select="."/>
                    <!--</div>-->
            </xsl:when>
            <xsl:otherwise>
                <strong>
                    <xsl:choose>
                        <xsl:when test="parent::bibref/@href">
                            <xsl:variable name="href">
                                <xsl:choose>
                                    <xsl:when test="starts-with(../@href, 'http://')">
                                        <xsl:value-of select="../@href"/>
                                    </xsl:when>
                                    <xsl:otherwise>
                                        <xsl:value-of select="concat('http://', ../@href)"/>
                                    </xsl:otherwise>
                                </xsl:choose>
                            </xsl:variable>
                            <a href="{$href}">
                                <xsl:if test="../@title">
                                    <xsl:attribute name="title">
                                        <xsl:value-of select="../@title"/>
                                    </xsl:attribute>
                                </xsl:if>
                                <xsl:value-of select="."/>
                            </a>
                        </xsl:when>
                        <xsl:otherwise>
                            <xsl:value-of select="."/>
                        </xsl:otherwise>
                    </xsl:choose>
                </strong>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template name="section_content">
        <xsl:param name="title" select="'false'"/>
        <xsl:if test="local-name() != 'controlaccess' or count(./*[local-name() != 'head' and not(@source='liste-niveau') and not(@source='liste-typedocAC') and not(@type='typir')])">
        <xsl:if test="not(@audience = 'internal') or $audience != 'false'">
            <section class="{local-name()}">
                <xsl:if test="not(head) and $title = 'true'">
                    <xsl:variable name="count" select="count(ancestor::*/head)"/>
                    <header>
                        <xsl:element name="h{$count + 3}">
                            <xsl:choose>
                                <xsl:when test="local-name() = 'custodhist'">
                                    <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayEADFragment::i18nFromXsl', 'Conservation history:')"/>
                                </xsl:when>
                                <xsl:when test="local-name() = 'arrangement'">
                                    <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayEADFragment::i18nFromXsl', 'Arrangement:')"/>
                                </xsl:when>
                                <xsl:when test="local-name() = 'relatedmaterial'">
                                    <xsl:value-of select="php:function('Bach\Homebundle\twig\DisplayEADFragment::i18nfromxsl', 'Related material:')"/>
                                </xsl:when>
                                <xsl:when test="local-name() = 'bibliography'">
                                    <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayEADFragment::i18nFromXsl', 'Bibliography:')"/>
                                </xsl:when>
                                <xsl:when test="local-name() = 'userestrict'">
                                    <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayEADFragment::i18nFromXsl', 'Userestrict:')"/>
                                </xsl:when>
                                <xsl:when test="local-name() = 'bioghist'">
                                    <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayEADFragment::i18nFromXsl', 'Biography or history:')"/>
                                </xsl:when>
                                <xsl:when test="local-name() = 'acqinfo'">
                                    <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayEADFragment::i18nFromXsl', 'Acquisition information:')"/>
                                </xsl:when>
                                <xsl:when test="local-name() = 'separatedmaterial'">
                                    <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayEADFragment::i18nFromXsl', 'Separated material:')"/>
                                </xsl:when>
                                <xsl:when test="local-name() = 'otherfindaid'">
                                    <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayEADFragment::i18nFromXsl', 'Other finding aid:')"/>
                                </xsl:when>
                                <xsl:when test="local-name() = 'physdesc'">
                                    <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayEADFragment::i18nFromXsl', 'Physical description')"/>
                                </xsl:when>
                                <xsl:when test="local-name() = 'processinfo'">
                                    <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayEADFragment::i18nFromXsl', 'processinfo')"/>
                                </xsl:when>
                                <xsl:when test="local-name() = 'controlaccess'">
                                    <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayEADFragment::i18nFromXsl', 'Descriptors')"/>
                                </xsl:when>
                                <xsl:when test="local-name() = 'imprint'">
                                    <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayEADFragment::i18nFromXsl', 'Publication informations')"/>
                                </xsl:when>
                                <xsl:when test="local-name() = 'originalsloc'">
                                    <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayEADFragment::i18nFromXsl', 'Original localisation')"/>
                                </xsl:when>
                                <xsl:when test="local-name() = 'abstract'">
                                    <xsl:choose>
                                        <xsl:when test="@label">
                                            <xsl:value-of select="@label"/>
                                        </xsl:when>
                                        <xsl:otherwise>
                                            <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayEADFragment::i18nFromXsl', 'Abstract')"/>
                                        </xsl:otherwise>
                                    </xsl:choose>
                                </xsl:when>
                                <xsl:when test="local-name() = 'repository'">
                                    <xsl:choose>
                                        <xsl:when test="@label">
                                            <xsl:value-of select="@label"/>
                                        </xsl:when>
                                        <xsl:otherwise>
                                            <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayEADFragment::i18nFromXsl', 'Repository:')"/>
                                        </xsl:otherwise>
                                    </xsl:choose>
                                </xsl:when>
                            </xsl:choose>
                        </xsl:element>
                    </header>
                </xsl:if>
                <xsl:apply-templates mode="full"/>
                <xsl:if test="local-name() = 'controlaccess'">
                    <xsl:variable name="nodes" select="subject|geogname|persname|corpname|name|function|genreform[not(@source='liste-niveau') and not(@source='liste-typedocAC') and not(@type='typir')]"/>
                    <xsl:copy-of select="php:function('Bach\HomeBundle\Twig\DisplayEADFragment::showDescriptors', $nodes, $docid)"/>
                </xsl:if>
            </section>
        </xsl:if>
        </xsl:if>
    </xsl:template>

    <xsl:template match="scopecontent" mode="specific">
        <xsl:call-template name="section_content">
            <xsl:with-param name="title" select="'false'"/>
        </xsl:call-template>
    </xsl:template>

    <xsl:template match="accessrestrict|legalstatus|odd|processinfo|custodhist|arrangement|relatedmaterial|originalsloc|bibliography|userestrict|bioghist|acqinfo|separatedmaterial|otherfindaid|repository|physdesc|controlaccess|abstract" mode="full">
        <xsl:call-template name="section_content">
            <xsl:with-param name="title">
                <xsl:choose>
                    <xsl:when test ="/archdesc or local-name() ='abstract'">true</xsl:when>
                    <xsl:otherwise>false</xsl:otherwise>
                </xsl:choose>
            </xsl:with-param>
        </xsl:call-template>
    </xsl:template>

    <xsl:template match="table|thead|tbody" mode="full">
        <xsl:element name="{local-name()}">
            <xsl:apply-templates mode="full"/>
        </xsl:element>
    </xsl:template>

    <xsl:template match="tgroup" mode="full">
        <xsl:apply-templates mode="full"/>
    </xsl:template>

    <xsl:template match="row" mode="full">
        <xsl:variable name="parent-name" select="local-name(parent::node())"/>
        <tr>
            <xsl:apply-templates mode="full">
                <xsl:with-param name="parent-name" select="$parent-name"/>
            </xsl:apply-templates>
        </tr>
    </xsl:template>

    <xsl:template match="entry" mode="full">
        <xsl:param name="parent-name"/>
        <xsl:choose>
            <xsl:when test="$parent-name = 'thead'">
                <th>
                    <xsl:apply-templates mode="full"/>
                </th>
            </xsl:when>
            <xsl:otherwise>
                <td>
                    <xsl:apply-templates mode="full"/>
                </td>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="emph" mode="full">
        <xsl:choose>
            <xsl:when test="@render='bold'">
                <strong>
                    <xsl:apply-templates mode="full"/>
                </strong>
            </xsl:when>
            <xsl:when test="@render='italic'">
                <em>
                    <xsl:apply-templates mode="full"/>
                </em>
            </xsl:when>
            <xsl:otherwise>
                <xsl:apply-templates mode="full"/>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="list" mode="full">
        <ul>
            <xsl:apply-templates mode="full"/>
        </ul>
    </xsl:template>

    <xsl:template match="defitem|change" mode="full">
        <dl>
            <xsl:apply-templates mode="full"/>
        </dl>
    </xsl:template>

    <xsl:template match="label" mode="full">
        <xsl:variable name="parent-name" select="local-name(parent::node())"/>
        <xsl:choose>
            <xsl:when test="$parent-name = 'defitem'">
                <dt>
                    <xsl:apply-templates mode="full"/>
                </dt>
            </xsl:when>
            <xsl:otherwise>
                <xsl:apply-templates mode="full"/>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="date" mode="full">
        <xsl:variable name="parent-name" select="local-name(parent::node())"/>
    <xsl:choose>
            <xsl:when test="$parent-name = 'change'">
                <dt>
                    <xsl:apply-templates mode="full"/>
                </dt>
            </xsl:when>
            <xsl:otherwise>
                <xsl:apply-templates mode="full"/>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="item" mode="full">
        <xsl:variable name="parent-name" select="local-name(parent::node())"/>
        <xsl:choose>
            <xsl:when test="$parent-name = 'list'">
                <li>
                    <xsl:apply-templates mode="full"/>
                </li>
            </xsl:when>
            <xsl:when test="$parent-name = 'defitem' or $parent-name = 'change'">
                <dd>
                    <xsl:apply-templates mode="full"/>
                </dd>
            </xsl:when>
            <xsl:otherwise>
                <xsl:apply-templates mode="full"/>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="head" mode="full">
        <!-- Count *real* descriptors from controlaccess to prevent empty title display -->
        <xsl:variable name="descriptors_count" select="count(../*[local-name() != 'head' and not(@source='liste-niveau') and not(@source='liste-typedocAC') and not(@type='typir')])"/>
        <xsl:if test="(text() != '' and $descriptors_count &gt; 0)">
            <!-- Count direct parent that have a head child. That will include current node -->
            <xsl:variable name="count" select="count(ancestor::*/head)"/>
            <header>
                <xsl:element name="h{$count + 2}">
                    <xsl:value-of select="."/>
                </xsl:element>
            </header>
        </xsl:if>
    </xsl:template>

    <xsl:template match="bibref" mode="full">
        <xsl:variable name="elt">
            <xsl:choose>
                <xsl:when test="not(parent::p)">
                    <xsl:text>div</xsl:text>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:text>span</xsl:text>
                </xsl:otherwise>
            </xsl:choose>
        </xsl:variable>

        <xsl:element name="{$elt}">
            <xsl:attribute name="property">dc:bibliographicCitation</xsl:attribute>
            <xsl:apply-templates mode="full"/>
        </xsl:element>
    </xsl:template>

    <xsl:template match="relatedmaterial/ref|ref" mode="full">
        <xsl:choose>
            <xsl:when test="@target != ''">
                <a>
                    <xsl:attribute name="href">
                        <xsl:variable name="basename">
                        <xsl:call-template name="substring-before-last">
                            <xsl:with-param name="string1" select="$docid" />
                            <xsl:with-param name="string2" select="'_'" />
                        </xsl:call-template>
                        </xsl:variable>
                        <xsl:value-of select="concat('/archives/show/',$basename,'_',@target)" />
                    </xsl:attribute>
                    <xsl:if test="@title and . != ''">
                        <xsl:attribute name="title">
                            <xsl:value-of select="@title"/>
                        </xsl:attribute>
                    </xsl:if>
                    <xsl:if test="@title and . = ''">
                        <xsl:value-of select="@title"/>
                    </xsl:if>
                    <xsl:attribute name="target">_blank</xsl:attribute>
                    <xsl:value-of select="text()"/>
                </a>
            </xsl:when>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="extref|archref" mode="full">
        <xsl:choose>
            <xsl:when test="@href">
                <xsl:choose>
                    <xsl:when test="not(substring(@href, 1, 7) = 'http://') and not(substring(@href, 1, 8) = 'https://' )">
                        <xsl:variable name="titleFrag">
                            <xsl:choose>
                            <xsl:when test="text() != ''">
                                <xsl:value-of select="text()"/>
                            </xsl:when>
                            <xsl:otherwise>
                                <xsl:value-of select="@title"/>
                            </xsl:otherwise>
                        </xsl:choose>
                        </xsl:variable>
                        <xsl:copy-of select="php:function('Bach\HomeBundle\Twig\DisplayDao::getDao', string(@href), $titleFrag, $viewer_uri, 'thumb', $covers_dir)"/>
                    </xsl:when>
                    <xsl:otherwise>
                        <a href="{@href}">
                            <xsl:if test="@title and . != ''">
                                <xsl:attribute name="title">
                                    <xsl:value-of select="@title"/>
                                </xsl:attribute>
                            </xsl:if>
                            <xsl:attribute name="target">_blank</xsl:attribute>
                            <xsl:if test="@title and . = ''">
                                <xsl:value-of select="@title"/>
                            </xsl:if>
                            <xsl:apply-templates mode="full"/>
                        </a>
                    </xsl:otherwise>
                </xsl:choose>
            </xsl:when>
            <xsl:otherwise>
                <xsl:apply-templates mode="full"/>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="blockquote" mode="full">
        <blockquote>
            <xsl:apply-templates mode="full"/>
        </blockquote>
    </xsl:template>

    <xsl:template match="p" mode="full">
        <p>
            <xsl:if test="@altrender">
                <xsl:attribute name="class">
                    <xsl:value-of select="@altrender"/>
                </xsl:attribute>
            </xsl:if>
            <xsl:apply-templates mode="full"/>
        </p>
    </xsl:template>

    <xsl:template match="dao" mode="full">
        <xsl:choose>
            <xsl:when test="@href">
                <xsl:copy-of select="php:function('Bach\HomeBundle\Twig\DisplayDao::getDao', string(@href), string(text()), $viewer_uri, 'thumb', $covers_dir, 'true')"/>
            </xsl:when>
            <xsl:otherwise>
                <xsl:copy-of select="text()" />
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="text()" mode="full">
        <xsl:copy-of select="."/>
    </xsl:template>

    <xsl:template match="dao|daoloc" mode="daos">
        <xsl:variable name="title" value="@title"/>
        <xsl:copy-of select="php:function('Bach\HomeBundle\Twig\DisplayDao::getDao', string(@href), $title, $viewer_uri, 'medium', $covers_dir)"/>
    </xsl:template>

    <xsl:template match="did" mode="resume">
        <xsl:if test="unitid or langmaterial">
            <!-- Title is already displayed, show other items -->
            <header class="did">
                <xsl:if test="unitid">
                    <span class="unitid" property="dc:identifier">
                        <xsl:value-of select="unitid"/>
                    </span>
                    <!--<xsl:if test="langmaterial">
                        <xsl:text> - </xsl:text>
                    </xsl:if>-->
                </xsl:if>
                <!--<xsl:if test="langmaterial">
                    <xsl:value-of select="langmaterial"/>
                </xsl:if>-->
            </header>
        </xsl:if>
    </xsl:template>

    <xsl:template match="physdesc" mode="resume">
        <section class="physdesc">
            <xsl:if test="@label">
                <header><xsl:value-of select="@label"/></header>
                <xsl:text> </xsl:text>
            </xsl:if>
            <xsl:apply-templates mode="resume"/>
        </section>
    </xsl:template>

    <xsl:template match="lb" mode="full">
        <xsl:if test="not(preceding-sibling::lb)">
            <br/>
        </xsl:if>
    </xsl:template>

    <xsl:template match="lb" mode="resume">
        <xsl:if test="not(preceding-sibling::lb)">
            <br/>
        </xsl:if>
    </xsl:template>

    <xsl:template match="controlaccess" mode="resume">
        <xsl:variable name="nodes" select="subject|geogname|persname|corpname|name|function|genreform[not(@source='liste-niveau') and not(@source='liste-typedocAC') and not(@type='typir')]"/>
        <xsl:copy-of select="php:function('Bach\HomeBundle\Twig\DisplayEADFragment::showDescriptors', $nodes, $docid)"/>
    </xsl:template>

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

    <!-- Per default, display nothing -->
    <xsl:template match="*|@*|node()"/>
    <xsl:template match="*|@*|node()" mode="full"/>
    <xsl:template match="*|@*|node()" mode="resume"/>

</xsl:stylesheet>

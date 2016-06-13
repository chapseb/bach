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
    <xsl:param name="expanded" select="'false'"/>

    <xsl:template match="ead">
        <article>
            <header id="cdcTitle">
                <xsl:apply-templates select="eadheader" mode="header"/>
            </header>
            <div id="inventory_contents">
                <h3 id="presentation">
                    <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Presentation')"/>
                </h3>
                <div id="inventory_presentation">
                        <dl>
                            <xsl:apply-templates mode="presentation"/>
                        </dl>
                </div>
                <h3 id="contents">
                    <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Contents')"/>
                </h3>
                <div class="css-treeview">
                    <ul>%contents%</ul>
                </div>
                <h3 id="biblioinfos">
                    <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayEADFragment::i18nFromXsl', 'Bibliographic informations')"/>
                </h3>
                <div>
                    <xsl:apply-templates select="eadheader/*" mode="header"/>
                </div>
            </div>
        </article>
    </xsl:template>

    <xsl:template match="eadheader" mode="header">
        <h2>
            <xsl:apply-templates select="filedesc/titlestmt/titleproper" mode="header_title"/>
        </h2>
        <xsl:apply-templates select="filedesc/titlestmt/author" mode="header_title"/>
    </xsl:template>

    <xsl:template match="titleproper" mode="header_title">
        <xsl:apply-templates/>
    </xsl:template>

    <xsl:template match="author" mode="header_title">
        <div class="inventory_author">
            <xsl:apply-templates/>
        </div>
    </xsl:template>

    <xsl:template match="archdesc" mode="presentation">
        <xsl:apply-templates mode="presentation"/>
    </xsl:template>

    <xsl:template match="did" mode="presentation">
        <xsl:apply-templates mode="presentation"/>
    </xsl:template>

    <xsl:template match="unitid|unittitle|unitdate" mode="presentation">
        <dt>
            <h2>
                <xsl:choose>
                    <xsl:when test="@label">
                        <xsl:value-of select="@label"/>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:choose>
                            <xsl:when test="local-name() = 'unittitle'">
                                <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Title')"/>
                            </xsl:when>
                            <xsl:when test="local-name() = 'unitdate'">
                                <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Date')"/>
                            </xsl:when>
                            <xsl:when test="local-name() = 'unitid'">
                                <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Class number')"/>
                            </xsl:when>
                        </xsl:choose>
                    </xsl:otherwise>
                </xsl:choose>
            </h2>
        </dt>
        <dd>
            <xsl:value-of select="."/>
        </dd>
    </xsl:template>

    <xsl:template match="extent|physfacet|genreform" mode="presentation">
        <dt>
            <h3>
                <xsl:choose>
                    <xsl:when test="@label">
                        <xsl:value-of select="@label"/>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:choose>
                            <xsl:when test="local-name() = 'extent'">
                                <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Extent')"/>
                            </xsl:when>
                             <xsl:when test="local-name() = 'genreform'">
                                <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Genreform')"/>
                            </xsl:when>
                             <xsl:when test="local-name() = 'physfacet'">
                                <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Physfacet')"/>
                            </xsl:when>
                        </xsl:choose>
                    </xsl:otherwise>
                </xsl:choose>
            </h3>
        </dt>
        <dd>
            <xsl:value-of select="."/>
        </dd>
    </xsl:template>

    <xsl:template match="physdesc|origination|langmaterial|repository" mode="presentation">
        <dt>
            <h2>
                <xsl:choose>
                    <xsl:when test="@label">
                        <xsl:value-of select="@label"/>
                    </xsl:when>
                    <xsl:when test="local-name() ='physdesc'">
                        <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Physical description')"/>
                    </xsl:when>
                    <xsl:when test="local-name() ='origination'">
                        <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Origination')"/>
                    </xsl:when>
                    <xsl:when test="local-name() ='langmaterial'">
                        <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Language')"/>
                    </xsl:when>
                    <xsl:when test="local-name() = 'repository'">
                        <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Repository')"/>
                    </xsl:when>
                </xsl:choose>
           </h2>
        </dt>
        <dd>
            <xsl:apply-templates mode="contents"/>
        </dd>
        <xsl:apply-templates mode="presentation"/>
    </xsl:template>

    <!-- head elements are handled from their parents -->
    <xsl:template match="custodhist/head|separatedmaterial/head|acqinfo/head|bibliography/head|//relatedmaterial/head|userestrict/head|odd/head|bioghist/head|scopecontent/head|processinfo/head|otherfindaid/head|accessrestrict/head|arrangement/head|originalsloc/head" mode="contents"/>
    <xsl:template match="custodhist|separatedmaterial|acqinfo|bibliography|//relatedmaterial|userestrict|odd|bioghist|scopecontent|processinfo|otherfindaid|accessrestrict|arrangement|originalsloc" mode="presentation">
        <xsl:variable name="current" select="local-name()"/>
        <xsl:choose>
            <xsl:when test="./*[local-name() = $current]">
                <!-- Until now, if an element contains an element with the same name,
                     the first one is empty (or has just a header); we can ignore it -->
                <xsl:if test="head">
                    <section class="{local-name()}">
                        <header>
                            <xsl:variable name="count" select="count(ancestor::*/head)"/>
                            <xsl:element name="h{$count + 2}">
                                <xsl:value-of select="head"/>
                            </xsl:element>
                        </header>
                    </section>
                </xsl:if>
                <xsl:apply-templates mode="presentation"/>
            </xsl:when>
            <xsl:otherwise>
                <section class="{local-name()}">
                    <header>
                        <xsl:choose>
                            <xsl:when test="head">
                             <xsl:variable name="count" select="count(ancestor::*/head)"/>
                            <xsl:element name="h{$count + 2}">
                                <xsl:value-of select="head"/>
                            </xsl:element>
                            </xsl:when>
                            <xsl:otherwise>
                            <xsl:variable name="count" select="count(ancestor::*/head)"/>
                            <xsl:element name="h{$count + 2}">
                                <xsl:choose>
                                    <xsl:when test="local-name() ='accessrestrict'">
                                        <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Legal status')"/>
                                    </xsl:when>
                                    <xsl:when test="local-name() ='arrangement'">
                                        <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Arrangement:')"/>
                                    </xsl:when>

                                    <xsl:when test="local-name() ='custodhist'">
                                        <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Custodial history')"/>
                                    </xsl:when>
                                    <xsl:when test="local-name() ='acqinfo'">
                                        <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Acquisition information')"/>
                                    </xsl:when>
                                    <xsl:when test="local-name() ='bibliography'">
                                        <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Bibliography')"/>
                                    </xsl:when>
                                    <xsl:when test="local-name() ='relatedmaterial'">
                                        <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Related material')"/>
                                    </xsl:when>
                                    <xsl:when test="local-name() ='userestrict'">
                                        <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Condition of use')"/>
                                    </xsl:when>
                                    <xsl:when test="local-name() ='bioghist'">
                                        <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Biography or history')"/>
                                    </xsl:when>
                                    <xsl:when test="local-name() ='scopecontent'">
                                        <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Scope and content')"/>
                                    </xsl:when>
                                    <xsl:when test="local-name() ='processinfo'">
                                        <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Processing information')"/>
                                    </xsl:when>
                                    <xsl:when test="local-name() ='otherfindaid'">
                                        <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Other finding aid')"/>
                                    </xsl:when>
                                    <xsl:when test="local-name() ='odd'">
                                        <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Odd')"/>
                                    </xsl:when>
                                </xsl:choose>
                            </xsl:element>
                            </xsl:otherwise>
                        </xsl:choose>
                        </header>
                    <xsl:apply-templates mode="contents"/>
                </section>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="controlaccess" mode="presentation">
        <xsl:variable name="nodes" select="subject|geogname|persname|corpname|name|function|genreform[not(@source='liste-niveau') and not(@source='liste-typedocAC') and not(@type='typir')]"/>
        <xsl:copy-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::showDescriptors', $nodes, $docid)/descriptors/*"/>
    </xsl:template>

    <xsl:template match="genreform[@source = 'liste-typedocAC']" mode="contents"/>
    <xsl:template match="physdesc/extent" mode="contents"/>
    <xsl:template match="physdesc/genreform" mode="contents"/>
    <xsl:template match="physdesc/physfacet" mode="contents"/>

    <xsl:template match="p" mode="contents">
        <p>
            <xsl:apply-templates mode="contents"/>
        </p>
    </xsl:template>

    <xsl:template match="list" mode="contents">
        <ul>
            <xsl:apply-templates mode="contents"/>
        </ul>
    </xsl:template>

    <xsl:template match="defitem|change" mode="contents">
        <dl>
            <xsl:apply-templates mode="contents"/>
        </dl>
    </xsl:template>

    <xsl:template match="label" mode="contents">
        <xsl:variable name="parent-name" select="local-name(parent::node())"/>
        <xsl:choose>
            <xsl:when test="$parent-name = 'defitem'">
                <dt>
                    <xsl:apply-templates mode="contents"/>
                </dt>
            </xsl:when>
            <xsl:otherwise>
                <xsl:apply-templates mode="contents"/>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="date" mode="contents">
        <xsl:variable name="parent-name" select="local-name(parent::node())"/>
        <xsl:choose>
            <xsl:when test="$parent-name = 'change'">
                <dt>
                    <xsl:apply-templates mode="contents"/>
                </dt>
            </xsl:when>
            <xsl:otherwise>
                <xsl:apply-templates mode="contents"/>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="extref|archref" mode="full">
        <xsl:choose>
            <xsl:when test="@href">
                <xsl:choose>
                    <xsl:when test="substring(@href, 1, 7) = 'http://'">
                        <a href="{@href}">
                            <xsl:if test="@title and . != ''">
                                <xsl:attribute name="title">
                                    <xsl:value-of select="@title"/>
                                </xsl:attribute>
                            </xsl:if>
                            <xsl:if test="@title and . = ''">
                                <xsl:value-of select="@title"/>
                            </xsl:if>
                            <xsl:apply-templates mode="full"/>
                        </a>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:element name="a">
                                <xsl:attribute name="href">
                                    <xsl:variable name="varhref" select="@href"/>
                                    <xsl:call-template name="replace-string">
                                        <xsl:with-param name="text" select="@href"/>
                                        <xsl:with-param name="replace" select="'.xml'" />
                                        <xsl:with-param name="with" select="''"/>
                                    </xsl:call-template>
                                </xsl:attribute>

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
                                    <xsl:variable name="varhref" select="@href"/>
                                    <xsl:call-template name="replace-string">
                                        <xsl:with-param name="text" select="@href"/>
                                        <xsl:with-param name="replace" select="'.xml'" />
                                        <xsl:with-param name="with" select="''"/>
                                    </xsl:call-template>
                            </xsl:otherwise>
                            </xsl:choose>
                        </xsl:element>
                    </xsl:otherwise>
                </xsl:choose>
            </xsl:when>
            <xsl:otherwise>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    <xsl:template match="extref|archref" mode="contents">
        <xsl:choose>
            <xsl:when test="@href">
                <xsl:choose>
                    <xsl:when test="substring(@href, 1, 7) = 'http://'">
                        <a href="{@href}">
                            <xsl:if test="@title and . != ''">
                                <xsl:attribute name="title">
                                    <xsl:value-of select="@title"/>
                                </xsl:attribute>
                            </xsl:if>
                            <xsl:if test="@title and . = ''">
                                <xsl:value-of select="@title"/>
                            </xsl:if>
                            <xsl:apply-templates mode="contents"/>
                        </a>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:element name="a">
                                <xsl:attribute name="href">
                                    <xsl:variable name="varhref" select="@href"/>
                                    <xsl:call-template name="replace-string">
                                        <xsl:with-param name="text" select="@href"/>
                                        <xsl:with-param name="replace" select="'.xml'" />
                                        <xsl:with-param name="with" select="''"/>
                                    </xsl:call-template>
                                </xsl:attribute>

                            <xsl:if test="@title and . != ''">
                                <xsl:attribute name="title">
                                    <xsl:value-of select="@title"/>
                                </xsl:attribute>
                            </xsl:if>
                            <xsl:choose>
                                <xsl:when test="@title and not(. = '')">
                                    <xsl:value-of select="@title"/>
                                </xsl:when>
                                <xsl:otherwise>
                                    <xsl:variable name="varhref" select="@href"/>
                                    <xsl:call-template name="replace-string">
                                        <xsl:with-param name="text" select="@href"/>
                                        <xsl:with-param name="replace" select="'.xml'" />
                                        <xsl:with-param name="with" select="''"/>
                                    </xsl:call-template>
                            </xsl:otherwise>
                            </xsl:choose>
                        </xsl:element>
                    </xsl:otherwise>
                </xsl:choose>
            </xsl:when>
            <xsl:otherwise>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>


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

    <xsl:template match="item" mode="contents">
        <xsl:variable name="parent-name" select="local-name(parent::node())"/>
        <xsl:choose>
            <xsl:when test="$parent-name = 'list'">
                <li>
                    <xsl:choose>
                        <xsl:when test="substring(extref/@href, 1, 7) = 'http://'">
                            <xsl:apply-templates mode="full"/>
                        </xsl:when>
                        <xsl:otherwise>
                            <xsl:choose>
                                <xsl:when test="archref">
                                    <xsl:element name="a">
                                        <xsl:attribute name="href">
                                            <xsl:variable name="varhref" select="archref/@href"/>
                                            <xsl:call-template name="replace-string">
                                                <xsl:with-param name="text" select="archref/@href"/>
                                                    <xsl:with-param name="replace" select="'.xml'" />
                                                      <xsl:with-param name="with" select="''"/>
                                            </xsl:call-template>

                                        </xsl:attribute>
                                        <xsl:if test="archref/@title and . != ''">
                                            <xsl:attribute name="title">
                                                <xsl:value-of select="archref/@title"/>
                                            </xsl:attribute>
                                        </xsl:if>
                                        <xsl:if test="archref/@title and . = ''">
                                            <xsl:value-of select="archref/@title"/>
                                        </xsl:if>
                                        <xsl:apply-templates mode="full"/>
                                    </xsl:element>
                                </xsl:when>
                                <xsl:otherwise>
                                    <xsl:apply-templates mode="contents"/>
                                </xsl:otherwise>
                            </xsl:choose>
                        </xsl:otherwise>
                    </xsl:choose>
                </li>
            </xsl:when>
            <xsl:when test="$parent-name = 'defitem' or $parent-name = 'change'">
                <dd>
                    <xsl:apply-templates mode="contents"/>
                </dd>
            </xsl:when>
            <xsl:otherwise>
                <xsl:apply-templates mode="contents"/>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <!-- ***** FILEDESC ***** -->
    <xsl:template match="filedesc" mode="header">
        <section class="{local-name()}">
            <header>
                <h3>
                    <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Publication informations')"/>
                </h3>
            </header>
            <xsl:apply-templates mode="header"/>
        </section>
    </xsl:template>

    <xsl:template match="filedesc/titlestmt" mode="header">
        <h4>
            <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Title statement')"/>
        </h4>

        <!-- Title proper -->
        <strong>
            <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Title proper:')"/>
        </strong>
        <xsl:choose>
            <xsl:when test="count(titleproper) > 1">
                <ul>
                    <xsl:apply-templates select="titleproper" mode="header"/>
                </ul>
            </xsl:when>
            <xsl:otherwise>
                <xsl:value-of select="' '"/>
                <xsl:apply-templates select="titleproper"/>
            </xsl:otherwise>
        </xsl:choose>

        <!-- Subtitle -->
        <xsl:if test="subtitle">
            <br/>
            <strong>
                <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Subtitle:')"/>
            </strong>
            <xsl:choose>
                <xsl:when test="count(subtitle) > 1">
                    <ul>
                        <xsl:apply-templates select="subtitle" mode="header"/>
                    </ul>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:value-of select="' '"/>
                    <xsl:apply-templates select="subtitle"/>
                </xsl:otherwise>
            </xsl:choose>
        </xsl:if>

        <!-- Author -->
        <xsl:if test="author">
            <br/>
            <strong>
                <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Author:')"/>
            </strong>
            <xsl:value-of select="' '"/>
            <xsl:apply-templates select="author"/>
        </xsl:if>

        <!-- Sponsor -->
        <xsl:if test="sponsor and normalize-space(sponsor) != ''">
            <br/>
            <strong>
                <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Sponsor:')"/>
            </strong>
            <xsl:value-of select="' '"/>
            <xsl:apply-templates select="sponsor"/>
        </xsl:if>
    </xsl:template>

    <xsl:template match="filedesc/titlestmt[ count(titleproper) > 1 ]/titleproper" mode="header">
        <li>
            <xsl:apply-templates />
        </li>
    </xsl:template>

    <xsl:template match="filedesc/titlestmt[ count(subtitle) > 1 ]/subtitle" mode="header">
        <li>
            <xsl:apply-templates />
        </li>
    </xsl:template>

    <xsl:template match="filedesc/titlestmt/author" mode="header">
        <xsl:apply-templates />
    </xsl:template>

    <xsl:template match="filedesc/titlestmt/sponsor" mode="header">
        <xsl:apply-templates />
    </xsl:template>

    <xsl:template match="filedesc/editionstmt" mode="header">
        <h4>
            <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Edition statement')"/>
        </h4>
        <xsl:apply-templates mode="header"/>
    </xsl:template>

    <xsl:template match="filedesc/publicationstmt" mode="header">
        <h4>
            <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Publication statement')"/>
        </h4>
        <xsl:apply-templates mode="header" />
    </xsl:template>

    <xsl:template match="filedesc/publicationstmt/publisher" mode="header">
        <strong>
            <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Publisher:')"/>
        </strong>
        <xsl:value-of select="' '"/>
        <xsl:apply-templates/>
    </xsl:template>

    <xsl:template match="filedesc/publicationstmt/date" mode="header">
        <br/>
        <strong>
            <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Date:')"/>
        </strong>
        <xsl:value-of select="' '"/>
        <xsl:apply-templates />
    </xsl:template>

    <xsl:template match="filedesc/publicationstmt/address" mode="header">
        <br/>
        <strong>
            <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Address:')"/>
        </strong>
        <xsl:value-of select="' '"/>
        <xsl:apply-templates />
    </xsl:template>

    <xsl:template match="filedesc/publicationstmt/num|filedesc/seriesstmt/num" mode="header">
        <br/>
        <strong>
            <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Number:')"/>
        </strong>
        <xsl:value-of select="' '"/>
        <xsl:apply-templates />
    </xsl:template>

    <xsl:template match="filedesc/publicationstmt/p|filedesc/editionstmt/p|filedesc/editionstmt/edition|filedesc/seriesstmt/p|filedesc/notestmt/note/p" mode="header">
        <p>
            <xsl:apply-templates />
        </p>
    </xsl:template>

    <xsl:template match="filedesc/seriesstmt" mode="header">
        <h4>
            <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Series statement')"/>
        </h4>
        <xsl:apply-templates mode="header" />
    </xsl:template>

    <xsl:template match="filedesc/seriesstmt/titleproper" mode="header">
        <strong>
            <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Title proper:')"/>
        </strong>
        <xsl:value-of select="' '"/>
        <xsl:apply-templates />
    </xsl:template>

    <xsl:template match="filedesc/notestmt" mode="header">
        <h4>
            <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Note statement')"/>
        </h4>
        <xsl:apply-templates mode="header" />
    </xsl:template>

    <xsl:template match="filedesc/notestmt/note" mode="header">
        <div>
            <xsl:apply-templates/>
        </div>
    </xsl:template>

    <xsl:template match="profiledesc" mode="header">
        <xsl:if test="*">
            <section class="{local-name()}">
                <header>
                    <h3>
                        <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Profile')"/>
                    </h3>
                </header>
                <xsl:apply-templates mode="header"/>
            </section>
        </xsl:if>
    </xsl:template>

    <xsl:template match="profiledesc/creation" mode="header">
        <strong>
            <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Creation:')"/>
        </strong>
        <xsl:value-of select="' '"/>
        <xsl:apply-templates />
    </xsl:template>

    <xsl:template match="profiledesc/langusage" mode="header">
        <br/>
        <strong>
            <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Language:')"/>
        </strong>
        <xsl:value-of select="' '"/>
        <xsl:apply-templates />
    </xsl:template>

    <xsl:template match="profiledesc/descrules" mode="header">
        <br/>
        <strong>
            <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Description rules:')"/>
        </strong>
        <xsl:value-of select="' '"/>
        <xsl:apply-templates />
    </xsl:template>

    <xsl:template match="revisiondesc" mode="header">
        <section class="{local-name()}">
            <header>
               <h3>
                    <xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayHtml::i18nFromXsl', 'Revision description')"/>
                </h3>
            </header>
            <xsl:apply-templates mode="header"/>
        </section>
    </xsl:template>

    <xsl:template match="revisiondesc/change" mode="header">
        <xsl:choose>
            <xsl:when test="count(item) = 1 ">
                <xsl:apply-templates select="item" mode="header"/>
            </xsl:when>
            <xsl:otherwise>
                <ul>
                    <xsl:apply-templates select="item" mode="header"/>
                </ul>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="revisiondesc/change[count(item) > 1]/item" mode="header">
        <li>
            <xsl:apply-templates />
        </li>
    </xsl:template>

    <xsl:template match="revisiondesc/change[count(item) = 1]/item" mode="header">
        <p><xsl:apply-templates /></p>
    </xsl:template>
    <!-- ***** END FILEDESC ***** -->

    <!-- ***** CONTENTS ***** -->
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
        </a>
    </xsl:template>
    <!-- ***** END CONTENTS ***** -->

    <!-- ***** GENERIC TAGS ***** -->
    <xsl:template match="date|language" mode="contents">
        <xsl:apply-templates select="."/>
    </xsl:template>
    <xsl:template match="date|language">
        <xsl:value-of select="' '"/>
        <xsl:apply-templates/>
        <xsl:value-of select="' '"/>
    </xsl:template>

    <xsl:template match="titleproper|author|sponsor|addressline|subtitle" mode="contents">
        <xsl:apply-templates select="."/>
    </xsl:template>
    <xsl:template match="titleproper|author|sponsor|addressline|subtitle">
        <xsl:apply-templates/>
    </xsl:template>

    <xsl:template match="unittitle/unitdate" mode="contents">
        <xsl:apply-templates select="."/>
    </xsl:template>
    <xsl:template match="unittitle/unitdate">
        <span class="date" property="dc:date">
            <xsl:value-of select="' '"/>
            <xsl:value-of select="."/>
        </span>
    </xsl:template>

    <xsl:template match="emph" mode="contents">
        <xsl:apply-templates select="."/>
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

    <xsl:template match="text()" mode="contents">
        <xsl:apply-templates select="."/>
    </xsl:template>
    <xsl:template match="text()">
        <xsl:copy-of select="translate(., '&#xA;&#xD;', '  ')"/>
    </xsl:template>

    <xsl:template match="lb" mode="contents">
        <xsl:apply-templates select="."/>
    </xsl:template>
    <!-- ***** END GENERIC TAGS ***** -->

    <!-- Per default, display nothing -->
    <xsl:template match="*|@*|node()"/>
    <xsl:template match="*|@*|node()" mode="header"/>
    <xsl:template match="*|@*|node()" mode="presentation"/>

</xsl:stylesheet>

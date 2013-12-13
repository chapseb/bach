<?xml version="1.0" encoding="UTF-8"?>
<!--

Displays an EAD fragment as HTML

@author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
@license  Unknown http://unknown.com
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
        <ul class="cdc">
            <xsl:apply-templates select="./c|./c01|./c02|./c03|./c04|./c05|./c06|./c07|./c08|./c09|./c10|./c11|./c12" mode="lvl"/>
            <xsl:if test="count(//not_matched/*) &gt; 0">
                <li>
                    <strong><xsl:value-of select="php:function('Bach\HomeBundle\Twig\DisplayCdc::i18nFromXsl', 'Not classified')"/></strong>
                    <ul>
                        <xsl:for-each select="//not_matched/*">
                            <li>
                                <a link="{concat('%%%', local-name(), '_description%%%')}"><xsl:value-of select="."/></a>
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

        <li id="{$id}">
            <xsl:apply-templates/>
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
        <xsl:text> </xsl:text>
        <xsl:if test="../unitid">
            <span class="unitid" property="dc:identifier">
                <xsl:value-of select="../unitid"/>
            </span>
        </xsl:if>
        <xsl:if test="../unitdate">
            <xsl:if test="../unitid"> - </xsl:if>
            <span class="date" property="dc:date">
                <xsl:value-of select="../unitdate"/>
            </span>
        </xsl:if>
    </xsl:template>

    <xsl:template match="otherfindaid">
        <xsl:apply-templates/>
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

    <xsl:template match="list">
        <xsl:variable name="proceed" select="php:function('Bach\HomeBundle\Twig\DisplayCdc::hasPublished', ., //dadocs)"/>
        <xsl:if test="$proceed">
            <ul>
                <xsl:apply-templates/>
            </ul>
        </xsl:if>
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
                <li>
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
            <xsl:when test="//dadocs/*[local-name() = $docid]">
                <a link="{concat('%%%', string($docid), '_description%%%')}">
                    <xsl:if test="@title and . != ''">
                        <xsl:attribute name="title">
                            <xsl:value-of select="@title"/>
                        </xsl:attribute>
                    </xsl:if>
                    <xsl:if test="@title and . = ''">
                        <xsl:value-of select="@title"/>
                    </xsl:if>
                    <xsl:value-of select="//dadocs/*[local-name() = $docid]"/>
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
        <xsl:copy-of select="."/>
    </xsl:template>

    <xsl:template match="lb">
        <br/>
    </xsl:template>

    <!-- Per default, display nothing -->
    <xsl:template match="*|@*|node()"/>
    <xsl:template match="*|@*|node()" mode="extends"/>
    <xsl:template match="*|@*|node()" mode="lvl"/>

</xsl:stylesheet>
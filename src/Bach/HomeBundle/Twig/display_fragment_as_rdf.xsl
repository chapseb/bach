<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl"
	xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
	xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
	xmlns:dcterms="http://purl.org/dc/terms/"
	xmlns:time="http://www.w3.org/2006/time#"
	xmlns:foaf="http://xmlns.com/foaf/0.1/"
	xmlns:skos="http://www.w3.org/2004/02/skos/core#"
	xmlns:mdfa="http://www.anaphore.eu/ontologies/mdfa#"
	exclude-result-prefixes="php">

	
	<xsl:output method="xml" omit-xml-declaration="no" indent="yes" />

	<xsl:param name="xmlBase">http://bach.anaphore.org/resource/</xsl:param>

	<xsl:template match="/">
		<rdf:RDF 
			xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
			xmlns:dcterms="http://purl.org/dc/terms/"
			xmlns:mdfa="http://www.anaphore.eu/ontologies/mdfa#"
			xml:base="{$xmlBase}"
			>
				<xsl:apply-templates />
		</rdf:RDF>
	</xsl:template>
	
	<xsl:template match="ead">
		<xsl:apply-templates />
	</xsl:template>
	
	

	<xsl:template match="archdesc">

		<!-- id est soit l'attribut @id, soit généré automatiquement -->
		<xsl:variable name="id">
			<xsl:choose>
				<xsl:when test="@id"><xsl:value-of select="@id" /></xsl:when>
				<xsl:otherwise><xsl:value-of select="generate-id(.)" /></xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		
		<mdfa:RessourceArchivistique rdf:about="{$id}">
			<xsl:call-template name="aPourNiveau"><xsl:with-param name="level" select="@level" /></xsl:call-template>
			<xsl:apply-templates />			
			<mdfa:aPourNotice>
				<mdfa:Notice rdf:about="{$id}-notice">
					<xsl:apply-templates mode="notice" />
				</mdfa:Notice>
			</mdfa:aPourNotice>
		</mdfa:RessourceArchivistique>
		
	</xsl:template>
	
	<xsl:template match="dsc">
		<xsl:apply-templates />
	</xsl:template>

	<xsl:template match="c|c01|c02|c03|c04|c05|c06|c07|c08|c09|c10|c11|c12">

		<!-- id est soit l'attribut @id, soit généré automatiquement -->
		<xsl:variable name="id">
			<xsl:choose>
				<xsl:when test="@id"><xsl:value-of select="@id" /></xsl:when>
				<xsl:otherwise><xsl:value-of select="generate-id(.)" /></xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		
		<xsl:choose>
			<xsl:when test="parent::dsc | parent::c | parent::c01 | parent::c02 | parent::c03 | parent::c04 | parent::c05 | parent::c06 | parent::c07 | parent::c08 | parent::c09 | parent::c10 | parent::c11 | parent::c12">
				<!-- on est dans un parent et dans ce cas on génère un dcterms:hasPart -->
				<dcterms:hasPart>
					<mdfa:RessourceArchivistique rdf:about="{$id}">
						<xsl:call-template name="aPourNiveau"><xsl:with-param name="level" select="@level" /></xsl:call-template>	
						<xsl:apply-templates />
						<mdfa:aPourNotice>
							<mdfa:Notice rdf:about="{$id}-notice">
								<xsl:apply-templates mode="notice" />
							</mdfa:Notice>
						</mdfa:aPourNotice>									
					</mdfa:RessourceArchivistique>
				</dcterms:hasPart>
			</xsl:when>
			<xsl:otherwise>
				<!-- on n'est pas dans un parent et dans ce cas on génère la ressource directement -->
				<mdfa:RessourceArchivistique rdf:about="{$id}">
					<xsl:call-template name="aPourNiveau"><xsl:with-param name="level" select="@level" /></xsl:call-template>
					<xsl:apply-templates />
					<mdfa:aPourNotice>
						<mdfa:Notice rdf:about="{$id}-notice">
							<xsl:apply-templates mode="notice" />
						</mdfa:Notice>
					</mdfa:aPourNotice>					
				</mdfa:RessourceArchivistique>
			</xsl:otherwise>
		</xsl:choose>
		
	</xsl:template>
	
	<xsl:template match="custodhist">
		<mdfa:historiqueDeConservation><xsl:call-template name="extraire-p"><xsl:with-param name="e" select="." /></xsl:call-template></mdfa:historiqueDeConservation>
	</xsl:template>
	
	<xsl:template match="acqinfo">
		<mdfa:modalitesEntree>
			<mdfa:Valeur>
				<mdfa:valeurTextuelle><xsl:call-template name="extraire-p"><xsl:with-param name="e" select="." /></xsl:call-template></mdfa:valeurTextuelle>
			</mdfa:Valeur>
		</mdfa:modalitesEntree>
	</xsl:template>
	
	<xsl:template match="scopecontent">
		<mdfa:descriptionDuContenu><xsl:call-template name="extraire-p"><xsl:with-param name="e" select="." /></xsl:call-template></mdfa:descriptionDuContenu>
	</xsl:template>
	
	<xsl:template match="arrangement">
		<mdfa:modeDeClassement><xsl:call-template name="extraire-p"><xsl:with-param name="e" select="." /></xsl:call-template></mdfa:modeDeClassement>
	</xsl:template>
	
	<xsl:template match="prefercite">
		<mdfa:mentionsConseillees><xsl:call-template name="extraire-p"><xsl:with-param name="e" select="." /></xsl:call-template></mdfa:mentionsConseillees>
	</xsl:template>

	<xsl:template match="accessrestrict">
		<!-- cas du accessrestrict contenant du texte : correspond aux modalités d'accès -->
		<xsl:if test="p">
			<mdfa:modalitesAcces>
				<mdfa:Valeur>
					<mdfa:valeurTextuelle><xsl:call-template name="extraire-p"><xsl:with-param name="e" select="." /></xsl:call-template></mdfa:valeurTextuelle>
				</mdfa:Valeur>
			</mdfa:modalitesAcces>
		</xsl:if>

		<!-- traitement des sous-balises, comme legalstatus -->
		<xsl:apply-templates />
	</xsl:template>
	
	<xsl:template match="accessrestrict/legalstatus">
		<mdfa:statutJuridique>
			<mdfa:Valeur>
				<mdfa:valeurTextuelle><xsl:call-template name="extraire-p"><xsl:with-param name="e" select="." /></xsl:call-template></mdfa:valeurTextuelle>
			</mdfa:Valeur>
		</mdfa:statutJuridique>
	</xsl:template>
	
	<xsl:template match="userestrict">
		<mdfa:modalitesUtilisation>
			<mdfa:Valeur>
				<mdfa:valeurTextuelle><xsl:call-template name="extraire-p"><xsl:with-param name="e" select="." /></xsl:call-template></mdfa:valeurTextuelle>
			</mdfa:Valeur>
		</mdfa:modalitesUtilisation>
	</xsl:template>
	
	<xsl:template match="bibliography">
		<mdfa:referenceBibliographique>
			<xsl:call-template name="extraire-p"><xsl:with-param name="e" select="." /></xsl:call-template>
		</mdfa:referenceBibliographique>
	</xsl:template>

	
	
	<!-- ***** did and sub-elements ***** -->
	
	<xsl:template match="did">
		<xsl:apply-templates />
	</xsl:template>
	
	<xsl:template match="abstract">
		<dcterms:abstract><xsl:value-of select="." /></dcterms:abstract>
	</xsl:template>
	
	<xsl:template match="container">
		<!-- ? -->
	</xsl:template>
	
	<xsl:template match="dao">
		<mdfa:estIllustrePar>
			<!-- TODO : URI ? -->
			<foaf:Image rdf:about="{@href}">
				<xsl:if test="@title">
					<mdfa:legende><xsl:value-of select="@title" /></mdfa:legende>
				</xsl:if>
			</foaf:Image>
		</mdfa:estIllustrePar>
	</xsl:template>
	
	<xsl:template match="daogrp">
		<!-- ? -->
	</xsl:template>
	
	<xsl:template match="langmaterial">
		<dcterms:language><xsl:value-of select="." /></dcterms:language>
	</xsl:template>
	
	<xsl:template match="materialspec">
		<!-- ? -->
	</xsl:template>
	
	<xsl:template match="note">
		<!-- ? -->
	</xsl:template>
	
	<xsl:template match="origination">
		<!-- TODO : sans doute faire un choix ? -->
		<mdfa:aPourOrigineQualifiee>
			<mdfa:RelationOrigine>
				<mdfa:valeurTextuelle><xsl:value-of select="normalize-space(.)" /></mdfa:valeurTextuelle>
			</mdfa:RelationOrigine>
		</mdfa:aPourOrigineQualifiee>
		<mdfa:aPourOrigine>
			<foaf:Agent>
				<foaf:name><xsl:value-of select="normalize-space(.)" /></foaf:name>
				<mdfa:biographie>
					<!-- aller chercher la biographie du producteur sur bioghist -->
					<xsl:call-template name="extraire-p"><xsl:with-param name="e" select="parent::did/parent::*/bioghist" /></xsl:call-template>
				</mdfa:biographie>
			</foaf:Agent>
		</mdfa:aPourOrigine>
	</xsl:template>
	
	<xsl:template match="physdesc">
		<xsl:if test="normalize-space(text()) != ''">
			<dcterms:medium>
				<rdf:Description>
					<mdfa:valeurTextuelle><xsl:value-of select="normalize-space(text())" /></mdfa:valeurTextuelle>
				</rdf:Description>
			</dcterms:medium>
		</xsl:if>
		<!-- extent -->
		<xsl:apply-templates />
	</xsl:template>
	<xsl:template match="physdesc/extent">
		<mdfa:nombreUnites><xsl:value-of select="normalize-space(.)" /></mdfa:nombreUnites>
	</xsl:template>
	
	<xsl:template match="physloc">
		<!-- ? -->
	</xsl:template>
	
	<!-- The institution or agency responsible for providing intellectual access to the materials being described. -->
	<xsl:template match="repository">
		<mdfa:aPourGestionnaireIntellectuel>
			<foaf:Agent>
				<foaf:name><xsl:value-of select="normalize-space(.)" /></foaf:name>
			</foaf:Agent>
		</mdfa:aPourGestionnaireIntellectuel>
	</xsl:template>
	
	<xsl:template match="unitdate">
		<mdfa:datesContenu>			
			<time:Interval>
				<rdfs:label><xsl:value-of select="." /></rdfs:label>
			</time:Interval>
		</mdfa:datesContenu>
	</xsl:template>
	
	<xsl:template match="unitid">
		<mdfa:cote><xsl:value-of select="." /></mdfa:cote>
	</xsl:template>
	
	<xsl:template match="unittitle">
		<!-- TODO : sous-balises ? -->
		<rdfs:label><xsl:value-of select="." /></rdfs:label>
	</xsl:template>
	
	<!-- ***** fin did ***** -->
	
	
	<!-- ***** traitement des notices ***** -->
	
	<xsl:template match="processinfo" mode="notice">
		<!-- il y a une valeur, ce sera notre redacteur de notice -->
		<xsl:if test="p">
			<dcterms:creator>
				<xsl:call-template name="extraire-p"><xsl:with-param name="e" select="." /></xsl:call-template>
			</dcterms:creator>
		</xsl:if>

		<!-- traitement des sous-balises -->
		<xsl:apply-templates mode="notice" />
	</xsl:template>
	
	<!-- ***** FIN traitement des notices ***** -->
	
	
	<!-- ***** traitement des descripteurs (controlaccess) ***** -->
	
	<xsl:template match="controlaccess">
		<xsl:apply-templates />
	</xsl:template>
	
	<xsl:template match="controlaccess/corpname">
		<mdfa:aPourSujetOrganisation>
			<foaf:Organization>
				<foaf:name><xsl:value-of select="." /></foaf:name>
			</foaf:Organization>
		</mdfa:aPourSujetOrganisation>
	</xsl:template>
	
	<xsl:template match="controlaccess/famname">
		<mdfa:aPourSujetFamille>
			<mdfa:Famille>
				<foaf:name><xsl:value-of select="." /></foaf:name>
			</mdfa:Famille>
		</mdfa:aPourSujetFamille>
	</xsl:template>
	
	<xsl:template match="controlaccess/function">
		<mdfa:refleteFonction>
			<mdfa:Fonction>
				<rdfs:label><xsl:value-of select="." /></rdfs:label>
			</mdfa:Fonction>
		</mdfa:refleteFonction>
	</xsl:template>
	
	<xsl:template match="controlaccess/genreform">
		<!-- on met cela comme type de la ressource -->
		<dcterms:type>
			<mdfa:TypeDeRessource>
				<rdfs:label><xsl:value-of select="." /></rdfs:label>
			</mdfa:TypeDeRessource>
		</dcterms:type>
	</xsl:template>
	
	<xsl:template match="controlaccess/geogname">
		<mdfa:aPourSujetLieu>
			<mdfa:Lieu>
				<rdfs:label><xsl:value-of select="." /></rdfs:label>
			</mdfa:Lieu>
		</mdfa:aPourSujetLieu>
	</xsl:template>
	
	<xsl:template match="controlaccess/occupation">
		<!-- profession. pas de correspondance dans MDFA ? -->
	</xsl:template>
	
	<xsl:template match="controlaccess/persname">
		<mdfa:aPourSujetPersonne>
			<foaf:Person>
				<foaf:name><xsl:value-of select="." /></foaf:name>
			</foaf:Person>
		</mdfa:aPourSujetPersonne>
	</xsl:template>
	
	<xsl:template match="controlaccess/subject">
		<mdfa:aPourSujetMatiere>
			<skos:Concept>
				<rdfs:label><xsl:value-of select="." /></rdfs:label>
			</skos:Concept>
		</mdfa:aPourSujetMatiere>
	</xsl:template>
	
	<!-- ***** FIN traitement des descripteurs -->
	
	<xsl:template name="aPourNiveau">
		<xsl:param name="level" />
		<xsl:if test="$level">
			<mdfa:aPourNiveau rdf:resource="http://www.anaphore.eu/tables/niveau/{$level}" />
		</xsl:if>
	</xsl:template>
	
	<xsl:template name="extraire-p">
		<xsl:param name="e" />
		<xsl:choose>
			<xsl:when test="$e/p">
				<xsl:apply-templates select="$e/p" mode="extraire-p" />
			</xsl:when>
			<xsl:otherwise><xsl:value-of select="normalize-space($e[text()])" /></xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	
	<xsl:template match="p" mode="extraire-p">
		<xsl:value-of select="normalize-space(.)" />
		<!--  Ajouter un saut de ligne si on n'est pas sur le dernier paragraphe -->
		<xsl:if test="following-sibling::p"><xsl:text>
</xsl:text>		
		</xsl:if>
	</xsl:template>
	
	
	<!-- ***** XSL BUILT-INS ***** -->
	
	<!-- template pour matcher tous les elements non-matches et ne rien faire avec -->
	<xsl:template match="*" />
	
	<!-- template pour matcher tous les textes non-matches -->
	<!-- si non-present, plein de sauts de lignes serons insérés dans le XML resultat -->
	<xsl:template match="text()|@*"></xsl:template>
	
	<!-- ***** XSL BUILT-INS - notices ***** -->
	
	<!-- template pour matcher tous les elements non-matches et ne rien faire avec -->
	<xsl:template match="*" mode="notice" />
	
	<!-- template pour matcher tous les textes non-matches -->
	<!-- si non-present, plein de sauts de lignes serons insérés dans le XML resultat -->
	<xsl:template match="text()|@*" mode="notice"></xsl:template>
	
</xsl:stylesheet>

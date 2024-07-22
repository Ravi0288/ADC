<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet
	version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:eml="https://eml.ecoinformatics.org/eml-2.2.0"
	xmlns:gmd="http://www.isotc211.org/2005/gmd"
	xmlns:gmx="http://www.isotc211.org/2005/gmx"
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xmlns:srv="http://www.isotc211.org/2005/srv"
	xmlns:gco="http://www.isotc211.org/2005/gco"
	xmlns:gts="http://www.isotc211.org/2005/gts"
	xmlns:gml="http://www.opengis.net/gml"
	xmlns:xlink="http://www.w3.org/1999/xlink"
	xmlns:date="http://exslt.org/dates-and-times"
	exclude-result-prefixes="date">
	<xsl:output method="xml"/>

	<xsl:template match="eml:eml">
		<gmd:MD_Metadata
			xmlns:gmd="http://www.isotc211.org/2005/gmd"
			xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
			xmlns:srv="http://www.isotc211.org/2005/srv"
			xmlns:gco="http://www.isotc211.org/2005/gco"
			xmlns:gts="http://www.isotc211.org/2005/gts"
			xmlns:gml="http://www.opengis.net/gml"
			xsi:schemaLocation="http://www.isotc211.org/2005/gmd http://schemas.opengis.net/iso/19139/20060504/gmd/gmd.xsd">
			<gmd:fileIdentifier>
				<xsl:variable name="lbl" select="substring-before(@packageId, '.')"/>
				<xsl:variable name="num" select="substring-before(substring-after(@packageId, '.'),'.')"/>
				<xsl:call-template name="writeCharacterString">
					
				
					<xsl:with-param name="stringToWrite" select="concat($lbl,'.',$num)"/>
				</xsl:call-template>
			</gmd:fileIdentifier>
			
			<gmd:language>

				<xsl:call-template name="writeCharacterString">

					<xsl:with-param name="stringToWrite">

						<xsl:choose>

							<xsl:when test="dataset/@xml:lang">
								<xsl:value-of select="dataset/@xml:lang"/>
							</xsl:when>

							<xsl:otherwise>
								<xsl:value-of select="'eng'"/>
							</xsl:otherwise>
						</xsl:choose>
					</xsl:with-param>
				</xsl:call-template>
			</gmd:language>
			<gmd:characterSet>

				<xsl:call-template name="writeCodelist">

					<xsl:with-param name="codeListName" select="'gmd:MD_CharacterSetCode'"/>

					<xsl:with-param name="codeListValue" select="'utf8'"/>
				</xsl:call-template>
			</gmd:characterSet>
			<gmd:hierarchyLevel>

				<xsl:call-template name="writeCodelist">

					<xsl:with-param name="codeListName" select="'gmd:MD_ScopeCode'"/>

					<xsl:with-param name="codeListValue" select="'dataset'"/>
				</xsl:call-template>
			</gmd:hierarchyLevel>

			<xsl:for-each select="dataset/metadataProvider">
				<gmd:contact>

					<xsl:call-template name="emlPerson2ResponsibleParty">
						<xsl:with-param name="roleCode" select="'pointOfContact'"/>
					</xsl:call-template>
				</gmd:contact>
			</xsl:for-each>
			<gmd:dateStamp>
				<!-- generate a date/time stamp for the metadata document -->
				<gco:DateTime>
					<xsl:value-of select="date:date-time()"/>
				</gco:DateTime>
			</gmd:dateStamp>
			<gmd:metadataStandardName>
				<gco:CharacterString>ISO 19115</gco:CharacterString>
			</gmd:metadataStandardName>
			<gmd:metadataStandardVersion>
				<gco:CharacterString>2003</gco:CharacterString>
			</gmd:metadataStandardVersion>

			<gmd:identificationInfo>
				<gmd:MD_DataIdentification>
					<gmd:citation>
						<gmd:CI_Citation>
							<!-- added conditional in case xml:lang attribute is not present -->

							<xsl:choose>

								<xsl:when test="dataset/title/@xml:lang='eng'">
									<gmd:title>
										<gco:CharacterString>
											<xsl:value-of select="dataset/title"/>
										</gco:CharacterString>
									</gmd:title>
								</xsl:when>

								<xsl:otherwise>
									<gmd:title>
										<gco:CharacterString>
											<xsl:value-of select="dataset/title"/>
										</gco:CharacterString>
									</gmd:title>
								</xsl:otherwise>
							</xsl:choose>

							<xsl:if test="dataset/shortName">
								<gmd:alternateTitle>
									<gco:CharacterString>
										<xsl:value-of select="dataset/shortName"/>
									</gco:CharacterString>
								</gmd:alternateTitle>
							</xsl:if>
							<gmd:date>
								<gmd:CI_Date>
									<gmd:date>
										<gco:Date>
											<xsl:value-of select="dataset/pubDate"/>
										</gco:Date>
									</gmd:date>
									<gmd:dateType>

										<xsl:call-template name="writeCodelist">

											<xsl:with-param name="codeListName" select="'gmd:CI_DateTypeCode'"/>

											<xsl:with-param name="codeListValue" select="'publication'"/>
										</xsl:call-template>
									</gmd:dateType>
								</gmd:CI_Date>
							</gmd:date>

							<xsl:choose>
							<!-- <xsl:if test="dataset/alternateIdentifier and (alternateIdentifier/@system='https://doi.org')"> -->
							       <xsl:when test="dataset/alternateIdentifier and (dataset/alternateIdentifier/@system='https://doi.org')">
								   		<gmd:identifier>
											<gmd:MD_Identifier>
												
												<gmd:authority>
													<gmd:CI_Citation>
														<gmd:title>
															<gco:CharacterString>Dataset DOI</gco:CharacterString>
														</gmd:title>
														<gmd:date>
															<gmd:CI_Date>
																<gmd:date>
																	<gco:Date>2021-01-26</gco:Date>
																</gmd:date>
																<gmd:dateType>
																	<gmd:CI_DateTypeCode codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#CI_DateTypeCode" codeListValue="publication"/>
																</gmd:dateType>
															</gmd:CI_Date>
														</gmd:date>
													</gmd:CI_Citation>
												</gmd:authority>
												<gmd:code>
													<gco:CharacterString>
														<xsl:value-of select="substring-after(dataset/alternateIdentifier,'doi:')"/>
													</gco:CharacterString>
												</gmd:code>
											</gmd:MD_Identifier>
										</gmd:identifier>
								   </xsl:when>
								    
								   <xsl:otherwise>
										<gmd:identifier>
											<gmd:MD_Identifier>
												<gmd:code>
													<gco:CharacterString>
														<xsl:value-of select="dataset/alternateIdentifier"/>
													</gco:CharacterString>
												</gmd:code>
											</gmd:MD_Identifier>
										</gmd:identifier>
								</xsl:otherwise>
								   
							<!-- </xsl:if> -->
							</xsl:choose>
							
						
								
								
							<xsl:for-each select="//description/section">
								<xsl:if test="title='References'">
								<xsl:for-each select="para">
									<gmd:identifier>
										<gmd:MD_Identifier>
											
											<gmd:authority>
												<gmd:CI_Citation>
													<gmd:title>
														<gco:CharacterString>Related Article</gco:CharacterString>
													</gmd:title>
													<gmd:date>
														<gmd:CI_Date>
															<gmd:date>
																<gco:Date>2015-04-04</gco:Date>
															</gmd:date>
															<gmd:dateType>
																<gmd:CI_DateTypeCode codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#CI_DateTypeCode" codeListValue="creation"/>
															</gmd:dateType>
														</gmd:CI_Date>
													</gmd:date>
													<gmd:edition>
														<gco:CharacterString>1</gco:CharacterString>
													</gmd:edition>
													
													<gmd:otherCitationDetails>
														<gco:CharacterString>
															<xsl:value-of select="."/>
														</gco:CharacterString>
													</gmd:otherCitationDetails>
													</gmd:CI_Citation>
											</gmd:authority>
											<gmd:code>
												<gco:CharacterString>
													
												</gco:CharacterString>
											</gmd:code>
										</gmd:MD_Identifier>
									</gmd:identifier>
									</xsl:for-each>
									</xsl:if>
							</xsl:for-each>
								
							
							<xsl:for-each select="dataset/creator">
								<gmd:citedResponsibleParty>

									<xsl:call-template name="emlPerson2ResponsibleParty">
										<xsl:with-param name="roleCode" select="'originator'"/>
									</xsl:call-template>
								</gmd:citedResponsibleParty>
							</xsl:for-each>

							

							<!-- <xsl:for-each select="dataset/publisher">
								<gmd:citedResponsibleParty>

									<xsl:call-template name="emlPerson2ResponsibleParty">
										<xsl:with-param name="roleCode" select="'publisher'"/>
									</xsl:call-template>
								</gmd:citedResponsibleParty>
							</xsl:for-each> -->
							<gmd:citedResponsibleParty>
								<gmd:CI_ResponsibleParty>
									<gmd:organisationName>
										<gco:CharacterString>Environmental Data Initiative (EDI)</gco:CharacterString>
									</gmd:organisationName>
									<gmd:contactInfo>
										<gmd:CI_Contact>
											<gmd:onlineResource>
												<gmd:CI_OnlineResource>
													<gmd:linkage>
														<gmd:URL>https://portal.edirepository.org/nis</gmd:URL>
													</gmd:linkage>
													<gmd:protocol>
														<gco:CharacterString>HTTP</gco:CharacterString>
													</gmd:protocol>
													<gmd:applicationProfile>
														<gco:CharacterString>Web Browser</gco:CharacterString>
													</gmd:applicationProfile>
													<gmd:name>
														<gco:CharacterString>Environmental Data Initiative (EDI)</gco:CharacterString>
													</gmd:name>
													<gmd:description>
														<gco:CharacterString>The EDI Data Portal contains environmental and ecological data packages contributed by a number of participating organizations. Data providers make every effort to release data in a timely fashion and with attention to accurate, well-designed and well-documented data. To understand data fully, please read the associated metadata and contact data providers if you have any questions. Data may be used in a manner conforming with the license information found in the “Intellectual Rights” section of the data package metadata or defaults to the EDI Data Policy. The Environmental Data Initiative shall not be liable for any damages resulting from misinterpretation or misuse of the data or metadata.</gco:CharacterString>
													</gmd:description>
													<gmd:function>
														<gmd:CI_OnLineFunctionCode codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/gmxCodelists.xml#CI_OnLineFunctionCode" codeListValue="information">information</gmd:CI_OnLineFunctionCode>
													</gmd:function>
												</gmd:CI_OnlineResource>
											</gmd:onlineResource>
										</gmd:CI_Contact>
									</gmd:contactInfo>
									<gmd:role>
										<gmd:CI_RoleCode codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/gmxCodelists.xml#CI_RoleCode" codeListValue="publisher">Publisher</gmd:CI_RoleCode>
									</gmd:role>
								</gmd:CI_ResponsibleParty>
							</gmd:citedResponsibleParty>
						</gmd:CI_Citation>
					</gmd:citation>
					
					<gmd:abstract>
						<xsl:variable name="paragraph">
								<xsl:for-each select="dataset/abstract/section/para | dataset/abstract/para" >
									<xsl:choose>
									    <xsl:when test="*">
												<xsl:value-of select="normalize-space(*/text())"/>
									    </xsl:when>
									    <xsl:otherwise>
												<xsl:value-of select="normalize-space(.)"/>
									    </xsl:otherwise>
									</xsl:choose>
								</xsl:for-each>
						</xsl:variable>
						<xsl:call-template name="writeCharacterString">
							<xsl:with-param name="stringToWrite" select="$paragraph"/>
						</xsl:call-template>

						<!--
						<xsl:call-template name="writeCharacterString">
							<xsl:with-param name="stringToWrite" select="dataset/abstract/para"/>
						</xsl:call-template>
						-->
					</gmd:abstract>
					<gmd:purpose>

						<xsl:call-template name="writeCharacterString">

							<xsl:with-param name="stringToWrite">

								<xsl:if test="dataset/purpose/title">
									<xsl:value-of select="concat(dataset/purpose/title,': ')"/>
								</xsl:if>	

								<xsl:for-each select="dataset/purpose/para">
									<xsl:value-of select="concat(.,' ')"/>
								</xsl:for-each>
							</xsl:with-param>
						</xsl:call-template>
					</gmd:purpose>

					<!-- <xsl:for-each select="dataset/contact">
						<gmd:pointOfContact>

							<xsl:call-template name="emlPerson2ResponsibleParty">
								<xsl:with-param name="roleCode" select="'pointOfContact'"/>
							</xsl:call-template>
						</gmd:pointOfContact>
					</xsl:for-each> -->
					<gmd:pointOfContact>
						<gmd:CI_ResponsibleParty>
							<gmd:individualName>
								<gco:CharacterString></gco:CharacterString>
							</gmd:individualName>
							<gmd:organisationName>
								<gco:CharacterString>Data Manager</gco:CharacterString>
							</gmd:organisationName>
							<gmd:positionName gco:nilReason="missing"/>
							<gmd:contactInfo>
								<gmd:CI_Contact>
									<gmd:phone>
										<gmd:CI_Telephone>
											<gmd:voice gco:nilReason="missing"/>
										</gmd:CI_Telephone>
									</gmd:phone>
									<gmd:address>
										<gmd:CI_Address>
											<gmd:city gco:nilReason="missing"/>
											<gmd:administrativeArea gco:nilReason="missing"/>
											<gmd:postalCode gco:nilReason="missing"/>
											<gmd:country gco:nilReason="missing"/>
											<gmd:electronicMailAddress>
												<gco:CharacterString>jornada.data@nmsu.edu</gco:CharacterString>
											</gmd:electronicMailAddress>
										</gmd:CI_Address>
									</gmd:address>
									<gmd:onlineResource>
										<gmd:CI_OnlineResource>
											<gmd:linkage gco:nilReason="missing"/>
										</gmd:CI_OnlineResource>
									</gmd:onlineResource>
									<gmd:hoursOfService gco:nilReason="missing"/>
									<gmd:contactInstructions gco:nilReason="missing"/>
								</gmd:CI_Contact>
							</gmd:contactInfo>
							<gmd:role>
								<gmd:CI_RoleCode codeList="https://www.ngdc.noaa.gov/metadata/published/xsd/schema/resources/Codelist/gmxCodelists.xml#gmd:CI_RoleCode" codeListValue="pointOfContact">pointOfContact</gmd:CI_RoleCode>
							</gmd:role>
						</gmd:CI_ResponsibleParty>
					</gmd:pointOfContact>		
						
					<gmd:resourceMaintenance test="dataset/maintenance/maintenanceUpdateFrequency">
						<gmd:MD_MaintenanceInformation>
							<gmd:maintenanceAndUpdateFrequency>
								<gmd:MD_MaintenanceFrequencyCode codeListValue="unknown" codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#MD_MaintenanceFrequencyCode"/>
							</gmd:maintenanceAndUpdateFrequency>
						</gmd:MD_MaintenanceInformation>
					</gmd:resourceMaintenance>			
					<xsl:if test="dataset/keywordSet">

						<xsl:for-each select="dataset/keywordSet">
							<xsl:choose>
								<xsl:when test="(keywordThesaurus and not(keywordThesaurus = 'https://apps.usgs.gov/thesaurus/thesaurus-full.php?thcode=15')) or not(keywordThesaurus)">
									<!-- <xsl:if test="keywordThesaurus != 'https://apps.usgs.gov/thesaurus/thesaurus-full.php?thcode=15'"> -->
										<gmd:descriptiveKeywords>
											<gmd:MD_Keywords>

												<xsl:for-each select="keyword">
													<gmd:keyword>
														<gco:CharacterString>
															<xsl:value-of select="translate(., '&quot;', '')"/>
														</gco:CharacterString>
													</gmd:keyword>
												</xsl:for-each>

												<gmd:thesaurusName>
													<gmd:CI_Citation xmlns:geonet="http://www.fao.org/geonetwork">
														<gmd:title>
															<gco:CharacterString>Keyword Thesaurus Title</gco:CharacterString>
														</gmd:title>
													</gmd:CI_Citation>
												</gmd:thesaurusName>
											</gmd:MD_Keywords>
										</gmd:descriptiveKeywords>
									<!-- </xsl:if> -->
								</xsl:when>
      						</xsl:choose>
						</xsl:for-each>
					</xsl:if>
					<gmd:descriptiveKeywords>
						<gmd:MD_Keywords>
							<gmd:keyword>
								<gco:CharacterString>216</gco:CharacterString>
							</gmd:keyword>
							<gmd:keyword>
								<gco:CharacterString>Data.gov</gco:CharacterString>
							</gmd:keyword>
							<gmd:keyword>
								<gco:CharacterString>ARS</gco:CharacterString>
							</gmd:keyword>
							<gmd:keyword>
								<gco:CharacterString>NSF > LTER=Long-Term Ecological Research</gco:CharacterString>
							</gmd:keyword>
							<gmd:keyword>
								<gco:CharacterString>USDA > ARS > Natural Resources and Sustainable Agricultural Systems National Program > 216 Agricultural System Competitiveness and Sustainability</gco:CharacterString>
							</gmd:keyword>
							<gmd:type>
									<gmd:MD_KeywordTypeCode codeListValue="theme" codeList="http://www.ngdc.noaa.gov/metadata/published/xsd/schema/resources/Codelist/gmxCodelists.xml#MD_KeywordTypeCode">theme</gmd:MD_KeywordTypeCode>
								</gmd:type>
							<gmd:thesaurusName>
								<gmd:CI_Citation xmlns:geonet="http://www.fao.org/geonetwork">
									<gmd:title>
										<gco:CharacterString>Keyword Thesaurus Title</gco:CharacterString>
									</gmd:title>
								</gmd:CI_Citation>
							</gmd:thesaurusName>
						</gmd:MD_Keywords>
					</gmd:descriptiveKeywords>
					
					<xsl:variable name="geographicalCoverage">
						<xsl:for-each select="dataset/coverage/geographicCoverage/geographicDescription">
							<xsl:value-of select="concat(.,'  ')"/>
						</xsl:for-each>
					</xsl:variable>
					<gmd:descriptiveKeywords >
						<gmd:MD_Keywords>
							<gmd:keyword>
								<gco:CharacterString><xsl:value-of select="$geographicalCoverage"/></gco:CharacterString>
							</gmd:keyword>
							<gmd:type>
									<gmd:MD_KeywordTypeCode codeListValue="place" codeList="http://www.ngdc.noaa.gov/metadata/published/xsd/schema/resources/Codelist/gmxCodelists.xml#MD_KeywordTypeCode">place</gmd:MD_KeywordTypeCode>
								</gmd:type>
							<gmd:thesaurusName>
								<gmd:CI_Citation xmlns:geonet="http://www.fao.org/geonetwork"
										uuid="36abb798-de40-4d75-ba45-864b43b2a49e">
										<gmd:title>
											<gco:CharacterString>Geographical Coverage Location Description</gco:CharacterString>
										</gmd:title>
										<gmd:alternateTitle>
											<gco:CharacterString>Spatial Geographical Coverage Location</gco:CharacterString>
										</gmd:alternateTitle>
										<gmd:date gco:nilReason="unknown"/>
										<gmd:edition gco:nilReason="unknown"/>
									</gmd:CI_Citation>
							</gmd:thesaurusName>
						</gmd:MD_Keywords>
					</gmd:descriptiveKeywords>	
								
					<gmd:descriptiveKeywords>
						<gmd:MD_Keywords>
							<gmd:keyword>
								<gco:CharacterString xmlns:geonet="http://www.fao.org/geonetwork">Department of
									Agriculture=5 > National Research=005:040</gco:CharacterString>
							</gmd:keyword>
							<gmd:type>
								<gmd:MD_KeywordTypeCode
									codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#MD_KeywordTypeCode"
									codeListValue="theme"/>
							</gmd:type>
							<gmd:thesaurusName>
								<gmd:CI_Citation xmlns:geonet="http://www.fao.org/geonetwork"
									uuid="36abb798-de40-4d75-ba45-864b43b2a49e">
									<gmd:title>
										<gco:CharacterString>Federal Program Inventory</gco:CharacterString>
									</gmd:title>
									<gmd:alternateTitle>
										<gco:CharacterString>US Federal Program Codes</gco:CharacterString>
									</gmd:alternateTitle>
									<gmd:date>
										<gmd:CI_Date>
											<gmd:date>
												<gco:Date>2013-09-16</gco:Date>
											</gmd:date>
											<gmd:dateType>
												<gmd:CI_DateTypeCode
													codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#CI_DateTypeCode"
													codeListValue="revision"/>
											</gmd:dateType>
										</gmd:CI_Date>
									</gmd:date>
									<gmd:edition>
										<gco:CharacterString>Fiscal Year 2013</gco:CharacterString>
									</gmd:edition>
									<gmd:citedResponsibleParty>
										<gmd:CI_ResponsibleParty uuid="f1cc15d4-efc7-43d0-97d7-c619a824bfd0">
											<gmd:organisationName>
												<gco:CharacterString>Office of Management and
													Budget</gco:CharacterString>
											</gmd:organisationName>
											<gmd:contactInfo>
												<gmd:CI_Contact>
													<gmd:onlineResource>
														<gmd:CI_OnlineResource>
															<gmd:linkage>
																<gmd:URL>https://www.whitehouse.gov/omb</gmd:URL>
															</gmd:linkage>
															<gmd:protocol>
																<gco:CharacterString>HTTP</gco:CharacterString>
															</gmd:protocol>
															<gmd:applicationProfile>
																<gco:CharacterString>Web Browser</gco:CharacterString>
															</gmd:applicationProfile>
															<gmd:name>
																<gco:CharacterString>Office of Management and
																	Budget</gco:CharacterString>
															</gmd:name>
															<gmd:description>
																<gco:CharacterString>The Office of Management and Budget
																	(OMB) serves the President of the United States in
																	overseeing the implementation of his vision across
																	the Executive Branch. Specifically, OMB’s mission is
																	to assist the President in meeting his policy,
																	budget, management and regulatory objectives and to
																	fulfill the agency’s statutory
																	responsibilities.</gco:CharacterString>
															</gmd:description>
															<gmd:function>
																<gmd:CI_OnLineFunctionCode
																	codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#CI_OnLineFunctionCode"
																	codeListValue="information"/>
															</gmd:function>
														</gmd:CI_OnlineResource>
													</gmd:onlineResource>
												</gmd:CI_Contact>
											</gmd:contactInfo>
											<gmd:role>
												<gmd:CI_RoleCode
													codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#CI_RoleCode"
													codeListValue="originator "/>
											</gmd:role>
										</gmd:CI_ResponsibleParty>
									</gmd:citedResponsibleParty>
									<gmd:otherCitationDetails>
										<gco:CharacterString>Office of Management and budget. File
											FederalProgramInventory_FY13_MachineReadable_091613.xls, current loation
											unknown.</gco:CharacterString>
									</gmd:otherCitationDetails>
								</gmd:CI_Citation>
							</gmd:thesaurusName>
						</gmd:MD_Keywords>
					</gmd:descriptiveKeywords>

						<gmd:descriptiveKeywords>
							<gmd:MD_Keywords>
								<gmd:keyword>
									<gco:CharacterString>
										<xsl:value-of select="'Creative Commons Attribution'"/>
									</gco:CharacterString>
								</gmd:keyword>
								<gmd:type>
									<gmd:MD_KeywordTypeCode codeListValue="theme" codeList="http://www.ngdc.noaa.gov/metadata/published/xsd/schema/resources/Codelist/gmxCodelists.xml#MD_KeywordTypeCode">theme</gmd:MD_KeywordTypeCode>
								</gmd:type>
								<gmd:thesaurusName>
									<gmd:CI_Citation>
										<gmd:title>
										<gco:CharacterString>Ag Data Commons Data License Type</gco:CharacterString>
										</gmd:title>
									<gmd:alternateTitle>
									<gco:CharacterString>USDA National Agricultural Library Ag Data Commons Data License Type</gco:CharacterString>
									</gmd:alternateTitle>
									<gmd:date>
									<gmd:CI_Date>
									<gmd:date>
									<gco:Date>2016</gco:Date>
									</gmd:date>
									<gmd:dateType>
									<gmd:CI_DateTypeCode codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#CI_DateTypeCode" codeListValue="publication"/>
									</gmd:dateType>
									</gmd:CI_Date>
									</gmd:date>
									<gmd:edition gco:nilReason="missing">
									<gco:CharacterString/>
									</gmd:edition>
									<gmd:citedResponsibleParty>
									<gmd:CI_ResponsibleParty uuid="b5d99c43-4f69-444b-8bf2-185e1672110f">
									<gmd:individualName>
									<gco:CharacterString>Erin Antognoli</gco:CharacterString>
									</gmd:individualName>
									<gmd:organisationName>
									<gco:CharacterString>USDA National Agricultural Library</gco:CharacterString>
									</gmd:organisationName>
									<gmd:positionName>
									<gco:CharacterString>* Ag Data Commons Metadata Librarian</gco:CharacterString>
									</gmd:positionName>
									<gmd:contactInfo>
									<gmd:CI_Contact uuid="4c0071d9-d58f-4f59-b398-a0a6a50d1228">
									<gmd:phone>
									<gmd:CI_Telephone>
									<gmd:voice gco:nilReason="missing">
									<gco:CharacterString/>
									</gmd:voice>
									<gmd:facsimile gco:nilReason="missing">
									<gco:CharacterString/>
									</gmd:facsimile>
									</gmd:CI_Telephone>
									</gmd:phone>
									<gmd:address>
									<gmd:CI_Address>
									<gmd:deliveryPoint>
									<gco:CharacterString>10301 Baltimore Ave</gco:CharacterString>
									</gmd:deliveryPoint>
									<gmd:city>
									<gco:CharacterString>Beltsville</gco:CharacterString>
									</gmd:city>
									<gmd:administrativeArea>
									<gco:CharacterString>MD</gco:CharacterString>
									</gmd:administrativeArea>
									<gmd:postalCode>
									<gco:CharacterString>20705</gco:CharacterString>
									</gmd:postalCode>
									<gmd:country>
									<gco:CharacterString>United States of America</gco:CharacterString>
									</gmd:country>
									<gmd:electronicMailAddress>
									<gco:CharacterString>nal-adc-curator@ars.usda.gov</gco:CharacterString>
									</gmd:electronicMailAddress>
									</gmd:CI_Address>
									</gmd:address>
									<gmd:onlineResource>
									<gmd:CI_OnlineResource uuid="95889d18-443b-4683-9c18-f95ae6b21e06">
									<gmd:linkage>
									<gmd:URL>https://nal.usda.gov</gmd:URL>
									</gmd:linkage>
									<gmd:protocol>
									<gco:CharacterString>HTTP</gco:CharacterString>
									</gmd:protocol>
									<gmd:applicationProfile>
									<gco:CharacterString>Web Browser</gco:CharacterString>
									</gmd:applicationProfile>
									<gmd:name>
									<gco:CharacterString>USDA National Agricultural Library</gco:CharacterString>
									</gmd:name>
									<gmd:description>
									<gco:CharacterString>The National Agricultural Library is one of the national libraries of the United States and houses one of the world's largest collections devoted to agriculture and its related sciences.</gco:CharacterString>
									</gmd:description>
									<gmd:function>
									<gmd:CI_OnLineFunctionCode codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#CI_OnLineFunctionCode" codeListValue="information"/>
									</gmd:function>
									</gmd:CI_OnlineResource>
									</gmd:onlineResource>
									<gmd:contactInstructions>
									<gco:CharacterString>Ag Data Commons Metadata Contact</gco:CharacterString>
									</gmd:contactInstructions>
									</gmd:CI_Contact>
									</gmd:contactInfo>
									<gmd:role>
									<gmd:CI_RoleCode codeListValue="pointOfContact" codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#CI_RoleCode"/>
									</gmd:role>
									</gmd:CI_ResponsibleParty>
									</gmd:citedResponsibleParty>
									<gmd:otherCitationDetails>
									<gco:CharacterString>Creative Commons and other licenses recognized by Ag Data Commons</gco:CharacterString>
									</gmd:otherCitationDetails>
									</gmd:CI_Citation>
									</gmd:thesaurusName>
							</gmd:MD_Keywords>
						</gmd:descriptiveKeywords>
					
					<gmd:descriptiveKeywords>
						<gmd:MD_Keywords>
							<gmd:keyword>
								<gco:CharacterString>Department of Agriculture=5 > Agricultural Research Service=005:018</gco:CharacterString>
							</gmd:keyword>
							<gmd:type>
								<gmd:MD_KeywordTypeCode codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#MD_KeywordTypeCode" codeListValue="theme"/>
							</gmd:type>
							<gmd:thesaurusName>
								<gmd:CI_Citation xmlns:geonet="http://www.fao.org/geonetwork" uuid="6ba5a0f5-d960-4e34-9cc9-80e5217961a7">
									<gmd:title>
										<gco:CharacterString>OMB Bureau Codes</gco:CharacterString>
									</gmd:title>
									<gmd:alternateTitle>
										<gco:CharacterString>Office of Management and Budget Bureau Codes</gco:CharacterString>
									</gmd:alternateTitle>
									<gmd:date>
										<gmd:CI_Date>
											<gmd:date>
												<gco:Date>2016</gco:Date>
											</gmd:date>
											<gmd:dateType>
												<gmd:CI_DateTypeCode codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#CI_DateTypeCode" codeListValue="revision"/>
											</gmd:dateType>
										</gmd:CI_Date>
									</gmd:date>
									<gmd:edition>
										<gco:CharacterString>2016</gco:CharacterString>
									</gmd:edition>
									<gmd:citedResponsibleParty>
										<gmd:CI_ResponsibleParty uuid="2c1e8dfb-0975-4db8-9741-0e2b124dd64a">
											<gmd:organisationName>
												<gco:CharacterString>OMB Bureau Codes</gco:CharacterString>
											</gmd:organisationName>
											<gmd:contactInfo>
												<gmd:CI_Contact>
													<gmd:onlineResource>
														<gmd:CI_OnlineResource>
															<gmd:linkage>
																<gmd:URL>https://obamawhitehouse.archives.gov/sites/default/files/omb/assets/a11_current_year/app_c.pdf</gmd:URL>
															</gmd:linkage>
															<gmd:protocol>
																<gco:CharacterString>HTTP</gco:CharacterString>
															</gmd:protocol>
															<gmd:applicationProfile>
																<gco:CharacterString>Web Browser</gco:CharacterString>
															</gmd:applicationProfile>
															<gmd:name>
																<gco:CharacterString>APPENDIX C—LISTING OF OMB AGENCY/BUREAU AND TREASURY CODES</gco:CharacterString>
															</gmd:name>
															<gmd:description>
																<gco:CharacterString>In the MAX system, OMB assigns and uses agency and bureau codes, which are associated with agency and bureau titles that are published in the Budget.PDF file.</gco:CharacterString>
															</gmd:description>
															<gmd:function>
																<gmd:CI_OnLineFunctionCode codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#CI_OnLineFunctionCode" codeListValue="information"/>
															</gmd:function>
														</gmd:CI_OnlineResource>
													</gmd:onlineResource>
												</gmd:CI_Contact>
											</gmd:contactInfo>
											<gmd:role>
												<gmd:CI_RoleCode codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#CI_RoleCode" codeListValue="resourceProvider"/>
											</gmd:role>
										</gmd:CI_ResponsibleParty>
									</gmd:citedResponsibleParty>
									<gmd:otherCitationDetails>
										<gco:CharacterString>Complete list of bureau codes</gco:CharacterString>
									</gmd:otherCitationDetails>
								</gmd:CI_Citation>
							</gmd:thesaurusName>
						</gmd:MD_Keywords>
					</gmd:descriptiveKeywords>
					
					<gmd:descriptiveKeywords>
						<gmd:MD_Keywords>
							<gmd:keyword>
								<gco:CharacterString>Jornada Basin</gco:CharacterString>
							</gmd:keyword>
							<gmd:type>
								<gmd:MD_KeywordTypeCode codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#MD_KeywordTypeCode" codeListValue="place"/>
							</gmd:type>
						</gmd:MD_Keywords>
					</gmd:descriptiveKeywords>
					<gmd:descriptiveKeywords>
						<gmd:MD_Keywords>
							<gmd:keyword>
								<gco:CharacterString>United States Department of Agriculture > Agricultural Research Service > Long-Term Agroecosystem Research > Jornada Experimental Range</gco:CharacterString>
							</gmd:keyword>
							<gmd:keyword>
								<gco:CharacterString>USDA > ARS > Natural Resources and Sustainable Agricultural Systems National Program > 216 Agricultural System Competitiveness and Sustainability</gco:CharacterString>
							</gmd:keyword>
							<gmd:type>
								<gmd:MD_KeywordTypeCode codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#MD_KeywordTypeCode" codeListValue="theme"/>
							</gmd:type>
							<gmd:thesaurusName>
								<gmd:CI_Citation xmlns:geonet="http://www.fao.org/geonetwork" uuid="dea454ff-f5ca-441e-9f4e-3647c1a1725f">
									<gmd:title>
										<gco:CharacterString>Data Source Affiliation</gco:CharacterString>
									</gmd:title>
									<gmd:alternateTitle>
										<gco:CharacterString>USDA NAL Data Source Affiliation</gco:CharacterString>
									</gmd:alternateTitle>
									<gmd:date>
										<gmd:CI_Date>
											<gmd:date>
												<gco:Date>2017</gco:Date>
											</gmd:date>
											<gmd:dateType>
												<gmd:CI_DateTypeCode codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#CI_DateTypeCode" codeListValue="publication"/>
											</gmd:dateType>
										</gmd:CI_Date>
									</gmd:date>
									<gmd:edition gco:nilReason="missing">
										<gco:CharacterString/>
									</gmd:edition>
									<gmd:citedResponsibleParty>
										<gmd:CI_ResponsibleParty uuid="b5d99c43-4f69-444b-8bf2-185e1672110f">
											<gmd:individualName>
												<gco:CharacterString>Erin Antognoli</gco:CharacterString>
											</gmd:individualName>
											<gmd:organisationName>
												<gco:CharacterString>USDA National Agricultural Library</gco:CharacterString>
											</gmd:organisationName>
											<gmd:positionName>
												<gco:CharacterString>* Ag Data Commons Metadata Librarian</gco:CharacterString>
											</gmd:positionName>
											<gmd:contactInfo>
												<gmd:CI_Contact uuid="4c0071d9-d58f-4f59-b398-a0a6a50d1228">
													<gmd:phone>
														<gmd:CI_Telephone>
															<gmd:voice gco:nilReason="missing">
																<gco:CharacterString/>
															</gmd:voice>
															<gmd:facsimile gco:nilReason="missing">
																<gco:CharacterString/>
															</gmd:facsimile>
														</gmd:CI_Telephone>
													</gmd:phone>
													<gmd:address>
														<gmd:CI_Address>
															<gmd:deliveryPoint>
																<gco:CharacterString>10301 Baltimore Ave</gco:CharacterString>
															</gmd:deliveryPoint>
															<gmd:city>
																<gco:CharacterString>Beltsville</gco:CharacterString>
															</gmd:city>
															<gmd:administrativeArea>
																<gco:CharacterString>MD</gco:CharacterString>
															</gmd:administrativeArea>
															<gmd:postalCode>
																<gco:CharacterString>20705</gco:CharacterString>
															</gmd:postalCode>
															<gmd:country>
																<gco:CharacterString>United States of America</gco:CharacterString>
															</gmd:country>
															<gmd:electronicMailAddress>
																<gco:CharacterString>nal-adc-curator@ars.usda.gov</gco:CharacterString>
															</gmd:electronicMailAddress>
														</gmd:CI_Address>
													</gmd:address>
													<gmd:onlineResource>
														<gmd:CI_OnlineResource uuid="95889d18-443b-4683-9c18-f95ae6b21e06">
															<gmd:linkage>
																<gmd:URL>https://nal.usda.gov</gmd:URL>
															</gmd:linkage>
															<gmd:protocol>
																<gco:CharacterString>HTTP</gco:CharacterString>
															</gmd:protocol>
															<gmd:applicationProfile>
																<gco:CharacterString>Web Browser</gco:CharacterString>
															</gmd:applicationProfile>
															<gmd:name>
																<gco:CharacterString>USDA National Agricultural Library</gco:CharacterString>
															</gmd:name>
															<gmd:description>
																<gco:CharacterString>The National Agricultural Library is one of the national libraries of the United States and houses one of the world's largest collections devoted to agriculture and its related sciences.</gco:CharacterString>
															</gmd:description>
															<gmd:function>
																<gmd:CI_OnLineFunctionCode codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#CI_OnLineFunctionCode" codeListValue="information"/>
															</gmd:function>
														</gmd:CI_OnlineResource>
													</gmd:onlineResource>
													<gmd:contactInstructions>
														<gco:CharacterString>Ag Data Commons Metadata Contact</gco:CharacterString>
													</gmd:contactInstructions>
												</gmd:CI_Contact>
											</gmd:contactInfo>
											<gmd:role>
												<gmd:CI_RoleCode codeListValue="pointOfContact" codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#CI_RoleCode"/>
											</gmd:role>
										</gmd:CI_ResponsibleParty>
									</gmd:citedResponsibleParty>
									<gmd:otherCitationDetails>
										<gco:CharacterString>A controlled vocabulary to associate data and GIS layers with the data providers and affilated resarch networks.</gco:CharacterString>
									</gmd:otherCitationDetails>
								</gmd:CI_Citation>
							</gmd:thesaurusName>
						</gmd:MD_Keywords>
					</gmd:descriptiveKeywords>
					
					<!-- <gmd:descriptiveKeywords>
						<gmd:MD_Keywords>
							<gmd:keyword>
								<gco:CharacterString>National Science Foundation</gco:CharacterString>
							</gmd:keyword>
							<gmd:type>
								<gmd:MD_KeywordTypeCode codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#MD_KeywordTypeCode" codeListValue="theme"/>
							</gmd:type>
							<gmd:thesaurusName>
								<gmd:CI_Citation>
									<gmd:title>
										<gco:CharacterString>Crossref Funding</gco:CharacterString>
									</gmd:title>
									<gmd:alternateTitle>
										<gco:CharacterString>Crossref Open Funder Registry</gco:CharacterString>
									</gmd:alternateTitle>
									<gmd:date>
										<gmd:CI_Date>
											<gmd:date>
												<gco:Date>2016-08-11</gco:Date>
											</gmd:date>
											<gmd:dateType>
												<gmd:CI_DateTypeCode codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#CI_DateTypeCode" codeListValue="revision">revision</gmd:CI_DateTypeCode>
											</gmd:dateType>
										</gmd:CI_Date>
									</gmd:date>
									<gmd:edition>
										<gco:CharacterString>1.2</gco:CharacterString>
									</gmd:edition>
									<gmd:citedResponsibleParty>
										<gmd:CI_ResponsibleParty uuid="814c1d4e-330a-4755-9f61-45cc24af2a8c">
											<gmd:organisationName>
												<gco:CharacterString>crossref.org</gco:CharacterString>
											</gmd:organisationName>
											<gmd:contactInfo>
												<gmd:CI_Contact>
													<gmd:onlineResource>
														<gmd:CI_OnlineResource>
															<gmd:linkage>
																<gmd:URL>https://www.crossref.org/services/funder-registry/</gmd:URL>
															</gmd:linkage>
															<gmd:protocol>
																<gco:CharacterString>HTTP</gco:CharacterString>
															</gmd:protocol>
															<gmd:applicationProfile>
																<gco:CharacterString>Web Browser</gco:CharacterString>
															</gmd:applicationProfile>
															<gmd:name>
																<gco:CharacterString>Open Funder Registry</gco:CharacterString>
															</gmd:name>
															<gmd:description>
																<gco:CharacterString>Funding data provides a standard way to report funding sources for published scholarly research. Publishers deposit funding information from articles and other content using a standard taxonomy of funder names.</gco:CharacterString>
															</gmd:description>
															<gmd:function>
																<gmd:CI_OnLineFunctionCode codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/gmxCodelists.xml#CI_OnLineFunctionCode" codeListValue="information">information</gmd:CI_OnLineFunctionCode>
															</gmd:function>
														</gmd:CI_OnlineResource>
													</gmd:onlineResource>
												</gmd:CI_Contact>
											</gmd:contactInfo>
											<gmd:role>
												<gmd:CI_RoleCode codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/gmxCodelists.xml#CI_RoleCode" codeListValue="resourceProvider">Resource Provider</gmd:CI_RoleCode>
											</gmd:role>
										</gmd:CI_ResponsibleParty>
									</gmd:citedResponsibleParty>
									<gmd:otherCitationDetails>
										<gco:CharacterString>A common taxonomy of over 11,000 funding body names that funding data initiative participants should use to normalize Funder Names and IDs.</gco:CharacterString>
									</gmd:otherCitationDetails>
								</gmd:CI_Citation>
							</gmd:thesaurusName>
						</gmd:MD_Keywords>
					</gmd:descriptiveKeywords> -->
					<gmd:descriptiveKeywords>
						<gmd:MD_Keywords>
							<gmd:keyword><gco:CharacterString>agroecosystems</gco:CharacterString></gmd:keyword>
							<gmd:keyword><gco:CharacterString>rangelands</gco:CharacterString></gmd:keyword>
							<gmd:keyword><gco:CharacterString>sustainable agricultural intensification</gco:CharacterString></gmd:keyword>
							<gmd:type>
								<gmd:MD_KeywordTypeCode codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#MD_KeywordTypeCode" codeListValue="theme"/>
							</gmd:type>
							<gmd:thesaurusName>
								<gmd:CI_Citation xmlns:geonet="http://www.fao.org/geonetwork" uuid="ecbf87b1-ca15-41b5-bfe0-6589a975770b">
									<gmd:title>
										<gco:CharacterString>National Agricultural Library Thesaurus (NALT)</gco:CharacterString>
									</gmd:title>
									<gmd:date>
										<gmd:CI_Date>
											<gmd:date>
												<gco:Date>2015</gco:Date>
											</gmd:date>
											<gmd:dateType>
												<gmd:CI_DateTypeCode codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#CI_DateTypeCode" codeListValue="publication"/>
											</gmd:dateType>
										</gmd:CI_Date>
									</gmd:date>
									<gmd:edition>
										<gco:CharacterString>2015 Edition</gco:CharacterString>
									</gmd:edition>
									<gmd:citedResponsibleParty>
										<gmd:CI_ResponsibleParty uuid="4d172771-89ca-4561-8233-db638076a892">
											<gmd:organisationName>
												<gco:CharacterString>National Agricultural Library Thesaurus (NALT)</gco:CharacterString>
											</gmd:organisationName>
											<gmd:positionName>
												<gco:CharacterString>* Agricultural Reference</gco:CharacterString>
											</gmd:positionName>
											<gmd:contactInfo>
												<gmd:CI_Contact>
													<gmd:address>
														<gmd:CI_Address>
															<gmd:deliveryPoint>
																<gco:CharacterString>10301 Baltimore Ave</gco:CharacterString>
															</gmd:deliveryPoint>
															<gmd:city>
																<gco:CharacterString>Beltsville, MD</gco:CharacterString>
															</gmd:city>
															<gmd:postalCode>
																<gco:CharacterString>20705</gco:CharacterString>
															</gmd:postalCode>
															<gmd:country>
																<gco:CharacterString>United States of America</gco:CharacterString>
															</gmd:country>
															<gmd:electronicMailAddress>
																<gco:CharacterString>agref@ars.usda.gov</gco:CharacterString>
															</gmd:electronicMailAddress>
														</gmd:CI_Address>
													</gmd:address>
													<gmd:onlineResource>
														<gmd:CI_OnlineResource>
															<gmd:linkage>
																<gmd:URL>https://agclass.nal.usda.gov/</gmd:URL>
															</gmd:linkage>
															<gmd:protocol>
																<gco:CharacterString>HTTP</gco:CharacterString>
															</gmd:protocol>
															<gmd:applicationProfile>
																<gco:CharacterString>Web Browser</gco:CharacterString>
															</gmd:applicationProfile>
															<gmd:name>
																<gco:CharacterString>USDA National Agricultural Library Thesaurus and Glossary Home Page</gco:CharacterString>
															</gmd:name>
															<gmd:description>
																<gco:CharacterString>The Thesaurus and Glossary are online vocabulary tools of agricultural terms in English and Spanish and are cooperatively produced by the National Agricultural Library, USDA, and the Inter-American Institute for Cooperation on Agriculture as well as other Latin American agricultural institutions belonging to the Agriculture Information and Documentation Service of the Americas (SIDALC).</gco:CharacterString>
															</gmd:description>
															<gmd:function>
																<gmd:CI_OnLineFunctionCode codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#CI_OnLineFunctionCode" codeListValue="information"/>
															</gmd:function>
														</gmd:CI_OnlineResource>
													</gmd:onlineResource>
												</gmd:CI_Contact>
											</gmd:contactInfo>
											<gmd:role>
												<gmd:CI_RoleCode codeListValue="pointOfContact" codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#CI_RoleCode"/>
											</gmd:role>
										</gmd:CI_ResponsibleParty>
									</gmd:citedResponsibleParty>
									<gmd:otherCitationDetails>
										<gco:CharacterString>A controlled vocabulary of keywords to classify dataset records in Ag Data Commons</gco:CharacterString>
									</gmd:otherCitationDetails>
								</gmd:CI_Citation>
							</gmd:thesaurusName>
						</gmd:MD_Keywords>
					</gmd:descriptiveKeywords>
					<gmd:descriptiveKeywords>
						<gmd:MD_Keywords>
							<gmd:keyword>
								<gco:CharacterString>31922</gco:CharacterString>
							</gmd:keyword>
							<gmd:type>
								<gmd:MD_KeywordTypeCode codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#MD_KeywordTypeCode" codeListValue="theme"/>
							</gmd:type>
							<gmd:thesaurusName>
								<gmd:CI_Citation xmlns:geonet="http://www.fao.org/geonetwork" uuid="763005c9-762c-4a67-9fc1-5aaea4086568">
									<gmd:title>
										<gco:CharacterString>Ag Data Commons Keywords</gco:CharacterString>
									</gmd:title>
									<gmd:alternateTitle>
										<gco:CharacterString>USDA NAL Ag Data Commons Keywords</gco:CharacterString>
									</gmd:alternateTitle>
									<gmd:date>
										<gmd:CI_Date>
											<gmd:date>
												<gco:Date>2016</gco:Date>
											</gmd:date>
											<gmd:dateType>
												<gmd:CI_DateTypeCode codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#CI_DateTypeCode" codeListValue="publication"/>
											</gmd:dateType>
										</gmd:CI_Date>
									</gmd:date>
									<gmd:edition>
										<gco:CharacterString>ADC Keywords hierarchy v4</gco:CharacterString>
									</gmd:edition>
									<gmd:citedResponsibleParty>
										<gmd:CI_ResponsibleParty uuid="b5d99c43-4f69-444b-8bf2-185e1672110f">
											<gmd:individualName>
												<gco:CharacterString>Erin Antognoli</gco:CharacterString>
											</gmd:individualName>
											<gmd:organisationName>
												<gco:CharacterString>USDA National Agricultural Library</gco:CharacterString>
											</gmd:organisationName>
											<gmd:positionName>
												<gco:CharacterString>* Ag Data Commons Metadata Librarian</gco:CharacterString>
											</gmd:positionName>
											<gmd:contactInfo>
												<gmd:CI_Contact uuid="4c0071d9-d58f-4f59-b398-a0a6a50d1228">
													<gmd:phone>
														<gmd:CI_Telephone>
															<gmd:voice gco:nilReason="missing">
																<gco:CharacterString/>
															</gmd:voice>
															<gmd:facsimile gco:nilReason="missing">
																<gco:CharacterString/>
															</gmd:facsimile>
														</gmd:CI_Telephone>
													</gmd:phone>
													<gmd:address>
														<gmd:CI_Address>
															<gmd:deliveryPoint>
																<gco:CharacterString>10301 Baltimore Ave</gco:CharacterString>
															</gmd:deliveryPoint>
															<gmd:city>
																<gco:CharacterString>Beltsville</gco:CharacterString>
															</gmd:city>
															<gmd:administrativeArea>
																<gco:CharacterString>MD</gco:CharacterString>
															</gmd:administrativeArea>
															<gmd:postalCode>
																<gco:CharacterString>20705</gco:CharacterString>
															</gmd:postalCode>
															<gmd:country>
																<gco:CharacterString>United States of America</gco:CharacterString>
															</gmd:country>
															<gmd:electronicMailAddress>
																<gco:CharacterString>nal-adc-curator@ars.usda.gov</gco:CharacterString>
															</gmd:electronicMailAddress>
														</gmd:CI_Address>
													</gmd:address>
													<gmd:onlineResource>
														<gmd:CI_OnlineResource uuid="95889d18-443b-4683-9c18-f95ae6b21e06">
															<gmd:linkage>
																<gmd:URL>https://nal.usda.gov</gmd:URL>
															</gmd:linkage>
															<gmd:protocol>
																<gco:CharacterString>HTTP</gco:CharacterString>
															</gmd:protocol>
															<gmd:applicationProfile>
																<gco:CharacterString>Web Browser</gco:CharacterString>
															</gmd:applicationProfile>
															<gmd:name>
																<gco:CharacterString>USDA National Agricultural Library</gco:CharacterString>
															</gmd:name>
															<gmd:description>
																<gco:CharacterString>The National Agricultural Library is one of the national libraries of the United States and houses one of the world's largest collections devoted to agriculture and its related sciences.</gco:CharacterString>
															</gmd:description>
															<gmd:function>
																<gmd:CI_OnLineFunctionCode codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#CI_OnLineFunctionCode" codeListValue="information"/>
															</gmd:function>
														</gmd:CI_OnlineResource>
													</gmd:onlineResource>
													<gmd:contactInstructions>
														<gco:CharacterString>Ag Data Commons Metadata Contact</gco:CharacterString>
													</gmd:contactInstructions>
												</gmd:CI_Contact>
											</gmd:contactInfo>
											<gmd:role>
												<gmd:CI_RoleCode codeListValue="pointOfContact" codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#CI_RoleCode"/>
											</gmd:role>
										</gmd:CI_ResponsibleParty>
									</gmd:citedResponsibleParty>
									<gmd:otherCitationDetails>
										<gco:CharacterString>A controlled vocabulary of keywords to classify dataset records in Ag Data Commons</gco:CharacterString>
									</gmd:otherCitationDetails>
								</gmd:CI_Citation>
							</gmd:thesaurusName>
						</gmd:MD_Keywords>
					</gmd:descriptiveKeywords>
					
					<gmd:resourceConstraints>
						<gmd:MD_LegalConstraints>
							<gmd:accessConstraints>

								<xsl:call-template name="writeCodelist">

									<xsl:with-param name="codeListName" select="'gmd:MD_RestrictionCode'"/>

									<xsl:with-param name="codeListValue" select="'otherRestrictions'"/>
								</xsl:call-template>
							</gmd:accessConstraints>
							<gmd:useConstraints>

							</gmd:useConstraints>
							<gmd:otherConstraints>

								<xsl:call-template name="writeCharacterString">
									<xsl:with-param name="stringToWrite" select="dataset/intellectualRights"/>
								</xsl:call-template>
							</gmd:otherConstraints>

							<xsl:if test="*/physical/distribution/access">

								<xsl:for-each select="access/allow">
									<gmd:otherConstraints>
										<gco:CharacterString>
											<xsl:value-of select="concat(principal,': ',permission)"/>
										</gco:CharacterString>
									</gmd:otherConstraints>
								</xsl:for-each>
							</xsl:if>
						</gmd:MD_LegalConstraints>
					</gmd:resourceConstraints>
					<gmd:language>
						<gmd:LanguageCode codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/Codelist/ML_gmxCodelists.xml#LanguageCode" codeListValue="eng">English</gmd:LanguageCode>
					</gmd:language>
					<gmd:characterSet>
						<gmd:MD_CharacterSetCode codeList="http://www.isotc211.org/2005/resources/Codelist/gmxCodelists.xml#MD_CharacterSetCode" codeListValue=""/>
					</gmd:characterSet>

					<gmd:topicCategory>
						<gmd:MD_TopicCategoryCode>farming</gmd:MD_TopicCategoryCode>
					</gmd:topicCategory>
					<gmd:topicCategory>
						<gmd:MD_TopicCategoryCode>environment</gmd:MD_TopicCategoryCode>
					</gmd:topicCategory>
					<gmd:topicCategory>
						<gmd:MD_TopicCategoryCode>biota</gmd:MD_TopicCategoryCode>
					</gmd:topicCategory>
					<gmd:topicCategory>
						<gmd:MD_TopicCategoryCode>climatologyMeteorologyAtmosphere</gmd:MD_TopicCategoryCode>
					</gmd:topicCategory>
					<gmd:topicCategory>
						<gmd:MD_TopicCategoryCode>geoscientificInformation</gmd:MD_TopicCategoryCode>
					</gmd:topicCategory>
					<gmd:extent>
						<gmd:EX_Extent id="boundingExtent">

							<xsl:for-each select="dataset/coverage/geographicCoverage">
								<xsl:apply-templates select="."/>
							</xsl:for-each>

							<xsl:for-each select="dataset/coverage/temporalCoverage">

								<xsl:apply-templates select=".">

									<xsl:with-param name="timePeriodId">
										<xsl:number value="position()" format="1"/>
									</xsl:with-param>
								</xsl:apply-templates>
							</xsl:for-each>
						</gmd:EX_Extent>
					</gmd:extent>
				</gmd:MD_DataIdentification>
			</gmd:identificationInfo>

			<xsl:for-each select="//dataTable">
				<gmd:contentInfo>
					<gmd:MD_CoverageDescription>
						<gmd:attributeDescription>

							<xsl:attribute name="gco:nilReason">
								<xsl:value-of select="'unknown'"/>
							</xsl:attribute>
						</gmd:attributeDescription>
						<gmd:contentType>

							<xsl:call-template name="writeCodelist">

								<xsl:with-param name="codeListName" select="'gmd:MD_CoverageContentTypeCode'"/>

								<xsl:with-param name="codeListValue" select="'physicalMeasurement'"/>
							</xsl:call-template>
						</gmd:contentType>

						<xsl:for-each select="attributeList/attribute">
							<xsl:call-template name="writeVariable"/>
						</xsl:for-each>
					</gmd:MD_CoverageDescription>
				</gmd:contentInfo>
			</xsl:for-each>
			<gmd:distributionInfo>
				<gmd:MD_Distribution>
					<gmd:distributionFormat>
						<gmd:MD_Format>
							<gmd:name>

								<xsl:call-template name="writeCharacterString">
									<xsl:with-param name="stringToWrite" select="additionalMetadata/metadata/physical/dataFormat/externallyDefinedFormat/formatName"/>
								</xsl:call-template>
							</gmd:name>
							<gmd:version>

								<xsl:call-template name="writeCharacterString">
									<xsl:with-param name="stringToWrite" select="additionalMetadata/metadata/physical/dataFormat/externallyDefinedFormat/formatVersion"/>
								</xsl:call-template>
							</gmd:version>
						</gmd:MD_Format>
					</gmd:distributionFormat>

					<!-- <xsl:for-each select="dataset/distribution/online">
						<gmd:transferOptions>
							<gmd:MD_DigitalTransferOptions>
								<gmd:onLine>
									<gmd:CI_OnlineResource>
										<gmd:linkage>
											<gmd:URL>
												<xsl:value-of select="url"/>
											</gmd:URL>
										</gmd:linkage>
										<gmd:protocol>
											<gco:CharacterString>
												<xsl:value-of select="substring-before(url,':')"/>
											</gco:CharacterString>
										</gmd:protocol>
										<gmd:description>

											<xsl:call-template name="writeCharacterString">
												<xsl:with-param name="stringToWrite" select="description"/>
											</xsl:call-template>
										</gmd:description>
										<gmd:function>

											<xsl:call-template name="writeCodelist">

												<xsl:with-param name="codeListName" select="'gmd:CI_OnLineFunctionCode'"/>

												<xsl:with-param name="codeListValue" select="url/@function"/>
											</xsl:call-template>
										</gmd:function>
									</gmd:CI_OnlineResource>
								</gmd:onLine>
							</gmd:MD_DigitalTransferOptions>
						</gmd:transferOptions>
					</xsl:for-each> -->
					<!-- <gmd:transferOptions>
						<gmd:MD_DigitalTransferOptions>
							<xsl:variable name="scope" select="substring-before(@packageId, '.')"/>
							<xsl:variable name="num" select="substring-before(substring-after(@packageId, '.'),'.')"/>
				
							<gmd:onLine>
								<gmd:CI_OnlineResource>
									<gmd:linkage>
										<gmd:URL>
											<xsl:value-of select="concat('https://portal.edirepository.org/nis/mapbrowse?scope=',$scope,'&amp;identifier=',$num)"/>
										</gmd:URL>
									</gmd:linkage>
									<gmd:protocol>
										<gco:CharacterString/>
									</gmd:protocol>
									<gmd:description>
										<gco:CharacterString>Webpage with information and links to data files for download</gco:CharacterString>
									</gmd:description>
									<gmd:function>
										<gmd:CI_OnLineFunctionCode codeList="http://www.isotc211.org/2005/resources/Codelist/gmxCodelists.xml#CI_OnLineFunctionCode" codeListValue=""/>
									</gmd:function>
								</gmd:CI_OnlineResource>
							</gmd:onLine>
						</gmd:MD_DigitalTransferOptions>
					</gmd:transferOptions> -->
					<!-- <gmd:transferOptions>
						<gmd:MD_DigitalTransferOptions>
							<gmd:onLine>
								<gmd:CI_OnlineResource>
									<gmd:linkage>
										<gmd:URL>
											<xsl:value-of select="dataset/dataTable/physical/distribution/online/url"/>
										</gmd:URL>
									</gmd:linkage>
									<gmd:protocol>
										<gco:CharacterString/>
									</gmd:protocol>
									<gmd:description>
										<gco:CharacterString>
										<xsl:value-of select="dataset/dataTable/physical/objectName"/></gco:CharacterString>
									</gmd:description>
									<gmd:function>
										<gmd:CI_OnLineFunctionCode codeList="http://www.isotc211.org/2005/resources/Codelist/gmxCodelists.xml#CI_OnLineFunctionCode" codeListValue=""/>
									</gmd:function>
								</gmd:CI_OnlineResource>
							</gmd:onLine>
						</gmd:MD_DigitalTransferOptions>
					</gmd:transferOptions> -->
					<!-- <xsl:if test="additionalMetadata/metadata/physical/distribution/online/url">
					<gmd:transferOptions>
						<gmd:MD_DigitalTransferOptions>
							<gmd:onLine>
								<gmd:CI_OnlineResource>
									<gmd:linkage>
										<gmd:URL>
											<xsl:value-of select="additionalMetadata/metadata/physical/distribution/online/url"/>
										</gmd:URL>
									</gmd:linkage>
									<gmd:protocol>
										<gco:CharacterString/>
									</gmd:protocol>
									<gmd:description>
										<gco:CharacterString/>
									</gmd:description>
									<gmd:function>
										<gmd:CI_OnLineFunctionCode codeList="http://www.isotc211.org/2005/resources/Codelist/gmxCodelists.xml#CI_OnLineFunctionCode" codeListValue=""/>
									</gmd:function>
								</gmd:CI_OnlineResource>
							</gmd:onLine>
						</gmd:MD_DigitalTransferOptions>
					</gmd:transferOptions>
					</xsl:if> -->
				</gmd:MD_Distribution>
			</gmd:distributionInfo>
			<gmd:dataQualityInfo>
				<gmd:DQ_DataQuality>
					<gmd:scope>
						<gmd:DQ_Scope>
							<gmd:level>
								<gmd:MD_ScopeCode codeList="http://www.isotc211.org/2005/resources/Codelist/gmxCodelists.xml#MD_ScopeCode" codeListValue="dataset">dataset</gmd:MD_ScopeCode>
							</gmd:level>
						</gmd:DQ_Scope>
					</gmd:scope>
					<gmd:lineage>
						<gmd:LI_Lineage>
							<gmd:statement>
								<gco:CharacterString/>
							</gmd:statement>

							<xsl:for-each select="/eml:eml/dataset/methods/methodStep">
								<gmd:processStep>
									<gmd:LI_ProcessStep>
										<gmd:description>

											<xsl:call-template name="writeCharacterString">

												<xsl:with-param name="stringToWrite">

													<xsl:value-of select="concat(description/section/title,': ')"/>

													<xsl:for-each select="description/section/para">
														<xsl:value-of select="."/>
													</xsl:for-each>
												</xsl:with-param>
											</xsl:call-template>
										</gmd:description>
									</gmd:LI_ProcessStep>
								</gmd:processStep>
							</xsl:for-each>
						</gmd:LI_Lineage>
					</gmd:lineage>
				</gmd:DQ_DataQuality>
			</gmd:dataQualityInfo>

			<xsl:if test="access">
				<gmd:metadataConstraints>
					<gmd:MD_LegalConstraints>

						<xsl:for-each select="access/allow">
							<gmd:otherConstraints>
								<gco:CharacterString>
									<xsl:value-of select="concat(principal,': ',permission)"/>
								</gco:CharacterString>
							</gmd:otherConstraints>
						</xsl:for-each>
					</gmd:MD_LegalConstraints>
				</gmd:metadataConstraints>
			</xsl:if>
		</gmd:MD_Metadata>
	</xsl:template>

	<xsl:template match="individualName" mode="name">
		<gmd:individualName>
			<gco:CharacterString>

				<xsl:value-of select="givenName"/>

				<xsl:value-of select="surName"/>
			</gco:CharacterString>
		</gmd:individualName>
	</xsl:template>
	<xsl:template match="maintenance">
		<gmd:resourceMaintenance >
			<gmd:MD_MaintenanceInformation>
				<gmd:maintenanceAndUpdateFrequency>
					<gmd:MD_MaintenanceFrequencyCode codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#MD_MaintenanceFrequencyCode">
						
							<xsl:attribute name="codeListValue">
							<xsl:value-of select='maintenanceUpdateFrequency'/>
							</xsl:attribute>
					</gmd:MD_MaintenanceFrequencyCode>
				</gmd:maintenanceAndUpdateFrequency>
			</gmd:MD_MaintenanceInformation>
		</gmd:resourceMaintenance>
	</xsl:template>
	<xsl:template match="onlineUrl">
		<gmd:onlineResource>
			<gmd:CI_OnlineResource>
				<gmd:linkage>
					<gmd:URL>
						<xsl:value-of select="."/>
					</gmd:URL>
				</gmd:linkage>
			</gmd:CI_OnlineResource>
		</gmd:onlineResource>
	</xsl:template>

	<xsl:template match="geographicCoverage">
		<gmd:geographicElement>
			<gmd:EX_GeographicBoundingBox>
				<gmd:westBoundLongitude>
					<gco:Decimal>
						<xsl:value-of select="boundingCoordinates/westBoundingCoordinate"/>
					</gco:Decimal>
				</gmd:westBoundLongitude>
				<gmd:eastBoundLongitude>
					<gco:Decimal>
						<xsl:value-of select="boundingCoordinates/eastBoundingCoordinate"/>
					</gco:Decimal>
				</gmd:eastBoundLongitude>
				<gmd:southBoundLatitude>
					<gco:Decimal>
						<xsl:value-of select="boundingCoordinates/southBoundingCoordinate"/>
					</gco:Decimal>
				</gmd:southBoundLatitude>
				<gmd:northBoundLatitude>
					<gco:Decimal>
						<xsl:value-of select="boundingCoordinates/northBoundingCoordinate"/>
					</gco:Decimal>
				</gmd:northBoundLatitude>
			</gmd:EX_GeographicBoundingBox>
		</gmd:geographicElement>
	</xsl:template>

	<xsl:template match="temporalCoverage">

		<xsl:param name="timePeriodId"/>
		<!-- check on how to handle single dates in ISO -->
		<gmd:temporalElement>
			<gmd:EX_TemporalExtent>
				<gmd:extent>
					<gml:TimePeriod gml:id="timePeriod{$timePeriodId}">
						<gml:begin>
							<gml:TimeInstant gml:id="timePeriod{$timePeriodId}BeginDate">
								<gml:timePosition>
									<xsl:value-of select="rangeOfDates/beginDate/calendarDate"/>
								</gml:timePosition>
							</gml:TimeInstant>
						</gml:begin>
						<gml:end>
							<gml:TimeInstant gml:id="timePeriod{$timePeriodId}EndDate">
								<gml:timePosition>
									<xsl:value-of select="rangeOfDates/endDate/calendarDate"/>
								</gml:timePosition>
							</gml:TimeInstant>
						</gml:end>
					</gml:TimePeriod>
				</gmd:extent>
			</gmd:EX_TemporalExtent>
		</gmd:temporalElement>
	</xsl:template>

	<xsl:template match="methods">

		<xsl:if test="normalize-space(methodStep/description/para)">
			<xsl:text>Methods: </xsl:text>

			<xsl:value-of select="methodStep/description/para"/>
			<xsl:text>  | </xsl:text>
		</xsl:if>

		<xsl:if test="normalize-space(sampling/studyExtent/description)">
			<xsl:text>Sampling area: </xsl:text>

			<xsl:value-of select="sampling/studyExtent/description"/>
			<xsl:text>  | </xsl:text>
		</xsl:if>

		<xsl:if test="normalize-space(sampling/samplingDescription/para)">
			<xsl:text>Sampling procedure: </xsl:text>

			<xsl:value-of select="sampling/samplingDescription/para"/>
			<xsl:text>  | </xsl:text>
		</xsl:if>

		<xsl:if test="normalize-space(qualityControl/description/para)">
			<xsl:text>Quality control: </xsl:text>

			<xsl:value-of select="qualityControl/description/para"/>
			<xsl:text>  | </xsl:text>
		</xsl:if>
	</xsl:template>
	
	<xsl:template name="writeCharacterString">

		<xsl:param name="stringToWrite"/>

		<xsl:choose>

			<xsl:when test="string($stringToWrite)">
				<gco:CharacterString>
					<xsl:value-of select="normalize-space($stringToWrite)"/>
				</gco:CharacterString>
			</xsl:when>

			<xsl:otherwise>

				<xsl:attribute name="gco:nilReason">
					<xsl:value-of select="'missing'"/>
				</xsl:attribute>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template name="writeCodelist">

		<xsl:param name="codeListName"/>

		<xsl:param name="codeListValue"/>

		<xsl:variable name="codeListLocation" select="'https://www.ngdc.noaa.gov/metadata/published/xsd/schema/resources/Codelist/gmxCodelists.xml'"/>

		<xsl:element name="{$codeListName}">

			<xsl:attribute name="codeList">

				<xsl:value-of select="$codeListLocation"/>

				<xsl:value-of select="'#'"/>

				<xsl:value-of select="$codeListName"/>
			</xsl:attribute>

			<xsl:attribute name="codeListValue">
				<xsl:value-of select="$codeListValue"/>
			</xsl:attribute>

			<xsl:value-of select="$codeListValue"/>
		</xsl:element>
	</xsl:template>

	<xsl:template name="emlPerson2ResponsibleParty">

		<xsl:param name="roleCode"/>
		<gmd:CI_ResponsibleParty>
			<gmd:individualName>
				<xsl:choose>
         		<xsl:when test="(userId and (userId/@directory='http://orcid.org')) or (userId and (userId/@directory='https://orcid.org'))">
					<xsl:call-template name="writeCharacterString">
						<xsl:with-param name="stringToWrite" select="normalize-space(concat(individualName/salutation,' ',individualName/givenName,' ',individualName/surName,'; orcid=',substring-after(userId,'orcid.org/')))"/>
					</xsl:call-template>
				</xsl:when>
				<xsl:when test="userId and (userId/@directory='ORCID')">
					<xsl:call-template name="writeCharacterString">
						<xsl:with-param name="stringToWrite" select="normalize-space(concat(individualName/salutation,' ',individualName/givenName,' ',individualName/surName,'; orcid=',userId))"/>
					</xsl:call-template>
				</xsl:when>
				<xsl:otherwise>
					<xsl:call-template name="writeCharacterString">
						<xsl:with-param name="stringToWrite" select="normalize-space(concat(individualName/salutation,' ',individualName/givenName,' ',individualName/surName))"/>
					</xsl:call-template>
				</xsl:otherwise>
				</xsl:choose>
			</gmd:individualName>
			<gmd:organisationName>
				<xsl:call-template name="writeCharacterString">
					<xsl:with-param name="stringToWrite" select="organizationName"/>
				</xsl:call-template>
			</gmd:organisationName>
			<gmd:positionName>

				<xsl:call-template name="writeCharacterString">
					<xsl:with-param name="stringToWrite" select="positionName"/>
				</xsl:call-template>
			</gmd:positionName>
			<gmd:contactInfo>
				<gmd:CI_Contact>
					<gmd:phone>
						<gmd:CI_Telephone>
							<gmd:voice>

								<xsl:call-template name="writeCharacterString">
									<xsl:with-param name="stringToWrite" select="phone"/>
								</xsl:call-template>
							</gmd:voice>
						</gmd:CI_Telephone>
					</gmd:phone>
					<gmd:address>
						<gmd:CI_Address>

							<xsl:for-each select="address/deliveryPoint">
								<gmd:deliveryPoint>

									<xsl:call-template name="writeCharacterString">
										<xsl:with-param name="stringToWrite" select="."/>
									</xsl:call-template>
								</gmd:deliveryPoint>
							</xsl:for-each>
							<gmd:city>

								<xsl:call-template name="writeCharacterString">
									<xsl:with-param name="stringToWrite" select="address/city"/>
								</xsl:call-template>
							</gmd:city>
							<gmd:administrativeArea>

								<xsl:call-template name="writeCharacterString">
									<xsl:with-param name="stringToWrite" select="address/administrativeArea"/>
								</xsl:call-template>
							</gmd:administrativeArea>
							<gmd:postalCode>

								<xsl:call-template name="writeCharacterString">
									<xsl:with-param name="stringToWrite" select="address/postalCode"/>
								</xsl:call-template>
							</gmd:postalCode>
							<gmd:country>

								<xsl:call-template name="writeCharacterString">
									<xsl:with-param name="stringToWrite" select="address/country"/>
								</xsl:call-template>
							</gmd:country>
							<gmd:electronicMailAddress>

								<xsl:call-template name="writeCharacterString">
									<xsl:with-param name="stringToWrite" select="electronicMailAddress"/>
								</xsl:call-template>
							</gmd:electronicMailAddress>
						</gmd:CI_Address>
					</gmd:address>
					<gmd:onlineResource>
						<gmd:CI_OnlineResource>
							<gmd:linkage>

								<xsl:choose>

									<xsl:when test="org-contact-info/url">
										<gmd:URL>
											<xsl:value-of select="org-contact-info/url"/>
										</gmd:URL>
									</xsl:when>

									<xsl:otherwise>

										<xsl:attribute name="gco:nilReason">
											<xsl:value-of select="'missing'"/>
										</xsl:attribute>
									</xsl:otherwise>
								</xsl:choose>
							</gmd:linkage>
						</gmd:CI_OnlineResource>
					</gmd:onlineResource>
					<gmd:hoursOfService>

						<xsl:call-template name="writeCharacterString">
							<xsl:with-param name="stringToWrite" select="org-contact-info/business-hours"/>
						</xsl:call-template>
					</gmd:hoursOfService>
					<gmd:contactInstructions>

						<xsl:call-template name="writeCharacterString">
							<xsl:with-param name="stringToWrite" select="contact-instructions"/>
						</xsl:call-template>
					</gmd:contactInstructions>
				</gmd:CI_Contact>
			</gmd:contactInfo>
			<gmd:role>

				<xsl:call-template name="writeCodelist">

					<xsl:with-param name="codeListName" select="'gmd:CI_RoleCode'"/>

					<xsl:with-param name="codeListValue" select="$roleCode"/>
				</xsl:call-template>
			</gmd:role>
		</gmd:CI_ResponsibleParty>
	</xsl:template>

	<xsl:template name="writeVariable">
		<gmd:dimension>
			<gmd:MD_Band>
				<gmd:sequenceIdentifier>
					<gco:MemberName>
						<gco:aName>

							<xsl:call-template name="writeCharacterString">
								<xsl:with-param name="stringToWrite" select="attributeName"/>
							</xsl:call-template>
						</gco:aName>
						<gco:attributeType>
							<gco:TypeName>
								<gco:aName>

									<xsl:call-template name="writeCharacterString">
										<xsl:with-param name="stringToWrite" select="measurementScale/interval/unit/standardUnit"/>
									</xsl:call-template>
								</gco:aName>
							</gco:TypeName>
						</gco:attributeType>
					</gco:MemberName>
				</gmd:sequenceIdentifier>
				<gmd:descriptor>

					<xsl:call-template name="writeCharacterString">

						<xsl:with-param name="stringToWrite">

							<xsl:value-of select="attributeLabel"/>

							<xsl:if test="attributeDefinition">
								<xsl:value-of select="concat('- ',attributeDefinition)"/>
							</xsl:if>
						</xsl:with-param>
					</xsl:call-template>
				</gmd:descriptor>

				<xsl:if test="measurementScale/interval/unit/standardUnit">
					<gmd:units>

						<xsl:attribute name="xlink:href">

							<xsl:value-of select="'http://someUnitsDictionary.xml#'"/>

							<xsl:value-of select="measurementScale/interval/unit/standardUnit"/>
						</xsl:attribute>
					</gmd:units>
				</xsl:if>
			</gmd:MD_Band>
		</gmd:dimension>
	</xsl:template>
</xsl:stylesheet>

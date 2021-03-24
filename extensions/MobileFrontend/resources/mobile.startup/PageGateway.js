( function ( M ) {
	var sectionTemplate = mw.template.get( 'mobile.startup', 'Section.hogan' ),
		util = M.require( 'mobile.startup/util' ),
		cache = {};

	/**
	 * Add child to listOfSections if the level of child is the same as the last
	 * child of listOfSections, otherwise add it to the children of the last
	 * section of listOfSections. If listOfSections is empty, just add child to it.
	 * @method
	 * @private
	 * @ignore
	 * @param {Array} listOfSections - Array of section ids
	 * @param {Object} child - Section to be added to listOfSections
	 */
	function assignToParent( listOfSections, child ) {
		var section;
		if ( listOfSections.length === 0 ) {
			listOfSections.push( child );
		} else {
			// take a look at the last child
			section = listOfSections[listOfSections.length - 1];
			// If the level is the same as another section in this list it is a sibling
			if ( parseInt( section.level, 10 ) === parseInt( child.level, 10 ) ) {
				listOfSections.push( child );
			} else {
				// Otherwise take a look at that sections children recursively
				assignToParent( section.children, child );
			}
		}
	}

	/**
	 * Order sections hierarchically
	 * @method
	 * @private
	 * @ignore
	 * @param {Array} sections Array of section objects created from response HTML
	 * @return {Array} Ordered array of sections
	 */
	function transformSections( sections ) {
		var sectionLevels = sections.map( function ( s ) { return s.level; } ),
			existingSectionLevels = sectionLevels.filter( function ( level ) {
				return !!level;
			} ),
			collapseLevel = Math.min.apply( this, existingSectionLevels ).toString(),
			lastSection,
			result = [];

		// if the first section level is not equal to collapseLevel, this first
		// section will not have a parent and will be appended to the result.
		sections.forEach( function ( section ) {
			section.children = [];

			if (
				!lastSection ||
				(
					!section.level ||
					section.level === collapseLevel
				) ||
				// make sure lastSections first child's level is bigger than section.level
				(
					lastSection.children.length &&
					lastSection.children[0].level > section.level
				) ||
				// also make sure section.level is not bigger than the lastSection.level
				(
					lastSection.level &&
					lastSection.level >= section.level
				)
			) {
				result.push( section );
				lastSection = section;
			} else {
				assignToParent( lastSection.children, section );
				lastSection.text += sectionTemplate.render( section );
			}
		} );

		return result;
	}

	/**
	 * API for providing Page data
	 * @class PageGateway
	 * @param {mw.Api} api
	 */
	function PageGateway( api ) {
		this.api = api;
	}

	PageGateway.prototype = {
		/**
		 * Retrieve a page from the api
		 *
		 * @method
		 * @param {string} title the title of the page to be retrieved
		 * @param {string} endpoint an alternative api url to retrieve the page from
		 * @param {boolean} leadOnly When set only the lead section content is returned
		 * @return {jQuery.Deferred} with parameter page data that can be passed to a Page view
		 */
		getPage: function ( title, endpoint, leadOnly ) {
			var timestamp,
				d = util.Deferred(),
				options = endpoint ? {
					url: endpoint,
					dataType: 'jsonp'
				} : {},
				protection = {
					edit: [ '*' ]
				};

			if ( !cache[title] ) {
				cache[title] = this.api.get( {
					action: 'mobileview',
					page: title,
					variant: mw.config.get( 'wgPreferredVariant' ),
					redirect: 'yes',
					prop: 'id|sections|text|lastmodified|lastmodifiedby|languagecount|hasvariants|protection|displaytitle|revision',
					noheadings: 'yes',
					sectionprop: 'level|line|anchor',
					sections: leadOnly ? 0 : 'all'
				}, options ).then( function ( resp ) {
					var sections, lastModified, resolveObj, mv;

					if ( resp.error ) {
						return d.reject( resp.error );
					} else if ( !resp.mobileview.sections ) {
						return d.reject( 'No sections' );
					} else {
						mv = resp.mobileview;
						sections = transformSections( mv.sections );
						// Assume the timestamp is in the form TS_ISO_8601 and we don't care about old browsers
						// change to seconds to be consistent with PHP
						timestamp = new Date( mv.lastmodified ).getTime() / 1000;
						lastModified = mv.lastmodifiedby;

						// FIXME: [API] the API sometimes returns an object and sometimes an array
						// There are various quirks with the format of protection level as returned by api.
						// Also it is usually incomplete - if something is missing this means that it has
						// no protection level. When an array this means there is no protection level set.
						// So to keep the data type consistent either use the predefined protection level, or
						// extend it with what is returned by API.
						protection = Array.isArray( mv.protection ) ? protection : util.extend( protection, mv.protection );
						resolveObj = {
							title: title,
							id: mv.id,
							revId: mv.revId,
							protection: protection,
							lead: sections[0].text,
							sections: sections.slice( 1 ),
							isMainPage: mv.hasOwnProperty( 'mainpage' ),
							historyUrl: mw.util.getUrl( title, {
								action: 'history'
							} ),
							lastModifiedTimestamp: timestamp,
							languageCount: mv.languagecount,
							hasVariants: mv.hasOwnProperty( 'hasvariants' ),
							displayTitle: mv.displaytitle
						};
						// Add non-anonymous user information
						if ( lastModified ) {
							util.extend( resolveObj, {
								lastModifiedUserName: lastModified.name,
								lastModifiedUserGender: lastModified.gender
							} );
						}
						// FIXME: Return a Page class here
						return resolveObj;
					}
				}, function ( msg ) {
					return d.reject( msg );
				} );
			}

			return cache[title];
		},

		/**
		 * Invalidate the internal cache for a given page
		 *
		 * @method
		 * @param {string} title the title of the page who's cache you want to invalidate
		 */
		invalidatePage: function ( title ) {
			delete cache[title];
		},

		/**
		 * Gets language variant list for a page; helper function for getPageLanguages()
		 *
		 * @method
		 * @private
		 * @param  {string} title Name of the page to obtain variants for
		 * @param  {Object} data Data from API
		 * @return {Array|boolean} List of language variant objects or false if no variants exist
		 */
		_getLanguageVariantsFromApiResponse: function ( title, data ) {
			var generalData = data.query.general,
				variantPath = generalData.variantarticlepath,
				variants = [];

			if ( !generalData.variants ) {
				return false;
			}

			// Create the data object for each variant and store it
			Object.keys( generalData.variants ).forEach( function ( index ) {
				var item = generalData.variants[ index ],
					variant = {
						autonym: item.name,
						lang: item.code
					};

				if ( variantPath ) {
					variant.url = variantPath
						.replace( '$1', title )
						.replace( '$2', item.code );
				} else {
					variant.url = mw.util.getUrl( title, {
						variant: item.code
					} );
				}
				variants.push( variant );
			} );

			return variants;
		},

		/**
		 * Retrieve available languages for a given title
		 *
		 * @method
		 * @param {string} title the title of the page languages should be retrieved for
		 * @param {string} [language] when provided the names of the languages returned
		 *  will be translated additionally into this language.
		 * @return {jQuery.Deferred} which is called with an object containing langlinks
		 * and variant links as defined @ https://en.m.wikipedia.org/w/api.php?action=help&modules=query%2Blanglinks
		 */
		getPageLanguages: function ( title, language ) {
			var self = this,
				args = {
					action: 'query',
					meta: 'siteinfo',
					siprop: 'general',
					prop: 'langlinks',
					lllimit: 'max',
					titles: title,
					formatversion: 2
				};

			if ( language ) {
				args.llprop = 'url|autonym|langname';
				args.llinlanguagecode = language;
			} else {
				args.llprop = 'url|autonym';
			}
			return this.api.get( args ).then( function ( resp ) {
				return {
					languages: resp.query.pages[0].langlinks || [],
					variants: self._getLanguageVariantsFromApiResponse( title, resp )
				};
			}, function () {
				return util.Deferred().reject();
			} );
		},

		/**
		 * Extract sections from headings in $el
		 * @method
		 * @private
		 * @param {jQuery.Object} $el object from which sections are extracted
		 * @return {Array} Array of section objects created from headings in $el
		 * FIXME: Where's a better place for these two functions to live?
		 */
		_getAPIResponseFromHTML: function ( $el ) {
			var $headings = $el.find( 'h1,h2,h3,h4,h5,h6' ),
				sections = [];

			$headings.each( function () {
				var level = this.tagName.substr( 1 ),
					$span = $el.find( this ).find( '.mw-headline' );

				if ( $span.length ) {
					sections.push( {
						level: level,
						line: $span.html(),
						anchor: $span.attr( 'id' ) || '',
						text: ''
					} );
				}
			} );
			return sections;
		},

		/**
		 * Order sections hierarchically
		 * @method
		 * @param {jQuery.Object} $el object from which sections are extracted
		 * @return {Array} Ordered array of sections
		 */
		getSectionsFromHTML: function ( $el ) {
			return transformSections( this._getAPIResponseFromHTML( $el ) );
		}
	};

	M.define( 'mobile.startup/PageGateway', PageGateway );
}( mw.mobileFrontend ) );

/**
 * Base mw.TextSource object
 *
 * @param {Object} source Source object to extend
 * @param {Object} textProvider [Optional] The text provider interface ( to load source from api )
 */
( function ( mw, $ ) {
	'use strict';

	mw.TextSource = function ( source ) {
		return this.init( source );
	};
	mw.TextSource.prototype = {

		// The load state:
		loaded: false,

		// Container for the captions
		// captions include "start", "end" and "content" fields
		captions: [],

		// The css style for captions ( some file formats specify display types )
		styleCss: {},

		// The previous index of the timed text served
		// Avoids searching the entire array on time updates.
		prevIndex: 0,

		/**
		 * Inherits mediaSource from embedPlayer
		 * @constructor
		 * @param {Object} source Base source element
		 * @param {Object} textProvider Pointer to the textProvider
		 * @return {mw.TextSource}
		 */
		init: function ( source, textProvider ) {
			var i;
			//	Inherits mediaSource
			for ( i in source ) {
				this[ i ] = source[ i ];
			}

			// Set default category to subtitle if unset:
			if ( !this.kind ) {
				this.kind = 'subtitle';
			}
			// Set the textProvider if provided
			if ( textProvider ) {
				this.textProvider = textProvider;
			}
			return this;
		},

		/**
		 * Function to load and parse the source text
		 * @param {Function} callback Function called once text source is loaded
		 * @return {Mixed}
		 */
		load: function ( callback ) {
			var self = this;
			mw.log( 'TextSource:: load src ' + self.getSrc() );

			// Setup up a callback ( in case it was not defined )
			if ( !callback ) {
				callback = function () { return; };
			}

			// Check if the captions have already been loaded:
			if ( this.loaded ) {
				return callback();
			}

			// Try to load src via XHR source
			if ( !this.getSrc() ) {
				mw.log( 'Error: TextSource no source url for text track' );
				return callback();
			}

			// Check type for special loaders:
			$( mw ).triggerQueueCallback( 'TimedText_LoadTextSource', self, function () {
				if ( self.loaded ) {
					callback();
				} else {
					// if no module loaded the text source use the normal ajax proxy:
					// eslint-disable-next-line new-cap, no-new
					new mw.ajaxProxy( {
						url: self.getSrc(),
						success: function ( resultXML ) {
							self.captions = self.getCaptions( resultXML );
							self.loaded = true;
							mw.log( 'mw.TextSource :: loaded from ' + self.getSrc() + ' Found: ' + self.captions.length + ' captions' );
							callback();
						},
						error: function () {
							mw.log( 'Error: TextSource Error with http response' );
							self.loaded = true;
							callback();
						}
					} );
				}
			} );
		},
		/**
		* Returns the text content for requested time
		*
		* @param {number} time Time in seconds
		* @return {Object}
		*/
		getCaptionForTime: function ( time ) {
			var i, caption, startIndex, firstCapIndex,
				prevCaption = this.captions[ this.prevIndex ],
				captionSet = {};

			// Setup the startIndex:
			if ( prevCaption && time >= prevCaption.start ) {
				startIndex = this.prevIndex;
			} else {
				// If a backwards seek start searching at the start:
				startIndex = 0;
			}
			firstCapIndex = 0;
			// Start looking for the text via time, add all matches that are in range
			for ( i = startIndex; i < this.captions.length; i++ ) {
				caption = this.captions[ i ];
				// Don't handle captions with 0 or -1 end time:
				if ( caption.end === 0 || caption.end === -1 ) { continue; }

				if ( time >= caption.start &&
					time <= caption.end ) {
					// set the earliest valid time to the current start index:
					if ( !firstCapIndex ) {
						firstCapIndex = caption.start;
					}

					// mw.log("Start cap time: " + caption.start + ' End time: ' + caption.end );
					captionSet[ i ] = caption;
				}
				// captions are stored in start order stop search if we get larger than time
				if ( caption.start > time ) {
					break;
				}
			}
			// Update the prevIndex:
			this.prevIndex = firstCapIndex;
			// Return the set of captions in range:
			return captionSet;
		},

		/**
		 * Check if the caption is an overlay format ( and must be ontop of the player )
		 * @return {boolean}
		 */
		isOverlay: function () {
			return this.mimeType === 'text/xml';
		},

		getCaptions: function ( data ) {
			// Detect caption data type:
			switch ( this.mimeType ) {
				case 'text/mw-srt':
					return this.getCaptionsFromMediaWikiSrt( data );
				case 'text/x-srt':
					return this.getCaptionsFromSrt( data );
				case 'text/xml':
					return this.getCaptionsFromTMML( data );
			}
			// caption mime not found return empty set:
			return [];
		},

		getStyleCssById: function ( styleId ) {
			if ( this.styleCss[ styleId ] ) {
				return this.styleCss[ styleId ];
			}
			return {};
		},
		/**
		 * Grab timed text from TMML format
		 *
		 * @param {Object} data
		 * @return {Array}
		 */
		getCaptionsFromTMML: function ( data ) {
			var bodyStyleId,
				self = this,
				// set up display information:
				captions = [],
				xml = ( $( data ).find( 'tt' ).length ) ? data : $.parseXML( data );
			mw.log( 'TextSource::getCaptionsFromTMML', data );

			// Check for parse error:
			try {
				if ( !xml || $( xml ).find( 'parsererror' ).length ) {
					mw.log( 'Error: close caption parse error: ' + $( xml ).find( 'parsererror' ).text() );
					return captions;
				}
			} catch ( e ) {
				mw.log( 'Error: close caption parse error: ' + e.toString() );
				return captions;
			}

			// Set the body Style
			bodyStyleId = $( xml ).find( 'body' ).attr( 'style' );

			// Set style translate ttml to css
			$( xml ).find( 'style' ).each( function ( inx, style ) {
				var cssObject = {};
				// Map CamelCase css properties:
				$( style.attributes ).each( function ( inx, attr ) {
					var cssName, c,
						attrName = attr.name;
					if ( attrName.substr( 0, 4 ) !== 'tts:' ) {
						// skip
						return true;
					}
					cssName = '';
					for ( c = 4; c < attrName.length; c++ ) {
						if ( attrName[ c ].toLowerCase() !== attrName[ c ] ) {
							cssName += '-' + attrName[ c ].toLowerCase();
						} else {
							cssName += attrName[ c ];
						}
					}
					cssObject[ cssName ] = attr.nodeValue;
				} );
				// for(var i =0; i< style.length )
				self.styleCss[ $( style ).attr( 'id' ) ] = cssObject;
			} );

			$( xml ).find( 'p' ).each( function ( inx, p ) {
				// Get text content by converting ttml node to html
				var end, captionObj, $meta,
					content = '';
				$.each( p.childNodes, function ( inx, node ) {
					content += self.convertTTML2HTML( node );
				} );
				// Get the end time:
				end = null;
				if ( $( p ).attr( 'end' ) ) {
					end = mw.npt2seconds( $( p ).attr( 'end' ) );
				}
				// Look for dur
				if ( !end && $( p ).attr( 'dur' ) ) {
					end = mw.npt2seconds( $( p ).attr( 'begin' ) ) +
						mw.npt2seconds( $( p ).attr( 'dur' ) );
				}

				// Create the caption object :
				captionObj = {
					start: mw.npt2seconds( $( p ).attr( 'begin' ) ),
					end: end,
					content: content
				};

				// See if we have custom metadata for position of this caption object
				// there are 35 columns across and 15 rows high
				$meta = $( p ).find( 'metadata' );
				if ( $meta.length ) {
					captionObj.css = {
						position: 'absolute'
					};
					if ( $meta.attr( 'cccol' ) ) {
						captionObj.css.left = ( $meta.attr( 'cccol' ) / 35 ) * 100 + '%';
						// also means the width has to be reduced:
						// captionObj['css']['width'] =  100 - parseInt( captionObj['css']['left'] ) + '%';
					}
					if ( $meta.attr( 'ccrow' ) ) {
						captionObj.css.top = ( $meta.attr( 'ccrow' ) / 15 ) * 100 + '%';
					}
				}
				if ( $( p ).attr( 'tts:textAlign' ) ) {
					if ( !captionObj.css ) {
						captionObj.css = {};
					}
					captionObj.css[ 'text-align' ] = $( p ).attr( 'tts:textAlign' );

					// Remove text align is "right" flip the css left:
					if ( captionObj.css[ 'text-align' ] === 'right' && captionObj.css.left ) {
						// captionObj['css']['width'] = captionObj['css']['left'];
						captionObj.css.left = null;
					}
				}

				// check if this p has any style else use the body parent
				if ( $( p ).attr( 'style' ) ) {
					captionObj.styleId = $( p ).attr( 'style' );
				} else {
					captionObj.styleId = bodyStyleId;
				}
				captions.push( captionObj );
			} );
			return captions;
		},
		convertTTML2HTML: function ( node ) {
			var ttsStyleMap, nodeString, styleVal, attr,
				self = this;

			// look for text node:
			if ( node.nodeType === Node.TEXT_NODE ) {
				return node.textContent;
			}
			// skip metadata nodes:
			if ( node.nodeName === 'metadata' ) {
				return '';
			}
			// if a br just append
			if ( node.nodeName === 'br' ) {
				return '<br />';
			}
			// Setup tts mappings TODO should be static property of a ttmlSource object.
			ttsStyleMap = {
				'tts:color': 'color',
				'tts:fontWeight': 'font-weight',
				'tts:fontStyle': 'font-style'
			};
			if ( node.childNodes.length ) {
				nodeString = '';
				styleVal = '';
				for ( attr in ttsStyleMap ) {
					if ( node.getAttribute( attr ) ) {
						styleVal += ttsStyleMap[ attr ] + ':' + node.getAttribute( attr ) + ';';
					}
				}
				nodeString += '<' + node.nodeName + ' style="' + styleVal + '" >';
				$.each( node.childNodes, function ( inx, childNode ) {
					nodeString += self.convertTTML2HTML( childNode );
				} );
				nodeString += '</' + node.nodeName + '>';
				return nodeString;
			}
		},
		/**
		 * srt timed text parse handle:
		 * @param {string} data SRT string to be parsed
		 * @return {Array}
		 */
		getCaptionsFromSrt: function ( data ) {
			var xml, srt, captions, caplist, i, captionText, caption, s, m,
				self = this;
			mw.log( 'TextSource::getCaptionsFromSrt' );
			// Check if the "srt" parses as an XML
			try {
				xml = $.parseXML( data );
				if ( xml && $( xml ).find( 'parsererror' ).length === 0 ) {
					return this.getCaptionsFromTMML( data );
				}
			} catch ( e ) {
				// srt should not be xml
			}
			// Remove dos newlines
			srt = data.replace( /\r+/g, '' );

			// Trim white space start and end
			srt = srt.replace( /^\s+|\s+$/g, '' );

			// Remove all html tags for security reasons
			srt = srt.replace( /<[a-zA-Z/][^>]*>/g, '' );

			// Get captions
			captions = [];
			caplist = srt.split( '\n\n' );
			for ( i = 0; i < caplist.length; i++ ) {
				caption = false;
				captionText = caplist[ i ];
				s = captionText.split( /\n/ );
				if ( s.length < 2 ) {
					// file format error or comment lines
					continue;
				}
				if ( s[ 0 ].match( /^\d+$/ ) && s[ 1 ].match( /\d+:\d+:\d+/ ) ) {
					// ignore caption number in s[0]
					// parse time string
					m = s[ 1 ].match( /(\d+):(\d+):(\d+)(?:,(\d+))?\s*--?>\s*(\d+):(\d+):(\d+)(?:,(\d+))?/ );
					if ( m ) {
						caption = self.match2caption( m );
					} else {
						// Unrecognized timestring
						continue;
					}
					if ( caption ) {
						// concatenate text lines to html text
						caption.content = s.slice( 2 ).join( '<br>' );
					}
				} else {
					// file format error or comment lines
					continue;
				}
				// Add the current caption to the captions set:
				captions.push( caption );
			}

			return captions;
		},

		/**
		 * Get srts from a mediawiki html / srt string
		 *
		 *  Right now wiki -> html is not always friendly to our srt parsing.
		 *  The long term plan is to move the srt parsing to server side and have the api
		 *  server up the srt's times in JSON form
		 *
		 *  Also see https://bugzilla.wikimedia.org/show_bug.cgi?id=29126
		 *
		 * TODO move to mediaWiki specific module.
		 * @param {string} data
		 * @return {boolean} [description]
		 */
		getCaptionsFromMediaWikiSrt: function ( data ) {
			var self = this,
				captions = [ ],
				curentCap = {
					content: ''
				};
			mw.log( 'TimedText::getCaptionsFromMediaWikiSrt:' );
			// Note this string concatenation and html error wrapping sometimes causes
			// parse issues where the wikitext includes many native <p /> tags without child
			// subtitles. In prating this is not a deal breakers because the wikitext for
			// TimedText namespace and associated srts already has a specific format.
			// Long term we will move to server side parsing.
			$( '<div>' + data + '</div>' ).find( 'p' ).each( function () {
				var m,
					currentPtext = $( this ).html();
				// mw.log( 'pText: ' + currentPtext );

				// We translate raw wikitext gennerated html into a matched srt time sample.
				// The raw html looks like:
				// #
				// hh:mm:ss,ms --&gt hh:mm:ss,ms
				// text
				//
				// You can read more about the srt format here:
				// http://en.wikipedia.org/wiki/SubRip
				//
				// We attempt to be fairly robust in our regular expression to catch a few
				// srt variations such as omition of commas and empty text lines.
				m = currentPtext
					.replace( '--&gt;', '-->' ) // restore --&gt with --> for easier srt parsing:
					.match( /\d+\s([\d-]+):([\d-]+):([\d-]+)(?:,([\d-]+))?\s*--?>\s*([\d-]+):([\d-]+):([\d-]+)(?:,([\d-]+))?\n?(.*)/ );

				if ( m ) {
					captions.push(
						self.match2caption( m )
					);
					return true;
				}

				/***
				 * Handle multi line sytle output
				 *
				 * Handles cases parse cases where an entire line can't be parsed in the single
				 * regular expression above, Since the diffrent captions pars are outputed in
				 * diffrent <p /> tags by the wikitext parser output.
				 */

				// Check if we have reached the end of a multi line match
				if ( parseInt( currentPtext ) === currentPtext ) {
					if ( curentCap.content !== '' ) {
						captions.push( curentCap );
					}
					// Clear out the current caption content
					curentCap = {
						content: ''
					};
					return true;
				}
				// Check only for time match:
				m = currentPtext
					.replace( '--&gt;', '-->' )
					.match( /(\d+):(\d+):(\d+)(?:,(\d+))?\s*--?>\s*(\d+):(\d+):(\d+)(?:,(\d+))?/ );
				if ( m ) {
					// Update the currentCap:
					curentCap = self.match2caption( m );
					return true;
				}
				// Else append contnet for the curentCap
				if ( currentPtext !== '<br>' ) {
					curentCap.content += currentPtext;
				}
			} );
			// Push last subtitle:
			if ( curentCap.length !== 0 ) {
				captions.push( curentCap );
			}
			mw.log( 'TimedText::getCaptionsFromMediaWikiSrt found ' + captions.length + ' captions' );
			return captions;
		},
		/**
		 * Takes a regular expresion match and converts it to a caption object
		 *
		 * @param {Array} m
		 * @return {Object}
		 */
		match2caption: function ( m ) {
			var caption = {},
				// Look for ms:
				startMs = ( m[ 4 ] ) ? parseInt( m[ 4 ], 10 ) : 0,
				endMs = ( m[ 8 ] ) ? parseInt( m[ 8 ], 10 ) : 0;
			caption.start = this.timeParts2seconds( m[ 1 ], m[ 2 ], m[ 3 ], startMs );
			caption.end = this.timeParts2seconds( m[ 5 ], m[ 6 ], m[ 7 ], endMs );
			if ( m[ 9 ] ) {
				caption.content = $.trim( m[ 9 ] );
			}
			return caption;
		},
		/**
		 * Takes time parts in hours, min, seconds and milliseconds and coverts to float seconds.
		 *
		 * @param {number} hours
		 * @param {number} min
		 * @param {number} sec
		 * @param {number} ms
		 * @return {Object}
		 */
		timeParts2seconds: function ( hours, min, sec, ms ) {
			return mw.measurements2seconds( {
				hours: hours,
				minutes: min,
				seconds: sec,
				milliseconds: ms
			} );
		}
	};

}( mediaWiki, jQuery ) );

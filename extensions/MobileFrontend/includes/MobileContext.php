<?php

use MediaWiki\MediaWikiServices;
use MobileFrontend\Devices\DeviceDetectorService;
use MobileFrontend\WMFBaseDomainExtractor;

/**
 * Provide various request-dependant methods to use in mobile context
 */
class MobileContext extends ContextSource {
	const MODE_BETA = 'beta';
	const MODE_STABLE = 'stable';
	const OPTIN_COOKIE_NAME = 'optin';
	const STOP_MOBILE_REDIRECT_COOKIE_NAME = 'stopMobileRedirect';
	const USEFORMAT_COOKIE_NAME = 'mf_useformat';
	const USER_MODE_PREFERENCE_NAME = 'mfMode';
	const LOGGER_CHANNEL = 'mobile';
	/**
	 * Saves the testing mode user has opted in: 'beta' or 'stable'
	 * @var string $mobileMode
	 */
	protected $mobileMode;

	/**
	 * Whether to show the first paragraph before the infobox in the lead section
	 * @var boolean $showFirstParagraphBeforeInfobox
	 */
	protected $showFirstParagraphBeforeInfobox;
	/**
	 * Save explicitly requested format
	 * @var string $useFormat
	 */
	protected $useFormat;
	/**
	 * Save whether current page is blacklisted from displaying in mobile view
	 * @var boolean $blacklistedPage
	 */
	protected $blacklistedPage;

	/**
	 * Key/value pairs of things to add to X-Analytics response header for anlytics
	 * @var array
	 */
	protected $analyticsLogItems = [];

	/**
	 * The memoized result of `MobileContext#isMobileDevice`.
	 *
	 * This defaults to `null`, meaning that `MobileContext#isMobileDevice` has
	 * yet to be called.
	 *
	 * @see MobileContext#isMobileDevice
	 *
	 * @var {bool|null} $isMobileDevice
	 */
	private $isMobileDevice = null;

	/**
	 * @var string $action MediaWiki 'action'
	 */
	protected $action;

	/**
	 * Saves requested Mobile action
	 * @var string $mobileAction
	 */
	protected $mobileAction;

	/**
	 * Save whether mobile view is explicity requested
	 * @var boolean $forceMobileView
	 */
	private $forceMobileView = false;
	/**
	 * Save whether content should be transformed to better suit mobile devices
	 * @var boolean $contentTransformations
	 */
	private $contentTransformations = true;
	/**
	 * Save whether or not we should display the mobile view
	 * @var boolean $mobileView
	 */
	private $mobileView = null;
	/**
	 * Have we already checked for desktop/mobile view toggling?
	 * @var boolean $toggleViewChecked
	 */
	private $toggleViewChecked = false;
	/**
	 * Save an instance of this class
	 * @var MobileContext $instance
	 */
	private static $instance = null;
	/**
	 * @var string What to switch the view to
	 */
	private $viewChange = '';
	/**
	 * @var String Domain to use for the stopMobileRedirect cookie
	 */
	public static $mfStopRedirectCookieHost = null;
	/**
	 * @var String Stores the actual mobile url template.
	 */
	private $mobileUrlTemplate = false;

	/**
	 * Returns the actual MobileContext Instance or create a new if no exists
	 * @return MobileContext
	 */
	public static function singleton() {
		if ( !self::$instance ) {
			self::$instance = new MobileContext( RequestContext::getMain() );
		}
		return self::$instance;
	}

	/**
	 * Resets the singleton instance.
	 */
	public static function resetInstanceForTesting() {
		self::$instance = null;
	}

	/**
	 * Set the IontextSource Object
	 * @param IContextSource $context The IContextSource Object has to set
	 */
	protected function __construct( IContextSource $context ) {
		$this->setContext( $context );
	}

	/**
	 * Get MobileFrontend's config object.
	 * @return Config
	 */
	public function getMFConfig() {
		/** @var Config $config */
		$config = MediaWikiServices::getInstance()->getService( 'MobileFrontend.Config' );
		return $config;
	}

	/**
	 * Detects whether the UA is sending the request from a device and, if so,
	 * whether to display the mobile view to that device.
	 *
	 * The mobile view will always be displayed to mobile devices. However, it
	 * will only be displayed to tablet devices if `$wgMFShowMobileViewToTablets`
	 * is truthy.
	 *
	 * @FIXME: This should be renamed to something more appropriate, e.g.
	 * `shouldDisplayMobileViewToDevice`.
	 *
	 * @see MobileContext::shouldDisplayMobileView
	 *
	 * @return bool
	 */
	public function isMobileDevice() {
		if ( $this->isMobileDevice !== null ) {
			return $this->isMobileDevice;
		}

		$this->isMobileDevice = false;

		$config = $this->getMFConfig();
		$properties = DeviceDetectorService::factory( $config )
			->detectDeviceProperties( $this->getRequest(), $_SERVER );

		if ( $properties ) {
			$showMobileViewToTablets = $config->get( 'MFShowMobileViewToTablets' );

			$this->isMobileDevice =
				$properties->isMobileDevice()
				|| ( $properties->isTabletDevice() && $showMobileViewToTablets );
		}

		return $this->isMobileDevice;
	}

	/**
	 * Save whether mobile view should always be enforced
	 * @param bool $value should mobile view be enforced?
	 */
	public function setForceMobileView( $value ) {
		$this->forceMobileView = $value;
	}

	/**
	 * Whether mobile view should always be enforced
	 * @return bool is mobile view enforced?
	 */
	public function getForceMobileView() {
		return $this->forceMobileView;
	}

	/**
	 * Whether content should be transformed to better suit mobile devices
	 * @param bool $value should content be transformed?
	 */
	public function setContentTransformations( $value ) {
		$this->contentTransformations = $value;
	}

	/**
	 * Whether content should be transformed to better suit mobile devices
	 * @return bool is content being transformed?
	 */
	public function getContentTransformations() {
		return $this->contentTransformations;
	}

	/**
	 * Sets the value of $this->mobileMode property to the value of the 'optin' cookie.
	 * If the cookie is not set the value will be an empty string.
	 */
	private function loadMobileModeCookie() {
		$this->mobileMode = $this->getRequest()->getCookie( self::OPTIN_COOKIE_NAME, '' );
	}

	/**
	 * Returns the testing mode user has opted in: 'beta' or any other value for stable
	 * @return string
	 */
	private function getMobileMode() {
		$enableBeta = $this->getMFConfig()->get( 'MFEnableBeta' );

		if ( !$enableBeta ) {
			return '';
		}
		if ( is_null( $this->mobileMode ) ) {
			$mobileAction = $this->getMobileAction();
			if ( $mobileAction === self::MODE_BETA || $mobileAction === self::MODE_STABLE ) {
				$this->mobileMode = $mobileAction;
			} else {
				$user = $this->getUser();
				if ( $user->isAnon() ) {
					$this->loadMobileModeCookie();
				} else {
					$mode = $user->getOption( self::USER_MODE_PREFERENCE_NAME );
					$this->mobileMode = $mode;
					// Edge case where preferences are corrupt or the user opted
					// in before change.
					if ( $mode === null ) {
						// Should we set the user option here?
						$this->loadMobileModeCookie();
					}
				}
			}
		}
		return $this->mobileMode;
	}

	/**
	 * Sets testing group membership, both cookie and this class variables
	 * @param string $mode Mode to set
	 */
	public function setMobileMode( $mode ) {
		if ( $mode !== self::MODE_BETA ) {
			$mode = '';
		}
		// Update statistics
		if ( $mode === self::MODE_BETA ) {
			wfIncrStats( 'mobile.opt_in_cookie_set' );
		}
		if ( !$mode ) {
			wfIncrStats( 'mobile.opt_in_cookie_unset' );
		}
		$this->mobileMode = $mode;

		$user = $this->getUser();
		if ( $user->getId() ) {
			$user->setOption( self::USER_MODE_PREFERENCE_NAME, $mode );
			DeferredUpdates::addCallableUpdate( function () use ( $user, $mode ) {
				if ( wfReadOnly() ) {
					return;
				}

				$latestUser = $user->getInstanceForUpdate();
				$latestUser->setOption( self::USER_MODE_PREFERENCE_NAME, $mode );
				$latestUser->saveSettings();
			} );
		}

		$this->getRequest()->response()->setCookie( self::OPTIN_COOKIE_NAME, $mode, 0, [
			'prefix' => '',
			'domain' => $this->getCookieDomain()
		] );
	}

	/**
	 * Wether user is Beta group member
	 * @return bool
	 */
	public function isBetaGroupMember() {
		return $this->getMobileMode() === self::MODE_BETA;
	}

	/**
	 * Determine whether or not we should display the mobile view
	 *
	 * Step through the hierarchy of what should or should not trigger
	 * the mobile view.
	 *
	 * Primacy is given to the page action - we will never show mobile view
	 * for page edits or page history. 'userformat' request param is then
	 * honored, followed by cookie settings, then actual device detection,
	 * finally falling back on false.
	 * @return bool
	 */
	public function shouldDisplayMobileView() {
		if ( !is_null( $this->mobileView ) ) {
			return $this->mobileView;
		}
		// check if we need to toggle between mobile/desktop view
		$this->checkToggleView();
		$this->mobileView = $this->shouldDisplayMobileViewInternal();
		if ( $this->mobileView ) {
			$this->redirectMobileEnabledPages();
			Hooks::run( 'EnterMobileMode', [ $this ] );
		}
		return $this->mobileView;
	}

	/**
	 * If a page has an equivalent but different mobile page redirect to it
	 */
	private function redirectMobileEnabledPages() {
		$request = $this->getRequest();
		$title = $this->getTitle();

		$redirectUrl = null;
		if ( $request->getCheck( 'diff' ) ) {
			$redirectUrl = SpecialMobileDiff::getMobileUrlFromDesktop();
		}

		if ( $request->getVal( 'action' ) === 'history' &&
			// IContextSource::getTitle() can be null
			$title !== null &&
			// check, if SpecialMobileHistory supports the history action set for this title
			// content model
			SpecialMobileHistory::shouldUseSpecialHistory( $title )
		) {
			$values = $this->getRequest()->getValues();
			// avoid infinite redirect loops
			unset( $values['action'] );
			// Avoid multiple history parameters
			unset( $values['title'] );
			$redirectUrl = SpecialPage::getTitleFor( 'History', $this->getTitle() )->
				getLocalURL( $values );
		}

		if ( $redirectUrl ) {
			$this->getOutput()->redirect( $redirectUrl );
		}
	}

	/**
	 * Value for shouldDisplayMobileView()
	 * @return bool
	 */
	private function shouldDisplayMobileViewInternal() {
		// May be overridden programmatically
		if ( $this->forceMobileView ) {
			return true;
		}

		// always display desktop or mobile view if it's explicitly requested
		$useFormat = $this->getUseFormat();
		if ( $useFormat == 'desktop' ) {
			return false;
		} elseif ( $useFormat == 'mobile' ) {
			return true;
		}

		/**
		 * If a user is accessing the site from a mobile domain, then we should
		 * always display the mobile version of the site (otherwise, the cache
		 * may get polluted). See
		 * https://bugzilla.wikimedia.org/show_bug.cgi?id=46473
		 */
		if ( $this->usingMobileDomain() ) {
			return true;
		}

		// check cookies for what to display
		$useMobileFormat = $this->getUseFormatCookie();
		if ( $useMobileFormat == 'true' ) {
			return true;
		}
		$stopMobileRedirect = $this->getStopMobileRedirectCookie();
		if ( $stopMobileRedirect == 'true' ) {
			return false;
		}

		// do device detection
		if ( $this->isMobileDevice() ) {
			return true;
		}

		return false;
	}

	/**
	 * Checks whether current page is blacklisted from displaying mobile view
	 * @return bool
	 */
	public function isBlacklistedPage() {
		if ( is_null( $this->blacklistedPage ) ) {
			$this->blacklistedPage = $this->isBlacklistedPageInternal();
		}

		return $this->blacklistedPage;
	}

	/**
	 * Value for isBlacklistedPage()
	 * @return bool
	 */
	private function isBlacklistedPageInternal() {
		$config = $this->getMFConfig();
		$noMobilePages = $config->get( 'MFNoMobilePages' );
		$noMobileCategory = $config->get( 'MFNoMobileCategory' );

		// Check for blacklisted category membership
		$title = $this->getTitle();
		if ( $noMobileCategory && $title ) {
			$id = $title->getArticleID();
			if ( $id ) {
				$dbr = wfGetDB( DB_REPLICA );
				if ( $dbr->selectField( 'categorylinks',
					'cl_from',
					[ 'cl_from' => $id, 'cl_to' => $noMobileCategory ],
					__METHOD__
				) ) {
					return true;
				}
			}
		}
		// ...and individual page blacklisting
		if ( $noMobilePages && $title && in_array( $title->getPrefixedText(), $noMobilePages ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Get requested mobile action
	 * @return string
	 */
	public function getMobileAction() {
		if ( is_null( $this->mobileAction ) ) {
			$this->mobileAction = $this->getRequest()->getText( 'mobileaction' );
		}

		return $this->mobileAction;
	}

	/**
	 * Gets the value of the `useformat` query string parameter. This can be
	 * overridden using the `MobileContext#setUseFormat`.
	 *
	 * @return string
	 */
	public function getUseFormat() {
		if ( !isset( $this->useFormat ) ) {
			$useFormat = $this->getRequest()->getText( 'useformat' );
			$this->setUseFormat( $useFormat );
		}
		return $this->useFormat;
	}

	/**
	 * Overrides the value of `MobileContext#getUseFormat`.
	 *
	 * @param string $useFormat new value
	 */
	public function setUseFormat( $useFormat ) {
		$this->useFormat = $useFormat;
	}

	/**
	 * Set Cookie to stop automatically redirect to mobile page
	 * @param int $expiry Expire time of cookie
	 */
	public function setStopMobileRedirectCookie( $expiry = null ) {
		if ( is_null( $expiry ) ) {
			$expiry = $this->getUseFormatCookieExpiry();
		}

		$this->getRequest()->response()->setcookie(
			self::STOP_MOBILE_REDIRECT_COOKIE_NAME, 'true', $expiry,
			[
				'domain' => $this->getStopMobileRedirectCookieDomain(),
				'prefix' => '',
				'secure' => false,
			]
		);
	}

	/**
	 * Remove cookie and continue automatic redirect to mobile page
	 */
	public function unsetStopMobileRedirectCookie() {
		if ( is_null( $this->getStopMobileRedirectCookie() ) ) {
			return;
		}
		$expire = $this->getUseFormatCookieExpiry( time(), -3600 );
		$this->setStopMobileRedirectCookie( $expire );
	}

	/**
	 * Read cookie for stop automatic mobile redirect
	 * @return string
	 */
	public function getStopMobileRedirectCookie() {
		$stopMobileRedirectCookie = $this->getRequest()
			->getCookie( self::STOP_MOBILE_REDIRECT_COOKIE_NAME, '' );

		return $stopMobileRedirectCookie;
	}

	/**
	 * Get the useformat cookie
	 *
	 * This cookie can determine whether or not a user should see the mobile
	 * version of a page.
	 *
	 * @return string|null
	 */
	public function getUseFormatCookie() {
		$useFormatFromCookie = $this->getRequest()->getCookie( self::USEFORMAT_COOKIE_NAME, '' );

		return $useFormatFromCookie;
	}

	/**
	 * Return the base level domain or IP address
	 *
	 * @return string
	 */
	public function getCookieDomain() {
		$helper = new WMFBaseDomainExtractor();
		return $helper->getCookieDomain( $this->getMFConfig()->get( 'Server' ) );
	}

	/**
	 * Determine the correct domain to use for the stopMobileRedirect cookie
	 *
	 * Will use $wgMFStopRedirectCookieHost if it's set, otherwise will use
	 * result of getCookieDomain()
	 * @return string
	 */
	public function getStopMobileRedirectCookieDomain() {
		$mfStopRedirectCookieHost = $this->getMFConfig()->get( 'MFStopRedirectCookieHost' );

		if ( !$mfStopRedirectCookieHost ) {
			self::$mfStopRedirectCookieHost = $this->getCookieDomain();

		} else {
			self::$mfStopRedirectCookieHost = $mfStopRedirectCookieHost;
		}

		return self::$mfStopRedirectCookieHost;
	}

	/**
	 * Set the mf_useformat cookie
	 *
	 * This cookie can determine whether or not a user should see the mobile
	 * version of pages.
	 *
	 * @param string $cookieFormat should user see mobile version of pages?
	 * @param null $expiry Expiration of cookie
	 */
	public function setUseFormatCookie( $cookieFormat = 'true', $expiry = null ) {
		if ( is_null( $expiry ) ) {
			$expiry = $this->getUseFormatCookieExpiry();
		}
		$this->getRequest()->response()->setcookie(
			self::USEFORMAT_COOKIE_NAME,
			$cookieFormat,
			$expiry,
			[
				'prefix' => '',
				'httpOnly' => true,
			]
		);
		wfIncrStats( 'mobile.useformat_' . $cookieFormat . '_cookie_set' );
	}

	/**
	 * Remove cookie based saved useformat value
	 */
	public function unsetUseFormatCookie() {
		if ( is_null( $this->getUseFormatCookie() ) ) {
			return;
		}

		// set expiration date in the past
		$expire = $this->getUseFormatCookieExpiry( time(), -3600 );
		$this->setUseFormatCookie( '', $expire, true );
	}

	/**
	 * Get the expiration time for the mf_useformat cookie
	 *
	 * @param int $startTime The base time (in seconds since Epoch) from which to calculate
	 * 		cookie expiration. If null, time() is used.
	 * @param int $cookieDuration The time (in seconds) the cookie should last
	 * @return int The time (in seconds since Epoch) that the cookie should expire
	 */
	protected function getUseFormatCookieExpiry( $startTime = null, $cookieDuration = null ) {
		// use $cookieDuration if it's valid
		if ( intval( $cookieDuration ) === 0 ) {
			$cookieDuration = $this->getUseFormatCookieDuration();
		}

		// use $startTime if it's valid
		if ( intval( $startTime ) === 0 ) {
			$startTime = time();
		}

		$expiry = $startTime + $cookieDuration;
		return $expiry;
	}

	/**
	 * Determine the duration the cookie should last.
	 *
	 * If $wgMobileFrontendFormatcookieExpiry has a non-0 value, use that
	 * for the duration. Otherwise, fall back to $wgCookieExpiration.
	 *
	 * @return int The number of seconds for which the cookie should last.
	 */
	public function getUseFormatCookieDuration() {
		$mobileFrontendFormatCookieExpiry =
			$this->getMFConfig()->get( 'MobileFrontendFormatCookieExpiry' );

		$cookieExpiration = $this->getConfig()->get( 'CookieExpiration' );

		$cookieDuration = ( abs( intval( $mobileFrontendFormatCookieExpiry ) ) > 0 ) ?
			$mobileFrontendFormatCookieExpiry : $cookieExpiration;
		return $cookieDuration;
	}

	/**
	 * Take a URL Host Template and return the mobile token portion
	 *
	 * Eg if a desktop domain is en.wikipedia.org, but the mobile variant is
	 * en.m.wikipedia.org, the mobile token is 'm.'
	 *
	 * @param string $mobileUrlHostTemplate URL host
	 * @return string
	 */
	public function getMobileHostToken( $mobileUrlHostTemplate ) {
		return preg_replace( '/%h[0-9]\.{0,1}/', '', $mobileUrlHostTemplate );
	}

	/**
	 * Get the template for mobile URLs.
	 * @see $wgMobileUrlTemplate
	 * @return string
	 */
	public function getMobileUrlTemplate() {
		if ( !$this->mobileUrlTemplate ) {
			$this->mobileUrlTemplate = $this->getMFConfig()->get( 'MobileUrlTemplate' );
		}
		return $this->mobileUrlTemplate;
	}

	/**
	 * Take a URL and return a copy that conforms to the mobile URL template
	 * @param string $url URL to convert
	 * @param bool $forceHttps should force HTTPS?
	 * @return string|bool
	 */
	public function getMobileUrl( $url, $forceHttps = false ) {
		if ( $this->shouldDisplayMobileView() ) {
			$subdomainTokenReplacement = null;
			if ( Hooks::run( 'GetMobileUrl', [ &$subdomainTokenReplacement, $this ] ) ) {
				if ( !empty( $subdomainTokenReplacement ) ) {
					$mobileUrlHostTemplate = $this->parseMobileUrlTemplate( 'host' );
					$mobileToken = $this->getMobileHostToken( $mobileUrlHostTemplate );
					$this->mobileUrlTemplate = str_replace(
						$mobileToken,
						$subdomainTokenReplacement,
						$this->getMobileUrlTemplate()
					);
				}
			}
		}

		$parsedUrl = wfParseUrl( $url );
		// if parsing failed, maybe it's a local Url, try to expand and reparse it - task T107505
		if ( !$parsedUrl ) {
			$expandedUrl = wfExpandUrl( $url );
			if ( $expandedUrl ) {
				$parsedUrl = wfParseUrl( $expandedUrl );
			}
			if ( !$expandedUrl || !$parsedUrl ) {
				return false;
			}
		}

		$this->updateMobileUrlHost( $parsedUrl );
		if ( $forceHttps ) {
			$parsedUrl['scheme'] = 'https';
			$parsedUrl['delimiter'] = '://';
		}

		$assembleUrl = wfAssembleUrl( $parsedUrl );
		return $assembleUrl;
	}

	/**
	 * If a mobile-domain is specified by the $wgMobileUrlTemplate and
	 * there's a mobile header, then we assume the user is accessing
	 * the site from the mobile-specific domain (because why would the
	 * desktop site set the header?).
	 * @return bool
	 */
	public function usingMobileDomain() {
		$config = $this->getMFConfig();
		$mobileHeader = $config->get( 'MFMobileHeader' );
		return ( $config->get( 'MobileUrlTemplate' )
			&& $mobileHeader
			&& $this->getRequest()->getHeader( $mobileHeader ) !== false
		);
	}

	/**
	 * Take a URL and return a copy that removes any mobile tokens
	 * @param string $url representing a page on the mobile domain e.g. `https://en.m.wikipedia.org/`
	 * @return string (absolute url)
	 */
	public function getDesktopUrl( $url ) {
		$parsedUrl = wfParseUrl( $url );
		$this->updateDesktopUrlHost( $parsedUrl );
		$this->updateDesktopUrlQuery( $parsedUrl );
		$desktopUrl = wfAssembleUrl( $parsedUrl );
		return $desktopUrl;
	}

	/**
	 * Update host of given URL to conform to mobile URL template.
	 * @param array &$parsedUrl Result of parseUrl() or wfParseUrl()
	 */
	protected function updateMobileUrlHost( &$parsedUrl ) {
		if ( IP::isIPAddress( $parsedUrl['host'] ) ) {
			// Do not update host when IP is used
			return;
		}
		$mobileUrlHostTemplate = $this->parseMobileUrlTemplate( 'host' );
		if ( !strlen( $mobileUrlHostTemplate ) ) {
			return;
		}

		$parsedHostParts = explode( ".", $parsedUrl['host'] );
		$templateHostParts = explode( ".", $mobileUrlHostTemplate );
		$targetHostParts = [];

		foreach ( $templateHostParts as $key => $templateHostPart ) {
			if ( strstr( $templateHostPart, '%h' ) ) {
				$parsedHostPartKey = substr( $templateHostPart, 2 );
				if ( !array_key_exists( $parsedHostPartKey, $parsedHostParts ) ) {
					// invalid pattern for this host, ignore
					return;
				}
				$targetHostParts[$key] = $parsedHostParts[$parsedHostPartKey];
			} elseif ( isset( $parsedHostParts[$key] )
				&& $templateHostPart == $parsedHostParts[$key] ) {
				$targetHostParts = $parsedHostParts;
				break;
			} else {
				$targetHostParts[$key] = $templateHostPart;
			}
		}

		$parsedUrl['host'] = implode( ".", $targetHostParts );
	}

	/**
	 * Update the host of a given URL to strip out any mobile tokens
	 * @param array &$parsedUrl Result of parseUrl() or wfParseUrl()
	 */
	protected function updateDesktopUrlHost( &$parsedUrl ) {
		$server = $this->getConfig()->get( 'Server' );

		$mobileUrlHostTemplate = $this->parseMobileUrlTemplate( 'host' );
		if ( !strlen( $mobileUrlHostTemplate ) ) {
			return;
		}

		$parsedWgServer = wfParseUrl( $server );
		$parsedUrl['host'] = $parsedWgServer['host'];
	}

	/**
	 * Update the query portion of a given URL to remove any 'useformat' params
	 * @param array &$parsedUrl Result of parseUrl() or wfParseUrl()
	 */
	protected function updateDesktopUrlQuery( &$parsedUrl ) {
		if ( isset( $parsedUrl['query'] ) && strpos( $parsedUrl['query'], 'useformat' ) !== false ) {
			$query = wfCgiToArray( $parsedUrl['query'] );
			unset( $query['useformat'] );
			$parsedUrl['query'] = wfArrayToCgi( $query );
		}
	}

	/**
	 * Update path of given URL to conform to mobile URL template.
	 *
	 * NB: this is not actually being used anywhere at the moment. It will
	 * take some magic to get MW to properly handle path modifications like
	 * this is intended to provide. This will hopefully be implemented someday
	 * in the not to distant future.
	 *
	 * @param array &$parsedUrl Result of parseUrl() or wfParseUrl()
	 */
	protected function updateMobileUrlPath( &$parsedUrl ) {
		$scriptPath = $this->getConfig()->get( 'ScriptPath' );

		$mobileUrlPathTemplate = $this->parseMobileUrlTemplate( 'path' );

		// if there's no path template, no reason to continue.
		if ( !strlen( $mobileUrlPathTemplate ) ) {
			return;
		}

		// find out if we already have a templated path
		$templatePathOffset = strpos( $mobileUrlPathTemplate, '%p' );
		$templatePathSansToken = substr( $mobileUrlPathTemplate, 0, $templatePathOffset );
		if ( substr_compare( $parsedUrl[ 'path' ], $scriptPath . $templatePathSansToken, 0 ) > 0 ) {
			return;
		}

		$scriptPathLength = strlen( $scriptPath );
		// the "+ 1" removes the preceding "/" from the path sans $wgScriptPath
		$pathSansScriptPath = substr( $parsedUrl[ 'path' ], $scriptPathLength + 1 );
		$parsedUrl[ 'path' ] = $scriptPath . $templatePathSansToken . $pathSansScriptPath;
	}

	/**
	 * Parse mobile URL template into its host and path components.
	 *
	 * Optionally specify which portion of the template you want returned.
	 * @param string $part which part to return?
	 * @return mixed
	 */
	public function parseMobileUrlTemplate( $part = null ) {
		$mobileUrlTemplate = $this->getMobileUrlTemplate();

		$pathStartPos = strpos( $mobileUrlTemplate, '/' );

		/**
		 * This if/else block exists because of an annoying aspect of substr()
		 * Even if you pass 'null' or 'false' into the 'length' param, it
		 * will return an empty string.
		 * http://www.stopgeek.com/wp-content/uploads/2007/07/sense.jpg
		 */
		if ( $pathStartPos === false ) {
			$host = substr( $mobileUrlTemplate, 0 );
		} else {
			$host = substr( $mobileUrlTemplate, 0,  $pathStartPos );
		}

		$path = substr( $mobileUrlTemplate, $pathStartPos );

		if ( $part == 'host' ) {
			return $host;
		} elseif ( $part == 'path' ) {
			return $path;
		} else {
			return [ 'host' => $host, 'path' => $path ];
		}
	}

	/**
	 * Toggles view to one specified by the user
	 *
	 * If a user has requested a particular view (eg clicked 'Desktop' from
	 * a mobile page), set the requested view for this particular request
	 * and set a cookie to keep them on that view for subsequent requests.
	 *
	 * @param string $view User requested particular view
	 */
	public function toggleView( $view ) {
		$this->viewChange = $view;
		if ( !strlen( trim( $this->getMobileUrlTemplate() ) ) ) {
			$this->setUseFormat( $view );
		}
	}

	/**
	 * Performs view change as requested vy toggleView()
	 */
	public function doToggling() {
		$mobileUrlTemplate = $this->getMobileUrlTemplate();

		if ( !$this->viewChange ) {
			return;
		}

		$query = $this->getRequest()->getQueryValues();
		unset( $query['mobileaction'] );
		unset( $query['useformat'] );
		unset( $query['title'] );
		$url = $this->getTitle()->getFullURL( $query, false, PROTO_CURRENT );

		if ( $this->viewChange == 'mobile' ) {
			// unset stopMobileRedirect cookie
			// @TODO is this necessary with unsetting the cookie via JS?
			$this->unsetStopMobileRedirectCookie();

			// if no mobileurl template, set mobile cookie
			if ( !strlen( trim( $mobileUrlTemplate ) ) ) {
				$this->setUseFormatCookie();
			} else {
				// else redirect to mobile domain
				$mobileUrl = $this->getMobileUrl( $url );
				$this->getOutput()->redirect( $mobileUrl, 301 );
			}
		} elseif ( $this->viewChange == 'desktop' ) {
			// set stopMobileRedirect cookie
			$this->setStopMobileRedirectCookie();
			// unset useformat cookie
			if ( $this->getUseFormatCookie() == "true" ) {
				$this->unsetUseFormatCookie();
			}

			if ( strlen( trim( $mobileUrlTemplate ) ) ) {
				// if mobileurl template, redirect to desktop domain
				$desktopUrl = $this->getDesktopUrl( $url );
				$this->getOutput()->redirect( $desktopUrl, 301 );
			}
		}
	}

	/**
	 * Determine whether or not we need to toggle the view, and toggle it
	 */
	public function checkToggleView() {
		if ( !$this->toggleViewChecked ) {
			$this->toggleViewChecked = true;
			$mobileAction = $this->getMobileAction();
			if ( $mobileAction == 'toggle_view_desktop' ) {
				$this->toggleView( 'desktop' );
			} elseif ( $mobileAction == 'toggle_view_mobile' ) {
				$this->toggleView( 'mobile' );
			}
		}
	}

	/**
	 * Determine whether or not a given URL is local
	 *
	 * @param string $url URL to check against
	 * @return bool
	 */
	public function isLocalUrl( $url ) {
		$parsedTarget = wfParseUrl( $url );
		$parsedServer = wfParseUrl( $this->getMFConfig()->get( 'Server' ) );
		return $parsedTarget['host'] === $parsedServer['host'];
	}

	/**
	 * Add key/value pairs for analytics purposes to $this->analyticsLogItems
	 * @param string $key for <key> in `X-Analytics: <key>=<value>`
	 * @param string $val for <value> in `X-Analytics: <key>=<value>`
	 */
	public function addAnalyticsLogItem( $key, $val ) {
		$key = trim( $key );
		$val = trim( $val );
		$this->analyticsLogItems[$key] = $val;
	}

	/**
	 * Read key/value pairs for analytics purposes from $this->analyticsLogItems
	 * @return array
	 */
	public function getAnalyticsLogItems() {
		return $this->analyticsLogItems;
	}

	/**
	 * Get HTTP header string for X-Analytics
	 *
	 * This is made up of key/vaule pairs and is used for analytics
	 * purposes.
	 *
	 * @return string|bool
	 */
	public function getXAnalyticsHeader() {
		$response = $this->getRequest()->response();
		$currentHeader = method_exists( $response, 'getHeader' ) ?
			$response->getHeader( 'X-Analytics' ) : '';
		parse_str( preg_replace( '/; */', '&', $currentHeader ), $logItems );
		$logItems += $this->getAnalyticsLogItems();
		if ( count( $logItems ) ) {
			$xanalytics_items = [];
			foreach ( $logItems as $key => $val ) {
				$xanalytics_items[] = urlencode( $key ) . "=" . urlencode( $val );
			}
			$headerValue = implode( ';', $xanalytics_items );
			return "X-Analytics: $headerValue";
		} else {
			return false;
		}
	}

	/**
	 * Take a key/val pair in string format and add it to $this->analyticsLogItems
	 *
	 * @param string $xanalytics_item In the format key=value
	 */
	public function addAnalyticsLogItemFromXAnalytics( $xanalytics_item ) {
		list( $key, $val ) = explode( '=', $xanalytics_item, 2 );
		$this->addAnalyticsLogItem( urldecode( $key ), urldecode( $val ) );
	}

	/**
	 * Adds analytics log items if the user is in beta mode
	 *
	 * Invoked from MobileFrontendHooks::onRequestContextCreateSkin()
	 *
	 * Making changes to what this method logs? Make sure you update the
	 * documentation for the X-Analytics header: https://wikitech.wikimedia.org/wiki/X-Analytics
	 */
	public function logMobileMode() {
		if ( $this->isBetaGroupMember() ) {
			$this->addAnalyticsLogItem( 'mf-m', 'b' );
		}
	}

	/**
	 * Process-local override for MFStripResponsiveImages, used by
	 * the mobileview API request.
	 */
	private $stripResponsiveImagesOverride = null;

	/**
	 * Should image thumbnails in pages remove the high-density additions
	 * during this request?
	 *
	 * @return bool
	 */
	public function shouldStripResponsiveImages() {
		if ( $this->stripResponsiveImagesOverride === null ) {
			return $this->shouldDisplayMobileView()
				&& $this->getMFConfig()->get( 'MFStripResponsiveImages' );
		} else {
			return $this->stripResponsiveImagesOverride;
		}
	}

	/**
	 * Config override for responsive image strip mode.
	 *
	 * @param bool $val New value
	 */
	public function setStripResponsiveImages( $val ) {
		$this->stripResponsiveImagesOverride = $val;
	}

	/**
	 * Gets whether Wikibase descriptions should be shown in search results, including nearby search,
	 * and watchlists; or as taglines on article pages.
	 * Doesn't take into account whether the wikidata descriptions
	 * feature has been enabled.
	 *
	 * @param string $feature which description to show?
	 * @return bool
	 * @throws DomainException If `feature` isn't one that shows Wikidata descriptions. See the
	 *  `wgMFDisplayWikibaseDescriptions` configuration variable for detail
	 */
	public function shouldShowWikibaseDescriptions( $feature ) {
		$config = $this->getMFConfig();
		$displayWikibaseDescriptions = $config->get( 'MFDisplayWikibaseDescriptions' );
		if ( !isset( $displayWikibaseDescriptions[ $feature ] ) ) {
			throw new DomainException(
				"\"{$feature}\" isn't a feature that shows Wikidata descriptions."
			);
		}

		return $displayWikibaseDescriptions[ $feature ];
	}
}

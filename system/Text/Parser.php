<?php
/**
 * @brief		Text Parser
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		12 Jun 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Text;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Text Parser
 */
class _Parser
{	
	/**
	 * @brief	Cached permissions
	 */
	protected static $permissions = array();

	/**
	 * @brief	File object classes
	 */
	protected static $fileObjectClasses = array();
	
	/**
	 * @brief	Emoticons
	 */
	protected static $emoticons = NULL;
	
	/**
	 * @brief	Any error messages generated during the parse routine
	 *  @li URL_NOT_ALLOWED
	 */
	public $errors = array();
		
	/**
	 * Parse statically
	 *
	 * @param	string				$value	The value to parse
	 * @param	bool				$bbcode				Parse BBCode?
	 * @param	array|null			$attachIds			array of ID numbers to idenfity content for attachments if the content has been saved - the first two must be int or null, the third must be string or null. If content has not been saved yet, an MD5 hash used to claim attachments after saving.
	 * @param	\IPS\Member|null	$member				If parsing BBCode or attachments, the member posting. NULL will use currently logged in member.
	 * @param	string|bool			$area				If parsing BBCode or attachments, the Editor area we're parsing in. e.g. "core_Signatures". A boolean value will allow or disallow all BBCodes that are dependant on area.
	 * @param	bool				$filterProfanity	Remove profanity?
	 * @param	bool				$cleanHtml			If TRUE, HTML will be cleaned through HTMLPurifier
	 * @param	callback			$htmlPurifierConfig	A function which will be passed the HTMLPurifier_Config object to customise it - see example
	 * @param	int					$requestTimeout		NULL uses \IPS\DEFAULT_REQUEST_TIMEOUT. Can be set manually to avoid large timeouts on rebuild processes
	 * @return	string
	 * @see		__construct
	 */
	public static function parseStatic( $value, $bbcode=FALSE, $attachIds=NULL, $member=NULL, $area=FALSE, $filterProfanity=TRUE, $cleanHtml=TRUE, $htmlPurifierConfig=NULL, $requestTimeout=NULL )
	{
		$obj = new self( $bbcode, $attachIds, $member, $area, $filterProfanity, $cleanHtml, $htmlPurifierConfig, $requestTimeout );
		return $obj->parse( $value );
	}
	
	/**
	 * Can use plugin?
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string		$key	Plugin key
	 * @param	string		$area	The Editor area
	 * @return	bool
	 */
	public static function canUse( \IPS\Member $member, $key, $area )
	{
		$permissionSettings = json_decode( \IPS\Settings::i()->ckeditor_permissions, TRUE );
		
		if ( !isset( static::$permissions[ $member->member_id ][ $key ] ) )
		{
			if ( !isset( $permissionSettings[ $key ] ) )
			{
				static::$permissions[ $member->member_id ][ $key ] = TRUE;
			}
			else
			{
				$val = TRUE;
				if ( $permissionSettings[ $key ]['groups'] !== '*' )
				{
					if ( !$member->inGroup( $permissionSettings[ $key ]['groups'] ) )
					{
						$val = FALSE;
					}
				}
				if ( $permissionSettings[ $key ]['areas'] !== '*' )
				{
					if ( !in_array( $area, $permissionSettings[ $key ]['areas'] ) )
					{
						$val = FALSE;
					}
				}
				static::$permissions[ $member->member_id ][ $key ] = $val;
			}
		}
		
		return static::$permissions[ $member->member_id ][ $key ];
	}
	
	/**
	 * Get BBCode Tags
	 * Even though we no longer support Custom BBCode in favour of CKEditor plugins, this
	 * method is separated so that hooks can continue to provide additional BBCodes if
	 * administrators really want them.
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string|bool	$area	The Editor area we're parsing in. e.g. "core_Signatures". A boolean value will allow or disallow all BBCodes that are dependant on area.
	 * @code
	 	return array(
	 		'font'	=> array(					// Key represents the BBCode tag (e.g. [font])
		 		'tag'			=> 'span',						// The HTML tag to use
		 		'attributes'	=> array( ... )					// Key/Value pairs of attributes to use (optional) - can use {option} to get the [tag=option] value
		 		'callback'		=> function( $node, $matches, $document )	// A callback to modify the DOMNode object
		 		{
		 			...
		 		},
		 		'block'			=> FALSE,						// If this is a block-level tag (optional, default false)
		 		'single'		=> FALSE,						// If this is a single tag, with no content (optional, default false)
	 	)
	 * @endcode
	 * @return	array
	 */
	public function bbcodeTags( \IPS\Member $member, $area )
	{
		$return = array();
		
		/* Acronym */
		$return['acronym'] = array( 'tag' => 'abbr', 'attributes' => array( 'title' => '{option}' ) );
		
		/* Background */
		if ( static::canUse( $member, 'BGColor', $area ) )
		{
			$return['background'] = array( 'tag' => 'span', 'attributes' => array( 'style' => 'background-color:{option}' ) );
		}
		
		/* Bold */
		if ( static::canUse( $member, 'Bold', $area ) )
		{
			$return['b'] = array( 'tag' => 'strong' );
		}
		
		/* Code */
		if ( static::canUse( $member, 'pbckcode', $area ) )
		{
			$code = array( 'tag' => 'pre', 'attributes' => array( 'class' => 'ipsCode' ), 'block' => TRUE, 'noParse' => TRUE, 'noChildren' => TRUE );
			
			$return['code'] = $code;
			$return['codebox'] = $code;
			$return['html'] = $code;
			$return['php'] = $code;
			$return['sql'] = $code;
			$return['xml'] = $code;
		}
		
		/* Color */
		if ( static::canUse( $member, 'TextColor', $area ) )
		{
			$return['color'] = array( 'tag' => 'span', 'attributes' => array( 'style' => 'color:{option}' ) );
		}
		
		/* Font */
		if ( static::canUse( $member, 'Font', $area ) )
		{
			$return['font'] = array( 'tag' => 'span', 'attributes' => array( 'style' => 'font-family:{option}' ) );
		}
		
		/* HR */
		$return['hr'] = array( 'tag' => 'hr', 'single' => TRUE );

		/* Image */
		if ( static::canUse( $member, 'ipsImage', $area ) )
		{
			$return['img'] = array( 'tag' => 'img', 'attributes' => array( 'src' => '{option}', 'class' => 'ipsImage' ), 'single' => TRUE );
		}
		
		/* Indent */
		if ( static::canUse( $member, 'Indent', $area ) )
		{
			$return['indent'] = array( 'tag' => 'div', 'attributes' => array( 'style' => 'margin-left:{option}px' ), 'block' => FALSE );
		}
		
		/* Italics */
		if ( static::canUse( $member, 'Italic', $area ) )
		{
			$return['i'] = array( 'tag' => 'em' );
		}
		
		/* Justify */
		if ( static::canUse( $member, 'JustifyLeft', $area ) )
		{
			$return['left'] = array( 'tag' => 'div', 'attributes' => array( 'style' => 'text-align:left' ), 'block' => TRUE );
		}
		if ( static::canUse( $member, 'JustifyCenter', $area ) )
		{
			$return['center'] = array( 'tag' => 'div', 'attributes' => array( 'style' => 'text-align:center' ), 'block' => TRUE );
		}
		if ( static::canUse( $member, 'JustifyRight', $area ) )
		{
			$return['right'] = array( 'tag' => 'div', 'attributes' => array( 'style' => 'text-align:right' ), 'block' => TRUE );
		}
		
		/* Links */
		if ( static::canUse( $member, 'ipsLink', $area ) )
		{
			/* Global */
			$return['email'] = array( 'tag' => 'a', 'attributes' => array( 'href' => 'mailto:{option}' ) );
			$return['member'] = array(
				'tag'		=> 'a',
				'callback'	=> function( $node, $matches, $document )
				{
					$memberName = trim( $matches[2], '\'"' );
					try
					{
						$member = \IPS\Member::load( $memberName, 'name' );
						if ( $member->member_id != 0)
						{
							$node->setAttribute( 'href',  $member->url() );
							$node->appendChild( $document->createTextNode( $member->name ) );
						}

					}
					catch ( \Exception $e ) {}
					return $node;
				},
				'single'	=> TRUE,
			);

			$return['url'] = array(
				'tag'			=> 'a',
				'attributes'	=> array( 'href' => '{option}' ),
				'callback'		=> function( $node, $matches, $document )
				{
					if( isset( $matches[2] ) )
					{
						$node->setAttribute( 'href', trim( $matches[2], "'\"" ) );
					}

					return $node;
				}
			);
			
			/* Blog */
			if ( \IPS\Application::appIsEnabled('blog') )
			{
				$return['blog'] = array(
					'tag'		=> 'a',
					'callback'	=> function( $node, $matches, $document )
					{
						$node->setAttribute( 'href', \IPS\Http\Url::internal( "app=blog&amp;showblog=" . urlencode( $matches[2] ), 'front' ) );
					}
				);
				$return['entry'] = array(
					'tag'		=> 'a',
					'callback'	=> function( $node, $matches, $document )
					{
						$node->setAttribute( 'href', \IPS\Http\Url::internal( "app=blog&amp;showentry"  . urlencode( $matches[2] ), 'front' ) );
					}
				);
			}
			
			/* Forums */
			if ( \IPS\Application::appIsEnabled('forums') )
			{
				$return['post'] = array(
					'tag'		=> 'a',
					'callback'	=> function( $node, $matches, $document )
					{
						$node->setAttribute( 'href', \IPS\Http\Url::internal( "act=findpost&amp;pid=" . urlencode( $matches[2] ), 'front' ) );
					}
				);
				$return['snapback'] = array(
					'tag'		=> 'a',
					'callback'	=> function( $node, $matches, $document )
					{
						$node->setAttribute( 'href', \IPS\Http\Url::internal( "act=findpost&amp;pid=" . urlencode( $matches[2] ), 'front' ) );
						$node->appendChild( $document->createEntityReference( 'larr' ) );
						return $node;
					},
					'single'	=> TRUE,
				);
				$return['topic'] = array(
					'tag'		=> 'a',
					'callback'	=> function( $node, $matches, $document )
					{
						try
						{
							$topic = \IPS\forums\Topic::load( intval( $matches[2] ) );

							$node->setAttribute( 'href', \IPS\Http\Url::internal( "app=forums&module=forums&controller=topic&id=" . urlencode( $matches[2] ), 'front', 'forums_topic', array( $topic->title_seo ) ) );
						}
						catch( \Exception $e )
						{
							$node->setAttribute( 'href', \IPS\Http\Url::internal( "app=forums&module=forums&controller=topic&id=" . urlencode( $matches[2] ), 'front' ) );
						}

						return $node;
					}
				);
			}
		}
		
		/* List */
		if ( static::canUse( $member, 'BulletedList', $area ) or static::canUse( $member, 'NumberedList', $area ) )
		{
			$return['list'] = array(
				'tag' => 'ul',
				'attributes' => array( 'data-ipsBBCode-list' => 'true' ),
				'callback' => function( $node, $matches, $document )
				{
					if ( isset( $matches[2] ) )
					{
						$node = $document->createElement( 'ol' );
						$node->setAttribute( 'data-ipsBBCode-list', 'true' );
						switch ( $matches[2] )
						{
							case '1':
								$node->setAttribute( 'style', 'list-style-type: decimal' );
								break;
							case '0':
								$node->setAttribute( 'style', 'list-style-type: decimal-leading-zero' );
								break;
							case 'a':
								$node->setAttribute( 'style', 'list-style-type: lower-alpha' );
								break;
							case 'A':
								$node->setAttribute( 'style', 'list-style-type: upper-alpha' );
								break;
							case 'i':
								$node->setAttribute( 'style', 'list-style-type: lower-roman' );
								break;
							case 'I':
								$node->setAttribute( 'style', 'list-style-type: upper-roman' );
								break;
						}
					}
					
					return $node;
				},
				'block' => TRUE
			);
		}
		
		/* Media tags can be stripped as we'll automatically act on the URL */
		$return['blogmedia'] = NULL;
		$return['flash'] = NULL;
		$return['media'] = NULL;
		$return['movie'] = NULL;
		$return['video'] = NULL;
		$return['youtube'] = NULL;

		/* Quote */
		if ( static::canUse( $member, 'ipsQuote', $area ) )
		{
			$return['quote'] = array(
				'tag' => 'blockquote',
				'callback' => function( $node, $matches )
				{
					$node->setAttribute( 'class', 'ipsQuote' );
					$node->setAttribute( 'data-ipsQuote', '' );
					$node->setAttribute( 'data-cite', isset( $matches[2] ) ? $matches[2] : NULL );
					return $node;
				},
				'block' => TRUE
			);
		}

		/* Size */
		if ( static::canUse( $member, 'FontSize', $area ) )
		{
			$return['size'] = array(
				'tag'		=> 'span',
				'callback'	=> function( $node, $matches )
				{
					switch ( $matches[2] )
					{
						case 1:
							$node->setAttribute( 'style', 'font-size:8px' );
							break;
						case 2:
							$node->setAttribute( 'style', 'font-size:10px' );
							break;
						case 3:
							$node->setAttribute( 'style', 'font-size:12px' );
							break;
						case 4:
							$node->setAttribute( 'style', 'font-size:14px' );
							break;
						case 5:
							$node->setAttribute( 'style', 'font-size:18px' );
							break;
						case 6:
							$node->setAttribute( 'style', 'font-size:24px' );
							break;
						case 7:
							$node->setAttribute( 'style', 'font-size:36px' );
							break;
						case 8:
							$node->setAttribute( 'style', 'font-size:48px' );
							break;
					}
					return $node;
				}
			);
		}
		
		/* Spoiler */
		if ( static::canUse( $member, 'ipsSpoiler', $area ) )
		{
			$return['spoiler'] = array( 'tag' => 'blockquote', 'attributes' => array( 'class' => 'ipsStyle_spoiler', 'data-ipsSpoiler' => '', 'tabindex' => '0' ), 'block' => TRUE );
		}
		
		/* Strike */
		if ( static::canUse( $member, 'Strike', $area ) )
		{
			$return['s'] = array( 'tag' => 'span', 'attributes' => array( 'style' => 'text-decoration:line-through' ) );
			$return['strike'] = array( 'tag' => 'span', 'attributes' => array( 'style' => 'text-decoration:line-through' ) );
		}
		
		/* Subscript */
		if ( static::canUse( $member, 'Subscript', $area ) )
		{
			$return['sub'] = array( 'tag' => 'sub' );
		}
		
		/* Superscript */
		if ( static::canUse( $member, 'Superscript', $area ) )
		{
			$return['sup'] = array( 'tag' => 'sup' );
		}
		
		/* Underline */
		if ( static::canUse( $member, 'Underline', $area ) )
		{
			$return['u'] = array( 'tag' => 'span', 'attributes' => array( 'style' => 'text-decoration:underline' ) );
		}

		/* IP.Content "page" and the necessary links */
		if ( static::canUse( $member, 'Page', $area ) )
		{
			$return['page'] = array(
				'tag' => 'div',
				'callback' => function( $node, $matches, $document )
				{

					$node->setAttribute( 'data-role', 'contentPage' );

					$break	= $document->createElement( 'hr' );
					$break->setAttribute( 'data-role', 'contentPageBreak' );

					$node->appendChild( $break );

					return $node;
				},
				'attributes' => array(),
				'block' => TRUE,
			);
		}

		return $return;
	}
	
	/**
	 * Get OEmbed Services
	 * Implemented in this way so it's easy for hook authors to override if they wanted to
	 *
	 * @see		<a href="http://www.oembed.com">oEmbed</a>
	 * @return	array
	 */
	protected static function oembedServices()
	{
		return array(
			'youtube.com'					=> 'https://www.youtube.com/oembed',
			'm.youtube.com'					=> 'https://www.youtube.com/oembed',
			'youtu.be'						=> 'https://www.youtube.com/oembed',
			'flickr.com'					=> 'http://www.flickr.com/services/oembed/',
			'flic.kr'						=> 'http://www.flickr.com/services/oembed/',
			'hulu.com'						=> 'http://www.hulu.com/api/oembed.json',
			'vimeo.com'						=> 'http://vimeo.com/api/oembed.json',
			'collegehumor.com'				=> 'http://www.collegehumor.com/oembed.json',
			'twitter.com'					=> 'https://api.twitter.com/1/statuses/oembed.json',
			'instagr.am'					=> 'http://api.instagram.com/oembed',
			'instagram.com'					=> 'http://api.instagram.com/oembed',
			'soundcloud.com'				=> 'http://soundcloud.com/oembed',
			'open.spotify.com'				=> 'https://embed.spotify.com/oembed/',
			'play.spotify.com'				=> 'https://embed.spotify.com/oembed/',
			'ted.com'						=> 'http://www.ted.com/talks/oembed.json',
			'vine.co'						=> 'https://vine.co/oembed.json',
		);
	}
	
	/**
	 * Get URL bases (whout schema) that we'll allow iframes from
	 *
	 * @return	array
	 */
	protected static function allowedIFrameBases()
	{
		$return = array();
		
		/* The community itself (for internal embeds) */
		$return[] = str_replace( array( 'http://', 'https://' ), '', \IPS\Settings::i()->base_url );
		
		/* Our default embed options */		
		$return = array_merge( $return, array(
			'www.youtube.com/embed/',
			'player.vimeo.com/video/',
			'www.hulu.com/embed.html',
			'www.collegehumor.com/e/',
			'embed-ssl.ted.com/',
			'vine.co/',
			'gfycat.com/',
			'embed.spotify.com/'
		) );
		
		/* Extra admin-defined options */
		if ( \IPS\Settings::i()->editor_allowed_iframe_bases )
		{
			$return = array_merge( $return, explode( ',', \IPS\Settings::i()->editor_allowed_iframe_bases ) );
		}
		
		return $return;
	}
	
	/**
	 * Convert URL to embed HTML
	 *
	 * @param	string		$url		The URL
	 * @param	bool		$iframe		Some services need to be in iFrames so they cannot be edited in the editor. If TRUE, will return contents for iframe, if FALSE, return the iframe.
	 * @return	string|null	HTML embded code, or NULL if URL is not embeddable
	 */
	public static function embeddableMedia( $url, $iframe=FALSE )
	{
		/* Init */
		if ( filter_var( $url, FILTER_VALIDATE_URL ) === FALSE )
		{
			return NULL;
		}
		$parsedUrl = parse_url( $url );
		$domain = $parsedUrl['host'];
		if ( mb_substr( $domain, 0, 4 ) === 'www.' )
		{
			$domain = mb_substr( $domain, 4 );
		}
		if( !$domain )
		{
			return null;
		}

		/* oEmbed services */
		$oembedServices = static::oembedServices();
		if ( array_key_exists( $domain, $oembedServices ) )
		{
			try
			{
				$oembedUrl = \IPS\Http\Url::external( $oembedServices[ $domain ] )->setQueryString( array( 'format' => 'json', 'url' => $url, 'scheme' => ( $parsedUrl['scheme'] === 'https' or \IPS\Request::i()->isSecure() ) ? 'https' : null ) );
				$response = $oembedUrl->request( static::$requestTimeout )->get()->decodeJson();
				
				/* Ignore errors - this will be caught by the catch statement below */
				if( !isset( $response['type'] ) )
				{
					throw new \RuntimeException( 'NO_EMBED_TYPE' );
				}

				/* The "type" parameter is a way for services to indicate the type of content they are retruning. It is not strict, but we use it to identify the best styles to apply. */
				switch ( $response['type'] )
				{
					/* Static photo - show an <img> tag, linked if necessary, using .ipsImage to be responsive. Similar outcome to if a user had used the "insert image from URL" button */
					case 'photo':
						return \IPS\Theme::i()->getTemplate( 'embed', 'core', 'global' )->photo( $response['url'], $url, $response['title'] );
					
					/* Video - insert the provided HTML directly (it will be a video so there's nothing we need to prevent from being edited), using .ipsEmbeddedVideo to make it responsive */
					case 'video':
                        $response['html'] = str_replace( 'allowfullscreen', 'allowfullscreen="true"', $response['html'] );
						return \IPS\Theme::i()->getTemplate( 'embed', 'core', 'global' )->video( $response['html'] );
					
					/* Other - show an <iframe> with the provided HTML inside, using .ipsEmbeddedOther to make the width right and data-controller="core.front.core.autoSizeIframe" to make the height right */
					case 'rich':						
						if ( $iframe )
						{
							return $response['html'];
						}
						else
						{
							return \IPS\Theme::i()->getTemplate( 'embed', 'core', 'global' )->iframe( (string) \IPS\Http\Url::internal('app=core&module=system&controller=embed')->setQueryString( 'url', $url ) );
						}
					
					/* Link - none of the defautl services use this, but provided for completeness. Just inserts an <a> tag */
					case 'link':
						return \IPS\Theme::i()->getTemplate( 'embed', 'core', 'global' )->link( $response['url'], $response['title'] );
				}
			}
			catch ( \Exception $e ) {}
		}
		
		/* Other services which don't support oEmbed but we still want to support */
		if ( in_array( $domain, array( 'facebook.com', 'plus.google.com', 'gfycat.com' ) ) )
		{
            /* Make sure it's a URL we want to embed */
			switch ( $domain )
			{
				case 'facebook.com':
					$regex = '/^https:\/\/www\.facebook\.com\/(.+?\/permalink\/|photo|video\.php|(.+?)\/posts\/|(.+?)\/photos\/|(.+?)\/videos\/)/i';
					break;
				case 'plus.google.com':
					$regex = '/^https:\/\/plus.google.com\/\d+\/posts\//i';
					break;
				case 'gfycat.com':
					$regex = '/^https?:\/\/gfycat.com\/(.+)/i';
			}
			if ( !preg_match( $regex, $url, $matches ) )
			{
				return NULL;
			}
									
			/* Show the iframe... */
			if ( !$iframe )
			{
				if ( $domain == 'gfycat.com' )
				{
					try
					{
						$data = \IPS\Http\Url::external( 'https://gfycat.com/cajax/get/' . $matches[1] )->request()->get()->decodeJson();
						if( isset( $data['gfyItem'] ) )
						{
							return \IPS\Theme::i()->getTemplate( 'embed', 'core', 'global' )->iframe( (string) \IPS\Http\Url::internal('app=core&module=system&controller=embed')->setQueryString( array( 'url' => $url, 'key' => $data['gfyItem']['gfyName'] ) ) );
						}
						else
						{
							return NULL;
						}
					}
					catch ( \IPS\Http\Request\Exception $e )
					{
						return NULL;
					}
				}
				else
				{
					return \IPS\Theme::i()->getTemplate( 'embed', 'core', 'global' )->iframe( (string) \IPS\Http\Url::internal('app=core&module=system&controller=embed')->setQueryString( 'url', $url ) );
				}
			}
			
			/* Or show the content... */
			else
			{
				switch ( $domain )
				{
					case 'gfycat.com':
						return \IPS\Theme::i()->getTemplate( 'global', 'core', 'front' )->blankTemplate( \IPS\Theme::i()->getTemplate( 'embed', 'core', 'global' )->gfycat( \IPS\Request::i()->key ) );
					
					case 'facebook.com':
						return \IPS\Theme::i()->getTemplate( 'global', 'core', 'front' )->blankTemplate( \IPS\Theme::i()->getTemplate( 'embed', 'core', 'global' )->facebook( $url ) );
					
					case 'plus.google.com':
						return \IPS\Theme::i()->getTemplate( 'global', 'core', 'front' )->blankTemplate( \IPS\Theme::i()->getTemplate( 'embed', 'core', 'global' )->google( $url ) );
				}
			}
		}

		/* Internal Embeds (links to other topics, etc) */
		try
		{
			$url = new \IPS\Http\Url( $url );
			$qs = array_merge( $url->queryString, $url->getFriendlyUrlData() );
			if ( isset( $qs['app'] ) and ( !isset( $qs['do'] ) or $qs['do'] != 'rss' ) )
			{
				foreach ( \IPS\Application::load( $qs['app'] )->extensions( 'core', 'ContentRouter' ) as $key => $extension )
				{
					$classes = $extension->classes;
					if ( isset( $extension->ownedNodes ) )
					{
						$classes = array_merge( $classes, $extension->ownedNodes );
					}
					if ( isset( $extension->embeddableContent ) )
					{
						$classes = array_merge( $classes, $extension->embeddableContent );
					}

					foreach ( $classes as $class )
					{
						try
						{
							if ( in_array( 'IPS\Content\Embeddable', class_implements( $class ) ) )
							{
								$item = $class::loadFromURL( $url );
								$urlDiff	= array_diff_assoc( $qs, array_merge( $item->url()->queryString, $item->url()->getFriendlyUrlData() ) );

								if( $item instanceof \IPS\Content\Embeddable AND !count( array_intersect( array( 'app', 'module', 'controller' ), array_keys( $urlDiff ) ) ) )
								{
									$title = ( $item instanceof \IPS\Content ) ? $item->mapped('title') : $item->_title;
									$preview = new \IPS\Http\Url( (string) $url );
									if ( \IPS\Settings::i()->use_friendly_urls )
									{
										$preview = $preview->stripArguments()->setQueryString( 'do', 'embed' );
									}
									else
									{
										$preview = $preview->setQueryString( 'do', 'embed' );
									}
									
									if( isset( $url->queryString['do'] ) and $url->queryString['do'] == 'findComment' )
									{
										$preview = $preview->setQueryString( 'embedComment', $url->queryString['comment'] );
									}
									elseif( isset( $url->queryString['do'] ) and $url->queryString['do'] == 'findReview' )
									{
										$preview = $preview->setQueryString( 'embedReview', $url->queryString['review'] );
									}
									if( isset( $url->queryString['do'] ) )
									{
										$preview = $preview->setQueryString( 'embedDo', $url->queryString['do'] );
									}
									if ( isset( $url->queryString['page'] ) AND $url->queryString['page'] > 1 )
									{
										$preview = $preview->setQueryString( 'page', $url->queryString['page'] );
									}
									return "<iframe src='{$preview}' frameborder='0' data-embedContent></iframe>";
								}
							}
						}
						catch ( \Exception $e ) {}
					}
				}
			}
		}
		catch ( \Exception $e ) {}
		
		/* Images */
		$extension = mb_substr( $url, mb_strrpos( $url, '.' ) + 1 );
		$questionPos = mb_strpos( $extension, '?' );
		if ( $questionPos !== FALSE )
		{
			$extension = mb_substr( $extension, 0, $questionPos );
		}
		$hashPos = mb_strpos( $extension, '#' );
		if ( $hashPos !== FALSE )
		{
			$extension = mb_substr( $extension, 0, $hashPos );
		}
		if ( in_array( mb_strtolower( $extension ), \IPS\Image::$imageExtensions ) )
		{
			$extra			= '';

            if ( !\IPS\Settings::i()->allow_remote_images )
            {
                throw new \DomainException;
            }
			
			/* Try getimagesize first, as it is more efficient when available */
			try
			{
				try
				{
					/* It isn't possible to set a timeout for getimagesize() so if a URL takes a long time too load, or times out, then that can cause parsing to fail. Create a temporary file from the remote one and use that instead. We will request the file here, which will throw an exception if it times out */
					$image = \IPS\Http\Url::external( $url )->request()->get();
					$temporary = tempnam( \IPS\TEMP_DIRECTORY, 'image' );
					\file_put_contents( $temporary, $image );

					$dims = @getimagesize( $temporary );
					
					if ( @is_file( $temporary ) )
					{
						@unlink( $temporary );
					}

					if( $dims === FALSE OR !isset( $dims[0] ) OR !$dims[0] )
					{
						throw new \RuntimeException;
					}
				}
				catch( \IPS\Http\Request\Exception $e )
				{
					return NULL;
				}
			}
			catch( \RuntimeException $e )
			{
				/* Remote servers can take too long to respond. Catch the exception here so that images are inserted as links instead */
				try
				{
					$image = \IPS\Image::create( (string) \IPS\Http\Url::external( $url )->request()->get() );
					$dims = array( 0 => $image->width, 1 => $image->height );
				}
				catch ( \Exception $e )
				{
					return NULL;
				}
			}

			$maxImageDims	= \IPS\Settings::i()->attachment_image_size ? explode( 'x', \IPS\Settings::i()->attachment_image_size ) : array( 1000, 750 );
			$width = NULL;
			$height = NULL;

			if ( is_array( $dims ) AND ( $dims[0] > 0 AND $dims[1] > 0 ) )
			{
				/* Check width first */
				if ( $dims[0] > $maxImageDims[0] )
				{
					$width	= $maxImageDims[0];
					$height = floor( $dims[1] / $dims[0] * $width );
				}
				
				if ( $height > $maxImageDims[1] )
				{
					$width	= floor( $maxImageDims[1] * ( $width / $height ) );
					$height = $maxImageDims[1];
				}
			}
			return \IPS\Theme::i()->getTemplate( 'embed', 'core', 'global' )->photo( $url, NULL, NULL, $width, $height );
		}
		
		/* Still here? */
		return NULL;
	}
	
	/**
	 * @brief	The current open block, needed by the BBCode parser
	 */
	protected $openBlock = NULL;
	
	/**
	 * @brief	How deep within a node already inserted into $openBlock we are
	 */
	protected $openBlockDepth = 0;
	
	/**
	 * @brief	All open blocks, needed by the BBCode parser
	 */
	protected $openBlocks = array();
	
	/**
	 * @brief	All open inline nodes, needed by the BBCode parser
	 */
	protected $inlineNodes = array();
		
	/**
	 * @brief	Parsing BBCode flag, needed by the BBCode parser for no-parse tags
	 */
	protected $parseOn = TRUE;
	
	/**
	 * @brief	BBCode Tags
	 */
	protected $bbcode = array();
	
	/**
	 * @brief	Attachment IDs
	 */
	protected $attachIds;
	
	/**
	 * @brief	Existing attachments
	 */
	public $existingAttachments = array();
	
	/**
	 * @brief	Member
	 */
	protected $member = NULL;
		
	/**
	 * @brief	Loose Profanity Filters
	 */
	protected $looseProfanity = array();
	
	/**
	 * @brief	Exact Profanity Filters
	 */
	protected $exactProfanity = array();
	
	/**
	 * @brief	Acronyms
	 */
	protected $acronyms = array();
		
	/**
	 * HTMLPurifier Object
	 */
	protected $htmlPurifier = NULL;
		
	/**
	 * @brief	Mapped attachments
	 */
	public $mappedAttachments = array();
	
	/**
	 * @brief	External link request timeout (NULL uses \IPS\DEFAULT_REQUEST_TIMEOUT)
	 */
	protected static $requestTimeout = NULL;
		
	/**
	 * Constructor
	 *
	 * @param	bool				$bbcode				Parse BBCode?
	 * @param	array|null			$attachIds			array of ID numbers to idenfity content for attachments if the content has been saved - the first two must be int or null, the third must be string or null. If content has not been saved yet, an MD5 hash used to claim attachments after saving.
	 * @param	\IPS\Member|null	$member				If parsing BBCode or attachments, the member posting. NULL will use currently logged in member.
	 * @param	string|bool			$area				If parsing BBCode or attachments, the Editor area we're parsing in. e.g. "core_Signatures". A boolean value will allow or disallow all BBCodes that are dependant on area.
	 * @param	bool				$filterProfanity	Remove profanity?
	 * @param	bool				$cleanHtml			If TRUE, HTML will be cleaned through HTMLPurifier
	 * @param	callback			$htmlPurifierConfig	A function which will be passed the HTMLPurifier_Config object to customise it - see example
	 * @param	int					$requestTimeout		NULL uses \IPS\DEFAULT_REQUEST_TIMEOUT. Can be set manually to avoid large timeouts on rebuild processes
	 * @return	void
	 * @code
	 	$parser = new \IPS\Text\Parser(
	 		TRUE,
	 		TRUE,
	 		TRUE,
	 		TRUE,
	 		TRUE,
	 		function( $config ) {
	 			$config->set( 'URI.Munge', TRUE );
	 		}
	 	);
	 	$value = $parser->parse( $value );
	 * @endcode
	 */
	public function __construct( $bbcode=FALSE, $attachIds=NULL, $member=NULL, $area=FALSE, $filterProfanity=TRUE, $cleanHtml=TRUE, $htmlPurifierConfig=NULL, $requestTimeout=NULL )
	{
		/* Get member */
		$this->member = $member === NULL ? \IPS\Member::loggedIn() : $member;
		
		/* Get BBCode */
		if ( $bbcode )
		{
			$this->bbcode = static::bbcodeTags( $this->member, $area );
		}
		
		/* Set timeout */
		static::$requestTimeout = $requestTimeout;
		
		/* Set area */
		$this->area = $area;
		
		/* Set attach IDs */
		$this->attachIds = $attachIds;
		
		/* Get profanity */
		if ( $filterProfanity AND !$this->member->group['g_bypass_badwords'] )
		{
			$this->looseProfanity = iterator_to_array( \IPS\Db::i()->select( '*', 'core_profanity_filters', array( 'm_exact=?', FALSE ) )->setKeyField('type')->setValueField('swop') );
			$this->exactProfanity = iterator_to_array( \IPS\Db::i()->select( '*', 'core_profanity_filters', array( 'm_exact=?', TRUE ) )->setKeyField('type')->setValueField('swop') );

			foreach( $this->exactProfanity as $key => $value )
			{
				$this->exactProfanity[ mb_strtolower( $key ) ] = $value;
			}
		}

		/* Get acronyms */
		$this->acronyms = iterator_to_array( \IPS\Db::i()->select( '*', 'core_acronyms', array() )->setKeyField('a_short') );
		
		foreach( $this->acronyms as $key => $values )
		{
			unset( $this->acronyms[$key] );
			$this->acronyms[mb_strtolower($key)] = $values;
		}
		
		/* Get any attachments which belong to this piece of content */
		if ( is_array( $this->attachIds ) )
		{
			$where = array( array( 'location_key=?', $area ) );
			$i = 1;
			foreach ( $this->attachIds as $id )
			{
				$where[] = array( "id{$i}=?", $id );
				$i++;
			}
			$this->existingAttachments = iterator_to_array( \IPS\Db::i()->select( '*', 'core_attachments_map', $where )->setKeyField( 'attachment_id' ) );
			$this->mappedAttachments = array_keys( $this->existingAttachments );
		}
		
		/* Get HTMLPurifier */
		if ( $cleanHtml )
		{
			/* Init */
			require_once \IPS\ROOT_PATH . "/system/3rd_party/HTMLPurifier/HTMLPurifier.auto.php";
			$config = \HTMLPurifier_Config::createDefault();

			/* Register a custom cache definition */
			$definitionCacheFactory	= \HTMLPurifier_DefinitionCacheFactory::instance();
			$definitionCacheFactory->register( 'IPSCache', "HtmlPurifierDefinitionCache" );
			require_once( \IPS\ROOT_PATH . '/system/Text/HtmlPurifierDefinitionCache.php' );
			
			/* Basic Configuration */
			$config->set( 'Cache.DefinitionImpl', 'IPSCache' );
			$config->set( 'HTML.Doctype', 'HTML 4.01 Transitional' );
			
			/* Allow embedded media */
			$config->set( 'HTML.SafeIframe', true );
			$config->set( 'URI.SafeIframeRegexp', '%^(' . implode( '|', array_map( function( $val )
			{
				return '(https?:)?//' . preg_quote( $val );
			}, static::allowedIFrameBases() ) ) . ')%' );
			$config->set( 'HTML.SafeObject', true );

			/* Only allow explicitly set css classes */
			$allowedCssClasses = static::getAllowedCssClasses();

			if( \IPS\Settings::i()->editor_allowed_classes )
			{
				$allowedCssClasses = array_merge( $allowedCssClasses, explode( ',', \IPS\Settings::i()->editor_allowed_classes ) );
			}

			$config->set( 'Attr.AllowedClasses', $allowedCssClasses );

			/* Add any custom stuff.  this has to be called before getHTMLDefinition() */
			if ( $htmlPurifierConfig !== NULL )
			{
				call_user_func( $htmlPurifierConfig, $config );
			}

			/* Allow quotes and spoilers */
			/* @note I would have liked to have checked editor_allowed_datacontrollers by using an ENUM for the third parameter to addAttribute, however there
				appears to be a bug with HTML Purifier where these are still stripped even if allowed - thus we'll check for them when parsing the node later */
			$def = $config->getHTMLDefinition(true);
			$def->addAttribute( 'blockquote', 'data-ipsquote', 'Text' );
			$def->addAttribute( 'blockquote', 'data-ipsquote-timestamp', 'Text' );
			$def->addAttribute( 'blockquote', 'data-ipsquote-userid', 'Text' );
			$def->addAttribute( 'blockquote', 'data-ipsquote-username', 'Text' );
			$def->addAttribute( 'blockquote', 'data-ipsquote-contentapp', 'Text' );
			$def->addAttribute( 'blockquote', 'data-ipsquote-contentclass', 'Text' );
			$def->addAttribute( 'blockquote', 'data-ipsquote-contenttype', 'Text' );
			$def->addAttribute( 'blockquote', 'data-ipsquote-contentid', 'Text' );
			$def->addAttribute( 'blockquote', 'data-ipsquote-contentcommentid', 'Text' );
			$def->addAttribute( 'blockquote', 'data-cite', 'Text' );
			$def->addAttribute( 'div', 'data-ipsspoiler', 'Text' );
			$def->addAttribute( 'div', 'data-role', 'Text' );
			$def->addAttribute( 'div', 'data-controller', 'Text' );
			$def->addAttribute( 'div', 'contenteditable', 'Text' );
			$def->addAttribute( 'img', 'data-fileid', 'Text' );
			$def->addAttribute( 'img', 'data-unique', 'Text' );
			$def->addAttribute( 'img', 'data-munge-src', 'Text' );
			$def->addAttribute( 'img', 'data-extension', 'Text' );
			$def->addAttribute( 'img', 'srcset', 'Text' );
			$def->addAttribute( 'img', 'data-emoticon', 'Text' );
			$def->addAttribute( 'a', 'data-pages-page', 'Number' );
			$def->addAttribute( 'a', 'contenteditable', 'Text' );
			$def->addAttribute( 'a', 'data-mentionid', 'Number' );
			$def->addAttribute( 'a', 'data-controller', 'Text' );
			$def->addAttribute( 'a', 'data-service', 'Text' );
			$def->addAttribute( 'a', 'data-preview', 'Text' );
			$def->addAttribute( 'a', 'data-oembed-url', 'Text' );
			$def->addAttribute( 'a', 'data-extension', 'Text' );
			$def->addAttribute( 'a', 'data-ipshover', 'Text' );
			$def->addAttribute( 'a', 'data-ipshover-target', 'Text' );
			$def->addAttribute( 'a', 'rel', 'Text' );
			$def->addAttribute( 'hr', 'data-role', 'Text' );
			$def->addAttribute( 'iframe', 'data-embedcontent', 'Text' );
			$def->addAttribute( 'iframe', 'data-controller', 'Text' );
			$def->addAttribute( 'iframe', 'data-munge-src', 'Text' );
            $def->addAttribute( 'iframe', 'allowfullscreen', 'Text' );
						
			/* Set */
			$this->htmlPurifier = new \HTMLPurifier( $config );
		}
	}
	
	/**
	 * Parse
	 *
	 * @param	string	$value	Text to parse
	 * @return	string
	 */
	public function parse( $value )
	{
		/* HTMLPurifier - remove bad stuff and make sure HTML is well formed */
		if ( $this->htmlPurifier !== NULL )
		{
			$value = $this->htmlPurifier->purify( $value );
		}

		/* BBCode, profanity, acronyms */
		if ( !empty( $this->bbcode ) or !empty( $this->looseProfanity ) or !empty( $this->exactProfanity ) or !empty( $this->acronyms ) or $this->attachIds !== NULL )
		{
			/* @link http://community.---.com/resources/bugs.html/_/4-0-0/no-js-br-added-in-the-middle-of-pre-blocks-r46414
				If this causes issues (I couldn't find any in my testing with and without js enabled) we can remove this */
			while( preg_match( "/\<pre class=['\"]ipsCode[\"']\>(.*?)(<br><br>)(.*?)\<\/pre\>/ims", $value, $matches ) )
			{
				$value = preg_replace( "/\<pre class=['\"]ipsCode[\"']\>(.*?)(<br><br>)(.*?)\<\/pre\>/ims", "<pre class=\"ipsCode\">$1\n$3</pre>", $value );
			}
			while( preg_match( "/\<pre class=['\"]ipsCode[\"']\>(.*?)(<br>)(.*?)\<\/pre\>/ims", $value, $matches ) )
			{
				$value = preg_replace( "/\<pre class=['\"]ipsCode[\"']\>(.*?)(<br>)(.*?)\<\/pre\>/ims", "<pre class=\"ipsCode\">$1$3</pre>", $value );
			}

			/* Little hacky way of adding closing [/page] tags (IP.Content) if omitted */
			if( mb_substr_count( $value, '[page]' ) !== mb_substr_count( $value, '[/page]' ) )
			{
				/* Previously you could have page 1 content before the first page tag - now we need to ensure a page tag starts the document to wrap things correctly */
				$value			= ( mb_strpos( $value, '[page]' ) === 0 ) ? $value : '[page]' . $value;
				$openPosition	= 0;

				/* Append closing tags */
				while( mb_substr_count( $value, '[page]' ) > mb_substr_count( $value, '[/page]' ) )
				{
					$openPosition	= mb_strpos( $value, '[page]', $openPosition );

					/* If there is another [page] tag, append the closing tag immediately before it, else append it to the end */
					if( mb_strpos( $value, '[page]', $openPosition + 1 ) )
					{
						$value	= mb_substr( $value, 0, mb_strpos( $value, '[page]', $openPosition + 1 ) ) . '[/page]' . mb_substr( $value, mb_strpos( $value, '[page]', $openPosition + 1 ) );
						$openPosition	+= mb_strlen( '[/page]' ) + 1;
					}
					else
					{
						$value	.= '[/page]';
					}
				}
			}

			/* Initiate a DOMDocument, force it to use UTF-8 */
			libxml_use_internal_errors(TRUE);
			$source = new \DOMDocument( '1.0', 'UTF-8' );
			@$source->loadHTML( "<head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'/></head>" . $value );

			/* And create a new */
			$document = new \DOMDocument( '1.0', 'UTF-8' );
			@$document->loadHTML( "<head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'/></head>" );

			/* Loop */
			foreach ( $source->childNodes as $node )
			{				
				if ( $node instanceof \DOMElement )
				{
					$this->parseNode( $document, $node );
				}
			}

			/* Set value */
			$value = preg_replace( '/^<!DOCTYPE.+?>/', '', str_replace( array( '<html>', '</html>', '<body>', '</body>', '<head>', '</head>' ), '', $document->saveHTML() ) );
			$value = preg_replace( '/(<iframe\s+?(?:[^>])*)\/>/i', '$1></iframe>', $value );
			$value = preg_replace( '/<meta http-equiv(?:[^>]+?)>/i', '', $value );
		}
					
		/* We need to wrap IP.Content custom 'pages' bbcode in a special div */
		if( isset( $this->bbcode['page'] ) AND ( ( mb_strpos( $value, 'data-role' ) !== FALSE AND mb_strpos( $value, 'contentPage' ) !== FALSE ) OR mb_stripos( $value, '[page]' ) !== FALSE ) AND mb_strpos( $value, 'core.front.core.articlePages' ) === FALSE )
		{
			$value = "<div data-controller='core.front.core.articlePages'>{$value}</div>";
		}

		/* Little hack for lists */
		if ( array_key_exists( 'list', $this->bbcode ) )
		{
			$value = preg_replace_callback( "/<[u|o]l data-ipsBBCode-list=\"true\"(?:.+?)?>.+?<\/[u|o]l>/is", function( $matches )
			{
				$v = str_replace( ' data-ipsBBCode-list="true"', '', $matches[0] );
				$v = str_replace( '</p><p>', '<br>', $v );
				$v = str_replace( array( '<p>', '</p>' ), '', $v );
				$v = str_replace( '[*]', '</li><li>', $v );
				$v = preg_replace( '#<ul>(\s+?)?</li>#s', '<ul>', $v );
				$v = preg_replace( '#<ol>(\s+?)?</li>#s', '<ol>', $v );
				$v = str_replace( '</ul>', '</li></ul>', $v );
				$v = str_replace( '</ol>', '</li></ol>', $v );
				
				return $v;
			}, $value );
		}

		/* HTMLPurifier again, to catch anything bad from bbcodes */
		if ( $this->htmlPurifier !== NULL )
		{
			$value = $this->htmlPurifier->purify( $value );
		}

		/* A few more replacements for dynamic file object URLs */
		$value = preg_replace( '#<([^>]+?)(srcset)=(\'|")%7BfileStore\.([\d\w\_]+?)%7D/#i', '<\1\2=\3<fileStore.\4>/', $value );
		$value = preg_replace( '#<([^>]+?)(href|src)=(\'|")%7BfileStore\.([\d\w\_]+?)%7D/#i', '<\1\2=\3<fileStore.\4>/', $value );
		$value = preg_replace( '#<([^>]+?)(href|src|srcset)=(\'|")%7B___base_url___%7D/#i', '<\1\2=\3<___base_url___>/', $value );
		$value = preg_replace( '#<([^>]+?)(data-fileid)=(\'|"){___base_url___}/#i', '<\1\2=\3<___base_url___>/', $value );

		/* iFrame has to be handled here */
		$value = preg_replace( '#<iframe(.+?)src=(\'|")' . preg_quote( rtrim( \IPS\Settings::i()->base_url, '/' ), '#' ) . '/#i', '<iframe\1src=\2<___base_url___>/', $value );

		/* Return */
		return $value;
	}
	
	/**
	 * Parse Node
	 *
	 * @param	\DOMDocument		$document	The document
	 * @param	\DOMElement			$node		The node to parse
	 * @param	\DOMElement|null	$parent		The parent node to place $node into
	 * @return	bool				If the node contains any contents
	 */
	protected function parseNode( $document, $node, $parent=NULL )
	{		
		/* If we have no parent, we'll insert straight into the document */
		$parent = $parent ?: $document;
		
		/* If this is a code box, don't try to parse inside it */
		if ( $node instanceof \DOMElement and mb_strpos( $node->getAttribute( 'class' ), 'ipsCode' ) !== false )
		{			
			$newNode = $document->importNode( $node, TRUE );
			$parent->appendChild( $newNode );
			return true;
		}
		
		/* Is this a text node? */
		if ( $node instanceof \DOMText )
		{			
			/* If we have an open block node, change the parent to that */
			if ( $this->openBlock and !$this->openBlockDepth )
			{
				$parent = $this->openBlock;
			}
				
			/* We only need to do this if we have any text */
			$text = $node->wholeText;
			
			/* Little hacky way of fixing lists that aren't spaced how we like */
			$text = str_ireplace( "[list][*]", "[list]</p><p>[*]", $text );
			$text = str_ireplace( "[list]<br>", "</p><p>[list]", $text );

			/* Little hacky way of fixing newlines inside center tags */
			$text = preg_replace( "/\[center\](.*?)<br>(.*?)\[\/center\]/i", "[center]$1</p><p>$2[/center]", $text );

			/* Little hacky way of fixing newlines inside code tags */
			while( preg_match( "/\[code\](.*?)<(br|p)>(.*?)\[\/code\]/ims", $text ) )
			{
				$text = preg_replace_callback( "/\[code\](.*?)<(?:br|p)>(.*?)\[\/code\]/ims", function( $matches ) {
					return "[code]" . str_replace( '</p>', '', "{$matches[1]}\n{$matches[2]}[/code]" );
				}, $text );
			}
			
			/* Little hacky way of supporting [tag]content[/tag] where our preference is [tag=content] */
			if ( array_key_exists( 'img', $this->bbcode ) )
			{
				$text = preg_replace( '/\[img\]([^\s]+)\[\/img\]/i', '[img=$1]', $text );
			}
			if ( array_key_exists( 'snapback', $this->bbcode ) )
			{
				$text = preg_replace( '/\[snapback\]([^\s]+)\[\/snapback\]/i', '[snapback=$1]', $text );
			}

			/* If this is raw text immediately inside a quote, we need to fix that */
			if( trim( $text ) and $parent instanceof \DOMElement and $parent->tagName === 'blockquote' and mb_strpos( $parent->getAttribute('class'), 'ipsStyle_spoiler' ) === FALSE )
			{
				$wrapper = $document->createElement( 'p', $text );
				$parent->appendChild( $wrapper );
				return true;
			}

			if ( !is_null( $text ) )
			{
				/* Note that we've not added any text yet */
				$addedText = FALSE;
				$textToGo = $text;
				
				/* Construct break points */
				$breakPoints = array();
				if ( count( $this->bbcode ) )
				{
					$breakPoints[] = '(\[\/?(?:' . implode( '|', array_map( 'preg_quote', array_keys( $this->bbcode ) ) ) . '|\*)(?:=.+?)?\])';
				}
				if ( count( $this->acronyms ) or count( $this->exactProfanity ) )
				{
					$array = array();

					foreach( array_merge( array_keys( $this->acronyms ), array_keys( $this->exactProfanity ) ) as $entry )
					{
						$array[]	= preg_quote( $entry, '/' );
					}

					/* @note: We use \b because of http://community.---.com/resources/bugs.html/_/4-0-0/anacronym-doesnt-work-in-middle-of-a-phrase-r47612 */
					$breakPoints[] = '((?=<^|\b)(?:' . implode( '|', $array ) . ')(?=\b|$))';
				}

				/* Split on BBCode tags, profanity and acronyms */
				if ( count( $breakPoints ) )
				{
					foreach( preg_split( '/' . implode( '|', $breakPoints ) . '/iu', $text, null, PREG_SPLIT_DELIM_CAPTURE ) as $section )
					{
						/* Deduct it from $textToGo */
						$textToGo = mb_substr( $textToGo, mb_strlen( $section ) );

						/* Replace exact profanity */
						if ( isset( $this->exactProfanity[ mb_strtolower( $section ) ] )  )
						{
							$profanityNode = $document->createTextNode( $this->exactProfanity[  mb_strtolower( $section ) ] );
							$parent->appendChild( $profanityNode );
							$addedText = TRUE;
						}
						/* Replace acronym */
						elseif ( isset( $this->acronyms[ mb_strtolower($section) ] )  )
						{
							if ( ( !$this->acronyms[ mb_strtolower( $section ) ]['a_casesensitive'] or $this->acronyms[ mb_strtolower( $section ) ]['a_short'] == $section ) and $parent->tagName !== 'abbr' )
							{
								$acronymNode = $document->createElement( 'abbr', htmlspecialchars( $section, \IPS\HTMLENTITIES, 'UTF-8', FALSE ) );
								$acronymNode->setAttribute( 'title', $this->acronyms[ mb_strtolower($section) ]['a_long'] );
							}
							else
							{
								$acronymNode = $document->createTextNode( $section );
							}
							
							$parent->appendChild( $acronymNode );
							$addedText = TRUE;
						}
						/* If this is a start BBCode tag, do stuff */
						elseif ( $this->parseOn === TRUE and preg_match( '/^\[([a-z]+?)(?:=(.+?))?\]$/i', $section, $matches ) and array_key_exists( mb_strtolower( $matches[1] ), $this->bbcode ) )
						{
							$_tag	= mb_strtolower( $matches[1] );

							/* Strip it? */
							if ( $this->bbcode[ $_tag ] === NULL )
							{
								continue;
							}
								
							/* Create the node */
							$bbcodeNode = $document->createElement( $this->bbcode[ $_tag ]['tag'] );
							
							/* Add attributes */
							if ( isset( $this->bbcode[ $_tag ]['attributes'] ) )
							{
								foreach ( $this->bbcode[ $_tag ]['attributes'] as $k => $v )
								{
									if ( isset( $matches[2] ) )
									{
										$v = str_replace( '{option}', $matches[2], $v );
									}
									
									$bbcodeNode->setAttribute( $k, $v );
								}
							}
														
							/* Callback */
							if ( isset( $this->bbcode[ $_tag ]['callback'] ) )
							{
								$bbcodeNode = call_user_func( $this->bbcode[ $_tag ]['callback'], $bbcodeNode, $matches, $document );
							}
							
							/* No parse? */
							if ( isset( $this->bbcode[ $_tag ]['noParse'] ) and $this->bbcode[ $_tag ]['noParse'] )
							{
								$this->parseOn = "[/{$_tag}]";
							}
							
							/* Block */
							if ( isset( $this->bbcode[ $_tag ]['block'] ) and $this->bbcode[ $_tag ]['block'] )
							{
								/* Insert it */
								$this->openBlock = $bbcodeNode;
								$this->openBlocks[] = $this->openBlock;

								try
								{
									if( $parent->parentNode )
									{
										$parent->parentNode->appendChild( $this->openBlock );
									}
									else
									{
										$parent->appendChild( $this->openBlock );
									}
								}
								catch( \ErrorException $e )
								{
									/* 'DOMNode::appendChild(): Couldn't fetch DOMElement' */
									if( mb_strpos( $e->getMessage(), "fetch DOMElement" ) )
									{
										throw new \InvalidArgumentException( $e->getMessage(), 103014 );
									}
									
									throw $e;
								}

								/* If we have text to go, create a placeholder for it */
								if ( $textToGo )
								{
									if( !isset( $this->bbcode[ $_tag ]['noChildren'] ) OR !$this->bbcode[ $_tag ]['noChildren'] )
									{
										$parent = $document->createElement( $parent->tagName );

										/* Sometimes bad data in earlier versions can cause parsing issues */
										try
										{
											$this->openBlock->appendChild( $parent );
										}
										catch( \ErrorException $e )
										{
											/* 'DOMNode::appendChild(): Couldn't fetch DOMElement' */
											if( mb_strpos( $e->getMessage(), "fetch DOMElement" ) )
											{
												throw new \InvalidArgumentException( $e->getMessage(), 103014 );
											}
											
											throw $e;
										}
									}
									else
									{
										$parent = $this->openBlock;
									}
								}
							}
							/* Single */
							elseif ( isset( $this->bbcode[ $_tag ]['single'] ) and $this->bbcode[ $_tag ]['single'] === TRUE )
							{								
								$nodeToAppend = $bbcodeNode;
								foreach ( $this->inlineNodes as $element )
								{
									$_node = $element->cloneNode( TRUE );
									$_node->appendChild( $nodeToAppend );
									$nodeToAppend = $_node;
								}	
														
								try
								{
									$parent->appendChild( $nodeToAppend );
								}
								catch( \ErrorException $e )
								{
									/* 'DOMNode::appendChild(): Couldn't fetch DOMElement' */
									if( mb_strpos( $e->getMessage(), "fetch DOMElement" ) )
									{
										throw new \InvalidArgumentException( $e->getMessage(), 103014 );
									}
									
									throw $e;
								}
								
								$addedText = TRUE;
							}
							/* Inline */
							else
							{
								if( $bbcodeNode !== null )
								{
									$this->inlineNodes[] = $bbcodeNode;
								}
							}
						}
						/* If this is a close BBCode tag, do stuff */
						elseif ( ( $this->parseOn === TRUE and in_array( mb_strtolower( $section ), array_map( function( $v ) { return "[/{$v}]"; }, array_keys( $this->bbcode ) ) ) ) or $this->parseOn === mb_strtolower( $section ) )
						{
							$_tag	= mb_strtolower( $section );
							$_bTag	= mb_substr( $_tag, 2, -1 );
							
							/* Strip it? */
							if ( $this->bbcode[ $_bTag ] === NULL )
							{
								continue;
							}
							
							/* Turn parsing back on */
							$this->parseOn = TRUE;

							/* Block */
							if ( isset( $this->bbcode[ $_bTag ]['block'] ) and $this->bbcode[ $_bTag ]['block'] )
							{
								/* If we have text to go, insert a placeholder for it */
								if ( $textToGo )
								{
									$parent = $document->createElement( $parent->tagName );

									if( $this->openBlock === NULL )
									{
										$this->openBlock = $document->createElement( 'div' );
										$document->appendChild( $this->openBlock );
									}

									if ( $this->openBlock->nextSibling )
									{
										$this->openBlock->parentNode->insertBefore( $parent, $this->openBlock->nextSibling );
									}
									elseif( $this->openBlock->parentNode )
									{
										try
										{
											$this->openBlock->parentNode->appendChild( $parent );
										}
										catch( \ErrorException $e )
										{
											/* 'DOMNode::appendChild(): Couldn't fetch DOMElement' */
											if( mb_strpos( $e->getMessage(), "fetch DOMElement" ) )
											{
												throw new \InvalidArgumentException( $e->getMessage(), 103014 );
											}
											
											throw $e;
										}
									}
								}
								
								/* Remove it */
								foreach ( array_reverse( $this->openBlocks, TRUE ) as $k => $element )
								{
									/* Severely badly formatted bbcode can throw Couldn't fetch DOMElement. Node no longer exists  */
									try
									{
										if ( $element->tagName == $this->bbcode[ $_bTag ]['tag'] )
										{
											unset( $this->openBlocks[ $k ] );
										}
									}
									catch( \ErrorException $e )
									{
										unset( $this->openBlocks[ $k ] );
									}
								}
														
								/* Work out the new open block */
								$this->openBlock = NULL;
								if ( !empty( $this->openBlocks ) )
								{
									foreach ( $this->openBlocks as $element )
									{
										if ( !$this->openBlock )
										{
											$this->openBlock = $element;
										}
										else
										{
											try
											{
												$this->openBlock->appendChild( $element );
												$this->openBlock = $element;
											}
											catch( \ErrorException $e )
											{
												/* 'DOMNode::appendChild(): Couldn't fetch DOMElement' */
												if( mb_strpos( $e->getMessage(), "fetch DOMElement" ) )
												{
													throw new \InvalidArgumentException( $e->getMessage(), 103014 );
												}
												
												throw $e;
											}
										}
									}
								}
							}
							/* Inline */
							else
							{
								foreach ( array_reverse( $this->inlineNodes, TRUE ) as $k => $element )
								{
									if ( $element->tagName == $this->bbcode[ $_bTag ]['tag'] )
									{
										unset( $this->inlineNodes[ $k ] );
									}
								}
							}
						}
						/* If this is normal text, add it to the new node */
						elseif ( !is_null( $section ) and $section !== '' )
						{
							/* Replace loose profanity */
							$section = str_ireplace( array_keys( $this->looseProfanity ), array_values( $this->looseProfanity ), $section );

							/* First create a text node */
							$node = $document->createTextNode( $section );
														
							try
							{
								/* Then put it in whatever elements are necessary */
								foreach ( $this->inlineNodes as $element )
								{
									$_node = $element->cloneNode( TRUE );
									$_node->appendChild( $node );
									$node = $_node;
								}

								/* And insert it */
								$parent->appendChild( $node );
							}
							catch( \ErrorException $e )
							{
								/* 'DOMNode::appendChild(): Couldn't fetch DOMElement' */
								if( mb_strpos( $e->getMessage(), "fetch DOMElement" ) )
								{
									throw new \InvalidArgumentException( $e->getMessage(), 103014 );
								}
								
								throw $e;
							}
							
							/* Note we did so */
							$addedText = TRUE;
						}
					}
					
					/* Return */
					return $addedText;
				}
				else
				{
					/* First create a text node */
					$node = $document->createTextNode( $text );
					
					try
					{
						$newNode = $document->importNode( $node );
						$parent->appendChild( $newNode );
					}
					catch( \ErrorException $e )
					{
						/* 'DOMNode::appendChild(): Couldn't fetch DOMElement' */
						if( mb_strpos( $e->getMessage(), "fetch DOMElement" ) )
						{
							throw new \InvalidArgumentException( $e->getMessage(), 103014 );
						}
						
						throw $e;
					}
					return TRUE;
				}
			}
			else
			{
				/* First create a text node */
				$node = $document->createTextNode( $text );
				
				try
				{
					$newNode = $document->importNode( $node );
					$parent->appendChild( $newNode );
				}
				catch( \ErrorException $e )
				{
					/* 'DOMNode::appendChild(): Couldn't fetch DOMElement' */
					if( mb_strpos( $e->getMessage(), "fetch DOMElement" ) )
					{
						throw new \InvalidArgumentException( $e->getMessage(), 103014 );
					}
					
					throw $e;
				}
				return TRUE;
			}
			
			return FALSE;
		}
		/* Nope, normal node - just import and loop over it's children */
		else
		{
			$added = FALSE;

			/* DOMComment for instance does not even have a tagName property, so just return if that's the case */
			if( !isset( $node->tagName ) )
			{
				return $added;
			}
						
			/* If this is the head of the document, just skip as we added that to force UTF-8 mode */
			if( $node->tagName == 'head' )
			{
				return $added;
			}

			/* Remove Negative Margins or nowraps */
			if( !( $node instanceof \DOMComment ) and $node->nodeType == XML_ELEMENT_NODE and $node->hasAttribute('style') )
			{
				$style		= $node->getAttribute('style');
				$_styles	= explode( ';', rtrim( $style, ';' ) );
				$_saved		= array();

				foreach( $_styles as $_style )
				{
					$_inlineStyle	= explode( ':', $_style );

					if( mb_strpos( trim( $_inlineStyle[0] ), 'margin' ) === 0 )
					{
						if( mb_substr( trim( $_inlineStyle[1] ), 0, 1 ) === '-' )
						{
							continue;
						}
					}
					
					if( mb_strpos( trim( $_inlineStyle[0] ), 'white-space' ) === 0 and mb_strpos( trim( $_inlineStyle[1] ), 'nowrap' ) === 0 )
					{
						continue;
					}

					$_saved[]	= $_style;
				}

				if( count( $_saved ) != count( $_styles ) )
				{
					$node->setAttribute( 'style', implode( ';', $_saved ) );
					$added = TRUE;
				}
			}

			/* Is it a link to an attachment? */
			if ( $node->tagName === 'img' and preg_match( '#^(?:http:|https:)?' . preg_quote( rtrim( str_replace( array( 'http://', 'https://' ), '//', \IPS\Settings::i()->base_url ), '/' ), '#' ) . '/applications/core/interface/file/attachment\.php\?id=(\d+)$#', $node->getAttribute('data-fileid'), $matches ) )
			{
				$node->setAttribute( 'data-fileid', $matches[1] );
			}
			
			if ( ( $node->tagName === 'a' and preg_match( '#^(?:http:|https:)?' . preg_quote( rtrim( str_replace( array( 'http://', 'https://' ), '//', \IPS\Settings::i()->base_url ), '/' ), '#' ) . '/applications/core/interface/file/attachment\.php\?id=(\d+)$#', $node->getAttribute('href'), $matches ) ) or ( $node->tagName === 'img' and $matches[1] = $node->getAttribute('data-fileid') ) )
			{			
				if ( isset( $matches[1] ) )
				{
					try
					{
						$attachment = \IPS\Db::i()->select( '*', 'core_attachments', array( 'attach_id=?', $matches[1] ) )->first();
						
						if ( isset( $this->existingAttachments[ $attachment['attach_id'] ] ) )
						{
							unset( $this->existingAttachments[ $attachment['attach_id'] ] );
						}
						elseif ( $attachment['attach_member_id'] === $this->member->member_id and !in_array( $attachment['attach_id'], $this->mappedAttachments ) )
						{
							\IPS\Db::i()->replace( 'core_attachments_map', array(
								'attachment_id'	=> $attachment['attach_id'],
								'location_key'	=> $this->area,
								'id1'			=> ( is_array( $this->attachIds ) and isset( $this->attachIds[0] ) ) ? $this->attachIds[0] : NULL,
								'id2'			=> ( is_array( $this->attachIds ) and isset( $this->attachIds[1] ) ) ? $this->attachIds[1] : NULL,
								'id3'			=> ( is_array( $this->attachIds ) and isset( $this->attachIds[2] ) ) ? $this->attachIds[2] : NULL,
								'temp'			=> is_string( $this->attachIds ) ? $this->attachIds : NULL
							) );
							
							$this->mappedAttachments[] = $attachment['attach_id'];
						}
						
						$added = TRUE;
					}
					catch ( \UnderflowException $e ) { }
				}
			}
			
			/* Is it a quote? */
			if ( $node->tagName === 'blockquote' && mb_strpos( $node->getAttribute('class'), 'ipsStyle_spoiler' ) === FALSE )
			{				
				/* Do we need to change the relative time? */
				if ( $node->getAttribute('data-cite') and $node->getAttribute('data-ipsquote-username') )
				{
					$node->setAttribute( 'data-cite', $node->getAttribute('data-ipsquote-username') ); // That's only actually ever shown if JS is disabled.
				}
			}

			/* Check URL blacklist/whitelist */
			if( $node->tagName === 'a' )
			{
				if( !$this->isAllowedUrl( $node->getAttribute('href') ) )
				{
					$node = $document->createTextNode( $node->getAttribute('href') );
					$node->tagName	= NULL;
				}
				else
				{
					/* Skipping VigLink? */
					if ( \IPS\Settings::i()->viglink_norewrite and \IPS\Member::loggedIn()->inGroup( explode( ',', \IPS\Settings::i()->viglink_norewrite ) ) )
					{
						$rels[] = 'norewrite';
					}
					
					/* Internal or External */
					try
					{
						$link = new \IPS\Http\Url( $node->getAttribute('href') );

						if ( !$link->isInternal )
						{
							$rels[] = 'external';
							
							/* Do we also want to add nofollow? */
							if( \IPS\Settings::i()->posts_add_nofollow and ( !\IPS\Settings::i()->posts_add_nofollow_exclude or ( isset( $link->data['host'] ) and !in_array( preg_replace( '/^www\./', '', $link->data['host'] ), json_decode( \IPS\Settings::i()->posts_add_nofollow_exclude ) ) ) ) )
							{
								$rels[] = 'nofollow';
							}
						}
						
						if ( !empty( $rels ) )
						{
							$node->setAttribute( 'rel', implode( ' ', $rels) );
						}
					}
					catch( \ErrorException $e )
					{
						$node = $document->createTextNode( $node->getAttribute('href') );
						$node->tagName	= NULL;
					}
				}
			}

			/* Act */
			if ( !( $node instanceof \DOMComment ) and $node->nodeType == XML_ELEMENT_NODE and ( $node->tagName === 'img' OR $node->tagName === 'iframe' ) AND $node->hasAttribute( 'data-munge-src' ) )
			{
				$node->removeAttribute( 'src' );
				$node->setAttribute( 'src', $node->getAttribute('data-munge-src') );
				$node->removeAttribute( 'data-munge-src' );

				$added = TRUE;
			}

			/* Fix missing alts or html purifier might try to do a non-UTF8 friendly approach */
			if ( !( $node instanceof \DOMComment ) and $node->nodeType == XML_ELEMENT_NODE and $node->tagName === 'img' AND ( !$node->hasAttribute('alt') OR !$node->getAttribute('alt') ) )
			{
				$node->setAttribute( 'alt', mb_substr( basename( $node->getAttribute( 'src' ) ), 0, 40 ) );

				$added = TRUE;
			}

			/* We can't allow data-role="commentContent" or this causes a lot of things to be broken when attempting to edit a post more than once (it duplicates the form fields multiple times with a broken editor and nothing will save) */
			if ( !( $node instanceof \DOMComment ) and $node->nodeType == XML_ELEMENT_NODE and $node->hasAttribute( 'data-role' ) )
			{
				$roles		= explode( ',', $node->getAttribute('data-role') );
				$saveRoles	= array();

				foreach( $roles as $role )
				{
					if( $role != 'commentContent' )
					{
						$saveRoles[] = $role;
					}
				}

				if( !count( $saveRoles ) )
				{
					$node->removeAttribute( 'data-role' );
				}
				else
				{
					$node->setAttribute( 'data-role', implode( ',', $saveRoles ) );
				}

				$added = TRUE;
			}

			/* Figure out what data-controllers we will allow */
			$allowedDataControllers = array( 'core.front.core.articlePages', 'core.front.core.autoSizeIframe', 'core.front.core.lightboxedImages' );

			if( \IPS\Settings::i()->editor_allowed_datacontrollers )
			{
				$allowedDataControllers = array_merge( $allowedDataControllers, explode( ',', \IPS\Settings::i()->editor_allowed_datacontrollers ) );
			}

			if ( !( $node instanceof \DOMComment ) and $node->nodeType == XML_ELEMENT_NODE and $node->hasAttribute( 'data-controller' ) )
			{
				$controllers		= explode( ',', $node->getAttribute('data-controller') );
				$saveControllers	= array();

				foreach( $controllers as $controller )
				{
					if( in_array( $controller, $allowedDataControllers ) )
					{
						$saveControllers[] = $controller;
					}
				}

				if( !count( $saveControllers ) )
				{
					$node->removeAttribute( 'data-controller' );
				}
				else
				{
					$node->setAttribute( 'data-controller', implode( ',', $saveControllers ) );
				}

				$added = TRUE;
			}

			if( $node->tagName === 'img' )
			{
				$imageSrc = $node->getAttribute('src');
				if ( !$this->isAllowedUrl( $imageSrc ) )
				{					
					return;
				}
				if ( \IPS\Settings::i()->remote_image_proxy )
				{
					$imageSrc = new \IPS\Http\Url( $imageSrc );
					if ( !$imageSrc->isInternal )
					{
						$newUrl = new \IPS\Http\Url( \IPS\Settings::i()->base_url . "applications/core/interface/imageproxy/imageproxy.php" );
						$newUrl = $newUrl->setQueryString( array(
							'img' 	=> (string) $imageSrc,
							'key'	=> hash_hmac( "sha256", (string) $imageSrc, \IPS\SITE_SECRET_KEY ?: md5( \IPS\Settings::i()->sql_pass . \IPS\Settings::i()->board_url . \IPS\Settings::i()->sql_database ) )
						) );

						$node->setAttribute( 'src', (string) $newUrl );
					}
				}
			}

			/* If we have an open block node, change the parent to that */
			$blockOpened = FALSE;
			if ( $this->openBlock and !$this->openBlockDepth )
			{
				$parent = $this->openBlock;
				$blockOpened = TRUE;
				$this->openBlockDepth++;
			}
			
			if ( ! isset( static::$fileObjectClasses['core_Attachment'] ) )
			{
				static::$fileObjectClasses['core_Attachment'] = \IPS\File::getClass('core_Attachment' );
			}
			
			if ( ! isset( static::$fileObjectClasses['core_Emoticons'] ) )
			{
				static::$fileObjectClasses['core_Emoticons'] = \IPS\File::getClass('core_Emoticons' );
			}
			
			if ( static::$emoticons === NULL )
			{
				static::$emoticons = static::getEmoticons();
			}

			$nodeChecked = FALSE;
			if ( ( $node->tagName === 'a' or $node->tagName === 'img' ) and $node->getAttribute('data-extension') )
			{
				$extension = $node->getAttribute('data-extension');
				
				if ( ! isset( static::$fileObjectClasses[ $extension ] ) )
				{
					try
					{
						static::$fileObjectClasses[ $extension ] = \IPS\File::getClass( $extension );
					}
					catch ( \Exception $e ) { }
				}
				$url = ( $node->tagName === 'a' ) ? $node->getAttribute('href') : $node->getAttribute('src');

				if ( isset( static::$fileObjectClasses[ $extension ] ) and preg_match( '#^(' . preg_quote( rtrim( static::$fileObjectClasses[ $extension ]->baseUrl(), '/' ), '#' ) . ')/(.+?)$#', $url, $matches ) )
				{
					$nodeChecked = TRUE;
					$added = TRUE;
					$node->setAttribute( ( $node->tagName === 'a' ? 'href' : 'src' ), '{fileStore.' . $extension . '}/' . $matches[2] );
				}
				else
				{
					$nodeChecked = FALSE;
				}
			}

			/* A fall back for earlier posts */
			if ( ! $nodeChecked and ( ( $node->tagName === 'a' and preg_match( '#^(' . preg_quote( rtrim( static::$fileObjectClasses['core_Attachment']->baseUrl(), '/' ), '#' ) . ')/(.+?)$#', $node->getAttribute('href'), $matches ) ) ) )
			{
				$aHref        = $matches[2];
				$isAttachLink = FALSE;
				if ( $node->hasChildNodes() )
				{
					foreach ( $node->childNodes as $child )
					{
						if ( !( $child instanceof \DOMComment ) and $child->nodeType == XML_ELEMENT_NODE AND ( $child->tagName === 'img' and preg_match( '#^(' . preg_quote( rtrim( static::$fileObjectClasses['core_Attachment']->baseUrl(), '/' ), '#' ) . ')/(.+?)$#', $child->getAttribute('src'), $matches ) ) and $child->getAttribute('data-fileid') )
						{
							$isAttachLink = TRUE;
						}
					}
				}
				
				if ( $isAttachLink )
				{
					$added = TRUE;
					$node->setAttribute( 'href', '{fileStore.core_Attachment}/' . $aHref );
				}
			}

			if ( ! $nodeChecked and ( ( $node->tagName === 'img' and preg_match( '#^(' . preg_quote( rtrim( static::$fileObjectClasses['core_Attachment']->baseUrl(), '/' ), '#' ) . ')/(.+?)$#', $node->getAttribute('src'), $matches ) ) and $node->getAttribute('data-fileid') ) )
			{
				$added = TRUE;
				$node->setAttribute( 'src', '{fileStore.core_Attachment}/' . $matches[2] );
				$nodeChecked = TRUE;
			}

			/* Emoticons */
			if ( ! $nodeChecked and ( ( $node->tagName === 'img' and preg_match( '#^(' . preg_quote( rtrim( static::$fileObjectClasses['core_Emoticons']->baseUrl(), '/' ), '#' ) . ')/(.+?)$#', $node->getAttribute('src'), $matches ) ) ) )
			{
				foreach( static::$emoticons as $emo )
				{
					if ( $emo['image'] == $matches[2] )
					{
						$added = TRUE;
						$node->setAttribute( 'src', '{fileStore.core_Emoticons}/' . $matches[2] );

						if( $emo['image_2x'] && $emo['width'] && $emo['height'] )
						{
							$node->setAttribute( 'srcset', '%7BfileStore.core_Emoticons%7D/' . $emo['image_2x'] . ' 2x' );
							/* Retina emoticons require a width/height for proper scaling */
							$node->setAttribute( 'width', $emo['width'] );
							$node->setAttribute( 'height', $emo['height'] );
						}
						$nodeChecked = TRUE;
					}
				}
			}

			/* Anything else that has a URL */
			if ( ! $nodeChecked and ( $node->tagName === 'a' or $node->tagName === 'img' ) )
			{
				$url = ( $node->tagName === 'a' ) ? $node->getAttribute('href') : $node->getAttribute('src');

				if ( preg_match( '#^(' . preg_quote( rtrim( \IPS\Settings::i()->base_url, '/' ), '#' ) . ')/(.+?)$#', $url, $matches ) )
				{
					$added = TRUE;
					$node->setAttribute( ( $node->tagName === 'a' ? 'href' : 'src' ), '{___base_url___}/' . $matches[2] );
				}
				
				if ( $node->getAttribute('data-fileid') )
				{
					if ( preg_match( '#^(' . preg_quote( rtrim( \IPS\Settings::i()->base_url, '/' ), '#' ) . ')/(.+?)$#', $node->getAttribute('data-fileid'), $matches ) )
					{
						$added = TRUE;
						$node->setAttribute( 'data-fileid', '{___base_url___}/' . $matches[2] );
					}
				}
			}

			/* Import */
			/* Sometimes bad data in earlier versions can cause parsing issues */
			try
			{
				$newNode = $document->importNode( $node );
				$parent->appendChild( $newNode );
			}
			catch( \ErrorException $e )
			{
				/* 'DOMNode::appendChild(): Couldn't fetch DOMElement' */
				if( mb_strpos( $e->getMessage(), "fetch DOMElement" ) )
				{
					throw new \InvalidArgumentException( $e->getMessage(), 103014 );
				}
				
				throw $e;
			}

			/* Loop children */
			if ( $node->hasChildNodes() )
			{
				foreach ( $node->childNodes as $child )
				{
					if ( $this->parseNode( $document, $child, $newNode ) )
					{
						$added = TRUE;
					}
				}
			}
			
			/* Decrease block depth */
			if ( $blockOpened )
			{
				$this->openBlockDepth--;
			}

			/* Return */
			return $added;
		}
	}
	
	/**
	 * Is allowed URL
	 *
	 * @param	string	$url	The URL
	 * @return	bool
	 */
	public function isAllowedUrl( $url )
	{
		if ( \IPS\Settings::i()->ipb_url_filter_option != 'none' )
		{
			$links = \IPS\Settings::i()->ipb_url_filter_option == "black" ? \IPS\Settings::i()->ipb_url_blacklist : \IPS\Settings::i()->ipb_url_whitelist;
	
			if( $links )
			{
				$linkValues = array();
				$linkValues = explode( "," , $links );
	
				if( \IPS\Settings::i()->ipb_url_filter_option == 'white' )
				{
					$linkValues[]	= "http://" . parse_url( \IPS\Settings::i()->base_url, PHP_URL_HOST ) . "*";
					$linkValues[]	= "https://" . parse_url( \IPS\Settings::i()->base_url, PHP_URL_HOST ) . "*";
				}
	
				if ( !empty( $linkValues ) )
				{
					$goodUrl = FALSE;
	
					foreach( $linkValues as $link )
					{
						if( !trim($link) )
						{
							continue;
						}
	
						$link = preg_quote( $link, '/' );
						$link = str_replace( '\*', "(.*?)", $link );
	
						if ( \IPS\Settings::i()->ipb_url_filter_option == "black" )
						{
							if( preg_match( '/' . $link . '/i', $url ) )
							{
								return false;
							}
						}
						else
						{
							if ( preg_match( '/' . $link . '/i', $url ) )
							{
								$goodUrl = TRUE;
							}
						}
					}
	
					if ( ! $goodUrl AND \IPS\Settings::i()->ipb_url_filter_option == "white" )
					{
						return false;
					}
				}
			}
		}
	
		return true;
	}

	/**
	 * Remove specific elements, useful for cleaning up content for display or truncating
	 *
	 * @param	string				$value			The value to parse
	 * @param	array|string		$elements		Element to remove, or array of elements to remove
	 * @return	string
	 */
	public static function removeElements( $value, $elements=array( 'blockquote', 'img', 'a' ) )
	{
		/* Initiate a DOMDocument, force it to use UTF-8 */
		libxml_use_internal_errors(TRUE);
		$source = new \DOMDocument( '1.0', 'UTF-8' );
		@$source->loadHTML( "<head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'/></head>" . $value );

		/* And create a new */
		$document = new \DOMDocument( '1.0', 'UTF-8' );
		$document->loadHTML( "<head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'/></head>" );

		$elements	= is_string( $elements ) ? array( $elements ) : $elements;

		/* Loop */
		foreach ( $source->childNodes as $node )
		{				
			if ( $node instanceof \DOMElement )
			{
				static::removeElementsNode( $document, $node, $elements );
			}
		}
		
		/* Set value */
		$value = preg_replace( '/^<!DOCTYPE.+?>/', '', str_replace( array( '<html>', '</html>', '<body>', '</body>', '<head>', '</head>' ), '', $document->saveHTML() ) );
		$value = preg_replace( '/(<iframe\s+?(?:[^>])*)\/>/i', '$1></iframe>', $value );
		$value = preg_replace( '/<meta http-equiv(?:[^>]+?)>/i', '', $value );

		return $value;
	}

	/**
	 * Remove specified elements from the node
	 *
	 * @param	\DOMDocument		$document	The document
	 * @param	\DOMElement			$node		The node to parse
	 * @param	array				$elements	Array of elements to remove
	 * @param	\DOMElement|null	$parent		The parent node to place $node into
	 * @return	bool				If the node contains any contents
	 */
	protected static function removeElementsNode( $document, $node, $elements, $parent=NULL )
	{		
		/* If we have no parent, we'll insert straight into the document */
		$parent = $parent ?: $document;
		
		/* Is this a text node? */
		if ( $node instanceof \DOMText )
		{
			/* Import */
			$newNode = $document->importNode( $node );
			$parent->appendChild( $newNode );

			return $node->wholeText;
		}
		/* Nope, normal node - just import and loop over it's children */
		else
		{
			$added = FALSE;

			/* DOMComment for instance does not even have a tagName property, so just return if that's the case */
			if( !isset( $node->tagName ) )
			{
				return $added;
			}

			/* If this is the head of the document, just skip as we added that to force UTF-8 mode */
			if( $node->tagName == 'head' )
			{
				return $added;
			}

			/* If this a forbidden element, skip it */
			if( in_array( $node->tagName, $elements ) )
			{
				return $added;
			}

			/* Import */
			$newNode = $document->importNode( $node );
			$parent->appendChild( $newNode );

			/* Loop children */
			if ( $node->hasChildNodes() )
			{
				foreach ( $node->childNodes as $child )
				{
					if ( static::removeElementsNode( $document, $child, $elements, $newNode ) )
					{
						$added = TRUE;
					}
				}
			}
			
			/* Return */
			return $added;
		}
	}

	/**
	 * Munge resources in ACP
	 *
	 * @param	string	$value	The value to parse
	 * @return	string
	 */
	public static function mungeResources( $value )
	{
		/* Initiate a DOMDocument, force it to use UTF-8 */
		libxml_use_internal_errors(TRUE);
		$source = new \DOMDocument( '1.0', 'UTF-8' );
		@$source->loadHTML( "<head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'/></head>" . $value );

		/* And create a new */
		$document = new \DOMDocument( '1.0', 'UTF-8' );
		$document->loadHTML( "<head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'/></head>" );

		/* Loop */
		foreach ( $source->childNodes as $node )
		{				
			if ( $node instanceof \DOMElement )
			{
				static::mungeResourcesNode( $document, $node );
			}
		}

		/* Set value */
		$value = preg_replace( '/^<!DOCTYPE.+?>/', '', str_replace( array( '<html>', '</html>', '<body>', '</body>', '<head>', '</head>' ), '', $document->saveHTML() ) );
		$value = preg_replace( '/(<iframe\s+?(?:[^>])*)\/>/i', '$1></iframe>', $value );
		$value = preg_replace( '/<meta http-equiv(?:[^>]+?)>/i', '', $value );

		return $value;
	}

	/**
	 * Remove specified elements from the node
	 *
	 * @param	\DOMDocument		$document	The document
	 * @param	\DOMElement			$node		The node to parse
	 * @param	\DOMElement|null	$parent		The parent node to place $node into
	 * @return	bool				If the node contains any contents
	 */
	protected static function mungeResourcesNode( $document, $node, $parent=NULL )
	{
		/* If we have no parent, we'll insert straight into the document */
		$parent = $parent ?: $document;
		
		/* Is this a text node? */
		if ( $node instanceof \DOMText )
		{
			/* Import */
			$newNode = $document->importNode( $node );
			$parent->appendChild( $newNode );

			return $node->wholeText;
		}
		/* Nope, normal node - just import and loop over it's children */
		else
		{
			$added = FALSE;

			/* DOMComment for instance does not even have a tagName property, so just return if that's the case */
			if( !isset( $node->tagName ) )
			{
				return $added;
			}

			/* If this is the head of the document, just skip as we added that to force UTF-8 mode */
			if( $node->tagName == 'head' )
			{
				return $added;
			}

			/* Act */
			if ( $node->tagName === 'img' OR $node->tagName === 'iframe' )
			{
				$localDomain	= parse_url( \IPS\Settings::i()->base_url, PHP_URL_HOST );
				$currentSrc		= $node->getAttribute('src');
				$srcDomain		= parse_url( $currentSrc, PHP_URL_HOST );

				if( $localDomain != $srcDomain )
				{
					$node->removeAttribute( 'src' );

					$key = hash_hmac( "sha256", $currentSrc, \IPS\SITE_SECRET_KEY ?: md5( \IPS\Settings::i()->sql_pass . \IPS\Settings::i()->board_url . \IPS\Settings::i()->sql_database ) );
					$node->setAttribute( 'src', (string) \IPS\Http\Url::internal( 'app=core&module=system&controller=redirect&url=' . urlencode( $currentSrc ) . '&key=' . $key . '&resource=1', 'front' ) );
					$node->setAttribute( 'data-munge-src', $currentSrc );

					$added = TRUE;
				}
			}

			/* Import */
			$newNode = $document->importNode( $node );
			$parent->appendChild( $newNode );

			/* Loop children */
			if ( $node->hasChildNodes() )
			{
				foreach ( $node->childNodes as $child )
				{
					if ( static::mungeResourcesNode( $document, $child, $newNode ) )
					{
						$added = TRUE;
					}
				}
			}

			/* Return */
			return $added;
		}
	}

	/**
	 * Rebuild attachment urls
	 *
	 * @param	string		$textContent	Content
	 * @return	mixed	False, or rebuilt content
	 */
	public static function rebuildAttachmentUrls( $textContent )
	{
		$rebuilt	= FALSE;
		
		$textContent = preg_replace( '#<([^>]+?)(href|src)=(\'|")<fileStore\.([\d\w\_]+?)>/#i', '<\1\2=\3%7BfileStore.\4%7D/', $textContent );
		$textContent = preg_replace( '#<([^>]+?)(href|src)=(\'|")<___base_url___>/#i', '<\1\2=\3%7B___base_url___%7D/', $textContent );
		$textContent = preg_replace( '#<([^>]+?)(data-fileid)=(\'|")<___base_url___>/#i', '<\1\2=\3%7B___base_url___%7D/', $textContent );
		
		/* Create DOMDocument */
		libxml_use_internal_errors(TRUE);
		$content = new \DOMDocument( '1.0', 'UTF-8' );
		@$content->loadHTML( "<head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'/></head>" . $textContent );
		
		$xpath = new \DOMXpath( $content );
		
		foreach ( $xpath->query('//img') as $image )
		{
			if( $image->getAttribute( 'data-fileid' ) )
			{
				try
				{
					$attachment	= \IPS\Db::i()->select( '*', 'core_attachments', array( 'attach_id=?', $image->getAttribute( 'data-fileid' ) ) )->first();
					$image->setAttribute( 'src', '{fileStore.core_Attachment}/' . $attachment['attach_thumb_location'] );
					
					$anchor = $image->parentNode;
					$anchor->setAttribute( 'href', '{fileStore.core_Attachment}/' . $attachment['attach_location'] );
					
					$rebuilt = TRUE;
				}
				catch ( \Exception $e ) { }
			}
			else
			{
				if ( ! isset( static::$fileObjectClasses['core_Emoticons'] ) )
				{
					static::$fileObjectClasses['core_Emoticons'] = \IPS\File::getClass('core_Emoticons' );
				}
				
				if ( static::$emoticons === NULL )
				{
					static::$emoticons = static::getEmoticons();
				}

				if ( ( $image->tagName === 'img' and preg_match( '#^(' . preg_quote( rtrim( static::$fileObjectClasses['core_Emoticons']->baseUrl(), '/' ), '#' ) . ')/(.+?)$#', $image->getAttribute('src'), $matches ) ) )
				{
					foreach( static::$emoticons as $emo )
					{
						if ( $emo['image'] == $matches[2] )
						{
							$image->setAttribute( 'src', '{fileStore.core_Emoticons}/' . $matches[2] );

							if( $emo['image_2x'] && $emo['width'] && $emo['height'] )
							{
								/* Retina emoticons require a width and height for proper scaling */
								$image->setAttribute( 'srcset', '%7BfileStore.core_Emoticons%7D/' . $emo['image_2x'] . ' 2x' );
								$image->setAttribute( 'width', $emo['width'] );
								$image->setAttribute( 'height', $emo['height'] );
							}
							$rebuilt = TRUE;
						}
					}
				}
			}
		}

		if( $rebuilt )
		{
			$value = $content->saveHTML();
			
			$value = preg_replace( '/<meta http-equiv(?:[^>]+?)>/i', '', preg_replace( '/^<!DOCTYPE.+?>/', '', str_replace( array( '<html>', '</html>', '<body>', '</body>', '<head>', '</head>' ), '', $value ) ) );
			
			/* A few more replacements for dynamic file object URLs */
			$value = preg_replace( '#<([^>]+?)(href|src|srcset)=(\'|")%7BfileStore\.([\d\w\_]+?)%7D/#i', '<\1\2=\3<fileStore.\4>/', $value );
			$value = preg_replace( '#<([^>]+?)(srcset)=(\'|")%7BfileStore\.([\d\w\_]+?)%7D/#i', '<\1\2=\3<fileStore.\4>/', $value );
			$value = preg_replace( '#<([^>]+?)(href|src|srcset)=(\'|")%7B___base_url___%7D/#i', '<\1\2=\3<___base_url___>/', $value );
			$value = preg_replace( '#<([^>]+?)(data-fileid)=(\'|")%7B___base_url___%7D/#i', '<\1\2=\3<___base_url___>/', $value );
			
			return $value;
		}

		return FALSE;
	}
	
	/**
	 * Fetch emoticons.
	 *
	 * @return array
	 */
	protected static function getEmoticons()
	{
		$emoticons = array();
		
		try
		{
			foreach ( \IPS\Db::i()->select( 'image, image_2x, width, height', 'core_emoticons' ) as $row )
			{
				$emoticons[] = $row;
			}
		}
		catch( \IPS\Db\Exception $ex )
		{
			/* The image_2x column was added in 4.1 so may not exist if Parser is used in previous upgrade modules */
			foreach ( \IPS\Db::i()->select( 'image, NULL as image_2x, 0 as width, 0 as height', 'core_emoticons' ) as $row )
			{
				$emoticons[] = $row;
			}
		}
		
		return $emoticons;
	}

	/**
	 * Return the default allowed CSS classes
	 *
	 * @note	Abstracted to allow easier override by third party devs
	 * @return	array
	 */
	protected function getAllowedCssClasses()
	{
		return array(
			'ipsStyle', 'ipsStyle_underline', 'ipsStyle_center', 'ipsStyle_left', 'ipsStyle_right', 'ipsStyle_indent', 'ipsStyle_hr',
			'ipsSpoiler', 'ipsSpoiler_header', 'ipsSpoiler_contents', 
			'ipsButton',
			'ipsCode', 'prettyprint', 'prettyprinted', 'lang-auto', 'lang-javascript', 'lang-php', 'lang-css', 'lang-html', 'lang-xml', 'linenums',
			'ipsQuote','ipsQuote_citation', 'ipsQuote_contents',
			'fa',
			'ipsImage', 'ipsImage_thumbnailed', 'ipsAttachLink', 'ipsAttachLink_image',
			'ipsEmbedded_image', 'ipsEmbedded_withImage', 'ipsEmbedded_headerArea', 'ipsEmbedded_type', 'ipsEmbedded', 'ipsEmbedded_error', 'ipsEmbeddedVideo', 'ipsEmbeddedVideo_limited', 'ipsEmbeddedOther',
			'ipsType_medium', 'ipsType_small', 'ipsEmbedded_content', 'ipsEmbedded_stats',
			'ipsUserPhoto_tiny',
		);
	}

	/**
	 * Perform a safe html_entity_decode if you are not using UTF-8 MB4
	 *
	 * @param	string	$value	Value to html entity decode
	 * @return	string
	 */
	public static function utf8mb4SafeDecode( $value )
	{
		$value = html_entity_decode( $value, ENT_QUOTES, 'UTF-8' );

		if ( \IPS\Settings::i()->getFromConfGlobal('sql_utf8mb4') !== TRUE )
		{
			$value = preg_replace_callback( '/[\x{10000}-\x{10FFFF}]/u', function( $mb4Character ) {
				return mb_convert_encoding( $mb4Character[0], 'HTML-ENTITIES', 'UTF-8' );
			}, $value );
		}

		return $value;
	}
}
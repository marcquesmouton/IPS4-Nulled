<?php
/**
 * @brief		Sockets REST Class
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		18 Mar 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Http\Request;
 
/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Sockets REST Class
 */
class _Sockets
{	
	/**
	 * @brief	URL
	 */
	protected $url = NULL;
	
	/**
	 * @brief	HTTP Version
	 */
	protected $httpVersion = '1.1';
	
	/**
	 * @brief	Timeout
	 */
	protected $timeout = 5;
		
	/**
	 * @brief	Headers
	 */
	protected $headers = array();
	
	/**
	 * @brief	Follow redirects?
	 */
	protected $followRedirects = TRUE;
	
	/**
	 * Contructor
	 *
	 * @param	\IPS\Http\Url	$url				URL
	 * @param	int				$timeout			Timeout (in seconds)
	 * @param	string			$httpVersion		HTTP Version
	 * @param	bool|int		$followRedirects	Automatically follow redirects? If a number is provided, will follow up to that number of redirects
	 * @return	void
	 */
	public function __construct( $url, $timeout, $httpVersion, $followRedirects )
	{
		$this->url = $url;
		$this->httpVersion = $httpVersion ?: '1.1';
		$this->timeout = $timeout;
		$this->followRedirects = $followRedirects;
		$this->contextOptions['http']['ignore_errors'] = TRUE;
	}
	
	/**
	 * Login
	 *
	 * @param	string	Username
	 * @param	string	Password
	 * @retrun	\IPS\Http\Request\Socket (for daisy chaining)	
	 */
	public function login( $username, $password )
	{
		$this->setHeaders( array( 'Authorization' => 'Basic ' . base64_encode( "{$username}:{$password}" ) ) );
		return $this;
	}
	
	/**
	 * Set Headers
	 *
	 * @param	array	Key/Value pair of headers
	 * @return	\IPS\Http\Request\Socket
	 */
	public function setHeaders( $headers )
	{
		$this->headers = array_merge( $this->headers, $headers );	
		return $this;
	}
	
	/**
	 * Toggle SSL checks
	 *
	 * @param	boolean		$value	True will enable SSL checks, false will disable them
	 * @return	\IPS\Http\Request\Socket
	 */
	public function sslCheck( $value=TRUE )
	{
		/* This is here for compatibility with curl */
		return $this;
	}
	
	/**
	 * Magic Method: __call
	 *
	 * @param	string	$method
	 * @param	array	Params
	 * @return	\IPS\Http\Response
	 */
	public function __call( $method, $params )
	{
		/* The data (string or array) will be the first parameter */
		if ( isset( $params[0] ) && is_array( $params[0] ) )
		{
			$this->setHeaders( array( 'Content-Type' => 'application/x-www-form-urlencoded' ) );
			$data = http_build_query( $params[0], '', '&' );
		}
		else
		{
			$data = ( isset( $params[0] ) ? $params[0] : NULL );
		}

		/* Set the method */
		$this->contextOptions['http']['method'] = mb_strtoupper( $method );

		/* Set the Content-Length header automatically if this is a POST */
		if( $this->contextOptions['http']['method'] == 'POST' )
		{
			$this->setHeaders( array( 'Content-Length' => \strlen( $data ) ) );
		}
		
		/* Parse URL */		
		if ( isset( $this->url->data['user'] ) or isset( $this->url->data['pass'] ) )
		{
			$this->login( isset( $this->url->data['user'] ) ? $this->url->data['user'] : NULL, isset( $this->url->data['pass'] ) ? $this->url->data['pass'] : NULL );
		}

		/* Open connection */
		$resource = @\fsockopen( ( $this->url->data['scheme'] === 'https' ? 'ssl://' : '' ) . $this->url->data['host'], isset( $this->url->data['port'] ) ? $this->url->data['port'] : ( $this->url->data['scheme'] === 'http' ? 80 : 443 ), $errno, $errstr, $this->timeout );
		if ( $resource === FALSE )
		{
			throw new SocketsException( $errstr, $errno );
		}
		
		/* Work out location to use. Ideally use it exactly as $this->url has it as some servers (Google is one) will be sensitive about the encoding
			and we will want to use whatever was passed to \IPS\Url::__construct. If we can't figure it out, construct manually.
			Additionally, some servers may use a URL such as http://news.domain.com/news which then causes the subdomain to be matched rather than the actual path */
		$pathPos = ( isset( $this->url->data['path'] ) ) ? mb_strpos( str_replace( $this->url->data['host'], '', $this->url ), $this->url->data['path'] ) : FALSE;
		if ( $pathPos !== FALSE )
		{
			$location = mb_substr( str_replace( $this->url->data['host'], '', $this->url ), $pathPos );
		}
		else
		{
			$location = ( isset( $this->url->data['path'] ) ? $this->url->data['path'] : '' ) . ( !empty( $this->url->queryString ) ? ( '?' . http_build_query( $this->url->queryString ) ) : '' ) . ( isset( $this->url->data['fragment'] ) ? "#{$this->url->data['fragment']}" : '' );
		}
		
		/* Send request */		
		$request  = mb_strtoupper( $method ) . ' ' . $location . " HTTP/{$this->httpVersion}\r\n";
		$request .= "Host: {$this->url->data['host']}\r\n";
		foreach ( $this->headers as $k => $v )
		{
			$request .= "{$k}: {$v}\r\n";
		}
		$request .= "Connection: Close\r\n";
		$request .= "\r\n";
		if ( $data )
		{
			$request .= $data;
		}
		\fwrite( $resource, $request );
								
		/* Read response */
		stream_set_timeout( $resource, $this->timeout );
		$status = stream_get_meta_data( $resource );
		$response = '';
		while( !feof($resource) and !$status['timed_out'] )		
		{
			$response .= \fgets( $resource, 8192 );
			$status = stream_get_meta_data( $resource );
		}
		
		/* Close connection */
		\fclose( $resource );
		
		/* Log */
		try
		{
			\IPS\Log::i( LOG_DEBUG )->write( "\n\n------------------------------------\nSOCKETS REQUEST: {$this->url}\n------------------------------------\n\n{$request}\n\n------------------------------------\nRESPONSE\n------------------------------------\n\n" . $response, 'request' );
		}
		catch( \Exception $e ){}

		/* Interpret response */
		$response = new \IPS\Http\Response( $response );

		/* Either return it or follow it */
		if ( $this->followRedirects and ( $response->httpResponseCode == 302 OR $response->httpResponseCode == 301 ) )
		{
			$newRequest = \IPS\Http\Url::external( $response->httpHeaders['Location'] )->request( $this->timeout, $this->httpVersion, is_int( $this->followRedirects ) ? ( $this->followRedirects - 1 ) : $this->followRedirects );
			return call_user_func_array( array( $newRequest, $method ), $params );
		}
		return $response;
	}

}

/**
 * Sockets Exception Class
 */
class SocketsException extends \IPS\Http\Request\Exception { }
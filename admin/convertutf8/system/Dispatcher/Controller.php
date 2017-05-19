<?php
/**
 * @brief		Abstract class that Controllers should extend
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		18 Feb 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPSUtf8\Dispatcher;

/**
 * Abstract class that Controllers should extend
 */
abstract class Controller
{

	/**
	 * Execute
	 *
	 * @param	string				$command	The part of the query string which will be used to get the method
	 * @param	\IPS\Http\Url|NULL	$url		The base URL for this controller or NULL to calculate automatically
	 * @return	void
	 */
	public function execute()
	{
		if( \IPSUtf8\Request::i()->do and \substr( \IPSUtf8\Request::i()->do, 0, 1 ) !== '_' )
		{
			$method = ( \IPSUtf8\Request::i()->do and preg_match( '/^[a-zA-Z\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', \IPSUtf8\Request::i()->do ) ) ? \IPSUtf8\Request::i()->do : NULL;

			if ( $method !== NULL AND method_exists( $this, $method ) )
			{
				call_user_func( array( $this, $method ) );
			}
			else
			{
				\IPSUtf8\Output\Browser::i()->error( "Page not found" );
			}
		}
		else
		{
			$this->manage();
		}
	}
}
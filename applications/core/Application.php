<?php
/**
 * @brief		Core Application Class
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		18 Feb 2013
 * @version		SVN_VERSION_NUMBER
 */
 
namespace IPS\core;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Core Application Class
 */
class _Application extends \IPS\Application
{
	/**
	 * ACP Menu Numbers
	 *
	 * @param	array	$queryString	Query String
	 * @return	int
	 */
	public function acpMenuNumber( $queryString )
	{
		parse_str( $queryString, $queryString );
		switch ( $queryString['controller'] )
		{
			case 'advertisements':
				return \IPS\Db::i()->select( 'COUNT(*)', 'core_advertisements', array( 'ad_active=-1' ) )->first();
				break;
		}
	}
	
	/**
	 * Return the custom badge for each row
	 *
	 * @return	NULL|array		Null for no badge, or an array of badge data (0 => CSS class type, 1 => language string, 2 => optional raw HTML to show instead of language string)
	 */
	public function get__badge()
	{
		/* Is there an update to show? */
		$badge	= NULL;

		if( $this->update_version )
		{
			$data	= json_decode( $this->update_version, TRUE );
			
			if( !empty($data['longversion']) AND $data['longversion'] > $this->long_version )
			{
				$released	= NULL;

				if( $data['released'] AND intval($data['released']) == $data['released'] AND \strlen($data['released']) == 10 )
				{
					$released	= (string) \IPS\DateTime::ts( $data['released'] )->localeDate();
				}
				else if( $data['released'] )
				{
					$released	= $data['released'];
				}

				$badge	= array(
					0	=> 'new',
					1	=> '',
					2	=> \IPS\Theme::i()->getTemplate( 'global', 'core' )->updatebadge( $data['version'], \IPS\Http\Url::internal( 'app=core&module=system&controller=upgrade', 'admin' ), $released, FALSE )
				);
			}
		}

		return $badge;
	}

	/**
	 * [Node] Get Icon for tree
	 *
	 * @note	Return the class for the icon (e.g. 'globe')
	 * @return	string|null
	 */
	protected function get__icon()
	{
		return 'cogs';
	}
	
	/**
	 * Can view page even when user is a guest when guests cannot access the site
	 *
	 * @param	\IPS\Application\Module	$module			The module
	 * @param	string					$controller		The controller
	 * @param	string|NULL				$do				To "do" parameter
	 * @return	bool
	 */
	public function allowGuestAccess( \IPS\Application\Module $module, $controller, $do )
	{
		return (
			$module->key == 'system'
			and
			in_array( $controller, array( 'login', 'register', 'lostpass', 'terms', 'ajax', 'privacy', 'editor', 'language', 'theme' ) )
		)
		or
		( 
			$module->key == 'contact' and $controller == 'contact'
		);
	}
	
	/**
	 * Default front navigation
	 *
	 * @code
	 	
	 	// Each item...
	 	array(
			'key'		=> 'Example',		// The extension key
			'app'		=> 'core',			// [Optional] The extension application. If ommitted, uses this application	
			'config'	=> array(...),		// [Optional] The configuration for the menu item
			'title'		=> 'SomeLangKey',	// [Optional] If provided, the value of this language key will be copied to menu_item_X
			'children'	=> array(...),		// [Optional] Array of child menu items for this item. Each has the same format.
		)
	 	
	 	return array(
		 	'rootTabs' 		=> array(), // These go in the top row
		 	'browseTabs'	=> array(),	// These go under the Browse tab on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Browse tab may not exist)
		 	'browseTabsEnd'	=> array(),	// These go under the Browse tab after all other items on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Browse tab may not exist)
		 	'activityTabs'	=> array(),	// These go under the Activity tab on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Activity tab may not exist)
		)
	 * @endcode
	 * @return array
	 */
	public function defaultFrontNavigation()
	{
		$activityTabs = array(
			array( 'key' => 'AllActivity' ),
			array( 'key' => 'YourActivityStreams' ),
		);
		
		foreach ( array( 1, 2 ) as $k )
		{
			try
			{
				\IPS\core\Stream::load( $k );
				$activityTabs[] = array(
					'key'		=> 'YourActivityStreamsItem',
					'config'	=> array( 'menu_stream_id' => $k )
				);
			}
			catch ( \Exception $e ) { }
		}

		$activityTabs[] = array( 'key' => 'Search' );
		
		return array(
			'rootTabs'		=> array(),
			'browseTabsEnd'	=> array(
				array( 'key' => 'Guidelines' ),
				array( 'key' => 'StaffDirectory' ),
				array( 'key' => 'OnlineUsers' ),
			),
			'activityTabs'	=> $activityTabs
		);
	}
}

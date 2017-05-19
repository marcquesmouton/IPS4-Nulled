<?php
/**
 * @brief		Gallery Application Class 
 *
 * @copyright	(c) 2014 Invision Power Services, Inc.
 * @package		IPS Social Suite
 * @subpackage	Gallery
 * @since		04 Mar 2014
 * @version		
 */
 
namespace IPS\gallery;

/**
 * Gallery Application Class
 */
class _Application extends \IPS\Application
{
	/**
	 * Init
	 *
	 * @return	void
	 */
	public function init()
	{
		/* If the viewing member cannot view the board (ex: guests must login first), then send a 404 Not Found header here, before the Login page shows in the dispatcher */
		if ( \IPS\Dispatcher::hasInstance() AND \IPS\Dispatcher::i()->controllerLocation === 'front')
		{
			if ( !\IPS\Member::loggedIn()->group['g_view_board'] and ( \IPS\Request::i()->module == 'gallery' and \IPS\Request::i()->controller == 'browse' and \IPS\Request::i()->do == 'rss' )
			or ( \IPS\Member::loggedIn()->members_bitoptions['remove_gallery_access'] )
			)
			{
				\IPS\Output::i()->error( 'node_error', '2G218/1', 404, '' );
			}
		}
	}

	/**
	 * [Node] Get Icon for tree
	 *
	 * @note	Return the class for the icon (e.g. 'globe')
	 * @return	string|null
	 */
	protected function get__icon()
	{
		return 'camera';
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
		return array(
			'rootTabs'		=> array(),
			'browseTabs'	=> array( array( 'key' => 'Gallery' ) ),
			'browseTabsEnd'	=> array(),
			'activityTabs'	=> array()
		);
	}
}
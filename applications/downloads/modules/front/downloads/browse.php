<?php
/**
 * @brief		Browse Files Controller
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @subpackage	Downloads
 * @since		08 Oct 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\downloads\modules\front\downloads;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Browse Files
 */
class _browse extends \IPS\Dispatcher\Controller
{
	/**
	 * Route
	 *
	 * @return	void
	 */
	protected function manage()
	{
		if ( isset( \IPS\Request::i()->currency ) and in_array( \IPS\Request::i()->currency, \IPS\nexus\Money::currencies() ) and isset( \IPS\Request::i()->csrfKey ) and \IPS\Request::i()->csrfKey === \IPS\Session\Front::i()->csrfKey )
		{
			$_SESSION['currency'] = \IPS\Request::i()->currency;
		}
		
		if ( isset( \IPS\Request::i()->id ) )
		{
			try
			{
				$this->_category( \IPS\downloads\Category::loadAndCheckPerms( \IPS\Request::i()->id, 'read' ) );
			}
			catch ( \OutOfRangeException $e )
			{
				\IPS\Output::i()->error( 'node_error', '2D175/1', 404, '' );
			}
		}
		else
		{
			$this->_index();
		}
	}
	
	/**
	 * Show Index
	 *
	 * @return	void
	 */
	protected function _index()
	{
		/* Add RSS feed */
		if ( \IPS\Settings::i()->idm_rss )
		{
			\IPS\Output::i()->rssFeeds['idm_rss_title'] = \IPS\Http\Url::internal( 'app=downloads&module=downloads&controller=browse&do=rss', 'front', 'downloads_rss' );
		}
		
		/* Get stuff */
		$featured = iterator_to_array( \IPS\downloads\File::featured( 4, '_rand' ) );
		$new = \IPS\downloads\File::getItemsWithPermission( array(), NULL, 14 );
		$highestRated = \IPS\downloads\File::getItemsWithPermission( array( array( 'file_rating > ?', 0 ) ), 'file_rating DESC, file_reviews DESC', 14 );
		$mostDownloaded = \IPS\downloads\File::getItemsWithPermission( array( array( 'file_downloads > ?', 0 ) ), 'file_downloads DESC', 14 );
		
		/* Online User Location */
		\IPS\Session::i()->setLocation( \IPS\Http\Url::internal( 'app=downloads', 'front', 'downloads' ), array(), 'loc_downloads_browsing' );
		
		/* Display */
		\IPS\Output::i()->sidebar['contextual'] = \IPS\Theme::i()->getTemplate( 'browse' )->indexSidebar( \IPS\downloads\Category::canOnAny('add') );
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('downloads');
		\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'browse' )->index( $featured, $new, $highestRated, $mostDownloaded );
	}
	
	/**
	 * Show Category
	 *
	 * @param	\IPS\downloads\Category	$category	The category to show
	 * @return	void
	 */
	protected function _category( $category )
	{
		$_count = \IPS\downloads\File::getItemsWithPermission( array( array( \IPS\downloads\File::$databasePrefix . \IPS\downloads\File::$databaseColumnMap['container'] . '=?', $category->_id ) ), NULL, 1, 'read', NULL, 0, NULL, FALSE, FALSE, FALSE, TRUE );

		if( !$_count )
		{
			/* Show a 'no files' template if there's nothing to display */
			$table = \IPS\Theme::i()->getTemplate( 'browse' )->noFiles( $category );
		}
		else
		{
			/* Build table */
			$table = new \IPS\Helpers\Table\Content( 'IPS\downloads\File', $category->url(), NULL, $category );
			$table->classes = array( 'ipsDataList_large' );
			
			if ( \IPS\Application::appIsEnabled( 'nexus' ) and \IPS\Settings::i()->idm_nexus_on )
			{
				$table->filters = array(
					'file_free'	=> "( ( file_cost='' OR file_cost IS NULL ) AND ( file_nexus='' OR file_nexus IS NULL ) )",
					'file_paid'	=> "( file_cost<>'' OR file_nexus>0 )",
				);
			}
			$table->title = \IPS\Member::loggedIn()->language()->pluralize(  \IPS\Member::loggedIn()->language()->get('download_file_count'), array( $_count ) );
		}

		/* Online User Location */
		$permissions = $category->permissions();
		\IPS\Session::i()->setLocation( $category->url(), explode( ",", $permissions['perm_view'] ), 'loc_downloads_viewing_category', array( "downloads_category_{$category->id}" => TRUE ) );
				
		/* Output */
		\IPS\Output::i()->title		= $category->_title;

		\IPS\Output::i()->contextualSearchOptions[ \IPS\Member::loggedIn()->language()->addToStack( 'search_contextual_item', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack( 'category' ) ) ) ) ] = array( 'type' => 'downloads_file', 'nodes' => $category->_id );
		\IPS\Output::i()->sidebar['contextual'] = \IPS\Theme::i()->getTemplate( 'browse' )->indexSidebar( \IPS\downloads\Category::canOnAny('add'), $category );
		\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'browse' )->category( $category, (string) $table );
	}

	/**
	 * Show a category listing
	 *
	 * @return	void
	 */
	protected function categories()
	{
		/* Online User Location */
		\IPS\Session::i()->setLocation( \IPS\Http\Url::internal( 'app=downloads&module=downloads&controller=browse&do=categories', 'front', 'downloads_categories' ), array(), 'loc_downloads_browsing_categories' );
		
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('__app_downloads');
		\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'browse' )->categories();
	}
	
	/**
	 * Latest Files RSS
	 *
	 * @return	void
	 */
	protected function rss()
	{
		if( !\IPS\Settings::i()->idm_rss )
		{
			\IPS\Output::i()->error( 'rss_offline', '2D175/2', 403, 'rss_offline_admin' );
		}

		$document = \IPS\Xml\Rss::newDocument( \IPS\Http\Url::internal( 'app=downloads&module=downloads&controller=browse', 'front', 'downloads' ), \IPS\Member::loggedIn()->language()->get('idm_rss_title'), \IPS\Member::loggedIn()->language()->get('idm_rss_title') );
		
		foreach ( \IPS\downloads\File::getItemsWithPermission() as $file )
		{
			$document->addItem( $file->name, $file->url(), $file->desc, \IPS\DateTime::ts( $file->updated ), $file->id );
		}
		
		/* @note application/rss+xml is not a registered IANA mime-type so we need to stick with text/xml for RSS */
		\IPS\Output::i()->sendOutput( $document->asXML(), 200, 'text/xml' );
	}
}
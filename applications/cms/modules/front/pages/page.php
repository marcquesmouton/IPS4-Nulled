<?php
/**
 * @brief		[Front] Page Controller
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		25 Feb 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\cms\modules\front\pages;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * page
 */
class _page extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		parent::execute();
	}

	/**
	 * Determine which method to load
	 *
	 * @return void
	 */
	public function manage()
	{
		/* As we bypass the normal URL checks, we need to perform this here */
		if ( \IPS\Settings::i()->use_friendly_urls and ! empty( \IPS\Request::i()->url()->data['query'] ) and ( mb_stripos( \IPS\Request::i()->url()->data['query'], 'app=cms&module=pages&controller=page&path=' ) !== FALSE ) )
		{ 
			try
			{
				$url = \IPS\Request::i()->url();
				$url->makeFriendly( 'content_page_path', \IPS\Request::i()->url()->queryString['path'] );
	
				\IPS\Output::i()->redirect( $url );
			}
			catch( \Exception $ex ) { }
		}
		
		$this->view();
	}
	
	/**
	 * Display a page. Sounds simple doesn't it? Well it's not.
	 *
	 * @return	void
	 */
	protected function view()
	{
		$page = $this->getPage();
		
		/* Database specific checks */
		if ( isset( \IPS\Request::i()->advancedSearchForm ) AND isset( \IPS\Request::i()->d ) )
		{
			/* showTableSearchForm just triggers __call which returns the database dispatcher HTML as we
			 * do not want the page content around the actual database */
			\IPS\Output::i()->output = $this->showTableSearchForm();
			return;
		}

		if ( \IPS\Request::i()->path == $page->full_path )
		{
			/* Just viewing this page, no database categories or records */
			$permissions = $page->permissions();
			\IPS\Session::i()->setLocation( $page->url(), explode( ",", $permissions['perm_view'] ), 'loc_cms_viewing_page', array( 'cms_page_' . $page->_id => TRUE ) );
		}

		$page->output();
	}
	
	/**
	 * Get the current page
	 * 
	 * @return \IPS\cms\Pages\Page
	 */
	public function getPage()
	{
		$page = null;
		if ( isset( \IPS\Request::i()->page_id ) )
		{
			try
			{
				$page = \IPS\cms\Pages\Page::load( \IPS\Request::i()->page_id );
			}
			catch ( \OutOfRangeException $e )
			{
				\IPS\Output::i()->error( 'content_err_page_404', '2T187/1', 404, '' );
			}
		}
		else if ( isset( \IPS\Request::i()->path ) AND  \IPS\Request::i()->path != '/' )
		{
			try
			{
				$page = \IPS\cms\Pages\Page::loadFromPath( \IPS\Request::i()->path );
			}
			catch ( \OutOfRangeException $e )
			{
				try
				{
					$page = \IPS\cms\Pages\Page::getUrlFromHistory( \IPS\Request::i()->path, ( isset( \IPS\Request::i()->url()->data['query'] ) ? \IPS\Request::i()->url()->data['query'] : NULL ) );

					if( (string) $page == (string) \IPS\Request::i()->url() )
					{
						\IPS\Output::i()->error( 'content_err_page_404', '2T187/3', 404, '' );
					}

					\IPS\Output::i()->redirect( $page, NULL, 301 );
				}
				catch( \OutOfRangeException $e )
				{
					\IPS\Output::i()->error( 'content_err_page_404', '2T187/2', 404, '' );
				}
			}
		}
		else
		{
            try
            {
                $page = \IPS\cms\Pages\Page::getDefaultPage();
            }
            catch ( \OutOfRangeException $e )
            {
                \IPS\Output::i()->error( 'content_err_page_404', '2T257/1', 404, '' );
            }
		}
		
		if ( $page === NULL )
		{
            \IPS\Output::i()->error( 'content_err_page_404', '2T257/2', 404, '' );
		}

		if ( ! $page->can('view') )
		{
			\IPS\Output::i()->error( 'content_err_page_403', '2T187/3', 403, '' );
		}
		
		/* Set the current page, so other blocks, DBs, etc don't have to figure out where they are */
		\IPS\cms\Pages\Page::$currentPage = $page;
		
		return $page;
	}
	
	/**
	 * Capture database specific things
	 *
	 * @param	string	$method	Desired method
	 * @param	array	$args	Arguments
	 * @return	void
	 */
	public function __call( $method, $args )
	{
		$page = $this->getPage();
		
		$databaseId = ( isset( \IPS\Request::i()->d ) ) ? \IPS\Request::i()->d : $page->getDatabase()->_id;
		
		if ( $databaseId !== NULL )
		{
			try
			{
				return \IPS\cms\Databases\Dispatcher::i()->setDatabase( $databaseId )->run();
			}
			catch( \OutOfRangeException $e )
			{
				\IPS\Output::i()->error( 'content_err_page_404', '2T257/3', 404, '' );
			}
		}
	}

	/**
	 * Embed
	 *
	 * @return	void
	 */
	protected function embed()
	{
		return $this->__call( 'embed', func_get_args() );
	}
}
<?php
/**
 * @brief		Database Dispatcher
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		16 April 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\cms\Databases;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Database Dispatcher
 */
class _Dispatcher extends \IPS\Dispatcher
{
	/**
	 * @brief	Singleton Instance (So we don't re-use the regular dispatcher)
	 */
	protected static $instance = NULL;
	
	/**
	 * @brief	Controller location
	 */
	public $controllerLocation = 'front';
	
	/**
	 * @brief	Database Id
	 */
	public $databaseId = NULL;
	
	/**
	 * @brief	Category Id
	 */
	public $categoryId = NULL;

	/**
	 * @brief	Record Id
	 */
	public $recordId = NULL;

	/**
	 * @brief	Url
	 */
	public $url = NULL;
	
	/**
	 * @brief	Module
	 */
	public $module = NULL;
	
	/**
	 * @brief	Output to return
	 */
	public $output = NULL;
	
	/**
	 * Set Database ID
	 *
	 * @param	mixed	$databaseId		Database key or ID
	 * @return	\IPS\Dispatcher
	 */
	public function setDatabase( $databaseId )
	{
		/* Other areas rely on $this->databaseId being numeric */
		if ( ! is_numeric( $databaseId ) )
		{
			$database   = \IPS\cms\Databases::load( $databaseId, 'database_key' );
			$databaseId = $database->id;
		}

		$this->databaseId = $databaseId;

		$database   = \IPS\cms\Databases::load( $databaseId );
		if ( ! $database->use_categories )
		{
			$this->categoryId = $database->_default_category;
		}

		return $this;
	}
	
	/**
	 * Set Category ID
	 *
	 * @param	mixed	$categoryId		Category ID
	 * @return	\IPS\Dispatcher
	 */
	public function setCategory( $categoryId )
	{
		$this->categoryId = $categoryId;
		return $this;
	}
	
	/**
	 * Init
	 *
	 * @return void
	 */
	public function init()
	{
		if ( ( \IPS\cms\Pages\Page::$currentPage AND ! ( \IPS\Application::load('cms')->default AND ! \IPS\cms\Pages\Page::$currentPage->folder_id AND \IPS\cms\Pages\Page::$currentPage->default ) ) )
		{
			\IPS\Output::i()->breadcrumb['module'] = array( \IPS\cms\Pages\Page::$currentPage->url(), \IPS\cms\Pages\Page::$currentPage->_title );
		}
	}

	/**
	 * Run
	 *
	 * @return void
	 */
	public function run()
	{
		/* Coming from a widget? */
		if ( isset( \IPS\Request::i()->pageID ) and isset( \IPS\Request::i()->blockID ) )
		{
			if ( \IPS\cms\Pages\Page::$currentPage === NULL )
			{
				/* make sure this is a valid widgetized page to stop tampering */
				try
				{
					foreach ( \IPS\Db::i()->select( '*', 'cms_page_widget_areas', array( 'area_page_id=?', \IPS\Request::i()->pageID ) ) as $item )
					{
						foreach( json_decode( $item['area_widgets'], TRUE ) as $block )
						{
							if ( $block['key'] === 'Database' and isset( $block['configuration']['database'] ) and intval( $block['configuration']['database'] ) === $this->databaseId )
							{
								\IPS\cms\Pages\Page::$currentPage = \IPS\cms\Pages\Page::load( \IPS\Request::i()->pageID );
							}
						}
					}
				}
				catch( \UnderflowException $e ) { }
			}

			/* Try again */
			if ( \IPS\cms\Pages\Page::$currentPage === NULL )
			{
				\IPS\Output::i()->error( 'page_doesnt_exist', '2T251/1', 404 );
			}

			/* Unset do query param otherwise it confuses the controller->execute(); */
			\IPS\Request::i()->do = NULL;
		}

		$url = 'app=cms&module=pages&controller=page&path=' . \IPS\cms\Pages\Page::$currentPage->full_path;

		try
		{
			$database = \IPS\cms\Databases::load( $this->databaseId );
		}
		catch( \OutOfRangeException $ex )
		{
			\IPS\Output::i()->error( 'page_doesnt_exist', '2T251/2', 404 );
		}

		$path = trim(  preg_replace( '#' . \IPS\cms\Pages\Page::$currentPage->full_path . '#', '', \IPS\Request::i()->path, 1 ), '/' );
		
		$this->databaseId = $database->id;

		if ( ! $database->use_categories )
		{
			$this->categoryId = $database->default_category;
		}

		/* Got a specific category ID? */
		if ( $this->categoryId !== NULL and ! $path and ( ( $database->use_categories and $database->cat_index_type !== 1 ) OR ( ! $database->use_categories and isset( \IPS\Request::i()->do ) ) ) )
		{
			$this->module = 'category';
		}
		else if ( isset( \IPS\Request::i()->c ) AND is_numeric( \IPS\Request::i()->c ) )
		{
			$this->categoryId = \IPS\Request::i()->c;
			$this->module = 'category';
		}
		else if ( empty( $path ) )
		{
			$this->module = 'index';
		}
		else
		{
			$url .= '/' . $path;
			$recordClass = '\IPS\cms\Records' . $database->id;

			if ( $database->use_categories )
			{
				$catClass = '\IPS\cms\Categories' . $database->id;
				$category = $catClass::loadFromPath( $path, $database->id );

				if ( $category === NULL )
				{
					/* Is this a record? */
					$bits = explode( '/', $path );
					$slug = array_pop( $bits );

					try
					{
						$record = $recordClass::loadFromSlug( $slug );

						\IPS\Output::i()->redirect( $record->url(), NULL, 301 );
					}
					catch ( \OutOfRangeException $ex )
					{
						/* Check slug history */
						try
						{
							$record = $recordClass::loadFromSlugHistory( $slug );

							\IPS\Output::i()->redirect( $record->url(), NULL, 301 );
						}
						catch ( \OutOfRangeException $ex )
						{
							\IPS\Output::i()->error( 'page_doesnt_exist', '2T251/4', 404 );
						}
					}
				}

				$whatsLeft = preg_replace( '#' . $category->full_path . '#', '', $path, 1 );

				$this->categoryId = $category->id;
			}
			else
			{
				$whatsLeft = $path;
			}

			if ( $whatsLeft )
			{
				/* Find the record */
				try
				{
					$record = $recordClass::loadFromSlug( $whatsLeft );

					/* Make the Content controller all kinds of happy */
					\IPS\Request::i()->id = $this->recordId = $record->primary_id_field;
				}
				catch( \OutOfRangeException $ex )
				{
					/* Check slug history */
					try
					{
						$record = $recordClass::loadFromSlugHistory( $whatsLeft );

						\IPS\Output::i()->redirect( $record->url(), NULL, 301 );
					}
					catch( \OutOfRangeException $ex )
					{
						\IPS\Output::i()->error( 'page_doesnt_exist', '2T251/5', 404 );
					}
				}
				
				$this->module = 'record';
			}
			else
			{
				/* It's a category listing */
				$this->module = 'category';
			}
		}
		
		$this->url = \IPS\Http\Url::internal( $url, 'front', 'content_page_path' );
		$className = '\\IPS\\cms\\modules\\front\\database\\' . $this->module;

		/* Init class */
		if( !class_exists( $className ) )
		{
			\IPS\Output::i()->error( 'page_doesnt_exist', '2T251/6', 404 );
		}
		$controller = new $className;
		if( !( $controller instanceof \IPS\Dispatcher\Controller ) )
		{
			\IPS\Output::i()->error( 'page_not_found', '3T251/7', 500, '' );
		}

		\IPS\Dispatcher::i()->dispatcherController	= $controller;
		
		\IPS\Output::i()->defaultSearchOption = array( "cms_records{$this->databaseId}", "cms_records{$this->databaseId}_pl" );

		/* Add database key to body classes for easier database specific themeing */
		\IPS\Output::i()->bodyClasses[] = 'cCmsDatabase_' . $database->key;
		
		/* Execute */
		$controller->execute();
		
		return $this->finish();
	}
	
	/**
	 * Finish
	 *
	 * @return	void
	 */
	public function finish()
	{
		return $this->output;
	}
}
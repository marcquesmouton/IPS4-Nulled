<?php
/**
 * @brief		DatabaseFilters Widget
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @subpackage	content
 * @since		02 Sept 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\cms\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * LatestArticles Widget
 */
class _DatabaseFilters extends \IPS\Widget
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'DatabaseFilters';
	
	/**
	 * @brief	App
	 */
	public $app = 'cms';
		
	/**
	 * @brief	Plugin
	 */
	public $plugin = '';

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render()
	{
		/* Viewing a record */
		if ( \IPS\cms\Databases\Dispatcher::i()->recordId )
		{
			return '';
		}

		if ( ! \IPS\cms\Databases\Dispatcher::i()->databaseId AND ! \IPS\cms\Databases\Dispatcher::i()->categoryId )
		{
			return '';
		}
		
		try
		{
			$database = \IPS\cms\Databases::load( \IPS\cms\Databases\Dispatcher::i()->databaseId );
			$database->preLoadWords();
		}
		catch ( \OutOfRangeException $e )
		{
			return '';
		}
		
		try
		{
			$category = \IPS\cms\Categories::load( \IPS\cms\Databases\Dispatcher::i()->categoryId );
		}
		catch ( \OutOfRangeException $e )
		{
			return '';
		}
		
		if ( ! $database->use_categories AND $database->cat_index_type !== 0 )
		{
			return '';
		}
		
		$fieldClass = 'IPS\cms\Fields' . $database->id;
		
		$fields = array();
		$cookie = $category->getFilterCookie();
		$cookieValues = ( $cookie !== NULL ) ? array_combine( array_map( create_function( '$k', 'return "field_" . $k;'), array_keys( $cookie ) ), $cookie ) : array();

		$urlValues = array();

		foreach( \IPS\Request::i() as $k => $v )
		{
			if( mb_strpos( $k, 'content_field_' ) !== FALSE )
			{
				$urlValues[ str_replace( 'content_', '', $k ) ] = is_array( $v ) ? implode( ',', $v ) : $v;
			}
		}

		$cookieValues = array_merge( $urlValues, $cookieValues );

		foreach( $fieldClass::fields( $cookieValues, 'view', $category, $fieldClass::FIELD_SKIP_TITLE_CONTENT | $fieldClass::FIELD_DISPLAY_FILTERS ) as $id => $field )
		{
			$fields[ $id ] = $field;
		}
		
		if ( count( $fields ) )
		{
			$form = new \IPS\Helpers\Form( 'category_filters', 'update', $category->url() );
			$form->class = 'ipsForm_vertical'; 
			if ( \IPS\Request::i()->sortby )
			{
				$form->hiddenValues['sortby']		 = \IPS\Request::i()->sortby;
				$form->hiddenValues['sortdirection'] = isset( \IPS\Request::i()->sortdirection ) ? \IPS\Request::i()->sortdirection : 'desc';
			}
			else
			{
				$form->hiddenValues['sortby']		 = $database->field_sort;
				$form->hiddenValues['sortdirection'] = $database->field_direction;
			}
			
			$form->hiddenValues['record_type'] = 'all';
			$form->hiddenValues['time_frame'] = 'show_all';
			
			foreach( $fields as $id => $field )
			{
				$form->add( $field );
			}

			$form->add( new \IPS\Helpers\Form\Checkbox( 'cms_widget_filters_remember', ( $cookie !== NULL ) ? TRUE : FALSE , FALSE, array( 'label' => 'cms_widget_filters_remember_text') ) );
			
			if ( $values = $form->values() )
			{
				$url    = $category->url()->setQueryString( array( 'advanced_search_submitted' => 1, 'csrfKey' => \IPS\Session::i()->csrfKey ) );
				$cookie = array();
				$params = array();
				foreach( $values as $k => $v )
				{
					if ( mb_substr( $k, 0, 14 ) === 'content_field_' )
					{
						$cookie[ mb_substr( $k, 14 ) ] = $v;
						$params[ $k ] = $v;
					}
				}
				
				if ( count( $form->hiddenValues ) )
				{
					foreach( $form->hiddenValues as $k => $v )
					{
						if ( $k !== 'csrfKey' )
						{
							if ( !in_array( $k, array( 'sortby', 'sortdirection' ) ) )
							{
								$cookie[ $k ] = $v;
							}
							$params[ $k ] = $v;
						}
					}
				}
				
				if ( $values['cms_widget_filters_remember'] )
				{
					$category->saveFilterCookie( $cookie );
					\IPS\Output::i()->redirect( $category->url() );
				}
				else
				{
					\IPS\Output::i()->redirect( $url->setQueryString( $params ) );
				}
			}
			
			return $this->output( $database, $category, $form );
		}
		else
		{
			return '';
		}
	}
}
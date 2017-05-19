<?php
/**
 * @brief		Custom Blocks Block
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @subpackage	content
 * @since		17 Oct 2014
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
 * Custom block Widget
 */
class _Blocks extends \IPS\Widget\PermissionCache
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'Blocks';
	
	/**
	 * @brief	App
	 */
	public $app = 'cms';
		
	/**
	 * @brief	Plugin
	 */
	public $plugin = '';
	
	/**
	 * Constructor
	 *
	 * @param	String				$uniqueKey				Unique key for this specific instance
	 * @param	array				$configuration			Widget custom configuration
	 * @param	null|string|array	$access					Array/JSON string of executable apps (core=sidebar only, content=IP.Content only, etc)
	 * @param	null|string			$orientation			Orientation (top, bottom, right, left)
	 * @param	boolean				$allowReuse				If true, when the block is used, it will remain in the sidebar so it can be used again.
	 * @param	string				$menuStyle				Menu is a drop down menu, modal is a bigger modal panel.
	 * @return	void
	 */
	public function __construct( $uniqueKey, array $configuration, $access=null, $orientation=null )
	{
		try
		{
			if (  isset( $configuration['cms_widget_custom_block'] ) )
			{
				$block = \IPS\cms\Blocks\Block::load( $configuration['cms_widget_custom_block'], 'block_key' );
				if ( $block->type === 'custom' AND ! $block->cache )
				{
					$this->neverCache = TRUE;
				}
			}
		}
		catch( \Exception $e ) { }
		
		parent::__construct( $uniqueKey, $configuration, $access, $orientation );
	}
	
	/**
	 * Specify widget configuration
	 *
	 * @param   \IPS\Helpers\Form   $form       Form Object
	 * @return	null|\IPS\Helpers\Form
	 */
	public function configuration( &$form=null )
 	{
	    if ( $form === null )
	    {
		    $form = new \IPS\Helpers\Form;
	    }

	    $form->add( new \IPS\Helpers\Form\Node( 'cms_widget_custom_block', ( isset( $this->configuration['cms_widget_custom_block'] ) ? $this->configuration['cms_widget_custom_block'] : NULL ), FALSE, array(
            'class' => '\IPS\cms\Blocks\Container',
            'permissionCheck' => function( $node )
                {
	                if ( $node instanceof \IPS\cms\Blocks\Container )
	                {
		                return FALSE;
	                }

	                return TRUE;
                }
        ) ) );

	    return $form;
 	}

	/**
	 * Pre config
	 *
	 * @param   array   $values     Form values
	 * @return  array
	 */
	public function preConfig( $values )
	{
		$newValues = array();

		if ( isset( $values['cms_widget_custom_block'] ) )
		{
			$newValues['cms_widget_custom_block'] = $values['cms_widget_custom_block']->key;
		}

		return $newValues;
	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render()
	{
		if ( isset( $this->configuration['cms_widget_custom_block'] ) )
		{
			return (string) \IPS\cms\Blocks\Block::display( $this->configuration['cms_widget_custom_block'], $this->orientation );
		}

		return '';
	}
}
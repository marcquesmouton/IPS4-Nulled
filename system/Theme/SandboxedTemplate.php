<?php
/**
 * @brief		Sameboxed Template
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		18 Feb 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Theme;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Sameboxed Template Class
 */
class _SandboxedTemplate
{
	/**
	 * @brief	Template
	 */
	public $template;
	
	/**
	 * Contructor
	 *
	 * @param	\IPS\Theme\Template	$template
	 * @return	void
	 */
	public function __construct( $template )
	{
		$this->template = $template;
	}
	
	/**
	 * Call
	 *
	 * @return string
	 */
	public function __call( $name, $args )
	{
		if ( !method_exists( $this->template, $name ) )
		{
			return "<span style='background:black;color:white;padding:6px;'>[[Template {$this->template->app}/{$this->template->templateLocation}/{$this->template->templateName}/{$name} does not exist. This theme may be out of date. Run the support tool in the AdminCP to restore the default theme.]]</span>";
		}
		else
		{
			try
			{
				return call_user_func_array( array( $this->template, $name ), $args );
			}
			catch ( \ErrorException $e )
			{
				try
				{
					\IPS\Log::i( \LOG_CRIT )->write( get_class( $e ) . "\n" . $e->getCode() . ": " . $e->getMessage() . "\n" . $e->getTraceAsString(), 'template_error' );
				}
				catch( \Exception $e ) {}
				
				return "<span style='background:black;color:white;padding:6px;'>[[Template {$this->template->app}/{$this->template->templateLocation}/{$this->template->templateName}/{$name} is throwing an error. This theme may be out of date. Run the support tool in the AdminCP to restore the default theme.]]</span>";
			}
		}
	}
}
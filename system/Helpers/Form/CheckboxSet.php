<?php
/**
 * @brief		Checkbox Set class for Form Builder
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		19 Jul 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Helpers\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Checkbox Set class for Form Builder
 */
class _CheckboxSet extends Select
{
	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		$args = func_get_args();		
		$args[3]['multiple'] = TRUE;

		call_user_func_array( 'parent::__construct', $args );
		
		if ( !isset( $this->options['descriptions'] ) )
		{
			$this->options['descriptions'] = array();
		}
	}
	
	/** 
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html()
	{
		/* Get descriptions */
		$descriptions = $this->options['descriptions'];
		if ( $this->options['parse'] === 'lang' )
		{
			foreach ( $this->options['options'] as $k => $v )
			{
				$key = "{$v}_desc";
				if ( \IPS\Member::loggedIn()->language()->checkKeyExists( $key ) )
				{
					$descriptions[ $k ] = \IPS\Member::loggedIn()->language()->addToStack( $key );
				}
			}
		}
		
		/* Translate labels back to keys? */
		if ( $this->options['returnLabels'] )
		{
			$value = array();
			if ( !is_array( $this->value ) )
			{
				$this->value = explode( ',', $this->value );
			}
			foreach ( $this->value as $v )
			{
				$value[] = array_search( $v, $this->options['options'] );
			}
		}
		else
		{
			$value = $this->value;
		}

		if ( ! is_array( $value ) )
		{
			$value = array( $value );
		}
		
		return \IPS\Theme::i()->getTemplate( 'forms', 'core', 'global' )->checkboxset( $this->name, $value, $this->required, $this->parseOptions(), $this->options['multiple'], $this->options['class'], $this->options['disabled'], $this->options['toggles'], NULL, $this->options['unlimited'], $this->options['unlimitedLang'], $this->options['unlimitedToggles'], $this->options['unlimitedToggleOn'], $descriptions );
	}
	
	/**
	 * Get value
	 *
	 * @return	array
	 */
	public function getValue()
	{
		$value = is_array( parent::getValue() ) ? static::getRealArrayValues( parent::getValue() ) : array();
		if ( $this->options['returnLabels'] )
		{
			if ( is_array( $value ) )
			{
				$return = array();
				foreach ( $value as $k => $v )
				{
					$return[ $k ] = $this->options['options'][ $v ];
				}
				return $return;
			}
			else
			{
				return $this->options['options'][ $value ];
			}
		}

		return $value;
	}
	
	/**
	 * Determine if this a numerically indexed array (then we need values) or a manually indexed array (then we need keys)
	 * Ok, look, I know this isn't great. I've been trying to fix this on and off for about 4 days now and to be honest
	 * I'm just relieved it works.
	 * It was either that or set fire to my office and walk into the sea. (MM)
	 *
	 * @note If you touch this Mark, you have to fix it yourself.
	 *
	 * @param  array	$array	A string. LOL. Not really, it's an array	
	 * @return array
	 */
	protected static function getRealArrayValues( $array )
	{
		$count = count( $array );
		$keys  = FALSE;
		
		for( $i = 0; $i < $count; $i++ )
		{
			if ( ! array_key_exists( $i, $array ) )
			{
				$keys = TRUE;
			}
		}
		
		return ( $keys ) ? array_keys( $array ) : array_values( $array );
	}
}
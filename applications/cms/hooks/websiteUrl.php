//<?php

class cms_hook_websiteUrl extends _HOOK_CLASS_
{

/* !Hook Data - DO NOT REMOVE */
public static function hookData() {
 return array_merge_recursive( array (
  'globalTemplate' => 
  array (
    0 => 
    array (
      'selector' => 'html > body > meta[itemprop=\'url\']',
      'type' => 'replace',
      'content' => '{{if \IPS\Settings::i()->cms_use_different_gateway AND \IPS\Settings::i()->cms_root_page_url}}
 <meta itemprop="url" content="{setting="cms_root_page_url"}">
{{else}}
 <meta itemprop="url" content="{setting="base_url"}">
{{endif}}',
    ),
  ),
), parent::hookData() );
}
/* End Hook Data */














}
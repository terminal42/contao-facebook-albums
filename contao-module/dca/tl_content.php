<?php

/**
 * facebook-albums extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2008-2015, terminal42
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    commercial
 */

/**
 * Add palettes to tl_content
 */
$GLOBALS['TL_DCA']['tl_content']['palettes']['facebook_album'] = '{type_legend},type,headline;{source_legend},facebook_album_account,facebook_album,facebook_album_order,metaIgnore,facebook_album_info;{image_legend},size,imagemargin,perRow,fullsize,perPage,numberOfItems;{template_legend:hide},galleryTpl,customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space;{invisible_legend:hide},invisible,start,stop';

/**
 * Add fields to tl_content
 */
$GLOBALS['TL_DCA']['tl_content']['fields']['facebook_album_account'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_content']['facebook_album_account'],
    'exclude'                 => true,
    'inputType'               => 'select',
    'foreignKey'              => 'tl_facebook_album_account.name',
    'eval'                    => array('mandatory'=>true, 'includeBlankOption'=>true, 'submitOnChange'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
    'sql'                     => "int(10) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['facebook_album'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_content']['facebook_album'],
    'exclude'                 => true,
    'inputType'               => 'select',
    'options_callback'        => array('Terminal42\FacebookAlbumsExtension\ContentDca', 'getAlbums'),
    'eval'                    => array('mandatory'=>true, 'includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
    'sql'                     => "varchar(32) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['facebook_album_order'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_content']['facebook_album_order'],
    'default'                 => 'facebook',
    'exclude'                 => true,
    'inputType'               => 'select',
    'options'                 => array('facebook', 'random', 'date_asc', 'date_desc'),
    'reference'               => &$GLOBALS['TL_LANG']['tl_content']['facebook_album_order'],
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => "varchar(16) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['facebook_album_info'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_content']['facebook_album_info'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'clr'),
    'sql'                     => "char(1) NOT NULL default ''"
);

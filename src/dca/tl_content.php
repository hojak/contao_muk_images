<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 */

/**
 * @package newsletter_content
 *
 * @copyright  David Enke 2015
 * @author     David Enke <post@davidenke.de>
 * @package    newsletter_content
 */


/**
 * specialize newsletter_content
 * add optional image to event content type for newsletter elements
 *
 */
if ($this->Input->get('do') == 'newsletter' || (\Input::get('table') == 'tl_content' && \Input::get('field') == 'type')) {

    $GLOBALS['TL_DCA']['tl_content']['palettes']['nl_events'] = str_replace(
			array(
				'{template_legend:hide}',
			),
			array(
				'{image_legend},addImage;{template_legend:hide}',
			),
			$GLOBALS['TL_DCA']['tl_content']['palettes']['nl_events']
		);


}

<?php

namespace MUK\Images;

/**
 * ImageHelper class, provides a generator for special newsletter images
 * */
class ImageHelper extends \Controller {
	
	const TL_CONFIG_PARAMETER = "MUK_IMAGE_TYPES";
	
	/**
	 * standard configuration, override via $GLOBALS['TL_CONFIG']['MUK_IMAGE_TYPES'] = array ( "type" => array ( ... ) );
	 * in the localconfig.php file of your contao installation
	 * 
	 * A configuration consists of the following elements:
	 * join_image:     path to the image, that has to be joined into 
	 * join_position:  top/left position for joining the image as array
	 * join_scale:     target size for the joined image
	 * circle_color:   (css) color of the circle around the original image
     * target_size:    size of the image to generate as array
	 * source_size:    to this size (as array), the original image will be scaled to
	 * crop_left:      pixels to crop from the left side of the generated image
	 * crop_right:     pixels to crop right
	 * crop_top:       pixels to crop top
	 * crop_bottom:    pixels to crop bottom
	 * background:     css color (or 'transparent') for the generated image
	 * file_suffix:    suffix for the generated images file in the cache directory
	 * 
	 **/
	private static $configuration = array (
		"left" => array (
			"join_image"    => 'system/modules/muk_images/assets/img/arrow-right.png', 
			'join_position' => array (206, 10),
			"join_scale"    => array (54,54),
			'circle_color'  => '#ffffff',
			'target_size'   => array (267,267),
			'source_size'   => array (235,235),
			'crop_left'     => 70,
			'crop_right'    => 0,
			'crop_top'      => 0,
			'crop_bottom'   => 0,
			'background'    => 'transparent',
			'file_suffix'   => 'nl',
		),
	);
	
	/**
 	 * get the configuration for the given type 
	 * 
	 * @param string $type 
	 * @return array 
	 */
	private static function getConfig ( $type ) {
		if ( is_array ( $type ) ) {
			return $type;
		} else if ( array_key_exists ( self::TL_CONFIG_PARAMETER, $GLOBALS['TL_CONFIG'])) {
			return @$GLOBALS['TL_CONFIG'][ self::TL_CONFIG_PARAMETER][ $type ];
		} else {
			return @self::$configuration[$type];
		}
	}

	/**
	 * Get an image object for the given file path or uuid and scale it to the given size.
	 * If an important part is set for the image, it will be used.
	 * 
	 * @param string $image  file object, path or uuid 
	 * @param int $width 
	 * @param int $height 
	 * @return string	path of the generated image 
	 */
	private static function getContaoImage ( $image, $width, $height ) {
		if ( is_a ( $image, "Contao\\File")) {
			$image = "" . $image->path;
		} elseif ( \Validator::isUuid( $image ) ) {
			$imgFile = \FilesModel::findByPk ( $image );
		  	$image = $imgFile->path;
		}


		if ( strpos ( $image, " ") !== false ) {
			return array ( null, "<b>Fehler: Der Pfad des Bildes enthält Leerzeichen!</b>");
		} else if ( ! preg_match ( '#^[a-zA-Z0-9/_\\.\\-]+$#i', $image)) {
			return array ( null, "<b>Fehler: Der Pfad des Bildes darf und Buchstaben, Zahlen, - und _ enthalten!</b> " . $image);
		}

		return array ( \Image::get ( $image, $width, $height, "crop" ), null );
	}

	/**
	 * create an image and return a corresponding image tag
	 * 
	 * @param string $image  uid or path 
	 * @param mixed $type	 configuration type for the image to generate  or complete configuration array 
	 * @param array $htmlAttributes	array with attributes for the html tag 
	 * @return  string	html img tag
	 */
	public static function generate ( $image, $type = "left", $htmlAttributes = array () ) {
		$config = self::getConfig ( $type );

		if ( ! $config ) {
			return "<b>Keine Konfiguration für Typ '" . $type. "'!</b>";
		}

		list ( $contaoImage, $errorMsg) = self::getContaoImage ( $image, $config['source_size'][0], $config ['source_size'][1] );
		if( $errorMsg ) return $errorMsg;
		
		$targetPath = $contaoImage . "-" . $config['file_suffix'] . ".png";

		if ( ! $contaoImage ) {
			return "<b>Fehler beim Auslesen des Orginal-Bildes!</b>";
		}


		// create empty image
		$createImage = new \Imagick();
		$createImage->newImage ( $config['target_size'][0], $config['target_size'][1], new \ImagickPixel( $config['background'] ));
		$createImage->setImageFormat ('png');

		
		// draw frame circle
		$drawCircle = new \ImagickDraw ();
		$drawCircle->setStrokeAntialias ( true );
		$drawCircle->setFillColor ( $config['circle_color'] );
		$drawCircle->circle( $config['target_size'][0] / 2, $config['target_size'][1] / 2, $config['target_size'][0]-1, $config['target_size'][1] / 2 );

		$createImage->drawImage ( $drawCircle );

		
		// add cut part from the existing image
		$mask = new \Imagick();
		$mask->newImage ( $config['source_size'][0], $config['source_size'][1], new \ImagickPixel ('transparent') );
		$drawMaskCircle = new \ImagickDraw ();
		$drawMaskCircle ->setStrokeAntialias ( true );
		$drawMaskCircle ->setFillColor ( new \ImagickPixel ('black'));
		$drawMaskCircle ->circle ( $config['source_size'][0] / 2, $config['source_size'][1] /2, $config['source_size'][0] / 2, $config['source_size'][1] -1 );
		$mask->drawImage ( $drawMaskCircle );
		
		$original = new \Imagick ( $contaoImage );
		$original->compositeImage ( $mask, \Imagick::COMPOSITE_COPYOPACITY, 0, 0 );

		$createImage->compositeImage ( 
			$original, 
			\Imagick::COMPOSITE_DEFAULT, 
			($config['target_size'][0] - $config['source_size'][0]) / 2,
			($config['target_size'][1] - $config['source_size'][1]) / 2
		);		

		// add the arrow image
		$joinImage = new \Imagick ( $config['join_image'] );
		if ( $config['join_scale']) {
			$joinImage->scaleImage ( $config['join_scale'][0], $config['join_scale'][1]);
		}
		$createImage->compositeImage ( $joinImage,\Imagick::COMPOSITE_DEFAULT, $config['join_position'][0], $config['join_position'][1]);

		// crop
		$width = $config['target_size'][0] - $config['crop_left'] - $config ['crop_right'];
		$height = $config['target_size'][1] - $config['crop_top'] - $config['crop_bottom'];
		$createImage->cropImage ( $width, $height, $config['crop_left'], $config['crop_top']);

		// store the image in the image cache
		$createImage->writeImage ( $targetPath );
				
		$result = '<img src="' . $targetPath .'"';
		foreach ( $htmlAttributes as $name => $value ) 
			$result .= " " . $name . '="' . $value . '"';
		$result .= "/>";
	
		return $result;	
	}


}

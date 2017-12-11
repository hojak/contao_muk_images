<?php

namespace MUK\Images;

class ImageHelper extends \Controller {
	
	private const $configuration = array (
		"left" => array (
			"join_image"    => 'system/modules/muk_images/assets/img/arrow_right.png',
			'join_position' => array (100, 100),
			'circle_color'  => '',
			'target_size'   => array (),
			'source_size'   => array (),
			'cut_left'      =>  0,
			'cut_right'     =>  0,
			'cut_top'       =>  0,
			'cut_bottom'    =>  0,
			'background'    => 'red',
			'file_suffix'   => 'nl',
		);
	);


	private static fucntion getContaoImage ( $image, $width, $height ) {
		if ( \Validator::isUuid( $image ) ) {
			$imgFile = \FilesModel::findByPk ( $image );
		  	$image = $imgFile->path;
		}

		return \Image::get ( $image, $width, $height, "crop" );
	}



	public static function generate ( $image, $type = "left", $htmlAttributes = array () ) {
		$config = self::$configuration[ $type ];

		if ( ! $config ) {
			return "<b>Keine Konfiguration für Typ '" . $type. "'!</b>";
		}

		$contaoImage = self::getContaoImage ( $image, $config['source_size'][0], $config ['source_size'][1] );
		$targetPath = $contaoImage . "-" . $config['file_suffix'] . ".png";

		if ( ! $contaoImage ) {
			return "<b>Fehler beim Auslesen des Orginal-Bildes!</b>";
		}


		// create empty image
		$createImage = new Imagick();
		$createImage->newImage ( $config['target_size'][0], $config['target_size'][1], new ImagickPixel( $config['background'] );
		$createImage->setImageFormat ('png');


		// draw frame circle
		$drawCircle = new ImagickDraw ();
		$drawCircle->setStrokeAntialias ( true );
		$drawCircle->setFillColor ( $config['circle_color'] );
		$drawCircle->circle(0,0,$config['target_size'][0], $config['target_size'][1] );

		$createImage->drawImage ( $drawCircle );

		
		// add cut part from the existing image
		$mask = new Imagick();
		$mask->newImage ( $config['source_size'][0], $config['source_size'][1], new ImagickPixel ('white') );
		$drawMaskCircle = new ImagickDraw ();
		$drawMaskCircle ->setStrokeAntialias ( true );
		$drawMaskCircle ->setFillColor ( new ImagickPixel ('black'));
		$drawMaskCircle ->circle ( 0, 0, $config['source_size'][0], $config['source_size'][1] );
		$mask->drawImage ( $drawMaskCircle );
		
		$original = new Imagick ( $contaoImage );
		$original->compositeImage ( $mask, Imagick::COMPOSITE_COPYOPACITY, 0, 0 );

		$createImage->compositeImage ( 
			$orignal, 
			Imagick::COMPOSITE_COPYOPACITY, 
			(config['target_size'][0] - $config['source_size'][0]) / 2,
			(config['target_size'][1] - $config['source_size'][1]) / 2
		);


		// add the arrow image
		$joinImage = new Imagick ( $config['join_image'] );
		$createImage->compositeImage ( $joinImage, imagick::COMPOSITE_COPYOPACITY, $config['join_position'][0], $config['join_position'][1]);


		// store the image in the image cache
		$createImage->writeImage ( $targetPath );
		
		$result = '<img src="' . $targetPath .'"';
		foreach ( $htmlAttribtues as $name => $value ) 
			$result .= " " . $name . '="' . $value . '"';
		$result .= "/>";
	
		return $result;	
	}


}

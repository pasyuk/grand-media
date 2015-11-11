<?php
$default_options = array(
	'maxwidth' => '0',
	'lockheight' => '0',
	'height' => '500',
	'maxheight' => '0',
	'autoSlideshow' => '1',
	'slideshowDelay' => '10',
	'thumbnailsWidth' => '100',
	'thumbnailsHeight' => '100',
	'property0' => 'opaque',
	'property1' => 'ffffff',
	'counterStatus' => '1',
	'barBgColor' => '282828',
	'labelColor' => '75c30f',
	'labelColorOver' => 'ffffff',
	'backgroundColorButton' => '000000',
	'descriptionBGColor' => '000000',
	'descriptionBGAlpha' => '75',
	'imageTitleColor' => '75c30f',
	'galleryTitleFontSize' => '15',
	'titleFontSize' => '12',
	'imageDescriptionColor' => 'ffffff',
	'descriptionFontSize' => '12',
	'linkColor' => '75c30f',
	'customCSS' => ''
);
$options_tree = array(
	array(
		'label' => 'Common Settings',
		'fields' => array(
			'maxwidth' => array(
				'label' => 'Max-Width',
				'tag' => 'input',
				'attr' => 'type="number" min="0"',
				'text' => 'Set the maximum width of the gallery. Leave 0 to disable max-width.'
			),
			'lockheight' => array(
				'label' => 'Set height manually',
				'tag' => 'checkbox',
				'attr' => 'data-watch="change"',
				'text' => 'By default a gallery automatically calculates own height to best fit the tallest image in a gallery.'
			),
			'height' => array(
				'label' => 'Height',
				'tag' => 'input',
				'attr' => 'type="text" min="0" data-lockheight="is:1"',
				'text' => 'Set height of the gallery. Do not set % unless you know what you doing.'
			),
			'maxheight' => array(
				'label' => 'Max-Height',
				'tag' => 'input',
				'attr' => 'type="number" min="0" data-lockheight="is:0"',
				'text' => 'Set the maximum height of the gallery. Leave 0 to disable max-height.'
			),
			'autoSlideshow' => array(
				'label' => 'Automatic Slideshow',
				'tag' => 'checkbox',
				'attr' => '',
				'text' => ''
			),
			'slideshowDelay' => array(
				'label' => 'Slideshow Delay',
				'tag' => 'input',
				'attr' => 'type="number" min="1" max="300"',
				'text' => 'Set delay between slides in seconds'
			),
			'thumbnailsWidth' => array(
				'label' => 'Thumbnails Width',
				'tag' => 'input',
				'attr' => 'type="number" min="0" max="300"',
				'text' => 'Set bottom thumbnails width in pixels'
			),
			'thumbnailsHeight' => array(
				'label' => 'Thumbnails Height',
				'tag' => 'input',
				'attr' => 'type="number" min="0" max="300"',
				'text' => 'Set bottom thumbnails height in pixels'
			),
			'property0' => array(
				'label' => 'Wmode for flash object',
				'tag' => 'select',
				'attr' => 'data-watch="change"',
				'text' => 'Default value: Opaque. If \'transparent\' - "Background Color" option is ignored, but you can position the absolute elements over the flash',
				'choices' => array(
					array(
						'label' => 'Opaque',
						'value' => 'opaque'
					),
					array(
						'label' => 'Window',
						'value' => 'window'
					),
					array(
						'label' => 'Transparent',
						'value' => 'transparent'
					)
				)
			),
			'property1' => array(
				'label' => 'Background Color',
				'tag' => 'input',
				'attr' => 'type="text" data-type="color" data-property0="not:transparent"',
				'text' => 'Set gallery background color'
			),
			'counterStatus' => array(
				'label' => 'Show image views/likes counter',
				'tag' => 'checkbox',
				'attr' => '',
				'text' => ''
			),
			'barBgColor' => array(
				'label' => 'Header & Footer Background Color',
				'tag' => 'input',
				'attr' => 'type="text" data-type="color"',
				'text' => ''
			),
			'labelColor' => array(
				'label' => 'Buttons Text Color',
				'tag' => 'input',
				'attr' => 'type="text" data-type="color"',
				'text' => ''
			),
			'labelColorOver' => array(
				'label' => 'Buttons Text Color on MouseOver',
				'tag' => 'input',
				'attr' => 'type="text" data-type="color"',
				'text' => ''
			),
			'backgroundColorButton' => array(
				'label' => 'Buttons BG Color',
				'tag' => 'input',
				'attr' => 'type="text" data-type="color"',
				'text' => ''
			),
			'descriptionBGColor' => array(
				'label' => 'Description BG Color',
				'tag' => 'input',
				'attr' => 'type="text" data-type="color"',
				'text' => 'Background for the image description that appears on mouseover'
			),
			'descriptionBGAlpha' => array(
				'label' => 'Image Description Background Alpha',
				'tag' => 'input',
				'attr' => 'type="number" min="0" max="100" step="5"',
				'text' => 'Opacity of the image description background'
			),
			'imageTitleColor' => array(
				'label' => 'Image Title Color',
				'tag' => 'input',
				'attr' => 'type="text" data-type="color"',
				'text' => 'Color for image title text'
			),
			'galleryTitleFontSize' => array(
				'label' => 'Gallery Title Font Size',
				'tag' => 'input',
				'attr' => 'type="number" min="10" max="30"',
				'text' => ''
			),
			'titleFontSize' => array(
				'label' => 'Image Title Font Size',
				'tag' => 'input',
				'attr' => 'type="number" min="10" max="30"',
				'text' => ''
			),
			'imageDescriptionColor' => array(
				'label' => 'Image Description Color',
				'tag' => 'input',
				'attr' => 'type="text" data-type="color"',
				'text' => 'Color of text for image description'
			),
			'descriptionFontSize' => array(
				'label' => 'Image Description Font Size',
				'tag' => 'input',
				'attr' => 'type="number" min="10" max="30"',
				'text' => 'Value from 10 to 30. Default value: 12'
			),
			'linkColor' => array(
				'label' => 'Link Color (in image description)',
				'tag' => 'input',
				'attr' => 'type="text" data-type="color"',
				'text' => ''
			)
			/*,
			'backButtonTextColor' => array(
				'label' => 'Back Button Text Color',
				'tag' => 'input',
				'attr' => 'type="text" data-type="color"',
				'text' => '(only for Full Window template). Default: ffffff'
			),
			'backButtonBgColor' => array(
				'label' => 'Back Button Background Color',
				'tag' => 'input',
				'attr' => 'type="text" data-type="color"',
				'desc'  => '(only for Full Window template). Default: 000000'
			)*/
		)
	),
	array(
		'label' => 'Advanced Settings',
		'fields' => array(
			'customCSS' => array(
				'label' => 'Custom CSS',
				'tag' => 'textarea',
				'attr' => 'cols="20" rows="10"',
				'text' => 'You can enter custom style rules into this box if you\'d like. IE: <i>a{color: red !important;}</i><br />This is an advanced option! This is not recommended for users not fluent in CSS... but if you do know CSS, anything you add here will override the default styles'
			)
			/*,
			'loveLink' => array(
				'label' => 'Display LoveLink?',
				'tag' => 'checkbox',
				'attr' => '',
				'text' => 'Selecting "Yes" will show the lovelink icon (codeasily.com) somewhere on the gallery'
			)*/
		)
	)
);

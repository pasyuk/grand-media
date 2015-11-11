<?php
$default_options = array(
	'maxwidth' => '0',
	'rating' => '1',
	'autoplay' => '0',
	'buttonText' => 'Download',
	'tracksToShow' => '5',
	'moreText' => 'View More...',
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
				'text' => 'Set the maximum width of the player. Leave 0 to disable max-width.'
			),
			'rating' => array(
				'label' => 'Rating',
				'tag' => 'checkbox',
				'attr' => '',
				'text' => 'Allow visitors to rate tracks'
			),
			'autoplay' => array(
				'label' => 'Autoplay',
				'tag' => 'checkbox',
				'attr' => '',
				'text' => ''
			),
			'buttonText' => array(
				'label' => 'Link Button Text',
				'tag' => 'input',
				'attr' => 'type="text"',
				'text' => 'If gmedia link field is not empty than button with this text will show near track (ex: Open, Buy, Download)'
			),
			'tracksToShow' => array(
				'label' => '# of Tracks to Show',
				'tag' => 'input',
				'attr' => 'type="number" min="-1"',
				'text' => 'Set how many tracks to see on page load. Others be hided and More button shows.'
			),
			'moreText' => array(
				'label' => 'More Button Text',
				'tag' => 'input',
				'attr' => 'type="text"',
				'text' => 'Button to show more tracks.'
			)
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

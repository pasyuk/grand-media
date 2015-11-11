<?php
$default_options = array(
	'width' => '640',
	'tracknumbers' => '1',
	'customCSS' => ''
);
$options_tree = array(
	array(
		'label' => 'Settings',
		'fields' => array(
			'width' => array(
				'label' => 'Width',
				'tag' => 'input',
				'attr' => 'type="number" min="0"',
				'text' => ''
			),
			'tracknumbers' => array(
				'label' => 'Track Numbers',
				'tag' => 'checkbox',
				'attr' => '',
				'text' => ''
			),
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

<?php
$default_options = array(
	'maxheight' => '0',
	'thumbCols' => '0',
	'thumbRows' => '0',
	'thumbsNavigation' => 'scroll',
	'bgColor' => 'ffffff',
	'bgAlpha' => '0',
	'thumbWidth' => '160',
	'thumbHeight' => '120',
	'thumbsSpacing' => '10',
	'thumbsVerticalPadding' => '5',
	'thumbsHorizontalPadding' => '3',
	'thumbsAlign' => 'left',
	'thumbAlpha' => '85',
	'thumbAlphaHover' => '100',
	'thumbBorderSize' => '1',
	'thumbBorderColor' => 'cccccc',
	'thumbPadding' => '5',
	'thumbsInfo' => 'label',
	'tooltipBgColor' => 'ffffff',
	'tooltipStrokeColor' => '000000',
	'tooltipTextColor' => '000000',
	'captionTitleColor' => 'ffffff',
	'captionTextColor' => 'ffffff',
	'lightboxPosition' => 'document',
	'lightboxWindowColor' => '000000',
	'lightboxWindowAlpha' => '80',
	'socialShareEnabled' => '1',
	'initRPdelay' => '100',
	'customCSS' => ''
);
$options_tree = array(
	array(
		'label' => 'Common Settings',
		'fields' => array(
			'maxheight' => array(
				'label' => 'Max-Height',
				'tag' => 'input',
				'attr' => 'type="number" min="0" data-watch="change"',
				'text' => 'Set the maximum height of the gallery. Leave 0 to disable max-height. If value is 0, then Thumbnail Rows value ignored and Thumbnail Columns is a max value'
			),
			'thumbCols' => array(
				'label' => 'Thumbnail Columns',
				'tag' => 'input',
				'attr' => 'type="number" min="0"',
				'text' => 'Number of Columns (number, 0 = auto). Set the number of columns for the grid. If value is 0, then number of columns will be relative to content width or relative to Thumbnail Rows (if rows not auto). This will be ignored if Height value is 0'
			),
			'thumbRows' => array(
				'label' => 'Thumbnail Rows',
				'tag' => 'input',
				'attr' => 'type="number" min="0"',
				'text' => 'Number of Lines (number, 0 = auto). Default value: 0. Set the number of lines for the grid. This will be ignored if Thumbnail Columns value is not 0 or if Height value is 0'
			),
			'thumbsNavigation' => array(
				'label' => 'Grid Navigation',
				'tag' => 'select',
				'attr' => 'data-maxheight="!=:0"',
				'text' => 'Set how you navigate through the thumbnails. Ignore this option if Height value is 0',
				'choices' => array(
					array(
						'label' => 'Mouse Move',
						'value' => 'mouse'
					),
					array(
						'label' => 'Scroll Bars',
						'value' => 'scroll'
					)
				)

			),
			'bgColor' => array(
				'label' => 'Background Color',
				'tag' => 'input',
				'attr' => 'type="text" data-type="color"',
				'text' => 'Set gallery background color'
			),
			'bgAlpha' => array(
				'label' => 'Background Alpha',
				'tag' => 'input',
				'attr' => 'type="number" min="0" max="100" step="5"',
				'text' => 'Set gallery background alpha opacity'
			)
		)
	),
	array(
		'label' => 'Thumb Grid General',
		'fields' => array(
			'thumbWidth' => array(
				'label' => 'Thumbnail Width',
				'tag' => 'input',
				'attr' => 'type="number" min="10" max="400"',
				'text' => ''
			),
			'thumbHeight' => array(
				'label' => 'Thumbnail Height',
				'tag' => 'input',
				'attr' => 'type="number" min="10" max="400"',
				'text' => ''
			),
			'thumbsSpacing' => array(
				'label' => 'Thumbnails Spacing',
				'tag' => 'input',
				'attr' => 'type="number" min="0"',
				'text' => 'Set the space between thumbnails'
			),
			'thumbsVerticalPadding' => array(
				'label' => 'Grid Vertical Padding',
				'tag' => 'input',
				'attr' => 'type="number" min="0"',
				'text' => 'Set the vertical padding for the thumbnails grid'
			),
			'thumbsHorizontalPadding' => array(
				'label' => 'Grid Horizontal Padding',
				'tag' => 'input',
				'attr' => 'type="number" min="0"',
				'text' => 'Set the horizontal padding for the thumbnails grid'
			),
			'thumbsAlign' => array(
				'label' => 'Thumbnails Align',
				'tag' => 'select',
				'attr' => '',
				'text' => 'Align thumbnails grid in container. Applied only if grid width less than gallery width',
				'choices' => array(
					array(
						'label' => 'Left',
						'value' => 'left'
					),
					array(
						'label' => 'Center',
						'value' => 'center'
					),
					array(
						'label' => 'Right',
						'value' => 'right'
					)
				)

			)
		)
	),
	array(
		'label' => 'Thumbnail Style',
		'fields' => array(
			'thumbAlpha' => array(
				'label' => 'Thumbnail Alpha',
				'tag' => 'input',
				'attr' => 'type="number" min="0" max="100" step="5"',
				'text' => 'Set the transparency of a thumbnail'
			),
			'thumbAlphaHover' => array(
				'label' => 'Thumbnail Alpha Hover',
				'tag' => 'input',
				'attr' => 'type="number" min="0" max="100" step="5"',
				'text' => 'Set the transparancy of a thumbnail when hover'
			),
			'thumbBorderSize' => array(
				'label' => 'Thumbnail Border Size',
				'tag' => 'input',
				'attr' => 'type="number" min="0"',
				'text' => 'Set border size for thumbnail'
			),
			'thumbBorderColor' => array(
				'label' => 'Thumbnail Border Color',
				'tag' => 'input',
				'attr' => 'type="text" data-type="color"',
				'text' => 'Set the color of a thumbnail\'s border'
			),
			'thumbPadding' => array(
				'label' => 'Thumbnail Padding',
				'tag' => 'input',
				'attr' => 'type="number" min="0"',
				'text' => 'Set padding for the thumbnail'
			)
		)
	),
	array(
		'label' => 'Thumbnails Title',
		'fields' => array(
			'thumbsInfo' => array(
				'label' => 'Display Thumbnails Title',
				'tag' => 'select',
				'attr' => ' data-watch="change"',
				'text' => 'Default value: Label. Display a small info text on the thumbnails, a tooltip or a label.',
				'choices' => array(
					array(
						'label' => 'Label',
						'value' => 'label'
					),
					array(
						'label' => 'Tooltip',
						'value' => 'tooltip'
					),
					array(
						'label' => 'None',
						'value' => 'none'
					)
				)

			),
			'tooltipBgColor' => array(
				'label' => 'Tooltip Background Color',
				'tag' => 'input',
				'attr' => 'type="text" data-type="color" data-thumbsinfo="is:tooltip"',
				'text' => 'Set tooltip background color. Ignore this if Display Thumbnails Title value is not Tooltip'
			),
			'tooltipStrokeColor' => array(
				'label' => 'Tooltip Stroke Color',
				'tag' => 'input',
				'attr' => 'type="text" data-type="color" data-thumbsinfo="is:tooltip"',
				'text' => 'Set tooltip stroke color. Ignore this if Display Thumbnails Title value is not Tooltip'
			),
			'tooltipTextColor' => array(
				'label' => 'Tooltip Text Color',
				'tag' => 'input',
				'attr' => 'type="text" data-type="color" data-thumbsinfo="is:tooltip"',
				'text' => 'Set tooltip text color. Ignore this if Display Thumbnails Title value is not Tooltip'
			)
		)
	),
	array(
		'label' => 'Lightbox Settings',
		'fields' => array(
			'lightboxPosition' => array(
				'label' => 'Lightbox Position',
				'tag' => 'select',
				'attr' => '',
				'text' => 'If the value is Document the lightbox is displayed over the web page fitting in the browser\'s window, else the lightbox is displayed in the gallery\'s container',
				'choices' => array(
					array(
						'label' => 'Document',
						'value' => 'document'
					),
					array(
						'label' => 'Gallery',
						'value' => 'gallery'
					)
				)
			),
			'captionTitleColor' => array(
				'label' => 'Lightbox Image Title Color',
				'tag' => 'input',
				'attr' => 'type="text" data-type="color"',
				'text' => 'Set the text color for image title'
			),
			'captionTextColor' => array(
				'label' => 'Lightbox Image Description Color',
				'tag' => 'input',
				'attr' => 'type="text" data-type="color"',
				'text' => 'Set the text color for image caption'
			),
			'lightboxWindowColor' => array(
				'label' => 'Lightbox Window Color',
				'tag' => 'input',
				'attr' => 'type="text" data-type="color"',
				'text' => 'Set the background color for the lightbox window'
			),
			'lightboxWindowAlpha' => array(
				'label' => 'Lightbox Window Alpha',
				'tag' => 'input',
				'attr' => 'type="number" min="0" max="100" step="5"',
				'text' => 'Set the transparancy for the lightbox window'
			),
			'socialShareEnabled' => array(
				'label' => 'Social Share',
				'tag' => 'checkbox',
				'attr' => '',
				'text' => 'Enable AddThis Social Share?'
			)
		)
	),
	array(
		'label' => 'Advanced Settings',
		'fields' => array(
			'initRPdelay' => array(
				'label' => 'Delay for Thumbnail Positioning',
				'tag' => 'input',
				'attr' => 'type="number" min="0" max="5000" step="1"',
				'text' => 'Set delay in miliseconds. Set more if gallery render wrong grid.'
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

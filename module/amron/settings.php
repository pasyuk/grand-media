<?php
$default_options = array(
	//Main layout
	'collectionThumbRecomendedWidth'          => '260',
	'collectionPreloaderColor'                => '333333',
	'linkTargetWindow'                        => '_blank',
	'thumbSpacing'                            => '10',
	//Tags Cloud
	'tagsFilter'                              => '1',
	'tagCloudAll'                             => 'All',
	'tagCloudTextColor'                       => 'rgba(0, 0, 0, 1)',
	'tagCloudTextColorOver'                   => 'rgba(255,255, 255, 1)',
	'tagCloudBgColor'                         => 'rgba(211, 211, 211, 1)',
	'tagCloudBgColorOver'                     => 'rgba(11, 11, 11, 1)',
	//Thumbnails
	'collectionThumbHoverColor'               => 'rgba(0, 0, 0, .7)',
	'collectionThumbContentBGColor'           => 'rgba(245,245,245,1)',
	'collectionThumbContentBGColor'           => 'rgba(245,245,245,1)',
	'collectionThumbTitleShow'                => '1',
	'collectionThumbTitleColor'               => 'rgba(0,0,0,1)',
	'collectionThumbFontSize'                 => '18',
	'collectionThumbDescriptionShow'          => '1',
	'collectionThumbDescriptionColor'         => 'rgba(0,0,0,1)',
	'collectionThumbDescriptionFontSize'      => '15',
	'collectionReadMoreButtonLabel'           => 'Read More',
	'collectionReadMoreButtonLabelColor'      => 'rgba(255, 255, 255, 1)',
	'collectionReadMoreButtonBGColor'         => 'rgba(0, 0, 0, 1)',
	'collectionReadMoreButtonLabelColorHover' => 'rgba(0, 0, 0, 1)',
	'collectionReadMoreButtonBGColorHover'    => 'rgba(235,235,235,1)',
	//Modal Window
	'modaBgColor'                             => 'rgba(0,0,0,0.9)',
	'modalInfoBoxBgColor'                     => 'rgba(255,255,255,1)',
	'modalInfoBoxTitleTextColor'              => '000000',
	'modalInfoBoxTextColor'                   => '333333',
	'infoBarCountersEnable'                   => '1',
	'infoBarDateInfoEnable'                   => '1',
	// Slider Page
	'lightBoxEnable'                          => '1',
	'sliderPreloaderColor'                    => 'ffffff',
	'sliderBgColor'                           => 'rgba(0,0,0,0.8)',
	'sliderHeaderFooterBgColor'               => '000000',
	'sliderNavigationColor'                   => 'rgba(0,0,0,1)',
	'sliderNavigationIconColor'               => 'rgba(255,255,255,1)',
	'sliderNavigationColorOver'               => 'rgba(255,255,255,1)',
	'sliderNavigationIconColorOver'           => 'rgba(0,0,0,1)',
	'sliderItemTitleFontSize'                 => '24',
	'sliderItemTitleTextColor'                => 'ffffff',
	'sliderThumbBarEnable'                    => '0',
	'sliderThumbBarHoverColor'                => 'ffffff',
	'sliderThumbSubMenuBackgroundColor'       => 'rgba(0,0,0,1)',
	'sliderThumbSubMenuBackgroundColorOver'   => 'rgba(255,255,255,1)',
	'sliderThumbSubMenuIconColor'             => 'rgba(255,255,255,1)',
	'sliderThumbSubMenuIconHoverColor'        => 'rgba(0,0,0,1)',
	'sliderInfoEnable'                        => '1',
	'sliderItemDownload'                      => '1',
	'sliderItemDiscuss'                       => '1',
	'sliderSocialShareEnabled'                => '1',
	'sliderLikesEnabled'                      => '1',
	// Custom CSS
	'customCSS'                               => '',
);
$options_tree    = array(
	array(
		'label'  => 'Common Settings',
		'fields' => array(
			'collectionThumbRecomendedWidth' => array(
				'label' => 'Thumbnail - desired Width',
				'tag'   => 'input',
				'attr'  => 'type="number" min="100" max="500" data-gridType="is:masonry"',
				'text'  => '',
			),
			'thumbSpacing'                   => array(
				'label' => 'Space between thumbnails',
				'tag'   => 'input',
				'attr'  => 'type="number" min="0" max="100"',
				'text'  => '',
			),
			'collectionPreloaderColor'       => array(
				'label' => 'Preloader Color',
				'tag'   => 'input',
				'attr'  => 'type="text" data-type="color"',
				'text'  => 'Set custom color for gallery',
			),
			'linkTargetWindow'               => array(
				'label'   => 'Link target',
				'tag'     => 'select',
				'attr'    => '',
				'text'    => '',
				'choices' => array(
					array(
						'label' => '_blank',
						'value' => '_blank',
					),
					array(
						'label' => '_self',
						'value' => '_self',
					),
				),
			),
		),
	),
	array(
		'label'  => 'Tags Filter',
		'fields' => array(
			'tagsFilter'            => array(
				'label' => 'Tags Cloud enable',
				'tag'   => 'checkbox',
				'attr'  => 'data-watch="change"',
				'text'  => 'Add tags filter for selected collection',
			),
			'tagCloudAll'           => array(
				'label' => 'Tag ALL - name',
				'tag'   => 'input',
				'attr'  => 'data-tagsFilter="is:1"',
				'text'  => '',
			),
			'tagCloudTextColor'     => array(
				'label' => 'Text color',
				'tag'   => 'input',
				'attr'  => 'type="text" data-type="rgba" data-tagsFilter="is:1"',
				'text'  => 'Tag button',
			),
			'tagCloudTextColorOver' => array(
				'label' => 'Text color (Over)',
				'tag'   => 'input',
				'attr'  => 'type="text" data-type="rgba" data-tagsFilter="is:1"',
				'text'  => 'Tag button',
			),
			'tagCloudBgColor'       => array(
				'label' => 'Background color',
				'tag'   => 'input',
				'attr'  => 'type="text" data-type="rgba" data-tagsFilter="is:1"',
				'text'  => 'Tag button',
			),
			'tagCloudBgColorOver'   => array(
				'label' => 'Background color (Over)',
				'tag'   => 'input',
				'attr'  => 'type="text" data-type="rgba" data-tagsFilter="is:1"',
				'text'  => 'Tag button',
			),
		),
	),
	array(
		'label'  => 'Thumbnails Settings',
		'fields' => array(
			'collectionThumbHoverColor'               => array(
				'label' => 'Hover color',
				'tag'   => 'input',
				'attr'  => 'type="text" data-type="rgba"',
				'text'  => '',
			),
			'collectionThumbContentBGColor'           => array(
				'label' => 'Description bar background color',
				'tag'   => 'input',
				'attr'  => 'type="text" data-type="rgba"',
				'text'  => '',
			),
			'collectionThumbTitleShow'                => array(
				'label' => 'Title',
				'tag'   => 'checkbox',
				'attr'  => 'data-watch="change"',
				'text'  => '',
			),
			'collectionThumbTitleColor'               => array(
				'label' => 'Title Text color',
				'tag'   => 'input',
				'attr'  => 'type="text" data-type="rgba" data-collectionThumbTitleShow="is:1"',
				'text'  => '',
			),
			'collectionThumbFontSize'                 => array(
				'label' => 'Title Font size',
				'tag'   => 'input',
				'attr'  => 'type="number" min="11" max="24" step="1" data-collectionThumbTitleShow="is:1"',
				'text'  => '',
			),
			'collectionThumbDescriptionShow'          => array(
				'label' => 'Item Description',
				'tag'   => 'checkbox',
				'attr'  => 'data-watch="change"',
				'text'  => '',
			),
			'collectionThumbDescriptionColor'         => array(
				'label' => 'Description Text color',
				'tag'   => 'input',
				'attr'  => 'type="text" data-type="rgba" data-collectionThumbDescriptionShow="is:1"',
				'text'  => '',
			),
			'collectionThumbDescriptionFontSize'      => array(
				'label' => 'Description Font size',
				'tag'   => 'input',
				'attr'  => 'type="number" min="11" max="24" step="1" data-collectionThumbDescriptionShow="is:1"',
				'text'  => '',
			),
			'collectionReadMoreButtonLabel'           => array(
				'label' => 'Read More button Label Text',
				'tag'   => 'input',
				'attr'  => '',
				'text'  => 'Read More',
			),
			'collectionReadMoreButtonBGColor'         => array(
				'label' => 'Read More button color',
				'tag'   => 'input',
				'attr'  => 'type="text" data-type="rgba"',
				'text'  => '',
			),
			'collectionReadMoreButtonBGColorHover'    => array(
				'label' => 'Read More button Hover color',
				'tag'   => 'input',
				'attr'  => 'type="text" data-type="rgba"',
				'text'  => '',
			),
			'collectionReadMoreButtonLabelColor'      => array(
				'label' => 'Read More button Label color',
				'tag'   => 'input',
				'attr'  => 'type="text" data-type="rgba"',
				'text'  => '',
			),
			'collectionReadMoreButtonLabelColorHover' => array(
				'label' => 'Read More button Label Hover color',
				'tag'   => 'input',
				'attr'  => 'type="text" data-type="rgba"',
				'text'  => '',
			),
		),
	),
	array(
		'label'  => 'Modal Window Settings (Item Info & Share)',
		'fields' => array(
			'modaBgColor'                => array(
				'label' => 'Overlap Color',
				'tag'   => 'input',
				'attr'  => 'type="text" data-type="rgba"',
				'text'  => '',
			),
			'modalInfoBoxBgColor'        => array(
				'label' => 'Info Bar Color',
				'tag'   => 'input',
				'attr'  => 'type="text" data-type="rgba"',
				'text'  => '',
			),
			'modalInfoBoxTitleTextColor' => array(
				'label' => 'Info Bar Title text Color',
				'tag'   => 'input',
				'attr'  => 'type="text" data-type="color"',
				'text'  => '',
			),
			'modalInfoBoxTextColor'      => array(
				'label' => 'Info Bar Text Color',
				'tag'   => 'input',
				'attr'  => 'type="text" data-type="color"',
				'text'  => '',
			),
			'infoBarCountersEnable'      => array(
				'label' => 'Show View/Likes/Comments',
				'tag'   => 'checkbox',
				'attr'  => 'data-watch="change"',
				'text'  => '',
			),
			'infoBarDateInfoEnable'      => array(
				'label' => 'Show item date',
				'tag'   => 'checkbox',
				'attr'  => 'data-watch="change"',
				'text'  => '',
			),
		),
	),
	array(
		'label'  => 'Lightbox Settings',
		'fields' => array(
			'lightBoxEnable'                        => array(
				'label' => 'Lightbox',
				'tag'   => 'checkbox',
				'attr'  => '',
				'text'  => 'Show the item in the Lightbox by clicking on the thumbnail',
			),
			'sliderPreloaderColor'                  => array(
				'label' => 'Preloader Color',
				'tag'   => 'input',
				'attr'  => 'type="text" data-type="color"',
				'text'  => '',
			),
			'sliderBgColor'                         => array(
				'label' => 'Background color',
				'tag'   => 'input',
				'attr'  => 'type="text" data-type="rgba"',
				'text'  => '',
			),
			'sliderHeaderFooterBgColor'             => array(
				'label' => 'Header & Footer background color',
				'tag'   => 'input',
				'attr'  => 'type="text" data-type="color"',
				'text'  => 'Gradient color',
			),
			'sliderNavigationColor'                 => array(
				'label' => 'Navigation button color',
				'tag'   => 'input',
				'attr'  => 'type="text" data-type="rgba"',
				'text'  => '',
			),
			'sliderNavigationColorOver'             => array(
				'label' => 'Navigation button color (over)',
				'tag'   => 'input',
				'attr'  => 'type="text" data-type="rgba"',
				'text'  => '',
			),
			'sliderNavigationIconColor'             => array(
				'label' => 'Navigation button Icons color',
				'tag'   => 'input',
				'attr'  => 'type="text" data-type="rgba"',
				'text'  => '',
			),
			'sliderNavigationIconColorOver'         => array(
				'label' => 'Navigation button Icons color (over)',
				'tag'   => 'input',
				'attr'  => 'type="text" data-type="rgba"',
				'text'  => '',
			),
			'sliderItemTitleFontSize'               => array(
				'label' => 'Item Title - font Size',
				'tag'   => 'input',
				'attr'  => 'type="number" min="11" max="34" step="1"',
				'text'  => '',
			),
			'sliderItemTitleTextColor'              => array(
				'label' => 'Item Title text color',
				'tag'   => 'input',
				'attr'  => 'type="text" data-type="color"',
				'text'  => '',
			),
			/*'sliderThumbBarEnable' => array('label' => 'Show Items Thumbnails',
				'tag' => 'checkbox',
				'attr' => 'data-watch="change"',
				'text' => ''
			),
			'sliderThumbBarHoverColor' => array('label' => 'Thumbnails Border Color (select mode)',
				'tag' => 'input',
				'attr' => 'type="text" data-type="color" data-sliderThumbBarEnable="is:1"',
				'text' => ''
			),*/
			'sliderThumbSubMenuBackgroundColor'     => array(
				'label' => 'Item Submenu Button color',
				'tag'   => 'input',
				'attr'  => 'type="text" data-type="rgba"',
				'text'  => '',
			),
			'sliderThumbSubMenuIconColor'           => array(
				'label' => 'Item Submenu Button Icon color',
				'tag'   => 'input',
				'attr'  => 'type="text" data-type="rgba"',
				'text'  => '',
			),
			'sliderThumbSubMenuBackgroundColorOver' => array(
				'label' => 'Item Submenu Button color (over)',
				'tag'   => 'input',
				'attr'  => 'type="text" data-type="rgba"',
				'text'  => '',
			),
			'sliderThumbSubMenuIconHoverColor'      => array(
				'label' => 'Item Submenu Button Icon color (over)',
				'tag'   => 'input',
				'attr'  => 'type="text" data-type="rgba"',
				'text'  => '',
			),
			'sliderInfoEnable'                      => array(
				'label' => 'Item Info button',
				'tag'   => 'checkbox',
				'attr'  => 'data-watch="change"',
				'text'  => '',
			),
			'sliderItemDownload'                    => array(
				'label' => 'Item Download button',
				'tag'   => 'checkbox',
				'attr'  => 'data-watch="change"',
				'text'  => '',
			),
			'sliderItemDiscuss'                     => array(
				'label' => 'Item Discuss button',
				'tag'   => 'checkbox',
				'attr'  => 'data-watch="change"',
				'text'  => '',
			),
			'sliderSocialShareEnabled'              => array(
				'label' => 'Item Share button',
				'tag'   => 'checkbox',
				'attr'  => 'data-watch="change"',
				'text'  => '',
			),
			'sliderLikesEnabled'                    => array(
				'label' => 'Item Like button',
				'tag'   => 'checkbox',
				'attr'  => 'data-watch="change"',
				'text'  => '',
			),
		),
	),
	array(
		'label'  => 'Advanced Settings',
		'fields' => array(
			'customCSS' => array(
				'label' => 'Custom CSS',
				'tag'   => 'textarea',
				'attr'  => 'cols="20" rows="10"',
				'text'  => 'You can enter custom style rules into this box if you\'d like. IE: <i>a{color: red !important;}</i>
                                                                      <br />This is an advanced option! This is not recommended for users not fluent in CSS... but if you do know CSS, 
                                                                      anything you add here will override the default styles',
			)
			/*,
			'loveLink' => array(
				'label' => 'Display LoveLink?',
				'tag' => 'checkbox',
				'attr' => '',
				'text' => 'Selecting "Yes" will show the lovelink icon (codeasily.com) somewhere on the gallery'
			)*/
		),
	),
);

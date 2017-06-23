<?php
$default_options = array(
    'maxwidth'              => '0',
    'lockheight'            => '1',
    'height'                => '500',
    'maxheight'             => '0',
    'autoSlideshow'         => '1',
    'slideshowDelay'        => '10',
    'thumbnailsWidth'       => '100',
    'thumbnailsHeight'      => '100',
    'property0'             => 'opaque',
    'property1'             => 'ffffff',
    'counterStatus'         => '1',
    'barBgColor'            => '282828',
    'labelColor'            => '75c30f',
    'labelColorOver'        => 'ffffff',
    'backgroundColorButton' => '000000',
    'descriptionBGColor'    => '000000',
    'descriptionBGAlpha'    => '75',
    'imageTitleColor'       => '75c30f',
    'galleryTitleFontSize'  => '15',
    'titleFontSize'         => '12',
    'imageDescriptionColor' => 'ffffff',
    'descriptionFontSize'   => '12',
    'linkColor'             => '75c30f',
    'customCSS'             => ''
);
$options_tree    = array(
    array(
        'label'  => __('Common Settings', 'grand-media'),
        'fields' => array(
            'maxwidth'              => array(
                'label' => __('Max-Width', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="0"',
                'text'  => __('Set the maximum width of the gallery. Leave 0 to disable max-width.', 'grand-media')
            ),
            'lockheight'            => array(
                'label' => __('Set height manually', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => 'data-watch="change"',
                'text'  => __('By default a gallery automatically calculates own height to best fit the tallest image in a gallery.', 'grand-media')
            ),
            'height'                => array(
                'label' => __('Height', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="text" min="0" data-lockheight="is:1"',
                'text'  => __('Set height of the gallery. Do not set % unless you know what you doing.', 'grand-media')
            ),
            'maxheight'             => array(
                'label' => __('Max-Height', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="0" data-lockheight="is:0"',
                'text'  => __('Set the maximum height of the gallery. Leave 0 to disable max-height.', 'grand-media')
            ),
            'autoSlideshow'         => array(
                'label' => __('Automatic Slideshow', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => ''
            ),
            'slideshowDelay'        => array(
                'label' => __('Slideshow Delay', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="1" max="300"',
                'text'  => __('Set delay between slides in seconds', 'grand-media')
            ),
            'thumbnailsWidth'       => array(
                'label' => __('Thumbnails Width', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="0" max="300"',
                'text'  => __('Set bottom thumbnails width in pixels', 'grand-media')
            ),
            'thumbnailsHeight'      => array(
                'label' => __('Thumbnails Height', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="0" max="300"',
                'text'  => __('Set bottom thumbnails height in pixels', 'grand-media')
            ),
            'property0'             => array(
                'label'   => __('Wmode for flash object', 'grand-media'),
                'tag'     => 'select',
                'attr'    => 'data-watch="change"',
                'text'    => __('Default value: Opaque. If \'transparent\' - "Background Color" option is ignored, but you can position the absolute elements over the flash', 'grand-media'),
                'choices' => array(
                    array(
                        'label' => __('Opaque', 'grand-media'),
                        'value' => 'opaque'
                    ),
                    array(
                        'label' => __('Window', 'grand-media'),
                        'value' => 'window'
                    ),
                    array(
                        'label' => __('Transparent', 'grand-media'),
                        'value' => 'transparent'
                    )
                )
            ),
            'property1'             => array(
                'label' => __('Background Color', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color" data-property0="not:transparent"',
                'text'  => __('Set gallery background color', 'grand-media')
            ),
            'counterStatus'         => array(
                'label' => __('Show image views/likes counter', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => ''
            ),
            'barBgColor'            => array(
                'label' => __('Header & Footer Background Color', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color"',
                'text'  => ''
            ),
            'labelColor'            => array(
                'label' => __('Buttons Text Color', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color"',
                'text'  => ''
            ),
            'labelColorOver'        => array(
                'label' => __('Buttons Text Color on MouseOver', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color"',
                'text'  => ''
            ),
            'backgroundColorButton' => array(
                'label' => __('Buttons BG Color', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color"',
                'text'  => ''
            ),
            'descriptionBGColor'    => array(
                'label' => __('Description BG Color', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color"',
                'text'  => __('Background for the image description that appears on mouseover', 'grand-media')
            ),
            'descriptionBGAlpha'    => array(
                'label' => __('Image Description Background Alpha', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="0" max="100" step="5"',
                'text'  => __('Opacity of the image description background', 'grand-media')
            ),
            'imageTitleColor'       => array(
                'label' => __('Image Title Color', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color"',
                'text'  => __('Color for image title text', 'grand-media')
            ),
            'galleryTitleFontSize'  => array(
                'label' => __('Gallery Title Font Size', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="10" max="30"',
                'text'  => ''
            ),
            'titleFontSize'         => array(
                'label' => __('Image Title Font Size', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="10" max="30"',
                'text'  => ''
            ),
            'imageDescriptionColor' => array(
                'label' => __('Image Description Color', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color"',
                'text'  => __('Color of text for image description', 'grand-media')
            ),
            'descriptionFontSize'   => array(
                'label' => __('Image Description Font Size', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="10" max="30"',
                'text'  => __('Value from 10 to 30. Default value: 12', 'grand-media')
            ),
            'linkColor'             => array(
                'label' => __('Link Color (in image description)', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color"',
                'text'  => ''
            )
            /*,
            'backButtonTextColor' => array(
                'label' => __('Back Button Text Color', 'grand-media'),
                'tag' => 'input',
                'attr' => 'type="text" data-type="color"',
                'text' => __('(only for Full Window template). Default: ffffff', 'grand-media')
            ),
            'backButtonBgColor' => array(
                'label' => __('Back Button Background Color', 'grand-media'),
                'tag' => 'input',
                'attr' => 'type="text" data-type="color"',
                'text'  => __('(only for Full Window template). Default: 000000', 'grand-media')
            )*/
        )
    ),
    array(
        'label'  => __('Advanced Settings', 'grand-media'),
        'fields' => array(
            'customCSS' => array(
                'label' => __('Custom CSS', 'grand-media'),
                'tag'   => 'textarea',
                'attr'  => 'cols="20" rows="10"',
                'text'  => __('You can enter custom style rules into this box if you\'d like. IE: <i>a{color: red !important;}</i><br />This is an advanced option! This is not recommended for users not fluent in CSS... but if you do know CSS, anything you add here will override the default styles', 'grand-media')
            )
            /*,
            'loveLink' => array(
                'label' => __('Display LoveLink?', 'grand-media'),
                'tag' => 'checkbox',
                'attr' => '',
                'text' => __('Selecting "Yes" will show the lovelink icon (codeasily.com) somewhere on the gallery', 'grand-media')
            )*/
        )
    )
);

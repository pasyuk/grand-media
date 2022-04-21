<?php
$default_options = array(
    'base_gallery_width'      => '800',
    'base_gallery_height'     => '500',
    'gallery_min_height'      => '230',
    'scale_mode'              => 'fit',
    'initial_slide'           => '0',
    'slideshow_autoplay'      => '0',
    'slideshow_delay'         => '7000',
    'gallery_focus'           => '0',
    'gallery_maximized'       => '0',
    'gallery_focus_maximized' => '0',
    'keyboard_help'           => '1',
    'show_comments'           => '1',
    'show_download_button'    => '1',
    'show_link_button'        => '1',
    'link_button_target'      => '_self',
    'show_description'        => '1',
    'show_author_avatar'      => '1',
    'show_author_name'        => '1',
    'show_share_button'       => '1',
    'show_like_button'        => '1',
    'link_color'              => '0099e5',
    'link_color_hover'        => '02adea',
    'download_button_text'    => __('Download', 'grand-media'),
    'link_button_text'        => __('Open Link', 'grand-media'),
    'comments_button_text'    => __('Discuss', 'grand-media'),
    'description_title'       => __('Description', 'grand-media'),
    'customCSS'               => ''
);
$options_tree    = array(
    array(
        'label'  => __('Common Settings', 'grand-media'),
        'fields' => array(
            'base_gallery_width'   => array(
                'label' => __('Base Width', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="1"',
                'text'  => '',
            ),
            'base_gallery_height'  => array(
                'label' => __('Base Height', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="1"',
                'text'  => __('Slider will autocalculate the ratio based on these values', 'grand-media')
            ),
            'gallery_min_height'   => array(
                'label' => __('Minimal Height', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="230"',
                'text'  => '',
            ),
            'gallery_maximized'    => array(
                'label' => __('Auto Height for Each Slide', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => __('Change slider height on change slide to best fit image in it', 'grand-media')
            ),
            'scale_mode'           => array(
                'label'   => __('Image Scale Mode', 'grand-media'),
                'tag'     => 'select',
                'attr'    => '',
                'text'    => __('Default value: Fit. Note \'Fill\' - can work inproperly on IE browser', 'grand-media'),
                'choices' => array(
                    array(
                        'label' => __('Fit', 'grand-media'),
                        'value' => 'fit'
                    ),
                    array(
                        'label' => __('Fill', 'grand-media'),
                        'value' => 'fill'
                    )
                )
            ),
            'initial_slide'        => array(
                'label' => __('Initial Slide', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="0"',
                'text'  => '',
            ),
            'slideshow_autoplay'   => array(
                'label' => __('Autoplay On Load', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => __('Start slideshow automatically on gallery load', 'grand-media')
            ),
            'slideshow_delay'      => array(
                'label' => __('Slideshow Delay', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="1000"',
                'text'  => __('Delay between change slides in miliseconds', 'grand-media')
            ),
            'show_download_button' => array(
                'label' => __('Show Download Button', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => 'data-watch="change"',
                'text'  => __('Download original file or if custom field with name "download" specified for the item then its value will be used.', 'grand-media')
            ),
            'show_link_button'     => array(
                'label' => __('Show Link Button', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => 'data-watch="change"',
                'text'  => __('Uses link field from the item', 'grand-media')
            ),
            'link_button_target'   => array(
                'label' => __('Link Button Target', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="text" placeholder="_self" data-show_link_button="is:1"',
                'text'  => __('"_self" to open links in same window; "_blank" to open in new tab.', 'grand-media')
            ),
            'show_comments'        => array(
                'label' => __('Show Comments', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => '',
            ),
            'show_description'     => array(
                'label' => __('Show Slide Description', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => 'data-watch="change"',
                'text'  => '',
            ),
            'show_author_avatar'   => array(
                'label' => __('Show Author Avatar', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => '',
            ),
            'show_author_name'   => array(
                'label' => __('Show Author Name', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => '',
            ),
            'show_share_button'    => array(
                'label' => __('Show Share Button', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => '',
            ),
            'show_like_button'     => array(
                'label' => __('Show Like Button', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => '',
            )
        )
    ),
    array(
        'label'  => __('Colors', 'grand-media'),
        'fields' => array(
            'link_color'       => array(
                'label' => __('Links and Buttons Color', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color"',
                'text'  => '',
            ),
            'link_color_hover' => array(
                'label' => __('Links and Buttons Color on Hover', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color"',
                'text'  => '',
            )
        )
    ),
    array(
        'label'  => __('Translate Strings', 'grand-media'),
        'fields' => array(
            'download_button_text' => array(
                'label' => __('Download Button Name', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="text"',
                'text'  => '',
            ),
            'link_button_text'     => array(
                'label' => __('Link Button Name', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="text"',
                'text'  => '',
            ),
            'comments_button_text' => array(
                'label' => __('Comments Button Name', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="text"',
                'text'  => '',
            ),
            'description_title'    => array(
                'label' => __('Slide Description Title', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="text"',
                'text'  => '',
            ),
        )
    ),
    array(
        'label'  => __('Advanced Settings', 'grand-media'),
        'fields' => array(
            'gallery_focus'           => array(
                'label' => __('Full Window Mode on Start', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => '',
            ),
            'gallery_focus_maximized' => array(
                'label' => __('Maximized Full Window Mode', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => '',
            ),
            'keyboard_help'           => array(
                'label' => __('Show Keyboard Help', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => '',
            ),
            'customCSS'               => array(
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

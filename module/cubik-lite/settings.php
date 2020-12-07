<?php
$default_options = array(
    'maxSize'               => '400',
    'thumbCols'             => '4',
    'facePadding'           => '20',
    'faceMargin'            => '20',
    'lightboxControlsColor' => 'ffffff',
    'lightboxTitleColor'    => 'f3f3f3',
    'lightboxTextColor'     => 'f3f3f3',
    'lightboxBGColor'       => '0b0b0b',
    'lightboxBGAlpha'       => '80',
    'sidebarBGColor'        => 'ffffff',
    'socialShareEnabled'    => '1',
    'share_post_link'       => '1',
    'deepLinks'             => '1',
    'lightbox800HideArrows' => '0',
    'commentsEnabled'       => '1',
    'thumb2link'            => '0',
    'show_title'            => '1',
    'show_tags'             => '1',
    'show_categories'       => '1',
    'show_albums'           => '1',
    'customCSS'             => ''
);
$options_tree    = array(
    array(
        'label'  => __('Common Settings', 'grand-media'),
        'fields' => array(
            'maxSize'    => array(
                'label' => __('Max Size of the Cube Side', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="0"',
                'text'  => __('Set the maximum size (width x height) of the gallery. Leave 0 to disable max size (not recommended).', 'grand-media')
            ),
            'thumb2link' => array(
                'label' => __('Thumbnail to Link', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => __('If item have Link, then open Link instead of lightbox. Note: Link also will be available via item Title on the thumbnail\'s label and in the lightbox', 'grand-media')
            ),

        )
    ),
    array(
        'label'  => __('Thumb Grid General', 'grand-media'),
        'fields' => array(
            'thumbCols'   => array(
                'label' => __('Thumbnail Columns', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="1" max="4"',
                'text'  => __('Number of Columns on one cube side (minimum 1, maximum 4) Set the number of columns for the side.', 'grand-media')
            ),
            'facePadding' => array(
                'label' => __('Grid Padding', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="0"',
                'text'  => __('Set the vertical padding for the thumbnails grid', 'grand-media')
            ),
            'faceMargin'  => array(
                'label' => __('Grid Margin', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="0"',
                'text'  => __('Set the horizontal padding for the thumbnails grid', 'grand-media')
            )
        )
    ),
    array(
        'label'  => 'Lightbox Settings',
        'fields' => array(
            'lightboxControlsColor' => array(
                'label' => __('Lightbox Controls / Buttons Color', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color"',
                'text'  => __('Set the color for lightbox control buttons', 'grand-media')
            ),
            'lightboxTitleColor'    => array(
                'label' => __('Lightbox Image Title Color', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color"',
                'text'  => __('Set the text color for image title', 'grand-media')
            ),
            'lightboxTextColor'     => array(
                'label' => __('Lightbox Image Description Color', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color"',
                'text'  => __('Set the text color for image caption', 'grand-media')
            ),
            'lightboxBGColor'       => array(
                'label' => __('Lightbox Window Color', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color"',
                'text'  => __('Set the background color for the lightbox window', 'grand-media')
            ),
            'lightboxBGAlpha'       => array(
                'label' => __('Lightbox Window Alpha', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="0" max="100" step="5"',
                'text'  => __('Set the transparancy for the lightbox window', 'grand-media')
            ),
            'sidebarBGColor'        => array(
                'label' => __('Comments Block BG Color', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color"',
                'text'  => __('Set the background color for the comments block', 'grand-media')
            ),
            'lightbox800HideArrows' => array(
                'label' => __('Hide Arrows when small window', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => __('Hide Arrows if window width less than 800px', 'grand-media')
            ),
            'deepLinks'             => array(
                'label' => __('Deep Links', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => __('Change URL hash in the address bar for each big image', 'grand-media')
            ),
            'commentsEnabled'       => array(
                'label' => __('Show Comments Button and Counter', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => ''
            ),
            'socialShareEnabled'    => array(
                'label' => __('Show Share Button', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => 'data-watch="change"',
                'text'  => ''
            ),
            'share_post_link'       => array(
                'label' => __('Share link to Gmedia Post', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => 'data-socialshareenabled="is:1"',
                'text'  => __('Share link to the individual Gmedia Post instead of to the image in gallery.', 'grand-media')
            ),
            'show_title'            => array(
                'label' => __('Show Title in Lightbox', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => ''
            ),
            'show_tags'             => array(
                'label' => __('Show Tags', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => ''
            ),
            'show_categories'       => array(
                'label' => __('Show Categories', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => ''
            ),
            'show_albums'           => array(
                'label' => __('Show Album', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => ''
            )
        )
    ),
    array(
        'label'  => 'Advanced Settings',
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

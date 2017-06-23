<?php
$default_options = array(
    'maxwidth'      => '0',
    'rating'        => '1',
    'autoplay'      => '0',
    'loop'          => '1',
    'buttonText'    => __('Download', 'grand-media'),
    'downloadTrack' => '0',
    'tracksToShow'  => '5',
    'moreText'      => __('View More...', 'grand-media'),
    'customCSS'     => ''
);
$options_tree    = array(
    array(
        'label'  => __('Common Settings', 'grand-media'),
        'fields' => array(
            'maxwidth'      => array(
                'label' => __('Max-Width', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="0"',
                'text'  => __('Set the maximum width of the player. Leave 0 to disable max-width.', 'grand-media')
            ),
            'rating'        => array(
                'label' => __('Rating', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => __('Allow visitors to rate tracks.', 'grand-media')
            ),
            'autoplay'      => array(
                'label' => __('Autoplay', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => ''
            ),
            'loop'          => array(
                'label' => __('Loop Playback', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => ''
            ),
            'buttonText'    => array(
                'label' => __('Link Button Text', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="text"',
                'text'  => __('If gmedia link field is not empty than button with this text will show near track (ex: Open, Buy, Download).', 'grand-media')
            ),
            'downloadTrack' => array(
                'label' => __('Link to File', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => __('If gmedia link field is empty than Link Button will download original file.', 'grand-media')
            ),
            'tracksToShow'  => array(
                'label' => __('# of Tracks to Show', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="-1"',
                'text'  => __('Set how many tracks to see on page load. Others be hided and More button shows.', 'grand-media')
            ),
            'moreText'      => array(
                'label' => __('More Button Text', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="text"',
                'text'  => __('Button to show more tracks.', 'grand-media')
            )
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
                'label'  => __('Display LoveLink?', 'grand-media'),
                'tag' => 'checkbox',
                'attr' => '',
                'text' => __('Selecting "Yes" will show the lovelink icon (codeasily.com) somewhere on the gallery', 'grand-media')
            )*/
        )
    )
);

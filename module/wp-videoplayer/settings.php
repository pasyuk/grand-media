<?php
$default_options = array(
    'width'        => '640',
    'tracknumbers' => '1',
    'customCSS'    => ''
);
$options_tree    = array(
    array(
        'label'  => __('Settings', 'grand-media'),
        'fields' => array(
            'width'        => array(
                'label' => __('Width', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="0"',
                'text'  => ''
            ),
            'tracknumbers' => array(
                'label' => __('Track Numbers', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => ''
            ),
            'customCSS'    => array(
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

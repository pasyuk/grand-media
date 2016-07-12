<?php
$default_options = array(
    'maxheight'               => '0',
    'thumbCols'               => '0',
    'thumbRows'               => '0',
    'thumbsNavigation'        => 'scroll',
    'bgColor'                 => 'ffffff',
    'bgAlpha'                 => '0',
    'thumbWidth'              => '160',
    'thumbHeight'             => '120',
    'thumbWidthMobile'        => '96',
    'thumbHeightMobile'       => '72',
    'thumbsSpacing'           => '10',
    'thumbsVerticalPadding'   => '5',
    'thumbsHorizontalPadding' => '3',
    'thumbsAlign'             => 'left',
    'thumbScale'              => '1',
    'thumbBG'                 => 'ffffff',
    'thumbAlpha'              => '85',
    'thumbAlphaHover'         => '100',
    'thumbBorderSize'         => '1',
    'thumbBorderColor'        => 'cccccc',
    'thumbPadding'            => '5',
    'thumbsInfo'              => 'label',
    'labelOnHover'            => '1',
    'labelTextColor'          => 'e7e7e7',
    'labelLinkColor'          => 'e7e179',
    'label8TextColor'         => '0b0b0b',
    'label8LinkColor'         => '3695E7',
    'tooltipTextColor'        => '0b0b0b',
    'tooltipBgColor'          => 'ffffff',
    'tooltipStrokeColor'      => '000000',
    'lightboxControlsColor'   => 'ffffff',
    'lightboxTitleColor'      => 'f3f3f3',
    'lightboxTextColor'       => 'f3f3f3',
    'lightboxBGColor'         => '0b0b0b',
    'lightboxBGAlpha'         => '80',
    'commentsBGColor'         => 'ffffff',
    'commentsBGAlpha'         => '80',
    'socialShareEnabled'      => '1',
    'share_post_link'         => '0',
    'deepLinks'               => '1',
    'lightbox800HideArrows'   => '0',
    'viewsEnabled'            => '1',
    'likesEnabled'            => '1',
    'commentsEnabled'         => '1',
    'thumb2link'              => '1',
    'initRPdelay'             => '100',
    'customCSS'               => ''
);
$options_tree    = array(
    array(
        'label'  => 'Common Settings',
        'fields' => array(
            'maxheight'        => array(
                'label' => 'Max-Height',
                'tag'   => 'input',
                'attr'  => 'type="number" min="0" data-watch="change"',
                'text'  => 'Set the maximum height of the gallery. Leave 0 to disable max-height. If value is 0, then Thumbnail Rows value ignored and Thumbnail Columns is a max value'
            ),
            'thumbCols'        => array(
                'label' => 'Thumbnail Columns',
                'tag'   => 'input',
                'attr'  => 'type="number" min="0"',
                'text'  => 'Number of Columns (number, 0 = auto). Set the number of columns for the grid. If value is 0, then number of columns will be relative to content width or relative to Thumbnail Rows (if rows not auto). This will be ignored if Height value is 0'
            ),
            'thumbRows'        => array(
                'label' => 'Thumbnail Rows',
                'tag'   => 'input',
                'attr'  => 'type="number" min="0"',
                'text'  => 'Number of Lines (number, 0 = auto). Default value: 0. Set the number of lines for the grid. This will be ignored if Thumbnail Columns value is not 0 or if Height value is 0'
            ),
            'thumbsNavigation' => array(
                'label'   => 'Grid Navigation',
                'tag'     => 'select',
                'attr'    => 'data-maxheight="!=:0"',
                'text'    => 'Set how you navigate through the thumbnails. Ignore this option if Height value is 0',
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
            'bgColor'          => array(
                'label' => 'Background Color',
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color"',
                'text'  => 'Set gallery background color'
            ),
            'bgAlpha'          => array(
                'label' => 'Background Alpha',
                'tag'   => 'input',
                'attr'  => 'type="number" min="0" max="100" step="5"',
                'text'  => 'Set gallery background alpha opacity'
            ),
            'thumb2link'       => array(
                'label' => 'Thumbnail to Link',
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => 'If item have Link, then open Link instead of lightbox. Note: Link also will be available via item Title on the thumbnail\'s label and in the lightbox'
            ),
            'deepLinks'       => array(
                'label' => 'Deep Links',
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => 'Change URL hash in the address bar for each big image'
            )
        )
    ),
    array(
        'label'  => 'Thumb Grid General',
        'fields' => array(
            'thumbWidth'              => array(
                'label' => 'Thumbnail Width',
                'tag'   => 'input',
                'attr'  => 'type="number" min="10" max="400"',
                'text'  => ''
            ),
            'thumbHeight'             => array(
                'label' => 'Thumbnail Height',
                'tag'   => 'input',
                'attr'  => 'type="number" min="10" max="400"',
                'text'  => ''
            ),
            'thumbWidthMobile'        => array(
                'label' => 'Thumbnail Width Mobile',
                'tag'   => 'input',
                'attr'  => 'type="number" min="10" max="400"',
                'text'  => 'Set width for thumbnail if window width is less than 640px'
            ),
            'thumbHeightMobile'       => array(
                'label' => 'Thumbnail Height Mobile',
                'tag'   => 'input',
                'attr'  => 'type="number" min="10" max="400"',
                'text'  => 'Set height for thumbnail if window width is less than 640px'
            ),
            'thumbsSpacing'           => array(
                'label' => 'Thumbnails Spacing',
                'tag'   => 'input',
                'attr'  => 'type="number" min="0"',
                'text'  => 'Set the space between thumbnails'
            ),
            'thumbsVerticalPadding'   => array(
                'label' => 'Grid Vertical Padding',
                'tag'   => 'input',
                'attr'  => 'type="number" min="0"',
                'text'  => 'Set the vertical padding for the thumbnails grid'
            ),
            'thumbsHorizontalPadding' => array(
                'label' => 'Grid Horizontal Padding',
                'tag'   => 'input',
                'attr'  => 'type="number" min="0"',
                'text'  => 'Set the horizontal padding for the thumbnails grid'
            ),
            'thumbsAlign'             => array(
                'label'   => 'Thumbnails Align',
                'tag'     => 'select',
                'attr'    => '',
                'text'    => 'Align thumbnails grid in container. Applied only if grid width less than gallery width',
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
            ),
            'viewsEnabled'            => array(
                'label' => 'Views Counter',
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => 'Show Views counter?'
            ),
            'likesEnabled'            => array(
                'label' => 'Like Button',
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => 'Enable Like Button?'
            ),
            'commentsEnabled'         => array(
                'label' => 'Comments',
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => 'Enable Comments?'
            )
        )
    ),
    array(
        'label'  => 'Thumbnail Style',
        'fields' => array(
            'thumbScale'       => array(
                'label' => 'Thumbnail Scale on mouseover',
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => ''
            ),
            'thumbBG'          => array(
                'label' => 'Thumbnail Container Background',
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color"',
                'text'  => 'Set empty for transparent background'
            ),
            'thumbAlpha'       => array(
                'label' => 'Thumbnail Alpha',
                'tag'   => 'input',
                'attr'  => 'type="number" min="0" max="100" step="5"',
                'text'  => 'Set the transparency of a thumbnail'
            ),
            'thumbAlphaHover'  => array(
                'label' => 'Thumbnail Alpha Hover',
                'tag'   => 'input',
                'attr'  => 'type="number" min="0" max="100" step="5"',
                'text'  => 'Set the transparancy of a thumbnail when hover'
            ),
            'thumbBorderSize'  => array(
                'label' => 'Thumbnail Border Size',
                'tag'   => 'input',
                'attr'  => 'type="number" min="0"',
                'text'  => 'Set border size for thumbnail'
            ),
            'thumbBorderColor' => array(
                'label' => 'Thumbnail Border Color',
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color"',
                'text'  => 'Set the color of a thumbnail\'s border'
            ),
            'thumbPadding'     => array(
                'label' => 'Thumbnail Padding',
                'tag'   => 'input',
                'attr'  => 'type="number" min="0"',
                'text'  => 'Set padding for the thumbnail'
            )
        )
    ),
    array(
        'label'  => 'Thumbnails Title',
        'fields' => array(
            'thumbsInfo'         => array(
                'label'   => 'Display Thumbnails Title',
                'tag'     => 'select',
                'attr'    => 'data-watch="change"',
                'text'    => 'Default value: Label. Display a small info text on the thumbnails, a tooltip or a label.',
                'choices' => array(
                    array(
                        'label' => 'Label Over Image',
                        'value' => 'label'
                    ),
                    array(
                        'label' => 'Label Under Image',
                        'value' => 'label_bottom'
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
            'labelOnHover'       => array(
                'label' => 'Show Label on Mouseover',
                'tag'   => 'checkbox',
                'attr'  => 'data-thumbsinfo="is:label:0"',
                'text'  => 'Uncheck to show thumbnail\'s label all time'
            ),
            'labelTextColor'     => array(
                'label' => 'Label-Over Text Color',
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color" data-thumbsinfo="is:label"',
                'text'  => 'Set Label-Over text color'
            ),
            'labelLinkColor'     => array(
                'label' => 'Label-Over Link Color',
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color" data-thumbsinfo="is:label"',
                'text'  => 'Set Label-Over link color'
            ),
            'label8TextColor'    => array(
                'label' => 'Label Text Color',
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color" data-thumbsinfo="is:label_bottom"',
                'text'  => 'Set Label text color'
            ),
            'label8LinkColor'    => array(
                'label' => 'Label Link Color',
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color" data-thumbsinfo="is:label_bottom"',
                'text'  => 'Set Label-Bottom link color'
            ),
            'tooltipTextColor'   => array(
                'label' => 'Tooltip Text Color',
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color" data-thumbsinfo="is:tooltip"',
                'text'  => 'Set Tooltip text color'
            ),
            'tooltipBgColor'     => array(
                'label' => 'Tooltip Background Color',
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color" data-thumbsinfo="is:tooltip"',
                'text'  => 'Set tooltip background color. Ignore this if Display Thumbnails Title value is not Tooltip'
            ),
            'tooltipStrokeColor' => array(
                'label' => 'Tooltip Stroke Color',
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color" data-thumbsinfo="is:tooltip"',
                'text'  => 'Set tooltip stroke color. Ignore this if Display Thumbnails Title value is not Tooltip'
            )
        )
    ),
    array(
        'label'  => 'Lightbox Settings',
        'fields' => array(
            'lightboxControlsColor' => array(
                'label' => 'Lightbox Controls / Buttons Color',
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color"',
                'text'  => 'Set the color for lightbox control buttons'
            ),
            'lightboxTitleColor'    => array(
                'label' => 'Lightbox Image Title Color',
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color"',
                'text'  => 'Set the text color for image title'
            ),
            'lightboxTextColor'     => array(
                'label' => 'Lightbox Image Description Color',
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color"',
                'text'  => 'Set the text color for image caption'
            ),
            'lightboxBGColor'       => array(
                'label' => 'Lightbox Window Color',
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color"',
                'text'  => 'Set the background color for the lightbox window'
            ),
            'lightboxBGAlpha'       => array(
                'label' => 'Lightbox Window Alpha',
                'tag'   => 'input',
                'attr'  => 'type="number" min="0" max="100" step="5"',
                'text'  => 'Set the transparancy for the lightbox window'
            ),
            'commentsBGColor'       => array(
                'label' => 'Comments Block BG Color',
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color"',
                'text'  => 'Set the background color for the comments block'
            ),
            'commentsBGAlpha'       => array(
                'label' => 'Comments Block BG Alpha',
                'tag'   => 'input',
                'attr'  => 'type="number" min="0" max="100" step="5"',
                'text'  => 'Set the transparancy for the comments block'
            ),
            'socialShareEnabled'    => array(
                'label' => 'Social Share',
                'tag'   => 'checkbox',
                'attr'  => 'data-watch="change"',
                'text'  => 'Enable AddThis Social Share?'
            ),
            'share_post_link'    => array(
                'label' => 'Share link to Gmedia Post',
                'tag'   => 'checkbox',
                'attr'  => 'data-socialshareenabled="is:1"',
                'text'  => 'Share link to the individual Gmedia Post instead of to the image in gallery.'
            ),
            'lightbox800HideArrows' => array(
                'label' => 'Hide Arrows when small window',
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => 'Hide Arrows if window width less than 800px'
            )
        )
    ),
    array(
        'label'  => 'Advanced Settings',
        'fields' => array(
            'initRPdelay' => array(
                'label' => 'Delay for Thumbnail Positioning',
                'tag'   => 'input',
                'attr'  => 'type="number" min="0" max="5000" step="1"',
                'text'  => 'Set delay in miliseconds. Set more if gallery render wrong grid.'
            ),
            'customCSS'   => array(
                'label' => 'Custom CSS',
                'tag'   => 'textarea',
                'attr'  => 'cols="20" rows="10"',
                'text'  => 'You can enter custom style rules into this box if you\'d like. IE: <i>a{color: red !important;}</i><br />This is an advanced option! This is not recommended for users not fluent in CSS... but if you do know CSS, anything you add here will override the default styles'
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

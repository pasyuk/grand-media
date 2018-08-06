<?php
$default_options = array(
    'per_page'                => '100',
    'maxheight'               => '0',
    'thumbCols'               => '0',
    'thumbRows'               => '0',
    'thumbsNavigation'        => 'scroll',
    'bgColor'                 => 'ffffff',
    'bgAlpha'                 => '0',
    'thumbWidth'              => '200',
    'thumbHeight'             => '180',
    'thumbWidthMobile'        => '150',
    'thumbHeightMobile'       => '135',
    'thumbsSpacing'           => '8',
    'thumbsVerticalPadding'   => '4',
    'thumbsHorizontalPadding' => '4',
    'thumbsAlign'             => 'center',
    'thumbScale'              => '1',
    'thumbBG'                 => 'ffffff',
    'thumbAlpha'              => '90',
    'thumbAlphaHover'         => '100',
    'thumbBorderSize'         => '1',
    'thumbBorderColor'        => 'cccccc',
    'thumbPadding'            => '2',
    'thumbsInfo'              => 'label',
    'labelOnHover'            => '1',
    'labelTextColor'          => 'ffffff',
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
    'socialShareEnabled'      => '1',
    'share_post_link'         => '1',
    'deepLinks'               => '1',
    'sidebarBGColor'          => 'ffffff',
    'lightbox800HideArrows'   => '0',
    'viewsEnabled'            => '1',
    'likesEnabled'            => '1',
    'commentsEnabled'         => '1',
    'thumb2link'              => '0',
    'link_target'             => 'auto',
    'show_title'              => '1',
    'show_tags'               => '1',
    'show_categories'         => '1',
    'show_albums'             => '1',
    'initRPdelay'             => '200',
    'customCSS'               => ''
);
$options_tree    = array(
    array(
        'label'  => __('Common Settings', 'grand-media'),
        'fields' => array(
            'per_page'             => array(
                'label' => __('Items Per Page', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="1"',
                'text'  => __('(ignored if there is "per_page" parameter in the Query Args.)', 'grand-media')
            ),
            'maxheight'            => array(
                'label' => __('Max-Height', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="0" data-watch="change"',
                'text'  => __('Set the maximum height of the gallery. Leave 0 to disable max-height. If value is 0, then Thumbnail Rows value ignored and Thumbnail Columns is a max value', 'grand-media')
            ),
            'thumbCols'            => array(
                'label' => __('Thumbnail Columns', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="0"',
                'text'  => __('Number of Columns (number, 0 = auto). Set the number of columns for the grid. If value is 0, then number of columns will be relative to content width or relative to Thumbnail Rows (if rows not auto). This will be ignored if Height value is 0', 'grand-media')
            ),
            'thumbRows'            => array(
                'label' => __('Thumbnail Rows', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="0"',
                'text'  => __('Number of Lines (number, 0 = auto). Default value: 0. Set the number of lines for the grid. This will be ignored if Thumbnail Columns value is not 0 or if Height value is 0', 'grand-media')
            ),
            'thumbsNavigation'     => array(
                'label'   => __('Grid Navigation', 'grand-media'),
                'tag'     => 'select',
                'attr'    => 'data-maxheight="!=:0"',
                'text'    => __('Set how you navigate through the thumbnails. Ignore this option if Height value is 0', 'grand-media'),
                'choices' => array(
                    array(
                        'label' => __('Mouse Move', 'grand-media'),
                        'value' => 'mouse'
                    ),
                    array(
                        'label' => __('Scroll Bars', 'grand-media'),
                        'value' => 'scroll'
                    )
                )

            ),
            'bgColor'              => array(
                'label' => __('Background Color', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color"',
                'text'  => __('Set gallery background color', 'grand-media')
            ),
            'bgAlpha'              => array(
                'label' => __('Background Alpha', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="0" max="100" step="5"',
                'text'  => __('Set gallery background alpha opacity', 'grand-media')
            ),
            'thumb2link'           => array(
                'label' => __('Thumbnail to Link', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => __('If item have Link, then open Link instead of lightbox. Note: Link also will be available via item Title on the thumbnail\'s label and in the lightbox', 'grand-media')
            ),
            'link_target'          => array(
                'label'   => __('Link Target', 'grand-media'),
                'tag'     => 'select',
                'attr'    => '',
                'text'    => __('"_self" to open links in same window; "_blank" to open in new tab. Could be overwrited via "link_target" custom field', 'grand-media'),
                'choices' => array(
                    array(
                        'label' => __('auto (only external links in new tab)', 'grand-media'),
                        'value' => 'auto'
                    ),
                    array(
                        'label' => __('_self', 'grand-media'),
                        'value' => '_self'
                    ),
                    array(
                        'label' => __('_blank', 'grand-media'),
                        'value' => '_blank'
                    )
                )

            ),
            'deepLinks'            => array(
                'label' => __('Deep Links', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => __('Change URL hash in the address bar for each big image', 'grand-media')
            ),
            'viewsEnabled'         => array(
                'label' => __('Views Counter', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => __('Show Views counter?', 'grand-media')
            ),
            'likesEnabled'         => array(
                'label' => __('Like Button', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => __('Enable Like Button?', 'grand-media')
            ),
            'commentsEnabled'      => array(
                'label' => __('Comments', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => __('Enable Comments?', 'grand-media')
            ),
            'socialShareEnabled'   => array(
                'label' => __('Show Share Button', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => 'data-watch="change"',
                'text'  => ''
            ),
            'share_post_link'      => array(
                'label' => __('Share link to Gmedia Post', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => 'data-socialshareenabled="is:1"',
                'text'  => __('Share link to the individual Gmedia Post instead of to the image in gallery.', 'grand-media')
            ),
            'show_title'           => array(
                'label' => __('Show Title in Lightbox', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => ''
            ),
            'show_tags'            => array(
                'label' => __('Show Tags', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => ''
            ),
            'show_categories'      => array(
                'label' => __('Show Categories', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => ''
            ),
            'show_albums'          => array(
                'label' => __('Show Album', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => ''
            ),
        )
    ),
    array(
        'label'  => __('Thumb Grid General', 'grand-media'),
        'fields' => array(
            'thumbWidth'              => array(
                'label' => __('Thumbnail Width', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="10" max="400"',
                'text'  => ''
            ),
            'thumbHeight'             => array(
                'label' => __('Thumbnail Height', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="10" max="400"',
                'text'  => ''
            ),
            'thumbWidthMobile'        => array(
                'label' => __('Thumbnail Width Mobile', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="10" max="400"',
                'text'  => __('Set width for thumbnail if window width is less than 640px', 'grand-media')
            ),
            'thumbHeightMobile'       => array(
                'label' => __('Thumbnail Height Mobile', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="10" max="400"',
                'text'  => __('Set height for thumbnail if window width is less than 640px', 'grand-media')
            ),
            'thumbsSpacing'           => array(
                'label' => __('Thumbnails Spacing', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="0"',
                'text'  => __('Set the space between thumbnails', 'grand-media')
            ),
            'thumbsVerticalPadding'   => array(
                'label' => __('Grid Vertical Padding', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="0"',
                'text'  => __('Set the vertical padding for the thumbnails grid', 'grand-media')
            ),
            'thumbsHorizontalPadding' => array(
                'label' => __('Grid Horizontal Padding', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="0"',
                'text'  => __('Set the horizontal padding for the thumbnails grid', 'grand-media')
            ),
            'thumbsAlign'             => array(
                'label'   => __('Thumbnails Align', 'grand-media'),
                'tag'     => 'select',
                'attr'    => '',
                'text'    => __('Align thumbnails grid in container. Applied only if grid width less than gallery width', 'grand-media'),
                'choices' => array(
                    array(
                        'label' => __('Left', 'grand-media'),
                        'value' => 'left'
                    ),
                    array(
                        'label' => __('Center', 'grand-media'),
                        'value' => 'center'
                    ),
                    array(
                        'label' => __('Right', 'grand-media'),
                        'value' => 'right'
                    )
                )
            )
        )
    ),
    array(
        'label'  => __('Thumbnail Style', 'grand-media'),
        'fields' => array(
            'thumbScale'       => array(
                'label' => __('Thumbnail Scale on mouseover', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => '',
                'text'  => ''
            ),
            'thumbBG'          => array(
                'label' => __('Thumbnail Container Background', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color"',
                'text'  => __('Set empty for transparent background', 'grand-media')
            ),
            'thumbAlpha'       => array(
                'label' => __('Thumbnail Alpha', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="0" max="100" step="5"',
                'text'  => __('Set the transparency of a thumbnail', 'grand-media')
            ),
            'thumbAlphaHover'  => array(
                'label' => __('Thumbnail Alpha Hover', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="0" max="100" step="5"',
                'text'  => __('Set the transparancy of a thumbnail when hover', 'grand-media')
            ),
            'thumbBorderSize'  => array(
                'label' => __('Thumbnail Border Size', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="0"',
                'text'  => __('Set border size for thumbnail', 'grand-media')
            ),
            'thumbBorderColor' => array(
                'label' => __('Thumbnail Border Color', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color"',
                'text'  => __('Set the color of a thumbnail\'s border', 'grand-media')
            ),
            'thumbPadding'     => array(
                'label' => __('Thumbnail Padding', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="0"',
                'text'  => __('Set padding for the thumbnail', 'grand-media')
            )
        )
    ),
    array(
        'label'  => __('Thumbnails Title', 'grand-media'),
        'fields' => array(
            'thumbsInfo'         => array(
                'label'   => __('Display Thumbnails Title', 'grand-media'),
                'tag'     => 'select',
                'attr'    => 'data-watch="change"',
                'text'    => __('Default value: Label. Display a small info text on the thumbnails, a tooltip or a label.', 'grand-media'),
                'choices' => array(
                    array(
                        'label' => __('Label Over Image', 'grand-media'),
                        'value' => 'label'
                    ),
                    array(
                        'label' => __('Label Under Image', 'grand-media'),
                        'value' => 'label_bottom'
                    ),
                    array(
                        'label' => __('Tooltip', 'grand-media'),
                        'value' => 'tooltip'
                    ),
                    array(
                        'label' => __('None', 'grand-media'),
                        'value' => 'none'
                    )
                )

            ),
            'labelOnHover'       => array(
                'label' => __('Show Label on Mouseover', 'grand-media'),
                'tag'   => 'checkbox',
                'attr'  => 'data-thumbsinfo="is:label:0"',
                'text'  => __('Uncheck to show thumbnail\'s label all time', 'grand-media')
            ),
            'labelTextColor'     => array(
                'label' => __('Label-Over Text Color', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color" data-thumbsinfo="is:label"',
                'text'  => __('Set Label-Over text color', 'grand-media')
            ),
            'labelLinkColor'     => array(
                'label' => __('Label-Over Link Color', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color" data-thumbsinfo="is:label"',
                'text'  => __('Set Label-Over link color', 'grand-media')
            ),
            'label8TextColor'    => array(
                'label' => __('Label Text Color', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color" data-thumbsinfo="is:label_bottom"',
                'text'  => __('Set Label text color', 'grand-media')
            ),
            'label8LinkColor'    => array(
                'label' => __('Label Link Color', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color" data-thumbsinfo="is:label_bottom"',
                'text'  => __('Set Label-Bottom link color', 'grand-media')
            ),
            'tooltipTextColor'   => array(
                'label' => __('Tooltip Text Color', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color" data-thumbsinfo="is:tooltip"',
                'text'  => __('Set Tooltip text color', 'grand-media')
            ),
            'tooltipBgColor'     => array(
                'label' => __('Tooltip Background Color', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color" data-thumbsinfo="is:tooltip"',
                'text'  => __('Set tooltip background color. Ignore this if Display Thumbnails Title value is not Tooltip', 'grand-media')
            ),
            'tooltipStrokeColor' => array(
                'label' => __('Tooltip Stroke Color', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="text" data-type="color" data-thumbsinfo="is:tooltip"',
                'text'  => __('Set tooltip stroke color. Ignore this if Display Thumbnails Title value is not Tooltip', 'grand-media')
            )
        )
    ),
    array(
        'label'  => __('Lightbox Settings', 'grand-media'),
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
            )
        )
    ),
    array(
        'label'  => __('Advanced Settings', 'grand-media'),
        'fields' => array(
            'initRPdelay' => array(
                'label' => __('Delay for Thumbnail Positioning', 'grand-media'),
                'tag'   => 'input',
                'attr'  => 'type="number" min="0" max="5000" step="1"',
                'text'  => __('Set delay in miliseconds. Set more if gallery render wrong grid.', 'grand-media')
            ),
            'customCSS'   => array(
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

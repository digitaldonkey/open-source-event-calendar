<?php
// This file is generated. Do not modify it manually.
return array(
	'classic' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'open-source-event-calendar/osec-calendar-classic',
		'version' => '0.2.0',
		'title' => 'Osec Calendar',
		'category' => 'widgets',
		'icon' => 'calendar-alt',
		'description' => 'Osec classic Bootstrap block.',
		'example' => array(
			
		),
		'supports' => array(
			'html' => false,
			'multiple' => false,
			'align' => array(
				'full',
				'wide'
			),
			'alignWide' => true
		),
		'textdomain' => 'open-source-event-calendar',
		'editorScript' => 'file:./index.js',
		'editorStyle' => array(
			'file:./index.css',
			'dashicons'
		),
		'attributes' => array(
			'view' => array(
				'type' => 'string',
				'default' => 'agenda',
				'enum' => array(
					'month',
					'week',
					'oneday',
					'agenda'
				)
			),
			'fixedDate' => array(
				'type' => 'string',
				'default' => null
			),
			'taxonomies' => array(
				'default' => array(
					
				),
				'type' => 'array'
			),
			'postIds' => array(
				'default' => array(
					
				),
				'type' => 'array'
			),
			'limit' => array(
				'default' => 10,
				'type' => 'integer'
			),
			'limitBy' => array(
				'type' => 'string',
				'default' => 'events',
				'enum' => array(
					'events',
					'days'
				)
			),
			'displayViewSwitch' => array(
				'default' => true,
				'type' => 'boolean'
			),
			'displayDateNavigation' => array(
				'default' => true,
				'type' => 'boolean'
			),
			'displayFilters' => array(
				'default' => true,
				'type' => 'boolean'
			),
			'displaySubscribe' => array(
				'default' => true,
				'type' => 'boolean'
			),
			'displayPrint' => array(
				'default' => true,
				'type' => 'boolean'
			),
			'agendaToggle' => array(
				'default' => false,
				'type' => 'boolean'
			)
		)
	),
	'react-big-calendar' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'open-source-event-calendar/react-big-calendar',
		'version' => '1.0.2',
		'title' => 'Osec Calendar Big Calendar',
		'category' => 'widgets',
		'icon' => 'calendar-alt',
		'description' => 'Osec react-big-calendar block.',
		'example' => array(
			
		),
		'supports' => array(
			'html' => false,
			'multiple' => false,
			'align' => array(
				'full',
				'wide'
			),
			'alignWide' => true
		),
		'textdomain' => 'open-source-event-calendar',
		'editorScript' => 'file:./index.js',
		'editorStyle' => array(
			'file:./index.css',
			'dashicons'
		),
		'viewScript' => 'file:./view.js',
		'style' => 'file:./style-index.css',
		'attributes' => array(
			'view' => array(
				'type' => 'string',
				'default' => 'agenda',
				'enum' => array(
					'month',
					'week',
					'oneday',
					'agenda'
				)
			),
			'fixedDate' => array(
				'type' => 'string',
				'default' => null
			),
			'taxonomies' => array(
				'default' => array(
					
				),
				'type' => 'array'
			),
			'postIds' => array(
				'default' => array(
					
				),
				'type' => 'array'
			),
			'limit' => array(
				'default' => 10,
				'type' => 'integer'
			),
			'limitBy' => array(
				'type' => 'string',
				'default' => 'events',
				'enum' => array(
					'events',
					'days'
				)
			),
			'displayViewSwitch' => array(
				'default' => true,
				'type' => 'boolean'
			),
			'displayDateNavigation' => array(
				'default' => true,
				'type' => 'boolean'
			),
			'displayFilters' => array(
				'default' => true,
				'type' => 'boolean'
			),
			'displaySubscribe' => array(
				'default' => true,
				'type' => 'boolean'
			),
			'displayPrint' => array(
				'default' => true,
				'type' => 'boolean'
			),
			'agendaToggle' => array(
				'default' => false,
				'type' => 'boolean'
			)
		)
	)
);

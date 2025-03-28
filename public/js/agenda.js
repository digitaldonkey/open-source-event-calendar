timely.define(["libs/twig", "agenda"], function (e) {
    var t, n;
    return t = e.twig, n = t({
        id: "../js_src/agenda.twig",
        data: [{
            type: "output",
            stack: [{
                type: "Twig.expression.type.variable",
                value: "navigation",
                match: ["navigation"]
            }, {type: "Twig.expression.type.filter", value: "raw", match: ["| raw", "raw"]}]
        }, {type: "raw", value: '\n\n<div class="ai1ec-agenda-view">\n	'}, {
            type: "logic",
            token: {
                type: "Twig.logic.type.if",
                stack: [{
                    type: "Twig.expression.type.variable",
                    value: "dates",
                    match: ["dates"]
                }, {type: "Twig.expression.type.test", filter: "empty"}],
                output: [{type: "raw", value: '		<p class="ai1ec-no-results">\n			'}, {
                    type: "output",
                    stack: [{
                        type: "Twig.expression.type.variable",
                        value: "text_upcoming_events",
                        match: ["text_upcoming_events"]
                    }]
                }, {type: "raw", value: "\n		</p>\n	"}]
            }
        }, {
            type: "logic", token: {
                type: "Twig.logic.type.else", match: ["else"], output: [{type: "raw", value: "		"}, {
                    type: "logic", token: {
                        type: "Twig.logic.type.for",
                        key_var: "date",
                        value_var: "date_info",
                        expression: [{type: "Twig.expression.type.variable", value: "dates", match: ["dates"]}],
                        output: [{
                            type: "raw",
                            value: '			<div class="ai1ec-date\n				'
                        }, {
                            type: "logic",
                            token: {
                                type: "Twig.logic.type.if",
                                stack: [{
                                    type: "Twig.expression.type.variable",
                                    value: "date_info",
                                    match: ["date_info"]
                                }, {
                                    type: "Twig.expression.type.key.period",
                                    key: "today"
                                }, {type: "Twig.expression.type.test", filter: "empty", modifier: "not"}],
                                output: [{type: "raw", value: "ai1ec-today"}]
                            }
                        }, {
                            type: "raw",
                            value: '">\n				<a class="ai1ec-date-title ai1ec-load-view"\n					href="'
                        }, {
                            type: "output",
                            stack: [{
                                type: "Twig.expression.type.variable",
                                value: "date_info",
                                match: ["date_info"]
                            }, {
                                type: "Twig.expression.type.key.period",
                                key: "href"
                            }, {
                                type: "Twig.expression.type.filter",
                                value: "e",
                                match: ["| e", "e"],
                                params: [{
                                    type: "Twig.expression.type.parameter.start",
                                    value: "(",
                                    match: ["("]
                                }, {
                                    type: "Twig.expression.type.string",
                                    value: "html_attr"
                                }, {
                                    type: "Twig.expression.type.parameter.end",
                                    value: ")",
                                    match: [")"],
                                    expression: !1
                                }]
                            }]
                        }, {type: "raw", value: '"\n					'}, {
                            type: "output",
                            stack: [{
                                type: "Twig.expression.type.variable",
                                value: "data_type",
                                match: ["data_type"]
                            }, {type: "Twig.expression.type.filter", value: "raw", match: ["| raw", "raw"]}]
                        }, {type: "raw", value: '>\n					<div class="ai1ec-month">'}, {
                            type: "output",
                            stack: [{
                                type: "Twig.expression.type.variable",
                                value: "date_info",
                                match: ["date_info"]
                            }, {type: "Twig.expression.type.key.period", key: "month"}]
                        }, {type: "raw", value: '</div>\n					<div class="ai1ec-day">'}, {
                            type: "output",
                            stack: [{
                                type: "Twig.expression.type.variable",
                                value: "date_info",
                                match: ["date_info"]
                            }, {type: "Twig.expression.type.key.period", key: "day"}]
                        }, {
                            type: "raw",
                            value: '</div>\n					<div class="ai1ec-weekday">'
                        }, {
                            type: "output",
                            stack: [{
                                type: "Twig.expression.type.variable",
                                value: "date_info",
                                match: ["date_info"]
                            }, {type: "Twig.expression.type.key.period", key: "weekday"}]
                        }, {type: "raw", value: "</div>\n					"}, {
                            type: "logic",
                            token: {
                                type: "Twig.logic.type.if",
                                stack: [{
                                    type: "Twig.expression.type.variable",
                                    value: "show_year_in_agenda_dates",
                                    match: ["show_year_in_agenda_dates"]
                                }],
                                output: [{
                                    type: "raw",
                                    value: '						<div class="ai1ec-year">'
                                }, {
                                    type: "output",
                                    stack: [{
                                        type: "Twig.expression.type.variable",
                                        value: "date_info",
                                        match: ["date_info"]
                                    }, {type: "Twig.expression.type.key.period", key: "year"}]
                                }, {type: "raw", value: "</div>\n					"}]
                            }
                        }, {
                            type: "raw",
                            value: '				</a>\n				<div class="ai1ec-date-events">\n					'
                        }, {
                            type: "logic", token: {
                                type: "Twig.logic.type.for",
                                key_var: null,
                                value_var: "category",
                                expression: [{
                                    type: "Twig.expression.type.variable",
                                    value: "date_info",
                                    match: ["date_info"]
                                }, {type: "Twig.expression.type.key.period", key: "events"}],
                                output: [{type: "raw", value: "						"}, {
                                    type: "logic", token: {
                                        type: "Twig.logic.type.for",
                                        key_var: null,
                                        value_var: "event",
                                        expression: [{
                                            type: "Twig.expression.type.variable",
                                            value: "category",
                                            match: ["category"]
                                        }],
                                        output: [{
                                            type: "raw",
                                            value: '							<div class="ai1ec-event\n								ai1ec-event-id-'
                                        }, {
                                            type: "output",
                                            stack: [{
                                                type: "Twig.expression.type.variable",
                                                value: "event",
                                                match: ["event"]
                                            }, {type: "Twig.expression.type.key.period", key: "post_id"}]
                                        }, {
                                            type: "raw",
                                            value: "\n								ai1ec-event-instance-id-"
                                        }, {
                                            type: "output",
                                            stack: [{
                                                type: "Twig.expression.type.variable",
                                                value: "event",
                                                match: ["event"]
                                            }, {type: "Twig.expression.type.key.period", key: "instance_id"}]
                                        }, {type: "raw", value: "\n								"}, {
                                            type: "logic",
                                            token: {
                                                type: "Twig.logic.type.if",
                                                stack: [{
                                                    type: "Twig.expression.type.variable",
                                                    value: "event",
                                                    match: ["event"]
                                                }, {type: "Twig.expression.type.key.period", key: "is_allday"}],
                                                output: [{type: "raw", value: "ai1ec-allday"}]
                                            }
                                        }, {type: "raw", value: "								"}, {
                                            type: "logic",
                                            token: {
                                                type: "Twig.logic.type.if",
                                                stack: [{
                                                    type: "Twig.expression.type.variable",
                                                    value: "expanded",
                                                    match: ["expanded"]
                                                }],
                                                output: [{type: "raw", value: "ai1ec-expanded"}]
                                            }
                                        }, {
                                            type: "raw",
                                            value: '"\n								data-end="'
                                        }, {
                                            type: "output",
                                            stack: [{
                                                type: "Twig.expression.type.variable",
                                                value: "event",
                                                match: ["event"]
                                            }, {type: "Twig.expression.type.key.period", key: "end"}]
                                        }, {
                                            type: "raw",
                                            value: '">\n\n								<div class="ai1ec-event-header">\n									<div class="ai1ec-event-toggle">\n										<i class="ai1ec-fa ai1ec-fa-minus-circle ai1ec-fa-lg"></i>\n										<i class="ai1ec-fa ai1ec-fa-plus-circle ai1ec-fa-lg"></i>\n									</div>\n									<span class="ai1ec-event-title">\n										'
                                        }, {
                                            type: "output",
                                            stack: [{
                                                type: "Twig.expression.type.variable",
                                                value: "event",
                                                match: ["event"]
                                            }, {
                                                type: "Twig.expression.type.key.period",
                                                key: "filtered_title"
                                            }, {
                                                type: "Twig.expression.type.filter",
                                                value: "raw",
                                                match: ["| raw", "raw"]
                                            }]
                                        }, {type: "raw", value: "\n										"}, {
                                            type: "logic", token: {
                                                type: "Twig.logic.type.if",
                                                stack: [{
                                                    type: "Twig.expression.type.variable",
                                                    value: "show_location_in_title",
                                                    match: ["show_location_in_title"]
                                                }, {
                                                    type: "Twig.expression.type.variable",
                                                    value: "event",
                                                    match: ["event"]
                                                }, {
                                                    type: "Twig.expression.type.key.period",
                                                    key: "venue"
                                                }, {
                                                    type: "Twig.expression.type.test",
                                                    filter: "empty",
                                                    modifier: "not"
                                                }, {
                                                    type: "Twig.expression.type.operator.binary",
                                                    value: "and",
                                                    precidence: 13,
                                                    associativity: "leftToRight",
                                                    operator: "and"
                                                }],
                                                output: [{
                                                    type: "raw",
                                                    value: '											<span class="ai1ec-event-location"\n												>'
                                                }, {
                                                    type: "output",
                                                    stack: [{
                                                        type: "Twig.expression.type.variable",
                                                        value: "text_venue_separator",
                                                        match: ["text_venue_separator"]
                                                    }, {
                                                        type: "Twig.expression.type.filter",
                                                        value: "format",
                                                        match: ["| format", "format"],
                                                        params: [{
                                                            type: "Twig.expression.type.parameter.start",
                                                            value: "(",
                                                            match: ["("]
                                                        }, {
                                                            type: "Twig.expression.type.variable",
                                                            value: "event",
                                                            match: ["event"]
                                                        }, {
                                                            type: "Twig.expression.type.key.period",
                                                            key: "venue"
                                                        }, {
                                                            type: "Twig.expression.type.parameter.end",
                                                            value: ")",
                                                            match: [")"],
                                                            expression: !1
                                                        }]
                                                    }]
                                                }, {
                                                    type: "raw",
                                                    value: "</span>\n										"
                                                }]
                                            }
                                        }, {
                                            type: "raw",
                                            value: "									</span>\n									"
                                        }, {
                                            type: "output",
                                            stack: [{
                                                type: "Twig.expression.type.variable",
                                                value: "action_buttons",
                                                match: ["action_buttons"]
                                            }, {
                                                type: "Twig.expression.type.filter",
                                                value: "raw",
                                                match: ["| raw", "raw"]
                                            }]
                                        }, {
                                            type: "raw",
                                            value: "\n									"
                                        }, {
                                            type: "logic",
                                            token: {
                                                type: "Twig.logic.type.set",
                                                key: "edit_post_link",
                                                expression: [{
                                                    type: "Twig.expression.type.variable",
                                                    value: "event",
                                                    match: ["event"]
                                                }, {type: "Twig.expression.type.key.period", key: "edit_post_link"}]
                                            }
                                        }, {type: "raw", value: "									"}, {
                                            type: "logic",
                                            token: {
                                                type: "Twig.logic.type.if",
                                                stack: [{
                                                    type: "Twig.expression.type.variable",
                                                    value: "edit_post_link",
                                                    match: ["edit_post_link"]
                                                }, {
                                                    type: "Twig.expression.type.test",
                                                    filter: "empty",
                                                    modifier: "not"
                                                }],
                                                output: [{
                                                    type: "raw",
                                                    value: '										<a class="post-edit-link" href="'
                                                }, {
                                                    type: "output",
                                                    stack: [{
                                                        type: "Twig.expression.type.variable",
                                                        value: "edit_post_link",
                                                        match: ["edit_post_link"]
                                                    }, {
                                                        type: "Twig.expression.type.filter",
                                                        value: "raw",
                                                        match: ["| raw", "raw"]
                                                    }]
                                                }, {
                                                    type: "raw",
                                                    value: '">\n											<i class="ai1ec-fa ai1ec-fa-pencil"></i> '
                                                }, {
                                                    type: "output",
                                                    stack: [{
                                                        type: "Twig.expression.type.variable",
                                                        value: "text_edit",
                                                        match: ["text_edit"]
                                                    }]
                                                }, {
                                                    type: "raw",
                                                    value: "\n										</a>\n									"
                                                }]
                                            }
                                        }, {
                                            type: "raw",
                                            value: '\n									<div class="ai1ec-event-time">\n										 '
                                        }, {
                                            type: "output",
                                            stack: [{
                                                type: "Twig.expression.type.variable",
                                                value: "event",
                                                match: ["event"]
                                            }, {
                                                type: "Twig.expression.type.key.period",
                                                key: "timespan_short"
                                            }, {
                                                type: "Twig.expression.type.filter",
                                                value: "raw",
                                                match: ["| raw", "raw"]
                                            }]
                                        }, {
                                            type: "raw",
                                            value: "\n									</div>\n								</div>\n\n								"
                                        }, {
                                            type: "raw",
                                            value: '\n								<div class="ai1ec-event-summary '
                                        }, {
                                            type: "logic",
                                            token: {
                                                type: "Twig.logic.type.if",
                                                stack: [{
                                                    type: "Twig.expression.type.variable",
                                                    value: "expanded",
                                                    match: ["expanded"]
                                                }],
                                                output: [{type: "raw", value: "ai1ec-expanded"}]
                                            }
                                        }, {
                                            type: "raw",
                                            value: '">\n\n									<div class="ai1ec-event-description">\n										'
                                        }, {
                                            type: "logic", token: {
                                                type: "Twig.logic.type.if",
                                                stack: [{
                                                    type: "Twig.expression.type.variable",
                                                    value: "event",
                                                    match: ["event"]
                                                }, {
                                                    type: "Twig.expression.type.key.period",
                                                    key: "content_img_url"
                                                }, {
                                                    type: "Twig.expression.type.test",
                                                    filter: "empty"
                                                }, {
                                                    type: "Twig.expression.type.variable",
                                                    value: "event",
                                                    match: ["event"]
                                                }, {
                                                    type: "Twig.expression.type.key.period",
                                                    key: "avatar_not_wrapped"
                                                }, {
                                                    type: "Twig.expression.type.test",
                                                    filter: "empty",
                                                    modifier: "not"
                                                }, {
                                                    type: "Twig.expression.type.operator.binary",
                                                    value: "and",
                                                    precidence: 13,
                                                    associativity: "leftToRight",
                                                    operator: "and"
                                                }],
                                                output: [{
                                                    type: "raw",
                                                    value: '											<a class="ai1ec-load-event"\n												href="'
                                                }, {
                                                    type: "output",
                                                    stack: [{
                                                        type: "Twig.expression.type.variable",
                                                        value: "event",
                                                        match: ["event"]
                                                    }, {
                                                        type: "Twig.expression.type.key.period",
                                                        key: "permalink"
                                                    }, {
                                                        type: "Twig.expression.type.filter",
                                                        value: "e",
                                                        match: ["| e", "e"],
                                                        params: [{
                                                            type: "Twig.expression.type.parameter.start",
                                                            value: "(",
                                                            match: ["("]
                                                        }, {
                                                            type: "Twig.expression.type.string",
                                                            value: "html_attr"
                                                        }, {
                                                            type: "Twig.expression.type.parameter.end",
                                                            value: ")",
                                                            match: [")"],
                                                            expression: !1
                                                        }]
                                                    }]
                                                }, {
                                                    type: "raw",
                                                    value: '">\n												'
                                                }, {
                                                    type: "output",
                                                    stack: [{
                                                        type: "Twig.expression.type.variable",
                                                        value: "event",
                                                        match: ["event"]
                                                    }, {
                                                        type: "Twig.expression.type.key.period",
                                                        key: "avatar_not_wrapped"
                                                    }, {
                                                        type: "Twig.expression.type.filter",
                                                        value: "raw",
                                                        match: ["| raw", "raw"]
                                                    }]
                                                }, {
                                                    type: "raw",
                                                    value: "\n											</a>\n										"
                                                }]
                                            }
                                        }, {
                                            type: "raw",
                                            value: "										"
                                        }, {
                                            type: "output",
                                            stack: [{
                                                type: "Twig.expression.type.variable",
                                                value: "event",
                                                match: ["event"]
                                            }, {
                                                type: "Twig.expression.type.key.period",
                                                key: "filtered_content"
                                            }, {
                                                type: "Twig.expression.type.filter",
                                                value: "raw",
                                                match: ["| raw", "raw"]
                                            }]
                                        }, {
                                            type: "raw",
                                            value: '\n									</div>\n\n									<div class="ai1ec-event-summary-footer">\n										<div class="ai1ec-btn-group ai1ec-actions">\n											'
                                        }, {
                                            type: "logic", token: {
                                                type: "Twig.logic.type.if",
                                                stack: [{
                                                    type: "Twig.expression.type.variable",
                                                    value: "is_ticket_button_enabled",
                                                    match: ["is_ticket_button_enabled"]
                                                }, {
                                                    type: "Twig.expression.type.variable",
                                                    value: "event",
                                                    match: ["event"]
                                                }, {
                                                    type: "Twig.expression.type.key.period",
                                                    key: "ticket_url"
                                                }, {
                                                    type: "Twig.expression.type.test",
                                                    filter: "empty",
                                                    modifier: "not"
                                                }, {
                                                    type: "Twig.expression.type.operator.binary",
                                                    value: "and",
                                                    precidence: 13,
                                                    associativity: "leftToRight",
                                                    operator: "and"
                                                }],
                                                output: [{
                                                    type: "raw",
                                                    value: '												<a class="ai1ec-pull-right ai1ec-btn ai1ec-btn-primary\n														ai1ec-btn-xs ai1ec-buy-tickets"\n													target="_blank"\n													href="'
                                                }, {
                                                    type: "output",
                                                    stack: [{
                                                        type: "Twig.expression.type.variable",
                                                        value: "event",
                                                        match: ["event"]
                                                    }, {
                                                        type: "Twig.expression.type.key.period",
                                                        key: "ticket_url"
                                                    }, {
                                                        type: "Twig.expression.type.filter",
                                                        value: "e",
                                                        match: ["| e", "e"],
                                                        params: [{
                                                            type: "Twig.expression.type.parameter.start",
                                                            value: "(",
                                                            match: ["("]
                                                        }, {
                                                            type: "Twig.expression.type.string",
                                                            value: "html_attr"
                                                        }, {
                                                            type: "Twig.expression.type.parameter.end",
                                                            value: ")",
                                                            match: [")"],
                                                            expression: !1
                                                        }]
                                                    }]
                                                }, {
                                                    type: "raw",
                                                    value: '"\n													>'
                                                }, {
                                                    type: "output",
                                                    stack: [{
                                                        type: "Twig.expression.type.variable",
                                                        value: "event",
                                                        match: ["event"]
                                                    }, {
                                                        type: "Twig.expression.type.key.period",
                                                        key: "ticket_url_label"
                                                    }]
                                                }, {
                                                    type: "raw",
                                                    value: "</a>\n											"
                                                }]
                                            }
                                        }, {
                                            type: "raw",
                                            value: '											<a class="ai1ec-read-more ai1ec-btn ai1ec-btn-default\n												ai1ec-load-event"\n												href="'
                                        }, {
                                            type: "output",
                                            stack: [{
                                                type: "Twig.expression.type.variable",
                                                value: "event",
                                                match: ["event"]
                                            }, {
                                                type: "Twig.expression.type.key.period",
                                                key: "permalink"
                                            }, {
                                                type: "Twig.expression.type.filter",
                                                value: "e",
                                                match: ["| e", "e"],
                                                params: [{
                                                    type: "Twig.expression.type.parameter.start",
                                                    value: "(",
                                                    match: ["("]
                                                }, {
                                                    type: "Twig.expression.type.string",
                                                    value: "html_attr"
                                                }, {
                                                    type: "Twig.expression.type.parameter.end",
                                                    value: ")",
                                                    match: [")"],
                                                    expression: !1
                                                }]
                                            }]
                                        }, {
                                            type: "raw",
                                            value: '">\n												'
                                        }, {
                                            type: "output",
                                            stack: [{
                                                type: "Twig.expression.type.variable",
                                                value: "text_read_more",
                                                match: ["text_read_more"]
                                            }]
                                        }, {
                                            type: "raw",
                                            value: ' <i class="ai1ec-fa ai1ec-fa-arrow-right"></i>\n											</a>\n										</div>\n										'
                                        }, {
                                            type: "logic",
                                            token: {
                                                type: "Twig.logic.type.set",
                                                key: "categories",
                                                expression: [{
                                                    type: "Twig.expression.type.variable",
                                                    value: "event",
                                                    match: ["event"]
                                                }, {type: "Twig.expression.type.key.period", key: "categories_html"}]
                                            }
                                        }, {
                                            type: "raw",
                                            value: "										"
                                        }, {
                                            type: "logic",
                                            token: {
                                                type: "Twig.logic.type.set",
                                                key: "tags",
                                                expression: [{
                                                    type: "Twig.expression.type.variable",
                                                    value: "event",
                                                    match: ["event"]
                                                }, {type: "Twig.expression.type.key.period", key: "tags_html"}]
                                            }
                                        }, {
                                            type: "raw",
                                            value: "										"
                                        }, {
                                            type: "logic",
                                            token: {
                                                type: "Twig.logic.type.if",
                                                stack: [{
                                                    type: "Twig.expression.type.variable",
                                                    value: "categories",
                                                    match: ["categories"]
                                                }, {
                                                    type: "Twig.expression.type.test",
                                                    filter: "empty",
                                                    modifier: "not"
                                                }],
                                                output: [{
                                                    type: "raw",
                                                    value: '											<span class="ai1ec-categories">\n												<span class="ai1ec-field-label">\n													<i class="ai1ec-fa ai1ec-fa-folder-open"></i>\n													'
                                                }, {
                                                    type: "output",
                                                    stack: [{
                                                        type: "Twig.expression.type.variable",
                                                        value: "text_categories",
                                                        match: ["text_categories"]
                                                    }]
                                                }, {
                                                    type: "raw",
                                                    value: "\n												</span>\n												"
                                                }, {
                                                    type: "output",
                                                    stack: [{
                                                        type: "Twig.expression.type.variable",
                                                        value: "categories",
                                                        match: ["categories"]
                                                    }, {
                                                        type: "Twig.expression.type.filter",
                                                        value: "raw",
                                                        match: ["| raw", "raw"]
                                                    }]
                                                }, {
                                                    type: "raw",
                                                    value: "\n											</span>\n										"
                                                }]
                                            }
                                        }, {
                                            type: "raw",
                                            value: "										"
                                        }, {
                                            type: "logic",
                                            token: {
                                                type: "Twig.logic.type.if",
                                                stack: [{
                                                    type: "Twig.expression.type.variable",
                                                    value: "tags",
                                                    match: ["tags"]
                                                }, {
                                                    type: "Twig.expression.type.test",
                                                    filter: "empty",
                                                    modifier: "not"
                                                }],
                                                output: [{
                                                    type: "raw",
                                                    value: '											<span class="ai1ec-tags">\n												<span class="ai1ec-field-label">\n													<i class="ai1ec-fa ai1ec-fa-tags"></i>\n													'
                                                }, {
                                                    type: "output",
                                                    stack: [{
                                                        type: "Twig.expression.type.variable",
                                                        value: "text_tags",
                                                        match: ["text_tags"]
                                                    }]
                                                }, {
                                                    type: "raw",
                                                    value: "\n												</span>\n												"
                                                }, {
                                                    type: "output",
                                                    stack: [{
                                                        type: "Twig.expression.type.variable",
                                                        value: "tags",
                                                        match: ["tags"]
                                                    }, {
                                                        type: "Twig.expression.type.filter",
                                                        value: "raw",
                                                        match: ["| raw", "raw"]
                                                    }]
                                                }, {
                                                    type: "raw",
                                                    value: "\n											</span>\n										"
                                                }]
                                            }
                                        }, {
                                            type: "raw",
                                            value: "									</div>\n								</div>\n\n							</div>\n						"
                                        }]
                                    }
                                }, {type: "raw", value: " "}, {type: "raw", value: "\n					"}]
                            }
                        }, {type: "raw", value: " "}, {
                            type: "raw",
                            value: "\n				</div>\n			</div>\n		"
                        }]
                    }
                }, {type: "raw", value: " "}, {type: "raw", value: "\n	"}]
            }
        }, {type: "raw", value: " "}, {
            type: "raw",
            value: '\n</div>\n\n<div class="ai1ec-pull-left">'
        }, {
            type: "output",
            stack: [{
                type: "Twig.expression.type.variable",
                value: "pagination_links",
                match: ["pagination_links"]
            }, {type: "Twig.expression.type.filter", value: "raw", match: ["| raw", "raw"]}]
        }, {type: "raw", value: "</div>\n"}],
        precompiled: !0
    }), n
});

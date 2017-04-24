localizator.grid.Content = function (config) {
    config = config || {};
    if (!config.id) {
        config.id = 'localizator-grid-content';
    }

    Ext.applyIf(config, {
        url: localizator.config.connector_url,
        fields: this.getFields(config),
        columns: this.getColumns(config),
        tbar: this.getTopBar(config),
        sm: new Ext.grid.CheckboxSelectionModel(),
        baseParams: {
            action: 'mgr/content/getlist',
			resource_id: config.resource_id,
        },
        listeners: {
            rowDblClick: function (grid, rowIndex, e) {
                var row = grid.store.getAt(rowIndex);
                this.updateItem(grid, e, row);
            }
        },
        viewConfig: {
            forceFit: true,
            enableRowBody: true,
            autoFill: true,
            showPreview: true,
            scrollOffset: 0,
            getRowClass: function (rec) {
                return !rec.data.active
                    ? 'localizator-grid-row-disabled'
                    : '';
            }
        },
        paging: true,
        remoteSort: true,
        autoHeight: true,
    });
    localizator.grid.Content.superclass.constructor.call(this, config);

    // Clear selection on grid refresh
    this.store.on('load', function () {
        if (this._getSelectedIds().length) {
            this.getSelectionModel().clearSelections();
        }
    }, this);
};
Ext.extend(localizator.grid.Content, MODx.grid.Grid, {
    windows: {},

    getMenu: function (grid, rowIndex) {
        var ids = this._getSelectedIds();

        var row = grid.getStore().getAt(rowIndex);
        var menu = localizator.utils.getMenu(row.data['actions'], this, ids);

        this.addContextMenuItem(menu);
    },

	translateItem: function (btn, e) {
        Ext.MessageBox.confirm(
            _('localizator_translate'),
            _('localizator_translate_confirm'),
            function (val) {
                if (val == 'yes') {
                    this._translation(0);
                }
            },
            this
        );


    },

	_translation: function (start) {
        if (!this._wait) {
            this._wait = Ext.MessageBox.wait(
                _('localizator_translate_wait'),
                _('please_wait')
            );
        }
        MODx.Ajax.request({
            url: localizator.config.connector_url,
            params: {
                action: 'mgr/content/translate',
                start: start || 0,
				resource_id: MODx.request.id,
            },
            listeners: {
                success: {
                    fn: function (response) {
						if (response.object['total'] == response.object['processed']) {
                            this._wait.hide();
                            this._wait = null;
                            this.refresh();
                        } else {
                            this._wait.updateText(
                                _('localizator_translate_wait_ext')
                                    .replace('[[+processed]]', response.object['processed'])
                                    .replace('[[+total]]', response.object['total'])
                            );
                            this._translation(response.object['processed']);
                        }

                    }, scope: this
                },
				failure: {
					fn: function(response) {
						this._wait.hide();
						this._wait = null;
						this.refresh();
						MODx.msg.alert(_('error'), response.message);
					}, scope: this
				}
            }
        });
    },



    createItem: function (btn, e) {
        var w = MODx.load({
            xtype: 'localizator-content-window-create',
            id: Ext.id(),
            listeners: {
                success: {
                    fn: function () {
                        this.refresh();
                    }, scope: this
                }
            }
        });
        w.reset();
        w.setValues({active: true});
        w.show(e.target);
    },

    updateItem: function (btn, e, row) {
        if (typeof(row) != 'undefined') {
            this.menu.record = row.data;
        }
        else if (!this.menu.record) {
            return false;
        }
        var id = this.menu.record.id;

        MODx.Ajax.request({
            url: this.config.url,
            params: {
                action: 'mgr/content/get',
                id: id
            },
            listeners: {
                success: {
                    fn: function (r) {
                        var w = MODx.load({
                            xtype: 'localizator-content-window-update',
                            id: Ext.id(),
                            record: r,
                            listeners: {
                                success: {
                                    fn: function () {
                                        this.refresh();
                                    }, scope: this
                                }
                            }
                        });
                        w.reset();
                        w.setValues(r.object);
                        w.show(e.target);
                    }, scope: this
                }
            }
        });
    },

    removeItem: function () {
        var ids = this._getSelectedIds();
        if (!ids.length) {
            return false;
        }
        MODx.msg.confirm({
            title: ids.length > 1
                ? _('localizator_items_remove')
                : _('localizator_item_remove'),
            text: ids.length > 1
                ? _('localizator_items_remove_confirm')
                : _('localizator_item_remove_confirm'),
            url: this.config.url,
            params: {
                action: 'mgr/content/remove',
                ids: Ext.util.JSON.encode(ids),
            },
            listeners: {
                success: {
                    fn: function () {
                        this.refresh();
                    }, scope: this
                }
            }
        });
        return true;
    },

    disableItem: function () {
        var ids = this._getSelectedIds();
        if (!ids.length) {
            return false;
        }
        MODx.Ajax.request({
            url: this.config.url,
            params: {
                action: 'mgr/content/disable',
                ids: Ext.util.JSON.encode(ids),
            },
            listeners: {
                success: {
                    fn: function () {
                        this.refresh();
                    }, scope: this
                }
            }
        })
    },

    enableItem: function () {
        var ids = this._getSelectedIds();
        if (!ids.length) {
            return false;
        }
        MODx.Ajax.request({
            url: this.config.url,
            params: {
                action: 'mgr/content/enable',
                ids: Ext.util.JSON.encode(ids),
            },
            listeners: {
                success: {
                    fn: function () {
                        this.refresh();
                    }, scope: this
                }
            }
        })
    },

    getFields: function () {
        return ['id', '_key', 'pagetitle', 'seotitle', 'active', 'actions'];
    },

    getColumns: function () {
        return [{
            header: _('localizator__key'),
            dataIndex: '_key',
            sortable: true,
            width: 200,
        }, {
            header: _('localizator_pagetitle'),
            dataIndex: 'pagetitle',
            sortable: true,
            width: 200,
        }, {
            header: _('localizator_seotitle'),
            dataIndex: 'seotitle',
            sortable: true,
            width: 200,
        }, {
            header: _('localizator_active'),
            dataIndex: 'active',
            renderer: localizator.utils.renderBoolean,
            sortable: true,
            width: 100,
        }, {
            header: _('localizator_grid_actions'),
            dataIndex: 'actions',
            renderer: localizator.utils.renderActions,
            sortable: false,
            width: 100,
            id: 'actions'
        }];
    },

    getTopBar: function () {
        return [{
            text: '<i class="icon icon-globe"></i>&nbsp;' + _('localizator_add'),
            handler: this.createItem,
            scope: this
        }, {
            text: '<i class="icon icon-language"></i>&nbsp;' + _('localizator_translate'),
            handler: this.translateItem,
            scope: this
        }, '->', {
            xtype: 'localizator-field-search',
            width: 250,
            listeners: {
                search: {
                    fn: function (field) {
                        this._doSearch(field);
                    }, scope: this
                },
                clear: {
                    fn: function (field) {
                        field.setValue('');
                        this._clearSearch();
                    }, scope: this
                },
            }
        }];
    },

    onClick: function (e) {
        var elem = e.getTarget();
        if (elem.nodeName == 'BUTTON') {
            var row = this.getSelectionModel().getSelected();
            if (typeof(row) != 'undefined') {
                var action = elem.getAttribute('action');
                if (action == 'showMenu') {
                    var ri = this.getStore().find('id', row.id);
                    return this._showMenu(this, ri, e);
                }
                else if (typeof this[action] === 'function') {
                    this.menu.record = row.data;
                    return this[action](this, e);
                }
            }
        }
        return this.processEvent('click', e);
    },

    _getSelectedIds: function () {
        var ids = [];
        var selected = this.getSelectionModel().getSelections();

        for (var i in selected) {
            if (!selected.hasOwnProperty(i)) {
                continue;
            }
            ids.push(selected[i]['id']);
        }

        return ids;
    },

    _doSearch: function (tf) {
        this.getStore().baseParams.query = tf.getValue();
        this.getBottomToolbar().changePage(1);
    },

    _clearSearch: function () {
        this.getStore().baseParams.query = '';
        this.getBottomToolbar().changePage(1);
    },
});
Ext.reg('localizator-grid-content', localizator.grid.Content);






localizator.window.CreateContent = function (config) {
    config = config || {};
    if (!config.id) {
        config.id = 'localizator-content-window-create';
    }
    Ext.applyIf(config, {
        title: _('localizator_item_create'),
        width: 768,
        autoHeight: true,
        url: localizator.config.connector_url,
        action: 'mgr/content/create',
        fields: this.getFields(config),
        keys: [{
            key: Ext.EventObject.ENTER, shift: true, fn: function () {
                this.submit()
            }, scope: this
        }]
    });
    localizator.window.CreateContent.superclass.constructor.call(this, config);

	this.on('show', function() {
		this.setValues({ resource_id:MODx.request.id });
		if(MODx.loadRTE !== 'undefined') {
	      MODx.loadRTE((this.config.id || this.id) + '-content');
	    }
	}.bind(this));
};
Ext.extend(localizator.window.CreateContent, MODx.Window, {

    getFields: function (config) {
        return [{
            xtype: 'hidden',
            name: 'resource_id',
            id: config.id + '-resource_id',
			allowBlank: false,
        }, {
			layout:'column',
			border:false,
			anchor: '100%',
			style: {margin: '0 0 20px 0'},
			items: [{
				layout: 'form',
				border:false,
				columnWidth: .5,
				items: [{
					xtype: 'localizator-combo-language',
					fieldLabel: _('localizator_language'),
					anchor: '99%',
					allowBlank: false,
				}, {
					xtype: 'textfield',
					fieldLabel: _('localizator_menutitle'),
					name: 'menutitle',
					id: config.id + '-menutitle',
					anchor: '99%',
				}]
			}, {
				layout: 'form',
				border:false,
				columnWidth: .5,
				items: [{
					xtype: 'textfield',
					fieldLabel: _('localizator_pagetitle'),
					name: 'pagetitle',
					id: config.id + '-pagetitle',
					anchor: '99%',
				}, {
					xtype: 'textfield',
					fieldLabel: _('localizator_longtitle'),
					name: 'longtitle',
					id: config.id + '-longtitle',
					anchor: '99%',
				}]
			}, {
				layout: 'form',
				border:false,
				style: {margin: 0},
				columnWidth: 1,
				items: [{
					xtype: 'textarea',
					fieldLabel: _('localizator_introtext'),
					name: 'introtext',
					id: config.id + '-introtext',
					anchor: '99%',
				}]
			}, {
				layout: 'form',
				border:false,
				style: {margin: 0},
				columnWidth: 1,
				items: [{
					xtype: 'textfield',
					fieldLabel: _('localizator_seotitle'),
					name: 'seotitle',
					id: config.id + '-seotitle',
					anchor: '99%',
				}, {
					xtype: 'textarea',
					fieldLabel: _('localizator_description'),
					name: 'description',
					id: config.id + '-description',
					anchor: '99%',
				}, {
					xtype: 'textfield',
					fieldLabel: _('localizator_keywords'),
					name: 'keywords',
					id: config.id + '-keywords',
					anchor: '99%',
				}]
			}, /* {
				поля перед контентом
			},*/ {
				layout: 'form',
				border:false,
				style: {margin: 0},
				columnWidth: 1,
				items: [{
					xtype: 'textarea',
					fieldLabel: '',
					name: 'content',
					id: config.id + '-content',
					anchor: '99%',
				}]
			}]
		}];
    },

});
Ext.reg('localizator-content-window-create', localizator.window.CreateContent);

localizator.window.UpdateContent = function (config) {
    config = config || {};
    if (!config.id) {
        config.id = 'localizator-content-window-update';
    }
    Ext.applyIf(config, {
        title: _('localizator_item_update'),
        width: 768,
        autoHeight: true,
        url: localizator.config.connector_url,
        action: 'mgr/content/update',
        fields: this.getFields(config),
        keys: [{
            key: Ext.EventObject.ENTER, shift: true, fn: function () {
                this.submit()
            }, scope: this
        }]
    });
    localizator.window.UpdateContent.superclass.constructor.call(this, config);

	this.on('show', function() {
		if(MODx.loadRTE !== 'undefined') {
	      MODx.loadRTE((this.config.id || this.id) + '-content');
	    }
	}.bind(this));
};
Ext.extend(localizator.window.UpdateContent, MODx.Window, {

    getFields: function (config) {
        return [{
            xtype: 'hidden',
            name: 'id',
            id: config.id + '-id',
			allowBlank: false,
        }, {
            xtype: 'hidden',
            name: 'resource_id',
            id: config.id + '-resource_id',
			allowBlank: false,
        }, {
			layout:'column',
			border:false,
			anchor: '100%',
			style: {margin: '0 0 20px 0'},
			items: [{
				layout: 'form',
				border:false,
				columnWidth: .5,
				items: [{
					xtype: 'localizator-combo-language',
					fieldLabel: _('localizator_language'),
					anchor: '99%',
					allowBlank: false,
				}, {
					xtype: 'textfield',
					fieldLabel: _('localizator_menutitle'),
					name: 'menutitle',
					id: config.id + '-menutitle',
					anchor: '99%',
				}]
			}, {
				layout: 'form',
				border:false,
				columnWidth: .5,
				items: [{
					xtype: 'textfield',
					fieldLabel: _('localizator_pagetitle'),
					name: 'pagetitle',
					id: config.id + '-pagetitle',
					anchor: '99%',
				}, {
					xtype: 'textfield',
					fieldLabel: _('localizator_longtitle'),
					name: 'longtitle',
					id: config.id + '-longtitle',
					anchor: '99%',
				}]
			}, {
				layout: 'form',
				border:false,
				style: {margin: 0},
				columnWidth: 1,
				items: [{
					xtype: 'textarea',
					fieldLabel: _('localizator_introtext'),
					name: 'introtext',
					id: config.id + '-introtext',
					anchor: '99%',
				}]
			}, {
				layout: 'form',
				border:false,
				style: {margin: 0},
				columnWidth: 1,
				items: [{
					xtype: 'textfield',
					fieldLabel: _('localizator_seotitle'),
					name: 'seotitle',
					id: config.id + '-seotitle',
					anchor: '99%',
				}, {
					xtype: 'textarea',
					fieldLabel: _('localizator_description'),
					name: 'description',
					id: config.id + '-description',
					anchor: '99%',
				}, {
					xtype: 'textfield',
					fieldLabel: _('localizator_keywords'),
					name: 'keywords',
					id: config.id + '-keywords',
					anchor: '99%',
				}]
			}, /* {
				поля перед контентом
			},*/ {
				layout: 'form',
				border:false,
				style: {margin: 0},
				columnWidth: 1,
				items: [{
					xtype: 'textarea',
					fieldLabel: '',
					name: 'content',
					id: config.id + '-content',
					anchor: '99%',
				}]
			}]
		}];
    },


});
Ext.reg('localizator-content-window-update', localizator.window.UpdateContent);
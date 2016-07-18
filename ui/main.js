//
// The tutorials app to manage an tutorials collection
//
function ciniki_tutorials_main() {
    this.webFlags = {
        '1':{'name':'Visible'},
        };
    this.toggleOptions = {'include':'Include'};
    this.init = function() {
        //
        // Setup the main panel to list the collection
        //
        this.menu = new M.panel('Tutorials',
            'ciniki_tutorials_main', 'menu',
            'mc', 'medium narrowaside', 'sectioned', 'ciniki.tutorials.main.menu');
        this.menu.data = {};
        this.menu.category = '';
        this.menu.sections = {
            'groups':{'label':'Groups', 'visible':'no', 'aside':'yes', 'type':'simplegrid', 'num_cols':1},
            'categories':{'label':'Categories', 'aside':'yes', 'type':'simplegrid', 'num_cols':1},
            'groupexport':{'label':'Export', 'visible':'no', 'aside':'yes', 'type':'simplegrid', 'num_cols':1},
            'options':{'label':'Options', 'visible':'no', 'aside':'yes', 'list':{
                'export':{'label':'Export', 'fn':'M.ciniki_tutorials_main.exportShow(\'M.ciniki_tutorials_main.showMenu();\');'},
                }},
            'published':{'label':'Published', 'visible':'yes', 'type':'simplegrid', 'num_cols':1,
                'cellClasses':['multiline'],
                'addTxt':'Add Tutorial',
                'addFn':'M.ciniki_tutorials_main.tutorialEdit(\'M.ciniki_tutorials_main.showMenu();\',0,M.ciniki_tutorials_main.menu.category);',
                },
            'unpublished':{'label':'Drafts', 'visible':'yes', 'type':'simplegrid', 'num_cols':1,
                'cellClasses':['multiline'],
                },
            };
        this.menu.sectionData = function(s) { 
            if( s == 'groupexport' ) { return this.data['groups']; }
            if( s == 'options' ) { return this.sections[s].list; }
            return this.data[s];
        };
        this.menu.cellValue = function(s, i, j, d) {
            if( s == 'groups' || s == 'groupexport' ) {
                return d.group.name;
            } else if( s == 'categories' ) {
                return d.category.name;
            } else if( s == 'published' || s == 'unpublished' ) {
                return '<span class="maintext">' + d.tutorial.title + '</span><span class="subtext">' + d.tutorial.tags + '</span>';
            }
        };
        this.menu.rowFn = function(s, i, d) {
            if( s == 'groups' ) {
                return 'M.ciniki_tutorials_main.showMenu(null,\'' + escape(d.group.name) + '\',\'\',\'' + d.group.permalink + '\');';
            } else if( s == 'categories' ) {
                return 'M.ciniki_tutorials_main.showMenu(null,\'' + escape(d.category.name) + '\',\'' + d.category.permalink + '\',\'\');';
            } else if( s == 'groupexport' ) {
                return 'M.ciniki_tutorials_main.exportShow(\'M.ciniki_tutorials_main.showMenu();\',\'' + escape(d.group.name) + '\',\'' + d.group.permalink + '\');';
            } else if( s == 'published' || s == 'unpublished' ) {
                return 'M.ciniki_tutorials_main.tutorialEdit(\'M.ciniki_tutorials_main.showMenu();\',\'' + d.tutorial.id + '\');';
            }
        };
        this.menu.addButton('add', 'Add', 'M.ciniki_tutorials_main.tutorialEdit(\'M.ciniki_tutorials_main.showMenu();\',0,M.ciniki_tutorials_main.menu.category);');
        this.menu.addButton('edit', 'Edit', 'M.ciniki_tutorials_main.categoryEdit(\'M.ciniki_tutorials_main.showMenu();\',M.ciniki_tutorials_main.menu.category);');
        this.menu.addClose('Back');

        //
        // The panel to edit a category
        //
        this.category = new M.panel('Category',
            'ciniki_tutorials_main', 'category',
            'mc', 'medium', 'sectioned', 'ciniki.tutorials.main.category');
        this.category.data = null;
        this.category.permalink = 0;
        this.category.sections = {
            'details':{'label':'', 'fields':{
                'sequence':{'label':'Sequence', 'type':'text'},
            }}, 
            '_buttons':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_tutorials_main.categorySave();'},
            }},
        };
        this.category.fieldValue = function(s, i, d) {
            return this.data[i];
        };
        this.category.addButton('save', 'Save', 'M.ciniki_tutorials_main.categorySave();');
        this.category.addClose('Cancel');

        //
        // The panel to edit a group
        //
        this.group = new M.panel('Group',
            'ciniki_tutorials_main', 'group',
            'mc', 'medium', 'sectioned', 'ciniki.tutorials.main.group');
        this.group.data = null;
        this.group.permalink = 0;
        this.group.sections = {
            '_image':{'label':'Image', 'aside':'yes', 'type':'imageform', 'fields':{
                'image':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'history':'no', 'controls':'all'},
            }},
            'details':{'label':'', 'fields':{
                'sequence':{'label':'Sequence', 'type':'text'},
            }}, 
            '_content':{'label':'Content', 'type':'simpleform', 'fields':{
                'content':{'label':'', 'type':'textarea', 'size':'large', 'hidelabel':'yes'},
            }},
            '_buttons':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_tutorials_main.groupSave();'},
            }},
        };
        this.group.fieldValue = function(s, i, d) {
            return this.data[i];
        };
        this.group.addDropImage = function(iid) {
            M.ciniki_tutorials_main.group.setFieldValue('image', iid, null, null);
            return true;
        };
        this.group.deleteImage = function() {
            this.setFieldValue('image', 0, null, null);
            return true;
        };
        this.group.addButton('save', 'Save', 'M.ciniki_tutorials_main.groupSave();');
        this.group.addClose('Cancel');

        //
        // Display information about a tutorial
        //
        this.tutorial = new M.panel('Tutorial',
            'ciniki_tutorials_main', 'tutorial',
            'mc', 'medium mediumaside', 'sectioned', 'ciniki.tutorials.main.tutorial');
        this.tutorial.data = null;
        this.tutorial.tutorial_id = 0;
        this.tutorial.sections = {
            '_image':{'label':'Image', 'aside':'yes', 'type':'imageform', 'fields':{
                'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'history':'no', 'controls':'all'},
            }},
            'details':{'label':'', 'aside':'yes', 'fields':{
                'title':{'label':'Title', 'type':'text'},
//              'permalink':{'label':'Permalink', 'active':'no', 'type':'text'},
                'sequence':{'label':'Order', 'type':'text'},
                'webflags_1':{'label':'Published', 'type':'flagtoggle', 'bit':0x01, 'field':'webflags', 'default':'on'},
            }}, 
            '_groups':{'label':'Groups', 'aside':'yes', 'active':'no', 'fields':{
                'groups':{'label':'', 'hidelabel':'yes', 'type':'tags', 'tags':[], 'hint':'Enter a new group:'},
            }},
            '_categories':{'label':'Categories', 'aside':'yes', 'active':'no', 'fields':{
                'categories':{'label':'', 'hidelabel':'yes', 'type':'tags', 'tags':[], 'hint':'Enter a new category:'},
            }},
            '_synopsis':{'label':'Synopsis', 'type':'simpleform', 'fields':{
                'synopsis':{'label':'', 'type':'textarea', 'hidelabel':'yes'},
            }},
            '_content':{'label':'Content', 'type':'simpleform', 'fields':{
                'content':{'label':'', 'type':'textarea', 'size':'large', 'hidelabel':'yes'},
            }},
            'steps':{'label':'Steps', 'type':'simplegrid', 'num_cols':1,
                'cellClasses':['multiline'],
                'addTxt':'Add Step',
                'addFn':'M.ciniki_tutorials_main.stepEdit(\'M.ciniki_tutorials_main.refreshSteps();\',0,M.ciniki_tutorials_main.tutorial.tutorial_id,[]);',
                },
            '_buttons':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_tutorials_main.tutorialSave();'},
                'delete':{'label':'Delete', 'fn':'M.ciniki_tutorials_main.tutorialDelete();'},
            }},
        };
        this.tutorial.fieldValue = function(s, i, d) {
            return this.data[i];
        };
        this.tutorial.sectionData = function(s) {
            return this.data[s];
        };
        this.tutorial.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.tutorials.tutorialHistory', 'args':{'business_id':M.curBusinessID, 
                'tutorial_id':this.tutorial_id, 'field':i}};
        }
        this.tutorial.addDropImage = function(iid) {
            M.ciniki_tutorials_main.tutorial.setFieldValue('primary_image_id', iid, null, null);
            return true;
        };
        this.tutorial.deleteImage = function() {
            this.setFieldValue('primary_image_id', 0, null, null);
            return true;
        };
        this.tutorial.cellValue = function(s, i, j, d) {
            if( (M.curBusiness.modules['ciniki.tutorials'].flags&0x01) > 0 ) {
                return '<span class="maintext">' + d.step.number + ': ' + d.step.title + '</span><span class="subtext">' + d.step.code + '</span>';
            }
            return d.step.title;
        };
        this.tutorial.rowFn = function(s, i, d) {
            return 'M.ciniki_tutorials_main.stepEdit(\'M.ciniki_tutorials_main.refreshSteps();\',\'' + d.step.id + '\',null,M.ciniki_tutorials_main.tutorial.data.steps);';
        };
        this.tutorial.addButton('save', 'Save', 'M.ciniki_tutorials_main.tutorialSave();');
        this.tutorial.addClose('Cancel');

        //
        // Display the form to edit a step
        //
        this.step = new M.panel('Step',
            'ciniki_tutorials_main', 'step',
            'mc', 'medium mediumaside', 'sectioned', 'ciniki.tutorials.main.step');
        this.step.data = null;
        this.step.step_content_id = 0;
        this.step.tutorial_id = 0;
        this.step.prevnext = {'prev_id':-1, 'next_id':-1, 'list':[]};
        this.step.sections = {
            '_image':{'label':'Image', 'aside':'yes', 'type':'imageform', 'fields':{
                'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'history':'no', 'controls':'all'},
            }},
            'details':{'label':'', 'aside':'yes', 'fields':{
                'code':{'label':'Code', 'type':'text', 'livesearch':'yes'},
                'title':{'label':'Title', 'type':'text'},
                'sequence':{'label':'Step #', 'type':'text'},
            }}, 
            '_content':{'label':'Content', 'type':'simpleform', 'fields':{
                'content':{'label':'', 'type':'textarea', 'size':'large', 'hidelabel':'yes'},
            }},
            '_buttons':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_tutorials_main.stepSave();'},
                'delete':{'label':'Delete', 'fn':'M.ciniki_tutorials_main.stepDelete();'},
            }},
        };
        this.step.fieldValue = function(s, i, d) {
            return this.data[i];
        };
        this.step.sectionData = function(s) {
            return this.data[s];
        };
        this.step.fieldHistoryArgs = function(s, i) {
            if( i == 'code' || i == 'title' || i == 'content' ) {
                return {'method':'ciniki.tutorials.tutorialStepContentHistory', 'args':{'business_id':M.curBusinessID, 
                    'content_id':this.step_content_id, 'field':i}};
            }
            return {'method':'ciniki.tutorials.tutorialStepHistory', 'args':{'business_id':M.curBusinessID, 
                'step_id':this.step_id, 'field':i}};
        }
        this.step.addDropImage = function(iid) {
            M.ciniki_tutorials_main.step.setFieldValue('image_id', iid, null, null);
            return true;
        };
        this.step.deleteImage = function() {
            this.setFieldValue('image_id', 0, null, null);
            return true;
        };
        this.step.liveSearchCb = function(s, i, value) {
            if( i == 'code' || i == 'title' ) {
                var rsp = M.api.getJSONBgCb('ciniki.tutorials.tutorialStepContentSearchField', 
                    {'business_id':M.curBusinessID, 'start_needle':value, 'field':i, 'limit':25}, function(rsp) { 
                        M.ciniki_tutorials_main.step.search_results = rsp.results;
                        M.ciniki_tutorials_main.step.liveSearchShow(s, i, M.gE(M.ciniki_tutorials_main.step.panelUID + '_' + i), rsp.results); 
                    });
                
            }
        };
        this.step.liveSearchResultValue = function(s, f, i, j, d) {
            if( f == 'code' ) {
                return d.result.code + ' - ' + d.result.title; 
            } else if( f == 'title' ) { 
                return d.result.title + ' - ' + d.result.code; 
            }
            return '';
        };
        this.step.liveSearchResultRowFn = function(s, f, i, j, d) { 
            if( f == 'code' || f == 'title' ) { 
                return 'M.ciniki_tutorials_main.stepUpdateContent(\'' + s + '\',\'' + f + '\',\'' + d.result.id + '\');';
            }
        };
        this.step.prevButtonFn = function() {
            if( this.prevnext.prev_id > -1 ) {
                return 'M.ciniki_tutorials_main.stepSave(\'' + this.prevnext.prev_id + '\');';
            }
            return null;
        };
        this.step.nextButtonFn = function() {
            if( this.prevnext.next_id > -1 ) {
                return 'M.ciniki_tutorials_main.stepSave(\'' + this.prevnext.next_id + '\');';
            }
            return null;
        };
        this.step.addButton('save', 'Save', 'M.ciniki_tutorials_main.stepSave();');
        this.step.addButton('next', 'Next');
        this.step.addClose('Cancel');
        this.step.addLeftButton('prev', 'Prev');

        //
        // The export/share panel
        //
        this.export = new M.panel('Tutorials',
            'ciniki_tutorials_main', 'export',
            'mc', 'medium', 'sectioned', 'ciniki.tutorials.main.export');
        this.export.data = {};
        this.export.group = '';
        this.export.category = '';
        this.export.sections = {};
        this.export.fieldValue = function(s, i, d) {
            return this.data[i];
        };
        this.export.addClose('Back');
    }

    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create container
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_tutorials_main', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        }

        //
        // Determine what is visible
        //
        this.menu.sections.groups.visible=(M.curBusiness.modules['ciniki.tutorials'].flags&0x04)>0?'yes':'no';
        this.menu.sections.groupexport.visible=(M.curBusiness.modules['ciniki.tutorials'].flags&0x04)>0?'yes':'no';
        this.menu.sections.options.visible=(M.curBusiness.modules['ciniki.tutorials'].flags&0x04)>0?'no':'yes';
        this.menu.sections.categories.visible=(M.curBusiness.modules['ciniki.tutorials'].flags&0x02)>0?'yes':'no';
        this.tutorial.sections._groups.active=(M.curBusiness.modules['ciniki.tutorials'].flags&0x04)>0?'yes':'no';
        this.tutorial.sections._categories.active=(M.curBusiness.modules['ciniki.tutorials'].flags&0x02)>0?'yes':'no';
//      this.tutorial.sections.details.fields.permalink.active=(M.curBusiness.modules['ciniki.tutorials'].flags&0x04)>0?'yes':'no';
        this.step.sections.details.fields.code.active=(M.curBusiness.modules['ciniki.tutorials'].flags&0x01)>0?'yes':'no';

        this.menu.category = '';
        this.menu.group = '';
        this.showMenu(cb, 'Tutorials', '', '');
    }

    this.showMenu = function(cb, title, category, group) {
        if( title != null && title != '' ) { this.menu.sections.published.label = unescape(title); }
        if( category != null ) { 
            this.menu.category = category; 
            if( category != '' ) {
                this.menu.addButton('edit', 'Edit', 'M.ciniki_tutorials_main.categoryEdit(\'M.ciniki_tutorials_main.showMenu();\',M.ciniki_tutorials_main.menu.category);');
            }
        }
        if( group != null ) { 
            this.menu.group = group; 
            if( group != '' ) {
                this.menu.addButton('edit', 'Edit', 'M.ciniki_tutorials_main.groupEdit(\'M.ciniki_tutorials_main.showMenu();\',M.ciniki_tutorials_main.menu.group);');
            }
        }
        M.api.getJSONCb('ciniki.tutorials.tutorialList', 
            {'business_id':M.curBusinessID, 'category':this.menu.category, 'group':this.menu.group, 'categories':'yes', 'groups':'yes'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_tutorials_main.menu;
                p.data = rsp;
                p.size = 'medium';
                p.sections.categories.visible = 'no';
                p.sections.categories.aside = 'no';
                p.sections.groups.visible = 'no';
                p.sections.groups.aside = 'no';
                if( rsp.categories != null && rsp.categories.length > 0 ) {
                    p.size = 'medium narrowaside';
                    p.sections.categories.visible = 'yes';
                    p.sections.categories.aside = 'yes';
                }
                if( rsp.groups != null && rsp.groups.length > 0 ) {
                    p.size = 'medium narrowaside';
                    p.sections.groups.visible = 'yes';
                    p.sections.groups.aside = 'yes';
                }
                p.sections.unpublished.visible = (rsp.unpublished!=null&&rsp.unpublished.length>0?'yes':'no');
                p.refresh();
                p.show(cb);
            });
    };

    this.categoryEdit = function(cb, category) {
        if( category != null ) { this.category.permalink = category; }
        M.api.getJSONCb('ciniki.tutorials.tagDetails', {'business_id':M.curBusinessID, 'tag_type':'10',
            'tag':this.category.permalink}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_tutorials_main.category;
                p.data = rsp.details;
                p.refresh();
                p.show(cb);
            });
    };

    this.categorySave = function() {
        var c = this.category.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.tutorials.tagUpdate', 
                {'business_id':M.curBusinessID, 'tag_type':'10', 'tag':this.category.permalink}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_tutorials_main.category.close();
                });
        } else {
            M.ciniki_tutorials_main.category.close();
        }
    };

    this.groupEdit = function(cb, group) {
        if( group != null ) { this.group.permalink = group; }
        M.api.getJSONCb('ciniki.tutorials.tagDetails', {'business_id':M.curBusinessID, 'tag_type':'40',
            'tag':this.group.permalink}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_tutorials_main.group;
                p.data = rsp.details;
                p.refresh();
                p.show(cb);
            });
    };

    this.groupSave = function() {
        var c = this.group.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.tutorials.tagUpdate', 
                {'business_id':M.curBusinessID, 'tag_type':'40', 'tag':this.group.permalink}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_tutorials_main.group.close();
                });
        } else {
            M.ciniki_tutorials_main.group.close();
        }
    };

    this.tutorialEdit = function(cb, tid, category) {
        if( tid != null ) { this.tutorial.tutorial_id = tid; }
        M.api.getJSONCb('ciniki.tutorials.tutorialGet', 
            {'business_id':M.curBusinessID, 'tutorial_id':this.tutorial.tutorial_id, 'categories':'yes', 'groups':'yes', 'steps':'yes'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_tutorials_main.tutorial;
                p.data = rsp.tutorial;
                p.sections._categories.fields.categories.tags = [];
                if( rsp.categories != null ) {
                    for(i in rsp.categories) {
                        p.sections._categories.fields.categories.tags.push(rsp.categories[i].category.name);
                    }
                }
                p.sections._groups.fields.groups.tags = [];
                if( rsp.groups != null ) {
                    for(i in rsp.groups) {
                        p.sections._groups.fields.groups.tags.push(rsp.groups[i].group.name);
                    }
                }
                p.refresh();
                p.show(cb);
            });
    };

    this.tutorialSave = function() {
        // Check form values
        var nv = this.tutorial.formFieldValue(this.tutorial.sections.details.fields.title, 'title');
        if( nv != this.tutorial.fieldValue('details', 'title') && nv == '' ) {
            alert('You must specifiy a title');
            return false;
        }
        if( this.tutorial.tutorial_id > 0 ) {
            var c = this.tutorial.serializeForm('no');
//          if( this.tutorial.sections.details.fields.permalink.active == 'yes' && this.tutorial.formValue('title') != this.tutorial.data.title ) {
//              c += '&permalink=' + encodeURIComponent(this.tutorial.formValue('permalink'));
//          }
            if( c != '' ) {
                M.api.postJSONFormData('ciniki.tutorials.tutorialUpdate', {'business_id':M.curBusinessID, 
                    'tutorial_id':this.tutorial.tutorial_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                        M.ciniki_tutorials_main.tutorial.close();
                    });
            } else {
                this.tutorial.close();
            }
        } else {
            var c = this.tutorial.serializeForm('yes');
            M.api.postJSONFormData('ciniki.tutorials.tutorialAdd', 
                {'business_id':M.curBusinessID}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_tutorials_main.tutorial.close();
                });
        }
    };

    this.tutorialDelete = function() {
        if( confirm('Are you sure you want to delete the tutorial \'' + this.tutorial.data.name + '\'?  All information about it will be removed and unrecoverable.') ) {
            M.api.getJSONCb('ciniki.tutorials.tutorialDelete', 
                {'business_id':M.curBusinessID, 'tutorial_id':this.tutorial.tutorial_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_tutorials_main.tutorial.close();
                });
        }
    };

    this.stepEdit = function(cb, sid, tid, list) {
        if( sid != null ) { this.step.step_id = sid; }
        if( list != null ) { this.step.prevnext.list = list; }
        if( M.ciniki_tutorials_main.tutorial.tutorial_id == 0 ) {
            var c = this.tutorial.serializeForm('yes');
            M.api.postJSONFormData('ciniki.tutorials.tutorialAdd', {'business_id':M.curBusinessID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_tutorials_main.tutorial.tutorial_id = rsp.id;
                M.ciniki_tutorials_main.stepEditFinish(cb, rsp.id);
            });
        }
        this.stepEditFinish(cb, tid);
    };

    this.stepEditFinish = function(cb, tid) {
        if( tid != null ) { this.step.tutorial_id = tid; }
        M.api.getJSONCb('ciniki.tutorials.tutorialStepGet', 
            {'business_id':M.curBusinessID, 'step_id':this.step.step_id, 'tutorial_id':this.step.tutorial_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_tutorials_main.step;
                p.data = rsp.step;
                p.step_content_id = rsp.step.step_content_id;
                // Setup prev/next buttons
                p.prevnext.prev_id = -1;
                p.prevnext.next_id = -1;
                if( p.prevnext.list != null ) {
                    for(i in p.prevnext.list) {
                        if( p.prevnext.next_id == -2 ) {
                            p.prevnext.next_id = p.prevnext.list[i].step.id;
                            break;
                        } else if( p.prevnext.list[i].step.id == p.step_id ) {
                            p.prevnext.next_id = -2;
                        } else {
                            p.prevnext.prev_id = p.prevnext.list[i].step.id;
                        }
                    }
                }
                p.refresh();
                p.show(cb);
            });
    };

    this.refreshSteps = function() {
        if( M.ciniki_tutorials_main.tutorial.tutorial_id > 0 ) {
            M.api.getJSONCb('ciniki.tutorials.tutorialGet', 
                {'business_id':M.curBusinessID, 'tutorial_id':M.ciniki_tutorials_main.tutorial.tutorial_id, 
                'steps':'yes'}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_tutorials_main.tutorial;
                    p.data.steps = rsp.tutorial.steps;
                    p.refreshSection('steps');
                    p.show();
                });
        } else {
            this.tutorial.refresh();
            this.tutorial.show();
        }
    };

    this.stepUpdateContent = function(s, f, cid) {
        
        for(i in this.step.search_results) {
            if(this.step.search_results[i].result.id == cid) {  
                this.step.step_content_id = cid;
                this.step.data.code = this.step.search_results[i].result.code;
                this.step.data.title = this.step.search_results[i].result.title;
                this.step.data.content = this.step.search_results[i].result.content;
                this.step.data.image_id = this.step.search_results[i].result.image_id;
                this.step.setFieldValue('code', this.step.search_results[i].result.code);
                this.step.setFieldValue('title', this.step.search_results[i].result.title);
                this.step.setFieldValue('content', this.step.search_results[i].result.content);
                this.step.refreshSection('_image');
                break;
            }
        }
        this.step.removeLiveSearch(s, f);
    };

    this.stepSave = function(pnid) {
        // Check form values
        var nv = this.step.formFieldValue(this.step.sections.details.fields.title, 'title');
        if( nv != this.step.fieldValue('details', 'title') && nv == '' ) {
            alert('You must specifiy a title');
            return false;
        }
        if( this.step.step_id > 0 ) {
            var c = this.step.serializeForm('no');
            if( c != '' ) {
                c += '&step_content_id=' + encodeURIComponent(this.step.step_content_id);
                M.api.postJSONFormData('ciniki.tutorials.tutorialStepUpdate', {'business_id':M.curBusinessID, 
                    'step_id':this.step.step_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                        (pnid!=null&&pnid>-1)?M.ciniki_tutorials_main.stepEdit(null,pnid):M.ciniki_tutorials_main.step.close();
                    });
            } else {
                (pnid!=null&&pnid>-1)?M.ciniki_tutorials_main.stepEdit(null,pnid):this.step.close();
            }
        } else {
            var c = this.step.serializeForm('yes');
            c += '&step_content_id=' + encodeURIComponent(this.step.step_content_id);
            c += '&tutorial_id=' + this.step.tutorial_id;
            M.api.postJSONFormData('ciniki.tutorials.tutorialStepAdd', 
                {'business_id':M.curBusinessID}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_tutorials_main.step.close();
                });
        }
    };

    this.stepDelete = function() {
        if( confirm('Are you sure you want to delete step \'' + this.step.data.title + '\'?  All information about it will be removed and unrecoverable.') ) {
            M.api.getJSONCb('ciniki.tutorials.tutorialStepDelete', 
                {'business_id':M.curBusinessID, 'step_id':this.step.step_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_tutorials_main.step.close();
                });
        }
    };

    this.exportShow = function(cb, title, group) {
        this.export.sections = {
            'details':{'label':'Export', 'fields':{
                'layout':{'label':'Layout', 'type':'select', 'default':'single', 'options':{'single':'Single', 'double':'Double', 'triple':'Triple'}},
                'coverpage':{'label':'Cover Page', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}},
                'toc':{'label':'TOC', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}},
                'doublesided':{'label':'Double Sided', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}},
                'title':{'label':'Title', 'type':'text'},
                'removetext':{'label':'Remove', 'type':'text', 'visible':'yes'},
                }},
        };
        if( group != null ) {
            this.export.group = group;
        }
        M.api.getJSONCb('ciniki.tutorials.tutorialList', 
            {'business_id':M.curBusinessID, 'allcategories':'yes', 'group':this.export.group}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_tutorials_main.export;
                p.data = rsp;
                if( rsp.categories != null ) {
                    for(var i in rsp.categories) {
                        p.sections['_' + i] = {'label':rsp.categories[i].category.name,
                            'fields':{}};
                        if( rsp.categories[i].category.tutorials != null ) {
                            for(var j in rsp.categories[i].category.tutorials) {
                                var t = rsp.categories[i].category.tutorials[j].tutorial;
                                p.sections['_'+i].fields['_'+t.id] = {'label':t.title, 'id':t.id, 'type':'toggle', 'none':'yes', 'toggles':{'include':'Yes'}};
                            }
                        }
                    }
                }
                p.sections['_buttons'] = {'label':'', 'buttons':{
                    'export':{'label':'Export', 'fn':'M.ciniki_tutorials_main.exportPDF();'},
                    }};
                p.refresh();
                p.show(cb);
            });
    };

    this.exportPDF = function(group) {
        var args = {'business_id':M.curBusinessID, 'output':'pdf'};
        args['layout'] = this.export.formValue('layout');
        if( group != null ) {
            args['group'] = group;
        }
        args['coverpage'] = this.export.formValue('coverpage');
        args['toc'] = this.export.formValue('toc');
        args['doublesided'] = this.export.formValue('doublesided');
        args['title'] = this.export.formValue('title');
        args['removetext'] = this.export.formValue('removetext');
        var tutorials = '';
        for(var i in this.export.sections) {
            if( i.match(/_[0-9]/) ) {
                for(var j in this.export.sections[i].fields) {
                    if( this.export.formValue(j) == 'include' ) {
                        tutorials += ',' + this.export.sections[i].fields[j].id;
                    }
                }
            }
        }
        if( tutorials != '' ) {
            args['tutorials'] = tutorials.substring(1); // remove first comma
        }
        M.api.openPDF('ciniki.tutorials.downloadPDF', args);
    };
}

(function () {
    var URL = window.UEDITOR_HOME_URL || getUEBasePath();
    window.UEDITOR_CONFIG = {
        UEDITOR_HOME_URL: URL
        , serverUrl: URL + "php/controller.php"
        , toolbars: [[
           'undo', 'redo','bold', 'italic', 'underline','forecolor','justifyleft', 'justifycenter', 'justifyright', 'inserttable' ,'source'
        ]]
        ,initialFrameHeight:100
        ,enableAutoSave: false
        ,enableContextMenu: true
        ,contextMenu:[
            {
                label:'',       //显示的名称
                cmdName:'selectall'//执行的command命令，当点击这个右键菜单时
            },
			'-',
                {
                    group:'',
                    icon:'table',
                    subMenu:[
                        {
                            label:'',
                            cmdName:'inserttable'
                        },
                        {
                            label:'',
                            cmdName:'deletetable'
                        },
                        '-',
                        {
                            label:'',
                            cmdName:'deleterow'
                        },
                        {
                            label:'',
                            cmdName:'deletecol'
                        },
                        {
                            label:'',
                            cmdName:'insertcol'
                        },
                        {
                            label:'',
                            cmdName:'insertcolnext'
                        },
                        {
                            label:'',
                            cmdName:'insertrow'
                        },
                        {
                            label:'',
                            cmdName:'insertrownext'
                        },
                        '-',
                        {
                            label:'',
                            cmdName:'mergecells'
                        },
                        {
                            label:'',
                            cmdName:'mergeright'
                        },
                        {
                            label:'',
                            cmdName:'mergedown'
                        },
                        '-',
                        {
                            label:'',
                            cmdName:'splittorows'
                        },
                        {
                            label:'',
                            cmdName:'splittocols'
                        },
                        {
                            label:'',
                            cmdName:'splittocells'
                        },
                        '-',
                        {
                            label:'',
                            cmdName:'averagedistributerow'
                        },
                        {
                            label:'',
                            cmdName:'averagedistributecol'
                        },
                        '-',
                        {
                            label:'',
                            cmdName:'edittd',
                            exec:function () {
                                if ( UE.ui['edittd'] ) {
                                    new UE.ui['edittd']( this );
                                }
                                this.getDialog('edittd').open();
                            }
                        },
                        {
                            label:'',
                            cmdName:'edittable',
                            exec:function () {
                                if ( UE.ui['edittable'] ) {
                                    new UE.ui['edittable']( this );
                                }
                                this.getDialog('edittable').open();
                            }
                        }
                    ]
                },
                {
                    group:'',
                    icon:'borderBack',
                    subMenu:[
                        {
                            label:'表格隔行变色',
                            cmdName:"interlacetable",
                            exec:function(){
                                this.execCommand("interlacetable");
                            }
                        },
                        {
                            label:'取消隔行变色',
                            cmdName:"uninterlacetable",
                            exec:function(){
                                this.execCommand("uninterlacetable");
                            }
                        },
                        {
                            label:'',
                            cmdName:"settablebackground",
                            exec:function(){
                                this.execCommand("settablebackground",{repeat:true,colorList:["#bbb","#ccc"]});
                            }
                        },
                        {
                            label:'',
                            cmdName:"cleartablebackground",
                            exec:function(){
                                this.execCommand("cleartablebackground");
                            }
                        },
                        {
                            label:'',
                            cmdName:"settablebackground",
                            exec:function(){
                                this.execCommand("settablebackground",{repeat:true,colorList:["red","blue"]});
                            }
                        },
                        {
                            label:'',
                            cmdName:"settablebackground",
                            exec:function(){
                                this.execCommand("settablebackground",{repeat:true,colorList:["#aaa","#bbb","#ccc"]});
                            }
                        }
                    ]
                },{
                    group:'',
                    icon:'aligntd',
                    subMenu:[
                        {
                            cmdName:'cellalignment',
                            value:{align:'left',vAlign:'top'}
                        },
                        {
                            cmdName:'cellalignment',
                            value:{align:'center',vAlign:'top'}
                        },
                        {
                            cmdName:'cellalignment',
                            value:{align:'right',vAlign:'top'}
                        },
                        {
                            cmdName:'cellalignment',
                            value:{align:'left',vAlign:'middle'}
                        },
                        {
                            cmdName:'cellalignment',
                            value:{align:'center',vAlign:'middle'}
                        },
                        {
                            cmdName:'cellalignment',
                            value:{align:'right',vAlign:'middle'}
                        },
                        {
                            cmdName:'cellalignment',
                            value:{align:'left',vAlign:'bottom'}
                        },
                        {
                            cmdName:'cellalignment',
                            value:{align:'center',vAlign:'bottom'}
                        },
                        {
                            cmdName:'cellalignment',
                            value:{align:'right',vAlign:'bottom'}
                        }
                    ]
                }
        ]
        ,shortcutMenu:[]
        ,elementPathEnabled : false
        ,wordCount:false
		,autoFloatEnabled:false
        ,autoHeightEnabled:true
    };

    function getUEBasePath(docUrl, confUrl) {

        return getBasePath(docUrl || self.document.URL || self.location.href, confUrl || getConfigFilePath());

    }

    function getConfigFilePath() {

        var configPath = document.getElementsByTagName('script');

        return configPath[ configPath.length - 1 ].src;

    }

    function getBasePath(docUrl, confUrl) {

        var basePath = confUrl;


        if (/^(\/|\\\\)/.test(confUrl)) {

            basePath = /^.+?\w(\/|\\\\)/.exec(docUrl)[0] + confUrl.replace(/^(\/|\\\\)/, '');

        } else if (!/^[a-z]+:/i.test(confUrl)) {

            docUrl = docUrl.split("#")[0].split("?")[0].replace(/[^\\\/]+$/, '');

            basePath = docUrl + "" + confUrl;

        }

        return optimizationPath(basePath);

    }

    function optimizationPath(path) {

        var protocol = /^[a-z]+:\/\//.exec(path)[ 0 ],
            tmp = null,
            res = [];

        path = path.replace(protocol, "").split("?")[0].split("#")[0];

        path = path.replace(/\\/g, '/').split(/\//);

        path[ path.length - 1 ] = "";

        while (path.length) {

            if (( tmp = path.shift() ) === "..") {
                res.pop();
            } else if (tmp !== ".") {
                res.push(tmp);
            }

        }

        return protocol + res.join("/");

    }

    window.UE = {
        getUEBasePath: getUEBasePath
    };

})();

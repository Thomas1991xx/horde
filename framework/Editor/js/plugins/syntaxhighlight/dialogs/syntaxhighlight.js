CKEDITOR.dialog.add("syntaxhighlight",function(j){var g=function(a){a=a.replace(/<br>/g,"\n");a=a.replace(/&amp;/g,"&");a=a.replace(/&lt;/g,"<");a=a.replace(/&gt;/g,">");a=a.replace(/&quot;/g,'"');return a};var h=function(a){var a=new Object();a.hideGutter=false;a.hideControls=false;a.collapse=false;a.showColumns=false;a.noWrap=false;a.firstLineChecked=false;a.firstLine=0;a.highlightChecked=false;a.highlight=null;a.lang=null;a.code="";return a};var f=function(a){var b=h();if(a){if(a.indexOf("brush")>-1){var c=/brush:[ ]{0,1}(\w*)/.exec(a);if(c!=null&&c.length>0){b.lang=c[1].replace(/^\s+|\s+$/g,"")}}if(a.indexOf("gutter")>-1){b.hideGutter=true}if(a.indexOf("toolbar")>-1){b.hideControls=true}if(a.indexOf("collapse")>-1){b.collapse=true}if(a.indexOf("first-line")>-1){var c=/first-line:[ ]{0,1}([0-9]{1,4})/.exec(a);if(c!=null&&c.length>0&&c[1]>1){b.firstLineChecked=true;b.firstLine=c[1]}}if(a.indexOf("highlight")>-1){if(a.match(/highlight:[ ]{0,1}\[[0-9]+(,[0-9]+)*\]/)){var d=/highlight:[ ]{0,1}\[(.*)\]/.exec(a);if(d!=null&&d.length>0){b.highlightChecked=true;b.highlight=d[1]}}}if(a.indexOf("ruler")>-1){b.showColumns=true}if(a.indexOf("wrap-lines")>-1){b.noWrap=true}}return b};var i=function(a){var b="brush:"+a.lang+";";if(a.hideGutter){b+="gutter:false;"}if(a.hideControls){b+="toolbar:false;"}if(a.collapse){b+="collapse:true;"}if(a.showColumns){b+="ruler:true;"}if(a.noWrap){b+="wrap-lines:false;"}if(a.firstLineChecked&&a.firstLine>1){b+="first-line:"+a.firstLine+";"}if(a.highlightChecked&&a.highlight!=""){b+="highlight: ["+a.highlight.replace(/\s/gi,"")+"];"}return b};return{title:j.lang.syntaxhighlight.title,minWidth:500,minHeight:400,onShow:function(){var c=this.getParentEditor();var d=c.getSelection();var e=d.getStartElement();var a=e&&e.getAscendant("pre",true);var b="";var l=null;if(a){code=g(a.getHtml());l=f(a.getAttribute("class"));l.code=code}else{l=h()}this.setupContent(l)},onOk:function(){var e=this.getParentEditor();var m=e.getSelection();var n=m.getStartElement();var b=n&&n.getAscendant("pre",true);var d=h();this.commitContent(d);var c=i(d);if(b){b.setAttribute("class",c);b.setText(d.code)}else{var a=new CKEDITOR.dom.element("pre");a.setAttribute("class",c);a.setText(d.code);e.insertElement(a)}},contents:[{id:"source",label:j.lang.syntaxhighlight.sourceTab,accessKey:"S",elements:[{type:"vbox",children:[{id:"cmbLang",type:"select",labelLayout:"horizontal",label:j.lang.syntaxhighlight.langLbl,"default":"java",widths:["25%","75%"],items:[["Bash (Shell)","bash"],["C#","csharp"],["C++","cpp"],["CSS","css"],["Delphi","delphi"],["Diff","diff"],["Groovy","groovy"],["Javascript","jscript"],["Java","java"],["Java FX","javafx"],["Perl","perl"],["PHP","php"],["Plain (Text)","plain"],["Python","python"],["Ruby","ruby"],["Scala","scala"],["SQL","sql"],["VB","vb"],["XML/XHTML","xml"]],setup:function(a){if(a.lang){this.setValue(a.lang)}},commit:function(a){a.lang=this.getValue()}}]},{type:"textarea",id:"hl_code",rows:22,style:"width: 100%",setup:function(a){if(a.code){this.setValue(a.code)}},commit:function(a){a.code=this.getValue()}}]},{id:"advanced",label:j.lang.syntaxhighlight.advancedTab,accessKey:"A",elements:[{type:"vbox",children:[{type:"html",html:"<strong>"+j.lang.syntaxhighlight.hideGutter+"</strong>"},{type:"checkbox",id:"hide_gutter",label:j.lang.syntaxhighlight.hideGutterLbl,setup:function(a){this.setValue(a.hideGutter)},commit:function(a){a.hideGutter=this.getValue()}},{type:"html",html:"<strong>"+j.lang.syntaxhighlight.hideControls+"</strong>"},{type:"checkbox",id:"hide_controls",label:j.lang.syntaxhighlight.hideControlsLbl,setup:function(a){this.setValue(a.hideControls)},commit:function(a){a.hideControls=this.getValue()}},{type:"html",html:"<strong>"+j.lang.syntaxhighlight.collapse+"</strong>"},{type:"checkbox",id:"collapse",label:j.lang.syntaxhighlight.collapseLbl,setup:function(a){this.setValue(a.collapse)},commit:function(a){a.collapse=this.getValue()}},{type:"html",html:"<strong>"+j.lang.syntaxhighlight.showColumns+"</strong>"},{type:"checkbox",id:"show_columns",label:j.lang.syntaxhighlight.showColumnsLbl,setup:function(a){this.setValue(a.showColumns)},commit:function(a){a.showColumns=this.getValue()}},{type:"html",html:"<strong>"+j.lang.syntaxhighlight.lineWrap+"</strong>"},{type:"checkbox",id:"line_wrap",label:j.lang.syntaxhighlight.lineWrapLbl,setup:function(a){this.setValue(a.noWrap)},commit:function(a){a.noWrap=this.getValue()}},{type:"html",html:"<strong>"+j.lang.syntaxhighlight.lineCount+"</strong>"},{type:"hbox",widths:["5%","95%"],children:[{type:"checkbox",id:"lc_toggle",label:"",setup:function(a){this.setValue(a.firstLineChecked)},commit:function(a){a.firstLineChecked=this.getValue()}},{type:"text",id:"default_lc",style:"width: 15%;",label:"",setup:function(a){if(a.firstLine>1){this.setValue(a.firstLine)}},commit:function(a){if(this.getValue()&&this.getValue()!=""){a.firstLine=this.getValue()}}}]},{type:"html",html:"<strong>"+j.lang.syntaxhighlight.highlight+"</strong>"},{type:"hbox",widths:["5%","95%"],children:[{type:"checkbox",id:"hl_toggle",label:"",setup:function(a){this.setValue(a.highlightChecked)},commit:function(a){a.highlightChecked=this.getValue()}},{type:"text",id:"default_hl",style:"width: 40%;",label:"",setup:function(a){if(a.highlight!=null){this.setValue(a.highlight)}},commit:function(a){if(this.getValue()&&this.getValue()!=""){a.highlight=this.getValue()}}}]},{type:"hbox",widths:["5%","95%"],children:[{type:"html",html:""},{type:"html",html:"<i>"+j.lang.syntaxhighlight.highlightLbl+"</i>"}]}]}]}]}});
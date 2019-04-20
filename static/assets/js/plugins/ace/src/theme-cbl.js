define("ace/theme/cbl",["require","exports","module","ace/lib/dom"], function(require, exports, module) {

exports.isDark = false;
exports.cssClass = "ace-cbl";
exports.cssText = "\
*/\
.ace-cbl .ace_gutter {\
background: #fef6e4;\
color: #E2E2E2\
}\
.ace-cbl .ace_print-margin {\
width: 1px;\
background: #fef6e4;\
}\
.ace-cbl {\
background-color: #141414;\
color: #546e74;\
}\
.ace-cbl .ace_cursor {\
color: #000000;\
}\
.ace-cbl .ace_marker-layer .ace_selection {\
background: rgba(221, 240, 255, 0.20)\
}\
.ace-cbl.ace_multiselect .ace_selection.ace_start {\
box-shadow: 0 0 3px 0px #141414;\
}\
.ace-cbl .ace_marker-layer .ace_step {\
background: rgb(102, 82, 0)\
}\
.ace-cbl .ace_marker-layer .ace_bracket {\
margin: -1px 0 0 -1px;\
border: 1px solid rgba(255, 255, 255, 0.25)\
}\
.ace-cbl .ace_marker-layer .ace_active-line {\
background-color: rgba(0, 0, 0, 0.031);\
}\
.ace-cbl .ace_gutter-active-line {\
background-color: rgba(0, 0, 0, 0.031);\
}\
.ace-cbl .ace_marker-layer .ace_selected-word {\
border: 1px solid rgba(221, 240, 255, 0.20)\
}\
.ace-cbl .ace_invisible {\
color: rgba(255, 255, 255, 0.25)\
}\
.ace-cbl .ace_keyword,\
.ace-cbl .ace_meta {\
color: #CDA869\
}\
.ace-cbl .ace_constant,\
.ace-cbl .ace_constant.ace_character,\
.ace-cbl .ace_constant.ace_character.ace_escape,\
.ace-cbl .ace_constant.ace_other,\
.ace-cbl .ace_heading,\
.ace-cbl .ace_markup.ace_heading,\
.ace-cbl .ace_support.ace_constant {\
color: #008CCE;\
}\
.ace-cbl .ace_title,\
.ace-cbl .ace_subtitle,\
.ace-cbl .ace_thirdtitle,\
.ace-cbl .ace_title.ace_num,\
.ace-cbl .ace_subtitle.ace_num,\
.ace-cbl .ace_thirdtitle.ace_num {\
color: #6c71c1;\
}\
.ace-cbl .ace_invalid.ace_illegal {\
color: #F8F8F8;\
background-color: rgba(86, 45, 86, 0.75)\
}\
.ace-cbl .ace_invalid.ace_deprecated {\
text-decoration: underline;\
font-style: italic;\
color: #D2A8A1\
}\
.ace-cbl .ace_support {\
color: #9B859D\
}\
.ace-cbl .ace_fold {\
background-color: #AC885B;\
border-color: #F8F8F8\
}\
.ace-cbl .ace_support.ace_function {\
color: #819a23;\
}\
.ace-cbl .ace_markup.ace_list,\
.ace-cbl .ace_storage {\
color: #ba8823\
}\
.ace-cbl .ace_entity.ace_name.ace_function,\
.ace-cbl .ace_meta.ace_tag,\
.ace-cbl .ace_variable {\
color: #AC885B\
}\
.ace-cbl .ace_string,\
.ace-cbl .ace_markup.ace_quote {\
color: #e12781;\
}\
.ace-cbl .ace_quote {\
font-style: italic;\
}\
.ace-cbl .ace_string.ace_regexp {\
color: #E9C062\
}\
.ace-cbl .ace_comment {\
font-style: italic;\
color: #5F5A60\
}\
.ace-cbl .ace_variable {\
color: #7587A6\
}\
.ace-cbl .ace_xml-pe {\
color: #494949\
}\
.ace-cbl .ace_indent-guide {\
background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAACCAYAAACZgbYnAAAAEklEQVQImWMQERFpYLC1tf0PAAgOAnPnhxyiAAAAAElFTkSuQmCC) right repeat-y\
}";

var dom = require("../lib/dom");
dom.importCssString(exports.cssText, exports.cssClass);
});

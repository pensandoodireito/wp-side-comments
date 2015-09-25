/**
 * Created by josafa on 24/09/15.
 */
jQuery(document).ready(function ($) {
    var editorTheme = "ace/theme/monokai";

    var styleEditor;
    var commentTemplateEditor;
    var sectionTemplateEditor;

    jQuery('input#submit').click(function (e) {
        prepareForm();
    });

    function createEditor(element, mode) {
        var editor = ace.edit(element);
        editor.setTheme(editorTheme);
        editor.getSession().setMode(mode);
        editor.getSession().setUseWorker(false);
        editor.$blockScrolling = Infinity;

        return editor;
    }

    function activateStyleEditor() {
        styleEditor = createEditor(data.styleEditorID, "ace/mode/css");
        styleEditor.setValue(css_beautify(styleEditor.getValue()));
        styleEditor.clearSelection();
    }

    function activateCommentTemplateEditor() {
        commentTemplateEditor = createEditor(data.commentTemplateEditorID, "ace/mode/html");
    }

    function activateSectionTemplateEditor() {
        sectionTemplateEditor = createEditor(data.sectionTemplateEditorID, "ace/mode/html");
    }

    function prepareForm() {
        jQuery('#' + data.styleFieldID).html(styleEditor.getValue());
        jQuery('#' + data.commentTemplateFieldID).html(commentTemplateEditor.getValue());
        jQuery('#' + data.sectionTemplateFieldID).html(sectionTemplateEditor.getValue());
    }

    activateStyleEditor();
    activateCommentTemplateEditor();
    activateSectionTemplateEditor();
});
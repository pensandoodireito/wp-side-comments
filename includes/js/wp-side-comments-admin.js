/**
 * Created by josafa on 24/09/15.
 */
jQuery(document).ready(function ($) {
    var editorTheme = "ace/theme/monokai";

    var styleEditor;
    var commentTemplateEditor;
    var sectionTemplateEditor;

    var customSectionUseSelector = 'input[name=' + data.optionsName + '\\[' + data.useCustomSectionID + '\\]]';
    var customCommentUseSelector = 'input[name=' + data.optionsName + '\\[' + data.useCustomCommentID + '\\]]';
    var customStyleSelector = 'input[name=' + data.optionsName + '\\[' + data.useCustomStyleID + '\\]]';

    jQuery('input#submit').click(function (e) {
        prepareForm();
    });

    jQuery(customSectionUseSelector).change(function (e) {
        checkCustomSectionUsage();
    });

    jQuery(customCommentUseSelector).change(function (e) {
        checkCustomCommentUsage();
    });

    jQuery(customStyleSelector).change(function (e) {
        checkCustomStyleUsage();
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

    function checkCustomSectionUsage() {
        var element = jQuery(customSectionUseSelector + ':checked');
        if (element.val() == 'S') {
            element.parents('tr').next().show();
            activateSectionTemplateEditor();
        } else {
            element.parents('tr').next().hide();
        }
    }

    function checkCustomCommentUsage() {
        var element = jQuery(customCommentUseSelector + ':checked');
        if (element.val() == 'S') {
            element.parents('tr').next().show();
            activateCommentTemplateEditor();
        } else {
            element.parents('tr').next().hide();
        }
    }

    function checkCustomStyleUsage() {
        var element = jQuery(customStyleSelector + ':checked');
        if (element.val() == 'S') {
            element.parents('tr').next().show();
            activateStyleEditor();
        } else {
            element.parents('tr').next().hide();
        }
    }

    function elementExists() {
        return $('#' + data.styleEditorID).length != 0
    }

    if (elementExists()) {
        activateStyleEditor();
        activateCommentTemplateEditor();
        activateSectionTemplateEditor();

        checkCustomSectionUsage();
        checkCustomCommentUsage();
        checkCustomStyleUsage();
    }

});
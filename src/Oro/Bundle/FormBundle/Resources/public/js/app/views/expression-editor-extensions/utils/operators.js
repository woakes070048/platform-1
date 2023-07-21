import __ from 'orotranslation/js/translator';
import {snippet} from '@codemirror/autocomplete';

export const OPERATORS_SNIPPET_TEMPLATES = {
    '=': '= #{1}',
    '==': '== #{1}',
    '!=': '!= #{1}',
    '>=': '>= #{1}',
    '>': '> #{1}',
    '<=': '<= #{1}',
    '<': '< #{1}',
    '*': '* #{1}',
    '/': '/ #{1}',
    '%': '% #{1}',
    '+': '+ #{1}',
    '-': '- #{1}',
    'and': 'and #{1}',
    'or': 'or #{1}',
    'in': 'in [#{1}]',
    'not in': 'not in [#{1}]',
    'matches': 'matches containsRegExp(#{1})',
    '()': '(#{1})'
};

export const getOperatorSnippet = name => {
    const tpl = OPERATORS_SNIPPET_TEMPLATES[name];

    if (!tpl) {
        return name;
    }

    return snippet(tpl);
};

export const operatorsDetailMap = {
    '=': __('oro.form.expression_editor.operators.detail.equal'),
    '==': __('oro.form.expression_editor.operators.detail.equal'),
    '!=': __('oro.form.expression_editor.operators.detail.not_equal'),
    '>=': __('oro.form.expression_editor.operators.detail.greater_than_or_equal'),
    '>': __('oro.form.expression_editor.operators.detail.greater_than'),
    '<=': __('oro.form.expression_editor.operators.detail.less_than_or_equal'),
    '<': __('oro.form.expression_editor.operators.detail.less_than'),
    '*': __('oro.form.expression_editor.operators.detail.multiplication'),
    '/': __('oro.form.expression_editor.operators.detail.division'),
    '%': __('oro.form.expression_editor.operators.detail.remainder'),
    '+': __('oro.form.expression_editor.operators.detail.addition'),
    '-': __('oro.form.expression_editor.operators.detail.subtraction')
};

import template from './sw-cms-el-html.html.twig';
import './sw-cms-el-html.scss';

const { Mixin } = Shopware;

/**
 * @private
 * @package discovery
 */
export default {
    template,

    compatConfig: Shopware.compatConfig,

    mixins: [
        Mixin.getByName('cms-element'),
    ],

    data() {
        return {
            editorConfig: {
                highlightActiveLine: false,
                cursorStyle: 'slim',
                highlightGutterLine: false,
                showFoldWidgets: false,
            },
        };
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('html');
        },
    },
};

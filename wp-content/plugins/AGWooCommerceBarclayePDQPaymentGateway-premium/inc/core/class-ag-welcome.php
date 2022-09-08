<?php
/*-----------------------------------------------------------------------------------*/
/*	AG Welcome/Account Screen
/*-----------------------------------------------------------------------------------*/
defined('ABSPATH') || die("No script kiddies please!");

if (class_exists('AG_welcome_screen')) {
    return;
}

/**
 * AG welcome screen
 */
class AG_welcome_screen
{


    /**
     * Doc url
     * @var string
     */
    public static $AG_doc_url = 'https://weareag.co.uk/docs/';


    /**
     * AG SVG
     * @var string
     */
    public static $ag_svg = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDIzLjAuMCwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPgo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IgoJIHZpZXdCb3g9IjAgMCAxODIgMTI1IiBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCAxODIgMTI1OyIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+CjxzdHlsZSB0eXBlPSJ0ZXh0L2NzcyI+Cgkuc3Qwe2ZpbGw6IzM4MzgzODt9Cjwvc3R5bGU+CjxwYXRoIGNsYXNzPSJzdDAiIGQ9Ik03OS4xOCwxMDkuNGMtMC43NS0wLjM2LTEtMS4yMi0wLjYyLTEuODNsNDEuOTctOTEuODJjMC4zOC0wLjYxLDEuMjUtMC44NiwyLTAuMzdjMC42MiwwLjM3LDAuODcsMS4xLDAuNSwxLjgzCglsLTQxLjk4LDkxLjdDODAuNjcsMTA5LjY1LDc5LjkyLDEwOS43Nyw3OS4xOCwxMDkuNHoiLz4KPGc+Cgk8cGF0aCBjbGFzcz0ic3QwIiBkPSJNODQuMyw3NC4wMWwxLjY3LTMuNjVMNjIuNywxOS4xNmMtMC41NS0xLjIzLTIuNDYtMS4zNC0zLjAxLDBsLTQwLjEyLDg3LjkzYy0wLjQxLDAuNjEsMCwxLjU5LDAuODIsMS44MwoJCWMwLjgzLDAuMjQsMS43OCwwLDIuMDYtMC44Nkw2MS4yLDIzLjMyTDg0LjMsNzQuMDF6Ii8+Cgk8cGF0aCBjbGFzcz0ic3QwIiBkPSJNNTIuMyw4Mi4wMmMwLDAuNzQsMC44MiwxLjM0LDEuNjQsMS4zNGgxNC4zOGMwLjk2LDAsMS42NC0wLjYxLDEuNjQtMS4zNGMwLTAuMjQsMC0wLjM2LTAuMTMtMC42MUw2Mi43LDY1Ljc1CgkJYy0wLjQxLTAuODYtMS4yMy0xLjIyLTIuMTktMC44NmMtMC42OSwwLjI1LTEuMDksMS4xMS0wLjgyLDEuODRsNi40MywxMy45NEg1My45NEM1My4xMiw4MC42Nyw1Mi4zLDgxLjI4LDUyLjMsODIuMDJ6Ii8+Cgk8cGF0aCBjbGFzcz0ic3QwIiBkPSJNNzMuMiw5OC4yOEg0NmMtMC42OSwwLTEuMzcsMC4yNC0xLjUsMC44NmwtMy43LDcuOTVjLTAuNDEsMC42MSwwLDEuNTksMC45NiwxLjgzYzAuODMsMC4yNCwxLjY0LDAsMS45Mi0wLjg2CgkJbDMuMjktNy4wOWgyNS4wMUw3My4yLDk4LjI4eiIvPgo8L2c+CjxnPgoJPHBhdGggY2xhc3M9InN0MCIgZD0iTTEyOS4zOCwyMi43N2MxLjY2LTAuMTcsMy4yOC0wLjM5LDUuMDUtMC40M2M3LjQ3LTAuMjQsMTQuNjgsMC44NiwyMS43NiwyLjkzYzAuOCwwLjI0LDEuNi0wLjEyLDItMC44NgoJCWMwLjI3LTAuNzMtMC4yNy0xLjU5LTEuMDctMS44M2MtNy40OC0yLjItMTQuOTUtMy40My0yMi44My0zLjE4Yy0xLjIzLDAuMDQtMi4zMywwLjIyLTMuNTIsMC4zMkwxMjkuMzgsMjIuNzd6Ii8+Cgk8cGF0aCBjbGFzcz0ic3QwIiBkPSJNMTU1LjY1LDQyLjE1YzAuNC0wLjYxLDAtMS40Ny0wLjgtMS44NGMtNi40MS0yLjY5LTEzLjYxLTMuNDItMjAuNTYtMy4xOGMtNC44MywwLjE2LTkuMDMsMS4xNC0xMi42OSwyLjYzCgkJbC0xLjkzLDQuMjJjMy45NC0yLjI4LDguODItMy43NiwxNC43Ni0zLjkyYzYuNTQtMC4zNiwxMy4wOCwwLjM3LDE5LjIyLDIuODFDMTU0LjQ1LDQzLjI1LDE1NS4zOCw0Mi44OCwxNTUuNjUsNDIuMTV6Ii8+Cgk8cGF0aCBjbGFzcz0ic3QwIiBkPSJNMTM0LjI5LDkzLjI3YzEuODcsMCwzLjc0LTAuMTIsNS40Ny0wLjQ5YzAuOCwwLDEuMzQtMC42MSwxLjM0LTEuMzRWNjUuMTRjMC0wLjc0LTAuNjctMS4zNS0xLjQ3LTEuMzUKCQljLTAuOTMsMC0xLjYsMC42MS0xLjYsMS4zNXYyNC45NGMtMS4zNCwwLjI1LTIuNTQsMC4yNS0zLjc0LDAuMjVjLTEyLjQxLDAtMjAuNDItNi4xMi0yNC4yOS0xNC4xOWMtMS4wMy0yLjA4LTEuNjMtNC4zMy0yLjAyLTYuNjIKCQlMMTA2LDczLjg0YzAuMzcsMS4xNSwwLjY3LDIuMzIsMS4xOSwzLjQxQzExMS4zNCw4Ni4zLDEyMC41NCw5My4yNywxMzQuMjksOTMuMjd6Ii8+Cgk8cGF0aCBjbGFzcz0ic3QwIiBkPSJNMTU4Ljk4LDYzLjc5Yy0wLjgsMC0xLjYsMC42MS0xLjYsMS4zNXYzNy41NGMtNy4wOCwzLjY3LTE1LjA5LDUuMzgtMjMuMSw1LjM4CgkJYy0xNi42OSwwLTI5LjAzLTYuMjktMzYuODktMTUuNDRsLTEuMzcsMi45OWM4LjM5LDkuMTYsMjEuMiwxNS4zOSwzOC4yNSwxNS4zOWM4Ljk0LDAsMTcuNjItMi4yMSwyNS41LTYuMjQKCQljMC40LTAuMzcsMC44LTAuODYsMC42Ny0xLjM0VjY1LjE0QzE2MC40Niw2NC40MSwxNTkuNzksNjMuNzksMTU4Ljk4LDYzLjc5eiIvPgo8L2c+Cjwvc3ZnPgo=';


    public static $single_instance = null;
    public static $args = array();

    /**
     * Construct
     */
    private function __construct()
    {
        if (!AG_licence::valid_licence()) {
            return;
        }

        add_action('admin_menu', array($this, 'ag_welcome_screen'), 20);

    }

    /**
     * run instance
     */
    public static function run_instance($args = array())
    {
        if (self::$single_instance === null) {
            self::$args = $args;
            self::$single_instance = new self();
        }

        return self::$single_instance;
    }

    /**
     * Getting started
     */
    public static function getting_started()
    {

        $option_name = 'ag_dismiss_welcome';
        $dismissed = get_option($option_name, false);

        if ($dismissed) {
            return;
        }

        $dismiss = filter_input(INPUT_POST, $option_name);

        if ($dismiss) {
            update_option($option_name, true);

            return;
        }


        ?>
        <div class="ag-notice ag-notice--getting-started">
            <form action="" method="post" class="ag-notice__dismiss">
                <input type="hidden" name="ag_dismiss_welcome" value="1">
                <button title="Dismiss" class="is-dismissible">
                    Hide <span class="dashicons dashicons-dismiss"></span></button>
            </form>
            <h2><img height="35" style="display: inline-block; vertical-align: text-bottom; margin: 0 8px 0 0"
                     src="<?php echo esc_attr(self::$ag_svg); ?>">Welcome
                to <?php echo self::$args['plugin_title']; ?>!</h2>
            <p>Thank you for choosing We are AG as your payment gateway partner.<br/>Below are some useful links to help
                you get started:</p>
            <?php $docLinks = AG_start_here_docs::output_doc_links(); ?>

        </div>
        <?php

    }

    /**
     * Add Welcome page
     */
    public function ag_welcome_screen()
    {


        $hook_suffix = add_menu_page(
            'Welcome to AG',
            'AG Plugins',
            'manage_options',
            htmlspecialchars(self::$args['parent_slug']),
            array($this, 'setup_welcome_page'),
            'dashicons-admin-network'
        );
        add_action("admin_print_styles-{$hook_suffix}", array($this, 'ePDQ_admin_css'));

    }

    public function ePDQ_admin_css()
    {
        wp_enqueue_style('ePDQ_admin', AG_ePDQ_path . 'assets/css/admin-style.css');
    }

    /**
     * Setting up the welcome page
     */
    public function setup_welcome_page()
    {

        $page_title = sprintf('<div style="padding-bottom: 15px;">%s from <a href="https://weareag.co.uk/?utm_source=ePDQ-Direct&utm_medium=insideplugin" target="_blank">We are AG</a> <em style="opacity: 0.6; font-size: 80%%;">(v%s)</em></div>', self::$args['plugin_title'], self::$args['plugin_version']);

        self::getting_started();

        ?>

        <div class="wrap ag-welcome-wrap">
            <h2><?php echo $page_title; ?></h2>

            <div class="ag-welcome-body">

                <h2>Account, Licence settings & Affiliation</h2>
                <div class="section">

                    <a class="product-card" target="_blank"
                       href="<?php echo admin_url('admin.php?page=' . self::$args['main_slug'] . '-account'); ?>"
                       style="width: 378.667px; float: left; margin-right: 32px">
                        <div class="product-card-content">
                            <div class="product-card-main">
                                <h3 class="heading-sm"><?php echo __('License &amp; Billing', 'ag_epdq_server'); ?></h3>
                                <div class="body">
                                    <p><?php echo __('Activate or sync your license, cancel your subscription, print invoices, and manage your account information.', 'ag_epdq_server'); ?></p>
                                </div>
                            </div>
                            <div class="product-card-footer">
                                <div class="btn btn-sm btn-stroke"><?php echo __('Manage Licence & Billing', 'ag_epdq_server'); ?></div>
                            </div>
                        </div>
                    </a>

                    <a class="product-card" target="_blank"
                       href="https://weareag.co.uk/account?utm_source=<?php echo self::$args['main_slug']; ?>&amp;utm_medium=insideplugin"
                       style="width: 378.667px; float: left; margin-right: 32px">
                        <div class="product-card-content">
                            <div class="product-card-main">
                                <h3 class="heading-sm"><?php echo __('Your Account', 'ag_epdq_server'); ?></h3>
                                <div class="body">
                                    <p><?php echo __('Manage all of your AG plugins, subscriptions, renewals, and more.', 'ag_epdq_server'); ?></p>
                                </div>
                            </div>
                            <div class="product-card-footer">
                                <div class="btn btn-sm btn-stroke"><?php echo __('Manage Your Account', 'ag_epdq_server'); ?></div>
                            </div>
                        </div>
                    </a>

                    <a class="product-card" target="_blank"
                       href="<?php echo admin_url('admin.php?page=' . self::$args['main_slug'] . '-affiliation'); ?>"
                       style="width: 378.667px; float: left; margin-right: 32px">
                        <div class="product-card-content">
                            <div class="product-card-main">
                                <h3 class="heading-sm"><?php echo __('Affiliate', 'ag_epdq_server'); ?></h3>
                                <div class="body">
                                    <p><?php echo __('Become an ambassador for AG and earn 20% commission for each sale!', 'ag_epdq_server'); ?></p>
                                </div>
                            </div>
                            <div class="product-card-footer">
                                <div class="btn btn-sm btn-stroke"><?php echo __('Find Out More', 'ag_epdq_server'); ?></div>
                            </div>
                        </div>
                    </a>

                    <div style="clear: both"></div>
                </div>
                <h2>Getting the help you need</h2>
                <div class="section">

                    <a class="product-card" target="_blank"
                       href="https://weareag.co.uk/support?utm_source=<?php echo self::$args['main_slug']; ?>&amp;utm_medium=insideplugin"
                       target="_blank" style="width: 378.667px; float: left; margin-right: 32px">
                        <div class="product-card-content">
                            <div class="product-card-main">
                                <h3 class="heading-sm"><?php echo __('Getting Support', 'ag_epdq_server'); ?></h3>
                                <div class="body">
                                    <p><?php echo __('Get premium support with a valid licence', 'ag_epdq_server'); ?></p>
                                </div>
                            </div>
                            <div class="product-card-footer">
                                <div class="btn btn-sm btn-stroke"><?php echo __('Submit a ticket', 'ag_epdq_server'); ?></div>
                            </div>
                        </div>
                    </a>

                    <a class="product-card" target="_blank"
                       href="<?php echo self::$AG_doc_url . self::$args['collection']; ?>" target="_blank"
                       style="width: 378.667px; float: left; margin-right: 32px">
                        <div class="product-card-content">
                            <div class="product-card-main">
                                <h3 class="heading-sm"><?php echo __('Documentation', 'ag_epdq_server'); ?></h3>
                                <div class="body">
                                    <p><?php echo __('Have a read of the plugin documentation.', 'ag_epdq_server'); ?></p>
                                </div>
                            </div>
                            <div class="product-card-footer">
                                <div class="btn btn-sm btn-stroke"><?php echo __('Documentation', 'ag_epdq_server'); ?></div>
                            </div>
                        </div>
                    </a>

                    <a class="product-card" target="_blank"
                       href="https://weareag.co.uk/feature-requests?utm_source=<?php echo self::$args['main_slug']; ?>&amp;utm_medium=insideplugin"
                       style="width: 378.667px; float: left; margin-right: 32px">
                        <div class="product-card-content">
                            <div class="product-card-main">
                                <h3 class="heading-sm"><?php echo __('Feature Request', 'ag_epdq_server'); ?></h3>
                                <div class="body">
                                    <p><?php echo __('Have a feature you\'d love to be part of the plugin?', 'ag_epdq_server'); ?></p>
                                </div>
                            </div>
                            <div class="product-card-footer">
                                <div class="btn btn-sm btn-stroke"><?php echo __('Submit a feature request', 'ag_epdq_server'); ?></div>
                            </div>
                        </div>
                    </a>

                    <a class="product-card" target="_blank"
                       href="<?php echo esc_url_raw(admin_url('?page=AG_ePDQ-wizard')); ?>"
                       style="width: 378.667px; float: left; margin-right: 32px">
                        <div class="product-card-content">
                            <div class="product-card-main">
                                <h3 class="heading-sm"><?php echo __('Setup Wizard', 'ag_epdq_server'); ?></h3>
                                <div class="body">
                                    <p><?php echo __('Struggling to get setup?', 'ag_epdq_server'); ?></p>
                                </div>
                            </div>
                            <div class="product-card-footer">
                                <div class="btn btn-sm btn-stroke"><?php echo __('Use the wizard', 'ag_epdq_server'); ?></div>
                            </div>
                        </div>
                    </a>

                    <div style="clear: both"></div>
                </div>


            </div>

        </div>
        <?php
    }

}

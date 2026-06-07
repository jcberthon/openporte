<?php

if ( ! defined( 'ABSPATH' ) ) exit;

function openporte_options_page_html()
{
  wp_enqueue_script(
    'altcha-admin-js',
    OpenPortePlugin::$admin_script_src,
    array(),
    OPENPORTE_VERSION,
    true
  );
  wp_enqueue_style(
    'altcha-admin-styles',
    OpenPortePlugin::$admin_css_src,
    array(),
    OPENPORTE_VERSION,
    'all'
  );
?>
  <div class="altcha-head">
    <div class="altcha-logo">
      <svg xmlns="http://www.w3.org/2000/svg" width="256" height="256" fill="none" viewBox="38.25 42.50 179.50 179.50">
        <path stroke="#777" stroke-linejoin="round" stroke-width="4" d="m118 62 10-10 10 10-3 12h-14z" style="fill:none;fill-opacity:1;stroke:#2b3d4f;stroke-width:3;stroke-dasharray:none;stroke-opacity:1"/>
        <g style="fill:none;fill-opacity:1;stroke:#808d8e;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-dasharray:none;stroke-opacity:1">
          <path d="m93.5 71.15 9.85-4.92 4.91 12.08-8.05 4.02zm11.64-5.61 10.46-3.4 3.07 12.67-8.56 2.78zm-45.59 43.2 5.23-9.68 10.96 7.06-4.27 7.92zm6.22-11.27 6.65-8.76 9.75 8.66-5.44 7.17zm7.86-10.2 7.87-7.68 8.37 10-6.44 6.29zm9.22-8.87 8.96-6.37 6.72 11.17-7.33 5.22zM52.2 133.34l2.12-10.8 12.57 3.49-1.74 8.83zm2.73-12.61 3.73-10.35 11.89 5.34-3.05 8.47zm10.09 15.97v11h-13v-12.5zm0 25.96v11h-13v-11zm0-12.98v11h-13v-11zm0 25.96v11h-13v-11zm0 12.98v11h-13v-11zm0 12.98v11h-13v-11z"/>
          <path d="m93.5 71.15 9.85-4.92 4.91 12.08-8.05 4.02zm11.64-5.61 10.46-3.4 3.07 12.67-8.56 2.78zm-45.59 43.2 5.23-9.68 10.96 7.06-4.27 7.92zm6.22-11.27 6.65-8.76 9.75 8.66-5.44 7.17zm7.86-10.2 7.87-7.68 8.37 10-6.44 6.29zm9.22-8.87 8.96-6.37 6.72 11.17-7.33 5.22zM52.2 133.34l2.12-10.8 12.57 3.49-1.74 8.83zm2.73-12.61 3.73-10.35 11.89 5.34-3.05 8.47zm10.09 15.97v11h-13v-12.5zm0 25.96v11h-13v-11zm0-12.98v11h-13v-11zm0 25.96v11h-13v-11zm0 12.98v11h-13v-11zm0 12.98v11h-13v-11z" transform="matrix(-1,0,0,1,255.86696,-1.446623e-5)"/>
        </g>
        <path d="M93.53 177.74q0 7.32.98 12.78a26 26 0 0 0 3.25 9.1 14.5 14.5 0 0 0 5.86 5.46q3.58 1.8 8.87 1.79 5.2 0 8.78-1.8a14.3 14.3 0 0 0 5.94-5.44 26 26 0 0 0 3.26-9.11q.97-5.46.97-12.78 0-5.61-.73-10.82a29 29 0 0 0-2.85-9.35 16 16 0 0 0-5.77-6.6q-3.66-2.43-9.6-2.43-5.94-.01-9.68 2.44a17 17 0 0 0-5.78 6.59 30 30 0 0 0-2.77 9.35 78 78 0 0 0-.73 10.82m-9.36 0q0-6.5 2.53-12.04a35 35 0 0 1 6.59-9.6 31 31 0 0 1 9.1-6.34 24 24 0 0 1 10.1-2.36q5.37 0 10.41 2.36a29 29 0 0 1 9.11 6.34 31 31 0 0 1 6.35 9.6 29.5 29.5 0 0 1 2.44 12.04q0 5.7-2.2 11.15a32 32 0 0 1-6.02 9.68 30 30 0 0 1-9.03 6.83 24 24 0 0 1-11.06 2.6 25.6 25.6 0 0 1-11.31-2.51 30 30 0 0 1-9.03-6.68 32 32 0 0 1-7.98-21.07m56.53-.08q1.62.33 3.42.49t3.17.16q6.01 0 9.36-3.5 3.4-3.57 3.41-10.74 0-4.14-.81-6.91a9.3 9.3 0 0 0-7.16-6.75 30 30 0 0 0-6.67-.65 46 46 0 0 0-4.72.24zm-17.65 28 9.51-.01V150h-9.12l-2.7-1.14h20.37l2.27-.16h2.2q1.14-.09 2.28-.08 4.65 0 8.54 1.14 3.99 1.05 6.84 3.09a15 15 0 0 1 4.55 4.88 12.6 12.6 0 0 1 1.63 6.35q0 3.66-1.87 6.5-1.87 2.85-4.96 4.8a25 25 0 0 1-7.25 3.01 34 34 0 0 1-11.47.9q-1.79-.25-3.17-.49v26.85h9.03v1.14h-29.47z" aria-label="OP" style="fill:#3b556e;stroke-width:2.08018;stroke-linecap:round;stroke-linejoin:round"/>
      </svg>
    </div>

    <div style="flex-grow: 1;">
      <div class="altcha-title"><?php echo esc_html__('OpenPorte', 'openporte'); ?></div>
      <div class="altcha-subtitle"><?php echo esc_html__('A Privacy-Friendly Captcha Alternative.', 'openporte'); ?></div>
    </div>

    <div>
      <div style="margin-bottom: 0.3rem;"><b><?php echo esc_html__('Do you like OpenPorte?', 'openporte'); ?></b></div>
      <div style="display:flex;gap: 0.5rem;">
        <a href="https://wordpress.org/support/plugin/openporte/reviews/" target="_blank" style="display: inline-flex; gap: 0.5rem;">
          <span><?php echo esc_html__('Review it!', 'openporte'); ?></span>
        </a>
      </div>
    </div>
  </div>

  <div class="wrap">

    <hr>

    <form action="options.php" method="post">
      <?php
      settings_errors();
      settings_fields('openporte_options');
      do_settings_sections('openporte_admin');
      submit_button();
      ?>
    </form>

    <div style="opacity: 0.8;">
      <p><?php
        /* translators: %1$s is the plugin version, and %2$s is the ALTCHA widget version */
        echo sprintf(
          esc_html__(
              'OpenPorte Spam Protection for WordPress, plugin version %1$s, ALTCHA widget version %2$s',
              'openporte',
          ),
          esc_html( OpenPortePlugin::$version ),
          esc_html( OpenPortePlugin::$widget_version ),
        );
      ?></p>
      <p>
        <?php
        echo sprintf(
          esc_html__(
            /* translators: the placeholders are opening and closing tags for a link (<a> tag) */
            'Please rate OpenPorte on WordPress.org to help us get the word out.',
            'openporte',
          ),
          '<a href="https://wordpress.org/support/plugin/openporte/reviews/" target="_blank">',
          '</a>',
        ); ?>
      </p>
      <p>
        <?php
        echo sprintf(
          /* translators: %1$s and %2$s are the opening and closing tags of a link to the ALTCHA project. */
          esc_html__('Powered by the %1$sALTCHA%2$s proof-of-work open-source project.', 'openporte'),
          '<a href="https://github.com/altcha-org/altcha" target="_blank">',
          '</a>',
        ); ?>
      </p>
      <p>
        <a href="https://github.com/jcberthon/openporte" target="_blank" style="display: inline-flex; gap: 0.3rem;">
          <span><?php echo esc_html__('Star OpenPorte on GitHub!', 'openporte'); ?></span>
        </a>
      </p>
    </div>
  </div>
<?php
}

function openporte_general_section_callback()
{
  ?>
    <p><?php
      /* translators: the placeholders are opening and closing tags for bold */
      echo sprintf(
        esc_html__(
          'Both modes run without any external paid service. %1$sSelf-hosted%2$s generates challenges via the WordPress REST API. %3$sCustom%4$s lets you point to your own ALTCHA-compatible backend.',
          'openporte',
        ),
        '<b>',
        '</b>',
        '<b>',
        '</b>',
      );
    ?></p>
  <?php
}

function openporte_spam_filter_section_callback()
{
  ?>
    <p><?php
      /* translators: the placeholders are opening and closing tags for bold */
      echo sprintf(
        esc_html__(
          'The Spam Filter acts on the classification returned by a %1$sCustom%2$s backend. It has no effect in %3$sSelf-hosted%4$s mode, which uses proof-of-work only.',
          'openporte',
        ),
        '<b>',
        '</b>',
        '<b>',
        '</b>',
      );
    ?></p>
  <?php
}

function openporte_widget_section_callback()
{
  ?>

    <p><?php echo esc_html__('Customize the widget to fit your needs.', 'openporte'); ?></p>

  <?php
}

function openporte_integrations_section_callback()
{
  ?>

    <p><?php echo esc_html__('Activate OpenPorte for these integrations:', 'openporte'); ?></p>

  <?php
}

function openporte_wordpress_section_callback()
{
  ?>

    <p><?php echo esc_html__('Activate OpenPorte for the core Wordpress functionality:', 'openporte'); ?></p>

  <?php
}

function openporte_settings_field_callback(array $args)
{
  $type = $args['type'];
  $name = $args['name'];
  $hint = isset($args['hint']) ? $args['hint'] : null;
  $custom = isset($args['custom']) ? $args['custom'] : '';
  $spamfilter = isset($args['spamfilter']) ? $args['spamfilter'] : '';
  $description = isset($args['description']) ? $args['description'] : null;
  $setting = get_option($name);
  $value = isset($setting) ? esc_attr($setting) : '';
  if ($type == "checkbox") {
    $value = 1;
  }
?>
  <input autcomplete="none" class="regular-text"  <?php echo $custom === true ? ' data-custom-api' : ''; ?> <?php echo $spamfilter === true ? ' data-spamfilter' : ''; ?> type="<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value) ?>" <?php $type == "checkbox" ? checked(1, $setting, true) : "" ?>>
  <label class="description" for="<?php echo esc_attr($name); ?>"><?php echo esc_html($description); ?></label>
  <?php if ($hint) { ?>
  <div style="opacity:0.7;font-size:85%;margin-top:3px"><?php echo esc_html($hint); ?></div>
  <?php } ?>
<?php
}

function openporte_settings_select_callback(array $args)
{
  $name = $args['name'];
  $hint = isset($args['hint']) ? $args['hint'] : null;
  $disabled = isset($args['disabled']) ? $args['disabled'] : false;
  $description = isset($args['description']) ? $args['description'] : null;
  $options = isset($args['options']) ? $args['options'] : array();
  $spamfilter_options = isset($args['spamfilter_options']) ? $args['spamfilter_options'] : array();
  $setting = get_option($name);
  $value = isset($setting) ? esc_attr($setting) : '';
?>
  <select name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($name); ?>">
  <?php
    foreach ( $options as $opt_key => $opt_value ) {
      echo '<option value="' . esc_attr( $opt_key ) . '" '
        . (in_array($opt_key, $spamfilter_options) ? ' data-spamfilter ' : '')
        . selected($value, $opt_key, false )
        . '>' . esc_html($opt_value) . '</option>';
    }
  ?>
  </select>
  <label class="description" for="<?php echo esc_attr($name); ?>"><?php echo esc_html($description) ?></label>
  <?php if ($hint) { ?>
  <div style="opacity:0.7;font-size:85%;margin-top:3px"><?php echo esc_html($hint); ?></div>
  <?php } ?>
<?php
}

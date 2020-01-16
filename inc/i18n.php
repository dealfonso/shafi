<?php

    if (!file_exists(__SHAFI_INC . '/vendor/autoload.php')) {

        /** This is a fallback to be able to use SHAFI even if php-gettext is not installed */
        if (! function_exists('__')) {
            function __($txt, $domain = null) {
                return $txt;
            }
        }
    
        if (! function_exists('_e')) {
            function _e($txt, $domain = null) {
                echo __($txt, $domain);
            }
        }
    
        if (! function_exists('_s')) {
            function _s($txt, ...$args) {
                return call_user_func_array('sprintf', array_merge([__($txt)], $args) );
            }
        }
        return;
    }

    require(__SHAFI_INC . '/vendor/autoload.php');

    use Gettext\Loader\PoLoader;
    use Gettext\Translator;

    if (! function_exists('bindtextdomain')) {
        define('__SHAFI_I18N_DEFAULT_LANG', 'en');

        $__SHAFI_I18N = array();

        function bindtextdomain($domain, $path) {
            if ($domain === null) $domain = "";

            if (!is_dir($path)) throw new Exception("path '$path' does not exist");

            $domain_data = __i18n_get_domain_data($domain);
            $domain_data["path"] = $path;
            $domain_data["avail"] = array();

            $prefix = "";
            if ($domain !== "") $prefix = "$domain-";
            $basename = "$path/$prefix";
            $basename_n = strlen($basename);

            foreach (glob("$basename*.po") as $lang) {
                $lang = str_replace('_', '-', substr($lang, $basename_n, -3));
                array_push($domain_data["avail"], $lang);
            }

            $domain_data["pref"] = prefered_language($domain_data["avail"], $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            __i18n_set_domain_data($domain, $domain_data);
        }
    }

    if (! function_exists('textdomain')) {
        $__SHAFI_I18N_DOMAIN = null;

        function textdomain($domain) {
            global $__SHAFI_I18N_DOMAIN;
            $__SHAFI_I18N_DOMAIN = $domain;
        }
    }

    function __i18n_set_domain_data($domain, $domain_data) {
        global $__SHAFI_I18N;
        if ($domain === null) $domain = "";
        $__SHAFI_I18N[$domain] = $domain_data;
    }

    function __i18n_get_domain_data($domain) {
        global $__SHAFI_I18N;
        if ($domain === null) $domain = "";
        if (isset($__SHAFI_I18N[$domain]))
            $domain_data = $__SHAFI_I18N[$domain];
        else
            $domain_data = array(
                'path' => null,
                'pref' => __SHAFI_I18N_DEFAULT_LANG,
                'avail' => array(),
                'translators' => array()
            );

        return $domain_data;
    }

    function __i18n_get_translator($domain, $lang = null) {
        if ($domain === null) $domain = "";
        $domain_data = __i18n_get_domain_data($domain);

        if (!isset($lang))
            $lang = $domain_data['pref'];

        if (isset($domain_data['translators'][$lang])) return $domain_data['translators'][$lang];

        if (in_array($lang, $domain_data['avail'])) {
            $loader = new PoLoader();
            $prefix = "";
            if ($domain !== "") $prefix = "$domain-";
            $lang_f = str_replace('-', '_', $lang);
            $translator = Translator::createFromTranslations( $loader->loadFile($domain_data['path'] . "/${prefix}${lang_f}.po"));
        } else 
            $translator = new Translator();

        $domain_data['translators'][$lang] = $translator;
        __i18n_set_domain_data($domain, $domain_data);

        return $translator;
    }

    if (! function_exists('__')) {
        function __($txt, $domain = null) {
            global $__SHAFI_I18N_DOMAIN;
            if (!isset($domain))
                $domain = $__SHAFI_I18N_DOMAIN;

            $translator = __i18n_get_translator($domain, null);
            return $translator->gettext($txt);
        }
    }

    if (! function_exists('_e')) {
        function _e($txt, $domain = null) {
            echo __($txt, $domain);
        }
    }

    if (! function_exists('_s')) {
        function _s($txt, ...$args) {
            return call_user_func_array('sprintf', array_merge([__($txt)], $args) );
        }
    }

    function prefered_language($available_languages_, $http_accept_language) {
        $available_languages = array();
        foreach ($available_languages_ as $available)
            $available_languages[strtolower($available)] = $available;

        $langs = array();
        preg_match_all('~([\w-]+)(?:[^,\d]+([\d.]+))?~', strtolower($http_accept_language), $matches, PREG_SET_ORDER);
        foreach($matches as $match) {
            list($a, $b) = explode('-', $match[1]) + array('', '');
            $value = isset($match[2]) ? (float) $match[2] : 1.0;
            if(isset($available_languages[$match[1]])) {
                $langs[$match[1]] = $value;
                continue;
            }
            if(isset($available_languages[$a])) {
                $langs[$a] = $value - 0.1;
            }
        }
        if($langs) {
            arsort($langs);
            return $available_languages[key($langs)]; // We don't need the whole array of choices since we have a match
        }
        return null;
    }

    bindtextdomain("shafi", "languages");
    textdomain("shafi");

    /*
    // use Gettext\TranslatorFunctions;
    $translator = Translator::createFromTranslations( $loader->loadFile('languages/es_ES.po'));

    //Register the translator to use the global functions
    TranslatorFunctions::register($translator);
    */
?>

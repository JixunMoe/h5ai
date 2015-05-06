<?php

class App {

    private $options;


    public function __construct() {

        $this->options = Util::load_commented_json(APP_PATH . "/conf/options.json");
    }


    public function get_options() {

        return $this->options;
    }


    public function query_option($keypath = "", $default = null) {

        return Util::array_query($this->options, $keypath, $default);
    }


    public function get_setup() {

        $keys = [
            "APP_HREF",
            "ROOT_HREF",
            "VERSION",

            "AS_ADMIN",
            "HAS_CUSTOM_PASSHASH"
        ];

        if (AS_ADMIN) {
            $keys = array_merge($keys, [
                "PHP_VERSION",
                "MIN_PHP_VERSION",
                "HAS_MIN_PHP_VERSION",
                "HAS_PHP_EXIF",
                "HAS_PHP_JPEG",

                "SERVER_NAME",
                "SERVER_VERSION",
                "HAS_SERVER",

                "INDEX_HREF",

                "HAS_WRITABLE_CACHE",

                "HAS_CMD_TAR",
                "HAS_CMD_ZIP",
                "HAS_CMD_CONVERT",
                "HAS_CMD_FFMPEG",
                "HAS_CMD_AVCONV",
                "HAS_CMD_DU"
            ]);
        }

        $setup = [];
        foreach ($keys as $key) {
            $setup[$key] = constant($key);
        }
        return $setup;
    }


    public function get_types() {

        return Util::load_commented_json(APP_PATH . "/conf/types.json");
    }


    public function to_href($path, $trailing_slash = true) {

        $rel_path = substr($path, strlen(ROOT_PATH));
        $parts = explode("/", $rel_path);
        $encoded_parts = [];
        foreach ($parts as $part) {
            if ($part != "") {
                $encoded_parts[] = rawurlencode($part);
            }
        }

        return Util::normalize_path(ROOT_HREF . implode("/", $encoded_parts), $trailing_slash);
    }


    public function to_path($href) {

        $rel_href = substr($href, strlen(ROOT_HREF));
        return Util::normalize_path(ROOT_PATH . "/" . rawurldecode($rel_href));
    }


    public function is_hidden($name) {

        // always hide
        if ($name === "." || $name === "..") {
            return true;
        }

        foreach ($this->query_option("view.hidden", []) as $re) {
            $re = Util::wrap_pattern($re);
            if (preg_match($re, $name)) {
                return true;
            }
        }

        return false;
    }


    public function read_dir($path) {

        $names = [];
        if (is_dir($path)) {
            foreach (scandir($path) as $name) {
                if (
                    $this->is_hidden($name)
                    || $this->is_hidden($this->to_href($path) . $name)
                    || (!is_readable($path .'/'. $name) && $this->query_option("view.hideIf403", false))
                ) {
                    continue;
                }
                $names[] = $name;
            }
        }
        return $names;
    }


    public function is_managed_href($href) {

        return $this->is_managed_path($this->to_path($href));
    }


    public function is_managed_path($path) {

        if (!is_dir($path) || strpos($path, '../') !== false || strpos($path, '/..') !== false || $path === '..') {
            return false;
        }

        if ($path === APP_PATH || strpos($path, APP_PATH . '/') === 0) {
            return false;
        }

        foreach ($this->query_option("view.unmanaged", []) as $name) {
            if (file_exists($path . "/" . $name)) {
                return false;
            }
        }

        while ($path !== ROOT_PATH) {
            if (@is_dir($path . "/_h5ai/server")) {
                return false;
            }
            $parent_path = Util::normalize_path(dirname($path));
            if ($parent_path === $path) {
                return false;
            }
            $path = $parent_path;
        }
        return true;
    }


    public function get_items($href, $what) {

        if (!$this->is_managed_href($href)) {
            return [];
        }

        $cache = [];
        $folder = Item::get($this, $this->to_path($href), $cache);

        // add content of subfolders
        if ($what >= 2 && $folder !== null) {
            foreach ($folder->get_content($cache) as $item) {
                $item->get_content($cache);
            }
            $folder = $folder->get_parent($cache);
        }

        // add content of this folder and all parent folders
        while ($what >= 1 && $folder !== null) {
            $folder->get_content($cache);
            $folder = $folder->get_parent($cache);
        }

        uasort($cache, ["Item", "cmp"]);
        $result = [];
        foreach ($cache as $p => $item) {
            $result[] = $item->to_json_object();
        }

        return $result;
    }


    public function get_langs() {

        $langs = [];
        $l10n_path = APP_PATH . "/conf/l10n";
        if (is_dir($l10n_path)) {
            if ($dir = opendir($l10n_path)) {
                while (($file = readdir($dir)) !== false) {
                    if (Util::ends_with($file, ".json")) {
                        $translations = Util::load_commented_json($l10n_path . "/" . $file);
                        $langs[basename($file, ".json")] = $translations["lang"];
                    }
                }
                closedir($dir);
            }
        }
        ksort($langs);
        return $langs;
    }


    public function get_l10n($iso_codes) {

        $results = [];

        foreach ($iso_codes as $iso_code) {
            $file = APP_PATH . "/conf/l10n/" . $iso_code . ".json";
            $results[$iso_code] = Util::load_commented_json($file);
            $results[$iso_code]["isoCode"] = $iso_code;
        }

        return $results;
    }


    public function get_thumbs($requests) {

        $hrefs = [];

        foreach ($requests as $req) {
            $thumb = new Thumb($this);
            $hrefs[] = $thumb->thumb($req["type"], $req["href"], $req["width"], $req["height"]);
        }

        return $hrefs;
    }
}
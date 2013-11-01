<?php

namespace Yapo;

class YapoMemory {

    static private $data = array();

    private function __consturct() {}

    public static function get($key, $space = 'default') {
        if (!empty(self::$data[$space][static::key($key)])) {
            return self::$data[$space][static::key($key)];
        } else {
            return null;
        }
    }

    public static function set($key, $value, $space = 'default') {
        self::$data[$space][static::key($key)] = $value;
    }

    public static function clean_space($space = 'default') {
        self::$data[$space] = array();
    }

    private static function key($key) {
        return md5(serialize($key));
    }


}
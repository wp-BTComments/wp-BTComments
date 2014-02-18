<?php

// Code taken from https://github.com/mikegogulski/bitcoin-php

class BitcoinAddressValidation {

    private static $hexchars = "0123456789ABCDEF";
    private static $base58chars = "123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz";

    public static function checkAddress($addr, $addressversion = "00") // hex byte
    {
        $addr = self::decodeBase58($addr);
        if (strlen($addr) != 50) {
            return false;
        }
        $version = substr($addr, 0, 2);
        if (hexdec($version) > hexdec($addressversion)) {
            return false;
        }
        $check = substr($addr, 0, strlen($addr) - 8);
        $check = pack("H*", $check);
        $check = strtoupper(hash("sha256", hash("sha256", $check, true)));
        $check = substr($check, 0, 8);
        return $check == substr($addr, strlen($addr) - 8);
    }


    private function decodeBase58($base58)
    {
        $origbase58 = $base58;

        //only valid chars allowed
        if (preg_match('/[^1-9A-HJ-NP-Za-km-z]/', $base58)) {
            return "";
        }

        $return = "0";
        for ($i = 0; $i < strlen($base58); $i++) {
            $current = (string)strpos(self::$base58chars, $base58[$i]);
            $return = (string)bcmul($return, "58", 0);
            $return = (string)bcadd($return, $current, 0);
        }

        $return = self::encodeHex($return);

        //leading zeros
        for ($i = 0; $i < strlen($origbase58) && $origbase58[$i] == "1"; $i++) {
            $return = "00" . $return;
        }

        if (strlen($return) % 2 != 0) {
            $return = "0" . $return;
        }

        return $return;
    }

    private function encodeHex($dec)
    {
        $return = "";
        while (bccomp($dec, 0) == 1) {
            $dv = (string)bcdiv($dec, "16", 0);
            $rem = (integer)bcmod($dec, "16");
            $dec = $dv;
            $return = $return . self::$hexchars[$rem];
        }
        return strrev($return);
    }
}

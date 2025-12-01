<?php
/**
 * Google Authenticator (TOTP) Implementation
 * Standalone PHP implementation for XAMPP
 */
class GoogleAuthenticator {
    private static $codeLength = 6;
    
    /**
     * Generate a random secret key
     */
    public static function generateSecret($length = 16) {
        $validChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Base32 alphabet
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $validChars[random_int(0, strlen($validChars) - 1)];
        }
        return $secret;
    }
    
    /**
     * Get QR Code URL for Google Authenticator
     */
    public static function getQRCodeUrl($username, $secret, $issuer = 'Formular App') {
        $label = urlencode($username);
        $issuerEncoded = urlencode($issuer);
        $url = "otpauth://totp/{$label}?secret={$secret}&issuer={$issuerEncoded}";
        return "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($url);
    }
    
    /**
     * Verify TOTP code
     */
    public static function verifyCode($secret, $code, $discrepancy = 1) {
        $code = trim($code);
        if (strlen($code) != self::$codeLength || !is_numeric($code)) {
            return false;
        }
        
        $timeSlice = floor(time() / 30);
        
        // Check current time slice and adjacent ones (for clock skew)
        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculatedCode = self::getCode($secret, $timeSlice + $i);
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Calculate TOTP code for given time slice
     */
    private static function getCode($secret, $timeSlice = null) {
        if ($timeSlice === null) {
            $timeSlice = floor(time() / 30);
        }
        
        $secretKey = self::base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeSlice);
        $hm = hash_hmac('sha1', $time, $secretKey, true);
        $offset = ord($hm[19]) & 0xf;
        $code = (
            ((ord($hm[$offset + 0]) & 0x7f) << 24) |
            ((ord($hm[$offset + 1]) & 0xff) << 16) |
            ((ord($hm[$offset + 2]) & 0xff) << 8) |
            (ord($hm[$offset + 3]) & 0xff)
        ) % pow(10, self::$codeLength);
        
        return str_pad($code, self::$codeLength, '0', STR_PAD_LEFT);
    }
    
    /**
     * Base32 decode
     */
    private static function base32Decode($secret) {
        if (!is_string($secret)) {
            return false;
        }
        
        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32charsArray = str_split($base32chars);
        $base32charsFlipped = array_flip($base32charsArray);
        
        $paddingCharCount = substr_count($secret, '=');
        $allowedValues = array(6, 4, 3, 1, 0);
        if (!in_array($paddingCharCount, $allowedValues)) {
            return false;
        }
        
        for ($i = 0; $i < 4; $i++) {
            if ($paddingCharCount == $allowedValues[$i] &&
                substr($secret, -($allowedValues[$i])) != str_repeat('=', $allowedValues[$i])) {
                return false;
            }
        }
        
        $secret = str_replace('=', '', $secret);
        $secret = str_split($secret);
        $binaryString = '';
        
        for ($i = 0; $i < count($secret); $i = $i + 8) {
            $x = '';
            if (!in_array($secret[$i], $base32charsArray)) {
                return false;
            }
            for ($j = 0; $j < 8; $j++) {
                if (isset($secret[$i + $j]) && isset($base32charsFlipped[$secret[$i + $j]])) {
                    $x .= str_pad(base_convert($base32charsFlipped[$secret[$i + $j]], 10, 2), 5, '0', STR_PAD_LEFT);
                } else {
                    $x .= '00000';
                }
            }
            $eightBits = str_split($x, 8);
            for ($z = 0; $z < count($eightBits); $z++) {
                $binaryString .= chr(base_convert($eightBits[$z], 2, 10));
            }
        }
        
        return $binaryString;
    }
}
?>


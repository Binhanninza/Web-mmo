<?php
define('ENC_KEY', 'BiMatNayChiMinhTaBiet@2024$$$'); // Đổi cái này đi
define('ENC_IV', '1234567890123456'); // Giữ nguyên 16 số này hoặc đổi số khác nhưng phải đủ 16 ký tự

function data_encrypt($string) {
    return openssl_encrypt($string, "AES-256-CBC", ENC_KEY, 0, ENC_IV);
}

function data_decrypt($string) {
    return openssl_decrypt($string, "AES-256-CBC", ENC_KEY, 0, ENC_IV);
}
?>

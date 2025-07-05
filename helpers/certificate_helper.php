<?php
function generateCertificateId() {
    return strtoupper(uniqid('CERT-') . '-' . bin2hex(random_bytes(4)));
}

function validateInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
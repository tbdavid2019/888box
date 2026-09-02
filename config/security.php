<?php
/**
 * Security Helper functions for 888box
 * 
 * Provides SSRF validation, IP resolution inspection, safe path normalization,
 * and security-hardened utility routines.
 */

/**
 * Check whether an IP address belongs to private, loopback, link-local, or reserved ranges.
 *
 * @param string $ip
 * @return bool True if IP is private/reserved/internal, false if public.
 */
function isPrivateOrReservedIp(string $ip): bool {
    // Validate IP format
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return true;
    }

    // Filter using PHP native flags for private and reserved ranges
    $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
    if (!filter_var($ip, FILTER_VALIDATE_IP, $flags)) {
        return true;
    }

    // Additional checks for IPv4 ranges that some PHP versions may miss
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $long = ip2long($ip);
        if ($long === false) {
            return true;
        }

        // 0.0.0.0/8 (Broadcast/Current network)
        if (($long & 0xFF000000) === 0) return true;
        // 10.0.0.0/8 (Private)
        if (($long & 0xFF000000) === 0x0A000000) return true;
        // 100.64.0.0/10 (Carrier-grade NAT)
        if (($long & 0xFFC00000) === 0x64400000) return true;
        // 127.0.0.0/8 (Loopback)
        if (($long & 0xFF000000) === 0x7F000000) return true;
        // 169.254.0.0/16 (Link-local & Cloud metadata: AWS 169.254.169.254)
        if (($long & 0xFFFF0000) === 0xA9FE0000) return true;
        // 172.16.0.0/12 (Private)
        if (($long & 0xFFF00000) === 0xAC100000) return true;
        // 192.0.0.0/24 (IETF Protocol Assignments)
        if (($long & 0xFFFFFF00) === 0xC0000000) return true;
        // 192.0.2.0/24 (TEST-NET-1)
        if (($long & 0xFFFFFF00) === 0xC0000200) return true;
        // 192.168.0.0/16 (Private)
        if (($long & 0xFFFF0000) === 0xC0A80000) return true;
        // 198.18.0.0/15 (Benchmarking)
        if (($long & 0xFFFE0000) === 0xC6120000) return true;
        // 198.51.100.0/24 (TEST-NET-2)
        if (($long & 0xFFFFFF00) === 0xC6336400) return true;
        // 203.0.113.0/24 (TEST-NET-3)
        if (($long & 0xFFFFFF00) === 0xCB007100) return true;
        // 224.0.0.0/4 (Multicast)
        if (($long & 0xF0000000) === 0xE0000000) return true;
        // 240.0.0.0/4 (Reserved)
        if (($long & 0xF0000000) === 0xF0000000) return true;
        // 255.255.255.255 (Broadcast)
        if ($long === -1 || $long === 0xFFFFFFFF) return true;
    }

    // Additional checks for IPv6 ranges
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $hex = bin2hex(inet_pton($ip));
        // ::1 (Loopback)
        if ($hex === str_repeat('0', 30) . '01') return true;
        // :: (Unspecified)
        if ($hex === str_repeat('0', 32)) return true;
        // IPv4-mapped IPv6 (::ffff:0:0/96)
        if (str_starts_with($hex, str_repeat('0', 20) . 'ffff')) {
            $ipv4Part = inet_ntop(substr(inet_pton($ip), 12));
            if ($ipv4Part && isPrivateOrReservedIp($ipv4Part)) {
                return true;
            }
        }
        // Unique Local Addresses (fc00::/7 -> fc.. or fd..)
        if (str_starts_with($hex, 'fc') || str_starts_with($hex, 'fd')) return true;
        // Link-Local (fe80::/10 -> fe8, fe9, fea, feb)
        if (preg_match('/^fe[89ab]/i', $hex)) return true;
    }

    return false;
}

/**
 * Validate a remote URL to prevent SSRF vulnerabilities.
 * Checks protocol scheme, host resolution, and IP range constraints.
 *
 * @param string $url
 * @return array ['valid' => bool, 'error' => string, 'url' => string]
 */
function validateSafeRemoteUrl(string $url): array {
    $trimmed = trim($url);
    if ($trimmed === '') {
        return ['valid' => false, 'error' => 'URL 不能為空', 'url' => ''];
    }

    if (!filter_var($trimmed, FILTER_VALIDATE_URL)) {
        return ['valid' => false, 'error' => '無效的 URL 格式', 'url' => $trimmed];
    }

    $parts = parse_url($trimmed);
    if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
        return ['valid' => false, 'error' => 'URL 缺少通訊協定或主機名稱', 'url' => $trimmed];
    }

    // Strict protocol whitelist: only http and https
    $scheme = strtolower($parts['scheme']);
    if (!in_array($scheme, ['http', 'https'], true)) {
        return ['valid' => false, 'error' => '僅支援 HTTP 與 HTTPS 協定', 'url' => $trimmed];
    }

    // Prohibit embedded credentials (user:pass@host)
    if (!empty($parts['user']) || !empty($parts['pass'])) {
        return ['valid' => false, 'error' => 'URL 不允許包含使用者憑據', 'url' => $trimmed];
    }

    $host = strtolower(trim($parts['host']));

    // Block localhost aliases
    $blockedHosts = ['localhost', 'localhost.localdomain', 'ip6-localhost', 'ip6-loopback'];
    if (in_array($host, $blockedHosts, true)) {
        return ['valid' => false, 'error' => '禁止存取本地或保留主機', 'url' => $trimmed];
    }

    // Resolve host to IPs
    $ips = [];
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        $ips[] = $host;
    } else {
        $records = @dns_get_record($host, DNS_A + DNS_AAAA);
        if (!empty($records)) {
            foreach ($records as $record) {
                if (isset($record['ip'])) {
                    $ips[] = $record['ip'];
                } elseif (isset($record['ipv6'])) {
                    $ips[] = $record['ipv6'];
                }
            }
        }

        // Fallback resolution
        if (empty($ips)) {
            $hostIps = @gethostbynamel($host);
            if (!empty($hostIps)) {
                $ips = array_merge($ips, $hostIps);
            }
        }
    }

    if (empty($ips)) {
        return ['valid' => false, 'error' => '無法解析主機 IP 位址', 'url' => $trimmed];
    }

    // Check all resolved IPs against private/reserved ranges
    foreach ($ips as $resolvedIp) {
        if (isPrivateOrReservedIp($resolvedIp)) {
            return ['valid' => false, 'error' => '禁止存取內部或私有網路位址', 'url' => $trimmed];
        }
    }

    return ['valid' => true, 'error' => '', 'url' => $trimmed];
}

/**
 * Configure cURL handle with safe protocols and limits.
 *
 * @param resource $ch cURL resource
 * @return void
 */
function applySafeCurlOptions($ch): void {
    if (defined('CURLOPT_PROTOCOLS') && defined('CURLPROTO_HTTP') && defined('CURLPROTO_HTTPS')) {
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
    }
    if (defined('CURLOPT_REDIR_PROTOCOLS') && defined('CURLPROTO_HTTP') && defined('CURLPROTO_HTTPS')) {
        curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
    }
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
}

<?php

function normalizeConfiguredDomains($siteDomainConfig) {
    if (is_array($siteDomainConfig)) {
        return array_values(array_filter(array_map('trim', $siteDomainConfig)));
    }

    return array_values(array_filter(array_map('trim', explode(',', (string)$siteDomainConfig))));
}

function isDavid888Host($host) {
    $normalizedHost = strtolower((string)$host);
    return $normalizedHost === 'david888.com' || str_ends_with($normalizedHost, '.david888.com');
}

function isHostAllowedByConfiguredDomains($host, $siteDomainConfig) {
    $normalizedHost = strtolower((string)$host);
    if ($normalizedHost === '') return false;

    foreach (normalizeConfiguredDomains($siteDomainConfig) as $domain) {
        if ($domain === '*') return true;

        $domainHost = parse_url($domain, PHP_URL_HOST);
        if ($domainHost === null && strpos($domain, '://') === false) {
            $domainHost = parse_url('https://' . $domain, PHP_URL_HOST);
        }

        if ($normalizedHost === strtolower((string)$domainHost)) {
            return true;
        }
    }

    return false;
}

function resolveCorsAllowOrigin($origin, $siteDomainConfig) {
    $siteDomains = normalizeConfiguredDomains($siteDomainConfig);
    if (in_array('*', $siteDomains, true)) {
        return '*';
    }

    $originHost = parse_url((string)$origin, PHP_URL_HOST);
    if (isHostAllowedByConfiguredDomains($originHost, $siteDomains) || isDavid888Host($originHost)) {
        return (string)$origin;
    }

    return 'null';
}

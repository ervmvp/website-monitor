<?php

declare(strict_types=1);

/**
 * Check whether a public HTTP or HTTPS website responds.
 *
 * @return array{url: string, online: bool, status: int, responseTime: int, message: string}
 */
function checkWebsite(string $url): array
{
    $url = trim($url);

    if (
        filter_var($url, FILTER_VALIDATE_URL) === false
        || !in_array(
            strtolower((string) parse_url($url, PHP_URL_SCHEME)),
            ['http', 'https'],
            true
        )
    ) {
        throw new InvalidArgumentException(
            'Enter a valid website URL that starts with http:// or https://.'
        );
    }

    $parts = parse_url($url);

    if (
        $parts === false
        || empty($parts['host'])
        || isset($parts['user'])
        || isset($parts['pass'])
    ) {
        throw new InvalidArgumentException(
            'Enter a valid website URL without a username or password.'
        );
    }

    $scheme = strtolower((string) $parts['scheme']);
    $host = trim((string) $parts['host'], '[]');
    $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);

    if ($port < 1 || $port > 65535) {
        throw new InvalidArgumentException('The URL has an invalid port.');
    }

    $addresses = filter_var($host, FILTER_VALIDATE_IP)
        ? [$host]
        : array_values(array_unique(array_merge(
            @gethostbynamel($host) ?: [],
            array_column(@dns_get_record($host, DNS_AAAA) ?: [], 'ipv6')
        )));

    if ($addresses === []) {
        return [
            'url' => $url,
            'online' => false,
            'status' => 0,
            'responseTime' => 0,
            'message' => 'The website hostname could not be found.'
        ];
    }

    foreach ($addresses as $address) {
        if (
            !filter_var(
                $address,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            )
        ) {
            throw new InvalidArgumentException(
                'Only publicly accessible websites can be checked.'
            );
        }
    }

    $ipv4Addresses = array_values(array_filter(
        $addresses,
        static function (string $address): bool {
            return filter_var(
                $address,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV4
            ) !== false;
        }
    ));
    $connectionAddresses = $ipv4Addresses !== [] ? $ipv4Addresses : $addresses;

    if (function_exists('curl_init')) {
        $check = checkWebsiteWithCurl(
            $url,
            $host,
            $port,
            $connectionAddresses,
            $ipv4Addresses !== []
        );
    } else {
        $check = checkWebsiteWithSockets(
            $parts,
            $scheme,
            $host,
            $port,
            $connectionAddresses
        );
    }

    $status = $check['status'];
    $responseTime = $check['responseTime'];
    $online = $status >= 200 && $status < 400;

    if ($status === 0) {
        $message = $check['message'];
    } elseif ($online) {
        $message = 'The website responded successfully.';
    } else {
        $message = 'The website responded with an HTTP error.';
    }

    return [
        'url' => $url,
        'online' => $online,
        'status' => $status,
        'responseTime' => $responseTime,
        'message' => $message
    ];
}

/**
 * Check a website using cURL while pinning connections to validated IPs.
 *
 * @param list<string> $addresses
 * @return array{status: int, responseTime: int, message: string}
 */
function checkWebsiteWithCurl(
    string $url,
    string $host,
    int $port,
    array $addresses,
    bool $hasIpv4
): array {
    $curl = curl_init($url);

    if ($curl === false) {
        return [
            'status' => 0,
            'responseTime' => 0,
            'message' => 'Could not start the website check.'
        ];
    }

    $resolve = [];
    if (!filter_var($host, FILTER_VALIDATE_IP)) {
        foreach ($addresses as $address) {
            $resolvedAddress = strpos($address, ':') !== false
                ? '[' . $address . ']'
                : $address;
            $resolve[] = "{$host}:{$port}:{$resolvedAddress}";
        }
    }

    $options = [
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_NOBODY => false,
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_IPRESOLVE => $hasIpv4 ? CURL_IPRESOLVE_V4 : CURL_IPRESOLVE_V6,
        CURLOPT_USERAGENT => 'PulseCheck/1.0',
        CURLOPT_WRITEFUNCTION => static function ($handle, string $data): int {
            return strlen($data);
        }
    ];

    if ($resolve !== []) {
        $options[CURLOPT_RESOLVE] = $resolve;
    }

    curl_setopt_array($curl, $options);

    $startedAt = microtime(true);
    curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $responseTime = (int) round((microtime(true) - $startedAt) * 1000);
    curl_close($curl);

    return [
        'status' => $status,
        'responseTime' => $responseTime,
        'message' => 'Could not connect to this website. Check the URL and try again.'
    ];
}

/**
 * Check a website using PHP streams when the optional cURL extension is absent.
 *
 * @param array<string, mixed> $parts
 * @param list<string> $addresses
 * @return array{status: int, responseTime: int, message: string}
 */
function checkWebsiteWithSockets(
    array $parts,
    string $scheme,
    string $host,
    int $port,
    array $addresses
): array {
    if ($scheme === 'https' && !extension_loaded('openssl')) {
        return [
            'status' => 0,
            'responseTime' => 0,
            'message' => 'HTTPS checks require the PHP cURL or OpenSSL extension.'
        ];
    }

    $path = $parts['path'] ?? '/';
    if (isset($parts['query'])) {
        $path .= '?' . $parts['query'];
    }

    $hostHeader = strpos($host, ':') !== false
        ? '[' . $host . ']'
        : $host;
    if (($scheme === 'https' && $port !== 443) || ($scheme === 'http' && $port !== 80)) {
        $hostHeader .= ':' . $port;
    }

    $request = "GET {$path} HTTP/1.1\r\n"
        . "Host: {$hostHeader}\r\n"
        . "User-Agent: PulseCheck/1.0\r\n"
        . "Accept: */*\r\n"
        . "Connection: close\r\n\r\n";

    $startedAt = microtime(true);

    foreach ($addresses as $address) {
        $socketHost = strpos($address, ':') !== false
            ? '[' . $address . ']'
            : $address;
        $transport = $scheme === 'https' ? 'tls' : 'tcp';
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'peer_name' => $host,
                'SNI_enabled' => true
            ]
        ]);
        $socket = @stream_socket_client(
            "{$transport}://{$socketHost}:{$port}",
            $errorCode,
            $errorMessage,
            8,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if ($socket === false) {
            continue;
        }

        stream_set_timeout($socket, 8);

        $written = 0;
        $requestLength = strlen($request);
        while ($written < $requestLength) {
            $bytesWritten = @fwrite($socket, substr($request, $written));
            if ($bytesWritten === false || $bytesWritten === 0) {
                break;
            }
            $written += $bytesWritten;
        }

        $statusLine = $written === $requestLength ? @fgets($socket, 8192) : false;
        fclose($socket);

        if (
            is_string($statusLine)
            && preg_match('/^HTTP\/\d(?:\.\d)?\s+(\d{3})\b/', $statusLine, $matches)
        ) {
            return [
                'status' => (int) $matches[1],
                'responseTime' => (int) round((microtime(true) - $startedAt) * 1000),
                'message' => ''
            ];
        }
    }

    return [
        'status' => 0,
        'responseTime' => (int) round((microtime(true) - $startedAt) * 1000),
        'message' => 'Could not connect to this website. Check the URL and try again.'
    ];
}
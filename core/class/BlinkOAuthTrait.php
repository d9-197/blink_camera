<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;

/**
 * OAuth 2.0 PKCE flow for Blink (api.oauth.blink.com).
 *
 * Injected into the blink_camera class. The trait references constants
 * (OAUTH_*, TIER_ENDPOINT, ...) and helpers (logdebug, logerror,
 * getConfigBlinkAccount, setConfigBlinkAccount) declared on the using class.
 */
trait BlinkOAuthTrait
{
    // -------------------------------------------------------------------------
    // OAuth 2.0 PKCE helpers
    // -------------------------------------------------------------------------

    private static function generatePKCE(): array {
        $verifier  = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        return ['verifier' => $verifier, 'challenge' => $challenge];
    }

    private static function generateHardwareId(): string {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    private static function serializeCookieJar(CookieJar $jar): string {
        $cookies = [];
        foreach ($jar as $cookie) {
            $cookies[] = $cookie->toArray();
        }
        return json_encode($cookies);
    }

    private static function deserializeCookieJar(string $serialized): CookieJar {
        $jar     = new CookieJar();
        $cookies = json_decode($serialized, true) ?? [];
        foreach ($cookies as $data) {
            $jar->setCookie(new SetCookie($data));
        }
        return $jar;
    }

    // -------------------------------------------------------------------------
    // OAuth 2.0 flow steps
    // -------------------------------------------------------------------------

    private static function oauthDoAuthorize(CookieJar $jar, string $hardware_id, string $code_challenge): bool {
        $client = new GuzzleHttp\Client(['verify' => false, 'cookies' => $jar, 'allow_redirects' => true]);
        try {
            $r = $client->request('GET', self::OAUTH_AUTHORIZE_URL, [
                'headers' => [
                    'User-Agent'      => self::OAUTH_USER_AGENT,
                    'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.5',
                ],
                'query' => [
                    'app_brand'          => 'blink',
                    'app_version'        => '6.18.0',
                    'client_id'          => self::OAUTH_CLIENT_ID,
                    'code_challenge'     => $code_challenge,
                    'code_challenge_method' => 'S256',
                    'device_brand'       => 'Apple',
                    'device_model'       => 'iPhone',
                    'device_os_version'  => '18.7',
                    'hardware_id'        => $hardware_id,
                    'redirect_uri'       => self::OAUTH_REDIRECT_URI,
                    'response_type'      => 'code',
                    'scope'              => self::OAUTH_SCOPE,
                ],
            ]);
            return $r->getStatusCode() === 200;
        } catch (Exception $e) {
            self::logdebug('oauthDoAuthorize ERROR: ' . $e->getMessage());
            return false;
        }
    }

    private static function oauthGetCsrfToken(CookieJar $jar): ?string {
        $client = new GuzzleHttp\Client(['verify' => false, 'cookies' => $jar, 'allow_redirects' => true]);
        try {
            $r    = $client->request('GET', self::OAUTH_SIGNIN_URL, [
                'headers' => [
                    'User-Agent'      => self::OAUTH_USER_AGENT,
                    'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.5',
                ],
            ]);
            $body = (string)$r->getBody();
            // Blink's Next.js signin page embeds the CSRF token inside
            // <script id="oauth-args" type="application/json">{..."csrf-token":"<value>"...}</script>
            if (preg_match('/"csrf-token"\s*:\s*"([^"]+)"/', $body, $m)) {
                return $m[1];
            }
            // Legacy HTML-form fallbacks.
            if (preg_match('/name=["\']csrf-token["\'][^>]*value=["\']([^"\']+)["\']/', $body, $m) ||
                preg_match('/value=["\']([^"\']+)["\'][^>]*name=["\']csrf-token["\']/', $body, $m)) {
                return $m[1];
            }
            self::logdebug('oauthGetCsrfToken: CSRF token not found in page');
            return null;
        } catch (Exception $e) {
            self::logdebug('oauthGetCsrfToken ERROR: ' . $e->getMessage());
            return null;
        }
    }

    private static function oauthSignin(CookieJar $jar, string $email, string $password, string $csrf_token): array {
        $client = new GuzzleHttp\Client(['verify' => false, 'cookies' => $jar, 'allow_redirects' => false, 'http_errors' => false]);
        try {
            $r = $client->request('POST', self::OAUTH_SIGNIN_URL, [
                'headers' => [
                    'User-Agent'   => self::OAUTH_USER_AGENT,
                    'Accept'       => 'application/json, text/plain, */*',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Origin'       => 'https://api.oauth.blink.com',
                    'Referer'      => self::OAUTH_SIGNIN_URL,
                ],
                'form_params' => [
                    'username'   => $email,
                    'password'   => $password,
                    'csrf-token' => $csrf_token,
                ],
            ]);
            $code     = $r->getStatusCode();
            $location = $r->getHeaderLine('Location');
            $body     = (string)$r->getBody();
            $json     = json_decode($body, true);

            // Legacy redirect-based flow (kept for robustness).
            if (in_array($code, [301, 302, 303, 307, 308])) {
                return ['status' => 'SUCCESS', 'location' => $location];
            }
            if ($code === 412) {
                return ['status' => '2FA_REQUIRED', 'location' => $location];
            }
            // Blink's Next.js signin endpoint now answers with JSON.
            if ($code >= 200 && $code < 300 && is_array($json)) {
                $status   = strtolower((string)($json['status'] ?? ''));
                $redirect = $json['redirect_url'] ?? ($json['redirect_to'] ?? ($json['location'] ?? ($json['continue_to'] ?? ($json['next_action_url'] ?? ''))));
                if (!$location && $redirect) { $location = (string)$redirect; }
                self::logdebug('oauthSignin JSON status=' . $status . ' redirect=' . (string)$redirect . ' body=' . substr($body, 0, 400));
                if ($status === 'auth-completed' || $status === 'authenticated' || $status === 'success') {
                    return ['status' => 'SUCCESS', 'location' => $location];
                }
                if ($status !== '' || !empty($json['challenge']) || !empty($json['challenge_type']) || !empty($json['mfa_required']) || !empty($json['otp_required'])) {
                    return ['status' => '2FA_REQUIRED', 'location' => $location];
                }
            }
            // Anything else (401 invalid_user_credentials, 400, 5xx, …) → ERROR.
            self::logdebug('oauthSignin unexpected response: HTTP ' . $code . ' body=' . substr($body, 0, 500));
            return ['status' => 'ERROR', 'location' => ''];
        } catch (Exception $e) {
            self::logdebug('oauthSignin ERROR: ' . $e->getMessage());
            return ['status' => 'ERROR', 'location' => ''];
        }
    }

    private static function oauthVerify2FA(CookieJar $jar, string $twofa_code, string $csrf_token): array {
        $client = new GuzzleHttp\Client(['verify' => false, 'cookies' => $jar, 'allow_redirects' => false, 'http_errors' => false]);
        try {
            $r = $client->request('POST', self::OAUTH_2FA_URL, [
                'headers' => [
                    'User-Agent'   => self::OAUTH_USER_AGENT,
                    'Accept'       => 'application/json, text/plain, */*',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Origin'       => 'https://api.oauth.blink.com',
                    'Referer'      => self::OAUTH_2FA_URL,
                ],
                'form_params' => [
                    '2fa_code'    => $twofa_code,
                    'csrf-token'  => $csrf_token,
                    'remember_me' => 'on',
                ],
            ]);
            $code     = $r->getStatusCode();
            $location = $r->getHeaderLine('Location');
            $body     = (string)$r->getBody();
            $data     = json_decode($body, true);
            $redirect = is_array($data) ? ($data['redirect_url'] ?? ($data['redirect_to'] ?? ($data['location'] ?? ($data['continue_to'] ?? ($data['next_action_url'] ?? ''))))) : '';
            if (!$location && $redirect) { $location = (string)$redirect; }
            self::logdebug('oauthVerify2FA HTTP ' . $code . ' location=' . $location . ' body=' . substr($body, 0, 400));

            if (($code === 200 || $code === 201) && is_array($data) && (($data['status'] ?? '') === 'auth-completed')) {
                return ['ok' => true, 'location' => $location];
            }
            return ['ok' => false, 'location' => $location];
        } catch (Exception $e) {
            self::logdebug('oauthVerify2FA ERROR: ' . $e->getMessage());
            return ['ok' => false, 'location' => ''];
        }
    }

    private static function oauthGetAuthCode(CookieJar $jar, string $hardware_id, string $code_challenge, ?string $startUrl = null): ?string {
        // The continuation request after signin/2FA must hit the bare authorize URL — the session
        // cookies carry the pending PKCE state. Re-sending the original PKCE params here would
        // start a new flow and bounce us back to /signin (verified against blinkpy's reference
        // implementation: oauth_get_authorization_code in fronzbot/blinkpy).
        if ($startUrl !== null && $startUrl !== '') {
            $url = $startUrl;
            if (!preg_match('/^https?:\/\//i', $url)) {
                $url = 'https://api.oauth.blink.com' . (substr($url, 0, 1) === '/' ? '' : '/') . $url;
            }
        } else {
            $url = self::OAUTH_AUTHORIZE_URL;
        }
        // Dump cookies for diagnosis (names + short value prefix only).
        $cookieDump = [];
        foreach ($jar as $c) { $cookieDump[] = $c->getName() . '=' . substr((string)$c->getValue(), 0, 8) . '…'; }
        self::logdebug('oauthGetAuthCode jar=[' . implode(', ', $cookieDump) . ']');
        $client = new GuzzleHttp\Client(['verify' => false, 'cookies' => $jar, 'allow_redirects' => false, 'http_errors' => false]);
        for ($i = 0; $i < 10; $i++) {
            try {
                $r = $client->request('GET', $url, [
                    'headers' => [
                        'User-Agent' => self::OAUTH_USER_AGENT,
                        'Accept'     => '*/*',
                        'Referer'    => self::OAUTH_SIGNIN_URL,
                    ],
                ]);
                $status   = $r->getStatusCode();
                $location = $r->getHeaderLine('Location');
                self::logdebug('oauthGetAuthCode step '.$i.' HTTP '.$status.' url='.$url.' location='.$location);
                if ($status >= 200 && $status < 300) {
                    self::logdebug('oauthGetAuthCode: body='.substr((string)$r->getBody(), 0, 400));
                    break;
                }
                if (!$location) {
                    break;
                }
                if (stripos($location, 'immedia-blink://') === 0) {
                    parse_str(parse_url($location, PHP_URL_QUERY), $params);
                    return $params['code'] ?? null;
                }
                if (!preg_match('/^https?:\/\//i', $location)) {
                    $p        = parse_url($url);
                    $location = $p['scheme'] . '://' . $p['host'] . '/' . ltrim($location, '/');
                }
                $url = $location;
            } catch (Exception $e) {
                self::logdebug('oauthGetAuthCode ERROR: ' . $e->getMessage());
                break;
            }
        }
        self::logdebug('oauthGetAuthCode: auth code not found');
        return null;
    }

    private static function oauthExchangeCode(string $code, string $code_verifier, string $hardware_id): ?array {
        $client = new GuzzleHttp\Client(['verify' => false]);
        try {
            $r = $client->request('POST', self::OAUTH_TOKEN_URL, [
                'headers' => [
                    'User-Agent'   => self::OAUTH_TOKEN_UA,
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept'       => 'application/json',
                ],
                'form_params' => [
                    'app_brand'    => 'blink',
                    'client_id'    => self::OAUTH_CLIENT_ID,
                    'code'         => $code,
                    'code_verifier' => $code_verifier,
                    'grant_type'   => 'authorization_code',
                    'hardware_id'  => $hardware_id,
                    'redirect_uri' => self::OAUTH_REDIRECT_URI,
                    'scope'        => self::OAUTH_SCOPE,
                ],
            ]);
            if ($r->getStatusCode() === 200) {
                return json_decode((string)$r->getBody(), true);
            }
            self::logdebug('oauthExchangeCode unexpected status: ' . $r->getStatusCode());
            return null;
        } catch (Exception $e) {
            self::logdebug('oauthExchangeCode ERROR: ' . $e->getMessage());
            return null;
        }
    }

    private static function oauthRefreshAccessToken(string $refresh_token, string $hardware_id): ?array {
        $client = new GuzzleHttp\Client(['verify' => false]);
        try {
            $r = $client->request('POST', self::OAUTH_TOKEN_URL, [
                'headers' => [
                    'User-Agent'   => self::OAUTH_TOKEN_UA,
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept'       => 'application/json',
                ],
                'form_params' => [
                    'grant_type'    => 'refresh_token',
                    'refresh_token' => $refresh_token,
                    'client_id'     => self::OAUTH_CLIENT_ID,
                    'scope'         => self::OAUTH_SCOPE,
                    'hardware_id'   => $hardware_id,
                ],
            ]);
            if ($r->getStatusCode() === 200) {
                return json_decode((string)$r->getBody(), true);
            }
            self::logdebug('oauthRefreshAccessToken unexpected status: ' . $r->getStatusCode());
            return null;
        } catch (Exception $e) {
            self::logdebug('oauthRefreshAccessToken ERROR: ' . $e->getMessage());
            return null;
        }
    }

    private static function getTierInfo(string $email): bool {
        $access_token = self::getConfigBlinkAccount($email, 'token');
        $client       = new GuzzleHttp\Client(['verify' => false]);
        try {
            $r    = $client->request('GET', self::TIER_ENDPOINT, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $access_token,
                    'User-Agent'    => self::OAUTH_TOKEN_UA,
                ],
            ]);
            $data = json_decode((string)$r->getBody(), true);
            self::logdebug('getTierInfo: ' . print_r($data, true));
            $region_id  = $data['tier']      ?? ($data['region_id'] ?? ($data['region'] ?? 'prod'));
            $account_id = $data['account_id'] ?? ($data['id']      ?? '');
            if ($region_id)  { self::setConfigBlinkAccount($email, 'region',    $region_id);  }
            if ($account_id) { self::setConfigBlinkAccount($email, 'accountId', $account_id); }
            return true;
        } catch (Exception $e) {
            self::logdebug('getTierInfo ERROR: ' . $e->getMessage());
            return false;
        }
    }

    private static function finalizeOAuth(string $email, CookieJar $jar, string $verifier, string $challenge, string $hardware_id, ?string $startUrl): bool {
        $code = self::oauthGetAuthCode($jar, $hardware_id, $challenge, $startUrl);
        if (!$code) {
            self::logerror('OAuth: authorization code not obtained');
            return false;
        }
        $tokens = self::oauthExchangeCode($code, $verifier, $hardware_id);
        if (!$tokens || empty($tokens['access_token'])) {
            self::logerror('OAuth: token exchange failed');
            return false;
        }
        self::setConfigBlinkAccount($email, 'token', $tokens['access_token']);
        if (!empty($tokens['refresh_token'])) {
            self::setConfigBlinkAccount($email, 'refresh_token', $tokens['refresh_token']);
        }
        self::setConfigBlinkAccount($email, 'oauth_hardware_id', $hardware_id);
        self::setConfigBlinkAccount($email, 'oauth_cookies', self::serializeCookieJar($jar));
        if (!self::getTierInfo($email)) {
            self::logerror('OAuth: tier_info lookup failed');
            return false;
        }
        self::setConfigBlinkAccount($email, 'verif', 'true');
        return true;
    }
}

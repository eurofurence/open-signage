<?php

namespace App\Providers\Socialite;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;
use RuntimeException;

class SocialiteIdentityProvider extends AbstractProvider
{
    private mixed $issuer;

    private mixed $userinfoEndpoint;

    private mixed $tokenEndpoint;

    private mixed $authorizationEndpoint;

    private mixed $jwksUri;

    private mixed $endSessionEndpoint;

    private mixed $revocationEndpoint;

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['email'];

    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ' ';

    public function getIdentityConfig()
    {
        if (isset($this->issuer)) {
            return $this;
        }

        $discoveryUrl = config('services.identity.openid_configuration');

        if (blank($discoveryUrl)) {
            throw new RuntimeException('IDENTITY_OPENID_CONFIGURATION is not set, but identity login is enabled.');
        }

        $config = Cache::remember('identity_config', now()->addDay(), function () use ($discoveryUrl) {
            return Http::get($discoveryUrl)->throw()->json();
        });
        $this->issuer = $config['issuer'];
        $this->userinfoEndpoint = $config['userinfo_endpoint'];
        $this->authorizationEndpoint = $config['authorization_endpoint'];
        $this->tokenEndpoint = $config['token_endpoint'];
        $this->jwksUri = $config['jwks_uri'];
        $this->endSessionEndpoint = $config['end_session_endpoint'];
        $this->revocationEndpoint = $config['revocation_endpoint'];

        return $this;
    }

    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getIdentityConfig()->authorizationEndpoint, $state);
    }

    protected function getTokenUrl()
    {
        return $this->getIdentityConfig()->tokenEndpoint;
    }

    /**
     * The identity server only accepts client_secret_basic authentication
     */
    protected function getTokenHeaders($code)
    {
        return array_merge(parent::getTokenHeaders($code), [
            'Authorization' => 'Basic ' . base64_encode(rawurlencode($this->clientId) . ':' . rawurlencode($this->clientSecret)),
        ]);
    }

    protected function getTokenFields($code)
    {
        return array_diff_key(parent::getTokenFields($code), array_flip(['client_id', 'client_secret']));
    }

    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getIdentityConfig()->userinfoEndpoint, [
            'headers' => [
                'cache-control' => 'no-cache',
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['sub'],
            'email' => $user['email'],
            'email_verified' => $user['email_verified'],
            'avatar' => $user['avatar'],
            'name' => $user['name'],
            'groups' => $user['groups'],
        ]);
    }
}

<?php
class JwtService
{
    private string $secret;
    private string $issuer;

    public function __construct(array $config)
    {
        $this->secret = $config['secret'];
        $this->issuer = $config['issuer'];
    }

    public function encode(array $claims, int $ttl): string
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $now = time();
        $payload = array_merge($claims, [
            'iss' => $this->issuer,
            'iat' => $now,
            'exp' => $now + $ttl,
        ]);

        $segments = [
            $this->base64Url(json_encode($header)),
            $this->base64Url(json_encode($payload)),
        ];
        $signingInput = implode('.', $segments);
        $signature = hash_hmac('sha256', $signingInput, $this->secret, true);
        $segments[] = $this->base64Url($signature);
        return implode('.', $segments);
    }

    public function decode(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        [$headerB64, $payloadB64, $signatureB64] = $parts;
        $signingInput = $headerB64 . '.' . $payloadB64;
        $expected = $this->base64Url(hash_hmac('sha256', $signingInput, $this->secret, true));
        if (!hash_equals($expected, $signatureB64)) {
            return null;
        }
        $payload = json_decode($this->base64UrlDecode($payloadB64), true);
        if (!isset($payload['exp']) || $payload['exp'] < time()) {
            return null;
        }
        return $payload;
    }

    private function base64Url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}

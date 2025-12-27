<?php
require_once __DIR__ . '/JwtService.php';
require_once __DIR__ . '/Response.php';

class AuthService
{
    private \PDO $pdo;
    private JwtService $jwt;
    private array $jwtConfig;

    public function __construct(\PDO $pdo, array $jwtConfig)
    {
        $this->pdo = $pdo;
        $this->jwt = new JwtService($jwtConfig);
        $this->jwtConfig = $jwtConfig;
    }

    public function requestOtp(string $phone): void
    {
        $otp = random_int(100000, 999999);
        $expires = (new DateTime('+5 minutes'))->format('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('REPLACE INTO otp_codes (phone, otp_code, expires_at) VALUES (:phone, :otp, :expires)');
        $stmt->execute([':phone' => $phone, ':otp' => $otp, ':expires' => $expires]);
        Response::json(['message' => 'OTP generated for development use', 'otp' => $otp]);
    }

    public function verifyOtp(string $phone, string $otp): void
    {
        $stmt = $this->pdo->prepare('SELECT otp_code, expires_at FROM otp_codes WHERE phone = :phone');
        $stmt->execute([':phone' => $phone]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row || $row['otp_code'] !== $otp || strtotime($row['expires_at']) < time()) {
            Response::json(['error' => 'Invalid or expired OTP'], 401);
            return;
        }

        $userStmt = $this->pdo->prepare('SELECT * FROM users WHERE phone = :phone');
        $userStmt->execute([':phone' => $phone]);
        $user = $userStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user) {
            Response::json(['error' => 'User not found. Registration via kiosk required.'], 404);
            return;
        }

        $tokens = $this->issueTokens((int)$user['user_id'], $user['role']);
        Response::json(['access_token' => $tokens['access'], 'refresh_token' => $tokens['refresh']]);
    }

    public function adminLogin(string $phone, string $password): void
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE phone = :phone AND role IN ("ADMIN", "SUPER")');
        $stmt->execute([':phone' => $phone]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            Response::json(['error' => 'Invalid credentials'], 401);
            return;
        }
        $tokens = $this->issueTokens((int)$user['user_id'], $user['role']);
        Response::json(['access_token' => $tokens['access'], 'refresh_token' => $tokens['refresh']]);
    }

    public function refreshToken(int $userId, string $deviceId, string $token): void
    {
        $stmt = $this->pdo->prepare('SELECT * FROM oauth_refresh_tokens WHERE user_id = :user_id AND device_id = :device_id AND refresh_token = :token AND revoked = 0 AND expires_at > NOW()');
        $stmt->execute([':user_id' => $userId, ':device_id' => $deviceId, ':token' => $token]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            Response::json(['error' => 'Invalid refresh token'], 401);
            return;
        }
        $access = $this->jwt->encode(['sub' => $userId, 'role' => $this->getRole($userId)], $this->jwtConfig['access_ttl']);
        Response::json(['access_token' => $access]);
    }

    public function logout(int $userId, string $deviceId, string $token): void
    {
        $stmt = $this->pdo->prepare('UPDATE oauth_refresh_tokens SET revoked = 1 WHERE user_id = :user_id AND device_id = :device_id AND refresh_token = :token');
        $stmt->execute([':user_id' => $userId, ':device_id' => $deviceId, ':token' => $token]);
        Response::json(['message' => 'Logged out']);
    }

    public function authenticate(string $header): ?array
    {
        if (!str_starts_with($header, 'Bearer ')) {
            return null;
        }
        $token = trim(substr($header, 7));
        return $this->jwt->decode($token);
    }

    private function issueTokens(int $userId, string $role): array
    {
        $access = $this->jwt->encode(['sub' => $userId, 'role' => $role], $this->jwtConfig['access_ttl']);
        $refresh = bin2hex(random_bytes(32));
        $expires = (new DateTime('+' . $this->jwtConfig['refresh_ttl'] . ' seconds'))->format('Y-m-d H:i:s');

        $stmt = $this->pdo->prepare('INSERT INTO oauth_refresh_tokens (user_id, device_id, refresh_token, expires_at) VALUES (:user_id, :device_id, :token, :expires)');
        $stmt->execute([
            ':user_id' => $userId,
            ':device_id' => 'web',
            ':token' => $refresh,
            ':expires' => $expires,
        ]);

        return ['access' => $access, 'refresh' => $refresh];
    }

    private function getRole(int $userId): string
    {
        $stmt = $this->pdo->prepare('SELECT role FROM users WHERE user_id = :user_id');
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? $row['role'] : 'USER';
    }
}

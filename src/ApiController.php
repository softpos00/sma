<?php
require_once __DIR__ . '/Response.php';

class ApiController
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function mosques(): void
    {
        $stmt = $this->pdo->query('SELECT mosque_id, name, address, latitude, longitude, radius, is_active FROM mosques WHERE is_active = 1');
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        Response::json($rows);
    }

    public function mosque(int $id): void
    {
        $stmt = $this->pdo->prepare('SELECT * FROM mosques WHERE mosque_id = :id');
        $stmt->execute([':id' => $id]);
        $mosque = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$mosque) {
            Response::json(['error' => 'Mosque not found'], 404);
            return;
        }
        Response::json($mosque);
    }

    public function today(int $id): void
    {
        $today = (new DateTime('now'))->format('Y-m-d');
        $stmt = $this->pdo->prepare('SELECT * FROM mosque_daily_prayer_times WHERE mosque_id = :id AND prayer_date = :day');
        $stmt->execute([':id' => $id, ':day' => $today]);
        $record = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$record) {
            $record = $this->fallbackTimes($id);
        }
        Response::json(['date' => $today, 'times' => $record]);
    }

    public function nextPrayer(int $id): void
    {
        $today = new DateTime();
        $this->today($id); // ensures fallback logic
        $stmt = $this->pdo->prepare('SELECT * FROM mosque_daily_prayer_times WHERE mosque_id = :id AND prayer_date = :day');
        $stmt->execute([':id' => $id, ':day' => $today->format('Y-m-d')]);
        $record = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$record) {
            $record = $this->fallbackTimes($id);
        }
        $times = array_filter([
            'fajr' => $record['fajr'] ?? null,
            'zuhr' => $record['zuhr'] ?? null,
            'asr' => $record['asr'] ?? null,
            'maghrib' => $record['maghrib'] ?? null,
            'isha' => $record['isha'] ?? null,
        ]);
        $next = null;
        foreach ($times as $name => $time) {
            $candidate = DateTime::createFromFormat('Y-m-d H:i:s', $today->format('Y-m-d') . ' ' . $time);
            if ($candidate && $candidate > $today) {
                $next = ['name' => ucfirst($name), 'time' => $time];
                break;
            }
        }
        Response::json($next ?: ['message' => 'No more prayers today']);
    }

    public function updateDaily(array $payload): void
    {
        $stmt = $this->pdo->prepare('REPLACE INTO mosque_daily_prayer_times (mosque_id, prayer_date, fajr, zuhr, asr, maghrib, isha) VALUES (:mosque_id, :date, :fajr, :zuhr, :asr, :maghrib, :isha)');
        $stmt->execute([
            ':mosque_id' => $payload['mosque_id'],
            ':date' => $payload['prayer_date'],
            ':fajr' => $payload['fajr'] ?? null,
            ':zuhr' => $payload['zuhr'] ?? null,
            ':asr' => $payload['asr'] ?? null,
            ':maghrib' => $payload['maghrib'] ?? null,
            ':isha' => $payload['isha'] ?? null,
        ]);
        Response::json(['message' => 'Prayer time saved']);
    }

    public function updateJummah(array $payload): void
    {
        $stmt = $this->pdo->prepare('UPDATE jamaah_times SET jummah = :jummah WHERE mosque_id = :mosque_id');
        $stmt->execute([
            ':mosque_id' => $payload['mosque_id'],
            ':jummah' => $payload['jummah'],
        ]);
        Response::json(['message' => 'Jumu\'ah updated']);
    }

    private function fallbackTimes(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT fajr, zuhr, asr, maghrib, isha FROM jamaah_times WHERE mosque_id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
    }
}

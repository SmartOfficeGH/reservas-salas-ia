<?php
declare(strict_types=1);

final class UserError extends RuntimeException {}

final class Service
{
    public function __construct(private PDO $db, private Closure $sendMail) {}

    public static function connect(array $config): PDO
    {
        $d = $config['db'];
        $db = new PDO("mysql:host={$d['host']};port={$d['port']};dbname={$d['name']};charset=utf8mb4", $d['user'], $d['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $db->exec("SET time_zone = '+00:00'");
        return $db;
    }

    private function query(string $sql, array $params = []): PDOStatement
    {
        $q = $this->db->prepare($sql);
        $q->execute($params);
        return $q;
    }

    public static function email(string $email): string
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254 || substr(strrchr($email, '@') ?: '', 1) !== 'palma.es') {
            throw new UserError('Utiliza una dirección cuyo dominio sea exactamente palma.es.');
        }
        return $email;
    }

    public static function hashPassword(string $password): string
    {
        if (mb_strlen($password) < 12) throw new UserError('La contraseña debe tener al menos 12 caracteres.');
        if (strlen($password) > 72) throw new UserError('La contraseña es demasiado larga. Acórtala y vuelve a intentarlo.');
        if (str_contains($password, "\0")) throw new UserError('La contraseña contiene un carácter no admitido.');
        return password_hash($password, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT);
    }

    public function throttle(string $bucket, int $limit = 20): void
    {
        $key = hash('sha256', $bucket);
        $this->db->beginTransaction();
        try {
            $this->query('INSERT IGNORE INTO rate_limits (bucket,hits,expires_at) VALUES (?,0,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 15 MINUTE))', [$key]);
            $row = $this->query('SELECT *, expires_at <= UTC_TIMESTAMP() AS expired FROM rate_limits WHERE bucket=? FOR UPDATE', [$key])->fetch();
            if ($row['expired']) {
                $this->query('UPDATE rate_limits SET hits=1,expires_at=DATE_ADD(UTC_TIMESTAMP(),INTERVAL 15 MINUTE) WHERE bucket=?', [$key]);
            } elseif ((int)$row['hits'] >= $limit) {
                throw new UserError('Demasiados intentos. Espera 15 minutos antes de volver a intentarlo.');
            } else {
                $this->query('UPDATE rate_limits SET hits=hits+1 WHERE bucket=?', [$key]);
            }
            $this->db->commit();
        } catch (Throwable $e) { $this->db->rollBack(); throw $e; }
    }

    public function register(string $email, string $password): void
    {
        $email = self::email($email);
        $hash = self::hashPassword($password);
        try {
            $this->query('INSERT INTO users (email,password_hash) VALUES (?,?)', [$email, $hash]);
            $id = (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') throw $e;
            // No cambiar contraseñas de una cuenta existente desde registro.
            return;
        }
        $this->issueToken($id, 'verify');
    }

    public function requestToken(string $email, string $kind): void
    {
        $email = self::email($email);
        $u = $this->query('SELECT * FROM users WHERE email=?', [$email])->fetch();
        if (!$u || ($kind === 'verify' && $u['verified_at'] !== null) || ($kind === 'reset' && $u['verified_at'] === null)) return;
        $this->issueToken((int)$u['id'], $kind);
    }

    private function issueToken(int $id, string $kind): void
    {
        if (!in_array($kind, ['verify','reset'], true)) throw new LogicException('Token type');
        $raw = bin2hex(random_bytes(32));
        $expiry = gmdate('Y-m-d H:i:s', time() + ($kind === 'verify' ? 86400 : 1800));
        $this->db->beginTransaction();
        try {
            $u = $this->query('SELECT * FROM users WHERE id=? FOR UPDATE', [$id])->fetch();
            $this->query('DELETE FROM email_tokens WHERE user_id=? AND kind=?', [$id,$kind]);
            $this->query('INSERT INTO email_tokens (user_id,kind,token_hash,expires_at) VALUES (?,?,?,?)', [$id,$kind,hash('sha256',$raw),$expiry]);
            $this->db->commit();
        } catch (Throwable $e) { $this->db->rollBack(); throw $e; }
        // Solo el destinatario recibe el secreto; la base conserva un hash.
        ($this->sendMail)($u['email'], $kind, $raw);
    }

    public function consumeToken(string $raw, string $kind, ?string $password = null): void
    {
        if (!preg_match('/^[a-f0-9]{64}$/D', $raw) || !in_array($kind, ['verify','reset'], true)) throw new UserError('Enlace inválido o caducado. Solicita uno nuevo.');
        $hash = $password === null ? null : self::hashPassword($password);
        $this->db->beginTransaction();
        try {
            // Descubrir propietario; bloquear siempre primero usuario y luego token.
            $candidate = $this->query('SELECT user_id FROM email_tokens WHERE token_hash=? AND kind=?', [hash('sha256',$raw),$kind])->fetch();
            if (!$candidate) throw new UserError('Enlace inválido o caducado. Solicita uno nuevo.');
            $u = $this->query('SELECT * FROM users WHERE id=? FOR UPDATE', [$candidate['user_id']])->fetch();
            $token = $this->query('SELECT * FROM email_tokens WHERE token_hash=? AND kind=? AND expires_at > UTC_TIMESTAMP() FOR UPDATE', [hash('sha256',$raw),$kind])->fetch();
            if (!$token) throw new UserError('Enlace inválido o caducado. Solicita uno nuevo.');
            if ($kind === 'verify') {
                $this->query('UPDATE users SET verified_at=COALESCE(verified_at,UTC_TIMESTAMP()) WHERE id=?', [$u['id']]);
                $this->query('DELETE FROM email_tokens WHERE id=?', [$token['id']]);
            } else {
                if ($hash === null || $u['verified_at'] === null) throw new UserError('No se puede recuperar esta cuenta.');
                $this->query('UPDATE users SET password_hash=?,auth_version=auth_version+1 WHERE id=?', [$hash,$u['id']]);
                $this->query('DELETE FROM email_tokens WHERE user_id=?', [$u['id']]);
            }
            $this->db->commit();
        } catch (Throwable $e) { $this->db->rollBack(); throw $e; }
    }

    public function login(string $email, string $password): array
    {
        try { $email = self::email($email); } catch (UserError) { throw new UserError('Correo o contraseña incorrectos.'); }
        $u = $this->query('SELECT * FROM users WHERE email=?', [$email])->fetch();
        $dummy = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi';
        if (!password_verify($password, $u['password_hash'] ?? $dummy) || !$u) throw new UserError('Correo o contraseña incorrectos.');
        if ($u['verified_at'] === null) throw new UserError('Verifica tu correo antes de acceder. Puedes solicitar otro enlace.');
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
        if (password_needs_rehash($u['password_hash'], $algo)) {
            $this->query('UPDATE users SET password_hash=? WHERE id=? AND password_hash=?', [password_hash($password,$algo),$u['id'],$u['password_hash']]);
        }
        return $u;
    }

    public function user(int $id): array|false { return $this->query('SELECT id,email,verified_at,auth_version FROM users WHERE id=?', [$id])->fetch(); }
    public function rooms(): array { return $this->query('SELECT * FROM rooms WHERE is_reservable=1 ORDER BY id')->fetchAll(); }
    public function publicRooms():array { return $this->query('SELECT id,name FROM rooms WHERE is_reservable=1 ORDER BY id')->fetchAll(); }
    public function availability(int $room,string $day,string $start,string $end=''):array
    {
        if(!preg_match('/^\d{2}:\d{2}$/',$start))throw new UserError('Indica una hora válida.');
        $minute=Occupancy::minute($start);
        $validationEnd=$end?:Occupancy::clock($minute+1);
        self::validateBooking(['concept'=>'Consulta de disponibilidad','day'=>$day,'start'=>$start,'end'=>$validationEnd]);
        if(!$this->query('SELECT id FROM rooms WHERE id=? AND is_reservable=1',[$room])->fetch())throw new UserError('Elige una sala reservable.');
        $events=$this->query('SELECT starts_at,ends_at FROM reservations WHERE room_id=? AND day=? ORDER BY starts_at',[$room,$day])->fetchAll();
        $until=960;
        foreach($events as $event) {
            $a=Occupancy::minute($event['starts_at']);$b=Occupancy::minute($event['ends_at']);
            if($minute>=$a&&$minute<$b)return ['available'=>false,'proposed_end'=>'','message'=>'Esta sala está ocupada a esa hora. Elige otra sala u otro horario.'];
            if($a>=$minute)$until=min($until,$a);
        }
        $available=$end===''||Occupancy::minute($end)<=$until;
        return ['available'=>$available,'proposed_end'=>$minute+30<=$until?Occupancy::clock($minute+30):'',
            'message'=>$available?'Disponibilidad consultada. Confirma la reserva para guardarla.':'Esta sala está ocupada en parte del horario elegido.'];
    }
    public function publicPeriod(string $start,string $end):array
    {
        // Proyección explícita: los datos privados ni siquiera se cargan.
        return $this->query('SELECT r.room_id,r.day,r.starts_at,r.ends_at FROM reservations r JOIN rooms s ON s.id=r.room_id WHERE r.day BETWEEN ? AND ? AND s.is_reservable=1 ORDER BY r.day,r.starts_at',[$start,$end])->fetchAll();
    }
    public function agenda(string $day): array { return $this->query('SELECT r.* FROM reservations r JOIN rooms s ON s.id=r.room_id WHERE r.day=? AND s.is_reservable=1 ORDER BY r.starts_at', [$day])->fetchAll(); }
    public function period(string $start,string $end):array { return $this->query('SELECT r.* FROM reservations r JOIN rooms s ON s.id=r.room_id WHERE r.day BETWEEN ? AND ? AND s.is_reservable=1 ORDER BY r.day,r.starts_at',[$start,$end])->fetchAll(); }
    public function mine(int $id): array { return $this->query('SELECT r.*,s.name,s.building FROM reservations r JOIN rooms s ON s.id=r.room_id WHERE r.user_id=? ORDER BY r.day,r.starts_at', [$id])->fetchAll(); }

    public static function validateBooking(array $r, ?DateTimeImmutable $now = null): void
    {
        if (!isset($r['concept']) || !is_string($r['concept']) || !preg_match('/\S/u', $r['concept']) || mb_strlen($r['concept']) > 255) throw new UserError('Indica un concepto de entre 1 y 255 caracteres.');
        $zone = new DateTimeZone('Europe/Madrid');
        $day = DateTimeImmutable::createFromFormat('!Y-m-d', $r['day'] ?? '', $zone);
        if (!$day || $day->format('Y-m-d') !== ($r['day'] ?? '') || (int)$day->format('N') > 5) throw new UserError('Selecciona una fecha válida de lunes a viernes.');
        foreach (['start','end'] as $field) if (!preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D', $r[$field] ?? '')) throw new UserError('Indica las horas de inicio y fin.');
        if ($r['end'] <= $r['start']) throw new UserError('La hora de fin debe ser posterior a la de inicio.');
        if ($r['start'] < '07:00' || $r['end'] > '16:00') throw new UserError('La reserva debe quedar completamente entre las 07:00 y las 16:00.');
        $start = new DateTimeImmutable($r['day'].' '.$r['start'], $zone);
        if ($start < ($now ?? new DateTimeImmutable('now', $zone))) throw new UserError('No se permiten reservas en el pasado.');
    }

    public function book(int $userId, array $r): int
    {
        self::validateBooking($r);
        $this->db->beginTransaction();
        try {
            $u = $this->query('SELECT verified_at FROM users WHERE id=? FOR UPDATE', [$userId])->fetch();
            if (!$u || !$u['verified_at']) throw new UserError('Debes verificar el correo para reservar.');
            // Mutex InnoDB por sala: incluye el caso en que todavía no hay reservas.
            $room = $this->query('SELECT id,is_reservable FROM rooms WHERE id=? FOR UPDATE', [$r['room_id'] ?? 0])->fetch();
            if (!$room || !(int)$room['is_reservable']) throw new UserError('Esta sala no está disponible para nuevas reservas. Selecciona una sala reservable.');
            self::validateBooking($r); // El horario podría haber pasado mientras se esperaba el bloqueo.
            $overlap = $this->query('SELECT id FROM reservations WHERE room_id=? AND day=? AND starts_at < ? AND ends_at > ? FOR UPDATE', [$r['room_id'],$r['day'],$r['end'],$r['start']])->fetch();
            if ($overlap) throw new UserError('La sala ya está reservada en parte de ese horario. Elige otro horario.');
            $this->query('INSERT INTO reservations (user_id,room_id,concept,day,starts_at,ends_at) VALUES (?,?,?,?,?,?)', [$userId,$r['room_id'],trim($r['concept']),$r['day'],$r['start'],$r['end']]);
            $id = (int)$this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (Throwable $e) { $this->db->rollBack(); throw $e; }
    }

    public function cancel(int $userId, int $reservationId): void
    {
        // El propietario forma parte de la condición de borrado, no solo de la interfaz.
        $q = $this->query('DELETE FROM reservations WHERE id=? AND user_id=?', [$reservationId,$userId]);
        if ($q->rowCount() !== 1) throw new UserError('La reserva no existe o no te pertenece.');
    }
}

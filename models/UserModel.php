<?php
require_once __DIR__ . '/../config/database.php';

class UserModel {
    public function findByEmail(string $email): ?array {
        return dbOne("SELECT * FROM users WHERE email = ? LIMIT 1", 's', [$email]);
    }

    public function findById(int $id): ?array {
        return dbOne("SELECT * FROM users WHERE id = ? LIMIT 1", 'i', [$id]);
    }

    /** $hashedToken must already be the sha256 of the cookie token. */
    public function findByRememberToken(string $hashedToken): ?array {
        return dbOne("SELECT * FROM users WHERE remember_token = ? LIMIT 1", 's', [$hashedToken]);
    }

    public function create(string $name, string $email, string $password, string $role): int {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
        return dbExec("INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,?)", 'ssss', [$name, $email, $hash, $role]);
    }

    public function updateProfile(int $id, string $name, string $email, ?string $pic): void {
        if ($pic) {
            dbExec("UPDATE users SET name=?, email=?, profile_picture=? WHERE id=?", 'sssi', [$name, $email, $pic, $id]);
        } else {
            dbExec("UPDATE users SET name=?, email=? WHERE id=?", 'ssi', [$name, $email, $id]);
        }
    }

    public function updatePassword(int $id, string $password): void {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
        dbExec("UPDATE users SET password_hash=? WHERE id=?", 'si', [$hash, $id]);
    }

    /** Stores the already-hashed token as given (pass null to clear). */
    public function setRememberToken(int $id, ?string $hashedToken): void {
        dbExec("UPDATE users SET remember_token=? WHERE id=?", 'si', [$hashedToken, $id]);
    }

    public function clearRememberToken(int $id): void {
        $this->setRememberToken($id, null);
    }

    public function getAllMembers(): array {
        return dbAll("SELECT id,name,email,profile_picture,created_at FROM users WHERE role='member' ORDER BY created_at DESC");
    }

    public function delete(int $id): void {
        dbExec("DELETE FROM users WHERE id=?", 'i', [$id]);
    }
}

<?php

declare(strict_types=1);
// app/models/Company.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/SmartCache.php';

class Company
{
    /**
     * Gera variações comuns de slug para resolver legados (_ e -, case).
     *
     * @return string[]
     */
    private static function slugCandidates(string $slug): array
    {
        $decoded = trim(rawurldecode($slug));
        if ($decoded === '') {
            return [];
        }

        $candidates = [$decoded];
        $dash = str_replace('_', '-', $decoded);
        $underscore = str_replace('-', '_', $decoded);

        if ($dash !== '') {
            $candidates[] = $dash;
        }
        if ($underscore !== '') {
            $candidates[] = $underscore;
        }

        return array_values(array_unique($candidates));
    }

    /** Busca empresa pelo slug (url amigável) */
    public static function findBySlug(string $slug): ?array
    {
        $normalizedSlug = trim(rawurldecode($slug));
        if ($normalizedSlug === '') {
            return null;
        }

        $key = 'company:slug:' . strtolower($normalizedSlug);

        return SmartCache::remember($key, function() use ($normalizedSlug) {
            $st = db()->prepare('SELECT * FROM companies WHERE LOWER(slug) = LOWER(?) LIMIT 1');
            $st->execute([$normalizedSlug]);
            $row = $st->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                return $row;
            }

            $fallbackCandidates = [
                str_replace('_', '-', $normalizedSlug),
                str_replace('-', '_', $normalizedSlug),
            ];

            foreach ($fallbackCandidates as $candidate) {
                if ($candidate === '' || $candidate === $normalizedSlug) {
                    continue;
                }

                $st = db()->prepare('SELECT * FROM companies WHERE LOWER(slug) = LOWER(?) LIMIT 1');
                $st->execute([$candidate]);
                $row = $st->fetch(PDO::FETCH_ASSOC);

                if ($row) {
                    return $row;
                }
            }

            return $row ?: null;
        }, 600); // 10 minutos (empresas mudam raramente)
    }

    /** Busca empresa pelo ID */
    public static function find(int $id): ?array
    {
        $key = "company:id:{$id}";
        
        return SmartCache::remember($key, function() use ($id) {
            $st = db()->prepare('SELECT * FROM companies WHERE id = ? LIMIT 1');
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);

            return $row ?: null;
        }, 600);
    }

    /** Lista todas as empresas (ex.: para painel admin global) */
    public static function all(): array
    {
        $key = "companies:all";
        
        return SmartCache::remember($key, function() {
            $st = db()->query('SELECT * FROM companies ORDER BY name ASC');

            return $st->fetchAll(PDO::FETCH_ASSOC);
        }, 300);
    }

    /** Cria nova empresa e retorna ID */
    public static function create(array $data): int
    {
        $st = db()->prepare('
            INSERT INTO companies (name, slug, logo, active, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ');
        $st->execute([
            $data['name'],
            $data['slug'],
            $data['logo'] ?? null,
            isset($data['active']) ? (int)$data['active'] : 1,
        ]);

        $id = (int) db()->lastInsertId();
        
        // Invalidar cache
        SmartCache::forgetByPattern('companies:*');
        foreach (self::slugCandidates((string)($data['slug'] ?? '')) as $candidate) {
            SmartCache::forget('company:slug:' . strtolower($candidate));
            SmartCache::forget('company:slug:' . $candidate);
        }
        
        return $id;
    }

    /** Atualiza empresa existente */
    public static function update(int $id, array $data): void
    {
        $previous = self::find($id);

        $st = db()->prepare('
            UPDATE companies
               SET name = ?, slug = ?, logo = ?, active = ?, updated_at = NOW()
             WHERE id = ?
        ');
        $st->execute([
            $data['name'],
            $data['slug'],
            $data['logo'] ?? null,
            isset($data['active']) ? (int)$data['active'] : 1,
            $id
        ]);
        
        // Invalidar cache
        SmartCache::forget("company:id:{$id}");
        foreach (self::slugCandidates((string)($data['slug'] ?? '')) as $candidate) {
            SmartCache::forget('company:slug:' . strtolower($candidate));
            SmartCache::forget('company:slug:' . $candidate);
        }
        if ($previous) {
            foreach (self::slugCandidates((string)($previous['slug'] ?? '')) as $candidate) {
                SmartCache::forget('company:slug:' . strtolower($candidate));
                SmartCache::forget('company:slug:' . $candidate);
            }
        }
        SmartCache::forgetByPattern('companies:*');
    }

    /** Remove empresa (pode adaptar para soft delete se preferir) */
    public static function delete(int $id): void
    {
        // Buscar dados antes de deletar para invalidar cache
        $company = self::find($id);
        
        $st = db()->prepare('DELETE FROM companies WHERE id = ?');
        $st->execute([$id]);
        
        // Invalidar cache
        SmartCache::forget("company:id:{$id}");
        if ($company) {
            foreach (self::slugCandidates((string)($company['slug'] ?? '')) as $candidate) {
                SmartCache::forget('company:slug:' . strtolower($candidate));
                SmartCache::forget('company:slug:' . $candidate);
            }
        }
        SmartCache::forgetByPattern('companies:*');
    }

    public static function updateDeliveryOptions(int $id, float $afterHoursFee, bool $freeDelivery): void
    {
        // Se ativar taxa gratuita para todos, desativar frete grátis promocional
        if ($freeDelivery) {
            $st = db()->prepare(
                'UPDATE companies SET delivery_after_hours_fee = ?, delivery_free_enabled = ?, delivery_free_min_value = 0 WHERE id = ?'
            );
            $st->execute([
                number_format($afterHoursFee, 2, '.', ''),
                1,
                $id,
            ]);
        } else {
            $st = db()->prepare(
                'UPDATE companies SET delivery_after_hours_fee = ?, delivery_free_enabled = ? WHERE id = ?'
            );
            $st->execute([
                number_format($afterHoursFee, 2, '.', ''),
                0,
                $id,
            ]);
        }
        
        // Invalidar cache da empresa
        SmartCache::forget("company:id:{$id}");
        $company = self::find($id);
        if ($company) {
            foreach (self::slugCandidates((string)($company['slug'] ?? '')) as $candidate) {
                SmartCache::forget('company:slug:' . strtolower($candidate));
                SmartCache::forget('company:slug:' . $candidate);
            }
        }
    }
}

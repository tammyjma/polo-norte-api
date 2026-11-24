<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class EstabloRepository
{
    private const KEY_RENOS = 'polonorte_renos';
    private const KEY_MANT = 'polonorte_mantenimientos';

    public function listarRenos(?string $estado = null): array
    {
        $renos = Cache::get(self::KEY_RENOS, []);
        if ($estado) {
            $renos = array_filter($renos, fn($r) => $r['estado'] === $estado);
        }
        return array_values($renos);
    }

    public function buscarReno(string $id): ?array
    {
        $renos = Cache::get(self::KEY_RENOS, []);
        return $renos[$id] ?? null;
    }

    public function existeNombreReno(string $nombre, ?string $excluirId = null): bool
    {
        foreach (Cache::get(self::KEY_RENOS, []) as $id => $reno) {
            if (strcasecmp($reno['nombre'], $nombre) === 0 && $id !== $excluirId) {
                return true;
            }
        }
        return false;
    }

    public function guardarReno(array $data, ?string $id = null): array
    {
        $renos = Cache::get(self::KEY_RENOS, []);
        $id = $id ?? (string) Str::uuid();
        $reno = array_merge($renos[$id] ?? [], $data, ['id' => $id]);
        $renos[$id] = $reno;
        Cache::put(self::KEY_RENOS, $renos);
        return $reno;
    }

    public function eliminarReno(string $id): bool
    {
        $renos = Cache::get(self::KEY_RENOS, []);
        if (!isset($renos[$id])) {
            return false;
        }
        unset($renos[$id]);
        Cache::put(self::KEY_RENOS, $renos);
        return true;
    }

    public function listarMantenimientos(?string $zona = null): array
    {
        $items = Cache::get(self::KEY_MANT, []);
        if ($zona) {
            $items = array_filter($items, fn($m) => $m['zona'] === $zona);
        }
        return array_values($items);
    }

    public function buscarMantenimiento(string $id): ?array
    {
        $items = Cache::get(self::KEY_MANT, []);
        return $items[$id] ?? null;
    }

    public function guardarMantenimiento(array $data, ?string $id = null): array
    {
        $items = Cache::get(self::KEY_MANT, []);
        $id = $id ?? (string) Str::uuid();
        $item = array_merge($items[$id] ?? [], $data, ['id' => $id]);
        $items[$id] = $item;
        Cache::put(self::KEY_MANT, $items);
        return $item;
    }
}
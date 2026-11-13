<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AvatarService
{
    private const DEFAULT_CONFIGURATION = [
        'skin' => 'light',
        'hairColor' => 'black',
        'hairStyle' => 'short',
        'eyeStyle' => 'round',
        'eyebrowStyle' => 'natural',
        'noseStyle' => 'small',
        'headShape' => 'round',
        'earStyle' => 'normal',
        'bodyType' => 'classic',
        'outfit' => 'green',
        'accessory' => 'none',
    ];

    private const REWARD_MEDALS = [
        'hat' => 'voluntario-seguro',
        'mangrove-leaf' => 'guardian-manglar',
        'recycling-badge' => 'agente-circular',
        'organizer-vest' => 'lider-responsable',
    ];

    public function defaults(): array
    {
        return self::DEFAULT_CONFIGURATION;
    }

    public function rewards(array $earnedMedals): array
    {
        $labels = [
            'hat' => 'Gorra de explorador',
            'mangrove-leaf' => 'Insignia de manglar',
            'recycling-badge' => 'Insignia circular',
            'organizer-vest' => 'Chaleco de organizador',
        ];

        return collect(self::REWARD_MEDALS)
            ->map(fn (string $medal, string $item) => [
                'item' => $item,
                'nombre' => $labels[$item],
                'medalla' => $medal,
                'desbloqueado' => in_array($medal, $earnedMedals, true),
            ])
            ->values()
            ->all();
    }

    public function validateConfiguration(array $configuration, array $earnedMedals): array
    {
        $validated = Validator::make($configuration, [
            'skin' => ['sometimes', 'string', Rule::in(['light', 'medium', 'dark'])],
            'hairColor' => ['sometimes', 'string', Rule::in(['black', 'brown', 'blonde'])],
            'hairStyle' => ['sometimes', 'string', Rule::in(['short', 'long', 'bob', 'curly'])],
            'eyeStyle' => ['sometimes', 'string', Rule::in(['round', 'happy', 'star'])],
            'eyebrowStyle' => ['sometimes', 'string', Rule::in(['natural', 'straight', 'arched', 'thick'])],
            'noseStyle' => ['sometimes', 'string', Rule::in(['small', 'triangle', 'soft', 'none'])],
            'headShape' => ['sometimes', 'string', Rule::in(['round', 'oval', 'wide', 'square'])],
            'earStyle' => ['sometimes', 'string', Rule::in(['normal', 'large', 'elf', 'small'])],
            'bodyType' => ['sometimes', 'string', Rule::in(['classic', 'round', 'slim', 'robust'])],
            'outfit' => ['sometimes', 'string', Rule::in(['green', 'white', 'pink', 'blue'])],
            'accessory' => ['sometimes', 'string', Rule::in(['none', 'sunglasses', ...array_keys(self::REWARD_MEDALS)])],
        ])->validate();

        $result = array_merge(self::DEFAULT_CONFIGURATION, $validated);
        $requiredMedal = self::REWARD_MEDALS[$result['accessory']] ?? null;

        if ($requiredMedal && ! in_array($requiredMedal, $earnedMedals, true)) {
            throw ValidationException::withMessages([
                'avatarConfig.accessory' => 'Desbloquea la medalla correspondiente antes de equipar este cosmético.',
            ]);
        }

        return $result;
    }
}
<?php

namespace Tests\Unit;

use App\Services\AvatarService;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AvatarServiceTest extends TestCase
{
    public function test_defaults_provide_a_complete_editable_avatar_configuration(): void
    {
        $configuration = (new AvatarService)->validateConfiguration([], []);

        $this->assertSame('green', $configuration['outfit']);
        $this->assertSame('none', $configuration['accessory']);
        $this->assertCount(11, $configuration);
    }

    public function test_reward_cosmetics_require_their_medal(): void
    {
        $this->expectException(ValidationException::class);

        (new AvatarService)->validateConfiguration(['accessory' => 'mangrove-leaf'], []);
    }

    public function test_unlocked_cosmetics_can_be_equipped(): void
    {
        $configuration = (new AvatarService)->validateConfiguration(
            ['accessory' => 'mangrove-leaf'],
            ['guardian-manglar'],
        );

        $this->assertSame('mangrove-leaf', $configuration['accessory']);
    }

    public function test_user_rehydrates_avatar_configuration_as_an_array(): void
    {
        $user = new User;
        $user->setRawAttributes(['avatarConfig' => ['skin' => 'dark']]);

        $this->assertSame(['skin' => 'dark'], $user->avatarConfig);
    }
}
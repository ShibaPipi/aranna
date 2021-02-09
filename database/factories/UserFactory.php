<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Users\Address;
use App\Models\Users\User;
use Faker\Generator as Faker;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(User::class, function (Faker $faker) {
    return [
        'username' => $faker->name,
        'password' => bcrypt('123456'),
        'gender' => $faker->randomKey([0, 1, 2]),
        'mobile' => $faker->phoneNumber,
        'avatar' => $faker->imageUrl(),
    ];
});

$factory->define(Address::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'user_id' => 0,
        'province' => '天津市',
        'city' => '市辖区',
        'county' => '和平区',
        'address_detail' => $faker->streetAddress,
        'area_code' => '',
        'postal_code' => $faker->postcode,
        'tel' => $faker->phoneNumber,
        'is_default' => 0,
    ];
});

$factory->state(User::class, 'default_address', function () {
    return [];
})->afterCreatingState(User::class, 'default_address', function (User $user) {
    factory(Address::class)->create([
        'user_id' => $user->id,
        'is_default' => 1,
    ]);
});

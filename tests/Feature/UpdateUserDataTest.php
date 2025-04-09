<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UpdateUserDataTest extends TestCase
{
    use RefreshDatabase; // Reset the database after each test

    /**
     * Set up the test environment.
     * This method seeds the database before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class); // Seed the database with test users
    }

    /**
     * Test that an authenticated user can modify their data.
     * This ensures that valid data allows the user to update their profile information.
     */
    public function test_an_authenticated_user_can_modify_their_data()
    {
        // Given: Valid user data
        $data = [
            "name" => "New Name",
            "last_name" => "New Last Name",
            "phone" => "1-402-947-9541",
        ];

        // When: The user attempts to update their profile
        $user = User::find(1)->first(); // Find the user with ID 1
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'PUT', route('profile.update'), $data);
        
        // The response should return a 200 status with a success message
        // dd($response->json());

        // Then: The response should return a 200 status with a success message
        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'status', 'message']);
        $response->assertJsonFragment([
            'data' => [
                'user' => [
                    "id"        => 1,
                    "email"     => "example@example.com",
                    "name"      => $data['name'],
                    "last_name" => $data['last_name'],
                    "phone"     => $data['phone'],
                    "role"      => null,
                ],
            ],
            'status' => 200,
            'message' => __('messages.user.info_updated')
        ]);

        // Ensure the old data is not present in the database
        $this->assertDatabaseMissing('users', [
            'email'     => 'example@example.com',
            "name"      => "Example",
            "last_name" => "Example Example",
        ]);
    }

    /**
     * Test that an authenticated user cannot modify their email.
     * This ensures that the email field is ignored during profile updates.
     */
    public function test_an_authenticated_user_cannot_modify_their_email()
    {
        // Given: Data with a new email
        $data = [
            "email"     => "newemail@example.com",
            "name"      => "New Name",
            "last_name" => "New Last Name",
            "phone"     => "1-972-924-0022",
        ];

        // When: The user attempts to update their profile
        $user = User::find(1)->first(); // Find the user with ID 1
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'PUT', route('profile.update'), $data);

        // Then: The response should return a 200 status with a success message
        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'status', 'message']);
        $response->assertJsonFragment([
            'data' => [
                'user' => [
                    "id"        => 1,
                    "email"     => "example@example.com", // Email should remain unchanged
                    "name"      => "New Name",
                    "last_name" => "New Last Name",
                    "phone"     => "1-972-924-0022",
                    "role"      => null
                ],
            ],
            'status' => 200,
            'message' => __('messages.user.info_updated')
        ]);

        // Ensure the email was not changed in the database
        $this->assertDatabaseHas('users', [
            'email'     => 'example@example.com',
            "name"      => "New Name",
            "last_name" => "New Last Name",
        ]);
    }

    /**
     * Test that an authenticated user cannot modify their password.
     * This ensures that the password field is ignored during profile updates.
     */
    public function test_an_authenticated_user_cannot_modify_their_password()
    {
        // Given: Data with a new password
        $data = [
            "password"  => "newpassword",
            "name"      => "New Name",
            "last_name" => "New Last Name",
        ];

        // When: The user attempts to update their profile
        $user = User::find(1)->first(); // Find the user with ID 1
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'PUT', route('profile.update'), $data);

        // Then: The response should return a 200 status with a success message
        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'status', 'message']);

        $user->refresh(); // Refresh the user instance to get the updated data
        $this->assertFalse(Hash::check($data['password'], $user->password)); // Verify the password was not updated
    }

    /**
     * Test that the name field is required.
     * This ensures that missing the name field returns a 422 status with validation errors.
     */
    public function test_name_must_be_required(): void
    {
        // Given: Missing name field
        $data = [
            "name"      => "",
            "last_name" => "Example Example",
        ];

        // When: The user attempts to update their profile
        $user = User::find(1)->first(); // Find the user with ID 1
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'PUT', route('profile.update'), $data);

        // Then: The response should return a 422 status with validation errors for the name field
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'status', 'message', 'errors' => ['name']
        ]);
        $response->assertJsonFragment([
            'name' => [
                __('validation.required', [
                    'attribute' => __('validation.attributes.name')
                ]),
            ]
        ]);
    }

    /**
     * Test that the last name must have at least 2 characters.
     * This ensures that short last names return a 422 status with validation errors.
     */
    public function test_last_name_must_have_at_least_2_characters(): void
    {
        // Given: Last name with less than 2 characters
        $data = [
            "name"      => "Example",
            "last_name" => "E",
        ];

        // When: The user attempts to update their profile
        $user = User::find(1)->first(); // Find the user with ID 1
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'PUT', route('profile.update'), $data);

        // Then: The response should return a 422 status with validation errors for the last name field
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'status', 'message', 'errors' => ['last_name']
        ]);
        $response->assertJsonFragment([
            'last_name' => [
                __('validation.min.string', [
                    'attribute' => __('validation.attributes.last_name'),
                    'min' => 2,
                ]),
            ]
        ]);
    }
}
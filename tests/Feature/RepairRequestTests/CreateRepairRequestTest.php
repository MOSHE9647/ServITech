<?php

namespace Tests\Feature\RepairRequestTests;

use App\Enums\RepairStatus;
use App\Enums\UserRoles;
use App\Models\RepairRequest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateRepairRequestTest extends TestCase
{
    use RefreshDatabase; // Reset the database after each test

    /**
     * Set up the test environment.
     * This method seeds the database before each test.
     */
    protected function setUp(): void
    {
        parent::setUp(); // Call the parent setUp method
        $this->seed(class: DatabaseSeeder::class); // Seed the database
    }
    
    public function test_repair_request_is_created_with_unique_receipt_number() {
        $repairRequest = RepairRequest::factory()->create();
        // dd($repairRequest);

        $this->assertNotNull($repairRequest->receipt_number);
        $this->assertMatchesRegularExpression('/^RR-\d{12}$/', $repairRequest->receipt_number);
    }

    public function test_an_authenticated_admin_user_can_create_repair_requests()
    {
        // Given: A valid repair request payload
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);

        // Then: The request should succeed, and the repair request should be stored in the database
        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'status', 'message']);
        $response->assertJsonFragment([
            'status' => 200,
            'message'=> __('messages.repair_request.created'),
            'data' => [
                'repairRequest' => array_merge($repairRequest, [
                    'id' => 1,
                    'receipt_number'=> "RR-000000000001",
                    'created_at'=> now()->format('Y-m-d\TH:i:s.000000\Z'),
                    'updated_at'=> now()->format('Y-m-d\TH:i:s.000000\Z'),
                ]),
            ],
        ]);

        $this->assertDatabaseHas('repair_requests', $repairRequest);
    }

    public function test_a_non_authenticated_admin_user_can_not_create_repair_requests()
    {
        // Given: A valid repair request payload
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // Create a non-admin user
        $user = User::create([
            "name" => "Example",
            "last_name" => "Example Example",
            'email' => 'example@example.com',
            'password'=> bcrypt('password'),
        ]);
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists
        $user->assignRole(UserRoles::USER);

        // When: The non-admin user attempts to create a repair request
        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);

        // Then: The request should fail with a 403 Forbidden status
        $response->assertStatus(403);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 403,
            'message'=> __('User does not have the right roles.'),
        ]);
    }

    public function test_customer_name_must_be_required()
    {
        // Given: A repair request payload with a missing customer name
        $repairRequest = [
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);
        // dd($response->json());

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'customer_name' => [
                    __('validation.required', [
                        'attribute' => __('validation.attributes.customer_name')
                    ])
                ],
            ],
        ]);
    }

    public function test_customer_name_must_be_a_string()
    {
        // Given: A repair request payload with a non-string customer name
        $repairRequest = [
            "customer_name"         => 12345678,
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);
        // dd($response->json());

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'customer_name' => [
                    __('validation.string', [
                        'attribute' => __('validation.attributes.customer_name')
                    ])
                ],
            ],
        ]);
    }

    public function test_customer_name_must_have_at_least_3_caracters()
    {
        // Given: A repair request payload with a customer name that has less than 3 characters
        $repairRequest = [
            "customer_name"         => "Ej",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);
        // dd($response->json());

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'customer_name' => [
                    __('validation.min.string', [
                        'attribute' => __('validation.attributes.customer_name'),
                        'min' => 3,
                    ])
                ],
            ],
        ]);
    }

    public function test_customer_phone_must_be_required()
    {
        // Given: A repair request payload with a missing customer phone
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);
        // dd($response->json());

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'customer_phone' => [
                    __('validation.required', [
                        'attribute' => __('validation.attributes.phone')
                    ])
                ],
            ],
        ]);
    }

    public function test_customer_phone_must_be_a_string()
    {
        // Given: A repair request payload with a non-string customer phone
        $repairRequest = [
            'customer_name'         => "Juan Pérez",
            'customer_phone'        => 12345678,
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);
        // dd($response->json());

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'customer_phone' => [
                    __('validation.string', [
                        'attribute' => __('validation.attributes.phone'),
                        'min' => 3,
                    ])
                ],
            ],
        ]);
    }

    public function test_customer_phone_must_have_at_least_8_caracters()
    {
        // Given: A repair request payload with a customer phone that has less than 8 characters
        $repairRequest = [
            'customer_name'         => "Juan Pérez",
            'customer_phone'        => "1234567",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);
        // dd($response->json());

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'customer_phone' => [
                    __('validation.min.string', [
                        'attribute' => __('validation.attributes.phone'),
                        'min' => 8,
                    ])
                ],
            ],
        ]);
    }

    public function test_customer_email_must_be_required()
    {
        // Given: A repair request payload with a missing customer email
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);
        // dd($response->json());

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'customer_email' => [
                    __('validation.required', [
                        'attribute' => __('validation.attributes.email')
                    ])
                ],
            ],
        ]);
    }

    public function test_customer_email_must_be_a_valid_email()
    {
        // Given: A repair request payload with an invalid customer email
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);
        // dd($response->json());

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'customer_email' => [
                    __('validation.email', [
                        'attribute' => __('validation.attributes.email')
                    ])
                ],
            ],
        ]);
    }

    public function test_article_name_must_be_required()
    {
        // Given: A repair request payload with a missing article name
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);
        // dd($response->json());

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_name' => [
                    __('validation.required', [
                        'attribute' => __('validation.attributes.article_name')
                    ])
                ],
            ],
        ]);
    }

    public function test_article_name_must_be_a_string()
    {
        // Given: A repair request payload with a non-string article name
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => 12345678,
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);
        // dd($response->json());

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_name' => [
                    __('validation.string', [
                        'attribute' => __('validation.attributes.article_name')
                    ])
                ],
            ],
        ]);
    }

    public function test_article_name_must_have_at_least_3_caracters()
    {
        // Given: A repair request payload with a article name that has less than 3 characters
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Ej",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);
        // dd($response->json());

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_name' => [
                    __('validation.min.string', [
                        'attribute' => __('validation.attributes.article_name'),
                        'min' => 3,
                    ])
                ],
            ],
        ]);
    }

    public function test_article_type_must_be_required()
    {
        // Given: A repair request payload with a missing article type
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);
        // dd($response->json());

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_type' => [
                    __('validation.required', [
                        'attribute' => __('validation.attributes.article_type')
                    ])
                ],
            ],
        ]);
    }

    public function test_article_type_must_be_a_string()
    {
        // Given: A repair request payload with a non-string article type
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => 12345678,
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);
        // dd($response->json());

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_type' => [
                    __('validation.string', [
                        'attribute' => __('validation.attributes.article_type')
                    ])
                ],
            ],
        ]);
    }

    public function test_article_type_must_have_at_least_3_caracters()
    {
        // Given: A repair request payload with a article type that has less than 3 characters
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Ej",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);
        // dd($response->json());

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_type' => [
                    __('validation.min.string', [
                        'attribute' => __('validation.attributes.article_type'),
                        'min' => 3,
                    ])
                ],
            ],
        ]);
    }

    public function test_article_brand_must_be_required()
    {
        // Given: A repair request payload with a missing article brand
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_brand' => [
                    __('validation.required', [
                        'attribute' => __('validation.attributes.article_brand')
                    ])
                ],
            ],
        ]);
    }

    public function test_article_brand_must_be_a_string()
    {
        // Given: A repair request payload with a non-string article brand
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => 12345678,
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_brand' => [
                    __('validation.string', [
                        'attribute' => __('validation.attributes.article_brand')
                    ])
                ],
            ],
        ]);
    }

    public function test_article_brand_must_have_at_least_2_characters()
    {
        // Given: A repair request payload with an article brand that has less than 2 characters
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "A",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_brand' => [
                    __('validation.min.string', [
                        'attribute' => __('validation.attributes.article_brand'),
                        'min' => 2,
                    ])
                ],
            ],
        ]);
    }

    public function test_article_model_must_be_required()
    {
        // Given: A repair request payload with a missing article model
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_model' => [
                    __('validation.required', [
                        'attribute' => __('validation.attributes.article_model')
                    ])
                ],
            ],
        ]);
    }

    public function test_article_model_must_be_a_string()
    {
        // Given: A repair request payload with a non-string article model
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => 12345678,
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_model' => [
                    __('validation.string', [
                        'attribute' => __('validation.attributes.article_model')
                    ])
                ],
            ],
        ]);
    }

    public function test_article_model_must_have_at_least_2_characters()
    {
        // Given: A repair request payload with an article model that has less than 2 characters
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "A",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_model' => [
                    __('validation.min.string', [
                        'attribute' => __('validation.attributes.article_model'),
                        'min' => 2,
                    ])
                ],
            ],
        ]);
    }

    public function test_article_serialnumber_can_be_nullable()
    {
        // Given: A repair request payload with a missing article serial number
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => null, // Nullable field
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);

        // Then: The request should succeed with a 200 OK status
        $response->assertStatus(200);
        $this->assertDatabaseHas('repair_requests', [
            "article_serialnumber" => null
        ]);
    }

    public function test_article_serialnumber_must_be_a_string()
    {
        // Given: A repair request payload with a non-string article serial number
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => 123456, // Invalid type
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_serialnumber' => [
                    __('validation.string', [
                        'attribute' => __('validation.attributes.serialnumber')
                    ])
                ],
            ],
        ]);
    }

    public function test_article_serialnumber_must_have_at_least_6_characters()
    {
        // Given: A repair request payload with an article serial number that has less than 6 characters
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "12345", // Too short
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_serialnumber' => [
                    __('validation.min.string', [
                        'attribute' => __('validation.attributes.serialnumber'),
                        'min' => 6,
                    ])
                ],
            ],
        ]);
    }

    public function test_article_accesories_can_be_nullable()
    {
        // Given: A repair request payload with a missing article accessories
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => null, // Nullable field
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);

        // Then: The request should succeed with a 200 OK status
        $response->assertStatus(200);
        $this->assertDatabaseHas('repair_requests', [
            "article_accesories" => null
        ]);
    }

    public function test_article_accesories_must_be_a_string()
    {
        // Given: A repair request payload with a non-string article accessories
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => 12345, // Invalid type
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_accesories' => [
                    __('validation.string', [
                        'attribute' => __('validation.attributes.accesories')
                    ])
                ],
            ],
        ]);
    }

    public function test_article_accesories_must_have_at_least_3_characters()
    {
        // Given: A repair request payload with article accessories that have less than 3 characters
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "AB", // Too short
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_accesories' => [
                    __('validation.min.string', [
                        'attribute' => __('validation.attributes.accesories'),
                        'min' => 3,
                    ])
                ],
            ],
        ]);
    }

    public function test_article_problem_must_be_required()
    {
        // Given: A repair request payload with a missing article problem
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_problem' => [
                    __('validation.required', [
                        'attribute' => __('validation.attributes.article_problem')
                    ])
                ],
            ],
        ]);
    }

    public function test_article_problem_must_be_a_string()
    {
        // Given: A repair request payload with a non-string article problem
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => 12345, // Invalid type
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_problem' => [
                    __('validation.string', [
                        'attribute' => __('validation.attributes.article_problem')
                    ])
                ],
            ],
        ]);
    }

    public function test_article_problem_must_have_at_least_3_characters()
    {
        // Given: A repair request payload with an article problem that has less than 3 characters
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "AB", // Too short
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_problem' => [
                    __('validation.min.string', [
                        'attribute' => __('validation.attributes.article_problem'),
                        'min' => 3,
                    ])
                ],
            ],
        ]);
    }

    public function test_repair_status_must_be_required()
    {
        // Given: A repair request payload with a missing repair status
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'repair_status' => [
                    __('validation.required', [
                        'attribute' => __('validation.attributes.repair_status')
                    ])
                ],
            ],
        ]);
    }

    public function test_repair_status_must_be_a_string()
    {
        // Given: A repair request payload with a non-string repair status
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => 12345, // Invalid type
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'repair_status' => [
                    __('validation.string', [
                        'attribute' => __('validation.attributes.repair_status')
                    ]),
                    __('validation.enum', [
                        'attribute' => __('validation.attributes.repair_status'),
                        'values' => implode(', ', array_column(RepairStatus::cases(), 'value'))
                    ])
                ],
            ],
        ]);
    }

    public function test_repair_status_must_be_a_valid_enum_value()
    {
        // Given: A repair request payload with an invalid repair status
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => "INVALID_STATUS", // Invalid enum value
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'repair_status' => [
                    __('validation.enum', [
                        'attribute' => __('validation.attributes.repair_status'),
                        'values' => implode(', ', array_column(RepairStatus::cases(), 'value'))
                    ])
                ],
            ],
        ]);
    }

    public function test_repair_details_can_be_nullable()
    {
        // Given: A repair request payload with a missing repair details
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => null, // Nullable field
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);

        // Then: The request should succeed with a 200 OK status
        $response->assertStatus(200);
        $this->assertDatabaseHas('repair_requests', [
            "repair_details" => null
        ]);
    }

    public function test_repair_details_must_be_a_string()
    {
        // Given: A repair request payload with a non-string repair details
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => 12345, // Invalid type
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'repair_details' => [
                    __('validation.string', [
                        'attribute' => __('validation.attributes.repair_details')
                    ])
                ],
            ],
        ]);
    }

    public function test_repair_details_must_have_at_least_3_characters()
    {
        // Given: A repair request payload with repair details that have less than 3 characters
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "AB", // Too short
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'repair_details' => [
                    __('validation.min.string', [
                        'attribute' => __('validation.attributes.repair_details'),
                        'min' => 3,
                    ])
                ],
            ],
        ]);
    }

    public function test_repair_price_can_be_nullable()
    {
        // Given: A repair request payload with a missing repair price
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => null, // Nullable field
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);

        // Then: The request should succeed with a 200 OK status
        $response->assertStatus(200);
        $this->assertDatabaseHas('repair_requests', [
            "repair_price" => null
        ]);
    }

    public function test_repair_price_must_be_numeric()
    {
        // Given: A repair request payload with a non-numeric repair price
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "invalid_price", // Invalid type
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'repair_price' => [
                    __('validation.numeric', [
                        'attribute' => __('validation.attributes.repair_price')
                    ])
                ],
            ],
        ]);
    }

    public function test_received_at_must_be_required()
    {
        // Given: A repair request payload with a missing received_at
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => null, // Missing field
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'received_at' => [
                    __('validation.required', [
                        'attribute' => __('validation.attributes.received_at')
                    ])
                ],
            ],
        ]);
    }

    public function test_received_at_must_be_a_valid_date()
    {
        // Given: A repair request payload with an invalid received_at
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "invalid_date", // Invalid date
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'received_at' => [
                    __('validation.date', [
                        'attribute' => __('validation.attributes.received_at')
                    ])
                ],
            ],
        ]);
    }

    public function test_repaired_at_can_be_nullable()
    {
        // Given: A repair request payload with a missing repaired_at
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null // Nullable field
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);

        // Then: The request should succeed with a 200 OK status
        $response->assertStatus(200);
        $this->assertDatabaseHas('repair_requests', [
            "repaired_at" => null
        ]);
    }

    public function test_repaired_at_must_be_a_valid_date()
    {
        // Given: A repair request payload with an invalid repaired_at
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => "invalid_date" // Invalid date
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'repaired_at' => [
                    __('validation.date', [
                        'attribute' => __('validation.attributes.repaired_at')
                    ])
                ],
            ],
        ]);
    }
}
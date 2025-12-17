<?php

declare(strict_types=1);

namespace Tests\Unit\Notes;

use App\Http\Requests\Notes\StoreNoteRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class NoteRequestValidationTest extends TestCase
{
    public function test_locked_visibility_requires_pin(): void
    {
        $request = new StoreNoteRequest();

        $data = [
            'title' => 'Locked Note',
            'body' => 'Secret',
            'visibility' => 'locked',
            'pin' => null,
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('pin', $validator->errors()->toArray());
    }

    public function test_shared_visibility_does_not_require_pin(): void
    {
        $request = new StoreNoteRequest();

        $data = [
            'title' => 'Shared Note',
            'body' => 'Info',
            'visibility' => 'shared',
            'pin' => null,
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->fails());
    }
}






<?php

namespace App\Http\Requests\Api\V2\User;

use App\Rules\Phone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddUserRequests extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'active' => 'sometimes|required|boolean',
            'name' => 'required|string',
            'last_name' => 'required|string',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email'),
            ],
            'phone' => [
                'sometimes',
                'required',
                new Phone(),
                Rule::unique('users', 'phone'),
            ],
            'password' => 'required|string',
        ];
    }

    /**
     * @param array $content
     * @return array
     */
    public function prepareBeforeValidation(array $content, Model $model): array
    {
        if (isset($content['active'])) {
            $content['active'] = toBool($content['active']);
        }

        if (isset($content['password'])) {
            $content['password'] = bcrypt($content['password']);
        }

        return $content;
    }
}

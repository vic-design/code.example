<?php

namespace App\Http\Requests\Api\V2\User;

use App\Rules\Media;
use App\Rules\Phone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditUserRequests extends FormRequest
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
            'name' => 'sometimes|required|string:191',
            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('users', 'email')->ignore(request('user')),
            ],
            'phone' => [
                'sometimes',
                'required',
                new Phone(),
                Rule::unique('users', 'phone')->ignore(request('user')),
            ],
            'patronymic' => 'sometimes|string|max:191',
            'last_name' => 'sometimes|string|max:191',
            'city' => 'sometimes|string|max:191',
            'address' => 'sometimes|string|max:191',
            'avatar.*' => ['sometimes', 'nullable', new Media()]
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

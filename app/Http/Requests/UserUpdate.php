<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserUpdate extends FormRequest
{

    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'phone' => 'sometimes|nullable|string|min:6|max:20',
            'phone2' => 'sometimes|nullable|string|min:6|max:20',
            'email' => 'sometimes|nullable|email|max:100|unique:users,id',
            'username' => 'sometimes|nullable|alpha_dash|min:8|max:100|unique:users',
            'photo' => 'sometimes|nullable|image|mimes:jpeg,gif,png,jpg|max:2048',
            'address' => 'required|string|min:6|max:120'
        ];
    }

    public function attributes()
    {
        return  [
            'nal_id' => 'Nationality',
            'state_id' => 'State',
            'lga_id' => 'LGA',
            'phone2' => 'Telephone',
        ];
    }

    /**
     * Get the data to be validated from the request.
     * Fixes PHP 8.1+ compatibility issue with file uploads
     */
    public function validationData()
    {
        // Build validation data manually to avoid file conversion errors
        $data = [];
        
        foreach (array_keys($this->rules()) as $field) {
            if ($field === 'photo') {
                // Only include photo if it's actually provided
                if ($this->hasFile('photo')) {
                    $data['photo'] = $this->file('photo');
                }
            } else {
                // For other fields, use the regular input method
                if ($this->has($field)) {
                    $data[$field] = $this->input($field);
                }
            }
        }
        
        return $data;
    }
}

<?php

namespace App\Http\Requests\TimeTable;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TTRecordRequest extends FormRequest
{

    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * @return array
     */
    public function rules()
    {
        $schoolId = app()->bound('currentSchool') ? app('currentSchool')->id : null;

        if ($this->method() === 'POST') {
            return [
                'name' => [
                    'required',
                    'string',
                    'min:3',
                    Rule::unique('time_table_records')->where('school_id', $schoolId),
                ],
                'my_class_id' => 'required',
            ];
        }

        return [
            'name' => [
                'required',
                'string',
                'min:3',
                Rule::unique('time_table_records')->where('school_id', $schoolId)->ignore($this->ttr),
            ],
            'my_class_id' => 'required',
        ];
    }

    public function attributes()
    {
        return  [
            'my_class_id' => 'Class',
        ];
    }
}

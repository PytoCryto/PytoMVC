<?php

namespace PytoMVC\System\Traits;

use PytoMVC\System\Http\Request;
use Illuminate\Support\Facades\Validator;

trait ValidatingAwareTrait
{
    /**
     * Validate the input data
     * 
     * @return bool
     */
    public function validate($data, array $rules = [], array $messages = [], array $customAttributes = [])
    {
        if ($data instanceof Request) {
            $data = $data->all();
        }

        $validator = Validator::make((array) $data, $rules, $messages, $customAttributes);

        if ($validator->passes()) {
            $this->afterIsValid($validator); // oh boy.. should've used the symfony event dispatcher :(

            return true;
        }

        foreach ($validator->errors()->all() as $message) {
            app('request')->getFlash()->error($message);
        }

        return false;
    }

    /**
     * Execute the contents of this method if the validation passes
     * 
     * @param  \PytoMVC\System\Validation\Validator $validator 
     * @return null
     */
    public function afterIsValid($validator)
    {
        //
    }
}

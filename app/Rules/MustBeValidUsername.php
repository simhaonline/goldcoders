<?php

namespace App\Rules;

use App\Models\User;
use Illuminate\Contracts\Validation\Rule;

class MustBeValidUsername implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Username Is Not Registered!';
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $user = new User;
        $user = $user->findForPassport($value);
        return null !== $user;
    }
}

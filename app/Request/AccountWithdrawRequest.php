<?php
declare(strict_types=1);

namespace App\Request;

use Hyperf\Validation\Request\FormRequest;

class AccountWithdrawRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
            'method' => ['required', 'in:PIX'],
            'pix.type' => ['required_with:pix', 'in:email'],
            'pix.key' => ['required_with:pix', 'email'],
            'schedule' => ['nullable', 'date'],
        ];
    }
}

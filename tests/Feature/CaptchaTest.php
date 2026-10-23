<?php

namespace Tests\Feature;

use Tests\TestCase;

class CaptchaTest extends TestCase
{
    public function test_captcha_image_is_served_and_code_stored_in_session(): void
    {
        $response = $this->get(route('captcha'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
        $response->assertSessionHas('captcha_code');
        $this->assertSame(5, strlen(session('captcha_code')));
    }

    public function test_login_requires_captcha(): void
    {
        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => 'admin@bumdesma.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('captcha');
        $this->assertGuest();
    }

    public function test_login_rejects_wrong_captcha_and_consumes_it(): void
    {
        $response = $this->from(route('login'))
            ->withSession(['captcha_code' => 'ABCDE'])
            ->post(route('login.store'), [
                'email' => 'admin@bumdesma.test',
                'password' => 'password',
                'captcha' => 'ZZZZZ',
            ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('captcha');
        $response->assertSessionMissing('captcha_code');
        $this->assertGuest();
    }

    public function test_captcha_check_is_case_insensitive(): void
    {
        $request = \Illuminate\Http\Request::create('/login', 'POST');
        $request->setLaravelSession(app('session.store'));
        $request->session()->put('captcha_code', 'aB3dE');

        $this->assertTrue(\App\Http\Controllers\CaptchaController::matches($request, ' ab3De '));
    }
}
